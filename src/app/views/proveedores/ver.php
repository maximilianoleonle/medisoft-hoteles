<?php
$proveedor = $proveedor ?? [];
$resumenCompras = $resumenCompras ?? [];
$comprasRecientes = $comprasRecientes ?? [];
$historialDisponible = $historialDisponible ?? false;

if (!function_exists('prov_view_safe')) {
    function prov_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('prov_view_money')) {
    function prov_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('prov_view_qty')) {
    function prov_view_qty($value)
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}

$proveedorId = (int)($proveedor['id'] ?? 0);
$activo = (int)($proveedor['activo'] ?? 0) === 1;
?>

<style>
.provider-detail-page {
    --prov-brand: var(--brand-primary, #1f3f46);
    --prov-accent: var(--brand-accent, #b58a3c);
    --prov-line: color-mix(in srgb, var(--prov-brand) 10%, #e5e7eb);
    --prov-soft: color-mix(in srgb, var(--prov-accent) 7%, #f8fafc);
    color: #243142;
}
.provider-detail-page .provider-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--prov-brand) 92%, #111827), color-mix(in srgb, var(--prov-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.provider-detail-page .provider-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.provider-detail-page .provider-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.provider-detail-page .provider-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.provider-detail-page .provider-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.provider-detail-page .provider-panel {
    border: 1px solid var(--prov-line);
    background: rgba(255,255,255,.92);
}
.provider-detail-page .provider-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--prov-line);
    font-weight: 800;
}
.provider-detail-page .provider-btn-muted {
    background: #fff;
    color: #334155;
}
.provider-detail-page .provider-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--prov-line);
    background: var(--prov-soft);
    font-size: .78rem;
    font-weight: 800;
}
.provider-detail-page .provider-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.provider-detail-page .provider-table td,
.provider-detail-page .provider-table th {
    border-bottom: 1px solid var(--prov-line);
    padding: 14px 12px;
}
.provider-detail-page .provider-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
</style>

<div class="provider-detail-page">
    <section class="provider-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="provider-kicker">Inventario / Proveedor</div>
                <h1 class="provider-title"><?= prov_view_safe($proveedor['nombre'] ?? null) ?></h1>
                <p class="provider-subtitle">
                    Ficha de solo lectura con contacto e historial de compras del hotel actual.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[320px]">
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Compras</div>
                    <div class="text-2xl font-black"><?= (int)($resumenCompras['compras'] ?? 0) ?></div>
                </div>
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Recibidas</div>
                    <div class="text-2xl font-black"><?= (int)($resumenCompras['recibidas'] ?? 0) ?></div>
                </div>
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Productos</div>
                    <div class="text-2xl font-black"><?= (int)($resumenCompras['productos'] ?? 0) ?></div>
                </div>
                <div class="provider-stat">
                    <div class="text-xs opacity-75">Total recibido</div>
                    <div class="text-xl font-black"><?= prov_view_money($resumenCompras['total_recibido'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="provider-btn provider-btn-muted" href="<?= url('proveedores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="provider-btn provider-btn-muted" href="<?= url('proveedores/' . $proveedorId . '/editar') ?>">
                    <i class="fas fa-pen"></i>
                    Editar datos
                </a>
                <a class="provider-btn provider-btn-muted" href="<?= url('compras/reportes/recibidas?proveedor_id=' . $proveedorId) ?>">
                    <i class="fas fa-chart-column"></i>
                    Reporte
                </a>
            </div>
            <span class="provider-badge">
                <i class="fas <?= $activo ? 'fa-check-circle' : 'fa-pause-circle' ?>"></i>
                <?= $activo ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(360px,420px)] gap-4 mb-4">
            <div class="provider-panel p-5">
                <h2 class="font-black text-lg mb-4">Datos del proveedor</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="provider-meta-label">Nombre comercial</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['nombre'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="provider-meta-label">Razon social</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['razon_social'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="provider-meta-label">RFC</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['rfc'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="provider-meta-label">Telefono</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['telefono'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="provider-meta-label">Correo</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['email'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="provider-meta-label">Actualizado</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['updated_at'] ?? null) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="provider-meta-label">Direccion</div>
                        <div class="font-black mt-1"><?= prov_view_safe($proveedor['direccion'] ?? null) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="provider-meta-label">Notas</div>
                        <div class="mt-1 text-slate-700 whitespace-pre-line"><?= prov_view_safe($proveedor['notas'] ?? null, 'Sin notas') ?></div>
                    </div>
                </div>
            </div>

            <div class="provider-panel p-5">
                <h2 class="font-black text-lg mb-4">Resumen de compras</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Borradores</span>
                        <strong><?= (int)($resumenCompras['borradores'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Canceladas</span>
                        <strong><?= (int)($resumenCompras['canceladas'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Lineas capturadas</span>
                        <strong><?= (int)($resumenCompras['lineas'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Cantidad total</span>
                        <strong><?= prov_view_qty($resumenCompras['cantidad_total'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Total lineas</span>
                        <strong><?= prov_view_money($resumenCompras['total_lineas'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Ultima compra</span>
                        <strong><?= prov_view_safe($resumenCompras['ultima_compra'] ?? null) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Ultima recepcion</span>
                        <strong><?= prov_view_safe($resumenCompras['ultima_recepcion'] ?? null) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="provider-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-black text-lg">Compras recientes</h2>
                <span class="provider-badge">
                    <i class="fas fa-lock"></i>
                    Solo lectura
                </span>
            </div>

            <?php if (!$historialDisponible): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-file-circle-exclamation"></i></div>
                    <h3 class="font-black text-lg">Historial no disponible</h3>
                    <p class="text-sm text-slate-500 mt-1">La tabla de compras minimas no esta disponible en esta instalacion.</p>
                </div>
            <?php elseif (empty($comprasRecientes)): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-receipt"></i></div>
                    <h3 class="font-black text-lg">Sin compras registradas</h3>
                    <p class="text-sm text-slate-500 mt-1">Este proveedor aun no tiene compras en el hotel actual.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="provider-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Compra</th>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Estado</th>
                                <th class="text-right">Lineas</th>
                                <th class="text-right">Productos</th>
                                <th class="text-right">Cantidad</th>
                                <th class="text-right">Movimientos</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comprasRecientes as $compra): ?>
                                <tr>
                                    <td>
                                        <a class="font-black text-slate-800 underline" href="<?= url('compras/' . (int)($compra['id'] ?? 0)) ?>">
                                            #<?= (int)($compra['id'] ?? 0) ?>
                                        </a>
                                        <div class="text-xs text-slate-500"><?= prov_view_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                                    </td>
                                    <td>
                                        <div><?= prov_view_safe($compra['fecha_recepcion'] ?? null, 'Sin recepcion') ?></div>
                                        <div class="text-xs text-slate-500">Compra <?= prov_view_safe($compra['fecha_compra'] ?? null) ?></div>
                                    </td>
                                    <td>
                                        <span class="provider-badge">
                                            <i class="fas fa-circle-dot"></i>
                                            <?= prov_view_safe($compra['estado'] ?? null) ?>
                                        </span>
                                    </td>
                                    <td class="text-right"><?= (int)($compra['detalle_count'] ?? 0) ?></td>
                                    <td class="text-right"><?= (int)($compra['producto_count'] ?? 0) ?></td>
                                    <td class="text-right"><?= prov_view_qty($compra['cantidad_total'] ?? 0) ?></td>
                                    <td class="text-right"><?= (int)($compra['movimientos_count'] ?? 0) ?></td>
                                    <td class="text-right font-black"><?= prov_view_money($compra['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
