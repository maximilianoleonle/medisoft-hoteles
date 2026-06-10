<?php
/**
 * Vista de configuración - Versión compacta
 * Vista hotelera
 */
?>

<!-- Estilos críticos inline para prevenir FOUC -->
<style>
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
}
.config-view { opacity: 0; transition: opacity 0.3s ease; }
.config-view.loaded { opacity: 1; }
.form-input {
    transition: all 0.3s ease;
    border: 1px solid #d1d5db;
}
.form-input:focus {
    border-color: var(--hotel-brown);
    box-shadow: 0 0 0 3px rgba(107, 68, 35, 0.1);
    outline: none;
}
.stat-card {
    background: linear-gradient(135deg, #ffffff, #f9fafb);
    border: 1px solid #e5e7eb;
}
.config-section {
    border-left: 3px solid var(--hotel-gold);
}
</style>

<div class="config-view min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Header Compacto -->
    <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white shadow-xl">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl font-bold font-playfair flex items-center gap-2">
                        <i class="fas fa-cog text-lg opacity-80"></i>
                        Configuración del Sistema
                        <span class="text-hotel-gold text-sm font-normal ml-2"><?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></span>
                    </h1>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?= url('configuracion/backup') ?>" 
                       class="bg-white/10 backdrop-blur text-white px-3 py-1.5 rounded-lg hover:bg-white/20 transition-all duration-300 flex items-center gap-1.5 border border-white/20 text-sm">
                        <i class="fas fa-database text-xs"></i>
                        <span>Respaldos</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container mx-auto px-4 py-4 max-w-7xl">
        <!-- Tarjetas de Estado del Sistema -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <!-- Último Respaldo -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Último Respaldo</p>
                        <?php if ($ultimo_backup): ?>
                            <p class="text-sm font-semibold text-gray-900">
                                <?= format_datetime($ultimo_backup['fecha']) ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                <?= $ultimo_backup['tamano'] ?>
                            </p>
                        <?php else: ?>
                            <p class="text-sm text-gray-500">Sin respaldos</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-blue-100 p-2.5 rounded-lg">
                        <i class="fas fa-database text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Espacio en Disco -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Espacio Usado</p>
                        <p class="text-sm font-semibold text-gray-900">
                            <?= format_file_size($espacio['usado']) ?>
                        </p>
                        <div class="w-full bg-gray-200 rounded-full h-1 mt-1">
                            <div class="bg-emerald-600 h-1 rounded-full" style="width: <?= round(($espacio['usado'] / $espacio['total']) * 100) ?>%"></div>
                        </div>
                    </div>
                    <div class="bg-emerald-100 p-2.5 rounded-lg">
                        <i class="fas fa-hdd text-emerald-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Usuarios Activos -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Usuarios Activos</p>
                        <p class="text-sm font-semibold text-gray-900">
                            <?= $usuarios_activos ?? 0 ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">En línea ahora</p>
                    </div>
                    <div class="bg-purple-100 p-2.5 rounded-lg">
                        <i class="fas fa-users text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <!-- Estado del Sistema -->
            <div class="stat-card rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Estado del Sistema</p>
                        <p class="text-sm font-semibold text-emerald-600">
                            Operativo
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5">Todos los servicios OK</p>
                    </div>
                    <div class="bg-emerald-100 p-2.5 rounded-lg">
                        <i class="fas fa-check-circle text-emerald-600"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de Configuración -->
        <form method="POST" action="<?= url('configuracion/actualizar') ?>" id="configForm">
            <?= csrf_field() ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Columna Izquierda -->
                <div class="space-y-4">
                    <!-- Información del Hotel -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-building text-hotel-brown text-sm"></i>
                            Información del Hotel
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Nombre del Hotel
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-hotel"></i>
                                    </span>
                                    <input type="text" 
                                           name="hotel_nombre" 
                                           value="<?= $config['hotel']['nombre'] ?? (function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles') ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Teléfono
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-phone"></i>
                                    </span>
                                    <input type="tel" 
                                           name="hotel_telefono" 
                                           value="<?= $config['hotel']['telefono'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="(555) 123-4567">
                                </div>
                            </div>
                            
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Dirección
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                    <input type="text" 
                                           name="hotel_direccion" 
                                           value="<?= $config['hotel']['direccion'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="Calle, Número, Colonia">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Email
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           name="hotel_email" 
                                           value="<?= $config['hotel']['email'] ?? '' ?>"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm"
                                           placeholder="hotel@ejemplo.com">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Horas por Estancia
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                    <input type="number" 
                                           name="hotel_horas_estancia" 
                                           value="<?= $config['hotel']['horas_estancia'] ?? 12 ?>"
                                           min="1" max="48"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Horarios -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs font-semibold text-gray-700 mb-2">Horarios</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">
                                        Check-in
                                    </label>
                                    <input type="time" 
                                           name="hotel_check_in_time" 
                                           value="<?= $config['hotel']['check_in_time'] ?? '15:00' ?>"
                                           class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">
                                        Check-out
                                    </label>
                                    <input type="time" 
                                           name="hotel_check_out_time" 
                                           value="<?= $config['hotel']['check_out_time'] ?? '12:00' ?>"
                                           class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuración de Tarifas -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-dollar-sign text-hotel-brown text-sm"></i>
                            Configuración de Tarifas
                        </h3>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Incremento Fin de Semana (%)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-percentage"></i>
                                    </span>
                                    <input type="number" 
                                           name="tarifas_incremento_fin_semana" 
                                           value="<?= $config['tarifas']['incremento_fin_semana'] ?? 0 ?>"
                                           min="0" max="100" step="0.1"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Aplicado viernes, sábado y domingo
                                </p>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Grupo Mínimo
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <input type="number" 
                                               name="tarifas_descuento_grupo_minimo" 
                                               value="<?= $config['tarifas']['descuento_grupo_minimo'] ?? 5 ?>"
                                               min="2" max="20"
                                               class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                                        Habitación Gratis
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                            <i class="fas fa-gift"></i>
                                        </span>
                                        <input type="number" 
                                               name="tarifas_descuento_grupo_gratis" 
                                               value="<?= $config['tarifas']['descuento_grupo_gratis'] ?? 20 ?>"
                                               min="5" max="50"
                                               class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500">
                                Una habitación gratis cada X habitaciones reservadas
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Columna Derecha -->
                <div class="space-y-4">
                    <!-- Configuración del Sistema -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-server text-hotel-brown text-sm"></i>
                            Configuración del Sistema
                        </h3>
                        
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Duración de Sesión (minutos)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-hourglass-half"></i>
                                    </span>
                                    <input type="number" 
                                           name="sistema_session_lifetime" 
                                           value="<?= $config['sistema']['session_lifetime'] ?? 120 ?>"
                                           min="15" max="480" step="15"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                            
                            <!-- Respaldos Automáticos -->
                            <div class="bg-gray-50 rounded-md p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" 
                                               name="sistema_backup_enabled" 
                                               value="1"
                                               <?= ($config['sistema']['backup_enabled'] ?? 0) ? 'checked' : '' ?>
                                               class="rounded text-hotel-brown focus:ring-hotel-brown">
                                        <span class="text-xs font-semibold text-gray-700">
                                            Respaldos Automáticos
                                        </span>
                                    </label>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3 mt-2">
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">
                                            Frecuencia
                                        </label>
                                        <select name="sistema_backup_frequency" 
                                                class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                            <option value="daily" <?= ($config['sistema']['backup_frequency'] ?? '') == 'daily' ? 'selected' : '' ?>>Diario</option>
                                            <option value="weekly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                            <option value="monthly" <?= ($config['sistema']['backup_frequency'] ?? '') == 'monthly' ? 'selected' : '' ?>>Mensual</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-xs text-gray-600 mb-1">
                                            Mantener últimos
                                        </label>
                                        <input type="number" 
                                               name="sistema_backup_keep_last" 
                                               value="<?= $config['sistema']['backup_keep_last'] ?? 4 ?>"
                                               min="1" max="30"
                                               class="form-input w-full px-3 py-1.5 rounded-md text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Inventario Automático -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2 config-section pl-3">
                            <i class="fas fa-boxes text-hotel-brown text-sm"></i>
                            Inventario Automático
                        </h3>
                        
                        <div class="space-y-3">
                            <p class="text-xs text-gray-600 mb-2">
                                Cantidad de productos a asignar automáticamente por habitación
                            </p>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Papel Higiénico (rollos)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-toilet-paper"></i>
                                    </span>
                                    <input type="number" 
                                           name="inventario_auto_papel_higienico" 
                                           value="<?= $config['inventario']['auto_papel_higienico'] ?? 2 ?>"
                                           min="0" max="10"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Jabón (unidades)
                                </label>
                                <div class="relative">
                                    <span class="absolute left-2.5 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm">
                                        <i class="fas fa-soap"></i>
                                    </span>
                                    <input type="number" 
                                           name="inventario_auto_jabon" 
                                           value="<?= $config['inventario']['auto_jabon'] ?? 2 ?>"
                                           min="0" max="10"
                                           class="form-input w-full pl-8 pr-3 py-1.5 rounded-md text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Sistema -->
                    <div class="bg-gray-100 rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-info-circle text-gray-600"></i>
                            Información del Sistema
                        </h4>
                        <div class="space-y-1 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span>Versión del Sistema:</span>
                                <span class="font-mono">v1.0.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span>PHP:</span>
                                <span class="font-mono"><?= phpversion() ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Base de Datos:</span>
                                <span class="font-mono">MySQL</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Servidor:</span>
                                <span class="font-mono"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botones de Acción -->
            <div class="bg-white rounded-lg shadow-sm p-3 mt-4">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <div class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-info-circle"></i>
                        Los cambios se aplicarán inmediatamente
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="<?= url('dashboard') ?>" 
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-all duration-300 flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-times text-xs"></i>
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-hotel-brown text-white rounded-md hover:bg-hotel-brown-dark transition-all duration-300 flex items-center gap-2 text-sm font-medium shadow hover:shadow-md">
                            <i class="fas fa-save text-xs"></i>
                            Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
// Formateo de teléfono
document.querySelector('input[name="hotel_telefono"]').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formattedValue = '';
    
    if (value.length > 0) {
        if (value.length <= 3) {
            formattedValue = `(${value}`;
        } else if (value.length <= 6) {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
        } else {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
        }
    }
    
    e.target.value = formattedValue;
});

// Validación del formulario
document.getElementById('configForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: '¿Guardar configuración?',
        text: 'Los cambios se aplicarán inmediatamente',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#6B4423',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-save mr-2"></i>Sí, guardar',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Toggle de respaldos automáticos
document.querySelector('input[name="sistema_backup_enabled"]').addEventListener('change', function() {
    const backupOptions = this.closest('.bg-gray-50').querySelectorAll('select, input[type="number"]');
    backupOptions.forEach(input => {
        input.disabled = !this.checked;
        input.style.opacity = this.checked ? '1' : '0.5';
    });
});

// Animación de entrada
document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.config-view');
    if (view) view.classList.add('loaded');
    
    // Trigger inicial del toggle
    const backupCheckbox = document.querySelector('input[name="sistema_backup_enabled"]');
    if (backupCheckbox) {
        backupCheckbox.dispatchEvent(new Event('change'));
    }
});
</script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
