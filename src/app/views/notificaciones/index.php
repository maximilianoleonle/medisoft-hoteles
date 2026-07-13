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

if (!function_exists('ntx_tiempo')) {
    function ntx_tiempo($value)
    {
        $timestamp = $value ? strtotime((string)$value) : false;
        if (!$timestamp) {
            return '-';
        }

        $dia = date('Y-m-d', $timestamp);
        if ($dia === date('Y-m-d')) {
            $diff = max(0, time() - $timestamp);
            if ($diff < 60) {
                return 'Justo ahora';
            }
            if ($diff < 3600) {
                return 'Hace ' . (int)floor($diff / 60) . ' min';
            }
            $horas = (int)floor($diff / 3600);
            return 'Hace ' . $horas . ($horas === 1 ? ' hora' : ' horas');
        }

        $hora = date('g:i A', $timestamp);
        if ($dia === date('Y-m-d', strtotime('-1 day'))) {
            return $hora;
        }

        $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return (int)date('j', $timestamp) . ' ' . $meses[(int)date('n', $timestamp) - 1] . ', ' . $hora;
    }
}

if (!function_exists('ntx_grupo_dia')) {
    function ntx_grupo_dia($value)
    {
        $timestamp = $value ? strtotime((string)$value) : false;
        if (!$timestamp) {
            return 'Anteriores';
        }

        $dia = date('Y-m-d', $timestamp);
        if ($dia === date('Y-m-d')) {
            return 'Hoy';
        }

        return $dia === date('Y-m-d', strtotime('-1 day')) ? 'Ayer' : 'Anteriores';
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

$nuevas = (int)($resumen['nuevas'] ?? 0);
$pendientesArchivables = (int)($resumen['pendientes'] ?? $nuevas);
$resueltas = (int)($resumen['resueltas'] ?? 0);
$historial = (int)($resumen['historial'] ?? ($resueltas + ($resumen['descartadas'] ?? 0)));

$estadoTabs = [
    'activas' => ['label' => 'Pendientes', 'count' => $pendientesArchivables],
    'resuelta' => ['label' => 'Atendidas', 'count' => $resueltas],
    'descartada' => ['label' => 'Archivadas', 'count' => max(0, $historial - $resueltas)],
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

// ── Fecha del dia y agrupacion Hoy / Ayer / Anteriores ──
$diasEs = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
$mesesEs = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$fechaKicker = $diasEs[(int)date('w')] . ' ' . (int)date('j') . ', ' . $mesesEs[(int)date('n') - 1];

$gruposDia = ['Hoy' => [], 'Ayer' => [], 'Anteriores' => []];
foreach ($notificaciones as $ntxItem) {
    $gruposDia[ntx_grupo_dia($ntxItem['created_at'] ?? null)][] = $ntxItem;
}
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

/* ═══ Notificaciones: bandeja limpia (mismo lenguaje en PC y movil) ═══
   Columna unica: fecha + titulo con punto de no-leidas, tabs de texto,
   grupos por dia y tarjetas suaves. El ruido de la version anterior
   (stats, filtros de origen, paneles laterales) se retiro a proposito. */
.ntx-page {
    --ntx-primary: var(--brand-primary, #1f3f46);
    --ntx-accent: var(--brand-accent, #b58a3c);
    --ntx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --ntx-ink: #232a36;
    --ntx-muted: #9aa3b2;
    --ntx-line: color-mix(in srgb, var(--ntx-primary) 9%, #e9e2d7);
    --ntx-tone-blue: #3f7891;
    --ntx-tone-sage: #2f8a70;
    --ntx-tone-amber: #b98a35;
    --ntx-tone-coral: #b66b5f;
    --ntx-tone-indigo: #6e6aa9;
    --ntx-tone-olive: #6f8547;
    --ntx-tone-slate: #58728f;
    --ntx-focus: color-mix(in srgb, var(--ntx-accent) 22%, transparent);
    min-height: 100vh;
    color: #344054;
    background: color-mix(in srgb, var(--ntx-accent) 3%, #fcfbf9);
    font-family: var(--ntx-sans);
}

.ntx-shell {
    width: min(720px, calc(100% - 48px));
    margin: 0 auto;
    padding: 22px 0 72px;
}

.ntx-alert {
    margin-bottom: 16px;
    padding: 12px 14px;
    border: 1px solid var(--ntx-line);
    border-radius: 12px;
    background: rgba(255, 255, 255, .9);
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

/* ── Header editorial: fecha + titulo con punto de no-leidas ── */
.ntxm-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 16px;
    padding: 6px 2px 0;
}

.ntxm-lockup { min-width: 0; }

.ntxm-date {
    margin: 0 0 7px;
    color: var(--ntx-muted);
    font-size: .74rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.ntxm-title {
    margin: 0;
    color: var(--ntx-ink);
    font-size: 2.05rem;
    line-height: 1.05;
    font-weight: 700;
    letter-spacing: -.02em;
}

.ntxm-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    margin: 3px 0 0 5px;
    border-radius: 999px;
    background: var(--ntx-accent);
    vertical-align: top;
}

/* ── Acciones discretas (solo PC): archivar + push del dispositivo ── */
.ntxm-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 5px;
    flex: 0 0 auto;
}

.ntxm-actions form { margin: 0; }

.ntxm-act {
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: 1px solid var(--ntx-line);
    border-radius: 999px;
    background: #fff;
    color: #5b6478;
    padding: 0 14px;
    font-family: inherit;
    font-size: .8rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    white-space: nowrap;
    transition: transform .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.ntxm-act[hidden] { display: none; }

.ntxm-act:hover:not([disabled]) {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--ntx-accent) 34%, var(--ntx-line));
    background: color-mix(in srgb, var(--ntx-accent) 6%, #fff);
    color: var(--ntx-ink);
}

.ntxm-act:active:not([disabled]) { transform: translateY(0); }

.ntxm-act[disabled] {
    opacity: .55;
    cursor: default;
}

.ntxm-act.is-confirming {
    border-color: #f3d08a;
    background: #fff8e8;
    color: #8a5b12;
}

.ntxm-push {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.ntxm-push-status {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

/* ── Tabs de estado: texto plano con subrayado de marca ── */
.ntxm-tabs {
    display: flex;
    gap: 18px;
    margin: 16px 2px 2px;
    overflow-x: auto;
    scrollbar-width: none;
}
.ntxm-tabs::-webkit-scrollbar { display: none; }

.ntxm-tab {
    flex: 0 0 auto;
    padding: 4px 0 7px;
    color: var(--ntx-muted);
    font-size: .84rem;
    font-weight: 600;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    white-space: nowrap;
    -webkit-tap-highlight-color: transparent;
    transition: color .16s ease;
}

.ntxm-tab:hover { color: var(--ntx-ink); }

.ntxm-tab b {
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    opacity: .75;
}

.ntxm-tab.is-active {
    color: var(--ntx-ink);
    border-bottom-color: var(--ntx-accent);
}

.ntxm-tab:focus-visible,
.ntxm-act:focus-visible,
.ntxm-card:focus-visible {
    outline: 3px solid var(--ntx-focus);
    outline-offset: 2px;
}

/* ── Grupos por dia ── */
.ntxm-group {
    margin: 22px 2px 10px;
    color: #a3abb9;
    font-size: .84rem;
    font-weight: 600;
}

.ntxm-list {
    display: grid;
    gap: 12px;
}

/* ── Tarjeta de aviso ── */
.ntxm-card {
    --ava: var(--ntx-tone-slate);
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 13px;
    align-items: start;
    padding: 15px 16px;
    border-radius: 20px;
    background: color-mix(in srgb, var(--ntx-primary) 4%, #fff);
    -webkit-tap-highlight-color: transparent;
    transition: transform .16s ease, background .16s ease, box-shadow .16s ease;
}

.ntxm-card[data-notif-url] { cursor: pointer; }
.ntxm-card[data-notif-url]:active { transform: scale(.985); }

.ntxm-card.state-resuelta,
.ntxm-card.state-descartada { opacity: .78; }

.ntxm-ava {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    font-size: .95rem;
    background: color-mix(in srgb, var(--ava) 14%, #fff);
    color: color-mix(in srgb, var(--ava) 74%, #5b6478);
}

.ntxm-card.mod-caja { --ava: var(--ntx-tone-amber); }
.ntxm-card.mod-habitaciones { --ava: var(--ntx-tone-sage); }
.ntxm-card.mod-facturacion { --ava: var(--ntx-tone-indigo); }
.ntxm-card.mod-inventario { --ava: var(--ntx-tone-olive); }
.ntxm-card.mod-reservaciones { --ava: var(--ntx-tone-blue); }
.ntxm-card.sev-alta,
.ntxm-card.sev-critica { --ava: var(--ntx-tone-coral); }

.ntxm-body {
    min-width: 0;
    padding-top: 2px;
}

.ntxm-card-title {
    margin: 0;
    color: #2a3240;
    font-size: .93rem;
    line-height: 1.35;
    font-weight: 650;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ntxm-card-msg {
    margin: 3px 0 0;
    color: #7d8595;
    font-size: .8rem;
    line-height: 1.42;
    font-weight: 500;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.ntxm-card-time {
    display: flex;
    align-items: center;
    gap: 6px;
    margin: 9px 0 0;
    color: #a8b0bd;
    font-size: .76rem;
    font-weight: 600;
}

/* No leida: lavado del acento de la marca + burbuja llena (como la
   tarjeta destacada de la referencia) */
.ntxm-card.is-new {
    background: color-mix(in srgb, var(--ntx-accent) 13%, #fff);
}

.ntxm-card.is-new .ntxm-ava {
    background: var(--ava);
    color: #fffdf8;
}

.ntxm-card.is-new .ntxm-card-time {
    color: color-mix(in srgb, var(--ntx-accent) 62%, #6b7280);
}

/* ── Estado vacio ── */
.ntxm-empty {
    padding: 64px 24px;
    text-align: center;
}

.ntxm-empty i {
    width: 54px;
    height: 54px;
    display: inline-grid;
    place-items: center;
    border-radius: 999px;
    background: color-mix(in srgb, var(--ntx-accent) 10%, #fff);
    color: color-mix(in srgb, var(--ntx-accent) 70%, #667085);
    font-size: 1.25rem;
}

.ntxm-empty h3 {
    margin: 16px 0 6px;
    color: #2a3240;
    font-size: 1.02rem;
    font-weight: 650;
}

.ntxm-empty p {
    max-width: 340px;
    margin: 0 auto;
    color: var(--ntx-muted);
    font-size: .84rem;
    line-height: 1.5;
    font-weight: 500;
}

/* ── Toast de confirmacion de archivo ── */
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

/* ── PC: mas aire y hover en tarjetas ── */
@media (min-width: 769px) {
    .ntxm-title { font-size: 2.5rem; }
    .ntxm-group { margin: 26px 2px 12px; font-size: .86rem; }
    .ntxm-card { padding: 17px 18px; gap: 14px; }

    .ntxm-card[data-notif-url]:hover {
        transform: translateY(-1px);
        background: color-mix(in srgb, var(--ntx-primary) 6%, #fff);
        box-shadow: 0 12px 26px -22px color-mix(in srgb, var(--ntx-primary) 45%, transparent);
    }

    .ntxm-card.is-new[data-notif-url]:hover {
        background: color-mix(in srgb, var(--ntx-accent) 17%, #fff);
    }
}

/* ── Movil: pantalla completa, sin acciones de PC ── */
@media (max-width: 768px) {
    .ntx-shell {
        width: 100%;
        margin: 0;
        padding: 4px 18px calc(96px + env(safe-area-inset-bottom, 0px));
    }

    .ntxm-head { padding: 6px 2px 0; }
    .ntxm-actions { display: none; }
}
</style>

<main class="ntx-page">
    <div class="ntx-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <?php if ($mensajeFlash): ?>
            <div class="ntx-alert <?= ntx_class($mensajeFlash['tipo'] ?? 'info', 'info') ?>">
                <?= ntx_safe($mensajeFlash['texto'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="ntxm" aria-label="Notificaciones">
            <header class="ntxm-head">
                <div class="ntxm-lockup">
                    <p class="ntxm-date"><?= ntx_safe($fechaKicker) ?></p>
                    <h1 class="ntxm-title">Notificaciones<?php if ($nuevas > 0): ?><span class="ntxm-dot" role="img" aria-label="<?= $nuevas ?> sin abrir"></span><?php endif; ?></h1>
                </div>

                <div class="ntxm-actions">
                    <?php if ($tablaDisponible && $estadoFiltro === 'activas' && $pendientesArchivables > 0): ?>
                        <form method="POST"
                              action="<?= url('notificaciones/marcar-todas-leidas') ?>"
                              data-archive-visible-form="1">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="archivar_pendientes">
                            <button type="submit" class="ntxm-act">
                                <i class="fas fa-box-archive"></i>
                                <span>Archivar visibles</span>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="ntxm-push"
                         data-pwa-push-panel
                         data-public-key-url="<?= url('api/pwa-push/public-key') ?>"
                         data-subscribe-url="<?= url('api/pwa-push/subscribe') ?>"
                         data-unsubscribe-url="<?= url('api/pwa-push/unsubscribe') ?>"
                         data-test-url="<?= url('api/pwa-push/test') ?>">
                        <span class="ntxm-push-status" data-pwa-push-status aria-live="polite"></span>
                        <button type="button" class="ntxm-act" data-pwa-push-toggle>
                            <i class="fas fa-bell"></i>
                            <span data-pwa-push-label>Avisos en este dispositivo</span>
                        </button>
                        <button type="button" class="ntxm-act" data-pwa-push-test hidden title="Enviar prueba a este dispositivo">
                            <i class="fas fa-paper-plane"></i>
                            <span>Prueba</span>
                        </button>
                    </div>
                </div>
            </header>

            <nav class="ntxm-tabs" aria-label="Cambiar estado">
                <?php foreach ($estadoTabs as $estado => $tab): ?>
                    <a class="ntxm-tab <?= $estadoFiltro === $estado ? 'is-active' : '' ?>"
                       href="<?= ntx_filter_url(['estado' => $estado]) ?>">
                        <?= ntx_safe($tab['label']) ?><?php if ((int)$tab['count'] > 0): ?> <b><?= (int)$tab['count'] ?></b><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if (!$tablaDisponible): ?>
                <div class="ntxm-empty">
                    <i class="fas fa-bell-slash"></i>
                    <h3>Notificaciones no disponibles</h3>
                    <p>La tabla de notificaciones todavia no esta instalada para este hotel.</p>
                </div>
            <?php elseif (empty($notificaciones)): ?>
                <div class="ntxm-empty">
                    <i class="fas <?= ntx_safe($emptyState['icon'] ?? 'fa-circle-check') ?>"></i>
                    <h3><?= ntx_safe($emptyState['title'] ?? 'Bandeja limpia') ?></h3>
                    <p><?= ntx_safe($emptyState['message'] ?? 'No hay notificaciones para esta vista.') ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($gruposDia as $grupoLabel => $grupoItems): ?>
                    <?php if (empty($grupoItems)) continue; ?>
                    <p class="ntxm-group"><?= ntx_safe($grupoLabel) ?></p>
                    <div class="ntxm-list">
                        <?php foreach ($grupoItems as $notificacion): ?>
                            <?php
                                $id = (int)($notificacion['id'] ?? 0);
                                $estadoClass = ntx_class($notificacion['estado'] ?? 'nueva', 'nueva');
                                $severidadClass = ntx_class($notificacion['severidad'] ?? 'info', 'info');
                                $modulo = (string)($notificacion['modulo'] ?? 'sistema');
                                $urlDestino = trim((string)($notificacion['url'] ?? ''));
                                $urlDestinoFinal = ($urlDestino !== '' && $id > 0) ? url('notificaciones/' . $id . '/abrir') : '';
                                $tituloNotificacion = (string)($notificacion['titulo'] ?? 'Notificacion');
                                $mensajeNotificacion = trim((string)($notificacion['mensaje'] ?? ''));
                            ?>
                            <article class="ntxm-card state-<?= $estadoClass ?> sev-<?= $severidadClass ?> mod-<?= ntx_class($modulo, 'sistema') ?><?= $estadoClass === 'nueva' ? ' is-new' : '' ?>"
                                     <?php if ($urlDestinoFinal !== ''): ?>
                                         data-notif-url="<?= htmlspecialchars($urlDestinoFinal, ENT_QUOTES, 'UTF-8') ?>"
                                         role="link"
                                         tabindex="0"
                                         aria-label="Abrir <?= ntx_safe($tituloNotificacion) ?>"
                                     <?php endif; ?>>
                                <span class="ntxm-ava" aria-hidden="true">
                                    <i class="fas <?= ntx_safe(ntx_icon($modulo), 'fa-bell') ?>"></i>
                                </span>
                                <div class="ntxm-body">
                                    <p class="ntxm-card-title"><?= ntx_safe($tituloNotificacion) ?></p>
                                    <?php if ($mensajeNotificacion !== ''): ?>
                                        <p class="ntxm-card-msg"><?= ntx_safe($mensajeNotificacion) ?></p>
                                    <?php endif; ?>
                                    <p class="ntxm-card-time">
                                        <i class="far fa-clock" aria-hidden="true"></i>
                                        <?= ntx_safe(ntx_tiempo($notificacion['created_at'] ?? null)) ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<script>
document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.ntxm-card[data-notif-url]');
    if (!item || event.target.closest('a, button, input, select, textarea, label')) {
        return;
    }

    window.location.href = item.dataset.notifUrl;
});

document.addEventListener('keydown', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.ntxm-card[data-notif-url]');
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
            button.title = message || '';
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
