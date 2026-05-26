<?php ?>

<!-- Estilos específicos para el reporte de habitaciones rentables - VERSIÓN RESPONSIVE -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
    --hotel-gold-light: #E5C69B;
}

.rentables-view { opacity: 0; transition: opacity 0.3s ease; }
.rentables-view.loaded { opacity: 1; }

/* Animaciones */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes rankUp {
    0% { transform: scale(0.8) rotate(-5deg); opacity: 0; }
    100% { transform: scale(1) rotate(0); opacity: 1; }
}

.stat-card {
    animation: slideIn 0.3s ease forwards;
    opacity: 0;
}

.stat-card:nth-child(1) { animation-delay: 0.05s; }
.stat-card:nth-child(2) { animation-delay: 0.1s; }
.stat-card:nth-child(3) { animation-delay: 0.15s; }
.stat-card:nth-child(4) { animation-delay: 0.2s; }

/* Efectos hover */
.hover-lift {
    transition: all 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}

/* Ranking badges */
.rank-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.875rem;
    animation: rankUp 0.5s ease forwards;
}

.rank-gold { 
    background: linear-gradient(135deg, #FFD700, #FFA500); 
    color: white;
    box-shadow: 0 2px 4px rgba(255,215,0,0.4);
}
.rank-silver { 
    background: linear-gradient(135deg, #C0C0C0, #808080); 
    color: white;
    box-shadow: 0 2px 4px rgba(192,192,192,0.4);
}
.rank-bronze { 
    background: linear-gradient(135deg, #CD7F32, #8B4513); 
    color: white;
    box-shadow: 0 2px 4px rgba(205,127,50,0.4);
}
.rank-regular { 
    background: #e5e7eb; 
    color: #6b7280;
}

/* Tablas compactas */
.data-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
    font-size: 0.813rem;
}

.data-table th {
    background: linear-gradient(135deg, var(--hotel-brown), var(--hotel-brown-dark));
    color: white;
    font-weight: 600;
    text-align: left;
    padding: 0.75rem;
    position: sticky;
    top: 0;
    z-index: 10;
    font-size: 0.75rem;
}

.data-table tbody tr {
    transition: all 0.15s ease;
    border-bottom: 1px solid #f3f4f6;
}

.data-table tbody tr:hover {
    background: rgba(212, 175, 55, 0.05);
    transform: translateX(2px);
}

.data-table td {
    padding: 0.5rem 0.75rem;
}

/* Progress bars para ocupación */
.ocupacion-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 2px;
}

.ocupacion-fill {
    height: 100%;
    transition: width 1s ease;
    position: relative;
    overflow: hidden;
}

.ocupacion-fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Revenue badges */
.revenue-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.revenue-high {
    background: rgba(16, 185, 129, 0.1);
    color: #047857;
}

.revenue-medium {
    background: rgba(251, 191, 36, 0.1);
    color: #b45309;
}

.revenue-low {
    background: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

/* Estilos para gráficas */
.chart-container {
    position: relative;
    height: 300px;
}

/* Botones de período */
.period-btn {
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.period-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.2);
    transform: translate(-50%, -50%);
    transition: width 0.5s, height 0.5s;
}

.period-btn:hover::before {
    width: 200px;
    height: 200px;
}

/* ESTILOS RESPONSIVE MEJORADOS */
@media (max-width: 768px) {
    /* Ajustes generales */
    .container {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    
    /* Header más compacto */
    .text-lg {
        font-size: 0.875rem !important;
    }
    
    .text-base {
        font-size: 0.75rem !important;
    }
    
    .text-sm {
        font-size: 0.625rem !important;
    }
    
    .text-xs {
        font-size: 0.5625rem !important;
    }
    
    /* Filtros más compactos */
    .p-3 {
        padding: 0.5rem !important;
    }
    
    .gap-3 {
        gap: 0.5rem !important;
    }
    
    /* Cards estadísticas ultra compactas */
    .stat-card .p-3 {
        padding: 0.5rem !important;
    }
    
    .stat-card .text-base {
        font-size: 0.875rem !important;
    }
    
    .stat-card .bg-emerald-100,
    .stat-card .bg-blue-100,
    .stat-card .bg-purple-100,
    .stat-card .bg-amber-100 {
        padding: 0.375rem !important;
    }
    
    /* Iconos más pequeños */
    .stat-card i {
        font-size: 0.625rem !important;
    }
    
    /* Badges más pequeños */
    .rank-badge {
        width: 20px !important;
        height: 20px !important;
        font-size: 0.625rem !important;
    }
    
    /* Tablas ultra compactas */
    .data-table {
        font-size: 0.625rem !important;
    }
    
    .data-table th {
        padding: 0.25rem !important;
        font-size: 0.5625rem !important;
    }
    
    .data-table td {
        padding: 0.25rem !important;
        font-size: 0.5625rem !important;
    }
    
    /* Ocultar columnas en móvil */
    .data-table th:nth-child(3),
    .data-table td:nth-child(3),
    .data-table th:nth-child(4),
    .data-table td:nth-child(4),
    .data-table th:nth-child(6),
    .data-table td:nth-child(6),
    .data-table th:nth-child(8),
    .data-table td:nth-child(8) {
        display: none;
    }
    
    /* Barras de ocupación más delgadas */
    .ocupacion-bar {
        height: 3px !important;
    }
    
    /* Revenue badges más pequeños */
    .revenue-badge {
        padding: 0.125rem 0.25rem !important;
        font-size: 0.5rem !important;
    }
    
    /* Gráficas más pequeñas */
    .chart-container {
        height: 200px !important;
    }
    
    /* Botones más compactos */
    button {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.625rem !important;
    }
    
    /* Botones de período aún más pequeños */
    .period-btn {
        padding: 0.25rem 0.375rem !important;
        font-size: 0.5rem !important;
    }
    
    /* Espaciados reducidos */
    .mb-3 {
        margin-bottom: 0.5rem !important;
    }
    
    .mb-2 {
        margin-bottom: 0.375rem !important;
    }
    
    /* Grid de cards en una sola columna */
    .grid.grid-cols-2 {
        grid-template-columns: 1fr !important;
        gap: 0.5rem !important;
    }
    
    /* Altura máxima de tablas reducida */
    .overflow-x-auto {
        max-height: 250px !important;
    }
    
    /* Cards de tipo más compactas */
    .grid.md\:grid-cols-2.lg\:grid-cols-3 {
        grid-template-columns: 1fr !important;
    }
    
    /* Insights más compactos */
    .grid.md\:grid-cols-2.lg\:grid-cols-3 .p-3 {
        padding: 0.5rem !important;
    }
}

/* Pantallas muy pequeñas */
@media (max-width: 380px) {
    /* Container con menos padding */
    .container {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    
    /* Texto aún más pequeño */
    .font-bold {
        font-weight: 600 !important;
    }
    
    /* Título principal más corto */
    h1 span {
        display: none;
    }
    
    h1::after {
        content: "Top Habitaciones";
    }
    
    /* Fecha más compacta */
    .text-hotel-gold {
        font-size: 0.5rem !important;
    }
    
    /* Cards con texto truncado */
    .stat-card h3 {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Inputs de fecha más pequeños */
    input[type="date"] {
        font-size: 0.75rem !important;
        padding: 0.25rem !important;
    }
    
    /* Ocultar más columnas en tabla */
    .data-table th:nth-child(5),
    .data-table td:nth-child(5) {
        display: none;
    }
}

/* Mejoras de accesibilidad móvil */
@media (max-width: 640px) {
    /* Touch targets mínimos */
    button,
    input,
    select {
        min-height: 40px;
    }
    
    /* Prevenir zoom en iOS */
    input[type="date"] {
        font-size: 16px;
    }
    
    /* Hover deshabilitado en móvil */
    @media (hover: none) {
        .hover-lift:hover {
            transform: none;
            box-shadow: none;
        }
        
        .data-table tbody tr:hover {
            background: transparent;
            transform: none;
        }
    }
}

/* Orientación landscape en móviles */
@media (max-width: 768px) and (orientation: landscape) {
    .chart-container {
        height: 150px !important;
    }
    
    .overflow-x-auto {
        max-height: 200px !important;
    }
}

/* Print styles */
@media print {
    .rentables-view { background: white !important; }
    form, button { display: none !important; }
    .chart-container { height: 200px !important; }
    .bg-gradient-to-r { background: #6B4423 !important; }
}
</style>

<div class="rentables-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-1 md:py-2">
    <!-- Header del Reporte - ULTRA COMPACTO PARA MÓVIL -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-lg">
        <div class="container mx-auto px-3 md:px-4 py-1.5 md:py-2">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-1 md:gap-2">
                <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
                    <div class="flex items-center gap-1.5">
                        <a href="<?= url('reportes') ?>" 
                           class="bg-white/10 backdrop-blur hover:bg-white/20 p-1 md:p-1.5 rounded transition-colors">
                            <i class="fas fa-arrow-left text-xs md:text-sm"></i>
                        </a>
                        <h1 class="text-sm md:text-lg font-bold font-playfair flex items-center gap-1 md:gap-2">
                            <i class="fas fa-trophy text-xs md:text-base opacity-80 hidden sm:inline"></i>
                            <span class="hidden md:inline">Habitaciones más Rentables</span>
                            <span class="md:hidden">Top Habitaciones</span>
                        </h1>
                    </div>
                    <span class="text-hotel-gold text-xs ml-1 md:ml-2">
                        <?= date('d/m', strtotime($fecha_inicio)) ?> - <?= date('d/m/y', strtotime($fecha_fin)) ?>
                    </span>
                </div>
                
                <div class="flex gap-1 md:gap-2">
                    
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-3 md:px-4 py-2 md:py-3 max-w-7xl">
        <!-- Filtros Ultra Compactos para Móvil -->
        <div class="bg-white rounded-lg shadow-sm p-2 md:p-3 mb-2 md:mb-3 border border-gray-100">
            <form method="GET" action="<?= url('reportes/habitaciones-rentables') ?>" class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-2 md:gap-3">
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="flex-1 min-w-0">
                        <label for="fecha_inicio" class="block text-xs font-semibold text-gray-700 mb-0.5">
                            <i class="fas fa-calendar-alt mr-0.5 text-xs"></i>Desde
                        </label>
                        <input type="date" 
                               id="fecha_inicio" 
                               name="fecha_inicio" 
                               value="<?= $fecha_inicio ?>" 
                               max="<?= date('Y-m-d') ?>"
                               class="w-full px-1.5 py-1 text-xs md:text-sm border border-gray-300 rounded focus:ring-1 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <label for="fecha_fin" class="block text-xs font-semibold text-gray-700 mb-0.5">
                            <i class="fas fa-calendar-check mr-0.5 text-xs"></i>Hasta
                        </label>
                        <input type="date" 
                               id="fecha_fin" 
                               name="fecha_fin" 
                               value="<?= $fecha_fin ?>" 
                               max="<?= date('Y-m-d') ?>"
                               class="w-full px-1.5 py-1 text-xs md:text-sm border border-gray-300 rounded focus:ring-1 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                    </div>
                </div>
                
                <div class="flex gap-1.5 sm:gap-2 w-full sm:w-auto">
                    <button type="submit" 
                            class="bg-hotel-brown text-white px-3 py-1.5 rounded hover:bg-hotel-brown-dark transition-all text-xs md:text-sm flex items-center justify-center gap-1 shadow flex-1 sm:flex-initial">
                        <i class="fas fa-filter text-xs"></i>
                        <span>Filtrar</span>
                    </button>
                    
                    <!-- Períodos Rápidos - Grid en móvil -->
                    
                </div>
            </form>
        </div>

        <!-- Cards de Estadísticas - ULTRA COMPACTAS PARA MÓVIL -->
        <?php 
        $total_general = array_sum(array_column($rentabilidad, 'ingresos_totales'));
        $total_reservaciones = array_sum(array_column($rentabilidad, 'total_reservaciones'));
        $promedio_ocupacion = count($rentabilidad) > 0 ? array_sum(array_column($rentabilidad, 'porcentaje_ocupacion')) / count($rentabilidad) : 0;
        $mejor_habitacion = $rentabilidad[0] ?? null;
        ?>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 mb-2 md:mb-3">
            <!-- Ingresos Totales -->
            <div class="stat-card hover-lift bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-emerald-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-dollar-sign text-emerald-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-xs md:text-base font-bold text-gray-800 truncate">
                            $<?= number_format($total_general, 0) ?>
                        </span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Ingresos Totales</h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        Del período
                    </p>
                </div>
            </div>

            <!-- Total Reservaciones -->
            <div class="stat-card hover-lift bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-blue-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-calendar-check text-blue-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-sm md:text-lg font-bold text-gray-800">
                            <?= number_format($total_reservaciones) ?>
                        </span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Reservaciones</h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        Completadas
                    </p>
                </div>
            </div>

            <!-- Ocupación Promedio -->
            <div class="stat-card hover-lift bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-purple-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-percentage text-purple-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-sm md:text-lg font-bold text-gray-800">
                            <?= number_format($promedio_ocupacion, 1) ?>%
                        </span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Ocupación</h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        Promedio
                    </p>
                </div>
            </div>

            <!-- Mejor Habitación -->
            <div class="stat-card hover-lift bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-amber-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-crown text-amber-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-amber-700 bg-amber-100 px-1 py-0.5 rounded">
                            #1
                        </span>
                    </div>
                    <h3 class="text-xs md:text-sm font-bold text-gray-800 truncate">
                        <?= $mejor_habitacion ? 'Hab. ' . htmlspecialchars($mejor_habitacion['numero']) : 'N/A' ?>
                    </h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        <?= $mejor_habitacion ? '$' . number_format($mejor_habitacion['ingresos_totales'], 0) : 'Sin datos' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabla de Ranking Principal - RESPONSIVE -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-2 md:mb-3">
            <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark px-3 md:px-4 py-1.5 md:py-2">
                <h3 class="text-xs md:text-sm font-bold text-white flex items-center gap-1">
                    <i class="fas fa-list-ol text-xs"></i>
                    Ranking de Rentabilidad
                </h3>
            </div>
            <div class="p-2 md:p-3">
                <div class="overflow-x-auto" style="max-height: 300px; md:max-height: 400px;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 30px">#</th>
                                <th>Habitación</th>
                                <th class="hidden sm:table-cell">Tipo</th>
                                <th class="hidden sm:table-cell text-center">Piso</th>
                                <th class="text-center">Res.</th>
                                <th class="hidden md:table-cell text-center">Días</th>
                                <th class="text-right">Ingresos</th>
                                <th class="hidden md:table-cell text-right">$/Día</th>
                                <th class="text-center">Ocup.</th>
                                <th class="text-center hidden sm:table-cell">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $posicion = 1;
                            foreach ($rentabilidad as $habitacion): 
                                if (!$habitacion['ingresos_totales']) continue;
                                $porcentaje = $total_general > 0 ? ($habitacion['ingresos_totales'] / $total_general * 100) : 0;
                                $rankClass = $posicion === 1 ? 'rank-gold' : ($posicion === 2 ? 'rank-silver' : ($posicion === 3 ? 'rank-bronze' : 'rank-regular'));
                                $ocupacionColor = $habitacion['porcentaje_ocupacion'] >= 80 ? '#10b981' : ($habitacion['porcentaje_ocupacion'] >= 60 ? '#f59e0b' : '#ef4444');
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="rank-badge <?= $rankClass ?>" style="animation-delay: <?= $posicion * 0.05 ?>s">
                                        <?= $posicion++ ?>
                                    </span>
                                </td>
                                <td class="font-medium text-gray-800">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-bed text-gray-400 text-xs"></i>
                                        <span class="text-xs md:text-sm"><?= htmlspecialchars($habitacion['numero']) ?></span>
                                    </div>
                                </td>
                                <td class="text-xs text-gray-600 hidden sm:table-cell">
                                    <?= htmlspecialchars($habitacion['tipo']) ?>
                                </td>
                                <td class="text-center text-xs md:text-sm hidden sm:table-cell">
                                    <?= htmlspecialchars($habitacion['piso']) ?>
                                </td>
                                <td class="text-center font-semibold text-xs md:text-sm">
                                    <?= $habitacion['total_reservaciones'] ?>
                                </td>
                                <td class="text-center text-xs md:text-sm hidden md:table-cell">
                                    <?= $habitacion['dias_ocupada'] ?>
                                </td>
                                <td class="text-right font-bold text-emerald-700 text-xs md:text-sm">
                                    <span class="hidden sm:inline">$<?= number_format($habitacion['ingresos_totales'], 0) ?></span>
                                    <span class="sm:hidden">
                                        <?php 
                                        $amount = $habitacion['ingresos_totales'];
                                        if ($amount >= 1000000) {
                                            echo '$' . number_format($amount / 1000000, 1) . 'M';
                                        } elseif ($amount >= 1000) {
                                            echo '$' . number_format($amount / 1000, 0) . 'k';
                                        } else {
                                            echo '$' . number_format($amount, 0);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="text-right text-xs md:text-sm text-gray-600 hidden md:table-cell">
                                    $<?= number_format($habitacion['precio_promedio'], 0) ?>
                                </td>
                                <td>
                                    <div>
                                        <div class="text-xs font-medium text-center">
                                            <?= number_format($habitacion['porcentaje_ocupacion'], 0) ?>%
                                        </div>
                                        <div class="ocupacion-bar hidden sm:block">
                                            <div class="ocupacion-fill" 
                                                 style="width: <?= $habitacion['porcentaje_ocupacion'] ?>%; background: <?= $ocupacionColor ?>;">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center hidden sm:table-cell">
                                    <span class="revenue-badge <?= $porcentaje >= 5 ? 'revenue-high' : ($porcentaje >= 2 ? 'revenue-medium' : 'revenue-low') ?>">
                                        <?= number_format($porcentaje, 1) ?>%
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gráficas en Grid - RESPONSIVE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 md:gap-3 mb-2 md:mb-3">
            <!-- Gráfica de Top 10 Ingresos -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2 md:p-3">
                <h3 class="text-xs md:text-sm font-bold text-gray-800 mb-1.5 md:mb-2 flex items-center gap-1 md:gap-2">
                    <i class="fas fa-chart-bar text-hotel-brown text-xs"></i>
                    Top 10 - Mayores Ingresos
                </h3>
                <div class="chart-container">
                    <canvas id="chartIngresosHabitacion"></canvas>
                </div>
            </div>

            <!-- Gráfica de Distribución por Tipo -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2 md:p-3">
                <h3 class="text-xs md:text-sm font-bold text-gray-800 mb-1.5 md:mb-2 flex items-center gap-1 md:gap-2">
                    <i class="fas fa-chart-pie text-hotel-brown text-xs"></i>
                    Distribución por Tipo
                </h3>
                <div class="chart-container">
                    <canvas id="chartDistribucionTipo"></canvas>
                </div>
            </div>
        </div>

        <!-- Análisis por Tipo - Cards RESPONSIVE -->
        <div class="mb-2 md:mb-3">
            <h3 class="text-xs md:text-sm font-bold text-gray-800 mb-1.5 md:mb-2 flex items-center gap-1 md:gap-2">
                <i class="fas fa-layer-group text-hotel-brown text-xs"></i>
                Rendimiento por Tipo de Habitación
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-3">
                <?php foreach ($ocupacionPorTipo as $tipo): 
                    $porcentajeTipo = $total_general > 0 ? ($tipo['ingresos_totales'] / $total_general * 100) : 0;
                ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2 md:p-3 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-1.5 md:mb-2">
                        <h4 class="font-bold text-gray-800 text-xs md:text-sm"><?= htmlspecialchars($tipo['tipo']) ?></h4>
                        <span class="text-xs bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">
                            <?= $tipo['total_habitaciones'] ?> hab.
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-1.5 md:gap-2 text-xs">
                        <div>
                            <span class="text-gray-500">Ingresos:</span>
                            <p class="font-semibold text-emerald-700 truncate">
                                $<?= number_format($tipo['ingresos_totales'], 0) ?>
                            </p>
                        </div>
                        <div>
                            <span class="text-gray-500">Precio prom:</span>
                            <p class="font-semibold">$<?= number_format($tipo['precio_promedio'], 0) ?></p>
                        </div>
                        <div>
                            <span class="text-gray-500">Días ocup:</span>
                            <p class="font-semibold"><?= number_format($tipo['dias_ocupadas']) ?></p>
                        </div>
                        <div>
                            <span class="text-gray-500">% del total:</span>
                            <p class="font-semibold text-hotel-brown"><?= number_format($porcentajeTipo, 1) ?>%</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Gráficas adicionales - RESPONSIVE -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 md:gap-3 mb-2 md:mb-3">
            <!-- Comparación de Precios -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2 md:p-3">
                <h3 class="text-xs md:text-sm font-bold text-gray-800 mb-1.5 md:mb-2 flex items-center gap-1 md:gap-2">
                    <i class="fas fa-tags text-hotel-brown text-xs"></i>
                    Comparación de Precios
                </h3>
                <div class="chart-container">
                    <canvas id="chartComparacionPrecios"></canvas>
                </div>
            </div>

            <!-- Porcentaje de Ocupación -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-2 md:p-3">
                <h3 class="text-xs md:text-sm font-bold text-gray-800 mb-1.5 md:mb-2 flex items-center gap-1 md:gap-2">
                    <i class="fas fa-bed text-hotel-brown text-xs"></i>
                    % Ocupación - Top 10
                </h3>
                <div class="chart-container">
                    <canvas id="chartOcupacion"></canvas>
                </div>
            </div>
        </div>

        <!-- Insights - RESPONSIVE -->
        <div class="bg-gradient-to-br from-hotel-gold/20 to-hotel-brown/10 rounded-lg p-3 md:p-4 border border-hotel-gold/30">
            <h3 class="text-xs md:text-sm font-bold text-hotel-brown mb-2 md:mb-3 flex items-center gap-1 md:gap-2">
                <i class="fas fa-lightbulb text-xs"></i>
                Insights y Recomendaciones
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 md:gap-3">
                <?php 
                // Calcular insights
                $mejor_tipo = $ocupacionPorTipo[0] ?? null;
                $habitacion_mas_rentable_por_dia = null;
                $max_ingreso_diario = 0;
                foreach ($rentabilidad as $h) {
                    if ($h['dias_ocupada'] > 0) {
                        $ingreso_diario = $h['ingresos_totales'] / $h['dias_ocupada'];
                        if ($ingreso_diario > $max_ingreso_diario) {
                            $max_ingreso_diario = $ingreso_diario;
                            $habitacion_mas_rentable_por_dia = $h;
                        }
                    }
                }
                ?>
                
                <div class="bg-white/80 backdrop-blur rounded-lg p-2 md:p-3">
                    <h4 class="font-semibold text-gray-800 mb-0.5 md:mb-1 text-xs md:text-sm flex items-center gap-1">
                        <i class="fas fa-star text-amber-500 text-xs"></i>
                        Tipo más rentable
                    </h4>
                    <p class="text-xs text-gray-600">
                        <?php if($mejor_tipo): ?>
                        <strong><?= $mejor_tipo['tipo'] ?></strong> genera el
                        <strong><?= round(($mejor_tipo['ingresos_totales'] / $total_general) * 100, 1) ?>%</strong>
                        de los ingresos
                        <?php endif; ?>
                    </p>
                </div>

                <div class="bg-white/80 backdrop-blur rounded-lg p-2 md:p-3">
                    <h4 class="font-semibold text-gray-800 mb-0.5 md:mb-1 text-xs md:text-sm flex items-center gap-1">
                        <i class="fas fa-chart-line text-emerald-600 text-xs"></i>
                        Mayor ingreso diario
                    </h4>
                    <p class="text-xs text-gray-600">
                        <?php if($habitacion_mas_rentable_por_dia): ?>
                        <strong>Hab. <?= $habitacion_mas_rentable_por_dia['numero'] ?></strong>
                        con <strong>$<?= number_format($max_ingreso_diario, 0) ?>/día</strong>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="bg-white/80 backdrop-blur rounded-lg p-2 md:p-3">
                    <h4 class="font-semibold text-gray-800 mb-0.5 md:mb-1 text-xs md:text-sm flex items-center gap-1">
                        <i class="fas fa-exclamation-circle text-red-600 text-xs"></i>
                        Oportunidad
                    </h4>
                    <p class="text-xs text-gray-600">
                        <?php 
                        $habitaciones_baja_ocupacion = array_filter($rentabilidad, function($h) {
                            return $h['porcentaje_ocupacion'] < 50;
                        });
                        ?>
                        <strong><?= count($habitaciones_baja_ocupacion) ?> habitaciones</strong>
                        con menos del 50% de ocupación
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos
const rentabilidadData = <?= json_encode($rentabilidad) ?>;
const ocupacionTipoData = <?= json_encode($ocupacionPorTipo) ?>;
const preciosTipoData = <?= json_encode($ingresosPromedio) ?>;

// Detectar si es móvil
const isMobile = window.innerWidth < 768;

// Filtrar y preparar datos
const habitacionesConIngresos = rentabilidadData.filter(h => h.ingresos_totales > 0);
const top10Habitaciones = habitacionesConIngresos.slice(0, 10);

// Configuración global
Chart.defaults.font.size = isMobile ? 9 : 11;
Chart.defaults.font.family = 'system-ui, -apple-system, sans-serif';

// Gráfica de Top 10 Ingresos
const ctxIngresos = document.getElementById('chartIngresosHabitacion').getContext('2d');
new Chart(ctxIngresos, {
    type: 'bar',
    data: {
        labels: top10Habitaciones.map(h => h.numero),
        datasets: [{
            label: 'Ingresos',
            data: top10Habitaciones.map(h => parseFloat(h.ingresos_totales)),
            backgroundColor: top10Habitaciones.map((h, i) => {
                if (i === 0) return '#D4A574'; // Gold
                if (i === 1) return '#C0C0C0'; // Silver
                if (i === 2) return '#CD7F32'; // Bronze
                return '#93c5fd'; // Blue
            }),
            borderWidth: 0,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: isMobile ? 6 : 8,
                cornerRadius: 4,
                titleFont: { size: isMobile ? 9 : 11 },
                bodyFont: { size: isMobile ? 8 : 10 },
                callbacks: {
                    label: function(context) {
                        const hab = top10Habitaciones[context.dataIndex];
                        return [
                            'Ingresos: $' + context.parsed.y.toLocaleString('es-MX'),
                            'Reservaciones: ' + hab.total_reservaciones,
                            'Ocupación: ' + hab.porcentaje_ocupacion + '%'
                        ];
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { display: false },
                ticks: {
                    font: { size: isMobile ? 8 : 10 },
                    callback: function(value) {
                        return '$' + (value/1000).toFixed(0) + 'k';
                    }
                }
            },
            x: {
                grid: { display: false },
                ticks: { 
                    font: { size: isMobile ? 8 : 10 },
                    maxRotation: isMobile ? 45 : 0
                }
            }
        }
    }
});

// Gráfica de Distribución por Tipo
const ctxDistribucion = document.getElementById('chartDistribucionTipo').getContext('2d');
new Chart(ctxDistribucion, {
    type: 'doughnut',
    data: {
        labels: ocupacionTipoData.map(t => t.tipo),
        datasets: [{
            data: ocupacionTipoData.map(t => parseFloat(t.ingresos_totales || 0)),
            backgroundColor: [
                '#6B4423', '#D4A574', '#E5C69B', 
                '#93c5fd', '#a78bfa', '#f472b6'
            ],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: isMobile ? 5 : 10,
                    font: { size: isMobile ? 8 : 10 },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                        return data.labels.map((label, i) => {
                            const value = data.datasets[0].data[i];
                            const percentage = ((value / total) * 100).toFixed(1);
                            return {
                                text: isMobile ? label.substring(0, 10) + '... ' + percentage + '%' : label + ' (' + percentage + '%)',
                                fillStyle: data.datasets[0].backgroundColor[i],
                                hidden: false,
                                index: i
                            };
                        });
                    }
                }
            }
        }
    }
});

// Gráfica de Comparación de Precios
const ctxPrecios = document.getElementById('chartComparacionPrecios').getContext('2d');
new Chart(ctxPrecios, {
    type: 'bar',
    data: {
        labels: preciosTipoData.map(t => isMobile ? t.tipo.substring(0, 8) + '...' : t.tipo),
        datasets: [{
            label: 'Promedio',
            data: preciosTipoData.map(t => parseFloat(t.precio_promedio)),
            backgroundColor: 'rgba(212, 175, 55, 0.7)',
            borderRadius: 4
        }, {
            label: 'Mínimo',
            data: preciosTipoData.map(t => parseFloat(t.precio_minimo)),
            backgroundColor: 'rgba(147, 197, 253, 0.7)',
            borderRadius: 4
        }, {
            label: 'Máximo',
            data: preciosTipoData.map(t => parseFloat(t.precio_maximo)),
            backgroundColor: 'rgba(107, 68, 35, 0.7)',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { 
                    padding: isMobile ? 3 : 5,
                    font: { size: isMobile ? 8 : 10 },
                    usePointStyle: true
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { display: false },
                ticks: {
                    font: { size: isMobile ? 8 : 10 },
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            x: {
                grid: { display: false },
                ticks: { 
                    font: { size: isMobile ? 8 : 10 },
                    maxRotation: isMobile ? 45 : 0
                }
            }
        }
    }
});

// Gráfica de Ocupación
const ctxOcupacion = document.getElementById('chartOcupacion').getContext('2d');
new Chart(ctxOcupacion, {
    type: 'bar',
    data: {
        labels: top10Habitaciones.map(h => h.numero),
        datasets: [{
            label: '% Ocupación',
            data: top10Habitaciones.map(h => parseFloat(h.porcentaje_ocupacion)),
            backgroundColor: top10Habitaciones.map(h => {
                const pct = parseFloat(h.porcentaje_ocupacion);
                if (pct >= 80) return '#10b981';
                if (pct >= 60) return '#f59e0b';
                return '#ef4444';
            }),
            borderRadius: 4
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: {
                beginAtZero: true,
                max: 100,
                grid: { display: false },
                ticks: {
                    font: { size: isMobile ? 8 : 10 },
                    callback: function(value) {
                        return value + '%';
                    }
                }
            },
            y: {
                grid: { display: false },
                ticks: { font: { size: isMobile ? 8 : 10 } }
            }
        }
    }
});

// Actualizar gráficas cuando cambie el tamaño de ventana
window.addEventListener('resize', function() {
    const newIsMobile = window.innerWidth < 768;
    if (newIsMobile !== isMobile) {
        location.reload();
    }
});

// Funciones auxiliares
function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);
    
    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

function exportarExcel() {
    // Aquí iría la lógica para exportar a Excel
    alert('Función de exportación en desarrollo');
}

// Animaciones al cargar
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.rentables-view');
    if (view) {
        setTimeout(() => {
            view.classList.add('loaded');
        }, 50);
    }
    
    // Animar barras de ocupación
    setTimeout(() => {
        document.querySelectorAll('.ocupacion-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 300);
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>