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

if (!function_exists('comp_report_estado_meta')) {
    function comp_report_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'borrador'  => ['Borrador', 'is-borrador', 'fa-pen-ruler'],
            'recibida'  => ['Recibida', 'is-recibida', 'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
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
    --cp-brand: var(--brand-primary, #1B2746);
    --cp-brand-2: var(--brand-secondary, #0F172A);
    --cp-gold: var(--brand-accent, #BD9441);
    --cp-gold-soft: color-mix(in srgb, var(--cp-gold) 15%, #FFFFFF);
    --cp-gold-line: color-mix(in srgb, var(--cp-gold) 42%, #E4D4B0);
    --cp-gold-ink: color-mix(in srgb, var(--cp-gold) 72%, #000);
    --cp-ivory: #F6F2EA;
    --cp-ivory-2: #FBF8F2;
    --cp-surface: #FFFFFF;
    --cp-surface-warm: #FCFAF5;
    --cp-border: color-mix(in srgb, var(--cp-brand) 7%, #E7E1D4);
    --cp-ring: color-mix(in srgb, var(--cp-gold) 32%, transparent);
    --cp-text: #171717;
    --cp-muted: #667085;
    --cp-heading: #111827;
    --cp-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cp-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cp-success: #1E9E63; --cp-success-bg: #E7F4EC;
    --cp-warning: #C2841C; --cp-warning-bg: #FAF0DC;
    --cp-danger: #B4392B; --cp-danger-bg: #F8EAE5;
    min-height: 100%;
    color: var(--cp-text);
    font-family: var(--cp-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cp-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cp-ivory-2), var(--cp-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.purchase-report-page .cp-shell { display: grid; gap: 14px; }
.purchase-report-page .cp-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.purchase-report-page .cp-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cp-gold), var(--cp-brand) 54%, color-mix(in srgb, var(--cp-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cp-brand) 72%, transparent);
}
.purchase-report-page .cp-kicker { margin: 0 0 2px; color: var(--cp-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.purchase-report-page .cp-title { margin: 0; font-family: var(--cp-serif); color: var(--cp-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.purchase-report-page .cp-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--cp-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.purchase-report-page .cp-stats { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.purchase-report-page .cp-stat { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.purchase-report-page .cp-stat-label { color: var(--cp-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.purchase-report-page .cp-stat-value { margin-top: 2px; font-family: var(--cp-serif); font-size: 1.5rem; font-weight: 700; line-height: 1.1; color: var(--cp-heading); }

.purchase-report-page .cp-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.purchase-report-page .cp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.purchase-report-page .cp-btn:hover { transform: translateY(-1px); }
.purchase-report-page .cp-btn-brand { background: linear-gradient(135deg, var(--cp-brand), var(--cp-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--cp-brand) 60%, transparent); }
.purchase-report-page .cp-btn-muted { background: var(--cp-surface); border-color: var(--cp-border); color: var(--cp-muted); }

.purchase-report-page .cp-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.purchase-report-page .cp-badge.is-borrador { color: color-mix(in srgb, var(--cp-warning) 82%, #000); background: var(--cp-warning-bg); border-color: color-mix(in srgb, var(--cp-warning) 28%, #fff); }
.purchase-report-page .cp-badge.is-recibida { color: color-mix(in srgb, var(--cp-success) 78%, #000); background: var(--cp-success-bg); border-color: color-mix(in srgb, var(--cp-success) 26%, #fff); }
.purchase-report-page .cp-badge.is-cancelada { color: color-mix(in srgb, var(--cp-danger) 82%, #000); background: var(--cp-danger-bg); border-color: color-mix(in srgb, var(--cp-danger) 26%, #fff); }
.purchase-report-page .cp-badge.is-soft { color: var(--cp-muted); background: var(--cp-surface-warm); border-color: var(--cp-border); }

.purchase-report-page .cp-panel { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.purchase-report-page .cp-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 15px 18px; border-bottom: 1px solid var(--cp-border); }
.purchase-report-page .cp-panel-title { font-family: var(--cp-serif); font-size: 1.35rem; font-weight: 700; color: var(--cp-heading); }
.purchase-report-page .cp-panel-hint { font-size: .72rem; color: var(--cp-muted); font-weight: 600; }

.purchase-report-page .cp-filter-form { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; align-items: center; }
.purchase-report-page .cp-control { width: 100%; min-height: 40px; border: 1px solid var(--cp-border); background: var(--cp-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--cp-text); font-weight: 600; font-size: .86rem; transition: border-color .16s ease, box-shadow .16s ease; }
.purchase-report-page .cp-control:focus { border-color: var(--cp-gold); box-shadow: 0 0 0 3px var(--cp-ring); outline: none; }
.purchase-report-page select.cp-control { cursor: pointer; }

.purchase-report-page .cp-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.purchase-report-page .cp-table thead { background: var(--cp-surface-warm); border-bottom: 1px solid var(--cp-border); }
.purchase-report-page .cp-table th { padding: 11px 14px; color: var(--cp-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.purchase-report-page .cp-table th.is-end, .purchase-report-page .cp-table td.is-end { text-align: right; }
.purchase-report-page .cp-table td { padding: 12px 14px; border-bottom: 1px solid var(--cp-border); vertical-align: middle; }
.purchase-report-page .cp-table tbody tr:last-child td { border-bottom: 0; }
.purchase-report-page .cp-table tbody tr { transition: background .16s ease; }
.purchase-report-page .cp-table tbody tr:hover { background: var(--cp-ivory-2); }
.purchase-report-page .cp-strong { font-weight: 700; color: var(--cp-heading); }
.purchase-report-page .cp-sub { color: var(--cp-muted); font-size: .72rem; }
.purchase-report-page .cp-faint { color: var(--cp-muted); }
.purchase-report-page .cp-doc-link { font-weight: 700; color: var(--cp-heading); text-decoration: none; }
.purchase-report-page .cp-doc-link:hover { text-decoration: underline; text-decoration-color: var(--cp-gold); text-underline-offset: 3px; }
.purchase-report-page .cp-empty-cell { text-align: center; color: var(--cp-muted); padding: 22px 14px; }

.purchase-report-page .cp-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cp-gold-soft); border: 1px solid var(--cp-gold-line); border-radius: 16px; }
.purchase-report-page .cp-notice i { color: var(--cp-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.purchase-report-page .cp-notice strong { color: var(--cp-heading); display: block; margin-bottom: 2px; }
.purchase-report-page .cp-notice p { color: var(--cp-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .purchase-report-page .cp-filter-form { grid-template-columns: repeat(2, minmax(0, 1fr)); } .purchase-report-page .cp-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="purchase-report-page p-4 sm:p-6">
    <div class="cp-shell">
        <?php $back_arrow_href = back_url('compras?estado=recibida'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cp-title-lockup">
                <div class="cp-hero-icon"><i class="fas fa-chart-column"></i></div>
                <div>
                    <p class="cp-kicker">Compras y abastecimiento</p>
                    <h1 class="cp-title">Compras recibidas</h1>
                    <p class="cp-subtitle">Resumen de lo que has recibido, agrupado por proveedor y por producto.</p>
                </div>
            </div>
            <span class="cp-badge is-soft"><i class="fas fa-eye"></i> Solo consulta</span>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cp-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Este reporte todav&iacute;a no est&aacute; disponible.</strong>
                    <p>P&iacute;dele al administrador del sistema que habilite el m&oacute;dulo de compras.<?php if (!empty($errorTecnico)): ?> <span style="opacity:.75">(<?= comp_report_safe($errorTecnico, '') ?>)</span><?php endif; ?></p>
                    <a class="cp-btn cp-btn-muted mt-4" href="<?= back_url('compras?estado=recibida') ?>" style="display:inline-flex">
                        <i class="fas fa-arrow-left"></i>
                        Volver a compras
                    </a>
                </div>
            </section>
        <?php else: ?>
            <section class="cp-stats">
                <div class="cp-stat"><p class="cp-stat-label">Compras</p><p class="cp-stat-value"><?= number_format((int)($resumen['compras'] ?? 0)) ?></p></div>
                <div class="cp-stat"><p class="cp-stat-label">Proveedores</p><p class="cp-stat-value"><?= number_format((int)($resumen['proveedores'] ?? 0)) ?></p></div>
                <div class="cp-stat"><p class="cp-stat-label">Productos</p><p class="cp-stat-value"><?= number_format((int)($resumen['productos'] ?? 0)) ?></p></div>
                <div class="cp-stat"><p class="cp-stat-label">Cantidad</p><p class="cp-stat-value"><?= comp_report_qty($resumen['cantidad_total'] ?? 0) ?></p></div>
                <div class="cp-stat"><p class="cp-stat-label">Total</p><p class="cp-stat-value"><?= comp_report_money($resumen['total_lineas'] ?? 0) ?></p></div>
            </section>

            <section class="cp-toolbar ms-back-legacy">
                <a class="cp-btn cp-btn-muted" href="<?= back_url('compras?estado=recibida') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a compras
                </a>
            </section>

            <section class="cp-panel p-3 md:p-4">
                <form method="GET" action="<?= url('compras/reportes/recibidas') ?>" class="cp-filter-form" data-auto-filter-form>
                    <select class="cp-control" name="proveedor_id">
                        <option value="">Todos los proveedores</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <?php $id = (string)($proveedor['id'] ?? ''); ?>
                            <option value="<?= (int)$id ?>" <?= $proveedorSeleccionado === $id ? 'selected' : '' ?>>
                                <?= comp_report_safe($proveedor['nombre'] ?? null) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="cp-control" name="producto_id">
                        <option value="">Todos los productos</option>
                        <?php foreach ($productos as $producto): ?>
                            <?php $id = (string)($producto['id'] ?? ''); ?>
                            <option value="<?= (int)$id ?>" <?= $productoSeleccionado === $id ? 'selected' : '' ?>>
                                <?= comp_report_safe(trim((string)($producto['codigo'] ?? '') . ' ' . (string)($producto['nombre'] ?? ''))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input class="cp-control" type="date" name="fecha_inicio" value="<?= comp_report_safe($fechaInicio, '') ?>">
                    <input class="cp-control" type="date" name="fecha_fin" value="<?= comp_report_safe($fechaFin, '') ?>">
                    <select class="cp-control" name="estado">
                        <option value="recibida" <?= $estado === 'recibida' ? 'selected' : '' ?>>Recibidas</option>
                        <option value="borrador" <?= $estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="cancelada" <?= $estado === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todas</option>
                    </select>
                    <button class="cp-btn cp-btn-brand" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="cp-panel overflow-hidden">
                    <div class="cp-panel-head">
                        <h2 class="cp-panel-title">Totales por proveedor</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="cp-table">
                            <thead>
                                <tr>
                                    <th>Proveedor</th>
                                    <th class="is-end">Compras</th>
                                    <th class="is-end">Cantidad</th>
                                    <th class="is-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porProveedor)): ?>
                                    <tr><td colspan="4" class="cp-empty-cell">Sin datos para estos filtros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porProveedor as $fila): ?>
                                        <tr>
                                            <td class="cp-strong"><?= comp_report_safe($fila['proveedor_nombre'] ?? null) ?></td>
                                            <td class="is-end"><?= (int)($fila['compras'] ?? 0) ?></td>
                                            <td class="is-end"><?= comp_report_qty($fila['cantidad_total'] ?? 0) ?></td>
                                            <td class="is-end cp-strong"><?= comp_report_money($fila['total_lineas'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="cp-panel overflow-hidden">
                    <div class="cp-panel-head">
                        <h2 class="cp-panel-title">Totales por producto</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="cp-table">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th class="is-end">Compras</th>
                                    <th class="is-end">Cantidad</th>
                                    <th class="is-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porProducto)): ?>
                                    <tr><td colspan="4" class="cp-empty-cell">Sin datos para estos filtros.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porProducto as $fila): ?>
                                        <tr>
                                            <td>
                                                <div class="cp-strong"><?= comp_report_safe($fila['producto_nombre'] ?? null) ?></div>
                                                <div class="cp-sub"><?= comp_report_safe($fila['producto_codigo'] ?? null, 'Sin c&oacute;digo') ?></div>
                                            </td>
                                            <td class="is-end"><?= (int)($fila['compras'] ?? 0) ?></td>
                                            <td class="is-end">
                                                <?= comp_report_qty($fila['cantidad_total'] ?? 0) ?>
                                                <?= comp_report_safe($fila['unidad_medida'] ?? null, '') ?>
                                            </td>
                                            <td class="is-end cp-strong"><?= comp_report_money($fila['total_lineas'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="cp-panel overflow-hidden">
                <div class="cp-panel-head">
                    <h2 class="cp-panel-title">Detalle de productos comprados</h2>
                    <span class="cp-panel-hint">Hasta 300 renglones</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>Compra</th>
                                <th>Proveedor</th>
                                <th>Producto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="is-end">Cantidad</th>
                                <th class="is-end">Costo</th>
                                <th class="is-end">Subtotal</th>
                                <th>Movimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lineas)): ?>
                                <tr><td colspan="9" class="cp-empty-cell">Sin renglones para estos filtros.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lineas as $linea): ?>
                                    <?php [$lEstadoLabel, $lEstadoClass, $lEstadoIcon] = comp_report_estado_meta($linea['estado'] ?? null); ?>
                                    <tr>
                                        <td>
                                            <a class="cp-doc-link" href="<?= url('compras/' . (int)($linea['compra_id'] ?? 0)) ?>">#<?= (int)($linea['compra_id'] ?? 0) ?></a>
                                            <div class="cp-sub"><?= comp_report_safe($linea['folio'] ?? null, 'Sin folio') ?></div>
                                        </td>
                                        <td><?= comp_report_safe($linea['proveedor_nombre'] ?? null) ?></td>
                                        <td>
                                            <div class="cp-strong"><?= comp_report_safe($linea['producto_nombre'] ?? null) ?></div>
                                            <div class="cp-sub"><?= comp_report_safe($linea['producto_codigo'] ?? null, 'Sin c&oacute;digo') ?></div>
                                        </td>
                                        <td>
                                            <div><?= comp_report_safe($linea['fecha_reporte'] ?? null) ?></div>
                                            <div class="cp-sub">Compra <?= comp_report_safe($linea['fecha_compra'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <span class="cp-badge <?= $lEstadoClass ?>">
                                                <i class="fas <?= $lEstadoIcon ?>"></i>
                                                <?= $lEstadoLabel ?>
                                            </span>
                                        </td>
                                        <td class="is-end">
                                            <?= comp_report_qty($linea['cantidad'] ?? 0) ?>
                                            <?= comp_report_safe($linea['unidad_medida'] ?? null, '') ?>
                                        </td>
                                        <td class="is-end"><?= comp_report_money($linea['costo_unitario'] ?? 0) ?></td>
                                        <td class="is-end cp-strong"><?= comp_report_money($linea['subtotal'] ?? 0) ?></td>
                                        <td>
                                            <?php if (!empty($linea['movimiento_inventario_id'])): ?>
                                                <div class="cp-strong">#<?= (int)$linea['movimiento_inventario_id'] ?> <?= comp_report_safe($linea['movimiento_tipo'] ?? null) ?></div>
                                                <div class="cp-sub"><?= comp_report_safe($linea['movimiento_created_at'] ?? null) ?></div>
                                            <?php else: ?>
                                                <span class="cp-faint">Sin movimiento</span>
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
    </div>
</div>
