<?php
$menuModuloActivo = function ($clave) {
    return function_exists('hotel_menu_module_enabled') ? hotel_menu_module_enabled($clave) : true;
};

$menuModulosSinConfigurar = function_exists('hotel_menu_modules_unconfigured') && hotel_menu_modules_unconfigured();
$mostrarDashboard = $menuModuloActivo('dashboard');
$mostrarHabitaciones = $menuModuloActivo('habitaciones');
$mostrarReservaciones = $menuModuloActivo('reservaciones');
$mostrarHuespedes = $menuModuloActivo('huespedes');
$mostrarCaja = $menuModuloActivo('caja');
$mostrarInventario = $menuModuloActivo('inventario');
$mostrarFacturacion = $menuModuloActivo('facturacion');
$mostrarReportes = $menuModuloActivo('reportes');
$mostrarGestion = $mostrarHabitaciones || $mostrarReservaciones || $mostrarHuespedes;
$mostrarOperaciones = $mostrarCaja || $mostrarInventario || $mostrarFacturacion;
$filtrarMenuHotel = function_exists('hotel_menu_should_filter_modules') && hotel_menu_should_filter_modules();
$mostrarUsuariosAdmin = !$filtrarMenuHotel && can('usuarios.view');
$mostrarTarifas = !$filtrarMenuHotel && can('usuarios.view');
$mostrarAdministracion = ($mostrarReportes || $mostrarUsuariosAdmin || $mostrarTarifas);
$sidebarRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$sidebarEsPanelSaas = strpos($sidebarRequestPath, '/admin/saas') === 0;
$sidebarBranding = (!$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_branding'))
    ? current_hotel_branding()
    : null;
$sidebarNombreVisual = $sidebarBranding
    ? hotel_branding_public_name($sidebarBranding, current_hotel_nombre() ?: 'Medisoft Hoteles')
    : 'Medisoft Hoteles';
$sidebarSubtitulo = $sidebarEsPanelSaas ? 'Panel SaaS' : 'Hotel';
$sidebarLogoUrl = ($sidebarBranding && function_exists('hotel_branding_asset_url'))
    ? (hotel_branding_asset_url($sidebarBranding['logo_url'] ?? null) ?: hotel_branding_default_logo_url())
    : (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : asset('img/logo.png'));
$sidebarMostrarLimpiezaOffline = !$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context();
?>

<?php if ($sidebarBranding): ?>
<style>
    .sidebar-main .logo-title {
        color: var(--brand-primary, #9CA777);
    }

    .sidebar-main .nav-item.active,
    .sidebar-main .nav-item:hover {
        background: linear-gradient(135deg, var(--brand-primary, #9CA777), var(--brand-secondary, #7A8B5C));
    }

    .sidebar-main .nav-badge,
    .sidebar-main .pulse-green {
        background: var(--brand-accent, #D4AF37);
    }
</style>
<?php endif; ?>

<aside id="sidebar" class="sidebar-main sidebar-fixed">
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="<?= htmlspecialchars($sidebarLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?>" class="logo-img">
            <div class="logo-text">
                <h2 class="logo-title"><?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="logo-subtitle"><?= htmlspecialchars($sidebarSubtitulo, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
    </div>

    <div class="sidebar-search" style="position:relative;">
        <div class="search-box" style="position:relative;display:flex;align-items:center;isolation:isolate;cursor:text;">
            <i class="fas fa-search search-icon" style="pointer-events:none;"></i>
            <input type="text"
                   id="buscador-global-input"
                   placeholder="Buscar huesped, reservacion..."
                   class="search-input"
                   autocomplete="off"
                   style="padding-right:28px;position:relative;z-index:2;pointer-events:auto;cursor:text;user-select:text;">
            <button id="buscador-global-clear"
                    title="Limpiar busqueda"
                    style="display:none;position:absolute;right:8px;top:50%;transform:translateY(-50%);
                           width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.18);
                           color:white;border:none;cursor:pointer;
                           align-items:center;justify-content:center;font-size:.6rem;z-index:3;pointer-events:auto;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="buscador-global-dropdown"
             style="display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);
                    background:white;border-radius:12px;
                    box-shadow:0 8px 30px rgba(0,0,0,.15);
                    border:1px solid #E5E7EB;
                    z-index:9999;max-height:420px;overflow-y:auto;">
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($mostrarDashboard): ?>
        <div class="nav-section">
            <a href="<?= url('dashboard') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
        </div>
        <?php endif; ?>

        <?php if ($menuModulosSinConfigurar): ?>
        <div class="nav-section">
            <div style="padding:8px 12px;font-size:0.75rem;color:#6b7280;">
                Modulos del hotel pendientes de configurar.
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mostrarGestion): ?>
        <div class="nav-section">
            <div class="nav-section-title">
                <span>GESTION</span>
            </div>

            <?php if ($mostrarHabitaciones): ?>
            <a href="<?= url('habitaciones') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'habitaciones') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-bed"></i>
                    <?php
                    $habitaciones_ocupadas = $habitaciones_ocupadas ?? 0;
                    if ($habitaciones_ocupadas > 0):
                    ?>
                    <span class="nav-badge"><?= $habitaciones_ocupadas ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Habitaciones</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarReservaciones): ?>
            <a href="<?= url('reservaciones') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'reservaciones') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-calendar-check"></i>
                    <?php
                    $pending_reservations = $pending_reservations ?? 0;
                    if ($pending_reservations > 0):
                    ?>
                    <span class="nav-badge"><?= $pending_reservations ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Reservaciones</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarHuespedes): ?>
            <a href="<?= url('huespedes') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'huespedes') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="nav-text">Huespedes</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarOperaciones): ?>
        <div class="nav-section">
            <div class="nav-section-title">
                <span>OPERACIONES</span>
            </div>

            <?php if ($mostrarCaja): ?>
            <a href="<?= url('caja') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'caja') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-cash-register"></i>
                    <?php
                    $caja_abierta = $caja_abierta ?? false;
                    if ($caja_abierta):
                    ?>
                    <span class="nav-badge pulse-green"></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Caja</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarInventario): ?>
            <a href="<?= url('inventario') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'inventario') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="nav-text">Inventarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarFacturacion): ?>
            <a href="<?= url('facturacion') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'facturacion') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <span class="nav-text">Facturacion</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarAdministracion): ?>
        <div class="nav-section">
            <div class="nav-section-title">
                <span>ADMINISTRACION</span>
            </div>

            <?php if ($mostrarReportes): ?>
            <a href="<?= url('reportes') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'reportes') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Reportes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarUsuariosAdmin): ?>
            <a href="<?= url('usuarios') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'usuarios') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarTarifas): ?>
            <a href="<?= url('configuracion/tarifas') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'tarifas') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Tarifas Dinamicas</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div style="display:flex;align-items:center;gap:8px;padding:6px 12px 2px;opacity:0.85;">
            <span class="pwa-status-dot"></span>
            <span class="pwa-status-label" id="sidebar-net-label">En linea</span>
        </div>
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

        <div class="user-dropdown" id="user-dropdown">
            <div class="dropdown-divider"></div>
            <?php if ($sidebarMostrarLimpiezaOffline): ?>
            <button type="button"
                    class="dropdown-item"
                    id="manual-offline-cleanup-btn"
                    style="width:100%;background:none;border:0;text-align:left;cursor:pointer;"
                    title="Limpia solo los datos offline locales de este navegador, con confirmacion previa.">
                <i class="fas fa-broom"></i>
                <span>Limpiar datos offline</span>
            </button>
            <div class="dropdown-divider"></div>
            <?php endif; ?>
            <form method="POST" action="<?= url('logout') ?>" id="logout-form" style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="dropdown-item text-red" style="width:100%;background:none;border:0;text-align:left;cursor:pointer;">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesion</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<div id="sidebar-overlay" class="sidebar-overlay"></div>

<style>
.user-dropdown.active {
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

.user-dropdown {
    z-index: 1100 !important;
}

.user-menu-btn {
    cursor: pointer;
}

.user-menu-btn:hover {
    background: color-mix(in srgb, var(--brand-primary, #9CA777) 10%, transparent);
}
</style>

<link rel="stylesheet" href="<?= asset('css/sidebar-styles.css') ?>">

<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuBtn = document.getElementById('user-menu-toggle');
    const userDropdown = document.getElementById('user-dropdown');

    if (userMenuBtn && userDropdown) {
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

        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userMenuBtn.contains(e.target)) {
                userDropdown.classList.remove('active', 'show');
            }
        });

        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

<style>
#user-dropdown {
    position: absolute;
    bottom: 100%;
    left: 0;
    right: 0;
    margin-bottom: 0.5rem;
    background: white;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #9CA777) 10%, transparent);
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
