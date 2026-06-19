<?php
$cuenta = $cuenta ?? [];
$movimientos = $movimientos ?? [];
$movimientosDisponibles = $movimientosDisponibles ?? false;

if (!function_exists('cxc_op_view_safe')) {
    function cxc_op_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_op_view_money')) {
    function cxc_op_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}
?>

<style>
.cxc-op-detail {
    --cxc-op-brand: var(--brand-primary, #1f3f46);
    --cxc-op-accent: var(--brand-accent, #b58a3c);
    --cxc-op-line: color-mix(in srgb, var(--cxc-op-brand) 10%, #e5e7eb);
    --cxc-op-soft: color-mix(in srgb, var(--cxc-op-accent) 7%, #f8fafc);
    color: #243142;
}
.cxc-op-detail .cxc-op-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxc-op-brand) 90%, #111827), color-mix(in srgb, var(--cxc-op-accent) 54%, #47321f));
    color: #fff;
    padding: 28px;
}
.cxc-op-detail .cxc-op-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .78;
    font-weight: 800;
}
.cxc-op-detail .cxc-op-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxc-op-detail .cxc-op-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.cxc-op-detail .cxc-op-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxc-op-detail .cxc-op-panel {
    border: 1px solid var(--cxc-op-line);
    background: rgba(255,255,255,.94);
}
.cxc-op-detail .cxc-op-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxc-op-line);
    font-weight: 800;
}
.cxc-op-detail .cxc-op-btn-muted {
    background: #fff;
    color: #334155;
}
.cxc-op-detail .cxc-op-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxc-op-line);
    background: var(--cxc-op-soft);
    font-size: .78rem;
    font-weight: 800;
}
.cxc-op-detail .cxc-op-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxc-op-detail .cxc-op-table td,
.cxc-op-detail .cxc-op-table th {
    border-bottom: 1px solid var(--cxc-op-line);
    padding: 14px 12px;
}
.cxc-op-detail .cxc-op-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
</style>

<div class="cxc-op-detail">
    <section class="cxc-op-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxc-op-kicker">CxC operativa / Solo lectura</div>
                <h1 class="cxc-op-title">Cuenta por cobrar #<?= (int)($cuenta['id'] ?? 0) ?></h1>
                <p class="cxc-op-subtitle">
                    Detalle read-only de la tabla operativa. Esta pantalla no cobra, no registra pagos, no modifica saldos y no toca Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Estado</div>
                    <div class="text-xl font-black"><?= cxc_op_view_safe($cuenta['estado'] ?? null) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-xl font-black"><?= cxc_op_view_money($cuenta['total'] ?? 0) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Saldo</div>
                    <div class="text-xl font-black"><?= cxc_op_view_money($cuenta['saldo'] ?? 0) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Movimientos</div>
                    <div class="text-2xl font-black"><?= count($movimientos) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar/operativas') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar') ?>">
                    <i class="fas fa-chart-line"></i>
                    Reporte estimado
                </a>
                <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar/simulador-caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Simulador Caja
                </a>
                <?php if (!empty($cuenta['reservacion_id'])): ?>
                    <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('reservaciones/ver/' . (int)$cuenta['reservacion_id']) ?>">
                        <i class="fas fa-calendar-check"></i>
                        Reservacion
                    </a>
                <?php endif; ?>
                <?php if (!empty($cuenta['solicitud_factura_id'])): ?>
                    <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('facturacion/ver/' . (int)$cuenta['solicitud_factura_id']) ?>">
                        <i class="fas fa-file-invoice"></i>
                        Factura
                    </a>
                <?php endif; ?>
                <?php if (!empty($cuenta['huesped_id'])): ?>
                    <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('huespedes/' . (int)$cuenta['huesped_id']) ?>">
                        <i class="fas fa-user"></i>
                        Huesped
                    </a>
                <?php endif; ?>
            </div>
            <span class="cxc-op-badge">
                <i class="fas fa-lock"></i>
                Sin acciones de cobro
            </span>
        </div>

        <div class="cxc-op-panel p-5 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="cxc-op-label">Folio</div>
                    <div class="font-black mt-1"><?= cxc_op_view_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                </div>
                <div>
                    <div class="cxc-op-label">Origen</div>
                    <div class="font-black mt-1">
                        <?= cxc_op_view_safe($cuenta['origen_tipo'] ?? null) ?>
                        <?php if (!empty($cuenta['origen_id'])): ?>
                            #<?= (int)$cuenta['origen_id'] ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="cxc-op-label">Emision</div>
                    <div class="font-black mt-1"><?= cxc_op_view_safe($cuenta['fecha_emision'] ?? null) ?></div>
                </div>
                <div>
                    <div class="cxc-op-label">Vencimiento</div>
                    <div class="font-black mt-1"><?= cxc_op_view_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div>
                </div>
                <div>
                    <div class="cxc-op-label">Huesped</div>
                    <div class="font-black mt-1"><?= cxc_op_view_safe($cuenta['huesped_nombre'] ?? null, 'Sin huesped') ?></div>
                    <div class="text-xs text-slate-500"><?= cxc_op_view_safe($cuenta['huesped_telefono'] ?? null, 'Sin telefono') ?></div>
                </div>
                <div>
                    <div class="cxc-op-label">Reservacion</div>
                    <div class="font-black mt-1">
                        <?php if (!empty($cuenta['reservacion_id'])): ?>
                            #<?= (int)$cuenta['reservacion_id'] ?> <?= cxc_op_view_safe($cuenta['reservacion_estado'] ?? null, '') ?>
                        <?php else: ?>
                            Sin reservacion
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="cxc-op-label">Factura</div>
                    <div class="font-black mt-1">
                        <?php if (!empty($cuenta['solicitud_factura_id'])): ?>
                            #<?= (int)$cuenta['solicitud_factura_id'] ?> <?= cxc_op_view_safe($cuenta['numero_factura'] ?? null, '') ?>
                        <?php else: ?>
                            Sin factura vinculada
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="cxc-op-label">Moneda</div>
                    <div class="font-black mt-1"><?= cxc_op_view_safe($cuenta['moneda'] ?? null) ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cxc-op-label">Concepto</div>
                    <div class="mt-1 text-slate-700"><?= cxc_op_view_safe($cuenta['concepto'] ?? null, 'Sin concepto') ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cxc-op-label">Notas</div>
                    <div class="mt-1 text-slate-700 whitespace-pre-line"><?= cxc_op_view_safe($cuenta['notas'] ?? null, 'Sin notas') ?></div>
                </div>
            </div>
        </div>

        <div class="cxc-op-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-black text-lg">Movimientos internos CxC</h2>
                <span class="cxc-op-badge">
                    <i class="fas fa-list-check"></i>
                    Read-only
                </span>
            </div>

            <?php if (!$movimientosDisponibles): ?>
                <div class="p-8 text-center">
                    <h3 class="font-black text-lg">Tabla de movimientos no disponible</h3>
                    <p class="text-sm text-slate-500 mt-1">Revisa la migracion 7B-A antes de usar el detalle operativo.</p>
                </div>
            <?php elseif (empty($movimientos)): ?>
                <div class="p-8 text-center">
                    <h3 class="font-black text-lg">Sin movimientos</h3>
                    <p class="text-sm text-slate-500 mt-1">Esta cuenta no tiene trazabilidad interna registrada.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="cxc-op-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Tipo</th>
                                <th class="text-right">Monto</th>
                                <th class="text-right">Saldo anterior</th>
                                <th class="text-right">Saldo posterior</th>
                                <th class="text-left">Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                                <tr>
                                    <td><?= cxc_op_view_safe($movimiento['created_at'] ?? null) ?></td>
                                    <td><?= cxc_op_view_safe($movimiento['tipo_movimiento'] ?? null) ?></td>
                                    <td class="text-right"><?= cxc_op_view_money($movimiento['monto'] ?? 0) ?></td>
                                    <td class="text-right"><?= cxc_op_view_money($movimiento['saldo_anterior'] ?? 0) ?></td>
                                    <td class="text-right font-black"><?= cxc_op_view_money($movimiento['saldo_posterior'] ?? 0) ?></td>
                                    <td><?= cxc_op_view_safe($movimiento['referencia'] ?? null, 'Sin referencia') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
