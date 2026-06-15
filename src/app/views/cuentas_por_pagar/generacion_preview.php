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

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'recibida');
?>

<style>
.cxp-preview-page {
    --cxp-brand: var(--brand-primary, #1f3f46);
    --cxp-accent: var(--brand-accent, #b58a3c);
    --cxp-line: color-mix(in srgb, var(--cxp-brand) 10%, #e5e7eb);
    --cxp-soft: color-mix(in srgb, var(--cxp-accent) 7%, #f8fafc);
    color: #243142;
}
.cxp-preview-page .cxp-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxp-brand) 92%, #111827), color-mix(in srgb, var(--cxp-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.cxp-preview-page .cxp-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.cxp-preview-page .cxp-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxp-preview-page .cxp-subtitle {
    margin-top: 8px;
    max-width: 54rem;
    color: rgba(255,255,255,.86);
}
.cxp-preview-page .cxp-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxp-preview-page .cxp-panel {
    border: 1px solid var(--cxp-line);
    background: rgba(255,255,255,.92);
}
.cxp-preview-page .cxp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxp-line);
    font-weight: 800;
}
.cxp-preview-page .cxp-btn-primary {
    background: var(--cxp-brand);
    border-color: var(--cxp-brand);
    color: #fff;
}
.cxp-preview-page .cxp-btn-muted {
    background: #fff;
    color: #334155;
}
.cxp-preview-page .cxp-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxp-line);
    background: #fff;
    padding: 0 12px;
}
.cxp-preview-page .cxp-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxp-preview-page .cxp-table td,
.cxp-preview-page .cxp-table th {
    border-bottom: 1px solid var(--cxp-line);
    padding: 14px 12px;
    vertical-align: top;
}
.cxp-preview-page .cxp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxp-line);
    background: var(--cxp-soft);
    font-size: .78rem;
    font-weight: 800;
}
.cxp-preview-page .cxp-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.cxp-preview-page .cxp-badge-blocked {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
</style>

<div class="cxp-preview-page">
    <section class="cxp-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxp-kicker">Compras / CxP</div>
                <h1 class="cxp-title">Preview de generacion CxP</h1>
                <p class="cxp-subtitle">
                    Simulador de solo lectura para detectar compras recibidas que podrian generar una cuenta por pagar. No crea registros, no toca pagos y no afecta Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 min-w-[380px]">
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Filas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Recibidas</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['recibidas'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Elegibles</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['elegibles'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Con CxP</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['con_cxp'] ?? 0) ?></div>
                </div>
                <div class="cxp-stat">
                    <div class="text-xs opacity-75">Total elegible</div>
                    <div class="text-xl font-black"><?= cxp_preview_money($resumen['total_elegible'] ?? 0) ?></div>
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
                <a class="cxp-btn cxp-btn-muted" href="<?= url('compras/reportes/recibidas') ?>">
                    <i class="fas fa-chart-line"></i>
                    Compras recibidas
                </a>
            </div>
            <span class="cxp-badge">
                <i class="fas fa-lock"></i>
                Solo GET
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="cxp-panel p-5">
                <strong>Preview no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Revisa que existan compras, proveedores y CxP base antes de usar este simulador.</p>
            </div>
        <?php else: ?>
            <div class="cxp-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-pagar/generacion-preview') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3">
                    <input class="cxp-input" type="search" name="buscar" value="<?= cxp_preview_safe($buscar, '') ?>" placeholder="Buscar por compra, folio o proveedor">
                    <select class="cxp-input" name="estado">
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todas</option>
                    </select>
                    <button class="cxp-btn cxp-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="cxp-panel overflow-hidden">
                <?php if (empty($compras)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-search"></i></div>
                        <h2 class="font-black text-lg">No hay compras para evaluar</h2>
                        <p class="text-sm text-slate-500 mt-1">Ajusta los filtros o revisa compras recibidas del hotel actual.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxp-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Compra</th>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-left">Hotel</th>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-left">CxP</th>
                                    <th class="text-left">Diagnostico</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($compras as $compra): ?>
                                    <?php $esElegible = !empty($compra['es_elegible']); ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('compras/' . (int)($compra['compra_id'] ?? 0)) ?>">
                                                #<?= (int)($compra['compra_id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= cxp_preview_safe($compra['compra_folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($compra['proveedor_id'])): ?>
                                                <a class="font-black text-slate-800 underline" href="<?= url('proveedores/' . (int)$compra['proveedor_id']) ?>">
                                                    <?= cxp_preview_safe($compra['proveedor_nombre'] ?? null) ?>
                                                </a>
                                                <div class="text-xs text-slate-500"><?= cxp_preview_safe($compra['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin proveedor</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="font-black"><?= cxp_preview_safe($compra['hotel_nombre'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500">hotel_id <?= (int)($compra['hotel_id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <div><?= cxp_preview_safe($compra['fecha_recepcion'] ?? null, 'Sin recepcion') ?></div>
                                            <div class="text-xs text-slate-500">Compra <?= cxp_preview_safe($compra['fecha_compra'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <span class="cxp-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxp_preview_safe($compra['compra_estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-black"><?= cxp_preview_money($compra['total'] ?? 0) ?></td>
                                        <td>
                                            <?php if (!empty($compra['cxp_id'])): ?>
                                                <a class="font-black text-slate-800 underline" href="<?= url('cuentas-por-pagar/' . (int)$compra['cxp_id']) ?>">
                                                    CxP #<?= (int)$compra['cxp_id'] ?>
                                                </a>
                                                <div class="text-xs text-slate-500">
                                                    <?= cxp_preview_safe($compra['cxp_estado'] ?? null) ?> - saldo <?= cxp_preview_money($compra['cxp_saldo'] ?? 0) ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin CxP</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($esElegible): ?>
                                                <span class="cxp-badge cxp-badge-ok">
                                                    <i class="fas fa-check-circle"></i>
                                                    Elegible
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxp_preview_safe($compra['motivo_elegibilidad'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cxp-badge cxp-badge-blocked">
                                                    <i class="fas fa-ban"></i>
                                                    Bloqueada
                                                </span>
                                                <div class="text-xs text-slate-500 mt-1"><?= cxp_preview_safe($compra['motivo_bloqueo'] ?? null, 'No elegible para generacion.') ?></div>
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
