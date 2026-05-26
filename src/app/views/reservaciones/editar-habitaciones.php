<?php
/**
 * Vista de Editar Habitaciones de Reservación - Versión Limpia y Funcional
 * Los Cedros
 */

// Validar que tenemos los datos necesarios
if (!isset($reservacion) || !isset($habitaciones) || !isset($habitaciones_seleccionadas)) {
    set_mensaje('Error: Datos incompletos', 'error');
    redirect('reservaciones');
    exit;
}

// Solo permitir edición en estado confirmada
if ($reservacion['estado'] !== 'confirmada') {
    set_mensaje('Solo se pueden modificar habitaciones en reservaciones confirmadas', 'warning');
    redirect('reservaciones/ver/' . $reservacion['id']);
    exit;
}

$habitaciones_ids = array_column($habitaciones_seleccionadas, 'habitacion_id');
$cortesias_actuales = array_column(array_filter($habitaciones_seleccionadas, function($h) {
    return $h['es_cortesia'] == 1;
}), 'habitacion_id');
?>

<style>
/* Variables CSS */
:root {
    --hotel-brown: #6B4423;
    --hotel-brown-dark: #5A3A1E;
    --hotel-gold: #D4A574;
    --hotel-cream: #FFF8F3;
}

/* Estilos base limpios */
.page-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding-top: 20px;
}

.main-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Header simple */
.page-header {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
    margin-bottom: 20px;
}

/* Cards base */
.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
}

.card-body {
    padding: 20px;
}

/* Grid de habitaciones */
.habitaciones-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 15px;
    margin-top: 20px;
}

/* Cards de habitación */
.habitacion-item {
    background: white;
    border: 3px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.habitacion-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* Estado seleccionado */
.habitacion-item.seleccionada {
    border-color: #28a745 !important;
    background: #f0f9ff !important;
    transform: scale(1.02);
}

.habitacion-item.seleccionada::after {
    content: '✓';
    position: absolute;
    top: 5px;
    right: 5px;
    background: #28a745;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

/* Estado ocupado */
.habitacion-item.ocupada {
    background: #fee;
    border-color: #dc3545;
    cursor: not-allowed;
    opacity: 0.7;
}

/* Estado cortesía */
.habitacion-item.es-cortesia {
    border-color: #ffc107 !important;
    background: #fffbf0 !important;
}

.badge-cortesia {
    display: inline-block;
    background: #ffc107;
    color: #000;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 8px;
}

/* Buscador */
.search-container {
    position: relative;
    margin-bottom: 20px;
}

.search-input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s;
}

.search-input:focus {
    outline: none;
    border-color: var(--hotel-brown);
}

.search-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

/* Botones */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: var(--hotel-brown);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: var(--hotel-brown-dark);
    transform: translateY(-1px);
}

.btn-primary:disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-warning:hover {
    background: #e0a800;
}

/* Alertas */
.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-info {
    background: #e7f3ff;
    color: #0c5da5;
    border: 1px solid #b8daff;
}

.alert-warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

/* Resumen */
.resumen-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.resumen-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.resumen-row:last-child {
    border-bottom: none;
}

/* Lista de habitaciones en resumen */
.habitaciones-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: white;
    border-radius: 6px;
    margin: 10px 0;
}

.habitacion-resumen-item {
    display: flex;
    justify-content: space-between;
    padding: 8px;
    border-bottom: 1px solid #f0f0f0;
}

.habitacion-resumen-item:last-child {
    border-bottom: none;
}

.habitacion-resumen-item.cortesia {
    background: #fffbf0;
    margin: 0 -8px;
    padding: 8px;
}

/* Info ocupación */
.info-ocupacion {
    background: white;
    border: 1px solid #dc3545;
    border-radius: 4px;
    padding: 8px;
    margin-top: 10px;
    font-size: 12px;
    color: #dc3545;
}

/* Responsive */

/* Tablets (hasta 1024px) */
@media (max-width: 1024px) {
    .main-container {
        padding: 0 15px;
    }
    
    .habitaciones-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
    }
}

/* Móviles (hasta 768px) */
@media (max-width: 768px) {
    .main-container {
        padding: 0 10px;
    }
    
    .page-header {
        padding: 15px;
    }
    
    .page-header h2 {
        font-size: 1.3rem;
    }
    
    .page-header > div {
        flex-direction: column;
        gap: 15px;
        align-items: stretch !important;
    }
    
    form > div {
        grid-template-columns: 1fr !important;
    }
    
    .card:last-child {
        position: static !important;
    }
    
    .habitaciones-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .habitacion-item {
        padding: 10px;
        font-size: 14px;
    }
    
    /* Modal responsive */
    .modal-content {
        width: 95%;
        margin: 20px auto;
    }
    
    .modal-body {
        padding: 15px;
        max-height: 70vh;
    }
    
    .modal-header {
        padding: 15px;
    }
    
    .modal-header h3 {
        font-size: 1.1rem;
    }
    
    .modal-footer {
        padding: 15px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-footer .btn {
        width: 100%;
        margin: 0 !important;
    }
    
    /* Botones más grandes para touch */
    .btn {
        padding: 12px 20px;
        font-size: 15px;
        min-height: 44px;
    }
    
    .search-input {
        padding: 10px 35px 10px 12px;
    }
    
    .card-body {
        padding: 15px;
    }
    
    .card-header {
        padding: 12px 15px;
    }
    
    /* Grid de cortesías en 1 columna */
    #gridCortesias {
        grid-template-columns: 1fr !important;
    }
}

/* Móviles pequeños (hasta 480px) */
@media (max-width: 480px) {
    .habitaciones-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header h2 {
        font-size: 1.2rem;
    }
}

/* Landscape móvil */
@media (max-width: 768px) and (orientation: landscape) {
    .habitaciones-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .modal-content {
        max-height: 85vh;
    }
}

/* Modal simple */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    width: 90%;
    max-width: 600px;
    margin: 50px auto;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 25px rgba(0,0,0,0.2);
}

.modal-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    background: #f8f9fa;
    padding: 15px 20px;
    border-top: 1px solid #e9ecef;
    text-align: right;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6c757d;
}
</style>

<div class="page-container">
    <div class="main-container">
        <!-- Header simple -->
        <div class="page-header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2 style="margin: 0 0 10px 0; color: #333;">
                        <i class="fas fa-bed" style="margin-right: 10px;"></i>
                        Modificar Habitaciones
                    </h2>
                    <p style="margin: 0; color: #666;">
                        Reservación #<?= $reservacion['id'] ?> - 
                        <?= htmlspecialchars($huesped['nombre_completo'] ?? '') ?> - 
                        <?= $noches ?> noche<?= $noches > 1 ? 's' : '' ?>
                    </p>
                </div>
                <a href="<?= url('reservaciones/ver/' . $reservacion['id']) ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left" style="margin-right: 5px;"></i>
                    Volver
                </a>
            </div>
        </div>

        <form id="formEditarHabitaciones" action="<?= url('reservaciones/actualizar-habitaciones') ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="reservacion_id" value="<?= $reservacion['id'] ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 20px;">
                <!-- Columna principal -->
                <div>
                    <div class="card">
                        <div class="card-header">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Habitaciones Disponibles</span>
                                <span id="contadorHabitaciones" style="background: #6c757d; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px;">
                                    0 seleccionadas
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Buscador -->
                            <div class="search-container">
                                <input type="text" 
                                       id="buscarHabitacion" 
                                       class="search-input" 
                                       placeholder="Buscar por número, tipo o piso...">
                                <i class="fas fa-search search-icon"></i>
                            </div>

                            <!-- Alerta de cortesías -->
                            <div id="alertaCortesias" class="alert alert-warning" style="display: none;">
                                <i class="fas fa-gift"></i>
                                <span id="mensajeCortesias"></span>
                            </div>

                            <!-- Grid de habitaciones -->
                            <div class="habitaciones-grid">
                                <?php foreach ($habitaciones as $hab): 
                                    $esta_seleccionada = in_array($hab['id'], $habitaciones_ids);
                                    $es_cortesia = in_array($hab['id'], $cortesias_actuales);
                                    $ocupada = !empty($hab['ocupacion']) && !$esta_seleccionada;
                                    
                                    $nombre_piso = [
                                        '-4' => '4 niveles abajo',
                                        '-2' => '2 niveles abajo', 
                                        '-1' => 'Un nivel abajo',
                                        '1' => 'Nivel de piso',
                                        '2' => '2º Nivel',
                                        '3' => '3º Nivel'
                                    ][$hab['piso']] ?? "Piso {$hab['piso']}";
                                ?>
                                    <div class="habitacion-item <?= $ocupada ? 'ocupada' : '' ?> <?= $esta_seleccionada ? 'seleccionada' : '' ?> <?= $es_cortesia ? 'es-cortesia' : '' ?>"
                                         data-habitacion-id="<?= $hab['id'] ?>"
                                         data-precio="<?= $hab['precio_con_incremento'] ?? $hab['precio_base'] ?>"
                                         data-numero="<?= $hab['numero'] ?>"
                                         data-tipo="<?= $hab['tipo'] ?>"
                                         data-piso="<?= $nombre_piso ?>"
                                         onclick="<?= !$ocupada ? 'toggleHabitacion(this)' : '' ?>">
                                        
                                        <?php if ($es_cortesia): ?>
                                            <div class="badge-cortesia">CORTESÍA</div>
                                        <?php endif; ?>
                                        
                                        <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">
                                            Hab. <?= $hab['numero'] ?>
                                        </div>
                                        
                                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                                            <i class="fas fa-building" style="margin-right: 5px;"></i><?= $nombre_piso ?>
                                        </div>
                                        
                                        <div style="font-size: 13px; margin-bottom: 10px;">
                                            <?= ucfirst($hab['tipo']) ?>
                                            <?php if (strpos($hab['tipo'], 'jacuzzi') !== false): ?>
                                                <i class="fas fa-hot-tub" style="color: #17a2b8; margin-left: 5px;"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($ocupada && !empty($hab['ocupacion'])): ?>
                                            <div class="info-ocupacion">
                                                <strong>Ocupada</strong><br>
                                                <?= date('d/m', strtotime($hab['ocupacion']['fecha_entrada'])) ?> - 
                                                <?= date('d/m', strtotime($hab['ocupacion']['fecha_salida'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="display: flex; justify-content: space-between; align-items: end;">
                                                <span style="font-size: 11px; color: #999;">Por noche</span>
                                                <span style="font-weight: 600; color: #333;">
                                                    $<?= number_format($hab['precio_con_incremento'] ?? $hab['precio_base'], 0) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <input type="checkbox" 
                                               name="habitaciones[]" 
                                               value="<?= $hab['id'] ?>" 
                                               class="habitacion-check"
                                               style="display: none;"
                                               <?= $esta_seleccionada ? 'checked' : '' ?>>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna de resumen -->
                <div>
                    <div class="card" style="position: sticky; top: 20px;">
                        <div class="card-header">
                            <i class="fas fa-calculator" style="margin-right: 8px;"></i>
                            Resumen de Cambios
                        </div>
                        <div class="card-body">
                            <div id="resumenReservacion">
                                <!-- Se llenará con JavaScript -->
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" 
                                        id="btnGuardar" 
                                        class="btn btn-primary" 
                                        style="width: 100%; margin-bottom: 10px;"
                                        disabled>
                                    <i class="fas fa-save" style="margin-right: 8px;"></i>
                                    Guardar Cambios
                                </button>
                                <a href="<?= url('reservaciones/ver/' . $reservacion['id']) ?>" 
                                   class="btn btn-secondary"
                                   style="width: 100%; display: block; text-decoration: none;">
                                    Cancelar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal de cortesías -->
<div id="modalCortesias" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin: 0;">
                <i class="fas fa-gift" style="margin-right: 8px; color: #ffc107;"></i>
                Seleccionar Cortesías
            </h3>
            <button class="close-modal" onclick="cerrarModalCortesias()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Cortesías disponibles: <span id="cortesiasDisponibles">0</span></strong><br>
                    <small>Por cada 11 habitaciones, 1 puede ser de cortesía</small>
                </div>
            </div>
            <div id="listaHabitacionesCortesia" style="margin-top: 20px;">
                <!-- Se llenará dinámicamente -->
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModalCortesias()" style="margin-right: 10px;">
                Cancelar
            </button>
            <button class="btn btn-warning" onclick="aplicarCortesias()">
                <i class="fas fa-check" style="margin-right: 5px;"></i>
                Aplicar Cortesías
            </button>
        </div>
    </div>
</div>

<script>
// Variables globales
let habitacionesSeleccionadas = [];
let habitacionesCortesiaSeleccionadas = <?= json_encode($cortesias_actuales) ?>;
const fechaEntrada = '<?= $reservacion['fecha_entrada'] ?>';
const fechaSalida = '<?= $reservacion['fecha_salida'] ?>';
const noches = <?= $noches ?>;
const precioOriginal = <?= $reservacion['precio_total'] ?>; // Precio total actual de la reservación

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Cargar habitaciones ya seleccionadas
    document.querySelectorAll('.habitacion-check:checked').forEach(function(checkbox) {
        const habitacionDiv = checkbox.closest('.habitacion-item');
        const hab = {
            id: habitacionDiv.dataset.habitacionId,
            numero: habitacionDiv.dataset.numero,
            tipo: habitacionDiv.dataset.tipo,
            piso: habitacionDiv.dataset.piso,
            precio: parseFloat(habitacionDiv.dataset.precio)
        };
        habitacionesSeleccionadas.push(hab);
    });
    
    actualizarContador();
    actualizarResumen();
    verificarCortesias();
    
    // Configurar búsqueda
    document.getElementById('buscarHabitacion').addEventListener('input', function(e) {
        const busqueda = e.target.value.toLowerCase();
        
        document.querySelectorAll('.habitacion-item').forEach(function(item) {
            const numero = item.dataset.numero.toLowerCase();
            const tipo = item.dataset.tipo.toLowerCase();
            const piso = item.dataset.piso.toLowerCase();
            
            if (numero.includes(busqueda) || tipo.includes(busqueda) || piso.includes(busqueda)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });
});

function toggleHabitacion(element) {
    const habitacionId = element.dataset.habitacionId;
    const checkbox = element.querySelector('.habitacion-check');
    const isChecked = checkbox.checked;
    
    if (!isChecked) {
        // Seleccionar habitación
        checkbox.checked = true;
        element.classList.add('seleccionada');
        
        const hab = {
            id: habitacionId,
            numero: element.dataset.numero,
            tipo: element.dataset.tipo,
            piso: element.dataset.piso,
            precio: parseFloat(element.dataset.precio)
        };
        habitacionesSeleccionadas.push(hab);
    } else {
        // Deseleccionar habitación
        checkbox.checked = false;
        element.classList.remove('seleccionada');
        element.classList.remove('es-cortesia');
        
        // Remover de seleccionadas
        habitacionesSeleccionadas = habitacionesSeleccionadas.filter(h => h.id !== habitacionId);
        
        // Remover de cortesías si estaba
        const indexCortesia = habitacionesCortesiaSeleccionadas.indexOf(habitacionId);
        if (indexCortesia > -1) {
            habitacionesCortesiaSeleccionadas.splice(indexCortesia, 1);
        }
    }
    
    actualizarContador();
    actualizarResumen();
    verificarCortesias();
}

function actualizarContador() {
    document.getElementById('contadorHabitaciones').textContent = 
        habitacionesSeleccionadas.length + ' seleccionadas';
}

function verificarCortesias() {
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);
    const cortesiasActuales = habitacionesCortesiaSeleccionadas.length;
    
    const alertaCortesias = document.getElementById('alertaCortesias');
    const mensajeCortesias = document.getElementById('mensajeCortesias');
    
    if (cortesiasDisponibles > 0) {
        alertaCortesias.style.display = 'flex';
        
        let mensaje = '';
        if (cortesiasActuales === 0) {
            mensaje = `Tienes <strong>${cortesiasDisponibles}</strong> cortesía${cortesiasDisponibles > 1 ? 's' : ''} disponible${cortesiasDisponibles > 1 ? 's' : ''}. `;
        } else if (cortesiasActuales < cortesiasDisponibles) {
            mensaje = `Usando <strong>${cortesiasActuales}</strong> de <strong>${cortesiasDisponibles}</strong> cortesía${cortesiasDisponibles > 1 ? 's' : ''} disponible${cortesiasDisponibles > 1 ? 's' : ''}. `;
        } else {
            mensaje = `Usando todas las cortesías disponibles (${cortesiasActuales}). `;
        }
        
        mensaje += `<button type="button" class="btn btn-warning" style="margin-left: 10px; padding: 5px 15px; font-size: 13px;" onclick="mostrarModalCortesias()">
                        <i class="fas fa-gift" style="margin-right: 5px;"></i>
                        ${cortesiasActuales === 0 ? 'Seleccionar' : 'Modificar'}
                    </button>`;
        
        mensajeCortesias.innerHTML = mensaje;
    } else {
        alertaCortesias.style.display = 'none';
        // Limpiar cortesías si no hay suficientes habitaciones
        if (habitacionesCortesiaSeleccionadas.length > 0) {
            habitacionesCortesiaSeleccionadas = [];
            document.querySelectorAll('.habitacion-item').forEach(item => {
                item.classList.remove('es-cortesia');
            });
        }
    }
}

function mostrarModalCortesias() {
    const modal = document.getElementById('modalCortesias');
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);
    
    document.getElementById('cortesiasDisponibles').textContent = cortesiasDisponibles;
    
    // Generar lista
    let html = '<div id="gridCortesias" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">';
    habitacionesSeleccionadas.forEach(hab => {
        const esCortesia = habitacionesCortesiaSeleccionadas.includes(hab.id);
        html += `
            <div style="border: 2px solid ${esCortesia ? '#ffc107' : '#e9ecef'}; 
                        background: ${esCortesia ? '#fffbf0' : 'white'}; 
                        padding: 15px; 
                        border-radius: 8px; 
                        cursor: pointer;
                        transition: all 0.3s;"
                 onclick="toggleCortesiaModal('${hab.id}', this)">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>Hab. ${hab.numero}</strong><br>
                        <small style="color: #666;">${hab.tipo}</small>
                    </div>
                    <input type="checkbox" 
                           class="cortesia-check"
                           data-hab-id="${hab.id}"
                           ${esCortesia ? 'checked' : ''}
                           style="width: 20px; height: 20px; cursor: pointer; pointer-events: none;">
                </div>
                <div style="margin-top: 10px; text-align: right;">
                    ${esCortesia ? '<span style="color: #ffc107; font-weight: 600;">CORTESÍA</span>' : 
                                  '<span style="color: #666;">$' + (hab.precio * noches).toLocaleString() + '</span>'}
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    document.getElementById('listaHabitacionesCortesia').innerHTML = html;
    modal.style.display = 'block';
}

function toggleCortesiaModal(habId, element) {
    const checkbox = element.querySelector('.cortesia-check');
    const totalHabs = habitacionesSeleccionadas.length;
    const cortesiasDisponibles = Math.floor(totalHabs / 11);
    const cortesiasActualesSeleccionadas = document.querySelectorAll('.cortesia-check:checked').length;
    
    // Si el checkbox ya está marcado, permitir desmarcarlo
    // Si no está marcado, verificar que no se exceda el límite
    if (!checkbox.checked && cortesiasActualesSeleccionadas >= cortesiasDisponibles) {
        alert(`Solo puedes seleccionar ${cortesiasDisponibles} habitación${cortesiasDisponibles > 1 ? 'es' : ''} de cortesía`);
        return;
    }
    
    // Toggle el checkbox
    checkbox.checked = !checkbox.checked;
    
    // Actualizar visual del contenedor
    if (checkbox.checked) {
        element.style.borderColor = '#ffc107';
        element.style.background = '#fffbf0';
        element.querySelector('div:last-child').innerHTML = '<span style="color: #ffc107; font-weight: 600;">CORTESÍA</span>';
    } else {
        element.style.borderColor = '#e9ecef';
        element.style.background = 'white';
        const hab = habitacionesSeleccionadas.find(h => h.id === habId);
        element.querySelector('div:last-child').innerHTML = '<span style="color: #666;">$' + (hab.precio * noches).toLocaleString() + '</span>';
    }
}

function aplicarCortesias() {
    habitacionesCortesiaSeleccionadas = [];
    
    document.querySelectorAll('.cortesia-check:checked').forEach(checkbox => {
        habitacionesCortesiaSeleccionadas.push(checkbox.dataset.habId);
    });
    
    // Actualizar visual en el grid principal
    document.querySelectorAll('.habitacion-item').forEach(item => {
        item.classList.remove('es-cortesia');
    });
    
    habitacionesCortesiaSeleccionadas.forEach(habId => {
        const elemento = document.querySelector(`.habitacion-item[data-habitacion-id="${habId}"]`);
        if (elemento) {
            elemento.classList.add('es-cortesia');
        }
    });
    
    cerrarModalCortesias();
    actualizarResumen();
    verificarCortesias();
}

function cerrarModalCortesias() {
    document.getElementById('modalCortesias').style.display = 'none';
}

function actualizarResumen() {
    const totalHabs = habitacionesSeleccionadas.length;
    const habsCortesia = habitacionesCortesiaSeleccionadas.length;
    
    let precioTotal = 0;
    let precioSinDescuento = 0;
    
    habitacionesSeleccionadas.forEach(hab => {
        // El precio YA incluye las noches, no multiplicar de nuevo
        const precioHab = hab.precio;
        precioSinDescuento += precioHab;
        
        if (!habitacionesCortesiaSeleccionadas.includes(hab.id)) {
            precioTotal += precioHab;
        }
    });
    
    let html = '';
    
    // Información básica
    html += `
        <div class="resumen-section">
            <div class="resumen-row">
                <span>Check-in:</span>
                <strong>${formatearFecha(fechaEntrada)}</strong>
            </div>
            <div class="resumen-row">
                <span>Check-out:</span>
                <strong>${formatearFecha(fechaSalida)}</strong>
            </div>
            <div class="resumen-row">
                <span>Noches:</span>
                <strong>${noches}</strong>
            </div>
            <div class="resumen-row">
                <span>Habitaciones:</span>
                <strong>${totalHabs}</strong>
            </div>
        </div>
    `;
    
    // Lista de habitaciones
    if (habitacionesSeleccionadas.length > 0) {
        html += `
            <div style="margin: 15px 0;">
                <strong style="display: block; margin-bottom: 10px;">Habitaciones seleccionadas:</strong>
                <div class="habitaciones-list">
        `;
        
        habitacionesSeleccionadas.forEach(hab => {
            const esCortesia = habitacionesCortesiaSeleccionadas.includes(hab.id);
            html += `
                <div class="habitacion-resumen-item ${esCortesia ? 'cortesia' : ''}">
                    <span>
                        Hab. ${hab.numero}
                        ${esCortesia ? '<span class="badge-cortesia" style="margin-left: 8px;">CORTESÍA</span>' : ''}
                    </span>
                    <span style="font-weight: 600; ${esCortesia ? 'text-decoration: line-through;' : ''}">
                        $${hab.precio.toLocaleString()}
                    </span>
                </div>
            `;
        });
        
        html += '</div></div>';
    }
    
    // Resumen de precios
    html += `
        <div class="resumen-section">
            <div class="resumen-row">
                <span>Precio original:</span>
                <strong>$${precioOriginal.toLocaleString()}</strong>
            </div>
            <div class="resumen-row">
                <span>Precio nuevo:</span>
                <strong style="font-size: 18px; color: var(--hotel-brown);">$${precioTotal.toLocaleString()}</strong>
            </div>
        </div>
    `;
    
    // Diferencia
    const diferencia = precioTotal - precioOriginal;
    if (diferencia !== 0) {
        html += `
            <div class="alert ${diferencia > 0 ? 'alert-warning' : 'alert-info'}" style="margin: 15px 0;">
                <i class="fas fa-${diferencia > 0 ? 'arrow-up' : 'arrow-down'}"></i>
                <span>
                    ${diferencia > 0 ? 'Aumento' : 'Reducción'}: 
                    <strong>${diferencia > 0 ? '+' : ''}$${Math.abs(diferencia).toLocaleString()}</strong>
                </span>
            </div>
        `;
    }
    
    document.getElementById('resumenReservacion').innerHTML = html;
    
    // Habilitar/deshabilitar botón
    document.getElementById('btnGuardar').disabled = totalHabs === 0;
}

function formatearFecha(fecha) {
    const [año, mes, dia] = fecha.split('-');
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${dia} ${meses[parseInt(mes)-1]} ${año}`;
}

// Manejar envío del formulario
document.getElementById('formEditarHabitaciones').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const totalHabs = habitacionesSeleccionadas.length;
    
    if (totalHabs === 0) {
        alert('Debe seleccionar al menos una habitación');
        return;
    }
    
    // Agregar campos ocultos para las cortesías
    document.querySelectorAll('.cortesia-hidden').forEach(input => input.remove());
    
    habitacionesCortesiaSeleccionadas.forEach(habId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'cortesias[]';
        input.value = habId;
        input.className = 'cortesia-hidden';
        this.appendChild(input);
    });
    
    // Confirmar con el usuario
    if (confirm('¿Confirmar cambios en la reservación?')) {
        this.submit();
    }
});
</script>