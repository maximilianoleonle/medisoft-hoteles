<!-- Crear Nuevo Huésped - Diseño Moderno -->

<?php
// Verificar si viene de reservación rápida
$return_to = $_GET['return_to'] ?? null;
$es_reservacion_rapida = $return_to === 'reservacion_rapida';
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 shadow-sm">
        <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4">
            <!-- Breadcrumb -->
            <div class="flex items-center text-xs text-blue-100 mb-3">
                <a href="<?= url('huespedes') ?>" class="hover:text-white flex items-center transition-colors">
                    <i class="fas fa-users mr-1"></i>
                    Huéspedes
                </a>
                <i class="fas fa-chevron-right mx-2 text-blue-200"></i>
                <span class="text-white font-medium">Nuevo Registro</span>
            </div>
            
            <!-- Header Principal -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <!-- Logo y Título -->
                <div class="flex items-center space-x-3">
                    <div class="bg-white/20 backdrop-blur-sm p-2.5 rounded-lg">
                        <i class="fas fa-user-plus text-white text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-semibold text-white">
                            Registrar Nuevo Huésped
                        </h1>
                        <p class="text-xs text-blue-100">
                            Complete la información del huésped
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mensaje informativo si viene de reservación rápida -->
    <?php if ($es_reservacion_rapida): ?>
    <div class="px-3 sm:px-4 lg:px-6 mt-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                <p class="text-sm text-blue-800">
                    Registre el nuevo huésped. Al guardar, continuará con la reservación de la habitación seleccionada.
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Contenido Principal -->
    <div class="px-3 sm:px-4 lg:px-6 py-4 sm:py-6 max-w-5xl mx-auto">
<?php
// Recuperar datos de reservación rápida si existen
$reservacion_rapida_params = '';
if ($es_reservacion_rapida) {
    $params_to_pass = [];
    if (isset($_GET['habitacion_id'])) $params_to_pass['habitacion_id'] = $_GET['habitacion_id'];
    if (isset($_GET['fecha_entrada'])) $params_to_pass['fecha_entrada'] = $_GET['fecha_entrada'];
    if (isset($_GET['fecha_salida'])) $params_to_pass['fecha_salida'] = $_GET['fecha_salida'];
    if (isset($_GET['hora_llegada'])) $params_to_pass['hora_llegada'] = $_GET['hora_llegada'];
    
    if (!empty($params_to_pass)) {
        $reservacion_rapida_params = '&' . http_build_query($params_to_pass);
    }
}
?>
<form method="POST" action="<?= url('huespedes/store') . ($return_to ? '?return_to=' . urlencode($return_to) . $reservacion_rapida_params : '') ?>" class="space-y-4">
            <?= csrf_field() ?>
            
            <!-- Información Personal -->
            <div class="bg-white rounded-xl shadow-sm border border-blue-100 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-4">
                    <h2 class="text-sm font-semibold text-white flex items-center">
                        <div class="bg-white/20 p-1.5 rounded mr-2">
                            <i class="fas fa-user text-white text-xs"></i>
                        </div>
                        Información Personal
                    </h2>
                </div>
                
                <div class="p-4 space-y-4 bg-gradient-to-br from-blue-50 to-white">
                    <!-- Nombre Completo -->
                    <div>
                        <label class="block text-xs font-medium text-blue-700 mb-1.5">
                            Nombre Completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nombre_completo" 
                               value="<?= old('nombre_completo') ?>"
                               required
                               placeholder="Ingrese el nombre completo del huésped"
                               class="w-full px-3 py-2 text-sm border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Teléfono -->
                        <div>
                            <label class="block text-xs font-medium text-blue-700 mb-1.5">
                                Teléfono
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-phone text-blue-400 text-xs"></i>
                                </div>
                                <input type="tel" 
                                       name="telefono" 
                                       value="<?= old('telefono') ?>"
                                       placeholder="10 dígitos"
                                       class="w-full pl-9 pr-3 py-2 text-sm border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-medium text-blue-700 mb-1.5">
                                Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <i class="fas fa-envelope text-blue-400 text-xs"></i>
                                </div>
                                <input type="email" 
                                       name="email" 
                                       value="<?= old('email') ?>"
                                       placeholder="correo@ejemplo.com"
                                       class="w-full pl-9 pr-3 py-2 text-sm border border-blue-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Procedencia -->
            <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-green-600 p-4">
                    <h2 class="text-sm font-semibold text-white flex items-center">
                        <div class="bg-white/20 p-1.5 rounded mr-2">
                            <i class="fas fa-map-marked-alt text-white text-xs"></i>
                        </div>
                        Procedencia
                    </h2>
                </div>
                
                <div class="p-4 bg-gradient-to-br from-green-50 to-white">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Estado -->
                        <div>
                            <label class="block text-xs font-medium text-green-700 mb-1.5">
                                Estado
                            </label>
                            <select name="procedencia_estado" 
                                    class="w-full px-3 py-2 text-sm border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white">
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= $estado ?>" <?= old('procedencia_estado') == $estado ? 'selected' : '' ?>>
                                        <?= $estado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Ciudad -->
                        <div>
                            <label class="block text-xs font-medium text-green-700 mb-1.5">
                                Ciudad
                            </label>
                            <input type="text" 
                                   name="procedencia_ciudad" 
                                   value="<?= old('procedencia_ciudad') ?>"
                                   placeholder="Ciudad de origen"
                                   class="w-full px-3 py-2 text-sm border border-green-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Vehículos -->
            <div class="bg-white rounded-xl shadow-sm border border-purple-100 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4">
                    <h2 class="text-sm font-semibold text-white flex items-center">
                        <div class="bg-white/20 p-1.5 rounded mr-2">
                            <i class="fas fa-car text-white text-xs"></i>
                        </div>
                        Vehículos
                    </h2>
                </div>
                
                <div class="p-4 bg-gradient-to-br from-purple-50 to-white">
                    <div id="vehiculos-container">
                        <!-- Plantilla de vehículo inicial -->
                        <div class="vehiculo-item bg-gradient-to-r from-purple-100 to-purple-50 rounded-lg p-4 mb-3 border border-purple-200">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-sm font-medium text-purple-800">Vehículo 1</h4>
                                <button type="button" onclick="eliminarVehiculo(this)" class="text-red-500 hover:text-red-700 text-sm hidden">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-purple-700 mb-1">
                                        Marca
                                    </label>
                                    <input type="text" 
                                           name="vehiculos[0][marca]" 
                                           placeholder="Ej: Toyota, Nissan, etc."
                                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-purple-700 mb-1">
                                        Modelo
                                    </label>
                                    <input type="text" 
                                           name="vehiculos[0][modelo]" 
                                           placeholder="Ej: Corolla, Sentra, etc."
                                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-purple-700 mb-1">
                                        Placas
                                    </label>
                                    <input type="text" 
                                           name="vehiculos[0][placas]" 
                                           placeholder="ABC-123"
                                           style="text-transform: uppercase"
                                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono bg-white">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-purple-700 mb-1">
                                        Color
                                    </label>
                                    <input type="text" 
                                           name="vehiculos[0][color]" 
                                           placeholder="Ej: Rojo, Azul, etc."
                                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                                </div>
                                
                                <!-- Sección de estacionamiento actualizada para crear.php -->
<div class="md:col-span-2">
    <label class="block text-xs font-medium text-purple-700 mb-2">
        Estacionamiento <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <label class="relative cursor-pointer">
            <input type="radio" 
                   name="vehiculos[0][estacionamiento]" 
                   value="coches"
                   class="peer sr-only" 
                   checked>
            <div class="px-3 py-2.5 border-2 rounded-lg text-center transition-all text-xs bg-white
                        border-purple-300 hover:border-purple-500
                        peer-checked:border-purple-500 peer-checked:bg-purple-500 peer-checked:text-white">
                <i class="fas fa-car text-base mb-0.5 block"></i>
                <p class="font-medium">Coches</p>
            </div>
        </label>
        
        
        
       
    </div>
</div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="agregarVehiculo()" 
                            class="mt-3 w-full bg-gradient-to-r from-purple-500 to-purple-600 text-white px-3 py-2.5 rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all flex items-center justify-center text-sm font-medium shadow-sm">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Agregar otro vehículo
                    </button>
                    
                    <p class="text-xs text-purple-600 mt-2 bg-purple-50 p-2 rounded-lg">
                        <i class="fas fa-info-circle mr-1"></i>
                        Puede registrar múltiples vehículos por huésped. Si no tiene vehículo, deje los campos en blanco.
                    </p>
                </div>
            </div>
            
            <!-- Notas -->
            <div class="bg-white rounded-xl shadow-sm border border-amber-100 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-4">
                    <h2 class="text-sm font-semibold text-white flex items-center">
                        <div class="bg-white/20 p-1.5 rounded mr-2">
                            <i class="fas fa-sticky-note text-white text-xs"></i>
                        </div>
                        Notas Adicionales
                    </h2>
                </div>
                
                <div class="p-4 bg-gradient-to-br from-amber-50 to-white">
                    <textarea name="notas" 
                              rows="3"
                              placeholder="Cualquier información adicional sobre el huésped..."
                              class="w-full px-3 py-2 text-sm border border-amber-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all bg-white"><?= old('notas') ?></textarea>
                </div>
            </div>
            
            <!-- Botones -->
           <!-- Botones -->
<div class="flex justify-end gap-3">
    <a href="<?= $return_to === 'reservacion_rapida' ? url('habitaciones') : url('huespedes') ?>" 
       class="px-4 py-2.5 border-2 border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 hover:border-gray-400 transition-all">
        <i class="fas fa-times mr-2"></i>
        Cancelar
    </a>
    <button type="submit" 
            class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all flex items-center shadow-md hover:shadow-lg transform hover:scale-105">
        <i class="fas fa-save mr-2"></i>
        <?= $es_reservacion_rapida ? 'Guardar y Continuar' : 'Registrar Huésped' ?>
    </button>
</div>
        </form>
    </div>
</div>

<script>
let vehiculoIndex = 1;

// Función para agregar vehículo
function agregarVehiculo() {
    const container = document.getElementById('vehiculos-container');
    const vehiculoHtml = `
        <div class="vehiculo-item bg-gradient-to-r from-purple-100 to-purple-50 rounded-lg p-4 mb-3 animate-fadeIn border border-purple-200">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-sm font-medium text-purple-800">Vehículo ${vehiculoIndex + 1}</h4>
                <button type="button" onclick="eliminarVehiculo(this)" class="text-red-500 hover:text-red-700 text-sm">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-purple-700 mb-1">
                        Marca
                    </label>
                    <input type="text" 
                           name="vehiculos[${vehiculoIndex}][marca]" 
                           placeholder="Ej: Toyota, Nissan, etc."
                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-purple-700 mb-1">
                        Modelo
                    </label>
                    <input type="text" 
                           name="vehiculos[${vehiculoIndex}][modelo]" 
                           placeholder="Ej: Corolla, Sentra, etc."
                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-purple-700 mb-1">
                        Placas
                    </label>
                    <input type="text" 
                           name="vehiculos[${vehiculoIndex}][placas]" 
                           placeholder="ABC-123"
                           style="text-transform: uppercase"
                           onchange="this.value = this.value.toUpperCase()"
                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-mono bg-white">
                </div>
                
                <div>
                    <label class="block text-xs font-medium text-purple-700 mb-1">
                        Color
                    </label>
                    <input type="text" 
                           name="vehiculos[${vehiculoIndex}][color]" 
                           placeholder="Ej: Rojo, Azul, etc."
                           class="w-full px-2.5 py-1.5 text-sm border border-purple-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all bg-white">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-purple-700 mb-2">
                        Estacionamiento <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <label class="relative cursor-pointer">
                            <input type="radio" 
                                   name="vehiculos[${vehiculoIndex}][estacionamiento]" 
                                   value="coches"
                                   class="peer sr-only" 
                                   checked>
                            <div class="px-3 py-2.5 border-2 rounded-lg text-center transition-all text-xs bg-white
                                        border-purple-300 hover:border-purple-500
                                        peer-checked:border-purple-500 peer-checked:bg-purple-500 peer-checked:text-white">
                                <i class="fas fa-car text-base mb-0.5 block"></i>
                                <p class="font-medium">Coches</p>
                            </div>
                        </label>
                        
                        
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', vehiculoHtml);
    vehiculoIndex++;
    
    // Mostrar botón de eliminar en el primer vehículo si hay más de uno
    const vehiculos = container.querySelectorAll('.vehiculo-item');
    if (vehiculos.length > 1) {
        vehiculos[0].querySelector('button').classList.remove('hidden');
    }
}

// Función para eliminar vehículo
function eliminarVehiculo(button) {
    const vehiculoItem = button.closest('.vehiculo-item');
    vehiculoItem.style.opacity = '0';
    vehiculoItem.style.transform = 'scale(0.9)';
    setTimeout(() => {
        vehiculoItem.remove();
        
        // Actualizar numeración
        const vehiculos = document.querySelectorAll('.vehiculo-item');
        vehiculos.forEach((vehiculo, index) => {
            vehiculo.querySelector('h4').textContent = `Vehículo ${index + 1}`;
        });
        
        // Ocultar botón de eliminar si solo queda un vehículo
        if (vehiculos.length === 1) {
            vehiculos[0].querySelector('button').classList.add('hidden');
        }
    }, 300);
}

// Formatear teléfono mientras se escribe
document.querySelector('input[name="telefono"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    e.target.value = value;
});

// Convertir placas a mayúsculas en todos los campos
document.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('[placas]')) {
        e.target.value = e.target.value.toUpperCase();
    }
});

// Validación del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    // Verificar si hay al menos un vehículo con datos completos
    const vehiculos = document.querySelectorAll('.vehiculo-item');
    let hayVehiculoCompleto = false;
    
    vehiculos.forEach(vehiculo => {
        const marca = vehiculo.querySelector('input[name*="[marca]"]').value;
        const placas = vehiculo.querySelector('input[name*="[placas]"]').value;
        
        if (marca && placas) {
            hayVehiculoCompleto = true;
        }
    });
    
    // Si hay datos parciales en algún vehículo, mostrar advertencia
    vehiculos.forEach(vehiculo => {
        const inputs = vehiculo.querySelectorAll('input[type="text"]');
        let hayDatosParciales = false;
        let camposLlenos = 0;
        
        inputs.forEach(input => {
            if (input.value.trim()) camposLlenos++;
        });
        
        if (camposLlenos > 0 && camposLlenos < 4) {
            vehiculo.style.border = '2px solid #ef4444';
            setTimeout(() => {
                vehiculo.style.border = '';
            }, 3000);
        }
    });
});
</script>

<style>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.3s ease-out;
}

/* Mejorar la apariencia de los radio buttons personalizados */
.peer:checked ~ div {
    border-color: #a855f7;
    background-color: #faf5ff;
}

.peer:focus ~ div {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
}

/* Transiciones suaves */
input, select, textarea {
    transition: all 0.2s ease-in-out;
}

/* Scrollbar personalizada */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

::-webkit-scrollbar-track {
    background: #f3f4f6;
}

::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .vehiculo-item {
        padding: 0.75rem;
    }
    
    input, select, textarea {
        font-size: 16px; /* Prevenir zoom en iOS */
    }
}
</style>