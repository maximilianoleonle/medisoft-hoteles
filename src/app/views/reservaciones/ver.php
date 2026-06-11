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
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin: 0 0 14px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--rd-brand) 13%, #E6D7BF);
    border-radius: 20px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--rd-brand) 96%, #111827), color-mix(in srgb, var(--rd-accent) 20%, var(--rd-brand)));
    box-shadow: 0 22px 44px -32px color-mix(in srgb, var(--rd-brand) 62%, transparent);
}

.reservation-detail-v2 .rd-strip-item {
    min-height: 74px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 16px;
    background: rgba(255,255,255,.08);
}

.reservation-detail-v2 .rd-strip-item span {
    color: color-mix(in srgb, #FDFBF7 70%, var(--rd-accent));
    font-size: .7rem;
    font-weight: 850;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.reservation-detail-v2 .rd-strip-item strong {
    color: #FDFBF7;
    font-size: 1rem;
    font-weight: 950;
    line-height: 1.05;
}

.reservation-detail-v2 .rd-strip-item.is-total {
    background: color-mix(in srgb, var(--rd-accent) 18%, rgba(255,255,255,.08));
    border-color: color-mix(in srgb, var(--rd-accent) 40%, rgba(255,255,255,.18));
}

.reservation-detail-v2 .rd-strip-item.is-total strong {
    color: color-mix(in srgb, var(--rd-accent) 72%, #FDFBF7);
    font-size: 1.22rem;
}

.reservation-detail-v2 .rd-quick-nav {
    position: sticky;
    top: 75px;
    z-index: 30;
    display: flex;
    gap: 8px;
    align-items: center;
    margin: -2px 0 16px;
    padding: 8px;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 28%, #E8DDCA);
    border-radius: 18px;
    background: color-mix(in srgb, #FDFBF7 94%, var(--rd-accent));
    box-shadow: 0 18px 38px -30px color-mix(in srgb, var(--rd-brand) 46%, transparent);
    backdrop-filter: blur(10px);
}

.reservation-detail-v2 .rd-quick-nav a {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid transparent;
    border-radius: 12px;
    color: var(--rd-muted);
    font-size: .76rem;
    font-weight: 850;
    text-decoration: none;
    transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
}

.reservation-detail-v2 .rd-quick-nav a:hover,
.reservation-detail-v2 .rd-quick-nav a:focus-visible {
    color: var(--rd-brand);
    border-color: color-mix(in srgb, var(--rd-accent) 24%, var(--rd-line));
    background: color-mix(in srgb, var(--rd-accent) 7%, #FDFBF7);
    outline: none;
    transform: translateY(-1px);
}

.reservation-detail-v2 .rd-quick-nav a.is-active {
    color: #FDFBF7;
    border-color: color-mix(in srgb, var(--rd-accent) 48%, var(--rd-brand));
    background: linear-gradient(135deg, var(--rd-brand), color-mix(in srgb, var(--rd-accent) 24%, var(--rd-brand)));
    box-shadow: 0 12px 24px -18px color-mix(in srgb, var(--rd-brand) 68%, transparent);
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

.reservation-detail-v2 .rd-section-card.is-visible {
    border-color: color-mix(in srgb, var(--rd-section-accent, var(--rd-accent)) 38%, var(--rd-line)) !important;
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

    .reservation-detail-v2 .rd-quick-nav {
        top: 10px;
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

    .reservation-detail-v2 .rd-quick-nav {
        overflow-x: auto;
        justify-content: flex-start;
        border-radius: 15px;
        padding: 7px;
        scroll-snap-type: x proximity;
    }

    .reservation-detail-v2 .rd-quick-nav a {
        flex: 0 0 auto;
        scroll-snap-align: start;
        white-space: nowrap;
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
<script>
function medisoftVolverAnterior(event, fallbackUrl) {
    if (event) event.preventDefault();

    try {
        var refUrl = new URL(document.referrer || '');
        var esAuth = /\/(?:login|logout)\/?$/.test(refUrl.pathname);

        if (refUrl.origin === window.location.origin && refUrl.href !== window.location.href && !esAuth) {
            window.location.href = refUrl.href;
            return false;
        }
    } catch (e) {}

    window.location.href = fallbackUrl;
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    const navLinks = Array.from(document.querySelectorAll('[data-rd-nav]'));
    const sections = navLinks
        .map(link => document.getElementById(link.getAttribute('data-rd-nav')))
        .filter(Boolean);

    if (!navLinks.length || !sections.length) return;

    const setActiveSection = function(id) {
        navLinks.forEach(link => {
            link.classList.toggle('is-active', link.getAttribute('data-rd-nav') === id);
        });
        sections.forEach(section => {
            section.classList.toggle('is-visible', section.id === id);
        });
    };

    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const target = document.getElementById(link.getAttribute('data-rd-nav'));
            if (!target) return;

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            history.replaceState(null, '', '#' + target.id);
            setActiveSection(target.id);
        });
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            const visible = entries
                .filter(entry => entry.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];

            if (visible) {
                setActiveSection(visible.target.id);
            }
        }, {
            rootMargin: '-30% 0px -55% 0px',
            threshold: [0.2, 0.45, 0.7]
        });

        sections.forEach(section => observer.observe(section));
    }
});
</script>

<div class="detail-view reservation-detail-v2" style="min-height: 100vh; background: linear-gradient(to bottom, #F9FAFB, #F3F4F6);">
    <!-- Header -->
    <div class="detail-header">
        <div class="container mx-auto px-3">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="<?= url('reservaciones') ?>" onclick="return medisoftVolverAnterior(event, this.href)" class="hover:scale-110 transition-transform">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-xl sm:text-2xl font-bold">
                                Reservación #<?= htmlspecialchars($reservacion['id'] ?? '') ?>
                            </h1>
                            <span class="estado-badge estado-<?= $reservacion['estado'] ?>">
                                <i class="fas fa-<?= htmlspecialchars($estado_info['icon'] ?? 'circle') ?> text-xs"></i>
                                <?= htmlspecialchars($estado_info['label']) ?>
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs opacity-90 mt-1">
                            <span><i class="fas fa-bed mr-1 text-gold"></i><?= $reservacion['total_habitaciones'] ?? '0' ?> habitaciones</span>
                            <?php if (($reservacion['habitaciones_cortesia'] ?? 0) > 0): ?>
                                <span class="text-green-300">
                                    <i class="fas fa-gift mr-1"></i><?= $reservacion['habitaciones_cortesia'] ?> gratis
                                </span>
                            <?php endif; ?>
                            <span><i class="fas fa-user mr-1 text-gold"></i><?= htmlspecialchars($reservacion['usuario_registro'] ?? 'Sistema') ?></span>
                            <span><i class="fas fa-clock mr-1 text-gold"></i><?= !empty($reservacion['created_at']) ? date('d/m/Y H:i', strtotime($reservacion['created_at'])) : '' ?></span>
                        </div>
                    </div>
                </div>

            <!-- Acciones Desktop -->
            <div class="hidden lg:flex items-center gap-2 no-print">
                <?php
// Sistema de Check-in Tardío - Detección Inteligente
if ($reservacion['estado'] == 'confirmada'):
    $hoy = date('Y-m-d');
    $fecha_entrada = $reservacion['fecha_entrada'];
    $fecha_salida = $reservacion['fecha_salida'];

    // Preparar datos para JavaScript
    $habitaciones_texto = implode(', ', array_column($habitaciones, 'numero'));
    $huesped_nombre_js = json_encode($huesped['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $habitaciones_texto_js = json_encode($habitaciones_texto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $fecha_entrada_formato_js = json_encode(date('d/m/Y', strtotime($fecha_entrada)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $fecha_salida_formato_js = json_encode(date('d/m/Y', strtotime($fecha_salida)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $huesped_nombre_js_attr = htmlspecialchars($huesped_nombre_js, ENT_QUOTES, 'UTF-8');
    $habitaciones_texto_js_attr = htmlspecialchars($habitaciones_texto_js, ENT_QUOTES, 'UTF-8');
    $fecha_entrada_formato_js_attr = htmlspecialchars($fecha_entrada_formato_js, ENT_QUOTES, 'UTF-8');
    $fecha_salida_formato_js_attr = htmlspecialchars($fecha_salida_formato_js, ENT_QUOTES, 'UTF-8');

    // CASO 1: Check-in normal (solo fecha de entrada)
    if ($hoy == $fecha_entrada): ?>
        <button onclick="abrirModalCheckIn(<?= htmlspecialchars($reservacion['id']) ?>, <?= htmlspecialchars($reservacion['precio_total']) ?>)"
                class="btn-action btn-success">
            <i class="fas fa-sign-in-alt"></i>
            Check-in
        </button>
    <?php
    // CASO 2: Check-in tardío (después de entrada pero antes de salida)
    elseif ($hoy > $fecha_entrada && $hoy < $fecha_salida):
        $dias_retraso = (strtotime($hoy) - strtotime($fecha_entrada)) / (60 * 60 * 24);
    ?>
        <button onclick="abrirModalCheckInTardio(<?= (int) $reservacion['id'] ?>, <?= $huesped_nombre_js_attr ?>, <?= $habitaciones_texto_js_attr ?>, <?= $fecha_entrada_formato_js_attr ?>, <?= $fecha_salida_formato_js_attr ?>, <?= (float) $reservacion['precio_total'] ?>, 'normal_tardio', <?= (int) $dias_retraso ?>)"
                class="btn-action bg-yellow-500 text-white hover:bg-yellow-600">
            <i class="fas fa-clock"></i>
            Check-in Tardío (<?= $dias_retraso ?> día<?= $dias_retraso > 1 ? 's' : '' ?>)
        </button>
    <?php
    // CASO 3: Proceso Express (ya pasó la fecha de salida)
    elseif ($hoy >= $fecha_salida):
        $dias_pasados = (strtotime($hoy) - strtotime($fecha_salida)) / (60 * 60 * 24);
    ?>
        <button onclick="abrirModalCheckInTardio(<?= (int) $reservacion['id'] ?>, <?= $huesped_nombre_js_attr ?>, <?= $habitaciones_texto_js_attr ?>, <?= $fecha_entrada_formato_js_attr ?>, <?= $fecha_salida_formato_js_attr ?>, <?= (float) $reservacion['precio_total'] ?>, 'express', <?= (int) $dias_pasados ?>)"
                class="btn-action bg-orange-600 text-white hover:bg-orange-700">
            <i class="fas fa-bolt"></i>
            Proceso Express (<?= $dias_pasados ?> día<?= $dias_pasados > 1 ? 's' : '' ?>)
        </button>
    <?php
    endif;
endif;
?>

                <?php if ($reservacion['estado'] == 'checked_in'): ?>
    <!-- Check-out con opción de selección de habitaciones -->
    <button
        type="button"
        onclick="abrirModalCheckOut()"
        class="btn-action btn-warning">
        <i class="fas fa-sign-out-alt"></i>
        Check-out
    </button>
<?php endif; ?>

                <?php if (in_array($reservacion['estado'], ['confirmada', 'checked_in'])): ?>
                    <button onclick="mostrarFormularioCancelacion()"
                            class="btn-action btn-danger">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </button>
                <?php endif; ?>

                <?php if ($reservacion['estado'] == 'confirmada'): ?>
                    <a href="<?= url('reservaciones/editar-habitaciones/' . $reservacion['id']) ?>"
                       class="btn-action bg-purple-500 text-white hover:bg-purple-600">
                        <i class="fas fa-bed"></i>
                        Modificar Habitaciones
                    </a>
                <?php endif; ?>

                <?php if (in_array($reservacion['estado'], ['confirmada', 'checked_in'])): ?>
                    <button onclick="abrirModalModificarDias()"
                            class="btn-action bg-indigo-500 text-white hover:bg-indigo-600">
                        <i class="fas fa-calendar-alt"></i>
                        Modificar Días
                    </button>
                <?php endif; ?>

                <button onclick="abrirModalCotizacion()"
        class="btn-action btn-cotizacion-ver">
    <i class="fas fa-file-pdf"></i>
    Cotización PDF
</button>

                <!-- ── Botón WhatsApp ── -->
                <?php if (!empty($huesped['telefono'])): ?>
                <div class="relative" id="wa-dropdown-wrapper">
                    <button onclick="toggleWAMenu()"
                            class="btn-action flex items-center gap-1.5"
                            style="background:#25D366;color:white;"
                            title="Enviar mensaje por WhatsApp">
                        <i class="fab fa-whatsapp text-base"></i>
                        WhatsApp
                        <i class="fas fa-chevron-down text-xs opacity-80"></i>
                    </button>
                    <div id="wa-menu"
                         class="hidden absolute right-0 mt-1 w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden"
                         style="top:100%;">
                        <?php
                        $wa_tel   = preg_replace('/\D/', '', $huesped['telefono']);
                        // Agregar código de país México si no lo tiene
                        if (strlen($wa_tel) === 10) $wa_tel = '52' . $wa_tel;
                        $wa_nombre = $huesped['nombre_completo'] ?? 'huésped';
                        $wa_habs   = implode(', ', array_column($habitaciones, 'numero'));
                        $wa_entrada = date('d/m/Y', strtotime($reservacion['fecha_entrada']));
                        $wa_salida  = date('d/m/Y', strtotime($reservacion['fecha_salida']));
                        $wa_noches  = max(1, (new DateTime($reservacion['fecha_salida']))->diff(new DateTime($reservacion['fecha_entrada']))->days);
                        $wa_precio  = '$' . number_format($reservacion['precio_total'], 2);
                        $wa_id      = $reservacion['id'];
                        $wa_metodo  = strtoupper($reservacion['metodo_pago'] ?? '');

                        $msg_confirmacion = urlencode(
                            "✅ *Confirmación de Reservación - {$nombreHotelVisible}*\n\n" .
                            "Hola {$wa_nombre}, su reservación ha sido confirmada.\n\n" .
                            "🏨 *Habitación(es):* {$wa_habs}\n" .
                            "📅 *Entrada:* {$wa_entrada}\n" .
                            "📅 *Salida:* {$wa_salida}\n" .
                            "🌙 *Noches:* {$wa_noches}\n" .
                            "💰 *Total:* {$wa_precio}\n" .
                            "🔖 *Reservación #:* {$wa_id}\n\n" .
                            "Check-in a partir de las 3:00 PM.\n" .
                            "¡Le esperamos! 🌿"
                        );

                        $wa_dias_para_llegar = (strtotime($reservacion['fecha_entrada']) - strtotime(date('Y-m-d'))) / 86400;
                        $msg_recordatorio = urlencode(
                            "⏰ *Recordatorio de llegada - {$nombreHotelVisible}*\n\n" .
                            "Hola {$wa_nombre}, le recordamos que su llegada es *mañana " . date('d/m/Y', strtotime($reservacion['fecha_entrada'])) . "*.\n\n" .
                            "🏨 *Habitación(es):* {$wa_habs}\n" .
                            "📅 *Salida:* {$wa_salida}\n" .
                            "💰 *Total:* {$wa_precio}\n\n" .
                            "Check-in: 3:00 PM · Check-out: 12:00 PM\n" .
                            "📍 Sistema de gestión hotelera\n\n" .
                            "¡Le esperamos! 🌿"
                        );

                        $msg_comprobante = urlencode(
                            "🧾 *Comprobante de Pago - {$nombreHotelVisible}*\n\n" .
                            "Hola {$wa_nombre}, gracias por su estancia.\n\n" .
                            "🏨 *Habitación(es):* {$wa_habs}\n" .
                            "📅 *Entrada:* {$wa_entrada}\n" .
                            "📅 *Salida:* {$wa_salida}\n" .
                            "🌙 *Noches:* {$wa_noches}\n" .
                            "💰 *Total pagado:* {$wa_precio}" . ($wa_metodo ? " ({$wa_metodo})" : "") . "\n" .
                            "🔖 *Reservación #:* {$wa_id}\n\n" .
                            "¡Fue un placer recibirle! Esperamos verle pronto. 🌿"
                        );
                        ?>
                        <a href="https://wa.me/<?= $wa_tel ?>?text=<?= $msg_confirmacion ?>"
                           target="_blank"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors border-b border-gray-50">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                  style="background:#E8F5E9;">
                                <i class="fas fa-calendar-check text-sm" style="color:#25D366;"></i>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Confirmación</div>
                                <div class="text-xs text-gray-400">Datos de la reservación</div>
                            </div>
                        </a>
                        <a href="https://wa.me/<?= $wa_tel ?>?text=<?= $msg_recordatorio ?>"
                           target="_blank"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors border-b border-gray-50">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                  style="background:#E8F5E9;">
                                <i class="fas fa-bell text-sm" style="color:#25D366;"></i>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Recordatorio de llegada</div>
                                <div class="text-xs text-gray-400">Para enviar un día antes</div>
                            </div>
                        </a>
                        <a href="https://wa.me/<?= $wa_tel ?>?text=<?= $msg_comprobante ?>"
                           target="_blank"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-green-50 transition-colors">
                            <span class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                                  style="background:#E8F5E9;">
                                <i class="fas fa-receipt text-sm" style="color:#25D366;"></i>
                            </span>
                            <div>
                                <div class="text-sm font-semibold text-gray-800">Comprobante de pago</div>
                                <div class="text-xs text-gray-400">Al finalizar la estancia</div>
                            </div>
                        </a>
                    </div>
                </div>
                <script>
                function toggleWAMenu() {
                    const menu = document.getElementById('wa-menu');
                    const button = document.querySelector('#wa-dropdown-wrapper > button');
                    menu.classList.toggle('hidden');
                    button?.classList.toggle('wa-menu-open', !menu.classList.contains('hidden'));
                }
                // Cerrar al hacer clic fuera
                document.addEventListener('click', function(e) {
                    const wrapper = document.getElementById('wa-dropdown-wrapper');
                    if (wrapper && !wrapper.contains(e.target)) {
                        document.getElementById('wa-menu')?.classList.add('hidden');
                        document.querySelector('#wa-dropdown-wrapper > button')?.classList.remove('wa-menu-open');
                    }
                });
                </script>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<!-- Contenido Principal -->
<div class="container mx-auto px-3 py-4">
    <!-- Alertas -->
    <!-- Alertas -->
<?php
// Calcular el tipo de alerta de check-in pendiente
$fecha_hoy = date('Y-m-d');
$puede_checkin = $reservacion['estado'] == 'confirmada' && $reservacion['fecha_entrada'] <= $fecha_hoy;
$es_express = $puede_checkin && $reservacion['fecha_salida'] <= $fecha_hoy;
$es_tardio = $puede_checkin && $reservacion['fecha_entrada'] < $fecha_hoy && !$es_express;

if ($puede_checkin): ?>
    <div class="alert alert-<?= ($es_tardio || $es_express) ? 'warning' : 'info' ?> mb-3">
        <i class="fas fa-<?= ($es_tardio || $es_express) ? 'exclamation-triangle' : 'info-circle' ?> text-lg"></i>
        <div>
            <?php if ($es_express): ?>
                <p class="font-semibold">Reservación vencida</p>
                <p class="text-xs mt-0.5 opacity-90">La fecha de salida ya llegó o pasó. Proceda con el proceso express.</p>
            <?php elseif ($es_tardio): ?>
                <p class="font-semibold">Reservación con check-in pendiente</p>
                <p class="text-xs mt-0.5 opacity-90">La fecha de entrada ya pasó. Proceda con el check-in tardío.</p>
            <?php else: ?>
                <p class="font-semibold">Check-in programado para hoy</p>
                <p class="text-xs mt-0.5 opacity-90">El huésped puede registrarse en cualquier momento.</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <?php if ($reservacion['estado'] == 'checked_in' && $reservacion['fecha_salida'] == date('Y-m-d')): ?>
        <div class="alert alert-warning mb-3">
            <i class="fas fa-exclamation-triangle text-lg"></i>
            <div>
                <p class="font-semibold">Check-out programado para hoy</p>
                <p class="text-xs mt-0.5 opacity-90">La salida está programada antes de las 12:00 PM.</p>
            </div>
        </div>
    <?php endif; ?>

    <section class="rd-reservation-hero-strip no-print" aria-label="Resumen rapido de la reservacion">
        <div class="rd-strip-item">
            <span>Estado</span>
            <strong><?= htmlspecialchars($estado_info['label']) ?></strong>
        </div>
        <div class="rd-strip-item">
            <span>Llegada</span>
            <strong><?= !empty($reservacion['fecha_entrada']) ? date('d/m/Y', strtotime($reservacion['fecha_entrada'])) : '-' ?></strong>
        </div>
        <div class="rd-strip-item">
            <span>Salida</span>
            <strong><?= !empty($reservacion['fecha_salida']) ? date('d/m/Y', strtotime($reservacion['fecha_salida'])) : '-' ?></strong>
        </div>
        <div class="rd-strip-item is-total">
            <span>Total</span>
            <strong><?= format_money($reservacion['precio_total'] ?? 0) ?></strong>
        </div>
    </section>

    <nav class="rd-quick-nav no-print" aria-label="Navegacion de la reservacion">
        <a href="#resumen-reservacion" data-rd-nav="resumen-reservacion" class="is-active">
            <i class="fas fa-receipt"></i>
            Resumen
        </a>
        <a href="#habitaciones-reservacion" data-rd-nav="habitaciones-reservacion">
            <i class="fas fa-bed"></i>
            Habitaciones
        </a>
        <a href="#huesped-reservacion" data-rd-nav="huesped-reservacion">
            <i class="fas fa-user"></i>
            Huesped
        </a>
    </nav>

    <!-- Grid Principal -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
        <!-- Columna Principal -->
        <div class="lg:col-span-2 space-y-3">
            <!-- Información Principal -->
            <div id="resumen-reservacion" class="info-card card-blue rd-section-card">
                <div class="card-header">
                    <div class="card-icon icon-blue">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h2 class="text-base font-bold text-gray-800">Información de Reservación</h2>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <div class="info-row">
                                <span class="info-label">Check-in</span>
                                <span class="info-value text-blue-600">
                                    <?= !empty($reservacion['fecha_entrada']) ? date('d/m/Y', strtotime($reservacion['fecha_entrada'])) : '-' ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Check-out</span>
                                <span class="info-value text-blue-600">
                                    <?= !empty($reservacion['fecha_salida']) ? date('d/m/Y', strtotime($reservacion['fecha_salida'])) : '-' ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Noches</span>
                                <span class="info-value">
                                    <?php
                                    $noches = 1;
                                    if (!empty($reservacion['fecha_entrada']) && !empty($reservacion['fecha_salida'])) {
                                        $entrada = new DateTime($reservacion['fecha_entrada']);
                                        $salida = new DateTime($reservacion['fecha_salida']);
                                        $noches = $entrada->diff($salida)->days ?: 1;
                                    }
                                    ?>
                                    <span class="text-purple-600"><?= $noches ?></span>
                                    <span class="text-xs text-gray-500 ml-1"><?= $noches == 1 ? 'noche' : 'noches' ?></span>
                                </span>
                            </div>
                        </div>
                        <div>
    <div class="precio-total mb-3">
        <?= format_money($reservacion['precio_total'] ?? 0) ?>
    </div>
    <?php if (!empty($reservacion['metodo_pago'])): ?>
        <div class="text-center">
            <span class="inline-flex items-center gap-2 px-3 py-1 bg-green-100 text-green-700 rounded-full font-semibold text-sm">
                <i class="fas fa-check-circle"></i>Pagado
            </span>

            <?php if (!empty($pagos) && count($pagos) > 1): ?>
                <!-- Mostrar múltiples métodos de pago -->
                <div class="mt-2 space-y-1">
                    <?php foreach ($pagos as $pago): ?>
                        <p class="text-xs text-gray-600">
                            <?= ucfirst(htmlspecialchars($pago['metodo_pago'])) ?>:
                            <span class="font-semibold"><?= format_money($pago['monto']) ?></span>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Método de pago único -->
                <p class="text-xs text-gray-600 mt-1">
                    vía <?= ucfirst(htmlspecialchars($reservacion['metodo_pago'])) ?>
                </p>
            <?php endif; ?>

            <!-- Botones: Imprimir Ticket y Cambiar Método de Pago -->
            <div class="mt-3 flex flex-wrap gap-2 justify-center no-print">
                <button onclick="imprimirTicketTermico()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-gray-700 to-gray-800 text-white rounded-lg text-xs font-semibold hover:from-gray-800 hover:to-gray-900 transition-all shadow-md hover:shadow-lg"
                        title="Imprimir ticket para impresora térmica">
                    <i class="fas fa-receipt"></i>
                    Imprimir Ticket
                </button>
                <button onclick="abrirModalCambiarPago()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-lg text-xs font-semibold hover:from-amber-600 hover:to-amber-700 transition-all shadow-md hover:shadow-lg"
                        title="Cambiar o modificar método de pago">
                    <i class="fas fa-exchange-alt"></i>
                    Cambiar Método de Pago
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>
                    </div>

                    <?php if (!empty($reservacion['notas'])): ?>
                        <div class="mt-3 p-3 bg-gradient-to-r from-amber-50 to-yellow-50 rounded-lg border border-amber-200">
                            <p class="text-sm text-amber-800">
                                <i class="fas fa-sticky-note mr-2 text-amber-600"></i>
                                <?= nl2br(htmlspecialchars($reservacion['notas'])) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Habitaciones -->
            <div id="habitaciones-reservacion" class="info-card card-purple rd-section-card">
                <div class="card-header">
                    <div class="card-icon icon-purple">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h2 class="text-base font-bold text-gray-800">Habitaciones Reservadas</h2>
                    <?php if (($reservacion['habitaciones_cortesia'] ?? 0) > 0): ?>
                        <span class="ml-auto text-xs bg-gradient-to-r from-green-500 to-green-600 text-white px-3 py-1 rounded-full font-bold shadow-md">
                            <i class="fas fa-gift mr-1"></i><?= $reservacion['habitaciones_cortesia'] ?> gratis
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php
                    $total_pagado_habitaciones = 0;
                    foreach ($habitaciones as $habitacion_precio) {
                        if (!empty($habitacion_precio['es_cortesia'])) {
                            continue;
                        }

                        if (isset($habitacion_precio['precio']) && is_numeric($habitacion_precio['precio'])) {
                            $total_pagado_habitaciones += (float) $habitacion_precio['precio'];
                        }
                    }
                    ?>
                    <div class="room-paid-summary">
                        <div>
                            <span>Pagado por habitaciones</span>
                            <strong><?= format_money($total_pagado_habitaciones) ?></strong>
                        </div>
                        <div>
                            <span>Total de la reservaci&oacute;n</span>
                            <strong><?= format_money($reservacion['precio_total'] ?? 0) ?></strong>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($habitaciones as $hab): ?>
                            <?php
                            $es_cortesia_habitacion = !empty($hab['es_cortesia']);
                            $precio_habitacion_pagado = (isset($hab['precio']) && is_numeric($hab['precio']))
                                ? (float) $hab['precio']
                                : 0;
                            ?>
                            <div class="room-card <?= $es_cortesia_habitacion ? 'cortesia' : '' ?>">
                                <div class="flex justify-between items-start">
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-gray-800">
                                            <i class="fas fa-door-open mr-1 text-purple-500"></i>
                                            Habitación <?= htmlspecialchars($hab['numero'] ?? '') ?>
                                        </h4>
                                        <p class="text-xs text-gray-600 mt-1">
                                            <?= htmlspecialchars($hab['tipo'] ?? '') ?> • Piso <?= htmlspecialchars($hab['piso'] ?? '') ?>
                                        </p>
                                    </div>
                                    <?php if ($es_cortesia_habitacion): ?>
                                        <span class="text-xs bg-gradient-to-r from-green-500 to-green-600 text-white px-2 py-1 rounded-full font-bold shadow">
                                            <i class="fas fa-gift mr-0.5"></i>Cortesía
                                        </span>
                                    <?php else: ?>

                                    <?php endif; ?>
                                </div>
                                <div class="room-price-panel">
                                    <span class="room-price-label">
                                        Precio que pag&oacute; el cliente
                                    </span>
                                    <span class="room-price-value <?= $es_cortesia_habitacion ? 'is-free' : '' ?>">
                                        <?= $es_cortesia_habitacion ? 'Cortes&iacute;a' : format_money($precio_habitacion_pagado) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Huésped -->
            <div id="huesped-reservacion" class="info-card card-green rd-section-card">
                <div class="card-header">
                    <div class="card-icon icon-green">
                        <i class="fas fa-user"></i>
                    </div>
                    <h2 class="text-base font-bold text-gray-800">Datos del Huésped</h2>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="info-row">
                            <span class="info-label">Nombre completo</span>
                            <span class="info-value"><?= htmlspecialchars($huesped['nombre_completo'] ?? 'No especificado') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value">
                                <i class="fas fa-phone text-xs text-green-500 mr-1"></i>
                                <?= htmlspecialchars($huesped['telefono'] ?: '-') ?>
                            </span>
                        </div>
                        <div class="info-row md:col-span-2">
                            <span class="info-label">Email</span>
                            <span class="info-value text-sm">
                                <i class="fas fa-envelope text-xs text-green-500 mr-1"></i>
                                <?= htmlspecialchars($huesped['email'] ?: '-') ?>
                            </span>
                        </div>
                        <div class="info-row md:col-span-2">
                            <span class="info-label">Procedencia</span>
                            <span class="info-value">
                                <i class="fas fa-map-marker-alt text-xs text-green-500 mr-1"></i>
                                <?= htmlspecialchars($huesped['procedencia_ciudad'] ?: '-') ?>,
                                <?= htmlspecialchars($huesped['procedencia_estado'] ?: '-') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Vehículos Section -->
                    <div class="mt-4 pt-4 border-t-2 border-gray-100">
                        <div class="flex justify-between items-center mb-3">
                            <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-car text-purple-500"></i>
                                Vehículos Registrados
                            </h3>
                            <button onclick="abrirModalAgregarVehiculo()"
                                    class="text-xs bg-purple-100 text-purple-700 hover:bg-purple-200 px-3 py-1 rounded-full font-semibold transition-all">
                                <i class="fas fa-plus-circle mr-1"></i>Agregar
                            </button>
                        </div>

                        <div id="listaVehiculos">
                            <div class="text-center py-4">
                                <i class="fas fa-spinner fa-spin text-2xl text-purple-500"></i>
                                <p class="text-sm text-gray-500 mt-2">Cargando vehículos...</p>
                            </div>
                        </div>
                    </div>

                    <!-- Agregar después de la sección de vehículos -->
<?php if ($reservacion['estado'] == 'checked_in'): ?>
<!-- Control de Llaves Section -->

<?php endif; ?>

<?php if ($reservacion['estado'] == 'checked_in'): ?>
<!-- Control de Remotos Section -->
<!-- ============================================================ -->
<!-- SECCIÓN ACTUALIZADA: Control de Remotos -->
<!-- Reemplazar desde la línea 1078 hasta la línea 1224 en ver.php -->
<!-- ============================================================ -->

<!-- Control de Remotos Section -->

<?php endif; ?>
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

// Funciones para control de remotos
function abrirModalEntregarRemoto(habitacionId, numeroHabitacion) {
    document.getElementById('entregar_remoto_habitacion_id').value = habitacionId;
    document.getElementById('entregar_remoto_habitacion_numero').textContent = numeroHabitacion;
    document.getElementById('modalEntregarRemoto').style.display = 'flex';
    document.getElementById('formEntregarRemoto').action = '<?= url("reservaciones/entregar-remoto") ?>';
}

function cerrarModalEntregarRemoto() {
    document.getElementById('modalEntregarRemoto').style.display = 'none';
    document.getElementById('formEntregarRemoto').reset();
}

function abrirModalRecibirRemoto(habitacionId, numeroHabitacion) {
    document.getElementById('recibir_remoto_habitacion_id').value = habitacionId;
    document.getElementById('recibir_remoto_habitacion_numero').textContent = numeroHabitacion;
    document.getElementById('modalRecibirRemoto').style.display = 'flex';
    document.getElementById('formRecibirRemoto').action = '<?= url("reservaciones/recibir-remoto") ?>';
}

function cerrarModalRecibirRemoto() {
    document.getElementById('modalRecibirRemoto').style.display = 'none';
    document.getElementById('formRecibirRemoto').reset();
}

function toggleEntregaRemotoManual(show) {
    const div = document.getElementById('entregaRemotoManualDiv');
    const input = div.querySelector('input[name="entregada_por_manual"]');

    if (show) {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function toggleRecepcionRemotoManual(show) {
    const div = document.getElementById('recepcionRemotoManualDiv');
    const input = div.querySelector('input[name="recibida_por_manual"]');

    if (show) {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function abrirModalEntregarRemotosMultiples() {
    // Desmarcar todos los checkboxes
    document.querySelectorAll('.remoto-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllRemotos').checked = false;

    document.getElementById('modalEntregarRemotosMultiples').style.display = 'flex';
}

function cerrarModalEntregarRemotosMultiples() {
    document.getElementById('modalEntregarRemotosMultiples').style.display = 'none';
    document.getElementById('formEntregarRemotosMultiples').reset();
}

function abrirModalRecibirRemotosMultiples() {
    // Desmarcar todos los checkboxes
    document.querySelectorAll('.remoto-recibir-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllRemotosRecibir').checked = false;

    document.getElementById('modalRecibirRemotosMultiples').style.display = 'flex';
}

function cerrarModalRecibirRemotosMultiples() {
    document.getElementById('modalRecibirRemotosMultiples').style.display = 'none';
    document.getElementById('formRecibirRemotosMultiples').reset();
}

function toggleSelectAllRemotos() {
    const selectAll = document.getElementById('selectAllRemotos');
    const checkboxes = document.querySelectorAll('.remoto-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function toggleSelectAllRemotosRecibir() {
    const selectAll = document.getElementById('selectAllRemotosRecibir');
    const checkboxes = document.querySelectorAll('.remoto-recibir-checkbox');

    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function toggleEntregaRemotosMultiplesManual(show) {
    const div = document.getElementById('entregaRemotosMultiplesManualDiv');
    const input = div.querySelector('input[name="entregada_por_manual"]');

    if (show) {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function toggleRecepcionRemotosMultiplesManual(show) {
    const div = document.getElementById('recepcionRemotosMultiplesManualDiv');
    const input = div.querySelector('input[name="recibida_por_manual"]');

    if (show) {
        div.style.display = 'block';
        input.required = true;
    } else {
        div.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

// Validación de formularios múltiples
document.getElementById('formEntregarRemotosMultiples')?.addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.remoto-checkbox:checked');

    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos una habitación');
        return false;
    }

    const nombrePropietario = this.querySelector('input[name="nombre_propietario_ine"]').value.trim();
    if (!nombrePropietario) {
        e.preventDefault();
        alert('Debe ingresar el nombre del propietario de la identificación');
        return false;
    }

    const tipoIdentificacion = this.querySelector('input[name="tipo_identificacion"]:checked');
    if (!tipoIdentificacion) {
        e.preventDefault();
        alert('Debe seleccionar el tipo de identificación');
        return false;
    }

    // Confirmar acción
    const cantidad = checkboxes.length;
    const mensaje = `¿Confirma entregar ${cantidad} control(es) remoto(s) a ${nombrePropietario}?`;

    if (!confirm(mensaje)) {
        e.preventDefault();
        return false;
    }
});

document.getElementById('formRecibirRemotosMultiples')?.addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('.remoto-recibir-checkbox:checked');

    if (checkboxes.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos una habitación');
        return false;
    }

    // Confirmar acción
    const cantidad = checkboxes.length;
    const mensaje = `¿Confirma recibir ${cantidad} control(es) remoto(s) del huésped?`;

    if (!confirm(mensaje)) {
        e.preventDefault();
        return false;
    }
});

// Cerrar modales al hacer clic fuera
window.addEventListener('click', function(e) {
    // Modal entregar individual
    if (e.target.id === 'modalEntregarRemoto') {
        cerrarModalEntregarRemoto();
    }

    // Modal recibir individual
    if (e.target.id === 'modalRecibirRemoto') {
        cerrarModalRecibirRemoto();
    }

    // Modal entregar múltiple
    if (e.target.id === 'modalEntregarRemotosMultiples') {
        cerrarModalEntregarRemotosMultiples();
    }

    // Modal recibir múltiple
    if (e.target.id === 'modalRecibirRemotosMultiples') {
        cerrarModalRecibirRemotosMultiples();
    }
});

// Cerrar modales con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalEntregarRemoto();
        cerrarModalRecibirRemoto();
        cerrarModalEntregarRemotosMultiples();
        cerrarModalRecibirRemotosMultiples();
    }
});

// Manejadores de formularios
document.getElementById('formEntregarRemoto').addEventListener('submit', function(e) {
    e.preventDefault();

    // Validar que se seleccionó tipo de identificación
    const tipoId = document.querySelector('input[name="tipo_identificacion"]:checked');
    if (!tipoId) {
        alert('Debe seleccionar el tipo de identificación');
        return;
    }

    this.action = '<?= url("reservaciones/entregar-remoto") ?>';
    this.submit();
});

document.getElementById('formRecibirRemoto').addEventListener('submit', function(e) {
    e.preventDefault();
    this.action = '<?= url("reservaciones/recibir-remoto") ?>';
    this.submit();
});

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
                    <div class="mt-4 flex justify-end">
                        <a href="/huespedes/<?= htmlspecialchars($huesped['id'] ?? '') ?>"
                           class="btn-action btn-primary">
                            <i class="fas fa-user-circle"></i>
                            Ver perfil completo
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Lateral -->
        <!-- Columna Lateral -->
<div class="space-y-3">
    <!-- Estado Actual -->
    <div class="info-card card-gold">
        <div class="card-body text-center py-4">
            <?php
            $estado_colors = [
                'confirmada' => 'blue',
                'checked_in' => 'green',
                'checked_out' => 'gray',
                'cancelada' => 'red'
            ];
            $color = $estado_colors[$reservacion['estado']] ?? 'gray';
            ?>
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-<?= $color ?>-100 to-<?= $color ?>-200 mb-3 shadow-lg">
                <i class="fas fa-<?= htmlspecialchars($estado_info['icon'] ?? 'circle') ?> text-2xl text-<?= $color ?>-600"></i>
            </div>
            <p class="text-lg font-bold text-<?= $color ?>-600">
                <?= htmlspecialchars($estado_info['label']) ?>
            </p>
            <?php if (!empty($reservacion['updated_at'])): ?>
                <p class="text-xs text-gray-500 mt-2">
                    Actualizado: <?= date('d/m H:i', strtotime($reservacion['updated_at'])) ?>
                </p>
            <?php endif; ?>

            <?php if ($reservacion['estado'] == 'checked_in' && !empty($reservacion['fecha_entrada']) && !empty($reservacion['hora_entrada'])): ?>
                <div class="mt-3 p-2 bg-green-50 rounded-lg">
                    <p class="text-xs text-green-700">
                        <i class="fas fa-clock mr-1"></i>
                        En el hotel:
                        <?php
                        $checkin = new DateTime($reservacion['fecha_entrada'] . ' ' . $reservacion['hora_entrada']);
                        $ahora = new DateTime();
                        $diff = $checkin->diff($ahora);
                        echo $diff->format('%d días, %h horas');
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Timeline -->
    <div class="info-card">
        <div class="card-header">
            <div class="card-icon icon-gold">
                <i class="fas fa-history"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800">Timeline</h3>
        </div>
        <div class="card-body">
            <div class="timeline-item">
                <div class="timeline-dot bg-blue-500"></div>
                <div class="ml-7">
                    <p class="font-semibold text-xs text-gray-800">Reservación creada</p>
                    <p class="text-xs text-gray-500">
                        <?= !empty($reservacion['created_at']) ? date('d/m/Y H:i', strtotime($reservacion['created_at'])) : '' ?>
                    </p>
                </div>
            </div>

            <?php if (!empty($reservacion['hora_entrada'])): ?>
                <div class="timeline-item">
                    <div class="timeline-dot bg-green-500"></div>
                    <div class="ml-7">
                        <p class="font-semibold text-xs text-gray-800">Check-in realizado</p>
                        <p class="text-xs text-gray-500">
                            <?= date('d/m/Y', strtotime($reservacion['fecha_entrada'])) ?>
                            a las <?= substr($reservacion['hora_entrada'], 0, 5) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($reservacion['estado'] == 'checked_out' && !empty($reservacion['hora_salida'])): ?>
                <div class="timeline-item">
                    <div class="timeline-dot bg-amber-500"></div>
                    <div class="ml-7">
                        <p class="font-semibold text-xs text-gray-800">Check-out completado</p>
                        <p class="text-xs text-gray-500">
                            <?= date('d/m/Y', strtotime($reservacion['fecha_salida'])) ?>
                            a las <?= substr($reservacion['hora_salida'], 0, 5) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($reservacion['estado'] == 'cancelada'): ?>
                <div class="timeline-item">
                    <div class="timeline-dot bg-red-500"></div>
                    <div class="ml-7">
                        <p class="font-semibold text-xs text-gray-800">Reservación cancelada</p>
                        <p class="text-xs text-gray-500">
                            <?= !empty($reservacion['updated_at']) ? date('d/m/Y H:i', strtotime($reservacion['updated_at'])) : '' ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información de Pago -->
    <?php if (!empty($reservacion['metodo_pago'])): ?>
        <div class="info-card card-green">
            <div class="card-header">
                <div class="card-icon icon-green">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-800">Información de Pago</h3>
            </div>
            <div class="card-body">
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-lg p-3 text-center border border-green-200">
                    <p class="text-xs text-green-700 mb-1 font-semibold">Total pagado</p>
                    <p class="text-2xl font-bold text-green-800"><?= format_money($reservacion['precio_total'] ?? 0) ?></p>

                    <?php if (!empty($pagos) && count($pagos) > 1): ?>
                        <!-- Pagos mixtos -->
                        <div class="mt-3 space-y-2">
                            <?php foreach ($pagos as $pago): ?>
                                <div class="inline-flex items-center gap-2 text-xs bg-white px-3 py-1 rounded-full border border-green-300 mr-2">
                                    <i class="fas fa-<?= $pago['metodo_pago'] == 'efectivo' ? 'money-bill-wave' : ($pago['metodo_pago'] == 'tarjeta' ? 'credit-card' : 'exchange-alt') ?> text-green-600"></i>
                                    <span class="font-semibold text-green-700">
                                        <?= ucfirst(htmlspecialchars($pago['metodo_pago'])) ?>:
                                        <?= format_money($pago['monto']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Pago único -->
                        <div class="mt-2 inline-flex items-center gap-2 text-xs bg-white px-3 py-1 rounded-full border border-green-300">
                            <i class="fas fa-<?= $reservacion['metodo_pago'] == 'efectivo' ? 'money-bill-wave' : ($reservacion['metodo_pago'] == 'tarjeta' ? 'credit-card' : 'exchange-alt') ?> text-green-600"></i>
                            <span class="font-semibold text-green-700"><?= ucfirst(htmlspecialchars($reservacion['metodo_pago'] ?? '')) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (($reservacion['cambio'] ?? 0) > 0): ?>
                    <div class="mt-3 p-2 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-xs text-blue-700">
                            <i class="fas fa-coins mr-1"></i>
                            Cambio entregado: <strong><?= format_money($reservacion['cambio']) ?></strong>
                        </p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pagos)): ?>
                    <!-- Detalles de pagos mixtos -->
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <h4 class="text-xs font-semibold text-gray-700 mb-2">Desglose de pagos:</h4>
                        <div class="space-y-1">
                            <?php foreach ($pagos as $pago): ?>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-600">
                                        <i class="fas fa-<?= $pago['metodo_pago'] == 'efectivo' ? 'money-bill-wave' : ($pago['metodo_pago'] == 'tarjeta' ? 'credit-card' : 'exchange-alt') ?> w-4"></i>
                                        <?= ucfirst($pago['metodo_pago']) ?>
                                        <?php if (!empty($pago['referencia'])): ?>
                                            <span class="text-gray-400">(<?= htmlspecialchars($pago['referencia']) ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="font-semibold text-gray-800"><?= format_money($pago['monto']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- SECCIÓN DE NOTAS RÁPIDAS (NUEVA) -->
    <div class="info-card card-gold">
        <div class="card-header">
            <div class="card-icon icon-gold">
                <i class="fas fa-sticky-note"></i>
            </div>
            <h3 class="text-sm font-bold text-gray-800">Notas Rápidas</h3>
            <?php if (isset($total_notas) && $total_notas > 0): ?>
                <span class="ml-auto text-xs bg-amber-500 text-white px-2 py-1 rounded-full"><?= $total_notas ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <!-- Formulario para agregar nota -->
            <div class="mb-3">
                <textarea id="nuevaNota"
                          placeholder="Agregar una nota rápida..."
                          class="w-full p-2 text-xs border rounded-lg resize-none"
                          rows="2"></textarea>
                <button onclick="agregarNota()"
                        class="btn-action btn-primary w-full mt-2 text-xs">
                    <i class="fas fa-plus"></i> Agregar Nota
                </button>
            </div>

            <!-- Lista de notas -->
            <div id="listaNotas" class="space-y-2 max-h-64 overflow-y-auto">
                <?php if (empty($notas)): ?>
                    <p class="text-center text-gray-500 text-xs py-3">No hay notas aún</p>
                <?php else: ?>
                    <?php foreach ($notas as $nota): ?>
                        <div class="nota-item bg-gradient-to-r from-amber-50 to-yellow-50 p-2 rounded-lg border border-amber-200">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-xs font-semibold text-amber-800">
                                    <?= htmlspecialchars($nota['usuario_nombre']) ?>
                                </span>
                                <span class="text-xs text-amber-600">
                                    <?= date('d/m H:i', strtotime($nota['created_at'])) ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-700 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($nota['nota'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Botones Móvil / Acciones Rápidas -->
    <div class="lg:hidden bg-white rounded-xl shadow-sm p-3 info-card no-print">
        <h3 class="text-sm font-bold text-gray-800 mb-3">Acciones Rápidas</h3>
        <div class="grid grid-cols-2 gap-2">
            <?php
// Detección para móvil
if ($reservacion['estado'] == 'confirmada'):
    $hoy = date('Y-m-d');
    $fecha_entrada = $reservacion['fecha_entrada'];
    $fecha_salida = $reservacion['fecha_salida'];

    $habitaciones_texto = implode(', ', array_column($habitaciones, 'numero'));
    $huesped_nombre_js = json_encode($huesped['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $habitaciones_texto_js = json_encode($habitaciones_texto, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $fecha_entrada_formato_js = json_encode(date('d/m/Y', strtotime($fecha_entrada)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $fecha_salida_formato_js = json_encode(date('d/m/Y', strtotime($fecha_salida)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    $huesped_nombre_js_attr = htmlspecialchars($huesped_nombre_js, ENT_QUOTES, 'UTF-8');
    $habitaciones_texto_js_attr = htmlspecialchars($habitaciones_texto_js, ENT_QUOTES, 'UTF-8');
    $fecha_entrada_formato_js_attr = htmlspecialchars($fecha_entrada_formato_js, ENT_QUOTES, 'UTF-8');
    $fecha_salida_formato_js_attr = htmlspecialchars($fecha_salida_formato_js, ENT_QUOTES, 'UTF-8');

    // Check-in normal
    if ($hoy == $fecha_entrada): ?>
        <button onclick="abrirModalCheckIn(<?= htmlspecialchars($reservacion['id']) ?>, <?= htmlspecialchars($reservacion['precio_total']) ?>)"
                class="btn-action btn-success text-xs justify-center col-span-2">
            <i class="fas fa-sign-in-alt"></i> Check-in
        </button>
    <?php
    // Check-in tardío
    elseif ($hoy > $fecha_entrada && $hoy < $fecha_salida):
        $dias = (strtotime($hoy) - strtotime($fecha_entrada)) / (60 * 60 * 24);
    ?>
        <button onclick="abrirModalCheckInTardio(<?= (int) $reservacion['id'] ?>, <?= $huesped_nombre_js_attr ?>, <?= $habitaciones_texto_js_attr ?>, <?= $fecha_entrada_formato_js_attr ?>, <?= $fecha_salida_formato_js_attr ?>, <?= (float) $reservacion['precio_total'] ?>, 'normal_tardio', <?= (int) $dias ?>)"
                class="btn-action bg-yellow-500 text-white hover:bg-yellow-600 text-xs justify-center col-span-2">
            <i class="fas fa-clock"></i> Check-in Tardío (<?= $dias ?> día<?= $dias > 1 ? 's' : '' ?>)
        </button>
    <?php
    // Proceso Express
    elseif ($hoy >= $fecha_salida):
        $dias = (strtotime($hoy) - strtotime($fecha_salida)) / (60 * 60 * 24);
    ?>
        <button onclick="abrirModalCheckInTardio(<?= (int) $reservacion['id'] ?>, <?= $huesped_nombre_js_attr ?>, <?= $habitaciones_texto_js_attr ?>, <?= $fecha_entrada_formato_js_attr ?>, <?= $fecha_salida_formato_js_attr ?>, <?= (float) $reservacion['precio_total'] ?>, 'express', <?= (int) $dias ?>)"
                class="btn-action bg-orange-600 text-white hover:bg-orange-700 text-xs justify-center col-span-2">
            <i class="fas fa-bolt"></i> Proceso Express (<?= $dias ?> día<?= $dias > 1 ? 's' : '' ?>)
        </button>
    <?php
    endif;
endif;
?>

            <?php if ($reservacion['estado'] == 'checked_in'): ?>
                <button onclick="confirmarCheckOut(<?= htmlspecialchars($reservacion['id']) ?>)"
                        class="btn-action btn-warning text-xs justify-center col-span-2">
                    <i class="fas fa-sign-out-alt"></i> Check-out
                </button>
            <?php endif; ?>
             <?php if ($reservacion['estado'] == 'confirmada'): ?>
                    <a href="<?= url('reservaciones/editar-habitaciones/' . $reservacion['id']) ?>"
                       class="btn-action bg-purple-500 text-white hover:bg-purple-600 text-xs justify-center">
                        <i class="fas fa-bed"></i>
                        Modificar Habitaciones
                    </a>
                <?php endif; ?>
            <?php if (in_array($reservacion['estado'], ['confirmada', 'checked_in'])): ?>
                <button onclick="abrirModalModificarDias()"
                        class="btn-action bg-indigo-500 text-white hover:bg-indigo-600 text-xs justify-center">
                    <i class="fas fa-calendar-alt"></i> Modificar Días
                </button>
            <?php endif; ?>
            <?php if (in_array($reservacion['estado'], ['confirmada', 'checked_in'])): ?>
                <button onclick="mostrarFormularioCancelacion()"
                        class="btn-action btn-danger text-xs justify-center">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            <?php endif; ?>

            <button onclick="abrirModalCotizacion()"
        class="btn-action btn-cotizacion-ver text-xs justify-center">
    <i class="fas fa-file-pdf"></i> Cotización
</button>
        </div>
    </div>
</div>
</div>


</div>

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
                                        onchange="calcularCambio()"
                                        onkeyup="calcularCambio()"
                                        placeholder="0.00"
                                        style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background: white;">
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

<div id="modalCheckInTardio" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="width: 420px; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="padding: 1rem; border-bottom: 2px solid #F3F4F6;">
            <h3 id="tituloModalTardio" style="font-size: 1.125rem; font-weight: 700; margin: 0; color: #1F2937;">
                <i class="fas fa-clock" style="color: #F59E0B; margin-right: 0.5rem;"></i>
                Check-in Tardío
            </h3>
            <button onclick="cerrarModalCheckInTardio()" style="background: none; border: none; cursor: pointer; color: #6B7280; font-size: 1.25rem;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Alerta de Advertencia -->
        <div id="alertaTardio" style="padding: 1rem; display: none;">
            <!-- Se llena dinámicamente -->
        </div>

        <div class="modal-body" style="padding: 1rem;">
            <form id="formCheckInTardio" method="POST" action="">
                <?= csrf_field() ?>
                <input type="hidden" name="tipo_tardio" id="tipo_tardio" value="">

                <!-- Info Reservación -->
                <div style="background: #F9FAFB; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem;">
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
                <div id="campo_hora_entrada" class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Hora de Check-in
                    </label>
                    <input type="time" name="hora_entrada" class="form-input" value="<?= date('H:i') ?>"
                           style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem;">
                </div>

                <!-- Total a Cobrar -->
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Total a Cobrar
                    </label>
                    <div style="padding: 0.75rem; background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border-radius: 0.5rem; text-align: center;">
                        <span id="totalACobrarTardio" style="font-weight: 800; font-size: 1.5rem; color: #92400E;">$0.00</span>
                    </div>
                </div>

                <!-- Métodos de Pago -->
                <div class="form-group">
                    <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
                        <i class="fas fa-wallet" style="color: #9333EA; margin-right: 0.375rem;"></i>
                        Métodos de Pago
                    </h4>
                    <p id="nota_pago_opcional" style="font-size: 0.75rem; color: #6B7280; margin-bottom: 0.5rem;">
                        💡 Opcional: Puede registrar el pago ahora o dejarlo pendiente.
                    </p>

                    <div id="metodosPagoContainerTardio" style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <!-- Sin Pago -->
                        <div class="metodo-pago-item" style="background: #F3F4F6; border: 2px solid #D1D5DB; border-radius: 0.5rem; padding: 0.75rem;">
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
                        <div class="metodo-pago-item" style="background: #F0FDF4; border: 2px solid #BBF7D0; border-radius: 0.5rem; padding: 0.75rem;">
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
                                            onchange="calcularCambioTardio()"
                                            onkeyup="calcularCambioTardio()"
                                            placeholder="0.00"
                                            style="width: 100%; padding: 0.375rem; font-size: 0.875rem; border: 1px solid #BBF7D0; border-radius: 0.375rem; background: white;">
                                    </div>
                                </div>
                                <div style="background-color: #D1FAE5; border-radius: 0.375rem; padding: 0.375rem 0.5rem; margin-top: 0.375rem; font-size: 0.75rem;">
                                    Cambio: <span id="cambio_efectivo_tardio" style="font-weight: 700; color: #065F46;">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta -->
                        <div class="metodo-pago-item" style="background: #EFF6FF; border: 2px solid #BFDBFE; border-radius: 0.5rem; padding: 0.75rem;">
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
        <label style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
                       background: white; border: 2px solid #BFDBFE; border-radius: 0.375rem;
                       cursor: pointer; font-size: 0.75rem; font-weight: 600; color: #1E40AF; transition: all 0.2s;"
               id="label_credito_tardio"
               onclick="this.style.borderColor='#3B82F6'; this.style.background='#DBEAFE'; document.getElementById('label_debito_tardio').style.borderColor='#BFDBFE'; document.getElementById('label_debito_tardio').style.background='white';">
            <input type="radio" name="tipo_tarjeta_tardio" value="credito" style="accent-color: #3B82F6;">
            <i class="fas fa-credit-card" style="font-size: 0.625rem;"></i> Crédito
        </label>
        <label style="flex: 1; display: flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.625rem;
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
                        <div class="metodo-pago-item" style="background: #F5F3FF; border: 2px solid #DDD6FE; border-radius: 0.5rem; padding: 0.75rem;">
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
<div style="margin-top: 1rem;">
    <h4 style="font-size: 0.875rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">
        <i class="fas fa-file-invoice" style="color: #2563EB; margin-right: 0.375rem;"></i>
        ¿El cliente requiere factura?
        <span style="color: #EF4444; font-size: 0.75rem;">*</span>
    </h4>

    <div id="facturaContainerTardio" style="display: flex; gap: 0.5rem;">
        <!-- Opción SÍ -->
        <label style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
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
        <label style="flex: 1; display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem;
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
    <div id="facturaValidacionTardio" class="hidden"
         style="margin-top: 0.375rem; padding: 0.375rem 0.5rem; background: #FEF2F2;
                border-radius: 0.375rem; border: 1px solid #FECACA;">
        <p style="font-size: 0.75rem; color: #DC2626; margin: 0;">
            <i class="fas fa-exclamation-circle" style="margin-right: 0.25rem;"></i>
            Debe indicar si el cliente requiere factura
        </p>
    </div>

    <!-- Info: se registrará para facturación interna -->
    <div id="facturaInfoInternaTardio" class="hidden"
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
                <div class="form-group" style="margin-top: 1rem;">
                    <label class="form-label" style="display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                        Notas Adicionales (opcional)
                    </label>
                    <textarea name="notas_adicionales" rows="2"
                              placeholder="Ej: El personal olvidó hacer el check-in"
                              style="width: 100%; padding: 0.5rem; border: 2px solid #E5E7EB; border-radius: 0.5rem; font-size: 0.875rem; resize: vertical;"></textarea>
                </div>

                <!-- Botones -->
                <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                    <button type="button" onclick="cerrarModalCheckInTardio()"
                            style="flex: 1; padding: 0.625rem; background: #F3F4F6; color: #374151; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btnConfirmarTardio"
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
        const ubicaciones = {
    'coches': { label: 'Coches', icon: 'fa-car', class: 'ubicacion-coches' },
    'camionetas': { label: 'Camionetas', icon: 'fa-truck', class: 'ubicacion-camionetas' },
    'discos': { label: 'Discos', icon: 'fa-compact-disc', class: 'ubicacion-discos' },
    'nikkos': { label: 'Nikkos', icon: 'fa-star', class: 'ubicacion-nikkos' }
};
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
function agregarNota() {
    const textarea = document.getElementById('nuevaNota');
    const nota = textarea.value.trim();

    if (!nota) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Nota vacía',
                text: 'Por favor escribe algo en la nota',
                timer: 2000
            });
        } else {
            alert('Por favor escribe algo en la nota');
        }
        return;
    }

    // Deshabilitar botón mientras se envía
    const btn = event.target;
    btn.disabled = true;

    const formData = new FormData();
    formData.append('reservacion_id', <?= $reservacion['id'] ?>);
    formData.append('nota', nota);
    formData.append('csrf_token', '<?= csrf_token() ?>');

    fetch('<?= url("reservaciones/agregar-nota") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpiar textarea
            textarea.value = '';

            // Actualizar lista de notas
            actualizarListaNotas();

            // Mostrar mensaje de éxito
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
        btn.disabled = false;
    });
}

function actualizarListaNotas() {
    fetch('<?= url("reservaciones/obtener-notas?reservacion_id=") ?><?= $reservacion['id'] ?>')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const container = document.getElementById('listaNotas');

            if (data.notas.length === 0) {
                container.innerHTML = '<p class="text-center text-gray-500 text-xs py-3">No hay notas aún</p>';
            } else {
                let html = '';
                data.notas.forEach(nota => {
                    const fecha = new Date(nota.created_at);
                    const fechaFormateada = fecha.toLocaleDateString('es-MX', {
                        day: '2-digit',
                        month: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    html += `
                        <div class="nota-item bg-gradient-to-r from-amber-50 to-yellow-50 p-2 rounded-lg border border-amber-200 animate-fadeIn">
                            <div class="flex items-start justify-between mb-1">
                                <span class="text-xs font-semibold text-amber-800">
                                    ${escapeHtml(nota.usuario_nombre)}
                                </span>
                                <span class="text-xs text-amber-600">
                                    ${fechaFormateada}
                                </span>
                            </div>
                            <p class="text-xs text-gray-700 whitespace-pre-wrap">${escapeHtml(nota.nota).replace(/\n/g, '<br>')}</p>
                        </div>
                    `;
                });
                container.innerHTML = html;
            }

            // Actualizar contador
            const badge = document.querySelector('.card-header .bg-amber-500');
            if (badge) {
                badge.textContent = data.notas.length;
            }
        }
    })
    .catch(error => {
        console.error('Error al actualizar notas:', error);
    });
}

function escapeHtml(text) {
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
document.getElementById('nuevaNota').addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        agregarNota();
    }
});

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
    document.getElementById('modalCheckInTardio').style.display = 'flex';
}

function cerrarModalCheckInTardio() {
    document.getElementById('modalCheckInTardio').style.display = 'none';
    resetearPagosTardio();
}

function toggleMetodoPagoTardio(metodo) {
    const checkbox = document.getElementById('check_' + metodo + (metodo === 'sin_pago' ? '' : '_tardio'));
    const panel = document.getElementById('panel_' + metodo + '_tardio');

    if (checkbox.checked) {
        // Si se selecciona un método de pago, desmarcar "sin pago"
        if (metodo !== 'sin_pago') {
            const sinPagoCheck = document.getElementById('check_sin_pago');
            if (sinPagoCheck) {
                sinPagoCheck.checked = false;
                metodosSeleccionadosTardio.delete('sin_pago');
            }
        } else {
            // Si se selecciona "sin pago", desmarcar todos los demás
            ['efectivo', 'tarjeta', 'transferencia'].forEach(m => {
                const check = document.getElementById('check_' + m + '_tardio');
                if (check) check.checked = false;
                const p = document.getElementById('panel_' + m + '_tardio');
                if (p) p.classList.add('hidden');
            });
            metodosSeleccionadosTardio = new Set(['sin_pago']);
        }

        metodosSeleccionadosTardio.add(metodo);
        if (panel) panel.classList.remove('hidden');

        if (metodo === 'efectivo') {
            calcularMontoEfectivoTardio();
        }
    } else {
        metodosSeleccionadosTardio.delete(metodo);
        if (panel) panel.classList.add('hidden');
    }

    mostrarResumenTardio();
    calcularTotalesTardio();
    // Actualizar aviso de factura interna
    const facturaNoTardio = document.getElementById('factura_no_tardio');
    if (facturaNoTardio && facturaNoTardio.checked) {
        mostrarInfoFacturaInternaTardio();
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

    const montoEfectivo = reservacionTardioData.total - totalOtros;
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
    // Desmarcar todos los checkboxes
    ['sin_pago', 'efectivo_tardio', 'tarjeta_tardio', 'transferencia_tardio'].forEach(id => {
        const check = document.getElementById('check_' + id);
        if (check) check.checked = false;
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

    document.getElementById('cambio_efectivo_tardio').textContent = '$0.00';
    document.getElementById('total_pagado_tardio').textContent = '$0.00';
    document.getElementById('pendiente_tardio').textContent = '$0.00';
    document.getElementById('resumen_totales_tardio').classList.add('hidden');

    // Marcar "sin pago" por defecto si no es express
    if (reservacionTardioData.tipo !== 'express') {
        const sinPago = document.getElementById('check_sin_pago');
        if (sinPago) sinPago.checked = true;
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
    letter-spacing: .05em;
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
    color: var(--brand-primary, #1B2746);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
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
    color: var(--brand-primary, #1B2746) !important;
    font-size: .72rem !important;
    font-weight: 900 !important;
    letter-spacing: .05em !important;
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

@media (max-width: 840px) {
    #modalCheckIn .rv-checkin-shell { grid-template-columns: 1fr; }
    #modalCheckIn .rv-checkin-hero {
        min-height: auto;
        padding: 18px 64px 18px 18px;
        gap: 12px;
    }
    #modalCheckIn .rv-checkin-hero::before,
    #modalCheckIn .rv-checkin-hero::after { display: none; }
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
    #modalCheckIn .rv-checkin-hero h3 i {
        width: 44px;
        height: 44px;
        border-radius: 15px;
    }
    #modalCheckIn .rv-checkin-hero h3::after {
        grid-column: 2;
        font-size: .76rem;
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
    #modalCheckIn .rv-checkin-shell {
        width: 100%;
        max-height: 96dvh;
        border-radius: 24px 24px 0 0;
    }
    #modalCheckIn .rv-checkin-form {
        grid-template-columns: 1fr;
        padding: 14px;
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
    #modalCheckIn .rv-checkin-actions {
        flex-direction: column;
        padding: 12px 14px 14px !important;
    }
    #modalCheckIn .rv-btn-cancel,
    #modalCheckIn .rv-btn-confirm { width: 100%; }
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
