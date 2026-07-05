<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<!-- CSS crítico inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.entrada-view { opacity: 0; transition: opacity 0.3s ease; }
.entrada-view.loaded { opacity: 1; }
.inv-form-error {
    display: block;
    margin-top: 0.4rem;
    color: #B42318;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.35;
}
</style>

<div class="entrada-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-arrow-down"></i>
                    Entrada de Inventario
                </h1>
                <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline ms-back--glass'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('inventario') ?>"
                   class="bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white/30 transition text-sm flex items-center gap-2 ms-back-legacy">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-6 py-6 max-w-3xl">
        <form method="POST" action="<?= url('inventario/procesarEntrada') ?>" id="formEntrada">
            <?= csrf_field() ?>
            
            <!-- Formulario Compacto en una sola card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Columna Izquierda -->
                    <div class="space-y-4">
                        <!-- Producto -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Producto <span class="text-red-500">*</span>
                            </label>
                            <select name="producto_id" 
                                    id="producto_select"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                    required>
                                <option value="">Seleccione producto...</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?= $producto['id'] ?>"
                                            <?= old('producto_id') == $producto['id'] ? 'selected' : '' ?>
                                            data-stock="<?= $producto['stock_actual'] ?>"
                                            data-nombre="<?= htmlspecialchars($producto['nombre']) ?>">
                                        <?= htmlspecialchars($producto['codigo']) ?> - 
                                        <?= htmlspecialchars($producto['nombre']) ?> 
                                        (Stock: <?= $producto['stock_actual'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (form_error('producto_id')): ?>
                                <span class="inv-form-error"><?= form_error('producto_id') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Cantidad -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Cantidad <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="number" 
                                       name="cantidad" 
                                       id="cantidad_input"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500" 
                                       min="0.01"
                                       step="0.01"
                                       placeholder="0"
                                       value="<?= old('cantidad') ?>"
                                       required>
                                <button type="button" onclick="setCantidad(10)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">+10</button>
                                <button type="button" onclick="setCantidad(25)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">+25</button>
                                <button type="button" onclick="setCantidad(50)" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm">+50</button>
                            </div>
                            <?php if (form_error('cantidad')): ?>
                                <span class="inv-form-error"><?= form_error('cantidad') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Motivo <span class="text-red-500">*</span>
                            </label>
                            <textarea name="motivo" 
                                      rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 resize-none" 
                                      placeholder="Ej: Compra mensual, Reposición de stock..."
                                      required><?= old('motivo') ?></textarea>
                            <?php if (form_error('motivo')): ?>
                                <span class="inv-form-error"><?= form_error('motivo') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Columna Derecha: Vista Previa -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-3 text-sm">Vista Previa</h3>
                        <div id="preview-panel" class="space-y-3">
                            <div class="text-center text-gray-400 py-8">
                                <i class="fas fa-box-open text-4xl mb-2"></i>
                                <p class="text-sm">Seleccione un producto</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="flex gap-3 mt-6 pt-6 border-t">
                    <button type="submit" 
                            class="flex-1 bg-emerald-500 text-white px-4 py-2.5 rounded-lg hover:bg-emerald-600 transition flex items-center justify-center gap-2 font-medium">
                        <i class="fas fa-check"></i>
                        Registrar Entrada
                    </button>
                    <a href="<?= url('inventario') ?>" 
                       class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Función para establecer cantidad
function setCantidad(valor) {
    const input = document.getElementById('cantidad_input');
    const currentValue = parseFloat(input.value) || 0;
    input.value = currentValue + valor;
    updatePreview();
}

// Vista previa del producto seleccionado
document.getElementById('producto_select').addEventListener('change', function(e) {
    updatePreview();
});

document.getElementById('cantidad_input').addEventListener('input', function(e) {
    updatePreview();
});

function updatePreview() {
    const productoSelect = document.getElementById('producto_select');
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const previewPanel = document.getElementById('preview-panel');
    const cantidad = parseFloat(document.getElementById('cantidad_input').value) || 0;
    
    if (productoSelect.value) {
        const stock = parseFloat(selectedOption.getAttribute('data-stock'));
        const nombre = selectedOption.getAttribute('data-nombre');
        const nuevoStock = stock + cantidad;
        const stockLabel = stock.toFixed(2);
        const nuevoStockLabel = nuevoStock.toFixed(2);
        
        previewPanel.innerHTML = `
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-500">Producto</p>
                    <p class="font-medium text-gray-900">${nombre}</p>
                </div>
                <div class="flex justify-between items-center py-2 border-t border-b">
                    <span class="text-sm text-gray-600">Stock actual</span>
                    <span class="font-bold text-gray-900">${stockLabel}</span>
                </div>
                ${cantidad > 0 ? `
                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-emerald-700">Stock después</span>
                        <span class="text-lg font-bold text-emerald-900">${nuevoStockLabel}</span>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    } else {
        previewPanel.innerHTML = `
            <div class="text-center text-gray-400 py-8">
                <i class="fas fa-box-open text-4xl mb-2"></i>
                <p class="text-sm">Seleccione un producto</p>
            </div>
        `;
    }
}

// Validación del formulario
// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.entrada-view').classList.add('loaded');
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
