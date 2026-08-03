<?php
/**
 * Vista Detalle de Corte de Caja
 * Redisenada para el sistema hotelero operativo.
 */

if (!function_exists('cut_h')) {
    function cut_h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cut_money')) {
    function cut_money($value) {
        return '$' . number_format((float)$value, 2);
    }
}

if (!function_exists('cut_money_abs')) {
    function cut_money_abs($value, $prefix = '') {
        return $prefix . '$' . number_format(abs((float)$value), 2);
    }
}

if (!function_exists('cut_signed_money')) {
    function cut_signed_money($value) {
        $value = (float)$value;
        return ($value >= 0 ? '+' : '-') . '$' . number_format(abs($value), 2);
    }
}

if (!function_exists('cut_date_label')) {
    function cut_date_label($value, $fallback = 'Sin fecha') {
        if (empty($value)) {
            return $fallback;
        }
        $timestamp = strtotime((string)$value);
        if (!$timestamp) {
            return $fallback;
        }
        return date('d/m/Y H:i', $timestamp);
    }
}

if (!function_exists('cut_time_label')) {
    function cut_time_label($value, $fallback = '--:--') {
        if (empty($value)) {
            return $fallback;
        }
        $timestamp = strtotime((string)$value);
        if (!$timestamp) {
            return $fallback;
        }
        return date('H:i', $timestamp);
    }
}

if (!function_exists('cut_clean_color')) {
    function cut_clean_color($value, $fallback = '#64748B') {
        $value = trim((string)$value);
        if (preg_match('/^#[0-9A-Fa-f]{3}([0-9A-Fa-f]{3})?$/', $value)) {
            return $value;
        }
        return $fallback;
    }
}

$movimientos = is_array($movimientos ?? null) ? $movimientos : [];
$denominaciones = is_array($denominaciones ?? null) ? $denominaciones : [];

$total_ingresos = (float)($corte['total_ingresos_efectivo'] ?? 0)
    + (float)($corte['total_ingresos_tarjeta'] ?? 0)
    + (float)($corte['total_ingresos_transferencia'] ?? 0);

$total_gastos = (float)($corte['total_gastos_efectivo'] ?? 0)
    + (float)($corte['total_gastos_tarjeta'] ?? 0)
    + (float)($corte['total_gastos_transferencia'] ?? 0);

$balance_general = $total_ingresos - $total_gastos;
$efectivo_en_caja = (float)($corte['monto_inicial'] ?? 0)
    + (float)($corte['total_ingresos_efectivo'] ?? 0)
    - (float)($corte['total_gastos_efectivo'] ?? 0);

$diferencia = (float)($corte['diferencia'] ?? 0);
$esta_cerrado = (($corte['estado'] ?? '') === 'cerrado');
$estado_label = $esta_cerrado ? 'Cerrado' : 'Abierto';
$fecha_apertura_label = cut_date_label($corte['fecha_apertura'] ?? null);
$fecha_cierre_label = cut_date_label($corte['fecha_cierre'] ?? null, 'Sin cierre');
$hora_apertura_label = cut_time_label($corte['fecha_apertura'] ?? null);
$usuario_apertura = trim((string)($corte['usuario_apertura'] ?? '')) ?: 'Sin responsable';
$usuario_cierre = trim((string)($corte['usuario_cierre'] ?? '')) ?: 'Sin responsable';
$caja_nombre = trim((string)($corte['caja_nombre'] ?? '')) ?: 'Caja';
$observaciones_corte = trim((string)($corte['observaciones'] ?? ''));

$cats_ingreso = [];
$cats_gasto = [];
$movimientos_ingresos = 0;
$movimientos_gastos = 0;
$metodo_counts = ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0];

foreach ($movimientos as $mov) {
    $tipo = (string)($mov['tipo'] ?? '');
    $es_ingreso = ($tipo === 'ingreso');
    // Concepto humano con respaldo al texto del sistema (anticipos, cobros, reversos).
    $cat = CajaMovimientosFeed::concepto($mov);
    $icono = trim((string)($mov['categoria_icono'] ?? '')) ?: 'fas fa-tag';
    $color = cut_clean_color($mov['categoria_color'] ?? '#64748B');
    $monto = (float)($mov['monto'] ?? 0);
    $metodo = (string)($mov['metodo_pago'] ?? '');

    if (isset($metodo_counts[$metodo])) {
        $metodo_counts[$metodo]++;
    }

    if ($es_ingreso) {
        $movimientos_ingresos++;
        if (!isset($cats_ingreso[$cat])) {
            $cats_ingreso[$cat] = ['total' => 0, 'cantidad' => 0, 'icono' => $icono, 'color' => $color];
        }
        $cats_ingreso[$cat]['total'] += $monto;
        $cats_ingreso[$cat]['cantidad']++;
    } else {
        $movimientos_gastos++;
        if (!isset($cats_gasto[$cat])) {
            $cats_gasto[$cat] = ['total' => 0, 'cantidad' => 0, 'icono' => $icono, 'color' => $color];
        }
        $cats_gasto[$cat]['total'] += $monto;
        $cats_gasto[$cat]['cantidad']++;
    }
}

uasort($cats_ingreso, function ($a, $b) {
    return ($b['total'] <=> $a['total']);
});
uasort($cats_gasto, function ($a, $b) {
    return ($b['total'] <=> $a['total']);
});

$metodos_pago = [
    'efectivo' => [
        'label' => 'Efectivo',
        'note' => 'Dinero fisico en caja',
        'icon' => 'money-bill-wave',
        'tone' => 'cash',
        'ingresos' => (float)($corte['total_ingresos_efectivo'] ?? 0),
        'gastos' => (float)($corte['total_gastos_efectivo'] ?? 0),
        'count' => $metodo_counts['efectivo'],
    ],
    'tarjeta' => [
        'label' => 'Tarjeta',
        'note' => 'Cobros con terminal',
        'icon' => 'credit-card',
        'tone' => 'card',
        'ingresos' => (float)($corte['total_ingresos_tarjeta'] ?? 0),
        'gastos' => (float)($corte['total_gastos_tarjeta'] ?? 0),
        'count' => $metodo_counts['tarjeta'],
    ],
    'transferencia' => [
        'label' => 'Transferencia',
        'note' => 'Pagos bancarios',
        'icon' => 'exchange-alt',
        'tone' => 'transfer',
        'ingresos' => (float)($corte['total_ingresos_transferencia'] ?? 0),
        'gastos' => (float)($corte['total_gastos_transferencia'] ?? 0),
        'count' => $metodo_counts['transferencia'],
    ],
];

$total_denominaciones = 0;
foreach ($denominaciones as $den) {
    $total_denominaciones += (float)($den['denominacion'] ?? 0) * (int)($den['cantidad'] ?? 0);
}

$diff_label = 'Cuadrado';
$diff_class = 'is-neutral';
$diff_icon = 'equals';
if ($diferencia > 0) {
    $diff_label = 'Sobrante';
    $diff_class = 'is-positive';
    $diff_icon = 'arrow-up';
} elseif ($diferencia < 0) {
    $diff_label = 'Faltante';
    $diff_class = 'is-negative';
    $diff_icon = 'arrow-down';
}
?>

<style id="cash-cut-detail-redesign">
    @import url('<?= asset('vendor/fonts/marca.css') ?>');

    .cut-detail-page {
        --cut-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
        --cut-brand-deep: var(--brand-action-bg-hover, var(--brand-secondary, #0F172A));
        --cut-accent: var(--brand-accent, #BD9441);
        --cut-on-brand: var(--brand-action-text, #FFFEFB);
        --cut-bg: var(--brand-surface-soft, color-mix(in srgb, var(--cut-accent) 8%, #F5F5F7));
        --cut-paper: var(--brand-surface, #FFFEFB);
        --cut-soft: color-mix(in srgb, var(--cut-accent) 4%, #FFFFFF);
        --cut-line: var(--brand-border, color-mix(in srgb, var(--cut-accent) 24%, #E7DEC9));
        --cut-line-soft: color-mix(in srgb, var(--cut-brand) 6%, #ECE6DA);
        --cut-text: var(--brand-text, #172033);
        --cut-muted: var(--brand-muted, #6B7686);
        --cut-green: #18A667;
        --cut-green-soft: #E8F7F0;
        --cut-red: #D84A3F;
        --cut-red-soft: #FCEDEB;
        --cut-blue: #3B72D9;
        --cut-blue-soft: #EAF1FD;
        --cut-violet: #7863D8;
        --cut-violet-soft: #F0EDFC;
        --cut-shadow: 0 16px 42px -30px color-mix(in srgb, var(--cut-brand-deep) 34%, transparent);
        --cut-shadow-soft: 0 1px 2px rgba(15, 23, 42, .04), 0 18px 40px -34px rgba(15, 23, 42, .32);
        --cut-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        --cut-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100dvh;
        background:
            radial-gradient(900px 430px at 92% -8%, color-mix(in srgb, var(--cut-accent) 13%, transparent), transparent 62%),
            radial-gradient(620px 360px at 0% 10%, color-mix(in srgb, var(--cut-brand) 8%, transparent), transparent 58%),
            linear-gradient(180deg, color-mix(in srgb, var(--cut-bg) 72%, #FFFFFF), var(--cut-bg));
        color: var(--cut-text);
        font-family: var(--cut-sans);
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    .cut-detail-page *,
    .cut-detail-page *::before,
    .cut-detail-page *::after { box-sizing: border-box; }

    .cut-detail-page :where(a, button, input, p, span, small, strong, div, h1, h2, h3) { font-family: var(--cut-sans); }

    .cut-shell {
        width: min(100%, 1760px);
        margin: 0 auto;
        padding: clamp(18px, 2.2vw, 34px);
    }

    .cut-backbar {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 14px;
    }

    .cut-back {
        width: 42px;
        height: 42px;
        display: inline-grid;
        place-items: center;
        border-radius: 14px;
        border: 1px solid var(--cut-line);
        background: rgba(255,255,255,.84);
        color: var(--cut-brand-deep);
        text-decoration: none;
        box-shadow: var(--cut-shadow-soft);
        transition: transform .18s ease, border-color .18s ease, background .18s ease;
    }

    .cut-back:hover { transform: translateX(-2px); border-color: color-mix(in srgb, var(--cut-accent) 38%, var(--cut-line)); }

    .cut-breadcrumb {
        color: var(--cut-muted);
        font-size: .82rem;
        font-weight: 850;
    }

    .cut-breadcrumb strong { color: var(--cut-brand-deep); }

    .cut-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 24px;
        align-items: center;
        padding: clamp(24px, 3vw, 36px);
        border: 1px solid color-mix(in srgb, var(--cut-accent) 26%, rgba(255,255,255,.22));
        border-radius: 24px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--cut-brand-deep) 94%, #020617) 0%, var(--cut-brand-deep) 58%, color-mix(in srgb, var(--cut-brand) 78%, var(--cut-brand-deep)) 100%);
        color: var(--cut-on-brand);
        box-shadow: 0 28px 70px -42px color-mix(in srgb, var(--cut-brand-deep) 82%, transparent);
    }

    .cut-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(90deg, rgba(255,255,255,.08), transparent 48%),
            repeating-linear-gradient(135deg, rgba(255,255,255,.055) 0 1px, transparent 1px 18px);
        opacity: .46;
        pointer-events: none;
    }

    .cut-hero::after {
        content: '';
        position: absolute;
        right: -90px;
        top: -120px;
        width: 300px;
        height: 300px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--cut-accent) 20%, transparent);
        pointer-events: none;
    }

    .cut-hero > * { position: relative; z-index: 1; }

    .cut-hero-content {
        padding-left: clamp(18px, 2.4vw, 44px);
    }

    .cut-title-row {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .cut-mark {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 15px;
        background:
            linear-gradient(135deg, rgba(255,255,255,.24), rgba(255,255,255,.10)),
            color-mix(in srgb, var(--cut-accent) 42%, transparent);
        border: 1px solid color-mix(in srgb, var(--cut-on-brand) 30%, transparent);
        color: var(--cut-on-brand);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.22), 0 12px 26px -18px rgba(0,0,0,.55);
    }

    .cut-mark i {
        color: var(--cut-on-brand) !important;
        text-shadow: 0 1px 8px rgba(0,0,0,.28);
    }

    .cut-title {
        margin: 0;
        color: var(--cut-on-brand);
        font-family: var(--cut-serif) !important;
        font-size: clamp(2.25rem, 4vw, 4rem);
        font-weight: 700;
        line-height: .94;
        letter-spacing: 0;
    }

    .cut-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 30px;
        padding: 0 12px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--cut-green) 18%, rgba(255,255,255,.08));
        border: 1px solid color-mix(in srgb, var(--cut-green) 32%, rgba(255,255,255,.12));
        color: #DFFBEB;
        font-size: .78rem;
        font-weight: 900;
    }

    .cut-status.is-open {
        background: color-mix(in srgb, var(--cut-accent) 22%, rgba(255,255,255,.08));
        border-color: color-mix(in srgb, var(--cut-accent) 34%, rgba(255,255,255,.12));
        color: #FFF3D6;
    }

    .cut-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: currentColor;
    }

    .cut-hero-copy {
        max-width: 68ch;
        margin: 14px 0 0;
        color: color-mix(in srgb, var(--cut-on-brand) 72%, transparent);
        font-size: .94rem;
        font-weight: 650;
        line-height: 1.55;
    }

    .cut-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 18px;
    }

    .cut-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 12px 0 8px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        color: color-mix(in srgb, var(--cut-on-brand) 88%, transparent);
        font-size: .78rem;
        font-weight: 800;
        backdrop-filter: blur(10px);
    }

    .cut-meta-pill i {
        width: 22px;
        height: 22px;
        display: inline-grid;
        place-items: center;
        border-radius: 8px;
        background: color-mix(in srgb, var(--cut-on-brand) 16%, transparent);
        color: var(--cut-on-brand) !important;
        font-size: .72rem;
        text-shadow: 0 1px 6px rgba(0,0,0,.24);
    }

    .cut-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(132px, 1fr));
        gap: 10px;
        min-width: min(100%, 460px);
    }

    .cut-btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 15px;
        border-radius: 14px;
        border: 1px solid rgba(255,255,255,.14);
        background: rgba(255,255,255,.09);
        color: var(--cut-on-brand);
        font-size: .83rem;
        font-weight: 900;
        text-decoration: none;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease, border-color .18s ease;
    }

    .cut-btn:hover { transform: translateY(-1px); background: rgba(255,255,255,.14); }

    .cut-btn.is-primary {
        background: color-mix(in srgb, var(--cut-accent) 82%, #FFFFFF);
        border-color: transparent;
        color: color-mix(in srgb, var(--cut-brand-deep) 88%, #000000);
    }

    .cut-kpi-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin: 18px 0;
    }

    .cut-kpi {
        min-height: 146px;
        display: grid;
        align-content: space-between;
        gap: 18px;
        padding: 18px;
        border-radius: 19px;
        border: 1px solid var(--cut-line);
        background: linear-gradient(145deg, rgba(255,255,255,.96), color-mix(in srgb, var(--cut-soft) 76%, #FFFFFF));
        box-shadow: var(--cut-shadow-soft);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .cut-kpi:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--cut-accent) 34%, var(--cut-line)); box-shadow: var(--cut-shadow); }

    .cut-kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .cut-kpi span:first-child {
        color: var(--cut-muted);
        font-size: .72rem;
        font-weight: 900;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .cut-kpi-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: color-mix(in srgb, var(--cut-accent) 13%, #FFFFFF);
        color: color-mix(in srgb, var(--cut-accent) 76%, #3F2E12);
        border: 1px solid var(--cut-line-soft);
    }

    .cut-kpi-value {
        font-family: var(--cut-serif) !important;
        color: var(--cut-brand-deep);
        font-size: clamp(1.56rem, 2vw, 2.18rem);
        line-height: 1;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }

    .cut-kpi-value.is-income,
    .cut-money.is-income { color: var(--cut-green); }
    .cut-kpi-value.is-expense,
    .cut-money.is-expense { color: var(--cut-red); }
    .cut-kpi-value.is-blue { color: var(--cut-blue); }

    .cut-kpi-note {
        margin: 6px 0 0;
        color: var(--cut-muted);
        font-size: .78rem;
        font-weight: 700;
    }

    .cut-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(370px, .95fr);
        gap: 18px;
        align-items: start;
        margin-bottom: 18px;
    }

    .cut-lower-grid {
        display: grid;
        grid-template-columns: minmax(360px, .62fr) minmax(0, 1.38fr);
        gap: 18px;
        align-items: start;
    }

    .cut-stack { display: grid; gap: 18px; }

    .cut-panel {
        overflow: hidden;
        border-radius: 20px;
        border: 1px solid var(--cut-line);
        background: rgba(255,255,255,.92);
        box-shadow: var(--cut-shadow-soft);
    }

    .cut-panel-head {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 13px;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid var(--cut-line-soft);
        background: linear-gradient(120deg, color-mix(in srgb, var(--cut-accent) 5%, #FFFFFF), rgba(255,255,255,.94));
    }

    .cut-panel-icon {
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        border: 1px solid var(--cut-line-soft);
        background: color-mix(in srgb, var(--cut-accent) 12%, #FFFFFF);
        color: color-mix(in srgb, var(--cut-accent) 78%, #3F2E12);
    }

    .cut-panel-icon.is-income { background: var(--cut-green-soft); color: var(--cut-green); }
    .cut-panel-icon.is-expense { background: var(--cut-red-soft); color: var(--cut-red); }
    .cut-panel-icon.is-blue { background: var(--cut-blue-soft); color: var(--cut-blue); }

    .cut-panel-kicker {
        display: block;
        color: var(--cut-muted);
        font-size: .68rem;
        font-weight: 950;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cut-panel-title {
        margin: 3px 0 0;
        color: var(--cut-brand-deep);
        font-family: var(--cut-serif) !important;
        font-size: 1.34rem;
        line-height: 1.05;
        font-weight: 700;
        letter-spacing: 0;
    }

    .cut-panel-total {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 32px;
        padding: 0 10px;
        border-radius: 999px;
        background: var(--cut-soft);
        color: var(--cut-brand-deep);
        font-size: .82rem;
        font-weight: 950;
        white-space: nowrap;
    }

    .cut-panel-body { padding: 18px 20px 20px; }

    .cut-method-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr));
        gap: 14px;
    }

    .cut-method {
        display: grid;
        min-width: 0;
        gap: 12px;
        min-height: 204px;
        padding: 16px;
        border-radius: 16px;
        border: 1px solid var(--cut-line-soft);
        background: linear-gradient(145deg, #FFFFFF, color-mix(in srgb, var(--method-soft, var(--cut-soft)) 72%, #FFFFFF));
    }

    .cut-method.is-cash { --method-soft: var(--cut-green-soft); --method-color: var(--cut-green); }
    .cut-method.is-card { --method-soft: var(--cut-blue-soft); --method-color: var(--cut-blue); }
    .cut-method.is-transfer { --method-soft: var(--cut-violet-soft); --method-color: var(--cut-violet); }

    .cut-method-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 10px;
    }

    .cut-method-name {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        color: var(--cut-brand-deep);
        font-size: .9rem;
        line-height: 1.25;
        font-weight: 950;
    }

    .cut-method-name i {
        width: 32px;
        height: 32px;
        display: inline-grid;
        place-items: center;
        flex: 0 0 32px;
        border-radius: 10px;
        background: var(--method-soft);
        color: var(--method-color);
    }

    .cut-method-count {
        color: var(--cut-muted);
        font-size: .72rem;
        font-weight: 850;
        white-space: nowrap;
    }

    .cut-method-note {
        margin: -4px 0 0;
        color: var(--cut-muted);
        font-size: .78rem;
        line-height: 1.4;
        font-weight: 700;
    }

    .cut-method-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: baseline;
        gap: 12px;
        min-width: 0;
        padding-top: 9px;
        border-top: 1px solid color-mix(in srgb, var(--method-color) 14%, #E8EDF4);
        color: var(--cut-muted);
        font-size: .82rem;
        font-weight: 780;
    }

    .cut-method-row strong {
        min-width: 0;
        text-align: right;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        font-weight: 950;
    }

    .cut-method-bar {
        overflow: hidden;
        display: grid;
        grid-template-columns: var(--cash-pct, 0fr) var(--card-pct, 0fr) var(--transfer-pct, 0fr);
        height: 12px;
        margin-bottom: 16px;
        border-radius: 999px;
        background: var(--cut-line-soft);
    }

    .cut-method-bar span:nth-child(1) { background: var(--cut-green); }
    .cut-method-bar span:nth-child(2) { background: var(--cut-blue); }
    .cut-method-bar span:nth-child(3) { background: var(--cut-violet); }

    .cut-audit-card {
        display: grid;
        gap: 14px;
    }

    .cut-audit-line {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 13px 14px;
        border-radius: 14px;
        background: var(--cut-soft);
        border: 1px solid var(--cut-line-soft);
    }

    .cut-audit-line span {
        color: var(--cut-muted);
        font-size: .82rem;
        font-weight: 850;
    }

    .cut-audit-line strong {
        color: var(--cut-brand-deep);
        font-size: .98rem;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
    }

    .cut-diff {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 15px;
        border-radius: 16px;
        border: 1px solid var(--cut-line-soft);
        background: #F8FAFC;
    }

    .cut-diff.is-positive { background: var(--cut-green-soft); border-color: color-mix(in srgb, var(--cut-green) 26%, #DDEFE6); }
    .cut-diff.is-negative { background: var(--cut-red-soft); border-color: color-mix(in srgb, var(--cut-red) 26%, #F1D6D2); }

    .cut-diff-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: rgba(255,255,255,.72);
        color: var(--cut-brand-deep);
    }

    .cut-diff.is-positive .cut-diff-icon,
    .cut-diff.is-positive .cut-diff-value { color: var(--cut-green); }
    .cut-diff.is-negative .cut-diff-icon,
    .cut-diff.is-negative .cut-diff-value { color: var(--cut-red); }

    .cut-diff-title { color: var(--cut-brand-deep); font-weight: 950; }
    .cut-diff-sub { display: block; color: var(--cut-muted); font-size: .76rem; font-weight: 760; }
    .cut-diff-value { font-family: var(--cut-serif) !important; font-size: 1.45rem; font-weight: 700; font-variant-numeric: tabular-nums; }

    .cut-note {
        padding: 14px;
        border-radius: 14px;
        border: 1px solid color-mix(in srgb, var(--cut-accent) 28%, var(--cut-line));
        background: color-mix(in srgb, var(--cut-accent) 11%, #FFFFFF);
    }

    .cut-note strong {
        display: flex;
        align-items: center;
        gap: 8px;
        color: color-mix(in srgb, var(--cut-accent) 72%, #5B3C08);
        font-size: .78rem;
        font-weight: 950;
        margin-bottom: 6px;
    }

    .cut-note p { margin: 0; color: var(--cut-text); font-size: .86rem; font-weight: 700; line-height: 1.45; }

    .cut-note--wide {
        margin-top: 18px;
        margin-bottom: 18px;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 12px;
        align-items: start;
    }

    .cut-note--wide .cut-note-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: rgba(255,255,255,.72);
        color: color-mix(in srgb, var(--cut-accent) 76%, #5B3C08);
    }

    .cut-note--wide strong { margin-bottom: 4px; }

    .cut-denom-list { display: grid; gap: 8px; }

    .cut-denom-row {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 14px;
        align-items: center;
        padding: 11px 12px;
        border-radius: 12px;
        border: 1px solid var(--cut-line-soft);
        background: #FFFFFF;
        color: var(--cut-text);
        font-size: .86rem;
        font-weight: 850;
    }

    .cut-denom-row small { color: var(--cut-muted); font-weight: 800; }
    .cut-denom-row strong { color: var(--cut-brand-deep); font-variant-numeric: tabular-nums; }

    .cut-denom-total {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-top: 10px;
        padding: 13px 14px;
        border-radius: 14px;
        background: color-mix(in srgb, var(--cut-brand) 8%, #FFFFFF);
        color: var(--cut-brand-deep);
        font-weight: 950;
    }

    .cut-cat-list { display: grid; gap: 10px; }

    .cut-cat {
        --cat-color: #64748B;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: 13px;
        border-radius: 15px;
        border: 1px solid var(--cut-line-soft);
        background: #FFFFFF;
        transition: transform .18s ease, border-color .18s ease, background .18s ease;
    }

    .cut-cat:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--cat-color) 24%, var(--cut-line));
        background: color-mix(in srgb, var(--cat-color) 5%, #FFFFFF);
    }

    .cut-cat-icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: color-mix(in srgb, var(--cat-color) 14%, #FFFFFF);
        color: var(--cat-color);
    }

    .cut-cat-title { display: block; color: var(--cut-text); font-size: .9rem; font-weight: 950; }
    .cut-cat-sub { display: block; color: var(--cut-muted); font-size: .76rem; font-weight: 760; }
    .cut-cat-total { font-weight: 950; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .cut-search {
        position: relative;
        padding: 14px 18px;
        border-bottom: 1px solid var(--cut-line-soft);
        background: color-mix(in srgb, var(--cut-soft) 80%, #FFFFFF);
    }

    .cut-search i {
        position: absolute;
        left: 32px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--cut-muted);
        font-size: .82rem;
    }

    .cut-search input {
        width: 100%;
        min-height: 44px;
        padding: 0 16px 0 42px;
        border-radius: 14px;
        border: 1px solid var(--cut-line);
        background: #FFFFFF;
        color: var(--cut-text);
        font-size: .88rem;
        font-weight: 760;
        outline: none;
        transition: border-color .18s ease, box-shadow .18s ease;
    }

    .cut-search input:focus {
        border-color: var(--cut-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cut-accent) 24%, transparent);
    }

    .cut-movement-list {
        display: grid;
        gap: 10px;
        max-height: 720px;
        overflow-y: auto;
        padding: 14px;
        scrollbar-color: color-mix(in srgb, var(--cut-accent) 50%, #FFFFFF) transparent;
    }

    .cut-movement-list::-webkit-scrollbar { width: 6px; }
    .cut-movement-list::-webkit-scrollbar-track { background: transparent; }
    .cut-movement-list::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--cut-accent) 44%, #FFFFFF); border-radius: 99px; }

    .cut-movement {
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        gap: 13px;
        align-items: start;
        padding: 14px;
        border-radius: 16px;
        border: 1px solid var(--cut-line-soft);
        background: #FFFFFF;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .cut-movement:hover { transform: translateY(-1px); border-color: var(--cut-line); box-shadow: var(--cut-shadow-soft); }

    .cut-movement-mark {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: var(--cut-red-soft);
        color: var(--cut-red);
    }

    .cut-movement.is-income .cut-movement-mark { background: var(--cut-green-soft); color: var(--cut-green); }

    .cut-movement-top {
        display: flex;
        align-items: center;
        gap: 7px;
        flex-wrap: wrap;
        margin-bottom: 6px;
    }

    .cut-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 24px;
        padding: 0 8px;
        border-radius: 999px;
        background: var(--cut-soft);
        color: var(--cut-muted);
        font-size: .68rem;
        font-weight: 950;
    }

    .cut-chip.is-income { background: var(--cut-green-soft); color: var(--cut-green); }
    .cut-chip.is-expense { background: var(--cut-red-soft); color: var(--cut-red); }

    .cut-movement-title {
        margin: 0;
        color: var(--cut-text);
        font-size: .94rem;
        font-weight: 950;
        line-height: 1.35;
    }

    .cut-movement-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 8px;
        color: var(--cut-muted);
        font-size: .75rem;
        font-weight: 780;
    }

    .cut-movement-meta a,
    .cut-movement-meta span { display: inline-flex; align-items: center; gap: 6px; }
    .cut-movement-meta a { color: var(--cut-brand); text-decoration: none; font-weight: 900; }
    .cut-movement-meta a:hover { text-decoration: underline; }

    .cut-movement-amount {
        align-self: center;
        font-size: 1rem;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        color: var(--cut-red);
    }

    .cut-movement.is-income .cut-movement-amount { color: var(--cut-green); }

    .cut-empty {
        display: grid;
        place-items: center;
        min-height: 180px;
        padding: 24px;
        text-align: center;
        color: var(--cut-muted);
    }

    .cut-empty i {
        width: 48px;
        height: 48px;
        display: grid;
        place-items: center;
        margin: 0 auto 10px;
        border-radius: 16px;
        background: var(--cut-soft);
        color: color-mix(in srgb, var(--cut-brand) 46%, var(--cut-muted));
    }

    .cut-empty strong { display: block; color: var(--cut-text); font-weight: 950; }
    .cut-empty p { margin: 5px 0 0; font-size: .86rem; font-weight: 720; }

    .cut-shortcuts {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .cut-shortcut {
        flex: 1 1 150px;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 12px;
        border-radius: 13px;
        border: 1px solid var(--cut-line-soft);
        background: var(--cut-soft);
        color: var(--cut-brand-deep);
        text-decoration: none;
        font-size: .82rem;
        font-weight: 900;
        transition: transform .18s ease, border-color .18s ease, background .18s ease;
    }

    .cut-shortcut:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--cut-accent) 34%, var(--cut-line)); background: color-mix(in srgb, var(--cut-accent) 8%, #FFFFFF); }

    .cut-detail-page a:focus-visible,
    .cut-detail-page button:focus-visible,
    .cut-detail-page input:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cut-accent) 30%, transparent);
    }

    @media (max-width: 1320px) {
        .cut-kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .cut-grid,
        .cut-lower-grid { grid-template-columns: 1fr; }
        .cut-actions { grid-template-columns: repeat(3, minmax(0, 1fr)); width: 100%; }
        .cut-hero { grid-template-columns: 1fr; }
        .cut-hero-content { padding-left: clamp(10px, 2vw, 18px); }
    }

    @media (max-width: 900px) {
        .cut-shell { padding: 16px 12px 34px; }
        .cut-kpi-grid,
        .cut-method-grid { grid-template-columns: 1fr; }
        .cut-actions { grid-template-columns: 1fr; }
        .cut-panel-head { grid-template-columns: auto minmax(0, 1fr); }
        .cut-panel-total { grid-column: 1 / -1; justify-self: start; }
        .cut-movement { grid-template-columns: 40px minmax(0, 1fr); }
        .cut-movement-amount { grid-column: 2; justify-self: start; }
        .cut-title { font-size: clamp(2.1rem, 12vw, 3rem); }
    }

    @media (max-width: 560px) {
        .cut-hero,
        .cut-panel-body { padding: 16px; }
        .cut-hero-content { padding-left: 8px; }
        .cut-panel-head { padding: 15px 16px; }
        .cut-kpi { min-height: 128px; }
        .cut-denom-row { grid-template-columns: 1fr auto; }
        .cut-denom-row strong { grid-column: 1 / -1; }
    }

    @media print {
        .no-print,
        .cut-backbar,
        .cut-search,
        .cut-shortcuts { display: none !important; }
        .cut-detail-page { background: #FFFFFF !important; }
        .cut-shell { width: 100%; padding: 0; }
        .cut-hero,
        .cut-panel,
        .cut-kpi,
        .cut-method,
        .cut-movement { box-shadow: none !important; }
        .cut-movement-list { max-height: none; overflow: visible; }
    }
</style>

<div class="cut-detail-page">
    <main class="cut-shell">
        <nav class="cut-backbar no-print" aria-label="Navegacion de corte">
            <?php $back_arrow_href = back_url('caja/historial'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('caja/historial') ?>" class="cut-back ms-back-legacy" title="Volver">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="cut-breadcrumb">Caja / Historial / <strong>Corte #<?= (int)($corte['id'] ?? 0) ?></strong></span>
        </nav>

        <header class="cut-hero" aria-label="Resumen principal del corte">
            <div class="cut-hero-content">
                <div class="cut-title-row">
                    <span class="cut-mark"><i class="fas fa-file-invoice-dollar"></i></span>
                    <h1 class="cut-title">Corte #<?= (int)($corte['id'] ?? 0) ?></h1>
                    <span class="cut-status <?= $esta_cerrado ? '' : 'is-open' ?>">
                        <span class="cut-status-dot"></span>
                        <?= cut_h($estado_label) ?>
                    </span>
                </div>
                <p class="cut-hero-copy">
                    Lectura completa del turno de caja: dinero inicial, movimientos, metodos de pago, arqueo y actividad registrada.
                </p>
                <div class="cut-meta" aria-label="Datos del corte">
                    <span class="cut-meta-pill"><i class="fas fa-cash-register"></i><?= cut_h($caja_nombre) ?></span>
                    <span class="cut-meta-pill"><i class="fas fa-calendar-alt"></i><?= cut_h($fecha_apertura_label) ?></span>
                    <span class="cut-meta-pill"><i class="fas fa-user"></i><?= cut_h($usuario_apertura) ?></span>
                    <?php if ($esta_cerrado): ?>
                        <span class="cut-meta-pill"><i class="fas fa-calendar-check"></i><?= cut_h($fecha_cierre_label) ?></span>
                        <span class="cut-meta-pill"><i class="fas fa-user-check"></i>Cerró: <?= cut_h($usuario_cierre) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cut-actions no-print" aria-label="Acciones del corte">
                <a href="<?= back_url('caja/historial') ?>" class="cut-btn"><i class="fas fa-arrow-left"></i>Volver</a>
                <a href="<?= url('caja/descargar-pdf/' . (int)($corte['id'] ?? 0)) ?>" class="cut-btn is-primary"><i class="fas fa-file-pdf"></i>Descargar PDF</a>
                <button type="button" onclick="window.print()" class="cut-btn ms-print-hide-mobile"><i class="fas fa-print"></i>Imprimir</button>
            </div>
        </header>

        <section class="cut-kpi-grid" aria-label="Indicadores del corte">
            <article class="cut-kpi">
                <div class="cut-kpi-top"><span>Monto inicial</span><span class="cut-kpi-icon"><i class="fas fa-wallet"></i></span></div>
                <div><div class="cut-kpi-value"><?= cut_money($corte['monto_inicial'] ?? 0) ?></div><p class="cut-kpi-note">Base de apertura, <?= cut_h($hora_apertura_label) ?> hrs</p></div>
            </article>
            <article class="cut-kpi">
                <div class="cut-kpi-top"><span>Ingresos</span><span class="cut-kpi-icon"><i class="fas fa-arrow-trend-up"></i></span></div>
                <div><div class="cut-kpi-value is-income"><?= cut_money_abs($total_ingresos, '+') ?></div><p class="cut-kpi-note"><?= number_format($movimientos_ingresos) ?> movimientos registrados</p></div>
            </article>
            <article class="cut-kpi">
                <div class="cut-kpi-top"><span>Gastos</span><span class="cut-kpi-icon"><i class="fas fa-arrow-trend-down"></i></span></div>
                <div><div class="cut-kpi-value is-expense"><?= cut_money_abs($total_gastos, '-') ?></div><p class="cut-kpi-note"><?= number_format($movimientos_gastos) ?> salidas registradas</p></div>
            </article>
            <article class="cut-kpi">
                <div class="cut-kpi-top"><span>Efectivo fisico</span><span class="cut-kpi-icon"><i class="fas fa-coins"></i></span></div>
                <div><div class="cut-kpi-value is-blue"><?= $efectivo_en_caja < 0 ? '-' : '' ?><?= cut_money(abs($efectivo_en_caja)) ?></div><p class="cut-kpi-note">Inicial + ingresos - gastos en efectivo</p></div>
            </article>
            <article class="cut-kpi">
                <div class="cut-kpi-top"><span>Balance</span><span class="cut-kpi-icon"><i class="fas fa-scale-balanced"></i></span></div>
                <div><div class="cut-kpi-value <?= $balance_general >= 0 ? 'is-income' : 'is-expense' ?>"><?= cut_signed_money($balance_general) ?></div><p class="cut-kpi-note">Todos los metodos de pago</p></div>
            </article>
        </section>

        <?php if ($observaciones_corte !== ''): ?>
            <section class="cut-note cut-note--wide" aria-label="Observaciones del corte">
                <span class="cut-note-icon"><i class="fas fa-sticky-note"></i></span>
                <div>
                    <strong>Observaciones del cierre</strong>
                    <p><?= nl2br(cut_h($observaciones_corte)) ?></p>
                </div>
            </section>
        <?php endif; ?>

        <section class="cut-grid" aria-label="Metodos y arqueo">
            <article class="cut-panel">
                <div class="cut-panel-head">
                    <span class="cut-panel-icon"><i class="fas fa-credit-card"></i></span>
                    <div>
                        <span class="cut-panel-kicker">Distribucion</span>
                        <h2 class="cut-panel-title">Metodos de pago</h2>
                    </div>
                    <span class="cut-panel-total"><?= number_format(count($movimientos)) ?> movimientos</span>
                </div>
                <div class="cut-panel-body">
                    <?php if ($total_ingresos > 0): ?>
                        <div class="cut-method-bar" style="--cash-pct: <?= max(0.01, (($metodos_pago['efectivo']['ingresos'] / $total_ingresos) * 100)) ?>fr; --card-pct: <?= max(0.01, (($metodos_pago['tarjeta']['ingresos'] / $total_ingresos) * 100)) ?>fr; --transfer-pct: <?= max(0.01, (($metodos_pago['transferencia']['ingresos'] / $total_ingresos) * 100)) ?>fr;" aria-label="Distribucion de ingresos por metodo">
                            <span></span><span></span><span></span>
                        </div>
                    <?php endif; ?>
                    <div class="cut-method-grid">
                        <?php foreach ($metodos_pago as $key => $method): ?>
                            <?php $method_balance = $method['ingresos'] - $method['gastos']; ?>
                            <article class="cut-method is-<?= cut_h($method['tone']) ?>">
                                <div class="cut-method-head">
                                    <div class="cut-method-name"><i class="fas fa-<?= cut_h($method['icon']) ?>"></i><?= cut_h($method['label']) ?></div>
                                    <span class="cut-method-count"><?= number_format($method['count']) ?> mov.</span>
                                </div>
                                <p class="cut-method-note"><?= cut_h($method['note']) ?></p>
                                <div class="cut-method-row"><span>Ingresos</span><strong class="cut-money is-income"><?= cut_money_abs($method['ingresos'], '+') ?></strong></div>
                                <div class="cut-method-row"><span>Gastos</span><strong class="cut-money is-expense"><?= cut_money_abs($method['gastos'], '-') ?></strong></div>
                                <div class="cut-method-row"><span>Balance</span><strong class="cut-money <?= $method_balance >= 0 ? 'is-income' : 'is-expense' ?>"><?= cut_signed_money($method_balance) ?></strong></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <aside class="cut-stack">
                <article class="cut-panel">
                    <div class="cut-panel-head">
                        <span class="cut-panel-icon is-blue"><i class="fas fa-calculator"></i></span>
                        <div>
                            <span class="cut-panel-kicker">Arqueo</span>
                            <h2 class="cut-panel-title"><?= $esta_cerrado ? 'Cierre de caja' : 'Caja abierta' ?></h2>
                        </div>
                        <span class="cut-panel-total"><?= cut_h($estado_label) ?></span>
                    </div>
                    <div class="cut-panel-body cut-audit-card">
                        <div class="cut-audit-line"><span>Efectivo esperado</span><strong><?= cut_money($efectivo_en_caja) ?></strong></div>
                        <div class="cut-audit-line"><span>Efectivo contado</span><strong><?= $esta_cerrado ? cut_money($corte['efectivo_contado'] ?? 0) : 'Pendiente' ?></strong></div>
                        <div class="cut-diff <?= cut_h($diff_class) ?>">
                            <span class="cut-diff-icon"><i class="fas fa-<?= cut_h($diff_icon) ?>"></i></span>
                            <span><strong class="cut-diff-title"><?= cut_h($diff_label) ?></strong><small class="cut-diff-sub">Diferencia de arqueo</small></span>
                            <strong class="cut-diff-value"><?= cut_signed_money($diferencia) ?></strong>
                        </div>
                    </div>
                </article>

                <article class="cut-panel">
                    <div class="cut-panel-head">
                        <span class="cut-panel-icon"><i class="fas fa-money-bill-alt"></i></span>
                        <div>
                            <span class="cut-panel-kicker">Efectivo</span>
                            <h2 class="cut-panel-title">Denominaciones</h2>
                        </div>
                        <span class="cut-panel-total"><?= cut_money($total_denominaciones) ?></span>
                    </div>
                    <div class="cut-panel-body">
                        <?php if (empty($denominaciones)): ?>
                            <div class="cut-empty">
                                <div><i class="fas fa-money-bill-wave"></i><strong>Sin denominaciones</strong><p>No se registraron billetes o monedas para este corte.</p></div>
                            </div>
                        <?php else: ?>
                            <div class="cut-denom-list">
                                <?php foreach ($denominaciones as $den): ?>
                                    <?php $subtotal = (float)($den['denominacion'] ?? 0) * (int)($den['cantidad'] ?? 0); ?>
                                    <div class="cut-denom-row">
                                        <span><?= cut_money($den['denominacion'] ?? 0) ?></span>
                                        <small>x <?= number_format((int)($den['cantidad'] ?? 0)) ?></small>
                                        <strong><?= cut_money($subtotal) ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="cut-denom-total"><span>Total contado</span><strong><?= cut_money($total_denominaciones) ?></strong></div>
                        <?php endif; ?>
                    </div>
                </article>
            </aside>
        </section>

        <section class="cut-lower-grid" aria-label="Categorias y movimientos">
            <div class="cut-stack">
                <article class="cut-panel">
                    <div class="cut-panel-head">
                        <span class="cut-panel-icon is-income"><i class="fas fa-chart-pie"></i></span>
                        <div>
                            <span class="cut-panel-kicker">Ingresos</span>
                            <h2 class="cut-panel-title">Por categoria</h2>
                        </div>
                        <span class="cut-panel-total cut-money is-income"><?= cut_money_abs($total_ingresos, '+') ?></span>
                    </div>
                    <div class="cut-panel-body">
                        <?php if (empty($cats_ingreso)): ?>
                            <div class="cut-empty"><div><i class="fas fa-inbox"></i><strong>Sin ingresos</strong><p>No hay ingresos registrados en este corte.</p></div></div>
                        <?php else: ?>
                            <div class="cut-cat-list">
                                <?php foreach ($cats_ingreso as $nombre => $cat): ?>
                                    <article class="cut-cat" style="--cat-color: <?= cut_h(cut_clean_color($cat['color'] ?? '#64748B')) ?>;">
                                        <span class="cut-cat-icon"><i class="<?= cut_h($cat['icono'] ?? 'fas fa-tag') ?>"></i></span>
                                        <span><strong class="cut-cat-title"><?= cut_h($nombre) ?></strong><small class="cut-cat-sub"><?= number_format($cat['cantidad']) ?> movimiento<?= $cat['cantidad'] != 1 ? 's' : '' ?></small></span>
                                        <strong class="cut-cat-total cut-money is-income"><?= cut_money_abs($cat['total'], '+') ?></strong>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="cut-panel">
                    <div class="cut-panel-head">
                        <span class="cut-panel-icon is-expense"><i class="fas fa-chart-pie"></i></span>
                        <div>
                            <span class="cut-panel-kicker">Gastos</span>
                            <h2 class="cut-panel-title">Por categoria</h2>
                        </div>
                        <span class="cut-panel-total cut-money is-expense"><?= cut_money_abs($total_gastos, '-') ?></span>
                    </div>
                    <div class="cut-panel-body">
                        <?php if (empty($cats_gasto)): ?>
                            <div class="cut-empty"><div><i class="fas fa-inbox"></i><strong>Sin gastos</strong><p>No hay gastos registrados en este corte.</p></div></div>
                        <?php else: ?>
                            <div class="cut-cat-list">
                                <?php foreach ($cats_gasto as $nombre => $cat): ?>
                                    <article class="cut-cat" style="--cat-color: <?= cut_h(cut_clean_color($cat['color'] ?? '#64748B')) ?>;">
                                        <span class="cut-cat-icon"><i class="<?= cut_h($cat['icono'] ?? 'fas fa-tag') ?>"></i></span>
                                        <span><strong class="cut-cat-title"><?= cut_h($nombre) ?></strong><small class="cut-cat-sub"><?= number_format($cat['cantidad']) ?> movimiento<?= $cat['cantidad'] != 1 ? 's' : '' ?></small></span>
                                        <strong class="cut-cat-total cut-money is-expense"><?= cut_money_abs($cat['total'], '-') ?></strong>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="cut-panel no-print">
                    <div class="cut-panel-head">
                        <span class="cut-panel-icon"><i class="fas fa-link"></i></span>
                        <div>
                            <span class="cut-panel-kicker">Caja</span>
                            <h2 class="cut-panel-title">Accesos rapidos</h2>
                        </div>
                    </div>
                    <div class="cut-panel-body">
                        <nav class="cut-shortcuts" aria-label="Accesos rapidos de caja">
                            <a href="<?= url('caja') ?>" class="cut-shortcut"><i class="fas fa-cash-register"></i>Caja</a>
                            <a href="<?= url('caja/historial') ?>" class="cut-shortcut"><i class="fas fa-history"></i>Historial</a>
                            <a href="<?= url('caja/movimientos') ?>" class="cut-shortcut"><i class="fas fa-list"></i>Movimientos</a>
                            <a href="<?= url('caja/reporte-metodos') ?>" class="cut-shortcut"><i class="fas fa-credit-card"></i>Metodos</a>
                        </nav>
                    </div>
                </article>
            </div>

            <article class="cut-panel">
                <div class="cut-panel-head">
                    <span class="cut-panel-icon"><i class="fas fa-list"></i></span>
                    <div>
                        <span class="cut-panel-kicker">Actividad</span>
                        <h2 class="cut-panel-title">Movimientos del corte</h2>
                    </div>
                    <span class="cut-panel-total"><?= number_format(count($movimientos)) ?> registros</span>
                </div>
                <?php if (empty($movimientos)): ?>
                    <div class="cut-empty"><div><i class="fas fa-receipt"></i><strong>Sin movimientos</strong><p>No hay movimientos en este corte.</p></div></div>
                <?php else: ?>
                    <div class="cut-search no-print">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscarMovCorte" placeholder="Buscar por descripcion, categoria, usuario o metodo..." autocomplete="off">
                    </div>
                    <div class="cut-movement-list" id="listaMovCorte">
                        <?php foreach ($movimientos as $mov): ?>
                            <?php
                            $es_ingreso = (($mov['tipo'] ?? '') === 'ingreso');
                            $metodo_key = (string)($mov['metodo_pago'] ?? '');
                            $method_info = $metodos_pago[$metodo_key] ?? ['label' => ucfirst($metodo_key ?: 'Metodo'), 'icon' => 'circle'];
                            $origen_ingreso = null;
                            if ($es_ingreso) {
                                $origen_ingreso = empty($mov['reservacion_id'])
                                    ? ['label' => 'Ingreso manual', 'icon' => 'keyboard']
                                    : ['label' => 'Renta habitacion', 'icon' => 'bed'];
                            }
                            $texto_busqueda = trim(implode(' ', [
                                $mov['descripcion'] ?? '',
                                CajaMovimientosFeed::concepto($mov),
                                $mov['usuario_nombre'] ?? '',
                                $mov['metodo_pago'] ?? '',
                                $origen_ingreso['label'] ?? '',
                                $mov['referencia'] ?? '',
                            ]));
                            ?>
                            <article class="cut-movement <?= $es_ingreso ? 'is-income' : 'is-expense' ?>" data-search="<?= cut_h(strtolower($texto_busqueda)) ?>">
                                <span class="cut-movement-mark"><i class="fas fa-arrow-<?= $es_ingreso ? 'down' : 'up' ?>"></i></span>
                                <div>
                                    <div class="cut-movement-top">
                                        <span class="cut-chip <?= $es_ingreso ? 'is-income' : 'is-expense' ?>"><i class="fas fa-arrow-<?= $es_ingreso ? 'down' : 'up' ?>"></i><?= $es_ingreso ? 'Ingreso' : 'Gasto' ?></span>
                                        <?php if ($origen_ingreso): ?>
                                            <span class="cut-chip"><i class="fas fa-<?= cut_h($origen_ingreso['icon']) ?>"></i><?= cut_h($origen_ingreso['label']) ?></span>
                                        <?php endif; ?>
                                        <span class="cut-chip"><i class="fas fa-<?= cut_h($method_info['icon'] ?? 'circle') ?>"></i><?= cut_h($method_info['label'] ?? ucfirst($metodo_key)) ?></span>
                                    </div>
                                    <h3 class="cut-movement-title"><?= cut_h(CajaMovimientosFeed::humanizar($mov['descripcion'] ?? '') ?: 'Movimiento sin descripcion') ?></h3>
                                    <div class="cut-movement-meta">
                                        <span><i class="far fa-clock"></i><?= cut_h(cut_date_label($mov['created_at'] ?? null)) ?></span>
                                        <?php if (!empty($mov['usuario_nombre'])): ?><span><i class="fas fa-user"></i><?= cut_h($mov['usuario_nombre']) ?></span><?php endif; ?>
                                        <span><i class="<?= cut_h($mov['categoria_icono'] ?? 'fas fa-tag') ?>" style="color: <?= cut_h(cut_clean_color($mov['categoria_color'] ?? '#64748B')) ?>"></i><?= cut_h(CajaMovimientosFeed::concepto($mov)) ?></span>
                                        <?php if (!empty($mov['referencia'])): ?><span><i class="fas fa-hashtag"></i><?= cut_h($mov['referencia']) ?></span><?php endif; ?>
                                        <?php if (!empty($mov['reservacion_id'])): ?><a href="<?= url('reservaciones/ver/' . (int)$mov['reservacion_id']) ?>"><i class="fas fa-bed"></i>Reserva #<?= (int)$mov['reservacion_id'] ?></a><?php endif; ?>
                                        <?php if (!empty($mov['habitaciones_detalle'])): ?><span><i class="fas fa-door-open"></i><?= cut_h($mov['habitaciones_detalle']) ?></span><?php endif; ?>
                                        <?php if (!empty($mov['editado'])): ?><span><i class="fas fa-pen"></i>Editado<?= !empty($mov['motivo_edicion']) ? ': ' . cut_h($mov['motivo_edicion']) : '' ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <strong class="cut-movement-amount"><?= $es_ingreso ? '+' : '-' ?><?= cut_money($mov['monto'] ?? 0) ?></strong>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>
    </main>
</div>

<script>
document.getElementById('buscarMovCorte')?.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#listaMovCorte .cut-movement').forEach(row => {
        const text = row.dataset.search || '';
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
});
</script>
