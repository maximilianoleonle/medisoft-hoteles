<?php
$catalogos = $catalogos ?? ['proveedores' => [], 'productos' => []];
$reporte = $reporte ?? [
    'filtros' => [],
    'resumen' => [],
    'lineas' => [],
    'por_proveedor' => [],
    'por_producto' => [],
];
$proveedores = $catalogos['proveedores'] ?? [];
$productos = $catalogos['productos'] ?? [];
$filtros = $reporte['filtros'] ?? [];
$resumen = $reporte['resumen'] ?? [];
$lineas = $reporte['lineas'] ?? [];
$porProveedor = $reporte['por_proveedor'] ?? [];
$porProducto = $reporte['por_producto'] ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$errorTecnico = $errorTecnico ?? null;

if (!function_exists('comp_report_safe')) {
    function comp_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('comp_report_money')) {
    function comp_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('comp_report_qty')) {
    function comp_report_qty($value)
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}

$estado = (string)($filtros['estado'] ?? 'recibida');
$proveedorSeleccionado = (string)($filtros['proveedor_id'] ?? '');
$productoSeleccionado = (string)($filtros['producto_id'] ?? '');
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
?>

<style>
.purchase-report-page {
    --purchase-brand: var(--brand-primary, #1f3f46);
    --purchase-accent: var(--brand-accent, #b58a3c);
    --purchase-line: color-mix(in srgb, var(--purchase-brand) 10%, #e5e7eb);
    --purchase-soft: color-mix(in srgb, var(--purchase-accent) 7%, #f8fafc);
    color: #243142;
}
.purchase-report-page .purchase-report-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--purchase-brand) 92%, #111827), color-mix(in srgb, var(--purchase-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.purchase-report-page .purchase-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.purchase-report-page .purchase-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.purchase-report-page .purchase-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.purchase-report-page .purchase-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.purchase-report-page .purchase-panel {
    border: 1px solid var(--purchase-line);
    background: rgba(255,255,255,.92);
}
.purchase-report-page .purchase-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--purchase-line);
    font-weight: 800;
}
.purchase-report-page .purchase-btn-primary {
    background: var(--purchase-brand);
    border-color: var(--purchase-brand);
    color: #fff;
}
.purchase-report-page .purchase-btn-muted {
    background: #fff;
    color: #334155;
}
.purchase-report-page .purchase-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--purchase-line);
    background: #fff;
    padding: 0 12px;
}
.purchase-report-page .purchase-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.purchase-report-page .purchase-table td,
.purchase-report-page .purchase-table th {
    border-bottom: 1px solid var(--purchase-line);
    padding: 12px;
}
.purchase-report-page .purchase-badge {
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

<div class="purchase-report-page">
    <section class="purchase-report-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="purchase-kicker">Inventario / Reportes</div>
                <h1 class="purchase-title">Compras recibidas</h1>
                <p class="purchase-subtitle">
                    Historial de solo lectura por proveedor y producto. No registra pagos, caja ni CxP.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-2 min-w-[360px]">
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Compras</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['compras'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Proveedores</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['proveedores'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Productos</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['productos'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Cantidad</div>
                    <div class="text-xl font-black"><?= comp_report_qty($resumen['cantidad_total'] ?? 0) ?></div>
                </div>
                <div class="purchase-stat">
                    <div class="text-xs opacity-75">Total</div>
                    <div class="text-xl font-black"><?= comp_report_money($resumen['total_lineas'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <a class="purchase-btn purchase-btn-muted" href="<?= url('compras?estado=recibida') ?>">
                <i class="fas fa-arrow-left"></i>
                Volver a compras
            </a>
            <span class="purchase-badge">
                <i class="fas fa-lock"></i>
                Solo lectura
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="purchase-panel p-5">
                <strong>Reporte no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1"><?= comp_report_safe($errorTecnico, 'Revisa la migracion minima de compras y el health checker.') ?></p>
            </div>
        <?php else: ?>
            <div class="purchase-panel p-4 mb-4">
                <form method="GET" action="<?= url('compras/reportes/recibidas') ?>" class="grid grid-cols-1 md:grid-cols-6 gap-3">
                    <select class="purchase-input" name="proveedor_id">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <?php $id = (string)($proveedor['id'] ?? ''); ?>
                            <option value="<?= (int)$id ?>" <?= $proveedorSeleccionado === $id ? 'selected' : '' ?>>
                                <?= comp_report_safe($proveedor['nombre'] ?? null) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="purchase-input" name="producto_id">
                        <option value="">Todos los productos</option>
                        <?php foreach ($productos as $producto): ?>
                            <?php $id = (string)($producto['id'] ?? ''); ?>
                            <option value="<?= (int)$id ?>" <?= $productoSeleccionado === $id ? 'selected' : '' ?>>
                                <?= comp_report_safe(trim((string)($producto['codigo'] ?? '') . ' ' . (string)($producto['nombre'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input class="purchase-input" type="date" name="fecha_inicio" value="<?= comp_report_safe($fechaInicio, '') ?>">
                    <input class="purchase-input" type="date" name="fecha_fin" value="<?= comp_report_safe($fechaFin, '') ?>">
                    <select class="purchase-input" name="estado">
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="purchase-btn purchase-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mb-4">
                <div class="purchase-panel overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="font-black text-lg">Totales por proveedor</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="purchase-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Proveedor</th>
                                    <th class="text-right">Compras</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porProveedor)): ?>
                                    <tr><td colspan="4" class="text-center text-slate-500">Sin datos para los filtros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porProveedor as $fila): ?>
                                        <tr>
                                            <td class="font-black"><?= comp_report_safe($fila['proveedor_nombre'] ?? null) ?></td>
                                            <td class="text-right"><?= (int)($fila['compras'] ?? 0) ?></td>
                                            <td class="text-right"><?= comp_report_qty($fila['cantidad_total'] ?? 0) ?></td>
                                            <td class="text-right font-black"><?= comp_report_money($fila['total_lineas'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="purchase-panel overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-200">
                        <h2 class="font-black text-lg">Totales por producto</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="purchase-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Producto</th>
                                    <th class="text-right">Compras</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porProducto)): ?>
                                    <tr><td colspan="4" class="text-center text-slate-500">Sin datos para los filtros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porProducto as $fila): ?>
                                        <tr>
                                            <td>
                                                <div class="font-black"><?= comp_report_safe($fila['producto_nombre'] ?? null) ?></div>
                                                <div class="text-xs text-slate-500"><?= comp_report_safe($fila['producto_codigo'] ?? null, 'Sin codigo') ?></div>
                                            </td>
                                            <td class="text-right"><?= (int)($fila['compras'] ?? 0) ?></td>
                                            <td class="text-right">
                                                <?= comp_report_qty($fila['cantidad_total'] ?? 0) ?>
                                                <?= comp_report_safe($fila['unidad_medida'] ?? null, '') ?>
                                            </td>
                                            <td class="text-right font-black"><?= comp_report_money($fila['total_lineas'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="purchase-panel overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-black text-lg">Lineas de compra</h2>
                    <span class="text-xs font-bold text-slate-500">Maximo 300 lineas</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="purchase-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Compra</th>
                                <th class="text-left">Proveedor</th>
                                <th class="text-left">Producto</th>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Estado</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Costo</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-left">Movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lineas)): ?>
                                <tr><td colspan="9" class="text-center text-slate-500">Sin lineas para los filtros.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lineas as $linea): ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('compras/' . (int)($linea['compra_id'] ?? 0)) ?>">
                                                #<?= (int)($linea['compra_id'] ?? 0) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= comp_report_safe($linea['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td><?= comp_report_safe($linea['proveedor_nombre'] ?? null) ?></td>
                                        <td>
                                            <div class="font-black"><?= comp_report_safe($linea['producto_nombre'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= comp_report_safe($linea['producto_codigo'] ?? null, 'Sin codigo') ?></div>
                                        </td>
                                        <td>
                                            <div><?= comp_report_safe($linea['fecha_reporte'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500">Compra <?= comp_report_safe($linea['fecha_compra'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <span class="purchase-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= comp_report_safe($linea['estado'] ?? null) ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <?= comp_report_qty($linea['cantidad'] ?? 0) ?>
                                            <?= comp_report_safe($linea['unidad_medida'] ?? null, '') ?>
                                        </td>
                                        <td class="text-right"><?= comp_report_money($linea['costo_unitario'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= comp_report_money($linea['subtotal'] ?? 0) ?></td>
                                        <td>
                                            <?php if (!empty($linea['movimiento_inventario_id'])): ?>
                                                <div class="font-black">#<?= (int)$linea['movimiento_inventario_id'] ?> <?= comp_report_safe($linea['movimiento_tipo'] ?? null) ?></div>
                                                <div class="text-xs text-slate-500"><?= comp_report_safe($linea['movimiento_created_at'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="text-slate-400">Sin movimiento</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
