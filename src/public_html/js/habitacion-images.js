// JavaScript mejorado para manejo de imágenes en habitaciones
// public_html/js/habitacion-images.js

document.addEventListener('DOMContentLoaded', function() {
    // Configuración
    const config = {
        maxSize: 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        minWidth: 200,
        minHeight: 200
    };
    
    // Elementos del DOM
    const fotoInput = document.getElementById('foto-input');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const uploadLabel = document.querySelector('label[for="foto-input"]');
    const dropZone = document.getElementById('drop-zone');
    
    // Manejo de selección de archivo
    if (fotoInput) {
        fotoInput.addEventListener('change', handleFileSelect);
    }
    
    // Drag and Drop
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        dropZone.addEventListener('drop', handleDrop, false);
    }
    
    // Funciones principales
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            validateAndShowImage(file);
        } else {
            resetPreview();
        }
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            fotoInput.files = files;
            validateAndShowImage(files[0]);
        }
    }
    
    function validateAndShowImage(file) {
        // Validar tipo
        if (!config.allowedTypes.includes(file.type)) {
            showError('Solo se permiten imágenes JPG, PNG, GIF o WebP');
            resetFileInput();
            return;
        }
        
        // Validar tamaño
        if (file.size > config.maxSize) {
            showError('La imagen no puede exceder 5MB');
            resetFileInput();
            return;
        }
        
        // Crear preview y validar dimensiones
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                if (this.width < config.minWidth || this.height < config.minHeight) {
                    showError(`La imagen debe tener al menos ${config.minWidth}x${config.minHeight} píxeles`);
                    resetFileInput();
                    return;
                }
                
                // Mostrar preview
                showImagePreview(e.target.result, file);
            };
            img.onerror = function() {
                showError('El archivo no es una imagen válida');
                resetFileInput();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    function showImagePreview(src, file) {
        if (previewImage && previewContainer) {
            previewImage.src = src;
            previewContainer.classList.remove('hidden');
            
            // Actualizar label
            if (uploadLabel) {
                const sizeText = formatFileSize(file.size);
                const fileName = file.name.length > 30 ? file.name.substring(0, 30) + '...' : file.name;
                
                uploadLabel.innerHTML = `
                    <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                    <p class="text-green-600 font-medium mb-2">Imagen seleccionada</p>
                    <p class="text-sm text-gray-600">${fileName}</p>
                    <p class="text-xs text-gray-500">${sizeText}</p>
                    <p class="text-xs text-blue-600 mt-2">Click para cambiar imagen</p>
                `;
            }
        }
    }
    
    function resetPreview() {
        if (previewContainer) {
            previewContainer.classList.add('hidden');
        }
        if (previewImage) {
            previewImage.src = '';
        }
        if (uploadLabel) {
            uploadLabel.innerHTML = `
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 font-medium mb-2">Click para subir imagen</p>
                <p class="text-sm text-gray-500">JPG, PNG, GIF o WebP - Máximo 5MB</p>
                <p class="text-xs text-gray-500 mt-1">Mínimo 200x200 píxeles</p>
                <p class="text-xs text-blue-600 mt-2">O arrastra y suelta la imagen aquí</p>
            `;
        }
    }
    
    function resetFileInput() {
        if (fotoInput) {
            fotoInput.value = '';
        }
        resetPreview();
    }
    
    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error en la imagen',
                text: message,
                confirmButtonColor: '#dc2626'
            });
        } else {
            (window.msToast ? window.msToast('error', 'Error', message) : alert('Error: ' + message));
        }
    }
    
    // Funciones auxiliares
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        dropZone.classList.add('border-purple-400', 'bg-purple-50');
    }
    
    function unhighlight(e) {
        dropZone.classList.remove('border-purple-400', 'bg-purple-50');
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Validación del formulario
    const form = document.querySelector('#form-habitacion');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
            }
        });
    }
});

// Funciones para lightbox (vista ver.php)
function abrirLightbox(imageSrc) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    
    if (lightbox && lightboxImage) {
        lightboxImage.src = imageSrc;
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Cerrar lightbox con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarLightbox();
    }
});

// Cerrar lightbox al hacer clic fuera
document.addEventListener('click', function(e) {
    const lightbox = document.getElementById('lightbox');
    if (lightbox && e.target === lightbox) {
        cerrarLightbox();
    }
});// JavaScript mejorado para manejo de imágenes en habitaciones
// public_html/js/habitacion-images.js

document.addEventListener('DOMContentLoaded', function() {
    // Configuración
    const config = {
        maxSize: 5 * 1024 * 1024, // 5MB
        allowedTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        minWidth: 200,
        minHeight: 200
    };
    
    // Elementos del DOM
    const fotoInput = document.getElementById('foto-input');
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    const uploadLabel = document.querySelector('label[for="foto-input"]');
    const dropZone = document.getElementById('drop-zone');
    
    // Manejo de selección de archivo
    if (fotoInput) {
        fotoInput.addEventListener('change', handleFileSelect);
    }
    
    // Drag and Drop
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        dropZone.addEventListener('drop', handleDrop, false);
    }
    
    // Funciones principales
    function handleFileSelect(e) {
        const file = e.target.files[0];
        if (file) {
            validateAndShowImage(file);
        } else {
            resetPreview();
        }
    }
    
    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            fotoInput.files = files;
            validateAndShowImage(files[0]);
        }
    }
    
    function validateAndShowImage(file) {
        // Validar tipo
        if (!config.allowedTypes.includes(file.type)) {
            showError('Solo se permiten imágenes JPG, PNG, GIF o WebP');
            resetFileInput();
            return;
        }
        
        // Validar tamaño
        if (file.size > config.maxSize) {
            showError('La imagen no puede exceder 5MB');
            resetFileInput();
            return;
        }
        
        // Crear preview y validar dimensiones
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                if (this.width < config.minWidth || this.height < config.minHeight) {
                    showError(`La imagen debe tener al menos ${config.minWidth}x${config.minHeight} píxeles`);
                    resetFileInput();
                    return;
                }
                
                // Mostrar preview
                showImagePreview(e.target.result, file);
            };
            img.onerror = function() {
                showError('El archivo no es una imagen válida');
                resetFileInput();
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
    
    function showImagePreview(src, file) {
        if (previewImage && previewContainer) {
            previewImage.src = src;
            previewContainer.classList.remove('hidden');
            
            // Actualizar label
            if (uploadLabel) {
                const sizeText = formatFileSize(file.size);
                const fileName = file.name.length > 30 ? file.name.substring(0, 30) + '...' : file.name;
                
                uploadLabel.innerHTML = `
                    <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
                    <p class="text-green-600 font-medium mb-2">Imagen seleccionada</p>
                    <p class="text-sm text-gray-600">${fileName}</p>
                    <p class="text-xs text-gray-500">${sizeText}</p>
                    <p class="text-xs text-blue-600 mt-2">Click para cambiar imagen</p>
                `;
            }
        }
    }
    
    function resetPreview() {
        if (previewContainer) {
            previewContainer.classList.add('hidden');
        }
        if (previewImage) {
            previewImage.src = '';
        }
        if (uploadLabel) {
            uploadLabel.innerHTML = `
                <i class="fas fa-cloud-upload-alt text-5xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 font-medium mb-2">Click para subir imagen</p>
                <p class="text-sm text-gray-500">JPG, PNG, GIF o WebP - Máximo 5MB</p>
                <p class="text-xs text-gray-500 mt-1">Mínimo 200x200 píxeles</p>
                <p class="text-xs text-blue-600 mt-2">O arrastra y suelta la imagen aquí</p>
            `;
        }
    }
    
    function resetFileInput() {
        if (fotoInput) {
            fotoInput.value = '';
        }
        resetPreview();
    }
    
    function showError(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error en la imagen',
                text: message,
                confirmButtonColor: '#dc2626'
            });
        } else {
            (window.msToast ? window.msToast('error', 'Error', message) : alert('Error: ' + message));
        }
    }
    
    // Funciones auxiliares
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    function highlight(e) {
        dropZone.classList.add('border-purple-400', 'bg-purple-50');
    }
    
    function unhighlight(e) {
        dropZone.classList.remove('border-purple-400', 'bg-purple-50');
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Validación del formulario
    const form = document.querySelector('#form-habitacion');
    if (form) {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
            }
        });
    }
});

// Funciones para lightbox (vista ver.php)
function abrirLightbox(imageSrc) {
    const lightbox = document.getElementById('lightbox');
    const lightboxImage = document.getElementById('lightbox-image');
    
    if (lightbox && lightboxImage) {
        lightboxImage.src = imageSrc;
        lightbox.classList.remove('hidden');
        lightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }
}

function cerrarLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
        lightbox.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Cerrar lightbox con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarLightbox();
    }
});

// Cerrar lightbox al hacer clic fuera
document.addEventListener('click', function(e) {
    const lightbox = document.getElementById('lightbox');
    if (lightbox && e.target === lightbox) {
        cerrarLightbox();
    }
});