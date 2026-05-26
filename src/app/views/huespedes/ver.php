<!-- Detalle de Huésped -->
<div class="p-6">
    
    <!-- Agregar antes de la sección de habitaciones -->
<?php 
echo "<!-- DEBUG HABITACIONES: ";
echo "Count: " . count($habitaciones ?? []);
echo " | Data: ";
var_dump($habitaciones);
echo " -->";
?>
    <!-- Header con navegación -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-2">
            <a href="<?= url('huespedes') ?>" class="hover:text-hotel-brown">Huéspedes</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span><?= htmlspecialchars($huesped['nombre_completo']) ?></span>
        </div>
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-800 font-playfair">
                <?= htmlspecialchars($huesped['nombre_completo']) ?>
            </h1>
            
            <div class="flex gap-2">
                <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>" 
                   class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200">
                    <i class="fas fa-edit mr-2"></i>Editar
                </a>
              <!-- Botón para nueva reservación con huésped preseleccionado -->
<a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" 
   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-hotel-gold to-yellow-600 text-white font-semibold rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
    <i class="fas fa-calendar-plus mr-2"></i>
    Nueva Reservación
</a>
                <a href="<?= url('huespedes') ?>" 
                   class="bg-hotel-brown text-white px-4 py-2 rounded-lg hover:bg-hotel-brown-dark transition duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna principal - Información y Reservaciones -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Información del Huésped -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user mr-2 text-hotel-brown"></i>
                    Información Personal
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Contacto -->
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Contacto</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-phone text-gray-400 w-5"></i>
                                <span class="ml-3">
                                    <?= htmlspecialchars($huesped['telefono'] ?: 'No registrado') ?>
                                </span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope text-gray-400 w-5"></i>
                                <span class="ml-3">
                                    <?= htmlspecialchars($huesped['email'] ?: 'No registrado') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Procedencia -->
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Procedencia</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-gray-400 w-5"></i>
                                <span class="ml-3">
                                    <?= htmlspecialchars($huesped['procedencia_estado'] ?: 'No especificado') ?>
                                </span>
                            </div>
                            <?php if ($huesped['procedencia_ciudad']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-city text-gray-400 w-5"></i>
                                <span class="ml-3">
                                    <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Vehículo -->
                    <?php if ($huesped['vehiculo_marca'] || $huesped['vehiculo_placas']): ?>
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Vehículo</h3>
                        <div class="space-y-2">
                            <?php if ($huesped['vehiculo_marca']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-car text-gray-400 w-5"></i>
                                <span class="ml-3">
                                    <?= htmlspecialchars($huesped['vehiculo_marca']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <?php if ($huesped['vehiculo_placas']): ?>
                            <div class="flex items-center">
                                <i class="fas fa-id-card text-gray-400 w-5"></i>
                                <span class="ml-3 font-mono">
                                    <?= htmlspecialchars($huesped['vehiculo_placas']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Fechas -->
                    <div>
                        <h3 class="font-semibold text-gray-700 mb-3">Registro</h3>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-plus text-gray-400 w-5"></i>
                                <span class="ml-3 text-sm">
                                    Registrado el <?= format_date($huesped['created_at']) ?>
                                </span>
                            </div>
                            <?php if ($ultima_visita): ?>
                            <div class="flex items-center">
                                <i class="fas fa-clock text-gray-400 w-5"></i>
                                <span class="ml-3 text-sm">
                                    Última visita: <?= format_date($ultima_visita) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($huesped['notas']): ?>
                <div class="mt-6 pt-6 border-t">
                    <h3 class="font-semibold text-gray-700 mb-2">Notas</h3>
                    <p class="text-gray-600"><?= nl2br(htmlspecialchars($huesped['notas'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
            <!-- Vehículos del Huésped -->
<!-- Vehículos del Huésped -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-800 flex items-center">
            <i class="fas fa-car mr-2 text-hotel-brown"></i>
            Vehículos Registrados
        </h2>
        <button type="button" onclick="abrirModalAgregarVehiculo()" 
                class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200 text-sm">
            <i class="fas fa-plus mr-2"></i>Agregar Vehículo
        </button>
    </div>
    
    <?php if (!empty($vehiculos)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($vehiculos as $vehiculo): ?>
                <div class="border-2 border-gray-200 rounded-xl p-4 hover:border-purple-300 transition-colors">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-car text-purple-600 mr-2"></i>
                                <h4 class="font-semibold text-gray-800">
                                    <?= htmlspecialchars($vehiculo['marca']) ?>
                                    <?php if ($vehiculo['modelo']): ?>
                                        <?= htmlspecialchars($vehiculo['modelo']) ?>
                                    <?php endif; ?>
                                </h4>
                            </div>
                            
                            <div class="space-y-1 text-sm">
                                <div class="flex items-center">
                                    <span class="text-gray-500 w-20">Placas:</span>
                                    <span class="font-mono font-semibold"><?= htmlspecialchars($vehiculo['placas']) ?></span>
                                </div>
                                <?php if ($vehiculo['color']): ?>
                                <div class="flex items-center">
                                    <span class="text-gray-500 w-20">Color:</span>
                                    <span><?= htmlspecialchars($vehiculo['color']) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="flex items-center">
                                    <span class="text-gray-500 w-20">Ubicación:</span>
                                    <?php
                                    $estacionamientos = HuespedVehiculo::getEstacionamientos();
                                    $ubicacion = $estacionamientos[$vehiculo['estacionamiento']] ?? 'No especificado';
                                    $iconos = [
    'coches' => 'fa-car'
];
$icono = $iconos[$vehiculo['estacionamiento']] ?? 'fa-question';
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas <?= $icono ?> mr-1"></i>
                                        <?= $ubicacion ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 ml-2">
                            <button onclick='editarVehiculo(<?= json_encode($vehiculo) ?>)' 
                                    class="text-blue-500 hover:text-blue-700" title="Editar vehículo">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmarEliminarVehiculo(<?= $vehiculo['id'] ?>, '<?= htmlspecialchars($vehiculo['marca']) ?> - <?= htmlspecialchars($vehiculo['placas']) ?>')" 
                                    class="text-red-500 hover:text-red-700" title="Eliminar vehículo">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-8">
            <i class="fas fa-car-side text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Sin vehículos registrados</p>
            <p class="text-gray-400 mt-2">Agregue vehículos para llevar control del estacionamiento</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para agregar vehículo -->
<div id="modalAgregarVehiculo" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
        <form id="formAgregarVehiculo">
            <input type="hidden" name="huesped_id" value="<?= $huesped['id'] ?>">
            <?= csrf_field() ?>
            
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-car mr-3 text-purple-600"></i>
                    Agregar Nuevo Vehículo
                </h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Marca <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="marca" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Modelo
                        </label>
                        <input type="text" name="modelo"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Placas
                        </label>
                        <input type="text" name="placas" style="text-transform: uppercase"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 font-mono">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Color
                        </label>
                        <input type="text" name="color"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                    </div>
                </div>
                
                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        Estacionamiento <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 gap-3">
        <label class="relative cursor-pointer">
            <input type="radio" name="estacionamiento" value="coches"
                   class="peer sr-only" checked>
            <div class="px-3 py-2 border-2 rounded-lg text-center transition-all text-sm
                        border-gray-200 hover:border-purple-500
                        peer-checked:border-purple-500 peer-checked:bg-purple-50">
                <i class="fas fa-car text-lg mb-1"></i>
                <p class="text-xs font-medium">Coches</p>
            </div>
        </label>
        
        
    </div>
</div>
            </div>
            
            <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                <button type="button" onclick="cerrarModalAgregarVehiculo()" 
                        class="px-6 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i> Guardar Vehículo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal para editar vehículo -->
<div id="modalEditarVehiculo" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
        <form id="formEditarVehiculo">
            <input type="hidden" name="vehiculo_id" id="edit_vehiculo_id">
            <?= csrf_field() ?>
            
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-car mr-3 text-blue-600"></i>
                    Editar Vehículo
                </h3>
            </div>
            
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Marca <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="marca" id="edit_marca" required
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Modelo
                        </label>
                        <input type="text" name="modelo" id="edit_modelo"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Placas
                        </label>
                        <input type="text" name="placas" id="edit_placas" style="text-transform: uppercase"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 font-mono">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            Color
                        </label>
                        <input type="text" name="color" id="edit_color"
                               class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    </div>
                </div>
                
                <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2">
        Estacionamiento <span class="text-red-500">*</span>
    </label>
    <div class="grid grid-cols-2 gap-3">
        <label class="relative cursor-pointer">
            <input type="radio" name="estacionamiento" value="coches"
                   class="peer sr-only" checked>
            <div class="px-3 py-2 border-2 rounded-lg text-center transition-all text-sm
                        border-gray-200 hover:border-purple-500
                        peer-checked:border-purple-500 peer-checked:bg-purple-50">
                <i class="fas fa-car text-lg mb-1"></i>
                <p class="text-xs font-medium">Coches</p>
            </div>
        </label>
        
       
    </div>
</div>
            </div>
            
            <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-2xl">
                <button type="button" onclick="cerrarModalEditarVehiculo()" 
                        class="px-6 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-100 transition-all">
                    Cancelar
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:shadow-lg transition-all">
                    <i class="fas fa-save mr-2"></i> Actualizar Vehículo
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Agregar antes de los scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Funciones para el modal de agregar
function abrirModalAgregarVehiculo() {
    document.getElementById('modalAgregarVehiculo').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalAgregarVehiculo() {
    document.getElementById('modalAgregarVehiculo').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('formAgregarVehiculo').reset();
}

// Funciones para el modal de editar
function abrirModalEditarVehiculo() {
    document.getElementById('modalEditarVehiculo').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalEditarVehiculo() {
    document.getElementById('modalEditarVehiculo').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('formEditarVehiculo').reset();
}

// Reemplazar tus funciones originales con estas
function mostrarModalIngreso() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalIngreso').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalIngreso() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalIngreso').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalIngreso').querySelector('form').reset();
}

function mostrarModalGasto() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalGasto').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalGasto() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalGasto').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalGasto').querySelector('form').reset();
}

// Modal agregar vehículo
function abrirModalAgregarVehiculo() {
    document.getElementById('sidebar').style.display = 'none'; // Oculta sidebar
    document.getElementById('modalAgregarVehiculo').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalAgregarVehiculo() {
    document.getElementById('sidebar').style.display = ''; // Muestra sidebar
    document.getElementById('modalAgregarVehiculo').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('formAgregarVehiculo').reset();
}

// Modal editar vehículo
function abrirModalEditarVehiculo() {
    document.getElementById('sidebar').style.display = 'none'; // Oculta sidebar
    document.getElementById('modalEditarVehiculo').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalEditarVehiculo() {
    document.getElementById('sidebar').style.display = ''; // Muestra sidebar
    document.getElementById('modalEditarVehiculo').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('formEditarVehiculo').reset();
}


// Función para editar vehículo
function editarVehiculo(vehiculo) {
    // Llenar el formulario con los datos del vehículo
    document.getElementById('edit_vehiculo_id').value = vehiculo.id;
    document.getElementById('edit_marca').value = vehiculo.marca;
    document.getElementById('edit_modelo').value = vehiculo.modelo || '';
    document.getElementById('edit_placas').value = vehiculo.placas || '';
    document.getElementById('edit_color').value = vehiculo.color || '';
    
    // Seleccionar el radio button correcto para estacionamiento
    const radios = document.querySelectorAll('.edit-estacionamiento');
    radios.forEach(radio => {
        if (radio.value === vehiculo.estacionamiento) {
            radio.checked = true;
        }
    });
    
    // Abrir el modal
    abrirModalEditarVehiculo();
}

// Cerrar modales al hacer clic fuera
document.getElementById('modalAgregarVehiculo').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalAgregarVehiculo();
    }
});

document.getElementById('modalEditarVehiculo').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalEditarVehiculo();
    }
});

// Convertir placas a mayúsculas en ambos formularios
document.querySelector('input[name="placas"]').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

document.getElementById('edit_placas').addEventListener('input', function(e) {
    e.target.value = e.target.value.toUpperCase();
});

// Manejar envío del formulario de agregar
// Manejar envío del formulario de agregar
document.getElementById('formAgregarVehiculo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('<?= url('huespedes/agregar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Agregar este header
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message,
                confirmButtonColor: '#5D3A1A'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#5D3A1A'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al agregar el vehículo',
            confirmButtonColor: '#5D3A1A'
        });
    });
});

// Manejar envío del formulario de editar
document.getElementById('formEditarVehiculo').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Debug: ver qué datos se están enviando
    console.log('Datos enviados:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    fetch('<?= url('huespedes/actualizar-vehiculo') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest' // Asegurar que se detecte como AJAX
        }
    })
    .then(response => {
        console.log('Status:', response.status);
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: data.message,
                confirmButtonColor: '#5D3A1A'
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Error al actualizar el vehículo',
                confirmButtonColor: '#5D3A1A'
            });
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al actualizar el vehículo. Por favor, intente nuevamente.',
            confirmButtonColor: '#5D3A1A'
        });
    });
});

// Función para eliminar vehículo
// Función para eliminar vehículo - VERSIÓN CORREGIDA
function confirmarEliminarVehiculo(vehiculoId, descripcion) {
    Swal.fire({
        title: '¿Eliminar vehículo?',
        text: `Se eliminará el vehículo: ${descripcion}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DC2626',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-trash mr-2"></i>Sí, eliminar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('vehiculo_id', vehiculoId);
            formData.append('csrf_token', '<?= csrf_token() ?>');
            
            fetch('<?= url('huespedes/eliminar-vehiculo') ?>', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'  // ← ESTE HEADER ES CRUCIAL
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Eliminado',
                        text: data.message,
                        confirmButtonColor: '#5D3A1A'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message,
                        confirmButtonColor: '#5D3A1A'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo eliminar el vehículo. Intente nuevamente.',
                    confirmButtonColor: '#5D3A1A'
                });
            });
        }
    });
}
</script>

<!-- Historial de Reservaciones -->
<div class="bg-white rounded-lg shadow-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
        <i class="fas fa-history mr-2 text-hotel-brown"></i>
        Historial de Reservaciones
    </h2>
    
    <?php if (!empty($reservaciones)): ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-sm text-gray-600 border-b">
                    <th class="pb-3">Habitaciones</th>
                    <th class="pb-3">Entrada</th>
                    <th class="pb-3">Salida</th>
                    <th class="pb-3">Total</th>
                    <th class="pb-3">Estado</th>
                    <th class="pb-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservaciones as $reservacion): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="py-3">
                        <div>
                            <p class="font-medium">
                                <?= htmlspecialchars($reservacion['habitaciones_numeros'] ?? 'Sin habitaciones') ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?= intval($reservacion['total_habitaciones'] ?? 0) ?> <?= (intval($reservacion['total_habitaciones'] ?? 0) == 1) ? 'habitación' : 'habitaciones' ?>
                                <?php if (isset($reservacion['habitaciones_cortesia']) && $reservacion['habitaciones_cortesia'] > 0): ?>
                                    <span class="text-green-600 font-semibold">
                                        (<?= $reservacion['habitaciones_cortesia'] ?> gratis)
                                    </span>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($reservacion['tipos_habitacion'])): ?>
                            <p class="text-xs text-gray-400 capitalize">
                                <?= htmlspecialchars($reservacion['tipos_habitacion']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="py-3 text-sm"><?= format_date($reservacion['fecha_entrada'] ?? '') ?></td>
                    <td class="py-3 text-sm"><?= format_date($reservacion['fecha_salida'] ?? '') ?></td>
                    <td class="py-3">
                        <p class="font-semibold"><?= format_money($reservacion['precio_total'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500"><?= ucfirst($reservacion['metodo_pago'] ?? 'No especificado') ?></p>
                    </td>
                    <td class="py-3">
                        <?php
                        $estado = $reservacion['estado'] ?? 'desconocido';
                        $estadoReserva = [
                            'confirmada' => ['label' => 'Confirmada', 'color' => 'blue'],
                            'checked_in' => ['label' => 'Check-in', 'color' => 'green'],
                            'checked_out' => ['label' => 'Check-out', 'color' => 'gray'],
                            'cancelada' => ['label' => 'Cancelada', 'color' => 'red']
                        ][$estado] ?? ['label' => 'Desconocido', 'color' => 'gray'];
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs rounded-full bg-<?= $estadoReserva['color'] ?>-100 text-<?= $estadoReserva['color'] ?>-800">
                            <?= $estadoReserva['label'] ?>
                        </span>
                    </td>
                    <td class="py-3">
                        <a href="<?= url('reservaciones/ver/' . $reservacion['id']) ?>" 
                           class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white px-3 py-1 rounded-lg text-xs hover:shadow-md transition-all inline-flex items-center">
                            <i class="fas fa-eye mr-1"></i>Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-8">
        <i class="fas fa-calendar-times text-6xl text-gray-300 mb-4"></i>
        <p class="text-xl text-gray-600">Sin reservaciones registradas</p>
        <p class="text-gray-500 mt-2">Este huésped no ha realizado ninguna reservación</p>
        <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" 
           class="inline-flex items-center mt-4 bg-gradient-to-r from-hotel-gold to-yellow-600 text-white px-4 py-3 rounded-xl hover:shadow-lg transform hover:scale-105 transition-all">
            <i class="fas fa-calendar-plus mr-2"></i>Crear primera reservación
        </a>
    </div>
    <?php endif; ?>
</div>
        <!-- Columna lateral - Estadísticas -->
        <div class="space-y-6">
            <!-- Tarjeta de Estadísticas -->
            <div class="bg-gradient-to-br from-hotel-brown to-hotel-brown-dark rounded-lg shadow-lg p-6 text-white">
                <h3 class="text-lg font-semibold mb-4">Estadísticas del Huésped</h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-3xl font-bold"><?= $total_reservaciones ?></p>
                        <p class="text-sm opacity-80">Total de visitas</p>
                    </div>
                    
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-2xl font-bold"><?= format_money($total_gastado) ?></p>
                        <p class="text-sm opacity-80">Total gastado</p>
                    </div>
                    
                    <?php if ($total_reservaciones > 0): ?>
                    <div class="pt-4 border-t border-white/20">
                        <p class="text-xl font-bold"><?= format_money($total_gastado / $total_reservaciones) ?></p>
                        <p class="text-sm opacity-80">Gasto promedio por visita</p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_reservaciones >= 3): ?>
                <div class="mt-4 bg-yellow-400/20 rounded-lg p-3">
                    <p class="text-sm flex items-center">
                        <i class="fas fa-star mr-2"></i>
                        Cliente frecuente
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Acciones Rápidas</h3>
                
                <div class="space-y-3">
                    <a href="<?= url('reservaciones/crear?huesped_id=' . $huesped['id']) ?>" 
                       class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Nueva Reservación
                    </a>
                    
                    <a href="<?= url('huespedes/' . $huesped['id'] . '/edit') ?>" 
                       class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Información
                    </a>
                    
                    <?php if ($huesped['telefono']): ?>
                    <a href="tel:<?= $huesped['telefono'] ?>" 
                       class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-phone mr-2"></i>
                        Llamar
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($huesped['email']): ?>
                    <a href="mailto:<?= $huesped['email'] ?>" 
                       class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-envelope mr-2"></i>
                        Enviar Email
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Información del Sistema -->
            <div class="bg-gray-50 rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-3">Información del Sistema</h3>
                <ul class="space-y-2 text-sm">
                    <li class="flex justify-between">
                        <span class="text-gray-600">ID:</span>
                        <span class="font-mono">#<?= $huesped['id'] ?></span>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-gray-600">Registrado:</span>
                        <span><?= format_datetime($huesped['created_at']) ?></span>
                    </li>
                    <li class="flex justify-between">
                        <span class="text-gray-600">Actualizado:</span>
                        <span><?= format_datetime($huesped['updated_at']) ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>