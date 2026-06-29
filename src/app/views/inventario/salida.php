<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<!-- CSS crítico inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.salida-view { opacity: 0; transition: opacity 0.3s ease; }
.salida-view.loaded { opacity: 1; }
.inv-form-error {
    display: block;
    margin-top: 0.4rem;
    color: #B42318;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.35;
}
</style>

<div class="salida-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-arrow-up"></i>
                    Salida de Inventario
                </h1>
                <a href="<?= back_url('inventario') ?>"
                   class="bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white/30 transition text-sm flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-6 py-6 max-w-3xl">
        <form method="POST" action="<?= url('inventario/procesarSalida') ?>" id="formSalida">
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
                                    id="producto_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
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
                        
                        <!-- Cantidad y Habitación en la misma fila -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Cantidad <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="cantidad" 
                                       id="cantidad"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500" 
                                       min="0.01"
                                       step="0.01"
                                       value="<?= old('cantidad') ?>"
                                       required>
                                <small class="text-gray-500" id="stock_info"></small>
                                <small class="text-red-600 font-semibold hidden" id="stock_error" aria-live="polite"></small>
                                <?php if (form_error('cantidad')): ?>
                                    <span class="inv-form-error"><?= form_error('cantidad') ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Habitación
                                </label>
                                <select name="habitacion_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                                    <option value="">Sin habitación</option>
                                    <?php foreach ($habitaciones as $habitacion): ?>
                                        <option value="<?= $habitacion['id'] ?>" <?= old('habitacion_id') == $habitacion['id'] ? 'selected' : '' ?>>
                                            Hab. <?= htmlspecialchars($habitacion['numero']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (form_error('habitacion_id')): ?>
                                    <span class="inv-form-error"><?= form_error('habitacion_id') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Tipo de Motivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Tipo de salida
                            </label>
                            <select name="motivo_tipo" 
                                    id="motivo_tipo"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                                <option value="manual" <?= old('motivo_tipo', 'manual') === 'manual' ? 'selected' : '' ?>>Salida manual</option>
                                <option value="solicitud" <?= old('motivo_tipo') === 'solicitud' ? 'selected' : '' ?>>Solicitud de huésped</option>
                                <option value="limpieza" <?= old('motivo_tipo') === 'limpieza' ? 'selected' : '' ?>>Limpieza/Mantenimiento</option>
                                <option value="otro" <?= old('motivo_tipo') === 'otro' ? 'selected' : '' ?>>Otro motivo</option>
                            </select>
                        </div>
                        
                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Detalles <span class="text-red-500">*</span>
                            </label>
                            <textarea name="motivo" 
                                      id="motivo"
                                      rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 resize-none" 
                                      placeholder="Especifique el motivo..."
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
                            class="flex-1 bg-orange-500 text-white px-4 py-2.5 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2 font-medium">
                        <i class="fas fa-check"></i>
                        Registrar Salida
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
// Mostrar stock disponible al seleccionar producto - FUNCIÓN ORIGINAL
document.getElementById('producto_id').addEventListener('change', function() {
    updatePreview();
    
    const selectedOption = this.options[this.selectedIndex];
    const stock = selectedOption.getAttribute('data-stock');
    const stockInfo = document.getElementById('stock_info');
    const cantidadInput = document.getElementById('cantidad');
    
    if (stock) {
        stockInfo.textContent = `Stock: ${stock}`;
        cantidadInput.max = stock;
    } else {
        stockInfo.textContent = '';
    }

    validarStockSalida();
});

// Actualizar cuando cambie la cantidad
document.getElementById('cantidad').addEventListener('input', function() {
    updatePreview();
    validarStockSalida();
});

// Vista previa actualizada
function updatePreview() {
    const productoSelect = document.getElementById('producto_id');
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const previewPanel = document.getElementById('preview-panel');
    const cantidad = parseFloat(document.getElementById('cantidad').value) || 0;
    
    if (productoSelect.value) {
        const stock = parseFloat(selectedOption.getAttribute('data-stock'));
        const nombre = selectedOption.getAttribute('data-nombre');
        const stockFinal = stock - cantidad;
        const stockLabel = stock.toFixed(2);
        const stockFinalLabel = stockFinal.toFixed(2);
        
        let estadoHtml = '';
        if (stock === 0) {
            estadoHtml = '<div class="text-center text-red-600 font-semibold">Sin Stock</div>';
        } else if (stockFinal < 0) {
            estadoHtml = '<div class="text-center text-red-600 font-semibold">Stock Insuficiente</div>';
        }
        
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
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-orange-700">Stock después</span>
                        <span class="text-lg font-bold ${stockFinal < 0 ? 'text-red-600' : 'text-orange-900'}">${stockFinalLabel}</span>
                    </div>
                </div>
                ${estadoHtml}
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

// Actualizar texto del motivo según el tipo - FUNCIÓN ORIGINAL
document.getElementById('motivo_tipo').addEventListener('change', function() {
    const motivoTextarea = document.getElementById('motivo');
    switch(this.value) {
        case 'solicitud':
            motivoTextarea.placeholder = 'Ej: Huésped de habitación 101 solicitó...';
            break;
        case 'limpieza':
            motivoTextarea.placeholder = 'Ej: Para limpieza de habitaciones...';
            break;
        default:
            motivoTextarea.placeholder = 'Especifique el motivo...';
    }
});

// Validación del formulario
document.getElementById('formSalida').addEventListener('submit', function(e) {
    if (!validarStockSalida(true)) {
        e.preventDefault();
        return false;
    }
});

function validarStockSalida(enviar = false) {
    const productoSelect = document.getElementById('producto_id');
    const cantidadInput = document.getElementById('cantidad');
    const stockError = document.getElementById('stock_error');
    const cantidad = parseFloat(cantidadInput.value) || 0;
    const selectedOption = productoSelect.options[productoSelect.selectedIndex];
    const stockActual = parseFloat(selectedOption.getAttribute('data-stock')) || 0;
    let mensaje = '';

    if (productoSelect.value && cantidad > stockActual) {
        mensaje = `No hay suficiente stock. Disponible: ${stockActual.toFixed(2)}.`;
    }

    cantidadInput.setCustomValidity(mensaje);
    cantidadInput.classList.toggle('ms-form-invalid', Boolean(mensaje));

    if (mensaje) {
        cantidadInput.setAttribute('aria-invalid', 'true');
    } else {
        cantidadInput.removeAttribute('aria-invalid');
        const errorId = cantidadInput.dataset.msErrorId;
        if (errorId) {
            document.getElementById(errorId)?.remove();
            const describedBy = String(cantidadInput.getAttribute('aria-describedby') || '')
                .split(/\s+/)
                .filter(Boolean)
                .filter(id => id !== errorId)
                .join(' ');
            if (describedBy) {
                cantidadInput.setAttribute('aria-describedby', describedBy);
            } else {
                cantidadInput.removeAttribute('aria-describedby');
            }
        }
        if (document.getElementById('formSalida')?.checkValidity()) {
            document.getElementById('formSalida')?.querySelector('.ms-form-error-summary')?.remove();
        }
    }

    if (stockError) {
        stockError.textContent = mensaje;
        stockError.classList.toggle('hidden', !mensaje);
    }

    if (mensaje && enviar) {
        cantidadInput.focus();
    }

    return !mensaje;
}

// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.salida-view').classList.add('loaded');
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
