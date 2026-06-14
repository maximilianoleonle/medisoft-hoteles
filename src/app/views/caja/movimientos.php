<?php
/**
 * Vista de Movimientos de Caja
 * Rediseño operativo tipo libro de caja.
 */

$movimientos = $movimientos ?? [];
$filtros = $filtros ?? [];
$totales = $totales ?? ['ingresos' => 0, 'gastos' => 0, 'balance' => 0];
$categorias = $categorias ?? [];
$metodos_pago = $metodos_pago ?? [];
$tipos = $tipos ?? [];

if (!function_exists('caja_mov_safe')) {
    function caja_mov_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('caja_mov_money')) {
    function caja_mov_money($amount, $signed = false) {
        $amount = (float)($amount ?? 0);
        $prefix = '';
        if ($amount < 0) {
            $prefix = '-';
            $amount = abs($amount);
        } elseif ($signed) {
            $prefix = '+';
        }

        return $prefix . '$' . number_format($amount, 2);
    }
}

if (!function_exists('caja_mov_date')) {
    function caja_mov_date($date, $format = 'd/m/Y') {
        if (empty($date)) {
            return '-';
        }
        $timestamp = strtotime((string)$date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('caja_mov_method_color')) {
    function caja_mov_method_color($method) {
        $map = [
            'efectivo' => '#16824E',
            'tarjeta' => '#2563EB',
            'transferencia' => '#7C3AED',
        ];
        return $map[$method] ?? '#64748B';
    }
}

$ingresos_total = (float)($totales['ingresos'] ?? 0);
$gastos_total = (float)($totales['gastos'] ?? 0);
$balance_total = (float)($totales['balance'] ?? ($ingresos_total - $gastos_total));
$movimientos_count = count($movimientos);
$total_flujo = max(1, $ingresos_total + $gastos_total);
$ingresos_pct = min(100, max(0, round(($ingresos_total / $total_flujo) * 100)));
$gastos_pct = min(100, max(0, 100 - $ingresos_pct));

$user_filter_keys = ['tipo', 'metodo_pago', 'categoria_id', 'fecha_inicio', 'fecha_fin', 'buscar'];
$active_filter_count = 0;
foreach ($user_filter_keys as $key) {
    if (!empty($filtros[$key])) {
        $active_filter_count++;
    }
}

$fecha_inicio = $filtros['fecha_inicio'] ?? '';
$fecha_fin = $filtros['fecha_fin'] ?? '';
$periodo_label = 'Corte actual';
if ($fecha_inicio || $fecha_fin) {
    $periodo_label = ($fecha_inicio === $fecha_fin)
        ? caja_mov_date($fecha_inicio)
        : caja_mov_date($fecha_inicio ?: $fecha_fin) . ' - ' . caja_mov_date($fecha_fin ?: $fecha_inicio);
}

$balance_es_positivo = $balance_total >= 0;
?>

<style>
.cash-movements-view {
    --cash-primary: var(--brand-primary, #1B2746);
    --cash-secondary: var(--brand-secondary, #0F172A);
    --cash-accent: var(--brand-accent, #BD9441);
    --cash-bg: color-mix(in srgb, var(--cash-accent) 8%, #F7F3EC);
    --cash-surface: color-mix(in srgb, var(--cash-accent) 3%, #FFFFFF);
    --cash-soft: color-mix(in srgb, var(--cash-primary) 6%, #FFFFFF);
    --cash-line: color-mix(in srgb, var(--cash-primary) 13%, #E7DDD1);
    --cash-text: #17233E;
    --cash-muted: #7A8498;
    --cash-income: #16824E;
    --cash-expense: #C24135;
    min-height: 100vh;
    background:
        radial-gradient(circle at 92% 6%, color-mix(in srgb, var(--cash-accent) 18%, transparent), transparent 32rem),
        linear-gradient(180deg, var(--cash-bg), #FBFAF7 54%, #F4EFE6);
    color: var(--cash-text);
}
.cash-shell {
    width: min(1640px, calc(100% - 32px));
    margin: 0 auto;
    padding: 28px 0 44px;
}
.cash-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 22px;
    align-items: end;
    padding: clamp(22px, 3vw, 34px);
    border: 1px solid var(--cash-line);
    border-radius: 22px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--cash-primary) 94%, #FFFFFF), color-mix(in srgb, var(--cash-secondary) 92%, #000000)),
        var(--cash-primary);
    box-shadow: 0 28px 70px -44px rgba(12, 18, 30, .72);
    overflow: hidden;
    position: relative;
}
.cash-hero::before {
    content: "";
    position: absolute;
    inset: auto -8% -64% 42%;
    height: 210px;
    background: radial-gradient(circle, color-mix(in srgb, var(--cash-accent) 42%, transparent), transparent 67%);
    pointer-events: none;
}
.cash-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: color-mix(in srgb, var(--cash-accent) 84%, #FFFFFF);
    font-size: .74rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.cash-hero h1 {
    margin: 10px 0 8px;
    color: #FFFDF8;
    font-size: clamp(2rem, 4vw, 4.4rem);
    line-height: .95;
    font-family: Georgia, "Times New Roman", serif;
    font-weight: 700;
}
.cash-hero p {
    max-width: 62ch;
    margin: 0;
    color: rgba(255,255,255,.72);
    font-weight: 650;
}
.cash-hero-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.cash-action {
    min-height: 42px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 12px;
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #FFFFFF;
    background: rgba(255,255,255,.11);
    font-size: .86rem;
    font-weight: 900;
    transition: transform .16s ease, background .16s ease, border-color .16s ease;
}
.cash-action:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.18);
    border-color: rgba(255,255,255,.32);
}
.cash-action.is-main {
    border-color: transparent;
    background: linear-gradient(135deg, var(--cash-accent), color-mix(in srgb, var(--cash-accent) 72%, #6B4B16));
    color: #FFFFFF;
    box-shadow: 0 16px 34px -22px color-mix(in srgb, var(--cash-accent) 72%, transparent);
}
.cash-balance-strip {
    display: grid;
    grid-template-columns: 1.15fr .9fr .95fr;
    gap: 12px;
    margin: 16px 0;
}
.cash-stat {
    border: 1px solid var(--cash-line);
    border-radius: 18px;
    background: rgba(255,255,255,.88);
    padding: 17px;
    box-shadow: 0 16px 34px rgba(15,23,42,.05);
}
.cash-stat-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--cash-muted);
    font-size: .76rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-transform: uppercase;
}
.cash-stat-label i {
    width: 31px;
    height: 31px;
    border-radius: 10px;
    display: inline-grid;
    place-items: center;
    background: var(--stat-soft);
    color: var(--stat-color);
}
.cash-stat strong {
    display: block;
    margin-top: 10px;
    color: var(--stat-color);
    font-size: clamp(1.45rem, 2.3vw, 2.35rem);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.cash-stat small {
    display: block;
    margin-top: 7px;
    color: #8792A6;
    font-size: .78rem;
    font-weight: 700;
}
.cash-stat.income { --stat-color: var(--cash-income); --stat-soft: #E7F6EE; }
.cash-stat.expense { --stat-color: var(--cash-expense); --stat-soft: #FDEDEB; }
.cash-stat.balance { --stat-color: <?= $balance_es_positivo ? 'var(--cash-income)' : 'var(--cash-expense)' ?>; --stat-soft: <?= $balance_es_positivo ? '#E7F6EE' : '#FDEDEB' ?>; }
.cash-flow-bar {
    grid-column: 1 / -1;
    display: grid;
    grid-template-columns: <?= (int)$ingresos_pct ?>fr <?= (int)$gastos_pct ?>fr;
    height: 10px;
    overflow: hidden;
    border-radius: 999px;
    background: #EEF1F5;
    border: 1px solid rgba(255,255,255,.8);
}
.cash-flow-bar span:first-child { background: var(--cash-income); }
.cash-flow-bar span:last-child { background: var(--cash-expense); }
.cash-filter-dock {
    margin-bottom: 16px;
    border: 1px solid var(--cash-line);
    border-radius: 20px;
    background: color-mix(in srgb, var(--cash-surface) 94%, transparent);
    box-shadow: 0 18px 44px rgba(15,23,42,.06);
    overflow: hidden;
}
.cash-filter-dock.hidden { display: none; }
.cash-filter-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--cash-line);
}
.cash-filter-title h2 {
    margin: 0;
    color: var(--cash-secondary);
    font-size: 1rem;
    font-weight: 950;
}
.cash-filter-title span {
    color: var(--cash-muted);
    font-size: .78rem;
    font-weight: 800;
}
.cash-filter-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 12px;
    padding: 18px;
}
.cash-field {
    grid-column: span 3;
}
.cash-field.wide {
    grid-column: span 4;
}
.cash-field label {
    display: block;
    margin-bottom: 7px;
    color: var(--cash-primary);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.cash-input {
    width: 100%;
    min-height: 43px;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 15%, #DCE2EA);
    border-radius: 12px;
    padding: 9px 12px;
    background: #FFFFFF;
    color: var(--cash-secondary);
    outline: none;
    font-weight: 700;
    transition: border-color .16s ease, box-shadow .16s ease;
}
.cash-input:focus {
    border-color: var(--cash-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--cash-primary) 12%, transparent);
}
.cash-filter-actions {
    grid-column: span 4;
    display: flex;
    align-items: end;
    gap: 10px;
}
.cash-form-button,
.cash-form-link {
    min-height: 43px;
    border-radius: 12px;
    padding: 0 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 900;
}
.cash-form-button {
    border: 0;
    background: var(--cash-primary);
    color: #FFFFFF;
}
.cash-form-link {
    border: 1px solid var(--cash-line);
    background: #FFFFFF;
    color: var(--cash-secondary);
}
.cash-workspace {
    display: grid;
    grid-template-columns: 330px minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}
.cash-sidebar-panel,
.cash-ledger-panel {
    border: 1px solid var(--cash-line);
    border-radius: 20px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 18px 44px rgba(15,23,42,.06);
}
.cash-sidebar-panel {
    position: sticky;
    top: 18px;
    padding: 18px;
}
.cash-side-heading {
    color: var(--cash-secondary);
    font-size: .98rem;
    font-weight: 950;
    margin: 0 0 12px;
}
.cash-audit-list {
    display: grid;
    gap: 10px;
}
.cash-audit-item {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    padding: 11px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--cash-primary) 4%, #FFFFFF);
}
.cash-audit-item i {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #FFFFFF;
    color: var(--cash-primary);
    border: 1px solid var(--cash-line);
}
.cash-audit-item span {
    display: block;
    color: #7A8498;
    font-size: .74rem;
    font-weight: 850;
}
.cash-audit-item strong {
    display: block;
    color: var(--cash-secondary);
    font-size: .92rem;
    font-weight: 950;
    margin-top: 1px;
}
.cash-active-filters {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid var(--cash-line);
}
.cash-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}
.cash-chip {
    border-radius: 999px;
    border: 1px solid var(--cash-line);
    background: #FFFFFF;
    color: var(--cash-secondary);
    padding: 6px 9px;
    font-size: .74rem;
    font-weight: 850;
}
.cash-ledger-panel {
    overflow: hidden;
}
.cash-ledger-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--cash-line);
    background: color-mix(in srgb, var(--cash-accent) 5%, #FFFFFF);
}
.cash-ledger-head h2 {
    margin: 0;
    color: var(--cash-secondary);
    font-size: 1.08rem;
    font-weight: 950;
}
.cash-ledger-head p {
    margin: 3px 0 0;
    color: var(--cash-muted);
    font-size: .82rem;
    font-weight: 700;
}
.cash-count-pill {
    border-radius: 999px;
    background: var(--cash-primary);
    color: #FFFFFF;
    padding: 7px 11px;
    font-size: .78rem;
    font-weight: 950;
}
.cash-empty {
    padding: 58px 18px;
    text-align: center;
    color: var(--cash-muted);
}
.cash-empty i {
    width: 68px;
    height: 68px;
    border-radius: 18px;
    display: inline-grid;
    place-items: center;
    margin-bottom: 14px;
    background: color-mix(in srgb, var(--cash-primary) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--cash-primary) 62%, #98A2B3);
    font-size: 1.65rem;
}
.cash-empty strong {
    display: block;
    color: var(--cash-secondary);
    font-size: 1.05rem;
    margin-bottom: 5px;
}
.cash-ledger-list {
    display: grid;
}
.cash-move-row {
    display: grid;
    grid-template-columns: 92px minmax(230px, 1.25fr) minmax(140px, .75fr) minmax(120px, .65fr) minmax(116px, .58fr) 142px 82px;
    gap: 12px;
    align-items: center;
    padding: 16px 18px;
    border-bottom: 1px solid color-mix(in srgb, var(--cash-line) 74%, transparent);
    transition: background .16s ease;
}
.cash-move-row:hover {
    background: color-mix(in srgb, var(--row-color) 4%, #FFFFFF);
}
.cash-move-date {
    display: grid;
    gap: 4px;
}
.cash-move-date strong {
    color: var(--cash-secondary);
    font-size: .88rem;
    font-weight: 950;
}
.cash-move-date span {
    color: var(--cash-muted);
    font-size: .76rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
}
.cash-move-main {
    min-width: 0;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
}
.cash-type-mark {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    color: var(--row-color);
    background: color-mix(in srgb, var(--row-color) 10%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--row-color) 20%, #FFFFFF);
}
.cash-move-description {
    min-width: 0;
}
.cash-move-description strong {
    display: block;
    color: var(--cash-secondary);
    font-weight: 950;
    line-height: 1.25;
    overflow-wrap: anywhere;
}
.cash-move-description span {
    display: block;
    margin-top: 4px;
    color: var(--cash-muted);
    font-size: .78rem;
    font-weight: 750;
}
.cash-meta-stack {
    display: grid;
    gap: 5px;
    min-width: 0;
}
.cash-meta-pill {
    width: fit-content;
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border: 1px solid var(--cash-line);
    border-radius: 999px;
    background: #FFFFFF;
    color: var(--cash-secondary);
    padding: 6px 9px;
    font-size: .76rem;
    font-weight: 850;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cash-meta-pill i {
    color: var(--pill-color, var(--cash-primary));
}
.cash-ref {
    color: var(--cash-muted);
    font-size: .75rem;
    font-weight: 750;
    overflow-wrap: anywhere;
}
.cash-user {
    color: #5C667A;
    font-size: .82rem;
    font-weight: 850;
    overflow-wrap: anywhere;
}
.cash-amount-cell {
    text-align: right;
}
.cash-amount {
    display: block;
    color: var(--row-color);
    font-size: 1.05rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.cash-reserve-link {
    display: inline-flex;
    justify-content: flex-end;
    gap: 5px;
    margin-top: 5px;
    color: #2563EB;
    font-size: .73rem;
    font-weight: 850;
}
.cash-room-detail {
    color: var(--cash-muted);
    font-size: .72rem;
    font-weight: 750;
    margin-top: 4px;
}
.cash-row-actions {
    display: flex;
    justify-content: center;
    gap: 8px;
}
.cash-icon-btn {
    width: 36px;
    height: 36px;
    border-radius: 11px;
    border: 1px solid var(--cash-line);
    display: inline-grid;
    place-items: center;
    background: #FFFFFF;
    color: var(--cash-secondary);
    transition: transform .16s ease, border-color .16s ease, color .16s ease;
}
.cash-icon-btn:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--cash-primary) 34%, var(--cash-line));
    color: var(--cash-primary);
}
.cash-icon-btn.is-edit {
    color: #2563EB;
}
.cash-modal {
    position: fixed;
    inset: 0;
    z-index: 13000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(12, 18, 30, .68);
    -webkit-backdrop-filter: blur(7px);
    backdrop-filter: blur(7px);
}
.cash-modal.hidden {
    display: none !important;
}
.cash-modal-card {
    width: min(520px, 100%);
    max-height: 92dvh;
    overflow: hidden;
    border-radius: 22px;
    background: #FBFAF7;
    box-shadow: 0 34px 90px -36px rgba(10,15,25,.72);
}
.cash-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    color: #FFFFFF;
    background: linear-gradient(135deg, var(--cash-primary), var(--cash-secondary));
}
.cash-modal-head h3 {
    margin: 0;
    font-size: 1.08rem;
    font-weight: 950;
    display: flex;
    align-items: center;
    gap: 10px;
}
.cash-modal-close {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    border: 1px solid rgba(255,255,255,.25);
    color: #FFFFFF;
    background: rgba(255,255,255,.13);
}
.cash-edit-form {
    padding: 18px;
    display: grid;
    gap: 13px;
    max-height: calc(92dvh - 74px);
    overflow-y: auto;
}
.cash-edit-field label {
    display: block;
    margin-bottom: 7px;
    color: var(--cash-primary);
    font-size: .74rem;
    font-weight: 950;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.cash-required {
    color: var(--cash-expense);
}
.cash-money-input {
    position: relative;
}
.cash-money-input span {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--cash-muted);
    font-weight: 900;
}
.cash-money-input .cash-input {
    padding-left: 30px;
}
.cash-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 4px;
}
.cash-cancel,
.cash-save {
    min-height: 42px;
    border-radius: 12px;
    padding: 0 15px;
    font-weight: 900;
}
.cash-cancel {
    border: 1px solid var(--cash-line);
    background: #FFFFFF;
    color: var(--cash-secondary);
}
.cash-save {
    border: 0;
    background: linear-gradient(135deg, var(--cash-primary), var(--cash-secondary));
    color: #FFFFFF;
}
@media (max-width: 1180px) {
    .cash-balance-strip {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .cash-workspace {
        grid-template-columns: 1fr;
    }
    .cash-sidebar-panel {
        position: static;
    }
    .cash-move-row {
        grid-template-columns: 82px minmax(260px, 1.3fr) minmax(140px, .8fr) minmax(120px, .7fr) 132px 76px;
    }
    .cash-user {
        display: none;
    }
}
@media (max-width: 900px) {
    .cash-hero {
        grid-template-columns: 1fr;
        align-items: start;
    }
    .cash-hero-actions {
        justify-content: flex-start;
    }
    .cash-balance-strip {
        grid-template-columns: 1fr;
    }
    .cash-field,
    .cash-field.wide,
    .cash-filter-actions {
        grid-column: span 6;
    }
    .cash-move-row {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    .cash-move-date {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cash-amount-cell,
    .cash-row-actions {
        text-align: left;
        justify-content: flex-start;
    }
}
@media (max-width: 620px) {
    .cash-shell {
        width: min(100% - 20px, 1640px);
        padding-top: 14px;
    }
    .cash-hero,
    .cash-stat,
    .cash-sidebar-panel,
    .cash-ledger-panel,
    .cash-filter-dock {
        border-radius: 16px;
    }
    .cash-hero-actions,
    .cash-filter-actions,
    .cash-modal-actions {
        display: grid;
        grid-template-columns: 1fr;
        width: 100%;
    }
    .cash-action,
    .cash-form-button,
    .cash-form-link,
    .cash-cancel,
    .cash-save {
        width: 100%;
    }
    .cash-field,
    .cash-field.wide,
    .cash-filter-actions {
        grid-column: 1 / -1;
    }
    .cash-filter-form {
        grid-template-columns: 1fr;
    }
    .cash-ledger-head {
        align-items: flex-start;
        flex-direction: column;
    }
}

/* === Propuesta visual: tablero de auditoria de caja === */
.cash-movements-view {
    --cash-bg: color-mix(in srgb, var(--cash-accent) 9%, #F5F0E7);
    --cash-panel: rgba(255, 253, 248, .9);
    --cash-panel-strong: #FFFDF8;
    --cash-glow: color-mix(in srgb, var(--cash-accent) 28%, transparent);
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--cash-primary) 5%, transparent) 0 1px, transparent 1px 28px),
        linear-gradient(180deg, color-mix(in srgb, var(--cash-accent) 7%, #FCFAF5), var(--cash-bg) 44%, #F8F4EC);
}

.cash-shell {
    width: min(1540px, calc(100% - 30px));
    padding-top: 24px;
}

.cash-hero {
    grid-template-columns: minmax(0, 1fr) minmax(260px, 328px);
    align-items: stretch;
    gap: 18px;
    border-color: color-mix(in srgb, var(--cash-accent) 28%, transparent);
    background:
        radial-gradient(circle at 14% 12%, color-mix(in srgb, var(--cash-accent) 20%, transparent), transparent 18rem),
        linear-gradient(145deg, rgba(255,253,248,.96), rgba(248,241,229,.92));
    box-shadow: 0 24px 62px -46px rgba(15, 23, 42, .58);
}

.cash-hero::before {
    inset: auto 3% -42% 52%;
    height: 260px;
    background: radial-gradient(circle, color-mix(in srgb, var(--cash-primary) 16%, transparent), transparent 68%);
}

.cash-kicker {
    color: color-mix(in srgb, var(--cash-accent) 72%, var(--cash-primary));
}

.cash-hero h1 {
    max-width: 12ch;
    color: var(--cash-primary);
    text-wrap: balance;
}

.cash-hero p {
    color: var(--cash-muted);
}

.cash-hero-insight {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 18px;
}

.cash-hero-insight span {
    min-width: 122px;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 11%, transparent);
    border-radius: 14px;
    background: rgba(255,255,255,.72);
    padding: 10px 12px;
}

.cash-hero-insight small,
.cash-ledger-columns span {
    display: block;
    color: var(--cash-muted);
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.cash-hero-insight strong {
    display: block;
    margin-top: 4px;
    color: var(--cash-primary);
    font-size: .95rem;
    font-weight: 950;
}

.cash-hero-actions {
    align-self: stretch;
    flex-direction: column;
    align-items: stretch;
    justify-content: center;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 12%, transparent);
    border-radius: 18px;
    background: rgba(255,255,255,.62);
}

.cash-action {
    width: 100%;
    justify-content: flex-start;
    border-color: color-mix(in srgb, var(--cash-primary) 13%, transparent);
    background: #FFFFFF;
    color: var(--cash-primary);
    box-shadow: 0 10px 22px -18px rgba(15,23,42,.36);
}

.cash-action:hover {
    background: color-mix(in srgb, var(--cash-accent) 8%, #FFFFFF);
}

.cash-action.is-main {
    background: linear-gradient(135deg, var(--cash-primary), color-mix(in srgb, var(--cash-primary) 80%, #000000));
    color: #FFFFFF;
}

.cash-balance-strip {
    position: relative;
    z-index: 2;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
    margin: -10px 18px 18px;
}

.cash-stat {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    background: var(--cash-panel-strong);
    box-shadow: 0 18px 44px -34px rgba(15, 23, 42, .5);
}

.cash-stat::after {
    content: "";
    position: absolute;
    inset: auto 16px 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--stat-color);
    opacity: .72;
}

.cash-stat-label {
    align-items: flex-start;
}

.cash-stat-label i {
    border-radius: 12px;
}

.cash-stat strong {
    margin-top: 12px;
}

.cash-flow-bar {
    grid-column: 1 / -1;
    margin-top: 2px;
    background: rgba(255,255,255,.72);
}

.cash-filter-dock {
    border-radius: 18px;
    background: rgba(255,253,248,.88);
    box-shadow: 0 18px 42px -34px rgba(15,23,42,.44);
}

.cash-filter-title {
    background: linear-gradient(90deg, color-mix(in srgb, var(--cash-accent) 7%, #FFFFFF), #FFFFFF);
}

.cash-input {
    border-radius: 11px;
    background: rgba(255,255,255,.95);
}

.cash-input:hover {
    border-color: color-mix(in srgb, var(--cash-accent) 38%, var(--cash-line));
}

.cash-workspace {
    grid-template-columns: minmax(250px, 300px) minmax(0, 1fr);
    gap: 14px;
}

.cash-sidebar-panel {
    border-radius: 18px;
    background:
        radial-gradient(circle at 90% 0%, color-mix(in srgb, var(--cash-accent) 14%, transparent), transparent 12rem),
        rgba(255,253,248,.9);
}

.cash-side-heading {
    letter-spacing: 0;
}

.cash-audit-item {
    border: 1px solid color-mix(in srgb, var(--cash-primary) 10%, transparent);
    background: #FFFFFF;
}

.cash-audit-item i {
    background: color-mix(in srgb, var(--cash-primary) 6%, #FFFFFF);
}

.cash-chip {
    border-radius: 11px;
    background: color-mix(in srgb, var(--cash-accent) 7%, #FFFFFF);
}

.cash-ledger-panel {
    border: 0;
    background: transparent;
    box-shadow: none;
}

.cash-ledger-head {
    border: 1px solid var(--cash-line);
    border-radius: 18px;
    background: rgba(255,253,248,.9);
    box-shadow: 0 16px 38px -34px rgba(15,23,42,.5);
    margin-bottom: 10px;
}

.cash-count-pill {
    border: 1px solid color-mix(in srgb, var(--cash-primary) 16%, transparent);
    background: color-mix(in srgb, var(--cash-primary) 8%, #FFFFFF);
    color: var(--cash-primary);
}

.cash-ledger-columns {
    display: grid;
    grid-template-columns: 92px minmax(230px, 1.25fr) minmax(140px, .75fr) minmax(120px, .65fr) minmax(116px, .58fr) 142px 82px;
    gap: 12px;
    padding: 0 18px 8px;
}

.cash-ledger-list {
    gap: 10px;
}

.cash-move-row {
    border: 1px solid color-mix(in srgb, var(--row-color) 18%, var(--cash-line));
    border-radius: 17px;
    background: #FFFFFF;
    box-shadow: inset 4px 0 0 var(--row-color), 0 14px 30px -28px rgba(15,23,42,.48);
}

.cash-move-row:hover {
    background: color-mix(in srgb, var(--row-color) 3%, #FFFFFF);
    transform: translateY(-1px);
}

.cash-type-mark {
    border: 0;
    background: color-mix(in srgb, var(--row-color) 12%, #FFFFFF);
}

.cash-move-description strong {
    text-wrap: pretty;
}

.cash-edited-note {
    color: #A45B13 !important;
}

.cash-meta-pill {
    border-radius: 11px;
    background: color-mix(in srgb, var(--pill-color, var(--cash-primary)) 7%, #FFFFFF);
}

.cash-ref,
.cash-user,
.cash-room-detail {
    line-height: 1.35;
}

.cash-amount {
    font-size: 1.12rem;
}

.cash-reserve-link {
    color: var(--cash-primary);
}

.cash-icon-btn {
    border-radius: 12px;
}

.cash-modal-card {
    border: 1px solid color-mix(in srgb, var(--cash-accent) 24%, transparent);
    background: #FFFDF8;
}

.cash-modal-head {
    background:
        radial-gradient(circle at right top, color-mix(in srgb, var(--cash-accent) 24%, transparent), transparent 12rem),
        linear-gradient(135deg, var(--cash-primary), var(--cash-secondary));
}

@media (max-width: 1180px) {
    .cash-ledger-columns {
        display: none;
    }
}

@media (max-width: 900px) {
    .cash-hero {
        grid-template-columns: 1fr;
    }

    .cash-hero-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .cash-balance-strip {
        margin: 12px 0 16px;
        grid-template-columns: 1fr;
    }
}

@media (max-width: 620px) {
    .cash-shell {
        width: min(100% - 18px, 1540px);
    }

    .cash-hero {
        padding: 18px;
    }

    .cash-hero h1 {
        font-size: clamp(2.1rem, 13vw, 3rem);
    }

    .cash-hero-actions {
        grid-template-columns: 1fr;
    }

    .cash-hero-insight span {
        flex: 1 1 100%;
    }

    .cash-move-row {
        border-radius: 15px;
        box-shadow: inset 3px 0 0 var(--row-color), 0 10px 24px -24px rgba(15,23,42,.48);
    }
}

    /* FIX dimensiones: el workspace 2-col (sidebar ~300px + ledger) no dejaba ancho
       para la fila de 7 columnas (~1030px) entre 1181–1440px y el panel tiene
       overflow:hidden, por lo que se cortaban las columnas Monto y Acción.
       Se apila el ledger a todo el ancho hasta 1440px (extiende el comportamiento
       que ya existía en ≤1180). La fila sigue en 7 columnas y ahora cabe completa. */
    @media (max-width: 1440px) {
        .cash-workspace { grid-template-columns: 1fr; }
        .cash-sidebar-panel { position: static; }
    }

/* === Ajuste estructural: movimientos de caja === */
.cash-movements-view .cash-shell {
    width: min(1760px, calc(100% - 42px));
    padding-top: 22px;
}

.cash-movements-view .cash-hero {
    grid-template-columns: minmax(0, 1fr) minmax(248px, 304px);
    align-items: start;
    gap: 20px;
    padding: clamp(22px, 2.25vw, 30px);
}

.cash-movements-view .cash-hero h1 {
    max-width: 22ch;
    margin-bottom: 10px;
    font-size: clamp(2.55rem, 3.35vw, 4.05rem);
    line-height: .98;
}

.cash-movements-view .cash-hero p {
    max-width: 76ch;
}

.cash-movements-view .cash-hero-insight {
    max-width: 850px;
    gap: 8px;
    margin-top: 16px;
}

.cash-movements-view .cash-hero-insight span {
    min-width: 112px;
    padding: 9px 11px;
}

.cash-movements-view .cash-hero-actions {
    align-self: center;
    min-height: 0;
    gap: 9px;
    padding: 12px;
}

.cash-movements-view .cash-action {
    min-height: 44px;
}

.cash-movements-view .cash-balance-strip {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin: 16px 0 18px;
}

.cash-movements-view .cash-stat {
    min-height: 124px;
    padding: 18px 20px;
}

.cash-movements-view .cash-flow-bar {
    height: 8px;
    margin: -3px 4px 0;
}

.cash-movements-view .cash-filter-dock {
    margin: 0 0 18px;
}

.cash-movements-view .cash-filter-form {
    grid-template-columns: repeat(24, minmax(0, 1fr));
    align-items: end;
}

.cash-movements-view .cash-field {
    grid-column: span 4;
}

.cash-movements-view .cash-field.wide {
    grid-column: span 6;
}

.cash-movements-view .cash-filter-actions {
    grid-column: span 6;
}

.cash-movements-view .cash-workspace {
    grid-template-columns: minmax(252px, 292px) minmax(0, 1fr);
    gap: 16px;
}

.cash-movements-view .cash-sidebar-panel {
    padding: 16px;
}

.cash-movements-view .cash-audit-item {
    min-height: 58px;
}

.cash-movements-view .cash-ledger-panel {
    min-width: 0;
}

.cash-movements-view .cash-ledger-head {
    margin-bottom: 12px;
    padding: 16px 18px;
}

.cash-movements-view .cash-ledger-columns,
.cash-movements-view .cash-move-row {
    grid-template-columns: 96px minmax(250px, 1.35fr) minmax(135px, .72fr) minmax(122px, .62fr) minmax(106px, .54fr) minmax(188px, .9fr) 46px;
}

.cash-movements-view .cash-ledger-columns {
    align-items: center;
    padding: 0 18px 9px;
}

.cash-movements-view .cash-move-row {
    gap: 14px;
    align-items: center;
    padding: 15px 16px;
}

.cash-movements-view .cash-move-main {
    grid-template-columns: 40px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
}

.cash-movements-view .cash-type-mark {
    width: 40px;
    height: 40px;
}

.cash-movements-view .cash-amount-cell {
    min-width: 0;
}

.cash-movements-view .cash-reserve-link {
    align-items: center;
    max-width: 100%;
}

.cash-movements-view .cash-room-detail {
    max-width: 28ch;
    margin-left: auto;
}

.cash-movements-view .cash-row-actions {
    justify-content: flex-end;
}

.cash-movements-view .cash-icon-btn {
    width: 34px;
    height: 34px;
}

@media (max-width: 1560px) {
    .cash-movements-view .cash-shell {
        width: min(100% - 30px, 1540px);
    }

    .cash-movements-view .cash-workspace {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-sidebar-panel {
        position: static;
        display: grid;
        grid-template-columns: minmax(0, 1.25fr) minmax(240px, .75fr);
        gap: 16px;
        align-items: start;
    }

    .cash-movements-view .cash-active-filters {
        margin-top: 0;
        padding-top: 0;
        border-top: 0;
    }
}

@media (max-width: 1180px) {
    .cash-movements-view .cash-ledger-columns {
        display: none;
    }

    .cash-movements-view .cash-move-row {
        grid-template-columns: 1fr;
        gap: 11px;
    }

    .cash-movements-view .cash-user {
        display: block;
    }

    .cash-movements-view .cash-amount-cell,
    .cash-movements-view .cash-row-actions {
        text-align: left;
        justify-content: flex-start;
    }

    .cash-movements-view .cash-room-detail {
        max-width: none;
        margin-left: 0;
    }
}

@media (max-width: 900px) {
    .cash-movements-view .cash-hero {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-hero h1 {
        max-width: 14ch;
    }

    .cash-movements-view .cash-hero-actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .cash-movements-view .cash-balance-strip,
    .cash-movements-view .cash-sidebar-panel {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-filter-form {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-field,
    .cash-movements-view .cash-field.wide,
    .cash-movements-view .cash-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 620px) {
    .cash-movements-view .cash-shell {
        width: min(100% - 18px, 1540px);
    }

    .cash-movements-view .cash-hero-actions {
        grid-template-columns: 1fr;
    }
}

/* === Capa final: mismo lenguaje visual que Notificaciones === */
.cash-movements-view {
    --cash-bg: #fbfaf6;
    --cash-panel: rgba(255, 255, 255, .78);
    --cash-panel-strong: #fffdf8;
    --cash-line: color-mix(in srgb, var(--cash-primary) 9%, #e9e2d7);
    --cash-text: #344054;
    --cash-ink: color-mix(in srgb, var(--cash-primary) 56%, #475467);
    --cash-muted: #748094;
    --cash-soft: color-mix(in srgb, var(--cash-accent) 5%, #f8f5ee);
    --cash-tone-blue: #3f7891;
    --cash-tone-sage: #2f8a70;
    --cash-tone-amber: #b98a35;
    --cash-tone-coral: #b66b5f;
    --cash-tone-indigo: #6e6aa9;
    background:
        radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--cash-accent) 12%, transparent), transparent 25rem),
        radial-gradient(circle at 35% 8%, color-mix(in srgb, var(--cash-tone-sage) 8%, transparent), transparent 24rem),
        radial-gradient(circle at 74% 4%, color-mix(in srgb, var(--cash-tone-indigo) 7%, transparent), transparent 25rem),
        radial-gradient(circle at 96% 8%, color-mix(in srgb, var(--cash-primary) 7%, transparent), transparent 30rem),
        linear-gradient(180deg, #fcfbf8 0%, color-mix(in srgb, var(--cash-accent) 4%, #f4f1ea) 100%);
    color: var(--cash-text);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
}

.cash-movements-view .cash-shell {
    width: min(1440px, calc(100% - 32px));
    padding: 26px 0 52px;
}

.cash-movements-view .cash-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin: 0 0 16px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
    overflow: visible;
}

.cash-movements-view .cash-hero::before {
    display: none;
}

.cash-movements-view .cash-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 30px;
    padding: 0 10px;
    border: 1px solid color-mix(in srgb, var(--cash-accent) 26%, #ded6c8);
    border-radius: 999px;
    background: rgba(255,255,255,.72);
    color: color-mix(in srgb, var(--cash-primary) 58%, #667085);
    font-size: .78rem;
    font-weight: 640;
    letter-spacing: 0;
    text-transform: none;
}

.cash-movements-view .cash-kicker i {
    color: color-mix(in srgb, var(--cash-accent) 76%, #795a16);
}

.cash-movements-view .cash-hero h1 {
    max-width: none;
    margin: 11px 0 0;
    color: var(--cash-ink);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
    font-size: clamp(1.7rem, 3vw, 2.85rem);
    line-height: 1.08;
    font-weight: 540;
    letter-spacing: 0;
    text-wrap: balance;
}

.cash-movements-view .cash-hero p {
    max-width: 720px;
    margin: 10px 0 0;
    color: #526176;
    font-size: .98rem;
    line-height: 1.55;
    font-weight: 430;
}

.cash-movements-view .cash-hero-insight {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    max-width: none;
    margin-top: 14px;
}

.cash-movements-view .cash-hero-insight span {
    min-width: 0;
    padding: 8px 10px;
    border: 1px solid var(--cash-line);
    border-radius: 999px;
    background: rgba(255,255,255,.68);
}

.cash-movements-view .cash-hero-insight small {
    display: inline;
    margin-right: 5px;
    color: var(--cash-muted);
    font-size: .73rem;
    font-weight: 650;
    letter-spacing: 0;
    text-transform: none;
}

.cash-movements-view .cash-hero-insight strong {
    display: inline;
    margin: 0;
    color: var(--cash-ink);
    font-size: .78rem;
    font-weight: 720;
}

.cash-movements-view .cash-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    align-self: end;
    gap: 9px;
    min-height: 0;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
}

.cash-movements-view .cash-action,
.cash-movements-view .cash-form-button,
.cash-movements-view .cash-form-link,
.cash-movements-view .cash-cancel,
.cash-movements-view .cash-save {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: auto;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid var(--cash-line);
    border-radius: 12px;
    background: rgba(255,255,255,.78);
    color: var(--cash-ink);
    box-shadow: 0 14px 34px -32px color-mix(in srgb, var(--cash-primary) 42%, transparent);
    font-size: .84rem;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0;
    text-decoration: none;
    transition: transform .18s cubic-bezier(.22, 1, .36, 1), border-color .18s ease, background .18s ease, color .18s ease, box-shadow .18s ease;
}

.cash-movements-view .cash-action:hover,
.cash-movements-view .cash-form-button:hover,
.cash-movements-view .cash-form-link:hover,
.cash-movements-view .cash-cancel:hover,
.cash-movements-view .cash-save:hover,
.cash-movements-view .cash-action:focus-visible,
.cash-movements-view .cash-form-button:focus-visible,
.cash-movements-view .cash-form-link:focus-visible,
.cash-movements-view .cash-cancel:focus-visible,
.cash-movements-view .cash-save:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--cash-accent) 34%, var(--cash-line));
    background: color-mix(in srgb, var(--cash-accent) 7%, #fffdf8);
    color: color-mix(in srgb, var(--cash-primary) 72%, #334155);
    box-shadow: 0 18px 38px -32px color-mix(in srgb, var(--cash-accent) 42%, transparent);
    outline: none;
}

.cash-movements-view .cash-action:active,
.cash-movements-view .cash-form-button:active,
.cash-movements-view .cash-form-link:active,
.cash-movements-view .cash-cancel:active,
.cash-movements-view .cash-save:active {
    transform: translateY(0) scale(.99);
}

.cash-movements-view .cash-action.is-main,
.cash-movements-view .cash-form-button,
.cash-movements-view .cash-save {
    border-color: color-mix(in srgb, var(--cash-primary) 18%, var(--cash-line));
    background: color-mix(in srgb, var(--cash-primary) 92%, #243044);
    color: var(--brand-action-text, #fffdf8);
}

.cash-movements-view .cash-action.is-main:hover,
.cash-movements-view .cash-form-button:hover,
.cash-movements-view .cash-save:hover {
    background: color-mix(in srgb, var(--cash-primary) 82%, #111827);
    color: var(--brand-action-text, #fffdf8);
}

.cash-movements-view .cash-balance-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin: 0 0 14px;
}

.cash-movements-view .cash-stat {
    min-height: 118px;
    padding: 15px;
    border: 1px solid color-mix(in srgb, var(--stat-color) 18%, var(--cash-line));
    border-radius: 14px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--stat-color) 10%, transparent), transparent 7rem),
        rgba(255,255,255,.78);
    box-shadow: none;
}

.cash-movements-view .cash-stat::after {
    display: none;
}

.cash-movements-view .cash-stat-label {
    align-items: center;
    color: var(--cash-muted);
    font-size: .72rem;
    font-weight: 720;
    letter-spacing: .05em;
}

.cash-movements-view .cash-stat-label i {
    width: 30px;
    height: 30px;
    border: 1px solid color-mix(in srgb, var(--stat-color) 16%, var(--cash-line));
    border-radius: 10px;
    background: color-mix(in srgb, var(--stat-color) 7%, #fffdf8);
}

.cash-movements-view .cash-stat strong {
    margin-top: 8px;
    color: color-mix(in srgb, var(--stat-color) 64%, var(--cash-ink));
    font-size: clamp(1.35rem, 2.1vw, 2rem);
    font-weight: 580;
}

.cash-movements-view .cash-stat small {
    margin-top: 8px;
    color: var(--cash-muted);
    font-size: .76rem;
    font-weight: 560;
}

.cash-movements-view .cash-stat.income { --stat-color: var(--cash-tone-sage); --stat-soft: color-mix(in srgb, var(--cash-tone-sage) 9%, #fffdf8); }
.cash-movements-view .cash-stat.expense { --stat-color: var(--cash-tone-coral); --stat-soft: color-mix(in srgb, var(--cash-tone-coral) 9%, #fffdf8); }
.cash-movements-view .cash-stat.balance { --stat-color: <?= $balance_es_positivo ? 'var(--cash-tone-blue)' : 'var(--cash-tone-coral)' ?>; --stat-soft: <?= $balance_es_positivo ? 'color-mix(in srgb, var(--cash-tone-blue) 9%, #fffdf8)' : 'color-mix(in srgb, var(--cash-tone-coral) 9%, #fffdf8)' ?>; }

.cash-movements-view .cash-flow-bar {
    grid-column: 1 / -1;
    height: 9px;
    margin: 0;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 7%, #e9e2d7);
    border-radius: 999px;
    background: color-mix(in srgb, var(--cash-primary) 6%, #f4f1ea);
}

.cash-movements-view .cash-flow-bar span:first-child {
    background: color-mix(in srgb, var(--cash-tone-sage) 72%, #fffdf8);
}

.cash-movements-view .cash-flow-bar span:last-child {
    background: color-mix(in srgb, var(--cash-tone-coral) 72%, #fffdf8);
}

.cash-movements-view .cash-filter-dock {
    display: block;
    margin: 0 0 14px;
    overflow: hidden;
    border: 1px solid var(--cash-line);
    border-radius: 16px;
    background: rgba(255,255,255,.78);
    box-shadow: none;
}

.cash-movements-view .cash-filter-dock.hidden {
    display: none;
}

.cash-movements-view .cash-filter-title {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 14px 15px;
    border-bottom: 1px solid var(--cash-line);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cash-accent) 9%, transparent), transparent 12rem),
        linear-gradient(180deg, rgba(255,255,255,.78), color-mix(in srgb, var(--cash-accent) 4%, #fffdf8));
}

.cash-movements-view .cash-filter-title h2,
.cash-movements-view .cash-side-heading,
.cash-movements-view .cash-ledger-head h2,
.cash-movements-view .cash-modal-head h3 {
    margin: 0;
    color: var(--cash-ink);
    font-size: 1rem;
    font-weight: 760;
    letter-spacing: 0;
}

.cash-movements-view .cash-filter-title span,
.cash-movements-view .cash-ledger-head p {
    margin: 5px 0 0;
    color: var(--cash-muted);
    font-size: .82rem;
    line-height: 1.35;
    font-weight: 520;
}

.cash-movements-view .cash-filter-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 11px;
    align-items: end;
    padding: 14px;
}

.cash-movements-view .cash-field {
    grid-column: span 2;
}

.cash-movements-view .cash-field.wide {
    grid-column: span 3;
}

.cash-movements-view .cash-filter-actions {
    grid-column: span 3;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 8px;
}

.cash-movements-view .cash-field label,
.cash-movements-view .cash-edit-field label {
    display: block;
    margin: 0 0 7px;
    color: color-mix(in srgb, var(--cash-primary) 62%, #475467);
    font-size: .72rem;
    font-weight: 760;
    letter-spacing: .05em;
    text-transform: uppercase;
}

.cash-movements-view .cash-input {
    width: 100%;
    min-height: 42px;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 10%, #ded6c8);
    border-radius: 12px;
    background: rgba(255,255,255,.84);
    color: var(--cash-ink);
    box-shadow: none;
    font-size: .88rem;
    font-weight: 560;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.cash-movements-view .cash-input:hover {
    border-color: color-mix(in srgb, var(--cash-accent) 30%, var(--cash-line));
}

.cash-movements-view .cash-input:focus {
    border-color: color-mix(in srgb, var(--cash-accent) 46%, var(--cash-line));
    background: #fffdf8;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--cash-accent) 14%, transparent);
    outline: none;
}

.cash-movements-view .cash-workspace {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

.cash-movements-view .cash-sidebar-panel {
    position: static;
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(260px, .75fr);
    gap: 14px;
    align-items: start;
    padding: 15px;
    border: 1px solid var(--cash-line);
    border-radius: 16px;
    background: rgba(255,255,255,.78);
    box-shadow: none;
}

.cash-movements-view .cash-audit-list {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    margin-top: 12px;
}

.cash-movements-view .cash-audit-item {
    min-height: 64px;
    padding: 10px;
    border: 1px solid color-mix(in srgb, var(--cash-primary) 9%, #e9e2d7);
    border-radius: 13px;
    background: color-mix(in srgb, var(--cash-primary) 3%, #fffdf8);
}

.cash-movements-view .cash-audit-item i {
    background: color-mix(in srgb, var(--cash-accent) 8%, #fffdf8);
    color: color-mix(in srgb, var(--cash-accent) 76%, #795a16);
}

.cash-movements-view .cash-audit-item span {
    color: var(--cash-muted);
    font-size: .7rem;
    font-weight: 720;
}

.cash-movements-view .cash-audit-item strong {
    color: var(--cash-ink);
    font-size: .86rem;
    font-weight: 720;
}

.cash-movements-view .cash-active-filters {
    margin: 0;
    padding: 0;
    border: 0;
}

.cash-movements-view .cash-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 12px;
}

.cash-movements-view .cash-chip {
    display: inline-flex;
    align-items: center;
    min-height: 28px;
    padding: 0 9px;
    border: 1px solid color-mix(in srgb, var(--cash-accent) 20%, var(--cash-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--cash-accent) 7%, #fffdf8);
    color: color-mix(in srgb, var(--cash-accent) 72%, #334155);
    font-size: .74rem;
    font-weight: 700;
}

.cash-movements-view .cash-ledger-panel {
    overflow: hidden;
    border: 1px solid var(--cash-line);
    border-radius: 16px;
    background: rgba(255,255,255,.78);
    box-shadow: none;
}

.cash-movements-view .cash-ledger-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    margin: 0;
    padding: 15px;
    border: 0;
    border-bottom: 1px solid var(--cash-line);
    border-radius: 0;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cash-tone-blue) 8%, transparent), transparent 12rem),
        linear-gradient(180deg, rgba(255,255,255,.74), color-mix(in srgb, var(--cash-accent) 4%, #fffdf8));
    box-shadow: none;
}

.cash-movements-view .cash-count-pill {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 10px;
    border: 1px solid color-mix(in srgb, var(--cash-tone-blue) 18%, var(--cash-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--cash-tone-blue) 7%, #fffdf8);
    color: color-mix(in srgb, var(--cash-tone-blue) 72%, #334155);
    font-size: .75rem;
    font-weight: 720;
}

.cash-movements-view .cash-ledger-columns {
    display: grid;
    grid-template-columns: 96px minmax(240px, 1.45fr) minmax(145px, .8fr) minmax(130px, .7fr) minmax(118px, .6fr) minmax(188px, .9fr) 54px;
    gap: 12px;
    align-items: center;
    padding: 12px 15px 8px;
    border-bottom: 1px solid color-mix(in srgb, var(--cash-primary) 6%, #ede7dc);
    background: rgba(255,253,248,.56);
}

.cash-movements-view .cash-ledger-columns span {
    color: var(--cash-muted);
    font-size: .68rem;
    font-weight: 760;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cash-movements-view .cash-ledger-list {
    display: grid;
    gap: 0;
}

.cash-movements-view .cash-move-row {
    display: grid;
    grid-template-columns: 96px minmax(240px, 1.45fr) minmax(145px, .8fr) minmax(130px, .7fr) minmax(118px, .6fr) minmax(188px, .9fr) 54px;
    gap: 12px;
    align-items: center;
    padding: 13px 15px;
    border: 0;
    border-bottom: 1px solid color-mix(in srgb, var(--row-color) 10%, var(--cash-line));
    border-radius: 0;
    background: rgba(255,255,255,.58);
    box-shadow: none;
    transition: background .16s ease, transform .16s cubic-bezier(.22, 1, .36, 1);
}

.cash-movements-view .cash-move-row:last-child {
    border-bottom: 0;
}

.cash-movements-view .cash-move-row:hover {
    transform: none;
    background: color-mix(in srgb, var(--row-color) 4%, #fffdf8);
}

.cash-movements-view .cash-move-date strong,
.cash-movements-view .cash-move-description strong {
    color: var(--cash-ink);
}

.cash-movements-view .cash-move-date span,
.cash-movements-view .cash-move-description span,
.cash-movements-view .cash-ref,
.cash-movements-view .cash-user,
.cash-movements-view .cash-room-detail {
    color: var(--cash-muted);
}

.cash-movements-view .cash-type-mark {
    width: 38px;
    height: 38px;
    border: 1px solid color-mix(in srgb, var(--row-color) 16%, var(--cash-line));
    border-radius: 12px;
    background: color-mix(in srgb, var(--row-color) 8%, #fffdf8);
    color: color-mix(in srgb, var(--row-color) 78%, #334155);
}

.cash-movements-view .cash-meta-pill {
    min-height: 28px;
    padding: 0 9px;
    border: 1px solid color-mix(in srgb, var(--pill-color, var(--cash-primary)) 16%, var(--cash-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--pill-color, var(--cash-primary)) 7%, #fffdf8);
    color: color-mix(in srgb, var(--pill-color, var(--cash-primary)) 72%, #334155);
    font-size: .74rem;
    font-weight: 720;
}

.cash-movements-view .cash-amount {
    color: color-mix(in srgb, var(--row-color) 74%, var(--cash-ink));
    font-size: 1rem;
    font-weight: 760;
}

.cash-movements-view .cash-reserve-link {
    color: color-mix(in srgb, var(--cash-tone-blue) 74%, #334155);
}

.cash-movements-view .cash-row-actions {
    display: inline-flex;
    justify-content: flex-end;
    gap: 6px;
}

.cash-movements-view .cash-icon-btn {
    width: 34px;
    height: 34px;
    border: 1px solid var(--cash-line);
    border-radius: 11px;
    background: rgba(255,255,255,.74);
    color: color-mix(in srgb, var(--cash-primary) 58%, #64748b);
    transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.cash-movements-view .cash-icon-btn:hover,
.cash-movements-view .cash-icon-btn:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--cash-tone-blue) 26%, var(--cash-line));
    background: color-mix(in srgb, var(--cash-tone-blue) 7%, #fffdf8);
    color: color-mix(in srgb, var(--cash-tone-blue) 78%, #334155);
    outline: none;
}

.cash-movements-view .cash-icon-btn.is-edit:hover,
.cash-movements-view .cash-icon-btn.is-edit:focus-visible {
    border-color: color-mix(in srgb, var(--cash-tone-amber) 28%, var(--cash-line));
    background: color-mix(in srgb, var(--cash-tone-amber) 7%, #fffdf8);
    color: color-mix(in srgb, var(--cash-tone-amber) 78%, #334155);
}

.cash-movements-view .cash-empty {
    margin: 18px;
    border: 1px dashed color-mix(in srgb, var(--cash-primary) 14%, #d8d0c3);
    border-radius: 14px;
    background: rgba(255,255,255,.54);
    color: var(--cash-muted);
}

.cash-movements-view .cash-modal {
    background: rgba(15, 23, 42, .34);
    backdrop-filter: blur(5px);
}

.cash-movements-view .cash-modal-card {
    overflow: hidden;
    border: 1px solid var(--cash-line);
    border-radius: 18px;
    background: #fffdf8;
    box-shadow: 0 30px 76px -42px rgba(15, 23, 42, .68);
}

.cash-movements-view .cash-modal-head {
    padding: 16px 18px;
    border-bottom: 1px solid var(--cash-line);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--cash-accent) 10%, transparent), transparent 12rem),
        linear-gradient(180deg, rgba(255,255,255,.86), color-mix(in srgb, var(--cash-accent) 4%, #fffdf8));
}

.cash-movements-view .cash-modal-close {
    border: 1px solid var(--cash-line);
    background: rgba(255,255,255,.72);
    color: var(--cash-ink);
}

@media (max-width: 1180px) {
    .cash-movements-view .cash-ledger-columns {
        display: none;
    }

    .cash-movements-view .cash-move-row {
        grid-template-columns: 1fr;
        gap: 10px;
        padding: 14px;
        border: 1px solid color-mix(in srgb, var(--row-color) 15%, var(--cash-line));
        border-radius: 14px;
        margin: 10px;
        background: rgba(255,255,255,.72);
    }

    .cash-movements-view .cash-move-row + .cash-move-row {
        margin-top: 0;
    }

    .cash-movements-view .cash-row-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 900px) {
    .cash-movements-view .cash-hero,
    .cash-movements-view .cash-ledger-head,
    .cash-movements-view .cash-filter-title {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-hero-actions,
    .cash-movements-view .cash-filter-actions {
        justify-content: stretch;
    }

    .cash-movements-view .cash-action,
    .cash-movements-view .cash-form-button,
    .cash-movements-view .cash-form-link {
        flex: 1 1 140px;
    }

    .cash-movements-view .cash-balance-strip,
    .cash-movements-view .cash-sidebar-panel,
    .cash-movements-view .cash-audit-list {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-filter-form {
        grid-template-columns: 1fr;
    }

    .cash-movements-view .cash-field,
    .cash-movements-view .cash-field.wide,
    .cash-movements-view .cash-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 620px) {
    .cash-movements-view .cash-shell {
        width: min(100% - 22px, 1440px);
        padding-top: 18px;
    }

    .cash-movements-view .cash-hero-actions,
    .cash-movements-view .cash-filter-actions {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>

<div class="cash-movements-view">
    <div class="cash-shell">
        <header class="cash-hero">
            <div>
                <div class="cash-kicker">
                    <i class="fas fa-receipt"></i>
                    Caja operativa
                </div>
                <h1>Movimientos de caja</h1>
                <p>Libro de actividad del corte con ingresos, gastos, metodos de pago, referencias y trazabilidad de usuario.</p>
                <div class="cash-hero-insight" aria-label="Resumen de consulta">
                    <span>
                        <small>Periodo</small>
                        <strong><?= caja_mov_safe($periodo_label) ?></strong>
                    </span>
                    <span>
                        <small>Registros</small>
                        <strong><?= (int)$movimientos_count ?></strong>
                    </span>
                    <span>
                        <small>Filtros</small>
                        <strong><?= (int)$active_filter_count ?> activos</strong>
                    </span>
                </div>
            </div>

            <div class="cash-hero-actions">
                <a href="<?= url('caja') ?>" class="cash-action">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>
                <button type="button" onclick="toggleFiltros()" class="cash-action">
                    <i class="fas fa-filter"></i>
                    <span>Filtros<?= $active_filter_count ? ' (' . (int)$active_filter_count . ')' : '' ?></span>
                </button>
                <button type="button" onclick="exportarMovimientos()" class="cash-action is-main">
                    <i class="fas fa-download"></i>
                    <span>Exportar</span>
                </button>
            </div>
        </header>

        <section class="cash-balance-strip" aria-label="Resumen de movimientos">
            <article class="cash-stat income">
                <div class="cash-stat-label">
                    <span>Total ingresos</span>
                    <i class="fas fa-arrow-down"></i>
                </div>
                <strong>+<?= caja_mov_money($ingresos_total) ?></strong>
                <small><?= (int)$ingresos_pct ?>% del flujo filtrado</small>
            </article>

            <article class="cash-stat expense">
                <div class="cash-stat-label">
                    <span>Total gastos</span>
                    <i class="fas fa-arrow-up"></i>
                </div>
                <strong>-<?= caja_mov_money($gastos_total) ?></strong>
                <small><?= (int)$gastos_pct ?>% del flujo filtrado</small>
            </article>

            <article class="cash-stat balance">
                <div class="cash-stat-label">
                    <span>Balance</span>
                    <i class="fas fa-scale-balanced"></i>
                </div>
                <strong><?= caja_mov_money($balance_total, true) ?></strong>
                <small><?= $balance_es_positivo ? 'Saldo positivo' : 'Saldo negativo' ?></small>
            </article>

            <div class="cash-flow-bar" aria-hidden="true">
                <span></span>
                <span></span>
            </div>
        </section>

        <section id="panelFiltros" class="cash-filter-dock">
            <div class="cash-filter-title">
                <div>
                    <h2>Filtros de consulta</h2>
                    <span><?= $active_filter_count ? (int)$active_filter_count . ' filtros activos' : 'Sin filtros aplicados' ?></span>
                </div>
                <span><?= caja_mov_safe($periodo_label) ?></span>
            </div>

            <form method="GET" action="<?= url('caja/movimientos') ?>" class="cash-filter-form" data-auto-filter-form>
                <div class="cash-field">
                    <label>Tipo</label>
                    <select name="tipo" class="cash-input">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $key => $tipo): ?>
                            <option value="<?= caja_mov_safe($key) ?>" <?= (($filtros['tipo'] ?? '') == $key) ? 'selected' : '' ?>>
                                <?= caja_mov_safe($tipo['label'] ?? $key) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cash-field">
                    <label>Categoria</label>
                    <select name="categoria" class="cash-input">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= caja_mov_safe($cat['id'] ?? '') ?>" <?= (($filtros['categoria_id'] ?? '') == ($cat['id'] ?? null)) ? 'selected' : '' ?>>
                                <?= caja_mov_safe($cat['nombre'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cash-field">
                    <label>Metodo de pago</label>
                    <select name="metodo_pago" class="cash-input">
                        <option value="">Todos</option>
                        <?php foreach ($metodos_pago as $key => $metodo): ?>
                            <option value="<?= caja_mov_safe($key) ?>" <?= (($filtros['metodo_pago'] ?? '') == $key) ? 'selected' : '' ?>>
                                <?= caja_mov_safe($metodo['label'] ?? $key) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cash-field wide">
                    <label>Buscar</label>
                    <input type="text" name="buscar" value="<?= caja_mov_safe($filtros['buscar'] ?? '', '') ?>" placeholder="Descripcion, referencia o proveedor" class="cash-input">
                </div>

                <div class="cash-field">
                    <label>Fecha inicio</label>
                    <input type="date" name="fecha_inicio" value="<?= caja_mov_safe($filtros['fecha_inicio'] ?? '', '') ?>" class="cash-input">
                </div>

                <div class="cash-field">
                    <label>Fecha fin</label>
                    <input type="date" name="fecha_fin" value="<?= caja_mov_safe($filtros['fecha_fin'] ?? '', '') ?>" class="cash-input">
                </div>

                <div class="cash-filter-actions">
                    <button type="submit" class="cash-form-button">
                        <i class="fas fa-search"></i>
                        Buscar
                    </button>
                    <a href="<?= url('caja/movimientos') ?>" class="cash-form-link">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <section class="cash-workspace">
            <aside class="cash-sidebar-panel">
                <h2 class="cash-side-heading">Lectura rapida</h2>
                <div class="cash-audit-list">
                    <div class="cash-audit-item">
                        <i class="fas fa-layer-group"></i>
                        <div>
                            <span>Registros</span>
                            <strong><?= (int)$movimientos_count ?> movimientos</strong>
                        </div>
                    </div>
                    <div class="cash-audit-item">
                        <i class="fas fa-calendar-day"></i>
                        <div>
                            <span>Periodo</span>
                            <strong><?= caja_mov_safe($periodo_label) ?></strong>
                        </div>
                    </div>
                    <div class="cash-audit-item">
                        <i class="fas fa-sliders"></i>
                        <div>
                            <span>Filtros activos</span>
                            <strong><?= (int)$active_filter_count ?></strong>
                        </div>
                    </div>
                </div>

                <div class="cash-active-filters">
                    <h2 class="cash-side-heading">Consulta</h2>
                    <div class="cash-chip-list">
                        <?php if (!empty($filtros['tipo'])): ?>
                            <span class="cash-chip">Tipo: <?= caja_mov_safe($tipos[$filtros['tipo']]['label'] ?? $filtros['tipo']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filtros['metodo_pago'])): ?>
                            <span class="cash-chip">Metodo: <?= caja_mov_safe($metodos_pago[$filtros['metodo_pago']]['label'] ?? $filtros['metodo_pago']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filtros['buscar'])): ?>
                            <span class="cash-chip">Busca: <?= caja_mov_safe($filtros['buscar']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])): ?>
                            <span class="cash-chip"><?= caja_mov_safe($periodo_label) ?></span>
                        <?php endif; ?>
                        <?php if ($active_filter_count === 0): ?>
                            <span class="cash-chip">Corte abierto</span>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <main class="cash-ledger-panel">
                <div class="cash-ledger-head">
                    <div>
                        <h2>Libro de movimientos</h2>
                        <p>Actividad ordenada por fecha de registro.</p>
                    </div>
                    <span class="cash-count-pill"><?= (int)$movimientos_count ?> registros</span>
                </div>

                <?php if (empty($movimientos)): ?>
                    <div class="cash-empty">
                        <i class="fas fa-inbox"></i>
                        <strong>Sin movimientos encontrados</strong>
                        <p>No hay actividad con los filtros seleccionados.</p>
                    </div>
                <?php else: ?>
                    <div class="cash-ledger-columns" aria-hidden="true">
                        <span>Fecha</span>
                        <span>Movimiento</span>
                        <span>Categoria</span>
                        <span>Metodo</span>
                        <span>Usuario</span>
                        <span>Monto</span>
                        <span>Accion</span>
                    </div>
                    <div class="cash-ledger-list">
                        <?php foreach ($movimientos as $mov): ?>
                            <?php
                                $tipo_key = $mov['tipo'] ?? 'gasto';
                                $es_ingreso = $tipo_key === 'ingreso';
                                $row_color = $es_ingreso ? '#16824E' : '#C24135';
                                $method_key = $mov['metodo_pago'] ?? '';
                                $method_meta = $metodos_pago[$method_key] ?? ['label' => ucfirst((string)$method_key), 'icon' => 'dollar-sign', 'color' => 'gray'];
                                $method_color = caja_mov_method_color($method_key);
                                $category_icon = $mov['categoria_icono'] ?? 'fas fa-tag';
                                $category_color = $mov['categoria_color'] ?? '#64748B';
                                $can_edit = (($mov['corte_id'] ?? null) == ($corteActual['id'] ?? 0) && empty($mov['editado']));
                            ?>
                            <article class="cash-move-row" data-id="<?= caja_mov_safe($mov['id'] ?? '') ?>" style="--row-color: <?= $row_color ?>;">
                                <div class="cash-move-date">
                                    <strong><?= caja_mov_safe(caja_mov_date($mov['created_at'] ?? null)) ?></strong>
                                    <span><?= caja_mov_safe(caja_mov_date($mov['created_at'] ?? null, 'H:i:s')) ?></span>
                                </div>

                                <div class="cash-move-main">
                                    <span class="cash-type-mark">
                                        <i class="fas fa-arrow-<?= $es_ingreso ? 'down' : 'up' ?>"></i>
                                    </span>
                                    <div class="cash-move-description">
                                        <strong><?= caja_mov_safe($mov['descripcion'] ?? '') ?></strong>
                                        <span><?= caja_mov_safe($tipos[$tipo_key]['label'] ?? ucfirst((string)$tipo_key)) ?></span>
                                        <?php if (!empty($mov['editado'])): ?>
                                            <span class="cash-edited-note">
                                                <i class="fas fa-edit"></i>
                                                Editado: <?= caja_mov_safe($mov['motivo_edicion'] ?? '') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="cash-meta-stack">
                                    <?php if (!empty($mov['categoria_nombre'])): ?>
                                        <span class="cash-meta-pill" style="--pill-color: <?= caja_mov_safe($category_color) ?>;">
                                            <i class="<?= caja_mov_safe($category_icon) ?>"></i>
                                            <?= caja_mov_safe($mov['categoria_nombre']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="cash-meta-pill">
                                            <i class="fas fa-tag"></i>
                                            Sin categoria
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($mov['proveedor'])): ?>
                                        <span class="cash-ref">
                                            <i class="fas fa-building"></i>
                                            <?= caja_mov_safe($mov['proveedor']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="cash-meta-stack">
                                    <span class="cash-meta-pill" style="--pill-color: <?= caja_mov_safe($method_color) ?>;">
                                        <i class="fas fa-<?= caja_mov_safe($method_meta['icon'] ?? 'dollar-sign') ?>"></i>
                                        <?= caja_mov_safe($method_meta['label'] ?? $method_key, 'Metodo') ?>
                                    </span>
                                    <?php if (!empty($mov['referencia'])): ?>
                                        <span class="cash-ref">Ref: <?= caja_mov_safe($mov['referencia']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($mov['comprobante'])): ?>
                                        <span class="cash-ref">Comp: <?= caja_mov_safe($mov['comprobante']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="cash-user">
                                    <?= caja_mov_safe($mov['usuario_nombre'] ?? '') ?>
                                </div>

                                <div class="cash-amount-cell">
                                    <span class="cash-amount">
                                        <?= $es_ingreso ? '+' : '-' ?><?= caja_mov_money($mov['monto'] ?? 0) ?>
                                    </span>
                                    <?php if (!empty($mov['reservacion_id'])): ?>
                                        <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>" class="cash-reserve-link">
                                            <i class="fas fa-bed"></i>
                                            Reserva #<?= caja_mov_safe($mov['reservacion_id']) ?>
                                        </a>
                                        <?php if (!empty($mov['habitaciones_detalle'])): ?>
                                            <div class="cash-room-detail">
                                                <?= caja_mov_safe($mov['habitaciones_detalle']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="cash-row-actions">
                                    <?php if ($can_edit): ?>
                                        <button type="button" onclick="editarMovimiento(<?= (int)$mov['id'] ?>)" class="cash-icon-btn is-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="verDetalle(<?= (int)$mov['id'] ?>)" class="cash-icon-btn" title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </section>
    </div>
</div>

<div id="modalEditar" class="cash-modal hidden">
    <div class="cash-modal-card">
        <div class="cash-modal-head">
            <h3>
                <i class="fas fa-pen-to-square"></i>
                Editar movimiento
            </h3>
            <button type="button" onclick="cerrarModalEditar()" class="cash-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formEditar" class="cash-edit-form">
            <input type="hidden" id="edit_id" name="id">

            <div class="cash-edit-field">
                <label>Descripcion <span class="cash-required">*</span></label>
                <textarea id="edit_descripcion" name="descripcion" rows="3" class="cash-input" required></textarea>
            </div>

            <div class="cash-edit-field">
                <label>Monto <span class="cash-required">*</span></label>
                <div class="cash-money-input">
                    <span>$</span>
                    <input type="number" id="edit_monto" name="monto" data-money-format="true" step="0.01" min="0.01" class="cash-input" required>
                </div>
            </div>

            <div class="cash-edit-field">
                <label>Metodo de pago <span class="cash-required">*</span></label>
                <select id="edit_metodo_pago" name="metodo_pago" class="cash-input" required>
                    <?php foreach ($metodos_pago as $key => $metodo): ?>
                        <option value="<?= caja_mov_safe($key) ?>"><?= caja_mov_safe($metodo['label'] ?? $key) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cash-edit-field">
                <label>Referencia</label>
                <input type="text" id="edit_referencia" name="referencia" class="cash-input">
            </div>

            <div class="cash-edit-field">
                <label>Motivo de la edicion <span class="cash-required">*</span></label>
                <textarea name="motivo_edicion" rows="3" class="cash-input" placeholder="Explica brevemente por que editas este movimiento" required></textarea>
            </div>

            <div class="cash-modal-actions">
                <button type="button" onclick="cerrarModalEditar()" class="cash-cancel">Cancelar</button>
                <button type="submit" class="cash-save">
                    <i class="fas fa-save"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFiltros() {
    const panel = document.getElementById('panelFiltros');
    if (panel) panel.classList.toggle('hidden');
}

function editarMovimiento(id) {
    fetch(`<?= url('caja/movimiento') ?>?id=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const mov = data.data;
            document.getElementById('edit_id').value = mov.id;
            document.getElementById('edit_descripcion').value = mov.descripcion;
            const editMontoInput = document.getElementById('edit_monto');
            if (window.MedisoftMoneyInput) {
                window.MedisoftMoneyInput.set(editMontoInput, mov.monto);
            } else if (editMontoInput) {
                editMontoInput.value = mov.monto;
            }
            document.getElementById('edit_metodo_pago').value = mov.metodo_pago;
            document.getElementById('edit_referencia').value = mov.referencia || '';

            document.getElementById('modalEditar').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            Swal.fire('Error', data.message || 'No se pudo cargar el movimiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al cargar el movimiento', 'error');
    });
}

function cerrarModalEditar() {
    const modal = document.getElementById('modalEditar');
    const form = document.getElementById('formEditar');
    if (modal) modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    if (form) form.reset();
}

document.getElementById('formEditar').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= url('caja/movimiento/editar') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Exito', 'Movimiento actualizado correctamente', 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'No se pudo actualizar el movimiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al actualizar el movimiento', 'error');
    });
});

function verDetalle(id) {
    console.log('Ver detalle de movimiento:', id);
}

function exportarMovimientos() {
    const params = new URLSearchParams(window.location.search);
    params.set('formato', 'excel');
    params.set('corte_id', '<?= $corteActual['id'] ?? 0 ?>');

    window.location.href = '<?= url('caja/exportar') ?>?' + params.toString();
}

document.getElementById('modalEditar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalEditar();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalEditar();
    }

    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        toggleFiltros();
    }
});
</script>
