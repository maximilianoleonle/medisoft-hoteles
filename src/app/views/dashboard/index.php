<?php
/**
 * Vista del Dashboard Principal - sistema hotelero operativo.
 */

$stats = $stats ?? [];
$caja_info = $caja_info ?? null;
$corte_actual = $corte_actual ?? null;
$reservaciones_hoy = $reservaciones_hoy ?? [];
$proximas_llegadas = $proximas_llegadas ?? [];
$proximas_salidas = $proximas_salidas ?? [];
$graficos = $graficos ?? [];

if (!function_exists('dashboard_safe')) {
    function dashboard_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('dashboard_format_date')) {
    function dashboard_format_date($value, $format = 'd/m/Y') {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return '-';
        }

        if ($format === 'l, d \d\e F') {
            $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
            $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
            $dia_semana = $dias[(int)date('w', $timestamp)];
            $dia = date('d', $timestamp);
            $mes = $meses[(int)date('n', $timestamp) - 1];

            return ucfirst($dia_semana) . ', ' . $dia . ' de ' . $mes;
        }

        if (function_exists('format_date')) {
            return format_date($value, $format);
        }

        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('dashboard_upper')) {
    function dashboard_upper($value) {
        $value = (string)$value;
        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }
}

if (!function_exists('dashboard_initials')) {
    function dashboard_initials($name) {
        $parts = preg_split('/\s+/', trim((string)$name));
        $letters = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            if (strlen($letters) >= 2) {
                break;
            }
        }

        $letters = $letters !== '' ? $letters : 'M';
        return function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
    }
}

if (!function_exists('dashboard_percent_text')) {
    function dashboard_percent_text($value) {
        $value = (float)$value;
        return rtrim(rtrim(number_format($value, 1), '0'), '.') . '%';
    }
}

if (!function_exists('dashboard_short_date')) {
    function dashboard_short_date($value) {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return '-';
        }

        $meses_cortos = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return date('d', $timestamp) . ' ' . $meses_cortos[(int)date('n', $timestamp) - 1];
    }
}

if (!function_exists('get_vehiculos_activos_hoy')) {
    function get_vehiculos_activos_hoy() {
        try {
            $db = Database::getInstance();
            $hotel_id = obtenerHotelIdActualCompat();

            $sql = "
                SELECT
                    hv.estacionamiento,
                    COUNT(DISTINCT hv.id) as total
                FROM huesped_vehiculos hv
                INNER JOIN reservaciones r ON hv.huesped_id = r.huesped_id
                WHERE r.hotel_id = ?
                AND r.estado = 'checked_in'
                AND r.fecha_entrada <= CURDATE()
                AND r.fecha_salida >= CURDATE()
                AND hv.activo = 1
                AND hv.estacionamiento = 'coches'
                GROUP BY hv.estacionamiento
            ";

            $stmt = $db->query($sql, [$hotel_id]);
            if (!$stmt) {
                return ['coches' => 0];
            }

            $resultados = $stmt->fetchAll();
            $conteo = ['coches' => 0];

            foreach ($resultados as $resultado) {
                if (($resultado['estacionamiento'] ?? '') === 'coches') {
                    $conteo['coches'] = (int)$resultado['total'];
                }
            }

            return $conteo;
        } catch (Exception $e) {
            error_log("Error obteniendo vehiculos activos: " . $e->getMessage());
            return ['coches' => 0];
        }
    }
}

if (!function_exists('get_lista_vehiculos_estacionamiento')) {
    function get_lista_vehiculos_estacionamiento() {
        try {
            $db = Database::getInstance();
            $hotel_id = obtenerHotelIdActualCompat();
            $sql = "
                SELECT
                    h.nombre_completo AS huesped,
                    GROUP_CONCAT(DISTINCT hab.numero ORDER BY hab.numero SEPARATOR ', ') AS habitaciones,
                    hv.marca,
                    hv.modelo,
                    hv.color,
                    hv.placas
                FROM huesped_vehiculos hv
                INNER JOIN reservaciones r ON hv.huesped_id = r.huesped_id
                INNER JOIN huespedes h ON h.id = hv.huesped_id
                LEFT JOIN reservacion_habitaciones rh ON rh.reservacion_id = r.id AND rh.hotel_id = r.hotel_id
                LEFT JOIN habitaciones hab ON hab.id = rh.habitacion_id AND hab.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado = 'checked_in'
                  AND r.fecha_entrada <= CURDATE()
                  AND r.fecha_salida >= CURDATE()
                  AND hv.activo = 1
                GROUP BY hv.id, h.id, hv.marca, hv.modelo, hv.color, hv.placas
                ORDER BY h.nombre_completo
            ";
            $stmt = $db->query($sql, [$hotel_id]);
            return $stmt ? ($stmt->fetchAll() ?: []) : [];
        } catch (Exception $e) {
            error_log('ERROR lista vehiculos estacionamiento: ' . $e->getMessage());
            return [];
        }
    }
}

$estadisticas_estacionamiento = get_vehiculos_activos_hoy();
$lista_vehiculos_estacionamiento = get_lista_vehiculos_estacionamiento();

$limite_coches = 30;
$coches = (int)($estadisticas_estacionamiento['coches'] ?? 0);
$pct_coches = $limite_coches > 0 ? min(100, max(0, round(($coches / $limite_coches) * 100))) : 0;
$espacios_disp = max(0, $limite_coches - $coches);
$ring_circumference = 314;
$ring_offset = $ring_circumference - ($ring_circumference * $pct_coches / 100);

$hotel_display_name = 'Medisoft Hoteles';
$hotel_branding = [];
if (function_exists('current_hotel_branding')) {
    try {
        $hotel_branding = current_hotel_branding();
    } catch (Throwable $e) {
        $hotel_branding = [];
    }
}

if (function_exists('current_hotel_display_name')) {
    $hotel_display_name = current_hotel_display_name('Medisoft Hoteles');
} elseif (function_exists('current_hotel_nombre')) {
    $hotel_nombre = trim((string)current_hotel_nombre());
    $hotel_display_name = $hotel_nombre !== '' ? $hotel_nombre : 'Medisoft Hoteles';
} elseif (!empty($_SESSION['hotel_nombre'])) {
    $hotel_display_name = $_SESSION['hotel_nombre'];
}

$hero_image_url = null;
if (function_exists('hotel_branding_asset_url') && !empty($hotel_branding['login_background_url'])) {
    $hero_image_url = hotel_branding_asset_url($hotel_branding['login_background_url']);
}
$hero_image_url = $hero_image_url ?: 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=1600&q=80';

$habitaciones_total = (int)($stats['habitaciones']['total'] ?? 0);
$habitaciones_ocupadas = (int)($stats['habitaciones']['ocupadas'] ?? 0);
$habitaciones_disponibles = (int)($stats['habitaciones']['disponibles_reales'] ?? ($stats['habitaciones']['disponibles'] ?? 0));
$habitaciones_por_llegar = (int)($stats['habitaciones']['por_llegar'] ?? 0);
$habitaciones_mantenimiento = (int)($stats['habitaciones']['mantenimiento'] ?? 0);
$habitaciones_limpieza = (int)($stats['habitaciones']['limpieza'] ?? 0);
$ocupacion_pct = min(100, max(0, (float)($stats['habitaciones']['porcentaje_ocupacion'] ?? 0)));
$habitaciones_libres = max(0, $habitaciones_total - $habitaciones_ocupadas);

$ingresos_total = (float)($stats['ingresos']['total_dia'] ?? 0);
$egresos_total = (float)($stats['egresos']['total_dia'] ?? 0);
$balance_dia = $ingresos_total - $egresos_total;
$entradas_total = (int)($stats['entradas']['total'] ?? 0);
$entradas_pendientes = (int)($stats['entradas']['pendientes'] ?? 0);
$salidas_total = (int)($stats['salidas']['total'] ?? 0);
$salidas_pendientes = (int)($stats['salidas']['pendientes'] ?? 0);

$hora = (int)date('G');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
$fecha_hoy = dashboard_format_date(date('Y-m-d'), 'l, d \d\e F');
$fecha_hero = dashboard_upper(str_replace(' de ', ' ', $fecha_hoy)) . ' · ' . date('H:i');
$caja_abierta = (bool)$corte_actual;
$usuario_nombre = function_exists('user_name') ? user_name() : ($_SESSION['nombre'] ?? 'Usuario');
$usuario_rol = function_exists('user_role') ? user_role() : ($_SESSION['rol'] ?? 'Hotel');
$usuario_iniciales = dashboard_initials($usuario_nombre);

$chart_data = $graficos['ocupacion_semanal'] ?? [];
$chart_max = max(1, $habitaciones_total);
$weekly_chart_rows = array_values(array_slice($chart_data, 0, 7));
$weekly_chart_count = count($weekly_chart_rows);
$weekly_chart_points = [];
$weekly_chart_area_points = '';
$weekly_chart_avg = 0;
$weekly_chart_peak = 0;
$weekly_chart_peak_day = '-';
$weekly_chart_total_pct = 0;

foreach ($weekly_chart_rows as $index => $row) {
    $ocupadas_week = max(0, (int)($row['ocupadas'] ?? 0));
    $pct_week = $chart_max > 0 ? min(100, max(0, ($ocupadas_week / $chart_max) * 100)) : 0;
    $weekly_chart_total_pct += $pct_week;

    if ($pct_week >= $weekly_chart_peak) {
        $weekly_chart_peak = $pct_week;
        $weekly_chart_peak_day = (string)($row['dia'] ?? '-');
    }

    $x = $weekly_chart_count > 1 ? ($index / ($weekly_chart_count - 1)) * 100 : 50;
    $y = 100 - $pct_week;
    $weekly_chart_points[] = round($x, 2) . ',' . round($y, 2);
}

if ($weekly_chart_count > 0) {
    $weekly_chart_avg = $weekly_chart_total_pct / $weekly_chart_count;
    $weekly_chart_area_points = '0,100 ' . implode(' ', $weekly_chart_points) . ' 100,100';
}

// ── Gráfica de línea/área "ocupación semanal" (curva suave) ──
$weekly_line_rows = [];
$weekly_line_peak_date = '';

foreach ($weekly_chart_rows as $index => $row) {
    $ocupadas_line = max(0, (int)($row['ocupadas'] ?? 0));
    $pct_line = $chart_max > 0 ? min(100, max(0, ($ocupadas_line / $chart_max) * 100)) : 0;
    $es_hoy_line = $index === $weekly_chart_count - 1;
    $es_pico_line = $weekly_chart_peak_day !== '-' && (string)($row['dia'] ?? '-') === $weekly_chart_peak_day;
    $x_line = $weekly_chart_count > 0 ? ((($index + 0.5) / $weekly_chart_count) * 100) : 50;

    if ($es_pico_line && $weekly_line_peak_date === '') {
        $weekly_line_peak_date = dashboard_short_date($row['fecha'] ?? '');
    }

    $weekly_line_rows[] = [
        'dia' => (string)($row['dia'] ?? '-'),
        'fecha' => dashboard_short_date($row['fecha'] ?? ''),
        'pct' => $pct_line,
        'x' => $x_line,
        'y' => 100 - $pct_line,
        'es_hoy' => $es_hoy_line,
        'es_pico' => $es_pico_line,
    ];
}

// Curva suave (Catmull-Rom → Bézier) para la línea y el área del gráfico
$weekly_line_path = '';
$weekly_area_path = '';
$weekly_line_count = count($weekly_line_rows);

if ($weekly_line_count === 1) {
    $p = $weekly_line_rows[0];
    $weekly_line_path = sprintf('M%.2f,%.2f L%.2f,%.2f', $p['x'], $p['y'], $p['x'], $p['y']);
} elseif ($weekly_line_count > 1) {
    $weekly_line_path = sprintf('M%.2f,%.2f', $weekly_line_rows[0]['x'], $weekly_line_rows[0]['y']);

    for ($i = 0; $i < $weekly_line_count - 1; $i++) {
        $p0 = $weekly_line_rows[max($i - 1, 0)];
        $p1 = $weekly_line_rows[$i];
        $p2 = $weekly_line_rows[$i + 1];
        $p3 = $weekly_line_rows[min($i + 2, $weekly_line_count - 1)];

        $cp1x = $p1['x'] + ($p2['x'] - $p0['x']) / 6;
        $cp1y = $p1['y'] + ($p2['y'] - $p0['y']) / 6;
        $cp2x = $p2['x'] - ($p3['x'] - $p1['x']) / 6;
        $cp2y = $p2['y'] - ($p3['y'] - $p1['y']) / 6;

        $weekly_line_path .= sprintf(' C%.2f,%.2f %.2f,%.2f %.2f,%.2f', $cp1x, $cp1y, $cp2x, $cp2y, $p2['x'], $p2['y']);
    }
}

if ($weekly_line_path !== '') {
    $weekly_area_path = $weekly_line_path
        . sprintf(' L%.2f,100 L%.2f,100 Z', $weekly_line_rows[$weekly_line_count - 1]['x'], $weekly_line_rows[0]['x']);
}

$weekly_line_updated_at = date('H:i');

// ── Compact mobile dashboard helpers (Claude Design "mobile-foto") ──
// Editorial hero + tight operational cards for phones. Reuses the same
// data as the desktop layout; no extra queries, no logic changes.
$m_ring_circ = 264; // 2·π·r con r = 42
$m_ring_offset = $m_ring_circ * (1 - min(100, max(0, $ocupacion_pct)) / 100);
$m_bar_width = static function ($count) use ($habitaciones_total) {
    if ($habitaciones_total <= 0 || $count <= 0) {
        return 0;
    }
    return min(100, max(4, (int) round($count / $habitaciones_total * 100)));
};
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

:root {
    --dash-primary: var(--brand-primary, #1B2746);
    --dash-secondary: var(--brand-secondary, #0F172A);
    --dash-accent: var(--brand-accent, #BD9441);
    --dash-ivory: color-mix(in srgb, var(--dash-accent) 9%, #F8F5ED);
    --dash-ivory-2: color-mix(in srgb, var(--dash-accent) 6%, #FBF9F4);
    --dash-surface: color-mix(in srgb, var(--dash-accent) 2%, #FFFFFF);
    --dash-surface-warm: color-mix(in srgb, var(--dash-accent) 5%, #FFFFFF);
    --dash-line: color-mix(in srgb, var(--dash-accent) 22%, #E7DEC9);
    --dash-line-soft: color-mix(in srgb, var(--dash-accent) 12%, #F0ECE2);
    --dash-navy: var(--dash-secondary);
    --dash-navy-700: color-mix(in srgb, var(--dash-secondary) 86%, var(--dash-primary));
    --dash-ink: var(--dash-secondary);
    --dash-slate-700: color-mix(in srgb, var(--dash-secondary) 70%, #64748B);
    --dash-slate-500: color-mix(in srgb, var(--dash-secondary) 48%, #94A3B8);
    --dash-slate-400: color-mix(in srgb, var(--dash-secondary) 35%, #CBD5E1);
    --dash-gold: var(--dash-accent);
    --dash-gold-mid: color-mix(in srgb, var(--dash-accent) 82%, #FFFFFF);
    --dash-gold-soft: color-mix(in srgb, var(--dash-accent) 64%, #FFFFFF);
    --dash-gold-bg: color-mix(in srgb, var(--dash-accent) 16%, #FFFFFF);
    --dash-gold-line: color-mix(in srgb, var(--dash-accent) 28%, #E2E8F0);
    --dash-available: #1E9E63;
    --dash-bg-available: #E7F4EC;
    --dash-occupied: #C2603C;
    --dash-bg-occupied: #F8EAE1;
    --dash-arriving: #5A57D2;
    --dash-bg-arriving: #ECEBFB;
    --dash-cleaning: #2F77E0;
    --dash-bg-cleaning: #E6EFFC;
    --dash-maint: #C2841C;
    --dash-bg-maint: #FAF0DC;
    --dash-critical: #D64539;
    --dash-bg-critical: #FBE9E7;
    --dash-r-xs: 8px;
    --dash-r-sm: 11px;
    --dash-r: 15px;
    --dash-r-lg: 20px;
    --dash-r-xl: 26px;
    --dash-shadow: 0 2px 8px color-mix(in srgb, var(--dash-secondary) 6%, transparent), 0 12px 28px color-mix(in srgb, var(--dash-secondary) 7%, transparent);
    --dash-shadow-lg: 0 18px 48px color-mix(in srgb, var(--dash-secondary) 16%, transparent);
    --dash-serif: 'Cormorant Garamond', Georgia, serif;
    --dash-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope .mobile-header-modern,
    body.hotel-layout-scope .hotel-header,
    body.hotel-layout-scope .scroll-progress {
        display: none !important;
    }
}

html,
body.hotel-layout-scope {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: hidden !important;
}

body.hotel-layout-scope {
    padding-top: 0 !important;
    background: var(--dash-ivory) !important;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope {
        margin-left: 0 !important;
    }
}

@media (max-width: 1024px) {
    body.hotel-layout-scope {
        padding-top: 64px !important;
    }
}

body.hotel-layout-scope > .flex.h-screen.overflow-hidden,
body.hotel-layout-scope > .flex.h-screen.overflow-hidden > .flex-1 {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    height: auto !important;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

@media (min-width: 1025px) {
    body.hotel-layout-scope > .flex.h-screen.overflow-hidden {
        box-sizing: border-box !important;
        padding-left: var(--sidebar-width) !important;
    }
}

body.hotel-layout-scope .main-content {
    width: 100% !important;
    max-width: 100% !important;
    min-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
    background: var(--dash-ivory) !important;
}

body.hotel-layout-scope .main-content > .dashboard-boutique {
    width: 100% !important;
    max-width: none !important;
    margin: 0 !important;
}

.dashboard-boutique {
    width: 100%;
    min-height: 100vh;
    display: block;
    color: var(--dash-ink);
    background:
        radial-gradient(circle at 92% 8%, rgba(194,160,90,.16), transparent 28rem),
        var(--dash-ivory);
    font-family: var(--dash-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.dashboard-boutique * {
    box-sizing: border-box;
}

@keyframes dashCardIn {
    from {
        opacity: 0;
        transform: translateY(14px) scale(.985);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes dashHeroLight {
    0%, 100% {
        opacity: .22;
        transform: translate3d(-6%, -2%, 0) scale(1);
    }
    50% {
        opacity: .42;
        transform: translate3d(2%, 2%, 0) scale(1.04);
    }
}

@keyframes dashPulseDot {
    0%, 100% {
        box-shadow: 0 0 0 0 color-mix(in srgb, currentColor 36%, transparent);
        transform: scale(1);
    }
    50% {
        box-shadow: 0 0 0 7px transparent;
        transform: scale(1.08);
    }
}

@keyframes dashIconFloat {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-3px);
    }
}

@keyframes dashProgressGrow {
    from {
        transform: scaleX(.08);
        opacity: .6;
    }
    to {
        transform: scaleX(1);
        opacity: 1;
    }
}

@keyframes dashRingDraw {
    from {
        stroke-dashoffset: 314;
    }
}

@keyframes dashWarmShift {
    0%, 100% {
        filter: saturate(1);
    }
    50% {
        filter: saturate(1.1) brightness(1.015);
    }
}

.avatar {
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 12px;
    background: linear-gradient(160deg, var(--dash-gold-mid), var(--dash-gold));
    color: #fff;
    font-weight: 800;
    font-size: 15px;
}

.boutique-main {
    min-width: 0;
    width: 100%;
    max-width: none;
    padding: clamp(18px, 1.55vw, 28px);
    padding-bottom: 42px;
}

.editorial-hero {
    position: relative;
    min-height: 225px;
    overflow: hidden;
    border-radius: 26px;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--dash-secondary) 86%, transparent), color-mix(in srgb, var(--dash-secondary) 18%, transparent)),
        url("<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>") center/cover;
    box-shadow: var(--dash-shadow-lg);
    isolation: isolate;
    animation: dashCardIn .58s cubic-bezier(.2, .78, .22, 1) both;
}

.editorial-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--dash-primary) 92%, transparent) 0%, color-mix(in srgb, var(--dash-primary) 62%, transparent) 48%, color-mix(in srgb, var(--dash-primary) 24%, transparent) 100%),
        radial-gradient(circle at 74% 42%, color-mix(in srgb, var(--dash-accent) 28%, transparent), transparent 21rem);
}

.editorial-hero::after {
    content: "";
    position: absolute;
    inset: -18% -10% auto auto;
    width: min(44vw, 560px);
    height: 260px;
    border-radius: 999px;
    background:
        radial-gradient(circle, color-mix(in srgb, var(--dash-accent) 42%, transparent), transparent 62%),
        radial-gradient(circle at 70% 20%, rgba(255,255,255,.18), transparent 38%);
    filter: blur(10px);
    pointer-events: none;
    animation: dashHeroLight 7s ease-in-out infinite;
}

.hero-top {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 22px 28px 0;
}

.hero-date {
    color: var(--dash-gold-soft);
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.hotel-switch,
.glass-button {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 0 15px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 11px;
    background: rgba(255,255,255,.14);
    color: #fff;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.26);
    backdrop-filter: blur(12px);
    font-size: 13px;
    font-weight: 700;
}

.glass-button {
    position: relative;
    width: 40px;
    justify-content: center;
    padding: 0;
}

.notification-dot {
    position: absolute;
    top: 9px;
    right: 10px;
    width: 7px;
    height: 7px;
    border-radius: 99px;
    background: var(--dash-critical);
    border: 1px solid #fff;
    animation: dashPulseDot 2.2s ease-in-out infinite;
}

.hero-title {
    position: relative;
    z-index: 1;
    margin-top: 18px;
    padding: 0 28px;
}

.hero-title h1 {
    margin: 0;
    color: #fff;
    font-family: var(--dash-serif);
    font-size: clamp(34px, 4.2vw, 50px);
    line-height: .95;
    font-weight: 650;
    letter-spacing: -.01em;
}

.hero-title p {
    margin: 9px 0 0;
    color: rgba(255,255,255,.78);
    font-size: 14px;
    font-weight: 700;
}


.grid4,
.grid2,
.grid3 {
    display: grid;
    gap: 20px;
    margin-top: 22px;
}

.grid4 {
    grid-template-columns: repeat(4, minmax(210px, 1fr));
}

.grid2 {
    grid-template-columns: minmax(0, 1.75fr) minmax(310px, .85fr);
}

.grid3 {
    grid-template-columns: repeat(3, minmax(260px, 1fr));
}

.card {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--dash-line);
    border-radius: 18px;
    background: var(--dash-surface);
    box-shadow: var(--dash-shadow);
    isolation: isolate;
    transition: transform .24s ease, border-color .24s ease, box-shadow .24s ease, background .24s ease;
}

.card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: 0;
    border-radius: inherit;
    background:
        radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--card-glow, var(--dash-accent)) 16%, transparent), transparent 12rem),
        linear-gradient(135deg, color-mix(in srgb, var(--card-glow, var(--dash-accent)) 4%, transparent), transparent 42%);
    opacity: 0;
    transition: opacity .24s ease;
    pointer-events: none;
}

.card > :not(.occ-wave) {
    position: relative;
    z-index: 1;
}

.occ-wave {
    z-index: 0;
}

.card:hover {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 34%, var(--dash-line));
    background: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 3%, var(--dash-surface));
    box-shadow: 0 20px 46px color-mix(in srgb, var(--dash-secondary) 14%, transparent);
}

.card:hover::before {
    opacity: 1;
}

.grid4 .card,
.grid2 .card,
.grid3 .card {
    animation: dashCardIn .52s cubic-bezier(.2, .78, .22, 1) both;
}

.grid4 .card:nth-child(1) {
    --card-glow: var(--dash-gold);
    animation-delay: .06s;
}

.grid4 .card:nth-child(2) {
    --card-glow: var(--dash-available);
    animation-delay: .12s;
}

.grid4 .card:nth-child(3) {
    --card-glow: var(--dash-arriving);
    animation-delay: .18s;
}

.grid4 .card:nth-child(4) {
    --card-glow: var(--dash-maint);
    animation-delay: .24s;
}

.grid2 .card:nth-child(1) {
    --card-glow: var(--dash-navy);
    animation-delay: .14s;
}

.grid2 .card:nth-child(2) {
    --card-glow: var(--dash-gold);
    animation-delay: .22s;
}

.grid3 .card:nth-child(1) {
    --card-glow: var(--dash-arriving);
    animation-delay: .2s;
}

.grid3 .card:nth-child(2) {
    --card-glow: var(--dash-maint);
    animation-delay: .27s;
}

.grid3 .card:nth-child(3) {
    --card-glow: var(--dash-available);
    animation-delay: .34s;
}

.card-pad {
    padding: 22px;
}

.occ-card {
    min-height: 334px;
}

.occ-wave {
    position: absolute;
    right: -80px;
    bottom: -100px;
    width: 360px;
    height: 180px;
    border-radius: 50%;
    background: linear-gradient(180deg, rgba(246,242,234,.42), rgba(255,255,255,0));
    animation: dashHeroLight 6.5s ease-in-out infinite;
}

.card-row-head,
.section-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.card-title {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .12em;
    line-height: 1.2;
    text-transform: uppercase;
}

.card-kicker-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    margin-top: 12px;
    color: var(--dash-gold);
    font-size: 12px;
    font-weight: 850;
    text-decoration: none;
    transition: color .18s ease, transform .18s ease;
}

.card-kicker-link i {
    font-size: 10px;
    transition: transform .18s ease;
}

.card-kicker-link:hover,
.card-kicker-link:focus-visible {
    color: var(--dash-navy);
    text-decoration: underline;
    text-underline-offset: 4px;
}

.card-kicker-link:hover i,
.card-kicker-link:focus-visible i {
    transform: translateX(2px);
}

.card-kicker-link:active {
    transform: translateY(1px);
}

.card-kicker-link:focus-visible,
.legend-link:focus-visible,
.list-row.is-action:focus-visible,
.dash-btn:focus-visible,
.dm-sec a:focus-visible,
.dm-rst.is-link:focus-visible,
.dm-ag.is-link:focus-visible,
.dm-btn:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--dash-gold) 70%, #fff);
    outline-offset: 3px;
}

.mini-icon {
    background: var(--dash-bg-occupied);
    color: var(--dash-occupied);
    transition: transform .24s ease, background .24s ease, color .24s ease;
}

.card:hover .mini-icon {
    transform: translateY(-2px) rotate(-3deg);
}

.grid4 .card:nth-child(1) .mini-icon,
.grid4 .card:nth-child(4) .mini-icon {
    animation: dashIconFloat 3.2s ease-in-out infinite;
}

.occ-percent {
    color: var(--dash-navy);
    font-size: 56px;
    line-height: .9;
    font-weight: 900;
    letter-spacing: -.04em;
    font-variant-numeric: tabular-nums;
}

.occ-meta {
    position: relative;
    z-index: 1;
    margin-top: 26px;
}

.occ-meta strong {
    color: var(--dash-navy);
    font-size: 25px;
    font-weight: 900;
}

.muted {
    color: var(--dash-slate-500);
}

.soft-note {
    margin-top: 5px;
    color: var(--dash-slate-500);
    font-size: 12.5px;
}

.money-total,
.metric-total {
    margin-top: 3px;
    color: var(--dash-navy);
    font-size: 26px;
    line-height: 1;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.money-section {
    margin: 18px 0 8px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .13em;
    text-transform: uppercase;
}

.money-line,
.legend-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    min-height: 27px;
    color: var(--dash-slate-700);
    font-size: 13px;
}

.legend-link {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 11px;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, transform .18s ease, color .18s ease;
}

.legend-link:hover,
.legend-link:focus-visible {
    background: color-mix(in srgb, var(--swatch, var(--dash-gold)) 9%, transparent);
    color: var(--dash-navy);
    transform: translateX(2px);
}

.legend-link:active {
    transform: translateX(1px) scale(.995);
}

.legend-value {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--dash-navy);
}

.legend-value i {
    color: var(--dash-slate-400);
    font-size: 10px;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.legend-link:hover .legend-value i,
.legend-link:focus-visible .legend-value i {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

.money-line strong,
.legend-line strong {
    color: var(--dash-navy);
    font-variant-numeric: tabular-nums;
}

.balance-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--dash-line);
}

.balance-line span {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.balance-line strong {
    color: var(--dash-available);
    font-size: 20px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    margin-top: 15px;
    padding: 7px 13px;
    border-radius: 999px;
    background: var(--dash-bg-available);
    color: var(--dash-available);
    font-size: 12px;
    font-weight: 900;
    transition: transform .2s ease, background .2s ease, color .2s ease;
}

.card:hover .status-badge {
    transform: translateY(-1px);
}

.status-badge.warn {
    background: var(--dash-bg-maint);
    color: var(--dash-maint);
}

.status-badge.critical {
    background: var(--dash-bg-critical);
    color: var(--dash-critical);
}

.led {
    width: 6px;
    height: 6px;
    border-radius: 99px;
    background: currentColor;
    animation: dashPulseDot 2.4s ease-in-out infinite;
}

.swatch {
    width: 8px;
    height: 8px;
    flex: 0 0 auto;
    border-radius: 99px;
    background: var(--swatch, var(--dash-gold));
}

.legend-line span:first-child {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.section-head {
    margin-bottom: 16px;
}

.section-head h2 {
    margin: 0;
    color: var(--dash-navy);
    font-family: var(--dash-serif);
    font-size: 24px;
    line-height: 1;
    font-weight: 650;
}

.weekly-occupancy-card {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 88% 4%, color-mix(in srgb, var(--dash-gold) 18%, transparent), transparent 18rem),
        linear-gradient(180deg, rgba(255,253,248,.98), rgba(250,246,238,.96));
}

.weekly-occupancy-card::after {
    content: "";
    position: absolute;
    inset: auto 22px 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--dash-gold), var(--dash-navy));
    opacity: .72;
}

.weekly-chart-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: start;
    margin-bottom: 14px;
}

.weekly-chart-head h2 {
    margin: 0;
    color: var(--dash-navy);
    font-family: var(--dash-serif);
    font-size: 24px;
    line-height: 1;
    font-weight: 650;
}

.weekly-chart-head p {
    margin: 6px 0 0;
    color: var(--dash-slate-500);
    font-size: 12px;
    font-weight: 750;
}

.weekly-chart-summary {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.weekly-summary-chip {
    min-width: 96px;
    border: 1px solid var(--dash-line);
    border-radius: 14px;
    background: rgba(255,255,255,.74);
    padding: 9px 10px;
}

.weekly-summary-chip span {
    display: block;
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.weekly-summary-chip strong {
    display: block;
    margin-top: 4px;
    color: var(--dash-navy);
    font-size: 18px;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.weekly-summary-chip em {
    display: block;
    margin-top: 3px;
    font-style: normal;
    font-size: 12px;
    font-weight: 850;
    color: var(--dash-gold);
    font-variant-numeric: tabular-nums;
}

.weekly-line-chart {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 14px;
    margin-top: 16px;
}

.weekly-line-axis {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    height: 200px;
    padding: 36px 0 12px;
    box-sizing: border-box;
    text-align: right;
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}

.weekly-line-body {
    min-width: 0;
}

.weekly-line-plot {
    position: relative;
    height: 200px;
    box-sizing: border-box;
    padding: 36px 14px 12px;
    border: 1px solid var(--dash-line);
    border-radius: 18px;
    background-color: rgba(255,255,255,.6);
    background-image: repeating-linear-gradient(to top, color-mix(in srgb, var(--dash-navy) 9%, transparent) 0 1px, transparent 1px 25%);
    background-origin: content-box;
    background-repeat: no-repeat;
}

.weekly-line-svg,
.weekly-line-points {
    position: absolute;
    inset: 36px 14px 12px;
}

.weekly-line-svg {
    width: calc(100% - 28px);
    height: calc(100% - 48px);
    overflow: visible;
}

.weekly-line-stroke {
    fill: none;
    stroke: var(--dash-navy);
    stroke-width: 2.5px;
    stroke-linecap: round;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
}

.weekly-line-area {
    stroke: none;
}

.weekly-line-stop-start {
    stop-color: var(--dash-navy);
    stop-opacity: .22;
}

.weekly-line-stop-end {
    stop-color: var(--dash-navy);
    stop-opacity: 0;
}

.weekly-line-point {
    position: absolute;
    transform: translateX(-50%);
}

.weekly-line-dot {
    display: block;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--dash-navy);
    border: 2px solid var(--dash-surface);
    box-shadow: 0 2px 6px color-mix(in srgb, var(--dash-navy) 30%, transparent);
    transform: translateY(50%);
}

.weekly-line-value {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    font-weight: 900;
    color: var(--dash-navy);
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.weekly-line-point.is-peak .weekly-line-dot {
    width: 13px;
    height: 13px;
    background: var(--dash-gold);
    border-width: 2px;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--dash-gold) 22%, transparent);
}

.weekly-line-point.is-peak .weekly-line-value {
    bottom: 17px;
    font-size: 12px;
    color: var(--dash-gold);
}

.weekly-line-labels {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    padding: 0 14px;
}

.weekly-line-label {
    flex: 1 1 0;
    min-width: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    text-align: center;
}

.weekly-line-label .d {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.weekly-line-label .f {
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.weekly-line-label.is-peak .d,
.weekly-line-label.is-peak .f {
    color: var(--dash-gold);
}

.weekly-line-label.is-today .d::after {
    content: "";
    display: inline-block;
    width: 5px;
    height: 5px;
    margin-left: 4px;
    border-radius: 50%;
    background: var(--dash-navy);
    vertical-align: middle;
}

.weekly-line-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 14px;
    padding-top: 12px;
    border-top: 1px solid var(--dash-line-soft);
}

.weekly-line-foot-info {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 700;
}

.weekly-line-foot-info svg {
    flex-shrink: 0;
    color: var(--dash-slate-400);
}

.weekly-line-foot-time {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.parking-head-note {
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .04em;
    white-space: nowrap;
}

.park-ring {
    display: grid;
    justify-items: center;
    gap: 14px;
}

.ring-box {
    position: relative;
    width: 136px;
    height: 136px;
}

.ring-box svg {
    width: 136px;
    height: 136px;
}

.ring-box svg circle[stroke-dasharray] {
    animation: dashRingDraw .9s cubic-bezier(.2, .78, .22, 1) both .18s;
}

.ring-num {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: var(--dash-navy);
    text-align: center;
}

.ring-num b {
    display: block;
    font-size: 38px;
    line-height: .85;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.ring-num span {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 800;
}

.park-bar {
    width: 100%;
    margin-top: 2px;
    padding: 15px;
    border: 1px solid var(--dash-line);
    border-radius: 13px;
    background: var(--dash-surface-warm);
}

.park-bar-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 850;
}

.park-bar-head b {
    color: var(--dash-gold);
    font-variant-numeric: tabular-nums;
}

.progress {
    height: 7px;
    overflow: hidden;
    margin-top: 10px;
    border-radius: 999px;
    background: #E8E1D4;
}

.progress i {
    display: block;
    width: var(--progress);
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--dash-gold-mid), var(--dash-gold));
    transform-origin: left center;
    animation: dashProgressGrow .72s cubic-bezier(.2, .78, .22, 1) both .18s;
}

.list-row {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    border-bottom: 1px solid var(--dash-line-soft);
    color: inherit;
    text-decoration: none;
    transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
}

.list-row.is-action {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 13px;
    cursor: pointer;
}

.list-row:last-child {
    border-bottom: 0;
}

.list-row.is-action:hover,
.list-row.is-action:focus-visible {
    background: color-mix(in srgb, var(--card-glow, var(--dash-accent)) 6%, transparent);
    box-shadow: 0 10px 22px color-mix(in srgb, var(--dash-secondary) 8%, transparent);
    transform: translateX(3px);
}

.list-row.is-action:active {
    transform: translateX(2px) scale(.995);
}

.list-avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: #fff;
    background: linear-gradient(150deg, #6E6BD8, #5A57D2);
    font-size: 12px;
    font-weight: 900;
}

.list-avatar.gold {
    background: linear-gradient(150deg, var(--dash-gold-mid), var(--dash-gold));
}

.list-name {
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 850;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.list-meta {
    margin-top: 2px;
    color: var(--dash-slate-500);
    font-size: 11.5px;
    font-weight: 650;
}

.list-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 9px;
    text-align: right;
}

.list-right-copy {
    min-width: 0;
}

.list-time {
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.list-action-icon {
    width: 24px;
    height: 24px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border: 1px solid var(--dash-line);
    border-radius: 999px;
    color: var(--dash-gold);
    background: #fff;
    font-size: 10px;
    opacity: .62;
    transition: opacity .18s ease, transform .18s ease, border-color .18s ease, background .18s ease;
}

.list-row.is-action:hover .list-name,
.list-row.is-action:focus-visible .list-name {
    color: var(--dash-secondary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.list-row.is-action:hover .list-action-icon,
.list-row.is-action:focus-visible .list-action-icon {
    border-color: color-mix(in srgb, var(--dash-gold) 42%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-gold) 8%, #fff);
    opacity: 1;
    transform: translateX(2px);
}

.cash-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.cash-box {
    min-height: 82px;
    padding: 13px;
    border: 1px solid var(--dash-line);
    border-radius: 13px;
    background: var(--dash-surface-warm);
    transition: transform .22s ease, border-color .22s ease, background .22s ease;
}

.cash-box.dark {
    border-color: var(--dash-navy);
    background: var(--dash-navy);
    animation: none;
}

.cash-box:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--dash-accent) 34%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-accent) 5%, var(--dash-surface-warm));
}

.cash-box.dark:hover {
    transform: none;
    border-color: var(--dash-navy);
    background: var(--dash-navy);
}

.cash-box .label {
    color: var(--dash-slate-500);
    font-size: 11px;
    font-weight: 850;
}

.cash-box.dark .label {
    color: var(--dash-gold-soft);
}

.cash-box .amount {
    margin-top: 8px;
    color: var(--dash-navy);
    font-size: 17px;
    line-height: 1;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.cash-box.dark .amount {
    color: #fff;
}

.button-row {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}

.dash-btn {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 13px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 12.5px;
    font-weight: 850;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.dash-btn.primary {
    flex: 1;
    background: var(--dash-navy);
    color: #fff;
}

.dash-btn.ghost {
    border: 1px solid var(--dash-line);
    color: var(--dash-navy);
    background: #fff;
}

.dash-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 13px 24px color-mix(in srgb, var(--dash-secondary) 13%, transparent);
}

.dash-btn:active {
    transform: translateY(0) scale(.99);
}

.empty-state {
    min-height: 140px;
    display: grid;
    place-items: center;
    color: var(--dash-slate-500);
    text-align: center;
    font-size: 13px;
    line-height: 1.45;
}

@media (min-width: 1600px) {
    .dashboard-boutique {
        grid-template-columns: 268px minmax(0, 1fr);
    }

    .boutique-main {
        padding: 24px 30px 46px;
    }

    .editorial-hero {
        min-height: 250px;
    }

    .grid4,
    .grid2,
    .grid3 {
        gap: 22px;
        margin-top: 22px;
    }

    .grid2 {
        grid-template-columns: minmax(0, 2fr) minmax(340px, .72fr);
    }

    .occ-card {
        min-height: 318px;
    }

    .card-pad {
        padding: 24px;
    }
}

@media (min-width: 1920px) {
    .boutique-main {
        padding-left: 34px;
        padding-right: 34px;
    }

    .editorial-hero {
        min-height: 270px;
    }

    .grid2 {
        grid-template-columns: minmax(0, 2.25fr) minmax(380px, .72fr);
    }
}

@media (max-width: 1280px) {
    .dashboard-boutique {
        grid-template-columns: 232px minmax(0, 1fr);
    }

    .boutique-main {
        padding: 20px;
        padding-bottom: 34px;
    }
}

@media (max-width: 1120px) {
    .grid4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid2 {
        grid-template-columns: 1fr;
    }

    .grid3 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .dashboard-boutique {
        display: block;
    }

    .boutique-main {
        padding: 16px;
    }

    .editorial-hero {
        min-height: 360px;
    }

    .hero-top {
        align-items: flex-start;
        flex-direction: column;
    }


    .grid4,
    .grid2 {
        grid-template-columns: 1fr;
    }

    .grid3 {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 560px) {
    .hero-actions {
        width: 100%;
        justify-content: space-between;
    }

    .hotel-switch {
        max-width: 190px;
    }

    .hero-title {
        padding: 0 18px;
    }

    .hero-title h1 {
        font-size: 36px;
    }


    .editorial-hero {
        min-height: 360px;
    }

    .cash-grid {
        grid-template-columns: 1fr;
    }

    .weekly-chart-head {
        grid-template-columns: 1fr;
    }

    .weekly-chart-summary {
        justify-content: stretch;
    }

    .weekly-summary-chip {
        flex: 1 1 120px;
    }

    .weekly-line-chart {
        grid-template-columns: 26px minmax(0, 1fr);
        gap: 8px;
    }

    .weekly-line-axis,
    .weekly-line-plot {
        height: 168px;
    }

    .weekly-line-plot {
        padding: 30px 8px 10px;
    }

    .weekly-line-svg,
    .weekly-line-points {
        inset: 30px 8px 10px;
    }

    .weekly-line-svg {
        width: calc(100% - 16px);
        height: calc(100% - 40px);
    }

    .weekly-line-labels,
    .weekly-line-foot {
        padding: 0 8px;
    }

    .weekly-line-foot {
        padding-left: 8px;
        padding-right: 8px;
    }

    .weekly-line-label .f {
        display: none;
    }

    .weekly-line-foot-time {
        display: none;
    }
}

/* ============================================================
   COMPACT MOBILE DASHBOARD  ·  Claude Design "mobile-foto"
   Boutique editorial photo hero + tight operational cards.
   Hidden on desktop; replaces the stacked grid layout on phones.
   ============================================================ */
.dash-mobile {
    display: none;
}

.dm-hero {
    position: relative;
    display: flex;
    align-items: flex-end;
    min-height: 200px;
    overflow: hidden;
    border-radius: 0 0 24px 24px;
    background-position: center;
    background-size: cover;
    background-repeat: no-repeat;
    box-shadow: var(--dash-shadow);
    animation: dashCardIn .52s cubic-bezier(.2, .78, .22, 1) both;
}

.dm-hero-scrim {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg,
            color-mix(in srgb, var(--dash-secondary) 55%, transparent) 0%,
            color-mix(in srgb, var(--dash-secondary) 10%, transparent) 34%,
            color-mix(in srgb, var(--dash-secondary) 84%, transparent) 100%),
        radial-gradient(circle at 78% 30%, color-mix(in srgb, var(--dash-accent) 22%, transparent), transparent 16rem);
    animation: dashHeroLight 7s ease-in-out infinite;
}

.dm-hero-txt {
    position: relative;
    z-index: 1;
    padding: 0 18px 18px;
}

.dm-eyebrow {
    color: var(--dash-gold-soft);
    font-size: 9.5px;
    font-weight: 800;
    letter-spacing: .18em;
    text-transform: uppercase;
}

.dm-hotel {
    margin: 5px 0 0;
    color: #fff;
    font-family: var(--dash-serif);
    font-size: 30px;
    line-height: 1;
    font-weight: 650;
    letter-spacing: -.01em;
}

.dm-greet {
    margin: 6px 0 0;
    color: rgba(255, 255, 255, .82);
    font-size: 12.5px;
    font-weight: 700;
}

.dm-body {
    padding: 0 14px;
}

.dm-sec {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin: 16px 4px 8px;
    color: var(--dash-slate-400);
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.dm-sec a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--dash-gold);
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: none;
    text-decoration: none;
    transition: color .18s ease, transform .18s ease;
}

.dm-sec a::after {
    content: ">";
    font-size: 10px;
    line-height: 1;
    opacity: .65;
    transition: transform .18s ease, opacity .18s ease;
}

.dm-sec a:hover,
.dm-sec a:focus-visible {
    color: var(--dash-navy);
}

.dm-sec a:hover::after,
.dm-sec a:focus-visible::after {
    opacity: 1;
    transform: translateX(2px);
}

.dm-sec a:active {
    transform: translateY(1px);
}

.dm-card {
    border: 1px solid var(--dash-line);
    border-radius: 15px;
    padding: 14px;
    background: var(--dash-surface);
    box-shadow: var(--dash-shadow);
    animation: dashCardIn .48s cubic-bezier(.2, .78, .22, 1) both;
    transition: transform .22s ease, border-color .22s ease, background .22s ease;
}

.dm-card + .dm-card {
    margin-top: 9px;
}

.dm-body > .dm-card:nth-child(2) {
    animation-delay: .05s;
}

.dm-body > .dm-card:nth-child(4) {
    animation-delay: .1s;
}

.dm-body > .dm-card:nth-child(6) {
    animation-delay: .15s;
}

.dm-body > .dm-card:nth-child(8) {
    animation-delay: .2s;
}

.dm-body > .dm-card:nth-child(10) {
    animation-delay: .25s;
}

.dm-card:active {
    transform: scale(.99);
    border-color: color-mix(in srgb, var(--dash-accent) 28%, var(--dash-line));
    background: color-mix(in srgb, var(--dash-accent) 4%, var(--dash-surface));
}

.dm-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    background: var(--dash-bg-available);
    color: var(--dash-available);
    font-size: 11.5px;
    font-weight: 800;
    white-space: nowrap;
}

.dm-badge .led {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    animation: dashPulseDot 2.4s ease-in-out infinite;
}

/* Ocupación */
.dm-occ {
    display: flex;
    align-items: center;
    gap: 15px;
}

.dm-ring {
    position: relative;
    width: 76px;
    height: 76px;
    flex: 0 0 auto;
}

.dm-ring svg {
    width: 76px;
    height: 76px;
}

.dm-ring svg circle[stroke-dasharray] {
    animation: dashRingDraw .85s cubic-bezier(.2, .78, .22, 1) both .12s;
}

.dm-ring b {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: var(--dash-navy);
    font-size: 19px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-occ-meta .big {
    color: var(--dash-navy);
    font-size: 21px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-occ-meta .big span {
    color: var(--dash-slate-400);
    font-size: 15px;
    font-weight: 700;
}

.dm-occ-meta .sub {
    margin-top: 1px;
    color: var(--dash-slate-500);
    font-size: 12.5px;
    font-weight: 700;
}

.dm-occ-meta .dm-badge {
    margin-top: 8px;
}

/* Movimientos del día */
.dm-mvtop {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.dm-mvtop .lbl {
    color: var(--dash-slate-400);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.dm-mvtop .bal {
    color: var(--dash-navy);
    font-size: 22px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
    margin-top: 11px;
}

.dm-split .b {
    padding: 9px 11px;
    border: 1px solid var(--dash-line);
    border-radius: 11px;
    background: var(--dash-surface-warm);
}

.dm-split .t {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.dm-split .v {
    margin-top: 4px;
    font-size: 16px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

/* Habitaciones — barras de estado */
.dm-rst {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 0;
    border-top: 1px solid var(--dash-line-soft);
}

.dm-rst.is-link {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 12px;
    color: inherit;
    text-decoration: none;
    cursor: pointer;
    transition: background .18s ease, transform .18s ease;
}

.dm-rst.is-link:hover,
.dm-rst.is-link:focus-visible {
    background: color-mix(in srgb, var(--state-accent, var(--dash-accent)) 8%, transparent);
    transform: translateX(2px);
}

.dm-rst.is-link:active {
    transform: translateX(1px) scale(.995);
}

.dm-rst:first-child {
    border-top: 0;
}

.dm-rst .dt {
    width: 9px;
    height: 9px;
    flex: 0 0 auto;
    border-radius: 50%;
}

.dm-rst .nm {
    color: var(--dash-slate-700);
    font-size: 13.5px;
    font-weight: 700;
    white-space: nowrap;
}

.dm-rst .bar {
    flex: 1;
    height: 6px;
    overflow: hidden;
    border-radius: 99px;
    background: color-mix(in srgb, var(--dash-accent) 14%, #EFEADF);
}

.dm-rst .bar i {
    display: block;
    height: 100%;
    border-radius: 99px;
    transform-origin: left center;
    animation: dashProgressGrow .68s cubic-bezier(.2, .78, .22, 1) both;
}

.dm-rst .ct {
    width: 26px;
    text-align: right;
    color: var(--dash-navy);
    font-size: 15px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-rst .go {
    width: 14px;
    color: var(--dash-slate-400);
    font-size: 10px;
    text-align: right;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.dm-rst.is-link:hover .go,
.dm-rst.is-link:focus-visible .go {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

/* Agenda */
.dm-ag-h {
    margin-bottom: 4px;
    font-size: 11px;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.dm-ag {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 8px 0;
    border-top: 1px solid var(--dash-line-soft);
    color: inherit;
    text-decoration: none;
    transition: transform .2s ease, background .2s ease;
}

.dm-ag.is-link {
    margin-inline: -8px;
    padding-inline: 8px;
    border-radius: 12px;
    cursor: pointer;
}

.dm-ag.is-link:hover,
.dm-ag.is-link:focus-visible {
    background: color-mix(in srgb, var(--dash-accent) 6%, transparent);
    transform: translateX(2px);
}

.dm-ag.first {
    border-top: 0;
}

.dm-ag.is-link:active {
    transform: translateX(3px);
    background: color-mix(in srgb, var(--dash-accent) 5%, transparent);
}

.dm-ag .av {
    width: 34px;
    height: 34px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 10px;
    color: #fff;
    font-size: 12px;
    font-weight: 900;
    background: linear-gradient(150deg, #6E6BD8, #5A57D2);
}

.dm-ag .av.gold {
    background: linear-gradient(150deg, var(--dash-gold-mid), var(--dash-gold));
}

.dm-ag .nm {
    color: var(--dash-navy);
    font-size: 13.5px;
    font-weight: 800;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.dm-ag .mt {
    margin-top: 1px;
    color: var(--dash-slate-500);
    font-size: 11.5px;
    font-weight: 650;
}

.dm-ag .tm {
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-ag .tm i {
    color: var(--dash-slate-400);
    font-size: 10px;
    opacity: .55;
    transition: color .18s ease, opacity .18s ease, transform .18s ease;
}

.dm-ag.is-link:hover .nm,
.dm-ag.is-link:focus-visible .nm {
    color: var(--dash-secondary);
    text-decoration: underline;
    text-underline-offset: 3px;
}

.dm-ag.is-link:hover .tm i,
.dm-ag.is-link:focus-visible .tm i {
    color: var(--dash-gold);
    opacity: 1;
    transform: translateX(2px);
}

.dm-ag-div {
    height: 1px;
    margin: 11px 0;
    border: 0;
    background: var(--dash-line);
}

.dm-empty {
    padding: 9px 0;
    color: var(--dash-slate-500);
    font-size: 12.5px;
}

/* Estado de caja */
.dm-caja {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
}

.dm-cbox {
    padding: 11px;
    border: 1px solid var(--dash-line);
    border-radius: 11px;
    background: var(--dash-surface-warm);
    transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.dm-cbox.dark {
    border-color: var(--dash-navy);
    background: var(--dash-navy);
    animation: none;
}

.dm-cbox:active {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--dash-accent) 30%, var(--dash-line));
}

.dm-cbox .t {
    color: var(--dash-slate-500);
    font-size: 10px;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.dm-cbox.dark .t {
    color: var(--dash-gold-soft);
}

.dm-cbox .v {
    margin-top: 7px;
    color: var(--dash-navy);
    font-size: 16px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
}

.dm-cbox.dark .v {
    color: #fff;
}

.dm-actions {
    display: flex;
    gap: 9px;
    margin-top: 11px;
}

.dm-btn {
    flex: 1;
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border-radius: 11px;
    font-size: 12.5px;
    font-weight: 850;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
}

.dm-btn.primary {
    background: var(--dash-navy);
    color: #fff;
}

.dm-btn.ghost {
    border: 1px solid var(--dash-line);
    background: #fff;
    color: var(--dash-navy);
}

.dm-btn:active {
    transform: scale(.985);
}

@media (max-width: 767px) {
    .dashboard-boutique .editorial-hero,
    .dashboard-boutique .grid4,
    .dashboard-boutique .grid2,
    .dashboard-boutique .grid3 {
        display: none !important;
    }

    .dashboard-boutique .dash-mobile {
        display: block;
    }

    .dashboard-boutique .boutique-main {
        padding: 0 0 30px !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .dashboard-boutique *,
    .dashboard-boutique *::before,
    .dashboard-boutique *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
    }
}
</style>

<div class="dashboard-boutique">
    <main class="boutique-main">
        <section class="editorial-hero">
            <div class="hero-top">
                <div class="hero-date"><?= dashboard_safe($fecha_hero) ?></div>
                <div class="hero-actions">
                    <div class="hotel-switch">
                        <span><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></span>
                    </div>
                    <div class="glass-button" aria-label="Notificaciones">
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span class="notification-dot"></span>
                    </div>
                    <div class="avatar" style="border-radius:50%"><?= dashboard_safe($usuario_iniciales, 'M') ?></div>
                </div>
            </div>

            <div class="hero-title">
                <h1><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></h1>
                <p><?= dashboard_safe($saludo, 'Hola') ?>, <?= dashboard_safe($usuario_nombre, 'usuario') ?> · Operación hotelera · Recepción</p>
            </div>

        </section>

        <section class="grid4">
            <article class="card card-pad occ-card">
                <div class="occ-wave"></div>
                <div class="card-row-head">
                    <div class="mini-icon"><i class="fas fa-bed" aria-hidden="true"></i></div>
                    <div style="text-align:right">
                        <div class="card-title">Ocupación</div>
                        <div class="occ-percent"><?= dashboard_percent_text($ocupacion_pct) ?></div>
                    </div>
                </div>
                <div class="occ-meta">
                    <strong><?= $habitaciones_ocupadas ?></strong>
                    <span class="muted">/ <?= $habitaciones_total ?> habitaciones ocupadas</span>
                    <div class="soft-note"><?= $habitaciones_libres ?> disponibles esta noche</div>
                    <a class="card-kicker-link" href="<?= url('habitaciones') ?>" title="Ver el tablero de habitaciones">
                        Ver habitaciones
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </article>

            <article class="card card-pad">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-bg-available);color:var(--dash-available)">
                        <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Movimientos del día</div>
                        <div class="money-total"><?= format_money($balance_dia) ?></div>
                    </div>
                </div>
                <div class="money-section" style="color:var(--dash-available)">Ingresos</div>
                <div class="money-line"><span>Efectivo</span><strong><?= format_money($stats['ingresos']['efectivo_dia'] ?? 0) ?></strong></div>
                <div class="money-line"><span>Tarjeta</span><strong><?= format_money($stats['ingresos']['tarjeta_dia'] ?? 0) ?></strong></div>
                <div class="money-line"><span>Transferencia</span><strong><?= format_money($stats['ingresos']['transferencia_dia'] ?? 0) ?></strong></div>
                <div class="money-section" style="color:var(--dash-critical)">Egresos</div>
                <div class="money-line"><span>Efectivo</span><strong><?= format_money($stats['egresos']['efectivo_dia'] ?? 0) ?></strong></div>
                <div class="money-line"><span>Transferencia</span><strong><?= format_money($stats['egresos']['transferencia_dia'] ?? 0) ?></strong></div>
                <div class="balance-line"><span>Balance</span><strong><?= format_money($balance_dia) ?></strong></div>
                <a class="card-kicker-link" href="<?= url('caja') ?>" title="Ir a caja para revisar movimientos">
                    Revisar caja
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="card card-pad">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-bg-arriving);color:var(--dash-arriving)">
                        <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Movimientos</div>
                        <div class="metric-total"><?= $entradas_total + $salidas_total ?></div>
                    </div>
                </div>
                <div style="margin-top:18px">
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-available)"></i>Entradas</span><strong><?= $entradas_total ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-maint)"></i>Salidas</span><strong><?= $salidas_total ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-arriving)"></i>Pendientes</span><strong><?= $entradas_pendientes + $salidas_pendientes ?></strong></div>
                </div>
                <div class="status-badge"><span class="led"></span>Jornada en curso</div>
                <a class="card-kicker-link" href="<?= url('reservaciones') ?>" title="Ver listado de reservaciones">
                    Ver reservaciones
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

            <article class="card card-pad">
                <div class="card-row-head" style="justify-content:flex-start">
                    <div class="mini-icon" style="background:var(--dash-gold-bg);color:var(--dash-gold)">
                        <i class="fas fa-building" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div class="card-title">Habitaciones</div>
                        <div class="metric-total"><?= $habitaciones_total ?></div>
                    </div>
                </div>
                <div style="margin-top:14px">
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=disponible') ?>" style="--swatch:var(--dash-available)" title="Ver habitaciones disponibles">
                        <span><i class="swatch" style="--swatch:var(--dash-available)"></i>Disponibles</span>
                        <span class="legend-value"><strong><?= $habitaciones_disponibles ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=ocupada') ?>" style="--swatch:var(--dash-occupied)" title="Ver habitaciones ocupadas">
                        <span><i class="swatch" style="--swatch:var(--dash-occupied)"></i>Ocupadas</span>
                        <span class="legend-value"><strong><?= $habitaciones_ocupadas ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=por_llegar') ?>" style="--swatch:var(--dash-arriving)" title="Ver habitaciones por llegar">
                        <span><i class="swatch" style="--swatch:var(--dash-arriving)"></i>Por llegar</span>
                        <span class="legend-value"><strong><?= $habitaciones_por_llegar ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=limpieza') ?>" style="--swatch:var(--dash-cleaning)" title="Ver habitaciones en limpieza">
                        <span><i class="swatch" style="--swatch:var(--dash-cleaning)"></i>Limpieza</span>
                        <span class="legend-value"><strong><?= $habitaciones_limpieza ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                    <a class="legend-line legend-link" href="<?= url('habitaciones?estado=mantenimiento') ?>" style="--swatch:var(--dash-maint)" title="Ver habitaciones en mantenimiento">
                        <span><i class="swatch" style="--swatch:var(--dash-maint)"></i>Mantenimiento</span>
                        <span class="legend-value"><strong><?= $habitaciones_mantenimiento ?></strong><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                    </a>
                </div>
            </article>
        </section>

        <section class="grid2">
            <article class="card card-pad weekly-occupancy-card">
                <div class="weekly-chart-head">
                    <div>
                        <h2>Ocupación semanal</h2>
                        <p>Comparativa diaria de habitaciones ocupadas frente al total activo.</p>
                    </div>
                    <div class="weekly-chart-summary">
                        <div class="weekly-summary-chip">
                            <span>Promedio semanal</span>
                            <strong><?= dashboard_percent_text($weekly_chart_avg) ?></strong>
                        </div>
                        <div class="weekly-summary-chip">
                            <span>Día pico</span>
                            <strong><?= dashboard_safe($weekly_chart_peak_day) ?> <?= dashboard_safe($weekly_line_peak_date) ?></strong>
                            <em><?= dashboard_percent_text($weekly_chart_peak) ?></em>
                        </div>
                    </div>
                </div>
                <?php if (empty($weekly_chart_rows)): ?>
                    <div class="empty-state">Sin datos de ocupación semanal para mostrar.</div>
                <?php else: ?>
                    <div class="weekly-line-chart" aria-label="Gráfica de ocupación semanal">
                        <div class="weekly-line-axis">
                            <span>100%</span>
                            <span>75%</span>
                            <span>50%</span>
                            <span>25%</span>
                            <span>0%</span>
                        </div>
                        <div class="weekly-line-body">
                            <div class="weekly-line-plot">
                                <svg class="weekly-line-svg" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="weeklyLineFill" x1="0" y1="0" x2="0" y2="1">
                                            <stop class="weekly-line-stop-start" offset="0%"></stop>
                                            <stop class="weekly-line-stop-end" offset="100%"></stop>
                                        </linearGradient>
                                    </defs>
                                    <path class="weekly-line-area" d="<?= dashboard_safe($weekly_area_path, '') ?>" fill="url(#weeklyLineFill)"></path>
                                    <path class="weekly-line-stroke" d="<?= dashboard_safe($weekly_line_path, '') ?>"></path>
                                </svg>
                                <div class="weekly-line-points">
                                    <?php foreach ($weekly_line_rows as $row): ?>
                                        <?php $point_class = 'weekly-line-point' . ($row['es_pico'] ? ' is-peak' : ''); ?>
                                        <div class="<?= $point_class ?>" style="left: <?= round($row['x'], 2) ?>%; bottom: <?= round($row['pct'], 2) ?>%" title="<?= dashboard_safe($row['dia']) ?> <?= dashboard_safe($row['fecha']) ?>: <?= dashboard_percent_text($row['pct']) ?>">
                                            <span class="weekly-line-value"><?= dashboard_percent_text($row['pct']) ?></span>
                                            <span class="weekly-line-dot"></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="weekly-line-labels">
                                <?php foreach ($weekly_line_rows as $row): ?>
                                    <?php $label_class = 'weekly-line-label' . ($row['es_hoy'] ? ' is-today' : '') . ($row['es_pico'] ? ' is-peak' : ''); ?>
                                    <div class="<?= $label_class ?>">
                                        <span class="d"><?= dashboard_safe($row['dia']) ?></span>
                                        <span class="f"><?= dashboard_safe($row['fecha']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="weekly-line-foot">
                                <span class="weekly-line-foot-info">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"></circle><path d="M12 11v5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path><circle cx="12" cy="8" r="1" fill="currentColor"></circle></svg>
                                    Basado en <?= (int)$chart_max ?> habitaciones activas
                                </span>
                                <span class="weekly-line-foot-time">Última actualización: Hoy <?= dashboard_safe($weekly_line_updated_at) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </article>

            <article class="card card-pad">
                <div class="section-head">
                    <h2>Estacionamiento</h2>
                    <span class="parking-head-note"><?= $coches ?> / <?= $limite_coches ?> ocupados</span>
                </div>
                <div class="park-ring">
                    <div class="ring-box">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle cx="60" cy="60" r="50" fill="none" style="stroke:var(--dash-line)" stroke-width="11"></circle>
                            <circle cx="60" cy="60" r="50" fill="none" stroke="url(#parkingGold)" stroke-width="11" stroke-linecap="round" stroke-dasharray="<?= $ring_circumference ?>" stroke-dashoffset="<?= $ring_offset ?>" transform="rotate(-90 60 60)"></circle>
                            <defs>
                                <linearGradient id="parkingGold" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0" style="stop-color:var(--dash-gold-mid)"></stop>
                                    <stop offset="1" style="stop-color:var(--dash-gold)"></stop>
                                </linearGradient>
                            </defs>
                        </svg>
                        <div class="ring-num"><div><b><?= $coches ?></b><span>de <?= $limite_coches ?></span></div></div>
                    </div>
                    <div class="park-bar">
                        <div class="park-bar-head">
                            <span><i class="fas fa-car" style="color:var(--dash-gold)" aria-hidden="true"></i> Vehículos</span>
                            <b><?= $pct_coches ?>%</b>
                        </div>
                        <div class="progress"><i style="--progress:<?= $pct_coches ?>%"></i></div>
                        <div class="soft-note"><?= $espacios_disp ?> espacios disponibles</div>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid3">
            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Próximas llegadas</h2>
                    <span class="status-badge" style="margin-top:0;background:var(--dash-bg-arriving);color:var(--dash-arriving)"><?= count($proximas_llegadas) ?></span>
                </div>
                <?php if (empty($proximas_llegadas)): ?>
                    <div class="empty-state">Sin llegadas programadas para hoy.</div>
                <?php else: ?>
                    <?php foreach (array_slice($proximas_llegadas, 0, 3) as $llegada): ?>
                        <?php
                        $hora_llegada = !empty($llegada['hora_llegada_estimada'])
                            ? date('H:i', strtotime($llegada['hora_llegada_estimada']))
                            : 'Por definir';
                        $nombre_llegada = $llegada['huesped_nombre'] ?? 'Huésped pendiente';
                        ?>
                        <a class="list-row is-action" href="<?= url('reservaciones/ver/' . (int)$llegada['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_llegada) ?>">
                            <div class="list-avatar"><?= dashboard_safe(dashboard_initials($nombre_llegada), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_llegada) ?></div>
                                <div class="list-meta"><?= dashboard_safe($llegada['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-right-copy">
                                    <div class="list-time"><?= dashboard_safe($hora_llegada) ?></div>
                                    <div class="list-meta">hoy</div>
                                </div>
                                <span class="list-action-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </article>

            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Próximas salidas</h2>
                    <span class="status-badge warn" style="margin-top:0"><?= count($proximas_salidas) ?></span>
                </div>
                <?php if (empty($proximas_salidas)): ?>
                    <div class="empty-state">Sin salidas programadas para hoy.</div>
                <?php else: ?>
                    <?php foreach (array_slice($proximas_salidas, 0, 3) as $salida): ?>
                        <?php
                        $nombre_salida = $salida['huesped_nombre'] ?? 'Huésped pendiente';
                        $hora_salida = !empty($salida['hora_salida'])
                            ? date('H:i', strtotime($salida['hora_salida']))
                            : (!empty($salida['hora_entrada']) ? date('H:i', strtotime($salida['hora_entrada'])) : 'Pendiente');
                        ?>
                        <a class="list-row is-action" href="<?= url('reservaciones/ver/' . (int)$salida['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_salida) ?>">
                            <div class="list-avatar gold"><?= dashboard_safe(dashboard_initials($nombre_salida), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_salida) ?></div>
                                <div class="list-meta"><?= dashboard_safe($salida['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-right-copy">
                                    <div class="list-time"><?= dashboard_safe($hora_salida) ?></div>
                                    <div class="list-meta"><?= !empty($salida['hora_salida']) ? 'en regla' : 'pendiente' ?></div>
                                </div>
                                <span class="list-action-icon" aria-hidden="true"><i class="fas fa-arrow-right"></i></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </article>

            <article class="card card-pad">
                <div class="section-head">
                    <h2 style="font-size:21px">Estado de caja</h2>
                    <span class="status-badge <?= $caja_abierta ? '' : 'warn' ?>" style="margin-top:0">
                        <span class="led"></span>
                        <?= $caja_abierta ? 'Corte abierto' : 'Corte pendiente' ?>
                        <?php if ($caja_info && !empty($caja_info['fecha_apertura'])): ?>
                            · <?= date('H:i', strtotime($caja_info['fecha_apertura'])) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="cash-grid">
                    <div class="cash-box">
                        <div class="label">Inicial</div>
                        <div class="amount"><?= format_money($caja_info['monto_inicial'] ?? 0) ?></div>
                    </div>
                    <div class="cash-box">
                        <div class="label">Ingresos</div>
                        <div class="amount" style="color:var(--dash-available)">+<?= format_money($caja_info['total_ingresos'] ?? $ingresos_total) ?></div>
                    </div>
                    <div class="cash-box">
                        <div class="label">Gastos</div>
                        <div class="amount" style="color:var(--dash-critical)">-<?= format_money($caja_info['total_gastos'] ?? $egresos_total) ?></div>
                    </div>
                    <div class="cash-box dark">
                        <div class="label">Esperado</div>
                        <div class="amount"><?= format_money($caja_info['efectivo_esperado'] ?? $balance_dia) ?></div>
                    </div>
                </div>
                <div class="button-row">
                    <a href="<?= url('caja') ?>" class="dash-btn primary" title="Registrar un movimiento en caja"><i class="fas fa-plus" aria-hidden="true"></i>Registrar movimiento</a>
                    <a href="<?= url('caja') ?>" class="dash-btn ghost" title="Ver el corte actual"><i class="fas fa-eye" aria-hidden="true"></i>Ver corte</a>
                </div>
            </article>
        </section>

        <!-- ════════════════════════════════════════════════════════════
             VISTA MÓVIL COMPACTA · Claude Design "mobile-foto"
             Solo visible en teléfonos (≤767px). Usa los mismos datos PHP
             que el layout de escritorio; no añade consultas ni lógica.
             ════════════════════════════════════════════════════════════ -->
        <div class="dash-mobile">
            <header class="dm-hero" style="background-image:url('<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>')">
                <div class="dm-hero-scrim"></div>
                <div class="dm-hero-txt">
                    <div class="dm-eyebrow"><?= dashboard_safe($fecha_hero) ?></div>
                    <h1 class="dm-hotel"><?= dashboard_safe($hotel_display_name, 'Medisoft Hoteles') ?></h1>
                    <p class="dm-greet"><?= dashboard_safe($saludo, 'Hola') ?>, <?= dashboard_safe($usuario_nombre, 'usuario') ?></p>
                </div>
            </header>

            <div class="dm-body">
                <div class="dm-sec"><span>Ocupación</span></div>
                <div class="dm-card">
                    <div class="dm-occ">
                        <div class="dm-ring">
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--dash-line)" stroke-width="9"></circle>
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--dash-navy)" stroke-width="9" stroke-linecap="round"
                                    stroke-dasharray="<?= $m_ring_circ ?>" stroke-dashoffset="<?= $m_ring_offset ?>" transform="rotate(-90 50 50)"></circle>
                            </svg>
                            <b><?= dashboard_percent_text($ocupacion_pct) ?></b>
                        </div>
                        <div class="dm-occ-meta">
                            <div class="big"><?= $habitaciones_ocupadas ?> <span>/ <?= $habitaciones_total ?></span></div>
                            <div class="sub">habitaciones ocupadas</div>
                            <span class="dm-badge"><span class="led"></span> <?= $habitaciones_libres ?> disponibles</span>
                        </div>
                    </div>
                </div>

                <div class="dm-sec"><span>Movimientos del día</span></div>
                <div class="dm-card">
                    <div class="dm-mvtop">
                        <div>
                            <div class="lbl">Balance</div>
                            <div class="bal"><?= format_money($balance_dia) ?></div>
                        </div>
                        <span class="dm-badge"><span class="led"></span> Jornada en curso</span>
                    </div>
                    <div class="dm-split">
                        <div class="b">
                            <div class="t" style="color:var(--dash-available)">Ingresos</div>
                            <div class="v" style="color:var(--dash-available)"><?= format_money($ingresos_total) ?></div>
                        </div>
                        <div class="b">
                            <div class="t" style="color:var(--dash-critical)">Egresos</div>
                            <div class="v" style="color:var(--dash-critical)"><?= format_money($egresos_total) ?></div>
                        </div>
                    </div>
                </div>

                <div class="dm-sec"><span>Habitaciones</span><a href="<?= url('habitaciones') ?>">Ver todas</a></div>
                <div class="dm-card">
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=disponible') ?>" style="--state-accent:var(--dash-available)" title="Ver habitaciones disponibles"><span class="dt" style="background:var(--dash-available)"></span><span class="nm">Disponibles</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_disponibles) ?>%;background:var(--dash-available)"></i></span><span class="ct"><?= $habitaciones_disponibles ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=ocupada') ?>" style="--state-accent:var(--dash-occupied)" title="Ver habitaciones ocupadas"><span class="dt" style="background:var(--dash-occupied)"></span><span class="nm">Ocupadas</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_ocupadas) ?>%;background:var(--dash-occupied)"></i></span><span class="ct"><?= $habitaciones_ocupadas ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=por_llegar') ?>" style="--state-accent:var(--dash-arriving)" title="Ver habitaciones por llegar"><span class="dt" style="background:var(--dash-arriving)"></span><span class="nm">Por llegar</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_por_llegar) ?>%;background:var(--dash-arriving)"></i></span><span class="ct"><?= $habitaciones_por_llegar ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=limpieza') ?>" style="--state-accent:var(--dash-cleaning)" title="Ver habitaciones en limpieza"><span class="dt" style="background:var(--dash-cleaning)"></span><span class="nm">Limpieza</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_limpieza) ?>%;background:var(--dash-cleaning)"></i></span><span class="ct"><?= $habitaciones_limpieza ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                    <a class="dm-rst is-link" href="<?= url('habitaciones?estado=mantenimiento') ?>" style="--state-accent:var(--dash-maint)" title="Ver habitaciones en mantenimiento"><span class="dt" style="background:var(--dash-maint)"></span><span class="nm">Mantenimiento</span><span class="bar"><i style="width:<?= $m_bar_width($habitaciones_mantenimiento) ?>%;background:var(--dash-maint)"></i></span><span class="ct"><?= $habitaciones_mantenimiento ?></span><span class="go"><i class="fas fa-chevron-right" aria-hidden="true"></i></span></a>
                </div>

                <div class="dm-sec"><span>Agenda</span><a href="<?= url('reservaciones') ?>">Ver agenda</a></div>
                <div class="dm-card">
                    <div class="dm-ag-h" style="color:var(--dash-arriving)">Llegadas · <?= count($proximas_llegadas) ?></div>
                    <?php if (empty($proximas_llegadas)): ?>
                        <div class="dm-empty">Sin llegadas programadas para hoy.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($proximas_llegadas, 0, 3) as $i => $llegada): ?>
                            <?php
                            $nombre_llegada = $llegada['huesped_nombre'] ?? 'Huésped pendiente';
                            $hora_llegada = !empty($llegada['hora_llegada_estimada'])
                                ? date('H:i', strtotime($llegada['hora_llegada_estimada']))
                                : 'Por definir';
                            ?>
                            <a class="dm-ag is-link<?= $i === 0 ? ' first' : '' ?>" href="<?= url('reservaciones/ver/' . (int)$llegada['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_llegada) ?>">
                                <div class="av"><?= dashboard_safe(dashboard_initials($nombre_llegada), 'H') ?></div>
                                <div>
                                    <div class="nm"><?= dashboard_safe($nombre_llegada) ?></div>
                                    <div class="mt"><?= dashboard_safe($llegada['habitaciones'] ?? null, 'Sin habitación') ?></div>
                                </div>
                                <span class="tm"><?= dashboard_safe($hora_llegada) ?><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <hr class="dm-ag-div">
                    <div class="dm-ag-h" style="color:var(--dash-maint)">Salidas · <?= count($proximas_salidas) ?></div>
                    <?php if (empty($proximas_salidas)): ?>
                        <div class="dm-empty">Sin salidas programadas para hoy.</div>
                    <?php else: ?>
                        <?php foreach (array_slice($proximas_salidas, 0, 3) as $i => $salida): ?>
                            <?php
                            $nombre_salida = $salida['huesped_nombre'] ?? 'Huésped pendiente';
                            $hora_salida = !empty($salida['hora_salida'])
                                ? date('H:i', strtotime($salida['hora_salida']))
                                : (!empty($salida['hora_entrada']) ? date('H:i', strtotime($salida['hora_entrada'])) : 'Pendiente');
                            ?>
                            <a class="dm-ag is-link<?= $i === 0 ? ' first' : '' ?>" href="<?= url('reservaciones/ver/' . (int)$salida['id']) ?>" title="Ver detalle de la reservación de <?= dashboard_safe($nombre_salida) ?>">
                                <div class="av gold"><?= dashboard_safe(dashboard_initials($nombre_salida), 'H') ?></div>
                                <div>
                                    <div class="nm"><?= dashboard_safe($nombre_salida) ?></div>
                                    <div class="mt"><?= dashboard_safe($salida['habitaciones'] ?? null, 'Sin habitación') ?></div>
                                </div>
                                <span class="tm"><?= dashboard_safe($hora_salida) ?><i class="fas fa-chevron-right" aria-hidden="true"></i></span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dm-sec"><span>Estado de caja</span></div>
                <div class="dm-card">
                    <div class="dm-caja">
                        <div class="dm-cbox">
                            <div class="t">Inicial</div>
                            <div class="v"><?= format_money($caja_info['monto_inicial'] ?? 0) ?></div>
                        </div>
                        <div class="dm-cbox">
                            <div class="t">Ingresos</div>
                            <div class="v" style="color:var(--dash-available)">+<?= format_money($caja_info['total_ingresos'] ?? $ingresos_total) ?></div>
                        </div>
                        <div class="dm-cbox">
                            <div class="t">Gastos</div>
                            <div class="v" style="color:var(--dash-critical)">-<?= format_money($caja_info['total_gastos'] ?? $egresos_total) ?></div>
                        </div>
                        <div class="dm-cbox dark">
                            <div class="t">Esperado</div>
                            <div class="v"><?= format_money($caja_info['efectivo_esperado'] ?? $balance_dia) ?></div>
                        </div>
                    </div>
                    <div class="dm-actions">
                        <a href="<?= url('caja') ?>" class="dm-btn primary" title="Registrar un movimiento en caja"><i class="fas fa-plus" aria-hidden="true"></i>Registrar movimiento</a>
                        <a href="<?= url('caja') ?>" class="dm-btn ghost" title="Ver el corte actual"><i class="fas fa-eye" aria-hidden="true"></i>Ver corte</a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
