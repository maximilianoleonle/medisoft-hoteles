<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$estadisticas = $estadisticas ?? [];
$porTipo = $porTipo ?? [];
$porPrioridad = $porPrioridad ?? [];
$habitacionesMasMant = $habitacionesMasMantenimiento ?? [];
$ultimosMantenimientos = $ultimosMantenimientos ?? [];
$tendenciaMensual = $tendenciaMensual ?? [];
$realizadoPorTop = $realizadoPorTop ?? [];
$fecha_inicio = !empty($fecha_inicio) ? $fecha_inicio : date('Y-m-01');
$fecha_fin = !empty($fecha_fin) ? $fecha_fin : date('Y-m-d');
$tipoSeleccionado = $tipo_filtro ?? ($_GET['tipo'] ?? '');

if (!function_exists('mant_safe')) {
    function mant_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mant_money')) {
    function mant_money($amount, $decimals = 0) {
        return '$' . number_format((float)($amount ?? 0), $decimals);
    }
}

if (!function_exists('mant_date')) {
    function mant_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('mant_duration')) {
    function mant_duration($hours) {
        $hours = (float)($hours ?? 0);
        if ($hours >= 24) {
            return number_format($hours / 24, 1) . ' d';
        }
        return number_format($hours, 1) . ' h';
    }
}

$total = (int)($estadisticas['total_mantenimientos'] ?? 0);
$completados = (int)($estadisticas['completados'] ?? 0);
$en_proceso = (int)($estadisticas['en_proceso'] ?? 0);
$cancelados = (int)($estadisticas['cancelados'] ?? 0);
$programados = (int)($estadisticas['programados'] ?? 0);
$costo_total = (float)($estadisticas['costo_total'] ?? 0);
$costo_prom = (float)($estadisticas['costo_promedio'] ?? 0);
$dur_prom_h = (float)($estadisticas['duracion_promedio_horas'] ?? 0);
$tasa = $total > 0 ? round(($completados / $total) * 100) : 0;
$pendientes = $en_proceso + $programados;
$periodo_texto = mant_date($fecha_inicio, 'd/m') . ' - ' . mant_date($fecha_fin);

$tipoConfig = [
    'preventivo' => ['color' => '#16824E', 'bg' => '#EAF6EF', 'icon' => 'fa-shield-alt', 'label' => 'Preventivo'],
    'correctivo' => ['color' => '#B7791F', 'bg' => '#FFF4D8', 'icon' => 'fa-wrench', 'label' => 'Correctivo'],
    'emergencia' => ['color' => '#B94A48', 'bg' => '#FDE9E7', 'icon' => 'fa-exclamation-triangle', 'label' => 'Emergencia'],
    'limpieza_profunda' => ['color' => '#3B83BD', 'bg' => '#E8F3FA', 'icon' => 'fa-broom', 'label' => 'Limpieza profunda'],
];

$prioridadColor = [
    'baja' => ['dot' => '#16824E', 'bg' => '#EAF6EF', 'text' => '#166534', 'label' => 'Baja'],
    'media' => ['dot' => '#BD9441', 'bg' => '#FFF7DF', 'text' => '#76520E', 'label' => 'Media'],
    'alta' => ['dot' => '#C97362', 'bg' => '#FDEDEA', 'text' => '#9B2C2C', 'label' => 'Alta'],
    'urgente' => ['dot' => '#8B5CF6', 'bg' => '#F1EBFF', 'text' => '#5B21B6', 'label' => 'Urgente'],
];

$estadoConfig = [
    'en_proceso' => ['bg' => '#E8F3FA', 'text' => '#1D5F8C', 'dot' => '#3B83BD', 'label' => 'En proceso'],
    'completado' => ['bg' => '#EAF6EF', 'text' => '#166534', 'dot' => '#16824E', 'label' => 'Completado'],
    'cancelado' => ['bg' => '#FDE9E7', 'text' => '#9B2C2C', 'dot' => '#B94A48', 'label' => 'Cancelado'],
    'programado' => ['bg' => '#F1E8DA', 'text' => '#6B5634', 'dot' => '#BD9441', 'label' => 'Programado'],
];

$maxHabitaciones = !empty($habitacionesMasMant) ? max(array_map(fn($h) => (int)($h['total_mantenimientos'] ?? 0), $habitacionesMasMant)) : 1;
$maxPrioridad = !empty($porPrioridad) ? max(array_map(fn($p) => (int)($p['cantidad'] ?? 0), $porPrioridad)) : 1;
$maxResponsables = !empty($realizadoPorTop) ? max(array_map(fn($r) => (int)($r['cantidad'] ?? 0), $realizadoPorTop)) : 1;

$tiposLabels = array_map(function($tipo) use ($tipoConfig) {
    $key = $tipo['tipo_mantenimiento'] ?? $tipo['tipo'] ?? '';
    return $tipoConfig[$key]['label'] ?? ucfirst(str_replace('_', ' ', $key));
}, $porTipo);
$tiposData = array_map(fn($tipo) => (int)($tipo['cantidad'] ?? 0), $porTipo);
$tiposColors = array_map(function($tipo) use ($tipoConfig) {
    $key = $tipo['tipo_mantenimiento'] ?? $tipo['tipo'] ?? '';
    return $tipoConfig[$key]['color'] ?? '#748096';
}, $porTipo);
$mesLabels = array_map(fn($mes) => (string)($mes['mes_label'] ?? $mes['mes'] ?? ''), $tendenciaMensual);
$mesData = array_map(fn($mes) => (int)($mes['cantidad'] ?? 0), $tendenciaMensual);
$mesCostos = array_map(fn($mes) => (float)($mes['costo_mes'] ?? 0), $tendenciaMensual);
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');
</style><style>
.mant-report-view {
    --mant-primary: var(--brand-primary, #1B2746);
    --mant-secondary: var(--brand-secondary, #0F172A);
    --mant-accent: var(--brand-accent, #BD9441);
    --mant-action: var(--brand-action-bg, var(--mant-primary));
    --mant-action-hover: var(--brand-action-bg-hover, var(--mant-secondary));
    --mant-on-action: var(--brand-action-text, #FFFEFB);
    --mant-bg: color-mix(in srgb, var(--mant-accent) 8%, #F6F1E8);
    --mant-surface: color-mix(in srgb, var(--mant-accent) 3%, #FFFDF8);
    --mant-soft: color-mix(in srgb, var(--mant-primary) 5%, #FFFDF8);
    --mant-line: color-mix(in srgb, var(--mant-primary) 13%, #E8DCCA);
    --mant-line-soft: color-mix(in srgb, var(--mant-primary) 8%, #F1E8DA);
    --mant-text: #17233E;
    --mant-muted: #748096;
    --mant-good: #16824E;
    --mant-warn: #B7791F;
    --mant-bad: #B94A48;
    --mant-card-shadow: 0 18px 44px -36px rgba(15, 23, 42, .52);
    min-height: 100vh;
    background:
        radial-gradient(circle at 86% 4%, color-mix(in srgb, var(--mant-accent) 24%, transparent), transparent 30rem),
        linear-gradient(135deg, color-mix(in srgb, var(--mant-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, var(--mant-bg), #FBFAF7 54%, #F1EAE0);
    color: var(--mant-text);
    opacity: 0;
    transition: opacity .24s ease;
}

.mant-report-view.loaded {
    opacity: 1;
}

.mant-shell {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px clamp(34px, 4vw, 76px) 50px;
    box-sizing: border-box;
}

.mant-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}

.mant-hero-main,
.mant-filter-panel,
.mant-metric,
.mant-panel,
.mant-type-card,
.mant-insight {
    border: 1px solid var(--mant-line);
    background: var(--mant-surface);
    box-shadow: var(--mant-card-shadow);
}

.mant-hero-main {
    position: relative;
    min-height: 325px;
    overflow: hidden;
    border-radius: 28px;
    padding: clamp(24px, 4vw, 44px);
    border-color: color-mix(in srgb, var(--mant-accent) 28%, transparent);
    background:
        radial-gradient(circle at 86% 18%, color-mix(in srgb, var(--mant-accent) 30%, transparent), transparent 22rem),
        linear-gradient(135deg,
            color-mix(in srgb, var(--mant-action) 54%, #101827),
            color-mix(in srgb, var(--mant-action-hover) 58%, #060A12)
        );
}

.mant-hero-main::after {
    content: "";
    position: absolute;
    inset: auto -10% -54% 46%;
    height: 220px;
    background: radial-gradient(circle, color-mix(in srgb, var(--mant-accent) 42%, transparent), transparent 67%);
    pointer-events: none;
}

.mant-back,
.mant-btn,
.mant-period-btn,
.mant-print {
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.mant-back,
.mant-print {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 38px;
    padding: 0 13px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 13px;
    background: rgba(255, 255, 255, .09);
    color: #FFFDF8;
    font-weight: 700;
    text-decoration: none;
}

.mant-print {
    cursor: pointer;
}

.mant-back:hover,
.mant-print:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, .32);
    background: rgba(255, 255, 255, .14);
}

.mant-kicker,
.mant-label,
.mant-section-kicker,
.mant-table th,
.mant-mini-label {
    color: var(--mant-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.mant-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    width: fit-content;
    margin-top: 26px;
    padding: 8px 11px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 999px;
    background: rgba(255, 255, 255, .1);
    color: #FFFFFF;
    text-shadow: 0 1px 1px rgba(0, 0, 0, .22);
}

.mant-kicker i {
    color: color-mix(in srgb, var(--mant-accent) 42%, #FFFFFF);
}

.mant-hero-main h1 {
    position: relative;
    z-index: 1;
    max-width: 12ch;
    margin: 14px 0 14px;
    color: var(--mant-on-action);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(2.4rem, 5vw, 5.15rem);
    font-weight: 700;
    line-height: .9;
    letter-spacing: 0;
    text-wrap: balance;
}

.mant-hero-main p {
    position: relative;
    z-index: 1;
    max-width: 68ch;
    margin: 0;
    color: color-mix(in srgb, var(--mant-on-action) 82%, transparent);
    font-weight: 500;
    line-height: 1.6;
}

.mant-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.mant-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--mant-accent) 45%, var(--mant-primary));
    border-radius: 13px;
    background: var(--mant-action);
    color: var(--mant-on-action);
    font-weight: 700;
    text-decoration: none;
}

.mant-btn.is-accent {
    border-color: color-mix(in srgb, var(--mant-action) 34%, var(--mant-accent));
    background: var(--mant-action);
    color: var(--mant-on-action);
}

.mant-hero-main .mant-btn.is-accent {
    border-color: rgba(255, 255, 255, .9);
    background: #FFFFFF;
    color: color-mix(in srgb, var(--mant-action) 78%, #05070D);
}

.mant-hero-main .mant-btn.is-accent i {
    color: color-mix(in srgb, var(--mant-accent) 48%, var(--mant-action));
}

.mant-btn.is-soft {
    border-color: rgba(255, 255, 255, .22);
    background: rgba(255, 255, 255, .1);
    color: #FFFDF8;
}

.mant-btn:hover,
.mant-period-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 32px -25px rgba(15, 23, 42, .58);
}

.mant-filter-panel {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 18px;
    border-radius: 25px;
    padding: 20px;
}

.mant-filter-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
}

.mant-period-value {
    margin-top: 7px;
    color: var(--mant-primary);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(1.5rem, 3vw, 2.35rem);
    font-weight: 700;
    line-height: 1;
}

.mant-filter-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.mant-field label {
    display: block;
    margin-bottom: 6px;
    color: var(--mant-primary);
    font-size: .75rem;
    font-weight: 700;
}

.mant-field input,
.mant-field select {
    width: 100%;
    min-height: 43px;
    border: 1px solid var(--mant-line);
    border-radius: 13px;
    background: color-mix(in srgb, var(--mant-accent) 3%, #FFFDF8);
    color: var(--mant-primary);
    font-size: .92rem;
    font-weight: 600;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.mant-field input:focus,
.mant-field select:focus {
    border-color: color-mix(in srgb, var(--mant-accent) 62%, var(--mant-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--mant-accent) 22%, transparent);
}

.mant-field.is-full,
.mant-filter-form .mant-btn {
    grid-column: 1 / -1;
}

.mant-periods {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.mant-period-btn {
    min-height: 40px;
    border: 1px solid var(--mant-line-soft);
    border-radius: 13px;
    background: color-mix(in srgb, var(--mant-primary) 4%, #FFFDF8);
    color: var(--mant-primary);
    font-size: .76rem;
    font-weight: 700;
}

.mant-metrics {
    display: grid;
    grid-template-columns: minmax(250px, 1.15fr) repeat(5, minmax(145px, .88fr));
    gap: 14px;
    margin-bottom: 18px;
}

.mant-metric {
    min-width: 0;
    overflow: hidden;
    border-radius: 21px;
    padding: 18px;
}

.mant-metric.is-featured {
    background:
        radial-gradient(circle at 92% 8%, color-mix(in srgb, var(--mant-accent) 18%, transparent), transparent 12rem),
        linear-gradient(135deg, color-mix(in srgb, var(--mant-accent) 10%, #FFFDF8), var(--mant-surface));
}

.mant-metric-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.mant-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--mant-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--mant-accent) 82%, var(--mant-primary));
}

.mant-value {
    margin-top: 13px;
    color: var(--mant-primary);
    font-size: clamp(1.35rem, 2.4vw, 2.1rem);
    font-weight: 700;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.mant-note {
    margin-top: 9px;
    color: var(--mant-muted);
    font-size: .8rem;
    font-weight: 600;
}

.mant-layout {
    display: grid;
    grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr);
    gap: 18px;
    align-items: start;
    margin-bottom: 18px;
}

.mant-panel {
    border-radius: 22px;
    overflow: hidden;
}

.mant-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--mant-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--mant-accent) 6%, #FFFDF8), var(--mant-surface));
}

.mant-section-head h2,
.mant-section-head h3 {
    margin: 0;
    color: var(--mant-primary);
    font-size: 1.05rem;
    font-weight: 700;
}

.mant-section-body {
    padding: 18px 20px 20px;
}

.mant-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--mant-primary) 7%, #FFFDF8);
    color: var(--mant-primary);
    font-size: .73rem;
    font-weight: 700;
    white-space: nowrap;
}

.mant-status-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
}

.mant-gauge-wrap {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 18px;
    align-items: center;
}

.mant-gauge {
    position: relative;
    width: 154px;
    height: 154px;
}

.mant-gauge svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.mant-gauge-bg {
    fill: none;
    stroke: color-mix(in srgb, var(--mant-primary) 8%, #FFFDF8);
    stroke-width: 12;
}

.mant-gauge-fill {
    fill: none;
    stroke: var(--mant-good);
    stroke-width: 12;
    stroke-linecap: round;
    transition: stroke-dashoffset .8s ease;
}

.mant-gauge-center {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.mant-gauge-center strong {
    color: var(--mant-primary);
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.mant-gauge-center span {
    color: var(--mant-muted);
    font-size: .76rem;
    font-weight: 600;
}

.mant-status-stack,
.mant-priority-stack,
.mant-room-stack,
.mant-people-stack {
    display: grid;
    gap: 10px;
}

.mant-status-row,
.mant-priority-row,
.mant-room-row,
.mant-person-row {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 11px;
    padding: 13px 14px;
    border: 1px solid var(--mant-line-soft);
    border-radius: 18px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--mant-accent) 4%, #FFFFFF), #FFFDF8);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .74);
}

.mant-room-row,
.mant-person-row {
    grid-template-columns: minmax(104px, auto) minmax(0, 1fr) minmax(28px, auto);
}

.mant-status-row strong,
.mant-priority-row strong,
.mant-room-row > div > strong,
.mant-person-row > div > strong {
    display: block;
    color: var(--mant-primary);
    font-weight: 700;
}

.mant-status-row > div > span,
.mant-priority-row > div > span,
.mant-room-row > div > span,
.mant-person-row > div > span {
    display: block;
    color: var(--mant-muted);
    font-size: .78rem;
    font-weight: 600;
}

.mant-row-value {
    color: var(--mant-primary);
    justify-self: end;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.mant-progress {
    height: 7px;
    margin-top: 7px;
    overflow: hidden;
    border-radius: 999px;
    background: color-mix(in srgb, var(--mant-primary) 8%, #FFFDF8);
}

.mant-progress span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--mant-accent);
    transition: width .75s ease;
}

.mant-chart-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(260px, .72fr);
    gap: 14px;
}

.mant-chart-box {
    min-height: 330px;
    padding: 16px;
    border: 1px solid var(--mant-line-soft);
    border-radius: 18px;
    background: color-mix(in srgb, var(--mant-primary) 3%, #FFFDF8);
}

.mant-chart-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.mant-chart-title strong {
    color: var(--mant-primary);
    font-size: .94rem;
    font-weight: 700;
}

.mant-chart-canvas {
    position: relative;
    height: 270px;
}

.mant-type-grid,
.mant-insights-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
}

.mant-type-card,
.mant-insight {
    min-width: 0;
    border-radius: 20px;
    padding: 17px;
}

.mant-type-card {
    border-color: var(--type-color, var(--mant-line));
    background:
        radial-gradient(circle at 95% 8%, color-mix(in srgb, var(--type-color, var(--mant-accent)) 14%, transparent), transparent 9rem),
        var(--mant-surface);
}

.mant-type-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.mant-type-head h4,
.mant-insight h4 {
    margin: 0;
    color: var(--mant-primary);
    font-size: .98rem;
    font-weight: 700;
}

.mant-mini-value {
    display: block;
    margin-top: 4px;
    color: var(--mant-primary);
    font-size: 1rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.mant-type-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.mant-table-wrap {
    overflow-x: auto;
    max-height: 560px;
}

.mant-table {
    width: 100%;
    min-width: 1080px;
    border-collapse: separate;
    border-spacing: 0;
}

.mant-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 12px 11px;
    border-bottom: 1px solid var(--mant-line);
    background: color-mix(in srgb, var(--mant-accent) 7%, #FFFDF8);
    text-align: left;
}

.mant-table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--mant-line-soft);
    color: var(--mant-text);
    font-size: .86rem;
    vertical-align: top;
}

.mant-table tbody tr {
    transition: background .18s ease;
}

.mant-table tbody tr:hover {
    background: color-mix(in srgb, var(--mant-accent) 5%, #FFFDF8);
}

.mant-room-badge,
.mant-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    padding: 0 10px;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 700;
    white-space: nowrap;
}

.mant-room-badge {
    position: relative;
    min-width: 98px;
    justify-content: center;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--mant-primary) 16%, #E8DCCA);
    background:
        linear-gradient(180deg, #FFFFFF, color-mix(in srgb, var(--mant-accent) 7%, #FFFDF8));
    color: color-mix(in srgb, var(--mant-primary) 86%, #101827);
    box-shadow:
        0 10px 22px -18px rgba(15, 23, 42, .55),
        inset 4px 0 0 color-mix(in srgb, var(--mant-accent) 72%, var(--mant-primary));
}

.mant-room-row > .mant-room-badge,
.mant-table .mant-room-badge {
    color: color-mix(in srgb, var(--mant-primary) 86%, #101827);
}

.mant-chip {
    background: color-mix(in srgb, var(--mant-primary) 6%, #FFFDF8);
    color: var(--mant-primary);
}

.mant-description {
    max-width: 270px;
}

.mant-description strong {
    display: block;
    max-width: 270px;
    color: var(--mant-primary);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mant-description span {
    display: block;
    max-width: 270px;
    margin-top: 3px;
    color: var(--mant-muted);
    font-size: .76rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.mant-person-avatar {
    width: 42px;
    height: 42px;
    display: inline-grid;
    align-items: center;
    justify-content: center;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--mant-primary) 16%, #E8DCCA);
    border-radius: 15px;
    background:
        linear-gradient(180deg, #FFFFFF, color-mix(in srgb, var(--mant-accent) 8%, #FFFDF8));
    color: color-mix(in srgb, var(--mant-primary) 86%, #101827);
    font-size: .75rem;
    font-weight: 700;
    box-shadow:
        0 10px 22px -18px rgba(15, 23, 42, .55),
        inset 0 -4px 0 color-mix(in srgb, var(--mant-accent) 58%, transparent);
}

.mant-person-row > .mant-person-avatar {
    color: color-mix(in srgb, var(--mant-primary) 86%, #101827);
}

.mant-insight {
    background:
        radial-gradient(circle at 92% 12%, color-mix(in srgb, var(--mant-accent) 13%, transparent), transparent 10rem),
        var(--mant-surface);
}

.mant-insight p {
    margin: 10px 0 0;
    color: var(--mant-muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.55;
}

.mant-empty {
    display: grid;
    place-items: center;
    min-height: 220px;
    padding: 32px;
    color: var(--mant-muted);
    text-align: center;
}

.mant-empty i {
    width: 64px;
    height: 64px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 22px;
    background: color-mix(in srgb, var(--mant-accent) 12%, #FFFDF8);
    color: color-mix(in srgb, var(--mant-accent) 80%, var(--mant-primary));
    font-size: 1.55rem;
}

.mant-empty strong {
    display: block;
    color: var(--mant-primary);
    font-size: 1rem;
    font-weight: 700;
}

@media (max-width: 1320px) {
    .mant-metrics {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .mant-metric.is-featured {
        grid-column: span 2;
    }
}

@media (max-width: 1180px) {
    .mant-hero,
    .mant-layout,
    .mant-chart-grid {
        grid-template-columns: 1fr;
    }

    .mant-type-grid,
    .mant-insights-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .mant-shell {
        width: 100%;
        padding: 18px 12px 36px;
    }

    .mant-hero-main,
    .mant-filter-panel,
    .mant-metric,
    .mant-panel,
    .mant-type-card,
    .mant-insight {
        border-radius: 18px;
    }

    .mant-hero-main {
        min-height: auto;
        padding: 22px;
    }

    .mant-kicker {
        margin-top: 20px;
    }

    .mant-hero-main h1 {
        max-width: 10ch;
        font-size: clamp(2.1rem, 14vw, 3.55rem);
    }

    .mant-filter-form,
    .mant-periods,
    .mant-metrics,
    .mant-type-grid,
    .mant-insights-grid,
    .mant-type-stats,
    .mant-gauge-wrap {
        grid-template-columns: 1fr;
    }

    .mant-metric.is-featured {
        grid-column: auto;
    }

    .mant-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .mant-room-row,
    .mant-person-row {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: start;
    }

    .mant-room-row > .mant-room-badge,
    .mant-person-row > .mant-person-avatar {
        grid-column: 1 / -1;
        justify-self: start;
    }

    .mant-gauge {
        margin: 0 auto;
    }

    .mant-chart-canvas {
        height: 245px;
    }

    .mant-table {
        min-width: 960px;
    }
}

@media print {
    .mant-report-view {
        background: #fff !important;
    }

    .mant-filter-panel,
    .mant-hero-actions,
    .mant-back,
    .mant-report-view button {
        display: none !important;
    }

    .mant-shell {
        width: 100%;
        padding: 0;
    }

    .mant-panel,
    .mant-metric {
        box-shadow: none !important;
    }
}
</style>

<div class="mant-report-view">
    <main class="mant-shell">
        <section class="mant-hero">
            <div class="mant-hero-main">
                <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reportes') ?>" class="mant-back ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Reportes
                </a>

                <div class="mant-kicker">
                    <i class="fas fa-hard-hat"></i>
                    Reporte de mantenimiento
                </div>

                <h1>Bitácora técnica del hotel</h1>
                <p>
                    Vista operativa para revisar volumen, costos, prioridad, tiempos y habitaciones que más requieren atención en el periodo seleccionado.
                </p>

                <div class="mant-hero-actions">
                    <button type="button" onclick="window.print()" class="mant-btn is-accent ms-print-hide-mobile">
                        <i class="fas fa-print"></i>
                        Imprimir
                    </button>
                    <a href="#registroMantenimiento" class="mant-btn is-soft">
                        <i class="fas fa-list-ul"></i>
                        Ver registro
                    </a>
                </div>
            </div>

            <aside class="mant-filter-panel">
                <div class="mant-filter-top">
                    <div>
                        <span class="mant-label">Periodo revisado</span>
                        <div class="mant-period-value"><?= $periodo_texto ?></div>
                    </div>
                    <span class="mant-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>

                <form method="GET" action="<?= url('reportes/mantenimiento') ?>" class="mant-filter-form" id="mantFiltrosForm" data-auto-filter-form>
                    <div class="mant-field">
                        <label for="fecha_inicio">Fecha inicio</label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               value="<?= mant_safe($fecha_inicio, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mant-field">
                        <label for="fecha_fin">Fecha fin</label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               value="<?= mant_safe($fecha_fin, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="mant-field is-full">
                        <label for="tipo">Tipo de mantenimiento</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos los tipos</option>
                            <?php foreach ($tipoConfig as $key => $config): ?>
                                <option value="<?= mant_safe($key) ?>" <?= $tipoSeleccionado === $key ? 'selected' : '' ?>>
                                    <?= mant_safe($config['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="mant-btn">
                        <i class="fas fa-filter"></i>
                        Aplicar filtros
                    </button>
                </form>

                <div class="mant-periods">
                    <button type="button" onclick="setMantPeriodo(30)" class="mant-period-btn">1M</button>
                    <button type="button" onclick="setMantPeriodo(90)" class="mant-period-btn">3M</button>
                    <button type="button" onclick="setMantPeriodo(180)" class="mant-period-btn">6M</button>
                    <button type="button" onclick="setMantPeriodo(365)" class="mant-period-btn">1A</button>
                </div>
            </aside>
        </section>

        <section class="mant-metrics" aria-label="Resumen de mantenimiento">
            <article class="mant-metric is-featured">
                <div class="mant-metric-head">
                    <span class="mant-label">Trabajos registrados</span>
                    <span class="mant-icon"><i class="fas fa-clipboard-list"></i></span>
                </div>
                <div class="mant-value"><?= number_format($total) ?></div>
                <div class="mant-note"><?= $tasa ?>% completado · <?= number_format($pendientes) ?> pendientes o activos.</div>
            </article>

            <article class="mant-metric">
                <div class="mant-metric-head">
                    <span class="mant-label">Completados</span>
                    <span class="mant-icon"><i class="fas fa-check-circle"></i></span>
                </div>
                <div class="mant-value"><?= number_format($completados) ?></div>
                <div class="mant-note">Cierres del periodo.</div>
            </article>

            <article class="mant-metric">
                <div class="mant-metric-head">
                    <span class="mant-label">En proceso</span>
                    <span class="mant-icon"><i class="fas fa-spinner"></i></span>
                </div>
                <div class="mant-value"><?= number_format($en_proceso) ?></div>
                <div class="mant-note">Trabajos activos.</div>
            </article>

            <article class="mant-metric">
                <div class="mant-metric-head">
                    <span class="mant-label">Programados</span>
                    <span class="mant-icon"><i class="fas fa-calendar-check"></i></span>
                </div>
                <div class="mant-value"><?= number_format($programados) ?></div>
                <div class="mant-note">Pendientes de iniciar.</div>
            </article>

            <article class="mant-metric">
                <div class="mant-metric-head">
                    <span class="mant-label">Costo total</span>
                    <span class="mant-icon"><i class="fas fa-dollar-sign"></i></span>
                </div>
                <div class="mant-value"><?= mant_money($costo_total) ?></div>
                <div class="mant-note">Promedio <?= mant_money($costo_prom) ?> por trabajo.</div>
            </article>

            <article class="mant-metric">
                <div class="mant-metric-head">
                    <span class="mant-label">Duración prom.</span>
                    <span class="mant-icon"><i class="fas fa-clock"></i></span>
                </div>
                <div class="mant-value"><?= mant_duration($dur_prom_h) ?></div>
                <div class="mant-note">Sobre trabajos completados.</div>
            </article>
        </section>

        <section class="mant-layout">
            <article class="mant-panel">
                <div class="mant-section-head">
                    <div>
                        <span class="mant-section-kicker">Estado general</span>
                        <h2>Tasa de completitud</h2>
                    </div>
                    <span class="mant-pill"><?= $tasa ?>%</span>
                </div>
                <div class="mant-section-body">
                    <div class="mant-gauge-wrap">
                        <div class="mant-gauge">
                            <?php $circumference = round(2 * M_PI * 42, 2); ?>
                            <svg viewBox="0 0 100 100" aria-hidden="true">
                                <circle class="mant-gauge-bg" cx="50" cy="50" r="42"></circle>
                                <circle class="mant-gauge-fill"
                                        cx="50"
                                        cy="50"
                                        r="42"
                                        stroke-dasharray="<?= $circumference ?>"
                                        stroke-dashoffset="<?= round($circumference * (1 - ($tasa / 100)), 2) ?>"
                                        id="mantGaugeFill"></circle>
                            </svg>
                            <div class="mant-gauge-center">
                                <strong><?= $tasa ?>%</strong>
                                <span>completado</span>
                            </div>
                        </div>

                        <div class="mant-status-stack">
                            <?php
                            $statusRows = [
                                ['key' => 'completado', 'label' => 'Completados', 'value' => $completados, 'note' => 'Cerrados correctamente'],
                                ['key' => 'en_proceso', 'label' => 'En proceso', 'value' => $en_proceso, 'note' => 'Activos ahora'],
                                ['key' => 'programado', 'label' => 'Programados', 'value' => $programados, 'note' => 'Pendientes de iniciar'],
                                ['key' => 'cancelado', 'label' => 'Cancelados', 'value' => $cancelados, 'note' => 'Sin cierre operativo'],
                            ];
                            ?>
                            <?php foreach ($statusRows as $row): ?>
                                <?php $config = $estadoConfig[$row['key']]; ?>
                                <div class="mant-status-row">
                                    <span class="mant-status-dot" style="background: <?= $config['dot'] ?>;"></span>
                                    <div>
                                        <strong><?= mant_safe($row['label']) ?></strong>
                                        <span><?= mant_safe($row['note']) ?></span>
                                    </div>
                                    <span class="mant-row-value"><?= number_format($row['value']) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </article>

            <article class="mant-panel">
                <div class="mant-section-head">
                    <div>
                        <span class="mant-section-kicker">Actividad</span>
                        <h2>Tendencia y mezcla</h2>
                    </div>
                    <span class="mant-pill">Chart.js</span>
                </div>
                <div class="mant-section-body">
                    <div class="mant-chart-grid">
                        <div class="mant-chart-box">
                            <div class="mant-chart-title">
                                <strong>Tendencia mensual</strong>
                                <span class="mant-mini-label">trabajos</span>
                            </div>
                            <div class="mant-chart-canvas">
                                <?php if (!empty($tendenciaMensual)): ?>
                                    <canvas id="chartTend"></canvas>
                                <?php else: ?>
                                    <div class="mant-empty">
                                        <div>
                                            <i class="fas fa-chart-bar"></i>
                                            <strong>Sin tendencia disponible</strong>
                                            <p>Aún no hay datos para graficar este periodo.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mant-chart-box">
                            <div class="mant-chart-title">
                                <strong>Distribución por tipo</strong>
                                <span class="mant-mini-label">mix</span>
                            </div>
                            <div class="mant-chart-canvas">
                                <?php if (!empty($porTipo)): ?>
                                    <canvas id="chartDonut"></canvas>
                                <?php else: ?>
                                    <div class="mant-empty">
                                        <div>
                                            <i class="fas fa-tools"></i>
                                            <strong>Sin desglose por tipo</strong>
                                            <p>Cuando existan trabajos aparecerá la distribución.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="mant-panel">
            <div class="mant-section-head">
                <div>
                    <span class="mant-section-kicker">Clasificación</span>
                    <h2>Tipos y prioridades</h2>
                </div>
                <span class="mant-pill"><?= number_format(count($porTipo)) ?> tipos</span>
            </div>
            <div class="mant-section-body">
                <?php if (!empty($porTipo) || !empty($porPrioridad)): ?>
                    <?php if (!empty($porTipo)): ?>
                        <div class="mant-type-grid" style="margin-bottom: 14px;">
                            <?php foreach ($porTipo as $tipo): ?>
                                <?php
                                $typeKey = $tipo['tipo_mantenimiento'] ?? $tipo['tipo'] ?? '';
                                $config = $tipoConfig[$typeKey] ?? ['color' => '#748096', 'bg' => '#F1F5F9', 'icon' => 'fa-tools', 'label' => ucfirst(str_replace('_', ' ', $typeKey))];
                                $cantidad = (int)($tipo['cantidad'] ?? 0);
                                $porcentaje = $total > 0 ? round(($cantidad / $total) * 100, 1) : 0;
                                ?>
                                <article class="mant-type-card" style="--type-color: <?= $config['color'] ?>;">
                                    <div class="mant-type-head">
                                        <div>
                                            <span class="mant-icon" style="background: <?= $config['bg'] ?>; color: <?= $config['color'] ?>;">
                                                <i class="fas <?= $config['icon'] ?>"></i>
                                            </span>
                                            <h4 style="margin-top: 10px;"><?= mant_safe($config['label']) ?></h4>
                                        </div>
                                        <span class="mant-pill"><?= number_format($cantidad) ?></span>
                                    </div>
                                    <div class="mant-type-stats">
                                        <div>
                                            <span class="mant-mini-label">Participación</span>
                                            <strong class="mant-mini-value"><?= number_format($porcentaje, 1) ?>%</strong>
                                        </div>
                                        <div>
                                            <span class="mant-mini-label">Costo</span>
                                            <strong class="mant-mini-value"><?= mant_money($tipo['costo_total'] ?? 0) ?></strong>
                                        </div>
                                        <div>
                                            <span class="mant-mini-label">Completados</span>
                                            <strong class="mant-mini-value"><?= number_format((int)($tipo['completados'] ?? 0)) ?></strong>
                                        </div>
                                        <div>
                                            <span class="mant-mini-label">Duración</span>
                                            <strong class="mant-mini-value"><?= mant_duration($tipo['duracion_promedio'] ?? 0) ?></strong>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($porPrioridad)): ?>
                        <div class="mant-priority-stack">
                            <?php foreach ($porPrioridad as $prioridad): ?>
                                <?php
                                $key = $prioridad['prioridad'] ?? '';
                                $config = $prioridadColor[$key] ?? ['dot' => '#748096', 'bg' => '#F1F5F9', 'text' => '#475569', 'label' => ucfirst($key)];
                                $cantidad = (int)($prioridad['cantidad'] ?? 0);
                                $width = $maxPrioridad > 0 ? round(($cantidad / $maxPrioridad) * 100) : 0;
                                ?>
                                <div class="mant-priority-row">
                                    <span class="mant-status-dot" style="background: <?= $config['dot'] ?>;"></span>
                                    <div>
                                        <strong><?= mant_safe($config['label']) ?></strong>
                                        <span><?= number_format((float)($prioridad['tasa_completitud'] ?? 0), 1) ?>% completitud · <?= mant_duration($prioridad['duracion_promedio'] ?? 0) ?></span>
                                        <div class="mant-progress">
                                            <span style="width: <?= $width ?>%; background: <?= $config['dot'] ?>;"></span>
                                        </div>
                                    </div>
                                    <span class="mant-row-value"><?= number_format($cantidad) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="mant-empty">
                        <div>
                            <i class="fas fa-layer-group"></i>
                            <strong>Sin clasificación disponible</strong>
                            <p>Los tipos y prioridades aparecerán cuando existan mantenimientos en el periodo.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="mant-layout">
            <article class="mant-panel">
                <div class="mant-section-head">
                    <div>
                        <span class="mant-section-kicker">Habitaciones</span>
                        <h2>Mayor carga de mantenimiento</h2>
                    </div>
                    <span class="mant-pill">Top <?= number_format(count($habitacionesMasMant)) ?></span>
                </div>
                <div class="mant-section-body">
                    <?php if (!empty($habitacionesMasMant)): ?>
                        <div class="mant-room-stack">
                            <?php foreach ($habitacionesMasMant as $habitacion): ?>
                                <?php
                                $totalHab = (int)($habitacion['total_mantenimientos'] ?? 0);
                                $width = $maxHabitaciones > 0 ? round(($totalHab / $maxHabitaciones) * 100) : 0;
                                ?>
                                <div class="mant-room-row">
                                    <span class="mant-room-badge">Hab. <?= mant_safe($habitacion['numero']) ?></span>
                                    <div>
                                        <strong><?= mant_safe($habitacion['tipo']) ?> · Piso <?= mant_safe($habitacion['piso']) ?></strong>
                                        <span><?= number_format((int)($habitacion['emergencias'] ?? 0)) ?> emergencias · <?= mant_money($habitacion['costo_total'] ?? 0) ?> costo</span>
                                        <div class="mant-progress">
                                            <span style="width: <?= $width ?>%;"></span>
                                        </div>
                                    </div>
                                    <span class="mant-row-value"><?= number_format($totalHab) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mant-empty">
                            <div>
                                <i class="fas fa-door-open"></i>
                                <strong>Sin habitaciones destacadas</strong>
                                <p>No hay habitaciones con mantenimientos en el periodo seleccionado.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="mant-panel">
                <div class="mant-section-head">
                    <div>
                        <span class="mant-section-kicker">Equipo</span>
                        <h2>Responsables con actividad</h2>
                    </div>
                    <span class="mant-pill"><?= number_format(count($realizadoPorTop)) ?> personas</span>
                </div>
                <div class="mant-section-body">
                    <?php if (!empty($realizadoPorTop)): ?>
                        <div class="mant-people-stack">
                            <?php foreach ($realizadoPorTop as $responsable): ?>
                                <?php
                                $nombre = $responsable['realizado_por'] ?? 'Sin asignar';
                                $cantidad = (int)($responsable['cantidad'] ?? 0);
                                $width = $maxResponsables > 0 ? round(($cantidad / $maxResponsables) * 100) : 0;
                                $iniciales = strtoupper(mb_substr($nombre, 0, 2));
                                ?>
                                <div class="mant-person-row">
                                    <span class="mant-person-avatar"><?= mant_safe($iniciales) ?></span>
                                    <div>
                                        <strong><?= mant_safe($nombre) ?></strong>
                                        <span><?= number_format($cantidad) ?> trabajos registrados</span>
                                        <div class="mant-progress">
                                            <span style="width: <?= $width ?>%; background: var(--mant-primary);"></span>
                                        </div>
                                    </div>
                                    <span class="mant-row-value"><?= number_format($cantidad) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="mant-empty">
                            <div>
                                <i class="fas fa-user-slash"></i>
                                <strong>Sin responsables registrados</strong>
                                <p>Cuando se asignen trabajos, aparecerá el ranking del equipo.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="mant-panel">
            <div class="mant-section-head">
                <div>
                    <span class="mant-section-kicker">Lectura ejecutiva</span>
                    <h2>Hallazgos del periodo</h2>
                </div>
                <span class="mant-pill">Operación</span>
            </div>
            <div class="mant-section-body">
                <div class="mant-insights-grid">
                    <article class="mant-insight">
                        <span class="mant-icon"><i class="fas fa-check-double"></i></span>
                        <h4>Cierre operativo</h4>
                        <p>
                            Se completó <strong><?= $tasa ?>%</strong> de los trabajos registrados, con
                            <strong><?= number_format($pendientes) ?></strong> mantenimientos pendientes o en proceso.
                        </p>
                    </article>

                    <article class="mant-insight">
                        <span class="mant-icon"><i class="fas fa-coins"></i></span>
                        <h4>Costo promedio</h4>
                        <p>
                            El costo promedio por trabajo es de <strong><?= mant_money($costo_prom) ?></strong>,
                            sobre un total de <strong><?= mant_money($costo_total) ?></strong>.
                        </p>
                    </article>

                    <article class="mant-insight">
                        <span class="mant-icon"><i class="fas fa-clock"></i></span>
                        <h4>Tiempo de solución</h4>
                        <p>
                            Los trabajos completados tardan en promedio <strong><?= mant_duration($dur_prom_h) ?></strong>.
                        </p>
                    </article>

                    <article class="mant-insight">
                        <span class="mant-icon"><i class="fas fa-door-open"></i></span>
                        <h4>Habitación más sensible</h4>
                        <p>
                            <?php if (!empty($habitacionesMasMant)): ?>
                                <strong>Hab. <?= mant_safe($habitacionesMasMant[0]['numero']) ?></strong>
                                registra <strong><?= number_format((int)($habitacionesMasMant[0]['total_mantenimientos'] ?? 0)) ?></strong> trabajos.
                            <?php else: ?>
                                No hay habitaciones con carga destacada en este periodo.
                            <?php endif; ?>
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="mant-panel" id="registroMantenimiento">
            <div class="mant-section-head">
                <div>
                    <span class="mant-section-kicker">Bitácora</span>
                    <h2>Registro de mantenimientos</h2>
                </div>
                <span class="mant-pill"><?= number_format(count($ultimosMantenimientos)) ?> registros</span>
            </div>
            <div class="mant-section-body">
                <?php if (!empty($ultimosMantenimientos)): ?>
                    <div class="mant-table-wrap">
                        <table class="mant-table">
                            <thead>
                                <tr>
                                    <th>Habitación</th>
                                    <th>Tipo</th>
                                    <th>Motivo / descripción</th>
                                    <th>Prioridad</th>
                                    <th>Inicio</th>
                                    <th>Fin</th>
                                    <th>Duración</th>
                                    <th>Responsable</th>
                                    <th>Costo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimosMantenimientos as $mantenimiento): ?>
                                    <?php
                                    $typeKey = $mantenimiento['tipo_mantenimiento'] ?? '';
                                    $type = $tipoConfig[$typeKey] ?? ['color' => '#748096', 'bg' => '#F1F5F9', 'icon' => 'fa-tools', 'label' => ucfirst(str_replace('_', ' ', $typeKey))];
                                    $priorityKey = $mantenimiento['prioridad'] ?? '';
                                    $priority = $prioridadColor[$priorityKey] ?? ['dot' => '#748096', 'bg' => '#F1F5F9', 'text' => '#475569', 'label' => ucfirst($priorityKey)];
                                    $stateKey = $mantenimiento['estado'] ?? '';
                                    $state = $estadoConfig[$stateKey] ?? ['bg' => '#F1F5F9', 'text' => '#475569', 'dot' => '#94A3B8', 'label' => ucfirst(str_replace('_', ' ', $stateKey))];

                                    $duracion = '-';
                                    if (!empty($mantenimiento['fecha_inicio']) && !empty($mantenimiento['fecha_fin'])) {
                                        $hours = (strtotime($mantenimiento['fecha_fin']) - strtotime($mantenimiento['fecha_inicio'])) / 3600;
                                        if ($hours >= 24) {
                                            $duracion = number_format($hours / 24, 1) . ' días';
                                        } elseif ($hours >= 1) {
                                            $duracion = number_format($hours, 1) . ' h';
                                        } else {
                                            $duracion = number_format(max($hours * 60, 0), 0) . ' min';
                                        }
                                    } elseif ($stateKey === 'en_proceso' && !empty($mantenimiento['fecha_inicio'])) {
                                        $hours = (time() - strtotime($mantenimiento['fecha_inicio'])) / 3600;
                                        $duracion = number_format(max($hours, 0), 1) . ' h en curso';
                                    }

                                    $responsable = !empty($mantenimiento['realizado_por'])
                                        ? $mantenimiento['realizado_por']
                                        : ($mantenimiento['usuario_nombre'] ?? 'Sin asignar');
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="mant-room-badge">
                                                Hab. <?= mant_safe($mantenimiento['habitacion_numero'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="mant-chip" style="background: <?= $type['bg'] ?>; color: <?= $type['color'] ?>;">
                                                <i class="fas <?= $type['icon'] ?>"></i>
                                                <?= mant_safe($type['label']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="mant-description">
                                                <strong title="<?= mant_safe($mantenimiento['motivo'] ?? '') ?>">
                                                    <?= mant_safe($mantenimiento['motivo'] ?? '-') ?>
                                                </strong>
                                                <?php if (!empty($mantenimiento['descripcion'])): ?>
                                                    <span title="<?= mant_safe($mantenimiento['descripcion']) ?>">
                                                        <?= mant_safe(mb_substr($mantenimiento['descripcion'], 0, 80)) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="mant-chip" style="background: <?= $priority['bg'] ?>; color: <?= $priority['text'] ?>;">
                                                <span class="mant-status-dot" style="background: <?= $priority['dot'] ?>;"></span>
                                                <?= mant_safe($priority['label']) ?>
                                            </span>
                                        </td>
                                        <td><?= mant_safe(!empty($mantenimiento['fecha_inicio']) ? date('d/m/y H:i', strtotime($mantenimiento['fecha_inicio'])) : '-') ?></td>
                                        <td><?= mant_safe(!empty($mantenimiento['fecha_fin']) ? date('d/m/y H:i', strtotime($mantenimiento['fecha_fin'])) : '-') ?></td>
                                        <td><?= mant_safe($duracion) ?></td>
                                        <td><?= mant_safe($responsable) ?></td>
                                        <td><strong><?= isset($mantenimiento['costo']) && $mantenimiento['costo'] !== null ? mant_money($mantenimiento['costo'], 2) : '-' ?></strong></td>
                                        <td>
                                            <span class="mant-chip" style="background: <?= $state['bg'] ?>; color: <?= $state['text'] ?>;">
                                                <span class="mant-status-dot" style="background: <?= $state['dot'] ?>;"></span>
                                                <?= mant_safe($state['label']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="mant-empty">
                        <div>
                            <i class="fas fa-tools"></i>
                            <strong>No hay registros en el periodo seleccionado</strong>
                            <p>Cuando existan mantenimientos, aparecerán aquí.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>

<script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
const mantTipoLabels = <?= json_encode($tiposLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantTipoData = <?= json_encode($tiposData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantTipoColors = <?= json_encode($tiposColors, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantMesLabels = <?= json_encode($mesLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantMesData = <?= json_encode($mesData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantMesCostos = <?= json_encode($mesCostos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const mantIsMobile = window.innerWidth < 768;

function mantMoney(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Number(value || 0));
}

function mantSetPeriodo(days) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - days);

    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];

    const form = document.getElementById('mantFiltrosForm') || document.querySelector('form');
    if (form) {
        form.submit();
    }
}

function setMantPeriodo(days) {
    mantSetPeriodo(days);
}

function mantChartBase(extraOptions = {}) {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    boxWidth: 10,
                    boxHeight: 10,
                    usePointStyle: true,
                    color: '#475569',
                    font: { size: mantIsMobile ? 10 : 11, weight: '700' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, .94)',
                padding: 11,
                cornerRadius: 10,
                titleFont: { size: mantIsMobile ? 11 : 12, weight: '800' },
                bodyFont: { size: mantIsMobile ? 10 : 11, weight: '650' }
            }
        },
        ...extraOptions
    };
}

function mantCreateCharts() {
    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    Chart.defaults.color = '#64748B';
    Chart.defaults.borderColor = 'rgba(27, 39, 70, .08)';

    const trendCanvas = document.getElementById('chartTend');
    if (trendCanvas) {
        new Chart(trendCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: mantMesLabels,
                datasets: [{
                    label: 'Mantenimientos',
                    data: mantMesData,
                    backgroundColor: 'rgba(189, 148, 65, .82)',
                    borderRadius: 9,
                    maxBarThickness: 44
                }, {
                    label: 'Costo',
                    data: mantMesCostos,
                    type: 'line',
                    yAxisID: 'costos',
                    borderColor: '#16824E',
                    backgroundColor: 'rgba(22, 130, 78, .14)',
                    borderWidth: 2,
                    pointRadius: 3,
                    tension: .36
                }]
            },
            options: mantChartBase({
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(27, 39, 70, .07)' },
                        ticks: { precision: 0 }
                    },
                    costos: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { display: false },
                        ticks: { callback: mantMoney }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: mantIsMobile ? 35 : 0 }
                    }
                }
            })
        });
    }

    const donutCanvas = document.getElementById('chartDonut');
    if (donutCanvas) {
        new Chart(donutCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: mantTipoLabels,
                datasets: [{
                    data: mantTipoData,
                    backgroundColor: mantTipoColors,
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: mantChartBase({
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            color: '#475569',
                            font: { size: mantIsMobile ? 10 : 11, weight: '700' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, .94)',
                        padding: 11,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((sum, item) => sum + Number(item || 0), 0);
                                const value = Number(context.parsed || 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                return context.label + ': ' + value.toLocaleString('es-MX') + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            })
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.mant-report-view');
    if (view) {
        requestAnimationFrame(() => view.classList.add('loaded'));
    }

    setTimeout(() => {
        document.querySelectorAll('.mant-progress span').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            requestAnimationFrame(() => {
                bar.style.width = width;
            });
        });
    }, 220);

    mantCreateCharts();
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
