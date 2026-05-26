<?php
/**
 * Vista de Corte de Caja
 * Los Cedros
 * 
 * NOTA: Esta versión incluye correcciones para evitar errores de deprecación
 * al pasar valores null a htmlspecialchars() en PHP 8+
 */

// Función helper para manejar valores null de forma segura
if (!function_exists('safe_html')) {
    function safe_html($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<!-- Corte de Caja -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-red-500">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Corte de Caja
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <span class="flex items-center">
                            <i class="fas fa-calendar mr-2"></i>
                            <?= date('d/m/Y', strtotime($corte['fecha_apertura'] ?? 'now')) ?>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="flex items-center">
                            <i class="fas fa-clock mr-2"></i>
                            Apertura: <?= date('H:i', strtotime($corte['fecha_apertura'] ?? 'now')) ?>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="flex items-center">
                            <i class="fas fa-user mr-2"></i>
                            <?= safe_html($corte['usuario_apertura']) ?>
                        </span>
                    </div>
                </div>
                
                <a href="<?= url('caja') ?>" 
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Resumen General -->
    <div class="max-w-6xl mx-auto mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Monto Inicial -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700">Monto Inicial</h3>
                    <i class="fas fa-wallet text-blue-500"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800">
                    $<?= number_format($resumen['monto_inicial'] ?? 0, 2) ?>
                </p>
            </div>
            
            <!-- Total Ingresos -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700">Total Ingresos</h3>
                    <i class="fas fa-arrow-down text-green-500"></i>
                </div>
                <p class="text-2xl font-bold text-green-600">
                    +$<?= number_format($resumen['ingresos']['total'] ?? 0, 2) ?>
                </p>
            </div>
            
            <!-- Total Gastos -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold text-gray-700">Total Gastos</h3>
                    <i class="fas fa-arrow-up text-red-500"></i>
                </div>
                <p class="text-2xl font-bold text-red-600">
                    -$<?= number_format($resumen['gastos']['total'] ?? 0, 2) ?>
                </p>
            </div>
            
            <!-- Efectivo Esperado -->
            <div class="bg-gradient-to-br from-hotel-gold to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-semibold">Efectivo Esperado</h3>
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <p class="text-3xl font-bold" id="efectivoEsperado">
                    $<?= number_format($resumen['efectivo_en_caja'] ?? 0, 2) ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda - Desglose -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Desglose por Método de Pago -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="bg-gray-800 p-4">
                    <h2 class="text-lg font-semibold text-white">
                        <i class="fas fa-list-alt mr-2"></i>
                        Desglose por Método de Pago
                    </h2>
                </div>
                <div class="p-6">
                    <table class="w-full">
                        <thead>
                            <tr class="text-sm text-gray-600 border-b">
                                <th class="text-left py-2">Método</th>
                                <th class="text-center py-2">Ingresos</th>
                                <th class="text-center py-2">Gastos</th>
                                <th class="text-right py-2">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (['efectivo', 'tarjeta', 'transferencia'] as $metodo): ?>
                                <?php 
                                $ingresos = $resumen['ingresos'][$metodo]['total'] ?? 0;
                                $gastos = $resumen['gastos'][$metodo]['total'] ?? 0;
                                $balance = $ingresos - $gastos;
                                ?>
                                <tr class="border-b">
                                    <td class="py-3">
                                        <span class="flex items-center gap-2">
                                            <i class="fas fa-<?= $metodos_pago[$metodo]['icon'] ?? 'dollar-sign' ?> text-<?= $metodos_pago[$metodo]['color'] ?? 'gray' ?>-500"></i>
                                            <?= ucfirst($metodo) ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-green-600">
                                        +$<?= number_format($ingresos, 2) ?>
                                        <?php if (($resumen['ingresos'][$metodo]['cantidad'] ?? 0) > 0): ?>
                                            <span class="text-xs text-gray-500 block">
                                                (<?= $resumen['ingresos'][$metodo]['cantidad'] ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-red-600">
                                        -$<?= number_format($gastos, 2) ?>
                                        <?php if (($resumen['gastos'][$metodo]['cantidad'] ?? 0) > 0): ?>
                                            <span class="text-xs text-gray-500 block">
                                                (<?= $resumen['gastos'][$metodo]['cantidad'] ?>)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right font-semibold <?= $balance >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                        $<?= number_format($balance, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="font-bold text-lg">
                                <td class="pt-4">TOTALES</td>
                                <td class="pt-4 text-center text-green-600">
                                    +$<?= number_format($resumen['ingresos']['total'] ?? 0, 2) ?>
                                </td>
                                <td class="pt-4 text-center text-red-600">
                                    -$<?= number_format($resumen['gastos']['total'] ?? 0, 2) ?>
                                </td>
                                <td class="pt-4 text-right text-hotel-brown">
                                    $<?= number_format($resumen['balance_general'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Movimientos por Categoría -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Ingresos por Categoría -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-green-600 p-4">
                        <h3 class="text-white font-semibold">
                            <i class="fas fa-tags mr-2"></i>
                            Ingresos por Categoría
                        </h3>
                    </div>
                    <div class="p-4">
                        <?php if (empty($movimientos_categoria['ingresos'])): ?>
                            <p class="text-gray-500 text-center py-4">Sin ingresos</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                    <div class="flex items-center justify-between p-2 bg-green-50 rounded">
                                        <span class="text-sm flex items-center gap-2">
                                            <i class="<?= $cat['icono'] ?? 'fas fa-tag' ?>" style="color: <?= $cat['color'] ?? '#000' ?>"></i>
                                            <?= safe_html($cat['categoria']) ?>
                                            <span class="text-xs text-gray-500">(<?= $cat['cantidad'] ?? 0 ?>)</span>
                                        </span>
                                        <span class="font-semibold text-green-600">
                                            $<?= number_format($cat['total'] ?? 0, 2) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Gastos por Categoría -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="bg-red-600 p-4">
                        <h3 class="text-white font-semibold">
                            <i class="fas fa-tags mr-2"></i>
                            Gastos por Categoría
                        </h3>
                    </div>
                    <div class="p-4">
                        <?php if (empty($movimientos_categoria['gastos'])): ?>
                            <p class="text-gray-500 text-center py-4">Sin gastos</p>
                        <?php else: ?>
                            <div class="space-y-2">
                                <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                                    <div class="flex items-center justify-between p-2 bg-red-50 rounded">
                                        <span class="text-sm flex items-center gap-2">
                                            <i class="<?= $cat['icono'] ?? 'fas fa-tag' ?>" style="color: <?= $cat['color'] ?? '#000' ?>"></i>
                                            <?= safe_html($cat['categoria']) ?>
                                            <span class="text-xs text-gray-500">(<?= $cat['cantidad'] ?? 0 ?>)</span>
                                        </span>
                                        <span class="font-semibold text-red-600">
                                            $<?= number_format($cat['total'] ?? 0, 2) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna Derecha - Arqueo de Caja -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-calculator mr-2"></i>
                    Arqueo de Efectivo
                </h2>
            </div>
            
            <form method="POST" action="<?= url('caja/corte/cerrar') ?>" id="formCorte" class="p-6">
                <?= csrf_field() ?>
                <input type="hidden" name="corte_id" value="<?= $corte['id'] ?? '' ?>">
                
                <!-- Conteo de Billetes -->
                <div class="space-y-3 mb-6">
                    <h4 class="font-semibold text-gray-700 mb-3">Conteo de Billetes y Monedas</h4>
                    
                    <?php 
                    // Asegurar que $denominaciones existe y es un array
                    $denominaciones = $denominaciones ?? [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.50];
                    foreach ($denominaciones as $denominacion): 
                    ?>
                        <div class="flex items-center gap-3">
                            <label class="w-20 text-sm text-gray-600">
                                $<?= number_format($denominacion, 2) ?>
                            </label>
                            <input type="number" 
                                   name="denominaciones[<?= $denominacion ?>]" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-center denominacion-input"
                                   data-valor="<?= $denominacion ?>"
                                   min="0" 
                                   value="0"
                                   placeholder="0">
                            <span class="w-24 text-right text-sm font-medium subtotal" data-denominacion="<?= $denominacion ?>">
                                $0.00
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Total Contado -->
                <div class="border-t pt-4 mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-semibold text-gray-700">Total Contado:</span>
                        <span class="text-2xl font-bold text-purple-600" id="totalContado">$0.00</span>
                    </div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-gray-600">Efectivo Esperado:</span>
                        <span class="text-lg font-semibold text-gray-700">
                            $<?= number_format($resumen['efectivo_en_caja'] ?? 0, 2) ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-gray-700">Diferencia:</span>
                        <span class="text-xl font-bold" id="diferencia">$0.00</span>
                    </div>
                </div>
                
                <!-- Campo oculto para efectivo contado -->
                <input type="hidden" name="efectivo_contado" id="efectivo_contado" value="0">
                
                <!-- Observaciones -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Observaciones
                    </label>
                    <textarea name="observaciones" 
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Comentarios sobre el corte..."></textarea>
                </div>
                
                <!-- Advertencia si hay diferencia -->
                <div id="alertaDiferencia" class="hidden mb-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm font-semibold text-yellow-800">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span id="mensajeDiferencia"></span>
                        </p>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex gap-3">
                    <a href="<?= url('caja') ?>" 
                       class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition text-center">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-cut mr-2"></i>
                        Cerrar Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lista de Movimientos -->
    <div class="max-w-6xl mx-auto mt-6">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gray-800 p-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-list mr-2"></i>
                    Detalle de Movimientos (<?= count($movimientos ?? []) ?>)
                </h2>
                <button onclick="exportarMovimientos()" 
                        class="px-3 py-1 bg-white/10 hover:bg-white/20 text-white rounded transition text-sm">
                    <i class="fas fa-download mr-1"></i>
                    Exportar
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr class="text-sm text-gray-600">
                            <th class="text-left p-3">Hora</th>
                            <th class="text-left p-3">Tipo</th>
                            <th class="text-left p-3">Descripción</th>
                            <th class="text-left p-3">Categoría</th>
                            <th class="text-left p-3">Método</th>
                            <th class="text-right p-3">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php 
                        // Asegurar que $movimientos es un array
                        $movimientos = $movimientos ?? [];
                        foreach ($movimientos as $mov): 
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 text-sm">
                                    <?= date('H:i', strtotime($mov['created_at'] ?? 'now')) ?>
                                </td>
                                <td class="p-3">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        <?= ($mov['tipo'] ?? 'ingreso') == 'ingreso' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= ucfirst($mov['tipo'] ?? 'ingreso') ?>
                                    </span>
                                </td>
                                <td class="p-3 text-sm">
                                    <?= safe_html($mov['descripcion']) ?>
                                    <?php if (!empty($mov['referencia'])): ?>
                                        <span class="text-xs text-gray-500 block">
                                            Ref: <?= safe_html($mov['referencia']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-sm">
                                    <?php if (!empty($mov['categoria_nombre'])): ?>
                                        <span class="flex items-center gap-1">
                                            <i class="<?= $mov['categoria_icono'] ?? 'fas fa-tag' ?> text-xs" 
                                               style="color: <?= $mov['categoria_color'] ?? '#000' ?>"></i>
                                            <?= safe_html($mov['categoria_nombre']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-sm">
                                    <?php 
                                    $metodo = $mov['metodo_pago'] ?? 'efectivo';
                                    $icon = $metodos_pago[$metodo]['icon'] ?? 'dollar-sign';
                                    $color = $metodos_pago[$metodo]['color'] ?? 'gray';
                                    ?>
                                    <i class="fas fa-<?= $icon ?> mr-1 text-<?= $color ?>-500"></i>
                                    <?= ucfirst($metodo) ?>
                                </td>
                                <td class="p-3 text-right font-semibold <?= ($mov['tipo'] ?? 'ingreso') == 'ingreso' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= ($mov['tipo'] ?? 'ingreso') == 'ingreso' ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Variables globales
const efectivoEsperado = <?= $resumen['efectivo_en_caja'] ?? 0 ?>;

// Calcular totales del arqueo
function calcularTotales() {
    let totalContado = 0;
    
    // Calcular subtotales y total
    document.querySelectorAll('.denominacion-input').forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const valor = parseFloat(input.dataset.valor);
        const subtotal = cantidad * valor;
        
        // Actualizar subtotal de la línea
        const subtotalElement = document.querySelector(`.subtotal[data-denominacion="${valor}"]`);
        subtotalElement.textContent = '$' + subtotal.toFixed(2);
        
        totalContado += subtotal;
    });
    
    // Actualizar total contado
    document.getElementById('totalContado').textContent = '$' + totalContado.toFixed(2);
    document.getElementById('efectivo_contado').value = totalContado.toFixed(2);
    
    // Calcular diferencia
    const diferencia = totalContado - efectivoEsperado;
    const diferenciaElement = document.getElementById('diferencia');
    
    diferenciaElement.textContent = (diferencia >= 0 ? '+' : '') + '$' + diferencia.toFixed(2);
    
    // Cambiar color según diferencia
    if (diferencia > 0) {
        diferenciaElement.className = 'text-xl font-bold text-green-600';
    } else if (diferencia < 0) {
        diferenciaElement.className = 'text-xl font-bold text-red-600';
    } else {
        diferenciaElement.className = 'text-xl font-bold text-gray-700';
    }
    
    // Mostrar/ocultar alerta
    const alertaDiferencia = document.getElementById('alertaDiferencia');
    const mensajeDiferencia = document.getElementById('mensajeDiferencia');
    
    if (Math.abs(diferencia) > 0.01) {
        alertaDiferencia.classList.remove('hidden');
        if (diferencia > 0) {
            mensajeDiferencia.textContent = `Hay un sobrante de $${Math.abs(diferencia).toFixed(2)}`;
        } else {
            mensajeDiferencia.textContent = `Hay un faltante de $${Math.abs(diferencia).toFixed(2)}`;
        }
    } else {
        alertaDiferencia.classList.add('hidden');
    }
}

// Event listeners para inputs
document.querySelectorAll('.denominacion-input').forEach(input => {
    input.addEventListener('input', calcularTotales);
    input.addEventListener('focus', function() {
        if (this.value === '0') this.value = '';
    });
    input.addEventListener('blur', function() {
        if (this.value === '') this.value = '0';
    });
});

// Confirmación antes de cerrar
document.getElementById('formCorte').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const totalContado = parseFloat(document.getElementById('efectivo_contado').value);
    const diferencia = totalContado - efectivoEsperado;
    
    let mensaje = `
        <div class="text-left">
            <p class="mb-2"><strong>Efectivo esperado:</strong> $${efectivoEsperado.toFixed(2)}</p>
            <p class="mb-2"><strong>Efectivo contado:</strong> $${totalContado.toFixed(2)}</p>
    `;
    
    if (Math.abs(diferencia) > 0.01) {
        const tipo = diferencia > 0 ? 'sobrante' : 'faltante';
        const color = diferencia > 0 ? 'green' : 'red';
        mensaje += `<p class="mb-2 text-${color}-600 font-bold">
            <strong>Diferencia:</strong> ${diferencia > 0 ? '+' : ''}$${diferencia.toFixed(2)} (${tipo})
        </p>`;
    } else {
        mensaje += `<p class="mb-2 text-green-600"><strong>✔ Cuadre exacto</strong></p>`;
    }
    
    mensaje += `</div>`;
    
    // Verificar si Swal está disponible
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Confirmar cierre de caja?',
            html: mensaje,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DC2626',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-cut mr-2"></i>Sí, cerrar caja',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit();
            }
        });
    } else {
        // Fallback si SweetAlert2 no está disponible
        if (confirm('¿Confirmar cierre de caja?\n\nEfectivo esperado: $' + efectivoEsperado.toFixed(2) + '\nEfectivo contado: $' + totalContado.toFixed(2))) {
            this.submit();
        }
    }
});

// Función para exportar movimientos
function exportarMovimientos() {
    window.location.href = '<?= url('caja/exportar?corte_id=' . ($corte['id'] ?? '')) ?>';
}

// Atajo para enfocar en el primer campo
document.addEventListener('DOMContentLoaded', function() {
    const primerInput = document.querySelector('.denominacion-input');
    if (primerInput) {
        primerInput.focus();
    }
});
</script>