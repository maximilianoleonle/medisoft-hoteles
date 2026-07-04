<?php
$unidadMedidaAjuste = trim((string)($producto['unidad_medida'] ?? 'pzs'));
if ($unidadMedidaAjuste === '') {
    $unidadMedidaAjuste = 'pzs';
}
$stockActualAjuste = round((float)($producto['stock_actual'] ?? 0), 2);
$stockActualAjusteLabel = number_format($stockActualAjuste, 2, '.', '');
?>

<!-- Ajustar Stock -->
<style>
.inv-form-error {
    display: block;
    margin-top: 0.4rem;
    color: #B42318;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.35;
}
</style>

<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-sync text-xl opacity-80"></i>
                        Ajustar Stock
                    </h1>
                    <p class="text-hotel-gold mt-1 text-sm">
                        Registrar entrada o salida de inventario
                    </p>
                </div>
                <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline ms-back--glass'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('inventario') ?>"
                   class="bg-white/10 backdrop-blur text-white px-4 py-2 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-2 border border-white/20 ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-6 py-6 max-w-2xl">
        <!-- Info del Producto -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">
                    <?= htmlspecialchars($producto['nombre']) ?>
                </h2>
                <p class="text-gray-600 mb-4">
                    Código: <span class="font-semibold"><?= htmlspecialchars($producto['codigo']) ?></span>
                </p>
                
                <!-- Stock Actual -->
                <div class="inline-flex items-center justify-center bg-gray-100 rounded-lg px-6 py-3">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-1">Stock Actual</p>
                        <p class="text-3xl font-bold text-gray-800">
                            <?= htmlspecialchars($stockActualAjusteLabel, ENT_QUOTES, 'UTF-8') ?>
                            <span class="text-lg font-normal text-gray-600"><?= htmlspecialchars($unidadMedidaAjuste, ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de Ajuste -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <form method="POST" action="<?= url('inventario/ajuste/' . $producto['id']) ?>" class="space-y-6">
                <?= csrf_field() ?>
                
                <!-- Tipo de Movimiento -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Tipo de Movimiento <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative">
                            <input type="radio" 
                                   name="tipo" 
                                   value="ENTRADA"
                                   class="peer sr-only"
                                   <?= old('tipo') === 'ENTRADA' ? 'checked' : '' ?>
                                   required>
                            <div class="p-4 border-2 border-gray-300 rounded-lg cursor-pointer text-center transition-all duration-300 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-gray-400">
                                <i class="fas fa-plus-circle text-2xl text-green-600 mb-2"></i>
                                <p class="font-semibold text-gray-700">Entrada</p>
                                <p class="text-sm text-gray-500">Agregar al inventario</p>
                            </div>
                        </label>
                        
                        <label class="relative">
                            <input type="radio" 
                                   name="tipo" 
                                   value="SALIDA"
                                   class="peer sr-only"
                                   <?= old('tipo') === 'SALIDA' ? 'checked' : '' ?>
                                   required>
                            <div class="p-4 border-2 border-gray-300 rounded-lg cursor-pointer text-center transition-all duration-300 peer-checked:border-red-500 peer-checked:bg-red-50 hover:border-gray-400">
                                <i class="fas fa-minus-circle text-2xl text-red-600 mb-2"></i>
                                <p class="font-semibold text-gray-700">Salida</p>
                                <p class="text-sm text-gray-500">Retirar del inventario</p>
                            </div>
                        </label>
                    </div>
                    <?php if (form_error('tipo')): ?>
                        <span class="inv-form-error"><?= form_error('tipo') ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Cantidad -->
                <div>
                    <label for="cantidad_ajuste" class="block text-sm font-semibold text-gray-700 mb-2">
                        Cantidad <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" 
                               id="cantidad_ajuste"
                               name="cantidad" 
                               min="0.01"
                               step="0.01"
                               inputmode="decimal"
                               class="w-full px-4 py-3 pr-12 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all duration-300"
                               placeholder="0.00"
                               value="<?= old('cantidad') ?>"
                               required>
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                            <?= htmlspecialchars($unidadMedidaAjuste, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <?php if (form_error('cantidad')): ?>
                        <span class="inv-form-error"><?= form_error('cantidad') ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Motivo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Motivo del Ajuste <span class="text-red-500">*</span>
                    </label>
                    <textarea name="motivo" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all duration-300"
                              placeholder="Describe el motivo del ajuste..."
                              required><?= old('motivo') ?></textarea>
                    <?php if (form_error('motivo')): ?>
                        <span class="inv-form-error"><?= form_error('motivo') ?></span>
                    <?php endif; ?>
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Observaciones
                    </label>
                    <textarea name="observaciones"
                              rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all duration-300"
                              placeholder="Detalle opcional para auditoria..."><?= old('observaciones') ?></textarea>
                    <?php if (form_error('observaciones')): ?>
                        <span class="inv-form-error"><?= form_error('observaciones') ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Vista Previa -->
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-info-circle text-amber-600 mt-0.5"></i>
                        <div class="text-sm text-amber-800">
                            <p class="font-semibold mb-1">Vista previa del cambio:</p>
                            <p id="preview-text" class="hidden">
                                Stock actual: <span class="font-semibold"><?= htmlspecialchars($stockActualAjusteLabel, ENT_QUOTES, 'UTF-8') ?></span> -&gt;
                                Stock nuevo: <span class="font-semibold" id="nuevo-stock">?</span> <?= htmlspecialchars($unidadMedidaAjuste, ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="<?= url('inventario') ?>" 
                       class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all duration-300">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-hotel-brown text-white rounded-lg hover:bg-hotel-brown-dark transition-all duration-300 flex items-center gap-2">
                        <i class="fas fa-check"></i>
                        <span>Confirmar Ajuste</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Vista previa del stock
document.addEventListener('DOMContentLoaded', function() {
    const tipoInputs = document.querySelectorAll('input[name="tipo"]');
    const cantidadInput = document.querySelector('input[name="cantidad"]');
    const previewText = document.getElementById('preview-text');
    const stockActual = <?= json_encode($stockActualAjuste) ?>;
    const unidadMedida = <?= json_encode($unidadMedidaAjuste) ?>;
    const stockActualLabel = stockActual.toFixed(2);
    
    function actualizarPreview() {
        const tipo = document.querySelector('input[name="tipo"]:checked');
        const cantidad = parseFloat(cantidadInput.value) || 0;
        
        if (tipo && cantidad > 0) {
            let nuevoStock;
            if (tipo.value === 'ENTRADA') {
                nuevoStock = stockActual + cantidad;
            } else {
                nuevoStock = stockActual - cantidad;
            }

            const nuevoStockLabel = nuevoStock.toFixed(2);
            
            previewText.innerHTML = `Stock actual: <span class="font-semibold">${stockActualLabel}</span> &rarr; Stock nuevo: <span class="font-semibold" id="nuevo-stock">${nuevoStockLabel}</span> ${unidadMedida}`;
            previewText.classList.remove('hidden');
            const nuevoStockSpan = document.getElementById('nuevo-stock');
            
            if (nuevoStock < 0) {
                nuevoStockSpan.classList.add('text-red-600', 'font-bold');
            } else {
                nuevoStockSpan.classList.remove('text-red-600', 'font-bold');
            }
        } else {
            previewText.classList.add('hidden');
        }
    }
    
    tipoInputs.forEach(input => input.addEventListener('change', actualizarPreview));
    cantidadInput.addEventListener('input', actualizarPreview);
});
</script>
