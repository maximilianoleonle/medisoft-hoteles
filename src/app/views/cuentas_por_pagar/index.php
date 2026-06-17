<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxp_safe')) {
    function cxp_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_money')) {
    function cxp_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
?>

<style>
.cxp-page {
    --cxp-brand: var(--brand-primary, #1f3f46);
    --cxp-accent: var(--brand-accent, #b58a3c);
    --cxp-line: color-mix(in srgb, var(--cxp-brand) 10%, #e5e7eb);
    --cxp-soft: color-mix(in srgb, var(--cxp-accent) 7%, #f8fafc);
    color: #243142;
}
.cxp-page .cxp-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxp-brand) 92%, #111827), color-mix(in srgb, var(--cxp-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.cxp-page .cxp-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.cxp-page .cxp-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxp-page .cxp-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.cxp-page .cxp-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxp-page .cxp-panel {
    border: 1px solid var(--cxp-line);
    background: rgba(255,255,255,.92);
}
.cxp-page .cxp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxp-line);
    font-weight: 800;
}
.cxp-page .cxp-btn-primary {
    background: var(--cxp-brand);
    border-color: var(--cxp-brand);
    color: #fff;
}
.cxp-page .cxp-btn-muted {
    background: #fff;
    color: #334155;
}
.cxp-page .cxp-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxp-line);
    background: #fff;
    padding: 0 12px;
}
.cxp-page .cxp-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxp-page .cxp-table td,
.cxp-page .cxp-table th {
    border-bottom: 1px solid var(--cxp-line);
    padding: 14px 12px;
}
.cxp-page .cxp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxp-line);
    background: var(--cxp-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="cxp-page">
    <section class="cxp-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxp-kicker">Compras / Finanzas operativas</div>
                <h1 class="cxp-title">Cuentas por pagar</h1>
                <p class="cxp-subtitle">
                    Cartera de proveedores en modo lectura. No registra pagos, no descuenta caja y no modifica compras.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[340px]">
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Cuentas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Pendientes</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['pendientes'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Saldo</div>
                    <div class="text-xl font-black"><?= cxp_money($resumen['saldo_total'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Vencido</div>
                    <div class="text-xl font-black"><?= cxp_money($resumen['saldo_vencido'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="cxp-panel p-5">
                <strong>Modulo no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Aplica la migracion Fase 3B solo despues de backup para habilitar esta vista.</p>
            </div>
        <?php else: ?>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap gap-2">
                    <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar/generacion-preview') ?>">
                        <i class="fas fa-search"></i>
                        Preview generacion desde compras
                    </a>
                    <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar/simulador-caja') ?>">
                        <i class="fas fa-cash-register"></i>
                        Simulador Caja
                    </a>
                </div>
                <span class="cxp-badge">
                    <i class="fas fa-lock"></i>
                    Sin generacion automatica
                </span>
            </div>

            <div class="cxp-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-pagar') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                    <input class="cxp-input" type="search" name="buscar" value="<?= cxp_safe($buscar, '') ?>" placeholder="Buscar por proveedor, folio o descripcion">
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
                        <h2 class="font-black text-lg">No hay cuentas por pagar</h2>
                        <p class="text-sm text-slate-500 mt-1">La estructura esta lista, pero aun no se generan compromisos desde compras.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxp-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Cuenta</th>
                                    <th class="text-left">Proveedor</th>
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
                                            <a class="font-black text-slate-800 underline" href="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0)) ?>">
                                                #<?= (int)($cuenta['id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= cxp_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td><?= cxp_safe($cuenta['proveedor_nombre'] ?? null) ?></td>
                                        <td><?= cxp_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></td>
                                        <td>
                                            <span class="cxp-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxp_safe($cuenta['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= cxp_money($cuenta['total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= cxp_money($cuenta['saldo'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <a class="cxp-btn cxp-btn-muted" href="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0)) ?>">
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
