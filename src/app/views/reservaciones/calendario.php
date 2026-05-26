<?php
/**
 * Vista de Calendario de Reservaciones - Versión Moderna Corregida
 * Los Cedros
 */

// Configurar el calendario
$primerDia = mktime(0, 0, 0, $mes, 1, $año);
$diasEnMes = date('t', $primerDia);
$diaSemana = date('w', $primerDia);
$mesAnterior = $mes - 1;
$mesSiguiente = $mes + 1;
$añoAnterior = $año;
$añoSiguiente = $año;

if ($mesAnterior < 1) {
    $mesAnterior = 12;
    $añoAnterior--;
}

if ($mesSiguiente > 12) {
    $mesSiguiente = 1;
    $añoSiguiente++;
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$diasSemanaCorto = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];

// Calcular estadísticas del mes
$totalReservaciones = count($reservaciones);
$confirmadas = 0;
$checkIn = 0;
$checkOut = 0;
$canceladas = 0;

foreach ($reservaciones as $r) {
    switch ($r['estado']) {
        case 'confirmada': $confirmadas++; break;
        case 'checked_in': $checkIn++; break;
        case 'checked_out': $checkOut++; break;
        case 'cancelada': $canceladas++; break;
    }
}

// Calcular ocupación promedio del mes
$diasOcupados = 0;
$totalDiasHabitacion = count($habitaciones) * $diasEnMes;

// Contar solo habitaciones ocupadas actualmente (checked_in)
foreach ($calendario as $dia => $habitacionesReservadas) {
    foreach ($habitacionesReservadas as $habId => $reserva) {
        // Solo contar como ocupada si está en check-in
        if ($reserva['estado'] == 'checked_in') {
            $diasOcupados++;
        }
    }
}
$ocupacionPromedio = $totalDiasHabitacion > 0 ? round(($diasOcupados / $totalDiasHabitacion) * 100) : 0;
?>

<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<!-- Calendario de Reservaciones Moderno -->
<div class="min-h-screen bg-gray-50">
    <!-- Header del Calendario -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="px-3 sm:px-4 lg:px-6 py-3 sm:py-4">
            <!-- Header Principal -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <!-- Logo y Título -->
                <div class="flex items-center space-x-3">
                    <div class="bg-amber-100 p-2 rounded-lg">
                        <i class="fas fa-calendar text-amber-600 text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg sm:text-xl font-semibold text-gray-900">
                            Calendario de Reservaciones
                        </h1>
                        <p class="text-xs text-gray-500">
                            Vista mensual de ocupación del hotel
                        </p>
                    </div>
                </div>
                
                <!-- Acciones Rápidas -->
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?= url('reservaciones') ?>" 
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 bg-gray-100 text-gray-700 text-xs sm:text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-list mr-1.5"></i>
                        Vista Lista
                    </a>
                    <a href="<?= url('reservaciones/crear') ?>" 
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 bg-blue-600 text-white text-xs sm:text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-plus mr-1.5"></i>
                        <span class="hidden xs:inline">Nueva</span> Reserva
                    </a>
                </div>
            </div>
            
            <!-- Estadísticas Rápidas del Header -->
            <div class="flex flex-wrap items-center gap-4 mt-3 pt-3 border-t border-gray-100">
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span class="text-xs text-gray-600">Ocupación:</span>
                    <span class="text-xs font-semibold text-gray-900">
                        <?= $ocupacionPromedio ?>%
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-xs text-gray-600">Total reservas:</span>
                    <span class="text-xs font-semibold text-gray-900">
                        <?= $totalReservaciones ?>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                    <span class="text-xs text-gray-600">Confirmadas:</span>
                    <span class="text-xs font-semibold text-gray-900">
                        <?= $confirmadas ?>
                    </span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                    <span class="text-xs text-gray-600">Check-ins:</span>
                    <span class="text-xs font-semibold text-gray-900">
                        <?= $checkIn ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="px-3 sm:px-4 lg:px-6 py-4 sm:py-6">
        <!-- Controles del Calendario -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
            <div class="p-3 sm:p-4">
                <div class="flex items-center justify-between">
                    <!-- Navegación Mes Anterior -->
                    <a href="<?= url("reservaciones/calendario?mes={$mesAnterior}&año={$añoAnterior}") ?>" 
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="fas fa-chevron-left mr-1.5"></i>
                        <span class="hidden sm:inline"><?= $meses[$mesAnterior] ?></span>
                    </a>
                    
                    <!-- Mes y Año Actual -->
                    <div class="text-center">
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-900">
                            <?= $meses[$mes] ?> <?= $año ?>
                        </h2>
                        <button onclick="irAHoy()" class="text-xs text-blue-600 hover:text-blue-700 mt-1">
                            <i class="fas fa-calendar-day mr-1"></i>
                            Ir a hoy
                        </button>
                    </div>
                    
                    <!-- Navegación Mes Siguiente -->
                    <a href="<?= url("reservaciones/calendario?mes={$mesSiguiente}&año={$añoSiguiente}") ?>" 
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        <span class="hidden sm:inline"><?= $meses[$mesSiguiente] ?></span>
                        <i class="fas fa-chevron-right ml-1.5"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Grid Principal: Calendario y Widgets -->
        <div class="grid grid-cols-1 gap-4 sm:gap-6">
            <!-- Calendario a ancho completo -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                    <div class="overflow-x-auto rounded-xl">
                        <table class="w-full calendar-table">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="sticky-column bg-gray-50 px-3 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border-r border-b border-gray-200">
                                        <div class="flex items-center space-x-1">
                                            <i class="fas fa-bed text-gray-500"></i>
                                            <span>Hab.</span>
                                        </div>
                                    </th>
                                    <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                        <?php 
                                        $fecha = mktime(0, 0, 0, $mes, $dia, $año);
                                        $diaSemanaActual = date('w', $fecha);
                                        $esFinDeSemana = ($diaSemanaActual == 0 || $diaSemanaActual == 6);
                                        $esHoy = (date('Y-m-d', $fecha) == date('Y-m-d'));
                                        ?>
                                        <th class="calendar-day px-1 py-2 text-center border-b border-gray-200
                                                   <?= $esHoy ? 'bg-blue-50' : ($esFinDeSemana ? 'bg-gray-100' : 'bg-gray-50') ?>">
                                            <div class="text-xs font-bold <?= $esHoy ? 'text-blue-600' : 'text-gray-700' ?>">
                                                <?= $dia ?>
                                            </div>
                                            <div class="text-xs <?= $esHoy ? 'text-blue-500' : 'text-gray-500' ?>">
                                                <span class="hidden sm:inline"><?= $diasSemana[$diaSemanaActual] ?></span>
                                                <span class="sm:hidden"><?= $diasSemanaCorto[$diaSemanaActual] ?></span>
                                            </div>
                                        </th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($habitaciones as $habitacion): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="sticky-column bg-white px-3 py-2 font-medium border-r border-b border-gray-200">
                                            <div class="flex items-center space-x-2">
                                                <span class="inline-flex items-center justify-center min-w-[32px] w-8 h-8 text-xs font-bold rounded-full bg-amber-100 text-amber-800">
                                                    <?= $habitacion['numero'] ?>
                                                </span>
                                                <span class="text-xs text-gray-500 hidden lg:inline">
                                                    <?= ucfirst(str_replace('_', ' ', $habitacion['tipo'])) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                            <?php 
                                            $fecha = mktime(0, 0, 0, $mes, $dia, $año);
                                            $esHoy = (date('Y-m-d', $fecha) == date('Y-m-d'));
                                            $diaSemanaActual = date('w', $fecha);
                                            $esFinDeSemana = ($diaSemanaActual == 0 || $diaSemanaActual == 6);
                                            ?>
                                            <td class="calendar-cell p-0.5 border-b border-gray-100 <?= $esHoy ? 'bg-blue-50' : ($esFinDeSemana ? 'bg-gray-50' : '') ?>">
                                                <?php if (isset($calendario[$dia][$habitacion['id']])): ?>
                                                    <?php 
                                                    $reserva = $calendario[$dia][$habitacion['id']]; 
                                                    $colorEstado = [
                                                        'confirmada' => 'bg-blue-500 hover:bg-blue-600',
                                                        'checked_in' => 'bg-green-500 hover:bg-green-600',
                                                        'checked_out' => 'bg-purple-500 hover:bg-purple-600', // Cambio de gris a púrpura para mejor visibilidad
                                                        'cancelada' => 'bg-red-400 hover:bg-red-500 opacity-60'
                                                    ][$reserva['estado']] ?? 'bg-gray-400';
                                                    
                                                    $iconoEstado = [
                                                        'confirmada' => 'fa-calendar-check',
                                                        'checked_in' => 'fa-sign-in-alt',
                                                        'checked_out' => 'fa-sign-out-alt',
                                                        'cancelada' => 'fa-times-circle'
                                                    ][$reserva['estado']] ?? 'fa-user';
                                                    ?>
                                                    <div class="reservation-block <?= $colorEstado ?> text-white rounded px-1 py-0.5 text-xs cursor-pointer transition-all transform hover:scale-105 flex items-center justify-center"
                                                         onclick="verReservacion(<?= $reserva['id'] ?>)"
                                                         data-toggle="tooltip"
                                                         title="<?= htmlspecialchars($reserva['huesped_nombre']) ?> - <?= ucfirst($reserva['estado']) ?>">
                                                        <i class="fas <?= $iconoEstado ?> text-xs"></i>
                                                        <span class="ml-1 hidden xl:inline truncate max-w-[60px]">
                                                            <?= explode(' ', $reserva['huesped_nombre'])[0] ?>
                                                        </span>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="available-cell hover:bg-green-100 rounded cursor-pointer transition-all flex items-center justify-center"
                                                         onclick="crearReservacion('<?= date('Y-m-d', $fecha) ?>', <?= $habitacion['id'] ?>)"
                                                         title="Disponible - Click para reservar">
                                                        <i class="fas fa-plus text-gray-300 hover:text-green-500 text-xs"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Widgets Laterales (1 columna en desktop) -->
            <div class="xl:col-span-1 space-y-4">
                <!-- Widget de Resumen -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-chart-pie text-indigo-600 mr-2"></i>
                        Resumen del Mes
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600">Ocupación promedio</span>
                            <span class="text-sm font-semibold text-gray-900"><?= $ocupacionPromedio ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 h-2 rounded-full transition-all" style="width: <?= $ocupacionPromedio ?>%"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-gray-800"><?= $totalReservaciones ?></p>
                                <p class="text-xs text-gray-600">Total</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-blue-600"><?= $confirmadas ?></p>
                                <p class="text-xs text-gray-600">Confirmadas</p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-green-600"><?= $checkIn ?></p>
                                <p class="text-xs text-gray-600">Check-in</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3 text-center">
                                <p class="text-xl font-bold text-gray-600"><?= $checkOut ?></p>
                                <p class="text-xs text-gray-600">Check-out</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget de Leyenda -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Leyenda
                    </h3>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-blue-500 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Confirmada</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-green-500 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Check-in realizado</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-gray-400 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Check-out realizado</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-red-400 opacity-60 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Cancelada</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-green-100 border border-green-300 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Disponible</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-4 h-4 bg-blue-50 border border-blue-300 rounded mr-3 flex-shrink-0"></div>
                            <span class="text-xs text-gray-700">Hoy</span>
                        </div>
                    </div>
                </div>

                <!-- Widget de Acciones Rápidas -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-bolt text-yellow-600 mr-2"></i>
                        Acciones Rápidas
                    </h3>
                    <div class="space-y-2">
                        <button onclick="exportarCalendario()" 
                                class="w-full px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                            <i class="fas fa-download mr-2"></i>
                            Exportar Calendario
                        </button>
                        <button onclick="imprimirCalendario()" 
                                class="w-full px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center">
                            <i class="fas fa-print mr-2"></i>
                            Imprimir
                        </button>
                        <a href="<?= url('reportes/ocupacion') ?>" 
                           class="block w-full px-3 py-2 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors text-center">
                            <i class="fas fa-chart-line mr-2"></i>
                            Ver Reportes
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Móvil: Lista de Reservaciones del Día -->
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:hidden">
            <h3 class="text-sm font-semibold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-list text-gray-600 mr-2"></i>
                Reservaciones de Hoy
            </h3>
            <?php
            $hoy = date('j');
            $reservacionesHoy = [];
            if (isset($calendario[$hoy])) {
                foreach ($calendario[$hoy] as $habId => $reserva) {
                    $reservacionesHoy[] = $reserva;
                }
            }
            ?>
            <?php if (empty($reservacionesHoy)): ?>
                <p class="text-xs text-gray-500 text-center py-4">No hay reservaciones para hoy</p>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($reservacionesHoy as $reserva): ?>
                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <p class="text-xs font-medium text-gray-900"><?= htmlspecialchars($reserva['huesped_nombre']) ?></p>
                                <p class="text-xs text-gray-500">Hab. <?= $reserva['habitacion_numero'] ?? 'N/A' ?></p>
                            </div>
                            <?php
                            $badgeClase = [
                                'confirmada' => 'bg-blue-100 text-blue-800',
                                'checked_in' => 'bg-green-100 text-green-800',
                                'checked_out' => 'bg-purple-100 text-purple-800',
                                'cancelada' => 'bg-red-100 text-red-800'
                            ][$reserva['estado']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeClase ?>">
                                <?= ucfirst($reserva['estado']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Estilos personalizados corregidos -->
<style>
/* Reset para evitar conflictos */
* {
    box-sizing: border-box;
}

/* Contenedor del calendario */
.calendar-container {
    max-height: calc(100vh - 320px);
    overflow: auto;
    position: relative;
}

/* Tabla del calendario */
.calendar-table {
    table-layout: auto; /* Cambio de fixed a auto para ancho completo */
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

/* Header sticky */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 20;
    background-color: #f9fafb;
}

/* Columna sticky con header sticky */
.sticky-column {
    position: sticky !important;
    left: 0;
    z-index: 10;
    min-width: 100px;
    background-color: white;
}

.sticky-header-column {
    z-index: 21; /* Mayor que el header normal */
    background-color: #f9fafb !important;
}

/* Sombra para la columna sticky */
.sticky-column::after {
    content: '';
    position: absolute;
    top: 0;
    right: -5px;
    bottom: 0;
    width: 5px;
    background: linear-gradient(to right, rgba(0,0,0,0.06), transparent);
    pointer-events: none;
}

/* Días del calendario - ancho flexible */
.calendar-day {
    width: auto;
    min-width: 35px;
    padding: 0.5rem 0.25rem;
}

/* Celdas del calendario */
.calendar-cell {
    height: 36px;
    min-height: 36px;
    max-height: 36px;
    vertical-align: middle;
    position: relative;
    padding: 2px;
}

/* Bloques de reservación */
.reservation-block {
    height: 28px;
    font-size: 0.65rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Celdas disponibles */
.available-cell {
    height: 28px;
    width: 100%;
}

/* Para pantallas grandes, mostrar más información */
@media (min-width: 1280px) {
    .calendar-day {
        min-width: 45px;
    }
    
    .sticky-column {
        min-width: 120px;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .calendar-container {
        max-height: calc(100vh - 280px);
    }
    
    .sticky-column {
        min-width: 70px;
    }
    
    .calendar-day {
        min-width: 28px;
        padding: 0.25rem 0;
        font-size: 0.6rem;
    }
    
    .calendar-cell {
        height: 30px;
        min-height: 30px;
        max-height: 30px;
        padding: 1px;
    }
    
    .reservation-block {
        height: 26px;
        padding: 0.125rem 0.25rem;
    }
    
    .available-cell {
        height: 26px;
    }
}

/* Print styles */
@media print {
    /* Ocultar elementos no necesarios para impresión */
    .no-print,
    .bg-white.shadow-sm.border-b, /* Header del sistema */
    .px-3.sm\:px-4.lg\:px-6.py-4.sm\:py-6 > .bg-white.rounded-xl.shadow-sm.border:first-child, /* Controles */
    .grid.grid-cols-1.md\:grid-cols-3, /* Widgets */
    .mt-6.bg-white.rounded-xl, /* Vista móvil */
    button,
    a[role="button"] {
        display: none !important;
    }
    
    /* Mostrar título solo en impresión */
    .print-title {
        display: block !important;
    }
    
    /* Ajustar el layout para impresión */
    body {
        background: white;
        margin: 0;
        padding: 0;
    }
    
    .min-h-screen {
        min-height: auto;
    }
    
    .px-3.sm\:px-4.lg\:px-6.py-4.sm\:py-6 {
        padding: 0.5cm;
    }
    
    .calendar-container {
        max-height: none !important;
        overflow: visible !important;
        page-break-inside: avoid;
    }
    
    /* Tabla a página completa */
    .calendar-table {
        width: 100%;
        font-size: 9pt;
        border: 1px solid #000;
    }
    
    .calendar-table th,
    .calendar-table td {
        border: 1px solid #ccc;
        padding: 2px !important;
    }
    
    .sticky-header,
    .sticky-column {
        position: static !important;
    }
    
    .calendar-day {
        min-width: auto !important;
        width: auto !important;
        font-size: 8pt;
    }
    
    .calendar-cell {
        height: 25px !important;
        min-height: 25px !important;
    }
    
    .reservation-block {
        height: 20px !important;
        font-size: 7pt !important;
        padding: 1px !important;
    }
    
    /* Mantener colores en impresión */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    
    .bg-blue-500 {
        background-color: #3b82f6 !important;
    }
    
    .bg-green-500 {
        background-color: #10b981 !important;
    }
    
    .bg-purple-500 {
        background-color: #8b5cf6 !important;
    }
    
    .bg-red-400 {
        background-color: #f87171 !important;
    }
    
    .bg-amber-100 {
        background-color: #fef3c7 !important;
    }
    
    .text-white {
        color: white !important;
    }
    
    /* Configuración de página */
    @page {
        size: landscape;
        margin: 1cm;
    }
    
    /* Evitar cortes de página en elementos importantes */
    tr {
        page-break-inside: avoid;
    }
}

/* Tooltips */
[data-toggle="tooltip"] {
    position: relative;
}

/* Animaciones suaves */
.transition-all {
    transition: all 0.2s ease-in-out;
}

.transition-colors {
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
}

/* Scrollbar personalizada para el calendario */
.calendar-container {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.calendar-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.calendar-container::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.calendar-container::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.calendar-container::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}

/* Mejoras visuales adicionales */
.bg-amber-100 {
    background-color: #fef3c7;
}

.text-amber-800 {
    color: #92400e;
}

.text-amber-600 {
    color: #d97706;
}

/* Asegurar que el calendario use todo el ancho disponible */
.calendar-table th:not(.sticky-column),
.calendar-table td:not(.sticky-column) {
    width: calc((100% - 100px) / 31); /* Distribuir el espacio entre los días */
}
</style>

<!-- Scripts -->
<script>
// Funciones del calendario
function verReservacion(id) {
    window.location.href = '<?= url('reservaciones/ver/') ?>' + id;
}

function crearReservacion(fecha, habitacionId) {
    window.location.href = '<?= url('reservaciones/crear') ?>?fecha_entrada=' + fecha + '&habitacion_id=' + habitacionId;
}

function irAHoy() {
    const hoy = new Date();
    const mes = hoy.getMonth() + 1;
    const año = hoy.getFullYear();
    window.location.href = '<?= url('reservaciones/calendario') ?>?mes=' + mes + '&año=' + año;
}

function exportarCalendario() {
    // Implementar exportación (CSV, PDF, etc.)
    alert('Función de exportación en desarrollo');
}

function imprimirCalendario() {
    window.print();
}

// Inicializar tooltips si está disponible Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }
    
    // Marcar el día actual con scroll automático
    const hoyElement = document.querySelector('.bg-blue-50');
    if (hoyElement && window.innerWidth > 768) {
        setTimeout(() => {
            hoyElement.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }, 100);
    }
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Flecha izquierda: mes anterior
    if (e.key === 'ArrowLeft' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        document.querySelector('a[href*="mes=<?= $mesAnterior ?>"]').click();
    }
    // Flecha derecha: mes siguiente
    if (e.key === 'ArrowRight' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        document.querySelector('a[href*="mes=<?= $mesSiguiente ?>"]').click();
    }
    // Tecla H: ir a hoy
    if (e.key === 'h' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        irAHoy();
    }
});

// FUNCIONES DE EXPORTACIÓN

// Función para exportar calendario a CSV
function exportarCalendario() {
    // Obtener el mes y año actuales del calendario
    const mesNombre = '<?= $meses[$mes] ?>';
    const año = '<?= $año ?>';
    
    // Crear el contenido CSV
    let csvContent = 'data:text/csv;charset=utf-8,';
    
    // Título
    csvContent += `Calendario de Reservaciones - ${mesNombre} ${año}\n\n`;
    
    // Encabezados
    csvContent += 'Habitacion,Tipo';
    for (let dia = 1; dia <= <?= $diasEnMes ?>; dia++) {
        csvContent += `,${dia}`;
    }
    csvContent += '\n';
    
    // Obtener datos de la tabla
    const tabla = document.querySelector('.calendar-table');
    const filas = tabla.querySelectorAll('tbody tr');
    
    filas.forEach((fila) => {
        // Habitación
        const habitacion = fila.querySelector('.sticky-column');
        const numeroHab = habitacion.querySelector('.text-amber-800').textContent.trim();
        const tipoHab = habitacion.querySelector('.text-gray-500') ? 
                        habitacion.querySelector('.text-gray-500').textContent.trim() : '';
        
        csvContent += `${numeroHab},${tipoHab}`;
        
        // Días
        const celdas = fila.querySelectorAll('td:not(.sticky-column)');
        celdas.forEach((celda) => {
            const reservacion = celda.querySelector('.reservation-block');
            if (reservacion) {
                // Extraer información de la reservación
                const titulo = reservacion.getAttribute('title') || '';
                const partes = titulo.split(' - ');
                const huesped = partes[0] || 'Reservado';
                const estado = partes[1] || '';
                
                // Limpiar para CSV
                const info = huesped.replace(/,/g, ';') + ' (' + estado + ')';
                csvContent += `,${info}`;
            } else {
                csvContent += ',Disponible';
            }
        });
        
        csvContent += '\n';
    });
    
    // Agregar resumen al final
    csvContent += '\n\nResumen del Mes\n';
    csvContent += `Total Reservaciones,<?= $totalReservaciones ?>\n`;
    csvContent += `Confirmadas,<?= $confirmadas ?>\n`;
    csvContent += `Check-in,<?= $checkIn ?>\n`;
    csvContent += `Check-out,<?= $checkOut ?>\n`;
    csvContent += `Canceladas,<?= $canceladas ?>\n`;
    csvContent += `Ocupacion Promedio,<?= $ocupacionPromedio ?>%\n`;
    
    // Crear enlace de descarga
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `calendario_${mesNombre}_${año}.csv`);
    document.body.appendChild(link);
    
    // Descargar
    link.click();
    document.body.removeChild(link);
    
    // Mostrar mensaje de éxito
    mostrarNotificacion('Calendario exportado exitosamente', 'success');
}

// Función mejorada para imprimir calendario
function imprimirCalendario() {
    // Agregar título temporal para impresión
    const titulo = document.createElement('div');
    titulo.className = 'print-title hidden';
    titulo.innerHTML = `
        <h1 style="text-align: center; margin-bottom: 20px;">
            Calendario de Reservaciones - <?= $meses[$mes] ?> <?= $año ?><br>
            <small>Los Cedros</small>
        </h1>
    `;
    
    const calendario = document.querySelector('.calendar-container');
    calendario.parentNode.insertBefore(titulo, calendario);
    
    // Imprimir
    window.print();
    
    // Remover título después de imprimir
    setTimeout(() => {
        titulo.remove();
    }, 1000);
}

// Función auxiliar para mostrar notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Si tienes toastr instalado
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensaje);
        return;
    }
    
    // Notificación simple sin librería
    const notificacion = document.createElement('div');
    notificacion.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: translateX(400px);
        transition: transform 0.3s ease-out;
    `;
    
    // Color según tipo
    const colores = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    notificacion.style.backgroundColor = colores[tipo] || colores.info;
    notificacion.textContent = mensaje;
    
    document.body.appendChild(notificacion);
    
    // Animación de entrada
    setTimeout(() => {
        notificacion.style.transform = 'translateX(0)';
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notificacion.style.transform = 'translateX(400px)';
        setTimeout(() => {
            document.body.removeChild(notificacion);
        }, 300);
    }, 3000);
}
</script>