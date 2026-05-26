<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<!-- CSS crítico inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.editar-producto-view { opacity: 0; transition: opacity 0.3s ease; }
.editar-producto-view.loaded { opacity: 1; }
</style>

<div class="editar-producto-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    Editar Producto
                </h1>
                <div class="flex items-center gap-3">
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm">
                        ID: <?= $producto['id'] ?>
                    </span>
                    <a href="<?= url('inventario') ?>" 
                       class="bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white/30 transition text-sm flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-6 py-6 max-w-4xl">
        <form method="POST" action="<?= url('inventario/actualizar/' . $producto['id']) ?>" id="formEditarProducto">
            <?= csrf_field() ?>
            
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Formulario Principal (2 columnas) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-box text-hotel-brown"></i>
                            Información del Producto
                        </h2>
                        
                        <!-- Código y Nombre -->
                        <div class="grid md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Código <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="codigo" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown uppercase" 
                                       value="<?= htmlspecialchars($producto['codigo']) ?>"
                                       pattern="[A-Za-z0-9]{3,20}"
                                       title="Solo letras y números, 3-20 caracteres"
                                       required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown" 
                                       value="<?= htmlspecialchars($producto['nombre']) ?>"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Categoría y Unidad -->
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Categoría <span class="text-red-500">*</span>
                                </label>
                                <select name="categoria_id" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown" 
                                        required>
                                    <option value="">Seleccione categoría...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>" 
                                                <?= ($producto['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Unidad de Medida
                                </label>
                                <select name="unidad_medida" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown">
                                    <option value="pieza" <?= ($producto['unidad_medida'] == 'pieza') ? 'selected' : '' ?>>Pieza</option>
                                    <option value="rollo" <?= ($producto['unidad_medida'] == 'rollo') ? 'selected' : '' ?>>Rollo</option>
                                    <option value="caja" <?= ($producto['unidad_medida'] == 'caja') ? 'selected' : '' ?>>Caja</option>
                                    <option value="paquete" <?= ($producto['unidad_medida'] == 'paquete') ? 'selected' : '' ?>>Paquete</option>
                                    <option value="litro" <?= ($producto['unidad_medida'] == 'litro') ? 'selected' : '' ?>>Litro</option>
                                    <option value="kilogramo" <?= ($producto['unidad_medida'] == 'kilogramo') ? 'selected' : '' ?>>Kilogramo</option>
                                    <option value="unidad" <?= ($producto['unidad_medida'] == 'unidad') ? 'selected' : '' ?>>Unidad</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Stock y Costo -->
                        <div class="grid md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Actual
                                </label>
                                <input type="number" 
                                       class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed" 
                                       value="<?= $producto['stock_actual'] ?>" 
                                       readonly 
                                       disabled>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Mínimo <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="stock_minimo" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown" 
                                       value="<?= $producto['stock_minimo'] ?>"
                                       min="0" 
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Costo Unitario
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                    <input type="number" 
                                           name="costo_unitario" 
                                           class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown" 
                                           value="<?= $producto['costo_unitario'] ?>"
                                           step="0.01" 
                                           min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Descripción -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea name="descripcion" 
                                      rows="2" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown resize-none" 
                                      placeholder="Detalles adicionales del producto..."><?= htmlspecialchars($producto['descripcion'] ?? '') ?></textarea>
                        </div>
                        
                        <!-- Descuento Automático -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" 
                                       name="descuento_automatico" 
                                       id="descuento_automatico"
                                       value="1"
                                       <?= $producto['descuento_automatico'] ? 'checked' : '' ?>
                                       class="mt-1 mr-3">
                                <div>
                                    <span class="font-medium text-gray-900">Descuento Automático en Check-in</span>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Active si este producto se descuenta automáticamente cuando un huésped hace check-in
                                    </p>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="flex gap-3 pt-4 border-t">
                            <button type="submit" 
                                    class="flex-1 bg-hotel-brown text-white px-4 py-2.5 rounded-lg hover:bg-hotel-brown-dark transition flex items-center justify-center gap-2 font-medium">
                                <i class="fas fa-save"></i>
                                Guardar Cambios
                            </button>
                            <a href="<?= url('inventario') ?>" 
                               class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Estado (1 columna) -->
                <div class="lg:col-span-1 space-y-4">
                    <!-- Estado del Stock -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                        <?php
                        $porcentaje_stock = $producto['stock_minimo'] > 0 ? 
                                           ($producto['stock_actual'] / $producto['stock_minimo']) * 100 : 100;
                        $clase_stock = 'emerald';
                        $texto_stock = 'Stock Óptimo';
                        $icono_stock = 'fa-check-circle';
                        
                        if ($porcentaje_stock <= 0) {
                            $clase_stock = 'red';
                            $texto_stock = 'Sin Stock';
                            $icono_stock = 'fa-times-circle';
                        } elseif ($porcentaje_stock <= 50) {
                            $clase_stock = 'amber';
                            $texto_stock = 'Stock Crítico';
                            $icono_stock = 'fa-exclamation-triangle';
                        } elseif ($porcentaje_stock <= 100) {
                            $clase_stock = 'blue';
                            $texto_stock = 'Stock Bajo';
                            $icono_stock = 'fa-info-circle';
                        }
                        ?>
                        
                        <div class="text-center">
                            <i class="fas <?= $icono_stock ?> text-<?= $clase_stock ?>-500 text-4xl mb-3"></i>
                            <h3 class="text-3xl font-bold text-gray-900"><?= number_format($producto['stock_actual'], 0) ?></h3>
                            <p class="text-sm text-gray-600 mb-2">Unidades en Stock</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-<?= $clase_stock ?>-100 text-<?= $clase_stock ?>-800">
                                <?= $texto_stock ?>
                            </span>
                        </div>
                        
                        <div class="mt-4 bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-<?= $clase_stock ?>-500 h-2 rounded-full transition-all" 
                                 style="width: <?= min($porcentaje_stock, 100) ?>%"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mt-4 pt-4 border-t">
                            <a href="<?= url('inventario/entrada?producto_id=' . $producto['id']) ?>" 
                               class="bg-emerald-500 text-white px-3 py-2 rounded-lg hover:bg-emerald-600 transition text-sm flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i>
                                Entrada
                            </a>
                            <a href="<?= url('inventario/salida?producto_id=' . $producto['id']) ?>" 
                               class="bg-orange-500 text-white px-3 py-2 rounded-lg hover:bg-orange-600 transition text-sm flex items-center justify-center gap-2">
                                <i class="fas fa-minus"></i>
                                Salida
                            </a>
                        </div>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="bg-gray-50 rounded-xl p-4 text-sm">
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Creado:</span>
                                <span class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($producto['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Actualizado:</span>
                                <span class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($producto['updated_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Estado:</span>
                                <?php if ($producto['activo']): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Activo
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactivo
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Alertas -->
                    <?php if ($producto['stock_actual'] <= 0): ?>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 text-xl flex-shrink-0"></i>
                            <div class="text-sm">
                                <p class="text-red-800 font-medium">Sin Stock</p>
                                <p class="text-red-700 mt-1">Este producto está agotado. Registre una entrada.</p>
                            </div>
                        </div>
                    </div>
                    <?php elseif ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <div class="flex gap-3">
                            <i class="fas fa-exclamation-triangle text-amber-600 text-xl flex-shrink-0"></i>
                            <div class="text-sm">
                                <p class="text-amber-800 font-medium">Stock Bajo</p>
                                <p class="text-amber-700 mt-1">Por debajo del mínimo establecido.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($producto['descuento_automatico']): ?>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="text-sm">
                            <p class="text-blue-800 font-medium mb-1">Descuento Automático Activo</p>
                            <p class="text-blue-700">
                                Se descuenta en check-in. 
                                <a href="<?= url('inventario/configuracion') ?>" class="underline">
                                    Ver configuración
                                </a>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-uppercase para el código - FUNCIÓN ORIGINAL
document.querySelector('input[name="codigo"]').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Confirmación antes de guardar si cambia descuento automático - FUNCIÓN ORIGINAL
document.getElementById('formEditarProducto').addEventListener('submit', function(e) {
    const descuentoOriginal = <?= $producto['descuento_automatico'] ? 'true' : 'false' ?>;
    const descuentoActual = document.getElementById('descuento_automatico').checked;
    
    if (descuentoOriginal !== descuentoActual) {
        if (!confirm('Está cambiando la configuración de descuento automático. ¿Desea continuar?')) {
            e.preventDefault();
        }
    }
});

// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.editar-producto-view').classList.add('loaded');
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>