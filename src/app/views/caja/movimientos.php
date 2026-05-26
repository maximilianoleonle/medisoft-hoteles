<?php
/**
 * Vista de Movimientos de Caja
 * Los Cedros
 */
?>

<!-- Movimientos de Caja -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-purple-500">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Movimientos de Caja
                    </h1>
                    <p class="text-gray-600">
                        Historial completo de ingresos y gastos
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <a href="<?= url('caja') ?>" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver</span>
                    </a>
                    <button onclick="toggleFiltros()" 
                            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                        <i class="fas fa-filter"></i>
                        <span>Filtros</span>
                    </button>
                    <button onclick="exportarMovimientos()" 
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                        <i class="fas fa-download"></i>
                        <span>Exportar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel de Filtros -->
    <div id="panelFiltros" class="max-w-7xl mx-auto mb-6 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="GET" action="<?= url('caja/movimientos') ?>" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $key => $tipo): ?>
                            <option value="<?= $key ?>" <?= $filtros['tipo'] == $key ? 'selected' : '' ?>>
                                <?= $tipo['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Categoría -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <select name="categoria" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $filtros['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Método de Pago -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Método de Pago</label>
                    <select name="metodo_pago" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                        <option value="">Todos</option>
                        <?php foreach ($metodos_pago as $key => $metodo): ?>
                            <option value="<?= $key ?>" <?= $filtros['metodo_pago'] == $key ? 'selected' : '' ?>>
                                <?= $metodo['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Búsqueda -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input type="text" name="buscar" 
                           value="<?= htmlspecialchars($filtros['buscar'] ?? '') ?>"
                           placeholder="Descripción, referencia..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <!-- Fecha Inicio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" 
                           value="<?= $filtros['fecha_inicio'] ?? '' ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <!-- Fecha Fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" 
                           value="<?= $filtros['fecha_fin'] ?? '' ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                
                <!-- Botones -->
                <div class="md:col-span-2 flex gap-3">
                    <button type="submit" 
                            class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-search mr-2"></i>
                        Buscar
                    </button>
                    <a href="<?= url('caja/movimientos') ?>" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-2"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Resumen de Totales -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Ingresos</p>
                        <p class="text-2xl font-bold text-green-600">
                            +$<?= number_format($totales['ingresos'], 2) ?>
                        </p>
                    </div>
                    <i class="fas fa-arrow-down text-3xl text-green-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Gastos</p>
                        <p class="text-2xl font-bold text-red-600">
                            -$<?= number_format($totales['gastos'], 2) ?>
                        </p>
                    </div>
                    <i class="fas fa-arrow-up text-3xl text-red-500"></i>
                </div>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Balance</p>
                        <p class="text-2xl font-bold <?= $totales['balance'] >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                            <?= $totales['balance'] >= 0 ? '+' : '' ?>$<?= number_format($totales['balance'], 2) ?>
                        </p>
                    </div>
                    <i class="fas fa-balance-scale text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de Movimientos -->
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>
                        Lista de Movimientos (<?= count($movimientos) ?>)
                    </h2>
                    <?php if (!empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])): ?>
                        <span class="text-white/80 text-sm">
                            <?php if ($filtros['fecha_inicio'] == $filtros['fecha_fin']): ?>
                                <?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?>
                            <?php else: ?>
                                <?= date('d/m/Y', strtotime($filtros['fecha_inicio'])) ?> - 
                                <?= date('d/m/Y', strtotime($filtros['fecha_fin'])) ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($movimientos)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No se encontraron movimientos con los filtros seleccionados</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr class="text-sm text-gray-600">
                                <th class="text-left p-3">Fecha/Hora</th>
                                <th class="text-left p-3">Tipo</th>
                                <th class="text-left p-3">Descripción</th>
                                <th class="text-left p-3">Categoría</th>
                                <th class="text-left p-3">Método</th>
                                <th class="text-left p-3">Usuario</th>
                                <th class="text-right p-3">Monto</th>
                                <th class="text-center p-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($movimientos as $mov): ?>
                                <tr class="hover:bg-gray-50" data-id="<?= $mov['id'] ?>">
                                    <td class="p-3 text-sm">
                                        <div>
                                            <p class="font-medium"><?= date('d/m/Y', strtotime($mov['created_at'])) ?></p>
                                            <p class="text-xs text-gray-500"><?= date('H:i:s', strtotime($mov['created_at'])) ?></p>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            <?= $mov['tipo'] == 'ingreso' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <i class="fas fa-arrow-<?= $mov['tipo'] == 'ingreso' ? 'down' : 'up' ?> mr-1"></i>
                                            <?= ucfirst($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <p class="font-medium"><?= htmlspecialchars($mov['descripcion']) ?></p>
                                        <?php if ($mov['referencia']): ?>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-hashtag mr-1"></i>
                                                Ref: <?= htmlspecialchars($mov['referencia']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($mov['proveedor']): ?>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-building mr-1"></i>
                                                <?= htmlspecialchars($mov['proveedor']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($mov['comprobante']): ?>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-file-invoice mr-1"></i>
                                                <?= htmlspecialchars($mov['comprobante']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if ($mov['editado']): ?>
                                            <p class="text-xs text-orange-600">
                                                <i class="fas fa-edit mr-1"></i>
                                                Editado: <?= htmlspecialchars($mov['motivo_edicion']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <?php if ($mov['categoria_nombre']): ?>
                                            <span class="flex items-center gap-2">
                                                <i class="<?= $mov['categoria_icono'] ?>" 
                                                   style="color: <?= $mov['categoria_color'] ?>"></i>
                                                <?= htmlspecialchars($mov['categoria_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <span class="flex items-center gap-2">
                                            <i class="fas fa-<?= $metodos_pago[$mov['metodo_pago']]['icon'] ?> 
                                               text-<?= $metodos_pago[$mov['metodo_pago']]['color'] ?>-500"></i>
                                            <?= ucfirst($mov['metodo_pago']) ?>
                                        </span>
                                    </td>
                                    <td class="p-3 text-sm text-gray-600">
                                        <?= htmlspecialchars($mov['usuario_nombre']) ?>
                                    </td>
                                    <td class="p-3 text-right">
                                        <p class="font-semibold <?= $mov['tipo'] == 'ingreso' ? 'text-green-600' : 'text-red-600' ?>">
                                            <?= $mov['tipo'] == 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'], 2) ?>
                                        </p>
                                        <?php if ($mov['reservacion_id']): ?>
                                            <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>" 
                                               class="text-xs text-blue-600 hover:underline">
                                                <i class="fas fa-bed mr-1"></i>
                                                Reserva #<?= $mov['reservacion_id'] ?>
                                            </a>
                                            <?php if (!empty($mov['habitaciones_detalle'])): ?>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    <i class="fas fa-door-open mr-1"></i>
                                                    <?= htmlspecialchars($mov['habitaciones_detalle']) ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <?php if ($mov['corte_id'] == ($corteActual['id'] ?? 0) && !$mov['editado']): ?>
                                            <button onclick="editarMovimiento(<?= $mov['id'] ?>)" 
                                                    class="text-blue-600 hover:text-blue-800 mr-2"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="verDetalle(<?= $mov['id'] ?>)" 
                                                class="text-gray-600 hover:text-gray-800"
                                                title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Editar Movimiento -->
<div id="modalEditar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6 rounded-t-2xl">
                <h3 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-edit mr-3"></i>
                    Editar Movimiento
                </h3>
            </div>
            
            <form id="formEditar" class="p-6">
                <input type="hidden" id="edit_id" name="id">
                
                <div class="space-y-4">
                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción <span class="text-red-500">*</span>
                        </label>
                        <textarea id="edit_descripcion" name="descripcion" rows="2" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                  required></textarea>
                    </div>
                    
                    <!-- Monto -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Monto <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                            <input type="number" id="edit_monto" name="monto" step="0.01" min="0.01"
                                   class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                   required>
                        </div>
                    </div>
                    
                    <!-- Método de Pago -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Método de Pago <span class="text-red-500">*</span>
                        </label>
                        <select id="edit_metodo_pago" name="metodo_pago" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required>
                            <?php foreach ($metodos_pago as $key => $metodo): ?>
                                <option value="<?= $key ?>"><?= $metodo['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Referencia -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Referencia
                        </label>
                        <input type="text" id="edit_referencia" name="referencia" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <!-- Motivo de Edición -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo de la Edición <span class="text-red-500">*</span>
                        </label>
                        <textarea name="motivo_edicion" rows="2" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" 
                                  placeholder="Explique brevemente por qué edita este movimiento" required></textarea>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="cerrarModalEditar()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle panel de filtros
function toggleFiltros() {
    const panel = document.getElementById('panelFiltros');
    panel.classList.toggle('hidden');
}

// Mostrar filtros si hay alguno activo
<?php if (array_filter($filtros)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        toggleFiltros();
    });
<?php endif; ?>

// Editar movimiento
function editarMovimiento(id) {
    // Obtener datos del movimiento
    fetch(`<?= url('caja/movimiento') ?>?id=${id}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const mov = data.data;
            document.getElementById('edit_id').value = mov.id;
            document.getElementById('edit_descripcion').value = mov.descripcion;
            document.getElementById('edit_monto').value = mov.monto;
            document.getElementById('edit_metodo_pago').value = mov.metodo_pago;
            document.getElementById('edit_referencia').value = mov.referencia || '';
            
            document.getElementById('modalEditar').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            Swal.fire('Error', data.message || 'No se pudo cargar el movimiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al cargar el movimiento', 'error');
    });
}

// Cerrar modal editar
function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('formEditar').reset();
}

// Guardar edición
document.getElementById('formEditar').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= url('caja/movimiento/editar') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', 'Movimiento actualizado correctamente', 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'No se pudo actualizar el movimiento', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al actualizar el movimiento', 'error');
    });
});

// Ver detalle del movimiento
function verDetalle(id) {
    // Aquí podrías mostrar un modal con más detalles
    console.log('Ver detalle de movimiento:', id);
}

// Exportar movimientos
function exportarMovimientos() {
    const params = new URLSearchParams(window.location.search);
    params.set('formato', 'excel');
    params.set('corte_id', '<?= $corteActual['id'] ?? 0 ?>');
    
    window.location.href = '<?= url('caja/exportar') ?>?' + params.toString();
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalEditar();
    }
    
    // Ctrl/Cmd + F para mostrar filtros
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        toggleFiltros();
    }
});
</script>