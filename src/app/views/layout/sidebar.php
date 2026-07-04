<?php
$menuModuloActivo = function ($clave) {
    return function_exists('hotel_menu_module_enabled') ? hotel_menu_module_enabled($clave) : true;
};

$menuModulosSinConfigurar = function_exists('hotel_menu_modules_unconfigured') && hotel_menu_modules_unconfigured();
$mostrarDashboard = $menuModuloActivo('dashboard');
$mostrarOperacionDiaria = $menuModuloActivo('tablero_ejecutivo');
$mostrarForecast = $menuModuloActivo('forecast');
$mostrarHabitaciones = $menuModuloActivo('habitaciones');
$mostrarReservaciones = $menuModuloActivo('reservaciones');
$mostrarHuespedes = $menuModuloActivo('huespedes');
$mostrarCaja = $menuModuloActivo('caja');
$mostrarInventario = $menuModuloActivo('inventario');
$mostrarCompras = $menuModuloActivo('compras');
$mostrarProveedores = $mostrarCompras;
$mostrarCuentasPorPagar = $mostrarCompras;
$mostrarDocumentos = $menuModuloActivo('documentos');
$mostrarTareas = $menuModuloActivo('tareas');
$mostrarFacturacion = $menuModuloActivo('facturacion');
$mostrarCuentasPorCobrar = $menuModuloActivo('cuentas_cobrar');
$mostrarReportes = $menuModuloActivo('reportes');
$mostrarUsuariosModulo = $menuModuloActivo('usuarios');
$mostrarConfiguracionModulo = $menuModuloActivo('configuracion');
$mostrarTarifasModulo = $menuModuloActivo('tarifas_dinamicas') || $menuModuloActivo('tarifas');
$sidebarRolHotel = function_exists('current_hotel_user_role') ? current_hotel_user_role() : null;
$sidebarPuedeUsuarios = can('usuarios.view') || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$sidebarPuedeConfiguracion = can('configuracion.view') || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$sidebarPuedeTarifas = is_gerente() || is_admin() || in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarFinanzas = $mostrarCaja || $mostrarCuentasPorCobrar || $mostrarFacturacion || $mostrarCuentasPorPagar;
$filtrarMenuHotel = function_exists('hotel_menu_should_filter_modules') && hotel_menu_should_filter_modules();
$mostrarUsuariosAdmin = (!$filtrarMenuHotel && can('usuarios.view')) || ($filtrarMenuHotel && $mostrarUsuariosModulo && $sidebarPuedeUsuarios);
$mostrarPersonal = $menuModuloActivo('personal') && $sidebarPuedeUsuarios;
$mostrarConfiguracion = $mostrarConfiguracionModulo && $sidebarPuedeConfiguracion;
$mostrarTarifas = $sidebarPuedeTarifas && (!$filtrarMenuHotel || $mostrarTarifasModulo);
$mostrarRoles = function_exists('can') && can('roles.manage') && $menuModuloActivo('roles_avanzados');
$mostrarAuditoria = $menuModuloActivo('auditoria') && in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarNotificacionesMenu = $menuModuloActivo('notificaciones');
$mostrarMotorReservas = $menuModuloActivo('motor_reservas') && in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarIaEjecutiva = $menuModuloActivo('ia_ejecutiva') && in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarWhatsApp = $menuModuloActivo('whatsapp') && in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarCheckinDigital = $menuModuloActivo('checkin_digital');
$mostrarCanales = $menuModuloActivo('canales_ical') && in_array($sidebarRolHotel, ['gerente', 'administrador'], true);
$mostrarCamarista = $menuModuloActivo('camarista');
$mostrarReputacion = $menuModuloActivo('reputacion');
$mostrarNightAudit = $menuModuloActivo('night_audit');
// Agrupación del menú: Recepción incluye check-in digital; Operación incluye limpieza;
// Ventas y canales agrupa los bloques comerciales; Configuración va aparte de Administración.
$mostrarGestion = $mostrarHabitaciones || $mostrarReservaciones || $mostrarHuespedes || $mostrarCheckinDigital;
$mostrarOperacionInterna = $mostrarTareas || $mostrarCamarista || $mostrarInventario || $mostrarCompras || $mostrarProveedores || $mostrarDocumentos || $mostrarNightAudit;
$mostrarVentasCanales = $mostrarMotorReservas || $mostrarCanales || $mostrarWhatsApp || $mostrarIaEjecutiva || $mostrarReputacion;
$mostrarAdministracion = ($mostrarReportes || $mostrarUsuariosAdmin || $mostrarPersonal || $mostrarNotificacionesMenu || $mostrarAuditoria);
$mostrarConfigSeccion = $mostrarConfiguracion || $mostrarTarifas || $mostrarRoles;
$sidebarRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$sidebarNormalizedPath = '/' . trim($sidebarRequestPath, '/');
if ($sidebarNormalizedPath === '/') {
    $sidebarNormalizedPath = '/dashboard';
}
$sidebarPathStarts = static function (string $prefix) use ($sidebarNormalizedPath): bool {
    $prefix = '/' . trim($prefix, '/');
    return $sidebarNormalizedPath === $prefix || strpos($sidebarNormalizedPath, $prefix . '/') === 0;
};
$sidebarPathIn = static function (array $prefixes) use ($sidebarPathStarts): bool {
    foreach ($prefixes as $prefix) {
        if ($sidebarPathStarts($prefix)) {
            return true;
        }
    }

    return false;
};
$sidebarActiveDashboard = $sidebarPathStarts('dashboard');
$sidebarActiveOperacionDiaria = $sidebarPathStarts('operacion/diaria');
$sidebarActiveForecast = $sidebarPathStarts('forecast');
$sidebarActiveAuditoria = $sidebarPathStarts('auditoria');
$sidebarActiveNightAudit = $sidebarPathStarts('night-audit');
$sidebarActiveReservaciones = $sidebarPathStarts('reservaciones');
$sidebarActiveHabitaciones = $sidebarPathStarts('habitaciones');
$sidebarActiveHuespedes = $sidebarPathStarts('huespedes');
$sidebarActiveCaja = $sidebarPathStarts('caja');
$sidebarActiveCuentasPorCobrar = $sidebarPathStarts('cuentas-por-cobrar');
$sidebarActiveFacturacion = $sidebarPathStarts('facturacion');
$sidebarActiveCuentasPorPagar = $sidebarPathStarts('cuentas-por-pagar');
$sidebarActiveTareas = $sidebarPathStarts('tareas');
$sidebarActiveInventario = $sidebarPathStarts('inventario');
$sidebarActiveCompras = $sidebarPathStarts('compras');
$sidebarActiveProveedores = $sidebarPathStarts('proveedores');
$sidebarActiveDocumentos = $sidebarPathStarts('documentos');
$sidebarActiveReportes = $sidebarPathStarts('reportes');
$sidebarActivePersonal = $sidebarPathStarts('trabajadores');
$sidebarActiveUsuarios = $sidebarPathStarts('usuarios');
$sidebarActiveNotificaciones = $sidebarPathStarts('notificaciones');
$sidebarActiveMotorReservas = $sidebarPathStarts('motor-reservas');
$sidebarActiveIaEjecutiva = $sidebarPathStarts('ia');
$sidebarActiveWhatsApp = $sidebarPathStarts('whatsapp');
$sidebarActiveReputacion = $sidebarPathStarts('reputacion');
$sidebarActiveCheckinDigital = $sidebarPathStarts('checkin-digital');
$sidebarActiveCanales = $sidebarPathStarts('canales');
$sidebarActiveCamarista = $sidebarPathStarts('camarista');
$sidebarActiveConfiguracion = $sidebarPathStarts('configuracion') && !$sidebarPathStarts('configuracion/tarifas') && !$sidebarPathStarts('configuracion/roles');
$sidebarActiveTarifas = $sidebarPathIn(['configuracion/tarifas', 'tarifas']);
$sidebarActiveRoles = $sidebarPathStarts('configuracion/roles');
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
$sidebarFacturasPendientes = 0;

if (!$sidebarEsPanelSaas && function_exists('has_hotel_context') && has_hotel_context() && function_exists('current_hotel_id')) {
    $sidebarHotelId = (int) current_hotel_id();

    try {
        require_once APP_PATH . '/models/Notificacion.php';
        $sidebarNotificacionModel = new Notificacion();
        $sidebarResumenNotificaciones = $sidebarNotificacionModel->resumenPorHotel(
            $sidebarHotelId,
            function_exists('current_hotel_user_role') ? current_hotel_user_role() : null,
            function_exists('user_id') ? user_id() : null
        );
        $sidebarNotificacionesNoLeidas = (int)($sidebarResumenNotificaciones['nuevas'] ?? 0);
    } catch (Throwable $e) {
        error_log('No se pudo contar notificaciones del sidebar: ' . $e->getMessage());
        $sidebarNotificacionesNoLeidas = 0;
    }

    if ($mostrarFacturacion && $sidebarHotelId > 0 && class_exists('Database')) {
        try {
            $sidebarDb = Database::getInstance();
            $sidebarTablaFacturas = $sidebarDb->query(
                "SELECT 1
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'solicitudes_factura'
                 LIMIT 1"
            );

            if ($sidebarTablaFacturas && $sidebarTablaFacturas->fetch()) {
                $sidebarFacturasStmt = $sidebarDb->query(
                    "SELECT COUNT(*) AS total
                     FROM solicitudes_factura
                     WHERE hotel_id = ?
                       AND estatus IN ('pendiente', 'datos_capturados')",
                    [$sidebarHotelId]
                );

                if ($sidebarFacturasStmt) {
                    $sidebarFacturasRow = $sidebarFacturasStmt->fetch(PDO::FETCH_ASSOC);
                    $sidebarFacturasPendientes = (int)($sidebarFacturasRow['total'] ?? 0);
                }
            }
        } catch (Throwable $e) {
            error_log('No se pudo contar facturas pendientes del sidebar: ' . $e->getMessage());
            $sidebarFacturasPendientes = 0;
        }
    }
}
?>

<?php
// Datos para el menú móvil boutique (solo sistema hotelero).
$sidebarUsuarioNombre = function_exists('user_name') ? (string) user_name() : 'Usuario';
$sidebarUsuarioRol = function_exists('user_role') ? (string) user_role() : '';
$sidebarIniciales = '';
foreach (preg_split('/\s+/', trim($sidebarUsuarioNombre)) as $sidebarParte) {
    if ($sidebarParte === '') { continue; }
    $sidebarIniciales .= function_exists('mb_substr') ? mb_substr($sidebarParte, 0, 1, 'UTF-8') : substr($sidebarParte, 0, 1);
    if ((function_exists('mb_strlen') ? mb_strlen($sidebarIniciales, 'UTF-8') : strlen($sidebarIniciales)) >= 2) { break; }
}
$sidebarIniciales = $sidebarIniciales !== ''
    ? (function_exists('mb_strtoupper') ? mb_strtoupper($sidebarIniciales, 'UTF-8') : strtoupper($sidebarIniciales))
    : 'U';
$sidebarAppVersion = '1.0.0';
$sidebarConfigApp = @include dirname(APP_PATH) . '/config/app.php';
if (is_array($sidebarConfigApp) && !empty($sidebarConfigApp['version'])) {
    $sidebarAppVersion = (string) $sidebarConfigApp['version'];
}
?>
<aside id="sidebar" class="sidebar-main sidebar-fixed <?= $sidebarEsPanelSaas ? 'sidebar-saas' : 'hotel-sidebar' ?>">
    <?php if (!$sidebarEsPanelSaas): ?>
    <!-- ── Menú móvil boutique: tarjeta de perfil (solo ≤1024px).
         El header y el footer del sistema permanecen visibles; se cierra
         desde el botón Menú del footer. ── -->
    <div class="ms-mm-prof">
        <div class="ms-mm-ava"><?= htmlspecialchars($sidebarIniciales, ENT_QUOTES, 'UTF-8') ?><span class="ms-mm-ring" aria-hidden="true"></span></div>
        <div class="ms-mm-pinfo">
            <div class="ms-mm-pn"><?= htmlspecialchars($sidebarUsuarioNombre, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="ms-mm-pe"><?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?></div>
            <?php if ($sidebarUsuarioRol !== ''): ?>
            <span class="ms-mm-pr">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 3 2.6 5.5 6 .8-4.4 4.2 1.1 6L12 16.9 6.7 19.5l1.1-6L3.4 9.3l6-.8z"/></svg>
                <?= htmlspecialchars($sidebarUsuarioRol, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
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
                    title="Buscar pantalla, huésped o reservación"
                    aria-label="Buscar pantalla, huésped o reservación">
                <i class="fas fa-search"></i>
            </button>
            <input type="text"
                   id="buscador-global-input"
                   placeholder="Buscar pantalla, huésped o reserva"
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
        <?php
        // Catalogo usado por el buscador global; no se renderiza como seccion visible.
        $navPantallasBusqueda = function_exists('nav_pantallas_visibles') ? nav_pantallas_visibles() : [];
        $navRutasFavoritas = function_exists('nav_favoritos_rutas') ? nav_favoritos_rutas() : [];
        ?>
        <script>
            window.MS_NAV_PANTALLAS = <?= json_encode(array_map(static function ($p) use ($navRutasFavoritas) {
                return [
                    'ruta' => $p['ruta'],
                    'etiqueta' => $p['etiqueta'],
                    'icono' => $p['icono'],
                    'buscar' => $p['buscar'] ?? '',
                    'favorito' => in_array($p['ruta'], $navRutasFavoritas, true),
                ];
            }, $navPantallasBusqueda), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?: '[]' ?>;
        </script>
        <div class="nav-section hotel-nav-section hotel-dashboard-section">
            <div class="nav-section-title ms-mm-only">
                <span>INICIO</span>
            </div>
            <?php if ($mostrarDashboard): ?>
            <a href="<?= url('dashboard') ?>"
               class="nav-item <?= $sidebarActiveDashboard ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-th-large"></i>
                </div>
                <span class="nav-text">Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarOperacionDiaria): ?>
            <a href="<?= url('operacion/diaria') ?>"
               class="nav-item <?= $sidebarActiveOperacionDiaria ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <span class="nav-text">Operacion diaria</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarForecast): ?>
            <a href="<?= url('forecast') ?>"
               class="nav-item <?= $sidebarActiveForecast ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Forecast</span>
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
                <span>RECEPCIÓN</span>
            </div>

            <?php if ($mostrarHabitaciones): ?>
            <a href="<?= url('habitaciones') ?>"
               class="nav-item <?= $sidebarActiveHabitaciones ? 'active' : '' ?>">
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
               class="nav-item <?= $sidebarActiveReservaciones ? 'active' : '' ?>">
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
               class="nav-item <?= $sidebarActiveHuespedes ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="nav-text">Huéspedes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCheckinDigital): ?>
            <a href="<?= url('checkin-digital') ?>"
               class="nav-item <?= $sidebarActiveCheckinDigital ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-id-badge"></i>
                </div>
                <span class="nav-text">Check-in digital</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarFinanzas): ?>
        <div class="nav-section hotel-nav-section hotel-operaciones-section hotel-finanzas-section">
            <div class="nav-section-title">
                <span>FINANZAS</span>
            </div>

            <?php if ($mostrarCaja): ?>
            <a href="<?= url('caja') ?>"
               class="nav-item <?= $sidebarActiveCaja ? 'active' : '' ?>">
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

            <?php if ($mostrarCuentasPorCobrar): ?>
            <a href="<?= url('cuentas-por-cobrar') ?>"
               class="nav-item <?= $sidebarActiveCuentasPorCobrar ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-hand-holding-dollar"></i>
                </div>
                <span class="nav-text">Cuentas por cobrar</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarFacturacion): ?>
            <a href="<?= url('facturacion') ?>"
               class="nav-item <?= $sidebarActiveFacturacion ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice"></i>
                    <?php if ($sidebarFacturasPendientes > 0): ?>
                    <span class="nav-badge nav-badge-billing"><?= $sidebarFacturasPendientes > 99 ? '99+' : (int)$sidebarFacturasPendientes ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Facturación</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCuentasPorPagar): ?>
            <a href="<?= url('cuentas-por-pagar') ?>"
               class="nav-item <?= $sidebarActiveCuentasPorPagar ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <span class="nav-text">Cuentas por pagar</span>
            </a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <?php if ($mostrarOperacionInterna): ?>
        <div class="nav-section hotel-nav-section hotel-operaciones-section hotel-operacion-interna-section">
            <div class="nav-section-title">
                <span>OPERACIÓN</span>
            </div>

            <?php if ($mostrarTareas): ?>
            <a href="<?= url('tareas') ?>"
               class="nav-item <?= $sidebarActiveTareas ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <span class="nav-text">Tareas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCamarista): ?>
            <a href="<?= url('camarista') ?>"
               class="nav-item <?= $sidebarActiveCamarista ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-broom"></i>
                </div>
                <span class="nav-text">Limpieza</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarInventario): ?>
            <a href="<?= url('inventario') ?>"
               class="nav-item <?= $sidebarActiveInventario ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-box"></i>
                </div>
                <span class="nav-text">Inventarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCompras): ?>
            <a href="<?= url('compras') ?>"
               class="nav-item <?= $sidebarActiveCompras ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <span class="nav-text">Compras</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarProveedores): ?>
            <a href="<?= url('proveedores') ?>"
               class="nav-item <?= $sidebarActiveProveedores ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="nav-text">Proveedores</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarDocumentos): ?>
            <a href="<?= url('documentos') ?>"
               class="nav-item <?= $sidebarActiveDocumentos ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <span class="nav-text">Documentos</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarNightAudit): ?>
            <a href="<?= url('night-audit') ?>"
               class="nav-item <?= $sidebarActiveNightAudit ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <span class="nav-text">Night audit</span>
            </a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <?php if ($mostrarVentasCanales): ?>
        <div class="nav-section hotel-nav-section hotel-ventas-section">
            <div class="nav-section-title">
                <span>VENTAS Y CANALES</span>
            </div>

            <?php if ($mostrarMotorReservas): ?>
            <a href="<?= url('motor-reservas') ?>"
               class="nav-item <?= $sidebarActiveMotorReservas ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <span class="nav-text">Motor de reservas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCanales): ?>
            <a href="<?= url('canales') ?>"
               class="nav-item <?= $sidebarActiveCanales ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span class="nav-text">Canales (iCal)</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarWhatsApp): ?>
            <a href="<?= url('whatsapp') ?>"
               class="nav-item <?= $sidebarActiveWhatsApp ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="nav-text">WhatsApp</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarIaEjecutiva): ?>
            <a href="<?= url('ia/resumen-diario') ?>"
               class="nav-item <?= $sidebarActiveIaEjecutiva ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <span class="nav-text">Asesor IA</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarReputacion): ?>
            <a href="<?= url('reputacion') ?>"
               class="nav-item <?= $sidebarActiveReputacion ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-star"></i>
                </div>
                <span class="nav-text">Reputación</span>
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
               class="nav-item <?= $sidebarActiveReportes ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Reportes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarPersonal): ?>
            <a href="<?= url('trabajadores') ?>"
               class="nav-item <?= $sidebarActivePersonal ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <span class="nav-text">Personal</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarUsuariosAdmin): ?>
            <a href="<?= url('usuarios') ?>"
               class="nav-item <?= $sidebarActiveUsuarios ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarNotificacionesMenu): ?>
            <a href="<?= url('notificaciones') ?>"
               class="nav-item <?= $sidebarActiveNotificaciones ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-bell"></i>
                    <?php if ($sidebarNotificacionesNoLeidas > 0): ?>
                    <span class="nav-badge nav-badge-notifications"><?= $sidebarNotificacionesNoLeidas > 99 ? '99+' : (int)$sidebarNotificacionesNoLeidas ?></span>
                    <?php endif; ?>
                </div>
                <span class="nav-text">Notificaciones</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarAuditoria): ?>
            <a href="<?= url('auditoria') ?>"
               class="nav-item <?= $sidebarActiveAuditoria ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <span class="nav-text">Bitácora</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($mostrarConfigSeccion): ?>
        <div class="nav-section hotel-nav-section hotel-config-section">
            <div class="nav-section-title">
                <span>CONFIGURACIÓN</span>
            </div>

            <?php if ($mostrarConfiguracion): ?>
            <a href="<?= url('configuracion') ?>"
               class="nav-item <?= $sidebarActiveConfiguracion ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="nav-text">Configuración</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarTarifas): ?>
            <a href="<?= url('configuracion/tarifas') ?>"
               class="nav-item <?= $sidebarActiveTarifas ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Tarifas dinámicas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarRoles): ?>
            <a href="<?= url('configuracion/roles') ?>"
               class="nav-item <?= $sidebarActiveRoles ? 'active' : '' ?>">
                <div class="nav-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <span class="nav-text">Roles y permisos</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!$sidebarEsPanelSaas): ?>
        <!-- ── Menú móvil boutique: cerrar sesión + versión (solo ≤1024px) ── -->
        <div class="ms-mm-bottom">
            <?php if ($sidebarMostrarLimpiezaOffline): ?>
            <button type="button" class="ms-mm-cleanup" onclick="document.getElementById('manual-offline-cleanup-btn') && document.getElementById('manual-offline-cleanup-btn').click();">
                <i class="fas fa-broom" aria-hidden="true"></i>
                <span>Limpiar datos offline</span>
            </button>
            <?php endif; ?>
            <form method="POST" action="<?= url('logout') ?>" class="ms-mm-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="ms-mm-logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M16 12H3m0 0 4-4m-4 4 4 4M20 4v16"/></svg>
                    Cerrar sesión
                </button>
            </form>
            <div class="ms-mm-ver">Medisoft Hoteles · versión <?= htmlspecialchars($sidebarAppVersion, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
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

.hotel-layout-scope .hotel-sidebar .nav-badge.nav-badge-billing,
.nav-badge.nav-badge-billing {
    top: -2px !important;
    right: -3px !important;
    min-width: 15px !important;
    height: 15px !important;
    border-radius: 999px !important;
    padding: 0 4px !important;
    font-size: .52rem !important;
    line-height: 15px !important;
    font-weight: 800 !important;
    background: var(--brand-accent, #B45309) !important;
    color: #FFFEFB !important;
    box-shadow: 0 0 0 2px var(--hotel-panel, #FFFFFF), 0 6px 14px rgba(180, 83, 9, 0.24) !important;
    animation: none !important;
}

.hotel-layout-scope .hotel-sidebar .nav-item.active .nav-badge.nav-badge-notifications,
.nav-item.active .nav-badge.nav-badge-notifications {
    background: #dc2626 !important;
    color: #FFFEFB !important;
    box-shadow: 0 0 0 2px var(--hotel-panel, #FFFFFF) !important;
}

.hotel-layout-scope .hotel-sidebar .nav-item.active .nav-badge.nav-badge-billing,
.nav-item.active .nav-badge.nav-badge-billing {
    background: var(--brand-accent, #B45309) !important;
    color: #FFFEFB !important;
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

            // El menú convive con header y footer visibles: la hamburguesa
            // alterna abrir/cerrar y no se usa overlay oscurecedor.
            mobileMenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var abierto = sidebar.classList.toggle('active');
                document.body.style.overflow = abierto ? 'hidden' : '';
                mobileMenuToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
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

<?php if (!$sidebarEsPanelSaas): ?>
<style>
/* ═══════════════════════════════════════════════════════════════
   MENÚ MÓVIL BOUTIQUE (≤1024px) — reskin del drawer del hotel.
   Prefijo #sidebar.hotel-sidebar: gana a las reglas !important de
   3 clases del shell (.hotel-layout-scope .hotel-sidebar ...).
   El desktop no se toca: las piezas ms-mm-* viven ocultas ahí.
   ═══════════════════════════════════════════════════════════════ */
.ms-mm-prof, .ms-mm-bottom { display: none; }
.nav-section-title.ms-mm-only { display: none !important; }

@media (max-width: 1024px) {
    /* El menú vive ENTRE el header (arriba, z~10000) y el footer flotante
     * (z 980): ambos permanecen visibles, como en cualquier otra vista. */
    #sidebar.sidebar-main.hotel-sidebar {
        top: 64px !important;
        bottom: auto !important;
        height: calc(100dvh - 64px) !important;
        max-height: calc(100dvh - 64px) !important;
        z-index: 900 !important;
        width: 100vw !important;
        max-width: 100vw !important;
        background: #FBF8F2 !important;
        border-right: 0 !important;
        box-shadow: none !important;
        padding: 8px 12px 0 !important;
        display: flex !important;
        flex-direction: column !important;
        font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        /* Sin animación de entrada: el menú aparece al instante,
         * como cualquier otra vista del sistema. */
        transform: translateX(-102%) !important;
        transition: none !important;
    }
    #sidebar.sidebar-main.hotel-sidebar.active {
        transform: translateX(0) !important;
    }
    @supports not (height: 100dvh) {
        #sidebar.sidebar-main.hotel-sidebar {
            height: calc(100vh - 64px) !important;
            max-height: calc(100vh - 64px) !important;
        }
    }

    /* El contenido del menú pasa por debajo del footer de vidrio */
    body.has-hotel-bottom-nav #sidebar.hotel-sidebar .sidebar-nav {
        padding-bottom: calc(var(--hbn-offset, 84px) + 16px) !important;
    }

    /* Tarjeta de perfil */
    #sidebar.hotel-sidebar .ms-mm-prof {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #FFFFFF;
        border: 1px solid #ECE5D8;
        border-radius: 16px;
        padding: 11px 12px;
        box-shadow: 0 1px 2px rgba(27,39,70,.05);
        margin-bottom: 2px;
        flex: none;
    }
    #sidebar.hotel-sidebar .ms-mm-ava {
        width: 48px; height: 48px; border-radius: 14px;
        display: grid; place-items: center;
        color: #fff;
        font-family: 'Cormorant Garamond', Georgia, serif;
        font-weight: 600; font-size: 19px;
        background: linear-gradient(150deg, var(--brand-secondary, #2E3F66), var(--brand-primary, #1B2746));
        flex: none; position: relative;
    }
    #sidebar.hotel-sidebar .ms-mm-ring {
        position: absolute; inset: -3px; border-radius: 17px;
        border: 1.5px solid var(--brand-accent, #E4D4B0);
        opacity: .55;
    }
    #sidebar.hotel-sidebar .ms-mm-pinfo { flex: 1; min-width: 0; }
    #sidebar.hotel-sidebar .ms-mm-pn {
        font-size: 15px; font-weight: 700;
        color: var(--brand-primary, #1B2746);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    #sidebar.hotel-sidebar .ms-mm-pe {
        font-size: 11.5px; color: #6C7689; margin-top: 2px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    #sidebar.hotel-sidebar .ms-mm-pr {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 10.5px; font-weight: 800; letter-spacing: .03em;
        text-transform: uppercase;
        color: var(--brand-accent, #B0883F);
        background: color-mix(in srgb, var(--brand-accent, #B0883F) 12%, #FFFFFF);
        border: 1px solid color-mix(in srgb, var(--brand-accent, #B0883F) 32%, #FFFFFF);
        padding: 3px 9px; border-radius: 99px; margin-top: 6px;
    }
    #sidebar.hotel-sidebar .ms-mm-pr svg { width: 11px; height: 11px; }

    /* Marca y footer de escritorio fuera en móvil */
    #sidebar.hotel-sidebar .sidebar-header,
    #sidebar.hotel-sidebar .sidebar-footer { display: none !important; }

    /* Buscador como tarjeta */
    #sidebar.hotel-sidebar .sidebar-search { padding: 8px 0 4px !important; flex: none; }
    #sidebar.hotel-sidebar .sidebar-search .search-box {
        background: #FFFFFF !important;
        border: 1px solid #ECE5D8 !important;
        border-radius: 14px !important;
        box-shadow: 0 1px 2px rgba(27,39,70,.05) !important;
    }
    #sidebar.hotel-sidebar .sidebar-search .search-input {
        color: var(--brand-primary, #1B2746) !important;
        font-size: 14px !important;
    }

    /* Navegación */
    #sidebar.hotel-sidebar .sidebar-nav {
        flex: 1 !important;
        overflow-y: auto !important;
        padding: 0 0 10px !important;
        margin: 0 !important;
        gap: 0 !important;
        scrollbar-width: none;
    }
    #sidebar.hotel-sidebar .sidebar-nav::-webkit-scrollbar { width: 0; }

    #sidebar.hotel-sidebar .nav-section {
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    /* La etiqueta del grupo vive en el espacio entre tarjetas */
    #sidebar.hotel-sidebar .nav-section-title {
        display: block !important;
        margin: 0 4px 8px !important;
        padding: 16px 0 0 !important;
        border: 0 !important;
        position: static !important;
    }
    #sidebar.hotel-sidebar .nav-section-title.ms-mm-only { display: block !important; }
    #sidebar.hotel-sidebar .hotel-dashboard-section .nav-section-title { padding-top: 10px !important; }
    #sidebar.hotel-sidebar .nav-section-title::before,
    #sidebar.hotel-sidebar .nav-section-title::after { display: none !important; content: none !important; }
    #sidebar.hotel-sidebar .nav-section-title span {
        font-size: 11.5px !important;
        font-weight: 800 !important;
        letter-spacing: .08em !important;
        text-transform: uppercase !important;
        color: #939BAD !important;
        opacity: 1 !important;
        display: inline !important;
    }

    /* Filas dentro de tarjetas blancas */
    #sidebar.hotel-sidebar .nav-item {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        margin: 0 !important;
        padding: 12px 13px !important;
        min-height: 0 !important;
        background: #FFFFFF !important;
        border: 1px solid #ECE5D8 !important;
        border-top-width: 0 !important;
        border-radius: 0 !important;
        color: var(--brand-primary, #1B2746) !important;
        font-size: 14.5px !important;
        font-weight: 600 !important;
        box-shadow: none !important;
        transform: none !important;
        overflow: visible !important;
    }
    #sidebar.hotel-sidebar .nav-section a.nav-item:first-of-type {
        border-top-width: 1px !important;
        border-top-left-radius: 16px !important;
        border-top-right-radius: 16px !important;
    }
    #sidebar.hotel-sidebar .nav-section a.nav-item:last-of-type {
        border-bottom-left-radius: 16px !important;
        border-bottom-right-radius: 16px !important;
        box-shadow: 0 1px 2px rgba(27,39,70,.05) !important;
    }
    #sidebar.hotel-sidebar .nav-item:hover,
    #sidebar.hotel-sidebar .nav-item:active {
        background: #FEFCF7 !important;
        color: var(--brand-primary, #1B2746) !important;
        transform: none !important;
    }
    #sidebar.hotel-sidebar .nav-item .nav-text {
        color: inherit !important;
        font-size: inherit !important;
        font-weight: inherit !important;
        opacity: 1 !important;
        flex: 1 !important;
        min-width: 0;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* Separador interno inset: arranca donde arranca el texto */
    #sidebar.hotel-sidebar .nav-section a.nav-item + a.nav-item::before {
        content: '' !important;
        position: absolute !important;
        left: 61px !important; right: 0 !important; top: 0 !important;
        bottom: auto !important;
        width: auto !important;
        height: 1px !important;
        background: #F1ECE2 !important;
        border-radius: 0 !important;
        opacity: 1 !important;
        transform: none !important;
    }

    /* Chevron a la derecha (redefine el ::after decorativo del shell) */
    #sidebar.hotel-sidebar .nav-item::after {
        content: '' !important;
        display: block !important;
        position: static !important;
        inset: auto !important;
        width: 8px !important; height: 8px !important; flex: none;
        border: 0 !important;
        border-top: 2px solid #B7BDCB !important;
        border-right: 2px solid #B7BDCB !important;
        border-radius: 0 !important;
        background: none !important;
        opacity: 1 !important;
        z-index: auto !important;
        transform: rotate(45deg) !important;
        margin-left: auto !important;
        margin-right: 2px !important;
        pointer-events: none;
    }
    #sidebar.hotel-sidebar .nav-item:has(.nav-badge)::after { display: none !important; }

    /* Icono como tile de color */
    #sidebar.hotel-sidebar .nav-item .nav-icon {
        position: static !important;
        width: 34px !important;
        height: 34px !important;
        min-width: 34px !important;
        border-radius: 10px !important;
        display: grid !important;
        place-items: center !important;
        background: var(--ms-mm-ib, #FBF8F2) !important;
        color: var(--ms-mm-ic, #6C7689) !important;
        flex: none;
        margin: 0 !important;
        box-shadow: none !important;
    }
    #sidebar.hotel-sidebar .nav-item .nav-icon i {
        font-size: 15px !important;
        color: inherit !important;
        width: auto !important;
        opacity: 1 !important;
    }

    /* Rotación de tintes semánticos por fila (paleta boutique) */
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+1) .nav-icon { --ms-mm-ib: #ECEBFB; --ms-mm-ic: #5A57D2; }
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+2) .nav-icon { --ms-mm-ib: #E7F4EC; --ms-mm-ic: #1E9E63; }
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+3) .nav-icon { --ms-mm-ib: #FAF0DC; --ms-mm-ic: #C2841C; }
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+4) .nav-icon { --ms-mm-ib: #E2F2F6; --ms-mm-ic: #0E96B8; }
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+5) .nav-icon { --ms-mm-ib: #E6EFFC; --ms-mm-ic: #2F77E0; }
    #sidebar.hotel-sidebar .nav-section a.nav-item:nth-of-type(6n+6) .nav-icon { --ms-mm-ib: #ECEFF4; --ms-mm-ic: #5B6B86; }

    /* Ítem activo: tile con el acento del hotel, sin píldora ni gradiente */
    #sidebar.hotel-sidebar .nav-item.active {
        background: #FFFFFF !important;
        color: var(--brand-primary, #1B2746) !important;
        font-weight: 800 !important;
        box-shadow: none !important;
        transform: none !important;
    }
    #sidebar.hotel-sidebar .nav-section a.nav-item.active:last-of-type {
        box-shadow: 0 1px 2px rgba(27,39,70,.05) !important;
    }
    #sidebar.hotel-sidebar .nav-item.active .nav-icon {
        background: color-mix(in srgb, var(--brand-accent, #B0883F) 16%, #FFFFFF) !important;
        color: var(--brand-accent, #B0883F) !important;
    }
    #sidebar.hotel-sidebar .nav-item.active .nav-text { color: inherit !important; }

    /* Badges numéricos a la derecha (estilo pastilla) */
    #sidebar.hotel-sidebar .nav-item .nav-badge {
        position: absolute !important;
        right: 14px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
        left: auto !important;
        bottom: auto !important;
        min-width: 20px !important;
        height: 20px !important;
        padding: 0 6px !important;
        border-radius: 99px !important;
        background: #D64539 !important;
        color: #fff !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        line-height: 20px !important;
        text-align: center !important;
        box-shadow: none !important;
        animation: none !important;
    }
    #sidebar.hotel-sidebar .nav-item .nav-badge.pulse-green {
        background: #1E9E63 !important;
        min-width: 10px !important;
        height: 10px !important;
        padding: 0 !important;
        right: 18px !important;
    }
    #sidebar.hotel-sidebar .nav-item .nav-badge.nav-badge-billing {
        background: var(--brand-accent, #B45309) !important;
    }

    /* Bloque final: limpiar offline + cerrar sesión + versión */
    #sidebar.hotel-sidebar .ms-mm-bottom { display: block; padding: 6px 2px 4px; }
    #sidebar.hotel-sidebar .ms-mm-cleanup {
        display: flex; align-items: center; justify-content: center; gap: 9px;
        width: 100%; margin-top: 18px; padding: 12px;
        border-radius: 14px;
        background: transparent; border: 1px dashed #D9D2C4;
        color: #6C7689;
        font-family: inherit; font-size: 13px; font-weight: 700;
        cursor: pointer;
    }
    #sidebar.hotel-sidebar .ms-mm-logout-form { margin: 0; }
    #sidebar.hotel-sidebar .ms-mm-logout {
        display: flex; align-items: center; justify-content: center; gap: 9px;
        width: 100%; margin-top: 12px; padding: 14px;
        border-radius: 14px;
        background: #FFFFFF;
        border: 1px solid rgba(214,69,57,.22);
        color: #D64539;
        font-family: inherit; font-size: 14.5px; font-weight: 700;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(27,39,70,.05);
        transition: background .14s;
    }
    #sidebar.hotel-sidebar .ms-mm-logout:active { background: #FBE9E7; }
    #sidebar.hotel-sidebar .ms-mm-logout svg { width: 18px; height: 18px; flex: none; }
    #sidebar.hotel-sidebar .ms-mm-ver {
        text-align: center;
        font-size: 11.5px; color: #939BAD; font-weight: 600;
        margin-top: 14px;
    }
}
</style>

<?php endif; ?>
