/**
 * Dashboard JavaScript - Los Cedros
 * Funcionalidades interactivas y actualizaciones en tiempo real
 */

// Configuración global
const DASHBOARD_CONFIG = {
    updateInterval: 30000, // 30 segundos
    animationDuration: 300,
    apiEndpoints: {
        stats: '/dashboard/stats',
        charts: '/dashboard/charts',
        ocupacion: '/api/dashboard/ocupacion',
        movimientos: '/api/dashboard/movimientos-recientes',
        alertas: '/api/dashboard/alertas'
    }
};

// Estado global del dashboard
let dashboardState = {
    charts: {},
    updateTimer: null,
    isUpdating: false
};

function dashboardFetchJson(url) {
    return fetch(url, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(response => response.json());
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[ch]));
}

// Inicialización del Dashboard
document.addEventListener('DOMContentLoaded', function() {
    console.log('Inicializando Dashboard Los Cedros...');
    
    // Inicializar componentes
    initializeWidgets();
    initializeCharts();
    initializeEventListeners();
    
    // Iniciar actualizaciones automáticas
    startAutoUpdate();
    
    // Agregar clase para animaciones
    document.querySelector('.dashboard-container')?.classList.add('stagger-animation');
});

/**
 * Inicializar widgets interactivos
 */
function initializeWidgets() {
    // Animar números al cargar
    animateNumbers();
    
    // Agregar tooltips
    initializeTooltips();
    
    // Efectos hover en widgets
    const widgets = document.querySelectorAll('.dashboard-widget');
    widgets.forEach(widget => {
        widget.addEventListener('mouseenter', function() {
            this.classList.add('widget-hover');
        });
        
        widget.addEventListener('mouseleave', function() {
            this.classList.remove('widget-hover');
        });
    });
}

/**
 * Animar números en los widgets
 */
function animateNumbers() {
    const numbers = document.querySelectorAll('.stat-number');
    
    numbers.forEach(element => {
        const finalValue = parseInt(element.textContent);
        const duration = 1000;
        const increment = finalValue / (duration / 16);
        let currentValue = 0;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
                currentValue = finalValue;
                clearInterval(timer);
            }
            element.textContent = Math.floor(currentValue);
        }, 16);
    });
}

/**
 * Inicializar tooltips personalizados
 */
function initializeTooltips() {
    const elements = document.querySelectorAll('[data-tooltip]');
    
    elements.forEach(element => {
        element.classList.add('custom-tooltip');
    });
}

/**
 * Inicializar listeners de eventos
 */
function initializeEventListeners() {
    // Botón de actualizar manual
    const refreshButton = document.getElementById('dashboard-refresh');
    if (refreshButton) {
        refreshButton.addEventListener('click', function() {
            this.classList.add('loading-indicator');
            updateDashboard().then(() => {
                this.classList.remove('loading-indicator');
                showNotification('Dashboard actualizado', 'success');
            });
        });
    }
    
    // Filtros de fecha
    const dateFilters = document.querySelectorAll('.date-filter');
    dateFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            updateCharts(this.value);
        });
    });
}

/**
 * Iniciar actualizaciones automáticas
 */
function startAutoUpdate() {
    // Actualizar inmediatamente
    updateDashboard();
    
    // Configurar intervalo
    dashboardState.updateTimer = setInterval(() => {
        updateDashboard();
    }, DASHBOARD_CONFIG.updateInterval);
}

/**
 * Detener actualizaciones automáticas
 */
function stopAutoUpdate() {
    if (dashboardState.updateTimer) {
        clearInterval(dashboardState.updateTimer);
        dashboardState.updateTimer = null;
    }
}

/**
 * Actualizar todo el dashboard
 */
async function updateDashboard() {
    if (dashboardState.isUpdating) return;
    
    dashboardState.isUpdating = true;
    
    try {
        // Actualizar estadísticas
        await updateStats();
        
        // Actualizar gráficos
        await updateCharts();
        
        // Actualizar alertas
        await updateAlerts();
        
        // Actualizar timestamp
        updateTimestamp();
        
    } catch (error) {
        console.error('Error actualizando dashboard:', error);
        showNotification('Error al actualizar el dashboard', 'error');
    } finally {
        dashboardState.isUpdating = false;
    }
}

/**
 * Actualizar estadísticas
 */
async function updateStats() {
    try {
        const result = await dashboardFetchJson(DASHBOARD_CONFIG.apiEndpoints.stats);
        
        if (result.success) {
            updateStatsUI(result.data.stats);
            updateCajaUI(result.data.caja);
        }
    } catch (error) {
        console.error('Error actualizando estadísticas:', error);
    }
}

/**
 * Actualizar UI de estadísticas
 */
function updateStatsUI(stats) {
    // Actualizar ocupación
    const ocupacionElement = document.querySelector('[data-stat="ocupacion"]');
    if (ocupacionElement) {
        animateValue(ocupacionElement, stats.habitaciones.porcentaje_ocupacion, '%');
        
        // Actualizar barra de progreso
        const progressBar = ocupacionElement.closest('.dashboard-widget')
            ?.querySelector('.progress-bar-fill');
        if (progressBar) {
            progressBar.style.width = stats.habitaciones.porcentaje_ocupacion + '%';
        }
    }
    
    // Actualizar ingresos
    const ingresosElement = document.querySelector('[data-stat="ingresos-total"]');
    if (ingresosElement) {
        ingresosElement.textContent = formatMoney(stats.ingresos.total_dia);
    }
    
    // Actualizar métodos de pago
    ['efectivo', 'tarjeta', 'transferencia'].forEach(metodo => {
        const element = document.querySelector(`[data-stat="ingresos-${metodo}"]`);
        if (element) {
            element.textContent = formatMoney(stats.ingresos[`${metodo}_dia`]);
        }
    });
    
    // Actualizar estado de habitaciones
    updateCounter('habitaciones-disponibles', stats.habitaciones.disponibles_reales || stats.habitaciones.disponibles);
    updateCounter('habitaciones-por-llegar', stats.habitaciones.por_llegar);
    updateCounter('habitaciones-ocupadas', stats.habitaciones.ocupadas);
    updateCounter('habitaciones-limpieza', stats.habitaciones.limpieza);
    updateCounter('habitaciones-mantenimiento', stats.habitaciones.mantenimiento);
    
    // Actualizar contadores de entradas/salidas
    updateCounter('entradas-total', stats.entradas.total);
    updateCounter('entradas-pendientes', stats.entradas.pendientes);
    updateCounter('salidas-total', stats.salidas.total);
    updateCounter('salidas-pendientes', stats.salidas.pendientes);
}

/**
 * Actualizar contador con animación
 */
function updateCounter(id, newValue) {
    const element = document.querySelector(`[data-counter="${id}"]`);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent) || 0;
    if (currentValue !== newValue) {
        animateValue(element, newValue);
        
        // Efecto de destello si cambió
        element.classList.add('counter-updated');
        setTimeout(() => {
            element.classList.remove('counter-updated');
        }, 1000);
    }
}

/**
 * Animar cambio de valor
 */
function animateValue(element, newValue, suffix = '') {
    const currentValue = parseFloat(element.textContent) || 0;
    const difference = newValue - currentValue;
    const duration = 500;
    const steps = 30;
    const increment = difference / steps;
    let step = 0;
    
    const timer = setInterval(() => {
        step++;
        const value = currentValue + (increment * step);
        element.textContent = formatNumber(value) + suffix;
        
        if (step >= steps) {
            clearInterval(timer);
            element.textContent = formatNumber(newValue) + suffix;
        }
    }, duration / steps);
}

/**
 * Actualizar información de caja
 */
function updateCajaUI(cajaInfo) {
    if (!cajaInfo) return;
    
    const cajaWidget = document.querySelector('.caja-status');
    if (cajaWidget) {
        cajaWidget.classList.add('abierta');
        
        // Actualizar montos
        const efectivoEsperado = document.querySelector('[data-caja="efectivo-esperado"]');
        if (efectivoEsperado) {
            efectivoEsperado.textContent = formatMoney(cajaInfo.efectivo_esperado);
        }
    }
}

/**
 * Actualizar gráficos
 */
async function updateCharts() {
    try {
        const result = await dashboardFetchJson(DASHBOARD_CONFIG.apiEndpoints.charts);
        
        if (result.success) {
            updateChartsUI(result.data);
        }
    } catch (error) {
        console.error('Error actualizando gráficos:', error);
    }
}

/**
 * Actualizar UI de gráficos
 */
function updateChartsUI(data) {
    // Actualizar gráfico de ocupación
    if (dashboardState.charts.ocupacion) {
        dashboardState.charts.ocupacion.data.labels = data.ocupacion_semanal.map(d => d.dia);
        dashboardState.charts.ocupacion.data.datasets[0].data = data.ocupacion_semanal.map(d => d.ocupadas);
        dashboardState.charts.ocupacion.data.datasets[1].data = data.ocupacion_semanal.map(d => d.disponibles);
        dashboardState.charts.ocupacion.update('active');
    }
    
    // Actualizar gráfico de ingresos
    if (dashboardState.charts.ingresos) {
        dashboardState.charts.ingresos.data.labels = data.ingresos_por_tipo.map(d => 
            d.tipo.charAt(0).toUpperCase() + d.tipo.slice(1).replace('_', ' ')
        );
        dashboardState.charts.ingresos.data.datasets[0].data = data.ingresos_por_tipo.map(d => d.ingresos);
        dashboardState.charts.ingresos.update('active');
    }
}

/**
 * Actualizar alertas
 */
async function updateAlerts() {
    try {
        const result = await dashboardFetchJson(DASHBOARD_CONFIG.apiEndpoints.alertas);
        
        if (result.success && result.data.length > 0) {
            showAlerts(result.data);
        }
    } catch (error) {
        console.error('Error actualizando alertas:', error);
    }
}

/**
 * Mostrar alertas
 */
function showAlerts(alerts) {
    const alertContainer = document.getElementById('dashboard-alerts');
    if (!alertContainer) return;
    
    alertContainer.innerHTML = '';
    
    alerts.forEach(alert => {
        const alertElement = createAlertElement(alert);
        alertContainer.appendChild(alertElement);
    });
}

/**
 * Crear elemento de alerta
 */
function createAlertElement(alert) {
    const div = document.createElement('div');
    div.className = `alert-box p-4 mb-3 rounded-lg border-l-4 ${getAlertClass(alert.type)}`;
    div.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${getAlertIcon(alert.type)} mr-3"></i>
            <div>
                <h4 class="font-semibold">${escapeHtml(alert.title)}</h4>
                <p class="text-sm mt-1">${escapeHtml(alert.message)}</p>
            </div>
        </div>
    `;
    return div;
}

/**
 * Obtener clase CSS para tipo de alerta
 */
function getAlertClass(type) {
    const classes = {
        'warning': 'bg-yellow-50 border-yellow-400 text-yellow-800',
        'error': 'bg-red-50 border-red-400 text-red-800',
        'info': 'bg-blue-50 border-blue-400 text-blue-800',
        'success': 'bg-green-50 border-green-400 text-green-800'
    };
    return classes[type] || classes.info;
}

/**
 * Obtener icono para tipo de alerta
 */
function getAlertIcon(type) {
    const icons = {
        'warning': 'fa-exclamation-triangle',
        'error': 'fa-times-circle',
        'info': 'fa-info-circle',
        'success': 'fa-check-circle'
    };
    return icons[type] || icons.info;
}

/**
 * Actualizar timestamp
 */
function updateTimestamp() {
    const timestampElement = document.querySelector('.dashboard-timestamp');
    if (timestampElement) {
        const now = new Date();
        timestampElement.textContent = `Actualizado: ${formatTime(now)}`;
    }
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 ${getNotificationClass(type)}`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${getAlertIcon(type)} mr-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.add('translate-x-0');
    }, 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

/**
 * Obtener clase para notificación
 */
function getNotificationClass(type) {
    const classes = {
        'success': 'bg-green-500 text-white',
        'error': 'bg-red-500 text-white',
        'warning': 'bg-yellow-500 text-white',
        'info': 'bg-blue-500 text-white'
    };
    return classes[type] || classes.info;
}

// Utilidades
function formatMoney(amount) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(amount);
}

function formatNumber(number) {
    return new Intl.NumberFormat('es-MX').format(number);
}

function formatTime(date) {
    return new Intl.DateTimeFormat('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    }).format(date);
}

// Limpiar al salir de la página
window.addEventListener('beforeunload', function() {
    stopAutoUpdate();
});
