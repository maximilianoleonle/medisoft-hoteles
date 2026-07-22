<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-playfair">Nuevo producto</h1>
            <p class="text-gray-600 mt-1">Agregar producto al inventario</p>
        </div>
        
        <div class="flex gap-2">
            <?php $back_arrow_href = back_url('inventario'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('inventario') ?>"
               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200 flex items-center ms-back-legacy">
                <i class="fas fa-arrow-left mr-2"></i>Regresar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario Principal -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg">
                <div class="p-6">
                    <form action="<?= url('inventarios/crear') ?>" method="POST" id="formNuevoProducto">
                        <?= csrf_field() ?>
                        
                        <!-- Información Básica -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-hotel-brown"></i>
                                Información Básica
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Código del Producto <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="codigo" 
                                           value="<?= old('codigo') ?>"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent"
                                           placeholder="Ej: PAPEL001">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Categoría <span class="text-red-500">*</span>
                                    </label>
                                    <select name="categoria_id" 
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                        <option value="">Seleccionar categoría...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= old('categoria_id') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= $cat['icono'] . ' ' . htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Nombre del Producto <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="nombre" 
                                       value="<?= old('nombre') ?>"
                                       required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent"
                                       placeholder="Nombre descriptivo del producto">
                            </div>
                            
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Descripción
                                </label>
                                <textarea name="descripcion" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent"
                                          placeholder="Descripción opcional del producto"><?= old('descripcion') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Stock y Costos -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-boxes mr-2 text-hotel-brown"></i>
                                Stock y Costos
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Unidad de Medida <span class="text-red-500">*</span>
                                    </label>
                                    <select name="unidad_medida_id" 
                                            required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                        <option value="">Seleccionar unidad...</option>
                                        <?php foreach ($unidades as $unidad): ?>
                                        <option value="<?= $unidad['id'] ?>" <?= old('unidad_medida_id') == $unidad['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($unidad['nombre'] . ' (' . $unidad['abreviatura'] . ')') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Ubicación en Almacén
                                    </label>
                                    <input type="text" 
                                           name="ubicacion" 
                                           value="<?= old('ubicacion') ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent"
                                           placeholder="Ej: Estante A, Nivel 2">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Stock Inicial
                                    </label>
                                    <input type="number" 
                                           name="stock_inicial" 
                                           value="<?= old('stock_inicial', '0') ?>"
                                           step="0.01"
                                           min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Stock Mínimo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           name="stock_minimo" 
                                           value="<?= old('stock_minimo') ?>"
                                           step="0.01"
                                           min="0"
                                           required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Costo Unitario <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2 text-gray-500">$</span>
                                        <input type="number" 
                                               name="costo_unitario" 
                                               value="<?= old('costo_unitario') ?>"
                                               step="0.01"
                                               min="0"
                                               required
                                               class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuración Automática -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-cogs mr-2 text-hotel-brown"></i>
                                Descuentos Automáticos
                            </h3>
                            
                            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                                <p class="text-sm text-blue-800">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Los descuentos automáticos permiten que el sistema reduzca el inventario automáticamente durante el check-in o limpieza de habitaciones.
                                </p>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="descuento_checkin" 
                                           id="descuento_checkin"
                                           value="1"
                                           <?= old('descuento_checkin') ? 'checked' : '' ?>
                                           class="rounded border-gray-300 text-hotel-brown focus:ring-hotel-brown mr-3">
                                    <label for="descuento_checkin" class="text-sm font-medium text-gray-700">
                                        Descuento automático en check-in
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="descuento_limpieza" 
                                           id="descuento_limpieza"
                                           value="1"
                                           <?= old('descuento_limpieza') ? 'checked' : '' ?>
                                           class="rounded border-gray-300 text-hotel-brown focus:ring-hotel-brown mr-3">
                                    <label for="descuento_limpieza" class="text-sm font-medium text-gray-700">
                                        Descuento automático en limpieza
                                    </label>
                                </div>
                                
                                <div id="config_limpieza" class="ml-6 <?= old('descuento_limpieza') ? '' : 'hidden' ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Cantidad por defecto para limpieza
                                    </label>
                                    <input type="number" 
                                           name="cantidad_limpieza" 
                                           value="<?= old('cantidad_limpieza', '1') ?>"
                                           step="0.01"
                                           min="0"
                                           class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Configuración por Tipo de Habitación -->
                        <div class="mb-8" id="config_habitaciones" style="display: none;">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-bed mr-2 text-hotel-brown"></i>
                                Configuración por Tipo de Habitación
                            </h3>
                            
                            <div class="bg-yellow-50 p-4 rounded-lg mb-4">
                                <p class="text-sm text-yellow-800">
                                    <i class="fas fa-lightbulb mr-1"></i>
                                    Configure las cantidades específicas que se descontarán automáticamente para cada tipo de habitación.
                                </p>
                            </div>
                            
                            <p id="descuento_config_error" class="hidden mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700" aria-live="polite"></p>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-500">Tipo de Habitación</th>
                                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Check-in</th>
                                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-500">Limpieza</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        <?php foreach ($tipos_habitacion as $tipo): ?>
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($tipo['nombre']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="number" 
                                                       name="config_habitaciones[<?= $tipo['id'] ?>][checkin]" 
                                                       step="0.01"
                                                       min="0"
                                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:ring-2 focus:ring-hotel-brown focus:border-transparent checkin-input"
                                                       placeholder="0">
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <input type="number" 
                                                       name="config_habitaciones[<?= $tipo['id'] ?>][limpieza]" 
                                                       step="0.01"
                                                       min="0"
                                                       class="w-20 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:ring-2 focus:ring-hotel-brown focus:border-transparent limpieza-input"
                                                       placeholder="0">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Botones -->
                        <div class="flex gap-3 justify-end pt-6 border-t">
                            <a href="<?= url('inventarios') ?>" 
                               class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-hotel-brown text-white rounded-lg hover:bg-hotel-brown-dark transition duration-200 flex items-center">
                                <i class="fas fa-save mr-2"></i>Guardar Producto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Panel de Ayuda -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-question-circle mr-2 text-hotel-brown"></i>
                    Ayuda
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Código del Producto</h4>
                        <p class="text-sm text-gray-600">
                            Use un código único y descriptivo. Recomendamos incluir la categoría, por ejemplo: PAPEL001, JABON002.
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Stock Mínimo</h4>
                        <p class="text-sm text-gray-600">
                            Cantidad mínima que debe mantenerse en inventario. El sistema generará alertas cuando el stock baje de este nivel.
                        </p>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Descuentos Automáticos</h4>
                        <p class="text-sm text-gray-600">
                            <strong>Check-in:</strong> Se descuenta automáticamente cuando un huésped hace check-in.<br>
                            <strong>Limpieza:</strong> Se descuenta cuando se marca una habitación como limpia.
                        </p>
                    </div>
                </div>
                
                <div class="mt-6 p-3 bg-green-50 rounded-lg">
                    <p class="text-sm text-green-800">
                        <i class="fas fa-lightbulb mr-1"></i>
                        <strong>Tip:</strong> Para productos como papel higiénico y jabón, activa los descuentos automáticos para mayor eficiencia.
                    </p>
                </div>
            </div>
            
            <!-- Preview de Configuración -->
            <div class="bg-white rounded-lg shadow-lg p-6 mt-6" id="preview_config" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-eye mr-2 text-hotel-brown"></i>
                    Vista Previa
                </h3>
                
                <div id="preview_content" class="text-sm text-gray-600">
                    <!-- Contenido dinámico generado por JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxCheckin = document.getElementById('descuento_checkin');
    const checkboxLimpieza = document.getElementById('descuento_limpieza');
    const configHabitaciones = document.getElementById('config_habitaciones');
    const configLimpieza = document.getElementById('config_limpieza');
    const previewConfig = document.getElementById('preview_config');
    
    function toggleConfiguracion() {
        const mostrarConfig = checkboxCheckin.checked || checkboxLimpieza.checked;
        
        if (mostrarConfig) {
            configHabitaciones.style.display = 'block';
            previewConfig.style.display = 'block';
        } else {
            configHabitaciones.style.display = 'none';
            previewConfig.style.display = 'none';
        }
        
        // Mostrar/ocultar configuración de limpieza
        if (checkboxLimpieza.checked) {
            configLimpieza.classList.remove('hidden');
        } else {
            configLimpieza.classList.add('hidden');
        }
        
        actualizarPreview();
        validarConfiguracionDescuentos(false);
    }
    
    function actualizarPreview() {
        const preview = document.getElementById('preview_content');
        let html = '<div class="space-y-2">';
        
        if (checkboxCheckin.checked) {
            html += '<div class="flex items-center text-blue-600"><i class="fas fa-check mr-2"></i>Descuento en check-in activado</div>';
        }
        
        if (checkboxLimpieza.checked) {
            html += '<div class="flex items-center text-green-600"><i class="fas fa-check mr-2"></i>Descuento en limpieza activado</div>';
        }
        
        if (!checkboxCheckin.checked && !checkboxLimpieza.checked) {
            html += '<div class="text-gray-500">Sin descuentos automáticos</div>';
        }
        
        html += '</div>';
        preview.innerHTML = html;
    }
    
    // Event listeners
    checkboxCheckin.addEventListener('change', toggleConfiguracion);
    checkboxLimpieza.addEventListener('change', toggleConfiguracion);
    document.querySelectorAll('.checkin-input, .limpieza-input, input[name="cantidad_limpieza"]').forEach(input => {
        input.addEventListener('input', () => validarConfiguracionDescuentos(false));
        input.addEventListener('change', () => validarConfiguracionDescuentos(false));
    });

    function tieneCantidadPositiva(selector) {
        return Array.from(document.querySelectorAll(selector)).some(input => input.value && parseFloat(input.value) > 0);
    }

    function validarConfiguracionDescuentos(enviar = false) {
        const error = document.getElementById('descuento_config_error');
        const objetivo = checkboxCheckin.checked ? checkboxCheckin : checkboxLimpieza;
        let tieneConfiguracion = true;

        if (checkboxCheckin.checked || checkboxLimpieza.checked) {
            tieneConfiguracion = false;

            if (checkboxCheckin.checked && tieneCantidadPositiva('.checkin-input')) {
                tieneConfiguracion = true;
            }

            if (checkboxLimpieza.checked) {
                const cantidadLimpieza = document.querySelector('input[name="cantidad_limpieza"]')?.value;
                if ((cantidadLimpieza && parseFloat(cantidadLimpieza) > 0) || tieneCantidadPositiva('.limpieza-input')) {
                    tieneConfiguracion = true;
                }
            }
        }

        const mensaje = tieneConfiguracion ? '' : 'Configura al menos una cantidad mayor a 0 para el descuento automatico seleccionado.';
        [checkboxCheckin, checkboxLimpieza].forEach(checkbox => {
            checkbox.setCustomValidity(checkbox === objetivo ? mensaje : '');
            checkbox.classList.toggle('ms-form-invalid', Boolean(mensaje && checkbox === objetivo));
        });

        if (error) {
            error.textContent = mensaje;
            error.classList.toggle('hidden', !mensaje);
        }

        if (mensaje && enviar) {
            objetivo.focus();
            document.getElementById('config_habitaciones')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return !mensaje;
    }
    
    // Validación del formulario
    document.getElementById('formNuevoProducto').addEventListener('submit', function(e) {
        if (!validarConfiguracionDescuentos(true)) {
            e.preventDefault();
            return false;
        }

        // Validar que si se activan descuentos automáticos, se configuren las cantidades
        if (checkboxCheckin.checked || checkboxLimpieza.checked) {
            let tieneConfiguracion = false;
            
            if (checkboxCheckin.checked) {
                document.querySelectorAll('.checkin-input').forEach(input => {
                    if (input.value && parseFloat(input.value) > 0) {
                        tieneConfiguracion = true;
                    }
                });
            }
            
            if (checkboxLimpieza.checked) {
                const cantidadLimpieza = document.querySelector('input[name="cantidad_limpieza"]').value;
                if (cantidadLimpieza && parseFloat(cantidadLimpieza) > 0) {
                    tieneConfiguracion = true;
                }
                
                document.querySelectorAll('.limpieza-input').forEach(input => {
                    if (input.value && parseFloat(input.value) > 0) {
                        tieneConfiguracion = true;
                    }
                });
            }
            
            if (!tieneConfiguracion) {
                e.preventDefault();
                return;
            }
        }
    });
    
    // Inicializar estado
    toggleConfiguracion();
});
</script>
