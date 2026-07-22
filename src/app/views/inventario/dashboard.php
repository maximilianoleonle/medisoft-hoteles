<!-- Dashboard de Inventario -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-chart-line text-xl opacity-80"></i>
                        Resumen de inventario
                    </h1>
                    <p class="text-hotel-gold mt-1 text-sm">
                        Análisis y estadísticas del inventario
                    </p>
                </div>
                <a href="<?= url('inventario') ?>" 
                   class="bg-white/10 backdrop-blur text-white px-4 py-2 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-2 border border-white/20">
                    <i class="fas fa-boxes"></i>
                    <span>Ver Inventario</span>
                </a>
            </div>
        </div>
    </div>
    
    
    
    <div class="container mx-auto px-6 py-6">
        <!-- Widgets Principales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Productos -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-box text-blue-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">
                            <?= $estadisticas['total_productos'] ?? 0 ?>
                        </span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Productos</h3>
                    <p class="text-xs text-gray-400 mt-1">Productos activos</p>
                </div>
            </div>
            
            <div class="page-title-right">
    <div class="btn-group">
        <?php if (in_array($_SESSION['usuario_rol'], ['gerente', 'administrador'])): ?>
            <a href="<?= url('inventario/configuracion') ?>"
               class="btn btn-info">
                <i class="fas fa-cog me-1"></i> 
                Configurar por Habitación
            </a>
        <?php endif; ?>
        
        <a href="<?= url('inventario/nuevo') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> 
            Nuevo Producto
        </a>
    </div>
</div>
            
            <!-- Stock Bajo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-red-100 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-red-700">
                            <?= $estadisticas['stock_bajo'] ?? 0 ?>
                        </span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Stock Bajo</h3>
                    <p class="text-xs text-gray-400 mt-1">Requieren atención</p>
                </div>
            </div>
            
            <!-- Valor Inventario -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">
                            <?= format_money($estadisticas['valor_total'] ?? 0) ?>
                        </span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Valor Total</h3>
                    <p class="text-xs text-gray-400 mt-1">En inventario</p>
                </div>
            </div>
            
            <!-- Movimientos Hoy -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-lg transition-shadow duration-300">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-sync text-purple-600 text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold text-gray-800">
                            <?= $estadisticas['movimientos_hoy'] ?? 0 ?>
                        </span>
                    </div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Movimientos</h3>
                    <p class="text-xs text-gray-400 mt-1">Hoy</p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Productos con Stock Bajo -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    Productos con Stock Bajo
                </h3>
                
                <div class="space-y-3">
                    <?php foreach ($productos_bajo_stock as $producto): ?>
                        <?php 
                        $porcentaje = $producto['stock_minimo'] > 0 
                            ? ($producto['stock_actual'] / $producto['stock_minimo'] * 100) 
                            : 0;
                        ?>
                        <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <p class="font-medium text-gray-800">
                                        <?= htmlspecialchars($producto['nombre']) ?>
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        <?= htmlspecialchars($producto['categoria_nombre']) ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-red-600">
                                        <?= number_format($producto['stock_actual'], 0) ?> pzs
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        Mín: <?= number_format($producto['stock_minimo'], 0) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-red-500 h-2 rounded-full transition-all duration-300" 
                                     style="width: <?= min($porcentaje, 100) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($productos_bajo_stock)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                            <p>Todos los productos tienen stock suficiente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Consumo por Categoría -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-pie text-blue-500"></i>
                    Consumo por Categoría (Últimos 30 días)
                </h3>
                
                <div class="space-y-3">
                    <?php foreach ($consumo_categorias as $cat): ?>
                        <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                            <div>
                                <p class="font-medium text-gray-800">
                                    <?= htmlspecialchars($cat['categoria']) ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= $cat['dias_activos'] ?> días con movimientos
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-800">
                                    <?= number_format($cat['salidas'], 0) ?>
                                </p>
                                <p class="text-xs text-gray-500">salidas</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($consumo_categorias)): ?>
                        <div class="text-center py-4 text-gray-500">
                            <p>No hay datos de consumo disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Movimientos Recientes -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center justify-between">
                <span class="flex items-center gap-2">
                    <i class="fas fa-history text-purple-500"></i>
                    Movimientos Recientes
                </span>
                <a href="<?= url('inventario/movimientos') ?>" 
                   class="text-sm text-blue-600 hover:text-blue-800">
                    Ver todos <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="border-b border-gray-200">
                        <tr>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">
                                Fecha
                            </th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">
                                Producto
                            </th>
                            <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wider py-2">
                                Tipo
                            </th>
                            <th class="text-center text-xs font-medium text-gray-500 uppercase tracking-wider py-2">
                                Cantidad
                            </th>
                            <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-2">
                                Usuario
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($movimientos_recientes as $mov): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-3 text-sm text-gray-600">
                                    <?= format_datetime($mov['created_at'], 'd/m H:i') ?>
                                </td>
                                <td class="py-3 text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($mov['producto_nombre']) ?>
                                </td>
                                <td class="py-3 text-center">
                                    <?php if ($mov['tipo_movimiento'] == 'ENTRADA'): ?>
                                        <span class="text-green-600 text-sm font-medium">
                                            <i class="fas fa-plus-circle mr-1"></i>Entrada
                                        </span>
                                    <?php else: ?>
                                        <span class="text-red-600 text-sm font-medium">
                                            <i class="fas fa-minus-circle mr-1"></i>Salida
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-center text-sm font-bold">
                                    <?= number_format($mov['cantidad'], 0) ?>
                                </td>
                                <td class="py-3 text-sm text-gray-600">
                                    <?= htmlspecialchars($mov['usuario_nombre']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
