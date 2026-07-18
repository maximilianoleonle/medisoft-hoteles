<?php
if (!function_exists('tlm_report_safe')) {
    function tlm_report_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_report_num')) {
    function tlm_report_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('tlm_report_date')) {
    function tlm_report_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_report_estado_meta')) {
    function tk_report_estado_meta($estado): array
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'asignada'   => ['Asignada', 'is-asignada', 'fa-user-check'],
            'en_proceso' => ['En proceso', 'is-proceso', 'fa-spinner'],
            'completada' => ['Completada', 'is-completada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

if (!function_exists('tk_report_workers_text')) {
    function tk_report_workers_text(array $tarea): string
    {
        foreach (['trabajadores_nombres', 'trabajadores_asignados', 'trabajador_nombre'] as $campo) {
            $texto = trim((string)($tarea[$campo] ?? ''));
            if ($texto !== '') {
                return $texto;
            }
        }

        return '';
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$prioridades = is_array($reporte['prioridades'] ?? null) ? $reporte['prioridades'] : [];
$riesgos = is_array($reporte['riesgos'] ?? null) ? $reporte['riesgos'] : [];
$porTrabajador = is_array($reporte['por_trabajador'] ?? null) ? $reporte['por_trabajador'] : [];
$porHabitacion = is_array($reporte['por_habitacion'] ?? null) ? $reporte['por_habitacion'] : [];
$recientes = is_array($reporte['recientes'] ?? null) ? $reporte['recientes'] : [];
$eventosRecientes = is_array($reporte['eventos_recientes'] ?? null) ? $reporte['eventos_recientes'] : [];

$categoriaLabels = [
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
$prioridadLabels = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];
?>

<style>
.tk-report {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 58%, var(--tk-brand));
    --tk-ivory: #F5F5F7; --tk-ivory-2: #FAFAFC;
    --tk-surface: #FFFFFF; --tk-surface-warm: #F5F5F7;
    --tk-border: color-mix(in srgb, var(--tk-brand) 6%, #E9E1D6);
    --tk-ring: color-mix(in srgb, var(--tk-gold) 32%, transparent);
    --tk-text: color-mix(in srgb, var(--tk-brand) 46%, #707B8C);
    --tk-muted: #8791A2;
    --tk-heading: color-mix(in srgb, var(--tk-brand) 66%, #566172);
    --tk-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-success: #1E9E63; --tk-success-bg: #E7F4EC;
    --tk-warning: #C2841C; --tk-warning-bg: #FAF0DC;
    --tk-danger: #B4392B; --tk-danger-bg: #F8EAE5;
    --tk-info: #2F77E0; --tk-info-bg: #E6EFFC;
    --tk-proc: #0E8A8A; --tk-proc-bg: #E2F4F4;
    --tk-view-accent: var(--tk-proc);
    --tk-view-soft: color-mix(in srgb, var(--tk-proc) 8%, #FFFFFF);
    --tk-view-ink: color-mix(in srgb, var(--tk-proc) 60%, var(--tk-brand));
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tk-report .tk-shell { display: grid; gap: 14px; min-width: 0; width: 100%; max-width: 100%; }

/* ── Hero · mismo lockup del tablero ── */
.tk-report .tk-hero-section {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr);
    align-items: start !important;
    justify-content: stretch !important;
    gap: 12px !important;
    min-width: 0; max-width: 100%; width: 100%;
    box-sizing: border-box;
    padding: 2px 0 6px;
}
.tk-report .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); grid-template-rows: auto auto; align-items: center; column-gap: 14px; justify-self: start; min-width: 0; max-width: min(100%, 780px); text-align: left; }
.tk-report .tk-title-lockup > .tk-hero-icon { grid-column: 1; grid-row: 1; }
.tk-report .tk-title-lockup > .tk-title-head { grid-column: 2; grid-row: 1; min-width: 0; }
.tk-report .tk-title-lockup > .tk-title-rest { grid-column: 2; grid-row: 2; min-width: 0; }
.tk-report .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-proc), var(--tk-brand) 56%, color-mix(in srgb, var(--tk-brand) 66%, var(--tk-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-report .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-report .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 650; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; overflow-wrap: anywhere; }
.tk-report .tk-subtitle { max-width: 46rem; margin: 9px 0 0; color: var(--tk-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }
.tk-report .tk-view-chip { display: inline-flex; align-items: center; gap: 7px; width: fit-content; margin-top: 10px; padding: 6px 10px; border-radius: 999px; background: var(--tk-view-soft); border: 1px solid color-mix(in srgb, var(--tk-view-accent) 24%, #fff); color: var(--tk-view-ink); font-size: .72rem; font-weight: 650; line-height: 1; }
.tk-report .tk-hero-actions { display: flex !important; flex-wrap: wrap; align-items: center; justify-content: flex-start; justify-self: start; gap: 8px; min-width: 0; max-width: 100%; }

.tk-report .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--tk-border); background: var(--tk-surface); color: var(--tk-muted); font-weight: 650; font-size: .88rem; line-height: 1; text-decoration: none; white-space: nowrap;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease; }
.tk-report .tk-btn:hover { transform: translateY(-1px); border-color: var(--tk-gold-line); color: var(--tk-gold-ink); }
.tk-report .tk-btn:active { transform: translateY(0) scale(.98); }
.tk-report .tk-btn:focus-visible { outline: 3px solid var(--tk-ring); outline-offset: 2px; }

/* ── Resumen · tarjetas translúcidas con acento lateral ── */
.tk-report .tk-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.tk-report .tk-summary-item { position: relative; overflow: hidden; background: rgba(255,255,255,.82); border: 1px solid var(--tk-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18); }
.tk-report .tk-summary-item::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: linear-gradient(180deg, color-mix(in srgb, var(--tk-view-accent) 72%, #fff), color-mix(in srgb, var(--tk-brand) 50%, var(--tk-view-accent))); opacity: .62; }
.tk-report .tk-summary-label { color: var(--tk-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.tk-report .tk-summary-value { margin-top: 2px; font-family: var(--tk-serif); font-size: 1.7rem; font-weight: 650; line-height: 1.1; color: var(--tk-heading); font-variant-numeric: tabular-nums; }
.tk-report .tk-summary-value.is-warning { color: color-mix(in srgb, var(--tk-warning) 68%, var(--tk-text)); }
.tk-report .tk-summary-value.is-info { color: color-mix(in srgb, var(--tk-info) 66%, var(--tk-text)); }
.tk-report .tk-summary-value.is-progress { color: color-mix(in srgb, var(--tk-proc) 66%, var(--tk-text)); }
.tk-report .tk-summary-value.is-success { color: color-mix(in srgb, var(--tk-success) 68%, var(--tk-text)); }

/* ── Paneles ── */
.tk-report .tk-stack { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.tk-report .tk-panel { background: rgba(255,255,255,.86); border: 1px solid var(--tk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); overflow: hidden; }
.tk-report .tk-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--tk-border); background: linear-gradient(90deg, var(--tk-view-soft), transparent 72%); }
.tk-report .tk-panel-head > div { min-width: 0; }
.tk-report .tk-panel-title { font-family: var(--tk-serif); font-size: 1.02rem; font-weight: 650; color: var(--tk-heading); margin: 0; line-height: 1.2; }
.tk-report .tk-panel-sub { font-size: .76rem; color: var(--tk-muted); margin: 3px 0 0; }

.tk-report .tk-list { padding: 6px 18px; }
.tk-report .tk-list-row { display: flex; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid var(--tk-border); font-size: .88rem; align-items: center; }
.tk-report .tk-list-row:last-child { border-bottom: 0; }
.tk-report .tk-list-row strong { color: var(--tk-heading); font-weight: 650; font-variant-numeric: tabular-nums; }
.tk-report .tk-list-row.is-risk strong { color: color-mix(in srgb, var(--tk-danger) 72%, var(--tk-text)); }
.tk-report .tk-list-row > span:not(.tk-dot) { color: var(--tk-muted); font-weight: 560; }
.tk-report .tk-list-row a { color: var(--tk-info); text-decoration: none; font-weight: 650; }
.tk-report .tk-list-row a:hover { text-decoration: underline; text-underline-offset: 3px; }
.tk-report .tk-dot { display: inline-flex; align-items: center; gap: 8px; color: var(--tk-text); font-weight: 560; }
.tk-report .tk-dot i { width: 16px; text-align: center; }

.tk-report .tk-table-wrap { overflow-x: auto; }
.tk-report .tk-table { width: 100%; border-collapse: collapse; min-width: 760px; font-size: .85rem; }
.tk-report .tk-table thead { background: var(--tk-surface-warm); border-bottom: 1px solid var(--tk-border); }
.tk-report .tk-table th { padding: 12px 16px; color: var(--tk-muted); font-size: .68rem; font-weight: 650; letter-spacing: .07em; text-transform: uppercase; text-align: left; }
.tk-report .tk-table td { padding: 13px 16px; border-bottom: 1px solid var(--tk-border); vertical-align: top; }
.tk-report .tk-table tbody tr { transition: background .16s ease; }
.tk-report .tk-table tbody tr:last-child td { border-bottom: 0; }
.tk-report .tk-table tbody tr:hover { background: rgba(251,248,242,.72); }
.tk-report .tk-link { font-weight: 650; color: var(--tk-heading); text-decoration: none; }
.tk-report .tk-link:hover { text-decoration: underline; text-decoration-color: var(--tk-gold); text-underline-offset: 3px; }
.tk-report .tk-sub { color: var(--tk-muted); font-size: .74rem; margin-top: 3px; }

.tk-report .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 650; border: 1px solid transparent; }
.tk-report .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 72%, var(--tk-text)); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 26%, #fff); }
.tk-report .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 70%, var(--tk-text)); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 24%, #fff); }
.tk-report .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 70%, var(--tk-text)); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 24%, #fff); }
.tk-report .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 70%, var(--tk-text)); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 24%, #fff); }
.tk-report .tk-badge.is-cancelada, .tk-report .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }

.tk-report .tk-empty { padding: 36px 18px; text-align: center; color: var(--tk-muted); }
.tk-report .tk-empty strong { display: block; color: var(--tk-heading); font-size: 1.02rem; margin-bottom: 6px; font-weight: 650; }
.tk-report .tk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--tk-gold-soft); border: 1px solid var(--tk-gold-line); border-radius: 16px; }
.tk-report .tk-notice i { color: var(--tk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.tk-report .tk-notice strong { color: var(--tk-heading); display: block; margin-bottom: 2px; font-weight: 650; }
.tk-report .tk-notice p { color: var(--tk-muted); font-size: .88rem; margin: 0; }

@media (min-width: 1024px) {
    .tk-report .tk-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 16px 28px !important;
        padding-bottom: 10px;
    }
    .tk-report .tk-hero-actions { justify-content: flex-end; justify-self: end; min-width: max-content; }
}
@media (max-width: 900px) {
    .tk-report .tk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tk-report .tk-stack { grid-template-columns: 1fr; }
    .tk-report .tk-title { font-size: 1.9rem; }
}

/* ════════════════════════════════════════════════════════════════════
   MÓVIL COMPACTO · misma estética que el índice de tareas (≤767px)
   ════════════════════════════════════════════════════════════════════ */
@media (max-width: 767px) {
    .tk-report {
        --tk-mobile-ink: color-mix(in srgb, var(--tk-brand) 62%, #6F7784);
        --tk-mobile-text: color-mix(in srgb, var(--tk-brand) 42%, #778394);
        --tk-mobile-muted: #98A2B3;
        padding: 10px 12px 18px !important;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--tk-proc) 12%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%);
    }
    .tk-report .tk-shell { gap: 10px; }

    /* Hero → tarjeta oscura */
    .tk-report .tk-hero-section {
        position: relative;
        overflow: hidden;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px !important;
        min-height: 120px;
        margin: 0;
        padding: 16px 14px 14px;
        border-radius: 22px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 14%, rgba(255,255,255,.16), transparent 92px),
            linear-gradient(135deg, color-mix(in srgb, var(--tk-brand) 92%, #000) 0%, color-mix(in srgb, var(--tk-proc) 44%, var(--tk-brand)) 100%);
        box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--tk-brand) 72%, transparent);
    }
    .tk-report .tk-hero-section::after {
        content: "";
        position: absolute;
        right: -44px; top: -44px;
        width: 150px; height: 150px;
        border-radius: 999px;
        background: rgba(255,255,255,.10);
        pointer-events: none;
    }
    .tk-report .tk-title-lockup {
        position: relative; z-index: 1;
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px; align-items: start;
        max-width: none;
    }
    .tk-report .tk-hero-icon {
        width: 42px; height: 42px;
        border-radius: 14px; font-size: 1rem;
        background: rgba(255,255,255,.16);
        color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: none;
        backdrop-filter: blur(10px);
    }
    .tk-report .tk-kicker { color: rgba(255,255,255,.76); font-size: .62rem; letter-spacing: .14em; }
    .tk-report .tk-title { color: #fff; font-size: 1.62rem; line-height: .98; text-shadow: 0 8px 22px rgba(0,0,0,.28); }
    .tk-report .tk-subtitle {
        display: -webkit-box;
        max-width: none;
        margin-top: 6px;
        color: rgba(255,255,255,.82);
        font-size: .73rem;
        line-height: 1.35;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .tk-report .tk-view-chip { margin-top: 8px; background: rgba(255,255,255,.14); border-color: rgba(255,255,255,.26); color: #fff; }
    .tk-report .tk-hero-actions { display: none !important; } /* la flecha flotante toma el relevo */

    /* Resumen → tira horizontal */
    .tk-report .tk-summary {
        display: flex;
        grid-template-columns: none;
        gap: 8px;
        margin: 0 -12px;
        padding: 0 12px 2px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .tk-report .tk-summary::-webkit-scrollbar { width: 0; height: 0; }
    .tk-report .tk-summary-item {
        flex: 0 0 118px;
        min-height: 70px;
        padding: 10px 11px;
        border-radius: 16px;
        background: rgba(255,255,255,.74);
        border-color: color-mix(in srgb, var(--tk-brand) 5%, #ECE4D8);
        box-shadow: 0 12px 22px -26px rgba(27,39,70,.24);
    }
    .tk-report .tk-summary-label { color: var(--tk-mobile-muted); font-size: .58rem; letter-spacing: .055em; white-space: nowrap; }
    .tk-report .tk-summary-value { margin-top: 5px; font-size: 1.18rem; color: var(--tk-mobile-ink); white-space: nowrap; }

    /* Paneles compactos */
    .tk-report .tk-stack { grid-template-columns: 1fr; gap: 10px; }
    .tk-report .tk-panel {
        border-radius: 18px;
        border-color: color-mix(in srgb, var(--tk-brand) 5%, #ECE4D8);
        box-shadow: 0 16px 28px -28px rgba(27,39,70,.28);
    }
    .tk-report .tk-panel-head { padding: 12px 14px; }
    .tk-report .tk-panel-title { font-size: .95rem; color: var(--tk-mobile-ink); }
    .tk-report .tk-panel-sub { font-size: .72rem; }
    .tk-report .tk-list { padding: 4px 14px; }
    .tk-report .tk-list-row { padding: 10px 0; font-size: .84rem; }
    .tk-report .tk-table { min-width: 620px; font-size: .8rem; }
    .tk-report .tk-table th, .tk-report .tk-table td { padding: 10px 12px; }
    .tk-report .tk-empty { padding: 28px 16px; }
}
@media (prefers-reduced-motion: reduce) {
    .tk-report * { transition-duration: .01ms !important; scroll-behavior: auto !important; }
}
</style>

<div class="tk-report tk-report--insights p-4 sm:p-6">
    <div class="tk-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="tk-hero-section">
            <div class="tk-title-lockup">
                <div class="tk-hero-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="tk-title-head">
                    <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                    <h1 class="tk-title">Reporte de tareas</h1>
                </div>
                <div class="tk-title-rest">
                    <p class="tk-subtitle">Resumen de tus tareas por estado, prioridad, categor&iacute;a, trabajador y habitaci&oacute;n. Solo para consultar.</p>
                    <div class="tk-view-chip"><i class="fas fa-chart-line"></i> Vista de an&aacute;lisis</div>
                </div>
            </div>
            <div class="tk-hero-actions">
                <a class="tk-btn ms-back-legacy" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="tk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para ver el reporte.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="tk-summary">
                <div class="tk-summary-item"><p class="tk-summary-label">Total</p><p class="tk-summary-value"><?= tlm_report_num($resumen['total'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Pendientes</p><p class="tk-summary-value is-warning"><?= tlm_report_num($resumen['pendiente'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Asignadas</p><p class="tk-summary-value is-info"><?= tlm_report_num($resumen['asignada'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">En proceso</p><p class="tk-summary-value is-progress"><?= tlm_report_num($resumen['en_proceso'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Completadas</p><p class="tk-summary-value is-success"><?= tlm_report_num($resumen['completada'] ?? 0) ?></p></div>
            </section>

            <div class="tk-stack">
                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <div>
                            <h2 class="tk-panel-title">Alertas</h2>
                            <p class="tk-panel-sub">Tareas que necesitan atenci&oacute;n.</p>
                        </div>
                    </div>
                    <div class="tk-list">
                        <div class="tk-list-row is-risk"><span class="tk-dot"><i class="fas fa-triangle-exclamation" style="color:var(--tk-danger)"></i> Vencidas</span><strong><?= tlm_report_num($riesgos['vencidas'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-clock" style="color:var(--tk-warning)"></i> Vencen en 24 h</span><strong><?= tlm_report_num($riesgos['proximas_24h'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-user-slash" style="color:var(--tk-muted)"></i> Activas sin asignar</span><strong><?= tlm_report_num($riesgos['sin_asignar_activas'] ?? 0) ?></strong></div>
                    </div>
                </section>

                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <div>
                            <h2 class="tk-panel-title">Prioridades y categor&iacute;as</h2>
                            <p class="tk-panel-sub">C&oacute;mo se reparten tus tareas.</p>
                        </div>
                    </div>
                    <div class="tk-list">
                        <?php foreach ($prioridadLabels as $key => $label): ?>
                            <div class="tk-list-row"><span><?= tlm_report_safe($label) ?></span><strong><?= tlm_report_num($prioridades[$key] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-broom" style="color:var(--tk-muted)"></i> Limpieza</span><strong><?= tlm_report_num($resumen['limpieza'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-screwdriver-wrench" style="color:var(--tk-muted)"></i> Mantenimiento</span><strong><?= tlm_report_num($resumen['mantenimiento'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-list-check" style="color:var(--tk-muted)"></i> General</span><strong><?= tlm_report_num($resumen['general'] ?? 0) ?></strong></div>
                    </div>
                </section>
            </div>

            <div class="tk-stack">
                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <div>
                            <h2 class="tk-panel-title">Carga por trabajador</h2>
                            <p class="tk-panel-sub">Tareas a cargo de cada persona.</p>
                        </div>
                    </div>
                    <?php if (empty($porTrabajador)): ?>
                        <div class="tk-empty"><strong>Sin trabajadores con tareas</strong>Nadie tiene tareas asignadas todav&iacute;a.</div>
                    <?php else: ?>
                        <div class="tk-list">
                            <?php foreach ($porTrabajador as $row): ?>
                                <div class="tk-list-row">
                                    <a href="<?= url('trabajadores/' . (int)($row['trabajador_id'] ?? 0)) ?>"><?= tlm_report_safe($row['trabajador_nombre'] ?? null) ?></a>
                                    <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <div>
                            <h2 class="tk-panel-title">Carga por habitaci&oacute;n</h2>
                            <p class="tk-panel-sub">Tareas relacionadas con cada habitaci&oacute;n.</p>
                        </div>
                    </div>
                    <?php if (empty($porHabitacion)): ?>
                        <div class="tk-empty"><strong>Sin habitaciones con tareas</strong>No hay tareas ligadas a una habitaci&oacute;n.</div>
                    <?php else: ?>
                        <div class="tk-list">
                            <?php foreach ($porHabitacion as $row): ?>
                                <div class="tk-list-row">
                                    <a href="<?= url('habitaciones/' . (int)($row['habitacion_id'] ?? 0)) ?>">Habitaci&oacute;n <?= tlm_report_safe($row['habitacion_numero'] ?? null) ?></a>
                                    <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="tk-panel">
                <div class="tk-panel-head">
                    <div>
                        <h2 class="tk-panel-title">Tareas recientes</h2>
                        <p class="tk-panel-sub">Las &uacute;ltimas tareas actualizadas, con enlace al detalle.</p>
                    </div>
                </div>
                <?php if (empty($recientes)): ?>
                    <div class="tk-empty"><strong>Sin tareas recientes</strong>A&uacute;n no hay tareas registradas en este hotel.</div>
                <?php else: ?>
                    <div class="tk-table-wrap">
                        <table class="tk-table">
                            <thead>
                                <tr><th>Tarea</th><th>Categor&iacute;a</th><th>Estado</th><th>Prioridad</th><th>Contexto</th><th>L&iacute;mite</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recientes as $tarea): ?>
                                    <?php
                                    [$eLabel, $eClass, $eIcon] = tk_report_estado_meta($tarea['estado'] ?? 'pendiente');
                                    $categoria = (string)($tarea['categoria'] ?? 'general');
                                    $prioridad = (string)($tarea['prioridad'] ?? 'media');
                                    $trabajadoresTexto = tk_report_workers_text($tarea);
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="tk-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>"><?= tlm_report_safe($tarea['titulo'] ?? null, 'Tarea #' . (int)($tarea['id'] ?? 0)) ?></a>
                                            <div class="tk-sub">Actualizada: <?= tlm_report_safe(tlm_report_date($tarea['updated_at'] ?? null)) ?></div>
                                        </td>
                                        <td><?= tlm_report_safe($categoriaLabels[$categoria] ?? $categoria) ?></td>
                                        <td><span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                        <td><?= tlm_report_safe($prioridadLabels[$prioridad] ?? $prioridad) ?></td>
                                        <td>
                                            <?php if (!empty($tarea['habitacion_id'])): ?>
                                                Hab. <?= tlm_report_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?><br>
                                            <?php elseif (!empty($tarea['area_id'])): ?>
                                                <?= tlm_report_safe($tarea['area_nombre'] ?? ('Área #' . (int)$tarea['area_id'])) ?><br>
                                            <?php endif; ?>
                                            <?php if ($trabajadoresTexto !== ''): ?>
                                                <?= tlm_report_safe($trabajadoresTexto) ?>
                                            <?php elseif (empty($tarea['habitacion_id']) && empty($tarea['area_id'])): ?>
                                                <span class="tk-sub" style="margin:0">Sin contexto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= tlm_report_safe(tlm_report_date($tarea['fecha_limite'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tk-panel">
                <div class="tk-panel-head">
                    <div>
                        <h2 class="tk-panel-title">Movimientos recientes</h2>
                        <p class="tk-panel-sub">Cambios registrados en las tareas.</p>
                    </div>
                </div>
                <?php if (empty($eventosRecientes)): ?>
                    <div class="tk-empty"><strong>Sin movimientos recientes</strong>Cuando haya tareas, aqu&iacute; ver&aacute;s su historial.</div>
                <?php else: ?>
                    <div class="tk-table-wrap">
                        <table class="tk-table">
                            <thead>
                                <tr><th>Evento</th><th>Tarea</th><th>Cambio</th><th>Usuario</th><th>Fecha</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventosRecientes as $evento): ?>
                                    <tr>
                                        <td><span class="tk-badge is-soft"><?= tlm_report_safe($evento['tipo_evento'] ?? null) ?></span></td>
                                        <td>
                                            <a class="tk-link" href="<?= url('tareas/' . (int)($evento['tarea_id'] ?? 0)) ?>"><?= tlm_report_safe($evento['tarea_titulo'] ?? null, 'Tarea #' . (int)($evento['tarea_id'] ?? 0)) ?></a>
                                            <div class="tk-sub"><?= tlm_report_safe($evento['comentario'] ?? null, '') ?></div>
                                        </td>
                                        <td><?= tlm_report_safe($evento['estado_anterior'] ?? null, '-') ?> &rarr; <?= tlm_report_safe($evento['estado_nuevo'] ?? null, '-') ?></td>
                                        <td><?= tlm_report_safe($evento['usuario_nombre'] ?? null, 'Sistema') ?></td>
                                        <td><?= tlm_report_safe(tlm_report_date($evento['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
