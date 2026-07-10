<?php
$trabajadores = $trabajadores ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('trab_cash_safe')) {
    function trab_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_cash_money')) {
    function trab_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_cash_date')) {
    function trab_cash_date($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return $fallback;
        }

        return htmlspecialchars(substr($text, 0, 10), ENT_QUOTES, 'UTF-8');
    }
}

$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$periodoInicio = (string)($filtros['periodo_inicio'] ?? '');
$periodoFin = (string)($filtros['periodo_fin'] ?? '');
$metodoPago = (string)($filtros['metodo_pago'] ?? 'efectivo');
$monto = (string)($filtros['monto'] ?? '');
$referencia = (string)($filtros['referencia'] ?? '');
?>

<style>
.worker-cash-page {
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 58%, var(--wk-brand));
    --wk-ivory: #F6F2EA; --wk-ivory-2: #FBF8F2;
    --wk-surface: #FFFFFF; --wk-surface-warm: #FCFAF5;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --wk-text: color-mix(in srgb, var(--wk-brand) 46%, #707B8C);
    --wk-muted: #8791A2;
    --wk-heading: color-mix(in srgb, var(--wk-brand) 66%, #566172);
    --wk-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-success: #1E9E63; --wk-success-bg: #E7F4EC;
    --wk-warning: #C2841C; --wk-warning-bg: #FAF0DC;
    --wk-danger: #B4392B; --wk-danger-bg: #F8EAE5;
    --wk-info: #2F77E0; --wk-info-bg: #E6EFFC;
    --wk-muted-2: color-mix(in srgb, var(--wk-brand) 34%, #8590A1);
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans); font-weight: 450;
    background: radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%), linear-gradient(180deg, var(--wk-ivory-2), var(--wk-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.worker-cash-page .wk-shell { display: grid; gap: 14px; }
.worker-cash-page .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.worker-cash-page .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.worker-cash-page .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.worker-cash-page .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 650; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.worker-cash-page .wk-subtitle { max-width: 52rem; margin: 8px 0 0; color: var(--wk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.worker-cash-page .wk-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
/* Neutraliza el .grid{min-height:200px} global de performance-optimization.css */
.worker-cash-page .grid { min-height: 0; }

/* Toggle segmentado Historial | Simular pago (mismo patrón que el historial y la ficha) */
.worker-cash-page .wk-tabs {
    display: flex; gap: 4px; padding: 5px; width: fit-content; max-width: 100%;
    background: color-mix(in srgb, var(--wk-brand) 5%, var(--wk-ivory-2, #FBF8F2));
    border: 1px solid var(--wk-border); border-radius: 15px;
}
.worker-cash-page .wk-tab {
    position: relative; display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid transparent; border-radius: 11px;
    background: transparent; color: var(--wk-muted);
    min-height: 40px; padding: 0 16px 2px; font-size: .85rem; font-weight: 650; cursor: pointer;
    font-family: var(--wk-sans); line-height: 1; white-space: nowrap; text-decoration: none;
    transition: color .16s ease, background .16s ease, box-shadow .16s ease;
}
.worker-cash-page .wk-tab i { font-size: .8rem; color: color-mix(in srgb, var(--wk-muted) 80%, #fff); transition: color .16s ease; }
.worker-cash-page .wk-tab:hover { color: var(--wk-heading); background: rgba(255,255,255,.65); }
.worker-cash-page .wk-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--wk-ring); }
.worker-cash-page .wk-tab.is-active {
    background: var(--wk-surface); color: var(--wk-heading); border-color: var(--wk-border);
    box-shadow: 0 1px 2px rgba(27,39,70,.05), 0 6px 14px -8px color-mix(in srgb, var(--wk-brand) 38%, transparent);
}
.worker-cash-page .wk-tab.is-active i { color: var(--wk-gold-ink); }
.worker-cash-page .wk-tab.is-active::after {
    content: ""; position: absolute; left: 16px; right: 16px; bottom: 5px; height: 2px; border-radius: 999px;
    background: linear-gradient(90deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 40%, #fff));
}
@media (max-width: 768px) {
    .worker-cash-page .wk-tabs { width: 100%; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .worker-cash-page .wk-tabs::-webkit-scrollbar { display: none; }
    .worker-cash-page .wk-tab { flex: 1 0 auto; justify-content: center; }
}
.worker-cash-page .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 650; font-size: .85rem; text-decoration: none; cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.worker-cash-page .wk-btn:hover { transform: translateY(-1px); }
.worker-cash-page .wk-btn-brand { position: relative; overflow: hidden; background: linear-gradient(135deg, var(--wk-brand), var(--wk-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--wk-brand) 60%, transparent); }
.worker-cash-page .wk-btn-brand::after {
    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.4), transparent);
    transform: translateX(-170%) skewX(-18deg);
}
.worker-cash-page .wk-btn-brand:hover::after { transition: transform .7s ease; transform: translateX(330%) skewX(-18deg); }
.worker-cash-page .wk-btn-muted { background: rgba(255,255,255,.86); border-color: var(--wk-border); color: var(--wk-muted); }
.worker-cash-page .wk-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--wk-surface-warm); color: var(--wk-muted); border: 1px solid var(--wk-border); font-size: .74rem; font-weight: 650; }

.worker-cash-page .wk-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.worker-cash-page .wk-stat { position: relative; background: rgba(255,255,255,.88); border: 1px solid var(--wk-border); border-radius: 14px; padding: 13px 14px 13px 18px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -20px rgba(27,39,70,.18); animation: wkRise .5s cubic-bezier(.22,1,.36,1) backwards; }
.worker-cash-page .wk-stat:nth-child(1) { animation-delay: .05s; }
.worker-cash-page .wk-stat:nth-child(2) { animation-delay: .11s; }
.worker-cash-page .wk-stat:nth-child(3) { animation-delay: .17s; }
.worker-cash-page .wk-stat:nth-child(4) { animation-delay: .23s; }
.worker-cash-page .wk-stat::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--wk-border); }
.worker-cash-page .wk-stat.is-ok::before { background: color-mix(in srgb, var(--wk-success) 55%, #fff); }
.worker-cash-page .wk-stat.is-warn::before { background: color-mix(in srgb, var(--wk-warning) 55%, #fff); }
.worker-cash-page .wk-stat.is-gold::before { background: var(--wk-gold-line); }
.worker-cash-page .wk-stat.is-ok .wk-stat-value { color: var(--wk-success); }
@keyframes wkRise { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }
.worker-cash-page .wk-stat-label { color: var(--wk-muted); font-size: .66rem; font-weight: 650; letter-spacing: .04em; text-transform: uppercase; }
.worker-cash-page .wk-stat-value { margin-top: 3px; font-family: var(--wk-serif); font-size: 1.48rem; font-weight: 650; line-height: 1; color: var(--wk-heading); }

.worker-cash-page .wk-panel { background: rgba(255,255,255,.88); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); }
.worker-cash-page .wk-meta-label { font-size: .66rem; color: var(--wk-muted); font-weight: 650; text-transform: uppercase; letter-spacing: .05em; }
.worker-cash-page .wk-meta-value { margin-top: 3px; font-weight: 620; color: var(--wk-heading); }
.worker-cash-page .wk-sub { color: var(--wk-muted-2); font-size: .72rem; }
.worker-cash-page .wk-strong { font-weight: 620; color: var(--wk-heading); }

.worker-cash-page .wk-control { width: 100%; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--wk-text); font-weight: 560; font-size: .85rem; transition: border-color .16s ease, box-shadow .16s ease, background .16s ease; }
.worker-cash-page .wk-control::placeholder { color: color-mix(in srgb, var(--wk-muted) 78%, #B8C0CB); font-weight: 520; }
.worker-cash-page .wk-control:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; }
.worker-cash-page select.wk-control { cursor: pointer; }
.worker-cash-page .wk-filter-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; align-items: end; max-width: 1080px; }
.worker-cash-page .wk-filter-field { display: flex; flex-direction: column; gap: 5px; min-width: 0; }
.worker-cash-page .wk-filter-label { color: var(--wk-muted); font-size: .64rem; font-weight: 650; letter-spacing: .06em; line-height: 1; text-transform: uppercase; }

.worker-cash-page .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.worker-cash-page .wk-badge-ok { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.worker-cash-page .wk-badge-blocked { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.worker-cash-page .wk-badge-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }

.worker-cash-page .wk-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.worker-cash-page .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.worker-cash-page .wk-table th { padding: 12px 13px; color: var(--wk-muted); font-size: .64rem; font-weight: 650; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.worker-cash-page .wk-table th.is-end, .worker-cash-page .wk-table td.is-end { text-align: right; }
.worker-cash-page .wk-table td { padding: 12px 13px; border-bottom: 1px solid var(--wk-border); vertical-align: top; }
.worker-cash-page .wk-table tbody tr:last-child td { border-bottom: 0; }
.worker-cash-page .wk-table tbody tr:hover { background: color-mix(in srgb, var(--wk-ivory-2) 72%, #fff); }
.worker-cash-page .wk-link { font-weight: 650; color: var(--wk-heading); text-decoration: none; }
.worker-cash-page .wk-link:hover { text-decoration: underline; text-decoration-color: var(--wk-gold); text-underline-offset: 3px; }
.worker-cash-page .wk-act { display: inline-flex; align-items: center; gap: .35rem; min-height: 32px; padding: 0 12px; border-radius: 9px; background: var(--wk-surface-warm); border: 1px solid var(--wk-border); color: var(--wk-info); font-size: .76rem; font-weight: 700; text-decoration: none; }
.worker-cash-page .wk-act:hover { background: var(--wk-info-bg); }

.worker-cash-page .wk-empty { text-align: center; padding: 40px 18px; }
.worker-cash-page .wk-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.25rem; }
.worker-cash-page .wk-empty h2 { color: var(--wk-heading); font-size: 1.05rem; font-weight: 650; }
.worker-cash-page .wk-empty p { color: var(--wk-muted); font-size: .88rem; margin-top: 6px; }
.worker-cash-page .wk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--wk-gold-soft); border: 1px solid var(--wk-gold-line); border-radius: 16px; }
.worker-cash-page .wk-notice i { color: var(--wk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.worker-cash-page .wk-notice strong { color: var(--wk-heading); display: block; margin-bottom: 2px; font-weight: 650; }
.worker-cash-page .wk-notice p { color: var(--wk-muted); font-size: .88rem; margin: 0; }

@media (min-width: 1100px) { .worker-cash-page .wk-filter-form { grid-template-columns: 88px minmax(220px,1.15fr) repeat(2, minmax(132px,.65fr)) minmax(132px,.65fr) minmax(118px,.6fr) minmax(150px,.75fr) auto; } }
@media (max-width: 980px) { .worker-cash-page .wk-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 640px) {
    .worker-cash-page .wk-stats,
    .worker-cash-page .wk-filter-form { grid-template-columns: 1fr; }
    .worker-cash-page .wk-btn { width: 100%; }
    .worker-cash-page .wk-toolbar { justify-content: flex-start; }
}
@media (prefers-reduced-motion: reduce) {
    .worker-cash-page .wk-btn-brand::after { display: none; }
    .worker-cash-page .wk-stat { animation: none !important; }
    .worker-cash-page * { transition-duration: .01ms !important; }
}
</style>

<div class="worker-cash-page p-4 sm:p-6">
    <div class="wk-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="wk-title-lockup">
                <div class="wk-hero-icon"><i class="fas fa-cash-register"></i></div>
                <div>
                    <p class="wk-kicker">Personal del hotel</p>
                    <h1 class="wk-title">Simulador de pagos</h1>
                    <p class="wk-subtitle">A qu&eacute; trabajadores podr&iacute;as pagarles ahora con Caja, seg&uacute;n su saldo y el corte abierto. Solo consulta: no registra pagos ni cambia saldos.</p>
                </div>
            </div>
            <div class="wk-toolbar lg:justify-end" style="justify-content: flex-start;">
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="wk-btn wk-btn-muted ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Volver</a>
                <a class="wk-btn wk-btn-muted" href="<?= url('caja') ?>"><i class="fas fa-cash-register"></i> Abrir Caja</a>
                <span class="wk-pill"><i class="fas fa-eye"></i> Solo consulta</span>
            </div>
        </section>

        <?php $subnav_section = 'personal'; $subnav_active = 'pagos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

        <nav class="wk-tabs" aria-label="Vistas de pagos">
            <a class="wk-tab" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-file-invoice-dollar"></i> Historial</a>
            <span class="wk-tab is-active" aria-current="page"><i class="fas fa-cash-register"></i> Simular pago</span>
        </nav>

        <?php if (!$tablaDisponible): ?>
            <section class="wk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>Faltan datos de personal, pagos laborales o de Caja para poder evaluar los pagos.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="wk-stats">
                <div class="wk-stat"><p class="wk-stat-label">Trabajadores</p><p class="wk-stat-value"><?= (int)($resumen['total'] ?? 0) ?></p></div>
                <div class="wk-stat is-ok"><p class="wk-stat-label">Se les puede pagar</p><p class="wk-stat-value"><?= (int)($resumen['elegibles'] ?? 0) ?></p></div>
                <div class="wk-stat is-gold"><p class="wk-stat-label">Monto simulado</p><p class="wk-stat-value"><?= trab_cash_money($resumen['monto_simulado_total'] ?? 0) ?></p></div>
                <div class="wk-stat <?= !empty($corte) ? 'is-ok' : 'is-warn' ?>"><p class="wk-stat-label">Corte abierto</p><p class="wk-stat-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></p></div>
            </section>

            <div class="wk-panel p-5">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div><div class="wk-meta-label">Corte</div><div class="wk-meta-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div></div>
                    <div><div class="wk-meta-label">Caja</div><div class="wk-meta-value"><?= trab_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div><div class="wk-sub"><?= trab_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicaci&oacute;n') ?></div></div>
                    <div><div class="wk-meta-label">Apertura</div><div class="wk-meta-value"><?= trab_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div></div>
                    <div>
                        <div class="wk-meta-label">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="wk-badge wk-badge-ok mt-1"><i class="fas fa-circle-check"></i> Abierto</span>
                        <?php else: ?>
                            <span class="wk-badge wk-badge-blocked mt-1"><i class="fas fa-triangle-exclamation"></i> Bloquea pagos</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <section class="wk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('trabajadores/pagos-caja/simulador') ?>" class="wk-filter-form" data-auto-filter-form>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">ID trabajador</span>
                        <input class="wk-control" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="Todos">
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">Buscar</span>
                        <input class="wk-control" type="search" name="buscar" value="<?= trab_cash_safe($buscar, '') ?>" placeholder="Trabajador, identificaci&oacute;n o rol">
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">Periodo desde</span>
                        <input class="wk-control" type="date" name="periodo_inicio" value="<?= trab_cash_safe($periodoInicio, '') ?>">
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">Periodo hasta</span>
                        <input class="wk-control" type="date" name="periodo_fin" value="<?= trab_cash_safe($periodoFin, '') ?>">
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">M&eacute;todo</span>
                        <select class="wk-control" name="metodo_pago">
                            <option value="efectivo" <?= $metodoPago === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                            <option value="tarjeta" <?= $metodoPago === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                            <option value="transferencia" <?= $metodoPago === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                        </select>
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">Monto</span>
                        <input class="wk-control" type="number" data-money-format="true" min="0" step="0.01" name="monto" value="<?= trab_cash_safe($monto, '') ?>" placeholder="Sugerido">
                    </label>
                    <label class="wk-filter-field">
                        <span class="wk-filter-label">Referencia</span>
                        <input class="wk-control" type="text" name="referencia" value="<?= trab_cash_safe($referencia, '') ?>" placeholder="Opcional">
                    </label>
                    <button class="wk-btn wk-btn-brand" type="submit"><i class="fas fa-filter"></i> Evaluar</button>
                </form>
            </section>

            <div class="wk-panel overflow-hidden">
                <?php if (empty($trabajadores)): ?>
                    <div class="wk-empty">
                        <div class="wk-empty-icon"><i class="fas fa-user-clock"></i></div>
                        <h2>No hay trabajadores para evaluar</h2>
                        <p>Ajusta los filtros o vuelve al listado de personal.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead>
                                <tr><th>Trabajador</th><th>Periodo y m&eacute;todo</th><th class="is-end">Saldo estimado</th><th class="is-end">Monto simulado</th><th>Pagos en Caja</th><th>&iquest;Se puede pagar?</th><th class="is-end">Ficha</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadores as $trabajador): ?>
                                    <?php $trabajadorUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0)); ?>
                                    <tr>
                                        <td>
                                            <a class="wk-link" href="<?= $trabajadorUrl ?>">#<?= (int)($trabajador['id'] ?? 0) ?> <?= trab_cash_safe($trabajador['nombre_completo'] ?? null, 'Sin nombre') ?></a>
                                            <div class="wk-sub"><?= trab_cash_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?> &middot; <?= trab_cash_safe($trabajador['estado'] ?? null, 'Sin estado') ?></div>
                                            <div class="wk-sub">Ref: <?= trab_cash_safe($trabajador['referencia_evaluada'] ?? null, '-') ?></div>
                                        </td>
                                        <td>
                                            <div class="wk-strong" style="text-transform:capitalize"><?= trab_cash_safe($trabajador['metodo_pago_simulado'] ?? null, 'efectivo') ?></div>
                                            <div class="wk-sub"><?= trab_cash_date($trabajador['periodo_inicio_simulado'] ?? null) ?> a <?= trab_cash_date($trabajador['periodo_fin_simulado'] ?? null) ?></div>
                                        </td>
                                        <td class="is-end">
                                            <div class="wk-strong"><?= trab_cash_money($trabajador['saldo_estimado'] ?? 0) ?></div>
                                            <div class="wk-sub">+<?= trab_cash_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_cash_money((float)($trabajador['conceptos_en_contra'] ?? 0) + (float)($trabajador['anticipos_saldo'] ?? 0) + (float)($trabajador['prestamos_saldo'] ?? 0)) ?></div>
                                        </td>
                                        <td class="is-end">
                                            <div class="wk-strong"><?= trab_cash_money($trabajador['monto_simulado'] ?? 0) ?></div>
                                            <div class="wk-sub">M&aacute;x: <?= trab_cash_money($trabajador['monto_maximo_sugerido'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <div class="wk-strong"><?= (int)($trabajador['pagos_caja_count'] ?? 0) ?> registros</div>
                                            <div class="wk-sub">Pagado: <?= trab_cash_money($trabajador['pagos_caja_total'] ?? 0) ?></div>
                                            <div class="wk-sub">&Uacute;ltimo: <?= trab_cash_safe($trabajador['ultimo_pago_caja'] ?? null, '-') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($trabajador['es_elegible_caja'])): ?>
                                                <span class="wk-badge wk-badge-ok"><i class="fas fa-circle-check"></i> S&iacute;, se puede</span>
                                                <p class="wk-sub mt-2"><?= trab_cash_safe($trabajador['motivo_elegibilidad_caja'] ?? null, '') ?></p>
                                            <?php else: ?>
                                                <span class="wk-badge wk-badge-blocked"><i class="fas fa-ban"></i> Todav&iacute;a no</span>
                                                <p class="wk-sub mt-2"><?= trab_cash_safe($trabajador['motivo_bloqueo_caja'] ?? null, 'Sin diagn&oacute;stico') ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="is-end"><a class="wk-act" href="<?= $trabajadorUrl ?>"><i class="fas fa-eye"></i> Ver</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
