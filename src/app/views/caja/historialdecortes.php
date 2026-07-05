<?php
/**
 * Vista de Historial de Cortes
 * Los Cedros
 */
?>

<!-- Historial de Cortes -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-blue-500">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Historial de Cortes
                    </h1>
                    <p class="text-gray-600">
                        Registro histórico de aperturas y cierres de caja
                    </p>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <a href="<?= url('caja') ?>" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Volver</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <select id="mes" onchange="cambiarPeriodo()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $mes == $m ? 'selected' : '' ?>>
                                    <?= strftime('%B', mktime(0, 0, 0, $m, 1)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select id="año" onchange="cambiarPeriodo()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?= $y ?>" <?= $año == $y ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas del Mes -->
    <?php if ($estadisticas): ?>
        <div class="max-w-7xl mx-auto mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Cortes -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Cortes Realizados</h3>
                        <i class="fas fa-clipboard-check text-blue-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-gray-800">
                        <?= $estadisticas['total_cortes'] ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        En <?= strftime('%B %Y', mktime(0, 0, 0, $mes, 1, $año)) ?>
                    </p>
                </div>
                
                <!-- Total Ingresos -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Total Ingresos</h3>
                        <i class="fas fa-arrow-down text-green-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-green-600">
                        $<?= number_format($estadisticas['total_ingresos'], 2) ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        Acumulado del mes
                    </p>
                </div>
                
                <!-- Total Gastos -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Total Gastos</h3>
                        <i class="fas fa-arrow-up text-red-500"></i>
                    </div>
                    <p class="text-2xl font-bold text-red-600">
                        $<?= number_format($estadisticas['total_gastos'], 2) ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        Acumulado del mes
                    </p>
                </div>
                
                <!-- Diferencias -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">Diferencias</h3>
                        <i class="fas fa-balance-scale text-purple-500"></i>
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm">
                            <span class="text-green-600 font-semibold">
                                +$<?= number_format($estadisticas['sobrantes'], 2) ?>
                            </span>
                            <span class="text-gray-500 text-xs">Sobrantes</span>
                        </p>
                        <p class="text-sm">
                            <span class="text-red-600 font-semibold">
                                -$<?= number_format($estadisticas['faltantes'], 2) ?>
                            </span>
                            <span class="text-gray-500 text-xs">Faltantes</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Días con más movimiento -->
        <?php if (!empty($estadisticas['dias_top'])): ?>
            <div class="max-w-7xl mx-auto mb-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2 text-orange-500"></i>
                        Días con Mayor Movimiento
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <?php foreach ($estadisticas['dias_top'] as $dia): ?>
                            <div class="bg-orange-50 rounded-lg p-4 text-center">
                                <p class="text-sm text-gray-600">
                                    <?= date('d/m', strtotime($dia['fecha'])) ?>
                                </p>
                                <p class="text-xl font-bold text-orange-600">
                                    $<?= number_format($dia['ingresos_dia'], 2) ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= $dia['total_movimientos'] ?> movimientos
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Lista de Cortes -->
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 p-4">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-list mr-2"></i>
                    Cortes del Mes (<?= count($cortes) ?>)
                </h2>
            </div>
            
            <?php if (empty($cortes)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No hay cortes registrados en este período</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr class="text-sm text-gray-600">
                                <th class="text-left p-3">#</th>
                                <th class="text-left p-3">Fecha</th>
                                <th class="text-left p-3">Horario</th>
                                <th class="text-left p-3">Usuario</th>
                                <th class="text-right p-3">Inicial</th>
                                <th class="text-right p-3">Ingresos</th>
                                <th class="text-right p-3">Gastos</th>
                                <th class="text-right p-3">Esperado</th>
                                <th class="text-right p-3">Contado</th>
                                <th class="text-right p-3">Diferencia</th>
                                <th class="text-center p-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <?php foreach ($cortes as $corte): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 text-sm font-medium">
                                        #<?= $corte['id'] ?>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <?= date('d/m/Y', strtotime($corte['fecha_apertura'])) ?>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div>
                                            <p class="font-medium">
                                                <?= date('H:i', strtotime($corte['fecha_apertura'])) ?>
                                                <?php if ($corte['fecha_cierre']): ?>
                                                    - <?= date('H:i', strtotime($corte['fecha_cierre'])) ?>
                                                <?php else: ?>
                                                    - <span class="text-green-600">Abierta</span>
                                                <?php endif; ?>
                                            </p>
                                            <?php if ($corte['fecha_cierre']): ?>
                                                <?php 
                                                $duracion = strtotime($corte['fecha_cierre']) - strtotime($corte['fecha_apertura']);
                                                $horas = floor($duracion / 3600);
                                                $minutos = floor(($duracion % 3600) / 60);
                                                ?>
                                                <p class="text-xs text-gray-500">
                                                    Duración: <?= $horas ?>h <?= $minutos ?>m
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 text-sm">
                                        <div>
                                            <p class="font-medium"><?= htmlspecialchars($corte['usuario_apertura']) ?></p>
                                            <?php if ($corte['usuario_cierre'] && $corte['usuario_cierre'] != $corte['usuario_apertura']): ?>
                                                <p class="text-xs text-gray-500">
                                                    Cerró: <?= htmlspecialchars($corte['usuario_cierre']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="p-3 text-right text-sm">
                                        $<?= number_format($corte['monto_inicial'], 2) ?>
                                    </td>
                                    <td class="p-3 text-right text-sm text-green-600 font-medium">
                                        +$<?= number_format($corte['total_ingresos'], 2) ?>
                                    </td>
                                    <td class="p-3 text-right text-sm text-red-600 font-medium">
                                        -$<?= number_format($corte['total_gastos'], 2) ?>
                                    </td>
                                    <td class="p-3 text-right text-sm font-medium">
                                        $<?= number_format($corte['efectivo_esperado'], 2) ?>
                                    </td>
                                    <td class="p-3 text-right text-sm font-medium">
                                        <?php if ($corte['efectivo_contado'] !== null): ?>
                                            $<?= number_format($corte['efectivo_contado'], 2) ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-right text-sm font-bold">
                                        <?php if ($corte['diferencia'] !== null): ?>
                                            <?php if ($corte['diferencia'] > 0): ?>
                                                <span class="text-green-600">
                                                    +$<?= number_format($corte['diferencia'], 2) ?>
                                                </span>
                                            <?php elseif ($corte['diferencia'] < 0): ?>
                                                <span class="text-red-600">
                                                    -$<?= number_format(abs($corte['diferencia']), 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-600">$0.00</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="<?= url('caja/corte/' . $corte['id']) ?>" 
                                           class="text-blue-600 hover:text-blue-800"
                                           title="Ver detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<script>
// Cambiar período
function cambiarPeriodo() {
    const mes = document.getElementById('mes').value;
    const año = document.getElementById('año').value;
    window.location.href = `<?= url('caja/historial') ?>?mes=${mes}&año=${año}`;
}

// Graficar estadísticas si hay datos
<?php if ($estadisticas && !empty($estadisticas['dias_top'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    // Aquí podrías agregar una gráfica con Chart.js si lo deseas
});
<?php endif; ?>
</script>