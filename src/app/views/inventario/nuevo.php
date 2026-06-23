<?php require_once APP_PATH . '/views/layout/header.php'; ?>
<?php
$unidadesMedida = is_array($unidadesMedida ?? null) && !empty($unidadesMedida)
    ? $unidadesMedida
    : [
        'pieza' => 'Pieza',
        'rollo' => 'Rollo',
        'caja' => 'Caja',
        'paquete' => 'Paquete',
        'litro' => 'Litro',
        'kilogramo' => 'Kilogramo',
        'unidad' => 'Unidad',
    ];

$unidadSeleccionada = (string) old('unidad_medida', 'pieza');
if (!array_key_exists($unidadSeleccionada, $unidadesMedida)) {
    foreach ($unidadesMedida as $unidadKey => $unidadLabel) {
        $unidadSeleccionada = (string) $unidadKey;
        break;
    }
}

$categoriaSeleccionada = (string) old('categoria_id', '');
$descuentoAutomaticoActivo = old('descuento_automatico', '') !== '';
?>

<!-- CSS crítico inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.nuevo-producto-view { opacity: 0; transition: opacity 0.3s ease; }
.nuevo-producto-view.loaded { opacity: 1; }
</style>

<div class="nuevo-producto-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i>
                    Nuevo Producto
                </h1>
                <a href="<?= back_url('inventario') ?>"
                   class="bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white/30 transition text-sm flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-6 py-6 max-w-4xl">
        <form method="POST" action="<?= url('inventario/guardar') ?>" id="formNuevoProducto">
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
                                       placeholder="PAP001"
                                       value="<?= old('codigo') ?>"
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
                                       placeholder="Papel Higiénico"
                                       value="<?= old('nombre') ?>"
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
                                        <option value="<?= $categoria['id'] ?>" <?= $categoriaSeleccionada === (string)$categoria['id'] ? 'selected' : '' ?>>
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
                                    <?php foreach ($unidadesMedida as $unidadKey => $unidadLabel): ?>
                                        <?php $unidadKey = (string) $unidadKey; ?>
                                        <option value="<?= htmlspecialchars($unidadKey, ENT_QUOTES, 'UTF-8') ?>" <?= $unidadSeleccionada === $unidadKey ? 'selected' : '' ?>>
                                            <?= htmlspecialchars((string) $unidadLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Stock y Costo -->
                        <div class="grid md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Inicial <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="stock_inicial"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown"
                                       value="<?= old('stock_inicial', 0) ?>"
                                       min="0"
                                       required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Stock Mínimo <span class="text-red-500">*</span>
                                </label>
                                <input type="number"
                                       name="stock_minimo"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown"
                                       value="<?= old('stock_minimo', 10) ?>"
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
                                           step="0.01"
                                           min="0"
                                           placeholder="0.00"
                                           value="<?= old('costo_unitario') ?>">
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
                                      placeholder="Detalles adicionales del producto..."><?= old('descripcion') ?></textarea>
                        </div>

                        <!-- Descuento Automático -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox"
                                       name="descuento_automatico"
                                       value="1"
                                       <?= $descuentoAutomaticoActivo ? 'checked' : '' ?>
                                       class="mt-1 mr-3">
                                <div>
                                    <span class="font-medium text-gray-900">Descuento Automático en Check-in</span>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Active si este producto se descuenta automáticamente cuando un huésped hace check-in
                                        (papel higiénico, jabón, etc.)
                                    </p>
                                </div>
                            </label>
                        </div>

                        <!-- Botones de acción -->
                        <div class="flex gap-3 pt-4 border-t">
                            <button type="submit"
                                    class="flex-1 bg-hotel-brown text-white px-4 py-2.5 rounded-lg hover:bg-hotel-brown-dark transition flex items-center justify-center gap-2 font-medium">
                                <i class="fas fa-save"></i>
                                Guardar Producto
                            </button>
                            <a href="<?= url('inventario') ?>"
                               class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Panel de Ayuda (1 columna) -->
                <div class="lg:col-span-1">
                    <!-- Ayuda Rápida -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4">
                        <h3 class="font-semibold text-blue-900 mb-2 flex items-center gap-2 text-sm">
                            <i class="fas fa-info-circle"></i>
                            Ayuda Rápida
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="font-medium text-blue-900">Código único</p>
                                <p class="text-blue-700">Ej: PAP001, JAB001, TOA001</p>
                            </div>
                            <div>
                                <p class="font-medium text-blue-900">Stock mínimo</p>
                                <p class="text-blue-700">Se alertará al llegar a este nivel</p>
                            </div>
                            <div>
                                <p class="font-medium text-blue-900">Descuento automático</p>
                                <p class="text-blue-700">Solo para productos de cortesía</p>
                            </div>
                        </div>
                    </div>

                    <!-- Categorías disponibles -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="bg-gray-50 px-4 py-3 border-b">
                            <h3 class="font-medium text-gray-900 text-sm flex items-center gap-2">
                                <i class="fas fa-tags"></i>
                                Categorías
                            </h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <?php foreach ($categorias as $categoria): ?>
                                <div class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            ID: <?= $categoria['id'] ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($categoria['descripcion'])): ?>
                                        <p class="text-xs text-gray-600 mt-1">
                                            <?= htmlspecialchars($categoria['descripcion']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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

// Validación del formulario
// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.nuevo-producto-view').classList.add('loaded');
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
