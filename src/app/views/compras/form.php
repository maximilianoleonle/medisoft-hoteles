<?php
$catalogos = $catalogos ?? ['proveedores' => [], 'productos' => []];
$proveedores = $catalogos['proveedores'] ?? [];
$productos = $catalogos['productos'] ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$errorTecnico = $errorTecnico ?? null;
$compra = isset($compra) && is_array($compra) ? $compra : [];
$modoEdicion = !empty($modoEdicion);
$compraId = (int)($compra['id'] ?? 0);
$compraFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

if (!function_exists('comp_form_safe')) {
    function comp_form_safe($value, $fallback = '')
    {
        $text = (string)($value ?? $fallback);
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('comp_form_money')) {
    function comp_form_money($value)
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}

if (!function_exists('comp_form_error')) {
    function comp_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? comp_form_safe($message) : '';
    }
}

if (!function_exists('comp_form_error_class')) {
    function comp_form_error_class(array $errors, string $field): string
    {
        return comp_form_error($errors, $field) !== '' ? ' cp-input-error' : '';
    }
}

if (!function_exists('comp_form_error_attrs')) {
    function comp_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (comp_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . comp_form_safe($errorId) . '"';
    }
}

$oldInputCompra = isset($_SESSION['old_input']) && is_array($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
$detallesCompra = isset($compra['detalles']) && is_array($compra['detalles']) ? array_values($compra['detalles']) : [];
$productosBase = array_column($detallesCompra, 'producto_id');
$cantidadesBase = array_column($detallesCompra, 'cantidad');
$costosBase = array_column($detallesCompra, 'costo_unitario');
$proveedorSeleccionado = (string)($oldInputCompra['proveedor_id'] ?? ($compra['proveedor_id'] ?? ''));
$folioValor = comp_form_safe($oldInputCompra['folio'] ?? ($compra['folio'] ?? ''));
$fechaCompraValor = comp_form_safe($oldInputCompra['fecha_compra'] ?? ($compra['fecha_compra'] ?? date('Y-m-d')));
$notasValor = comp_form_safe($oldInputCompra['notas'] ?? ($compra['notas'] ?? ''));
$productosSeleccionados = is_array($oldInputCompra['producto_id'] ?? null) ? array_values($oldInputCompra['producto_id']) : $productosBase;
$cantidadesFormulario = is_array($oldInputCompra['cantidad'] ?? null) ? array_values($oldInputCompra['cantidad']) : $cantidadesBase;
$costosFormulario = is_array($oldInputCompra['costo_unitario'] ?? null) ? array_values($oldInputCompra['costo_unitario']) : $costosBase;
$proveedoresPorProductoFormulario = is_array($oldInputCompra['detalle_proveedor_id'] ?? null) ? array_values($oldInputCompra['detalle_proveedor_id']) : [];
$faltanCatalogos = empty($proveedores) || empty($productos);
?>

<style>
.purchase-form-page {
    --cp-brand: var(--brand-primary, #1B2746);
    --cp-brand-2: var(--brand-secondary, #0F172A);
    --cp-gold: var(--brand-accent, #BD9441);
    --cp-gold-soft: color-mix(in srgb, var(--cp-gold) 15%, #FFFFFF);
    --cp-gold-line: color-mix(in srgb, var(--cp-gold) 42%, #E4D4B0);
    --cp-gold-ink: color-mix(in srgb, var(--cp-gold) 72%, #000);
    --cp-ivory: #F5F5F7;
    --cp-ivory-2: #FAFAFC;
    --cp-surface: #FFFFFF;
    --cp-surface-warm: #F5F5F7;
    --cp-border: color-mix(in srgb, var(--cp-brand) 7%, #E7E1D4);
    --cp-ring: color-mix(in srgb, var(--cp-gold) 32%, transparent);
    --cp-text: #171717;
    --cp-muted: #667085;
    --cp-heading: #111827;
    --cp-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cp-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cp-warning: #C2841C;
    --cp-warning-bg: #FAF0DC;
    min-height: 100%;
    color: var(--cp-text);
    font-family: var(--cp-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.purchase-form-page .cp-shell { display: grid; gap: 14px; max-width: 1100px; }
.purchase-form-page .cp-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.purchase-form-page .cp-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cp-gold), var(--cp-brand) 54%, color-mix(in srgb, var(--cp-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cp-brand) 72%, transparent);
}
.purchase-form-page .cp-kicker { margin: 0 0 2px; color: var(--cp-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.purchase-form-page .cp-title { margin: 0; font-family: var(--cp-serif); color: var(--cp-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.purchase-form-page .cp-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--cp-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.purchase-form-page .cp-panel { background: var(--cp-surface); border: 1px solid var(--cp-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.purchase-form-page label { display: block; font-size: .74rem; font-weight: 700; color: var(--cp-muted); text-transform: uppercase; letter-spacing: .045em; margin-bottom: 6px; }
.purchase-form-page .cp-req { color: var(--cp-gold-ink); }
.purchase-form-page .cp-optional { color: var(--cp-muted); font-size: .68rem; font-weight: 700; text-transform: none; letter-spacing: 0; }
.purchase-form-page .cp-input, .purchase-form-page .cp-textarea {
    width: 100%; border: 1px solid var(--cp-border); background: var(--cp-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--cp-text); font-weight: 600; font-size: .9rem; font-family: var(--cp-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.purchase-form-page .cp-input { min-height: 44px; }
.purchase-form-page select.cp-input { cursor: pointer; }
.purchase-form-page .cp-textarea { min-height: 96px; resize: vertical; }
.purchase-form-page .cp-input:focus, .purchase-form-page .cp-textarea:focus { border-color: var(--cp-gold); box-shadow: 0 0 0 3px var(--cp-ring); outline: none; background: #fff; }
.purchase-form-page .cp-input-error {
    border-color: #B42318;
    background: #FFF7F6;
}
.purchase-form-page .cp-form-error {
    display: block;
    margin-top: 7px;
    color: #B42318;
    font-size: .76rem;
    font-weight: 800;
    line-height: 1.35;
    letter-spacing: 0;
    text-transform: none;
}

.purchase-form-page .cp-section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.purchase-form-page .cp-section-title { font-family: var(--cp-serif); font-size: 1.4rem; font-weight: 700; color: var(--cp-heading); }
.purchase-form-page .cp-tag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 11px; border-radius: 999px; background: var(--cp-warning-bg); color: color-mix(in srgb, var(--cp-warning) 82%, #000); border: 1px solid color-mix(in srgb, var(--cp-warning) 28%, #fff); font-size: .72rem; font-weight: 700; }
.purchase-form-page .cp-line-row { border: 1px solid var(--cp-border); background: var(--cp-surface-warm); border-radius: 13px; padding: 13px; }
.purchase-form-page .cp-line-row.has-provider-override { border-color: var(--cp-gold-line); background: #fff; box-shadow: 0 10px 24px -22px color-mix(in srgb, var(--cp-gold) 54%, transparent); }
.purchase-form-page .cp-line-row label { font-size: .68rem; }
.purchase-form-page .cp-field-hint { margin-top: 6px; color: var(--cp-muted); font-size: .72rem; font-weight: 600; line-height: 1.35; }
.purchase-form-page .cp-line-note { margin-top: 7px; color: var(--cp-gold-ink); font-size: .7rem; font-weight: 800; line-height: 1.25; display: none; }
.purchase-form-page .cp-line-row.has-provider-override .cp-line-note { display: block; }

.purchase-form-page .cp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.purchase-form-page .cp-btn:hover { transform: translateY(-1px); }
.purchase-form-page .cp-btn:active { transform: translateY(0) scale(.98); }
.purchase-form-page .cp-btn:focus-visible { outline: 3px solid var(--cp-ring); outline-offset: 2px; }
.purchase-form-page .cp-btn-gold { background: linear-gradient(135deg, var(--cp-gold), color-mix(in srgb, var(--cp-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--cp-gold) 58%, transparent); }
.purchase-form-page .cp-btn-gold:disabled { opacity: .5; cursor: not-allowed; box-shadow: none; transform: none; }
.purchase-form-page .cp-btn-muted { background: var(--cp-surface); border-color: var(--cp-border); color: var(--cp-muted); }

.purchase-form-page .cp-warn { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; background: var(--cp-warning-bg); border: 1px solid color-mix(in srgb, var(--cp-warning) 28%, #fff); border-radius: 13px; color: color-mix(in srgb, var(--cp-warning) 82%, #000); font-size: .88rem; font-weight: 600; }
.purchase-form-page .cp-warn i { margin-top: 2px; }
.purchase-form-page .cp-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--cp-gold-soft); border: 1px solid var(--cp-gold-line); border-radius: 16px; }
.purchase-form-page .cp-notice i { color: var(--cp-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.purchase-form-page .cp-notice strong { color: var(--cp-heading); display: block; margin-bottom: 2px; }
.purchase-form-page .cp-notice p { color: var(--cp-muted); font-size: .88rem; margin: 0; }
</style>

<div class="purchase-form-page p-4 sm:p-6">
    <div class="cp-shell">
        <?php $back_arrow_href = back_url($modoEdicion ? 'compras/' . $compraId : 'compras'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="cp-title-lockup">
            <div class="cp-hero-icon"><i class="fas fa-clipboard-list"></i></div>
            <div>
                <p class="cp-kicker">Compras y abastecimiento</p>
                <h1 class="cp-title"><?= $modoEdicion ? 'Editar compra #' . $compraId : 'Nueva compra' ?></h1>
                <p class="cp-subtitle"><?= $modoEdicion ? 'Corrige el proveedor, folio o productos del borrador. Estos cambios todav&iacute;a no afectan el inventario.' : 'Captura lo que vas a comprar. Se guarda como <strong>borrador</strong>: el inventario y la mercanc&iacute;a se actualizan despu&eacute;s, cuando marques la compra como recibida.' ?></p>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="cp-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite.<?php if (!empty($errorTecnico)): ?> <span style="opacity:.75">(<?= comp_form_safe($errorTecnico, '') ?>)</span><?php endif; ?></p>
                    <a class="cp-btn cp-btn-muted mt-4" href="<?= back_url('compras') ?>" style="display:inline-flex">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </section>
        <?php else: ?>
            <form method="POST" action="<?= url($modoEdicion ? 'compras/' . $compraId . '/actualizar' : 'compras') ?>" class="cp-panel p-5">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="proveedor_id"><?= $modoEdicion ? 'Proveedor' : 'Proveedor general' ?> <?= $modoEdicion ? '<span class="cp-req">*</span>' : '<span class="cp-optional">(opcional)</span>' ?></label>
                        <select class="cp-input<?= comp_form_error_class($compraFieldErrors, 'proveedor_id') ?>" id="proveedor_id" name="proveedor_id"<?= $modoEdicion ? ' required' : '' ?><?= comp_form_error_attrs($compraFieldErrors, 'proveedor_id', 'ms-form-error-proveedor_id') ?>>
                            <option value="">Elige proveedor general</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?= (int)$proveedor['id'] ?>" <?= $proveedorSeleccionado === (string)$proveedor['id'] ? 'selected' : '' ?>>
                                    <?= comp_form_safe($proveedor['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (comp_form_error($compraFieldErrors, 'proveedor_id')): ?>
                            <span class="cp-form-error ms-form-field-error" id="ms-form-error-proveedor_id"><?= comp_form_error($compraFieldErrors, 'proveedor_id') ?></span>
                        <?php endif; ?>
                        <p class="cp-field-hint"><?= $modoEdicion ? 'Todos los productos de este borrador pertenecen a este proveedor.' : 'Se usa solo en las líneas que digan "Usar proveedor general". Si cada producto ya tiene proveedor, puedes dejarlo vacío.' ?></p>
                    </div>

                    <div>
                        <label for="folio">Folio</label>
                        <input class="cp-input<?= comp_form_error_class($compraFieldErrors, 'folio') ?>" id="folio" name="folio" type="text" maxlength="60" placeholder="Opcional (n&uacute;mero de nota o factura)" value="<?= $folioValor ?>"<?= comp_form_error_attrs($compraFieldErrors, 'folio', 'ms-form-error-folio') ?>>
                        <?php if (comp_form_error($compraFieldErrors, 'folio')): ?>
                            <span class="cp-form-error ms-form-field-error" id="ms-form-error-folio"><?= comp_form_error($compraFieldErrors, 'folio') ?></span>
                        <?php endif; ?>
                        <p class="cp-field-hint">Si mezclas proveedores, se agregará un sufijo al folio de cada borrador.</p>
                    </div>

                    <div>
                        <label for="fecha_compra">Fecha <span class="cp-req">*</span></label>
                        <input class="cp-input<?= comp_form_error_class($compraFieldErrors, 'fecha_compra') ?>" id="fecha_compra" name="fecha_compra" type="date" required value="<?= $fechaCompraValor ?>"<?= comp_form_error_attrs($compraFieldErrors, 'fecha_compra', 'ms-form-error-fecha_compra') ?>>
                        <?php if (comp_form_error($compraFieldErrors, 'fecha_compra')): ?>
                            <span class="cp-form-error ms-form-field-error" id="ms-form-error-fecha_compra"><?= comp_form_error($compraFieldErrors, 'fecha_compra') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="cp-section-head">
                        <h2 class="cp-section-title">Productos</h2>
                        <span class="cp-tag"><i class="fas fa-pen-ruler"></i> Se guarda como borrador</span>
                    </div>

                    <div class="grid gap-3">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <?php
                            $productoSeleccionado = (string)($productosSeleccionados[$i] ?? '');
                            $cantidadValor = comp_form_safe($cantidadesFormulario[$i] ?? '');
                            $costoValor = comp_form_safe($costosFormulario[$i] ?? '');
                            $proveedorLineaSeleccionado = (string)($proveedoresPorProductoFormulario[$i] ?? '');
                            ?>
                            <div class="cp-line-row purchase-line-row grid grid-cols-1 <?= $modoEdicion ? 'md:grid-cols-[minmax(220px,1fr)_120px_150px]' : 'md:grid-cols-[minmax(220px,1fr)_minmax(190px,.75fr)_120px_150px]' ?> gap-3<?= $proveedorLineaSeleccionado !== '' ? ' has-provider-override' : '' ?>">
                                <?php if (!$modoEdicion): ?><div>
                                    <label for="producto_id_<?= $i ?>">Producto <?= $i === 0 ? '<span class="cp-req">*</span>' : '' ?></label>
                                    <select class="cp-input cp-product-select<?= $i === 0 ? comp_form_error_class($compraFieldErrors, 'producto_id_0') : '' ?>" id="producto_id_<?= $i ?>" name="producto_id[]" <?= $i === 0 ? 'required' : '' ?><?= $i === 0 ? comp_form_error_attrs($compraFieldErrors, 'producto_id_0', 'ms-form-error-producto_id_0') : '' ?>>
                                        <option value="">Elige un producto</option>
                                        <?php foreach ($productos as $producto): ?>
                                            <option value="<?= (int)$producto['id'] ?>" data-costo="<?= comp_form_money($producto['costo_unitario'] ?? 0) ?>" <?= $productoSeleccionado === (string)$producto['id'] ? 'selected' : '' ?>>
                                                <?= comp_form_safe(($producto['codigo'] ?? '') . ' - ' . ($producto['nombre'] ?? '')) ?>
                                                (stock <?= comp_form_safe($producto['stock_actual'] ?? 0) ?> <?= comp_form_safe($producto['unidad_medida'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($i === 0 && comp_form_error($compraFieldErrors, 'producto_id_0')): ?>
                                        <span class="cp-form-error ms-form-field-error" id="ms-form-error-producto_id_0"><?= comp_form_error($compraFieldErrors, 'producto_id_0') ?></span>
                                    <?php endif; ?>
                                </div><?php else: ?>
                                    <input type="hidden" name="detalle_proveedor_id[]" value="">
                                <?php endif; ?>

                                <div>
                                    <label for="detalle_proveedor_id_<?= $i ?>">Proveedor del producto</label>
                                    <select class="cp-input cp-line-provider-select" id="detalle_proveedor_id_<?= $i ?>" name="detalle_proveedor_id[]">
                                        <option value="">Usar proveedor general</option>
                                        <?php foreach ($proveedores as $proveedor): ?>
                                            <option value="<?= (int)$proveedor['id'] ?>" <?= $proveedorLineaSeleccionado === (string)$proveedor['id'] ? 'selected' : '' ?>>
                                                <?= comp_form_safe($proveedor['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="cp-line-note">Se guardará en un borrador separado para este proveedor.</p>
                                </div>

                                <div>
                                    <label for="cantidad_<?= $i ?>">Cantidad <?= $i === 0 ? '<span class="cp-req">*</span>' : '' ?></label>
                                    <input class="cp-input<?= $i === 0 ? comp_form_error_class($compraFieldErrors, 'cantidad_0') : '' ?>" id="cantidad_<?= $i ?>" name="cantidad[]" type="number" min="0.01" step="0.01" value="<?= $cantidadValor ?>" <?= $i === 0 ? 'required' : '' ?><?= $i === 0 ? comp_form_error_attrs($compraFieldErrors, 'cantidad_0', 'ms-form-error-cantidad_0') : '' ?>>
                                    <?php if ($i === 0 && comp_form_error($compraFieldErrors, 'cantidad_0')): ?>
                                        <span class="cp-form-error ms-form-field-error" id="ms-form-error-cantidad_0"><?= comp_form_error($compraFieldErrors, 'cantidad_0') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label for="costo_unitario_<?= $i ?>">Costo unitario</label>
                                    <input class="cp-input cp-cost-input<?= $i === 0 ? comp_form_error_class($compraFieldErrors, 'costo_unitario_0') : '' ?>" id="costo_unitario_<?= $i ?>" name="costo_unitario[]" type="number" data-money-format="true" min="0" step="0.01" value="<?= $costoValor ?>"<?= $i === 0 ? comp_form_error_attrs($compraFieldErrors, 'costo_unitario_0', 'ms-form-error-costo_unitario_0') : '' ?>>
                                    <?php if ($i === 0 && comp_form_error($compraFieldErrors, 'costo_unitario_0')): ?>
                                        <span class="cp-form-error ms-form-field-error" id="ms-form-error-costo_unitario_0"><?= comp_form_error($compraFieldErrors, 'costo_unitario_0') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <p class="mt-2" style="font-size:.74rem;color:var(--cp-muted);font-weight:600">Puedes capturar hasta 5 productos. El costo se llena solo al elegir el producto; ajústalo si pagaste otro precio. Si una línea usa otro proveedor, se creará un borrador separado para mantener correcta la cuenta por pagar.</p>
                </div>

                <div class="mt-6">
                    <label for="notas">Notas internas</label>
                    <textarea class="cp-textarea<?= comp_form_error_class($compraFieldErrors, 'notas') ?>" id="notas" name="notas" maxlength="1000" placeholder="Lo que quieras recordar de esta compra"<?= comp_form_error_attrs($compraFieldErrors, 'notas', 'ms-form-error-notas') ?>><?= $notasValor ?></textarea>
                    <?php if (comp_form_error($compraFieldErrors, 'notas')): ?>
                        <span class="cp-form-error ms-form-field-error" id="ms-form-error-notas"><?= comp_form_error($compraFieldErrors, 'notas') ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($faltanCatalogos): ?>
                    <div class="cp-warn mt-5">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Para crear una compra necesitas tener al menos un proveedor y un producto activos en el hotel.</span>
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap gap-3 mt-6">
                    <button class="cp-btn cp-btn-gold" type="submit" <?= $faltanCatalogos ? 'disabled' : '' ?>>
                        <i class="fas fa-save"></i>
                        <?= $modoEdicion ? 'Guardar cambios' : 'Guardar borrador' ?>
                    </button>
                    <a class="cp-btn cp-btn-muted" href="<?= back_url($modoEdicion ? 'compras/' . $compraId : 'compras') ?>">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const proveedorGeneral = document.getElementById('proveedor_id');
    const proveedoresLinea = Array.from(document.querySelectorAll('.cp-line-provider-select'));

    function actualizarProveedorGeneralEnLineas() {
        if (!proveedorGeneral) {
            return;
        }

        const selected = proveedorGeneral.options[proveedorGeneral.selectedIndex];
        const nombreProveedor = selected && proveedorGeneral.value !== ''
            ? selected.textContent.trim().replace(/\s+/g, ' ')
            : '';

        proveedoresLinea.forEach((select) => {
            const opcionGeneral = select.querySelector('option[value=""]');
            if (!opcionGeneral) {
                return;
            }

            opcionGeneral.textContent = nombreProveedor
                ? 'Usar proveedor general (' + nombreProveedor + ')'
                : 'Usar proveedor general';
        });
    }

    function actualizarRequiredProveedorGeneral() {
        if (!proveedorGeneral) {
            return;
        }

        const necesitaGeneral = proveedoresLinea.some((provSelect) => {
            const row = provSelect.closest('.cp-line-row');
            const prodSelect = row ? row.querySelector('.cp-product-select') : null;
            return prodSelect && prodSelect.value !== '' && provSelect.value === '';
        });

        proveedorGeneral.required = false;
        proveedorGeneral.dataset.necesitaGeneral = necesitaGeneral ? 'true' : 'false';
    }

    function actualizarEstadoProveedorLinea(select) {
        const row = select.closest('.cp-line-row');
        if (!row) {
            return;
        }

        row.classList.toggle('has-provider-override', select.value !== '');
    }

    if (proveedorGeneral) {
        proveedorGeneral.addEventListener('change', actualizarProveedorGeneralEnLineas);
        actualizarProveedorGeneralEnLineas();
    }

    proveedoresLinea.forEach((select) => {
        select.addEventListener('change', () => {
            actualizarEstadoProveedorLinea(select);
            actualizarRequiredProveedorGeneral();
        });
        actualizarEstadoProveedorLinea(select);
    });

    actualizarRequiredProveedorGeneral();

    document.querySelectorAll('.cp-product-select').forEach((select) => {
        select.addEventListener('change', () => {
            actualizarRequiredProveedorGeneral();
            const row = select.closest('.cp-line-row');
            const costInput = row ? row.querySelector('.cp-cost-input') : null;
            const selected = select.options[select.selectedIndex];
            if (!costInput || !selected || costInput.value !== '') {
                return;
            }
            const cost = selected.getAttribute('data-costo');
            if (cost !== null && cost !== '') {
                if (window.MedisoftMoneyInput) {
                    window.MedisoftMoneyInput.set(costInput, cost);
                } else {
                    costInput.value = cost;
                }
            }
        });
    });
})();
</script>
