<?php
/**
 * Vista de detalle de huesped.
 * Rediseño operativo tipo expediente del huésped.
 */

$huesped = $huesped ?? [];
$vehiculos = $vehiculos ?? [];
$reservaciones = $reservaciones ?? [];
$total_reservaciones = (int)($total_reservaciones ?? 0);
$total_gastado = (float)($total_gastado ?? 0);
$gasto_promedio = $total_reservaciones > 0 ? $total_gastado / $total_reservaciones : 0;
$vehiculos_count = count($vehiculos);
$reservaciones_count = count($reservaciones);
$nombre_huesped = trim((string)($huesped['nombre_completo'] ?? 'Huesped'));
$inicial_huesped = function_exists('mb_substr') && function_exists('mb_strtoupper')
    ? mb_strtoupper(mb_substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1, 'UTF-8'), 'UTF-8')
    : strtoupper(substr($nombre_huesped !== '' ? $nombre_huesped : 'H', 0, 1));
$procedencia_estado = trim((string)($huesped['procedencia_estado'] ?? ''));
$procedencia_ciudad = trim((string)($huesped['procedencia_ciudad'] ?? ''));
$procedencia_label = $procedencia_estado !== '' ? $procedencia_estado : 'No especificada';
if ($procedencia_ciudad !== '') {
    $procedencia_label .= ' · ' . $procedencia_ciudad;
}
$telefono = trim((string)($huesped['telefono'] ?? ''));
$email = trim((string)($huesped['email'] ?? ''));
$contacto_label = $telefono !== '' || $email !== '' ? 'Contacto disponible' : 'Contacto incompleto';
$ultima_visita_label = $ultima_visita ? format_date($ultima_visita) : 'Sin visitas completadas';
$es_cliente_frecuente = $total_reservaciones >= 3;
$estacionamientos = [];
if (function_exists('hotel_general_catalog_parking_rows')) {
    foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
        $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
        $parkingLabel = trim((string)($parkingRow['label'] ?? ''));
        if ($parkingCode !== '' && $parkingLabel !== '') {
            $estacionamientos[$parkingCode] = $parkingLabel;
        }
    }
}
if (empty($estacionamientos) && class_exists('HuespedVehiculo')) {
    $estacionamientos = HuespedVehiculo::getEstacionamientos();
}
if (empty($estacionamientos)) {
    $estacionamientos = ['coches' => 'Coches'];
}
reset($estacionamientos);
$estacionamientoDefault = (string)key($estacionamientos);

if (!function_exists('guest_detail_safe')) {
    function guest_detail_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('guest_detail_status')) {
    function guest_detail_status($estado) {
        $map = [
            'confirmada' => ['label' => 'Confirmada', 'color' => '#2563EB', 'soft' => '#EEF4FF'],
            'checked_in' => ['label' => 'Check-in', 'color' => '#148653', 'soft' => '#E8F7EF'],
            'checked_out' => ['label' => 'Check-out', 'color' => '#64748B', 'soft' => '#F1F5F9'],
            'completada' => ['label' => 'Completada', 'color' => '#148653', 'soft' => '#E8F7EF'],
            'cancelada' => ['label' => 'Cancelada', 'color' => '#B83D35', 'soft' => '#FFF0EF'],
        ];
        return $map[$estado] ?? ['label' => 'Sin estado', 'color' => '#64748B', 'soft' => '#F1F5F9'];
    }
}

if (!function_exists('guest_detail_json_attr')) {
    function guest_detail_json_attr($value) {
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8');
    }
}

$guestParkingOptionsHtml = function ($inputClass = '') use ($estacionamientos, $estacionamientoDefault) {
    $html = '';
    foreach ($estacionamientos as $parkingCode => $parkingLabel) {
        $parkingCode = (string)$parkingCode;
        $parkingCodeEsc = htmlspecialchars($parkingCode, ENT_QUOTES, 'UTF-8');
        $parkingLabelEsc = htmlspecialchars((string)$parkingLabel, ENT_QUOTES, 'UTF-8');
        $classAttr = trim((string)$inputClass);
        $classHtml = $classAttr !== '' ? ' class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"' : '';
        $checkedAttr = $parkingCode === $estacionamientoDefault ? ' checked' : '';
        $html .= '<label class="guest-radio-card">';
        $html .= '<input type="radio" name="estacionamiento" value="' . $parkingCodeEsc . '"' . $classHtml . $checkedAttr . '>';
        $html .= '<span><i class="fas fa-car"></i>' . $parkingLabelEsc . '</span>';
        $html .= '</label>';
    }

    return $html;
};
?>

<style>
.guest-detail-view {
    --gd-primary: var(--brand-primary, #1B2746);
    --gd-secondary: var(--brand-secondary, #0F172A);
    --gd-accent: var(--brand-accent, #BD9441);
    --gd-bg: color-mix(in srgb, var(--gd-accent) 9%, #F7F2EA);
    --gd-surface: rgba(255, 253, 248, .92);
    --gd-surface-solid: #FFFDF8;
    --gd-line: color-mix(in srgb, var(--gd-primary) 13%, #E9DDCF);
    --gd-line-soft: color-mix(in srgb, var(--gd-primary) 8%, #EFE6DA);
    --gd-heading: #111827;
    --gd-text: #172033;
    --gd-text-soft: #334155;
    --gd-muted: #728096;
    --gd-accent-readable: color-mix(in srgb, var(--gd-accent) 66%, #3B2D12);
    --gd-success: #148653;
    --gd-danger: #B83D35;
    min-height: 100vh;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--gd-primary) 5%, transparent) 0 1px, transparent 1px 30px),
        radial-gradient(circle at 88% 7%, color-mix(in srgb, var(--gd-accent) 22%, transparent), transparent 30rem),
        linear-gradient(180deg, var(--gd-bg), #FBFAF6 48%, #F3ECE2);
    color: var(--gd-text);
}

.guest-detail-shell {
    width: min(1500px, calc(100% - 30px));
    margin: 0 auto;
    padding: 26px 0 46px;
}

.guest-breadcrumb {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 12px;
    color: var(--gd-muted);
    font-size: .84rem;
    font-weight: 800;
}

.guest-breadcrumb a {
    color: var(--gd-text-soft);
}

.guest-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(270px, 360px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 16px;
}

.guest-identity-card {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 18px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 26%, transparent);
    border-radius: 26px;
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--gd-accent) 26%, transparent), transparent 18rem),
        linear-gradient(135deg, rgba(255,253,248,.97), rgba(248,241,230,.92));
    box-shadow: 0 26px 72px -52px rgba(15, 23, 42, .68);
    padding: clamp(20px, 3vw, 32px);
}

.guest-identity-card::after {
    content: "";
    position: absolute;
    inset: auto 24px 0 auto;
    width: 220px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--gd-accent), var(--gd-primary));
    opacity: .72;
}

.guest-avatar {
    width: clamp(76px, 10vw, 118px);
    aspect-ratio: 1;
    display: grid;
    place-items: center;
    border-radius: 24px;
    background:
        linear-gradient(145deg, var(--gd-primary), color-mix(in srgb, var(--gd-primary) 78%, #000000));
    color: #FFFDF8;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.1rem, 5vw, 4.4rem);
    font-weight: 800;
    box-shadow: 0 20px 44px -28px rgba(15, 23, 42, .72);
}

.guest-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gd-accent-readable);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.guest-identity-card h1 {
    margin: 9px 0 8px;
    color: var(--gd-heading);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.25rem, 5vw, 5rem);
    line-height: .91;
    font-weight: 800;
    letter-spacing: 0;
    text-wrap: balance;
}

.guest-identity-card p {
    max-width: 68ch;
    margin: 0;
    color: var(--gd-muted);
    font-weight: 700;
    line-height: 1.55;
}

.guest-hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 16px;
}

.guest-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 18%, transparent);
    border-radius: 12px;
    background: rgba(255,255,255,.68);
    color: var(--gd-text-soft);
    padding: 8px 10px;
    font-size: .8rem;
    font-weight: 900;
}

.guest-action-rail {
    display: grid;
    gap: 10px;
    align-content: center;
    border: 1px solid var(--gd-line);
    border-radius: 22px;
    background: rgba(255,253,248,.88);
    box-shadow: 0 20px 54px -42px rgba(15, 23, 42, .58);
    padding: 16px;
}

.guest-action {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    border: 1px solid var(--gd-line);
    border-radius: 14px;
    background: #FFFFFF;
    color: var(--gd-text);
    padding: 0 14px;
    font-weight: 900;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.guest-action i:last-child {
    color: color-mix(in srgb, var(--gd-accent) 56%, #FFFFFF);
}

.guest-action:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
    border-color: color-mix(in srgb, var(--gd-accent) 36%, var(--gd-line));
}

.guest-action.primary {
    border-color: transparent;
    background: linear-gradient(135deg, var(--gd-primary), color-mix(in srgb, var(--gd-primary) 78%, #000000));
    color: #FFFFFF;
}

.guest-action.primary i:last-child {
    color: rgba(255,255,255,.7);
}

.guest-metrics-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.guest-metric {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--gd-line);
    border-radius: 18px;
    background: #FFFFFF;
    padding: 16px;
    box-shadow: 0 14px 34px -30px rgba(15,23,42,.46);
}

.guest-metric::after {
    content: "";
    position: absolute;
    inset: auto 16px 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--metric-color, var(--gd-accent));
    opacity: .78;
}

.guest-metric span,
.guest-section-label,
.guest-info-label,
.guest-system-list span {
    display: block;
    color: var(--gd-muted);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.guest-metric strong {
    display: block;
    margin-top: 8px;
    color: var(--gd-heading);
    font-size: clamp(1.45rem, 2.6vw, 2.25rem);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.guest-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(280px, 350px);
    gap: 16px;
    align-items: start;
}

.guest-main-stack,
.guest-side-stack {
    display: grid;
    gap: 16px;
}

.guest-side-stack {
    position: sticky;
    top: 18px;
}

.guest-panel {
    border: 1px solid var(--gd-line);
    border-radius: 22px;
    background: var(--gd-surface);
    box-shadow: 0 18px 48px -38px rgba(15,23,42,.45);
    overflow: hidden;
}

.guest-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--gd-line-soft);
    background: linear-gradient(90deg, color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF), #FFFFFF);
}

.guest-panel-head h2,
.guest-side-card h2 {
    margin: 0;
    color: var(--gd-heading);
    font-size: 1.04rem;
    font-weight: 950;
}

.guest-panel-head p {
    margin: 3px 0 0;
    color: var(--gd-muted);
    font-size: .82rem;
    font-weight: 700;
}

.guest-panel-body {
    padding: 18px;
}

.guest-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.guest-info-card {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    border: 1px solid var(--gd-line-soft);
    border-radius: 16px;
    background: #FFFFFF;
    padding: 14px;
}

.guest-info-card i {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--gd-accent) 12%, #FFFFFF);
    color: var(--gd-accent-readable);
}

.guest-info-card strong {
    display: block;
    margin-top: 4px;
    color: var(--gd-text);
    font-size: .96rem;
    font-weight: 900;
    overflow-wrap: anywhere;
}

.guest-note {
    margin-top: 12px;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 24%, transparent);
    border-radius: 16px;
    background: color-mix(in srgb, var(--gd-accent) 8%, #FFFFFF);
    padding: 14px;
}

.guest-note strong {
    display: block;
    color: var(--gd-heading);
    margin-bottom: 6px;
}

.guest-note p {
    margin: 0;
    color: var(--gd-muted);
    line-height: 1.6;
}

.guest-panel-action {
    min-height: 44px;
    border: 0;
    border-radius: 13px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: var(--gd-primary);
    color: #FFFFFF;
    padding: 0 18px;
    font-weight: 900;
    cursor: pointer;
    transition: transform .18s ease, box-shadow .18s ease;
}

.guest-panel-action i {
    flex: 0 0 auto;
    width: auto;
    height: auto;
    margin: 0;
    display: inline-flex;
    place-items: initial;
    border-radius: 0;
    background: transparent;
    color: currentColor;
    font-size: .96rem;
}

.guest-panel-action:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px -18px rgba(15,23,42,.58);
}

.guest-vehicle-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 360px), 1fr));
    gap: 12px;
}

.guest-vehicle-card {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(220px, .8fr);
    gap: 14px;
    align-items: stretch;
    border: 1px solid color-mix(in srgb, var(--gd-primary) 12%, var(--gd-line-soft));
    border-radius: 18px;
    background:
        linear-gradient(135deg, #FFFFFF, color-mix(in srgb, var(--gd-accent) 4%, #FFFFFF));
    padding: 15px;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.guest-vehicle-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--gd-accent) 36%, var(--gd-line));
    box-shadow: 0 18px 34px -30px rgba(15,23,42,.48);
}

.guest-vehicle-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
}

.guest-vehicle-title {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.guest-vehicle-icon {
    width: 48px;
    height: 48px;
    flex: none;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--gd-accent) 10%, #FFFFFF);
    color: var(--gd-accent-readable);
}

.guest-vehicle-title strong {
    display: block;
    color: var(--gd-text);
    font-weight: 950;
    line-height: 1.15;
    overflow-wrap: anywhere;
}

.guest-vehicle-title span {
    width: fit-content;
    display: inline-flex;
    margin-top: 7px;
    border: 1px solid var(--gd-line-soft);
    border-radius: 9px;
    background: color-mix(in srgb, var(--gd-primary) 5%, #FFFFFF);
    color: var(--gd-muted);
    padding: 4px 8px;
    font-size: .78rem;
    font-weight: 850;
    font-variant-numeric: tabular-nums;
}

.guest-mini-actions {
    display: flex;
    gap: 7px;
    flex: none;
}

.guest-icon-btn {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border: 1px solid var(--gd-line-soft);
    border-radius: 11px;
    background: #FFFFFF;
    color: var(--gd-text-soft);
    cursor: pointer;
    transition: transform .18s ease, background .18s ease;
}

.guest-icon-btn:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF);
}

.guest-icon-btn.danger {
    color: var(--gd-danger);
}

.guest-vehicle-details {
    display: grid;
    gap: 9px;
    margin-top: 0;
    padding: 12px;
    border: 1px solid var(--gd-line-soft);
    border-radius: 15px;
    background: rgba(255,255,255,.72);
}

.guest-detail-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    color: var(--gd-muted);
    font-size: .83rem;
    font-weight: 750;
}

.guest-detail-row strong {
    color: var(--gd-text);
    font-weight: 900;
    text-align: right;
    overflow-wrap: anywhere;
}

.guest-parking-badge,
.guest-status-badge {
    width: fit-content;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 12px;
    padding: 6px 9px;
    font-size: .76rem;
    font-weight: 900;
}

.guest-parking-badge {
    background: color-mix(in srgb, var(--gd-accent) 12%, #FFFFFF);
    color: var(--gd-text-soft);
}

.guest-empty {
    border: 1px dashed color-mix(in srgb, var(--gd-primary) 18%, transparent);
    border-radius: 18px;
    background: rgba(255,255,255,.62);
    padding: 28px 18px;
    text-align: center;
    color: var(--gd-muted);
}

.guest-empty > i {
    width: 60px;
    height: 60px;
    display: inline-grid;
    place-items: center;
    border-radius: 18px;
    background: color-mix(in srgb, var(--gd-accent) 9%, #FFFFFF);
    color: var(--gd-accent-readable);
    font-size: 1.45rem;
    margin-bottom: 12px;
}

.guest-empty strong {
    display: block;
    color: var(--gd-heading);
    font-size: 1.05rem;
    margin-bottom: 5px;
}

.guest-history-empty {
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr) auto;
    gap: 16px;
    align-items: center;
    padding: 22px;
    text-align: left;
}

.guest-history-empty i {
    grid-column: 1;
    grid-row: 1 / span 2;
    margin: 0;
}

.guest-history-empty strong {
    grid-column: 2;
    grid-row: 1;
    margin-bottom: 4px;
}

.guest-history-empty p {
    grid-column: 2;
    grid-row: 2;
    margin: 0;
    max-width: 54ch;
}

.guest-history-empty .guest-panel-action {
    grid-column: 3;
    grid-row: 1 / span 2;
    justify-self: center;
    min-width: min(100%, 260px);
    width: max-content;
    white-space: nowrap;
}

.guest-reservation-list {
    display: grid;
    gap: 10px;
}

.guest-reservation-card {
    display: grid;
    grid-template-columns: minmax(230px, 1.3fr) repeat(3, minmax(120px, .72fr)) auto;
    gap: 12px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--status-color) 22%, var(--gd-line));
    border-radius: 17px;
    background: #FFFFFF;
    box-shadow: inset 4px 0 0 var(--status-color);
    padding: 14px;
}

.guest-reservation-main strong {
    display: block;
    color: var(--gd-text);
    font-weight: 950;
    overflow-wrap: anywhere;
}

.guest-reservation-main span,
.guest-reservation-meta span {
    display: block;
    margin-top: 4px;
    color: var(--gd-muted);
    font-size: .8rem;
    font-weight: 750;
}

.guest-reservation-meta strong {
    display: block;
    color: var(--gd-heading);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.guest-status-badge {
    background: var(--status-soft);
    color: var(--status-color);
}

.guest-reservation-link {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 12px;
    background: var(--gd-primary);
    color: #FFFFFF;
    padding: 0 11px;
    font-size: .82rem;
    font-weight: 900;
}

.guest-side-card {
    border: 1px solid var(--gd-line);
    border-radius: 20px;
    background: var(--gd-surface);
    padding: 18px;
    box-shadow: 0 18px 48px -38px rgba(15,23,42,.45);
}

.guest-score-card {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--gd-accent) 28%, transparent), transparent 14rem),
        linear-gradient(145deg, var(--gd-primary), var(--gd-secondary));
    color: #FFFFFF;
}

.guest-score-card h2 {
    color: #FFFFFF;
}

.guest-score-number {
    margin: 15px 0 5px;
    font-size: clamp(2.1rem, 4vw, 3.2rem);
    font-weight: 950;
    line-height: 1;
}

.guest-score-card p {
    margin: 0;
    color: rgba(255,255,255,.7);
    font-weight: 700;
}

.guest-score-list {
    display: grid;
    gap: 10px;
    margin-top: 18px;
    padding-top: 16px;
    border-top: 1px solid rgba(255,255,255,.16);
}

.guest-score-list div,
.guest-system-list li {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: center;
}

.guest-score-list span {
    color: rgba(255,255,255,.64);
    font-size: .82rem;
    font-weight: 750;
}

.guest-score-list strong {
    color: #FFFFFF;
    font-weight: 950;
    text-align: right;
}

.guest-quick-actions {
    display: grid;
    gap: 9px;
    margin-top: 14px;
}

.guest-side-action {
    min-height: 42px;
    border: 1px solid var(--gd-line);
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #FFFFFF;
    color: var(--gd-text);
    padding: 0 12px;
    font-weight: 900;
}

.guest-side-action:hover {
    background: color-mix(in srgb, var(--gd-accent) 7%, #FFFFFF);
}

.guest-system-list {
    display: grid;
    gap: 10px;
    margin: 14px 0 0;
    padding: 0;
    list-style: none;
}

.guest-system-list strong {
    color: var(--gd-text);
    font-weight: 900;
    text-align: right;
}

.guest-modal {
    --gd-primary: color-mix(in srgb, var(--brand-primary, #1B2746) 34%, #243044);
    --gd-secondary: #121A2A;
    --gd-accent: color-mix(in srgb, var(--brand-accent, #BD9441) 62%, #B98B3E);
    --gd-line: rgba(67, 78, 94, .18);
    --gd-line-soft: rgba(67, 78, 94, .1);
    --gd-text: #182032;
    --gd-muted: #6B7586;
    position: fixed;
    inset: 0;
    z-index: 13000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background:
        radial-gradient(circle at 50% 15%, color-mix(in srgb, var(--gd-accent) 20%, transparent), transparent 24rem),
        rgba(10, 15, 25, .76);
    -webkit-backdrop-filter: blur(12px) saturate(1.05);
    backdrop-filter: blur(12px) saturate(1.05);
}

.guest-modal.hidden {
    display: none !important;
}

.guest-modal-card {
    width: min(560px, 100%);
    max-height: 92dvh;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--gd-accent) 34%, rgba(255,255,255,.16));
    border-radius: 24px;
    background:
        linear-gradient(180deg, #FFF8ED 0%, #F5ECDD 100%);
    box-shadow:
        0 34px 90px -36px rgba(0,0,0,.9),
        inset 0 1px 0 rgba(255,255,255,.86);
}

.guest-modal-head {
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 20px 22px;
    color: #FFFFFF;
    background:
        radial-gradient(circle at 86% 0%, color-mix(in srgb, var(--gd-accent) 36%, transparent), transparent 12rem),
        linear-gradient(135deg, #182238 0%, var(--gd-primary) 48%, #111827 100%);
}

.guest-modal-head::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--gd-accent) 72%, #FFFFFF), transparent);
}

.guest-modal-head h3 {
    position: relative;
    z-index: 1;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.08rem;
    font-weight: 950;
}

.guest-modal-head h3 i {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 13px;
    background: rgba(255,255,255,.12);
    color: color-mix(in srgb, var(--gd-accent) 72%, #FFFFFF);
}

.guest-modal-close {
    position: relative;
    z-index: 1;
    width: 36px;
    height: 36px;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 12px;
    background: rgba(255,255,255,.13);
    color: #FFFFFF;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease;
}

.guest-modal-close:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.22);
}

.guest-vehicle-form {
    display: grid;
    gap: 14px;
    padding: 18px;
    max-height: calc(92dvh - 74px);
    overflow-y: auto;
    background:
        radial-gradient(circle at 9% 8%, rgba(189, 148, 65, .14), transparent 13rem),
        linear-gradient(180deg, #FFF8ED, #F5ECDD);
}

.guest-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.guest-form-field,
.guest-radio-group {
    border: 1px solid var(--gd-line-soft);
    border-radius: 16px;
    background: rgba(255,255,255,.54);
    padding: 12px;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.64);
}

.guest-form-field label,
.guest-radio-group > label {
    display: block;
    margin-bottom: 7px;
    color: #263246;
    font-size: .74rem;
    font-weight: 950;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.guest-form-field input {
    width: 100%;
    min-height: 43px;
    border: 1px solid rgba(44, 55, 75, .24);
    border-radius: 12px;
    background: rgba(255,255,255,.96);
    color: var(--gd-text);
    outline: none;
    padding: 9px 12px;
    font-weight: 750;
    transition: border-color .18s ease, box-shadow .18s ease;
    box-shadow: 0 1px 0 rgba(255,255,255,.75);
}

.guest-form-field input:focus {
    border-color: color-mix(in srgb, var(--gd-accent) 68%, var(--gd-primary));
    box-shadow:
        0 0 0 4px color-mix(in srgb, var(--gd-accent) 18%, transparent),
        0 10px 22px rgba(28, 35, 49, .08);
}

.guest-radio-options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.guest-radio-card {
    cursor: pointer;
}

.guest-radio-card input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.guest-radio-card span {
    min-height: 58px;
    display: grid;
    place-items: center;
    gap: 5px;
    border: 1px solid rgba(44, 55, 75, .2);
    border-radius: 14px;
    background: rgba(255,255,255,.8);
    color: #263246;
    font-size: .82rem;
    font-weight: 900;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.guest-radio-card span:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--gd-accent) 42%, rgba(44,55,75,.2));
    background: rgba(255,255,255,.95);
}

.guest-radio-card input:checked + span {
    border-color: color-mix(in srgb, var(--gd-accent) 72%, var(--gd-primary));
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--gd-accent) 18%, #FFFFFF), rgba(255,255,255,.92));
    box-shadow: 0 12px 24px rgba(55, 43, 25, .12);
}

.guest-radio-card input:checked + span i {
    color: color-mix(in srgb, var(--gd-accent) 74%, var(--gd-primary));
}

.guest-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin: 2px -18px -18px;
    padding: 14px 18px 18px;
    border-top: 1px solid rgba(44, 55, 75, .12);
    background: rgba(255,255,255,.46);
}

.guest-cancel,
.guest-save {
    min-height: 42px;
    border-radius: 12px;
    padding: 0 15px;
    font-weight: 900;
    cursor: pointer;
}

.guest-cancel {
    border: 1px solid rgba(44, 55, 75, .18);
    background: rgba(255,255,255,.78);
    color: #263246;
}

.guest-save {
    border: 0;
    background: linear-gradient(135deg, #263246, var(--gd-primary));
    color: #FFFFFF;
    box-shadow: 0 14px 26px rgba(28, 35, 49, .22);
}

.guest-cancel:hover,
.guest-save:hover {
    transform: translateY(-1px);
}

@media (max-width: 1180px) {
    .guest-layout {
        grid-template-columns: 1fr;
    }

    .guest-side-stack {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 900px) {
    .guest-hero,
    .guest-identity-card {
        grid-template-columns: 1fr;
    }

    .guest-action-rail,
    .guest-side-stack,
    .guest-metrics-strip,
    .guest-info-grid {
        grid-template-columns: 1fr;
    }

    .guest-reservation-card {
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .guest-vehicle-card {
        grid-template-columns: 1fr;
    }

    .guest-vehicle-details {
        margin-top: 0;
    }
}

@media (max-width: 620px) {
    .guest-detail-shell {
        width: min(100% - 18px, 1500px);
        padding-top: 14px;
    }

    .guest-identity-card,
    .guest-action-rail,
    .guest-panel,
    .guest-side-card {
        border-radius: 18px;
    }

    .guest-avatar {
        width: 76px;
        border-radius: 18px;
    }

    .guest-identity-card h1 {
        font-size: clamp(2.1rem, 13vw, 3.1rem);
    }

    .guest-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .guest-panel-action,
    .guest-action,
    .guest-side-action {
        width: 100%;
    }

    .guest-vehicle-top {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
    }

    .guest-vehicle-title {
        align-items: flex-start;
    }

    .guest-history-empty {
        grid-template-columns: 1fr;
        text-align: center;
        justify-items: center;
    }

    .guest-history-empty > i,
    .guest-history-empty strong,
    .guest-history-empty p,
    .guest-history-empty .guest-panel-action {
        grid-column: auto;
        grid-row: auto;
    }

    .guest-history-empty .guest-panel-action {
        width: 100%;
        margin-top: 8px;
    }

    .guest-form-grid,
    .guest-radio-options {
        grid-template-columns: 1fr;
    }

    .guest-modal-actions {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>

<div class="guest-detail-view">
    <div class="guest-detail-shell">
        <nav class="guest-breadcrumb" aria-label="Ruta de navegacion">
            <a href="<?= url('huespedes') ?>">Huespedes</a>
            <i class="fas fa-chevron-right"></i>
            <span><?= guest_detail_safe($nombre_huesped) ?></span>
        </nav>

        <header class="guest-hero">
            <section class="guest-identity-card">
                <div class="guest-avatar" aria-hidden="true"><?= guest_detail_safe($inicial_huesped) ?></div>
                <div>
                    <div class="guest-kicker">
                        <i class="fas fa-id-badge"></i>
                        Expediente de huésped
                    </div>
                    <h1><?= guest_detail_safe($nombre_huesped) ?></h1>
                    <p>
                        Perfil operativo para revisar contacto, procedencia, vehículos, historial de reservaciones y acciones rápidas del huésped.
                    </p>
                    <div class="guest-hero-meta">
                        <span class="guest-chip">
                            <i class="fas fa-hashtag"></i>
                            ID <?= (int)($huesped['id'] ?? 0) ?>
                        </span>
                        <span class="guest-chip">
                            <i class="fas fa-location-dot"></i>
                            <?= guest_detail_safe($procedencia_label) ?>
                        </span>
                        <span class="guest-chip">
                            <i class="fas fa-address-book"></i>
                            <?= guest_detail_safe($contacto_label) ?>
                        </span>
                        <?php if ($es_cliente_frecuente): ?>
                            <span class="guest-chip">
                                <i class="fas fa-star"></i>
                                Cliente frecuente
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <aside class="guest-action-rail" aria-label="Acciones principales">
                <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-action primary">
                    <span><i class="fas fa-calendar-plus"></i> Nueva reservación</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="<?= url('huespedes/' . ($huesped['id'] ?? 0) . '/edit') ?>" class="guest-action">
                    <span><i class="fas fa-pen"></i> Editar información</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
                <a href="<?= url('huespedes') ?>" class="guest-action">
                    <span><i class="fas fa-arrow-left"></i> Volver al directorio</span>
                    <i class="fas fa-list"></i>
                </a>
            </aside>
        </header>

        <section class="guest-metrics-strip" aria-label="Resumen del huésped">
            <article class="guest-metric" style="--metric-color: var(--gd-primary);">
                <span>Visitas válidas</span>
                <strong><?= number_format($total_reservaciones) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: var(--gd-accent);">
                <span>Total gastado</span>
                <strong><?= format_money($total_gastado) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: var(--gd-success);">
                <span>Promedio</span>
                <strong><?= format_money($gasto_promedio) ?></strong>
            </article>
            <article class="guest-metric" style="--metric-color: #64748B;">
                <span>Vehículos</span>
                <strong><?= number_format($vehiculos_count) ?></strong>
            </article>
        </section>

        <div class="guest-layout">
            <main class="guest-main-stack">
                <section class="guest-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Datos del huésped</h2>
                            <p>Contacto, procedencia y datos de registro.</p>
                        </div>
                    </div>
                    <div class="guest-panel-body">
                        <div class="guest-info-grid">
                            <article class="guest-info-card">
                                <i class="fas fa-phone"></i>
                                <div>
                                    <span class="guest-info-label">Teléfono</span>
                                    <strong><?= guest_detail_safe($telefono, 'No registrado') ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <span class="guest-info-label">Email</span>
                                    <strong><?= guest_detail_safe($email, 'No registrado') ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-map-location-dot"></i>
                                <div>
                                    <span class="guest-info-label">Procedencia</span>
                                    <strong><?= guest_detail_safe($procedencia_label) ?></strong>
                                </div>
                            </article>
                            <article class="guest-info-card">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <span class="guest-info-label">Última visita</span>
                                    <strong><?= guest_detail_safe($ultima_visita_label) ?></strong>
                                </div>
                            </article>
                            <?php if (!empty($huesped['vehiculo_marca']) || !empty($huesped['vehiculo_placas'])): ?>
                                <article class="guest-info-card">
                                    <i class="fas fa-car-side"></i>
                                    <div>
                                        <span class="guest-info-label">Vehículo anterior</span>
                                        <strong>
                                            <?= guest_detail_safe(trim(($huesped['vehiculo_marca'] ?? '') . ' ' . ($huesped['vehiculo_placas'] ?? ''))) ?>
                                        </strong>
                                    </div>
                                </article>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($huesped['notas'])): ?>
                            <div class="guest-note">
                                <strong>Notas internas</strong>
                                <p><?= nl2br(guest_detail_safe($huesped['notas'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section id="vehiculos" class="guest-panel guest-vehicles-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Vehículos registrados</h2>
                            <p>Control de placas, color y estacionamiento.</p>
                        </div>
                        <button type="button" onclick="abrirModalAgregarVehiculo()" class="guest-panel-action">
                            <i class="fas fa-plus"></i>
                            Agregar vehículo
                        </button>
                    </div>
                    <div class="guest-panel-body">
                        <?php if (!empty($vehiculos)): ?>
                            <div class="guest-vehicle-grid">
                                <?php foreach ($vehiculos as $vehiculo): ?>
                                    <?php
                                        $ubicacion = $estacionamientos[$vehiculo['estacionamiento'] ?? ''] ?? 'No especificado';
                                        $icono = ($vehiculo['estacionamiento'] ?? '') === 'coches' ? 'fa-car' : 'fa-square-parking';
                                        $vehiculo_nombre = trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? ''));
                                        $vehiculo_desc = trim(($vehiculo['marca'] ?? '') . ' - ' . ($vehiculo['placas'] ?? ''));
                                    ?>
                                    <article class="guest-vehicle-card">
                                        <div class="guest-vehicle-top">
                                            <div class="guest-vehicle-title">
                                                <span class="guest-vehicle-icon">
                                                    <i class="fas <?= guest_detail_safe($icono) ?>"></i>
                                                </span>
                                                <div>
                                                    <strong><?= guest_detail_safe($vehiculo_nombre, 'Vehículo') ?></strong>
                                                    <span><?= guest_detail_safe($vehiculo['placas'] ?? '', 'Sin placas') ?></span>
                                                </div>
                                            </div>
                                            <div class="guest-mini-actions">
                                                <button type="button"
                                                        onclick='editarVehiculo(<?= guest_detail_json_attr($vehiculo) ?>)'
                                                        class="guest-icon-btn"
                                                        title="Editar vehículo">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button"
                                                        onclick='confirmarEliminarVehiculo(<?= (int)($vehiculo['id'] ?? 0) ?>, <?= guest_detail_json_attr($vehiculo_desc) ?>)'
                                                        class="guest-icon-btn danger"
                                                        title="Eliminar vehículo">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="guest-vehicle-details">
                                            <div class="guest-detail-row">
                                                <span>Color</span>
                                                <strong><?= guest_detail_safe($vehiculo['color'] ?? '', 'No especificado') ?></strong>
                                            </div>
                                            <div class="guest-detail-row">
                                                <span>Ubicación</span>
                                                <strong class="guest-parking-badge">
                                                    <i class="fas <?= guest_detail_safe($icono) ?>"></i>
                                                    <?= guest_detail_safe($ubicacion) ?>
                                                </strong>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="guest-empty">
                                <i class="fas fa-car-side"></i>
                                <strong>Sin vehículos registrados</strong>
                                <p>Agrega un vehículo para llevar control del estacionamiento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="guest-panel guest-history-panel">
                    <div class="guest-panel-head">
                        <div>
                            <h2>Historial de reservaciones</h2>
                            <p><?= number_format($reservaciones_count) ?> registros asociados a este huésped.</p>
                        </div>
                    </div>
                    <div class="guest-panel-body">
                        <?php if (!empty($reservaciones)): ?>
                            <div class="guest-reservation-list">
                                <?php foreach ($reservaciones as $reservacion): ?>
                                    <?php
                                        $estado = $reservacion['estado'] ?? 'desconocido';
                                        $estadoReserva = guest_detail_status($estado);
                                        $habitaciones_total = (int)($reservacion['total_habitaciones'] ?? 0);
                                    ?>
                                    <article class="guest-reservation-card"
                                             style="--status-color: <?= guest_detail_safe($estadoReserva['color']) ?>; --status-soft: <?= guest_detail_safe($estadoReserva['soft']) ?>;">
                                        <div class="guest-reservation-main">
                                            <strong><?= guest_detail_safe($reservacion['habitaciones_numeros'] ?? '', 'Sin habitaciones') ?></strong>
                                            <span>
                                                <?= $habitaciones_total ?> <?= $habitaciones_total === 1 ? 'habitación' : 'habitaciones' ?>
                                                <?php if (!empty($reservacion['habitaciones_cortesia'])): ?>
                                                    · <?= (int)$reservacion['habitaciones_cortesia'] ?> cortesía
                                                <?php endif; ?>
                                            </span>
                                            <?php if (!empty($reservacion['tipos_habitacion'])): ?>
                                                <span><?= guest_detail_safe($reservacion['tipos_habitacion']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Entrada</span>
                                            <strong><?= format_date($reservacion['fecha_entrada'] ?? '') ?></strong>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Salida</span>
                                            <strong><?= format_date($reservacion['fecha_salida'] ?? '') ?></strong>
                                        </div>
                                        <div class="guest-reservation-meta">
                                            <span>Total</span>
                                            <strong><?= format_money($reservacion['precio_total'] ?? 0) ?></strong>
                                            <span><?= guest_detail_safe(ucfirst($reservacion['metodo_pago'] ?? 'No especificado')) ?></span>
                                        </div>
                                        <div>
                                            <span class="guest-status-badge">
                                                <i class="fas fa-circle"></i>
                                                <?= guest_detail_safe($estadoReserva['label']) ?>
                                            </span>
                                        </div>
                                        <a href="<?= url('reservaciones/ver/' . ($reservacion['id'] ?? 0)) ?>" class="guest-reservation-link">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="guest-empty guest-history-empty">
                                <i class="fas fa-calendar-times"></i>
                                <strong>Sin reservaciones registradas</strong>
                                <p>Este huésped aún no tiene reservaciones en el sistema.</p>
                                <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-panel-action">
                                    <i class="fas fa-calendar-plus"></i>
                                    Crear primera reservación
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php View::partial('documentos_entidad', [
                    'documentosEntidad' => $documentosEntidad ?? [],
                    'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
                ]); ?>
            </main>

            <aside class="guest-side-stack">
                <section class="guest-side-card guest-score-card">
                    <h2>Perfil de actividad</h2>
                    <div class="guest-score-number"><?= number_format($total_reservaciones) ?></div>
                    <p><?= $es_cliente_frecuente ? 'Cliente frecuente con historial activo.' : 'Historial en crecimiento.' ?></p>
                    <div class="guest-score-list">
                        <div>
                            <span>Total gastado</span>
                            <strong><?= format_money($total_gastado) ?></strong>
                        </div>
                        <div>
                            <span>Gasto promedio</span>
                            <strong><?= format_money($gasto_promedio) ?></strong>
                        </div>
                        <div>
                            <span>Vehículos</span>
                            <strong><?= number_format($vehiculos_count) ?></strong>
                        </div>
                    </div>
                </section>

                <section class="guest-side-card">
                    <h2>Acciones rápidas</h2>
                    <div class="guest-quick-actions">
                        <a href="<?= url('reservaciones/crear?huesped_id=' . ($huesped['id'] ?? 0)) ?>" class="guest-side-action">
                            <span><i class="fas fa-calendar-plus"></i> Nueva reservación</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="<?= url('huespedes/' . ($huesped['id'] ?? 0) . '/edit') ?>" class="guest-side-action">
                            <span><i class="fas fa-pen"></i> Editar información</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <?php if ($telefono !== ''): ?>
                            <a href="tel:<?= guest_detail_safe($telefono) ?>" class="guest-side-action">
                                <span><i class="fas fa-phone"></i> Llamar</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($email !== ''): ?>
                            <a href="mailto:<?= guest_detail_safe($email) ?>" class="guest-side-action">
                                <span><i class="fas fa-envelope"></i> Enviar email</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="guest-side-card">
                    <h2>Información del sistema</h2>
                    <ul class="guest-system-list">
                        <li>
                            <span>ID</span>
                            <strong>#<?= (int)($huesped['id'] ?? 0) ?></strong>
                        </li>
                        <li>
                            <span>Registrado</span>
                            <strong><?= format_datetime($huesped['created_at'] ?? '') ?></strong>
                        </li>
                        <li>
                            <span>Actualizado</span>
                            <strong><?= format_datetime($huesped['updated_at'] ?? '') ?></strong>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </div>
</div>

<div id="modalAgregarVehiculo" class="guest-modal hidden">
    <div class="guest-modal-card">
        <div class="guest-modal-head">
            <h3>
                <i class="fas fa-car"></i>
                Agregar vehículo
            </h3>
            <button type="button" onclick="cerrarModalAgregarVehiculo()" class="guest-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formAgregarVehiculo" class="guest-vehicle-form">
            <input type="hidden" name="huesped_id" value="<?= (int)($huesped['id'] ?? 0) ?>">
            <?= csrf_field() ?>

            <div class="guest-form-grid">
                <div class="guest-form-field">
                    <label>Marca <span class="text-red-500">*</span></label>
                    <input type="text" name="marca" required>
                </div>
                <div class="guest-form-field">
                    <label>Modelo</label>
                    <input type="text" name="modelo">
                </div>
            </div>

            <div class="guest-form-grid">
                <div class="guest-form-field">
                    <label>Placas</label>
                    <input type="text" name="placas" style="text-transform: uppercase" class="font-mono">
                </div>
                <div class="guest-form-field">
                    <label>Color</label>
                    <input type="text" name="color">
                </div>
            </div>

            <div class="guest-radio-group">
                <label>Estacionamiento <span class="text-red-500">*</span></label>
                <div class="guest-radio-options">
                    <?= $guestParkingOptionsHtml() ?>
                </div>
            </div>

            <div class="guest-modal-actions">
                <button type="button" onclick="cerrarModalAgregarVehiculo()" class="guest-cancel">Cancelar</button>
                <button type="submit" class="guest-save">
                    <i class="fas fa-save"></i>
                    Guardar vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalEditarVehiculo" class="guest-modal hidden">
    <div class="guest-modal-card">
        <div class="guest-modal-head">
            <h3>
                <i class="fas fa-pen-to-square"></i>
                Editar vehículo
            </h3>
            <button type="button" onclick="cerrarModalEditarVehiculo()" class="guest-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formEditarVehiculo" class="guest-vehicle-form">
            <input type="hidden" name="vehiculo_id" id="edit_vehiculo_id">
            <?= csrf_field() ?>

            <div class="guest-form-grid">
                <div class="guest-form-field">
                    <label>Marca <span class="text-red-500">*</span></label>
                    <input type="text" name="marca" id="edit_marca" required>
                </div>
                <div class="guest-form-field">
                    <label>Modelo</label>
                    <input type="text" name="modelo" id="edit_modelo">
                </div>
            </div>

            <div class="guest-form-grid">
                <div class="guest-form-field">
                    <label>Placas</label>
                    <input type="text" name="placas" id="edit_placas" style="text-transform: uppercase" class="font-mono">
                </div>
                <div class="guest-form-field">
                    <label>Color</label>
                    <input type="text" name="color" id="edit_color">
                </div>
            </div>

            <div class="guest-radio-group">
                <label>Estacionamiento <span class="text-red-500">*</span></label>
                <div class="guest-radio-options">
                    <?= $guestParkingOptionsHtml('edit-estacionamiento') ?>
                </div>
            </div>

            <div class="guest-modal-actions">
                <button type="button" onclick="cerrarModalEditarVehiculo()" class="guest-cancel">Cancelar</button>
                <button type="submit" class="guest-save">
                    <i class="fas fa-save"></i>
                    Actualizar vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function guestSetModalState(modalId, isOpen) {
    const modal = document.getElementById(modalId);
    const sidebar = document.getElementById('sidebar');
    if (!modal) return;

    modal.classList.toggle('hidden', !isOpen);
    document.body.classList.toggle('overflow-hidden', isOpen);
    if (sidebar) {
        sidebar.style.display = isOpen ? 'none' : '';
    }
}

function abrirModalAgregarVehiculo() {
    guestSetModalState('modalAgregarVehiculo', true);
}

function cerrarModalAgregarVehiculo() {
    guestSetModalState('modalAgregarVehiculo', false);
    const form = document.getElementById('formAgregarVehiculo');
    if (form) form.reset();
}

function abrirModalEditarVehiculo() {
    guestSetModalState('modalEditarVehiculo', true);
}

function cerrarModalEditarVehiculo() {
    guestSetModalState('modalEditarVehiculo', false);
    const form = document.getElementById('formEditarVehiculo');
    if (form) form.reset();
}

function editarVehiculo(vehiculo) {
    document.getElementById('edit_vehiculo_id').value = vehiculo.id || '';
    document.getElementById('edit_marca').value = vehiculo.marca || '';
    document.getElementById('edit_modelo').value = vehiculo.modelo || '';
    document.getElementById('edit_placas').value = vehiculo.placas || '';
    document.getElementById('edit_color').value = vehiculo.color || '';

    const selectedParking = vehiculo.estacionamiento || <?= json_encode($estacionamientoDefault, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const parkingRadios = document.querySelectorAll('.edit-estacionamiento');
    let parkingMatched = false;
    parkingRadios.forEach(radio => {
        radio.checked = radio.value === selectedParking;
        if (radio.checked) {
            parkingMatched = true;
        }
    });
    if (!parkingMatched && parkingRadios.length > 0) {
        parkingRadios[0].checked = true;
    }

    abrirModalEditarVehiculo();
}

document.getElementById('modalAgregarVehiculo')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalAgregarVehiculo();
});

document.getElementById('modalEditarVehiculo')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditarVehiculo();
});

document.querySelector('#formAgregarVehiculo input[name="placas"]')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('edit_placas')?.addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('formAgregarVehiculo')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= url('huespedes/agregar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message,
                confirmButtonColor: '#1B2746'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#1B2746'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al agregar el vehículo',
            confirmButtonColor: '#1B2746'
        });
    });
});

document.getElementById('formEditarVehiculo')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= url('huespedes/actualizar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message,
                confirmButtonColor: '#1B2746'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al actualizar el vehículo',
                confirmButtonColor: '#1B2746'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al actualizar el vehículo. Intente nuevamente.',
            confirmButtonColor: '#1B2746'
        });
    });
});

function confirmarEliminarVehiculo(vehiculoId, descripcion) {
    Swal.fire({
        title: '¿Eliminar vehículo?',
        text: `Se eliminará el vehículo: ${descripcion}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#B83D35',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('vehiculo_id', vehiculoId);
            formData.append('csrf_token', '<?= csrf_token() ?>');

            fetch('<?= url('huespedes/eliminar-vehiculo') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: data.message,
                        confirmButtonColor: '#1B2746'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#1B2746'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo eliminar el vehículo. Intente nuevamente.',
                    confirmButtonColor: '#1B2746'
                });
            });
        }
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAgregarVehiculo();
        cerrarModalEditarVehiculo();
    }
});
</script>
