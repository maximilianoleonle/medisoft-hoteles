<?php
$catalogos = $catalogos ?? ['proveedores' => [], 'productos' => []];
$proveedores = $catalogos['proveedores'] ?? [];
$productos = $catalogos['productos'] ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$errorTecnico = $errorTecnico ?? null;

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
?>

<style>
.purchase-form-page {
    --purchase-brand: var(--brand-primary, #1f3f46);
    --purchase-accent: var(--brand-accent, #b58a3c);
    --purchase-line: color-mix(in srgb, var(--purchase-brand) 10%, #e5e7eb);
    color: #243142;
}
.purchase-form-page .purchase-form-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--purchase-brand) 92%, #111827), color-mix(in srgb, var(--purchase-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.purchase-form-page .purchase-form-panel {
    border: 1px solid var(--purchase-line);
    background: rgba(255,255,255,.92);
}
.purchase-form-page .purchase-input,
.purchase-form-page .purchase-textarea {
    width: 100%;
    border: 1px solid var(--purchase-line);
    background: #fff;
    padding: 10px 12px;
}
.purchase-form-page .purchase-input {
    min-height: 42px;
}
.purchase-form-page .purchase-textarea {
    min-height: 92px;
    resize: vertical;
}
.purchase-form-page label {
    display: block;
    font-size: .78rem;
    font-weight: 900;
    color: #475569;
    margin-bottom: 6px;
}
.purchase-form-page .purchase-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 15px;
    border: 1px solid var(--purchase-line);
    font-weight: 900;
}
.purchase-form-page .purchase-btn-primary {
    background: var(--purchase-brand);
    border-color: var(--purchase-brand);
    color: #fff;
}
.purchase-form-page .purchase-btn-muted {
    background: #fff;
    color: #334155;
}
.purchase-form-page .purchase-line-row {
    border: 1px solid var(--purchase-line);
    background: #fff;
    padding: 12px;
}
</style>

<div class="purchase-form-page">
    <section class="purchase-form-hero">
        <div class="text-xs uppercase tracking-widest opacity-75 font-black">Inventario</div>
        <h1 class="text-2xl md:text-3xl font-black mt-2">Nuevo borrador de compra</h1>
        <p class="mt-2 text-white/80 max-w-3xl">
            Esta captura conserva la compra como borrador y no modifica stock, caja, pagos ni cuentas por pagar.
        </p>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="purchase-form-panel p-5 max-w-5xl">
                <strong>Compras no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1"><?= comp_form_safe($errorTecnico, 'Revisa la migracion minima de compras y el health checker.') ?></p>
                <a class="purchase-btn purchase-btn-muted mt-4" href="<?= url('compras') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('compras') ?>" class="purchase-form-panel p-5 max-w-6xl">
                <?= csrf_field() ?>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="proveedor_id">Proveedor *</label>
                        <select class="purchase-input" id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccionar proveedor</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?= (int)$proveedor['id'] ?>">
                                    <?= comp_form_safe($proveedor['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="folio">Folio</label>
                        <input class="purchase-input" id="folio" name="folio" type="text" maxlength="60" placeholder="Opcional">
                    </div>

                    <div>
                        <label for="fecha_compra">Fecha *</label>
                        <input class="purchase-input" id="fecha_compra" name="fecha_compra" type="date" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="mt-5">
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <h2 class="font-black text-slate-800">Productos</h2>
                        <span class="text-xs font-bold text-slate-500">Borrador solamente</span>
                    </div>

                    <div class="grid gap-3">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <div class="purchase-line-row grid grid-cols-1 md:grid-cols-[1fr_130px_160px] gap-3">
                                <div>
                                    <label for="producto_id_<?= $i ?>">Producto <?= $i === 0 ? '*' : '' ?></label>
                                    <select class="purchase-input purchase-product-select" id="producto_id_<?= $i ?>" name="producto_id[]" <?= $i === 0 ? 'required' : '' ?>>
                                        <option value="">Seleccionar producto</option>
                                        <?php foreach ($productos as $producto): ?>
                                            <option value="<?= (int)$producto['id'] ?>" data-costo="<?= comp_form_money($producto['costo_unitario'] ?? 0) ?>">
                                                <?= comp_form_safe(($producto['codigo'] ?? '') . ' - ' . ($producto['nombre'] ?? '')) ?>
                                                (stock <?= comp_form_safe($producto['stock_actual'] ?? 0) ?> <?= comp_form_safe($producto['unidad_medida'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label for="cantidad_<?= $i ?>">Cantidad <?= $i === 0 ? '*' : '' ?></label>
                                    <input class="purchase-input" id="cantidad_<?= $i ?>" name="cantidad[]" type="number" min="0.01" step="0.01" <?= $i === 0 ? 'required' : '' ?>>
                                </div>

                                <div>
                                    <label for="costo_unitario_<?= $i ?>">Costo unitario</label>
                                    <input class="purchase-input purchase-cost-input" id="costo_unitario_<?= $i ?>" name="costo_unitario[]" type="number" min="0" step="0.01">
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="notas">Notas internas</label>
                    <textarea class="purchase-textarea" id="notas" name="notas" maxlength="1000"></textarea>
                </div>

                <?php if (empty($proveedores) || empty($productos)): ?>
                    <div class="mt-5 p-4 border border-amber-200 bg-amber-50 text-amber-900 text-sm">
                        Se requieren proveedores activos y productos activos del hotel para crear borradores.
                    </div>
                <?php endif; ?>

                <div class="flex flex-wrap gap-3 mt-5">
                    <button class="purchase-btn purchase-btn-primary" type="submit" <?= empty($proveedores) || empty($productos) ? 'disabled' : '' ?>>
                        <i class="fas fa-save"></i>
                        Guardar borrador
                    </button>
                    <a class="purchase-btn purchase-btn-muted" href="<?= url('compras') ?>">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </form>
        <?php endif; ?>
    </section>
</div>

<script>
(() => {
    document.querySelectorAll('.purchase-product-select').forEach((select) => {
        select.addEventListener('change', () => {
            const row = select.closest('.purchase-line-row');
            const costInput = row ? row.querySelector('.purchase-cost-input') : null;
            const selected = select.options[select.selectedIndex];
            if (!costInput || !selected || costInput.value !== '') {
                return;
            }
            const cost = selected.getAttribute('data-costo');
            if (cost !== null && cost !== '') {
                costInput.value = cost;
            }
        });
    });
})();
</script>
