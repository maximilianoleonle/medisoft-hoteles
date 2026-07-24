<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxc_op_safe')) {
    function cxc_op_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_op_money')) {
    function cxc_op_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxc_op_estado_meta')) {
    function cxc_op_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'    => ['Parcial', 'is-parcial', 'fa-circle-half-stroke'],
            'vencida'    => ['Vencida', 'is-vencida', 'fa-triangle-exclamation'],
            'liquidada'  => ['Liquidada', 'is-liquidada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
            'incobrable' => ['Incobrable', 'is-incobrable', 'fa-ban'],
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
.cxc-op-page {
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

.cxc-op-page .cx-shell { display: grid; gap: 14px; }
.cxc-op-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxc-op-page .cx-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent); }
.cxc-op-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxc-op-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; }
.cxc-op-page .cx-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--cx-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.cxc-op-page .cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.cxc-op-page .cx-btn:hover { transform: translateY(-1px); }
.cxc-op-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxc-op-page .cx-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cxc-op-page .cx-summary-item { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxc-op-page .cx-summary-label { color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.cxc-op-page .cx-summary-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }
.cxc-op-page .cx-summary-value.is-danger { color: var(--cx-danger); }

.cxc-op-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxc-op-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.cxc-op-page .cx-filter-form { display: grid; grid-template-columns: minmax(240px, 1fr) minmax(150px, 180px) 42px; max-width: 650px; gap: 10px; align-items: center; }
.cxc-op-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .86rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxc-op-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxc-op-page .cx-control::placeholder { color: color-mix(in srgb, var(--cx-muted) 72%, #fff); font-weight: 600; }
.cxc-op-page select.cx-control { cursor: pointer; }
.cxc-op-page .cx-search { position: relative; }
.cxc-op-page .cx-search .cx-control { padding-left: 38px; }
.cxc-op-page .cx-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--cx-muted); font-size: .9rem; pointer-events: none; }
.cxc-op-page .cx-filter-form .cx-reset { width: 42px; min-width: 42px; padding: 0; border-radius: 12px; overflow: hidden; font-size: 0; color: var(--cx-muted); }
.cxc-op-page .cx-filter-form .cx-reset i { margin: 0; font-size: .86rem; }
.cxc-op-page .cx-filter-form .cx-reset:hover { background: var(--cx-gold-soft); border-color: var(--cx-gold-line); color: var(--cx-gold-ink); }

.cxc-op-page .cx-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--cx-border); }
.cxc-op-page .cx-panel-title { font-size: .85rem; font-weight: 700; color: var(--cx-heading); }
.cxc-op-page .cx-panel-sub { font-size: .75rem; color: var(--cx-muted); }
.cxc-op-page .cx-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--cx-gold-soft); color: var(--cx-gold-ink); border: 1px solid var(--cx-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.cxc-op-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxc-op-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxc-op-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.cxc-op-page .cx-table th.is-end, .cxc-op-page .cx-table td.is-end { text-align: right; }
.cxc-op-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: middle; }
.cxc-op-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxc-op-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxc-op-page .cx-link { font-weight: 700; color: var(--cx-heading); text-decoration: none; }
.cxc-op-page .cx-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }
.cxc-op-page .cx-sub { color: var(--cx-muted); font-size: .72rem; }
.cxc-op-page .cx-strong { font-weight: 700; color: var(--cx-heading); }
.cxc-op-page .cx-saldo.is-danger { color: var(--cx-danger); }
.cxc-op-page .cx-act { display: inline-flex; align-items: center; gap: .35rem; min-height: 32px; padding: 0 12px; border-radius: 9px; background: var(--cx-surface-warm); border: 1px solid var(--cx-border); color: var(--cx-info); font-size: .76rem; font-weight: 700; text-decoration: none; }
.cxc-op-page .cx-act:hover { background: var(--cx-info-bg); }

.cxc-op-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.cxc-op-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, var(--cx-brand)); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxc-op-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxc-op-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-op-page .cx-badge.is-liquidada { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-op-page .cx-badge.is-incobrable { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-op-page .cx-badge.is-cancelada, .cxc-op-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }

.cxc-op-page .cx-empty { text-align: center; padding: 44px 18px; background: var(--cx-ivory-2); border: 1px dashed var(--cx-border); border-radius: 16px; }
.cxc-op-page .cx-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.3rem; }
.cxc-op-page .cx-empty h2 { color: var(--cx-heading); font-size: 1.1rem; font-weight: 700; }
.cxc-op-page .cx-empty p { color: var(--cx-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.cxc-op-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxc-op-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxc-op-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxc-op-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

.cxc-op-page [data-cxc-results-region] { transition: opacity .18s ease, filter .18s ease; }
.cxc-op-page [data-cxc-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (max-width: 767px) {
    .cxc-op-page .cx-filter-form { grid-template-columns: 1fr; }
    .cxc-op-page .cx-filter-form .cx-reset { justify-self: end; }
    .cxc-op-page .cx-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cxc-op-page .cx-title { font-size: 1.9rem; }
}
</style>

<div class="cxc-op-page p-4 sm:p-6">
    <div class="cx-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                <div>
                    <p class="cx-kicker">Cobros a hu&eacute;spedes</p>
                    <h1 class="cx-title">Cuentas operativas</h1>
                    <p class="cx-subtitle">Cuentas por cobrar ya generadas. Revisa saldo, vencimiento y entra al detalle para cobrar o auditar movimientos.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <div class="flex flex-wrap gap-2">
                    <a class="cx-btn cx-btn-muted" href="<?= url('cuentas-por-cobrar') ?>"><i class="fas fa-file-circle-plus"></i> Generar desde reservaci&oacute;n</a>
                    <a class="cx-btn cx-btn-muted" href="<?= url('cuentas-por-cobrar/simulador-caja') ?>"><i class="fas fa-cash-register"></i> Simulador de cobros</a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para llevar tus cuentas por cobrar.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-summary">
                <div class="cx-summary-item"><p class="cx-summary-label">Cuentas</p><p class="cx-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Pendientes</p><p class="cx-summary-value"><?= number_format((int)($resumen['pendientes'] ?? 0)) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Saldo por cobrar</p><p class="cx-summary-value"><?= cxc_op_money($resumen['saldo_total'] ?? 0) ?></p></div>
                <div class="cx-summary-item"><p class="cx-summary-label">Vencido</p><p class="cx-summary-value <?= $saldoVencido > 0 ? 'is-danger' : '' ?>"><?= cxc_op_money($saldoVencido) ?></p></div>
            </section>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar/operativas') ?>" class="cx-filter-form" data-cxc-live-search-form data-auto-filter-form>
                    <div class="cx-search">
                        <i class="fas fa-search cx-search-icon" data-cxc-search-icon></i>
                        <input class="cx-control" type="search" name="buscar" autocomplete="off" inputmode="search" value="<?= cxc_op_safe($buscar, '') ?>" placeholder="Buscar por folio, concepto, hu&eacute;sped o factura" data-cxc-live-search-input>
                    </div>
                    <select class="cx-control" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="liquidada" <?= $estado === 'liquidada' ? 'selected' : '' ?>>Liquidadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="incobrable" <?= $estado === 'incobrable' ? 'selected' : '' ?>>Incobrables</option>
                    </select>
                    <a class="cx-btn cx-btn-muted cx-reset" href="<?= url('cuentas-por-cobrar/operativas') ?>"><i class="fas fa-times"></i> Limpiar</a>
                </form>
            </section>

            <div data-cxc-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($cuentas)): ?>
                    <section class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-table-list"></i></div>
                        <h2>A&uacute;n no hay cuentas operativas</h2>
                        <p>No encontramos cuentas con estos filtros. Se generan desde tus reservaciones con saldo pendiente.</p>
                    </section>
                <?php else: ?>
                    <section class="cx-panel overflow-hidden">
                        <div class="cx-panel-head">
                            <div>
                                <div class="cx-panel-title">Lista de cuentas operativas</div>
                                <div class="cx-panel-sub">Cuentas ya generadas desde reservaciones: estado, vencimiento y saldo.</div>
                            </div>
                            <span class="cx-count-pill"><i class="fas fa-list"></i> <?= number_format($visibles) ?></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="cx-table">
                                <thead>
                                    <tr><th>Cuenta</th><th>Concepto</th><th>Hu&eacute;sped</th><th>Origen</th><th>Vence</th><th>Estado</th><th class="is-end">Total</th><th class="is-end">Saldo</th><th class="is-end">Acci&oacute;n</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cuentas as $cuenta): ?>
                                        <?php
                                        $cuentaId = (int)($cuenta['id'] ?? 0);
                                        $cuentaUrl = url('cuentas-por-cobrar/operativas/' . $cuentaId);
                                        [$eLabel, $eClass, $eIcon] = cxc_op_estado_meta($cuenta['estado'] ?? null);
                                        $esVencida = strtolower(trim((string)($cuenta['estado'] ?? ''))) === 'vencida';
                                        ?>
                                        <tr data-easy-href="<?= cxc_op_safe($cuentaUrl, '') ?>" role="link" tabindex="0" title="Abrir cuenta #<?= $cuentaId ?>" aria-label="Abrir cuenta por cobrar #<?= $cuentaId ?>">
                                            <td>
                                                <a class="cx-link" href="<?= $cuentaUrl ?>">#<?= $cuentaId ?></a>
                                                <div class="cx-sub"><?= cxc_op_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                            </td>
                                            <td><span class="cx-strong" style="font-weight:600"><?= cxc_op_safe($cuenta['concepto'] ?? null) ?></span></td>
                                            <td><?= cxc_op_safe($cuenta['huesped_nombre'] ?? null, 'Sin huésped') ?></td>
                                            <td>
                                                <?= cxc_op_safe($cuenta['origen_tipo'] ?? null) ?>
                                                <?php if (!empty($cuenta['origen_id'])): ?><div class="cx-sub">#<?= (int)$cuenta['origen_id'] ?></div><?php endif; ?>
                                            </td>
                                            <td><?= cxc_op_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin fecha') ?></td>
                                            <td><span class="cx-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                            <td class="is-end"><?= cxc_op_money($cuenta['total'] ?? 0) ?></td>
                                            <td class="is-end"><span class="cx-strong cx-saldo <?= $esVencida ? 'is-danger' : '' ?>"><?= cxc_op_money($cuenta['saldo'] ?? 0) ?></span></td>
                                            <td class="is-end"><a class="cx-act" href="<?= $cuentaUrl ?>"><i class="fas fa-eye"></i> Ver</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
        for (const [k, v] of data.entries()) { const n = String(v || '').trim(); if (n && n !== 'todos') url.searchParams.set(k, n); else url.searchParams.delete(k); }
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
    form.querySelector('[name="estado"]')?.addEventListener('change', () => { lastQuery = input.value.trim(); fetchResults(buildUrl()); });
    form.querySelector('.cx-reset')?.addEventListener('click', (e) => { e.preventDefault(); input.value = ''; const es = form.querySelector('[name="estado"]'); if (es) es.value = 'todos'; lastQuery = ''; fetchResults(new URL(e.currentTarget.href, window.location.origin)); input.focus(); });
    window.addEventListener('popstate', () => {
        const p = new URLSearchParams(window.location.search);
        input.value = p.get('buscar') || '';
        const es = form.querySelector('[name="estado"]'); if (es) es.value = p.get('estado') || 'todos';
        lastQuery = input.value.trim(); fetchResults(new URL(window.location.href), { pushState: false });
    });
    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => { isComposing = false; clearTimeout(liveSearchTimer); liveSearchTimer = setTimeout(live, delay); });
    input.addEventListener('input', () => { if (isComposing) return; clearTimeout(liveSearchTimer); liveSearchTimer = setTimeout(live, delay); });
})();
</script>
