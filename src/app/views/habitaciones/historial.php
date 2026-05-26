<!-- app/views/habitaciones/historial.php - Historial Completo de Reservaciones -->
<style>
:root {
    --hotel-purple: #8B5CF6;
    --hotel-purple-dark: #7C3AED;
}

.vista-historial { 
    opacity: 0; 
    transition: opacity 0.4s ease;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.vista-historial.loaded { opacity: 1; }

/* Animación de entrada */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.reservacion-card {
    animation: fadeInUp 0.5s ease-out;
    animation-fill-mode: both;
}

.reservacion-card:nth-child(1) { animation-delay: 0.05s; }
.reservacion-card:nth-child(2) { animation-delay: 0.1s; }
.reservacion-card:nth-child(3) { animation-delay: 0.15s; }
.reservacion-card:nth-child(4) { animation-delay: 0.2s; }
.reservacion-card:nth-child(5) { animation-delay: 0.25s; }

.info-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.12);
}

.btn-modern {
    border: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}
</style>

<div class="vista-historial">
    <!-- Header -->
    <div class="bg-white shadow-lg border-b sticky top-0 z-30">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" 
                       class="text-purple-600 hover:text-purple-800 transition-all hover:scale-110">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            Historial Completo - Habitación <?= htmlspecialchars($habitacion['numero']) ?>
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-history mr-1 text-purple-500"></i>
                            <?= count($historial) ?> reservaciones en total
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 max-w-5xl">
        
        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <?php 
            $total_reservaciones = count($historial);
            $total_noches = 0;
            $total_ingresos = 0;
            $huespedes_unicos = [];
            
            foreach ($historial as $reservacion) {
                $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
                $fecha_salida = new DateTime($reservacion['fecha_salida']);
                $total_noches += $fecha_entrada->diff($fecha_salida)->days;
                
                if (isset($reservacion['precio_total']) && $reservacion['precio_total'] > 0) {
                    $total_ingresos += $reservacion['precio_total'];
                } elseif (isset($reservacion['total']) && $reservacion['total'] > 0) {
                    $total_ingresos += $reservacion['total'];
                }
                
                if (!empty($reservacion['huesped_id'])) {
                    $huespedes_unicos[$reservacion['huesped_id']] = true;
                }
            }
            ?>
            
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-calendar-check text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Total</span>
                </div>
                <div class="text-3xl font-bold"><?= $total_reservaciones ?></div>
                <div class="text-xs opacity-90 mt-1">Reservaciones</div>
            </div>
            
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-moon text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Noches</span>
                </div>
                <div class="text-3xl font-bold"><?= $total_noches ?></div>
                <div class="text-xs opacity-90 mt-1">Total de noches</div>
            </div>
            
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-dollar-sign text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Ingresos</span>
                </div>
                <div class="text-2xl font-bold"><?= format_money($total_ingresos) ?></div>
                <div class="text-xs opacity-90 mt-1">Total generado</div>
            </div>
            
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-4 text-white shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas fa-users text-2xl opacity-80"></i>
                    <span class="text-xs font-semibold bg-white/20 px-2 py-1 rounded-full">Únicos</span>
                </div>
                <div class="text-3xl font-bold"><?= count($huespedes_unicos) ?></div>
                <div class="text-xs opacity-90 mt-1">Huéspedes únicos</div>
            </div>
        </div>

        <!-- Filtros (Opcional - puedes expandir esto) -->
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <div class="flex items-center gap-3">
                <i class="fas fa-filter text-purple-600"></i>
                <span class="font-semibold text-gray-700">Filtros</span>
                <div class="flex-1"></div>
                <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent" onchange="filtrarPorAno(this.value)">
                    <option value="">Todos los años</option>
                    <?php 
                    $anos = [];
                    foreach ($historial as $reservacion) {
                        $ano = date('Y', strtotime($reservacion['fecha_salida']));
                        $anos[$ano] = true;
                    }
                    krsort($anos);
                    foreach (array_keys($anos) as $ano): ?>
                    <option value="<?= $ano ?>"><?= $ano ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Lista de Reservaciones -->
        <div class="space-y-3">
            <?php 
            $hoy = new DateTime();
            foreach ($historial as $index => $reservacion): 
                $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
                $fecha_salida = new DateTime($reservacion['fecha_salida']);
                $duracion = $fecha_entrada->diff($fecha_salida)->days;
                $dias_desde_salida = $fecha_salida->diff($hoy)->days;
                
                $es_reciente = $dias_desde_salida <= 7;
                
                $total_pagado = null;
                if (isset($reservacion['precio_total']) && $reservacion['precio_total'] > 0) {
                    $total_pagado = $reservacion['precio_total'];
                } elseif (isset($reservacion['total']) && $reservacion['total'] > 0) {
                    $total_pagado = $reservacion['total'];
                }
                
                $procedencia = '';
                if (!empty($reservacion['procedencia'])) {
                    $procedencia = $reservacion['procedencia'];
                } elseif (!empty($reservacion['estado_procedencia'])) {
                    $procedencia = $reservacion['estado_procedencia'];
                }
            ?>
            <div class="info-card p-4 reservacion-card" data-ano="<?= date('Y', strtotime($reservacion['fecha_salida'])) ?>">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">
                                <?= $index + 1 ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-lg">
                                    <?= htmlspecialchars($reservacion['nombre_huesped']) ?>
                                </h4>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <?php if ($es_reciente): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-800 font-bold">
                                        <i class="fas fa-star mr-1"></i>
                                        Reciente
                                    </span>
                                    <?php endif; ?>
                                    <?php if (isset($reservacion['total_habitaciones']) && $reservacion['total_habitaciones'] > 1): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-800 font-bold">
                                        <i class="fas fa-users mr-1"></i>
                                        Grupo (<?= $reservacion['total_habitaciones'] ?>)
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                            <div class="flex items-center gap-2 bg-green-50 rounded-lg p-2">
                                <i class="fas fa-sign-in-alt text-green-600"></i>
                                <div>
                                    <div class="text-xs text-gray-600 font-semibold">Check-in</div>
                                    <div class="text-sm font-bold text-gray-900"><?= $fecha_entrada->format('d/m/Y') ?></div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 bg-red-50 rounded-lg p-2">
                                <i class="fas fa-sign-out-alt text-red-600"></i>
                                <div>
                                    <div class="text-xs text-gray-600 font-semibold">Check-out</div>
                                    <div class="text-sm font-bold text-gray-900"><?= $fecha_salida->format('d/m/Y') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap gap-2">
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-blue-50 px-2 py-1 rounded">
                                <i class="fas fa-moon text-blue-600"></i>
                                <span class="font-semibold">
                                    <?= $duracion ?> <?= $duracion == 1 ? 'noche' : 'noches' ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                <i class="fas fa-calendar-alt text-gray-500"></i>
                                <span class="font-semibold">
                                    <?php if ($dias_desde_salida == 0): ?>
                                        Salió hoy
                                    <?php elseif ($dias_desde_salida == 1): ?>
                                        Ayer
                                    <?php elseif ($dias_desde_salida < 7): ?>
                                        Hace <?= $dias_desde_salida ?> días
                                    <?php elseif ($dias_desde_salida < 30): ?>
                                        Hace <?= floor($dias_desde_salida / 7) ?> <?= floor($dias_desde_salida / 7) == 1 ? 'sem.' : 'sem.' ?>
                                    <?php elseif ($dias_desde_salida < 365): ?>
                                        Hace <?= floor($dias_desde_salida / 30) ?> <?= floor($dias_desde_salida / 30) == 1 ? 'mes' : 'meses' ?>
                                    <?php else: ?>
                                        Hace <?= floor($dias_desde_salida / 365) ?> <?= floor($dias_desde_salida / 365) == 1 ? 'año' : 'años' ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <?php if ($total_pagado): ?>
                            <div class="flex items-center gap-1 text-xs text-gray-700 bg-emerald-50 px-2 py-1 rounded">
                                <i class="fas fa-dollar-sign text-emerald-600"></i>
                                <span class="font-bold"><?= format_money($total_pagado) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($procedencia)): ?>
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-orange-50 px-2 py-1 rounded">
                                <i class="fas fa-map-marker-alt text-orange-500"></i>
                                <span class="font-semibold"><?= htmlspecialchars($procedencia) ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservacion['telefono'])): ?>
                            <div class="flex items-center gap-1 text-xs text-gray-600 bg-purple-50 px-2 py-1 rounded">
                                <i class="fas fa-phone text-purple-500"></i>
                                <span class="font-semibold"><?= htmlspecialchars($reservacion['telefono']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($reservacion['observaciones']) || !empty($reservacion['vehiculos']) || !empty($reservacion['folio'])): ?>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <?php if (!empty($reservacion['folio'])): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-bold">
                                <i class="fas fa-hashtag mr-1"></i>
                                <?= htmlspecialchars($reservacion['folio']) ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservacion['vehiculos']) && $reservacion['vehiculos'] > 0): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-gray-200 text-gray-700 font-bold">
                                <i class="fas fa-car mr-1"></i>
                                <?= $reservacion['vehiculos'] > 1 ? $reservacion['vehiculos'] . ' veh.' : '1 veh.' ?>
                            </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservacion['observaciones'])): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 font-bold" 
                                  title="<?= htmlspecialchars($reservacion['observaciones']) ?>">
                                <i class="fas fa-sticky-note mr-1"></i>
                                Con obs.
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <a href="<?= url('/reservaciones/ver/' . $reservacion['id']) ?>"  
                           class="btn-modern bg-purple-600 text-white hover:bg-purple-700">
                            <i class="fas fa-eye"></i>
                            Ver
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($historial)): ?>
        <div class="bg-white rounded-xl shadow-md p-12 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-4">
                <i class="fas fa-inbox text-3xl text-purple-400"></i>
            </div>
            <p class="text-gray-700 font-bold text-lg">Sin historial de reservaciones</p>
            <p class="text-sm text-gray-500 mt-2">Esta habitación no tiene reservaciones registradas</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.vista-historial')?.classList.add('loaded');
});

function filtrarPorAno(ano) {
    const cards = document.querySelectorAll('.reservacion-card');
    cards.forEach(card => {
        if (ano === '' || card.dataset.ano === ano) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>
