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
    --pv-brand: var(--brand-primary, #1B2746);
    --pv-brand-2: var(--brand-secondary, #0F172A);
    --pv-gold: var(--brand-accent, #BD9441);
    --pv-gold-soft: color-mix(in srgb, var(--pv-gold) 15%, #FFFFFF);
    --pv-gold-line: color-mix(in srgb, var(--pv-gold) 42%, #E4D4B0);
    --pv-gold-ink: color-mix(in srgb, var(--pv-gold) 72%, #000);
    --pv-ivory: #F6F2EA;
    --pv-ivory-2: #FBF8F2;
    --pv-surface: #FFFFFF;
    --pv-surface-warm: #FCFAF5;
    --pv-border: color-mix(in srgb, var(--pv-brand) 7%, #E7E1D4);
    --pv-ring: color-mix(in srgb, var(--pv-gold) 32%, transparent);
    --pv-text: #171717;
    --pv-muted: #667085;
    --pv-heading: #111827;
    --pv-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --pv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --pv-success: #1E9E63;
    --pv-success-bg: #E7F4EC;
    min-height: 100%;
    color: var(--pv-text);
    font-family: var(--pv-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--pv-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--pv-ivory-2), var(--pv-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.provider-detail-page .pv-shell { display: grid; gap: 14px; }
.provider-detail-page .pv-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.provider-detail-page .pv-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--pv-gold), var(--pv-brand) 54%, color-mix(in srgb, var(--pv-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--pv-brand) 72%, transparent);
}
.provider-detail-page .pv-kicker { margin: 0 0 2px; color: var(--pv-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.provider-detail-page .pv-title { margin: 0; font-family: var(--pv-serif); color: var(--pv-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.provider-detail-page .pv-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--pv-muted); font-size: .92rem; font-weight: 600; line-height: 1.5; }

.provider-detail-page .pv-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.provider-detail-page .pv-stat { background: var(--pv-surface); border: 1px solid var(--pv-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.provider-detail-page .pv-stat-label { color: var(--pv-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.provider-detail-page .pv-stat-value { margin-top: 2px; font-family: var(--pv-serif); font-size: 1.55rem; font-weight: 700; line-height: 1.1; color: var(--pv-heading); }

.provider-detail-page .pv-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.provider-detail-page .pv-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--pv-border); background: var(--pv-surface); color: var(--pv-text); font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.provider-detail-page .pv-btn:hover { transform: translateY(-1px); border-color: var(--pv-gold-line); color: var(--pv-gold-ink); box-shadow: 0 10px 24px -16px rgba(27,39,70,.4); }
.provider-detail-page .pv-btn:focus-visible { outline: 3px solid var(--pv-ring); outline-offset: 2px; }

.provider-detail-page .pv-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.provider-detail-page .pv-badge.is-active { color: color-mix(in srgb, var(--pv-success) 78%, #000); background: var(--pv-success-bg); border-color: color-mix(in srgb, var(--pv-success) 26%, #fff); }
.provider-detail-page .pv-badge.is-inactive { color: #5F5E5A; background: #F1EFE8; border-color: #D3D1C7; }
.provider-detail-page .pv-badge.is-soft { color: var(--pv-muted); background: var(--pv-surface-warm); border-color: var(--pv-border); }

.provider-detail-page .pv-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(360px, 420px); gap: 14px; align-items: start; }
.provider-detail-page .pv-panel { background: var(--pv-surface); border: 1px solid var(--pv-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.provider-detail-page .pv-panel-title { font-family: var(--pv-serif); font-size: 1.4rem; font-weight: 700; color: var(--pv-heading); }
.provider-detail-page .pv-meta-label { font-size: .68rem; color: var(--pv-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.provider-detail-page .pv-meta-value { margin-top: 3px; font-weight: 700; color: var(--pv-heading); }
.provider-detail-page .pv-meta-value.is-notes { font-weight: 500; color: #334155; white-space: pre-line; }

.provider-detail-page .pv-kv { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 11px 0; border-bottom: 1px solid var(--pv-border); font-size: .88rem; }
.provider-detail-page .pv-kv:last-child { border-bottom: 0; }
.provider-detail-page .pv-kv span { color: var(--pv-muted); font-weight: 700; }
.provider-detail-page .pv-kv strong { color: var(--pv-heading); font-weight: 700; }

.provider-detail-page .pv-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--pv-border); }
.provider-detail-page .pv-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.provider-detail-page .pv-table thead { background: var(--pv-surface-warm); border-bottom: 1px solid var(--pv-border); }
.provider-detail-page .pv-table th { padding: 12px 14px; color: var(--pv-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.provider-detail-page .pv-table th.is-end, .provider-detail-page .pv-table td.is-end { text-align: right; }
.provider-detail-page .pv-table td { padding: 13px 14px; border-bottom: 1px solid var(--pv-border); vertical-align: middle; }
.provider-detail-page .pv-table tbody tr:last-child td { border-bottom: 0; }
.provider-detail-page .pv-table tbody tr { transition: background .16s ease; }
.provider-detail-page .pv-table tbody tr:hover { background: var(--pv-ivory-2); }
.provider-detail-page .pv-doc-link { font-weight: 700; color: var(--pv-heading); text-decoration: none; }
.provider-detail-page .pv-doc-link:hover { text-decoration: underline; text-decoration-color: var(--pv-gold); text-underline-offset: 3px; }
.provider-detail-page .pv-sub { color: var(--pv-muted); font-size: .72rem; }
.provider-detail-page .pv-strong { font-weight: 700; color: var(--pv-heading); }

.provider-detail-page .pv-empty { text-align: center; padding: 40px 18px; }
.provider-detail-page .pv-empty-icon { width: 54px; height: 54px; margin: 0 auto 12px; border-radius: 18px; display: grid; place-items: center; background: var(--pv-gold-soft); color: var(--pv-gold-ink); font-size: 1.25rem; }
.provider-detail-page .pv-empty h3 { color: var(--pv-brand); font-size: 1.05rem; font-weight: 700; }
.provider-detail-page .pv-empty p { color: var(--pv-muted); font-size: .88rem; margin-top: 6px; }

@media (max-width: 1100px) { .provider-detail-page .pv-grid { grid-template-columns: 1fr; } }
@media (max-width: 720px) { .provider-detail-page .pv-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .provider-detail-page .pv-title { font-size: 1.9rem; } }
</style>

<div class="provider-detail-page p-4 sm:p-6">
    <div class="pv-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="pv-title-lockup">
                <div class="pv-hero-icon"><i class="fas fa-truck-field"></i></div>
                <div>
                    <p class="pv-kicker">Compras y abastecimiento</p>
                    <h1 class="pv-title"><?= prov_view_safe($proveedor['nombre'] ?? null) ?></h1>
                    <p class="pv-subtitle">Ficha del proveedor: sus datos de contacto y lo que le has comprado en este hotel.</p>
                </div>
            </div>
            <span class="pv-badge <?= $activo ? 'is-active' : 'is-inactive' ?>">
                <i class="fas <?= $activo ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                <?= $activo ? 'Activo' : 'Inactivo' ?>
            </span>
        </section>

        <section class="pv-stats">
            <div class="pv-stat">
                <p class="pv-stat-label">Compras</p>
                <p class="pv-stat-value"><?= number_format((int)($resumenCompras['compras'] ?? 0)) ?></p>
            </div>
            <div class="pv-stat">
                <p class="pv-stat-label">Recibidas</p>
                <p class="pv-stat-value"><?= number_format((int)($resumenCompras['recibidas'] ?? 0)) ?></p>
            </div>
            <div class="pv-stat">
                <p class="pv-stat-label">Productos</p>
                <p class="pv-stat-value"><?= number_format((int)($resumenCompras['productos'] ?? 0)) ?></p>
            </div>
            <div class="pv-stat">
                <p class="pv-stat-label">Total recibido</p>
                <p class="pv-stat-value"><?= prov_view_money($resumenCompras['total_recibido'] ?? 0) ?></p>
            </div>
        </section>

        <section class="pv-toolbar">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('proveedores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="pv-btn ms-back-legacy" href="<?= back_url('proveedores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="pv-btn" href="<?= url('proveedores/' . $proveedorId . '/editar') ?>">
                    <i class="fas fa-pen"></i>
                    Editar datos
                </a>
                <a class="pv-btn" href="<?= url('compras/reportes/recibidas?proveedor_id=' . $proveedorId) ?>">
                    <i class="fas fa-chart-column"></i>
                    Ver reporte
                </a>
            </div>
        </section>

        <div class="pv-grid">
            <div class="pv-panel p-5">
                <h2 class="pv-panel-title mb-4">Datos del proveedor</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="pv-meta-label">Nombre comercial</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['nombre'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="pv-meta-label">Raz&oacute;n social</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['razon_social'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="pv-meta-label">RFC</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['rfc'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="pv-meta-label">Tel&eacute;fono</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['telefono'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="pv-meta-label">Correo</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['email'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="pv-meta-label">&Uacute;ltima actualizaci&oacute;n</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['updated_at'] ?? null) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="pv-meta-label">Direcci&oacute;n</div>
                        <div class="pv-meta-value"><?= prov_view_safe($proveedor['direccion'] ?? null) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="pv-meta-label">Notas internas</div>
                        <div class="pv-meta-value is-notes"><?= prov_view_safe($proveedor['notas'] ?? null, 'Sin notas') ?></div>
                    </div>
                </div>
            </div>

            <div class="pv-panel p-5">
                <h2 class="pv-panel-title mb-3">Resumen de compras</h2>
                <div>
                    <div class="pv-kv"><span>Borradores</span><strong><?= (int)($resumenCompras['borradores'] ?? 0) ?></strong></div>
                    <div class="pv-kv"><span>Canceladas</span><strong><?= (int)($resumenCompras['canceladas'] ?? 0) ?></strong></div>
                    <div class="pv-kv"><span>Renglones capturados</span><strong><?= (int)($resumenCompras['lineas'] ?? 0) ?></strong></div>
                    <div class="pv-kv"><span>Cantidad total</span><strong><?= prov_view_qty($resumenCompras['cantidad_total'] ?? 0) ?></strong></div>
                    <div class="pv-kv"><span>Total en renglones</span><strong><?= prov_view_money($resumenCompras['total_lineas'] ?? 0) ?></strong></div>
                    <div class="pv-kv"><span>&Uacute;ltima compra</span><strong><?= prov_view_safe($resumenCompras['ultima_compra'] ?? null) ?></strong></div>
                    <div class="pv-kv"><span>&Uacute;ltima recepci&oacute;n</span><strong><?= prov_view_safe($resumenCompras['ultima_recepcion'] ?? null) ?></strong></div>
                </div>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="pv-panel overflow-hidden">
            <div class="pv-panel-head">
                <h2 class="pv-panel-title">Compras recientes</h2>
                <span class="pv-badge is-soft">
                    <i class="fas fa-eye"></i>
                    Solo consulta
                </span>
            </div>

            <?php if (!$historialDisponible): ?>
                <div class="pv-empty">
                    <div class="pv-empty-icon"><i class="fas fa-file-circle-exclamation"></i></div>
                    <h3>El historial todav&iacute;a no est&aacute; disponible</h3>
                    <p>Cuando registres compras a este proveedor, aparecer&aacute;n aqu&iacute;.</p>
                </div>
            <?php elseif (empty($comprasRecientes)): ?>
                <div class="pv-empty">
                    <div class="pv-empty-icon"><i class="fas fa-receipt"></i></div>
                    <h3>Sin compras todav&iacute;a</h3>
                    <p>Este proveedor a&uacute;n no tiene compras registradas en el hotel.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="pv-table">
                        <thead>
                            <tr>
                                <th>Compra</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th class="is-end">Renglones</th>
                                <th class="is-end">Productos</th>
                                <th class="is-end">Cantidad</th>
                                <th class="is-end">Movimientos</th>
                                <th class="is-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comprasRecientes as $compra): ?>
                                <tr>
                                    <td>
                                        <a class="pv-doc-link" href="<?= url('compras/' . (int)($compra['id'] ?? 0)) ?>">#<?= (int)($compra['id'] ?? 0) ?></a>
                                        <div class="pv-sub"><?= prov_view_safe($compra['folio'] ?? null, 'Sin folio') ?></div>
                                    </td>
                                    <td>
                                        <div><?= prov_view_safe($compra['fecha_recepcion'] ?? null, 'Sin recepci&oacute;n') ?></div>
                                        <div class="pv-sub">Compra <?= prov_view_safe($compra['fecha_compra'] ?? null) ?></div>
                                    </td>
                                    <td>
                                        <span class="pv-badge is-soft">
                                            <i class="fas fa-circle-dot"></i>
                                            <?= prov_view_safe($compra['estado'] ?? null) ?>
                                        </span>
                                    </td>
                                    <td class="is-end"><?= (int)($compra['detalle_count'] ?? 0) ?></td>
                                    <td class="is-end"><?= (int)($compra['producto_count'] ?? 0) ?></td>
                                    <td class="is-end"><?= prov_view_qty($compra['cantidad_total'] ?? 0) ?></td>
                                    <td class="is-end"><?= (int)($compra['movimientos_count'] ?? 0) ?></td>
                                    <td class="is-end pv-strong"><?= prov_view_money($compra['total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
