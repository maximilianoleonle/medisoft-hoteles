<?php
$compras = $compras ?? [];
$resumen = $resumen ?? [
    'total' => 0,
    'borradores' => 0,
    'recibidas' => 0,
    'canceladas' => 0,
    'total_borrador' => '0.00',
];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$errorTecnico = $errorTecnico ?? null;

if (!function_exists('comp_safe')) {
    function comp_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('comp_money')) {
    function comp_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('comp_estado_meta')) {
    function comp_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'borrador'  => ['Borrador', 'is-borrador', 'fa-pen-ruler'],
            'recibida'  => ['Recibida', 'is-recibida', 'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'borrador');
$visibles = count($compras);
?>

<style>
.purchases-page {
    --cp-brand: var(--brand-primary, #1B2746);
    --cp-brand-2: var(--brand-secondary, #0F172A);
    --cp-gold: var(--brand-accent, #BD9441);
    --cp-gold-soft: color-mix(in srgb, var(--cp-gold) 15%, #FFFFFF);
    --cp-gold-line: color-mix(in srgb, var(--cp-gold) 42%, #E4D4B0);
    --cp-gold-ink: color-mix(in srgb, var(--cp-gold) 72%, #000);
    --cp-ivory: #F6F2EA;
    --cp-ivory-2: #FBF8F2;
    --cp-surface: #FFFFFF;
    --cp-surface-warm: #FCFAF5;
    --cp-border: color-mix(in srgb, var(--cp-brand) 7%, #E7E1D4);
    --cp-ring: color-mix(in srgb, var(--cp-gold) 32%, transparent);
    --cp-text: #171717;
    --cp-muted: #667085;
    --cp-heading: #111827;
    --cp-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cp-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cp-success: #1E9E63;
    --cp-success-bg: #E7F4EC;
    --cp-warning: #C2841C;
    --cp-warning-bg: #FAF0DC;
    --cp-danger: #B4392B;
    --cp-danger-bg: #F8EAE5;
    --cp-info: #2F77E0;
    --cp-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--cp-text);
    font-family: var(--cp-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cp-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cp-ivory-2), var(--cp-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.purchases-page .cp-shell { display: grid; gap: 14px; }

.purchases-page .cp-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.purchases-page .cp-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cp-gold), var(--cp-brand) 54%, color-mix(in srgb, var(--cp-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cp-brand) 72%, transparent);
}
.purchases-page .cp-kicker { margin: 0 0 2px; color: var(--cp-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.purchases-page .cp-title { margin: 0; font-family: var(--cp-serif); color: var(--cp-heading); font-weight: 700; font-size: clamp(2.2rem, 4vw, 3.1rem); line-height: .98; }
.purchases-page .cp-subtitle { max-width: 46rem; margin: 9px 0 0; color: var(--cp-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.purchases-page .cp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 18px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.purchases-page .cp-btn:hover { transform: translateY(-1px); }
.purchases-page .cp-btn:active { transform: translateY(0) scale(.98); }
.purchases-page .cp-btn:focus-visible { outline: 3px solid var(--cp-ring); outline-offset: 2px; }
.purchases-page .cp-btn-gold { background: linear-gradient(135deg, var(--cp-gold), color-mix(in srgb, var(--cp-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--cp-gold) 58%, transparent); }
.purchases-page .cp-btn-brand { background: linear-gradient(135deg, var(--cp-brand), var(--cp-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cp-brand) 60%, transparent); }
.purchases-page .cp-btn-muted { background: var(--cp-surface); border-color: var(--cp-border); color: var(--cp-muted); }

.purchases-page .cp-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.purchases-page .cp-summary-item { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.purchases-page .cp-summary-label { color: var(--cp-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.purchases-page .cp-summary-value { margin-top: 2px; font-family: var(--cp-serif); font-size: 1.7rem; font-weight: 700; line-height: 1.1; color: var(--cp-heading); }
.purchases-page .cp-summary-value.is-draft { color: var(--cp-warning); }
.purchases-page .cp-summary-value.is-received { color: var(--cp-success); }

.purchases-page .cp-panel { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }

.purchases-page .cp-filter-form { display: grid; grid-template-columns: minmax(220px, 1fr) minmax(160px, 200px) auto auto auto; gap: 10px; align-items: center; }
.purchases-page .cp-control { width: 100%; min-height: 40px; border: 1px solid var(--cp-border); background: var(--cp-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cp-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.purchases-page .cp-control:focus { border-color: var(--cp-gold); box-shadow: 0 0 0 3px var(--cp-ring); outline: none; }
.purchases-page select.cp-control { cursor: pointer; }
.purchases-page .cp-search { position: relative; }
.purchases-page .cp-search .cp-control { padding-left: 38px; }
.purchases-page .cp-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--cp-muted); font-size: .9rem; pointer-events: none; }

.purchases-page .cp-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--cp-border); }
.purchases-page .cp-panel-title { font-size: .85rem; font-weight: 700; color: var(--cp-heading); }
.purchases-page .cp-panel-sub { font-size: .75rem; color: var(--cp-muted); }
.purchases-page .cp-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: var(--cp-gold-soft); color: var(--cp-gold-ink); border: 1px solid var(--cp-gold-line); font-size: .72rem; font-weight: 700; white-space: nowrap; }

.purchases-page .cp-desktop { display: none; }
.purchases-page .cp-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.purchases-page .cp-table thead { background: var(--cp-surface-warm); border-bottom: 1px solid var(--cp-border); }
.purchases-page .cp-table th { padding: 12px 16px; color: var(--cp-muted); font-size: .68rem; font-weight: 700; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.purchases-page .cp-table th.is-end { text-align: right; }
.purchases-page .cp-table td { padding: 13px 16px; vertical-align: middle; }
.purchases-page .cp-table td.is-end { text-align: right; }
.purchases-page .cp-row { border-bottom: 1px solid var(--cp-border); transition: background .16s ease, box-shadow .16s ease; }
.purchases-page .cp-row:last-child { border-bottom: 0; }
.purchases-page .cp-row:hover { background: var(--cp-ivory-2); box-shadow: 0 10px 24px -24px rgba(27,39,70,.48); }
.purchases-page .cp-id { font-family: var(--cp-serif); font-size: 1.15rem; font-weight: 700; color: var(--cp-heading); line-height: 1; }
.purchases-page .cp-id-link { color: inherit; text-decoration: none; }
.purchases-page .cp-id-link:hover { text-decoration: underline; text-decoration-color: var(--cp-gold); text-underline-offset: 3px; }
.purchases-page .cp-sub { color: var(--cp-muted); font-size: .74rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.purchases-page .cp-prov { font-weight: 700; color: var(--cp-heading); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.purchases-page .cp-cell-strong { font-weight: 700; color: var(--cp-heading); }
.purchases-page .cp-muted-text { color: var(--cp-muted); font-size: .8rem; }

.purchases-page .cp-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.purchases-page .cp-badge.is-borrador { color: color-mix(in srgb, var(--cp-warning) 82%, #000); background: var(--cp-warning-bg); border-color: color-mix(in srgb, var(--cp-warning) 28%, #fff); }
.purchases-page .cp-badge.is-recibida { color: color-mix(in srgb, var(--cp-success) 78%, #000); background: var(--cp-success-bg); border-color: color-mix(in srgb, var(--cp-success) 26%, #fff); }
.purchases-page .cp-badge.is-cancelada { color: color-mix(in srgb, var(--cp-danger) 82%, #000); background: var(--cp-danger-bg); border-color: color-mix(in srgb, var(--cp-danger) 26%, #fff); }
.purchases-page .cp-badge.is-soft { color: var(--cp-muted); background: var(--cp-surface-warm); border-color: var(--cp-border); }

.purchases-page .cp-actions { display: flex; justify-content: flex-end; align-items: center; gap: 7px; }
.purchases-page .cp-inline-form { display: inline-flex; margin: 0; }
.purchases-page .cp-action {
    display: inline-flex; align-items: center; gap: .4rem; min-height: 34px; padding: 0 12px; border-radius: 10px;
    background: var(--cp-surface-warm); border: 1px solid var(--cp-border); color: var(--cp-muted); font-size: .78rem; font-weight: 700; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease;
}
.purchases-page .cp-action:hover { transform: translateY(-1px); }
.purchases-page .cp-action-view { color: var(--cp-info); }
.purchases-page .cp-action-view:hover { background: var(--cp-info-bg); }
.purchases-page .cp-btn-receive { background: linear-gradient(135deg, var(--cp-success), color-mix(in srgb, var(--cp-success) 74%, #000)); border-color: transparent; color: #fff; }
.purchases-page .cp-btn-receive.is-confirming { background: linear-gradient(135deg, var(--cp-warning), color-mix(in srgb, var(--cp-warning) 72%, #000)); }

.purchase-toast {
    position: fixed; right: 22px; bottom: 22px; z-index: 15000; max-width: min(380px, calc(100vw - 32px));
    border: 1px solid var(--cp-gold-line, #E4D4B0); border-radius: 14px; background: var(--cp-gold-soft, #FBF3DE); color: var(--cp-gold-ink, #6b521f);
    padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 600; line-height: 1.42;
    opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease;
}
.purchase-toast.is-visible { opacity: 1; transform: translateY(0); }

.purchases-page .cp-mobile { display: grid; gap: 10px; padding: 12px; }
.purchases-page .cp-mobile-card { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
.purchases-page .cp-mobile-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--cp-gold) 38%, var(--cp-border)); }
.purchases-page .cp-mobile-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.purchases-page .cp-mobile-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 11px; }
.purchases-page .cp-mini-label { font-size: .64rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--cp-muted); }
.purchases-page .cp-mini-value { font-weight: 700; color: var(--cp-heading); font-size: .88rem; }
.purchases-page .cp-mobile-actions { display: flex; gap: 7px; margin-top: 11px; }
.purchases-page .cp-mobile-actions .cp-action, .purchases-page .cp-mobile-actions .cp-inline-form { flex: 1; }
.purchases-page .cp-mobile-actions .cp-action, .purchases-page .cp-mobile-actions .cp-btn-receive { width: 100%; justify-content: center; }

.purchases-page .cp-empty { text-align: center; padding: 44px 18px; background: var(--cp-ivory-2); border: 1px dashed var(--cp-border); border-radius: 16px; }
.purchases-page .cp-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--cp-gold-soft); color: var(--cp-gold-ink); font-size: 1.3rem; }
.purchases-page .cp-empty h2 { color: var(--cp-brand); font-size: 1.1rem; font-weight: 700; }
.purchases-page .cp-empty p { color: var(--cp-muted); margin: 8px auto 0; max-width: 28rem; font-size: .9rem; }
.purchases-page .cp-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cp-gold-soft); border: 1px solid var(--cp-gold-line); border-radius: 16px; }
.purchases-page .cp-notice i { color: var(--cp-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.purchases-page .cp-notice strong { color: var(--cp-heading); display: block; margin-bottom: 2px; }
.purchases-page .cp-notice p { color: var(--cp-muted); font-size: .88rem; margin: 0; }

.purchases-page [data-cp-results-region] { transition: opacity .18s ease, filter .18s ease; }
.purchases-page [data-cp-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 768px) {
    .purchases-page .cp-desktop { display: block; }
    .purchases-page .cp-mobile { display: none; }
}
@media (max-width: 767px) {
    .purchases-page .cp-filter-form { grid-template-columns: 1fr; }
    .purchases-page .cp-filter-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .purchases-page .cp-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .purchases-page .cp-title { font-size: 2rem; }
}
</style>

<div class="purchases-page p-4 sm:p-6">
    <div class="cp-shell">
        <section class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="cp-title-lockup">
                <div class="cp-hero-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <p class="cp-kicker">Compras y abastecimiento</p>
                    <h1 class="cp-title">Compras</h1>
                    <p class="cp-subtitle">Registra lo que le compras a tus proveedores. La guardas como borrador y, cuando llegue la mercanc&iacute;a, la marcas como recibida para que entre al inventario.</p>
                </div>
            </div>
            <?php if ($tablaDisponible): ?>
                <a class="cp-btn cp-btn-gold" href="<?= url('compras/crear') ?>" title="Crear nueva compra">
                    <i class="fas fa-plus"></i>
                    Nueva compra
                </a>
            <?php endif; ?>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cp-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para empezar a registrar tus compras.<?php if (!empty($errorTecnico)): ?> <span style="opacity:.75">(<?= comp_safe($errorTecnico, '') ?>)</span><?php endif; ?></p>
                </div>
            </section>
        <?php else: ?>
            <section class="cp-summary">
                <div class="cp-summary-item">
                    <p class="cp-summary-label">Total</p>
                    <p class="cp-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p>
                </div>
                <div class="cp-summary-item">
                    <p class="cp-summary-label">Borradores</p>
                    <p class="cp-summary-value is-draft"><?= number_format((int)($resumen['borradores'] ?? 0)) ?></p>
                </div>
                <div class="cp-summary-item">
                    <p class="cp-summary-label">Recibidas</p>
                    <p class="cp-summary-value is-received"><?= number_format((int)($resumen['recibidas'] ?? 0)) ?></p>
                </div>
                <div class="cp-summary-item">
                    <p class="cp-summary-label">Pendiente en borradores</p>
                    <p class="cp-summary-value"><?= comp_money($resumen['total_borrador'] ?? 0) ?></p>
                </div>
            </section>

            <section class="cp-panel p-3 md:p-4">
                <form method="GET" action="<?= url('compras') ?>" class="cp-filter-form" data-cp-live-search-form data-auto-filter-form>
                    <div class="cp-search">
                        <i class="fas fa-search cp-search-icon" data-cp-search-icon></i>
                        <input class="cp-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                               value="<?= comp_safe($buscar, '') ?>"
                               placeholder="Buscar por folio o proveedor"
                               data-cp-live-search-input>
                    </div>
                    <select class="cp-control" name="estado" title="Filtrar por estado">
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todas</option>
                    </select>
                    <button class="cp-btn cp-btn-brand cp-filter-submit" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="cp-btn cp-btn-muted" href="<?= url('compras/reportes/recibidas') ?>" title="Ver reporte de compras recibidas">
                        <i class="fas fa-chart-column"></i>
                        Reporte
                    </a>
                    <a class="cp-btn cp-btn-muted cp-reset" href="<?= url('compras') ?>" title="Limpiar filtros">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </form>
            </section>

            <div data-cp-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($compras)): ?>
                    <section class="cp-empty">
                        <div class="cp-empty-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h2>A&uacute;n no hay compras</h2>
                        <p>No encontramos compras con estos filtros. Crea la primera o cambia la b&uacute;squeda.</p>
                        <a class="cp-btn cp-btn-gold mt-4" href="<?= url('compras/crear') ?>" style="display:inline-flex">
                            <i class="fas fa-plus"></i>
                            Nueva compra
                        </a>
                    </section>
                <?php else: ?>
                    <section class="cp-panel overflow-hidden">
                        <div class="cp-panel-head">
                            <div>
                                <div class="cp-panel-title">Lista de compras</div>
                                <div class="cp-panel-sub">Folio, proveedor y estado de cada compra.</div>
                            </div>
                            <span class="cp-count-pill">
                                <i class="fas fa-list"></i>
                                <?= number_format($visibles) ?> <?= $visibles === 1 ? 'compra' : 'compras' ?>
                            </span>
                        </div>

                        <div class="cp-desktop">
                            <table class="cp-table">
                                <colgroup>
                                    <col style="width: 16%;">
                                    <col style="width: 24%;">
                                    <col style="width: 15%;">
                                    <col style="width: 14%;">
                                    <col style="width: 9%;">
                                    <col style="width: 22%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Compra</th>
                                        <th>Proveedor</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th class="is-end">Renglones</th>
                                        <th class="is-end">Total / Acci&oacute;n</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($compras as $compra): ?>
                                        <?php
                                        $compraEstado = (string)($compra['estado'] ?? '');
                                        [$estadoLabel, $estadoClass, $estadoIcon] = comp_estado_meta($compraEstado);
                                        $compraId = (int)($compra['id'] ?? 0);
                                        $compraUrl = url('compras/' . $compraId);
                                        ?>
                                        <tr class="cp-row">
                                            <td>
                                                <a class="cp-id cp-id-link" href="<?= $compraUrl ?>">#<?= $compraId ?></a>
                                                <div class="cp-sub"><?= comp_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                                            </td>
                                            <td><div class="cp-prov"><?= comp_safe($compra['proveedor_nombre'] ?? null) ?></div></td>
                                            <td><?= comp_safe($compra['fecha_compra'] ?? null) ?></td>
                                            <td>
                                                <span class="cp-badge <?= $estadoClass ?>">
                                                    <i class="fas <?= $estadoIcon ?>"></i>
                                                    <?= $estadoLabel ?>
                                                </span>
                                            </td>
                                            <td class="is-end"><?= (int)($compra['detalle_count'] ?? 0) ?></td>
                                            <td class="is-end">
                                                <div class="cp-actions">
                                                    <span class="cp-cell-strong" style="font-size:.95rem"><?= comp_money($compra['total'] ?? 0) ?></span>
                                                    <a class="cp-action cp-action-view" href="<?= $compraUrl ?>" title="Ver compra">
                                                        <i class="fas fa-eye"></i>
                                                        Ver
                                                    </a>
                                                    <?php if ($compraEstado === 'borrador'): ?>
                                                        <form class="cp-inline-form" method="POST" action="<?= url('compras/' . $compraId . '/recibir') ?>" data-receive-form="1">
                                                            <?= csrf_field() ?>
                                                            <button class="cp-action cp-btn-receive" type="submit">
                                                                <i class="fas fa-box-open"></i>
                                                                Recibir
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="cp-mobile">
                            <?php foreach ($compras as $compra): ?>
                                <?php
                                $compraEstado = (string)($compra['estado'] ?? '');
                                [$estadoLabel, $estadoClass, $estadoIcon] = comp_estado_meta($compraEstado);
                                $compraId = (int)($compra['id'] ?? 0);
                                $compraUrl = url('compras/' . $compraId);
                                ?>
                                <article class="cp-mobile-card">
                                    <div class="cp-mobile-top">
                                        <div class="min-w-0">
                                            <a class="cp-id cp-id-link" href="<?= $compraUrl ?>">#<?= $compraId ?></a>
                                            <div class="cp-sub"><?= comp_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                                        </div>
                                        <span class="cp-badge <?= $estadoClass ?>">
                                            <i class="fas <?= $estadoIcon ?>"></i>
                                            <?= $estadoLabel ?>
                                        </span>
                                    </div>
                                    <div class="cp-mobile-meta">
                                        <div>
                                            <div class="cp-mini-label">Proveedor</div>
                                            <div class="cp-mini-value"><?= comp_safe($compra['proveedor_nombre'] ?? null) ?></div>
                                        </div>
                                        <div>
                                            <div class="cp-mini-label">Fecha</div>
                                            <div class="cp-mini-value"><?= comp_safe($compra['fecha_compra'] ?? null) ?></div>
                                        </div>
                                        <div>
                                            <div class="cp-mini-label">Renglones</div>
                                            <div class="cp-mini-value"><?= (int)($compra['detalle_count'] ?? 0) ?></div>
                                        </div>
                                        <div>
                                            <div class="cp-mini-label">Total</div>
                                            <div class="cp-mini-value"><?= comp_money($compra['total'] ?? 0) ?></div>
                                        </div>
                                    </div>
                                    <div class="cp-mobile-actions">
                                        <a class="cp-action cp-action-view" href="<?= $compraUrl ?>">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                        <?php if ($compraEstado === 'borrador'): ?>
                                            <form class="cp-inline-form" method="POST" action="<?= url('compras/' . $compraId . '/recibir') ?>" data-receive-form="1">
                                                <?= csrf_field() ?>
                                                <button class="cp-action cp-btn-receive" type="submit">
                                                    <i class="fas fa-box-open"></i>
                                                    Recibir
                                                </button>
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
    function showPurchaseToast(message, duration = 7000) {
        let toast = document.getElementById('purchaseReceiveToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'purchaseReceiveToast';
            toast.className = 'purchase-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        window.clearTimeout(toast._hideTimer);
        toast.textContent = message;
        requestAnimationFrame(() => toast.classList.add('is-visible'));
        toast._hideTimer = window.setTimeout(() => toast.classList.remove('is-visible'), duration);
    }

    function resetReceiveForm(form) {
        if (!form) return;

        delete form.dataset.confirmReceive;
        window.clearTimeout(form._confirmReceiveTimer);
        const button = form.querySelector('button[type="submit"]');
        if (button && button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
        button?.classList.remove('is-confirming');
    }

    document.addEventListener('submit', function(event) {
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || form.dataset.receiveForm !== '1') {
            return;
        }

        if (form.dataset.confirmReceive === '1') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        document.querySelectorAll('form[data-receive-form="1"]').forEach(otherForm => {
            if (otherForm !== form) resetReceiveForm(otherForm);
        });

        form.dataset.confirmReceive = '1';
        const button = form.querySelector('button[type="submit"]');
        if (button) {
            button.dataset.originalHtml = button.innerHTML;
            button.classList.add('is-confirming');
            button.innerHTML = '<i class="fas fa-check"></i> Confirmar recepci&oacute;n';
        }

        showPurchaseToast('Recibir la compra suma la mercancia al inventario y genera movimientos. Presiona Confirmar recepcion para continuar.');
        form._confirmReceiveTimer = window.setTimeout(() => resetReceiveForm(form), 7000);
    }, true);
})();

(() => {
    const form = document.querySelector('[data-cp-live-search-form]');
    const input = document.querySelector('[data-cp-live-search-input]');
    const searchIcon = document.querySelector('[data-cp-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-cp-results-region]');

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
        const incoming = doc.querySelector('[data-cp-results-region]');
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
                console.error('Error en busqueda de compras:', error);
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

    form.querySelector('.cp-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = 'borrador';
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = params.get('estado') || 'borrador';
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
