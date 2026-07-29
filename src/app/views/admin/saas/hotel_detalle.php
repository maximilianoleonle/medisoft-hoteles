<?php
$hotel = $hotel ?? [];
$usuariosHotel = $usuariosHotel ?? [];
$modulosHotel = $modulosHotel ?? [];
$planes = $planes ?? [];
$planActual = $planActual ?? null;
$modulosPorPlan = $modulosPorPlan ?? [];
$brandingHotel = $brandingHotel ?? [];
$auditoriaPlan = $auditoriaPlan ?? [
    'estado' => 'sin_plan',
    'mensaje' => 'No hay auditoria disponible.',
    'modulos_incluidos_apagados' => [],
    'modulos_activos_fuera_plan' => [],
];
$activo = !empty($hotel['activo']);
$hotelSlug = trim((string) ($hotel['slug'] ?? ''));
$hotelLoginUrl = $activo && preg_match('/^[a-z0-9-]+$/', $hotelSlug)
    ? url('h/' . $hotelSlug . '/login')
    : null;
$usuariosAdminCount = count($usuariosHotel);
$modulosActivosCount = count(array_filter($modulosHotel, function ($modulo) {
    return !empty($modulo['activo_hotel']);
}));
$bloquesExtraActivos = count(array_filter($modulosHotel, function ($modulo) {
    // Solo opcionales contratables cuentan como "bloques extra" (los internos
    // Medisoft no se venden ni se cobran).
    return empty($modulo['es_core'])
        && (($modulo['tipo_comercial'] ?? 'opcional') === 'opcional')
        && !empty($modulo['activo_hotel']);
}));
$estadoAuditoria = $auditoriaPlan['estado'] ?? 'sin_plan';
$estadoStyle = [
    'consistente'   => 'background:rgba(22,163,74,.12);color:var(--ms-success);',
    'diferencias'   => 'background:rgba(245,158,11,.12);color:var(--ms-warning);',
    'personalizado' => 'background:rgba(37,99,235,.12);color:var(--ms-primary);',
    'sin_plan'      => 'background:rgba(100,116,139,.10);color:var(--ms-muted);',
][$estadoAuditoria] ?? 'background:rgba(100,116,139,.10);color:var(--ms-muted);';
$estadoTexto = [
    'consistente' => 'Consistente',
    'diferencias' => 'Con diferencias',
    'personalizado' => 'Personalizado',
    'sin_plan' => 'Sin plan',
][$estadoAuditoria] ?? 'No disponible';
$modulosApagados = $auditoriaPlan['modulos_incluidos_apagados'] ?? [];
$modulosFueraPlan = $auditoriaPlan['modulos_activos_fuera_plan'] ?? [];
$planBadgeStyle = function (string $clave): string {
    $map = [
        'basico'        => 'background:rgba(100,116,139,.12);color:var(--ms-muted);',
        'pro'           => 'background:rgba(37,99,235,.12);color:var(--ms-primary);',
        'premium'       => 'background:rgba(6,182,212,.15);color:var(--ms-accent);',
        'personalizado' => 'border:1px solid var(--ms-border);color:var(--ms-muted);',
    ];
    return $map[$clave] ?? 'background:rgba(100,116,139,.10);color:var(--ms-muted);';
};
$brandingOld = $_SESSION['old_input'] ?? [];
$brandingCampo = function ($key, $default = '') use ($brandingOld, $brandingHotel) {
    $value = array_key_exists($key, $brandingOld)
        ? $brandingOld[$key]
        : ($brandingHotel[$key] ?? $default);

    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
};
$brandingActivo = array_key_exists('activo', $brandingOld)
    ? !empty($brandingOld['activo'])
    : (!isset($brandingHotel['activo']) || !empty($brandingHotel['activo']));
$brandingNombrePreview = $brandingHotel['nombre_visual'] ?? $hotel['nombre'] ?? 'Medisoft Hoteles';
$brandingLogoPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['logo_url'] ?? null)
    : null;
$brandingFaviconPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['favicon_url'] ?? null)
    : null;
$brandingLoginBgPreview = function_exists('hotel_branding_asset_url')
    ? hotel_branding_asset_url($brandingHotel['login_background_url'] ?? null)
    : null;
$brandingPwaIcon192Preview = function_exists('hotel_branding_pwa_icon_asset_url')
    ? hotel_branding_pwa_icon_asset_url($brandingHotel['pwa_icon_192_url'] ?? null, 192)
    : null;
$brandingPwaIcon512Preview = function_exists('hotel_branding_pwa_icon_asset_url')
    ? hotel_branding_pwa_icon_asset_url($brandingHotel['pwa_icon_512_url'] ?? null, 512)
    : null;
$copyVisible = function ($value) {
    return strtr((string) ($value ?? ''), [
        'Administracion' => 'Administración',
        'administracion' => 'administración',
        'Auditoria' => 'Auditoría',
        'auditoria' => 'auditoría',
        'Basico' => 'Básico',
        'basico' => 'básico',
        'Catalogo' => 'Catálogo',
        'catalogo' => 'catálogo',
        'Codigo' => 'Código',
        'codigo' => 'código',
        'Configuracion' => 'Configuración',
        'configuracion' => 'configuración',
        'Direccion' => 'Dirección',
        'direccion' => 'dirección',
        'Facturacion' => 'Facturación',
        'facturacion' => 'facturación',
        'Icono' => 'Ícono',
        'icono' => 'ícono',
        'Maximo' => 'Máximo',
        'maximo' => 'máximo',
        'Migracion' => 'Migración',
        'migracion' => 'migración',
        'Modulo' => 'Módulo',
        'modulo' => 'módulo',
        'Modulos' => 'Módulos',
        'modulos' => 'módulos',
        'Operacion' => 'Operación',
        'operacion' => 'operación',
        'Razon social' => 'Razón social',
        'Telefono' => 'Teléfono',
        'contrasena' => 'contraseña',
        'estatico' => 'estático',
        'menu' => 'menú',
        'seccion' => 'sección',
        'segun' => 'según',
        'validos' => 'válidos',
    ]);
};
$escapeCopy = function ($value) use ($copyVisible) {
    return htmlspecialchars($copyVisible($value), ENT_QUOTES, 'UTF-8');
};
$fila = function ($label, $value) {
    $value = $value === null || $value === '' ? '-' : $value;
    ?>
    <div class="saas-detail-data-item">
        <dt><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></dt>
        <dd><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></dd>
    </div>
    <?php
};

$siguientePaso = [
    'href' => '#modulos',
    'icono' => 'fa-list-check',
    'titulo' => 'Revisa los bloques contratados',
    'detalle' => 'Confirma que el cobro mensual coincide con lo acordado con el hotel.',
];
if (empty($planActual['id'])) {
    $siguientePaso = [
        'href' => '#plan',
        'icono' => 'fa-layer-group',
        'titulo' => 'Asigna un plan comercial',
        'detalle' => 'El hotel todavía no tiene un plan registrado. Después podrás ajustar sus bloques.',
    ];
} elseif ($usuariosAdminCount === 0) {
    $siguientePaso = [
        'href' => '#usuarios',
        'icono' => 'fa-user-shield',
        'titulo' => 'Crea el primer acceso',
        'detalle' => 'Agrega a la persona que administrará el sistema de este hotel.',
    ];
} elseif (empty($brandingHotel)) {
    $siguientePaso = [
        'href' => '#branding',
        'icono' => 'fa-palette',
        'titulo' => 'Personaliza la marca del hotel',
        'detalle' => 'Configura nombre visual, colores, logo e íconos antes de entregar el sistema.',
    ];
} elseif (!$activo) {
    $siguientePaso = [
        'href' => '#resumen',
        'icono' => 'fa-power-off',
        'titulo' => 'Activa el hotel cuando esté listo',
        'detalle' => 'Verifica plan, bloques, accesos y marca antes de habilitar la entrada.',
    ];
}
?>

<style>
.ms-admin-scope .saas-hotel-detail {
    --ms-detail-soft: #f8fafc;
    --ms-detail-blue-soft: rgba(37, 99, 235, .07);
    --ms-detail-blue-border: rgba(37, 99, 235, .18);
    --ms-detail-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 32px rgba(15, 23, 42, .035);
}

/* Neutraliza la regla global .grid { min-height: 200px } solo en esta vista. */
.ms-admin-scope .saas-hotel-detail .grid {
    min-height: 0;
}

.ms-admin-scope .saas-detail-hero,
.ms-admin-scope .saas-detail-panel {
    border: 1px solid var(--ms-border);
    border-radius: .875rem;
    background: var(--ms-surface);
    box-shadow: var(--ms-detail-shadow);
}

.ms-admin-scope .saas-detail-hero {
    overflow: hidden;
}

.ms-admin-scope .saas-detail-hero-main {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 1.5rem;
}

.ms-admin-scope .saas-detail-actions {
    display: flex;
    flex-wrap: wrap;
    gap: .625rem;
}

.ms-admin-scope .saas-detail-button {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    border-radius: .5rem;
    padding: .625rem 1rem;
    font-size: .875rem;
    font-weight: 600;
    transition: background-color .16s ease, border-color .16s ease, opacity .16s ease, transform .16s ease;
}

.ms-admin-scope .saas-detail-button:focus-visible,
.ms-admin-scope .saas-detail-nav a:focus-visible,
.ms-admin-scope .saas-detail-login-url:focus-visible,
.ms-admin-scope .saas-module-search:focus-visible {
    outline: 3px solid rgba(37, 99, 235, .24);
    outline-offset: 2px;
}

.ms-admin-scope .saas-detail-button.is-primary {
    background: var(--ms-primary);
    color: #fff;
}

.ms-admin-scope .saas-detail-button.is-primary:hover {
    background: var(--ms-primary-hover);
    transform: translateY(-1px);
}

.ms-admin-scope .saas-detail-button.is-secondary {
    border: 1px solid var(--ms-border);
    background: #fff;
    color: var(--ms-text);
}

.ms-admin-scope .saas-detail-button.is-secondary:hover {
    border-color: #cbd5e1;
    background: var(--ms-detail-soft);
}

.ms-admin-scope .saas-detail-button.is-danger {
    background: var(--ms-danger);
    color: #fff;
}

.ms-admin-scope .saas-detail-button.is-success {
    background: var(--ms-success);
    color: #fff;
}

.ms-admin-scope .saas-detail-status {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    border-radius: 999px;
    padding: .35rem .7rem;
    font-size: .75rem;
    font-weight: 700;
}

.ms-admin-scope .saas-detail-status::before {
    width: .45rem;
    height: .45rem;
    border-radius: 999px;
    background: currentColor;
    content: '';
}

.ms-admin-scope .saas-detail-status.is-active {
    background: rgba(22, 163, 74, .11);
    color: var(--ms-success);
}

.ms-admin-scope .saas-detail-status.is-inactive {
    background: rgba(100, 116, 139, .11);
    color: var(--ms-muted);
}

.ms-admin-scope .saas-detail-overview {
    display: grid;
    border-top: 1px solid var(--ms-border);
    background: var(--ms-detail-soft);
}

.ms-admin-scope .saas-detail-metric {
    min-width: 0;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .saas-detail-metric:last-child {
    border-bottom: 0;
}

.ms-admin-scope .saas-detail-metric-label {
    color: var(--ms-muted);
    font-size: .6875rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.ms-admin-scope .saas-detail-metric-value {
    margin-top: .35rem;
    color: var(--ms-text);
    font-size: 1rem;
    font-weight: 700;
    line-height: 1.35;
}

.ms-admin-scope .saas-detail-login-access {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--ms-detail-blue-border);
    background: var(--ms-detail-blue-soft);
}

.ms-admin-scope .saas-detail-login-main {
    display: flex;
    min-width: 0;
    align-items: flex-start;
    gap: .875rem;
}

.ms-admin-scope .saas-detail-login-copy {
    min-width: 0;
}

.ms-admin-scope .saas-detail-login-access > .saas-detail-button {
    flex: 0 0 auto;
}

.ms-admin-scope .saas-detail-login-url {
    display: inline-block;
    margin-top: .3rem;
    overflow-wrap: anywhere;
    color: var(--ms-primary);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    font-size: .8125rem;
    font-weight: 650;
    line-height: 1.5;
}

.ms-admin-scope .saas-detail-login-url:hover {
    text-decoration: underline;
}

.ms-admin-scope .saas-detail-next {
    display: flex;
    align-items: flex-start;
    gap: .875rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--ms-detail-blue-border);
    background: var(--ms-detail-blue-soft);
    color: var(--ms-text);
}

.ms-admin-scope .saas-detail-next-icon,
.ms-admin-scope .saas-section-icon {
    display: inline-flex;
    width: 2.25rem;
    height: 2.25rem;
    flex: 0 0 2.25rem;
    align-items: center;
    justify-content: center;
    border-radius: .65rem;
    background: rgba(37, 99, 235, .10);
    color: var(--ms-primary);
}

.ms-admin-scope .saas-detail-nav-wrap {
    position: sticky;
    z-index: 20;
    top: .75rem;
    margin: 1rem 0 1.5rem;
}

.ms-admin-scope .saas-detail-nav {
    display: flex;
    gap: .375rem;
    overflow-x: auto;
    padding: .375rem;
    border: 1px solid var(--ms-border);
    border-radius: .75rem;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 8px 24px rgba(15, 23, 42, .07);
    scrollbar-width: thin;
}

.ms-admin-scope .saas-detail-nav a {
    display: inline-flex;
    min-height: 44px;
    flex: 1 0 auto;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    border-radius: .5rem;
    padding: .55rem .8rem;
    color: var(--ms-muted);
    font-size: .8125rem;
    font-weight: 650;
    white-space: nowrap;
    transition: background-color .16s ease, color .16s ease;
}

.ms-admin-scope .saas-detail-nav a:hover,
.ms-admin-scope .saas-detail-nav a.is-active {
    background: var(--ms-detail-blue-soft);
    color: var(--ms-primary);
}

.ms-admin-scope .saas-detail-nav a[aria-selected="true"] {
    box-shadow: inset 0 0 0 1px var(--ms-detail-blue-border);
}

/* Enlace que sale del detalle (Configuracion del hotel). */
.ms-admin-scope .saas-detail-nav a.saas-detail-nav-out {
    color: var(--ms-primary);
    box-shadow: inset 0 0 0 1px var(--ms-detail-blue-border);
}

.ms-admin-scope .saas-detail-nav-out-icon {
    font-size: .6875rem;
    opacity: .7;
}

.ms-admin-scope .saas-detail-section {
    scroll-margin-top: 6.5rem;
    margin-top: 2rem;
}

.ms-admin-scope .saas-detail-section[hidden] {
    display: none !important;
}

.ms-admin-scope .saas-detail-section.is-active {
    animation: saas-detail-panel-in .16s ease-out both;
}

@keyframes saas-detail-panel-in {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.ms-admin-scope .saas-section-heading {
    display: flex;
    flex-direction: column;
    gap: .875rem;
    margin-bottom: .875rem;
}

.ms-admin-scope .saas-section-heading-main {
    display: flex;
    min-width: 0;
    align-items: flex-start;
    gap: .875rem;
}

.ms-admin-scope .saas-section-heading h2 {
    color: var(--ms-text);
    font-size: 1.125rem;
    font-weight: 700;
    line-height: 1.35;
}

.ms-admin-scope .saas-section-heading p {
    margin-top: .2rem;
    max-width: 52rem;
    color: var(--ms-muted);
    font-size: .875rem;
    line-height: 1.55;
}

.ms-admin-scope .saas-detail-data-grid {
    display: grid;
    padding: .5rem 1.25rem;
}

.ms-admin-scope .saas-detail-data-item {
    min-width: 0;
    padding: .875rem 0;
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .saas-detail-data-item:last-child {
    border-bottom: 0;
}

.ms-admin-scope .saas-detail-data-item dt {
    color: var(--ms-muted);
    font-size: .6875rem;
    font-weight: 700;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.ms-admin-scope .saas-detail-data-item dd {
    margin-top: .3rem;
    overflow-wrap: anywhere;
    color: var(--ms-text);
    font-size: .875rem;
    font-weight: 550;
    line-height: 1.45;
}

.ms-admin-scope .saas-detail-panel-footer {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid var(--ms-border);
    background: var(--ms-detail-soft);
}

.ms-admin-scope .saas-detail-panel input:not([type="checkbox"]):not([type="hidden"]):not([type="file"]),
.ms-admin-scope .saas-detail-panel select,
.ms-admin-scope .saas-detail-panel .saas-module-search {
    min-height: 44px;
    border-color: #cbd5e1;
    border-radius: .5rem;
    background: #fff;
    color: var(--ms-text);
}

.ms-admin-scope .saas-detail-panel input:not([type="checkbox"]):not([type="hidden"]):not([type="file"]):focus,
.ms-admin-scope .saas-detail-panel select:focus,
.ms-admin-scope .saas-module-search:focus {
    border-color: var(--ms-primary) !important;
    outline: 0;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .13) !important;
}

.ms-admin-scope .saas-detail-panel input[type="checkbox"] {
    width: 1.125rem;
    height: 1.125rem;
    accent-color: var(--ms-primary);
}

.ms-admin-scope .saas-plan-card {
    border: 1px solid var(--ms-border);
    border-radius: .65rem;
    padding: 1rem;
    background: #fff;
}

.ms-admin-scope .saas-plan-card.is-current {
    border-color: var(--ms-detail-blue-border);
    background: var(--ms-detail-blue-soft);
}

.ms-admin-scope .saas-plan-card details {
    margin-top: .65rem;
}

.ms-admin-scope .saas-plan-card summary {
    cursor: pointer;
    color: var(--ms-primary);
    font-size: .75rem;
    font-weight: 650;
}

.ms-admin-scope .saas-module-toolbar {
    display: flex;
    flex-direction: column;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--ms-border);
}

.ms-admin-scope .saas-module-search-wrap {
    position: relative;
    min-width: 0;
    flex: 1;
}

.ms-admin-scope .saas-module-search-wrap > i {
    position: absolute;
    z-index: 1;
    top: 22px;
    left: .875rem;
    transform: translateY(-50%);
    color: var(--ms-muted);
    pointer-events: none;
}

.ms-admin-scope .saas-module-search {
    width: 100%;
    padding: .625rem .875rem .625rem 2.5rem;
    border: 1px solid #cbd5e1;
    font-size: .875rem;
}

.ms-admin-scope .saas-table-wrap {
    overflow-x: auto;
}

.ms-admin-scope .saas-detail-table {
    width: 100%;
    border-collapse: collapse;
}

.ms-admin-scope .saas-detail-table th {
    padding: .75rem 1rem;
    background: var(--ms-detail-soft);
    color: var(--ms-muted);
    font-size: .6875rem;
    font-weight: 700;
    letter-spacing: .055em;
    text-align: left;
    text-transform: uppercase;
}

.ms-admin-scope .saas-detail-table td {
    padding: .875rem 1rem;
    border-top: 1px solid var(--ms-border);
    vertical-align: middle;
}

.ms-admin-scope .saas-detail-table tbody tr:hover {
    background: rgba(248, 250, 252, .7);
}

.ms-admin-scope .saas-detail-empty-search {
    display: none;
    padding: 2rem 1.25rem;
    color: var(--ms-muted);
    font-size: .875rem;
    text-align: center;
}

.ms-admin-scope .saas-brand-preview {
    overflow: hidden;
    border: 1px solid var(--ms-border);
    border-radius: .75rem;
    background: #fff;
}

.ms-admin-scope .saas-branding-group-title {
    grid-column: 1 / -1;
    margin-top: .25rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ms-border);
    color: var(--ms-muted);
    font-size: .6875rem;
    font-weight: 700;
    letter-spacing: .065em;
    text-transform: uppercase;
}

.ms-admin-scope .saas-branding-group-title:first-child {
    margin-top: 0;
    padding-top: 0;
    border-top: 0;
}

.ms-admin-scope .saas-detail-panel input[type="file"] {
    min-height: 44px;
}

@media (min-width: 640px) {
    .ms-admin-scope .saas-detail-hero-main,
    .ms-admin-scope .saas-section-heading,
    .ms-admin-scope .saas-detail-panel-footer,
    .ms-admin-scope .saas-module-toolbar,
    .ms-admin-scope .saas-detail-login-access {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }

    .ms-admin-scope .saas-detail-overview {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .ms-admin-scope .saas-detail-metric:nth-child(odd) {
        border-right: 1px solid var(--ms-border);
    }

    .ms-admin-scope .saas-detail-data-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1.5rem;
    }

    .ms-admin-scope .saas-detail-data-item:nth-last-child(-n + 2) {
        border-bottom: 0;
    }
}

@media (min-width: 1024px) {
    .ms-admin-scope .saas-detail-overview {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .ms-admin-scope .saas-detail-metric {
        border-right: 1px solid var(--ms-border);
        border-bottom: 0;
    }

    .ms-admin-scope .saas-detail-metric:last-child {
        border-right: 0;
    }

    .ms-admin-scope .saas-brand-preview {
        position: sticky;
        top: 6.5rem;
    }
}

@media (max-width: 767px) {
    .ms-admin-scope .saas-detail-nav-wrap {
        top: 4.25rem;
    }

    .ms-admin-scope .saas-detail-table.is-responsive thead {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0 0 0 0);
        white-space: nowrap;
    }

    .ms-admin-scope .saas-detail-table.is-responsive,
    .ms-admin-scope .saas-detail-table.is-responsive tbody,
    .ms-admin-scope .saas-detail-table.is-responsive tr,
    .ms-admin-scope .saas-detail-table.is-responsive td {
        display: block;
        width: 100%;
    }

    .ms-admin-scope .saas-detail-table.is-responsive tbody {
        padding: .75rem;
    }

    .ms-admin-scope .saas-detail-table.is-responsive tr {
        overflow: hidden;
        margin-bottom: .75rem;
        border: 1px solid var(--ms-border);
        border-radius: .65rem;
        background: #fff;
    }

    .ms-admin-scope .saas-detail-table.is-responsive tr:last-child {
        margin-bottom: 0;
    }

    .ms-admin-scope .saas-detail-table.is-responsive td {
        display: grid;
        min-height: 44px;
        grid-template-columns: minmax(6.5rem, .7fr) minmax(0, 1fr);
        gap: .75rem;
        align-items: center;
        padding: .75rem;
        border-top: 1px solid var(--ms-border);
    }

    .ms-admin-scope .saas-detail-table.is-responsive td:first-child {
        border-top: 0;
    }

    .ms-admin-scope .saas-detail-table.is-responsive td::before {
        color: var(--ms-muted);
        content: attr(data-label);
        font-size: .6875rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .ms-admin-scope .saas-modules-table td.saas-module-name {
        display: block;
    }

    .ms-admin-scope .saas-modules-table td.saas-module-name::before {
        display: none;
    }

    .ms-admin-scope .saas-detail-actions,
    .ms-admin-scope .saas-detail-actions .saas-detail-button,
    .ms-admin-scope .saas-detail-panel-footer .saas-detail-button,
    .ms-admin-scope .saas-detail-panel-footer form,
    .ms-admin-scope .saas-detail-panel-footer form .saas-detail-button {
        width: 100%;
    }

    .ms-admin-scope .saas-detail-panel input:not([type="checkbox"]):not([type="hidden"]):not([type="file"]),
    .ms-admin-scope .saas-detail-panel select,
    .ms-admin-scope .saas-module-search {
        font-size: 16px;
    }
}

@media (max-width: 639px) {
    .ms-admin-scope .saas-detail-login-access .saas-detail-button {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .ms-admin-scope .saas-detail-button,
    .ms-admin-scope .saas-detail-nav a {
        transition: none;
    }

    .ms-admin-scope .saas-detail-section.is-active {
        animation: none;
    }
}

/* Secciones contraibles (bloqueados/internos): mismo patron del catalogo. */
.ms-admin-scope .ms-cat-summary {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: 1rem 1.5rem;
    min-height: 44px;
    cursor: pointer;
    list-style: none;
    -webkit-user-select: none;
    user-select: none;
}
.ms-admin-scope .ms-cat-summary::-webkit-details-marker { display: none; }
.ms-admin-scope .ms-cat-summary:focus-visible {
    outline: 2px solid var(--ms-primary);
    outline-offset: -2px;
    border-radius: .5rem;
}
.ms-admin-scope .ms-cat-summary .ms-cat-chevron {
    margin-left: auto;
    color: var(--ms-muted);
    transition: transform .15s ease;
    flex-shrink: 0;
}
.ms-admin-scope details[open] > .ms-cat-summary .ms-cat-chevron { transform: rotate(180deg); }
.ms-admin-scope details[open] > .ms-cat-summary { border-bottom: 1px solid var(--ms-border); }
@media (prefers-reduced-motion: reduce) {
    .ms-admin-scope .ms-cat-summary .ms-cat-chevron { transition: none; }
}
</style>

<div class="saas-hotel-detail max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <header class="saas-detail-hero">
        <div class="saas-detail-hero-main">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="saas-detail-status <?= $activo ? 'is-active' : 'is-inactive' ?>">
                        <?= $activo ? 'Hotel activo' : 'Hotel inactivo' ?>
                    </span>
                    <span class="text-xs font-medium" style="color:var(--ms-muted);">Cliente #<?= (int) ($hotel['id'] ?? 0) ?></span>
                </div>
                <h1 class="mt-3 text-2xl font-bold" style="color:var(--ms-text);">
                    <?= htmlspecialchars($hotel['nombre'] ?? 'Hotel', ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <p class="mt-1 text-sm" style="color:var(--ms-muted);">
                    <?= htmlspecialchars($hotel['slug'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($hotel['codigo'])): ?>· <?= htmlspecialchars($hotel['codigo'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?>
                </p>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed" style="color:var(--ms-muted);">
                    Administra la contratación, los accesos y la identidad visual de este hotel desde un solo lugar.
                </p>
            </div>
            <div class="saas-detail-actions">
                <a href="<?= back_url('admin/saas/hoteles') ?>" class="saas-detail-button is-secondary">
                    <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                    Volver a hoteles
                </a>
                <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="saas-detail-button is-primary">
                    <i class="fas fa-pen text-xs" aria-hidden="true"></i>
                    Editar datos
                </a>
            </div>
        </div>

        <div class="saas-detail-overview" aria-label="Resumen del hotel">
            <div class="saas-detail-metric">
                <div class="saas-detail-metric-label">Plan comercial</div>
                <div class="saas-detail-metric-value"><?= $escapeCopy($planActual['nombre'] ?? 'Sin plan') ?></div>
            </div>
            <div class="saas-detail-metric">
                <div class="saas-detail-metric-label">Cobro estimado</div>
                <div class="saas-detail-metric-value">
                    <?= isset($resumenCobro['total']) ? '$' . number_format((float) $resumenCobro['total'], 2) : '—' ?>
                    <?php if (isset($resumenCobro['total'])): ?><span class="text-xs font-normal" style="color:var(--ms-muted);">/ mes</span><?php endif; ?>
                </div>
            </div>
            <div class="saas-detail-metric">
                <div class="saas-detail-metric-label">Bloques opcionales</div>
                <div class="saas-detail-metric-value"><?= (int) $bloquesExtraActivos ?> <span class="text-xs font-normal" style="color:var(--ms-muted);">contratados</span></div>
            </div>
            <div class="saas-detail-metric">
                <div class="saas-detail-metric-label">Accesos vinculados</div>
                <div class="saas-detail-metric-value"><?= (int) $usuariosAdminCount ?> <span class="text-xs font-normal" style="color:var(--ms-muted);">usuarios</span></div>
            </div>
        </div>

        <?php if ($hotelLoginUrl): ?>
            <div class="saas-detail-login-access" aria-label="Acceso al login del hotel">
                <div class="saas-detail-login-main">
                    <span class="saas-detail-next-icon" aria-hidden="true"><i class="fas fa-arrow-right-to-bracket"></i></span>
                    <div class="saas-detail-login-copy">
                        <p class="text-sm font-semibold" style="color:var(--ms-text);">Login del hotel</p>
                        <a href="<?= htmlspecialchars($hotelLoginUrl, ENT_QUOTES, 'UTF-8') ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="saas-detail-login-url">
                            <?= htmlspecialchars($hotelLoginUrl, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">
                            Usa esta URL en una ventana privada si quieres ver el formulario sin cerrar tu sesión SaaS.
                        </p>
                    </div>
                </div>
                <a href="<?= htmlspecialchars($hotelLoginUrl, ENT_QUOTES, 'UTF-8') ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="saas-detail-button is-primary">
                    <i class="fas fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
                    Abrir login del hotel
                </a>
            </div>
        <?php endif; ?>

        <a href="<?= htmlspecialchars($siguientePaso['href'], ENT_QUOTES, 'UTF-8') ?>" class="saas-detail-next" data-detail-tab-trigger>
            <span class="saas-detail-next-icon" aria-hidden="true"><i class="fas <?= htmlspecialchars($siguientePaso['icono'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
            <span class="min-w-0">
                <span class="block text-[11px] font-bold uppercase tracking-wider" style="color:var(--ms-primary);">Siguiente recomendado</span>
                <span class="mt-0.5 block text-sm font-semibold"><?= htmlspecialchars($siguientePaso['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="mt-0.5 block text-xs leading-relaxed" style="color:var(--ms-muted);"><?= htmlspecialchars($siguientePaso['detalle'], ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <i class="fas fa-arrow-right ml-auto mt-2 text-xs" style="color:var(--ms-primary);" aria-hidden="true"></i>
        </a>
    </header>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
        <div class="mt-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm <?= $tipo === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800' ?>" role="status">
            <i class="fas <?= $tipo === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?> mt-0.5" aria-hidden="true"></i>
            <div><?= $mensaje['texto'] ?? '' ?></div>
        </div>
    <?php endif; ?>

    <div class="saas-detail-nav-wrap">
        <nav class="saas-detail-nav" aria-label="Configuración del hotel" role="tablist" aria-orientation="horizontal">
            <a href="#resumen" id="tab-resumen" class="is-active" role="tab" aria-controls="resumen" aria-selected="true" tabindex="0"><i class="fas fa-building" aria-hidden="true"></i><span>Datos</span></a>
            <a href="#plan" id="tab-plan" role="tab" aria-controls="plan" aria-selected="false" tabindex="-1"><i class="fas fa-layer-group" aria-hidden="true"></i><span>Plan</span></a>
            <a href="#modulos" id="tab-modulos" role="tab" aria-controls="modulos" aria-selected="false" tabindex="-1"><i class="fas fa-list-check" aria-hidden="true"></i><span>Bloques y cobro</span></a>
            <a href="#usuarios" id="tab-usuarios" role="tab" aria-controls="usuarios" aria-selected="false" tabindex="-1"><i class="fas fa-user-shield" aria-hidden="true"></i><span>Accesos</span></a>
            <a href="#branding" id="tab-branding" role="tab" aria-controls="branding" aria-selected="false" tabindex="-1"><i class="fas fa-palette" aria-hidden="true"></i><span>Marca</span></a>
            <?php // Sin role="tab": sale de esta pagina, no es un panel de aqui. ?>
            <a href="<?= url('admin/saas/hoteles/' . (int) ($hotel['id'] ?? 0) . '/configuracion') ?>" class="saas-detail-nav-out"><i class="fas fa-sliders-h" aria-hidden="true"></i><span>Configuración</span><i class="fas fa-arrow-up-right-from-square saas-detail-nav-out-icon" aria-hidden="true"></i></a>
        </nav>
    </div>

    <section id="resumen" class="saas-detail-section is-active" role="tabpanel" aria-labelledby="tab-resumen" tabindex="0">
    <?php $cobrosHotel = $cobrosHotel ?? []; ?>
    <?php if (!empty($cobrosHotel)): ?>
    <div class="saas-detail-panel mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Estado de cuenta</h2>
            <p class="text-sm text-gray-500">Cobros mensuales de este hotel (los gestionas en <a href="<?= url('admin/saas/cobros') ?>" class="underline">Cobros</a>).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y" style="border-color:var(--ms-border);">
                <thead style="background:var(--ms-bg);">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Periodo</th>
                        <th class="px-5 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Monto</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Estado</th>
                        <th class="px-5 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Pago</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color:var(--ms-border);">
                <?php
                $badgeCuenta = static function ($estado) {
                    $map = [
                        'pagado' => ['Pagado', 'background:rgba(22,163,74,.12);color:var(--ms-success);'],
                        'pendiente' => ['Pendiente', 'background:rgba(245,158,11,.14);color:#92600A;'],
                        'vencido' => ['Vencido', 'background:rgba(220,38,38,.12);color:#B91C1C;'],
                        'cancelado' => ['Cancelado', 'background:rgba(100,116,139,.12);color:var(--ms-muted);'],
                    ];
                    return $map[$estado] ?? [$estado, ''];
                };
                foreach ($cobrosHotel as $cc): [$ccTxt, $ccStyle] = $badgeCuenta($cc['estado']); ?>
                    <tr>
                        <td class="px-5 py-3 text-sm font-medium" style="color:var(--ms-text);"><?= htmlspecialchars((string) $cc['periodo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-5 py-3 text-right text-sm font-semibold" style="color:var(--ms-text);">$<?= number_format((float) $cc['monto'], 2) ?></td>
                        <td class="px-5 py-3"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold" style="<?= $ccStyle ?>"><?= htmlspecialchars($ccTxt, ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="px-5 py-3 text-xs" style="color:var(--ms-muted);">
                            <?= $cc['metodo'] ? htmlspecialchars((string) $cc['metodo'], ENT_QUOTES, 'UTF-8') : '—' ?><?= $cc['pagado_at'] ? ' · ' . htmlspecialchars(date('d/m/Y', strtotime((string) $cc['pagado_at'])), ENT_QUOTES, 'UTF-8') : '' ?>
                            <?= (!$cc['pagado_at'] && !empty($cc['vence_at']) && in_array($cc['estado'], ['pendiente', 'vencido'], true)) ? 'vence ' . htmlspecialchars(date('d/m/Y', strtotime((string) $cc['vence_at'])), ENT_QUOTES, 'UTF-8') : '' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

        <div class="saas-section-heading">
            <div class="saas-section-heading-main">
                <span class="saas-section-icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                <div class="min-w-0">
                    <h2>Datos y estado del hotel</h2>
                    <p>Información comercial, contacto y configuración regional del cliente.</p>
                </div>
            </div>
            <a href="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar') ?>" class="saas-detail-button is-secondary">
                <i class="fas fa-pen text-xs" aria-hidden="true"></i>
                Editar información
            </a>
        </div>

        <div class="saas-detail-panel overflow-hidden">
        <dl class="saas-detail-data-grid">
            <?php
            $fila('Nombre comercial', $hotel['nombre'] ?? null);
            $fila('Slug', $hotel['slug'] ?? null);
            $fila('Código interno', $hotel['codigo'] ?? null);
            $fila('Razón social', $hotel['razon_social'] ?? null);
            $fila('RFC', $hotel['rfc'] ?? null);
            $fila('Teléfono', $hotel['telefono'] ?? null);
            $fila('Email', $hotel['email'] ?? null);
            $fila('Dirección', $hotel['direccion'] ?? null);
            $fila('Ciudad', $hotel['ciudad'] ?? null);
            $fila('Estado / región', $hotel['estado'] ?? null);
            $fila('País', $hotel['pais'] ?? null);
            $fila('Zona horaria', $hotel['zona_horaria'] ?? null);
            $fila('Moneda', trim(($hotel['moneda_codigo'] ?? '') . ' ' . ($hotel['moneda_simbolo'] ?? '')));
            $fila('Plan comercial', $copyVisible($planActual['nombre'] ?? 'Sin plan'));
            $fila('Login del hotel', $hotelLoginUrl ?: 'Disponible cuando el hotel esté activo');
            $fila('Reservas en linea (link publico)', url('h/' . ($hotel['slug'] ?? '') . '/reservar'));
            $fila('Creado', $hotel['created_at'] ?? null);
            $fila('Actualizado', $hotel['updated_at'] ?? null);
            ?>
        </dl>

        <div class="saas-detail-panel-footer">
            <div>
                <p class="text-sm font-semibold" style="color:var(--ms-text);">Acceso operativo</p>
                <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">
                    <?= $activo
                        ? 'Suspender bloquea la entrada de los usuarios del hotel hasta que vuelvas a activarlo.'
                        : 'Activa el hotel únicamente cuando plan, bloques, accesos y marca estén listos.' ?>
                </p>
            </div>
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/estado') ?>" data-ms-confirm data-ms-type="<?= $activo ? 'error' : 'success' ?>" data-ms-icon="<?= $activo ? 'logout' : 'login' ?>" data-ms-title="<?= $activo ? '¿Suspender hotel?' : '¿Activar hotel?' ?>" data-ms-msg="<?= $activo ? 'El hotel quedará suspendido y sus usuarios no podrán entrar al sistema.' : 'El hotel quedará activo y sus usuarios podrán volver a entrar.' ?>" data-ms-ok="<?= $activo ? 'Suspender hotel' : 'Activar hotel' ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="activo" value="<?= $activo ? '0' : '1' ?>">
                <button type="submit"
                        class="saas-detail-button <?= $activo ? 'is-danger' : 'is-success' ?>">
                    <i class="fas <?= $activo ? 'fa-ban' : 'fa-power-off' ?> text-xs" aria-hidden="true"></i>
                    <?= $activo ? 'Suspender hotel' : 'Activar hotel' ?>
                </button>
            </form>
        </div>
        </div>
    </section>

    <section id="plan" class="saas-detail-section" role="tabpanel" aria-labelledby="tab-plan" tabindex="0">
        <div class="saas-section-heading">
            <div class="saas-section-heading-main">
                <span class="saas-section-icon" aria-hidden="true"><i class="fas fa-layer-group"></i></span>
                <div class="min-w-0">
                    <h2>Plan comercial</h2>
                    <p>El plan propone un combo de bloques; la contratación y el cobro reales se confirman en “Bloques y cobro”.</p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold" style="<?= $estadoStyle ?>">
                <?= htmlspecialchars($estadoTexto, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>

    <div class="saas-detail-panel overflow-hidden">

        <?php if (empty($planes)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catálogo de planes disponible. Aplique la migración de planes antes de configurar esta sección.
            </div>
        <?php else: ?>
            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>" id="form-plan-hotel">
                <?= csrf_field() ?>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-1">
                        <label for="plan_id" class="block text-sm font-medium text-gray-700">Plan actual</label>
                        <select id="plan_id" name="plan_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach ($planes as $plan): ?>
                                <option value="<?= (int) $plan['id'] ?>" <?= !empty($planActual['id']) && (int) $planActual['id'] === (int) $plan['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($plan['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="aplicar_modulos" value="1"
                                   class="mt-1 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                            <span>
                                Aplicar módulos sugeridos por el plan.
                                <span class="block text-xs text-amber-700">
                                    Esto puede activar o desactivar módulos según el preset seleccionado.
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <?php foreach ($planes as $plan): ?>
                                <?php
                                $planId = (int) $plan['id'];
                                $clavesPlan = array_column($modulosPorPlan[$planId] ?? [], 'clave');
                                ?>
                                <article class="saas-plan-card <?= !empty($planActual['id']) && (int) $planActual['id'] === $planId ? 'is-current' : '' ?>">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold" style="color:var(--ms-text);"><?= $escapeCopy($plan['nombre'] ?? '') ?></h3>
                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold" style="<?= $planBadgeStyle($plan['clave'] ?? '') ?>">
                                                <?= htmlspecialchars(ucfirst($plan['clave'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($planActual['id']) && (int) $planActual['id'] === $planId): ?>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:rgba(22,163,74,.12);color:var(--ms-success);">Actual</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-1 text-xs" style="color:var(--ms-muted);"><?= $escapeCopy($plan['descripcion'] ?? '') ?></p>
                                    <?php if (($plan['clave'] ?? '') === 'personalizado'): ?>
                                        <p class="mt-3 text-xs" style="color:var(--ms-muted);">Sin combo fijo: se arma bloque por bloque en el paso 3.</p>
                                    <?php else: ?>
                                        <p class="mt-2 text-sm font-semibold" style="color:var(--ms-text);">
                                            <?= $plan['precio_mensual'] !== null ? '$' . number_format((float) $plan['precio_mensual'], 2) . ' <span class="text-xs font-normal" style="color:var(--ms-muted);">/ mes</span>' : '' ?>
                                        </p>
                                        <details>
                                            <summary>Ver <?= count($clavesPlan) ?> bloques incluidos</summary>
                                            <p class="mt-2 text-xs leading-relaxed" style="color:var(--ms-muted);"><?= $escapeCopy(implode(', ', $clavesPlan)) ?></p>
                                        </details>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="saas-detail-panel-footer">
                    <p class="text-xs leading-relaxed" style="color:var(--ms-muted);">Guardar el plan no cambia los bloques salvo que marques “Aplicar módulos sugeridos”.</p>
                    <button type="submit" class="saas-detail-button is-primary">
                        <i class="fas fa-floppy-disk text-xs" aria-hidden="true"></i>
                        Guardar plan
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($estadoAuditoria === 'diferencias' && !empty($planActual['id'])): ?>
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-5 py-4">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-amber-600"></i>
                <div class="min-w-0 text-sm text-amber-900">
                    <p class="font-semibold">Lo contratado no coincide con el plan <?= $escapeCopy($planActual['nombre'] ?? '') ?>.</p>
                    <?php if (!empty($modulosApagados)): ?>
                        <p class="mt-1">El plan los incluye pero están apagados: <span class="font-medium"><?= $escapeCopy(implode(', ', array_map(function ($m) { return $m['nombre'] ?? $m['clave'] ?? ''; }, $modulosApagados))) ?></span>.</p>
                    <?php endif; ?>
                    <?php if (!empty($modulosFueraPlan)): ?>
                        <p class="mt-1">Están encendidos aunque el plan no los incluye: <span class="font-medium"><?= $escapeCopy(implode(', ', array_map(function ($m) { return $m['nombre'] ?? $m['clave'] ?? ''; }, $modulosFueraPlan))) ?></span>.</p>
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-amber-800">Esto no es un error: puedes dejarlo así (se cobra lo encendido) o alinear los bloques al plan.</p>
                </div>
                <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/plan') ?>" class="flex-shrink-0" data-ms-confirm data-ms-type="warning" data-ms-icon="alert" data-ms-title="¿Alinear bloques al plan?" data-ms-msg="Esto encenderá/apagará bloques para coincidir con el plan actual." data-ms-ok="Alinear al plan">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= (int) $planActual['id'] ?>">
                    <input type="hidden" name="aplicar_modulos" value="1">
                    <button type="submit" class="px-3 py-2 rounded-md bg-amber-700 text-white text-xs font-medium hover:bg-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">
                        Alinear al plan
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    </section>

    <section id="modulos" class="saas-detail-section" role="tabpanel" aria-labelledby="tab-modulos" tabindex="0">
        <div class="saas-section-heading">
            <div class="saas-section-heading-main">
                <span class="saas-section-icon" aria-hidden="true"><i class="fas fa-list-check"></i></span>
                <div class="min-w-0">
                    <h2>Bloques y cobro mensual</h2>
                    <p>Activa únicamente lo contratado. El total se recalcula al instante antes de guardar.</p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold" style="background:var(--ms-detail-blue-soft);color:var(--ms-primary);">
                <?= (int) $bloquesExtraActivos ?> activos
            </span>
        </div>

    <div class="saas-detail-panel overflow-hidden">

        <?php if (empty($modulosHotel)): ?>
            <div class="px-6 py-6 text-sm text-gray-600">
                No hay catálogo de módulos disponible. Aplique la migración de módulos antes de configurar esta sección.
            </div>
        <?php else: ?>
            <?php
            $precioBaseCobro = isset($resumenCobro['precio_base']) ? (float) $resumenCobro['precio_base'] : 0.0;
            $monedaHotel = $hotel['moneda_codigo'] ?? 'MXN';
            // Paquete basico = tipo_comercial 'base' (los internos Medisoft son
            // es_core pero NO se muestran como beneficio comercial del paquete).
            $modulosBasicos = array_filter($modulosHotel, function ($m) {
                return ($m['tipo_comercial'] ?? (empty($m['es_core']) ? 'opcional' : 'base')) === 'base';
            });
            // Seleccion de contratacion = solo opcionales (disponibles o
            // bloqueados con motivo); los internos quedan fuera del form.
            $modulosOpcionalesHotel = array_filter($modulosHotel, function ($m) {
                return empty($m['es_core'])
                    && (($m['tipo_comercial'] ?? 'opcional') === 'opcional');
            });
            $modulosInternos = array_filter($modulosHotel, function ($m) {
                return ($m['tipo_comercial'] ?? '') === 'interno';
            });
            ?>

            <?php
            // Subgrupos de opcionales para la jerarquia visual (solo lectura de
            // los mismos datos). Un reporte que se desbloquee en el futuro pasa
            // solo a "disponibles" y se vuelve contratable sin rediseño.
            $esReporteIndividualHotel = function (array $m): bool {
                return strpos((string) ($m['clave'] ?? ''), 'reporte_') === 0;
            };
            $opContratados = array_filter($modulosOpcionalesHotel, function ($m) {
                return !empty($m['activo_global']) && !empty($m['activo_hotel']);
            });
            $opPorContratar = array_filter($modulosOpcionalesHotel, function ($m) {
                return !empty($m['activo_global']) && empty($m['activo_hotel']);
            });
            $opReportesBloqueados = array_filter($modulosOpcionalesHotel, function ($m) use ($esReporteIndividualHotel) {
                return empty($m['activo_global']) && $esReporteIndividualHotel($m);
            });
            $opBloqueados = array_filter($modulosOpcionalesHotel, function ($m) use ($esReporteIndividualHotel) {
                return empty($m['activo_global']) && !$esReporteIndividualHotel($m);
            });
            $totalCobradoInicial = isset($resumenCobro['total_modulos']) ? (float) $resumenCobro['total_modulos'] : 0.0;
            ?>

            <!-- A. Resumen del cobro mensual -->
            <div class="px-6 py-4 border-b border-gray-200" style="background:rgba(37,99,235,.04);">
                <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Cobro mensual estimado</div>
                <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div>
                        <div class="text-xs" style="color:var(--ms-muted);">Paquete básico</div>
                        <div class="text-lg font-semibold" style="color:var(--ms-text);">$<span id="cobro-base"><?= number_format($precioBaseCobro, 2) ?></span></div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--ms-muted);">Bloques contratados (<span id="cobro-conteo"><?= count($opContratados) ?></span>)</div>
                        <div class="text-lg font-semibold" style="color:var(--ms-text);">$<span id="cobro-modulos"><?= number_format($totalCobradoInicial, 2) ?></span></div>
                    </div>
                    <div>
                        <div class="text-xs" style="color:var(--ms-muted);">Total mensual</div>
                        <div class="text-lg font-bold" style="color:var(--ms-primary);">$<span id="cobro-total"><?= number_format($precioBaseCobro + $totalCobradoInicial, 2) ?></span> <span class="text-xs font-semibold" style="color:var(--ms-muted);"><?= htmlspecialchars($monedaHotel, ENT_QUOTES, 'UTF-8') ?></span></div>
                    </div>
                </div>
                <p class="mt-2 text-xs" style="color:var(--ms-muted);">Se recalcula al marcar bloques o cambiar precios especiales, antes de guardar. Los módulos en pausa y las funciones internas no suman.</p>
            </div>

            <!-- B. Paquete base incluido (lectura) -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold" style="color:var(--ms-text);"><i class="fas fa-lock text-xs mr-1.5" aria-hidden="true" style="color:var(--ms-primary);"></i>Paquete base — incluido</p>
                    <span class="text-xs" style="color:var(--ms-muted);">Cubierto por el precio del paquete</span>
                </div>
                <ul class="mt-3 grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ($modulosBasicos as $modulo): ?>
                        <li class="flex items-center gap-2 rounded-lg border px-2.5 py-2" style="border-color:var(--ms-border);background:#fff;">
                            <i class="fas fa-circle-check text-xs flex-shrink-0" aria-hidden="true" style="color:var(--ms-primary);"></i>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium" style="color:var(--ms-text);"><?= $escapeCopy($modulo['nombre'] ?? '') ?></span>
                                <span class="block text-[11px]" style="color:var(--ms-muted);">Incluido en el paquete base</span>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/modulos') ?>" id="form-modulos-hotel">
                <?= csrf_field() ?>

                <div class="saas-module-toolbar">
                    <div>
                        <p class="text-sm font-semibold" style="color:var(--ms-text);">Bloques opcionales</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-gray-500">Marcado = contratado. Usa precio especial solamente cuando exista un acuerdo distinto al precio de lista.</p>
                    </div>
                    <div class="saas-module-search-wrap" style="max-width:24rem;">
                        <i class="fas fa-search text-xs" aria-hidden="true"></i>
                        <label for="module-search" class="sr-only">Buscar bloque</label>
                        <input type="search" id="module-search" class="saas-module-search" placeholder="Buscar por nombre, clave, categoría o motivo" autocomplete="off">
                        <p class="mt-1 text-right text-[11px]" style="color:var(--ms-muted);"><span id="module-visible-count"><?= count($modulosOpcionalesHotel) ?></span> de <?= count($modulosOpcionalesHotel) ?> bloques</p>
                    </div>
                </div>

                <?php
                // Fila contratable (checkbox habilitado + precio especial). Se usa
                // en "Contratados" y "Disponibles": mismo contrato del formulario.
                $renderFilaOpcional = function (array $modulo) use ($escapeCopy, $copyVisible) {
                    $precioCatalogo = (float) ($modulo['precio_mensual'] ?? 0);
                    $precioOverride = $modulo['precio_override'] ?? null;
                    $tieneOverride = $precioOverride !== null && $precioOverride !== '';
                    $precioAplicado = $tieneOverride ? (float) $precioOverride : $precioCatalogo;
                    $textoBusqueda = strtolower($copyVisible(($modulo['nombre'] ?? '') . ' ' . ($modulo['descripcion'] ?? '') . ' ' . ($modulo['clave'] ?? '') . ' ' . ($modulo['categoria'] ?? '')));
                    ?>
                                <tr data-module-row data-module-name="<?= htmlspecialchars($textoBusqueda, ENT_QUOTES, 'UTF-8') ?>">
                                    <td data-label="Contratado" class="text-sm">
                                        <input type="checkbox"
                                               name="modulos[]"
                                               value="<?= (int) $modulo['id'] ?>"
                                               aria-label="Contratar <?= $escapeCopy($modulo['nombre'] ?? '') ?>"
                                               data-precio="<?= number_format($precioAplicado, 2, '.', '') ?>"
                                               <?= !empty($modulo['activo_hotel']) ? 'checked' : '' ?>
                                               class="modulo-toggle h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    </td>
                                    <td data-label="Bloque" class="saas-module-name text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium"><?= $escapeCopy($modulo['nombre'] ?? '') ?></span>
                                        </div>
                                        <?php if (!empty($modulo['descripcion'])): ?>
                                            <div class="mt-1 text-xs text-gray-500"><?= $escapeCopy($modulo['descripcion']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Precio de lista" class="text-sm text-gray-700 whitespace-nowrap">$<?= number_format($precioCatalogo, 2) ?> <span class="text-xs text-gray-400">/ mes</span></td>
                                    <td data-label="Precio especial" class="text-sm">
                                        <input type="number" step="0.01" min="0"
                                               inputmode="decimal"
                                               name="precio_override[<?= (int) $modulo['id'] ?>]"
                                               value="<?= $tieneOverride ? number_format((float) $precioOverride, 2, '.', '') : '' ?>"
                                               placeholder="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               data-modulo="<?= (int) $modulo['id'] ?>"
                                               data-precio-catalogo="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               class="precio-override w-24 rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                                               aria-label="Precio especial de <?= $escapeCopy($modulo['nombre'] ?? '') ?>">
                                    </td>
                                    <td data-label="Precio aplicado" class="text-sm whitespace-nowrap">
                                        <span class="font-semibold" style="color:var(--ms-text);" data-aplicado-de="<?= (int) $modulo['id'] ?>">$<?= number_format($precioAplicado, 2) ?></span>
                                        <span class="block text-[11px]" style="color:var(--ms-muted);" data-aplicado-tipo-de="<?= (int) $modulo['id'] ?>"><?= $tieneOverride ? 'Precio especial' : 'Precio de lista' ?></span>
                                    </td>
                                </tr>
                    <?php
                };

                $theadOpcionales = '<thead><tr>'
                    . '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contratado</th>'
                    . '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloque</th>'
                    . '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio de lista</th>'
                    . '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio especial</th>'
                    . '<th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio aplicado</th>'
                    . '</tr></thead>';
                ?>

                <!-- C. Contratados -->
                <div class="px-6 pt-4 pb-1">
                    <h3 class="text-sm font-semibold" style="color:var(--ms-text);">
                        <i class="fas fa-circle-check mr-1.5 text-xs" aria-hidden="true" style="color:var(--ms-success);"></i>
                        Contratados
                        <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opContratados) ?>)</span>
                    </h3>
                </div>
                <?php if (empty($opContratados)): ?>
                    <div class="px-6 pb-4 text-sm text-gray-600">Este hotel aún no tiene bloques opcionales contratados.</div>
                <?php else: ?>
                    <div class="saas-table-wrap">
                        <table class="saas-detail-table is-responsive saas-modules-table">
                            <?= $theadOpcionales ?>
                            <tbody>
                                <?php foreach ($opContratados as $modulo) { $renderFilaOpcional($modulo); } ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- D. Disponibles para contratar -->
                <div class="border-t border-gray-100 px-6 pt-4 pb-1">
                    <h3 class="text-sm font-semibold" style="color:var(--ms-text);">
                        <i class="fas fa-plus mr-1.5 text-xs" aria-hidden="true" style="color:var(--ms-primary);"></i>
                        Disponibles para contratar
                        <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opPorContratar) ?>)</span>
                    </h3>
                </div>
                <?php if (empty($opPorContratar)): ?>
                    <div class="px-6 pb-4 text-sm text-gray-600">Sin más opcionales disponibles por ahora.</div>
                <?php else: ?>
                    <div class="saas-table-wrap">
                        <table class="saas-detail-table is-responsive saas-modules-table">
                            <?= $theadOpcionales ?>
                            <tbody>
                                <?php foreach ($opPorContratar as $modulo) { $renderFilaOpcional($modulo); } ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- E. Reportes individuales (bloqueados hasta tener precio) -->
                <?php if (!empty($opReportesBloqueados)): ?>
                    <div class="border-t border-gray-100 px-6 pt-4 pb-1">
                        <h3 class="text-sm font-semibold" style="color:var(--ms-text);">
                            <i class="fas fa-chart-line mr-1.5 text-xs" aria-hidden="true" style="color:var(--ms-muted);"></i>
                            Reportes individuales
                            <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opReportesBloqueados) ?>)</span>
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">Se contratarán por separado cuando tengan precio autorizado; hoy no suman al cobro. Ranking y comparativa de estados está incluido en Procedencia.</p>
                    </div>
                    <ul>
                        <?php foreach ($opReportesBloqueados as $modulo): ?>
                            <li class="flex flex-col gap-1.5 border-t border-gray-100 px-6 py-3 sm:flex-row sm:items-center sm:justify-between"
                                data-module-row
                                data-module-name="<?= htmlspecialchars(strtolower($copyVisible(($modulo['nombre'] ?? '') . ' ' . ($modulo['descripcion'] ?? '') . ' ' . ($modulo['clave'] ?? '') . ' ' . ($modulo['categoria'] ?? '') . ' ' . ($modulo['motivo_bloqueo'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-700"><?= $escapeCopy($modulo['nombre'] ?? '') ?></div>
                                    <?php if (!empty($modulo['motivo_bloqueo'])): ?>
                                        <div class="mt-0.5 text-xs" style="color:var(--ms-warning);"><i class="fas fa-circle-info text-[10px] mr-1" aria-hidden="true"></i><?= $escapeCopy($modulo['motivo_bloqueo']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <span class="inline-flex w-fit flex-shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(245,158,11,.12);color:#92400E;">
                                    <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                    Pendiente de precio
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- F. No disponibles todavia (contraida) -->
                <?php if (!empty($opBloqueados)): ?>
                    <details class="border-t border-gray-200" id="detalle-bloqueados">
                        <summary class="ms-cat-summary">
                            <i class="fas fa-circle-pause text-sm" aria-hidden="true" style="color:var(--ms-warning);"></i>
                            <h3 class="min-w-0 flex-1 text-sm font-semibold" style="color:var(--ms-text);">
                                No disponibles todavía
                                <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($opBloqueados) ?> módulos)</span>
                                <span class="block text-xs font-normal" style="color:var(--ms-muted);">Conservan su registro y contratación histórica, pero no dan acceso ni suman al cobro.</span>
                            </h3>
                            <i class="fas fa-chevron-down text-xs ms-cat-chevron" aria-hidden="true"></i>
                        </summary>
                        <div class="saas-table-wrap">
                            <table class="saas-detail-table is-responsive saas-modules-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Módulo</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Categoría</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($opBloqueados as $modulo): ?>
                                        <tr data-module-row
                                            data-module-name="<?= htmlspecialchars(strtolower($copyVisible(($modulo['nombre'] ?? '') . ' ' . ($modulo['descripcion'] ?? '') . ' ' . ($modulo['clave'] ?? '') . ' ' . ($modulo['categoria'] ?? '') . ' ' . ($modulo['motivo_bloqueo'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>">
                                            <td data-label="Módulo" class="saas-module-name text-sm">
                                                <span class="font-medium text-gray-700"><?= $escapeCopy($modulo['nombre'] ?? '') ?></span>
                                                <?php if (!empty($modulo['descripcion'])): ?>
                                                    <div class="mt-1 text-xs text-gray-500"><?= $escapeCopy($modulo['descripcion']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Categoría" class="text-sm text-gray-500"><?= $escapeCopy($modulo['categoria'] ?: '-') ?></td>
                                            <td data-label="Estado" class="text-sm">
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">
                                                    <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                                    No disponible aún
                                                </span>
                                                <?php if (!empty($modulo['activo_hotel'])): ?>
                                                    <span class="mt-1 block text-[11px]" style="color:var(--ms-muted);">Contratación en pausa (se conserva)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Motivo" class="text-sm">
                                                <span class="text-xs" style="color:var(--ms-warning);"><?= $escapeCopy($modulo['motivo_bloqueo'] ?? '') ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endif; ?>

                <div id="module-empty-search" class="saas-detail-empty-search" role="status">
                    <i class="fas fa-search mr-1.5" aria-hidden="true"></i>No hay bloques que coincidan con esa búsqueda.
                    <button type="button" id="module-search-clear" class="ml-1 font-medium underline" style="color:var(--ms-primary);">Limpiar búsqueda</button>
                </div>

                <div class="saas-detail-panel-footer">
                    <div class="text-xs" style="color:var(--ms-muted);">
                        El total estimado se actualiza arriba, en el resumen del cobro. Precios de lista editables en <a href="<?= url('admin/saas/modulos') ?>" class="underline">Catálogo comercial</a>.
                    </div>
                    <button type="submit" class="saas-detail-button is-primary" id="btn-guardar-modulos">
                        <i class="fas fa-floppy-disk text-xs" aria-hidden="true"></i>
                        <span data-texto-guardar>Guardar bloques y cobro</span>
                    </button>
                </div>
            </form>

            <!-- G. Funciones internas Medisoft (informativo, contraido) -->
            <?php if (!empty($modulosInternos)): ?>
                <details class="border-t border-gray-200">
                    <summary class="ms-cat-summary">
                        <i class="fas fa-screwdriver-wrench text-sm" aria-hidden="true" style="color:var(--ms-muted);"></i>
                        <h3 class="min-w-0 flex-1 text-sm font-semibold" style="color:var(--ms-text);">
                            Funciones internas Medisoft
                            <span class="ml-1 font-normal" style="color:var(--ms-muted);">(<?= count($modulosInternos) ?>)</span>
                            <span class="block text-xs font-normal" style="color:var(--ms-muted);">Infraestructura del sistema: sin costo para el hotel, sin contratación.</span>
                        </h3>
                        <i class="fas fa-chevron-down text-xs ms-cat-chevron" aria-hidden="true"></i>
                    </summary>
                    <ul class="divide-y divide-gray-100">
                        <?php foreach ($modulosInternos as $modulo): ?>
                            <li class="flex flex-col gap-1 px-6 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-700"><?= $escapeCopy($modulo['nombre'] ?? '') ?></div>
                                    <?php if (empty($modulo['activo_global']) && !empty($modulo['motivo_bloqueo'])): ?>
                                        <div class="mt-0.5 text-xs" style="color:var(--ms-warning);"><?= $escapeCopy($modulo['motivo_bloqueo']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-2">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium" style="background:rgba(100,116,139,.08);color:var(--ms-muted);">
                                        <i class="fas fa-screwdriver-wrench text-[10px]" aria-hidden="true"></i>
                                        Uso interno
                                    </span>
                                    <?php if (empty($modulo['activo_global'])): ?>
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold" style="background:rgba(245,158,11,.12);color:#92400E;">
                                            <i class="fas fa-circle-pause text-[10px]" aria-hidden="true"></i>
                                            En pausa
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </details>
            <?php endif; ?>

            <script>
            (function () {
                var form = document.getElementById('form-modulos-hotel');
                if (!form) return;
                var base = <?= json_encode(round($precioBaseCobro, 2)) ?>;
                var fmt = function (n) { return n.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

                function precioDeModulo(toggle) {
                    var override = form.querySelector('.precio-override[data-modulo="' + toggle.value + '"]');
                    if (override && override.value !== '' && !isNaN(parseFloat(override.value))) {
                        return Math.max(0, parseFloat(override.value));
                    }
                    return parseFloat(toggle.getAttribute('data-precio')) || 0;
                }

                // Refleja por fila el precio que aplicaria (especial o de lista).
                function actualizarAplicadoPorFila(toggle) {
                    var celda = document.querySelector('[data-aplicado-de="' + toggle.value + '"]');
                    var tipo = document.querySelector('[data-aplicado-tipo-de="' + toggle.value + '"]');
                    if (!celda) return;
                    var override = form.querySelector('.precio-override[data-modulo="' + toggle.value + '"]');
                    var usaEspecial = override && override.value !== '' && !isNaN(parseFloat(override.value));
                    celda.textContent = '$' + fmt(precioDeModulo(toggle));
                    if (tipo) tipo.textContent = usaEspecial ? 'Precio especial' : 'Precio de lista';
                }

                function recalcular() {
                    var totalModulos = 0;
                    var contratados = 0;
                    form.querySelectorAll('.modulo-toggle:not(:disabled)').forEach(function (toggle) {
                        actualizarAplicadoPorFila(toggle);
                        if (toggle.checked) {
                            totalModulos += precioDeModulo(toggle);
                            contratados += 1;
                        }
                    });
                    var conteo = document.getElementById('cobro-conteo');
                    if (conteo) conteo.textContent = String(contratados);
                    document.getElementById('cobro-modulos').textContent = fmt(totalModulos);
                    document.getElementById('cobro-total').textContent = fmt(base + totalModulos);
                }

                form.addEventListener('change', recalcular);
                form.addEventListener('input', recalcular);
                recalcular();

                // Estado de guardado (el anti doble-submit global de app.js aplica;
                // aqui solo se comunica visualmente el envio en curso).
                form.addEventListener('submit', function () {
                    var boton = document.getElementById('btn-guardar-modulos');
                    var texto = boton ? boton.querySelector('[data-texto-guardar]') : null;
                    if (!boton || boton.dataset.enviando === '1') return;
                    boton.dataset.enviando = '1';
                    boton.setAttribute('aria-busy', 'true');
                    if (texto) texto.textContent = 'Guardando…';
                    var icono = boton.querySelector('i.fas');
                    if (icono) icono.className = 'fas fa-spinner fa-spin text-xs';
                });
            })();
            </script>
        <?php endif; ?>
    </div>
    </section>

    <section id="usuarios" class="saas-detail-section" role="tabpanel" aria-labelledby="tab-usuarios" tabindex="0">
        <div class="saas-section-heading">
            <div class="saas-section-heading-main">
                <span class="saas-section-icon" aria-hidden="true"><i class="fas fa-user-shield"></i></span>
                <div class="min-w-0">
                    <h2>Accesos del hotel</h2>
                    <p>Revisa quién puede entrar y crea o vincula una cuenta administrativa cuando sea necesario.</p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold" style="background:var(--ms-detail-blue-soft);color:var(--ms-primary);">
                <?= count($usuariosHotel) ?> <?= count($usuariosHotel) === 1 ? 'acceso' : 'accesos' ?>
            </span>
        </div>

        <div class="saas-detail-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-sm font-semibold" style="color:var(--ms-text);">Personas con acceso</h3>
                <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">El rol hotelero define lo que pueden hacer dentro de este hotel.</p>
            </div>

            <div class="saas-table-wrap">
            <table class="saas-detail-table is-responsive saas-users-table">
                <thead>
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Usuario</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol hotel</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rol global</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Principal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuariosHotel)): ?>
                        <tr>
                            <td colspan="6" data-label="Accesos" class="px-4 py-8 text-sm text-gray-600 text-center">
                                <i class="fas fa-user-plus mr-1.5" style="color:var(--ms-primary);" aria-hidden="true"></i>
                                Aún no hay personas vinculadas. Completa el formulario de abajo para dar el primer acceso.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuariosHotel as $usuarioHotel): ?>
                            <?php
                            $hotelUsuarioActivo = !empty($usuarioHotel['hotel_usuario_activo']);
                            $usuarioActivo = !empty($usuarioHotel['usuario_activo']);
                            ?>
                            <tr>
                                <td data-label="Usuario" class="text-sm text-gray-900">
                                    <div class="font-medium"><?= htmlspecialchars($usuarioHotel['nombre_completo'] ?: $usuarioHotel['nombre_usuario'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="text-xs text-gray-500"><?= htmlspecialchars($usuarioHotel['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                                </td>
                                <td data-label="Email" class="text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Rol hotel" class="text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_hotel'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Rol global" class="text-sm text-gray-700"><?= htmlspecialchars($usuarioHotel['rol_global'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Estado" class="text-sm text-gray-700">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" style="<?= ($hotelUsuarioActivo && $usuarioActivo) ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.10);color:var(--ms-muted);' ?>">
                                        <?= ($hotelUsuarioActivo && $usuarioActivo) ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td data-label="Principal" class="text-sm text-gray-700">
                                    <?= !empty($usuarioHotel['es_principal']) ? 'Sí' : 'No' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

        <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/usuarios') ?>" class="border-t border-gray-200">
            <?= csrf_field() ?>

            <div class="px-5 pt-5">
                <h3 class="text-sm font-semibold" style="color:var(--ms-text);">Crear o vincular acceso</h3>
                <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">Si el usuario ya existe, solo se vincula. Si es nuevo, completa sus datos y una contraseña temporal.</p>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nombre_usuario" class="block text-sm font-medium text-gray-700">Usuario *</label>
                    <input type="text" id="nombre_usuario" name="nombre_usuario" required maxlength="80"
                           autocomplete="username"
                           value="<?= old('nombre_usuario') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                    <p class="mt-1 text-xs text-gray-500">Si ya existe, se vincula al hotel sin cambiar su contraseña.</p>
                </div>

                <div>
                    <label for="rol_hotel" class="block text-sm font-medium text-gray-700">Rol hotelero *</label>
                    <select id="rol_hotel" name="rol_hotel" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                        <option value="administrador" <?= old('rol_hotel', 'administrador') === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                        <option value="gerente" <?= old('rol_hotel') === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    </select>
                </div>

                <div>
                    <label for="nombre_completo" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" maxlength="150"
                           autocomplete="name"
                           value="<?= old('nombre_completo') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" id="email" name="email" maxlength="120"
                           autocomplete="email"
                           value="<?= old('email') ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña temporal</label>
                    <input type="password" id="password" name="password" minlength="10" autocomplete="new-password"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                </div>

                <div class="flex flex-col justify-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="es_principal" value="1" <?= old('es_principal') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Marcar como principal
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="activo" value="1" <?= old('activo', '1') ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Vínculo activo
                    </label>
                </div>
            </div>

            <div class="saas-detail-panel-footer">
                <p class="text-xs leading-relaxed" style="color:var(--ms-muted);">Podrás identificar al responsable principal en la lista superior.</p>
                <button type="submit" class="saas-detail-button is-primary">
                    <i class="fas fa-user-plus text-xs" aria-hidden="true"></i>
                    Crear o vincular administrador
                </button>
            </div>
        </form>
    </div>
    </section>

    <section id="branding" class="saas-detail-section" role="tabpanel" aria-labelledby="tab-branding" tabindex="0">
        <div class="saas-section-heading">
            <div class="saas-section-heading-main">
                <span class="saas-section-icon" aria-hidden="true"><i class="fas fa-palette"></i></span>
                <div class="min-w-0">
                    <h2>Marca del hotel</h2>
                    <p>Configura la identidad que verá el huésped y el equipo del hotel en el acceso y la aplicación instalable.</p>
                </div>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold" style="<?= $brandingActivo ? 'background:rgba(22,163,74,.12);color:var(--ms-success);' : 'background:rgba(100,116,139,.10);color:var(--ms-muted);' ?>">
                <?= $brandingActivo ? 'Marca activa' : 'Marca inactiva' ?>
            </span>
        </div>

    <?php
    // ── Marca del hotel: taller visual ────────────────────────────────────
    // Reemplaza al formulario plano anterior y recupera (mejorandolo) lo que
    // vivia en /configuracion → Marca: vista previa en vivo del sistema y del
    // login, selector de tema, y estado de cada archivo. Nuevo aqui: paletas
    // listas, aviso de contraste, colores tomados del logo, validacion de
    // medidas antes de subir y semaforo de completitud.
    $sbTemas = class_exists('HotelBranding') ? HotelBranding::temasDisponibles() : ['cupertino' => 'Cupertino'];
    $sbTemaActual = trim((string) ($brandingHotel['tema'] ?? ''));
    if (!array_key_exists($sbTemaActual, $sbTemas)) {
        $sbTemaActual = array_key_exists('cupertino', $sbTemas) ? 'cupertino' : (string) array_key_first($sbTemas);
    }
    $sbTemaNotas = [
        'deleite' => 'Boutique clásico: crema, tinta profunda y detalles dorados.',
        'cupertino' => 'Minimal premium: gris perla, tarjetas blancas y azul de acción.',
    ];
    $sbPrimario = trim((string) ($brandingHotel['color_primary'] ?? ''));
    $sbSecundario = trim((string) ($brandingHotel['color_secondary'] ?? ''));
    $sbAcento = trim((string) ($brandingHotel['color_accent'] ?? ''));
    $sbHex = function ($valor, $fallback) {
        $valor = strtoupper(trim((string) $valor));
        return preg_match('/^#[0-9A-F]{6}$/', $valor) ? $valor : $fallback;
    };
    $sbFondo = trim((string) ($fondoSistemaHotel ?? ''));
    $sbFondoModo = preg_match('/^#[0-9A-Fa-f]{6}$/', $sbFondo) ? 'custom' : 'default';
    $sbFondoValor = $sbFondoModo === 'custom' ? strtoupper($sbFondo) : '#F5F5F7';
    $sbSidebarStyle = (string) ($brandingHotel['sidebar_style'] ?? 'default');
    $sbLoginStyle = (string) ($brandingHotel['login_style'] ?? 'default');
    $sbInicial = strtoupper(mb_substr((string) $brandingNombrePreview, 0, 1, 'UTF-8'));

    // Semaforo de entrega: que le falta a esta marca para verse terminada.
    $sbChecklist = [
        ['clave' => 'nombre', 'label' => 'Nombre visual', 'ok' => trim((string) ($brandingHotel['nombre_visual'] ?? '')) !== ''],
        ['clave' => 'colores', 'label' => 'Colores de marca', 'ok' => $sbPrimario !== '' && $sbSecundario !== ''],
        ['clave' => 'logo', 'label' => 'Logo', 'ok' => !empty($brandingLogoPreview)],
        ['clave' => 'favicon', 'label' => 'Favicon', 'ok' => !empty($brandingFaviconPreview)],
        ['clave' => 'login', 'label' => 'Fondo de acceso', 'ok' => !empty($brandingLoginBgPreview)],
        ['clave' => 'pwa', 'label' => 'Íconos instalables', 'ok' => !empty($brandingPwaIcon192Preview) && !empty($brandingPwaIcon512Preview)],
    ];
    $sbListos = count(array_filter($sbChecklist, function ($item) { return $item['ok']; }));
    $sbTotal = count($sbChecklist);

    $sbArchivos = [
        [
            'campo' => 'logo', 'label' => 'Logo', 'input' => 'logo_file', 'url' => 'logo_url',
            'preview' => $brandingLogoPreview, 'accept' => '.png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp',
            'hint' => 'PNG, JPG o WebP · hasta 2 MB', 'max' => 2 * 1024 * 1024, 'w' => 0, 'h' => 0,
            'placeholder' => '/img/logo.png',
        ],
        [
            'campo' => 'favicon', 'label' => 'Favicon', 'input' => 'favicon_file', 'url' => 'favicon_url',
            'preview' => $brandingFaviconPreview, 'accept' => '.ico,.png,image/x-icon,image/vnd.microsoft.icon,image/png',
            'hint' => 'ICO o PNG · hasta 512 KB', 'max' => 512 * 1024, 'w' => 0, 'h' => 0,
            'placeholder' => '/img/favicon.png',
        ],
        [
            'campo' => 'login_bg', 'label' => 'Fondo de acceso', 'input' => 'login_background_file', 'url' => 'login_background_url',
            'preview' => $brandingLoginBgPreview, 'accept' => '.png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp',
            'hint' => 'PNG, JPG o WebP · hasta 4 MB', 'max' => 4 * 1024 * 1024, 'w' => 0, 'h' => 0,
            'placeholder' => '/uploads/branding/hotel/fondo.webp',
        ],
        [
            'campo' => 'pwa192', 'label' => 'Ícono instalable 192', 'input' => 'pwa_icon_192_file', 'url' => 'pwa_icon_192_url',
            'preview' => $brandingPwaIcon192Preview, 'accept' => '.png,.webp,image/png,image/webp',
            'hint' => 'PNG o WebP · exactamente 192×192 · hasta 1 MB', 'max' => 1024 * 1024, 'w' => 192, 'h' => 192,
            'placeholder' => '/uploads/branding/hotel/pwa-icons/icon-192.png',
        ],
        [
            'campo' => 'pwa512', 'label' => 'Ícono instalable 512', 'input' => 'pwa_icon_512_file', 'url' => 'pwa_icon_512_url',
            'preview' => $brandingPwaIcon512Preview, 'accept' => '.png,.webp,image/png,image/webp',
            'hint' => 'PNG o WebP · exactamente 512×512 · hasta 1 MB', 'max' => 1024 * 1024, 'w' => 512, 'h' => 512,
            'placeholder' => '/uploads/branding/hotel/pwa-icons/icon-512.png',
        ],
    ];
    ?>

    <style>
    .ms-admin-scope .sb-shell { display: grid; gap: 1rem; }
    .ms-admin-scope .sb-progress {
        display: flex; align-items: center; gap: .875rem; flex-wrap: wrap;
        padding: .875rem 1.125rem; border: 1px solid var(--ms-border); border-radius: .75rem;
        background: #FFFFFF; box-shadow: var(--ms-detail-shadow);
    }
    .ms-admin-scope .sb-progress-meter { display: flex; align-items: center; gap: .625rem; }
    .ms-admin-scope .sb-progress-ring {
        --sb-pct: 0;
        width: 44px; height: 44px; border-radius: 50%; flex: none;
        background: conic-gradient(var(--ms-primary) calc(var(--sb-pct) * 1%), rgba(148,163,184,.22) 0);
        display: grid; place-items: center;
    }
    .ms-admin-scope .sb-progress-ring span {
        width: 34px; height: 34px; border-radius: 50%; background: #FFFFFF;
        display: grid; place-items: center; font-size: .6875rem; font-weight: 700; color: var(--ms-text);
    }
    .ms-admin-scope .sb-progress-copy strong { display: block; font-size: .875rem; color: var(--ms-text); }
    .ms-admin-scope .sb-progress-copy small { color: var(--ms-muted); font-size: .75rem; }
    .ms-admin-scope .sb-flags { display: flex; gap: .375rem; flex-wrap: wrap; margin-left: auto; }
    .ms-admin-scope .sb-flag {
        display: inline-flex; align-items: center; gap: .375rem; padding: .3rem .625rem;
        border-radius: 999px; font-size: .75rem; font-weight: 600;
        background: rgba(148,163,184,.14); color: var(--ms-muted);
    }
    .ms-admin-scope .sb-flag.is-ok { background: rgba(22,163,74,.12); color: var(--ms-success); }

    .ms-admin-scope .sb-grid { display: grid; grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr); gap: 1.25rem; }
    @media (max-width: 1100px) { .ms-admin-scope .sb-grid { grid-template-columns: minmax(0, 1fr); } }
    .ms-admin-scope .sb-main { display: grid; gap: 1rem; min-width: 0; }
    .ms-admin-scope .sb-block {
        border: 1px solid var(--ms-border); border-radius: .75rem; background: #FFFFFF; overflow: hidden;
    }
    .ms-admin-scope .sb-block-head {
        display: flex; align-items: flex-start; gap: .625rem; padding: .875rem 1rem;
        border-bottom: 1px solid var(--ms-border); background: var(--ms-detail-soft);
    }
    .ms-admin-scope .sb-block-head i { color: var(--ms-primary); margin-top: .125rem; }
    .ms-admin-scope .sb-block-head h4 { margin: 0; font-size: .8125rem; font-weight: 700; color: var(--ms-text); }
    .ms-admin-scope .sb-block-head p { margin: .125rem 0 0; font-size: .75rem; color: var(--ms-muted); line-height: 1.45; }
    .ms-admin-scope .sb-block-body { padding: 1rem; display: grid; gap: .875rem; }
    .ms-admin-scope .sb-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .875rem; }
    @media (max-width: 700px) { .ms-admin-scope .sb-row { grid-template-columns: minmax(0, 1fr); } }
    .ms-admin-scope .sb-label { display: block; font-size: .75rem; font-weight: 650; color: var(--ms-text); margin-bottom: .3rem; }
    .ms-admin-scope .sb-input {
        width: 100%; border: 1px solid #d1d5db; border-radius: .5rem; padding: .5rem .625rem;
        font-size: .8125rem; color: var(--ms-text); background: #FFFFFF;
    }
    .ms-admin-scope .sb-input:focus { outline: 2px solid rgba(37,99,235,.35); outline-offset: 1px; border-color: var(--ms-primary); }
    .ms-admin-scope .sb-hint { margin: .3rem 0 0; font-size: .6875rem; color: var(--ms-muted); line-height: 1.45; }

    .ms-admin-scope .sb-color { display: flex; align-items: center; gap: .5rem; }
    .ms-admin-scope .sb-color input[type="color"] {
        width: 42px; height: 38px; padding: 0; border: 1px solid #d1d5db; border-radius: .5rem;
        background: #FFFFFF; cursor: pointer; flex: none;
    }
    .ms-admin-scope .sb-color .sb-input { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; text-transform: uppercase; }
    .ms-admin-scope .sb-contrast {
        display: inline-flex; align-items: center; gap: .375rem; margin-top: .375rem;
        font-size: .6875rem; font-weight: 600; padding: .2rem .5rem; border-radius: 999px;
        background: rgba(148,163,184,.14); color: var(--ms-muted);
    }
    .ms-admin-scope .sb-contrast.is-ok { background: rgba(22,163,74,.12); color: var(--ms-success); }
    .ms-admin-scope .sb-contrast.is-warn { background: rgba(245,158,11,.16); color: #92400e; }

    .ms-admin-scope .sb-palettes { display: flex; gap: .5rem; flex-wrap: wrap; }
    .ms-admin-scope .sb-palette {
        display: inline-flex; align-items: center; gap: .5rem; padding: .375rem .625rem;
        border: 1px solid var(--ms-border); border-radius: .625rem; background: #FFFFFF;
        font-size: .75rem; font-weight: 600; color: var(--ms-text); cursor: pointer;
    }
    .ms-admin-scope .sb-palette:hover { border-color: var(--ms-detail-blue-border); background: var(--ms-detail-blue-soft); }
    .ms-admin-scope .sb-palette-dots { display: inline-flex; }
    .ms-admin-scope .sb-palette-dots i {
        width: 13px; height: 13px; border-radius: 50%; display: inline-block;
        box-shadow: 0 0 0 1.5px #FFFFFF; margin-left: -4px;
    }
    .ms-admin-scope .sb-palette-dots i:first-child { margin-left: 0; }

    .ms-admin-scope .sb-seg { display: flex; gap: .25rem; padding: .25rem; border: 1px solid var(--ms-border); border-radius: .625rem; background: var(--ms-detail-soft); }
    .ms-admin-scope .sb-seg label {
        flex: 1 1 0; text-align: center; padding: .4rem .5rem; border-radius: .5rem;
        font-size: .75rem; font-weight: 650; color: var(--ms-muted); cursor: pointer;
    }
    .ms-admin-scope .sb-seg input { position: absolute; opacity: 0; pointer-events: none; }
    .ms-admin-scope .sb-seg input:checked + span { color: var(--ms-primary); }
    .ms-admin-scope .sb-seg label:has(input:checked) { background: #FFFFFF; color: var(--ms-primary); box-shadow: 0 1px 2px rgba(15,23,42,.08); }
    .ms-admin-scope .sb-seg label:focus-within { outline: 2px solid rgba(37,99,235,.35); outline-offset: 1px; }

    .ms-admin-scope .sb-themes { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: .625rem; }
    .ms-admin-scope .sb-theme {
        position: relative; border: 1px solid var(--ms-border); border-radius: .75rem; padding: .75rem;
        cursor: pointer; background: #FFFFFF; display: grid; gap: .375rem;
    }
    .ms-admin-scope .sb-theme:has(input:checked) { border-color: var(--ms-primary); box-shadow: inset 0 0 0 1px var(--ms-detail-blue-border); background: var(--ms-detail-blue-soft); }
    .ms-admin-scope .sb-theme input { position: absolute; opacity: 0; pointer-events: none; }
    .ms-admin-scope .sb-theme strong { font-size: .8125rem; color: var(--ms-text); }
    .ms-admin-scope .sb-theme small { font-size: .6875rem; color: var(--ms-muted); line-height: 1.4; }

    .ms-admin-scope .sb-files { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: .75rem; }
    .ms-admin-scope .sb-file {
        border: 1px solid var(--ms-border); border-radius: .75rem; padding: .75rem; background: #FFFFFF;
        display: grid; grid-template-columns: 52px minmax(0, 1fr); gap: .75rem; align-items: start;
    }
    .ms-admin-scope .sb-file-thumb {
        width: 52px; height: 52px; border-radius: .625rem; border: 1px solid var(--ms-border);
        background: var(--ms-detail-soft) center/cover no-repeat; display: grid; place-items: center;
        color: var(--ms-muted); font-size: .875rem; overflow: hidden;
    }
    .ms-admin-scope .sb-file-thumb img { width: 100%; height: 100%; object-fit: contain; }
    .ms-admin-scope .sb-file-name { font-size: .75rem; font-weight: 700; color: var(--ms-text); }
    .ms-admin-scope .sb-file-state { font-size: .6875rem; color: var(--ms-muted); margin-top: .125rem; }
    .ms-admin-scope .sb-file-state.is-error { color: #b91c1c; font-weight: 600; }
    .ms-admin-scope .sb-file-state.is-ready { color: var(--ms-success); font-weight: 600; }
    .ms-admin-scope .sb-file input[type="file"] { margin-top: .5rem; width: 100%; font-size: .6875rem; color: var(--ms-muted); }
    .ms-admin-scope .sb-file input[type="file"]::file-selector-button {
        margin-right: .5rem; border: 0; border-radius: .375rem; padding: .3rem .6rem;
        background: var(--ms-primary); color: #FFFFFF; font-size: .6875rem; font-weight: 600; cursor: pointer;
    }
    .ms-admin-scope .sb-file .sb-input { margin-top: .5rem; font-size: .6875rem; }
    .ms-admin-scope .sb-file-clear {
        margin-top: .375rem; background: none; border: 0; padding: 0; cursor: pointer;
        font-size: .6875rem; font-weight: 600; color: var(--ms-muted); text-decoration: underline;
    }

    .ms-admin-scope .sb-side { min-width: 0; }
    .ms-admin-scope .sb-preview-wrap { position: sticky; top: 5.5rem; display: grid; gap: .75rem; }
    .ms-admin-scope .sb-preview {
        --sb-primary: #1B2746; --sb-secondary: #0F172A; --sb-accent: #BD9441; --sb-bg: #F5F5F7; --sb-on-primary: #FFFFFF;
        border: 1px solid var(--ms-border); border-radius: .875rem; overflow: hidden; background: #FFFFFF;
        box-shadow: var(--ms-detail-shadow);
    }
    .ms-admin-scope .sb-preview-bar {
        display: flex; align-items: center; gap: .5rem; padding: .625rem .75rem;
        border-bottom: 1px solid var(--ms-border); background: var(--ms-detail-soft);
    }
    .ms-admin-scope .sb-preview-tabs { display: flex; gap: .25rem; }
    .ms-admin-scope .sb-preview-tab {
        border: 0; background: none; padding: .3rem .625rem; border-radius: .5rem;
        font-size: .6875rem; font-weight: 650; color: var(--ms-muted); cursor: pointer;
    }
    .ms-admin-scope .sb-preview-tab.is-active { background: #FFFFFF; color: var(--ms-primary); box-shadow: 0 1px 2px rgba(15,23,42,.08); }
    .ms-admin-scope .sb-dirty { margin-left: auto; font-size: .6875rem; font-weight: 600; color: var(--ms-muted); }
    .ms-admin-scope .sb-dirty.is-dirty { color: #92400e; }
    .ms-admin-scope .sb-stage { padding: .75rem; background: var(--sb-bg); min-height: 250px; }
    .ms-admin-scope .sb-view[hidden] { display: none; }

    .ms-admin-scope .sb-app { display: grid; grid-template-columns: 82px minmax(0, 1fr); gap: .5rem; }
    .ms-admin-scope .sb-app-side {
        background: var(--sb-primary); color: var(--sb-on-primary); border-radius: .625rem;
        padding: .625rem .5rem; display: grid; gap: .5rem; align-content: start;
    }
    .ms-admin-scope .sb-app-brand { display: grid; gap: .375rem; justify-items: center; text-align: center; }
    .ms-admin-scope .sb-app-mark {
        width: 30px; height: 30px; border-radius: 50%; background: rgba(255,255,255,.18);
        display: grid; place-items: center; overflow: hidden; font-size: .75rem; font-weight: 700;
    }
    .ms-admin-scope .sb-app-mark img { width: 100%; height: 100%; object-fit: contain; }
    .ms-admin-scope .sb-app-name { font-size: .5625rem; font-weight: 700; line-height: 1.2; word-break: break-word; }
    .ms-admin-scope .sb-app-nav { display: grid; gap: .25rem; }
    .ms-admin-scope .sb-app-nav span {
        display: flex; align-items: center; gap: .3rem; font-size: .5625rem; padding: .25rem .3rem;
        border-radius: .375rem; opacity: .78;
    }
    .ms-admin-scope .sb-app-nav span.is-active { background: rgba(255,255,255,.16); opacity: 1; font-weight: 700; }
    .ms-admin-scope .sb-app-body { display: grid; gap: .5rem; align-content: start; }
    .ms-admin-scope .sb-app-top {
        background: #FFFFFF; border: 1px solid rgba(15,23,42,.08); border-radius: .625rem;
        padding: .5rem .625rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem;
    }
    .ms-admin-scope .sb-app-top strong { font-size: .6875rem; color: #0f172a; }
    .ms-admin-scope .sb-app-chip {
        font-size: .5625rem; font-weight: 700; padding: .2rem .45rem; border-radius: 999px;
        background: var(--sb-accent); color: #1f2937;
    }
    .ms-admin-scope .sb-app-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .375rem; }
    .ms-admin-scope .sb-app-metric {
        background: #FFFFFF; border: 1px solid rgba(15,23,42,.08); border-radius: .5rem;
        padding: .375rem; text-align: center;
    }
    .ms-admin-scope .sb-app-metric b { display: block; font-size: .8125rem; color: var(--sb-primary); }
    .ms-admin-scope .sb-app-metric span { font-size: .5rem; color: #64748b; }
    .ms-admin-scope .sb-app-cta {
        background: var(--sb-secondary); color: #FFFFFF; border-radius: .5rem; padding: .4rem .625rem;
        font-size: .625rem; font-weight: 700; text-align: center;
    }

    .ms-admin-scope .sb-login {
        border-radius: .625rem; min-height: 226px; display: grid; place-items: center; padding: 1rem;
        background: linear-gradient(135deg, var(--sb-primary), var(--sb-secondary));
        background-size: cover; background-position: center;
    }
    .ms-admin-scope .sb-login-card {
        width: 100%; max-width: 220px; background: rgba(255,255,255,.96); border-radius: .75rem;
        padding: .875rem; text-align: center; box-shadow: 0 12px 30px rgba(15,23,42,.22);
    }
    .ms-admin-scope .sb-login-mark {
        width: 40px; height: 40px; border-radius: 50%; margin: 0 auto .5rem; overflow: hidden;
        background: var(--sb-primary); color: var(--sb-on-primary); display: grid; place-items: center;
        font-weight: 700; font-size: .875rem;
    }
    .ms-admin-scope .sb-login-mark img { width: 100%; height: 100%; object-fit: contain; background: #FFFFFF; }
    .ms-admin-scope .sb-login-card strong { display: block; font-size: .75rem; color: #0f172a; }
    .ms-admin-scope .sb-login-field { height: 24px; border-radius: .375rem; background: rgba(15,23,42,.07); margin-top: .5rem; }
    .ms-admin-scope .sb-login-btn {
        margin-top: .5rem; border-radius: .375rem; padding: .35rem; font-size: .625rem; font-weight: 700;
        background: var(--sb-primary); color: var(--sb-on-primary);
    }

    .ms-admin-scope .sb-install { display: grid; gap: .625rem; justify-items: center; padding: .5rem 0; }
    .ms-admin-scope .sb-install-row { display: flex; gap: .875rem; }
    .ms-admin-scope .sb-install-item { display: grid; gap: .3rem; justify-items: center; font-size: .5625rem; color: #475569; }
    .ms-admin-scope .sb-install-icon {
        width: 46px; height: 46px; border-radius: 12px; overflow: hidden; background: var(--sb-primary);
        color: var(--sb-on-primary); display: grid; place-items: center; font-weight: 700;
        box-shadow: 0 6px 14px rgba(15,23,42,.18);
    }
    .ms-admin-scope .sb-install-icon img { width: 100%; height: 100%; object-fit: cover; }
    .ms-admin-scope .sb-install-tab {
        display: flex; align-items: center; gap: .375rem; background: #FFFFFF; border: 1px solid rgba(15,23,42,.1);
        border-radius: .5rem .5rem 0 0; padding: .3rem .5rem; font-size: .5625rem; color: #475569; min-width: 132px;
    }
    .ms-admin-scope .sb-install-fav { width: 14px; height: 14px; border-radius: 3px; background: var(--sb-accent); overflow: hidden; }
    .ms-admin-scope .sb-install-fav img { width: 100%; height: 100%; object-fit: contain; }
    .ms-admin-scope .sb-note {
        border: 1px solid var(--ms-border); border-radius: .625rem; padding: .625rem .75rem;
        font-size: .6875rem; color: var(--ms-muted); background: #FFFFFF; line-height: 1.5;
    }
    </style>

    <div class="sb-shell">
        <div class="sb-progress">
            <div class="sb-progress-meter">
                <div class="sb-progress-ring" data-sb-ring style="--sb-pct: <?= $sbTotal > 0 ? (int) round($sbListos * 100 / $sbTotal) : 0 ?>">
                    <span data-sb-ring-text><?= (int) $sbListos ?>/<?= (int) $sbTotal ?></span>
                </div>
                <div class="sb-progress-copy">
                    <strong>Marca <?= $sbListos === $sbTotal ? 'lista para entregar' : 'en preparación' ?></strong>
                    <small><?= $sbListos === $sbTotal ? 'Este hotel ya tiene todo lo que se ve en el acceso y en la app.' : 'Completa lo que falta antes de entregar el sistema al hotel.' ?></small>
                </div>
            </div>
            <div class="sb-flags">
                <?php foreach ($sbChecklist as $item): ?>
                    <span class="sb-flag<?= $item['ok'] ? ' is-ok' : '' ?>" data-sb-flag="<?= htmlspecialchars($item['clave'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas <?= $item['ok'] ? 'fa-circle-check' : 'fa-circle-dashed' ?>" aria-hidden="true"></i>
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="saas-detail-panel overflow-hidden">
            <form method="POST"
                  action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/branding') ?>"
                  enctype="multipart/form-data"
                  id="sbForm"
                  data-sb-form
                  data-sb-base="<?= htmlspecialchars(rtrim((string) url(''), '/'), ENT_QUOTES, 'UTF-8') ?>">
                <?= csrf_field() ?>

                <div class="p-5 sb-grid">
                    <div class="sb-main">
                        <section class="sb-block">
                            <div class="sb-block-head">
                                <i class="fas fa-signature" aria-hidden="true"></i>
                                <div>
                                    <h4>Identidad</h4>
                                    <p>El nombre y el tema con los que el hotel ve su propio sistema.</p>
                                </div>
                            </div>
                            <div class="sb-block-body">
                                <div>
                                    <label class="sb-label" for="nombre_visual">Nombre visual</label>
                                    <input type="text" id="nombre_visual" name="nombre_visual" maxlength="150"
                                           class="sb-input" data-sb-name
                                           value="<?= $brandingCampo('nombre_visual', $hotel['nombre'] ?? '') ?>"
                                           placeholder="<?= htmlspecialchars((string) ($hotel['nombre'] ?? 'Hotel'), ENT_QUOTES, 'UTF-8') ?>">
                                    <p class="sb-hint">Aparece en el menú, en el acceso y en el nombre de la app instalada.</p>
                                </div>

                                <div>
                                    <span class="sb-label">Tema base</span>
                                    <div class="sb-themes">
                                        <?php foreach ($sbTemas as $slug => $nombreTema): ?>
                                            <label class="sb-theme">
                                                <input type="radio" name="tema" value="<?= htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') ?>"
                                                       <?= $sbTemaActual === $slug ? 'checked' : '' ?>>
                                                <strong><?= htmlspecialchars((string) $nombreTema, ENT_QUOTES, 'UTF-8') ?></strong>
                                                <small><?= htmlspecialchars($sbTemaNotas[$slug] ?? 'Estilo visual del sistema.', ENT_QUOTES, 'UTF-8') ?></small>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="sb-hint">Define la piel completa del sistema. Los colores de abajo se aplican encima.</p>
                                </div>
                            </div>
                        </section>

                        <section class="sb-block">
                            <div class="sb-block-head">
                                <i class="fas fa-droplet" aria-hidden="true"></i>
                                <div>
                                    <h4>Colores</h4>
                                    <p>Se escriben como variables CSS sanitizadas. Deja un campo vacío para usar el color del tema.</p>
                                </div>
                            </div>
                            <div class="sb-block-body">
                                <div class="sb-palettes" aria-label="Paletas listas">
                                    <?php
                                    $sbPaletas = [
                                        ['nombre' => 'Boutique', 'p' => '#1B2746', 's' => '#0F172A', 'a' => '#BD9441'],
                                        ['nombre' => 'Playa', 'p' => '#0F766E', 's' => '#115E59', 'a' => '#F59E0B'],
                                        ['nombre' => 'Colonial', 'p' => '#7C2D12', 's' => '#9A3412', 'a' => '#D6A756'],
                                        ['nombre' => 'Bosque', 'p' => '#14532D', 's' => '#166534', 'a' => '#A3B18A'],
                                        ['nombre' => 'Urbano', 'p' => '#1F2937', 's' => '#111827', 'a' => '#38BDF8'],
                                        ['nombre' => 'Lavanda', 'p' => '#4C1D95', 's' => '#5B21B6', 'a' => '#C4B5FD'],
                                    ];
                                    foreach ($sbPaletas as $paleta): ?>
                                        <button type="button" class="sb-palette"
                                                data-sb-palette="<?= htmlspecialchars($paleta['p'] . '|' . $paleta['s'] . '|' . $paleta['a'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="sb-palette-dots">
                                                <i style="background:<?= $paleta['p'] ?>"></i>
                                                <i style="background:<?= $paleta['s'] ?>"></i>
                                                <i style="background:<?= $paleta['a'] ?>"></i>
                                            </span>
                                            <?= htmlspecialchars($paleta['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        </button>
                                    <?php endforeach; ?>
                                    <button type="button" class="sb-palette" data-sb-from-logo hidden>
                                        <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                                        Tomar del logo
                                    </button>
                                </div>

                                <div class="sb-row">
                                    <div>
                                        <label class="sb-label" for="color_primary">Color principal</label>
                                        <div class="sb-color">
                                            <input type="color" aria-label="Elegir color principal"
                                                   data-sb-swatch="primary"
                                                   value="<?= htmlspecialchars($sbHex($sbPrimario, '#1B2746'), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="text" id="color_primary" name="color_primary" class="sb-input"
                                                   data-sb-hex="primary" maxlength="7" placeholder="#1B2746"
                                                   value="<?= $brandingCampo('color_primary') ?>">
                                        </div>
                                        <span class="sb-contrast" data-sb-contrast="primary"></span>
                                    </div>
                                    <div>
                                        <label class="sb-label" for="color_secondary">Color secundario</label>
                                        <div class="sb-color">
                                            <input type="color" aria-label="Elegir color secundario"
                                                   data-sb-swatch="secondary"
                                                   value="<?= htmlspecialchars($sbHex($sbSecundario, '#0F172A'), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="text" id="color_secondary" name="color_secondary" class="sb-input"
                                                   data-sb-hex="secondary" maxlength="7" placeholder="#0F172A"
                                                   value="<?= $brandingCampo('color_secondary') ?>">
                                        </div>
                                        <span class="sb-contrast" data-sb-contrast="secondary"></span>
                                    </div>
                                    <div>
                                        <label class="sb-label" for="color_accent">Color de acento</label>
                                        <div class="sb-color">
                                            <input type="color" aria-label="Elegir color de acento"
                                                   data-sb-swatch="accent"
                                                   value="<?= htmlspecialchars($sbHex($sbAcento, '#BD9441'), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="text" id="color_accent" name="color_accent" class="sb-input"
                                                   data-sb-hex="accent" maxlength="7" placeholder="#BD9441"
                                                   value="<?= $brandingCampo('color_accent') ?>">
                                        </div>
                                        <p class="sb-hint">Detalles, chips y estados destacados.</p>
                                    </div>
                                    <div>
                                        <label class="sb-label" for="fondo_sistema">Fondo del sistema</label>
                                        <input type="hidden" name="hotel_appearance[background_mode]" id="fondo_sistema_modo"
                                               value="<?= htmlspecialchars($sbFondoModo, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="sb-color">
                                            <input type="color" id="fondo_sistema" name="hotel_appearance[background_color]"
                                                   data-sb-background
                                                   value="<?= htmlspecialchars($sbFondoValor, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="button" class="sb-palette" data-sb-background-default>Usar el del tema</button>
                                        </div>
                                        <p class="sb-hint" data-sb-background-state><?= $sbFondoModo === 'custom' ? 'Personalizado para este hotel.' : 'Predeterminado del tema.' ?></p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="sb-block">
                            <div class="sb-block-head">
                                <i class="fas fa-table-columns" aria-hidden="true"></i>
                                <div>
                                    <h4>Menú y acceso</h4>
                                    <p>Cómo se ve la barra lateral del sistema y la pantalla de entrada del hotel.</p>
                                </div>
                            </div>
                            <div class="sb-block-body">
                                <div class="sb-row">
                                    <div>
                                        <span class="sb-label">Estilo del menú</span>
                                        <div class="sb-seg" data-sb-seg>
                                            <?php foreach (['default' => 'Default', 'solid' => 'Sólido', 'dark' => 'Oscuro'] as $valor => $etiqueta): ?>
                                                <label>
                                                    <input type="radio" name="sidebar_style" value="<?= $valor ?>" data-sb-sidebar
                                                           <?= $sbSidebarStyle === $valor ? 'checked' : '' ?>>
                                                    <span><?= $etiqueta ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="sb-label">Estilo del acceso</span>
                                        <div class="sb-seg" data-sb-seg>
                                            <?php foreach (['default' => 'Default', 'soft' => 'Suave', 'image' => 'Con imagen'] as $valor => $etiqueta): ?>
                                                <label>
                                                    <input type="radio" name="login_style" value="<?= $valor ?>" data-sb-login
                                                           <?= $sbLoginStyle === $valor ? 'checked' : '' ?>>
                                                    <span><?= $etiqueta ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="sb-hint">"Con imagen" necesita un fondo de acceso cargado.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="sb-block">
                            <div class="sb-block-head">
                                <i class="fas fa-images" aria-hidden="true"></i>
                                <div>
                                    <h4>Imágenes</h4>
                                    <p>Se revisan el peso y las medidas antes de enviar. La ruta manual sigue disponible por si el archivo ya está en el servidor.</p>
                                </div>
                            </div>
                            <div class="sb-block-body">
                                <div class="sb-files">
                                    <?php foreach ($sbArchivos as $archivo): ?>
                                        <div class="sb-file" data-sb-file="<?= htmlspecialchars($archivo['campo'], ENT_QUOTES, 'UTF-8') ?>">
                                            <span class="sb-file-thumb" data-sb-thumb>
                                                <?php if (!empty($archivo['preview'])): ?>
                                                    <img src="<?= htmlspecialchars($archivo['preview'], ENT_QUOTES, 'UTF-8') ?>" alt="">
                                                <?php else: ?>
                                                    <i class="fas fa-image" aria-hidden="true"></i>
                                                <?php endif; ?>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="sb-file-name"><?= htmlspecialchars($archivo['label'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="sb-file-state" data-sb-file-state
                                                     data-default="<?= !empty($archivo['preview']) ? 'Cargado' : 'Sin archivo' ?>">
                                                    <?= !empty($archivo['preview']) ? 'Cargado' : 'Sin archivo' ?>
                                                </div>
                                                <input type="file"
                                                       name="<?= htmlspecialchars($archivo['input'], ENT_QUOTES, 'UTF-8') ?>"
                                                       accept="<?= htmlspecialchars($archivo['accept'], ENT_QUOTES, 'UTF-8') ?>"
                                                       data-sb-file-input
                                                       data-max="<?= (int) $archivo['max'] ?>"
                                                       data-w="<?= (int) $archivo['w'] ?>"
                                                       data-h="<?= (int) $archivo['h'] ?>">
                                                <input type="text"
                                                       name="<?= htmlspecialchars($archivo['url'], ENT_QUOTES, 'UTF-8') ?>"
                                                       class="sb-input"
                                                       data-sb-file-url
                                                       value="<?= $brandingCampo($archivo['url']) ?>"
                                                       placeholder="<?= htmlspecialchars($archivo['placeholder'], ENT_QUOTES, 'UTF-8') ?>">
                                                <p class="sb-hint"><?= htmlspecialchars($archivo['hint'], ENT_QUOTES, 'UTF-8') ?></p>
                                                <?php if (!empty($archivo['preview'])): ?>
                                                    <button type="button" class="sb-file-clear" data-sb-file-clear>Quitar esta imagen</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <label class="flex items-center gap-2 text-sm" style="color:var(--ms-text);">
                                    <input type="checkbox" name="activo" value="1" <?= $brandingActivo ? 'checked' : '' ?>
                                           class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    Marca activa (si se apaga, el hotel vuelve a la identidad Medisoft)
                                </label>
                            </div>
                        </section>
                    </div>

                    <aside class="sb-side" aria-label="Vista previa de la marca">
                        <div class="sb-preview-wrap">
                            <div class="sb-preview" data-sb-preview>
                                <div class="sb-preview-bar">
                                    <div class="sb-preview-tabs" role="tablist" aria-label="Qué previsualizar">
                                        <button type="button" class="sb-preview-tab is-active" data-sb-view="sistema">Sistema</button>
                                        <button type="button" class="sb-preview-tab" data-sb-view="acceso">Acceso</button>
                                        <button type="button" class="sb-preview-tab" data-sb-view="app">App</button>
                                    </div>
                                    <span class="sb-dirty" data-sb-dirty>Sin cambios</span>
                                </div>

                                <div class="sb-stage">
                                    <div class="sb-view" data-sb-view-panel="sistema">
                                        <div class="sb-app">
                                            <div class="sb-app-side">
                                                <div class="sb-app-brand">
                                                    <span class="sb-app-mark" data-sb-mark>
                                                        <?php if (!empty($brandingLogoPreview)): ?>
                                                            <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="" data-sb-logo>
                                                        <?php else: ?>
                                                            <span data-sb-initial><?= htmlspecialchars($sbInicial, ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="sb-app-name" data-sb-name-out><?= htmlspecialchars((string) $brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?></span>
                                                </div>
                                                <div class="sb-app-nav">
                                                    <span class="is-active"><i class="fas fa-compass"></i> Inicio</span>
                                                    <span><i class="fas fa-bed"></i> Cuartos</span>
                                                    <span><i class="fas fa-wallet"></i> Caja</span>
                                                </div>
                                            </div>
                                            <div class="sb-app-body">
                                                <div class="sb-app-top">
                                                    <strong>Operación de hoy</strong>
                                                    <span class="sb-app-chip">Recepción</span>
                                                </div>
                                                <div class="sb-app-metrics">
                                                    <div class="sb-app-metric"><b>18</b><span>Libres</span></div>
                                                    <div class="sb-app-metric"><b>7</b><span>Ocupadas</span></div>
                                                    <div class="sb-app-metric"><b>3</b><span>Llegadas</span></div>
                                                </div>
                                                <div class="sb-app-cta">Registrar llegada</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sb-view" data-sb-view-panel="acceso" hidden>
                                        <div class="sb-login" data-sb-login>
                                            <div class="sb-login-card">
                                                <span class="sb-login-mark" data-sb-mark>
                                                    <?php if (!empty($brandingLogoPreview)): ?>
                                                        <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="" data-sb-logo>
                                                    <?php else: ?>
                                                        <span data-sb-initial><?= htmlspecialchars($sbInicial, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </span>
                                                <strong data-sb-name-out><?= htmlspecialchars((string) $brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?></strong>
                                                <div class="sb-login-field"></div>
                                                <div class="sb-login-field"></div>
                                                <div class="sb-login-btn">Entrar</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="sb-view" data-sb-view-panel="app" hidden>
                                        <div class="sb-install">
                                            <div class="sb-install-row">
                                                <div class="sb-install-item">
                                                    <span class="sb-install-icon" data-sb-install="192">
                                                        <?php if (!empty($brandingPwaIcon192Preview)): ?>
                                                            <img src="<?= htmlspecialchars($brandingPwaIcon192Preview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                                        <?php else: ?>
                                                            <span data-sb-initial><?= htmlspecialchars($sbInicial, ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    En el teléfono
                                                </div>
                                                <div class="sb-install-item">
                                                    <span class="sb-install-icon" data-sb-install="512">
                                                        <?php if (!empty($brandingPwaIcon512Preview)): ?>
                                                            <img src="<?= htmlspecialchars($brandingPwaIcon512Preview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                                        <?php else: ?>
                                                            <span data-sb-initial><?= htmlspecialchars($sbInicial, ENT_QUOTES, 'UTF-8') ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                    Pantalla de inicio
                                                </div>
                                            </div>
                                            <div class="sb-install-tab">
                                                <span class="sb-install-fav" data-sb-favicon>
                                                    <?php if (!empty($brandingFaviconPreview)): ?>
                                                        <img src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                                    <?php endif; ?>
                                                </span>
                                                <span data-sb-name-out><?= htmlspecialchars((string) $brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                            <p class="sb-hint" style="text-align:center;">El manifest usa los íconos del hotel solo cuando existen 192 y 512 válidos.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p class="sb-note">
                                Es una maqueta: refleja colores, logo y nombre, no el diseño exacto de cada pantalla.
                                <?php if ($hotelLoginUrl): ?>
                                    Para verlo real, abre <a href="<?= htmlspecialchars($hotelLoginUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="color:var(--ms-primary);font-weight:600;">el acceso del hotel</a> en una ventana privada.
                                <?php endif; ?>
                            </p>
                        </div>
                    </aside>
                </div>

                <div class="saas-detail-panel-footer">
                    <p class="text-xs leading-relaxed" style="color:var(--ms-muted);">
                        Los cambios se aplican únicamente a la experiencia visual de este hotel.
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" class="saas-detail-button" data-sb-reset disabled>
                            <i class="fas fa-rotate-left text-xs" aria-hidden="true"></i>
                            Restablecer
                        </button>
                        <button type="submit" class="saas-detail-button is-primary">
                            <i class="fas fa-floppy-disk text-xs" aria-hidden="true"></i>
                            Guardar marca
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Taller de marca del hotel: previsualiza en vivo, avisa de contraste y
    // revisa peso/medidas de cada imagen ANTES de enviar (el servidor vuelve a
    // validar; esto solo evita el viaje perdido).
    (function () {
        var form = document.querySelector('[data-sb-form]');
        if (!form) { return; }

        var preview = form.querySelector('[data-sb-preview]');
        var dirtyLabel = form.querySelector('[data-sb-dirty]');
        var resetBtn = form.querySelector('[data-sb-reset]');
        var nameInput = form.querySelector('[data-sb-name]');
        var backgroundInput = form.querySelector('[data-sb-background]');
        var backgroundMode = document.getElementById('fondo_sistema_modo');
        var backgroundState = form.querySelector('[data-sb-background-state]');
        var loginStage = form.querySelector('[data-sb-login]');
        var fromLogoBtn = form.querySelector('[data-sb-from-logo]');

        var DEFAULTS = { primary: '#1B2746', secondary: '#0F172A', accent: '#BD9441' };
        var estadoInicial = new FormData(form);
        var urlsIniciales = {};

        function normalizarHex(valor) {
            valor = String(valor || '').trim();
            if (valor === '') { return ''; }
            if (valor.charAt(0) !== '#') { valor = '#' + valor; }
            if (/^#[0-9a-fA-F]{3}$/.test(valor)) {
                valor = '#' + valor.charAt(1) + valor.charAt(1) + valor.charAt(2)
                      + valor.charAt(2) + valor.charAt(3) + valor.charAt(3);
            }
            return /^#[0-9a-fA-F]{6}$/.test(valor) ? valor.toUpperCase() : '';
        }

        function luminancia(hex) {
            var r = parseInt(hex.substr(1, 2), 16) / 255;
            var g = parseInt(hex.substr(3, 2), 16) / 255;
            var b = parseInt(hex.substr(5, 2), 16) / 255;
            var canal = function (c) { return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4); };
            return 0.2126 * canal(r) + 0.7152 * canal(g) + 0.0722 * canal(b);
        }

        function contrasteConBlanco(hex) {
            return (1.05) / (luminancia(hex) + 0.05);
        }

        function mezclar(hexA, hexB, proporcion) {
            var canal = function (hex, desde) { return parseInt(hex.substr(desde, 2), 16); };
            var mezcla = [1, 3, 5].map(function (desde) {
                var valor = Math.round(canal(hexA, desde) * (1 - proporcion) + canal(hexB, desde) * proporcion);
                return ('0' + Math.max(0, Math.min(255, valor)).toString(16)).slice(-2);
            });
            return ('#' + mezcla.join('')).toUpperCase();
        }

        function colorDe(clave) {
            var campo = form.querySelector('[data-sb-hex="' + clave + '"]');
            return normalizarHex(campo ? campo.value : '') || DEFAULTS[clave];
        }

        function pintarContraste(clave) {
            var chip = form.querySelector('[data-sb-contrast="' + clave + '"]');
            if (!chip) { return; }

            var hex = colorDe(clave);
            var ratio = contrasteConBlanco(hex);
            chip.className = 'sb-contrast ' + (ratio >= 4.5 ? 'is-ok' : 'is-warn');
            chip.textContent = ratio >= 4.5
                ? 'Texto blanco legible encima (' + ratio.toFixed(1) + ':1)'
                : 'Muy claro: el texto blanco encima se lee mal (' + ratio.toFixed(1) + ':1)';
        }

        function aplicarColores() {
            if (!preview) { return; }

            var primario = colorDe('primary');
            preview.style.setProperty('--sb-primary', primario);
            preview.style.setProperty('--sb-secondary', colorDe('secondary'));
            preview.style.setProperty('--sb-accent', colorDe('accent'));
            preview.style.setProperty('--sb-on-primary', contrasteConBlanco(primario) >= 3 ? '#FFFFFF' : '#111827');

            var modo = backgroundMode ? backgroundMode.value : 'default';
            var fondo = modo === 'custom' && backgroundInput ? normalizarHex(backgroundInput.value) : '';
            preview.style.setProperty('--sb-bg', fondo || '#F5F5F7');

            pintarContraste('primary');
            pintarContraste('secondary');
        }

        function aplicarNombre() {
            var visual = nameInput && nameInput.value.trim() !== ''
                ? nameInput.value.trim()
                : (nameInput ? nameInput.placeholder : 'Hotel');

            form.querySelectorAll('[data-sb-name-out]').forEach(function (nodo) {
                nodo.textContent = visual;
            });
            form.querySelectorAll('[data-sb-initial]').forEach(function (nodo) {
                nodo.textContent = visual.charAt(0).toUpperCase();
            });
        }

        function aplicarLogin() {
            if (!loginStage) { return; }

            var estilo = (form.querySelector('[data-sb-login-style]:checked') || {}).value
                || (form.querySelector('input[name="login_style"]:checked') || {}).value
                || 'default';
            var fondo = urlActual('login_bg');

            if (estilo === 'image' && fondo) {
                loginStage.style.backgroundImage = 'linear-gradient(rgba(15,23,42,.45), rgba(15,23,42,.45)), url("' + fondo + '")';
            } else if (estilo === 'soft') {
                loginStage.style.backgroundImage = 'linear-gradient(135deg, color-mix(in srgb, var(--sb-primary) 22%, #FFFFFF), color-mix(in srgb, var(--sb-secondary) 14%, #FFFFFF))';
            } else {
                loginStage.style.backgroundImage = 'linear-gradient(135deg, var(--sb-primary), var(--sb-secondary))';
            }
        }

        // ── Imagenes ──────────────────────────────────────────────────────
        var tarjetas = Array.prototype.slice.call(form.querySelectorAll('[data-sb-file]'));

        // Las rutas guardadas son relativas a la raiz publica ("uploads/..."):
        // desde /admin/saas/hoteles/{id} el navegador las resolveria contra esa
        // carpeta y la imagen saldria rota. Se anclan a la base de la app.
        function resolverUrl(valor) {
            valor = String(valor || '').trim();
            if (valor === '') { return ''; }
            if (/^(data:|https?:|\/\/)/i.test(valor)) { return valor; }

            var base = form.dataset.sbBase || '';
            return base + '/' + valor.replace(/^\/+/, '');
        }

        function urlActual(campo) {
            var tarjeta = form.querySelector('[data-sb-file="' + campo + '"]');
            if (!tarjeta) { return ''; }
            if (tarjeta.dataset.sbLocal) { return tarjeta.dataset.sbLocal; }
            var url = tarjeta.querySelector('[data-sb-file-url]');
            return url && url.value.trim() !== '' ? resolverUrl(url.value) : '';
        }

        // Una ruta guardada puede apuntar a un archivo que ya no existe: si la
        // imagen no carga volvemos a la inicial en vez de dejar el icono roto.
        function pintarMarca(caja, fuente) {
            if (!caja) { return; }

            var img = caja.querySelector('img');
            var inicial = caja.querySelector('[data-sb-initial]');

            if (!fuente) {
                if (img) { img.hidden = true; }
                if (inicial) { inicial.hidden = false; }
                return;
            }

            if (!img) {
                img = document.createElement('img');
                img.alt = '';
                caja.appendChild(img);
            }

            img.onerror = function () {
                img.hidden = true;
                if (inicial) { inicial.hidden = false; }
            };
            img.onload = function () {
                img.hidden = false;
                if (inicial) { inicial.hidden = true; }
            };
            img.src = fuente;
        }

        function pintarImagenes() {
            var logo = urlActual('logo');
            form.querySelectorAll('[data-sb-mark]').forEach(function (marca) {
                pintarMarca(marca, logo);
            });

            [['192', 'pwa192'], ['512', 'pwa512']].forEach(function (par) {
                pintarMarca(form.querySelector('[data-sb-install="' + par[0] + '"]'), urlActual(par[1]));
            });

            var favCaja = form.querySelector('[data-sb-favicon]');
            if (favCaja) {
                var favicon = urlActual('favicon');
                var favImg = favCaja.querySelector('img');
                if (favicon) {
                    if (!favImg) { favImg = document.createElement('img'); favImg.alt = ''; favCaja.appendChild(favImg); }
                    favImg.src = favicon;
                    favImg.hidden = false;
                } else if (favImg) {
                    favImg.hidden = true;
                }
            }

            if (fromLogoBtn) { fromLogoBtn.hidden = urlActual('logo') === ''; }
            aplicarLogin();
            pintarBanderas();
        }

        function pintarMiniatura(tarjeta, fuente) {
            var thumb = tarjeta.querySelector('[data-sb-thumb]');
            if (!thumb) { return; }

            var img = thumb.querySelector('img');
            var icono = thumb.querySelector('i');

            if (!fuente) {
                if (img) { img.remove(); }
                if (icono) { icono.hidden = false; }
                return;
            }

            if (!img) { img = document.createElement('img'); img.alt = ''; thumb.appendChild(img); }
            img.onerror = function () {
                img.hidden = true;
                if (icono) { icono.hidden = false; }
            };
            img.onload = function () {
                img.hidden = false;
                if (icono) { icono.hidden = true; }
            };
            img.src = fuente;
        }

        function estadoTarjeta(tarjeta, texto, clase) {
            var estado = tarjeta.querySelector('[data-sb-file-state]');
            if (!estado) { return; }
            estado.textContent = texto;
            estado.className = 'sb-file-state' + (clase ? ' ' + clase : '');
        }

        function kb(bytes) {
            return bytes >= 1048576
                ? (bytes / 1048576).toFixed(1) + ' MB'
                : Math.round(bytes / 1024) + ' KB';
        }

        tarjetas.forEach(function (tarjeta) {
            var campo = tarjeta.dataset.sbFile;
            var entrada = tarjeta.querySelector('[data-sb-file-input]');
            var urlCampo = tarjeta.querySelector('[data-sb-file-url]');
            var limpiar = tarjeta.querySelector('[data-sb-file-clear]');

            urlsIniciales[campo] = urlCampo ? urlCampo.value : '';

            if (entrada) {
                entrada.addEventListener('change', function () {
                    var archivo = entrada.files && entrada.files[0];
                    delete tarjeta.dataset.sbLocal;

                    if (!archivo) {
                        estadoTarjeta(tarjeta, tarjeta.querySelector('[data-sb-file-state]').dataset.default || 'Sin archivo', '');
                        pintarMiniatura(tarjeta, resolverUrl(urlCampo ? urlCampo.value : ''));
                        pintarImagenes();
                        marcarSucio();
                        return;
                    }

                    var tope = parseInt(entrada.dataset.max || '0', 10);
                    if (tope > 0 && archivo.size > tope) {
                        estadoTarjeta(tarjeta, 'Pesa ' + kb(archivo.size) + ': el máximo es ' + kb(tope), 'is-error');
                        entrada.value = '';
                        return;
                    }

                    var lector = new FileReader();
                    lector.onload = function (evento) {
                        var fuente = evento.target.result;
                        var anchoPedido = parseInt(entrada.dataset.w || '0', 10);
                        var altoPedido = parseInt(entrada.dataset.h || '0', 10);

                        var img = new Image();
                        img.onload = function () {
                            if (anchoPedido > 0 && (img.naturalWidth !== anchoPedido || img.naturalHeight !== altoPedido)) {
                                estadoTarjeta(
                                    tarjeta,
                                    'Mide ' + img.naturalWidth + '×' + img.naturalHeight + ': debe ser ' + anchoPedido + '×' + altoPedido,
                                    'is-error'
                                );
                                entrada.value = '';
                                pintarMiniatura(tarjeta, resolverUrl(urlCampo ? urlCampo.value : ''));
                                pintarImagenes();
                                return;
                            }

                            tarjeta.dataset.sbLocal = fuente;
                            estadoTarjeta(tarjeta, 'Listo para subir · ' + img.naturalWidth + '×' + img.naturalHeight + ' · ' + kb(archivo.size), 'is-ready');
                            pintarMiniatura(tarjeta, fuente);
                            pintarImagenes();
                            marcarSucio();
                        };
                        img.onerror = function () {
                            estadoTarjeta(tarjeta, 'No se pudo leer la imagen', 'is-error');
                            entrada.value = '';
                        };
                        img.src = fuente;
                    };
                    lector.readAsDataURL(archivo);
                });
            }

            if (urlCampo) {
                urlCampo.addEventListener('input', function () {
                    delete tarjeta.dataset.sbLocal;
                    pintarMiniatura(tarjeta, resolverUrl(urlCampo.value));
                    pintarImagenes();
                    marcarSucio();
                });
            }

            if (limpiar) {
                limpiar.addEventListener('click', function () {
                    if (urlCampo) { urlCampo.value = ''; }
                    if (entrada) { entrada.value = ''; }
                    delete tarjeta.dataset.sbLocal;
                    estadoTarjeta(tarjeta, 'Se quitará al guardar', '');
                    pintarMiniatura(tarjeta, '');
                    pintarImagenes();
                    marcarSucio();
                });
            }
        });

        // ── Semaforo de completitud ───────────────────────────────────────
        function pintarBanderas() {
            var listas = {
                nombre: nameInput ? nameInput.value.trim() !== '' : false,
                colores: normalizarHex((form.querySelector('[data-sb-hex="primary"]') || {}).value) !== ''
                      && normalizarHex((form.querySelector('[data-sb-hex="secondary"]') || {}).value) !== '',
                logo: urlActual('logo') !== '',
                favicon: urlActual('favicon') !== '',
                login: urlActual('login_bg') !== '',
                pwa: urlActual('pwa192') !== '' && urlActual('pwa512') !== ''
            };

            var listos = 0;
            var total = 0;

            Object.keys(listas).forEach(function (clave) {
                var chip = document.querySelector('[data-sb-flag="' + clave + '"]');
                total += 1;
                if (listas[clave]) { listos += 1; }
                if (!chip) { return; }
                chip.classList.toggle('is-ok', listas[clave]);
                var icono = chip.querySelector('i');
                if (icono) { icono.className = 'fas ' + (listas[clave] ? 'fa-circle-check' : 'fa-circle-dashed'); }
            });

            var anillo = document.querySelector('[data-sb-ring]');
            var texto = document.querySelector('[data-sb-ring-text]');
            if (anillo) { anillo.style.setProperty('--sb-pct', total > 0 ? Math.round(listos * 100 / total) : 0); }
            if (texto) { texto.textContent = listos + '/' + total; }
        }

        // ── Cambios sin guardar ───────────────────────────────────────────
        function marcarSucio() {
            if (!dirtyLabel) { return; }

            var actual = new FormData(form);
            var cambios = 0;
            actual.forEach(function (valor, clave) {
                if (clave === 'csrf_token' || valor instanceof File) { return; }
                if (String(estadoInicial.get(clave) === null ? '' : estadoInicial.get(clave)) !== String(valor)) {
                    cambios += 1;
                }
            });

            form.querySelectorAll('[data-sb-file-input]').forEach(function (entrada) {
                if (entrada.files && entrada.files.length > 0) { cambios += 1; }
            });

            dirtyLabel.textContent = cambios === 0
                ? 'Sin cambios'
                : (cambios === 1 ? '1 cambio sin guardar' : cambios + ' cambios sin guardar');
            dirtyLabel.classList.toggle('is-dirty', cambios > 0);
            if (resetBtn) { resetBtn.disabled = cambios === 0; }
        }

        // ── Colores: swatch <-> hex ───────────────────────────────────────
        form.querySelectorAll('[data-sb-swatch]').forEach(function (swatch) {
            var clave = swatch.dataset.sbSwatch;
            var hexInput = form.querySelector('[data-sb-hex="' + clave + '"]');

            swatch.addEventListener('input', function () {
                if (hexInput) { hexInput.value = swatch.value.toUpperCase(); }
                aplicarColores();
                pintarBanderas();
                marcarSucio();
            });

            if (hexInput) {
                hexInput.addEventListener('input', function () {
                    var hex = normalizarHex(hexInput.value);
                    if (hex) { swatch.value = hex; }
                    aplicarColores();
                    pintarBanderas();
                    marcarSucio();
                });
                hexInput.addEventListener('blur', function () {
                    var hex = normalizarHex(hexInput.value);
                    if (hex) { hexInput.value = hex; }
                });
            }
        });

        form.querySelectorAll('[data-sb-palette]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var partes = String(boton.dataset.sbPalette || '').split('|');
                [['primary', partes[0]], ['secondary', partes[1]], ['accent', partes[2]]].forEach(function (par) {
                    var hex = normalizarHex(par[1]);
                    if (!hex) { return; }
                    var campo = form.querySelector('[data-sb-hex="' + par[0] + '"]');
                    var swatch = form.querySelector('[data-sb-swatch="' + par[0] + '"]');
                    if (campo) { campo.value = hex; }
                    if (swatch) { swatch.value = hex; }
                });
                aplicarColores();
                pintarBanderas();
                marcarSucio();
            });
        });

        if (backgroundInput) {
            backgroundInput.addEventListener('input', function () {
                if (backgroundMode) { backgroundMode.value = 'custom'; }
                if (backgroundState) { backgroundState.textContent = 'Personalizado para este hotel.'; }
                aplicarColores();
                marcarSucio();
            });
        }

        var backgroundDefault = form.querySelector('[data-sb-background-default]');
        if (backgroundDefault) {
            backgroundDefault.addEventListener('click', function () {
                if (backgroundMode) { backgroundMode.value = 'default'; }
                if (backgroundInput) { backgroundInput.value = '#F5F5F7'; }
                if (backgroundState) { backgroundState.textContent = 'Predeterminado del tema.'; }
                aplicarColores();
                marcarSucio();
            });
        }

        // Colores tomados del logo (mismo origen: el canvas no se contamina).
        if (fromLogoBtn) {
            fromLogoBtn.addEventListener('click', function () {
                var fuente = urlActual('logo');
                if (!fuente) { return; }

                var img = new Image();
                img.onload = function () {
                    try {
                        var lienzo = document.createElement('canvas');
                        var lado = 40;
                        lienzo.width = lado;
                        lienzo.height = lado;
                        var ctx = lienzo.getContext('2d');
                        ctx.drawImage(img, 0, 0, lado, lado);
                        var datos = ctx.getImageData(0, 0, lado, lado).data;
                        var cubos = {};

                        for (var i = 0; i < datos.length; i += 4) {
                            if (datos[i + 3] < 200) { continue; }
                            var r = datos[i], g = datos[i + 1], b = datos[i + 2];
                            var max = Math.max(r, g, b), min = Math.min(r, g, b);
                            // Fuera el lienzo: blancos, cremas y grises claros de
                            // fondo. Sin esto un logo sobre papel devuelve tres
                            // tonos casi blancos y la marca queda ilegible.
                            if (max > 232) { continue; }
                            if (max - min < 26 && max > 190) { continue; }
                            var llave = (r >> 4) + '-' + (g >> 4) + '-' + (b >> 4);
                            if (!cubos[llave]) { cubos[llave] = { n: 0, r: 0, g: 0, b: 0 }; }
                            cubos[llave].n += 1;
                            cubos[llave].r += r;
                            cubos[llave].g += g;
                            cubos[llave].b += b;
                        }

                        var lista = Object.keys(cubos).map(function (llave) {
                            var c = cubos[llave];
                            return {
                                n: c.n,
                                hex: '#' + [Math.round(c.r / c.n), Math.round(c.g / c.n), Math.round(c.b / c.n)].map(function (v) {
                                    return ('0' + v.toString(16)).slice(-2);
                                }).join('').toUpperCase()
                            };
                        }).sort(function (a, b) { return b.n - a.n; }).slice(0, 3);

                        if (lista.length === 0) {
                            if (fromLogoBtn) {
                                fromLogoBtn.textContent = 'El logo no tiene un color dominante claro';
                                setTimeout(function () {
                                    fromLogoBtn.innerHTML = '<i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> Tomar del logo';
                                }, 2600);
                            }
                            return;
                        }

                        lista.sort(function (a, b) { return luminancia(a.hex) - luminancia(b.hex); });

                        // Con un solo tono util se derivan los otros dos en vez
                        // de repetir el mismo color tres veces.
                        var base = lista[0].hex;
                        var elegidos = {
                            primary: base,
                            secondary: lista[1] ? lista[1].hex : mezclar(base, '#000000', 0.35),
                            accent: lista[2] ? lista[2].hex : mezclar(base, '#FFFFFF', 0.45)
                        };

                        Object.keys(elegidos).forEach(function (clave) {
                            var campo = form.querySelector('[data-sb-hex="' + clave + '"]');
                            var swatch = form.querySelector('[data-sb-swatch="' + clave + '"]');
                            if (campo) { campo.value = elegidos[clave]; }
                            if (swatch) { swatch.value = elegidos[clave]; }
                        });

                        aplicarColores();
                        pintarBanderas();
                        marcarSucio();
                    } catch (e) {
                        // Un logo servido desde otro dominio contamina el canvas: se ignora.
                    }
                };
                img.src = fuente;
            });
        }

        if (nameInput) {
            nameInput.addEventListener('input', function () {
                aplicarNombre();
                pintarBanderas();
                marcarSucio();
            });
        }

        form.querySelectorAll('input[name="sidebar_style"], input[name="login_style"], input[name="tema"], input[name="activo"]').forEach(function (entrada) {
            entrada.addEventListener('change', function () {
                aplicarLogin();
                marcarSucio();
            });
        });

        form.querySelectorAll('[data-sb-view]').forEach(function (boton) {
            boton.addEventListener('click', function () {
                var vista = boton.dataset.sbView;
                form.querySelectorAll('[data-sb-view]').forEach(function (otro) {
                    otro.classList.toggle('is-active', otro === boton);
                });
                form.querySelectorAll('[data-sb-view-panel]').forEach(function (panel) {
                    panel.hidden = panel.dataset.sbViewPanel !== vista;
                });
            });
        });

        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                form.reset();
                tarjetas.forEach(function (tarjeta) {
                    delete tarjeta.dataset.sbLocal;
                    var estado = tarjeta.querySelector('[data-sb-file-state]');
                    if (estado) { estadoTarjeta(tarjeta, estado.dataset.default || 'Sin archivo', ''); }
                    pintarMiniatura(tarjeta, resolverUrl(urlsIniciales[tarjeta.dataset.sbFile] || ''));
                });
                form.querySelectorAll('[data-sb-swatch]').forEach(function (swatch) {
                    var hexInput = form.querySelector('[data-sb-hex="' + swatch.dataset.sbSwatch + '"]');
                    var hex = normalizarHex(hexInput ? hexInput.value : '');
                    if (hex) { swatch.value = hex; }
                });
                aplicarColores();
                aplicarNombre();
                pintarImagenes();
                marcarSucio();
            });
        }

        aplicarColores();
        aplicarNombre();
        pintarImagenes();
        marcarSucio();
    })();
    </script>
    </section>

</div>

<script>
// Filtrar bloques opcionales sin alterar la selección ni enviar el formulario.
(function () {
    var input = document.getElementById('module-search');
    if (!input) return;

    var rows = Array.prototype.slice.call(document.querySelectorAll('[data-module-row]'));
    var count = document.getElementById('module-visible-count');
    var empty = document.getElementById('module-empty-search');
    var limpiar = document.getElementById('module-search-clear');
    var detalleBloqueados = document.getElementById('detalle-bloqueados');

    function filtrar() {
        var query = input.value.trim().toLocaleLowerCase('es-MX');
        var visibles = 0;
        var visiblesEnBloqueados = 0;

        rows.forEach(function (row) {
            var contenido = (row.getAttribute('data-module-name') || row.textContent || '').toLocaleLowerCase('es-MX');
            var coincide = query === '' || contenido.indexOf(query) !== -1;
            row.hidden = !coincide;
            if (coincide) {
                visibles += 1;
                if (detalleBloqueados && detalleBloqueados.contains(row)) visiblesEnBloqueados += 1;
            }
        });

        // Si la búsqueda encuentra módulos dentro de la sección contraída,
        // se abre para que el resultado sea visible.
        if (detalleBloqueados && query !== '' && visiblesEnBloqueados > 0) {
            detalleBloqueados.open = true;
        }

        if (count) count.textContent = String(visibles);
        if (empty) empty.style.display = query !== '' && visibles === 0 ? 'block' : 'none';
    }

    input.addEventListener('input', filtrar);
    if (limpiar) limpiar.addEventListener('click', function () {
        input.value = '';
        filtrar();
        input.focus();
    });
    filtrar();
})();

// Mostrar una sola sección a la vez y conservarla en la URL/historial.
(function () {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.saas-detail-nav [role="tab"]'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.saas-detail-section[role="tabpanel"]'));
    if (!tabs.length || !panels.length) return;

    var navWrap = document.querySelector('.saas-detail-nav-wrap');
    var storageKey = 'ms-hotel-detail-tab:' + window.location.pathname;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function idValido(id) {
        return panels.some(function (panel) { return panel.id === id; });
    }

    function idDesdeHash() {
        var hash = window.location.hash ? window.location.hash.slice(1) : '';
        try { hash = decodeURIComponent(hash); } catch (e) {}
        return idValido(hash) ? hash : '';
    }

    function recordar(id) {
        try { window.sessionStorage.setItem(storageKey, id); } catch (e) {}
    }

    function recordarLeido() {
        try {
            var id = window.sessionStorage.getItem(storageKey) || '';
            return idValido(id) ? id : '';
        } catch (e) {
            return '';
        }
    }

    function actualizarUrl(id, reemplazar) {
        var url = window.location.pathname + window.location.search + '#' + encodeURIComponent(id);
        if (window.history && window.history.pushState) {
            window.history[reemplazar ? 'replaceState' : 'pushState']({detailTab: id}, '', url);
            return;
        }
        window.location.hash = id;
    }

    function llevarAlContenido() {
        if (!navWrap) return;
        window.requestAnimationFrame(function () {
            window.scrollTo({
                top: Math.max(0, navWrap.offsetTop - 12),
                behavior: reduceMotion ? 'auto' : 'smooth'
            });
        });
    }

    function activar(id, opciones) {
        opciones = opciones || {};
        if (!idValido(id)) id = 'resumen';

        panels.forEach(function (panel) {
            var activo = panel.id === id;
            panel.hidden = !activo;
            panel.classList.toggle('is-active', activo);
        });

        tabs.forEach(function (tab) {
            var activo = tab.getAttribute('aria-controls') === id;
            tab.classList.toggle('is-active', activo);
            tab.setAttribute('aria-selected', activo ? 'true' : 'false');
            tab.setAttribute('tabindex', activo ? '0' : '-1');
        });

        recordar(id);
        if (opciones.actualizarUrl) actualizarUrl(id, !!opciones.reemplazarUrl);
        if (opciones.desplazar) llevarAlContenido();
    }

    tabs.forEach(function (tab, index) {
        tab.addEventListener('click', function (event) {
            event.preventDefault();
            activar(tab.getAttribute('aria-controls'), {actualizarUrl: true, desplazar: true});
        });

        tab.addEventListener('keydown', function (event) {
            var nuevoIndice = null;
            if (event.key === 'ArrowRight') nuevoIndice = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft') nuevoIndice = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') nuevoIndice = 0;
            if (event.key === 'End') nuevoIndice = tabs.length - 1;
            if (nuevoIndice === null) return;

            event.preventDefault();
            tabs[nuevoIndice].focus();
            activar(tabs[nuevoIndice].getAttribute('aria-controls'), {actualizarUrl: true, desplazar: true});
        });
    });

    document.querySelectorAll('[data-detail-tab-trigger]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            var id = (trigger.getAttribute('href') || '').replace(/^#/, '');
            if (!idValido(id)) return;
            event.preventDefault();
            activar(id, {actualizarUrl: true, desplazar: true});
        });
    });

    function restaurarDesdeHistorial() {
        activar(idDesdeHash() || 'resumen', {actualizarUrl: false, desplazar: true});
    }

    window.addEventListener('popstate', restaurarDesdeHistorial);
    window.addEventListener('hashchange', restaurarDesdeHistorial);

    var inicial = idDesdeHash() || recordarLeido() || 'resumen';
    activar(inicial, {actualizarUrl: true, reemplazarUrl: true, desplazar: false});

    if (window.location.hash) {
        window.addEventListener('load', function () { window.scrollTo(0, 0); }, {once: true});
    }
})();

// Confirmar guardado del plan solo cuando se aplicará el preset de módulos.
(function () {
    var form = document.getElementById('form-plan-hotel');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        var aplicar = form.querySelector('[name=aplicar_modulos]');
        if (!aplicar || !aplicar.checked) return;
        if (form.dataset.msOk === '1') { delete form.dataset.msOk; return; }
        e.preventDefault();
        msConfirm({
            type: 'warning',
            icon: 'alert',
            title: '¿Aplicar preset del plan?',
            msg: 'Guardar este plan aplicando el preset puede activar o desactivar módulos del hotel.',
            confirmLabel: 'Guardar y aplicar'
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.msOk = '1';
            if (form.requestSubmit) form.requestSubmit();
            else form.submit();
        });
    });
})();
</script>
