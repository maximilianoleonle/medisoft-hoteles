<?php
/**
 * Vista de Apertura de Caja
 * Los Cedros
 */
?>

<!-- Apertura de Caja -->
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
    <div class="max-w-md w-full">
        <!-- Logo o Imagen -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-hotel-gold to-yellow-600 rounded-full mb-4">
                <i class="fas fa-cash-register text-4xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-hotel-brown font-playfair">
                Apertura de Caja
            </h1>
            <p class="text-gray-600 mt-2">
                <?= date('l, d \d\e F \d\e Y') ?>
            </p>
            <p class="text-sm text-gray-500 mt-3 max-w-sm mx-auto">
                Abre un corte de caja para registrar movimientos de la jornada.
            </p>
        </div>
        
        <!-- Formulario -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark p-6">
                <h2 class="text-xl font-semibold text-white flex items-center">
                    <i class="fas fa-unlock mr-3"></i>
                    Abrir <?= htmlspecialchars($caja['nombre']) ?>
                </h2>
            </div>
            
            <form method="POST" action="<?= url('caja/abrir') ?>" class="p-6">
                <?= csrf_field() ?>
                <input type="hidden" name="caja_id" value="<?= $caja['id'] ?>">
                
                <div class="space-y-6">
                    <!-- Información del Usuario -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-user-circle mr-2"></i>
                            <strong>Usuario:</strong> <?= user_name() ?>
                        </p>
                        <p class="text-sm text-blue-800 mt-1">
                            <i class="fas fa-clock mr-2"></i>
                            <strong>Hora:</strong> <?= date('H:i:s') ?>
                        </p>
                    </div>
                    
                    <!-- Monto Inicial -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Monto Inicial en Efectivo
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-gray-500 text-lg">$</span>
                            <input type="number" 
                                   name="monto_inicial" 
                                   step="0.01" 
                                   min="0" 
                                   class="w-full pl-10 pr-4 py-3 text-lg border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-hotel-gold/20 focus:border-hotel-gold transition-all" 
                                   placeholder="0.00"
                                   value="0.00"
                                   required>
                        </div>
                        <p class="text-sm text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Captura el efectivo inicial con el que comienza este turno.
                        </p>
                    </div>
                    
                    <!-- Notas opcionales -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Observaciones (Opcional)
                        </label>
                        <textarea name="observaciones" 
                                  rows="3"
                                  class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-4 focus:ring-hotel-gold/20 focus:border-hotel-gold transition-all"
                                  placeholder="Alguna observación sobre el inicio de turno..."></textarea>
                    </div>
                    
                    <!-- Recordatorio -->
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <h4 class="text-sm font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Importante
                        </h4>
                        <ul class="text-sm text-yellow-700 space-y-1">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 text-yellow-600"></i>
                                <span>Verifica que el monto inicial sea correcto.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 text-yellow-600"></i>
                                <span>Una vez abierta, realiza el corte al finalizar la jornada.</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 text-yellow-600"></i>
                                <span>Todos los ingresos y gastos quedarán registrados en este corte.</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="flex gap-3 mt-8">
                    <a href="<?= url('dashboard') ?>" 
                       class="flex-1 px-6 py-3 border-2 border-gray-300 text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition-all text-center">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </a>
                    <button type="submit" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-hotel-gold to-yellow-600 text-white font-medium rounded-xl hover:shadow-xl transform hover:scale-105 transition-all">
                        <i class="fas fa-unlock mr-2"></i>
                        Abrir Caja
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Enlaces adicionales -->
        <div class="mt-6 text-center">
            <a href="<?= url('caja/historial') ?>" 
               class="text-sm text-gray-600 hover:text-hotel-brown transition">
                <i class="fas fa-history mr-1"></i>
                Ver historial de cortes anteriores
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Auto-focus en el campo de monto
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('input[name="monto_inicial"]').focus();
});

// Formatear el campo de monto mientras se escribe
document.querySelector('input[name="monto_inicial"]').addEventListener('input', function(e) {
    // Permitir solo números y punto decimal
    this.value = this.value.replace(/[^\d.]/g, '');
    
    // Permitir solo un punto decimal
    const parts = this.value.split('.');
    if (parts.length > 2) {
        this.value = parts[0] + '.' + parts.slice(1).join('');
    }
    
    // Limitar a 2 decimales
    if (parts[1] && parts[1].length > 2) {
        this.value = parts[0] + '.' + parts[1].substring(0, 2);
    }
});

// Confirmación antes de abrir
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const montoInicial = parseFloat(document.querySelector('input[name="monto_inicial"]').value) || 0;
    
    Swal.fire({
        title: '¿Confirmar apertura de caja?',
        html: `
            <div class="text-left">
                <p class="mb-2"><strong>Monto inicial:</strong> $${montoInicial.toFixed(2)}</p>
                <p class="mb-2"><strong>Usuario:</strong> <?= user_name() ?></p>
                <p class="mb-2"><strong>Fecha y hora:</strong> <?= date('d/m/Y H:i:s') ?></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#D4AF37',
        cancelButtonColor: '#6B7280',
        confirmButtonText: '<i class="fas fa-unlock mr-2"></i>Sí, abrir caja',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});
</script>
