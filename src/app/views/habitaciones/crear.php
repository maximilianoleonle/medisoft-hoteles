<!-- app/views/habitaciones/crear.php -->
<div class="min-h-screen bg-gradient-to-br from-hotel-cream to-white p-6">
    <div class="max-w-7xl mx-auto mb-8">
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver a Habitaciones
            </a>
        </div>
        
        <div class="bg-white rounded-2xl shadow-xl p-8 border-t-4 border-hotel-gold">
            <h1 class="text-4xl font-bold text-hotel-brown font-playfair mb-2">
                Registrar Nueva Habitación
            </h1>
            <p class="text-gray-600">Complete la información para agregar una nueva habitación</p>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto">
        <form method="POST" action="<?= url('habitaciones/store') ?>" enctype="multipart/form-data" id="form-habitacion">
            <?= csrf_field() ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Información Básica -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-info-circle mr-3"></i>
                                Información Básica
                            </h2>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Número de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           name="numero" 
                                           value="<?= old('numero') ?>"
                                           required
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tipo de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <select name="tipo" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                        <option value="">Seleccione el tipo</option>
                                        <?php foreach ($tipos as $key => $tipo): ?>
                                            <option value="<?= $key ?>" <?= old('tipo') == $key ? 'selected' : '' ?>>
                                                <?= $tipo ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Piso <span class="text-red-500">*</span>
                                    </label>
                                    <select name="piso" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                        <option value="">Seleccione el piso</option>
                                        <?php foreach ($pisos as $key => $piso): ?>
                                            <option value="<?= $key ?>" <?= old('piso') == $key ? 'selected' : '' ?>>
                                                <?= $piso ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Precio por Noche <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3 text-xl font-bold text-hotel-gold">$</span>
                                        <input type="number" 
                                               name="precio_base" 
                                               value="<?= old('precio_base', '550.00') ?>"
                                               min="0"
                                               step="0.01"
                                               required
                                               class="w-full pl-10 pr-4 py-3 text-xl font-semibold border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                        <span class="absolute right-4 top-4 text-sm text-gray-500">MXN</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Características -->
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-list-check mr-3"></i>
                                Características y Amenidades
                            </h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="mb-6">
                                <p class="text-sm font-medium text-gray-700 mb-3">Características especiales:</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="caracteristicas_especiales[]" value="pantalla" class="mr-3">
                                        <span class="text-sm font-medium">Pantalla</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="caracteristicas_especiales[]" value="balcon" class="mr-3">
                                        <span class="text-sm font-medium">Balcón</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="caracteristicas_especiales[]" value="jacuzzi" class="mr-3">
                                        <span class="text-sm font-medium">Jacuzzi</span>
                                    </label>
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all">
                                        <input type="checkbox" name="caracteristicas_especiales[]" value="amplia" class="mr-3">
                                        <span class="text-sm font-medium">Más Amplia</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción adicional (opcional)
                                </label>
                                <textarea name="caracteristicas" 
                                          rows="3"
                                          placeholder="Si desea personalizar la descripción, escriba aquí..."
                                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all"><?= old('caracteristicas') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                  <!-- Card de Imágenes MÚLTIPLES - Reemplazar la sección de imagen en crear.php -->
<div class="bg-white rounded-2xl shadow-lg overflow-hidden">
    <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6">
        <h2 class="text-xl font-semibold text-white flex items-center">
            <i class="fas fa-images mr-3"></i>
            Fotografías de la Habitación
        </h2>
    </div>
    
    <div class="p-6">
        <!-- Zona de carga múltiple -->
        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center hover:border-purple-400 transition-all" id="drop-zone">
            <input type="file" 
                   name="fotos[]" 
                   accept="image/*"
                   id="fotos-input"
                   multiple
                   class="hidden">
            <label for="fotos-input" class="cursor-pointer">
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 font-medium mb-2">Click para subir imágenes</p>
                <p class="text-sm text-gray-500">JPG, PNG, GIF o WebP - Máximo 5MB por imagen</p>
                <p class="text-xs text-gray-500 mt-1">Puedes seleccionar múltiples imágenes</p>
                <p class="text-xs text-blue-600 mt-2">O arrastra y suelta las imágenes aquí</p>
            </label>
        </div>
        
        <!-- Vista previa de múltiples imágenes -->
        <div id="preview-container" class="mt-6 hidden">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Imágenes seleccionadas:</h4>
            <div id="preview-grid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <!-- Las previsualizaciones se agregarán aquí dinámicamente -->
            </div>
        </div>
        
        <!-- Información sobre las imágenes -->
        <div class="mt-4 bg-purple-50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-purple-800 mb-2">
                <i class="fas fa-info-circle mr-1"></i>
                Información sobre las imágenes
            </h4>
            <ul class="text-xs text-purple-700 space-y-1">
                <li>• La primera imagen será la principal</li>
                <li>• Puedes subir hasta 10 imágenes por habitación</li>
                <li>• Las imágenes se pueden reordenar después</li>
                <li>• Tamaño recomendado: 1920x1080 píxeles o mayor</li>
            </ul>
        </div>
    </div>
</div>

<!-- Script para manejo de múltiples imágenes -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fotosInput = document.getElementById('fotos-input');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const dropZone = document.getElementById('drop-zone');
    
    let selectedFiles = [];
    const maxFiles = 10;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Manejar selección de archivos
    fotosInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });
    
    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });
    
    function highlight(e) {
        dropZone.classList.add('border-purple-400', 'bg-purple-50');
    }
    
    function unhighlight(e) {
        dropZone.classList.remove('border-purple-400', 'bg-purple-50');
    }
    
    dropZone.addEventListener('drop', handleDrop, false);
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }
    
    function handleFiles(files) {
        const newFiles = Array.from(files);
        
        // Validar cantidad total
        if (selectedFiles.length + newFiles.length > maxFiles) {
            Swal.fire({
                icon: 'warning',
                title: 'Demasiadas imágenes',
                text: `Solo puedes subir hasta ${maxFiles} imágenes`,
                confirmButtonColor: '#9333ea'
            });
            return;
        }
        
        // Validar cada archivo
        const validFiles = [];
        for (let file of newFiles) {
            if (!allowedTypes.includes(file.type)) {
                showError(`${file.name} no es una imagen válida`);
                continue;
            }
            
            if (file.size > maxSize) {
                showError(`${file.name} excede el tamaño máximo de 5MB`);
                continue;
            }
            
            validFiles.push(file);
        }
        
        if (validFiles.length > 0) {
            selectedFiles = selectedFiles.concat(validFiles);
            updatePreview();
            updateFileInput();
        }
    }
    
    function updatePreview() {
        previewGrid.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            previewContainer.classList.add('hidden');
            return;
        }
        
        previewContainer.classList.remove('hidden');
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = createPreviewItem(e.target.result, file, index);
                previewGrid.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }
    
    function createPreviewItem(src, file, index) {
        const div = document.createElement('div');
        div.className = 'relative group';
        div.innerHTML = `
            <div class="aspect-w-16 aspect-h-9 rounded-lg overflow-hidden shadow-md">
                <img src="${src}" alt="${file.name}" class="w-full h-32 object-cover">
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                    <button type="button" onclick="removeImage(${index})" class="bg-red-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-red-600">
                        <i class="fas fa-trash mr-1"></i>Eliminar
                    </button>
                </div>
                ${index === 0 ? '<div class="absolute top-2 left-2 bg-purple-500 text-white px-2 py-1 rounded text-xs font-medium">Principal</div>' : ''}
            </div>
            <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
            <p class="text-xs text-gray-500">${formatFileSize(file.size)}</p>
        `;
        return div;
    }
    
    window.removeImage = function(index) {
        selectedFiles.splice(index, 1);
        updatePreview();
        updateFileInput();
    };
    
    function updateFileInput() {
        // Crear un nuevo DataTransfer para mantener los archivos seleccionados
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fotosInput.files = dataTransfer.files;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
                
                <!-- Columna lateral -->
                <div class="space-y-6">
                    <!-- Estado activo -->
                    <div class="bg-green-50 rounded-2xl p-6">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" 
                                   name="activa" 
                                   value="1"
                                   checked
                                   class="mt-1 mr-3 w-5 h-5 text-green-600 focus:ring-green-500 rounded">
                            <div>
                                <span class="font-semibold text-gray-800">Activar Habitación</span>
                                <p class="text-sm text-gray-600 mt-1">
                                    La habitación estará disponible para reservas
                                </p>
                            </div>
                        </label>
                    </div>
                    
                    <!-- Botones -->
                    <div class="space-y-3">
                        <button type="submit" class="w-full bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white font-semibold py-4 px-6 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-save mr-3"></i>
                            Crear Habitación
                        </button>
                        
                        <a href="<?= url('habitaciones') ?>" class="w-full bg-white border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-xl hover:bg-gray-50 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-times mr-3"></i>
                            Cancelar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= asset('js/habitacion-images.js') ?>"></script>