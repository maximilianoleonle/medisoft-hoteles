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

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxc-op-page {
    --cxc-op-brand: var(--brand-primary, #1f3f46);
    --cxc-op-accent: var(--brand-accent, #b58a3c);
    --cxc-op-line: color-mix(in srgb, var(--cxc-op-brand) 10%, #e5e7eb);
    --cxc-op-soft: color-mix(in srgb, var(--cxc-op-accent) 7%, #f8fafc);
    color: #243142;
}
.cxc-op-page .cxc-op-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxc-op-brand) 90%, #111827), color-mix(in srgb, var(--cxc-op-accent) 54%, #47321f));
    color: #fff;
    padding: 28px;
}
.cxc-op-page .cxc-op-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .78;
    font-weight: 800;
}
.cxc-op-page .cxc-op-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxc-op-page .cxc-op-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.cxc-op-page .cxc-op-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxc-op-page .cxc-op-panel {
    border: 1px solid var(--cxc-op-line);
    background: rgba(255,255,255,.94);
}
.cxc-op-page .cxc-op-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxc-op-line);
    font-weight: 800;
}
.cxc-op-page .cxc-op-btn-primary {
    background: var(--cxc-op-brand);
    border-color: var(--cxc-op-brand);
    color: #fff;
}
.cxc-op-page .cxc-op-btn-muted {
    background: #fff;
    color: #334155;
}
.cxc-op-page .cxc-op-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxc-op-line);
    background: #fff;
    padding: 0 12px;
}
.cxc-op-page .cxc-op-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxc-op-page .cxc-op-table td,
.cxc-op-page .cxc-op-table th {
    border-bottom: 1px solid var(--cxc-op-line);
    padding: 14px 12px;
}
.cxc-op-page .cxc-op-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxc-op-line);
    background: var(--cxc-op-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="cxc-op-page">
    <section class="cxc-op-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxc-op-kicker">CxC operativa / Fase 7B-B</div>
                <h1 class="cxc-op-title">Cuentas por cobrar operativas</h1>
                <p class="cxc-op-subtitle">
                    Listado read-only de la tabla nueva de CxC. No genera cuentas, no cobra, no registra pagos, no toca Caja y no importa historicos.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[340px]">
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Cuentas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Pendientes</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['pendientes'] ?? 0) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Saldo</div>
                    <div class="text-xl font-black"><?= cxc_op_money($resumen['saldo_total'] ?? 0) ?></div>
                </div>
                <div class="cxc-op-stat">
                    <div class="text-xs opacity-75">Vencido</div>
                    <div class="text-xl font-black"><?= cxc_op_money($resumen['saldo_vencido'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="cxc-op-panel p-5">
                <strong>Base CxC operativa no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Aplica primero la migracion 7B-A con backup verificado.</p>
            </div>
        <?php else: ?>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="cxc-op-badge">
                        <i class="fas fa-lock"></i>
                        Solo lectura
                    </span>
                    <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar') ?>">
                        <i class="fas fa-chart-line"></i>
                        Reporte estimado
                    </a>
                    <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar/simulador-caja') ?>">
                        <i class="fas fa-cash-register"></i>
                        Simulador Caja
                    </a>
                </div>
                <span class="text-sm text-slate-500">Consulta operativa de CxC; los cobros con Caja aun no estan habilitados.</span>
            </div>

            <div class="cxc-op-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar/operativas') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                    <input class="cxc-op-input" type="search" name="buscar" value="<?= cxc_op_safe($buscar, '') ?>" placeholder="Buscar por folio, concepto, huesped o factura">
                    <select class="cxc-op-input" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="parcial" <?= $estado === 'parcial' ? 'selected' : '' ?>>Parciales</option>
                        <option value="vencida" <?= $estado === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
                        <option value="liquidada" <?= $estado === 'liquidada' ? 'selected' : '' ?>>Liquidadas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="incobrable" <?= $estado === 'incobrable' ? 'selected' : '' ?>>Incobrables</option>
                    </select>
                    <button class="cxc-op-btn cxc-op-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="cxc-op-panel overflow-hidden">
                <?php if (empty($cuentas)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-table"></i></div>
                        <h2 class="font-black text-lg">CxC operativa vacia</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            No hay cuentas por cobrar operativas que coincidan con los filtros seleccionados.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxc-op-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Cuenta</th>
                                    <th class="text-left">Concepto</th>
                                    <th class="text-left">Huesped</th>
                                    <th class="text-left">Origen</th>
                                    <th class="text-left">Vencimiento</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Saldo</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('cuentas-por-cobrar/operativas/' . (int)($cuenta['id'] ?? 0)) ?>">
                                                #<?= (int)($cuenta['id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= cxc_op_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td><?= cxc_op_safe($cuenta['concepto'] ?? null) ?></td>
                                        <td><?= cxc_op_safe($cuenta['huesped_nombre'] ?? null, 'Sin huesped') ?></td>
                                        <td>
                                            <?= cxc_op_safe($cuenta['origen_tipo'] ?? null) ?>
                                            <?php if (!empty($cuenta['origen_id'])): ?>
                                                <div class="text-xs text-slate-500">#<?= (int)$cuenta['origen_id'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= cxc_op_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></td>
                                        <td>
                                            <span class="cxc-op-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxc_op_safe($cuenta['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= cxc_op_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= cxc_op_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <a class="cxc-op-btn cxc-op-btn-muted" href="<?= url('cuentas-por-cobrar/operativas/' . (int)($cuenta['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </a>
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
