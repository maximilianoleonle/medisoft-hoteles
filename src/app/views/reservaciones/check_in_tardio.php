<?php
/**
 * Vista: Check-in Tardío / Proceso Express - SIEMPRE CON PAGOS
 * Los Cedros
 */

$es_express = $verificacion['tipo'] == 'express';
$reservacion_id = $reservacion['id'];
?>

<div class="container mx-auto px-4 py-6 max-w-4xl">
    
    <!-- Header con alerta según tipo -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    <?= $es_express ? '⚡ Check-in/Check-out Express' : '⏰ Check-in Tardío' ?>
                </h1>
                <p class="text-gray-600 mt-1">Reservación #<?= $reservacion_id ?></p>
            </div>
            <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>" class="text-blue-600 hover:text-blue-800">
                ← Volver
            </a>
        </div>
        
        <!-- Alerta de advertencia -->
        <div class="<?= $es_express ? 'bg-orange-50 border-orange-500' : 'bg-yellow-50 border-yellow-500' ?> border-l-4 p-4 rounded">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($es_express): ?>
                        <svg class="h-6 w-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    <?php else: ?>
                        <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium <?= $es_express ? 'text-orange-800' : 'text-yellow-800' ?>">
                        <?= htmlspecialchars($verificacion['motivo']) ?>
                    </p>
                    <?php if ($es_express): ?>
                        <p class="mt-2 text-sm text-orange-700">
                            ⚠️ <strong>IMPORTANTE:</strong> Este proceso realizará check-in y check-out automáticamente 
                            en un solo paso, ya que la fecha de salida ya pasó.
                        </p>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-yellow-700">
                            💡 Puede registrar el pago en este momento o dejarlo pendiente para el check-out.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Información de la reservación -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">📋 Información de la Reservación</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Huésped -->
            <div>
                <p class="text-sm text-gray-600">Huésped</p>
                <p class="font-medium text-gray-900"><?= htmlspecialchars($huesped['nombre_completo']) ?></p>
            </div>
            
            <!-- Habitaciones -->
            <div>
                <p class="text-sm text-gray-600">Habitaciones</p>
                <p class="font-medium text-gray-900">
                    <?php
                    $nums = array_column($habitaciones, 'numero');
                    echo implode(', ', $nums);
                    ?>
                </p>
            </div>
            
            <!-- Fechas -->
            <div>
                <p class="text-sm text-gray-600">Fecha de Entrada</p>
                <p class="font-medium text-gray-900">
                    <?= date('d/m/Y', strtotime($reservacion['fecha_entrada'])) ?>
                    <?php if (isset($verificacion['dias_retraso']) && $verificacion['dias_retraso'] > 0): ?>
                        <span class="text-xs text-yellow-600 ml-2">
                            (hace <?= $verificacion['dias_retraso'] ?> día<?= $verificacion['dias_retraso'] > 1 ? 's' : '' ?>)
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div>
                <p class="text-sm text-gray-600">Fecha de Salida</p>
                <p class="font-medium text-gray-900">
                    <?= date('d/m/Y', strtotime($reservacion['fecha_salida'])) ?>
                    <?php if ($es_express && isset($verificacion['dias_pasados'])): ?>
                        <span class="text-xs text-orange-600 ml-2">
                            (hace <?= $verificacion['dias_pasados'] ?> día<?= $verificacion['dias_pasados'] > 1 ? 's' : '' ?>)
                        </span>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Total -->
            <div class="md:col-span-2">
                <p class="text-sm text-gray-600">Total a Pagar</p>
                <p class="text-2xl font-bold text-green-600">
                    <?= format_money($reservacion['precio_total']) ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <form method="POST" action="<?= url('reservaciones/procesar-check-in-tardio') ?>" id="formCheckInTardio">
        <?= csrf_field() ?>
        <input type="hidden" name="reservacion_id" value="<?= $reservacion_id ?>">
        <input type="hidden" name="tipo" value="<?= $verificacion['tipo'] ?>">
        
        <!-- Hora de Entrada (solo para check-in tardío normal) -->
        <?php if (!$es_express): ?>
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">🕐 Hora de Entrada</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Hora de Check-in <span class="text-red-500">*</span>
                </label>
                <input type="time" name="hora_entrada" value="<?= date('H:i') ?>" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                <p class="text-xs text-gray-500 mt-1">
                    Se usará la hora actual por defecto
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- SECCIÓN DE PAGOS - SIEMPRE VISIBLE -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">💳 Información de Pago</h2>
            
            <?php if (!$es_express): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-blue-800">
                    💡 <strong>Opcional:</strong> Puede registrar el pago ahora o dejarlo pendiente para el check-out.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Método de Pago -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Método de Pago <?= $es_express ? '<span class="text-red-500">*</span>' : '' ?>
                </label>
                <select name="metodo_pago" id="metodo_pago" class="w-full px-3 py-2 border border-gray-300 rounded-lg" <?= $es_express ? 'required' : '' ?>>
                    <option value="">-- Seleccionar --</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="mixto">Pago Mixto</option>
                    <option value="pendiente" <?= !$es_express ? 'selected' : '' ?>>Sin Pago (Pendiente)</option>
                </select>
            </div>
            
            <!-- Pago Simple -->
            <div id="pago_simple" style="display: none;">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Referencia (opcional)
                    </label>
                    <input type="text" name="referencia" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            
            <!-- Pago Mixto -->
            <div id="pago_mixto" style="display: none;">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <p class="text-sm text-blue-800 mb-2">
                        Total a pagar: <strong><?= format_money($reservacion['precio_total']) ?></strong>
                    </p>
                    <p class="text-sm text-blue-700">
                        Distribuya el pago entre los diferentes métodos:
                    </p>
                </div>
                
                <div class="space-y-3">
                    <!-- Efectivo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Efectivo</label>
                        <input type="number" name="efectivo" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg pago-input"
                               data-metodo="efectivo">
                    </div>
                    
                    <!-- Tarjeta -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tarjeta</label>
                        <input type="number" name="tarjeta" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg pago-input mb-2"
                               data-metodo="tarjeta">
                        <input type="text" name="referencia_tarjeta" placeholder="Referencia (opcional)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <!-- Transferencia -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transferencia</label>
                        <input type="number" name="transferencia" step="0.01" min="0" value="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg pago-input mb-2"
                               data-metodo="transferencia">
                        <input type="text" name="referencia_transferencia" placeholder="Referencia (opcional)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    
                    <!-- Total pagado -->
                    <div class="bg-gray-50 border border-gray-300 rounded-lg p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Ingresado:</span>
                            <span id="total_pagado" class="text-lg font-bold text-blue-600">$0.00</span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-600">Diferencia:</span>
                            <span id="diferencia" class="text-sm font-medium">$<?= number_format($reservacion['precio_total'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notas Adicionales -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">📝 Notas Adicionales</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Motivo del <?= $es_express ? 'proceso express' : 'retraso' ?> (opcional)
                </label>
                <textarea name="notas_adicionales" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                          placeholder="Ej: El personal olvidó hacer el check-in / El huésped llegó sin aviso"></textarea>
            </div>
        </div>
        
        <!-- Botones de Acción -->
        <div class="flex items-center justify-between">
            <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>" 
               class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Cancelar
            </a>
            
            <button type="submit" 
                    class="px-6 py-3 <?= $es_express ? 'bg-orange-600 hover:bg-orange-700' : 'bg-blue-600 hover:bg-blue-700' ?> text-white rounded-lg font-medium">
                <?php if ($es_express): ?>
                    ⚡ Procesar Express (Check-in + Check-out)
                <?php else: ?>
                    ✓ Realizar Check-in Tardío
                <?php endif; ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const metodoPago = document.getElementById('metodo_pago');
    const pagoSimple = document.getElementById('pago_simple');
    const pagoMixto = document.getElementById('pago_mixto');
    const totalReservacion = <?= $reservacion['precio_total'] ?>;
    
    // Cambiar visualización según método de pago
    if (metodoPago) {
        metodoPago.addEventListener('change', function() {
            const metodo = this.value;
            
            pagoSimple.style.display = 'none';
            pagoMixto.style.display = 'none';
            
            if (metodo === 'tarjeta' || metodo === 'transferencia') {
                pagoSimple.style.display = 'block';
            } else if (metodo === 'mixto') {
                pagoMixto.style.display = 'block';
                calcularTotalPagado(); // Inicializar cálculo
            }
        });
    }
    
    // Calcular total en pago mixto
    const pagoInputs = document.querySelectorAll('.pago-input');
    pagoInputs.forEach(input => {
        input.addEventListener('input', calcularTotalPagado);
    });
    
    function calcularTotalPagado() {
        let total = 0;
        
        pagoInputs.forEach(input => {
            const valor = parseFloat(input.value) || 0;
            total += valor;
        });
        
        const diferencia = totalReservacion - total;
        
        document.getElementById('total_pagado').textContent = '$' + total.toFixed(2);
        
        const diferenciaEl = document.getElementById('diferencia');
        diferenciaEl.textContent = '$' + Math.abs(diferencia).toFixed(2);
        
        if (diferencia > 0) {
            diferenciaEl.className = 'text-sm font-medium text-red-600';
        } else if (diferencia < 0) {
            diferenciaEl.className = 'text-sm font-medium text-green-600';
        } else {
            diferenciaEl.className = 'text-sm font-medium text-gray-600';
        }
    }
    
    // Validación antes de enviar
    const form = document.getElementById('formCheckInTardio');
    form.addEventListener('submit', function(e) {
        const metodo = metodoPago ? metodoPago.value : null;
        
        if (metodo === 'mixto') {
            let total = 0;
            pagoInputs.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            
            if (total <= 0) {
                e.preventDefault();
                alert('Debe ingresar al menos un monto en el pago mixto');
                return false;
            }
            
            if (total < totalReservacion) {
                if (!confirm('El total ingresado ($' + total.toFixed(2) + ') es menor al total de la reservación ($' + totalReservacion.toFixed(2) + ').\n\n¿Desea continuar de todas formas?')) {
                    e.preventDefault();
                    return false;
                }
            }
        }
        
        // Confirmación para proceso express
        <?php if ($es_express): ?>
        if (!confirm('¿Confirma que desea realizar el proceso EXPRESS?\n\nEsto hará check-in Y check-out automáticamente ya que la reservación está vencida.')) {
            e.preventDefault();
            return false;
        }
        <?php endif; ?>
    });
});
</script>