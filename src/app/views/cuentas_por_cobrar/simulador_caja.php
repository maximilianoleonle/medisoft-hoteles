<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;
$tipoCobroDisponible = $tipoCobroDisponible ?? false;

if (!function_exists('cxc_cash_safe')) {
    function cxc_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_cash_money')) {
    function cxc_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxc-cash-page {
    --cxc-cash-brand: var(--brand-primary, #1f3f46);
    --cxc-cash-accent: var(--brand-accent, #b58a3c);
    --cxc-cash-line: color-mix(in srgb, var(--cxc-cash-brand) 10%, #e5e7eb);
    --cxc-cash-soft: color-mix(in srgb, var(--cxc-cash-accent) 7%, #f8fafc);
    color: #243142;
}
.cxc-cash-page .cxc-cash-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxc-cash-brand) 92%, #111827), color-mix(in srgb, var(--cxc-cash-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.cxc-cash-page .cxc-cash-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.cxc-cash-page .cxc-cash-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxc-cash-page .cxc-cash-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.cxc-cash-page .cxc-cash-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxc-cash-page .cxc-cash-panel {
    border: 1px solid var(--cxc-cash-line);
    background: rgba(255,255,255,.92);
}
.cxc-cash-page .cxc-cash-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxc-cash-line);
    font-weight: 800;
}
.cxc-cash-page .cxc-cash-btn-primary {
    background: var(--cxc-cash-brand);
    border-color: var(--cxc-cash-brand);
    color: #fff;
}
.cxc-cash-page .cxc-cash-btn-muted {
    background: #fff;
    color: #334155;
}
.cxc-cash-page .cxc-cash-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxc-cash-line);
    background: #fff;
    padding: 0 12px;
}
.cxc-cash-page .cxc-cash-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxc-cash-page .cxc-cash-table td,
.cxc-cash-page .cxc-cash-table th {
    border-bottom: 1px solid var(--cxc-cash-line);
    padding: 14px 12px;
    vertical-align: top;
}
.cxc-cash-page .cxc-cash-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxc-cash-line);
    background: var(--cxc-cash-soft);
    font-size: .78rem;
    font-weight: 800;
}
.cxc-cash-page .cxc-cash-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.cxc-cash-page .cxc-cash-badge-blocked {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.cxc-cash-page .cxc-cash-badge-warn {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
</style>

<div class="cxc-cash-page">
    <section class="cxc-cash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxc-cash-kicker">CxC / Caja</div>
                <h1 class="cxc-cash-title">Simulador de cobro CxC</h1>
                <p class="cxc-cash-subtitle">
                    Diagnostico previo de cuentas por cobrar contra Caja. Esta pantalla no registra cobros, no cambia saldos y no crea movimientos.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="cxc-cash-stat">
                    <div class="text-xs opacity-75">Cuentas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxc-cash-stat">
                    <div class="text-xs opacity-75">Elegibles</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['elegibles'] ?? 0) ?></div>
                </div>
                <div class="cxc-cash-stat">
                    <div class="text-xs opacity-75">Saldo elegible</div>
                    <div class="text-xl font-black"><?= cxc_cash_money($resumen['saldo_elegible'] ?? 0) ?></div>
                </div>
                <div class="cxc-cash-stat">
                    <div class="text-xs opacity-75">Corte abierto</div>
                    <div class="text-xl font-black"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="cxc-cash-btn cxc-cash-btn-muted" href="<?= url('cuentas-por-cobrar/operativas') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a CxC
                </a>
                <a class="cxc-cash-btn cxc-cash-btn-muted" href="<?= url('caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Ver Caja
                </a>
            </div>
            <span class="cxc-cash-badge">
                <i class="fas fa-lock"></i>
                Solo GET
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="cxc-cash-panel p-5">
                <strong>Simulador no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas base de CxC o Caja para evaluar cobros de forma segura.</p>
            </div>
        <?php else: ?>
            <div class="cxc-cash-panel p-5 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Corte</div>
                        <div class="font-black mt-1"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Caja</div>
                        <div class="font-black mt-1"><?= cxc_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div>
                        <div class="text-xs text-slate-500"><?= cxc_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicacion') ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Apertura</div>
                        <div class="font-black mt-1"><?= cxc_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Tipo COBRO</div>
                        <?php if ($tipoCobroDisponible): ?>
                            <span class="cxc-cash-badge cxc-cash-badge-ok mt-1">
                                <i class="fas fa-check-circle"></i>
                                Disponible
                            </span>
                        <?php else: ?>
                            <span class="cxc-cash-badge cxc-cash-badge-warn mt-1">
                                <i class="fas fa-triangle-exclamation"></i>
                                Pendiente
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-wide text-slate-500 font-black">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="cxc-cash-badge cxc-cash-badge-ok mt-1">
                                <i class="fas fa-check-circle"></i>
                                Abierto
                            </span>
                        <?php else: ?>
                            <span class="cxc-cash-badge cxc-cash-badge-warn mt-1">
                                <i class="fas fa-triangle-exclamation"></i>
                                Bloquea cobros
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="cxc-cash-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar/simulador-caja') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                    <input class="cxc-cash-input" type="search" name="buscar" value="<?= cxc_cash_safe($buscar, '') ?>" placeholder="Buscar por cuenta, folio, reservacion, huesped o factura">
                    <select class="cxc-cash-input" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="liquidada" <?= $estado === 'liquidada' ? 'selected' : '' ?>>Liquidadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="incobrable" <?= $estado === 'incobrable' ? 'selected' : '' ?>>Incobrables</option>
                    </select>
                    <button class="cxc-cash-btn cxc-cash-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="cxc-cash-panel overflow-hidden">
                <?php if (empty($cuentas)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-file-invoice-dollar"></i></div>
                        <h2 class="font-black text-lg">No hay cuentas para evaluar</h2>
                        <p class="text-sm text-slate-500 mt-1">Cambia los filtros o genera una CxC desde una reservacion elegible.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxc-cash-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Cuenta</th>
                                    <th class="text-left">Huesped</th>
                                    <th class="text-left">Origen</th>
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
                                            <a class="font-black text-slate-800 underline" href="<?= url('cuentas-por-cobrar/operativas/' . (int)($cuenta['id'] ?? 0)) ?>">
                                                #<?= (int)($cuenta['id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= cxc_cash_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($cuenta['huesped_id']) && !empty($cuenta['huesped_nombre'])): ?>
                                                <a class="font-black text-slate-800 underline" href="<?= url('huespedes/' . (int)$cuenta['huesped_id']) ?>">
                                                    <?= cxc_cash_safe($cuenta['huesped_nombre'] ?? null) ?>
                                                </a>
                                                <div class="text-xs text-slate-500"><?= cxc_cash_safe($cuenta['huesped_telefono'] ?? null, 'Sin telefono') ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin huesped valido</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= cxc_cash_safe($cuenta['origen_tipo'] ?? null) ?>
                                            <?php if (!empty($cuenta['origen_id'])): ?>
                                                <div class="text-xs text-slate-500">#<?= (int)$cuenta['origen_id'] ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($cuenta['reservacion_id'])): ?>
                                                <div class="text-xs text-slate-500">Reservacion #<?= (int)$cuenta['reservacion_id'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="cxc-cash-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxc_cash_safe($cuenta['estado'] ?? null) ?>
                                            </span>
                                            <div class="text-xs text-slate-500 mt-1">
                                                <?= cxc_cash_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?>
                                            </div>
                                        </td>
                                        <td class="text-right"><?= cxc_cash_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= cxc_cash_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cxc-cash-badge cxc-cash-badge-ok">
                                                    <i class="fas fa-check-circle"></i>
                                                    Elegible
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxc_cash_safe($cuenta['motivo_elegibilidad_caja'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cxc-cash-badge cxc-cash-badge-blocked">
                                                    <i class="fas fa-ban"></i>
                                                    Bloqueada
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxc_cash_safe($cuenta['motivo_bloqueo_caja'] ?? null, 'No elegible.') ?></div>
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
