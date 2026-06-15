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

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'borrador');
?>

<style>
.purchases-page {
    --purchase-brand: var(--brand-primary, #1f3f46);
    --purchase-accent: var(--brand-accent, #b58a3c);
    --purchase-line: color-mix(in srgb, var(--purchase-brand) 10%, #e5e7eb);
    --purchase-soft: color-mix(in srgb, var(--purchase-accent) 7%, #f8fafc);
    color: #243142;
}
.purchases-page .purchase-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--purchase-brand) 92%, #111827), color-mix(in srgb, var(--purchase-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.purchases-page .purchase-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.purchases-page .purchase-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.purchases-page .purchase-subtitle {
    margin-top: 8px;
    max-width: 48rem;
    color: rgba(255,255,255,.86);
}
.purchases-page .purchase-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.purchases-page .purchase-panel {
    border: 1px solid var(--purchase-line);
    background: rgba(255,255,255,.9);
}
.purchases-page .purchase-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--purchase-line);
    font-weight: 800;
}
.purchases-page .purchase-btn-primary {
    background: var(--purchase-brand);
    border-color: var(--purchase-brand);
    color: #fff;
}
.purchases-page .purchase-btn-muted {
    background: #fff;
    color: #334155;
}
.purchases-page .purchase-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--purchase-line);
    background: #fff;
    padding: 0 12px;
}
.purchases-page .purchase-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.purchases-page .purchase-table td,
.purchases-page .purchase-table th {
    border-bottom: 1px solid var(--purchase-line);
    padding: 14px 12px;
}
.purchases-page .purchase-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--purchase-line);
    background: var(--purchase-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="purchases-page">
    <section class="purchase-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="purchase-kicker">Inventario</div>
                <h1 class="purchase-title">Compras</h1>
                <p class="purchase-subtitle">
                    Captura minima en borrador. La recepcion de inventario, pagos, caja y cuentas por pagar permanecen fuera de esta pantalla.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Borradores</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['borradores'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Recibidas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['recibidas'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">En borrador</div>
                    <div class="text-xl font-black"><?= comp_money($resumen['total_borrador'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="purchase-panel p-5">
                <strong>Compras no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1"><?= comp_safe($errorTecnico, 'Revisa la migracion minima de compras y el health checker.') ?></p>
            </div>
        <?php else: ?>
            <div class="purchase-panel p-4 mb-4">
                <form method="GET" action="<?= url('compras') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_170px_auto_auto] gap-3">
                    <input class="purchase-input" type="search" name="buscar" value="<?= comp_safe($buscar, '') ?>" placeholder="Buscar por folio o proveedor">
                    <select class="purchase-input" name="estado">
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="purchase-btn purchase-btn-muted" type="submit">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
                    <a class="purchase-btn purchase-btn-primary" href="<?= url('compras/crear') ?>">
                        <i class="fas fa-plus"></i>
                        Nuevo
                    </a>
                </form>
            </div>

            <div class="purchase-panel overflow-hidden">
                <?php if (empty($compras)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-clipboard-list"></i></div>
                        <h2 class="font-black text-lg">No hay compras en esta vista</h2>
                        <p class="text-sm text-slate-500 mt-1">Crea un borrador o cambia los filtros.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="purchase-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Compra</th>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Lineas</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compras as $compra): ?>
                                    <tr>
                                        <td>
                                            <div class="font-black text-slate-800">#<?= (int)($compra['id'] ?? 0) ?></div>
                                            <div class="text-xs text-slate-500"><?= comp_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td><?= comp_safe($compra['proveedor_nombre'] ?? null) ?></td>
                                        <td><?= comp_safe($compra['fecha_compra'] ?? null) ?></td>
                                        <td>
                                            <span class="purchase-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= comp_safe($compra['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= (int)($compra['detalle_count'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= comp_money($compra['total'] ?? 0) ?></td>
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
