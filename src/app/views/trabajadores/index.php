<?php
$trabajadores = $trabajadores ?? [];
$resumen = $resumen ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0, 'baja' => 0];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('trab_safe')) {
    function trab_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_money')) {
    function trab_money($value)
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return '$' . number_format((float)$value, 2);
    }
}

if (!function_exists('trab_inicial')) {
    function trab_inicial($value)
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars(strtoupper(mb_substr($text !== '' ? $text : 'T', 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wk_estado_meta')) {
    function wk_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'activo'   => ['Activo', 'is-activo', 'fa-circle-check'],
            'inactivo' => ['Inactivo', 'is-inactivo', 'fa-circle-pause'],
            'baja'     => ['Baja', 'is-baja', 'fa-user-slash'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
$visibles = count($trabajadores);
?>

<style>
.workers-page {
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 72%, #000);
    --wk-ivory: #F6F2EA; --wk-ivory-2: #FBF8F2;
    --wk-surface: #FFFFFF; --wk-surface-warm: #FCFAF5;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --wk-text: #171717; --wk-muted: #667085; --wk-heading: #111827;
    --wk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-success: #1E9E63; --wk-success-bg: #E7F4EC;
    --wk-warning: #C2841C; --wk-warning-bg: #FAF0DC;
    --wk-danger: #B4392B; --wk-danger-bg: #F8EAE5;
    --wk-info: #2F77E0; --wk-info-bg: #E6EFFC;
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--wk-ivory-2), var(--wk-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.workers-page .wk-shell { display: grid; gap: 14px; }
.workers-page .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.workers-page .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.workers-page .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.workers-page .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 700; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; }
.workers-page .wk-subtitle { max-width: 46rem; margin: 9px 0 0; color: var(--wk-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.workers-page .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 15px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.workers-page .wk-btn:hover { transform: translateY(-1px); }
.workers-page .wk-btn:active { transform: translateY(0) scale(.98); }
.workers-page .wk-btn:focus-visible { outline: 3px solid var(--wk-ring); outline-offset: 2px; }
.workers-page .wk-btn-gold { background: linear-gradient(135deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--wk-gold) 58%, transparent); }
.workers-page .wk-btn-brand { background: linear-gradient(135deg, var(--wk-brand), var(--wk-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--wk-brand) 60%, transparent); }
.workers-page .wk-btn-muted { background: var(--wk-surface); border-color: var(--wk-border); color: var(--wk-muted); }

.workers-page .wk-navrow { display: flex; flex-wrap: wrap; gap: 8px; }
.workers-page .wk-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.workers-page .wk-summary-item { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.workers-page .wk-summary-label { color: var(--wk-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.workers-page .wk-summary-value { margin-top: 2px; font-family: var(--wk-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--wk-heading); }
.workers-page .wk-summary-value.is-active { color: var(--wk-success); }

.workers-page .wk-panel { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.workers-page .wk-filter-form { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(160px, 200px) auto auto; gap: 10px; align-items: center; }
.workers-page .wk-control { width: 100%; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--wk-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.workers-page .wk-control:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; }
.workers-page select.wk-control { cursor: pointer; }
.workers-page .wk-search { position: relative; }
.workers-page .wk-search .wk-control { padding-left: 38px; }
.workers-page .wk-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--wk-muted); font-size: .9rem; pointer-events: none; }

.workers-page .wk-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--wk-border); }
.workers-page .wk-panel-title { font-size: .85rem; font-weight: 700; color: var(--wk-heading); }
.workers-page .wk-panel-sub { font-size: .75rem; color: var(--wk-muted); }
.workers-page .wk-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--wk-gold-soft); color: var(--wk-gold-ink); border: 1px solid var(--wk-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.workers-page .wk-desktop { display: none; }
.workers-page .wk-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.workers-page .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.workers-page .wk-table th { padding: 12px 16px; color: var(--wk-muted); font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.workers-page .wk-table th.is-end { text-align: right; }
.workers-page .wk-table td { padding: 13px 16px; vertical-align: middle; }
.workers-page .wk-table td.is-end { text-align: right; }
.workers-page .wk-row { border-bottom: 1px solid var(--wk-border); transition: background .16s ease, box-shadow .16s ease; }
.workers-page .wk-row:last-child { border-bottom: 0; }
.workers-page .wk-row:hover { background: var(--wk-ivory-2); box-shadow: 0 10px 24px -24px rgba(27,39,70,.48); }
.workers-page .wk-id-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
.workers-page .wk-avatar { width: 38px; height: 38px; border-radius: 12px; display: grid; place-items: center; flex-shrink: 0; font-weight: 700; font-size: .9rem;
    background: var(--wk-avatar-bg, #EEF2FF); color: var(--wk-avatar-fg, #3730A3); border: 1px solid var(--wk-avatar-border, #C7D2FE); }
.workers-page .wk-name { font-weight: 700; color: var(--wk-heading); line-height: 1.25; text-decoration: none; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
.workers-page .wk-name:hover { text-decoration: underline; text-decoration-color: var(--wk-gold); text-underline-offset: 3px; }
.workers-page .wk-sub { color: var(--wk-muted); font-size: .74rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.workers-page .wk-line { color: #334155; font-size: .82rem; }
.workers-page .wk-cell-strong { font-weight: 700; color: var(--wk-heading); }
.workers-page .wk-muted-text { color: var(--wk-muted); font-size: .8rem; }

.workers-page .wk-row:nth-child(6n+1) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+1) .wk-avatar { --wk-avatar-bg:#EEF2FF; --wk-avatar-fg:#3730A3; --wk-avatar-border:#C7D2FE; }
.workers-page .wk-row:nth-child(6n+2) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+2) .wk-avatar { --wk-avatar-bg:#ECFDF5; --wk-avatar-fg:#047857; --wk-avatar-border:#A7F3D0; }
.workers-page .wk-row:nth-child(6n+3) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+3) .wk-avatar { --wk-avatar-bg:#FFF7ED; --wk-avatar-fg:#C2410C; --wk-avatar-border:#FED7AA; }
.workers-page .wk-row:nth-child(6n+4) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+4) .wk-avatar { --wk-avatar-bg:#FDF2F8; --wk-avatar-fg:#BE185D; --wk-avatar-border:#FBCFE8; }
.workers-page .wk-row:nth-child(6n+5) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+5) .wk-avatar { --wk-avatar-bg:#F0FDFA; --wk-avatar-fg:#0F766E; --wk-avatar-border:#99F6E4; }
.workers-page .wk-row:nth-child(6n+6) .wk-avatar, .workers-page .wk-mcard:nth-child(6n+6) .wk-avatar { --wk-avatar-bg:#F8FAFC; --wk-avatar-fg:#475569; --wk-avatar-border:#CBD5E1; }

.workers-page .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.workers-page .wk-badge.is-activo { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.workers-page .wk-badge.is-inactivo { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.workers-page .wk-badge.is-baja { color: color-mix(in srgb, var(--wk-danger) 82%, #000); background: var(--wk-danger-bg); border-color: color-mix(in srgb, var(--wk-danger) 26%, #fff); }
.workers-page .wk-badge.is-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }

.workers-page .wk-actions { display: flex; justify-content: flex-end; gap: 7px; }
.workers-page .wk-actions form { display: inline-flex; margin: 0; }
.workers-page .wk-action { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 10px; background: var(--wk-surface-warm); border: 1px solid var(--wk-border); color: var(--wk-muted); cursor: pointer; text-decoration: none; transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease; }
.workers-page .wk-action:hover { transform: translateY(-1px); }
.workers-page .wk-action-view { color: var(--wk-info); } .workers-page .wk-action-view:hover { background: var(--wk-info-bg); }
.workers-page .wk-action-edit { color: var(--wk-gold-ink); } .workers-page .wk-action-edit:hover { background: var(--wk-gold-soft); }
.workers-page .wk-action-off { color: var(--wk-danger); } .workers-page .wk-action-off:hover { background: var(--wk-danger-bg); }
.workers-page .wk-action-on { color: var(--wk-success); } .workers-page .wk-action-on:hover { background: var(--wk-success-bg); }
.workers-page .wk-action.is-confirming { background: linear-gradient(135deg, var(--wk-warning), color-mix(in srgb, var(--wk-warning) 72%, #000)); color: #fff; border-color: transparent; }

.worker-action-toast { position: fixed; right: 22px; bottom: 22px; z-index: 15000; max-width: min(390px, calc(100vw - 32px));
    border: 1px solid var(--wk-gold-line, #E4D4B0); border-radius: 14px; background: var(--wk-gold-soft, #FBF3DE); color: var(--wk-gold-ink, #6b521f);
    padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 600; line-height: 1.42;
    opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
.worker-action-toast.is-visible { opacity: 1; transform: translateY(0); }

.workers-page .wk-mobile { display: grid; gap: 10px; padding: 12px; }
.workers-page .wk-mcard { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); transition: transform .16s ease, border-color .16s ease; }
.workers-page .wk-mcard:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--wk-gold) 38%, var(--wk-border)); }
.workers-page .wk-mtop { display: flex; align-items: center; gap: 11px; }
.workers-page .wk-mtop .min-w-0 { min-width: 0; flex: 1; }
.workers-page .wk-mmeta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 11px; }
.workers-page .wk-mlabel { font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--wk-muted); }
.workers-page .wk-mvalue { font-weight: 700; color: var(--wk-heading); font-size: .84rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.workers-page .wk-mactions { display: flex; gap: 7px; margin-top: 11px; }
.workers-page .wk-mactions form { display: flex; flex: 1; }
.workers-page .wk-cardbtn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 36px; width: 100%; border-radius: 10px; background: var(--wk-surface-warm); border: 1px solid var(--wk-border); color: var(--wk-text); font-size: .76rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: background .16s ease, color .16s ease, border-color .16s ease; }
.workers-page .wk-cardbtn:hover { border-color: var(--wk-gold-line); color: var(--wk-gold-ink); background: var(--wk-gold-soft); }
.workers-page .wk-cardbtn.is-off { color: var(--wk-danger); }
.workers-page .wk-cardbtn.is-on { color: var(--wk-success); }
.workers-page .wk-cardbtn.is-confirming { background: linear-gradient(135deg, var(--wk-warning), color-mix(in srgb, var(--wk-warning) 72%, #000)); color: #fff; border-color: transparent; }

.workers-page .wk-empty { text-align: center; padding: 44px 18px; background: var(--wk-ivory-2); border: 1px dashed var(--wk-border); border-radius: 16px; }
.workers-page .wk-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.3rem; }
.workers-page .wk-empty h2 { color: var(--wk-brand); font-size: 1.1rem; font-weight: 700; }
.workers-page .wk-empty p { color: var(--wk-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.workers-page .wk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--wk-gold-soft); border: 1px solid var(--wk-gold-line); border-radius: 16px; }
.workers-page .wk-notice i { color: var(--wk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.workers-page .wk-notice strong { color: var(--wk-heading); display: block; margin-bottom: 2px; }
.workers-page .wk-notice p { color: var(--wk-muted); font-size: .88rem; margin: 0; }

.workers-page [data-wk-results-region] { transition: opacity .18s ease, filter .18s ease; }
.workers-page [data-wk-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 768px) {
    .workers-page .wk-desktop { display: block; }
    .workers-page .wk-mobile { display: none; }
}
@media (max-width: 767px) {
    .workers-page .wk-filter-form { grid-template-columns: 1fr; }
    .workers-page .wk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .workers-page .wk-title { font-size: 1.9rem; }
}
</style>

<div class="workers-page p-4 sm:p-6">
    <div class="wk-shell">
        <section class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3">
            <div class="wk-title-lockup">
                <div class="wk-hero-icon"><i class="fas fa-users-gear"></i></div>
                <div>
                    <p class="wk-kicker">Personal del hotel</p>
                    <h1 class="wk-title">Personal</h1>
                    <p class="wk-subtitle">Las personas que trabajan en el hotel: su rol, contacto y estado. Desde aqu&iacute; entras a su ficha, n&oacute;mina y pagos.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <a class="wk-btn wk-btn-gold" href="<?= url('trabajadores/crear') ?>"><i class="fas fa-plus"></i> Nuevo trabajador</a>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="wk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para gestionar a tu personal.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="wk-navrow">
                <a class="wk-btn wk-btn-muted" href="<?= url('trabajadores/reporte') ?>"><i class="fas fa-chart-pie"></i> Reporte</a>
                <a class="wk-btn wk-btn-muted" href="<?= url('trabajadores/nomina/periodos') ?>"><i class="fas fa-calendar-check"></i> Per&iacute;odos</a>
                <a class="wk-btn wk-btn-muted" href="<?= url('trabajadores/nomina/preview') ?>"><i class="fas fa-clipboard-list"></i> Pre-n&oacute;mina</a>
                <a class="wk-btn wk-btn-muted" href="<?= url('trabajadores/pagos-caja/simulador') ?>"><i class="fas fa-cash-register"></i> Simulador de pagos</a>
                <a class="wk-btn wk-btn-muted" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-file-invoice-dollar"></i> Pagos en Caja</a>
            </section>

            <section class="wk-summary">
                <div class="wk-summary-item"><p class="wk-summary-label">Total</p><p class="wk-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="wk-summary-item"><p class="wk-summary-label">Activos</p><p class="wk-summary-value is-active"><?= number_format((int)($resumen['activos'] ?? 0)) ?></p></div>
                <div class="wk-summary-item"><p class="wk-summary-label">Inactivos</p><p class="wk-summary-value"><?= number_format((int)($resumen['inactivos'] ?? 0)) ?></p></div>
                <div class="wk-summary-item"><p class="wk-summary-label">De baja</p><p class="wk-summary-value"><?= number_format((int)($resumen['baja'] ?? 0)) ?></p></div>
            </section>

            <section class="wk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('trabajadores') ?>" class="wk-filter-form" data-wk-live-search-form data-auto-filter-form>
                    <div class="wk-search">
                        <i class="fas fa-search wk-search-icon" data-wk-search-icon></i>
                        <input class="wk-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                               value="<?= trab_safe($buscar, '') ?>"
                               placeholder="Buscar por nombre, rol, tel&eacute;fono o correo"
                               data-wk-live-search-input>
                    </div>
                    <select class="wk-control" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>De baja</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="wk-btn wk-btn-brand" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a class="wk-btn wk-btn-muted wk-reset" href="<?= url('trabajadores') ?>"><i class="fas fa-times"></i> Limpiar</a>
                </form>
            </section>

            <div data-wk-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($trabajadores)): ?>
                    <section class="wk-empty">
                        <div class="wk-empty-icon"><i class="fas fa-id-card"></i></div>
                        <h2>A&uacute;n no hay personal</h2>
                        <p>No encontramos trabajadores con estos filtros. Da de alta al primero del hotel.</p>
                        <a class="wk-btn wk-btn-gold mt-4" href="<?= url('trabajadores/crear') ?>" style="display:inline-flex"><i class="fas fa-plus"></i> Nuevo trabajador</a>
                    </section>
                <?php else: ?>
                    <section class="wk-panel overflow-hidden">
                        <div class="wk-panel-head">
                            <div>
                                <div class="wk-panel-title">Lista de personal</div>
                                <div class="wk-panel-sub">Rol, contacto y estado de cada persona.</div>
                            </div>
                            <span class="wk-count-pill"><i class="fas fa-list"></i> <?= number_format($visibles) ?> <?= $visibles === 1 ? 'persona' : 'personas' ?></span>
                        </div>

                        <div class="wk-desktop">
                            <table class="wk-table">
                                <colgroup><col style="width: 26%;"><col style="width: 16%;"><col style="width: 22%;"><col style="width: 12%;"><col style="width: 12%;"><col style="width: 12%;"></colgroup>
                                <thead>
                                    <tr><th>Trabajador</th><th>Rol</th><th>Contacto</th><th>Estado</th><th class="is-end">Salario base</th><th class="is-end">Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trabajadores as $trabajador): ?>
                                        <?php
                                        $tId = (int)($trabajador['id'] ?? 0);
                                        $tUrl = url('trabajadores/' . $tId);
                                        $tEstado = (string)($trabajador['estado'] ?? '');
                                        [$eLabel, $eClass, $eIcon] = wk_estado_meta($tEstado);
                                        $tTel = trim((string)($trabajador['telefono'] ?? '')) !== '';
                                        $tMail = trim((string)($trabajador['email'] ?? '')) !== '';
                                        ?>
                                        <tr class="wk-row">
                                            <td>
                                                <div class="wk-id-cell">
                                                    <div class="wk-avatar"><?= trab_inicial($trabajador['nombre_completo'] ?? '') ?></div>
                                                    <div class="min-w-0">
                                                        <a class="wk-name" href="<?= $tUrl ?>"><?= trab_safe($trabajador['nombre_completo'] ?? null) ?></a>
                                                        <div class="wk-sub"><?= trab_safe($trabajador['identificacion'] ?? null, 'Sin identificaci&oacute;n') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="wk-line"><?= trab_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></span></td>
                                            <td>
                                                <?php if ($tTel): ?><div class="wk-line"><i class="fas fa-phone" style="color:var(--wk-muted);width:14px"></i> <?= trab_safe($trabajador['telefono'] ?? null) ?></div><?php endif; ?>
                                                <?php if ($tMail): ?><div class="wk-sub"><i class="fas fa-envelope" style="width:14px"></i> <?= trab_safe($trabajador['email'] ?? null) ?></div><?php endif; ?>
                                                <?php if (!$tTel && !$tMail): ?><span class="wk-muted-text">Sin contacto</span><?php endif; ?>
                                            </td>
                                            <td><span class="wk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                            <td class="is-end cell-strong"><span class="wk-cell-strong"><?= trab_money($trabajador['salario_base'] ?? null) ?></span></td>
                                            <td class="is-end">
                                                <div class="wk-actions">
                                                    <a class="wk-action wk-action-view" href="<?= $tUrl ?>" title="Ver" aria-label="Ver trabajador"><i class="fas fa-eye"></i></a>
                                                    <a class="wk-action wk-action-edit" href="<?= url('trabajadores/' . $tId . '/editar') ?>" title="Editar" aria-label="Editar trabajador"><i class="fas fa-pen"></i></a>
                                                    <?php if ($tEstado === 'baja'): ?>
                                                        <form method="POST" action="<?= url('trabajadores/' . $tId . '/reactivar') ?>">
                                                            <?= csrf_field() ?>
                                                            <button class="wk-action wk-action-on" type="submit" title="Reactivar" aria-label="Reactivar trabajador"><i class="fas fa-rotate-left"></i></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" action="<?= url('trabajadores/' . $tId . '/baja-logica') ?>" data-worker-confirm="1" data-confirm-label="Confirmar baja" data-confirm-message="La baja conserva el registro del trabajador; podras reactivarlo despues.">
                                                            <?= csrf_field() ?>
                                                            <button class="wk-action wk-action-off" type="submit" title="Dar de baja" aria-label="Dar de baja trabajador"><i class="fas fa-user-slash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="wk-mobile">
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <?php
                                $tId = (int)($trabajador['id'] ?? 0);
                                $tUrl = url('trabajadores/' . $tId);
                                $tEstado = (string)($trabajador['estado'] ?? '');
                                [$eLabel, $eClass, $eIcon] = wk_estado_meta($tEstado);
                                ?>
                                <article class="wk-mcard">
                                    <div class="wk-mtop">
                                        <div class="wk-avatar"><?= trab_inicial($trabajador['nombre_completo'] ?? '') ?></div>
                                        <div class="min-w-0">
                                            <a class="wk-name" href="<?= $tUrl ?>"><?= trab_safe($trabajador['nombre_completo'] ?? null) ?></a>
                                            <div class="wk-sub"><?= trab_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                        </div>
                                        <span class="wk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                                    </div>
                                    <div class="wk-mmeta">
                                        <div><div class="wk-mlabel">Tel&eacute;fono</div><div class="wk-mvalue"><?= trab_safe($trabajador['telefono'] ?? null) ?></div></div>
                                        <div><div class="wk-mlabel">Salario base</div><div class="wk-mvalue"><?= trab_money($trabajador['salario_base'] ?? null) ?></div></div>
                                    </div>
                                    <div class="wk-mactions">
                                        <a class="wk-cardbtn" href="<?= $tUrl ?>"><i class="fas fa-eye"></i> Ver</a>
                                        <a class="wk-cardbtn" href="<?= url('trabajadores/' . $tId . '/editar') ?>"><i class="fas fa-pen"></i> Editar</a>
                                        <?php if ($tEstado === 'baja'): ?>
                                            <form method="POST" action="<?= url('trabajadores/' . $tId . '/reactivar') ?>">
                                                <?= csrf_field() ?>
                                                <button class="wk-cardbtn is-on" type="submit"><i class="fas fa-rotate-left"></i> Reactivar</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= url('trabajadores/' . $tId . '/baja-logica') ?>" data-worker-confirm="1" data-confirm-label="Confirmar baja" data-confirm-message="La baja conserva el registro del trabajador; podras reactivarlo despues.">
                                                <?= csrf_field() ?>
                                                <button class="wk-cardbtn is-off" type="submit"><i class="fas fa-user-slash"></i> Baja</button>
                                            </form>
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
(function() {
    function showWorkerToast(message, duration = 7000) {
        let toast = document.getElementById('workerActionToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'workerActionToast';
            toast.className = 'worker-action-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        window.clearTimeout(toast._hideTimer);
        toast.textContent = message;
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        toast._hideTimer = window.setTimeout(() => toast.classList.remove('is-visible'), duration);
    }

    function resetWorkerConfirm(form) {
        if (!form) return;
        delete form.dataset.confirmedAction;
        window.clearTimeout(form._confirmTimer);
        const button = form.querySelector('button[type="submit"]');
        if (button && button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
        button?.classList.remove('is-confirming');
    }

    document.addEventListener('submit', function(event) {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || form.dataset.workerConfirm !== '1') return;
        if (form.dataset.confirmedAction === '1') return;

        event.preventDefault();
        event.stopPropagation();

        document.querySelectorAll('form[data-worker-confirm="1"]').forEach(otherForm => {
            if (otherForm !== form) resetWorkerConfirm(otherForm);
        });

        form.dataset.confirmedAction = '1';
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.dataset.originalHtml = button.innerHTML;
            button.classList.add('is-confirming');
            button.innerHTML = `<i class="fas fa-check"></i> ${form.dataset.confirmLabel || 'Confirmar'}`;
        }

        showWorkerToast(`${form.dataset.confirmMessage || 'Confirma esta accion.'} Presiona el boton otra vez para continuar.`);
        form._confirmTimer = window.setTimeout(() => resetWorkerConfirm(form), 7000);
    }, true);
})();

(() => {
    const form = document.querySelector('[data-wk-live-search-form]');
    const input = document.querySelector('[data-wk-live-search-input]');
    const searchIcon = document.querySelector('[data-wk-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-wk-results-region]');

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
            if (normalized) url.searchParams.set(key, normalized); else url.searchParams.delete(key);
        }
        return url;
    };

    const updateFromDocument = (doc) => {
        const incoming = doc.querySelector('[data-wk-results-region]');
        const current = getResultsRegion();
        if (incoming && current) current.replaceWith(incoming);
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);
        try {
            const response = await fetch(url.toString(), { credentials: 'same-origin', signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('No se pudo cargar la busqueda');
            const html = await response.text();
            updateFromDocument(parser.parseFromString(html, 'text/html'));
            if (pushState) window.history.replaceState({}, '', url.pathname + url.search);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de personal:', error);
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            if (activeRequest === controller) { activeRequest = null; setSearching(false); }
        }
    };

    const submitLiveSearch = () => {
        const current = input.value.trim();
        if (current === lastQuery) return;
        lastQuery = current;
        fetchResults(buildSearchUrl());
    };

    form.addEventListener('submit', (event) => { event.preventDefault(); lastQuery = input.value.trim(); fetchResults(buildSearchUrl()); });
    form.querySelector('[name="estado"]')?.addEventListener('change', () => { lastQuery = input.value.trim(); fetchResults(buildSearchUrl()); });
    form.querySelector('.wk-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = 'activos';
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = params.get('estado') || 'activos';
        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });
    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => { isComposing = false; window.clearTimeout(liveSearchTimer); liveSearchTimer = window.setTimeout(submitLiveSearch, delay); });
    input.addEventListener('input', () => { if (isComposing) return; window.clearTimeout(liveSearchTimer); liveSearchTimer = window.setTimeout(submitLiveSearch, delay); });
})();
</script>
