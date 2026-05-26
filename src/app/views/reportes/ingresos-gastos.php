<?php include __DIR__ . '/../layout/header.php'; ?>
<?php  
// Necesitamos que el controlador nos pase también los datos por método de pago
// Esto debería venir del ReportesController
$metodosPago = $metodosPago ?? [
    'efectivo' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
    'tarjeta' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0],
    'transferencia' => ['ingresos' => 0, 'gastos' => 0, 'balance' => 0]
];
?>

<!-- Estilos específicos para el reporte -->
<style>
:root {
    --hotel-green: #5C7A4E;
    --hotel-green-dark: #4A6340;
    --hotel-green-light: #7A9B6A;
    --hotel-gold: #C8A96A;
    --hotel-cream: #F7F4EE;
    --hotel-brown: #5C7A4E;
    --hotel-brown-dark: #4A6340;
}

.reporte-view { opacity: 0; transition: opacity 0.3s ease; }
.reporte-view.loaded { opacity: 1; }

/* Animaciones */
@keyframes slideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.stat-card {
    animation: slideIn 0.5s ease forwards;
    opacity: 0;
}

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }

/* Efectos hover para cards */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(92,122,78,0.12);
}

/* Tablas estilizadas */
.styled-table {
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.styled-table th {
    background: linear-gradient(135deg, var(--hotel-green), var(--hotel-green-dark));
    color: white;
    font-weight: 600;
    text-align: left;
    padding: 1rem;
    position: sticky;
    top: 0;
    z-index: 10;
}

.styled-table tbody tr {
    transition: all 0.2s ease;
}

.styled-table tbody tr:hover {
    background-color: #f0f4ee;
    transform: scale(1.01);
    box-shadow: 0 2px 5px rgba(92,122,78,0.08);
}

.styled-table tbody tr:nth-child(even) {
    background-color: #f8faf6;
}

/* Indicadores de porcentaje */
.percentage-bar {
    height: 4px;
    background-color: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-top: 4px;
}

.percentage-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--hotel-gold), var(--hotel-green));
    transition: width 1s ease;
}

/* Botones de período rápido */
.period-btn {
    position: relative;
    overflow: hidden;
    background-color: #eef2eb !important;
    color: #4A6340 !important;
    border: 1px solid #d4dece;
    transition: all 0.2s ease;
}

.period-btn:hover {
    background-color: #dce8d5 !important;
    border-color: #5C7A4E;
}

.period-btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.period-btn:hover::before {
    width: 300px;
    height: 300px;
}

/* Animación para números */
@keyframes countUp {
    from { opacity: 0; }
    to { opacity: 1; }
}

.count-up {
    animation: countUp 0.5s ease forwards;
}

/* Estilos para botones de toggle de datasets */
.dataset-toggle-btn {
    position: relative;
    transition: all 0.3s ease;
}

.dataset-toggle-btn.dataset-hidden {
    opacity: 0.6;
    background-color: #f3f4f6 !important;
    color: #9ca3af !important;
}

.dataset-toggle-btn.dataset-hidden::after {
    content: '';
    position: absolute;
    left: 10%;
    right: 10%;
    top: 50%;
    height: 2px;
    background-color: currentColor;
    transform: rotate(-5deg);
    pointer-events: none;
}

.dataset-toggle-btn .toggle-icon {
    transition: transform 0.3s ease;
}

.dataset-toggle-btn.dataset-hidden .toggle-icon {
    transform: rotate(45deg);
}

/* Filtro card con toque crema */
.filter-card {
    background: #FDFCF9;
    border: 1px solid #E8E2D6;
}

/* Inputs con estilo Los Cedros */
input[type="date"]:focus,
select:focus {
    border-color: var(--hotel-green) !important;
    box-shadow: 0 0 0 3px rgba(92,122,78,0.15) !important;
    outline: none;
}

/* Estilos Responsive Mejorados */
@media (max-width: 768px) {
    /* Ajuste de padding general más agresivo */
    .container {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
    
    /* Header super compacto */
    .font-playfair {
        font-size: 1.125rem !important;
        line-height: 1.2 !important;
    }
    
    /* Cards de resumen - diseño más compacto */
    .stat-card {
        margin-bottom: 0.75rem;
    }
    
    .stat-card .p-4,
    .stat-card .p-6 {
        padding: 0.75rem !important;
    }
    
    .stat-card .text-2xl,
    .stat-card .text-3xl {
        font-size: 1.25rem !important;
        line-height: 1.2 !important;
    }
    
    .stat-card .bg-white\/20 {
        padding: 0.5rem !important;
    }
    
    .stat-card .text-xl,
    .stat-card .text-2xl {
        font-size: 1rem !important;
    }
    
    /* Texto más pequeño en cards */
    .stat-card .text-xs {
        font-size: 0.65rem !important;
    }
    
    .stat-card .text-sm {
        font-size: 0.75rem !important;
    }
    
    /* Tablas super responsive */
    .styled-table {
        font-size: 0.75rem;
    }
    
    .styled-table th,
    .styled-table td {
        padding: 0.375rem !important;
        white-space: nowrap;
    }
    
    /* Simplificar tablas en móvil */
    .styled-table th:nth-child(2),
    .styled-table td:nth-child(2),
    .styled-table tfoot td:nth-child(2) {
        display: none;
    }
    
    /* Barras de porcentaje ocultas en móvil */
    .percentage-bar {
        display: none !important;
    }
    
    /* Hacer tablas más compactas */
    .table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Botones de período super compactos */
    .period-btn {
        padding: 0.375rem 0.5rem !important;
        font-size: 0.65rem !important;
        white-space: nowrap;
        background-color: #eef2eb !important;
        color: #4A6340 !important;
    }
    
    /* Filtros de fecha más compactos */
    .flex-1.min-w-full {
        min-width: 100% !important;
    }
    
    /* Labels más pequeños */
    label {
        font-size: 0.75rem !important;
    }
    
    /* Gráfica optimizada para móvil */
    #graficaEvolucion {
        height: 200px !important;
    }
    
    /* Botones de toggle más pequeños */
    .dataset-toggle-btn {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.65rem !important;
    }
    
    /* Insights más compactos */
    .grid.md\:grid-cols-2 {
        grid-template-columns: 1fr !important;
        gap: 0.5rem !important;
    }
    
    /* Cards de insights más pequeñas */
    .bg-white\/80.backdrop-blur {
        padding: 0.75rem !important;
    }
    
    /* Modal más compacta */
    .max-w-md {
        max-width: calc(100vw - 1rem) !important;
    }
    
    /* Desglose métodos de pago más compacto */
    .border.rounded-lg.p-3,
    .border.rounded-lg.p-4 {
        padding: 0.75rem !important;
    }
    
    /* Íconos más pequeños */
    .text-lg {
        font-size: 0.875rem !important;
    }
    
    .text-xl {
        font-size: 1rem !important;
    }
    
    /* Espaciados reducidos */
    .gap-4 {
        gap: 0.5rem !important;
    }
    
    .gap-6 {
        gap: 0.75rem !important;
    }
    
    .mb-4 {
        margin-bottom: 0.75rem !important;
    }
    
    .mb-6 {
        margin-bottom: 1rem !important;
    }
    
    /* Botones de acción principales más compactos */
    .px-4.py-2\.5 {
        padding: 0.5rem 0.75rem !important;
    }
    
    /* Hover effects deshabilitados en móvil */
    @media (hover: none) {
        .hover-lift:hover {
            transform: none;
            box-shadow: none;
        }
        
        .styled-table tbody tr:hover {
            transform: none;
            background-color: inherit;
        }
    }
}

/* Tablets (768px - 1024px) */
@media (min-width: 768px) and (max-width: 1024px) {
    .container {
        max-width: 100% !important;
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }
    
    /* Grid de 2 columnas para tablets */
    .grid.lg\:grid-cols-3 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .grid.lg\:grid-cols-3 > :last-child {
        grid-column: span 2;
    }
}

/* Mejoras de accesibilidad móvil */
@media (max-width: 640px) {
    /* Botones con altura mínima para toque */
    button {
        min-height: 40px;
    }
    
    /* Inputs más grandes */
    input[type="date"],
    select {
        min-height: 40px;
        font-size: 16px; /* Previene zoom en iOS */
    }
    
    /* Título principal más pequeño */
    h1 {
        font-size: 1rem !important;
    }
    
    /* Subtítulos más pequeños */
    h2, h3 {
        font-size: 0.875rem !important;
    }
    
    /* Párrafos y texto general */
    p {
        font-size: 0.75rem !important;
    }
}

/* Mejoras para pantallas muy pequeñas */
@media (max-width: 380px) {
    /* Contenedor con menos padding */
    .container {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }
    
    /* Texto aún más pequeño */
    .text-lg {
        font-size: 0.75rem !important;
    }
    
    .text-base {
        font-size: 0.7rem !important;
    }
    
    .text-sm {
        font-size: 0.65rem !important;
    }
    
    .text-xs {
        font-size: 0.6rem !important;
    }
    
    /* Stack de botones en vertical */
    .flex.gap-2:not(.flex-wrap) {
        flex-direction: column;
        width: 100%;
    }
    
    .flex.gap-2:not(.flex-wrap) button {
        width: 100%;
    }
    
    /* Cards de resumen ultra compactas */
    .stat-card .count-up {
        font-size: 1rem !important;
    }
    
    /* Ocultar textos secundarios */
    .stat-card .mt-3,
    .stat-card .mt-4 {
        display: none;
    }
    
    /* Período del reporte más compacto */
    .text-hotel-gold {
        font-size: 0.6rem !important;
    }
}
</style>

<div class="reporte-view min-h-screen bg-gradient-to-br from-[#F7F4EE] to-[#EEF2EB] py-2 md:py-4">
    <!-- Header del Reporte -->
    <div class="bg-gradient-to-r from-[#4A6340] to-[#3D5234] text-white shadow-xl">
        <div class="container mx-auto px-3 md:px-6 py-2 md:py-4">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-2 md:gap-4">
                <div class="text-center lg:text-left w-full lg:w-auto">
                    <div class="flex items-center gap-2 md:gap-3 mb-1 md:mb-2 justify-center lg:justify-start">
                        <a href="<?= url('reportes') ?>" 
                           class="bg-white/10 backdrop-blur hover:bg-white/20 p-1.5 md:p-2 rounded-lg transition-colors">
                            <i class="fas fa-arrow-left text-xs md:text-base"></i>
                        </a>
                        <h1 class="text-sm md:text-2xl font-bold font-playfair flex items-center gap-1 md:gap-2">
                            <i class="fas fa-balance-scale text-sm md:text-xl opacity-80 hidden md:inline"></i>
                            <span class="block md:inline">Ingresos vs Gastos</span>
                        </h1>
                    </div>
                    <p class="text-[#C8A96A] text-xs md:text-sm ml-0 md:ml-12">
                        <?= date('d/m', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-3 md:px-6 py-3 md:py-6 max-w-7xl">
        <!-- Filtros de Fecha Mejorados -->
        <div class="bg-[#FDFCF9] rounded-xl shadow-sm p-3 md:p-6 mb-3 md:mb-6 border border-[#E8E2D6]">
            <form method="get" action="<?= url('reportes/ingresos-gastos') ?>" class="flex flex-col md:flex-row flex-wrap items-stretch md:items-end gap-2 md:gap-4">
                <div class="flex-1 min-w-full md:min-w-[200px]">
                    <label for="fecha_inicio" class="block text-xs md:text-sm font-semibold text-[#4A6340] mb-1">
                        <i class="fas fa-calendar-alt mr-1 text-xs"></i>Desde
                    </label>
                    <input type="date" 
                           id="fecha_inicio" 
                           name="fecha_inicio" 
                           value="<?= $fecha_inicio ?>" 
                           max="<?= date('Y-m-d') ?>"
                           class="w-full px-2 md:px-4 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] transition-all text-sm md:text-base bg-white">
                </div>
                
                <div class="flex-1 min-w-full md:min-w-[200px]">
                    <label for="fecha_fin" class="block text-xs md:text-sm font-semibold text-[#4A6340] mb-1">
                        <i class="fas fa-calendar-check mr-1 text-xs"></i>Hasta
                    </label>
                    <input type="date" 
                           id="fecha_fin" 
                           name="fecha_fin" 
                           value="<?= $fecha_fin ?>" 
                           max="<?= date('Y-m-d') ?>"
                           class="w-full px-2 md:px-4 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] transition-all text-sm md:text-base bg-white">
                </div>
                
                <div class="flex gap-2 w-full md:w-auto">
                    <button type="submit" 
                            class="bg-[#5C7A4E] text-white px-4 py-2 rounded-lg hover:bg-[#4A6340] transition-all duration-300 flex items-center justify-center gap-2 shadow hover:shadow-md w-full md:w-auto text-sm">
                        <i class="fas fa-sync-alt text-xs"></i>
                        <span>Actualizar</span>
                    </button>
                </div>
            </form>
            
            <!-- Botones de Período Rápido - Mejorados para móvil -->
            <div class="mt-3 pt-3 border-t border-[#E0DAD0]">
                <div class="text-xs font-medium text-[#5C7A4E] mb-2">Períodos rápidos:</div>
                <div class="grid grid-cols-3 sm:flex sm:flex-wrap gap-1.5 md:gap-2">
                    <button onclick="setPeriodo(7)" class="period-btn px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                        7 días
                    </button>
                    <button onclick="setPeriodo(30)" class="period-btn px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                        30 días
                    </button>
                    <button onclick="setPeriodo(90)" class="period-btn px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                        90 días
                    </button>
                    <button onclick="setMesActual()" class="period-btn px-2 py-1.5 rounded-lg text-xs transition-all text-center">
                        Este mes
                    </button>
                    <button onclick="setAnioActual()" class="period-btn px-2 py-1.5 rounded-lg text-xs transition-all text-center col-span-2 sm:col-span-1">
                        Este año
                    </button>
                </div>
            </div>
        </div>

        <!-- Botones de Reportes Adicionales -->
        <div class="flex flex-col sm:flex-row gap-2 md:gap-4 mb-3 md:mb-6">
            <button onclick="abrirModalReporteUsuario()" 
                    class="bg-[#5C7A4E] text-white px-3 py-2 rounded-lg hover:bg-[#4A6340] transition-all duration-300 flex items-center justify-center gap-2 shadow hover:shadow-lg text-xs md:text-base">
                <i class="fas fa-users text-xs md:text-sm"></i>
                <span>Por Usuario</span>
            </button>
            
            <button onclick="abrirModalReporteIngresos()" 
                    class="bg-[#C8A96A] text-[#3D5234] px-3 py-2 rounded-lg hover:bg-[#B8994A] transition-all duration-300 flex items-center justify-center gap-2 shadow hover:shadow-lg font-semibold text-xs md:text-base">
                <i class="fas fa-chart-bar text-xs md:text-sm"></i>
                <span>Ingresos Totales</span>
            </button>
        </div>

        <!-- Cards de Resumen - Estilo mejorado para móviles -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6 mb-4 md:mb-8">
            <!-- Card Ingresos -->
            <div class="stat-card hover-lift bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-3 md:p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-emerald-100 text-xs md:text-sm font-medium uppercase tracking-wider mb-1">Total Ingresos</p>
                            <p class="text-xl md:text-3xl font-bold count-up truncate">
                                <?= format_currency($totales['ingresos']) ?>
                            </p>
                            <div class="mt-2 flex items-center text-xs md:text-sm">
                                <i class="fas fa-chart-line mr-1 text-xs"></i>
                                <span><?= count($datos['ingresos'] ?? []) ?> cat.</span>
                            </div>
                        </div>
                        <div class="bg-white/20 p-2 md:p-4 rounded-full ml-2">
                            <i class="fas fa-arrow-up text-base md:text-2xl"></i>
                        </div>
                    </div>
                </div>
                <div class="p-2 md:p-4 bg-emerald-50">
                    <div class="text-xs text-emerald-700 font-medium truncate">
                        Promedio: <?= format_currency($totales['ingresos'] / max(count($resumenDiario ?? []), 1)) ?>/día
                    </div>
                </div>
            </div>

            <!-- Card Gastos -->
            <div class="stat-card hover-lift bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-red-500 to-red-600 p-3 md:p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-red-100 text-xs md:text-sm font-medium uppercase tracking-wider mb-1">Total Gastos</p>
                            <p class="text-xl md:text-3xl font-bold count-up truncate">
                                <?= format_currency($totales['gastos']) ?>
                            </p>
                            <div class="mt-2 flex items-center text-xs md:text-sm">
                                <i class="fas fa-receipt mr-1 text-xs"></i>
                                <span><?= count($datos['gastos'] ?? []) ?> cat.</span>
                            </div>
                        </div>
                        <div class="bg-white/20 p-2 md:p-4 rounded-full ml-2">
                            <i class="fas fa-arrow-down text-base md:text-2xl"></i>
                        </div>
                    </div>
                </div>
                <div class="p-2 md:p-4 bg-red-50">
                    <div class="text-xs text-red-700 font-medium truncate">
                        Promedio: <?= format_currency($totales['gastos'] / max(count($resumenDiario ?? []), 1)) ?>/día
                    </div>
                </div>
            </div>

            <!-- Card Utilidad -->
            <div class="stat-card hover-lift bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden md:col-span-2 lg:col-span-1">
                <div class="bg-gradient-to-br <?= $totales['utilidad'] >= 0 ? 'from-[#5C7A4E] to-[#4A6340]' : 'from-gray-500 to-gray-600' ?> p-3 md:p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <p class="text-white/90 text-xs md:text-sm font-medium uppercase tracking-wider mb-1">Utilidad Neta</p>
                            <p class="text-xl md:text-3xl font-bold count-up truncate">
                                <?= format_currency($totales['utilidad']) ?>
                            </p>
                            <div class="mt-2 flex items-center justify-between text-xs md:text-sm">
                                <span>Margen:</span>
                                <span class="font-bold">
                                    <?= $totales['ingresos'] > 0 ? round(($totales['utilidad'] / $totales['ingresos']) * 100, 2) : 0 ?>%
                                </span>
                            </div>
                        </div>
                        <div class="bg-white/20 p-2 md:p-4 rounded-full ml-2">
                            <i class="fas <?= $totales['utilidad'] >= 0 ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> text-base md:text-2xl"></i>
                        </div>
                    </div>
                </div>
                <div class="p-2 md:p-4 <?= $totales['utilidad'] >= 0 ? 'bg-[#EEF4EB]' : 'bg-gray-50' ?>">
                    <div class="flex items-center justify-center text-xs <?= $totales['utilidad'] >= 0 ? 'text-[#4A6340]' : 'text-gray-700' ?> font-medium">
                        <i class="fas <?= $totales['utilidad'] >= 0 ? 'fa-smile' : 'fa-meh' ?> mr-1 text-xs"></i>
                        <?= $totales['utilidad'] >= 0 ? 'Resultado positivo' : 'Requiere atención' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desglose por Método de Pago -->
        <div class="bg-white rounded-xl shadow-sm border border-[#E0DDD5] overflow-hidden mb-3 md:mb-6">
            <div class="bg-gradient-to-r from-[#5C7A4E] to-[#4A6340] px-3 md:px-6 py-2.5 md:py-4">
                <h3 class="text-sm md:text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-credit-card text-xs md:text-base"></i>
                    <span class="text-xs md:text-base">Desglose por Método de Pago</span>
                </h3>
            </div>
            <div class="p-3 md:p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 md:gap-6">
                    <!-- Efectivo -->
                    <div class="border rounded-lg p-2.5 md:p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-2 md:mb-4">
                            <div class="flex items-center gap-1.5 md:gap-2">
                                <i class="fas fa-money-bill-wave text-green-600 text-sm md:text-xl"></i>
                                <h4 class="font-semibold text-gray-800 text-xs md:text-base">Efectivo</h4>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 mb-0.5">Balance</p>
                                <p class="text-sm md:text-lg font-bold <?= ($metodosPago['efectivo']['balance'] >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= format_currency($metodosPago['efectivo']['balance'] ?? 0) ?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs md:text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ingresos:</span>
                                <span class="text-green-700 font-medium"><?= format_currency($metodosPago['efectivo']['ingresos'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Gastos:</span>
                                <span class="text-red-700 font-medium"><?= format_currency($metodosPago['efectivo']['gastos'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjeta -->
                    <div class="border rounded-lg p-2.5 md:p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-2 md:mb-4">
                            <div class="flex items-center gap-1.5 md:gap-2">
                                <i class="fas fa-credit-card text-blue-600 text-sm md:text-xl"></i>
                                <h4 class="font-semibold text-gray-800 text-xs md:text-base">Tarjeta</h4>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 mb-0.5">Balance</p>
                                <p class="text-sm md:text-lg font-bold <?= ($metodosPago['tarjeta']['balance'] >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= format_currency($metodosPago['tarjeta']['balance'] ?? 0) ?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs md:text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ingresos:</span>
                                <span class="text-green-700 font-medium"><?= format_currency($metodosPago['tarjeta']['ingresos'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Gastos:</span>
                                <span class="text-red-700 font-medium"><?= format_currency($metodosPago['tarjeta']['gastos'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Transferencia -->
                    <div class="border rounded-lg p-2.5 md:p-4 hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
                        <div class="flex items-center justify-between mb-2 md:mb-4">
                            <div class="flex items-center gap-1.5 md:gap-2">
                                <i class="fas fa-exchange-alt text-purple-600 text-sm md:text-xl"></i>
                                <h4 class="font-semibold text-gray-800 text-xs md:text-base">Transferencia</h4>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500 mb-0.5">Balance</p>
                                <p class="text-sm md:text-lg font-bold <?= ($metodosPago['transferencia']['balance'] >= 0) ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= format_currency($metodosPago['transferencia']['balance'] ?? 0) ?>
                                </p>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs md:text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ingresos:</span>
                                <span class="text-green-700 font-medium"><?= format_currency($metodosPago['transferencia']['ingresos'] ?? 0) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Gastos:</span>
                                <span class="text-red-700 font-medium"><?= format_currency($metodosPago['transferencia']['gastos'] ?? 0) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica de Evolución - Contenedor mejorado -->
        <?php if (!empty($resumenDiario)): ?>
        <div class="bg-white rounded-xl shadow-sm p-3 md:p-6 mb-3 md:mb-6 border border-[#E0DDD5]">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-3 md:mb-4 gap-3">
                <h2 class="text-base md:text-xl font-bold text-[#3D5234] flex items-center gap-2">
                    <i class="fas fa-chart-line text-[#5C7A4E] text-sm md:text-base"></i>
                    <span class="text-sm md:text-base">Evolución Diaria</span>
                </h2>
                <div class="flex gap-1.5 md:gap-2 flex-wrap">
                    <button id="toggleIngresos" onclick="toggleDataset(0)" 
                            class="dataset-toggle-btn px-2 md:px-3 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs md:text-sm hover:bg-emerald-200 transition-all flex items-center gap-1">
                        <i class="fas fa-eye toggle-icon text-xs"></i>
                        <span>Ingresos</span>
                    </button>
                    <button id="toggleGastos" onclick="toggleDataset(1)" 
                            class="dataset-toggle-btn px-2 md:px-3 py-1 bg-red-100 text-red-700 rounded-lg text-xs md:text-sm hover:bg-red-200 transition-all flex items-center gap-1">
                        <i class="fas fa-eye toggle-icon text-xs"></i>
                        <span>Gastos</span>
                    </button>
                    <button id="toggleUtilidad" onclick="toggleDataset(2)" 
                            class="dataset-toggle-btn px-2 md:px-3 py-1 bg-[#EEF4EB] text-[#4A6340] rounded-lg text-xs md:text-sm hover:bg-[#DCE9D5] transition-all flex items-center gap-1">
                        <i class="fas fa-eye toggle-icon text-xs"></i>
                        <span>Utilidad</span>
                    </button>
                </div>
            </div>
            <div class="relative" style="height: 200px;">
                <canvas id="graficaEvolucion"></canvas>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-xl shadow-sm p-3 md:p-6 mb-3 md:mb-6 border border-[#E0DDD5]">
            <div class="text-center py-6 md:py-12 text-gray-500">
                <i class="fas fa-chart-line text-2xl md:text-4xl mb-2 md:mb-4"></i>
                <p class="text-xs md:text-base">No hay datos para mostrar la gráfica en el período seleccionado</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tablas de Detalle - Grid responsivo -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
            <!-- Ingresos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#E0DDD5] overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 md:px-6 py-3 md:py-4">
                    <h3 class="text-base md:text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-arrow-up"></i>
                        Ingresos por Categoría
                    </h3>
                </div>
                <div class="p-4 md:p-6 table-container">
                    <?php if (!empty($datos['ingresos'])): ?>
                    <div class="overflow-x-auto">
                        <table class="styled-table min-w-full">
                            <thead>
                                <tr>
                                    <th class="rounded-tl-lg text-xs md:text-sm">Categoría</th>
                                    <th class="text-center text-xs md:text-sm hidden md:table-cell">Cantidad</th>
                                    <th class="text-right text-xs md:text-sm">Total</th>
                                    <th class="text-center rounded-tr-lg text-xs md:text-sm">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos['ingresos'] as $ingreso): ?>
                                <?php $porcentaje = $totales['ingresos'] > 0 ? round(($ingreso['total'] / $totales['ingresos']) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="px-3 md:px-4 py-2 md:py-3 font-medium text-gray-800 text-xs md:text-sm">
                                        <i class="fas fa-tag text-emerald-500 mr-1 md:mr-2 text-xs"></i>
                                        <?= $ingreso['categoria'] ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center text-gray-600 text-xs md:text-sm hidden md:table-cell">
                                        <?= number_format($ingreso['cantidad']) ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-right font-semibold text-emerald-700 text-xs md:text-sm">
                                        <?= format_currency($ingreso['total']) ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center text-xs md:text-sm">
                                        <span class="font-medium text-gray-700"><?= $porcentaje ?>%</span>
                                        <div class="percentage-bar hidden md:block">
                                            <div class="percentage-fill" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-emerald-50 font-bold text-xs md:text-sm">
                                    <td class="px-3 md:px-4 py-2 md:py-3">Total</td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center hidden md:table-cell"><?= number_format(array_sum(array_column($datos['ingresos'], 'cantidad'))) ?></td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-right text-emerald-700"><?= format_currency($totales['ingresos']) ?></td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-6 md:py-8 text-gray-500">
                        <i class="fas fa-info-circle text-3xl md:text-4xl mb-2"></i>
                        <p class="text-sm md:text-base">No hay ingresos registrados en este período</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gastos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#E0DDD5] overflow-hidden">
                <div class="bg-gradient-to-r from-red-500 to-red-600 px-4 md:px-6 py-3 md:py-4">
                    <h3 class="text-base md:text-lg font-bold text-white flex items-center gap-2">
                        <i class="fas fa-arrow-down"></i>
                        Gastos por Categoría
                    </h3>
                </div>
                <div class="p-4 md:p-6 table-container">
                    <?php if (!empty($datos['gastos'])): ?>
                    <div class="overflow-x-auto">
                        <table class="styled-table min-w-full">
                            <thead>
                                <tr>
                                    <th class="rounded-tl-lg text-xs md:text-sm">Categoría</th>
                                    <th class="text-center text-xs md:text-sm hidden md:table-cell">Cantidad</th>
                                    <th class="text-right text-xs md:text-sm">Total</th>
                                    <th class="text-center rounded-tr-lg text-xs md:text-sm">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos['gastos'] as $gasto): ?>
                                <?php $porcentaje = $totales['gastos'] > 0 ? round(($gasto['total'] / $totales['gastos']) * 100, 1) : 0; ?>
                                <tr>
                                    <td class="px-3 md:px-4 py-2 md:py-3 font-medium text-gray-800 text-xs md:text-sm">
                                        <i class="fas fa-tag text-red-500 mr-1 md:mr-2 text-xs"></i>
                                        <?= $gasto['categoria'] ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center text-gray-600 text-xs md:text-sm hidden md:table-cell">
                                        <?= number_format($gasto['cantidad']) ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-right font-semibold text-red-700 text-xs md:text-sm">
                                        <?= format_currency($gasto['total']) ?>
                                    </td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center text-xs md:text-sm">
                                        <span class="font-medium text-gray-700"><?= $porcentaje ?>%</span>
                                        <div class="percentage-bar hidden md:block">
                                            <div class="percentage-fill bg-gradient-to-r from-red-400 to-red-600" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="bg-red-50 font-bold text-xs md:text-sm">
                                    <td class="px-3 md:px-4 py-2 md:py-3">Total</td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center hidden md:table-cell"><?= number_format(array_sum(array_column($datos['gastos'], 'cantidad'))) ?></td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-right text-red-700"><?= format_currency($totales['gastos']) ?></td>
                                    <td class="px-3 md:px-4 py-2 md:py-3 text-center">100%</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-6 md:py-8 text-gray-500">
                        <i class="fas fa-info-circle text-3xl md:text-4xl mb-2"></i>
                        <p class="text-sm md:text-base">No hay gastos registrados en este período</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resumen Diario - Tabla scrollable mejorada -->
        <?php if (!empty($resumenDiario)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-[#E0DDD5] overflow-hidden">
            <div class="bg-gradient-to-r from-[#5C7A4E] to-[#4A6340] px-4 md:px-6 py-3 md:py-4">
                <h3 class="text-base md:text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-calendar-day"></i>
                    Detalle Diario
                </h3>
            </div>
            <div class="p-4 md:p-6">
                <div class="overflow-x-auto max-h-64 md:max-h-96 overflow-y-auto">
                    <table class="styled-table min-w-full">
                        <thead>
                            <tr>
                                <th class="rounded-tl-lg text-xs md:text-sm sticky left-0 bg-gradient-to-r from-[#5C7A4E] to-[#4A6340]">Fecha</th>
                                <th class="text-xs md:text-sm hidden sm:table-cell">Día</th>
                                <th class="text-right text-xs md:text-sm">Ingresos</th>
                                <th class="text-right text-xs md:text-sm">Gastos</th>
                                <th class="text-right rounded-tr-lg text-xs md:text-sm">Utilidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resumenDiario as $dia): ?>
                            <?php 
                            $fecha = strtotime($dia['fecha']);
                            $diaSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][date('w', $fecha)];
                            $esFinSemana = in_array(date('w', $fecha), [0, 6]);
                            ?>
                            <tr class="<?= $esFinSemana ? 'bg-[#EEF5EB]' : '' ?>">
                                <td class="px-3 md:px-4 py-2 md:py-3 font-medium text-xs md:text-sm sticky left-0 <?= $esFinSemana ? 'bg-[#EEF5EB]' : 'bg-white' ?>">
                                    <?= date('d/m/Y', $fecha) ?>
                                </td>
                                <td class="px-3 md:px-4 py-2 md:py-3 text-xs md:text-sm hidden sm:table-cell">
                                    <span class="<?= $esFinSemana ? 'text-[#5C7A4E] font-semibold' : 'text-gray-600' ?>">
                                        <?= $diaSemana ?>
                                    </span>
                                </td>
                                <td class="px-3 md:px-4 py-2 md:py-3 text-right text-emerald-700 font-semibold text-xs md:text-sm">
                                    <?= format_currency($dia['ingresos']) ?>
                                </td>
                                <td class="px-3 md:px-4 py-2 md:py-3 text-right text-red-700 font-semibold text-xs md:text-sm">
                                    <?= format_currency($dia['gastos']) ?>
                                </td>
                                <td class="px-3 md:px-4 py-2 md:py-3 text-right font-bold <?= $dia['utilidad'] >= 0 ? 'text-[#5C7A4E]' : 'text-gray-700' ?> text-xs md:text-sm">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="fas <?= $dia['utilidad'] >= 0 ? 'fa-caret-up' : 'fa-caret-down' ?> text-xs"></i>
                                        <?= format_currency($dia['utilidad']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Insights y Recomendaciones -->
        <?php if (!empty($datos['ingresos']) || !empty($datos['gastos'])): ?>
        <div class="mt-6 md:mt-8 bg-gradient-to-br from-[#C8A96A]/15 to-[#5C7A4E]/10 rounded-xl p-4 md:p-6 border border-[#C8A96A]/25">
            <h3 class="text-base md:text-lg font-bold text-[#3D5234] mb-3 md:mb-4 flex items-center gap-2">
                <i class="fas fa-lightbulb text-[#C8A96A]"></i>
                Insights y Recomendaciones
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
                <?php if (!empty($datos['ingresos'])): ?>
                <div class="bg-white/80 backdrop-blur rounded-lg p-3 md:p-4">
                    <h4 class="font-semibold text-gray-800 mb-1.5 md:mb-2 text-sm md:text-base">
                        <i class="fas fa-chart-pie text-emerald-600 mr-1 md:mr-2"></i>
                        Mayor fuente de ingresos
                    </h4>
                    <p class="text-xs md:text-sm text-gray-600">
                        <?php 
                        $maxIngreso = array_reduce($datos['ingresos'], function($carry, $item) {
                            return (!$carry || $item['total'] > $carry['total']) ? $item : $carry;
                        });
                        ?>
                        <strong><?= $maxIngreso['categoria'] ?></strong> representa el 
                        <strong><?= round(($maxIngreso['total'] / $totales['ingresos']) * 100, 1) ?>%</strong>
                        de los ingresos totales.
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($datos['gastos'])): ?>
                <div class="bg-white/80 backdrop-blur rounded-lg p-3 md:p-4">
                    <h4 class="font-semibold text-gray-800 mb-1.5 md:mb-2 text-sm md:text-base">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-1 md:mr-2"></i>
                        Mayor gasto
                    </h4>
                    <p class="text-xs md:text-sm text-gray-600">
                        <?php 
                        $maxGasto = array_reduce($datos['gastos'], function($carry, $item) {
                            return (!$carry || $item['total'] > $carry['total']) ? $item : $carry;
                        });
                        ?>
                        <strong><?= $maxGasto['categoria'] ?? 'N/A' ?></strong> representa el 
                        <strong><?= $totales['gastos'] > 0 ? round(($maxGasto['total'] / $totales['gastos']) * 100, 1) : 0 ?>%</strong>
                        de los gastos totales.
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($resumenDiario)): ?>
                <div class="bg-white/80 backdrop-blur rounded-lg p-3 md:p-4">
                    <h4 class="font-semibold text-gray-800 mb-1.5 md:mb-2 text-sm md:text-base">
                        <i class="fas fa-calendar-check text-blue-600 mr-1 md:mr-2"></i>
                        Mejor día
                    </h4>
                    <p class="text-xs md:text-sm text-gray-600">
                        <?php 
                        $mejorDia = array_reduce($resumenDiario, function($carry, $item) {
                            return (!$carry || $item['utilidad'] > $carry['utilidad']) ? $item : $carry;
                        });
                        ?>
                        El <strong><?= date('d/m/Y', strtotime($mejorDia['fecha'])) ?></strong>
                        con una utilidad de <strong><?= format_currency($mejorDia['utilidad']) ?></strong>
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="bg-white/80 backdrop-blur rounded-lg p-3 md:p-4">
                    <h4 class="font-semibold text-gray-800 mb-1.5 md:mb-2 text-sm md:text-base">
                        <i class="fas fa-info-circle text-purple-600 mr-1 md:mr-2"></i>
                        Promedio diario
                    </h4>
                    <p class="text-xs md:text-sm text-gray-600">
                        Utilidad promedio: 
                        <strong class="<?= ($totales['utilidad'] / max(count($resumenDiario ?? []), 1)) >= 0 ? 'text-[#5C7A4E]' : 'text-red-700' ?>">
                            <?= format_currency($totales['utilidad'] / max(count($resumenDiario ?? []), 1)) ?>
                        </strong> por día
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Reporte por Usuario -->
<div id="modalReporteUsuario" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-4 md:p-6 max-w-md w-full mx-auto shadow-2xl border border-[#E0DDD5]">
        <h3 class="text-base md:text-lg font-bold text-[#3D5234] mb-4">Reporte de Ingresos y Gastos por Usuario</h3>
        <form id="formReporteUsuario" class="space-y-3 md:space-y-4">
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Usuario</label>
                <select name="usuario_id" required class="w-full px-3 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] text-sm md:text-base">
                    <option value="">Seleccione un usuario</option>
                    <?php foreach ($usuarios ?? [] as $usuario): ?>
                        <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" required value="<?= date('Y-m-01') ?>" 
                       class="w-full px-3 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] text-sm md:text-base">
            </div>
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Fecha Fin</label>
                <input type="date" name="fecha_fin" required value="<?= date('Y-m-d') ?>" 
                       class="w-full px-3 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] text-sm md:text-base">
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-3 md:pt-4">
                <button type="submit" class="flex-1 bg-[#5C7A4E] text-white px-4 py-2.5 rounded-lg hover:bg-[#4A6340] transition-colors text-sm md:text-base">
                    Generar Reporte
                </button>
                <button type="button" onclick="cerrarModalReporteUsuario()" 
                        class="flex-1 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base border border-gray-200">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reporte de Ingresos Totales -->
<div id="modalReporteIngresos" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl p-4 md:p-6 max-w-md w-full mx-auto shadow-2xl border border-[#E0DDD5]">
        <h3 class="text-base md:text-lg font-bold text-[#3D5234] mb-4">Reporte de Ingresos Totales</h3>
        <form id="formReporteIngresos" class="space-y-3 md:space-y-4">
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" required value="<?= date('Y-m-01') ?>" 
                       class="w-full px-3 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] text-sm md:text-base">
            </div>
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Fecha Fin</label>
                <input type="date" name="fecha_fin" required value="<?= date('Y-m-d') ?>" 
                       class="w-full px-3 py-2 border border-[#C8D9BE] rounded-lg focus:ring-2 focus:ring-[#5C7A4E]/20 focus:border-[#5C7A4E] text-sm md:text-base">
            </div>
            <div>
                <label class="block text-xs md:text-sm font-medium text-[#4A6340] mb-1 md:mb-2">Incluir detalles por</label>
                <div class="space-y-2">
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="desglose[]" value="categoria" checked 
                               class="rounded text-[#5C7A4E] focus:ring-[#5C7A4E] mr-2">
                        <span>Categoría</span>
                    </label>
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="desglose[]" value="metodo_pago" checked 
                               class="rounded text-[#5C7A4E] focus:ring-[#5C7A4E] mr-2">
                        <span>Método de pago</span>
                    </label>
                    <label class="flex items-center text-sm">
                        <input type="checkbox" name="desglose[]" value="diario" 
                               class="rounded text-[#5C7A4E] focus:ring-[#5C7A4E] mr-2">
                        <span>Detalle diario</span>
                    </label>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2 pt-3 md:pt-4">
                <button type="submit" class="flex-1 bg-[#C8A96A] text-[#3D5234] px-4 py-2.5 rounded-lg hover:bg-[#B8994A] transition-colors font-semibold text-sm md:text-base">
                    Generar Reporte
                </button>
                <button type="button" onclick="cerrarModalReporteIngresos()" 
                        class="flex-1 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-200 transition-colors text-sm md:text-base border border-gray-200">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Datos para la gráfica
const datosGrafica = <?= json_encode($resumenDiario ?? []) ?>;

// Solo crear gráfica si hay datos
if (datosGrafica && datosGrafica.length > 0) {
    const ctx = document.getElementById('graficaEvolucion').getContext('2d');
    const chartConfig = {
        type: 'line',
        data: {
            labels: datosGrafica.map(d => {
                const fecha = new Date(d.fecha);
                return fecha.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
            }),
            datasets: [{
                label: 'Ingresos',
                data: datosGrafica.map(d => d.ingresos),
                borderColor: 'rgb(16, 185, 129)',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.3,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(16, 185, 129)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Gastos',
                data: datosGrafica.map(d => d.gastos),
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                tension: 0.3,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(239, 68, 68)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }, {
                label: 'Utilidad',
                data: datosGrafica.map(d => d.utilidad),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.3,
                borderWidth: 3,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 10,
                        usePointStyle: true,
                        font: {
                            size: window.innerWidth < 768 ? 10 : 12,
                            family: "'Inter', sans-serif"
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 8,
                    cornerRadius: 8,
                    titleFont: {
                        size: 12,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 11
                    },
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '$' + context.parsed.y.toLocaleString('es-MX');
                            }
                            return label;
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
                        callback: function(value) {
                            return '$' + value.toLocaleString('es-MX');
                        },
                        font: {
                            size: window.innerWidth < 768 ? 9 : 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        padding: 5,
                        font: {
                            size: window.innerWidth < 768 ? 9 : 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    };

    const myChart = new Chart(ctx, chartConfig);
    
    // Función mejorada para alternar datasets con efecto visual
    window.toggleDataset = function(index) {
        const dataset = myChart.data.datasets[index];
        dataset.hidden = !dataset.hidden;
        myChart.update();
        
        // Actualizar el botón correspondiente
        const buttons = ['toggleIngresos', 'toggleGastos', 'toggleUtilidad'];
        const button = document.getElementById(buttons[index]);
        const icon = button.querySelector('.toggle-icon');
        
        if (dataset.hidden) {
            button.classList.add('dataset-hidden');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            button.classList.remove('dataset-hidden');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    // Actualizar el tamaño de fuente cuando cambie el tamaño de ventana
    window.addEventListener('resize', function() {
        myChart.options.plugins.legend.labels.font.size = window.innerWidth < 768 ? 10 : 12;
        myChart.options.scales.y.ticks.font.size = window.innerWidth < 768 ? 9 : 11;
        myChart.options.scales.x.ticks.font.size = window.innerWidth < 768 ? 9 : 11;
        myChart.update();
    });
}

// Exportar a PDF
function exportarPDF() {
    const url = '<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-gastos' +
                '&fecha_inicio=<?= $fecha_inicio ?>' +
                '&fecha_fin=<?= $fecha_fin ?>';
    window.open(url, '_blank');
}

// Funciones para períodos rápidos
function setPeriodo(dias) {
    const fechaFin = new Date();
    const fechaInicio = new Date();
    fechaInicio.setDate(fechaInicio.getDate() - dias);
    
    document.getElementById('fecha_inicio').value = fechaInicio.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fechaFin.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

function setMesActual() {
    const fecha = new Date();
    const primerDia = new Date(fecha.getFullYear(), fecha.getMonth(), 1);
    const ultimoDia = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);
    
    document.getElementById('fecha_inicio').value = primerDia.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = ultimoDia.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

function setAnioActual() {
    const fecha = new Date();
    const primerDia = new Date(fecha.getFullYear(), 0, 1);
    
    document.getElementById('fecha_inicio').value = primerDia.toISOString().split('T')[0];
    document.getElementById('fecha_fin').value = fecha.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

// Reemplazar las funciones de apertura y cierre de modales con estas versiones actualizadas:

function abrirModalReporteUsuario() {
    // Ocultar sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }
    
    // Mostrar modal
    document.getElementById('modalReporteUsuario').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalReporteUsuario() {
    // Restaurar sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }
    
    // Ocultar modal
    document.getElementById('modalReporteUsuario').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalReporteUsuario').querySelector('form').reset();
}

function abrirModalReporteIngresos() {
    // Ocultar sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }
    
    // Mostrar modal
    document.getElementById('modalReporteIngresos').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalReporteIngresos() {
    // Restaurar sidebar
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }
    
    // Ocultar modal
    document.getElementById('modalReporteIngresos').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalReporteIngresos').querySelector('form').reset();
}

// Manejar envío de formularios
document.getElementById('formReporteUsuario').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData).toString();
    window.open('<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-gastos-usuario&' + params, '_blank');
    cerrarModalReporteUsuario();
});

document.getElementById('formReporteIngresos').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const params = new URLSearchParams(formData).toString();
    window.open('<?= url('reportes/exportar-pdf') ?>?tipo=ingresos-totales&' + params, '_blank');
    cerrarModalReporteIngresos();
});

// Animación de carga
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.reporte-view');
    if (view) {
        setTimeout(() => {
            view.classList.add('loaded');
        }, 100);
    }
    
    // Animar barras de porcentaje
    setTimeout(() => {
        document.querySelectorAll('.percentage-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 500);
    
    // Efecto de conteo para números grandes
    document.querySelectorAll('.count-up').forEach(element => {
        const finalText = element.textContent;
        element.textContent = '$0';
        setTimeout(() => {
            element.textContent = finalText;
        }, 300);
    });
});

// Print styles
if (window.myChart) {
    window.addEventListener('beforeprint', function() {
        myChart.resize(800, 400);
    });

    window.addEventListener('afterprint', function() {
        myChart.resize();
    });
}
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include __DIR__ . '/../layout/footer.php'; ?>