<?php
/**
 * Reporte de metodos de pago de caja.
 * Vista presentacional: no modifica calculos ni datos de caja.
 */

$reporte = $reporte ?? [];
$metodos_pago = $metodos_pago ?? [];
$fecha_inicio = $fecha_inicio ?? date('Y-m-01');
$fecha_fin = $fecha_fin ?? date('Y-m-d');

if (!function_exists('caja_met_safe')) {
    function caja_met_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('caja_met_money')) {
    function caja_met_money($amount, $signed = false) {
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

if (!function_exists('caja_met_date')) {
    function caja_met_date($date) {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);
        return $timestamp ? date('d/m/Y', $timestamp) : '-';
    }
}

if (!function_exists('caja_met_method_meta')) {
    function caja_met_method_meta($key, $metodos_pago) {
        $fallback = [
            'efectivo' => ['label' => 'Efectivo', 'icon' => 'money-bill-wave', 'color' => 'green'],
            'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'credit-card', 'color' => 'blue'],
            'transferencia' => ['label' => 'Transferencia', 'icon' => 'exchange-alt', 'color' => 'purple'],
        ];

        $meta = $metodos_pago[$key] ?? ($fallback[$key] ?? []);
        $label = $meta['label'] ?? ucfirst(str_replace('_', ' ', (string)$key));
        $icon = $meta['icon'] ?? 'circle';
        $color = $meta['color'] ?? 'slate';

        return ['label' => $label, 'icon' => $icon, 'color' => $color];
    }
}

if (!function_exists('caja_met_class')) {
    function caja_met_class($value) {
        return preg_replace('/[^a-z0-9_-]+/i', '-', strtolower((string)$value));
    }
}

$metodo_keys = array_values(array_unique(array_merge(array_keys($metodos_pago), array_keys($reporte))));
if (empty($metodo_keys)) {
    $metodo_keys = ['efectivo', 'tarjeta', 'transferencia'];
}

$totales_generales = [
    'ingresos' => 0,
    'gastos' => 0,
    'balance' => 0,
    'movimientos' => 0,
];

$metodos_resumen = [];

foreach ($metodo_keys as $metodo_key) {
    $bloque = $reporte[$metodo_key] ?? [];
    $ingresos = (float)($bloque['total_ingresos'] ?? 0);
    $gastos = (float)($bloque['total_gastos'] ?? 0);
    $cantidad = 0;

    foreach (['ingresos', 'gastos'] as $tipo_lista) {
        foreach (($bloque[$tipo_lista] ?? []) as $row) {
            $cantidad += (int)($row['cantidad'] ?? 0);
        }
    }

    $totales_generales['ingresos'] += $ingresos;
    $totales_generales['gastos'] += $gastos;
    $totales_generales['movimientos'] += $cantidad;

    $metodos_resumen[$metodo_key] = [
        'meta' => caja_met_method_meta($metodo_key, $metodos_pago),
        'ingresos' => $ingresos,
        'gastos' => $gastos,
        'balance' => $ingresos - $gastos,
        'movimientos' => $cantidad,
        'ingresos_items' => $bloque['ingresos'] ?? [],
        'gastos_items' => $bloque['gastos'] ?? [],
    ];
}

$totales_generales['balance'] = $totales_generales['ingresos'] - $totales_generales['gastos'];
$flujo_total = $totales_generales['ingresos'] + $totales_generales['gastos'];
$ingresos_pct = 0;
$gastos_pct = 0;
if ($flujo_total > 0) {
    $ingresos_pct = min(100, max(0, round(($totales_generales['ingresos'] / $flujo_total) * 100)));
    $gastos_pct = min(100, max(0, 100 - $ingresos_pct));
}
$periodo_label = caja_met_date($fecha_inicio) . ' - ' . caja_met_date($fecha_fin);
$sin_datos = $totales_generales['movimientos'] === 0;
?>

<style>
.cash-methods-view {
    --cm-primary: var(--brand-primary, #1B2746);
    --cm-secondary: var(--brand-secondary, #0F172A);
    --cm-accent: var(--brand-accent, #BD9441);
    --cm-action: var(--brand-action-bg, var(--cm-primary));
    --cm-action-hover: var(--brand-action-bg-hover, var(--cm-secondary));
    --cm-on-action: var(--brand-action-text, #FFFEFB);
    --cm-bg: color-mix(in srgb, var(--cm-accent) 8%, #F7F3EC);
    --cm-surface: color-mix(in srgb, var(--cm-accent) 3%, #FFFFFF);
    --cm-soft: color-mix(in srgb, var(--cm-primary) 6%, #FFFFFF);
    --cm-line: color-mix(in srgb, var(--cm-primary) 13%, #E6DED2);
    --cm-line-soft: color-mix(in srgb, var(--cm-primary) 8%, #EEE7DC);
    --cm-text: var(--brand-text, #17233E);
    --cm-muted: var(--brand-muted, #748096);
    --cm-income: #16824E;
    --cm-expense: #B93A32;
    --cm-card-shadow: 0 18px 44px -36px rgba(15, 23, 42, .52);
    min-height: 100vh;
    background:
        radial-gradient(circle at 86% 4%, color-mix(in srgb, var(--cm-accent) 24%, transparent), transparent 30rem),
        linear-gradient(135deg, color-mix(in srgb, var(--cm-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, var(--cm-bg), #FBFAF7 56%, #F3EDE4);
    color: var(--cm-text);
}

.cash-methods-shell {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px clamp(34px, 4vw, 76px) 50px;
    box-sizing: border-box;
}

.cash-methods-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(300px, 420px);
    gap: 18px;
    align-items: stretch;
    margin-bottom: 18px;
}

.cash-methods-title,
.cash-methods-period,
.cash-methods-card,
.cash-methods-panel,
.cash-methods-empty {
    border: 1px solid var(--cm-line);
    border-radius: 22px;
    background: color-mix(in srgb, var(--cm-surface) 94%, transparent);
    box-shadow: var(--cm-card-shadow);
}

.cash-methods-title {
    position: relative;
    min-height: 325px;
    overflow: hidden;
    border-radius: 28px;
    padding: clamp(24px, 4vw, 44px);
    border-color: color-mix(in srgb, var(--cm-accent) 28%, transparent);
    background:
        radial-gradient(circle at 86% 18%, color-mix(in srgb, var(--cm-accent) 30%, transparent), transparent 22rem),
        linear-gradient(135deg,
            color-mix(in srgb, var(--cm-action) 54%, #101827),
            color-mix(in srgb, var(--cm-action-hover) 58%, #060A12)
        );
}

.cash-methods-title::after {
    content: "";
    position: absolute;
    inset: auto -10% -54% 46%;
    height: 220px;
    background: radial-gradient(circle, color-mix(in srgb, var(--cm-accent) 42%, transparent), transparent 68%);
    pointer-events: none;
}

.cash-methods-kicker,
.cash-methods-label,
.cash-methods-section-label,
.cash-methods-row-label {
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.cash-methods-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    width: fit-content;
    padding: 8px 11px;
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 999px;
    background: rgba(255,255,255,.1);
    color: #FFFFFF;
    text-shadow: 0 1px 1px rgba(0,0,0,.22);
}

.cash-methods-kicker i {
    color: color-mix(in srgb, var(--cm-accent) 42%, #FFFFFF);
}

.cash-methods-title h1 {
    position: relative;
    z-index: 1;
    max-width: 12ch;
    margin: 14px 0 14px;
    color: var(--cm-on-action);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.4rem, 5vw, 5.15rem);
    line-height: .9;
    font-weight: 700;
    letter-spacing: 0;
}

.cash-methods-title p {
    position: relative;
    z-index: 1;
    max-width: 66ch;
    margin: 0;
    color: color-mix(in srgb, var(--cm-on-action) 82%, transparent);
    font-weight: 650;
    line-height: 1.6;
}

.cash-methods-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.cash-methods-action,
.cash-methods-button,
.cash-methods-link {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 12px;
    font-size: .86rem;
    font-weight: 900;
    text-decoration: none;
    transition: transform .16s ease, background .16s ease, border-color .16s ease, box-shadow .16s ease;
}

.cash-methods-action {
    padding: 0 14px;
    color: var(--cm-on-action);
    background: color-mix(in srgb, var(--cm-on-action) 12%, transparent);
    border: 1px solid color-mix(in srgb, var(--cm-on-action) 22%, transparent);
}

.cash-methods-action:hover,
.cash-methods-button:hover,
.cash-methods-link:hover {
    transform: translateY(-1px);
}

.cash-methods-action.is-main,
.cash-methods-button {
    color: var(--cm-on-action);
    background: linear-gradient(135deg, var(--cm-action), var(--cm-action-hover));
    border: 1px solid color-mix(in srgb, var(--cm-accent) 34%, var(--cm-action));
    box-shadow: 0 16px 34px -24px color-mix(in srgb, var(--cm-action) 80%, transparent);
}

.cash-methods-title .cash-methods-action {
    border-color: rgba(255,255,255,.24);
    background: rgba(255,255,255,.1);
    color: var(--cm-on-action);
}

.cash-methods-title .cash-methods-action.is-main {
    border-color: rgba(255,255,255,.74);
    background: #FFFDF8;
    color: color-mix(in srgb, var(--cm-action) 82%, #111827);
    box-shadow: 0 18px 38px -28px rgba(0,0,0,.62);
}

.cash-methods-title .cash-methods-action.is-main i {
    color: color-mix(in srgb, var(--cm-accent) 76%, var(--cm-action));
}

.cash-methods-period {
    padding: 20px;
}

.cash-methods-period-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.cash-methods-period-value {
    color: var(--cm-primary);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.7rem, 3vw, 2.45rem);
    line-height: 1;
    font-weight: 700;
}

.cash-methods-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.cash-methods-field {
    min-width: 0;
}

.cash-methods-field label {
    display: block;
    margin-bottom: 6px;
    color: var(--cm-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-transform: uppercase;
}

.cash-methods-input {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--cm-line);
    border-radius: 12px;
    background: #FFFFFF;
    color: var(--cm-text);
    padding: 0 12px;
    font-weight: 800;
    outline: none;
}

.cash-methods-input:focus {
    border-color: color-mix(in srgb, var(--cm-accent) 62%, var(--cm-line));
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--cm-accent) 22%, transparent);
}

.cash-methods-period-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 10px;
    align-items: center;
}

.cash-methods-button {
    flex: 1;
    border: 0;
    cursor: pointer;
    padding: 0 14px;
}

.cash-methods-link {
    padding: 0 12px;
    color: var(--cm-primary);
    background: color-mix(in srgb, var(--cm-primary) 6%, #FFFFFF);
    border: 1px solid var(--cm-line);
}

.cash-methods-summary {
    display: grid;
    grid-template-columns: 1.1fr .9fr .9fr .8fr;
    gap: 12px;
    margin-bottom: 18px;
}

.cash-methods-card {
    min-width: 0;
    padding: 17px;
}

.cash-methods-label,
.cash-methods-section-label,
.cash-methods-row-label {
    color: var(--cm-muted);
}

.cash-methods-value {
    margin-top: 8px;
    color: var(--cm-primary);
    font-size: clamp(1.45rem, 2.4vw, 2rem);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.cash-methods-value.is-income {
    color: var(--cm-income);
}

.cash-methods-value.is-expense {
    color: var(--cm-expense);
}

.cash-methods-note {
    margin: 8px 0 0;
    color: var(--cm-muted);
    font-size: .78rem;
    line-height: 1.35;
    font-weight: 700;
}

.cash-methods-flow {
    display: flex;
    height: 9px;
    margin-top: 14px;
    overflow: hidden;
    border-radius: 999px;
    background: var(--cm-line-soft);
}

.cash-methods-flow span:first-child {
    background: var(--cm-income);
}

.cash-methods-flow span:last-child {
    background: var(--cm-expense);
}

.cash-methods-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.cash-methods-method {
    position: relative;
    overflow: hidden;
    padding: 18px;
    border: 1px solid var(--cm-line);
    border-radius: 20px;
    background: var(--cm-surface);
    box-shadow: var(--cm-card-shadow);
}

.cash-methods-method::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 4px;
    background: var(--method-color, var(--cm-accent));
}

.cash-methods-method.is-efectivo {
    --method-color: var(--cm-income);
}

.cash-methods-method.is-tarjeta {
    --method-color: #2563EB;
}

.cash-methods-method.is-transferencia {
    --method-color: #7C3AED;
}

.cash-methods-method-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 16px;
}

.cash-methods-method-title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.cash-methods-method-icon {
    width: 42px;
    height: 42px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 42px;
    border-radius: 13px;
    color: var(--method-color, var(--cm-primary));
    background: color-mix(in srgb, var(--method-color, var(--cm-primary)) 10%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--method-color, var(--cm-primary)) 18%, transparent);
}

.cash-methods-method-title h2 {
    margin: 0;
    color: var(--cm-primary);
    font-size: 1rem;
    line-height: 1.15;
    font-weight: 950;
}

.cash-methods-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    color: var(--method-color, var(--cm-primary));
    background: color-mix(in srgb, var(--method-color, var(--cm-primary)) 10%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--method-color, var(--cm-primary)) 18%, transparent);
    font-size: .72rem;
    font-weight: 900;
    white-space: nowrap;
}

.cash-methods-method-metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cash-methods-mini {
    min-width: 0;
    padding: 12px;
    border: 1px solid var(--cm-line-soft);
    border-radius: 14px;
    background: color-mix(in srgb, var(--cm-primary) 3%, #FFFFFF);
}

.cash-methods-mini span {
    display: block;
    color: var(--cm-muted);
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-methods-mini strong {
    display: block;
    margin-top: 6px;
    color: var(--cm-primary);
    font-size: 1rem;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.cash-methods-mini strong.is-income {
    color: var(--cm-income);
}

.cash-methods-mini strong.is-expense {
    color: var(--cm-expense);
}

.cash-methods-details {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
}

.cash-methods-panel {
    overflow: hidden;
}

.cash-methods-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--cm-accent) 6%, #FFFFFF), #FFFFFF);
    border-bottom: 1px solid var(--cm-line-soft);
}

.cash-methods-panel-head h3 {
    margin: 4px 0 0;
    color: var(--cm-primary);
    font-size: 1rem;
    line-height: 1.15;
    font-weight: 950;
}

.cash-methods-panel-body {
    padding: 14px;
}

.cash-methods-group + .cash-methods-group {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid var(--cm-line-soft);
}

.cash-methods-group-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 9px;
}

.cash-methods-group-title strong {
    color: var(--cm-primary);
    font-size: .86rem;
    font-weight: 950;
}

.cash-methods-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--cm-line-soft);
}

.cash-methods-row:last-child {
    border-bottom: 0;
}

.cash-methods-row-title {
    min-width: 0;
}

.cash-methods-row-title strong {
    display: block;
    color: var(--cm-text);
    font-size: .88rem;
    line-height: 1.25;
    font-weight: 900;
    overflow-wrap: anywhere;
}

.cash-methods-row-title span {
    display: block;
    margin-top: 3px;
    color: var(--cm-muted);
    font-size: .72rem;
    font-weight: 800;
}

.cash-methods-row-amount {
    color: var(--cm-primary);
    font-size: .9rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.cash-methods-row-amount.is-income {
    color: var(--cm-income);
}

.cash-methods-row-amount.is-expense {
    color: var(--cm-expense);
}

.cash-methods-empty {
    padding: 34px;
    margin-top: 18px;
    text-align: center;
}

.cash-methods-empty i {
    width: 48px;
    height: 48px;
    display: inline-grid;
    place-items: center;
    margin-bottom: 12px;
    border-radius: 15px;
    color: var(--cm-primary);
    background: color-mix(in srgb, var(--cm-primary) 8%, #FFFFFF);
    border: 1px solid var(--cm-line);
}

.cash-methods-empty h2 {
    margin: 0 0 8px;
    color: var(--cm-primary);
    font-size: 1.25rem;
    font-weight: 950;
}

.cash-methods-empty p {
    max-width: 58ch;
    margin: 0 auto;
    color: var(--cm-muted);
    font-weight: 700;
}

@media (max-width: 1180px) {
    .cash-methods-summary,
    .cash-methods-grid,
    .cash-methods-details {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cash-methods-summary .cash-methods-card:first-child {
        grid-column: span 2;
    }
}

@media (max-width: 860px) {
    .cash-methods-hero,
    .cash-methods-summary,
    .cash-methods-grid,
    .cash-methods-details {
        grid-template-columns: 1fr;
    }

    .cash-methods-summary .cash-methods-card:first-child {
        grid-column: auto;
    }
}

@media (max-width: 620px) {
    .cash-methods-shell {
        width: 100%;
        padding: 18px 12px 36px;
    }

    .cash-methods-title {
        min-height: auto;
        padding: 22px;
    }

    .cash-methods-title,
    .cash-methods-period,
    .cash-methods-card,
    .cash-methods-panel,
    .cash-methods-empty {
        border-radius: 16px;
    }

    .cash-methods-form,
    .cash-methods-method-metrics {
        grid-template-columns: 1fr;
    }

    .cash-methods-period-actions,
    .cash-methods-hero-actions {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>

<main class="cash-methods-view">
    <div class="cash-methods-shell">
        <section class="cash-methods-hero">
            <div class="cash-methods-title">
                <span class="cash-methods-kicker">
                    <i class="fas fa-credit-card"></i>
                    Caja operativa
                </span>
                <h1>Metodos de pago</h1>
                <p>
                    Revisa como se distribuyen ingresos y salidas por efectivo, tarjeta y transferencia
                    dentro del periodo seleccionado.
                </p>
                <div class="cash-methods-hero-actions">
                    <a href="<?= url('caja') ?>" class="cash-methods-action is-main">
                        <i class="fas fa-arrow-left"></i>
                        Volver a caja
                    </a>
                    <a href="<?= url('caja/movimientos') ?>" class="cash-methods-action">
                        <i class="fas fa-list"></i>
                        Movimientos
                    </a>
                    <a href="<?= url('caja/historial') ?>" class="cash-methods-action">
                        <i class="fas fa-history"></i>
                        Historial
                    </a>
                </div>
            </div>

            <aside class="cash-methods-period">
                <div class="cash-methods-period-head">
                    <div>
                        <span class="cash-methods-label">Periodo consultado</span>
                        <div class="cash-methods-period-value"><?= caja_met_safe($periodo_label) ?></div>
                    </div>
                    <i class="fas fa-calendar-alt text-xl" style="color: var(--cm-accent);"></i>
                </div>

                <form method="GET" action="<?= url('caja/reporte-metodos') ?>" class="cash-methods-form" data-auto-filter-form>
                    <div class="cash-methods-field">
                        <label for="fecha_inicio">Desde</label>
                        <input id="fecha_inicio" type="date" name="fecha_inicio" value="<?= caja_met_safe($fecha_inicio, '') ?>" class="cash-methods-input">
                    </div>
                    <div class="cash-methods-field">
                        <label for="fecha_fin">Hasta</label>
                        <input id="fecha_fin" type="date" name="fecha_fin" value="<?= caja_met_safe($fecha_fin, '') ?>" class="cash-methods-input">
                    </div>
                    <div class="cash-methods-period-actions">
                        <button type="submit" class="cash-methods-button">
                            <i class="fas fa-search"></i>
                            Consultar
                        </button>
                        <a href="<?= url('caja/reporte-metodos') ?>" class="cash-methods-link">
                            Limpiar
                        </a>
                    </div>
                </form>
            </aside>
        </section>

        <section class="cash-methods-summary" aria-label="Resumen general">
            <article class="cash-methods-card">
                <span class="cash-methods-label">Balance del periodo</span>
                <div class="cash-methods-value <?= $totales_generales['balance'] >= 0 ? 'is-income' : 'is-expense' ?>">
                    <?= caja_met_money($totales_generales['balance'], true) ?>
                </div>
                <p class="cash-methods-note">Ingresos menos salidas registradas.</p>
                <div class="cash-methods-flow" aria-label="Proporcion de ingresos y gastos">
                    <span style="width: <?= $ingresos_pct ?>%;"></span>
                    <span style="width: <?= $gastos_pct ?>%;"></span>
                </div>
            </article>
            <article class="cash-methods-card">
                <span class="cash-methods-label">Ingresos</span>
                <div class="cash-methods-value is-income"><?= caja_met_money($totales_generales['ingresos']) ?></div>
                <p class="cash-methods-note"><?= $ingresos_pct ?>% del flujo registrado.</p>
            </article>
            <article class="cash-methods-card">
                <span class="cash-methods-label">Gastos</span>
                <div class="cash-methods-value is-expense"><?= caja_met_money($totales_generales['gastos']) ?></div>
                <p class="cash-methods-note"><?= $gastos_pct ?>% del flujo registrado.</p>
            </article>
            <article class="cash-methods-card">
                <span class="cash-methods-label">Movimientos</span>
                <div class="cash-methods-value"><?= number_format($totales_generales['movimientos']) ?></div>
                <p class="cash-methods-note">Operaciones agrupadas por categoria.</p>
            </article>
        </section>

        <?php if ($sin_datos): ?>
            <section class="cash-methods-empty">
                <i class="fas fa-inbox"></i>
                <h2>No hay movimientos para este periodo</h2>
                <p>Cambia el rango de fechas o vuelve a caja para registrar ingresos y gastos del turno actual.</p>
            </section>
        <?php endif; ?>

        <section class="cash-methods-grid" aria-label="Resumen por metodo">
            <?php foreach ($metodos_resumen as $metodo_key => $metodo): ?>
                <?php
                $meta = $metodo['meta'];
                $method_class = caja_met_class($metodo_key);
                ?>
                <article class="cash-methods-method is-<?= caja_met_safe($method_class, 'metodo') ?>">
                    <div class="cash-methods-method-head">
                        <div class="cash-methods-method-title">
                            <span class="cash-methods-method-icon">
                                <i class="fas fa-<?= caja_met_safe($meta['icon'], 'circle') ?>"></i>
                            </span>
                            <div>
                                <h2><?= caja_met_safe($meta['label']) ?></h2>
                                <span class="cash-methods-note"><?= number_format($metodo['movimientos']) ?> movimientos</span>
                            </div>
                        </div>
                        <span class="cash-methods-pill"><?= caja_met_money($metodo['balance'], true) ?></span>
                    </div>

                    <div class="cash-methods-method-metrics">
                        <div class="cash-methods-mini">
                            <span>Ingresos</span>
                            <strong class="is-income"><?= caja_met_money($metodo['ingresos']) ?></strong>
                        </div>
                        <div class="cash-methods-mini">
                            <span>Gastos</span>
                            <strong class="is-expense"><?= caja_met_money($metodo['gastos']) ?></strong>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="cash-methods-details" aria-label="Detalle por categoria">
            <?php foreach ($metodos_resumen as $metodo_key => $metodo): ?>
                <?php
                $meta = $metodo['meta'];
                $ingresos_items = $metodo['ingresos_items'];
                $gastos_items = $metodo['gastos_items'];
                ?>
                <article class="cash-methods-panel">
                    <div class="cash-methods-panel-head">
                        <div>
                            <span class="cash-methods-section-label">Detalle</span>
                            <h3><?= caja_met_safe($meta['label']) ?></h3>
                        </div>
                        <span class="cash-methods-pill">
                            <i class="fas fa-<?= caja_met_safe($meta['icon'], 'circle') ?>"></i>
                            <?= number_format($metodo['movimientos']) ?>
                        </span>
                    </div>

                    <div class="cash-methods-panel-body">
                        <div class="cash-methods-group">
                            <div class="cash-methods-group-title">
                                <strong>Ingresos</strong>
                                <span class="cash-methods-row-amount is-income"><?= caja_met_money($metodo['ingresos']) ?></span>
                            </div>

                            <?php if (empty($ingresos_items)): ?>
                                <p class="cash-methods-note">Sin ingresos registrados.</p>
                            <?php else: ?>
                                <?php foreach ($ingresos_items as $item): ?>
                                    <div class="cash-methods-row">
                                        <div class="cash-methods-row-title">
                                            <strong><?= caja_met_safe($item['categoria'] ?? null, 'Sin categoria') ?></strong>
                                            <span><?= number_format((int)($item['cantidad'] ?? 0)) ?> movimientos</span>
                                        </div>
                                        <div class="cash-methods-row-amount is-income">
                                            <?= caja_met_money($item['total'] ?? 0) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="cash-methods-group">
                            <div class="cash-methods-group-title">
                                <strong>Gastos y egresos</strong>
                                <span class="cash-methods-row-amount is-expense"><?= caja_met_money($metodo['gastos']) ?></span>
                            </div>

                            <?php if (empty($gastos_items)): ?>
                                <p class="cash-methods-note">Sin gastos registrados.</p>
                            <?php else: ?>
                                <?php foreach ($gastos_items as $item): ?>
                                    <div class="cash-methods-row">
                                        <div class="cash-methods-row-title">
                                            <strong><?= caja_met_safe($item['categoria'] ?? null, 'Sin categoria') ?></strong>
                                            <span><?= number_format((int)($item['cantidad'] ?? 0)) ?> movimientos</span>
                                        </div>
                                        <div class="cash-methods-row-amount is-expense">
                                            <?= caja_met_money($item['total'] ?? 0) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </div>
</main>
