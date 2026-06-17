<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxp_cash_safe')) {
    function cxp_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_cash_money')) {
    function cxp_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxp-cash-page {
    --cxp-brand: var(--brand-primary, #1f3f46);
    --cxp-accent: var(--brand-accent, #b58a3c);
    --cxp-line: color-mix(in srgb, var(--cxp-brand) 10%, #e5e7eb);
    --cxp-soft: color-mix(in srgb, var(--cxp-accent) 7%, #f8fafc);
    color: #243142;
}
.cxp-cash-page .cxp-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxp-brand) 92%, #111827), color-mix(in srgb, var(--cxp-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.cxp-cash-page .cxp-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.cxp-cash-page .cxp-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxp-cash-page .cxp-subtitle {
    margin-top: 8px;
    max-width: 56rem;
    color: rgba(255,255,255,.86);
}
.cxp-cash-page .cxp-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxp-cash-page .cxp-panel {
    border: 1px solid var(--cxp-line);
    background: rgba(255,255,255,.92);
}
.cxp-cash-page .cxp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxp-line);
    font-weight: 800;
}
.cxp-cash-page .cxp-btn-primary {
    background: var(--cxp-brand);
    border-color: var(--cxp-brand);
    color: #fff;
}
.cxp-cash-page .cxp-btn-muted {
    background: #fff;
    color: #334155;
}
.cxp-cash-page .cxp-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxp-line);
    background: #fff;
    padding: 0 12px;
}
.cxp-cash-page .cxp-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxp-cash-page .cxp-table td,
.cxp-cash-page .cxp-table th {
    border-bottom: 1px solid var(--cxp-line);
    padding: 14px 12px;
    vertical-align: top;
}
.cxp-cash-page .cxp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxp-line);
    background: var(--cxp-soft);
    font-size: .78rem;
    font-weight: 800;
}
.cxp-cash-page .cxp-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.cxp-cash-page .cxp-badge-blocked {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.cxp-cash-page .cxp-badge-warn {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
</style>

<div class="cxp-cash-page">
    <section class="cxp-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxp-kicker">CxP / Caja</div>
                <h1 class="cxp-title">Simulador de egreso a proveedor</h1>
                <p class="cxp-subtitle">
                    Diagnostico previo para saber que cuentas podrian pagarse con Caja. Esta pantalla no registra egresos, no cambia saldos y no crea movimientos.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Cuentas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Elegibles</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['elegibles'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Saldo elegible</div>
                    <div class="text-xl font-black"><?= cxp_cash_money($resumen['saldo_elegible'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Corte abierto</div>
                    <div class="text-xl font-black"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a CxP
                </a>
                <a class="cxp-btn cxp-btn-muted" href="<?= url('caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Ver Caja
                </a>
            </div>
            <span class="cxp-badge">
                <i class="fas fa-lock"></i>
                Solo GET
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="cxp-panel p-5">
                <strong>Simulador no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas base de CxP o Caja para evaluar egresos de forma segura.</p>
            </div>
        <?php else: ?>
            <div class="cxp-panel p-5 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Corte</div>
                        <div class="font-black mt-1"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Caja</div>
                        <div class="font-black mt-1"><?= cxp_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div>
                        <div class="text-xs text-slate-500"><?= cxp_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicacion') ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Apertura</div>
                        <div class="font-black mt-1"><?= cxp_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="cxp-badge cxp-badge-ok mt-1">
                                <i class="fas fa-check-circle"></i>
                                Abierto
                            </span>
                        <?php else: ?>
                            <span class="cxp-badge cxp-badge-warn mt-1">
                                <i class="fas fa-triangle-exclamation"></i>
                                Bloquea egresos
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="cxp-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-pagar/simulador-caja') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                    <input class="cxp-input" type="search" name="buscar" value="<?= cxp_cash_safe($buscar, '') ?>" placeholder="Buscar por cuenta, folio, compra o proveedor">
                    <select class="cxp-input" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="pagada" <?= $estado === 'pagada' ? 'selected' : '' ?>>Pagadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                    <button class="cxp-btn cxp-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="cxp-panel overflow-hidden">
                <?php if (empty($cuentas)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h2 class="font-black text-lg">No hay cuentas para evaluar</h2>
                        <p class="text-sm text-slate-500 mt-1">Cambia los filtros o genera CxP desde una compra recibida antes de simular egresos.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxp-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Cuenta</th>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-left">Compra</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Saldo</th>
                                    <th class="text-left">Diagnostico</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <?php $esElegible = !empty($cuenta['es_elegible_caja']); ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0)) ?>">
                                                #<?= (int)($cuenta['id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= cxp_cash_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['proveedor_id']) && !empty($cuenta['proveedor_nombre'])): ?>
                                                <a class="font-black text-slate-800 underline" href="<?= url('proveedores/' . (int)$cuenta['proveedor_id']) ?>">
                                                    <?= cxp_cash_safe($cuenta['proveedor_nombre'] ?? null) ?>
                                                </a>
                                                <div class="text-xs text-slate-500"><?= cxp_cash_safe($cuenta['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin proveedor valido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['compra_id'])): ?>
                                                <a class="font-black text-slate-800 underline" href="<?= url('compras/' . (int)$cuenta['compra_id']) ?>">
                                                    #<?= (int)$cuenta['compra_id'] ?>
                                                </a>
                                                <div class="text-xs text-slate-500">
                                                    <?= cxp_cash_safe($cuenta['compra_folio'] ?? null, 'Sin folio') ?>
                                                    · <?= cxp_cash_safe($cuenta['compra_estado'] ?? null, 'Sin estado') ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin compra</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="cxp-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxp_cash_safe($cuenta['estado'] ?? null) ?>
                                            </span>
                                            <div class="text-xs text-slate-500 mt-1">
                                                <?= cxp_cash_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?>
                                            </div>
                                        </td>
                                        <td class="text-right"><?= cxp_cash_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= cxp_cash_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cxp-badge cxp-badge-ok">
                                                    <i class="fas fa-check-circle"></i>
                                                    Elegible
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxp_cash_safe($cuenta['motivo_elegibilidad_caja'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cxp-badge cxp-badge-blocked">
                                                    <i class="fas fa-ban"></i>
                                                    Bloqueada
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxp_cash_safe($cuenta['motivo_bloqueo_caja'] ?? null, 'No elegible.') ?></div>
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
    </section>
</div>
