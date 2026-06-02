<!-- Gestión de Huéspedes -->
<style>
.guests-page {
    --guest-brand: var(--brand-primary, #2563EB);
    --guest-brand-dark: color-mix(in srgb, var(--guest-brand), #000 26%);
    --guest-brand-soft: color-mix(in srgb, var(--guest-brand) 9%, #F8FAFC);
    --guest-border: color-mix(in srgb, var(--guest-brand) 16%, #E5E7EB);
    --guest-ring: color-mix(in srgb, var(--guest-brand) 22%, transparent);
    color: #0F172A;
}
.guest-hero {
    background: linear-gradient(135deg, var(--guest-brand-dark), var(--guest-brand));
    border-radius: 18px;
    padding: 18px;
    box-shadow: 0 18px 38px color-mix(in srgb, var(--guest-brand) 18%, transparent);
    overflow: hidden;
    position: relative;
}
.guest-hero::after {
    content: '';
    position: absolute;
    width: 180px;
    height: 180px;
    right: -58px;
    top: -72px;
    border-radius: 999px;
    background: color-mix(in srgb, #fff 14%, transparent);
}
.guest-hero > * { position: relative; z-index: 1; }
.guest-hero-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: rgba(255,255,255,.16);
    color: #fff;
}
.guest-primary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 40px;
    padding: .625rem 1rem;
    border-radius: 12px;
    background: #fff;
    color: var(--guest-brand-dark);
    font-weight: 800;
    box-shadow: 0 10px 22px rgba(15,23,42,.14);
    transition: transform .18s ease, box-shadow .18s ease;
}
.guest-primary-btn:hover { transform: translateY(-1px); box-shadow: 0 14px 28px rgba(15,23,42,.18); }
.guest-stat,
.guest-panel {
    background: #fff;
    border: 1px solid var(--guest-border);
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}
.guest-stat { padding: 16px; }
.guest-stat-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: var(--guest-brand-soft);
    color: var(--guest-brand);
}
.guest-control {
    border-color: var(--guest-border) !important;
    background: color-mix(in srgb, var(--guest-brand) 3%, #fff);
}
.guest-control:focus {
    border-color: var(--guest-brand) !important;
    box-shadow: 0 0 0 3px var(--guest-ring) !important;
    outline: none !important;
}
.guest-filter-btn {
    background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-dark));
    color: #fff;
    border-radius: 11px;
    min-height: 40px;
}
.guest-reset-btn {
    background: #F1F5F9;
    color: #475569;
    border-radius: 11px;
    min-height: 40px;
}
.guest-table thead { background: #F8FAFC; }
.guest-table th { color: #64748B; font-weight: 800; letter-spacing: .045em; }
.guest-row { transition: background .16s ease; }
.guest-row:hover { background: var(--guest-brand-soft); }
.guest-id {
    color: #64748B;
    font-size: .72rem;
}
.guest-action {
    width: 32px;
    height: 32px;
    display: inline-grid;
    place-items: center;
    border-radius: 10px;
    background: #F8FAFC;
    transition: background .16s ease, transform .16s ease;
}
.guest-action:hover { transform: translateY(-1px); }
.guest-action-view { color: #2563EB; }
.guest-action-edit { color: #B45309; }
.guest-action-book { color: #047857; }
.guest-action-view:hover { background: #DBEAFE; }
.guest-action-edit:hover { background: #FEF3C7; }
.guest-action-book:hover { background: #D1FAE5; }
.guest-empty {
    border-top: 1px solid var(--guest-border);
    background: linear-gradient(180deg, #fff, var(--guest-brand-soft));
}
.guest-pagination-active {
    border-color: var(--guest-brand) !important;
    background: var(--guest-brand) !important;
    color: #fff !important;
}
</style>

<div class="guests-page p-4 sm:p-6">
    <!-- Header -->
    <div class="guest-hero mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            <div class="guest-hero-icon">
                <i class="fas fa-users text-lg"></i>
            </div>
            <div>
                <h1 class="text-2xl font-extrabold text-white">Huéspedes</h1>
                <p class="text-white/75 mt-1 text-sm">Directorio operativo de clientes, contacto, origen y visitas.</p>
            </div>
        </div>
        
        <a href="<?= url('huespedes/create') ?>" 
           class="guest-primary-btn">
            <i class="fas fa-user-plus mr-2"></i>Nuevo Huésped
        </a>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="guest-stat">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-sm font-semibold">Total huéspedes</p>
                    <p class="text-3xl font-extrabold text-slate-900"><?= number_format($total_huespedes) ?></p>
                </div>
                <div class="guest-stat-icon">
                    <i class="fas fa-users text-xl"></i>
                </div>
            </div>
        </div>
        
       
        
        <div class="guest-stat">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-500 text-sm font-semibold">Estados registrados</p>
                    <p class="text-3xl font-extrabold text-slate-900">
                        <?= count(array_unique(array_column($huespedes, 'procedencia_estado'))) ?>
                    </p>
                </div>
                <div class="guest-stat-icon">
                    <i class="fas fa-map-marked-alt text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="guest-panel p-4 mb-6">
        <form method="GET" action="<?= url('huespedes') ?>" class="flex flex-wrap gap-3 items-end">
            <!-- Búsqueda -->
            <div class="flex-1 min-w-[300px]">
                <label class="block text-sm font-bold text-slate-700 mb-1">Buscar</label>
                <div class="relative">
                    <input type="text" 
                           name="buscar" 
                           value="<?= htmlspecialchars($buscar ?? '') ?>"
                           placeholder="Nombre, teléfono, email o placas..."
                           class="guest-control w-full pl-10 pr-4 py-2 border rounded-lg">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
            
            <!-- Estado -->
            <div class="w-full md:w-auto">
                <label class="block text-sm font-bold text-slate-700 mb-1">Estado de Procedencia</label>
                <select name="estado" class="guest-control px-3 py-2 border rounded-lg">
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
                <button type="submit" class="guest-filter-btn px-4 py-2 transition duration-200">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="<?= url('huespedes') ?>" class="guest-reset-btn px-4 py-2 transition duration-200 inline-flex items-center">
                    <i class="fas fa-times mr-2"></i>Limpiar
                </a>
            </div>
        </form>
    </div>
    
    <!-- Tabla de huéspedes -->
    <div class="guest-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="guest-table w-full">
                <thead>
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
                    <tr class="guest-row">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($huesped['nombre_completo']) ?>
                                </div>
                                <div class="guest-id">
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
                       class="font-semibold" style="color:var(--guest-brand);">
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
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $huesped['total_reservaciones'] >= 3 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-800' ?>">
                                    <?= $huesped['total_reservaciones'] ?>
                                    <?php if ($huesped['total_reservaciones'] >= 3): ?>
                                        <i class="fas fa-star ml-1 text-amber-500"></i>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400">0</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center gap-2">
                                <a href="<?= url('huespedes/' . $huesped['id']) ?>" 
                                   class="guest-action guest-action-view" title="Ver detalles">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>" 
                                   class="guest-action guest-action-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" 
                                   class="guest-action guest-action-book" title="Nueva reservación">
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
        <div class="guest-empty text-center py-12 px-4">
            <div class="mx-auto mb-4 w-16 h-16 rounded-2xl grid place-items-center" style="background:var(--guest-brand-soft);color:var(--guest-brand);">
                <i class="fas fa-users text-2xl"></i>
            </div>
            <p class="text-xl font-bold text-slate-700">No se encontraron huéspedes</p>
            <p class="text-slate-500 mt-2">Ajusta los filtros o registra un huésped nuevo para continuar.</p>
            <a href="<?= url('huespedes/create') ?>" class="guest-primary-btn mt-5">
                <i class="fas fa-user-plus"></i>Nuevo Huésped
            </a>
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
                    <span class="guest-pagination-active relative inline-flex items-center px-4 py-2 border text-sm font-medium">
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
