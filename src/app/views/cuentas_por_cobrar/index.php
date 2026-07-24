<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxc_safe')) {
    function cxc_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_money')) {
    function cxc_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxc_estado_meta')) {
    function cxc_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'    => ['Parcial', 'is-parcial', 'fa-circle-half-stroke'],
            'vencida'    => ['Vencida', 'is-vencida', 'fa-triangle-exclamation'],
            'liquidada'  => ['Liquidada', 'is-liquidada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
            'incobrable' => ['Incobrable', 'is-incobrable', 'fa-ban'],
            'excedente'  => ['Excedente', 'is-liquidada', 'fa-circle-plus'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estadoReservacion = (string)($filtros['estado_reservacion'] ?? 'todas');
$estadoSaldo = (string)($filtros['estado_saldo'] ?? 'pendiente');
$vigencia = (string)($filtros['vigencia'] ?? 'vigentes');
$visibles = count($cuentas);
?>

<style>
.cxc-page {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 78%, var(--cx-brand));
    --cx-ivory: #F5F5F7; --cx-ivory-2: #FAFAFC;
    --cx-surface: #FFFFFF; --cx-surface-warm: #F5F5F7;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: color-mix(in srgb, var(--cx-brand) 34%, #647080);
    --cx-text-soft: color-mix(in srgb, var(--cx-brand) 24%, #7B8492);
    --cx-muted: #7A8493; --cx-heading: color-mix(in srgb, var(--cx-brand) 62%, #6B7280);
    --cx-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%; color: var(--cx-text); font-family: var(--cx-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cxc-page .cx-shell { display: grid; gap: 14px; }
.cxc-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxc-page .cx-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent); }
.cxc-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxc-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; }
.cxc-page .cx-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--cx-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.cxc-page .cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.cxc-page .cx-btn:hover { transform: translateY(-1px); }
.cxc-page .cx-btn:active { transform: translateY(0) scale(.98); }
.cxc-page .cx-btn:focus-visible { outline: 3px solid var(--cx-ring); outline-offset: 2px; }
.cxc-page .cx-btn-gold { background: linear-gradient(135deg, var(--cx-gold), color-mix(in srgb, var(--cx-gold) 76%, var(--cx-brand))); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--cx-gold) 58%, transparent); }
.cxc-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxc-page .cx-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cxc-page .cx-summary-item { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxc-page .cx-summary-label { color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.cxc-page .cx-summary-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxc-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxc-page .cx-filter-form { display: grid; grid-template-columns: minmax(220px, 1.15fr) minmax(140px, 180px) minmax(140px, 180px) minmax(140px, 180px) 42px; max-width: 900px; gap: 10px; align-items: center; }
.cxc-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .86rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxc-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxc-page .cx-control::placeholder { color: color-mix(in srgb, var(--cx-muted) 72%, #fff); font-weight: 600; }
.cxc-page select.cx-control { cursor: pointer; }
.cxc-page .cx-search { position: relative; }
.cxc-page .cx-search .cx-control { padding-left: 38px; }
.cxc-page .cx-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--cx-muted); font-size: .9rem; pointer-events: none; }
.cxc-page .cx-filter-form .cx-reset { width: 42px; min-width: 42px; padding: 0; border-radius: 12px; overflow: hidden; font-size: 0; color: var(--cx-muted); }
.cxc-page .cx-filter-form .cx-reset i { margin: 0; font-size: .86rem; }
.cxc-page .cx-filter-form .cx-reset:hover { background: var(--cx-gold-soft); border-color: var(--cx-gold-line); color: var(--cx-gold-ink); }

.cxc-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.cxc-page .cx-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--cx-border); }
.cxc-page .cx-panel-title { font-size: .85rem; font-weight: 700; color: var(--cx-heading); }
.cxc-page .cx-panel-sub { font-size: .75rem; color: var(--cx-muted); }
.cxc-page .cx-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--cx-gold-soft); color: var(--cx-gold-ink); border: 1px solid var(--cx-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.cxc-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxc-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxc-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.cxc-page .cx-table th.is-end, .cxc-page .cx-table td.is-end { text-align: right; }
.cxc-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: middle; }
.cxc-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxc-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxc-page .cx-link { font-weight: 700; color: var(--cx-heading); text-decoration: none; }
.cxc-page .cx-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }
.cxc-page .cx-sub { color: var(--cx-muted); font-size: .72rem; }
.cxc-page .cx-strong { font-weight: 700; color: var(--cx-heading); }
.cxc-page .cx-faint { color: var(--cx-muted); }

.cxc-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.cxc-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, var(--cx-brand)); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxc-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxc-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-page .cx-badge.is-liquidada { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-page .cx-badge.is-incobrable { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-page .cx-badge.is-cancelada, .cxc-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }

.cxc-page .cx-rowact { display: inline-flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
.cxc-page .cx-rowact form { display: inline-flex; margin: 0; }
.cxc-page .cx-act { display: inline-flex; align-items: center; gap: .35rem; min-height: 32px; padding: 0 11px; border-radius: 9px; background: var(--cx-surface-warm); border: 1px solid var(--cx-border); color: var(--cx-muted); font-size: .76rem; font-weight: 700; text-decoration: none; cursor: pointer; transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease; }
.cxc-page .cx-act:hover { transform: translateY(-1px); }
.cxc-page .cx-act-gen { background: var(--cx-gold-soft); color: var(--cx-gold-ink); border-color: var(--cx-gold-line); }
.cxc-page .cx-act-gen:hover { background: color-mix(in srgb, var(--cx-gold) 22%, #fff); }

.cxc-page .cx-empty { text-align: center; padding: 44px 18px; background: var(--cx-ivory-2); border: 1px dashed var(--cx-border); border-radius: 16px; }
.cxc-page .cx-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.3rem; }
.cxc-page .cx-empty h2 { color: var(--cx-heading); font-size: 1.1rem; font-weight: 700; }
.cxc-page .cx-empty p { color: var(--cx-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.cxc-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxc-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxc-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxc-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

.cxc-page [data-cxc-results-region] { transition: opacity .18s ease, filter .18s ease; }
.cxc-page [data-cxc-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (max-width: 859px) {
    .cxc-page .cx-filter-form { grid-template-columns: 1fr 1fr; }
    .cxc-page .cx-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cxc-page .cx-title { font-size: 1.9rem; }
}

/* ════════════════════════════════════════════════════════════════════
   MÓVIL COMPACTO  ·  estética dashboard / habitaciones (≤768px)
   Tabla → tarjetas; se omite lo que satura (ver resumen al usuario).
   ════════════════════════════════════════════════════════════════════ */
.cxc-page .cx-mobile-list { display: none; }

@media (max-width: 768px) {
    .cxc-page { padding: 14px !important; }
    .cxc-page .cx-shell { gap: 12px; }

    /* Header compacto */
    .cxc-page .cx-title-lockup { grid-template-columns: 40px minmax(0, 1fr); column-gap: 11px; align-items: center; }
    .cxc-page .cx-hero-icon { width: 40px; height: 40px; border-radius: 12px; font-size: 1rem; }
    .cxc-page .cx-kicker { display: none; }          /* omitido */
    .cxc-page .cx-title { font-size: 1.5rem; }
    .cxc-page .cx-subtitle { display: none; }        /* omitido: parrafo largo */
    .cxc-page section.flex > .cx-btn { width: 100%; }

    /* Resumen 2x2 compacto */
    .cxc-page .cx-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .cxc-page .cx-summary-item { padding: 10px 12px; border-radius: 12px; }
    .cxc-page .cx-summary-value { font-size: 1.35rem; }

    /* Filtros: search + selects apilados (live-search auto-aplica) */
    .cxc-page .cx-panel.p-3 { padding: 12px !important; }
    .cxc-page .cx-filter-form { grid-template-columns: 1fr; gap: 8px; }
    .cxc-page .cx-control { min-height: 44px; }
    .cxc-page .cx-filter-form .cx-reset { width: 42px; justify-self: end; }

    /* Tabla oculta → tarjetas */
    .cxc-page .cx-table-wrap { display: none; }
    .cxc-page .cx-panel-head { padding: 12px 14px; }
    .cxc-page .cx-panel-sub { display: none; }       /* omitido: descripcion decorativa */
    .cxc-page .cx-mobile-list { display: grid; gap: 10px; padding: 12px; }

    .cxc-page .cx-mcard {
        display: grid;
        gap: 10px;
        border: 1px solid var(--cx-border);
        border-radius: 14px;
        background: var(--cx-surface);
        padding: 13px;
        box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -20px rgba(27,39,70,.22);
    }
    .cxc-page .cx-mcard-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
    .cxc-page .cx-mcard-id { font-family: var(--cx-serif); font-size: 1.02rem; font-weight: 700; }
    .cxc-page .cx-mcard-guest { margin-top: 2px; font-weight: 700; color: var(--cx-text); font-size: .92rem; line-height: 1.2; }
    .cxc-page .cx-mcard-phone { margin-top: 1px; color: var(--cx-muted); font-size: .74rem; }
    .cxc-page .cx-mcard-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
    .cxc-page .cx-mfig { border: 1px solid var(--cx-border); border-radius: 11px; background: var(--cx-surface-warm); padding: 8px 9px; }
    .cxc-page .cx-mfig.is-saldo { border-color: var(--cx-gold-line); background: var(--cx-gold-soft); }
    .cxc-page .cx-mfig-label { display: block; color: var(--cx-muted); font-size: .6rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .cxc-page .cx-mfig-val { display: block; margin-top: 2px; color: var(--cx-text); font-size: .92rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .cxc-page .cx-mfig.is-saldo .cx-mfig-val { color: var(--cx-gold-ink); }
    .cxc-page .cx-mcard-dates { display: inline-flex; align-items: center; gap: 6px; color: var(--cx-muted); font-size: .74rem; }
    .cxc-page .cx-mcard-dates i { color: var(--cx-gold-ink); }
    .cxc-page .cx-mcard-acts { display: flex; flex-wrap: wrap; gap: 6px; }
    .cxc-page .cx-mcard-acts .cx-act,
    .cxc-page .cx-mcard-acts form { flex: 1 1 auto; }
    .cxc-page .cx-mcard-acts .cx-act { min-height: 40px; justify-content: center; }
    .cxc-page .cx-mcard-acts form .cx-act { width: 100%; }
}
</style>

<div class="cxc-page p-4 sm:p-6">
    <div class="cx-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="cx-hero-section flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon ms-glass-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                <div>
                    <p class="cx-kicker">Cobros a hu&eacute;spedes</p>
                    <h1 class="cx-title ms-glass-title">Cuentas por cobrar</h1>
                    <p class="cx-subtitle">Lo que te deben tus hu&eacute;spedes, seg&uacute;n sus reservaciones, pagos y facturas. Aqu&iacute; generas la cuenta cuando queda saldo pendiente.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <a class="cx-btn cx-btn-muted ms-glass-btn" href="<?= url('cuentas-por-cobrar/operativas') ?>"><i class="fas fa-table-list"></i> Cuentas operativas</a>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>Faltan datos base de reservaciones, pagos o facturas. P&iacute;dele al administrador del sistema que la habilite.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-summary">
                <div class="cx-summary-item"><p class="cx-summary-label">Reservaciones</p><p class="cx-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Con saldo</p><p class="cx-summary-value" style="color:var(--cx-warning)"><?= number_format((int)($resumen['pendientes'] ?? 0)) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Ya cobrado</p><p class="cx-summary-value" style="color:var(--cx-success)"><?= cxc_money($resumen['total_cubierto'] ?? 0) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Saldo por cobrar</p><p class="cx-summary-value"><?= cxc_money($resumen['saldo_estimado'] ?? 0) ?></p></div>
            </section>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar') ?>" class="cx-filter-form" data-cxc-live-search-form data-auto-filter-form>
                    <div class="cx-search">
                        <i class="fas fa-search cx-search-icon" data-cxc-search-icon></i>
                        <input class="cx-control" type="search" name="buscar" autocomplete="off" inputmode="search" value="<?= cxc_safe($buscar, '') ?>" placeholder="Buscar por hu&eacute;sped o reservaci&oacute;n" data-cxc-live-search-input>
                    </div>
                    <select class="cx-control" name="estado_reservacion">
                        <option value="todas" <?= $estadoReservacion === 'todas' ? 'selected' : '' ?>>Todas las reservaciones</option>
                        <option value="confirmada" <?= $estadoReservacion === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                        <option value="checked_in" <?= $estadoReservacion === 'checked_in' ? 'selected' : '' ?>>Check-in</option>
                        <option value="checked_out" <?= $estadoReservacion === 'checked_out' ? 'selected' : '' ?>>Check-out</option>
                        <option value="cancelada" <?= $estadoReservacion === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                    <select class="cx-control" name="vigencia">
                        <option value="vigentes" <?= $vigencia === 'vigentes' ? 'selected' : '' ?>>Hoy y futuras</option>
                        <option value="pasadas" <?= $vigencia === 'pasadas' ? 'selected' : '' ?>>Pasadas</option>
                        <option value="todas" <?= $vigencia === 'todas' ? 'selected' : '' ?>>Todas las fechas</option>
                    </select>
                    <select class="cx-control" name="estado_saldo">
                        <option value="todas" <?= $estadoSaldo === 'todas' ? 'selected' : '' ?>>Todos los saldos</option>
                        <option value="pendiente" <?= $estadoSaldo === 'pendiente' ? 'selected' : '' ?>>Con saldo pendiente</option>
                        <option value="liquidada" <?= $estadoSaldo === 'liquidada' ? 'selected' : '' ?>>Ya pagadas</option>
                        <option value="excedente" <?= $estadoSaldo === 'excedente' ? 'selected' : '' ?>>Con excedente</option>
                    </select>
                    <a class="cx-btn cx-btn-muted cx-reset" href="<?= url('cuentas-por-cobrar') ?>"><i class="fas fa-times"></i> Limpiar</a>
                </form>
            </section>

            <div data-cxc-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($cuentas)): ?>
                    <section class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                        <h2>No hay nada por cobrar</h2>
                        <p>No encontramos reservaciones con estos filtros. Cambia la b&uacute;squeda para ver otras.</p>
                    </section>
                <?php else: ?>
                    <section class="cx-panel overflow-hidden">
                        <div class="cx-panel-head">
                            <div>
                                <div class="cx-panel-title">Reservaciones con saldo</div>
                                <div class="cx-panel-sub">Hu&eacute;sped, cu&aacute;nto ya pag&oacute; y cu&aacute;nto falta por cobrar.</div>
                            </div>
                            <span class="cx-count-pill"><i class="fas fa-list"></i> <?= number_format($visibles) ?></span>
                        </div>
                        <div class="overflow-x-auto cx-table-wrap">
                            <table class="cx-table">
                                <thead>
                                    <tr><th>Reservaci&oacute;n</th><th>Hu&eacute;sped</th><th>Fechas</th><th>Estado</th><th class="is-end">Total</th><th class="is-end">Cobrado</th><th class="is-end">Saldo</th><th class="is-end">Acci&oacute;n</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                        <?php
                                        $reservacionId = (int)($cuenta['reservacion_id'] ?? 0);
                                        $reservacionUrl = url('reservaciones/ver/' . $reservacionId);
                                        $facturaId = (int)($cuenta['solicitud_factura_id'] ?? 0);
                                        $cxcCobros = (float)($cuenta['cxc_cobros_total'] ?? 0);
                                        [$eLabel, $eClass, $eIcon] = cxc_estado_meta($cuenta['estado_saldo'] ?? null);
                                        ?>
                                        <tr data-easy-href="<?= cxc_safe($reservacionUrl, '') ?>" role="link" tabindex="0" title="Abrir reservacion #<?= $reservacionId ?>" aria-label="Abrir reservacion #<?= $reservacionId ?>">
                                            <td>
                                                <a class="cx-link" href="<?= url('reservaciones/ver/' . $reservacionId) ?>">#<?= $reservacionId ?></a>
                                            </td>
                                            <td>
                                                <div class="cx-strong"><?= cxc_safe($cuenta['huesped_nombre'] ?? null) ?></div>
                                                <div class="cx-sub"><?= cxc_safe($cuenta['huesped_telefono'] ?? null, 'Sin teléfono') ?></div>
                                            </td>
                                            <td><span class="cx-faint"><?= cxc_safe($cuenta['fecha_entrada'] ?? null) ?> &rarr; <?= cxc_safe($cuenta['fecha_salida'] ?? null) ?></span></td>
                                            <td>
                                                <span class="cx-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                                                <div class="cx-sub mt-1"><?= cxc_safe($cuenta['reservacion_estado'] ?? null) ?></div>
                                            </td>
                                            <td class="is-end"><?= cxc_money($cuenta['precio_total'] ?? 0) ?></td>
                                            <td class="is-end">
                                                <span class="cx-strong" style="color:var(--cx-success)"><?= cxc_money($cuenta['monto_cubierto'] ?? 0) ?></span>
                                                <div class="cx-sub">
                                                    Pagos <?= cxc_money($cuenta['pagos_total'] ?? 0) ?> &middot; Abonos <?= cxc_money($cuenta['abonos_total'] ?? 0) ?>
                                                    <?php if ($cxcCobros > 0.004): ?>
                                                        &middot; Cobros de cuenta <?= cxc_money($cxcCobros) ?>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="is-end cx-strong"><?= cxc_money($cuenta['saldo_estimado'] ?? 0) ?></td>
                                            <td class="is-end">
                                                <div class="cx-rowact">
                                                    <a class="cx-act" href="<?= url('reservaciones/ver/' . $reservacionId) ?>"><i class="fas fa-eye"></i> Reservaci&oacute;n</a>
                                                    <?php if ($facturaId > 0): ?>
                                                        <a class="cx-act" href="<?= url('facturacion/ver/' . $facturaId) ?>"><i class="fas fa-file-invoice"></i> Factura</a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($cuenta['es_elegible_generacion_cxc'])): ?>
                                                        <form method="POST" action="<?= url('cuentas-por-cobrar/generar-desde-reservacion/' . $reservacionId) ?>">
                                                            <?= csrf_field() ?>
                                                            <button class="cx-act cx-act-gen" type="submit" title="<?= cxc_safe($cuenta['motivo_generacion_cxc'] ?? null, 'Generar cuenta por cobrar') ?>"><i class="fas fa-file-circle-plus"></i> Generar cuenta</button>
                                                        </form>
                                                    <?php elseif (!empty($cuenta['cxc_operativa_id'])): ?>
                                                        <a class="cx-act" href="<?= url('cuentas-por-cobrar/operativas/' . (int)$cuenta['cxc_operativa_id']) ?>"><i class="fas fa-table-list"></i> Cuenta #<?= (int)$cuenta['cxc_operativa_id'] ?></a>
                                                    <?php else: ?>
                                                        <span class="cx-act" title="<?= cxc_safe($cuenta['motivo_bloqueo_generacion_cxc'] ?? null, 'No disponible') ?>" style="cursor:default"><i class="fas fa-ban"></i> No disponible</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="cx-mobile-list" aria-label="Cuentas por cobrar (movil)">
                            <?php foreach ($cuentas as $cuenta): ?>
                                <?php
                                $reservacionId = (int)($cuenta['reservacion_id'] ?? 0);
                                $reservacionUrl = url('reservaciones/ver/' . $reservacionId);
                                $facturaId = (int)($cuenta['solicitud_factura_id'] ?? 0);
                                $cxcCobros = (float)($cuenta['cxc_cobros_total'] ?? 0);
                                [$eLabel, $eClass, $eIcon] = cxc_estado_meta($cuenta['estado_saldo'] ?? null);
                                ?>
                                <article class="cx-mcard" data-easy-href="<?= cxc_safe($reservacionUrl, '') ?>" role="link" tabindex="0" title="Abrir reservacion #<?= $reservacionId ?>" aria-label="Abrir reservacion #<?= $reservacionId ?>">
                                    <div class="cx-mcard-top">
                                        <div>
                                            <a class="cx-link cx-mcard-id" href="<?= url('reservaciones/ver/' . $reservacionId) ?>">#<?= $reservacionId ?></a>
                                            <div class="cx-mcard-guest"><?= cxc_safe($cuenta['huesped_nombre'] ?? null) ?></div>
                                            <div class="cx-mcard-phone"><?= cxc_safe($cuenta['huesped_telefono'] ?? null, 'Sin teléfono') ?></div>
                                        </div>
                                        <span class="cx-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                                    </div>

                                    <div class="cx-mcard-figs">
                                        <div class="cx-mfig">
                                            <span class="cx-mfig-label">Total</span>
                                            <span class="cx-mfig-val"><?= cxc_money($cuenta['precio_total'] ?? 0) ?></span>
                                        </div>
                                        <div class="cx-mfig">
                                            <span class="cx-mfig-label">Cobrado</span>
                                            <span class="cx-mfig-val" style="color:var(--cx-success)"><?= cxc_money($cuenta['monto_cubierto'] ?? 0) ?></span>
                                        </div>
                                        <div class="cx-mfig is-saldo">
                                            <span class="cx-mfig-label">Saldo</span>
                                            <span class="cx-mfig-val"><?= cxc_money($cuenta['saldo_estimado'] ?? 0) ?></span>
                                        </div>
                                    </div>

                                        <div class="cx-mcard-dates">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= cxc_safe($cuenta['fecha_entrada'] ?? null) ?> &rarr; <?= cxc_safe($cuenta['fecha_salida'] ?? null) ?>
                                        </div>
                                        <?php if ($cxcCobros > 0.004): ?>
                                            <div class="cx-mcard-dates">
                                                <i class="fas fa-table-list"></i>
                                                Cobros de cuenta <?= cxc_money($cxcCobros) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="cx-mcard-acts">
                                        <a class="cx-act" href="<?= url('reservaciones/ver/' . $reservacionId) ?>"><i class="fas fa-eye"></i> Reservaci&oacute;n</a>
                                        <?php if ($facturaId > 0): ?>
                                            <a class="cx-act" href="<?= url('facturacion/ver/' . $facturaId) ?>"><i class="fas fa-file-invoice"></i> Factura</a>
                                        <?php endif; ?>
                                        <?php if (!empty($cuenta['es_elegible_generacion_cxc'])): ?>
                                            <form method="POST" action="<?= url('cuentas-por-cobrar/generar-desde-reservacion/' . $reservacionId) ?>">
                                                <?= csrf_field() ?>
                                                <button class="cx-act cx-act-gen" type="submit" title="<?= cxc_safe($cuenta['motivo_generacion_cxc'] ?? null, 'Generar cuenta por cobrar') ?>"><i class="fas fa-file-circle-plus"></i> Generar cuenta</button>
                                            </form>
                                        <?php elseif (!empty($cuenta['cxc_operativa_id'])): ?>
                                            <a class="cx-act" href="<?= url('cuentas-por-cobrar/operativas/' . (int)$cuenta['cxc_operativa_id']) ?>"><i class="fas fa-table-list"></i> Cuenta #<?= (int)$cuenta['cxc_operativa_id'] ?></a>
                                        <?php else: ?>
                                            <span class="cx-act" title="<?= cxc_safe($cuenta['motivo_bloqueo_generacion_cxc'] ?? null, 'No disponible') ?>" style="cursor:default"><i class="fas fa-ban"></i> No disponible</span>
                                        <?php endif; ?>
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
    const form = document.querySelector('[data-cxc-live-search-form]');
    const input = document.querySelector('[data-cxc-live-search-input]');
    const searchIcon = document.querySelector('[data-cxc-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null, isComposing = false, lastQuery = input.value.trim(), activeRequest = null;
    const parser = new DOMParser(); const delay = 280;
    const getRegion = () => document.querySelector('[data-cxc-results-region]');

    const setSearching = (s) => {
        getRegion()?.classList.toggle('is-updating', s);
        getRegion()?.setAttribute('aria-busy', s ? 'true' : 'false');
        if (searchIcon) { searchIcon.classList.toggle('fa-search', !s); searchIcon.classList.toggle('fa-circle-notch', s); searchIcon.classList.toggle('fa-spin', s); }
    };
    const buildUrl = (target = null) => {
        const url = target ? new URL(target, window.location.origin) : new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [k, v] of data.entries()) { const n = String(v || '').trim(); if (n && n !== 'todas') url.searchParams.set(k, n); else url.searchParams.delete(k); }
        return url;
    };
    const apply = (doc) => { const inc = doc.querySelector('[data-cxc-results-region]'); const cur = getRegion(); if (inc && cur) cur.replaceWith(inc); };
    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const c = new AbortController(); activeRequest = c; setSearching(true);
        try {
            const r = await fetch(url.toString(), { credentials: 'same-origin', signal: c.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error('fail');
            apply(parser.parseFromString(await r.text(), 'text/html'));
            if (pushState) window.history.replaceState({}, '', url.pathname + url.search);
        } catch (e) { if (e.name !== 'AbortError') { console.error('Error CxC:', e); HTMLFormElement.prototype.submit.call(form); } }
        finally { if (activeRequest === c) { activeRequest = null; setSearching(false); } }
    };
    const live = () => { const cur = input.value.trim(); if (cur === lastQuery) return; lastQuery = cur; fetchResults(buildUrl()); };
    form.addEventListener('submit', (e) => { e.preventDefault(); lastQuery = input.value.trim(); fetchResults(buildUrl()); });
    form.querySelectorAll('select').forEach((s) => s.addEventListener('change', () => { lastQuery = input.value.trim(); fetchResults(buildUrl()); }));
    form.querySelector('.cx-reset')?.addEventListener('click', (e) => { e.preventDefault(); input.value = ''; form.querySelectorAll('select').forEach((s) => { s.selectedIndex = 0; }); lastQuery = ''; fetchResults(new URL(e.currentTarget.href, window.location.origin)); input.focus(); });
    window.addEventListener('popstate', () => {
        const p = new URLSearchParams(window.location.search);
        input.value = p.get('buscar') || '';
        const er = form.querySelector('[name="estado_reservacion"]'); if (er) er.value = p.get('estado_reservacion') || 'todas';
        const es = form.querySelector('[name="estado_saldo"]'); if (es) es.value = p.get('estado_saldo') || 'pendiente';
        const vg = form.querySelector('[name="vigencia"]'); if (vg) vg.value = p.get('vigencia') || 'vigentes';
        lastQuery = input.value.trim(); fetchResults(new URL(window.location.href), { pushState: false });
    });
    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => { isComposing = false; clearTimeout(liveSearchTimer); liveSearchTimer = setTimeout(live, delay); });
    input.addEventListener('input', () => { if (isComposing) return; clearTimeout(liveSearchTimer); liveSearchTimer = setTimeout(live, delay); });
})();
</script>
