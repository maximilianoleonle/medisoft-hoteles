<!-- Habitaciones Disponibles -->
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-2">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown">Habitaciones</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span>Disponibilidad</span>
        </div>
        
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 font-playfair">Disponibilidad de Habitaciones</h1>
                <p class="text-gray-600 mt-1">Consultar disponibilidad de habitaciones por fechas</p>
            </div>
            <a href="<?= url('reservaciones/crear') ?>" 
               class="bg-hotel-gold text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition duration-200 flex items-center">
                <i class="fas fa-calendar-plus mr-2"></i>Nueva Reservación
            </a>
        </div>
    </div>
    
    <!-- Formulario de búsqueda -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <form method="GET" action="<?= url('habitaciones/disponibles') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4" data-auto-filter-form>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Entrada</label>
                <input type="date" 
                       name="fecha_entrada" 
                       value="<?= $fecha_entrada ?>"
                       min="<?= date('Y-m-d') ?>"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Salida</label>
                <input type="date" 
                       name="fecha_salida" 
                       value="<?= $fecha_salida ?>"
                       min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Habitación</label>
                <select name="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown focus:border-transparent">
                    <option value="">Todos los tipos</option>
                    <?php foreach ($tipos as $key => $tipo): ?>
                        <option value="<?= $key ?>" <?= ($tipo_filtro ?? '') == $key ? 'selected' : '' ?>>
                            <?= $tipo ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="w-full bg-hotel-brown text-white px-4 py-2 rounded-lg hover:bg-hotel-brown-dark transition duration-200">
                    <i class="fas fa-search mr-2"></i>Buscar
                </button>
            </div>
        </form>
        
        <!-- Información de la búsqueda -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <p class="text-sm text-gray-600">
                <?php 
                $entrada = new DateTime($fecha_entrada);
                $salida = new DateTime($fecha_salida);
                $noches = $entrada->diff($salida)->days;
                ?>
                <i class="fas fa-info-circle mr-1"></i>
                Búsqueda para <strong><?= $noches ?> noche<?= $noches != 1 ? 's' : '' ?></strong>
                del <strong><?= format_date($fecha_entrada) ?></strong> al <strong><?= format_date($fecha_salida) ?></strong>
            </p>
        </div>
    </div>
    
    <!-- Promoción de cortesía -->
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
            <i class="fas fa-gift text-green-600 text-2xl mr-3"></i>
            <div>
                <p class="text-green-800 font-semibold">¡Promoción Especial!</p>
                <p class="text-green-700 text-sm">Por cada 10 habitaciones reservadas, 1 es GRATIS. Se aplica automáticamente al crear la reservación.</p>
            </div>
        </div>
    </div>
    
    <!-- Resumen de resultados -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-blue-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    <?php 
                    $total_disponibles = count($habitaciones_disponibles ?? []);
                    $total_no_disponibles = count($habitaciones_no_disponibles ?? []);
                    $total_todas = count($todas_las_habitaciones ?? []);
                    ?>
                    Total de habitaciones: <strong><?= $total_todas ?></strong> | 
                    <span class="text-green-700"><i class="fas fa-check-circle"></i> Disponibles: <?= $total_disponibles ?></span> | 
                    <span class="text-red-700"><i class="fas fa-times-circle"></i> Ocupadas: <?= $total_no_disponibles ?></span>
                </p>
            </div>
            <?php if ($total_disponibles > 0): ?>
            <a href="<?= url('reservaciones/crear?fecha_entrada=' . $fecha_entrada . '&fecha_salida=' . $fecha_salida) ?>" 
               class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center">
                <i class="fas fa-calendar-plus mr-2"></i>
                Crear Reservación
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Leyenda -->
    <div class="flex gap-4 mb-6 text-sm">
        <div class="flex items-center">
            <div class="w-4 h-4 bg-green-100 border border-green-300 rounded mr-2"></div>
            <span>Disponible</span>
        </div>
        <div class="flex items-center">
            <div class="w-4 h-4 bg-red-100 border border-red-300 rounded mr-2"></div>
            <span>Ocupada</span>
        </div>
    </div>
    
    <!-- Habitaciones por tipo -->
    <?php 
    // Agrupar todas las habitaciones por tipo
    $todasPorTipo = [];
    foreach ($todas_las_habitaciones ?? [] as $hab) {
        $tipo = $hab['tipo'];
        if (!isset($todasPorTipo[$tipo])) {
            $todasPorTipo[$tipo] = [];
        }
        // Marcar si está disponible
        $hab['disponible'] = false;
        foreach ($habitaciones_disponibles ?? [] as $disp) {
            if ($disp['id'] == $hab['id']) {
                $hab['disponible'] = true;
                break;
            }
        }
        // Si no está disponible, buscar información de la reservación
        if (!$hab['disponible']) {
            foreach ($habitaciones_no_disponibles ?? [] as $noDisp) {
                if ($noDisp['id'] == $hab['id']) {
                    $hab['info_ocupacion'] = $noDisp['info_ocupacion'] ?? null;
                    break;
                }
            }
        }
        $todasPorTipo[$tipo][] = $hab;
    }
    ?>
    
    <?php foreach ($todasPorTipo as $tipo => $habitacionesTipo): ?>
    <div class="mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
            <i class="fas fa-bed mr-2 text-hotel-brown"></i>
            <?php 
            $nombreTipo = isset($tipos[$tipo]) ? $tipos[$tipo] : ucfirst($tipo);
            $disponibles_tipo = array_filter($habitacionesTipo, function($h) { return $h['disponible']; });
            ?>
            <?= $nombreTipo ?> (<?= count($disponibles_tipo) ?> disponibles de <?= count($habitacionesTipo) ?>)
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($habitacionesTipo as $habitacion): ?>
            <div class="<?= $habitacion['disponible'] ? 'bg-white' : 'bg-red-50' ?> rounded-lg shadow-lg p-5 transition-all duration-200 border-2 <?= $habitacion['disponible'] ? 'border-transparent hover:border-hotel-gold hover:shadow-xl' : 'border-red-200 opacity-75' ?>">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h3 class="text-2xl font-bold <?= $habitacion['disponible'] ? 'text-hotel-brown' : 'text-red-700' ?>">
                            Habitación <?= htmlspecialchars($habitacion['numero']) ?>
                        </h3>
                        <p class="text-sm <?= $habitacion['disponible'] ? 'text-gray-600' : 'text-red-600' ?>">
                            Piso <?= $habitacion['piso'] ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold <?= $habitacion['disponible'] ? 'text-gray-800' : 'text-red-700' ?>">
                            <?= format_money($habitacion['precio_base']) ?>
                        </p>
                        <p class="text-xs <?= $habitacion['disponible'] ? 'text-gray-500' : 'text-red-500' ?>">
                            por noche
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($habitacion['caracteristicas'])): ?>
                <p class="text-sm <?= $habitacion['disponible'] ? 'text-gray-600' : 'text-red-600' ?> mb-4 line-clamp-2">
                    <?= htmlspecialchars($habitacion['caracteristicas']) ?>
                </p>
                <?php endif; ?>
                
                <div class="border-t <?= $habitacion['disponible'] ? 'border-gray-200' : 'border-red-200' ?> pt-3">
                    <?php if ($habitacion['disponible']): ?>
                        <p class="text-sm text-gray-600 mb-3">
                            <i class="fas fa-calculator mr-1"></i>
                            Total por <?= $noches ?> noche<?= $noches != 1 ? 's' : '' ?>: 
                            <strong class="text-hotel-brown text-lg">
                                <?= format_money($habitacion['precio_base'] * $noches) ?>
                            </strong>
                        </p>
                        
                        <div class="bg-green-50 p-3 rounded-lg border border-green-200">
                            <p class="text-xs text-green-700 text-center font-semibold">
                                <i class="fas fa-check-circle mr-1"></i>
                                DISPONIBLE
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="bg-red-100 p-3 rounded-lg border border-red-300">
                            <p class="text-sm text-red-800 text-center font-semibold mb-1">
                                <i class="fas fa-times-circle mr-1"></i>
                                NO DISPONIBLE
                            </p>
                            <?php if (!empty($habitacion['info_ocupacion'])): ?>
                                <p class="text-xs text-red-600 text-center">
                                    Ocupada por: <?= htmlspecialchars($habitacion['info_ocupacion']['huesped'] ?? 'Reservado') ?>
                                    <br>
                                    <?= format_date($habitacion['info_ocupacion']['fecha_entrada'] ?? '') ?> - 
                                    <?= format_date($habitacion['info_ocupacion']['fecha_salida'] ?? '') ?>
                                </p>
                            <?php else: ?>
                                <p class="text-xs text-red-600 text-center">
                                    Ocupada en las fechas seleccionadas
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php if (empty($todasPorTipo)): ?>
        <!-- Sin habitaciones -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <i class="fas fa-hotel text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-800 mb-2">No se encontraron habitaciones</h3>
            <p class="text-gray-600">
                No hay habitaciones registradas en el sistema.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Botón flotante para crear reservación (solo si hay disponibles) -->
    <?php if ($total_disponibles > 0): ?>
    <div class="fixed bottom-6 right-6 z-40">
        <a href="<?= url('reservaciones/crear?fecha_entrada=' . $fecha_entrada . '&fecha_salida=' . $fecha_salida) ?>" 
           class="bg-hotel-gold text-white px-6 py-3 rounded-full shadow-xl hover:bg-yellow-600 transition-all duration-200 flex items-center group">
            <i class="fas fa-calendar-plus mr-2"></i>
            <span class="hidden group-hover:inline">Crear Reservación</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- Script para validación de fechas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fechaEntrada = document.querySelector('input[name="fecha_entrada"]');
    const fechaSalida = document.querySelector('input[name="fecha_salida"]');
    
    // Actualizar fecha mínima de salida cuando cambia la entrada
    fechaEntrada.addEventListener('change', function() {
        const fechaMin = new Date(this.value);
        fechaMin.setDate(fechaMin.getDate() + 1);
        fechaSalida.min = fechaMin.toISOString().split('T')[0];
        
        // Si la fecha de salida es menor que la nueva fecha mínima, actualizarla
        if (fechaSalida.value <= this.value) {
            fechaSalida.value = fechaMin.toISOString().split('T')[0];
        }
    });
});
</script>
