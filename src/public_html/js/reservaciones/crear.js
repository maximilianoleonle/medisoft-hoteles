// Variables globales
let habitacionesSeleccionadas = [];
let precioCalculado = 0;

function apiFetch(url) {
    return fetch(url, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
}

function escHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[ch]));
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar eventos
    inicializarEventos();
    
    // Cargar habitaciones disponibles inicial
    actualizarHabitacionesDisponibles();
});

function inicializarEventos() {
    // Eventos de cambio de fecha
    const fechaEntrada = document.getElementById('fecha_entrada');
    const fechaSalida = document.getElementById('fecha_salida');
    
    if (fechaEntrada) {
        fechaEntrada.addEventListener('change', function() {
            actualizarFechaSalida();
            actualizarHabitacionesDisponibles();
        });
    }
    
    if (fechaSalida) {
        fechaSalida.addEventListener('change', actualizarHabitacionesDisponibles);
    }
    
    // Evento de cambio de hora (afecta el precio por llegada en madrugada)
    const horaLlegada = document.getElementById('hora_llegada');
    if (horaLlegada) {
        horaLlegada.addEventListener('change', actualizarPrecio);
    }
    
    // Evento de búsqueda de huésped
    const buscarHuespedBtn = document.getElementById('buscar-huesped');
    if (buscarHuespedBtn) {
        buscarHuespedBtn.addEventListener('click', buscarHuesped);
    }
}

function actualizarFechaSalida() {
    const fechaEntrada = document.getElementById('fecha_entrada');
    const fechaSalida = document.getElementById('fecha_salida');
    
    if (fechaEntrada.value) {
        // Establecer fecha mínima de salida como el día siguiente a la entrada
        const fecha = new Date(fechaEntrada.value);
        fecha.setDate(fecha.getDate() + 1);
        const fechaMin = fecha.toISOString().split('T')[0];
        
        fechaSalida.min = fechaMin;
        
        // Si la fecha de salida es anterior a la nueva fecha mínima, actualizarla
        if (!fechaSalida.value || fechaSalida.value <= fechaEntrada.value) {
            fechaSalida.value = fechaMin;
        }
    }
}

async function actualizarHabitacionesDisponibles() {
    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;
    
    if (!fechaEntrada || !fechaSalida) {
        return;
    }
    
    try {
        // Mostrar loading
        mostrarLoading('habitaciones-container', 'Cargando habitaciones disponibles...');
        
        // Llamar API para obtener habitaciones disponibles
        const response = await apiFetch(`/api/habitaciones/disponibles?fecha_entrada=${encodeURIComponent(fechaEntrada)}&fecha_salida=${encodeURIComponent(fechaSalida)}`);
        
        if (!response.ok) {
            throw new Error('Error al cargar habitaciones');
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarHabitacionesDisponibles(data.data);
            // Actualizar precio si hay habitaciones seleccionadas
            if (habitacionesSeleccionadas.length > 0) {
                actualizarPrecio();
            }
        } else {
            throw new Error(data.message || 'Error al cargar habitaciones');
        }
        
    } catch (error) {
        console.error('Error:', error);
        mostrarError('habitaciones-container', 'No se pudieron cargar las habitaciones: ' + error.message);
    }
}

function mostrarHabitacionesDisponibles(habitaciones) {
    const container = document.getElementById('habitaciones-container');
    
    if (habitaciones.length === 0) {
        container.innerHTML = `
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-800">No hay habitaciones disponibles para las fechas seleccionadas.</p>
            </div>`;
        return;
    }
    
    // Agrupar habitaciones por tipo
    const habitacionesPorTipo = habitaciones.reduce((acc, hab) => {
        if (!acc[hab.tipo]) {
            acc[hab.tipo] = [];
        }
        acc[hab.tipo].push(hab);
        return acc;
    }, {});
    
    let html = '<div class="space-y-6">';
    
    for (const [tipo, habs] of Object.entries(habitacionesPorTipo)) {
        html += `
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="font-semibold text-lg mb-3 capitalize">${escHtml(tipo.replace('_', ' '))}</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        `;
        
        habs.forEach(hab => {
            const isSelected = habitacionesSeleccionadas.includes(hab.id.toString());
            const habNumero = escHtml(hab.numero);
            const habTipo = escHtml(hab.tipo);
            const fotoUrl = escHtml(hab.foto_url || '');
            html += `
                <label class="relative flex items-center p-3 bg-white border rounded-lg cursor-pointer hover:border-hotel-brown transition-colors ${isSelected ? 'border-hotel-brown bg-hotel-cream' : 'border-gray-200'}">
                    <input type="checkbox" 
                           name="habitaciones[]" 
                           value="${hab.id}" 
                           class="habitacion-checkbox mr-3"
                           data-numero="${habNumero}"
                           data-tipo="${habTipo}"
                           data-precio="${hab.precio_base}"
                           ${isSelected ? 'checked' : ''}
                           onchange="toggleHabitacion(this)">
                    <div class="flex-1">
                        <span class="font-medium">Habitación ${habNumero}</span>
                        <div class="text-sm text-gray-600">
                            <span class="precio-habitacion">$${formatMoney(hab.precio_base)}</span>/noche
                        </div>
                    </div>
                    ${fotoUrl ? `<img src="${fotoUrl}" alt="Hab ${habNumero}" class="w-16 h-16 rounded object-cover ml-2">` : ''}
                </label>
            `;
        });
        
        html += '</div></div>';
    }
    
    html += '</div>';
    container.innerHTML = html;
    
    // Restaurar selecciones previas
    actualizarContadorHabitaciones();
}

function toggleHabitacion(checkbox) {
    const habitacionId = checkbox.value;
    
    if (checkbox.checked) {
        if (!habitacionesSeleccionadas.includes(habitacionId)) {
            habitacionesSeleccionadas.push(habitacionId);
        }
    } else {
        habitacionesSeleccionadas = habitacionesSeleccionadas.filter(id => id !== habitacionId);
    }
    
    actualizarContadorHabitaciones();
    actualizarPrecio();
}

function actualizarContadorHabitaciones() {
    const contador = document.getElementById('habitaciones-seleccionadas');
    if (contador) {
        const total = habitacionesSeleccionadas.length;
        contador.textContent = `${total} habitación${total !== 1 ? 'es' : ''} seleccionada${total !== 1 ? 's' : ''}`;
        
        // Mostrar/ocultar sección de precio
        const seccionPrecio = document.getElementById('seccion-precio');
        if (seccionPrecio) {
            seccionPrecio.style.display = total > 0 ? 'block' : 'none';
        }
    }
}

async function actualizarPrecio() {
    if (habitacionesSeleccionadas.length === 0) {
        return;
    }
    
    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;
    const horaLlegada = document.getElementById('hora_llegada').value;
    
    if (!fechaEntrada || !fechaSalida) {
        return;
    }
    
    try {
        // Mostrar loading en precio
        const precioContainer = document.getElementById('precio-total-container');
        if (precioContainer) {
            precioContainer.innerHTML = '<div class="animate-pulse">Calculando precio...</div>';
        }
        
        // Llamar API para calcular precio con tarifas dinámicas
        const params = new URLSearchParams({
            habitaciones: habitacionesSeleccionadas.join(','),
            fecha_entrada: fechaEntrada,
            fecha_salida: fechaSalida,
            hora_llegada: horaLlegada
        });
        
        const response = await apiFetch(`/api/habitaciones/calcular-precio?${params}`);
        
        if (!response.ok) {
            throw new Error('Error al calcular precio');
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarDetallesPrecio(data.data);
            precioCalculado = data.data.precio_total;
        } else {
            throw new Error(data.message || 'Error al calcular precio');
        }
        
    } catch (error) {
        console.error('Error:', error);
        const precioContainer = document.getElementById('precio-total-container');
        if (precioContainer) {
            precioContainer.innerHTML = '<div class="text-red-600">Error al calcular precio</div>';
        }
    }
}

function mostrarDetallesPrecio(datos) {
    const precioContainer = document.getElementById('precio-total-container');
    if (!precioContainer) return;
    
    let html = `
        <div class="space-y-3">
            <div class="flex justify-between text-gray-600">
                <span>Precio por noche:</span>
                <span>$${formatMoney(datos.precio_por_noche)}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Número de noches:</span>
                <span>${datos.noches}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Total habitaciones:</span>
                <span>${datos.total_habitaciones}</span>
            </div>
    `;
    
    // Mostrar descuento si aplica
    if (datos.habitaciones_cortesia > 0) {
        html += `
            <div class="flex justify-between text-green-600 font-medium">
                <span>Habitaciones de cortesía:</span>
                <span>${datos.habitaciones_cortesia}</span>
            </div>
            <div class="flex justify-between text-green-600">
                <span>Ahorro:</span>
                <span>-$${formatMoney(datos.ahorro)}</span>
            </div>
        `;
    }
    
    // Mostrar alerta de madrugada si aplica
    if (datos.es_madrugada) {
        html += `
            <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm">
                <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                <span class="text-yellow-800">Llegada en horario de madrugada (00:00 - 05:59)</span>
            </div>
        `;
    }
    
    html += `
            <div class="border-t pt-3">
                <div class="flex justify-between text-xl font-bold">
                    <span>Total a pagar:</span>
                    <span class="text-hotel-brown">${datos.precio_formateado}</span>
                </div>
            </div>
        </div>
    `;
    
    precioContainer.innerHTML = html;
}

async function buscarHuesped() {
    const termino = document.getElementById('buscar_huesped_input').value.trim();
    
    if (termino.length < 3) {
        mostrarAlerta('Por favor ingrese al menos 3 caracteres para buscar', 'warning');
        return;
    }
    
    try {
        const response = await apiFetch(`/api/huespedes/search?term=${encodeURIComponent(termino)}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            mostrarResultadosHuespedes(data.data);
        } else {
            mostrarAlerta('No se encontraron huéspedes con ese criterio', 'info');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarAlerta('Error al buscar huéspedes', 'error');
    }
}

function mostrarResultadosHuespedes(huespedes) {
    const container = document.getElementById('resultados-busqueda');
    let html = '<div class="mt-4 space-y-2">';
    huespedes.forEach(huesped => {
        const id = Number(huesped.id) || 0;
        const nombre = String(huesped.nombre_completo ?? '');
        const telefono = String(huesped.telefono ?? '');
        html += `
            <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 cursor-pointer" 
                 onclick="seleccionarHuesped(${id}, ${JSON.stringify(nombre)}, ${JSON.stringify(telefono)})">
                <div class="font-medium">${escHtml(nombre)}</div>
                <div class="text-sm text-gray-600">${escHtml(telefono)} - ${escHtml(huesped.procedencia || 'Sin procedencia')}</div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function seleccionarHuesped(id, nombre, telefono) {
    document.getElementById('huesped_id').value = id;
    document.getElementById('huesped_nombre').value = nombre;
    document.getElementById('buscar_huesped_input').value = '';
    document.getElementById('resultados-busqueda').innerHTML = '';
    
    // Ocultar sección de búsqueda y mostrar huésped seleccionado
    document.getElementById('buscar-huesped-section').style.display = 'none';
    document.getElementById('huesped-seleccionado').style.display = 'block';
}

function cambiarHuesped() {
    document.getElementById('huesped_id').value = '';
    document.getElementById('huesped_nombre').value = '';
    document.getElementById('buscar-huesped-section').style.display = 'block';
    document.getElementById('huesped-seleccionado').style.display = 'none';
}

// Funciones auxiliares
function mostrarLoading(containerId, mensaje = 'Cargando...') {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="flex items-center justify-center p-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-hotel-brown mr-3"></div>
                <span class="text-gray-600">${mensaje}</span>
            </div>
        `;
    }
}

function mostrarError(containerId, mensaje) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800">${mensaje}</p>
            </div>
        `;
    }
}

function mostrarAlerta(mensaje, tipo = 'info') {
    const alertas = {
        'info': 'bg-blue-50 border-blue-200 text-blue-800',
        'warning': 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'error': 'bg-red-50 border-red-200 text-red-800',
        'success': 'bg-green-50 border-green-200 text-green-800'
    };
    
    const alertContainer = document.getElementById('alert-container') || document.body;
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 p-4 rounded-lg border ${alertas[tipo]} max-w-md z-50`;
    alertDiv.innerHTML = mensaje;
    
    alertContainer.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function formatMoney(amount) {
    return new Intl.NumberFormat('es-MX', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
}

// Validación del formulario antes de enviar
function validarFormulario() {
    // Verificar huésped seleccionado
    const huespedId = document.getElementById('huesped_id').value;
    if (!huespedId) {
        mostrarAlerta('Debe seleccionar un huésped', 'error');
        return false;
    }
    
    // Verificar habitaciones seleccionadas
    if (habitacionesSeleccionadas.length === 0) {
        mostrarAlerta('Debe seleccionar al menos una habitación', 'error');
        return false;
    }
    
    // Verificar fechas
    const fechaEntrada = document.getElementById('fecha_entrada').value;
    const fechaSalida = document.getElementById('fecha_salida').value;
    
    if (!fechaEntrada || !fechaSalida) {
        mostrarAlerta('Debe seleccionar las fechas de entrada y salida', 'error');
        return false;
    }
    
    return true;
}
