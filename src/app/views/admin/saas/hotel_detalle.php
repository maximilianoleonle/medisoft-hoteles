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
    return empty($modulo['es_core']) && !empty($modulo['activo_hotel']);
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
            $modulosBasicos = array_filter($modulosHotel, function ($m) { return !empty($m['es_core']); });
            $modulosOpcionalesHotel = array_filter($modulosHotel, function ($m) { return empty($m['es_core']); });
            ?>

            <div class="px-6 py-4 border-b border-gray-200" style="background:rgba(37,99,235,.04);">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold" style="color:var(--ms-text);"><i class="fas fa-lock text-xs mr-1.5" style="color:var(--ms-primary);"></i>Paquete básico — siempre incluido</p>
                        <p class="mt-0.5 text-xs text-gray-500">Lo mínimo para operar un hotel. No se puede apagar y ya está cubierto por el precio base.</p>
                    </div>
                    <span class="text-sm font-semibold" style="color:var(--ms-primary);">$<?= number_format($precioBaseCobro, 2) ?> / mes</span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <?php foreach ($modulosBasicos as $modulo): ?>
                        <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium" style="border-color:var(--ms-border);background:#fff;color:var(--ms-text);">
                            <?= $escapeCopy($modulo['nombre'] ?? '') ?>
                        </span>
                    <?php endforeach; ?>
                </div>
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
                        <input type="search" id="module-search" class="saas-module-search" placeholder="Buscar bloque por nombre o descripción" autocomplete="off">
                        <p class="mt-1 text-right text-[11px]" style="color:var(--ms-muted);"><span id="module-visible-count"><?= count($modulosOpcionalesHotel) ?></span> de <?= count($modulosOpcionalesHotel) ?> bloques</p>
                    </div>
                </div>

                <div class="saas-table-wrap">
                    <table class="saas-detail-table is-responsive saas-modules-table">
                        <thead>
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contratado</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Bloque</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio de lista</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Precio especial</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modulosOpcionalesHotel as $modulo): ?>
                                <?php
                                $globalActivo = !empty($modulo['activo_global']);
                                $precioCatalogo = (float) ($modulo['precio_mensual'] ?? 0);
                                $precioOverride = $modulo['precio_override'] ?? null;
                                $precioAplicado = $precioOverride !== null && $precioOverride !== '' ? (float) $precioOverride : $precioCatalogo;
                                ?>
                                <tr data-module-row data-module-name="<?= htmlspecialchars(strtolower($copyVisible(($modulo['nombre'] ?? '') . ' ' . ($modulo['descripcion'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>" class="<?= $globalActivo ? '' : 'bg-gray-50 text-gray-400' ?>">
                                    <td data-label="Contratado" class="text-sm">
                                        <input type="checkbox"
                                               name="modulos[]"
                                               value="<?= (int) $modulo['id'] ?>"
                                               aria-label="Contratar <?= $escapeCopy($modulo['nombre'] ?? '') ?>"
                                               data-precio="<?= number_format($precioAplicado, 2, '.', '') ?>"
                                               <?= !empty($modulo['activo_hotel']) ? 'checked' : '' ?>
                                               <?= $globalActivo ? '' : 'disabled' ?>
                                               class="modulo-toggle h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    </td>
                                    <td data-label="Bloque" class="saas-module-name text-sm text-gray-900">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium"><?= $escapeCopy($modulo['nombre'] ?? '') ?></span>
                                            <?php if (!$globalActivo): ?>
                                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background:rgba(100,116,139,.10);color:var(--ms-muted);">No disponible aún</span>
                                            <?php endif; ?>
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
                                               value="<?= $precioOverride !== null && $precioOverride !== '' ? number_format((float) $precioOverride, 2, '.', '') : '' ?>"
                                               placeholder="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               data-modulo="<?= (int) $modulo['id'] ?>"
                                               data-precio-catalogo="<?= number_format($precioCatalogo, 2, '.', '') ?>"
                                               class="precio-override w-24 rounded-md border-gray-300 text-sm focus:ring-gray-900 focus:border-gray-900"
                                               <?= $globalActivo ? '' : 'disabled' ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div id="module-empty-search" class="saas-detail-empty-search" role="status">
                    <i class="fas fa-search mr-1.5" aria-hidden="true"></i>No hay bloques que coincidan con esa búsqueda.
                </div>

                <div class="saas-detail-panel-footer">
                    <div class="text-sm" style="color:var(--ms-text);">
                        <div class="text-[11px] font-semibold uppercase tracking-wider" style="color:var(--ms-muted);">Cobro mensual estimado</div>
                        <div class="mt-0.5">
                            Paquete básico $<span id="cobro-base"><?= number_format($precioBaseCobro, 2) ?></span>
                            + bloques $<span id="cobro-modulos">0.00</span>
                            = <span class="text-lg font-bold">$<span id="cobro-total">0.00</span> <?= htmlspecialchars($monedaHotel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="text-xs" style="color:var(--ms-muted);">Se recalcula al activar/desactivar bloques o cambiar precios especiales. Precios editables en <a href="<?= url('admin/saas/modulos') ?>" class="underline">Bloques y precios</a>.</div>
                    </div>
                    <button type="submit" class="saas-detail-button is-primary">
                        <i class="fas fa-floppy-disk text-xs" aria-hidden="true"></i>
                        Guardar bloques y cobro
                    </button>
                </div>
            </form>

            <script>
            (function () {
                var form = document.getElementById('form-modulos-hotel');
                if (!form) return;
                var base = <?= json_encode(round($precioBaseCobro, 2)) ?>;

                function precioDeModulo(toggle) {
                    var override = form.querySelector('.precio-override[data-modulo="' + toggle.value + '"]');
                    if (override && override.value !== '' && !isNaN(parseFloat(override.value))) {
                        return Math.max(0, parseFloat(override.value));
                    }
                    return parseFloat(toggle.getAttribute('data-precio')) || 0;
                }

                function recalcular() {
                    var totalModulos = 0;
                    form.querySelectorAll('.modulo-toggle:checked:not(:disabled)').forEach(function (toggle) {
                        totalModulos += precioDeModulo(toggle);
                    });
                    var fmt = function (n) { return n.toLocaleString('es-MX', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };
                    document.getElementById('cobro-modulos').textContent = fmt(totalModulos);
                    document.getElementById('cobro-total').textContent = fmt(base + totalModulos);
                }

                form.addEventListener('change', recalcular);
                form.addEventListener('input', recalcular);
                recalcular();
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

    <div class="saas-detail-panel overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold" style="color:var(--ms-text);">Identidad visual</h3>
            <p class="mt-1 text-xs leading-relaxed" style="color:var(--ms-muted);">Usa colores en formato hexadecimal y archivos optimizados. No se admite código CSS, HTML ni JavaScript.</p>
        </div>

        <form method="POST" action="<?= url('admin/saas/hoteles/' . (int) $hotel['id'] . '/branding') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="p-5 grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="saas-branding-group-title">Identidad principal</div>
                    <div class="md:col-span-2">
                        <label for="nombre_visual" class="block text-sm font-medium text-gray-700">Nombre visual</label>
                        <input type="text" id="nombre_visual" name="nombre_visual" maxlength="150"
                               value="<?= $brandingCampo('nombre_visual', $hotel['nombre'] ?? '') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="Hotel Demo SaaS">
                    </div>

                    <div>
                        <label for="color_primary" class="block text-sm font-medium text-gray-700">Color primario</label>
                        <input type="text" id="color_primary" name="color_primary"
                               value="<?= $brandingCampo('color_primary') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#0F766E">
                    </div>

                    <div>
                        <label for="color_secondary" class="block text-sm font-medium text-gray-700">Color secundario</label>
                        <input type="text" id="color_secondary" name="color_secondary"
                               value="<?= $brandingCampo('color_secondary') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#115E59">
                    </div>

                    <div>
                        <label for="color_accent" class="block text-sm font-medium text-gray-700">Color acento</label>
                        <input type="text" id="color_accent" name="color_accent"
                               value="<?= $brandingCampo('color_accent') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="#F59E0B">
                    </div>

                    <div>
                        <label for="sidebar_style" class="block text-sm font-medium text-gray-700">Estilo sidebar</label>
                        <select id="sidebar_style" name="sidebar_style"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach (['default' => 'Default', 'solid' => 'Sólido', 'dark' => 'Oscuro'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($brandingHotel['sidebar_style'] ?? 'default') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="saas-branding-group-title">Logo</div>
                    <div class="md:col-span-2">
                        <label for="logo_url" class="block text-sm font-medium text-gray-700">Logo URL/ruta</label>
                        <input type="text" id="logo_url" name="logo_url"
                               value="<?= $brandingCampo('logo_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/img/logo.png">
                        <label for="logo_file" class="mt-3 block text-sm font-medium text-gray-700">Subir logo</label>
                        <input type="file" id="logo_file" name="logo_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG o WebP. Máximo 2 MB. No SVG.</p>
                    </div>

                    <div class="saas-branding-group-title">Acceso y navegador</div>
                    <div>
                        <label for="favicon_url" class="block text-sm font-medium text-gray-700">Favicon URL/ruta</label>
                        <input type="text" id="favicon_url" name="favicon_url"
                               value="<?= $brandingCampo('favicon_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/img/favicon.png">
                        <label for="favicon_file" class="mt-3 block text-sm font-medium text-gray-700">Subir favicon</label>
                        <input type="file" id="favicon_file" name="favicon_file" accept=".ico,.png,image/x-icon,image/vnd.microsoft.icon,image/png"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">ICO o PNG. Máximo 512 KB.</p>
                    </div>

                    <div>
                        <label for="login_background_url" class="block text-sm font-medium text-gray-700">Fondo login URL/ruta</label>
                        <input type="text" id="login_background_url" name="login_background_url"
                               value="<?= $brandingCampo('login_background_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/fondo.webp">
                        <label for="login_background_file" class="mt-3 block text-sm font-medium text-gray-700">Subir fondo login</label>
                        <input type="file" id="login_background_file" name="login_background_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG, JPG, JPEG o WebP. Máximo 4 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="login_style" class="block text-sm font-medium text-gray-700">Estilo login</label>
                        <select id="login_style" name="login_style"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900">
                            <?php foreach (['default' => 'Default', 'soft' => 'Suave', 'image' => 'Con imagen'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= ($brandingHotel['login_style'] ?? 'default') === $value ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="saas-branding-group-title">Aplicación instalable</div>
                    <div>
                        <label for="pwa_icon_192_url" class="block text-sm font-medium text-gray-700">Ícono PWA 192 URL/ruta</label>
                        <input type="text" id="pwa_icon_192_url" name="pwa_icon_192_url"
                               value="<?= $brandingCampo('pwa_icon_192_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/pwa-icons/icon-192.png">
                        <label for="pwa_icon_192_file" class="mt-3 block text-sm font-medium text-gray-700">Subir ícono PWA 192x192</label>
                        <input type="file" id="pwa_icon_192_file" name="pwa_icon_192_file" accept=".png,.webp,image/png,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG o WebP. Exactamente 192x192 px. Máximo 1 MB. No SVG.</p>
                    </div>

                    <div>
                        <label for="pwa_icon_512_url" class="block text-sm font-medium text-gray-700">Ícono PWA 512 URL/ruta</label>
                        <input type="text" id="pwa_icon_512_url" name="pwa_icon_512_url"
                               value="<?= $brandingCampo('pwa_icon_512_url') ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gray-900 focus:ring-gray-900"
                               placeholder="/uploads/branding/hotel/pwa-icons/icon-512.png">
                        <label for="pwa_icon_512_file" class="mt-3 block text-sm font-medium text-gray-700">Subir ícono PWA 512x512</label>
                        <input type="file" id="pwa_icon_512_file" name="pwa_icon_512_file" accept=".png,.webp,image/png,image/webp"
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-[var(--ms-primary)] file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-[var(--ms-primary-hover)]">
                        <p class="mt-1 text-xs text-gray-500">PNG o WebP. Exactamente 512x512 px. Máximo 1 MB. No SVG.</p>
                    </div>

                    <label class="mt-2 flex items-center gap-2 text-sm text-gray-700 md:col-span-2">
                        <input type="checkbox" name="activo" value="1"
                               <?= $brandingActivo ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                        Branding activo
                    </label>
                </div>

                <div class="lg:col-span-1">
                    <div class="saas-brand-preview">
                        <div style="background:linear-gradient(135deg, <?= htmlspecialchars($brandingHotel['color_primary'] ?? '#9CA777', ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($brandingHotel['color_secondary'] ?? '#7A8B5C', ENT_QUOTES, 'UTF-8') ?>);" class="px-4 py-8 text-center text-white">
                            <?php if ($brandingLogoPreview): ?>
                                <img src="<?= htmlspecialchars($brandingLogoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?>" class="mx-auto h-16 w-16 rounded-full bg-white object-contain p-2">
                            <?php else: ?>
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-white/20 text-2xl font-bold">
                                    <?= htmlspecialchars(strtoupper(substr((string) $brandingNombrePreview, 0, 1)), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                            <div class="mt-3 text-sm font-semibold"><?= htmlspecialchars($brandingNombrePreview, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="mt-1 text-xs opacity-80">Vista previa básica</div>
                        </div>
                        <div class="p-4 text-xs text-gray-600">
                            Los valores se imprimen como CSS variables sanitizadas:
                            <span class="font-mono">--brand-primary</span>,
                            <span class="font-mono">--brand-secondary</span> y
                            <span class="font-mono">--brand-accent</span>.
                        </div>
                        <div class="border-t border-gray-200 p-4">
                            <div class="grid grid-cols-2 gap-3 text-xs text-gray-600">
                                <div>
                                    <div class="font-semibold text-gray-700">Favicon</div>
                                    <?php if ($brandingFaviconPreview): ?>
                                        <img src="<?= htmlspecialchars($brandingFaviconPreview, ENT_QUOTES, 'UTF-8') ?>" alt="Favicon" class="mt-2 h-8 w-8 object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Sin favicon</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-semibold text-gray-700">Fondo login</div>
                                    <?php if ($brandingLoginBgPreview): ?>
                                        <div class="mt-2 h-12 rounded bg-cover bg-center" style="background-image:url('<?= htmlspecialchars($brandingLoginBgPreview, ENT_QUOTES, 'UTF-8') ?>')"></div>
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Sin fondo</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 p-4">
                            <div class="font-semibold text-gray-700 text-xs">Íconos PWA del manifest</div>
                            <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                <div>
                                    <div class="font-medium text-gray-700">192x192</div>
                                    <?php if ($brandingPwaIcon192Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwaIcon192Preview, ENT_QUOTES, 'UTF-8') ?>" alt="Ícono PWA 192" class="mt-2 h-12 w-12 rounded object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Fallback estático</div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-medium text-gray-700">512x512</div>
                                    <?php if ($brandingPwaIcon512Preview): ?>
                                        <img src="<?= htmlspecialchars($brandingPwaIcon512Preview, ENT_QUOTES, 'UTF-8') ?>" alt="Ícono PWA 512" class="mt-2 h-12 w-12 rounded object-contain">
                                    <?php else: ?>
                                        <div class="mt-2 text-gray-400">Fallback estático</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-gray-500">El manifest usa íconos del hotel solo cuando existen 192 y 512 válidos.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="saas-detail-panel-footer">
                <p class="text-xs leading-relaxed" style="color:var(--ms-muted);">Los cambios se aplican únicamente a la experiencia visual de este hotel.</p>
                <button type="submit" class="saas-detail-button is-primary">
                    <i class="fas fa-floppy-disk text-xs" aria-hidden="true"></i>
                    Guardar marca
                </button>
            </div>
        </form>
    </div>
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

    function filtrar() {
        var query = input.value.trim().toLocaleLowerCase('es-MX');
        var visibles = 0;

        rows.forEach(function (row) {
            var contenido = (row.getAttribute('data-module-name') || row.textContent || '').toLocaleLowerCase('es-MX');
            var coincide = query === '' || contenido.indexOf(query) !== -1;
            row.hidden = !coincide;
            if (coincide) visibles += 1;
        });

        if (count) count.textContent = String(visibles);
        if (empty) empty.style.display = query !== '' && visibles === 0 ? 'block' : 'none';
    }

    input.addEventListener('input', filtrar);
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
