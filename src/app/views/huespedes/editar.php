<!-- Reemplazar la sección de Vehículo en editar.php con esta nueva sección -->
<!-- Editar Huésped -->
<div class="min-h-screen bg-gradient-to-br from-hotel-cream to-white p-6">
    <!-- Header -->
    <div class="max-w-4xl mx-auto mb-8">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="<?= url('huespedes') ?>" class="hover:text-hotel-brown">
                <i class="fas fa-users mr-1"></i>Huéspedes
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <a href="<?= url('huespedes/' . $huesped['id']) ?>" class="hover:text-hotel-brown">
                <?= htmlspecialchars($huesped['nombre_completo']) ?>
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span>Editar</span>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8 border-l-8 border-hotel-gold">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Editar Huésped
                    </h1>
                    <p class="text-gray-600">Actualice la información de <?= htmlspecialchars($huesped['nombre_completo']) ?></p>
                </div>
                <div class="hidden lg:block">
                    <div class="bg-hotel-cream p-6 rounded-full">
                        <i class="fas fa-user-edit text-5xl text-hotel-brown"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario -->
    <div class="max-w-4xl mx-auto">
        <form method="POST" action="<?= url('huespedes/' . $huesped['id'] . '/update') ?>" class="space-y-6">
            <?= csrf_field() ?>
            
            <!-- Información Personal -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-user mr-3"></i>
                        Información Personal
                    </h2>
                </div>
                
                <div class="p-6 space-y-4">
                    <!-- Nombre Completo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Nombre Completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="nombre_completo" 
                               value="<?= htmlspecialchars($huesped['nombre_completo']) ?>"
                               required
                               placeholder="Ingrese el nombre completo del huésped"
                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Teléfono
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" 
                                       name="telefono" 
                                       value="<?= htmlspecialchars($huesped['telefono']) ?>"
                                       placeholder="10 dígitos"
                                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" 
                                       name="email" 
                                       value="<?= htmlspecialchars($huesped['email']) ?>"
                                       placeholder="correo@ejemplo.com"
                                       class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Procedencia -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-green-600 to-green-700 p-6">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-map-marked-alt mr-3"></i>
                        Procedencia
                    </h2>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Estado
                            </label>
                            <select name="procedencia_estado" 
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= $estado ?>" <?= $huesped['procedencia_estado'] == $estado ? 'selected' : '' ?>>
                                        <?= $estado ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Ciudad -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Ciudad
                            </label>
                            <input type="text" 
                                   name="procedencia_ciudad" 
                                   value="<?= htmlspecialchars($huesped['procedencia_ciudad']) ?>"
                                   placeholder="Ciudad de origen"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-green-500/20 focus:border-green-500 transition-all">
                        </div>
                    </div>
                </div>
            </div>
            
         <!-- Vehículos -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6">
        <h2 class="text-xl font-semibold text-white flex items-center">
            <i class="fas fa-car mr-3"></i>
            Vehículos
        </h2>
    </div>
    
    <div class="p-6">
        <!-- Información importante -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i>
                Los vehículos se gestionan desde la vista de detalle del huésped. Aquí puede ver los vehículos actuales.
            </p>
        </div>
        
        <!-- Lista de vehículos actuales -->
        <?php
        $vehiculoModel = new HuespedVehiculo();
        $vehiculos = $vehiculoModel->porHuesped($huesped['id']);
        ?>
        
        <?php if (!empty($vehiculos)): ?>
            <div class="space-y-3">
                <h4 class="font-semibold text-gray-700 mb-2">Vehículos registrados:</h4>
                <?php foreach ($vehiculos as $vehiculo): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-car text-purple-600 mr-3"></i>
                            <div>
                                <p class="font-medium">
                                    <?= htmlspecialchars($vehiculo['marca']) ?> 
                                    <?= htmlspecialchars($vehiculo['modelo'] ?? '') ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    Placas: <span class="font-mono"><?= htmlspecialchars($vehiculo['placas']) ?></span>
                                    <?php if ($vehiculo['color']): ?>
                                        • Color: <?= htmlspecialchars($vehiculo['color']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <?php
                            $estacionamientos = HuespedVehiculo::getEstacionamientos();
                            $ubicacion = $estacionamientos[$vehiculo['estacionamiento']] ?? 'No especificado';
                            ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <?= $ubicacion ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">
                <i class="fas fa-car-side text-4xl text-gray-300 mb-2 block"></i>
                No hay vehículos registrados
            </p>
        <?php endif; ?>
        
        <div class="mt-4 pt-4 border-t">
            <a href="<?= url('huespedes/' . $huesped['id']) ?>#vehiculos" 
               class="text-purple-600 hover:text-purple-800 font-medium text-sm">
                <i class="fas fa-cog mr-1"></i>
                Gestionar vehículos desde la vista de detalle
            </a>
        </div>
    </div>
</div>
            
            <!-- Notas -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-gray-600 to-gray-700 p-6">
                    <h2 class="text-xl font-semibold text-white flex items-center">
                        <i class="fas fa-sticky-note mr-3"></i>
                        Notas Adicionales
                    </h2>
                </div>
                
                <div class="p-6">
                    <textarea name="notas" 
                              rows="3"
                              placeholder="Cualquier información adicional sobre el huésped..."
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-gray-500/20 focus:border-gray-500 transition-all"><?= htmlspecialchars($huesped['notas']) ?></textarea>
                </div>
            </div>
            
            <!-- Información del sistema (solo lectura) -->
            <div class="bg-gray-50 rounded-2xl p-6">
                <h3 class="font-semibold text-gray-700 mb-3">Información del Sistema</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">ID del Huésped</p>
                        <p class="font-mono font-semibold">#<?= $huesped['id'] ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Fecha de Registro</p>
                        <p class="font-semibold"><?= format_date($huesped['created_at']) ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600">Última Actualización</p>
                        <p class="font-semibold"><?= format_datetime($huesped['updated_at']) ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Botones -->
            <div class="flex justify-end gap-3">
                <a href="<?= url('huespedes/' . $huesped['id']) ?>" 
                   class="px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white font-semibold rounded-xl hover:shadow-xl transform hover:scale-105 transition-all">
                    <i class="fas fa-save mr-2"></i>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Formatear teléfono mientras se escribe
document.querySelector('input[name="telefono"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 10) {
        value = value.slice(0, 10);
    }
    e.target.value = value;
});

// Convertir placas a mayúsculas
document.querySelector('input[name="vehiculo_placas"]').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});
</script>
