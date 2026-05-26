<!-- app/views/habitaciones/ver.php - Versión Mejorada y Sin Restricciones -->
<style>
/* CSS crítico inline para carga rápida */
:root {
    --hotel-purple: #8B5CF6;
    --hotel-purple-dark: #7C3AED;
    --hotel-purple-light: #A78BFA;
    --hotel-emerald: #10B981;
    --hotel-blue: #3B82F6;
    --hotel-rose: #F43F5E;
}

.vista-habitacion { 
    opacity: 0; 
    transition: opacity 0.4s ease;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}
.vista-habitacion.loaded { opacity: 1; }

/* Estados mejorados y más vibrantes */
.estado-disponible { 
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
    border-left: 5px solid #10B981;
    box-shadow: 0 4px 6px rgba(16, 185, 129, 0.1);
}

.estado-por-llegar { 
    background: linear-gradient(135deg, #e9d5ff 0%, #d8b4fe 100%) !important;
    border-left: 5px solid #8B5CF6 !important;
    box-shadow: 0 4px 6px rgba(139, 92, 246, 0.1);
}

.estado-ocupada { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
    border-left: 5px solid #EF4444;
    box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);
}

.estado-mantenimiento { 
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
    border-left: 5px solid #F59E0B;
    box-shadow: 0 4px 6px rgba(245, 158, 11, 0.1);
}

.estado-limpieza { 
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
    border-left: 5px solid #3B82F6;
    box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
}

/* Cards con mejor diseño */
.info-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.info-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0,0,0,0.15);
}

/* Botones modernos y atractivos */
.btn-modern {
    border: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    padding: 0.625rem 1.25rem;
    border-radius: 0.625rem;
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

.btn-modern:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Gallery thumbnail activa */
.thumb-active {
    border: 3px solid #8B5CF6 !important;
    box-shadow: 0 0 0 4px rgba(139, 92, 246, 0.3);
    transform: scale(1.05);
}

/* Animación de pulso para elementos importantes */
@keyframes pulse-soft {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.pulse-soft {
    animation: pulse-soft 2s ease-in-out infinite;
}

/* Gradientes de fondo mejorados */
.bg-gradient-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.bg-gradient-emerald {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
}

.bg-gradient-purple {
    background: linear-gradient(135deg, #8B5CF6 0%, #7C3AED 100%);
}

.bg-gradient-rose {
    background: linear-gradient(135deg, #F43F5E 0%, #E11D48 100%);
}

.bg-gradient-amber {
    background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
}

/* Sombras personalizadas */
.shadow-modern {
    box-shadow: 0 10px 25px rgba(0,0,0,0.1), 0 6px 10px rgba(0,0,0,0.08);
}

/* Efecto glass morphism */
.glass-effect {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
}
</style>

<div class="vista-habitacion">
    <!-- Header Mejorado -->
    <div class="bg-white shadow-modern border-b sticky top-0 z-30">
        <div class="container mx-auto px-4 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center gap-3">
                    <a href="<?= url('habitaciones') ?>" 
                       class="text-purple-600 hover:text-purple-800 transition-all hover:scale-110">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            Habitación <?= htmlspecialchars($habitacion['numero']) ?>
                        </h1>
                        <p class="text-sm text-gray-600 mt-1">
                            <i class="fas fa-door-open mr-1 text-purple-500"></i>
                            <?= isset($tipos[$habitacion['tipo']]) ? $tipos[$habitacion['tipo']] : ucfirst($habitacion['tipo']) ?> • 
                            <?= Habitacion::getNombrePiso($habitacion['piso']) ?>
                        </p>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="<?= url('habitaciones/' . $habitacion['id'] . '/edit') ?>"
                       class="btn-modern bg-gradient-to-r from-gray-100 to-gray-200 text-gray-700 hover:from-gray-200 hover:to-gray-300">
                        <i class="fas fa-edit"></i>
                        <span class="hidden sm:inline">Editar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-4 max-w-6xl">
        <!-- Alerta de Reservación Pendiente Mejorada -->
        <?php if (isset($reservacion_pendiente) && $reservacion_pendiente): ?>
        <div class="mb-4 bg-gradient-purple rounded-xl shadow-modern p-5 transform hover:scale-102 transition-transform">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="bg-white/20 text-white p-3 rounded-xl pulse-soft">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <p class="font-bold text-white text-lg">
                            <?= htmlspecialchars($reservacion_pendiente['nombre_completo']) ?> - Llegada pendiente
                        </p>
                        <p class="text-sm text-purple-100 mt-1">
                            <i class="fas fa-clock mr-1"></i>
                            Hora estimada: <?= date('g:i A', strtotime($reservacion_pendiente['hora_llegada_estimada'])) ?>
                            <?php if ($reservacion_pendiente['total_habitaciones'] > 1): ?>
                            • <i class="fas fa-users mr-1"></i>Grupo de <?= $reservacion_pendiente['total_habitaciones'] ?> habitaciones
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <a href="<?= url('reservaciones/ver/' . $reservacion_pendiente['reservacion_id']) ?>" 
                   class="btn-modern bg-white text-purple-600 hover:bg-purple-50 font-bold">
                    <i class="fas fa-sign-in-alt"></i>
                    Check-in
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Layout Principal -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Columna Principal (2/3) -->
            <div class="lg:col-span-2 space-y-4">
                
                <!-- Estado y Precio Mejorado -->
                <div class="info-card p-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div>
                            <h2 class="text-base font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <i class="fas fa-info-circle text-purple-500"></i>
                                Estado Actual
                            </h2>
                            <?php 
                            $estado_actual = $habitacion['estado_display'] ?? $habitacion['estado'];
                            $estados_completos = $estados;
                            $estados_completos['por_llegar'] = ['label' => 'Por llegar', 'color' => 'purple', 'icon' => 'clock'];
                            $estadoInfo = $estados_completos[$estado_actual];
                            $colorClasses = [
                                'disponible' => 'bg-gradient-emerald',
                                'ocupada' => 'bg-gradient-rose',
                                'mantenimiento' => 'bg-gradient-amber',
                                'limpieza' => 'bg-blue-500',
                                'por_llegar' => 'bg-gradient-purple'
                            ][$estado_actual] ?? 'bg-gray-500';
                            ?>
                            <div class="inline-flex items-center gap-2 <?= $colorClasses ?> text-white px-4 py-2 rounded-lg shadow-lg">
                                <i class="fas fa-<?= $estadoInfo['icon'] ?>"></i>
                                <span class="font-bold"><?= $estadoInfo['label'] ?></span>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <?php if ($habitacion['tiene_incremento']): ?>
                                <div class="text-2xl font-bold text-purple-600">
                                    <?= format_money($habitacion['precio_actual']) ?>
                                </div>
                                <div class="text-sm text-gray-500 line-through">
                                    <?= format_money($habitacion['precio_base_original']) ?>
                                </div>
                                <div class="text-xs text-rose-600 font-bold mt-1">
                                    <i class="fas fa-arrow-up mr-1"></i>
                                    +<?= format_money($habitacion['incremento_total']) ?> tarifa dinámica
                                </div>
                            <?php else: ?>
                                <div class="text-2xl font-bold text-purple-600">
                                    <?= format_money($habitacion['precio_base']) ?>
                                </div>
                                <div class="text-xs text-gray-500">por noche</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas (SIN RESTRICCIONES) -->
                <div class="info-card p-4">
                    <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-500"></i>
                        Acciones Rápidas
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php if ($habitacion['estado'] == 'disponible'): ?>
                            <button onclick="mostrarModalMantenimiento()" 
                                    class="btn-modern bg-gradient-amber text-white hover:shadow-xl w-full justify-center">
                                <i class="fas fa-tools"></i>
                                Iniciar Mantenimiento
                            </button>
                            
                            <a href="<?= url('reservaciones/crear?habitacion=' . $habitacion['id']) ?>"
                               class="btn-modern bg-gradient-emerald text-white hover:shadow-xl w-full justify-center">
                                <i class="fas fa-calendar-plus"></i>
                                Nueva Reservación
                            </a>
                            
                            <button onclick="mostrarModalProgramarMantenimiento()" 
                                    class="btn-modern bg-gradient-to-r from-yellow-400 to-orange-400 text-white hover:shadow-xl w-full justify-center">
                                <i class="fas fa-calendar-check"></i>
                                Programar Mantenimiento
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($habitacion['estado'] == 'limpieza'): ?>
                        <form method="POST" action="<?= url('habitaciones/limpieza/' . $habitacion['id']) ?>" class="w-full">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="finalizar">
                            <button type="submit" class="btn-modern bg-gradient-emerald text-white hover:shadow-xl w-full justify-center">
                                <i class="fas fa-check-circle"></i>
                                Finalizar Limpieza
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- INFORMACIÓN DEL HUÉSPED ACTUAL (SIEMPRE QUE EXISTA) -->
<?php if ($ocupacion_actual): ?>
<div class="estado-ocupada rounded-lg p-4">
    <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
        <i class="fas fa-user-check text-red-600"></i>
        Información del Huésped Actual
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
        <div class="bg-white/60 rounded-lg p-2">
            <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Huésped</p>
            <p class="font-bold text-gray-900"><?= htmlspecialchars($ocupacion_actual['nombre_completo'] ?? '') ?></p>
        </div>
        <div class="bg-white/60 rounded-lg p-2">
            <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Salida</p>
            <p class="font-bold text-red-700">
                <?= format_date($ocupacion_actual['fecha_salida'] ?? '') ?>
                <?php if (($ocupacion_actual['fecha_salida'] ?? '') == date('Y-m-d')): ?>
                <span class="text-xs bg-yellow-500 text-white px-2 py-1 rounded-full ml-2">HOY</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="bg-white/60 rounded-lg p-2">
            <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Teléfono</p>
            <p class="font-bold text-gray-700 text-sm">
                <i class="fas fa-phone text-gray-400 mr-1"></i>
                <?= htmlspecialchars($ocupacion_actual['telefono'] ?? 'No registrado') ?>
            </p>
        </div>
        <div class="bg-white/60 rounded-lg p-2">
            <p class="text-xs text-gray-600 mb-1 font-semibold uppercase">Vehículos</p>
            <?php 
            // Obtener vehículos detallados del huésped
            if (class_exists('HuespedVehiculo') && isset($ocupacion_actual['huesped_id'])) {
                $vehiculoModel = new HuespedVehiculo();
                $vehiculos = $vehiculoModel->porHuesped($ocupacion_actual['huesped_id']);
                
                if (!empty($vehiculos)): ?>
                    <div class="space-y-1">
                        <?php foreach ($vehiculos as $vehiculo): ?>
                            <div class="text-xs">
                                <span class="font-bold text-gray-700">
                                    <i class="fas fa-car text-gray-400 mr-1"></i>
                                    <?= htmlspecialchars(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? '')) ?>
                                </span>
                                <span class="text-gray-600">(<?= htmlspecialchars($vehiculo['placas'] ?? '') ?>)</span>
                                <?php if (!empty($vehiculo['estacionamiento'])): ?>
                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium <?= get_estacionamiento_badge_class($vehiculo['estacionamiento']) ?>">
                                        <i class="fas <?= get_estacionamiento_icon($vehiculo['estacionamiento']) ?> mr-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $vehiculo['estacionamiento'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="font-bold text-gray-700 text-sm">
                        <i class="fas fa-car text-gray-400 mr-1"></i>
                        Sin vehículos registrados
                    </p>
                <?php endif;
            } else if (isset($ocupacion_actual['huesped_id'])) { ?>
                <p class="font-bold text-gray-700 text-sm">
                    <i class="fas fa-car text-gray-400 mr-1"></i>
                    <?= htmlspecialchars(get_resumen_vehiculos_huesped($ocupacion_actual['huesped_id']) ?? 'Sin vehículos') ?>
                </p>
            <?php } else { ?>
                <p class="font-bold text-gray-700 text-sm">
                    <i class="fas fa-car text-gray-400 mr-1"></i>
                    Sin vehículos registrados
                </p>
            <?php } ?>
        </div>
    </div>
    <div class="flex gap-2">
        <?php 
        // IMPORTANTE: Usar 'id' del array $ocupacion_actual que corresponde a r.id (reservacion_id)
        $reservacion_id = $ocupacion_actual['id'] ?? 0;
        ?>
        <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>"
           class="btn-modern bg-blue-500 text-white hover:bg-blue-600 w-full justify-center">
            <i class="fas fa-eye"></i>Ver Reservación Completa
        </a>
    </div>
</div>
<?php endif; ?>

                <!-- Galería de Imágenes Mejorada -->
                <?php 
                $habitacionImagenModel = new HabitacionImagen();
                $imagenes = $habitacionImagenModel->porHabitacion($habitacion['id']);
                $tiene_imagenes = !empty($imagenes);
                ?>

                <?php if ($tiene_imagenes): ?>
                <div class="info-card overflow-hidden">
                    <div class="relative">
                        <?php 
                        $imagen_principal = $imagenes[0];
                        foreach ($imagenes as $img) {
                            if ($img['es_principal']) {
                                $imagen_principal = $img;
                                break;
                            }
                        }
                        ?>
                        <img id="imagen-principal"
                             src="<?= image_url($imagen_principal['url']) ?>" 
                             alt="Habitación <?= htmlspecialchars($habitacion['numero']) ?>"
                             class="w-full h-56 sm:h-72 object-cover cursor-pointer hover:scale-105 transition-transform duration-500"
                             onclick="abrirLightbox(this.src)">
                        
                        <?php if (count($imagenes) > 1): ?>
                        <div class="absolute bottom-4 left-4 bg-black/60 text-white px-3 py-2 rounded-lg text-sm backdrop-blur-sm">
                            <i class="fas fa-images mr-2"></i>
                            <span id="contador"><?= count($imagenes) ?> fotos</span>
                        </div>
                        <?php endif; ?>
                        
                        <button onclick="abrirLightbox(document.getElementById('imagen-principal').src)" 
                                class="absolute top-4 right-4 bg-white/90 hover:bg-white text-gray-800 p-3 rounded-lg shadow-lg transition-all hover:scale-110">
                            <i class="fas fa-expand text-lg"></i>
                        </button>
                    </div>
                    
                    <?php if (count($imagenes) > 1): ?>
                    <div class="p-4 bg-gray-50">
                        <div class="flex overflow-x-auto gap-3 pb-2">
                            <?php foreach ($imagenes as $index => $imagen): ?>
                            <img src="<?= image_url($imagen['url']) ?>"
                                 class="w-24 h-24 object-cover rounded-lg cursor-pointer transition-all hover:scale-110 <?= $index === 0 ? 'thumb-active' : '' ?>"
                                 onclick="cambiarImagen('<?= image_url($imagen['url']) ?>', <?= $index ?>)">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="info-card p-12 text-center">
                    <i class="fas fa-images text-5xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 font-medium text-lg">No hay imágenes disponibles</p>
                    <p class="text-sm text-gray-400 mt-2">Agrega fotos para mostrar esta habitación</p>
                </div>
                <?php endif; ?>

                <!-- INFORMACIÓN DE MANTENIMIENTO (SI APLICA) -->
                <?php if ($habitacion['estado'] == 'mantenimiento' && isset($mantenimiento_actual) && $mantenimiento_actual): ?>
                <div class="estado-mantenimiento rounded-lg p-4">
                    <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-tools text-amber-600"></i>
                        Mantenimiento en Progreso
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                        <div class="bg-white/60 rounded-lg p-2">
                            <span class="text-xs text-gray-600 font-semibold uppercase block mb-1">Tipo:</span>
                            <span class="font-bold text-black">
                                <?= isset($mantenimiento_actual['tipo_mantenimiento']) 
                                    ? ucfirst(str_replace('_', ' ', $mantenimiento_actual['tipo_mantenimiento']))
                                    : 'No especificado' ?>
                            </span>
                        </div>
                        <div class="bg-white/60 rounded-lg p-2">
                            <span class="text-xs text-gray-600 font-semibold uppercase block mb-1">Prioridad:</span>
                            <?php 
                            $prioridad = isset($mantenimiento_actual['prioridad']) ? $mantenimiento_actual['prioridad'] : 'media';
                            $colorPrioridad = $prioridad == 'urgente' ? 'red' : ($prioridad == 'alta' ? 'orange' : 'gray');
                            ?>
                            <span class="font-bold text-<?= $colorPrioridad ?>-700">
                                <?= ucfirst($prioridad) ?>
                            </span>
                        </div>
                        <div class="col-span-full bg-white/60 rounded-lg p-2">
                            <span class="text-xs text-gray-600 font-semibold uppercase block mb-1">Motivo:</span>
                            <span class="font-bold text-black">
                                <?= isset($mantenimiento_actual['motivo']) 
                                    ? htmlspecialchars($mantenimiento_actual['motivo']) 
                                    : 'No especificado' ?>
                            </span>
                        </div>
                    </div>
                    <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/mantenimiento') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="finalizar">
                        <button type="submit" class="btn-modern bg-gradient-emerald text-white hover:shadow-xl w-full justify-center">
                            <i class="fas fa-check-circle"></i>Finalizar Mantenimiento
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- MANTENIMIENTOS PROGRAMADOS -->
                <?php if (!empty($mantenimientos_programados)): ?>
                <div class="info-card p-4" style="border: 2px solid #F59E0B; background: linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%);">
                    <h3 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-calendar-check text-amber-600"></i>
                        Mantenimientos Programados
                        <span class="bg-amber-500 text-white text-xs px-2 py-0.5 rounded-full"><?= count($mantenimientos_programados) ?></span>
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($mantenimientos_programados as $mp): ?>
                        <div class="bg-white rounded-lg p-3 border border-amber-200">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-bold text-amber-800 text-sm">
                                        <i class="fas fa-wrench mr-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $mp['tipo_mantenimiento'])) ?>
                                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full ml-1">
                                            <?= ucfirst($mp['prioridad'] ?? 'media') ?>
                                        </span>
                                    </p>
                                    <p class="text-xs text-gray-600 mt-1">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?= date('d/m/Y', strtotime($mp['fecha_programada'])) ?>
                                        <?php if (!empty($mp['fecha_programada_fin'])): ?>
                                            al <?= date('d/m/Y', strtotime($mp['fecha_programada_fin'])) ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($mp['motivo'])): ?>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-comment mr-1"></i><?= htmlspecialchars($mp['motivo']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="<?= url('habitaciones/cancelar-mantenimiento-programado/' . $mp['id']) ?>" 
                                      onsubmit="return confirm('¿Cancelar este mantenimiento programado?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="motivo_cancelacion" value="Cancelado manualmente">
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium hover:bg-red-50 px-2 py-1 rounded transition-all" title="Cancelar">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- OCUPACIÓN DESTACADA - PRÓXIMA ARRIBA, ÚLTIMA ABAJO -->
                <?php 
                $proxima_destacada = null;
                $ultima_destacada = null;
                
                $hoy = date('Y-m-d'); // Fecha de hoy en formato string
                
                if (!empty($historial_reciente)) {
                    $menor_dias_proxima = PHP_INT_MAX;
                    $menor_dias_ultima = PHP_INT_MAX;
                    
                    foreach ($historial_reciente as $res) {
                        // SOLO procesar si tiene estado definido
                        if (!isset($res['estado']) || empty($res['fecha_entrada']) || empty($res['fecha_salida'])) {
                            continue;
                        }
                        
                        // PRÓXIMA: confirmada Y fecha_entrada >= hoy
                        if ($res['estado'] === 'confirmada' && $res['fecha_entrada'] >= $hoy) {
                            $dias_hasta = (strtotime($res['fecha_entrada']) - strtotime($hoy)) / 86400;
                            if ($dias_hasta < $menor_dias_proxima) {
                                $menor_dias_proxima = $dias_hasta;
                                $proxima_destacada = $res;
                            }
                        }
                        
                        // ÚLTIMA: checked_out Y fecha_salida < hoy
                        if ($res['estado'] === 'checked_out' && $res['fecha_salida'] < $hoy) {
                            $dias_desde = (strtotime($hoy) - strtotime($res['fecha_salida'])) / 86400;
                            if ($dias_desde < $menor_dias_ultima) {
                                $menor_dias_ultima = $dias_desde;
                                $ultima_destacada = $res;
                            }
                        }
                    }
                }
                ?>
                
                <!-- PRÓXIMA RESERVACIÓN (PRIMERO) -->
                <?php if ($proxima_destacada): ?>
                <div class="info-card overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-check text-xl"></i>
                            <div>
                                <h3 class="font-bold">Próxima Reservación</h3>
                                <p class="text-xs opacity-90">Check-in programado</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-blue-50">
                        <?php 
                        $fecha_entrada_prox = new DateTime($proxima_destacada['fecha_entrada']);
                        $fecha_salida_prox = new DateTime($proxima_destacada['fecha_salida']);
                        $duracion_prox = $fecha_entrada_prox->diff($fecha_salida_prox)->days;
                        $hoy_dt = new DateTime();
                        $dias_hasta_entrada = $hoy_dt->diff($fecha_entrada_prox)->days;
                        
                        $total_pagado_prox = $proxima_destacada['precio_total'] ?? 0;
                        ?>
                        
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-lg mb-2">
                                    <?= htmlspecialchars($proxima_destacada['nombre_huesped']) ?>
                                </h4>
                                
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div class="bg-white rounded-lg p-2 border-2 border-blue-300">
                                        <div class="text-xs text-gray-600 font-semibold mb-1">
                                            <i class="fas fa-sign-in-alt text-green-600 mr-1"></i>Check-in
                                        </div>
                                        <div class="font-bold text-gray-900"><?= $fecha_entrada_prox->format('d/m/Y') ?></div>
                                    </div>
                                    <div class="bg-white rounded-lg p-2 border-2 border-blue-300">
                                        <div class="text-xs text-gray-600 font-semibold mb-1">
                                            <i class="fas fa-sign-out-alt text-red-600 mr-1"></i>Check-out
                                        </div>
                                        <div class="font-bold text-gray-900"><?= $fecha_salida_prox->format('d/m/Y') ?></div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-clock"></i>
                                        <span>
                                            <?php if ($dias_hasta_entrada == 0): ?>
                                                Llega HOY
                                            <?php elseif ($dias_hasta_entrada == 1): ?>
                                                Llega MAÑANA
                                            <?php else: ?>
                                                En <?= $dias_hasta_entrada ?> días
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded font-semibold">
                                        <i class="fas fa-moon"></i>
                                        <span><?= $duracion_prox ?> <?= $duracion_prox == 1 ? 'noche' : 'noches' ?></span>
                                    </div>
                                    
                                    <?php if ($total_pagado_prox > 0): ?>
                                    <div class="flex items-center gap-1 text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span><?= format_money($total_pagado_prox) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($proxima_destacada['telefono'])): ?>
                                    <div class="flex items-center gap-1 text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded font-semibold">
                                        <i class="fas fa-phone"></i>
                                        <span><?= htmlspecialchars($proxima_destacada['telefono']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($proxima_destacada['total_habitaciones']) && $proxima_destacada['total_habitaciones'] > 1): ?>
                                    <div class="flex items-center gap-1 text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-users"></i>
                                        <span>Grupo (<?= $proxima_destacada['total_habitaciones'] ?> hab.)</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <a href="<?= url('/reservaciones/ver/' . $proxima_destacada['id']) ?>"  
                                   class="btn-modern bg-purple-600 text-white hover:bg-purple-700 text-xs px-3 py-2">
                                    <i class="fas fa-eye"></i>
                                    Ver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ÚLTIMA OCUPACIÓN (SEGUNDO) -->
                <?php if ($ultima_destacada): ?>
                <div class="info-card overflow-hidden">
                    <div class="bg-gradient-to-r from-gray-600 to-gray-700 text-white p-3">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-history text-xl"></i>
                            <div>
                                <h3 class="font-bold">Última Ocupación</h3>
                                <p class="text-xs opacity-90">Reservación más reciente</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gray-50">
                        <?php 
                        $fecha_entrada_ult = new DateTime($ultima_destacada['fecha_entrada']);
                        $fecha_salida_ult = new DateTime($ultima_destacada['fecha_salida']);
                        $duracion_ult = $fecha_entrada_ult->diff($fecha_salida_ult)->days;
                        $hoy_dt = new DateTime();
                        $dias_desde_salida = $fecha_salida_ult->diff($hoy_dt)->days;
                        
                        $total_pagado_ult = $ultima_destacada['precio_total'] ?? 0;
                        ?>
                        
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 text-lg mb-2">
                                    <?= htmlspecialchars($ultima_destacada['nombre_huesped']) ?>
                                </h4>
                                
                                <div class="grid grid-cols-2 gap-2 mb-3">
                                    <div class="bg-white rounded-lg p-2 border-2 border-gray-300">
                                        <div class="text-xs text-gray-600 font-semibold mb-1">
                                            <i class="fas fa-sign-in-alt text-green-600 mr-1"></i>Check-in
                                        </div>
                                        <div class="font-bold text-gray-900"><?= $fecha_entrada_ult->format('d/m/Y') ?></div>
                                    </div>
                                    <div class="bg-white rounded-lg p-2 border-2 border-gray-300">
                                        <div class="text-xs text-gray-600 font-semibold mb-1">
                                            <i class="fas fa-sign-out-alt text-red-600 mr-1"></i>Check-out
                                        </div>
                                        <div class="font-bold text-gray-900"><?= $fecha_salida_ult->format('d/m/Y') ?></div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2">
                                    <div class="flex items-center gap-1 text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>
                                            <?php if ($dias_desde_salida == 0): ?>
                                                Salió hoy
                                            <?php elseif ($dias_desde_salida == 1): ?>
                                                Ayer
                                            <?php elseif ($dias_desde_salida < 7): ?>
                                                Hace <?= $dias_desde_salida ?> días
                                            <?php else: ?>
                                                Hace <?= floor($dias_desde_salida / 7) ?> semanas
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center gap-1 text-xs bg-indigo-50 text-indigo-700 px-2 py-1 rounded font-semibold">
                                        <i class="fas fa-moon"></i>
                                        <span><?= $duracion_ult ?> <?= $duracion_ult == 1 ? 'noche' : 'noches' ?></span>
                                    </div>
                                    
                                    <?php if ($total_pagado_ult > 0): ?>
                                    <div class="flex items-center gap-1 text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span><?= format_money($total_pagado_ult) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($ultima_destacada['telefono'])): ?>
                                    <div class="flex items-center gap-1 text-xs bg-purple-50 text-purple-700 px-2 py-1 rounded font-semibold">
                                        <i class="fas fa-phone"></i>
                                        <span><?= htmlspecialchars($ultima_destacada['telefono']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($ultima_destacada['total_habitaciones']) && $ultima_destacada['total_habitaciones'] > 1): ?>
                                    <div class="flex items-center gap-1 text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded font-bold">
                                        <i class="fas fa-users"></i>
                                        <span>Grupo (<?= $ultima_destacada['total_habitaciones'] ?> hab.)</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div>
                                <a href="<?= url('/reservaciones/ver/' . $ultima_destacada['id']) ?>"  
                                   class="btn-modern bg-purple-600 text-white hover:bg-purple-700 text-xs px-3 py-2">
                                    <i class="fas fa-eye"></i>
                                    Ver
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- HISTORIAL DE RESERVACIONES - ORDENADO POR FECHA MÁS RECIENTE -->
                <?php 
                // ORDENAR RESERVACIONES POR FECHA MÁS RECIENTE PRIMERO
                if (!empty($historial_reciente)) {
                    usort($historial_reciente, function($a, $b) {
                        $fechaA = strtotime($a['fecha_salida'] ?? '1970-01-01');
                        $fechaB = strtotime($b['fecha_salida'] ?? '1970-01-01');
                        return $fechaB - $fechaA; // Orden descendente (más reciente primero)
                    });
                }
                ?>
                
                <?php if (!empty($historial_reciente)): ?>
                <div class="info-card">
                    <div class="p-3 border-b bg-gradient-to-r from-purple-50 to-white">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-history text-purple-600"></i>
                                Historial de Reservaciones
                            </h3>
                            <span class="text-xs text-purple-600 bg-purple-100 px-2 py-1 rounded-full font-bold">
                                <i class="fas fa-calendar-check mr-1"></i>
                                Últimas <?= min(10, count($historial_reciente)) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="divide-y divide-gray-100">
                        <?php 
                        $hoy = new DateTime();
                        foreach (array_slice($historial_reciente, 0, 10) as $index => $reservacion): 
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
                        <div class="p-3 hover:bg-gradient-to-r hover:from-purple-50 hover:to-transparent transition-all group">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-start gap-2 mb-2">
                                        <h4 class="font-bold text-gray-900">
                                            <?= htmlspecialchars($reservacion['nombre_huesped']) ?>
                                        </h4>
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
                                    
                                    <div class="grid grid-cols-2 gap-2 mb-2">
                                        <div class="flex items-center gap-1 bg-green-50 rounded p-1.5 text-xs">
                                            <i class="fas fa-sign-in-alt text-green-600"></i>
                                            <span class="text-gray-700 font-semibold">
                                                <?= $fecha_entrada->format('d/m/Y') ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-1 bg-red-50 rounded p-1.5 text-xs">
                                            <i class="fas fa-sign-out-alt text-red-600"></i>
                                            <span class="text-gray-700 font-semibold">
                                                <?= $fecha_salida->format('d/m/Y') ?>
                                            </span>
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
                                                    Hace <?= floor($dias_desde_salida / 7) ?> <?= floor($dias_desde_salida / 7) == 1 ? 'semana' : 'semanas' ?>
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
                                            Obs.
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="ml-3">
                                    <a href="<?= url('/reservaciones/ver/' . $reservacion['id']) ?>"  
                                       class="btn-modern bg-purple-600 text-white hover:bg-purple-700 text-xs px-2 py-1.5">
                                        <i class="fas fa-eye"></i>
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($historial_reciente) > 10): ?>
                    <div class="p-3 border-t bg-gradient-to-r from-gray-50 to-white">
                        <a href="<?= url('/habitaciones/' . $habitacion['id'] . '/historial') ?>" 
                           class="inline-flex items-center gap-2 text-sm text-purple-600 hover:text-purple-700 font-bold hover:gap-3 transition-all">
                            <i class="fas fa-history"></i>
                            Ver historial completo (<?= count($historial_reciente) ?>)
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="info-card">
                    <div class="p-3 border-b bg-gradient-to-r from-purple-50 to-white">
                        <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-history text-purple-600"></i>
                            Historial de Reservaciones
                        </h3>
                    </div>
                    <div class="p-8 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-purple-100 rounded-full mb-4">
                            <i class="fas fa-inbox text-3xl text-purple-400"></i>
                        </div>
                        <p class="text-gray-700 font-bold text-lg">Sin reservaciones previas</p>
                        <p class="text-sm text-gray-500 mt-2">Esta habitación no tiene historial de huéspedes</p>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Columna lateral (1/3) -->
            <div class="lg:col-span-1">
                <!-- Información adicional de la habitación aquí si es necesario -->
            </div>
        </div>
        
        <!-- Botón de Ver Historial Completo - Siempre visible -->
        <div class="mt-6 mb-4">
            <div class="bg-white rounded-xl shadow-lg border-2 border-purple-200 overflow-hidden hover:shadow-2xl transition-all">
                <div class="bg-gradient-to-r from-purple-50 to-white p-4 border-b border-purple-100">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fas fa-history text-purple-600"></i>
                        Historial Completo
                    </h3>
                </div>
                <div class="p-6 text-center">
                    <div class="mb-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-3">
                            <i class="fas fa-calendar-alt text-3xl text-purple-600"></i>
                        </div>
                        <p class="text-gray-700 font-semibold text-lg mb-1">
                            Ver todas las reservaciones
                        </p>
                        <p class="text-sm text-gray-500">
                            Accede al historial completo con estadísticas detalladas
                        </p>
                    </div>
                    <a href="<?= url('/habitaciones/' . $habitacion['id'] . '/historial') ?>" 
                       class="btn-modern bg-gradient-purple text-white hover:from-purple-700 hover:to-purple-800 px-6 py-3 text-base font-bold shadow-lg">
                        <i class="fas fa-history"></i>
                        Ver Historial Completo
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Mantenimiento Mejorado -->
<div id="modalMantenimiento" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform scale-95 opacity-0 transition-all duration-300" id="modalContent">
        <div class="bg-gradient-amber text-white p-4 rounded-t-xl flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-tools text-xl"></i>
                <span class="font-bold text-lg">Iniciar Mantenimiento</span>
            </div>
            <button onclick="cerrarModalMantenimiento()" class="text-white/80 hover:text-white hover:scale-110 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/mantenimiento') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="iniciar">
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-wrench mr-1 text-amber-500"></i>
                    Tipo de Mantenimiento
                </label>
                <select name="tipo_mantenimiento" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="">Seleccione...</option>
                    <option value="preventivo">Preventivo</option>
                    <option value="correctivo">Correctivo</option>
                    <option value="emergencia">Emergencia</option>
                    <option value="limpieza_profunda">Limpieza Profunda</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i>
                    Prioridad
                </label>
                <select name="prioridad" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="baja">Baja</option>
                    <option value="media" selected>Media</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">Urgente</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-comment mr-1 text-amber-500"></i>
                    Motivo
                </label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModalMantenimiento()" 
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-bold transition-all hover:scale-105">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-3 bg-gradient-amber text-white rounded-lg text-sm font-bold transition-all hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-check mr-1"></i>
                    Iniciar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Programar Mantenimiento -->
<div id="modalProgramarMantenimiento" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md transform scale-95 opacity-0 transition-all duration-300" id="modalProgramarContent">
        <div class="text-white p-4 rounded-t-xl flex items-center justify-between" style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
            <div class="flex items-center gap-2">
                <i class="fas fa-calendar-check text-xl"></i>
                <span class="font-bold text-lg">Programar Mantenimiento</span>
            </div>
            <button onclick="cerrarModalProgramarMantenimiento()" class="text-white/80 hover:text-white hover:scale-110 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/programar-mantenimiento') ?>" class="p-6 space-y-4">
            <?= csrf_field() ?>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p class="text-xs text-blue-800">
                    <i class="fas fa-info-circle mr-1"></i>
                    La habitación seguirá disponible hasta la fecha programada, pero no se podrán hacer reservaciones que conflicten con las fechas del mantenimiento.
                </p>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-calendar mr-1 text-amber-500"></i>Fecha Inicio *
                    </label>
                    <input type="date" name="fecha_programada" required min="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                        <i class="fas fa-calendar-times mr-1 text-amber-500"></i>Fecha Fin
                    </label>
                    <input type="date" name="fecha_programada_fin" min="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <p class="text-xs text-gray-400 mt-1">Opcional</p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-wrench mr-1 text-amber-500"></i>Tipo de Mantenimiento
                </label>
                <select name="tipo_mantenimiento" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="">Seleccione...</option>
                    <option value="preventivo">Preventivo</option>
                    <option value="correctivo">Correctivo</option>
                    <option value="emergencia">Emergencia</option>
                    <option value="limpieza_profunda">Limpieza Profunda</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1 text-amber-500"></i>Prioridad
                </label>
                <select name="prioridad" required class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="baja">Baja</option>
                    <option value="media" selected>Media</option>
                    <option value="alta">Alta</option>
                    <option value="urgente">Urgente</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    <i class="fas fa-comment mr-1 text-amber-500"></i>Motivo
                </label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." 
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
            </div>
            
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="cerrarModalProgramarMantenimiento()" 
                        class="flex-1 px-4 py-3 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm font-bold transition-all hover:scale-105">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-3 text-white rounded-lg text-sm font-bold transition-all hover:scale-105 hover:shadow-lg"
                        style="background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);">
                    <i class="fas fa-calendar-check mr-1"></i>Programar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lightbox Mejorado con Navegación -->
<div id="lightbox" class="fixed inset-0 bg-black/95 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4" onclick="cerrarLightbox()">
    <div class="relative max-w-6xl max-h-full w-full" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] mx-auto object-contain rounded-lg shadow-2xl">
        
        <?php if (count($imagenes ?? []) > 1): ?>
        <button onclick="navegarLightbox(-1)" 
                class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full p-4 transition-all hover:scale-110">
            <i class="fas fa-chevron-left text-2xl"></i>
        </button>
        <button onclick="navegarLightbox(1)" 
                class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full p-4 transition-all hover:scale-110">
            <i class="fas fa-chevron-right text-2xl"></i>
        </button>
        
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 bg-white/10 backdrop-blur-sm text-white px-4 py-2 rounded-full font-bold">
            <span id="lightbox-counter">1 / <?= count($imagenes) ?></span>
        </div>
        
        <div class="absolute bottom-16 left-1/2 -translate-x-1/2 flex gap-2 max-w-full overflow-x-auto p-2">
            <?php foreach ($imagenes as $index => $imagen): ?>
            <img src="<?= image_url($imagen['url']) ?>"
                 class="w-20 h-20 object-cover rounded-lg cursor-pointer opacity-60 hover:opacity-100 transition-all hover:scale-110 lightbox-thumb"
                 onclick="cambiarImagenLightbox(<?= $index ?>)"
                 data-index="<?= $index ?>">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <button onclick="cerrarLightbox()" 
                class="absolute top-4 right-4 text-white bg-white/10 hover:bg-white/20 backdrop-blur-sm rounded-full p-4 transition-all hover:scale-110">
            <i class="fas fa-times text-2xl"></i>
        </button>
    </div>
</div>

<style>
/* Animación suave para hover en las cards */
.group:hover {
    background: linear-gradient(to right, #fafafa, #ffffff);
}

/* Mejora visual para los iconos */
.fas {
    width: 1em;
    text-align: center;
}

/* Tooltip mejorado para observaciones */
[title] {
    position: relative;
    cursor: help;
}

[title]:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.75rem;
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: white;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    white-space: normal;
    max-width: 300px;
    word-wrap: break-word;
    z-index: 10;
    margin-bottom: 0.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
        gap: 0.75rem;
    }
    
    .btn-modern {
        padding: 0.5rem 0.875rem;
        gap: 0.375rem;
    }
    
    .flex-wrap {
        font-size: 0.75rem;
    }
    
    .text-xs {
        font-size: 0.65rem;
    }
}

/* Animación de entrada suave */
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

.info-card {
    animation: fadeInUp 0.5s ease-out;
}
</style>

<script>
// Variables globales
let currentIndex = 0;
let lightboxIndex = 0;
const imagenes = <?= json_encode(array_map(function($img) { return image_url($img['url']); }, $imagenes ?? [])) ?>;

// Inicialización
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.vista-habitacion')?.classList.add('loaded');
    
    // Eventos de teclado para lightbox
    document.addEventListener('keydown', handleKeyPress);
    
    // Touch events para swipe en móvil
    let touchStartX = 0;
    let touchEndX = 0;
    
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });
        
        lightbox.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });
    }
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const diff = touchStartX - touchEndX;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                navegarLightbox(1); // Swipe izquierda = siguiente
            } else {
                navegarLightbox(-1); // Swipe derecha = anterior
            }
        }
    }
});

// Cambiar imagen principal
function cambiarImagen(url, index) {
    const imgPrincipal = document.getElementById('imagen-principal');
    if (imgPrincipal) {
        imgPrincipal.src = url;
        currentIndex = index;
        
        // Actualizar contador
        const contador = document.getElementById('contador');
        if (contador) {
            contador.textContent = `${index + 1} / ${imagenes.length}`;
        }
        
        // Actualizar thumbnails activos
        document.querySelectorAll('.overflow-x-auto img').forEach((img, i) => {
            if (i === index) {
                img.classList.add('thumb-active');
            } else {
                img.classList.remove('thumb-active');
            }
        });
    }
}

// Lightbox con ocultación de sidebar
function abrirLightbox(url) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }
    
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    
    if (lightbox && img) {
        lightboxIndex = imagenes.indexOf(url);
        if (lightboxIndex === -1) lightboxIndex = 0;
        
        img.src = url;
        lightbox.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        actualizarLightbox();
    }
}

function cerrarLightbox() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }
    
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

// Navegación en lightbox
function navegarLightbox(direccion) {
    if (imagenes.length <= 1) return;
    
    lightboxIndex = (lightboxIndex + direccion + imagenes.length) % imagenes.length;
    actualizarLightbox();
}

function cambiarImagenLightbox(index) {
    lightboxIndex = index;
    actualizarLightbox();
}

function actualizarLightbox() {
    const imgLightbox = document.getElementById('lightbox-img');
    if (imgLightbox) {
        imgLightbox.src = imagenes[lightboxIndex];
    }
    
    const counter = document.getElementById('lightbox-counter');
    if (counter) {
        counter.textContent = `${lightboxIndex + 1} / ${imagenes.length}`;
    }
    
    document.querySelectorAll('.lightbox-thumb').forEach((thumb, index) => {
        if (index === lightboxIndex) {
            thumb.classList.remove('opacity-60');
            thumb.classList.add('opacity-100', 'ring-4', 'ring-white', 'scale-110');
        } else {
            thumb.classList.add('opacity-60');
            thumb.classList.remove('opacity-100', 'ring-4', 'ring-white', 'scale-110');
        }
    });
}

// Manejo de teclado
function handleKeyPress(e) {
    const lightbox = document.getElementById('lightbox');
    if (lightbox && lightbox.classList.contains('hidden')) return;
    
    switch(e.key) {
        case 'Escape':
            cerrarLightbox();
            break;
        case 'ArrowLeft':
            navegarLightbox(-1);
            break;
        case 'ArrowRight':
            navegarLightbox(1);
            break;
    }
}

// Modal de mantenimiento con ocultación de sidebar
function mostrarModalMantenimiento() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = 'none';
    }
    
    const modal = document.getElementById('modalMantenimiento');
    const content = document.getElementById('modalContent');
    
    if (modal && content) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        setTimeout(() => {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }, 10);
    }
}

function cerrarModalMantenimiento() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.style.display = '';
    }
    
    const modal = document.getElementById('modalMantenimiento');
    const content = document.getElementById('modalContent');
    
    if (modal && content) {
        content.style.transform = 'scale(0.95)';
        content.style.opacity = '0';
        document.body.classList.remove('overflow-hidden');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            const form = modal.querySelector('form');
            if (form) form.reset();
        }, 300);
    }
}

// Checkout
function realizarCheckout(reservacionId) {
    if (confirm('¿Realizar check-out de esta habitación?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('reservaciones/check-out/') ?>' + reservacionId;
        
        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = 'csrf_token';
        csrfField.value = '<?= csrf_token() ?>';
        form.appendChild(csrfField);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Cerrar modal al hacer clic fuera
const modalMantenimiento = document.getElementById('modalMantenimiento');
if (modalMantenimiento) {
    modalMantenimiento.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalMantenimiento();
    });
}

// Modal Programar Mantenimiento
function mostrarModalProgramarMantenimiento() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
    
    const modal = document.getElementById('modalProgramarMantenimiento');
    const content = document.getElementById('modalProgramarContent');
    
    if (modal && content) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        setTimeout(() => {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }, 10);
    }
}

function cerrarModalProgramarMantenimiento() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = '';
    
    const modal = document.getElementById('modalProgramarMantenimiento');
    const content = document.getElementById('modalProgramarContent');
    
    if (modal && content) {
        content.style.transform = 'scale(0.95)';
        content.style.opacity = '0';
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => {
            modal.classList.add('hidden');
            const form = modal.querySelector('form');
            if (form) form.reset();
        }, 300);
    }
}

const modalProgramar = document.getElementById('modalProgramarMantenimiento');
if (modalProgramar) {
    modalProgramar.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalProgramarMantenimiento();
    });
}
</script>