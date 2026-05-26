<?php
/**
 * Vista de Historial de Cortes de Caja
 * Los Cedros
 */
?>

<style>
    .corte-card {
        background: #fff;
        border-bottom: 1px solid #eef0f3;
        padding: 16px;
    }

    .corte-card:last-child {
        border-bottom: 0;
    }

    .corte-card__metric {
        border: 1px solid #eef0f3;
        border-radius: 10px;
        padding: 10px 12px;
        background: #fafafa;
        min-width: 0;
    }

    .cortes-desktop-table th,
    .cortes-desktop-table td {
        padding-left: 12px;
        padding-right: 12px;
    }

    .cortes-desktop-table {
        table-layout: fixed;
    }
</style>

<!-- Historial de Cortes de Caja -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4 md:p-6">
    <!-- Header Principal -->
    <div class="max-w-7xl mx-auto mb-6">
        <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border-t-4 border-hotel-gold">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        Historial de Cortes de Caja
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                        <span class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <?= obtener_nombre_mes($mes) ?> <?= $año ?>
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="flex items-center">
                            <i class="fas fa-cut mr-2"></i>
                            <?= count($cortes) ?> cortes realizados
                        </span>
                    </div>
                </div>
                
                <!-- Controles de navegación -->
                <div class="flex flex-wrap gap-2">
                    <!-- Selector de mes/año -->
                    <form method="GET" action="<?= url('caja/historial') ?>" class="flex gap-2">
                        <select name="mes" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-gold focus:border-transparent">
                            <?php for($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == $mes ? 'selected' : '' ?>>
                                    <?= obtener_nombre_mes($m) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select name="año" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-hotel-gold focus:border-transparent">
                            <?php for($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $y == $año ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-hotel-brown text-white rounded-lg hover:bg-hotel-brown-dark transition">
                            <i class="fas fa-filter mr-2"></i>
                            Filtrar
                        </button>
                    </form>
                    
                    <!-- Botón volver -->
                    <a href="<?= url('caja') ?>" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span class="hidden sm:inline">Volver a Caja</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas del Mes -->
    <?php if (!empty($estadisticas)): ?>
    <div class="max-w-7xl mx-auto mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
            <!-- Total Cortes -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Cortes Realizados</p>
                        <p class="text-2xl font-bold text-gray-800">
                            <?= number_format($estadisticas['total_cortes'] ?? 0) ?>
                        </p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-cut text-xl text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Ingresos -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Ingresos</p>
                        <p class="text-2xl font-bold text-green-600">
                            $<?= number_format($estadisticas['total_ingresos'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-arrow-down text-xl text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Total Gastos -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Gastos</p>
                        <p class="text-2xl font-bold text-red-600">
                            $<?= number_format($estadisticas['total_gastos'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-arrow-up text-xl text-red-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Balance del Mes -->
            <?php 
            $balance_mes = ($estadisticas['total_ingresos'] ?? 0) - ($estadisticas['total_gastos'] ?? 0);
            ?>
            <div class="bg-gradient-to-br from-hotel-gold to-yellow-600 rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90 mb-1">Balance del Mes</p>
                        <p class="text-2xl font-bold">
                            <?= $balance_mes >= 0 ? '+' : '' ?>$<?= number_format($balance_mes, 2) ?>
                        </p>
                    </div>
                    <div class="bg-white/20 p-3 rounded-full">
                        <i class="fas fa-balance-scale text-xl"></i>
                    </div>
                </div>
            </div>
            
            <!-- Sobrantes -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Sobrantes</p>
                        <p class="text-2xl font-bold text-blue-600">
                            $<?= number_format($estadisticas['sobrantes'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-plus text-xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Faltantes -->
            <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 mb-1">Total Faltantes</p>
                        <p class="text-2xl font-bold text-orange-600">
                            $<?= number_format($estadisticas['faltantes'] ?? 0, 2) ?>
                        </p>
                    </div>
                    <div class="bg-orange-100 p-3 rounded-full">
                        <i class="fas fa-minus text-xl text-orange-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Días con más movimiento -->
        <?php if (!empty($estadisticas['dias_top'])): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 mt-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-trophy mr-2 text-yellow-500"></i>
                Días con Mayor Actividad
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <?php foreach ($estadisticas['dias_top'] as $index => $dia): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="font-medium text-gray-800">
                                <?= date('d M', strtotime($dia['fecha'])) ?>
                            </p>
                            <p class="text-xs text-gray-500">
                                <?= $dia['total_movimientos'] ?> movimientos
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-green-600">
                                $<?= number_format($dia['ingresos_dia'], 2) ?>
                            </p>
                            <?php if ($index == 0): ?>
                                <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                                    TOP
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Tabla de Cortes -->
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 p-4">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-list mr-2"></i>
                    Listado de Cortes de Caja
                </h2>
            </div>
            
            <?php if (empty($cortes)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">No hay cortes registrados en este período</p>
                </div>
            <?php else: ?>
                <div class="xl:hidden">
                    <?php foreach ($cortes as $corte): ?>
                        <?php
                        $diferencia = $corte['diferencia'] ?? 0;
                        $estado = $corte['estado'] ?? 'abierto';
                        $totalIngresos = $corte['total_ingresos'] ?? 0;
                        $totalGastos = $corte['total_gastos'] ?? 0;
                        ?>
                        <article class="corte-card">
                            <div class="flex items-start justify-between gap-3 mb-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-900">
                                            Corte #<?= str_pad($corte['id'], 6, '0', STR_PAD_LEFT) ?>
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?= $estado == 'abierto' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <i class="fas fa-<?= $estado == 'abierto' ? 'lock-open' : 'lock' ?> mr-1"></i>
                                            <?= ucfirst($estado) ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                                        <?= date('d/m/Y', strtotime($corte['fecha_apertura'])) ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Apertura: <?= date('H:i', strtotime($corte['fecha_apertura'])) ?>
                                        <?php if ($corte['fecha_cierre']): ?>
                                            · Cierre: <?= date('H:i', strtotime($corte['fecha_cierre'])) ?>
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div class="flex items-center gap-3 shrink-0">
                                    <a href="<?= url('caja/corte/' . $corte['id']) ?>"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition"
                                       title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($estado == 'cerrado'): ?>
                                        <a href="<?= url('caja/descargar-pdf/' . $corte['id']) ?>"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg bg-red-50 text-red-700 hover:bg-red-100 transition"
                                           title="Descargar PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <button onclick="exportarCorte(<?= $corte['id'] ?>)"
                                                class="w-9 h-9 inline-flex items-center justify-center rounded-lg bg-green-50 text-green-700 hover:bg-green-100 transition"
                                                title="Exportar Excel">
                                            <i class="fas fa-file-excel"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 text-sm text-gray-700 mb-3">
                                <i class="fas fa-user text-gray-400 text-xs"></i>
                                <span class="font-medium truncate"><?= htmlspecialchars($corte['usuario_apertura'] ?? 'N/A') ?></span>
                                <?php if ($corte['usuario_cierre'] && $corte['usuario_cierre'] != $corte['usuario_apertura']): ?>
                                    <span class="text-gray-400">·</span>
                                    <span class="text-xs text-gray-500 truncate">Cerró: <?= htmlspecialchars($corte['usuario_cierre']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div class="corte-card__metric">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Ingresos</p>
                                    <p class="text-sm font-bold text-green-600">+$<?= number_format($totalIngresos, 2) ?></p>
                                </div>
                                <div class="corte-card__metric">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Gastos</p>
                                    <p class="text-sm font-bold text-red-600">-$<?= number_format($totalGastos, 2) ?></p>
                                </div>
                                <div class="corte-card__metric">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Esperado</p>
                                    <p class="text-sm font-bold text-gray-900">$<?= number_format($corte['efectivo_esperado'] ?? 0, 2) ?></p>
                                </div>
                                <div class="corte-card__metric">
                                    <p class="text-[11px] uppercase tracking-wide text-gray-500 font-semibold">Contado</p>
                                    <p class="text-sm font-bold text-gray-900">$<?= number_format($corte['efectivo_contado'] ?? 0, 2) ?></p>
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="text-xs text-gray-500">
                                    <span title="Ingresos efectivo">IE: $<?= number_format($corte['total_ingresos_efectivo'] ?? 0, 2) ?></span>
                                    <span class="mx-1 text-gray-300">|</span>
                                    <span title="Ingresos tarjeta">IT: $<?= number_format($corte['total_ingresos_tarjeta'] ?? 0, 2) ?></span>
                                    <span class="mx-1 text-gray-300">|</span>
                                    <span title="Ingresos transferencia">ITr: $<?= number_format($corte['total_ingresos_transferencia'] ?? 0, 2) ?></span>
                                </div>
                                <?php if ($diferencia != 0): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        <?= $diferencia > 0 ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' ?>">
                                        <i class="fas fa-<?= $diferencia > 0 ? 'plus' : 'minus' ?> mr-1"></i>
                                        Diferencia $<?= number_format(abs($diferencia), 2) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check mr-1"></i>
                                        Cuadrado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="hidden xl:block">
                    <table class="w-full cortes-desktop-table">
                        <colgroup>
                            <col style="width: 8%;">
                            <col style="width: 12%;">
                            <col style="width: 12%;">
                            <col style="width: 13%;">
                            <col style="width: 11%;">
                            <col style="width: 11%;">
                            <col style="width: 10%;">
                            <col style="width: 9%;">
                            <col style="width: 7%;">
                            <col style="width: 7%;">
                        </colgroup>
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Corte #
                                </th>
                                <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha/Hora
                                </th>
                                <th class="py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Usuario
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ingresos
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Gastos
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efectivo Esperado
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Efectivo Contado
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Diferencia
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th class="py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($cortes as $corte): ?>
                                <?php 
                                $diferencia = $corte['diferencia'] ?? 0;
                                $estado = $corte['estado'] ?? 'abierto';
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 align-top">
                                        <span class="text-sm font-semibold text-gray-900">
                                            #<?= str_pad($corte['id'], 6, '0', STR_PAD_LEFT) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 align-top">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= date('d/m/Y', strtotime($corte['fecha_apertura'])) ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                Apertura: <?= date('H:i', strtotime($corte['fecha_apertura'])) ?>
                                                <?php if ($corte['fecha_cierre']): ?>
                                                    - Cierre: <?= date('H:i', strtotime($corte['fecha_cierre'])) ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="py-4 align-top">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                <?= htmlspecialchars($corte['usuario_apertura'] ?? 'N/A') ?>
                                            </p>
                                            <?php if ($corte['usuario_cierre'] && $corte['usuario_cierre'] != $corte['usuario_apertura']): ?>
                                                <p class="text-xs text-gray-500">
                                                    Cerró: <?= htmlspecialchars($corte['usuario_cierre']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <div>
                                            <p class="text-sm font-semibold text-green-600">
                                                $<?= number_format($corte['total_ingresos'] ?? 0, 2) ?>
                                            </p>
                                            <div class="text-xs text-gray-500">
                                                <span title="Efectivo">E: $<?= number_format($corte['total_ingresos_efectivo'] ?? 0, 2) ?></span> |
                                                <span title="Tarjeta">T: $<?= number_format($corte['total_ingresos_tarjeta'] ?? 0, 2) ?></span> |
                                                <span title="Transferencia">Tr: $<?= number_format($corte['total_ingresos_transferencia'] ?? 0, 2) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <div>
                                            <p class="text-sm font-semibold text-red-600">
                                                $<?= number_format($corte['total_gastos'] ?? 0, 2) ?>
                                            </p>
                                            <div class="text-xs text-gray-500">
                                                <span title="Efectivo">E: $<?= number_format($corte['total_gastos_efectivo'] ?? 0, 2) ?></span> |
                                                <span title="Tarjeta">T: $<?= number_format($corte['total_gastos_tarjeta'] ?? 0, 2) ?></span> |
                                                <span title="Transferencia">Tr: $<?= number_format($corte['total_gastos_transferencia'] ?? 0, 2) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <p class="text-sm font-medium text-gray-900">
                                            $<?= number_format($corte['efectivo_esperado'] ?? 0, 2) ?>
                                        </p>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <p class="text-sm font-medium text-gray-900">
                                            $<?= number_format($corte['efectivo_contado'] ?? 0, 2) ?>
                                        </p>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <?php if ($diferencia != 0): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?= $diferencia > 0 ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' ?>">
                                                <i class="fas fa-<?= $diferencia > 0 ? 'plus' : 'minus' ?> mr-1"></i>
                                                $<?= number_format(abs($diferencia), 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>
                                                Cuadrado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            <?= $estado == 'abierto' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
                                            <i class="fas fa-<?= $estado == 'abierto' ? 'lock-open' : 'lock' ?> mr-1"></i>
                                            <?= ucfirst($estado) ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-center align-top">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="<?= url('caja/corte/' . $corte['id']) ?>" 
                                               class="text-blue-600 hover:text-blue-800 transition"
                                               title="Ver detalle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($estado == 'cerrado'): ?>
                                                <a href="<?= url('caja/descargar-pdf/' . $corte['id']) ?>" 
   class="text-red-600 hover:text-red-800 transition"
   title="Descargar PDF">
    <i class="fas fa-file-pdf"></i>
</a>
                                                <button onclick="exportarCorte(<?= $corte['id'] ?>)" 
                                                        class="text-green-600 hover:text-green-800 transition"
                                                        title="Exportar Excel">
                                                    <i class="fas fa-file-excel"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Gráfica de Tendencias del Mes -->
    <?php if (!empty($cortes)): ?>
    <div class="max-w-7xl mx-auto mt-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                Tendencia de Ingresos y Gastos del Mes
            </h3>
            <div style="position: relative; height: 300px;">
                <canvas id="chartTendencias"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Resumen por Método de Pago -->
    <?php if (!empty($cortes)): ?>
    <div class="max-w-7xl mx-auto mt-6">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-credit-card mr-2 text-purple-600"></i>
                Resumen del Mes por Método de Pago
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php 
                // Calcular totales por método
                $totales_metodo = [
                    'efectivo' => ['ingresos' => 0, 'gastos' => 0],
                    'tarjeta' => ['ingresos' => 0, 'gastos' => 0],
                    'transferencia' => ['ingresos' => 0, 'gastos' => 0]
                ];
                
                foreach ($cortes as $corte) {
                    if ($corte['estado'] == 'cerrado') {
                        $totales_metodo['efectivo']['ingresos'] += $corte['total_ingresos_efectivo'] ?? 0;
                        $totales_metodo['efectivo']['gastos'] += $corte['total_gastos_efectivo'] ?? 0;
                        $totales_metodo['tarjeta']['ingresos'] += $corte['total_ingresos_tarjeta'] ?? 0;
                        $totales_metodo['tarjeta']['gastos'] += $corte['total_gastos_tarjeta'] ?? 0;
                        $totales_metodo['transferencia']['ingresos'] += $corte['total_ingresos_transferencia'] ?? 0;
                        $totales_metodo['transferencia']['gastos'] += $corte['total_gastos_transferencia'] ?? 0;
                    }
                }
                ?>
                
                <!-- Efectivo -->
                <div class="border-2 border-green-200 rounded-xl p-4 bg-green-50">
                    <h4 class="font-semibold text-gray-700 flex items-center mb-3">
                        <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                        Efectivo
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Ingresos:</span>
                            <span class="font-semibold text-green-600">
                                +$<?= number_format($totales_metodo['efectivo']['ingresos'], 2) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Gastos:</span>
                            <span class="font-semibold text-red-600">
                                -$<?= number_format($totales_metodo['efectivo']['gastos'], 2) ?>
                            </span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Balance:</span>
                                <?php $balance_efectivo = $totales_metodo['efectivo']['ingresos'] - $totales_metodo['efectivo']['gastos']; ?>
                                <span class="font-bold text-lg <?= $balance_efectivo >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $balance_efectivo >= 0 ? '+' : '' ?>$<?= number_format($balance_efectivo, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjeta -->
                <div class="border-2 border-blue-200 rounded-xl p-4 bg-blue-50">
                    <h4 class="font-semibold text-gray-700 flex items-center mb-3">
                        <i class="fas fa-credit-card mr-2 text-blue-600"></i>
                        Tarjeta
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Ingresos:</span>
                            <span class="font-semibold text-green-600">
                                +$<?= number_format($totales_metodo['tarjeta']['ingresos'], 2) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Gastos:</span>
                            <span class="font-semibold text-red-600">
                                -$<?= number_format($totales_metodo['tarjeta']['gastos'], 2) ?>
                            </span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Balance:</span>
                                <?php $balance_tarjeta = $totales_metodo['tarjeta']['ingresos'] - $totales_metodo['tarjeta']['gastos']; ?>
                                <span class="font-bold text-lg <?= $balance_tarjeta >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $balance_tarjeta >= 0 ? '+' : '' ?>$<?= number_format($balance_tarjeta, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transferencia -->
                <div class="border-2 border-purple-200 rounded-xl p-4 bg-purple-50">
                    <h4 class="font-semibold text-gray-700 flex items-center mb-3">
                        <i class="fas fa-exchange-alt mr-2 text-purple-600"></i>
                        Transferencia
                    </h4>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Ingresos:</span>
                            <span class="font-semibold text-green-600">
                                +$<?= number_format($totales_metodo['transferencia']['ingresos'], 2) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Gastos:</span>
                            <span class="font-semibold text-red-600">
                                -$<?= number_format($totales_metodo['transferencia']['gastos'], 2) ?>
                            </span>
                        </div>
                        <div class="border-t pt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Balance:</span>
                                <?php $balance_transferencia = $totales_metodo['transferencia']['ingresos'] - $totales_metodo['transferencia']['gastos']; ?>
                                <span class="font-bold text-lg <?= $balance_transferencia >= 0 ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $balance_transferencia >= 0 ? '+' : '' ?>$<?= number_format($balance_transferencia, 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Gráfica de tendencias
<?php if (!empty($cortes)): ?>
    // Preparar datos para la gráfica
    const cortesData = <?= json_encode($cortes) ?>;
    
    // Agrupar por día
    const datosPorDia = {};
    cortesData.forEach(corte => {
        const fecha = corte.fecha_apertura.split(' ')[0];
        if (!datosPorDia[fecha]) {
            datosPorDia[fecha] = {
                ingresos: 0,
                gastos: 0
            };
        }
        datosPorDia[fecha].ingresos += parseFloat(corte.total_ingresos || 0);
        datosPorDia[fecha].gastos += parseFloat(corte.total_gastos || 0);
    });
    
    const fechasOrdenadas = Object.keys(datosPorDia).sort();
    const ingresosPorDia = fechasOrdenadas.map(fecha => datosPorDia[fecha].ingresos);
    const gastosPorDia = fechasOrdenadas.map(fecha => datosPorDia[fecha].gastos);
    const etiquetas = fechasOrdenadas.map(fecha => {
        const [año, mes, dia] = fecha.split('-');
        return `${dia}/${mes}`;
    });
    
    const ctx = document.getElementById('chartTendencias').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Ingresos',
                data: ingresosPorDia,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Gastos',
                data: gastosPorDia,
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += '$' + new Intl.NumberFormat('es-MX').format(context.parsed.y);
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + new Intl.NumberFormat('es-MX').format(value);
                        }
                    }
                }
            }
        }
    });
<?php endif; ?>

// Función para imprimir corte
function imprimirCorte(id) {
    window.open('<?= url('caja/corte/') ?>' + id + '?print=1', '_blank');
}

// Función para exportar corte
function exportarCorte(id) {
    window.location.href = '<?= url('caja/exportar?formato=excel&corte_id=') ?>' + id;
}

// Función auxiliar para obtener nombre del mes
<?php
function obtener_nombre_mes($mes) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[$mes] ?? '';
}
?>
</script>
