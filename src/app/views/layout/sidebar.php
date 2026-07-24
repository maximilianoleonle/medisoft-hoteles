<?php
$menuModuloActivo = function ($clave) {
    return function_exists('hotel_menu_module_enabled') ? hotel_menu_module_enabled($clave) : true;
};

$menuModulosSinConfigurar = function_exists('hotel_menu_modules_unconfigured') && hotel_menu_modules_unconfigured();
// Puerta de permiso por pantalla. Un rol acotado (p. ej. camarista) solo ve en
// el menu los modulos de alguno de cuyos permisos dispone; direccion y
// recepcion conservan su alcance. Es visibilidad de MENU, no el control de
// seguridad: los gates de servidor son aparte y mandan ellos.
//
// UNA SOLA VERDAD (auditoria de accesos, 23 jul 2026): el menu se gatea SOLO
// por permiso. Se retiro $sidebarEsGestion (rol-string 'gerente'/'administrador'),
// que fallaba por los dos lados: no reconocia los roles personalizados (todos
// guardan el ENUM 'recepcionista') y a la vez mostraba entradas que el
// controlador luego rechazaba. Ahora el menu enseña exactamente lo que el
// servidor deja pasar. Los permisos de cada area estan en config/permisos.php.
$puede = static function (string ...$perms) : bool {
    if (!function_exists('can')) { return true; }
    foreach ($perms as $perm) { if (can($perm)) { return true; } }
    return false;
};

$mostrarDashboard = $menuModuloActivo('dashboard') && (function_exists('nav_puede_ver_dashboard') ? nav_puede_ver_dashboard() : true);
$mostrarOperacionDiaria = $menuModuloActivo('tablero_ejecutivo') && $puede('operacion.view');
$mostrarForecast = $menuModuloActivo('forecast') && $puede('forecast.view');
$mostrarHabitaciones = $menuModuloActivo('habitaciones') && $puede('habitaciones.view');
$mostrarReservaciones = $menuModuloActivo('reservaciones') && $puede('reservaciones.view');
$mostrarHuespedes = $menuModuloActivo('huespedes') && $puede('huespedes.view');
$mostrarCaja = $menuModuloActivo('caja') && $puede('caja.view');
$mostrarInventario = $menuModuloActivo('inventario') && $puede('inventarios.view');
$mostrarCompras = $menuModuloActivo('compras') && $puede('compras.view');
$mostrarProveedores = $menuModuloActivo('compras') && $puede('proveedores.view');
$mostrarCuentasPorPagar = $menuModuloActivo('compras') && $puede('cuentas_por_pagar.view');
$mostrarDocumentos = $menuModuloActivo('documentos') && $puede('documentos.view');
$mostrarTareas = $menuModuloActivo('tareas') && $puede('tareas.view');
$mostrarMantenimientoPlus = $menuModuloActivo('mantenimiento_plus') && $puede('tareas.view', 'habitaciones.mantenimiento');
$mostrarLavanderia = $menuModuloActivo('lavanderia') && $puede('lavanderia.view');
$mostrarFacturacion = $menuModuloActivo('facturacion') && $puede('facturacion.view');
$mostrarCuentasPorCobrar = $menuModuloActivo('cuentas_cobrar') && $puede('cuentas_por_cobrar.view');
$mostrarReportes = $menuModuloActivo('reportes') && $puede('reportes.view');
$mostrarUsuariosModulo = $menuModuloActivo('usuarios');
$mostrarConfiguracionModulo = $menuModuloActivo('configuracion');
$mostrarTarifasModulo = $menuModuloActivo('tarifas_dinamicas') || $menuModuloActivo('tarifas');
$sidebarPuedeUsuarios = can('usuarios.view');
$sidebarPuedeConfiguracion = can('configuracion.view');
// Tarifas: gatear por permiso del HOTEL, nunca por is_gerente()/is_admin()
// (leen usuarios.rol GLOBAL, ajeno al hotel actual: un camarista cuyo usuario
// tenga rol global 'gerente' NO debe ver Tarifas).
$sidebarPuedeTarifas = $puede('tarifas.view', 'tarifas.edit');
$mostrarFinanzas = $mostrarCaja || $mostrarCuentasPorCobrar || $mostrarFacturacion || $mostrarCuentasPorPagar;
$filtrarMenuHotel = function_exists('hotel_menu_should_filter_modules') && hotel_menu_should_filter_modules();
$mostrarUsuariosAdmin = (!$filtrarMenuHotel && can('usuarios.view')) || ($filtrarMenuHotel && $mostrarUsuariosModulo && $sidebarPuedeUsuarios);
// Personal tiene su propio permiso; antes colgaba del de Usuarios.
$mostrarPersonal = $menuModuloActivo('personal') && $puede('personal.view');
$mostrarNomina = $menuModuloActivo('nomina_avanzada') && can('nomina.view');
$mostrarConfiguracion = $mostrarConfiguracionModulo && $sidebarPuedeConfiguracion;
$mostrarTarifas = $sidebarPuedeTarifas && (!$filtrarMenuHotel || $mostrarTarifasModulo);
$mostrarRoles = function_exists('can') && can('roles.manage') && $menuModuloActivo('roles_avanzados');
$mostrarAuditoria = $menuModuloActivo('auditoria') && $puede('auditoria.view');
$mostrarMotorReservas = $menuModuloActivo('motor_reservas') && $puede('motor_reservas.view');
$mostrarIaEjecutiva = $menuModuloActivo('ia_ejecutiva') && $puede('ia.view');
// Guardian (vigilancia financiera): gating temporal en ia_ejecutiva (al monetizar, usar su
// propio modulo) + permiso guardian.view: el informe nombra usuarios, solo direccion lo ve.
$mostrarVigilanciaFinanciera = $mostrarIaEjecutiva && function_exists('can') && can('guardian.view');
// Panel de valor del copiloto: uso real del asistente.
$mostrarCopilotoPanel = $menuModuloActivo('copiloto') && $puede('copiloto.valor');
$mostrarWhatsApp = $menuModuloActivo('whatsapp') && $puede('whatsapp.view');
$mostrarMensajes = $menuModuloActivo('canal_whatsapp') && $puede('mensajes.view');
$mostrarCheckinDigital = $menuModuloActivo('checkin_digital') && $puede('checkin_digital.view');
$mostrarCanales = $menuModuloActivo('canales_ical') && $puede('canales.view');
$mostrarCamarista = $menuModuloActivo('camarista') && $puede('camarista.view', 'tareas.view');
$mostrarReputacion = $menuModuloActivo('reputacion') && $puede('reputacion.view');
$mostrarNightAudit = $menuModuloActivo('night_audit') && $puede('night_audit.view');
$mostrarLealtad = $menuModuloActivo('lealtad') && $puede('lealtad.view');
// Agrupación del menú: Recepción incluye check-in digital; Operación incluye limpieza;
// Ventas y canales agrupa los bloques comerciales; Configuración va aparte de Administración.
$mostrarGestion = $mostrarHabitaciones || $mostrarReservaciones || $mostrarHuespedes || $mostrarCheckinDigital;
$mostrarOperacionInterna = $mostrarTareas || $mostrarMantenimientoPlus || $mostrarLavanderia || $mostrarCamarista || $mostrarInventario || $mostrarCompras || $mostrarProveedores || $mostrarDocumentos || $mostrarNightAudit;
$mostrarVentasCanales = $mostrarMotorReservas || $mostrarCanales || $mostrarWhatsApp || $mostrarMensajes || $mostrarIaEjecutiva || $mostrarReputacion || $mostrarLealtad;
$mostrarAdministracion = ($mostrarReportes || $mostrarUsuariosAdmin || $mostrarPersonal || $mostrarNomina || $mostrarAuditoria);
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
$sidebarActiveLealtad = $sidebarPathStarts('lealtad');
$sidebarActiveReservaciones = $sidebarPathStarts('reservaciones');
$sidebarActiveHabitaciones = $sidebarPathStarts('habitaciones');
$sidebarActiveHuespedes = $sidebarPathStarts('huespedes');
$sidebarActiveCaja = $sidebarPathStarts('caja');
$sidebarActiveCuentasPorCobrar = $sidebarPathStarts('cuentas-por-cobrar');
$sidebarActiveFacturacion = $sidebarPathStarts('facturacion');
$sidebarActiveCuentasPorPagar = $sidebarPathStarts('cuentas-por-pagar');
$sidebarActiveTareas = $sidebarPathStarts('tareas');
$sidebarActiveMantenimientos = $sidebarPathStarts('mantenimientos');
$sidebarActiveLavanderia = $sidebarPathStarts('lavanderia');
$sidebarActiveInventario = $sidebarPathStarts('inventario');
$sidebarActiveCompras = $sidebarPathStarts('compras');
$sidebarActiveProveedores = $sidebarPathStarts('proveedores');
$sidebarActiveDocumentos = $sidebarPathStarts('documentos');
$sidebarActiveReportes = $sidebarPathStarts('reportes');
$sidebarActivePersonal = $sidebarPathStarts('trabajadores');
$sidebarActiveNomina = $sidebarPathStarts('nomina');
$sidebarActiveUsuarios = $sidebarPathStarts('usuarios');
$sidebarActiveMotorReservas = $sidebarPathStarts('motor-reservas');
$sidebarActiveVigilanciaFinanciera = $sidebarPathStarts('ia/vigilancia-financiera');
$sidebarActiveIaEjecutiva = $sidebarPathStarts('ia') && !$sidebarActiveVigilanciaFinanciera;
$sidebarActiveCopilotoPanel = $sidebarPathStarts('copiloto');
$sidebarActiveWhatsApp = $sidebarPathStarts('whatsapp');
$sidebarActiveMensajes = $sidebarPathStarts('mensajes');
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
// Novedades por seccion (burbujas del sidebar). Fuente unica y defensiva.
$sidebarNovedades = [];
if (!$sidebarEsPanelSaas) {
    require_once APP_PATH . '/helpers/sidebar_novedades.php';
    $sidebarNovedades = sidebar_novedades();
}

// Campana de notificaciones del sidebar. Misma puerta por modulo que la del
// dashboard y la del header movil; los datos salen de notificaciones_quick()
// (cacheado ~60s en sesion) porque aqui no hay controlador que los inyecte.
$sidebarActiveNotificaciones = $sidebarPathStarts('notificaciones');
$sidebarNotifActivo = !$sidebarEsPanelSaas && $menuModuloActivo('notificaciones');
$sidebarNotifPendientes = 0;
$sidebarNotifRecientes = [];
if ($sidebarNotifActivo) {
    require_once APP_PATH . '/helpers/notificaciones_quick.php';
    $sidebarNotifQuick = notificaciones_quick(8);
    $sidebarNotifPendientes = (int) ($sidebarNotifQuick['pendientes'] ?? 0);
    $sidebarNotifRecientes = is_array($sidebarNotifQuick['recientes'] ?? null)
        ? $sidebarNotifQuick['recientes']
        : [];
}

/**
 * Burbuja de una entrada del menu. Se oculta en la seccion activa (ya estas ahi).
 *   - ['dot' => true]  -> punto de novedad (algo nuevo desde tu ultima visita)
 *   - ['count' => int] -> numero de pendientes
 */
$sidebarBadge = static function (string $ruta, bool $activo = false) use ($sidebarNovedades): string {
    if ($activo) {
        return '';
    }

    $info = $sidebarNovedades[$ruta] ?? null;
    if (!$info) {
        return '';
    }

    if (!empty($info['dot'])) {
        return '<span class="nav-badge nav-badge-novedad" aria-label="Novedad" title="Hay algo nuevo aquí"></span>';
    }

    $n = (int) ($info['count'] ?? 0);
    if ($n <= 0) {
        return '';
    }

    $tono = $info['tono'] ?? 'count';
    $sufijo = $tono === 'notify' ? 'notifications' : ($tono === 'billing' ? 'billing' : 'count');

    return '<span class="nav-badge nav-badge-' . $sufijo . '">' . ($n > 99 ? '99+' : (string) $n) . '</span>';
};
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
<?php if (!$sidebarEsPanelSaas): ?>
<style id="hotel-sidebar-critical-state">
@media (max-width: 1024px) {
    #sidebar.sidebar-main.hotel-sidebar {
        position: fixed !important;
        top: 64px !important;
        left: 0 !important;
        bottom: auto !important;
        width: 100vw !important;
        max-width: 100vw !important;
        height: calc(100vh - 64px) !important;
        max-height: calc(100vh - 64px) !important;
        height: calc(100dvh - 64px) !important;
        max-height: calc(100dvh - 64px) !important;
        transform: translate3d(-102%, 0, 0) !important;
        transition: none !important;
        overflow: hidden !important;
        display: flex !important;
        flex-direction: column !important;
        background: #FAFAFC !important;
        z-index: 900 !important;
        pointer-events: none !important;
    }

    #sidebar.sidebar-main.hotel-sidebar.active,
    #sidebar.sidebar-main.hotel-sidebar.open {
        transform: translate3d(0, 0, 0) !important;
        pointer-events: auto !important;
    }

    #sidebar.hotel-sidebar .sidebar-header,
    #sidebar.hotel-sidebar .sidebar-footer {
        display: none !important;
    }

    #sidebar.hotel-sidebar .ms-mm-prof {
        display: flex !important;
    }

    #sidebar.hotel-sidebar .ms-mm-bottom {
        display: block !important;
    }
}

@media (min-width: 1025px) {
    #sidebar.hotel-sidebar .ms-mm-prof,
    #sidebar.hotel-sidebar .ms-mm-bottom {
        display: none !important;
    }
}
</style>
<?php endif; ?>
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
            <a href="<?= url(function_exists('home_route_for_current_user') ? home_route_for_current_user() : 'dashboard') ?>" class="hotel-boutique-home" aria-label="Ir al inicio">
                <div class="hotel-boutique-mark">
                    <img src="<?= htmlspecialchars($sidebarLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="hotel-boutique-name"><?= htmlspecialchars($sidebarNombreVisual, ENT_QUOTES, 'UTF-8') ?></div>
            </a>
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
                   placeholder="Buscar"
                   class="search-input"
                   autocomplete="off"
                   style="padding-right:28px;position:relative;z-index:2;pointer-events:auto;cursor:text;user-select:text;">
            <button id="buscador-global-clear"
                    title="Limpiar búsqueda"
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
        <?php if ($mostrarDashboard || $mostrarOperacionDiaria || $mostrarForecast): ?>
        <div class="nav-section hotel-nav-section hotel-dashboard-section">
            <div class="nav-section-title ms-mm-only">
                <span>INICIO</span>
            </div>
            <?php if ($mostrarDashboard): ?>
            <a href="<?= url('dashboard') ?>"
               class="nav-item <?= $sidebarActiveDashboard ? 'active' : '' ?>"
               title="Resumen general del hotel">
                <div class="nav-icon">
                    <i class="fas fa-compass"></i>
                </div>
                <span class="nav-text">Inicio</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarOperacionDiaria): ?>
            <a href="<?= url('operacion/diaria') ?>"
               class="nav-item <?= $sidebarActiveOperacionDiaria ? 'active' : '' ?>"
               title="Todo lo del día en una pantalla: cobros, llegadas, salidas y tareas">
                <div class="nav-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <span class="nav-text">El hotel hoy</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarForecast): ?>
            <a href="<?= url('forecast') ?>"
               class="nav-item <?= $sidebarActiveForecast ? 'active' : '' ?>"
               title="Qué tan lleno estará el hotel los próximos 30, 60 y 90 días">
                <div class="nav-icon">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
                <span class="nav-text">Pronóstico de ocupación</span>
            </a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

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
               class="nav-item <?= $sidebarActiveHabitaciones ? 'active' : '' ?>"
               title="Estado de los cuartos y espacios del hotel">
                <div class="nav-icon">
                    <i class="fas fa-bed"></i>
                    <?= $sidebarBadge('habitaciones', $sidebarActiveHabitaciones) ?>
                </div>
                <span class="nav-text">Habitaciones y áreas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarReservaciones): ?>
            <a href="<?= url('reservaciones') ?>"
               class="nav-item <?= $sidebarActiveReservaciones ? 'active' : '' ?>"
               title="Crea y administra las reservas, entradas y salidas">
                <div class="nav-icon">
                    <i class="fas fa-calendar-check"></i>
                    <?= $sidebarBadge('reservaciones', $sidebarActiveReservaciones) ?>
                </div>
                <span class="nav-text">Reservaciones</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarHuespedes): ?>
            <a href="<?= url('huespedes') ?>"
               class="nav-item <?= $sidebarActiveHuespedes ? 'active' : '' ?>"
               title="Directorio de tus huéspedes y su historial">
                <div class="nav-icon">
                    <i class="fas fa-users"></i>
                    <?= $sidebarBadge('huespedes', $sidebarActiveHuespedes) ?>
                </div>
                <span class="nav-text">Huéspedes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCheckinDigital): ?>
            <a href="<?= url('checkin-digital') ?>"
               class="nav-item <?= $sidebarActiveCheckinDigital ? 'active' : '' ?>"
               title="El huésped se registra desde su celular antes de llegar">
                <div class="nav-icon">
                    <i class="fas fa-qrcode"></i>
                    <?= $sidebarBadge('checkin-digital', $sidebarActiveCheckinDigital) ?>
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
               class="nav-item <?= $sidebarActiveCaja ? 'active' : '' ?>"
               title="Cobros, gastos y cortes del turno">
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
               class="nav-item <?= $sidebarActiveCuentasPorCobrar ? 'active' : '' ?>"
               title="Lo que te deben huéspedes y empresas">
                <div class="nav-icon">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <?= $sidebarBadge('cuentas-por-cobrar', $sidebarActiveCuentasPorCobrar) ?>
                </div>
                <span class="nav-text">Cuentas por cobrar</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarFacturacion): ?>
            <a href="<?= url('facturacion') ?>"
               class="nav-item <?= $sidebarActiveFacturacion ? 'active' : '' ?>"
               title="Facturas fiscales de tus ventas">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice"></i>
                    <?= $sidebarBadge('facturacion', $sidebarActiveFacturacion) ?>
                </div>
                <span class="nav-text">Facturación</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCuentasPorPagar): ?>
            <a href="<?= url('cuentas-por-pagar') ?>"
               class="nav-item <?= $sidebarActiveCuentasPorPagar ? 'active' : '' ?>"
               title="Lo que le debes a tus proveedores">
                <div class="nav-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <?= $sidebarBadge('cuentas-por-pagar', $sidebarActiveCuentasPorPagar) ?>
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
               class="nav-item <?= $sidebarActiveTareas ? 'active' : '' ?>"
               title="Pendientes del equipo del hotel">
                <div class="nav-icon">
                    <i class="fas fa-tasks"></i>
                    <?= $sidebarBadge('tareas', $sidebarActiveTareas) ?>
                </div>
                <span class="nav-text">Tareas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCamarista): ?>
            <a href="<?= url('camarista') ?>"
               class="nav-item <?= $sidebarActiveCamarista ? 'active' : '' ?>"
               title="Trabajo de camaristas: qué cuarto limpiar y en qué estado va">
                <div class="nav-icon">
                    <i class="fas fa-broom"></i>
                    <?= $sidebarBadge('camarista', $sidebarActiveCamarista) ?>
                </div>
                <span class="nav-text">Limpieza</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarMantenimientoPlus): ?>
            <a href="<?= url('mantenimientos/activos') ?>"
               class="nav-item <?= $sidebarActiveMantenimientos ? 'active' : '' ?>"
               title="Reparaciones y servicio preventivo de equipos">
                <div class="nav-icon">
                    <i class="fas fa-toolbox"></i>
                    <?= $sidebarBadge('mantenimientos/activos', $sidebarActiveMantenimientos) ?>
                </div>
                <span class="nav-text">Mantenimiento</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarLavanderia): ?>
            <a href="<?= url('lavanderia') ?>"
               class="nav-item <?= $sidebarActiveLavanderia ? 'active' : '' ?>"
               title="Control de blancos: sábanas, toallas y ropa">
                <div class="nav-icon">
                    <i class="fas fa-shirt"></i>
                    <?= $sidebarBadge('lavanderia', $sidebarActiveLavanderia) ?>
                </div>
                <span class="nav-text">Lavandería</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarInventario): ?>
            <a href="<?= url('inventario') ?>"
               class="nav-item <?= $sidebarActiveInventario ? 'active' : '' ?>"
               title="Existencias de productos e insumos">
                <div class="nav-icon">
                    <i class="fas fa-boxes-stacked"></i>
                </div>
                <span class="nav-text">Inventarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCompras): ?>
            <a href="<?= url('compras') ?>"
               class="nav-item <?= $sidebarActiveCompras ? 'active' : '' ?>"
               title="Órdenes de compra y recepción de mercancía">
                <div class="nav-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <span class="nav-text">Compras</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarProveedores): ?>
            <a href="<?= url('proveedores') ?>"
               class="nav-item <?= $sidebarActiveProveedores ? 'active' : '' ?>"
               title="Directorio de tus proveedores">
                <div class="nav-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="nav-text">Proveedores</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarDocumentos): ?>
            <a href="<?= url('documentos') ?>"
               class="nav-item <?= $sidebarActiveDocumentos ? 'active' : '' ?>"
               title="Archivos y expedientes del hotel">
                <div class="nav-icon">
                    <i class="fas fa-folder-open"></i>
                </div>
                <span class="nav-text">Documentos</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarNightAudit): ?>
            <a href="<?= url('night-audit') ?>"
               class="nav-item <?= $sidebarActiveNightAudit ? 'active' : '' ?>"
               title="Revisión nocturna: no-shows, salidas vencidas y cortes abiertos">
                <div class="nav-icon">
                    <i class="fas fa-moon"></i>
                </div>
                <span class="nav-text">Cierre del día</span>
            </a>
            <?php endif; ?>

        </div>
        <?php endif; ?>

        <?php if ($mostrarVentasCanales): ?>
        <div class="nav-section hotel-nav-section hotel-ventas-section">
            <div class="nav-section-title">
                <span>VENTAS Y COMUNICACIÓN</span>
            </div>

            <?php if ($mostrarMotorReservas): ?>
            <a href="<?= url('motor-reservas') ?>"
               class="nav-item <?= $sidebarActiveMotorReservas ? 'active' : '' ?>"
               title="Tu página pública para que el huésped reserve y pague anticipo">
                <div class="nav-icon">
                    <i class="fas fa-globe"></i>
                    <?= $sidebarBadge('motor-reservas', $sidebarActiveMotorReservas) ?>
                </div>
                <span class="nav-text">Reservas en línea</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCanales): ?>
            <a href="<?= url('canales') ?>"
               class="nav-item <?= $sidebarActiveCanales ? 'active' : '' ?>"
               title="Sincroniza calendarios para no vender dos veces el mismo cuarto">
                <div class="nav-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <span class="nav-text">Airbnb y Booking</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarMensajes): ?>
            <a href="<?= url('mensajes') ?>"
               class="nav-item <?= $sidebarActiveMensajes ? 'active' : '' ?>"
               title="Confirmaciones, recordatorios y encuestas listos para enviar">
                <div class="nav-icon">
                    <i class="fas fa-comment-dots"></i>
                    <?= $sidebarBadge('mensajes', $sidebarActiveMensajes) ?>
                </div>
                <span class="nav-text">Mensajes a huéspedes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarWhatsApp): ?>
            <a href="<?= url('whatsapp') ?>"
               class="nav-item <?= $sidebarActiveWhatsApp ? 'active' : '' ?>"
               title="Conecta el número del hotel para avisos automáticos">
                <div class="nav-icon">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <span class="nav-text">Conectar WhatsApp</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarIaEjecutiva): ?>
            <a href="<?= url('ia/resumen-diario') ?>"
               class="nav-item <?= $sidebarActiveIaEjecutiva ? 'active' : '' ?>"
               title="Resumen del día narrado con inteligencia artificial">
                <div class="nav-icon">
                    <i class="fas fa-wand-magic-sparkles"></i>
                </div>
                <span class="nav-text">Asesor inteligente</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarCopilotoPanel): ?>
            <a href="<?= url('copiloto/valor') ?>"
               class="nav-item <?= $sidebarActiveCopilotoPanel ? 'active' : '' ?>"
               title="Cuánto y cómo se usa el asistente del hotel">
                <div class="nav-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <span class="nav-text">Uso del asistente</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarVigilanciaFinanciera): ?>
            <a href="<?= url('ia/vigilancia-financiera') ?>"
               class="nav-item <?= $sidebarActiveVigilanciaFinanciera ? 'active' : '' ?>"
               title="Avisa cuando algo en los números se sale del patrón del hotel">
                <div class="nav-icon">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <span class="nav-text">Guardián financiero</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarReputacion): ?>
            <a href="<?= url('reputacion') ?>"
               class="nav-item <?= $sidebarActiveReputacion ? 'active' : '' ?>"
               title="Lo que opinan tus huéspedes: reseñas y encuestas">
                <div class="nav-icon">
                    <i class="fas fa-star"></i>
                    <?= $sidebarBadge('reputacion', $sidebarActiveReputacion) ?>
                </div>
                <span class="nav-text">Opiniones y encuestas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarLealtad): ?>
            <a href="<?= url('lealtad') ?>"
               class="nav-item <?= $sidebarActiveLealtad ? 'active' : '' ?>"
               title="Premia a los huéspedes que regresan">
                <div class="nav-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <span class="nav-text">Huésped frecuente</span>
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
               class="nav-item <?= $sidebarActiveReportes ? 'active' : '' ?>"
               title="Estadísticas y gráficas del hotel">
                <div class="nav-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <span class="nav-text">Reportes</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarPersonal): ?>
            <a href="<?= url('trabajadores') ?>"
               class="nav-item <?= $sidebarActivePersonal ? 'active' : '' ?>"
               title="Expedientes de tus empleados">
                <div class="nav-icon">
                    <i class="fas fa-id-card"></i>
                </div>
                <span class="nav-text">Personal</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarNomina): ?>
            <a href="<?= url('nomina') ?>"
               class="nav-item <?= $sidebarActiveNomina ? 'active' : '' ?>"
               title="Sueldos, incidencias y periodos de pago">
                <div class="nav-icon">
                    <i class="fas fa-money-check-dollar"></i>
                </div>
                <span class="nav-text">Nómina</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarUsuariosAdmin): ?>
            <a href="<?= url('usuarios') ?>"
               class="nav-item <?= $sidebarActiveUsuarios ? 'active' : '' ?>"
               title="Cuentas de acceso al sistema (no confundir con Personal)">
                <div class="nav-icon">
                    <i class="fas fa-user"></i>
                </div>
                <span class="nav-text">Usuarios</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarAuditoria): ?>
            <a href="<?= url('auditoria') ?>"
               class="nav-item <?= $sidebarActiveAuditoria ? 'active' : '' ?>"
               title="Quién hizo qué y cuándo dentro del sistema">
                <div class="nav-icon">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <span class="nav-text">Historial de actividad</span>
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
               class="nav-item <?= $sidebarActiveConfiguracion ? 'active' : '' ?>"
               title="Datos y ajustes generales del hotel">
                <div class="nav-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <span class="nav-text">Configuración</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarTarifas): ?>
            <a href="<?= url('configuracion/tarifas') ?>"
               class="nav-item <?= $sidebarActiveTarifas ? 'active' : '' ?>"
               title="Tarifas por temporada e incrementos de precio">
                <div class="nav-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <span class="nav-text">Precios y temporadas</span>
            </a>
            <?php endif; ?>

            <?php if ($mostrarRoles): ?>
            <a href="<?= url('configuracion/roles') ?>"
               class="nav-item <?= $sidebarActiveRoles ? 'active' : '' ?>"
               title="Qué puede ver y hacer cada puesto en el sistema">
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
                <span>Borrar copia local de este equipo</span>
            </button>
            <?php endif; ?>
            <form method="POST" action="<?= url('logout') ?>" class="ms-mm-logout-form">
                <?= csrf_field() ?>
                <button type="submit" class="ms-mm-logout">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4M21 12H10M17 8l4 4-4 4"/></svg>
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
            <!-- Operaciones offline pendientes/rechazadas (JS lo muestra solo si hay) -->
            <a href="<?= url('offline/pendientes') ?>" id="offline-ops-link" class="hidden"
               style="margin-left:auto;display:flex;align-items:center;gap:5px;text-decoration:none;"
               title="Cambios sin enviar">
                <i class="fas fa-cloud-arrow-up" style="font-size:.8rem;color:#D97706;"></i>
                <span id="offline-ops-badge" class="hidden"
                      style="background:#D97706;color:white;border-radius:10px;padding:0 7px;font-size:.7rem;font-weight:700;line-height:1.5;">0</span>
            </a>
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
            <?php if ($sidebarNotifActivo): ?>
            <!-- ── Campana de notificaciones: mismo panel rápido del dashboard,
                 aquí disponible desde cualquier pantalla. Vive junto al menú
                 de usuario; el panel se abre hacia arriba desde el pie. ── -->
            <div class="sb-bell-shell" data-sb-notif>
                <button type="button"
                        class="user-menu-btn sb-bell<?= $sidebarNotifPendientes > 0 ? ' has-pendientes' : '' ?><?= $sidebarActiveNotificaciones ? ' is-active' : '' ?>"
                        data-sb-notif-toggle
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-controls="sbNotifPanel"
                        title="Notificaciones"
                        aria-label="<?= $sidebarNotifPendientes > 0 ? 'Notificaciones: ' . $sidebarNotifPendientes . ' sin leer' : 'Notificaciones' ?>">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <?php if ($sidebarNotifPendientes > 0): ?>
                    <span class="sb-bell-badge"><?= $sidebarNotifPendientes > 99 ? '99+' : (int) $sidebarNotifPendientes ?></span>
                    <?php endif; ?>
                </button>

                <div class="sb-notif-panel" id="sbNotifPanel" data-sb-notif-panel hidden>
                    <div class="sb-notif-head">
                        <span>Pendientes</span>
                        <strong data-sb-notif-count><?= (int) $sidebarNotifPendientes ?></strong>
                    </div>

                    <div class="sb-notif-list" data-sb-notif-list>
                        <?php if (empty($sidebarNotifRecientes)): ?>
                            <div class="sb-notif-empty">Sin pendientes operativos por ahora.</div>
                        <?php else: ?>
                            <?php foreach (array_slice($sidebarNotifRecientes, 0, 8) as $sbNotif): ?>
                                <?php
                                $sbModulo = (string) ($sbNotif['modulo'] ?? 'sistema');
                                $sbId = (int) ($sbNotif['id'] ?? 0);
                                $sbTieneUrl = trim((string) ($sbNotif['url'] ?? '')) !== '';
                                $sbHref = ($sbTieneUrl && $sbId > 0)
                                    ? url('notificaciones/' . $sbId . '/abrir')
                                    : url('notificaciones');
                                $sbAutomatica = strpos((string) ($sbNotif['tipo'] ?? ''), 'regla_') === 0;
                                $sbSeveridad = strtolower((string) ($sbNotif['severidad'] ?? ''));
                                $sbSevClass = in_array($sbSeveridad, ['critica', 'alta'], true)
                                    ? ' sb-crit'
                                    : ($sbSeveridad === 'media' ? ' sb-warn' : '');
                                $sbFecha = notificaciones_quick_fecha($sbNotif['created_at'] ?? null);
                                ?>
                                <div class="sb-swipe" data-sb-swipe>
                                    <div class="sb-swipe-bg" aria-hidden="true">
                                        <i class="fas fa-check" aria-hidden="true"></i> Archivar
                                    </div>
                                    <a class="sb-notif-row<?= $sbSevClass ?>"
                                       href="<?= htmlspecialchars($sbHref, ENT_QUOTES, 'UTF-8') ?>"
                                       <?= $sbId > 0 ? 'data-archive-url="' . htmlspecialchars(url('notificaciones/' . $sbId . '/descartar'), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                        <span class="sb-notif-icon">
                                            <i class="fas <?= htmlspecialchars(notificaciones_quick_icono($sbModulo), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                        </span>
                                        <span class="sb-notif-body">
                                            <span class="sb-notif-title"><?= htmlspecialchars((string) ($sbNotif['titulo'] ?? 'Notificación'), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="sb-notif-meta">
                                                <?= htmlspecialchars(notificaciones_quick_etiqueta($sbModulo), ENT_QUOTES, 'UTF-8') ?><?= $sbFecha !== '' ? ' · ' . htmlspecialchars($sbFecha, ENT_QUOTES, 'UTF-8') : '' ?> · <?= $sbAutomatica ? 'Automática' : 'Manual' ?>
                                            </span>
                                        </span>
                                        <span class="sb-notif-go">
                                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                        </span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($sidebarNotifRecientes)): ?>
                    <div class="sb-notif-hint" data-sb-notif-hint>
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                        Desliza una notificación a la izquierda para archivar
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
                    title="Borra solo la copia guardada en este navegador, con confirmación previa. No borra nada del hotel.">
                <i class="fas fa-broom"></i>
                <span>Borrar copia local de este equipo</span>
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

/* Burbuja de conteo (accion): numero de pendientes por seccion. */
.hotel-layout-scope .hotel-sidebar .nav-badge.nav-badge-count,
.nav-badge.nav-badge-count {
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
    box-shadow: 0 0 0 2px var(--hotel-panel, #FFFFFF), 0 6px 14px rgba(15, 23, 42, 0.18) !important;
    animation: none !important;
}

/* Punto de novedad (informativo): "hay algo nuevo aqui" desde tu ultima visita. */
.hotel-layout-scope .hotel-sidebar .nav-badge.nav-badge-novedad,
.nav-badge.nav-badge-novedad {
    top: 1px !important;
    right: 1px !important;
    min-width: 9px !important;
    width: 9px !important;
    height: 9px !important;
    padding: 0 !important;
    border-radius: 999px !important;
    background: var(--brand-accent, #B45309) !important;
    box-shadow: 0 0 0 2px var(--hotel-panel, #FFFFFF) !important;
    animation: sidebarNovedadPulse 2.2s ease-in-out infinite;
}

@keyframes sidebarNovedadPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50%      { transform: scale(1.22); opacity: .72; }
}

@media (prefers-reduced-motion: reduce) {
    .nav-badge.nav-badge-novedad { animation: none !important; }
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

<?php if (!$sidebarEsPanelSaas): ?>
<!-- Sidebar PC de dos niveles (rail + flyout). Revertir: quitar estas 2 líneas y borrar ambos assets. -->
<link rel="stylesheet" href="<?= function_exists('asset_version') ? asset_version('css/sidebar-rail.css') : asset('css/sidebar-rail.css') ?>">
<script src="<?= function_exists('asset_version') ? asset_version('js/sidebar-rail.js') : asset('js/sidebar-rail.js') ?>"></script>
<?php endif; ?>

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

        initSidebarScrollMemory(sidebar);
    }

    function initSidebarScrollMemory(sidebar) {
        if (!sidebar || sidebar.dataset.scrollMemoryReady === 'true') {
            return;
        }

        const nav = sidebar.querySelector('.sidebar-nav');
        if (!nav) {
            return;
        }

        sidebar.dataset.scrollMemoryReady = 'true';

        const storageKey = getSidebarScrollStorageKey();
        const maxAge = 8 * 60 * 60 * 1000;
        let lastClickedHref = '';
        let saveTimer = 0;

        const normalizeHref = function(href) {
            if (!href || href === '#') {
                return '';
            }

            try {
                const url = new URL(href, window.location.href);
                if (url.origin !== window.location.origin) {
                    return '';
                }
                return url.pathname.replace(/\/+$/, '') + url.search;
            } catch (error) {
                return '';
            }
        };

        const activeLinkHref = function() {
            const active = nav.querySelector('a.nav-item.active[href]');
            return active ? normalizeHref(active.getAttribute('href')) : '';
        };

        const currentPath = function() {
            return window.location.pathname.replace(/\/+$/, '') + window.location.search;
        };

        const save = function(href) {
            try {
                window.sessionStorage.setItem(storageKey, JSON.stringify({
                    top: nav.scrollTop || 0,
                    href: href || lastClickedHref || activeLinkHref(),
                    path: currentPath(),
                    savedAt: Date.now()
                }));
            } catch (error) {}
        };

        const scheduleSave = function() {
            if (saveTimer) {
                return;
            }

            saveTimer = window.setTimeout(function() {
                saveTimer = 0;
                save();
            }, 120);
        };

        const readRecord = function() {
            try {
                const raw = window.sessionStorage.getItem(storageKey);
                if (!raw) {
                    return null;
                }

                const record = JSON.parse(raw);
                const top = Number(record && record.top);
                const savedAt = Number(record && record.savedAt);
                if (!Number.isFinite(top) || (savedAt && Date.now() - savedAt > maxAge)) {
                    return null;
                }

                return {
                    top: Math.max(0, top),
                    href: String(record.href || ''),
                    path: String(record.path || '')
                };
            } catch (error) {
                return null;
            }
        };

        const activeIsVisible = function() {
            const active = nav.querySelector('a.nav-item.active[href]');
            if (!active) {
                return true;
            }

            const navRect = nav.getBoundingClientRect();
            const activeRect = active.getBoundingClientRect();
            return activeRect.top >= navRect.top + 8 && activeRect.bottom <= navRect.bottom - 8;
        };

        const centerActive = function() {
            const active = nav.querySelector('a.nav-item.active[href]');
            if (!active) {
                return;
            }

            const targetTop = active.offsetTop - Math.max(0, (nav.clientHeight - active.offsetHeight) / 2);
            const maxTop = Math.max(0, nav.scrollHeight - nav.clientHeight);
            nav.scrollTop = Math.max(0, Math.min(targetTop, maxTop));
        };

        const restore = function(attempt) {
            const record = readRecord();
            if (!record) {
                centerActive();
                return;
            }

            const maxTop = Math.max(0, nav.scrollHeight - nav.clientHeight);
            const activeHref = activeLinkHref();
            const shouldTrustRecord = !record.href || !activeHref || record.href === activeHref || record.path === currentPath();

            if (shouldTrustRecord) {
                nav.scrollTop = Math.max(0, Math.min(record.top, maxTop));
            } else if (!activeIsVisible()) {
                centerActive();
            }

            if (attempt < 3 && maxTop <= 0 && record.top > 0) {
                window.setTimeout(function() {
                    restore(attempt + 1);
                }, attempt === 0 ? 80 : 180);
            }
        };

        window.requestAnimationFrame(function() {
            restore(0);
        });

        nav.addEventListener('scroll', scheduleSave, { passive: true });

        nav.addEventListener('click', function(event) {
            const link = event.target instanceof Element ? event.target.closest('a.nav-item[href]') : null;
            if (!link || event.defaultPrevented) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                return;
            }

            const href = normalizeHref(link.getAttribute('href'));
            if (!href) {
                return;
            }

            lastClickedHref = href;
            save(href);
        }, true);

        window.addEventListener('pagehide', function() {
            save();
        }, { passive: true });

        window.addEventListener('beforeunload', function() {
            save();
        });
    }

    function getSidebarScrollStorageKey() {
        const hotelId = window.MEDISOFT_CONTEXT && window.MEDISOFT_CONTEXT.hotel_id
            ? String(window.MEDISOFT_CONTEXT.hotel_id)
            : 'global';
        const sidebar = document.getElementById('sidebar');
        const scope = sidebar && sidebar.classList.contains('sidebar-saas') ? 'saas' : 'hotel';
        return 'medisoft:sidebar-scroll:v2:' + scope + ':' + hotelId;
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
        background: #FAFAFC !important;
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
        font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
        background: var(--ms-mm-ib, #FAFAFC) !important;
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

<?php if ($sidebarNotifActivo): ?>
<style>
/* ═══════════════════════════════════════════════════════════════
   CAMPANA DE NOTIFICACIONES DEL SIDEBAR
   Vive en el pie, dentro de la tarjeta del usuario (junto al menú
   de ⋮): las alertas acompañan a la persona, no a la búsqueda.
   Colores con tokens --hotel-* para que el tema oscuro entre solo.
   El panel se muda a <body> al abrir (el aside es overflow:hidden)
   y crece hacia arriba porque el ancla está al fondo del viewport.
   ═══════════════════════════════════════════════════════════════ */
/* Ancla del numerito; el panel se va a <body> en cuanto carga el JS. */
.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell-shell {
    position: relative;
    flex: 0 0 auto;
    display: block;
    margin: 0;
    padding: 0;
    overflow: visible;
}

/* El timbre LLEVA la clase .user-menu-btn a propósito: así hereda el fondo,
   el radio y el hover que cada piel le pinta al botón de ⋮ de al lado
   (shell, rail, cupertino, oscuro) sin duplicar una sola regla de color.
   Aquí solo se corrige la geometría: cuadrado parejo y campana centrada. */
.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    padding: 0;
    font-size: 14px;
    line-height: 1;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transition: background-color .18s ease, color .18s ease, transform .18s ease;
}

.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell:active { transform: scale(.92); }

.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--sr-primary, #1B2746) 55%, transparent);
    outline-offset: 2px;
}

/* Abierto: se queda "presionado" con la misma gramática del hover del ⋮. */
.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell-shell.is-open .sb-bell {
    background: color-mix(in srgb, var(--sr-primary, #1B2746) 10%, transparent) !important;
    color: var(--sr-primary, #1B2746) !important;
}

/* Repique discreto al cargar cuando hay algo por atender. */
.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell.has-pendientes i {
    transform-origin: 50% 12%;
    animation: sbBellRing 4.5s ease-in-out .8s 2;
}

@keyframes sbBellRing {
    0%, 8%, 100% { transform: rotate(0); }
    2%  { transform: rotate(11deg); }
    4%  { transform: rotate(-9deg); }
    6%  { transform: rotate(5deg); }
}

/* Mismo rojo semantico de las burbujas del menu y del header movil.
   Sin anillo de color: el fondo de la sidebar es un degradado y ningun anillo
   solido casa con el; una sombra suave la despega sin ensuciar. */
.hotel-layout-scope #sidebar.hotel-sidebar .sb-bell-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 16px;
    height: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border-radius: 999px;
    background: #dc2626;
    color: #FFFEFB;
    font-size: .6rem;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -.01em;
    box-shadow: 0 1px 3px rgba(0, 0, 0, .28);
    pointer-events: none;
}

/* ── Panel ──────────────────────────────────────────────────── */
.sb-notif-panel {
    position: fixed;
    z-index: 1250;
    width: min(360px, calc(100vw - 24px));
    border: 1px solid var(--hotel-border, #E7DEC9);
    border-radius: 18px;
    /* Mismo destello dorado del flyout del rail: el panel se lee como
       hermano suyo, no como una caja ajena pegada al menú. */
    background:
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--hotel-accent, #BD9441) 12%, transparent), transparent 11rem),
        var(--hotel-panel, #fff);
    color: var(--hotel-text, #0F172A);
    box-shadow: 0 22px 52px color-mix(in srgb, var(--hotel-secondary, #0F172A) 18%, transparent);
    overflow: hidden;
    font-family: Manrope, Inter, ui-sans-serif, system-ui, sans-serif;
    animation: sbNotifIn .22s cubic-bezier(.22, 1, .36, 1);
}

@keyframes sbNotifIn {
    from { opacity: 0; transform: translateX(-8px) scale(.98); }
    to   { opacity: 1; transform: none; }
}

.sb-notif-panel[hidden] { display: none; }

.sb-notif-panel.sb-closing {
    animation: sbNotifOut .16s cubic-bezier(.4, 0, 1, 1) forwards;
    pointer-events: none;
}

@keyframes sbNotifOut {
    from { opacity: 1; transform: none; }
    to   { opacity: 0; transform: translateX(-6px) scale(.98); }
}

.sb-notif-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 15px 19px 12px;
}

.sb-notif-head span {
    color: var(--hotel-muted, #939BAD);
    font-size: 11.5px;
    font-weight: 800;
    letter-spacing: .14em;
    text-transform: uppercase;
}

.sb-notif-head strong {
    color: var(--hotel-text, #1B2746);
    font-size: 21px;
    font-weight: 700;
    line-height: 1;
}

.sb-notif-list {
    max-height: min(326px, 52vh);
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
}

.sb-notif-list::-webkit-scrollbar { width: 6px; }
.sb-notif-list::-webkit-scrollbar-thumb { background: var(--hotel-line, #E2D6BF); border-radius: 99px; }

/* Fila deslizable: capa verde "Archivar" debajo de la fila. */
.sb-swipe {
    position: relative;
    border-top: 1px solid var(--hotel-border, #ECE5D8);
    overflow: hidden;
}

.sb-swipe.gone {
    transition: height .32s ease, opacity .2s ease;
    opacity: 0;
    border-top: 0;
}

.sb-swipe-bg {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding-right: 22px;
    background: linear-gradient(90deg, #2BA76A, #1E9E63);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
}

.sb-notif-row {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 19px;
    background: var(--hotel-panel, #fff);
    color: inherit;
    text-decoration: none;
    transition: background .14s ease;
    touch-action: pan-y;
    user-select: none;
    -webkit-user-drag: none;
}

.sb-notif-row.dragging { transition: none; }
.sb-notif-row.settle { transition: transform .3s cubic-bezier(.22, 1, .36, 1); }

.sb-notif-row:hover,
.sb-notif-row:focus-visible {
    background: color-mix(in srgb, var(--hotel-accent, #BD9441) 7%, var(--hotel-panel, #fff));
    outline: none;
}

.sb-notif-icon {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    flex: none;
    border-radius: 11px;
    background: color-mix(in srgb, var(--hotel-primary, #1B2746) 7%, transparent);
    color: var(--hotel-muted, #6C7689);
    font-size: 14px;
    pointer-events: none;
}

/* Severidad semantica: critica/alta rojo, media ambar (no cambia con la marca). */
.sb-crit .sb-notif-icon { background: #FBE9E7; color: #D64539; }
.sb-warn .sb-notif-icon { background: #FAF0DC; color: #C2841C; }

.sb-notif-body { min-width: 0; flex: 1; pointer-events: none; }

.sb-notif-title {
    display: block;
    color: var(--hotel-text, #1B2746);
    font-size: 13.5px;
    font-weight: 700;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sb-notif-meta {
    display: block;
    margin-top: 3px;
    color: var(--hotel-muted, #939BAD);
    font-size: 11.5px;
    font-weight: 600;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sb-notif-go {
    margin-left: auto;
    flex: none;
    color: var(--hotel-muted, #B7BDCB);
    font-size: 12px;
    pointer-events: none;
}

.sb-notif-empty {
    padding: 24px 19px;
    color: var(--hotel-muted, #939BAD);
    font-size: 13px;
    font-weight: 600;
    text-align: center;
}

.sb-notif-hint {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 8px;
    border-top: 1px solid var(--hotel-border, #ECE5D8);
    background: color-mix(in srgb, var(--hotel-accent, #BD9441) 5%, var(--hotel-panel, #fff));
    color: var(--hotel-muted, #939BAD);
    font-size: 11px;
    font-weight: 600;
}

/* En movil el menu es pantalla completa y el header ya trae su campana:
   una sola por vista, sin duplicar el mismo control. */
@media (max-width: 1024px) {
    #sidebar.hotel-sidebar .sb-bell-shell { display: none !important; }
}

@media (prefers-reduced-motion: reduce) {
    .hotel-layout-scope #sidebar.hotel-sidebar .sb-bell.has-pendientes i { animation: none; }
    .sb-notif-panel, .sb-notif-panel.sb-closing { animation: none; }
}
</style>

<script>
(function () {
    var root = document.querySelector('[data-sb-notif]');
    if (!root) { return; }

    var button = root.querySelector('[data-sb-notif-toggle]');
    var panel = root.querySelector('[data-sb-notif-panel]');
    if (!button || !panel) { return; }

    // El aside es overflow:hidden; el panel vive en <body> y se posiciona fijo.
    document.body.appendChild(panel);

    // Flotante a la derecha del menu, alineado con la campana. Si no cabe
    // (ventana angosta), cae al otro lado y se ajusta al alto disponible.
    // Mismo anclaje que el flyout del rail (sidebar-rail.js): pegado al borde
    // derecho del menu, no al boton, para que no se monte sobre la sidebar.
    var aside = document.getElementById('sidebar');

    function placePanel() {
        var rect = button.getBoundingClientRect();
        var borde = aside ? aside.getBoundingClientRect().right : rect.right;
        var width = Math.min(360, Math.max(280, window.innerWidth - 24));
        var left = Math.round(borde + 8);
        if (left + width > window.innerWidth - 8) {
            left = Math.round(rect.left - width - 8);
        }
        // Tope duro: si el menu esta fuera de pantalla (drawer cerrado, resize
        // a medias), el ancla da basura y el panel no debe irse del viewport.
        left = Math.max(8, Math.min(left, window.innerWidth - width - 8));

        panel.style.width = width + 'px';
        panel.style.left = left + 'px';
        panel.style.top = '0px';

        var alto = panel.offsetHeight || 320;
        var top = Math.max(8, Math.min(rect.top - 6, window.innerHeight - alto - 8));
        panel.style.top = Math.round(top) + 'px';
    }

    var animTimer = null;

    function openPanel() {
        if (animTimer) { clearTimeout(animTimer); animTimer = null; }
        panel.classList.remove('sb-closing');
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        root.classList.add('is-open');
        placePanel();
    }

    function closePanel() {
        if (panel.hidden || panel.classList.contains('sb-closing')) { return; }
        panel.classList.add('sb-closing');
        button.setAttribute('aria-expanded', 'false');
        root.classList.remove('is-open');
        animTimer = setTimeout(function () {
            panel.hidden = true;
            panel.classList.remove('sb-closing');
            animTimer = null;
        }, 160);
    }

    button.addEventListener('click', function (event) {
        event.stopPropagation();
        if (panel.hidden || panel.classList.contains('sb-closing')) {
            openPanel();
            return;
        }
        closePanel();
    });

    // Solo abre con clic: nada de hover. Pasar el cursor por el menu no debe
    // desplegar un panel que tapa media pantalla.

    document.addEventListener('click', function (event) {
        if (!(event.target instanceof Element)) { return; }
        if (!panel.hidden && !root.contains(event.target) && !panel.contains(event.target)) {
            closePanel();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closePanel();
            button.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (!panel.hidden) { placePanel(); }
    });

    window.addEventListener('scroll', function () {
        if (!panel.hidden) { placePanel(); }
    }, true);

    // ── Deslizar para archivar (mismo POST a /notificaciones/{id}/descartar) ──
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';
    var headCount = panel.querySelector('[data-sb-notif-count]');
    var list = panel.querySelector('[data-sb-notif-list]');
    var THRESHOLD = 96;
    var pendientes = headCount ? (parseInt(headCount.textContent, 10) || 0) : 0;

    function renderCounts() {
        if (headCount) { headCount.textContent = pendientes; }
        var badge = button.querySelector('.sb-bell-badge');
        if (pendientes <= 0) {
            if (badge) { badge.remove(); }
            button.classList.remove('has-pendientes');
            button.setAttribute('aria-label', 'Notificaciones');
        } else if (badge) {
            badge.textContent = pendientes > 99 ? '99+' : String(pendientes);
            button.setAttribute('aria-label', 'Notificaciones: ' + pendientes + ' sin leer');
        }
    }

    function onArchived(sw) {
        sw.classList.add('gone');
        sw.style.height = '0px';
        pendientes = Math.max(0, pendientes - 1);
        renderCounts();
        setTimeout(function () {
            sw.remove();
            if (list && !list.querySelector('[data-sb-swipe]')) {
                list.innerHTML = '<div class="sb-notif-empty">Sin pendientes operativos por ahora.</div>';
                var hint = panel.querySelector('[data-sb-notif-hint]');
                if (hint) { hint.remove(); }
            }
            if (!panel.hidden) { placePanel(); }
        }, 340);
    }

    function archiveRow(sw, row) {
        row.classList.add('settle');
        row.style.transform = 'translateX(-110%)';
        sw.style.height = sw.offsetHeight + 'px';

        var body = new URLSearchParams();
        body.set('csrf_token', csrfToken);

        fetch(row.dataset.archiveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        }).then(function (res) {
            if (!res.ok) { throw new Error('HTTP ' + res.status); }
            onArchived(sw);
        }).catch(function () {
            sw.style.height = '';
            row.style.transform = 'translateX(0)';
            if (typeof window.msToast === 'function') {
                window.msToast('error', 'Notificaciones', 'No se pudo archivar. Intenta de nuevo.');
            }
        });
    }

    if (list) {
        list.querySelectorAll('[data-sb-swipe]').forEach(function (sw) {
            var row = sw.querySelector('.sb-notif-row');
            if (!row || !row.dataset.archiveUrl) { return; }

            var startX = 0, startY = 0, dx = 0;
            var active = false, decided = false, horiz = false;

            row.addEventListener('pointerdown', function (e) {
                if (e.pointerType === 'mouse' && e.button !== 0) { return; }
                startX = e.clientX; startY = e.clientY;
                dx = 0; active = true; decided = false; horiz = false;
                row.classList.remove('settle');
            });

            row.addEventListener('pointermove', function (e) {
                if (!active) { return; }
                var mx = e.clientX - startX;
                var my = e.clientY - startY;
                if (!decided && (Math.abs(mx) > 6 || Math.abs(my) > 6)) {
                    decided = true;
                    horiz = Math.abs(mx) > Math.abs(my);
                    if (horiz) {
                        row.classList.add('dragging');
                        try { row.setPointerCapture(e.pointerId); } catch (err) {}
                    }
                }
                if (!horiz) { return; }
                if (e.cancelable) { e.preventDefault(); }
                dx = Math.min(0, mx); // solo hacia la izquierda
                row.style.transform = 'translateX(' + dx + 'px)';
            });

            function finishDrag() {
                if (!active) { return; }
                active = false;
                row.classList.remove('dragging');
                if (horiz) {
                    // Suprime la navegacion del click que sigue al arrastre.
                    row.dataset.dragged = '1';
                    setTimeout(function () { delete row.dataset.dragged; }, 0);
                    if (dx < -THRESHOLD) {
                        archiveRow(sw, row);
                    } else {
                        row.classList.add('settle');
                        row.style.transform = 'translateX(0)';
                    }
                }
            }

            row.addEventListener('pointerup', finishDrag);
            row.addEventListener('pointercancel', finishDrag);
            row.addEventListener('click', function (e) {
                if (row.dataset.dragged === '1') { e.preventDefault(); }
            });
        });
    }
})();
</script>
<?php endif; ?>
