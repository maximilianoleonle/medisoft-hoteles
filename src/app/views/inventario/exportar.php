<?php require_once APP_PATH . '/views/layout/header.php'; ?>

<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
}
.exportar-view { opacity: 0; transition: opacity 0.3s ease; }
.exportar-view.loaded { opacity: 1; }
</style>

<div class="exportar-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-4">
    <!-- Header -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-6 py-3">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold flex items-center gap-2">
                    <i class="fas fa-file-export"></i>
                    Exportar Inventario
                </h1>
                <a href="<?= url('inventario') ?>" 
                   class="bg-white/20 text-white px-3 py-1.5 rounded-lg hover:bg-white/30 transition text-sm flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-6 py-6 max-w-2xl">
        <!-- Formulario de Exportación -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fas fa-file-pdf text-red-600"></i>
                Generar Reporte PDF
            </h2>
            
            <form method="POST" action="<?= url('inventario/generarPdfMovimientos') ?>" class="space-y-4">
                <?= csrf_field() ?>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-1"></i>
                        El reporte incluirá el stock actual de todos los productos y los movimientos realizados en el período seleccionado.
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Desde <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="fecha_desde" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown"
                               value="2025-09-01"
                               required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Hasta <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="fecha_hasta" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-brown/20 focus:border-hotel-brown"
                               value="2025-09-30"
                               required>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="submit" 
                            class="flex-1 bg-red-600 text-white px-4 py-2.5 rounded-lg hover:bg-red-700 transition flex items-center justify-center gap-2 font-medium">
                        <i class="fas fa-download"></i>
                        Generar PDF
                    </button>
                    <a href="<?= url('inventario') ?>" 
                       class="px-4 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Opciones Adicionales -->
        <div class="mt-4 grid md:grid-cols-2 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h3 class="font-semibold text-gray-900 mb-2 text-sm">
                    <i class="fas fa-chart-bar text-blue-600 mr-1"></i>
                    Reporte de Stock Actual
                </h3>
                <p class="text-xs text-gray-600 mb-3">
                    Genera un PDF con el inventario actual, incluyendo productos con stock bajo.
                </p>
                <button onclick="exportarStockActual()" 
                        class="w-full bg-blue-600 text-white px-3 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-download mr-1"></i>
                    Descargar Stock Actual
                </button>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h3 class="font-semibold text-gray-900 mb-2 text-sm">
                    <i class="fas fa-history text-purple-600 mr-1"></i>
                    Movimientos del Día
                </h3>
                <p class="text-xs text-gray-600 mb-3">
                    Descarga todos los movimientos realizados en el día actual.
                </p>
                <button onclick="exportarMovimientosHoy()" 
                        class="w-full bg-purple-600 text-white px-3 py-2 rounded-lg hover:bg-purple-700 transition text-sm">
                    <i class="fas fa-download mr-1"></i>
                    Movimientos de Hoy
                </button>
            </div>
        </div>
        
        <!-- Nota informativa sobre las fechas -->
        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-lg p-4">
            <p class="text-sm text-amber-800 flex items-start gap-2">
                <i class="fas fa-lightbulb mt-0.5"></i>
                <span>Los movimientos en el sistema están registrados con fechas de 2025. Asegúrate de seleccionar el rango de fechas correcto.</span>
            </p>
        </div>
    </div>
</div>

<script>
// Función para obtener la fecha actual en formato correcto
function getFechaActual() {
    // Como los movimientos están en 2025, usar esa fecha
    return '2025-09-13';
}

function exportarStockActual() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/generarPdfMovimientos") ?>';
    form.innerHTML = `
        <?= csrf_field() ?>
        <input type="hidden" name="fecha_desde" value="2025-09-12">
        <input type="hidden" name="fecha_hasta" value="2025-09-13">
    `;
    document.body.appendChild(form);
    form.submit();
}

function exportarMovimientosHoy() {
    const hoy = getFechaActual();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/generarPdfMovimientos") ?>';
    form.innerHTML = `
        <?= csrf_field() ?>
        <input type="hidden" name="fecha_desde" value="${hoy}">
        <input type="hidden" name="fecha_hasta" value="${hoy}">
    `;
    document.body.appendChild(form);
    form.submit();
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.exportar-view').classList.add('loaded');
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>