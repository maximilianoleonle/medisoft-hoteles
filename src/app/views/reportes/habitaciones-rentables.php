<?php
$rentabilidad = $rentabilidad ?? [];
$ocupacionPorTipo = $ocupacionPorTipo ?? [];
$ingresosPromedio = $ingresosPromedio ?? [];
$fecha_inicio = !empty($fecha_inicio) ? $fecha_inicio : date('Y-m-01');
$fecha_fin = !empty($fecha_fin) ? $fecha_fin : date('Y-m-d');

if (!function_exists('rentables_safe')) {
    function rentables_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rentables_money')) {
    function rentables_money($amount, $decimals = 0) {
        return '$' . number_format((float)($amount ?? 0), $decimals);
    }
}

if (!function_exists('rentables_short_money')) {
    function rentables_short_money($amount) {
        $amount = (float)($amount ?? 0);
        if ($amount >= 1000000) {
            return '$' . number_format($amount / 1000000, 1) . 'M';
        }
        if ($amount >= 1000) {
            return '$' . number_format($amount / 1000, 0) . 'k';
        }
        return rentables_money($amount);
    }
}

if (!function_exists('rentables_date')) {
    function rentables_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

$habitacionesConIngresos = array_values(array_filter($rentabilidad, function($habitacion) {
    return (float)($habitacion['ingresos_totales'] ?? 0) > 0;
}));

$total_general = array_sum(array_map(function($habitacion) {
    return (float)($habitacion['ingresos_totales'] ?? 0);
}, $rentabilidad));

$total_reservaciones = array_sum(array_map(function($habitacion) {
    return (int)($habitacion['total_reservaciones'] ?? 0);
}, $rentabilidad));

$total_dias_ocupados = array_sum(array_map(function($habitacion) {
    return (int)($habitacion['dias_ocupada'] ?? 0);
}, $rentabilidad));

$promedio_ocupacion = count($rentabilidad) > 0
    ? array_sum(array_map(function($habitacion) {
        return (float)($habitacion['porcentaje_ocupacion'] ?? 0);
    }, $rentabilidad)) / count($rentabilidad)
    : 0;

$mejor_habitacion = $habitacionesConIngresos[0] ?? null;
$top_habitaciones = array_slice($habitacionesConIngresos, 0, 10);
$top_podium = array_slice($habitacionesConIngresos, 0, 3);
$mejor_tipo = $ocupacionPorTipo[0] ?? null;
$habitaciones_baja_ocupacion = array_values(array_filter($rentabilidad, function($habitacion) {
    return (float)($habitacion['porcentaje_ocupacion'] ?? 0) < 50;
}));

$habitacion_mas_rentable_por_dia = null;
$max_ingreso_diario = 0;
foreach ($rentabilidad as $habitacion) {
    $dias = (float)($habitacion['dias_ocupada'] ?? 0);
    if ($dias <= 0) {
        continue;
    }

    $ingreso_diario = (float)($habitacion['ingresos_totales'] ?? 0) / $dias;
    if ($ingreso_diario > $max_ingreso_diario) {
        $max_ingreso_diario = $ingreso_diario;
        $habitacion_mas_rentable_por_dia = $habitacion;
    }
}

$periodo_texto = rentables_date($fecha_inicio, 'd/m') . ' - ' . rentables_date($fecha_fin, 'd/m/Y');
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Manrope:wght@400;500;600;700&display=swap');
</style><style>
.rentables-view {
    --rent-primary: var(--brand-primary, #1B2746);
    --rent-secondary: var(--brand-secondary, #0F172A);
    --rent-accent: var(--brand-accent, #BD9441);
    --rent-action: var(--brand-action-bg, var(--rent-primary));
    --rent-action-hover: var(--brand-action-bg-hover, var(--rent-secondary));
    --rent-on-action: var(--brand-action-text, #FFFEFB);
    --rent-bg: color-mix(in srgb, var(--rent-accent) 8%, #F6F1E8);
    --rent-surface: color-mix(in srgb, var(--rent-accent) 3%, #FFFDF8);
    --rent-soft: color-mix(in srgb, var(--rent-primary) 5%, #FFFDF8);
    --rent-line: color-mix(in srgb, var(--rent-primary) 13%, #E8DCCA);
    --rent-line-soft: color-mix(in srgb, var(--rent-primary) 8%, #F1E8DA);
    --rent-text: #17233E;
    --rent-muted: #748096;
    --rent-good: #16824E;
    --rent-warn: #B7791F;
    --rent-bad: #B94A48;
    --rent-card-shadow: 0 18px 44px -36px rgba(15, 23, 42, .52);
    min-height: 100vh;
    background:
        radial-gradient(circle at 86% 4%, color-mix(in srgb, var(--rent-accent) 24%, transparent), transparent 30rem),
        linear-gradient(135deg, color-mix(in srgb, var(--rent-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, var(--rent-bg), #FBFAF7 52%, #F1EAE0);
    color: var(--rent-text);
    opacity: 0;
    transition: opacity .24s ease;
}

.rentables-view.loaded {
    opacity: 1;
}

.rent-shell {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px clamp(34px, 4vw, 76px) 50px;
    box-sizing: border-box;
}

.rent-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}

.rent-hero-main,
.rent-filter-panel,
.rent-metric,
.rent-panel,
.rent-type-card,
.rent-insight {
    border: 1px solid var(--rent-line);
    background: var(--rent-surface);
    box-shadow: var(--rent-card-shadow);
}

.rent-hero-main {
    position: relative;
    min-height: 325px;
    overflow: hidden;
    border-radius: 28px;
    padding: clamp(24px, 4vw, 44px);
    border-color: color-mix(in srgb, var(--rent-accent) 28%, transparent);
    background:
        radial-gradient(circle at 86% 18%, color-mix(in srgb, var(--rent-accent) 30%, transparent), transparent 22rem),
        linear-gradient(135deg,
            color-mix(in srgb, var(--rent-action) 54%, #101827),
            color-mix(in srgb, var(--rent-action-hover) 58%, #060A12)
        );
}

.rent-hero-main::after {
    content: "";
    position: absolute;
    inset: auto -10% -54% 46%;
    height: 220px;
    background: radial-gradient(circle, color-mix(in srgb, var(--rent-accent) 42%, transparent), transparent 67%);
    pointer-events: none;
}

.rent-back {
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
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.rent-back:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, .32);
    background: rgba(255, 255, 255, .14);
}

.rent-kicker,
.rent-label,
.rent-section-kicker,
.rent-table th,
.rent-mini-label {
    color: var(--rent-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.rent-kicker {
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

.rent-kicker i {
    color: color-mix(in srgb, var(--rent-accent) 42%, #FFFFFF);
}

.rent-hero-main h1 {
    position: relative;
    z-index: 1;
    max-width: 12ch;
    margin: 14px 0 14px;
    color: var(--rent-on-action);
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(2.4rem, 5vw, 5.15rem);
    font-weight: 700;
    line-height: .9;
    letter-spacing: 0;
    text-wrap: balance;
}

.rent-hero-main p {
    position: relative;
    z-index: 1;
    max-width: 68ch;
    margin: 0;
    color: color-mix(in srgb, var(--rent-on-action) 82%, transparent);
    font-weight: 500;
    line-height: 1.6;
}

.rent-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.rent-btn,
.rent-period-btn {
    min-height: 42px;
    border-radius: 13px;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.rent-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--rent-accent) 45%, var(--rent-primary));
    background: var(--rent-action);
    color: var(--rent-on-action);
    font-weight: 700;
    text-decoration: none;
}

.rent-btn.is-accent {
    border-color: color-mix(in srgb, var(--rent-action) 34%, var(--rent-accent));
    background: var(--rent-action);
    color: var(--rent-on-action);
}

.rent-hero-main .rent-btn.is-accent {
    border-color: rgba(255, 255, 255, .9);
    background: #FFFFFF;
    color: color-mix(in srgb, var(--rent-action) 78%, #05070D);
}

.rent-hero-main .rent-btn.is-accent i {
    color: color-mix(in srgb, var(--rent-accent) 48%, var(--rent-action));
}

.rent-btn.is-soft {
    border-color: rgba(255, 255, 255, .22);
    background: rgba(255, 255, 255, .1);
    color: #FFFDF8;
}

.rent-btn:hover,
.rent-period-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 32px -25px rgba(15, 23, 42, .58);
}

.rent-filter-panel {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 18px;
    border-radius: 25px;
    padding: 20px;
}

.rent-filter-top {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    align-items: flex-start;
}

.rent-period-value {
    margin-top: 7px;
    color: var(--rent-primary);
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.5rem, 3vw, 2.35rem);
    font-weight: 700;
    line-height: 1;
}

.rent-filter-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.rent-field label {
    display: block;
    margin-bottom: 6px;
    color: var(--rent-primary);
    font-size: .75rem;
    font-weight: 700;
}

.rent-field input {
    width: 100%;
    min-height: 43px;
    border: 1px solid var(--rent-line);
    border-radius: 13px;
    background: color-mix(in srgb, var(--rent-accent) 3%, #FFFDF8);
    color: var(--rent-primary);
    font-size: .92rem;
    font-weight: 600;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.rent-field input:focus {
    border-color: color-mix(in srgb, var(--rent-accent) 62%, var(--rent-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rent-accent) 22%, transparent);
}

.rent-filter-form .rent-btn {
    grid-column: 1 / -1;
}

.rent-periods {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.rent-period-btn {
    border: 1px solid var(--rent-line-soft);
    background: color-mix(in srgb, var(--rent-primary) 4%, #FFFDF8);
    color: var(--rent-primary);
    font-size: .76rem;
    font-weight: 700;
}

.rent-metrics {
    display: grid;
    grid-template-columns: minmax(260px, 1.2fr) repeat(3, minmax(170px, .95fr));
    gap: 14px;
    margin-bottom: 18px;
}

.rent-metric {
    min-width: 0;
    overflow: hidden;
    border-radius: 21px;
    padding: 18px;
}

.rent-metric.is-featured {
    background:
        radial-gradient(circle at 92% 8%, color-mix(in srgb, var(--rent-accent) 18%, transparent), transparent 12rem),
        linear-gradient(135deg, color-mix(in srgb, var(--rent-accent) 10%, #FFFDF8), var(--rent-surface));
}

.rent-metric-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.rent-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--rent-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--rent-accent) 82%, var(--rent-primary));
}

.rent-value {
    margin-top: 13px;
    color: var(--rent-primary);
    font-size: clamp(1.45rem, 2.8vw, 2.2rem);
    font-weight: 700;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.rent-note {
    margin-top: 9px;
    color: var(--rent-muted);
    font-size: .8rem;
    font-weight: 600;
}

.rent-layout {
    display: grid;
    grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
    gap: 18px;
    align-items: start;
    margin-bottom: 18px;
}

.rent-panel {
    border-radius: 22px;
    overflow: hidden;
}

.rent-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--rent-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--rent-accent) 6%, #FFFDF8), var(--rent-surface));
}

.rent-section-head h2,
.rent-section-head h3 {
    margin: 0;
    color: var(--rent-primary);
    font-size: 1.05rem;
    font-weight: 700;
}

.rent-section-body {
    padding: 18px 20px 20px;
}

.rent-podium {
    display: grid;
    gap: 12px;
}

.rent-podium-item {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 13px;
    align-items: center;
    padding: 14px;
    overflow: hidden;
    border: 1px solid var(--rent-line-soft);
    border-radius: 18px;
    background: color-mix(in srgb, var(--rent-accent) 3%, #FFFDF8);
}

.rent-podium-item::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: var(--rent-accent);
}

.rent-podium-item:first-child {
    background:
        radial-gradient(circle at 92% 12%, color-mix(in srgb, var(--rent-accent) 18%, transparent), transparent 11rem),
        color-mix(in srgb, var(--rent-accent) 7%, #FFFDF8);
}

.rent-rank {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--rent-primary) 8%, #FFFDF8);
    color: var(--rent-primary);
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.rent-podium-item:first-child .rent-rank {
    background: color-mix(in srgb, var(--rent-accent) 22%, #FFFDF8);
    color: color-mix(in srgb, var(--rent-primary) 88%, #000);
}

.rent-room-title {
    display: block;
    color: var(--rent-primary);
    font-size: 1rem;
    font-weight: 700;
}

.rent-room-meta {
    display: block;
    margin-top: 3px;
    color: var(--rent-muted);
    font-size: .8rem;
    font-weight: 600;
}

.rent-podium-money {
    color: var(--rent-good);
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.rent-chart-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.rent-chart-box {
    min-height: 320px;
    padding: 16px;
    border: 1px solid var(--rent-line-soft);
    border-radius: 18px;
    background: color-mix(in srgb, var(--rent-primary) 3%, #FFFDF8);
}

.rent-chart-box.is-wide {
    grid-column: 1 / -1;
}

.rent-chart-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 12px;
}

.rent-chart-title strong {
    color: var(--rent-primary);
    font-size: .94rem;
    font-weight: 700;
}

.rent-chart-canvas {
    position: relative;
    height: 265px;
}

.rent-table-wrap {
    overflow-x: auto;
    max-height: 520px;
}

.rent-table {
    width: 100%;
    min-width: 860px;
    border-collapse: separate;
    border-spacing: 0;
}

.rent-table th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 12px 11px;
    border-bottom: 1px solid var(--rent-line);
    background: color-mix(in srgb, var(--rent-accent) 7%, #FFFDF8);
    text-align: left;
}

.rent-table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--rent-line-soft);
    color: var(--rent-text);
    font-size: .86rem;
}

.rent-table tbody tr {
    transition: background .18s ease;
}

.rent-table tbody tr:hover {
    background: color-mix(in srgb, var(--rent-accent) 5%, #FFFDF8);
}

.rent-room-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.rent-room-mark {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: var(--rent-primary);
    color: #FFFDF8;
    font-size: .75rem;
    font-weight: 700;
}

.rent-share {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 58px;
    padding: 5px 8px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--rent-accent) 13%, #FFFDF8);
    color: var(--rent-primary);
    font-size: .74rem;
    font-weight: 700;
}

.rent-occupancy {
    min-width: 116px;
}

.rent-occupancy-line {
    height: 7px;
    margin-top: 6px;
    overflow: hidden;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rent-primary) 8%, #FFFDF8);
}

.rent-occupancy-line span {
    display: block;
    height: 100%;
    border-radius: inherit;
    background: var(--rent-good);
    transition: width .75s ease;
}

.rent-types-grid,
.rent-insights-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.rent-type-card,
.rent-insight {
    min-width: 0;
    border-radius: 20px;
    padding: 17px;
}

.rent-type-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 14px;
}

.rent-type-head h4,
.rent-insight h4 {
    margin: 0;
    color: var(--rent-primary);
    font-size: .98rem;
    font-weight: 700;
}

.rent-pill {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rent-primary) 7%, #FFFDF8);
    color: var(--rent-primary);
    font-size: .73rem;
    font-weight: 700;
    white-space: nowrap;
}

.rent-type-stats {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.rent-mini-value {
    display: block;
    margin-top: 4px;
    color: var(--rent-primary);
    font-size: 1rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.rent-insight {
    background:
        radial-gradient(circle at 92% 12%, color-mix(in srgb, var(--rent-accent) 13%, transparent), transparent 10rem),
        var(--rent-surface);
}

.rent-insight p {
    margin: 10px 0 0;
    color: var(--rent-muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.55;
}

.rent-empty {
    display: grid;
    place-items: center;
    min-height: 240px;
    padding: 34px;
    color: var(--rent-muted);
    text-align: center;
}

.rent-empty i {
    width: 64px;
    height: 64px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 22px;
    background: color-mix(in srgb, var(--rent-accent) 12%, #FFFDF8);
    color: color-mix(in srgb, var(--rent-accent) 80%, var(--rent-primary));
    font-size: 1.55rem;
}

.rent-empty strong {
    display: block;
    color: var(--rent-primary);
    font-size: 1rem;
    font-weight: 700;
}

@media (max-width: 1220px) {
    .rent-hero,
    .rent-layout {
        grid-template-columns: 1fr;
    }

    .rent-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rent-metric.is-featured {
        grid-column: 1 / -1;
    }
}

@media (max-width: 980px) {
    .rent-chart-grid,
    .rent-types-grid,
    .rent-insights-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .rent-shell {
        width: 100%;
        padding: 18px 12px 36px;
    }

    .rent-hero-main,
    .rent-filter-panel,
    .rent-metric,
    .rent-panel,
    .rent-type-card,
    .rent-insight {
        border-radius: 18px;
    }

    .rent-hero-main {
        min-height: auto;
        padding: 22px;
    }

    .rent-kicker {
        margin-top: 20px;
    }

    .rent-hero-main h1 {
        max-width: 10ch;
        font-size: clamp(2.1rem, 14vw, 3.55rem);
    }

    .rent-filter-form,
    .rent-periods,
    .rent-metrics,
    .rent-type-stats {
        grid-template-columns: 1fr;
    }

    .rent-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .rent-podium-item {
        grid-template-columns: auto minmax(0, 1fr);
    }

    .rent-podium-money {
        grid-column: 2;
        text-align: left;
    }

    .rent-chart-canvas {
        height: 245px;
    }

    .rent-table {
        min-width: 740px;
    }
}

@media print {
    .rentables-view {
        background: #fff !important;
    }

    .rent-filter-panel,
    .rent-hero-actions,
    .rent-back,
    .rentables-view button {
        display: none !important;
    }

    .rent-shell {
        width: 100%;
        padding: 0;
    }

    .rent-panel,
    .rent-metric {
        box-shadow: none !important;
    }
}
</style>

<div class="rentables-view">
    <main class="rent-shell">
        <section class="rent-hero">
            <div class="rent-hero-main">
                <a href="<?= back_url('reportes') ?>" class="rent-back">
                    <i class="fas fa-arrow-left"></i>
                    Reportes
                </a>

                <div class="rent-kicker">
                    <i class="fas fa-chart-line"></i>
                    Habitaciones más rentables
                </div>

                <h1>Mapa de rendimiento por habitación</h1>
                <p>
                    Lectura operativa de ingresos, ocupación y precio promedio por habitación para detectar las unidades que más empujan la venta del hotel.
                </p>

                <div class="rent-hero-actions">
                    <button type="button" onclick="exportarPDF()" class="rent-btn is-accent">
                        <i class="fas fa-file-pdf"></i>
                        Exportar PDF
                    </button>
                    <a href="#rankingRentabilidad" class="rent-btn is-soft">
                        <i class="fas fa-list-ol"></i>
                        Ver ranking
                    </a>
                </div>
            </div>

            <aside class="rent-filter-panel">
                <div class="rent-filter-top">
                    <div>
                        <span class="rent-label">Periodo analizado</span>
                        <div class="rent-period-value"><?= $periodo_texto ?></div>
                    </div>
                    <span class="rent-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </span>
                </div>

                <form method="GET" action="<?= url('reportes/habitaciones-rentables') ?>" class="rent-filter-form" id="rentablesFiltrosForm" data-auto-filter-form>
                    <div class="rent-field">
                        <label for="fecha_inicio">Desde</label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               value="<?= rentables_safe($fecha_inicio, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="rent-field">
                        <label for="fecha_fin">Hasta</label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               value="<?= rentables_safe($fecha_fin, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>

                    <button type="submit" class="rent-btn">
                        <i class="fas fa-filter"></i>
                        Aplicar filtros
                    </button>
                </form>

                <div>
                    <div class="rent-periods">
                        <button type="button" onclick="setPeriodo(30)" class="rent-period-btn">1M</button>
                        <button type="button" onclick="setPeriodo(90)" class="rent-period-btn">3M</button>
                        <button type="button" onclick="setPeriodo(180)" class="rent-period-btn">6M</button>
                        <button type="button" onclick="setPeriodo(365)" class="rent-period-btn">1A</button>
                    </div>
                </div>
            </aside>
        </section>

        <section class="rent-metrics" aria-label="Resumen de rentabilidad">
            <article class="rent-metric is-featured">
                <div class="rent-metric-head">
                    <span class="rent-label">Ingresos del periodo</span>
                    <span class="rent-icon"><i class="fas fa-dollar-sign"></i></span>
                </div>
                <div class="rent-value"><?= rentables_money($total_general) ?></div>
                <div class="rent-note">
                    <?= number_format($total_reservaciones) ?> reservaciones completadas en <?= number_format($total_dias_ocupados) ?> días ocupados.
                </div>
            </article>

            <article class="rent-metric">
                <div class="rent-metric-head">
                    <span class="rent-label">Reservaciones</span>
                    <span class="rent-icon"><i class="fas fa-calendar-check"></i></span>
                </div>
                <div class="rent-value"><?= number_format($total_reservaciones) ?></div>
                <div class="rent-note">Habitaciones con venta registrada: <?= number_format(count($habitacionesConIngresos)) ?></div>
            </article>

            <article class="rent-metric">
                <div class="rent-metric-head">
                    <span class="rent-label">Ocupación promedio</span>
                    <span class="rent-icon"><i class="fas fa-percentage"></i></span>
                </div>
                <div class="rent-value"><?= number_format($promedio_ocupacion, 1) ?>%</div>
                <div class="rent-note"><?= number_format(count($habitaciones_baja_ocupacion)) ?> habitaciones debajo del 50%.</div>
            </article>

            <article class="rent-metric">
                <div class="rent-metric-head">
                    <span class="rent-label">Líder actual</span>
                    <span class="rent-icon"><i class="fas fa-crown"></i></span>
                </div>
                <div class="rent-value">
                    <?= $mejor_habitacion ? 'Hab. ' . rentables_safe($mejor_habitacion['numero']) : 'N/A' ?>
                </div>
                <div class="rent-note">
                    <?= $mejor_habitacion ? rentables_money($mejor_habitacion['ingresos_totales']) . ' generados' : 'Sin datos en el periodo.' ?>
                </div>
            </article>
        </section>

        <section class="rent-layout">
            <article class="rent-panel">
                <div class="rent-section-head">
                    <div>
                        <span class="rent-section-kicker">Top de habitaciones</span>
                        <h2>Podio de ingreso real</h2>
                    </div>
                    <span class="rent-pill"><?= number_format(count($habitacionesConIngresos)) ?> con ingresos</span>
                </div>
                <div class="rent-section-body">
                    <?php if (!empty($top_podium)): ?>
                        <div class="rent-podium">
                            <?php foreach ($top_podium as $index => $habitacion): ?>
                                <?php
                                $dias = max((float)($habitacion['dias_ocupada'] ?? 0), 1);
                                $ingresoDia = (float)($habitacion['ingresos_totales'] ?? 0) / $dias;
                                ?>
                                <div class="rent-podium-item">
                                    <span class="rent-rank">#<?= $index + 1 ?></span>
                                    <div>
                                        <strong class="rent-room-title">Habitación <?= rentables_safe($habitacion['numero']) ?></strong>
                                        <span class="rent-room-meta">
                                            <?= rentables_safe($habitacion['tipo']) ?> · Piso <?= rentables_safe($habitacion['piso']) ?> · <?= number_format((float)($habitacion['porcentaje_ocupacion'] ?? 0), 1) ?>% ocupación
                                        </span>
                                    </div>
                                    <div class="rent-podium-money">
                                        <?= rentables_money($habitacion['ingresos_totales']) ?>
                                        <span class="rent-room-meta"><?= rentables_money($ingresoDia) ?>/día</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rent-empty">
                            <div>
                                <i class="fas fa-bed"></i>
                                <strong>Sin habitaciones rentables en este periodo</strong>
                                <p>Cambia el rango de fechas para consultar habitaciones con ingresos registrados.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="rent-panel">
                <div class="rent-section-head">
                    <div>
                        <span class="rent-section-kicker">Visualización</span>
                        <h2>Ingresos y ocupación</h2>
                    </div>
                    <span class="rent-pill">Top 10</span>
                </div>
                <div class="rent-section-body">
                    <div class="rent-chart-grid">
                        <div class="rent-chart-box">
                            <div class="rent-chart-title">
                                <strong>Mayores ingresos</strong>
                                <span class="rent-mini-label">MXN</span>
                            </div>
                            <div class="rent-chart-canvas">
                                <canvas id="chartIngresosHabitacion"></canvas>
                            </div>
                        </div>

                        <div class="rent-chart-box">
                            <div class="rent-chart-title">
                                <strong>% ocupación</strong>
                                <span class="rent-mini-label">Top</span>
                            </div>
                            <div class="rent-chart-canvas">
                                <canvas id="chartOcupacion"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="rent-panel" id="rankingRentabilidad">
            <div class="rent-section-head">
                <div>
                    <span class="rent-section-kicker">Ranking completo</span>
                    <h2>Rentabilidad por habitación</h2>
                </div>
                <span class="rent-pill"><?= number_format(count($rentabilidad)) ?> registros</span>
            </div>
            <div class="rent-section-body">
                <?php if (!empty($habitacionesConIngresos)): ?>
                    <div class="rent-table-wrap">
                        <table class="rent-table">
                            <thead>
                                <tr>
                                    <th style="width: 58px;">#</th>
                                    <th>Habitación</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Piso</th>
                                    <th class="text-center">Res.</th>
                                    <th class="text-center">Días</th>
                                    <th class="text-right">Ingresos</th>
                                    <th class="text-right">$/Día</th>
                                    <th>Ocupación</th>
                                    <th class="text-center">% ingresos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($habitacionesConIngresos as $index => $habitacion): ?>
                                    <?php
                                    $ingresos = (float)($habitacion['ingresos_totales'] ?? 0);
                                    $dias = max((float)($habitacion['dias_ocupada'] ?? 0), 1);
                                    $ocupacion = (float)($habitacion['porcentaje_ocupacion'] ?? 0);
                                    $participacion = $total_general > 0 ? ($ingresos / $total_general) * 100 : 0;
                                    $ocupacionColor = $ocupacion >= 80 ? 'var(--rent-good)' : ($ocupacion >= 60 ? 'var(--rent-warn)' : 'var(--rent-bad)');
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="rent-rank">#<?= $index + 1 ?></span>
                                        </td>
                                        <td>
                                            <div class="rent-room-cell">
                                                <span class="rent-room-mark"><?= rentables_safe($habitacion['numero']) ?></span>
                                                <div>
                                                    <strong class="rent-room-title">Hab. <?= rentables_safe($habitacion['numero']) ?></strong>
                                                    <span class="rent-room-meta"><?= rentables_safe($habitacion['tipo']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= rentables_safe($habitacion['tipo']) ?></td>
                                        <td class="text-center"><?= rentables_safe($habitacion['piso']) ?></td>
                                        <td class="text-center font-bold"><?= number_format((int)($habitacion['total_reservaciones'] ?? 0)) ?></td>
                                        <td class="text-center"><?= number_format((int)($habitacion['dias_ocupada'] ?? 0)) ?></td>
                                        <td class="text-right font-bold text-emerald-700">
                                            <span class="hidden sm:inline"><?= rentables_money($ingresos) ?></span>
                                            <span class="sm:hidden"><?= rentables_short_money($ingresos) ?></span>
                                        </td>
                                        <td class="text-right"><?= rentables_money($ingresos / $dias) ?></td>
                                        <td>
                                            <div class="rent-occupancy">
                                                <strong><?= number_format($ocupacion, 1) ?>%</strong>
                                                <div class="rent-occupancy-line">
                                                    <span class="rent-occupancy-fill" style="width: <?= min(100, max(0, $ocupacion)) ?>%; background: <?= $ocupacionColor ?>;"></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="rent-share"><?= number_format($participacion, 1) ?>%</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="rent-empty">
                        <div>
                            <i class="fas fa-chart-bar"></i>
                            <strong>No hay ingresos para mostrar</strong>
                            <p>El reporte conserva los filtros actuales; prueba con un rango más amplio.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="rent-layout">
            <article class="rent-panel">
                <div class="rent-section-head">
                    <div>
                        <span class="rent-section-kicker">Tipos de habitación</span>
                        <h2>Distribución y precio</h2>
                    </div>
                </div>
                <div class="rent-section-body">
                    <div class="rent-chart-grid">
                        <div class="rent-chart-box">
                            <div class="rent-chart-title">
                                <strong>Ingresos por tipo</strong>
                                <span class="rent-mini-label">Mix</span>
                            </div>
                            <div class="rent-chart-canvas">
                                <canvas id="chartDistribucionTipo"></canvas>
                            </div>
                        </div>

                        <div class="rent-chart-box">
                            <div class="rent-chart-title">
                                <strong>Comparación de precios</strong>
                                <span class="rent-mini-label">Min / prom / max</span>
                            </div>
                            <div class="rent-chart-canvas">
                                <canvas id="chartComparacionPrecios"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            <article class="rent-panel">
                <div class="rent-section-head">
                    <div>
                        <span class="rent-section-kicker">Rendimiento por categoría</span>
                        <h2>Lectura por tipo</h2>
                    </div>
                </div>
                <div class="rent-section-body">
                    <?php if (!empty($ocupacionPorTipo)): ?>
                        <div class="rent-types-grid">
                            <?php foreach ($ocupacionPorTipo as $tipo): ?>
                                <?php $porcentajeTipo = $total_general > 0 ? ((float)($tipo['ingresos_totales'] ?? 0) / $total_general) * 100 : 0; ?>
                                <article class="rent-type-card">
                                    <div class="rent-type-head">
                                        <h4><?= rentables_safe($tipo['tipo']) ?></h4>
                                        <span class="rent-pill"><?= number_format((int)($tipo['total_habitaciones'] ?? 0)) ?> hab.</span>
                                    </div>
                                    <div class="rent-type-stats">
                                        <div>
                                            <span class="rent-mini-label">Ingresos</span>
                                            <strong class="rent-mini-value"><?= rentables_money($tipo['ingresos_totales']) ?></strong>
                                        </div>
                                        <div>
                                            <span class="rent-mini-label">Precio prom.</span>
                                            <strong class="rent-mini-value"><?= rentables_money($tipo['precio_promedio']) ?></strong>
                                        </div>
                                        <div>
                                            <span class="rent-mini-label">Días ocup.</span>
                                            <strong class="rent-mini-value"><?= number_format((int)($tipo['dias_ocupadas'] ?? 0)) ?></strong>
                                        </div>
                                        <div>
                                            <span class="rent-mini-label">% total</span>
                                            <strong class="rent-mini-value"><?= number_format($porcentajeTipo, 1) ?>%</strong>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="rent-empty">
                            <div>
                                <i class="fas fa-layer-group"></i>
                                <strong>Sin desglose por tipo</strong>
                                <p>No hay datos suficientes para agrupar habitaciones por categoría.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <section class="rent-panel">
            <div class="rent-section-head">
                <div>
                    <span class="rent-section-kicker">Lectura ejecutiva</span>
                    <h2>Hallazgos del periodo</h2>
                </div>
                <span class="rent-pill">Operación</span>
            </div>
            <div class="rent-section-body">
                <div class="rent-insights-grid">
                    <article class="rent-insight">
                        <span class="rent-icon"><i class="fas fa-star"></i></span>
                        <h4>Tipo más rentable</h4>
                        <p>
                            <?php if ($mejor_tipo && $total_general > 0): ?>
                                <strong><?= rentables_safe($mejor_tipo['tipo']) ?></strong> concentra el
                                <strong><?= number_format(((float)$mejor_tipo['ingresos_totales'] / $total_general) * 100, 1) ?>%</strong>
                                de los ingresos del periodo.
                            <?php else: ?>
                                Aún no hay datos suficientes para definir un tipo líder.
                            <?php endif; ?>
                        </p>
                    </article>

                    <article class="rent-insight">
                        <span class="rent-icon"><i class="fas fa-coins"></i></span>
                        <h4>Mayor ingreso diario</h4>
                        <p>
                            <?php if ($habitacion_mas_rentable_por_dia): ?>
                                <strong>Hab. <?= rentables_safe($habitacion_mas_rentable_por_dia['numero']) ?></strong>
                                alcanza <strong><?= rentables_money($max_ingreso_diario) ?>/día</strong>
                                en el rango consultado.
                            <?php else: ?>
                                No hay días ocupados suficientes para calcular ingreso diario.
                            <?php endif; ?>
                        </p>
                    </article>

                    <article class="rent-insight">
                        <span class="rent-icon"><i class="fas fa-bullseye"></i></span>
                        <h4>Oportunidad de ocupación</h4>
                        <p>
                            <strong><?= number_format(count($habitaciones_baja_ocupacion)) ?> habitaciones</strong>
                            aparecen con menos del 50% de ocupación en este periodo.
                        </p>
                    </article>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const rentabilidadData = <?= json_encode($rentabilidad, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const ocupacionTipoData = <?= json_encode($ocupacionPorTipo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const preciosTipoData = <?= json_encode($ingresosPromedio, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const isMobile = window.innerWidth < 768;
const habitacionesConIngresos = rentabilidadData.filter(h => Number(h.ingresos_totales || 0) > 0);
const top10Habitaciones = habitacionesConIngresos.slice(0, 10);
const chartPalette = ['#BD9441', '#16824E', '#3B83BD', '#A46B3F', '#6E7CA8', '#9CA66A', '#C97362', '#5F7F7A', '#D0AA69', '#7B6FA6'];

function moneyTick(value) {
    const amount = Number(value || 0);
    if (amount >= 1000000) {
        return '$' + (amount / 1000000).toFixed(1) + 'M';
    }
    if (amount >= 1000) {
        return '$' + Math.round(amount / 1000) + 'k';
    }
    return '$' + amount.toLocaleString('es-MX');
}

function moneyFull(value) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(Number(value || 0));
}

function chartBaseOptions(extraOptions = {}) {
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
                    font: { size: isMobile ? 10 : 11, weight: '700' }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(15, 23, 42, .94)',
                padding: 11,
                cornerRadius: 10,
                titleFont: { size: isMobile ? 11 : 12, weight: '800' },
                bodyFont: { size: isMobile ? 10 : 11, weight: '650' }
            }
        },
        ...extraOptions
    };
}

function createCharts() {
    if (typeof Chart === 'undefined') {
        return;
    }

    Chart.defaults.font.family = 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    Chart.defaults.color = '#64748B';
    Chart.defaults.borderColor = 'rgba(27, 39, 70, .08)';

    const ingresosCanvas = document.getElementById('chartIngresosHabitacion');
    if (ingresosCanvas) {
        new Chart(ingresosCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: top10Habitaciones.map(h => 'Hab. ' + h.numero),
                datasets: [{
                    label: 'Ingresos',
                    data: top10Habitaciones.map(h => Number(h.ingresos_totales || 0)),
                    backgroundColor: top10Habitaciones.map((h, index) => chartPalette[index % chartPalette.length]),
                    borderWidth: 0,
                    borderRadius: 9,
                    maxBarThickness: 42
                }]
            },
            options: chartBaseOptions({
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, .94)',
                        padding: 11,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                const habitacion = top10Habitaciones[context.dataIndex] || {};
                                return [
                                    'Ingresos: ' + moneyFull(context.parsed.y),
                                    'Reservaciones: ' + Number(habitacion.total_reservaciones || 0).toLocaleString('es-MX'),
                                    'Ocupación: ' + Number(habitacion.porcentaje_ocupacion || 0).toFixed(1) + '%'
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(27, 39, 70, .07)' },
                        ticks: { callback: moneyTick }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: isMobile ? 45 : 0 }
                    }
                }
            })
        });
    }

    const ocupacionCanvas = document.getElementById('chartOcupacion');
    if (ocupacionCanvas) {
        new Chart(ocupacionCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: top10Habitaciones.map(h => 'Hab. ' + h.numero),
                datasets: [{
                    label: '% Ocupación',
                    data: top10Habitaciones.map(h => Number(h.porcentaje_ocupacion || 0)),
                    backgroundColor: top10Habitaciones.map(h => {
                        const pct = Number(h.porcentaje_ocupacion || 0);
                        if (pct >= 80) return '#16824E';
                        if (pct >= 60) return '#B7791F';
                        return '#B94A48';
                    }),
                    borderRadius: 9,
                    maxBarThickness: 30
                }]
            },
            options: chartBaseOptions({
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, .94)',
                        padding: 11,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                return Number(context.parsed.x || 0).toFixed(1) + '% ocupación';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(27, 39, 70, .07)' },
                        ticks: { callback: value => value + '%' }
                    },
                    y: { grid: { display: false } }
                }
            })
        });
    }

    const tipoCanvas = document.getElementById('chartDistribucionTipo');
    if (tipoCanvas) {
        new Chart(tipoCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ocupacionTipoData.map(t => t.tipo),
                datasets: [{
                    data: ocupacionTipoData.map(t => Number(t.ingresos_totales || 0)),
                    backgroundColor: ocupacionTipoData.map((item, index) => chartPalette[index % chartPalette.length]),
                    borderWidth: 0,
                    hoverOffset: 5
                }]
            },
            options: chartBaseOptions({
                cutout: '64%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            boxHeight: 10,
                            usePointStyle: true,
                            color: '#475569',
                            font: { size: isMobile ? 10 : 11, weight: '700' },
                            generateLabels: function(chart) {
                                const data = chart.data;
                                const total = data.datasets[0].data.reduce((sum, item) => sum + Number(item || 0), 0);
                                return data.labels.map((label, index) => {
                                    const value = Number(data.datasets[0].data[index] || 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';
                                    const shortLabel = isMobile && label.length > 12 ? label.substring(0, 12) + '...' : label;
                                    return {
                                        text: shortLabel + ' (' + percentage + '%)',
                                        fillStyle: data.datasets[0].backgroundColor[index % data.datasets[0].backgroundColor.length],
                                        strokeStyle: data.datasets[0].backgroundColor[index % data.datasets[0].backgroundColor.length],
                                        hidden: false,
                                        index
                                    };
                                });
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, .94)',
                        padding: 11,
                        cornerRadius: 10,
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + moneyFull(context.parsed);
                            }
                        }
                    }
                }
            })
        });
    }

    const preciosCanvas = document.getElementById('chartComparacionPrecios');
    if (preciosCanvas) {
        new Chart(preciosCanvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: preciosTipoData.map(t => isMobile && t.tipo.length > 10 ? t.tipo.substring(0, 10) + '...' : t.tipo),
                datasets: [{
                    label: 'Mínimo',
                    data: preciosTipoData.map(t => Number(t.precio_minimo || 0)),
                    backgroundColor: 'rgba(59, 131, 189, .72)',
                    borderRadius: 8
                }, {
                    label: 'Promedio',
                    data: preciosTipoData.map(t => Number(t.precio_promedio || 0)),
                    backgroundColor: 'rgba(189, 148, 65, .82)',
                    borderRadius: 8
                }, {
                    label: 'Máximo',
                    data: preciosTipoData.map(t => Number(t.precio_maximo || 0)),
                    backgroundColor: 'rgba(22, 130, 78, .72)',
                    borderRadius: 8
                }]
            },
            options: chartBaseOptions({
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(27, 39, 70, .07)' },
                        ticks: { callback: moneyTick }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: isMobile ? 35 : 0 }
                    }
                }
            })
        });
    }
}

function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);

    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];

    const form = document.getElementById('rentablesFiltrosForm') || document.querySelector('form');
    if (form) {
        form.submit();
    }
}

function exportarPDF() {
    const url = '<?= url('reportes/exportar-pdf') ?>?tipo=habitaciones-rentables' +
                '&fecha_inicio=<?= rawurlencode((string)$fecha_inicio) ?>' +
                '&fecha_fin=<?= rawurlencode((string)$fecha_fin) ?>';
    window.open(url, '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.rentables-view');
    if (view) {
        requestAnimationFrame(() => view.classList.add('loaded'));
    }

    setTimeout(() => {
        document.querySelectorAll('.rent-occupancy-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            requestAnimationFrame(() => {
                bar.style.width = width;
            });
        });
    }, 220);

    createCharts();
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
