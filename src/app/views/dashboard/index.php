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

        if (function_exists('format_date')) {
            return format_date($value, $format);
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($format, $timestamp) : '-';
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
$fecha_hero = strtoupper(str_replace(' DE ', ' ', $fecha_hoy)) . ' · ' . date('H:i');
$caja_abierta = (bool)$corte_actual;
$usuario_nombre = function_exists('user_name') ? user_name() : ($_SESSION['nombre'] ?? 'Usuario');
$usuario_rol = function_exists('user_role') ? user_role() : ($_SESSION['rol'] ?? 'Hotel');
$usuario_iniciales = dashboard_initials($usuario_nombre);

$chart_data = $graficos['ocupacion_semanal'] ?? [];
$chart_max = max(1, $habitaciones_total);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

:root {
    --dash-primary: var(--brand-primary, #2563EB);
    --dash-secondary: var(--brand-secondary, #0F172A);
    --dash-accent: var(--brand-accent, #06B6D4);
    --dash-ivory: color-mix(in srgb, var(--dash-primary) 6%, #F8FAFC);
    --dash-ivory-2: color-mix(in srgb, var(--dash-accent) 5%, #F8FAFC);
    --dash-surface: color-mix(in srgb, var(--dash-primary) 2%, #FFFFFF);
    --dash-surface-warm: color-mix(in srgb, var(--dash-accent) 4%, #FFFFFF);
    --dash-line: color-mix(in srgb, var(--dash-primary) 16%, #E2E8F0);
    --dash-line-soft: color-mix(in srgb, var(--dash-primary) 9%, #EEF2F7);
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
    --dash-occupied: #5B6B86;
    --dash-bg-occupied: #ECEFF4;
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
        linear-gradient(90deg, rgba(15,23,42,.86), rgba(15,23,42,.18)),
        url("<?= htmlspecialchars($hero_image_url, ENT_QUOTES, 'UTF-8') ?>") center/cover;
    box-shadow: var(--dash-shadow-lg);
}

.editorial-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(90deg, rgba(27,39,70,.92) 0%, rgba(27,39,70,.62) 48%, rgba(27,39,70,.24) 100%),
        radial-gradient(circle at 74% 42%, rgba(194,160,90,.28), transparent 21rem);
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

.mini-icon {
    background: var(--dash-bg-occupied);
    color: var(--dash-occupied);
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

.chart-legend {
    display: flex;
    align-items: center;
    gap: 18px;
    color: var(--dash-slate-500);
    font-size: 12px;
    font-weight: 750;
}

.chart-legend span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}

.legend-box {
    width: 11px;
    height: 11px;
    border-radius: 4px;
    background: var(--box);
}

.chart-bars {
    height: 190px;
    display: grid;
    grid-template-columns: repeat(7, minmax(34px, 1fr));
    align-items: end;
    gap: 18px;
    margin: 8px 3px 0;
}

.bar-stack {
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: end;
    gap: 4px;
}

.bar-free,
.bar-occupied {
    min-height: 5px;
    border-radius: 7px 7px 2px 2px;
}

.bar-free {
    background: #ECE7DC;
}

.bar-occupied {
    background: var(--dash-navy);
}

.xaxis {
    display: grid;
    grid-template-columns: repeat(7, minmax(34px, 1fr));
    gap: 18px;
    margin: 10px 3px 0;
    color: var(--dash-slate-400);
    font-size: 12px;
    font-weight: 750;
    text-align: center;
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
}

.list-row {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    min-height: 58px;
    border-bottom: 1px solid var(--dash-line-soft);
}

.list-row:last-child {
    border-bottom: 0;
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
    text-align: right;
}

.list-time {
    color: var(--dash-navy);
    font-size: 13px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
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
}

.cash-box.dark {
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

    .chart-bars {
        height: 215px;
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

    .chart-bars,
    .xaxis {
        gap: 8px;
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
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
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
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-available)"></i>Disponibles</span><strong><?= $habitaciones_disponibles ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-occupied)"></i>Ocupadas</span><strong><?= $habitaciones_ocupadas ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-arriving)"></i>Por llegar</span><strong><?= $habitaciones_por_llegar ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-cleaning)"></i>Limpieza</span><strong><?= $habitaciones_limpieza ?></strong></div>
                    <div class="legend-line"><span><i class="swatch" style="--swatch:var(--dash-maint)"></i>Mantenimiento</span><strong><?= $habitaciones_mantenimiento ?></strong></div>
                </div>
            </article>
        </section>

        <section class="grid2">
            <article class="card card-pad">
                <div class="section-head">
                    <h2>Ocupación semanal</h2>
                    <div class="chart-legend">
                        <span><i class="legend-box" style="--box:var(--dash-navy)"></i>Ocupadas</span>
                        <span><i class="legend-box" style="--box:#ECE7DC"></i>Disponibles</span>
                    </div>
                </div>
                <?php if (empty($chart_data)): ?>
                    <div class="empty-state">Sin datos de ocupación semanal para mostrar.</div>
                <?php else: ?>
                    <div class="chart-bars">
                        <?php foreach (array_slice($chart_data, 0, 7) as $row): ?>
                            <?php
                            $ocupadas = max(0, (int)($row['ocupadas'] ?? 0));
                            $disponibles = max(0, (int)($row['disponibles'] ?? max(0, $chart_max - $ocupadas)));
                            $ocupadas_h = $chart_max > 0 ? min(100, max(3, ($ocupadas / $chart_max) * 100)) : 3;
                            $disponibles_h = $chart_max > 0 ? min(100, max(3, ($disponibles / $chart_max) * 100)) : 3;
                            ?>
                            <div class="bar-stack" title="<?= dashboard_safe($row['dia'] ?? '') ?>: <?= $ocupadas ?> ocupadas">
                                <div class="bar-free" style="height:<?= $disponibles_h ?>%"></div>
                                <div class="bar-occupied" style="height:<?= $ocupadas_h ?>%"></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="xaxis">
                        <?php foreach (array_slice($chart_data, 0, 7) as $row): ?>
                            <span><?= dashboard_safe($row['dia'] ?? '-') ?></span>
                        <?php endforeach; ?>
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
                        <div class="list-row">
                            <div class="list-avatar"><?= dashboard_safe(dashboard_initials($nombre_llegada), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_llegada) ?></div>
                                <div class="list-meta"><?= dashboard_safe($llegada['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-time"><?= dashboard_safe($hora_llegada) ?></div>
                                <div class="list-meta">hoy</div>
                            </div>
                        </div>
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
                        <div class="list-row">
                            <div class="list-avatar gold"><?= dashboard_safe(dashboard_initials($nombre_salida), 'H') ?></div>
                            <div>
                                <div class="list-name"><?= dashboard_safe($nombre_salida) ?></div>
                                <div class="list-meta"><?= dashboard_safe($salida['habitaciones'] ?? null, 'Sin habitación') ?></div>
                            </div>
                            <div class="list-right">
                                <div class="list-time"><?= dashboard_safe($hora_salida) ?></div>
                                <div class="list-meta"><?= !empty($salida['hora_salida']) ? 'en regla' : 'pendiente' ?></div>
                            </div>
                        </div>
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
                    <a href="/caja" class="dash-btn primary">Registrar movimiento</a>
                    <a href="/caja" class="dash-btn ghost">Ver corte</a>
                </div>
            </article>
        </section>
    </main>
</div>
