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
                        Conceptos de ingresos y gastos
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
<style id="cash-category-modal-layout">
    /*
     * El shell usa z-index > 1000 y custom.css expande cualquier max-w-* al 100%.
     * Este modal evita ambas reglas globales y se comporta como los overlays
     * actuales de Caja: centrado, por encima del shell y con scroll interno.
     */
    /*
     * SweetAlert2 vive en z-index 1060: contra los 13000 de este modal, CUALQUIER
     * Swal disparado con el modal abierto queda TAPADO y parece que la pantalla
     * se congeló (pasó dos veces: con el aviso de error y con el de éxito, que
     * dejaba el botón en "Guardando..." hasta cerrar el modal a mano). Se sube
     * por encima del modal de una vez para que no vuelva a esconderse ninguno.
     */
    .swal2-container {
        z-index: 13500 !important;
    }

    .cash-category-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 13000 !important;
        display: grid;
        place-items: center;
        padding:
            max(16px, env(safe-area-inset-top))
            max(16px, env(safe-area-inset-right))
            max(16px, env(safe-area-inset-bottom))
            max(16px, env(safe-area-inset-left));
        overflow-y: auto;
        background: rgba(12, 18, 30, .68);
        -webkit-backdrop-filter: blur(7px);
        backdrop-filter: blur(7px);
    }

    .cash-category-modal.hidden {
        display: none !important;
    }

    body.cash-category-modal-open,
    body.cash-category-modal-open .main-content {
        overflow: hidden !important;
    }

    .cash-category-modal__dialog {
        width: min(32rem, calc(100vw - 32px)) !important;
        max-width: 32rem !important;
        max-height: min(94dvh, 780px);
        margin: auto;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(255, 255, 255, .52);
        border-radius: 22px;
        background: #FFFFFF;
        box-shadow: 0 34px 90px -36px rgba(10, 15, 25, .78);
    }

    .cash-category-modal__head {
        flex: 0 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 18px 20px !important;
    }

    .cash-category-modal__close {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        display: inline-grid;
        place-items: center;
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 12px;
        color: #FFFFFF;
        background: rgba(255, 255, 255, .14);
        transition: background-color .16s ease, transform .16s ease;
    }

    .cash-category-modal__close:hover {
        background: rgba(255, 255, 255, .24);
    }

    .cash-category-modal__close:focus-visible {
        outline: 3px solid rgba(255, 255, 255, .72);
        outline-offset: 2px;
    }

    .cash-category-modal__form {
        flex: 1 1 auto;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .cash-category-modal__body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 16px 20px 12px;
        overflow-y: auto;
        overscroll-behavior: contain;
        scrollbar-gutter: stable;
    }

    .cash-category-modal__fields > * + * {
        margin-top: 12px !important;
    }

    .cash-category-modal__form label {
        margin-bottom: 6px !important;
    }

    .cash-category-modal__form input:not([type="hidden"]),
    .cash-category-modal__form select {
        min-height: 42px;
    }

    .cash-category-modal__form textarea {
        min-height: 62px;
    }

    .cash-category-modal__preview {
        padding: 10px 12px !important;
    }

    .cash-category-modal__preview-icon {
        width: 40px !important;
        height: 40px !important;
        flex: 0 0 40px;
    }

    /* El resumen global de validación se mantiene visible, pero más compacto. */
    .cash-category-modal__form > .ms-form-error-summary {
        flex: 0 0 auto;
        gap: 2px;
        margin: 12px 20px 0;
        padding: 10px 12px;
        box-shadow: none;
    }

    .cash-category-modal__form > .ms-form-error-summary strong {
        font-size: .84rem;
    }

    .cash-category-modal__form > .ms-form-error-summary span {
        margin-top: 2px;
        font-size: .78rem;
    }

    .cash-category-modal__actions {
        flex: 0 0 auto;
        margin-top: 0 !important;
        padding: 12px 20px max(14px, env(safe-area-inset-bottom));
        border-top: 1px solid #E5E7EB;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 -10px 24px -22px rgba(15, 23, 42, .7);
    }

    .cash-category-modal__actions button {
        min-height: 44px;
    }

    @media (max-width: 640px) {
        .cash-category-modal {
            place-items: end center;
            padding:
                max(10px, env(safe-area-inset-top))
                max(10px, env(safe-area-inset-right))
                max(10px, env(safe-area-inset-bottom))
                max(10px, env(safe-area-inset-left));
        }

        .cash-category-modal__dialog {
            width: 100% !important;
            max-width: none !important;
            max-height: calc(100dvh - 20px - env(safe-area-inset-top) - env(safe-area-inset-bottom));
            border-radius: 20px;
        }

        .cash-category-modal__head {
            padding: 14px 16px !important;
        }

        .cash-category-modal__body {
            padding: 14px 16px 10px;
        }

        .cash-category-modal__form > .ms-form-error-summary {
            margin: 10px 16px 0;
        }

        .cash-category-modal__actions {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            padding: 10px 16px max(12px, env(safe-area-inset-bottom));
        }
    }

    @media (max-width: 350px) {
        .cash-category-modal__split {
            grid-template-columns: minmax(0, 1fr) !important;
            gap: 12px !important;
        }
    }

    @media (prefers-reduced-motion: no-preference) {
        .cash-category-modal:not(.hidden) .cash-category-modal__dialog {
            animation: cashCategoryModalIn .2s cubic-bezier(.22, 1, .36, 1);
        }

        @keyframes cashCategoryModalIn {
            from { opacity: 0; transform: translateY(14px) scale(.985); }
            to { opacity: 1; transform: none; }
        }
    }
</style>

<div id="modalCategoria"
     class="cash-category-modal hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalTitulo"
     aria-hidden="true">
        <div class="cash-category-modal__dialog" tabindex="-1">
            <div class="cash-category-modal__head bg-gradient-to-r from-orange-500 to-orange-600 p-6">
                <h3 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-tag mr-3"></i>
                    <span id="modalTitulo">Nueva Categoría</span>
                </h3>
                <button type="button"
                        class="cash-category-modal__close"
                        onclick="cerrarModalCategoria()"
                        aria-label="Cerrar modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            
            <form id="formCategoria" class="cash-category-modal__form">
                <div class="cash-category-modal__body">
                    <input type="hidden" id="categoria_id" name="id">

                    <!-- Aviso de error DENTRO del modal. Un Swal aqui no sirve:
                         este modal va en z-index 13000 y SweetAlert2 en 1060, asi
                         que el aviso quedaba TAPADO y solo aparecia al cerrar el
                         modal a mano (queja real del 31-jul). Ademas conserva lo
                         que el usuario ya escribio. -->
                    <div id="categoriaFormError"
                         class="hidden mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                         role="alert" aria-live="assertive">
                        <i class="fas fa-circle-exclamation mt-0.5 text-red-500" aria-hidden="true"></i>
                        <span id="categoriaFormErrorTexto"></span>
                    </div>

                    <div class="cash-category-modal__fields space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="categoria_nombre" class="block text-sm font-medium text-gray-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nombre" id="categoria_nombre"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500" 
                               required>
                    </div>
                    
                    <!-- Tipo -->
                    <div>
                        <label for="categoria_tipo" class="block text-sm font-medium text-gray-700 mb-2">
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
                        <label for="categoria_descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                            Descripción
                        </label>
                        <textarea name="descripcion" id="categoria_descripcion" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>
                    
                    <!-- Icono y Color -->
                    <div class="cash-category-modal__split grid grid-cols-2 gap-4">
                        <div>
                            <label for="categoria_icono" class="block text-sm font-medium text-gray-700 mb-2">
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
                            <label for="categoria_color" class="block text-sm font-medium text-gray-700 mb-2">
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
                        <div class="cash-category-modal__preview bg-gray-50 rounded-lg p-4 flex items-center gap-3">
                            <div id="preview-icon" class="cash-category-modal__preview-icon w-12 h-12 flex items-center justify-center rounded-full">
                                <i id="preview-icon-class" class="fas fa-tag text-xl"></i>
                            </div>
                            <span id="preview-nombre" class="font-medium">Nueva Categoría</span>
                        </div>
                    </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="cash-category-modal__actions flex justify-end gap-3 mt-6">
                    <button type="button" onclick="cerrarModalCategoria()" 
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                    <button type="submit" id="btnGuardarCategoria"
                            class="px-4 py-2 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg hover:shadow-lg transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2"></i>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
</div>

<!-- Librería para drag and drop -->
<script src="<?= asset('vendor/sortablejs/Sortable.min.js') ?>"></script>

<script>
// Datos de categorías para edición
const categoriasData = <?= json_encode($categorias) ?>;
const modalCategoriaEl = document.getElementById('modalCategoria');
const modalCategoriaDialog = modalCategoriaEl?.querySelector('.cash-category-modal__dialog');
let modalCategoriaDisparador = null;

// El shell contiene capas propias (sidebar/header). En <body>, position:fixed
// queda anclado al viewport y no hereda restricciones del contenido principal.
if (modalCategoriaEl && modalCategoriaEl.parentElement !== document.body) {
    document.body.appendChild(modalCategoriaEl);
}

function abrirModalCategoria() {
    if (!modalCategoriaEl) return;

    modalCategoriaDisparador = document.activeElement instanceof HTMLElement
        ? document.activeElement
        : null;
    limpiarErrorCategoria();
    modalCategoriaEl.classList.remove('hidden');
    modalCategoriaEl.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden', 'cash-category-modal-open');

    window.requestAnimationFrame(() => {
        const nombre = document.getElementById('categoria_nombre');
        (nombre || modalCategoriaDialog)?.focus();
    });
}

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
    
    abrirModalCategoria();
}

// Cerrar modal
function cerrarModalCategoria() {
    if (!modalCategoriaEl || modalCategoriaEl.classList.contains('hidden')) return;

    modalCategoriaEl.classList.add('hidden');
    modalCategoriaEl.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden', 'cash-category-modal-open');
    limpiarErrorCategoria();

    if (modalCategoriaDisparador && document.contains(modalCategoriaDisparador)) {
        modalCategoriaDisparador.focus();
    }
    modalCategoriaDisparador = null;
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
    
    abrirModalCategoria();
}

// Aviso de error dentro del modal (ver el comentario del bloque en el HTML:
// un Swal queda tapado por este modal y el usuario no ve nada).
function mostrarErrorCategoria(mensaje) {
    const caja = document.getElementById('categoriaFormError');
    if (!caja) return;
    document.getElementById('categoriaFormErrorTexto').textContent = mensaje;
    caja.classList.remove('hidden');
    caja.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

function limpiarErrorCategoria() {
    const caja = document.getElementById('categoriaFormError');
    if (!caja) return;
    caja.classList.add('hidden');
    document.getElementById('categoriaFormErrorTexto').textContent = '';
}

// Guardar categoría
document.getElementById('formCategoria').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const id = formData.get('id');
    const url = id ? '<?= url('caja/categoria/actualizar') ?>' : '<?= url('caja/categoria/crear') ?>';

    // Un solo envío a la vez: sin esto el botón sigue vivo mientras el servidor
    // responde y un segundo clic manda otra alta (así quedaron dos peticiones
    // fallidas seguidas en el log de producción del 31-jul).
    const btn = document.getElementById('btnGuardarCategoria');
    if (btn && btn.disabled) return;
    const btnHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2" aria-hidden="true"></i>Guardando...';
    }
    limpiarErrorCategoria();

    const restaurarBoton = () => {
        if (!btn) return;
        btn.disabled = false;
        btn.innerHTML = btnHtml;
    };

    fetch(url, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    })
    // Respuesta no-JSON (500 en HTML, sesión vencida, 403): sin esto el .json()
    // truena y el usuario solo ve un genérico "Error al procesar la solicitud".
    .then(response => response.json().catch(() => ({
        success: false,
        message: response.status === 403
            ? 'No tienes permiso para hacer este cambio.'
            : 'El servidor respondió de forma inesperada (código ' + response.status + '). Vuelve a intentar.'
    })))
    .then(data => {
        if (data.success) {
            // Cerrar ANTES de avisar: guardó bien, el formulario ya no tiene
            // nada que hacer ahí y así el aviso queda a la vista aunque alguien
            // cambie los z-index de arriba.
            cerrarModalCategoria();
            Swal.fire('Éxito', data.message, 'success')
                .then(() => location.reload());
            return;
        }
        restaurarBoton();
        mostrarErrorCategoria(data.message || 'No se pudo guardar la categoría.');
    })
    .catch(error => {
        console.error('Error:', error);
        restaurarBoton();
        mostrarErrorCategoria('No se pudo conectar con el servidor. Revisa tu conexión e intenta de nuevo.');
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

// Clic fuera del panel: cerrar sin afectar el formulario ni sus controles.
modalCategoriaEl?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCategoria();
});

// Mantener el foco dentro del diálogo mientras está abierto.
modalCategoriaEl?.addEventListener('keydown', function(e) {
    if (e.key !== 'Tab' || this.classList.contains('hidden')) return;

    const focusables = Array.from(this.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    )).filter(element => element.offsetParent !== null);
    if (!focusables.length) {
        e.preventDefault();
        modalCategoriaDialog?.focus();
        return;
    }

    const primero = focusables[0];
    const ultimo = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === primero) {
        e.preventDefault();
        ultimo.focus();
    } else if (!e.shiftKey && document.activeElement === ultimo) {
        e.preventDefault();
        primero.focus();
    }
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modalCategoriaEl && !modalCategoriaEl.classList.contains('hidden')) {
        e.preventDefault();
        cerrarModalCategoria();
    }
});
</script>
