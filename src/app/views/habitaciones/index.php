<?php
$hotel_id_actual = obtenerHotelIdActualCompat();

// Mapeo de colores de habitación (Área Confortable)
$checkins_pendientes = isset($checkins_pendientes) && is_array($checkins_pendientes) ? $checkins_pendientes : [];
$checkouts_vencidos = isset($checkouts_vencidos) && is_array($checkouts_vencidos) ? $checkouts_vencidos : [];
$llegadas_tardias = isset($llegadas_tardias) && is_array($llegadas_tardias) ? $llegadas_tardias : [];
// Proyeccion a OTRA fecha: las fichas dejan de decir "hoy" (mentian al consultar
// otro dia). Vacio = estamos viendo el dia en curso. Los 4 consumidores del label
// (fichas hb-stat, $hbMobileSegments, $hbChips, $quickLegend) leen de aqui.
$hbFechaProyectada = !empty($mostrar_disponibilidad_fecha) ? (string) ($fecha_consultada ?? '') : '';
$hbFechaCorta = $hbFechaProyectada !== '' ? format_date($hbFechaProyectada, 'd/m') : '';
$hbLabelLibres = $hbFechaProyectada !== '' ? 'Libres' : 'Libres hoy';
$hb_hotel_checkin_hora = function_exists('hotel_config_get')
    ? (string) hotel_config_get('operacion.checkin_hora', '15:00', $hotel_id_actual)
    : '15:00';
$hb_hotel_checkin_hora = substr(trim($hb_hotel_checkin_hora), 0, 5);
if (!preg_match('/^\d{2}:\d{2}$/', $hb_hotel_checkin_hora)) {
    $hb_hotel_checkin_hora = '15:00';
}
// El panel de check-outs decia "Hasta 12:00 PM" fijo; la hora es por hotel.
$hb_hotel_checkout_hora = function_exists('hotel_config_get')
    ? (string) hotel_config_get('operacion.checkout_hora', '12:00', $hotel_id_actual)
    : '12:00';
$hb_hotel_checkout_hora = substr(trim($hb_hotel_checkout_hora), 0, 5);
if (!preg_match('/^\d{2}:\d{2}$/', $hb_hotel_checkout_hora)) {
    $hb_hotel_checkout_hora = '12:00';
}
$hb_hotel_checkout_label = date('g:i A', strtotime('2000-01-01 ' . $hb_hotel_checkout_hora));
$colores_habitacion = [
    'MOKA'      => '#6F4E37',
    'PURPURA'   => '#800080',
    'ORO'       => '#DAA520',
    'AMARILLO'  => '#F0C420',
    'MARRON'    => '#8B4513',
    'CEREZA'    => '#DE3163',
    'VIOLETA'   => '#7C3AED',
    'LIMON'     => '#84CC16',
    'NARANJA'   => '#EA580C',
    'MARFIL'    => '#C8B88A',
    'AZUL'      => '#2563EB',
    'ROSA'      => '#EC4899',
    'VERDE'     => '#16A34A',
    'UVA'       => '#6B21A8',
    'MENTA'     => '#34D399',
    'AMBAR'     => '#D97706',
    'VINO'      => '#7F1D1D',
    'GRIS'      => '#6B7280',
    'CHOCOLATE' => '#7B3F00',
    'CORAL'     => '#F97316',
    'TURQUESA'  => '#0D9488',
    'MAGENTA'   => '#DB2777',
];

if (!function_exists('hb_room_plain_text')) {
    function hb_room_plain_text($value) {
        $value = strtolower((string)($value ?? ''));
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return $ascii !== false ? $ascii : $value;
    }
}

if (!function_exists('hb_room_type_catalog_marker')) {
    function hb_room_type_catalog_marker(array $habitacion, array $tipos) {
        $features = (string)($habitacion['caracteristicas'] ?? '');
        if (!preg_match('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*([^\[\r\n,;]+?)\s*\[([a-z0-9_\-]+)\]/i', $features, $matches)) {
            return null;
        }

        $code = strtolower(trim((string)($matches[2] ?? '')));
        if ($code === '') {
            return null;
        }

        $label = trim((string)($matches[1] ?? ''));

        return [
            'code' => $code,
            'label' => $tipos[$code] ?? ($label !== '' ? $label : ucfirst(str_replace('_', ' ', $code))),
        ];
    }
}

if (!function_exists('hb_room_type_code')) {
    function hb_room_type_code(array $habitacion, array $tipos) {
        $marker = hb_room_type_catalog_marker($habitacion, $tipos);
        if (is_array($marker) && !empty($marker['code'])) {
            return (string)$marker['code'];
        }

        $tipo = (string)($habitacion['tipo'] ?? '');
        $features = hb_room_plain_text($habitacion['caracteristicas'] ?? '');

        if (strpos($features, 'jacuzzi') !== false) {
            if ($tipo === 'doble') {
                return 'doble_jacuzzi';
            }
            if ($tipo === 'sencilla') {
                return 'sencilla_jacuzzi';
            }
        }

        return $tipo;
    }
}

if (!function_exists('hb_room_type_label')) {
    function hb_room_type_label(array $habitacion, array $tipos) {
        $marker = hb_room_type_catalog_marker($habitacion, $tipos);
        if (is_array($marker) && trim((string)($marker['label'] ?? '')) !== '') {
            return (string)$marker['label'];
        }

        $tipo = (string)($habitacion['tipo'] ?? '');
        $label = $tipos[$tipo] ?? (function_exists('get_tipo_habitacion') ? get_tipo_habitacion($tipo) : ucfirst(str_replace('_', ' ', $tipo)));
        $features = hb_room_plain_text($habitacion['caracteristicas'] ?? '');

        if (strpos($features, 'jacuzzi') !== false && stripos((string)$label, 'jacuzzi') === false) {
            if ($tipo === 'doble') {
                return 'Doble con Jacuzzi';
            }
            if ($tipo === 'sencilla') {
                return 'Sencilla con Jacuzzi';
            }
        }

        return trim((string)$label) !== '' ? (string)$label : 'Otras';
    }
}

if (!function_exists('hb_room_beds_total')) {
    function hb_room_beds_total(array $habitacion) {
        return max(0, (int)($habitacion['camas_matrimoniales'] ?? 0) + (int)($habitacion['camas_individuales'] ?? 0));
    }
}

if (!function_exists('hb_room_capacity_label')) {
    function hb_room_capacity_label(array $habitacion) {
        $capacidad = (int)($habitacion['capacidad_personas'] ?? 0);
        if ($capacidad < 1) {
            return 'Personas N/D';
        }

        return number_format($capacidad) . ' persona' . ($capacidad === 1 ? '' : 's');
    }
}

if (!function_exists('hb_room_beds_label')) {
    function hb_room_beds_label(array $habitacion) {
        $total = hb_room_beds_total($habitacion);
        if ($total < 1) {
            return 'Camas N/D';
        }

        return number_format($total) . ' cama' . ($total === 1 ? '' : 's');
    }
}

if (!function_exists('hb_room_beds_detail_label')) {
    function hb_room_beds_detail_label(array $habitacion) {
        $matrimoniales = max(0, (int)($habitacion['camas_matrimoniales'] ?? 0));
        $individuales = max(0, (int)($habitacion['camas_individuales'] ?? 0));
        $partes = [];

        if ($matrimoniales > 0) {
            $partes[] = number_format($matrimoniales) . ' mat.';
        }

        if ($individuales > 0) {
            $partes[] = number_format($individuales) . ' ind.';
        }

        return !empty($partes) ? implode(' / ', $partes) : 'No definido';
    }
}

if (!function_exists('hb_room_owner_percent_label')) {
    function hb_room_owner_percent_label($value) {
        $percent = is_numeric($value) ? (float)$value : 100.0;
        if ($percent >= 99.995) {
            return '';
        }

        $label = number_format($percent, 2, '.', '');
        return rtrim(rtrim($label, '0'), '.') . '%';
    }
}
?>
<?php
// Obtener habitaciones en limpieza para el botón
$habitaciones_limpieza = [];
foreach ($habitaciones as $hab) {
    if ($hab['estado'] == 'limpieza') {
        $habitaciones_limpieza[] = [
            'id' => $hab['id'],
            'numero' => $hab['numero'],
            'tipo' => hb_room_type_label($hab, $tipos),
            'piso' => $hab['piso']
        ];
    }
}
$tiene_limpieza = count($habitaciones_limpieza) > 0;
?>
<style>
/* ═══════════════════════════════════════════════════════════════
   VISUAL REDESIGN — Aesthetic Enhancement Layer
   Only CSS changes. Zero functionality modifications.
   ═══════════════════════════════════════════════════════════════ */
@import url('<?= asset('vendor/fonts/marca2.css') ?>');

:root {
    --primary: #4A6741;
    --primary-dark: #3A5233;
    --primary-light: #5C7D52;
    --accent: #C8956C;
    --accent-dark: #B07A52;
    --accent-light: #E0B896;
    --surface: #FAFBF9;
    --surface-card: #FFFFFF;
    --surface-hover: #F5F7F4;
    --text-primary: #1A2E1A;
    --text-secondary: #5A6B5A;
    --text-muted: #8A9B8A;
    --border: #E2E8E0;
    --border-light: #EEF2ED;
    --shadow-sm: 0 1px 3px rgba(74, 103, 65, 0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 12px rgba(74, 103, 65, 0.08), 0 2px 4px rgba(0,0,0,0.04);
    --shadow-lg: 0 8px 30px rgba(74, 103, 65, 0.10), 0 4px 8px rgba(0,0,0,0.04);
    --shadow-xl: 0 16px 48px rgba(74, 103, 65, 0.12), 0 8px 16px rgba(0,0,0,0.04);
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 20px;
    --hotel-green: #4A6741;
}

/* Global typography & base */
.habitaciones-view,
.habitaciones-view *:not(i):not([class*="fa-"]):not(.fas):not(.far):not(.fab):not(.fal):not(.fad) {
    font-family: 'DM Sans', system-ui, -apple-system, sans-serif !important;
}

.habitaciones-view h1,
.habitaciones-view h2,
.habitaciones-view h3,
.habitaciones-view h4,
.habitaciones-view .text-2xl,
.habitaciones-view .text-xl,
.habitaciones-view .text-lg {
    font-family: 'Outfit', 'DM Sans', system-ui, sans-serif !important;
    letter-spacing: -0.02em;
}

.habitaciones-view {
    background: linear-gradient(165deg, #F4F7F3 0%, #EDF1EC 40%, #F0F3EF 100%) !important;
    min-height: 100vh;
}

/* Subtle background texture */
.habitaciones-view::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: radial-gradient(circle at 25% 25%, rgba(74, 103, 65, 0.015) 0%, transparent 50%),
                      radial-gradient(circle at 75% 75%, rgba(200, 149, 108, 0.015) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.habitaciones-view > * {
    position: relative;
    z-index: 1;
}

/* ── Header redesign ── */
.modern-header {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(20px) saturate(1.3) !important;
    -webkit-backdrop-filter: blur(20px) saturate(1.3) !important;
    border-bottom: 1px solid rgba(74, 103, 65, 0.08) !important;
    box-shadow: 0 1px 8px rgba(74, 103, 65, 0.05), 0 0 1px rgba(0,0,0,0.05) !important;
    padding: 0.625rem 0 !important;
}

.modern-header h1 {
    color: var(--text-primary) !important;
    font-weight: 700 !important;
    font-size: 1.25rem !important;
}

.modern-header p {
    color: var(--text-muted) !important;
}

.modern-header .bg-gradient-to-br {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.25) !important;
}

/* ── Buttons redesign ── */
.btn-modern {
    border-radius: var(--radius-md) !important;
    font-weight: 600 !important;
    letter-spacing: 0.01em !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    border: 1px solid transparent !important;
    font-size: 0.8125rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
    box-shadow: var(--shadow-md) !important;
}

.btn-modern.bg-gray-100 {
    background: var(--surface) !important;
    color: var(--text-secondary) !important;
    border-color: var(--border) !important;
}

.btn-modern.bg-gray-100:hover {
    background: white !important;
    border-color: var(--primary) !important;
    color: var(--primary) !important;
}

.btn-modern.bg-emerald-500 {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3) !important;
}

.btn-modern.bg-emerald-500:hover {
    box-shadow: 0 4px 16px rgba(74, 103, 65, 0.35) !important;
}

.btn-modern.bg-blue-500 {
    background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%) !important;
    box-shadow: 0 2px 8px rgba(59, 125, 216, 0.3) !important;
}

.btn-modern.bg-gradient-to-r {
    background: linear-gradient(135deg, var(--accent-dark) 0%, var(--accent) 100%) !important;
    box-shadow: 0 2px 8px rgba(200, 149, 108, 0.3) !important;
}

.btn-modern.bg-gradient-to-r:hover {
    box-shadow: 0 4px 16px rgba(200, 149, 108, 0.4) !important;
}

/* ── Container & content area — FULL WIDTH ── */
.habitaciones-view .container {
    max-width: 100% !important;
    padding-left: 1.5rem !important;
    padding-right: 1.5rem !important;
}

.habitaciones-view .container.max-w-7xl {
    max-width: 100% !important;
}

@media (min-width: 1280px) {
    .habitaciones-view .container {
        padding-left: 2rem !important;
        padding-right: 2rem !important;
    }
}

/* ── Stat widgets redesign ── */
/* CRITICAL: Protect Font Awesome from font override */
.habitaciones-view i[class*="fa-"],
.habitaciones-view .fas,
.habitaciones-view .far,
.habitaciones-view .fab,
.habitaciones-view .fal,
.habitaciones-view .fad {
    font-family: "Font Awesome 5 Free", "Font Awesome 5 Brands", "FontAwesome" !important;
    font-style: normal !important;
}

.habitaciones-view .fas {
    font-weight: 900 !important;
}

.habitaciones-view .far {
    font-weight: 400 !important;
}

.stat-widget {
    border-radius: var(--radius-lg) !important;
    border: 1px solid var(--border-light) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative !important;
    overflow: hidden !important;
}

/* Decorative accent on stat widgets */
.stat-widget::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.stat-widget:hover::before {
    opacity: 1;
}

.stat-widget.bg-blue-50::before { background: linear-gradient(90deg, #3B7DD8, #5B93E0); }
.stat-widget.bg-emerald-50::before { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.stat-widget.bg-purple-50::before { background: linear-gradient(90deg, #6A42B0, #9575CD); }
.stat-widget.bg-gray-50::before { background: linear-gradient(90deg, #5A6B5A, #8A9B8A); }

.stat-widget::after {
    content: '';
    position: absolute;
    top: -10px;
    right: -10px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    opacity: 0.04;
    pointer-events: none;
}

.stat-widget.bg-blue-50::after { background: #3B7DD8; }
.stat-widget.bg-emerald-50::after { background: var(--primary); }
.stat-widget.bg-purple-50::after { background: #7E57C2; }
.stat-widget.bg-gray-50::after { background: #5A6B5A; }

.stat-widget:hover {
    transform: translateY(-3px) !important;
    box-shadow: var(--shadow-lg) !important;
    border-color: transparent !important;
}

.stat-widget .text-2xl {
    font-weight: 800 !important;
    font-family: 'Outfit', sans-serif !important;
}

.stat-widget .bg-blue-500 {
    background: linear-gradient(135deg, #3B7DD8 0%, #5B93E0 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(59, 125, 216, 0.25) !important;
}

.stat-widget .bg-emerald-500 {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(74, 103, 65, 0.25) !important;
}

.stat-widget .bg-purple-500 {
    background: linear-gradient(135deg, #7E57C2 0%, #9575CD 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(126, 87, 194, 0.25) !important;
}

.stat-widget .bg-gray-600 {
    background: linear-gradient(135deg, #5A6B5A 0%, #7A8B7A 100%) !important;
    border-radius: var(--radius-md) !important;
    box-shadow: 0 2px 6px rgba(90, 107, 90, 0.25) !important;
}

/* Stat widget backgrounds */
.stat-widget.bg-blue-50 {
    background: linear-gradient(135deg, #F0F5FC 0%, #E8F0FA 100%) !important;
    border-color: rgba(59, 125, 216, 0.12) !important;
}

.stat-widget.bg-emerald-50 {
    background: linear-gradient(135deg, #F0F7EF 0%, #E5F0E4 100%) !important;
    border-color: rgba(74, 103, 65, 0.12) !important;
}

.stat-widget.bg-purple-50 {
    background: linear-gradient(135deg, #F5F0FC 0%, #EDE5FA 100%) !important;
    border-color: rgba(126, 87, 194, 0.12) !important;
}

.stat-widget.bg-gray-50 {
    background: linear-gradient(135deg, #F5F7F5 0%, #EDF0ED 100%) !important;
    border-color: rgba(90, 107, 90, 0.12) !important;
}

/* White wrapper for stats */
.bg-white.rounded-lg.shadow-sm.p-3.mb-4 {
    background: rgba(255, 255, 255, 0.7) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-sm) !important;
    padding: 1rem !important;
}

/* ── Filter bar redesign ── */
.bg-white.rounded-xl.shadow-sm.p-2.mb-4,
.bg-white.rounded-xl.shadow-sm.p-3.mb-4,
div[class*="bg-white rounded-xl shadow-sm"][class*="mb-4"] {
    background: rgba(255, 255, 255, 0.75) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-sm) !important;
}

.filter-input,
.filter-select,
.filter-date {
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
    background: var(--surface) !important;
    color: var(--text-primary) !important;
    font-size: 0.8125rem !important;
    transition: all 0.2s ease !important;
}

.filter-input:focus,
.filter-select:focus,
.filter-date:focus {
    border-color: var(--primary) !important;
    background: white !important;
    box-shadow: 0 0 0 3px rgba(74, 103, 65, 0.1) !important;
}

.filter-btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%) !important;
    color: white !important;
    border-radius: var(--radius-sm) !important;
    box-shadow: 0 2px 6px rgba(74, 103, 65, 0.2) !important;
}

.filter-btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%) !important;
    box-shadow: 0 3px 10px rgba(74, 103, 65, 0.3) !important;
    transform: translateY(-1px) !important;
}

.filter-btn-reset {
    background: var(--surface) !important;
    color: var(--text-muted) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
}

.filter-btn-today {
    background: var(--surface) !important;
    color: var(--text-secondary) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
    padding: 0.375rem 0.75rem !important;
    font-size: 0.75rem !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
    height: 32px !important;
    text-decoration: none !important;
    font-weight: 500 !important;
    transition: all 0.2s !important;
}

.filter-btn-today:hover {
    border-color: var(--primary) !important;
    color: var(--primary) !important;
    background: white !important;
}

/* ── Room cards - state colors refined ── */
.estado-disponible {
    background: linear-gradient(145deg, #E8F5E4 0%, #D4EDCF 50%, #C5E4BF 100%) !important;
    border-left: 4px solid var(--primary) !important;
    box-shadow: var(--shadow-sm) !important;
}

.estado-por_llegar {
    background: linear-gradient(145deg, #EDE5FA 0%, #DED3F5 50%, #D4C7F0 100%) !important;
    border-left: 4px solid #7E57C2 !important;
    box-shadow: 0 2px 4px rgba(126, 87, 194, 0.1) !important;
}

.estado-ocupada {
    background: linear-gradient(145deg, #FDE8E8 0%, #FACACA 50%, #F5B8B8 100%) !important;
    border-left: 4px solid #D45B5B !important;
    box-shadow: 0 2px 4px rgba(212, 91, 91, 0.1) !important;
}

.estado-mantenimiento {
    background: linear-gradient(145deg, #FEF3DC 0%, #FDE8B9 50%, #FBDDA0 100%) !important;
    border-left: 4px solid #C8956C !important;
    box-shadow: 0 2px 4px rgba(200, 149, 108, 0.1) !important;
}

.estado-limpieza {
    background: linear-gradient(145deg, #E3F0FC 0%, #D0E4F9 50%, #BFD9F5 100%) !important;
    border-left: 4px solid #3B7DD8 !important;
    box-shadow: 0 2px 4px rgba(59, 125, 216, 0.1) !important;
}

/* ── CREATIVE CARD ENHANCEMENTS ── */
/* Decorative corner accents on cards */
.flip-card-front::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 50px;
    border-radius: 0 var(--radius-lg) 0 100%;
    opacity: 0.06;
    pointer-events: none;
    z-index: 1;
    transition: all 0.3s ease;
}

.estado-disponible.flip-card-front::before { background: var(--primary); }
.estado-por_llegar.flip-card-front::before { background: #7E57C2; }
.estado-ocupada.flip-card-front::before { background: #D45B5B; }
.estado-mantenimiento.flip-card-front::before { background: #C8956C; }
.estado-limpieza.flip-card-front::before { background: #3B7DD8; }

/* Decorative bottom bar on cards */
.flip-card-front::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 12px;
    right: 12px;
    height: 3px;
    border-radius: 3px 3px 0 0;
    opacity: 0;
    transform: scaleX(0);
    transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: none;
    z-index: 2;
}

.estado-disponible.flip-card-front::after { background: linear-gradient(90deg, var(--primary), var(--primary-light)); }
.estado-por_llegar.flip-card-front::after { background: linear-gradient(90deg, #6A42B0, #9575CD); }
.estado-ocupada.flip-card-front::after { background: linear-gradient(90deg, #C94444, #E88080); }
.estado-mantenimiento.flip-card-front::after { background: linear-gradient(90deg, #B07A52, #D4A070); }
.estado-limpieza.flip-card-front::after { background: linear-gradient(90deg, #2E6AB0, #5B93E0); }

.flip-card:hover .flip-card-front::after {
    opacity: 1;
    transform: scaleX(1);
}

.flip-card:hover .flip-card-front::before {
    opacity: 0.1;
    width: 65px;
    height: 65px;
}

/* Subtle diagonal pattern on disponible cards */
.estado-disponible {
    background-image:
        linear-gradient(145deg, #E8F5E4 0%, #D4EDCF 50%, #C5E4BF 100%),
        repeating-linear-gradient(
            45deg,
            transparent,
            transparent 10px,
            rgba(74, 103, 65, 0.02) 10px,
            rgba(74, 103, 65, 0.02) 11px
        ) !important;
}

/* Subtle dots pattern on ocupada cards */
.estado-ocupada {
    background-image:
        linear-gradient(145deg, #FDE8E8 0%, #FACACA 50%, #F5B8B8 100%),
        radial-gradient(circle, rgba(212, 91, 91, 0.04) 1px, transparent 1px) !important;
    background-size: auto, 12px 12px !important;
}

/* Enhanced room number styling */
.flip-card-front h3 {
    position: relative;
    display: inline-block;
}

/* Status badge glow effect */
.estado-icon {
    position: relative;
}

.estado-disponible .estado-icon::after,
.estado-por_llegar .estado-icon::after,
.estado-ocupada .estado-icon::after,
.estado-limpieza .estado-icon::after,
.estado-mantenimiento .estado-icon::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: inherit;
    opacity: 0.25;
    z-index: -1;
}

.estado-disponible .estado-icon::after { box-shadow: 0 0 8px var(--primary); }
.estado-por_llegar .estado-icon::after { box-shadow: 0 0 8px #7E57C2; }
.estado-ocupada .estado-icon::after { box-shadow: 0 0 8px #D45B5B; }
.estado-limpieza .estado-icon::after { box-shadow: 0 0 8px #3B7DD8; }
.estado-mantenimiento .estado-icon::after { box-shadow: 0 0 8px #C8956C; }

/* Card inner shadow for depth */
.flip-card-front {
    box-shadow: inset 0 -1px 0 rgba(0,0,0,0.03), inset 0 1px 0 rgba(255,255,255,0.5) !important;
}

/* Price tag styling */
.flip-card-front .text-sm.font-bold {
    background: rgba(255,255,255,0.5);
    padding: 2px 8px;
    border-radius: 6px;
    font-variant-numeric: tabular-nums;
    backdrop-filter: blur(4px);
}

/* ── Flip card refinement ── */
.flip-card {
    border-radius: var(--radius-lg) !important;
}

.flip-card-front,
.flip-card-back {
    border-radius: var(--radius-lg) !important;
}

.room-card-compact {
    border-radius: var(--radius-lg) !important;
}

.room-card-compact:hover {
    box-shadow: rgba(74, 103, 65, 0.18) 0px 60px 30px -40px !important;
}

.room-card-compact.flipped:hover {
    box-shadow: rgba(74, 103, 65, 0.22) 0px 60px 30px -40px !important;
}

/* Staggered card entrance animation */
@keyframes cardSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.97);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.room-card-compact {
    animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards !important;
}

/* ── Back panel state colors — handled via inline style from PHP now ── */

/* Decorative circles on back panel */
.flip-card-back::before {
    content: '';
    position: absolute;
    top: -20px;
    right: -20px;
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    pointer-events: none;
}

.flip-card-back::after {
    content: '';
    position: absolute;
    bottom: -12px;
    left: -12px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,0.05);
    pointer-events: none;
}

.flip-card-back .btn-action {
    border-radius: var(--radius-sm) !important;
    font-weight: 600 !important;
    backdrop-filter: blur(4px) !important;
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    background: rgba(255, 255, 255, 0.15) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.flip-card-back .btn-action:hover {
    background: rgba(255, 255, 255, 0.28) !important;
    transform: scale(1.04) !important;
}

.flip-card-back .btn-primary {
    background: white !important;
    border: none !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.18) !important;
    color: var(--room-accent-color, #4A6741) !important;
    font-weight: 700 !important;
    border-radius: 8px !important;
}

.flip-card-back .btn-primary:hover {
    background: rgba(255,255,255,0.95) !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.22) !important;
    transform: translateY(-1px) !important;
}

/* ── Estado icons refined ── */
.estado-icon {
    border-radius: var(--radius-sm) !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1) !important;
}

.estado-disponible .estado-icon { background: var(--primary) !important; }
.estado-por_llegar .estado-icon { background: #7E57C2 !important; }
.estado-ocupada .estado-icon { background: #D45B5B !important; }
.estado-mantenimiento .estado-icon { background: var(--accent) !important; }
.estado-limpieza .estado-icon { background: #3B7DD8 !important; }

/* ── Check-in/out panels redesign ── */
.bg-purple-50.p-3.rounded-t-lg {
    background: linear-gradient(135deg, #F5F0FC 0%, #EDE5FA 100%) !important;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
}

.bg-yellow-50.p-3.rounded-t-lg {
    background: linear-gradient(135deg, #FEF7EC 0%, #FDF0DC 100%) !important;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0 !important;
}

/* Panels container */
.grid.grid-cols-1.lg\:grid-cols-2.gap-3.mb-4 > .bg-white.rounded-lg.shadow-sm {
    border-radius: var(--radius-lg) !important;
    border: 1px solid var(--border-light) !important;
    overflow: hidden !important;
    box-shadow: var(--shadow-sm) !important;
}

/* ── Quick view modal redesign ── */
#vistaRapidaModal .bg-white.rounded-xl {
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important;
    border: 1px solid var(--border-light) !important;
}

#vistaRapidaModal .bg-gradient-to-r {
    background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%) !important;
    padding: 1rem 1.25rem !important;
}

/* Quick view room tiles */
.room-quick-view {
    border-radius: var(--radius-md) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
}

.room-quick-view .font-bold,
.room-quick-view .text-xs {
    font-family: 'Outfit', sans-serif !important;
}

.room-quick-view:hover {
    transform: scale(1.1) !important;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15) !important;
}

/* ── Cleaning modal redesign ── */
#modalLimpieza .bg-white.rounded-xl {
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-xl) !important;
}

#modalLimpieza .bg-gradient-to-r {
    background: linear-gradient(135deg, #2E6AB0 0%, #3B7DD8 60%, #5B93E0 100%) !important;
}

#modalLimpieza label {
    border-radius: var(--radius-md) !important;
    transition: all 0.2s ease !important;
}

#modalLimpieza label:hover {
    background: #E3F0FC !important;
    border-color: #3B7DD8 !important;
}

/* ── Tooltip redesign ── */
.room-tooltip {
    border-radius: var(--radius-md) !important;
    box-shadow: var(--shadow-xl) !important;
    border: 1px solid var(--border-light) !important;
    backdrop-filter: blur(8px) !important;
    background: rgba(255, 255, 255, 0.96) !important;
}

.room-tooltip .tooltip-header {
    font-family: 'Outfit', sans-serif !important;
    font-weight: 700 !important;
    color: var(--text-primary) !important;
}

.room-tooltip .tooltip-guest {
    background: linear-gradient(135deg, #F5F7F4 0%, #EDF0ED 100%) !important;
    border: 1px solid var(--border) !important;
    border-radius: var(--radius-sm) !important;
}

/* ── Room color stripe refined ── */
.room-color-stripe {
    border-radius: 4px !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2), 0 0 0 1px rgba(255,255,255,0.5) !important;
}

/* ── SweetAlert custom styling ── */
.swal2-popup {
    border-radius: var(--radius-xl) !important;
}

.swal2-popup *:not(i):not([class*="fa-"]):not(.fas):not(.far):not(.fab) {
    font-family: 'DM Sans', system-ui, sans-serif !important;
}

.swal2-title {
    font-family: 'Outfit', sans-serif !important;
    color: var(--text-primary) !important;
}

/* ── Scrollbar styling ── */
.habitaciones-view ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.habitaciones-view ::-webkit-scrollbar-track {
    background: var(--surface);
    border-radius: 3px;
}

.habitaciones-view ::-webkit-scrollbar-thumb {
    background: var(--text-muted);
    border-radius: 3px;
}

.habitaciones-view ::-webkit-scrollbar-thumb:hover {
    background: var(--text-secondary);
}

/* ── No results message ── */
.habitaciones-view .bg-white.rounded-lg.shadow-sm.p-8 {
    background: rgba(255, 255, 255, 0.8) !important;
    backdrop-filter: blur(10px) !important;
    border: 1px solid var(--border-light) !important;
    border-radius: var(--radius-xl) !important;
    box-shadow: var(--shadow-md) !important;
}

/* ── Date availability banner ── */
.bg-blue-50.border.border-blue-200.rounded-lg {
    background: linear-gradient(135deg, #EDF4FC 0%, #E3EEF9 100%) !important;
    border: 1px solid rgba(59, 125, 216, 0.15) !important;
    border-radius: var(--radius-md) !important;
}

/* ── Loading animation refined ── */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}

.room-card-compact {
    animation: slideUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards !important;
}

/* ── Checkout/Checkin indicators refined ── */
.checkout-today-indicator {
    border-radius: 8px !important;
    box-shadow: 0 2px 8px rgba(251, 191, 36, 0.4) !important;
    font-weight: 800 !important;
    letter-spacing: 0.02em !important;
}

.checkin-vencido-indicator,
.checkout-vencido-indicator {
    border-radius: 8px !important;
    letter-spacing: 0.02em !important;
}

.late-arrival-indicator {
    border-radius: 8px !important;
    letter-spacing: 0.02em !important;
}

/* ── Purple badge (check-ins count) ── */
.bg-purple-600.text-white.text-xs.px-2 {
    background: linear-gradient(135deg, #6A42B0 0%, #8E65D0 100%) !important;
    box-shadow: 0 1px 4px rgba(106, 66, 176, 0.3) !important;
}

.bg-yellow-600.text-white.text-xs.px-2 {
    background: linear-gradient(135deg, #C8956C 0%, #D4A070 100%) !important;
    box-shadow: 0 1px 4px rgba(200, 149, 108, 0.3) !important;
}

/* ── Fix subtle details ── */
.bg-purple-50.rounded.hover\:bg-purple-100 {
    border-radius: var(--radius-sm) !important;
}

.bg-yellow-50.rounded.hover\:bg-yellow-100 {
    border-radius: var(--radius-sm) !important;
}

/* Price text */
.flip-card-front .text-sm.font-bold {
    color: var(--primary-dark) !important;
    font-family: 'Outfit', sans-serif !important;
}

/* Room number in cards */
.flip-card-front h3 {
    font-family: 'Outfit', sans-serif !important;
    color: var(--text-primary) !important;
    font-weight: 800 !important;
}

/* Mobile flip content header */
.mobile-flip-content h3 {
    font-family: 'Outfit', sans-serif !important;
    font-weight: 800 !important;
}

/* ── Grid gap refinement ── */
#habitaciones-grid {
    gap: 1.25rem !important;
}

/* Add 5 columns for very wide screens */
@media (min-width: 1536px) {
    #habitaciones-grid {
        grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
    }
}

/* Add 6 columns for ultra-wide */
@media (min-width: 1800px) {
    #habitaciones-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr)) !important;
    }
}

@media (max-width: 640px) {
    #habitaciones-grid {
        gap: 1rem !important;
    }
}

/* ── Btn opcion refinement ── */
.btn-opcion {
    border-radius: var(--radius-sm) !important;
    transition: all 0.2s ease !important;
}

.btn-opcion.activo {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3) !important;
}

/* ── Responsive refinements ── */
@media (max-width: 640px) {
    .modern-header {
        padding: 0.5rem 0 !important;
    }

    .stat-widget {
        border-radius: var(--radius-md) !important;
    }

    .bg-white.rounded-lg.shadow-sm.p-3.mb-4 {
        border-radius: var(--radius-lg) !important;
        padding: 0.75rem !important;
    }
}

/* Texto verde oscuro para estado disponible */
.estado-disponible .text-center p,
.estado-disponible_fecha .text-center p {
    color: #059669 !important;
}

/* Texto oscuro para estado ocupada */
.estado-ocupada .bg-white\/70 p,
.estado-ocupada_fecha .bg-white\/70 p {
    color: #1f2937 !important; /* Gris muy oscuro */
}

/* Texto oscuro para estado por llegar */
.estado-por_llegar .bg-white\/70 p {
    color: #1f2937 !important; /* Gris muy oscuro */
}
/* Texto azul oscuro para limpieza */
.estado-limpieza .text-center p {
    color: #1e40af !important;
}
/* Mejoras adicionales para tooltips */
.room-tooltip {
    display: none;
    font-family: system-ui, -apple-system, sans-serif;
}

.room-tooltip.show {
    display: block;
}

.room-tooltip .tooltip-header {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #1f2937;
}

.room-tooltip .tooltip-info i {
    color: #6b7280;
    flex-shrink: 0;
}

.room-tooltip .tooltip-guest {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    border: 1px solid #e5e7eb;
}

.room-tooltip .tooltip-guest i {
    color: #6b7280;
}

/* Animación de entrada mejorada */
@keyframes tooltipFadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.room-tooltip.show {
    animation: tooltipFadeIn 0.2s ease-out;
}

/* Sombra mejorada para el tooltip */
.room-tooltip {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                0 10px 15px -3px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(0, 0, 0, 0.05);
}
/* Texto ámbar oscuro para mantenimiento */
.estado-mantenimiento .text-center p {
    color: #92400e !important;
}

/* Para móviles específicamente */
@media (max-width: 640px) {
    .mobile-flip-content .text-center p {
        font-weight: 700; /* Más negrita para mejor legibilidad */
    }
}

/* NUEVO: Texto oscuro para el estado combinado limpieza + por llegar */
.estado-limpieza-por-llegar .text-center p {
    color: #1f2937 !important;
}
/* CSS crítico */
.habitaciones-view {
    opacity: 0;
    transition: opacity 0.3s ease;
    min-height: 100vh;
    background: #f8f9fa;
}
.habitaciones-view.loaded { opacity: 1; }

/* Header flotante moderno y compacto */
.modern-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(0,0,0,0.1);
    z-index: 40;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    padding: 0.75rem 0;
}

/* Header en flujo normal: no acompaña el scroll */
@media (min-width: 768px) {
    .modern-header {
        position: relative;
        top: auto;
    }
}

.modern-header .btn-modern {
    padding: 0.375rem 0.875rem;
    font-size: 0.8125rem;
}

.modern-header .btn-modern i {
    font-size: 0.75rem;
}

@media (max-width: 640px) {
    .modern-header .container {
        padding: 0.75rem;
    }

    .modern-header h1 {
        font-size: 1.125rem;
    }

    .modern-header .btn-modern {
        padding: 0.375rem 0.625rem;
        font-size: 0.75rem;
    }
}

/* Filtros compactos y modernos */
.filter-input,
.filter-select,
.filter-date {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    background: #f9fafb;
    transition: all 0.2s;
    min-height: 44px;
    height: 44px;
}

.filter-input:focus,
.filter-select:focus,
.filter-date:focus {
    outline: none;
    border-color: #4A6741;
    background: white;
    box-shadow: 0 0 0 3px rgba(74, 103, 65, 0.1);
}

.filter-select {
    padding-right: 1.5rem;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.25rem center;
    background-repeat: no-repeat;
    background-size: 1rem;
    appearance: none;
}

/* Estilos para modales con scroll */
.swal-popup-scrollable {
    max-height: 90vh !important;
    display: flex !important;
    flex-direction: column !important;
}

.swal-content-scrollable {
    overflow-y: auto !important;
    max-height: calc(90vh - 200px) !important;
}

/* Estilo específico para la lista de habitaciones */
.swal2-html-container .bg-white {
    scrollbar-width: thin;
    scrollbar-color: #FB923C #FED7AA;
}

.swal2-html-container .bg-white::-webkit-scrollbar {
    width: 8px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-track {
    background: #FED7AA;
    border-radius: 4px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-thumb {
    background: #FB923C;
    border-radius: 4px;
}

.swal2-html-container .bg-white::-webkit-scrollbar-thumb:hover {
    background: #F97316;
}

.filter-date {
    padding-right: 0.375rem;
}

.filter-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    min-height: 44px;
    height: 44px;
    border: 1px solid transparent;
}

.filter-btn i {
    font-size: 0.625rem;
}

.filter-btn-primary {
    background: #4A6741;
    color: white;
}

.filter-btn-primary:hover {
    background: #3A5233;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(74, 103, 65, 0.2);
}

.filter-btn-reset {
    background: #f3f4f6;
    color: #6b7280;
    border-color: #e5e7eb;
}

.filter-btn-reset:hover {
    background: #e5e7eb;
    color: #4b5563;
}

/* Ajustes móviles para filtros */
@media (max-width: 640px) {
    .filter-input,
    .filter-select,
    .filter-date,
    .filter-btn {
        font-size: 0.8125rem;
        min-height: 44px;
        height: 44px;
    }

    .filter-input {
        padding-left: 1.75rem;
    }

    .filter-select,
    .filter-date {
        min-width: 0;
        flex: 1;
    }
}

/* Tarjetas compactas */
.room-card-compact {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    height: 100%;
}
.room-card-compact:hover {
    transform: scale(1.02);
    box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}



/* Badge de color para la card */
.room-color-stripe {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 6px;
    height: 24px;
    border-radius: 3px;
    z-index: 5;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}

@media (max-width: 640px) {
    .room-color-stripe {
        width: 5px;
        height: 20px;
        top: 6px;
        left: 6px;
    }
}

/* Indicadores flotantes */
.checkout-today-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #fbbf24;
    color: #78350f;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 9999px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 2px;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 10;
}
/* Animación para el ícono de TV cuando hay controles pendientes */
@keyframes pulse {
    0% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
    100% {
        opacity: 1;
    }
}

.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
.late-arrival-indicator {
    position: absolute;
    top: 4px;
    left: 4px;
    background: #581c87;
    color: #f3e8ff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 9999px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 2px;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Indicador de CHECK-IN VENCIDO - MUY LLAMATIVO */
.checkin-vencido-indicator {
    position: absolute;
    top: 4px;
    left: 4px;
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 9999px;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 3px;
    animation: alertPulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 20;
    box-shadow: 0 0 15px rgba(220, 38, 38, 0.6), 0 4px 8px rgba(0,0,0,0.3);
    border: 2px solid #fee2e2;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.checkin-vencido-indicator i {
    animation: shake 0.5s ease-in-out infinite;
}

/* Indicador de CHECK-OUT VENCIDO - MUY LLAMATIVO */
.checkout-vencido-indicator {
    position: absolute;
    top: 4px;
    right: 4px;
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: white;
    font-size: 10px;
    padding: 3px 8px;
    border-radius: 9999px;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 3px;
    animation: alertPulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    z-index: 20;
    box-shadow: 0 0 15px rgba(234, 88, 12, 0.6), 0 4px 8px rgba(0,0,0,0.3);
    border: 2px solid #fed7aa;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.checkout-vencido-indicator i {
    animation: shake 0.5s ease-in-out infinite;
}

/* Animación de pulso más intensa para alertas críticas */
@keyframes alertPulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.7;
        transform: scale(1.05);
    }
}

/* Animación de temblor para los íconos de alerta */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

/* Estados con colores distintivos */
.estado-disponible {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-left: 4px solid #059669;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.1);
}

/* Tarjetas con check-in vencido - borde rojo pulsante */
.flip-card.has-checkin-vencido .flip-card-front {
    border: 3px solid #dc2626 !important;
    box-shadow: 0 0 20px rgba(220, 38, 38, 0.4), 0 4px 8px rgba(0,0,0,0.1) !important;
    animation: borderPulse 2s ease-in-out infinite;
}

/* Tarjetas con check-out vencido - borde naranja pulsante */
.flip-card.has-checkout-vencido .flip-card-front {
    border: 3px solid #ea580c !important;
    box-shadow: 0 0 20px rgba(234, 88, 12, 0.4), 0 4px 8px rgba(0,0,0,0.1) !important;
    animation: borderPulse 2s ease-in-out infinite;
}

/* Animación de borde pulsante para alertas */
@keyframes borderPulse {
    0%, 100% {
        box-shadow: 0 0 20px rgba(220, 38, 38, 0.4), 0 4px 8px rgba(0,0,0,0.1);
    }
    50% {
        box-shadow: 0 0 30px rgba(220, 38, 38, 0.6), 0 6px 12px rgba(0,0,0,0.15);
    }
}

/* Ajustes para indicadores en móvil */
@media (max-width: 640px) {
    .checkin-vencido-indicator,
    .checkout-vencido-indicator,
    .checkout-today-indicator,
    .late-arrival-indicator {
        font-size: 8px;
        padding: 2px 5px;
        gap: 2px;
    }

    .checkin-vencido-indicator i,
    .checkout-vencido-indicator i {
        font-size: 8px;
    }

    /* Mantener bordes pulsantes visibles en móvil */
    .flip-card.has-checkin-vencido .flip-card-front,
    .flip-card.has-checkout-vencido .flip-card-front {
        border-width: 2px !important;
    }
}

.estado-por_llegar {
    background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%) !important;
    border-left: 4px solid #9333ea;
    box-shadow: 0 2px 4px rgba(147, 51, 234, 0.15);
}

.estado-ocupada {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-left: 4px solid #dc2626;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
}

.estado-mantenimiento {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-left: 4px solid #d97706;
    box-shadow: 0 2px 4px rgba(217, 119, 6, 0.1);
}

.estado-limpieza {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    border-left: 4px solid #2563eb;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.1);
}
/* NUEVO: Estado combinado - Limpieza + Por Llegar (degradado azul a morado) */
.estado-limpieza-por-llegar {
    background: linear-gradient(45deg, #dbeafe 0%, #dbeafe 45%, #e9d5ff 55%, #e9d5ff 100%) !important;
    border-left: 4px solid;
    border-image: linear-gradient(to bottom, #2563eb 0%, #9333ea 100%) 1;
    box-shadow: 0 2px 4px rgba(107, 114, 128, 0.15);
}

.estado-doble {
    background: linear-gradient(45deg, #fee2e2 0%, #fee2e2 45%, #e9d5ff 55%, #e9d5ff 100%) !important;
    border-left: 4px solid;
    border-image: linear-gradient(to bottom, #dc2626 0%, #7c3aed 100%) 1;
    box-shadow: 0 2px 4px rgba(107, 114, 128, 0.15);
}

/* Estados para vista por fecha */
.estado-disponible_fecha {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-left: 4px solid #059669;
    box-shadow: 0 2px 4px rgba(5, 150, 105, 0.1);
}

.estado-ocupada_fecha {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-left: 4px solid #dc2626;
    box-shadow: 0 2px 4px rgba(220, 38, 38, 0.1);
}

/* Iconos de estado */
.estado-icon {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.estado-disponible .estado-icon { background: #059669; color: white; }
.estado-por_llegar .estado-icon { background: #9333ea; color: white; }
.estado-ocupada .estado-icon { background: #C2603C; color: white; }
.estado-mantenimiento .estado-icon { background: #d97706; color: white; }
.estado-limpieza .estado-icon { background: #2563eb; color: white; }
.estado-doble .estado-icon {
    background: linear-gradient(45deg, #dc2626 50%, #9333ea 50%);
    color: white;
}

/* NUEVO: Icono para estado combinado limpieza + por llegar */
.estado-limpieza-por-llegar .estado-icon {
    background: linear-gradient(45deg, #2563eb 50%, #9333ea 50%);
    color: white;
}

/* Widgets simplificados */
.stat-widget {
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}
.stat-widget:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.1);
}

/* Botones modernos */
.btn-modern {
    border: none;
    font-weight: 500;
    transition: all 0.2s ease;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

/* Animación de carga */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.room-card-compact {
    animation: slideUp 0.3s ease forwards;
}

/* ═══════════════════════════════════════════
   UIVERSE CARD ANIMATION — Click to reveal
   ═══════════════════════════════════════════ */

.flip-card {
    height: 165px;
    background: white;
    border-radius: 22px;
    padding: 3px;
    position: relative;
    overflow: hidden;
    box-shadow: rgba(74, 103, 65, 0.22) 0px 50px 25px -40px;
    transition: height 0.4s ease-in-out, border-radius 0.3s ease;
    cursor: pointer;
}

.flip-card.flipped {
    height: 240px;
    border-top-left-radius: 42px;
}

@media (max-width: 1024px) {
    .flip-card { height: 172px; }
    .flip-card.flipped { height: 245px; }
}
@media (max-width: 768px) {
    .flip-card { height: 178px; }
    .flip-card.flipped { height: 250px; }
}
@media (max-width: 640px) {
    .flip-card { height: 190px; }
    .flip-card.flipped { height: 260px; }
    #habitaciones-grid { gap: 0.75rem; }
}

/* Ocultar cara frontal completamente cuando está abierta */
.flip-card.flipped .flip-card-front {
    opacity: 0;
    pointer-events: none;
}

/* Flat inner — no 3D transform */
.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
    text-align: left;
    transition: height 0.4s ease-in-out;
}
/* Tooltip para vista rápida */
.room-tooltip {
    position: absolute;
    z-index: 9999;
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    padding: 0.75rem;
    min-width: 220px;
    max-width: 280px;
    pointer-events: none;
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.2s ease;
}

.room-tooltip.show {
    opacity: 1;
    transform: translateY(0);
}

/* Agregar en tu sección de estilos */
.btn-success {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(74, 103, 65, 0.3);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(74, 103, 65, 0.4);
}

.room-tooltip::before {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid transparent;
    border-right: 6px solid transparent;
    border-top: 6px solid white;
}

.room-tooltip .tooltip-header {
    font-weight: bold;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e5e7eb;
}

.room-tooltip .tooltip-info {
    font-size: 0.75rem;
    color: #4b5563;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.25rem;
}

.room-tooltip .tooltip-info i {
    width: 14px;
    text-align: center;
    color: #6b7280;
}

.room-tooltip .tooltip-guest {
    background: #f3f4f6;
    padding: 0.375rem 0.5rem;
    border-radius: 0.375rem;
    margin-top: 0.5rem;
    font-size: 0.75rem;
}

.room-tooltip .tooltip-price {
    font-weight: bold;
    color: #059669;
    margin-top: 0.5rem;
    text-align: right;
    font-size: 0.875rem;
}
/* ── UIverse card — click reveals back panel ── */
.flip-card.flipped {
    border-top-left-radius: 42px;
}

.flip-card-inner {
    position: relative;
    width: 100%;
    height: 100%;
}

/* Front stays in place, just gets covered by the back panel sliding up */
.flip-card-front {
    position: absolute;
    width: calc(100% - 6px);
    height: calc(100% - 6px);
    top: 3px;
    left: 3px;
    z-index: 1;
    border-radius: 19px;
    overflow: hidden;
    padding: 12px;
}

/* ── BACK PANEL: slides up from bottom ── */
.flip-card-back {
    position: absolute;
    left: 3px;
    right: 3px;
    top: 100%;
    bottom: 3px;
    z-index: 2;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: rgba(0,0,0,0.1) 0px 5px 10px inset;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: top 0.4s cubic-bezier(0.645, 0.045, 0.355, 1) 0s;
    padding: 10px 12px;
}

.flip-card.flipped .flip-card-back {
    top: 3px;
    transition: top 0.4s cubic-bezier(0.645, 0.045, 0.355, 1) 0.05s;
}

@media (max-width: 640px) {
    .flip-card-back {
        padding: 8px;
    }
    .flip-card-back h4 {
        font-size: 0.7rem;
        margin-bottom: 0.2rem;
    }
    .flip-card-back .info-item {
        font-size: 0.55rem;
        margin-bottom: 0.05rem;
    }
    .flip-card-back .action-buttons {
        gap: 0.2rem;
        margin-top: 0.2rem;
    }
    .flip-card-back .btn-action {
        font-size: 0.55rem;
        padding: 0.3rem 0.45rem;
        min-height: 28px;
    }
}



/* ── Front face circle view (shown when panel is open) ── */
/* Removed: no circle animation. Back panel fully covers the card. */

/* Subtle click indicator on front face — small dot at bottom center */
.flip-card-front::after {
    content: '';
    position: absolute;
    bottom: 7px;
    left: 50%;
    transform: translateX(-50%);
    width: 28px;
    height: 4px;
    background: var(--room-accent-color, #4A6741);
    opacity: 0.4;
    border-radius: 2px;
    pointer-events: none;
    transition: opacity 0.2s ease, width 0.2s ease;
}

.flip-card:hover .flip-card-front::after {
    opacity: 0.7;
    width: 40px;
}

.flip-card.flipped .flip-card-front::after {
    opacity: 0;
}

/* Back panel text colors */
.flip-card-back h4 {
    color: white !important;
}
.flip-card-back .info-item {
    color: rgba(255,255,255,0.92) !important;
}
.flip-card-back .info-item i {
    color: rgba(255,255,255,0.75) !important;
}

/* NUEVO: Estilos para reversos con alertas vencidas */
.flip-card-back.back-checkin-vencido {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    box-shadow: 0 0 20px rgba(220, 38, 38, 0.5);
}

.flip-card-back.back-checkout-vencido {
    background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
    color: white;
    box-shadow: 0 0 20px rgba(234, 88, 12, 0.5);
}

/* Mejorar levemente la legibilidad sin romper el diseño */
.flip-card-back.back-checkin-vencido h4,
.flip-card-back.back-checkout-vencido h4 {
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.flip-card-back.back-checkin-vencido .info-item,
.flip-card-back.back-checkout-vencido .info-item {
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
/* NUEVO: Color del reverso para estado combinado limpieza + por llegar */
.flip-card-back.back-limpieza-por-llegar {
    background: linear-gradient(135deg, #2563eb 0%, #9333ea 100%);
    color: white;
}

.flip-card-back.back-doble {
    background: linear-gradient(135deg, #dc2626 0%, #7c3aed 100%);
    color: white;
}

.flip-card-back h4 {
    font-size: 0.8125rem;
    font-weight: bold;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flip-card-back .info-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.6rem;
    margin-bottom: 0.15rem;
    opacity: 0.95;
}

.flip-card-back .info-item i {
    width: 12px;
    text-align: center;
    flex-shrink: 0;
}

/* Indicador de saldo en la tarjeta de habitación ocupada.
   El reverso usa gradiente con texto blanco, por eso usamos fondos
   translúcidos + acento brillante para que resalte sobre cualquier color. */
.flip-card-back .info-item.hb-saldo-pendiente {
    background: rgba(251, 191, 36, 0.22);
    border: 1px solid rgba(251, 191, 36, 0.55);
    border-radius: 8px;
    padding: 3px 7px;
    font-weight: 700;
}
.flip-card-back .info-item.hb-saldo-pendiente i {
    color: #fde68a;
}
.flip-card-back .info-item.hb-saldo-cubierto {
    opacity: 0.85;
}
.flip-card-back .info-item.hb-saldo-cubierto i {
    color: #bbf7d0;
}

/* Chip de tareas vinculadas (abre el panel "Tareas del cuarto") */
.flip-card-back .hb-tareas-chip {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    width: 100%;
    font-family: inherit;
    font-size: 0.6rem;
    line-height: 1.2;
    margin: 0.1rem 0 0.2rem;
    padding: 0.32rem 0.45rem;
    border: 1px solid rgba(255,255,255,0.28);
    border-radius: 8px;
    background: rgba(255,255,255,0.12);
    color: #fff;
    cursor: pointer;
    text-align: left;
    transition: background .15s ease, border-color .15s ease;
}
.flip-card-back .hb-tareas-chip:hover {
    background: rgba(255,255,255,0.22);
    border-color: rgba(255,255,255,0.5);
}
.flip-card-back .hb-tareas-chip i { width: 12px; text-align: center; flex-shrink: 0; }
.flip-card-back .hb-tareas-chip > span { flex: 1; min-width: 0; }
.flip-card-back .hb-tareas-chip .hb-tareas-detail,
.flip-card-back .hb-tareas-chip .hb-tareas-venc { opacity: .9; font-weight: 600; }
.flip-card-back .hb-tareas-chip .hb-tareas-venc { color: #FECACA; }
.flip-card-back .hb-tareas-chip .hb-tareas-arrow { font-size: .55rem; opacity: .7; }
.flip-card-back .hb-tareas-chip.is-vencida { border-color: rgba(254,202,202,.7); background: rgba(220,38,38,.30); }
.flip-card-back .hb-tareas-chip--empty { opacity: .82; border-style: dashed; }
.flip-card-back .hb-tareas-chip--empty:hover { opacity: 1; }

/* Modal "Tareas del cuarto": posicion robusta, no depende de utilidades Tailwind
   (se ancla al viewport aunque existan ancestros con transform/perspective) */
.tc-modal {
    position: fixed;
    inset: 0;
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(15, 23, 42, .6);
    -webkit-backdrop-filter: blur(3px);
    backdrop-filter: blur(3px);
}
.tc-modal.hidden { display: none; }
.tc-dialog {
    width: min(680px, 94vw);
    max-height: 88vh;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 30px 80px rgba(2, 6, 23, .45);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.tc-dialog > #tareasCuartoBody {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

.flip-card-back .action-buttons {
    display: flex;
    gap: 0.35rem;
    margin-top: auto;
    padding-top: 0.35rem;
    flex-shrink: 0;
}

.flip-card-back .btn-action {
    flex: 1;
    background: rgba(255, 255, 255, 0.18);
    color: white;
    padding: 0.3rem 0.4rem;
    border-radius: 8px;
    text-align: center;
    font-weight: 600;
    font-size: 0.6rem;
    letter-spacing: 0.02em;
    transition: all 0.18s ease;
    cursor: pointer;
    border: 1px solid rgba(255, 255, 255, 0.28);
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.18rem;
    white-space: nowrap;
    min-height: 44px;
    backdrop-filter: blur(6px);
}

.flip-card-back .btn-action:hover {
    background: rgba(255, 255, 255, 0.32);
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}

/* Contenido responsivo para móvil/tablet */
@media (max-width: 1024px) {
    /* Ajustes para tablets */
    .flip-card-front {
        padding: 10px;
    }

    .flip-card-back {
        padding: 10px;
    }

    .estado-icon {
        width: 26px;
        height: 26px;
        font-size: 13px;
    }

    .flip-card-front h3 {
        font-size: 1.125rem;
    }

    .flip-card-back h4 {
        font-size: 0.8125rem;
    }

    .flip-card-back .info-item {
        font-size: 0.625rem;
    }
}

@media (max-width: 640px) {
    /* Espaciado general */
    .habitaciones-view .container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* Widgets más compactos */
    .stat-widget {
        padding: 0.625rem;
    }

    .stat-widget .text-lg {
        font-size: 1rem;
    }

    /* Ocultar contenido desktop */
    .flip-card-front > .hidden.sm\:block {
        display: none !important;
    }

    /* Mostrar contenido móvil */
    .flip-card-front {
        padding: 8px;
        display: flex !important;
        flex-direction: column;
        justify-content: space-between;
    }

    .mobile-flip-content {
        display: flex !important;
        flex-direction: column;
        height: 100%;
        justify-content: space-between;
    }

    /* Ajustes de tamaño para móvil */
    .mobile-flip-content h3 {
        font-size: 1.5rem;
        line-height: 1.2;
    }

    .mobile-flip-content .text-xs {
        font-size: 0.625rem;
    }

    .mobile-flip-content .text-sm {
        font-size: 0.75rem;
    }

    .mobile-estado-icon {
        width: 22px !important;
        height: 22px !important;
        font-size: 11px !important;
    }

    /* Indicador de flip más visible */
    .mobile-flip-indicator {
        background: rgba(0, 0, 0, 0.2);
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 0.625rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        color: rgba(0, 0, 0, 0.8);
    }

    .mobile-flip-indicator::after {
        content: "→";
        font-weight: bold;
    }

    /* Ajustar reverso para móvil */
    .flip-card-back {
        padding: 8px;
    }

    .flip-card-back h4 {
        font-size: 0.75rem;
        margin-bottom: 0.375rem;
    }

    .flip-card-back .info-item {
        font-size: 0.625rem;
        margin-bottom: 0.125rem;
    }

    .flip-card-back .info-item i {
        font-size: 0.625rem;
        width: 12px;
    }

    .flip-card-back .action-buttons {
        gap: 0.375rem;
        margin-top: 0.375rem;
    }

    .flip-card-back .btn-action {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
    }

    /* Forzar color morado en móvil */
    .estado-por_llegar,
    .flip-card-front.estado-por_llegar {
        background: #c084fc !important;
        background-color: #c084fc !important;
        border-left: 4px solid #9333ea !important;
    }
}

/* Ocultar contenido móvil en desktop */
@media (min-width: 641px) {
    .mobile-flip-content {
        display: none !important;
    }
}

/* Mejoras para la accesibilidad táctil */
@media (hover: none) and (pointer: coarse) {
    .btn-action {
        min-height: 44px;
    }
}

/* Botones de opción */
.btn-opcion {
    cursor: pointer;
    transition: all 0.2s;
}

.btn-opcion.activo {
    background-color: #4A6741 !important;
    border-color: #4A6741 !important;
    color: white !important;
}

.btn-opcion.activo i,
.btn-opcion.activo span,
.btn-opcion.activo p {
    color: white !important;
}

.btn-opcion:hover {
    background-color: #d1d5db;
}
</style>
<script>
// Script crítico para la animación de carga
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.habitaciones-view');
    if (view) {
        view.classList.add('loaded');
    }

    // Animación de entrada escalonada
    const cards = document.querySelectorAll('.room-card-compact');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 20}ms`;
    });

    // Prevenir submit si no hay fecha seleccionada
    const form = document.querySelector('form');
    const fechaInput = document.querySelector('input[name="fecha_consulta"]');

    if (form && fechaInput) {
        form.addEventListener('submit', function(e) {
            const mostrarDisp = document.querySelector('input[name="mostrar_disponibilidad"]');
            if (mostrarDisp && mostrarDisp.value === '1' && !fechaInput.value) {
                fechaInput.removeAttribute('name');
                mostrarDisp.removeAttribute('name');
            }
        });
    }
});
</script>
<style>
/* Fase habitaciones: acciones brand-aware y ocupacion separada de alertas criticas. */
.habitaciones-view{
    --hotel-brand-primary: var(--brand-primary,#1B2746);
    --hotel-brand-secondary: var(--brand-secondary,#0F172A);
    --state-occupied:#C2603C;
    --state-occupied-dark:#9E4A2E;
    --state-occupied-soft:#F8EAE1;
}
.btn-brand{background:var(--hotel-brand-primary)!important;color:#fff!important;border:1px solid transparent!important;box-shadow:0 10px 24px color-mix(in srgb,var(--hotel-brand-primary) 24%,transparent)!important;}
.btn-brand:hover{filter:brightness(0.94);transform:translateY(-1px)!important;}
.btn-brand-outline{background:#fff!important;color:var(--hotel-brand-primary)!important;border:1px solid color-mix(in srgb,var(--hotel-brand-primary) 35%,#fff)!important;}
.btn-brand-outline:hover{background:color-mix(in srgb,var(--hotel-brand-primary) 8%,#fff)!important;}
.btn-brand-soft{background:color-mix(in srgb,var(--hotel-brand-primary) 12%,#fff)!important;color:var(--hotel-brand-primary)!important;border:1px solid color-mix(in srgb,var(--hotel-brand-primary) 22%,#fff)!important;}
.btn-brand-soft:hover{background:color-mix(in srgb,var(--hotel-brand-primary) 18%,#fff)!important;transform:translateY(-1px)!important;}
.brand-hover-card:hover{border-color:var(--hotel-brand-primary)!important;background:var(--hotel-brand-primary)!important;color:#fff!important;}
.brand-text{color:var(--hotel-brand-primary)!important;}
.brand-focus:focus,
.filter-input:focus,
.filter-select:focus,
.filter-date:focus{
    border-color:var(--hotel-brand-primary)!important;
    box-shadow:0 0 0 2px color-mix(in srgb,var(--hotel-brand-primary) 28%,transparent)!important;
    outline:none!important;
}
.filter-btn-primary{
    background:linear-gradient(135deg,var(--hotel-brand-primary),var(--hotel-brand-secondary))!important;
    color:#fff!important;
    box-shadow:0 6px 14px color-mix(in srgb,var(--hotel-brand-primary) 20%,transparent)!important;
}
.filter-btn-primary:hover{filter:brightness(0.96);box-shadow:0 8px 18px color-mix(in srgb,var(--hotel-brand-primary) 28%,transparent)!important;}
.filter-btn-today{color:var(--hotel-brand-primary)!important;border-color:color-mix(in srgb,var(--hotel-brand-primary) 28%,#fff)!important;}
.filter-btn-today:hover{background:color-mix(in srgb,var(--hotel-brand-primary) 8%,#fff)!important;}
.estado-ocupada,
.estado-ocupada_fecha{
    background-image:
        linear-gradient(145deg,#FFF7F3 0%,#F8EAE1 52%,#F2D4C5 100%),
        radial-gradient(circle, rgba(194,96,60,0.06) 1px, transparent 1px) !important;
    background-size: auto, 12px 12px !important;
    border-left-color:var(--state-occupied) !important;
    box-shadow:0 2px 4px rgba(194,96,60,0.14) !important;
}
.estado-ocupada.flip-card-front::before,
.estado-ocupada_fecha.flip-card-front::before{background:var(--state-occupied) !important;}
.estado-ocupada.flip-card-front::after,
.estado-ocupada_fecha.flip-card-front::after{background:linear-gradient(90deg,var(--state-occupied-dark),#D97750) !important;}
.estado-ocupada .estado-icon,
.estado-ocupada_fecha .estado-icon{background:var(--state-occupied) !important;color:#fff!important;}
.estado-ocupada .estado-icon::after,
.estado-ocupada_fecha .estado-icon::after{box-shadow:0 0 8px var(--state-occupied) !important;}
</style>
<div class="habitaciones-view">
    <?php if ($hbFechaProyectada !== ''): ?>
    <?php
        // Barra de contexto temporal. Solo existe cuando NO estas viendo hoy.
        // Va PEGADA (sticky, no fixed) para no sumar un flotante mas a esta vista
        // (ya compiten el topbar ms-vtb, el nav inferior movil y el FAB del
        // copiloto, y los modales viven dentro de .container con z-index atrapado).
        $hbFechaLarga = format_date($hbFechaProyectada, 'l, d \d\e F \d\e Y');
        $hbFechaEsPasada = $hbFechaProyectada < date('Y-m-d');
    ?>
    <div class="hb-datebar" role="status">
        <span class="hb-datebar__ic" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
        <span class="hb-datebar__txt">
            <strong>
                <span class="hb-datebar__full"><?= htmlspecialchars($hbFechaLarga) ?></span>
                <span class="hb-datebar__short"><?= htmlspecialchars($hbFechaCorta) ?></span>
            </strong>
            <small><?= $hbFechaEsPasada
                ? 'Est&aacute;s viendo una fecha pasada, no hoy'
                : 'Est&aacute;s viendo otra fecha, no hoy' ?></small>
        </span>
        <a href="<?= url('habitaciones') ?>" class="hb-datebar__back">
            <i class="fas fa-rotate-left" aria-hidden="true"></i><span>Volver a hoy</span>
        </a>
    </div>
    <?php endif; ?>
    <!-- Header desktop original -->
    <div class="modern-header" id="mainHeader">
        <div class="container mx-auto px-4 py-3">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-3">
                <div class="flex items-center gap-4">
                    <div class="p-2 rounded-lg shadow-sm" style="background: linear-gradient(135deg, var(--brand-primary, #1B2746), var(--brand-secondary, #0F172A));">
                        <i class="fas fa-bed text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800"><span class="hb-title-prefix">Gesti&oacute;n de </span>Habitaciones</h1>
                        <p class="text-xs text-gray-500 hidden sm:block">Control en tiempo real</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button onclick="mostrarVistaRapida()" title="Abrir vista rápida de habitaciones" class="btn-modern bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-th text-sm"></i>
                        <span class="hidden sm:inline">Vista Rápida</span>
                    </button>
                    <?php if ($tiene_limpieza): ?>
                    <button onclick="mostrarModalLimpieza()" title="Marcar habitaciones en limpieza" class="btn-modern btn-brand-soft hb-cleaning-btn relative">
                        <i class="fas fa-broom text-sm"></i>
                        <span>Limpieza</span>
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"><?= count($habitaciones_limpieza) ?></span>
                    </button>
                    <?php endif; ?>
                    <a href="<?= url('reservaciones/crear') ?>" onclick="return abrirSelectorNuevaReserva(event)" title="Crear una nueva reservación" class="btn-modern btn-brand">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span>Nueva Reserva</span>
                    </a>
                    <?php if (can('habitaciones.create')): ?>
                    <a href="<?= url('habitaciones/create') ?>" title="Registrar una habitación nueva" class="btn-modern btn-brand-outline">
                        <span class="hidden sm:inline">Nueva</span>
                        <span>Habitaci&oacute;n</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php
            // Subnav de seccion DENTRO del header glass: mismo cromado que
            // /areas y /mapa para que el cambio de pestana se sienta continuo.
            $subnav_section = 'habitaciones';
            $subnav_active = 'habitaciones';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
            <style>.habitaciones-view .modern-header .ms-subnav{margin:10px 0 0;}</style>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4 max-w-7xl">
    <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
    <!-- Header móvil (solo visible en móvil ≤767px) -->
    <div class="hb-page-header">
        <div class="hb-page-header__top">
            <h1 class="hb-page-title">Habitaciones</h1>
            <p class="hb-page-subtitle">Control de disponibilidad en tiempo real</p>
        </div>
        <div class="hb-page-actions">
            <a href="<?= url('reservaciones/crear') ?>"
               onclick="return abrirSelectorNuevaReserva(event)"
               title="Crear una nueva reservación"
               class="hb-action-btn hb-action-btn--primary">
                <i class="fas fa-plus"></i>
                <span>Nueva reserva</span>
            </a>
            <button onclick="mostrarVistaRapida()"
                    title="Abrir vista rápida de habitaciones"
                    class="hb-action-btn hb-action-btn--outline">
                <i class="fas fa-th-large"></i>
                <span>Vista r&aacute;pida</span>
            </button>
            <?php if (can('habitaciones.create')): ?>
            <a href="<?= url('habitaciones/create') ?>"
               title="Registrar una habitación nueva"
               class="hb-action-btn hb-action-btn--outline hb-action-btn--desktop-only">
                <i class="fas fa-bed"></i>
                <span>Nueva habitaci&oacute;n</span>
            </a>
            <?php endif; ?>
            <?php if ($tiene_limpieza): ?>
            <button onclick="mostrarModalLimpieza()"
                    title="Marcar habitaciones en limpieza"
                    class="hb-action-btn hb-action-btn--soft hb-action-btn--badge hb-cleaning-btn"
                    data-badge="<?= count($habitaciones_limpieza) ?>">
                <i class="fas fa-broom"></i>
                <span>Limpieza</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
        <?php
        // Franja operativa del día: el TOTAL de cuartos que llegan hoy (dimension
        // aparte de las fichas de estado, que son excluyentes y suman el total).
        $hbLlegadasTotal = (int)($estadisticas['por_llegar_total'] ?? 0);
        $hbLlegadasReservas = (int)($estadisticas['reservas_llegan_hoy'] ?? 0);
        $hbLlegadasLimpieza = (int)($estadisticas['por_llegar_en_limpieza'] ?? 0);
        $hbSalidasHoy = (int)($estadisticas['salidas_hoy'] ?? 0);
        ?>
        <?php if ($hbLlegadasTotal > 0 || $hbSalidasHoy > 0): ?>
        <div class="hb-today-strip" role="note" aria-label="Movimiento de hoy">
            <i class="fas fa-calendar-day" aria-hidden="true"></i>
            <strong>Hoy:</strong>
            <?php if ($hbLlegadasTotal > 0): ?>
            <span><?= $hbLlegadasReservas ?> <?= $hbLlegadasReservas === 1 ? 'reserva llega' : 'reservas llegan' ?> · <?= $hbLlegadasTotal ?> <?= $hbLlegadasTotal === 1 ? 'cuarto por llegar' : 'cuartos por llegar' ?><?php if ($hbLlegadasLimpieza > 0): ?> <em>(<?= $hbLlegadasLimpieza ?> aún en limpieza)</em><?php endif; ?></span>
            <?php endif; ?>
            <?php if ($hbLlegadasTotal > 0 && $hbSalidasHoy > 0): ?><span class="hb-today-sep">·</span><?php endif; ?>
            <?php if ($hbSalidasHoy > 0): ?>
            <span><?= $hbSalidasHoy ?> <?= $hbSalidasHoy === 1 ? 'salida' : 'salidas' ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <!-- Widgets de estado (6, semánticos, excluyentes: suman el total) -->
        <div class="hb-stats" id="hbStats">
            <div class="hb-stat hb-stat--total" data-estado="" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="Mostrar todas las habitaciones">
                <span class="hb-stat-ic"><i class="fas fa-door-closed"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['total'] ?? 0 ?></span>
                <span class="hb-stat-l">Total</span>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="hb-stat hb-stat--available" data-estado="disponible" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="<?= $hbFechaProyectada !== '' ? 'Filtrar habitaciones vendibles el ' . htmlspecialchars($hbFechaCorta) : 'Filtrar habitaciones libres (sin llegada hoy)' ?>">
                <span class="hb-stat-ic"><i class="fas fa-check-circle"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['libres_hoy'] ?? ($estadisticas['disponibles'] ?? 0) ?></span>
                <span class="hb-stat-l"><?= $hbLabelLibres ?></span>
                <?php if ($hbFechaProyectada !== ''): ?>
                <span class="hb-stat-sub">vendibles el <?= htmlspecialchars($hbFechaCorta) ?></span>
                <?php endif; ?>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="hb-stat hb-stat--occupied" data-estado="ocupada" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="Filtrar habitaciones ocupadas">
                <span class="hb-stat-ic"><i class="fas fa-bed"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['ocupadas'] ?? 0 ?></span>
                <span class="hb-stat-l">Ocupada</span>
                <?php if ($hbFechaProyectada !== ''): ?>
                <span class="hb-stat-sub">estancias que vienen de antes</span>
                <?php endif; ?>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="hb-stat hb-stat--arriving" data-estado="por_llegar" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="<?= $hbFechaProyectada !== '' ? 'Filtrar reservaciones que entran el ' . htmlspecialchars($hbFechaCorta) : 'Filtrar llegadas de hoy con cuarto listo' ?>">
                <span class="hb-stat-ic"><i class="fas fa-clock"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['por_llegar'] ?? 0 ?></span>
                <span class="hb-stat-l">Por llegar</span>
                <?php if ($hbFechaProyectada !== ''): ?>
                <span class="hb-stat-sub">entran el <?= htmlspecialchars($hbFechaCorta) ?></span>
                <?php endif; ?>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="hb-stat hb-stat--cleaning" data-estado="limpieza" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="Filtrar habitaciones en limpieza">
                <span class="hb-stat-ic"><i class="fas fa-broom"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['limpieza'] ?? 0 ?></span>
                <span class="hb-stat-l">Limpieza</span>
                <?php if ($hbFechaProyectada !== ''): ?>
                <span class="hb-stat-sub">no aplica en otra fecha</span>
                <?php elseif (!empty($estadisticas['por_llegar_en_limpieza'])): ?>
                <span class="hb-stat-sub"><?= (int)$estadisticas['por_llegar_en_limpieza'] ?> para llegadas de hoy</span>
                <?php endif; ?>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
            <div class="hb-stat hb-stat--maint" data-estado="mantenimiento" role="button" tabindex="0" onclick="hbSetEstado(this)" onkeydown="hbStatKey(event, this)" title="Filtrar habitaciones en mantenimiento">
                <span class="hb-stat-ic"><i class="fas fa-wrench"></i></span>
                <span class="hb-stat-n"><?= $estadisticas['mantenimiento'] ?? 0 ?></span>
                <span class="hb-stat-l">Mantenimiento</span>
                <?php if ($hbFechaProyectada !== ''): ?>
                <span class="hb-stat-sub">estado de hoy, no proyectado</span>
                <?php endif; ?>
                <span class="hb-stat-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
            </div>
        </div>
        <?php
        $estadoActual = $filtros['estado'] ?? '';
        $hbMobileSegments = [
            [$hbLabelLibres, (int)($estadisticas['libres_hoy'] ?? ($estadisticas['disponibles'] ?? 0)), 'var(--c-available)', 'disponible'],
            ['Ocupada', (int)($estadisticas['ocupadas'] ?? 0), 'var(--c-occupied)', 'ocupada'],
            ['Por llegar', (int)($estadisticas['por_llegar'] ?? 0), 'var(--c-arriving)', 'por_llegar'],
            ['Limpieza', (int)($estadisticas['limpieza'] ?? 0), 'var(--c-cleaning)', 'limpieza'],
            ['Mantenimiento', (int)($estadisticas['mantenimiento'] ?? 0), 'var(--c-maint)', 'mantenimiento'],
        ];
        $hbMobileTotalBar = 0;
        foreach ($hbMobileSegments as $hbMobileSeg) {
            $hbMobileTotalBar += $hbMobileSeg[1];
        }
        $hbMobileTotalBar = max(1, $hbMobileTotalBar);
        ?>
        <div class="hb-mobile-occupancy" aria-label="Resumen movil de ocupacion">
            <div class="hb-mobile-occupancy-head">
                <span>Ocupaci&oacute;n general</span>
                <strong>
                    <b><?= (int)($estadisticas['ocupadas'] ?? 0) ?> / <?= (int)($estadisticas['total'] ?? 0) ?></b>
                    <small>habitaciones</small>
                </strong>
            </div>
            <div class="hb-mobile-occbar">
                <?php foreach ($hbMobileSegments as $hbMobileSeg): ?>
                    <?php if ($hbMobileSeg[1] > 0): ?>
                        <span style="width: <?= round(($hbMobileSeg[1] / $hbMobileTotalBar) * 100, 3) ?>%; background: <?= $hbMobileSeg[2] ?>;"></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <div class="hb-mobile-legend">
                <?php foreach ($hbMobileSegments as $hbMobileSeg): ?>
                    <button type="button" data-estado="<?= $hbMobileSeg[3] ?>" onclick="hbSetEstado(this)" class="hb-mobile-lg<?= $estadoActual === $hbMobileSeg[3] ? ' is-active' : '' ?>" title="Filtrar habitaciones: <?= htmlspecialchars($hbMobileSeg[0]) ?>">
                        <span class="hb-mobile-dot" style="background: <?= $hbMobileSeg[2] ?>;"></span>
                        <span><?= $hbMobileSeg[0] ?></span>
                        <b><?= $hbMobileSeg[1] ?></b>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
           <?php
        $total_alertas = count($checkouts_vencidos ?? []) + count($checkins_pendientes ?? []) + count($llegadas_tardias ?? []);
        if ($total_alertas > 0):
        ?>

        <style>
        /* Alertas pendientes: misma familia visual que los paneles hb-move (check-ins/outs del día).
           Acordeón siempre cerrado al cargar; el resumen por tipo queda visible en la cabecera. */
        .habitaciones-view .hb-alerts{
            margin-bottom: 1rem;
            border-color: color-mix(in srgb, var(--hb-late, #C9322B) 32%, var(--hb-line, #E2D9C8)) !important;
            border-left: 4px solid color-mix(in srgb, var(--hb-late, #C9322B) 78%, var(--hb-line, #E2D9C8)) !important;
            background:
                radial-gradient(circle at 97% 0%, color-mix(in srgb, var(--hb-late, #C9322B) 10%, transparent), transparent 11rem),
                linear-gradient(135deg, #FFFDFC, color-mix(in srgb, var(--hb-late, #C9322B) 5%, #FFF8F5)) !important;
            box-shadow: 0 16px 32px -26px color-mix(in srgb, var(--hb-late, #C9322B) 60%, transparent) !important;
        }
        .habitaciones-view .hb-alerts-toggle{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 11px 14px;
            border: 0;
            background: transparent;
            font-family: inherit;
            text-align: left;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            transition: background-color .16s ease;
        }
        .habitaciones-view .hb-alerts-toggle:hover{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 4%, transparent);
        }
        /* Variante estatica (proyectando a otra fecha): informa, no se despliega.
           Sin cursor de clic ni hover, porque no hay nada que abrir. */
        .habitaciones-view .hb-alerts.is-static .hb-alerts-toggle{ cursor: default; }
        .habitaciones-view .hb-alerts.is-static .hb-alerts-toggle:hover{ background: transparent; }
        .habitaciones-view .hb-alerts-goto-hoy{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
            padding: 6px 11px;
            border-radius: 8px;
            background: color-mix(in srgb, var(--hb-late, #C9322B) 10%, #FFF);
            color: color-mix(in srgb, var(--hb-late, #C9322B) 72%, var(--hb-primary, #1B2746));
            font-size: .72rem;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }
        .habitaciones-view .hb-alerts-goto-hoy:hover{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 18%, #FFF);
        }
        html[data-theme="dark"] .habitaciones-view .hb-alerts-goto-hoy{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 26%, #1C1C1E);
            color: #F5F5F7;
        }
        @media (max-width:640px){
            .habitaciones-view .hb-alerts-goto-hoy span{ display: none; }
            .habitaciones-view .hb-alerts-goto-hoy{ padding: 6px 9px; }
        }
        .habitaciones-view .hb-alerts-toggle .hb-move-title strong{
            color: color-mix(in srgb, var(--hb-late, #C9322B) 46%, var(--hb-primary, #1B2746));
        }
        .habitaciones-view .hb-alerts-toggle .hb-move-title small{
            color: color-mix(in srgb, var(--hb-late, #C9322B) 30%, var(--hb-slate-400, #94A3B8));
        }
        .habitaciones-view .hb-alerts-toggle:focus-visible{
            outline: 2px solid color-mix(in srgb, var(--hb-accent, #BD9441) 72%, #fff);
            outline-offset: -2px;
            border-radius: 14px;
        }
        .habitaciones-view .hb-alerts.is-open .hb-alerts-toggle{
            border-bottom: 1px solid var(--hb-line, #E2D9C8);
        }
        .habitaciones-view .hb-alerts-mark-wrap{
            position: relative;
            flex: 0 0 auto;
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 11px;
            background: var(--hb-late, #C9322B);
            color: #FFFDFB;
            font-size: .82rem;
            box-shadow: 0 10px 18px -12px color-mix(in srgb, var(--hb-late, #C9322B) 90%, transparent);
        }
        .habitaciones-view .hb-alerts-mark-wrap::after{
            content: "";
            position: absolute;
            top: -2px;
            right: -2px;
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: var(--hb-accent, #BD9441);
            box-shadow: 0 0 0 2px #fff;
            animation: hbAlertsPing 2.4s ease-out infinite;
        }
        @keyframes hbAlertsPing{
            0%, 72%, 100% { outline: 0 solid transparent; }
            36% { outline: 5px solid color-mix(in srgb, var(--hb-accent, #BD9441) 30%, transparent); }
        }
        @media (prefers-reduced-motion: reduce){
            .habitaciones-view .hb-alerts-mark-wrap::after{ animation: none; }
        }
        .habitaciones-view .hb-alerts-sum{
            display: flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
        }
        .habitaciones-view .hb-alerts-chip{
            display: inline-flex;
            align-items: center;
            gap: 5px;
            min-height: 26px;
            padding: 0 10px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .habitaciones-view .hb-alerts-chip i{ font-size: .64rem; }
        .habitaciones-view .hb-alerts-chip em{ font-style: normal; font-weight: 650; }
        .habitaciones-view .hb-alerts-chip--late{
            background: #fff;
            border-color: color-mix(in srgb, var(--hb-late, #C9322B) 34%, #fff);
            color: var(--hb-late, #C9322B);
            box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--hb-late, #C9322B) 80%, transparent);
        }
        .habitaciones-view .hb-alerts-chip--pending{
            background: #fff;
            border-color: color-mix(in srgb, var(--c-maint, #D97706) 36%, #fff);
            color: color-mix(in srgb, var(--c-maint, #D97706) 86%, #000);
            box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--c-maint, #D97706) 70%, transparent);
        }
        .habitaciones-view .hb-alerts-chip--today{
            background: #fff;
            border-color: color-mix(in srgb, var(--c-arriving, #7C3AED) 32%, #fff);
            color: var(--c-arriving, #7C3AED);
            box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--c-arriving, #7C3AED) 70%, transparent);
        }
        .habitaciones-view .hb-alerts-chev{
            flex: 0 0 auto;
            width: 26px;
            height: 26px;
            display: grid;
            place-items: center;
            border: 1px solid var(--hb-line, #E2D9C8);
            border-radius: 999px;
            background: #fff;
            color: var(--hb-slate-400, #94A3B8);
            font-size: .62rem;
            transition: transform .28s ease, color .16s ease;
        }
        .habitaciones-view .hb-alerts.is-open .hb-alerts-chev{
            transform: rotate(180deg);
            color: var(--hb-primary, #1B2746);
        }
        .habitaciones-view .hb-alerts-collapse{
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows .3s ease;
        }
        .habitaciones-view .hb-alerts.is-open .hb-alerts-collapse{
            grid-template-rows: 1fr;
        }
        .habitaciones-view .hb-alerts-collapse-inner{
            overflow: hidden;
            min-height: 0;
        }
        .habitaciones-view .hb-alerts-body{
            max-height: 264px;
        }
        @media (max-width: 640px){
            .habitaciones-view .hb-alerts-chip em{ display: none; }
            .habitaciones-view .hb-alerts-toggle .hb-move-title small{ display: none; }
        }
        .habitaciones-view .hb-alerts-kicker{
            display: flex;
            align-items: center;
            gap: 7px;
            margin: 10px 6px 5px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--hb-slate-400, #94A3B8);
        }
        .habitaciones-view .hb-alerts-kicker:first-child{ margin-top: 4px; }
        .habitaciones-view .hb-alerts-kicker i{ font-size: .66rem; }
        .habitaciones-view .hb-alerts-kicker--late i{ color: var(--hb-late, #C9322B); }
        .habitaciones-view .hb-alerts-kicker--pending i{ color: var(--c-maint, #D97706); }
        .habitaciones-view .hb-alerts-kicker--today i{ color: var(--c-arriving, #7C3AED); }
        .habitaciones-view .hb-alerts-item{
            gap: 10px;
        }
        .habitaciones-view .hb-alerts-item[data-href]{
            cursor: pointer;
            transition: background-color .16s ease, border-color .16s ease, box-shadow .16s ease;
        }
        .habitaciones-view .hb-alerts-item[data-href]:hover{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 4%, #fff);
            border-color: color-mix(in srgb, var(--hb-late, #C9322B) 18%, var(--hb-line, #E2D9C8));
        }
        .habitaciones-view .hb-alerts-item[data-href]:focus-visible{
            outline: 2px solid color-mix(in srgb, var(--hb-accent, #BD9441) 70%, transparent);
            outline-offset: -2px;
            border-radius: 12px;
        }
        .habitaciones-view .hb-alerts-ico{
            flex: 0 0 auto;
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            font-size: .74rem;
        }
        .habitaciones-view .hb-alerts-ico--late{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 10%, #fff);
            color: var(--hb-late, #C9322B);
        }
        .habitaciones-view .hb-alerts-ico--pending{
            background: color-mix(in srgb, var(--c-maint, #D97706) 10%, #fff);
            color: var(--c-maint, #D97706);
        }
        .habitaciones-view .hb-alerts-ico--today{
            background: color-mix(in srgb, var(--c-arriving, #7C3AED) 10%, #fff);
            color: var(--c-arriving, #7C3AED);
        }
        .habitaciones-view .hb-alerts-item .hb-alerts-info{ flex: 1; min-width: 0; }
        .habitaciones-view .hb-alerts-item p:first-child{
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .habitaciones-view .hb-alerts-item p:last-child b{
            font-weight: 700;
            color: var(--hb-late, #C9322B);
        }
        .habitaciones-view .hb-alerts-item p:last-child a{
            color: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .habitaciones-view .hb-alerts-btn{
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-height: 32px;
            padding: 0 11px;
            border: 1px solid var(--hb-line, #E2D9C8);
            border-radius: 10px;
            background: #fff;
            color: var(--hb-primary, #1B2746);
            font-size: .74rem;
            font-weight: 700;
            white-space: nowrap;
            cursor: pointer;
            text-decoration: none;
            transition: background-color .16s ease, border-color .16s ease, color .16s ease;
        }
        .habitaciones-view .hb-alerts-btn--late{
            border-color: color-mix(in srgb, var(--hb-late, #C9322B) 32%, var(--hb-line, #E2D9C8));
            color: var(--hb-late, #C9322B);
        }
        .habitaciones-view .hb-alerts-btn--late:hover{
            background: color-mix(in srgb, var(--hb-late, #C9322B) 8%, #fff);
        }
        .habitaciones-view .hb-alerts-btn--pending{
            border-color: color-mix(in srgb, var(--c-maint, #D97706) 32%, var(--hb-line, #E2D9C8));
            color: color-mix(in srgb, var(--c-maint, #D97706) 86%, #000);
        }
        .habitaciones-view .hb-alerts-btn--pending:hover{
            background: color-mix(in srgb, var(--c-maint, #D97706) 8%, #fff);
        }
        @media (max-width: 640px){
            .habitaciones-view .hb-alerts-btn span{ display: none; }
            .habitaciones-view .hb-alerts-btn{
                width: 34px;
                justify-content: center;
                padding: 0;
            }
        }
        </style>

        <?php
            // Proyectando a otra fecha el panel se vuelve ESTATICO: las 3 alertas se
            // calculan contra CURDATE() (nadie tiene un "check-out vencido del 17 de
            // septiembre"), asi que re-anclarlas a la fecha consultada no significa
            // nada. Se conserva la senal —un huesped que no se ha ido es urgente
            // aunque estes planeando septiembre— y se quita la ACCION: resolver
            // pendientes de hoy desde la vista de otro dia es el mismo tropiezo que
            // el boton de check-out, que ya se oculta al proyectar. La salida es
            // "Volver a hoy", que lleva a donde si se puede actuar.
            $hbAlertasEstaticas = $hbFechaProyectada !== '';
        ?>
        <div class="hb-move-card hb-alerts<?= $hbAlertasEstaticas ? ' is-static' : '' ?>" id="hbAlertsCard" role="region" aria-label="Alertas pendientes">
            <?php if ($hbAlertasEstaticas): ?>
            <div class="hb-alerts-toggle">
                <span class="hb-move-title">
            <?php else: ?>
            <button type="button"
                    class="hb-alerts-toggle"
                    aria-expanded="false"
                    aria-controls="hbAlertsCollapse"
                    onclick="hbToggleAlertas()">
                <span class="hb-move-title">
            <?php endif; ?>
                    <span class="hb-alerts-mark-wrap"><i class="fas fa-exclamation-triangle"></i></span>
                    <span>
                        <strong>Alertas pendientes</strong>
                        <?php // Se calculan contra CURDATE(): al proyectar hay que decir de cuando son. ?>
                        <small><?= $hbFechaProyectada !== ''
                            ? 'De hoy, no del ' . htmlspecialchars($hbFechaCorta)
                            : 'Toca para revisar el detalle' ?></small>
                    </span>
                </span>
                <span class="hb-alerts-sum">
                    <?php if (!empty($checkouts_vencidos)): ?>
                    <span class="hb-alerts-chip hb-alerts-chip--late" title="Check-outs vencidos">
                        <i class="fas fa-door-open"></i><?= count($checkouts_vencidos) ?> <em>vencido<?= count($checkouts_vencidos) > 1 ? 's' : '' ?></em>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($checkins_pendientes)): ?>
                    <span class="hb-alerts-chip hb-alerts-chip--pending" title="Check-ins pendientes">
                        <i class="fas fa-user-clock"></i><?= count($checkins_pendientes) ?> <em>sin check-in</em>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($llegadas_tardias)): ?>
                    <span class="hb-alerts-chip hb-alerts-chip--today" title="Llegadas tardías hoy">
                        <i class="fas fa-clock"></i><?= count($llegadas_tardias) ?> <em>hoy</em>
                    </span>
                    <?php endif; ?>
                    <?php if ($hbAlertasEstaticas): ?>
                    <a href="<?= url('habitaciones') ?>" class="hb-alerts-goto-hoy">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i><span>Volver a hoy</span>
                    </a>
                    <?php else: ?>
                    <span class="hb-alerts-chev"><i class="fas fa-chevron-down"></i></span>
                    <?php endif; ?>
                </span>
            <?= $hbAlertasEstaticas ? '</div>' : '</button>' ?>

            <?php if (!$hbAlertasEstaticas): ?>
            <div class="hb-alerts-collapse" id="hbAlertsCollapse">
            <div class="hb-alerts-collapse-inner">
            <div class="hb-move-body hb-alerts-body">

                <?php if (!empty($checkouts_vencidos)): ?>
                <p class="hb-alerts-kicker hb-alerts-kicker--late">
                    <i class="fas fa-door-open"></i>
                    Check-outs vencidos · <?= count($checkouts_vencidos) ?>
                </p>
                <?php foreach ($checkouts_vencidos as $checkout): ?>
                <div class="hb-move-item hb-alerts-item"
                     data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$checkout['id']), ENT_QUOTES, 'UTF-8') ?>"
                     role="link"
                     tabindex="0"
                     title="Abrir reservacion"
                     aria-label="Abrir reservacion de <?= htmlspecialchars($checkout['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="hb-alerts-ico hb-alerts-ico--late"><i class="fas fa-sign-out-alt"></i></span>
                    <div class="hb-alerts-info">
                        <p><?= htmlspecialchars($checkout['nombre_completo']) ?></p>
                        <p>
                            Hab. <?= htmlspecialchars($checkout['habitaciones']) ?>
                            · Salida <?= date('d/m/Y', strtotime($checkout['fecha_salida'])) ?>
                            · <b><?= $checkout['dias_retraso'] ?> día<?= $checkout['dias_retraso'] > 1 ? 's' : '' ?> de retraso</b>
                        </p>
                    </div>
                    <button type="button"
                            onclick="confirmarCheckOut(<?= $checkout['id'] ?>)"
                            title="Realizar check-out de esta reservación"
                            class="hb-alerts-btn hb-alerts-btn--late">
                        <i class="fas fa-sign-out-alt"></i><span>Check-out</span>
                    </button>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($checkins_pendientes)): ?>
                <p class="hb-alerts-kicker hb-alerts-kicker--pending">
                    <i class="fas fa-user-clock"></i>
                    Check-ins pendientes · <?= count($checkins_pendientes) ?>
                </p>
                <?php foreach ($checkins_pendientes as $checkin): ?>
                <div class="hb-move-item hb-alerts-item"
                     data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$checkin['id']), ENT_QUOTES, 'UTF-8') ?>"
                     role="link"
                     tabindex="0"
                     title="Abrir reservacion"
                     aria-label="Abrir reservacion de <?= htmlspecialchars($checkin['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="hb-alerts-ico hb-alerts-ico--pending"><i class="fas fa-user-clock"></i></span>
                    <div class="hb-alerts-info">
                        <p><?= htmlspecialchars($checkin['nombre_completo']) ?></p>
                        <p>
                            Hab. <?= htmlspecialchars($checkin['habitaciones']) ?>
                            · Llegada <?= date('d/m/Y', strtotime($checkin['fecha_entrada'])) ?>
                            · <?= $checkin['dias_retraso'] ?> día<?= $checkin['dias_retraso'] > 1 ? 's' : '' ?> sin check-in
                            <?php if ($checkin['telefono']): ?>
                                · <a href="tel:<?= htmlspecialchars($checkin['telefono']) ?>"><?= htmlspecialchars($checkin['telefono']) ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= url('reservaciones/ver/' . $checkin['id'] . '?checkin_return_to=habitaciones') ?>#checkin"
                       title="Abrir reservación para hacer check-in"
                       class="hb-alerts-btn hb-alerts-btn--pending">
                        <i class="fas fa-sign-in-alt"></i><span>Check-in</span>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!empty($llegadas_tardias)): ?>
                <p class="hb-alerts-kicker hb-alerts-kicker--today">
                    <i class="fas fa-clock"></i>
                    Llegadas tardías hoy · <?= count($llegadas_tardias) ?>
                </p>
                <?php foreach ($llegadas_tardias as $tardio): ?>
                <div class="hb-move-item hb-alerts-item"
                     data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$tardio['id']), ENT_QUOTES, 'UTF-8') ?>"
                     role="link"
                     tabindex="0"
                     title="Abrir reservacion"
                     aria-label="Abrir reservacion de <?= htmlspecialchars($tardio['nombre_completo'] ?? 'huesped', ENT_QUOTES, 'UTF-8') ?>">
                    <span class="hb-alerts-ico hb-alerts-ico--today"><i class="fas fa-clock"></i></span>
                    <div class="hb-alerts-info">
                        <p><?= htmlspecialchars($tardio['nombre_completo']) ?></p>
                        <p>
                            Hab. <?= htmlspecialchars($tardio['habitaciones']) ?>
                            · Hora estimada <?= substr($tardio['hora_llegada_estimada'], 0, 5) ?>
                            <?php if ($tardio['telefono']): ?>
                                · <a href="tel:<?= htmlspecialchars($tardio['telefono']) ?>"><?= htmlspecialchars($tardio['telefono']) ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="<?= url('reservaciones/ver/' . $tardio['id']) ?>"
                       title="Ver detalle de la reservación"
                       class="hb-move-action hb-alerts-go" aria-label="Ver reservación">
                        <i class="fas fa-arrow-right text-sm"></i>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

            </div>
            </div>
            </div>
            <?php endif; ?>
        </div>

        <script>
        function hbToggleAlertas() {
            const card = document.getElementById('hbAlertsCard');
            const btn = card.querySelector('.hb-alerts-toggle');
            const open = card.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        (function () {
            const card = document.getElementById('hbAlertsCard');
            if (!card) {
                return;
            }

            function alertRowFromTarget(target) {
                if (!(target instanceof Element)) {
                    return null;
                }

                if (target.closest('a, button, input, textarea, select, label')) {
                    return null;
                }

                return target.closest('.hb-alerts-item[data-href]');
            }

            function openAlertRow(row, event) {
                const href = row.getAttribute('data-href');
                if (!href) {
                    return;
                }

                if (event && (event.ctrlKey || event.metaKey || event.button === 1)) {
                    window.open(href, '_blank', 'noopener');
                    return;
                }

                window.location.href = href;
            }

            card.addEventListener('click', function (event) {
                const row = alertRowFromTarget(event.target);
                if (!row) {
                    return;
                }

                openAlertRow(row, event);
            });

            card.addEventListener('auxclick', function (event) {
                if (event.button !== 1) {
                    return;
                }

                const row = alertRowFromTarget(event.target);
                if (!row) {
                    return;
                }

                event.preventDefault();
                openAlertRow(row, event);
            });

            card.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter' && event.key !== ' ') {
                    return;
                }

                const row = event.target.closest && event.target.closest('.hb-alerts-item[data-href]');
                if (!row || row !== event.target) {
                    return;
                }

                event.preventDefault();
                openAlertRow(row, event);
            });
        })();
        </script>

        <?php endif; ?>
        <!-- Barra de Filtros Compacta -->
        <!-- Reemplazar toda la sección de "Barra de Filtros Compacta" con esto: -->
<div class="bg-white rounded-xl shadow-sm p-2 sm:p-3 mb-4 hb-filter-panel">
    <?php $hbFechaActiva = !empty($filtros['fecha_consulta']); ?>

    <?php
        $hbChips = [
            ''              => ['Todas',         (int)($estadisticas['total'] ?? 0),         ''],
            'disponible'    => [$hbLabelLibres,  (int)($estadisticas['libres_hoy'] ?? ($estadisticas['disponibles'] ?? 0)), 'available'],
            'ocupada'       => ['Ocupada',       (int)($estadisticas['ocupadas'] ?? 0),      'occupied'],
            'por_llegar'    => ['Por llegar',    (int)($estadisticas['por_llegar'] ?? 0),    'arriving'],
            'limpieza'      => ['Limpieza',      (int)($estadisticas['limpieza'] ?? 0),      'cleaning'],
            'mantenimiento' => ['Mantenimiento', (int)($estadisticas['mantenimiento'] ?? 0), 'maint'],
        ];
        ?>
        <div class="hb-filterbar">
            <div class="hb-search">
                <i class="fas fa-search"></i>
                <input type="text" id="hbSearch" value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>" placeholder="Buscar nº, tipo o huésped…" autocomplete="off" oninput="hbApplyFilters()">
            </div>
            <button type="button" class="hb-filter-trigger" onclick="document.querySelector('.hb-chips')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });" title="Ver filtros" aria-label="Ver filtros">
                <i class="fas fa-sliders-h" aria-hidden="true"></i>
            </button>
            <span class="hb-fdiv"></span>
            <div class="hb-chips">
                <?php foreach ($hbChips as $val => $def): ?>
                    <button type="button" data-estado="<?= $val ?>" onclick="hbSetEstado(this)" title="Filtrar habitaciones: <?= htmlspecialchars($def[0]) ?>"
                            class="hb-chip<?= $estadoActual === $val ? ' is-active' : '' ?><?= $def[2] ? ' chip-'.$def[2] : '' ?>">
                        <?php if ($def[2]): ?><span class="hb-chip-dot"></span><?php endif; ?>
                        <?= $def[0] ?> <span class="hb-chip-ct"><?= $def[1] ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <form method="GET" action="<?= url('habitaciones') ?>" class="hb-filter-right" data-auto-filter-form>
                <input type="date" name="fecha_consulta" id="fecha_consulta" value="<?= $filtros['fecha_consulta'] ?? '' ?>" class="filter-date<?= $hbFechaActiva ? ' is-active' : '' ?>" title="Disponibilidad en fecha">
                <input type="hidden" name="mostrar_disponibilidad" value="1">
                <a href="<?= url('habitaciones') ?>" class="filter-btn filter-btn-today" title="Volver a hoy">
                    <i class="fas fa-calendar-day"></i><span class="hidden sm:inline">Hoy</span>
                </a>
                <button type="button" class="filter-btn filter-btn-reset" onclick="hbClearFilters()" title="Limpiar filtros">
                    <i class="fas fa-redo-alt"></i><span class="hidden sm:inline">Limpiar</span>
                </button>
            </form>
        </div>
</div>

<style>
.hb-filter-panel .filter-date.is-active{
    border-color:#3B7DD8 !important;
    background:#EDF4FC !important;
    box-shadow:0 0 0 2px rgba(59,125,216,.18);
    font-weight:700;
}
</style>
<?php if (!empty($filtros['fecha_consulta']) && $filtros['fecha_consulta'] != date('Y-m-d')): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (window.msToast) window.msToast('info', 'Disponibilidad', 'Mostrando habitaciones para el <?= format_date($filtros['fecha_consulta']) ?>');
});
</script>
<?php endif; ?>

        <!-- Movimientos del día -->
        <?php
        $fecha_consulta = !empty($filtros['fecha_consulta']) ? $filtros['fecha_consulta'] : date('Y-m-d');
        $mostrar_movimientos = !empty($filtros['mostrar_disponibilidad']) || !empty($filtros['fecha_consulta']) || $fecha_consulta == date('Y-m-d');
        $es_filtro_fecha = !empty($filtros['fecha_consulta']) && $filtros['fecha_consulta'] != date('Y-m-d');
        // "Hoy," solo cuando de verdad es hoy: los paneles rotulaban "Hoy, 01/08/2026".
        $hb_mov_prefijo = $fecha_consulta === date('Y-m-d') ? 'Hoy, ' : '';
        ?>
        <?php if ($mostrar_movimientos): ?>
            <?php
            $db = Database::getInstance();
            // Establecer zona horaria de MySQL a México
            $db->query("SET time_zone = '-06:00'");

            // Check-ins del día
            $sql_checkins = "SELECT r.*, h.nombre_completo, h.telefono,
                            GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                            GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids
                            FROM reservaciones r
                            INNER JOIN huespedes h ON r.huesped_id = h.id
                            INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                            INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                            WHERE r.hotel_id = ?
                            AND DATE(r.fecha_entrada) = ?
                            AND r.estado = 'confirmada'
                            GROUP BY r.id
                            ORDER BY r.hora_llegada_estimada";
            $stmt = $db->query($sql_checkins, [$hotel_id_actual, $fecha_consulta]);
            $checkins_dia = $stmt->fetchAll();
            // Check-outs del dia. Antes se suprimian ENTEROS al filtrar por fecha y el
            // panel mentia "No hay check-outs programados" sin haber consultado nada.
            // HOY conserva su criterio exacto ('checked_in' = cola de trabajo real, la
            // unica con boton de accion); en OTRA fecha es una proyeccion, con el mismo
            // juego de estados que la consulta de ocupadas: las confirmadas que terminan
            // ese dia (aun no llegan, pero saldran) y, hacia atras, las ya cerradas.
            $checkouts_estados = $es_filtro_fecha
                ? ['confirmada', 'checked_in', 'checked_out']
                : ['checked_in'];
            $checkouts_in = implode(',', array_fill(0, count($checkouts_estados), '?'));
            $sql_checkouts = "SELECT r.*, h.nombre_completo, h.telefono,
                             GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                             GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids
                             FROM reservaciones r
                             INNER JOIN huespedes h ON r.huesped_id = h.id
                             INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                             INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                             WHERE r.hotel_id = ?
                             AND DATE(r.fecha_salida) = ?
                             AND r.estado IN ($checkouts_in)
                             GROUP BY r.id
                             ORDER BY r.hora_entrada";
            $stmt = $db->query($sql_checkouts, array_merge([$hotel_id_actual, $fecha_consulta], $checkouts_estados));
            $checkouts_dia = $stmt->fetchAll();

            // Crear mapa de habitaciones con doble movimiento
            $habitaciones_doble_movimiento = [];
            foreach ($checkouts_dia as $checkout) {
                $hab_ids = explode(',', $checkout['habitaciones_ids']);
                foreach ($hab_ids as $hab_id) {
                    $habitaciones_doble_movimiento[$hab_id]['checkout'] = $checkout;
                }
            }
            foreach ($checkins_dia as $checkin) {
                $hab_ids = explode(',', $checkin['habitaciones_ids']);
                foreach ($hab_ids as $hab_id) {
                    $habitaciones_doble_movimiento[$hab_id]['checkin'] = $checkin;
                }
            }
            ?>

            <?php if (true || count($checkins_dia) > 0 || count($checkouts_dia) > 0): ?>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4 hb-movements">
                <!-- Panel de Check-ins -->
                <div class="bg-white rounded-lg shadow-sm hb-move-card hb-move-card--in">
                    <div class="bg-purple-50 p-3 rounded-t-lg border-b border-purple-100 hb-move-head hb-move-head--in">
                        <h3 class="text-sm font-semibold text-purple-800 flex items-center justify-between">
                            <span class="hb-move-title">
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                <span>
                                    <strong>Check-ins</strong>
                                    <small><?= $hb_mov_prefijo ?><?= format_date($fecha_consulta) ?></small>
                                </span>
                            </span>
                            <span class="bg-purple-600 text-white text-xs px-2 py-0.5 rounded-full">
                                <?= count($checkins_dia) ?>
                            </span>
                        </h3>
                    </div>
                    <div class="p-2 max-h-48 overflow-y-auto hb-move-body">
                        <?php if (empty($checkins_dia)): ?>
                            <p class="text-gray-500 text-center py-3 text-sm hb-move-empty">No hay check-ins programados</p>
                        <?php else: ?>
                            <div class="space-y-1">
                                <?php foreach ($checkins_dia as $checkin): ?>
                                    <div class="flex items-center justify-between p-2 bg-purple-50 rounded hover:bg-purple-100 transition-colors hb-move-item hb-move-item--in"
                                         data-href="<?= htmlspecialchars(url('reservaciones/ver/' . (int)$checkin['id']), ENT_QUOTES, 'UTF-8') ?>"
                                         role="link"
                                         tabindex="0"
                                         title="Abrir reservación"
                                         aria-label="Abrir reservación de <?= htmlspecialchars($checkin['nombre_completo'] ?? 'huésped', ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-800 truncate">
                                                <?= htmlspecialchars($checkin['nombre_completo']) ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <i class="fas fa-bed mr-1"></i><?= htmlspecialchars($checkin['habitaciones_numeros']) ?>
                                                • <?= substr($checkin['hora_llegada_estimada'], 0, 5) ?>
                                            </p>
                                        </div>
                                        <a href="<?= url('reservaciones/ver/' . $checkin['id']) ?>"
                                           class="text-purple-600 hover:text-purple-800 ml-2 hb-move-action"
                                           tabindex="-1" aria-hidden="true">
                                            <i class="fas fa-arrow-right text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Panel de Check-outs -->
                 <div class="bg-white rounded-lg shadow-sm hb-move-card hb-move-card--out">
                    <div class="bg-yellow-50 p-3 rounded-t-lg border-b border-yellow-100 hb-move-head hb-move-head--out">
                        <h3 class="text-sm font-semibold text-yellow-800 flex items-center justify-between">
                            <span class="hb-move-title">
                                <i class="fas fa-sign-out-alt mr-2"></i>
                                <span>
                                    <strong>Check-outs</strong>
                                    <small><?= $hb_mov_prefijo ?><?= format_date($fecha_consulta) ?></small>
                                </span>
                            </span>
                            <span class="bg-yellow-600 text-white text-xs px-2 py-0.5 rounded-full">
                                <?= count($checkouts_dia) ?>
                            </span>
                        </h3>
                    </div>
                    <div class="p-2 max-h-48 overflow-y-auto hb-move-body">
                        <?php if (empty($checkouts_dia)): ?>
                            <p class="text-gray-500 text-center py-3 text-sm hb-move-empty">No hay check-outs programados</p>
                        <?php else: ?>
                            <div class="space-y-1">
                                <?php foreach ($checkouts_dia as $checkout): ?>
                                    <div class="flex items-center justify-between p-2 bg-yellow-50 rounded hover:bg-yellow-100 transition-colors hb-move-item hb-move-item--out">
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-sm text-gray-800 truncate">
                                                <?= htmlspecialchars($checkout['nombre_completo']) ?>
                                            </p>
                                            <p class="text-xs text-gray-600">
                                                <i class="fas fa-bed mr-1"></i><?= htmlspecialchars($checkout['habitaciones_numeros']) ?>
                                                • Hasta <?= htmlspecialchars($hb_hotel_checkout_label) ?>
                                            </p>
                                        </div>
                                        <?php if (!$es_filtro_fecha && $checkout['estado'] == 'checked_in'): ?>
                                            <button onclick="confirmarCheckOut(<?= $checkout['id'] ?>)"
                                                    class="bg-yellow-500 text-white px-2 py-1 rounded text-xs hover:bg-yellow-600 transition-colors ml-2">
                                                Check-out
                                            </button>
                                        <?php elseif ($es_filtro_fecha): ?>
                                            <?php
                                            // Proyeccion: no se puede cerrar HOY una salida de otra fecha.
                                            // En su lugar, en que va esa reservacion (sin enum crudo).
                                            $hb_estado_salida = [
                                                'confirmada'  => 'Aún no llega',
                                                'checked_in'  => 'Hospedado',
                                                'checked_out' => 'Ya salió',
                                            ][$checkout['estado']] ?? 'Programada';
                                            ?>
                                            <span class="text-xs text-gray-500 ml-2 whitespace-nowrap"><?= htmlspecialchars($hb_estado_salida) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <script>
            (function () {
                const zone = document.querySelector('.hb-movements');
                if (!zone) { return; }

                function rowFromTarget(target) {
                    if (!(target instanceof Element)) { return null; }
                    if (target.closest('a, button, input, textarea, select, label')) { return null; }
                    return target.closest('.hb-move-item[data-href]');
                }

                function openRow(row, event) {
                    const href = row.getAttribute('data-href');
                    if (!href) { return; }
                    if (event && (event.ctrlKey || event.metaKey || event.button === 1)) {
                        window.open(href, '_blank', 'noopener');
                        return;
                    }
                    window.location.href = href;
                }

                zone.addEventListener('click', function (event) {
                    const row = rowFromTarget(event.target);
                    if (row) { openRow(row, event); }
                });

                zone.addEventListener('auxclick', function (event) {
                    if (event.button !== 1) { return; }
                    const row = rowFromTarget(event.target);
                    if (!row) { return; }
                    event.preventDefault();
                    openRow(row, event);
                });

                zone.addEventListener('keydown', function (event) {
                    if (event.key !== 'Enter' && event.key !== ' ') { return; }
                    const row = event.target.closest && event.target.closest('.hb-move-item[data-href]');
                    if (!row || row !== event.target) { return; }
                    event.preventDefault();
                    openRow(row, event);
                });
            })();
            </script>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Grid de Habitaciones Compacto -->
        <?php
        // Obtener check-ins vencidos para alertas
        $db = Database::getInstance();
        $db->query("SET time_zone = '-06:00'");
        $sql_checkins_vencidos = "SELECT r.*, h.nombre_completo, h.telefono,
                    GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                    GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids,
                    DATEDIFF(CURDATE(), r.fecha_entrada) as dias_retraso
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                    INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                    WHERE r.hotel_id = ?
                    AND r.fecha_entrada < CURDATE()
                    AND r.estado = 'confirmada'
                    GROUP BY r.id
                    ORDER BY r.fecha_entrada";
        $stmt = $db->query($sql_checkins_vencidos, [$hotel_id_actual]);
        $checkins_vencidos_real = $stmt->fetchAll();

        // Crear array para mapeo rápido de check-ins vencidos
        $habitaciones_con_checkin_vencido = [];
        foreach ($checkins_vencidos_real as $cv) {
            $hab_ids = explode(',', $cv['habitaciones_ids']);
            foreach ($hab_ids as $hab_id) {
                $habitaciones_con_checkin_vencido[trim($hab_id)] = [
                    'reservacion_id' => $cv['id'],
                    'nombre' => $cv['nombre_completo'],
                    'fecha_entrada' => $cv['fecha_entrada'],
                    'dias_retraso' => $cv['dias_retraso']
                ];
            }
        }

        // Obtener check-outs vencidos para alertas
        $sql_checkouts_vencidos = "SELECT r.*, h.nombre_completo, h.telefono,
                    GROUP_CONCAT(hab.numero ORDER BY hab.numero SEPARATOR ', ') as habitaciones_numeros,
                    GROUP_CONCAT(hab.id ORDER BY hab.numero SEPARATOR ',') as habitaciones_ids,
                    DATEDIFF(CURDATE(), r.fecha_salida) as dias_retraso
                    FROM reservaciones r
                    INNER JOIN huespedes h ON r.huesped_id = h.id
                    INNER JOIN reservacion_habitaciones rh ON r.id = rh.reservacion_id AND rh.hotel_id = r.hotel_id
                    INNER JOIN habitaciones hab ON rh.habitacion_id = hab.id AND hab.hotel_id = r.hotel_id
                    WHERE r.hotel_id = ?
                    AND r.fecha_salida < CURDATE()
                    AND r.estado = 'checked_in'
                    GROUP BY r.id
                    ORDER BY r.fecha_salida";
        $stmt = $db->query($sql_checkouts_vencidos, [$hotel_id_actual]);
        $checkouts_vencidos_real = $stmt->fetchAll();

        // Crear array para mapeo rápido de check-outs vencidos
        $habitaciones_con_checkout_vencido = [];
        foreach ($checkouts_vencidos_real as $co) {
            $hab_ids = explode(',', $co['habitaciones_ids']);
            foreach ($hab_ids as $hab_id) {
                $habitaciones_con_checkout_vencido[trim($hab_id)] = [
                    'reservacion_id' => $co['id'],
                    'nombre' => $co['nombre_completo'],
                    'fecha_salida' => $co['fecha_salida'],
                    'dias_retraso' => $co['dias_retraso']
                ];
            }
        }
        ?>

        <div id="habitaciones-grid">
            <?php
            // Ordenar: habitaciones de color primero (se conserva dentro de cada piso)
            usort($habitaciones, function($a, $b) use ($colores_habitacion) {
                $a_es_color = isset($colores_habitacion[strtoupper($a['numero'])]);
                $b_es_color = isset($colores_habitacion[strtoupper($b['numero'])]);
                if ($a_es_color && !$b_es_color) return -1;
                if (!$a_es_color && $b_es_color) return 1;
                return 0;
            });
            // Agrupar por CATEGORIA (tipo de habitacion), no por nivel/piso
            $habitaciones_por_cat = [];
            foreach ($habitaciones as $__hab) {
                $__catKey = hb_room_type_label($__hab, $tipos);
                $habitaciones_por_cat[$__catKey][] = $__hab;
            }
            // Ordenar las categorias por su etiqueta legible
            uksort($habitaciones_por_cat, function($a, $b) use ($tipos){
                return strcasecmp((string)$a, (string)$b);
            });
            // Dentro de cada categoria, ordenar por numero/abecedario (natural)
            foreach ($habitaciones_por_cat as &$__grp) {
                usort($__grp, function($a, $b){ return strnatcasecmp((string)($a['numero'] ?? ''), (string)($b['numero'] ?? '')); });
            }
            unset($__grp);
            ?>
            <?php foreach ($habitaciones_por_cat as $__catKey => $__habs): ?>
                <?php
                // El colapso del badge (room-code-long) se decide POR GRUPO, no por
                // tarjeta: si un nombre del grupo es largo, todo el grupo usa icono.
                // Evita píldora y ✓ mezclados en la misma fila con el mismo estado.
                $__grupoNumeroMax = 0;
                foreach ($__habs as $__h) {
                    $__numTxt = (string)($__h['numero'] ?? '');
                    $__numLen = function_exists('mb_strlen') ? mb_strlen($__numTxt, 'UTF-8') : strlen($__numTxt);
                    if ($__numLen > $__grupoNumeroMax) {
                        $__grupoNumeroMax = $__numLen;
                    }
                }
                ?>
                <section class="floor-section">
                    <div class="floor-label">
                        <span class="floor-t"><?= htmlspecialchars($__catKey !== '' ? $__catKey : 'Otras') ?></span>
                        <span class="floor-rule"></span>
                        <span class="floor-ct"><?= count($__habs) ?> <?= count($__habs) == 1 ? 'habitación' : 'habitaciones' ?></span>
                    </div>
                    <div class="rgrid">
                    <?php foreach ($__habs as $habitacion): ?>
                <?php
                $estado_actual = $habitacion['estado_display'] ?? $habitacion['estado'];
                $estado_fisico = $habitacion['estado'] ?? $estado_actual;
                $esta_consultando_disponibilidad_fecha = !empty($mostrar_disponibilidad_fecha)
                    || (!empty($filtros['fecha_consulta']) && !empty($filtros['mostrar_disponibilidad']));
                $estado_principal = $estado_actual;
                if (!$esta_consultando_disponibilidad_fecha && in_array($estado_fisico, ['limpieza', 'mantenimiento', 'ocupada'], true)) {
                    $estado_principal = $estado_fisico;
                }
                $tieneEstadoLimpiezaOperativa = $estado_fisico === 'limpieza' && !$esta_consultando_disponibilidad_fecha;
                // En modo fecha la reservacion que cubre el dia viaja en info_ocupacion,
                // sea estancia en curso ('ocupada_fecha') o llegada de ese dia
                // ('por_llegar'): las dos pintan huesped, fechas y enlace a la reserva.
                $tieneCompromisoEnFecha = $esta_consultando_disponibilidad_fecha
                    && in_array($estado_actual, ['ocupada_fecha', 'por_llegar'], true)
                    && isset($habitacion['info_ocupacion']);
                $esLlegadaEnFecha = $tieneCompromisoEnFecha && $estado_actual === 'por_llegar';
                $estadoInfo = $estados[$estado_actual] ?? ['label' => 'Desconocido', 'color' => 'gray', 'icon' => 'question'];
                $estadoInfoPrincipal = $estados[$estado_principal] ?? $estadoInfo;

                // Verificar si tiene doble movimiento
                $tiene_doble_movimiento = false;
                // NUEVO: Verificar si está en limpieza Y tiene reservación por llegar
$limpieza_con_por_llegar = false;
if ($tieneEstadoLimpiezaOperativa && isset($habitacion['reservacion_pendiente'])) {
    // La habitación está en limpieza y tiene una reservación pendiente
    $limpieza_con_por_llegar = true;
}
                $info_checkout = null;
                $info_checkin = null;

                if ($mostrar_movimientos && isset($habitaciones_doble_movimiento[$habitacion['id']])) {
                    if (isset($habitaciones_doble_movimiento[$habitacion['id']]['checkout']) &&
                        isset($habitaciones_doble_movimiento[$habitacion['id']]['checkin'])) {
                        $tiene_doble_movimiento = true;
                        $info_checkout = $habitaciones_doble_movimiento[$habitacion['id']]['checkout'];
                        $info_checkin = $habitaciones_doble_movimiento[$habitacion['id']]['checkin'];
                    }
                }

                // Verificar si tiene check-out hoy o vencido usando el array mapeado
                $tiene_checkout_hoy = false;
                $tiene_checkout_vencido = false;
                $info_checkout_vencido = null;

                // Primero verificar si está en el array de check-outs vencidos.
                // "Vencido" se calcula contra CURDATE(): es un hecho de HOY y NO debe
                // repintar la tarjeta cuando estas proyectando a otra fecha (el aviso
                // sigue vivo en el panel de alertas, ahi rotulado como de hoy).
                if (!$esta_consultando_disponibilidad_fecha && isset($habitaciones_con_checkout_vencido[$habitacion['id']])) {
                    $tiene_checkout_vencido = true;
                    $info_checkout_vencido = $habitaciones_con_checkout_vencido[$habitacion['id']];
                } elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])) {
                    // Si no está vencido, verificar si sale hoy
                    $fecha_salida = $habitacion['ocupacion_actual']['fecha_salida'];
                    if ($fecha_salida == date('Y-m-d')) {
                        $tiene_checkout_hoy = true;
                    }
                }

                // Verificar si es llegada tardía o check-in vencido usando el array mapeado
                $es_llegada_tardia = false;
                $es_checkin_vencido = false;
                $info_checkin_vencido = null;

                // Idem check-in vencido: hecho de HOY (DATEDIFF contra CURDATE()). Sin
                // este candado, en la proyeccion ganaba a $tieneCompromisoEnFecha en la
                // cadena de faceGuest y la tarjeta mostraba al huesped atrasado de hoy
                // en vez de quien ocupa el cuarto en la fecha consultada.
                if (!$esta_consultando_disponibilidad_fecha && isset($habitaciones_con_checkin_vencido[$habitacion['id']])) {
                    $es_checkin_vencido = true;
                    $info_checkin_vencido = $habitaciones_con_checkin_vencido[$habitacion['id']];
                } elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])) {
                    // Si no está vencido, verificar si es llegada tardía hoy
                    $fecha_entrada = $habitacion['reservacion_pendiente']['fecha_entrada'] ?? null;
                    if ($fecha_entrada == date('Y-m-d')) {
                        // Si es hoy pero con hora tardía
                        $hora_entrada = $habitacion['reservacion_pendiente']['hora_llegada_estimada'] ?? '14:00:00';
                        if (strtotime($hora_entrada) < strtotime(date('H:i:s'))) {
                            $es_llegada_tardia = true;
                        }
                    }
                }

                $nombrePiso = $pisos[$habitacion['piso']] ?? 'Piso ' . $habitacion['piso'];
                $pisoAbrev = $habitacion['piso'] == 1 ? 'PB' :
                            ($habitacion['piso'] > 0 ? 'P' . $habitacion['piso'] :
                            'S' . abs($habitacion['piso']));
                $habitacionTipoLabel = hb_room_type_label($habitacion, $tipos);
                $habitacionCamasTotal = hb_room_beds_total($habitacion);
                $habitacionCapacidad = (int)($habitacion['capacidad_personas'] ?? 0);
                $habitacionCamasTexto = hb_room_beds_label($habitacion);
                $habitacionCamasDetalle = hb_room_beds_detail_label($habitacion);
                $habitacionCapacidadTexto = hb_room_capacity_label($habitacion);
                $habitacionPropietarioNombre = trim((string)($habitacion['propietario_nombre'] ?? ''));
                $habitacionPropietarioTexto = $habitacionPropietarioNombre !== '' ? $habitacionPropietarioNombre : 'Sin propietario';
                $habitacionPropietarioPct = $habitacion['propietario_participacion_pct'] ?? 100;
                $habitacionPropietarioPctLabel = hb_room_owner_percent_label($habitacionPropietarioPct);

                // Determinar clase de color para el reverso
                // Determinar clase de color para el reverso
if ($tiene_doble_movimiento) {
    $backColorClass = 'back-doble';
} elseif ($es_checkin_vencido) {
    $backColorClass = 'back-checkin-vencido';
} elseif ($tiene_checkout_vencido) {
    $backColorClass = 'back-checkout-vencido';
} elseif ($limpieza_con_por_llegar) {
    $backColorClass = 'back-limpieza-por-llegar';
} else {
    $backColorClass = 'back-' . $estado_principal;
}

                // Color de habitación (Área Confortable)
                $color_hab = $colores_habitacion[strtoupper($habitacion['numero'])] ?? null;
                ?>

                <!-- Tarjeta con flip para todas las habitaciones -->
                <?php
                // Colores (exactos del diseño): el STRIPE usa el color de la habitación
                // (Área Confortable) o el del estado; la HOJA (sheet) usa SIEMPRE el color
                // SEMÁNTICO del ESTADO. Vencido/sin check-in = rojo crítico.
                $stateAccentColors = [
                    'disponible'       => '#1E9E63',
                    'disponible_fecha' => '#1E9E63',
                    'por_llegar'       => '#8039D0',
                    'ocupada'          => '#C2603C',
                    'ocupada_fecha'    => '#C2603C',
                    'mantenimiento'    => '#C2841C',
                    'limpieza'         => '#2F77E0',
                    'doble'            => '#8039D0',
                    'limpieza-por-llegar' => '#2F77E0',
                ];
                $accentColor = $color_hab ?: ($stateAccentColors[$estado_principal] ?? ($stateAccentColors[$estado_actual] ?? '#1E9E63'));
                if ($es_checkin_vencido || $tiene_checkout_vencido) {
                    $sheetColor = '#D64539';
                } else {
                    $sheetColor = $stateAccentColors[$estado_principal] ?? ($stateAccentColors[$estado_actual] ?? '#1E9E63');
                }
                $backStyle = ''; // el fondo de la hoja lo aplica el CSS vía --sheet-c
                $hbSearchStr = strtolower(trim(($habitacion['numero'] ?? '') . ' ' . $habitacionTipoLabel . ' ' . $habitacionPropietarioTexto . ' ' . ($habitacion['ocupacion_actual']['nombre_completo'] ?? '') . ' ' . ($habitacion['reservacion_pendiente']['nombre_completo'] ?? '')));
                $reservacion_detalle_id = null;
                if ($es_checkin_vencido && $info_checkin_vencido) {
                    $reservacion_detalle_id = $info_checkin_vencido['reservacion_id'] ?? null;
                } elseif ($tiene_checkout_vencido && $info_checkout_vencido) {
                    $reservacion_detalle_id = $info_checkout_vencido['reservacion_id'] ?? null;
                } elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])) {
                    $reservacion_detalle_id = $habitacion['reservacion_pendiente']['reservacion_id'] ?? ($habitacion['reservacion_pendiente']['id'] ?? null);
                } elseif ($tieneCompromisoEnFecha && isset($habitacion['info_ocupacion']['reservacion_id'])) {
                    $reservacion_detalle_id = $habitacion['info_ocupacion']['reservacion_id'];
                } elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])) {
                    $reservacion_detalle_id = $habitacion['ocupacion_actual']['id'] ?? ($habitacion['ocupacion_actual']['reservacion_id'] ?? null);
                }
                $reservacion_detalle_id = $reservacion_detalle_id && (int) $reservacion_detalle_id > 0 ? (int) $reservacion_detalle_id : null;
                $puede_checkout_ocupada = $reservacion_detalle_id
                    && !$tiene_checkout_vencido
                    && $habitacion['estado'] == 'ocupada'
                    && isset($habitacion['ocupacion_actual'])
                    && $estado_actual !== 'ocupada_fecha'
                    && can('habitaciones.checkout');
                $hbIncidencias = [];
                if ($es_checkin_vencido && $info_checkin_vencido) {
                    $diasRetraso = (int)($info_checkin_vencido['dias_retraso'] ?? 0);
                    $hbIncidencias[] = [
                        'type' => 'critical',
                        'icon' => 'exclamation-triangle',
                        'label' => 'Sin check-in',
                        'detail' => $diasRetraso > 0 ? ($diasRetraso . ' día' . ($diasRetraso === 1 ? '' : 's')) : 'Vencido',
                    ];
                } elseif ($es_llegada_tardia) {
                    $hbIncidencias[] = [
                        'type' => 'warning',
                        'icon' => 'moon',
                        'label' => 'Llegada tardía',
                        'detail' => '',
                    ];
                }
                if ($limpieza_con_por_llegar && !$es_checkin_vencido) {
                    $hbIncidencias[] = [
                        'type' => 'info',
                        'icon' => 'calendar-day',
                        'label' => 'Reserva pendiente',
                        'detail' => '',
                        'class' => 'reservation-pending',
                    ];
                }
                $tareasResumen = is_array($habitacion['tareas_activas_resumen'] ?? null) ? $habitacion['tareas_activas_resumen'] : [];
                $tareasActivas = (int)($tareasResumen['total_activas'] ?? 0);
                $tareasDetalle = [];
                if ((int)($tareasResumen['limpieza'] ?? 0) > 0) {
                    $tareasDetalle[] = (int)$tareasResumen['limpieza'] . ' limp.';
                }
                if ((int)($tareasResumen['mantenimiento'] ?? 0) > 0) {
                    $tareasDetalle[] = (int)$tareasResumen['mantenimiento'] . ' mant.';
                }
                if ((int)($tareasResumen['general'] ?? 0) > 0) {
                    $tareasDetalle[] = (int)$tareasResumen['general'] . ' gen.';
                }
                if ($tareasActivas > 0) {
                    $hbIncidencias[] = [
                        'type' => 'info',
                        'icon' => 'tasks',
                        'label' => $tareasActivas . ' tarea' . ($tareasActivas === 1 ? '' : 's'),
                        'detail' => implode(' / ', $tareasDetalle),
                    ];
                }
                // Saldo pendiente: chip visible en la cara frontal de la tarjeta
                // (no requiere voltear). Solo aplica a habitaciones ocupadas.
                $saldoOcupFront = (float)($habitacion['ocupacion_actual']['saldo'] ?? 0);
                if ($habitacion['estado'] == 'ocupada' && $saldoOcupFront > 0.004) {
                    $hbIncidencias[] = [
                        'type' => 'warning',
                        'icon' => 'dollar-sign',
                        'label' => 'Saldo pendiente',
                        'detail' => format_money($saldoOcupFront),
                    ];
                }
                $tareasProxLimite = $tareasResumen['proxima_fecha_limite'] ?? null;
                $tareasVencida = $tareasProxLimite && strtotime((string)$tareasProxLimite) && strtotime((string)$tareasProxLimite) < time();
                $habitacionNumeroTexto = (string)($habitacion['numero'] ?? '');
                $habitacionNumeroLongitud = function_exists('mb_strlen')
                    ? mb_strlen($habitacionNumeroTexto, 'UTF-8')
                    : strlen($habitacionNumeroTexto);
                $habitacionNumeroLayoutClass = ($__grupoNumeroMax > 5 ? ' room-code-long' : '')
                    . ($habitacionNumeroLongitud > 8 ? ' room-code-xl' : '');
                ?>
                <div class="flip-card room-card-compact <?= $tiene_checkout_vencido ? 'has-checkout-vencido' : '' ?> <?= $es_checkin_vencido ? 'has-checkin-vencido' : '' ?> <?= $tieneEstadoLimpiezaOperativa ? 'has-cleaning-state' : '' ?><?= $habitacionNumeroLayoutClass ?>"
                      onclick="toggleFlip(this, event)"
                      onkeydown="hbCardKey(event, this)"
                      role="button"
                      tabindex="0"
                      title="Abrir acciones de la habitación <?= htmlspecialchars($habitacion['numero']) ?>"
                      aria-label="Abrir acciones de la habitación <?= htmlspecialchars($habitacion['numero']) ?>"
                      data-habitacion-id="<?= $habitacion['id'] ?>"
                      data-estado="<?= htmlspecialchars($estado_principal) ?>" data-tipo="<?= htmlspecialchars(hb_room_type_code($habitacion, $tipos)) ?>" data-piso="<?= htmlspecialchars($habitacion['piso']) ?>" data-q="<?= htmlspecialchars($hbSearchStr) ?>"
                      style="--room-accent-color: <?= htmlspecialchars($accentColor) ?>; --sheet-c: <?= htmlspecialchars($sheetColor) ?>;">
                    <div class="flip-card-inner">
                        <!-- Parte frontal -->
                        <div class="flip-card-front <?= $tiene_doble_movimiento ? 'estado-doble' : 'estado-' . $estado_principal ?> rounded-lg shadow-sm relative">



                            <?php if ($tiene_checkout_hoy): ?>
                            <div class="checkout-today-indicator">
                                <i class="fas fa-sign-out-alt" style="font-size: 9px;"></i>
                                <span>HOY</span>
                            </div>
                            <?php endif; ?>

                            <?php if ($tiene_checkout_vencido): ?>
                            <div class="checkout-vencido-indicator">
                                <i class="fas fa-exclamation-circle" style="font-size: 10px;"></i>
                                <span class="font-bold">VENCIDO</span>
                            </div>
                            <?php endif; ?>

                            <?php if ($es_llegada_tardia && !$es_checkin_vencido): ?>
                            <div class="late-arrival-indicator">
                                <i class="fas fa-moon" style="font-size: 9px;"></i>
                                <span>TARDÍA</span>
                            </div>
                            <?php endif; ?>

                            <!-- Cara frontal (rediseño boutique habitaciones.html) -->
                            <?php
                            $faceGuest = ''; $faceMeta = '';
                            if ($tiene_doble_movimiento) {
                                $faceGuest = trim(explode(' ', $info_checkout['nombre_completo'])[0] . ' → ' . explode(' ', $info_checkin['nombre_completo'])[0]);
                                $faceMeta = 'Rotación de huéspedes';
                            } elseif ($es_checkin_vencido && $info_checkin_vencido && $estado_principal !== 'limpieza' && $estado_principal !== 'mantenimiento') {
                                $faceGuest = $info_checkin_vencido['nombre'];
                                $faceMeta = 'Sin check-in · ' . $info_checkin_vencido['dias_retraso'] . ' día' . ($info_checkin_vencido['dias_retraso'] > 1 ? 's' : '') . ' de retraso';
                            } elseif ($estado_principal == 'por_llegar' && isset($habitacion['reservacion_pendiente'])) {
                                $faceGuest = $habitacion['reservacion_pendiente']['nombre_completo'];
                                $faceMeta = $es_llegada_tardia ? 'Llegada tardía pendiente' : ('Llega ' . date('g:i A', strtotime($habitacion['reservacion_pendiente']['hora_llegada_estimada'])));
                            } elseif ($estado_principal == 'ocupada' && isset($habitacion['ocupacion_actual'])) {
                                $faceGuest = $habitacion['ocupacion_actual']['nombre_completo'];
                                if ($tiene_checkout_vencido) { $faceMeta = 'Check-out vencido'; }
                                elseif ($tiene_checkout_hoy) { $faceMeta = 'Sale hoy'; }
                                else { $faceMeta = 'Sale ' . format_date($habitacion['ocupacion_actual']['fecha_salida']); }
                            } elseif ($tieneCompromisoEnFecha) {
                                $faceGuest = $habitacion['info_ocupacion']['huesped'] ?? ($esLlegadaEnFecha ? 'Llegada' : 'Ocupada');
                                $faceMeta = $esLlegadaEnFecha ? 'Llega ese día' : 'No disponible en la fecha';
                            } elseif ($estado_principal == 'limpieza') {
                                if ($es_checkin_vencido && $info_checkin_vencido) {
                                    $faceGuest = $info_checkin_vencido['nombre'];
                                } elseif (isset($habitacion['reservacion_pendiente'])) {
                                    $faceGuest = $habitacion['reservacion_pendiente']['nombre_completo'];
                                }
                                $faceMeta = isset($habitacion['reservacion_pendiente']) ? 'En limpieza para llegada' : 'Limpieza pendiente';
                            } elseif ($estado_principal == 'mantenimiento') {
                                $faceMeta = $habitacion['mantenimiento_actual']['tipo_mantenimiento'] ?? 'Mantenimiento';
                            }
                            ?>
                            <div class="rc-face">
                                <span class="rc-stripe" style="background: <?= htmlspecialchars($accentColor) ?>;"></span>
                                <div class="rc-top">
                                    <div class="rc-id">
                                        <?php $rcNumLen = mb_strlen(trim((string) $habitacion['numero'])); ?>
                                        <div class="rc-num<?= $rcNumLen > 8 ? ' rc-num--xl' : ($rcNumLen > 4 ? ' rc-num--long' : '') ?>" title="<?= htmlspecialchars($habitacion['numero']) ?>"><?= htmlspecialchars($habitacion['numero']) ?></div>
                                        <div class="rc-type"><?= $pisoAbrev ?> · <?= htmlspecialchars($habitacionTipoLabel) ?></div>
                                    </div>
                                    <span class="rc-badge" title="Estado: <?= htmlspecialchars($estadoInfoPrincipal['label']) ?>" aria-label="Estado: <?= htmlspecialchars($estadoInfoPrincipal['label']) ?>"><i class="fas fa-<?= $estadoInfoPrincipal['icon'] ?>" aria-hidden="true"></i><span><?= $estadoInfoPrincipal['label'] ?></span></span>
                                </div>
                                <div class="rc-mid">
                                    <?php if ($faceGuest !== ''): ?>
                                        <div class="rc-guest"><i class="fas fa-user"></i><span><?= htmlspecialchars($faceGuest) ?></span></div>
                                    <?php else: ?>
                                        <div class="rc-guest rc-guest--empty"><i class="fas fa-bed"></i><span>Sin huésped</span></div>
                                    <?php endif; ?>
                                    <?php if (!empty($hbIncidencias)): ?>
                                        <div class="rc-incidents" aria-label="Incidencias de la habitación">
                                            <?php foreach ($hbIncidencias as $incidencia): ?>
                                                <span class="rc-incident rc-incident--<?= htmlspecialchars($incidencia['type']) ?><?= !empty($incidencia['class']) ? ' rc-incident--' . htmlspecialchars($incidencia['class']) : '' ?>">
                                                    <i class="fas fa-<?= htmlspecialchars($incidencia['icon']) ?>"></i>
                                                    <span><?= htmlspecialchars($incidencia['label']) ?></span>
                                                    <?php if (!empty($incidencia['detail'])): ?><small><?= htmlspecialchars($incidencia['detail']) ?></small><?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($faceMeta !== ''): ?><div class="rc-meta"><?= htmlspecialchars($faceMeta) ?></div><?php endif; ?>
                                    <div class="rc-room-facts" aria-label="Capacidad y camas">
                                        <span class="rc-room-fact" title="Capacidad: <?= htmlspecialchars($habitacionCapacidadTexto) ?>">
                                            <i class="fas fa-users" aria-hidden="true"></i>
                                            <span><?= htmlspecialchars($habitacionCapacidadTexto) ?></span>
                                        </span>
                                        <span class="rc-room-fact" title="Camas: <?= htmlspecialchars($habitacionCamasTexto) ?><?= $habitacionCamasTotal > 0 ? ' (' . htmlspecialchars($habitacionCamasDetalle) . ')' : '' ?>">
                                            <i class="fas fa-bed" aria-hidden="true"></i>
                                            <span><?= htmlspecialchars($habitacionCamasTexto) ?></span>
                                        </span>
                                    </div>
                                </div>
                                <div class="rc-foot">
                                    <span class="rc-price"><?= format_money($habitacion['precio_actual'] ?? $habitacion['precio_base']) ?><small>/noche</small></span>
                                    <span class="rc-owner" title="Propietario: <?= htmlspecialchars($habitacionPropietarioTexto) ?>">
                                        <i class="fas fa-user-tie" aria-hidden="true"></i>
                                        <span><?= htmlspecialchars($habitacionPropietarioTexto) ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Parte trasera con información adicional -->
                        <div class="flip-card-back <?= $backColorClass ?>"<?= $backStyle ? ' style="' . $backStyle . '"' : '' ?>>
                            <h4 class="room-card-action-title">Hab. <?= htmlspecialchars($habitacion['numero']) ?></h4>
                            <div class="room-card-scroll-info">
                                <div class="info-item">
                                    <i class="fas fa-user-tie"></i>
                                    <span>
                                        Propietario: <?= htmlspecialchars($habitacionPropietarioTexto) ?>
                                        <?php if ($habitacionPropietarioPctLabel !== ''): ?>
                                            - <?= htmlspecialchars($habitacionPropietarioPctLabel) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-users"></i>
                                    <span>Capacidad: <?= htmlspecialchars($habitacionCapacidadTexto) ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-bed"></i>
                                    <span>
                                        Camas: <?= htmlspecialchars($habitacionCamasTexto) ?>
                                        <?php if ($habitacionCamasTotal > 0): ?>
                                            (<?= htmlspecialchars($habitacionCamasDetalle) ?>)
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <?php if ($tareasActivas > 0): ?>
                                    <button type="button"
                                            class="hb-tareas-chip<?= $tareasVencida ? ' is-vencida' : '' ?>"
                                            onclick="event.stopPropagation(); abrirTareasCuarto(<?= (int)$habitacion['id'] ?>, '<?= htmlspecialchars($habitacionNumeroTexto, ENT_QUOTES) ?>', '<?= htmlspecialchars((string)($habitacion['estado'] ?? ''), ENT_QUOTES) ?>')"
                                            title="Ver tareas de esta habitación">
                                        <i class="fas fa-tasks"></i>
                                        <span>
                                            <?= $tareasActivas ?> tarea<?= $tareasActivas === 1 ? '' : 's' ?> activa<?= $tareasActivas === 1 ? '' : 's' ?>
                                            <?php if (!empty($tareasDetalle)): ?>
                                                <small class="hb-tareas-detail">(<?= htmlspecialchars(implode(' · ', $tareasDetalle)) ?>)</small>
                                            <?php endif; ?>
                                            <?php if ($tareasVencida): ?><small class="hb-tareas-venc">· vencida</small><?php endif; ?>
                                        </span>
                                        <i class="fas fa-chevron-right hb-tareas-arrow"></i>
                                    </button>
                                <?php elseif (can('habitaciones.mantenimiento')): ?>
                                    <button type="button"
                                            class="hb-tareas-chip hb-tareas-chip--empty"
                                            onclick="event.stopPropagation(); crearTareaCuarto(<?= (int)$habitacion['id'] ?>, '<?= htmlspecialchars((string)($habitacion['estado'] ?? ''), ENT_QUOTES) ?>')"
                                            title="Crear tarea para esta habitación">
                                        <i class="fas fa-plus-circle"></i>
                                        <span>Sin tareas · Crear</span>
                                    </button>
                                <?php endif; ?>

                                <?php if ($tiene_doble_movimiento): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exchange-alt"></i>
                                        <span>Rotación de huéspedes</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Sale: <?= htmlspecialchars(explode(' ', $info_checkout['nombre_completo'])[0]) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Entra: <?= htmlspecialchars(explode(' ', $info_checkin['nombre_completo'])[0]) ?></span>
                                    </div>

                                <?php elseif ($es_checkin_vencido && $info_checkin_vencido): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>CHECK-IN VENCIDO</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($info_checkin_vencido['nombre']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Debió llegar: <?= date('d/m/Y', strtotime($info_checkin_vencido['fecha_entrada'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $info_checkin_vencido['dias_retraso'] ?> día<?= $info_checkin_vencido['dias_retraso'] > 1 ? 's' : '' ?> de retraso</span>
                                    </div>

                                    <?php if ($habitacion['estado'] == 'limpieza'): ?>
                                        <div class="info-item">
                                            <i class="fas fa-broom"></i>
                                            <span>Tambien requiere limpieza</span>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($tiene_checkout_vencido && $info_checkout_vencido): ?>
                                    <div class="info-item">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>CHECK-OUT VENCIDO</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($info_checkout_vencido['nombre']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Debió salir: <?= date('d/m/Y', strtotime($info_checkout_vencido['fecha_salida'])) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?= $info_checkout_vencido['dias_retraso'] ?> día<?= $info_checkout_vencido['dias_retraso'] > 1 ? 's' : '' ?> de retraso</span>
                                    </div>

                                <?php elseif ($estado_actual == 'disponible' || $estado_actual == 'disponible_fecha'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Lista para reservar</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?= htmlspecialchars($habitacionTipoLabel) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?= format_money($habitacion['precio_actual'] ?? $habitacion['precio_base']) ?>/noche</span>
                                    </div>

                                <?php elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($habitacion['reservacion_pendiente']['nombre_completo']) ?></span>
                                    </div>
                                    <?php if ($habitacion['estado'] == 'limpieza'): ?>
                                        <div class="info-item">
                                            <i class="fas fa-broom"></i>
                                            <span>En limpieza · prep&aacute;rala para el check-in</span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($es_llegada_tardia): ?>
                                        <div class="info-item">
                                            <i class="fas fa-moon"></i>
                                            <span>Llegada tardía pendiente</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span>Desde: <?= format_date($habitacion['reservacion_pendiente']['fecha_entrada']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Llegada: <?= date('g:i A', strtotime($habitacion['reservacion_pendiente']['hora_llegada_estimada'])) ?></span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-phone"></i>
                                            <span>Check-in pendiente</span>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($tieneCompromisoEnFecha): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($habitacion['info_ocupacion']['huesped'] ?? 'Reservacion') ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-<?= $esLlegadaEnFecha ? 'clock' : 'calendar-check' ?>"></i>
                                        <span><?= $esLlegadaEnFecha ? 'Llega ese d&iacute;a · falta recibirla' : 'Ocupada en la fecha consultada' ?></span>
                                    </div>
                                    <?php if (!empty($habitacion['info_ocupacion']['fecha_entrada']) && !empty($habitacion['info_ocupacion']['fecha_salida'])): ?>
                                        <div class="info-item">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?= format_date($habitacion['info_ocupacion']['fecha_entrada']) ?> - <?= format_date($habitacion['info_ocupacion']['fecha_salida']) ?></span>
                                        </div>
                                    <?php endif; ?>

                                <?php elseif ($habitacion['estado'] == 'ocupada' && isset($habitacion['ocupacion_actual'])): ?>
                                    <div class="info-item">
                                        <i class="fas fa-user"></i>
                                        <span><?= htmlspecialchars($habitacion['ocupacion_actual']['nombre_completo']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>Entrada: <?= format_date($habitacion['ocupacion_actual']['fecha_entrada']) ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span>Salida: <?= format_date($habitacion['ocupacion_actual']['fecha_salida']) ?></span>
                                    </div>
                                    <?php $saldoOcup = (float)($habitacion['ocupacion_actual']['saldo'] ?? 0); ?>
                                    <?php if ($saldoOcup > 0.004): ?>
                                    <div class="info-item hb-saldo-pendiente" title="Esta reservación tiene saldo pendiente. No se podrá hacer check-out hasta cobrarlo.">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Saldo pendiente: <strong><?= format_money($saldoOcup) ?></strong></span>
                                    </div>
                                    <?php else: ?>
                                    <div class="info-item hb-saldo-cubierto" title="Cuenta cubierta. Lista para check-out.">
                                        <i class="fas fa-check-circle"></i>
                                        <span>Cuenta cubierta</span>
                                    </div>
                                    <?php endif; ?>

                                <?php elseif ($habitacion['estado'] == 'limpieza'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-broom"></i>
                                        <span>En proceso de limpieza</span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span>Tiempo estimado: 30 min</span>
                                    </div>

                                <?php elseif ($habitacion['estado'] == 'mantenimiento'): ?>
                                    <div class="info-item">
                                        <i class="fas fa-tools"></i>
                                        <span><?= $habitacion['mantenimiento_actual']['tipo_mantenimiento'] ?? 'Mantenimiento general' ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-wrench"></i>
                                        <span>Trabajo en progreso</span>
                                    </div>
                                <?php endif; ?>

                                <div class="info-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?= $nombrePiso ?></span>
                                </div>
                            </div>

                            <div class="action-buttons">
    <?php if ($tieneCompromisoEnFecha && isset($habitacion['info_ocupacion']['reservacion_id'])): ?>
        <!-- Botón Ver Reservación cuando hay filtro de fecha -->
        <a href="<?= url('reservaciones/ver/' . $habitacion['info_ocupacion']['reservacion_id']) ?>" class="btn-action btn-primary" onclick="event.stopPropagation();" title="Ver detalle de la reservación">
            <i class="fas fa-eye mr-1"></i>Ver Reservación
        </a>
    <?php else: ?>
        <!-- Botón Detalles normal -->
        <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="btn-action" onclick="event.stopPropagation();" title="Ver detalle de la habitación">
            <i class="fas fa-eye mr-1"></i>Detalles
        </a>
     <?php endif; ?>

    <?php if ($reservacion_detalle_id && !($tieneCompromisoEnFecha && isset($habitacion['info_ocupacion']['reservacion_id']))): ?>
        <a href="<?= url('reservaciones/ver/' . $reservacion_detalle_id) ?>" class="btn-action" onclick="event.stopPropagation();" title="Ver detalle de la reservación">
            <i class="fas fa-file-alt mr-1"></i>Reservacion
        </a>
    <?php endif; ?>

    <?php if ($puede_checkout_ocupada): ?>
        <a href="javascript:void(0)" onclick="event.stopPropagation(); confirmarCheckOut(<?= (int)$reservacion_detalle_id ?>)" class="btn-action btn-primary hb-card-checkout-action" title="Realizar check-out de esta reservacion">
            <i class="fas fa-sign-out-alt mr-1"></i>Check-out
        </a>
    <?php endif; ?>

                                 <?php if ($estado_actual == 'disponible' || $estado_actual == 'disponible_fecha'): ?>
                                    <?php if ($estado_actual == 'disponible_fecha'): ?>
                                        <a href="javascript:void(0)" onclick="return hbReservarDesdeHabitacion(event, <?= $habitacion['id'] ?>, '<?= $filtros['fecha_consulta'] ?>')" class="btn-action btn-primary hb-reserve-action" title="Reservar esta habitación en la fecha seleccionada">
                                            <i class="fas fa-plus mr-1"></i>Reservar
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="return hbReservarDesdeHabitacion(event, <?= $habitacion['id'] ?>)" class="btn-action btn-primary hb-reserve-action" title="Crear reservación rápida para esta habitación">
                                            <i class="fas fa-plus mr-1"></i>Reservar
                                        </a>
                                    <?php endif; ?>

                                <?php elseif ($tiene_checkout_vencido && $info_checkout_vencido): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); confirmarCheckOut(<?= $info_checkout_vencido['reservacion_id'] ?>)" class="btn-action btn-primary" title="Realizar check-out de esta reservación">
                                        <i class="fas fa-sign-out-alt mr-1"></i>Check-out
                                    </a>

                                <?php elseif ($es_checkin_vencido && $info_checkin_vencido): ?>
                                    <?php if ($habitacion['estado'] == 'limpieza'): ?>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); liberarHabitacion(<?= $habitacion['id'] ?>)" class="btn-action btn-primary" title="Marcar esta habitacion como limpia">
                                            <i class="fas fa-check mr-1"></i>Limpia
                                        </a>
                                    <?php else: ?>
                                        <?php /* Accion duplicada omitida: ya existen Detalles/Reservacion en esta hoja. */ ?>
                                    <?php endif; ?>

                                <?php elseif ($estado_actual == 'por_llegar' && isset($habitacion['reservacion_pendiente']) && can('reservaciones.checkin')): ?>
                                    <?php if ($habitacion['estado'] == 'limpieza'): ?>
                                        <!-- Llega un huésped pero la habitación sigue en limpieza: primero hay que dejarla lista -->
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); liberarHabitacion(<?= $habitacion['id'] ?>)" class="btn-action btn-primary" title="Marcar esta habitación como limpia antes del check-in">
                                            <i class="fas fa-check mr-1"></i>Limpia
                                        </a>
                                    <?php else: ?>
                                        <a href="javascript:void(0)" onclick="event.stopPropagation(); hacerCheckInRapido(<?= $habitacion['reservacion_pendiente']['reservacion_id'] ?? $habitacion['reservacion_pendiente']['id'] ?>)" class="btn-action btn-primary" title="Hacer check-in de la reservación pendiente">
                                            <i class="fas fa-sign-in-alt mr-1"></i>Check-in
                                        </a>
                                    <?php endif; ?>

                                <?php elseif ($habitacion['estado'] == 'limpieza'): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); liberarHabitacion(<?= $habitacion['id'] ?>)" class="btn-action btn-primary" title="Marcar esta habitación como limpia">
                                        <i class="fas fa-check mr-1"></i>Limpia
                                    </a>

                                <?php elseif ($habitacion['estado'] == 'mantenimiento'): ?>
                                    <a href="javascript:void(0)" onclick="event.stopPropagation(); finalizarMantenimiento(<?= $habitacion['id'] ?>)" class="btn-action btn-primary" title="Finalizar mantenimiento de esta habitación">
                                        <i class="fas fa-check mr-1"></i>Finalizar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>

<?php /* #hbNoResults = miss del filtro CLIENT-side (hay cuartos en el DOM). Solo se
                 renderiza cuando existen habitaciones; si el hotel tiene CERO, el estado
                 premium de abajo es el unico (antes se apilaban los dos, ver screenshot). */ ?>
        <?php if (!empty($habitaciones)): ?>
        <div id="hbNoResults" style="display:none; text-align:center; padding:44px 20px; background:#FAFAFC; border:1px dashed #E7E1D4; border-radius:16px; margin-top:4px;">
            <i class="fas fa-filter" style="font-size:1.5rem; color:var(--brand-accent,#BD9441);"></i>
            <div style="font-weight:700; color:var(--brand-primary,#1B2746); margin-top:10px;">Sin resultados</div>
            <div style="color:#6C7689; font-size:.85rem; margin-top:4px;">Ninguna habitación coincide con el filtro.</div>
        </div>
        <?php endif; ?>

        <!-- Estado vacio -->
        <?php if (empty($habitaciones)):
            $hbTotalReal = (int)($estadisticas['total'] ?? 0); // total del hotel SIN filtro
            $hbPuedeCrear = can('habitaciones.create');
        ?>
            <?php if ($hbTotalReal === 0): ?>
                <?php /* El hotel aun no tiene NINGUNA habitacion: onboarding, no "ajusta filtros". */ ?>
                <div id="hbEmptyState" class="hb-empty-first" role="region" aria-label="Registrar primera habitación">
                    <div class="hb-empty-first__glow" aria-hidden="true"></div>
                    <div class="hb-empty-first__inner">
                        <div class="hb-empty-first__badge" aria-hidden="true">
                            <i class="fas fa-bed"></i>
                            <span class="hb-empty-first__spark"><i class="fas fa-plus"></i></span>
                        </div>
                        <h3 class="hb-empty-first__title">Aún no hay habitaciones</h3>
                        <p class="hb-empty-first__lead">Registra tu primera habitación para empezar a recibir huéspedes, asignar tarifas y controlar la ocupación desde este panel.</p>

                        <?php if ($hbPuedeCrear): ?>
                        <a href="<?= url('habitaciones/create') ?>" class="hb-empty-first__cta">
                            <i class="fas fa-plus"></i>
                            <span>Añadir primera habitación</span>
                        </a>
                        <a href="<?= url('habitaciones/lote') ?>" class="hb-empty-first__cta-alt">
                            <i class="fas fa-layer-group"></i>
                            <span>¿Vas a cargar muchas? Créalas en lote</span>
                        </a>
                        <?php else: ?>
                        <p class="hb-empty-first__note"><i class="fas fa-lock"></i> Pídele a un administrador que registre las habitaciones del hotel.</p>
                        <?php endif; ?>

                        <ul class="hb-empty-first__hints" aria-hidden="true">
                            <li><i class="fas fa-tag"></i> Tarifas por tipo</li>
                            <li><i class="fas fa-broom"></i> Estados de limpieza</li>
                            <li><i class="fas fa-calendar-check"></i> Reservaciones al día</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <?php /* Hay cuartos pero el filtro/busqueda del servidor no arrojo nada. */ ?>
                <div id="hbEmptyState" class="hb-empty-filter">
                    <div class="hb-empty-filter__icon"><i class="fas fa-filter"></i></div>
                    <h3 class="hb-empty-filter__title">No se encontraron habitaciones</h3>
                    <p class="hb-empty-filter__lead">Ajusta los filtros de búsqueda o verifica los criterios.</p>
                    <a href="<?= url('habitaciones') ?>" class="hb-empty-filter__cta">
                        <i class="fas fa-redo"></i>
                        <span>Mostrar todas</span>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php /* #hbNoResults trae fondo claro fijo (inline) que desentona en dark; se remapea
                 a superficies del tema. Los estados hb-empty-* ya usan tokens --brand-* (dark
                 los voltea solo), asi que aqui solo queda el override de hbNoResults. */ ?>
        <style id="hb-emptystate-dark">
            html[data-theme="dark"] #hbNoResults{
                background: var(--brand-surface, #201F19) !important;
                border-color: var(--brand-line, #403C31) !important;
            }
            html[data-theme="dark"] #hbNoResults div{ color: var(--brand-text, #EFE9DC) !important; }
            html[data-theme="dark"] #hbNoResults div + div{ color: var(--brand-muted, #A69F8E) !important; }
        </style>

        <style id="hb-empty-first-styles">
            .hb-empty-first{
                position:relative; overflow:hidden; text-align:center;
                padding:58px 24px 62px; margin-top:4px;
                border-radius:22px;
                background: var(--brand-surface, #FFFDF8);
                border:1px solid var(--brand-line, #EDE7DA);
                box-shadow:0 22px 48px -34px rgba(27,39,70,.42);
            }
            .hb-empty-first__glow{
                position:absolute; inset:-40% 0 auto 0; height:70%;
                background: radial-gradient(60% 100% at 50% 0%,
                    color-mix(in srgb, var(--brand-accent, #BD9441) 16%, transparent) 0%,
                    transparent 70%);
                pointer-events:none;
            }
            .hb-empty-first__inner{ position:relative; z-index:1; max-width:460px; margin:0 auto; }
            .hb-empty-first__badge{
                position:relative; width:88px; height:88px; margin:0 auto 24px;
                display:flex; align-items:center; justify-content:center;
                border-radius:28px; font-size:2.05rem;
                color: var(--brand-accent, #BD9441);
                background: color-mix(in srgb, var(--brand-accent, #BD9441) 13%, var(--brand-surface, #fff));
                border:1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 28%, transparent);
                box-shadow:0 16px 30px -18px color-mix(in srgb, var(--brand-accent, #BD9441) 70%, transparent);
                animation: hbEmptyFloat 5.5s ease-in-out infinite;
            }
            .hb-empty-first__spark{
                position:absolute; right:-6px; bottom:-6px;
                width:30px; height:30px; border-radius:50%;
                display:flex; align-items:center; justify-content:center;
                font-size:.72rem; color:#fff;
                background: linear-gradient(135deg, var(--brand-primary, #1B2746), var(--brand-secondary, #0F172A));
                border:2px solid var(--brand-surface, #fff);
                box-shadow:0 6px 14px -6px rgba(27,39,70,.6);
            }
            @keyframes hbEmptyFloat{ 0%,100%{ transform:translateY(0); } 50%{ transform:translateY(-6px); } }
            @media (prefers-reduced-motion: reduce){ .hb-empty-first__badge{ animation:none; } }
            .hb-empty-first__title{
                font-size:1.42rem; font-weight:700; letter-spacing:-.01em;
                color: var(--brand-primary, #1B2746); margin:0 0 8px;
            }
            .hb-empty-first__lead{
                font-size:.95rem; line-height:1.6; color: var(--brand-muted, #6C7689);
                margin:0 auto 26px; max-width:410px;
            }
            .hb-empty-first__cta{
                position:relative; overflow:hidden;
                display:inline-flex; align-items:center; gap:10px;
                padding:14px 28px; border-radius:14px;
                font-weight:700; font-size:.96rem; text-decoration:none; color:#fff;
                background: linear-gradient(135deg, var(--brand-primary, #1B2746), var(--brand-secondary, #0F172A));
                box-shadow:0 16px 32px -16px color-mix(in srgb, var(--brand-primary, #1B2746) 72%, transparent);
                transition: transform .18s ease, box-shadow .18s ease;
            }
            .hb-empty-first__cta:hover{
                transform: translateY(-2px);
                box-shadow:0 22px 40px -16px color-mix(in srgb, var(--brand-primary, #1B2746) 72%, transparent);
            }
            .hb-empty-first__cta::after{
                content:''; position:absolute; inset:0;
                background: linear-gradient(120deg, transparent 32%, rgba(255,255,255,.30) 50%, transparent 68%);
                transform: translateX(-120%);
            }
            .hb-empty-first__cta:hover::after{ animation: hbEmptyShine .9s ease; }
            @keyframes hbEmptyShine{ to{ transform: translateX(120%); } }
            .hb-empty-first__cta-alt{
                display:inline-flex; align-items:center; gap:8px;
                margin-top:12px; padding:9px 18px; border-radius:12px;
                font-weight:600; font-size:.86rem; text-decoration:none;
                color: var(--brand-primary, #1B2746);
                background: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, transparent);
                border:1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 22%, transparent);
                transition: background .18s ease;
            }
            .hb-empty-first__cta-alt:hover{ background: color-mix(in srgb, var(--brand-primary, #1B2746) 14%, transparent); }
            .hb-empty-first__note{
                display:inline-flex; align-items:center; gap:8px;
                font-size:.9rem; color: var(--brand-muted, #6C7689);
            }
            .hb-empty-first__hints{
                list-style:none; padding:0; margin:32px 0 0;
                display:flex; flex-wrap:wrap; gap:10px 20px; justify-content:center;
            }
            .hb-empty-first__hints li{
                display:inline-flex; align-items:center; gap:7px;
                font-size:.8rem; color: var(--brand-muted, #8A8474);
            }
            .hb-empty-first__hints i{ color: var(--brand-accent, #BD9441); }

            /* Estado de filtro sin coincidencias (hay cuartos, pero ninguno matchea) */
            .hb-empty-filter{
                text-align:center; padding:44px 22px; margin-top:4px;
                border-radius:18px;
                background: var(--brand-surface, #FAFAFC);
                border:1px solid var(--brand-line, #EDE7DA);
            }
            .hb-empty-filter__icon{
                width:60px; height:60px; margin:0 auto 14px;
                display:flex; align-items:center; justify-content:center;
                border-radius:18px; font-size:1.35rem;
                color: var(--brand-accent, #BD9441);
                background: color-mix(in srgb, var(--brand-accent, #BD9441) 12%, var(--brand-surface, #fff));
            }
            .hb-empty-filter__title{
                font-size:1.05rem; font-weight:700; color: var(--brand-primary, #1B2746); margin:0 0 4px;
            }
            .hb-empty-filter__lead{
                font-size:.88rem; color: var(--brand-muted, #6C7689); margin:0 auto 18px; max-width:340px;
            }
            .hb-empty-filter__cta{
                display:inline-flex; align-items:center; gap:8px;
                padding:10px 20px; border-radius:12px;
                font-weight:700; font-size:.88rem; text-decoration:none;
                color: var(--brand-primary, #1B2746);
                background: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, var(--brand-surface, #fff));
                border:1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 18%, transparent);
                transition: background .16s ease;
            }
            .hb-empty-filter__cta:hover{ background: color-mix(in srgb, var(--brand-primary, #1B2746) 14%, var(--brand-surface, #fff)); }

            @media (max-width:640px){
                .hb-empty-first{ padding:44px 18px 48px; border-radius:18px; }
                .hb-empty-first__title{ font-size:1.22rem; }
                .hb-empty-first__badge{ width:78px; height:78px; font-size:1.8rem; }
            }
        </style>

        <div id="hbMobileSheetBack" class="hb-mobile-sheet-back" onclick="hbCloseMobileRoomSheet()" aria-hidden="true"></div>
        <div id="hbMobileRoomSheet" class="hb-mobile-room-sheet" aria-hidden="true">
            <div class="hb-mobile-sheet-grab"></div>
            <div id="hbMobileSheetContent" class="hb-mobile-sheet-content"></div>
        </div>

        <!-- Resumen compacto de disponibilidad -->


<!-- Modal de Vista Rápida -->
<!-- Modal de Vista Rápida -->
<style id="hb-quick-view-command-redesign">
    #vistaRapidaModal.hb-quick-modal {
        --qv-primary: var(--brand-primary, var(--hb-primary, #1B2746));
        --qv-secondary: var(--brand-secondary, var(--hb-primary-2, #0F172A));
        --qv-accent: var(--brand-accent, var(--hb-accent, #BD9441));
        --qv-paper: color-mix(in srgb, var(--qv-accent) 7%, #fbfaf6);
        --qv-panel: rgba(255,255,255,.95);
        --qv-ink: #172033;
        --qv-muted: #687386;
        --qv-line: color-mix(in srgb, var(--qv-primary) 11%, #eadfca);
        --qv-green: #149A62;
        --qv-occupied: #B75638;
        --qv-purple: #8039D0;
        --qv-blue: #2F77D9;
        --qv-amber: #C98B18;
        background:
            radial-gradient(860px 360px at 84% 8%, color-mix(in srgb, var(--qv-accent) 18%, transparent), transparent 62%),
            rgba(13, 18, 29, .66) !important;
        backdrop-filter: blur(12px);
        padding: clamp(12px, 2vw, 28px) !important;
    }

    #vistaRapidaModal.hb-quick-modal .hb-quick-dialog {
        width: min(1180px, calc(100vw - 28px)) !important;
        max-height: min(88vh, 820px) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        background:
            linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.9)),
            var(--qv-paper) !important;
        border: 1px solid color-mix(in srgb, var(--qv-accent) 26%, rgba(255,255,255,.22)) !important;
        box-shadow: 0 34px 90px -44px rgba(0,0,0,.76) !important;
    }

    #vistaRapidaModal .hb-quick-header {
        position: relative;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
        min-height: 124px;
        padding: clamp(18px, 2.1vw, 28px) !important;
        color: #fff !important;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--qv-secondary) 94%, #000), color-mix(in srgb, var(--qv-primary) 82%, var(--qv-secondary))) !important;
        isolation: isolate;
    }

    #vistaRapidaModal .hb-quick-header::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background:
            linear-gradient(90deg, rgba(255,255,255,.08) 1px, transparent 1px),
            linear-gradient(180deg, rgba(255,255,255,.07) 1px, transparent 1px);
        background-size: 26px 26px;
        opacity: .36;
        pointer-events: none;
    }

    #vistaRapidaModal .hb-quick-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    #vistaRapidaModal .hb-quick-mark {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        flex: none;
        border-radius: 13px;
        color: #fff;
        background: color-mix(in srgb, var(--qv-accent) 28%, rgba(255,255,255,.1));
        border: 1px solid rgba(255,255,255,.2);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
    }

    #vistaRapidaModal .hb-quick-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 6px;
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .09em;
        text-transform: uppercase;
        color: rgba(255,255,255,.68);
    }

    #vistaRapidaModal .hb-quick-eyebrow::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: var(--qv-green);
        box-shadow: 0 0 0 5px rgba(20,154,98,.15);
    }

    #vistaRapidaModal .hb-quick-title {
        margin: 0;
        color: #fff !important;
        font-family: 'Outfit', 'DM Sans', system-ui, sans-serif;
        font-size: clamp(1.55rem, 2.2vw, 2.55rem) !important;
        font-weight: 900 !important;
        line-height: .95;
        letter-spacing: 0;
        text-wrap: balance;
    }

    #vistaRapidaModal .hb-quick-subtitle {
        margin-top: 9px;
        max-width: 64ch;
        color: rgba(255,255,255,.72);
        font-size: .88rem;
        line-height: 1.5;
    }

    #vistaRapidaModal .hb-quick-close {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: #fff !important;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        transition: transform .18s ease, background .18s ease, border-color .18s ease;
    }

    #vistaRapidaModal .hb-quick-close:hover,
    #vistaRapidaModal .hb-quick-close:focus-visible {
        transform: translateY(-1px);
        background: rgba(255,255,255,.2);
        border-color: rgba(255,255,255,.32);
        outline: none;
    }

    #vistaRapidaModal .hb-quick-body {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 270px;
        gap: 0;
        max-height: calc(88vh - 124px) !important;
        padding: 0 !important;
        overflow: hidden !important;
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--qv-primary) 4%, transparent) 1px, transparent 1px),
            linear-gradient(180deg, #fffdfa, var(--qv-paper)) !important;
        background-size: 28px 28px, auto;
    }

    #vistaRapidaModal .hb-quick-map {
        min-width: 0;
        padding: clamp(16px, 2vw, 24px);
        overflow: auto;
    }

    #vistaRapidaModal .hb-quick-map-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 16px;
    }

    #vistaRapidaModal .hb-quick-map-head h4 {
        margin: 0;
        color: var(--qv-ink);
        font-family: 'Outfit', 'DM Sans', system-ui, sans-serif;
        font-size: 1.05rem;
        font-weight: 900;
    }

    #vistaRapidaModal .hb-quick-map-head p {
        margin: 4px 0 0;
        color: var(--qv-muted);
        font-size: .8rem;
    }

    #vistaRapidaModal .hb-quick-count {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        padding: 9px 11px;
        border-radius: 10px;
        color: var(--qv-ink);
        background: rgba(255,255,255,.78);
        border: 1px solid var(--qv-line);
        font-size: .8rem;
        font-weight: 900;
        font-variant-numeric: tabular-nums;
    }

    #vistaRapidaModal .hb-quick-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)) !important;
        gap: 10px !important;
    }

    #vistaRapidaContainer .room-quick-view.hb-quick-room {
        --qv-room: #7b8496;
        position: relative;
        display: grid;
        align-content: space-between;
        min-height: 86px;
        padding: 11px 10px !important;
        border-radius: 13px !important;
        overflow: hidden;
        isolation: isolate;
        color: var(--qv-ink) !important;
        text-align: left !important;
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.88)) !important;
        border: 1px solid color-mix(in srgb, var(--qv-room) 30%, var(--qv-line)) !important;
        box-shadow: 0 10px 22px -19px color-mix(in srgb, var(--qv-room) 70%, transparent) !important;
        transform: none !important;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease !important;
    }

    #vistaRapidaContainer .room-quick-view.hb-quick-room::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: var(--qv-room);
        z-index: -1;
    }

    #vistaRapidaContainer .room-quick-view.hb-quick-room::after {
        content: '';
        position: absolute;
        right: 9px;
        top: 9px;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: var(--qv-room);
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--qv-room) 14%, transparent);
        opacity: 1;
        transform: none;
    }

    #vistaRapidaContainer .room-quick-view.hb-quick-room:hover,
    #vistaRapidaContainer .room-quick-view.hb-quick-room:focus-visible {
        transform: translateY(-2px) !important;
        border-color: color-mix(in srgb, var(--qv-room) 54%, var(--qv-line)) !important;
        box-shadow: 0 18px 32px -24px color-mix(in srgb, var(--qv-room) 82%, transparent) !important;
        outline: none;
    }

    #vistaRapidaContainer .room-quick-view.hb-quick-room:active {
        transform: translateY(0) scale(.99) !important;
    }

    #vistaRapidaModal .hb-quick-room.qv-state-disponible,
    #vistaRapidaModal .hb-quick-room.qv-state-disponible_fecha { --qv-room: var(--qv-green); }
    #vistaRapidaModal .hb-quick-room.qv-state-ocupada,
    #vistaRapidaModal .hb-quick-room.qv-state-ocupada_fecha { --qv-room: var(--qv-occupied); }
    #vistaRapidaModal .hb-quick-room.qv-state-por_llegar { --qv-room: var(--qv-purple); }
    #vistaRapidaModal .hb-quick-room.qv-state-limpieza { --qv-room: var(--qv-blue); }
    #vistaRapidaModal .hb-quick-room.qv-state-mantenimiento { --qv-room: var(--qv-amber); }

    #vistaRapidaModal .qv-room-num {
        display: block;
        max-width: calc(100% - 14px);
        color: color-mix(in srgb, var(--qv-room) 42%, var(--qv-ink));
        font-family: 'Outfit', 'DM Sans', system-ui, sans-serif;
        font-size: clamp(1rem, 1vw, 1.25rem);
        font-weight: 950;
        line-height: 1;
        letter-spacing: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #vistaRapidaModal .qv-room-meta {
        display: block;
        margin-top: 7px;
        color: var(--qv-muted);
        font-size: .68rem;
        font-weight: 900;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    #vistaRapidaModal .qv-room-state {
        display: block;
        margin-top: 7px;
        color: color-mix(in srgb, var(--qv-room) 68%, var(--qv-ink));
        font-size: .68rem;
        font-weight: 850;
        line-height: 1.15;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #vistaRapidaModal .hb-quick-aside {
        min-width: 0;
        padding: clamp(16px, 1.8vw, 22px);
        background:
            linear-gradient(180deg, rgba(255,255,255,.7), rgba(255,255,255,.44)),
            color-mix(in srgb, var(--qv-accent) 7%, #fff);
        border-left: 1px solid var(--qv-line);
        overflow: auto;
    }

    #vistaRapidaModal .hb-quick-aside-title {
        margin: 0 0 13px;
        color: var(--qv-ink);
        font-family: 'Outfit', 'DM Sans', system-ui, sans-serif;
        font-size: .92rem;
        font-weight: 950;
    }

    #vistaRapidaModal .hb-quick-legend {
        display: grid !important;
        gap: 9px !important;
        color: var(--qv-ink);
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        justify-content: stretch !important;
    }

    #vistaRapidaModal .hb-quick-legend-item {
        --legend-color: #7b8496;
        display: grid !important;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 9px !important;
        min-height: 42px;
        padding: 9px 10px;
        border-radius: 11px;
        background: rgba(255,255,255,.72);
        border: 1px solid color-mix(in srgb, var(--legend-color) 20%, var(--qv-line));
    }

    #vistaRapidaModal .hb-quick-legend-swatch {
        width: 12px !important;
        height: 28px !important;
        border-radius: 999px !important;
        background: var(--legend-color) !important;
        box-shadow: 0 0 0 4px color-mix(in srgb, var(--legend-color) 11%, transparent) !important;
    }

    #vistaRapidaModal .hb-quick-legend-name {
        min-width: 0;
        font-size: .78rem;
        font-weight: 900;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #vistaRapidaModal .hb-quick-legend-count {
        padding: 5px 8px;
        border-radius: 8px;
        color: color-mix(in srgb, var(--legend-color) 72%, var(--qv-ink));
        background: color-mix(in srgb, var(--legend-color) 8%, #fff);
        border: 1px solid color-mix(in srgb, var(--legend-color) 18%, var(--qv-line));
        font-size: .76rem;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
    }

    #vistaRapidaModal .hb-quick-hint {
        margin-top: 16px;
        padding: 13px;
        border-radius: 12px;
        color: var(--qv-muted);
        background: rgba(255,255,255,.62);
        border: 1px dashed var(--qv-line);
        font-size: .78rem;
        line-height: 1.42;
    }

    #vistaRapidaModal .hb-quick-hint strong {
        display: block;
        margin-bottom: 3px;
        color: var(--qv-ink);
        font-weight: 950;
    }

    #vistaRapidaModal #roomTooltip.room-tooltip {
        border-radius: 13px !important;
        border: 1px solid color-mix(in srgb, var(--qv-accent) 28%, rgba(255,255,255,.2)) !important;
        box-shadow: 0 22px 46px -26px rgba(0,0,0,.58) !important;
    }

    @media (max-width: 940px) {
        #vistaRapidaModal.hb-quick-modal {
            align-items: flex-end !important;
            padding: 10px !important;
        }

        #vistaRapidaModal.hb-quick-modal .hb-quick-dialog {
            width: 100% !important;
            max-height: 92dvh !important;
            border-radius: 18px 18px 12px 12px !important;
        }

        #vistaRapidaModal .hb-quick-header {
            grid-template-columns: 1fr auto;
            min-height: 112px;
            padding: 16px !important;
        }

        #vistaRapidaModal .hb-quick-mark {
            width: 40px;
            height: 40px;
        }

        #vistaRapidaModal .hb-quick-subtitle {
            display: none;
        }

        #vistaRapidaModal .hb-quick-body {
            grid-template-columns: 1fr;
            max-height: calc(92dvh - 112px) !important;
            overflow: auto !important;
        }

        #vistaRapidaModal .hb-quick-map {
            overflow: visible;
            padding: 14px;
        }

        #vistaRapidaModal .hb-quick-map-head {
            align-items: flex-start;
            margin-bottom: 12px;
        }

        #vistaRapidaModal .hb-quick-grid {
            grid-template-columns: repeat(auto-fill, minmax(86px, 1fr)) !important;
            gap: 8px !important;
        }

        #vistaRapidaContainer .room-quick-view.hb-quick-room {
            min-height: 78px;
            padding: 10px 9px !important;
        }

        #vistaRapidaModal .hb-quick-aside {
            border-left: 0;
            border-top: 1px solid var(--qv-line);
            padding: 14px;
            overflow: visible;
        }

        #vistaRapidaModal .hb-quick-legend {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 520px) {
        #vistaRapidaModal .hb-quick-title-row {
            gap: 10px;
        }

        #vistaRapidaModal .hb-quick-title {
            font-size: 1.34rem !important;
        }

        #vistaRapidaModal .hb-quick-map-head {
            display: grid;
            gap: 9px;
        }

        #vistaRapidaModal .hb-quick-count {
            justify-self: start;
        }

        #vistaRapidaModal .hb-quick-grid {
            grid-template-columns: repeat(auto-fill, minmax(78px, 1fr)) !important;
        }

        #vistaRapidaContainer .room-quick-view.hb-quick-room {
            min-height: 74px;
        }

        #vistaRapidaModal .qv-room-num {
            font-size: .98rem;
        }

        #vistaRapidaModal .qv-room-state {
            display: none;
        }

        #vistaRapidaModal .hb-quick-legend {
            grid-template-columns: 1fr;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        #vistaRapidaModal *,
        #vistaRapidaContainer .room-quick-view.hb-quick-room {
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<style id="hb-quick-glass-cupertino">
/* Vista Rápida (modal): la banda verde del encabezado pasa a losa candy de
   la marca — SOLO Cupertino claro, y SOLO el header (sutil: las celdas y las
   píldoras de estado quedan intactas para no saturar). Deleite y modo oscuro
   sin cambios. Revertir: borrar este bloque. */
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-header {
    color: #1D1D1F !important;
    background:
        radial-gradient(46% 160% at 96% 76%, rgba(255,255,255,.82), rgba(255,255,255,0) 72%),
        linear-gradient(180deg, rgba(255,255,255,.5), rgba(255,255,255,0) 40%),
        linear-gradient(165deg,
            color-mix(in srgb, var(--qv-primary) 12%, #FFFFFF) 0%,
            color-mix(in srgb, var(--qv-primary) 26%, #FFFFFF) 100%) !important;
    border-bottom: 1px solid color-mix(in srgb, var(--qv-primary) 18%, rgba(17,24,39,.06)) !important;
    box-shadow: inset 0 1px 1px rgba(255,255,255,.9) !important;
}
/* Retícula "operativa": líneas casi imperceptibles teñidas de marca */
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-header::before {
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--qv-primary) 24%, transparent) 1px, transparent 1px),
        linear-gradient(180deg, color-mix(in srgb, var(--qv-primary) 24%, transparent) 1px, transparent 1px) !important;
    opacity: .1 !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-eyebrow {
    color: color-mix(in srgb, var(--qv-primary) 55%, #6E6E73) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-title {
    color: color-mix(in srgb, var(--qv-primary) 30%, #111827) !important;
    text-shadow: 0 1px 0 rgba(255,255,255,.4) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-subtitle {
    color: #6E6E73 !important;
}
/* Tesela del ícono: lechosa con tinta de marca */
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-mark {
    color: color-mix(in srgb, var(--qv-primary) 72%, #111827) !important;
    background: rgba(255,255,255,.62) !important;
    border: 1px solid rgba(255,255,255,.9) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.9), 0 1px 3px color-mix(in srgb, var(--qv-primary) 16%, transparent) !important;
}
/* Cerrar (X): chip lechoso con tinta */
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-close {
    color: color-mix(in srgb, var(--qv-primary) 66%, #111827) !important;
    background: rgba(255,255,255,.62) !important;
    border: 1px solid rgba(255,255,255,.9) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-close:hover,
html[data-tema="cupertino"]:not([data-theme="dark"]) #vistaRapidaModal .hb-quick-close:focus-visible {
    background: rgba(255,255,255,.85) !important;
    border-color: #FFFFFF !important;
}
</style>

<style id="hb-reserve-glass-cupertino">
/* Wizard "Crear reservación" (modal Nueva Reserva): el rail izquierdo oscuro
   pasa a losa candy de la marca — SOLO Cupertino claro. Tinta oscura, caja de
   fechas y pasos lechosos; el paso activo conserva su relleno de marca y el
   completo su verde semántico. Botones del wizard sin tocar (contraste de CTA).
   Deleite y modo oscuro intactos. Revertir: borrar este bloque. */
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-side {
    color: #1D1D1F !important;
    background:
        radial-gradient(260px 210px at 100% 10%, rgba(255,255,255,.55), transparent 62%),
        linear-gradient(155deg,
            color-mix(in srgb, var(--hb-reserve-brand) 12%, #FFFFFF),
            color-mix(in srgb, var(--hb-reserve-brand) 26%, #FFFFFF)) !important;
    border-right: 1px solid color-mix(in srgb, var(--hb-reserve-brand) 16%, rgba(17,24,39,.06)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-side::after {
    background: color-mix(in srgb, var(--hb-reserve-brand) 16%, transparent) !important;
    opacity: .4 !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-side__eyebrow {
    color: color-mix(in srgb, var(--hb-reserve-brand) 55%, #6E6E73) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-side h2 {
    color: color-mix(in srgb, var(--hb-reserve-brand) 30%, #111827) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-side p {
    color: #6E6E73 !important;
}
/* Caja de fechas: panel lechoso con tinta */
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-datebox {
    border: 1px solid rgba(255,255,255,.9) !important;
    background: rgba(255,255,255,.55) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.85) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-dateitem + .hb-reserve-dateitem {
    border-top: 1px solid color-mix(in srgb, var(--hb-reserve-brand) 12%, rgba(17,24,39,.06)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-dateicon {
    background: rgba(255,255,255,.7) !important;
    color: color-mix(in srgb, var(--hb-reserve-brand) 66%, #111827) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-dateitem small {
    color: #6E6E73 !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-dateitem strong {
    color: #1D1D1F !important;
}
/* Pasos: tinta sobre lienzo; activo = marca, completo = verde semántico */
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-steps li {
    color: #8A8A8F !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-steps li span {
    border: 1px solid color-mix(in srgb, var(--hb-reserve-brand) 20%, rgba(17,24,39,.12)) !important;
    color: #6E6E73 !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-steps li.is-active,
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-steps li.is-complete {
    color: #1D1D1F !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-steps li.is-active span {
    border-color: color-mix(in srgb, var(--hb-reserve-brand) 40%, transparent) !important;
    background: color-mix(in srgb, var(--hb-reserve-brand) 82%, #FFFFFF) !important;
    color: #FFFFFF !important;
}

/* Skeleton del wizard: su lado izquierdo también en candy (antes gradiente navy),
   así el "cargando" no rompe con el nuevo diseño; barras en tinta de marca. */
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-swal .hb-reserve-sk__side {
    background: linear-gradient(155deg,
        color-mix(in srgb, var(--hb-reserve-brand) 12%, #FFFFFF),
        color-mix(in srgb, var(--hb-reserve-brand) 26%, #FFFFFF)) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-swal .hb-reserve-sk__side .hb-reserve-sk-bar {
    background: color-mix(in srgb, var(--hb-reserve-brand) 15%, #FFFFFF) !important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .hb-reserve-swal .hb-reserve-sk__side .hb-reserve-sk-bar::after {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.7), transparent) !important;
}
</style>

<div id="vistaRapidaModal" class="hb-quick-modal fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="hb-quick-dialog bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden">
        <div class="hb-quick-header text-white p-3 flex justify-between items-center">
            <div class="hb-quick-title-row">
                <div class="hb-quick-mark" aria-hidden="true">
                    <i class="fas fa-th-large"></i>
                </div>
                <div>
                    <span class="hb-quick-eyebrow">Mapa operativo</span>
                    <h3 class="hb-quick-title text-lg font-bold">Vista Rápida de Habitaciones</h3>
                    <p class="hb-quick-subtitle">Consulta estados y abre la acción principal de cada habitación sin salir del tablero.</p>
                </div>
            </div>
            <button onclick="cerrarVistaRapida()" class="hb-quick-close text-white hover:text-gray-200 transition-colors p-1" title="Cerrar vista rápida" aria-label="Cerrar vista rápida">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="hb-quick-body p-4 overflow-y-auto max-h-[calc(90vh-60px)]" id="vistaRapidaContainer">
            <div class="hb-quick-map">
                <div class="hb-quick-map-head">
                    <div>
                        <h4>Habitaciones del hotel</h4>
                        <p>Da clic en una celda para reservar o abrir su detalle.</p>
                    </div>
                    <span class="hb-quick-count">
                        <i class="fas fa-door-open" aria-hidden="true"></i>
                        <?= (int)($estadisticas['total'] ?? count($habitaciones)) ?> habitaciones
                    </span>
                </div>
            <div class="hb-quick-grid grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10 gap-2">
                <?php foreach ($habitaciones as $hab): ?>
                    <?php
                    $estado_hab = $hab['estado_display'] ?? $hab['estado'];
                    $colorRapido = [
                        'disponible' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-300',
                        'disponible_fecha' => 'bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-emerald-300',
                        'por_llegar' => 'bg-purple-500 text-white shadow-purple-300',
                        'ocupada' => 'bg-gradient-to-br from-[#C2603C] to-[#9E4A2E] text-white shadow-orange-200',
                        'ocupada_fecha' => 'bg-gradient-to-br from-[#C2603C] to-[#9E4A2E] text-white shadow-orange-200',
                        'mantenimiento' => 'bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-amber-300',
                        'limpieza' => 'bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-blue-300'
                    ][$estado_hab] ?? 'bg-gradient-to-br from-gray-400 to-gray-600 text-white shadow-gray-300';

                    $pisoCortado = $hab['piso'] == 1 ? 'PB' :
                                  ($hab['piso'] > 0 ? 'P' . $hab['piso'] :
                                  'S' . abs($hab['piso']));

                    // Preparar datos para el tooltip
                    $tooltipData = [
                        'numero' => $hab['numero'],
                        'piso' => $pisos[$hab['piso']] ?? 'Piso ' . $hab['piso'],
                        'tipo' => hb_room_type_label($hab, $tipos),
                        'capacidad' => hb_room_capacity_label($hab),
                        'camas' => hb_room_beds_label($hab),
                        'camas_detalle' => hb_room_beds_detail_label($hab),
                        'estado' => $estados[$estado_hab]['label'] ?? 'Desconocido',
                        'precio' => format_money($hab['precio_actual'] ?? $hab['precio_base']),
                        'huesped' => null,
                        'fechas' => null,
                        'noches' => null,
                        'telefono' => null,
                        'hora_llegada' => null
                    ];

                    if ($estado_hab == 'ocupada' && isset($hab['ocupacion_actual'])) {
                        $tooltipData['huesped'] = $hab['ocupacion_actual']['nombre_completo'] ?? 'Huésped';
                        $entrada = $hab['ocupacion_actual']['fecha_entrada'] ?? null;
                        $salida = $hab['ocupacion_actual']['fecha_salida'] ?? null;

                        if ($entrada && $salida) {
                            $tooltipData['fechas'] = format_date($entrada) . ' - ' . format_date($salida);
                            try {
                                $fecha1 = new DateTime($entrada);
                                $fecha2 = new DateTime($salida);
                                $tooltipData['noches'] = $fecha1->diff($fecha2)->days;
                            } catch (Exception $e) {
                                $tooltipData['noches'] = null;
                            }
                        }
                        $tooltipData['telefono'] = $hab['ocupacion_actual']['telefono'] ?? null;

                    } elseif ($estado_hab == 'por_llegar' && isset($hab['reservacion_pendiente'])) {
                        $tooltipData['huesped'] = $hab['reservacion_pendiente']['nombre_completo'] ?? 'Huésped';
                        $fecha_entrada = $hab['reservacion_pendiente']['fecha_entrada'] ?? null;
                        if ($fecha_entrada) {
                            $tooltipData['fechas'] = 'Llega: ' . format_date($fecha_entrada);
                        }
                        $tooltipData['hora_llegada'] = $hab['reservacion_pendiente']['hora_llegada_estimada'] ?? null;
                        $tooltipData['telefono'] = $hab['reservacion_pendiente']['telefono'] ?? null;
                    }
                    ?>
                    <div class="<?= $colorRapido ?> qv-state-<?= htmlspecialchars($estado_hab) ?> rounded-lg p-2 text-center cursor-pointer transition-all hover:scale-105 shadow-md room-quick-view hb-quick-room"
                         role="button"
                         tabindex="0"
                         onkeydown="hbPressClick(event, this)"
                         data-estado="<?= htmlspecialchars($estado_hab) ?>"
                         title="<?= ($estado_hab == 'disponible' || $estado_hab == 'disponible_fecha') ? 'Crear reservación para habitación ' : 'Ver detalle de habitación ' ?><?= htmlspecialchars($hab['numero']) ?>"
                          onclick="<?= ($estado_hab == 'disponible' || $estado_hab == 'disponible_fecha') ? 'crearReservacionRapida(' . $hab['id'] . ')' : 'window.location.href=\'' . url('habitaciones/' . $hab['id']) . '\'' ?>"
                          data-tooltip='<?= htmlspecialchars(json_encode($tooltipData), ENT_QUOTES, 'UTF-8') ?>'>
                        <span class="qv-room-num font-bold text-sm"><?= htmlspecialchars($hab['numero']) ?></span>
                        <span class="qv-room-meta text-xs opacity-90"><?= htmlspecialchars($pisoCortado) ?></span>
                        <span class="qv-room-state"><?= htmlspecialchars($estados[$estado_hab]['label'] ?? 'Desconocido') ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>

            <!-- Leyenda -->
            <aside class="hb-quick-aside">
                <h4 class="hb-quick-aside-title">Estados</h4>
                <?php
                $quickLegend = [
                    [$hbLabelLibres, (int)($estadisticas['libres_hoy'] ?? ($estadisticas['disponibles'] ?? 0)), 'var(--qv-green)'],
                    ['Por llegar', (int)($estadisticas['por_llegar'] ?? 0), 'var(--qv-purple)'],
                    ['Ocupada', (int)($estadisticas['ocupadas'] ?? 0), 'var(--qv-occupied)'],
                    ['Limpieza', (int)($estadisticas['limpieza'] ?? 0), 'var(--qv-blue)'],
                    ['Mantenimiento', (int)($estadisticas['mantenimiento'] ?? 0), 'var(--qv-amber)'],
                ];
                ?>
                <div class="hb-quick-legend mt-4 pt-4 border-t flex flex-wrap gap-3 justify-center text-xs">
                    <?php foreach ($quickLegend as $legendItem): ?>
                        <div class="hb-quick-legend-item flex items-center gap-1" style="--legend-color: <?= $legendItem[2] ?>;">
                            <span class="hb-quick-legend-swatch w-4 h-4 rounded shadow-sm" aria-hidden="true"></span>
                            <span class="hb-quick-legend-name"><?= htmlspecialchars($legendItem[0]) ?></span>
                            <span class="hb-quick-legend-count"><?= $legendItem[1] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="hb-quick-hint">
                    <strong>Acción rápida</strong>
                    Las habitaciones disponibles inician reservación; las demás abren su detalle.
                </div>
            </aside>
        </div>
    </div>

    <!-- Tooltip container -->
    <div id="roomTooltip" class="room-tooltip"></div>
</div>
<!-- ════════════════════════════════════════════════════════════════════
     Modal de Limpieza Múltiple — rediseño responsive con identidad de
     limpieza (azul fresco = estado semántico --c-cleaning del sistema)
     sobre la esencia boutique de la vista (serif Cormorant, superficies
     marfil, acento oro, sombras suaves). El modal vive FUERA de
     .habitaciones-view, por eso define sus propios tokens --lm-* desde
     los --brand-* globales. Todos los selectores se prefijan con
     #modalLimpieza para ganar (por especificidad) a los overrides
     dispersos que comparte con #vistaRapidaModal.
     ════════════════════════════════════════════════════════════════════ -->
<style id="lm-cleaning-redesign">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&display=swap');

#modalLimpieza.lm-overlay{
  --lm-brand: var(--brand-primary, #1B2746);
  --lm-gold: var(--brand-accent, #BD9441);
  --lm-clean:#2F77E0; --lm-clean-deep:#1E5FBF; --lm-clean-bright:#5A9BF2;
  --lm-clean-soft:#E6EFFC; --lm-clean-mist:#F3F8FF;
  --lm-surface:#FFFFFF; --lm-surface-warm:#F5F5F7;
  --lm-line:#E7E1D4; --lm-line-cool:#D5E3F6;
  --lm-ink:#20293A; --lm-ink-soft:#5C6675; --lm-ink-faint:#8B94A3;
  --lm-radius:24px; --lm-radius-md:15px; --lm-radius-sm:11px;
  --lm-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --lm-shadow:0 34px 80px -30px rgba(16,32,64,.6), 0 10px 30px -18px rgba(16,32,64,.35);
  background:rgba(14,26,46,.55)!important;
  -webkit-backdrop-filter:blur(10px); backdrop-filter:blur(10px);
}

/* ── Diálogo ── */
#modalLimpieza .lm-dialog{
  position:relative; box-sizing:border-box;
  display:flex; flex-direction:column;
  width:min(680px, 100%);
  max-height:min(88vh, 760px); max-height:min(88dvh, 760px);
  overflow:hidden;
  background:var(--lm-surface);
  border-radius:var(--lm-radius); box-shadow:var(--lm-shadow), 0 0 0 1px rgba(213,227,246,.5);
  font-family:'DM Sans','Outfit',system-ui,-apple-system,sans-serif;
  color:var(--lm-ink);
  animation:lmPop .3s cubic-bezier(.22,1,.36,1);
}
#modalLimpieza .lm-dialog *{ box-sizing:border-box; }

/* ── Header con motivo de limpieza (azul fresco + burbujas) ── */
#modalLimpieza .lm-header{
  position:relative; overflow:hidden; flex:0 0 auto;
  display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:16px;
  min-height:94px; padding:20px 24px 20px 22px;
  border-radius:var(--lm-radius) var(--lm-radius) 0 0;
  background:linear-gradient(135deg, #2E6FD2 0%, #3D82EA 58%, #60A1FA 100%);
  color:#fff;
}
#modalLimpieza .lm-header::after{
  content:''; position:absolute; inset:0; pointer-events:none;
  background:
    radial-gradient(96% 74% at 16% -24%, rgba(255,255,255,.31), transparent 60%),
    linear-gradient(180deg, rgba(255,255,255,.1), transparent 46%);
}
#modalLimpieza .lm-bubbles{ position:absolute; inset:0; overflow:hidden; pointer-events:none; }
#modalLimpieza .lm-bubble{
  position:absolute; bottom:-24px; border-radius:50%; opacity:0;
  background:radial-gradient(circle at 32% 30%, rgba(255,255,255,.9), rgba(255,255,255,.28) 45%, rgba(255,255,255,.06) 70%);
  box-shadow:inset 0 0 6px rgba(255,255,255,.4);
  animation:lmRise linear infinite;
}
#modalLimpieza .lm-bubble--1{ left:12%; width:14px; height:14px; animation-duration:7s;   animation-delay:0s; }
#modalLimpieza .lm-bubble--2{ left:32%; width:9px;  height:9px;  animation-duration:9s;   animation-delay:1.4s; }
#modalLimpieza .lm-bubble--3{ left:56%; width:18px; height:18px; animation-duration:8s;   animation-delay:.6s; }
#modalLimpieza .lm-bubble--4{ left:74%; width:11px; height:11px; animation-duration:10s;  animation-delay:2.1s; }
#modalLimpieza .lm-bubble--5{ left:88%; width:7px;  height:7px;  animation-duration:6.5s; animation-delay:.9s; }

#modalLimpieza .lm-header__main{ position:relative; z-index:1; display:flex; align-items:center; gap:14px; min-width:0; flex:1; }
#modalLimpieza .lm-emblem{
  position:relative; flex:0 0 auto; width:50px; height:50px; border-radius:14px;
  display:grid; place-items:center;
  background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.34);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.34), 0 8px 18px -12px rgba(0,0,0,.42);
  -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px);
}
#modalLimpieza .lm-emblem i{ font-size:1.28rem; color:#fff; }
#modalLimpieza .lm-emblem__spark{
  position:absolute; top:-4px; right:-4px; width:14px; height:14px;
  background:linear-gradient(135deg,#fff,var(--lm-gold));
  clip-path:polygon(50% 0,60% 40%,100% 50%,60% 60%,50% 100%,40% 60%,0 50%,40% 40%);
  filter:drop-shadow(0 0 4px rgba(255,255,255,.75));
  animation:lmTwinkle 2.4s ease-in-out infinite;
}
#modalLimpieza .lm-header__text{ min-width:0; }
#modalLimpieza .lm-title{
  margin:0;
  font-family:var(--lm-serif)!important;
  font-weight:600;
  font-size:1.58rem;
  line-height:1.1;
  letter-spacing:0;
  color:#fff;
  text-wrap:balance;
}
#modalLimpieza .lm-subtitle{ margin:4px 0 0; font-size:.78rem; font-weight:700; line-height:1.28; color:rgba(255,255,255,.88); }
#modalLimpieza .lm-close{
  position:relative; z-index:1; flex:0 0 auto; width:38px; height:38px; border-radius:9px;
  border:1px solid rgba(255,255,255,.22); cursor:pointer;
  display:grid; place-items:center; background:rgba(255,255,255,.1); color:#fff; font-size:1rem;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.16);
  transition:background .18s ease, border-color .18s ease, transform .18s ease;
}
#modalLimpieza .lm-close:hover{ background:rgba(255,255,255,.22); border-color:rgba(255,255,255,.34); }
#modalLimpieza .lm-close:active{ transform:scale(.94); }
#modalLimpieza .lm-close:focus-visible{ outline:2px solid #fff; outline-offset:2px; }

/* ── Toolbar: progreso + selección rápida ── */
#modalLimpieza .lm-toolbar{
  flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:16px;
  padding:14px 22px; background:var(--lm-surface); border-bottom:1px solid var(--lm-line);
}
#modalLimpieza .lm-progress-wrap{ min-width:0; flex:1; }
#modalLimpieza .lm-count{ margin:0; display:flex; align-items:baseline; gap:6px; flex-wrap:wrap; }
#modalLimpieza .lm-count__n{ font-family:var(--lm-serif); font-weight:700; font-size:1.5rem; line-height:1; color:var(--lm-clean-deep); }
#modalLimpieza .lm-count__sep{ font-size:.82rem; color:var(--lm-ink-faint); }
#modalLimpieza .lm-count__total{ font-weight:700; font-size:.95rem; color:var(--lm-ink); }
#modalLimpieza .lm-count__label{ font-size:.82rem; color:var(--lm-ink-soft); }
#modalLimpieza .lm-progress{ margin-top:8px; height:6px; max-width:280px; border-radius:999px; background:var(--lm-clean-soft); overflow:hidden; }
#modalLimpieza .lm-progress__bar{ display:block; height:100%; width:0%; border-radius:999px; background:linear-gradient(90deg, var(--lm-clean), var(--lm-clean-bright)); transition:width .3s cubic-bezier(.22,1,.36,1); }
#modalLimpieza .lm-select{ flex:0 0 auto; display:flex; gap:8px; }
#modalLimpieza .lm-select__btn{
  display:inline-flex; align-items:center; gap:6px; cursor:pointer; min-height:40px;
  padding:8px 14px; border-radius:11px; font-size:.82rem; font-weight:600;
  border:1px solid transparent; transition:all .18s ease;
  background:var(--lm-clean-soft); color:var(--lm-clean-deep);
}
#modalLimpieza .lm-select__btn i{ font-size:.8rem; }
#modalLimpieza .lm-select__btn:hover{ background:color-mix(in srgb, var(--lm-clean) 18%, #fff); }
#modalLimpieza .lm-select__btn.is-active{ background:var(--lm-clean); color:#fff; box-shadow:0 8px 18px -10px var(--lm-clean); }
#modalLimpieza .lm-select__btn--ghost{ background:transparent; color:var(--lm-ink-soft); border-color:var(--lm-line); }
#modalLimpieza .lm-select__btn--ghost:hover{ background:var(--lm-surface-warm); color:var(--lm-ink); border-color:var(--lm-ink-faint); }
#modalLimpieza .lm-select__btn:focus-visible{ outline:2px solid var(--lm-clean); outline-offset:2px; }

/* ── Cuerpo / lista ── */
#modalLimpieza .lm-body{
  flex:1 1 auto; min-height:0; overflow-y:auto; padding:16px 22px 20px;
  background:linear-gradient(180deg, var(--lm-clean-mist) 0%, var(--lm-surface-warm) 120px);
}
#modalLimpieza .lm-list{ display:flex; flex-direction:column; gap:10px; }
#modalLimpieza .lm-room{
  position:relative; display:flex; align-items:center; gap:14px; cursor:pointer;
  padding:14px 16px; border-radius:var(--lm-radius-md);
  background:var(--lm-surface)!important; border:1.5px solid var(--lm-line)!important;
  box-shadow:0 1px 2px rgba(16,32,64,.04);
  transition:border-color .18s ease, background .18s ease, transform .18s ease, box-shadow .18s ease;
}
#modalLimpieza .lm-room:hover{
  border-color:var(--lm-line-cool)!important; background:var(--lm-clean-mist)!important;
  transform:translateY(-1px); box-shadow:0 10px 22px -14px rgba(47,119,224,.5);
}
#modalLimpieza .lm-room:has(.lm-room__input:checked){
  border-color:var(--lm-clean)!important; background:var(--lm-clean-mist)!important;
  box-shadow:0 0 0 1px var(--lm-clean) inset, 0 12px 24px -16px rgba(47,119,224,.6);
}
#modalLimpieza .lm-room__input{ position:absolute; opacity:0; width:1px; height:1px; margin:0; pointer-events:none; }
#modalLimpieza .lm-check{
  flex:0 0 auto; width:24px; height:24px; border-radius:8px; display:grid; place-items:center;
  background:#fff; border:2px solid var(--lm-line-cool); color:#fff;
  transition:all .18s cubic-bezier(.22,1,.36,1);
}
#modalLimpieza .lm-check i{ font-size:.7rem; opacity:0; transform:scale(.4); transition:all .18s cubic-bezier(.22,1,.36,1); }
#modalLimpieza .lm-room__input:checked + .lm-check{
  background:linear-gradient(135deg, var(--lm-clean), var(--lm-clean-deep));
  border-color:var(--lm-clean-deep); box-shadow:0 6px 14px -6px var(--lm-clean);
}
#modalLimpieza .lm-room__input:checked + .lm-check i{ opacity:1; transform:scale(1); }
#modalLimpieza .lm-room__input:focus-visible + .lm-check{ outline:2px solid var(--lm-clean); outline-offset:2px; }
#modalLimpieza .lm-room__emblem{
  flex:0 0 auto; width:42px; height:42px; border-radius:12px; display:grid; place-items:center;
  background:var(--lm-clean-soft); color:var(--lm-clean-deep); font-size:1.05rem; border:1px solid var(--lm-line-cool);
}
#modalLimpieza .lm-room__info{ flex:1; min-width:0; display:flex; flex-direction:column; gap:3px; }
#modalLimpieza .lm-room__name{ font-family:var(--lm-serif); font-weight:700; font-size:1.15rem; line-height:1; color:var(--lm-ink); }
#modalLimpieza .lm-room__meta{ display:flex; align-items:center; gap:7px; font-size:.76rem; color:var(--lm-ink-soft); min-width:0; }
#modalLimpieza .lm-room__chip{ display:inline-flex; align-items:center; gap:5px; flex:0 0 auto; }
#modalLimpieza .lm-room__chip i{ font-size:.72rem; color:var(--lm-ink-faint); }
#modalLimpieza .lm-room__dot{ color:var(--lm-ink-faint); flex:0 0 auto; }
#modalLimpieza .lm-room__type{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
#modalLimpieza .lm-badge{
  flex:0 0 auto; display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:999px;
  font-size:.72rem; font-weight:700; letter-spacing:.01em;
  background:var(--lm-clean-soft); color:var(--lm-clean-deep); border:1px solid var(--lm-line-cool);
}
#modalLimpieza .lm-badge i{ font-size:.72rem; }

/* ── Estado vacío ── */
/* Personal que hizo la limpieza (selector obligatorio del modal masivo) */
#modalLimpieza .lm-staff{ margin-top:14px; padding-top:12px; border-top:1px dashed var(--lm-line); }
#modalLimpieza .lm-staff__title{ margin:0; display:flex; align-items:center; gap:7px; font-weight:700; font-size:.9rem; color:var(--lm-ink); }
#modalLimpieza .lm-staff__title i{ color:var(--lm-clean); font-size:.85rem; }
#modalLimpieza .lm-staff__hint{ margin:3px 0 10px; font-size:.76rem; color:var(--lm-ink-soft); }
#modalLimpieza .lm-staff__list{ display:flex; flex-wrap:wrap; gap:8px; }
#modalLimpieza .lm-staff__chip{ display:inline-flex; align-items:center; gap:7px; padding:8px 13px; border:1.5px solid var(--lm-line);
  border-radius:999px; cursor:pointer; background:var(--lm-surface); font-size:.82rem; font-weight:600; color:var(--lm-ink);
  transition:border-color .15s ease, background .15s ease, color .15s ease; }
#modalLimpieza .lm-staff__chip:hover{ border-color:var(--lm-clean); background:var(--lm-clean-mist); }
#modalLimpieza .lm-staff__chip input{ width:15px; height:15px; accent-color:var(--lm-clean); margin:0; flex:0 0 auto; }
#modalLimpieza .lm-staff__chip:has(input:checked){ border-color:var(--lm-clean); background:var(--lm-clean-soft); color:var(--lm-clean-deep); }
#modalLimpieza .lm-staff__chip--none{ border-style:dashed; color:var(--lm-ink-soft); }
#modalLimpieza .lm-staff__chip--none input{ accent-color:var(--lm-ink-faint); }
#modalLimpieza .lm-staff__chip--none:hover{ border-color:var(--lm-ink-faint); background:var(--lm-surface-warm); }
#modalLimpieza .lm-staff__chip--none:has(input:checked){ border-color:var(--lm-ink-faint); background:var(--lm-slate-soft, #EEF1F4); color:var(--lm-ink); }

#modalLimpieza .lm-empty{ text-align:center; padding:40px 20px; }
#modalLimpieza .lm-empty__icon{ display:grid; place-items:center; width:64px; height:64px; margin:0 auto 14px; border-radius:20px; background:var(--lm-clean-soft); color:var(--lm-clean); font-size:1.7rem; }
#modalLimpieza .lm-empty__title{ margin:0; font-family:var(--lm-serif); font-weight:700; font-size:1.3rem; color:var(--lm-ink); }
#modalLimpieza .lm-empty__text{ margin:4px 0 0; font-size:.85rem; color:var(--lm-ink-soft); }

/* ── Footer ── */
#modalLimpieza .lm-footer{
  flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:12px;
  padding:16px 22px; padding-bottom:max(16px, env(safe-area-inset-bottom));
  background:var(--lm-surface); border-top:1px solid var(--lm-line);
}
#modalLimpieza .lm-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:9px; cursor:pointer;
  border:0; border-radius:13px; font-size:.9rem; font-weight:700; min-height:48px; padding:0 20px;
  font-family:inherit; transition:all .18s ease;
}
#modalLimpieza .lm-btn--ghost{ background:transparent; color:var(--lm-ink-soft); }
#modalLimpieza .lm-btn--ghost:hover{ background:var(--lm-surface-warm); color:var(--lm-ink); }
#modalLimpieza .lm-btn--primary{
  position:relative; overflow:hidden; color:#fff; padding:0 24px;
  background:linear-gradient(135deg, var(--lm-clean-deep), var(--lm-clean) 60%, var(--lm-clean-bright));
  box-shadow:0 14px 26px -12px rgba(47,119,224,.7);
}
#modalLimpieza .lm-btn--primary:hover:not(:disabled){ filter:brightness(1.05); transform:translateY(-1px); box-shadow:0 18px 32px -12px rgba(47,119,224,.8); }
#modalLimpieza .lm-btn--primary:active:not(:disabled){ transform:translateY(0) scale(.99); }
#modalLimpieza .lm-btn:focus-visible{ outline:2px solid var(--lm-clean-deep); outline-offset:2px; }
#modalLimpieza .lm-btn--primary:disabled{ background:#E9ECF1; color:#A7AEBA; box-shadow:none; cursor:not-allowed; }
#modalLimpieza .lm-btn__count{
  display:none; min-width:22px; height:22px; padding:0 7px; border-radius:999px; line-height:1;
  background:rgba(255,255,255,.25); color:#fff; font-size:.78rem; font-weight:700; align-items:center; justify-content:center;
}
#modalLimpieza .lm-btn__count.is-visible{ display:inline-flex; }
#modalLimpieza .lm-btn--primary:disabled .lm-btn__count{ background:rgba(0,0,0,.08); color:#A7AEBA; }
#modalLimpieza .lm-btn__shine{
  position:absolute; top:0; left:0; width:40%; height:100%; pointer-events:none;
  background:linear-gradient(100deg, transparent, rgba(255,255,255,.5), transparent);
  transform:translateX(-160%) skewX(-18deg);
}
#modalLimpieza .lm-btn--primary:hover:not(:disabled) .lm-btn__shine{ transition:transform .7s ease; transform:translateX(320%) skewX(-18deg); }

/* ── Animaciones ── */
@keyframes lmPop{ from{ opacity:0; transform:translateY(16px) scale(.98);} to{ opacity:1; transform:none;} }
@keyframes lmSheetIn{ from{ transform:translateY(100%);} to{ transform:none;} }
@keyframes lmTwinkle{ 0%,100%{ transform:scale(.7) rotate(0deg); opacity:.6;} 50%{ transform:scale(1) rotate(90deg); opacity:1;} }
@keyframes lmRise{ 0%{ transform:translateY(0) scale(.6); opacity:0;} 15%{ opacity:.6;} 80%{ opacity:.45;} 100%{ transform:translateY(-165px) scale(1); opacity:0;} }

/* ── Responsive: hoja inferior en móvil (mismo breakpoint 640px que la vista) ── */
@media (max-width:640px){
  #modalLimpieza .lm-dialog{
    width:100%; max-width:none; max-height:92dvh;
    border-radius:24px 24px 0 0; border-bottom:0;
    animation:lmSheetIn .34s cubic-bezier(.22,1,.36,1);
  }
  #modalLimpieza .lm-dialog::before{
    display:none;
  }
  #modalLimpieza .lm-header{ min-height:94px; padding:20px 24px 20px 22px; gap:16px; }
  #modalLimpieza .lm-emblem{ width:50px; height:50px; }
  #modalLimpieza .lm-title{ font-size:1.58rem; line-height:1.1; }
  #modalLimpieza .lm-subtitle{ font-size:.78rem; }
  #modalLimpieza .lm-toolbar{ padding:12px 16px; flex-wrap:wrap; gap:12px; }
  #modalLimpieza .lm-progress{ max-width:none; }
  #modalLimpieza .lm-select{ width:100%; }
  #modalLimpieza .lm-select__btn{ flex:1; justify-content:center; min-height:42px; }
  #modalLimpieza .lm-body{ padding:14px 16px 18px; }
  #modalLimpieza .lm-room{ padding:12px 14px; gap:12px; }
  #modalLimpieza .lm-room__emblem{ width:38px; height:38px; }
  #modalLimpieza .lm-room__name{ font-size:1.05rem; }
  #modalLimpieza .lm-footer{ padding:14px 16px; padding-bottom:max(14px, env(safe-area-inset-bottom)); gap:10px; }
  #modalLimpieza .lm-btn{ flex:1; min-height:50px; }
  #modalLimpieza .lm-btn--ghost{ flex:0 0 auto; padding:0 16px; }
}
@media (max-width:380px){
  #modalLimpieza .lm-badge span{ display:none; }
  #modalLimpieza .lm-badge{ padding:6px 9px; }
  #modalLimpieza .lm-room{ gap:10px; }
}

/* ── Accesibilidad: respetar reduce-motion ── */
@media (prefers-reduced-motion: reduce){
  #modalLimpieza .lm-dialog,
  #modalLimpieza .lm-emblem__spark,
  #modalLimpieza .lm-btn__shine,
  #modalLimpieza .lm-progress__bar{ animation:none!important; transition:none!important; }
  #modalLimpieza .lm-bubble{ display:none; }
  #modalLimpieza .lm-emblem__spark{ opacity:.9; }
}
</style>
<div id="modalLimpieza"
     class="lm-overlay fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="lmTitle" aria-describedby="lmSubtitle"
     data-ms-keep-sidebar
     onclick="if(event.target===this)cerrarModalLimpieza()">
    <div class="lm-dialog" role="document">

        <!-- Header: identidad de limpieza -->
        <header class="lm-header">
            <div class="lm-bubbles" aria-hidden="true">
                <span class="lm-bubble lm-bubble--1"></span>
                <span class="lm-bubble lm-bubble--2"></span>
                <span class="lm-bubble lm-bubble--3"></span>
                <span class="lm-bubble lm-bubble--4"></span>
                <span class="lm-bubble lm-bubble--5"></span>
            </div>
            <div class="lm-header__main">
                <span class="lm-emblem" aria-hidden="true">
                    <i class="fas fa-broom"></i>
                    <span class="lm-emblem__spark"></span>
                </span>
                <div class="lm-header__text">
                    <h3 id="lmTitle" class="lm-title">Marcar Habitaciones Limpias</h3>
                    <p id="lmSubtitle" class="lm-subtitle">Selecciona las habitaciones que ya están listas</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalLimpieza()" class="lm-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </header>

        <!-- Toolbar: progreso + selección rápida -->
        <div class="lm-toolbar">
            <div class="lm-progress-wrap">
                <p class="lm-count">
                    <span id="contadorSeleccionadas" class="lm-count__n">0</span>
                    <span class="lm-count__sep">de</span>
                    <span class="lm-count__total"><?= count($habitaciones_limpieza) ?></span>
                    <span class="lm-count__label">habitaciones seleccionadas</span>
                </p>
                <div class="lm-progress" role="progressbar" aria-label="Habitaciones seleccionadas" aria-valuemin="0" aria-valuemax="<?= count($habitaciones_limpieza) ?>">
                    <span id="lmProgress" class="lm-progress__bar"></span>
                </div>
            </div>
            <div class="lm-select" role="group" aria-label="Selección rápida">
                <button type="button" id="lmSelAll" class="lm-select__btn" onclick="seleccionarTodasLimpieza(true)">
                    <i class="fas fa-check-double"></i><span>Todas</span>
                </button>
                <button type="button" id="lmSelNone" class="lm-select__btn lm-select__btn--ghost" onclick="seleccionarTodasLimpieza(false)">
                    <i class="fas fa-eraser"></i><span>Ninguna</span>
                </button>
            </div>
        </div>

        <!-- Lista de habitaciones en limpieza -->
        <div class="lm-body">
            <div id="listaHabitacionesLimpieza" class="lm-list">
                <?php foreach ($habitaciones_limpieza as $hab): ?>
                <label class="lm-room">
                    <input type="checkbox" value="<?= $hab['id'] ?>" class="checkbox-limpieza lm-room__input" onchange="actualizarContadorLimpieza()">
                    <span class="lm-check" aria-hidden="true"><i class="fas fa-check"></i></span>
                    <span class="lm-room__emblem" aria-hidden="true"><i class="fas fa-door-open"></i></span>
                    <span class="lm-room__info">
                        <span class="lm-room__name">Habitación <?= $hab['numero'] ?></span>
                        <span class="lm-room__meta">
                            <span class="lm-room__chip"><i class="fas fa-layer-group"></i><?= $pisos[$hab['piso']] ?? 'Piso ' . $hab['piso'] ?></span>
                            <span class="lm-room__dot">•</span>
                            <span class="lm-room__type"><?= $hab['tipo'] ?></span>
                        </span>
                    </span>
                    <span class="lm-badge"><i class="fas fa-broom"></i><span>En limpieza</span></span>
                </label>
                <?php endforeach; ?>
            </div>

            <?php if (empty($habitaciones_limpieza)): ?>
            <div class="lm-empty">
                <span class="lm-empty__icon" aria-hidden="true"><i class="fas fa-check-circle"></i></span>
                <p class="lm-empty__title">Todo impecable</p>
                <p class="lm-empty__text">No hay habitaciones en limpieza ahora mismo.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($habitaciones_limpieza) && !empty($personal_limpieza['disponible'])): ?>
            <!-- Personal que hizo la limpieza (obligatorio: personas o "sin registrar") -->
            <div class="lm-staff">
                <p class="lm-staff__title"><i class="fas fa-user-check" aria-hidden="true"></i>¿Quién hizo la limpieza?</p>
                <p class="lm-staff__hint">Se registrará para todas las habitaciones seleccionadas. Puedes elegir a más de una persona.</p>
                <div class="lm-staff__list">
                    <?php foreach (($personal_limpieza['personal'] ?? []) as $pLimpieza): ?>
                    <label class="lm-staff__chip">
                        <input type="checkbox" class="lm-staff-check" value="<?= (int)$pLimpieza['id'] ?>">
                        <span><?= htmlspecialchars((string)$pLimpieza['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                    <?php endforeach; ?>
                    <label class="lm-staff__chip lm-staff__chip--none">
                        <input type="checkbox" id="lmSinPersonal">
                        <span>Sin registrar personal</span>
                    </label>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer con acciones -->
        <footer class="lm-footer">
            <button type="button" onclick="cerrarModalLimpieza()" class="lm-btn lm-btn--ghost">
                <i class="fas fa-times"></i><span>Cancelar</span>
            </button>
            <button type="button" id="btnMarcarLimpias" onclick="marcarHabitacionesLimpias()" class="lm-btn lm-btn--primary" disabled>
                <span class="lm-btn__shine" aria-hidden="true"></span>
                <i class="fas fa-check-double"></i>
                <span class="lm-btn__label">Marcar como Limpias</span>
                <span id="lmBtnCount" class="lm-btn__count">0</span>
            </button>
        </footer>

    </div>
</div>

<!-- Modal: Tareas del cuarto (panel read-only que enlaza a la seccion de Tareas) -->
<div id="modalTareasCuarto" class="tc-modal hidden">
    <div class="tc-dialog">
        <div class="bg-gradient-to-r from-slate-700 to-slate-800 text-white p-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="bg-white/20 p-2 rounded-lg"><i class="fas fa-list-check text-xl"></i></div>
                <div>
                    <h3 class="text-lg font-bold">Tareas del cuarto <span id="tareasCuartoTitulo"></span></h3>
                    <p class="text-xs text-slate-200">Seguimiento operativo. No cambia el estado de la habitación.</p>
                </div>
            </div>
            <button onclick="cerrarTareasCuarto()" class="text-white hover:text-slate-200 transition-colors p-2 hover:bg-white/10 rounded-lg">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-5" id="tareasCuartoBody">
            <!-- Se llena por JS -->
        </div>

        <div class="bg-gray-50 px-5 py-4 flex justify-between items-center border-t gap-3">
            <a id="linkVerTodasTareas" href="#" class="px-4 py-2 text-slate-700 hover:bg-gray-200 rounded-lg transition-colors font-medium text-sm">
                <i class="fas fa-arrow-up-right-from-square mr-2"></i>Ver todas
            </a>
            <button type="button" id="btnCrearTareaCuarto" onclick="crearTareaDesdeModal()"
                    class="px-5 py-2 bg-gradient-to-r from-slate-700 to-slate-800 text-white rounded-lg hover:from-slate-800 hover:to-slate-900 transition-all font-bold shadow-md text-sm">
                <i class="fas fa-plus mr-2"></i>Crear tarea
            </button>
        </div>
    </div>
</div>

<script>
/* ── Panel "Tareas del cuarto" (vínculo Habitaciones ↔ Tareas) ───────────── */
window.HB_PUEDE_CREAR_TAREA = <?= can('habitaciones.mantenimiento') ? 'true' : 'false' ?>;
(function () {
    const TC = { id: 0, numero: '', estado: '' };
    const URLS = {
        fetch: '<?= url('tareas/de-habitacion') ?>',
        crear: '<?= url('tareas/crear') ?>',
        lista: '<?= url('tareas') ?>',
        tarea: '<?= url('tareas') ?>'
    };
    const ESTADOS = {
        pendiente:  ['Pendiente',  'bg-amber-100 text-amber-800'],
        asignada:   ['Asignada',   'bg-blue-100 text-blue-800'],
        en_proceso: ['En proceso', 'bg-teal-100 text-teal-800'],
        completada: ['Completada', 'bg-green-100 text-green-700'],
        cancelada:  ['Cancelada',  'bg-gray-100 text-gray-500']
    };
    const CATS = { limpieza: 'Limpieza', mantenimiento: 'Mantenimiento', general: 'General' };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    function fmtFecha(v) {
        if (!v) return '—';
        const t = Date.parse(String(v).replace(' ', 'T'));
        if (isNaN(t)) return '—';
        const d = new Date(t);
        return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' }) + ' ' +
               d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
    }

    function urlCrear() {
        let u = URLS.crear + '?habitacion_id=' + encodeURIComponent(TC.id);
        if (TC.estado === 'limpieza') u += '&categoria=limpieza';
        return u;
    }

    window.abrirTareasCuarto = function (id, numero, estado) {
        TC.id = id; TC.numero = numero || ''; TC.estado = estado || '';
        document.getElementById('tareasCuartoTitulo').textContent = TC.numero ? ('— Hab. ' + TC.numero) : '';
        document.getElementById('linkVerTodasTareas').href = URLS.lista + '?buscar=' + encodeURIComponent(TC.numero);

        const btnCrear = document.getElementById('btnCrearTareaCuarto');
        if (btnCrear) {
            btnCrear.style.display = window.HB_PUEDE_CREAR_TAREA ? '' : 'none';
        }

        const modal = document.getElementById('modalTareasCuarto');
        modal.classList.remove('hidden');

        const body = document.getElementById('tareasCuartoBody');
        body.innerHTML = '<div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p class="text-sm">Cargando tareas…</p></div>';

        fetch(URLS.fetch + '/' + encodeURIComponent(id), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.ok) { throw new Error('respuesta'); }
                renderTareas(data.tareas || []);
            })
            .catch(function () {
                body.innerHTML = '<div class="text-center py-8 text-red-500"><i class="fas fa-triangle-exclamation text-2xl mb-2"></i><p class="text-sm">No se pudieron cargar las tareas.</p></div>';
            });
    };

    function renderTareas(tareas) {
        const body = document.getElementById('tareasCuartoBody');
        if (!tareas.length) {
            body.innerHTML = '<div class="text-center py-8 text-gray-500">' +
                '<i class="fas fa-clipboard-check text-3xl mb-3 text-green-500"></i>' +
                '<p class="font-medium">Sin tareas vinculadas</p>' +
                '<p class="text-sm">Este cuarto no tiene tareas registradas.</p></div>';
            return;
        }
        let html = '<div class="space-y-2">';
        tareas.forEach(function (t) {
            const est = ESTADOS[t.estado] || [t.estado || 'Sin estado', 'bg-gray-100 text-gray-600'];
            const cat = CATS[t.categoria] || (t.categoria || 'General');
            const trab = t.trabajador_nombre ? esc(t.trabajador_nombre) : 'Sin asignar';
            html +=
                '<a href="' + URLS.tarea + '/' + (t.id || 0) + '" class="flex items-center justify-between gap-3 p-3 rounded-lg border border-gray-200 hover:border-slate-400 hover:bg-slate-50 transition-colors">' +
                    '<div class="min-w-0">' +
                        '<div class="font-semibold text-gray-800 truncate">' + esc(t.titulo || ('Tarea #' + (t.id || 0))) + '</div>' +
                        '<div class="text-xs text-gray-500 mt-0.5 flex flex-wrap gap-x-3 gap-y-1">' +
                            '<span><i class="fas fa-tag mr-1"></i>' + esc(cat) + '</span>' +
                            '<span><i class="fas fa-user mr-1"></i>' + trab + '</span>' +
                            '<span><i class="fas fa-clock mr-1"></i>' + fmtFecha(t.fecha_limite) + '</span>' +
                        '</div>' +
                    '</div>' +
                    '<span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold ' + est[1] + '">' + esc(est[0]) + '</span>' +
                '</a>';
        });
        html += '</div>';
        body.innerHTML = html;
    }

    window.cerrarTareasCuarto = function () {
        document.getElementById('modalTareasCuarto').classList.add('hidden');
    };

    window.crearTareaDesdeModal = function () {
        window.location.href = urlCrear();
    };

    // Acceso directo desde el chip "Sin tareas · Crear"
    window.crearTareaCuarto = function (id, estado) {
        TC.id = id; TC.estado = estado || '';
        window.location.href = urlCrear();
    };

    // Cerrar al hacer clic en el backdrop
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('modalTareasCuarto');
        if (modal && e.target === modal) { modal.classList.add('hidden'); }
    });

    // Cerrar con Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalTareasCuarto');
            if (modal) { modal.classList.add('hidden'); }
        }
    });

    // CLAVE: mover el modal a <body> para que position:fixed se ancle al viewport
    // y no a un ancestro con transform/perspective (flip-cards).
    const modalEl = document.getElementById('modalTareasCuarto');
    if (modalEl && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
})();
</script>

<!-- Formulario oculto para check-out rápido -->
<form id="formCheckOut" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<!-- ════════════════════════════════════════════════════════════════════
     BOUTIQUE REFINEMENT LAYER — adaptación visual de "habitaciones.html"
     Solo CSS. Brand-aware (--brand-*). Estados con color semántico fijo.
     No altera flip-cards, formularios, JS, rutas ni la lógica de estados.
     Capa scopeada a .habitaciones-view para ganar especificidad sin tocar markup.
     ════════════════════════════════════════════════════════════════════ -->
<style id="hb-boutique-refinement">
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.habitaciones-view{
  /* Identidad del hotel (fallback boutique navy/oro) */
  --hb-primary: var(--brand-primary, #1B2746);
  --hb-secondary: var(--brand-secondary, #0F172A);
  --hb-accent: var(--brand-accent, #BD9441);
  --hb-ivory:#F5F5F7; --hb-ivory-2:#FAFAFC;
  --hb-surface:#FFFFFF; --hb-surface-warm:#F5F5F7;
  --hb-line:#E7E1D4; --hb-line-soft:#F0EBE0;
  --hb-slate-700:#3E4A66; --hb-slate-500:#6C7689; --hb-slate-400:#9AA1B2;
  /* Estados (significado fijo) */
  --c-available:#1E9E63; --bg-available:#E7F4EC;
  --c-occupied:#C2603C;  --bg-occupied:#F8EAE1;   /* OCUPADA = terracota/rojo (preferencia del usuario sobre el slate del HTML) */
  --c-arriving:#8039D0;  --bg-arriving:#EEE6FC;   /* POR LLEGAR = violeta franco (hue 268): el indigo #5A57D2 anterior se confundia con el azul de LIMPIEZA */
  --c-cleaning:#2F77E0;  --bg-cleaning:#E6EFFC;
  --c-maint:#C2841C;     --bg-maint:#FAF0DC;
  --c-critical:#D64539;  --bg-critical:#FBE9E7;
  --serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --hb-radius:16px; --hb-radius-lg:20px;
  --hb-shadow-xs:0 1px 2px rgba(27,39,70,.05);
  --hb-shadow-sm:0 1px 2px rgba(27,39,70,.05),0 2px 6px rgba(27,39,70,.05);
  --hb-shadow:0 4px 14px rgba(27,39,70,.07),0 22px 40px -24px rgba(27,39,70,.30);
}

/* ── Lienzo ── */
.habitaciones-view{
  
}
.habitaciones-view::before{ display:none !important; }

/* ── Header ── */
.habitaciones-view .modern-header{
  background:color-mix(in srgb,#fff 86%,transparent)!important;
  border-bottom:1px solid var(--hb-line)!important;
  box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 10px 26px -22px rgba(27,39,70,.6)!important;
}
.habitaciones-view .modern-header h1{
  font-family:var(--serif)!important; font-size:2rem!important; font-weight:600!important;
  color:var(--hb-primary)!important; letter-spacing:0!important; line-height:1!important;
}
.habitaciones-view .modern-header p{ color:var(--hb-slate-500)!important; }
.habitaciones-view .modern-header .p-2.rounded-lg{
  background:linear-gradient(150deg,var(--hb-primary),var(--hb-secondary))!important; border-radius:12px!important;
}

/* ── Widgets (6 semánticos) — visibles en desktop, ocultos en móvil (ver media query) ── */
.habitaciones-view .hb-stats{ display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:18px; }
.habitaciones-view .hb-stat{
  display:flex; flex-direction:column; text-decoration:none; background:var(--hb-surface);
  border:1px solid var(--hb-line); border-radius:var(--hb-radius); padding:14px 15px;
  box-shadow:var(--hb-shadow-sm); position:relative; overflow:hidden; --sc:var(--hb-primary);
}
.habitaciones-view .hb-stat::before,
.habitaciones-view .hb-stat::after{
  content:''; position:absolute; pointer-events:none; opacity:0; transition:opacity .18s ease;
}
.habitaciones-view .hb-stat::before{
  inset:9px 0 8px; border-radius:inherit;
  background:
    linear-gradient(90deg, color-mix(in srgb,var(--sc) 82%,transparent), transparent 4px),
    linear-gradient(270deg, color-mix(in srgb,var(--sc) 82%,transparent), transparent 4px);
}
.habitaciones-view .hb-stat::after{
  left:12px; right:12px; bottom:0; height:2px; border-radius:999px;
  background:linear-gradient(90deg, transparent, color-mix(in srgb,var(--sc) 72%,transparent) 14%, var(--sc) 50%, color-mix(in srgb,var(--sc) 72%,transparent) 86%, transparent);
}
.habitaciones-view .hb-stat.is-filter-active{
  border-color:color-mix(in srgb, var(--sc) 34%, var(--hb-line));
  background:linear-gradient(90deg, color-mix(in srgb, var(--sc) 7%, #fff) 0%, var(--hb-surface) 20%, var(--hb-surface) 80%, color-mix(in srgb, var(--sc) 7%, #fff) 100%);
  box-shadow:var(--hb-shadow-sm), inset 0 -1px 0 color-mix(in srgb,var(--sc) 45%,transparent);
}
.habitaciones-view .hb-stat.is-filter-active::before{ opacity:.9; }
.habitaciones-view .hb-stat.is-filter-active::after{ opacity:.72; }
.habitaciones-view .hb-stat-ic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:color-mix(in srgb,var(--sc) 13%,#fff); color:var(--sc); margin-bottom:10px; }
.habitaciones-view .hb-stat-ic i{ font-size:.95rem; }
.habitaciones-view .hb-stat-n{ font-family:var(--serif); font-size:2.05rem; font-weight:700; line-height:1; color:var(--hb-primary); font-variant-numeric:tabular-nums; }
.habitaciones-view .hb-stat-l{ font-size:.7rem; font-weight:700; letter-spacing:.03em; color:var(--hb-slate-500); margin-top:6px; text-transform:uppercase; }
.habitaciones-view .hb-stat-sub{ font-size:.62rem; font-weight:600; color:color-mix(in srgb, var(--sc) 68%, var(--hb-slate-500)); margin-top:3px; line-height:1.25; }
/* Franja operativa del dia (dimension aparte de las fichas de estado) */
.habitaciones-view .hb-today-strip{
  display:flex; align-items:center; flex-wrap:wrap; gap:6px 8px;
  background:var(--hb-surface); border:1px solid var(--hb-line); border-radius:12px;
  padding:8px 14px; margin-bottom:10px; font-size:.8rem; color:var(--hb-slate-500);
}
.habitaciones-view .hb-today-strip i{ color:var(--hb-accent); font-size:.8rem; }
.habitaciones-view .hb-today-strip strong{ color:var(--hb-slate-700); font-weight:700; }
.habitaciones-view .hb-today-strip em{ font-style:normal; font-weight:700; color:var(--c-cleaning); }
.habitaciones-view .hb-today-sep{ opacity:.5; }
.habitaciones-view .hb-stat--total{ --sc:var(--hb-primary); }
.habitaciones-view .hb-stat--available{ --sc:var(--c-available); }
.habitaciones-view .hb-stat--occupied{ --sc:var(--c-occupied); }
.habitaciones-view .hb-stat--arriving{ --sc:var(--c-arriving); }
.habitaciones-view .hb-stat--cleaning{ --sc:var(--c-cleaning); }
.habitaciones-view .hb-stat--maint{ --sc:var(--c-maint); }

/* ── Filtros ── */
.habitaciones-view .filter-input,
.habitaciones-view .filter-select,
.habitaciones-view .filter-date{
  background:var(--hb-surface-warm)!important; border:1px solid var(--hb-line)!important;
  border-radius:11px!important; color:var(--hb-slate-700)!important; font-weight:600!important;
}
.habitaciones-view .filter-input:focus,
.habitaciones-view .filter-select:focus,
.habitaciones-view .filter-date:focus{
  outline:none!important; border-color:var(--hb-accent)!important;
  box-shadow:0 0 0 3px color-mix(in srgb, var(--hb-accent) 22%, transparent)!important;
}
.habitaciones-view .filter-btn{ border-radius:11px!important; font-weight:700!important; }
.habitaciones-view .filter-btn-primary{
  background:linear-gradient(150deg,var(--hb-primary),var(--hb-secondary))!important; color:#fff!important; border-color:transparent!important;
  box-shadow:0 8px 18px -10px color-mix(in srgb,var(--hb-primary) 75%, transparent)!important;
}
.habitaciones-view .filter-btn-primary:hover{ transform:translateY(-1px)!important; }
.habitaciones-view .filter-btn-today{ background:var(--hb-surface)!important; border:1px solid var(--hb-line)!important; color:var(--hb-slate-700)!important; }
.habitaciones-view .filter-btn-reset{ background:var(--bg-critical)!important; color:var(--c-critical)!important; border-color:transparent!important; }
/* ── Barra de filtros con chips (como el diseño) ── */
.habitaciones-view .hb-filterbar{ display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.habitaciones-view .hb-search{ display:inline-flex; align-items:center; gap:8px; background:var(--hb-surface-warm); border:1px solid var(--hb-line); border-radius:11px; padding:8px 12px; flex:1 1 165px; min-width:150px; max-width:260px; }
.habitaciones-view .hb-search i{ color:var(--hb-slate-400); font-size:.8rem; flex:none; }
.habitaciones-view .hb-search input{ border:0!important; outline:0!important; background:transparent!important; font-size:.82rem; font-weight:600; color:var(--hb-slate-700); width:100%; }
.habitaciones-view .hb-search:focus-within{ border-color:var(--hb-accent); box-shadow:0 0 0 3px color-mix(in srgb, var(--hb-accent) 20%, transparent); }
.habitaciones-view .hb-fdiv{ width:1px; height:22px; background:var(--hb-line); margin:0 2px; }
.habitaciones-view .hb-chips{ display:flex; gap:7px; flex-wrap:wrap; }
.habitaciones-view .hb-chip{ --chip-c:var(--hb-primary); display:inline-flex; align-items:center; gap:7px; font-family:inherit; font-size:.78rem; font-weight:600; color:var(--hb-slate-700); background:var(--hb-surface-warm); border:1px solid var(--hb-line); padding:7px 13px; border-radius:999px; cursor:pointer; transition:transform .14s, border-color .14s, background .14s; white-space:nowrap; }
.habitaciones-view .hb-chip:hover{ border-color:var(--hb-accent); transform:translateY(-1px); }
/* Activo (Deleite Sereno): pastilla entintada del color del estado, sin losa negra */
.habitaciones-view .hb-chip.is-active{ background:color-mix(in srgb, var(--chip-c) 13%, #fff); color:color-mix(in srgb, var(--chip-c) 74%, #1F2937); border-color:color-mix(in srgb, var(--chip-c) 42%, #fff); box-shadow:0 6px 14px -8px color-mix(in srgb, var(--chip-c) 55%, transparent); }
.habitaciones-view .hb-chip.is-active .hb-chip-ct{ color:inherit; opacity:.78; }
.habitaciones-view .hb-chip-ct{ color:var(--hb-slate-400); font-weight:700; }
.habitaciones-view .hb-chip-dot{ width:8px; height:8px; border-radius:50%; flex:none; background:var(--chip-c); }
/* Color semántico de cada estado (lo consumen chip y leyenda móvil) */
.habitaciones-view .chip-available, .habitaciones-view .hb-mobile-lg[data-estado="disponible"]{ --chip-c:var(--c-available); }
.habitaciones-view .chip-occupied,  .habitaciones-view .hb-mobile-lg[data-estado="ocupada"]{ --chip-c:var(--c-occupied); }
.habitaciones-view .chip-arriving,  .habitaciones-view .hb-mobile-lg[data-estado="por_llegar"]{ --chip-c:var(--c-arriving); }
.habitaciones-view .chip-cleaning,  .habitaciones-view .hb-mobile-lg[data-estado="limpieza"]{ --chip-c:var(--c-cleaning); }
.habitaciones-view .chip-maint,     .habitaciones-view .hb-mobile-lg[data-estado="mantenimiento"]{ --chip-c:var(--c-maint); }
.habitaciones-view .hb-filter-right{ display:flex; align-items:center; gap:6px; margin-left:auto; }
@media (max-width:760px){ .habitaciones-view .hb-filter-right{ margin-left:0; } .habitaciones-view .hb-search{ max-width:none; } }
/* Escritorio: la barra va en UNA sola linea. Con flex-wrap:wrap el grupo de
   fecha/Hoy/Limpiar saltaba a un segundo renglon y, por su margin-left:auto,
   quedaba flotando solo a la derecha (se veia como una barra partida en dos).
   Ahora los chips son el elemento elastico: absorben el espacio libre y, si de
   verdad no cabe, scrollean en horizontal — mismo recurso que ya usa el layout
   movil — en vez de empujar a nadie a otra fila. */
@media (min-width:768px){
  .habitaciones-view .hb-filterbar{ flex-wrap:nowrap; }
  /* Factor de shrink ALTO: el reparto de flex es base x factor, y la base de los
     chips (~685px) aplasta a la del buscador (200px). Con un factor normal los
     chips se comian casi todo el recorte y siempre quedaba uno cortado; con 100
     el buscador se lleva ~97% del ajuste y cede hasta su min-width antes de que
     los chips pierdan un pixel. */
  .habitaciones-view .hb-search{ flex:0 100 200px; min-width:128px; }
  .habitaciones-view .hb-chips{
    /* shrink NORMAL (no 0): si los chips se niegan a ceder, una vez que el
       buscador toca su min-width el sobrante empuja al grupo Hoy/Limpiar FUERA
       de la tarjeta (medido a 1024px: 111px de desborde). Cediendo, el sobrante
       se convierte en scroll horizontal de los chips y nada se sale. */
    flex:1 1 auto;
    min-width:0;
    flex-wrap:nowrap;
    overflow-x:auto;
    scrollbar-width:none;
    gap:5px;
  }
  .habitaciones-view .hb-chips::-webkit-scrollbar{ height:0; }
  .habitaciones-view .hb-chip{ flex:0 0 auto; padding:7px 9px; }
  .habitaciones-view .hb-filter-right{ flex:0 0 auto; margin-left:0; gap:5px; }
  .habitaciones-view .hb-filter-right .filter-date{ font-size:.76rem; }
}
/* La barra NO mide lo que el viewport: la sidebar se come ~370px, asi que a
   1440 quedan ~1067px y los 6 chips con etiqueta completa no caben junto al
   buscador y los 3 controles. Debajo de 1600 los botones Hoy/Limpiar van solo
   con icono (conservan su title) — es el mismo recurso que ya usa el layout
   movil con las clases .hidden sm:inline del marcado. */
@media (min-width:768px) and (max-width:1599px){
  .habitaciones-view .hb-filter-right .filter-btn span{ display:none; }
  .habitaciones-view .hb-filter-right .filter-btn{ padding-left:10px; padding-right:10px; }
}

/* ════ Animación "hoja de acciones que sube" (como Medisoft Habitaciones.html) ════
   La tarjeta NO se expande: queda a altura fija y la hoja (reverso) se desliza
   por encima de la cara, que permanece detrás. Reusa el toggle .flipped del JS. */
.habitaciones-view .flip-card,
.habitaciones-view .flip-card.flipped{ height:186px!important; }
.habitaciones-view .flip-card.flipped{ border-top-left-radius:22px!important; }
@media (max-width:768px){ .habitaciones-view .flip-card, .habitaciones-view .flip-card.flipped{ height:192px!important; } }
@media (max-width:640px){ .habitaciones-view .flip-card, .habitaciones-view .flip-card.flipped{ height:200px!important; } }
.habitaciones-view .flip-card.flipped .flip-card-front{ opacity:1!important; }
.habitaciones-view .flip-card-back{ transition:top .42s cubic-bezier(.22,1,.36,1)!important; overflow-y:auto!important; padding:13px 15px!important; }
.habitaciones-view .flip-card.flipped .flip-card-back{ transition:top .42s cubic-bezier(.22,1,.36,1)!important; }
.habitaciones-view .flip-card-back::-webkit-scrollbar{ width:0; height:0; }
/* hoja compacta para que quepa a altura fija */
.habitaciones-view .flip-card-back h4{ font-size:1.05rem!important; margin-bottom:6px!important; }
.habitaciones-view .flip-card-back .info-item{ font-size:.72rem!important; margin-bottom:2px!important; }
.habitaciones-view .flip-card-back .action-buttons{ margin-top:8px!important; }
/* Hoja = gradiente del color SEMÁNTICO del estado (igual que el HTML: gradient(--c) → 74% --c + negro) */
.habitaciones-view .flip-card-back{ background:linear-gradient(160deg, var(--sheet-c, var(--hb-primary)) 0%, color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 74%, #000) 100%)!important; color:#fff!important; }
.habitaciones-view .flip-card-back .btn-action{ background:rgba(255,255,255,.16)!important; border:1px solid rgba(255,255,255,.28)!important; color:#fff!important; }
.habitaciones-view .flip-card-back .btn-action:hover{ background:rgba(255,255,255,.3)!important; }
.habitaciones-view .flip-card-back .btn-primary{ background:#fff!important; color:var(--sheet-c, var(--hb-primary))!important; border-color:#fff!important; }
.habitaciones-view .flip-card-back .btn-primary:hover{ background:rgba(255,255,255,.92)!important; }

/* ── TARJETAS DE HABITACIÓN (rediseño boutique) ── */
.habitaciones-view #habitaciones-grid{ gap:15px!important; }
.habitaciones-view .room-card-compact{ border-radius:var(--hb-radius)!important; }
.habitaciones-view .room-card-compact:hover{ box-shadow:var(--hb-shadow)!important; }
.habitaciones-view .flip-card-front{
  border:1px solid var(--hb-line)!important; border-radius:var(--hb-radius)!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.55)!important; padding:0!important; overflow:hidden!important;
}
.habitaciones-view .flip-card-front::before,
.habitaciones-view .flip-card-front::after{ display:none!important; }
/* superficie semántica + barra de acento izquierda */
.habitaciones-view .estado-disponible,.habitaciones-view .estado-disponible_fecha{ background:var(--bg-available)!important; border-left:4px solid var(--c-available)!important; background-size:auto!important; }
.habitaciones-view .estado-ocupada,.habitaciones-view .estado-ocupada_fecha{ background:var(--bg-occupied)!important; border-left:4px solid var(--c-occupied)!important; background-size:auto!important; }
.habitaciones-view .estado-por_llegar,.habitaciones-view .estado-doble{ background:var(--bg-arriving)!important; border-left:4px solid var(--c-arriving)!important; }
.habitaciones-view .estado-limpieza,.habitaciones-view .estado-limpieza-por-llegar{ background:var(--bg-cleaning)!important; border-left:4px solid var(--c-cleaning)!important; }
.habitaciones-view .estado-mantenimiento{ background:var(--bg-maint)!important; border-left:4px solid var(--c-maint)!important; }

/* layout de la cara */
.habitaciones-view .rc-face{ position:relative; height:100%; display:flex; flex-direction:column; padding:13px 15px 13px 18px; }
.habitaciones-view .rc-stripe{ position:absolute; top:14px; left:0; width:5px; height:26px; border-radius:0 3px 3px 0; box-shadow:0 1px 3px rgba(0,0,0,.18); }
.habitaciones-view .rc-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.habitaciones-view .rc-num{ font-family:var(--serif)!important; font-size:1.95rem!important; font-weight:700!important; line-height:.92!important; color:var(--hb-primary)!important; letter-spacing:0!important; font-variant-numeric:tabular-nums; }
.habitaciones-view .rc-type{ font-size:.68rem; font-weight:600; color:var(--hb-slate-500); margin-top:3px; text-transform:uppercase; letter-spacing:.03em; }
.habitaciones-view .rc-badge{ display:inline-flex; align-items:center; gap:5px; font-size:.6rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; padding:5px 9px; border-radius:999px; color:#fff; white-space:nowrap; background:var(--hb-primary); box-shadow:0 2px 6px -2px rgba(27,39,70,.4); }
.habitaciones-view .rc-badge i{ font-size:.58rem; }
.habitaciones-view .estado-disponible .rc-badge,.habitaciones-view .estado-disponible_fecha .rc-badge{ background:var(--c-available); }
.habitaciones-view .estado-ocupada .rc-badge,.habitaciones-view .estado-ocupada_fecha .rc-badge{ background:var(--c-occupied); }
.habitaciones-view .estado-por_llegar .rc-badge,.habitaciones-view .estado-doble .rc-badge{ background:var(--c-arriving); }
.habitaciones-view .estado-limpieza .rc-badge,.habitaciones-view .estado-limpieza-por-llegar .rc-badge{ background:var(--c-cleaning); }
.habitaciones-view .estado-mantenimiento .rc-badge{ background:var(--c-maint); }
.habitaciones-view .rc-mid{ margin-top:auto; min-width:0; }
.habitaciones-view .rc-guest{ display:flex; align-items:center; gap:6px; font-size:.8rem; font-weight:600; color:var(--hb-primary); min-width:0; }
.habitaciones-view .rc-guest i{ font-size:.68rem; color:var(--hb-slate-400); flex:none; }
.habitaciones-view .rc-guest span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.habitaciones-view .rc-guest--empty{ color:var(--hb-slate-400); font-weight:500; }
.habitaciones-view .rc-meta{ font-size:.68rem; color:var(--hb-slate-500); margin-top:2px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.habitaciones-view .rc-room-facts{ display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; min-width:0; }
.habitaciones-view .rc-room-fact{ display:inline-flex; align-items:center; gap:4px; max-width:100%; min-height:22px; padding:4px 8px; border:1px solid color-mix(in srgb,var(--hb-line) 82%,#fff); border-radius:999px; background:rgba(255,255,255,.72); color:var(--hb-heading); font-size:.6rem; font-weight:850; line-height:1; white-space:nowrap; overflow:hidden; box-shadow:inset 0 1px 0 rgba(255,255,255,.76); }
.habitaciones-view .rc-room-fact i{ flex:none; font-size:.58rem; color:color-mix(in srgb,var(--room-accent-color) 74%,var(--hb-heading)); }
.habitaciones-view .rc-room-fact span{ min-width:0; overflow:hidden; text-overflow:ellipsis; }
.habitaciones-view .rc-foot{ display:flex; align-items:center; justify-content:space-between; margin-top:9px; gap:8px; }
.habitaciones-view .rc-price{ font-size:.82rem; font-weight:800; color:var(--hb-primary); white-space:nowrap; }
.habitaciones-view .rc-price small{ font-weight:600; color:var(--hb-slate-400); font-size:.6rem; }
.habitaciones-view .rc-hint{ display:inline-flex; align-items:center; gap:4px; font-size:.6rem; font-weight:700; color:var(--hb-slate-400); white-space:nowrap; }
.habitaciones-view .rc-owner{ min-width:0; max-width:46%; display:inline-flex; align-items:center; justify-content:flex-end; gap:5px; font-size:.62rem; font-weight:800; color:color-mix(in srgb, var(--hb-primary) 82%, var(--hb-slate-600)); white-space:nowrap; }
.habitaciones-view .rc-owner i{ font-size:.68rem; color:var(--hb-accent); }
.habitaciones-view .rc-owner span{ min-width:0; overflow:hidden; text-overflow:ellipsis; }

/* ── Rediseño 2026-07-10: altura por contenido (adiós hueco muerto) ──
   Antes ambas caras eran absolutas y la altura salía de capas legacy:
   en habitaciones libres quedaba un vacío entre el número y los datos.
   Ahora la cara frontal vive en flujo normal y dicta la altura; la
   trasera sigue siendo overlay deslizante (top:100% → 3px). El bloque
   medio fluye tras el encabezado y el pie se ancla abajo; el grid
   iguala alturas por fila con stretch. */
.habitaciones-view .flip-card{ height:auto!important; min-height:150px!important; }
.habitaciones-view .flip-card-inner{ position:relative!important; height:100%!important; min-height:144px!important; display:flex!important; flex-direction:column!important; }
.habitaciones-view .flip-card-front{ position:relative!important; top:0!important; left:0!important; right:auto!important; bottom:auto!important; width:100%!important; height:auto!important; flex:1 1 auto!important; display:flex!important; flex-direction:column!important; }
.habitaciones-view .rc-face{ flex:1 1 auto!important; height:auto!important; }
.habitaciones-view .rc-mid{ margin-top:10px!important; }
.habitaciones-view .rc-foot{ margin-top:auto!important; padding-top:9px; }
.habitaciones-view .flip-card.flipped{ height:186px!important; }
.habitaciones-view .room-card-compact{ height:auto!important; }

/* indicadores (esquina) — conservados, refinados */
.habitaciones-view .checkout-today-indicator,
.habitaciones-view .checkout-vencido-indicator,
.habitaciones-view .checkin-vencido-indicator,
.habitaciones-view .late-arrival-indicator{ border-radius:999px!important; letter-spacing:.03em!important; box-shadow:0 3px 8px -2px rgba(0,0,0,.25)!important; z-index:5; }

/* ── Reverso / hoja de acciones ── */
.habitaciones-view .flip-card-back{ border-radius:var(--hb-radius)!important; }
.habitaciones-view .flip-card-back h4{ font-family:var(--serif)!important; font-weight:600!important; font-size:1.25rem!important; }
.habitaciones-view .flip-card-back .action-buttons{ gap:8px!important; }
.habitaciones-view .flip-card-back .btn-action{ border-radius:10px!important; font-weight:700!important; backdrop-filter:blur(4px)!important; border:1px solid rgba(255,255,255,.28)!important; background:rgba(255,255,255,.16)!important; }
.habitaciones-view .flip-card-back .btn-action:hover{ background:rgba(255,255,255,.3)!important; }
.habitaciones-view .flip-card-back .btn-primary{ background:#fff!important; color:var(--sheet-c,var(--hb-primary))!important; border:none!important; box-shadow:0 4px 12px -4px rgba(0,0,0,.3)!important; }

/* ── Empty state ── */
.habitaciones-view .p-8.text-center{ background:var(--hb-ivory-2)!important; border:1px dashed var(--hb-line)!important; border-radius:var(--hb-radius)!important; }
.habitaciones-view .p-8.text-center .bg-gray-100{ background:color-mix(in srgb,var(--hb-accent) 16%, #fff)!important; color:var(--hb-accent)!important; }
.habitaciones-view .p-8.text-center .text-gray-400{ color:var(--hb-accent)!important; }

/* ── Modales ── */
#vistaRapidaModal .bg-white.rounded-xl, #modalLimpieza .bg-white.rounded-xl{ border-radius:18px!important; box-shadow:0 28px 70px -24px rgba(27,39,70,.45)!important; }

/* ── Responsive ── */
@media (max-width:1100px){ .habitaciones-view .hb-stats{ grid-template-columns:repeat(3,1fr); } }
@media (max-width:560px){
  .habitaciones-view .hb-stats{ grid-template-columns:repeat(2,1fr); gap:10px; }
  .habitaciones-view .modern-header h1{ font-size:1.6rem!important; }
  .habitaciones-view .rc-num{ font-size:1.8rem!important; }
}

/* serif (gana al universal DM Sans via ID specificity) */
#mainHeader h1{ font-family:var(--serif)!important; }
#hbStats .hb-stat-n{ font-family:var(--serif)!important; }
#habitaciones-grid .rc-num{ font-family:var(--serif)!important; }
#habitaciones-grid .flip-card-back h4{ font-family:var(--serif)!important; }
#habitaciones-grid .floor-t{ font-family:var(--serif)!important; }

/* ── Secciones por piso ── */
.habitaciones-view #habitaciones-grid{ display:block!important; }
.habitaciones-view .floor-section{ margin-bottom:4px; }
.habitaciones-view .floor-label{ display:flex; align-items:center; gap:14px; margin:24px 2px 14px; }
.habitaciones-view .floor-section:first-child .floor-label{ margin-top:4px; }
.habitaciones-view .floor-t{ font-size:1.35rem; font-weight:600; color:var(--hb-primary); white-space:nowrap; line-height:1; }
.habitaciones-view .floor-rule{ flex:1; height:1px; background:var(--hb-line); }
.habitaciones-view .floor-ct{ font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--hb-slate-400); white-space:nowrap; }
.habitaciones-view .rgrid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(216px, 1fr)); gap:15px; }
@media (max-width:560px){ .habitaciones-view .rgrid{ grid-template-columns:1fr; gap:12px; } }

/* ════ Animaciones (adaptadas de habitaciones.html) ════ */
.habitaciones-view .room-card-compact:not(.flipped):hover{ transform:translateY(-3px)!important; }
/* Hoja de acciones: el reverso revela info + botones de forma escalonada al voltear */
@keyframes hbReveal{ from{ opacity:0; transform:translateY(10px); } to{ opacity:1; transform:none; } }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item{ animation:hbReveal .34s cubic-bezier(.22,1,.36,1) backwards; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(2){ animation-delay:.05s; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(3){ animation-delay:.10s; }
.habitaciones-view .flip-card.flipped .flip-card-back .info-item:nth-child(4){ animation-delay:.15s; }
.habitaciones-view .flip-card.flipped .flip-card-back .action-buttons{ animation:hbReveal .36s cubic-bezier(.22,1,.36,1) .18s backwards; }
/* Modales propios: pop al abrir (vista rápida y limpieza) */
@keyframes hbModalPop{ from{ opacity:0; transform:translateY(14px) scale(.985); } to{ opacity:1; transform:none; } }
#vistaRapidaModal:not(.hidden) > .bg-white, #modalLimpieza:not(.hidden) > .bg-white{ animation:hbModalPop .26s cubic-bezier(.22,1,.36,1); }
/* Tiles de vista rápida: micro-zoom ya existente; respetar reduce-motion */
@media (prefers-reduced-motion: reduce){
  .habitaciones-view .room-card-compact, .habitaciones-view .flip-card-back .info-item,
  .habitaciones-view .rc-incident--reservation-pending,
  .habitaciones-view .rc-incident--reservation-pending::after,
  .habitaciones-view .rc-incident--reservation-pending i,
  .habitaciones-view .flip-card-back .action-buttons,
  #vistaRapidaModal > .bg-white, #modalLimpieza > .bg-white{ animation:none!important; transition:none!important; }
  .habitaciones-view .room-card-compact:not(.flipped):hover{ transform:none!important; }
}

/* ════ Modales — SweetAlert2 + propios (marca, NO --ms-*) ════ */
/* nivel body: SweetAlert vive fuera de .habitaciones-view → usar --brand-* directo */
.swal2-popup{ border-radius:20px!important; box-shadow:0 28px 70px -24px rgba(27,39,70,.45)!important; }
.swal2-title{ color:var(--brand-primary,#1B2746)!important; }
.swal2-styled.swal2-confirm{ border:0!important; border-radius:11px!important; font-weight:700!important; box-shadow:0 10px 22px -12px rgba(27,39,70,.45)!important; }
.swal2-styled.swal2-confirm:hover{ filter:brightness(1.06); }
/* Íconos decorativos de formularios DENTRO de SweetAlert → marca (el púrpura semántico de "por llegar" en leyendas vive fuera de swal y no se toca) */
.swal2-popup .text-purple-600{ color:var(--brand-primary,#1B2746)!important; }
.swal2-popup .text-purple-800{ color:var(--brand-secondary,#0F172A)!important; }
.swal2-popup .bg-purple-600{ background:var(--brand-primary,#1B2746)!important; }
/* Estados interactivos (hover/focus) de los formularios de modales → marca (chrome, no semántica) */
.swal2-popup [class*="hover:bg-purple-50"]:hover{ background-color:color-mix(in srgb, var(--brand-primary,#1B2746) 7%, #fff)!important; }
.swal2-popup [class*="hover:bg-purple-100"]:hover{ background-color:color-mix(in srgb, var(--brand-primary,#1B2746) 11%, #fff)!important; }
.swal2-popup [class*="hover:text-purple-800"]:hover{ color:var(--brand-primary,#1B2746)!important; }
.swal2-popup [class*="ring-purple"]:focus{ box-shadow:0 0 0 3px color-mix(in srgb, var(--brand-accent,#BD9441) 38%, transparent)!important; }
#modalLimpieza [class*="ring-blue"]:focus{ box-shadow:0 0 0 3px color-mix(in srgb, var(--brand-accent,#BD9441) 38%, transparent)!important; }
#modalLimpieza .text-blue-600.checkbox-limpieza, #modalLimpieza input.checkbox-limpieza{ accent-color:var(--brand-primary,#1B2746); }
/* Reverso / hoja de acciones: encabezado con separador (estilo sheet del diseño) */
.habitaciones-view .flip-card-back h4{ border-bottom:1px solid rgba(255,255,255,.22)!important; padding-bottom:7px!important; margin-bottom:5px!important; letter-spacing:.01em; }
.swal2-styled.swal2-confirm:focus{ box-shadow:0 0 0 3px color-mix(in srgb, var(--brand-primary,#1B2746) 30%, transparent)!important; }
.swal2-styled.swal2-cancel{ border-radius:11px!important; font-weight:700!important; }
.swal2-popup.hb-swal-checkout{
  width:min(520px,calc(100vw - 24px))!important;
  max-height:min(92dvh,760px)!important;
  padding:0!important;
  display:flex!important;
  flex-direction:column!important;
  overflow:hidden!important;
}
.hb-swal-checkout .swal2-title{
  flex:0 0 auto;
  padding:20px 24px 10px!important;
  font-size:1.14rem!important;
  line-height:1.2!important;
}
.hb-swal-checkout .swal2-html-container{
  flex:1 1 auto!important;
  min-height:0!important;
  margin:0!important;
  padding:0 24px!important;
  overflow:visible!important;
}
.hb-checkout-shell{
  min-height:0;
  display:grid;
  gap:14px;
  text-align:left;
}
.hb-checkout-room-list{
  max-height:min(38dvh,260px);
  overflow-y:auto;
  padding:2px 4px 2px 0;
  scrollbar-width:thin;
  scrollbar-color:#F97316 #FFEDD5;
}
.hb-checkout-room-list::-webkit-scrollbar{ width:8px; }
.hb-checkout-room-list::-webkit-scrollbar-track{ background:#FFEDD5; border-radius:999px; }
.hb-checkout-room-list::-webkit-scrollbar-thumb{ background:#F97316; border-radius:999px; }
.hb-checkout-room-option{
  min-height:48px;
}
.hb-swal-checkout .swal2-actions{
  flex:0 0 auto;
  width:100%;
  margin:0!important;
  padding:16px 24px 20px!important;
  display:grid!important;
  grid-template-columns:1fr 1fr;
  gap:10px;
  border-top:1px solid var(--hb-line,#E7DDCA);
  background:var(--hb-surface-warm,#FAFAFC);
}
.hb-swal-checkout .swal2-actions .swal2-styled{
  width:100%;
  min-height:44px;
  margin:0!important;
}
@media (max-width:640px){
  .swal2-popup.hb-swal-checkout{
    width:calc(100vw - 16px)!important;
    max-height:92dvh!important;
    border-radius:22px 22px 0 0!important;
  }
  .hb-swal-checkout .swal2-title{ padding:18px 18px 8px!important; }
  .hb-swal-checkout .swal2-html-container{ padding:0 18px!important; }
  .hb-swal-checkout .swal2-actions{
    grid-template-columns:1fr;
    padding:14px 18px calc(16px + env(safe-area-inset-bottom, 0px))!important;
  }
}
.swal2-popup.hb-swal-checkin{
  width:min(430px,calc(100vw - 28px))!important;
  padding:0!important;
  border:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 24%,#E7DDCA)!important;
  border-radius:24px!important;
  overflow:hidden!important;
  background:#FAFAFC!important;
  box-shadow:0 32px 82px -28px rgba(12,18,32,.66)!important;
}
.hb-swal-checkin::before{
  content:'';
  display:block;
  height:9px;
  background:linear-gradient(90deg,var(--brand-primary,#1B2746),color-mix(in srgb,var(--brand-accent,#BD9441) 72%,#fff));
}
.hb-swal-checkin .swal2-icon{
  margin:22px auto 8px!important;
  border-color:color-mix(in srgb,#148653 52%,#D7DEE8)!important;
  color:#148653!important;
}
.hb-swal-checkin .swal2-title{
  padding:0 24px!important;
  color:var(--brand-secondary,#0F172A)!important;
  font-size:1.15rem!important;
  line-height:1.18!important;
  font-weight:900!important;
  letter-spacing:0!important;
}
.hb-swal-checkin .swal2-html-container{
  margin:8px 24px 0!important;
  color:#667085!important;
  font-size:.88rem!important;
  font-weight:700!important;
  line-height:1.45!important;
}
.hb-swal-checkin .swal2-actions{
  width:100%!important;
  margin:20px 0 0!important;
  padding:16px!important;
  display:grid!important;
  grid-template-columns:1fr 1.18fr;
  gap:10px!important;
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 4%,#fff)!important;
  border-top:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 10%,#E7DDCA)!important;
}
.hb-swal-checkin .hb-swal-cancel,
.hb-swal-checkin .hb-swal-confirm{
  width:100%!important;
  min-height:44px!important;
  margin:0!important;
  border-radius:14px!important;
  font-weight:900!important;
  box-shadow:none!important;
}
.hb-swal-checkin .hb-swal-cancel{
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 12%,#D7DEE8)!important;
  background:#fff!important;
  color:var(--brand-secondary,#0F172A)!important;
}
.hb-swal-checkin .hb-swal-confirm{
  border:0!important;
  background:linear-gradient(135deg,#148653,#0F6F49)!important;
  color:#fff!important;
  box-shadow:0 16px 32px -20px rgba(20,134,83,.9)!important;
}
@media (max-width:420px){
  .hb-swal-checkin .swal2-actions{ grid-template-columns:1fr; }
}
/* Tarjetas selectoras "Cliente Nuevo / Existente" del flujo Reservar */
.brand-hover-card{ transition:all .18s ease!important; }
.brand-hover-card:hover{ background:var(--brand-primary,#1B2746)!important; border-color:var(--brand-primary,#1B2746)!important; color:#fff!important; transform:translateY(-2px); box-shadow:0 12px 24px -12px rgba(27,39,70,.55)!important; }
.brand-text{ color:var(--brand-primary,#1B2746)!important; }
/* Flujo de reservacion rapida: selector de cliente + hora de llegada */
.hb-swal .swal2-title{
  padding:0!important;
  font-size:1.18rem!important;
  letter-spacing:0!important;
  line-height:1.18!important;
}
.hb-swal{
  position:relative!important;
  border:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 18%,#E7DDCA)!important;
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 6%,#FFFFFF)!important;
}
.hb-swal-client{
  width:min(460px,calc(100vw - 28px))!important;
}
.hb-swal .swal2-actions{
  gap:10px!important;
}
.hb-swal .swal2-styled{
  min-height:42px!important;
  padding:0 18px!important;
}
.hb-swal-html{
  overflow:visible!important;
}
.hb-quick-client{
  text-align:left;
}
.hb-quick-client__copy{
  margin:0 0 14px;
  color:var(--hb-slate-500,#64748B);
  font-size:.88rem;
  line-height:1.45;
  text-align:left;
}
.hb-quick-client__grid{
  display:grid;
  grid-template-columns:1fr;
  gap:10px;
}
.hb-client-card{
  appearance:none;
  position:relative;
  overflow:hidden;
  width:100%;
  min-height:76px;
  padding:13px 14px;
  border:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 18%,#E7DDCA);
  border-radius:15px;
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 3%,#fff);
  color:var(--brand-primary,#1B2746);
  display:grid;
  grid-template-columns:44px minmax(0,1fr) 18px;
  align-items:center;
  gap:12px;
  text-align:left;
  box-shadow:0 1px 2px rgba(27,39,70,.05);
  cursor:pointer;
  transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.hb-client-card:hover,
.hb-client-card:focus-visible{
  transform:translateY(-1px);
  border-color:color-mix(in srgb,var(--brand-primary,#1B2746) 34%,var(--brand-accent,#BD9441));
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 6%,#fff);
  box-shadow:0 14px 28px -24px rgba(27,39,70,.45);
  outline:none;
}
.hb-client-card__icon{
  width:42px;
  height:42px;
  border-radius:13px;
  display:grid;
  place-items:center;
  color:var(--brand-primary,#1B2746);
  background:#fff;
  box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--brand-accent,#BD9441) 22%,transparent);
}
.hb-client-card--existing .hb-client-card__icon{
  color:var(--brand-primary,#1B2746);
  background:#fff;
  box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--brand-primary,#1B2746) 14%,transparent);
}
.hb-client-card__icon i{
  font-size:.98rem;
}
.hb-client-card__body{
  display:grid;
  gap:3px;
  min-width:0;
}
.hb-client-card__label{
  color:var(--brand-primary,#1B2746);
  font-size:.95rem;
  font-weight:900;
  line-height:1.1;
}
.hb-client-card__hint{
  color:var(--hb-slate-500,#64748B);
  font-size:.75rem;
  font-weight:700;
  line-height:1.25;
}
.hb-client-card__arrow{
  position:static;
  justify-self:end;
  color:color-mix(in srgb,var(--brand-primary,#1B2746) 50%,#fff);
  font-size:.76rem;
  transition:transform .18s ease;
}
.hb-client-card:hover .hb-client-card__arrow,
.hb-client-card:focus-visible .hb-client-card__arrow{
  transform:translateX(3px);
}
.swal2-popup.hb-swal-client{
  width:min(640px,calc(100vw - 28px))!important;
  padding:0!important;
  border:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 24%,#E7DDCA)!important;
  border-radius:24px!important;
  overflow:hidden!important;
  background:#FAFAFC!important;
  box-shadow:0 32px 82px -28px rgba(12,18,32,.66)!important;
}
.hb-swal-client .swal2-title{
  display:none!important;
}
.hb-swal-client .swal2-html-container{
  margin:0!important;
  padding:0!important;
  overflow:visible!important;
}
.swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
.swal2-container.hb-swal-sheet-container.swal2-noanimation{
  background:linear-gradient(180deg,rgba(12,17,28,.78),rgba(12,17,28,.70))!important;
  backdrop-filter:blur(5px);
}
.hb-swal-client .swal2-close{
  width:38px!important;
  height:38px!important;
  margin:10px 10px 0 0!important;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 12%,#E7DDCA)!important;
  border-radius:12px!important;
  color:var(--brand-primary,#1B2746)!important;
  background:#FFFFFF!important;
  box-shadow:0 10px 20px -18px rgba(27,39,70,.45)!important;
}
.hb-swal-client .swal2-actions{
  width:100%!important;
  margin:0!important;
  padding:0 18px 18px!important;
  justify-content:stretch!important;
}
.hb-swal-client .hb-swal-cancel{
  width:100%!important;
  min-height:44px!important;
  margin:0!important;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 12%,#D7DEE8)!important;
  border-radius:14px!important;
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 6%,#FFFFFF)!important;
  color:var(--brand-primary,#1B2746)!important;
  font-size:.86rem!important;
  font-weight:900!important;
  box-shadow:none!important;
}
.hb-client-choice{
  display:grid;
  gap:0;
  background:#FAFAFC;
  text-align:left;
}
.hb-client-choice__head{
  display:grid;
  grid-template-columns:52px minmax(0,1fr);
  align-items:center;
  gap:14px;
  padding:24px 24px 18px;
  border-bottom:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 18%,#E7DDCA);
  background:
    linear-gradient(135deg,color-mix(in srgb,var(--brand-accent,#BD9441) 12%,#FFFFFF),#FFFFFF 66%);
}
.hb-client-choice__mark{
  width:52px;
  height:52px;
  border-radius:16px;
  display:grid;
  place-items:center;
  color:#FFFFFF;
  background:linear-gradient(145deg,var(--brand-primary,#1B2746),var(--brand-secondary,#0F172A));
  box-shadow:0 14px 26px -16px rgba(27,39,70,.55);
}
.hb-client-choice__mark i{
  font-size:1.05rem;
}
.hb-client-choice__eyebrow{
  display:block;
  margin-bottom:5px;
  color:color-mix(in srgb,var(--brand-primary,#1B2746) 58%,var(--brand-accent,#BD9441));
  font-size:.68rem;
  font-weight:900;
  letter-spacing:.09em;
  line-height:1;
  text-transform:uppercase;
}
.hb-client-choice h3{
  margin:0;
  color:var(--brand-primary,#1B2746);
  font-size:clamp(1.18rem,2.7vw,1.46rem);
  font-weight:900;
  line-height:1.12;
  letter-spacing:0;
}
.hb-client-choice__head p{
  margin:7px 0 0;
  color:var(--hb-slate-500,#64748B);
  font-size:.86rem;
  line-height:1.42;
}
.hb-client-choice__options{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px;
  padding:18px;
}
.hb-client-option{
  appearance:none;
  position:relative;
  width:100%;
  min-height:188px;
  padding:16px;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 11%,#E7DDCA);
  border-radius:18px;
  background:#fff;
  color:var(--brand-primary,#1B2746);
  display:flex;
  flex-direction:column;
  gap:13px;
  text-align:left;
  cursor:pointer;
  box-shadow:0 1px 2px rgba(27,39,70,.05), 0 16px 34px -30px rgba(27,39,70,.42);
  transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.hb-client-option::before{
  content:"";
  position:absolute;
  inset:0;
  pointer-events:none;
  border-radius:inherit;
  background:linear-gradient(180deg,rgba(255,255,255,.72),rgba(255,255,255,0));
}
.hb-client-option:hover,
.hb-client-option:focus-visible{
  transform:translateY(-2px);
  border-color:color-mix(in srgb,var(--brand-primary,#1B2746) 36%,var(--brand-accent,#BD9441));
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 6%,#fff);
  box-shadow:0 18px 36px -26px rgba(27,39,70,.44);
  outline:none;
}
.hb-client-option__top{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}
.hb-client-option__icon{
  width:42px;
  height:42px;
  border-radius:13px;
  display:grid;
  place-items:center;
  color:var(--brand-primary,#1B2746);
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 14%,#fff);
  box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--brand-accent,#BD9441) 28%,transparent);
}
.hb-client-option--existing .hb-client-option__icon{
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 7%,#fff);
  box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--brand-primary,#1B2746) 18%,transparent);
}
.hb-client-option__tag{
  min-height:24px;
  padding:5px 9px;
  border-radius:999px;
  color:color-mix(in srgb,var(--brand-primary,#1B2746) 68%,var(--brand-accent,#BD9441));
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 9%,#fff);
  font-size:.66rem;
  font-weight:900;
  line-height:1;
}
.hb-client-option__body{
  display:grid;
  gap:7px;
  position:relative;
  z-index:1;
}
.hb-client-option__body strong{
  color:var(--brand-primary,#1B2746);
  font-size:1.03rem;
  font-weight:900;
  line-height:1.08;
}
.hb-client-option__body span{
  color:var(--hb-slate-500,#64748B);
  font-size:.8rem;
  font-weight:650;
  line-height:1.42;
}
.hb-client-option__cta{
  position:relative;
  z-index:1;
  margin-top:auto;
  padding-top:11px;
  border-top:1px solid color-mix(in srgb,var(--brand-accent,#BD9441) 15%,#ECE5D8);
  color:var(--brand-primary,#1B2746);
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  font-size:.78rem;
  font-weight:900;
}
.hb-client-option__cta i{
  transition:transform .18s ease;
}
.hb-client-option:hover .hb-client-option__cta i,
.hb-client-option:focus-visible .hb-client-option__cta i{
  transform:translateX(3px);
}
@media (max-width:640px){
  .swal2-popup.hb-swal-client{
    width:calc(100vw - 22px)!important;
    border-radius:22px!important;
  }
  .hb-client-choice__head{
    grid-template-columns:46px minmax(0,1fr);
    gap:12px;
    padding:20px 18px 16px;
  }
  .hb-client-choice__mark{
    width:46px;
    height:46px;
    border-radius:14px;
  }
  .hb-client-choice__options{
    grid-template-columns:1fr;
    padding:14px;
    gap:10px;
  }
  .hb-client-option{
    min-height:0;
    padding:14px;
    display:grid;
    grid-template-columns:1fr;
    gap:11px;
  }
  .hb-client-option__body strong{
    font-size:.98rem;
  }
  .hb-client-option__body span{
    font-size:.76rem;
  }
  .hb-client-option__cta{
    padding-top:10px;
  }
  .hb-swal-client .swal2-actions{
    padding:0 14px 14px!important;
  }
}
.hb-reservation-step{
  text-align:left;
}
.hb-reservation-pill{
  display:inline-flex;
  align-items:center;
  gap:8px;
  margin:0 auto 14px;
  padding:9px 13px;
  border-radius:999px;
  color:var(--brand-primary,#1B2746);
  background:color-mix(in srgb,var(--brand-accent,#BD9441) 12%,#fff);
  font-size:.83rem;
  font-weight:900;
}
.hb-reservation-pill--existing{
  color:#047857;
  background:color-mix(in srgb,#10B981 13%,#fff);
}
.hb-reservation-summary{
  padding:14px;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 10%,#E7DDCA);
  border-radius:18px;
  background:linear-gradient(180deg,#fff,color-mix(in srgb,var(--brand-accent,#BD9441) 5%,#fff));
}
.hb-reservation-dates{
  display:grid;
  gap:8px;
  margin-bottom:13px;
}
.hb-date-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:8px 0;
  border-bottom:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 7%,transparent);
}
.hb-date-row:last-child{
  border-bottom:0;
}
.hb-date-row span{
  color:var(--hb-slate-500,#64748B);
  font-size:.78rem;
  font-weight:800;
}
.hb-date-row strong{
  color:var(--brand-primary,#1B2746);
  font-size:.9rem;
  font-weight:900;
}
.hb-arrival-card{
  padding-top:14px;
  border-top:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 10%,#E7DDCA);
}
.hb-arrival-label{
  display:block;
  margin-bottom:3px;
  color:var(--brand-primary,#1B2746);
  font-size:.92rem;
  font-weight:900;
}
.hb-arrival-copy{
  margin:0 0 10px;
  color:var(--hb-slate-500,#64748B);
  font-size:.78rem;
  line-height:1.35;
}
.hb-arrival-control{
  display:grid;
  grid-template-columns:minmax(0,1fr) auto;
  gap:10px;
}
.hb-arrival-input{
  width:100%;
  min-width:0;
  height:52px;
  padding:0 13px;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 15%,#E7DDCA);
  border-radius:14px;
  background:#fff;
  color:var(--brand-primary,#1B2746);
  font-size:16px;
  font-weight:850;
  outline:none;
}
.hb-arrival-input:focus{
  border-color:var(--brand-accent,#BD9441);
  box-shadow:0 0 0 3px color-mix(in srgb,var(--brand-accent,#BD9441) 28%,transparent);
}
.hb-arrival-now{
  min-width:88px;
  height:52px;
  padding:0 14px;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 14%,#E7DDCA);
  border-radius:14px;
  background:var(--brand-primary,#1B2746);
  color:#fff;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:7px;
  font-size:.82rem;
  font-weight:900;
  transition:transform .16s ease, filter .16s ease;
}
.hb-arrival-now:hover,
.hb-arrival-now:focus-visible{
  filter:brightness(1.08);
  outline:none;
}
.hb-arrival-now:active{
  transform:scale(.98);
}
/* Modal Limpieza: header + botón primario a marca (como Vista Rápida) */
#modalLimpieza .bg-gradient-to-r{ background:linear-gradient(135deg, var(--brand-primary,#1B2746), var(--brand-secondary,#0F172A))!important; }
#modalLimpieza .text-blue-600, #modalLimpieza .text-blue-700{ color:var(--brand-primary,#1B2746)!important; }
/* Acento dorado de marca para detalles/realces de los modales propios */
#vistaRapidaModal .vr-accent, #modalLimpieza .vr-accent{ color:var(--brand-accent,#BD9441)!important; }
/* Modales propios: pulido visual sin cambiar callbacks */
#vistaRapidaModal,
#modalLimpieza{ background:rgba(18,22,34,.52)!important; backdrop-filter:blur(10px); }
#vistaRapidaModal > .bg-white,
#modalLimpieza > .bg-white{
  border:1px solid var(--hb-line)!important;
  background:var(--hb-surface)!important;
  box-shadow:0 28px 70px -24px rgba(18,22,34,.5)!important;
}
#vistaRapidaModal > .bg-white > div:first-child,
#modalLimpieza > .bg-white > div:first-child{
  border-bottom:1px solid rgba(255,255,255,.16)!important;
}
#vistaRapidaContainer .room-quick-view{
  border-radius:12px!important;
  border:1px solid var(--hb-line)!important;
}
#modalLimpieza .checkbox-limpieza{ accent-color:var(--brand-primary,#1B2746); }
#modalLimpieza label{
  border-color:var(--hb-line)!important;
  background:var(--hb-surface-warm)!important;
}
#modalLimpieza label:hover{
  border-color:color-mix(in srgb,var(--brand-primary,#1B2746) 28%,var(--hb-line))!important;
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 5%,var(--hb-surface-warm))!important;
}
.swal2-popup{
  border:1px solid var(--hb-line,#E7DDCA)!important;
  background:var(--hb-surface,#FFFFFF)!important;
}
.swal2-html-container{
  color:var(--hb-slate-500,#64748B)!important;
}
.habitaciones-view .hb-hidden{ display:none!important; }
.habitaciones-view #hbNoResults{ grid-column:1/-1; }

/* ═══ HEADER MINIMALISTA — solo visible en móvil ≤767px ═══ */
.habitaciones-view .hb-page-header{
    display: none;
    padding: 4px 0 10px;
}
.habitaciones-view .hb-page-header__top{
    margin-bottom: 8px;
}
.habitaciones-view .hb-page-title{
    font-family: var(--serif, Georgia, serif);
    font-size: 1.6rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--hb-primary, #1B2746);
    margin: 0;
}
.habitaciones-view .hb-page-subtitle{
    font-size: .76rem;
    color: var(--hb-slate-400, #94A3B8);
    margin: 2px 0 0;
    font-weight: 500;
}
.habitaciones-view .hb-page-actions{
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    align-items: center;
    padding: 4px 0 6px;
}
.habitaciones-view .hb-action-btn{
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 11px;
    border-radius: 9px;
    font-size: .76rem;
    font-weight: 700;
    border: 1px solid transparent;
    cursor: pointer;
    text-decoration: none;
    transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
    white-space: nowrap;
    position: relative;
    flex: 1 1 0;
    justify-content: center;
}
.habitaciones-view .hb-action-btn i{ font-size: .70rem; }
.habitaciones-view .hb-action-btn:hover{ transform: translateY(-1px); }
.habitaciones-view .hb-action-btn--primary{
    background: var(--brand-primary, #1B2746);
    color: #fff;
    box-shadow: 0 8px 20px -10px color-mix(in srgb, var(--brand-primary,#1B2746) 50%, transparent);
}
.habitaciones-view .hb-action-btn--primary:hover{
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--brand-primary,#1B2746) 60%, transparent);
}
.habitaciones-view .hb-action-btn--outline{
    background: #fff;
    color: var(--hb-primary, #1B2746);
    border-color: var(--hb-line, #E2D9C8);
}
.habitaciones-view .hb-action-btn--outline:hover{
    background: var(--hb-surface-warm, #FAF8F4);
}
.habitaciones-view .hb-action-btn--soft{
    background: color-mix(in srgb, var(--brand-primary,#1B2746) 9%, #fff);
    color: var(--hb-primary, #1B2746);
    border-color: color-mix(in srgb, var(--brand-primary,#1B2746) 18%, #E2D9C8);
}
.habitaciones-view .hb-action-btn--badge{
    position: relative;
}
.habitaciones-view .hb-action-btn--badge::after{
    content: attr(data-badge);
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 999px;
    background: #E53E3E;
    color: #fff;
    font-size: .65rem;
    font-weight: 900;
    display: grid;
    place-items: center;
}

/* ═══ OCUPACIÓN GENERAL — solo visible en móvil (ver media query ≤767px) ═══ */
.habitaciones-view .hb-mobile-occupancy{
    display: none;
}
.habitaciones-view .hb-mobile-occupancy-head{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 9px;
    color: var(--hb-primary, #1B2746);
}
.habitaciones-view .hb-mobile-occupancy-head span{
    color: var(--hb-slate-400, #94A3B8);
    font-size: .67rem;
    font-weight: 850;
    letter-spacing: .09em;
    text-transform: uppercase;
}
.habitaciones-view .hb-mobile-occupancy-head strong{
    font-size: .82rem;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    text-align: right;
    line-height: 1.2;
}
.habitaciones-view .hb-mobile-occupancy-head strong b{
    display: block;
    font-size: .95rem;
}
.habitaciones-view .hb-mobile-occupancy-head strong small{
    display: block;
    font-size: .66rem;
    font-weight: 600;
    color: var(--hb-slate-400, #94A3B8);
}
.habitaciones-view .hb-mobile-occbar{
    display: flex;
    gap: 2px;
    height: 8px;
    border-radius: 999px;
    overflow: hidden;
    background: var(--hb-line-soft, #F0EAE0);
}
.habitaciones-view .hb-mobile-occbar span{ display: block; min-width: 3px; }
.habitaciones-view .hb-mobile-legend{
    display: flex;
    flex-wrap: wrap;
    gap: 6px 18px;
    margin-top: 10px;
}
.habitaciones-view .hb-mobile-lg{
    --chip-c: var(--hb-primary);
    display: inline-flex;
    flex-direction: row;
    align-items: center;
    gap: 5px;
    border: 0;
    padding: 0;
    background: transparent;
    color: var(--hb-slate-600, #4B5563);
    font-size: .78rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
}
.habitaciones-view .hb-mobile-dot{ width: 8px; height: 8px; border-radius: 999px; flex: none; }
.habitaciones-view .hb-mobile-lg b{ color: var(--hb-primary, #1B2746); font-weight: 700; margin-left: 1px; }
.habitaciones-view .hb-mobile-lg.is-active{ color: color-mix(in srgb, var(--chip-c) 78%, #1F2937); font-weight: 700; }

/* ═══ PANEL DE MOVIMIENTOS DEL DÍA (desktop + mobile) ═══ */
.habitaciones-view .hb-movements{
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
.habitaciones-view .hb-move-card{
    background: #fff;
    border: 1px solid var(--hb-line, #E2D9C8);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--hb-shadow-xs, 0 1px 4px rgba(18,22,34,.06));
}
.habitaciones-view .hb-move-head{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 14px;
    background: transparent !important;
    border-bottom: 1px solid var(--hb-line, #E2D9C8);
}
.habitaciones-view .hb-move-head h3{
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    margin: 0;
    font-size: .84rem;
}
.habitaciones-view .hb-move-title{
    display: flex;
    align-items: center;
    gap: 8px;
}
.habitaciones-view .hb-move-title strong{
    display: block;
    font-size: .84rem;
    font-weight: 700;
    color: var(--hb-primary, #1B2746);
}
.habitaciones-view .hb-move-title small{
    display: block;
    font-size: .70rem;
    color: var(--hb-slate-400, #94A3B8);
    font-weight: 500;
}
.habitaciones-view .hb-move-head--in i{ color: var(--c-arriving, #7C3AED) !important; }
.habitaciones-view .hb-move-head--out i{ color: var(--c-maint, #D97706) !important; }
.habitaciones-view .hb-move-head h3 > span:last-child{
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 800;
    display: grid;
    place-items: center;
    color: #fff;
}
.habitaciones-view .hb-move-head--in h3 > span:last-child{ background: var(--c-arriving, #7C3AED); }
.habitaciones-view .hb-move-head--out h3 > span:last-child{ background: var(--c-maint, #D97706); }
.habitaciones-view .hb-move-body{
    max-height: 200px;
    overflow-y: auto;
    padding: 6px 8px;
}
.habitaciones-view .hb-move-item{
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 6px;
    border-radius: 10px;
    border: 1px solid transparent;
    margin-bottom: 4px;
    background: transparent;
}
.habitaciones-view .hb-move-item:last-child{ margin-bottom: 0; }
.habitaciones-view .hb-move-item:hover{ background: var(--hb-surface-warm, #FAF8F4); }
.habitaciones-view .hb-move-item[data-href]{ cursor: pointer; }
.habitaciones-view .hb-move-item[data-href]:focus-visible{
    outline: 2px solid color-mix(in srgb, var(--c-arriving, #7C3AED) 65%, transparent);
    outline-offset: -2px;
}
.habitaciones-view .hb-move-item p:first-child{
    font-size: .84rem;
    font-weight: 700;
    color: var(--hb-primary, #1B2746);
    margin: 0 0 2px;
}
.habitaciones-view .hb-move-item p:last-child{
    font-size: .72rem;
    color: var(--hb-slate-400, #94A3B8);
    margin: 0;
}
.habitaciones-view .hb-move-action{
    color: var(--hb-slate-400, #94A3B8) !important;
    padding: 4px;
}
.habitaciones-view .hb-move-action:hover{ color: var(--hb-primary, #1B2746) !important; }
.habitaciones-view .hb-move-empty{
    font-size: .80rem;
    color: var(--hb-slate-400, #94A3B8);
    padding: 14px 8px;
    text-align: center;
    margin: 0;
}
@media (max-width: 640px){
    .habitaciones-view .hb-movements{ grid-template-columns: 1fr; gap: 8px; }
    .habitaciones-view .hb-action-btn--desktop-only{ display: none; }
}
.habitaciones-view .hb-mobile-sheet-back,
.habitaciones-view .hb-mobile-room-sheet{ display:none; }
body.hb-mobile-sheet-open{ overflow:hidden; }
body.hb-modal-open{ overflow:hidden; }

@media (max-width:640px){
  .habitaciones-view{
    min-height:100dvh;
    
  }

  .habitaciones-view .hb-page-header{ display:block!important; }
  .habitaciones-view .modern-header{
    display:none!important;
    background:color-mix(in srgb, var(--hb-ivory) 94%, #fff 6%)!important;
    box-shadow:0 10px 28px -24px rgba(18,22,34,.45)!important;
  }
  .habitaciones-view .modern-header .container{ padding:10px 13px 11px!important; }
  .habitaciones-view .modern-header .container > .flex{ align-items:stretch!important; gap:10px!important; }
  .habitaciones-view .modern-header .container > .flex > .flex.items-center{ width:100%; justify-content:flex-start; gap:10px!important; }
  .habitaciones-view .modern-header .p-2.rounded-lg{
    width:30px!important;
    height:40px!important;
    padding:0!important;
    display:grid!important;
    place-items:center!important;
    border-radius:0!important;
    background:transparent!important;
    box-shadow:none!important;
  }
  .habitaciones-view .modern-header .p-2.rounded-lg i{
    color:var(--hb-primary)!important;
    font-size:1.15rem!important;
  }
  .habitaciones-view .modern-header h1{
    font-size:1.48rem!important;
    line-height:.95!important;
    letter-spacing:0!important;
    white-space:nowrap;
  }
  .habitaciones-view .modern-header .hb-title-prefix{ display:none; }
  .habitaciones-view .modern-header p{
    display:block!important;
    margin-top:3px!important;
    color:var(--hb-slate-500)!important;
    font-size:.68rem!important;
    line-height:1.1!important;
  }
  .habitaciones-view .modern-header .flex.flex-wrap.gap-2{
    width:100%;
    display:grid!important;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:7px!important;
  }
  .habitaciones-view .modern-header .btn-modern{
    min-width:0;
    min-height:41px;
    padding:7px 6px!important;
    border-radius:12px!important;
    display:flex!important;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    gap:3px;
    font-size:.62rem!important;
    line-height:1.05!important;
    box-shadow:var(--hb-shadow-xs)!important;
  }
  .habitaciones-view .modern-header .btn-modern i{ margin:0!important; font-size:.88rem!important; }
  .habitaciones-view .modern-header .btn-modern span{
    display:inline!important;
    max-width:100%;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  .habitaciones-view > .container{ max-width:none!important; padding:12px 12px 24px!important; }
  .habitaciones-view .hb-stats{ display:none!important; }
  .habitaciones-view .hb-mobile-occupancy{
    display:block;
    margin:0 0 10px;
    padding:11px 12px 12px;
    border:1px solid var(--hb-line);
    border-radius:16px;
    background:var(--hb-surface-warm);
    box-shadow:var(--hb-shadow-xs);
  }
  .habitaciones-view .hb-mobile-occupancy-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin-bottom:9px;
    color:var(--hb-primary);
  }
  .habitaciones-view .hb-mobile-occupancy-head span{
    color:var(--hb-slate-400);
    font-size:.72rem;
    font-weight:850;
    letter-spacing:.08em;
    text-transform:uppercase;
  }
  .habitaciones-view .hb-mobile-occupancy-head strong{
    font-size:.84rem;
    font-weight:900;
    font-variant-numeric:tabular-nums;
  }
  .habitaciones-view .hb-mobile-occbar{
    display:flex;
    gap:3px;
    height:13px;
    border-radius:999px;
    overflow:hidden;
    background:var(--hb-line-soft);
  }
  .habitaciones-view .hb-mobile-occbar span{ display:block; min-width:3px; }
  .habitaciones-view .hb-mobile-legend{
    display:flex;
    flex-wrap:wrap;
    gap:7px 12px;
    margin-top:10px;
  }
  .habitaciones-view .hb-mobile-lg{
    display:inline-flex;
    align-items:center;
    gap:5px;
    border:0;
    padding:0;
    background:transparent;
    color:var(--hb-slate-500);
    font-size:.68rem;
    font-weight:750;
    line-height:1.1;
  }
  .habitaciones-view .hb-mobile-dot{ width:8px; height:8px; border-radius:999px; flex:none; }
  .habitaciones-view .hb-mobile-lg b{ color:var(--hb-primary); font-weight:900; }
  .habitaciones-view .hb-mobile-lg.is-active{ color:color-mix(in srgb,var(--chip-c) 78%,#1F2937); }

  .habitaciones-view .hb-filter-panel{
    padding:0!important;
    margin:0 0 14px!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
    box-shadow:none!important;
    overflow:visible;
  }
  .habitaciones-view .hb-filter-panel > .bg-blue-50{ margin:10px 10px 0!important; border-radius:12px!important; }
  .habitaciones-view .hb-filterbar{
    display:grid!important;
    grid-template-columns:1fr;
    gap:10px!important;
    padding:0!important;
  }
  .habitaciones-view .hb-search{
    width:100%;
    max-width:none!important;
    min-height:46px;
    padding:10px 14px!important;
    border:1px solid color-mix(in srgb,var(--hb-primary) 10%,var(--hb-line))!important;
    border-radius:16px!important;
    background:color-mix(in srgb,var(--hb-surface) 78%,var(--hb-ivory))!important;
    box-shadow:0 12px 26px -24px rgba(18,22,34,.34)!important;
  }
  .habitaciones-view .hb-search i{ color:color-mix(in srgb,var(--hb-primary) 48%,var(--hb-slate-400))!important; }
  .habitaciones-view .hb-search input{ font-size:16px!important; }
  .habitaciones-view .hb-fdiv{ display:none!important; }
  .habitaciones-view .hb-chips{
    display:flex!important;
    flex-wrap:nowrap!important;
    gap:7px!important;
    margin:0 -10px;
    padding:0 10px 2px;
    overflow-x:auto;
    scrollbar-width:none;
  }
  .habitaciones-view .hb-chips::-webkit-scrollbar{ width:0; height:0; }
  .habitaciones-view .hb-chip{
    flex:0 0 auto;
    padding:8px 13px!important;
    border-radius:999px!important;
    border-color:transparent!important;
    background:transparent!important;
    color:var(--hb-primary)!important;
    font-size:.72rem!important;
    font-weight:800!important;
  }
  .habitaciones-view .hb-chip.is-active{
    border-color:color-mix(in srgb,var(--chip-c) 45%,#fff)!important;
    background:color-mix(in srgb,var(--chip-c) 14%,#fff)!important;
    color:color-mix(in srgb,var(--chip-c) 76%,#1F2937)!important;
    box-shadow:0 10px 20px -16px color-mix(in srgb,var(--chip-c) 60%,transparent)!important;
  }
  .habitaciones-view .hb-filter-right{
    width:100%;
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 42px 42px;
    gap:7px!important;
  }
  .habitaciones-view .filter-date,
  .habitaciones-view .filter-btn{
    width:100%!important;
    min-height:42px!important;
    border-radius:14px!important;
    background:color-mix(in srgb,var(--hb-surface) 72%,var(--hb-ivory))!important;
    border-color:color-mix(in srgb,var(--hb-primary) 12%,var(--hb-line))!important;
  }
  .habitaciones-view .filter-btn span{ display:none!important; }

  .habitaciones-view .hb-movements{
    display:grid!important;
    gap:10px!important;
    margin:2px 0 15px!important;
  }
  .habitaciones-view .hb-move-card{
    border:1px solid var(--hb-line)!important;
    border-radius:18px!important;
    background:color-mix(in srgb,var(--hb-surface) 82%,var(--hb-ivory))!important;
    box-shadow:0 16px 34px -28px rgba(18,22,34,.38)!important;
    overflow:hidden;
  }
  .habitaciones-view .hb-move-head{
    padding:12px 13px!important;
    border:0!important;
    border-bottom:1px solid var(--hb-line)!important;
    border-radius:0!important;
    background:transparent!important;
  }
  .habitaciones-view .hb-move-head h3{
    color:var(--hb-primary)!important;
    font-size:.78rem!important;
    line-height:1.1!important;
  }
  .habitaciones-view .hb-move-head h3 > span:first-child{
    display:flex;
    align-items:center;
    gap:7px;
    min-width:0;
  }
  .habitaciones-view .hb-move-head h3 i{ margin:0!important; }
  .habitaciones-view .hb-move-head--in h3 i{ color:var(--c-arriving)!important; }
  .habitaciones-view .hb-move-head--out h3 i{ color:var(--c-maint)!important; }
  .habitaciones-view .hb-move-head h3 > span:last-child{
    min-width:24px;
    height:24px;
    display:inline-grid;
    place-items:center;
    padding:0!important;
    border-radius:999px!important;
    font-size:.7rem!important;
    font-weight:900!important;
  }
  .habitaciones-view .hb-move-head--in h3 > span:last-child{ background:color-mix(in srgb,var(--c-arriving) 70%,#fff)!important; }
  .habitaciones-view .hb-move-head--out h3 > span:last-child{ background:color-mix(in srgb,var(--c-maint) 70%,#fff)!important; }
  .habitaciones-view .hb-move-body{
    max-height:210px!important;
    padding:8px!important;
  }
  .habitaciones-view .hb-move-item{
    align-items:center!important;
    gap:10px!important;
    padding:10px!important;
    border:1px solid transparent!important;
    border-radius:14px!important;
    background:var(--hb-surface-warm)!important;
  }
  .habitaciones-view .hb-move-item--in{ border-color:color-mix(in srgb,var(--c-arriving) 18%,transparent)!important; }
  .habitaciones-view .hb-move-item--out{ border-color:color-mix(in srgb,var(--c-maint) 18%,transparent)!important; }
  .habitaciones-view .hb-move-item p:first-child{
    color:var(--hb-primary)!important;
    font-size:.78rem!important;
  }
  .habitaciones-view .hb-move-item p:last-child{
    color:var(--hb-slate-500)!important;
    font-size:.66rem!important;
    line-height:1.25!important;
  }
  .habitaciones-view .hb-move-action{
    width:30px;
    height:30px;
    display:grid!important;
    place-items:center;
    margin-left:4px!important;
    border-radius:999px;
    background:color-mix(in srgb,var(--c-arriving) 12%,var(--hb-surface))!important;
    color:var(--c-arriving)!important;
  }
  .habitaciones-view .hb-move-item--out button{
    min-height:30px!important;
    padding:6px 10px!important;
    border-radius:999px!important;
    background:var(--c-maint)!important;
    color:#fff!important;
    font-size:.64rem!important;
    font-weight:850!important;
    box-shadow:0 10px 18px -14px var(--c-maint)!important;
  }
  .habitaciones-view .hb-move-empty{
    margin:0!important;
    padding:22px 10px!important;
    color:var(--hb-slate-400)!important;
    font-size:.78rem!important;
  }

  .habitaciones-view #habitaciones-grid{ margin-top:2px; }
  .habitaciones-view .floor-section{ margin-bottom:8px!important; }
  .habitaciones-view .floor-label{ gap:10px!important; margin:14px 2px 9px!important; }
  .habitaciones-view .floor-t{
    color:var(--hb-slate-400)!important;
    font-family:var(--hb-sans,'DM Sans',system-ui,sans-serif)!important;
    font-size:.68rem!important;
    font-weight:900!important;
    letter-spacing:.08em!important;
    line-height:1!important;
    text-transform:uppercase;
  }
  .habitaciones-view .floor-rule{ background:var(--hb-line)!important; }
  .habitaciones-view .floor-ct{ font-size:.62rem!important; letter-spacing:.04em!important; }
  .habitaciones-view .rgrid{ grid-template-columns:repeat(2,minmax(0,1fr))!important; gap:10px!important; }
  .habitaciones-view .flip-card,
  .habitaciones-view .flip-card.flipped{
    height:148px!important;
    border-radius:15px!important;
    animation:none!important;
  }
  .habitaciones-view .room-card-compact:not(.flipped):hover{ transform:none!important; }
  .habitaciones-view .flip-card-inner,
  .habitaciones-view .flip-card-front{ border-radius:15px!important; }
  .habitaciones-view .flip-card-front{
    width:100%!important;
    height:100%!important;
    top:0!important;
    left:0!important;
    border:1px solid var(--hb-line)!important;
    border-left:1px solid var(--hb-line)!important;
    background:var(--hb-surface)!important;
    box-shadow:var(--hb-shadow-xs)!important;
  }
  .habitaciones-view .estado-disponible,
  .habitaciones-view .estado-disponible_fecha,
  .habitaciones-view .estado-ocupada,
  .habitaciones-view .estado-ocupada_fecha,
  .habitaciones-view .estado-por_llegar,
  .habitaciones-view .estado-doble,
  .habitaciones-view .estado-limpieza,
  .habitaciones-view .estado-limpieza-por-llegar,
  .habitaciones-view .estado-mantenimiento{
    border-left-width:1px!important;
    background:var(--hb-surface)!important;
  }
  .habitaciones-view .rc-face{ padding:11px 10px 10px 14px!important; }
  .habitaciones-view .rc-stripe{
    top:0!important;
    left:0!important;
    bottom:0!important;
    width:5px!important;
    height:auto!important;
    border-radius:15px 0 0 15px!important;
    box-shadow:none!important;
  }
  .habitaciones-view .rc-top{ gap:6px!important; }
  .habitaciones-view .rc-num{ font-size:1.62rem!important; font-weight:650!important; line-height:.9!important; }
  .habitaciones-view .rc-type{
    max-width:82px;
    margin-top:2px!important;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size:.58rem!important;
    letter-spacing:.02em!important;
  }
  .habitaciones-view .rc-badge{
    max-width:70px;
    min-height:21px;
    padding:4px 7px!important;
    border:1px solid color-mix(in srgb,var(--sheet-c,var(--hb-primary)) 24%,var(--hb-line))!important;
    border-radius:999px!important;
    background:var(--hb-surface-warm)!important;
    box-shadow:none!important;
    color:var(--sheet-c,var(--hb-primary))!important;
    font-size:0!important;
  }
  .habitaciones-view .rc-badge i{ display:none!important; }
  .habitaciones-view .rc-badge span{
    display:block;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    font-size:.55rem!important;
  }
  .habitaciones-view .rc-mid{ margin-top:8px!important; }
  .habitaciones-view .rc-guest{ gap:5px!important; font-size:.68rem!important; line-height:1.15!important; }
  .habitaciones-view .rc-guest i{ font-size:.58rem!important; }
  .habitaciones-view .rc-meta{ display:none!important; }
  .habitaciones-view .rc-foot{ margin-top:7px!important; gap:5px!important; }
  .habitaciones-view .rc-price{ font-size:.68rem!important; }
  .habitaciones-view .rc-price small{ display:none!important; }
  .habitaciones-view .rc-hint{
    width:22px;
    height:22px;
    border:1px solid var(--hb-line);
    border-radius:999px;
    justify-content:center;
    background:var(--hb-surface-warm);
    color:var(--hb-slate-400);
    font-size:0!important;
  }
  .habitaciones-view .rc-hint i{ font-size:.68rem!important; }
  .habitaciones-view .rc-hint span{ display:none!important; }
  .habitaciones-view .checkout-today-indicator,
  .habitaciones-view .checkout-vencido-indicator,
  .habitaciones-view .checkin-vencido-indicator,
  .habitaciones-view .late-arrival-indicator{
    top:6px!important;
    right:6px!important;
    min-height:18px!important;
    padding:3px 6px!important;
    font-size:.5rem!important;
  }

  .habitaciones-view .room-card-compact.flipped{
    height:148px!important;
    z-index:auto!important;
    grid-column:auto!important;
  }
  .habitaciones-view .room-card-compact.flipped .flip-card-front{
    opacity:1!important;
    pointer-events:auto!important;
  }
  .habitaciones-view .room-card-compact .flip-card-back{
    top:100%!important;
    pointer-events:none!important;
  }

  .habitaciones-view .hb-mobile-sheet-back{
    display:block;
    position:fixed;
    inset:0;
    z-index:10030;
    background:rgba(18,22,34,.46);
    opacity:0;
    visibility:hidden;
    transition:opacity .25s ease, visibility .25s ease;
  }
  .habitaciones-view .hb-mobile-sheet-back.is-open{ opacity:1; visibility:visible; }
  .habitaciones-view .hb-mobile-room-sheet{
    display:flex;
    position:fixed;
    left:0;
    right:0;
    bottom:0;
    z-index:10031;
    max-height:min(88dvh, calc(100dvh - env(safe-area-inset-top, 0px) - 8px));
    border-radius:24px 24px 0 0;
    background:var(--hb-surface);
    box-shadow:0 -12px 40px rgba(18,22,34,.2);
    transform:translateY(102%);
    transition:transform .36s cubic-bezier(.22,1,.36,1);
    flex-direction:column;
    overflow:hidden;
  }
  .habitaciones-view .hb-mobile-room-sheet.is-open{ transform:none; }
  .habitaciones-view .hb-mobile-sheet-grab{
    width:40px;
    height:5px;
    border-radius:999px;
    background:var(--hb-line);
    margin:10px auto 0;
    flex:none;
  }
  .habitaciones-view .hb-mobile-sheet-content{
    min-height:0;
    overflow-y:auto;
    -webkit-overflow-scrolling:touch;
    overscroll-behavior:contain;
    touch-action:pan-y;
    scroll-padding-bottom:calc(36px + env(safe-area-inset-bottom, 0px));
    scrollbar-width:none;
  }
  .habitaciones-view .hb-mobile-sheet-content::-webkit-scrollbar{ width:0; height:0; }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back{
    position:static!important;
    inset:auto!important;
    /* El reverso se oculta en la tarjeta con transform:translateY(100%+12px)
       (mecanismo de "panel que sube"). El clon dentro de la hoja NO debe heredar
       ese desplazamiento: sin este reset el contenido se va ~una pantalla abajo
       (fuera de vista) y la hoja se ve EN BLANCO en móvil. */
    transform:none!important;
    width:auto!important;
    min-height:0!important;
    max-height:none!important;
    display:flex!important;
    flex-direction:column!important;
    justify-content:flex-start!important;
    gap:0!important;
    padding:14px 20px calc(28px + env(safe-area-inset-bottom, 0px))!important;
    border-radius:0!important;
    overflow:visible!important;
    background:transparent!important;
    color:var(--hb-primary)!important;
    box-shadow:none!important;
    animation:none!important;
    pointer-events:auto!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back::before,
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back::after{ display:none!important; }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back h4{
    margin:0 0 12px!important;
    padding:0 0 14px!important;
    border-bottom:1px solid var(--hb-line-soft)!important;
    color:var(--hb-primary)!important;
    font-size:1.9rem!important;
    line-height:.95!important;
    letter-spacing:0!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .info-item{
    min-height:40px!important;
    margin:0 0 9px!important;
    padding:9px 10px!important;
    border:1px solid var(--hb-line-soft);
    border-radius:12px;
    background:var(--hb-surface-warm);
    color:var(--hb-primary)!important;
    font-size:.82rem!important;
    text-shadow:none!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .info-item i{
    width:26px;
    height:26px;
    display:inline-grid;
    place-items:center;
    border-radius:9px;
    background:var(--hb-ivory-2);
    color:var(--sheet-c,var(--hb-primary))!important;
    text-shadow:none!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .action-buttons{
    display:grid!important;
    grid-template-columns:1fr 1fr;
    gap:9px!important;
    margin-top:12px!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .btn-action{
    min-height:44px!important;
    padding:11px 12px!important;
    border:1px solid var(--hb-line)!important;
    border-radius:13px!important;
    background:var(--hb-surface-warm)!important;
    color:var(--hb-primary)!important;
    box-shadow:none!important;
    font-size:.78rem!important;
    font-weight:800!important;
    text-shadow:none!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .btn-action:active{ transform:scale(.97)!important; }
  .habitaciones-view .hb-mobile-sheet-content .btn-primary{
    grid-column:1 / -1;
    border-color:transparent!important;
    background:var(--sheet-c,var(--hb-primary))!important;
    color:#fff!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .btn-primary.hb-reserve-action{
    color:#EEF1EC!important;
    text-shadow:0 1px 0 rgba(15,23,42,.18)!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .btn-primary.hb-reserve-action i{
    color:inherit!important;
  }
  .habitaciones-view .hb-mobile-room-sheet.is-reserving{
    max-height:min(88dvh, calc(100dvh - env(safe-area-inset-top, 0px) - 8px));
  }
  .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-mobile-sheet-content{
    overflow-y:auto;
  }
  .habitaciones-view .hb-mobile-sheet-content .hb-reserve-shell{
    min-height:0!important;
    max-height:none!important;
    background:var(--hb-surface)!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .hb-reserve-main{
    min-height:0!important;
    max-height:none!important;
    overflow:visible!important;
    background:var(--hb-surface)!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .hb-reserve-mobile-intro{
    padding-top:26px!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .hb-reserve-footer{
    position:sticky;
    bottom:0;
    background:
      linear-gradient(180deg, rgba(250,251,249,0), var(--hb-surface) 22%),
      var(--hb-surface)!important;
  }

  #vistaRapidaModal,
  #modalLimpieza{
    align-items:flex-end!important;
    justify-content:center!important;
    padding:0!important;
  }
  #vistaRapidaModal > .bg-white,
  #modalLimpieza > .bg-white{
    position:relative;
    width:100%!important;
    max-width:none!important;
    max-height:92dvh!important;
    border-radius:24px 24px 0 0!important;
    animation:hbModalSheetIn .36s cubic-bezier(.22,1,.36,1)!important;
  }
  #vistaRapidaModal > .bg-white::before,
  #modalLimpieza > .bg-white::before{
    content:'';
    position:absolute;
    top:9px;
    left:50%;
    width:40px;
    height:5px;
    border-radius:999px;
    background:rgba(255,255,255,.38);
    transform:translateX(-50%);
    z-index:2;
  }
  #vistaRapidaModal > .bg-white > div:first-child,
  #modalLimpieza > .bg-white > div:first-child{
    padding-top:24px!important;
    border-radius:24px 24px 0 0!important;
  }
  #vistaRapidaModal h3,
  #modalLimpieza h3{
    font-size:1rem!important;
    line-height:1.1!important;
  }
  #vistaRapidaContainer{
    max-height:calc(92dvh - 72px)!important;
    padding:13px!important;
  }
  #vistaRapidaContainer .grid{
    grid-template-columns:repeat(4,minmax(0,1fr))!important;
    gap:8px!important;
  }
  #modalLimpieza .p-6.overflow-y-auto{
    max-height:calc(92dvh - 168px)!important;
    padding:14px!important;
  }
  #modalLimpieza .bg-gray-50{
    padding:12px 14px!important;
    gap:10px!important;
    background:var(--hb-surface-warm)!important;
  }
  #modalLimpieza .bg-gray-50 button{
    min-height:42px;
    border-radius:13px!important;
  }
  .swal2-popup{
    width:calc(100vw - 24px)!important;
    max-width:calc(100vw - 24px)!important;
    border-radius:18px!important;
    padding:1rem!important;
  }
  .swal2-html-container{
    max-height:70dvh;
    overflow-y:auto;
    margin:.75rem 0!important;
  }
  .swal2-container.hb-swal-sheet-container{
    align-items:flex-end!important;
    padding:0!important;
  }
  .swal2-container.hb-swal-sheet-container .hb-swal{
    width:100%!important;
    max-width:none!important;
    margin:0!important;
    border-radius:24px 24px 0 0!important;
    padding:24px 14px calc(16px + env(safe-area-inset-bottom))!important;
    animation:hbModalSheetIn .36s cubic-bezier(.22,1,.36,1)!important;
  }
  .swal2-container.hb-swal-sheet-container .hb-swal::before{
    content:'';
    position:absolute;
    top:9px;
    left:50%;
    width:40px;
    height:5px;
    border-radius:999px;
    background:color-mix(in srgb,var(--brand-primary,#1B2746) 18%,transparent);
    transform:translateX(-50%);
  }
  .swal2-container.hb-swal-sheet-container .swal2-title{
    padding:0 30px!important;
    font-size:1.08rem!important;
    line-height:1.16!important;
  }
  .swal2-container.hb-swal-sheet-container .hb-swal-html{
    max-height:min(70dvh,540px);
    overflow-y:auto!important;
    margin:.75rem 0 .45rem!important;
  }
  .hb-quick-client__copy{
    margin-bottom:12px;
    font-size:.86rem;
    text-align:left;
  }
  .hb-quick-client__grid{
    grid-template-columns:1fr;
    gap:10px;
  }
  .hb-client-card{
    min-height:72px;
    padding:13px;
    border-radius:16px;
    grid-template-columns:46px minmax(0,1fr) 22px;
    align-items:center;
    align-content:center;
    gap:11px;
  }
  .hb-client-card__icon{
    width:44px;
    height:44px;
  }
  .hb-client-card__body{
    min-width:0;
  }
  .hb-client-card__label{
    font-size:.98rem;
  }
  .hb-client-card__hint{
    font-size:.74rem;
  }
  .hb-client-card__arrow{
    position:static;
    justify-self:end;
  }
  .hb-client-choice{
    gap:0;
  }
  .hb-client-choice__head{
    grid-template-columns:44px minmax(0,1fr);
    gap:11px;
    padding:18px 16px 15px;
  }
  .hb-client-choice__mark{
    width:44px;
    height:44px;
    border-radius:13px;
  }
  .hb-client-choice h3{
    font-size:1.08rem;
  }
  .hb-client-choice__head p{
    margin-top:5px;
    font-size:.78rem;
    line-height:1.35;
  }
  .hb-client-choice__options{
    grid-template-columns:1fr;
    gap:10px;
    padding:13px;
  }
  .hb-client-option{
    min-height:0;
    padding:13px;
    border-radius:15px;
    gap:10px;
  }
  .hb-client-option__top{
    align-items:flex-start;
  }
  .hb-client-option__icon{
    width:38px;
    height:38px;
    border-radius:12px;
  }
  .hb-client-option__body strong{
    font-size:.94rem;
  }
  .hb-client-option__body span{
    font-size:.74rem;
  }
  .hb-client-option__cta{
    padding-top:9px;
  }
  .hb-reservation-pill{
    width:100%;
    justify-content:center;
    margin-bottom:10px;
  }
  .hb-reservation-summary{
    padding:12px;
    border-radius:16px;
  }
  .hb-reservation-dates{
    gap:0;
    margin-bottom:10px;
  }
  .hb-date-row{
    padding:10px 0;
  }
  .hb-date-row span{
    font-size:.75rem;
  }
  .hb-date-row strong{
    font-size:.86rem;
  }
  .hb-arrival-card{
    padding-top:12px;
  }
  .hb-arrival-copy{
    font-size:.75rem;
  }
  .hb-arrival-control{
    grid-template-columns:1fr;
    gap:8px;
  }
  .hb-arrival-input,
  .hb-arrival-now{
    height:50px;
    border-radius:13px;
  }
  .hb-arrival-now{
    width:100%;
  }
  .hb-swal .swal2-actions{
    width:100%!important;
    display:grid!important;
    grid-template-columns:1fr;
    gap:8px!important;
    margin-top:10px!important;
  }
  .hb-swal .swal2-styled{
    width:100%!important;
    margin:0!important;
    min-height:44px!important;
    white-space:normal!important;
  }
  @keyframes hbModalSheetIn{
    from{ transform:translateY(102%); }
    to{ transform:translateY(0); }
  }
}

/* Selector de huesped y hora de llegada: capa adaptable al branding de cada hotel. */
.swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
.swal2-container.hb-swal-sheet-container.swal2-noanimation{
  background:
    radial-gradient(circle at 50% 0%, color-mix(in srgb,var(--brand-primary,#1B2746) 20%, transparent), transparent 34%),
    color-mix(in srgb,var(--brand-primary,#1B2746) 40%, rgba(15,23,42,.78))!important;
  backdrop-filter:blur(8px);
}
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
  --hb-modal-primary:var(--brand-primary,#1B2746);
  --hb-modal-secondary:var(--brand-secondary,#0F172A);
  --hb-modal-ink:#111827;
  --hb-modal-muted:#64748B;
  --hb-modal-line:color-mix(in srgb,var(--hb-modal-primary) 14%,#E7DDCA);
  --hb-modal-soft:color-mix(in srgb,var(--hb-modal-primary) 7%,#FFFFFF);
  --hb-modal-softer:color-mix(in srgb,var(--hb-modal-primary) 4%,#FFFFFF);
  border:1px solid var(--hb-modal-line)!important;
  background:linear-gradient(180deg,#FFFFFF,color-mix(in srgb,var(--hb-modal-primary) 3%,#FAFAFC))!important;
  color:var(--hb-modal-ink)!important;
  box-shadow:0 34px 86px -30px color-mix(in srgb,var(--hb-modal-primary) 42%, rgba(12,18,32,.78))!important;
}
.swal2-popup.hb-swal-client{
  width:min(660px,calc(100vw - 28px))!important;
}
.swal2-popup.hb-swal-arrival{
  width:min(520px,calc(100vw - 28px))!important;
  padding:22px!important;
  border-radius:24px!important;
}
.hb-swal-arrival .swal2-title{
  color:var(--hb-modal-ink)!important;
  font-size:1.22rem!important;
  font-weight:900!important;
  line-height:1.12!important;
  padding:0!important;
}
.hb-swal-arrival .swal2-html-container{
  margin:14px 0 0!important;
}
.hb-client-choice{
  background:transparent!important;
}
.hb-client-choice__head{
  border-bottom:1px solid var(--hb-modal-line)!important;
  background:
    radial-gradient(circle at 88% 0%, color-mix(in srgb,var(--hb-modal-primary) 13%, transparent), transparent 34%),
    linear-gradient(180deg,#FFFFFF,var(--hb-modal-softer))!important;
}
.hb-client-choice__mark{
  color:#FFFFFF!important;
  background:linear-gradient(145deg,var(--hb-modal-primary),var(--hb-modal-secondary))!important;
  box-shadow:0 16px 30px -18px color-mix(in srgb,var(--hb-modal-primary) 72%, transparent)!important;
}
.hb-client-choice__eyebrow{
  color:color-mix(in srgb,var(--hb-modal-primary) 78%, var(--hb-modal-ink))!important;
}
.hb-client-choice h3,
.hb-client-option__body strong,
.hb-date-row strong,
.hb-arrival-label{
  color:var(--hb-modal-ink)!important;
}
.hb-client-choice__head p,
.hb-client-option__body span,
.hb-arrival-copy,
.hb-date-row span{
  color:var(--hb-modal-muted)!important;
}
.hb-client-option{
  border-color:var(--hb-modal-line)!important;
  background:#FFFFFF!important;
  color:var(--hb-modal-ink)!important;
  box-shadow:0 1px 2px rgba(17,24,39,.04), 0 18px 38px -32px color-mix(in srgb,var(--hb-modal-primary) 45%, transparent)!important;
}
.hb-client-option:hover,
.hb-client-option:focus-visible{
  border-color:color-mix(in srgb,var(--hb-modal-primary) 36%,#D9CDBA)!important;
  background:var(--hb-modal-soft)!important;
  box-shadow:0 20px 42px -30px color-mix(in srgb,var(--hb-modal-primary) 58%, transparent)!important;
}
.hb-client-option__icon,
.hb-client-option--existing .hb-client-option__icon{
  color:var(--hb-modal-primary)!important;
  background:var(--hb-modal-soft)!important;
  box-shadow:inset 0 0 0 1px color-mix(in srgb,var(--hb-modal-primary) 18%, transparent)!important;
}
.hb-client-option__tag{
  color:color-mix(in srgb,var(--hb-modal-primary) 78%, var(--hb-modal-ink))!important;
  background:var(--hb-modal-soft)!important;
}
.hb-client-option__cta{
  border-top:1px solid color-mix(in srgb,var(--hb-modal-primary) 12%,#ECE5D8)!important;
  color:var(--hb-modal-primary)!important;
}
.hb-reservation-pill,
.hb-reservation-pill--existing{
  color:color-mix(in srgb,var(--brand-primary,#1B2746) 82%,#111827)!important;
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 8%,#FFFFFF)!important;
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 14%,#E7DDCA)!important;
}
.hb-reservation-summary{
  border-color:color-mix(in srgb,var(--brand-primary,#1B2746) 12%,#E7DDCA)!important;
  background:linear-gradient(180deg,#FFFFFF,color-mix(in srgb,var(--brand-primary,#1B2746) 4%,#FFFFFF))!important;
}
.hb-date-row{
  border-bottom-color:color-mix(in srgb,var(--brand-primary,#1B2746) 9%,transparent)!important;
}
.hb-arrival-card{
  border-top-color:color-mix(in srgb,var(--brand-primary,#1B2746) 12%,#E7DDCA)!important;
}
.hb-arrival-input{
  border-color:color-mix(in srgb,var(--brand-primary,#1B2746) 16%,#E7DDCA)!important;
  color:#111827!important;
}
.hb-arrival-input:focus{
  border-color:var(--brand-primary,#1B2746)!important;
  box-shadow:0 0 0 4px color-mix(in srgb,var(--brand-primary,#1B2746) 14%,transparent)!important;
}
.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm{
  border:0!important;
  background:linear-gradient(135deg,var(--brand-primary,#1B2746),var(--brand-secondary,#0F172A))!important;
  color:#FFFFFF!important;
  box-shadow:0 16px 30px -18px color-mix(in srgb,var(--brand-primary,#1B2746) 70%, transparent)!important;
}
.hb-swal-arrival .hb-swal-confirm:hover,
.hb-arrival-now:hover{
  filter:brightness(1.05);
}
.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
  border:1px solid color-mix(in srgb,var(--brand-primary,#1B2746) 13%,#D7DEE8)!important;
  background:#FFFFFF!important;
  color:#111827!important;
  box-shadow:none!important;
}
.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:hover{
  background:color-mix(in srgb,var(--brand-primary,#1B2746) 5%,#FFFFFF)!important;
}
@media (max-width:640px){
  .swal2-popup.hb-swal-client,
  .swal2-popup.hb-swal-arrival{
    width:100%!important;
    max-width:none!important;
    border-radius:24px 24px 0 0!important;
  }
  .swal2-popup.hb-swal-arrival{
    padding:24px 14px calc(16px + env(safe-area-inset-bottom))!important;
  }
  .hb-client-choice__head{
    padding-top:22px!important;
  }
}

@media (max-width:380px){
  .habitaciones-view .modern-header .flex.flex-wrap.gap-2{ grid-template-columns:repeat(2,minmax(0,1fr)); }
  .habitaciones-view .rgrid{ gap:8px!important; }
  .habitaciones-view .flip-card,
  .habitaciones-view .flip-card.flipped{ height:144px!important; }
  .habitaciones-view .room-card-compact.flipped{ height:144px!important; }
  .habitaciones-view .rc-num{ font-size:1.48rem!important; }
  .habitaciones-view .rc-badge{ max-width:62px; }
}

/* Affordance layer: senales claras sin cambiar la estructura visual. */
.habitaciones-view .btn-modern,
.habitaciones-view .filter-btn,
.habitaciones-view .hb-chip,
.habitaciones-view .hb-mobile-lg,
.habitaciones-view .room-card-compact,
.habitaciones-view .room-quick-view,
.habitaciones-view .flip-card-back .btn-action,
.habitaciones-view .hb-stat[role="button"]{
  -webkit-tap-highlight-color:transparent;
}
.habitaciones-view .btn-modern:focus-visible,
.habitaciones-view .filter-btn:focus-visible,
.habitaciones-view .hb-chip:focus-visible,
.habitaciones-view .hb-mobile-lg:focus-visible,
.habitaciones-view .room-card-compact:focus-visible,
.habitaciones-view .room-quick-view:focus-visible,
.habitaciones-view .flip-card-back .btn-action:focus-visible,
.habitaciones-view .hb-stat[role="button"]:focus-visible{
  outline:2px solid color-mix(in srgb,var(--hb-accent) 72%,#fff);
  outline-offset:3px;
}
.habitaciones-view .hb-stat[role="button"]{
  cursor:pointer;
  transition:transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.habitaciones-view .hb-stat[role="button"]:hover,
.habitaciones-view .hb-stat[role="button"]:focus-visible{
  transform:translateY(-2px);
  border-color:color-mix(in srgb,var(--sc) 44%,var(--hb-line));
  box-shadow:var(--hb-shadow);
}
.habitaciones-view .hb-stat[role="button"]:active,
.habitaciones-view .hb-chip:active,
.habitaciones-view .hb-mobile-lg:active,
.habitaciones-view .room-card-compact:active,
.habitaciones-view .room-quick-view:active{
  transform:translateY(0) scale(.99)!important;
}
.habitaciones-view .hb-stat-go{
  position:absolute;
  top:12px;
  right:12px;
  width:24px;
  height:24px;
  display:grid;
  place-items:center;
  border:1px solid color-mix(in srgb,var(--sc) 20%,var(--hb-line));
  border-radius:999px;
  color:var(--sc);
  background:color-mix(in srgb,var(--sc) 6%,#fff);
  font-size:.62rem;
  opacity:.58;
  transition:opacity .18s ease, transform .18s ease, background .18s ease;
}
.habitaciones-view .hb-stat[role="button"]:hover .hb-stat-go,
.habitaciones-view .hb-stat[role="button"]:focus-visible .hb-stat-go{
  opacity:1;
  transform:translateX(2px);
  background:#fff;
}
.habitaciones-view .hb-chip:hover .hb-chip-ct,
.habitaciones-view .hb-chip:focus-visible .hb-chip-ct,
.habitaciones-view .hb-mobile-lg:hover b,
.habitaciones-view .hb-mobile-lg:focus-visible b{
  color:var(--hb-primary);
}
.habitaciones-view .room-card-compact:not(.flipped):hover .rc-num,
.habitaciones-view .room-card-compact:not(.flipped):focus-visible .rc-num{
  color:color-mix(in srgb,var(--room-accent-color) 54%,var(--hb-primary))!important;
}
.habitaciones-view .room-card-compact:not(.flipped):hover .rc-hint,
.habitaciones-view .room-card-compact:not(.flipped):focus-visible .rc-hint{
  color:var(--room-accent-color);
}
.habitaciones-view .room-card-compact:not(.flipped):hover .rc-hint i,
.habitaciones-view .room-card-compact:not(.flipped):focus-visible .rc-hint i{
  transform:translateY(-1px);
}
.habitaciones-view .flip-card-back .btn-action:active,
.habitaciones-view .btn-modern:active,
.habitaciones-view .filter-btn:active{
  transform:translateY(0) scale(.99)!important;
}
.habitaciones-view .flip-card-back .btn-action i,
.habitaciones-view .btn-modern i{
  transition:transform .18s ease;
}
.habitaciones-view .flip-card-back .btn-action:hover i,
.habitaciones-view .flip-card-back .btn-action:focus-visible i{
  transform:translateX(1px);
}
#vistaRapidaContainer .room-quick-view{
  position:relative;
}
#vistaRapidaContainer .room-quick-view::after{
  content:"";
  position:absolute;
  inset:auto 8px 7px auto;
  width:5px;
  height:5px;
  border-right:2px solid rgba(255,255,255,.85);
  border-bottom:2px solid rgba(255,255,255,.85);
  transform:rotate(-45deg);
  opacity:.66;
  transition:opacity .18s ease, transform .18s ease;
}
#vistaRapidaContainer .room-quick-view:hover::after,
#vistaRapidaContainer .room-quick-view:focus-visible::after{
  opacity:1;
  transform:translateX(2px) rotate(-45deg);
}
/* Estado "No llego": alerta clara sin borde griton ni datos encimados. */
.habitaciones-view .room-card-compact.has-checkin-vencido{
  --hb-late:#C9322B;
  --hb-late-dark:#8F1F1B;
  --hb-late-soft:#FFF0EC;
  --hb-late-line:#F2B8AE;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .flip-card-front{
  border:1px solid color-mix(in srgb,var(--hb-late) 38%,var(--hb-line))!important;
  border-left:0!important;
  background:
    radial-gradient(circle at 96% 8%, color-mix(in srgb,var(--hb-late) 18%,transparent), transparent 4.8rem),
    linear-gradient(135deg,#FFFDFC 0%,var(--hb-late-soft) 55%,color-mix(in srgb,var(--hb-accent) 8%,#FFF7F2) 100%)!important;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.74),
    0 16px 32px -26px color-mix(in srgb,var(--hb-late) 72%,#111827)!important;
  animation:none!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .flip-card-front::before{
  content:"";
  display:block!important;
  position:absolute;
  inset:0 auto 0 0;
  width:6px;
  height:auto;
  border-radius:var(--hb-radius) 0 0 var(--hb-radius);
  background:linear-gradient(180deg,var(--hb-late),var(--hb-late-dark));
  opacity:1;
  pointer-events:none;
  z-index:2;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-face{
  padding-top:38px!important;
  padding-left:21px!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-stripe{
  display:none!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .checkin-vencido-indicator{
  top:11px!important;
  left:16px!important;
  right:auto!important;
  min-height:23px!important;
  padding:4px 9px 4px 8px!important;
  border:1px solid color-mix(in srgb,var(--hb-late) 22%,#fff)!important;
  border-radius:999px!important;
  background:linear-gradient(135deg,var(--hb-late),var(--hb-late-dark))!important;
  color:#fff!important;
  font-size:.6rem!important;
  font-weight:900!important;
  letter-spacing:.04em!important;
  line-height:1!important;
  box-shadow:0 9px 18px -13px color-mix(in srgb,var(--hb-late) 80%,#111827)!important;
  text-shadow:none!important;
  animation:none!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .checkin-vencido-indicator i{
  animation:none!important;
  font-size:.62rem!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-num{
  color:color-mix(in srgb,var(--hb-late-dark) 58%,var(--hb-primary))!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-badge{
  border:1px solid color-mix(in srgb,var(--c-available) 20%,#fff)!important;
  background:#fff!important;
  color:#157A52!important;
  box-shadow:0 8px 18px -16px rgba(21,122,82,.55)!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-guest{
  width:100%;
  padding:7px 9px;
  border:1px solid color-mix(in srgb,var(--hb-late) 18%,#fff);
  border-radius:12px;
  background:rgba(255,255,255,.64);
  color:color-mix(in srgb,var(--hb-late-dark) 58%,var(--hb-primary))!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.78);
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-guest i{
  color:var(--hb-late)!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-meta{
  display:block!important;
  margin-top:5px!important;
  color:color-mix(in srgb,var(--hb-late-dark) 58%,var(--hb-slate-500))!important;
  font-weight:750!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-price{
  color:color-mix(in srgb,var(--hb-late-dark) 48%,var(--hb-primary))!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido .rc-hint{
  color:color-mix(in srgb,var(--hb-late) 70%,var(--hb-slate-400))!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):hover .flip-card-front,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):focus-visible .flip-card-front{
  border-color:color-mix(in srgb,var(--hb-late) 52%,var(--hb-line))!important;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.78),
    0 20px 42px -28px color-mix(in srgb,var(--hb-late) 78%,#111827)!important;
}

@media (max-width:640px){
  .habitaciones-view .room-card-compact.has-checkin-vencido,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    height:166px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .flip-card-front{
    background:
      radial-gradient(circle at 100% 0%, color-mix(in srgb,var(--hb-late) 13%,transparent), transparent 4.2rem),
      linear-gradient(135deg,#FFFDFC 0%,var(--hb-late-soft) 100%)!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .flip-card-front::before{
    border-radius:15px 0 0 15px;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-face{
    padding:33px 9px 10px 15px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .checkin-vencido-indicator{
    top:8px!important;
    left:11px!important;
    right:auto!important;
    min-height:20px!important;
    padding:4px 7px!important;
    font-size:.5rem!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-num{
    font-size:1.46rem!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-type{
    max-width:92px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-badge{
    max-width:75px!important;
    min-height:20px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-guest{
    padding:5px 7px!important;
    border-radius:10px!important;
    font-size:.64rem!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-meta{
    display:block!important;
    max-width:100%;
    margin-top:4px!important;
    font-size:.56rem!important;
    line-height:1.15!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-foot{
    margin-top:6px!important;
  }
}

@media (max-width:380px){
  .habitaciones-view .room-card-compact.has-checkin-vencido,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    height:158px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-face{
    padding-top:30px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido .rc-meta{
    display:none!important;
  }
}

/* Hoja de acciones del estado "No llego": completa y legible al abrir. */
.habitaciones-view .flip-card-back > div:first-child{
  min-width:0;
}
.habitaciones-view .flip-card-back .info-item span,
.habitaciones-view .flip-card-back .btn-action{
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
}
.habitaciones-view .flip-card-back .info-item span{
  white-space:nowrap;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
  height:238px!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-front{
  opacity:1!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back{
  top:3px!important;
  bottom:3px!important;
  padding:13px!important;
  border:1px solid color-mix(in srgb,var(--hb-late) 28%,rgba(255,255,255,.28))!important;
  border-radius:var(--hb-radius)!important;
  background:
    radial-gradient(circle at 98% 4%, rgba(255,255,255,.18), transparent 4.8rem),
    linear-gradient(155deg,var(--hb-late) 0%,var(--hb-late-dark) 100%)!important;
  box-shadow:
    inset 0 1px 0 rgba(255,255,255,.18),
    0 18px 36px -28px color-mix(in srgb,var(--hb-late-dark) 84%,#000)!important;
  display:flex!important;
  flex-direction:column!important;
  justify-content:flex-start!important;
  gap:0!important;
  overflow:hidden!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > div:first-child{
  min-height:0;
  overflow-y:auto;
  padding-right:2px;
  scrollbar-width:none;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > div:first-child::-webkit-scrollbar{
  width:0;
  height:0;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back h4{
  margin:0 0 8px!important;
  padding:0 0 8px!important;
  border-bottom:1px solid rgba(255,255,255,.22)!important;
  color:#fff!important;
  font-size:1.08rem!important;
  line-height:1!important;
  white-space:nowrap!important;
  overflow:hidden!important;
  text-overflow:ellipsis!important;
  text-shadow:none!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item{
  min-height:27px!important;
  margin:0 0 5px!important;
  padding:6px 8px!important;
  border:1px solid rgba(255,255,255,.14);
  border-radius:10px;
  background:rgba(255,255,255,.12);
  color:rgba(255,255,255,.94)!important;
  display:flex!important;
  align-items:center!important;
  gap:7px!important;
  font-size:.68rem!important;
  font-weight:750!important;
  line-height:1.15!important;
  text-shadow:none!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item i{
  width:19px!important;
  height:19px;
  display:inline-grid;
  place-items:center;
  border-radius:7px;
  background:rgba(255,255,255,.15);
  color:#fff!important;
  flex:none;
  font-size:.62rem!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item span{
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .action-buttons{
  display:grid!important;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:7px!important;
  margin-top:8px!important;
  padding-top:8px!important;
  border-top:1px solid rgba(255,255,255,.18);
  flex:none;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .btn-action{
  min-width:0;
  min-height:34px!important;
  padding:8px 9px!important;
  border-radius:11px!important;
  background:rgba(255,255,255,.14)!important;
  color:#fff!important;
  font-size:.66rem!important;
  font-weight:850!important;
  line-height:1!important;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .btn-primary{
  background:#fff!important;
  color:var(--hb-late-dark)!important;
}

@media (max-width:640px){
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido{
    --hb-late:#C9322B;
    --hb-late-dark:#8F1F1B;
    --hb-late-soft:#FFF0EC;
    color:var(--hb-primary)!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido h4{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:10px!important;
    font-size:1.72rem!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido h4::after{
    content:"No llego";
    flex:none;
    padding:7px 9px;
    border-radius:999px;
    background:linear-gradient(135deg,var(--hb-late),var(--hb-late-dark));
    color:#fff;
    font-family:var(--hb-sans,'DM Sans',system-ui,sans-serif);
    font-size:.62rem;
    font-weight:900;
    letter-spacing:.05em;
    line-height:1;
    text-transform:uppercase;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido .info-item{
    border-color:color-mix(in srgb,var(--hb-late) 14%,var(--hb-line-soft))!important;
    background:color-mix(in srgb,var(--hb-late-soft) 42%,var(--hb-surface-warm))!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido .info-item span{
    min-width:0;
    overflow-wrap:anywhere;
    white-space:normal;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido .info-item i{
    color:var(--hb-late)!important;
  }
  .habitaciones-view .hb-mobile-sheet-content .flip-card-back.back-checkin-vencido .btn-primary{
    background:linear-gradient(135deg,var(--hb-late),var(--hb-late-dark))!important;
  }
}

/* Color balance: hotel color as accent, operational data in neutral ink. */
.habitaciones-view{
  --hb-heading:#111827;
  --hb-body:#1F2937;
  --hb-muted:#667085;
  --hb-line:#E7E1D4;
  --hb-line-soft:#F3EEE4;
  --bg-available:#F0F8F3;
  --bg-occupied:#F8EAE1;
  --bg-arriving:#F2EAFD;
  --bg-cleaning:#EFF6FC;
  --bg-maint:#FBF3E3;
  --c-occupied:#C2603C;
  --c-occupied-dark:#9E4A2E;
}

.habitaciones-view .modern-header h1,
.habitaciones-view .hb-stat-n,
.habitaciones-view .rc-num,
.habitaciones-view .rc-price,
.habitaciones-view .rc-guest,
.habitaciones-view .hb-mobile-occupancy-head strong,
.habitaciones-view .hb-mobile-lg b{
  color:var(--hb-heading)!important;
}

.habitaciones-view .modern-header .p-2.rounded-lg{
  background:linear-gradient(150deg,color-mix(in srgb,var(--hb-primary) 82%,#111827),var(--hb-secondary))!important;
  box-shadow:0 10px 24px -18px color-mix(in srgb,var(--hb-primary) 72%,transparent)!important;
}

.habitaciones-view .btn-brand{
  background:linear-gradient(135deg,var(--hb-primary),var(--hb-secondary))!important;
  box-shadow:0 10px 22px -14px color-mix(in srgb,var(--hb-primary) 62%,transparent)!important;
}

.habitaciones-view .btn-brand-outline{
  color:var(--hb-heading)!important;
  border-color:color-mix(in srgb,var(--hb-primary) 14%,var(--hb-line))!important;
}

.habitaciones-view .btn-brand-outline:hover,
.habitaciones-view .btn-brand-soft:hover,
.habitaciones-view .filter-btn-today:hover{
  color:var(--hb-heading)!important;
  border-color:color-mix(in srgb,var(--hb-accent) 38%,var(--hb-line))!important;
  background:color-mix(in srgb,var(--hb-accent) 8%,#fff)!important;
}

.habitaciones-view .btn-brand-soft,
.habitaciones-view .filter-btn-today{
  color:var(--hb-heading)!important;
  background:color-mix(in srgb,var(--hb-primary) 5%,#fff)!important;
  border-color:color-mix(in srgb,var(--hb-primary) 12%,var(--hb-line))!important;
}

.habitaciones-view .filter-btn-primary{
  background:linear-gradient(135deg,var(--hb-primary),var(--hb-secondary))!important;
  box-shadow:0 8px 18px -12px color-mix(in srgb,var(--hb-primary) 58%,transparent)!important;
}

.habitaciones-view .hb-stat{
  border-color:color-mix(in srgb,var(--sc) 9%,var(--hb-line))!important;
}

.habitaciones-view .hb-stat-ic{
  background:color-mix(in srgb,var(--sc) 10%,#fff)!important;
  color:color-mix(in srgb,var(--sc) 82%,var(--hb-heading))!important;
}

.habitaciones-view .hb-stat.is-filter-active{
  background:linear-gradient(180deg,#fff,color-mix(in srgb,var(--sc) 5%,#fff))!important;
  border-color:color-mix(in srgb,var(--sc) 26%,var(--hb-line))!important;
}

/* Widgets de estado: conserva el diseno original; acento solo abajo al filtrar. */
.habitaciones-view #hbStats .hb-stat::before{
  display:none!important;
  opacity:0!important;
}

.habitaciones-view #hbStats .hb-stat::after{
  left:16px!important;
  right:16px!important;
  bottom:0!important;
  height:3px!important;
  border-radius:999px 999px 0 0!important;
  background:
    radial-gradient(circle at 62% 50%, rgba(255,255,255,.86) 0 1px, transparent 2px),
    linear-gradient(90deg,
      color-mix(in srgb,var(--sc) 18%,transparent) 0%,
      color-mix(in srgb,var(--sc) 54%,#fff) 18%,
      var(--sc) 46%,
      color-mix(in srgb,var(--sc) 76%,#fff) 62%,
      color-mix(in srgb,var(--sc) 22%,transparent) 100%)!important;
  box-shadow:0 -1px 0 rgba(255,255,255,.74) inset,0 8px 16px -13px color-mix(in srgb,var(--sc) 68%,transparent)!important;
  opacity:0!important;
}

.habitaciones-view #hbStats .hb-stat[role="button"]:hover::after,
.habitaciones-view #hbStats .hb-stat[role="button"]:focus-visible::after{
  opacity:0!important;
}

.habitaciones-view #hbStats .hb-stat.is-filter-active::after{
  left:13px!important;
  right:13px!important;
  height:4px!important;
  opacity:1!important;
}

.habitaciones-view .hb-chip.is-active,
.habitaciones-view .hb-mobile-lg.is-active{
  background:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 14%, #fff)!important;
  border-color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 42%, #fff)!important;
  color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 74%, #1F2937)!important;
}

.habitaciones-view .hb-mobile-lg.is-active b{
  color:inherit!important;
}

.habitaciones-view .flip-card-front{
  color:var(--hb-body)!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.75),0 10px 24px -22px rgba(17,24,39,.35)!important;
}

.habitaciones-view .estado-disponible,
.habitaciones-view .estado-disponible_fecha{
  background:linear-gradient(145deg,#F7FCF8,var(--bg-available))!important;
}

.habitaciones-view .estado-ocupada,
.habitaciones-view .estado-ocupada_fecha{
  background:linear-gradient(145deg,#FFF7F3,var(--bg-occupied))!important;
}

.habitaciones-view .estado-por_llegar,
.habitaciones-view .estado-doble{
  background:linear-gradient(145deg,#FBFAFF,var(--bg-arriving))!important;
}

.habitaciones-view .estado-limpieza,
.habitaciones-view .estado-limpieza-por-llegar{
  background:linear-gradient(145deg,#FAFDFF,var(--bg-cleaning))!important;
}

.habitaciones-view .estado-mantenimiento{
  background:linear-gradient(145deg,#FFFDF8,var(--bg-maint))!important;
}

.habitaciones-view .rc-badge{
  box-shadow:none!important;
}

.habitaciones-view .rc-guest i,
.habitaciones-view .rc-hint,
.habitaciones-view .rc-meta,
.habitaciones-view .rc-type{
  color:var(--hb-muted)!important;
}

.habitaciones-view .flip-card-back{
  background:
    radial-gradient(circle at 18% 12%,rgba(255,255,255,.16),transparent 34%),
    linear-gradient(160deg,color-mix(in srgb,var(--sheet-c,var(--hb-primary)) 76%,#172033),color-mix(in srgb,var(--sheet-c,var(--hb-primary)) 46%,#111827))!important;
}

.habitaciones-view .flip-card-back .btn-primary{
  color:var(--hb-heading)!important;
}

.habitaciones-view .room-card-compact:not(.flipped):hover .rc-num,
.habitaciones-view .room-card-compact:not(.flipped):focus-visible .rc-num{
  color:var(--hb-heading)!important;
}

.habitaciones-view .room-card-compact:not(.flipped):hover .rc-hint,
.habitaciones-view .room-card-compact:not(.flipped):focus-visible .rc-hint{
  color:color-mix(in srgb,var(--hb-accent) 74%,var(--hb-heading))!important;
}

/* Estado operativo primero; incidencias como chips secundarios. */
.habitaciones-view .rc-incidents{
  display:flex;
  flex-wrap:wrap;
  gap:5px;
  margin-top:6px;
  min-width:0;
}

.habitaciones-view .rc-incident{
  display:inline-flex;
  align-items:center;
  gap:4px;
  max-width:100%;
  min-height:21px;
  padding:3px 7px;
  border:1px solid color-mix(in srgb,var(--hb-line) 78%,#fff);
  border-radius:999px;
  background:rgba(255,255,255,.72);
  color:var(--hb-heading);
  font-size:.57rem;
  font-weight:850;
  line-height:1;
  letter-spacing:.02em;
  text-transform:uppercase;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.76);
  white-space:nowrap;
  overflow:hidden;
}

.habitaciones-view .rc-incident i{
  flex:none;
  font-size:.56rem;
}

.habitaciones-view .rc-incident span{
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
}

.habitaciones-view .rc-incident small{
  flex:none;
  font-size:.53rem;
  font-weight:800;
  opacity:.78;
  text-transform:none;
}

.habitaciones-view .rc-incident--critical{
  border-color:color-mix(in srgb,#D64539 36%,#fff);
  background:color-mix(in srgb,#D64539 10%,#fff);
  color:#A72822;
}

.habitaciones-view .rc-incident--warning{
  border-color:color-mix(in srgb,var(--c-maint) 34%,#fff);
  background:color-mix(in srgb,var(--c-maint) 11%,#fff);
  color:color-mix(in srgb,var(--c-maint) 82%,#5B3B05);
}

.habitaciones-view .rc-incident--info{
  border-color:color-mix(in srgb,var(--c-arriving) 26%,#fff);
  background:color-mix(in srgb,var(--c-arriving) 9%,#fff);
  color:color-mix(in srgb,var(--c-arriving) 78%,var(--hb-heading));
}

.habitaciones-view .rc-incident--reservation-pending{
  position:relative;
  isolation:isolate;
  border-color:color-mix(in srgb,var(--c-arriving) 42%,#fff);
  background:linear-gradient(135deg,
    color-mix(in srgb,var(--c-arriving) 14%,#fff) 0%,
    rgba(255,255,255,.86) 100%);
  color:color-mix(in srgb,var(--c-arriving) 88%,var(--hb-heading));
  box-shadow:inset 0 1px 0 rgba(255,255,255,.82),0 0 0 0 color-mix(in srgb,var(--c-arriving) 22%,transparent);
  animation:hbReservationPendingPulse 2s ease-in-out infinite;
}

.habitaciones-view .rc-incident--reservation-pending::after{
  content:"";
  position:absolute;
  inset:-55% -80%;
  z-index:0;
  background:linear-gradient(100deg,transparent 38%,rgba(255,255,255,.82) 50%,transparent 62%);
  transform:translateX(-65%) rotate(7deg);
  opacity:0;
  animation:hbReservationPendingSweep 3.2s ease-in-out infinite;
}

.habitaciones-view .rc-incident--reservation-pending i,
.habitaciones-view .rc-incident--reservation-pending span,
.habitaciones-view .rc-incident--reservation-pending small{
  position:relative;
  z-index:1;
}

.habitaciones-view .rc-incident--reservation-pending i{
  animation:hbReservationPendingIcon 2s ease-in-out infinite;
}

@keyframes hbReservationPendingPulse{
  0%,100%{
    transform:translateY(0) scale(1);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.82),0 0 0 0 color-mix(in srgb,var(--c-arriving) 0%,transparent);
  }
  50%{
    transform:translateY(-1px) scale(1.015);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.88),0 0 0 4px color-mix(in srgb,var(--c-arriving) 18%,transparent);
  }
}

@keyframes hbReservationPendingSweep{
  0%,38%{ opacity:0; transform:translateX(-70%) rotate(7deg); }
  48%{ opacity:.75; }
  66%,100%{ opacity:0; transform:translateX(70%) rotate(7deg); }
}

@keyframes hbReservationPendingIcon{
  0%,100%{ transform:scale(1); }
  50%{ transform:scale(1.12); }
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front{
  animation:none!important;
  border:1px solid var(--hb-line)!important;
  border-left-width:4px!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.75),0 10px 24px -22px rgba(17,24,39,.35)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front::before{
  display:none!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-face{
  padding:13px 15px 13px 18px!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-stripe{
  display:block!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-num,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-price{
  color:var(--hb-primary)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-guest{
  width:auto!important;
  padding:0!important;
  border:0!important;
  border-radius:0!important;
  background:transparent!important;
  box-shadow:none!important;
  color:var(--hb-primary)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-guest i,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-meta,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-hint{
  color:var(--hb-muted)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-meta{
  margin-top:2px!important;
  font-weight:600!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-badge{
  border:0!important;
  color:#fff!important;
  box-shadow:none!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-disponible,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-disponible_fecha,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-disponible,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-disponible_fecha{
  background:linear-gradient(145deg,#F7FCF8,var(--bg-available))!important;
  border-left-color:var(--c-available)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-por_llegar,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-doble,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-por_llegar,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-doble{
  background:linear-gradient(145deg,#FBFAFF,var(--bg-arriving))!important;
  border-left-color:var(--c-arriving)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-ocupada,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-ocupada_fecha,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-ocupada,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-ocupada_fecha{
  background:linear-gradient(145deg,#FFF7F3,var(--bg-occupied))!important;
  border-left-color:var(--c-occupied)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-limpieza,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-limpieza{
  background:linear-gradient(145deg,#FAFDFF,var(--bg-cleaning))!important;
  border-left-color:var(--c-cleaning)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-mantenimiento,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped) .flip-card-front.estado-mantenimiento{
  background:linear-gradient(145deg,#FFFDF8,var(--bg-maint))!important;
  border-left-color:var(--c-maint)!important;
}

/* "Sin check-in" + habitación DISPONIBLE: el badge verde se perdía sobre la
   superficie verde de la tarjeta (mismo tono + sin sombra). Le damos una
   píldora nítida —fondo claro, texto verde profundo, aro y sombra— para que
   la leyenda "Disponible" resalte sin competir con el chip rojo "Sin check-in". */
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-disponible .rc-badge,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-disponible_fecha .rc-badge{
  background:#fff!important;
  color:#12784A!important;
  border:1px solid color-mix(in srgb,var(--c-available) 42%,#fff)!important;
  box-shadow:0 3px 8px -3px color-mix(in srgb,var(--c-available) 55%,transparent), inset 0 1px 0 rgba(255,255,255,.9)!important;
}
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-por_llegar .rc-badge,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-doble .rc-badge{ background:var(--c-arriving)!important; }
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-ocupada .rc-badge,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-ocupada_fecha .rc-badge{ background:var(--c-occupied)!important; }
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-limpieza .rc-badge{ background:var(--c-cleaning)!important; }
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .flip-card-front.estado-mantenimiento .rc-badge{ background:var(--c-maint)!important; }

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):hover .flip-card-front,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):focus-visible .flip-card-front,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):hover .flip-card-front,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):focus-visible .flip-card-front{
  border-color:var(--hb-line)!important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.75),0 12px 28px -24px rgba(17,24,39,.38)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-disponible,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-disponible_fecha,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-disponible,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-disponible_fecha{
  border-left-color:var(--c-available)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-por_llegar,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-doble,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-por_llegar,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-doble{
  border-left-color:var(--c-arriving)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-ocupada,
.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-ocupada_fecha,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-ocupada,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-ocupada_fecha{
  border-left-color:var(--c-occupied)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-limpieza,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-limpieza{
  border-left-color:var(--c-cleaning)!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-mantenimiento,
.habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped):is(:hover,:focus-visible) .flip-card-front.estado-mantenimiento{
  border-left-color:var(--c-maint)!important;
}

@media (max-width:640px){
  .habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped),
  .habitaciones-view .room-card-compact.has-checkout-vencido:not(.flipped){
    height:200px!important;
  }
  .habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-face{
    padding:11px 10px 10px 14px!important;
  }
  .habitaciones-view .rc-incidents{
    gap:4px;
    margin-top:4px;
  }
  .habitaciones-view .rc-incident{
    min-height:19px;
    padding:3px 6px;
    font-size:.5rem;
  }
  .habitaciones-view .rc-incident small{
    font-size:.49rem;
  }
}

/* Correccion anti-corte 2.0 (2026-07-10): la cara frontal ahora vive en
   flujo normal (rediseño en el bloque hb), asi que la tarjeta crece con
   su contenido y nada puede cortarse: sin huesped queda compacta, con
   huesped/incidencias crece lo justo. El grid iguala alturas por fila.
   Solo el estado volteado conserva altura fija (la cara trasera es
   overlay absoluto). */
.habitaciones-view .rgrid{
  align-items:stretch!important;
}

.habitaciones-view .flip-card{
  min-height:150px!important;
  height:auto!important;
}

.habitaciones-view .flip-card.flipped{
  min-height:186px!important;
  height:186px!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
  min-height:258px!important;
  height:258px!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped{
  min-height:276px!important;
  height:276px!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped .action-buttons{
  grid-template-columns:repeat(3,minmax(0,1fr))!important;
}

.habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped .btn-action{
  padding-inline:7px!important;
}

.habitaciones-view .flip-card-inner,
.habitaciones-view .flip-card-front{
  min-height:100%!important;
}

.habitaciones-view .rc-face{
  min-height:0!important;
  gap:8px!important;
  padding:14px 15px 13px 18px!important;
}

.habitaciones-view .rc-top{
  flex:none!important;
  min-height:44px;
}

.habitaciones-view .rc-id{
  min-width:0;
}

.habitaciones-view .rc-badge{
  flex:0 1 auto;
  max-width:108px;
}

.habitaciones-view .rc-badge span{
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* Mantiene nombres largos de habitacion separados del estado. */
.habitaciones-view .rc-top{
  display:grid!important;
  grid-template-columns:minmax(0,1fr) auto!important;
  align-items:start!important;
  column-gap:8px!important;
  row-gap:4px!important;
}

.habitaciones-view .rc-id{
  min-width:0!important;
  max-width:100%!important;
}

.habitaciones-view .rc-num,
.habitaciones-view .rc-type{
  display:block!important;
  max-width:100%!important;
  overflow:hidden!important;
  text-overflow:ellipsis!important;
  white-space:nowrap!important;
}

.habitaciones-view .rc-badge{
  flex:none!important;
  justify-self:end!important;
  min-width:28px!important;
  max-width:104px!important;
  overflow:hidden!important;
}

.habitaciones-view .rc-badge span{
  display:block!important;
  white-space:nowrap!important;
  min-width:0!important;
  overflow:hidden!important;
  text-overflow:ellipsis!important;
}

.habitaciones-view .room-card-compact.room-code-long .rc-badge{
  width:34px!important;
  height:34px!important;
  padding:0!important;
  display:inline-grid!important;
  place-items:center!important;
  gap:0!important;
  border-radius:999px!important;
}

.habitaciones-view .room-card-compact.room-code-long .rc-badge i{
  font-size:14px!important;
  line-height:1!important;
}

.habitaciones-view .room-card-compact.room-code-long .rc-badge span{
  display:none!important;
}

.habitaciones-view .room-card-compact.room-code-xl .rc-top{
  min-height:56px!important;
}

.habitaciones-view .room-card-compact.room-code-xl .rc-num{
  display:-webkit-box!important;
  white-space:normal!important;
  overflow:hidden!important;
  text-overflow:clip!important;
  overflow-wrap:anywhere!important;
  word-break:break-word!important;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  max-width:9ch!important;
  max-height:2.1em!important;
  line-height:.96!important;
}

.habitaciones-view .room-card-compact.room-code-xl .rc-type{
  margin-top:3px!important;
}

.habitaciones-view .rc-mid{
  min-height:0!important;
  margin-top:auto!important;
  display:flex!important;
  flex-direction:column!important;
  gap:4px!important;
}

.habitaciones-view .rc-guest,
.habitaciones-view .rc-meta{
  line-height:1.2!important;
}

.habitaciones-view .rc-incidents{
  margin-top:1px!important;
  gap:4px!important;
  overflow:visible!important;
}

.habitaciones-view .rc-incident{
  max-width:100%!important;
  min-height:20px!important;
  padding:3px 7px!important;
}

.habitaciones-view .rc-foot{
  flex:none!important;
  margin-top:6px!important;
  min-height:24px;
}

@media (max-width:768px){
  .habitaciones-view .flip-card{
    min-height:150px!important;
    height:auto!important;
  }
  .habitaciones-view .flip-card.flipped{
    min-height:192px!important;
    height:192px!important;
  }
}

@media (max-width:640px){
  .habitaciones-view .rgrid{
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:11px!important;
  }

  .habitaciones-view .flip-card,
  .habitaciones-view .flip-card.flipped,
  .habitaciones-view .room-card-compact.has-checkin-vencido,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:224px!important;
    height:224px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:268px!important;
    height:268px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped{
    min-height:286px!important;
    height:286px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped .action-buttons{
    grid-template-columns:1fr!important;
  }

  .habitaciones-view .rc-face,
  .habitaciones-view .room-card-compact.has-checkin-vencido:not(.flipped) .rc-face{
    gap:6px!important;
    padding:12px 10px 10px 14px!important;
  }

  .habitaciones-view .rc-top{
    min-height:39px!important;
  }

  .habitaciones-view .rc-badge{
    max-width:82px!important;
  }

  .habitaciones-view .rc-mid{
    gap:3px!important;
  }

  .habitaciones-view .rc-meta{
    display:block!important;
    max-width:100%;
    font-size:.55rem!important;
    line-height:1.15!important;
  }

  .habitaciones-view .rc-incidents{
    margin-top:1px!important;
  }

  .habitaciones-view .rc-incident{
    max-width:100%!important;
    min-height:18px!important;
    padding:3px 6px!important;
  }

  .habitaciones-view .rc-foot{
    margin-top:4px!important;
    min-height:22px!important;
  }
}

@media (max-width:430px){
  .habitaciones-view .rgrid{
    grid-template-columns:1fr!important;
  }

  .habitaciones-view .flip-card,
  .habitaciones-view .flip-card.flipped,
  .habitaciones-view .room-card-compact.has-checkin-vencido,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:204px!important;
    height:204px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:268px!important;
    height:268px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped{
    min-height:286px!important;
    height:286px!important;
  }

  .habitaciones-view .rc-badge{
    max-width:116px!important;
  }
}

@media (max-width:380px){
  .habitaciones-view .flip-card,
  .habitaciones-view .flip-card.flipped,
  .habitaciones-view .room-card-compact.has-checkin-vencido,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:204px!important;
    height:204px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped{
    min-height:268px!important;
    height:268px!important;
  }

  .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped{
    min-height:286px!important;
    height:286px!important;
  }
}

/* ─── Habitaciones Mobile — Rediseño Boutique (override final) ──────────────
   2 columnas. Card compacta: número grande + ícono de estado + huésped +
   precio. Sin saturar: solo lo que el operador necesita de un vistazo.
   ─────────────────────────────────────────────────────────────────────────── */
@media (max-width:640px){

  /* 1. Grid: 2 columnas con espacio cómodo entre cards */
  .habitaciones-view .rgrid{
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:10px!important;
  }

  /* 2. Card móvil: base por contenido; volteada y vencida con altura fija */
  .habitaciones-view .flip-card{
    min-height:156px!important;
    height:auto!important;
    border-radius:18px!important;
  }
  .habitaciones-view .flip-card.flipped,
  .habitaciones-view .room-card-compact.flipped,
  .habitaciones-view .room-card-compact.has-checkin-vencido.flipped,
  .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped{
    min-height:156px!important;
    height:156px!important;
    border-radius:18px!important;
  }
  .habitaciones-view .flip-card-inner,
  .habitaciones-view .flip-card-front{
    border-radius:18px!important;
    min-height:100%!important;
  }

  /* 3. rc-face: padding balanceado */
  .habitaciones-view .rc-face{
    padding:12px 11px 11px 16px!important;
    gap:0!important;
  }

  /* 4. rc-stripe: barra de color de estado */
  .habitaciones-view .rc-stripe{
    width:5px!important;
    border-radius:18px 0 0 18px!important;
  }

  /* 5. rc-top: número a la izquierda, ícono de estado a la derecha */
  .habitaciones-view .rc-top{
    min-height:0!important;
    align-items:flex-start!important;
    column-gap:6px!important;
    row-gap:2px!important;
  }

  /* 6. rc-num: número de habitación prominente en serif */
  .habitaciones-view .rc-num{
    font-size:1.72rem!important;
    font-weight:600!important;
    line-height:.88!important;
  }

  /* 7. rc-type: piso · tipo, pequeño y sin truncar */
  .habitaciones-view .rc-type{
    max-width:none!important;
    font-size:.58rem!important;
    margin-top:3px!important;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
  }

  /* 8. rc-badge: círculo ícono — legible, sin texto, sin espacio */
  .habitaciones-view .rc-badge{
    flex:none!important;
    width:26px!important;
    height:26px!important;
    max-width:26px!important;
    min-height:0!important;
    padding:0!important;
    display:inline-grid!important;
    place-items:center!important;
    border-radius:50%!important;
    font-size:0!important;
  }
  .habitaciones-view .rc-badge i{
    display:inline!important;
    font-size:.6rem!important;
  }
  .habitaciones-view .rc-badge span{
    display:none!important;
  }

  /* 9. rc-mid: solo nombre del huésped, sin meta ni incidencias */
  .habitaciones-view .rc-mid{
    gap:0!important;
  }
  .habitaciones-view .rc-meta{
    display:none!important;
  }
  .habitaciones-view .rc-incidents{
    display:none!important;
  }
  .habitaciones-view .rc-room-facts{
    display:flex!important;
    flex-wrap:nowrap!important;
    gap:4px!important;
    margin-top:6px!important;
    min-width:0!important;
  }
  .habitaciones-view .rc-room-fact{
    flex:1 1 0!important;
    min-width:0!important;
    min-height:19px!important;
    padding:3px 5px!important;
    font-size:.54rem!important;
    font-weight:750!important;
  }
  .habitaciones-view .rc-room-fact i{
    font-size:.52rem!important;
  }
  .habitaciones-view .rc-guest{
    font-size:.7rem!important;
    font-weight:600!important;
    gap:5px!important;
    line-height:1.2!important;
  }
  .habitaciones-view .rc-guest i{ font-size:.6rem!important; }

  /* 10. rc-foot: precio visible, hint como ícono discreto */
  .habitaciones-view .rc-foot{
    min-height:0!important;
    margin-top:8px!important;
    gap:5px!important;
  }
  .habitaciones-view .rc-price{
    font-size:.7rem!important;
    font-weight:600!important;
  }
  .habitaciones-view .rc-price small{
    display:none!important;
  }
  .habitaciones-view .rc-hint{
    width:20px!important;
    height:20px!important;
    flex:none;
    border:1px solid var(--hb-line)!important;
    border-radius:50%!important;
    background:var(--hb-surface-warm)!important;
    padding:0!important;
    font-size:0!important;
    color:var(--hb-slate-400)!important;
  }
  .habitaciones-view .rc-hint i{ font-size:.56rem!important; }
  .habitaciones-view .rc-hint span{ display:none!important; }

  /* 11. Indicadores de alerta: esquina superior derecha */
  .habitaciones-view .checkout-today-indicator,
  .habitaciones-view .checkout-vencido-indicator,
  .habitaciones-view .checkin-vencido-indicator,
  .habitaciones-view .late-arrival-indicator{
    top:7px!important;
    right:8px!important;
    font-size:.48rem!important;
    padding:2px 5px!important;
    min-height:16px!important;
  }

  /* 12. Header — compacto, sin cuadro, botones en una sola fila */

  /* Contenedor del header: padding mínimo */
  .habitaciones-view .modern-header .container{
    padding:8px 13px 9px!important;
  }

  /* Quitar el cuadro/caja con gradiente oscuro */
  .habitaciones-view .modern-header .p-2.rounded-lg{
    display:none!important;
  }

  /* Titulo: sin gap extra, compacto */
  .habitaciones-view .modern-header .container > .flex > .flex.items-center{
    gap:0!important;
  }
  .habitaciones-view .modern-header h1{
    font-size:1.28rem!important;
    letter-spacing:-.01em!important;
    line-height:1!important;
  }
  .habitaciones-view .modern-header p{
    display:none!important;
  }

  /* Botones: todos en UNA sola fila horizontal */
  .habitaciones-view .modern-header .flex.flex-wrap.gap-2{
    display:flex!important;
    flex-wrap:nowrap!important;
    gap:6px!important;
    width:100%!important;
  }
  .habitaciones-view .modern-header .btn-modern{
    flex:1!important;
    min-width:0!important;
    min-height:34px!important;
    height:34px!important;
    padding:0 8px!important;
    border-radius:11px!important;
    font-size:.64rem!important;
    font-weight:600!important;
    flex-direction:row!important;
    gap:5px!important;
    box-shadow:none!important;
    white-space:nowrap;
  }
  .habitaciones-view .modern-header .btn-modern i{
    font-size:.76rem!important;
    margin:0!important;
    flex:none;
  }
  .habitaciones-view .modern-header .btn-modern span{
    display:inline!important;
    overflow:hidden;
    text-overflow:ellipsis;
    min-width:0;
  }
  /* Nueva Reserva: ocupa el doble de ancho que los otros */
  .habitaciones-view .modern-header .btn-modern.btn-brand{
    order:-1!important;
    flex:2!important;
  }

  /* 13. Widget de ocupación — tarjeta compacta, mismo estilo movements */
  .habitaciones-view .hb-mobile-occupancy{
    margin-bottom:10px!important;
    padding:0!important;
    border:1px solid var(--hb-line)!important;
    border-radius:16px!important;
    overflow:hidden!important;
    background:var(--hb-surface)!important;
    box-shadow:0 2px 8px -5px rgba(18,22,34,.1)!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:10px!important;
    margin-bottom:0!important;
    padding:9px 13px!important;
    background:color-mix(in srgb,var(--hb-primary) 6%,var(--hb-ivory))!important;
    border-bottom:1px solid color-mix(in srgb,var(--hb-primary) 8%,var(--hb-line))!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head span{
    font-size:.67rem!important;
    font-weight:600!important;
    color:var(--hb-primary)!important;
    letter-spacing:.06em!important;
    text-transform:uppercase!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head strong{
    font-size:.7rem!important;
    font-weight:700!important;
    font-variant-numeric:tabular-nums!important;
    background:var(--hb-primary)!important;
    color:#fff!important;
    padding:2px 9px!important;
    border-radius:999px!important;
    line-height:1.5!important;
  }
  .habitaciones-view .hb-mobile-occbar{
    height:7px!important;
    border-radius:999px!important;
    margin:9px 13px 0!important;
    gap:2px!important;
    overflow:hidden!important;
    background:color-mix(in srgb,var(--hb-primary) 5%,var(--hb-line))!important;
  }
  .habitaciones-view .hb-mobile-occbar span{
    display:block!important;
    min-width:3px!important;
  }
  .habitaciones-view .hb-mobile-legend{
    display:flex!important;
    flex-wrap:wrap!important;
    gap:5px 13px!important;
    padding:8px 13px 10px!important;
    margin-top:0!important;
  }
  .habitaciones-view .hb-mobile-lg{
    display:inline-flex!important;
    align-items:center!important;
    gap:4px!important;
    font-size:.64rem!important;
    font-weight:600!important;
    color:var(--hb-slate-500)!important;
    border:0!important;
    padding:0!important;
    background:transparent!important;
    line-height:1.2!important;
  }
  .habitaciones-view .hb-mobile-dot{
    width:6px!important;
    height:6px!important;
    border-radius:999px!important;
    flex:none!important;
  }
  .habitaciones-view .hb-mobile-lg b{
    font-weight:700!important;
    color:var(--hb-primary)!important;
  }
  .habitaciones-view .hb-mobile-lg.is-active{
    color:color-mix(in srgb,var(--chip-c) 78%,#1F2937)!important;
  }

  /* 14. Corrección de pesos tipográficos (regla boutique: máx 700 sans) */
  .habitaciones-view .hb-chip{ font-weight:600!important; }
  .habitaciones-view .hb-chip.is-active{ font-weight:700!important; }
  .habitaciones-view .floor-t{ font-weight:700!important; }
  .habitaciones-view .hb-move-head h3 > span:last-child{ font-weight:700!important; }
  .habitaciones-view .hb-move-item--out button{ font-weight:700!important; }
  .habitaciones-view .hb-mobile-sheet-content .btn-action{ font-weight:700!important; }
  .hb-reservation-pill{ font-weight:700!important; }
  .hb-date-row span{ font-weight:600!important; }
  .hb-date-row strong{ font-weight:700!important; }
  .hb-arrival-label{ font-weight:700!important; }
  .hb-arrival-input{ font-weight:600!important; }
  .hb-arrival-now{ font-weight:700!important; }

}
/* ─── Fin rediseño móvil ─────────────────────────────────────────────────── */

</style>

<style id="hb-mobile-top-refresh">
.habitaciones-view .hb-filter-trigger{display:none;}
@media (max-width:767px){
  .habitaciones-view{
    background:radial-gradient(680px 260px at 88% -80px,color-mix(in srgb,var(--hb-accent) 13%,transparent),transparent 62%),linear-gradient(180deg,#FAFAFC 0%,#F7F1E8 100%)!important;
  }
  .habitaciones-view .hb-page-header{ display:block!important; }
  .habitaciones-view .modern-header{
    display:none!important;
  }
  .habitaciones-view .modern-header .container{padding:22px 23px 10px!important;}
  .habitaciones-view .modern-header .container>.flex{
    display:grid!important;
    grid-template-columns:1fr!important;
    align-items:stretch!important;
    gap:16px!important;
  }
  .habitaciones-view .modern-header .container>.flex>.flex.items-center{
    display:block!important;
    width:100%!important;
    gap:0!important;
  }
  .habitaciones-view .modern-header .p-2.rounded-lg,
  .habitaciones-view .modern-header .hb-title-prefix{display:none!important;}
  .habitaciones-view .modern-header h1{
    margin:0!important;
    color:#111827!important;
    font-family:var(--serif)!important;
    font-size:clamp(2.08rem,8.6vw,2.75rem)!important;
    font-weight:650!important;
    line-height:.96!important;
    letter-spacing:0!important;
  }
  .habitaciones-view .modern-header p{
    display:block!important;
    margin:7px 0 0!important;
    color:#6D625C!important;
    font-size:.94rem!important;
    font-weight:500!important;
    line-height:1.35!important;
  }
  .habitaciones-view .modern-header .flex.flex-wrap.gap-2{
    width:100%!important;
    display:grid!important;
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
    gap:12px!important;
  }
  .habitaciones-view .modern-header .btn-modern{
    min-width:0!important;
    width:100%!important;
    height:54px!important;
    min-height:54px!important;
    padding:0 12px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 12%,var(--hb-line))!important;
    border-radius:16px!important;
    display:flex!important;
    flex-direction:row!important;
    align-items:center!important;
    justify-content:center!important;
    gap:9px!important;
    background:color-mix(in srgb,#FFFFFF 82%,var(--hb-ivory))!important;
    color:color-mix(in srgb,var(--hb-secondary) 76%,#4B5563)!important;
    box-shadow:0 16px 32px -28px rgba(17,24,39,.42)!important;
    font-size:.84rem!important;
    font-weight:760!important;
    line-height:1.1!important;
    text-transform:none!important;
  }
  .habitaciones-view .modern-header .btn-modern i{
    margin:0!important;
    color:currentColor!important;
    font-size:1rem!important;
    flex:none!important;
  }
  .habitaciones-view .modern-header .btn-modern span{
    display:inline!important;
    min-width:0!important;
    overflow:hidden!important;
    text-overflow:ellipsis!important;
    white-space:nowrap!important;
  }
  .habitaciones-view .modern-header .btn-modern.btn-brand{
    order:1!important;
    flex:auto!important;
    border-color:transparent!important;
    background:linear-gradient(135deg,color-mix(in srgb,var(--hb-secondary) 64%,var(--hb-accent)),var(--hb-secondary))!important;
    color:#F9F5ED!important;
  }
  .habitaciones-view .modern-header button[onclick="mostrarVistaRapida()"]{order:2!important;}
  .habitaciones-view .modern-header .btn-modern.btn-brand-outline{
    order:3!important;
    background:color-mix(in srgb,#FFFFFF 88%,var(--hb-ivory))!important;
    color:color-mix(in srgb,var(--hb-secondary) 72%,#374151)!important;
  }
  .habitaciones-view .modern-header .btn-modern.btn-brand-soft{
    order:4!important;
    grid-column:1/-1!important;
    height:46px!important;
    min-height:46px!important;
    background:color-mix(in srgb,var(--hb-primary) 7%,#FFFFFF)!important;
    color:var(--hb-primary)!important;
  }
  .habitaciones-view>.container{
    max-width:none!important;
    padding:12px 22px 28px!important;
  }
  .habitaciones-view .hb-stats{display:none!important;}
  .habitaciones-view .hb-mobile-occupancy{
    display:block!important;
    margin:8px 0 18px!important;
    padding:20px 20px 18px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 11%,var(--hb-line))!important;
    border-radius:18px!important;
    background:rgba(255,255,255,.62)!important;
    box-shadow:0 18px 44px -34px rgba(17,24,39,.45)!important;
    overflow:hidden!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:12px!important;
    margin:0 0 10px!important;
    padding:0!important;
    border:0!important;
    background:transparent!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head span{
    color:#6F5144!important;
    font-family:var(--serif)!important;
    font-size:.9rem!important;
    font-weight:650!important;
    letter-spacing:.055em!important;
    line-height:1.2!important;
    text-transform:uppercase!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head strong{
    min-width:64px!important;
    min-height:0!important;
    padding:4px 9px!important;
    display:inline-flex!important;
    flex-direction:column!important;
    align-items:center!important;
    justify-content:center!important;
    gap:1px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 10%,var(--hb-line))!important;
    border-radius:10px!important;
    background:rgba(255,255,255,.72)!important;
    color:#111827!important;
    box-shadow:none!important;
    text-align:center!important;
  }
  .habitaciones-view .hb-mobile-occupancy-head strong b{
    display:block;
    color:#111827!important;
    font-size:.82rem!important;
    font-weight:700!important;
    line-height:1!important;
    font-variant-numeric:tabular-nums;
  }
  .habitaciones-view .hb-mobile-occupancy-head strong small{
    display:block;
    margin-top:1px;
    color:#776D67!important;
    font-size:.58rem!important;
    font-weight:500!important;
    line-height:1.05!important;
  }
  .habitaciones-view .hb-mobile-occbar{
    display:flex!important;
    gap:5px!important;
    height:8px!important;
    margin:0!important;
    border-radius:999px!important;
    background:transparent!important;
    overflow:hidden!important;
  }
  .habitaciones-view .hb-mobile-occbar span{
    display:block!important;
    min-width:8px!important;
    border-radius:999px!important;
  }
  .habitaciones-view .hb-mobile-legend{
    display:flex!important;
    flex-wrap:wrap!important;
    gap:4px 14px!important;
    padding:8px 13px 10px!important;
    margin:0!important;
  }
  .habitaciones-view .hb-mobile-lg{
    display:inline-flex!important;
    flex-direction:row!important;
    align-items:center!important;
    justify-content:flex-start!important;
    gap:4px!important;
    min-width:0!important;
    min-height:0!important;
    padding:0!important;
    border:0!important;
    background:transparent!important;
    color:#5F5A55!important;
    font-size:.72rem!important;
    font-weight:500!important;
    line-height:1.2!important;
    white-space:nowrap!important;
  }
  .habitaciones-view .hb-mobile-lg:last-child{border-right:0!important;}
  .habitaciones-view .hb-mobile-dot{
    width:7px!important;
    height:7px!important;
    border-radius:999px!important;
    flex:none!important;
  }
  .habitaciones-view .hb-mobile-lg b{
    color:#111827!important;
    font-size:.72rem!important;
    font-weight:600!important;
    line-height:1.2!important;
  }
  .habitaciones-view .hb-mobile-lg.is-active{
    background:color-mix(in srgb,var(--chip-c) 10%,transparent)!important;
    border:1px solid color-mix(in srgb,var(--chip-c) 26%,transparent)!important;
    border-radius:999px!important;
    padding:2px 8px 2px 5px!important;
    color:color-mix(in srgb,var(--chip-c) 78%,#1F2937)!important;
    font-weight:600!important;
  }
  .habitaciones-view .hb-mobile-lg.is-active b{
    color:inherit!important;
    font-weight:700!important;
  }
  .habitaciones-view .hb-filter-panel{
    margin:0 0 16px!important;
    padding:0!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
    box-shadow:none!important;
    overflow:visible!important;
  }
  .habitaciones-view .hb-filterbar{
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 40px!important;
    gap:8px!important;
    padding:0!important;
  }
  .habitaciones-view .hb-search{
    width:100%!important;
    max-width:none!important;
    min-height:40px!important;
    padding:0 13px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 12%,var(--hb-line))!important;
    border-radius:12px!important;
    background:rgba(255,255,255,.64)!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-search i{
    color:#8C827B!important;
    font-size:.82rem!important;
  }
  .habitaciones-view .hb-search input{
    font-size:.85rem!important;
    font-weight:500!important;
    color:#111827!important;
  }
  .habitaciones-view .hb-search input::placeholder{color:#A39A93!important;}
  .habitaciones-view .hb-filter-trigger{
    display:grid!important;
    place-items:center!important;
    width:44px!important;
    height:44px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 12%,var(--hb-line))!important;
    border-radius:12px!important;
    background:rgba(255,255,255,.66)!important;
    color:color-mix(in srgb,var(--hb-secondary) 72%,#6B7280)!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-filter-trigger i{font-size:.85rem!important;}
  .habitaciones-view .hb-fdiv{display:none!important;}
  .habitaciones-view .hb-chips{
    grid-column:1/-1!important;
    display:flex!important;
    flex-wrap:nowrap!important;
    gap:7px!important;
    margin:4px -2px 0!important;
    padding:0 2px 3px!important;
    overflow-x:auto!important;
    scrollbar-width:none!important;
  }
  .habitaciones-view .hb-chips::-webkit-scrollbar{width:0!important;height:0!important;}
  .habitaciones-view .hb-chip{
    flex:0 0 auto!important;
    min-height:44px!important;
    padding:0 11px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 10%,var(--hb-line))!important;
    border-radius:999px!important;
    background:rgba(255,255,255,.55)!important;
    color:#5F5A55!important;
    font-size:.74rem!important;
    font-weight:600!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-chip.is-active{
    border-color:color-mix(in srgb,var(--chip-c) 45%,#fff)!important;
    background:color-mix(in srgb,var(--chip-c) 14%,#fff)!important;
    color:color-mix(in srgb,var(--chip-c) 76%,#1F2937)!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-chip-ct{color:inherit!important;opacity:.72!important;}
  .habitaciones-view .hb-filter-right{
    grid-column:1/-1!important;
    width:100%!important;
    display:grid!important;
    grid-template-columns:minmax(0,1fr) 44px 44px!important;
    gap:8px!important;
    margin:4px 0 0!important;
  }
  .habitaciones-view .filter-date,
  .habitaciones-view .filter-btn{
    min-height:44px!important;
    height:44px!important;
    border-radius:12px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 11%,var(--hb-line))!important;
    background:rgba(255,255,255,.62)!important;
    color:#111827!important;
    box-shadow:none!important;
  }
  .habitaciones-view .filter-date{
    padding:0 12px!important;
    font-size:.85rem!important;
    font-weight:500!important;
  }
  .habitaciones-view .filter-btn{
    display:grid!important;
    place-items:center!important;
    padding:0!important;
    font-size:.85rem!important;
  }
  .habitaciones-view .filter-btn span{display:none!important;}
  .habitaciones-view .filter-btn-reset{
    color:#EF4D45!important;
    background:rgba(255,255,255,.7)!important;
  }
  .habitaciones-view .hb-filter-panel{
    padding:10px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 10%,var(--hb-line))!important;
    border-radius:18px!important;
    background:linear-gradient(180deg,rgba(255,255,255,.78),rgba(255,255,255,.56))!important;
    box-shadow:0 16px 34px -30px rgba(18,22,34,.42)!important;
  }
  .habitaciones-view .hb-filterbar{
    grid-template-columns:minmax(0,1fr) 44px!important;
    gap:10px!important;
    align-items:center!important;
  }
  .habitaciones-view .hb-search,
  .habitaciones-view .hb-filter-trigger,
  .habitaciones-view .filter-date,
  .habitaciones-view .filter-btn{
    min-height:44px!important;
    height:44px!important;
    border-radius:14px!important;
    background:#fff!important;
    border-color:color-mix(in srgb,var(--hb-secondary) 12%,var(--hb-line))!important;
    box-shadow:0 8px 20px -20px rgba(18,22,34,.5)!important;
  }
  .habitaciones-view .hb-search{
    padding:0 14px!important;
  }
  .habitaciones-view .hb-filter-trigger{
    width:44px!important;
  }
  .habitaciones-view .hb-chips{
    flex-wrap:nowrap!important;
    gap:8px!important;
    margin:0 -2px!important;
    padding:0 2px 3px!important;
    overflow-x:auto!important;
    overflow-y:hidden!important;
    scrollbar-width:none!important;
    -webkit-overflow-scrolling:touch!important;
  }
  .habitaciones-view .hb-chips::-webkit-scrollbar{width:0!important;height:0!important;}
  .habitaciones-view .hb-chip{
    flex:0 0 auto!important;
    min-height:34px!important;
    padding:0 12px!important;
    background:rgba(255,255,255,.88)!important;
    border-color:color-mix(in srgb,var(--hb-secondary) 13%,var(--hb-line))!important;
    font-size:.74rem!important;
    line-height:1!important;
  }
  .habitaciones-view .hb-chip-dot{
    width:7px!important;
    height:7px!important;
  }
  .habitaciones-view .hb-filter-right{
    grid-template-columns:minmax(0,1fr) 44px 44px!important;
    gap:8px!important;
    margin:0!important;
  }
  .habitaciones-view .filter-date{
    min-width:0!important;
    padding:0 12px!important;
    font-size:.86rem!important;
  }
  .habitaciones-view .filter-btn{
    font-size:.88rem!important;
  }
  .habitaciones-view .filter-btn-reset{
    color:#E0443E!important;
    background:color-mix(in srgb,#FFF 88%,#FEE2E2)!important;
  }
  .habitaciones-view .hb-movements{
    display:grid!important;
    gap:10px!important;
    margin:10px 0 12px!important;
  }
  .habitaciones-view .hb-move-card{
    border:1px solid color-mix(in srgb,var(--hb-secondary) 10%,var(--hb-line))!important;
    border-radius:14px!important;
    overflow:hidden!important;
    background:rgba(255,255,255,.62)!important;
    box-shadow:none!important;
  }
  .habitaciones-view .hb-move-card--in{background:linear-gradient(135deg,color-mix(in srgb,var(--c-arriving) 9%,#FFFFFF),rgba(255,255,255,.66) 62%)!important;}
  .habitaciones-view .hb-move-card--out{background:linear-gradient(135deg,color-mix(in srgb,var(--c-maint) 10%,#FFFFFF),rgba(255,255,255,.66) 62%)!important;}
  .habitaciones-view .hb-move-head{
    padding:10px 13px 7px!important;
    border:0!important;
    background:transparent!important;
  }
  .habitaciones-view .hb-move-head h3{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:10px!important;
    color:#111827!important;
    font-family:var(--serif)!important;
    font-size:.95rem!important;
    font-weight:650!important;
    line-height:1!important;
  }
  .habitaciones-view .hb-move-head h3>span:first-child{
    display:flex!important;
    align-items:center!important;
    gap:7px!important;
    min-width:0!important;
  }
  .habitaciones-view .hb-move-title>span{
    display:grid!important;
    gap:1px!important;
    min-width:0!important;
  }
  .habitaciones-view .hb-move-title strong{
    color:#111827!important;
    font:inherit!important;
    line-height:1!important;
  }
  .habitaciones-view .hb-move-title small{
    color:#8E837B!important;
    font-family:var(--hb-sans,'DM Sans',system-ui,sans-serif)!important;
    font-size:.65rem!important;
    font-weight:500!important;
    line-height:1.15!important;
  }
  .habitaciones-view .hb-move-head h3 i{
    margin-top:0!important;
    font-size:.82rem!important;
  }
  .habitaciones-view .hb-move-head h3>span:last-child{
    min-width:26px!important;
    height:26px!important;
    display:grid!important;
    place-items:center!important;
    padding:0!important;
    border-radius:999px!important;
    color:#fff!important;
    font-family:var(--hb-sans,'DM Sans',system-ui,sans-serif)!important;
    font-size:.75rem!important;
    font-weight:700!important;
  }
  .habitaciones-view .hb-move-body{
    max-height:none!important;
    padding:0 9px 9px!important;
    overflow:visible!important;
  }
  .habitaciones-view .hb-move-item{
    padding:8px 10px!important;
    border:1px solid color-mix(in srgb,var(--hb-secondary) 8%,var(--hb-line))!important;
    border-radius:10px!important;
    background:rgba(255,255,255,.72)!important;
  }
  .habitaciones-view .hb-move-item p:first-child{
    color:#111827!important;
    font-size:.82rem!important;
    font-weight:700!important;
  }
  .habitaciones-view .hb-move-item p:last-child{
    color:#756C65!important;
    font-size:.76rem!important;
    line-height:1.35!important;
  }
  .habitaciones-view .hb-move-action{
    width:30px!important;
    height:30px!important;
    display:grid!important;
    place-items:center!important;
    border-radius:999px!important;
    background:color-mix(in srgb,var(--c-arriving) 12%,#FFFFFF)!important;
    color:var(--c-arriving)!important;
    font-size:.78rem!important;
  }
  .habitaciones-view .hb-move-empty{
    margin:0!important;
    padding:10px 12px!important;
    border:1px dashed color-mix(in srgb,var(--hb-secondary) 12%,var(--hb-line))!important;
    border-radius:10px!important;
    color:#9B928C!important;
    background:rgba(255,255,255,.42)!important;
    font-family:var(--serif)!important;
    font-size:.8rem!important;
    text-align:center!important;
  }
}
</style>

<style id="hb-cleaning-button-polish">
.habitaciones-view .btn-modern.hb-cleaning-btn,
.habitaciones-view .hb-action-btn.hb-cleaning-btn{
    background:linear-gradient(135deg,#E0F2FE 0%,#BAE6FD 100%)!important;
    color:#075985!important;
    border-color:#7DD3FC!important;
    box-shadow:0 10px 22px -16px rgba(2,132,199,.72)!important;
}
.habitaciones-view .btn-modern.hb-cleaning-btn i,
.habitaciones-view .hb-action-btn.hb-cleaning-btn i{
    color:#0284C7!important;
}
.habitaciones-view .btn-modern.hb-cleaning-btn:hover,
.habitaciones-view .hb-action-btn.hb-cleaning-btn:hover{
    background:linear-gradient(135deg,#D8F0FF 0%,#A7DDFB 100%)!important;
    border-color:#38BDF8!important;
    color:#075985!important;
    box-shadow:0 14px 26px -17px rgba(2,132,199,.86)!important;
    transform:translateY(-1px)!important;
}
.habitaciones-view .btn-modern.hb-cleaning-btn > span.absolute,
.habitaciones-view .hb-action-btn.hb-cleaning-btn::after{
    background:#0369A1!important;
    color:#FFFFFF!important;
    box-shadow:0 8px 16px -10px rgba(3,105,161,.9)!important;
}
</style>

<style id="hb-quick-view-final-override">
    #vistaRapidaModal.hb-quick-modal {
        background:
            radial-gradient(860px 360px at 84% 8%, color-mix(in srgb, var(--brand-accent, #BD9441) 18%, transparent), transparent 62%),
            rgba(13, 18, 29, .66) !important;
        backdrop-filter: blur(12px) !important;
    }

    #vistaRapidaModal.hb-quick-modal > .hb-quick-dialog.bg-white {
        width: min(1180px, calc(100vw - 28px)) !important;
        max-width: none !important;
        max-height: min(88vh, 820px) !important;
        border-radius: 18px !important;
        overflow: hidden !important;
        background:
            linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.9)),
            color-mix(in srgb, var(--brand-accent, #BD9441) 7%, #fbfaf6) !important;
        border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 26%, rgba(255,255,255,.22)) !important;
        box-shadow: 0 34px 90px -44px rgba(0,0,0,.76) !important;
    }

    #vistaRapidaModal.hb-quick-modal > .hb-quick-dialog > .hb-quick-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        min-height: 124px !important;
        padding: clamp(18px, 2.1vw, 28px) !important;
        border-radius: 0 !important;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 94%, #000), color-mix(in srgb, var(--brand-primary, #1B2746) 82%, var(--brand-secondary, #0F172A))) !important;
        border-bottom: 0 !important;
    }

    #vistaRapidaModal.hb-quick-modal h3.hb-quick-title {
        font-size: clamp(1.55rem, 2.2vw, 2.55rem) !important;
        line-height: .95 !important;
        color: #fff !important;
    }

    #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer.hb-quick-body {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 270px !important;
        max-height: calc(88vh - 124px) !important;
        padding: 0 !important;
        overflow: hidden !important;
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--brand-primary, #1B2746) 4%, transparent) 1px, transparent 1px),
            linear-gradient(180deg, #fffdfa, color-mix(in srgb, var(--brand-accent, #BD9441) 7%, #fbfaf6)) !important;
        background-size: 28px 28px, auto !important;
    }

    #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer .hb-quick-grid {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(104px, 1fr)) !important;
        gap: 10px !important;
    }

    #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer .room-quick-view.hb-quick-room {
        background: linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,255,255,.88)) !important;
        color: var(--qv-ink, #172033) !important;
        border-color: color-mix(in srgb, var(--qv-room) 30%, var(--qv-line, #eadfca)) !important;
        transform: none !important;
    }

    #vistaRapidaModal.hb-quick-modal .hb-quick-aside {
        display: block !important;
    }

    @media (max-width: 940px) {
        #vistaRapidaModal.hb-quick-modal {
            align-items: flex-end !important;
            padding: 10px !important;
        }

        #vistaRapidaModal.hb-quick-modal > .hb-quick-dialog.bg-white {
            width: 100% !important;
            max-width: none !important;
            max-height: 92dvh !important;
            border-radius: 18px 18px 12px 12px !important;
        }

        #vistaRapidaModal.hb-quick-modal > .hb-quick-dialog > .hb-quick-header {
            min-height: 112px !important;
            padding: 16px !important;
            border-radius: 18px 18px 0 0 !important;
        }

        #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer.hb-quick-body {
            grid-template-columns: 1fr !important;
            max-height: calc(92dvh - 112px) !important;
            overflow: auto !important;
            padding: 0 !important;
        }

        #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer .hb-quick-grid {
            grid-template-columns: repeat(auto-fill, minmax(86px, 1fr)) !important;
            gap: 8px !important;
        }
    }

    @media (max-width: 520px) {
        #vistaRapidaModal.hb-quick-modal h3.hb-quick-title {
            font-size: 1.34rem !important;
        }

        #vistaRapidaModal.hb-quick-modal #vistaRapidaContainer .hb-quick-grid {
            grid-template-columns: repeat(auto-fill, minmax(78px, 1fr)) !important;
        }
    }
</style>

<style id="hb-reservation-flow-modal-redesign">
/* Reservacion rapida: modales compactos, neutrales y legibles con cualquier branding. */
.swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
.swal2-container.hb-swal-sheet-container.swal2-noanimation{
    background:
        radial-gradient(740px 320px at 50% -12%, color-mix(in srgb, var(--brand-accent, #BD9441) 16%, transparent), transparent 62%),
        rgba(15, 23, 42, .42) !important;
    backdrop-filter: blur(7px) !important;
}

.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-primary: var(--brand-primary, #1B2746);
    --hb-modal-secondary: var(--brand-secondary, #0F172A);
    --hb-modal-accent: var(--brand-accent, #BD9441);
    --hb-modal-ink: #172033;
    --hb-modal-soft-ink: #475569;
    --hb-modal-muted: #6B7280;
    --hb-modal-line: color-mix(in srgb, var(--hb-modal-primary) 12%, #E6DCCB);
    --hb-modal-soft: color-mix(in srgb, var(--hb-modal-primary) 5%, #FFFFFF);
    --hb-modal-action: color-mix(in srgb, var(--hb-modal-primary) 78%, #111827);
    --hb-modal-action-text: #FFFFFF;
    width: min(720px, calc(100vw - 32px)) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 18px !important;
    background:
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,253,248,.96)),
        #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
    box-shadow:
        0 30px 76px -42px rgba(15, 23, 42, .68),
        0 1px 0 rgba(255,255,255,.9) inset !important;
}

.swal2-popup.hb-swal-arrival{
    width: min(560px, calc(100vw - 32px)) !important;
}

.hb-swal .swal2-title:empty{
    display: none !important;
}

.hb-swal .swal2-html-container{
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

.hb-swal .swal2-close{
    position: absolute !important;
    top: 12px !important;
    right: 12px !important;
    z-index: 30 !important;
    width: 34px !important;
    height: 34px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-primary) 10%, #E5E7EB) !important;
    border-radius: 999px !important;
    background: rgba(255,255,255,.84) !important;
    color: var(--hb-modal-soft-ink) !important;
    font-size: 1.2rem !important;
    cursor: pointer !important;
    pointer-events: auto !important;
    transition: transform .16s ease, background .16s ease, color .16s ease;
}

.hb-swal .swal2-close:hover{
    transform: translateY(-1px);
    background: #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
}

.hb-client-choice,
.hb-reservation-step{
    width: 100%;
    text-align: left;
    background: transparent !important;
}

.hb-client-choice__head,
.hb-arrival-head{
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    padding: 18px 20px 15px;
    border-bottom: 1px solid var(--hb-modal-line);
    background:
        radial-gradient(420px 160px at 92% 0%, color-mix(in srgb, var(--hb-modal-accent) 12%, transparent), transparent 68%),
        linear-gradient(180deg, #FFFFFF, color-mix(in srgb, var(--hb-modal-primary) 3%, #FFFDF8));
}

.hb-client-choice__mark,
.hb-arrival-mark{
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: var(--hb-modal-action) !important;
    color: var(--hb-modal-action-text) !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--hb-modal-action) 78%, transparent);
}

.hb-client-choice__mark i,
.hb-arrival-mark i{
    font-size: .95rem;
}

.hb-client-choice__eyebrow{
    display: block;
    margin: 0 0 4px;
    color: color-mix(in srgb, var(--hb-modal-accent) 72%, var(--hb-modal-soft-ink)) !important;
    font-size: .68rem;
    font-weight: 850;
    letter-spacing: .08em;
    line-height: 1.1;
    text-transform: uppercase;
}

.hb-client-choice h3,
.hb-arrival-head h3{
    margin: 0;
    color: var(--hb-modal-ink) !important;
    font-size: 1.15rem;
    font-weight: 850;
    letter-spacing: 0;
    line-height: 1.12;
}

.hb-client-choice__head p,
.hb-arrival-head p{
    max-width: 52ch;
    margin: 6px 0 0;
    color: var(--hb-modal-muted) !important;
    font-size: .84rem;
    font-weight: 500;
    line-height: 1.45;
}

.hb-client-choice__options{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 16px 18px 18px;
}

.hb-client-option{
    position: relative;
    display: grid !important;
    grid-template-rows: auto 1fr auto;
    gap: 12px;
    min-height: 156px;
    padding: 14px !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 16px !important;
    background: #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.9) inset, 0 16px 32px -30px rgba(15,23,42,.44) !important;
    text-align: left !important;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.hb-client-option::before{
    content: "";
    position: absolute;
    inset: 12px auto 12px 0;
    width: 3px;
    border-radius: 0 999px 999px 0;
    background: color-mix(in srgb, var(--hb-modal-action) 58%, #D8C8A8);
    opacity: .82;
}

.hb-client-option:hover,
.hb-client-option:focus-visible{
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--hb-modal-action) 26%, #D9CDBA) !important;
    background: color-mix(in srgb, var(--hb-modal-action) 4%, #FFFFFF) !important;
    box-shadow: 0 20px 40px -30px color-mix(in srgb, var(--hb-modal-action) 48%, transparent) !important;
}

.hb-client-option__top,
.hb-client-option__body,
.hb-client-option__cta{
    position: relative;
    z-index: 1;
}

.hb-client-option__top{
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.hb-client-option__icon{
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: var(--hb-modal-soft) !important;
    color: var(--hb-modal-action) !important;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--hb-modal-action) 12%, transparent) !important;
}

.hb-client-option__tag{
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 12%, #ECE5D8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--hb-modal-action) 5%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-soft-ink)) !important;
    font-size: .67rem;
    font-weight: 800;
    line-height: 1;
}

.hb-client-option__body{
    display: grid;
    gap: 6px;
}

.hb-client-option__body strong{
    color: var(--hb-modal-ink) !important;
    font-size: .98rem;
    font-weight: 850;
    line-height: 1.15;
}

.hb-client-option__body span{
    color: var(--hb-modal-muted) !important;
    font-size: .82rem;
    font-weight: 500;
    line-height: 1.38;
}

.hb-client-option__cta{
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 34px;
    padding-top: 10px;
    border-top: 1px solid color-mix(in srgb, var(--hb-modal-action) 10%, #ECE5D8) !important;
    color: var(--hb-modal-action) !important;
    font-size: .78rem;
    font-weight: 850;
}

.hb-client-option__cta i{
    transition: transform .16s ease;
}

.hb-client-option:hover .hb-client-option__cta i,
.hb-client-option:focus-visible .hb-client-option__cta i{
    transform: translateX(2px);
}

.hb-client-option--existing{
    --hb-modal-action: color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #1E9E63);
}

.hb-reservation-pill--existing{
    --hb-modal-action: color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #1E9E63);
}

.hb-reservation-pill--new{
    --hb-modal-action: color-mix(in srgb, var(--brand-primary, #1B2746) 84%, var(--brand-accent, #BD9441));
}

.hb-arrival-context{
    display: grid;
    gap: 12px;
    padding: 15px 18px 18px;
}

.hb-reservation-pill,
.hb-reservation-pill--existing{
    display: inline-flex !important;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
    width: fit-content;
    min-height: 32px;
    margin: 0 !important;
    padding: 0 11px;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 13%, #E6DCCB) !important;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hb-modal-action) 5%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-soft-ink)) !important;
    font-size: .74rem;
    font-weight: 820;
}

.hb-reservation-summary{
    display: grid !important;
    gap: 12px;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}

.hb-reservation-dates{
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}

.hb-date-row{
    display: grid;
    gap: 5px;
    min-height: 68px;
    padding: 11px 12px !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 14px;
    background: #FFFFFF;
}

.hb-date-row span{
    color: var(--hb-modal-muted) !important;
    font-size: .68rem;
    font-weight: 820;
    letter-spacing: .06em;
    line-height: 1;
    text-transform: uppercase;
}

.hb-date-row strong{
    color: var(--hb-modal-ink) !important;
    font-size: .93rem;
    font-weight: 850;
    line-height: 1.15;
}

.hb-arrival-card{
    display: grid;
    gap: 10px;
    padding: 14px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 14%, #E6DCCB) !important;
    border-radius: 16px;
    background:
        linear-gradient(180deg, #FFFFFF, color-mix(in srgb, var(--hb-modal-action) 4%, #FFFDF8)) !important;
}

.hb-arrival-label{
    margin: 0 !important;
    color: var(--hb-modal-ink) !important;
    font-size: .9rem;
    font-weight: 850;
    line-height: 1.15;
}

.hb-arrival-copy{
    margin: -5px 0 0 !important;
    color: var(--hb-modal-muted) !important;
    font-size: .79rem;
    font-weight: 500;
    line-height: 1.35;
}

.hb-arrival-control{
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
}

.hb-arrival-input{
    width: 100%;
    height: 42px;
    padding: 0 12px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 16%, #D8CDBB) !important;
    border-radius: 12px !important;
    background: #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
    font-size: .96rem !important;
    font-weight: 780;
    outline: none;
    box-shadow: none !important;
}

.hb-arrival-input:focus{
    border-color: color-mix(in srgb, var(--hb-modal-action) 42%, #D8CDBB) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--hb-modal-action) 10%, transparent) !important;
}

.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm{
    min-height: 42px !important;
    border: 0 !important;
    border-radius: 12px !important;
    background: var(--hb-modal-action) !important;
    color: var(--hb-modal-action-text) !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--hb-modal-action) 76%, transparent) !important;
    font-weight: 850 !important;
}

.hb-arrival-now{
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px !important;
    font-size: .82rem;
}

.hb-arrival-now:hover,
.hb-swal-arrival .hb-swal-confirm:hover{
    filter: brightness(1.04);
}

.hb-swal .swal2-actions{
    width: 100% !important;
    gap: 8px !important;
    margin: 0 !important;
    padding: 0 18px 18px !important;
}

.swal2-popup.hb-swal-arrival .swal2-actions{
    display: grid !important;
    grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr);
}

.swal2-popup.hb-swal-client .swal2-actions{
    justify-content: stretch !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel,
.hb-swal-arrival .hb-swal-confirm{
    min-height: 42px !important;
    margin: 0 !important;
    padding: 0 14px !important;
    border-radius: 12px !important;
    font-size: .84rem !important;
    letter-spacing: 0 !important;
}

.hb-swal-arrival .hb-swal-cancel{
    order: 1;
}

.hb-swal-arrival .hb-swal-confirm{
    order: 2;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    border: 1px solid var(--hb-modal-line) !important;
    background: #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
    box-shadow: none !important;
}

.hb-swal-client .hb-swal-cancel{
    width: 100% !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:hover{
    background: var(--hb-modal-soft) !important;
}

@media (max-width: 640px){
    .swal2-container.hb-swal-sheet-container{
        align-items: flex-end !important;
        padding: 10px !important;
    }

    .swal2-popup.hb-swal-client,
    .swal2-popup.hb-swal-arrival{
        width: 100% !important;
        max-width: none !important;
        border-radius: 18px 18px 12px 12px !important;
    }

    .hb-client-choice__head,
    .hb-arrival-head{
        padding: 18px 16px 14px;
    }

    .hb-client-choice__options,
    .hb-arrival-context{
        padding: 14px;
    }

    .hb-client-choice__options,
    .hb-reservation-dates,
    .swal2-popup.hb-swal-arrival .swal2-actions{
        grid-template-columns: 1fr;
    }

    .hb-client-option{
        min-height: 132px;
    }

    .hb-swal .swal2-actions{
        padding: 0 14px calc(14px + env(safe-area-inset-bottom)) !important;
    }
}
</style>

<style id="hb-reservation-flow-modal-finesse">
/* Ajuste fino: menos saturacion de marca, mas paleta funcional y microinteracciones suaves. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-surface: oklch(98.4% .010 83);
    --hb-modal-panel: oklch(96.9% .014 78);
    --hb-modal-ink: oklch(24% .025 252);
    --hb-modal-soft-ink: oklch(38% .028 250);
    --hb-modal-muted: oklch(52% .026 252);
    --hb-modal-line: color-mix(in oklch, var(--brand-primary, #1B2746) 10%, oklch(88% .020 83));
    --hb-modal-brand-quiet: color-mix(in oklch, var(--brand-primary, #1B2746) 30%, oklch(35% .030 248));
    --hb-modal-coral: oklch(58% .102 33);
    --hb-modal-sage: oklch(56% .075 165);
    --hb-modal-blue: oklch(55% .074 246);
    --hb-modal-amber: oklch(66% .096 78);
    --hb-modal-action: oklch(30% .030 252);
    --hb-modal-action-text: oklch(97% .010 83);
    --hb-modal-hi: oklch(99% .006 83);
    background:
        radial-gradient(520px 180px at 84% 0%, color-mix(in oklch, var(--hb-modal-blue) 13%, transparent), transparent 70%),
        radial-gradient(360px 150px at 4% 0%, color-mix(in oklch, var(--hb-modal-amber) 11%, transparent), transparent 72%),
        linear-gradient(180deg, var(--hb-modal-surface), oklch(97.4% .012 78)) !important;
    box-shadow:
        0 26px 70px -42px rgba(15, 23, 42, .56),
        0 1px 0 color-mix(in oklch, var(--hb-modal-hi) 82%, var(--hb-modal-panel)) inset !important;
    animation: hbReserveModalIn .19s cubic-bezier(.22, 1, .36, 1) both;
}

.swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
.swal2-container.hb-swal-sheet-container.swal2-noanimation{
    background:
        radial-gradient(720px 320px at 54% -10%, color-mix(in oklch, var(--brand-accent, #BD9441) 10%, transparent), transparent 68%),
        rgba(15, 23, 42, .34) !important;
    backdrop-filter: blur(5px) !important;
}

.hb-client-choice__head,
.hb-arrival-head{
    background:
        linear-gradient(135deg, color-mix(in oklch, var(--hb-modal-blue) 5%, var(--hb-modal-surface)), transparent 58%),
        linear-gradient(180deg, var(--hb-modal-surface), var(--hb-modal-panel)) !important;
}

.hb-client-choice__mark,
.hb-arrival-mark{
    background:
        linear-gradient(135deg, color-mix(in oklch, var(--hb-modal-blue) 82%, oklch(30% .028 252)), color-mix(in oklch, var(--hb-modal-sage) 68%, oklch(28% .028 252))) !important;
    box-shadow: 0 13px 24px -20px color-mix(in oklch, var(--hb-modal-blue) 70%, transparent) !important;
}

.hb-client-choice__eyebrow{
    color: color-mix(in oklch, var(--hb-modal-blue) 64%, var(--hb-modal-soft-ink)) !important;
}

.hb-client-option{
    background:
        radial-gradient(240px 120px at 100% 0%, color-mix(in oklch, var(--hb-modal-action) 7%, transparent), transparent 70%),
        color-mix(in oklch, var(--hb-modal-surface) 92%, var(--hb-modal-hi)) !important;
    box-shadow:
        0 1px 0 color-mix(in oklch, var(--hb-modal-hi) 84%, var(--hb-modal-panel)) inset,
        0 14px 30px -31px rgba(15, 23, 42, .42) !important;
    transition:
        transform .18s cubic-bezier(.22, 1, .36, 1),
        border-color .18s ease,
        background .18s ease,
        box-shadow .18s ease,
        color .18s ease !important;
}

.hb-client-option::before{
    background:
        linear-gradient(180deg, color-mix(in oklch, var(--hb-modal-action) 64%, var(--hb-modal-panel)), color-mix(in oklch, var(--hb-modal-action) 36%, var(--hb-modal-panel)));
    opacity: .72;
}

.hb-client-option--new,
.hb-reservation-pill--new{
    --hb-modal-action: var(--hb-modal-coral);
}

.hb-client-option--existing,
.hb-reservation-pill--existing{
    --hb-modal-action: var(--hb-modal-sage);
}

.hb-client-option:hover,
.hb-client-option:focus-visible{
    transform: translateY(-1px);
    border-color: color-mix(in oklch, var(--hb-modal-action) 28%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(260px 130px at 100% 0%, color-mix(in oklch, var(--hb-modal-action) 10%, transparent), transparent 72%),
        color-mix(in oklch, var(--hb-modal-action) 4%, var(--hb-modal-surface)) !important;
    box-shadow:
        0 1px 0 color-mix(in oklch, var(--hb-modal-hi) 84%, var(--hb-modal-panel)) inset,
        0 18px 34px -31px color-mix(in oklch, var(--hb-modal-action) 52%, transparent) !important;
}

.hb-client-option:active{
    transform: translateY(0) scale(.995);
}

.hb-client-option__icon{
    background: color-mix(in oklch, var(--hb-modal-action) 8%, var(--hb-modal-surface)) !important;
    transition: transform .18s cubic-bezier(.22, 1, .36, 1), background .18s ease, color .18s ease;
}

.hb-client-option:hover .hb-client-option__icon,
.hb-client-option:focus-visible .hb-client-option__icon{
    transform: rotate(-2deg) scale(1.035);
    background: color-mix(in oklch, var(--hb-modal-action) 13%, var(--hb-modal-surface)) !important;
}

.hb-client-option__tag,
.hb-reservation-pill,
.hb-reservation-pill--existing{
    background: color-mix(in oklch, var(--hb-modal-action) 7%, var(--hb-modal-surface)) !important;
    border-color: color-mix(in oklch, var(--hb-modal-action) 16%, var(--hb-modal-line)) !important;
    color: color-mix(in oklch, var(--hb-modal-action) 68%, var(--hb-modal-soft-ink)) !important;
}

.hb-date-row{
    background: color-mix(in oklch, var(--hb-modal-surface) 88%, var(--hb-modal-hi)) !important;
    transition: border-color .18s ease, background .18s ease;
}

.hb-date-row:first-child{
    border-color: color-mix(in oklch, var(--hb-modal-blue) 22%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(160px 80px at 100% 0%, color-mix(in oklch, var(--hb-modal-blue) 8%, transparent), transparent 70%),
        color-mix(in oklch, var(--hb-modal-surface) 90%, var(--hb-modal-hi)) !important;
}

.hb-date-row:nth-child(2){
    border-color: color-mix(in oklch, var(--hb-modal-amber) 25%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(160px 80px at 100% 0%, color-mix(in oklch, var(--hb-modal-amber) 9%, transparent), transparent 70%),
        color-mix(in oklch, var(--hb-modal-surface) 90%, var(--hb-modal-hi)) !important;
}

.hb-arrival-card{
    background:
        radial-gradient(230px 120px at 100% 0%, color-mix(in oklch, var(--hb-modal-sage) 8%, transparent), transparent 76%),
        color-mix(in oklch, var(--hb-modal-surface) 90%, var(--hb-modal-hi)) !important;
    border-color: color-mix(in oklch, var(--hb-modal-sage) 18%, var(--hb-modal-line)) !important;
}

.hb-arrival-input{
    background: color-mix(in oklch, var(--hb-modal-hi) 80%, var(--hb-modal-surface)) !important;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.hb-arrival-input:hover{
    border-color: color-mix(in oklch, var(--hb-modal-blue) 24%, var(--hb-modal-line)) !important;
}

.hb-arrival-input:focus{
    border-color: color-mix(in oklch, var(--hb-modal-blue) 46%, var(--hb-modal-line)) !important;
    box-shadow: 0 0 0 4px color-mix(in oklch, var(--hb-modal-blue) 12%, transparent) !important;
}

.hb-arrival-now{
    --hb-modal-action: var(--hb-modal-blue);
}

.hb-arrival-later{
    width: 100%;
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    margin-top: 2px;
    padding: 10px 11px;
    border: 1px solid color-mix(in oklch, var(--hb-modal-amber) 26%, var(--hb-modal-line)) !important;
    border-radius: 13px;
    background:
        radial-gradient(180px 90px at 100% 0%, color-mix(in oklch, var(--hb-modal-amber) 9%, transparent), transparent 70%),
        color-mix(in oklch, var(--hb-modal-surface) 92%, var(--hb-modal-hi)) !important;
    color: var(--hb-modal-ink);
    text-align: left;
    cursor: pointer;
    transition: transform .18s cubic-bezier(.22, 1, .36, 1), border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.hb-arrival-later__icon{
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: color-mix(in oklch, var(--hb-modal-amber) 11%, var(--hb-modal-surface));
    color: color-mix(in oklch, var(--hb-modal-amber) 70%, var(--hb-modal-soft-ink));
}

.hb-arrival-later strong,
.hb-arrival-later small{
    display: block;
}

.hb-arrival-later strong{
    font-size: .84rem;
    font-weight: 900;
    line-height: 1.15;
}

.hb-arrival-later small{
    margin-top: 2px;
    color: var(--hb-modal-muted);
    font-size: .74rem;
    font-weight: 650;
    line-height: 1.25;
}

.hb-arrival-later:hover,
.hb-arrival-later:focus-visible{
    transform: translateY(-1px);
    border-color: color-mix(in oklch, var(--hb-modal-amber) 42%, var(--hb-modal-line)) !important;
    box-shadow: 0 14px 26px -24px color-mix(in oklch, var(--hb-modal-amber) 42%, transparent);
    outline: none;
}

.hb-swal-arrival .hb-swal-confirm{
    --hb-modal-action: color-mix(in oklch, var(--hb-modal-brand-quiet) 72%, var(--hb-modal-blue));
}

.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm,
.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    transition:
        transform .18s cubic-bezier(.22, 1, .36, 1),
        background .18s ease,
        border-color .18s ease,
        color .18s ease,
        box-shadow .18s ease,
        filter .18s ease !important;
}

.hb-arrival-now:hover,
.hb-arrival-now:focus-visible,
.hb-swal-arrival .hb-swal-confirm:hover,
.hb-swal-arrival .hb-swal-confirm:focus-visible{
    transform: translateY(-1px);
    filter: none;
    background: color-mix(in oklch, var(--hb-modal-action) 86%, oklch(24% .026 252)) !important;
    box-shadow: 0 16px 26px -20px color-mix(in oklch, var(--hb-modal-action) 62%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible{
    transform: translateY(-1px);
    border-color: color-mix(in oklch, var(--hb-modal-amber) 26%, var(--hb-modal-line)) !important;
    background: color-mix(in oklch, var(--hb-modal-amber) 6%, var(--hb-modal-surface)) !important;
}

.hb-arrival-now:active,
.hb-swal-arrival .hb-swal-confirm:active,
.hb-swal-client .hb-swal-cancel:active,
.hb-swal-arrival .hb-swal-cancel:active{
    transform: translateY(0) scale(.99);
}

.swal2-popup.hb-swal-client.swal2-hide,
.swal2-popup.hb-swal-arrival.swal2-hide{
    animation: hbReserveModalOut .13s ease-in both !important;
}

.swal2-container.hb-swal-sheet-container.swal2-backdrop-hide{
    background: rgba(15, 23, 42, 0) !important;
    backdrop-filter: blur(0) !important;
    transition: background .13s ease, backdrop-filter .13s ease !important;
}

/* Capa final: modal mas blanco, con acentos universales y acciones mas visibles. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-danger: oklch(58% .160 27);
    --hb-modal-danger-dark: oklch(47% .160 27);
    --hb-modal-coral: oklch(63% .145 35);
    --hb-modal-sage: oklch(60% .100 166);
    --hb-modal-blue: oklch(57% .094 235);
    --hb-modal-amber: oklch(71% .120 78);
    background:
        radial-gradient(520px 180px at 105% -8%, color-mix(in oklch, var(--hb-modal-coral) 15%, transparent), transparent 72%),
        radial-gradient(420px 150px at -8% 0%, color-mix(in oklch, var(--hb-modal-sage) 13%, transparent), transparent 74%),
        #fff !important;
    border-color: color-mix(in oklch, var(--brand-accent, #BD9441) 18%, oklch(89% .018 83)) !important;
    box-shadow:
        0 34px 78px -46px rgba(15, 23, 42, .62),
        0 1px 0 rgba(255,255,255,.96) inset !important;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before{
    content: "";
    display: block;
    height: 7px;
    background: linear-gradient(90deg,
        var(--hb-modal-danger),
        var(--hb-modal-coral) 34%,
        var(--hb-modal-amber) 66%,
        var(--hb-modal-sage));
}

.hb-swal .swal2-close{
    top: 14px !important;
    right: 14px !important;
    width: 36px !important;
    height: 36px !important;
    border: 1px solid color-mix(in oklch, var(--hb-modal-danger) 18%, #fff) !important;
    background: linear-gradient(180deg, var(--hb-modal-danger), var(--hb-modal-danger-dark)) !important;
    color: #fff !important;
    box-shadow: 0 14px 24px -18px color-mix(in oklch, var(--hb-modal-danger) 72%, transparent) !important;
}

.hb-swal .swal2-close:hover,
.hb-swal .swal2-close:focus-visible{
    transform: translateY(-1px) scale(1.03);
    background: linear-gradient(180deg, color-mix(in oklch, var(--hb-modal-danger) 92%, #fff), var(--hb-modal-danger-dark)) !important;
    color: #fff !important;
    outline: 3px solid color-mix(in oklch, var(--hb-modal-danger) 18%, transparent) !important;
}

.hb-client-choice__head,
.hb-arrival-head{
    position: relative;
    border-bottom-color: color-mix(in oklch, var(--brand-accent, #BD9441) 16%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(420px 160px at 92% -10%, color-mix(in oklch, var(--hb-modal-coral) 12%, transparent), transparent 70%),
        radial-gradient(320px 140px at 0% 0%, color-mix(in oklch, var(--hb-modal-sage) 11%, transparent), transparent 74%),
        linear-gradient(180deg, #fff, color-mix(in oklch, var(--hb-modal-amber) 5%, #fff)) !important;
}

.hb-client-choice__head::after,
.hb-arrival-head::after{
    content: "";
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: -1px;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg,
        color-mix(in oklch, var(--hb-modal-coral) 78%, transparent),
        color-mix(in oklch, var(--hb-modal-amber) 60%, transparent),
        color-mix(in oklch, var(--hb-modal-sage) 70%, transparent));
}

.hb-client-choice__mark{
    background: linear-gradient(135deg, var(--hb-modal-coral), color-mix(in oklch, var(--hb-modal-danger) 64%, var(--hb-modal-coral))) !important;
}

.hb-arrival-mark{
    background: linear-gradient(135deg, var(--hb-modal-blue), color-mix(in oklch, var(--hb-modal-sage) 74%, var(--hb-modal-blue))) !important;
}

.hb-client-option--new,
.hb-reservation-pill--new{
    --hb-modal-action: var(--hb-modal-coral);
}

.hb-client-option--existing,
.hb-reservation-pill--existing{
    --hb-modal-action: var(--hb-modal-sage);
}

.hb-client-option,
.hb-date-row,
.hb-arrival-card{
    background:
        radial-gradient(240px 120px at 100% 0%, color-mix(in oklch, var(--hb-modal-action, var(--hb-modal-blue)) 8%, transparent), transparent 72%),
        #fff !important;
}

.hb-client-option{
    border-color: color-mix(in oklch, var(--hb-modal-action) 20%, var(--hb-modal-line)) !important;
}

.hb-client-option__icon{
    background: color-mix(in oklch, var(--hb-modal-action) 13%, #fff) !important;
    color: color-mix(in oklch, var(--hb-modal-action) 82%, var(--hb-modal-ink)) !important;
}

.hb-arrival-card{
    --hb-modal-action: var(--hb-modal-blue);
    border-color: color-mix(in oklch, var(--hb-modal-blue) 22%, var(--hb-modal-line)) !important;
}

.hb-arrival-later{
    background:
        radial-gradient(200px 100px at 100% 0%, color-mix(in oklch, var(--hb-modal-amber) 14%, transparent), transparent 72%),
        color-mix(in oklch, var(--hb-modal-amber) 4%, #fff) !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    border-color: color-mix(in oklch, var(--hb-modal-danger) 30%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in oklch, var(--hb-modal-danger) 9%, #fff), color-mix(in oklch, var(--hb-modal-danger) 5%, #fff)) !important;
    color: color-mix(in oklch, var(--hb-modal-danger-dark) 82%, var(--hb-modal-ink)) !important;
    box-shadow: 0 12px 24px -22px color-mix(in oklch, var(--hb-modal-danger) 46%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible{
    border-color: color-mix(in oklch, var(--hb-modal-danger) 46%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in oklch, var(--hb-modal-danger) 16%, #fff), color-mix(in oklch, var(--hb-modal-danger) 8%, #fff)) !important;
}

/* Propuesta sobria: menos color, mas blanco calido y acentos silenciosos. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-surface: #fffdfa;
    --hb-modal-paper: #ffffff;
    --hb-modal-ink: #202a32;
    --hb-modal-soft-ink: #4d5963;
    --hb-modal-muted: #6f7882;
    --hb-modal-line: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #e7ded2);
    --hb-modal-clay: #9b6a5f;
    --hb-modal-clay-deep: #745048;
    --hb-modal-moss: #657a70;
    --hb-modal-moss-deep: #4f6259;
    --hb-modal-blue: #65798d;
    --hb-modal-blue-deep: #4e6173;
    --hb-modal-amber: #a88c5a;
    --hb-modal-action: var(--hb-modal-blue);
    --hb-modal-action-text: #ffffff;
    background:
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,253,249,.98)),
        var(--hb-modal-surface) !important;
    border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 9%, #e7ded2) !important;
    box-shadow:
        0 30px 70px -46px rgba(20, 28, 36, .56),
        0 1px 0 rgba(255,255,255,.95) inset !important;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before{
    height: 4px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--brand-primary, #1B2746) 24%, #e8ded0),
        color-mix(in srgb, var(--brand-accent, #BD9441) 24%, #eee6da)) !important;
}

.hb-swal .swal2-close{
    border-color: color-mix(in srgb, var(--hb-modal-clay) 22%, #e9ded3) !important;
    background: rgba(255,255,255,.94) !important;
    color: var(--hb-modal-clay-deep) !important;
    box-shadow: 0 12px 24px -20px rgba(116, 80, 72, .42) !important;
}

.hb-swal .swal2-close:hover,
.hb-swal .swal2-close:focus-visible{
    background: color-mix(in srgb, var(--hb-modal-clay) 8%, #fff) !important;
    color: var(--hb-modal-clay-deep) !important;
    outline: 3px solid color-mix(in srgb, var(--hb-modal-clay) 14%, transparent) !important;
}

.hb-client-choice__head,
.hb-arrival-head{
    border-bottom-color: var(--hb-modal-line) !important;
    background:
        linear-gradient(180deg, #ffffff, color-mix(in srgb, var(--brand-accent, #BD9441) 4%, #fffdf8)) !important;
}

.hb-client-choice__head::after,
.hb-arrival-head::after{
    height: 1px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--brand-primary, #1B2746) 18%, transparent),
        color-mix(in srgb, var(--brand-accent, #BD9441) 24%, transparent)) !important;
}

.hb-client-choice__mark,
.hb-arrival-mark{
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 18%, var(--hb-modal-line));
    background: color-mix(in srgb, var(--hb-modal-action) 9%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-client-choice__mark{
    --hb-modal-action: var(--hb-modal-clay);
}

.hb-arrival-mark{
    --hb-modal-action: var(--hb-modal-blue);
}

.hb-client-choice__eyebrow{
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 48%, var(--hb-modal-muted)) !important;
}

.hb-client-option--new,
.hb-reservation-pill--new{
    --hb-modal-action: var(--hb-modal-clay);
}

.hb-client-option--existing,
.hb-reservation-pill--existing{
    --hb-modal-action: var(--hb-modal-moss);
}

.hb-client-option,
.hb-date-row,
.hb-arrival-card{
    background: var(--hb-modal-paper) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action, var(--hb-modal-blue)) 14%, var(--hb-modal-line)) !important;
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 16px 34px -34px rgba(24, 32, 40, .38) !important;
}

.hb-client-option::before{
    background: color-mix(in srgb, var(--hb-modal-action) 44%, #d9cfc2) !important;
    opacity: .72;
}

.hb-client-option:hover,
.hb-client-option:focus-visible{
    background: color-mix(in srgb, var(--hb-modal-action) 3%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 24%, var(--hb-modal-line)) !important;
    box-shadow: 0 20px 40px -34px color-mix(in srgb, var(--hb-modal-action) 28%, transparent) !important;
}

.hb-client-option__icon,
.hb-client-option__tag,
.hb-reservation-pill{
    background: color-mix(in srgb, var(--hb-modal-action) 6%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 12%, var(--hb-modal-line)) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 68%, var(--hb-modal-soft-ink)) !important;
}

.hb-arrival-card{
    --hb-modal-action: var(--hb-modal-blue);
}

.hb-arrival-later{
    border-color: color-mix(in srgb, var(--hb-modal-amber) 18%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-amber) 4%, #fff) !important;
}

.hb-arrival-later__icon{
    background: color-mix(in srgb, var(--hb-modal-amber) 8%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-amber) 70%, var(--hb-modal-soft-ink)) !important;
}

.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm{
    background: linear-gradient(135deg, var(--hb-modal-blue), var(--hb-modal-blue-deep)) !important;
    color: #fff !important;
    box-shadow: 0 14px 24px -20px rgba(78, 97, 115, .5) !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    border-color: color-mix(in srgb, var(--hb-modal-clay) 18%, var(--hb-modal-line)) !important;
    background: #fff !important;
    color: color-mix(in srgb, var(--hb-modal-clay-deep) 70%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible{
    border-color: color-mix(in srgb, var(--hb-modal-clay) 28%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-clay) 5%, #fff) !important;
}

/* Toques cromaticos moderados: accion clara sin saturar el modal. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-clay: #aa6958;
    --hb-modal-clay-deep: #7d4b40;
    --hb-modal-moss: #4f806f;
    --hb-modal-moss-deep: #3d6557;
    --hb-modal-blue: #4e7398;
    --hb-modal-blue-deep: #3f5f80;
    --hb-modal-amber: #aa7f3f;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before{
    height: 5px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--hb-modal-clay) 46%, #eadfd4),
        color-mix(in srgb, var(--hb-modal-blue) 38%, #e6edf2),
        color-mix(in srgb, var(--hb-modal-moss) 42%, #e5eee9)) !important;
}

.hb-client-option--new .hb-client-option__icon{
    background: color-mix(in srgb, var(--hb-modal-clay) 16%, #fff) !important;
    color: var(--hb-modal-clay-deep) !important;
}

.hb-client-option--existing .hb-client-option__icon{
    background: color-mix(in srgb, var(--hb-modal-moss) 16%, #fff) !important;
    color: var(--hb-modal-moss-deep) !important;
}

.hb-client-option--new .hb-client-option__cta,
.hb-client-option--existing .hb-client-option__cta{
    min-height: 38px;
    margin-top: 2px;
    padding: 10px 12px 0;
    border-top-color: color-mix(in srgb, var(--hb-modal-action) 16%, var(--hb-modal-line)) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 86%, var(--hb-modal-ink)) !important;
}

.hb-client-option__tag,
.hb-reservation-pill{
    background: color-mix(in srgb, var(--hb-modal-action) 10%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 18%, var(--hb-modal-line)) !important;
}

.hb-arrival-mark{
    background: color-mix(in srgb, var(--hb-modal-blue) 15%, #fff) !important;
    color: var(--hb-modal-blue-deep) !important;
}

.hb-arrival-now{
    background: linear-gradient(135deg, color-mix(in srgb, var(--hb-modal-blue) 92%, #fff), var(--hb-modal-blue-deep)) !important;
}

.hb-swal-arrival .hb-swal-confirm{
    background: linear-gradient(135deg, color-mix(in srgb, var(--brand-primary, #1B2746) 62%, var(--hb-modal-blue)), var(--hb-modal-blue-deep)) !important;
}

.hb-arrival-later{
    border-color: color-mix(in srgb, var(--hb-modal-amber) 32%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-amber) 8%, #fff), #fff) !important;
}

.hb-arrival-later__icon{
    background: color-mix(in srgb, var(--hb-modal-amber) 15%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-amber) 84%, var(--hb-modal-ink)) !important;
}

/* Estados de color claros: verde registrado, verde suave despues, cancelar rojo bajo. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-green: #2f8f62;
    --hb-modal-green-deep: #216b4a;
    --hb-modal-new-strong: #c46349;
    --hb-modal-new-deep: #934634;
    --hb-modal-cancel: #b95b57;
    --hb-modal-cancel-deep: #8f403d;
}

.hb-client-option--existing:hover,
.hb-client-option--existing:focus-visible{
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-green) 15%, #fff), #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-green) 48%, var(--hb-modal-line)) !important;
    box-shadow: 0 22px 42px -32px color-mix(in srgb, var(--hb-modal-green) 55%, transparent) !important;
}

.hb-client-option--existing:hover .hb-client-option__icon,
.hb-client-option--existing:focus-visible .hb-client-option__icon{
    background: linear-gradient(135deg, var(--hb-modal-green), var(--hb-modal-green-deep)) !important;
    color: #fff !important;
}

.hb-client-option--existing:hover .hb-client-option__tag,
.hb-client-option--existing:focus-visible .hb-client-option__tag{
    background: color-mix(in srgb, var(--hb-modal-green) 18%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-green) 34%, var(--hb-modal-line)) !important;
    color: var(--hb-modal-green-deep) !important;
}

.hb-client-option--existing:hover .hb-client-option__cta,
.hb-client-option--existing:focus-visible .hb-client-option__cta{
    color: var(--hb-modal-green-deep) !important;
}

.hb-client-option--new:hover,
.hb-client-option--new:focus-visible{
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-new-strong) 13%, #fff), #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-new-strong) 42%, var(--hb-modal-line)) !important;
    box-shadow: 0 22px 42px -32px color-mix(in srgb, var(--hb-modal-new-strong) 50%, transparent) !important;
}

.hb-client-option--new:hover .hb-client-option__icon,
.hb-client-option--new:focus-visible .hb-client-option__icon{
    background: linear-gradient(135deg, var(--hb-modal-new-strong), var(--hb-modal-new-deep)) !important;
    color: #fff !important;
}

.hb-arrival-later{
    border-color: color-mix(in srgb, var(--hb-modal-green) 30%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(190px 92px at 100% 0%, color-mix(in srgb, var(--hb-modal-green) 10%, transparent), transparent 72%),
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-green) 7%, #fff), #fff) !important;
}

.hb-arrival-later__icon{
    background: color-mix(in srgb, var(--hb-modal-green) 14%, #fff) !important;
    color: var(--hb-modal-green-deep) !important;
}

.hb-arrival-later:hover,
.hb-arrival-later:focus-visible{
    border-color: color-mix(in srgb, var(--hb-modal-green) 44%, var(--hb-modal-line)) !important;
    box-shadow: 0 14px 26px -24px color-mix(in srgb, var(--hb-modal-green) 45%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    border-color: color-mix(in srgb, var(--hb-modal-cancel) 30%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-cancel) 12%, #fff), color-mix(in srgb, var(--hb-modal-cancel) 6%, #fff)) !important;
    color: var(--hb-modal-cancel-deep) !important;
    box-shadow: 0 14px 22px -22px color-mix(in srgb, var(--hb-modal-cancel) 54%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible{
    border-color: color-mix(in srgb, var(--hb-modal-cancel) 44%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-cancel) 17%, #fff), color-mix(in srgb, var(--hb-modal-cancel) 9%, #fff)) !important;
    color: var(--hb-modal-cancel-deep) !important;
    box-shadow: 0 16px 26px -22px color-mix(in srgb, var(--hb-modal-cancel) 62%, transparent) !important;
}

/* Redisenio minimal: overlay oscuro sin difuminado y decisiones rapidas. */
.swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
.swal2-container.hb-swal-sheet-container.swal2-noanimation{
    background: rgba(8, 13, 20, .58) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

.swal2-container.hb-swal-sheet-container.swal2-backdrop-hide{
    background: rgba(8, 13, 20, 0) !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival{
    --hb-modal-surface: #fbfaf7;
    --hb-modal-paper: #ffffff;
    --hb-modal-ink: #1f2933;
    --hb-modal-soft-ink: #47525d;
    --hb-modal-muted: #707a84;
    --hb-modal-line: #e6ded2;
    --hb-modal-action: color-mix(in srgb, var(--brand-primary, #1B2746) 52%, #45515d);
    --hb-modal-existing: #536b61;
    --hb-modal-new: #8b675c;
    --hb-modal-danger: #9c5d58;
    width: min(620px, calc(100vw - 32px)) !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 16px !important;
    background: var(--hb-modal-surface) !important;
    color: var(--hb-modal-ink) !important;
    box-shadow: 0 28px 70px -44px rgba(8, 13, 20, .72) !important;
    animation: hbReserveModalIn .16s cubic-bezier(.22, 1, .36, 1) both;
}

.swal2-popup.hb-swal-arrival{
    width: min(500px, calc(100vw - 32px)) !important;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before,
.hb-client-choice__head::after,
.hb-arrival-head::after,
.hb-client-option::before{
    display: none !important;
}

.hb-swal .swal2-close{
    top: 14px !important;
    right: 14px !important;
    width: 32px !important;
    height: 32px !important;
    border: 1px solid var(--hb-modal-line) !important;
    background: #ffffff !important;
    color: #5d6670 !important;
    box-shadow: none !important;
}

.hb-swal .swal2-close:hover,
.hb-swal .swal2-close:focus-visible{
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--hb-modal-danger) 7%, #fff) !important;
    color: var(--hb-modal-danger) !important;
    outline: 3px solid color-mix(in srgb, var(--hb-modal-danger) 14%, transparent) !important;
}

.hb-client-choice__head,
.hb-arrival-head{
    grid-template-columns: 34px minmax(0, 1fr) !important;
    gap: 11px !important;
    padding: 20px 22px 16px !important;
    border-bottom: 1px solid var(--hb-modal-line) !important;
    background: var(--hb-modal-surface) !important;
}

.hb-client-choice__mark,
.hb-arrival-mark{
    width: 34px !important;
    height: 34px !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 10px !important;
    background: #ffffff !important;
    color: var(--hb-modal-action) !important;
    box-shadow: none !important;
}

.hb-client-choice__eyebrow{
    margin-bottom: 5px !important;
    color: var(--hb-modal-muted) !important;
    font-size: .66rem !important;
    font-weight: 760 !important;
    letter-spacing: .06em !important;
}

.hb-client-choice h3,
.hb-arrival-head h3{
    color: var(--hb-modal-ink) !important;
    font-size: 1.12rem !important;
    font-weight: 720 !important;
    line-height: 1.16 !important;
}

.hb-client-choice__head p,
.hb-arrival-head p{
    margin-top: 6px !important;
    color: var(--hb-modal-soft-ink) !important;
    font-size: .84rem !important;
    font-weight: 440 !important;
    line-height: 1.42 !important;
}

.hb-client-choice__options{
    gap: 10px !important;
    padding: 14px 16px 16px !important;
}

.hb-client-option{
    min-height: 124px !important;
    gap: 9px !important;
    padding: 14px !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 13px !important;
    background: var(--hb-modal-paper) !important;
    box-shadow: none !important;
}

.hb-client-option--new{
    --hb-modal-action: var(--hb-modal-new);
}

.hb-client-option--existing{
    --hb-modal-action: var(--hb-modal-existing);
}

.hb-client-option:hover,
.hb-client-option:focus-visible{
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--hb-modal-action) 34%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-action) 4%, #fff) !important;
    box-shadow: 0 18px 34px -32px color-mix(in srgb, var(--hb-modal-action) 38%, transparent) !important;
}

.hb-client-option__icon{
    width: 34px !important;
    height: 34px !important;
    border-radius: 10px !important;
    background: color-mix(in srgb, var(--hb-modal-action) 9%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 78%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-client-option__tag{
    display: none !important;
}

.hb-client-option__body strong{
    color: var(--hb-modal-ink) !important;
    font-size: .98rem !important;
    font-weight: 720 !important;
}

.hb-client-option__body span{
    color: var(--hb-modal-muted) !important;
    font-size: .8rem !important;
    font-weight: 430 !important;
}

.hb-client-option__cta{
    justify-content: flex-start !important;
    gap: 7px !important;
    min-height: 24px !important;
    padding-top: 2px !important;
    border-top: 0 !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-ink)) !important;
    font-size: .76rem !important;
    font-weight: 720 !important;
}

.hb-arrival-context{
    gap: 11px !important;
    padding: 14px 16px 16px !important;
}

.hb-reservation-pill,
.hb-reservation-pill--existing,
.hb-reservation-pill--new{
    min-height: 28px !important;
    border-color: var(--hb-modal-line) !important;
    background: #ffffff !important;
    color: var(--hb-modal-soft-ink) !important;
    font-size: .72rem !important;
    font-weight: 680 !important;
}

.hb-reservation-dates{
    gap: 8px !important;
}

.hb-date-row,
.hb-arrival-card{
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    box-shadow: none !important;
}

.hb-date-row{
    min-height: 62px !important;
    padding: 10px 11px !important;
}

.hb-date-row span{
    color: var(--hb-modal-muted) !important;
    font-size: .64rem !important;
    font-weight: 720 !important;
}

.hb-date-row strong{
    color: var(--hb-modal-ink) !important;
    font-size: .9rem !important;
    font-weight: 720 !important;
}

.hb-arrival-card{
    gap: 9px !important;
    padding: 13px !important;
}

.hb-arrival-label{
    color: var(--hb-modal-ink) !important;
    font-size: .9rem !important;
    font-weight: 720 !important;
}

.hb-arrival-copy{
    margin-top: -4px !important;
    color: var(--hb-modal-muted) !important;
    font-size: .78rem !important;
    font-weight: 430 !important;
}

.hb-arrival-input{
    height: 40px !important;
    border-color: var(--hb-modal-line) !important;
    border-radius: 10px !important;
    background: #ffffff !important;
    color: var(--hb-modal-ink) !important;
    font-size: .94rem !important;
    font-weight: 680 !important;
}

.hb-arrival-input:hover{
    border-color: color-mix(in srgb, var(--hb-modal-action) 22%, var(--hb-modal-line)) !important;
}

.hb-arrival-input:focus{
    border-color: color-mix(in srgb, var(--hb-modal-action) 48%, var(--hb-modal-line)) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--hb-modal-action) 12%, transparent) !important;
}

.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm{
    min-height: 40px !important;
    border-radius: 10px !important;
    background: var(--hb-modal-action) !important;
    color: #ffffff !important;
    box-shadow: none !important;
}

.hb-arrival-later{
    grid-template-columns: 30px minmax(0, 1fr) !important;
    gap: 9px !important;
    padding: 9px 10px !important;
    border-color: var(--hb-modal-line) !important;
    border-radius: 11px !important;
    background: #ffffff !important;
    box-shadow: none !important;
}

.hb-arrival-later__icon{
    width: 30px !important;
    height: 30px !important;
    border-radius: 9px !important;
    background: color-mix(in srgb, var(--hb-modal-existing) 9%, #fff) !important;
    color: var(--hb-modal-existing) !important;
}

.hb-arrival-later strong{
    color: var(--hb-modal-ink) !important;
    font-size: .82rem !important;
    font-weight: 720 !important;
}

.hb-arrival-later small{
    color: var(--hb-modal-muted) !important;
    font-size: .72rem !important;
    font-weight: 430 !important;
}

.hb-arrival-later:hover,
.hb-arrival-later:focus-visible{
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--hb-modal-existing) 30%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-existing) 4%, #fff) !important;
    box-shadow: none !important;
}

.hb-swal .swal2-actions{
    gap: 8px !important;
    padding: 0 16px 16px !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel,
.hb-swal-arrival .hb-swal-confirm{
    min-height: 40px !important;
    border-radius: 10px !important;
    font-size: .82rem !important;
    font-weight: 700 !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel{
    border-color: color-mix(in srgb, var(--hb-modal-danger) 24%, var(--hb-modal-line)) !important;
    background: #ffffff !important;
    color: color-mix(in srgb, var(--hb-modal-danger) 82%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible{
    border-color: color-mix(in srgb, var(--hb-modal-danger) 38%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-danger) 6%, #fff) !important;
    box-shadow: none !important;
}

@media (max-width: 640px){
    .swal2-container.hb-swal-sheet-container{
        padding: 12px !important;
    }

    .swal2-popup.hb-swal-client,
    .swal2-popup.hb-swal-arrival{
        border-radius: 16px !important;
    }

    .hb-client-choice__head,
    .hb-arrival-head{
        padding: 18px 16px 14px !important;
    }

    .hb-client-choice__options,
    .hb-arrival-context{
        padding: 12px !important;
    }
}

@keyframes hbReserveModalIn{
    from{
        opacity: 0;
        transform: translateY(8px) scale(.985);
    }
    to{
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes hbReserveModalOut{
    from{
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    to{
        opacity: 0;
        transform: translateY(8px) scale(.985);
    }
}

@media (prefers-reduced-motion: reduce){
    .swal2-popup.hb-swal-client,
    .swal2-popup.hb-swal-arrival,
    .hb-client-option,
    .hb-client-option__icon,
    .hb-arrival-now,
    .hb-swal-arrival .hb-swal-confirm,
    .hb-swal-client .hb-swal-cancel,
    .hb-swal-arrival .hb-swal-cancel{
        animation: none !important;
        transition-duration: .01ms !important;
    }
}

/* Nueva reserva: shell compartido con reservaciones, brand-aware y sin encimar pasos. */
.swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal{
    width: min(780px, calc(100vw - 32px)) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E8DFD1) !important;
    border-radius: 24px !important;
    background: #FBFAF7 !important;
    color: #172033 !important;
    box-shadow: 0 34px 78px -42px rgba(8, 13, 20, .70) !important;
}

.swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-html-container{
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

.hb-reserve-shell{
    --hb-reserve-brand: var(--brand-primary, #1B2746);
    --hb-reserve-brand-2: var(--brand-secondary, #0F172A);
    --hb-reserve-accent: var(--brand-accent, #BD9441);
    --hb-reserve-line: color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E8DFD1);
    --hb-reserve-surface: #FBFAF7;
    --hb-reserve-paper: #FFFEFB;
    --hb-reserve-ink: #172033;
    --hb-reserve-muted: #6B7686;
    --hb-reserve-new: color-mix(in srgb, var(--brand-accent, #BD9441) 72%, #B76B52);
    --hb-reserve-existing: #20A66B;
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    min-height: 482px;
    text-align: left;
    background: var(--hb-reserve-surface);
}

.hb-reserve-side{
    position: relative;
    isolation: isolate;
    overflow: hidden;
    display: grid;
    grid-template-rows: auto auto auto auto 1fr;
    align-content: start;
    padding: 26px 26px;
    color: #FFFEFB;
    background:
        radial-gradient(260px 210px at 100% 10%, rgba(255,255,255,.10), transparent 62%),
        linear-gradient(155deg, color-mix(in srgb, var(--hb-reserve-brand-2) 92%, #101827), color-mix(in srgb, var(--hb-reserve-brand) 78%, #1F2937));
}

.hb-reserve-side::after{
    content: '';
    position: absolute;
    top: -34px;
    right: -70px;
    width: 230px;
    height: 230px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hb-reserve-accent) 20%, transparent);
    opacity: .65;
    z-index: -1;
}

.hb-reserve-side__eyebrow,
.hb-reserve-main__eyebrow{
    display: block;
    margin: 0 0 12px;
    font-size: .70rem;
    font-weight: 900;
    letter-spacing: .18em;
    line-height: 1;
    text-transform: uppercase;
}

.hb-reserve-side__eyebrow{
    color: color-mix(in srgb, var(--hb-reserve-accent) 70%, #FFFEFB);
}

.hb-reserve-side h2{
    margin: 0;
    max-width: 9ch;
    color: #FFFEFB;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.72rem;
    font-weight: 780;
    line-height: 1.03;
    letter-spacing: 0;
}

.hb-reserve-side > p{
    margin: 12px 0 18px;
    max-width: 25ch;
    color: rgba(255,255,255,.78);
    font-size: .86rem;
    font-weight: 650;
    line-height: 1.45;
}

.hb-reserve-datebox{
    display: grid;
    gap: 0;
    padding: 8px 14px;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 16px;
    background: rgba(255,255,255,.08);
}

.hb-reserve-dateitem{
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-height: 50px;
}

.hb-reserve-dateitem + .hb-reserve-dateitem{
    border-top: 1px solid rgba(255,255,255,.12);
}

.hb-reserve-dateicon{
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    background: rgba(255,255,255,.12);
    color: color-mix(in srgb, var(--hb-reserve-accent) 62%, #FFFEFB);
    font-size: .78rem;
}

.hb-reserve-dateitem small{
    display: block;
    color: rgba(255,255,255,.54);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.hb-reserve-dateitem strong{
    display: block;
    margin-top: 2px;
    color: #FFFEFB;
    font-size: .88rem;
    font-weight: 900;
}

.hb-reserve-steps{
    display: grid;
    gap: 12px;
    margin: 18px 0 0;
    padding: 0;
    list-style: none;
}

.hb-reserve-steps li{
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,.62);
    font-size: .88rem;
    font-weight: 850;
}

.hb-reserve-steps li span{
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.20);
    color: rgba(255,255,255,.72);
    font-variant-numeric: tabular-nums;
}

.hb-reserve-steps li.is-active,
.hb-reserve-steps li.is-complete{
    color: #FFFEFB;
}

.hb-reserve-steps li.is-active span{
    border-color: color-mix(in srgb, var(--hb-reserve-accent) 76%, #FFFEFB);
    background: color-mix(in srgb, var(--hb-reserve-accent) 82%, #9B7236);
    color: #FFFEFB;
}

.hb-reserve-steps li.is-complete span{
    border-color: #20A66B;
    background: #20A66B;
    color: #FFFEFB;
}

.hb-reserve-main{
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 482px;
    padding: 32px 24px 0;
    background: #FFFEFB;
}

.hb-reserve-main__eyebrow{
    color: #8791A3;
    margin-bottom: 24px;
}

.hb-reserve-main h3{
    margin: 0;
    color: var(--hb-reserve-ink);
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.35rem;
    font-weight: 760;
    line-height: 1.18;
}

.hb-reserve-main > p{
    max-width: 49ch;
    margin: 7px 0 18px;
    color: var(--hb-reserve-muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.45;
}

.hb-reserve-choice-list{
    display: grid;
    gap: 10px;
}

.hb-reserve-choice{
    --hb-choice-color: var(--hb-reserve-accent);
    width: 100%;
    min-height: 92px;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 28px;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border: 1px solid var(--hb-reserve-line);
    border-radius: 14px;
    background: var(--hb-reserve-paper);
    color: var(--hb-reserve-ink);
    text-align: left;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.hb-reserve-choice--new{ --hb-choice-color: var(--hb-reserve-new); }
.hb-reserve-choice--existing{ --hb-choice-color: var(--hb-reserve-existing); }

.hb-reserve-choice:hover,
.hb-reserve-choice:focus-visible,
.hb-reserve-choice.is-selected{
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--hb-choice-color) 60%, var(--hb-reserve-line));
    background: color-mix(in srgb, var(--hb-choice-color) 10%, #FFFEFB);
    box-shadow: 0 18px 36px -32px color-mix(in srgb, var(--hb-choice-color) 54%, transparent);
    outline: none;
}

.hb-reserve-choice__icon{
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: color-mix(in srgb, var(--hb-choice-color) 78%, var(--hb-reserve-ink));
    background: color-mix(in srgb, var(--hb-choice-color) 10%, #FFFEFB);
}

.hb-reserve-choice__copy{
    display: grid;
    gap: 6px;
    min-width: 0;
}

.hb-reserve-choice__copy > span{
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.hb-reserve-choice__copy strong{
    color: var(--hb-reserve-ink);
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.08rem;
    font-weight: 760;
    line-height: 1.1;
}

.hb-reserve-choice__copy em{
    min-height: 20px;
    display: inline-flex;
    align-items: center;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--hb-choice-color) 12%, #FFFEFB);
    color: color-mix(in srgb, var(--hb-choice-color) 84%, var(--hb-reserve-ink));
    font-size: .62rem;
    font-style: normal;
    font-weight: 900;
}

.hb-reserve-choice__copy small{
    color: var(--hb-reserve-muted);
    font-size: .82rem;
    font-weight: 600;
    line-height: 1.35;
}

.hb-reserve-choice__check{
    width: 24px;
    height: 24px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 2px solid color-mix(in srgb, var(--hb-choice-color) 20%, #DCD4C8);
    color: transparent;
    background: #FFFEFB;
    font-size: .70rem;
}

.hb-reserve-choice.is-selected .hb-reserve-choice__check{
    border-color: var(--hb-choice-color);
    background: var(--hb-choice-color);
    color: #FFFEFB;
}

.hb-reserve-timechips{
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 3px 0 14px;
}

.hb-reserve-timechip{
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--hb-reserve-accent) 22%, var(--hb-reserve-line));
    border-radius: 999px;
    background: #FFFEFB;
    color: #4F5969;
    font-size: .84rem;
    font-weight: 850;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.hb-reserve-timechip:hover,
.hb-reserve-timechip:focus-visible,
.hb-reserve-timechip.is-active{
    transform: translateY(-1px);
    border-color: var(--hb-reserve-brand-2);
    background: var(--hb-reserve-brand-2);
    color: #FFFEFB;
    outline: none;
}

.hb-reserve-timefield{
    min-height: 60px;
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    padding: 0 18px;
    border: 1px solid var(--hb-reserve-line);
    border-radius: 12px;
    background: #FFFEFB;
}

.hb-reserve-timefield i{
    color: #A8B1BF;
}

.hb-reserve-timefield .hb-arrival-input{
    height: 58px !important;
    min-height: 58px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    color: var(--hb-reserve-brand-2) !important;
    font-size: 1.28rem !important;
    font-weight: 900 !important;
    box-shadow: none !important;
}

.hb-reserve-hint{
    margin: 10px 0 0 !important;
    display: flex;
    align-items: center;
    gap: 7px;
    color: #9AA4B5 !important;
    font-size: .78rem !important;
    font-weight: 650 !important;
}

.hb-reserve-validation{
    margin: 10px 0 0;
    padding: 10px 12px;
    border: 1px solid #F3B8B6;
    border-radius: 12px;
    background: #FFF3F2;
    color: #A4423E;
    font-size: .80rem;
    font-weight: 800;
}

.hb-reserve-validation.hidden{
    display: none;
}

.hb-reserve-footer{
    display: grid;
    grid-template-columns: minmax(150px, .88fr) minmax(190px, 1.22fr);
    gap: 12px;
    margin: auto -24px 0;
    padding: 16px 24px;
    border-top: 1px solid var(--hb-reserve-line);
    background: #FFFEFB;
}

.hb-reserve-btn{
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 12px;
    padding: 0 16px;
    font-size: .88rem;
    font-weight: 900;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.hb-reserve-btn:hover,
.hb-reserve-btn:focus-visible{
    transform: translateY(-1px);
    outline: none;
}

.hb-reserve-btn--ghost{
    border: 1px solid var(--hb-reserve-line);
    background: #FFFEFB;
    color: var(--hb-reserve-brand-2);
}

.hb-reserve-btn--primary{
    border: 0;
    background: var(--hb-reserve-brand-2);
    color: #FFFEFB;
    box-shadow: 0 16px 28px -22px color-mix(in srgb, var(--hb-reserve-brand-2) 70%, transparent);
}

/* Strip de fechas — solo visible en móvil cuando el sidebar está oculto */
.hb-reserve-mobile-dates,
.hb-reserve-mobile-intro{ display: none; }

@media (max-width: 760px){
    .hb-reserve-mobile-dates{
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 10px;
        font-size: .74rem;
        font-weight: 600;
        color: var(--hb-reserve-muted, #6B7686);
    }
    .hb-reserve-mobile-dates i{
        font-size: .62rem;
        opacity: .5;
    }

    /* ── Popup ── */
    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal{
        width: 100% !important;
        max-width: none !important;
        border-radius: 20px 20px 0 0 !important;
        box-shadow: 0 -8px 40px -8px rgba(8,13,20,.28) !important;
    }

    /* ── Shell: columna única, sidebar oculto ── */
    .hb-reserve-shell{
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .hb-reserve-side{
        display: none; /* ocultar sidebar completa en móvil */
    }

    /* ── Main compacto ── */
    .hb-reserve-main{
        min-height: 0;
        padding: 20px 18px 0;
    }

    .hb-reserve-main__eyebrow{
        margin-bottom: 16px;
        font-size: .68rem;
    }

    .hb-reserve-main h3{
        font-size: 1.18rem;
    }

    .hb-reserve-main > p{
        font-size: .84rem;
        margin-bottom: 14px;
    }

    /* ── Opciones de tipo de cliente: una columna ── */
    .hb-reserve-choice-list{
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .hb-reserve-choice{
        min-height: 72px;
        padding: 12px 14px;
        gap: 12px;
    }

    /* ── Footer pegado al fondo ── */
    .hb-reserve-footer{
        grid-template-columns: 1fr 1fr;
        margin-left: -18px;
        margin-right: -18px;
        padding: 12px 18px calc(12px + env(safe-area-inset-bottom));
        gap: 8px;
    }

    /* ── Chips de hora: envolver en móvil ── */
    .hb-reserve-timechips{
        flex-wrap: wrap;
        gap: 8px;
    }

    .hb-reserve-timechip{
        flex: 1 1 calc(50% - 4px);
        min-width: 0;
        justify-content: center;
        font-size: .82rem;
        padding: 10px 8px;
    }

    /* ── Campo de hora ── */
    .hb-reserve-timefield{
        margin-top: 2px;
    }

    .hb-arrival-input{
        font-size: 1rem;
    }

    .hb-reserve-hint{
        font-size: .78rem;
    }
}

@media (max-width: 760px){
    .swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
    .swal2-container.hb-swal-sheet-container.swal2-noanimation{
        align-items: flex-end !important;
        padding: 0 !important;
        background: rgba(17, 24, 39, .54) !important;
        backdrop-filter: none !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal{
        width: 100% !important;
        max-width: none !important;
        max-height: 92dvh !important;
        border: 0 !important;
        border-radius: 22px 22px 0 0 !important;
        background: #FFFEFB !important;
        box-shadow: 0 -18px 58px -26px rgba(8,13,20,.45) !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close{
        top: 22px !important;
        right: 22px !important;
        width: 46px !important;
        height: 46px !important;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 14%, #E5D8C9) !important;
        border-radius: 999px !important;
        background: #FFFEFB !important;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 74%, #76655A) !important;
        font-size: 1.35rem !important;
        box-shadow: 0 14px 26px -23px rgba(15,23,42,.50) !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-html-container{
        max-height: 92dvh !important;
        overflow: hidden !important;
    }

    .hb-reserve-shell{
        max-height: 92dvh;
        background: #FFFEFB;
    }

    .hb-reserve-main{
        max-height: 92dvh;
        padding: 0 !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        background: #FFFEFB !important;
    }

    .hb-reserve-mobile-dates{ display: none !important; }

    .hb-reserve-mobile-intro{
        display: block;
        position: relative;
        padding: 42px 22px 0;
    }

    .hb-reserve-mobile-intro::before{
        content: '';
        position: absolute;
        top: 16px;
        left: 50%;
        width: 68px;
        height: 8px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--brand-secondary, #0F172A) 22%, #DADDE3);
        transform: translateX(-50%);
    }

    .hb-reserve-mobile-kicker{
        display: block;
        margin: 0 58px 8px 0;
        color: #8A93A4;
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .18em;
        line-height: 1;
        text-transform: uppercase;
    }

    .hb-reserve-mobile-title{
        margin: 0 58px 12px 0;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #111827);
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        font-size: 1.72rem;
        font-weight: 850;
        letter-spacing: -.02em;
        line-height: 1.08;
    }

    .hb-reserve-mobile-copy{
        max-width: 31ch;
        margin: 0 0 20px;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 50%, #8A93A4);
        font-size: .94rem;
        font-weight: 680;
        line-height: 1.45;
    }

    .hb-reserve-mobile-datebox{
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin: 0 0 20px;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 18%, #E9D8C8);
        border-radius: 14px;
        background: linear-gradient(180deg, #FFFEFB, color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB));
        overflow: hidden;
    }

    .hb-reserve-mobile-dateitem{
        min-width: 0;
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr);
        align-items: center;
        gap: 9px;
        padding: 16px 12px;
    }

    .hb-reserve-mobile-dateitem + .hb-reserve-mobile-dateitem{
        border-left: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 10%, #E9D8C8);
    }

    .hb-reserve-mobile-dateicon{
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 7%, #F8F2EC);
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 70%, var(--brand-accent, #BD9441));
        font-size: .92rem;
    }

    .hb-reserve-mobile-dateitem small{
        display: block;
        color: #8A93A4;
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .10em;
        line-height: 1;
        text-transform: uppercase;
    }

    .hb-reserve-mobile-dateitem strong{
        display: block;
        margin-top: 4px;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 88%, #111827);
        font-size: .84rem;
        font-weight: 850;
        line-height: 1.1;
        white-space: nowrap;
    }

    .hb-reserve-mobile-progress{
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr);
        align-items: start;
        gap: 14px;
        margin: 4px 2px 28px;
    }

    .hb-reserve-mobile-progress-title{
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 56%, #6B7280);
        font-size: .88rem;
        font-weight: 820;
        line-height: 30px;
    }

    .hb-reserve-mobile-steps{
        display: grid;
        grid-template-columns: 1fr 1fr;
        position: relative;
        min-width: 0;
    }

    .hb-reserve-mobile-steps::before{
        content: '';
        position: absolute;
        top: 14px;
        left: 26px;
        right: 26px;
        height: 2px;
        background: color-mix(in srgb, var(--brand-secondary, #0F172A) 16%, #D9DEE6);
    }

    .hb-reserve-mobile-step{
        position: relative;
        z-index: 1;
        display: grid;
        justify-items: center;
        gap: 8px;
        color: #7A8496;
        font-size: .72rem;
        font-weight: 820;
        text-align: center;
    }

    .hb-reserve-mobile-step span{
        width: 30px;
        height: 30px;
        display: grid;
        place-items: center;
        border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 20%, #DADDE3);
        background: #FFFEFB;
        color: #7A8496;
        font-size: .78rem;
        font-weight: 850;
        font-variant-numeric: tabular-nums;
    }

    .hb-reserve-mobile-step.is-active{
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
    }

    .hb-reserve-mobile-step.is-active span{
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 34%, var(--brand-accent, #BD9441));
        background: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
        color: #FFFEFB;
    }

    .hb-reserve-mobile-step.is-complete span{
        border-color: #20A66B;
        background: #20A66B;
        color: #FFFEFB;
    }

    .hb-reserve-main > .hb-reserve-main__eyebrow{ display: none; }

    .hb-reserve-main h3{
        margin: 0;
        padding: 0 22px;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #111827);
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        font-size: 1.32rem;
        font-weight: 850;
        letter-spacing: -.02em;
        line-height: 1.16;
    }

    .hb-reserve-main > p{
        max-width: 34ch;
        margin: 8px 22px 14px;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 50%, #8791A3);
        font-size: .86rem;
        font-weight: 620;
        line-height: 1.42;
    }

    .hb-reserve-choice-list{
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 0 22px;
    }

    .hb-reserve-choice{
        min-height: 94px;
        grid-template-columns: 50px minmax(0, 1fr) 34px;
        gap: 14px;
        padding: 14px 15px 14px 18px;
        border-radius: 12px;
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 15%, #E9D8C8);
        background: #FFFEFB;
        box-shadow: 0 1px 0 rgba(255,255,255,.9) inset;
    }

    .hb-reserve-choice:hover,
    .hb-reserve-choice:focus-visible,
    .hb-reserve-choice.is-selected{
        background:
            radial-gradient(260px 130px at 100% 0%, color-mix(in srgb, var(--hb-choice-color) 8%, transparent), transparent 70%),
            color-mix(in srgb, var(--hb-choice-color) 4%, #FFFEFB);
        border-color: color-mix(in srgb, var(--hb-choice-color) 52%, #E1CDBE);
        box-shadow: 0 16px 30px -30px color-mix(in srgb, var(--hb-choice-color) 56%, transparent);
    }

    .hb-reserve-choice__icon{
        width: 50px;
        height: 50px;
        border-radius: 13px;
        font-size: 1rem;
    }

    .hb-reserve-choice__copy > span{
        gap: 9px;
        flex-wrap: wrap;
    }

    .hb-reserve-choice__copy strong{
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        font-size: 1.03rem;
        font-weight: 850;
    }

    .hb-reserve-choice__copy em{
        min-height: 22px;
        padding: 0 9px;
        font-size: .65rem;
    }

    .hb-reserve-choice__copy small{
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 48%, #8791A3);
        font-size: .82rem;
        font-weight: 620;
        line-height: 1.35;
    }

    .hb-reserve-choice__check{
        width: 34px;
        height: 34px;
        font-size: .82rem;
    }

    .hb-reserve-footer{
        grid-template-columns: 1fr 1fr;
        gap: 14px;
        position: sticky;
        bottom: 0;
        z-index: 4;
        margin: 26px 0 0;
        padding: 16px 22px calc(20px + env(safe-area-inset-bottom));
        border-top: 0;
        background:
            linear-gradient(180deg, rgba(255,254,251,0), #FFFEFB 20%),
            #FFFEFB;
    }

    .hb-reserve-btn{
        min-height: 56px;
        border-radius: 13px;
        font-size: .92rem;
        font-weight: 850;
    }

    .hb-reserve-btn--ghost{
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 13%, #E9D8C8);
        background: #FFFEFB;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, #394154);
    }

    .hb-reserve-btn--primary{
        background: linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, var(--brand-accent, #BD9441)), var(--brand-secondary, #0F172A));
        box-shadow: 0 18px 36px -26px color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, transparent);
    }

    .hb-reserve-timechips{
        flex-wrap: wrap;
        gap: 8px;
        padding: 0 22px;
        margin-top: 2px;
    }

    .hb-reserve-timechip{
        flex: 1 1 calc(50% - 4px);
        min-width: 0;
        min-height: 42px;
        justify-content: center;
        font-size: .82rem;
        padding: 10px 8px;
    }

    .hb-reserve-timefield{
        margin: 12px 22px 0;
        min-height: 58px;
    }

    .hb-arrival-input{ font-size: 1rem; }

    .hb-reserve-hint{
        margin: 10px 22px 0 !important;
        font-size: .78rem;
    }

    .hb-reserve-validation{
        margin: 10px 22px 0;
    }
}

@media (max-width: 430px){
    .hb-reserve-mobile-intro{
        padding-left: 18px;
        padding-right: 18px;
    }

    .hb-reserve-mobile-datebox{
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .hb-reserve-mobile-dateitem{
        grid-template-columns: 30px minmax(0, 1fr);
        gap: 7px;
        padding: 12px 8px;
    }

    .hb-reserve-mobile-dateitem + .hb-reserve-mobile-dateitem{
        border-left: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 10%, #E9D8C8);
        border-top: 0;
    }

    .hb-reserve-mobile-dateicon{
        width: 30px;
        height: 30px;
        border-radius: 9px;
        font-size: .78rem;
    }

    .hb-reserve-mobile-dateitem small{
        font-size: .56rem;
    }

    .hb-reserve-mobile-dateitem strong{
        font-size: .74rem;
    }

    .hb-reserve-mobile-progress{
        grid-template-columns: 104px minmax(0, 1fr);
        gap: 10px;
        margin-bottom: 24px;
    }

    .hb-reserve-mobile-progress-title{
        line-height: 30px;
        font-size: .80rem;
    }

    .hb-reserve-main h3,
    .hb-reserve-choice-list,
    .hb-reserve-timechips{
        padding-left: 18px;
        padding-right: 18px;
    }

    .hb-reserve-main > p{
        margin-left: 18px;
        margin-right: 18px;
    }

    .hb-reserve-timefield,
    .hb-reserve-hint,
    .hb-reserve-validation{
        margin-left: 18px !important;
        margin-right: 18px !important;
    }

    .hb-reserve-choice{
        grid-template-columns: 44px minmax(0, 1fr) 32px;
        padding-left: 14px;
        padding-right: 12px;
    }

    .hb-reserve-choice__icon{
        width: 44px;
        height: 44px;
    }

    .hb-reserve-footer{
        padding-left: 18px;
        padding-right: 18px;
        gap: 10px;
    }
}

/* Nueva reservacion movil: hoja compacta alineada al sheet de habitacion. */
@media (max-width: 760px){
    .swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
    .swal2-container.hb-swal-sheet-container.swal2-noanimation{
        padding: 0 18px calc(18px + env(safe-area-inset-bottom)) !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal{
        width: min(390px, calc(100vw - 36px)) !important;
        max-width: 390px !important;
        max-height: 86dvh !important;
        border-radius: 24px 24px 16px 16px !important;
        box-shadow: 0 -12px 40px rgba(18,22,34,.22) !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close{
        top: 18px !important;
        right: 18px !important;
        width: 38px !important;
        height: 38px !important;
        font-size: 1.18rem !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close{
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-html-container,
    .hb-reserve-shell,
    .hb-reserve-main{
        max-height: 86dvh !important;
    }

    .hb-reserve-mobile-intro{
        padding: 26px 20px 0 !important;
    }

    .hb-reserve-mobile-intro::before{
        top: 10px !important;
        width: 40px !important;
        height: 5px !important;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 26%, #D7D0C7) !important;
    }

    .hb-reserve-mobile-kicker{
        margin: 0 48px 6px 0 !important;
        font-size: .64rem !important;
        letter-spacing: .16em !important;
    }

    .hb-reserve-mobile-title{
        margin: 0 48px 12px 0 !important;
        font-size: 1.36rem !important;
        line-height: 1.05 !important;
    }

    .hb-reserve-mobile-copy{
        display: none !important;
    }

    .hb-reserve-mobile-datebox{
        margin-bottom: 14px !important;
        border-radius: 13px !important;
    }

    .hb-reserve-mobile-dateitem{
        grid-template-columns: 24px minmax(0, 1fr) !important;
        gap: 6px !important;
        padding: 10px 7px !important;
    }

    .hb-reserve-mobile-dateicon{
        width: 24px !important;
        height: 24px !important;
        border-radius: 8px !important;
        font-size: .66rem !important;
    }

    .hb-reserve-mobile-dateitem small{
        font-size: .50rem !important;
        letter-spacing: .07em !important;
    }

    .hb-reserve-mobile-dateitem strong{
        margin-top: 3px !important;
        font-size: .68rem !important;
    }

    .hb-reserve-mobile-progress{
        grid-template-columns: 86px minmax(0, 1fr) !important;
        gap: 8px !important;
        margin: 0 0 16px !important;
        align-items: center !important;
    }

    .hb-reserve-mobile-progress-title{
        font-size: .74rem !important;
        line-height: 26px !important;
    }

    .hb-reserve-mobile-steps::before{
        top: 12px !important;
        left: 24px !important;
        right: 24px !important;
    }

    .hb-reserve-mobile-step{
        gap: 0 !important;
    }

    .hb-reserve-mobile-step span{
        width: 26px !important;
        height: 26px !important;
        font-size: .70rem !important;
    }

    .hb-reserve-mobile-step strong{
        display: none !important;
    }

    .hb-reserve-main h3{
        padding: 0 20px !important;
        margin: 0 0 14px !important;
        font-size: 1.06rem !important;
        line-height: 1.15 !important;
    }

    .hb-reserve-main > p{
        display: none !important;
    }

    .hb-reserve-choice-list{
        gap: 8px !important;
        padding: 0 20px !important;
    }

    .hb-reserve-choice{
        min-height: 68px !important;
        grid-template-columns: 38px minmax(0, 1fr) 28px !important;
        gap: 11px !important;
        padding: 11px 12px !important;
        border-radius: 12px !important;
    }

    .hb-reserve-choice__icon{
        width: 38px !important;
        height: 38px !important;
        border-radius: 11px !important;
        font-size: .88rem !important;
    }

    .hb-reserve-choice__copy{
        gap: 3px !important;
    }

    .hb-reserve-choice__copy > span{
        gap: 7px !important;
    }

    .hb-reserve-choice__copy strong{
        font-size: .94rem !important;
    }

    .hb-reserve-choice__copy em{
        min-height: 18px !important;
        padding: 0 7px !important;
        font-size: .58rem !important;
    }

    .hb-reserve-choice__copy small{
        display: none !important;
    }

    .hb-reserve-choice__check{
        width: 28px !important;
        height: 28px !important;
        font-size: .72rem !important;
    }

    .hb-reserve-timechips{
        gap: 7px !important;
        padding: 0 20px !important;
        margin-top: 0 !important;
    }

    .hb-reserve-timechip{
        min-height: 38px !important;
        flex-basis: calc(50% - 4px) !important;
        padding: 8px 7px !important;
        font-size: .76rem !important;
    }

    .hb-reserve-timefield{
        min-height: 52px !important;
        margin: 10px 20px 0 !important;
        padding: 0 14px !important;
    }

    .hb-reserve-timefield .hb-arrival-input{
        height: 50px !important;
        min-height: 50px !important;
        font-size: .98rem !important;
    }

    .hb-reserve-hint{
        display: none !important;
    }

    .hb-reserve-validation{
        margin: 8px 20px 0 !important;
    }

    .hb-reserve-footer{
        gap: 10px !important;
        margin-top: 14px !important;
        padding: 12px 20px calc(14px + env(safe-area-inset-bottom)) !important;
    }

    .hb-reserve-footer--single{
        grid-template-columns: 1fr !important;
    }

    .hb-reserve-btn{
        min-height: 46px !important;
        border-radius: 13px !important;
        font-size: .84rem !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-mobile-intro{
        padding: 28px 24px 0 !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-mobile-title{
        margin-bottom: 14px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-mobile-datebox{
        margin-bottom: 18px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-mobile-dateitem{
        padding: 11px 8px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-mobile-progress{
        margin-bottom: 20px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-main h3{
        margin: 2px 0 14px !important;
        padding-left: 24px !important;
        padding-right: 24px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-choice-list,
    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-timechips{
        padding-left: 24px !important;
        padding-right: 24px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-choice-list{
        gap: 10px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-choice{
        min-height: 74px !important;
        padding: 13px 14px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-timechips{
        gap: 9px !important;
        margin-top: 2px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-timefield,
    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-validation{
        margin-left: 24px !important;
        margin-right: 24px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-timefield{
        margin-top: 14px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-validation{
        margin-top: 10px !important;
    }

    .habitaciones-view .hb-mobile-room-sheet.is-reserving .hb-reserve-footer{
        gap: 12px !important;
        margin-top: 20px !important;
        padding: 14px 24px calc(16px + env(safe-area-inset-bottom)) !important;
    }
}

@media (min-width: 761px){
    .swal2-container.hb-swal-sheet-container.swal2-backdrop-show,
    .swal2-container.hb-swal-sheet-container.swal2-noanimation{
        background: rgba(17, 24, 39, .42) !important;
        backdrop-filter: none !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal{
        width: min(820px, calc(100vw - 56px)) !important;
        border-radius: 22px !important;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 12%, #E8DED1) !important;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB) !important;
        box-shadow:
            0 34px 90px -48px color-mix(in srgb, var(--brand-secondary, #0F172A) 78%, transparent),
            0 1px 0 rgba(255,255,255,.86) inset !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close{
        top: 20px !important;
        right: 20px !important;
        width: 38px !important;
        height: 38px !important;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 12%, #E4D8C8) !important;
        border-radius: 999px !important;
        background: #FFFEFB !important;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, #7B6B5F) !important;
        font-size: 1.24rem !important;
        box-shadow: 0 12px 26px -24px rgba(15, 23, 42, .55) !important;
    }

    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close:hover,
    .swal2-container.hb-swal-sheet-container .swal2-popup.hb-reserve-swal .swal2-close:focus-visible{
        transform: translateY(-1px);
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 7%, #FFFEFB) !important;
        color: var(--brand-secondary, #0F172A) !important;
    }

    .hb-reserve-shell{
        --hb-reserve-brand: var(--brand-primary, #1B2746);
        --hb-reserve-brand-2: var(--brand-secondary, #0F172A);
        --hb-reserve-accent: var(--brand-accent, #BD9441);
        --hb-reserve-warm: color-mix(in srgb, var(--brand-secondary, #0F172A) 72%, var(--brand-accent, #BD9441));
        --hb-reserve-line: color-mix(in srgb, var(--brand-secondary, #0F172A) 13%, #E9DFD1);
        --hb-reserve-surface: color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB);
        --hb-reserve-paper: #FFFEFB;
        --hb-reserve-ink: color-mix(in srgb, var(--brand-secondary, #0F172A) 88%, #121826);
        --hb-reserve-muted: color-mix(in srgb, var(--brand-secondary, #0F172A) 52%, #8D96A5);
        --hb-reserve-new: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
        grid-template-columns: 318px minmax(0, 1fr);
        min-height: 528px;
        background: var(--hb-reserve-paper);
    }

    .hb-reserve-side{
        padding: 28px 32px;
        grid-template-rows: auto auto auto auto 1fr;
        background:
            radial-gradient(240px 210px at 108% 9%, rgba(255,255,255,.12), transparent 64%),
            linear-gradient(158deg, color-mix(in srgb, var(--hb-reserve-warm) 88%, #5F514A), color-mix(in srgb, var(--hb-reserve-brand-2) 84%, #3C302D)) !important;
    }

    .hb-reserve-side::after{
        top: -26px;
        right: -78px;
        width: 250px;
        height: 250px;
        background: rgba(255,255,255,.08);
        opacity: .88;
    }

    .hb-reserve-side__eyebrow{
        margin-bottom: 14px;
        color: rgba(255,255,255,.68);
        font-size: .68rem;
        letter-spacing: .22em;
    }

    .hb-reserve-side h2{
        max-width: 10ch;
        font-size: 2rem;
        line-height: 1.02;
        text-shadow: 0 1px 0 rgba(0,0,0,.08);
    }

    .hb-reserve-side > p{
        margin: 14px 0 22px;
        max-width: 27ch;
        color: rgba(255,255,255,.80);
        font-size: .88rem;
        font-weight: 620;
    }

    .hb-reserve-datebox{
        padding: 10px 16px;
        border-radius: 15px;
        border-color: rgba(255,255,255,.16);
        background: rgba(255,255,255,.09);
        box-shadow: 0 1px 0 rgba(255,255,255,.10) inset;
    }

    .hb-reserve-dateitem{
        min-height: 58px;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 12px;
    }

    .hb-reserve-dateicon{
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: rgba(255,255,255,.12);
        color: rgba(255,255,255,.86);
        font-size: .82rem;
    }

    .hb-reserve-dateitem small{
        color: rgba(255,255,255,.55);
        font-size: .66rem;
        letter-spacing: .09em;
    }

    .hb-reserve-dateitem strong{
        font-size: .91rem;
        letter-spacing: .01em;
    }

    .hb-reserve-steps{
        gap: 14px;
        margin-top: 22px;
    }

    .hb-reserve-steps li{
        grid-template-columns: 32px minmax(0, 1fr);
        gap: 12px;
        color: rgba(255,255,255,.58);
    }

    .hb-reserve-steps li span{
        width: 32px;
        height: 32px;
        border-color: rgba(255,255,255,.18);
        background: rgba(255,255,255,.05);
    }

    .hb-reserve-steps li.is-active span{
        border-color: rgba(255,255,255,.20);
        background: color-mix(in srgb, var(--hb-reserve-warm) 78%, #FFFEFB);
        box-shadow: 0 14px 26px -20px rgba(0,0,0,.45);
    }

    .hb-reserve-main{
        min-height: 528px;
        padding: 42px 36px 0;
        background:
            linear-gradient(180deg, #FFFEFB, color-mix(in srgb, var(--brand-accent, #BD9441) 2%, #FFFEFB)) !important;
    }

    .hb-reserve-main__eyebrow{
        margin-bottom: 24px;
        color: #8E97A7;
        font-size: .69rem;
        letter-spacing: .18em;
    }

    .hb-reserve-main h3{
        font-size: 1.42rem;
        line-height: 1.16;
    }

    .hb-reserve-main > p{
        max-width: 48ch;
        margin: 8px 0 22px;
        font-size: .88rem;
        line-height: 1.48;
    }

    .hb-reserve-choice-list{
        gap: 14px;
    }

    .hb-reserve-choice{
        min-height: 98px;
        grid-template-columns: 46px minmax(0, 1fr) 30px;
        gap: 16px;
        padding: 17px 20px;
        border-radius: 12px;
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 11%, #E9DFD1);
        background: #FFFEFB;
        box-shadow: 0 1px 0 rgba(255,255,255,.86) inset;
    }

    .hb-reserve-choice:hover,
    .hb-reserve-choice:focus-visible,
    .hb-reserve-choice.is-selected{
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--hb-choice-color) 58%, #D9CABE);
        background:
            radial-gradient(260px 140px at 100% 0%, color-mix(in srgb, var(--hb-choice-color) 9%, transparent), transparent 68%),
            color-mix(in srgb, var(--hb-choice-color) 5%, #FFFEFB);
        box-shadow:
            0 1px 0 rgba(255,255,255,.92) inset,
            0 18px 38px -34px color-mix(in srgb, var(--hb-choice-color) 62%, transparent);
    }

    .hb-reserve-choice__icon{
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--hb-choice-color) 8%, #F7F2EC);
        color: color-mix(in srgb, var(--hb-choice-color) 68%, var(--hb-reserve-ink));
    }

    .hb-reserve-choice__copy{
        gap: 7px;
    }

    .hb-reserve-choice__copy strong{
        font-size: 1.04rem;
    }

    .hb-reserve-choice__copy em{
        min-height: 19px;
        padding: 0 8px;
        background: color-mix(in srgb, var(--hb-choice-color) 9%, #F8F3EE);
        color: color-mix(in srgb, var(--hb-choice-color) 72%, var(--hb-reserve-muted));
    }

    .hb-reserve-choice__copy small{
        max-width: 42ch;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 46%, #8D96A5);
        font-size: .82rem;
        line-height: 1.42;
    }

    .hb-reserve-choice__check{
        width: 30px;
        height: 30px;
        border-color: color-mix(in srgb, var(--hb-choice-color) 24%, #D9CEC4);
    }

    .hb-reserve-choice.is-selected .hb-reserve-choice__check{
        border-color: color-mix(in srgb, var(--hb-choice-color) 88%, #FFFEFB);
        background: color-mix(in srgb, var(--hb-choice-color) 88%, #FFFEFB);
    }

    .hb-reserve-footer{
        grid-template-columns: minmax(160px, .9fr) minmax(220px, 1.15fr);
        gap: 18px;
        margin: auto -36px 0;
        padding: 24px 36px 24px;
        border-top-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 9%, #E9DFD1);
        background: linear-gradient(180deg, color-mix(in srgb, var(--brand-accent, #BD9441) 1%, #FFFEFB), #FFFEFB);
    }

    .hb-reserve-btn{
        min-height: 50px;
        border-radius: 12px;
        font-size: .88rem;
    }

    .hb-reserve-btn--ghost{
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 12%, #E9DFD1);
        background: #FFFEFB;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 78%, #384153);
    }

    .hb-reserve-btn--primary{
        background: linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 84%, #4D5566), var(--brand-secondary, #0F172A));
        color: #FFFEFB;
        box-shadow: 0 18px 34px -24px color-mix(in srgb, var(--brand-secondary, #0F172A) 72%, transparent);
    }
}

/* Correccion desktop: el modal de limpieza debe respetar la sidebar fija. */
@media (min-width: 1025px) {
    body.hotel-layout-scope #modalLimpieza {
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: var(--sidebar-width, 260px) !important;
        width: auto !important;
        height: 100vh !important;
        height: 100dvh !important;
        z-index: 900 !important;
        align-items: center !important;
        justify-content: center !important;
        padding: clamp(18px, 2vw, 32px) !important;
    }

    body.hotel-layout-scope.sidebar-collapsed #modalLimpieza {
        left: var(--sidebar-collapsed-width, 70px) !important;
    }

    body.hotel-layout-scope #modalLimpieza > .bg-white {
        width: min(940px, calc(100% - 48px)) !important;
        max-width: 940px !important;
        max-height: min(82vh, 760px) !important;
        margin: 0 auto !important;
        border-radius: 22px !important;
    }

    body.hotel-layout-scope #modalLimpieza > .bg-white > div:first-child {
        border-radius: 22px 22px 0 0 !important;
    }

    body.hotel-layout-scope #modalLimpieza .p-6.overflow-y-auto {
        max-height: min(calc(82vh - 168px), 560px) !important;
        padding: 20px 24px !important;
    }

    body.hotel-layout-scope #modalLimpieza .bg-gray-50 {
        padding: 16px 24px !important;
    }
}

@media (max-width: 1024px) {
    body.hotel-layout-scope #modalLimpieza {
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        z-index: 10070 !important;
        align-items: flex-end !important;
        justify-content: center !important;
        padding: 0 !important;
    }

    body.hotel-layout-scope #modalLimpieza > .bg-white {
        width: 100% !important;
        max-width: none !important;
        height: min(88dvh, calc(100dvh - env(safe-area-inset-top, 0px) - 8px)) !important;
        max-height: min(88dvh, calc(100dvh - env(safe-area-inset-top, 0px) - 8px)) !important;
        display: grid !important;
        grid-template-rows: auto minmax(0, 1fr) auto !important;
        overflow: hidden !important;
        border-radius: 24px 24px 0 0 !important;
    }

    body.hotel-layout-scope #modalLimpieza > .bg-white > div:first-child {
        min-height: 116px !important;
        padding: 24px 18px 18px !important;
        border-radius: 24px 24px 0 0 !important;
    }

    body.hotel-layout-scope #modalLimpieza .p-6.overflow-y-auto {
        min-height: 0 !important;
        max-height: none !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
        padding: 16px 14px calc(18px + env(safe-area-inset-bottom, 0px)) !important;
    }

    body.hotel-layout-scope #modalLimpieza .bg-gray-50 {
        position: relative !important;
        z-index: 2 !important;
        display: grid !important;
        grid-template-columns: minmax(0, .9fr) minmax(0, 1.25fr) !important;
        gap: 10px !important;
        padding: 12px 14px calc(14px + env(safe-area-inset-bottom, 0px)) !important;
        background:
            linear-gradient(180deg, color-mix(in srgb, var(--hb-surface, #FFFFFF) 88%, transparent), var(--hb-surface-warm, #F5F5F7) 18%),
            var(--hb-surface-warm, #F5F5F7) !important;
        box-shadow: 0 -12px 26px -24px rgba(18, 22, 34, .55);
    }

    body.hotel-layout-scope #modalLimpieza .bg-gray-50 button {
        width: 100% !important;
        min-width: 0 !important;
        min-height: 46px !important;
        padding: 11px 10px !important;
        border-radius: 13px !important;
        font-size: .78rem !important;
        line-height: 1.05 !important;
        white-space: normal !important;
    }
}
</style>

<style id="hb-room-card-info-scroll-fixed-actions">
@media (min-width: 641px) {
    .habitaciones-view .flip-card.flipped {
        min-height: 258px !important;
        height: 258px !important;
        overflow: visible !important;
    }

    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped {
        min-height: 284px !important;
        height: 284px !important;
    }

    .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped {
        min-height: 306px !important;
        height: 306px !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back {
        top: 3px !important;
        bottom: 3px !important;
        overflow: hidden !important;
        padding: 12px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-start !important;
        gap: 8px !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back > .room-card-scroll-info,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > .room-card-scroll-info {
        min-height: 0 !important;
        flex: 1 1 auto !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        padding-right: 5px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 5px !important;
        scrollbar-width: thin !important;
        scrollbar-color: rgba(255, 255, 255, .42) transparent !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar {
        width: 5px !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar-track,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar-track {
        background: transparent !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar-thumb,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back > .room-card-scroll-info::-webkit-scrollbar-thumb {
        border-radius: 999px !important;
        background: rgba(255, 255, 255, .38) !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back h4,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back h4 {
        flex: none !important;
        position: relative !important;
        z-index: 3 !important;
        margin: 0 0 3px !important;
        padding: 0 0 8px !important;
        font-size: 1.05rem !important;
        line-height: 1.05 !important;
        border-bottom: 1px solid rgba(255, 255, 255, .22) !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back .info-item,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item {
        min-height: 24px !important;
        margin: 0 !important;
        padding: 4px 7px !important;
        border: 1px solid rgba(255, 255, 255, .14) !important;
        border-radius: 9px !important;
        background: rgba(255, 255, 255, .11) !important;
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
        color: rgba(255, 255, 255, .94) !important;
        font-size: .66rem !important;
        font-weight: 750 !important;
        line-height: 1.12 !important;
        text-shadow: none !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back .info-item i,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item i {
        width: 17px !important;
        height: 17px !important;
        display: inline-grid !important;
        place-items: center !important;
        flex: none !important;
        border-radius: 6px !important;
        background: rgba(255, 255, 255, .15) !important;
        color: #fff !important;
        font-size: .58rem !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back .info-item span,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .info-item span {
        min-width: 0 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back .action-buttons,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .action-buttons {
        flex: none !important;
        position: relative !important;
        z-index: 2 !important;
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 7px !important;
        margin-top: 0 !important;
        padding-top: 8px !important;
        border-top: 1px solid rgba(255, 255, 255, .18) !important;
    }

    .habitaciones-view .flip-card.flipped .flip-card-back .btn-action,
    .habitaciones-view .room-card-compact.has-checkin-vencido.flipped .flip-card-back .btn-action {
        min-width: 0 !important;
        min-height: 44px !important;
        padding: 7px 8px !important;
        border-radius: 10px !important;
        font-size: .65rem !important;
        font-weight: 850 !important;
        line-height: 1 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    .habitaciones-view .room-card-compact.has-checkin-vencido.has-cleaning-state.flipped .flip-card-back .action-buttons {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    }
}

.habitaciones-view .hb-chip {
    min-height: 44px !important;
}

@media (max-width: 640px), (hover: none) and (pointer: coarse) {
    .habitaciones-view .main-content,
    .habitaciones-view {
        touch-action: pan-y;
    }

    .habitaciones-view .flip-card,
    .habitaciones-view .room-card-compact,
    .habitaciones-view .flip-card-inner,
    .habitaciones-view .flip-card-front {
        -webkit-backface-visibility: hidden !important;
        backface-visibility: hidden !important;
        will-change: auto !important;
    }

    .habitaciones-view .flip-card,
    .habitaciones-view .room-card-compact,
    .habitaciones-view .checkout-today-indicator,
    .habitaciones-view .checkout-vencido-indicator,
    .habitaciones-view .checkin-vencido-indicator,
    .habitaciones-view .late-arrival-indicator,
    .habitaciones-view .hb-alerts-mark-wrap::after,
    .habitaciones-view .animate-pulse {
        animation: none !important;
    }

    .habitaciones-view .checkin-vencido-indicator i,
    .habitaciones-view .checkout-vencido-indicator i {
        animation: none !important;
    }

    #vistaRapidaModal,
    #modalLimpieza {
        z-index: 10040 !important;
    }
}

/* Correccion final: header real del modal de limpieza.
   Esta capa vive al final porque la vista conserva overrides antiguos para
   #modalLimpieza basados en el markup previo. */
#modalLimpieza.lm-overlay > .lm-dialog::before {
    content: none !important;
    display: none !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header {
    position: relative !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 16px !important;
    min-height: 94px !important;
    padding: 20px 24px 20px 22px !important;
    border-radius: 24px 24px 0 0 !important;
    background: linear-gradient(135deg, #2E6FD2 0%, #3D82EA 58%, #60A1FA 100%) !important;
    color: #fff !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header .lm-header__main {
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    min-width: 0 !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header .lm-emblem {
    width: 50px !important;
    height: 50px !important;
    border-radius: 14px !important;
    background: rgba(255,255,255,.16) !important;
    border: 1px solid rgba(255,255,255,.34) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.34), 0 8px 18px -12px rgba(0,0,0,.42) !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header h3.lm-title {
    margin: 0 !important;
    color: #fff !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    font-size: 1.58rem !important;
    font-weight: 600 !important;
    line-height: 1.1 !important;
    letter-spacing: 0 !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header .lm-subtitle {
    margin: 4px 0 0 !important;
    color: rgba(255,255,255,.88) !important;
    font-size: .78rem !important;
    font-weight: 700 !important;
    line-height: 1.28 !important;
}

#modalLimpieza.lm-overlay > .lm-dialog > .lm-header .lm-close {
    width: 38px !important;
    height: 38px !important;
    border-radius: 9px !important;
    border: 1px solid rgba(255,255,255,.22) !important;
    background: rgba(255,255,255,.1) !important;
    color: #fff !important;
}

@media (max-width: 640px) {
    #modalLimpieza.lm-overlay,
    body.hotel-layout-scope #modalLimpieza.lm-overlay {
        align-items: center !important;
        justify-content: center !important;
        padding: 24px !important;
    }

    #modalLimpieza.lm-overlay > .lm-dialog,
    body.hotel-layout-scope #modalLimpieza.lm-overlay > .lm-dialog {
        width: min(680px, 100%) !important;
        max-width: 680px !important;
        height: auto !important;
        max-height: min(88vh, 760px) !important;
        max-height: min(88dvh, 760px) !important;
        border-radius: 24px !important;
        animation: lmPop .3s cubic-bezier(.22,1,.36,1) !important;
    }

    #modalLimpieza.lm-overlay > .lm-dialog > .lm-header {
        min-height: 94px !important;
        padding: 20px 24px 20px 22px !important;
    }

    #modalLimpieza.lm-overlay > .lm-dialog > .lm-header h3.lm-title {
        font-size: 1.58rem !important;
        line-height: 1.1 !important;
    }
}
</style>

<style id="hb-room-card-glass-redesign">
/* ═══ Tarjetas de habitación: cristal líquido — SOLO TEMA CUPERTINO ═══════
   (decisión del owner 2026-07-10: Deleite conserva sus tarjetas tal cual).
   Pase final SOLO escritorio (≥769px) y SOLO modo claro: el pase móvil
   compacto (≤768px) y el modo oscuro conservan su diseño tal cual.
   Gana a la sección 18 de cupertino.css (tarjeta blanca plana) por orden
   de documento: mismo peso y especificidad, pero este bloque vive en el
   <body> y la hoja del tema en el <head>.
   Menos señales compitiendo, una jerarquía clara: fuera el pastel de
   cuerpo entero, el borde izquierdo grueso y los chips con marco; el
   estado vive en UNA bruma de cristal + la pastilla tintada. Sin
   backdrop-filter: el lienzo es plano (nada que desenfocar) y con
   ~50 tarjetas sería puro costo. El stripe de color de la habitación
   (identidad, no estado) se conserva.
   Nombres largos: el PHP emite rc-num--long / rc-num--xl por longitud
   (el modificador queda en el markup para todos los temas; solo estila aquí).
   Revertir: borrar este bloque + el modificador en el markup (rc-num). */

/* Antitruncado de nombres (ambos anchos y modos de Cupertino) */
html[data-tema="cupertino"] .habitaciones-view .rc-num.rc-num--long{
  font-size:1.3rem!important;
  line-height:1.05!important;
  letter-spacing:.005em!important;
  padding-top:4px;
  max-width:100%;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
html[data-tema="cupertino"] .habitaciones-view .rc-num.rc-num--xl{
  font-size:1.02rem!important;
  line-height:1.1!important;
  padding-top:6px;
  max-width:100%;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
html[data-tema="cupertino"] .habitaciones-view .rc-id{ min-width:0; }

@media (min-width: 769px){
  /* Color de estado de cada tarjeta (custom prop que hereda toda la cara) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-disponible,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-disponible_fecha{ --rc-state: var(--c-available); }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-ocupada,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-ocupada_fecha{ --rc-state: var(--c-occupied); }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-por_llegar,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-doble{ --rc-state: var(--c-arriving); }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-limpieza,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-limpieza-por-llegar{ --rc-state: var(--c-cleaning); }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .estado-mantenimiento{ --rc-state: var(--c-maint); }

  /* Superficie: losa de cristal pastel del estado (tinte a toda la tarjeta),
     bloom de luz a la derecha y cantos de vidrio grueso — ref. candy glass. */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"]{
    background:
      radial-gradient(58% 74% at 96% 80%, rgba(255,255,255,.85), rgba(255,255,255,0) 72%),
      linear-gradient(180deg, rgba(255,255,255,.5), rgba(255,255,255,0) 40%),
      linear-gradient(165deg,
        color-mix(in srgb, var(--rc-state, var(--hb-primary)) 14%, #FFFFFF) 0%,
        color-mix(in srgb, var(--rc-state, var(--hb-primary)) 30%, #FFFFFF) 100%)!important;
    border:1px solid color-mix(in srgb, var(--rc-state, var(--hb-primary)) 24%, rgba(255,255,255,.9))!important;
    border-left-width:1px!important;
    border-radius:20px!important;
    box-shadow:
      inset 0 1px 1px rgba(255,255,255,.95),
      inset 0 -2px 5px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 13%, transparent),
      0 3px 7px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 12%, rgba(27,39,70,.05)),
      0 20px 38px -18px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 46%, rgba(27,39,70,.25))!important;
  }

  /* Radio de losa también en contenedor y hoja de acciones (coherencia al girar) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .room-card-compact,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back{
    border-radius:20px!important;
  }

  /* Elevación al pasar el cursor: el cristal flota y su halo se intensifica */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .room-card-compact{
    transition:transform .22s cubic-bezier(.22,1,.36,1);
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .room-card-compact:not(.flipped):hover{
    transform:translateY(-2px);
    box-shadow:none!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .room-card-compact:not(.flipped):hover .flip-card-front[class*="estado-"]{
    border-color:color-mix(in srgb, var(--rc-state, var(--hb-primary)) 40%, rgba(255,255,255,.9))!important;
    filter:brightness(1.02) saturate(1.05);
    box-shadow:
      inset 0 1px 1px rgba(255,255,255,.95),
      inset 0 -2px 5px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 13%, transparent),
      0 5px 12px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 16%, rgba(27,39,70,.06)),
      0 26px 48px -18px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 58%, rgba(27,39,70,.3))!important;
  }

  /* Pastilla de estado: chip lechoso sobre la losa pastel (como la ref.) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"] .rc-badge{
    background:rgba(255,255,255,.66)!important;
    color:color-mix(in srgb, var(--rc-state, var(--hb-primary)) 78%, #111827)!important;
    border:1px solid rgba(255,255,255,.85);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 1px 3px color-mix(in srgb, var(--rc-state, var(--hb-primary)) 18%, transparent)!important;
  }

  /* Identidad de la habitación: piso y tipo sin gritar (fuera uppercase) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .rc-type{
    text-transform:none!important;
    font-size:.67rem!important;
    font-weight:600!important;
    letter-spacing:.01em!important;
    color:var(--hb-slate-500, #667085)!important;
  }

  /* Línea contextual: huésped con icono del color del estado */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .rc-guest{ font-size:.84rem!important; }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"] .rc-guest i{
    color:color-mix(in srgb, var(--rc-state, var(--hb-primary)) 68%, #111827)!important;
  }

  /* Capacidad y camas: texto silencioso, sin marco de pastilla */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .rc-room-facts{ gap:12px!important; margin-top:7px!important; }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"] .rc-room-fact{
    min-height:0!important;
    padding:0!important;
    border:0!important;
    border-radius:0!important;
    background:transparent!important;
    box-shadow:none!important;
    font-size:.64rem!important;
    font-weight:650!important;
    color:var(--hb-slate-500, #667085)!important;
    overflow:visible!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"] .rc-room-fact i{
    font-size:.6rem!important;
    color:color-mix(in srgb, var(--rc-state, var(--hb-primary)) 50%, var(--hb-slate-400, #94A3B8))!important;
  }

  /* Pie: hilo de luz arriba, precio protagonista */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-front[class*="estado-"] .rc-foot{
    border-top:1px solid color-mix(in srgb, var(--rc-state, var(--hb-primary)) 12%, rgba(17,24,39,.06));
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .rc-price{ font-size:.92rem!important; }

  /* ── Hoja de acciones (card volteada): la misma losa candy glass ──
     El gradiente oscuro del estado se vuelve losa pastel con tinta oscura;
     chips lechosos, botón primario en el color sólido del estado. */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back{
    background:
      radial-gradient(58% 74% at 96% 82%, rgba(255,255,255,.85), rgba(255,255,255,0) 72%),
      linear-gradient(180deg, rgba(255,255,255,.5), rgba(255,255,255,0) 40%),
      linear-gradient(165deg,
        color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 18%, #FFFFFF) 0%,
        color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 36%, #FFFFFF) 100%)!important;
    color:#1D1D1F!important;
    border:1px solid color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 26%, rgba(255,255,255,.9))!important;
    box-shadow:
      inset 0 1px 1px rgba(255,255,255,.95),
      inset 0 -2px 5px color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 13%, transparent),
      0 -8px 22px -10px color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 34%, rgba(27,39,70,.14))!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back h4{
    color:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 72%, #111827)!important;
    border-bottom-color:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 24%, rgba(17,24,39,.08))!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .info-item,
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .hb-tareas-chip{
    background:rgba(255,255,255,.6)!important;
    border-color:rgba(255,255,255,.88)!important;
    color:#1F2937!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.85);
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .info-item i{
    background:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 15%, #FFFFFF)!important;
    color:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 75%, #111827)!important;
  }

  /* Chip de tareas vencidas: la alarma se re-dibuja para lienzo claro */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .hb-tareas-chip .hb-tareas-venc{ color:#B91C1C!important; }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .hb-tareas-chip.is-vencida{
    background:#FEE2E2!important;
    border-color:rgba(220,38,38,.45)!important;
    color:#991B1B!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back > .room-card-scroll-info::-webkit-scrollbar-thumb{
    background:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 35%, rgba(17,24,39,.16))!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .action-buttons{
    border-top-color:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 22%, rgba(17,24,39,.08))!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .btn-action{
    background:rgba(255,255,255,.62)!important;
    border:1px solid rgba(255,255,255,.88)!important;
    color:color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 70%, #111827)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 1px 3px color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 16%, transparent)!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .btn-action:hover{
    background:rgba(255,255,255,.82)!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .btn-primary{
    background:linear-gradient(180deg, color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 86%, #FFFFFF), var(--sheet-c, var(--hb-primary)))!important;
    color:#FFFFFF!important;
    border:1px solid color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 78%, #FFFFFF)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.35), 0 4px 10px -3px color-mix(in srgb, var(--sheet-c, var(--hb-primary)) 55%, transparent)!important;
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .flip-card-back .btn-primary:hover{
    filter:brightness(1.05);
  }
}

/* ── Header hero + paneles Check-ins/outs en candy glass (≥768px, como el
   hero original). Gana al pase hb-header-hero (posterior en documento) por
   especificidad del prefijo de tema. Limpieza (.hb-cleaning-btn) intacto. ── */
@media (min-width: 768px){
  /* Header: BARRA DE CRISTAL TRASLÚCIDO REAL (propuesta 2). Es sticky: al
     hacer scroll, las tarjetas candy pasan POR DEBAJO y el backdrop-filter
     las desenfoca a través del vidrio teñido de la marca — liquid glass con
     propósito, no solo pintura. Un solo elemento con blur = barato. */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header{
    background:
      radial-gradient(42% 150% at 96% 55%, rgba(255,255,255,.4), rgba(255,255,255,0) 70%),
      linear-gradient(165deg,
        color-mix(in srgb, var(--hb-primary) 22%, rgba(255,255,255,.82)) 0%,
        color-mix(in srgb, var(--hb-primary) 40%, rgba(255,255,255,.62)) 100%)!important;
    -webkit-backdrop-filter: blur(28px) saturate(1.8);
    backdrop-filter: blur(28px) saturate(1.8);
    border:1px solid color-mix(in srgb, var(--hb-primary) 26%, rgba(255,255,255,.7))!important;
    box-shadow:
      inset 0 1px 1px rgba(255,255,255,.85),
      inset 0 -1px 2px rgba(255,255,255,.3),
      0 12px 30px -14px color-mix(in srgb, var(--hb-primary) 42%, rgba(27,39,70,.2))!important;
  }

  /* Filo de luz superior sutil sobre el vidrio */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header::before{
    background:
      radial-gradient(60% 100% at 12% -30%, rgba(255,255,255,.45), transparent 58%),
      linear-gradient(180deg, rgba(255,255,255,.3), transparent 42%);
  }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header h1{
    color:#1D1D1F!important;
    text-shadow:none!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .hb-title-prefix{
    color:color-mix(in srgb, var(--hb-primary) 50%, #6E6E73)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header p{
    color:#6E6E73!important;
    text-shadow:none!important;
  }

  /* Emblema: teja lechosa con el icono en tinta de marca (adiós vidrio oscuro) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .p-2.rounded-lg{
    background:rgba(255,255,255,.6)!important;
    border:1px solid rgba(255,255,255,.9)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 6px 14px -8px color-mix(in srgb, var(--hb-primary) 40%, transparent)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .p-2.rounded-lg i{
    color:color-mix(in srgb, var(--hb-primary) 80%, #111827)!important;
  }

  /* Botones secundarios: chips lechosos con tinta (Vista Rápida, Nueva Habitación) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand){
    background:rgba(255,255,255,.62)!important;
    border:1px solid rgba(255,255,255,.88)!important;
    color:#1F2937!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 1px 3px color-mix(in srgb, var(--hb-primary) 14%, transparent)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand) i{
    color:color-mix(in srgb, var(--hb-primary) 70%, #111827)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand):hover{
    background:rgba(255,255,255,.85)!important;
    border-color:#FFFFFF!important;
  }

  /* CTA Nueva Reserva: sólido de marca — el que jala el ojo sobre el pastel */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern.btn-brand{
    background:linear-gradient(180deg, color-mix(in srgb, var(--hb-primary) 86%, #FFFFFF), var(--hb-primary))!important;
    color:#FFFFFF!important;
    border:1px solid color-mix(in srgb, var(--hb-primary) 78%, #FFFFFF)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.35), 0 10px 20px -10px color-mix(in srgb, var(--hb-primary) 60%, transparent)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern.btn-brand i{ color:#FFFFFF!important; }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .modern-header .btn-modern.btn-brand:hover{
    background:var(--hb-primary)!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.35), 0 14px 26px -10px color-mix(in srgb, var(--hb-primary) 68%, transparent)!important;
  }

  /* ── Check-ins / Check-outs: candy glass SOLO en la cabecera (decisión del
     owner): el cuerpo del panel conserva su lavado original de antes. ── */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-card--in{ --mv-c: var(--c-arriving, #7C3AED); }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-card--out{ --mv-c: var(--c-maint, #D97706); }

  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-head{
    background:
      radial-gradient(40% 170% at 97% 50%, rgba(255,255,255,.8), rgba(255,255,255,0) 70%),
      linear-gradient(165deg,
        color-mix(in srgb, var(--mv-c, var(--hb-primary)) 14%, #FFFFFF) 0%,
        color-mix(in srgb, var(--mv-c, var(--hb-primary)) 28%, #FFFFFF) 100%)!important;
    border-bottom:1px solid color-mix(in srgb, var(--mv-c, var(--hb-primary)) 22%, rgba(17,24,39,.06))!important;
    box-shadow:inset 0 1px 1px rgba(255,255,255,.9);
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-title strong{
    color:color-mix(in srgb, var(--mv-c, var(--hb-primary)) 70%, #111827)!important;
  }
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-title small{ color:#6E6E73!important; }

  /* Contador: chip lechoso con tinta del color (antes sólido) */
  html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-move-head h3 > span:last-child{
    background:rgba(255,255,255,.7)!important;
    color:color-mix(in srgb, var(--mv-c, var(--hb-primary)) 75%, #111827)!important;
    border:1px solid rgba(255,255,255,.9);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.9), 0 1px 3px color-mix(in srgb, var(--mv-c, var(--hb-primary)) 18%, transparent);
  }
}

/* ── Modo oscuro Cupertino: correcciones de esta franja (bugs preexistentes:
   la banda de filtros, el botón Hoy, la stat activa y los paneles
   Check-ins/outs quedaban con literales claros). Paleta Apple dark de
   cupertino.css: #1C1C1E paneles, #38383A líneas, #98989D muted. ── */
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view div.bg-white:has(> .hb-filterbar){
  background:#1C1C1E!important;
  border:1px solid #38383A;
  box-shadow:none!important;
}

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .filter-btn-today,
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .btn-brand-soft{
  background:#2C2C2E!important;
  border-color:#38383A!important;
  color:#F5F5F7!important;
}

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .btn-brand-soft:hover,
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .filter-btn-today:hover{
  background:#3A3A3C!important;
  border-color:#48484A!important;
  color:#F5F5F7!important;
}

/* Stat con filtro activo: gradiente blanco → panel grafito con tinte del estado */
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-stat.is-filter-active{
  background:linear-gradient(180deg, #1C1C1E, color-mix(in srgb, var(--sc, #0A84FF) 16%, #1C1C1E))!important;
  border-color:color-mix(in srgb, var(--sc, #0A84FF) 42%, #38383A)!important;
}
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-stat.is-filter-active .hb-stat-n{
  color:#F5F5F7!important;
}

/* Paneles Check-ins/outs: panel grafito + cabecera teñida del color (candy dark) */
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-card--in{ --mv-c: var(--c-arriving, #7C3AED); }
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-card--out{ --mv-c: var(--c-maint, #D97706); }

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-card{
  background:#1C1C1E!important;
  border-color:#38383A!important;
  box-shadow:none!important;
}

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-head{
  background:
    radial-gradient(40% 170% at 97% 50%, rgba(255,255,255,.08), rgba(255,255,255,0) 70%),
    linear-gradient(165deg,
      color-mix(in srgb, var(--mv-c, #0A84FF) 26%, #1C1C1E) 0%,
      color-mix(in srgb, var(--mv-c, #0A84FF) 14%, #1C1C1E) 100%)!important;
  border-bottom:1px solid color-mix(in srgb, var(--mv-c, #0A84FF) 32%, #38383A)!important;
}

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-title strong{
  color:color-mix(in srgb, var(--mv-c, #0A84FF) 45%, #F5F5F7)!important;
}
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-title small{ color:#98989D!important; }

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-head h3 > span:last-child{
  background:color-mix(in srgb, var(--mv-c, #0A84FF) 32%, #1C1C1E)!important;
  color:color-mix(in srgb, var(--mv-c, #0A84FF) 35%, #FFFFFF)!important;
  border:1px solid color-mix(in srgb, var(--mv-c, #0A84FF) 42%, transparent);
}

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-body{ background:transparent!important; }
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-empty{ color:#98989D!important; }

html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-item{ background:transparent!important; }
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-item:hover{ background:#2C2C2E!important; }
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-item p:first-child{ color:#F5F5F7!important; }
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-move-item p:last-child{ color:#98989D!important; }

/* ── Chips de filtro: cristal líquido del color del estado — SOLO CUPERTINO
   (petición del owner 2026-07-22: fuera la pastilla oscura; el chip activo
   se enciende con el candy glass de SU estado, como las tarjetas). "Todas"
   usa la tinta de marca vía el fallback de --chip-c. !important + prefijo
   de tema para ganar a los pases móviles del propio archivo. ── */
html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-chip.is-active{
  background:
    radial-gradient(60% 86% at 90% 78%, rgba(255,255,255,.85), rgba(255,255,255,0) 72%),
    linear-gradient(165deg,
      color-mix(in srgb, var(--chip-c, var(--hb-primary)) 16%, #FFFFFF) 0%,
      color-mix(in srgb, var(--chip-c, var(--hb-primary)) 34%, #FFFFFF) 100%)!important;
  border-color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 30%, rgba(255,255,255,.9))!important;
  color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 78%, #111827)!important;
  box-shadow:
    inset 0 1px 1px rgba(255,255,255,.95),
    inset 0 -2px 5px color-mix(in srgb, var(--chip-c, var(--hb-primary)) 13%, transparent),
    0 3px 7px color-mix(in srgb, var(--chip-c, var(--hb-primary)) 12%, rgba(27,39,70,.05)),
    0 14px 26px -14px color-mix(in srgb, var(--chip-c, var(--hb-primary)) 52%, rgba(27,39,70,.28))!important;
}
html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-chip.is-active .hb-chip-ct{
  color:inherit!important;
  opacity:.8!important;
}
/* Leyenda móvil: mismo lenguaje en tinte suave */
html[data-tema="cupertino"]:not([data-theme="dark"]) .habitaciones-view .hb-mobile-lg.is-active{
  background:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 12%, #FFFFFF)!important;
  border-color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 30%, rgba(255,255,255,.9))!important;
  color:color-mix(in srgb, var(--chip-c, var(--hb-primary)) 78%, #111827)!important;
}

/* Modo oscuro: losa grafito encendida con el tinte del estado (como hb-move-head) */
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-chip.is-active{
  background:linear-gradient(165deg,
    color-mix(in srgb, var(--chip-c, #0A84FF) 32%, #1C1C1E) 0%,
    color-mix(in srgb, var(--chip-c, #0A84FF) 16%, #1C1C1E) 100%)!important;
  border-color:color-mix(in srgb, var(--chip-c, #0A84FF) 42%, #38383A)!important;
  color:color-mix(in srgb, var(--chip-c, #0A84FF) 45%, #F5F5F7)!important;
  box-shadow:none!important;
}
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-chip.is-active .hb-chip-ct{
  color:inherit!important;
  opacity:.75!important;
}
html[data-theme="dark"][data-tema="cupertino"] .habitaciones-view .hb-mobile-lg.is-active{
  background:color-mix(in srgb, var(--chip-c, #0A84FF) 24%, #1C1C1E)!important;
  border-color:color-mix(in srgb, var(--chip-c, #0A84FF) 40%, #38383A)!important;
  color:color-mix(in srgb, var(--chip-c, #0A84FF) 42%, #F5F5F7)!important;
}
</style>

<style id="lm-caja-motion">
/* ══ Entrada coreografiada estilo Caja (registrar ingreso/gasto) + skeleton de carga ══
   Reemplaza el keyframe lmPop por una transición gobernada por .is-open (reflow síncrono),
   suma un skeleton de ~520ms y respeta prefers-reduced-motion. La sidebar/cabecera se
   ocultan desde JS (mostrarModalLimpieza), igual que en Caja. */

/* Fondo oscuro: aparece con un desvanecido suave */
#modalLimpieza.lm-overlay{ opacity:0; transition:opacity .26s cubic-bezier(.22,1,.36,1); }
#modalLimpieza.lm-overlay.is-open{ opacity:1; }

/* El overlay cubre TODO el viewport (incluida la franja de la sidebar) para que el
   MISMO backdrop oscuro+blur del modal la cubra igual que el resto del sistema. La
   sidebar no se oculta: el difuminador global la deja en z-index:1 (debajo), así que
   este backdrop full-bleed la unifica con el contenido — sin la franja más clara. */
@media (min-width:1025px){
  body.hotel-layout-scope #modalLimpieza.lm-overlay{
    left:0 !important; right:0 !important; top:0 !important; bottom:0 !important; width:100% !important;
  }
}

/* Sincronía sidebar↔modal SIN el observador genérico (que iba con lag). La propia
   apertura/cierre pone/quita `body.hb-lm-sidebar-under` (síncrono con la animación), y esto
   baja la sidebar bajo el backdrop full-bleed, que la cubre/difumina y aparece/desvanece CON
   el modal. El modal lleva `data-ms-keep-sidebar` para que el fix global lo ignore. */
body.hb-lm-sidebar-under #sidebar.sidebar-main.hotel-sidebar,
body.hb-lm-sidebar-under #sidebar.sidebar-main.sidebar-saas{
  z-index:1 !important; pointer-events:none !important;
}

/* Diálogo: entra con un "pop" (leve subida + escala) — anula lmPop en todos los tamaños */
#modalLimpieza.lm-overlay .lm-dialog,
#modalLimpieza.lm-overlay > .lm-dialog{
  animation:none !important;
  opacity:0; transform:translateY(18px) scale(.965); transform-origin:center bottom;
  transition:opacity .3s ease, transform .42s cubic-bezier(.22,1,.36,1);
  will-change:transform, opacity;
}
#modalLimpieza.lm-overlay.is-open .lm-dialog,
#modalLimpieza.lm-overlay.is-open > .lm-dialog{
  opacity:1 !important; transform:translateY(0) scale(1) !important;
}

/* ── Skeleton: cubre el diálogo mientras "carga" y se desvanece al revelar el contenido ── */
#modalLimpieza .lm-skeleton{
  position:absolute; inset:0; z-index:9;
  display:flex; flex-direction:column;
  background:var(--lm-surface, #fff);
  border-radius:var(--lm-radius, 24px);
  opacity:1; transition:opacity .34s ease;
}
#modalLimpieza .lm-dialog:not(.is-loading) .lm-skeleton{ opacity:0; pointer-events:none; }
#modalLimpieza .lm-sk-head{
  flex:0 0 auto; min-height:94px; padding:20px 24px;
  display:flex; align-items:center; gap:14px;
  background:linear-gradient(135deg, #2E6FD2 0%, #3D82EA 58%, #60A1FA 100%);
  border-radius:var(--lm-radius, 24px) var(--lm-radius, 24px) 0 0;
}
#modalLimpieza .lm-sk-htext{ flex:1; min-width:0; display:grid; gap:9px; }
#modalLimpieza .lm-sk-toolbar{
  flex:0 0 auto; display:flex; align-items:center; justify-content:space-between; gap:16px;
  padding:14px 20px; border-bottom:1px solid var(--lm-line, #E7E1D4);
}
#modalLimpieza .lm-sk-pills{ display:flex; gap:8px; }
#modalLimpieza .lm-sk-body{ flex:1 1 auto; display:flex; flex-direction:column; gap:10px; padding:16px 20px; overflow:hidden; }
#modalLimpieza .lm-sk-room{
  display:flex; align-items:center; gap:14px; padding:14px 16px;
  border:1px solid var(--lm-line, #E7E1D4); border-radius:15px;
}
#modalLimpieza .lm-sk-lines{ flex:1; min-width:0; display:grid; gap:8px; }
#modalLimpieza .lm-sk-footer{
  flex:0 0 auto; display:flex; justify-content:flex-end; gap:10px;
  padding:16px 20px; border-top:1px solid var(--lm-line, #E7E1D4);
}
#modalLimpieza .lm-sk-bar{
  position:relative; overflow:hidden; border-radius:9px;
  background:color-mix(in srgb, var(--lm-ink-faint, #8B94A3) 20%, var(--lm-surface-warm, #F5F5F7));
}
#modalLimpieza .lm-sk-head .lm-sk-bar{ background:rgba(255,255,255,.28); }
#modalLimpieza .lm-sk-bar::after{
  content:''; position:absolute; inset:0; transform:translateX(-100%);
  background:linear-gradient(90deg, transparent, color-mix(in srgb, #fff 72%, transparent), transparent);
  animation:lmSkShimmer 1.25s ease-in-out infinite;
}
#modalLimpieza .lm-sk-head .lm-sk-bar::after{ background:linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent); }
/* Modificadores de tamaño (después de la base para ganar) */
#modalLimpieza .lm-sk-bar.sk-emblem{ flex:0 0 50px; width:50px; height:50px; border-radius:14px; }
#modalLimpieza .lm-sk-bar.sk-title{ height:16px; width:60%; }
#modalLimpieza .lm-sk-bar.sk-sub{ height:11px; width:44%; }
#modalLimpieza .lm-sk-bar.sk-count{ height:22px; width:min(220px, 60%); }
#modalLimpieza .lm-sk-bar.sk-pill{ height:38px; width:92px; border-radius:11px; }
#modalLimpieza .lm-sk-bar.sk-em{ flex:0 0 44px; width:44px; height:44px; border-radius:12px; }
#modalLimpieza .lm-sk-bar.sk-name{ height:15px; width:52%; }
#modalLimpieza .lm-sk-bar.sk-meta{ height:10px; width:74%; }
#modalLimpieza .lm-sk-bar.sk-btn{ height:48px; width:150px; border-radius:14px; }
#modalLimpieza .lm-sk-bar.sk-btn--sm{ width:104px; }
@keyframes lmSkShimmer{ 100%{ transform:translateX(100%); } }

/* Respeta a quien prefiere menos movimiento */
@media (prefers-reduced-motion: reduce){
  #modalLimpieza.lm-overlay,
  #modalLimpieza.lm-overlay .lm-dialog,
  #modalLimpieza .lm-skeleton{ transition-duration:.01ms !important; }
  #modalLimpieza.lm-overlay .lm-dialog{ transform:none !important; }
  #modalLimpieza .lm-sk-bar::after{ animation:none !important; }
}
</style>

<style id="hb-reserve-caja-motion">
/* ══ Wizard "Crear reservación" (SweetAlert .hb-reserve-swal): entrada estilo Caja + skeleton ══
   La sidebar ya queda cubierta por el overlay de SweetAlert; aquí sumamos el "pop" de entrada
   (reemplaza el zoom por defecto de swal2) y un skeleton que se auto-desvanece (~560ms). */

/* Entrada/salida como STEPPER direccional (customClass show/hide del wizard):
   - Primera apertura (data-hbdir="initial"): "pop" (sube + escala).
   - Avanzar (fwd): el paso actual desliza y se desvanece hacia la IZQUIERDA y el
     nuevo entra deslizando DESDE LA DERECHA → sensación de "seguir adelante".
   - Volver (back): al revés. El desplazamiento es corto (~36px), no vuela fuera
     de pantalla, para que se sienta conectado y no "desaparezca por completo". */
.swal2-popup.hb-reserve-swal.hb-reserve-swal-in{ animation:hbReservePop .42s cubic-bezier(.22,1,.36,1); }
.swal2-popup.hb-reserve-swal.hb-reserve-swal-out{ animation:hbReserveFadeOut .16s ease forwards; }

.hb-swal-sheet-container[data-hbdir="fwd"] .swal2-popup.hb-reserve-swal.hb-reserve-swal-in{ animation:hbReserveInRight .42s cubic-bezier(.22,1,.36,1); }
.hb-swal-sheet-container[data-hbdir="fwd"] .swal2-popup.hb-reserve-swal.hb-reserve-swal-out{ animation:hbReserveOutLeft .24s cubic-bezier(.45,0,.7,.25) forwards; }
.hb-swal-sheet-container[data-hbdir="back"] .swal2-popup.hb-reserve-swal.hb-reserve-swal-in{ animation:hbReserveInLeft .42s cubic-bezier(.22,1,.36,1); }
.hb-swal-sheet-container[data-hbdir="back"] .swal2-popup.hb-reserve-swal.hb-reserve-swal-out{ animation:hbReserveOutRight .24s cubic-bezier(.45,0,.7,.25) forwards; }

@keyframes hbReservePop{ 0%{ opacity:0; transform:translateY(18px) scale(.965); } 100%{ opacity:1; transform:translateY(0) scale(1); } }
@keyframes hbReserveInRight{ 0%{ opacity:0; transform:translateX(38px) scale(.99); } 100%{ opacity:1; transform:none; } }
@keyframes hbReserveInLeft{ 0%{ opacity:0; transform:translateX(-38px) scale(.99); } 100%{ opacity:1; transform:none; } }
@keyframes hbReserveOutLeft{ 0%{ opacity:1; transform:none; } 100%{ opacity:0; transform:translateX(-30px) scale(.99); } }
@keyframes hbReserveOutRight{ 0%{ opacity:1; transform:none; } 100%{ opacity:0; transform:translateX(30px) scale(.99); } }
@keyframes hbReserveFadeOut{ to{ opacity:0; } }

/* ── Skeleton: cubre el shell mientras "carga" y se desvanece revelando el contenido ── */
.hb-reserve-shell{ position:relative; }
.hb-reserve-swal .hb-reserve-sk{
  position:absolute; inset:0; z-index:6;
  display:grid; grid-template-columns:inherit;   /* sigue el colapso responsivo del shell */
  background:var(--hb-reserve-surface, #FBFAF7);
  border-radius:inherit; overflow:hidden;
  animation:hbReserveSkHide .34s ease 560ms forwards;
}
.hb-reserve-swal .hb-reserve-sk__side{
  padding:26px; display:flex; flex-direction:column; gap:14px;
  background:linear-gradient(155deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #101827), color-mix(in srgb, var(--brand-primary, #1B2746) 78%, #1F2937));
}
.hb-reserve-swal .hb-reserve-sk__main{ padding:30px 34px; display:flex; flex-direction:column; gap:14px; }
.hb-reserve-swal .hb-reserve-sk__datebox{ display:flex; flex-direction:column; gap:10px; margin-top:6px; }
.hb-reserve-swal .hb-reserve-sk__steps{ display:flex; flex-direction:column; gap:12px; margin-top:auto; }
.hb-reserve-swal .hb-reserve-sk__cards{ display:flex; flex-direction:column; gap:14px; margin-top:6px; }
.hb-reserve-swal .hb-reserve-sk__foot{ margin-top:auto; display:flex; justify-content:flex-end; }
.hb-reserve-swal .hb-reserve-sk-card{
  height:88px; border-radius:16px; position:relative; overflow:hidden;
  border:1px solid var(--hb-reserve-line, #E8DFD1); background:var(--hb-reserve-paper, #FFFEFB);
}
.hb-reserve-swal .hb-reserve-sk-bar{
  position:relative; overflow:hidden; border-radius:8px;
  background:color-mix(in srgb, var(--hb-reserve-muted, #6B7686) 20%, var(--hb-reserve-paper, #FFFEFB));
}
.hb-reserve-swal .hb-reserve-sk__side .hb-reserve-sk-bar{ background:rgba(255,255,255,.16); }
.hb-reserve-swal .hb-reserve-sk-bar::after,
.hb-reserve-swal .hb-reserve-sk-card::after{
  content:''; position:absolute; inset:0; transform:translateX(-100%);
  background:linear-gradient(90deg, transparent, color-mix(in srgb, #fff 55%, transparent), transparent);
  animation:hbReserveShimmer 1.25s ease-in-out infinite;
}
.hb-reserve-swal .hb-reserve-sk__side .hb-reserve-sk-bar::after{ background:linear-gradient(90deg, transparent, rgba(255,255,255,.34), transparent); }
/* Tamaños (después de la base para ganar) */
.hb-reserve-swal .hb-reserve-sk-bar.sk-eyebrow{ height:9px; width:44%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-h2{ height:20px; width:72%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-h2--2{ width:52%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-p{ height:11px; width:88%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-date{ height:38px; width:100%; border-radius:12px; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-step{ height:14px; width:62%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-title{ height:22px; width:66%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-line{ height:11px; width:92%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-line--short{ width:60%; }
.hb-reserve-swal .hb-reserve-sk-bar.sk-btn{ height:44px; width:150px; border-radius:12px; }
@keyframes hbReserveShimmer{ 100%{ transform:translateX(100%); } }
@keyframes hbReserveSkHide{ from{ opacity:1; } to{ opacity:0; visibility:hidden; } }

/* Respeta a quien prefiere menos movimiento */
@media (prefers-reduced-motion: reduce){
  .swal2-popup.hb-reserve-swal.hb-reserve-swal-in,
  .swal2-popup.hb-reserve-swal.hb-reserve-swal-out{ animation-duration:.01ms !important; }
  .hb-reserve-swal .hb-reserve-sk{ animation:hbReserveSkHide .01s linear 300ms forwards; }
  .hb-reserve-swal .hb-reserve-sk-bar::after,
  .hb-reserve-swal .hb-reserve-sk-card::after{ animation:none; }
}
</style>

<script>
/* Filtro client-side (sin recargar): chips de estado + tipo + piso + búsqueda, con animación */
(function(){
  window.__hbF = { estado:<?= json_encode((string) $estadoActual) ?>, tipo:'', piso:'', q:'' };
  window.__hbLastHistoryUrl = window.location.href;
})();
function hbSyncStats(){
  var estado = window.__hbF && window.__hbF.estado ? window.__hbF.estado : '';
  document.querySelectorAll('#hbStats .hb-stat').forEach(function(stat){
    stat.classList.toggle('is-filter-active', !!estado && (stat.dataset.estado || '') === estado);
  });
}
function hbFilterEstado(estado){
  if (estado === 'disponible_fecha') return 'disponible';
  if (estado === 'ocupada_fecha') return 'ocupada';
  return estado;
}
function hbSyncEstadoUrl(estado){
  if (!window.history || !window.URL || !window.URLSearchParams) return;
  var url = new URL(window.location.href);
  if (estado) {
    url.searchParams.set('estado', estado);
  } else {
    url.searchParams.delete('estado');
  }
  var nextUrl = url.toString();
  if (nextUrl === window.__hbLastHistoryUrl) return;

  try {
    window.history.pushState({ hbEstado: estado || '' }, '', nextUrl);
    window.__hbLastHistoryUrl = nextUrl;
  } catch(e) {
    try {
      window.history.replaceState({ hbEstado: estado || '' }, '', nextUrl);
      window.__hbLastHistoryUrl = nextUrl;
    } catch(_) {}
  }
}
function hbEstadoDesdeUrl(){
  try {
    return new URL(window.location.href).searchParams.get('estado') || '';
  } catch(e) {
    return '';
  }
}
function hbMarcarEstadoActivo(){
  document.querySelectorAll('.hb-chip, .hb-mobile-lg').forEach(function(c){
    c.classList.toggle('is-active', (c.getAttribute('data-estado') || '') === window.__hbF.estado);
  });
}
function hbApplyFilters(){
  var f = window.__hbF;
  var se = document.getElementById('hbSearch'); f.q = (se ? se.value : '').trim().toLowerCase();
  var t = document.getElementById('hbTipo'); f.tipo = t ? t.value : '';
  var p = document.getElementById('hbPiso'); f.piso = p ? p.value : '';
  var grid = document.getElementById('habitaciones-grid'); if(!grid) return;
  var total = 0;
  var shouldAnimateCards = !(window.matchMedia && window.matchMedia('(max-width: 640px), (hover: none) and (pointer: coarse)').matches);
  grid.querySelectorAll('.flip-card').forEach(function(card){
    var show = true;
    var cardEstado = hbFilterEstado(card.dataset.estado || '');
    if(f.estado && cardEstado !== f.estado) show = false;
    if(f.tipo && (card.dataset.tipo||'') !== f.tipo) show = false;
    if(f.piso && String(card.dataset.piso||'') !== String(f.piso)) show = false;
    if(f.q && (card.dataset.q||'').indexOf(f.q) === -1) show = false;
    card.classList.remove('flipped');
    card.classList.toggle('hb-hidden', !show);
    if(show) total++;
  });
  grid.querySelectorAll('.floor-section').forEach(function(sec){
    var vis = sec.querySelectorAll('.flip-card:not(.hb-hidden)').length;
    sec.classList.toggle('hb-hidden', vis === 0);
    var ct = sec.querySelector('.floor-ct');
    if(ct) ct.textContent = vis + ' ' + (vis === 1 ? 'habitaci\u00f3n' : 'habitaciones');
    var i = 0;
    sec.querySelectorAll('.flip-card:not(.hb-hidden)').forEach(function(c){
      if (shouldAnimateCards) {
        try { c.animate([{opacity:0, transform:'translateY(10px) scale(.985)'},{opacity:1, transform:'none'}], {duration:300, delay:i*28, easing:'cubic-bezier(.22,1,.36,1)', fill:'backwards'}); } catch(e){}
      }
      i++;
    });
  });
  var nr = document.getElementById('hbNoResults'); if(nr) nr.style.display = total === 0 ? '' : 'none';
  hbSyncStats();
}
function hbSetEstado(btn){
  window.__hbF.estado = btn.getAttribute('data-estado') || '';
  hbSyncEstadoUrl(window.__hbF.estado);
  hbMarcarEstadoActivo();
  hbApplyFilters();
}
function hbClearFilters(){
  window.__hbF = { estado:'', tipo:'', piso:'', q:'' };
  hbSyncEstadoUrl('');
  var s = document.getElementById('hbSearch'); if(s) s.value = '';
  var t = document.getElementById('hbTipo'); if(t) t.value = '';
  var p = document.getElementById('hbPiso'); if(p) p.value = '';
  hbMarcarEstadoActivo();
  hbApplyFilters();
}
window.addEventListener('popstate', function(){
  if (!window.__hbF) return;
  window.__hbF.estado = hbEstadoDesdeUrl();
  hbMarcarEstadoActivo();
  hbApplyFilters();
  window.__hbLastHistoryUrl = window.location.href;
});
document.addEventListener('DOMContentLoaded', function(){
  if (window.history && window.history.replaceState) {
    try {
      window.history.replaceState({ hbEstado: hbEstadoDesdeUrl() }, '', window.location.href);
    } catch(e) {}
  }
  hbApplyFilters();
});
</script>

<!-- JavaScript -->
<script>
// Función para hacer flip con clic
// Reemplazar la función toggleFlip con esta versión mejorada
function hbIsActivationKey(event) {
    return event && (event.key === 'Enter' || event.key === ' ');
}

function hbStatKey(event, element) {
    if (!hbIsActivationKey(event)) return;
    event.preventDefault();
    hbSetEstado(element);
}

function hbCardKey(event, card) {
    if (!hbIsActivationKey(event)) return;
    event.preventDefault();
    toggleFlip(card, event);
}

function hbPressClick(event, element) {
    if (!hbIsActivationKey(event)) return;
    event.preventDefault();
    element.click();
}

function hbIsMobileRooms() {
    return window.matchMedia && window.matchMedia('(max-width: 640px)').matches;
}

function hbOpenMobileRoomSheet(card) {
    const back = card.querySelector('.flip-card-back');
    const sheet = document.getElementById('hbMobileRoomSheet');
    const backdrop = document.getElementById('hbMobileSheetBack');
    const content = document.getElementById('hbMobileSheetContent');
    if (!back || !sheet || !backdrop || !content) return;

    document.querySelectorAll('.flip-card.flipped').forEach(function(openCard) {
        openCard.classList.remove('flipped');
    });

    const cardStyles = window.getComputedStyle(card);
    const sheetColor = (cardStyles.getPropertyValue('--sheet-c') || '').trim();
    const accentColor = (cardStyles.getPropertyValue('--room-accent-color') || '').trim();
    sheet.style.setProperty('--sheet-c', sheetColor || accentColor || 'var(--brand-primary,#1B2746)');

    content.innerHTML = '';
    content.appendChild(back.cloneNode(true));
    content.dataset.hbRoomContent = content.innerHTML;
    sheet.classList.remove('is-reserving');
    backdrop.classList.add('is-open');
    sheet.classList.add('is-open');
    backdrop.setAttribute('aria-hidden', 'false');
    sheet.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hb-mobile-sheet-open');
}

function hbCloseMobileRoomSheet() {
    const sheet = document.getElementById('hbMobileRoomSheet');
    const backdrop = document.getElementById('hbMobileSheetBack');
    const content = document.getElementById('hbMobileSheetContent');
    if (!sheet || !backdrop) return;

    sheet.classList.remove('is-open');
    sheet.classList.remove('is-reserving');
    backdrop.classList.remove('is-open');
    backdrop.setAttribute('aria-hidden', 'true');
    sheet.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hb-mobile-sheet-open');
    window.setTimeout(function() {
        if (content && !sheet.classList.contains('is-open')) {
            content.innerHTML = '';
            delete content.dataset.hbRoomContent;
        }
    }, 380);
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') hbCloseMobileRoomSheet();
});
window.addEventListener('resize', function() {
    if (!hbIsMobileRooms()) hbCloseMobileRoomSheet();
});

function toggleFlip(card, event) {
    // Prevenir propagación si se hace clic en un enlace o botón
    if (event) {
        const target = event.target;
        if (target.closest('a') || target.closest('button') || target.closest('.btn-action')) {
            event.stopPropagation();
            return;
        }
    }

    if (hbIsMobileRooms()) {
        if (event) event.preventDefault();
        hbOpenMobileRoomSheet(card);
        return;
    }

    // Mantener una sola habitacion abierta evita solapes en movil.
    const wasFlipped = card.classList.contains('flipped');
    document.querySelectorAll('.flip-card.flipped').forEach(function(openCard) {
        if (openCard !== card) {
            openCard.classList.remove('flipped');
        }
    });
    card.classList.toggle('flipped', !wasFlipped);
}

function crearReservacionConFecha(habitacionId, fecha) {
    // Agregar T00:00:00 para evitar problemas de zona horaria
    const fechaEntrada = new Date(fecha + 'T00:00:00');
    const fechaSalida = new Date(fechaEntrada);
    fechaSalida.setDate(fechaSalida.getDate() + 1);

    // Función auxiliar para formatear fecha en formato local YYYY-MM-DD
    function formatearFechaLocal(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }

    const fechaSalidaStr = formatearFechaLocal(fechaSalida);

    window.location.href = '<?= url('reservaciones/crear') ?>?' +
        'habitacion_id=' + habitacionId +
        '&fecha_entrada=' + fecha +
        '&fecha_salida=' + fechaSalidaStr;
}

function hbReservaDatosDesdeFecha(fecha) {
    const hoy = new Date();
    const fechaEntrada = new Date(fecha + 'T00:00:00');
    const fechaSalida = new Date(fechaEntrada);
    fechaSalida.setDate(fechaSalida.getDate() + 1);

    function formatearFechaLocal(fechaObj) {
        const anio = fechaObj.getFullYear();
        const mes = String(fechaObj.getMonth() + 1).padStart(2, '0');
        const dia = String(fechaObj.getDate()).padStart(2, '0');
        return `${anio}-${mes}-${dia}`;
    }

    return {
        fechaEntrada: fecha,
        fechaSalida: formatearFechaLocal(fechaSalida),
        horaActual: hoy.toTimeString().slice(0, 5)
    };
}

function obtenerDatosReservaDefault() {
    const hoy = new Date();
    const manana = new Date(hoy);
    manana.setDate(manana.getDate() + 1);

    // Función auxiliar para formatear fecha en formato local YYYY-MM-DD
    function formatearFechaLocal(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }

    return {
        fechaEntrada: formatearFechaLocal(hoy),
        fechaSalida: formatearFechaLocal(manana),
        horaActual: hoy.toTimeString().slice(0, 5)
    };
}

const hbHotelCheckinHora = <?= json_encode($hb_hotel_checkin_hora, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const hbHotelCheckinHoraLabel = hbReservaFormatearHoraChip(hbHotelCheckinHora);

function hbReservaFormatearHoraChip(hora) {
    const match = String(hora || '').match(/^(\d{1,2}):(\d{2})/);
    if (!match) return '03:00 p. m.';

    const horas24 = parseInt(match[1], 10);
    const minutos = match[2];
    const periodo = horas24 >= 12 ? 'p. m.' : 'a. m.';
    const horas12 = horas24 % 12 || 12;

    return `${String(horas12).padStart(2, '0')}:${minutos} ${periodo}`;
}

let hbReservaSwalTimer = null;

/* Dirección del stepper del wizard: 'initial' (pop de entrada), 'fwd' (avanza,
   desliza izq→entra der) o 'back' (Volver, entra izq). Al fijarla también se
   actualiza el contenedor swal ACTUAL para que su salida sea del lado correcto. */
window.__hbReserveDir = 'initial';
function hbReserveSetDir(dir) {
    window.__hbReserveDir = dir || 'initial';
    var c = document.querySelector('.hb-swal-sheet-container');
    if (c) c.setAttribute('data-hbdir', window.__hbReserveDir);
}
/* Config compartida de animación direccional para los Swal del wizard. */
function hbReserveSwalAnim() {
    return {
        showClass: { popup: 'hb-reserve-swal-in', backdrop: 'swal2-backdrop-show' },
        hideClass: { popup: 'hb-reserve-swal-out', backdrop: 'swal2-backdrop-hide' },
        willOpen: function (popup) {
            try {
                var c = popup && popup.closest ? popup.closest('.swal2-container') : null;
                if (c) c.setAttribute('data-hbdir', window.__hbReserveDir || 'initial');
            } catch (e) {}
        }
    };
}

function hbCerrarSwalReserva(callback, delay = 160) {
    if (hbReservaSwalTimer) {
        clearTimeout(hbReservaSwalTimer);
        hbReservaSwalTimer = null;
    }

    const run = () => {
        hbReservaSwalTimer = null;
        if (typeof callback === 'function') {
            callback();
        }
    };

    const swalVisible = typeof Swal !== 'undefined'
        && typeof Swal.isVisible === 'function'
        && Swal.isVisible();

    if (swalVisible && typeof Swal.close === 'function') {
        Swal.close();
    }

    hbReservaSwalTimer = window.setTimeout(run, swalVisible ? delay : Math.min(delay, 80));
}

function hbReservaCalcularNoches(fechaEntrada, fechaSalida) {
    const entrada = new Date(fechaEntrada + 'T00:00:00');
    const salida = new Date(fechaSalida + 'T00:00:00');
    const diff = Math.round((salida - entrada) / 86400000);
    const noches = Number.isFinite(diff) && diff > 0 ? diff : 1;
    return noches + ' ' + (noches === 1 ? 'noche' : 'noches');
}

function hbReservaSidebar(fechaEntrada, fechaSalida, paso, habitacionId) {
    const entradaLabel = formatearFechaReservaCorta(new Date(fechaEntrada + 'T00:00:00'));
    const salidaLabel = formatearFechaReservaCorta(new Date(fechaSalida + 'T00:00:00'));
    const nochesLabel = hbReservaCalcularNoches(fechaEntrada, fechaSalida);
    const pasoActual = parseInt(paso, 10) || 1;
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const detalle = tieneHabitacion
        ? 'Configura los datos iniciales para preparar esta habitacion.'
        : 'Configura los datos iniciales para preparar la estancia del huesped.';

    return `
        <aside class="hb-reserve-side" aria-label="Resumen de nueva reservacion">
            <span class="hb-reserve-side__eyebrow">Nueva reservacion</span>
            <h2>Crear<br>reservacion</h2>
            <p>${detalle}</p>

            <div class="hb-reserve-datebox">
                <div class="hb-reserve-dateitem">
                    <span class="hb-reserve-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Entrada</small><strong>${entradaLabel}</strong></span>
                </div>
                <div class="hb-reserve-dateitem">
                    <span class="hb-reserve-dateicon"><i class="fas fa-moon"></i></span>
                    <span><small>Noches</small><strong>${nochesLabel}</strong></span>
                </div>
                <div class="hb-reserve-dateitem">
                    <span class="hb-reserve-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Salida</small><strong>${salidaLabel}</strong></span>
                </div>
            </div>

            <ol class="hb-reserve-steps" aria-label="Progreso">
                <li class="${pasoActual === 1 ? 'is-active' : 'is-complete'}">
                    <span>1</span>
                    <strong>Tipo de cliente</strong>
                </li>
                <li class="${pasoActual === 2 ? 'is-active' : ''}">
                    <span>2</span>
                    <strong>Hora de llegada</strong>
                </li>
            </ol>
        </aside>
    `;
}

// Skeleton de carga del wizard (estilo Caja): réplica del shell de dos columnas.
// Se auto-desvanece por CSS (~560ms). Solo se inyecta en las plantillas de escritorio (Swal).
function hbReservaSkeleton() {
    return `
        <div class="hb-reserve-sk" aria-hidden="true">
            <div class="hb-reserve-sk__side">
                <div class="hb-reserve-sk-bar sk-eyebrow"></div>
                <div class="hb-reserve-sk-bar sk-h2"></div>
                <div class="hb-reserve-sk-bar sk-h2 sk-h2--2"></div>
                <div class="hb-reserve-sk-bar sk-p"></div>
                <div class="hb-reserve-sk__datebox">
                    <div class="hb-reserve-sk-bar sk-date"></div>
                    <div class="hb-reserve-sk-bar sk-date"></div>
                    <div class="hb-reserve-sk-bar sk-date"></div>
                </div>
                <div class="hb-reserve-sk__steps">
                    <div class="hb-reserve-sk-bar sk-step"></div>
                    <div class="hb-reserve-sk-bar sk-step"></div>
                </div>
            </div>
            <div class="hb-reserve-sk__main">
                <div class="hb-reserve-sk-bar sk-eyebrow"></div>
                <div class="hb-reserve-sk-bar sk-title"></div>
                <div class="hb-reserve-sk-bar sk-line"></div>
                <div class="hb-reserve-sk-bar sk-line sk-line--short"></div>
                <div class="hb-reserve-sk__cards">
                    <div class="hb-reserve-sk-card"></div>
                    <div class="hb-reserve-sk-card"></div>
                </div>
                <div class="hb-reserve-sk__foot"><div class="hb-reserve-sk-bar sk-btn"></div></div>
            </div>
        </div>
    `;
}

function hbReservaMobileIntro(fechaEntrada, fechaSalida, paso, habitacionId) {
    const entradaLabel = formatearFechaReservaCorta(new Date(fechaEntrada + 'T00:00:00'));
    const salidaLabel = formatearFechaReservaCorta(new Date(fechaSalida + 'T00:00:00'));
    const nochesLabel = hbReservaCalcularNoches(fechaEntrada, fechaSalida);
    const pasoActual = parseInt(paso, 10) || 1;
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const detalle = tieneHabitacion
        ? 'Configura los datos iniciales para preparar esta habitacion.'
        : 'Configura los datos iniciales para preparar la estancia del huesped.';

    return `
        <div class="hb-reserve-mobile-intro" role="group" aria-label="Resumen de nueva reservacion">
            <span class="hb-reserve-mobile-kicker">Nueva reservacion</span>
            <h2 class="hb-reserve-mobile-title">Crear reservacion</h2>
            <p class="hb-reserve-mobile-copy">${detalle}</p>

            <div class="hb-reserve-mobile-datebox">
                <div class="hb-reserve-mobile-dateitem">
                    <span class="hb-reserve-mobile-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Entrada</small><strong>${entradaLabel}</strong></span>
                </div>
                <div class="hb-reserve-mobile-dateitem">
                    <span class="hb-reserve-mobile-dateicon"><i class="fas fa-moon"></i></span>
                    <span><small>Noches</small><strong>${nochesLabel}</strong></span>
                </div>
                <div class="hb-reserve-mobile-dateitem">
                    <span class="hb-reserve-mobile-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Salida</small><strong>${salidaLabel}</strong></span>
                </div>
            </div>

            <div class="hb-reserve-mobile-progress" aria-label="Progreso">
                <span class="hb-reserve-mobile-progress-title">Paso ${pasoActual} de 2</span>
                <div class="hb-reserve-mobile-steps" role="list">
                    <div class="hb-reserve-mobile-step ${pasoActual === 1 ? 'is-active' : 'is-complete'}" role="listitem">
                        <span>1</span>
                        <strong>Tipo de cliente</strong>
                    </div>
                    <div class="hb-reserve-mobile-step ${pasoActual === 2 ? 'is-active' : ''}" role="listitem">
                        <span>2</span>
                        <strong>Hora de llegada</strong>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function hbFormatarFechaCorta(fechaISO) {
    if (!fechaISO) return '—';
    try {
        const [y, m, d] = fechaISO.split('-');
        const meses = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return `${parseInt(d)} ${meses[parseInt(m)-1]}`;
    } catch(e) { return fechaISO; }
}

function hbElegirTipoCliente(button, tipo) {
    const shell = button?.closest('.hb-reserve-shell');
    if (!shell) return;
    shell.dataset.tipo = tipo;
    shell.querySelectorAll('.hb-reserve-choice').forEach(option => {
        option.classList.toggle('is-selected', option === button);
        option.setAttribute('aria-pressed', option === button ? 'true' : 'false');
    });
}

function hbContinuarTipoClienteDesdeShell(habitacionId, fechaEntrada, fechaSalida, horaActual) {
    const shell = document.querySelector('.hb-reserve-shell');
    const tipo = shell?.dataset.tipo || 'nuevo';
    seleccionarTipoCliente(tipo, habitacionId, fechaEntrada, fechaSalida, horaActual);
}

function hbReservaSetHora(valor, button) {
    const input = document.getElementById('horaLlegadaRapida');
    if (input) input.value = valor;
    const shell = button?.closest('.hb-reserve-shell');
    if (shell) {
        shell.querySelectorAll('.hb-reserve-timechip').forEach(chip => chip.classList.remove('is-active'));
    }
    if (button) button.classList.add('is-active');
    const validation = document.getElementById('hbReserveValidation');
    if (validation) validation.classList.add('hidden');
}

function hbCrearReservacionDesdeHora(tipo, habitacionId, fechaEntrada, fechaSalida) {
    const input = document.getElementById('horaLlegadaRapida');
    const validation = document.getElementById('hbReserveValidation');
    const horaSeleccionada = input ? input.value : '';

    if (!horaSeleccionada) {
        if (validation) validation.classList.remove('hidden');
        if (input) input.focus();
        return;
    }

    continuarReservacionDesdeModal(tipo, habitacionId, fechaEntrada, fechaSalida, horaSeleccionada, false);
}
function abrirSelectorNuevaReserva(event) {
    if (typeof Swal === 'undefined') {
        return true;
    }

    if (event) event.preventDefault();
    mostrarSelectorTipoCliente(null, obtenerDatosReservaDefault());
    return false;
}

function crearReservacionRapida(habitacionId) {
    mostrarSelectorTipoCliente(habitacionId, obtenerDatosReservaDefault());
}

function hbReservarDesdeHabitacion(event, habitacionId, fecha = null) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    const desdeSheetMovil = !!event?.target?.closest('#hbMobileRoomSheet') && hbIsMobileRooms();
    if (desdeSheetMovil) {
        const datosReserva = fecha ? hbReservaDatosDesdeFecha(fecha) : obtenerDatosReservaDefault();
        hbMostrarReservaSheetTipo(habitacionId, datosReserva);
        return false;
    }

    if (fecha) {
        crearReservacionConFecha(habitacionId, fecha);
        return false;
    }

    crearReservacionRapida(habitacionId);
    return false;
}

function hbRenderReservaEnSheet(html) {
    const sheet = document.getElementById('hbMobileRoomSheet');
    const content = document.getElementById('hbMobileSheetContent');
    if (!sheet || !content) return false;

    content.innerHTML = html;
    content.scrollTop = 0;
    sheet.classList.add('is-reserving');
    return true;
}

function hbReservaSheetVolverDetalle() {
    const sheet = document.getElementById('hbMobileRoomSheet');
    const content = document.getElementById('hbMobileSheetContent');
    if (!sheet || !content) return;

    if (content.dataset.hbRoomContent) {
        content.innerHTML = content.dataset.hbRoomContent;
        content.scrollTop = 0;
        sheet.classList.remove('is-reserving');
        return;
    }

    hbCloseMobileRoomSheet();
}

function hbMostrarReservaSheetTipo(habitacionId, datosReserva) {
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const habitacionArg = tieneHabitacion ? parseInt(habitacionId, 10) : 'null';
    const fechaEntrada = datosReserva.fechaEntrada;
    const fechaSalida = datosReserva.fechaSalida;
    const horaActual = datosReserva.horaActual;

    hbRenderReservaEnSheet(`
        <div class="hb-reserve-shell hb-reserve-shell--sheet" data-tipo="nuevo">
            ${hbReservaSidebar(fechaEntrada, fechaSalida, 1, habitacionId)}
            <section class="hb-reserve-main" aria-label="Tipo de cliente">
                ${hbReservaMobileIntro(fechaEntrada, fechaSalida, 1, habitacionId)}
                <span class="hb-reserve-main__eyebrow">Paso 1 de 2</span>
                <h3>&iquest;Para quien es la reservacion?</h3>

                <div class="hb-reserve-choice-list" role="group" aria-label="Tipo de cliente">
                    <button onclick="hbElegirTipoCliente(this, 'nuevo'); return false;"
                            type="button"
                            class="hb-reserve-choice hb-reserve-choice--new is-selected"
                            aria-pressed="true">
                        <span class="hb-reserve-choice__icon"><i class="fas fa-user-plus"></i></span>
                        <span class="hb-reserve-choice__copy">
                            <span><strong>Cliente nuevo</strong><em>Registro</em></span>
                            <small>Registra al huesped y vuelve con esta habitacion lista.</small>
                        </span>
                        <span class="hb-reserve-choice__check"><i class="fas fa-check"></i></span>
                    </button>
                    <button onclick="hbElegirTipoCliente(this, 'existente'); return false;"
                            type="button"
                            class="hb-reserve-choice hb-reserve-choice--existing"
                            aria-pressed="false">
                        <span class="hb-reserve-choice__icon"><i class="fas fa-user-check"></i></span>
                        <span class="hb-reserve-choice__copy">
                            <span><strong>Cliente registrado</strong><em>Existente</em></span>
                            <small>Continua directo a buscar al huesped.</small>
                        </span>
                        <span class="hb-reserve-choice__check"><i class="fas fa-check"></i></span>
                    </button>
                </div>

                <div class="hb-reserve-footer">
                    <button type="button" onclick="hbReservaSheetVolverDetalle()" class="hb-reserve-btn hb-reserve-btn--ghost">
                        <i class="fas fa-arrow-left"></i> Detalles
                    </button>
                    <button type="button" onclick="hbReservaSheetIrHora(${habitacionArg}, '${fechaEntrada}', '${fechaSalida}', '${horaActual}')" class="hb-reserve-btn hb-reserve-btn--primary">
                        Continuar <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </section>
        </div>
    `);
}

function hbReservaSheetIrHora(habitacionId, fechaEntrada, fechaSalida, horaActual) {
    const shell = document.querySelector('#hbMobileSheetContent .hb-reserve-shell');
    const tipo = shell?.dataset.tipo || 'nuevo';
    hbMostrarReservaSheetHora(tipo, habitacionId, fechaEntrada, fechaSalida, horaActual);
}

function hbMostrarReservaSheetHora(tipo, habitacionId, fechaEntrada, fechaSalida, horaActual) {
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const habitacionArg = tieneHabitacion ? parseInt(habitacionId, 10) : 'null';

    hbRenderReservaEnSheet(`
        <div class="hb-reserve-shell hb-reserve-shell--sheet" data-tipo="${tipo}">
            ${hbReservaSidebar(fechaEntrada, fechaSalida, 2, habitacionId)}
            <section class="hb-reserve-main" aria-label="Hora de llegada">
                ${hbReservaMobileIntro(fechaEntrada, fechaSalida, 2, habitacionId)}
                <span class="hb-reserve-main__eyebrow">Paso 2 de 2</span>
                <h3>&iquest;A que hora llega?</h3>

                <div class="hb-reserve-timechips" aria-label="Opciones rapidas de hora">
                    <button type="button" onclick="hbReservaSetHora('${horaActual}', this)" class="hb-reserve-timechip is-active"><i class="far fa-clock"></i> Ahora</button>
                    <button type="button" onclick="hbReservaSetHora(hbHotelCheckinHora, this)" class="hb-reserve-timechip" title="Hora de check-in configurada" aria-label="Usar hora de check-in configurada: ${hbHotelCheckinHoraLabel}">${hbHotelCheckinHoraLabel}</button>
                    <button type="button" onclick="hbReservaSetHora('20:00', this)" class="hb-reserve-timechip">08:00 p. m.</button>
                    <button type="button" onclick="continuarReservacionDesdeModal('${tipo}', ${habitacionArg}, '${fechaEntrada}', '${fechaSalida}', '', true); return false;" class="hb-reserve-timechip"><i class="far fa-calendar-plus"></i> Definir despues</button>
                </div>

                <label class="hb-reserve-timefield" for="horaLlegadaRapida">
                    <i class="far fa-clock"></i>
                    <input type="time" id="horaLlegadaRapida" value="${horaActual}" class="hb-arrival-input brand-focus" oninput="document.getElementById('hbReserveValidation')?.classList.add('hidden')">
                </label>
                <p id="hbReserveValidation" class="hb-reserve-validation hidden">Ingresa la hora de llegada o usa Definir despues.</p>

                <div class="hb-reserve-footer">
                    <button type="button" onclick="hbMostrarReservaSheetTipo(${habitacionArg}, { fechaEntrada: '${fechaEntrada}', fechaSalida: '${fechaSalida}', horaActual: '${horaActual}' })" class="hb-reserve-btn hb-reserve-btn--ghost">
                        <i class="fas fa-arrow-left"></i> Volver
                    </button>
                    <button type="button" onclick="hbCrearReservacionDesdeHora('${tipo}', ${habitacionArg}, '${fechaEntrada}', '${fechaSalida}')" class="hb-reserve-btn hb-reserve-btn--primary">
                        <i class="fas fa-check"></i> Crear
                    </button>
                </div>
            </section>
        </div>
    `);
}

function mostrarSelectorTipoCliente(habitacionId, datosReserva, dir) {
    window.__hbReserveDir = dir || 'initial';
    const tieneHabitacion = habitacionId !== null && habitacionId !== undefined && habitacionId !== '';
    const habitacionArg = tieneHabitacion ? parseInt(habitacionId, 10) : 'null';
    const fechaEntrada = datosReserva.fechaEntrada;
    const fechaSalida = datosReserva.fechaSalida;
    const horaActual = datosReserva.horaActual;
    const textoAyuda = tieneHabitacion
        ? 'Elige si vas a registrar un huesped nuevo o si usaras un cliente que ya existe para esta habitacion.'
        : 'Elige si vas a registrar un huesped nuevo o si la reservacion sera para un cliente que ya existe.';

    Swal.fire({
        ...hbReserveSwalAnim(),
        title: '',
        html: `
            <div class="hb-reserve-shell" data-tipo="nuevo">
                ${(window.__hbReserveDir || 'initial') === 'initial' ? hbReservaSkeleton() : ''}
                ${hbReservaSidebar(fechaEntrada, fechaSalida, 1, habitacionId)}
                <section class="hb-reserve-main" aria-label="Tipo de cliente">
                    ${hbReservaMobileIntro(fechaEntrada, fechaSalida, 1, habitacionId)}
                    <div class="hb-reserve-mobile-dates" aria-hidden="true">
                        <span><i class="fas fa-calendar-day"></i> ${hbFormatarFechaCorta(fechaEntrada)}</span>
                        <i class="fas fa-arrow-right" style="font-size:.6rem;opacity:.4"></i>
                        <span><i class="fas fa-calendar-day"></i> ${hbFormatarFechaCorta(fechaSalida)}</span>
                    </div>
                    <span class="hb-reserve-main__eyebrow">Paso 1 de 2</span>
                    <h3>&iquest;Para quien es la reservacion?</h3>
                    <p>${textoAyuda}</p>

                    <div class="hb-reserve-choice-list" role="group" aria-label="Tipo de cliente">
                        <button onclick="hbElegirTipoCliente(this, 'nuevo'); return false;"
                                type="button"
                                class="hb-reserve-choice hb-reserve-choice--new is-selected"
                                aria-pressed="true">
                            <span class="hb-reserve-choice__icon"><i class="fas fa-user-plus"></i></span>
                            <span class="hb-reserve-choice__copy">
                                <span><strong>Cliente nuevo</strong><em>Registro</em></span>
                                <small>${tieneHabitacion ? 'Crea el huesped y vuelve al flujo con esta habitacion lista.' : 'Registra al huesped y vuelve al flujo con las fechas listas.'}</small>
                            </span>
                            <span class="hb-reserve-choice__check"><i class="fas fa-check"></i></span>
                        </button>
                        <button onclick="hbElegirTipoCliente(this, 'existente'); return false;"
                                type="button"
                                class="hb-reserve-choice hb-reserve-choice--existing"
                                aria-pressed="false">
                            <span class="hb-reserve-choice__icon"><i class="fas fa-user-check"></i></span>
                            <span class="hb-reserve-choice__copy">
                                <span><strong>Cliente registrado</strong><em>Existente</em></span>
                                <small>${tieneHabitacion ? 'Usa un huesped ya creado y completa los datos de llegada.' : 'Continua directo a crear la reservacion y busca al huesped.'}</small>
                            </span>
                            <span class="hb-reserve-choice__check"><i class="fas fa-check"></i></span>
                        </button>
                    </div>

                    <div class="hb-reserve-footer hb-reserve-footer--single">
                        <button type="button" onclick="hbContinuarTipoClienteDesdeShell(${habitacionArg}, '${fechaEntrada}', '${fechaSalida}', '${horaActual}')" class="hb-reserve-btn hb-reserve-btn--primary">
                            Continuar <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: false,
        showCloseButton: true,
        allowOutsideClick: true,
        allowEscapeKey: true,
        returnFocus: false,
        closeButtonAriaLabel: 'Cerrar',
        width: '820px',
        customClass: {
            container: 'hb-swal-sheet-container',
            popup: 'hb-swal hb-swal-client hb-reserve-swal',
            htmlContainer: 'hb-swal-html'
        },
        buttonsStyling: false
    });
}
// Función para entrega rápida de control remoto desde el índice de habitaciones
function entregarRemotoRapido(habitacionId, reservacionId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Entregar Control Remoto',
            html: `
                <div class="text-left space-y-4">
                    <!-- Tipo de identificación -->
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-1"></i>
                            Tipo de identificación:
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="ine" class="mr-2" checked>
                                <i class="fas fa-id-card mr-2 text-purple-600"></i>
                                INE
                            </label>
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="licencia" class="mr-2">
                                <i class="fas fa-car mr-2 text-purple-600"></i>
                                Licencia de Conducir
                            </label>
                            <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                                <input type="radio" name="tipo_id_rapido" value="otro" class="mr-2">
                                <i class="fas fa-passport mr-2 text-purple-600"></i>
                                Otro
                            </label>
                        </div>
                    </div>

                    <!-- Nombre del propietario de la INE (NUEVO en v2.0) -->
                    <div class="mb-4">
                        <p class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Nombre del propietario de la identificación:
                        </p>
                        <input type="text"
                               id="nombre_propietario_ine_rapido"
                               class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="Ej: Juan Pérez García">
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Ingrese el nombre completo tal como aparece en la identificación
                        </p>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--brand-primary, #1B2746)',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, entregar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const tipoId = document.querySelector('input[name="tipo_id_rapido"]:checked');
                if (!tipoId) {
                    Swal.showValidationMessage('Debe seleccionar el tipo de identificación');
                    return false;
                }

                const nombrePropietario = document.getElementById('nombre_propietario_ine_rapido').value.trim();
                if (!nombrePropietario) {
                    Swal.showValidationMessage('Debe ingresar el nombre del propietario de la identificación');
                    return false;
                }

                return {
                    tipo_identificacion: tipoId.value,
                    nombre_propietario: nombrePropietario
                };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-remoto") ?>';

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

                // Tipo identificación
                const tipoIdInput = document.createElement('input');
                tipoIdInput.type = 'hidden';
                tipoIdInput.name = 'tipo_identificacion';
                tipoIdInput.value = result.value.tipo_identificacion;
                form.appendChild(tipoIdInput);

                // Nombre del propietario de la INE (NUEVO campo v2.0)
                const nombreInput = document.createElement('input');
                nombreInput.type = 'hidden';
                nombreInput.name = 'nombre_propietario_ine';
                nombreInput.value = result.value.nombre_propietario;
                form.appendChild(nombreInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        // Fallback sin SweetAlert
        const tipoId = prompt('Ingrese tipo de identificación:\n1 = INE\n2 = Licencia\n3 = Otro');
        if (tipoId) {
            let tipoIdentificacion = '';
            switch(tipoId) {
                case '1': tipoIdentificacion = 'ine'; break;
                case '2': tipoIdentificacion = 'licencia'; break;
                case '3': tipoIdentificacion = 'otro'; break;
                default:
                    window.msToast('error', null, 'Opción inválida');
                    return;
            }

            const nombrePropietario = prompt('Ingrese el nombre del propietario de la identificación:');
            if (!nombrePropietario) {
                window.msToast('warning', null, 'Debe ingresar el nombre del propietario');
                return;
            }

            if (confirm('¿Entregar control remoto con ' + tipoIdentificacion + '?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-remoto") ?>';
                form.innerHTML = `
                    <?= csrf_field() ?>
                    <input type="hidden" name="habitacion_id" value="${habitacionId}">
                    <input type="hidden" name="reservacion_id" value="${reservacionId}">
                    <input type="hidden" name="tipo_entrega" value="usuario_actual">
                    <input type="hidden" name="tipo_identificacion" value="${tipoIdentificacion}">
                    <input type="hidden" name="nombre_propietario_ine" value="${nombrePropietario}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    }
}


 /**
 * NUEVA: Entregar controles remotos a múltiples habitaciones
 * Permite entregar remotos a varias habitaciones con una sola INE
 *
 * @param {number} reservacionId - ID de la reservación
 * @param {array} habitaciones - Array de objetos con {id, numero} de las habitaciones de la reservación
 */
function entregarRemotosMultiples(reservacionId, habitaciones) {
    if (typeof Swal === 'undefined') {
        window.msToast('error', null, 'Esta funcionalidad requiere SweetAlert2');
        return;
    }

    // Filtrar solo habitaciones que el hotel tiene el remoto (disponibles para entregar)
    // Esto debería venir del backend, pero lo dejamos como ejemplo
    const habitacionesDisponibles = habitaciones.filter(h => h.hotel_tiene_remoto);

    if (habitacionesDisponibles.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin remotos disponibles',
            text: 'Todos los remotos de esta reservación ya fueron entregados',
            confirmButtonColor: 'var(--brand-primary, #1B2746)'
        });
        return;
    }

    // Crear HTML con checkboxes para seleccionar habitaciones
    let habitacionesHTML = '';
    habitacionesDisponibles.forEach(hab => {
        habitacionesHTML += `
            <label class="flex items-center p-3 border rounded-lg hover:bg-purple-50 cursor-pointer mb-2">
                <input type="checkbox" name="habitaciones_sel" value="${hab.id}" class="mr-3 w-4 h-4">
                <i class="fas fa-door-open mr-2 text-purple-600"></i>
                <span class="font-semibold">Habitación ${hab.numero}</span>
            </label>
        `;
    });

    Swal.fire({
        title: 'Entregar Controles Remotos',
        html: `
            <div class="text-left space-y-4">
                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-double mr-1"></i>
                        Seleccione las habitaciones:
                    </p>
                    <div class="max-h-40 overflow-y-auto border rounded-lg p-2">
                        ${habitacionesHTML}
                    </div>
                    <button type="button" onclick="document.querySelectorAll('input[name=habitaciones_sel]').forEach(cb => cb.checked = true)"
                            class="mt-2 text-xs text-purple-600 hover:text-purple-800">
                        <i class="fas fa-check-square mr-1"></i>Seleccionar todas
                    </button>
                </div>

                <!-- Tipo de identificación -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-id-card mr-1"></i>
                        Tipo de identificación:
                    </p>
                    <div class="space-y-2">
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="ine" class="mr-2" checked>
                            <i class="fas fa-id-card mr-2 text-purple-600"></i>
                            INE
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="licencia" class="mr-2">
                            <i class="fas fa-car mr-2 text-purple-600"></i>
                            Licencia de Conducir
                        </label>
                        <label class="flex items-center p-2 border rounded hover:bg-purple-50 cursor-pointer">
                            <input type="radio" name="tipo_id_multiple" value="otro" class="mr-2">
                            <i class="fas fa-passport mr-2 text-purple-600"></i>
                            Otro
                        </label>
                    </div>
                </div>

                <!-- Nombre del propietario de la INE (NUEVO en v2.0) -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-user mr-1"></i>
                        Nombre del propietario de la identificación:
                    </p>
                    <input type="text"
                           id="nombre_propietario_ine_multiple"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           placeholder="Ej: Juan Pérez García">
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Ingrese el nombre completo tal como aparece en la identificación
                    </p>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-primary, #1B2746)',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-hand-holding mr-2"></i>Entregar Remotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Validar que se hayan seleccionado habitaciones
            const habitacionesSeleccionadas = Array.from(
                document.querySelectorAll('input[name="habitaciones_sel"]:checked')
            ).map(cb => cb.value);

            if (habitacionesSeleccionadas.length === 0) {
                Swal.showValidationMessage('Debe seleccionar al menos una habitación');
                return false;
            }

            // Validar tipo de identificación
            const tipoId = document.querySelector('input[name="tipo_id_multiple"]:checked');
            if (!tipoId) {
                Swal.showValidationMessage('Debe seleccionar el tipo de identificación');
                return false;
            }

            // Validar nombre del propietario
            const nombrePropietario = document.getElementById('nombre_propietario_ine_multiple').value.trim();
            if (!nombrePropietario) {
                Swal.showValidationMessage('Debe ingresar el nombre del propietario de la identificación');
                return false;
            }

            return {
                habitaciones: habitacionesSeleccionadas,
                tipo_identificacion: tipoId.value,
                nombre_propietario: nombrePropietario
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/entregar-remotos-multiples") ?>';

            // CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= csrf_token() ?>';
            form.appendChild(csrfInput);

            // Reservación ID
            const resInput = document.createElement('input');
            resInput.type = 'hidden';
            resInput.name = 'reservacion_id';
            resInput.value = reservacionId;
            form.appendChild(resInput);

            // Habitaciones seleccionadas (como array)
            result.value.habitaciones.forEach(habId => {
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitaciones_ids[]';
                habInput.value = habId;
                form.appendChild(habInput);
            });

            // Tipo entrega
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo_entrega';
            tipoInput.value = 'usuario_actual';
            form.appendChild(tipoInput);

            // Tipo identificación
            const tipoIdInput = document.createElement('input');
            tipoIdInput.type = 'hidden';
            tipoIdInput.name = 'tipo_identificacion';
            tipoIdInput.value = result.value.tipo_identificacion;
            form.appendChild(tipoIdInput);

            // Nombre del propietario de la INE (NUEVO campo v2.0)
            const nombreInput = document.createElement('input');
            nombreInput.type = 'hidden';
            nombreInput.name = 'nombre_propietario_ine';
            nombreInput.value = result.value.nombre_propietario;
            form.appendChild(nombreInput);

            document.body.appendChild(form);
            form.submit();
        }
    });
}

/**
 * Función para recibir control remoto de MÚLTIPLES habitaciones de una reservación
 * Esta función muestra un modal para seleccionar habitaciones
 *
 * @param {number} reservacionId - ID de la reservación
 * @param {array} habitaciones - Array de objetos con {id, numero} de las habitaciones de la reservación
 */
function recibirRemotosMultiples(reservacionId, habitaciones) {
    if (typeof Swal === 'undefined') {
        window.msToast('error', null, 'Esta funcionalidad requiere SweetAlert2');
        return;
    }

    // Filtrar solo habitaciones que tienen el remoto con el huésped (disponibles para recibir)
    // Esto debería venir del backend, pero lo dejamos como ejemplo
    const habitacionesConRemoto = habitaciones.filter(h => !h.hotel_tiene_remoto);

    if (habitacionesConRemoto.length === 0) {
        Swal.fire({
            icon: 'info',
            title: 'Sin remotos para recibir',
            text: 'Todos los remotos de esta reservación ya están en el hotel',
            confirmButtonColor: '#10B981'
        });
        return;
    }

    // Crear HTML con checkboxes para seleccionar habitaciones
    let habitacionesHTML = '';
    habitacionesConRemoto.forEach(hab => {
        habitacionesHTML += `
            <label class="flex items-center p-3 border rounded-lg hover:bg-green-50 cursor-pointer mb-2">
                <input type="checkbox" name="habitaciones_recibir" value="${hab.id}" class="mr-3 w-4 h-4">
                <i class="fas fa-door-open mr-2 text-green-600"></i>
                <span class="font-semibold">Habitación ${hab.numero}</span>
            </label>
        `;
    });

    Swal.fire({
        title: 'Recibir Controles Remotos',
        html: `
            <div class="text-left space-y-4">
                <!-- Selección de habitaciones -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-double mr-1"></i>
                        Seleccione las habitaciones:
                    </p>
                    <div class="max-h-40 overflow-y-auto border rounded-lg p-2">
                        ${habitacionesHTML}
                    </div>
                    <button type="button" onclick="document.querySelectorAll('input[name=habitaciones_recibir]').forEach(cb => cb.checked = true)"
                            class="mt-2 text-xs text-green-600 hover:text-green-800">
                        <i class="fas fa-check-square mr-1"></i>Seleccionar todas
                    </button>
                </div>

                <!-- Notas opcionales -->
                <div class="mb-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-sticky-note mr-1"></i>
                        Notas (opcional):
                    </p>
                    <textarea id="notas_recepcion_multiple"
                              class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                              rows="3"
                              placeholder="Ej: Remotos en buen estado"></textarea>
                </div>

                <div class="bg-green-50 p-3 rounded-lg">
                    <p class="text-xs text-green-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        Se devolverán los controles remotos y las identificaciones correspondientes
                    </p>
                </div>
            </div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-hand-holding mr-2"></i>Recibir Remotos',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            // Validar que se hayan seleccionado habitaciones
            const habitacionesSeleccionadas = Array.from(
                document.querySelectorAll('input[name="habitaciones_recibir"]:checked')
            ).map(cb => cb.value);

            if (habitacionesSeleccionadas.length === 0) {
                Swal.showValidationMessage('Debe seleccionar al menos una habitación');
                return false;
            }

            const notas = document.getElementById('notas_recepcion_multiple').value.trim();

            return {
                habitaciones: habitacionesSeleccionadas,
                notas: notas || 'Devolución múltiple desde índice'
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear formulario temporal
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/recibir-remotos-multiples") ?>';

            // CSRF token
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= csrf_token() ?>';
            form.appendChild(csrfInput);

            // Reservación ID
            const resInput = document.createElement('input');
            resInput.type = 'hidden';
            resInput.name = 'reservacion_id';
            resInput.value = reservacionId;
            form.appendChild(resInput);

            // Habitaciones seleccionadas (como array)
            result.value.habitaciones.forEach(habId => {
                const habInput = document.createElement('input');
                habInput.type = 'hidden';
                habInput.name = 'habitaciones_ids[]';
                habInput.value = habId;
                form.appendChild(habInput);
            });

            // Tipo recepción
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo_recepcion';
            tipoInput.value = 'usuario_actual';
            form.appendChild(tipoInput);

            // Notas
            const notasInput = document.createElement('input');
            notasInput.type = 'hidden';
            notasInput.name = 'notas';
            notasInput.value = result.value.notas;
            form.appendChild(notasInput);

            document.body.appendChild(form);
            form.submit();
        }
    });
}


function seleccionarTipoCliente(tipo, habitacionId, fechaEntrada, fechaSalida, horaActual) {
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const habitacionArg = tieneHabitacion ? parseInt(habitacionId, 10) : 'null';

    hbReserveSetDir('fwd');
    hbCerrarSwalReserva(() => {
        Swal.fire({
            ...hbReserveSwalAnim(),
            title: '',
            html: `
                <div class="hb-reserve-shell" data-tipo="${tipo}">
                    ${(window.__hbReserveDir || 'initial') === 'initial' ? hbReservaSkeleton() : ''}
                    ${hbReservaSidebar(fechaEntrada, fechaSalida, 2, habitacionId)}
                    <section class="hb-reserve-main" aria-label="Hora de llegada">
                        ${hbReservaMobileIntro(fechaEntrada, fechaSalida, 2, habitacionId)}
                        <div class="hb-reserve-mobile-dates" aria-hidden="true">
                            <span><i class="fas fa-calendar-day"></i> ${hbFormatarFechaCorta(fechaEntrada)}</span>
                            <i class="fas fa-arrow-right" style="font-size:.6rem;opacity:.4"></i>
                            <span><i class="fas fa-calendar-day"></i> ${hbFormatarFechaCorta(fechaSalida)}</span>
                        </div>
                        <span class="hb-reserve-main__eyebrow">Paso 2 de 2</span>
                        <h3>&iquest;A que hora llega?</h3>
                        <p>Elige una opcion rapida o escribe la hora. Tambien puedes capturarla despues dentro de la reservacion.</p>

                        <div class="hb-reserve-timechips" aria-label="Opciones rapidas de hora">
                            <button type="button" onclick="hbReservaSetHora('${horaActual}', this)" class="hb-reserve-timechip is-active"><i class="far fa-clock"></i> Ahora</button>
                            <button type="button" onclick="hbReservaSetHora(hbHotelCheckinHora, this)" class="hb-reserve-timechip" title="Hora de check-in configurada" aria-label="Usar hora de check-in configurada: ${hbHotelCheckinHoraLabel}">${hbHotelCheckinHoraLabel}</button>
                            <button type="button" onclick="hbReservaSetHora('20:00', this)" class="hb-reserve-timechip">08:00 p. m.</button>
                            <button type="button" onclick="continuarReservacionSinHora('${tipo}', ${habitacionArg}, '${fechaEntrada}', '${fechaSalida}'); return false;" class="hb-reserve-timechip"><i class="far fa-calendar-plus"></i> Definir despues</button>
                        </div>

                        <label class="hb-reserve-timefield" for="horaLlegadaRapida">
                            <i class="far fa-clock"></i>
                            <input type="time" id="horaLlegadaRapida" value="${horaActual}" class="hb-arrival-input brand-focus" oninput="document.getElementById('hbReserveValidation')?.classList.add('hidden')">
                        </label>
                        <p class="hb-reserve-hint"><i class="far fa-eye"></i> Esta hora ayuda a preparar la habitacion a tiempo; no es obligatoria si decides definirla despues.</p>
                        <p id="hbReserveValidation" class="hb-reserve-validation hidden">Ingresa la hora de llegada o usa Definir despues.</p>

                        <div class="hb-reserve-footer">
                            <button type="button" onclick="hbReserveSetDir('back'); hbCerrarSwalReserva(() => mostrarSelectorTipoCliente(${habitacionArg}, { fechaEntrada: '${fechaEntrada}', fechaSalida: '${fechaSalida}', horaActual: '${horaActual}' }, 'back'), 150)" class="hb-reserve-btn hb-reserve-btn--ghost">
                                <i class="fas fa-arrow-left"></i> Volver
                            </button>
                            <button type="button" onclick="hbCrearReservacionDesdeHora('${tipo}', ${habitacionArg}, '${fechaEntrada}', '${fechaSalida}')" class="hb-reserve-btn hb-reserve-btn--primary">
                                <i class="fas fa-check"></i> Crear reservacion
                            </button>
                        </div>
                    </section>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            showCloseButton: true,
            allowOutsideClick: true,
            allowEscapeKey: true,
            returnFocus: false,
            closeButtonAriaLabel: 'Cerrar',
            width: '820px',
            customClass: {
                container: 'hb-swal-sheet-container',
                popup: 'hb-swal hb-swal-arrival hb-reserve-swal',
                htmlContainer: 'hb-swal-html'
            },
            buttonsStyling: false
        });
    });
}
// Función auxiliar para formatear fecha
function hayHabitacionReservaRapida(habitacionId) {
    return habitacionId !== null
        && habitacionId !== undefined
        && habitacionId !== ''
        && habitacionId !== 'null'
        && !Number.isNaN(parseInt(habitacionId, 10));
}

function continuarReservacionSinHora(tipo, habitacionId, fechaEntrada, fechaSalida) {
    hbCerrarSwalReserva(() => {
        continuarReservacionDesdeModal(tipo, habitacionId, fechaEntrada, fechaSalida, '', true);
    }, 80);
}

function continuarReservacionDesdeModal(tipo, habitacionId, fechaEntrada, fechaSalida, horaSeleccionada, horaPendiente) {
    const tieneHabitacion = hayHabitacionReservaRapida(habitacionId);
    const habitacionValor = tieneHabitacion ? parseInt(habitacionId, 10) : null;
    const horaFinal = horaSeleccionada || '';

    const datosReservacion = {
        habitacionId: habitacionValor,
        fechaEntrada: fechaEntrada,
        fechaSalida: fechaSalida,
        horaLlegada: horaFinal || null,
        horaLlegadaPendiente: !!horaPendiente
    };

    try {
        sessionStorage.setItem('reservacionRapida', JSON.stringify(datosReservacion));
    } catch (error) {
        // Si el navegador bloquea sessionStorage, el flujo por URL sigue funcionando.
    }

    if (tipo === 'nuevo') {
        const params = new URLSearchParams({
            return_to: 'reservacion_rapida',
            fecha_entrada: fechaEntrada,
            fecha_salida: fechaSalida
        });

        if (tieneHabitacion) {
            params.set('habitacion_id', habitacionValor);
        }

        if (horaFinal) {
            params.set('hora_llegada', horaFinal);
        }

        window.location.href = '<?= url('huespedes/create') ?>?' + params.toString();
        return;
    }

    const params = new URLSearchParams({
        fecha_entrada: fechaEntrada,
        fecha_salida: fechaSalida
    });
    params.set('preseleccion', 'true');

    if (tieneHabitacion) {
        params.set('habitacion_id', habitacionValor);
    }

    if (horaFinal) {
        params.set('hora_llegada', horaFinal);
    }

    window.location.href = '<?= url('reservaciones/crear') ?>?' + params.toString();
}

function formatearFechaCorta(fecha) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const dia = fecha.getDate();
    const mes = meses[fecha.getMonth()];
    const año = fecha.getFullYear();
    return `${dia} ${mes} ${año}`;
}

function formatearFechaReservaCorta(fecha) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const dia = fecha.getDate();
    const mes = meses[fecha.getMonth()];
    const anio = String(fecha.getFullYear()).slice(-2);
    return `${dia} ${mes} ${anio}`;
}

function hacerCheckInRapido(reservacionId) {
    Swal.fire({
        title: '¿Realizar Check-in?',
        text: 'Se procederá con el check-in del huésped',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-primary, #1B2746)',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-in-alt mr-2"></i>Hacer Check-in',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true,
        customClass: {
            popup: 'hb-swal-checkin',
            confirmButton: 'hb-swal-confirm',
            cancelButton: 'hb-swal-cancel'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= url('reservaciones/ver/') ?>' + reservacionId + '?checkin_return_to=habitaciones#checkin';
        }
    });
}

// ============================================================================
// FUNCIÓN MEJORADA: Check-out con selección de habitaciones
// ============================================================================

(function mostrarResultadoCheckInHabitaciones() {
    const params = new URLSearchParams(window.location.search);
    const reservacionId = parseInt(params.get('checkin_ok') || '0', 10);
    if (!reservacionId) return;

    params.delete('checkin_ok');
    const cleanQuery = params.toString();
    const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '') + window.location.hash;
    window.history.replaceState({}, document.title, cleanUrl);

    const verUrl = '<?= url('reservaciones/ver/') ?>' + reservacionId;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Check-in registrado',
            text: 'La habitacion quedo ocupada y las alertas se actualizaron.',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-eye"></i> Ver reservacion',
            cancelButtonText: 'Quedarme aqui',
            confirmButtonColor: 'var(--brand-primary, #1B2746)',
            cancelButtonColor: '#6B7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = verUrl;
            }
        });
        return;
    }

    if (window.msToast) {
        window.msToast('success', null, 'Check-in registrado. Las alertas se actualizaron.');
    }
})();

function confirmarCheckOut(reservacionId) {
    // Mostrar loading mientras obtenemos los datos
    Swal.fire({
        title: 'Cargando información...',
        html: 'Obteniendo datos de la reservación',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // NUEVA FORMA: Obtener las habitaciones desde el servidor con sus IDs reales
    fetch(`<?= url('api/reservaciones/') ?>${reservacionId}/habitaciones`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();

        if (!data.success || !data.habitaciones || data.habitaciones.length === 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudieron obtener las habitaciones'
            });
            return;
        }

        const habitaciones = data.habitaciones;

        // Si solo hay 1 habitación, hacer check-out directo
        if (habitaciones.length === 1) {
            checkOutDirectoIndex(reservacionId, habitaciones[0]);
        } else {
            // Si hay múltiples, mostrar modal de selección
            mostrarModalCheckOutIndex(reservacionId, habitaciones);
        }
    })
    .catch(error => {
        console.error('Error al obtener habitaciones:', error);
        Swal.close();

        // Si falla el API, usar método de respaldo
        confirmarCheckOutRespaldo(reservacionId);
    });
}

// ========================================
// MÉTODO DE RESPALDO (si falla el API)
// ========================================
function confirmarCheckOutRespaldo(reservacionId) {
    Swal.fire({
        title: '¿Realizar Check-out?',
        text: 'Se registrará la salida del huésped',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Sí, hacer check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            ejecutarCheckOutIndex(reservacionId, null);
        }
    });
}

// ========================================
// CHECK-OUT DIRECTO (1 habitación)
// ========================================


// ========================================
// MODAL DE SELECCIÓN DE HABITACIONES
// ========================================
function mostrarModalCheckOutIndex(reservacionId, habitaciones) {
    // ✅ CORRECCIÓN: Manejar TODOS los posibles formatos de ID
    let habitacionesHTML = habitaciones.map(hab => {
        // Intentar obtener el ID en este orden de prioridad:
        const habId = hab.reservacion_habitacion_id || hab.habitacion_id || hab.id || hab.rel_id;

        // Si no hay ID, alertar
        if (!habId || habId === 'undefined') {
            console.error('⚠️ Habitación sin ID válido:', hab);
        }

        return `
        <label class="hb-checkout-room-option flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg cursor-pointer border-2 border-transparent hover:border-orange-400 transition-all">
            <input
                type="checkbox"
                value="${habId}"
                class="checkbox-habitacion-index mr-3 w-5 h-5 text-orange-600 rounded focus:ring-orange-500"
                checked
                data-hab-numero="${hab.numero || hab.habitacion_numero}"
            >
            <div class="flex-1">
                <span class="font-bold text-gray-800">Hab. ${hab.numero || hab.habitacion_numero || 'N/A'}</span>
                <span class="text-xs text-gray-500 ml-2">(${hab.tipo || hab.tipo_nombre || 'Standard'})</span>
            </div>
        </label>
        `;
    }).join('');

    Swal.fire({
        title: 'Seleccionar Habitaciones',
        html: `
            <div class="hb-checkout-shell">
                <p class="text-sm text-gray-600 mb-4">
                    Selecciona las habitaciones a liberar:
                </p>
                <div class="hb-checkout-room-list space-y-2" role="group" aria-label="Habitaciones para check-out">
                    ${habitacionesHTML}
                </div>
                <div class="flex gap-2 mb-4">
                    <button
                        type="button"
                        onclick="document.querySelectorAll('.checkbox-habitacion-index').forEach(cb => cb.checked = true)"
                        class="text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                        Todas
                    </button>
                    <button
                        type="button"
                        onclick="document.querySelectorAll('.checkbox-habitacion-index').forEach(cb => cb.checked = false)"
                        class="text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                        Ninguna
                    </button>
                </div>
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <i class="fas fa-info-circle mr-2"></i>
                    Si seleccionas todas, se completará el check-out. Si seleccionas algunas, la reservación permanecerá activa.
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-check mr-2"></i>Confirmar Check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        width: '500px',
        customClass: {
            popup: 'hb-swal-checkout',
            htmlContainer: 'hb-swal-checkout-html',
            actions: 'hb-swal-checkout-actions'
        },
        preConfirm: () => {
            // ✅ CORRECCIÓN: Asegurar que se convierten a números correctamente
            const checkboxes = document.querySelectorAll('.checkbox-habitacion-index:checked');
            const seleccionadas = Array.from(checkboxes).map(cb => {
                const valor = cb.value;
                const numero = parseInt(valor, 10);

                return numero;
            }).filter(id => !isNaN(id) && id > 0); // Filtrar NaN y valores inválidos

            if (seleccionadas.length === 0) {
                Swal.showValidationMessage('Debes seleccionar al menos una habitación');
                return false;
            }

            return seleccionadas;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            ejecutarCheckOutIndex(reservacionId, result.value);
        }
    });
}

// ========================================
// EJECUTAR CHECK-OUT CON HABITACIONES
// ========================================

// ========================================
// MODAL "CUENTA PENDIENTE" (reutilizable)
// Se muestra cuando el check-out se bloquea por saldo pendiente.
// ========================================
function mostrarModalSaldoPendiente(data) {
    const mensaje = data.message || 'Esta reservación tiene un saldo pendiente. Cobra el saldo antes de registrar la salida.';
    const irCobrar = data.url_reservacion
        ? `<a href="${data.url_reservacion}" class="swal2-confirm swal2-styled" style="display:inline-flex;align-items:center;gap:8px;background:#F97316;margin-top:6px;"><i class="fas fa-cash-register"></i> Ir a cobrar</a>`
        : '';
    Swal.fire({
        icon: 'warning',
        title: 'Cuenta pendiente',
        html: `
            <div class="text-center" style="max-width:420px;margin:0 auto;">
                <p style="margin-bottom:14px;color:#374151;">${mensaje}</p>
                ${irCobrar}
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: 'Cerrar',
        cancelButtonColor: '#6B7280',
    });
}

// ========================================
// CHECK-OUT RÁPIDO (todas las habitaciones)
// ========================================
async function ejecutarCheckOutRapido(reservacionId) {
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('hora_salida', new Date().toTimeString().slice(0, 8));

    // Selector opcional de responsable de limpieza (cancelable)
    if (window.CheckoutLimpieza) {
        const asignaciones = await CheckoutLimpieza.seleccionar({
            infoUrl: `<?= url('api/reservaciones') ?>/${reservacionId}/limpieza-personal`
        });
        if (asignaciones === null) return; // usuario cancelo el check-out
        CheckoutLimpieza.aplicarAFormData(formData, asignaciones);
    }

    Swal.fire({
        title: 'Procesando Check-out...',
        html: 'Registrando salida del huésped',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    const endpoint = `<?= url('reservaciones/check-out-rapido/') ?>${reservacionId}`;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData,
        redirect: 'follow'
    })
    .then(response => {
        // Siempre quedarse en la misma página (index)
        if (response.redirected || !response.ok) {
            // El servidor procesó el check-out y redirigió, recargar index
            window.location.reload();
            return null;
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }

        window.location.reload();
        return null;
    })
    .then(data => {
        if (data === null) return;

        Swal.close();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Check-out Completado!',
                html: data.message || 'La salida se ha registrado correctamente',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.reload();
            });
        } else if (data.saldo_pendiente) {
            mostrarModalSaldoPendiente(data);
        } else {
            throw new Error(data.message || 'Error al procesar check-out');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudo completar el check-out'
        });
    });
}


// Obtener información de la reservación con sus habitaciones
function obtenerInfoReservacion(reservacionId) {
    Swal.fire({
        title: 'Cargando información...',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Hacer petición para obtener las habitaciones de la reservación
    fetch(`<?= url('reservaciones/ver/') ?>${reservacionId}`)
        .then(response => response.text())
        .then(html => {
            // Parsear el HTML para extraer información de habitaciones
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // Buscar información de habitaciones en el HTML
            const habitacionesInfo = extraerHabitacionesDelHTML(doc, reservacionId);

            if (habitacionesInfo.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudieron obtener las habitaciones'
                });
                return;
            }

            // Si solo hay 1 habitación, hacer check-out directo
            if (habitacionesInfo.length === 1) {
                checkOutDirectoIndex(reservacionId, habitacionesInfo[0]);
            } else {
                // Si hay múltiples, mostrar modal de selección
                mostrarModalCheckOutIndex(reservacionId, habitacionesInfo);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Si falla, hacer check-out tradicional
            checkOutDirectoIndex(reservacionId, null);
        });
}

// Extraer información de habitaciones del HTML
function extraerHabitacionesDelHTML(doc, reservacionId) {
    const habitaciones = [];

    // Buscar en la tabla o lista de habitaciones
    const habitacionesElements = doc.querySelectorAll('[data-habitacion-id], .habitacion-item, tr[data-habitacion]');

    if (habitacionesElements.length > 0) {
        habitacionesElements.forEach((el, index) => {
            const id = el.dataset.habitacionId || el.dataset.habitacion || (index + 1);
            const numero = el.textContent.match(/\d+/)?.[0] || (index + 1);
            const tipo = el.textContent.match(/(Sencilla|Doble|Triple|Suite|Cuádruple)/i)?.[0] || 'Standard';

            habitaciones.push({
                id: parseInt(id),
                numero: numero,
                tipo: tipo
            });
        });
    }

    // Si no encontramos habitaciones en el HTML, usar datos del checkout actual
    if (habitaciones.length === 0) {
        // Buscar en los datos de checkout del día
        const checkoutElement = document.querySelector(`[onclick*="confirmarCheckOut(${reservacionId})"]`);
        if (checkoutElement) {
            const parent = checkoutElement.closest('.p-2, .flex');
            const habitacionesText = parent?.querySelector('.text-xs')?.textContent || '';
            const numeros = habitacionesText.match(/\d+/g) || [];

            numeros.forEach((num, index) => {
                habitaciones.push({
                    id: index + 1, // ID temporal
                    numero: num,
                    tipo: 'Standard'
                });
            });
        }
    }

    return habitaciones;
}

function checkOutDirectoIndex(reservacionId, habitacionInfo) {
    const mensajeHabitacion = habitacionInfo
        ? `<p class="mb-2">Habitación: <strong>${habitacionInfo.numero || habitacionInfo.habitacion_numero}</strong></p>`
        : '';

    Swal.fire({
        title: '¿Realizar Check-out?',
        html: `
            <div class="text-left">
                ${mensajeHabitacion}
                <p class="text-sm text-gray-600">Se registrará la salida y las habitaciones pasarán a limpieza</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F97316',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-sign-out-alt mr-2"></i>Sí, hacer check-out',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Obtener el ID correcto
            const habId = habitacionInfo.reservacion_habitacion_id || habitacionInfo.habitacion_id || habitacionInfo.id;
            ejecutarCheckOutIndex(reservacionId, habId ? [habId] : null);
        }
    });
}

// Modal de selección de habitaciones


// Ejecutar el check-out con las habitaciones seleccionadas
async function ejecutarCheckOutIndex(reservacionId, habitacionesIds) {
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    formData.append('hora_salida', new Date().toTimeString().slice(0, 8));

    // ✅ CORRECCIÓN: Agregar habitaciones de forma más robusta
    if (habitacionesIds && Array.isArray(habitacionesIds) && habitacionesIds.length > 0) {
        habitacionesIds.forEach((id, index) => {
            formData.append('habitaciones[]', id);
        });
    }

    // Selector opcional de responsable de limpieza (cancelable)
    if (window.CheckoutLimpieza) {
        const asignaciones = await CheckoutLimpieza.seleccionar({
            infoUrl: `<?= url('api/reservaciones') ?>/${reservacionId}/limpieza-personal`,
            habitacionesIds: habitacionesIds
        });
        if (asignaciones === null) return; // usuario cancelo el check-out
        CheckoutLimpieza.aplicarAFormData(formData, asignaciones);
    }

    Swal.fire({
        title: 'Procesando Check-out...',
        html: 'Registrando salida del huésped',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Usar el endpoint de check-out parcial
    const endpoint = `<?= url('reservaciones/check-out-parcial/') ?>${reservacionId}`;

    fetch(endpoint, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData,
        redirect: 'follow'
    })
    .then(response => {
        // Siempre quedarse en la misma página (index)
        if (response.redirected) {
            window.location.reload();
            return null;
        }

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }

        // Intentar parsear como JSON
        const contentType = response.headers.get('content-type');

        if (contentType && contentType.includes('application/json')) {
            return response.json();
        }

        // Si no es JSON, es un redirect exitoso o HTML
        window.location.reload();
        return null;
    })
    .then(data => {
        if (!data) return; // Ya manejado (redirect)

        if (data.saldo_pendiente) {
            mostrarModalSaldoPendiente(data);
            return;
        }

        if (data.success) {
            let htmlContent = `
                <div class="text-left mx-auto" style="max-width: 500px;">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <h4 class="font-semibold text-lg mb-2 flex items-center">
                            <i class="fas fa-check-circle mr-2 text-green-600"></i>
                            Check-out realizado
                        </h4>
                        <div class="space-y-2 text-sm">
                            ${data.huesped ? `<p><strong>Huésped:</strong> ${data.huesped}</p>` : ''}
                            ${data.habitaciones ? `<p><strong>Habitaciones liberadas:</strong> ${data.habitaciones}</p>` : ''}
                            <p><strong>Hora salida:</strong> ${data.hora_salida || new Date().toLocaleTimeString()}</p>
                            ${data.total ? `<p><strong>Total estancia:</strong> ${data.total}</p>` : ''}
                        </div>
                    </div>
                </div>
            `;

            Swal.fire({
                icon: 'success',
                title: 'Check-out Exitoso',
                html: htmlContent,
                confirmButtonColor: '#F97316',
                confirmButtonText: '<i class="fas fa-check mr-2"></i>Aceptar'
            }).then(() => {
                window.location.reload();
            });
        } else {
            throw new Error(data.message || 'Error desconocido en el check-out');
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Ocurrió un error al procesar el check-out',
            confirmButtonColor: '#F97316'
        });
    });
}

<?php
// Personal de limpieza para el selector obligatorio de "quién limpió".
$hbPersonalLimpieza = $personal_limpieza ?? ['disponible' => false, 'personal' => [], 'asignadas' => []];
?>
// Personal activo del hotel + asignados a la limpieza activa de cada cuarto (preselección).
const HB_LIMPIEZA_PERSONAL = <?= json_encode($hbPersonalLimpieza['personal'] ?? []) ?>;
const HB_LIMPIEZA_ASIGNADAS = <?= json_encode(!empty($hbPersonalLimpieza['asignadas']) ? $hbPersonalLimpieza['asignadas'] : new stdClass()) ?>;

async function liberarHabitacion(id) {
    // Selector obligatorio: quién hizo la limpieza (una o más personas) o
    // la elección explícita "Sin registrar personal". No se puede omitir.
    let seleccion = { trabajadorIds: [], sinPersonal: false, omitido: true };
    const preseleccion = (HB_LIMPIEZA_ASIGNADAS[id] || HB_LIMPIEZA_ASIGNADAS[String(id)] || []).map(Number);

    if (window.LimpiezaPersonal && preseleccion.length > 0) {
        // Ya se asignó responsable al hacer check-out: confirmar de un clic en
        // vez de pedir la selección otra vez (evita el doble trabajo).
        const nombres = preseleccion
            .map(pid => (HB_LIMPIEZA_PERSONAL.find(p => p.id === pid) || {}).nombre)
            .filter(Boolean);

        const confirmacion = await Swal.fire({
            title: '¿Confirmar limpieza?',
            html: nombres.length
                ? `<p style="margin-bottom:8px;">La habitación quedará disponible.</p><p><strong>${nombres.join(', ')}</strong> quedó asignad${nombres.length > 1 ? 'os' : 'o'} a esta limpieza desde el check-out.</p>`
                : '<p>La habitación quedará disponible.</p>',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonColor: '#059669',
            denyButtonColor: '#2F77E0',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, confirmar',
            denyButtonText: 'Cambiar personal',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        });

        if (confirmacion.isDismissed) return; // canceló

        if (confirmacion.isConfirmed) {
            seleccion = { trabajadorIds: preseleccion, sinPersonal: false };
        } else if (confirmacion.isDenied) {
            seleccion = await LimpiezaPersonal.elegir({
                personal: HB_LIMPIEZA_PERSONAL,
                preseleccion: preseleccion,
                textoIntro: 'La habitación quedará disponible. Indica quién hizo la limpieza (puedes elegir a más de una persona).'
            });
            if (seleccion === null) return; // canceló
        }
    } else if (window.LimpiezaPersonal) {
        seleccion = await LimpiezaPersonal.elegir({
            personal: HB_LIMPIEZA_PERSONAL,
            preseleccion: null,
            textoIntro: 'La habitación quedará disponible. Indica quién hizo la limpieza (puedes elegir a más de una persona).'
        });
        if (seleccion === null) return; // canceló
    }

    if (seleccion.omitido) {
        // Sin personal registrado o módulo de tareas apagado: confirmación simple.
        const result = await Swal.fire({
            title: '¿Marcar como disponible?',
            text: 'La habitación quedará lista para nuevas reservaciones',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-check mr-2"></i>Sí, está limpia',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
            reverseButtons: true
        });
        if (!result.isConfirmed) return;
    }

    // Mostrar loader
    Swal.fire({
        title: 'Procesando...',
        html: 'Actualizando estado de la habitación',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    const cuerpoLiberar = new URLSearchParams();
    cuerpoLiberar.append('csrf_token', '<?= csrf_token() ?>');
    if (window.LimpiezaPersonal) {
        LimpiezaPersonal.aplicarAFormData(cuerpoLiberar, seleccion);
    }

    // Hacer la petición AJAX
    fetch('<?= url('habitaciones/') ?>' + id + '/liberar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: cuerpoLiberar.toString()
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Habitación disponible!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-6xl text-green-500 mb-4"></i>
                        <p class="text-lg mb-2">Habitación <strong>${data.numero || id}</strong></p>
                        <p class="text-gray-600">Ha sido marcada como disponible</p>
                    </div>
                `,
                confirmButtonColor: '#059669',
                confirmButtonText: 'Entendido'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'No se pudo actualizar el estado de la habitación',
                confirmButtonColor: '#dc2626'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al procesar la solicitud',
            confirmButtonColor: '#dc2626'
        });
    });
}

function finalizarMantenimiento(id) {
    Swal.fire({
        title: '¿Finalizar mantenimiento?',
        text: 'La habitación quedará disponible para reservaciones',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#F59E0B',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-tools mr-2"></i>Sí, finalizar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('habitaciones/') ?>' + id + '/mantenimiento';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?= csrf_token() ?>';
            form.appendChild(csrfToken);

            const accion = document.createElement('input');
            accion.type = 'hidden';
            accion.name = 'accion';
            accion.value = 'finalizar';
            form.appendChild(accion);

            document.body.appendChild(form);
            form.submit();
        }
    });
}

function mostrarVistaRapida() {
    const sidebar = document.getElementById('sidebar');
    const mainHeader = document.getElementById('mainHeader');
    const modal = document.getElementById('vistaRapidaModal');
    if (sidebar) sidebar.style.display = 'none';
    if (mainHeader) mainHeader.style.display = 'none';
    if (modal) modal.classList.remove('hidden');
    document.body.classList.add('hb-modal-open');
    document.body.style.overflow = 'hidden';
}

function hbHayModalHabitacionesAbierto() {
    const vistaRapida = document.getElementById('vistaRapidaModal');
    const limpieza = document.getElementById('modalLimpieza');

    return !!(
        (vistaRapida && !vistaRapida.classList.contains('hidden')) ||
        (limpieza && !limpieza.classList.contains('hidden'))
    );
}

function hbSincronizarBloqueoModales() {
    const hayModal = hbHayModalHabitacionesAbierto();
    document.body.classList.toggle('hb-modal-open', hayModal);
    if (!hayModal && !document.body.classList.contains('hb-mobile-sheet-open')) {
        document.body.style.overflow = '';
    }
}

function cerrarVistaRapida() {
    const sidebar = document.getElementById('sidebar');
    const mainHeader = document.getElementById('mainHeader');
    const modal = document.getElementById('vistaRapidaModal');
    if (sidebar) sidebar.style.display = '';
    if (mainHeader) mainHeader.style.display = '';
    if (modal) modal.classList.add('hidden');
    hbSincronizarBloqueoModales();
}
// Función para recepción rápida de control remoto desde el índice de habitaciones
function recibirRemotoRapido(habitacionId, reservacionId) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Recibir control remoto?',
            text: 'Se registrará la devolución del control remoto y la identificación',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Sí, recibir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Crear formulario temporal
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/recibir-remoto") ?>';

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

                // Tipo recepción
                const tipoInput = document.createElement('input');
                tipoInput.type = 'hidden';
                tipoInput.name = 'tipo_recepcion';
                tipoInput.value = 'usuario_actual';
                form.appendChild(tipoInput);

                // Notas
                const notasInput = document.createElement('input');
                notasInput.type = 'hidden';
                notasInput.name = 'notas';
                notasInput.value = 'Devolución rápida desde índice';
                form.appendChild(notasInput);

                document.body.appendChild(form);
                form.submit();
            }
        });
    } else {
        if (confirm('¿Recibir control remoto del huésped?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url("reservaciones/recibir-remoto") ?>';
            form.innerHTML = `
                <?= csrf_field() ?>
                <input type="hidden" name="habitacion_id" value="${habitacionId}">
                <input type="hidden" name="reservacion_id" value="${reservacionId}">
                <input type="hidden" name="tipo_recepcion" value="usuario_actual">
                <input type="hidden" name="notas" value="Devolución rápida desde índice">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

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
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '<?= url("reservaciones/entregar-llave") ?>';

                form.innerHTML = `
                    <?= csrf_field() ?>
                    <input type="hidden" name="habitacion_id" value="${habitacionId}">
                    <input type="hidden" name="reservacion_id" value="${reservacionId}">
                    <input type="hidden" name="tipo_entrega" value="usuario_actual">
                `;

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarVistaRapida();
    }
});

/**
 * ========================================
 * FUNCIONES PARA LIMPIEZA MÚLTIPLE
 * ========================================
 */

// ── Deleite Sereno / estilo Caja: entrada coreografiada + skeleton de carga ──
const LM_SKELETON_MS = 520;

function lmPrefersReduced() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
}

function lmEnsureSkeleton(dialog) {
    if (!dialog || dialog.querySelector('.lm-skeleton')) {
        return;
    }
    let rooms = '';
    for (let i = 0; i < 3; i++) {
        rooms +=
            '<div class="lm-sk-room">' +
                '<div class="lm-sk-bar sk-em"></div>' +
                '<div class="lm-sk-lines"><div class="lm-sk-bar sk-name"></div><div class="lm-sk-bar sk-meta"></div></div>' +
            '</div>';
    }
    const sk = document.createElement('div');
    sk.className = 'lm-skeleton';
    sk.setAttribute('aria-hidden', 'true');
    sk.innerHTML =
        '<div class="lm-sk-head">' +
            '<div class="lm-sk-bar sk-emblem"></div>' +
            '<div class="lm-sk-htext"><div class="lm-sk-bar sk-title"></div><div class="lm-sk-bar sk-sub"></div></div>' +
        '</div>' +
        '<div class="lm-sk-toolbar">' +
            '<div class="lm-sk-bar sk-count"></div>' +
            '<div class="lm-sk-pills"><div class="lm-sk-bar sk-pill"></div><div class="lm-sk-bar sk-pill"></div></div>' +
        '</div>' +
        '<div class="lm-sk-body">' + rooms + '</div>' +
        '<div class="lm-sk-footer"><div class="lm-sk-bar sk-btn sk-btn--sm"></div><div class="lm-sk-bar sk-btn"></div></div>';
    dialog.appendChild(sk);
}

/**
 * Mostrar modal de limpieza (entrada coreografiada + skeleton, sidebar/cabecera ocultas)
 */
function mostrarModalLimpieza() {
    const modal = document.getElementById('modalLimpieza');
    if (!modal) {
        return;
    }

    // Baja la sidebar bajo el modal en el MISMO frame que se abre (sin lag del
    // observador global). El backdrop full-bleed la cubre y la difumina en sincronía.
    document.body.classList.add('hb-lm-sidebar-under');

    const dialog = modal.querySelector('.lm-dialog');
    if (dialog) {
        if (!lmPrefersReduced()) {
            lmEnsureSkeleton(dialog);
            dialog.classList.add('is-loading');
            if (dialog._lmSkTimer) {
                clearTimeout(dialog._lmSkTimer);
            }
            dialog._lmSkTimer = setTimeout(function () {
                dialog.classList.remove('is-loading');
            }, LM_SKELETON_MS);
        } else {
            dialog.classList.remove('is-loading');
        }
    }

    modal.classList.remove('hidden');
    document.body.classList.add('hb-modal-open');
    document.body.classList.add('overflow-hidden');

    // Fuerza el estado inicial (oculto) y dispara la transición de entrada — reflow, no rAF.
    void modal.offsetWidth;
    modal.classList.add('is-open');

    actualizarContadorLimpieza();
}

/**
 * Cerrar modal de limpieza (espera la transición de salida, restaura sidebar/cabecera)
 */
function cerrarModalLimpieza() {
    const modal = document.getElementById('modalLimpieza');
    if (!modal) {
        return;
    }
    const dialog = modal.querySelector('.lm-dialog');

    const finalizar = function () {
        modal.classList.add('hidden');

        document.body.classList.remove('overflow-hidden');
        document.body.classList.remove('hb-lm-sidebar-under');

        if (dialog) {
            if (dialog._lmSkTimer) {
                clearTimeout(dialog._lmSkTimer);
            }
            dialog.classList.remove('is-loading');
        }

        hbSincronizarBloqueoModales();
    };

    modal.classList.remove('is-open');

    if (!dialog || lmPrefersReduced()) {
        finalizar();
        return;
    }

    // Espera a que termine la transición del diálogo (con respaldo por tiempo).
    let cerrado = false;
    const alTerminar = function (e) {
        if (e && (e.target !== dialog || (e.propertyName && e.propertyName !== 'transform'))) {
            return;
        }
        if (cerrado) {
            return;
        }
        cerrado = true;
        dialog.removeEventListener('transitionend', alTerminar);
        finalizar();
    };
    dialog.addEventListener('transitionend', alTerminar);
    setTimeout(alTerminar, 500);
}

// Exclusión mutua: "Sin registrar personal" limpia a las personas y viceversa.
(function() {
    const sinPersonal = document.getElementById('lmSinPersonal');
    if (!sinPersonal) return;
    const staffChecks = document.querySelectorAll('#modalLimpieza .lm-staff-check');
    sinPersonal.addEventListener('change', function() {
        if (sinPersonal.checked) staffChecks.forEach(c => { c.checked = false; });
    });
    staffChecks.forEach(c => c.addEventListener('change', function() {
        if (c.checked) sinPersonal.checked = false;
    }));
})();

/**
 * Seleccionar/deseleccionar todas las habitaciones
 */
function seleccionarTodasLimpieza(seleccionar) {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza');
    checkboxes.forEach(checkbox => {
        checkbox.checked = seleccionar;
    });
    actualizarContadorLimpieza();
}

/**
 * Actualizar contador de habitaciones seleccionadas
 */
/**
 * Actualizar contador de habitaciones seleccionadas
 */
function actualizarContadorLimpieza() {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza:checked');
    const contador = checkboxes.length;

    // ✅ VALIDAR que el elemento existe antes de modificarlo
    const contadorElement = document.getElementById('contadorSeleccionadas');
    if (contadorElement) {
        contadorElement.textContent = contador;
    }

    // Habilitar/deshabilitar botón de confirmar
    const btnMarcar = document.getElementById('btnMarcarLimpias');
    if (btnMarcar) {
        if (contador > 0) {
            btnMarcar.disabled = false;
            btnMarcar.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btnMarcar.disabled = true;
            btnMarcar.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // Rediseño "limpieza": feedback visual adicional (aditivo y defensivo, no cambia el contrato)
    const totalLimpieza = document.querySelectorAll('.checkbox-limpieza').length;
    const barraProgreso = document.getElementById('lmProgress');
    if (barraProgreso) {
        barraProgreso.style.width = (totalLimpieza ? Math.round((contador / totalLimpieza) * 100) : 0) + '%';
    }
    const chipConteo = document.getElementById('lmBtnCount');
    if (chipConteo) {
        chipConteo.textContent = contador;
        chipConteo.classList.toggle('is-visible', contador > 0);
    }
    const progressWrap = document.querySelector('#modalLimpieza .lm-progress');
    if (progressWrap) {
        progressWrap.setAttribute('aria-valuenow', contador);
    }
    const btnTodas = document.getElementById('lmSelAll');
    if (btnTodas) {
        btnTodas.classList.toggle('is-active', totalLimpieza > 0 && contador === totalLimpieza);
    }
}

/**
 * Marcar habitaciones seleccionadas como limpias
 */
/**
 * Marcar habitaciones seleccionadas como limpias
 */
function marcarHabitacionesLimpias() {
    const checkboxes = document.querySelectorAll('.checkbox-limpieza:checked');
    const habitacionesIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    if (habitacionesIds.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Selecciona habitaciones',
            text: 'Debes seleccionar al menos una habitación',
            confirmButtonColor: '#3B82F6'
        });
        return;
    }

    // Personal que hizo la limpieza: obligatorio cuando el selector está presente
    // (una o más personas, o la elección explícita "Sin registrar personal").
    const staffSection = document.querySelector('#modalLimpieza .lm-staff');
    const staffChecks = document.querySelectorAll('#modalLimpieza .lm-staff-check:checked');
    const sinPersonalEl = document.getElementById('lmSinPersonal');
    const sinPersonal = !!(sinPersonalEl && sinPersonalEl.checked);

    if (staffSection && !sinPersonal && staffChecks.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: '¿Quién hizo la limpieza?',
            text: 'Selecciona al menos una persona o marca "Sin registrar personal".',
            confirmButtonColor: '#3B82F6'
        });
        return;
    }

    // Mostrar loading
    Swal.fire({
        title: 'Procesando...',
        html: `Marcando ${habitacionesIds.length} habitación${habitacionesIds.length > 1 ? 'es' : ''} como limpia${habitacionesIds.length > 1 ? 's' : ''}`,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Enviar petición al servidor
    const formData = new FormData();
    formData.append('csrf_token', '<?= csrf_token() ?>');
    habitacionesIds.forEach(id => {
        formData.append('habitaciones_ids[]', id);
    });
    if (staffSection) {
        formData.append('personal_confirmado', '1');
        if (sinPersonal) {
            formData.append('sin_personal', '1');
        } else {
            staffChecks.forEach(chk => formData.append('trabajador_ids[]', chk.value));
        }
    }

    const url = '<?= url('habitaciones/liberar-multiples') ?>';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Obtener el texto completo de la respuesta
        return response.text().then(text => {
            // Intentar parsear como JSON
            try {
                const data = JSON.parse(text);
                return { ok: response.ok, status: response.status, data: data };
            } catch (e) {
                console.error('ERROR: No se pudo parsear como JSON:', e);
                console.error('Text that failed to parse:', text);
                throw new Error('La respuesta del servidor no es JSON válido. Ver consola para detalles.');
            }
        });
    })
    .then(result => {
        if (result.data.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Habitaciones Listas!',
                html: `
                    <div class="text-center">
                        <div class="bg-green-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-check-circle text-4xl text-green-600"></i>
                        </div>
                        <p class="text-lg mb-2">
                            <strong>${result.data.actualizadas}</strong> habitación${result.data.actualizadas > 1 ? 'es' : ''}
                            marcada${result.data.actualizadas > 1 ? 's' : ''} como disponible${result.data.actualizadas > 1 ? 's' : ''}
                        </p>
                        ${result.data.habitaciones ? `
                            <p class="text-sm text-gray-600 mt-2">
                                Habitaciones: ${result.data.habitaciones}
                            </p>
                        ` : ''}
                    </div>
                `,
                confirmButtonColor: '#10B981',
                confirmButtonText: 'Entendido',
                timer: 3000
            }).then(() => {
                location.reload();
            });

            cerrarModalLimpieza();
        } else {
            throw new Error(result.data.message || 'Error desconocido en el servidor');
        }
    })
    .catch(error => {
        console.error('ERROR CAPTURADO:', error);
        console.error('Error stack:', error.stack);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'No se pudieron actualizar las habitaciones',
            confirmButtonColor: '#EF4444'
        });
    });
}
<?php
$hayLimpieza = false;
foreach ($habitaciones as $h) {
    if ($h['estado'] == 'limpieza') {
        $hayLimpieza = true;
        break;
    }
}
?>
<?php if ($hayLimpieza): ?>
const updateIndicator = document.createElement('div');

document.body.appendChild(updateIndicator);

let seconds = 60;
const countdown = setInterval(() => {
    seconds--;
    document.getElementById('countdown').textContent = seconds;
    if (seconds <= 0) {
        clearInterval(countdown);
        location.reload();
    }
}, 1000);
<?php endif; ?>

// Sistema de tooltips mejorado para vista rápida
document.addEventListener('DOMContentLoaded', function() {
    let tooltipTimeout;
    const tooltip = document.getElementById('roomTooltip');

    // Solo inicializar si existe el tooltip
    if (!tooltip) return;

    // Añadir eventos a todas las habitaciones de vista rápida
    document.querySelectorAll('.room-quick-view').forEach(room => {
        room.addEventListener('mouseenter', function(e) {
            clearTimeout(tooltipTimeout);

            try {
                const data = JSON.parse(this.getAttribute('data-tooltip'));

                // Construir contenido del tooltip con más información
                let tooltipContent = `
                    <div class="tooltip-header">
                        <i class="fas fa-door-open mr-1"></i>
                        Habitación ${data.numero}
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-layer-group"></i>
                        <span>${data.piso}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-bed"></i>
                        <span>${data.tipo}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-users"></i>
                        <span>${data.capacidad || 'Personas N/D'}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-bed"></i>
                        <span>${data.camas || 'Camas N/D'}${data.camas_detalle && data.camas_detalle !== 'No definido' ? ' (' + data.camas_detalle + ')' : ''}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-circle text-xs"></i>
                        <span class="font-semibold">${data.estado}</span>
                    </div>
                    <div class="tooltip-info">
                        <i class="fas fa-tag"></i>
                        <span class="text-green-600 font-bold">${data.precio}</span>
                    </div>
                `;

                // Información adicional según el estado
                if (data.huesped) {
                    tooltipContent += `
                        <div class="tooltip-guest">
                            <div class="flex items-center gap-1 mb-1">
                                <i class="fas fa-user text-xs"></i>
                                <div class="font-semibold">${data.huesped}</div>
                            </div>
                    `;

                    if (data.telefono) {
                        tooltipContent += `
                            <div class="text-xs flex items-center gap-1 mt-1">
                                <i class="fas fa-phone text-xs"></i>
                                <span>${data.telefono}</span>
                            </div>
                        `;
                    }

                    if (data.fechas) {
                        tooltipContent += `
                            <div class="text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-calendar-alt text-xs"></i>
                                <span>${data.fechas}</span>
                            </div>
                        `;
                    }

                    if (data.hora_llegada) {
                        tooltipContent += `
                            <div class="text-xs mt-1 flex items-center gap-1">
                                <i class="fas fa-clock text-xs"></i>
                                <span>Hora estimada: ${data.hora_llegada.substring(0, 5)}</span>
                            </div>
                        `;
                    }

                    if (data.noches) {
                        tooltipContent += `
                            <div class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-moon mr-1"></i>${data.noches} noche${data.noches > 1 ? 's' : ''}
                            </div>
                        `;
                    }

                    tooltipContent += `</div>`;
                }

                // Indicadores especiales
                if (data.estado === 'Limpieza') {
                    tooltipContent += `
                        <div class="text-xs text-blue-600 mt-2 font-medium">
                            <i class="fas fa-broom mr-1"></i>Tiempo estimado: 30 min
                        </div>
                    `;
                }

                if (data.estado === 'Mantenimiento') {
                    tooltipContent += `
                        <div class="text-xs text-amber-600 mt-2 font-medium">
                            <i class="fas fa-tools mr-1"></i>En proceso
                        </div>
                    `;
                }

                tooltip.innerHTML = tooltipContent;

                // Posicionar tooltip
                const rect = this.getBoundingClientRect();

                // Calcular posición inicial
                let top = rect.top - 10;
                let left = rect.left + (rect.width / 2);

                // Mostrar temporalmente para obtener dimensiones
                tooltip.style.opacity = '0';
                tooltip.style.display = 'block';

                const tooltipRect = tooltip.getBoundingClientRect();

                // Ajustar posición final
                top = rect.top - tooltipRect.height - 10;
                left = left - (tooltipRect.width / 2);

                // Verificar límites de pantalla
                if (top < 10) {
                    top = rect.bottom + 10;
                }

                if (left < 10) {
                    left = 10;
                } else if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }

                tooltip.style.top = top + 'px';
                tooltip.style.left = left + 'px';
                tooltip.style.display = '';
                tooltip.style.opacity = '';

                // Mostrar tooltip con animación
                tooltipTimeout = setTimeout(() => {
                    tooltip.classList.add('show');
                }, 50);

            } catch (error) {
                console.error('Error al procesar tooltip:', error);
            }
        });

        room.addEventListener('mouseleave', function() {
            clearTimeout(tooltipTimeout);
            if (tooltip) {
                tooltip.classList.remove('show');
            }
        });
    });
});
</script>



<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>

<!-- =====================================================================
     Header vibrante — banda de marca luminosa y glossy, al estilo del modal
     de limpieza (marca -> marca mas clara + brillo blanco + destello dorado).
     Solo desktop (>=768px); en movil manda .hb-page-header (esta oculto).
     Todo el cromado se deriva de --brand-* (white-label).
     IMPORTANTE: el boton de Limpieza (.hb-cleaning-btn) se deja INTACTO a
     peticion del usuario -> excluido de los estilos de boton de esta banda.
     Va al final del archivo para ganar por orden de cascada.
     ===================================================================== -->
<style id="hb-header-hero">
@media (min-width:768px){
  /* Tarjeta visualmente flotante, pero fija en el flujo de la pagina: esquinas redondeadas
     + margenes + sombra/glow de marca, misma forma que el modal. Gradiente de
     marca luminoso (marca -> marca aclarada) + brillo blanco = energia "wow". */
  .habitaciones-view .modern-header{
    position:relative; top:auto; overflow:hidden;
    margin:16px clamp(14px,3vw,40px) 8px!important;
    border-radius:22px!important;
    --hb-hero-lift: color-mix(in srgb, var(--hb-primary) 66%, #ffffff);
    background:linear-gradient(116deg,
        var(--hb-primary) 0%,
        var(--hb-primary) 30%,
        var(--hb-hero-lift) 72%,
        color-mix(in srgb, var(--hb-primary) 82%, #ffffff) 100%)!important;
    border:1px solid color-mix(in srgb, var(--hb-primary) 20%, transparent)!important;
    box-shadow:
      0 34px 64px -34px color-mix(in srgb, var(--hb-primary) 82%, #000),
      0 14px 34px -24px color-mix(in srgb, var(--hb-primary) 58%, #000),
      inset 0 1px 0 rgba(255,255,255,.24)!important;
    padding:1.05rem 0!important;
  }
  /* Capa glossy blanca (specular + segundo brillo + sheen superior) */
  .habitaciones-view .modern-header::before{
    content:''; position:absolute; inset:0; pointer-events:none; z-index:0;
    background:
      radial-gradient(100% 90% at 8% -42%, rgba(255,255,255,.40), transparent 56%),
      radial-gradient(55% 120% at 76% -24%, rgba(255,255,255,.20), transparent 55%),
      linear-gradient(180deg, rgba(255,255,255,.16), transparent 44%);
  }
  .habitaciones-view .modern-header .container{ position:relative; z-index:1; }

  /* Titulo y subtitulo sobre la banda */
  .habitaciones-view .modern-header h1{
    color:#fff!important; text-shadow:0 1px 2px rgba(0,0,0,.18);
  }
  .habitaciones-view .modern-header .hb-title-prefix{
    color:rgba(255,255,255,.62)!important; font-weight:500!important;
  }
  .habitaciones-view .modern-header p{
    color:rgba(255,255,255,.82)!important;
    display:flex!important; align-items:center; gap:8px; font-weight:500!important;
    text-shadow:0 1px 2px rgba(0,0,0,.16);
  }
  /* Punto "en vivo" que late -> refuerza "Control en tiempo real" */
  .habitaciones-view .modern-header p::before{
    content:''; width:7px; height:7px; border-radius:50%; flex:0 0 auto;
    background:#42D392; box-shadow:0 0 0 0 rgba(66,211,146,.55);
    animation:hbLivePulse 2.2s ease-out infinite;
  }
  @keyframes hbLivePulse{ 0%{box-shadow:0 0 0 0 rgba(66,211,146,.5)} 70%{box-shadow:0 0 0 7px rgba(66,211,146,0)} 100%{box-shadow:0 0 0 0 rgba(66,211,146,0)} }

  /* Emblema de vidrio con destello dorado (misma primitiva que el modal) */
  .habitaciones-view .modern-header .p-2.rounded-lg{
    position:relative; width:48px!important; height:48px!important;
    display:grid!important; place-items:center;
    background:rgba(255,255,255,.16)!important;
    border:1px solid rgba(255,255,255,.38)!important;
    border-radius:14px!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.4), 0 12px 24px -14px rgba(0,0,0,.5)!important;
    -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px);
  }
  .habitaciones-view .modern-header .p-2.rounded-lg i{ font-size:1.18rem!important; color:#fff!important; }
  .habitaciones-view .modern-header .p-2.rounded-lg::after{
    content:''; position:absolute; top:-5px; right:-5px; width:15px; height:15px;
    background:linear-gradient(135deg,#fff,var(--hb-accent));
    clip-path:polygon(50% 0,60% 40%,100% 50%,60% 60%,50% 100%,40% 60%,0 50%,40% 40%);
    filter:drop-shadow(0 0 5px rgba(255,255,255,.85));
    animation:hbEmblemTwinkle 2.6s ease-in-out infinite;
  }
  @keyframes hbEmblemTwinkle{ 0%,100%{transform:scale(.7) rotate(0);opacity:.6} 50%{transform:scale(1) rotate(90deg);opacity:1} }

  /* Botones secundarios (Vista Rapida, Nueva Habitacion) -> vidrio.
     Se EXCLUYE .hb-cleaning-btn (Limpieza intacto) y .btn-brand (CTA aparte). */
  .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand){
    background:rgba(255,255,255,.14)!important;
    border:1px solid rgba(255,255,255,.30)!important;
    color:#fff!important;
    box-shadow:inset 0 1px 0 rgba(255,255,255,.18)!important;
    -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);
  }
  .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand) i{ color:#fff!important; }
  .habitaciones-view .modern-header .btn-modern:not(.hb-cleaning-btn):not(.btn-brand):hover{
    background:rgba(255,255,255,.24)!important;
    border-color:rgba(255,255,255,.5)!important;
    transform:translateY(-2px)!important;
  }
  /* CTA primario -> chip claro que resalta + brillo que barre al hover */
  .habitaciones-view .modern-header .btn-modern.btn-brand{
    position:relative; overflow:hidden;
    background:linear-gradient(135deg,#FFFFFF,color-mix(in srgb,var(--hb-accent) 16%,#FFFFFF))!important;
    color:var(--hb-primary)!important;
    border:1px solid color-mix(in srgb,var(--hb-accent) 36%,#fff)!important;
    box-shadow:0 14px 28px -14px rgba(0,0,0,.5), inset 0 1px 0 #fff!important;
    font-weight:700!important;
  }
  .habitaciones-view .modern-header .btn-modern.btn-brand i{ color:var(--hb-primary)!important; }
  .habitaciones-view .modern-header .btn-modern.btn-brand:hover{
    background:#fff!important; transform:translateY(-2px)!important;
    box-shadow:0 18px 32px -14px rgba(0,0,0,.55)!important;
  }
  .habitaciones-view .modern-header .btn-modern.btn-brand::after{
    content:''; position:absolute; top:0; bottom:0; left:0; width:42%;
    background:linear-gradient(100deg,transparent,rgba(255,255,255,.7),transparent);
    transform:translateX(-170%) skewX(-18deg); pointer-events:none;
  }
  .habitaciones-view .modern-header .btn-modern.btn-brand:hover::after{
    transition:transform .7s ease; transform:translateX(320%) skewX(-18deg);
  }
}
/* Respeto por reduced-motion: se apagan destello, latido y brillo */
@media (min-width:768px) and (prefers-reduced-motion: reduce){
  .habitaciones-view .modern-header .p-2.rounded-lg::after,
  .habitaciones-view .modern-header p::before{ animation:none!important; }
  .habitaciones-view .modern-header .btn-modern.btn-brand::after{ display:none!important; }
}

/* Con un modal abierto (body.ms-modal-abierto, publicado por modal-sidebar-fix.js)
   el header baja de z-index para que el backdrop del modal lo cubra igual que al
   resto del contenido (mismo patrón que la sidebar). Necesario porque los modales
   de esta vista viven DENTRO del .container (position:relative; z-index:1): su
   z-index alto queda atrapado en ese contexto de apilamiento y cualquier elemento
   externo con z>1 — este header (z-index:40) — les pintaba encima. */
body.ms-modal-abierto .habitaciones-view .modern-header{
  z-index:0!important;
  pointer-events:none!important;
}

/* ── Barra de contexto temporal ────────────────────────────────────────────
   Aparece SOLO al consultar una fecha distinta de hoy. Va sticky (no fixed):
   se queda a la vista al hacer scroll sin sumar otro flotante a una pantalla
   que ya tiene topbar pegada, nav inferior movil y el FAB del copiloto.
   Se ancla en top:0 del scroller real, que es main.main-content (NO el window:
   medido, la topbar .ms-vtb no llega a fijarse ahi y se va con el scroll, asi
   que descontar su alto solo dejaba un hueco por el que pasaba el contenido).
   z-index 31 > 30 de .ms-vtb: si en algun tema esa barra si llegara a fijarse,
   gana la de fecha — saber que NO estas viendo hoy pesa mas que la flechita,
   que sigue a un scroll de distancia.
   COLOR: grafito neutro a proposito. En esta vista TODOS los matices utiles ya
   son un estado de habitacion (verde=libre, terracota=ocupada, violeta=por
   llegar, azul=limpieza, ambar=mantenimiento); pintarla de color la haria leer
   como un estado mas. El grafito lee como "modo del sistema". */
.habitaciones-view .hb-datebar{
  position:sticky;
  top:0;
  z-index:31;
  display:flex;
  align-items:center;
  gap:12px;
  margin:0 0 12px;
  padding:10px 16px;
  border-radius:12px;
  background:#2B2E33;
  color:#F5F5F7;
  box-shadow:0 10px 24px -18px rgba(0,0,0,.75);
}
.habitaciones-view .hb-datebar__ic{
  display:grid; place-items:center;
  width:30px; height:30px; flex:0 0 30px;
  border-radius:9px;
  background:rgba(255,255,255,.13);
  font-size:.85rem;
}
.habitaciones-view .hb-datebar__txt{ display:flex; flex-direction:column; gap:1px; min-width:0; flex:1 1 auto; }
.habitaciones-view .hb-datebar__txt strong{
  font-size:.9rem; font-weight:700; line-height:1.2;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.habitaciones-view .hb-datebar__txt small{ font-size:.72rem; color:rgba(245,245,247,.72); line-height:1.2; }
.habitaciones-view .hb-datebar__short{ display:none; }
.habitaciones-view .hb-datebar__back{
  display:inline-flex; align-items:center; gap:7px;
  flex:0 0 auto;
  padding:7px 13px;
  border-radius:9px;
  background:#F5F5F7;
  color:#2B2E33;
  font-size:.78rem; font-weight:700;
  text-decoration:none;
  transition:transform .15s ease, background .15s ease;
}
.habitaciones-view .hb-datebar__back:hover{ background:#FFF; transform:translateY(-1px); }
.habitaciones-view .hb-datebar__back:focus-visible{ outline:2px solid #F5F5F7; outline-offset:2px; }
/* En oscuro el grafito se aclara para despegarse del fondo negro de Cupertino */
html[data-theme="dark"] .habitaciones-view .hb-datebar{
  background:#3A3D42;
  box-shadow:0 10px 24px -18px rgba(0,0,0,.9);
}
@media (max-width:640px){
  .habitaciones-view .hb-datebar{ gap:10px; padding:9px 12px; border-radius:10px; }
  .habitaciones-view .hb-datebar__full{ display:none; }
  .habitaciones-view .hb-datebar__short{ display:inline; }
  .habitaciones-view .hb-datebar__back span{ display:none; }
  .habitaciones-view .hb-datebar__back{ padding:8px 10px; }
}
/* Mismo candado que el header: con un modal abierto baja de z-index para que el
   backdrop la cubra (los modales de esta vista quedan atrapados en .container). */
body.ms-modal-abierto .habitaciones-view .hb-datebar{
  z-index:0!important;
  pointer-events:none!important;
}
</style>

<!-- =====================================================================
     Fluidez de la vista de habitaciones (rendimiento de animaciones).
     1) El reverso de la card se deslizaba animando 'top' (con top+bottom
        fijos -> mueve Y redimensiona su contenido con scroll cada frame =
        trabado). Se cambia a TRANSFORM: translateY, que corre en el
        compositor (GPU) sin reflow -> deslizamiento fluido.
     2) La cara de la card y los botones del reverso tienen fondo OPACO;
        su backdrop-filter:blur no se ve pero encarece scroll/animacion en
        toda la grilla. Se elimina (cero cambio visual, mas fluido).
     Va al final para ganar por orden de cascada. No cambia logica ni JS.
     ===================================================================== -->
<style id="hb-fluidity">
/* (1) Reverso: 'top' -> translateY (compositor puro, sin reflow) */
.habitaciones-view .flip-card-back{
  top:3px!important;
  bottom:3px!important;
  transform:translateY(calc(100% + 12px));
  transition:transform .42s cubic-bezier(.22,1,.36,1)!important;
}
.habitaciones-view .flip-card.flipped .flip-card-back{
  transform:translateY(0);
  transition:transform .42s cubic-bezier(.22,1,.36,1)!important;
  will-change:transform;
}

/* (2) Fondos opacos: el blur no aporta y encarece cada frame de scroll/flip */
.habitaciones-view .flip-card-front{ -webkit-backdrop-filter:none!important; backdrop-filter:none!important; }
.habitaciones-view .flip-card-back .btn-action{ -webkit-backdrop-filter:none!important; backdrop-filter:none!important; }

/* (2b) La etiqueta de precio era el hueco que quedaba: un backdrop-filter
   POR TARJETA (30-80 regiones de desenfoque vivas dentro del scroller).
   El fondo translúcido se queda; solo cae el blur. */
.habitaciones-view .flip-card-front .text-sm.font-bold{ -webkit-backdrop-filter:none!important; backdrop-filter:none!important; }

/* (3) En táctil, fuera las animaciones que PINTAN por frame (box-shadow /
   outline / border animados, barridos y shakes perpetuos): con N tarjetas
   con alerta el compositor nunca duerme y el scroll tironea. La alerta
   sigue gritando — colores, bordes y sombras quedan ESTÁTICOS — y los
   pulsos baratos de opacidad (compositables) se conservan. */
@media (pointer: coarse){
  .flip-card.has-checkin-vencido .flip-card-front,
  .flip-card.has-checkout-vencido .flip-card-front{ animation:none!important; }
  .checkin-vencido-indicator i,
  .checkout-vencido-indicator i{ animation:none!important; }
  .habitaciones-view .rc-incident--reservation-pending{ animation:none!important; }
  .habitaciones-view .rc-incident--reservation-pending::after{ animation:none!important; opacity:0!important; }
  .habitaciones-view .rc-incident--reservation-pending i{ animation:none!important; }
  .habitaciones-view .hb-alerts-mark-wrap::after{ animation:none!important; }
}
</style>
