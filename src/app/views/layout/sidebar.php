<!-- Sidebar Completo - Los Cedros -->
<aside id="sidebar" class="sidebar-main sidebar-fixed">
    <!-- Logo y título -->
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="<?= asset('img/logo-hotel-san-nicolas2.png') ?>" alt="Los Cedros" class="logo-img">
            <div class="logo-text">
                <h2 class="logo-title">Los Cedros</h2>
                <p class="logo-subtitle">Hotel</p>
            </div>
        </div>
    </div>
    
    <!-- Buscador Global -->
    <div class="sidebar-search" style="position:relative;">
        <div class="search-box" style="position:relative;display:flex;align-items:center;isolation:isolate;cursor:text;">
            <i class="fas fa-search search-icon" style="pointer-events:none;"></i>
            <input type="text"
                   id="buscador-global-input"
                   placeholder="Buscar huésped, reservación..."
                   class="search-input"
                   autocomplete="off"
                   style="padding-right:28px;position:relative;z-index:2;pointer-events:auto;cursor:text;user-select:text;">
            <!-- Botón limpiar -->
            <button id="buscador-global-clear"
                    title="Limpiar búsqueda"
                    style="display:none;position:absolute;right:8px;top:50%;transform:translateY(-50%);
                           width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.18);
                           color:white;border:none;cursor:pointer;
                           align-items:center;justify-content:center;font-size:.6rem;z-index:3;pointer-events:auto;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Dropdown de resultados -->
        <div id="buscador-global-dropdown"
             style="display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);
                    background:white;border-radius:12px;
                    box-shadow:0 8px 30px rgba(0,0,0,.15);
                    border:1px solid #E5E7EB;
                    z-index:9999;max-height:420px;overflow-y:auto;">
        </div>
    </div>
    
    <!-- Menú de navegación -->
    <nav class="sidebar-nav">
        <!-- Dashboard -->
        <div class="nav-section">
            <a href="<?= url('dashboard') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>
        
        <!-- Sección Gestión -->
        <div class="nav-section">
            <div class="nav-section-title">
                <span>GESTIÓN</span>
            </div>
            
            <a href="<?= url('habitaciones') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'habitaciones') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-bed"></i>
                    <?php 
                    // Contador de habitaciones ocupadas (opcional)
                    $habitaciones_ocupadas = $habitaciones_ocupadas ?? 0;
                    if ($habitaciones_ocupadas > 0): 
                    ?>
                    <span class="nav-badge"><?= $habitaciones_ocupadas ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Habitaciones</span>
            </a>
            
            <a href="<?= url('reservaciones') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'reservaciones') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-calendar-check"></i>
                    <?php 
                    // Contador de reservaciones pendientes (opcional)
                    $pending_reservations = $pending_reservations ?? 0;
                    if ($pending_reservations > 0): 
                    ?>
                    <span class="nav-badge"><?= $pending_reservations ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Reservaciones</span>
            </a>
            
            <a href="<?= url('huespedes') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'huespedes') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="nav-text">Huéspedes</span>
            </a>
        </div>
        
        <!-- Sección Operaciones -->
        <div class="nav-section">
            <div class="nav-section-title">
                <span>OPERACIONES</span>
            </div>
            
            <a href="<?= url('caja') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'caja') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-cash-register"></i>
                    <?php 
                    // Indicador de caja abierta (opcional)
                    $caja_abierta = $caja_abierta ?? false;
                    if ($caja_abierta): 
                    ?>
                    <span class="nav-badge pulse-green"></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Caja</span>
            </a>
            
            <a href="<?= url('inventario') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'inventario') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="nav-text">Inventarios</span>
            </a>
            <a href="<?= url('facturacion') ?>" 
   class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'facturacion') !== false ? 'active' : '' ?>">
    <div class="nav-icon">
        <i class="fas fa-file-invoice-dollar"></i>
    </div>
    <span class="nav-text">Facturación</span>
</a>

            
            
        </div>
        
        <!-- Sección Administración (condicional) -->
        <?php if (can('usuarios.view') || can('configuracion.view')): ?>
        <div class="nav-section">
            <div class="nav-section-title">
                <span>ADMINISTRACIÓN</span>
            </div>
            <a href="<?= url('reportes') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'reportes') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Reportes</span>
            </a>
            <?php if (can('usuarios.view')): ?>
            <a href="<?= url('usuarios') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
            <?php endif; ?>
            
            <?php if (can('usuarios.view')): ?>
            <a href="<?= url('configuracion/tarifas') ?>" 
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'tarifas') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Tarifas Dinámicas</span>
            </a>
            <?php endif; ?>
            
            <?php if (can('configuracion.view')): ?>
            
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>
    
    <!-- Footer del sidebar con info del usuario -->
    <div class="sidebar-footer">
        <!-- Indicador de estado de red -->
        <div style="display:flex;align-items:center;gap:8px;padding:6px 12px 2px;opacity:0.85;">
            <span class="pwa-status-dot"></span>
            <span class="pwa-status-label" id="sidebar-net-label">En línea</span>
        </div>
        <!-- Usuario -->
        <div class="user-section">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <p class="user-name"><?= user_name() ?></p>
                <p class="user-role"><?= user_role() ?></p>
            </div>
            <button class="user-menu-btn" id="user-menu-toggle">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
        
        <!-- Menú desplegable del usuario -->
        <div class="user-dropdown" id="user-dropdown">
           
            <div class="dropdown-divider"></div>
            <form method="POST" action="<?= url('logout') ?>" id="logout-form" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item text-red" style="width:100%;background:none;border:0;text-align:left;cursor:pointer;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<!-- Overlay para móvil (mantenido solo para respuesta táctil) -->
<div id="sidebar-overlay" class="sidebar-overlay"></div>

<!-- CSS adicional para hacer funcionar el dropdown -->
<style>
/* Hacer que el dropdown funcione con la clase 'active' */
.user-dropdown.active {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

/* Asegurar z-index alto para el dropdown */
.user-dropdown {
    z-index: 1100 !important;
}

/* Mejorar el hover del botón de menú - VERDE OLIVO */
.user-menu-btn {
    cursor: pointer;
}

.user-menu-btn:hover {
    background: rgba(156, 167, 119, 0.1);
}
</style>

<!-- Incluir estilos del sidebar -->
<link rel="stylesheet" href="<?= asset('css/sidebar-styles.css') ?>">

<!-- Incluir scripts del sidebar modificado -->
<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>

<!-- Script adicional para corregir el dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuBtn = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuBtn && userDropdown) {
        // Toggle dropdown al hacer clic
        userMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isActive = userDropdown.classList.contains('active');
            
            if (isActive) {
                userDropdown.classList.remove('active', 'show');
            } else {
                userDropdown.classList.add('active', 'show');
            }
        });
        
        // Cerrar al hacer clic fuera
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userDropdown.classList.remove('active', 'show');
            }
        });
        
        // Prevenir que clicks dentro del dropdown lo cierren
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

<style>



/* Corregir el dropdown del usuario - VERDE OLIVO */
#user-dropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    margin-bottom: 0.5rem;
    background: white;
    border: 1px solid rgba(156, 167, 119, 0.1);
    border-radius: 6px;
    padding: 0.5rem;
    box-shadow: 0 -8px 20px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.2s ease;
    z-index: 1200;
}

#user-dropdown.active,
#user-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.sidebar-footer {
    position: relative;
}
</style>
