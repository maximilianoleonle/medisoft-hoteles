<!-- app/views/habitaciones/crear.php -->
<style id="create-room-redesign">
    .create-room-page {
        --room-ink: #2d302f;
        --room-muted: #68716d;
        --room-line: rgba(55, 64, 60, 0.12);
        --room-paper: rgba(255, 255, 255, 0.92);
        --room-soft: #f7f4ed;
        --room-gold: color-mix(in srgb, var(--brand-accent, #b58b4a) 52%, #b58b4a);
        --room-brown: color-mix(in srgb, var(--brand-primary, #765438) 24%, #6b5138);
        --room-sage: #7f987d;
        --room-sky: #7c9bb3;
        --room-clay: #c47f67;
        min-height: 100dvh;
        padding: clamp(1rem, 2.2vw, 2rem);
        color: var(--room-ink);
        background:
            radial-gradient(circle at 6% 8%, rgba(181, 139, 74, 0.18), transparent 30rem),
            radial-gradient(circle at 92% 12%, rgba(124, 155, 179, 0.2), transparent 28rem),
            radial-gradient(circle at 70% 84%, rgba(127, 152, 125, 0.18), transparent 32rem),
            linear-gradient(135deg, #fbf7ef 0%, #f5f2ea 42%, #eef3f0 100%);
    }

    .create-room-page::before {
        content: "";
        position: fixed;
        inset: 0;
        pointer-events: none;
        background-image:
            linear-gradient(rgba(45, 48, 47, 0.035) 1px, transparent 1px),
            linear-gradient(90deg, rgba(45, 48, 47, 0.03) 1px, transparent 1px);
        background-size: 34px 34px;
        mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0.62), transparent 75%);
    }

    .create-room-page > * {
        position: relative;
        z-index: 1;
    }

    .create-room-breadcrumb a {
        width: fit-content;
        padding: 0.65rem 0.9rem;
        border: 1px solid rgba(118, 84, 56, 0.14);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.62);
        color: var(--room-brown);
        box-shadow: 0 12px 28px rgba(59, 46, 31, 0.08);
        backdrop-filter: blur(12px);
    }

    .create-room-hero-card,
    .create-room-card,
    .create-room-active-card {
        border: 1px solid rgba(70, 78, 72, 0.12);
        background: var(--room-paper) !important;
        box-shadow: 0 22px 55px rgba(57, 49, 37, 0.12) !important;
        backdrop-filter: blur(14px);
    }

    .create-room-hero-card {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1.5rem;
        border-top: 0 !important;
    }

    .create-room-hero-card::before {
        content: "";
        position: absolute;
        inset: 0;
        border-left: 7px solid var(--room-gold);
        background:
            linear-gradient(110deg, rgba(255, 255, 255, 0.86), rgba(255, 255, 255, 0.58)),
            radial-gradient(circle at 88% 20%, rgba(196, 127, 103, 0.18), transparent 14rem);
        pointer-events: none;
    }

    .create-room-hero-card::after {
        content: "Nuevo registro";
        position: relative;
        flex: 0 0 auto;
        padding: 0.55rem 0.8rem;
        border: 1px solid rgba(127, 152, 125, 0.3);
        border-radius: 999px;
        background: rgba(127, 152, 125, 0.13);
        color: #496047;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .create-room-hero-card h1,
    .create-room-hero-card p {
        position: relative;
        z-index: 1;
    }

    .create-room-hero-card h1 {
        max-width: 780px;
        color: var(--room-ink) !important;
        line-height: 0.98;
        text-wrap: balance;
    }

    .create-room-hero-card p {
        max-width: 58ch;
        color: var(--room-muted) !important;
    }

    .create-room-form-grid {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) minmax(305px, 360px);
        align-items: start;
        gap: clamp(1rem, 2.3vw, 2rem);
    }

    .create-room-main,
    .create-room-side {
        grid-column: auto !important;
    }

    .create-room-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.25rem !important;
    }

    .create-room-card::before,
    .create-room-active-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 6px;
        background: var(--section-accent, var(--room-gold));
    }

    .create-section-basic { --section-accent: var(--room-gold); }
    .create-section-features { --section-accent: var(--room-sky); }
    .create-section-photos { --section-accent: var(--room-clay); }
    .create-room-active-card { --section-accent: var(--room-sage); }

    .create-room-card > div:first-child {
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(247, 244, 237, 0.72)) !important;
        border-bottom: 1px solid var(--room-line);
        padding-left: 1.8rem !important;
    }

    .create-room-card > div:first-child h2 {
        color: var(--room-ink) !important;
    }

    .create-room-card > div:first-child h2 i {
        display: inline-grid;
        width: 2.35rem;
        height: 2.35rem;
        margin-right: 0.75rem !important;
        place-items: center;
        border-radius: 0.9rem;
        background: color-mix(in srgb, var(--section-accent) 18%, white);
        color: var(--section-accent);
    }

    .create-room-page label {
        color: #3f4743;
    }

    .create-room-page input:not([type="checkbox"]):not([type="file"]):not([type="hidden"]),
    .create-room-page select,
    .create-room-page textarea {
        border: 1px solid rgba(71, 82, 76, 0.16) !important;
        background: rgba(255, 255, 255, 0.82) !important;
        color: var(--room-ink);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .create-room-page input:not([type="checkbox"]):not([type="file"]):not([type="hidden"]):focus,
    .create-room-page select:focus,
    .create-room-page textarea:focus {
        border-color: color-mix(in srgb, var(--room-brown) 62%, white) !important;
        box-shadow: 0 0 0 4px rgba(181, 139, 74, 0.16) !important;
    }

    .create-room-page input[type="checkbox"] {
        accent-color: var(--room-sage);
    }

    .create-section-features .grid label {
        border: 1px solid rgba(71, 82, 76, 0.13) !important;
        background: rgba(249, 248, 244, 0.78);
        box-shadow: 0 10px 22px rgba(57, 49, 37, 0.06);
    }

    .create-section-features .grid label:hover {
        border-color: rgba(124, 155, 179, 0.45) !important;
        background: rgba(240, 246, 247, 0.95);
        transform: translateY(-1px);
    }

    .create-section-photos #drop-zone {
        border-color: rgba(196, 127, 103, 0.34) !important;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(250, 243, 238, 0.88));
    }

    .create-section-photos #drop-zone:hover,
    .create-section-photos #drop-zone.border-purple-400 {
        border-color: var(--room-clay) !important;
        background: rgba(252, 241, 236, 0.9) !important;
    }

    .create-section-photos .text-purple-800,
    .create-section-photos .text-purple-700,
    .create-section-photos .text-blue-600 {
        color: #8d5b4b !important;
    }

    .create-section-photos .bg-purple-50 {
        background-color: rgba(196, 127, 103, 0.12) !important;
    }

    .create-section-photos .bg-purple-500 {
        background-color: var(--room-clay) !important;
    }

    .create-room-active-card {
        position: sticky;
        top: 1rem;
        overflow: hidden;
        border-radius: 1.25rem !important;
        padding: 1.5rem 1.5rem 1.5rem 1.8rem !important;
    }

    .create-room-active-card span {
        color: var(--room-ink);
    }

    .create-room-active-card p {
        color: var(--room-muted);
    }

    .create-room-actions {
        padding: 0.75rem;
        border: 1px solid rgba(70, 78, 72, 0.1);
        border-radius: 1.15rem;
        background: rgba(255, 255, 255, 0.56);
        box-shadow: 0 16px 34px rgba(57, 49, 37, 0.09);
    }

    .create-room-submit {
        background: linear-gradient(135deg, #344139, var(--room-brown)) !important;
        box-shadow: 0 15px 30px rgba(73, 56, 39, 0.2);
    }

    .create-room-submit:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 18px 34px rgba(73, 56, 39, 0.26) !important;
    }

    .create-room-cancel {
        border: 1px solid rgba(70, 78, 72, 0.16) !important;
        background: rgba(255, 255, 255, 0.76) !important;
        color: #46504b !important;
    }

    .create-room-cancel:hover {
        background: rgba(247, 244, 237, 0.9) !important;
    }

    @media (max-width: 1120px) {
        .create-room-form-grid {
            grid-template-columns: 1fr;
        }

        .create-room-active-card {
            position: relative;
            top: auto;
        }
    }

    @media (max-width: 720px) {
        .create-room-page {
            padding: 1rem;
        }

        .create-room-hero-card {
            display: block;
            padding: 1.5rem !important;
        }

        .create-room-hero-card::after {
            display: inline-flex;
            margin-top: 1rem;
        }

        .create-room-hero-card h1 {
            font-size: clamp(2rem, 10vw, 2.6rem) !important;
        }

        .create-room-card > div:first-child {
            padding: 1.25rem 1.25rem 1.25rem 1.55rem !important;
        }
    }
</style>

<div class="create-room-page min-h-screen bg-gradient-to-br from-hotel-cream to-white p-6">
    <div class="max-w-7xl mx-auto mb-8">
        <div class="create-room-breadcrumb flex items-center text-sm text-gray-600 mb-4">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Volver a Habitaciones
            </a>
        </div>

        <div class="create-room-hero-card bg-white rounded-2xl shadow-xl p-8 border-t-4 border-hotel-gold">
            <h1 class="text-4xl font-bold text-hotel-brown font-playfair mb-2">
                Registrar Nueva Habitación
            </h1>
            <p class="text-gray-600">Complete la información para agregar una nueva habitación</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto">
        <form method="POST" action="<?= url('habitaciones/store') ?>" enctype="multipart/form-data" id="form-habitacion">
            <?= csrf_field() ?>

            <div class="create-room-form-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna principal -->
                <div class="create-room-main lg:col-span-2 space-y-6">
                    <!-- Información Básica -->
                    <div class="create-room-card create-section-basic bg-white rounded-2xl shadow-lg overflow-hidden">
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
                    <div class="create-room-card create-section-features bg-white rounded-2xl shadow-lg overflow-hidden">
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
                                    <?php
                                    $amenidadesHabitacion = is_array($amenidades ?? null) && !empty($amenidades)
                                        ? $amenidades
                                        : [
                                            'pantalla' => 'Pantalla',
                                            'balcon' => 'Balcon',
                                            'jacuzzi' => 'Jacuzzi',
                                            'amplia' => 'Mas amplia',
                                        ];
                                    $oldEspeciales = $_SESSION['old_input']['caracteristicas_especiales'] ?? [];
                                    $oldEspeciales = is_array($oldEspeciales) ? $oldEspeciales : [];
                                    ?>
                                    <?php foreach ($amenidadesHabitacion as $amenidadKey => $amenidadLabel): ?>
                                        <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all">
                                            <input type="checkbox"
                                                   name="caracteristicas_especiales[]"
                                                   value="<?= htmlspecialchars((string) $amenidadKey, ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= in_array((string) $amenidadKey, $oldEspeciales, true) ? 'checked' : '' ?>
                                                   class="mr-3">
                                            <span class="text-sm font-medium"><?= htmlspecialchars((string) $amenidadLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        </label>
                                    <?php endforeach; ?>
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
<div class="create-room-card create-section-photos bg-white rounded-2xl shadow-lg overflow-hidden">
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
                </div>

                <!-- Columna lateral -->
                <div class="create-room-side space-y-6">
                    <!-- Estado activo -->
                    <div class="create-room-active-card bg-green-50 rounded-2xl p-6">
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
                    <div class="create-room-actions space-y-3">
                        <button type="submit" class="create-room-submit w-full bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white font-semibold py-4 px-6 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-save mr-3"></i>
                            Crear Habitación
                        </button>

                        <a href="<?= url('habitaciones') ?>" class="create-room-cancel w-full bg-white border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-xl hover:bg-gray-50 transition-all duration-200 flex items-center justify-center">
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
