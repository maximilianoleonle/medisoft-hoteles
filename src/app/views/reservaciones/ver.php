<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista de Detalle de Reservación - Diseño Moderno y Colorido
 * Vista hotelera
 */

$estado_info = $estados[$reservacion['estado']] ?? ['label' => 'Desconocido', 'color' => 'gray'];
$pagos = $pagos ?? [];
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

// Detectar si acaba de hacerse un check-in exitoso para auto-descargar ticket
$auto_imprimir_ticket = false;
if (isset($_SESSION['flash_message']) &&
    $_SESSION['flash_message']['tipo'] === 'success' &&
    stripos($_SESSION['flash_message']['texto'], 'Check-in') !== false) {
    $auto_imprimir_ticket = true;
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
$rdCheckinJsMode = $rdCheckinMode === 'express' ? 'express' : 'normal_tardio';
$rdHuespedNombreJsonAttr = htmlspecialchars(json_encode($rdHuespedNombre, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdHabitacionesTextoJsonAttr = htmlspecialchars(json_encode($rdHabitacionesTexto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdFechaEntradaFormatoJsonAttr = htmlspecialchars(json_encode($rdFechaEntrada !== '' ? date('d/m/Y', strtotime($rdFechaEntrada)) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdFechaSalidaFormatoJsonAttr = htmlspecialchars(json_encode($rdFechaSalida !== '' ? date('d/m/Y', strtotime($rdFechaSalida)) : '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
$rdMetodoPagoLabel = !empty($reservacion['metodo_pago'])
    ? ucfirst(str_replace('_', ' ', (string)$reservacion['metodo_pago']))
    : 'Pendiente';
$rdNoches = 1;
if ($rdFechaEntrada !== '' && $rdFechaSalida !== '') {
    $rdEntradaDate = new DateTime($rdFechaEntrada);
    $rdSalidaDate = new DateTime($rdFechaSalida);
    $rdNoches = $rdEntradaDate->diff($rdSalidaDate)->days ?: 1;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700;800;900&display=swap');

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
    font-weight: 800;
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
    --rd-serif: "Cormorant Garamond", Georgia, serif;
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
    font-weight: 900;
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
    font-weight: 850;
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
    font-weight: 850;
}

.reservation-detail-v2 .info-value {
    min-width: 0;
    color: var(--rd-brand);
    font-size: .88rem;
    font-weight: 900;
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
    font-weight: 950;
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
    font-weight: 850;
}

.reservation-detail-v2 .room-paid-summary strong {
    display: block;
    color: var(--rd-brand);
    font-size: clamp(1.05rem, 2vw, 1.3rem);
    font-weight: 950;
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
    font-weight: 850;
}

.reservation-detail-v2 .room-price-value {
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 950;
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
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.reservation-detail-v2 .rd-strip-item strong {
    color: #182033;
    font-size: 1rem;
    font-weight: 950;
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
    font-weight: 950;
}

.reservation-detail-v2 .rd-room-link {
    padding: 4px 7px;
    margin: -4px -7px;
    color: var(--rd-brand) !important;
    font-weight: 950;
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
    font-weight: 850;
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
$rdPayments = is_array($pagos ?? null) ? $pagos : [];
$rdNotes = is_array($notas ?? null) ? $notas : [];
$rdDocuments = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];
$rdDocsContext = is_array($documentosEntidadContexto ?? null) ? $documentosEntidadContexto : [];
$rdVehicles = is_array($vehiculos ?? null) ? $vehiculos : [];
$rdTotal = (float)($reservacion['precio_total'] ?? 0);
$rdTotalPaid = 0.0;
foreach ($rdPayments as $rdPaymentRow) {
    $rdTotalPaid += (float)($rdPaymentRow['monto'] ?? $rdPaymentRow['cantidad'] ?? 0);
}
if ($rdTotalPaid <= 0 && !empty($reservacion['metodo_pago'])) {
    $rdTotalPaid = $rdTotal;
}
$rdPaymentLabel = !empty($reservacion['metodo_pago']) ? 'Pagado - ' . $rdMetodoPagoLabel : 'Pago pendiente';
$rdCortesias = (int)($reservacion['habitaciones_cortesia'] ?? 0);
foreach ($rdRooms as $rdRoomCountRow) {
    if (!empty($rdRoomCountRow['es_cortesia']) || !empty($rdRoomCountRow['cortesia'])) {
        $rdCortesias++;
    }
}
$rdGuestName = trim((string)($huesped['nombre_completo'] ?? $rdHuespedNombre ?? 'Huesped'));
$rdGuestPhone = trim((string)($huesped['telefono'] ?? $huesped['celular'] ?? $huesped['telefono_principal'] ?? ''));
$rdGuestEmail = trim((string)($huesped['email'] ?? $huesped['correo'] ?? ''));
$rdGuestIdType = trim((string)($huesped['tipo_identificacion'] ?? $huesped['identificacion_tipo'] ?? ''));
$rdGuestIdNumber = trim((string)($huesped['numero_identificacion'] ?? $huesped['identificacion_numero'] ?? $huesped['identificacion'] ?? ''));
$rdGuestId = trim(($rdGuestIdType !== '' ? $rdGuestIdType . ' - ' : '') . $rdGuestIdNumber);
$rdGuestOriginParts = array_filter([
    trim((string)($huesped['procedencia_ciudad'] ?? $huesped['ciudad'] ?? '')),
    trim((string)($huesped['procedencia_estado'] ?? $huesped['estado'] ?? '')),
    trim((string)($huesped['pais'] ?? '')),
]);
$rdGuestOrigin = !empty($huesped['procedencia']) ? (string)$huesped['procedencia'] : implode(', ', $rdGuestOriginParts);
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

<script>
function medisoftVolverAnterior(event) {
    event.preventDefault();
    if (window.history.length > 1) {
        window.history.back();
        return;
    }
    window.location.href = event.currentTarget.getAttribute('href');
}
</script>

<style>
.rdv3 {
    --rdv3-primary: var(--brand-primary, #172342);
    --rdv3-primary-2: var(--brand-secondary, #33415f);
    --rdv3-accent: var(--brand-accent, #b78b42);
    --rdv3-bg: color-mix(in srgb, var(--brand-accent, #b78b42) 9%, #f8f4eb);
    --rdv3-ink: #24304a;
    --rdv3-muted: #8790a7;
    --rdv3-line: rgba(36, 48, 74, .12);
    --rdv3-card: rgba(255, 255, 255, .94);
    --rdv3-blue: color-mix(in srgb, var(--brand-primary, #1f5da8) 76%, #2563eb);
    --rdv3-green: #24b777;
    --rdv3-violet: #6757e8;
    --rdv3-gold: color-mix(in srgb, var(--brand-accent, #b78b42) 82%, #f0b84c);
    --rdv3-cyan: #13a8c6;
    min-height: 100dvh;
    margin: 0;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--rdv3-accent) 14%, transparent) 0, transparent 34rem),
        var(--rdv3-bg);
    color: var(--rdv3-ink);
    font-family: Manrope, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
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
.rdv3-crumbs { color: #8a93a7; font-size: .78rem; font-weight: 900; }
.rdv3-crumbs strong { color: var(--rdv3-primary); }
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
    background:
        radial-gradient(circle at 100% 50%, rgba(255, 255, 255, .14) 0 8rem, transparent 8.2rem),
        linear-gradient(135deg, var(--rdv3-primary), color-mix(in srgb, var(--rdv3-primary) 86%, #51607b));
    color: #fff;
    box-shadow: 0 18px 38px rgba(23, 35, 66, .25);
}
.rdv3-titleline { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; }
.rdv3-titleline h1 {
    margin: 0;
    font-family: "Cormorant Garamond", Georgia, serif;
    font-size: clamp(2rem, 2vw, 2.75rem);
    line-height: .95;
    font-weight: 800;
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
    font-weight: 900;
}
.rdv3-status::before { content: ""; width: 7px; height: 7px; border-radius: 999px; background: currentColor; }
.rdv3-status--ok { background: rgba(50, 185, 122, .19); color: #82e0ae; border: 1px solid rgba(130, 224, 174, .2); }
.rdv3-status--wait { background: rgba(245, 181, 64, .18); color: #f2cf8c; border: 1px solid rgba(242, 207, 140, .2); }
.rdv3-status--danger { background: rgba(239, 68, 68, .18); color: #fecaca; border: 1px solid rgba(254, 202, 202, .2); }
.rdv3-meta { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 16px; color: rgba(255, 255, 255, .72); font-size: .82rem; font-weight: 800; }
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
    font-weight: 950;
    cursor: pointer;
}
.rdv3-btn-primary { background: #22b77a; color: #fff; box-shadow: 0 12px 24px rgba(34, 183, 122, .25); }
.rdv3-btn-primary:hover { background: #1fa66f; }
.rdv3-btn-ghost { background: rgba(255, 255, 255, .11); color: #fff; border: 1px solid rgba(255, 255, 255, .24); }
.rdv3-btn-danger { background: rgba(255, 255, 255, .09); color: #fff; border: 1px solid rgba(255, 255, 255, .22); }
.rdv3-layout { display: grid; grid-template-columns: minmax(0, 1fr) clamp(318px, 24vw, 360px); gap: 26px; margin-top: 26px; align-items: start; }
.rdv3-left, .rdv3-right { display: grid; gap: 24px; min-width: 0; }
.rdv3-right { position: static; align-self: start; }
.rdv3-card {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 18%, rgba(255,255,255,.8));
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 5%, #fff) 0, rgba(255,255,255,.96) 9rem),
        var(--rdv3-card);
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
    background:
        radial-gradient(circle at 52% 48%,
            color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 20%, transparent) 0 24%,
            color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 10%, transparent) 42%,
            transparent 72%);
    opacity: .72;
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
.rdv3-card-title { margin: 0; color: var(--rdv3-primary); font-family: "Cormorant Garamond", Georgia, serif; font-size: 1.22rem; line-height: 1.1; font-weight: 800; overflow-wrap: anywhere; }
.rdv3-card-body { position: relative; z-index: 1; padding: 12px 42px 32px; }
.rdv3-stay { display: grid; grid-template-columns: minmax(0, 1fr) 82px minmax(0, 1fr); border: 1px solid color-mix(in srgb, var(--rdv3-blue) 18%, transparent); border-radius: 16px; overflow: hidden; background: linear-gradient(90deg, rgba(59,130,246,.08), rgba(255,255,255,.78) 44%, rgba(14,165,233,.07)); }
.rdv3-date { padding: 18px; }
.rdv3-date:first-child { background: linear-gradient(90deg, rgba(59,130,246,.08), transparent); }
.rdv3-date:last-child { background: linear-gradient(270deg, rgba(14,165,233,.08), transparent); }
.rdv3-date:last-child { text-align: right; }
.rdv3-label { display: block; color: #a3acbd; font-size: .69rem; font-weight: 950; text-transform: uppercase; letter-spacing: .03em; }
.rdv3-label--green { color: #26a96f; }
.rdv3-date strong { display: block; margin-top: 6px; color: var(--rdv3-blue); font-family: "Cormorant Garamond", Georgia, serif; font-size: 1.35rem; line-height: 1; }
.rdv3-date span { display: block; margin-top: 6px; color: #8992a5; font-size: .78rem; font-weight: 800; }
.rdv3-nights { display: grid; place-items: center; text-align: center; border-left: 1px solid color-mix(in srgb, var(--rdv3-blue) 16%, transparent); border-right: 1px solid color-mix(in srgb, var(--rdv3-blue) 16%, transparent); color: var(--rdv3-blue); background: rgba(255, 255, 255, .72); }
.rdv3-nights b { display: block; font-size: 1.35rem; line-height: 1; }
.rdv3-nights span { display: block; margin-top: 5px; color: #a3acbd; font-size: .62rem; font-weight: 950; text-transform: uppercase; }
.rdv3-total { margin-top: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 86px; padding: 17px 18px; border-radius: 16px; border: 1px solid rgba(45, 189, 121, .26); background: radial-gradient(circle at 12% 10%, rgba(45, 189, 121, .17), transparent 18rem), linear-gradient(90deg, rgba(45, 189, 121, .16), rgba(45, 189, 121, .07)); box-shadow: inset 0 0 0 1px rgba(255,255,255,.55); }
.rdv3-total .rdv3-amount { margin-top: 5px; font-family: "Cormorant Garamond", Georgia, serif; font-size: 1.7rem; font-weight: 800; color: var(--rdv3-primary); }
.rdv3-pill { display: inline-flex; align-items: center; gap: 7px; min-height: 28px; padding: 0 12px; border-radius: 999px; background: #fff; color: #37b77d; font-size: .74rem; font-weight: 950; }
.rdv3-res-note { margin-top: 16px; min-height: 42px; display: flex; align-items: center; gap: 9px; padding: 11px 14px; border-radius: 12px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 24%, transparent); background: color-mix(in srgb, var(--rdv3-accent) 12%, #fff); color: color-mix(in srgb, var(--rdv3-accent) 72%, #5c4927); font-size: .84rem; font-weight: 800; }
.rdv3-badge { display: inline-flex; align-items: center; gap: 7px; min-height: 28px; padding: 0 11px; border-radius: 999px; background: color-mix(in srgb, var(--rdv3-accent) 14%, #fff); color: var(--rdv3-accent); font-size: .75rem; font-weight: 950; }
.rdv3-rooms { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.rdv3-room { --room-accent: var(--rdv3-blue); position: relative; min-height: 116px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--room-accent) 18%, transparent); background: linear-gradient(135deg, color-mix(in srgb, var(--room-accent) 8%, #fff), rgba(255,252,247,.76)); padding: 16px; display: grid; align-content: space-between; gap: 12px; box-shadow: inset 0 0 0 1px rgba(255,255,255,.62); }
.rdv3-room::before { content: ""; position: absolute; left: 0; top: 16px; bottom: 16px; width: 4px; border-radius: 0 8px 8px 0; background: var(--room-accent); opacity: .76; }
.rdv3-room:nth-child(4n+1) { --room-accent: var(--rdv3-blue); }
.rdv3-room:nth-child(4n+2) { --room-accent: var(--rdv3-gold); }
.rdv3-room:nth-child(4n+3) { --room-accent: var(--rdv3-violet); }
.rdv3-room:nth-child(4n+4) { --room-accent: var(--rdv3-green); }
.rdv3-room.is-courtesy { --room-accent: var(--rdv3-gold); background: color-mix(in srgb, var(--rdv3-gold) 14%, #fff); }
.rdv3-room-top { display: flex; justify-content: space-between; gap: 12px; }
.rdv3-room-number { font-family: "Cormorant Garamond", Georgia, serif; color: color-mix(in srgb, var(--room-accent) 82%, var(--rdv3-primary)); font-size: 1.16rem; font-weight: 800; }
.rdv3-room-type { color: #69738a; font-size: .82rem; font-weight: 800; margin-top: 2px; }
.rdv3-room-price { color: color-mix(in srgb, var(--room-accent) 80%, var(--rdv3-primary)); font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }
.rdv3-tags { display: flex; flex-wrap: wrap; gap: 7px; }
.rdv3-tag { min-height: 23px; display: inline-flex; align-items: center; gap: 6px; padding: 0 9px; border-radius: 7px; background: rgba(255,255,255,.86); border: 1px solid color-mix(in srgb, var(--room-accent, var(--rdv3-accent)) 14%, var(--rdv3-line)); color: #6f7a91; font-size: .68rem; font-weight: 900; }
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
.rdv3-guest-name { margin: 0; color: var(--rdv3-primary); font-family: "Cormorant Garamond", Georgia, serif; font-size: 1.55rem; font-weight: 800; line-height: 1; }
.rdv3-guest-sub { margin-top: 5px; color: #7e879b; font-size: .83rem; font-weight: 800; }
.rdv3-info-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.rdv3-info { --info-accent: var(--rdv3-blue); min-height: 62px; padding: 12px 13px; display: flex; align-items: center; gap: 12px; border: 1px solid color-mix(in srgb, var(--info-accent) 14%, transparent); border-radius: 12px; background: linear-gradient(135deg, color-mix(in srgb, var(--info-accent) 6%, #fff), rgba(255,252,247,.75)); }
.rdv3-info:nth-child(2) { --info-accent: var(--rdv3-cyan); }
.rdv3-info:nth-child(3) { --info-accent: var(--rdv3-violet); }
.rdv3-info:nth-child(4) { --info-accent: var(--rdv3-green); }
.rdv3-info i { width: 28px; height: 28px; border-radius: 9px; display: grid; place-items: center; color: var(--info-accent); background: #fff; border: 1px solid color-mix(in srgb, var(--info-accent) 15%, var(--rdv3-line)); }
.rdv3-info small { display: block; color: #a3acbd; font-size: .64rem; font-weight: 950; text-transform: uppercase; }
.rdv3-info b { display: block; margin-top: 2px; color: #3b4660; font-size: .82rem; font-weight: 950; overflow-wrap: anywhere; }
.rdv3-subhead { margin: 17px 0 9px; display: flex; align-items: center; justify-content: space-between; gap: 12px; color: var(--rdv3-primary); font-size: .84rem; font-weight: 950; }
.rdv3-subhead span { display: inline-flex; align-items: center; gap: 8px; }
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
    font-weight: 850;
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
.rdv3-vehicle-title { color: var(--rdv3-primary); font-size: .84rem; font-weight: 950; }
.rdv3-vehicle-sub { color: #8790a4; font-size: .72rem; font-weight: 800; }
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
.rdv3-action-icon.is-gold { color: var(--rdv3-accent); background: color-mix(in srgb, var(--rdv3-accent) 15%, #fff); }
.rdv3-action-icon.is-blue { color: #3b82f6; background: #eaf2ff; }
.rdv3-action-icon.is-violet { color: #6252d8; background: #eeebff; }
.rdv3-action-icon.is-gray { color: #6d7689; background: #f4f5f7; }
.rdv3-action i.fa-chevron-right { color: #a3acbd; font-size: .72rem; }
.rdv3-timeline { position: relative; display: grid; gap: 15px; padding-left: 12px; }
.rdv3-timeline::before { content: ""; position: absolute; left: 5px; top: 7px; bottom: 7px; width: 2px; background: color-mix(in srgb, var(--rdv3-accent) 18%, transparent); }
.rdv3-step { position: relative; padding-left: 18px; }
.rdv3-step::before { content: ""; position: absolute; left: -12px; top: 4px; width: 14px; height: 14px; border-radius: 999px; background: #fff; border: 3px solid var(--step-color, #d4d8df); }
.rdv3-step.is-blue { --step-color: #3b82f6; }
.rdv3-step.is-green { --step-color: #24b777; }
.rdv3-step.is-gold { --step-color: var(--rdv3-accent); }
.rdv3-step-title { color: var(--rdv3-primary); font-size: .82rem; font-weight: 950; }
.rdv3-step-meta { margin-top: 2px; color: #8b94a8; font-size: .72rem; font-weight: 800; line-height: 1.3; }
.rdv3-payment-total { text-align: center; border-radius: 14px; border: 1px solid rgba(45, 189, 121, .26); background: radial-gradient(circle at 15% 0, rgba(45,189,121,.18), transparent 12rem), rgba(45, 189, 121, .13); padding: 18px; margin-bottom: 14px; }
.rdv3-payment-total small { display: block; color: #27a96e; font-size: .67rem; font-weight: 950; text-transform: uppercase; }
.rdv3-payment-total b { display: block; margin-top: 7px; color: var(--rdv3-primary); font-family: "Cormorant Garamond", Georgia, serif; font-size: 1.65rem; }
.rdv3-payment-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 0; color: var(--rdv3-primary); }
.rdv3-payment-row + .rdv3-payment-row { border-top: 1px solid var(--rdv3-line); }
.rdv3-payment-method { display: flex; align-items: center; gap: 11px; min-width: 0; }
.rdv3-payment-method i { width: 31px; height: 31px; border-radius: 9px; display: grid; place-items: center; color: #2dbd79; background: #e7f8ef; }
.rdv3-payment-method b { display: block; font-size: .82rem; font-weight: 950; }
.rdv3-payment-method small { display: block; color: #8790a4; font-size: .7rem; font-weight: 800; }
.rdv3-payment-amount { font-size: .84rem; font-weight: 950; white-space: nowrap; }
.rdv3-note-composer { display: grid; gap: 10px; margin-bottom: 14px; padding: 12px; border-radius: 14px; border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 20%, transparent); background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 7%, #fff), rgba(255,255,255,.74)); }
.rdv3-note-composer label { color: var(--rdv3-primary); font-size: .78rem; font-weight: 950; }
.rdv3-note-input { width: 100%; min-height: 86px; resize: vertical; padding: 11px 12px; border: 1px solid color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 22%, var(--rdv3-line)); border-radius: 12px; background: #fff; color: var(--rdv3-primary); font: inherit; font-size: .82rem; line-height: 1.45; }
.rdv3-note-input::placeholder { color: #98a2b3; }
.rdv3-note-input:focus { outline: none; border-color: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 54%, var(--rdv3-line)); box-shadow: 0 0 0 3px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 16%, transparent); }
.rdv3-note-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.rdv3-note-hint { color: #8b94a8; font-size: .72rem; font-weight: 800; }
.rdv3-note-submit { min-height: 34px; border: 0; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 0 12px; background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 82%, #22324a); color: #fff; font-size: .76rem; font-weight: 950; cursor: pointer; }
.rdv3-note-submit:hover { filter: brightness(.96); }
.rdv3-note-submit:disabled { opacity: .64; cursor: wait; }
.rdv3-note-list { display: grid; gap: 10px; max-height: 280px; overflow: auto; }
.rdv3-note { border-radius: 12px; border: 1px solid color-mix(in srgb, var(--rdv3-accent) 22%, transparent); background: color-mix(in srgb, var(--rdv3-accent) 12%, #fff); padding: 13px; color: #7a5f2b; font-size: .8rem; font-weight: 800; line-height: 1.45; }
.rdv3-note small { display: block; margin-bottom: 6px; color: color-mix(in srgb, var(--rdv3-accent) 84%, #6e5527); font-weight: 950; }
.rdv3-count-badge { min-width: 28px; height: 28px; padding: 0 8px; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 90%, #22324a); color: #fff; font-size: .72rem; font-weight: 950; line-height: 1; box-shadow: 0 8px 16px -10px color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 74%, transparent); }
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
.rdv3-doc-meta { margin-top: 4px; color: #8790a4; font-size: .72rem; font-weight: 800; }
.rdv3-doc-right { display: flex; align-items: center; gap: 10px; flex: 0 0 auto; }
.rdv3-doc-state { border-radius: 999px; min-height: 25px; padding: 0 10px; display: inline-flex; align-items: center; color: #27a96e; background: #e7f8ef; font-size: .7rem; font-weight: 950; }
.rdv3-doc-state.is-draft { color: var(--rdv3-accent); background: color-mix(in srgb, var(--rdv3-accent) 14%, #fff); }
.rdv3-iconbtn { width: 32px; height: 32px; border-radius: 9px; border: 1px solid var(--rdv3-line); background: #fff; display: grid; place-items: center; color: #8790a4; }
.rdv3-empty { min-height: 86px; padding: 22px; text-align: center; color: #7f8ba0; font-size: .86rem; font-weight: 850; border: 1px dashed color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 22%, transparent); border-radius: 14px; background: linear-gradient(135deg, color-mix(in srgb, var(--rdv3-card-accent, var(--rdv3-accent)) 7%, #fff), rgba(255,255,255,.58)); display: grid; place-items: center; }
.rdv3-footer-line { display: flex; justify-content: space-between; gap: 12px; padding-top: 12px; color: #758096; font-size: .78rem; font-weight: 850; }
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
    .rdv3-nights { min-height: 62px; border-left: 0; border-right: 0; border-top: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); border-bottom: 1px solid color-mix(in srgb, var(--rdv3-accent) 16%, transparent); }
    .rdv3-date:last-child { text-align: left; }
    .rdv3-total, .rdv3-doc-row { align-items: flex-start; flex-direction: column; }
    .rdv3-doc-right { width: 100%; justify-content: space-between; }
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
</style>

<div class="rdv3 detail-view">
    <div class="rdv3-shell">
        <main class="rdv3-main">
            <div class="rdv3-page">
                <div class="rdv3-topbar">
                    <a class="rdv3-back" href="<?= url('reservaciones') ?>" onclick="medisoftVolverAnterior(event)" aria-label="Volver a reservaciones"><i class="fas fa-arrow-left"></i></a>
                    <div class="rdv3-crumbs">Reservaciones / <strong>Reservacion #<?= $rdReservationId ?></strong></div>
                </div>

                <section class="rdv3-hero" aria-labelledby="rdv3-title">
                    <div>
                        <div class="rdv3-titleline">
                            <h1 id="rdv3-title">Reservacion #<?= $rdReservationId ?></h1>
                            <span class="rdv3-status <?= $rdEstadoClass ?>"><?= $rdSafe($rdEstadoLabel, 'Pendiente') ?></span>
                        </div>
                        <div class="rdv3-meta">
                            <span><i class="fas fa-bed"></i><?= count($rdRooms) ?> habitacion<?= count($rdRooms) === 1 ? '' : 'es' ?></span>
                            <span><i class="fas fa-user"></i>Registro: <?= $rdSafe($_SESSION['usuario_nombre'] ?? ($reservacion['usuario_nombre'] ?? 'Recepcion')) ?></span>
                            <span><i class="far fa-clock"></i><?= $rdSafe($rdDateTime($rdCreatedAt), '-') ?></span>
                        </div>
                    </div>
                    <div class="rdv3-hero-actions">
                        <?php if ($rdCheckinMode === 'normal'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-primary" onclick="abrirModalCheckIn(<?= $rdReservationId ?>, <?= $rdTotal ?>)"><i class="fas fa-right-to-bracket"></i>Check-in</button>
                        <?php elseif ($rdCheckinMode === 'late' || $rdCheckinMode === 'express'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-primary" onclick='abrirModalCheckInTardio(<?= $rdReservationId ?>, <?= $rdHuespedNombreJsonAttr ?>, <?= $rdHabitacionesTextoJsonAttr ?>, <?= $rdFechaEntradaFormatoJsonAttr ?>, <?= $rdFechaSalidaFormatoJsonAttr ?>, <?= $rdTotal ?>, "<?= $rdCheckinJsMode ?>", <?= (int)$rdCheckinDays ?>)'><i class="fas fa-right-to-bracket"></i>Check-in</button>
                        <?php elseif ($rdEstadoKey === 'checked_in'): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-primary" onclick="abrirModalCheckOut()"><i class="fas fa-right-from-bracket"></i>Check-out</button>
                        <?php endif; ?>
                        <?php if (in_array($rdEstadoKey, ['confirmada', 'checked_in'], true)): ?>
                            <button type="button" class="rdv3-btn rdv3-btn-ghost" onclick="abrirModalModificarDias()"><i class="far fa-calendar"></i>Modificar dias</button>
                            <button type="button" class="rdv3-btn rdv3-btn-danger" onclick="mostrarFormularioCancelacion()"><i class="fas fa-xmark"></i>Cancelar</button>
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
                                <div class="rdv3-total">
                                    <div>
                                        <span class="rdv3-label rdv3-label--green">Precio total</span>
                                        <div class="rdv3-amount"><?= $rdMoney($rdTotal) ?></div>
                                    </div>
                                    <span class="rdv3-pill"><i class="fas fa-check"></i><?= $rdSafe($rdPaymentLabel, 'Pago pendiente') ?></span>
                                </div>
                                <?php if (!empty($reservacion['notas'])): ?>
                                    <div class="rdv3-res-note"><i class="far fa-note-sticky"></i><?= $rdSafe($reservacion['notas']) ?></div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-card--rooms">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon rdv3-icon--violet"><i class="fas fa-bed"></i></span>
                                    <h2 class="rdv3-card-title">Habitaciones reservadas</h2>
                                </div>
                                <?php if ($rdCortesias > 0): ?>
                                    <span class="rdv3-badge"><i class="fas fa-gift"></i><?= $rdCortesias ?> cortesia<?= $rdCortesias === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                            </header>
                            <div class="rdv3-card-body">
                                <?php if (empty($rdRooms)): ?>
                                    <div class="rdv3-empty">No hay habitaciones asociadas a esta reservacion.</div>
                                <?php else: ?>
                                    <div class="rdv3-rooms">
                                        <?php foreach ($rdRooms as $room): ?>
                                            <?php
                                            $roomIsCourtesy = !empty($room['es_cortesia']) || !empty($room['cortesia']);
                                            $roomType = $room['tipo_nombre'] ?? $room['tipo'] ?? $room['nombre_tipo'] ?? 'Habitacion';
                                            $roomPeople = $room['personas'] ?? $room['capacidad'] ?? null;
                                            $roomPrice = $room['precio'] ?? $room['precio_total'] ?? $room['precio_noche'] ?? null;
                                            ?>
                                            <article class="rdv3-room <?= $roomIsCourtesy ? 'is-courtesy' : '' ?>">
                                                <div class="rdv3-room-top">
                                                    <div>
                                                        <div class="rdv3-room-number"><?= $rdSafe($room['numero'] ?? 'S/N') ?></div>
                                                        <div class="rdv3-room-type"><?= $rdSafe($roomType) ?><?= $roomPeople ? ' - ' . (int)$roomPeople . ' personas' : '' ?></div>
                                                    </div>
                                                    <?php if ($roomIsCourtesy): ?>
                                                        <span class="rdv3-badge"><i class="fas fa-gift"></i>Cortesia</span>
                                                    <?php elseif ($roomPrice !== null): ?>
                                                        <div class="rdv3-room-price"><?= $rdMoney($roomPrice) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="rdv3-tags">
                                                    <?php if (!empty($room['piso'])): ?><span class="rdv3-tag"><i class="fas fa-layer-group"></i>Piso <?= $rdSafe($room['piso']) ?></span><?php endif; ?>
                                                    <?php if (!empty($room['categoria'])): ?><span class="rdv3-tag"><i class="fas fa-tag"></i><?= $rdSafe($room['categoria']) ?></span><?php endif; ?>
                                                    <?php if (!$roomIsCourtesy && $roomPrice === null): ?><span class="rdv3-tag"><i class="fas fa-bed"></i>Reservada</span><?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-card--guest">
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
                                        <h2 class="rdv3-guest-name"><?= $rdSafe($rdGuestName, 'Huesped') ?></h2>
                                        <div class="rdv3-guest-sub"><?= $rdSafe($huesped['tipo_cliente'] ?? 'Cliente') ?><?= $rdGuestOrigin !== '' ? ' - ' . $rdSafe($rdGuestOrigin) : '' ?></div>
                                    </div>
                                </div>

                                <div class="rdv3-info-grid">
                                    <div class="rdv3-info"><i class="fas fa-phone"></i><div><small>Telefono</small><b><?= $rdSafe($rdGuestPhone) ?></b></div></div>
                                    <div class="rdv3-info"><i class="far fa-envelope"></i><div><small>Email</small><b><?= $rdSafe($rdGuestEmail) ?></b></div></div>
                                    <div class="rdv3-info"><i class="far fa-address-card"></i><div><small>Identificacion</small><b><?= $rdSafe($rdGuestId) ?></b></div></div>
                                    <div class="rdv3-info"><i class="fas fa-location-dot"></i><div><small>Procedencia</small><b><?= $rdSafe($rdGuestOrigin) ?></b></div></div>
                                </div>

                                <div class="rdv3-subhead">
                                    <span><i class="fas fa-car-side rdv3-car-accent"></i>Vehiculos registrados</span>
                                    <button type="button" class="rdv3-link" onclick="abrirModalAgregarVehiculo()"><i class="fas fa-pen"></i> Agregar</button>
                                </div>
                                <div id="listaVehiculos" class="rdv3-vehicle-list">
                                    <?php if (empty($rdVehicles)): ?>
                                        <div class="rdv3-empty">Cargando vehiculos...</div>
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
                            </div>
                        </section>

                        <section class="rdv3-card rdv3-card--docs">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading">
                                    <span class="rdv3-icon"><i class="far fa-folder"></i></span>
                                    <h2 class="rdv3-card-title">Documentos vinculados</h2>
                                </div>
                                <div class="rdv3-doc-actions">
                                    <?php if ($rdDocEntityTipo !== '' && $rdDocEntityId > 0): ?>
                                        <a class="rdv3-doc-btn is-soft" href="<?= url('documentos/entidad/' . rawurlencode($rdDocEntityTipo) . '/' . $rdDocEntityId) ?>"><i class="fas fa-lock"></i>Centro Documental</a>
                                        <a class="rdv3-doc-btn is-gold" href="<?= url('documentos/subir' . $rdDocEntityQuery) ?>"><i class="fas fa-link"></i>Vincular documento</a>
                                    <?php endif; ?>
                                </div>
                            </header>
                            <div class="rdv3-card-body">
                                <?php if (empty($rdDocuments)): ?>
                                    <div class="rdv3-empty">Esta reservacion aun no tiene documentos vinculados.</div>
                                <?php else: ?>
                                    <div class="rdv3-doc-list">
                                        <?php foreach ($rdDocuments as $documento): ?>
                                            <?php
                                            $documentoId = (int)($documento['id'] ?? 0);
                                            $docEstado = trim((string)($documento['estado'] ?? '')) ?: 'activo';
                                            $docName = trim((string)(($documento['titulo'] ?? '') ?: ($documento['nombre_original'] ?? 'Documento')));
                                            [$docIconLabel, $docIconClass] = $rdDocIcon($documento);
                                            ?>
                                            <article class="rdv3-doc-row">
                                                <div class="rdv3-doc-main">
                                                    <span class="rdv3-docicon <?= $docIconClass ?>"><?= $rdSafe($docIconLabel) ?></span>
                                                    <div>
                                                        <div class="rdv3-doc-title"><?= $rdSafe($docName, 'Documento') ?></div>
                                                        <div class="rdv3-doc-meta"><?= $rdBytes($documento['size_bytes'] ?? 0) ?> &middot; <?= $rdSafe($rdDateTime($documento['created_at'] ?? null)) ?> &middot; <?= $rdSafe($documento['subido_por_nombre'] ?? 'Recepcion') ?></div>
                                                    </div>
                                                </div>
                                                <div class="rdv3-doc-right">
                                                    <span class="rdv3-doc-state <?= $docEstado === 'borrador' ? 'is-draft' : '' ?>"><?= $rdSafe($docEstado, 'activo') ?></span>
                                                    <?php if ($documentoId > 0): ?>
                                                        <a class="rdv3-iconbtn" href="<?= url('documentos/' . $documentoId) ?>" aria-label="Ver documento"><i class="far fa-eye"></i></a>
                                                        <a class="rdv3-iconbtn" href="<?= url('documentos/' . $documentoId . '/descargar') ?>" aria-label="Descargar documento"><i class="fas fa-print"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="rdv3-footer-line">
                                        <span><?= count($rdDocuments) ?> documento<?= count($rdDocuments) === 1 ? '' : 's' ?> &middot; <?= $rdBytes($rdDocTotalBytes) ?> en total</span>
                                        <?php if ($rdDocEntityTipo !== '' && $rdDocEntityId > 0): ?>
                                            <a class="rdv3-link" href="<?= url('documentos/entidad/' . rawurlencode($rdDocEntityTipo) . '/' . $rdDocEntityId) ?>">Ver todos en Centro Documental &rarr;</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>

                    <aside class="rdv3-right" aria-label="Panel lateral de reservacion">
                        <section class="rdv3-card rdv3-side-card rdv3-side-card--actions">
                            <header class="rdv3-card-header">
                                <div class="rdv3-heading"><span class="rdv3-icon"><i class="fas fa-grip"></i></span><h2 class="rdv3-card-title">Acciones rapidas</h2></div>
                            </header>
                            <div class="rdv3-card-body rdv3-actions">
                                <?php if ($rdCheckinMode === 'normal'): ?>
                                    <button type="button" class="rdv3-action" onclick="abrirModalCheckIn(<?= $rdReservationId ?>, <?= $rdTotal ?>)"><span class="rdv3-action-left"><span class="rdv3-action-icon is-green"><i class="fas fa-right-to-bracket"></i></span>Registrar check-in</span><i class="fas fa-chevron-right"></i></button>
                                <?php elseif ($rdCheckinMode === 'late' || $rdCheckinMode === 'express'): ?>
                                    <button type="button" class="rdv3-action" onclick='abrirModalCheckInTardio(<?= $rdReservationId ?>, <?= $rdHuespedNombreJsonAttr ?>, <?= $rdHabitacionesTextoJsonAttr ?>, <?= $rdFechaEntradaFormatoJsonAttr ?>, <?= $rdFechaSalidaFormatoJsonAttr ?>, <?= $rdTotal ?>, "<?= $rdCheckinJsMode ?>", <?= (int)$rdCheckinDays ?>)'><span class="rdv3-action-left"><span class="rdv3-action-icon is-green"><i class="fas fa-right-to-bracket"></i></span>Registrar check-in</span><i class="fas fa-chevron-right"></i></button>
                                <?php elseif ($rdEstadoKey === 'checked_in'): ?>
                                    <button type="button" class="rdv3-action" onclick="abrirModalCheckOut()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-green"><i class="fas fa-right-from-bracket"></i></span>Registrar check-out</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <?php if ($rdEstadoKey === 'checked_in'): ?>
                                    <button type="button" class="rdv3-action" onclick="abrirModalCambiarPago()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-gold"><i class="fas fa-right-left"></i></span>Cambiar metodo de pago</span><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                                <button type="button" class="rdv3-action" onclick="abrirModalCotizacion()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-blue"><i class="fas fa-clipboard-list"></i></span>Generar cotizacion</span><i class="fas fa-chevron-right"></i></button>
                                <button type="button" class="rdv3-action" onclick="abrirModalModificarDias()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-violet"><i class="far fa-calendar"></i></span>Modificar dias</span><i class="fas fa-chevron-right"></i></button>
                                <?php if (!empty($reservacion['metodo_pago'])): ?>
                                    <button type="button" class="rdv3-action" onclick="imprimirTicketTermico()"><span class="rdv3-action-left"><span class="rdv3-action-icon is-gray"><i class="fas fa-print"></i></span>Imprimir ticket termico</span><i class="fas fa-chevron-right"></i></button>
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
                                        <?php $paymentMethod = $payment['metodo_pago'] ?? $payment['metodo'] ?? $rdMetodoPagoLabel; ?>
                                        <div class="rdv3-payment-row">
                                            <div class="rdv3-payment-method"><i class="far fa-credit-card"></i><div><b><?= $rdSafe(ucfirst(str_replace('_', ' ', (string)$paymentMethod))) ?></b><small><?= $rdSafe($payment['referencia'] ?? $payment['notas'] ?? 'Recibido en caja') ?></small></div></div>
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
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h3 class="font-bold text-lg">
                <i class="fas fa-sign-out-alt mr-2"></i>
                Check-out de Habitaciones
            </h3>
            <button onclick="cerrarModalCheckOut()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('reservaciones/check-out-parcial/' . $reservacion['id']) ?>" id="formCheckOut">
            <?= csrf_field() ?>

            <div class="modal-body">
                <p class="text-sm text-gray-600 mb-4">
                    Selecciona las habitaciones que deseas liberar:
                </p>

                <!-- Lista de habitaciones con checkboxes -->
                <div class="space-y-2 mb-4">
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
                                <span class="text-xs text-gray-500 ml-2">(<?= $hab['tipo'] ?>)</span>
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

                <!-- Mensaje de advertencia -->
                <div id="mensajeSeleccion" class="hidden p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    <span id="textoMensaje"></span>
                </div>
            </div>

            <div class="flex gap-3 p-4 bg-gray-50 border-t">
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
<script>
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
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Entregar llave al huésped?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-llave") ?>';

                // CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= csrf_token() ?>';
                form.appendChild(csrfInput);

                // Habitación ID
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitacion_id';
                habInput.value = habitacionId;
                form.appendChild(habInput);

                // Reservación ID
                const resInput = document.createElement('input');
                resInput.type = 'hidden';
                resInput.name = 'reservacion_id';
                resInput.value = reservacionId;
                form.appendChild(resInput);

                // Tipo entrega
                const tipoInput = document.createElement('input');
                tipoInput.type = 'hidden';
                tipoInput.name = 'tipo_entrega';
                tipoInput.value = 'usuario_actual';
                form.appendChild(tipoInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        if (confirm('¿Entregar llave al huésped?')) {
            window.location.href = '<?= url("reservaciones/entregar-llave-rapida/") ?>' + habitacionId + '/' + reservacionId;
        }
    }
}

// Manejadores de formularios
document.getElementById('formEntregarLlave').addEventListener('submit', function(e) {
    e.preventDefault();
    this.action = '<?= url("reservaciones/entregar-llave") ?>';
    this.submit();
});

document.getElementById('formRecibirLlave').addEventListener('submit', function(e) {
    e.preventDefault();
    this.action = '<?= url("reservaciones/recibir-llave") ?>';
    this.submit();
});
</script>

<!-- Modal de Check-in con Pagos Mixtos -->

<div id="modalCheckIn" class="modal-overlay rv-checkin-modal" style="display: none;">
    <div class="modal-content rv-checkin-shell" style="width: 380px;">
        <div class="modal-header rv-checkin-hero" style="padding: 1rem; border-bottom: 2px solid #F3F4F6; background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%);">
            <h3 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-sign-in-alt" style="color: #10B981; margin-right: 0.5rem;"></i>
                Confirmar Check-in
            </h3>
            <button onclick="cerrarModalCheckIn()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <div class="modal-body rv-checkin-body" style="padding: 1rem;">
        <form id="formCheckInModal" method="POST" action="" class="rv-checkin-form">
            <?= csrf_field() ?>

            <div class="form-group rv-arrival-field" style="margin-bottom: 1rem;">
                <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                    Hora de llegada
                </label>
                <input type="time" name="hora_entrada" class="form-input" value="<?= date('H:i') ?>" required
                       style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem;">
            </div>

            <div class="form-group rv-total-field" style="margin-bottom: 1rem;">
                <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                    Total a Cobrar
                </label>
                <div style="padding: 0.75rem; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: 0.5rem; text-align: center;">
                    <span id="totalACobrar" style="font-weight: 800; font-size: 1.5rem; color: #92400E;">$0.00</span>
                </div>
            </div>

            <!-- Sección de Métodos de Pago -->
            <div class="form-group rv-payment-section">
                <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                    <i class="fas fa-wallet" style="color: #9333EA; margin-right: 0.375rem;"></i>
                    Métodos de Pago
                </h4>

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
                        <i class="fas fa-exchange-alt"></i>
                        Transferencia exacta
                    </button>
                    <button type="button" class="rv-money-shortcut is-split" onclick="dividirPagoRapido()">
                        <i class="fas fa-code-branch"></i>
                        Mitad efectivo/tarjeta
                    </button>
                </div>

                <div id="metodosPagoContainer" class="rv-pay-methods" style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <!-- Efectivo -->
                    <div class="metodo-pago-item rv-pay-option rv-pay-cash" style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 0.5rem; padding: 0.75rem;">
                        <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #065F46; cursor: pointer;">
                            <input type="checkbox"
                                id="check_efectivo"
                                onchange="toggleMetodoPago('efectivo')"
                                style="margin-right: 0.5rem;">
                            <i class="fas fa-money-bill-wave" style="color: #10B981; margin-right: 0.5rem;"></i>
                            Efectivo
                        </label>
                        <div id="panel_efectivo" class="hidden" style="margin-top: 0.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #065F46;">Total a cobrar</label>
                                        <input type="number"
                                        name="monto_efectivo"
                                        id="monto_efectivo"
                                        data-money-format="true"
                                        step="0.01"
                                        min="0"
                                        readonly
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background-color: #F0FDF4;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #065F46;">Monto recibido</label>
                                    <input type="number"
                                        name="recibido_efectivo"
                                        id="recibido_efectivo"
                                        data-money-format="true"
                                        step="0.01"
                                        min="0"
                                        oninput="calcularCambio()"
                                        onchange="calcularCambio()"
                                        onkeyup="calcularCambio()"
                                        placeholder="0.00"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background: white;">
                                    <button type="button" class="rv-money-mini" onclick="marcarEfectivoExacto()">
                                        Recibi exacto
                                    </button>
                                </div>
                            </div>
                            <div style="background-color: #D1FAE5; border-radius: 0.375rem; padding: 0.375rem 0.5rem; margin-top: 0.375rem; font-size: 0.75rem;">
                                Cambio: <span id="cambio_efectivo" style="font-weight: 700; color: #065F46;">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta -->
                    <div class="metodo-pago-item rv-pay-option rv-pay-card" style="background: #EFF6FF; border: 2px solid #BFDBFE; border-radius: 0.5rem; padding: 0.75rem;">
                        <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #1E40AF; cursor: pointer;">
                            <input type="checkbox"
                                id="check_tarjeta"
                                onchange="toggleMetodoPago('tarjeta')"
                                style="margin-right: 0.5rem;">
                            <i class="fas fa-credit-card" style="color: #3B82F6; margin-right: 0.5rem;"></i>
                            Tarjeta
                        </label>
                        <div id="panel_tarjeta" class="hidden" style="margin-top: 0.5rem;">
    <!-- Tipo de tarjeta: Crédito / Débito -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
        <label class="rv-radio-chip" style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
                       background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem;
                       cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF; transition: all 0.2s;"
               id="label_credito"
               onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_debito').style.borderColor='#BFDBFE'; document.getElementById('label_debito').style.background='white';">
            <input type="radio" name="tipo_tarjeta" value="credito" style="accent-color: #3B82F6;">
            <i class="fas fa-credit-card" style="font-size: 0.625rem;"></i> Crédito
        </label>
        <label class="rv-radio-chip" style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
                       background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem;
                       cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF; transition: all 0.2s;"
               id="label_debito"
               onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_credito').style.borderColor='#BFDBFE'; document.getElementById('label_credito').style.background='white';">
            <input type="radio" name="tipo_tarjeta" value="debito" style="accent-color: #3B82F6;">
            <i class="fas fa-money-check-alt" style="font-size: 0.625rem;"></i> Débito
        </label>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
        <div>
            <label style="font-size: 0.75rem; color: #1E40AF;">Monto</label>
                                    <input type="number"
                                        name="monto_tarjeta"
                                        id="monto_tarjeta"
                                        data-money-format="true"
                                        step="0.01"
                                        min="0"
                                        oninput="calcularTotales()"
                                        onchange="calcularTotales()"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #1E40AF;">Referencia</label>
                                    <input type="text"
                                        name="referencia_tarjeta"
                                        placeholder="Últimos 4 dígitos"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transferencia -->
                    <div class="metodo-pago-item rv-pay-option rv-pay-transfer" style="background: #F5F3FF; border: 2px solid #DDD6FE; border-radius: 0.5rem; padding: 0.75rem;">
                        <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #5B21B6; cursor: pointer;">
                            <input type="checkbox"
                                id="check_transferencia"
                                onchange="toggleMetodoPago('transferencia')"
                                style="margin-right: 0.5rem;">
                            <i class="fas fa-exchange-alt" style="color: #9333EA; margin-right: 0.5rem;"></i>
                            Transferencia
                        </label>
                        <div id="panel_transferencia" class="hidden" style="margin-top: 0.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #5B21B6;">Monto</label>
                                    <input type="number"
                                        name="monto_transferencia"
                                        id="monto_transferencia"
                                        data-money-format="true"
                                        step="0.01"
                                        min="0"
                                        oninput="calcularTotales()"
                                        onchange="calcularTotales()"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #5B21B6;">Referencia</label>
                                    <input type="text"
                                        name="referencia_transferencia"
                                        placeholder="Número de operación"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ========== SECCIÓN DE FACTURA ========== -->
<div class="rv-invoice-section" style="margin-top: 1rem; margin-bottom: 0.5rem;">
    <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
        <i class="fas fa-file-invoice" style="color: #2563EB; margin-right: 0.375rem;"></i>
        ¿El cliente requiere factura?
        <span style="color: #EF4444; font-size: 0.75rem;">*</span>
    </h4>

    <div id="facturaContainer" class="rv-invoice-grid" style="display: flex; gap: 0.5rem;">
        <!-- Opción SÍ -->
        <label class="rv-invoice-choice" style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                       background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                       cursor: pointer; transition: all 0.2s;"
               id="label_factura_si"
               onmouseover="this.style.borderColor='#3B82F6'"
               onmouseout="if(!document.getElementById('factura_si').checked) this.style.borderColor='#E5E7EB'">
            <input type="radio" name="requiere_factura" id="factura_si" value="si"
                   onchange="seleccionarFactura('si')"
                   style="accent-color: #2563EB;">
            <div>
                <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">Sí</span>
                <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Se registrará para facturación</p>
            </div>
        </label>

        <!-- Opción NO -->
        <label class="rv-invoice-choice" style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                       background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                       cursor: pointer; transition: all 0.2s;"
               id="label_factura_no"
               onmouseover="this.style.borderColor='#6B7280'"
               onmouseout="if(!document.getElementById('factura_no').checked) this.style.borderColor='#E5E7EB'">
            <input type="radio" name="requiere_factura" id="factura_no" value="no"
                   onchange="seleccionarFactura('no')"
                   style="accent-color: #6B7280;">
            <div>
                <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">No</span>
                <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Sin factura</p>
            </div>
        </label>
    </div>

    <!-- Mensaje cuando no se ha seleccionado -->
    <div id="facturaValidacion" class="rv-checkin-message hidden"
         style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #FEF2F2;
                border-radius: 0.375rem; border: 1px solid #FECACA;">
        <p style="font-size: 0.75rem; color: #DC2626; margin: 0;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i>
            Debe indicar si el cliente requiere factura
        </p>
    </div>

    <!-- Info: se registrará para facturación interna -->
    <div id="facturaInfoInterna" class="rv-checkin-note hidden"
         style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #EFF6FF;
                border-radius: 0.375rem; border: 1px solid #BFDBFE;">
        <p style="font-size: 0.7rem; color: #1E40AF; margin: 0;">
            <i class="fas fa-info-circle" style="margin-right: 0.25rem;"></i>
            El pago con tarjeta/transferencia se registrará en facturación para uso interno
        </p>
    </div>
</div>
<!-- ========== FIN SECCIÓN DE FACTURA ========== -->
            <!-- Resumen de Pago -->
            <div class="rv-checkin-summary" style="background: linear-gradient(135deg, #F9FAFB 0%, #F3F4F6 100%); border-radius: 0.5rem; padding: 0.75rem; margin: 1rem 0; border: 1px solid #E5E7EB;">
                <h5 style="font-weight: 700; font-size: 0.75rem; color: #374151; margin-bottom: 0.375rem;">Resumen de Pago</h5>
                <div style="font-size: 0.75rem;">
                    <div class="rv-summary-row" style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Total a cobrar:</span>
                        <span id="resumenTotal" style="font-weight: 700;">$0.00</span>
                    </div>
                    <div class="rv-summary-row is-paid" style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Total pagado:</span>
                        <span id="resumenPagado" style="font-weight: 700; color: #10B981;">$0.00</span>
                    </div>
                    <div id="divRestante" class="rv-summary-row is-due" style="display: none; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Restante:</span>
                        <span id="resumenRestante" style="font-weight: 700; color: #EF4444;">$0.00</span>
                    </div>
                    <div id="divCambio" class="rv-summary-row is-change" style="display: none; justify-content: space-between; margin-bottom: 0.25rem;">
                        <span>Cambio total:</span>
                        <span id="resumenCambio" style="font-weight: 700; color: #3B82F6;">$0.00</span>
                    </div>
                </div>
            </div>

            <!-- Mensajes de validación -->
            <div id="mensajeValidacion" class="rv-checkin-message hidden" style="margin-bottom: 0.75rem; padding: 0.5rem; border-radius: 0.375rem; font-size: 0.75rem;"></div>

            <!-- Botones -->
            <div class="rv-checkin-actions" style="display: flex; gap: 0.5rem;">
                <button type="button" onclick="cerrarModalCheckIn()"
                        class="rv-btn-cancel"
                        style="flex: 1; padding: 0.625rem; border: 2px solid #E5E7EB; background: white; color: #374151; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    Cancelar
                </button>
                <button type="submit"
                        id="btnConfirmarCheckIn"
                        class="rv-btn-confirm"
                        style="flex: 1; padding: 0.625rem; background: linear-gradient(135deg, #10B981 0%, #059669 100%); color: white; border: none; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-check" style="margin-right: 0.25rem;"></i>
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>


</div>

<!-- Modal para cancelación -->

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
                    <span style="font-weight: 800; font-size: 1.125rem; color: #92400E;"><?= format_money($reservacion['precio_total'] ?? 0) ?></span>
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

<div id="modalCheckInTardio" class="modal-overlay rv-checkin-modal rv-tardio-modal" style="display: none;">
    <div class="modal-content rv-checkin-shell rv-tardio-shell" style="width: 420px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header rv-checkin-hero rv-tardio-hero" style="padding: 1rem; border-bottom: 2px solid #F3F4F6;">
            <h3 id="tituloModalTardio" style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-clock" style="color: #F59E0B; margin-right: 0.5rem;"></i>
                Check-in Tardío
            </h3>
            <button onclick="cerrarModalCheckInTardio()" class="rv-checkin-close" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Alerta de Advertencia -->
        <div id="alertaTardio" style="padding: 1rem; display: none;">
            <!-- Se llena dinámicamente -->
        </div>

        <div class="modal-body rv-checkin-body rv-tardio-body" style="padding: 1rem;">
            <form id="formCheckInTardio" method="POST" action="" class="rv-checkin-form rv-tardio-form">
                <?= csrf_field() ?>
                <input type="hidden" name="tipo_tardio" id="tipo_tardio" value="">

                <div class="rv-tardio-fields">
                <!-- Info Reservación -->
                <div class="rv-tardio-info" style="background: #F9FAFB; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.875rem;">
                        <div>
                            <span style="color: #6B7280;">Huésped:</span>
                            <span id="huesped_tardio" style="font-weight: 600; color: #1F2937;"></span>
                        </div>
                        <div>
                            <span style="color: #6B7280;">Habitaciones:</span>
                            <span id="habitaciones_tardio" style="font-weight: 600; color: #1F2937;"></span>
                        </div>
                        <div>
                            <span style="color: #6B7280;">Entrada:</span>
                            <span id="fecha_entrada_tardio" style="font-weight: 600; color: #1F2937;"></span>
                        </div>
                        <div>
                            <span style="color: #6B7280;">Salida:</span>
                            <span id="fecha_salida_tardio" style="font-weight: 600; color: #1F2937;"></span>
                        </div>
                    </div>
                </div>

                <!-- Hora de Entrada (solo para tardío normal) -->
                <div id="campo_hora_entrada" class="form-group rv-arrival-field" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Hora de Check-in
                    </label>
                    <input type="time" name="hora_entrada" class="form-input" value="<?= date('H:i') ?>"
                           style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem;">
                </div>

                <!-- Total a Cobrar -->
                <div class="form-group rv-total-field" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Total a Cobrar
                    </label>
                    <div style="padding: 0.75rem; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: 0.5rem; text-align: center;">
                        <span id="totalACobrarTardio" style="font-weight: 800; font-size: 1.5rem; color: #92400E;">$0.00</span>
                    </div>
                </div>

                <!-- Métodos de Pago -->
                <div class="form-group rv-payment-section rv-tardio-payment">
                    <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                        <i class="fas fa-wallet" style="color: #9333EA; margin-right: 0.375rem;"></i>
                        Métodos de Pago
                    </h4>
                    <p id="nota_pago_opcional" style="font-size: 0.75rem; color: #6B7280; margin-bottom: 0.5rem;">
                        💡 Opcional: Puede registrar el pago ahora o dejarlo pendiente.
                    </p>

                    <div class="rv-payment-shortcuts" aria-label="Atajos de pago express">
                        <button type="button" class="rv-money-shortcut is-cash" onclick="aplicarPagoRapidoTardio('efectivo')">
                            <i class="fas fa-money-bill-wave"></i>
                            Efectivo exacto
                        </button>
                        <button type="button" class="rv-money-shortcut is-card" onclick="aplicarPagoRapidoTardio('tarjeta')">
                            <i class="fas fa-credit-card"></i>
                            Tarjeta exacta
                        </button>
                        <button type="button" class="rv-money-shortcut is-transfer" onclick="aplicarPagoRapidoTardio('transferencia')">
                            <i class="fas fa-exchange-alt"></i>
                            Transferencia exacta
                        </button>
                        <button type="button" class="rv-money-shortcut is-split" onclick="dividirPagoRapidoTardio()">
                            <i class="fas fa-code-branch"></i>
                            Mitad efectivo/tarjeta
                        </button>
                    </div>

                    <div id="metodosPagoContainerTardio" class="rv-pay-methods" style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <!-- Sin Pago -->
                        <div class="metodo-pago-item rv-pay-option rv-pay-pending" style="background: #F3F4F6; border: 2px solid #D1D5DB; border-radius: 0.5rem; padding: 0.75rem;">
                            <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #4B5563; cursor: pointer;">
                                <input type="checkbox"
                                    id="check_sin_pago"
                                    onchange="toggleMetodoPagoTardio('sin_pago')"
                                    checked
                                    style="margin-right: 0.5rem;">
                                <i class="fas fa-clock" style="color: #6B7280; margin-right: 0.5rem;"></i>
                                Sin Pago (Pendiente)
                            </label>
                        </div>

                        <!-- Efectivo -->
                        <div class="metodo-pago-item rv-pay-option rv-pay-cash" style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 0.5rem; padding: 0.75rem;">
                            <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #065F46; cursor: pointer;">
                                <input type="checkbox"
                                    id="check_efectivo_tardio"
                                    onchange="toggleMetodoPagoTardio('efectivo')"
                                    style="margin-right: 0.5rem;">
                                <i class="fas fa-money-bill-wave" style="color: #10B981; margin-right: 0.5rem;"></i>
                                Efectivo
                            </label>
                            <div id="panel_efectivo_tardio" class="hidden" style="margin-top: 0.5rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                    <div>
                                        <label style="font-size: 0.75rem; color: #065F46;">Total a cobrar</label>
                                        <input type="number"
                                            name="monto_efectivo"
                                            id="monto_efectivo_tardio"
                                            data-money-format="true"
                                            step="0.01"
                                            min="0"
                                            readonly
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background-color: #F0FDF4;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.75rem; color: #065F46;">Monto recibido</label>
                                        <input type="number"
                                            name="recibido_efectivo"
                                            id="recibido_efectivo_tardio"
                                            data-money-format="true"
                                            step="0.01"
                                            min="0"
                                            oninput="calcularCambioTardio()"
                                            onchange="calcularCambioTardio()"
                                            onkeyup="calcularCambioTardio()"
                                            placeholder="0.00"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background: white;">
                                        <button type="button" class="rv-money-mini" onclick="marcarEfectivoExactoTardio()">
                                            Recibi exacto
                                        </button>
                                    </div>
                                </div>
                                <div style="background-color: #D1FAE5; border-radius: 0.375rem; padding: 0.375rem 0.5rem; margin-top: 0.375rem; font-size: 0.75rem;">
                                    Cambio: <span id="cambio_efectivo_tardio" style="font-weight: 700; color: #065F46;">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta -->
                        <div class="metodo-pago-item rv-pay-option rv-pay-card" style="background: #EFF6FF; border: 2px solid #BFDBFE; border-radius: 0.5rem; padding: 0.75rem;">
                            <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #1E40AF; cursor: pointer;">
                                <input type="checkbox"
                                    id="check_tarjeta_tardio"
                                    onchange="toggleMetodoPagoTardio('tarjeta')"
                                    style="margin-right: 0.5rem;">
                                <i class="fas fa-credit-card" style="color: #3B82F6; margin-right: 0.5rem;"></i>
                                Tarjeta
                            </label>
                            <div id="panel_tarjeta_tardio" class="hidden" style="margin-top: 0.5rem;">
    <!-- Tipo de tarjeta: Crédito / Débito -->
    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
        <label class="rv-radio-chip" style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
                       background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem;
                       cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF; transition: all 0.2s;"
               id="label_credito_tardio"
               onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_debito_tardio').style.borderColor='#BFDBFE'; document.getElementById('label_debito_tardio').style.background='white';">
            <input type="radio" name="tipo_tarjeta_tardio" value="credito" style="accent-color: #3B82F6;">
            <i class="fas fa-credit-card" style="font-size: 0.625rem;"></i> Crédito
        </label>
        <label class="rv-radio-chip" style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
                       background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem;
                       cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF; transition: all 0.2s;"
               id="label_debito_tardio"
               onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_credito_tardio').style.borderColor='#BFDBFE'; document.getElementById('label_credito_tardio').style.background='white';">
            <input type="radio" name="tipo_tarjeta_tardio" value="debito" style="accent-color: #3B82F6;">
            <i class="fas fa-money-check-alt" style="font-size: 0.625rem;"></i> Débito
        </label>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
        <div>
            <label style="font-size: 0.75rem; color: #1E40AF;">Monto</label>
                                        <input type="number"
                                            name="monto_tarjeta"
                                            id="monto_tarjeta_tardio"
                                            data-money-format="true"
                                            step="0.01"
                                            min="0"
                                            oninput="calcularTotalesTardio()"
                                            onchange="calcularTotalesTardio()"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.75rem; color: #1E40AF;">Referencia</label>
                                        <input type="text"
                                            name="referencia_tarjeta"
                                            placeholder="Últimos 4 dígitos"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BFDBFE; border-radius: 0.375rem;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transferencia -->
                        <div class="metodo-pago-item rv-pay-option rv-pay-transfer" style="background: #F5F3FF; border: 2px solid #DDD6FE; border-radius: 0.5rem; padding: 0.75rem;">
                            <label style="display: flex; align-items: center; font-weight: 600; font-size: 0.875rem; color: #5B21B6; cursor: pointer;">
                                <input type="checkbox"
                                    id="check_transferencia_tardio"
                                    onchange="toggleMetodoPagoTardio('transferencia')"
                                    style="margin-right: 0.5rem;">
                                <i class="fas fa-exchange-alt" style="color: #9333EA; margin-right: 0.5rem;"></i>
                                Transferencia
                            </label>
                            <div id="panel_transferencia_tardio" class="hidden" style="margin-top: 0.5rem;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                                    <div>
                                        <label style="font-size: 0.75rem; color: #5B21B6;">Monto</label>
                                        <input type="number"
                                            name="monto_transferencia"
                                            id="monto_transferencia_tardio"
                                            data-money-format="true"
                                            step="0.01"
                                            min="0"
                                            oninput="calcularTotalesTardio()"
                                            onchange="calcularTotalesTardio()"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                                    </div>
                                    <div>
                                        <label style="font-size: 0.75rem; color: #5B21B6;">Referencia</label>
                                        <input type="text"
                                            name="referencia_transferencia"
                                            placeholder="Nº de operación"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumen de Totales (pago mixto) -->
                    <div id="resumen_totales_tardio" class="hidden" style="background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem; padding: 0.75rem; margin-top: 0.75rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.375rem; font-size: 0.875rem;">
                            <span style="color: #6B7280;">Total Pagado:</span>
                            <span id="total_pagado_tardio" style="font-weight: 700; color: #1F2937;">$0.00</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                            <span style="color: #6B7280;">Pendiente:</span>
                            <span id="pendiente_tardio" style="font-weight: 700; color: #DC2626;">$0.00</span>
                        </div>
                    </div>
                </div>
                <!-- ========== SECCIÓN DE FACTURA (TARDÍO) ========== -->
<div class="rv-invoice-section" style="margin-top: 1rem;">
    <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
        <i class="fas fa-file-invoice" style="color: #2563EB; margin-right: 0.375rem;"></i>
        ¿El cliente requiere factura?
        <span style="color: #EF4444; font-size: 0.75rem;">*</span>
    </h4>

    <div id="facturaContainerTardio" class="rv-invoice-grid" style="display: flex; gap: 0.5rem;">
        <!-- Opción SÍ -->
        <label class="rv-invoice-choice" style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                       background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                       cursor: pointer; transition: all 0.2s;"
               id="label_factura_si_tardio"
               onmouseover="this.style.borderColor='#3B82F6'"
               onmouseout="if(!document.getElementById('factura_si_tardio').checked) this.style.borderColor='#E5E7EB'">
            <input type="radio" name="requiere_factura" id="factura_si_tardio" value="si"
                   onchange="seleccionarFacturaTardio('si')"
                   style="accent-color: #2563EB;">
            <div>
                <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">Sí</span>
                <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Se registrará para facturación</p>
            </div>
        </label>

        <!-- Opción NO -->
        <label class="rv-invoice-choice" style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
                       background: #F9FAFB; border: 2px solid #E5E7EB; border-radius: 0.5rem;
                       cursor: pointer; transition: all 0.2s;"
               id="label_factura_no_tardio"
               onmouseover="this.style.borderColor='#6B7280'"
               onmouseout="if(!document.getElementById('factura_no_tardio').checked) this.style.borderColor='#E5E7EB'">
            <input type="radio" name="requiere_factura" id="factura_no_tardio" value="no"
                   onchange="seleccionarFacturaTardio('no')"
                   style="accent-color: #6B7280;">
            <div>
                <span style="font-weight: 600; font-size: 0.875rem; color: #1F2937;">No</span>
                <p style="font-size: 0.7rem; color: #6B7280; margin: 0;">Sin factura</p>
            </div>
        </label>
    </div>

    <!-- Mensaje cuando no se ha seleccionado -->
    <div id="facturaValidacionTardio" class="rv-checkin-message hidden"
         style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #FEF2F2;
                border-radius: 0.375rem; border: 1px solid #FECACA;">
        <p style="font-size: 0.75rem; color: #DC2626; margin: 0;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i>
            Debe indicar si el cliente requiere factura
        </p>
    </div>

    <!-- Info: se registrará para facturación interna -->
    <div id="facturaInfoInternaTardio" class="rv-checkin-note hidden"
         style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #EFF6FF;
                border-radius: 0.375rem; border: 1px solid #BFDBFE;">
        <p style="font-size: 0.7rem; color: #1E40AF; margin: 0;">
            <i class="fas fa-info-circle" style="margin-right: 0.25rem;"></i>
            El pago con tarjeta/transferencia se registrará en facturación para uso interno
        </p>
    </div>
</div>
<!-- ========== FIN SECCIÓN DE FACTURA (TARDÍO) ========== -->
                <!-- Notas Adicionales -->
                <div class="form-group rv-notes-section" style="margin-top: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Notas Adicionales (opcional)
                    </label>
                    <textarea name="notas_adicionales" rows="2"
                              placeholder="Ej: El personal olvidó hacer el check-in"
                              style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem; resize: vertical;"></textarea>
                </div>
                </div>

                <!-- Botones -->
                <div class="rv-checkin-actions" style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" onclick="cerrarModalCheckInTardio()"
                            class="rv-btn-cancel"
                            style="flex: 1; padding: 0.625rem; background: #F3F4F6; color: #374151; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarTardio"
                            class="rv-btn-confirm"
                            style="flex: 1; padding: 0.625rem; background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 700; cursor: pointer;">
                        ✓ Confirmar Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =====================================================
     MODAL: Modificar Días de Reservación
     ===================================================== -->
<div id="modalModificarDias" class="modal-overlay" style="display:none;">
    <div class="modal-content" style="width:480px; max-width:95vw;">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#4F46E5,#6366F1); color:white; padding:1rem 1.25rem; border-radius:0.75rem 0.75rem 0 0; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:0.625rem;">
                <div style="background:rgba(255,255,255,0.2); border-radius:0.5rem; width:2rem; height:2rem; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <div style="font-weight:700; font-size:0.95rem;">Modificar Días</div>
                    <div style="font-size:0.7rem; opacity:0.85;">Reservación #<?= $reservacion['id'] ?></div>
                </div>
            </div>
            <button onclick="cerrarModalModificarDias()" style="background:rgba(255,255,255,0.15); border:none; border-radius:0.375rem; color:white; width:1.75rem; height:1.75rem; cursor:pointer; display:flex; align-items:center; justify-content:center; position:static;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="padding:1.25rem;">
            <!-- Fechas actuales -->
            <div style="background:#F1F5F9; border-radius:0.625rem; padding:0.875rem; margin-bottom:1rem; display:flex; gap:1rem;">
                <div style="flex:1; text-align:center;">
                    <div style="font-size:0.65rem; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Entrada</div>
                    <div style="font-size:0.9rem; font-weight:700; color:#1E293B;"><?= date('d/m/Y', strtotime($reservacion['fecha_entrada'])) ?></div>
                </div>
                <div style="display:flex; align-items:center; color:#94A3B8;">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div style="flex:1; text-align:center;">
                    <div style="font-size:0.65rem; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Salida actual</div>
                    <div style="font-size:0.9rem; font-weight:700; color:#1E293B;"><?= date('d/m/Y', strtotime($reservacion['fecha_salida'])) ?></div>
                </div>
                <div style="flex:1; text-align:center;">
                    <div style="font-size:0.65rem; color:#64748B; font-weight:600; text-transform:uppercase; letter-spacing:.05em;">Noches actuales</div>
                    <div style="font-size:0.9rem; font-weight:700; color:#4F46E5;" id="mdd-noches-actuales">–</div>
                </div>
            </div>

            <!-- Selector de noches -->
            <div style="margin-bottom:1rem;">
                <label style="display:block; font-size:0.8rem; font-weight:600; color:#374151; margin-bottom:0.5rem;">
                    <i class="fas fa-moon mr-1" style="color:#4F46E5;"></i>
                    Nueva cantidad de noches
                </label>
                <div style="display:flex; align-items:center; gap:0.75rem;">
                    <button type="button" onclick="cambiarNoches(-1)"
                            style="background:#E0E7FF; color:#4F46E5; border:none; border-radius:0.5rem; width:2.5rem; height:2.5rem; font-size:1.25rem; cursor:pointer; font-weight:700;">−</button>
                    <input type="number" id="mdd-noches-input" min="1" max="365"
                           style="flex:1; text-align:center; font-size:1.5rem; font-weight:700; color:#1E293B; border:2px solid #C7D2FE; border-radius:0.625rem; padding:0.5rem; outline:none;"
                           oninput="actualizarPreviewDias()" />
                    <button type="button" onclick="cambiarNoches(1)"
                            style="background:#E0E7FF; color:#4F46E5; border:none; border-radius:0.5rem; width:2.5rem; height:2.5rem; font-size:1.25rem; cursor:pointer; font-weight:700;">+</button>
                </div>
            </div>

            <!-- Nueva fecha de salida -->
            <div style="background:#EEF2FF; border:1.5px solid #C7D2FE; border-radius:0.625rem; padding:0.75rem; margin-bottom:1rem; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:0.8rem; color:#4338CA; font-weight:600;">
                    <i class="fas fa-calendar-check mr-1"></i>
                    Nueva fecha de salida:
                </span>
                <span id="mdd-nueva-salida" style="font-size:0.9rem; font-weight:700; color:#312E81;">–</span>
            </div>

            <!-- Panel de resultado -->
            <div id="mdd-preview" style="display:none; border-radius:0.625rem; padding:0.875rem; margin-bottom:1rem;"></div>

            <!-- Botones -->
            <div style="display:flex; gap:0.75rem;">
                <button type="button" onclick="cerrarModalModificarDias()"
                        style="flex:1; background:#F1F5F9; color:#374151; border:none; border-radius:0.625rem; padding:0.75rem; font-weight:600; cursor:pointer;">
                    Cancelar
                </button>
                <button type="button" id="mdd-btn-verificar" onclick="verificarYConfirmarDias()"
                        style="flex:2; background:linear-gradient(135deg,#4F46E5,#6366F1); color:white; border:none; border-radius:0.625rem; padding:0.75rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem;">
                    <i class="fas fa-search" id="mdd-btn-icon"></i>
                    <span id="mdd-btn-texto">Verificar disponibilidad</span>
                </button>
            </div>
        </div>
    </div>
</div>
<div id="modalCotizacion" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 420px; border-radius: 16px; overflow: hidden;">
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
                    <p style="font-weight: 800; font-size: 1.25rem; color: #78350F;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </p>
                </div>
            </div>

            <!-- Campo de anticipo -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 6px;">
                    <i class="fas fa-hand-holding-usd" style="color: #059669; margin-right: 6px;"></i>
                    Anticipo del cliente (opcional)
                </label>
                <p style="font-size: 0.7rem; color: #6B7280; margin-bottom: 8px;">
                    Este monto es solo para la cotización, no se registrará en el sistema.
                </p>
                <div style="position: relative;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem; font-weight: 700; color: #059669;">$</span>
                    <input type="number"
                           id="inputAnticipoCotizacion"
                           data-money-format="true"
                           min="0"
                           max="<?= $reservacion['precio_total'] ?>"
                           step="1"
                           value="0"
                           placeholder="0"
                           oninput="actualizarSaldoCotizacion()"
                           style="width: 100%; padding: 12px 12px 12px 28px; border: 2px solid #D1D5DB; border-radius: 10px; font-size: 1.1rem; font-weight: 700; color: #374151; transition: border-color 0.2s, box-shadow 0.2s; background: #F9FAFB;"
                           onfocus="this.style.borderColor='#059669'; this.style.boxShadow='0 0 0 3px rgba(5,150,105,0.15)';"
                           onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none';">
                </div>
            </div>

            <!-- Resumen de saldo -->
            <div id="resumenSaldoCotizacion" style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 10px; padding: 12px; margin-bottom: 16px; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #065F46;">Total estancia:</span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #065F46;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #059669;">Anticipo:</span>
                    <span id="txtAnticipoCotizacion" style="font-size: 0.8rem; font-weight: 600; color: #059669;">-$0</span>
                </div>
                <div style="border-top: 1px dashed #BBF7D0; padding-top: 6px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.875rem; font-weight: 700; color: #065F46;">Saldo pendiente:</span>
                    <span id="txtSaldoCotizacion" style="font-size: 1.1rem; font-weight: 800; color: #065F46;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </span>
                </div>
            </div>

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
    <input type="hidden" name="anticipo" id="hiddenAnticipoCotizacion" value="0">
</form>
<!-- Formularios ocultos para acciones rápidas -->

<form id="formCheckOut" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<!-- Scripts -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>









<!-- ─────────────────────────────────────────────────────────────


<!-- Modal Cotización con Anticipo -->
<div id="modalCotizacion" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 420px; border-radius: 16px; overflow: hidden;">
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
                    <p style="font-weight: 800; font-size: 1.25rem; color: #78350F;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </p>
                </div>
            </div>

            <!-- Campo de anticipo -->
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 6px;">
                    <i class="fas fa-hand-holding-usd" style="color: #059669; margin-right: 6px;"></i>
                    Anticipo del cliente (opcional)
                </label>
                <p style="font-size: 0.7rem; color: #6B7280; margin-bottom: 8px;">
                    Este monto es solo para la cotización, no se registrará en el sistema.
                </p>
                <div style="position: relative;">
                    <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 1rem; font-weight: 700; color: #059669;">$</span>
                    <input type="number"
                           id="inputAnticipoCotizacion"
                           data-money-format="true"
                           min="0"
                           max="<?= $reservacion['precio_total'] ?>"
                           step="1"
                           value="0"
                           placeholder="0"
                           oninput="actualizarSaldoCotizacion()"
                           style="width: 100%; padding: 12px 12px 12px 28px; border: 2px solid #D1D5DB; border-radius: 10px; font-size: 1.1rem; font-weight: 700; color: #374151; transition: border-color 0.2s, box-shadow 0.2s; background: #F9FAFB;"
                           onfocus="this.style.borderColor='#059669'; this.style.boxShadow='0 0 0 3px rgba(5,150,105,0.15)';"
                           onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none';">
                </div>
            </div>

            <!-- Resumen de saldo -->
            <div id="resumenSaldoCotizacion" style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 10px; padding: 12px; margin-bottom: 16px; display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #065F46;">Total estancia:</span>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #065F46;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                    <span style="font-size: 0.8rem; color: #059669;">Anticipo:</span>
                    <span id="txtAnticipoCotizacion" style="font-size: 0.8rem; font-weight: 600; color: #059669;">-$0</span>
                </div>
                <div style="border-top: 1px dashed #BBF7D0; padding-top: 6px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.875rem; font-weight: 700; color: #065F46;">Saldo pendiente:</span>
                    <span id="txtSaldoCotizacion" style="font-size: 1.1rem; font-weight: 800; color: #065F46;">
                        <?= format_money($reservacion['precio_total'] ?? 0) ?>
                    </span>
                </div>
            </div>

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
    <input type="hidden" name="anticipo" id="hiddenAnticipoCotizacion" value="0">
</form>



<script>
// =============================================
// COTIZACIÓN PDF CON ANTICIPO
// =============================================
const TOTAL_RESERVACION_COTIZACION = <?= floatval($reservacion['precio_total']) ?>;

function cotizacionMoneyValue(inputOrValue) {
    if (window.MedisoftMoneyInput) {
        return window.MedisoftMoneyInput.read(inputOrValue);
    }

    const value = inputOrValue && typeof inputOrValue === 'object' && 'value' in inputOrValue
        ? inputOrValue.value
        : inputOrValue;

    return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function setCotizacionMoneyValue(input, value) {
    if (!input) {
        return;
    }

    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.set(input, value, { fixed: false });
    } else {
        input.value = value;
    }
}

function abrirModalCotizacion() {
    setCotizacionMoneyValue(document.getElementById('inputAnticipoCotizacion'), 0);
    document.getElementById('resumenSaldoCotizacion').style.display = 'none';
    document.getElementById('modalCotizacion').style.display = 'flex';
}

function cerrarModalCotizacion() {
    document.getElementById('modalCotizacion').style.display = 'none';
}

function actualizarSaldoCotizacion() {
    const input = document.getElementById('inputAnticipoCotizacion');
    let anticipo = cotizacionMoneyValue(input);

    // Validar que no exceda el total
    if (anticipo > TOTAL_RESERVACION_COTIZACION) {
        anticipo = TOTAL_RESERVACION_COTIZACION;
        setCotizacionMoneyValue(input, anticipo);
    }
    if (anticipo < 0) {
        anticipo = 0;
        setCotizacionMoneyValue(input, 0);
    }

    const resumenDiv = document.getElementById('resumenSaldoCotizacion');
    if (anticipo > 0) {
        resumenDiv.style.display = 'block';
        document.getElementById('txtAnticipoCotizacion').textContent = '-$' + anticipo.toLocaleString('es-MX');
        const saldo = TOTAL_RESERVACION_COTIZACION - anticipo;
        document.getElementById('txtSaldoCotizacion').textContent = '$' + saldo.toLocaleString('es-MX');
    } else {
        resumenDiv.style.display = 'none';
    }
}

function generarCotizacionPdf() {
    const anticipo = cotizacionMoneyValue(document.getElementById('inputAnticipoCotizacion'));
    document.getElementById('hiddenAnticipoCotizacion').value = anticipo;
    document.getElementById('formCotizacionReservacion').submit();

    // Cerrar modal después de un momento
    setTimeout(() => cerrarModalCotizacion(), 500);
}

// Cerrar modal al hacer click fuera
document.getElementById('modalCotizacion').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCotizacion();
});
</script>
<script>

// Variables globales
let totalReservacion = 0;
let vehiculosHuesped = [];

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
}

function validarFacturaCheckIn() {
    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const validacion = document.getElementById('facturaValidacion');

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
}

// =============================================
// FUNCIONES DE FACTURACIÓN - CHECK-IN TARDÍO
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
}
// FUNCIONES DE CHECK-IN CON PAGOS MIXTOS
function abrirModalCheckIn(id, total) {
    totalReservacion = parseFloat(total);

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
        totalACobrarElement.textContent = formatMoney(totalReservacion);
    }

    const resumenTotalElement = document.getElementById('resumenTotal');
    if (resumenTotalElement) {
        resumenTotalElement.textContent = formatMoney(totalReservacion);
    }

    resetearFormularioPago();
    resetearFacturaCheckIn();
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Función de check-out
function confirmarCheckOut(id) {
    const formCheckOut = document.getElementById('formCheckOut');

    if (!formCheckOut) {
        console.error('Formulario de check-out no encontrado');
        alert('Error: No se encontró el formulario de check-out');
        return;
    }

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Realizar Check-out?',
            text: "Se registrará la salida del huésped y las habitaciones pasarán a limpieza",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#D4AF37',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Sí, hacer check-out',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const horaInput = formCheckOut.querySelector('input[name="hora_salida"]');
                if (horaInput) {
                    horaInput.value = new Date().toTimeString().slice(0, 8);
                }

                formCheckOut.action = '<?= url("reservaciones/check-out/") ?>' + id;
                formCheckOut.submit();
            }
        });
    } else {
        if (confirm('¿Confirmar check-out?')) {
            formCheckOut.action = '<?= url("reservaciones/check-out/") ?>' + id;
            formCheckOut.submit();
        }
    }
}

// Funciones de vehículos
function cargarVehiculos() {
    const huespedId = <?= json_encode($huesped['id'] ?? 0) ?>;
    if (!huespedId) {
        document.getElementById('listaVehiculos').innerHTML =
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
            document.getElementById('listaVehiculos').innerHTML =
                '<p class="text-center text-gray-500 text-sm">Error al cargar vehículos</p>';
        });
}

// Mostrar vehículos con diseño mejorado
function mostrarVehiculos() {
    const container = document.getElementById('listaVehiculos');

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
    document.getElementById('modalCancelacion').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalCancelacion() {
    document.getElementById('modalCancelacion').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Funciones auxiliares
function formatMoney(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function moneyValue(inputOrValue) {
    if (window.MedisoftMoneyInput) {
        return window.MedisoftMoneyInput.read(inputOrValue);
    }

    const value = inputOrValue && typeof inputOrValue === 'object' && 'value' in inputOrValue
        ? inputOrValue.value
        : inputOrValue;

    return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function setMoneyValue(inputOrId, value) {
    const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
    if (!input) {
        return;
    }

    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.set(input, value);
    } else {
        input.value = Number(value || 0).toFixed(2);
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
    resetearFormularioPago();
}

// Funciones de pago
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

            setMoneyValue(montoInput, montoRestante);

            const recibidoInput = document.getElementById('recibido_efectivo');
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
function setCheckInPaymentChecked(metodo, checked) {
    const checkbox = document.getElementById('check_' + metodo);
    const panel = document.getElementById('panel_' + metodo);
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox) {
        return false;
    }

    if (checkbox.checked !== checked) {
        checkbox.checked = checked;
        toggleMetodoPago(metodo);
        return true;
    }

    if (checked) {
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');
    } else {
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
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
    setCheckInPaymentChecked(metodo, true);
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
    let metodos = getSelectedCheckInPaymentMethods();

    if (metodos.length < 2) {
        resetearFormularioPago();
        metodos = ['efectivo', 'tarjeta'];
    }

    metodos.forEach(function(metodo) {
        setCheckInPaymentChecked(metodo, true);
    });

    const partes = splitMoneyParts(totalReservacion, metodos.length);

    metodos.forEach(function(metodo, index) {
        if (metodo !== 'efectivo') {
            setMoneyValue('monto_' + metodo, partes[index]);
        }
    });

    if (metodos.includes('efectivo')) {
        setMoneyValue('monto_efectivo', partes[metodos.indexOf('efectivo')]);
    }

    calcularTotales();

    if (metodos.includes('efectivo')) {
        setMoneyValue('recibido_efectivo', moneyValue(document.getElementById('monto_efectivo')));
        calcularCambio();
    }

    mostrarMensaje('Pago dividido entre ' + metodos.length + ' metodos.', 'success');
}

document.getElementById('formCheckInModal').addEventListener('submit', function(e) {
    e.preventDefault();

    // NUEVA VALIDACIÓN: Verificar factura
    if (!validarFacturaCheckIn()) {
        // Scroll hacia la sección de factura
        document.getElementById('facturaContainer').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const totalPagado = calcularTotalPagado();
    const diferencia = Math.abs(totalPagado - totalReservacion);

    if (diferencia > 0.01) {
        mostrarMensaje('El total pagado no coincide con el monto de la reservación', 'error');
        return;
    }

    const checkEfectivo = document.getElementById('check_efectivo');
    if (checkEfectivo && checkEfectivo.checked) {
        const recibido = moneyValue(document.getElementById('recibido_efectivo'));
        const montoPagar = moneyValue(document.getElementById('monto_efectivo'));

        if (montoPagar > 0 && recibido < montoPagar) {
            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            return;
        }
    }

    const metodosSeleccionados = ['efectivo', 'tarjeta', 'transferencia'].filter(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        return checkbox && checkbox.checked;
    });

    if (metodosSeleccionados.length === 0) {
        mostrarMensaje('Debe seleccionar al menos un método de pago', 'error');
        return;
    }

    // Validar tipo de tarjeta (débito/crédito) si tarjeta está seleccionada
    const checkTarjetaCI = document.getElementById('check_tarjeta');
    if (checkTarjetaCI && checkTarjetaCI.checked) {
        const tipoTarjetaSeleccionado = document.querySelector('input[name="tipo_tarjeta"]:checked');
        if (!tipoTarjetaSeleccionado) {
            mostrarMensaje('Debe seleccionar el tipo de tarjeta: Crédito o Débito', 'error');
            document.getElementById('panel_tarjeta').scrollIntoView({ behavior: 'smooth', block: 'center' });
            const labelCredito = document.getElementById('label_credito');
            const labelDebito = document.getElementById('label_debito');
            if (labelCredito) { labelCredito.style.borderColor = '#EF4444'; }
            if (labelDebito) { labelDebito.style.borderColor = '#EF4444'; }
            setTimeout(() => {
                if (labelCredito) { labelCredito.style.borderColor = '#BFDBFE'; }
                if (labelDebito) { labelDebito.style.borderColor = '#BFDBFE'; }
            }, 3000);
            return;
        }
    }

    sanitizeMoneyForm(this);
    this.submit();
});
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

function calcularTotales() {
    let totalPagado = 0;

    const totalOtros = calcularTotalPagadoSinEfectivo();
    totalPagado = totalOtros;

    const checkEfectivo = document.getElementById('check_efectivo');
    const montoEfectivoInput = document.getElementById('monto_efectivo');

    if (checkEfectivo && checkEfectivo.checked && montoEfectivoInput) {
        const montoRestante = Math.max(0, totalReservacion - totalOtros);
        setMoneyValue(montoEfectivoInput, montoRestante);
        totalPagado += montoRestante;
    }

    const resumenPagadoElement = document.getElementById('resumenPagado');
    if (resumenPagadoElement) {
        resumenPagadoElement.textContent = formatMoney(totalPagado);
    }

    const totalPagadoFinal = calcularTotalPagado();
    const diferencia = totalReservacion - totalPagadoFinal;

    const divRestante = document.getElementById('divRestante');
    const divCambio = document.getElementById('divCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');

    if (divRestante) divRestante.style.display = 'none';
    if (divCambio) divCambio.style.display = 'none';

    if (Math.abs(diferencia) < 0.01) {
        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
    } else if (diferencia > 0.01) {
        if (!checkEfectivo || !checkEfectivo.checked) {
            if (divRestante) {
                divRestante.style.display = 'flex';
                const resumenRestante = document.getElementById('resumenRestante');
                if (resumenRestante) {
                    resumenRestante.textContent = formatMoney(diferencia);
                }
            }
            mostrarMensaje('Falta completar el pago', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        } else {
            ocultarMensaje();
            if (btnConfirmar) btnConfirmar.disabled = false;
        }
    } else if (diferencia < -0.01) {
        mostrarMensaje('El monto total excede el precio de la reservación', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    }

    if (checkEfectivo && checkEfectivo.checked) {
        calcularCambio();
    }
}

function calcularCambio() {
    const checkEfectivo = document.getElementById('check_efectivo');
    if (!checkEfectivo || !checkEfectivo.checked) return;

    const montoPagarInput = document.getElementById('monto_efectivo');
    const montoRecibidoInput = document.getElementById('recibido_efectivo');
    const cambioSpan = document.getElementById('cambio_efectivo');

    if (!montoPagarInput || !montoRecibidoInput || !cambioSpan) return;

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
        if (montoInput) montoInput.value = '';
        if (card) card.classList.remove('is-open');
    });

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
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalCheckIn').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalCheckIn();
    }
});

document.getElementById('modalCancelacion').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalCancelacion();
    }
});

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

// Función de agregar vehículo (implementar según tu sistema)
function abrirModalAgregarVehiculo() {
    // Redirigir a la página de huésped para agregar vehículo
    const huespedId = <?= json_encode($huesped['id'] ?? 0) ?>;
    if (huespedId) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Agregar Vehículo',
                text: 'Será redirigido al perfil del huésped para agregar un vehículo',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#9333EA',
                confirmButtonText: 'Ir al perfil',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= url("huespedes/") ?>' + huespedId + '#vehiculos';
                }
            });
        } else {
            if (confirm('¿Ir al perfil del huésped para agregar vehículo?')) {
                window.location.href = '<?= url("huespedes/") ?>' + huespedId + '#vehiculos';
            }
        }
    }
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
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Eliminar vehículo?',
            html: `<p>Se eliminará el vehículo:</p><p class="font-bold">${descripcion}</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                eliminarVehiculo(id);
            }
        });
    } else {
        if (confirm(`¿Eliminar vehículo ${descripcion}?`)) {
            eliminarVehiculo(id);
        }
    }
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

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Vehículo eliminado',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        } else {
            throw new Error(data.message || 'Error al eliminar vehículo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo eliminar el vehículo'
            });
        } else {
            alert('Error al eliminar vehículo');
        }
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
function agregarNota(trigger) {
    const textarea = document.getElementById('nuevaNota');
    if (!textarea) {
        return;
    }

    const nota = textarea.value.trim();

    if (!nota) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Nota vacia',
                text: 'Por favor escribe algo en la nota',
                timer: 2000
            });
        } else {
            alert('Por favor escribe algo en la nota');
        }
        textarea.focus();
        return;
    }

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

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Nota agregada',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            throw new Error(data.message || 'Error al agregar nota');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message
            });
        } else {
            alert('Error: ' + error.message);
        }
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
    nuevaNotaTextarea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            agregarNota(document.getElementById('btnAgregarNota'));
        }
    });
}

function abrirModalCheckOut() {
    document.getElementById('modalCheckOut').classList.remove('hidden');
    actualizarSeleccion();
}

function cerrarModalCheckOut() {
    document.getElementById('modalCheckOut').classList.add('hidden');
}

function seleccionarTodas(seleccionar) {
    const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]');
    checkboxes.forEach(cb => cb.checked = seleccionar);
    actualizarSeleccion();
}

function actualizarSeleccion() {
    const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]');
    const seleccionadas = Array.from(checkboxes).filter(cb => cb.checked);
    const total = checkboxes.length;
    const btnConfirmar = document.getElementById('btnConfirmarCheckOut');
    const mensaje = document.getElementById('mensajeSeleccion');
    const textoMensaje = document.getElementById('textoMensaje');

    // Habilitar/deshabilitar botón
    btnConfirmar.disabled = seleccionadas.length === 0;

    // Mostrar mensaje informativo
    if (seleccionadas.length > 0) {
        mensaje.classList.remove('hidden');

        if (seleccionadas.length === total) {
            mensaje.className = 'p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700';
            textoMensaje.innerHTML = '<strong>Check-out completo:</strong> Se liberarán todas las habitaciones y la reservación se marcará como finalizada.';
        } else {
            mensaje.className = 'p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700';
            textoMensaje.innerHTML = `<strong>Check-out parcial:</strong> Se liberarán ${seleccionadas.length} de ${total} habitaciones. La reservación permanecerá activa con las habitaciones restantes.`;
        }
    } else {
        mensaje.classList.add('hidden');
    }
}

// Confirmar antes de enviar
document.getElementById('formCheckOut').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="habitaciones[]"]:checked');
    const total = document.querySelectorAll('input[name="habitaciones[]"]').length;

    if (checkboxes.length === 0) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Selección requerida',
                text: 'Debe seleccionar al menos una habitación'
            });
        } else {
            alert('Debe seleccionar al menos una habitación');
        }
        return;
    }

    const mensaje = checkboxes.length === total
        ? '¿Realizar check-out completo de todas las habitaciones?'
        : `¿Realizar check-out de ${checkboxes.length} habitación(es)?`;

    if (typeof Swal !== 'undefined') {
    e.preventDefault();
    const form = e.target;  // ✅ AGREGA ESTA LÍNEA
    Swal.fire({
            title: 'Confirmar Check-out',
            text: mensaje,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#F97316',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit(); // ✅ USAR LA REFERENCIA GUARDADA
            }
        });
    } else {
        if (!confirm(mensaje)) {
            e.preventDefault();
        }
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCheckOut();
    }
});


let reservacionTardioData = {};
let metodosSeleccionadosTardio = new Set(['sin_pago']);

function abrirModalCheckInTardio(id, huesped, habitaciones, fechaEntrada, fechaSalida, total, tipo, diasRetraso) {
    const modalTardio = document.getElementById('modalCheckInTardio');

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
    form.action = '<?= url("reservaciones/check-in/") ?>' + id;

    document.getElementById('tipo_tardio').value = tipo;

    // Llenar información
    document.getElementById('huesped_tardio').textContent = huesped;
    document.getElementById('habitaciones_tardio').textContent = habitaciones;
    document.getElementById('fecha_entrada_tardio').textContent = fechaEntrada;
    document.getElementById('fecha_salida_tardio').textContent = fechaSalida;
    document.getElementById('totalACobrarTardio').textContent = formatMoney(total);

    // Configurar según tipo
    const titulo = document.getElementById('tituloModalTardio');
    const alerta = document.getElementById('alertaTardio');
    const btnConfirmar = document.getElementById('btnConfirmarTardio');
    const campoHora = document.getElementById('campo_hora_entrada');
    const notaPagoOpcional = document.getElementById('nota_pago_opcional');
    const checkSinPago = document.getElementById('check_sin_pago');

    if (tipo === 'express') {
        // Proceso Express
        titulo.innerHTML = '<i class="fas fa-bolt" style="color: #EA580C; margin-right: 0.5rem;"></i>Proceso Express';
        btnConfirmar.innerHTML = '⚡ Procesar Express';
        btnConfirmar.style.background = 'linear-gradient(135deg, #EA580C 0%, #C2410C 100%)';
        campoHora.style.display = 'none';
        notaPagoOpcional.style.display = 'none';
        checkSinPago.parentElement.parentElement.style.display = 'none';

        alerta.style.display = 'block';
        alerta.innerHTML = `
            <div style="background: #FFF7ED; border-left: 4px solid #EA580C; padding: 0.75rem; border-radius: 0.375rem;">
                <p style="font-size: 0.875rem; color: #9A3412; font-weight: 600; margin-bottom: 0.25rem;">
                    ⚠️ IMPORTANTE: Proceso Express
                </p>
                <p style="font-size: 0.75rem; color: #9A3412;">
                    La fecha de salida pasó hace <strong>${diasRetraso} día(s)</strong>.
                    Este proceso hará check-in Y check-out automáticamente en un solo paso.
                </p>
            </div>
        `;
    } else {
        // Check-in Tardío Normal
        titulo.innerHTML = '<i class="fas fa-clock" style="color: #F59E0B; margin-right: 0.5rem;"></i>Check-in Tardío';
        btnConfirmar.innerHTML = '✓ Confirmar Check-in Tardío';
        btnConfirmar.style.background = 'linear-gradient(135deg, #F59E0B 0%, #D97706 100%)';
        campoHora.style.display = 'block';
        notaPagoOpcional.style.display = 'block';
        checkSinPago.parentElement.parentElement.style.display = 'block';

        alerta.style.display = 'block';
        alerta.innerHTML = `
            <div style="background: #FFFBEB; border-left: 4px solid #F59E0B; padding: 0.75rem; border-radius: 0.375rem;">
                <p style="font-size: 0.875rem; color: #92400E; font-weight: 600; margin-bottom: 0.25rem;">
                    ⏰ Check-in con ${diasRetraso} día(s) de retraso
                </p>
                <p style="font-size: 0.75rem; color: #92400E;">
                    La fecha de entrada fue ${fechaEntrada}. Se registrará el check-in con nota de retraso.
                </p>
            </div>
        `;
    }

    // Resetear pagos
    metodosSeleccionadosTardio = tipo === 'express' ? new Set() : new Set(['sin_pago']);
    resetearPagosTardio();
    resetearFacturaTardio();

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
}

function toggleMetodoPagoTardio(metodo) {
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

function setTardioPaymentChecked(metodo, checked) {
    const checkbox = document.getElementById('check_' + metodo + (metodo === 'sin_pago' ? '' : '_tardio'));
    const panel = document.getElementById('panel_' + metodo + '_tardio');
    const card = checkbox?.closest('.rv-pay-option, .metodo-pago-item');

    if (!checkbox) {
        return false;
    }

    if (checkbox.checked !== checked) {
        checkbox.checked = checked;
        toggleMetodoPagoTardio(metodo);
        return true;
    }

    if (checked) {
        metodosSeleccionadosTardio.add(metodo);
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');
    } else {
        metodosSeleccionadosTardio.delete(metodo);
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
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
    setTardioPaymentChecked(metodo, true);
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
    let metodos = getSelectedTardioPaymentMethods();

    if (metodos.length < 2) {
        resetearPagosTardio();
        metodos = ['efectivo', 'tarjeta'];
    }

    metodos.forEach(function(metodo) {
        setTardioPaymentChecked(metodo, true);
    });

    const partes = splitMoneyParts(reservacionTardioData.total, metodos.length);

    metodos.forEach(function(metodo, index) {
        if (metodo !== 'efectivo') {
            setMoneyValue('monto_' + metodo + '_tardio', partes[index]);
        }
    });

    if (metodos.includes('efectivo')) {
        setMoneyValue('monto_efectivo_tardio', partes[metodos.indexOf('efectivo')]);
    }

    mostrarResumenTardio();
    calcularTotalesTardio();

    if (metodos.includes('efectivo')) {
        setMoneyValue('recibido_efectivo_tardio', moneyValue(document.getElementById('monto_efectivo_tardio')));
        calcularCambioTardio();
    }
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

function calcularCambioTardio() {
    const monto = moneyValue(document.getElementById('monto_efectivo_tardio'));
    const recibido = moneyValue(document.getElementById('recibido_efectivo_tardio'));
    const cambio = recibido - monto;

    document.getElementById('cambio_efectivo_tardio').textContent = formatMoney(cambio);
    calcularTotalesTardio();
}

function calcularTotalesTardio() {
    if (metodosSeleccionadosTardio.has('efectivo')) {
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
        pendienteEl.style.color = '#DC2626'; // Rojo
    } else if (pendiente < 0) {
        pendienteEl.style.color = '#16A34A'; // Verde (pago de más)
    } else {
        pendienteEl.style.color = '#6B7280'; // Gris (exacto)
    }
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
    metodosSeleccionadosTardio = reservacionTardioData.tipo === 'express' ? new Set() : new Set(['sin_pago']);

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

    // Marcar "sin pago" por defecto si no es express
    if (reservacionTardioData.tipo !== 'express') {
        const sinPago = document.getElementById('check_sin_pago');
        if (sinPago) {
            sinPago.checked = true;
            const sinPagoCard = sinPago.closest('.rv-pay-option, .metodo-pago-item');
            if (sinPagoCard) sinPagoCard.classList.add('is-open');
        }
    }
}

// Validar formulario antes de enviar
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formCheckInTardio');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // NUEVA VALIDACIÓN: Verificar factura
            if (!validarFacturaTardio()) {
                document.getElementById('facturaContainerTardio').scrollIntoView({ behavior: 'smooth', block: 'center' });
                return false;
            }

            // Validación para proceso express
            if (reservacionTardioData.tipo === 'express') {
                if (metodosSeleccionadosTardio.size === 0) {
                    alert('Debe seleccionar al menos un método de pago para el proceso express');
                    return false;
                }

                if (!confirm('¿Confirma que desea realizar el PROCESO EXPRESS?\n\nEsto hará check-in Y check-out automáticamente ya que la reservación está vencida.')) {
                    return false;
                }
            }

            // Validar tipo de tarjeta tardío
            if (metodosSeleccionadosTardio.has('tarjeta')) {
                const tipoTarjetaTardio = document.querySelector('#panel_tarjeta_tardio input[name="tipo_tarjeta_tardio"]:checked');
                if (!tipoTarjetaTardio) {
                    alert('Debe seleccionar el tipo de tarjeta: Crédito o Débito');
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
    habitaciones: <?= json_encode(array_map(function($h) { return ['numero' => $h['numero'], 'tipo' => $h['tipo']]; }, $habitaciones)) ?>,
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
<title>Ticket #${ticketData.id}</title>
<style>
    @page { margin: 0; size: 80mm auto; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
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
        font-weight: 900;
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
        font-weight: 900;
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
    .row .value { font-weight: 900; text-align: right; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    table td { padding: 2px 0; font-weight: 700; }
    .total-row {
        font-size: 18px;
        font-weight: 900;
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
        font-weight: 900;
        margin-top: 5px;
        padding: 4px;
        letter-spacing: 0.5px;
    }
    @media print {
        body { width: 80mm; }
        .no-print { display: none !important; }
    }
</style>
</head>
<body>
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
    } else {
        alert('Por favor permite las ventanas emergentes para imprimir el ticket.');
    }
}

// ========== FUNCIONES MODAL CAMBIAR MÉTODO DE PAGO ==========
let metodosSeleccionadosCP = new Set();
const totalReservacionCP = <?= json_encode(floatval($reservacion['precio_total'] ?? 0)) ?>;

function abrirModalCambiarPago() {
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

    document.getElementById('asignadoCP').textContent = '$0.00';
    document.getElementById('divPendienteCP').style.display = 'none';
    document.getElementById('msgValidacionCP').classList.add('hidden');

    // Resetear sección de factura
    resetearFacturaCP();

    document.getElementById('modalCambiarPago').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function cerrarModalCambiarPago() {
    document.getElementById('modalCambiarPago').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function toggleMetodoPagoCP(metodo) {
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

    // Auto-calcular efectivo si hay otros métodos
    if (metodosSeleccionadosCP.has('efectivo') && metodosSeleccionadosCP.size > 1) {
        let otrosMontos = 0;
        if (metodosSeleccionadosCP.has('tarjeta')) {
            otrosMontos += moneyValue(document.getElementById('monto_tarjeta_cp'));
        }
        if (metodosSeleccionadosCP.has('transferencia')) {
            otrosMontos += moneyValue(document.getElementById('monto_transferencia_cp'));
        }
        const efectivoCalc = totalReservacionCP - otrosMontos;
        if (efectivoCalc >= 0) {
            setMoneyValue('monto_efectivo_cp', efectivoCalc);
        }
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

    ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
        if (metodosSeleccionadosCP.has(m)) {
            const monto = moneyValue(document.getElementById('monto_' + m + '_cp'));
            if (monto <= 0) {
                mostrarMsgCP('Todos los métodos seleccionados deben tener un monto mayor a 0', 'error');
                return;
            }
            totalAsignado += monto;

            const pago = { metodo: m, monto: monto };

            if (m === 'tarjeta') {
                const tipoTarjeta = document.querySelector('input[name="tipo_tarjeta_cp"]:checked');
                if (!tipoTarjeta) {
                    mostrarMsgCP('Debe seleccionar tipo de tarjeta: Crédito o Débito', 'error');
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

    if (pagosNuevos.length === 0) return;

    if (Math.abs(totalAsignado - totalReservacionCP) > 0.01) {
        mostrarMsgCP(`El total asignado (${formatMoney(totalAsignado)}) no coincide con el total (${formatMoney(totalReservacionCP)})`, 'error');
        return;
    }

    if (!confirm('¿Está seguro de cambiar el método de pago de esta reservación?')) return;

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
            cerrarModalCambiarPago();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Método actualizado',
                    text: data.message || 'El método de pago se cambió correctamente',
                    confirmButtonColor: '#10B981'
                }).then(() => location.reload());
            } else {
                alert(data.message || 'Método de pago actualizado correctamente');
                location.reload();
            }
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
    div.style.background = tipo === 'error' ? '#FEF2F2' : '#F0FDF4';
    div.style.color = tipo === 'error' ? '#DC2626' : '#059669';
    div.style.border = '1px solid ' + (tipo === 'error' ? '#FECACA' : '#BBF7D0');
    div.innerHTML = '<i class="fas fa-' + (tipo === 'error' ? 'exclamation-circle' : 'check-circle') + '" style="margin-right:0.25rem;"></i>' + msg;

    setTimeout(() => div.classList.add('hidden'), 5000);
}

// =============================================
// FUNCIONES DE FACTURACIÓN - CAMBIAR PAGO (CP)
// =============================================

function seleccionarFacturaCP(valor) {
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
document.getElementById('modalCambiarPago').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCambiarPago();
});

// Auto-imprimir ticket después de un check-in exitoso
<?php if ($auto_imprimir_ticket): ?>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        imprimirTicketTermico('descargar');
    }, 1500);
});
<?php endif; ?>

// ============================================
// MODIFICAR DÍAS DE RESERVACIÓN
// ============================================
const MDD = {
    fechaEntrada: '<?= $reservacion['fecha_entrada'] ?>',
    fechaSalida:  '<?= $reservacion['fecha_salida'] ?>',
    reservacionId: <?= $reservacion['id'] ?>,
    precioActual: <?= floatval($reservacion['precio_total']) ?>,
    nochesActuales: 0,
    nuevaFechaSalida: null,
    estadoVerificado: false,
    nuevoPrecioCalculado: 0
};

function abrirModalModificarDias() {
    const entrada = new Date(MDD.fechaEntrada + 'T12:00:00');
    const salida  = new Date(MDD.fechaSalida + 'T12:00:00');
    MDD.nochesActuales = Math.round((salida - entrada) / (1000 * 60 * 60 * 24));

    document.getElementById('mdd-noches-actuales').textContent = MDD.nochesActuales + ' noche' + (MDD.nochesActuales !== 1 ? 's' : '');
    document.getElementById('mdd-noches-input').value = MDD.nochesActuales;
    document.getElementById('mdd-preview').style.display = 'none';
    document.getElementById('mdd-preview').innerHTML = '';
    resetBtnVerificar();
    MDD.estadoVerificado = false;
    actualizarPreviewDias();

    document.getElementById('modalModificarDias').style.display = 'flex';
}

function cerrarModalModificarDias() {
    document.getElementById('modalModificarDias').style.display = 'none';
}

function cambiarNoches(delta) {
    const input = document.getElementById('mdd-noches-input');
    const val = parseInt(input.value) || MDD.nochesActuales;
    input.value = Math.max(1, val + delta);
    actualizarPreviewDias();
}

function actualizarPreviewDias() {
    const noches = parseInt(document.getElementById('mdd-noches-input').value) || 1;
    const entrada = new Date(MDD.fechaEntrada + 'T12:00:00');
    const nuevaSalida = new Date(entrada);
    nuevaSalida.setDate(nuevaSalida.getDate() + noches);

    MDD.nuevaFechaSalida = nuevaSalida.toISOString().split('T')[0];

    const opciones = { day: '2-digit', month: '2-digit', year: 'numeric' };
    document.getElementById('mdd-nueva-salida').textContent = nuevaSalida.toLocaleDateString('es-MX', opciones);

    MDD.estadoVerificado = false;
    resetBtnVerificar();
    document.getElementById('mdd-preview').style.display = 'none';
}

function resetBtnVerificar() {
    const btn = document.getElementById('mdd-btn-verificar');
    document.getElementById('mdd-btn-icon').className  = 'fas fa-search';
    document.getElementById('mdd-btn-texto').textContent = 'Verificar disponibilidad';
    btn.style.background = 'linear-gradient(135deg,#4F46E5,#6366F1)';
    btn.style.pointerEvents = '';
    btn.onclick = verificarYConfirmarDias;
}

async function verificarYConfirmarDias() {
    const noches = parseInt(document.getElementById('mdd-noches-input').value);

    if (noches === MDD.nochesActuales) {
        mostrarPreviewMDD('warning', '<i class="fas fa-info-circle mr-2"></i>El número de noches es igual al actual. No hay cambios que hacer.');
        return;
    }
    if (noches < 1) {
        mostrarPreviewMDD('error', '<i class="fas fa-exclamation-triangle mr-2"></i>Mínimo 1 noche.');
        return;
    }

    document.getElementById('mdd-btn-icon').className  = 'fas fa-spinner fa-spin';
    document.getElementById('mdd-btn-texto').textContent = 'Verificando...';
    document.getElementById('mdd-btn-verificar').style.pointerEvents = 'none';
    document.getElementById('mdd-preview').style.display = 'none';

    try {
        const resp = await fetch(`<?= url('reservaciones/verificar-modificar-dias') ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                reservacion_id: MDD.reservacionId,
                nueva_fecha_salida: MDD.nuevaFechaSalida
            })
        });

        const data = await resp.json();

        if (!data.disponible) {
            mostrarPreviewMDD('error',
                `<i class="fas fa-ban mr-2"></i><strong>Sin disponibilidad</strong><br>
                 <small style="opacity:.85;">${data.mensaje || 'Alguna habitación no está libre en las fechas solicitadas.'}</small>`
            );
            resetBtnVerificar();
            return;
        }

        const diff = data.nuevo_precio - MDD.precioActual;
        const signo = diff >= 0 ? '+' : '';
        const colorDiff = diff > 0 ? '#DC2626' : (diff < 0 ? '#059669' : '#374151');
        const notasExtra = noches > MDD.nochesActuales
            ? '<small style="opacity:.8;">Se extenderá la fecha de salida.</small>'
            : '<small style="opacity:.8;">Se reducirá la fecha de salida.</small>';

        mostrarPreviewMDD('success',
            `<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.5rem;">
                <span style="font-weight:700;"><i class="fas fa-check-circle mr-1"></i>Habitaciones disponibles</span>
             </div>
             <div style="display:flex; gap:1rem; flex-wrap:wrap; font-size:.82rem;">
                <div>Precio actual: <strong>$${MDD.precioActual.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong></div>
                <div>Precio nuevo: <strong>$${data.nuevo_precio.toLocaleString('es-MX', {minimumFractionDigits:2})}</strong></div>
                <div>Diferencia: <strong style="color:${colorDiff};">${signo}$${Math.abs(diff).toLocaleString('es-MX', {minimumFractionDigits:2})}</strong></div>
             </div>
             <div style="margin-top:.5rem;">${notasExtra}</div>`
        );

        document.getElementById('mdd-btn-icon').className  = 'fas fa-check';
        document.getElementById('mdd-btn-texto').textContent = 'Confirmar cambio';
        document.getElementById('mdd-btn-verificar').style.background = 'linear-gradient(135deg,#059669,#10B981)';
        document.getElementById('mdd-btn-verificar').style.pointerEvents = '';
        document.getElementById('mdd-btn-verificar').onclick = confirmarCambioDias;

        MDD.estadoVerificado = true;
        MDD.nuevoPrecioCalculado = data.nuevo_precio;

    } catch (e) {
        mostrarPreviewMDD('error', '<i class="fas fa-exclamation-triangle mr-2"></i>Error de conexión. Intenta de nuevo.');
        resetBtnVerificar();
    }
}

async function confirmarCambioDias() {
    document.getElementById('mdd-btn-icon').className  = 'fas fa-spinner fa-spin';
    document.getElementById('mdd-btn-texto').textContent = 'Guardando...';
    document.getElementById('mdd-btn-verificar').style.pointerEvents = 'none';

    try {
        const resp = await fetch(`<?= url('reservaciones/modificar-dias') ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                reservacion_id: MDD.reservacionId,
                nueva_fecha_salida: MDD.nuevaFechaSalida
            })
        });

        const data = await resp.json();

        if (data.success) {
            cerrarModalModificarDias();

            // Mostrar resumen con ajuste de caja si aplica
            if (data.ajuste_caja) {
                const ac = data.ajuste_caja;
                const metodoTexto = {efectivo:'Efectivo', tarjeta:'Tarjeta', transferencia:'Transferencia'};
                const esDevolucion = ac.tipo === 'devolucion';

                Swal.fire({
                    icon: esDevolucion ? 'info' : 'success',
                    title: esDevolucion ? 'Devolución registrada' : 'Cobro adicional registrado',
                    html: `<div style="text-align:center;">
                        <p style="font-size:1.1rem; margin-bottom:.5rem;">
                            ${esDevolucion ? 'Se registró una <strong>devolución</strong> en caja por' : 'Se registró un <strong>cobro adicional</strong> en caja por'}
                        </p>
                        <p style="font-size:1.8rem; font-weight:700; color:${esDevolucion ? '#DC2626' : '#059669'}; margin:.5rem 0;">
                            $${ac.monto.toLocaleString('es-MX', {minimumFractionDigits:2})}
                        </p>
                        <p style="font-size:.85rem; color:#6B7280;">
                            Método: <strong>${metodoTexto[ac.metodo_pago] || ac.metodo_pago}</strong>
                        </p>
                        ${esDevolucion ? '<p style="font-size:.8rem; color:#9CA3AF; margin-top:.5rem;"><i class="fas fa-info-circle mr-1"></i>Recuerda entregar el cambio al huésped</p>' : ''}
                    </div>`,
                    confirmButtonColor: '#5C7A4E',
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                window.location.reload();
            }
        } else {
            mostrarPreviewMDD('error', `<i class="fas fa-times mr-2"></i>${data.mensaje || 'Error al guardar el cambio.'}`);
            resetBtnVerificar();
        }
    } catch (e) {
        mostrarPreviewMDD('error', '<i class="fas fa-exclamation-triangle mr-2"></i>Error de conexión.');
        resetBtnVerificar();
    }
}

function mostrarPreviewMDD(tipo, html) {
    const colores = {
        success: { bg: '#ECFDF5', border: '#6EE7B7', color: '#065F46' },
        error:   { bg: '#FEF2F2', border: '#FECACA', color: '#7F1D1D' },
        warning: { bg: '#FFFBEB', border: '#FDE68A', color: '#92400E' }
    };
    const c = colores[tipo] || colores.warning;
    const el = document.getElementById('mdd-preview');
    el.style.cssText = `display:block; background:${c.bg}; border:1.5px solid ${c.border}; color:${c.color}; border-radius:.625rem; padding:.875rem; margin-bottom:1rem; font-size:.82rem; line-height:1.5;`;
    el.innerHTML = html;
}
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

/* Check-in confirm modal: scoped redesign, keeps IDs and form contract intact. */
#modalCheckIn.rv-checkin-modal {
    padding: clamp(10px, 2vw, 24px);
    background:
        radial-gradient(circle at 16% 18%, color-mix(in srgb, var(--brand-accent, #BD9441) 24%, transparent), transparent 34%),
        linear-gradient(145deg, rgba(7, 12, 22, .86), rgba(23, 29, 43, .74));
    -webkit-backdrop-filter: blur(10px) saturate(1.05);
    backdrop-filter: blur(10px) saturate(1.05);
    z-index: 12000;
}

#modalCheckIn .rv-checkin-shell {
    width: min(960px, calc(100vw - 24px)) !important;
    max-width: min(960px, calc(100vw - 24px)) !important;
    max-height: min(92dvh, 900px);
    display: grid !important;
    grid-template-columns: minmax(250px, .9fr) minmax(0, 1.7fr);
    overflow: hidden;
    border-radius: 28px;
    background: #F8F4EC;
    border: 1px solid rgba(255,255,255,.55);
    box-shadow: 0 38px 92px -34px rgba(4, 8, 18, .78), 0 0 0 1px rgba(255,255,255,.22) inset;
    animation: rvCheckInShow .32s cubic-bezier(.22, 1, .36, 1);
}

@keyframes rvCheckInShow {
    from { opacity: 0; transform: translateY(22px) scale(.985); }
    to { opacity: 1; transform: none; }
}

#modalCheckIn .rv-checkin-hero {
    position: relative;
    isolation: isolate;
    min-height: 100%;
    padding: 28px !important;
    display: flex !important;
    flex-direction: column;
    justify-content: space-between;
    gap: 28px;
    color: #fff;
    background:
        linear-gradient(150deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 88%, #111827), color-mix(in srgb, var(--brand-primary, #1B2746) 74%, #1F2937)),
        var(--brand-secondary, #0F172A) !important;
    border-bottom: 0 !important;
}

#modalCheckIn .rv-checkin-hero::before {
    content: '';
    position: absolute;
    inset: 14px;
    z-index: -1;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 22px;
}

#modalCheckIn .rv-checkin-hero::after {
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

#modalCheckIn .rv-checkin-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.24);
    border-radius: 14px;
    background: rgba(255,255,255,.13);
    color: #fff;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease;
}

#modalCheckIn .rv-checkin-close:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.24);
}

#modalCheckIn .rv-checkin-hero > button {
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

#modalCheckIn .rv-checkin-hero > button:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.24) !important;
}

#modalCheckIn .rv-checkin-hero h3 {
    margin: 0 !important;
    padding-top: 34px;
    max-width: 9ch;
    display: grid;
    gap: 14px;
    color: #fff !important;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(2rem, 4vw, 2.8rem) !important;
    font-weight: 700 !important;
    line-height: .9 !important;
    letter-spacing: 0 !important;
    text-wrap: balance;
}

#modalCheckIn .rv-checkin-hero h3::after {
    content: 'Valida llegada, pago mixto y factura antes de confirmar.';
    max-width: 24ch;
    color: rgba(255,255,255,.72);
    font-family: 'Manrope', system-ui, sans-serif;
    font-size: .86rem;
    font-weight: 700;
    line-height: 1.45;
}

#modalCheckIn .rv-checkin-hero h3 i {
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

#modalCheckIn .rv-checkin-flag {
    width: max-content;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    color: rgba(255,255,255,.86);
    font-size: .72rem;
    font-weight: 850;
}

#modalCheckIn .rv-checkin-heading {
    display: grid;
    gap: 14px;
    padding-top: 26px;
}

#modalCheckIn .rv-checkin-icon {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    border-radius: 18px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.16);
}

#modalCheckIn .rv-checkin-heading h3 {
    margin: 0;
    max-width: 9ch;
    color: #fff;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(2rem, 4vw, 2.8rem);
    font-weight: 700;
    line-height: .9;
    letter-spacing: 0;
    text-wrap: balance;
}

#modalCheckIn .rv-checkin-heading p {
    max-width: 25ch;
    margin: 0;
    color: rgba(255,255,255,.72);
    font-size: .86rem;
    font-weight: 700;
    line-height: 1.45;
}

#modalCheckIn .rv-checkin-total {
    display: grid;
    gap: 6px;
    padding: 16px;
    border-radius: 20px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.18);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.16);
}

#modalCheckIn .rv-checkin-total span {
    color: rgba(255,255,255,.66);
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: 0;
    text-transform: uppercase;
}

#modalCheckIn .rv-checkin-total strong {
    color: #fff;
    font-size: clamp(1.75rem, 4vw, 2.45rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-checkin-form {
    min-height: 0;
    max-height: min(92dvh, 900px);
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(175px, .58fr);
    gap: 14px;
    padding: 20px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--brand-primary, #1B2746) 28%, #D7DCE3) transparent;
}

#modalCheckIn .rv-checkin-body {
    min-height: 0;
    padding: 0 !important;
    overflow: hidden;
}

#modalCheckIn .rv-arrival-field,
#modalCheckIn .rv-total-field,
#modalCheckIn .rv-payment-section,
#modalCheckIn .rv-invoice-section,
#modalCheckIn .rv-checkin-summary {
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E7DDD1) !important;
    border-radius: 20px !important;
    background: rgba(255,255,255,.92) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
}

#modalCheckIn .rv-arrival-field,
#modalCheckIn .rv-total-field,
#modalCheckIn .rv-payment-section,
#modalCheckIn .rv-invoice-section {
    margin: 0 !important;
    padding: 16px !important;
}

#modalCheckIn .rv-total-field {
    background: linear-gradient(145deg, #fff, color-mix(in srgb, var(--brand-accent, #BD9441) 9%, #fff)) !important;
}

#modalCheckIn .rv-total-field > div {
    padding: 0 !important;
    background: transparent !important;
    text-align: left !important;
    border-radius: 0 !important;
}

#modalCheckIn #totalACobrar {
    display: block;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: clamp(1.55rem, 4vw, 2.2rem) !important;
    font-weight: 950 !important;
    line-height: 1 !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-payment-section,
#modalCheckIn .rv-invoice-section,
#modalCheckIn .rv-checkin-summary,
#modalCheckIn #mensajeValidacion,
#modalCheckIn .rv-checkin-actions {
    grid-column: 1 / -1;
}

#modalCheckIn .rv-checkin-scroll {
    padding: 20px;
    display: grid;
    gap: 14px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--brand-primary, #1B2746) 28%, #D7DCE3) transparent;
}

#modalCheckIn .rv-checkin-block,
#modalCheckIn .rv-checkin-summary {
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E7DDD1);
    border-radius: 20px;
    background: rgba(255,255,255,.92);
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
}

#modalCheckIn .rv-checkin-block { padding: 16px; }

#modalCheckIn .rv-checkin-arrival {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(165px, .55fr);
    gap: 14px;
    align-items: end;
    background:
        linear-gradient(145deg, #fff, color-mix(in srgb, var(--brand-accent, #BD9441) 8%, #fff));
}

#modalCheckIn .rv-section-title {
    margin: 0 0 10px;
    color: var(--brand-secondary, #0F172A);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    font-size: .94rem;
    font-weight: 950;
}

#modalCheckIn .rv-section-title small {
    color: #7A8498;
    font-size: .71rem;
    font-weight: 800;
    letter-spacing: 0;
}

#modalCheckIn .rv-section-title i { color: var(--brand-primary, #1B2746); }

#modalCheckIn .rv-payment-section > h4,
#modalCheckIn .rv-invoice-section > h4 {
    margin: 0 0 10px !important;
    color: var(--brand-secondary, #0F172A) !important;
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: .94rem !important;
    font-weight: 950 !important;
}

#modalCheckIn .rv-payment-section > h4 i,
#modalCheckIn .rv-invoice-section > h4 i {
    color: var(--brand-primary, #1B2746) !important;
    margin-right: 0 !important;
}

#modalCheckIn .rv-field-label {
    display: block;
    margin-bottom: 7px;
    color: #111827;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: 0;
    text-transform: uppercase;
}

#modalCheckIn .rv-field-input {
    width: 100%;
    min-height: 43px;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 16%, #DFE4EA);
    border-radius: 14px;
    padding: 10px 12px;
    background: #fff;
    color: var(--brand-secondary, #0F172A);
    font-size: .9rem;
    font-weight: 760;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

#modalCheckIn .rv-field-input:focus {
    border-color: var(--brand-primary, #1B2746);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-primary, #1B2746) 12%, transparent);
}

#modalCheckIn .form-label,
#modalCheckIn .rv-arrival-field > label,
#modalCheckIn .rv-total-field > label,
#modalCheckIn .rv-pay-option label[style*="font-size: 0.75rem"] {
    display: block !important;
    margin-bottom: 7px !important;
    color: #111827 !important;
    font-size: .72rem !important;
    font-weight: 900 !important;
    letter-spacing: 0 !important;
    text-transform: uppercase;
}

#modalCheckIn input[type="time"],
#modalCheckIn input[type="number"],
#modalCheckIn input[type="text"] {
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

#modalCheckIn input[type="time"]:focus,
#modalCheckIn input[type="number"]:focus,
#modalCheckIn input[type="text"]:focus {
    border-color: var(--brand-primary, #1B2746) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand-primary, #1B2746) 12%, transparent) !important;
}

#modalCheckIn .rv-pay-methods {
    display: grid !important;
    gap: 10px !important;
}

#modalCheckIn .rv-pay-option {
    --rv-pay-color: var(--brand-primary, #1B2746);
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--rv-pay-color) 16%, #E5E7EB) !important;
    border-radius: 17px !important;
    background: #fff !important;
    padding: 0 !important;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease, transform .18s ease;
}

#modalCheckIn .rv-pay-option::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: color-mix(in srgb, var(--rv-pay-color) 76%, #fff);
    opacity: .68;
}

#modalCheckIn .rv-pay-option.is-open,
#modalCheckIn .rv-pay-option:has(input[type="checkbox"]:checked) {
    border-color: color-mix(in srgb, var(--rv-pay-color) 46%, #D7DCE3);
    background: color-mix(in srgb, var(--rv-pay-color) 4%, #fff);
    box-shadow: 0 18px 36px -28px color-mix(in srgb, var(--rv-pay-color) 70%, transparent);
    transform: translateY(-1px);
}

#modalCheckIn .rv-pay-cash { --rv-pay-color: #148653; }
#modalCheckIn .rv-pay-card { --rv-pay-color: #2563EB; }
#modalCheckIn .rv-pay-transfer { --rv-pay-color: #7C3AED; }

#modalCheckIn .rv-payment-shortcuts,
#modalCheckInTardio .rv-payment-shortcuts {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    margin: 0 0 12px;
}

#modalCheckIn .rv-money-shortcut,
#modalCheckInTardio .rv-money-shortcut,
#modalCheckIn .rv-money-mini,
#modalCheckInTardio .rv-money-mini {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid color-mix(in srgb, var(--rv-shortcut-color, var(--brand-primary, #1B2746)) 22%, #DCE2EA);
    border-radius: 13px;
    background: color-mix(in srgb, var(--rv-shortcut-color, var(--brand-primary, #1B2746)) 7%, #fff);
    color: color-mix(in srgb, var(--rv-shortcut-color, var(--brand-primary, #1B2746)) 82%, #111827);
    font-size: .77rem;
    font-weight: 900;
    line-height: 1.15;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

#modalCheckIn .rv-money-shortcut:hover,
#modalCheckInTardio .rv-money-shortcut:hover,
#modalCheckIn .rv-money-mini:hover,
#modalCheckInTardio .rv-money-mini:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rv-shortcut-color, var(--brand-primary, #1B2746)) 42%, #DCE2EA);
    box-shadow: 0 12px 24px -22px color-mix(in srgb, var(--rv-shortcut-color, var(--brand-primary, #1B2746)) 80%, transparent);
}

#modalCheckIn .rv-money-shortcut.is-cash,
#modalCheckInTardio .rv-money-shortcut.is-cash { --rv-shortcut-color: #148653; }
#modalCheckIn .rv-money-shortcut.is-card,
#modalCheckInTardio .rv-money-shortcut.is-card { --rv-shortcut-color: #2563EB; }
#modalCheckIn .rv-money-shortcut.is-transfer,
#modalCheckInTardio .rv-money-shortcut.is-transfer { --rv-shortcut-color: #7C3AED; }
#modalCheckIn .rv-money-shortcut.is-split,
#modalCheckInTardio .rv-money-shortcut.is-split { --rv-shortcut-color: var(--brand-accent, #BD9441); }

#modalCheckIn .rv-money-mini,
#modalCheckInTardio .rv-money-mini {
    width: 100%;
    min-height: 32px;
    margin-top: 7px;
    --rv-shortcut-color: #148653;
}

#modalCheckIn .rv-pay-toggle {
    width: 100%;
    padding: 13px 14px 13px 17px;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    color: var(--brand-secondary, #0F172A);
    font-size: .9rem;
    font-weight: 920;
}

#modalCheckIn .rv-pay-option > label {
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

#modalCheckIn .rv-pay-option > label i {
    color: var(--rv-pay-color) !important;
    margin-right: 0 !important;
}

#modalCheckIn .rv-pay-option > label input {
    width: 18px !important;
    height: 18px !important;
    margin-right: 0 !important;
    accent-color: var(--rv-pay-color) !important;
}

#modalCheckIn .rv-pay-toggle input {
    width: 18px;
    height: 18px;
    accent-color: var(--rv-pay-color);
}

#modalCheckIn .rv-pay-toggle i { color: var(--rv-pay-color); }

#modalCheckIn .rv-pay-panel {
    border-top: 1px solid color-mix(in srgb, var(--rv-pay-color) 18%, #E5E7EB);
    padding: 13px 14px 15px 17px;
    background: color-mix(in srgb, var(--rv-pay-color) 6%, #fff);
}

#modalCheckIn .rv-pay-option > div[id^="panel_"] {
    margin-top: 0 !important;
    border-top: 1px solid color-mix(in srgb, var(--rv-pay-color) 18%, #E5E7EB);
    padding: 13px 14px 15px 17px;
    background: color-mix(in srgb, var(--rv-pay-color) 6%, #fff);
}

#modalCheckIn #panel_efectivo > div:first-child,
#modalCheckIn #panel_tarjeta > div:last-child,
#modalCheckIn #panel_transferencia > div:first-child {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 10px !important;
}

#modalCheckIn #panel_tarjeta > div:first-child {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 8px !important;
    margin-bottom: 10px !important;
}

#modalCheckIn .rv-input-grid,
#modalCheckIn .rv-card-type,
#modalCheckIn .rv-invoice-grid {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px !important;
}

#modalCheckIn .rv-change-pill {
    margin-top: 9px;
    padding: 9px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    border-radius: 14px;
    background: rgba(255,255,255,.78);
    color: #475467;
    font-size: .82rem;
    font-weight: 850;
}

#modalCheckIn #panel_efectivo > div:last-child {
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

#modalCheckIn #cambio_efectivo {
    color: #148653 !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-change-pill strong {
    color: #148653;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-change-pill strong.text-red-600 { color: #DC2626; }

#modalCheckIn .rv-radio-chip,
#modalCheckIn .rv-invoice-choice {
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #DCE2EA) !important;
    border-radius: 15px !important;
    background: #fff !important;
    cursor: pointer;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}

#modalCheckIn .rv-radio-chip {
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

#modalCheckIn .rv-radio-chip input,
#modalCheckIn .rv-invoice-choice input {
    accent-color: var(--brand-primary, #1B2746);
}

#modalCheckIn .rv-radio-chip:has(input:checked) {
    border-color: #2563EB !important;
    background: #EFF6FF !important;
    box-shadow: 0 12px 24px -22px rgba(37,99,235,.82);
}

#modalCheckIn .rv-invoice-choice {
    min-height: 70px;
    display: flex !important;
    align-items: flex-start !important;
    gap: 10px !important;
    padding: 13px !important;
}

#modalCheckIn .rv-invoice-choice strong {
    display: block;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .88rem !important;
    font-weight: 920 !important;
}

#modalCheckIn .rv-invoice-choice > div > span {
    display: block;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .88rem !important;
    font-weight: 920 !important;
}

#modalCheckIn .rv-invoice-choice p,
#modalCheckIn .rv-invoice-choice span span {
    display: block;
    margin-top: 2px;
    color: #667085 !important;
    font-size: .72rem !important;
    font-weight: 700 !important;
    line-height: 1.35 !important;
}

#modalCheckIn #label_factura_si.is-selected {
    border-color: color-mix(in srgb, #2563EB 62%, #DCE2EA) !important;
    background: #EFF6FF !important;
    box-shadow: 0 12px 24px -22px rgba(37,99,235,.82);
}

#modalCheckIn #label_factura_no.is-selected {
    border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 38%, #DCE2EA) !important;
    background: color-mix(in srgb, var(--brand-primary, #1B2746) 5%, #fff) !important;
}

#modalCheckIn .rv-checkin-note,
#modalCheckIn .rv-checkin-message {
    margin-top: 9px !important;
    border-radius: 13px !important;
    padding: 10px 11px !important;
    font-size: .78rem !important;
    font-weight: 780 !important;
    line-height: 1.35 !important;
}

#modalCheckIn .rv-checkin-note {
    border: 1px solid #BFDBFE !important;
    background: #EFF6FF !important;
    color: #1E40AF !important;
}

#modalCheckIn .rv-checkin-message {
    border: 1px solid #FECACA !important;
    background: #FEF2F2 !important;
    color: #B42318 !important;
}

#modalCheckIn .rv-checkin-message.is-warning {
    border-color: #FDE68A !important;
    background: #FFFBEB !important;
    color: #92400E !important;
}

#modalCheckIn .rv-checkin-message.is-info {
    border-color: #BFDBFE !important;
    background: #EFF6FF !important;
    color: #1D4ED8 !important;
}

#modalCheckIn .rv-checkin-message.is-success {
    border-color: #BBF7D0 !important;
    background: #F0FDF4 !important;
    color: #047857 !important;
}

#modalCheckIn .rv-checkin-summary {
    padding: 15px 16px !important;
    margin: 0 !important;
    background: linear-gradient(135deg, #fff, #F6F2EA) !important;
}

#modalCheckIn .rv-checkin-summary h5 {
    margin: 0 0 8px !important;
    color: var(--brand-secondary, #0F172A) !important;
    font-size: .82rem !important;
    font-weight: 950 !important;
}

#modalCheckIn .rv-summary-row {
    display: flex !important;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 4px 0;
    color: #667085 !important;
    font-size: .82rem !important;
    font-weight: 780 !important;
    margin-bottom: 0 !important;
}

#modalCheckIn .rv-summary-row strong {
    color: var(--brand-secondary, #0F172A) !important;
    font-variant-numeric: tabular-nums;
}

#modalCheckIn .rv-summary-row.is-paid strong { color: #148653; }
#modalCheckIn .rv-summary-row.is-due strong { color: #DC2626; }
#modalCheckIn .rv-summary-row.is-change strong { color: #2563EB; }

#modalCheckIn .rv-checkin-actions {
    position: sticky;
    bottom: 0;
    display: flex !important;
    gap: 10px !important;
    padding: 15px 20px 20px !important;
    border-top: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E7DDD1);
    background: color-mix(in srgb, #F8F4EC 90%, transparent) !important;
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}

#modalCheckIn .rv-btn-cancel,
#modalCheckIn .rv-btn-confirm {
    min-height: 45px !important;
    border-radius: 14px !important;
    padding: 0 16px !important;
    font-size: .9rem !important;
    font-weight: 930 !important;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

#modalCheckIn .rv-btn-cancel {
    flex: .9;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #DCE2EA) !important;
    color: var(--brand-secondary, #0F172A) !important;
    background: #fff !important;
}

#modalCheckIn .rv-btn-confirm {
    flex: 1.25;
    border: 0 !important;
    color: #fff !important;
    background: linear-gradient(135deg, #148653, #0F6F49) !important;
    box-shadow: 0 16px 32px -19px rgba(20,134,83,.92) !important;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

#modalCheckIn .rv-btn-confirm:disabled {
    opacity: .58;
    cursor: not-allowed;
    box-shadow: none;
}

#modalCheckIn .rv-btn-cancel:hover,
#modalCheckIn .rv-btn-confirm:not(:disabled):hover {
    transform: translateY(-1px);
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
    font-family: 'Cormorant Garamond', Georgia, serif;
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
        padding: 14px;
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
