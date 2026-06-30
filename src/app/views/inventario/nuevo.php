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
.inv-form-error {
    display: block;
    margin-top: 0.4rem;
    color: #B42318;
    font-size: 0.78rem;
    font-weight: 700;
    line-height: 1.35;
}

.nuevo-producto-view {
    --inv-brand: var(--brand-primary, #1B2746);
    --inv-brand-2: var(--brand-secondary, #0F172A);
    --inv-accent: var(--brand-accent, #BD9441);
    --inv-accent-dark: color-mix(in srgb, var(--inv-accent) 72%, #3F2E12);
    --inv-ivory: color-mix(in srgb, var(--inv-accent) 8%, #F8F5ED);
    --inv-ivory-2: color-mix(in srgb, var(--inv-accent) 5%, #FCFAF5);
    --inv-surface: color-mix(in srgb, var(--inv-accent) 2%, #FFFFFF);
    --inv-surface-warm: color-mix(in srgb, var(--inv-accent) 5%, #FFFFFF);
    --inv-line: color-mix(in srgb, var(--inv-accent) 20%, #E7DEC9);
    --inv-muted: color-mix(in srgb, var(--inv-brand-2) 48%, #94A3B8);
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--inv-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--inv-ivory-2), var(--inv-ivory) 58%, #F7F2EA) !important;
    color: var(--inv-brand-2);
    padding-top: 10px !important;
    padding-bottom: 18px !important;
}

.nuevo-producto-view > .bg-gradient-to-r {
    width: min(100%, 1280px);
    margin: 0 auto 10px;
    padding: 0 16px;
    background: transparent !important;
    color: var(--inv-brand-2) !important;
    box-shadow: none !important;
}

.nuevo-producto-view > .bg-gradient-to-r .container,
.nuevo-producto-view > .container {
    max-width: 1080px;
}

.nuevo-producto-view > .container {
    padding: 0 16px 18px !important;
}

.nuevo-producto-view > .bg-gradient-to-r .container {
    padding: 0 !important;
}

.nuevo-producto-view h1 {
    color: var(--inv-brand-2);
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(1.75rem, 3vw, 2.35rem);
    font-weight: 650;
    line-height: .96;
}

.nuevo-producto-view h1 i {
    width: 42px;
    height: 42px;
    display: inline-grid;
    place-items: center;
    border-radius: 14px;
    background: linear-gradient(150deg, var(--inv-brand), var(--inv-brand-2));
    color: #FFFFFF;
    font-size: 1rem;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--inv-brand) 58%, transparent);
}

.nuevo-producto-view > .bg-gradient-to-r a {
    min-height: 36px;
    border: 1px solid var(--inv-line);
    border-radius: 13px;
    background: var(--inv-surface) !important;
    color: var(--inv-brand-2) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--inv-brand-2) 4%, transparent);
}

.nuevo-producto-view form > .grid {
    align-items: start;
    gap: 14px !important;
}

.nuevo-producto-view form > .grid > div:first-child > .bg-white,
.nuevo-producto-view form > .grid > div:last-child > .bg-white,
.nuevo-producto-view form > .grid > div:last-child > .bg-blue-50 {
    border: 1px solid var(--inv-line) !important;
    border-radius: 18px !important;
    background: var(--inv-surface) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--inv-brand-2) 4%, transparent), 0 14px 32px -24px color-mix(in srgb, var(--inv-brand-2) 34%, transparent) !important;
}

.nuevo-producto-view form > .grid > div:first-child > .bg-white {
    padding: 18px !important;
}

.nuevo-producto-view form > .grid > div:last-child > .bg-white,
.nuevo-producto-view form > .grid > div:last-child > .bg-blue-50 {
    padding: 13px !important;
    margin-bottom: 12px !important;
}

.nuevo-producto-view h2 {
    margin-bottom: 12px !important;
    font-size: 1rem !important;
}

.nuevo-producto-view h3 {
    margin-bottom: 8px !important;
    font-size: .84rem !important;
}

.nuevo-producto-view .grid[class*="md:grid-cols-2"],
.nuevo-producto-view .grid[class*="md:grid-cols-3"] {
    gap: 12px !important;
    margin-bottom: 12px !important;
}

.nuevo-producto-view .mb-4 {
    margin-bottom: 12px !important;
}

.nuevo-producto-view form > .grid > div:last-child [class*="px-4"][class*="py-3"] {
    padding: 9px 12px !important;
}

.nuevo-producto-view h2,
.nuevo-producto-view h3 {
    color: var(--inv-brand-2);
    letter-spacing: 0;
}

.nuevo-producto-view label {
    color: color-mix(in srgb, var(--inv-brand-2) 72%, #64748B) !important;
    font-weight: 800 !important;
}

.nuevo-producto-view input:not([type="checkbox"]),
.nuevo-producto-view select,
.nuevo-producto-view textarea {
    min-height: 40px;
    border-color: var(--inv-line) !important;
    border-radius: 12px !important;
    background: color-mix(in srgb, var(--inv-accent) 2%, #FFFEFB) !important;
    color: var(--inv-brand-2);
    font-size: .82rem;
    font-weight: 650;
}

.nuevo-producto-view input:not([type="checkbox"]):focus,
.nuevo-producto-view select:focus,
.nuevo-producto-view textarea:focus {
    border-color: color-mix(in srgb, var(--inv-accent) 72%, var(--inv-brand)) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--inv-accent) 17%, transparent) !important;
}

.nuevo-producto-view textarea {
    line-height: 1.32;
}

.nuevo-producto-view .bg-amber-50 {
    border-color: color-mix(in srgb, var(--inv-accent) 24%, #E8DDCA) !important;
    background: color-mix(in srgb, var(--inv-accent) 9%, #FFFFFF) !important;
    padding: 12px !important;
    margin-bottom: 12px !important;
}

.nuevo-producto-view .bg-amber-50 p {
    line-height: 1.35;
}

.nuevo-producto-view .flex.gap-2.pt-2 {
    padding-top: 12px !important;
}

.nuevo-producto-view button[type="submit"] {
    background: linear-gradient(145deg, var(--inv-brand), var(--inv-brand-2)) !important;
    box-shadow: 0 14px 28px -18px color-mix(in srgb, var(--inv-brand) 68%, transparent);
}

@media (max-width: 720px) {
    .nuevo-producto-view {
        padding-top: 10px !important;
        padding-bottom: 14px !important;
    }

    .nuevo-producto-view > .bg-gradient-to-r {
        margin-bottom: 8px;
        padding: 0 10px;
    }

    .nuevo-producto-view > .bg-gradient-to-r .flex {
        align-items: flex-start;
        gap: 8px;
    }

    .nuevo-producto-view h1 {
        align-items: center;
        gap: 8px;
        font-size: 1.32rem !important;
        line-height: .98;
    }

    .nuevo-producto-view h1 i {
        width: 36px;
        height: 36px;
        border-radius: 11px;
        font-size: .86rem;
    }

    .nuevo-producto-view > .bg-gradient-to-r a {
        min-height: 34px;
        padding: 0 8px;
        border-radius: 11px;
        font-size: .7rem;
        line-height: 1.1;
    }

    .nuevo-producto-view > .container {
        padding: 0 8px 14px !important;
    }

    .nuevo-producto-view form > .grid {
        gap: 6px !important;
    }

    .nuevo-producto-view form > .grid > div:first-child > .bg-white {
        padding: 8px !important;
        border-radius: 14px !important;
        box-shadow: 0 10px 24px rgba(24, 33, 46, .07) !important;
    }

    .nuevo-producto-view form > .grid > div:last-child {
        display: none;
    }

    .nuevo-producto-view h2 {
        margin-bottom: 7px !important;
        font-size: .82rem !important;
        line-height: 1.1;
    }

    .nuevo-producto-view h2 i {
        width: 26px;
        height: 26px;
        display: inline-grid;
        place-items: center;
        border-radius: 10px;
        background: color-mix(in srgb, var(--inv-accent) 12%, #FFFDF8);
        font-size: .72rem;
    }

    .nuevo-producto-view .grid[class*="md:grid-cols-2"],
    .nuevo-producto-view .grid[class*="md:grid-cols-3"] {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px !important;
        margin-bottom: 6px !important;
        align-items: start;
        grid-auto-rows: min-content;
    }

    .nuevo-producto-view form > .grid > div:first-child > .bg-white > .grid[class*="md:grid-cols-3"]:first-of-type {
        grid-template-columns: 1fr;
        gap: 8px !important;
    }

    .nuevo-producto-view form > .grid > div:first-child > .bg-white > .grid[class*="md:grid-cols-3"]:first-of-type [class*="md:col-span-2"] {
        grid-column: auto;
    }

    .nuevo-producto-view [class*="md:col-span-2"] {
        grid-column: 1 / -1;
    }

    .nuevo-producto-view label {
        margin-bottom: 3px !important;
        font-size: .58rem !important;
        line-height: 1.1;
    }

    .nuevo-producto-view form > .grid > div:first-child > .bg-white .grid > div,
    .nuevo-producto-view form > .grid > div:first-child > .bg-white > .mb-4 {
        min-height: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }

    .nuevo-producto-view input:not([type="checkbox"]),
    .nuevo-producto-view select {
        min-height: 44px;
        padding: 7px 10px !important;
        font-size: 13px;
        line-height: 1.15;
    }

    .nuevo-producto-view select {
        font-size: 12.5px;
        text-overflow: ellipsis;
    }

    .nuevo-producto-view textarea {
        min-height: 68px;
        padding: 8px 10px !important;
        font-size: 13px;
        line-height: 1.32;
    }

    .nuevo-producto-view .relative span {
        left: 0;
        padding-left: 10px !important;
        font-size: .78rem;
    }

    .nuevo-producto-view input[name="costo_unitario"] {
        padding-left: 28px !important;
    }

    .nuevo-producto-view .bg-amber-50 {
        margin-bottom: 7px !important;
        padding: 8px !important;
        border-radius: 12px !important;
    }

    .nuevo-producto-view .bg-amber-50 label {
        align-items: flex-start;
    }

    .nuevo-producto-view .bg-amber-50 span {
        font-size: .78rem;
        line-height: 1.2;
    }

    .nuevo-producto-view .bg-amber-50 p {
        margin-top: 2px !important;
        font-size: .68rem !important;
        line-height: 1.22;
    }

    .nuevo-producto-view .flex.gap-2.pt-2 {
        display: grid !important;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr);
        gap: 6px !important;
        padding-top: 7px !important;
    }

    .nuevo-producto-view button[type="submit"],
    .nuevo-producto-view .flex.gap-2.pt-2 a {
        width: 100%;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px !important;
        padding: 0 10px !important;
        font-size: .72rem;
        line-height: 1.1;
    }
}

@media (max-width: 380px) {
    .nuevo-producto-view .grid[class*="md:grid-cols-2"],
    .nuevo-producto-view .grid[class*="md:grid-cols-3"],
    .nuevo-producto-view form > .grid > div:first-child > .bg-white > .grid,
    .nuevo-producto-view .flex.gap-2.pt-2 {
        grid-template-columns: 1fr;
    }
}
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

    <div class="container mx-auto px-4 py-2 max-w-4xl">
        <form method="POST" action="<?= url('inventario/guardar') ?>" id="formNuevoProducto">
            <?= csrf_field() ?>

            <div class="grid lg:grid-cols-3 gap-3">
                <!-- Formulario Principal (2 columnas) -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-3 sm:p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2 flex items-center gap-2">
                            <i class="fas fa-box text-hotel-brown"></i>
                            Información del Producto
                        </h2>

                        <!-- Código y Nombre -->
                        <div class="grid md:grid-cols-3 gap-2 mb-2">
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
                                <?php if (form_error('codigo')): ?>
                                    <span class="inv-form-error"><?= form_error('codigo') ?></span>
                                <?php endif; ?>
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
                                <?php if (form_error('nombre')): ?>
                                    <span class="inv-form-error"><?= form_error('nombre') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Categoría y Unidad -->
                        <div class="grid grid-cols-2 gap-2 mb-2">
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
                                <?php if (form_error('categoria_id')): ?>
                                    <span class="inv-form-error"><?= form_error('categoria_id') ?></span>
                                <?php endif; ?>
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
                                <?php if (form_error('unidad_medida')): ?>
                                    <span class="inv-form-error"><?= form_error('unidad_medida') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Stock y Costo -->
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-2">
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
                                <?php if (form_error('stock_inicial')): ?>
                                    <span class="inv-form-error"><?= form_error('stock_inicial') ?></span>
                                <?php endif; ?>
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
                                <?php if (form_error('stock_minimo')): ?>
                                    <span class="inv-form-error"><?= form_error('stock_minimo') ?></span>
                                <?php endif; ?>
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
                                <?php if (form_error('costo_unitario')): ?>
                                    <span class="inv-form-error"><?= form_error('costo_unitario') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Descripción
                            </label>
                            <textarea name="descripcion"
                                      rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown resize-none"
                                      placeholder="Detalles adicionales del producto..."><?= old('descripcion') ?></textarea>
                            <?php if (form_error('descripcion')): ?>
                                <span class="inv-form-error"><?= form_error('descripcion') ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Descuento Automático -->
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 mb-2">
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
                        <div class="flex gap-2 pt-2 border-t">
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
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 mb-3">
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
