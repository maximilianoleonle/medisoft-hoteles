<?php
$cuenta = $cuenta ?? [];
$movimientos = $movimientos ?? [];
$movimientosDisponibles = $movimientosDisponibles ?? false;
$pagoCaja = $pagoCaja ?? [];
$pagoToken = $pagoToken ?? null;

if (!function_exists('cxp_view_safe')) {
    function cxp_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_view_money')) {
    function cxp_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}
?>

<style>
.cxp-detail-page {
    --cxp-brand: var(--brand-primary, #1f3f46);
    --cxp-accent: var(--brand-accent, #b58a3c);
    --cxp-line: color-mix(in srgb, var(--cxp-brand) 10%, #e5e7eb);
    --cxp-soft: color-mix(in srgb, var(--cxp-accent) 7%, #f8fafc);
    color: #243142;
}
.cxp-detail-page .cxp-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxp-brand) 92%, #111827), color-mix(in srgb, var(--cxp-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.cxp-detail-page .cxp-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.cxp-detail-page .cxp-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxp-detail-page .cxp-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.cxp-detail-page .cxp-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxp-detail-page .cxp-panel {
    border: 1px solid var(--cxp-line);
    background: rgba(255,255,255,.92);
}
.cxp-detail-page .cxp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxp-line);
    font-weight: 800;
}
.cxp-detail-page .cxp-btn-muted {
    background: #fff;
    color: #334155;
}
.cxp-detail-page .cxp-btn-primary {
    background: var(--cxp-brand);
    border-color: var(--cxp-brand);
    color: #fff;
}
.cxp-detail-page .cxp-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxp-line);
    background: #fff;
    padding: 0 12px;
}
.cxp-detail-page textarea.cxp-input {
    min-height: 84px;
    padding-top: 10px;
}
.cxp-detail-page .cxp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxp-line);
    background: var(--cxp-soft);
    font-size: .78rem;
    font-weight: 800;
}
.cxp-detail-page .cxp-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.cxp-detail-page .cxp-badge-blocked {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.cxp-detail-page .cxp-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxp-detail-page .cxp-table td,
.cxp-detail-page .cxp-table th {
    border-bottom: 1px solid var(--cxp-line);
    padding: 14px 12px;
}
.cxp-detail-page .cxp-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
</style>

<div class="cxp-detail-page">
    <section class="cxp-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxp-kicker">Compras / Cuenta por pagar</div>
                <h1 class="cxp-title">Cuenta #<?= (int)($cuenta['id'] ?? 0) ?></h1>
                <p class="cxp-subtitle">
                    Detalle de CxP con pago proveedor controlado. Solo registra egreso cuando hay corte de Caja abierto y validaciones de hotel.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Estado</div>
                    <div class="text-xl font-black"><?= cxp_view_safe($cuenta['estado'] ?? null) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-xl font-black"><?= cxp_view_money($cuenta['total'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Saldo</div>
                    <div class="text-xl font-black"><?= cxp_view_money($cuenta['saldo'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Movimientos</div>
                    <div class="text-2xl font-black"><?= count($movimientos) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar/simulador-caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Simulador Caja
                </a>
                <?php if (!empty($cuenta['compra_id'])): ?>
                    <a class="cxp-btn cxp-btn-muted" href="<?= url('compras/' . (int)$cuenta['compra_id']) ?>">
                        <i class="fas fa-receipt"></i>
                        Compra
                    </a>
                <?php endif; ?>
                <?php if (!empty($cuenta['proveedor_id'])): ?>
                    <a class="cxp-btn cxp-btn-muted" href="<?= url('proveedores/' . (int)$cuenta['proveedor_id']) ?>">
                        <i class="fas fa-truck"></i>
                        Proveedor
                    </a>
                <?php endif; ?>
            </div>
            <span class="cxp-badge">
                <i class="fas fa-shield-alt"></i>
                Pago controlado
            </span>
        </div>

        <div class="cxp-panel p-5 mb-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="cxp-meta-label">Proveedor</div>
                    <div class="font-black mt-1"><?= cxp_view_safe($cuenta['proveedor_nombre'] ?? null) ?></div>
                    <div class="text-xs text-slate-500"><?= cxp_view_safe($cuenta['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Folio</div>
                    <div class="font-black mt-1"><?= cxp_view_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Emision</div>
                    <div class="font-black mt-1"><?= cxp_view_safe($cuenta['fecha_emision'] ?? null) ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Vencimiento</div>
                    <div class="font-black mt-1"><?= cxp_view_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Compra vinculada</div>
                    <div class="font-black mt-1">
                        <?php if (!empty($cuenta['compra_id'])): ?>
                            #<?= (int)$cuenta['compra_id'] ?> <?= cxp_view_safe($cuenta['compra_folio'] ?? null, '') ?>
                        <?php else: ?>
                            Sin compra vinculada
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="cxp-meta-label">Subtotal</div>
                    <div class="font-black mt-1"><?= cxp_view_money($cuenta['subtotal'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Impuestos</div>
                    <div class="font-black mt-1"><?= cxp_view_money($cuenta['impuestos'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="cxp-meta-label">Moneda</div>
                    <div class="font-black mt-1"><?= cxp_view_safe($cuenta['moneda'] ?? null) ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cxp-meta-label">Descripcion</div>
                    <div class="mt-1 text-slate-700"><?= cxp_view_safe($cuenta['descripcion'] ?? null, 'Sin descripcion') ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cxp-meta-label">Notas</div>
                    <div class="mt-1 text-slate-700 whitespace-pre-line"><?= cxp_view_safe($cuenta['notas'] ?? null, 'Sin notas') ?></div>
                </div>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="cxp-panel p-5 mb-4">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h2 class="font-black text-lg">Pago proveedor con Caja</h2>
                    <?php if (!empty($pagoCaja['elegible'])): ?>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= cxp_view_safe($pagoCaja['motivo_elegibilidad'] ?? null) ?>
                            Se registrara un movimiento de Caja de tipo gasto y un movimiento referencial de CxP.
                        </p>
                    <?php else: ?>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= cxp_view_safe($pagoCaja['motivo_bloqueo'] ?? null, 'Esta cuenta no es elegible para pago con Caja.') ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($pagoCaja['corte'])): ?>
                        <span class="cxp-badge cxp-badge-ok">
                            <i class="fas fa-cash-register"></i>
                            Corte #<?= (int)$pagoCaja['corte']['id'] ?>
                        </span>
                    <?php else: ?>
                        <span class="cxp-badge cxp-badge-blocked">
                            <i class="fas fa-ban"></i>
                            Sin corte abierto
                        </span>
                    <?php endif; ?>
                    <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar/simulador-caja') ?>">
                        <i class="fas fa-search"></i>
                        Ver diagnostico
                    </a>
                </div>
            </div>

            <?php if (!empty($pagoCaja['elegible']) && !empty($pagoToken)): ?>
                <form method="POST" action="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0) . '/registrar-pago-caja') ?>" class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pago_token" value="<?= cxp_view_safe($pagoToken, '') ?>">

                    <div>
                        <label class="cxp-meta-label" for="cxp_pago_monto">Monto</label>
                        <input
                            id="cxp_pago_monto"
                            class="cxp-input mt-1"
                            type="number"
                            name="monto"
                            min="0.01"
                            max="<?= cxp_view_safe($pagoCaja['monto_maximo'] ?? ($cuenta['saldo'] ?? 0), '0.00') ?>"
                            step="0.01"
                            value="<?= cxp_view_safe($pagoCaja['monto_maximo'] ?? ($cuenta['saldo'] ?? 0), '0.00') ?>"
                            required
                        >
                    </div>

                    <div>
                        <label class="cxp-meta-label" for="cxp_pago_metodo">Metodo</label>
                        <select id="cxp_pago_metodo" class="cxp-input mt-1" name="metodo_pago" required>
                            <?php foreach (($pagoCaja['metodos_pago'] ?? []) as $metodo => $label): ?>
                                <option value="<?= cxp_view_safe($metodo, '') ?>"><?= cxp_view_safe($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="cxp-meta-label" for="cxp_pago_referencia">Referencia</label>
                        <input
                            id="cxp_pago_referencia"
                            class="cxp-input mt-1"
                            type="text"
                            name="referencia"
                            maxlength="100"
                            placeholder="Folio, transferencia o nota breve"
                        >
                    </div>

                    <div class="md:col-span-3">
                        <label class="cxp-meta-label" for="cxp_pago_notas">Notas</label>
                        <textarea id="cxp_pago_notas" class="cxp-input mt-1" name="notas" maxlength="1000" placeholder="Opcional"></textarea>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="cxp-btn cxp-btn-primary w-full justify-center">
                            <i class="fas fa-money-bill-wave"></i>
                            Registrar pago
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="cxp-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-black text-lg">Movimientos referenciales</h2>
                <span class="cxp-badge">
                    <i class="fas fa-list-check"></i>
                    Trazabilidad CxP
                </span>
            </div>

            <?php if (!$movimientosDisponibles): ?>
                <div class="p-8 text-center">
                    <h3 class="font-black text-lg">Tabla de movimientos no disponible</h3>
                    <p class="text-sm text-slate-500 mt-1">Revisa la migracion Fase 3B antes de usar movimientos referenciales.</p>
                </div>
            <?php elseif (empty($movimientos)): ?>
                <div class="p-8 text-center">
                    <h3 class="font-black text-lg">Sin movimientos</h3>
                    <p class="text-sm text-slate-500 mt-1">La cuenta no tiene movimientos referenciales registrados.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="cxp-table min-w-full text-sm">
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
                                    <td><?= cxp_view_safe($movimiento['created_at'] ?? null) ?></td>
                                    <td><?= cxp_view_safe($movimiento['tipo_movimiento'] ?? null) ?></td>
                                    <td class="text-right"><?= cxp_view_money($movimiento['monto'] ?? 0) ?></td>
                                    <td class="text-right"><?= cxp_view_money($movimiento['saldo_anterior'] ?? 0) ?></td>
                                    <td class="text-right font-black"><?= cxp_view_money($movimiento['saldo_posterior'] ?? 0) ?></td>
                                    <td><?= cxp_view_safe($movimiento['referencia'] ?? null, 'Sin referencia') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
