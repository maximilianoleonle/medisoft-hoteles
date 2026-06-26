<?php
$notificaciones = $notificaciones ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$modulos = $modulos ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$mensajeFlash = get_mensaje();

if (!function_exists('ntx_safe')) {
    function ntx_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ntx_class')) {
    function ntx_class($value, $fallback = 'item')
    {
        $class = strtolower(trim((string)($value ?? '')));
        $class = preg_replace('/[^a-z0-9_-]+/', '-', $class);
        $class = trim((string)$class, '-');
        return $class !== '' ? $class : $fallback;
    }
}

if (!function_exists('ntx_date')) {
    function ntx_date($value, $format = 'd/m/Y H:i')
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('ntx_label')) {
    function ntx_label($value)
    {
        $labels = [
            'activas' => 'Pendientes',
            'nueva' => 'Sin abrir',
            'leida' => 'En seguimiento',
            'resuelta' => 'Atendida',
            'descartada' => 'Archivada',
            'info' => 'Info',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Critica',
            'caja' => 'Caja',
            'habitaciones' => 'Habitaciones',
            'facturacion' => 'Facturacion',
            'inventario' => 'Inventario',
            'reservaciones' => 'Reservaciones',
            'reportes' => 'Reportes',
            'sistema' => 'Sistema',
        ];

        $key = (string)($value ?? '');
        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}

if (!function_exists('ntx_icon')) {
    function ntx_icon($modulo)
    {
        $icons = [
            'caja' => 'fa-wallet',
            'habitaciones' => 'fa-bed',
            'facturacion' => 'fa-file-invoice',
            'inventario' => 'fa-boxes-stacked',
            'reservaciones' => 'fa-calendar-check',
            'reportes' => 'fa-chart-line',
            'sistema' => 'fa-sliders',
        ];

        return $icons[(string)$modulo] ?? 'fa-bell';
    }
}

if (!function_exists('ntx_filter_url')) {
    function ntx_filter_url(array $overrides = [])
    {
        $query = array_merge($_GET, $overrides);
        unset($query['severidad']);
        $query = array_filter($query, static function ($value) {
            return $value !== null && $value !== '';
        });

        return url('notificaciones' . (!empty($query) ? '?' . http_build_query($query) : ''));
    }
}

$estadoFiltro = (string)($filtros['estado'] ?? 'activas');
if ($estadoFiltro === 'nueva') {
    $estadoFiltro = 'activas';
}
$moduloFiltro = (string)($filtros['modulo'] ?? '');
$hotelNombre = function_exists('current_hotel_display_name') ? current_hotel_display_name() : 'Hotel';

$nuevas = (int)($resumen['nuevas'] ?? 0);
$pendientes = $nuevas;
$pendientesArchivables = (int)($resumen['pendientes'] ?? $pendientes);
$prioritarias = (int)($resumen['prioritarias'] ?? 0);
$hoy = (int)($resumen['hoy'] ?? 0);
$historial = (int)($resumen['historial'] ?? (($resumen['resueltas'] ?? 0) + ($resumen['descartadas'] ?? 0)));
$totalVista = count($notificaciones);

$estadoTabs = [
    'activas' => ['label' => 'Pendientes', 'count' => $pendientesArchivables],
    'resuelta' => ['label' => 'Atendidas', 'count' => (int)($resumen['resueltas'] ?? 0)],
    'descartada' => ['label' => 'Archivadas', 'count' => max(0, $historial - (int)($resumen['resueltas'] ?? 0))],
];
$contextoVacio = $moduloFiltro !== '' ? ' de ' . ntx_label($moduloFiltro) : '';
$emptyStates = [
    'activas' => [
        'icon' => 'fa-circle-check',
        'title' => 'Bandeja limpia',
        'message' => 'No hay pendientes accionables' . $contextoVacio . '. Los avisos informativos ya atendidos se mantienen fuera de esta vista.',
    ],
    'resuelta' => [
        'icon' => 'fa-check-double',
        'title' => 'Sin atendidas todavia',
        'message' => 'Cuando una notificacion se resuelva, aparecera aqui para consulta.',
    ],
    'descartada' => [
        'icon' => 'fa-box-archive',
        'title' => 'Archivo limpio',
        'message' => 'Las notificaciones archivadas se mostraran aqui cuando necesites revisar historial.',
    ],
];
$emptyState = $emptyStates[$estadoFiltro] ?? $emptyStates['activas'];
$totalModulos = array_sum(array_map(static function ($modulo) {
    return (int)($modulo['total'] ?? 0);
}, $modulos));
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.ntx-page {
    --ntx-primary: var(--brand-primary, #1f3f46);
    --ntx-secondary: var(--brand-secondary, #27333f);
    --ntx-accent: var(--brand-accent, #b58a3c);
    --ntx-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --ntx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --ntx-action: var(--brand-action-bg, var(--ntx-primary));
    --ntx-action-hover: var(--brand-action-bg-hover, color-mix(in srgb, var(--ntx-action) 90%, #111827));
    --ntx-on-action: var(--brand-action-text, #fffdf8);
    --ntx-ink: color-mix(in srgb, var(--ntx-primary) 54%, #475467);
    --ntx-text: #344054;
    --ntx-muted: #748094;
    --ntx-line: color-mix(in srgb, var(--ntx-primary) 9%, #e9e2d7);
    --ntx-surface: rgba(255, 255, 255, .76);
    --ntx-soft: color-mix(in srgb, var(--ntx-accent) 5%, #f8f5ee);
    --ntx-tone-blue: #3f7891;
    --ntx-tone-sage: #2f8a70;
    --ntx-tone-amber: #b98a35;
    --ntx-tone-coral: #b66b5f;
    --ntx-tone-indigo: #6e6aa9;
    --ntx-tone-olive: #6f8547;
    --ntx-tone-slate: #58728f;
    --ntx-panel-accent: var(--ntx-accent);
    --ntx-panel-wash: color-mix(in srgb, var(--ntx-panel-accent) 6%, #fffdf8);
    --ntx-focus: color-mix(in srgb, var(--ntx-accent) 22%, transparent);
    min-height: 100vh;
    color: var(--ntx-text);
    background:
        radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--ntx-accent) 12%, transparent), transparent 25rem),
        radial-gradient(circle at 35% 8%, color-mix(in srgb, var(--ntx-tone-sage) 8%, transparent), transparent 24rem),
        radial-gradient(circle at 74% 4%, color-mix(in srgb, var(--ntx-tone-indigo) 7%, transparent), transparent 25rem),
        radial-gradient(circle at 96% 8%, color-mix(in srgb, var(--ntx-primary) 7%, transparent), transparent 30rem),
        linear-gradient(180deg, #fcfbf8 0%, color-mix(in srgb, var(--ntx-accent) 4%, #f4f1ea) 100%);
    font-family: var(--ntx-sans);
}

.ntx-shell {
    width: min(1440px, calc(100% - 32px));
    margin: 0 auto;
    padding: 26px 0 52px;
}

.ntx-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: start;
    margin-bottom: 18px;
}

.ntx-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(960px, 100%);
}

.ntx-hero-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex: 0 0 48px;
    border-radius: 15px;
    color: #fff;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--ntx-accent), var(--ntx-primary) 54%, var(--ntx-tone-sage));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--ntx-primary) 72%, transparent);
}

.ntx-title-copy {
    min-width: 0;
    padding-top: 1px;
}

.ntx-kicker {
    display: block;
    margin: 0 0 2px;
    padding: 0;
    border: 0;
    background: transparent;
    color: var(--ntx-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.ntx-title {
    margin: 0;
    color: color-mix(in srgb, var(--ntx-primary) 60%, #465467);
    font-family: var(--ntx-serif);
    font-size: clamp(2.35rem, 4vw, 3.35rem);
    line-height: .98;
    font-weight: 700;
    letter-spacing: 0;
    text-wrap: balance;
}

.ntx-subtitle {
    max-width: 920px;
    margin: 9px 0 0;
    color: #526176;
    font-size: .94rem;
    line-height: 1.55;
    font-weight: 600;
}

.ntx-live-card {
    min-width: 260px;
    border: 1px solid var(--ntx-line);
    border-radius: 16px;
    background:
        linear-gradient(135deg, rgba(255,255,255,.84), color-mix(in srgb, var(--ntx-accent) 7%, rgba(255,255,255,.9)));
    box-shadow: 0 18px 42px -38px color-mix(in srgb, var(--ntx-primary) 34%, transparent);
    padding: 16px;
    color: var(--ntx-text);
}

.ntx-live-card span {
    display: block;
    color: var(--ntx-muted);
    font-size: .74rem;
    font-weight: 650;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.ntx-live-card strong {
    display: block;
    margin-top: 5px;
    font-size: 2.25rem;
    line-height: 1;
    color: color-mix(in srgb, var(--ntx-primary) 62%, #4b5563);
    font-weight: 560;
    font-variant-numeric: tabular-nums;
}

.ntx-live-card small {
    display: block;
    margin-top: 8px;
    color: color-mix(in srgb, var(--ntx-accent) 72%, #7c5b16);
    font-weight: 620;
}

.ntx-alert {
    margin-bottom: 14px;
    padding: 12px 14px;
    border: 1px solid var(--ntx-line);
    border-radius: 12px;
    background: rgba(255,255,255,.9);
    color: var(--ntx-ink);
    font-weight: 520;
}

.ntx-alert.success {
    border-color: #bbf7d0;
    background: #f0fdf4;
    color: #166534;
}

.ntx-alert.error {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.ntx-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

.ntx-stat {
    --stat-color: var(--ntx-accent);
    position: relative;
    overflow: hidden;
    min-height: 94px;
    display: grid;
    align-content: space-between;
    border: 1px solid color-mix(in srgb, var(--stat-color) 18%, var(--ntx-line));
    border-radius: 14px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--stat-color) 13%, transparent), transparent 8rem),
        linear-gradient(135deg, color-mix(in srgb, var(--stat-color) 8%, rgba(255,255,255,.88)), var(--ntx-surface));
    box-shadow: 0 14px 34px -32px color-mix(in srgb, var(--stat-color) 42%, transparent);
    padding: 14px;
}

.ntx-stat::after {
    content: "";
    position: absolute;
    inset: auto 12px 0 12px;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: var(--stat-color, var(--ntx-accent));
}

.ntx-stat.is-new {
    --stat-color: var(--ntx-tone-amber);
}

.ntx-stat.is-priority {
    --stat-color: var(--ntx-tone-coral);
}

.ntx-stat.is-today {
    --stat-color: var(--ntx-tone-blue);
}

.ntx-stat.is-history {
    --stat-color: var(--ntx-tone-sage);
}

.ntx-stat span {
    color: var(--ntx-muted);
    font-size: .72rem;
    font-weight: 620;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.ntx-stat strong {
    color: color-mix(in srgb, var(--ntx-primary) 58%, #4b5563);
    font-size: 1.75rem;
    line-height: 1;
    font-weight: 560;
    font-variant-numeric: tabular-nums;
}

.ntx-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 16px;
    align-items: start;
}

.ntx-side {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(270px, .34fr);
    grid-template-areas: "modules device";
    gap: 12px;
    align-items: stretch;
}

.ntx-panel {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--ntx-panel-accent) 15%, var(--ntx-line));
    border-radius: 16px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--ntx-panel-accent) 10%, transparent), transparent 11rem),
        linear-gradient(180deg, rgba(255,255,255,.94), var(--ntx-panel-wash));
    box-shadow: 0 14px 34px -34px color-mix(in srgb, var(--ntx-panel-accent) 40%, transparent);
}

.ntx-module-panel {
    grid-area: modules;
    display: flex;
    flex-direction: column;
    --ntx-panel-accent: var(--ntx-tone-olive);
    --ntx-panel-wash: color-mix(in srgb, var(--ntx-tone-olive) 5%, #fffdf8);
}

.ntx-panel-head {
    position: relative;
    padding: 13px 15px 10px;
    border-bottom: 1px solid color-mix(in srgb, var(--ntx-panel-accent) 14%, var(--ntx-line));
}

.ntx-panel-head::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 3px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--ntx-panel-accent) 62%, var(--ntx-line)), color-mix(in srgb, var(--ntx-panel-accent) 10%, transparent));
}

.ntx-panel-head h2 {
    margin: 0;
    color: color-mix(in srgb, var(--ntx-panel-accent) 38%, var(--ntx-ink));
    font-size: .96rem;
    line-height: 1.2;
    font-weight: 660;
}

.ntx-panel-head p {
    margin: 4px 0 0;
    color: var(--ntx-muted);
    font-size: .78rem;
    line-height: 1.42;
    font-weight: 430;
}

.ntx-btn {
    min-height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    border: 1px solid var(--ntx-line);
    border-radius: 11px;
    background: #fffdf8;
    color: var(--ntx-ink);
    padding: 0 11px;
    font-size: .8rem;
    font-weight: 680;
    text-decoration: none;
    cursor: pointer;
    transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
}

.ntx-btn:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ntx-panel-accent) 32%, var(--ntx-line));
    background: color-mix(in srgb, var(--ntx-panel-accent) 8%, #fffdf8);
}

.ntx-btn:active {
    transform: translateY(0);
}

.ntx-btn:focus-visible,
.ntx-row:focus-visible,
.ntx-tab:focus-visible {
    outline: 3px solid var(--ntx-focus);
    outline-offset: 2px;
}

.ntx-btn.is-primary {
    border-color: color-mix(in srgb, var(--ntx-action) 80%, transparent);
    background: var(--ntx-action);
    color: var(--ntx-on-action);
}

.ntx-btn.is-primary:hover {
    background: var(--ntx-action-hover);
}

.ntx-module-strip {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    align-content: center;
    gap: 8px;
    flex: 1 1 auto;
    min-height: 104px;
    padding: 20px 22px 22px;
}

.ntx-module-chip {
    --module-accent: var(--ntx-tone-olive);
    display: inline-flex;
    align-items: center;
    gap: 7px;
    flex: 0 1 190px;
    justify-content: space-between;
    min-height: 38px;
    border: 1px solid color-mix(in srgb, var(--module-accent) 18%, #ddd5c8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--module-accent) 4%, #fffdf8);
    color: #435164;
    padding: 0 10px 0 6px;
    font-size: .78rem;
    font-weight: 660;
    text-decoration: none;
    transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease;
}

.ntx-module-chip:nth-child(6n+1) { --module-accent: var(--ntx-tone-olive); }
.ntx-module-chip:nth-child(6n+2) { --module-accent: var(--ntx-tone-blue); }
.ntx-module-chip:nth-child(6n+3) { --module-accent: var(--ntx-tone-indigo); }
.ntx-module-chip:nth-child(6n+4) { --module-accent: var(--ntx-tone-sage); }
.ntx-module-chip:nth-child(6n+5) { --module-accent: var(--ntx-tone-amber); }
.ntx-module-chip:nth-child(6n+6) { --module-accent: var(--ntx-tone-coral); }

.ntx-module-chip i {
    width: 27px;
    height: 27px;
    display: inline-grid;
    place-items: center;
    border-radius: 999px;
    background: color-mix(in srgb, var(--module-accent) 12%, #fff);
    color: color-mix(in srgb, var(--module-accent) 72%, #667085);
}

.ntx-module-chip strong {
    margin-left: auto;
    color: var(--ntx-muted);
    font-size: .7rem;
    font-weight: 720;
    font-variant-numeric: tabular-nums;
}

.ntx-module-chip:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--module-accent) 36%, #ddd5c8);
    background: color-mix(in srgb, var(--module-accent) 8%, #fffdf8);
}

.ntx-module-chip.is-active {
    border-color: color-mix(in srgb, var(--ntx-action) 84%, transparent);
    background: var(--ntx-action);
    color: var(--ntx-on-action);
}

.ntx-module-chip.is-active i {
    background: color-mix(in srgb, var(--ntx-on-action) 18%, transparent);
    color: var(--ntx-on-action);
}

.ntx-module-chip.is-active strong {
    color: color-mix(in srgb, var(--ntx-on-action) 84%, transparent);
}

.ntx-push {
    grid-area: device;
    --ntx-panel-accent: var(--ntx-tone-coral);
    --ntx-panel-wash: color-mix(in srgb, var(--ntx-tone-coral) 5%, #fffdf8);
    display: grid;
    align-content: space-between;
    overflow: hidden;
    padding: 0;
}

.ntx-push-head {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 10px;
    padding: 13px 15px 11px;
    border-bottom: 1px solid color-mix(in srgb, var(--ntx-line) 84%, transparent);
}

.ntx-push-icon {
    width: 34px;
    height: 34px;
    display: inline-grid;
    place-items: center;
    border-radius: 11px;
    background: color-mix(in srgb, var(--ntx-panel-accent) 12%, #fffdf8);
    color: color-mix(in srgb, var(--ntx-panel-accent) 72%, #667085);
}

.ntx-push h2 {
    margin: 0;
    color: var(--ntx-ink);
    font-size: .96rem;
    font-weight: 660;
}

.ntx-push p {
    margin: 4px 0 0;
    color: var(--ntx-muted);
    font-size: .78rem;
    line-height: 1.45;
    font-weight: 430;
}

.ntx-push-actions {
    display: grid;
    gap: 8px;
    margin: 0;
    padding: 12px 15px 15px;
}

.ntx-push[data-push-state="enabled"] .ntx-btn.is-primary {
    border-color: #fecaca;
    background: #fef2f2;
    color: #991b1b;
}

.ntx-main {
    min-width: 0;
    overflow: hidden;
    --ntx-panel-accent: var(--ntx-tone-sage);
    --ntx-panel-wash: color-mix(in srgb, var(--ntx-tone-sage) 4%, #fffdf8);
}

.ntx-inbox-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: start;
    padding: 16px;
    border-bottom: 1px solid var(--ntx-line);
}

.ntx-inbox-head h2 {
    margin: 0;
    color: var(--ntx-ink);
    font-size: 1.18rem;
    line-height: 1.2;
    font-weight: 620;
}

.ntx-inbox-head p {
    margin: 5px 0 0;
    color: var(--ntx-muted);
    font-size: .84rem;
    line-height: 1.45;
    font-weight: 420;
}

.ntx-count {
    display: inline-flex;
    align-items: center;
    min-height: 34px;
    border: 1px solid color-mix(in srgb, var(--ntx-panel-accent) 20%, #ddd5c8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--ntx-panel-accent) 8%, #fff);
    color: color-mix(in srgb, var(--ntx-panel-accent) 62%, #667085);
    padding: 0 11px;
    font-size: .78rem;
    font-weight: 620;
    white-space: nowrap;
}

.ntx-inbox-tools {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
}

.ntx-archive-form {
    margin: 0;
}

.ntx-btn.is-archive {
    min-height: 34px;
    border-color: color-mix(in srgb, var(--ntx-tone-sage) 24%, #ddd5c8);
    background: color-mix(in srgb, var(--ntx-tone-sage) 7%, #fffdf8);
    color: color-mix(in srgb, var(--ntx-tone-sage) 68%, #334155);
    white-space: nowrap;
}

.ntx-btn.is-archive:hover {
    border-color: color-mix(in srgb, var(--ntx-tone-sage) 42%, #ddd5c8);
    background: color-mix(in srgb, var(--ntx-tone-sage) 12%, #fffdf8);
}

.ntx-btn.is-archive.is-confirming {
    border-color: color-mix(in srgb, var(--ntx-tone-gold) 58%, #d8c5a0);
    background: color-mix(in srgb, var(--ntx-tone-gold) 16%, #fffdf8);
    color: color-mix(in srgb, var(--ntx-tone-gold) 70%, #334155);
}

.ntx-action-toast {
    position: fixed;
    right: 22px;
    bottom: 22px;
    z-index: 15000;
    max-width: min(390px, calc(100vw - 32px));
    border: 1px solid #f3d08a;
    border-radius: 14px;
    background: #fff8e8;
    color: #8a5b12;
    padding: 12px 14px;
    box-shadow: 0 18px 42px rgba(24, 32, 48, .18);
    font-size: .82rem;
    font-weight: 850;
    line-height: 1.42;
    opacity: 0;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .18s ease, transform .18s ease;
}

.ntx-action-toast.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.ntx-tabs {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    align-content: center;
    gap: 8px;
    min-height: 64px;
    padding: 12px 16px;
    border-bottom: 1px solid var(--ntx-line);
    box-sizing: border-box;
}

.ntx-tab {
    min-height: 34px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid color-mix(in srgb, var(--ntx-primary) 10%, #ddd5c8);
    border-radius: 999px;
    background: #fff;
    color: #526176;
    padding: 0 11px;
    font-size: .78rem;
    font-weight: 560;
    text-decoration: none;
    transition: background .18s ease, border-color .18s ease, color .18s ease;
}

.ntx-tab:hover,
.ntx-tab.is-active {
    border-color: color-mix(in srgb, var(--ntx-panel-accent) 42%, #ddd5c8);
    background: color-mix(in srgb, var(--ntx-panel-accent) 8%, #fff);
    color: var(--ntx-ink);
}

.ntx-tab b {
    font-variant-numeric: tabular-nums;
}

.ntx-feed {
    display: grid;
    gap: 10px;
    padding: 14px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--ntx-panel-accent) 8%, transparent), transparent 16rem),
        linear-gradient(180deg, rgba(255,255,255,.50), color-mix(in srgb, var(--ntx-panel-accent) 3%, rgba(255,255,255,.24)));
}

.ntx-row {
    --row-color: var(--ntx-tone-slate);
    position: relative;
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    gap: 13px;
    align-items: start;
    min-height: 94px;
    border: 1px solid color-mix(in srgb, var(--row-color) 14%, #e8e0d3);
    border-radius: 14px;
    background: #fff;
    padding: 14px;
    box-shadow: 0 10px 24px -24px color-mix(in srgb, var(--row-color) 35%, transparent);
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.ntx-row::before {
    content: "";
    position: absolute;
    inset: 12px auto 12px 0;
    width: 3px;
    border-radius: 0 999px 999px 0;
    background: var(--row-color, #94a3b8);
}

.ntx-row.is-clickable {
    cursor: pointer;
}

.ntx-row.is-clickable:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--row-color) 28%, #dfd5c8);
    background: #fff;
    box-shadow: 0 14px 28px -26px color-mix(in srgb, var(--row-color) 38%, transparent);
}

.ntx-row.sev-info { --row-color: var(--ntx-tone-blue); }
.ntx-row.sev-media { --row-color: var(--ntx-tone-amber); }
.ntx-row.sev-alta { --row-color: var(--ntx-tone-coral); }
.ntx-row.sev-critica { --row-color: #c2410c; }

.ntx-row.state-resuelta,
.ntx-row.state-descartada {
    opacity: .82;
}

.ntx-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--row-color) 20%, #ddd5c8);
    border-radius: 12px;
    background: color-mix(in srgb, var(--row-color) 12%, #fff);
    color: color-mix(in srgb, var(--row-color) 72%, #667085);
}

.ntx-row-copy {
    min-width: 0;
}

.ntx-row-top {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 7px;
}

.ntx-row-title {
    color: color-mix(in srgb, var(--ntx-primary) 52%, #344054);
    font-size: .98rem;
    line-height: 1.28;
    font-weight: 620;
}

.ntx-badge {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    border-radius: 999px;
    padding: 0 8px;
    font-size: .7rem;
    font-weight: 650;
}

.ntx-badge.state-nueva {
    background: color-mix(in srgb, var(--ntx-tone-amber) 15%, #fff);
    color: color-mix(in srgb, var(--ntx-tone-amber) 68%, #667085);
}

.ntx-badge.state-leida {
    background: #eef2f7;
    color: #475569;
}

.ntx-badge.state-resuelta {
    background: color-mix(in srgb, var(--ntx-tone-sage) 16%, #fff);
    color: color-mix(in srgb, var(--ntx-tone-sage) 76%, #166534);
}

.ntx-badge.state-descartada {
    background: #f1f5f9;
    color: #475569;
}

.ntx-badge.sev-info {
    background: color-mix(in srgb, var(--ntx-tone-blue) 12%, #fff);
    color: color-mix(in srgb, var(--ntx-tone-blue) 70%, #475569);
}

.ntx-badge.sev-media {
    background: color-mix(in srgb, var(--ntx-tone-amber) 18%, #fff);
    color: color-mix(in srgb, var(--ntx-tone-amber) 78%, #92400e);
}

.ntx-badge.sev-alta {
    background: color-mix(in srgb, var(--ntx-tone-coral) 17%, #fff);
    color: color-mix(in srgb, var(--ntx-tone-coral) 80%, #9a3412);
}

.ntx-badge.sev-critica {
    background: color-mix(in srgb, #dc2626 14%, #fff);
    color: #991b1b;
}

.ntx-message {
    margin: 7px 0 0;
    color: #526176;
    font-size: .9rem;
    line-height: 1.48;
    font-weight: 420;
}

.ntx-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
    color: var(--ntx-muted);
    font-size: .78rem;
    font-weight: 430;
}

.ntx-meta span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ntx-arrow {
    align-self: center;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--row-color) 18%, #ddd5c8);
    border-radius: 12px;
    color: color-mix(in srgb, var(--row-color) 65%, #667085);
    background: color-mix(in srgb, var(--row-color) 5%, #fff);
}

.ntx-empty {
    min-height: 280px;
    display: grid;
    place-items: center;
    border: 1px dashed color-mix(in srgb, var(--ntx-panel-accent) 28%, #d9cec0);
    border-radius: 14px;
    background: color-mix(in srgb, var(--ntx-panel-accent) 5%, rgba(255,255,255,.72));
    padding: 28px;
    text-align: center;
}

.ntx-empty i {
    width: 52px;
    height: 52px;
    display: inline-grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--ntx-panel-accent) 22%, #ddd5c8);
    border-radius: 14px;
    background: color-mix(in srgb, var(--ntx-panel-accent) 10%, #fff);
    color: color-mix(in srgb, var(--ntx-panel-accent) 70%, #667085);
    font-size: 1.2rem;
}

.ntx-empty h3 {
    margin: 14px 0 5px;
    color: var(--ntx-ink);
    font-size: 1.05rem;
    font-weight: 620;
}

.ntx-empty p {
    max-width: 420px;
    margin: 0 auto;
    color: var(--ntx-muted);
    line-height: 1.5;
    font-weight: 420;
}

@media (max-width: 1100px) {
    .ntx-top,
    .ntx-layout {
        grid-template-columns: 1fr;
    }

    .ntx-live-card {
        min-width: 0;
    }

    .ntx-side {
        position: static;
        grid-template-columns: 1fr;
        grid-template-areas:
            "modules"
            "device";
    }
}

@media (max-width: 760px) {
    .ntx-shell {
        width: 100%;
        margin: 0;
        padding: 16px 14px 40px;
    }

    /* ── Header compacto ── */
    .ntx-top {
        display: block;
        margin-bottom: 14px;
    }
    .ntx-title-lockup {
        grid-template-columns: 40px minmax(0, 1fr);
        column-gap: 11px;
        align-items: center;
        max-width: none;
    }
    .ntx-hero-icon {
        width: 40px;
        height: 40px;
        flex-basis: 40px;
        border-radius: 13px;
    }
    .ntx-kicker { display: none; }            /* omitido: redundante en movil */
    .ntx-title { font-size: 1.6rem; }
    .ntx-subtitle { display: none; }          /* omitido: parrafo largo satura */
    .ntx-live-card { display: none; }         /* omitido: duplica los stats de abajo */

    /* ── Stats 2x2 compactos ── */
    .ntx-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 14px;
    }
    .ntx-stat {
        min-height: 0;
        gap: 5px;
        padding: 11px 12px;
        border-radius: 13px;
    }
    .ntx-stat::after { height: 2px; }
    .ntx-stat span { font-size: .66rem; }
    .ntx-stat strong { font-size: 1.5rem; }

    /* ── Paneles laterales: compactos ── */
    .ntx-layout { gap: 12px; }
    .ntx-side { gap: 12px; }
    .ntx-panel-head { padding: 12px 14px 9px; }
    .ntx-panel-head p { display: none; }      /* omitido: descripcion decorativa */

    /* Filtro de origen: chips en fila scrollable */
    .ntx-module-strip {
        flex-wrap: nowrap;
        overflow-x: auto;
        justify-content: flex-start;
        gap: 7px;
        min-height: 0;
        padding: 12px 14px;
        scrollbar-width: none;
    }
    .ntx-module-strip::-webkit-scrollbar { display: none; }
    .ntx-module-chip {
        flex: 0 0 auto;
        min-height: 34px;
        padding: 0 11px 0 6px;
    }

    /* Panel Dispositivo (push): se conserva, compacto */
    .ntx-push-head { padding: 12px 14px 10px; }
    .ntx-push-actions { padding: 10px 14px 13px; }

    /* ── Bandeja ── */
    .ntx-inbox-head {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 14px;
    }
    .ntx-inbox-head h2 { font-size: 1.05rem; }
    .ntx-inbox-tools { justify-content: flex-start; }
    .ntx-tabs {
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 6px;
        min-height: 0;
        padding: 10px 14px;
        scrollbar-width: none;
    }
    .ntx-tabs::-webkit-scrollbar { display: none; }
    .ntx-tab { flex: 0 0 auto; white-space: nowrap; }
    .ntx-feed { padding: 12px; gap: 8px; }
    .ntx-row {
        grid-template-columns: 38px minmax(0, 1fr);
        gap: 11px;
        min-height: 0;
        padding: 12px;
        border-radius: 13px;
    }
    .ntx-icon { width: 38px; height: 38px; border-radius: 11px; }
    .ntx-row-title { font-size: .92rem; }
    .ntx-message {
        margin-top: 5px;
        font-size: .84rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .ntx-meta { gap: 8px; margin-top: 8px; font-size: .72rem; }
    .ntx-arrow { display: none; }
    .ntx-empty { min-height: 200px; padding: 24px; }
}
</style>

<main class="ntx-page">
    <div class="ntx-shell">
        <?php if ($mensajeFlash): ?>
            <div class="ntx-alert <?= ntx_class($mensajeFlash['tipo'] ?? 'info', 'info') ?>">
                <?= ntx_safe($mensajeFlash['texto'] ?? '') ?>
            </div>
        <?php endif; ?>

        <header class="ntx-top">
            <div class="ntx-title-lockup">
                <div class="ntx-hero-icon" aria-hidden="true">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="ntx-title-copy">
                    <p class="ntx-kicker">Operacion hotelera</p>
                    <h1 class="ntx-title">Notificaciones</h1>
                    <p class="ntx-subtitle">Pendientes accionables, avisos atendidos e historial sin saturar la operacion diaria &middot; <?= ntx_safe($hotelNombre) ?></p>
                </div>
            </div>

            <aside class="ntx-live-card" aria-label="Resumen principal">
                <span>Pendientes</span>
                <strong><?= $pendientesArchivables ?></strong>
                <small><?= $nuevas ?> sin abrir - <?= $prioritarias ?> prioritarias</small>
            </aside>
        </header>

        <section class="ntx-stats" aria-label="Resumen de notificaciones">
            <div class="ntx-stat is-new">
                <span>Sin abrir</span>
                <strong><?= $nuevas ?></strong>
            </div>
            <div class="ntx-stat is-priority">
                <span>Prioritarias</span>
                <strong><?= $prioritarias ?></strong>
            </div>
            <div class="ntx-stat is-today">
                <span>Hoy</span>
                <strong><?= $hoy ?></strong>
            </div>
            <div class="ntx-stat is-history">
                <span>Historial</span>
                <strong><?= $historial ?></strong>
            </div>
        </section>

        <div class="ntx-layout">
            <aside class="ntx-side" aria-label="Controles de notificaciones">
                <?php if (!empty($modulos)): ?>
                    <section class="ntx-panel ntx-module-panel">
                        <div class="ntx-panel-head">
                            <h2>Origen</h2>
                            <p>Filtra por area sin llenar la pantalla de controles.</p>
                        </div>
                        <div class="ntx-module-strip">
                            <a class="ntx-module-chip <?= $moduloFiltro === '' ? 'is-active' : '' ?>"
                               href="<?= ntx_filter_url(['modulo' => '']) ?>">
                                <i class="fas fa-layer-group"></i>
                                <span>Todos</span>
                                <strong><?= (int)$totalModulos ?></strong>
                            </a>
                            <?php foreach ($modulos as $modulo): ?>
                                <?php $moduloClave = (string)($modulo['modulo'] ?? ''); ?>
                                <a class="ntx-module-chip <?= $moduloFiltro === $moduloClave ? 'is-active' : '' ?>"
                                   href="<?= ntx_filter_url(['modulo' => $moduloClave]) ?>">
                                    <i class="fas <?= ntx_safe(ntx_icon($moduloClave), 'fa-bell') ?>"></i>
                                    <span><?= ntx_safe(ntx_label($moduloClave)) ?></span>
                                    <strong><?= (int)($modulo['total'] ?? 0) ?></strong>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="ntx-panel ntx-push"
                         data-pwa-push-panel
                         data-public-key-url="<?= url('api/pwa-push/public-key') ?>"
                         data-subscribe-url="<?= url('api/pwa-push/subscribe') ?>"
                         data-unsubscribe-url="<?= url('api/pwa-push/unsubscribe') ?>"
                         data-test-url="<?= url('api/pwa-push/test') ?>">
                    <div class="ntx-push-head">
                        <span class="ntx-push-icon" aria-hidden="true">
                            <i class="fas fa-mobile-screen-button"></i>
                        </span>
                        <div>
                            <h2>Dispositivo</h2>
                            <p data-pwa-push-status>Revisando compatibilidad del navegador...</p>
                        </div>
                    </div>
                    <div class="ntx-push-actions">
                        <button type="button" class="ntx-btn is-primary" data-pwa-push-toggle>
                            <i class="fas fa-bell"></i>
                            <span data-pwa-push-label>Activar en este dispositivo</span>
                        </button>
                        <button type="button" class="ntx-btn" data-pwa-push-test hidden>
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviar prueba</span>
                        </button>
                    </div>
                </section>
            </aside>

            <section class="ntx-panel ntx-main" aria-label="Bandeja de notificaciones">
                <div class="ntx-inbox-head">
                    <div>
                        <h2>Actividad</h2>
                        <p><?= ntx_safe($estadoTabs[$estadoFiltro]['label'] ?? ntx_label($estadoFiltro)) ?><?= $moduloFiltro !== '' ? ' de ' . ntx_safe(ntx_label($moduloFiltro)) : '' ?></p>
                    </div>
                    <div class="ntx-inbox-tools">
                        <?php if ($tablaDisponible && $estadoFiltro === 'activas' && $pendientesArchivables > 0): ?>
                            <form method="POST"
                                  action="<?= url('notificaciones/marcar-todas-leidas') ?>"
                                  class="ntx-archive-form"
                                  data-archive-visible-form="1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="archivar_pendientes">
                                <button type="submit" class="ntx-btn is-archive">
                                    <i class="fas fa-box-archive"></i>
                                    <span>Archivar visibles</span>
                                </button>
                            </form>
                        <?php endif; ?>
                        <span class="ntx-count"><?= $totalVista ?> registros</span>
                    </div>
                </div>

                <nav class="ntx-tabs" aria-label="Cambiar estado">
                    <?php foreach ($estadoTabs as $estado => $tab): ?>
                        <a class="ntx-tab <?= $estadoFiltro === $estado ? 'is-active' : '' ?>"
                           href="<?= ntx_filter_url(['estado' => $estado]) ?>">
                            <span><?= ntx_safe($tab['label']) ?></span>
                            <b><?= (int)$tab['count'] ?></b>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="ntx-feed">
                    <?php if (!$tablaDisponible): ?>
                        <div class="ntx-empty">
                            <div>
                                <i class="fas fa-bell-slash"></i>
                                <h3>Notificaciones no disponibles</h3>
                                <p>La tabla de notificaciones todavia no esta instalada para este hotel.</p>
                            </div>
                        </div>
                    <?php elseif (empty($notificaciones)): ?>
                        <div class="ntx-empty">
                            <div>
                                <i class="fas <?= ntx_safe($emptyState['icon'] ?? 'fa-circle-check') ?>"></i>
                                <h3><?= ntx_safe($emptyState['title'] ?? 'Bandeja limpia') ?></h3>
                                <p><?= ntx_safe($emptyState['message'] ?? 'No hay notificaciones para esta vista.') ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notificaciones as $notificacion): ?>
                            <?php
                                $id = (int)($notificacion['id'] ?? 0);
                                $estado = (string)($notificacion['estado'] ?? 'nueva');
                                $severidad = (string)($notificacion['severidad'] ?? 'info');
                                $modulo = (string)($notificacion['modulo'] ?? 'sistema');
                                $tipo = (string)($notificacion['tipo'] ?? '');
                                $esAutomatica = strpos($tipo, 'regla_') === 0;
                                $urlDestino = trim((string)($notificacion['url'] ?? ''));
                                $urlDestinoFinal = ($urlDestino !== '' && $id > 0) ? url('notificaciones/' . $id . '/abrir') : '';
                                $tituloNotificacion = (string)($notificacion['titulo'] ?? 'Notificacion');
                                $estadoClass = ntx_class($estado, 'nueva');
                                $severidadClass = ntx_class($severidad, 'info');
                                $requiereAccion = in_array($estado, ['nueva', 'leida'], true);
                            ?>
                            <article class="ntx-row state-<?= $estadoClass ?> sev-<?= $severidadClass ?><?= $urlDestinoFinal !== '' ? ' is-clickable' : '' ?>"
                                     <?php if ($urlDestinoFinal !== ''): ?>
                                         data-notif-url="<?= htmlspecialchars($urlDestinoFinal, ENT_QUOTES, 'UTF-8') ?>"
                                         role="link"
                                         tabindex="0"
                                         aria-label="Abrir <?= ntx_safe($tituloNotificacion) ?>"
                                     <?php endif; ?>>
                                <div class="ntx-icon" aria-hidden="true">
                                    <i class="fas <?= ntx_safe(ntx_icon($modulo), 'fa-bell') ?>"></i>
                                </div>

                                <div class="ntx-row-copy">
                                    <div class="ntx-row-top">
                                        <span class="ntx-row-title"><?= ntx_safe($notificacion['titulo'] ?? '') ?></span>
                                        <span class="ntx-badge state-<?= $estadoClass ?>"><?= ntx_safe(ntx_label($estado)) ?></span>
                                        <span class="ntx-badge sev-<?= $severidadClass ?>"><?= ntx_safe(ntx_label($severidad)) ?></span>
                                    </div>
                                    <p class="ntx-message"><?= ntx_safe($notificacion['mensaje'] ?? '') ?></p>
                                    <div class="ntx-meta">
                                        <span><i class="fas fa-layer-group"></i> <?= ntx_safe(ntx_label($modulo)) ?></span>
                                        <?php if ($requiereAccion): ?>
                                            <span><i class="fas fa-bolt"></i> Requiere accion</span>
                                        <?php endif; ?>
                                        <?php if ($esAutomatica): ?>
                                            <span><i class="fas fa-rotate"></i> Automatica</span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-clock"></i> <?= ntx_safe(ntx_date($notificacion['created_at'] ?? null)) ?></span>
                                    </div>
                                </div>

                                <?php if ($urlDestinoFinal !== ''): ?>
                                    <span class="ntx-arrow" aria-hidden="true">
                                        <i class="fas fa-arrow-up-right-from-square"></i>
                                    </span>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</main>

<script>
document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.ntx-row[data-notif-url]');
    if (!item || event.target.closest('a, button, input, select, textarea, label')) {
        return;
    }

    window.location.href = item.dataset.notifUrl;
});

document.addEventListener('keydown', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.ntx-row[data-notif-url]');
    if (!item || !['Enter', ' '].includes(event.key)) {
        return;
    }

    event.preventDefault();
    window.location.href = item.dataset.notifUrl;
});

(function () {
    'use strict';

    function showArchiveNotice(message, type) {
        if (window.PWA && typeof window.PWA.showToast === 'function') {
            window.PWA.showToast(message, type || 'info', 5200);
            return;
        }

        var toast = document.getElementById('ntxActionToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'ntxActionToast';
            toast.className = 'ntx-action-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }

        window.clearTimeout(toast._hideTimer);
        toast.textContent = message;
        requestAnimationFrame(function () {
            toast.classList.add('is-visible');
        });
        toast._hideTimer = window.setTimeout(function () {
            toast.classList.remove('is-visible');
        }, 7000);
    }

    function resetArchiveConfirm(form) {
        if (!form) return;

        delete form.dataset.confirmArchive;
        window.clearTimeout(form._confirmArchiveTimer);

        var button = form.querySelector('button[type="submit"]');
        if (button && button.dataset.originalHtml) {
            button.innerHTML = button.dataset.originalHtml;
            delete button.dataset.originalHtml;
        }
        if (button) {
            button.classList.remove('is-confirming');
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || form.dataset.archiveVisibleForm !== '1') {
            return;
        }

        if (form.dataset.confirmArchive === '1') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        form.dataset.confirmArchive = '1';
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.dataset.originalHtml = button.innerHTML;
            button.classList.add('is-confirming');
            button.innerHTML = '<i class="fas fa-check"></i><span>Confirmar archivo</span>';
        }

        showArchiveNotice('Se archivaran las notificaciones pendientes visibles. Podras consultarlas en Archivadas.', 'info');
        form._confirmArchiveTimer = window.setTimeout(function () {
            resetArchiveConfirm(form);
        }, 7000);
    }, true);
})();

(function () {
    'use strict';

    var panel = document.querySelector('[data-pwa-push-panel]');
    if (!panel || panel.dataset.ntxPushBound === '1') {
        return;
    }

    panel.dataset.ntxPushBound = '1';

    var configCache = null;
    var button = panel.querySelector('[data-pwa-push-toggle]');
    var testButton = panel.querySelector('[data-pwa-push-test]');
    var status = panel.querySelector('[data-pwa-push-status]');
    var label = panel.querySelector('[data-pwa-push-label]');

    if (status) {
        status.textContent = 'Inicializando controles de este dispositivo...';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function pushSupported() {
        return 'serviceWorker' in navigator &&
            'PushManager' in window &&
            'Notification' in window;
    }

    function isIosDevice() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
    }

    function isStandalonePwa() {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    }

    function setState(state, message) {
        panel.dataset.pushState = state;

        if (status) {
            status.textContent = message || '';
        }

        if (button) {
            button.disabled = ['loading', 'unsupported', 'blocked', 'unconfigured', 'disabled'].indexOf(state) !== -1;
            button.dataset.mode = state === 'enabled' ? 'disable' : 'enable';
        }

        if (label) {
            label.textContent = state === 'enabled'
                ? 'Desactivar en este dispositivo'
                : 'Activar en este dispositivo';
        }

        if (testButton) {
            testButton.hidden = state !== 'enabled';
            testButton.disabled = state !== 'enabled';
        }
    }

    function notify(message, type) {
        if (window.PWA && typeof window.PWA.showToast === 'function') {
            window.PWA.showToast(message, type || 'info', 5200);
            return;
        }

        if (status) {
            status.textContent = message;
        }
    }

    function base64UrlToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var output = new Uint8Array(rawData.length);

        for (var i = 0; i < rawData.length; i++) {
            output[i] = rawData.charCodeAt(i);
        }

        return output;
    }

    function jsonFetch(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }, options || {})).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'No se pudo completar la accion.');
                }

                return data;
            });
        });
    }

    function postJson(url, payload) {
        return jsonFetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken(),
            },
            body: JSON.stringify(payload || {}),
        });
    }

    function loadConfig() {
        if (configCache) {
            return Promise.resolve(configCache);
        }

        return jsonFetch(panel.dataset.publicKeyUrl).then(function (config) {
            configCache = config;
            return config;
        });
    }

    function readyRegistration() {
        return navigator.serviceWorker.ready.then(function (registration) {
            if (!registration || !registration.pushManager) {
                throw new Error('El Service Worker no esta listo para Push.');
            }

            return registration;
        });
    }

    function refresh() {
        if (!pushSupported()) {
            setState(
                'unsupported',
                isIosDevice() && !isStandalonePwa()
                    ? 'En iPhone debes agregar la app a pantalla de inicio para recibir avisos.'
                    : 'Este navegador no soporta notificaciones push PWA.'
            );
            return Promise.resolve();
        }

        setState('loading', 'Revisando estado de este dispositivo...');

        return loadConfig()
            .then(function (config) {
                if (!config.enabled || !config.public_key) {
                    setState(config.configured ? 'disabled' : 'unconfigured', config.message || 'Push no disponible.');
                    return null;
                }

                if (Notification.permission === 'denied') {
                    setState('blocked', 'El navegador bloqueo los permisos. Activalos desde la configuracion del sitio.');
                    return null;
                }

                return readyRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (subscription) {
                        setState(
                            subscription ? 'enabled' : 'available',
                            subscription
                                ? 'Este dispositivo ya recibe avisos del hotel.'
                                : (config.message || 'Puedes activar avisos en este dispositivo.')
                        );
                    });
                });
            })
            .catch(function (error) {
                setState('error', error.message || 'No se pudo revisar Push PWA.');
            });
    }

    function enablePush() {
        return loadConfig().then(function (config) {
            if (!config.enabled || !config.public_key) {
                throw new Error(config.message || 'Push no disponible.');
            }

            return Notification.requestPermission().then(function (permission) {
                if (permission !== 'granted') {
                    throw new Error('Permiso no concedido por el navegador.');
                }

                return readyRegistration().then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (existing) {
                        return existing || registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: base64UrlToUint8Array(config.public_key),
                        });
                    });
                });
            });
        }).then(function (subscription) {
            return postJson(panel.dataset.subscribeUrl, {
                subscription: subscription.toJSON(),
            });
        });
    }

    function disablePush() {
        return readyRegistration().then(function (registration) {
            return registration.pushManager.getSubscription();
        }).then(function (subscription) {
            if (!subscription) {
                return null;
            }

            return postJson(panel.dataset.unsubscribeUrl, {
                endpoint: subscription.endpoint,
            }).then(function () {
                return subscription.unsubscribe();
            });
        });
    }

    if (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var wasEnabled = panel.dataset.pushState === 'enabled';
            setState('loading', wasEnabled ? 'Desactivando avisos...' : 'Activando avisos...');

            (wasEnabled ? disablePush() : enablePush())
                .then(function () {
                    configCache = null;
                    notify(
                        wasEnabled
                            ? 'Notificaciones desactivadas en este dispositivo.'
                            : 'Notificaciones activadas en este dispositivo.',
                        'success'
                    );
                })
                .catch(function (error) {
                    notify(error.message || 'No se pudo cambiar Push PWA.', 'error');
                })
                .then(refresh);
        });
    }

    if (testButton) {
        testButton.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();

            setState('loading', 'Enviando prueba...');

            postJson(panel.dataset.testUrl, {})
                .then(function () {
                    notify('Prueba enviada. Revisa las notificaciones del dispositivo.', 'success');
                })
                .catch(function (error) {
                    notify(error.message || 'No se pudo enviar la prueba.', 'error');
                })
                .then(refresh);
        });
    }

    refresh();
})();
</script>
