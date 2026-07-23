<?php
$porEstado = $porEstado ?? [];
$topEstados = $topEstados ?? array_slice($porEstado, 0, 10);
$porCiudad = $porCiudad ?? [];
$evolucionMensual = $evolucionMensual ?? [];
$fecha_inicio = !empty($fecha_inicio) ? $fecha_inicio : date('Y-m-d', strtotime('-1 month'));
$fecha_fin = !empty($fecha_fin) ? $fecha_fin : date('Y-m-d');

if (!function_exists('proc_geo_money')) {
    function proc_geo_money($amount) {
        return format_currency((float)($amount ?? 0));
    }
}

if (!function_exists('proc_geo_safe')) {
    function proc_geo_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('proc_geo_date')) {
    function proc_geo_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('proc_geo_percent')) {
    function proc_geo_percent($value, $total) {
        $total = (float)($total ?? 0);
        if ($total <= 0) {
            return 0;
        }

        return round(((float)$value / $total) * 100, 1);
    }
}

$porNacionalidad = $porNacionalidad ?? [];
$totalExtranjeros = (int)array_sum(array_column($porNacionalidad, 'total_huespedes'));
$ingresosExtranjeros = array_sum(array_column($porNacionalidad, 'ingresos_totales'));
$topNacionalidades = array_slice($porNacionalidad, 0, 8);
$nacLider = $porNacionalidad[0] ?? null;

$totalHuespedes = array_sum(array_column($porEstado, 'total_huespedes'));
$totalReservaciones = array_sum(array_column($porEstado, 'total_reservaciones'));
$totalIngresos = array_sum(array_column($porEstado, 'ingresos_totales'));
$porcentajeExtranjeros = $totalHuespedes > 0 ? round(($totalExtranjeros / $totalHuespedes) * 100, 1) : 0;
$promedioEstancia = $totalReservaciones > 0 ? round($totalHuespedes / $totalReservaciones, 1) : 0;
$ticketPromedio = $totalHuespedes > 0 ? $totalIngresos / $totalHuespedes : 0;
$topEstado = $porEstado[0] ?? null;
$topCiudad = $porCiudad[0] ?? null;
$top5 = array_slice($porEstado, 0, 5);
$topCiudades = array_slice($porCiudad, 0, 6);
$datosEstados = [];
foreach ($porEstado as $estadoDato) {
    if (!empty($estadoDato['estado'])) {
        $datosEstados[$estadoDato['estado']] = $estadoDato;
    }
}
?>

<style>
.geo-report-view {
    --geo-primary: var(--brand-primary, #1B2746);
    --geo-secondary: var(--brand-secondary, #0F172A);
    --geo-accent: var(--brand-accent, #BD9441);
    --geo-action: var(--brand-action-bg, var(--geo-primary));
    --geo-action-hover: var(--brand-action-bg-hover, var(--geo-secondary));
    --geo-on-action: var(--brand-action-text, #FFFFFF);
    --geo-bg: color-mix(in srgb, var(--geo-primary) 4%, #F8FAFC);
    --geo-surface: #FFFFFF;
    --geo-soft: color-mix(in srgb, var(--geo-primary) 5%, #FFFFFF);
    --geo-line: color-mix(in srgb, var(--geo-primary) 13%, #E5E7EB);
    --geo-line-soft: color-mix(in srgb, var(--geo-primary) 8%, #F1F5F9);
    --geo-text: #17233E;
    --geo-muted: #748096;
    --geo-map-empty: #D7DEE8;
    --geo-map-1: #38BDF8;
    --geo-map-2: #22C55E;
    --geo-map-3: #FACC15;
    --geo-map-4: #F97316;
    --geo-map-5: #E11D48;
    --geo-map-stroke: rgba(15, 23, 42, .72);
    --geo-map-stroke-light: rgba(255, 255, 255, .9);
    --geo-map-bg-deep: color-mix(in srgb, var(--geo-secondary) 78%, #030712);
    --geo-map-bg-mid: color-mix(in srgb, var(--geo-primary) 58%, #111827);
    --geo-map-bg-glow: color-mix(in srgb, var(--geo-accent) 36%, transparent);
    --geo-card-shadow: 0 18px 44px -36px rgba(15, 23, 42, .52);
    min-height: 100vh;
    background:
        radial-gradient(circle at 86% 4%, color-mix(in srgb, var(--geo-accent) 9%, transparent), transparent 30rem),
        linear-gradient(135deg, color-mix(in srgb, var(--geo-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, var(--geo-bg), #F8FAFC 56%, #EEF2F7);
    color: var(--geo-text);
    opacity: 0;
    transition: opacity .24s ease;
}

.geo-report-view.loaded {
    opacity: 1;
}

.geo-shell {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px clamp(34px, 4vw, 76px) 50px;
    box-sizing: border-box;
}

.geo-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}

.geo-hero-main,
.geo-period-panel,
.geo-card,
.geo-section,
.geo-map-panel {
    border: 1px solid var(--geo-line);
    background: var(--geo-surface);
    box-shadow: var(--geo-card-shadow);
}

.geo-hero-main {
    position: relative;
    overflow: hidden;
    min-height: 325px;
    padding: clamp(24px, 4vw, 44px);
    border-color: color-mix(in srgb, var(--geo-accent) 28%, transparent);
    border-radius: 28px;
    background:
        radial-gradient(circle at 86% 18%, color-mix(in srgb, var(--geo-accent) 30%, transparent), transparent 22rem),
        linear-gradient(135deg,
            color-mix(in srgb, var(--geo-action) 54%, #101827),
            color-mix(in srgb, var(--geo-action-hover) 58%, #060A12)
        );
}

.geo-hero-main::after {
    content: "";
    position: absolute;
    inset: auto -10% -54% 46%;
    height: 220px;
    background: radial-gradient(circle, color-mix(in srgb, var(--geo-accent) 42%, transparent), transparent 68%);
    pointer-events: none;
}

.geo-kicker,
.geo-label,
.geo-section-kicker,
.geo-table th,
.geo-mini-label {
    color: var(--geo-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.geo-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    width: fit-content;
    padding: 8px 11px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 999px;
    background: rgba(255, 255, 255, .1);
    color: #FFFFFF;
    text-shadow: 0 1px 1px rgba(0, 0, 0, .22);
}

.geo-kicker i {
    color: color-mix(in srgb, var(--geo-accent) 42%, #FFFFFF);
}

.geo-hero-main h1 {
    position: relative;
    z-index: 1;
    max-width: 12ch;
    margin: 14px 0 14px;
    color: var(--geo-on-action);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.4rem, 5vw, 5.15rem);
    line-height: .9;
    font-weight: 700;
    letter-spacing: 0;
}

.geo-hero-main p {
    position: relative;
    z-index: 1;
    max-width: 66ch;
    margin: 0;
    color: color-mix(in srgb, var(--geo-on-action) 82%, transparent);
    font-weight: 650;
    line-height: 1.6;
}

.geo-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.geo-btn,
.geo-period-btn {
    min-height: 42px;
    border-radius: 13px;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.geo-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--geo-accent) 46%, var(--geo-primary));
    background: var(--geo-action);
    color: var(--geo-on-action);
    font-weight: 900;
    text-decoration: none;
}

.geo-btn.is-soft {
    border-color: rgba(255, 255, 255, .22);
    background: rgba(255, 255, 255, .10);
    color: #FFFFFF;
}

.geo-btn.is-accent {
    border-color: color-mix(in srgb, var(--geo-action) 34%, var(--geo-accent));
    background: var(--geo-action);
    color: var(--geo-on-action);
}

.geo-hero-main .geo-btn.is-accent {
    border-color: rgba(255, 255, 255, .9);
    background: #FFFFFF;
    color: color-mix(in srgb, var(--geo-action) 78%, #05070D);
}

.geo-hero-main .geo-btn.is-accent i {
    color: color-mix(in srgb, var(--geo-accent) 48%, var(--geo-action));
}

.geo-btn:hover,
.geo-period-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px -24px rgba(15, 23, 42, .55);
}

.geo-period-panel {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 17px;
    padding: 20px;
    border-radius: 24px;
}

.geo-period-value {
    margin-top: 7px;
    color: var(--geo-primary);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.45rem, 3vw, 2.3rem);
    font-weight: 700;
    line-height: 1;
}

.geo-filter-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.geo-field label {
    display: block;
    margin-bottom: 6px;
    color: var(--geo-primary);
    font-size: .75rem;
    font-weight: 900;
}

.geo-field input {
    width: 100%;
    min-height: 43px;
    border: 1px solid var(--geo-line);
    border-radius: 13px;
    background: color-mix(in srgb, var(--geo-primary) 3%, #FFFFFF);
    color: var(--geo-primary);
    font-weight: 800;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.geo-field input:focus {
    border-color: color-mix(in srgb, var(--geo-accent) 62%, var(--geo-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--geo-accent) 22%, transparent);
}

.geo-filter-form .geo-btn {
    grid-column: 1 / -1;
}

.geo-periods {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.geo-period-btn {
    border: 1px solid var(--geo-line-soft);
    background: color-mix(in srgb, var(--geo-primary) 4%, #FFFFFF);
    color: var(--geo-primary);
    font-size: .76rem;
    font-weight: 900;
}

.geo-metric-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.18fr) repeat(3, minmax(180px, .94fr));
    gap: 14px;
    margin-bottom: 18px;
}

.geo-card {
    min-width: 0;
    padding: 18px;
    border-radius: 20px;
}

.geo-card.is-main {
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--geo-primary) 6%, #FFFFFF), var(--geo-surface));
}

.geo-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.geo-card-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--geo-accent) 13%, #FFFFFF);
    color: color-mix(in srgb, var(--geo-accent) 82%, var(--geo-primary));
}

.geo-value {
    margin-top: 13px;
    color: var(--geo-primary);
    font-size: clamp(1.45rem, 2.8vw, 2.2rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.geo-note {
    margin-top: 9px;
    color: var(--geo-muted);
    font-size: .8rem;
    font-weight: 750;
}

.geo-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.22fr) minmax(310px, .78fr);
    gap: 18px;
    align-items: start;
    margin-bottom: 18px;
}

.geo-map-panel,
.geo-section {
    border-radius: 22px;
    overflow: hidden;
}

.geo-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--geo-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--geo-primary) 4%, #FFFFFF), var(--geo-surface));
}

.geo-section-head h2,
.geo-section-head h3 {
    margin: 0;
    color: var(--geo-primary);
    font-size: 1.05rem;
    font-weight: 950;
}

.geo-section-body {
    padding: 18px 20px 20px;
}

.geo-map-frame {
    position: relative;
    isolation: isolate;
    min-height: 590px;
    padding: 18px;
    background:
        radial-gradient(circle at 21% 14%, var(--geo-map-bg-glow), transparent 23rem),
        radial-gradient(circle at 82% 82%, color-mix(in srgb, var(--geo-primary) 34%, transparent), transparent 21rem),
        linear-gradient(135deg, var(--geo-map-bg-deep), var(--geo-map-bg-mid) 54%, color-mix(in srgb, var(--geo-accent) 22%, #0F172A));
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, .08),
        inset 0 38px 90px rgba(255, 255, 255, .05);
}

#mapaMexicoContainer {
    position: relative;
    z-index: 1;
    width: 100%;
    height: 540px;
    max-height: 62vh;
    border-radius: 18px;
    overflow: hidden;
    background:
        linear-gradient(135deg, rgba(255, 255, 255, .05), transparent 38%),
        color-mix(in srgb, var(--geo-map-bg-deep) 84%, var(--geo-map-bg-mid));
    box-shadow:
        inset 0 0 0 1px rgba(255, 255, 255, .10),
        0 24px 54px -34px rgba(0, 0, 0, .72);
}

#mapaMexicoContainer svg {
    width: 100%;
    height: 100%;
}

#mapaMexicoContainer .estado,
#mapaMexicoContainer path[data-estado] {
    stroke: var(--geo-map-stroke);
    stroke-width: .85;
    stroke-linejoin: round;
    cursor: pointer;
    transition: fill .2s ease, filter .2s ease, stroke .2s ease, stroke-width .2s ease;
    vector-effect: non-scaling-stroke;
}

#mapaMexicoContainer .estado:hover,
#mapaMexicoContainer path[data-estado]:hover {
    stroke: var(--geo-map-stroke-light);
    stroke-width: 1.8;
    filter: saturate(1.18) brightness(1.08) drop-shadow(0 6px 14px rgba(0,0,0,.45));
}

#mapaMexicoContainer .estado-sin-datos {
    fill: var(--geo-map-empty);
    opacity: .72;
    cursor: not-allowed;
}

.geo-map-loader {
    display: grid;
    place-items: center;
    height: 100%;
    color: rgba(255, 255, 255, .68);
    font-size: .9rem;
    font-weight: 800;
}

.geo-map-tooltip {
    position: absolute;
    z-index: 20;
    opacity: 0;
    display: none;
    pointer-events: none;
    max-width: 240px;
    padding: 12px 14px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 15px;
    background: rgba(15, 23, 42, .94);
    color: #FFFFFF;
    box-shadow: 0 20px 44px -26px rgba(0, 0, 0, .7);
    transition: opacity .18s ease;
}

.geo-legend {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--geo-muted);
    font-size: .78rem;
    font-weight: 800;
}

.geo-legend-colors {
    display: flex;
    gap: 3px;
}

.geo-legend-colors span {
    width: 20px;
    height: 12px;
    border-radius: 999px;
    border: 1px solid rgba(15, 23, 42, .18);
    box-shadow: 0 4px 10px rgba(15, 23, 42, .16);
}

.geo-top-stack {
    display: grid;
    gap: 10px;
}

.geo-rank-item,
.geo-city-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 11px;
    align-items: center;
    padding: 12px;
    border: 1px solid var(--geo-line-soft);
    border-radius: 16px;
    background: color-mix(in srgb, var(--geo-primary) 3%, #FFFFFF);
}

.geo-rank {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--geo-accent) 15%, #FFFFFF);
    color: var(--geo-primary);
    font-weight: 950;
}

.geo-rank-item strong,
.geo-city-item strong {
    display: block;
    color: var(--geo-primary);
    font-weight: 950;
}

.geo-rank-item span,
.geo-city-item span {
    display: block;
    color: var(--geo-muted);
    font-size: .78rem;
    font-weight: 750;
}

.geo-rank-value {
    color: var(--geo-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.geo-charts-grid {
    display: grid;
    grid-template-columns: minmax(0, .86fr) minmax(0, 1.14fr);
    gap: 18px;
    margin-bottom: 18px;
}

.geo-chart-box {
    height: 320px;
    position: relative;
}

.geo-state-rank {
    display: grid;
    gap: 14px;
}

.geo-state-podium {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
}

.geo-state-podium-card,
.geo-state-row {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--rank-color, var(--geo-accent)) 24%, var(--geo-line-soft));
    background:
        radial-gradient(circle at 84% 8%, color-mix(in srgb, var(--rank-color, var(--geo-accent)) 16%, transparent), transparent 7rem),
        color-mix(in srgb, var(--rank-color, var(--geo-accent)) 4%, var(--geo-surface));
    box-shadow: 0 12px 28px -26px rgba(15, 23, 42, .54);
}

.geo-state-podium-card {
    min-height: 218px;
    padding: 14px;
    border-radius: 18px;
}

.geo-state-podium-card::after {
    content: "";
    position: absolute;
    right: 14px;
    bottom: 0;
    left: 14px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 72%, transparent);
}

.geo-state-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}

.geo-state-rank-badge,
.geo-state-share,
.geo-state-row-rank {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.geo-state-rank-badge {
    min-width: 36px;
    height: 32px;
    padding: 0 10px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 72%, var(--geo-primary));
    background: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 14%, #FFFFFF);
}

.geo-state-share {
    min-width: 48px;
    height: 30px;
    border-radius: 999px;
    color: var(--geo-primary);
    background: rgba(255, 255, 255, .78);
    font-size: .75rem;
}

.geo-state-orbit {
    position: relative;
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    margin: 14px 0 12px;
    border-radius: 50%;
    background:
        conic-gradient(var(--rank-color, var(--geo-accent)) var(--rank-angle, 0deg), color-mix(in srgb, var(--geo-primary) 8%, #FFFFFF) 0);
}

.geo-state-orbit::after {
    content: "";
    position: absolute;
    inset: 8px;
    border-radius: inherit;
    background: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 4%, #FFFFFF);
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .05);
}

.geo-state-orbit span,
.geo-state-orbit strong {
    position: relative;
    z-index: 1;
    display: block;
    text-align: center;
}

.geo-state-orbit strong {
    color: var(--geo-primary);
    font-size: 1.12rem;
    line-height: 1;
    font-weight: 950;
}

.geo-state-orbit span {
    margin-top: 3px;
    color: var(--geo-muted);
    font-size: .62rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.geo-state-podium-card h4 {
    margin: 0 0 10px;
    color: var(--geo-primary);
    font-size: .98rem;
    font-weight: 950;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.geo-state-stats {
    display: grid;
    gap: 7px;
}

.geo-state-stats span {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 8px;
    color: var(--geo-muted);
    font-size: .68rem;
    font-weight: 850;
    text-transform: uppercase;
}

.geo-state-stats strong {
    color: var(--geo-primary);
    font-size: .78rem;
    font-weight: 950;
    text-transform: none;
    font-variant-numeric: tabular-nums;
}

.geo-state-list {
    display: grid;
    gap: 8px;
}

.geo-state-row {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr) auto;
    gap: 11px;
    align-items: center;
    min-height: 72px;
    padding: 11px 12px;
    border-radius: 16px;
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.geo-state-row:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 38%, var(--geo-line));
    box-shadow: 0 18px 34px -30px rgba(15, 23, 42, .62);
}

.geo-state-row-rank {
    width: 40px;
    height: 40px;
    border-radius: 14px;
    color: var(--geo-primary);
    background: color-mix(in srgb, var(--rank-color, var(--geo-accent)) 13%, #FFFFFF);
}

.geo-state-row-main {
    min-width: 0;
}

.geo-state-row-main strong {
    display: block;
    color: var(--geo-primary);
    font-weight: 950;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.geo-state-row-main span {
    display: block;
    margin-top: 3px;
    color: var(--geo-muted);
    font-size: .72rem;
    font-weight: 850;
}

.geo-state-row-metrics {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
}

.geo-state-row-metrics span {
    min-width: 74px;
    padding: 6px 8px;
    border-radius: 12px;
    background: rgba(255, 255, 255, .74);
    color: var(--geo-muted);
    font-size: .62rem;
    font-weight: 850;
    line-height: 1.05;
    text-align: right;
    text-transform: uppercase;
}

.geo-state-row-metrics strong {
    display: block;
    margin-top: 3px;
    color: var(--geo-primary);
    font-weight: 950;
    text-transform: none;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 1120px) {
    .geo-state-podium {
        grid-template-columns: 1fr;
    }

    .geo-state-podium-card {
        min-height: 0;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 82px;
        column-gap: 12px;
        align-items: center;
    }

    .geo-state-card-head,
    .geo-state-podium-card h4,
    .geo-state-stats {
        grid-column: 1;
    }

    .geo-state-orbit {
        grid-column: 2;
        grid-row: 1 / span 3;
        margin: 0;
    }
}

@media (max-width: 740px) {
    .geo-state-podium-card,
    .geo-state-row {
        border-radius: 14px;
    }

    .geo-state-podium-card {
        grid-template-columns: 1fr;
    }

    .geo-state-orbit {
        grid-column: 1;
        grid-row: auto;
        margin: 12px 0;
    }

    .geo-state-row {
        grid-template-columns: 38px minmax(0, 1fr);
    }

    .geo-state-row-metrics {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        padding-left: 49px;
    }

    .geo-state-row-metrics span {
        min-width: 0;
        text-align: left;
    }
}

.geo-table {
    width: 100%;
    min-width: 720px;
    border-collapse: separate;
    border-spacing: 0;
}

.geo-table th {
    padding: 12px 11px;
    border-bottom: 1px solid var(--geo-line);
    text-align: left;
}

.geo-table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--geo-line-soft);
    color: var(--geo-text);
    font-size: .86rem;
}

.geo-table tbody tr {
    transition: background .18s ease;
}

.geo-table tbody tr:hover {
    background: color-mix(in srgb, var(--geo-primary) 4%, #FFFFFF);
}

.geo-table-wrap {
    overflow-x: auto;
    max-height: 460px;
}

.geo-progress {
    height: 7px;
    margin-top: 6px;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--geo-primary) 8%, #FFFFFF);
}

.geo-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--geo-map-2), var(--geo-map-5));
    transition: width .7s ease;
}

.geo-empty {
    display: grid;
    place-items: center;
    min-height: 220px;
    padding: 30px;
    text-align: center;
    color: var(--geo-muted);
}

.geo-empty i {
    width: 64px;
    height: 64px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 22px;
    background: color-mix(in srgb, var(--geo-accent) 12%, #FFFFFF);
    color: color-mix(in srgb, var(--geo-accent) 80%, var(--geo-primary));
    font-size: 1.55rem;
}

@media (max-width: 1180px) {
    .geo-hero,
    .geo-layout,
    .geo-charts-grid {
        grid-template-columns: 1fr;
    }

    .geo-metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .geo-card.is-main {
        grid-column: 1 / -1;
    }
}

@media (max-width: 720px) {
    .geo-shell {
        width: 100%;
        padding: 18px 12px 36px;
    }

    .geo-hero-main,
    .geo-period-panel,
    .geo-card,
    .geo-section,
    .geo-map-panel {
        border-radius: 18px;
    }

    .geo-hero-main {
        min-height: auto;
        padding: 22px;
    }

    .geo-hero-main h1 {
        max-width: 9ch;
        font-size: clamp(2.05rem, 14vw, 3.5rem);
    }

    .geo-filter-form,
    .geo-periods,
    .geo-metric-grid {
        grid-template-columns: 1fr;
    }

    .geo-map-frame {
        min-height: 420px;
        padding: 12px;
    }

    #mapaMexicoContainer {
        height: 380px;
        max-height: none;
    }

    .geo-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .geo-chart-box {
        height: 270px;
    }
}

.geo-intl-grid {
    display: grid;
    grid-template-columns: minmax(220px, .82fr) minmax(0, 1.18fr);
    gap: 18px;
    align-items: start;
}

.geo-intl-kpis {
    display: grid;
    gap: 12px;
}

.geo-intl-kpi {
    padding: 15px;
    border: 1px solid var(--geo-line-soft);
    border-radius: 16px;
    background:
        radial-gradient(circle at 88% 12%, color-mix(in srgb, var(--geo-accent) 8%, transparent), transparent 8rem),
        color-mix(in srgb, var(--geo-primary) 3%, #FFFFFF);
}

.geo-intl-kpi strong {
    display: block;
    margin-top: 7px;
    color: var(--geo-primary);
    font-size: 1.55rem;
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.geo-intl-kpi small {
    display: block;
    margin-top: 6px;
    color: var(--geo-muted);
    font-size: .76rem;
    font-weight: 750;
}

.geo-intl-flag {
    color: color-mix(in srgb, var(--geo-accent) 80%, var(--geo-primary));
}

@media (max-width: 900px) {
    .geo-intl-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="geo-report-view procedencia-view">
    <main class="geo-shell">
        <section class="geo-hero">
            <div class="geo-hero-main">
                <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reportes') ?>" class="geo-btn is-soft ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Volver a reportes
                </a>
                <div class="mt-6">
                    <span class="geo-kicker">
                        <i class="fas fa-map-marked-alt"></i>
                        Radar de procedencia
                    </span>
                    <h1>Procedencia geográfica</h1>
                    <p>
                        Explora de dónde llegan los huéspedes, qué estados generan más reservaciones y cómo se mueve la demanda por región.
                    </p>
                    <div class="geo-actions">
                        <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                        <button type="button" onclick="exportarPDF()" class="geo-btn is-accent">
                            <i class="fas fa-file-pdf"></i>
                            Exportar PDF
                        </button>
                        <?php endif; ?>
                        <?php if ($topEstado): ?>
                            <span class="geo-btn is-soft">
                                <i class="fas fa-trophy"></i>
                                Top: <?= proc_geo_safe($topEstado['estado'] ?? '') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <aside class="geo-period-panel">
                <div>
                    <span class="geo-label">Periodo consultado</span>
                    <div class="geo-period-value">
                        <?= proc_geo_date($fecha_inicio, 'd/m') ?> - <?= proc_geo_date($fecha_fin) ?>
                    </div>
                    <p class="geo-note"><?= number_format(count($porEstado)) ?> estados con lectura en este rango.</p>
                </div>

                <form method="get" action="<?= url('reportes/procedencia') ?>" class="geo-filter-form" id="procedenciaFiltrosForm" data-auto-filter-form>
                    <div class="geo-field">
                        <label for="fecha_inicio">Desde</label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               value="<?= proc_geo_safe($fecha_inicio, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="geo-field">
                        <label for="fecha_fin">Hasta</label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               value="<?= proc_geo_safe($fecha_fin, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="geo-btn">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar mapa
                    </button>
                </form>

                <div>
                    <span class="geo-label">Periodos rápidos</span>
                    <div class="geo-periods mt-2">
                        <button type="button" onclick="setPeriodo(30)" class="geo-period-btn">1M</button>
                        <button type="button" onclick="setPeriodo(90)" class="geo-period-btn">3M</button>
                        <button type="button" onclick="setPeriodo(180)" class="geo-period-btn">6M</button>
                        <button type="button" onclick="setPeriodo(365)" class="geo-period-btn">1A</button>
                    </div>
                </div>
            </aside>
        </section>

        <section class="geo-metric-grid">
            <article class="geo-card is-main">
                <div class="geo-card-head">
                    <span class="geo-label">Huéspedes ubicados</span>
                    <span class="geo-card-icon"><i class="fas fa-users"></i></span>
                </div>
                <div class="geo-value"><?= number_format($totalHuespedes) ?></div>
                <p class="geo-note">
                    Distribuidos en <?= number_format(count($porEstado)) ?> estados. Promedio <?= $promedioEstancia ?> huésp/res.
                </p>
            </article>

            <article class="geo-card">
                <div class="geo-card-head">
                    <span class="geo-label">Reservaciones</span>
                    <span class="geo-card-icon"><i class="fas fa-calendar-check"></i></span>
                </div>
                <div class="geo-value"><?= number_format($totalReservaciones) ?></div>
                <p class="geo-note">Reservaciones ligadas a procedencia.</p>
            </article>

            <article class="geo-card">
                <div class="geo-card-head">
                    <span class="geo-label">Ingresos</span>
                    <span class="geo-card-icon"><i class="fas fa-dollar-sign"></i></span>
                </div>
                <div class="geo-value"><?= proc_geo_money($totalIngresos) ?></div>
                <p class="geo-note"><?= proc_geo_money($ticketPromedio) ?> por huésped.</p>
            </article>

            <article class="geo-card">
                <div class="geo-card-head">
                    <span class="geo-label">Estado líder</span>
                    <span class="geo-card-icon"><i class="fas fa-map-marker-alt"></i></span>
                </div>
                <div class="geo-value" style="font-size: clamp(1.25rem, 2vw, 1.85rem);">
                    <?= $topEstado ? proc_geo_safe($topEstado['estado']) : 'Sin datos' ?>
                </div>
                <p class="geo-note">
                    <?= $topEstado ? number_format($topEstado['total_huespedes']) . ' huéspedes' : 'No hay registros en el periodo.' ?>
                </p>
            </article>
        </section>

        <section class="geo-layout">
            <article class="geo-map-panel">
                <div class="geo-section-head">
                    <div>
                        <span class="geo-section-kicker">Mapa nacional</span>
                        <h2>Distribución geográfica de huéspedes</h2>
                    </div>
                    <div class="geo-legend">
                        <span>Baja</span>
                        <div class="geo-legend-colors">
                            <span style="background:var(--geo-map-empty)" title="Sin datos"></span>
                            <span style="background:var(--geo-map-1)" title="Baja"></span>
                            <span style="background:var(--geo-map-2)" title="Media baja"></span>
                            <span style="background:var(--geo-map-3)" title="Media"></span>
                            <span style="background:var(--geo-map-4)" title="Alta"></span>
                            <span style="background:var(--geo-map-5)" title="Muy alta"></span>
                        </div>
                        <span>Alta</span>
                    </div>
                </div>
                <div class="geo-map-frame">
                    <div id="mapaMexicoContainer">
                        <div class="geo-map-loader">
                            <span><i class="fas fa-spinner fa-spin mr-2"></i>Cargando mapa...</span>
                        </div>
                    </div>
                    <div id="mapTooltip" class="geo-map-tooltip">
                        <div class="font-bold text-sm mb-1" id="tooltipEstado"></div>
                        <div id="tooltipContent" class="text-xs"></div>
                    </div>
                </div>
            </article>

            <aside class="geo-section">
                <div class="geo-section-head">
                    <div>
                        <span class="geo-section-kicker">Concentración</span>
                        <h3>Top estados</h3>
                    </div>

                </div>
                <div class="geo-section-body">
                    <?php if (!empty($top5)): ?>
                        <div class="geo-top-stack">
                            <?php foreach ($top5 as $index => $estado): ?>
                                <?php $porcentaje = proc_geo_percent($estado['total_huespedes'] ?? 0, $totalHuespedes); ?>
                                <article class="geo-rank-item">
                                    <div class="geo-rank"><?= $index + 1 ?></div>
                                    <div class="min-w-0">
                                        <strong><?= proc_geo_safe($estado['estado'] ?? '') ?></strong>
                                        <span><?= number_format($estado['total_huespedes'] ?? 0) ?> huéspedes · <?= number_format($estado['total_reservaciones'] ?? 0) ?> reservaciones</span>
                                    </div>
                                    <div class="geo-rank-value"><?= $porcentaje ?>%</div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="geo-empty">
                            <div>
                                <i class="fas fa-map-marked-alt"></i>
                                <p>No hay estados con datos para este periodo.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </section>

        <section class="geo-charts-grid">
            <article class="geo-section">
                <div class="geo-section-head">
                    <div>
                        <span class="geo-section-kicker">Ranking visual</span>
                        <h3>Top 10 estados</h3>
                    </div>
                    <span class="geo-label">Por huéspedes</span>
                </div>
                <div class="geo-section-body">
                    <?php $topEstadosVisual = array_slice($topEstados, 0, 10); ?>
                    <?php if (!empty($topEstadosVisual)): ?>
                        <?php
                            $rankColors = ['var(--geo-map-5)', 'var(--geo-map-4)', 'var(--geo-map-3)', 'var(--geo-map-2)', 'var(--geo-map-1)'];
                            $podiumEstados = array_slice($topEstadosVisual, 0, 3);
                            $listaEstados = array_slice($topEstadosVisual, 3);
                        ?>
                        <div class="geo-state-rank">
                            <div class="geo-state-podium">
                                <?php foreach ($podiumEstados as $index => $estado): ?>
                                    <?php
                                        $huespedesEstado = (int)($estado['total_huespedes'] ?? 0);
                                        $porcentaje = proc_geo_percent($huespedesEstado, $totalHuespedes);
                                        $rankAngle = $huespedesEstado > 0 ? max(10, min(360, round($porcentaje * 3.6))) : 0;
                                        $rankColor = $rankColors[$index % count($rankColors)];
                                    ?>
                                    <article class="geo-state-podium-card" style="--rank-angle: <?= $rankAngle ?>deg; --rank-color: <?= $rankColor ?>;">
                                        <div class="geo-state-card-head">
                                            <span class="geo-state-rank-badge">#<?= $index + 1 ?></span>
                                            <span class="geo-state-share"><?= $porcentaje ?>%</span>
                                        </div>
                                        <div class="geo-state-orbit" aria-label="<?= $porcentaje ?>% del total de huéspedes">
                                            <div>
                                                <strong><?= number_format($huespedesEstado) ?></strong>
                                                <span>Huésp.</span>
                                            </div>
                                        </div>
                                        <h4><?= proc_geo_safe($estado['estado'] ?? '') ?></h4>
                                        <div class="geo-state-stats">
                                            <span>Reservas <strong><?= number_format($estado['total_reservaciones'] ?? 0) ?></strong></span>
                                            <span>Ingresos <strong><?= proc_geo_money($estado['ingresos_totales'] ?? 0) ?></strong></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!empty($listaEstados)): ?>
                                <div class="geo-state-list">
                                    <?php foreach ($listaEstados as $offset => $estado): ?>
                                        <?php
                                            $index = $offset + 3;
                                            $huespedesEstado = (int)($estado['total_huespedes'] ?? 0);
                                            $porcentaje = proc_geo_percent($huespedesEstado, $totalHuespedes);
                                            $rankColor = $rankColors[$index % count($rankColors)];
                                        ?>
                                        <article class="geo-state-row" style="--rank-color: <?= $rankColor ?>;">
                                            <span class="geo-state-row-rank">#<?= $index + 1 ?></span>
                                            <div class="geo-state-row-main">
                                                <strong><?= proc_geo_safe($estado['estado'] ?? '') ?></strong>
                                                <span><?= $porcentaje ?>% del total de huéspedes</span>
                                            </div>
                                            <div class="geo-state-row-metrics">
                                                <span>Huésp. <strong><?= number_format($huespedesEstado) ?></strong></span>
                                                <span>Reservas <strong><?= number_format($estado['total_reservaciones'] ?? 0) ?></strong></span>
                                                <span>Ingresos <strong><?= proc_geo_money($estado['ingresos_totales'] ?? 0) ?></strong></span>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="geo-empty">
                            <div>
                                <i class="fas fa-map-marked-alt"></i>
                                <p>No hay estados con datos para este periodo.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="geo-section">
                <div class="geo-section-head">
                    <div>
                        <span class="geo-section-kicker">Tendencia</span>
                        <h3>Evolución mensual del top 5</h3>
                    </div>
                    <span class="geo-label">Demanda por mes</span>
                </div>
                <div class="geo-section-body">
                    <div class="geo-chart-box">
                        <canvas id="graficaEvolucion"></canvas>
                    </div>
                </div>
            </article>
        </section>

        <section class="geo-section" style="margin-bottom: 18px;">
            <div class="geo-section-head">
                <div>
                    <span class="geo-section-kicker">Radar internacional</span>
                    <h2>Procedencia internacional</h2>
                </div>
                <div class="geo-legend">
                    <i class="fas fa-earth-americas geo-intl-flag"></i>
                    <span><?= number_format($totalExtranjeros) ?> extranjeros · <?= $porcentajeExtranjeros ?>% del total</span>
                </div>
            </div>
            <div class="geo-section-body">
                <?php if (!empty($porNacionalidad)): ?>
                    <div class="geo-intl-grid">
                        <div class="geo-intl-kpis">
                            <div class="geo-intl-kpi">
                                <span class="geo-label">Huéspedes extranjeros</span>
                                <strong><?= number_format($totalExtranjeros) ?></strong>
                                <small><?= $porcentajeExtranjeros ?>% de los huéspedes ubicados</small>
                            </div>
                            <div class="geo-intl-kpi">
                                <span class="geo-label">Nacionalidades</span>
                                <strong><?= number_format(count($porNacionalidad)) ?></strong>
                                <small>distintas en el periodo<?= $nacLider ? ' · lidera ' . proc_geo_safe($nacLider['nacionalidad'] ?? '') : '' ?></small>
                            </div>
                            <div class="geo-intl-kpi">
                                <span class="geo-label">Ingresos de extranjeros</span>
                                <strong><?= proc_geo_money($ingresosExtranjeros) ?></strong>
                                <small><?= $totalExtranjeros > 0 ? proc_geo_money($ingresosExtranjeros / max($totalExtranjeros, 1)) . ' por huésped' : 'Sin datos' ?></small>
                            </div>
                        </div>

                        <div class="geo-top-stack">
                            <?php foreach ($topNacionalidades as $index => $nac): ?>
                                <?php $porcentajeNac = proc_geo_percent($nac['total_huespedes'] ?? 0, $totalExtranjeros); ?>
                                <article class="geo-rank-item">
                                    <div class="geo-rank"><?= $index + 1 ?></div>
                                    <div class="min-w-0">
                                        <strong><?= proc_geo_safe($nac['nacionalidad'] ?? '') ?></strong>
                                        <span><?= number_format($nac['total_huespedes'] ?? 0) ?> huéspedes · <?= number_format($nac['total_reservaciones'] ?? 0) ?> reservaciones · <?= proc_geo_money($nac['ingresos_totales'] ?? 0) ?></span>
                                    </div>
                                    <div class="geo-rank-value"><?= $porcentajeNac ?>%</div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="geo-empty">
                        <div>
                            <i class="fas fa-earth-americas"></i>
                            <p>Sin huéspedes extranjeros registrados en este periodo.</p>
                            <p style="font-size: .8rem; margin-top: 6px;">
                                Activa el campo <strong>Nacionalidad</strong> en Configuración → Registro de huéspedes para capturar la procedencia internacional.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

    </main>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script>
const evolucion = <?= json_encode($evolucionMensual) ?>;
const estadosTop5 = <?= json_encode(array_slice(array_column($topEstados, 'estado'), 0, 5)) ?>;
const datosEstados = <?= json_encode($datosEstados) ?>;
const totalHuespedesGlobal = <?= (int)$totalHuespedes ?>;
const isMobile = window.innerWidth < 768;

function getReportStyles() {
    const root = document.querySelector('.geo-report-view');
    return root ? getComputedStyle(root) : getComputedStyle(document.documentElement);
}

function resolveGeoColor(value, fallback) {
    const root = document.querySelector('.geo-report-view') || document.body;
    if (!root || !value) {
        return fallback;
    }

    const probe = document.createElement('span');
    probe.style.position = 'absolute';
    probe.style.visibility = 'hidden';
    probe.style.pointerEvents = 'none';
    probe.style.color = fallback;
    probe.style.color = value;
    root.appendChild(probe);
    const resolved = getComputedStyle(probe).color;
    probe.remove();

    return resolved || fallback;
}

function getGeoColor(name, fallback) {
    const value = getReportStyles().getPropertyValue(name).trim();
    return resolveGeoColor(value || fallback, fallback);
}

function getGeoAlpha(color, alpha) {
    if (color.startsWith('#')) {
        const hex = color.replace('#', '');
        const full = hex.length === 3
            ? hex.split('').map(char => char + char).join('')
            : hex;
        const value = parseInt(full, 16);
        const r = (value >> 16) & 255;
        const g = (value >> 8) & 255;
        const b = value & 255;
        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
    }

    if (color.startsWith('rgb')) {
        const parts = color.match(/\d+(\.\d+)?/g) || [];
        if (parts.length >= 3) {
            return `rgba(${parts[0]}, ${parts[1]}, ${parts[2]}, ${alpha})`;
        }
    }

    return color;
}

function getChartPalette() {
    const accent = getGeoColor('--geo-accent', '#BD9441');
    const primary = getGeoColor('--geo-primary', '#1B2746');
    const map2 = getGeoColor('--geo-map-2', '#22C55E');
    const map3 = getGeoColor('--geo-map-3', '#FACC15');
    const map4 = getGeoColor('--geo-map-4', '#F97316');

    return [
        { border: accent, bg: getGeoAlpha(accent, 0.14) },
        { border: primary, bg: getGeoAlpha(primary, 0.10) },
        { border: map4, bg: getGeoAlpha(map4, 0.11) },
        { border: map3, bg: getGeoAlpha(map3, 0.12) },
        { border: map2, bg: getGeoAlpha(map2, 0.14) }
    ];
}

function buildCharts() {
    const textColor = getGeoColor('--geo-text', '#17233E');
    const lineColor = getGeoColor('--geo-line-soft', '#F1F5F9');
    const colorPalette = getChartPalette();

    const evolucionCanvas = document.getElementById('graficaEvolucion');
    if (evolucionCanvas && evolucion.length > 0) {
        const meses = [...new Set(evolucion.map(e => e.mes))];
        const datasets = estadosTop5.map((estado, index) => ({
            label: estado,
            data: meses.map(mes => {
                const dato = evolucion.find(e => e.mes === mes && e.estado === estado);
                return dato ? dato.total_huespedes : 0;
            }),
            borderColor: colorPalette[index].border,
            backgroundColor: colorPalette[index].bg,
            tension: 0.36,
            borderWidth: 2,
            pointRadius: isMobile ? 2 : 3,
            pointHoverRadius: isMobile ? 4 : 5,
            fill: false
        }));

        new Chart(evolucionCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: meses.map(mes => {
                    const [year, month] = mes.split('-');
                    const date = new Date(year, month - 1);
                    return isMobile
                        ? date.toLocaleDateString('es-MX', { month: 'short' })
                        : date.toLocaleDateString('es-MX', { month: 'short', year: 'numeric' });
                }),
                datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { weight: '700', size: isMobile ? 9 : 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(23, 35, 62, .94)',
                        padding: 12,
                        callbacks: {
                            label: context => `${context.dataset.label}: ${Number(context.parsed.y || 0).toLocaleString('es-MX')} huéspedes`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor, font: { weight: '700', size: isMobile ? 9 : 11 } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: value => Number(value || 0).toLocaleString('es-MX')
                        },
                        grid: { color: lineColor }
                    }
                }
            }
        });
    }
}

window.addEventListener('resize', function() {
    const newIsMobile = window.innerWidth < 768;
    if (newIsMobile !== isMobile) {
        location.reload();
    }
});

function exportarPDF() {
    const url = '<?= url('reportes/exportar-pdf') ?>?tipo=procedencia' +
                '&fecha_inicio=<?= $fecha_inicio ?>' +
                '&fecha_fin=<?= $fecha_fin ?>';
    if (window.MedisoftMobileFiles) {
        window.MedisoftMobileFiles.open(url, { label: 'PDF del reporte' });
        return;
    }
    window.open(url, '_blank');
}

function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);

    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
    const form = document.getElementById('procedenciaFiltrosForm') || document.querySelector('form');
    if (form) {
        form.submit();
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount || 0);
}

function getColorByPercentage2(porcentaje) {
    if (porcentaje > 20) return getGeoColor('--geo-map-5', '#E11D48');
    if (porcentaje > 15) return getGeoColor('--geo-map-4', '#F97316');
    if (porcentaje > 10) return getGeoColor('--geo-map-3', '#FACC15');
    if (porcentaje > 5) return getGeoColor('--geo-map-2', '#22C55E');
    if (porcentaje > 0) return getGeoColor('--geo-map-1', '#38BDF8');
    return getGeoColor('--geo-map-empty', '#D7DEE8');
}

function initMapaSVG() {
    const svgUrl = '<?= url("img/mexico-states.svg") ?>';

    fetch(svgUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(svgContent => {
            const container = document.getElementById('mapaMexicoContainer');
            if (!container) return;

            container.innerHTML = svgContent;
            const svgElement = container.querySelector('svg');

            if (!svgElement) {
                throw new Error('No se encontró elemento SVG en el contenido cargado');
            }

            svgElement.setAttribute('id', 'mapaMexico');
            svgElement.setAttribute('class', 'w-full h-full');
            svgElement.setAttribute('preserveAspectRatio', 'xMidYMid meet');

            if (!svgElement.hasAttribute('viewBox')) {
                const bbox = svgElement.getBBox();
                svgElement.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
            }

            svgElement.querySelectorAll('path').forEach(path => {
                if (!path.hasAttribute('data-estado') && path.id) {
                    const estado = obtenerNombreEstado(path.id);
                    if (estado) {
                        path.setAttribute('data-estado', estado);
                    }
                }

                if (path.hasAttribute('data-estado')) {
                    path.classList.add('estado');
                }
            });

            setTimeout(initMapaFunctionality, 100);
        })
        .catch(error => {
            const container = document.getElementById('mapaMexicoContainer');
            if (!container) return;

            container.innerHTML = `
                <div class="geo-map-loader">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-red-300 text-2xl mb-2"></i>
                        <p>Error al cargar el mapa</p>
                        <p class="text-xs opacity-70 mt-1">${error.message}</p>
                    </div>
                </div>
            `;
        });
}

function obtenerNombreEstado(id) {
    const mapaIds = {
        'MX-AGU': 'Aguascalientes',
        'MX-BCN': 'Baja California',
        'MX-BCS': 'Baja California Sur',
        'MX-CAM': 'Campeche',
        'MX-CHP': 'Chiapas',
        'MX-CHH': 'Chihuahua',
        'MX-CMX': 'Ciudad de México',
        'MX-COA': 'Coahuila',
        'MX-COL': 'Colima',
        'MX-DUR': 'Durango',
        'MX-MEX': 'México',
        'MX-GUA': 'Guanajuato',
        'MX-GRO': 'Guerrero',
        'MX-HID': 'Hidalgo',
        'MX-JAL': 'Jalisco',
        'MX-MIC': 'Michoacán',
        'MX-MOR': 'Morelos',
        'MX-NAY': 'Nayarit',
        'MX-NLE': 'Nuevo León',
        'MX-OAX': 'Oaxaca',
        'MX-PUE': 'Puebla',
        'MX-QUE': 'Querétaro',
        'MX-ROO': 'Quintana Roo',
        'MX-SLP': 'San Luis Potosí',
        'MX-SIN': 'Sinaloa',
        'MX-SON': 'Sonora',
        'MX-TAB': 'Tabasco',
        'MX-TAM': 'Tamaulipas',
        'MX-TLA': 'Tlaxcala',
        'MX-VER': 'Veracruz',
        'MX-YUC': 'Yucatán',
        'MX-ZAC': 'Zacatecas'
    };

    return mapaIds[id] || null;
}

function initMapaFunctionality() {
    const estados = document.querySelectorAll('.estado, path[data-estado]');
    const tooltip = document.getElementById('mapTooltip');
    const tooltipEstado = document.getElementById('tooltipEstado');
    const tooltipContent = document.getElementById('tooltipContent');
    const container = document.getElementById('mapaMexicoContainer');
    const total = Math.max(Number(totalHuespedesGlobal || 0), 1);

    const mapeoNombres = {
        'Estado de México': 'México',
        'CDMX': 'Ciudad de México',
        'Mexico City': 'Ciudad de México',
        'Mexico State': 'México'
    };

    estados.forEach(estado => {
        let nombreEstado = estado.getAttribute('data-estado');
        nombreEstado = mapeoNombres[nombreEstado] || nombreEstado;

        const datosDelEstado = datosEstados[nombreEstado];

        if (datosDelEstado && Number(datosDelEstado.total_huespedes || 0) > 0) {
            const porcentaje = (Number(datosDelEstado.total_huespedes || 0) / total) * 100;
            estado.style.fill = getColorByPercentage2(porcentaje);

            if ('ontouchstart' in window) {
                estado.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    mostrarTooltipMobile(nombreEstado, datosDelEstado, porcentaje);
                });
            } else {
                estado.addEventListener('mouseenter', function() {
                    tooltipEstado.textContent = nombreEstado;
                    tooltipContent.innerHTML = `
                        <div>Huéspedes: ${Number(datosDelEstado.total_huespedes || 0).toLocaleString('es-MX')}</div>
                        <div>Reservaciones: ${Number(datosDelEstado.total_reservaciones || 0).toLocaleString('es-MX')}</div>
                        <div>Ingresos: ${formatCurrency(datosDelEstado.ingresos_totales || 0)}</div>
                        <div class="font-bold text-yellow-300 mt-1">${porcentaje.toFixed(1)}% del total</div>
                    `;

                    tooltip.style.opacity = '1';
                    tooltip.style.display = 'block';

                    const rect = this.getBoundingClientRect();
                    const containerRect = container.getBoundingClientRect();
                    tooltip.style.left = (rect.left - containerRect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
                    tooltip.style.top = (rect.top - containerRect.top - tooltip.offsetHeight - 10) + 'px';

                    const tooltipRect = tooltip.getBoundingClientRect();
                    if (tooltipRect.left < containerRect.left) {
                        tooltip.style.left = '10px';
                    } else if (tooltipRect.right > containerRect.right) {
                        tooltip.style.left = (containerRect.width - tooltip.offsetWidth - 10) + 'px';
                    }
                });

                estado.addEventListener('mouseleave', function() {
                    tooltip.style.opacity = '0';
                    setTimeout(() => {
                        if (tooltip.style.opacity === '0') {
                            tooltip.style.display = 'none';
                        }
                    }, 180);
                });
            }

            estado.addEventListener('click', function() {
                mostrarDetalleEstado(nombreEstado, datosDelEstado);
            });
        } else {
            estado.style.fill = getGeoColor('--geo-map-empty', '#D7DEE8');
            estado.classList.add('estado-sin-datos');

            if ('ontouchstart' in window) {
                estado.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    mostrarTooltipMobile(nombreEstado || 'Estado desconocido', null, 0);
                });
            }
        }
    });
}

function mostrarTooltipMobile(nombreEstado, datos, porcentaje) {
    if (datos) {
        Swal.fire({
            title: nombreEstado,
            html: `
                <div class="text-left text-sm">
                    <p class="mb-1"><strong>Huéspedes:</strong> ${Number(datos.total_huespedes || 0).toLocaleString('es-MX')}</p>
                    <p class="mb-1"><strong>Reservaciones:</strong> ${Number(datos.total_reservaciones || 0).toLocaleString('es-MX')}</p>
                    <p class="mb-1"><strong>Ingresos:</strong> ${formatCurrency(datos.ingresos_totales || 0)}</p>
                    <p class="font-bold text-amber-600">${porcentaje.toFixed(1)}% del total</p>
                </div>
            `,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            toast: true,
            position: 'center',
            width: '260px'
        });
    } else {
        Swal.fire({
            title: nombreEstado,
            text: 'Sin datos disponibles',
            icon: 'info',
            showConfirmButton: false,
            timer: 2000,
            toast: true,
            position: 'center',
            width: '220px'
        });
    }
}

function mostrarDetalleEstado(nombreEstado, datos) {
    const total = Math.max(Number(totalHuespedesGlobal || 0), 1);
    Swal.fire({
        title: nombreEstado,
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Total de huéspedes:</strong> ${Number(datos.total_huespedes || 0).toLocaleString('es-MX')}</p>
                <p class="mb-2"><strong>Reservaciones:</strong> ${Number(datos.total_reservaciones || 0).toLocaleString('es-MX')}</p>
                <p class="mb-2"><strong>Ingresos totales:</strong> ${formatCurrency(datos.ingresos_totales || 0)}</p>
                <p class="mb-2"><strong>Porcentaje del total:</strong> ${((Number(datos.total_huespedes || 0) / total) * 100).toFixed(1)}%</p>
                <p><strong>Promedio por reservación:</strong> ${(Number(datos.total_huespedes || 0) / Math.max(Number(datos.total_reservaciones || 0), 1)).toFixed(1)} huéspedes</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: getGeoColor('--geo-primary', '#1B2746'),
        confirmButtonText: 'Cerrar',
        width: window.innerWidth < 768 ? '90%' : '500px'
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.procedencia-view');
    if (view) {
        requestAnimationFrame(() => view.classList.add('loaded'));
    }

    setTimeout(() => {
        document.querySelectorAll('.state-progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            requestAnimationFrame(() => {
                bar.style.width = width;
            });
        });
    }, 250);

    buildCharts();
    initMapaSVG();
});
</script>

<?php /* Sin include de footer aqui: esta vista se sirve con View::renderTemplate(),
   que ya lo incluye. Incluirlo a mano duplicaba el copiloto y no abria. */ ?>
