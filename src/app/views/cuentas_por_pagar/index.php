<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxp_safe')) {
    function cxp_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_money')) {
    function cxp_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxp_estado_meta')) {
    function cxp_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente' => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'   => ['Parcial',   'is-parcial',   'fa-circle-half-stroke'],
            'vencida'   => ['Vencida',   'is-vencida',   'fa-triangle-exclamation'],
            'pagada'    => ['Pagada',    'is-pagada',    'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$visibles = count($cuentas);
$saldoVencido = (float)($resumen['saldo_vencido'] ?? 0);
?>

<style>
.cxp-page {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 72%, #000);
    --cx-ivory: #F6F2EA;
    --cx-ivory-2: #FBF8F2;
    --cx-surface: #FFFFFF;
    --cx-surface-warm: #FCFAF5;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: #171717;
    --cx-muted: #667085;
    --cx-heading: #111827;
    --cx-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--cx-text);
    font-family: var(--cx-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cx-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cx-ivory-2), var(--cx-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cxp-page .cx-shell { display: grid; gap: 14px; }
.cxp-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxp-page .cx-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent);
}
.cxp-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxp-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; }
.cxp-page .cx-subtitle { max-width: 46rem; margin: 9px 0 0; color: var(--cx-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.cxp-page .cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.cxp-page .cx-btn:hover { transform: translateY(-1px); }
.cxp-page .cx-btn:active { transform: translateY(0) scale(.98); }
.cxp-page .cx-btn:focus-visible { outline: 3px solid var(--cx-ring); outline-offset: 2px; }
.cxp-page .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cx-brand) 60%, transparent); }
.cxp-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxp-page .cx-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cxp-page .cx-summary-item { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxp-page .cx-summary-label { color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.cxp-page .cx-summary-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }
.cxp-page .cx-summary-value.is-danger { color: var(--cx-danger); }

.cxp-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxp-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.cxp-page .cx-hint-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--cx-surface-warm); color: var(--cx-muted); border: 1px solid var(--cx-border); font-size: .74rem; font-weight: 700; }

.cxp-page .cx-filter-form { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(170px, 210px) auto auto; gap: 10px; align-items: center; }
.cxp-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxp-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxp-page select.cx-control { cursor: pointer; }
.cxp-page .cx-search { position: relative; }
.cxp-page .cx-search .cx-control { padding-left: 38px; }
.cxp-page .cx-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--cx-muted); font-size: .9rem; pointer-events: none; }

.cxp-page .cx-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--cx-border); }
.cxp-page .cx-panel-title { font-size: .85rem; font-weight: 700; color: var(--cx-heading); }
.cxp-page .cx-panel-sub { font-size: .75rem; color: var(--cx-muted); }
.cxp-page .cx-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--cx-gold-soft); color: var(--cx-gold-ink); border: 1px solid var(--cx-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.cxp-page .cx-desktop { display: none; }
.cxp-page .cx-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.cxp-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxp-page .cx-table th { padding: 12px 16px; color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.cxp-page .cx-table th.is-end { text-align: right; }
.cxp-page .cx-table td { padding: 13px 16px; vertical-align: middle; }
.cxp-page .cx-table td.is-end { text-align: right; }
.cxp-page .cx-row { border-bottom: 1px solid var(--cx-border); transition: background .16s ease, box-shadow .16s ease; }
.cxp-page .cx-row:last-child { border-bottom: 0; }
.cxp-page .cx-row:hover { background: var(--cx-ivory-2); box-shadow: 0 10px 24px -24px rgba(27,39,70,.48); }
.cxp-page .cx-id { font-family: var(--cx-serif); font-size: 1.15rem; font-weight: 700; color: var(--cx-heading); line-height: 1; }
.cxp-page .cx-id-link { color: inherit; text-decoration: none; }
.cxp-page .cx-id-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }
.cxp-page .cx-sub { color: var(--cx-muted); font-size: .74rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cxp-page .cx-prov { font-weight: 700; color: var(--cx-heading); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cxp-page .cx-cell-strong { font-weight: 700; color: var(--cx-heading); }
.cxp-page .cx-saldo { font-weight: 700; color: var(--cx-heading); }
.cxp-page .cx-saldo.is-danger { color: var(--cx-danger); }

.cxp-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.cxp-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, #000); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxp-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxp-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, #000); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxp-page .cx-badge.is-pagada { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-page .cx-badge.is-cancelada { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxp-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }

.cxp-page .cx-action { display: inline-flex; align-items: center; gap: .4rem; min-height: 34px; padding: 0 13px; border-radius: 10px; background: var(--cx-surface-warm); border: 1px solid var(--cx-border); color: var(--cx-info); font-size: .78rem; font-weight: 700; text-decoration: none; transition: transform .16s ease, background .16s ease; }
.cxp-page .cx-action:hover { transform: translateY(-1px); background: var(--cx-info-bg); }

.cxp-page .cx-mobile { display: grid; gap: 10px; padding: 12px; }
.cxp-page .cx-mobile-card { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); transition: transform .16s ease, border-color .16s ease; }
.cxp-page .cx-mobile-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--cx-gold) 38%, var(--cx-border)); }
.cxp-page .cx-mobile-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.cxp-page .cx-mobile-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 11px; }
.cxp-page .cx-mini-label { font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--cx-muted); }
.cxp-page .cx-mini-value { font-weight: 700; color: var(--cx-heading); font-size: .88rem; }
.cxp-page .cx-mobile-actions { margin-top: 11px; }
.cxp-page .cx-mobile-actions .cx-action { width: 100%; justify-content: center; }

.cxp-page .cx-empty { text-align: center; padding: 44px 18px; background: var(--cx-ivory-2); border: 1px dashed var(--cx-border); border-radius: 16px; }
.cxp-page .cx-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.3rem; }
.cxp-page .cx-empty h2 { color: var(--cx-brand); font-size: 1.1rem; font-weight: 700; }
.cxp-page .cx-empty p { color: var(--cx-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.cxp-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxp-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxp-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxp-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

.cxp-page [data-cxp-results-region] { transition: opacity .18s ease, filter .18s ease; }
.cxp-page [data-cxp-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 768px) {
    .cxp-page .cx-desktop { display: block; }
    .cxp-page .cx-mobile { display: none; }
}
@media (max-width: 767px) {
    .cxp-page .cx-filter-form { grid-template-columns: 1fr; }
    .cxp-page .cx-filter-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .cxp-page .cx-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cxp-page .cx-title { font-size: 1.9rem; }
}
</style>
<style>
.cxp-page {
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 58%, var(--cx-brand));
    --cx-border: color-mix(in srgb, var(--cx-brand) 6%, #E9E1D6);
    --cx-text: color-mix(in srgb, var(--cx-brand) 46%, #707B8C);
    --cx-muted: #8791A2;
    --cx-heading: color-mix(in srgb, var(--cx-brand) 66%, #566172);
    padding: 18px 16px 42px !important;
}

.cxp-page .cx-shell {
    width: 100%;
    max-width: 1120px;
    min-width: 0;
    margin: 0 auto;
}

.cxp-page .cx-hero-section {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr);
    align-items: start !important;
    justify-content: stretch !important;
    gap: 12px !important;
    min-width: 0;
    width: 100%;
    padding: 2px 0 6px;
}

.cxp-page .cx-title-lockup {
    max-width: min(100%, 780px);
}

.cxp-page .cx-title-lockup > div:last-child {
    min-width: 0;
}

.cxp-page .cx-hero-icon {
    color: var(--cx-gold-ink);
    background:
        linear-gradient(145deg, rgba(255,255,255,.88), rgba(251,247,238,.9)),
        radial-gradient(circle at 36% 28%, color-mix(in srgb, var(--cx-gold) 22%, transparent), transparent 58%);
    border: 1px solid color-mix(in srgb, var(--cx-gold) 26%, var(--cx-border));
    box-shadow: 0 16px 30px rgba(15, 23, 42, .08);
}

.cxp-page .cx-kicker,
.cxp-page .cx-title,
.cxp-page .cx-btn,
.cxp-page .cx-summary-label,
.cxp-page .cx-summary-value,
.cxp-page .cx-hint-pill,
.cxp-page .cx-control,
.cxp-page .cx-panel-title,
.cxp-page .cx-count-pill,
.cxp-page .cx-table th,
.cxp-page .cx-id,
.cxp-page .cx-prov,
.cxp-page .cx-cell-strong,
.cxp-page .cx-saldo,
.cxp-page .cx-badge,
.cxp-page .cx-action,
.cxp-page .cx-mini-label,
.cxp-page .cx-mini-value,
.cxp-page .cx-empty h2,
.cxp-page .cx-notice strong {
    font-weight: 650;
}

.cxp-page .cx-title {
    color: var(--cx-heading);
    letter-spacing: 0;
    overflow-wrap: anywhere;
}

.cxp-page .cx-subtitle {
    max-width: 48rem;
    color: var(--cx-muted);
}

.cxp-page .cx-btn {
    min-height: 44px;
    padding: 0 18px;
    color: var(--cx-text);
    white-space: nowrap;
}

.cxp-page .cx-btn-brand {
    color: #fff;
    background: linear-gradient(135deg, color-mix(in srgb, var(--cx-gold) 86%, #fff), color-mix(in srgb, var(--cx-gold) 72%, var(--cx-brand)));
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--cx-gold) 42%, transparent);
}

.cxp-page .cx-btn-brand:hover {
    color: #fff;
    box-shadow: 0 16px 28px -18px color-mix(in srgb, var(--cx-gold) 54%, transparent);
}

.cxp-page .cx-btn-muted {
    color: var(--cx-muted);
    background: rgba(255,255,255,.86);
}

.cxp-page .cx-btn-muted:hover,
.cxp-page .cx-action:hover,
.cxp-page .cx-card-btn:hover {
    border-color: color-mix(in srgb, var(--cx-gold) 28%, #ECE1D1);
    background: color-mix(in srgb, var(--cx-gold) 10%, #FFFFFF);
    color: var(--cx-gold-ink);
}

.cxp-page .cx-summary-item,
.cxp-page .cx-panel,
.cxp-page .cx-mobile-card {
    background: rgba(255,255,255,.86);
    border-color: var(--cx-border);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}

.cxp-page .cx-summary-value,
.cxp-page .cx-panel-title,
.cxp-page .cx-id,
.cxp-page .cx-prov,
.cxp-page .cx-cell-strong,
.cxp-page .cx-saldo,
.cxp-page .cx-mini-value {
    color: var(--cx-heading);
}

.cxp-page .cx-summary-value.is-danger,
.cxp-page .cx-saldo.is-danger {
    color: color-mix(in srgb, var(--cx-danger) 74%, var(--cx-text));
}

.cxp-page .cx-filter-form {
    grid-template-columns: minmax(200px, 1fr) minmax(160px, 210px) auto auto;
}

.cxp-page .cx-control {
    min-height: 44px;
    color: var(--cx-text);
    background: var(--cx-surface-warm);
}

.cxp-page .cx-control::placeholder {
    color: color-mix(in srgb, var(--cx-muted) 82%, #B8C0CB);
    font-weight: 520;
}

.cxp-page .cx-panel-head {
    background: var(--cx-surface-warm);
    border-color: var(--cx-border);
}

.cxp-page .cx-panel-title {
    color: var(--cx-heading);
    letter-spacing: 0;
}

.cxp-page .cx-count-pill {
    color: var(--cx-gold-ink);
    background: color-mix(in srgb, var(--cx-gold) 10%, #FFFFFF);
    border-color: color-mix(in srgb, var(--cx-gold) 28%, #ECE1D1);
}

.cxp-page .cx-table thead {
    background: rgba(251,248,242,.86);
}

.cxp-page .cx-table th,
.cxp-page .cx-sub,
.cxp-page .cx-panel-sub,
.cxp-page .cx-mini-label,
.cxp-page .cx-empty p,
.cxp-page .cx-notice p {
    color: var(--cx-muted);
}

.cxp-page .cx-table td,
.cxp-page .cx-mobile-card {
    color: var(--cx-text);
}

.cxp-page .cx-row:hover {
    background: rgba(251,248,242,.72);
    box-shadow: inset 3px 0 0 color-mix(in srgb, var(--cx-gold) 42%, transparent);
}

.cxp-page .cx-action {
    min-height: 44px;
    color: var(--cx-text);
}

.cxp-page .cx-empty {
    background: var(--cx-ivory-2);
}

.cxp-page .cx-empty h2,
.cxp-page .cx-notice strong {
    color: var(--cx-heading);
}

@media (min-width: 1024px) {
    .cxp-page .cx-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 16px 28px !important;
        padding-bottom: 10px;
    }

    .cxp-page .cx-hero-section > .flex {
        justify-content: flex-end;
        justify-self: end;
        min-width: max-content;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .cxp-page .cx-filter-form {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .cxp-page .cx-search {
        grid-column: 1 / -1;
    }
}

@media (max-width: 767px) {
    .cxp-page {
        padding: 12px 12px 28px !important;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--cx-gold) 10%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%);
    }

    .cxp-page .cx-shell {
        gap: 10px;
    }

    .cxp-page .cx-hero-section {
        padding: 4px 0 8px;
    }

    .cxp-page .cx-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }

    .cxp-page .cx-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }

    .cxp-page .cx-title {
        font-size: clamp(1.9rem, 10vw, 2.35rem);
        line-height: 1;
    }

    .cxp-page .cx-subtitle {
        font-size: .88rem;
    }

    .cxp-page .cx-hero-section > .flex,
    .cxp-page .cx-filter-form {
        grid-template-columns: 1fr;
        width: 100%;
    }

    .cxp-page .cx-btn,
    .cxp-page .cx-filter-submit,
    .cxp-page .cx-reset {
        width: 100%;
    }

    .cxp-page .cx-summary {
        gap: 8px;
    }

    .cxp-page .cx-summary-item {
        padding: 11px 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .cxp-page .cx-btn,
    .cxp-page .cx-row,
    .cxp-page .cx-mobile-card,
    .cxp-page .cx-action,
    .cxp-page [data-cxp-results-region] {
        transition: none;
    }
}
</style>

<div class="cxp-page p-4 sm:p-6">
    <div class="cx-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="cx-hero-section flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <p class="cx-kicker">Pagos a proveedores</p>
                    <h1 class="cx-title">Cuentas por pagar</h1>
                    <p class="cx-subtitle">Lo que le debes a cada proveedor: cu&aacute;nto, cu&aacute;ndo vence y cu&aacute;nto te falta por pagar. Los pagos se registran desde el detalle de cada cuenta.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <div class="flex flex-wrap gap-2">
                    <a class="cx-btn cx-btn-brand" href="<?= url('cuentas-por-pagar/generacion-preview') ?>" title="Generar cuentas desde compras recibidas">
                        <i class="fas fa-file-circle-plus"></i>
                        Generar desde compras
                    </a>
                    <a class="cx-btn cx-btn-muted" href="<?= url('cuentas-por-pagar/simulador-caja') ?>" title="Simular pagos con Caja">
                        <i class="fas fa-cash-register"></i>
                        Simulador de pagos
                    </a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para empezar a llevar tus cuentas por pagar.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-summary">
                <div class="cx-summary-item">
                    <p class="cx-summary-label">Cuentas</p>
                    <p class="cx-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p>
                </div>
                <div class="cx-summary-item">
                    <p class="cx-summary-label">Pendientes</p>
                    <p class="cx-summary-value"><?= number_format((int)($resumen['pendientes'] ?? 0)) ?></p>
                </div>
                <div class="cx-summary-item">
                    <p class="cx-summary-label">Saldo por pagar</p>
                    <p class="cx-summary-value"><?= cxp_money($resumen['saldo_total'] ?? 0) ?></p>
                </div>
                <div class="cx-summary-item">
                    <p class="cx-summary-label">Vencido</p>
                    <p class="cx-summary-value <?= $saldoVencido > 0 ? 'is-danger' : '' ?>"><?= cxp_money($saldoVencido) ?></p>
                </div>
            </section>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-pagar') ?>" class="cx-filter-form" data-cxp-live-search-form data-auto-filter-form>
                    <div class="cx-search">
                        <i class="fas fa-search cx-search-icon" data-cxp-search-icon></i>
                        <input class="cx-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                               value="<?= cxp_safe($buscar, '') ?>"
                               placeholder="Buscar por proveedor, folio o descripci&oacute;n"
                               data-cxp-live-search-input>
                    </div>
                    <select class="cx-control" name="estado" title="Filtrar por estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                    <button class="cx-btn cx-btn-brand cx-filter-submit" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="cx-btn cx-btn-muted cx-reset" href="<?= url('cuentas-por-pagar') ?>" title="Limpiar filtros">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </form>
            </section>

            <div data-cxp-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($cuentas)): ?>
                    <section class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h2>A&uacute;n no hay cuentas por pagar</h2>
                        <p>No encontramos cuentas con estos filtros. Se generan desde tus compras recibidas.</p>
                        <a class="cx-btn cx-btn-brand mt-4" href="<?= url('cuentas-por-pagar/generacion-preview') ?>" style="display:inline-flex">
                            <i class="fas fa-file-circle-plus"></i>
                            Generar desde compras
                        </a>
                    </section>
                <?php else: ?>
                    <section class="cx-panel overflow-hidden">
                        <div class="cx-panel-head">
                            <div>
                                <div class="cx-panel-title">Lista de cuentas por pagar</div>
                                <div class="cx-panel-sub">Proveedor, vencimiento y saldo de cada cuenta.</div>
                            </div>
                            <span class="cx-count-pill">
                                <i class="fas fa-list"></i>
                                <?= number_format($visibles) ?> <?= $visibles === 1 ? 'cuenta' : 'cuentas' ?>
                            </span>
                        </div>

                        <div class="cx-desktop">
                            <table class="cx-table">
                                <colgroup>
                                    <col style="width: 16%;">
                                    <col style="width: 24%;">
                                    <col style="width: 16%;">
                                    <col style="width: 13%;">
                                    <col style="width: 13%;">
                                    <col style="width: 18%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Cuenta</th>
                                        <th>Proveedor</th>
                                        <th>Vence</th>
                                        <th>Estado</th>
                                        <th class="is-end">Total</th>
                                        <th class="is-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                        <?php
                                        [$estadoLabel, $estadoClass, $estadoIcon] = cxp_estado_meta($cuenta['estado'] ?? null);
                                        $cuentaId = (int)($cuenta['id'] ?? 0);
                                        $cuentaUrl = url('cuentas-por-pagar/' . $cuentaId);
                                        $esVencida = strtolower(trim((string)($cuenta['estado'] ?? ''))) === 'vencida';
                                        ?>
                                        <tr class="cx-row" data-easy-href="<?= cxp_safe($cuentaUrl, '') ?>" role="link" tabindex="0" title="Abrir cuenta #<?= $cuentaId ?>" aria-label="Abrir cuenta por pagar #<?= $cuentaId ?>">
                                            <td>
                                                <a class="cx-id cx-id-link" href="<?= $cuentaUrl ?>">#<?= $cuentaId ?></a>
                                                <div class="cx-sub"><?= cxp_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                            </td>
                                            <td><div class="cx-prov"><?= cxp_safe($cuenta['proveedor_nombre'] ?? null) ?></div></td>
                                            <td><?= cxp_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin fecha') ?></td>
                                            <td>
                                                <span class="cx-badge <?= $estadoClass ?>">
                                                    <i class="fas <?= $estadoIcon ?>"></i>
                                                    <?= $estadoLabel ?>
                                                </span>
                                            </td>
                                            <td class="is-end cx-cell-strong"><?= cxp_money($cuenta['total'] ?? 0) ?></td>
                                            <td class="is-end">
                                                <span class="cx-saldo <?= $esVencida ? 'is-danger' : '' ?>"><?= cxp_money($cuenta['saldo'] ?? 0) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="cx-mobile">
                            <?php foreach ($cuentas as $cuenta): ?>
                                <?php
                                [$estadoLabel, $estadoClass, $estadoIcon] = cxp_estado_meta($cuenta['estado'] ?? null);
                                $cuentaId = (int)($cuenta['id'] ?? 0);
                                $cuentaUrl = url('cuentas-por-pagar/' . $cuentaId);
                                $esVencida = strtolower(trim((string)($cuenta['estado'] ?? ''))) === 'vencida';
                                ?>
                                <article class="cx-mobile-card" data-easy-href="<?= cxp_safe($cuentaUrl, '') ?>" role="link" tabindex="0" title="Abrir cuenta #<?= $cuentaId ?>" aria-label="Abrir cuenta por pagar #<?= $cuentaId ?>">
                                    <div class="cx-mobile-top">
                                        <div class="min-w-0">
                                            <a class="cx-id cx-id-link" href="<?= $cuentaUrl ?>">#<?= $cuentaId ?></a>
                                            <div class="cx-sub"><?= cxp_safe($cuenta['proveedor_nombre'] ?? null) ?></div>
                                        </div>
                                        <span class="cx-badge <?= $estadoClass ?>">
                                            <i class="fas <?= $estadoIcon ?>"></i>
                                            <?= $estadoLabel ?>
                                        </span>
                                    </div>
                                    <div class="cx-mobile-meta">
                                        <div>
                                            <div class="cx-mini-label">Vence</div>
                                            <div class="cx-mini-value"><?= cxp_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin fecha') ?></div>
                                        </div>
                                        <div>
                                            <div class="cx-mini-label">Folio</div>
                                            <div class="cx-mini-value"><?= cxp_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </div>
                                        <div>
                                            <div class="cx-mini-label">Total</div>
                                            <div class="cx-mini-value"><?= cxp_money($cuenta['total'] ?? 0) ?></div>
                                        </div>
                                        <div>
                                            <div class="cx-mini-label">Saldo</div>
                                            <div class="cx-mini-value" style="<?= $esVencida ? 'color:var(--cx-danger)' : '' ?>"><?= cxp_money($cuenta['saldo'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="cx-mobile-actions">
                                        <a class="cx-action" href="<?= $cuentaUrl ?>">
                                            <i class="fas fa-eye"></i>
                                            Ver detalle
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-cxp-live-search-form]');
    const input = document.querySelector('[data-cxp-live-search-input]');
    const searchIcon = document.querySelector('[data-cxp-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-cxp-results-region]');

    const setSearching = (isSearching) => {
        getResultsRegion()?.classList.toggle('is-updating', isSearching);
        getResultsRegion()?.setAttribute('aria-busy', isSearching ? 'true' : 'false');
        if (searchIcon) {
            searchIcon.classList.toggle('fa-search', !isSearching);
            searchIcon.classList.toggle('fa-circle-notch', isSearching);
            searchIcon.classList.toggle('fa-spin', isSearching);
        }
    };

    const buildSearchUrl = (targetUrl = null) => {
        const url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [key, value] of data.entries()) {
            const normalized = String(value || '').trim();
            if (normalized) {
                url.searchParams.set(key, normalized);
            } else {
                url.searchParams.delete(key);
            }
        }
        return url;
    };

    const updateFromDocument = (doc) => {
        const incoming = doc.querySelector('[data-cxp-results-region]');
        const current = getResultsRegion();
        if (incoming && current) {
            current.replaceWith(incoming);
        }
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);
        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('No se pudo cargar la busqueda');
            const html = await response.text();
            const doc = parser.parseFromString(html, 'text/html');
            updateFromDocument(doc);
            if (pushState) {
                window.history.replaceState({}, '', url.pathname + url.search);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de cuentas por pagar:', error);
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
                setSearching(false);
            }
        }
    };

    const submitLiveSearch = () => {
        const current = input.value.trim();
        if (current === lastQuery) return;
        lastQuery = current;
        fetchResults(buildSearchUrl());
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        lastQuery = input.value.trim();
        fetchResults(buildSearchUrl());
    });

    form.querySelector('[name="estado"]')?.addEventListener('change', () => {
        lastQuery = input.value.trim();
        fetchResults(buildSearchUrl());
    });

    form.querySelector('.cx-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = 'todos';
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = params.get('estado') || 'todos';
        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });

    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => {
        isComposing = false;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });
    input.addEventListener('input', () => {
        if (isComposing) return;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });
})();
</script>
