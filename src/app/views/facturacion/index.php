<?php
header('Content-Type: text/html; charset=UTF-8');

$solicitudes = $solicitudes ?? [];
$estadisticas = $estadisticas ?? [];
$mensaje = get_mensaje();

$buscar = $buscar ?? '';
$filtro_tipo = $filtro_tipo ?? '';
$filtro_estatus = $filtro_estatus ?? '';
$fecha_desde = $fecha_desde ?? '';

$pagina_actual = max(1, (int)($pagina_actual ?? 1));
$total_paginas = max(1, (int)($total_paginas ?? 1));
$total_registros = (int)($total_registros ?? count($solicitudes));
$billingVisibles = count($solicitudes);

if (!function_exists('billing_safe')) {
    function billing_safe($value, string $fallback = '-'): string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            $value = $fallback;
        }

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('billing_money')) {
    function billing_money($amount): string
    {
        return '$' . number_format((float)($amount ?? 0), 2);
    }
}

if (!function_exists('billing_date')) {
    function billing_date($date, string $format = 'd/m/Y'): string
    {
        if (empty($date)) {
            return '-';
        }

        $timestamp = strtotime((string)$date);

        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('billing_initial')) {
    function billing_initial($name): string
    {
        $name = trim((string)($name ?? ''));
        if ($name === '') {
            return 'F';
        }

        return strtoupper(substr($name, 0, 1));
    }
}

if (!function_exists('billing_page_url')) {
    function billing_page_url(int $page): string
    {
        $query = array_merge($_GET, ['page' => $page]);
        $query = array_filter($query, static function ($value) {
            return $value !== null && $value !== '';
        });

        return url('facturacion' . ($query ? '?' . http_build_query($query) : ''));
    }
}

$statusLabels = [
    'pendiente' => 'Pendiente',
    'en_proceso' => 'En proceso',
    'procesando' => 'En proceso',
    'facturada' => 'Facturada',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
    'rechazada' => 'Rechazada',
];

$tipoLabels = [
    'cliente' => 'Cliente',
    'uso_interno' => 'Publico General',
];

$pendientes = (int)($estadisticas['pendientes'] ?? 0);
$enProceso = (int)($estadisticas['en_proceso'] ?? ($estadisticas['procesando'] ?? 0));
$facturadas = (int)($estadisticas['completadas_mes'] ?? ($estadisticas['facturadas_mes'] ?? ($estadisticas['facturadas'] ?? 0)));
$montoPendiente = (float)($estadisticas['monto_pendiente'] ?? 0);

$billingSummaryCards = [
    [
        'label' => 'Pendientes',
        'value' => number_format($pendientes),
        'meta' => 'Por revisar',
        'tone' => 'amber',
    ],
    [
        'label' => 'En proceso',
        'value' => number_format($enProceso),
        'meta' => 'Seguimiento activo',
        'tone' => 'blue',
    ],
    [
        'label' => 'Completadas',
        'value' => number_format($facturadas),
        'meta' => 'Cierre del mes',
        'tone' => 'green',
    ],
    [
        'label' => 'Monto pendiente',
        'value' => billing_money($montoPendiente),
        'meta' => 'Importe por emitir',
        'tone' => 'gold',
    ],
];

$billingPaginationPages = [];
if ($total_paginas > 1) {
    $startPage = max(1, $pagina_actual - 2);
    $endPage = min($total_paginas, $pagina_actual + 2);

    if ($startPage > 1) {
        $billingPaginationPages[] = 1;
        if ($startPage > 2) {
            $billingPaginationPages[] = 'ellipsis-start';
        }
    }

    for ($page = $startPage; $page <= $endPage; $page++) {
        $billingPaginationPages[] = $page;
    }

    if ($endPage < $total_paginas) {
        if ($endPage < $total_paginas - 1) {
            $billingPaginationPages[] = 'ellipsis-end';
        }
        $billingPaginationPages[] = $total_paginas;
    }
}
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.billing-page {
    --billing-brand: var(--brand-primary, #24485a);
    --billing-brand-2: var(--brand-secondary, #162f3e);
    --billing-accent: var(--brand-accent, #b88934);
    --billing-accent-soft: color-mix(in srgb, var(--billing-accent) 14%, #fffaf0 86%);
    --billing-ink: #243746;
    --billing-muted: #718096;
    --billing-line: rgba(39, 55, 70, 0.16);
    --billing-line-strong: rgba(39, 55, 70, 0.25);
    --billing-paper: rgba(255, 255, 255, 0.82);
    --billing-ivory: #fffdf8;
    --billing-surface: #f8f5ee;
    --billing-shadow: 0 18px 46px rgba(31, 41, 55, 0.08);
    --billing-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--billing-ink);
}

.billing-shell {
    width: 100%;
    max-width: none;
    margin: 0;
    display: grid;
    gap: 1rem;
}

.billing-hero {
    display: block;
    padding: 0.15rem 0 0.25rem;
}

.billing-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 2rem;
    padding: 0.35rem 0.72rem;
    border: 1px solid var(--billing-line);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.68);
    color: var(--billing-brand);
    font-size: 0.78rem;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(31, 41, 55, 0.04);
}

.billing-title-row {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-top: 0.8rem;
}

.billing-hero-icon {
    width: 3rem;
    height: 3rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border-radius: 0.95rem;
    color: #fff;
    background:
        radial-gradient(circle at 28% 22%, rgba(255, 255, 255, 0.32), transparent 32%),
        linear-gradient(145deg, var(--billing-brand), var(--billing-brand-2));
    box-shadow: 0 18px 38px color-mix(in srgb, var(--billing-brand) 24%, transparent);
}

.billing-title {
    margin: 0;
    color: var(--billing-brand);
    font-family: var(--billing-serif);
    font-size: clamp(2.5rem, 4.2vw, 4.6rem);
    font-weight: 700;
    letter-spacing: 0;
    line-height: 0.92;
}

.billing-subtitle {
    max-width: 820px;
    margin: 0.55rem 0 0;
    color: #52677a;
    font-size: clamp(0.98rem, 1.25vw, 1.16rem);
    line-height: 1.55;
}

.billing-summary-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.72rem;
}

.billing-summary-card {
    position: relative;
    min-height: 6.05rem;
    overflow: hidden;
    padding: 0.92rem 1rem;
    border: 1px solid var(--billing-line);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.78);
    box-shadow: 0 14px 34px rgba(31, 41, 55, 0.05);
}

.billing-summary-card::before {
    content: '';
    position: absolute;
    inset: auto 0 0;
    height: 3px;
    background: var(--billing-accent);
}

.billing-summary-card::after {
    content: '';
    position: absolute;
    top: -36px;
    right: -26px;
    width: 92px;
    height: 92px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--card-tone) 16%, transparent);
}

.billing-summary-card[data-tone="amber"] { --card-tone: #c68a21; }
.billing-summary-card[data-tone="blue"] { --card-tone: #3f7fa2; }
.billing-summary-card[data-tone="green"] { --card-tone: #169b62; }
.billing-summary-card[data-tone="gold"] { --card-tone: var(--billing-accent); }
.billing-summary-card[data-tone="amber"]::before { background: #d3911e; }
.billing-summary-card[data-tone="blue"]::before { background: #3f7fa2; }
.billing-summary-card[data-tone="green"]::before { background: #18a36b; }

.billing-summary-label {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.48rem;
    color: #748296;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.billing-summary-dot {
    width: 0.52rem;
    height: 0.52rem;
    border-radius: 999px;
    background: var(--card-tone);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--card-tone) 13%, transparent);
}

.billing-summary-value {
    position: relative;
    z-index: 1;
    margin-top: 0.68rem;
    color: var(--billing-brand);
    font-family: var(--billing-serif);
    font-size: clamp(1.82rem, 2.6vw, 2.45rem);
    font-weight: 700;
    line-height: 1;
}

.billing-summary-meta {
    position: relative;
    z-index: 1;
    margin-top: 0.34rem;
    color: #66758a;
    font-size: 0.83rem;
    font-weight: 700;
}

.billing-panel {
    overflow: hidden;
    border: 1px solid var(--billing-line);
    border-radius: 1rem;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.74)),
        var(--billing-ivory);
    box-shadow: 0 16px 42px rgba(31, 41, 55, 0.05);
}

.billing-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.08rem 0.88rem;
    border-bottom: 1px solid var(--billing-line);
}

.billing-panel-title {
    margin: 0;
    color: var(--billing-brand);
    font-size: 1.08rem;
    font-weight: 700;
}

.billing-panel-copy {
    margin: 0.18rem 0 0;
    color: #66758a;
    font-size: 0.88rem;
}

.billing-count-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 2.12rem;
    padding: 0.28rem 0.76rem;
    border: 1px solid var(--billing-line);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.72);
    color: var(--billing-brand);
    font-size: 0.82rem;
    font-weight: 700;
    white-space: nowrap;
}

.billing-filter-form {
    display: grid;
    grid-template-columns: minmax(250px, 1.35fr) minmax(160px, 0.72fr) minmax(170px, 0.78fr) minmax(170px, 0.72fr) auto;
    gap: 0.72rem;
    align-items: end;
    padding: 1rem 1.08rem;
    border-bottom: 1px solid var(--billing-line);
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--billing-accent) 7%, transparent), transparent 42%),
        rgba(255, 255, 255, 0.38);
}

.billing-field label {
    display: block;
    margin-bottom: 0.38rem;
    color: #2f4052;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.billing-input-wrap {
    position: relative;
}

.billing-input-wrap i {
    position: absolute;
    left: 0.9rem;
    top: 50%;
    transform: translateY(-50%);
    color: #8795a6;
    pointer-events: none;
}

.billing-input,
.billing-select {
    width: 100%;
    min-height: 2.72rem;
    padding: 0.7rem 0.9rem;
    border: 1px solid var(--billing-line-strong);
    border-radius: 0.78rem;
    background: rgba(255, 255, 255, 0.9);
    color: var(--billing-ink);
    font-size: 0.92rem;
    font-weight: 700;
    outline: none;
    transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.billing-input-wrap .billing-input {
    padding-left: 2.55rem;
}

.billing-input:focus,
.billing-select:focus {
    border-color: color-mix(in srgb, var(--billing-brand) 56%, white);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--billing-brand) 12%, transparent);
    background: #fff;
}

.billing-filter-actions {
    display: flex;
    gap: 0.56rem;
    align-items: center;
}

.billing-clear-btn {
    min-height: 2.72rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border-radius: 0.78rem;
    padding: 0.72rem 0.96rem;
    font-size: 0.9rem;
    font-weight: 700;
    text-decoration: none;
    white-space: nowrap;
    transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
}

.billing-clear-btn {
    border: 1px solid var(--billing-line);
    color: var(--billing-brand);
    background: rgba(255, 255, 255, 0.72);
}

.billing-clear-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 16px 32px rgba(31, 41, 55, 0.09);
}

.billing-desktop-table {
    display: block;
}

.billing-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 0.7rem;
    padding: 0.72rem 1.08rem 0.25rem;
}

.billing-table thead th {
    padding: 0 0.85rem 0.2rem;
    color: #748296;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-align: left;
    text-transform: uppercase;
}

.billing-table thead th:last-child,
.billing-table tbody td:last-child {
    text-align: right;
}

.billing-row {
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 10px 28px rgba(31, 41, 55, 0.045);
}

.billing-row td {
    padding: 0.85rem;
    border-top: 1px solid var(--billing-line);
    border-bottom: 1px solid var(--billing-line);
    vertical-align: middle;
}

.billing-row td:first-child {
    border-left: 1px solid var(--billing-line);
    border-radius: 0.95rem 0 0 0.95rem;
}

.billing-row td:last-child {
    border-right: 1px solid var(--billing-line);
    border-radius: 0 0.95rem 0.95rem 0;
}

.billing-request-cell,
.billing-guest-cell {
    display: flex;
    align-items: center;
    gap: 0.76rem;
    min-width: 0;
}

.billing-avatar,
.billing-icon-box {
    width: 2.55rem;
    height: 2.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    border: 1px solid var(--billing-line-strong);
    border-radius: 0.82rem;
    background: var(--billing-accent-soft);
    color: var(--billing-brand);
    font-weight: 700;
}

.billing-avatar {
    color: #fff;
    border-color: transparent;
    background:
        radial-gradient(circle at 30% 24%, rgba(255, 255, 255, 0.28), transparent 34%),
        linear-gradient(145deg, var(--billing-brand), var(--billing-accent));
}

.billing-main-text {
    min-width: 0;
}

.billing-name {
    display: block;
    color: var(--billing-brand);
    font-size: 0.97rem;
    font-weight: 700;
    line-height: 1.25;
    text-decoration: none;
}

a.billing-name:hover {
    color: var(--billing-accent);
}

.billing-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.42rem 0.7rem;
    margin-top: 0.32rem;
    color: #6f7f91;
    font-size: 0.78rem;
    font-weight: 700;
}

.billing-meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.28rem;
}

.billing-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 1.68rem;
    padding: 0.26rem 0.62rem;
    border-radius: 999px;
    color: var(--chip-color, #536475);
    background: var(--chip-bg, #eef2f5);
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}

.billing-chip-status-pendiente {
    --chip-bg: rgba(210, 137, 33, 0.13);
    --chip-color: #9a6417;
}

.billing-chip-status-en_proceso,
.billing-chip-status-procesando {
    --chip-bg: rgba(63, 127, 162, 0.13);
    --chip-color: #386f8e;
}

.billing-chip-status-facturada,
.billing-chip-status-completada {
    --chip-bg: rgba(22, 155, 98, 0.13);
    --chip-color: #14784f;
}

.billing-chip-status-cancelada,
.billing-chip-status-rechazada {
    --chip-bg: rgba(198, 66, 59, 0.12);
    --chip-color: #a33934;
}

.billing-rfc {
    color: #2f4052;
    font-size: 0.88rem;
    font-weight: 700;
}

.billing-muted {
    color: #748296;
    font-size: 0.78rem;
    font-weight: 700;
}

.billing-amount {
    color: var(--billing-brand);
    font-family: var(--billing-serif);
    font-size: 1.42rem;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
}

.billing-date {
    color: #536475;
    font-size: 0.86rem;
    font-weight: 700;
    white-space: nowrap;
}

.billing-action {
    min-height: 2.48rem;
    min-width: 2.48rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border: 1px solid var(--billing-line-strong);
    border-radius: 0.78rem;
    color: var(--billing-brand);
    background: rgba(255, 255, 255, 0.82);
    font-size: 0.86rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.18s ease, color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.billing-action-icon {
    width: 2.48rem;
    padding: 0;
}

.billing-action-icon i {
    font-size: 0.95rem;
}

.billing-action:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--billing-accent) 52%, var(--billing-line));
    color: var(--billing-accent);
    box-shadow: 0 14px 26px rgba(31, 41, 55, 0.08);
}

.billing-mobile-list {
    display: none;
    padding: 0.8rem;
    gap: 0.72rem;
}

.billing-card {
    padding: 0.88rem;
    border: 1px solid var(--billing-line);
    border-radius: 0.95rem;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 12px 28px rgba(31, 41, 55, 0.05);
}

.billing-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.8rem;
}

.billing-card-body {
    display: grid;
    gap: 0.58rem;
    margin-top: 0.82rem;
    padding-top: 0.82rem;
    border-top: 1px solid var(--billing-line);
}

.billing-card-line {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    color: #627287;
    font-size: 0.84rem;
    font-weight: 700;
}

.billing-card-line strong {
    color: var(--billing-brand);
    text-align: right;
}

.billing-card-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 0.82rem;
}

.billing-empty {
    margin: 1rem;
    padding: 2.2rem 1rem;
    border: 1px dashed var(--billing-line-strong);
    border-radius: 1rem;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--billing-accent) 9%, transparent), transparent),
        rgba(255, 255, 255, 0.72);
    text-align: center;
}

.billing-empty-icon {
    width: 3rem;
    height: 3rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--billing-line);
    border-radius: 1rem;
    color: var(--billing-brand);
    background: #fff;
}

.billing-empty h3 {
    margin: 0.85rem 0 0;
    color: var(--billing-brand);
    font-size: 1.12rem;
    font-weight: 700;
}

.billing-empty p {
    max-width: 520px;
    margin: 0.35rem auto 0;
    color: #66758a;
    font-size: 0.9rem;
}

.billing-pagination-shell {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.8rem 1.08rem 1rem;
    border-top: 1px solid var(--billing-line);
}

.billing-pagination-copy {
    color: #66758a;
    font-size: 0.84rem;
    font-weight: 700;
}

.billing-pagination {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.38rem;
}

.billing-page-link,
.billing-page-current,
.billing-page-ellipsis {
    min-width: 2.28rem;
    min-height: 2.28rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.7rem;
    font-size: 0.84rem;
    font-weight: 700;
    text-decoration: none;
}

.billing-page-link {
    border: 1px solid var(--billing-line);
    color: var(--billing-brand);
    background: rgba(255, 255, 255, 0.78);
}

.billing-page-current {
    border: 1px solid transparent;
    color: #fff;
    background: linear-gradient(135deg, var(--billing-brand), var(--billing-brand-2));
}

.billing-page-ellipsis {
    color: #8a96a6;
}

.billing-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.72rem;
    padding: 0.82rem 0.95rem;
    border: 1px solid rgba(22, 155, 98, 0.2);
    border-radius: 0.92rem;
    background: rgba(22, 155, 98, 0.08);
    color: #166b49;
    font-weight: 700;
}

@media (max-width: 1280px) {
    .billing-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .billing-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 1024px) {
    .billing-summary-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .billing-desktop-table {
        display: none;
    }

    .billing-mobile-list {
        display: grid;
    }
}

@media (max-width: 680px) {
    .billing-title-row {
        align-items: flex-start;
    }

    .billing-hero-icon {
        width: 2.7rem;
        height: 2.7rem;
    }

    .billing-summary-strip {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
        margin-bottom: 0.9rem;
    }

    .billing-summary-card {
        min-height: 5.35rem;
        padding: 0.62rem 0.64rem 0.54rem;
        border-radius: 0.82rem;
        box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04), 0 10px 22px -20px rgba(31, 41, 55, 0.42);
    }

    .billing-summary-card::before {
        height: 2px;
    }

    .billing-summary-card::after {
        top: -25px;
        right: -23px;
        width: 58px;
        height: 58px;
        opacity: 0.72;
    }

    .billing-summary-label {
        gap: 0.34rem;
        font-size: 0.58rem;
        letter-spacing: 0.055em;
        line-height: 1.1;
        white-space: nowrap;
    }

    .billing-summary-dot {
        width: 0.42rem;
        height: 0.42rem;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--card-tone) 12%, transparent);
    }

    .billing-summary-value {
        margin-top: 0.58rem;
        font-size: clamp(1.15rem, 5.1vw, 1.34rem);
        line-height: 0.98;
    }

    .billing-summary-card[data-tone="gold"] .billing-summary-value {
        font-size: clamp(0.98rem, 4.55vw, 1.18rem);
    }

    .billing-summary-meta {
        margin-top: 0.24rem;
        font-size: 0.64rem;
        line-height: 1.12;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .billing-panel-header,
    .billing-pagination-shell {
        flex-direction: column;
        align-items: stretch;
    }

    .billing-panel-header {
        gap: 0.55rem;
        padding: 0.82rem 0.88rem 0.5rem;
    }

    .billing-panel-title {
        font-size: 0.98rem;
    }

    .billing-panel-copy {
        display: none;
    }

    .billing-count-pill {
        width: fit-content;
        min-height: 1.8rem;
        padding: 0.18rem 0.58rem;
        font-size: 0.74rem;
    }

    .billing-filter-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem;
        margin: 0 0.72rem 0.78rem;
        padding: 0.68rem;
        border: 1px solid var(--billing-line);
        border-radius: 1rem;
        background: rgba(255, 255, 255, 0.78);
    }

    .billing-filter-form .billing-field:first-child {
        grid-column: 1 / -1;
    }

    .billing-filter-form .billing-field {
        min-width: 0;
    }

    .billing-field label {
        margin-bottom: 0.28rem;
        font-size: 0.64rem;
        letter-spacing: 0.055em;
    }

    .billing-input,
    .billing-select {
        min-height: 2.42rem;
        padding: 0.58rem 0.7rem;
        border-radius: 0.72rem;
        font-size: 0.84rem;
    }

    .billing-input-wrap .billing-input {
        padding-left: 2.28rem;
    }

    .billing-input-wrap i {
        left: 0.78rem;
        font-size: 0.86rem;
    }

    .billing-filter-actions {
        grid-column: 2 / 3;
        flex-direction: row;
        justify-content: flex-end;
        align-items: end;
        gap: 0.42rem;
    }

    .billing-clear-btn {
        width: 2.42rem;
        min-width: 2.42rem;
        min-height: 2.42rem;
        padding: 0;
        gap: 0;
        border-radius: 0.72rem;
    }

    .billing-filter-label {
        display: none;
    }

    .billing-clear-btn i {
        font-size: 0.86rem;
    }

    .billing-mobile-list {
        padding: 0.68rem;
        gap: 0.62rem;
    }

    .billing-card {
        padding: 0.78rem;
    }

    .billing-card-footer {
        margin-top: 0.68rem;
    }

    .billing-card-footer .billing-action-icon {
        width: 2.38rem;
        min-width: 2.38rem;
        min-height: 2.38rem;
    }

    .billing-pagination {
        justify-content: flex-start;
    }
}

@media (max-width: 380px) {
    .billing-filter-form {
        grid-template-columns: 1fr;
    }

    .billing-filter-actions {
        grid-column: 1 / -1;
    }
}
</style>

<div class="billing-page hotel-page p-4 sm:p-6">
    <div class="billing-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="billing-hero hotel-page-header">
            <div>
                <span class="billing-kicker">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?>
                </span>

                <div class="billing-title-row">
                    <span class="billing-hero-icon" aria-hidden="true">
                        <i class="fas fa-receipt"></i>
                    </span>
                    <h1 class="billing-title">Facturación</h1>
                </div>

                <p class="billing-subtitle">
                    Las facturas que tus huéspedes pidieron: cuáles faltan, cuáles están
                    en proceso y cuáles ya se emitieron.
                </p>
            </div>

        </section>

        <?php if ($mensaje): ?>
            <div class="billing-alert">
                <i class="fas fa-check-circle mt-1"></i>
                <span><?= billing_safe($mensaje) ?></span>
            </div>
        <?php endif; ?>

        <section class="billing-summary-strip" aria-label="Resumen de facturacion">
            <?php foreach ($billingSummaryCards as $card): ?>
                <article class="billing-summary-card" data-tone="<?= billing_safe($card['tone']) ?>">
                    <div class="billing-summary-label">
                        <span class="billing-summary-dot" aria-hidden="true"></span>
                        <?= billing_safe($card['label']) ?>
                    </div>
                    <div class="billing-summary-value"><?= billing_safe($card['value']) ?></div>
                    <div class="billing-summary-meta"><?= billing_safe($card['meta']) ?></div>
                </article>
            <?php endforeach; ?>
        </section>

        <section class="billing-panel hotel-card">
            <div class="billing-panel-header">
                <div>
                    <h2 class="billing-panel-title">Solicitudes fiscales</h2>
                    <p class="billing-panel-copy">Filtra por huésped, tipo, estado o fecha para encontrar una solicitud.</p>
                </div>
                <span class="billing-count-pill">
                    <?= number_format($total_registros) ?> registros
                </span>
            </div>

            <form method="GET" action="<?= url('facturacion') ?>" class="billing-filter-form" data-auto-filter-form>
                <div class="billing-field">
                    <label for="buscar">Busqueda</label>
                    <div class="billing-input-wrap">
                        <i class="fas fa-search"></i>
                        <input
                            id="buscar"
                            type="text"
                            name="buscar"
                            value="<?= billing_safe($buscar, '') ?>"
                            placeholder="Huesped, RFC o folio"
                            class="billing-input"
                        >
                    </div>
                </div>

                <div class="billing-field">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="billing-select">
                        <option value="">Todos</option>
                        <option value="cliente" <?= $filtro_tipo === 'cliente' ? 'selected' : '' ?>>Cliente</option>
                        <option value="uso_interno" <?= $filtro_tipo === 'uso_interno' ? 'selected' : '' ?>>Publico General</option>
                    </select>
                </div>

                <div class="billing-field">
                    <label for="estatus">Estado</label>
                    <select id="estatus" name="estatus" class="billing-select">
                        <option value="">Todos</option>
                        <option value="pendiente" <?= $filtro_estatus === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="en_proceso" <?= $filtro_estatus === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                        <option value="completada" <?= $filtro_estatus === 'completada' ? 'selected' : '' ?>>Completada</option>
                        <option value="cancelada" <?= $filtro_estatus === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>

                <div class="billing-field">
                    <label for="fecha_desde">Desde</label>
                    <input
                        id="fecha_desde"
                        type="date"
                        name="fecha_desde"
                        value="<?= billing_safe($fecha_desde, '') ?>"
                        class="billing-input"
                    >
                </div>

                <div class="billing-filter-actions">
                    <a href="<?= url('facturacion') ?>" class="billing-clear-btn" aria-label="Limpiar filtros" title="Limpiar filtros">
                        <i class="fas fa-undo"></i>
                        <span class="billing-filter-label">Limpiar</span>
                    </a>
                </div>
            </form>

            <div data-billing-results-region>
                <?php if (!empty($solicitudes)): ?>
                    <div class="billing-desktop-table">
                        <table class="billing-table hotel-table">
                            <thead>
                                <tr>
                                    <th>Solicitud</th>
                                    <th>Huesped</th>
                                    <th>Fiscal</th>
                                    <th>Estado</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($solicitudes as $sol): ?>
                                    <?php
                                    $id = (int)($sol['id'] ?? 0);
                                    $reservacionId = (int)($sol['reservacion_id'] ?? 0);
                                    $nombre = (string)($sol['huesped_nombre'] ?? 'Huesped');
                                    $telefono = (string)($sol['huesped_telefono'] ?? '');
                                    $tipo = (string)($sol['tipo'] ?? 'cliente');
                                    $estatus = (string)($sol['estatus'] ?? 'pendiente');
                                    $estatusClass = preg_replace('/[^a-z0-9_]/', '_', strtolower($estatus));
                                    $rfc = (string)($sol['rfc'] ?? '');
                                    $createdAt = (string)($sol['created_at'] ?? '');
                                    ?>
                                    <tr class="billing-row" data-easy-href="<?= htmlspecialchars(url('facturacion/ver/' . $id), ENT_QUOTES, 'UTF-8') ?>" role="link" tabindex="0" title="Abrir solicitud #<?= $id ?>" aria-label="Abrir solicitud fiscal #<?= $id ?>">
                                        <td>
                                            <div class="billing-request-cell">
                                                <span class="billing-icon-box" aria-hidden="true">
                                                    <i class="fas fa-file-invoice"></i>
                                                </span>
                                                <div class="billing-main-text">
                                                    <span class="billing-name">Solicitud #<?= $id ?></span>
                                                    <div class="billing-meta">
                                                        <span>
                                                            <i class="fas fa-tag"></i>
                                                            <?= billing_safe($tipoLabels[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo))) ?>
                                                        </span>
                                                        <?php if ($reservacionId > 0): ?>
                                                            <span>
                                                                <i class="fas fa-bed"></i>
                                                                Reserva #<?= $reservacionId ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="billing-guest-cell">
                                                <span class="billing-avatar" aria-hidden="true">
                                                    <?= billing_safe(billing_initial($nombre)) ?>
                                                </span>
                                                <div class="billing-main-text">
                                                    <?php if ($reservacionId > 0): ?>
                                                        <a href="<?= url('reservaciones/ver/' . $reservacionId) ?>" class="billing-name">
                                                            <?= billing_safe($nombre) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="billing-name"><?= billing_safe($nombre) ?></span>
                                                    <?php endif; ?>
                                                    <div class="billing-meta">
                                                        <span>
                                                            <i class="fas fa-phone"></i>
                                                            <?= billing_safe($telefono, 'Sin telefono') ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="billing-rfc"><?= billing_safe($rfc, 'Publico general') ?></div>
                                            <div class="billing-muted">Datos fiscales</div>
                                        </td>

                                        <td>
                                            <span class="billing-chip billing-chip-status-<?= billing_safe($estatusClass, 'pendiente') ?>">
                                                <?= billing_safe($statusLabels[$estatus] ?? ucfirst(str_replace('_', ' ', $estatus))) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="billing-amount"><?= billing_money($sol['monto_total'] ?? 0) ?></div>
                                        </td>

                                        <td>
                                            <div class="billing-date"><?= billing_date($createdAt) ?></div>
                                            <div class="billing-muted"><?= billing_date($createdAt, 'H:i') ?></div>
                                        </td>

                                        <td>
                                            <a href="<?= url('facturacion/ver/' . $id) ?>" class="billing-action billing-action-icon" aria-label="Ver solicitud #<?= $id ?>" title="Ver solicitud">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="billing-mobile-list" aria-label="Solicitudes fiscales">
                        <?php foreach ($solicitudes as $sol): ?>
                            <?php
                            $id = (int)($sol['id'] ?? 0);
                            $reservacionId = (int)($sol['reservacion_id'] ?? 0);
                            $nombre = (string)($sol['huesped_nombre'] ?? 'Huesped');
                            $telefono = (string)($sol['huesped_telefono'] ?? '');
                            $tipo = (string)($sol['tipo'] ?? 'cliente');
                            $estatus = (string)($sol['estatus'] ?? 'pendiente');
                            $estatusClass = preg_replace('/[^a-z0-9_]/', '_', strtolower($estatus));
                            $rfc = (string)($sol['rfc'] ?? '');
                            $createdAt = (string)($sol['created_at'] ?? '');
                            ?>
                            <article class="billing-card" data-easy-href="<?= htmlspecialchars(url('facturacion/ver/' . $id), ENT_QUOTES, 'UTF-8') ?>" role="link" tabindex="0" title="Abrir solicitud #<?= $id ?>" aria-label="Abrir solicitud fiscal #<?= $id ?>">
                                <div class="billing-card-head">
                                    <div class="billing-guest-cell">
                                        <span class="billing-avatar" aria-hidden="true">
                                            <?= billing_safe(billing_initial($nombre)) ?>
                                        </span>
                                        <div class="billing-main-text">
                                            <span class="billing-name"><?= billing_safe($nombre) ?></span>
                                            <div class="billing-meta">
                                                <span>
                                                    <i class="fas fa-file-invoice"></i>
                                                    Solicitud #<?= $id ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="billing-chip billing-chip-status-<?= billing_safe($estatusClass, 'pendiente') ?>">
                                        <?= billing_safe($statusLabels[$estatus] ?? ucfirst(str_replace('_', ' ', $estatus))) ?>
                                    </span>
                                </div>

                                <div class="billing-card-body">
                                    <div class="billing-card-line">
                                        <span>Tipo</span>
                                        <strong><?= billing_safe($tipoLabels[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo))) ?></strong>
                                    </div>
                                    <div class="billing-card-line">
                                        <span>RFC</span>
                                        <strong><?= billing_safe($rfc, 'Publico general') ?></strong>
                                    </div>
                                    <div class="billing-card-line">
                                        <span>Telefono</span>
                                        <strong><?= billing_safe($telefono, 'Sin telefono') ?></strong>
                                    </div>
                                    <?php if ($reservacionId > 0): ?>
                                        <div class="billing-card-line">
                                            <span>Reserva</span>
                                            <strong>#<?= $reservacionId ?></strong>
                                        </div>
                                    <?php endif; ?>
                                    <div class="billing-card-line">
                                        <span>Fecha</span>
                                        <strong><?= billing_date($createdAt) ?></strong>
                                    </div>
                                    <div class="billing-card-line">
                                        <span>Monto</span>
                                        <strong><?= billing_money($sol['monto_total'] ?? 0) ?></strong>
                                    </div>
                                </div>

                                <div class="billing-card-footer">
                                    <a href="<?= url('facturacion/ver/' . $id) ?>" class="billing-action billing-action-icon" aria-label="Ver solicitud #<?= $id ?>" title="Ver solicitud">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="billing-empty">
                        <span class="billing-empty-icon" aria-hidden="true">
                            <i class="fas fa-file-invoice"></i>
                        </span>
                        <h3>No hay solicitudes con esos filtros</h3>
                        <p>Ajusta la busqueda o limpia los filtros para volver a ver las solicitudes de facturacion.</p>
                    </div>
                <?php endif; ?>

                <?php if ($total_paginas > 1): ?>
                    <div class="billing-pagination-shell">
                        <div class="billing-pagination-copy">
                            Pagina <?= number_format($pagina_actual) ?> de <?= number_format($total_paginas) ?>
                        </div>

                        <nav class="billing-pagination" aria-label="Paginacion de facturacion">
                            <?php if ($pagina_actual > 1): ?>
                                <a class="billing-page-link" href="<?= billing_page_url($pagina_actual - 1) ?>" aria-label="Pagina anterior">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>

                            <?php foreach ($billingPaginationPages as $page): ?>
                                <?php if (is_string($page)): ?>
                                    <span class="billing-page-ellipsis">...</span>
                                <?php elseif ((int)$page === $pagina_actual): ?>
                                    <span class="billing-page-current"><?= (int)$page ?></span>
                                <?php else: ?>
                                    <a class="billing-page-link" href="<?= billing_page_url((int)$page) ?>"><?= (int)$page ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a class="billing-page-link" href="<?= billing_page_url($pagina_actual + 1) ?>" aria-label="Pagina siguiente">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
