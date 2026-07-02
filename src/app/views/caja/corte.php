<?php
/**
 * Vista de Corte de Caja
 * Los Cedros
 *
 * NOTA: Esta version incluye correcciones para evitar errores de deprecacion
 * al pasar valores null a htmlspecialchars() en PHP 8+.
 */

if (!function_exists('safe_html')) {
    function safe_html($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('corte_money')) {
    function corte_money($value, $prefix = '') {
        return $prefix . '$' . number_format((float)($value ?? 0), 2);
    }
}

$movimientos = $movimientos ?? [];
$movimientos_categoria = $movimientos_categoria ?? ['ingresos' => [], 'gastos' => []];
$denominaciones = $denominaciones ?? [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.50];
$metodos_pago = $metodos_pago ?? [];

$fechaApertura = $corte['fecha_apertura'] ?? 'now';
$efectivoEsperado = (float)($resumen['efectivo_en_caja'] ?? 0);
$balanceGeneral = (float)($resumen['balance_general'] ?? 0);
?>

<style>
.ccx-page {
    --ccx-primary: var(--brand-primary, #1f3f46);
    --ccx-secondary: var(--brand-secondary, #27333f);
    --ccx-accent: var(--brand-accent, #b58a3c);
    --ccx-ink: color-mix(in srgb, var(--ccx-primary) 54%, #475467);
    --ccx-text: #344054;
    --ccx-muted: #748094;
    --ccx-line: color-mix(in srgb, var(--ccx-primary) 9%, #e9e2d7);
    --ccx-surface: rgba(255,255,255,.78);
    --ccx-focus: color-mix(in srgb, var(--ccx-accent) 22%, transparent);
    min-height: 100vh;
    color: var(--ccx-text);
    background:
        radial-gradient(circle at 8% 0%, color-mix(in srgb, var(--ccx-accent) 12%, transparent), transparent 25rem),
        radial-gradient(circle at 96% 10%, color-mix(in srgb, var(--ccx-primary) 7%, transparent), transparent 30rem),
        linear-gradient(180deg, #fcfbf8 0%, color-mix(in srgb, var(--ccx-accent) 4%, #f4f1ea) 100%);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
}

.ccx-shell {
    width: min(1440px, calc(100% - 32px));
    margin: 0 auto;
    padding: 26px 0 54px;
}

.ccx-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin-bottom: 16px;
}

.ccx-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 30px;
    padding: 0 10px;
    border: 1px solid color-mix(in srgb, var(--ccx-accent) 26%, #ded6c8);
    border-radius: 999px;
    background: rgba(255,255,255,.72);
    color: color-mix(in srgb, var(--ccx-primary) 58%, #667085);
    font-size: .78rem;
    font-weight: 640;
}

.ccx-kicker i {
    color: color-mix(in srgb, var(--ccx-accent) 76%, #795a16);
}

.ccx-title {
    margin: 11px 0 0;
    color: color-mix(in srgb, var(--ccx-primary) 58%, #465467);
    font-size: clamp(1.7rem, 3vw, 2.85rem);
    line-height: 1.08;
    font-weight: 540;
    letter-spacing: 0;
    text-wrap: balance;
}

.ccx-subtitle {
    max-width: 720px;
    margin: 10px 0 0;
    color: #526176;
    font-size: .98rem;
    line-height: 1.55;
    font-weight: 430;
}

.ccx-back {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid var(--ccx-line);
    border-radius: 12px;
    background: rgba(255,255,255,.84);
    color: var(--ccx-ink);
    padding: 0 13px;
    font-size: .84rem;
    font-weight: 560;
    text-decoration: none;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.ccx-back:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ccx-accent) 30%, var(--ccx-line));
    background: color-mix(in srgb, var(--ccx-accent) 7%, #fff);
}

.ccx-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.ccx-stat {
    position: relative;
    overflow: hidden;
    min-height: 100px;
    display: grid;
    align-content: space-between;
    border: 1px solid var(--ccx-line);
    border-radius: 14px;
    background: var(--ccx-surface);
    box-shadow: 0 14px 34px -32px color-mix(in srgb, var(--ccx-primary) 22%, transparent);
    padding: 14px;
}

.ccx-stat::after {
    content: "";
    position: absolute;
    inset: auto 12px 0 12px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--stat-color, var(--ccx-accent));
}

.ccx-stat span {
    color: var(--ccx-muted);
    font-size: .72rem;
    font-weight: 620;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.ccx-stat strong {
    color: color-mix(in srgb, var(--ccx-primary) 58%, #4b5563);
    font-size: clamp(1.25rem, 2.4vw, 1.75rem);
    line-height: 1.04;
    font-weight: 560;
    font-variant-numeric: tabular-nums;
}

.ccx-stat small {
    color: var(--ccx-muted);
    font-size: .78rem;
    font-weight: 430;
}

.ccx-stat.is-start { --stat-color: #64748b; }
.ccx-stat.is-income { --stat-color: #16a34a; }
.ccx-stat.is-expense { --stat-color: #dc2626; }
.ccx-stat.is-cash { --stat-color: color-mix(in srgb, var(--ccx-accent) 70%, #d89d20); }

.ccx-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(330px, 420px);
    gap: 16px;
    align-items: start;
}

.ccx-main,
.ccx-side {
    min-width: 0;
    display: grid;
    gap: 16px;
}

.ccx-panel {
    border: 1px solid var(--ccx-line);
    border-radius: 16px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 16px 38px -34px color-mix(in srgb, var(--ccx-primary) 24%, transparent);
    overflow: hidden;
}

.ccx-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 15px 16px 12px;
    border-bottom: 1px solid color-mix(in srgb, var(--ccx-line) 84%, transparent);
}

.ccx-panel-head h2,
.ccx-panel-head h3 {
    margin: 0;
    color: var(--ccx-ink);
    font-size: 1rem;
    line-height: 1.24;
    font-weight: 620;
}

.ccx-panel-head p {
    margin: 5px 0 0;
    color: var(--ccx-muted);
    font-size: .8rem;
    line-height: 1.42;
    font-weight: 420;
}

.ccx-count {
    min-height: 30px;
    display: inline-flex;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--ccx-accent) 20%, #ddd5c8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--ccx-accent) 8%, #fff);
    color: color-mix(in srgb, var(--ccx-primary) 58%, #667085);
    padding: 0 10px;
    font-size: .76rem;
    font-weight: 620;
    white-space: nowrap;
}

.ccx-table-wrap {
    overflow-x: auto;
}

.ccx-table {
    width: 100%;
    border-collapse: collapse;
}

.ccx-table th {
    padding: 12px 14px;
    border-bottom: 1px solid var(--ccx-line);
    color: var(--ccx-muted);
    font-size: .72rem;
    font-weight: 620;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}

.ccx-table td {
    padding: 12px 14px;
    border-bottom: 1px solid color-mix(in srgb, var(--ccx-line) 72%, transparent);
    color: var(--ccx-text);
    font-size: .88rem;
    line-height: 1.35;
    font-weight: 430;
}

.ccx-table tfoot td {
    border-bottom: 0;
    background: color-mix(in srgb, var(--ccx-accent) 4%, #fff);
    font-weight: 620;
}

.ccx-panel.is-movements {
    align-self: start;
}

.ccx-panel.is-movements .ccx-panel-head {
    align-items: center;
    padding: 12px 14px 10px;
}

.ccx-panel.is-movements .ccx-panel-head h2 {
    font-size: .96rem;
}

.ccx-panel.is-movements .ccx-panel-head p {
    margin-top: 3px;
    font-size: .76rem;
}

.ccx-panel.is-movements .ccx-table th {
    padding: 9px 12px;
    font-size: .68rem;
}

.ccx-panel.is-movements .ccx-table td {
    padding: 10px 12px;
    font-size: .84rem;
}

.ccx-money {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.ccx-money.is-income { color: #15803d; }
.ccx-money.is-expense { color: #b91c1c; }
.ccx-money.is-balance { color: color-mix(in srgb, var(--ccx-primary) 62%, #475569); }

.ccx-method {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--ccx-ink);
    font-weight: 560;
}

.ccx-method i {
    color: color-mix(in srgb, var(--ccx-accent) 76%, #795a16);
}

.ccx-categories {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.ccx-list {
    display: grid;
    gap: 8px;
    padding: 14px;
}

.ccx-list-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--ccx-primary) 8%, #ebe4d8);
    border-radius: 12px;
    background: rgba(255,255,255,.78);
    padding: 10px;
}

.ccx-list-name {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    color: var(--ccx-text);
    font-size: .84rem;
    font-weight: 430;
}

.ccx-list-name span {
    color: var(--ccx-muted);
    font-size: .74rem;
}

.ccx-empty {
    padding: 26px 14px;
    text-align: center;
    color: var(--ccx-muted);
    font-size: .86rem;
    font-weight: 430;
}

.ccx-form {
    padding: 16px;
}

.ccx-form-title {
    margin: 0 0 12px;
    color: var(--ccx-ink);
    font-size: .96rem;
    line-height: 1.24;
    font-weight: 620;
}

.ccx-denoms {
    display: grid;
    gap: 8px;
    margin-bottom: 16px;
}

.ccx-denom-row {
    display: grid;
    grid-template-columns: 82px minmax(0, 1fr) 88px;
    gap: 9px;
    align-items: center;
}

.ccx-denom-label,
.ccx-subtotal {
    color: var(--ccx-muted);
    font-size: .78rem;
    font-weight: 520;
    font-variant-numeric: tabular-nums;
}

.ccx-subtotal {
    text-align: right;
}

.ccx-input,
.ccx-textarea {
    width: 100%;
    border: 1px solid color-mix(in srgb, var(--ccx-accent) 18%, #ddd5c8);
    border-radius: 12px;
    background: #fff;
    color: var(--ccx-text);
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.ccx-input {
    height: 40px;
    padding: 0 10px;
    text-align: center;
    font-weight: 520;
    font-variant-numeric: tabular-nums;
}

.ccx-textarea {
    min-height: 86px;
    resize: vertical;
    padding: 10px 12px;
    font-weight: 430;
}

.ccx-input:focus,
.ccx-textarea:focus {
    border-color: color-mix(in srgb, var(--ccx-accent) 60%, var(--ccx-line));
    box-shadow: 0 0 0 4px var(--ccx-focus);
    background: #fff;
}

.ccx-form-label {
    display: block;
    margin: 0 0 7px;
    color: #435164;
    font-size: .74rem;
    font-weight: 620;
    text-transform: uppercase;
    letter-spacing: .04em;
}

.ccx-total-box {
    display: grid;
    gap: 9px;
    border-top: 1px solid var(--ccx-line);
    border-bottom: 1px solid var(--ccx-line);
    padding: 14px 0;
    margin-bottom: 16px;
}

.ccx-total-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: var(--ccx-muted);
    font-size: .86rem;
}

.ccx-total-row strong,
.ccx-total-row output {
    color: color-mix(in srgb, var(--ccx-primary) 58%, #4b5563);
    font-size: 1rem;
    font-weight: 560;
    font-variant-numeric: tabular-nums;
}

.ccx-total-row.is-main output {
    font-size: 1.55rem;
}

#diferencia.text-green-600 { color: #15803d; }
#diferencia.text-red-600 { color: #b91c1c; }
#diferencia.text-gray-700 { color: color-mix(in srgb, var(--ccx-primary) 58%, #4b5563); }

.ccx-alert {
    margin-bottom: 16px;
    border: 1px solid #fde68a;
    border-radius: 12px;
    background: #fffbeb;
    color: #92400e;
    padding: 12px;
    font-size: .84rem;
    font-weight: 520;
}

.ccx-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
}

.ccx-btn {
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid var(--ccx-line);
    border-radius: 12px;
    background: #fff;
    color: var(--ccx-ink);
    padding: 0 12px;
    font-size: .84rem;
    font-weight: 620;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.ccx-btn:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ccx-accent) 30%, var(--ccx-line));
    background: color-mix(in srgb, var(--ccx-accent) 7%, #fff);
}

.ccx-btn.is-danger {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.ccx-export {
    min-height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid color-mix(in srgb, var(--ccx-accent) 20%, #ddd5c8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--ccx-accent) 8%, #fff);
    color: color-mix(in srgb, var(--ccx-primary) 58%, #667085);
    padding: 0 10px;
    font-size: .78rem;
    font-weight: 620;
    cursor: pointer;
}

.ccx-export-group {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}

.ccx-export.is-pdf {
    border-color: color-mix(in srgb, #dc2626 24%, #ddd5c8);
    background: color-mix(in srgb, #dc2626 8%, #fff);
    color: color-mix(in srgb, #991b1b 70%, var(--ccx-primary));
}

.ccx-export:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ccx-accent) 36%, #ddd5c8);
}

.ccx-export.is-pdf:hover {
    border-color: color-mix(in srgb, #dc2626 38%, #ddd5c8);
}

.ccx-export:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--ccx-accent) 72%, #fff);
    outline-offset: 2px;
}

.ccx-badge {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 999px;
    padding: 0 8px;
    font-size: .7rem;
    font-weight: 650;
}

.ccx-badge.is-income {
    background: #dcfce7;
    color: #166534;
}

.ccx-badge.is-expense {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 1180px) {
    .ccx-top,
    .ccx-grid {
        grid-template-columns: 1fr;
    }

    .ccx-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .ccx-shell {
        width: min(100% - 20px, 1440px);
        padding-top: 18px;
    }

    .ccx-title {
        font-size: clamp(1.75rem, 9vw, 2.6rem);
    }

    .ccx-summary,
    .ccx-categories,
    .ccx-actions {
        grid-template-columns: 1fr;
    }

    .ccx-denom-row {
        grid-template-columns: 72px minmax(0, 1fr) 76px;
    }

    .ccx-panel-head {
        display: grid;
    }

    .ccx-export-group {
        justify-content: stretch;
    }

    .ccx-export {
        min-height: 38px;
        flex: 1 1 120px;
    }
}
</style>

<style id="caja-corte-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');
.ccx-page {
    --ccx-primary: var(--brand-primary, #1B2746) !important;
    --ccx-secondary: var(--brand-secondary, #0F172A) !important;
    --ccx-accent: var(--brand-accent, #BD9441) !important;
    --ccx-ink: #111827 !important;
    --ccx-text: #171717 !important;
    --ccx-muted: #667085 !important;
    --ccx-line: color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #E7E1D4) !important;
    --ccx-surface: #FFFFFF !important;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--brand-accent, #BD9441) 8%, transparent), transparent 60%),
        linear-gradient(180deg, #FBF8F2, #F6F2EA) !important;
}
.ccx-page .ccx-title { font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif !important; color: var(--ccx-ink) !important; font-weight: 700 !important; font-size: clamp(2rem, 4vw, 2.9rem) !important; }
.ccx-page .ccx-panel-head h2, .ccx-page .ccx-panel-head h3, .ccx-page .ccx-form-title { color: var(--ccx-ink) !important; font-weight: 700 !important; }
.ccx-page .ccx-panel, .ccx-page .ccx-stat { background: #FFFFFF !important; border-color: var(--ccx-line) !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -26px rgba(27,39,70,.3) !important; }
.ccx-page .ccx-stat strong { font-family: 'Cormorant Garamond', Georgia, serif !important; color: var(--ccx-ink) !important; }
.ccx-page .ccx-total-row strong, .ccx-page .ccx-total-row output { color: var(--ccx-ink) !important; }
.ccx-page .ccx-total-row.is-main output { font-family: 'Cormorant Garamond', Georgia, serif !important; }
.ccx-page .ccx-input, .ccx-page .ccx-textarea { background: #FCFAF5 !important; border-color: var(--ccx-line) !important; }
.ccx-page .ccx-input:focus, .ccx-page .ccx-textarea:focus { border-color: var(--brand-accent, #BD9441) !important; box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand-accent, #BD9441) 26%, transparent) !important; }
.ccx-page .ccx-btn.is-danger { background: linear-gradient(135deg, #B4392B, color-mix(in srgb, #B4392B 72%, #000)) !important; border-color: transparent !important; color: #fff !important; }
.ccx-page .ccx-btn.is-danger:hover { color: #fff !important; }
</style>

<main class="ccx-page">
    <div class="ccx-shell">
        <header class="ccx-top">
            <div>
                <span class="ccx-kicker">
                    <i class="fas fa-cash-register"></i>
                    Caja abierta
                </span>
                <h1 class="ccx-title">Corte de caja</h1>
                <p class="ccx-subtitle">
                    Revisa el resumen del turno, cuenta el efectivo y cierra la caja con una vista clara y tranquila.
                </p>
            </div>

            <a href="<?= back_url('caja') ?>" class="ccx-back">
                <i class="fas fa-arrow-left"></i>
                <span>Volver a caja</span>
            </a>
        </header>

        <section class="ccx-summary" aria-label="Resumen de corte">
            <article class="ccx-stat is-start">
                <span>Monto inicial</span>
                <strong><?= corte_money($resumen['monto_inicial'] ?? 0) ?></strong>
                <small><?= date('d/m/Y H:i', strtotime($fechaApertura)) ?> · <?= safe_html($corte['usuario_apertura'] ?? '') ?></small>
            </article>

            <article class="ccx-stat is-income">
                <span>Total ingresos</span>
                <strong><?= corte_money($resumen['ingresos']['total'] ?? 0, '+') ?></strong>
                <small>Entradas registradas en el corte</small>
            </article>

            <article class="ccx-stat is-expense">
                <span>Total gastos</span>
                <strong><?= corte_money($resumen['gastos']['total'] ?? 0, '-') ?></strong>
                <small>Salidas registradas en el corte</small>
            </article>

            <article class="ccx-stat is-cash">
                <span>Efectivo esperado</span>
                <strong id="efectivoEsperado"><?= corte_money($efectivoEsperado) ?></strong>
                <small>Balance general: <?= corte_money($balanceGeneral) ?></small>
            </article>
        </section>

        <div class="ccx-grid">
            <div class="ccx-main">
                <section class="ccx-panel">
                    <div class="ccx-panel-head">
                        <div>
                            <h2>Metodo de pago</h2>
                            <p>Ingresos, gastos y balance por forma de cobro.</p>
                        </div>
                        <span class="ccx-count">3 metodos</span>
                    </div>

                    <div class="ccx-table-wrap">
                        <table class="ccx-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Metodo</th>
                                    <th class="text-center">Ingresos</th>
                                    <th class="text-center">Gastos</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (['efectivo', 'tarjeta', 'transferencia'] as $metodo): ?>
                                    <?php
                                    $ingresos = (float)($resumen['ingresos'][$metodo]['total'] ?? 0);
                                    $gastos = (float)($resumen['gastos'][$metodo]['total'] ?? 0);
                                    $balance = $ingresos - $gastos;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="ccx-method">
                                                <i class="fas fa-<?= safe_html($metodos_pago[$metodo]['icon'] ?? 'dollar-sign') ?>"></i>
                                                <?= ucfirst($metodo) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="ccx-money is-income"><?= corte_money($ingresos, '+') ?></span>
                                            <?php if (($resumen['ingresos'][$metodo]['cantidad'] ?? 0) > 0): ?>
                                                <small class="block text-gray-400">(<?= (int)$resumen['ingresos'][$metodo]['cantidad'] ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="ccx-money is-expense"><?= corte_money($gastos, '-') ?></span>
                                            <?php if (($resumen['gastos'][$metodo]['cantidad'] ?? 0) > 0): ?>
                                                <small class="block text-gray-400">(<?= (int)$resumen['gastos'][$metodo]['cantidad'] ?>)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <span class="ccx-money <?= $balance >= 0 ? 'is-income' : 'is-expense' ?>">
                                                <?= corte_money($balance) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>TOTAL</td>
                                    <td class="text-center">
                                        <span class="ccx-money is-income"><?= corte_money($resumen['ingresos']['total'] ?? 0, '+') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="ccx-money is-expense"><?= corte_money($resumen['gastos']['total'] ?? 0, '-') ?></span>
                                    </td>
                                    <td class="text-right">
                                        <span class="ccx-money is-balance"><?= corte_money($balanceGeneral) ?></span>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <section class="ccx-categories" aria-label="Movimientos por categoria">
                    <article class="ccx-panel">
                        <div class="ccx-panel-head">
                            <div>
                                <h3>Ingresos por categoria</h3>
                                <p>Origen de las entradas del turno.</p>
                            </div>
                        </div>
                        <div class="ccx-list">
                            <?php if (empty($movimientos_categoria['ingresos'])): ?>
                                <p class="ccx-empty">Sin ingresos</p>
                            <?php else: ?>
                                <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                    <div class="ccx-list-row">
                                        <span class="ccx-list-name">
                                            <i class="<?= safe_html($cat['icono'] ?? 'fas fa-tag') ?>" style="color: <?= safe_html($cat['color'] ?? '#94a3b8') ?>"></i>
                                            <?= safe_html($cat['categoria'] ?? '') ?>
                                            <span>(<?= (int)($cat['cantidad'] ?? 0) ?>)</span>
                                        </span>
                                        <span class="ccx-money is-income"><?= corte_money($cat['total'] ?? 0) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="ccx-panel">
                        <div class="ccx-panel-head">
                            <div>
                                <h3>Gastos por categoria</h3>
                                <p>Salida de efectivo y otros metodos.</p>
                            </div>
                        </div>
                        <div class="ccx-list">
                            <?php if (empty($movimientos_categoria['gastos'])): ?>
                                <p class="ccx-empty">Sin gastos</p>
                            <?php else: ?>
                                <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                                    <div class="ccx-list-row">
                                        <span class="ccx-list-name">
                                            <i class="<?= safe_html($cat['icono'] ?? 'fas fa-tag') ?>" style="color: <?= safe_html($cat['color'] ?? '#94a3b8') ?>"></i>
                                            <?= safe_html($cat['categoria'] ?? '') ?>
                                            <span>(<?= (int)($cat['cantidad'] ?? 0) ?>)</span>
                                        </span>
                                        <span class="ccx-money is-expense"><?= corte_money($cat['total'] ?? 0) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                </section>

                <section class="ccx-panel is-movements">
                    <div class="ccx-panel-head">
                        <div>
                            <h2>Detalle de movimientos</h2>
                            <p>Todos los movimientos registrados en este corte.</p>
                        </div>
                        <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                        <div class="ccx-export-group" aria-label="Exportar detalle de movimientos">
                            <button onclick="exportarMovimientos('excel')" class="ccx-export" type="button">
                                <i class="fas fa-file-excel"></i>
                                <span>Excel</span>
                            </button>
                            <button onclick="exportarMovimientos('pdf')" class="ccx-export is-pdf" type="button">
                                <i class="fas fa-file-pdf"></i>
                                <span>PDF</span>
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="ccx-table-wrap">
                        <table class="ccx-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Hora</th>
                                    <th class="text-left">Tipo</th>
                                    <th class="text-left">Descripcion</th>
                                    <th class="text-left">Categoria</th>
                                    <th class="text-left">Metodo</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($movimientos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-400 py-8">No hay movimientos en este corte.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <?php
                                        $tipoMovimiento = $mov['tipo'] ?? 'ingreso';
                                        $metodo = $mov['metodo_pago'] ?? 'efectivo';
                                        $icon = $metodos_pago[$metodo]['icon'] ?? 'dollar-sign';
                                        ?>
                                        <tr>
                                            <td><?= date('H:i', strtotime($mov['created_at'] ?? 'now')) ?></td>
                                            <td>
                                                <span class="ccx-badge <?= $tipoMovimiento === 'ingreso' ? 'is-income' : 'is-expense' ?>">
                                                    <?= ucfirst($tipoMovimiento) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= safe_html($mov['descripcion'] ?? '') ?>
                                                <?php if (!empty($mov['referencia'])): ?>
                                                    <small class="block text-gray-400">Ref: <?= safe_html($mov['referencia']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($mov['categoria_nombre'])): ?>
                                                    <span class="ccx-method">
                                                        <i class="<?= safe_html($mov['categoria_icono'] ?? 'fas fa-tag') ?>" style="color: <?= safe_html($mov['categoria_color'] ?? '#94a3b8') ?>"></i>
                                                        <?= safe_html($mov['categoria_nombre']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="ccx-method">
                                                    <i class="fas fa-<?= safe_html($icon) ?>"></i>
                                                    <?= ucfirst($metodo) ?>
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <span class="ccx-money <?= $tipoMovimiento === 'ingreso' ? 'is-income' : 'is-expense' ?>">
                                                    <?= $tipoMovimiento === 'ingreso' ? '+' : '-' ?>$<?= number_format((float)($mov['monto'] ?? 0), 2) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="ccx-side" aria-label="Arqueo de efectivo">
                <section class="ccx-panel">
                    <div class="ccx-panel-head">
                        <div>
                            <h2>Arqueo de efectivo</h2>
                            <p>Cuenta billetes y monedas antes de cerrar.</p>
                        </div>
                    </div>

                    <form method="POST" action="<?= url('caja/corte/cerrar') ?>" id="formCorte" class="ccx-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="corte_id" value="<?= $corte['id'] ?? '' ?>">

                        <h4 class="ccx-form-title">Denominaciones</h4>
                        <div class="ccx-denoms">
                            <?php foreach ($denominaciones as $denominacion): ?>
                                <div class="ccx-denom-row">
                                    <label class="ccx-denom-label">
                                        $<?= number_format((float)$denominacion, 2) ?>
                                    </label>
                                    <input type="number"
                                           name="denominaciones[<?= $denominacion ?>]"
                                           class="ccx-input denominacion-input"
                                           data-valor="<?= $denominacion ?>"
                                           min="0"
                                           value="0"
                                           placeholder="0">
                                    <span class="ccx-subtotal subtotal" data-denominacion="<?= $denominacion ?>">
                                        $0.00
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="ccx-total-box">
                            <div class="ccx-total-row is-main">
                                <span>Total contado</span>
                                <output id="totalContado">$0.00</output>
                            </div>
                            <div class="ccx-total-row">
                                <span>Efectivo esperado</span>
                                <strong><?= corte_money($efectivoEsperado) ?></strong>
                            </div>
                            <div class="ccx-total-row">
                                <span>Diferencia</span>
                                <output id="diferencia">$0.00</output>
                            </div>
                        </div>

                        <input type="hidden" name="efectivo_contado" id="efectivo_contado" value="0">

                        <div class="mb-4">
                            <label class="ccx-form-label">Observaciones</label>
                            <textarea name="observaciones"
                                      rows="3"
                                      class="ccx-textarea"
                                      placeholder="Comentarios sobre el corte..."></textarea>
                        </div>

                        <div id="alertaDiferencia" class="hidden">
                            <div class="ccx-alert">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span id="mensajeDiferencia"></span>
                            </div>
                        </div>

                        <div class="ccx-actions">
                            <a href="<?= url('caja') ?>" class="ccx-btn">
                                Cancelar
                            </a>
                            <button type="submit" class="ccx-btn is-danger">
                                <i class="fas fa-cut"></i>
                                <span>Cerrar caja</span>
                            </button>
                        </div>
                    </form>
                </section>
            </aside>
        </div>
    </div>
</main>

<script>
const efectivoEsperado = <?= $efectivoEsperado ?>;

function calcularTotales() {
    let totalContado = 0;

    document.querySelectorAll('.denominacion-input').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const valor = parseFloat(input.dataset.valor);
        const subtotal = cantidad * valor;

        const subtotalElement = document.querySelector(`.subtotal[data-denominacion="${valor}"]`);
        subtotalElement.textContent = '$' + subtotal.toFixed(2);

        totalContado += subtotal;
    });

    document.getElementById('totalContado').textContent = '$' + totalContado.toFixed(2);
    document.getElementById('efectivo_contado').value = totalContado.toFixed(2);

    const diferencia = totalContado - efectivoEsperado;
    const diferenciaElement = document.getElementById('diferencia');

    diferenciaElement.textContent = (diferencia >= 0 ? '+' : '') + '$' + diferencia.toFixed(2);

    if (diferencia > 0) {
        diferenciaElement.className = 'text-xl font-bold text-green-600';
    } else if (diferencia < 0) {
        diferenciaElement.className = 'text-xl font-bold text-red-600';
    } else {
        diferenciaElement.className = 'text-xl font-bold text-gray-700';
    }

    const alertaDiferencia = document.getElementById('alertaDiferencia');
    const mensajeDiferencia = document.getElementById('mensajeDiferencia');

    if (Math.abs(diferencia) > 0.01) {
        alertaDiferencia.classList.remove('hidden');
        if (diferencia > 0) {
            mensajeDiferencia.textContent = `Hay un sobrante de $${Math.abs(diferencia).toFixed(2)}`;
        } else {
            mensajeDiferencia.textContent = `Hay un faltante de $${Math.abs(diferencia).toFixed(2)}`;
        }
    } else {
        alertaDiferencia.classList.add('hidden');
    }
}

document.querySelectorAll('.denominacion-input').forEach(input => {
    input.addEventListener('input', calcularTotales);
    input.addEventListener('focus', function() {
        if (this.value === '0') this.value = '';
    });
    input.addEventListener('blur', function() {
        if (this.value === '') this.value = '0';
    });
});

document.getElementById('formCorte').addEventListener('submit', function(e) {
    e.preventDefault();

    calcularTotales();
    const totalContado = parseFloat(document.getElementById('efectivo_contado').value);
    const diferencia = totalContado - efectivoEsperado;

    let mensaje = `
        <div class="text-left">
            <p class="mb-2"><strong>Efectivo esperado:</strong> $${efectivoEsperado.toFixed(2)}</p>
            <p class="mb-2"><strong>Efectivo contado:</strong> $${totalContado.toFixed(2)}</p>
    `;

    if (Math.abs(diferencia) > 0.01) {
        const tipo = diferencia > 0 ? 'sobrante' : 'faltante';
        const color = diferencia > 0 ? 'green' : 'red';
        mensaje += `<p class="mb-2 text-${color}-600 font-bold">
            <strong>Diferencia:</strong> ${diferencia > 0 ? '+' : ''}$${diferencia.toFixed(2)} (${tipo})
        </p>`;
    } else {
        mensaje += `<p class="mb-2 text-green-600"><strong>Cuadre exacto</strong></p>`;
    }

    mensaje += `</div>`;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Confirmar cierre de caja',
            html: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-cut mr-2"></i>Si, cerrar caja',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    } else {
        if (confirm('Confirmar cierre de caja?\n\nEfectivo esperado: $' + efectivoEsperado.toFixed(2) + '\nEfectivo contado: $' + totalContado.toFixed(2))) {
            this.submit();
        }
    }
});

function exportarMovimientos(formato = 'excel') {
    const formatoSeguro = formato === 'pdf' ? 'pdf' : 'excel';
    const exportUrl = '<?= url('caja/exportar?corte_id=' . ($corte['id'] ?? '')) ?>&formato=' + formatoSeguro;
    if (formatoSeguro === 'pdf' && window.MedisoftMobileFiles) {
        window.MedisoftMobileFiles.open(exportUrl, { label: 'PDF de movimientos' });
        return;
    }
    window.location.href = exportUrl;
}

document.addEventListener('DOMContentLoaded', function() {
    const primerInput = document.querySelector('.denominacion-input');
    if (primerInput) {
        primerInput.focus();
    }
});
</script>
