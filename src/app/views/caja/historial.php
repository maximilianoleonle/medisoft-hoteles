<?php
/**
 * Vista de Historial de Cortes de Caja
 * Rediseño operativo tipo bitácora financiera.
 */

$cortes = $cortes ?? [];
$estadisticas = $estadisticas ?? [];
$mes = (int)($mes ?? date('m'));
$año = (int)($año ?? date('Y'));

if (!function_exists('obtener_nombre_mes')) {
    function obtener_nombre_mes($mes) {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[(int)$mes] ?? '';
    }
}

if (!function_exists('caja_hist_money')) {
    function caja_hist_money($amount, $signed = false) {
        $amount = (float)($amount ?? 0);
        $prefix = '';
        if ($amount < 0) {
            $prefix = '-';
            $amount = abs($amount);
        } elseif ($signed && $amount > 0) {
            $prefix = '+';
        }
        return $prefix . '$' . number_format($amount, 2);
    }
}

if (!function_exists('caja_hist_date')) {
    function caja_hist_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('caja_hist_duration')) {
    function caja_hist_duration($inicio, $fin) {
        if (empty($inicio) || empty($fin)) {
            return 'En curso';
        }

        $seconds = max(0, strtotime((string)$fin) - strtotime((string)$inicio));
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return $hours . 'h ' . $minutes . 'm';
    }
}

if (!function_exists('caja_hist_safe')) {
    function caja_hist_safe($value, $fallback = 'N/A') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$periodo_label = obtener_nombre_mes($mes) . ' ' . $año;
$total_cortes = count($cortes);
$ingresos_mes = (float)($estadisticas['total_ingresos'] ?? 0);
$gastos_mes = (float)($estadisticas['total_gastos'] ?? 0);
$balance_mes = $ingresos_mes - $gastos_mes;
$sobrantes_mes = (float)($estadisticas['sobrantes'] ?? 0);
$faltantes_mes = (float)($estadisticas['faltantes'] ?? 0);
$flujo_total = max(1, $ingresos_mes + $gastos_mes);
$ingresos_pct = min(100, max(0, round(($ingresos_mes / $flujo_total) * 100)));
$gastos_pct = min(100, max(0, 100 - $ingresos_pct));

$cortes_abiertos = 0;
$cortes_cerrados = 0;
$cortes_con_diferencia = 0;
foreach ($cortes as $corte_resumen) {
    $estado_resumen = $corte_resumen['estado'] ?? 'abierto';
    if ($estado_resumen === 'abierto') {
        $cortes_abiertos++;
    } else {
        $cortes_cerrados++;
    }

    if ((float)($corte_resumen['diferencia'] ?? 0) != 0.0) {
        $cortes_con_diferencia++;
    }
}

$totales_metodo = [
    'efectivo' => ['label' => 'Efectivo', 'icon' => 'fa-money-bill-wave', 'ingresos' => 0, 'gastos' => 0, 'color' => 'green'],
    'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'fa-credit-card', 'ingresos' => 0, 'gastos' => 0, 'color' => 'blue'],
    'transferencia' => ['label' => 'Transferencia', 'icon' => 'fa-exchange-alt', 'ingresos' => 0, 'gastos' => 0, 'color' => 'violet'],
];

foreach ($cortes as $corte_metodo) {
    if (($corte_metodo['estado'] ?? '') !== 'cerrado') {
        continue;
    }

    $totales_metodo['efectivo']['ingresos'] += (float)($corte_metodo['total_ingresos_efectivo'] ?? 0);
    $totales_metodo['efectivo']['gastos'] += (float)($corte_metodo['total_gastos_efectivo'] ?? 0);
    $totales_metodo['tarjeta']['ingresos'] += (float)($corte_metodo['total_ingresos_tarjeta'] ?? 0);
    $totales_metodo['tarjeta']['gastos'] += (float)($corte_metodo['total_gastos_tarjeta'] ?? 0);
    $totales_metodo['transferencia']['ingresos'] += (float)($corte_metodo['total_ingresos_transferencia'] ?? 0);
    $totales_metodo['transferencia']['gastos'] += (float)($corte_metodo['total_gastos_transferencia'] ?? 0);
}
?>

<style>
.cash-history-view {
    --ch-primary: var(--brand-primary, #1B2746);
    --ch-secondary: var(--brand-secondary, #0F172A);
    --ch-accent: var(--brand-accent, #BD9441);
    --ch-action: var(--brand-action-bg, var(--ch-primary));
    --ch-action-hover: var(--brand-action-bg-hover, color-mix(in srgb, var(--ch-action) 90%, #111827));
    --ch-on-action: var(--brand-action-text, #FFFDF8);
    --ch-bg: color-mix(in srgb, var(--ch-accent) 8%, #F6F1E8);
    --ch-surface: color-mix(in srgb, var(--ch-accent) 3%, #FFFDF8);
    --ch-surface-strong: color-mix(in srgb, var(--ch-primary) 5%, #FFFDF8);
    --ch-line: color-mix(in srgb, var(--ch-primary) 13%, #E8DCCC);
    --ch-line-soft: color-mix(in srgb, var(--ch-primary) 8%, #EFE7DB);
    --ch-text: #17233E;
    --ch-muted: #748096;
    --ch-income: #16824E;
    --ch-expense: #B93A32;
    --ch-warning: #B7791F;
    --ch-info: #2563A7;
    min-height: 100vh;
    background:
        linear-gradient(120deg, color-mix(in srgb, var(--ch-accent) 5%, transparent) 0 1px, transparent 1px 26px),
        radial-gradient(circle at 84% 6%, color-mix(in srgb, var(--ch-accent) 22%, transparent), transparent 31rem),
        linear-gradient(180deg, var(--ch-bg), #FBFAF7 55%, #F2ECE3);
    color: var(--ch-text);
}

.cash-history-shell {
    width: min(1480px, calc(100% - 28px));
    margin: 0 auto;
    padding: 28px 0 46px;
}

.cash-history-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(330px, .65fr);
    gap: 16px;
    align-items: stretch;
    margin-bottom: 18px;
}

.cash-history-title {
    position: relative;
    overflow: hidden;
    display: flex;
    min-height: 276px;
    flex-direction: column;
    justify-content: space-between;
    padding: clamp(22px, 3vw, 34px);
    border: 1px solid var(--ch-line);
    border-radius: 24px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--ch-accent) 5%, #FFFDF8), var(--ch-surface) 62%),
        linear-gradient(180deg, rgba(255, 255, 255, .62), transparent);
    box-shadow: 0 24px 58px -46px rgba(15, 23, 42, .6);
}

.cash-history-title::after {
    content: "";
    position: absolute;
    inset: auto 24px 24px auto;
    width: min(42%, 340px);
    height: 1px;
    background: color-mix(in srgb, var(--ch-accent) 36%, transparent);
    pointer-events: none;
}

.cash-history-title-top {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.cash-history-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 5px 11px 5px 6px;
    border: 1px solid color-mix(in srgb, var(--ch-primary) 14%, var(--ch-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--ch-primary) 4%, #FFFDF8);
    color: var(--ch-primary);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-history-kicker i {
    width: 25px;
    height: 25px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: var(--ch-action);
    color: var(--ch-on-action);
    font-size: .72rem;
}

.cash-history-period-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 36px;
    padding: 0 12px;
    border: 1px solid color-mix(in srgb, var(--ch-accent) 24%, var(--ch-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--ch-accent) 7%, #FFFDF8);
    color: var(--ch-primary);
    font-size: .78rem;
    font-weight: 900;
}

.cash-history-copy {
    position: relative;
    z-index: 1;
    margin-top: clamp(22px, 4vw, 42px);
}

.cash-history-title h1 {
    max-width: 13ch;
    margin: 0 0 14px;
    color: var(--ch-primary);
    font-family: inherit;
    font-size: clamp(2.25rem, 4.4vw, 4.45rem);
    line-height: .95;
    font-weight: 950;
    letter-spacing: 0;
}

.cash-history-title p {
    max-width: 58ch;
    margin: 0;
    color: color-mix(in srgb, var(--ch-text) 76%, var(--ch-muted));
    font-weight: 700;
    line-height: 1.6;
}

.cash-history-note-row {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 22px;
}

.cash-history-note {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 0 11px;
    border: 1px solid var(--ch-line-soft);
    border-radius: 999px;
    background: color-mix(in srgb, var(--ch-surface) 82%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-primary) 82%, var(--ch-muted));
    font-size: .78rem;
    font-weight: 850;
}

.cash-history-note i {
    color: color-mix(in srgb, var(--ch-accent) 76%, var(--ch-primary));
}

.cash-period-card {
    display: grid;
    align-content: start;
    gap: 14px;
    padding: 18px;
    border: 1px solid var(--ch-line);
    border-radius: 22px;
    background: var(--ch-surface);
    box-shadow: 0 18px 46px -38px rgba(15, 23, 42, .48);
}

.cash-period-card-head {
    display: grid;
    gap: 5px;
}

.cash-period-label,
.cash-section-label,
.cash-metric-label,
.cash-method-label {
    color: var(--ch-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-period-value {
    color: var(--ch-primary);
    font-family: inherit;
    font-size: clamp(1.55rem, 2.5vw, 2.1rem);
    line-height: 1;
    font-weight: 950;
}

.cash-history-form {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(90px, .55fr);
    gap: 10px;
}

.cash-history-form select,
.cash-history-form button,
.cash-history-link,
.cash-action-icon {
    min-height: 42px;
    border-radius: 13px;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.cash-filter-field {
    display: grid;
    gap: 6px;
}

.cash-filter-field span {
    color: var(--ch-muted);
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.cash-history-form select {
    width: 100%;
    border: 1px solid var(--ch-line);
    background: color-mix(in srgb, var(--ch-accent) 3%, #FFFDF8);
    color: var(--ch-primary);
    font-weight: 800;
    outline: none;
}

.cash-history-form select:focus {
    border-color: color-mix(in srgb, var(--ch-accent) 62%, var(--ch-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--ch-accent) 22%, transparent);
}

.cash-history-form button,
.cash-history-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 15px;
    font-weight: 900;
}

.cash-history-form button {
    grid-column: 1 / -1;
    border: 1px solid color-mix(in srgb, var(--ch-action) 78%, transparent);
    background: var(--ch-action);
    color: var(--ch-on-action);
}

.cash-history-form button:hover {
    background: var(--ch-action-hover);
}

.cash-history-link {
    border: 1px solid var(--ch-line);
    background: color-mix(in srgb, var(--ch-primary) 4%, #FFFDF8);
    color: var(--ch-primary);
    text-decoration: none;
}

.cash-history-form button:hover,
.cash-history-link:hover,
.cash-action-icon:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 28px -24px rgba(15, 23, 42, .55);
}

.cash-quick-states {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0;
    overflow: hidden;
    border: 1px solid var(--ch-line-soft);
    border-radius: 16px;
    background: color-mix(in srgb, var(--ch-primary) 3%, #FFFDF8);
}

.cash-quick-state {
    padding: 12px;
    border-right: 1px solid var(--ch-line-soft);
    background: transparent;
}

.cash-quick-state:last-child {
    border-right: 0;
}

.cash-quick-state strong {
    display: block;
    color: var(--ch-primary);
    font-size: 1.18rem;
    font-weight: 950;
    line-height: 1;
}

.cash-quick-state span {
    display: block;
    margin-top: 5px;
    color: var(--ch-muted);
    font-size: .72rem;
    font-weight: 800;
}

.cash-summary-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.25fr) repeat(4, minmax(150px, .75fr));
    gap: 14px;
    margin-bottom: 18px;
}

.cash-balance-card,
.cash-metric-card,
.cash-section-card,
.cash-cuts-panel {
    border: 1px solid var(--ch-line);
    background: var(--ch-surface);
    box-shadow: 0 16px 40px -34px rgba(15, 23, 42, .5);
}

.cash-balance-card,
.cash-metric-card {
    min-width: 0;
    border-radius: 20px;
}

.cash-balance-card {
    padding: 20px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--ch-accent) 10%, #FFFDF8), var(--ch-surface));
}

.cash-balance-amount {
    margin: 8px 0 12px;
    color: var(--ch-primary);
    font-size: clamp(1.65rem, 3vw, 2.25rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.cash-balance-amount.is-income {
    color: var(--ch-income);
}

.cash-balance-amount.is-expense {
    color: var(--ch-expense);
}

.cash-flow-bars {
    display: grid;
    grid-template-columns: minmax(0, <?= $ingresos_pct ?>fr) minmax(0, <?= $gastos_pct ?>fr);
    gap: 6px;
    height: 9px;
}

.cash-flow-bars span {
    border-radius: 999px;
}

.cash-flow-bars span:first-child {
    background: var(--ch-income);
}

.cash-flow-bars span:last-child {
    background: var(--ch-expense);
}

.cash-metric-card {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 132px;
    padding: 17px;
}

.cash-metric-card i {
    color: color-mix(in srgb, var(--ch-accent) 74%, var(--ch-primary));
}

.cash-metric-value {
    margin-top: 10px;
    color: var(--ch-primary);
    font-size: 1.38rem;
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.cash-metric-value.is-income,
.cash-row-money.is-income {
    color: var(--ch-income);
}

.cash-metric-value.is-expense,
.cash-row-money.is-expense {
    color: var(--ch-expense);
}

.cash-metric-note {
    margin-top: 8px;
    color: var(--ch-muted);
    font-size: .78rem;
    font-weight: 700;
}

.cash-activity-strip {
    margin-bottom: 18px;
    padding: 16px;
    border: 1px solid var(--ch-line);
    border-radius: 20px;
    background: color-mix(in srgb, var(--ch-accent) 4%, #FFFDF8);
}

.cash-top-days {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 10px;
    margin-top: 12px;
}

.cash-day-pill {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    padding: 11px;
    border: 1px solid var(--ch-line-soft);
    border-radius: 15px;
    background: var(--ch-surface);
}

.cash-day-rank {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 11px;
    background: color-mix(in srgb, var(--ch-accent) 18%, #FFFDF8);
    color: var(--ch-primary);
    font-weight: 950;
}

.cash-day-pill strong,
.cash-method-balance {
    display: block;
    color: var(--ch-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.cash-day-pill span {
    display: block;
    color: var(--ch-muted);
    font-size: .76rem;
    font-weight: 750;
}

.cash-section-card,
.cash-cuts-panel {
    border-radius: 22px;
    overflow: hidden;
}

.cash-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--ch-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--ch-accent) 6%, #FFFDF8), var(--ch-surface));
}

.cash-section-head h2,
.cash-section-head h3 {
    margin: 0;
    color: var(--ch-primary);
    font-size: 1.04rem;
    font-weight: 950;
}

.cash-section-body {
    padding: 18px 20px 20px;
}

.cash-empty-state {
    display: grid;
    place-items: center;
    min-height: 280px;
    padding: 36px;
    text-align: center;
}

.cash-empty-icon {
    width: 74px;
    height: 74px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border-radius: 24px;
    background: color-mix(in srgb, var(--ch-accent) 12%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-accent) 78%, var(--ch-primary));
    font-size: 1.8rem;
}

.cash-mobile-list {
    display: none;
}

.cash-cut-card {
    position: relative;
    padding: 16px;
    border-bottom: 1px solid var(--ch-line-soft);
    background: var(--ch-surface);
}

.cash-cut-card:last-child {
    border-bottom: 0;
}

.cash-cut-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: start;
}

.cash-cut-id {
    margin: 0;
    color: var(--ch-primary);
    font-size: 1.05rem;
    font-weight: 950;
}

.cash-meta-line {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    margin-top: 8px;
    color: var(--ch-muted);
    font-size: .78rem;
    font-weight: 750;
}

.cash-cut-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
}

.cash-action-icon {
    min-width: 39px;
    min-height: 39px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 11px;
    border: 1px solid var(--ch-line);
    border-radius: 12px;
    background: color-mix(in srgb, var(--ch-primary) 4%, #FFFDF8);
    color: var(--ch-primary);
    text-decoration: none;
    font-size: .76rem;
    font-weight: 900;
    line-height: 1;
    white-space: nowrap;
}

.cash-action-icon i {
    flex: 0 0 auto;
    font-size: .86rem;
}

.cash-action-label {
    display: inline-block;
}

.cash-action-icon.is-pdf {
    color: var(--ch-expense);
    background: color-mix(in srgb, var(--ch-expense) 6%, #FFFDF8);
    border-color: color-mix(in srgb, var(--ch-expense) 24%, var(--ch-line));
}

.cash-action-icon.is-excel {
    color: var(--ch-income);
    background: color-mix(in srgb, var(--ch-income) 7%, #FFFDF8);
    border-color: color-mix(in srgb, var(--ch-income) 24%, var(--ch-line));
}

.cash-status,
.cash-difference {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 26px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 900;
    white-space: nowrap;
}

.cash-status.is-open {
    background: color-mix(in srgb, var(--ch-warning) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-warning) 84%, var(--ch-primary));
}

.cash-status.is-closed {
    background: color-mix(in srgb, var(--ch-primary) 8%, #FFFDF8);
    color: var(--ch-primary);
}

.cash-difference.is-balanced {
    background: color-mix(in srgb, var(--ch-income) 11%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-income) 86%, var(--ch-primary));
}

.cash-difference.is-surplus {
    background: color-mix(in srgb, var(--ch-info) 11%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-info) 84%, var(--ch-primary));
}

.cash-difference.is-short {
    background: color-mix(in srgb, var(--ch-warning) 15%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-warning) 86%, #50310C);
}

.cash-card-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
    margin-top: 14px;
}

.cash-card-metric {
    min-width: 0;
    padding: 11px;
    border: 1px solid var(--ch-line-soft);
    border-radius: 14px;
    background: color-mix(in srgb, var(--ch-primary) 3%, #FFFDF8);
}

.cash-card-metric span,
.cash-method-row span {
    display: block;
    color: var(--ch-muted);
    font-size: .69rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.cash-card-metric strong {
    display: block;
    margin-top: 5px;
    color: var(--ch-primary);
    font-size: .95rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.cash-method-mini {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
    color: var(--ch-muted);
    font-size: .74rem;
    font-weight: 800;
}

.cash-desktop-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

.cash-desktop-table th {
    padding: 13px 12px;
    border-bottom: 1px solid var(--ch-line);
    color: var(--ch-muted);
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-desktop-table td {
    padding: 15px 12px;
    border-bottom: 1px solid var(--ch-line-soft);
    vertical-align: top;
}

.cash-desktop-table th.cash-actions-col,
.cash-desktop-table td.cash-actions-cell {
    padding-left: 16px;
    padding-right: 18px;
}

.cash-actions-cell .cash-cut-actions {
    justify-content: flex-end;
    min-width: 168px;
}

.cash-desktop-table tbody tr {
    transition: background .18s ease;
}

.cash-desktop-table tbody tr:hover {
    background: color-mix(in srgb, var(--ch-accent) 5%, #FFFDF8);
}

.cash-row-title {
    color: var(--ch-primary);
    font-weight: 950;
}

.cash-row-sub {
    margin-top: 4px;
    color: var(--ch-muted);
    font-size: .76rem;
    font-weight: 750;
}

.cash-row-money {
    color: var(--ch-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.cash-row-breakdown {
    margin-top: 5px;
    color: var(--ch-muted);
    font-size: .7rem;
    font-weight: 750;
    line-height: 1.45;
}

.cash-analytics-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.28fr) minmax(320px, .72fr);
    gap: 18px;
    margin-top: 18px;
}

.cash-chart-frame {
    position: relative;
    height: 320px;
}

.cash-method-grid {
    display: grid;
    gap: 12px;
}

.cash-method-card {
    padding: 14px;
    border: 1px solid var(--ch-line-soft);
    border-radius: 17px;
    background: color-mix(in srgb, var(--ch-accent) 3%, #FFFDF8);
}

.cash-method-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 11px;
}

.cash-method-icon {
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--ch-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--ch-accent) 80%, var(--ch-primary));
}

.cash-method-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    padding: 8px 0;
    border-top: 1px solid var(--ch-line-soft);
}

.cash-method-row strong {
    color: var(--ch-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

@media (max-width: 1180px) {
    .cash-history-hero,
    .cash-analytics-grid {
        grid-template-columns: 1fr;
    }

    .cash-summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cash-balance-card {
        grid-column: 1 / -1;
    }

    .cash-top-days {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cash-desktop-wrap {
        display: none;
    }

    .cash-mobile-list {
        display: block;
    }
}

@media (max-width: 700px) {
    .cash-history-shell {
        width: min(100% - 20px, 1480px);
        padding: 18px 0 30px;
    }

    .cash-history-title,
    .cash-period-card,
    .cash-balance-card,
    .cash-metric-card,
    .cash-section-card,
    .cash-cuts-panel {
        border-radius: 18px;
    }

    .cash-history-title {
        min-height: auto;
        padding: 20px;
    }

    .cash-history-title-top {
        align-items: flex-start;
        flex-direction: column;
    }

    .cash-history-copy {
        margin-top: 28px;
    }

    .cash-history-title h1 {
        max-width: 100%;
        font-size: clamp(2.05rem, 13vw, 3.35rem);
    }

    .cash-history-note-row {
        margin-top: 18px;
    }

    .cash-history-note {
        width: 100%;
    }

    .cash-history-form,
    .cash-summary-grid,
    .cash-top-days,
    .cash-card-metrics,
    .cash-quick-states {
        grid-template-columns: 1fr;
    }

    .cash-quick-state {
        border-right: 0;
        border-bottom: 1px solid var(--ch-line-soft);
    }

    .cash-quick-state:last-child {
        border-bottom: 0;
    }

    .cash-cut-top {
        grid-template-columns: 1fr;
    }

    .cash-cut-actions {
        justify-content: flex-start;
    }

    .cash-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .cash-chart-frame {
        height: 260px;
    }
}
</style>

<div class="cash-history-view">
    <div class="cash-history-shell">
        <section class="cash-history-hero">
            <div class="cash-history-title">
                <div class="cash-history-title-top">
                    <span class="cash-history-kicker">
                        <i class="fas fa-archive"></i>
                        Bitácora de caja
                    </span>
                    <span class="cash-history-period-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <?= caja_hist_safe($periodo_label) ?>
                    </span>
                </div>

                <div class="cash-history-copy">
                    <h1>Historial de cortes</h1>
                    <p>
                        Consulta cada apertura y cierre del mes, revisa diferencias de efectivo y abre el detalle antes de descargar comprobantes.
                    </p>
                </div>

                <div class="cash-history-note-row" aria-label="Alcance de la bitácora">
                    <span class="cash-history-note">
                        <i class="fas fa-door-open"></i>
                        Aperturas y cierres
                    </span>
                    <span class="cash-history-note">
                        <i class="fas fa-balance-scale"></i>
                        Diferencias de efectivo
                    </span>
                    <span class="cash-history-note">
                        <i class="fas fa-file-download"></i>
                        Descargas por corte
                    </span>
                </div>
            </div>

            <aside class="cash-period-card">
                <div class="cash-period-card-head">
                    <span class="cash-period-label">Periodo consultado</span>
                    <div class="cash-period-value"><?= caja_hist_safe($periodo_label) ?></div>
                </div>

                <form method="GET" action="<?= url('caja/historial') ?>" class="cash-history-form" data-auto-filter-form>
                    <label class="cash-filter-field">
                        <span>Mes</span>
                        <select name="mes" aria-label="Mes">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $mes ? 'selected' : '' ?>>
                                    <?= obtener_nombre_mes($m) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <label class="cash-filter-field">
                        <span>Año</span>
                        <select name="año" aria-label="Año">
                            <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $año ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </label>
                    <button type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar cortes
                    </button>
                </form>

                <?php $back_arrow_href = back_url('caja'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('caja') ?>" class="cash-history-link ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Volver a caja
                </a>

                <div class="cash-quick-states">
                    <div class="cash-quick-state">
                        <strong><?= number_format($total_cortes) ?></strong>
                        <span>Cortes</span>
                    </div>
                    <div class="cash-quick-state">
                        <strong><?= number_format($cortes_cerrados) ?></strong>
                        <span>Cerrados</span>
                    </div>
                    <div class="cash-quick-state">
                        <strong><?= number_format($cortes_abiertos) ?></strong>
                        <span>Abiertos</span>
                    </div>
                </div>
            </aside>
        </section>

        <?php if (!empty($estadisticas)): ?>
            <section class="cash-summary-grid">
                <article class="cash-balance-card">
                    <span class="cash-metric-label">Balance del mes</span>
                    <div class="cash-balance-amount <?= $balance_mes >= 0 ? 'is-income' : 'is-expense' ?>">
                        <?= caja_hist_money($balance_mes, true) ?>
                    </div>
                    <div class="cash-flow-bars" aria-label="Distribución de ingresos y gastos">
                        <span style="width:100%"></span>
                        <span style="width:100%"></span>
                    </div>
                    <p class="cash-metric-note">
                        <?= $ingresos_pct ?>% ingresos, <?= $gastos_pct ?>% gastos del flujo mensual.
                    </p>
                </article>

                <article class="cash-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="cash-metric-label">Ingresos</span>
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <div class="cash-metric-value is-income"><?= caja_hist_money($ingresos_mes) ?></div>
                    <p class="cash-metric-note">Total registrado en cortes.</p>
                </article>

                <article class="cash-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="cash-metric-label">Gastos</span>
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <div class="cash-metric-value is-expense"><?= caja_hist_money($gastos_mes) ?></div>
                    <p class="cash-metric-note">Salidas acumuladas.</p>
                </article>

                <article class="cash-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="cash-metric-label">Sobrantes</span>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="cash-metric-value"><?= caja_hist_money($sobrantes_mes) ?></div>
                    <p class="cash-metric-note">Diferencias positivas.</p>
                </article>

                <article class="cash-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="cash-metric-label">Faltantes</span>
                        <i class="fas fa-minus"></i>
                    </div>
                    <div class="cash-metric-value"><?= caja_hist_money($faltantes_mes) ?></div>
                    <p class="cash-metric-note"><?= number_format($cortes_con_diferencia) ?> cortes con diferencia.</p>
                </article>
            </section>

            <?php if (!empty($estadisticas['dias_top'])): ?>
                <section class="cash-activity-strip">
                    <span class="cash-section-label">Días con mayor actividad</span>
                    <div class="cash-top-days">
                        <?php foreach ($estadisticas['dias_top'] as $index => $dia): ?>
                            <article class="cash-day-pill">
                                <div class="cash-day-rank"><?= $index + 1 ?></div>
                                <div class="min-w-0">
                                    <strong><?= caja_hist_money($dia['ingresos_dia'] ?? 0) ?></strong>
                                    <span><?= caja_hist_date($dia['fecha'] ?? null, 'd/m') ?> · <?= number_format($dia['total_movimientos'] ?? 0) ?> movimientos</span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>

        <section class="cash-cuts-panel">
            <div class="cash-section-head">
                <div>
                    <span class="cash-section-label">Cortes del periodo</span>
                    <h2>Listado de cortes de caja</h2>
                </div>
                <span class="cash-status is-closed">
                    <i class="fas fa-list"></i>
                    <?= number_format($total_cortes) ?> registros
                </span>
            </div>

            <?php if (empty($cortes)): ?>
                <div class="cash-empty-state">
                    <div>
                        <div class="cash-empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 mb-2">No hay cortes registrados</h3>
                        <p class="text-sm text-slate-500">Cambia el periodo o vuelve a caja para revisar la operación actual.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="cash-mobile-list">
                    <?php foreach ($cortes as $corte): ?>
                        <?php
                        $diferencia = (float)($corte['diferencia'] ?? 0);
                        $estado = $corte['estado'] ?? 'abierto';
                        $is_open = $estado === 'abierto';
                        $difference_class = $diferencia == 0 ? 'is-balanced' : ($diferencia > 0 ? 'is-surplus' : 'is-short');
                        ?>
                        <article class="cash-cut-card">
                            <div class="cash-cut-top">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="cash-cut-id">Corte #<?= str_pad((string)$corte['id'], 6, '0', STR_PAD_LEFT) ?></h3>
                                        <span class="cash-status <?= $is_open ? 'is-open' : 'is-closed' ?>">
                                            <i class="fas fa-<?= $is_open ? 'lock-open' : 'lock' ?>"></i>
                                            <?= ucfirst($estado) ?>
                                        </span>
                                    </div>
                                    <div class="cash-meta-line">
                                        <span><i class="fas fa-calendar-alt"></i> <?= caja_hist_date($corte['fecha_apertura'] ?? null) ?></span>
                                        <span><i class="fas fa-clock"></i> <?= caja_hist_date($corte['fecha_apertura'] ?? null, 'H:i') ?><?= !empty($corte['fecha_cierre']) ? ' - ' . caja_hist_date($corte['fecha_cierre'], 'H:i') : '' ?></span>
                                        <span><?= caja_hist_duration($corte['fecha_apertura'] ?? null, $corte['fecha_cierre'] ?? null) ?></span>
                                    </div>
                                </div>

                                <div class="cash-cut-actions">
                                    <a href="<?= url('caja/corte/' . $corte['id']) ?>" class="cash-action-icon" title="Ver detalle" aria-label="Ver detalle del corte #<?= (int)$corte['id'] ?>">
                                        <i class="fas fa-eye"></i>
                                        <span class="cash-action-label">Ver</span>
                                    </a>
                                    <?php if ($estado == 'cerrado'): ?>
                                        <a href="<?= url('caja/descargar-pdf/' . $corte['id']) ?>" class="cash-action-icon is-pdf" title="Descargar PDF" aria-label="Descargar PDF del corte #<?= (int)$corte['id'] ?>">
                                            <i class="fas fa-file-pdf"></i>
                                            <span class="cash-action-label">PDF</span>
                                        </a>
                                        <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                                        <button type="button" onclick="exportarCorte(<?= $corte['id'] ?>)" class="cash-action-icon is-excel" title="Exportar Excel" aria-label="Exportar Excel del corte #<?= (int)$corte['id'] ?>">
                                            <i class="fas fa-file-excel"></i>
                                            <span class="cash-action-label">Excel</span>
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="cash-meta-line">
                                <span><i class="fas fa-user"></i> <?= caja_hist_safe($corte['usuario_apertura'] ?? null) ?></span>
                                <?php if (!empty($corte['usuario_cierre']) && $corte['usuario_cierre'] != ($corte['usuario_apertura'] ?? null)): ?>
                                    <span>Cerró: <?= caja_hist_safe($corte['usuario_cierre']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="cash-card-metrics">
                                <div class="cash-card-metric">
                                    <span>Ingresos</span>
                                    <strong class="cash-row-money is-income"><?= caja_hist_money($corte['total_ingresos'] ?? 0, true) ?></strong>
                                </div>
                                <div class="cash-card-metric">
                                    <span>Gastos</span>
                                    <strong class="cash-row-money is-expense">-<?= caja_hist_money(abs((float)($corte['total_gastos'] ?? 0))) ?></strong>
                                </div>
                                <div class="cash-card-metric">
                                    <span>Esperado</span>
                                    <strong><?= caja_hist_money($corte['efectivo_esperado'] ?? 0) ?></strong>
                                </div>
                                <div class="cash-card-metric">
                                    <span>Contado</span>
                                    <strong><?= $corte['efectivo_contado'] !== null ? caja_hist_money($corte['efectivo_contado']) : '-' ?></strong>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="cash-method-mini">
                                    <span>E: <?= caja_hist_money($corte['total_ingresos_efectivo'] ?? 0) ?></span>
                                    <span>T: <?= caja_hist_money($corte['total_ingresos_tarjeta'] ?? 0) ?></span>
                                    <span>Tr: <?= caja_hist_money($corte['total_ingresos_transferencia'] ?? 0) ?></span>
                                </div>
                                <span class="cash-difference <?= $difference_class ?>">
                                    <i class="fas fa-<?= $diferencia == 0 ? 'check' : ($diferencia > 0 ? 'plus' : 'minus') ?>"></i>
                                    <?= $diferencia == 0 ? 'Cuadrado' : caja_hist_money(abs($diferencia)) ?>
                                </span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="cash-desktop-wrap">
                    <table class="cash-desktop-table">
                        <colgroup>
                            <col style="width: 10%;">
                            <col style="width: 14%;">
                            <col style="width: 12%;">
                            <col style="width: 11%;">
                            <col style="width: 11%;">
                            <col style="width: 9%;">
                            <col style="width: 9%;">
                            <col style="width: 7%;">
                            <col style="width: 17%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-left">Corte</th>
                                <th class="text-left">Periodo</th>
                                <th class="text-left">Usuario</th>
                                <th class="text-right">Ingresos</th>
                                <th class="text-right">Gastos</th>
                                <th class="text-right">Esperado</th>
                                <th class="text-right">Contado</th>
                                <th class="text-center">Diferencia</th>
                                <th class="text-center cash-actions-col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cortes as $corte): ?>
                                <?php
                                $diferencia = (float)($corte['diferencia'] ?? 0);
                                $estado = $corte['estado'] ?? 'abierto';
                                $is_open = $estado === 'abierto';
                                $difference_class = $diferencia == 0 ? 'is-balanced' : ($diferencia > 0 ? 'is-surplus' : 'is-short');
                                ?>
                                <tr>
                                    <td>
                                        <div class="cash-row-title">#<?= str_pad((string)$corte['id'], 6, '0', STR_PAD_LEFT) ?></div>
                                        <div class="cash-row-sub">
                                            <span class="cash-status <?= $is_open ? 'is-open' : 'is-closed' ?>">
                                                <i class="fas fa-<?= $is_open ? 'lock-open' : 'lock' ?>"></i>
                                                <?= ucfirst($estado) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cash-row-title"><?= caja_hist_date($corte['fecha_apertura'] ?? null) ?></div>
                                        <div class="cash-row-sub">
                                            <?= caja_hist_date($corte['fecha_apertura'] ?? null, 'H:i') ?>
                                            <?= !empty($corte['fecha_cierre']) ? ' - ' . caja_hist_date($corte['fecha_cierre'], 'H:i') : ' - abierta' ?>
                                            · <?= caja_hist_duration($corte['fecha_apertura'] ?? null, $corte['fecha_cierre'] ?? null) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="cash-row-title"><?= caja_hist_safe($corte['usuario_apertura'] ?? null) ?></div>
                                        <?php if (!empty($corte['usuario_cierre']) && $corte['usuario_cierre'] != ($corte['usuario_apertura'] ?? null)): ?>
                                            <div class="cash-row-sub">Cerró: <?= caja_hist_safe($corte['usuario_cierre']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <div class="cash-row-money is-income"><?= caja_hist_money($corte['total_ingresos'] ?? 0, true) ?></div>
                                        <div class="cash-row-breakdown">
                                            E <?= caja_hist_money($corte['total_ingresos_efectivo'] ?? 0) ?> ·
                                            T <?= caja_hist_money($corte['total_ingresos_tarjeta'] ?? 0) ?> ·
                                            Tr <?= caja_hist_money($corte['total_ingresos_transferencia'] ?? 0) ?>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="cash-row-money is-expense">-<?= caja_hist_money(abs((float)($corte['total_gastos'] ?? 0))) ?></div>
                                        <div class="cash-row-breakdown">
                                            E <?= caja_hist_money($corte['total_gastos_efectivo'] ?? 0) ?> ·
                                            T <?= caja_hist_money($corte['total_gastos_tarjeta'] ?? 0) ?> ·
                                            Tr <?= caja_hist_money($corte['total_gastos_transferencia'] ?? 0) ?>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="cash-row-money"><?= caja_hist_money($corte['efectivo_esperado'] ?? 0) ?></div>
                                    </td>
                                    <td class="text-right">
                                        <div class="cash-row-money"><?= $corte['efectivo_contado'] !== null ? caja_hist_money($corte['efectivo_contado']) : '-' ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="cash-difference <?= $difference_class ?>">
                                            <i class="fas fa-<?= $diferencia == 0 ? 'check' : ($diferencia > 0 ? 'plus' : 'minus') ?>"></i>
                                            <?= $diferencia == 0 ? 'Cuadrado' : caja_hist_money(abs($diferencia)) ?>
                                        </span>
                                    </td>
                                    <td class="text-center cash-actions-cell">
                                        <div class="cash-cut-actions">
                                            <a href="<?= url('caja/corte/' . $corte['id']) ?>" class="cash-action-icon" title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                                <span class="cash-action-label">Ver</span>
                                            </a>
                                            <?php if ($estado == 'cerrado'): ?>
                                                <a href="<?= url('caja/descargar-pdf/' . $corte['id']) ?>" class="cash-action-icon is-pdf" title="Descargar PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                    <span class="cash-action-label">PDF</span>
                                                </a>
                                                <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                                                <button type="button" onclick="exportarCorte(<?= $corte['id'] ?>)" class="cash-action-icon is-excel" title="Exportar Excel">
                                                    <i class="fas fa-file-excel"></i>
                                                    <span class="cash-action-label">Excel</span>
                                                </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($cortes)): ?>
            <section class="cash-analytics-grid">
                <article class="cash-section-card">
                    <div class="cash-section-head">
                        <div>
                            <span class="cash-section-label">Tendencia mensual</span>
                            <h3>Ingresos y gastos por día</h3>
                        </div>
                        <span class="cash-status is-closed">
                            <i class="fas fa-chart-line"></i>
                            <?= caja_hist_safe($periodo_label) ?>
                        </span>
                    </div>
                    <div class="cash-section-body">
                        <div class="cash-chart-frame">
                            <canvas id="chartTendencias"></canvas>
                        </div>
                    </div>
                </article>

                <article class="cash-section-card">
                    <div class="cash-section-head">
                        <div>
                            <span class="cash-section-label">Métodos de pago</span>
                            <h3>Resumen de cortes cerrados</h3>
                        </div>
                    </div>
                    <div class="cash-section-body">
                        <div class="cash-method-grid">
                            <?php foreach ($totales_metodo as $metodo): ?>
                                <?php $balance_metodo = $metodo['ingresos'] - $metodo['gastos']; ?>
                                <article class="cash-method-card">
                                    <div class="cash-method-head">
                                        <div>
                                            <span class="cash-method-label"><?= caja_hist_safe($metodo['label']) ?></span>
                                            <strong class="cash-method-balance"><?= caja_hist_money($balance_metodo, true) ?></strong>
                                        </div>
                                        <div class="cash-method-icon">
                                            <i class="fas <?= caja_hist_safe($metodo['icon']) ?>"></i>
                                        </div>
                                    </div>
                                    <div class="cash-method-row">
                                        <span>Ingresos</span>
                                        <strong class="cash-row-money is-income"><?= caja_hist_money($metodo['ingresos'], true) ?></strong>
                                    </div>
                                    <div class="cash-method-row">
                                        <span>Gastos</span>
                                        <strong class="cash-row-money is-expense">-<?= caja_hist_money(abs((float)$metodo['gastos'])) ?></strong>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </article>
            </section>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
<?php if (!empty($cortes)): ?>
    const cortesData = <?= json_encode($cortes) ?>;
    const datosPorDia = {};

    cortesData.forEach(corte => {
        const fecha = (corte.fecha_apertura || '').split(' ')[0];
        if (!fecha) {
            return;
        }

        if (!datosPorDia[fecha]) {
            datosPorDia[fecha] = { ingresos: 0, gastos: 0 };
        }

        datosPorDia[fecha].ingresos += parseFloat(corte.total_ingresos || 0);
        datosPorDia[fecha].gastos += parseFloat(corte.total_gastos || 0);
    });

    const fechasOrdenadas = Object.keys(datosPorDia).sort();
    const ingresosPorDia = fechasOrdenadas.map(fecha => datosPorDia[fecha].ingresos);
    const gastosPorDia = fechasOrdenadas.map(fecha => datosPorDia[fecha].gastos);
    const etiquetas = fechasOrdenadas.map(fecha => {
        const [year, month, day] = fecha.split('-');
        return `${day}/${month}`;
    });

    const chartCanvas = document.getElementById('chartTendencias');
    if (chartCanvas) {
        const styles = getComputedStyle(document.querySelector('.cash-history-view'));
        const incomeColor = styles.getPropertyValue('--ch-income').trim() || '#16824E';
        const expenseColor = styles.getPropertyValue('--ch-expense').trim() || '#B93A32';
        const textColor = styles.getPropertyValue('--ch-text').trim() || '#17233E';
        const lineColor = styles.getPropertyValue('--ch-line-soft').trim() || '#E8DCCC';

        new Chart(chartCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: etiquetas,
                datasets: [{
                    label: 'Ingresos',
                    data: ingresosPorDia,
                    borderColor: incomeColor,
                    backgroundColor: 'rgba(22, 130, 78, 0.10)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.36,
                    fill: true
                }, {
                    label: 'Gastos',
                    data: gastosPorDia,
                    borderColor: expenseColor,
                    backgroundColor: 'rgba(185, 58, 50, 0.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.36,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            boxWidth: 8,
                            font: { weight: '700' }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(23, 35, 62, .94)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label ? context.dataset.label + ': ' : '';
                                return label + '$' + new Intl.NumberFormat('es-MX').format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor, font: { weight: '700' } },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return '$' + new Intl.NumberFormat('es-MX').format(value);
                            }
                        },
                        grid: { color: lineColor }
                    }
                }
            }
        });
    }
<?php endif; ?>

function imprimirCorte(id) {
    window.open('<?= url('caja/corte/') ?>' + id + '?print=1', '_blank');
}

function exportarCorte(id) {
    window.location.href = '<?= url('caja/exportar?formato=excel&corte_id=') ?>' + id;
}
</script>
