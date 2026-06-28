<?php
$compras = $compras ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxp_preview_safe')) {
    function cxp_preview_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_preview_money')) {
    function cxp_preview_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxp_preview_estado_meta')) {
    function cxp_preview_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'borrador'  => ['Borrador', 'is-parcial',   'fa-pen-ruler'],
            'recibida'  => ['Recibida', 'is-pagada',    'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'recibida');
?>

<style>
.cxp-preview-page {
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
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.cxp-preview-page .cx-shell { display: grid; gap: 14px; }
.cxp-preview-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxp-preview-page .cx-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent);
}
.cxp-preview-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxp-preview-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.cxp-preview-page .cx-subtitle { max-width: 50rem; margin: 8px 0 0; color: var(--cx-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.cxp-preview-page .cx-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.cxp-preview-page .cx-stat { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxp-preview-page .cx-stat-label { color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.cxp-preview-page .cx-stat-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxp-preview-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.cxp-preview-page .cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.cxp-preview-page .cx-btn:hover { transform: translateY(-1px); }
.cxp-preview-page .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cx-brand) 60%, transparent); }
.cxp-preview-page .cx-btn-gold { background: linear-gradient(135deg, var(--cx-gold), color-mix(in srgb, var(--cx-gold) 76%, #000)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cx-gold) 58%, transparent); }
.cxp-preview-page .cx-btn-muted { background: var(--cx-surface); border-color: var(--cx-border); color: var(--cx-muted); }

.cxp-preview-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxp-preview-page .cx-control { width: 100%; min-height: 40px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cx-text); font-weight: 600; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.cxp-preview-page .cx-control:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; }
.cxp-preview-page select.cx-control { cursor: pointer; }
.cxp-preview-page .cx-filter-form { display: grid; grid-template-columns: 1fr 180px auto; gap: 10px; align-items: center; }

.cxp-preview-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.cxp-preview-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxp-preview-page .cx-badge.is-pagada { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-preview-page .cx-badge.is-cancelada, .cxp-preview-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxp-preview-page .cx-badge-ok { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-preview-page .cx-badge-blocked { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }

.cxp-preview-page .cx-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--cx-border); }
.cxp-preview-page .cx-panel-title { font-family: var(--cx-serif); font-size: 1.35rem; font-weight: 700; color: var(--cx-heading); }
.cxp-preview-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxp-preview-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxp-preview-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.cxp-preview-page .cx-table th.is-end, .cxp-preview-page .cx-table td.is-end { text-align: right; }
.cxp-preview-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: top; }
.cxp-preview-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxp-preview-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxp-preview-page .cx-link { font-weight: 700; color: var(--cx-heading); text-decoration: none; }
.cxp-preview-page .cx-link:hover { text-decoration: underline; text-decoration-color: var(--cx-gold); text-underline-offset: 3px; }
.cxp-preview-page .cx-sub { color: var(--cx-muted); font-size: .72rem; }
.cxp-preview-page .cx-faint { color: var(--cx-muted); }
.cxp-preview-page .cx-strong { font-weight: 700; color: var(--cx-heading); }

.cxp-preview-page .cx-empty { text-align: center; padding: 40px 18px; }
.cxp-preview-page .cx-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--cx-gold-soft); color: var(--cx-gold-ink); font-size: 1.25rem; }
.cxp-preview-page .cx-empty h2 { color: var(--cx-brand); font-size: 1.05rem; font-weight: 700; }
.cxp-preview-page .cx-empty p { color: var(--cx-muted); font-size: .88rem; margin-top: 6px; }
.cxp-preview-page .cx-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cx-gold-soft); border: 1px solid var(--cx-gold-line); border-radius: 16px; }
.cxp-preview-page .cx-notice i { color: var(--cx-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.cxp-preview-page .cx-notice strong { color: var(--cx-heading); display: block; margin-bottom: 2px; }
.cxp-preview-page .cx-notice p { color: var(--cx-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .cxp-preview-page .cx-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .cxp-preview-page .cx-filter-form { grid-template-columns: 1fr; } }
</style>

<div class="cxp-preview-page p-4 sm:p-6">
    <div class="cx-shell">
        <section class="cx-title-lockup">
            <div class="cx-hero-icon"><i class="fas fa-file-circle-plus"></i></div>
            <div>
                <p class="cx-kicker">Pagos a proveedores</p>
                <h1 class="cx-title">Generar cuentas desde compras</h1>
                <p class="cx-subtitle">Revisa tus compras recibidas y genera la cuenta por pagar de cada una. Esto solo crea la cuenta; no registra pagos ni toca Caja.</p>
            </div>
        </section>

        <section class="cx-toolbar">
            <div class="flex flex-wrap gap-2">
                <a class="cx-btn cx-btn-muted" href="<?= back_url('cuentas-por-pagar') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a cuentas por pagar
                </a>
                <a class="cx-btn cx-btn-muted" href="<?= url('compras/reportes/recibidas') ?>">
                    <i class="fas fa-chart-column"></i>
                    Compras recibidas
                </a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cx-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; disponible.</strong>
                    <p>Necesitas tener compras y proveedores registrados antes de generar cuentas por pagar.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="cx-stats">
                <div class="cx-stat"><p class="cx-stat-label">Compras</p><p class="cx-stat-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Recibidas</p><p class="cx-stat-value"><?= number_format((int)($resumen['recibidas'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Listas para generar</p><p class="cx-stat-value"><?= number_format((int)($resumen['elegibles'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Ya con cuenta</p><p class="cx-stat-value"><?= number_format((int)($resumen['con_cxp'] ?? 0)) ?></p></div>
                <div class="cx-stat"><p class="cx-stat-label">Monto por generar</p><p class="cx-stat-value"><?= cxp_preview_money($resumen['total_elegible'] ?? 0) ?></p></div>
            </section>

            <section class="cx-panel p-3 md:p-4">
                <form method="GET" action="<?= url('cuentas-por-pagar/generacion-preview') ?>" class="cx-filter-form" data-auto-filter-form>
                    <input class="cx-control" type="search" name="buscar" value="<?= cxp_preview_safe($buscar, '') ?>" placeholder="Buscar por compra, folio o proveedor">
                    <select class="cx-control" name="estado">
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todas</option>
                    </select>
                    <button class="cx-btn cx-btn-brand" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </section>

            <div class="cx-panel overflow-hidden">
                <div class="cx-panel-head">
                    <h2 class="cx-panel-title">Compras evaluadas</h2>
                </div>
                <?php if (empty($compras)): ?>
                    <div class="cx-empty">
                        <div class="cx-empty-icon"><i class="fas fa-magnifying-glass"></i></div>
                        <h2>No hay compras para evaluar</h2>
                        <p>Cambia los filtros o revisa tus compras recibidas del hotel.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cx-table">
                            <thead>
                                <tr>
                                    <th>Compra</th>
                                    <th>Proveedor</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="is-end">Total</th>
                                    <th>Cuenta por pagar</th>
                                    <th>Generar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compras as $compra): ?>
                                    <?php
                                    $esElegible = !empty($compra['es_elegible']);
                                    [$cEstadoLabel, $cEstadoClass, $cEstadoIcon] = cxp_preview_estado_meta($compra['compra_estado'] ?? null);
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="cx-link" href="<?= url('compras/' . (int)($compra['compra_id'] ?? 0)) ?>">#<?= (int)($compra['compra_id'] ?? 0) ?></a>
                                            <div class="cx-sub"><?= cxp_preview_safe($compra['compra_folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($compra['proveedor_id']) && !empty($compra['proveedor_nombre'])): ?>
                                                <a class="cx-link" href="<?= url('proveedores/' . (int)$compra['proveedor_id']) ?>"><?= cxp_preview_safe($compra['proveedor_nombre'] ?? null) ?></a>
                                                <div class="cx-sub"><?= cxp_preview_safe($compra['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                                            <?php else: ?>
                                                <span class="cx-faint">Sin proveedor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?= cxp_preview_safe($compra['fecha_recepcion'] ?? null, 'Sin recepci&oacute;n') ?></div>
                                            <div class="cx-sub">Compra <?= cxp_preview_safe($compra['fecha_compra'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <span class="cx-badge <?= $cEstadoClass ?>">
                                                <i class="fas <?= $cEstadoIcon ?>"></i>
                                                <?= $cEstadoLabel ?>
                                            </span>
                                        </td>
                                        <td class="is-end cx-strong"><?= cxp_preview_money($compra['total'] ?? 0) ?></td>
                                        <td>
                                            <?php if (!empty($compra['cxp_id'])): ?>
                                                <a class="cx-link" href="<?= url('cuentas-por-pagar/' . (int)$compra['cxp_id']) ?>">Cuenta #<?= (int)$compra['cxp_id'] ?></a>
                                                <div class="cx-sub">
                                                    <?= cxp_preview_safe($compra['cxp_estado'] ?? null) ?> &middot; saldo <?= cxp_preview_money($compra['cxp_saldo'] ?? 0) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="cx-faint">A&uacute;n sin cuenta</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cx-badge cx-badge-ok"><i class="fas fa-circle-check"></i> Lista</span>
                                                <div class="cx-sub mt-1"><?= cxp_preview_safe($compra['motivo_elegibilidad'] ?? null) ?></div>
                                                <form method="POST" action="<?= url('cuentas-por-pagar/generar-desde-compra/' . (int)($compra['compra_id'] ?? 0)) ?>" class="mt-3">
                                                    <?= csrf_field() ?>
                                                    <button class="cx-btn cx-btn-gold" type="submit">
                                                        <i class="fas fa-file-invoice-dollar"></i>
                                                        Generar cuenta
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="cx-badge cx-badge-blocked"><i class="fas fa-ban"></i> No disponible</span>
                                                <div class="cx-sub mt-1"><?= cxp_preview_safe($compra['motivo_bloqueo'] ?? null, 'No se puede generar todav&iacute;a.') ?></div>
                                            <?php endif; ?>
                                        </td>
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
