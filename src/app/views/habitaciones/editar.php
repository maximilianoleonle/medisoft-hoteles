<!-- Vista Editar Habitación - ACTUALIZADA PARA Los Cedros -->
<div class="min-h-screen bg-gradient-to-br from-hotel-cream to-white p-6">
    <!-- Header elegante -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown">
                <i class="fas fa-bed mr-1"></i>Habitaciones
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="hover:text-hotel-brown">
                Habitación <?= htmlspecialchars($habitacion['numero']) ?>
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span>Editar</span>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8 border-l-8 border-hotel-gold">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Editar Habitación <?= htmlspecialchars($habitacion['numero']) ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php
                        // Mostrar información del tipo actual
                        $tiposInfo = [
                            'sencilla' => '1 cama matrimonial (2 personas)',
                            'doble' => '2 camas matrimoniales (4 personas)', 
                            'triple' => '3 camas matrimoniales (6 personas)',
                            'cuadruple' => '4 camas matrimoniales (8 personas)',
                            'doble_jacuzzi' => '2 camas matrimoniales con jacuzzi (4 personas)',
                            'sencilla_jacuzzi' => '1 cama matrimonial con jacuzzi (2 personas)'
                        ];
                        echo $tiposInfo[$habitacion['tipo']] ?? 'Tipo especial';
                        ?>
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="bg-hotel-cream p-6 rounded-full">
                            <i class="fas fa-edit text-5xl text-hotel-brown"></i>
                        </div>
                        <!-- Estado actual badge -->
                        <?php 
                        $estados = Habitacion::getEstados();
                        $estadoActual = $estados[$habitacion['estado']] ?? ['label' => 'Desconocido', 'color' => 'gray'];
                        $badgeColors = [
                            'disponible' => 'bg-green-500',
                            'ocupada' => 'bg-red-500',
                            'mantenimiento' => 'bg-yellow-500',
                            'limpieza' => 'bg-blue-500'
                        ];
                        ?>
                        <div class="absolute -bottom-2 -right-2 <?= $badgeColors[$habitacion['estado']] ?? 'bg-gray-500' ?> text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg">
                            <?= $estadoActual['label'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario con diseño moderno -->
    <div class="max-w-7xl mx-auto">
        <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/update') ?>" 
              enctype="multipart/form-data" class="space-y-8">
            <?= csrf_field() ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna principal (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Card de Información Básica -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-info-circle mr-3"></i>
                                Información Básica
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <!-- Número y Tipo en grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Número de habitación -->
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Número de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <i class="fas fa-hashtag text-gray-400"></i>
                                        </div>
                                        <input type="text" 
                                               name="numero" 
                                               value="<?= htmlspecialchars($habitacion['numero']) ?>"
                                               required
                                               class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                    </div>
                                    
                                </div>
                                
                                <!-- Tipo de habitación - ACTUALIZADO -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tipo de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="tipo" 
                                                required
                                                class="w-full pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all appearance-none">
                                            <?php foreach (Habitacion::getTipos() as $key => $tipo): ?>
                                                <option value="<?= $key ?>" <?= $habitacion['tipo'] == $key ? 'selected' : '' ?>>
                                                    <?= $tipo ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Piso y Precio en grid - ACTUALIZADO PARA SÓTANOS -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Piso con selección ACTUALIZADA para incluir sótanos -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Ubicación - Piso <span class="text-red-500">*</span>
                                    </label>
                                    <div class="space-y-3">
                                        <!-- Sótanos -->
                                        <div class="border-2 border-gray-200 rounded-xl p-4">
                                            <p class="text-xs font-medium text-gray-600 mb-2">Niveles abajo</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php 
                                                $sotanos = [-4 => '4 abajo', -2 => '2 abajo', -1 => '1 abajo'];
                                                foreach ($sotanos as $piso_num => $label): 
                                                ?>
                                                <label class="relative">
                                                    <input type="radio" 
                                                           name="piso" 
                                                           value="<?= $piso_num ?>" 
                                                           <?= $habitacion['piso'] == $piso_num ? 'checked' : '' ?>
                                                           required
                                                           class="sr-only peer">
                                                    <div class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer transition-all peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white hover:border-gray-300">
                                                        <i class="fas fa-arrow-down text-lg mb-1"></i>
                                                        <span class="text-xs font-medium"><?= $label ?></span>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- Pisos superiores -->
                                        <div class="border-2 border-gray-200 rounded-xl p-4">
                                            <p class="text-xs font-medium text-gray-600 mb-2">Pisos Superiores</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php 
                                                $pisos_sup = [1 => 'Nivel piso', 2 => '2° Nivel', 3 => '3° Nivel'];
                                                foreach ($pisos_sup as $piso_num => $label): 
                                                ?>
                                                <label class="relative">
                                                    <input type="radio" 
                                                           name="piso" 
                                                           value="<?= $piso_num ?>" 
                                                           <?= $habitacion['piso'] == $piso_num ? 'checked' : '' ?>
                                                           required
                                                           class="sr-only peer">
                                                    <div class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer transition-all peer-checked:border-hotel-brown peer-checked:bg-hotel-brown peer-checked:text-white hover:border-gray-300">
                                                        <i class="fas fa-building text-lg mb-1"></i>
                                                        <span class="text-xs font-medium"><?= $label ?></span>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Precio con rangos por tipo -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Precio por Noche <span class="text-red-500">*</span>
                                    </label>
                                    <?php
                                    // Mostrar rango sugerido según el tipo actual
                                    $rangos = Habitacion::getRangoPrecios();
                                    $rango_actual = $rangos[$habitacion['tipo']] ?? ['min' => 500, 'max' => 1600];
                                    ?>
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500" id="rango-precio">
                                            Rango sugerido para <?= get_tipo_habitacion($habitacion['tipo']) ?>: 
                                            $<?= number_format($rango_actual['min']) ?> - $<?= number_format($rango_actual['max']) ?>
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <span class="text-xl font-bold text-hotel-gold">$</span>
                                        </div>
                                        <input type="number" 
                                               name="precio_base" 
                                               value="<?= $habitacion['precio_base'] ?>"
                                               min="<?= $rango_actual['min'] ?>"
                                               max="<?= $rango_actual['max'] + 500 ?>"
                                               step="50"
                                               required
                                               class="w-full pl-10 pr-4 py-3 text-xl font-semibold border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <span class="text-sm text-gray-500">MXN</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de Características - ACTUALIZADO -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-list-check mr-3"></i>
                                Características del Los Cedros
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <!-- Características estándar del hotel -->
                            <div class="mb-6">
                                <p class="text-sm font-medium text-gray-700 mb-3">Características incluidas en todas las habitaciones:</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <?php 
                                    $caracteristicas_base = [
                                        ['icon' => 'wifi', 'label' => 'Wi-Fi', 'color' => 'blue'],
                                        ['icon' => 'tv', 'label' => 'Cablevisión', 'color' => 'purple'],
                                        ['icon' => 'bath', 'label' => 'Baño Privado', 'color' => 'green'],
                                        ['icon' => 'car', 'label' => 'Estacionamiento', 'color' => 'gray'],
                                        ['icon' => 'shower', 'label' => 'Agua Caliente', 'color' => 'red'],
                                        ['icon' => 'fan', 'label' => 'Ventilador', 'color' => 'cyan']
                                    ];
                                    
                                    foreach ($caracteristicas_base as $caract): 
                                    ?>
                                    <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                                        <i class="fas fa-<?= $caract['icon'] ?> mr-2 text-<?= $caract['color'] ?>-600"></i>
                                        <span class="text-xs font-medium"><?= $caract['label'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Características especiales con checkboxes -->
                            <div class="mb-6">
                                <p class="text-sm font-medium text-gray-700 mb-3">Características especiales:</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php 
                                    $caracteristicas_especiales = [
                                        'pantalla' => ['icon' => 'tv', 'label' => 'Pantalla (en lugar de TV normal)', 'color' => 'purple'],
                                        'balcon' => ['icon' => 'home', 'label' => 'Balcón', 'color' => 'green'],
                                        'jacuzzi' => ['icon' => 'bath', 'label' => 'Jacuzzi', 'color' => 'blue', 'disabled_for' => ['doble_jacuzzi', 'sencilla_jacuzzi']],
                                        'amplia' => ['icon' => 'expand-arrows-alt', 'label' => 'Habitación más amplia', 'color' => 'yellow']
                                    ];
                                    
                                    // Parsear características existentes
                                    $caracteristicas_actuales = strtolower($habitacion['caracteristicas'] ?? '');
                                    
                                    foreach ($caracteristicas_especiales as $key => $especial): 
                                        $keywords = [
                                            'pantalla' => 'pantalla',
                                            'balcon' => 'balcón',
                                            'jacuzzi' => 'jacuzzi',
                                            'amplia' => 'más amplia'
                                        ];
                                        $isChecked = strpos($caracteristicas_actuales, $keywords[$key]) !== false;
                                        $isDisabled = isset($especial['disabled_for']) && in_array($habitacion['tipo'], $especial['disabled_for']);
                                    ?>
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-<?= $especial['color'] ?>-300 hover:bg-<?= $especial['color'] ?>-50 transition-all group <?= $isDisabled ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                        <input type="checkbox" 
                                               name="caracteristicas_especiales[]" 
                                               value="<?= $key ?>"
                                               <?= $isChecked ? 'checked' : '' ?>
                                               <?= $isDisabled ? 'disabled' : '' ?>
                                               class="mr-3 w-4 h-4 text-<?= $especial['color'] ?>-600 focus:ring-<?= $especial['color'] ?>-500 rounded">
                                        <i class="fas fa-<?= $especial['icon'] ?> mr-2 text-gray-600 group-hover:text-<?= $especial['color'] ?>-600"></i>
                                        <span class="text-sm font-medium"><?= $especial['label'] ?></span>
                                        <?php if ($isDisabled): ?>
                                            <span class="ml-2 text-xs text-gray-500">(incluido en el tipo)</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Descripción completa (solo lectura mejorada) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción completa de características
                                </label>
                                <textarea name="caracteristicas" 
                                          rows="4"
                                          placeholder="Ejemplo: 2 camas matrimoniales, pantalla, balcón, baño, ventilador, agua caliente, Wifi, Cablevisión, estacionamiento"
                                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"><?= htmlspecialchars($habitacion['caracteristicas']) ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    Esta descripción se genera automáticamente en base al tipo y características seleccionadas, pero puede personalizarse.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Card de Imágenes MÚLTIPLES -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-images mr-3"></i>
                                Fotografías de la Habitación
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <?php 
                            // Obtener imágenes existentes
                            $habitacionImagenModel = new HabitacionImagen();
                            $imagenes_existentes = $habitacionImagenModel->porHabitacion($habitacion['id']);
                            $total_imagenes = count($imagenes_existentes);
                            ?>
                            
                            <!-- Imágenes actuales -->
                            <!-- Encabezado de imágenes con enlace de gestión siempre visible -->
<div class="flex justify-between items-center mb-4">
    <h4 class="text-sm font-semibold text-gray-700">
        Imágenes de la habitación (<?= $total_imagenes ?>/10)
    </h4>
    <a href="<?= url('habitaciones/' . $habitacion['id'] . '/imagenes') ?>" 
       class="text-sm text-purple-600 hover:underline">
        <i class="fas fa-cog mr-1"></i>Gestionar imágenes
    </a>
</div>

<!-- Imágenes actuales -->
<?php if ($total_imagenes > 0): ?>
<div class="mb-6">
                                
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <?php foreach (array_slice($imagenes_existentes, 0, 4) as $index => $imagen): ?>
                                    <div class="relative group">
                                        <img src="<?= image_url($imagen['url']) ?>" 
                                             alt="Imagen <?= $index + 1 ?>"
                                             class="w-full h-24 object-cover rounded-lg shadow">
                                        <?php if ($imagen['es_principal']): ?>
                                        <div class="absolute top-1 left-1 bg-green-500 text-white px-2 py-0.5 rounded text-xs">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($total_imagenes > 4): ?>
                                    <div class="flex items-center justify-center bg-gray-100 rounded-lg h-24">
                                        <span class="text-gray-600 text-sm">+<?= $total_imagenes - 4 ?> más</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Upload de nuevas imágenes -->
                            <?php if ($total_imagenes < 10): ?>
                            
                                
                                <!-- Vista previa de nuevas imágenes -->
                                <div id="preview-container" class="mt-4 hidden">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Nuevas imágenes a agregar:</h5>
                                    <div id="preview-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <!-- Las previsualizaciones se agregarán aquí dinámicamente -->
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-yellow-800 text-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Has alcanzado el límite máximo de 10 imágenes. Para agregar nuevas, primero debes eliminar algunas existentes.
                                </p>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Información -->
                            <div class="mt-4 bg-purple-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-purple-800 mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Información sobre las imágenes
                                </h4>
                                <ul class="text-xs text-purple-700 space-y-1">
                                    <li>• Máximo 10 imágenes por habitación</li>
                                    <li>• La imagen marcada con <i class="fas fa-star"></i> es la principal</li>
                                    <li>• Puedes gestionar el orden y eliminar imágenes en la sección de gestión</li>
                                    <li>• Las nuevas imágenes se agregarán al final de la galería</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Columna lateral (1/3) -->
                <div class="space-y-6">
                    <!-- Card de Estado Actual con información del hotel -->
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 text-white">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Información de la Habitación
                        </h3>
                        
                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm opacity-80">Estado:</span>
                                <span class="font-bold text-lg flex items-center">
                                    <i class="fas fa-<?= $estadoActual['icon'] ?? 'question' ?> mr-2"></i>
                                    <?= $estadoActual['label'] ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm opacity-80">Ubicación:</span>
                                <span class="font-medium">
                                    <?= Habitacion::getNombrePiso($habitacion['piso']) ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-80">Capacidad:</span>
                                <span class="font-medium">
                                    <?= $habitacion['capacidad_personas'] ?> personas
                                </span>
                            </div>
                        </div>
                        
                        <div class="bg-blue-500/20 rounded-lg p-3">
                            <p class="text-xs flex items-start">
                                <i class="fas fa-info-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                Esta habitación pertenece al Los Cedros de Santa Catarina Juquila, Oaxaca.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Preview en tiempo real MEJORADO -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-eye mr-2 text-hotel-brown"></i>
                            Vista Previa
                        </h3>
                        
                        <div class="bg-gradient-to-br from-hotel-cream to-white rounded-xl p-4 border-2 border-hotel-brown/20">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-2xl font-bold text-hotel-brown" id="preview-numero">
                                        <?= htmlspecialchars($habitacion['numero']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600" id="preview-tipo">
                                        <?= get_tipo_habitacion($habitacion['tipo']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Ubicación</p>
                                    <p class="text-lg font-bold text-gray-800" id="preview-piso">
                                        <?= Habitacion::getNombrePiso($habitacion['piso']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="border-t pt-3">
                                <p class="text-xs text-gray-500">Precio por noche</p>
                                <p class="text-2xl font-bold text-hotel-gold" id="preview-precio">
                                    $<?= number_format($habitacion['precio_base'], 0) ?>
                                </p>
                            </div>
                            
                            <!-- Características especiales en el preview -->
                            <div class="mt-3 pt-3 border-t" id="preview-caracteristicas">
                                <p class="text-xs text-gray-500 mb-1">Características especiales:</p>
                                <div class="flex flex-wrap gap-1" id="preview-badges">
                                    <!-- Se llenarán con JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado Activo/Inactivo -->
                    <div class="bg-yellow-50 rounded-2xl p-6 border-2 border-yellow-200">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" 
                                   name="activa" 
                                   value="1"
                                   <?= $habitacion['activa'] ? 'checked' : '' ?>
                                   class="mt-1 mr-3 w-5 h-5 text-yellow-600 focus:ring-yellow-500 rounded">
                            <div>
                                <span class="font-semibold text-gray-800">Habitación Activa</span>
                                <p class="text-sm text-gray-600 mt-1">
                                    Desmarque para ocultar la habitación del sistema de reservas
                                </p>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="space-y-3">
                        <button type="submit" 
                                class="w-full bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white font-semibold py-4 px-6 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-save mr-3"></i>
                            Guardar Cambios
                        </button>
                        
                        <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" 
                           class="w-full bg-white border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-xl hover:bg-gray-50 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-times mr-3"></i>
                            Cancelar
                        </a>
                        
                        <?php if (can('habitaciones.delete') && $habitacion['estado'] == 'disponible'): ?>
                        <button type="button" 
                                onclick="confirmarEliminacion()"
                                class="w-full bg-red-50 border-2 border-red-200 text-red-600 font-semibold py-4 px-6 rounded-xl hover:bg-red-100 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-trash-alt mr-3"></i>
                            Eliminar Habitación
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script mejorado para Los Cedros -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tipos de habitación con información - ACTUALIZADO
    const tiposInfo = {
        'sencilla': 'Sencilla',
        'doble': 'Doble',
        'triple': 'Triple',
        'cuadruple': 'Cuádruple',
        'doble_jacuzzi': 'Doble con Jacuzzi',
        'sencilla_jacuzzi': 'Sencilla con Jacuzzi'
    };
    
    // Información de pisos - ACTUALIZADO
    const pisosInfo = {
        '-4': '4 niveles abajo',
        '-2': '2 niveles abajo',
        '-1': 'Un nivel abajo',
        '1': 'Nivel de piso',
        '2': '2º Nivel',
        '3': '3º Nivel'
    };
    
    // Rangos de precio por tipo - ACTUALIZADO
    const rangosPrecio = {
        'sencilla': {min: 550, max: 550},
        'doble': {min: 700, max: 1000},
        'triple': {min: 1100, max: 1200},
        'cuadruple': {min: 1400, max: 1400},
        'doble_jacuzzi': {min: 1600, max: 1600},
        'sencilla_jacuzzi': {min: 1000, max: 1000}
    };
    
    // Preview en tiempo real
    const numeroInput = document.querySelector('input[name="numero"]');
    const tipoSelect = document.querySelector('select[name="tipo"]');
    const pisoInputs = document.querySelectorAll('input[name="piso"]');
    const precioInput = document.querySelector('input[name="precio_base"]');
    const caracteristicasCheckboxes = document.querySelectorAll('input[name="caracteristicas_especiales[]"]');
    
    // Elementos del preview
    const previewNumero = document.getElementById('preview-numero');
    const previewTipo = document.getElementById('preview-tipo');
    const previewPiso = document.getElementById('preview-piso');
    const previewPrecio = document.getElementById('preview-precio');
    const previewBadges = document.getElementById('preview-badges');
    
    // Actualizar preview
    function updatePreview() {
        // Número
        previewNumero.textContent = numeroInput.value || '---';
        
        // Tipo
        const tipoSeleccionado = tipoSelect.value;
        previewTipo.textContent = tiposInfo[tipoSeleccionado] || 'Sin tipo';
        
        // Piso
        const pisoSeleccionado = document.querySelector('input[name="piso"]:checked');
        if (pisoSeleccionado) {
            previewPiso.textContent = pisosInfo[pisoSeleccionado.value] || pisoSeleccionado.value;
        } else {
            previewPiso.textContent = 'Sin definir';
        }
        
        // Precio
        const precio = parseFloat(precioInput.value) || 0;
        previewPrecio.textContent = '$' + precio.toLocaleString('es-MX');
        
        // Características especiales
        updateCaracteristicasBadges();
        
        // Actualizar rango de precio sugerido
        updateRangoPrecio(tipoSeleccionado);
        
        // Manejar checkboxes especiales para tipos con jacuzzi
        manejarJacuzziCheckbox(tipoSeleccionado);
    }
    
    // Actualizar badges de características
    function updateCaracteristicasBadges() {
        const caracteristicasSeleccionadas = [];
        caracteristicasCheckboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                const labels = {
                    'pantalla': 'Pantalla',
                    'balcon': 'Balcón',
                    'jacuzzi': 'Jacuzzi',
                    'amplia': 'Más Amplia'
                };
                caracteristicasSeleccionadas.push(labels[checkbox.value] || checkbox.value);
            }
        });
        
        // Agregar jacuzzi automáticamente si es tipo con jacuzzi
        const tipoSeleccionado = tipoSelect.value;
        if ((tipoSeleccionado === 'doble_jacuzzi' || tipoSeleccionado === 'sencilla_jacuzzi') && !caracteristicasSeleccionadas.includes('Jacuzzi')) {
            caracteristicasSeleccionadas.unshift('Jacuzzi');
        }
        
        previewBadges.innerHTML = '';
        if (caracteristicasSeleccionadas.length > 0) {
            caracteristicasSeleccionadas.forEach(caract => {
                const badge = document.createElement('span');
                badge.className = 'px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium';
                badge.textContent = caract;
                previewBadges.appendChild(badge);
            });
        } else {
            previewBadges.innerHTML = '<span class="text-xs text-gray-500">Ninguna especial</span>';
        }
    }
    
    // Actualizar rango de precio sugerido
    function updateRangoPrecio(tipo) {
        const rango = rangosPrecio[tipo];
        if (rango) {
            precioInput.min = rango.min;
            precioInput.max = rango.max + 500;
            
            // Buscar el elemento de rango sugerido y actualizarlo
            const rangoTexto = document.getElementById('rango-precio');
            if (rangoTexto) {
                rangoTexto.textContent = `Rango sugerido para ${tiposInfo[tipo]}: $${rango.min.toLocaleString()} - $${rango.max.toLocaleString()}`;
            }
        }
    }
    
    // Manejar checkbox de jacuzzi para tipos con jacuzzi incluido
    function manejarJacuzziCheckbox(tipo) {
        const jacuzziCheckbox = document.querySelector('input[name="caracteristicas_especiales[]"][value="jacuzzi"]');
        if (jacuzziCheckbox) {
            const label = jacuzziCheckbox.closest('label');
            if (tipo === 'doble_jacuzzi' || tipo === 'sencilla_jacuzzi') {
                jacuzziCheckbox.disabled = true;
                jacuzziCheckbox.checked = true;
                label.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                jacuzziCheckbox.disabled = false;
                label.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }
    
    // Event listeners
    numeroInput.addEventListener('input', updatePreview);
    tipoSelect.addEventListener('change', updatePreview);
    pisoInputs.forEach(input => input.addEventListener('change', updatePreview));
    precioInput.addEventListener('input', updatePreview);
    caracteristicasCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updatePreview));
    
    // Formatear precio al perder foco
    precioInput.addEventListener('blur', function() {
        if (this.value) {
            const valor = parseFloat(this.value);
            // Redondear a múltiplos de 50
            const valorRedondeado = Math.round(valor / 50) * 50;
            this.value = valorRedondeado;
            updatePreview();
        }
    });
    
    // Inicializar preview
    updatePreview();
});

// Confirmar eliminación
function confirmarEliminacion() {
    Swal.fire({
        title: '¿Eliminar habitación?',
        html: `¿Está seguro de eliminar la habitación <strong><?= htmlspecialchars($habitacion['numero']) ?></strong>?<br>
               <span class="text-red-600">Esta acción no se puede deshacer.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Crear form para eliminar
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('habitaciones/' . $habitacion['id'] . '/delete') ?>';
            
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = '<?= csrf_token() ?>';
            form.appendChild(csrfField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Script para manejo de múltiples imágenes en edición
document.addEventListener('DOMContentLoaded', function() {
    const fotosInput = document.getElementById('fotos-input');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const dropZone = document.getElementById('drop-zone');
    
    if (!fotosInput) return;
    
    let selectedFiles = [];
    const maxFiles = <?= 10 - $total_imagenes ?>;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Manejar selección de archivos
    fotosInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    // Drag and drop
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-purple-400', 'bg-purple-50');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-purple-400', 'bg-purple-50');
            }, false);
        });
        
        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }, false);
    }
    
    function handleFiles(files) {
        const newFiles = Array.from(files);
        
        // Validar cantidad
        if (newFiles.length > maxFiles) {
            Swal.fire({
                icon: 'warning',
                title: 'Demasiadas imágenes',
                text: `Solo puedes agregar ${maxFiles} imagen${maxFiles > 1 ? 'es' : ''} más`,
                confirmButtonColor: '#9333ea'
            });
            return;
        }
        
        // Validar cada archivo
        selectedFiles = [];
        for (let file of newFiles) {
            if (!allowedTypes.includes(file.type)) {
                showError(`${file.name} no es una imagen válida`);
                continue;
            }
            
            if (file.size > maxSize) {
                showError(`${file.name} excede el tamaño máximo de 5MB`);
                continue;
            }
            
            selectedFiles.push(file);
        }
        
        updatePreview();
        updateFileInput();
    }
    
    function updatePreview() {
        if (!previewGrid || !previewContainer) return;
        
        previewGrid.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            previewContainer.classList.add('hidden');
            return;
        }
        
        previewContainer.classList.remove('hidden');
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" 
                         alt="${file.name}" 
                         class="w-full h-24 object-cover rounded-lg shadow">
                    <button type="button" 
                            onclick="removeNewImage(${index})"
                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                `;
                previewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
    
    window.removeNewImage = function(index) {
        selectedFiles.splice(index, 1);
        updatePreview();
        updateFileInput();
    };
    
    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fotosInput.files = dataTransfer.files;
    }
    
    function showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: message,
            confirmButtonColor: '#dc2626'
        });
    }
});
</script>