<!-- app/views/habitaciones/gestionar-imagenes.php -->
<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center text-sm text-gray-600 mb-2">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown">Habitaciones</a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="hover:text-hotel-brown">
                Habitación <?= htmlspecialchars($habitacion['numero']) ?>
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span>Gestionar Imágenes</span>
        </div>
        
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold text-gray-800 font-playfair">
                Gestionar Imágenes - Habitación <?= htmlspecialchars($habitacion['numero']) ?>
            </h1>
            
            <?php $back_arrow_href = back_url('habitaciones/' . $habitacion['id']); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('habitaciones/' . $habitacion['id']) ?>"
               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition duration-200 ms-back-legacy">
                <i class="fas fa-arrow-left mr-2"></i>Volver
            </a>
        </div>
    </div>
    
    <!-- Información de la habitación -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm text-gray-600">Tipo</p>
                <p class="font-semibold"><?= ucfirst($habitacion['tipo']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Piso</p>
                <p class="font-semibold"><?= Habitacion::getNombrePiso($habitacion['piso']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Estado</p>
                <p class="font-semibold"><?= ucfirst($habitacion['estado']) ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-600">Total de imágenes</p>
                <p class="font-semibold"><?= count($imagenes) ?> / 10</p>
            </div>
        </div>
    </div>
    
    <!-- Agregar nuevas imágenes -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-plus-circle mr-2 text-green-600"></i>
            Agregar Nuevas Imágenes
        </h2>
        
        <?php if (count($imagenes) >= 10): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-yellow-800">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Has alcanzado el límite máximo de 10 imágenes por habitación.
            </p>
        </div>
        <?php else: ?>
        <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/agregar-imagenes') ?>" 
              enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            
            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-purple-400 transition-all">
                <input type="file" 
                       name="fotos[]" 
                       accept="image/*"
                       id="nuevas-fotos"
                       multiple
                       class="hidden"
                       <?= count($imagenes) >= 10 ? 'disabled' : '' ?>>
                <label for="nuevas-fotos" class="cursor-pointer">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                    <p class="text-gray-600 font-medium">Click para seleccionar imágenes</p>
                    <p class="text-sm text-gray-500">
                        Puedes agregar hasta <?= 10 - count($imagenes) ?> imagen<?= (10 - count($imagenes)) > 1 ? 'es' : '' ?> más
                    </p>
                </label>
            </div>
            
            <div id="preview-nuevas" class="hidden grid grid-cols-2 md:grid-cols-4 gap-4"></div>
            
            <button type="submit" 
                    id="btn-subir"
                    class="hidden bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-upload mr-2"></i>
                Subir Imágenes
            </button>
        </form>
        <?php endif; ?>
    </div>
    
    <!-- Galería de imágenes existentes -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            <i class="fas fa-images mr-2 text-purple-600"></i>
            Imágenes Actuales
        </h2>
        
        <?php if (empty($imagenes)): ?>
        <div class="text-center py-8">
            <i class="fas fa-image text-6xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No hay imágenes cargadas para esta habitación</p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="galeria-imagenes">
            <?php foreach ($imagenes as $index => $imagen): ?>
            <div class="relative group" data-imagen-id="<?= $imagen['id'] ?>">
                <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-md">
                    <img src="<?= image_url($imagen['url']) ?>" 
                         alt="Imagen <?= $index + 1 ?>"
                         class="w-full h-48 object-cover">
                    
                    <!-- Overlay con opciones -->
                    <div class="absolute inset-0 bg-black bg-opacity-60 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                        <div class="space-y-2">
                            <?php if (!$imagen['es_principal']): ?>
                            <button onclick="establecerPrincipal(<?= $imagen['id'] ?>)" 
                                    class="w-full bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition">
                                <i class="fas fa-star mr-2"></i>Establecer como principal
                            </button>
                            <?php endif; ?>
                            
                            <button onclick="eliminarImagen(<?= $imagen['id'] ?>)" 
                                    class="w-full bg-red-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-600 transition">
                                <i class="fas fa-trash mr-2"></i>Eliminar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Badge si es principal -->
                    <?php if ($imagen['es_principal']): ?>
                    <div class="absolute top-2 left-2 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-medium shadow-lg">
                        <i class="fas fa-star mr-1"></i>Principal
                    </div>
                    <?php endif; ?>
                    
                    <!-- Número de orden -->
                    <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-xs">
                        #<?= $index + 1 ?>
                    </div>
                </div>
                
                <!-- Información de la imagen -->
                <div class="mt-2 text-sm text-gray-600">
                    <p class="truncate" title="<?= htmlspecialchars($imagen['url']) ?>">
                        <?= basename($imagen['url']) ?>
                    </p>
                    <?php if (!empty($imagen['descripcion'])): ?>
                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($imagen['descripcion']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Scripts -->
<script>
// Preview de nuevas imágenes
document.getElementById('nuevas-fotos')?.addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    const preview = document.getElementById('preview-nuevas');
    const btnSubir = document.getElementById('btn-subir');
    const maxRestante = <?= 10 - count($imagenes) ?>;
    
    if (files.length > maxRestante) {
        Swal.fire({
            icon: 'warning',
            title: 'Demasiadas imágenes',
            text: `Solo puedes agregar ${maxRestante} imagen${maxRestante > 1 ? 'es' : ''} más`,
            confirmButtonColor: '#9333ea'
        });
        e.target.value = '';
        return;
    }
    
    preview.innerHTML = '';
    preview.classList.remove('hidden');
    btnSubir.classList.remove('hidden');
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'relative';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-32 object-cover rounded-lg">
                <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
            `;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

// Establecer imagen principal
function establecerPrincipal(imagenId) {
    Swal.fire({
        title: '¿Establecer como principal?',
        text: 'Esta imagen será la que se muestre primero',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, establecer',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('habitaciones/imagen/principal') ?>';
            
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = '<?= csrf_token() ?>';
            form.appendChild(csrfField);
            
            const imagenField = document.createElement('input');
            imagenField.type = 'hidden';
            imagenField.name = 'imagen_id';
            imagenField.value = imagenId;
            form.appendChild(imagenField);
            
            const habitacionField = document.createElement('input');
            habitacionField.type = 'hidden';
            habitacionField.name = 'habitacion_id';
            habitacionField.value = '<?= $habitacion['id'] ?>';
            form.appendChild(habitacionField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Eliminar imagen
function eliminarImagen(imagenId) {
    Swal.fire({
        title: '¿Eliminar imagen?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?= url('habitaciones/imagen/eliminar') ?>';
            
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = '<?= csrf_token() ?>';
            form.appendChild(csrfField);
            
            const imagenField = document.createElement('input');
            imagenField.type = 'hidden';
            imagenField.name = 'imagen_id';
            imagenField.value = imagenId;
            form.appendChild(imagenField);
            
            const habitacionField = document.createElement('input');
            habitacionField.type = 'hidden';
            habitacionField.name = 'habitacion_id';
            habitacionField.value = '<?= $habitacion['id'] ?>';
            form.appendChild(habitacionField);
            
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
