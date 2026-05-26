<?php ?>

<!-- Estilos específicos para el reporte de procedencia - VERSIÓN RESPONSIVE -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}

.procedencia-view { opacity: 0; transition: opacity 0.3s ease; }
.procedencia-view.loaded { opacity: 1; }

/* Animaciones */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
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
.hover-grow {
    transition: all 0.2s ease;
}

/* Estilos para el SVG cargado dinámicamente */
#mapaMexicoContainer svg {
    width: 100%;
    height: 100%;
}

#mapaMexicoContainer .estado,
#mapaMexicoContainer path[data-estado] {
    stroke: #ffffff;
    stroke-width: 0.5;
    cursor: pointer;
    transition: all 0.2s ease;
    vector-effect: non-scaling-stroke;
}

#mapaMexicoContainer .estado:hover,
#mapaMexicoContainer path[data-estado]:hover {
    stroke: #6B4423;
    stroke-width: 1.5;
    filter: brightness(0.9) drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

#mapaMexicoContainer .estado-sin-datos {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Fondo del SVG */
#mapaMexicoContainer {
    background-color: #111827;
}

#mexico-map {
    background: transparent;
}

/* Ajustar colores de los estados para mejor contraste en fondo negro */
#mapaMexicoContainer .estado,
#mapaMexicoContainer path[data-estado] {
    stroke: #374151;
    stroke-width: 0.5;
}

#mapaMexicoContainer .estado:hover,
#mapaMexicoContainer path[data-estado]:hover {
    stroke: #D4A574;
    stroke-width: 1.5;
}

/* Estados sin datos en fondo negro */
#mapaMexicoContainer .estado-sin-datos {
    fill: #1f2937;
    opacity: 0.7;
}

/* Loader para fondo negro */
.mapa-loader {
    color: #9ca3af;
}

/* Loader del mapa */
.mapa-loader {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #6b7280;
}

.hover-grow:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    padding: 0.5rem;
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
}

.data-table td {
    padding: 0.375rem 0.5rem;
}

/* Badges de ranking compactos */
.rank-badge {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.688rem;
}

.rank-badge.gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
.rank-badge.silver { background: linear-gradient(135deg, #C0C0C0, #808080); color: white; }
.rank-badge.bronze { background: linear-gradient(135deg, #CD7F32, #8B4513); color: white; }
.rank-badge.regular { background: #e5e7eb; color: #6b7280; }

/* Progress bars compactos */
.state-progress {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.state-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--hotel-gold), var(--hotel-brown));
    transition: width 0.8s ease;
}

/* Ajustes para vista compacta */
.compact-header {
    padding: 0.75rem 0 !important;
}

.compact-card {
    padding: 0.75rem !important;
}

.compact-section {
    margin-bottom: 1rem !important;
}

.compact-title {
    font-size: 1rem !important;
    margin-bottom: 0.5rem !important;
}

/* Estilos para el mapa interactivo */
.estado-path {
    transition: all 0.2s ease;
}

.estado-path:hover {
    filter: brightness(1.1);
}

/* ESTILOS RESPONSIVE MEJORADOS */
@media (max-width: 768px) {
    /* Ajustes generales */
    .container {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    
    /* Header más compacto */
    .compact-header {
        padding: 0.5rem 0 !important;
    }
    
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
    .stat-card .p-3,
    .compact-card {
        padding: 0.5rem !important;
    }
    
    .stat-card .text-lg {
        font-size: 0.875rem !important;
    }
    
    .stat-card .bg-blue-100,
    .stat-card .bg-emerald-100,
    .stat-card .bg-purple-100,
    .stat-card .bg-amber-100 {
        padding: 0.375rem !important;
    }
    
    /* Iconos más pequeños */
    .stat-card i {
        font-size: 0.625rem !important;
    }
    
    /* Mapa más grande en móvil */
    #mapaMexicoContainer {
        height: 350px !important;
    }
    
    @media (min-width: 768px) {
        #mapaMexicoContainer {
            height: 500px !important;
        }
    }
    
    /* Panel lateral del mapa oculto en móvil */
    #mapaMexicoContainer .absolute.bottom-4.right-4 {
        display: none;
    }
    
    /* Tooltip del mapa más pequeño */
    #mapTooltip {
        padding: 0.5rem !important;
        font-size: 0.625rem !important;
    }
    
    /* Gráficas más pequeñas */
    #graficaEstados,
    #graficaEvolucion {
        height: 180px !important;
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
    
    /* Badges más pequeños */
    .rank-badge {
        width: 16px !important;
        height: 16px !important;
        font-size: 0.5rem !important;
    }
    
    /* Ocultar columnas en móvil */
    .data-table th:nth-child(4),
    .data-table td:nth-child(4) {
        display: none;
    }
    
    /* Barras de progreso más delgadas */
    .state-progress {
        height: 2px !important;
    }
    
    /* Botones más compactos */
    button {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.625rem !important;
    }
    
    /* Botones de período aún más pequeños */
    .px-2.py-1 {
        padding: 0.25rem 0.375rem !important;
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
        max-height: 200px !important;
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
        content: "Procedencia";
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
        .hover-grow:hover {
            transform: none;
            box-shadow: none;
        }
        
        .data-table tbody tr:hover {
            background: transparent;
        }
    }
}

/* Orientación landscape en móviles */
@media (max-width: 768px) and (orientation: landscape) {
    #mapaMexicoContainer {
        height: 300px !important;
    }
    
    #graficaEstados,
    #graficaEvolucion {
        height: 150px !important;
    }
}

/* Print styles */
@media print {
    .procedencia-view {
        opacity: 1 !important;
    }
    
    button {
        display: none !important;
    }
    
    .shadow-sm,
    .shadow-lg {
        box-shadow: none !important;
    }
}
</style>

<div class="procedencia-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-1 md:py-2">
    <!-- Header del Reporte - ULTRA COMPACTO PARA MÓVIL -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-lg">
        <div class="container mx-auto px-3 md:px-4 py-1.5 md:py-2 compact-header">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-1 md:gap-2">
                <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
                    <div class="flex items-center gap-1.5">
                        <a href="<?= url('reportes') ?>" 
                           class="bg-white/10 backdrop-blur hover:bg-white/20 p-1 md:p-1.5 rounded transition-colors">
                            <i class="fas fa-arrow-left text-xs md:text-sm"></i>
                        </a>
                        <h1 class="text-sm md:text-lg font-bold font-playfair flex items-center gap-1 md:gap-2">
                            <i class="fas fa-map-marked-alt text-xs md:text-base opacity-80 hidden sm:inline"></i>
                            <span class="hidden md:inline">Procedencia Geográfica de Huéspedes</span>
                            <span class="md:hidden">Procedencia</span>
                        </h1>
                    </div>
                    <span class="text-hotel-gold text-xs ml-1 md:ml-2">
                        <?= date('d/m', strtotime($fecha_inicio)) ?> - <?= date('d/m/y', strtotime($fecha_fin)) ?>
                    </span>
                </div>
                
                
            </div>
        </div>
    </div>

    <div class="container mx-auto px-3 md:px-4 py-2 md:py-3 max-w-7xl">
        <!-- Filtros Ultra Compactos para Móvil -->
        <div class="bg-white rounded-lg shadow-sm p-2 md:p-3 mb-2 md:mb-3 border border-gray-100 compact-section">
            <form method="get" action="<?= url('reportes/procedencia') ?>" class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-2 md:gap-3">
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
                        <i class="fas fa-sync-alt text-xs"></i>
                        <span>Actualizar</span>
                    </button>
                    
                    <!-- Períodos Rápidos - Grid en móvil -->
                    <div class="grid grid-cols-4 gap-1 flex-1 sm:flex sm:gap-1">
                        <button type="button" onclick="setPeriodo(30)" class="px-1.5 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition-all text-center">
                            1M
                        </button>
                        <button type="button" onclick="setPeriodo(90)" class="px-1.5 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition-all text-center">
                            3M
                        </button>
                        <button type="button" onclick="setPeriodo(180)" class="px-1.5 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition-all text-center">
                            6M
                        </button>
                        <button type="button" onclick="setPeriodo(365)" class="px-1.5 py-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-xs transition-all text-center">
                            1A
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Cards de Estadísticas - ULTRA COMPACTAS PARA MÓVIL -->
        <?php 
        $totalHuespedes = array_sum(array_column($porEstado, 'total_huespedes'));
        $totalReservaciones = array_sum(array_column($porEstado, 'total_reservaciones'));
        $totalIngresos = array_sum(array_column($porEstado, 'ingresos_totales'));
        $promedioEstancia = $totalReservaciones > 0 ? round($totalHuespedes / $totalReservaciones, 1) : 0;
        ?>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-2 md:gap-3 mb-2 md:mb-3 compact-section">
            <!-- Total Huéspedes -->
            <div class="stat-card hover-grow bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3 compact-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-blue-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-users text-blue-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-sm md:text-lg font-bold text-gray-800"><?= number_format($totalHuespedes) ?></span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Total Huéspedes</h3>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <?= count($porEstado) ?> estados
                    </p>
                </div>
            </div>

            <!-- Total Reservaciones -->
            <div class="stat-card hover-grow bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3 compact-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-emerald-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-calendar-check text-emerald-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-sm md:text-lg font-bold text-gray-800"><?= number_format($totalReservaciones) ?></span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Reservaciones</h3>
                    <p class="text-xs text-gray-600 mt-0.5">
                        <?= $promedioEstancia ?> huésp/res
                    </p>
                </div>
            </div>

            <!-- Ingresos Totales -->
            <div class="stat-card hover-grow bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3 compact-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-purple-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-dollar-sign text-purple-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-xs md:text-base font-bold text-gray-800 truncate"><?= format_currency($totalIngresos) ?></span>
                    </div>
                    <h3 class="text-xs font-medium text-gray-500 uppercase truncate">Ingresos</h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        <?= format_currency($totalIngresos / max($totalHuespedes, 1)) ?>/huésp
                    </p>
                </div>
            </div>

            <!-- Top Estado -->
            <div class="stat-card hover-grow bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-2 md:p-3 compact-card">
                    <div class="flex items-center justify-between mb-1">
                        <div class="bg-amber-100 p-1.5 md:p-2 rounded">
                            <i class="fas fa-trophy text-amber-600 text-xs md:text-sm"></i>
                        </div>
                        <span class="text-xs font-bold text-amber-700 bg-amber-100 px-1 py-0.5 rounded">
                            #1
                        </span>
                    </div>
                    <h3 class="text-xs md:text-sm font-bold text-gray-800 truncate"><?= $porEstado[0]['estado'] ?? 'N/A' ?></h3>
                    <p class="text-xs text-gray-600 mt-0.5 truncate">
                        <?= isset($porEstado[0]) ? number_format($porEstado[0]['total_huespedes']) . ' huésp' : 'Sin datos' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Mapa Real de México con SVG externo - RESPONSIVE -->
        <div class="bg-white rounded-lg shadow-sm p-2 md:p-3 mb-2 md:mb-3 border border-gray-100 compact-section">
            <div class="flex items-center justify-between mb-1.5 md:mb-2">
                <h2 class="text-sm md:text-base font-bold text-gray-800 flex items-center gap-1 md:gap-2 compact-title">
                    <i class="fas fa-map-marked-alt text-hotel-brown text-xs md:text-sm"></i>
                    <span class="hidden sm:inline">Distribución Geográfica de Huéspedes</span>
                    <span class="sm:hidden">Mapa de Huéspedes</span>
                </h2>
                <div class="hidden md:flex items-center gap-3 text-xs">
                    <span class="text-gray-500">Intensidad:</span>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400">0%</span>
                        <div class="flex gap-0.5">
                            <div class="w-3 h-3 rounded" style="background: #e5e7eb"></div>
                            <div class="w-3 h-3 rounded" style="background: #dbeafe"></div>
                            <div class="w-3 h-3 rounded" style="background: #93c5fd"></div>
                            <div class="w-3 h-3 rounded" style="background: #3b83bd"></div>
                            <div class="w-3 h-3 rounded" style="background: #84d3b2"></div>
                            <div class="w-3 h-3 rounded" style="background: #008f39"></div>
                        </div>
                        <span class="text-gray-600 font-medium">Max</span>
                    </div>
                </div>
            </div>
            
            <div class="relative bg-gray-900 rounded-lg p-2 md:p-4" style="height: 350px; md:height: 500px;">
                <!-- Contenedor para el SVG que se cargará -->
                <div id="mapaMexicoContainer" class="w-full h-full" style="max-height: 100%;">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-gray-500 text-xs md:text-sm">
                            <i class="fas fa-spinner fa-spin mr-1 md:mr-2"></i>Cargando mapa...
                        </div>
                    </div>
                </div>
                
                <!-- Tooltip flotante -->
                <div id="mapTooltip" class="absolute opacity-0 pointer-events-none bg-gray-900 text-white p-2 md:p-3 rounded-lg shadow-lg text-xs transition-opacity duration-200" style="z-index: 1000;">
                    <div class="font-bold text-xs md:text-sm mb-0.5 md:mb-1" id="tooltipEstado"></div>
                    <div id="tooltipContent" class="text-xs"></div>
                </div>
                
                <!-- Panel lateral con Top 5 - Solo desktop -->
                <div class="hidden lg:block absolute top-4 right-4 bg-white/95 backdrop-blur rounded-lg shadow-lg p-3 text-xs" style="width: 180px;">
                    <h4 class="font-bold text-gray-800 mb-2 text-sm flex items-center gap-1">
                        <i class="fas fa-trophy text-amber-500"></i>
                        Top 5 Estados
                    </h4>
                    <?php 
                    $top5 = array_slice($porEstado, 0, 5);
                    foreach($top5 as $index => $estado): 
                        $porcentaje = round(($estado['total_huespedes'] / $totalHuespedes) * 100, 1);
                    ?>
                    <div class="mb-2 pb-2 <?= $index < 4 ? 'border-b border-gray-100' : '' ?>">
                        <div class="flex items-center justify-between mb-0.5">
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold <?= $index === 0 ? 'text-amber-600' : ($index === 1 ? 'text-gray-500' : ($index === 2 ? 'text-orange-600' : 'text-gray-400')) ?>">
                                    <?= $index + 1 ?>
                                </span>
                                <span class="text-gray-700 font-medium">
                                    <?= $estado['estado'] ?>
                                </span>
                            </div>
                            <span class="font-bold text-gray-900"><?= $porcentaje ?>%</span>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?= number_format($estado['total_huespedes']) ?> huéspedes
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Top 5 móvil - Debajo del mapa -->
            <div class="lg:hidden mt-2 bg-gray-50 rounded p-2">
                <h4 class="font-bold text-gray-800 mb-1.5 text-xs flex items-center gap-1">
                    <i class="fas fa-trophy text-amber-500 text-xs"></i>
                    Top 5 Estados
                </h4>
                <div class="grid grid-cols-1 gap-1">
                    <?php foreach($top5 as $index => $estado): 
                        $porcentaje = round(($estado['total_huespedes'] / $totalHuespedes) * 100, 1);
                    ?>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1">
                            <span class="font-bold <?= $index === 0 ? 'text-amber-600' : ($index === 1 ? 'text-gray-500' : ($index === 2 ? 'text-orange-600' : 'text-gray-400')) ?>">
                                <?= $index + 1 ?>
                            </span>
                            <span class="text-gray-700">
                                <?= $estado['estado'] ?>
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold"><?= $porcentaje ?>%</span>
                            <span class="text-gray-500 ml-1">(<?= number_format($estado['total_huespedes']) ?>)</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Gráfica de Top 10 Estados - COMPACTA Y RESPONSIVE -->
        <div class="bg-white rounded-lg shadow-sm p-2 md:p-3 mb-2 md:mb-3 border border-gray-100 compact-section">
            <div class="flex items-center justify-between mb-1.5 md:mb-2">
                <h2 class="text-sm md:text-base font-bold text-gray-800 flex items-center gap-1 md:gap-2 compact-title">
                    <i class="fas fa-chart-bar text-hotel-brown text-xs md:text-sm"></i>
                    Top 10 Estados
                </h2>
                <span class="text-xs text-gray-500">Por huéspedes</span>
            </div>
            <div class="relative" style="height: 180px; md:height: 250px;">
                <canvas id="graficaEstados"></canvas>
            </div>
        </div>

        <!-- Tabla de Estados - Ancho completo -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden mb-2 md:mb-3">
            <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark px-3 md:px-4 py-1.5 md:py-2">
                <h3 class="text-xs md:text-sm font-bold text-white flex items-center gap-1">
                    <i class="fas fa-list-ol text-xs"></i>
                    Ranking por Estados
                </h3>
            </div>
            <div class="p-2 md:p-3">
                <div class="overflow-x-auto" style="max-height: 400px; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="rounded-tl text-center" style="width: 30px">#</th>
                                <th>Estado</th>
                                <th class="text-center">Huéspedes</th>
                                <th class="text-center">Reservaciones</th>
                                <th class="text-right">Ingresos</th>
                                <th class="text-center rounded-tr" style="width: 80px">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($porEstado as $index => $estado): ?>
                            <?php 
                            $porcentaje = round(($estado['total_huespedes'] / $totalHuespedes) * 100, 1);
                            $rankClass = $index === 0 ? 'gold' : ($index === 1 ? 'silver' : ($index === 2 ? 'bronze' : 'regular'));
                            ?>
                            <tr>
                                <td class="text-center">
                                    <span class="rank-badge <?= $rankClass ?>">
                                        <?= $index + 1 ?>
                                    </span>
                                </td>
                                <td class="font-medium text-gray-800">
                                    <div class="flex items-center gap-1">
                                        <i class="fas fa-map-marker-alt text-hotel-brown text-xs"></i>
                                        <span class="text-xs md:text-sm"><?= htmlspecialchars($estado['estado']) ?></span>
                                    </div>
                                </td>
                                <td class="text-center font-semibold text-xs md:text-sm">
                                    <?= number_format($estado['total_huespedes']) ?>
                                </td>
                                <td class="text-center text-gray-600 text-xs md:text-sm">
                                    <?= number_format($estado['total_reservaciones']) ?>
                                </td>
                                <td class="text-right font-semibold text-emerald-700 text-xs md:text-sm">
                                    <span class="hidden sm:inline"><?= format_currency($estado['ingresos_totales']) ?></span>
                                    <span class="sm:hidden">
                                        <?php 
                                        $amount = $estado['ingresos_totales'];
                                        if ($amount >= 1000000) {
                                            echo '$' . number_format($amount / 1000000, 1) . 'M';
                                        } elseif ($amount >= 1000) {
                                            echo '$' . number_format($amount / 1000, 0) . 'k';
                                        } else {
                                            echo format_currency($amount);
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="text-xs font-medium"><?= $porcentaje ?>%</div>
                                    <div class="state-progress hidden sm:block">
                                        <div class="state-progress-fill" style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Gráfica de Evolución Mensual - COMPACTA Y RESPONSIVE -->
        <div class="bg-white rounded-lg shadow-sm p-2 md:p-3 border border-gray-100">
            <div class="flex items-center justify-between mb-1.5 md:mb-2">
                <h2 class="text-sm md:text-base font-bold text-gray-800 flex items-center gap-1 md:gap-2 compact-title">
                    <i class="fas fa-chart-line text-hotel-brown text-xs md:text-sm"></i>
                    <span class="hidden sm:inline">Evolución Mensual - Top 5 Estados</span>
                    <span class="sm:hidden">Evolución Top 5</span>
                </h2>
                <span class="text-xs text-gray-500 hidden sm:inline">Tendencia mensual</span>
            </div>
            <div class="relative" style="height: 180px; md:height: 250px;">
                <canvas id="graficaEvolucion"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Datos
const topEstados = <?= json_encode($topEstados) ?>;
const evolucion = <?= json_encode($evolucionMensual) ?>;

// Configuración de colores
const coloresGradiente = [
    { border: '#D4A574', bg: 'rgba(212, 175, 55, 0.1)' },
    { border: '#6B4423', bg: 'rgba(107, 68, 35, 0.1)' },
    { border: '#3B82F6', bg: 'rgba(59, 130, 246, 0.1)' },
    { border: '#10B981', bg: 'rgba(16, 185, 129, 0.1)' },
    { border: '#8B5CF6', bg: 'rgba(139, 92, 246, 0.1)' }
];

// Detectar si es móvil
const isMobile = window.innerWidth < 768;

// Gráfica de barras - Top 10 Estados
const ctxEstados = document.getElementById('graficaEstados').getContext('2d');
const gradienteEstados = ctxEstados.createLinearGradient(0, 0, 0, 250);
gradienteEstados.addColorStop(0, '#3b83bd');
gradienteEstados.addColorStop(1, '#3c84bd');

new Chart(ctxEstados, {
    type: 'bar',
    data: {
        labels: topEstados.map(e => e.estado),
        datasets: [{
            label: 'Número de Huéspedes',
            data: topEstados.map(e => e.total_huespedes),
            backgroundColor: gradienteEstados,
            borderColor: '#6B4423',
            borderWidth: 1,
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: isMobile ? 6 : 8,
                cornerRadius: 4,
                titleFont: { size: isMobile ? 9 : 11 },
                bodyFont: { size: isMobile ? 8 : 10 },
                callbacks: {
                    label: function(context) {
                        const estado = topEstados[context.dataIndex];
                        return [
                            `Huéspedes: ${estado.total_huespedes.toLocaleString()}`,
                            `Ingresos: ${new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(estado.ingresos_totales)}`
                        ];
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    padding: 5,
                    font: { size: isMobile ? 8 : 10 },
                    callback: function(value) {
                        return value.toLocaleString();
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    padding: 5,
                    font: { size: isMobile ? 8 : 10 },
                    maxRotation: 45,
                    minRotation: 45
                }
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeOutQuart'
        }
    }
});

// Gráfica de evolución mensual
const ctxEvolucion = document.getElementById('graficaEvolucion').getContext('2d');

// Procesar datos para la gráfica
const meses = [...new Set(evolucion.map(e => e.mes))];
const estadosTop5 = <?= json_encode(array_slice(array_column($topEstados, 'estado'), 0, 5)) ?>;

const datasets = estadosTop5.map((estado, index) => {
    return {
        label: estado,
        data: meses.map(mes => {
            const dato = evolucion.find(e => e.mes === mes && e.estado === estado);
            return dato ? dato.total_huespedes : 0;
        }),
        borderColor: coloresGradiente[index].border,
        backgroundColor: coloresGradiente[index].bg,
        tension: 0.3,
        borderWidth: isMobile ? 1.5 : 2,
        pointRadius: isMobile ? 2 : 3,
        pointHoverRadius: isMobile ? 3 : 5,
        pointBackgroundColor: coloresGradiente[index].border,
        pointBorderColor: '#fff',
        pointBorderWidth: 1
    };
});

new Chart(ctxEvolucion, {
    type: 'line',
    data: {
        labels: meses.map(mes => {
            const [year, month] = mes.split('-');
            const date = new Date(year, month - 1);
            return isMobile 
                ? date.toLocaleDateString('es-MX', { month: 'short' })
                : date.toLocaleDateString('es-MX', { month: 'short', year: 'numeric' });
        }),
        datasets: datasets
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: isMobile ? 5 : 10,
                    usePointStyle: true,
                    font: { size: isMobile ? 8 : 10 },
                    boxWidth: isMobile ? 4 : 6
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: isMobile ? 6 : 8,
                cornerRadius: 4,
                titleFont: { size: isMobile ? 9 : 11 },
                bodyFont: { size: isMobile ? 8 : 10 },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toLocaleString() + ' huéspedes';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                },
                ticks: {
                    padding: 5,
                    font: { size: isMobile ? 8 : 10 },
                    callback: function(value) {
                        return isMobile ? value/1000 + 'k' : value.toLocaleString();
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    padding: 5,
                    font: { size: isMobile ? 8 : 10 }
                }
            }
        }
    }
});

// Actualizar gráficas cuando cambie el tamaño de ventana
window.addEventListener('resize', function() {
    const newIsMobile = window.innerWidth < 768;
    if (newIsMobile !== isMobile) {
        location.reload(); // Recargar para ajustar las gráficas
    }
});

// Funciones auxiliares
function exportarPDF() {
    const url = '<?= url('reportes/exportar-pdf') ?>?tipo=procedencia' +
                '&fecha_inicio=<?= $fecha_inicio ?>' +
                '&fecha_fin=<?= $fecha_fin ?>';
    window.open(url, '_blank');
}

function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);
    
    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

// Función helper para formato de moneda en móvil
function formatCurrencyCompact(amount) {
    if (amount >= 1000000) {
        return '$' + (amount / 1000000).toFixed(1) + 'M';
    } else if (amount >= 1000) {
        return '$' + (amount / 1000).toFixed(0) + 'k';
    }
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Datos del mapa
const datosEstados = <?= json_encode(array_combine(
    array_column($porEstado, 'estado'),
    $porEstado
)) ?>;
const totalHuespedesGlobal = <?= $totalHuespedes ?>;

// Función para obtener el color según la cantidad de huéspedes
function getColorForEstado(huespedes) {
    const porcentaje = (huespedes / totalHuespedesGlobal) * 100;
    if (porcentaje > 20) return '#008f39';
    if (porcentaje > 15) return '#84d3b2';
    if (porcentaje > 10) return '#3b83bd';
    if (porcentaje > 5) return '#7dd3fc';
    if (porcentaje > 2) return '#bae6fd';
    if (porcentaje > 0) return '#f0f9ff';
    return '#e5e7eb';
}

// Función mejorada para cargar e inicializar el mapa SVG
function initMapaSVG() {
    const svgUrl = '<?= url("img/mexico-states.svg") ?>';
    
    console.log('Cargando mapa desde:', svgUrl);
    
    fetch(svgUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(svgContent => {
            const container = document.getElementById('mapaMexicoContainer');
            container.innerHTML = svgContent;
            
            const svgElement = container.querySelector('svg');
            if (svgElement) {
                svgElement.setAttribute('id', 'mapaMexico');
                svgElement.setAttribute('class', 'w-full h-full');
                svgElement.setAttribute('preserveAspectRatio', 'xMidYMid meet');
                
                if (!svgElement.hasAttribute('viewBox')) {
                    const bbox = svgElement.getBBox();
                    svgElement.setAttribute('viewBox', `${bbox.x} ${bbox.y} ${bbox.width} ${bbox.height}`);
                }
                
                const paths = svgElement.querySelectorAll('path');
                paths.forEach(path => {
                    if (!path.hasAttribute('data-estado') && path.id) {
                        const estado = obtenerNombreEstado(path.id);
                        if (estado) {
                            path.setAttribute('data-estado', estado);
                        }
                    }
                    
                    if (path.hasAttribute('data-estado')) {
                        path.classList.add('estado');
                    }
                });
                
                setTimeout(() => {
                    initMapaFunctionality();
                }, 100);
            } else {
                throw new Error('No se encontró elemento SVG en el contenido cargado');
            }
        })
        .catch(error => {
            console.error('Error al cargar el mapa:', error);
            document.getElementById('mapaMexicoContainer').innerHTML = `
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl md:text-2xl mb-2"></i>
                        <p class="text-gray-600 text-xs md:text-sm">Error al cargar el mapa</p>
                        <p class="text-xs text-gray-500 mt-1">${error.message}</p>
                    </div>
                </div>
            `;
        });
}

// Función para convertir IDs a nombres de estados
function obtenerNombreEstado(id) {
    const mapaIds = {
        'MX-AGU': 'Aguascalientes',
        'MX-BCN': 'Baja California',
        'MX-BCS': 'Baja California Sur',
        'MX-CAM': 'Campeche',
        'MX-CHP': 'Chiapas',
        'MX-CHH': 'Chihuahua',
        'MX-CMX': 'Ciudad de México',
        'MX-COA': 'Coahuila',
        'MX-COL': 'Colima',
        'MX-DUR': 'Durango',
        'MX-MEX': 'México',
        'MX-GUA': 'Guanajuato',
        'MX-GRO': 'Guerrero',
        'MX-HID': 'Hidalgo',
        'MX-JAL': 'Jalisco',
        'MX-MIC': 'Michoacán',
        'MX-MOR': 'Morelos',
        'MX-NAY': 'Nayarit',
        'MX-NLE': 'Nuevo León',
        'MX-OAX': 'Oaxaca',
        'MX-PUE': 'Puebla',
        'MX-QUE': 'Querétaro',
        'MX-ROO': 'Quintana Roo',
        'MX-SLP': 'San Luis Potosí',
        'MX-SIN': 'Sinaloa',
        'MX-SON': 'Sonora',
        'MX-TAB': 'Tabasco',
        'MX-TAM': 'Tamaulipas',
        'MX-TLA': 'Tlaxcala',
        'MX-VER': 'Veracruz',
        'MX-YUC': 'Yucatán',
        'MX-ZAC': 'Zacatecas'
    };
    
    return mapaIds[id] || null;
}

// Función para inicializar la funcionalidad del mapa
function initMapaFunctionality() {
    const estados = document.querySelectorAll('.estado, path[data-estado]');
    const tooltip = document.getElementById('mapTooltip');
    const tooltipEstado = document.getElementById('tooltipEstado');
    const tooltipContent = document.getElementById('tooltipContent');
    
    console.log('Estados encontrados:', estados.length);
    
    const mapeoNombres = {
        'Estado de México': 'México',
        'CDMX': 'Ciudad de México',
        'Mexico City': 'Ciudad de México',
        'Mexico State': 'México'
    };
    
    estados.forEach(estado => {
        let nombreEstado = estado.getAttribute('data-estado');
        nombreEstado = mapeoNombres[nombreEstado] || nombreEstado;
        
        const datosDelEstado = datosEstados[nombreEstado];
        
        if (datosDelEstado && datosDelEstado.total_huespedes > 0) {
            const porcentaje = (datosDelEstado.total_huespedes / totalHuespedesGlobal) * 100;
            estado.style.fill = getColorByPercentage2(porcentaje);
            
            // Eventos táctiles para móvil
            if ('ontouchstart' in window) {
                estado.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    mostrarTooltipMobile(nombreEstado, datosDelEstado, porcentaje);
                });
            } else {
                // Eventos de mouse para desktop
                estado.addEventListener('mouseenter', function(e) {
                    tooltipEstado.textContent = nombreEstado;
                    tooltipContent.innerHTML = `
                        <div>Huéspedes: ${datosDelEstado.total_huespedes.toLocaleString()}</div>
                        <div>Reservaciones: ${datosDelEstado.total_reservaciones.toLocaleString()}</div>
                        <div>Ingresos: ${formatCurrency(datosDelEstado.ingresos_totales)}</div>
                        <div class="font-bold text-yellow-300 mt-1">${porcentaje.toFixed(1)}% del total</div>
                    `;
                    
                    tooltip.style.opacity = '1';
                    tooltip.style.display = 'block';
                    
                    const rect = this.getBoundingClientRect();
                    const containerRect = document.getElementById('mapaMexicoContainer').getBoundingClientRect();
                    
                    tooltip.style.left = (rect.left - containerRect.left + rect.width/2 - tooltip.offsetWidth/2) + 'px';
                    tooltip.style.top = (rect.top - containerRect.top - tooltip.offsetHeight - 10) + 'px';
                    
                    const tooltipRect = tooltip.getBoundingClientRect();
                    if (tooltipRect.left < containerRect.left) {
                        tooltip.style.left = '10px';
                    } else if (tooltipRect.right > containerRect.right) {
                        tooltip.style.left = (containerRect.width - tooltip.offsetWidth - 10) + 'px';
                    }
                });
                
                estado.addEventListener('mouseleave', function() {
                    tooltip.style.opacity = '0';
                    setTimeout(() => {
                        if (tooltip.style.opacity === '0') {
                            tooltip.style.display = 'none';
                        }
                    }, 200);
                });
            }
            
            estado.addEventListener('click', function() {
                mostrarDetalleEstado(nombreEstado, datosDelEstado);
            });
        } else {
            estado.style.fill = '#e5e7eb';
            estado.classList.add('estado-sin-datos');
            
            if ('ontouchstart' in window) {
                estado.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    mostrarTooltipMobile(nombreEstado || 'Estado desconocido', null, 0);
                });
            }
        }
    });
    
    console.log('Mapa inicializado con', estados.length, 'estados');
}

// Función para mostrar tooltip en móvil
function mostrarTooltipMobile(nombreEstado, datos, porcentaje) {
    if (datos) {
        Swal.fire({
            title: nombreEstado,
            html: `
                <div class="text-left text-sm">
                    <p class="mb-1"><strong>Huéspedes:</strong> ${datos.total_huespedes.toLocaleString()}</p>
                    <p class="mb-1"><strong>Reservaciones:</strong> ${datos.total_reservaciones.toLocaleString()}</p>
                    <p class="mb-1"><strong>Ingresos:</strong> ${formatCurrency(datos.ingresos_totales)}</p>
                    <p class="font-bold text-amber-600">${porcentaje.toFixed(1)}% del total</p>
                </div>
            `,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            toast: true,
            position: 'center',
            width: '250px'
        });
    } else {
        Swal.fire({
            title: nombreEstado,
            text: 'Sin datos disponibles',
            icon: 'info',
            showConfirmButton: false,
            timer: 2000,
            toast: true,
            position: 'center',
            width: '200px'
        });
    }
}

// Función auxiliar para formatear moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

function getColorByPercentage2(porcentaje) {
    if (porcentaje > 20) return '#008f39';
    if (porcentaje > 15) return '#84d3b2';
    if (porcentaje > 10) return '#3b83bd';
    if (porcentaje > 5) return '#7dd3fc';
    if (porcentaje > 2) return '#93c5fd';
    if (porcentaje > 0) return '#dbeafe';
    return '#e5e7eb';
}

// Función para mostrar detalles del estado
function mostrarDetalleEstado(nombreEstado, datos) {
    Swal.fire({
        title: nombreEstado,
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Total de huéspedes:</strong> ${datos.total_huespedes.toLocaleString()}</p>
                <p class="mb-2"><strong>Reservaciones:</strong> ${datos.total_reservaciones.toLocaleString()}</p>
                <p class="mb-2"><strong>Ingresos totales:</strong> ${formatCurrency(datos.ingresos_totales)}</p>
                <p class="mb-2"><strong>Porcentaje del total:</strong> ${((datos.total_huespedes / totalHuespedesGlobal) * 100).toFixed(1)}%</p>
                <p><strong>Promedio por reservación:</strong> ${(datos.total_huespedes / datos.total_reservaciones).toFixed(1)} huéspedes</p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#6B4423',
        confirmButtonText: 'Cerrar',
        width: window.innerWidth < 768 ? '90%' : '500px'
    });
}

// Animaciones al cargar
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.procedencia-view');
    if (view) {
        setTimeout(() => {
            view.classList.add('loaded');
        }, 50);
    }
    
    // Animar progress bars
    setTimeout(() => {
        document.querySelectorAll('.state-progress-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 300);
    
    // Inicializar mapa SVG
    initMapaSVG();
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>