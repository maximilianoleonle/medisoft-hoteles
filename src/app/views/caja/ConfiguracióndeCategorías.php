<?php
/**
 * Vista de Configuración de Categorías
 * Los Cedros
 */
?>

<!-- Configuración de Categorías -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-orange-500">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Categorías de Movimientos
                    </h1>
                    <p class="text-gray-600">
                        Configuración y gestión de categorías para ingresos y gastos
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <?php $back_arrow_href = url('caja'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                    <a href="<?= url('caja') ?>"
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2 ms-back-legacy">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver</span>
                    </a>
                    <button onclick="mostrarModalNueva()" 
                            class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:shadow-lg transition flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Nueva Categoría</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas de Uso -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-chart-bar mr-2 text-purple-500"></i>
                Estadísticas de Uso
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach ($estadisticas as $stat): ?>
                    <?php if ($stat['total_movimientos'] > 0): ?>
                        <div class="bg-gray-50 rounded-lg p-4 text-center">
                            <div class="w-12 h-12 mx-auto mb-2 flex items-center justify-center rounded-full" 
                                 style="background-color: <?= $stat['color'] ?>20;">
                                <i class="<?= $stat['icono'] ?> text-lg" style="color: <?= $stat['color'] ?>"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-800 mb-1">
                                <?= htmlspecialchars($stat['nombre']) ?>
                            </p>
                            <p class="text-xs text-gray-600">
                                <?= $stat['total_movimientos'] ?> movimientos
                            </p>
                            <p class="text-xs font-semibold <?= $stat['tipo'] == 'ingreso' ? 'text-green-600' : 'text-red-600' ?>">
                                $<?= number_format($stat['monto_total'], 2) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Categorías de Ingresos -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 to-green-700 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-arrow-down mr-2"></i>
                    Categorías de Ingresos
                </h2>
            </div>
            <div class="p-4">
                <div class="space-y-2" id="categoriasIngresos">
                    <?php foreach ($categorias as $cat): ?>
                        <?php if ($cat['tipo'] == 'ingreso' || $cat['tipo'] == 'ambos'): ?>
                            <div class="categoria-item flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition" 
                                 data-id="<?= $cat['id'] ?>" data-orden="<?= $cat['orden'] ?>">
                                <div class="flex items-center gap-3">
                                    <button class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-grip-vertical"></i>
                                    </button>
                                    <div class="w-10 h-10 flex items-center justify-center rounded-full" 
                                         style="background-color: <?= $cat['color'] ?>20;">
                                        <i class="<?= $cat['icono'] ?>" style="color: <?= $cat['color'] ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($cat['nombre']) ?></p>
                                        <?php if ($cat['descripcion']): ?>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($cat['descripcion']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($cat['activa']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            Inactiva
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php 
                                    // Categorías protegidas del sistema
                                    $protegidas = ['Hospedaje', 'Anticipo'];
                                    $esProtegida = in_array($cat['nombre'], $protegidas);
                                    ?>
                                    
                                    <?php if (!$esProtegida): ?>
                                        <button onclick="editarCategoria(<?= $cat['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleCategoria(<?= $cat['id'] ?>, <?= $cat['activa'] ?>)" 
                                                class="<?= $cat['activa'] ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800' ?>"
                                                title="<?= $cat['activa'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500">
                                            <i class="fas fa-lock mr-1"></i>
                                            Sistema
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Categorías de Gastos -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-700 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-arrow-up mr-2"></i>
                    Categorías de Gastos
                </h2>
            </div>
            <div class="p-4">
                <div class="space-y-2" id="categoriasGastos">
                    <?php foreach ($categorias as $cat): ?>
                        <?php if ($cat['tipo'] == 'gasto' || $cat['tipo'] == 'ambos'): ?>
                            <div class="categoria-item flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition" 
                                 data-id="<?= $cat['id'] ?>" data-orden="<?= $cat['orden'] ?>">
                                <div class="flex items-center gap-3">
                                    <button class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-grip-vertical"></i>
                                    </button>
                                    <div class="w-10 h-10 flex items-center justify-center rounded-full" 
                                         style="background-color: <?= $cat['color'] ?>20;">
                                        <i class="<?= $cat['icono'] ?>" style="color: <?= $cat['color'] ?>"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800"><?= htmlspecialchars($cat['nombre']) ?></p>
                                        <?php if ($cat['descripcion']): ?>
                                            <p class="text-xs text-gray-500"><?= htmlspecialchars($cat['descripcion']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <?php if ($cat['activa']): ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                            Inactiva
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!$esProtegida): ?>
                                        <button onclick="editarCategoria(<?= $cat['id'] ?>)" 
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleCategoria(<?= $cat['id'] ?>, <?= $cat['activa'] ?>)" 
                                                class="<?= $cat['activa'] ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800' ?>"
                                                title="<?= $cat['activa'] ? 'Desactivar' : 'Activar' ?>">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva/Editar Categoría -->
<div id="modalCategoria" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 p-6 rounded-t-2xl">
                <h3 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-tag mr-3"></i>
                    <span id="modalTitulo">Nueva Categoría</span>
                </h3>
            </div>
            
            <form id="formCategoria" class="p-6">
                <input type="hidden" id="categoria_id" name="id">
                
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="categoria_nombre"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" 
                               required>
                    </div>
                    
                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select name="tipo" id="categoria_tipo"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" 
                                required>
                            <option value="ingreso">Ingreso</option>
                            <option value="gasto">Gasto</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    
                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea name="descripcion" id="categoria_descripcion" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>
                    
                    <!-- Icono y Color -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Icono
                            </label>
                            <select name="icono" id="categoria_icono"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                <?php foreach ($iconos as $clase => $nombre): ?>
                                    <option value="<?= $clase ?>"><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Color
                            </label>
                            <select name="color" id="categoria_color"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500">
                                <?php foreach ($colores as $hex => $nombre): ?>
                                    <option value="<?= $hex ?>" style="color: <?= $hex ?>"><?= $nombre ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Vista previa -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Vista Previa
                        </label>
                        <div class="bg-gray-50 rounded-lg p-4 flex items-center gap-3">
                            <div id="preview-icon" class="w-12 h-12 flex items-center justify-center rounded-full">
                                <i id="preview-icon-class" class="fas fa-tag text-xl"></i>
                            </div>
                            <span id="preview-nombre" class="font-medium">Nueva Categoría</span>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="cerrarModalCategoria()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-save mr-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Librería para drag and drop -->
<script src="<?= asset('vendor/sortablejs/Sortable.min.js') ?>"></script>

<script>
// Datos de categorías para edición
const categoriasData = <?= json_encode($categorias) ?>;

// Inicializar Sortable para reordenamiento
document.addEventListener('DOMContentLoaded', function() {
    // Para categorías de ingresos
    new Sortable(document.getElementById('categoriasIngresos'), {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'bg-blue-100',
        onEnd: function(evt) {
            actualizarOrden();
        }
    });
    
    // Para categorías de gastos
    new Sortable(document.getElementById('categoriasGastos'), {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'bg-blue-100',
        onEnd: function(evt) {
            actualizarOrden();
        }
    });
});

// Actualizar vista previa
document.getElementById('categoria_nombre').addEventListener('input', function() {
    document.getElementById('preview-nombre').textContent = this.value || 'Nueva Categoría';
});

document.getElementById('categoria_icono').addEventListener('change', function() {
    const iconClass = this.value;
    const previewIcon = document.getElementById('preview-icon-class');
    previewIcon.className = iconClass + ' text-xl';
});

document.getElementById('categoria_color').addEventListener('change', function() {
    const color = this.value;
    document.getElementById('preview-icon').style.backgroundColor = color + '20';
    document.getElementById('preview-icon-class').style.color = color;
});

// Mostrar modal nueva categoría
function mostrarModalNueva() {
    document.getElementById('modalTitulo').textContent = 'Nueva Categoría';
    document.getElementById('formCategoria').reset();
    document.getElementById('categoria_id').value = '';
    
    // Reset vista previa
    document.getElementById('preview-nombre').textContent = 'Nueva Categoría';
    document.getElementById('preview-icon-class').className = 'fas fa-tag text-xl';
    document.getElementById('preview-icon').style.backgroundColor = '#6B728020';
    document.getElementById('preview-icon-class').style.color = '#6B7280';
    
    document.getElementById('modalCategoria').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

// Cerrar modal
function cerrarModalCategoria() {
    document.getElementById('modalCategoria').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Editar categoría
function editarCategoria(id) {
    const categoria = categoriasData.find(c => c.id == id);
    if (!categoria) return;
    
    document.getElementById('modalTitulo').textContent = 'Editar Categoría';
    document.getElementById('categoria_id').value = categoria.id;
    document.getElementById('categoria_nombre').value = categoria.nombre;
    document.getElementById('categoria_tipo').value = categoria.tipo;
    document.getElementById('categoria_descripcion').value = categoria.descripcion || '';
    document.getElementById('categoria_icono').value = categoria.icono;
    document.getElementById('categoria_color').value = categoria.color;
    
    // Actualizar vista previa
    document.getElementById('preview-nombre').textContent = categoria.nombre;
    document.getElementById('preview-icon-class').className = categoria.icono + ' text-xl';
    document.getElementById('preview-icon').style.backgroundColor = categoria.color + '20';
    document.getElementById('preview-icon-class').style.color = categoria.color;
    
    document.getElementById('modalCategoria').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

// Guardar categoría
document.getElementById('formCategoria').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const id = formData.get('id');
    const url = id ? '<?= url('caja/categoria/actualizar') ?>' : '<?= url('caja/categoria/crear') ?>';
    
    fetch(url, {
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
            Swal.fire('Éxito', data.message, 'success')
                .then(() => location.reload());
        } else {
            Swal.fire('Error', data.message || 'Error al guardar la categoría', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Error al procesar la solicitud', 'error');
    });
});

// Toggle activar/desactivar
function toggleCategoria(id, estadoActual) {
    const accion = estadoActual ? 'desactivar' : 'activar';
    
    Swal.fire({
        title: `¿${accion.charAt(0).toUpperCase() + accion.slice(1)} categoría?`,
        text: `¿Está seguro de que desea ${accion} esta categoría?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: estadoActual ? '#DC2626' : '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= url('caja/categoria/toggle') ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Éxito', data.message, 'success')
                        .then(() => location.reload());
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Error al procesar la solicitud', 'error');
            });
        }
    });
}

// Actualizar orden
function actualizarOrden() {
    const ordenamiento = {};
    let orden = 1;
    
    // Recolectar orden de todas las categorías
    document.querySelectorAll('.categoria-item').forEach(item => {
        const id = item.dataset.id;
        ordenamiento[orden] = id;
        orden++;
    });
    
    // Enviar al servidor
    fetch('<?= url('caja/categoria/orden') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ orden: ordenamiento })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            Swal.fire('Error', 'No se pudo actualizar el orden', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCategoria();
    }
});
</script>