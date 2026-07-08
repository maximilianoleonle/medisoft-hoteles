<?php
$compra = $compra ?? [];
$detalles = $compra['detalles'] ?? [];

if (!function_exists('comp_view_safe')) {
    function comp_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('comp_view_money')) {
    function comp_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('comp_view_qty')) {
    function comp_view_qty($value)
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}

if (!function_exists('comp_view_estado_meta')) {
    function comp_view_estado_meta($estado)
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

$estado = (string)($compra['estado'] ?? '');
[$estadoLabel, $estadoClass, $estadoIcon] = comp_view_estado_meta($estado);
$movimientosVinculados = 0;
foreach ($detalles as $detalle) {
    if (!empty($detalle['movimiento_inventario_id'])) {
        $movimientosVinculados++;
    }
}
?>

<style>
.purchase-detail-page {
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
    --cp-text: #171717;
    --cp-muted: #667085;
    --cp-heading: #111827;
    --cp-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cp-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cp-success: #1E9E63; --cp-success-bg: #E7F4EC;
    --cp-warning: #C2841C; --cp-warning-bg: #FAF0DC;
    --cp-danger: #B4392B; --cp-danger-bg: #F8EAE5;
    min-height: 100%;
    color: var(--cp-text);
    font-family: var(--cp-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cp-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cp-ivory-2), var(--cp-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.purchase-detail-page .cp-shell { display: grid; gap: 14px; }
.purchase-detail-page .cp-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.purchase-detail-page .cp-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cp-gold), var(--cp-brand) 54%, color-mix(in srgb, var(--cp-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cp-brand) 72%, transparent);
}
.purchase-detail-page .cp-kicker { margin: 0 0 2px; color: var(--cp-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.purchase-detail-page .cp-title { margin: 0; font-family: var(--cp-serif); color: var(--cp-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.purchase-detail-page .cp-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--cp-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.purchase-detail-page .cp-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.purchase-detail-page .cp-stat { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.purchase-detail-page .cp-stat-label { color: var(--cp-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.purchase-detail-page .cp-stat-value { margin-top: 2px; font-family: var(--cp-serif); font-size: 1.55rem; font-weight: 700; line-height: 1.1; color: var(--cp-heading); }

.purchase-detail-page .cp-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.purchase-detail-page .cp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--cp-border); background: var(--cp-surface); color: var(--cp-text); font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease;
}
.purchase-detail-page .cp-btn:hover { transform: translateY(-1px); border-color: var(--cp-gold-line); color: var(--cp-gold-ink); }

.purchase-detail-page .cp-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.purchase-detail-page .cp-badge.is-borrador { color: color-mix(in srgb, var(--cp-warning) 82%, #000); background: var(--cp-warning-bg); border-color: color-mix(in srgb, var(--cp-warning) 28%, #fff); }
.purchase-detail-page .cp-badge.is-recibida { color: color-mix(in srgb, var(--cp-success) 78%, #000); background: var(--cp-success-bg); border-color: color-mix(in srgb, var(--cp-success) 26%, #fff); }
.purchase-detail-page .cp-badge.is-cancelada { color: color-mix(in srgb, var(--cp-danger) 82%, #000); background: var(--cp-danger-bg); border-color: color-mix(in srgb, var(--cp-danger) 26%, #fff); }
.purchase-detail-page .cp-badge.is-soft { color: var(--cp-muted); background: var(--cp-surface-warm); border-color: var(--cp-border); }

.purchase-detail-page .cp-panel { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.purchase-detail-page .cp-panel-title { font-family: var(--cp-serif); font-size: 1.4rem; font-weight: 700; color: var(--cp-heading); }
.purchase-detail-page .cp-meta-label { font-size: .68rem; color: var(--cp-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.purchase-detail-page .cp-meta-value { margin-top: 3px; font-weight: 700; color: var(--cp-heading); }

.purchase-detail-page .cp-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--cp-border); }
.purchase-detail-page .cp-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.purchase-detail-page .cp-table thead { background: var(--cp-surface-warm); border-bottom: 1px solid var(--cp-border); }
.purchase-detail-page .cp-table th { padding: 12px 14px; color: var(--cp-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.purchase-detail-page .cp-table th.is-end, .purchase-detail-page .cp-table td.is-end { text-align: right; }
.purchase-detail-page .cp-table td { padding: 13px 14px; border-bottom: 1px solid var(--cp-border); vertical-align: middle; }
.purchase-detail-page .cp-table tbody tr:last-child td { border-bottom: 0; }
.purchase-detail-page .cp-table tbody tr { transition: background .16s ease; }
.purchase-detail-page .cp-table tbody tr:hover { background: var(--cp-ivory-2); }
.purchase-detail-page .cp-strong { font-weight: 700; color: var(--cp-heading); }
.purchase-detail-page .cp-sub { color: var(--cp-muted); font-size: .72rem; }
.purchase-detail-page .cp-faint { color: var(--cp-muted); }

.purchase-detail-page .cp-empty { text-align: center; padding: 40px 18px; }
.purchase-detail-page .cp-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--cp-gold-soft); color: var(--cp-gold-ink); font-size: 1.25rem; }
.purchase-detail-page .cp-empty h3 { color: var(--cp-brand); font-size: 1.05rem; font-weight: 700; }
.purchase-detail-page .cp-empty p { color: var(--cp-muted); font-size: .88rem; margin-top: 6px; }

@media (max-width: 720px) { .purchase-detail-page .cp-stats { grid-template-columns: 1fr; } .purchase-detail-page .cp-title { font-size: 1.9rem; } }
</style>

<div class="purchase-detail-page p-4 sm:p-6">
    <div class="cp-shell">
        <?php $back_arrow_href = back_url('compras?estado=' . urlencode($estado ?: 'todos')); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cp-title-lockup">
                <div class="cp-hero-icon"><i class="fas fa-clipboard-list"></i></div>
                <div>
                    <p class="cp-kicker">Compras y abastecimiento</p>
                    <h1 class="cp-title">Compra #<?= (int)($compra['id'] ?? 0) ?></h1>
                    <p class="cp-subtitle">Detalle de la compra: proveedor, productos y los movimientos de inventario que gener&oacute;.</p>
                </div>
            </div>
            <span class="cp-badge <?= $estadoClass ?>">
                <i class="fas <?= $estadoIcon ?>"></i>
                <?= $estadoLabel ?>
            </span>
        </section>

        <section class="cp-stats">
            <div class="cp-stat">
                <p class="cp-stat-label">Renglones</p>
                <p class="cp-stat-value"><?= count($detalles) ?></p>
            </div>
            <div class="cp-stat">
                <p class="cp-stat-label">Movimientos de inventario</p>
                <p class="cp-stat-value"><?= $movimientosVinculados ?></p>
            </div>
            <div class="cp-stat">
                <p class="cp-stat-label">Total</p>
                <p class="cp-stat-value"><?= comp_view_money($compra['total'] ?? 0) ?></p>
            </div>
        </section>

        <section class="cp-toolbar">
            <div class="flex flex-wrap gap-2">
                <a class="cp-btn ms-back-legacy" href="<?= back_url('compras?estado=' . urlencode($estado ?: 'todos')) ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="cp-btn" href="<?= url('compras/reportes/recibidas') ?>">
                    <i class="fas fa-chart-column"></i>
                    Ver reporte
                </a>
            </div>
        </section>

        <div class="cp-panel p-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="cp-meta-label">Proveedor</div>
                    <div class="cp-meta-value"><?= comp_view_safe($compra['proveedor_nombre'] ?? null) ?></div>
                </div>
                <div>
                    <div class="cp-meta-label">Folio</div>
                    <div class="cp-meta-value"><?= comp_view_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                </div>
                <div>
                    <div class="cp-meta-label">Fecha de compra</div>
                    <div class="cp-meta-value"><?= comp_view_safe($compra['fecha_compra'] ?? null) ?></div>
                </div>
                <div>
                    <div class="cp-meta-label">Fecha de recepci&oacute;n</div>
                    <div class="cp-meta-value"><?= comp_view_safe($compra['fecha_recepcion'] ?? null, 'Pendiente') ?></div>
                </div>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="cp-panel overflow-hidden">
            <div class="cp-panel-head">
                <h2 class="cp-panel-title">Productos y movimientos de inventario</h2>
                <span class="cp-badge is-soft"><i class="fas fa-eye"></i> Solo consulta</span>
            </div>
            <?php if (empty($detalles)): ?>
                <div class="cp-empty">
                    <div class="cp-empty-icon"><i class="fas fa-box-open"></i></div>
                    <h3>Esta compra no tiene productos</h3>
                    <p>No hay renglones que mostrar.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="is-end">Cantidad</th>
                                <th class="is-end">Costo</th>
                                <th class="is-end">Subtotal</th>
                                <th>Movimiento</th>
                                <th class="is-end">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td>
                                        <div class="cp-strong"><?= comp_view_safe($detalle['producto_nombre'] ?? null) ?></div>
                                        <div class="cp-sub"><?= comp_view_safe($detalle['producto_codigo'] ?? null, 'Sin c&oacute;digo') ?></div>
                                    </td>
                                    <td class="is-end"><?= comp_view_qty($detalle['cantidad'] ?? 0) ?></td>
                                    <td class="is-end"><?= comp_view_money($detalle['costo_unitario'] ?? 0) ?></td>
                                    <td class="is-end cp-strong"><?= comp_view_money($detalle['subtotal'] ?? 0) ?></td>
                                    <td>
                                        <?php if (!empty($detalle['movimiento_inventario_id'])): ?>
                                            <div class="cp-strong">#<?= (int)$detalle['movimiento_inventario_id'] ?> <?= comp_view_safe($detalle['movimiento_tipo'] ?? null) ?></div>
                                            <div class="cp-sub"><?= comp_view_safe($detalle['movimiento_created_at'] ?? null) ?></div>
                                        <?php else: ?>
                                            <span class="cp-faint">Sin movimiento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="is-end">
                                        <?php if (!empty($detalle['movimiento_inventario_id'])): ?>
                                            <div class="cp-strong">
                                                <?= comp_view_qty($detalle['movimiento_stock_anterior'] ?? 0) ?>
                                                &rarr;
                                                <?= comp_view_qty($detalle['movimiento_stock_posterior'] ?? 0) ?>
                                            </div>
                                            <div class="cp-sub"><?= comp_view_safe($detalle['movimiento_motivo'] ?? null) ?></div>
                                        <?php else: ?>
                                            <span class="cp-faint">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
