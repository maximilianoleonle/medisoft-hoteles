<?php include __DIR__ . '/../layout/header.php'; ?>
<?php
$metodosPago = $metodosPago ?? [
    'efectivo' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
    'tarjeta' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
    'transferencia' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0]
];

$datos = $datos ?? ['ingresos' => [], 'gastos' => []];
$totales = $totales ?? ['ingresos' => 0, 'gastos' => 0, 'utilidad' => 0];
$resumenDiario = $resumenDiario ?? [];
$usuarios = $usuarios ?? [];
$fecha_inicio = !empty($fecha_inicio) ? $fecha_inicio : date('Y-m-01');
$fecha_fin = !empty($fecha_fin) ? $fecha_fin : date('Y-m-d');

if (!function_exists('rep_ig_money')) {
    function rep_ig_money($amount, $signed = false) {
        $amount = (float)($amount ?? 0);
        $prefix = '';
        if ($amount < 0) {
            $prefix = '-';
            $amount = abs($amount);
        } elseif ($signed && $amount > 0) {
            $prefix = '+';
        }

        return $prefix . format_currency($amount);
    }
}

if (!function_exists('rep_ig_date')) {
    function rep_ig_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('rep_ig_safe')) {
    function rep_ig_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rep_ig_percent')) {
    function rep_ig_percent($value, $total) {
        $total = (float)($total ?? 0);
        if ($total <= 0) {
            return 0;
        }

        return round(((float)$value / $total) * 100, 1);
    }
}

$total_ingresos = (float)($totales['ingresos'] ?? 0);
$total_gastos = (float)($totales['gastos'] ?? 0);
$utilidad_neta = (float)($totales['utilidad'] ?? ($total_ingresos - $total_gastos));
$dias_periodo = max(count($resumenDiario), 1);
$promedio_ingresos = $total_ingresos / $dias_periodo;
$promedio_gastos = $total_gastos / $dias_periodo;
$promedio_utilidad = $utilidad_neta / $dias_periodo;
$margen_utilidad = $total_ingresos > 0 ? round(($utilidad_neta / $total_ingresos) * 100, 2) : 0;
$flujo_total = max(1, $total_ingresos + $total_gastos);
$ingresos_ratio = min(100, max(0, round(($total_ingresos / $flujo_total) * 100)));
$gastos_ratio = min(100, max(0, 100 - $ingresos_ratio));

$maxIngreso = !empty($datos['ingresos'])
    ? array_reduce($datos['ingresos'], function($carry, $item) {
        return (!$carry || ($item['total'] ?? 0) > ($carry['total'] ?? 0)) ? $item : $carry;
    })
    : null;

$maxGasto = !empty($datos['gastos'])
    ? array_reduce($datos['gastos'], function($carry, $item) {
        return (!$carry || ($item['total'] ?? 0) > ($carry['total'] ?? 0)) ? $item : $carry;
    })
    : null;

$mejorDia = !empty($resumenDiario)
    ? array_reduce($resumenDiario, function($carry, $item) {
        return (!$carry || ($item['utilidad'] ?? 0) > ($carry['utilidad'] ?? 0)) ? $item : $carry;
    })
    : null;

$metodoMeta = [
    'efectivo' => ['label' => 'Efectivo', 'icon' => 'fa-money-bill-wave'],
    'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'fa-credit-card'],
    'transferencia' => ['label' => 'Transferencia', 'icon' => 'fa-exchange-alt'],
];
?>

<style>
.profit-report-view,
.profit-modal-overlay {
    --pr-primary: var(--brand-primary, #1B2746);
    --pr-secondary: var(--brand-secondary, #0F172A);
    --pr-accent: var(--brand-accent, #BD9441);
    --pr-bg: color-mix(in srgb, var(--pr-accent) 8%, #F7F2EA);
    --pr-surface: color-mix(in srgb, var(--pr-accent) 3%, #FFFDF8);
    --pr-soft: color-mix(in srgb, var(--pr-primary) 5%, #FFFDF8);
    --pr-line: color-mix(in srgb, var(--pr-primary) 13%, #E8DCCC);
    --pr-line-soft: color-mix(in srgb, var(--pr-primary) 8%, #F0E7DB);
    --pr-text: #17233E;
    --pr-muted: #748096;
    --pr-income: #16824E;
    --pr-expense: #B93A32;
    --pr-info: #2563A7;
    --pr-warning: #B7791F;
    color: var(--pr-text);
}

.profit-report-view {
    min-height: 100vh;
    background:
        radial-gradient(circle at 90% 7%, color-mix(in srgb, var(--pr-accent) 22%, transparent), transparent 28rem),
        linear-gradient(120deg, color-mix(in srgb, var(--pr-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, var(--pr-bg), #FBFAF7 56%, #F3EDE4);
    opacity: 0;
    transition: opacity .24s ease;
}

.profit-report-view.loaded {
    opacity: 1;
}

.profit-shell {
    width: min(1500px, calc(100% - 28px));
    margin: 0 auto;
    padding: 28px 0 48px;
}

.profit-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}

.profit-hero-main,
.profit-period-panel,
.profit-card,
.profit-section {
    border: 1px solid var(--pr-line);
    background: var(--pr-surface);
    box-shadow: 0 18px 48px -38px rgba(15, 23, 42, .48);
}

.profit-hero-main {
    position: relative;
    overflow: hidden;
    min-height: 310px;
    padding: clamp(24px, 4vw, 42px);
    border-color: color-mix(in srgb, var(--pr-accent) 24%, transparent);
    border-radius: 26px;
    background:
        radial-gradient(circle at 88% 14%, color-mix(in srgb, var(--pr-accent) 34%, transparent), transparent 21rem),
        linear-gradient(135deg, color-mix(in srgb, var(--pr-primary) 95%, #0A0F1C), var(--pr-secondary));
}

.profit-hero-main::after {
    content: "";
    position: absolute;
    inset: auto -8% -54% 44%;
    height: 220px;
    background: radial-gradient(circle, color-mix(in srgb, var(--pr-accent) 42%, transparent), transparent 68%);
    pointer-events: none;
}

.profit-kicker,
.profit-label,
.profit-section-kicker,
.profit-table th,
.profit-mini-label {
    color: var(--pr-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.profit-kicker {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    color: color-mix(in srgb, var(--pr-accent) 82%, #FFFDF8);
}

.profit-hero-main h1 {
    max-width: 11ch;
    margin: 14px 0 14px;
    color: #FFFDF8;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.45rem, 5vw, 5.2rem);
    line-height: .9;
    font-weight: 700;
    letter-spacing: 0;
}

.profit-hero-main p {
    max-width: 66ch;
    margin: 0;
    color: rgba(255, 255, 255, .74);
    font-weight: 650;
    line-height: 1.6;
}

.profit-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.profit-btn,
.profit-icon-btn,
.profit-period-btn {
    min-height: 42px;
    border-radius: 13px;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.profit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--pr-accent) 46%, var(--pr-primary));
    background: var(--pr-primary);
    color: #FFFDF8;
    font-weight: 900;
    text-decoration: none;
}

.profit-btn.is-soft {
    border-color: rgba(255, 255, 255, .22);
    background: rgba(255, 255, 255, .10);
    color: #FFFDF8;
}

.profit-btn.is-accent {
    background: color-mix(in srgb, var(--pr-accent) 88%, #FFFDF8);
    color: color-mix(in srgb, var(--pr-primary) 88%, #000);
}

.profit-btn:hover,
.profit-period-btn:hover,
.profit-icon-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 30px -24px rgba(15, 23, 42, .55);
}

.profit-period-panel {
    display: flex;
    flex-direction: column;
    gap: 17px;
    justify-content: space-between;
    padding: 20px;
    border-radius: 24px;
}

.profit-period-value {
    margin-top: 7px;
    color: var(--pr-primary);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.45rem, 3vw, 2.3rem);
    font-weight: 700;
    line-height: 1;
}

.profit-filter-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 11px;
}

.profit-field label {
    display: block;
    margin-bottom: 6px;
    color: var(--pr-primary);
    font-size: .75rem;
    font-weight: 900;
}

.profit-field input,
.profit-modal select,
.profit-modal input {
    width: 100%;
    min-height: 43px;
    border: 1px solid var(--pr-line);
    border-radius: 13px;
    background: color-mix(in srgb, var(--pr-accent) 3%, #FFFDF8);
    color: var(--pr-primary);
    font-weight: 800;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease;
}

.profit-field input:focus,
.profit-modal select:focus,
.profit-modal input:focus {
    border-color: color-mix(in srgb, var(--pr-accent) 62%, var(--pr-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--pr-accent) 22%, transparent);
}

.profit-filter-form .profit-btn {
    grid-column: 1 / -1;
}

.profit-periods {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
}

.profit-period-btn {
    border: 1px solid var(--pr-line-soft);
    background: color-mix(in srgb, var(--pr-primary) 4%, #FFFDF8);
    color: var(--pr-primary);
    font-size: .76rem;
    font-weight: 900;
}

.profit-metric-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1.25fr) repeat(3, minmax(180px, .9fr));
    gap: 14px;
    margin-bottom: 18px;
}

.profit-card {
    min-width: 0;
    padding: 18px;
    border-radius: 20px;
}

.profit-card.is-main {
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--pr-accent) 11%, #FFFDF8), var(--pr-surface));
}

.profit-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.profit-card-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: color-mix(in srgb, var(--pr-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--pr-accent) 82%, var(--pr-primary));
}

.profit-value {
    margin-top: 13px;
    color: var(--pr-primary);
    font-size: clamp(1.45rem, 2.8vw, 2.2rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.profit-value.is-income,
.profit-money.is-income {
    color: var(--pr-income);
}

.profit-value.is-expense,
.profit-money.is-expense {
    color: var(--pr-expense);
}

.profit-note {
    margin-top: 9px;
    color: var(--pr-muted);
    font-size: .8rem;
    font-weight: 750;
}

.profit-flow {
    display: grid;
    grid-template-columns: minmax(0, <?= $ingresos_ratio ?>fr) minmax(0, <?= $gastos_ratio ?>fr);
    gap: 7px;
    height: 10px;
    margin-top: 15px;
}

.profit-flow span {
    border-radius: 999px;
}

.profit-flow span:first-child {
    background: var(--pr-income);
}

.profit-flow span:last-child {
    background: var(--pr-expense);
}

.profit-methods {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.profit-method {
    padding: 16px;
    border: 1px solid var(--pr-line);
    border-radius: 20px;
    background: var(--pr-surface);
}

.profit-method-top {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 12px;
}

.profit-method h3 {
    margin: 4px 0 0;
    color: var(--pr-primary);
    font-weight: 950;
}

.profit-method-balance {
    color: var(--pr-primary);
    font-size: 1.22rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.profit-method-balance.is-income {
    color: var(--pr-income);
}

.profit-method-balance.is-expense {
    color: var(--pr-expense);
}

.profit-method-line {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 12px;
    padding: 9px 0;
    border-top: 1px solid var(--pr-line-soft);
}

.profit-method-line span {
    color: var(--pr-muted);
    font-size: .78rem;
    font-weight: 800;
}

.profit-method-line strong,
.profit-money {
    color: var(--pr-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.profit-section {
    border-radius: 22px;
    overflow: hidden;
    margin-bottom: 18px;
}

.profit-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--pr-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--pr-accent) 6%, #FFFDF8), var(--pr-surface));
}

.profit-section-head h2,
.profit-section-head h3 {
    margin: 0;
    color: var(--pr-primary);
    font-size: 1.05rem;
    font-weight: 950;
}

.profit-section-body {
    padding: 18px 20px 20px;
}

.profit-chart-shell {
    height: 320px;
    position: relative;
}

.profit-toggle-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.dataset-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 34px;
    padding: 0 11px;
    border: 1px solid var(--pr-line);
    border-radius: 999px;
    background: color-mix(in srgb, var(--pr-primary) 4%, #FFFDF8);
    color: var(--pr-primary);
    font-size: .75rem;
    font-weight: 900;
    transition: opacity .18s ease, transform .18s ease, background .18s ease;
}

.dataset-toggle-btn:hover {
    transform: translateY(-1px);
}

.dataset-toggle-btn.dataset-hidden {
    opacity: .52;
    text-decoration: line-through;
}

.profit-two-columns {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-bottom: 18px;
}

.profit-table-wrap {
    overflow-x: auto;
}

.profit-table {
    width: 100%;
    min-width: 560px;
    border-collapse: separate;
    border-spacing: 0;
}

.profit-table th {
    padding: 12px 11px;
    border-bottom: 1px solid var(--pr-line);
    text-align: left;
}

.profit-table td {
    padding: 13px 11px;
    border-bottom: 1px solid var(--pr-line-soft);
    color: var(--pr-text);
    font-size: .86rem;
}

.profit-table tbody tr {
    transition: background .18s ease;
}

.profit-table tbody tr:hover {
    background: color-mix(in srgb, var(--pr-accent) 5%, #FFFDF8);
}

.profit-table tfoot td {
    background: color-mix(in srgb, var(--pr-primary) 5%, #FFFDF8);
    color: var(--pr-primary);
    font-weight: 950;
}

.profit-bar {
    height: 7px;
    margin-top: 6px;
    border-radius: 999px;
    overflow: hidden;
    background: color-mix(in srgb, var(--pr-primary) 8%, #FFFDF8);
}

.profit-bar span {
    display: block;
    height: 100%;
    border-radius: inherit;
    transform-origin: left center;
    transition: transform .7s ease;
}

.profit-bar.is-income span {
    background: var(--pr-income);
}

.profit-bar.is-expense span {
    background: var(--pr-expense);
}

.profit-empty {
    display: grid;
    place-items: center;
    min-height: 220px;
    padding: 30px;
    text-align: center;
    color: var(--pr-muted);
}

.profit-empty i {
    width: 64px;
    height: 64px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    border-radius: 22px;
    background: color-mix(in srgb, var(--pr-accent) 12%, #FFFDF8);
    color: color-mix(in srgb, var(--pr-accent) 80%, var(--pr-primary));
    font-size: 1.55rem;
}

.profit-daily-list {
    display: grid;
    gap: 9px;
    max-height: 410px;
    overflow: auto;
    padding-right: 4px;
}

.profit-day-row {
    display: grid;
    grid-template-columns: minmax(120px, .9fr) repeat(3, minmax(110px, 1fr));
    gap: 12px;
    align-items: center;
    padding: 13px;
    border: 1px solid var(--pr-line-soft);
    border-radius: 16px;
    background: color-mix(in srgb, var(--pr-accent) 2%, #FFFDF8);
}

.profit-day-row.is-weekend {
    background: color-mix(in srgb, var(--pr-accent) 8%, #FFFDF8);
}

.profit-day-date {
    color: var(--pr-primary);
    font-weight: 950;
}

.profit-day-week {
    color: var(--pr-muted);
    font-size: .76rem;
    font-weight: 800;
}

.profit-insights {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}

.profit-insight {
    padding: 15px;
    border: 1px solid var(--pr-line-soft);
    border-radius: 17px;
    background: color-mix(in srgb, var(--pr-accent) 4%, #FFFDF8);
}

.profit-insight strong {
    color: var(--pr-primary);
    font-weight: 950;
}

.profit-insight p {
    margin: 8px 0 0;
    color: var(--pr-muted);
    font-size: .82rem;
    font-weight: 700;
    line-height: 1.55;
}

.profit-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(15, 23, 42, .62);
}

.profit-modal-overlay.hidden {
    display: none;
}

.profit-modal {
    width: min(100%, 480px);
    border: 1px solid var(--pr-line);
    border-radius: 22px;
    background: var(--pr-surface);
    box-shadow: 0 32px 80px -38px rgba(15, 23, 42, .82);
    overflow: hidden;
}

.profit-modal-head {
    padding: 18px 20px;
    border-bottom: 1px solid var(--pr-line-soft);
    background: linear-gradient(180deg, color-mix(in srgb, var(--pr-accent) 7%, #FFFDF8), var(--pr-surface));
}

.profit-modal-head h3 {
    margin: 0;
    color: var(--pr-primary);
    font-size: 1.05rem;
    font-weight: 950;
}

.profit-modal-body {
    display: grid;
    gap: 13px;
    padding: 18px 20px 20px;
}

.profit-modal label {
    color: var(--pr-primary);
    font-size: .8rem;
    font-weight: 900;
}

.profit-modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding-top: 6px;
}

.profit-check-list {
    display: grid;
    gap: 9px;
}

.profit-check-list label {
    display: flex;
    align-items: center;
    gap: 10px;
    min-height: 40px;
    padding: 10px 12px;
    border: 1px solid var(--pr-line-soft);
    border-radius: 13px;
    background: color-mix(in srgb, var(--pr-primary) 3%, #FFFDF8);
}

.profit-check-list input {
    min-height: auto;
    width: 18px;
    height: 18px;
    accent-color: var(--pr-primary);
}

@media (max-width: 1180px) {
    .profit-hero,
    .profit-two-columns {
        grid-template-columns: 1fr;
    }

    .profit-metric-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .profit-card.is-main {
        grid-column: 1 / -1;
    }

    .profit-methods,
    .profit-insights {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .profit-day-row {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    .profit-shell {
        width: min(100% - 20px, 1500px);
        padding: 18px 0 34px;
    }

    .profit-hero-main,
    .profit-period-panel,
    .profit-card,
    .profit-section,
    .profit-modal {
        border-radius: 18px;
    }

    .profit-hero-main {
        min-height: auto;
    }

    .profit-hero-main h1 {
        max-width: 9ch;
        font-size: clamp(2.05rem, 14vw, 3.5rem);
    }

    .profit-filter-form,
    .profit-periods,
    .profit-metric-grid,
    .profit-methods,
    .profit-insights,
    .profit-day-row,
    .profit-modal-actions {
        grid-template-columns: 1fr;
    }

    .profit-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .profit-chart-shell {
        height: 270px;
    }
}
</style>

<div class="profit-report-view reporte-view">
    <main class="profit-shell">
        <section class="profit-hero">
            <div class="profit-hero-main">
                <a href="<?= url('reportes') ?>" class="profit-btn is-soft">
                    <i class="fas fa-arrow-left"></i>
                    Volver a reportes
                </a>
                <div class="mt-6">
                    <span class="profit-kicker">
                        <i class="fas fa-balance-scale"></i>
                        Laboratorio de rentabilidad
                    </span>
                    <h1>Ingresos vs gastos</h1>
                    <p>
                        Lee el periodo como una fotografía financiera: flujo, margen, categorías, métodos de pago y utilidad diaria en una sola vista operativa.
                    </p>
                    <div class="profit-hero-actions">
                        <button type="button" onclick="exportarPDF()" class="profit-btn is-accent">
                            <i class="fas fa-file-pdf"></i>
                            Exportar PDF
                        </button>
                        <button type="button" onclick="abrirModalReporteUsuario()" class="profit-btn is-soft">
                            <i class="fas fa-users"></i>
                            Por usuario
                        </button>
                        <button type="button" onclick="abrirModalReporteIngresos()" class="profit-btn is-soft">
                            <i class="fas fa-chart-bar"></i>
                            Ingresos totales
                        </button>
                    </div>
                </div>
            </div>

            <aside class="profit-period-panel">
                <div>
                    <span class="profit-label">Periodo consultado</span>
                    <div class="profit-period-value">
                        <?= rep_ig_date($fecha_inicio, 'd/m') ?> - <?= rep_ig_date($fecha_fin) ?>
                    </div>
                    <p class="profit-note"><?= number_format($dias_periodo) ?> día<?= $dias_periodo === 1 ? '' : 's' ?> con lectura financiera.</p>
                </div>

                <form method="get" action="<?= url('reportes/ingresos-gastos') ?>" class="profit-filter-form" id="reporteFiltrosForm">
                    <div class="profit-field">
                        <label for="fecha_inicio">Desde</label>
                        <input type="date"
                               id="fecha_inicio"
                               name="fecha_inicio"
                               value="<?= rep_ig_safe($fecha_inicio, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="profit-field">
                        <label for="fecha_fin">Hasta</label>
                        <input type="date"
                               id="fecha_fin"
                               name="fecha_fin"
                               value="<?= rep_ig_safe($fecha_fin, '') ?>"
                               max="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="profit-btn">
                        <i class="fas fa-sync-alt"></i>
                        Actualizar reporte
                    </button>
                </form>

                <div>
                    <span class="profit-label">Periodos rápidos</span>
                    <div class="profit-periods mt-2">
                        <button type="button" onclick="setPeriodo(7)" class="profit-period-btn">7 días</button>
                        <button type="button" onclick="setPeriodo(30)" class="profit-period-btn">30 días</button>
                        <button type="button" onclick="setPeriodo(90)" class="profit-period-btn">90 días</button>
                        <button type="button" onclick="setMesActual()" class="profit-period-btn">Mes</button>
                        <button type="button" onclick="setAnioActual()" class="profit-period-btn">Año</button>
                    </div>
                </div>
            </aside>
        </section>

        <section class="profit-metric-grid">
            <article class="profit-card is-main">
                <div class="profit-card-head">
                    <span class="profit-label">Utilidad neta</span>
                    <span class="profit-card-icon">
                        <i class="fas <?= $utilidad_neta >= 0 ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                    </span>
                </div>
                <div class="profit-value <?= $utilidad_neta >= 0 ? 'is-income' : 'is-expense' ?>">
                    <?= rep_ig_money($utilidad_neta, true) ?>
                </div>
                <div class="profit-flow" aria-label="Relación ingresos gastos">
                    <span></span>
                    <span></span>
                </div>
                <p class="profit-note">
                    Margen <?= $margen_utilidad ?>%, <?= $ingresos_ratio ?>% ingresos y <?= $gastos_ratio ?>% gastos del flujo.
                </p>
            </article>

            <article class="profit-card">
                <div class="profit-card-head">
                    <span class="profit-label">Total ingresos</span>
                    <span class="profit-card-icon"><i class="fas fa-arrow-up"></i></span>
                </div>
                <div class="profit-value is-income count-up"><?= format_currency($total_ingresos) ?></div>
                <p class="profit-note"><?= count($datos['ingresos'] ?? []) ?> categorías, promedio <?= format_currency($promedio_ingresos) ?>/día.</p>
            </article>

            <article class="profit-card">
                <div class="profit-card-head">
                    <span class="profit-label">Total gastos</span>
                    <span class="profit-card-icon"><i class="fas fa-arrow-down"></i></span>
                </div>
                <div class="profit-value is-expense count-up"><?= format_currency($total_gastos) ?></div>
                <p class="profit-note"><?= count($datos['gastos'] ?? []) ?> categorías, promedio <?= format_currency($promedio_gastos) ?>/día.</p>
            </article>

            <article class="profit-card">
                <div class="profit-card-head">
                    <span class="profit-label">Promedio utilidad</span>
                    <span class="profit-card-icon"><i class="fas fa-calendar-day"></i></span>
                </div>
                <div class="profit-value <?= $promedio_utilidad >= 0 ? 'is-income' : 'is-expense' ?>"><?= rep_ig_money($promedio_utilidad, true) ?></div>
                <p class="profit-note">Resultado diario promedio del rango.</p>
            </article>
        </section>

        <section class="profit-methods">
            <?php foreach ($metodoMeta as $metodoKey => $meta): ?>
                <?php $metodo = $metodosPago[$metodoKey] ?? ['ingresos' => 0, 'gastos' => 0, 'balance' => 0]; ?>
                <article class="profit-method">
                    <div class="profit-method-top">
                        <div>
                            <span class="profit-label">Método de pago</span>
                            <h3><i class="fas <?= $meta['icon'] ?> mr-2"></i><?= $meta['label'] ?></h3>
                        </div>
                        <div class="profit-method-balance <?= ((float)($metodo['balance'] ?? 0)) >= 0 ? 'is-income' : 'is-expense' ?>">
                            <?= rep_ig_money($metodo['balance'] ?? 0, true) ?>
                        </div>
                    </div>
                    <div class="profit-method-line">
                        <span>Ingresos</span>
                        <strong class="profit-money is-income"><?= rep_ig_money($metodo['ingresos'] ?? 0, true) ?></strong>
                    </div>
                    <div class="profit-method-line">
                        <span>Gastos</span>
                        <strong class="profit-money is-expense">-<?= format_currency(abs((float)($metodo['gastos'] ?? 0))) ?></strong>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="profit-section">
            <div class="profit-section-head">
                <div>
                    <span class="profit-section-kicker">Evolución diaria</span>
                    <h2>Flujo del periodo</h2>
                </div>
                <?php if (!empty($resumenDiario)): ?>
                    <div class="profit-toggle-group">
                        <button id="toggleIngresos" type="button" onclick="toggleDataset(0)" class="dataset-toggle-btn">
                            <i class="fas fa-eye toggle-icon"></i>
                            Ingresos
                        </button>
                        <button id="toggleGastos" type="button" onclick="toggleDataset(1)" class="dataset-toggle-btn">
                            <i class="fas fa-eye toggle-icon"></i>
                            Gastos
                        </button>
                        <button id="toggleUtilidad" type="button" onclick="toggleDataset(2)" class="dataset-toggle-btn">
                            <i class="fas fa-eye toggle-icon"></i>
                            Utilidad
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profit-section-body">
                <?php if (!empty($resumenDiario)): ?>
                    <div class="profit-chart-shell">
                        <canvas id="graficaEvolucion"></canvas>
                    </div>
                <?php else: ?>
                    <div class="profit-empty">
                        <div>
                            <i class="fas fa-chart-line"></i>
                            <p>No hay datos para mostrar la gráfica en este periodo. Prueba otro rango de fechas o genera actividad demo.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="profit-two-columns">
            <article class="profit-section">
                <div class="profit-section-head">
                    <div>
                        <span class="profit-section-kicker">Entrada de dinero</span>
                        <h3>Ingresos por categoría</h3>
                    </div>
                    <span class="profit-money is-income"><?= format_currency($total_ingresos) ?></span>
                </div>
                <div class="profit-section-body">
                    <?php if (!empty($datos['ingresos'])): ?>
                        <div class="profit-table-wrap">
                            <table class="profit-table">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($datos['ingresos'] as $ingreso): ?>
                                        <?php $porcentaje = rep_ig_percent($ingreso['total'] ?? 0, $total_ingresos); ?>
                                        <tr>
                                            <td>
                                                <strong><?= rep_ig_safe($ingreso['categoria'] ?? '') ?></strong>
                                                <div class="profit-bar is-income">
                                                    <span class="percentage-fill" style="width: <?= $porcentaje ?>%"></span>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= number_format($ingreso['cantidad'] ?? 0) ?></td>
                                            <td class="text-right profit-money is-income"><?= format_currency($ingreso['total'] ?? 0) ?></td>
                                            <td class="text-center"><?= $porcentaje ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="text-center"><?= number_format(array_sum(array_column($datos['ingresos'], 'cantidad'))) ?></td>
                                        <td class="text-right"><?= format_currency($total_ingresos) ?></td>
                                        <td class="text-center">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="profit-empty">
                            <div>
                                <i class="fas fa-info-circle"></i>
                                <p>No hay ingresos registrados en este periodo. Los cobros aparecerán aquí cuando exista actividad.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="profit-section">
                <div class="profit-section-head">
                    <div>
                        <span class="profit-section-kicker">Salida de dinero</span>
                        <h3>Gastos por categoría</h3>
                    </div>
                    <span class="profit-money is-expense"><?= format_currency($total_gastos) ?></span>
                </div>
                <div class="profit-section-body">
                    <?php if (!empty($datos['gastos'])): ?>
                        <div class="profit-table-wrap">
                            <table class="profit-table">
                                <thead>
                                    <tr>
                                        <th>Categoría</th>
                                        <th class="text-center">Cantidad</th>
                                        <th class="text-right">Total</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($datos['gastos'] as $gasto): ?>
                                        <?php $porcentaje = rep_ig_percent($gasto['total'] ?? 0, $total_gastos); ?>
                                        <tr>
                                            <td>
                                                <strong><?= rep_ig_safe($gasto['categoria'] ?? '') ?></strong>
                                                <div class="profit-bar is-expense">
                                                    <span class="percentage-fill" style="width: <?= $porcentaje ?>%"></span>
                                                </div>
                                            </td>
                                            <td class="text-center"><?= number_format($gasto['cantidad'] ?? 0) ?></td>
                                            <td class="text-right profit-money is-expense"><?= format_currency($gasto['total'] ?? 0) ?></td>
                                            <td class="text-center"><?= $porcentaje ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="text-center"><?= number_format(array_sum(array_column($datos['gastos'], 'cantidad'))) ?></td>
                                        <td class="text-right"><?= format_currency($total_gastos) ?></td>
                                        <td class="text-center">100%</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="profit-empty">
                            <div>
                                <i class="fas fa-info-circle"></i>
                                <p>No hay gastos registrados en este periodo. Los egresos aparecerán aquí cuando se capturen movimientos.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        </section>

        <?php if (!empty($resumenDiario)): ?>
            <section class="profit-section">
                <div class="profit-section-head">
                    <div>
                        <span class="profit-section-kicker">Bitácora diaria</span>
                        <h3>Detalle por día</h3>
                    </div>
                    <span class="profit-money"><?= number_format(count($resumenDiario)) ?> días</span>
                </div>
                <div class="profit-section-body">
                    <div class="profit-daily-list">
                        <?php foreach ($resumenDiario as $dia): ?>
                            <?php
                            $fecha = strtotime($dia['fecha']);
                            $diaSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][date('w', $fecha)];
                            $esFinSemana = in_array(date('w', $fecha), [0, 6]);
                            ?>
                            <article class="profit-day-row <?= $esFinSemana ? 'is-weekend' : '' ?>">
                                <div>
                                    <div class="profit-day-date"><?= date('d/m/Y', $fecha) ?></div>
                                    <div class="profit-day-week"><?= $diaSemana ?><?= $esFinSemana ? ' · fin de semana' : '' ?></div>
                                </div>
                                <div>
                                    <span class="profit-mini-label">Ingresos</span>
                                    <strong class="profit-money is-income"><?= format_currency($dia['ingresos'] ?? 0) ?></strong>
                                </div>
                                <div>
                                    <span class="profit-mini-label">Gastos</span>
                                    <strong class="profit-money is-expense"><?= format_currency($dia['gastos'] ?? 0) ?></strong>
                                </div>
                                <div>
                                    <span class="profit-mini-label">Utilidad</span>
                                    <strong class="profit-money <?= ($dia['utilidad'] ?? 0) >= 0 ? 'is-income' : 'is-expense' ?>">
                                        <?= rep_ig_money($dia['utilidad'] ?? 0, true) ?>
                                    </strong>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($datos['ingresos']) || !empty($datos['gastos'])): ?>
            <section class="profit-section">
                <div class="profit-section-head">
                    <div>
                        <span class="profit-section-kicker">Lecturas rápidas</span>
                        <h3>Insights del periodo</h3>
                    </div>
                </div>
                <div class="profit-section-body">
                    <div class="profit-insights">
                        <?php if ($maxIngreso): ?>
                            <article class="profit-insight">
                                <span class="profit-label">Mayor ingreso</span>
                                <p><strong><?= rep_ig_safe($maxIngreso['categoria'] ?? '') ?></strong> representa <strong><?= rep_ig_percent($maxIngreso['total'] ?? 0, $total_ingresos) ?>%</strong> de los ingresos.</p>
                            </article>
                        <?php endif; ?>

                        <?php if ($maxGasto): ?>
                            <article class="profit-insight">
                                <span class="profit-label">Mayor gasto</span>
                                <p><strong><?= rep_ig_safe($maxGasto['categoria'] ?? '') ?></strong> concentra <strong><?= rep_ig_percent($maxGasto['total'] ?? 0, $total_gastos) ?>%</strong> de los gastos.</p>
                            </article>
                        <?php endif; ?>

                        <?php if ($mejorDia): ?>
                            <article class="profit-insight">
                                <span class="profit-label">Mejor día</span>
                                <p><strong><?= rep_ig_date($mejorDia['fecha'] ?? null) ?></strong> cerró con utilidad de <strong><?= rep_ig_money($mejorDia['utilidad'] ?? 0, true) ?></strong>.</p>
                            </article>
                        <?php endif; ?>

                        <article class="profit-insight">
                            <span class="profit-label">Promedio diario</span>
                            <p>La utilidad promedio del rango fue <strong><?= rep_ig_money($promedio_utilidad, true) ?></strong> por día.</p>
                        </article>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<div id="modalReporteUsuario" class="profit-modal-overlay hidden">
    <div class="profit-modal">
        <div class="profit-modal-head">
            <span class="profit-section-kicker">Exportación por usuario</span>
            <h3>Reporte de ingresos y gastos por usuario</h3>
        </div>
        <form id="formReporteUsuario">
            <div class="profit-modal-body">
                <div>
                    <label>Usuario</label>
                    <select name="usuario_id" required>
                        <option value="">Seleccione un usuario</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id'] ?>"><?= rep_ig_safe($usuario['nombre_completo'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" required value="<?= date('Y-m-01') ?>">
                </div>
                <div>
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" required value="<?= date('Y-m-d') ?>">
                </div>
                <div class="profit-modal-actions">
                    <button type="submit" class="profit-btn">Generar reporte</button>
                    <button type="button" onclick="cerrarModalReporteUsuario()" class="profit-btn is-soft" style="color: var(--pr-primary); border-color: var(--pr-line); background: var(--pr-soft);">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="modalReporteIngresos" class="profit-modal-overlay hidden">
    <div class="profit-modal">
        <div class="profit-modal-head">
            <span class="profit-section-kicker">Exportación de ingresos</span>
            <h3>Reporte de ingresos totales</h3>
        </div>
        <form id="formReporteIngresos">
            <div class="profit-modal-body">
                <div>
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" required value="<?= date('Y-m-01') ?>">
                </div>
                <div>
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" required value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label>Incluir detalles por</label>
                    <div class="profit-check-list">
                        <label>
                            <input type="checkbox" name="desglose[]" value="categoria" checked>
                            <span>Categoría</span>
                        </label>
                        <label>
                            <input type="checkbox" name="desglose[]" value="metodo_pago" checked>
                            <span>Método de pago</span>
                        </label>
                        <label>
                            <input type="checkbox" name="desglose[]" value="diario">
                            <span>Detalle diario</span>
                        </label>
                    </div>
                </div>
                <div class="profit-modal-actions">
                    <button type="submit" class="profit-btn is-accent">Generar reporte</button>
                    <button type="button" onclick="cerrarModalReporteIngresos()" class="profit-btn is-soft" style="color: var(--pr-primary); border-color: var(--pr-line); background: var(--pr-soft);">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const datosGrafica = <?= json_encode($resumenDiario) ?>;
let myChart = null;

if (datosGrafica && datosGrafica.length > 0) {
    const canvas = document.getElementById('graficaEvolucion');
    if (canvas) {
        const styles = getComputedStyle(document.querySelector('.profit-report-view'));
        const incomeColor = styles.getPropertyValue('--pr-income').trim() || '#16824E';
        const expenseColor = styles.getPropertyValue('--pr-expense').trim() || '#B93A32';
        const infoColor = styles.getPropertyValue('--pr-info').trim() || '#2563A7';
        const textColor = styles.getPropertyValue('--pr-text').trim() || '#17233E';
        const gridColor = styles.getPropertyValue('--pr-line-soft').trim() || '#F0E7DB';

        myChart = new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: datosGrafica.map(d => {
                    const fecha = new Date(d.fecha);
                    return fecha.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
                }),
                datasets: [{
                    label: 'Ingresos',
                    data: datosGrafica.map(d => d.ingresos),
                    borderColor: incomeColor,
                    backgroundColor: 'rgba(22, 130, 78, 0.10)',
                    tension: 0.36,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true
                }, {
                    label: 'Gastos',
                    data: datosGrafica.map(d => d.gastos),
                    borderColor: expenseColor,
                    backgroundColor: 'rgba(185, 58, 50, 0.08)',
                    tension: 0.36,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true
                }, {
                    label: 'Utilidad',
                    data: datosGrafica.map(d => d.utilidad),
                    borderColor: infoColor,
                    backgroundColor: 'rgba(37, 99, 167, 0.08)',
                    tension: 0.36,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: false
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
                                return label + '$' + Number(context.parsed.y || 0).toLocaleString('es-MX');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor, font: { weight: '700' }, maxRotation: 0 },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: textColor,
                            callback: function(value) {
                                return '$' + Number(value || 0).toLocaleString('es-MX');
                            }
                        },
                        grid: { color: gridColor }
                    }
                }
            }
        });

        window.toggleDataset = function(index) {
            const dataset = myChart.data.datasets[index];
            dataset.hidden = !dataset.hidden;
            myChart.update();

            const buttons = ['toggleIngresos', 'toggleGastos', 'toggleUtilidad'];
            const button = document.getElementById(buttons[index]);
            if (!button) return;

            const icon = button.querySelector('.toggle-icon');
            if (dataset.hidden) {
                button.classList.add('dataset-hidden');
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
            } else {
                button.classList.remove('dataset-hidden');
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            }
        };
    }
}

function exportarPDF() {
    const url = '<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-gastos' +
                '&fecha_inicio=<?= $fecha_inicio ?>' +
                '&fecha_fin=<?= $fecha_fin ?>';
    window.open(url, '_blank');
}

function enviarFiltroReporte() {
    const form = document.getElementById('reporteFiltrosForm');
    if (form) {
        form.submit();
    }
}

function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);

    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
    enviarFiltroReporte();
}

function setMesActual() {
    const fecha = new Date();
    const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);

    document.getElementById('fecha_inicio').value = primerDia.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = ultimoDia.toISOString().split('T')[0];
    enviarFiltroReporte();
}

function setAnioActual() {
    const fecha = new Date();
    const primerDia = new Date(fecha.getFullYear(), 0, 1);

    document.getElementById('fecha_inicio').value = primerDia.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fecha.toISOString().split('T')[0];
    enviarFiltroReporte();
}

function abrirModalReporteUsuario() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }

    document.getElementById('modalReporteUsuario').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalReporteUsuario() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }

    document.getElementById('modalReporteUsuario').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalReporteUsuario').querySelector('form').reset();
}

function abrirModalReporteIngresos() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }

    document.getElementById('modalReporteIngresos').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalReporteIngresos() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }

    document.getElementById('modalReporteIngresos').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalReporteIngresos').querySelector('form').reset();
}

document.getElementById('formReporteUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData).toString();
    window.open('<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-gastos-usuario&' + params, '_blank');
    cerrarModalReporteUsuario();
});

document.getElementById('formReporteIngresos').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData).toString();
    window.open('<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-totales&' + params, '_blank');
    cerrarModalReporteIngresos();
});

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.reporte-view');
    if (view) {
        requestAnimationFrame(() => view.classList.add('loaded'));
    }

    setTimeout(() => {
        document.querySelectorAll('.percentage-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            requestAnimationFrame(() => {
                bar.style.width = width;
            });
        });
    }, 250);
});

window.addEventListener('beforeprint', function() {
    if (myChart) {
        myChart.resize(800, 400);
    }
});

window.addEventListener('afterprint', function() {
    if (myChart) {
        myChart.resize();
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
