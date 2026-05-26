<!-- Gestión de Huéspedes -->
<div class="p-6">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 font-playfair">Huéspedes</h1>
            <p class="text-gray-600 mt-1">Gestión de huéspedes del hotel</p>
        </div>
        
        <a href="<?= url('huespedes/create') ?>" 
           class="bg-hotel-brown text-white px-4 py-2 rounded-lg hover:bg-hotel-brown-dark transition duration-200 flex items-center">
            <i class="fas fa-user-plus mr-2"></i>Nuevo Huésped
        </a>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Huéspedes</p>
                    <p class="text-2xl font-bold text-gray-800"><?= number_format($total_huespedes) ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>
        
       
        
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Estados Registrados</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <?= count(array_unique(array_column($huespedes, 'procedencia_estado'))) ?>
                    </p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-map-marked-alt text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-lg p-4 mb-6">
        <form method="GET" action="<?= url('huespedes') ?>" class="flex flex-wrap gap-3 items-end">
            <!-- Búsqueda -->
            <div class="flex-1 min-w-[300px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <div class="relative">
                    <input type="text" 
                           name="buscar" 
                           value="<?= htmlspecialchars($buscar ?? '') ?>"
                           placeholder="Nombre, teléfono, email o placas..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="w-full md:w-auto">
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado de Procedencia</label>
                <select name="estado" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?= $estado ?>" <?= ($estado_filtro ?? '') == $estado ? 'selected' : '' ?>>
                            <?= $estado ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Botones -->
            <div class="flex gap-2">
                <button type="submit" class="bg-hotel-brown text-white px-4 py-2 rounded-lg hover:bg-hotel-brown-dark transition duration-200">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="<?= url('huespedes') ?>" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200">
                    <i class="fas fa-times mr-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tabla de huéspedes -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Huésped
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Procedencia
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vehículo
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Visitas
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($huespedes as $huesped): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($huesped['nombre_completo']) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    ID: <?= $huesped['id'] ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                <?php if ($huesped['telefono']): ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-phone text-gray-400 mr-2"></i>
                                        <?= htmlspecialchars($huesped['telefono']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($huesped['email']): ?>
                                    <div class="flex items-center text-xs text-gray-500 mt-1">
                                        <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                        <?= htmlspecialchars($huesped['email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$huesped['telefono'] && !$huesped['email']): ?>
                                    <span class="text-gray-400">Sin contacto</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm">
                                <?php if ($huesped['procedencia_estado']): ?>
                                    <div class="font-medium text-gray-900">
                                        <?= htmlspecialchars($huesped['procedencia_estado']) ?>
                                    </div>
                                    <?php if ($huesped['procedencia_ciudad']): ?>
                                        <div class="text-xs text-gray-500">
                                            <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">No especificado</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <!-- Reemplazar la columna de vehículo en la tabla con esto -->

<td class="px-6 py-4 whitespace-nowrap">
    <?php 
    // Obtener vehículos del huésped
    $vehiculoModel = new HuespedVehiculo();
    $vehiculos = $vehiculoModel->porHuesped($huesped['id']);
    $total_vehiculos = count($vehiculos);
    ?>
    
    <?php if ($total_vehiculos > 0): ?>
        <div class="text-sm">
            <div class="text-gray-900">
                <i class="fas fa-car text-gray-400 mr-1"></i>
                <?= $total_vehiculos ?> <?= $total_vehiculos == 1 ? 'vehículo' : 'vehículos' ?>
            </div>
            <?php if ($total_vehiculos == 1 && !empty($vehiculos[0]['placas'])): ?>
                <div class="text-xs text-gray-500 font-mono">
                    <?= htmlspecialchars($vehiculos[0]['placas']) ?>
                </div>
            <?php elseif ($total_vehiculos > 1): ?>
                <div class="text-xs text-gray-500">
                    <a href="<?= url('huespedes/' . $huesped['id']) ?>#vehiculos" 
                       class="text-purple-600 hover:text-purple-800">
                        Ver todos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <span class="text-gray-400 text-sm">Sin vehículo</span>
    <?php endif; ?>
</td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php if ($huesped['total_reservaciones'] > 0): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $huesped['total_reservaciones'] >= 3 ? 'bg-gold-100 text-gold-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $huesped['total_reservaciones'] ?>
                                    <?php if ($huesped['total_reservaciones'] >= 3): ?>
                                        <i class="fas fa-star ml-1 text-gold-600"></i>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="<?= url('huespedes/' . $huesped['id']) ?>" 
                                   class="text-blue-600 hover:text-blue-900" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>" 
                                   class="text-yellow-600 hover:text-yellow-900" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" 
                                   class="text-green-600 hover:text-green-900" title="Nueva reservación">
                                    <i class="fas fa-calendar-plus"></i>
                                </a>
                                
                                
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (empty($huespedes)): ?>
        <div class="text-center py-12">
            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
            <p class="text-xl text-gray-600">No se encontraron huéspedes</p>
            <p class="text-gray-500 mt-2">Intenta ajustar los filtros de búsqueda</p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="mt-6 flex justify-center">
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <?php if ($pagina_actual > 1): ?>
                <a href="?page=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-hotel-brown bg-hotel-brown text-white text-sm font-medium">
                        <?= $i ?>
                    </span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>" 
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?page=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>" 
                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>