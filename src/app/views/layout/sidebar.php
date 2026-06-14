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
$mostrarUsuariosModulo = $menuModuloActivo('usuarios');
$mostrarConfiguracionModulo = $menuModuloActivo('configuracion');
$mostrarTarifasModulo = $menuModuloActivo('tarifas_dinamicas') || $menuModuloActivo('tarifas');
$sidebarRolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
$sidebarPuedeUsuarios = can('usuarios.view') || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$sidebarPuedeConfiguracion = can('configuracion.view') || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$sidebarPuedeTarifas = is_gerente() || is_admin() || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarGestion = $mostrarHabitaciones || $mostrarReservaciones || $mostrarHuespedes;
$mostrarOperaciones = $mostrarCaja || $mostrarInventario || $mostrarFacturacion;
$filtrarMenuHotel = function_exists('hotel_menu_should_filter_modules') && hotel_menu_should_filter_modules();
$mostrarUsuariosAdmin = (!$filtrarMenuHotel && can('usuarios.view')) || ($filtrarMenuHotel && $mostrarUsuariosModulo && $sidebarPuedeUsuarios);
$mostrarConfiguracion = $mostrarConfiguracionModulo && $sidebarPuedeConfiguracion;
$mostrarTarifas = $sidebarPuedeTarifas && (!$filtrarMenuHotel || $mostrarTarifasModulo);
$mostrarNotificacionesMenu = true;
$mostrarAdministracion = ($mostrarReportes || $mostrarUsuariosAdmin || $mostrarNotificacionesMenu || $mostrarConfiguracion || $mostrarTarifas);
$sidebarRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$sidebarEsPanelSaas = strpos($sidebarRequestPath, '/admin/saas') === 0;
$sidebarBranding = (!$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_branding'))
    ? current_hotel_branding()
    : null;
$sidebarNombreVisual = $sidebarEsPanelSaas
    ? 'Panel Medisoft'
    : ($sidebarBranding
    ? hotel_branding_public_name($sidebarBranding, current_hotel_nombre() ?: 'Medisoft Hoteles')
    : 'Medisoft Hoteles');
$sidebarSubtitulo = $sidebarEsPanelSaas ? 'Admin SaaS' : 'Hotel';
$sidebarLogoUrl = ($sidebarBranding && function_exists('hotel_branding_asset_url'))
    ? (hotel_branding_asset_url($sidebarBranding['logo_url'] ?? null) ?: hotel_branding_default_logo_url())
    : (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : asset('img/logo.png'));
$sidebarMostrarLimpiezaOffline = !$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context();
$sidebarNotificacionesNoLeidas = 0;

if (!$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_id')) {
    try {
        require_once APP_PATH . '/models/Notificacion.php';
        $sidebarNotificacionModel = new Notificacion();
        $sidebarNotificacionesNoLeidas = $sidebarNotificacionModel->contarNoLeidas(
            (int) current_hotel_id(),
            function_exists('current_hotel_user_role') ? current_hotel_user_role() : null,
            function_exists('user_id') ? user_id() : null
        );
    } catch (Throwable $e) {
        error_log('No se pudo contar notificaciones del sidebar: ' . $e->getMessage());
        $sidebarNotificacionesNoLeidas = 0;
    }
}
?>

<aside id="sidebar" class="sidebar-main sidebar-fixed <?= $sidebarEsPanelSaas ? 'sidebar-saas' : 'hotel-sidebar' ?>">
    <div class="sidebar-header <?= $sidebarEsPanelSaas ? '' : 'hotel-sidebar-brand' ?>">
        <?php if ($sidebarEsPanelSaas): ?>
        <div class="logo-container <?= $sidebarEsPanelSaas ? '' : 'hotel-sidebar-brand-inner' ?>">
            <img src="<?= htmlspecialchars($sidebarLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?>" class="logo-img">
            <div class="logo-text">
                <h2 class="logo-title"><?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="logo-subtitle"><?= htmlspecialchars($sidebarSubtitulo, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="hotel-boutique-brand" aria-label="<?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
            <div class="hotel-boutique-mark">
                <img src="<?= htmlspecialchars($sidebarLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="hotel-boutique-name"><?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="hotel-boutique-rule">
                <span></span>
                <strong>OPERACIÓN HOTELERA</strong>
                <span></span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$sidebarEsPanelSaas): ?>
    <div class="sidebar-search hotel-sidebar-search" style="position:relative;">
        <div class="search-box" style="position:relative;display:flex;align-items:center;isolation:isolate;cursor:text;">
            <button type="button"
                    id="buscador-global-trigger"
                    class="search-trigger"
                    title="Buscar huésped o reservación"
                    aria-label="Buscar huésped o reservación">
                <i class="fas fa-search"></i>
            </button>
            <input type="text"
                   id="buscador-global-input"
                   placeholder="Buscar huésped o reservación"
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
    <?php endif; ?>

    <nav class="sidebar-nav <?= $sidebarEsPanelSaas ? '' : 'hotel-sidebar-nav' ?>">
        <?php if ($sidebarEsPanelSaas): ?>
        <div class="nav-section saas-nav-section">
            <div class="nav-section-title saas-section-title">
                <span>Operación SaaS</span>
            </div>

            <a href="<?= url('admin/saas/hoteles') ?>"
               class="nav-item saas-nav-item <?= strpos($sidebarRequestPath, '/admin/saas/hoteles') === 0 && $sidebarRequestPath !== '/admin/saas/hoteles/crear' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-building"></i>
                </div>
                <span class="nav-text">Hoteles / Clientes</span>
            </a>

            <a href="<?= url('admin/saas/hoteles/crear') ?>"
               class="nav-item saas-nav-item <?= $sidebarRequestPath === '/admin/saas/hoteles/crear' ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <span class="nav-text">Crear hotel</span>
            </a>
        </div>

        <div class="nav-section saas-nav-section">
            <div class="nav-section-title saas-section-title">
                <span>Configuración de cliente</span>
            </div>
            <div class="saas-sidebar-note">
                <i class="fas fa-circle-info"></i>
                <span>Branding, plan, módulos y usuarios se gestionan desde el detalle de cada hotel.</span>
            </div>
        </div>
        <?php else: ?>
        <div class="nav-section hotel-nav-section hotel-dashboard-section">
            <?php if ($mostrarDashboard): ?>
            <a href="<?= url('dashboard') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
            <?php endif; ?>

        </div>

        <?php if ($menuModulosSinConfigurar): ?>
        <div class="nav-section hotel-nav-section">
            <div style="padding:8px 12px;font-size:0.75rem;color:#6b7280;">
                Módulos del hotel pendientes de configurar.
            </div>
        </div>
        <?php endif; ?>

        <?php if ($mostrarGestion): ?>
        <div class="nav-section hotel-nav-section hotel-gestion-section">
            <div class="nav-section-title">
                <span>GESTIÓN</span>
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
                <span class="nav-text">Huéspedes</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarOperaciones): ?>
        <div class="nav-section hotel-nav-section hotel-operaciones-section">
            <div class="nav-section-title">
                <span>OPERACIONES</span>
            </div>

            <?php if ($mostrarCaja): ?>
            <a href="<?= url('caja') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'caja') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-wallet"></i>
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
                    <i class="fas fa-box"></i>
                </div>
                <span class="nav-text">Inventarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarFacturacion): ?>
            <a href="<?= url('facturacion') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'facturacion') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <span class="nav-text">Facturación</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarAdministracion): ?>
        <div class="nav-section hotel-nav-section hotel-admin-section">
            <div class="nav-section-title">
                <span>ADMINISTRACIÓN</span>
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
                    <i class="fas fa-user"></i>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarNotificacionesMenu): ?>
            <a href="<?= url('notificaciones') ?>"
               class="nav-item <?= strpos($sidebarRequestPath, '/notificaciones') === 0 ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($sidebarNotificacionesNoLeidas > 0): ?>
                    <span class="nav-badge nav-badge-notifications"><?= $sidebarNotificacionesNoLeidas > 99 ? '99+' : (int)$sidebarNotificacionesNoLeidas ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Notificaciones</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarConfiguracion): ?>
            <a href="<?= url('configuracion') ?>"
               class="nav-item <?= strpos($sidebarRequestPath, '/configuracion') === 0 && strpos($sidebarRequestPath, '/configuracion/tarifas') !== 0 ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="nav-text">Configuración</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarTarifas): ?>
            <a href="<?= url('configuracion/tarifas') ?>"
               class="nav-item <?= strpos($_SERVER['REQUEST_URI'], 'tarifas') !== false ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Tarifas dinámicas</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer <?= $sidebarEsPanelSaas ? '' : 'hotel-sidebar-footer' ?>">
        <?php if (!$sidebarEsPanelSaas): ?>
        <div class="hotel-sidebar-status" style="display:flex;align-items:center;gap:8px;padding:6px 12px 2px;opacity:0.85;">
            <span class="pwa-status-dot"></span>
            <span class="pwa-status-label" id="sidebar-net-label">Sesión activa</span>
        </div>
        <?php endif; ?>
        <div class="user-menu-shell">
        <div class="user-section <?= $sidebarEsPanelSaas ? '' : 'hotel-user-menu' ?>">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <p class="user-name"><?= user_name() ?></p>
                <p class="user-role"><?= user_role() ?></p>
            </div>
            <button type="button" class="user-menu-btn" id="user-menu-toggle" aria-label="Abrir menú de usuario">
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
                <span>Cerrar sesión</span>
                </button>
            </form>
        </div>
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
    background: color-mix(in srgb, <?= $sidebarEsPanelSaas ? 'var(--ms-primary, #2563EB)' : 'var(--brand-primary, #1B2746)' ?> 10%, transparent);
}

.nav-badge.nav-badge-notifications {
    top: -1px !important;
    right: -1px !important;
    min-width: 13px !important;
    height: 13px !important;
    border-radius: 999px !important;
    padding: 0 3px !important;
    font-size: .48rem !important;
    line-height: 1 !important;
    font-weight: 800 !important;
}

.hotel-layout-scope .hotel-sidebar .nav-item.active .nav-badge.nav-badge-notifications,
.nav-item.active .nav-badge.nav-badge-notifications {
    background: #dc2626 !important;
    color: #FFFEFB !important;
    box-shadow: 0 0 0 2px var(--hotel-panel, #FFFFFF) !important;
}
</style>

<?php if ($sidebarEsPanelSaas): ?>
<style>
.sidebar-main.sidebar-saas {
    background: var(--ms-sidebar, #0B1220);
    color: #E5EDF8;
    border-right: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: 16px 0 40px rgba(15, 23, 42, 0.18);
}

.sidebar-saas .sidebar-header {
    border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    background:
        radial-gradient(circle at top left, color-mix(in srgb, var(--ms-primary, #2563EB) 26%, transparent), transparent 34%),
        linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0));
}

.sidebar-saas .logo-container {
    align-items: center;
}

.sidebar-saas .logo-img {
    background: rgba(255, 255, 255, 0.96);
    border: 1px solid rgba(255, 255, 255, 0.16);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
}

.sidebar-saas .logo-title {
    color: #F8FAFC;
    letter-spacing: 0;
}

.sidebar-saas .logo-subtitle {
    display: inline-flex;
    width: fit-content;
    margin-top: 0.25rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ms-accent, #06B6D4) 18%, transparent);
    color: #BAE6FD;
    padding: 0.2rem 0.55rem;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.sidebar-saas .sidebar-nav {
    padding-top: 1rem;
}

.sidebar-saas .saas-nav-section {
    padding-inline: 0.85rem;
}

.sidebar-saas .saas-section-title {
    color: #93A4BA;
    font-size: 0.68rem;
    letter-spacing: 0.08em;
}

.sidebar-saas .saas-nav-item {
    margin-top: 0.35rem;
    border: 1px solid transparent;
    color: #CBD5E1;
    transition: background-color 0.16s ease, border-color 0.16s ease, color 0.16s ease, transform 0.16s ease;
}

.sidebar-saas .saas-nav-item:hover {
    background: rgba(37, 99, 235, 0.12);
    border-color: rgba(96, 165, 250, 0.28);
    color: #F8FAFC;
    transform: translateX(2px);
}

.sidebar-saas .saas-nav-item.active {
    background: linear-gradient(135deg, var(--ms-primary, #2563EB), var(--ms-primary-hover, #1D4ED8));
    border-color: color-mix(in srgb, var(--ms-accent, #06B6D4) 34%, transparent);
    color: #F8FAFC;
    box-shadow: 0 12px 24px rgba(37, 99, 235, 0.22);
}

.sidebar-saas .saas-nav-item .nav-icon {
    color: inherit;
}

.sidebar-saas .saas-sidebar-note {
    display: flex;
    gap: 0.65rem;
    margin: 0.45rem 0.85rem 0;
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.42);
    padding: 0.75rem;
    color: #AAB8CA;
    font-size: 0.75rem;
    line-height: 1.45;
}

.sidebar-saas .saas-sidebar-note i {
    margin-top: 0.15rem;
    color: var(--ms-accent, #06B6D4);
}

.sidebar-saas .sidebar-footer {
    border-top: 1px solid rgba(148, 163, 184, 0.16);
    background: rgba(2, 6, 23, 0.24);
}

.sidebar-saas .user-section {
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-radius: 8px;
    background: rgba(15, 23, 42, 0.48);
}

.sidebar-saas .user-avatar {
    background: color-mix(in srgb, var(--ms-primary, #2563EB) 22%, transparent);
    color: #DBEAFE;
}

.sidebar-saas .user-name {
    color: #F8FAFC;
}

.sidebar-saas .user-role {
    color: #94A3B8;
}

.sidebar-saas .user-menu-btn {
    color: #CBD5E1;
}

.sidebar-saas .user-menu-btn:hover {
    background: rgba(37, 99, 235, 0.14);
    color: #F8FAFC;
}

.sidebar-saas #user-dropdown {
    background: #F8FAFC;
    border-color: rgba(15, 23, 42, 0.12);
}

.sidebar-saas .dropdown-item {
    color: #334155;
}

.sidebar-saas .dropdown-item.text-red {
    color: var(--ms-danger, #DC2626);
}
</style>
<?php endif; ?>

<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>

<script>
(() => {
    function initSidebarInteractions() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');

        if (sidebar && overlay && mobileMenuToggle && !mobileMenuToggle.dataset.hotelShellBound) {
            mobileMenuToggle.dataset.hotelShellBound = 'true';
            mobileMenuToggle.setAttribute('aria-expanded', 'false');

            mobileMenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                sidebar.classList.add('active');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
                mobileMenuToggle.setAttribute('aria-expanded', 'true');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            });
        }

        const userMenuBtn = document.getElementById('user-menu-toggle');
        const userDropdown = document.getElementById('user-dropdown');
        const globalSearchTrigger = document.getElementById('buscador-global-trigger');
        const globalSearchInput = document.getElementById('buscador-global-input');

        if (globalSearchTrigger && globalSearchInput && !globalSearchTrigger.dataset.hotelSearchBound) {
            globalSearchTrigger.dataset.hotelSearchBound = 'true';
            globalSearchTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                globalSearchInput.focus();
                globalSearchInput.select();
            });
        }

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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarInteractions);
    } else {
        initSidebarInteractions();
    }
})();
</script>

<style>
.user-menu-shell {
    position: relative;
    width: 100%;
}

#user-dropdown {
    position: absolute;
    bottom: calc(100% + 8px);
    left: 0;
    right: 0;
    margin-bottom: 0;
    background: white;
    border: 1px solid color-mix(in srgb, <?= $sidebarEsPanelSaas ? 'var(--ms-primary, #2563EB)' : 'var(--brand-primary, #1B2746)' ?> 10%, transparent);
    border-radius: 6px;
    padding: 0.5rem;
    max-height: min(240px, calc(100vh - 150px));
    overflow-y: auto;
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
