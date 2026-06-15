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

$estado = (string)($compra['estado'] ?? '');
$movimientosVinculados = 0;
foreach ($detalles as $detalle) {
    if (!empty($detalle['movimiento_inventario_id'])) {
        $movimientosVinculados++;
    }
}
?>

<style>
.purchase-detail-page {
    --purchase-brand: var(--brand-primary, #1f3f46);
    --purchase-accent: var(--brand-accent, #b58a3c);
    --purchase-line: color-mix(in srgb, var(--purchase-brand) 10%, #e5e7eb);
    --purchase-soft: color-mix(in srgb, var(--purchase-accent) 7%, #f8fafc);
    color: #243142;
}
.purchase-detail-page .purchase-detail-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--purchase-brand) 92%, #111827), color-mix(in srgb, var(--purchase-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.purchase-detail-page .purchase-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.purchase-detail-page .purchase-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.purchase-detail-page .purchase-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.purchase-detail-page .purchase-panel {
    border: 1px solid var(--purchase-line);
    background: rgba(255,255,255,.92);
}
.purchase-detail-page .purchase-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.purchase-detail-page .purchase-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--purchase-line);
    font-weight: 800;
}
.purchase-detail-page .purchase-btn-muted {
    background: #fff;
    color: #334155;
}
.purchase-detail-page .purchase-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--purchase-line);
    background: var(--purchase-soft);
    font-size: .78rem;
    font-weight: 800;
}
.purchase-detail-page .purchase-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.purchase-detail-page .purchase-table td,
.purchase-detail-page .purchase-table th {
    border-bottom: 1px solid var(--purchase-line);
    padding: 14px 12px;
}
.purchase-detail-page .purchase-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
</style>

<div class="purchase-detail-page">
    <section class="purchase-detail-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="purchase-kicker">Inventario / Compra</div>
                <h1 class="purchase-title">Compra #<?= (int)($compra['id'] ?? 0) ?></h1>
                <p class="purchase-subtitle">
                    Vista de solo lectura para revisar proveedor, lineas capturadas y movimientos de inventario vinculados.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Estado</div>
                    <div class="text-xl font-black"><?= comp_view_safe($estado) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Lineas</div>
                    <div class="text-2xl font-black"><?= count($detalles) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Movimientos</div>
                    <div class="text-2xl font-black"><?= $movimientosVinculados ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-xl font-black"><?= comp_view_money($compra['total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="purchase-btn purchase-btn-muted" href="<?= url('compras?estado=' . urlencode($estado ?: 'todos')) ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="purchase-btn purchase-btn-muted" href="<?= url('compras/reportes/recibidas') ?>">
                    <i class="fas fa-chart-column"></i>
                    Reporte
                </a>
            </div>
            <span class="purchase-badge">
                <i class="fas fa-circle-dot"></i>
                <?= comp_view_safe($estado) ?>
            </span>
        </div>

        <div class="purchase-panel p-5 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="purchase-meta-label">Proveedor</div>
                    <div class="font-black mt-1"><?= comp_view_safe($compra['proveedor_nombre'] ?? null) ?></div>
                </div>
                <div>
                    <div class="purchase-meta-label">Folio</div>
                    <div class="font-black mt-1"><?= comp_view_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                </div>
                <div>
                    <div class="purchase-meta-label">Fecha compra</div>
                    <div class="font-black mt-1"><?= comp_view_safe($compra['fecha_compra'] ?? null) ?></div>
                </div>
                <div>
                    <div class="purchase-meta-label">Fecha recepcion</div>
                    <div class="font-black mt-1"><?= comp_view_safe($compra['fecha_recepcion'] ?? null, 'Pendiente') ?></div>
                </div>
            </div>
        </div>

        <div class="purchase-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h2 class="font-black text-lg">Lineas y movimientos vinculados</h2>
            </div>
            <?php if (empty($detalles)): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-box-open"></i></div>
                    <h3 class="font-black text-lg">Esta compra no tiene lineas</h3>
                    <p class="text-sm text-slate-500 mt-1">No hay productos que mostrar.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="purchase-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Producto</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Costo</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-left">Movimiento</th>
                                <th class="text-right">Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td>
                                        <div class="font-black text-slate-800"><?= comp_view_safe($detalle['producto_nombre'] ?? null) ?></div>
                                        <div class="text-xs text-slate-500"><?= comp_view_safe($detalle['producto_codigo'] ?? null, 'Sin codigo') ?></div>
                                    </td>
                                    <td class="text-right"><?= comp_view_qty($detalle['cantidad'] ?? 0) ?></td>
                                    <td class="text-right"><?= comp_view_money($detalle['costo_unitario'] ?? 0) ?></td>
                                    <td class="text-right font-black"><?= comp_view_money($detalle['subtotal'] ?? 0) ?></td>
                                    <td>
                                        <?php if (!empty($detalle['movimiento_inventario_id'])): ?>
                                            <div class="font-black">#<?= (int)$detalle['movimiento_inventario_id'] ?> <?= comp_view_safe($detalle['movimiento_tipo'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= comp_view_safe($detalle['movimiento_created_at'] ?? null) ?></div>
                                        <?php else: ?>
                                            <span class="text-slate-400">Sin movimiento</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <?php if (!empty($detalle['movimiento_inventario_id'])): ?>
                                            <div class="font-black">
                                                <?= comp_view_qty($detalle['movimiento_stock_anterior'] ?? 0) ?>
                                                &rarr;
                                                <?= comp_view_qty($detalle['movimiento_stock_posterior'] ?? 0) ?>
                                            </div>
                                            <div class="text-xs text-slate-500"><?= comp_view_safe($detalle['movimiento_motivo'] ?? null) ?></div>
                                        <?php else: ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
