<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_date')) {
    function tlm_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_estado_meta')) {
    function tk_estado_meta($estado): array
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

if (!function_exists('tk_prioridad_meta')) {
    function tk_prioridad_meta($prioridad): array
    {
        $key = strtolower(trim((string)($prioridad ?? '')));
        $map = [
            'baja'    => ['Baja', 'is-baja', 'fa-arrow-down'],
            'media'   => ['Media', 'is-media', 'fa-equals'],
            'alta'    => ['Alta', 'is-alta', 'fa-arrow-up'],
            'urgente' => ['Urgente', 'is-urgente', 'fa-fire'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Media'), 'is-media', 'fa-equals'];
    }
}

if (!function_exists('tk_categoria_meta')) {
    function tk_categoria_meta($categoria): array
    {
        $key = strtolower(trim((string)($categoria ?? '')));
        $map = [
            'limpieza'      => ['Limpieza', 'fa-broom'],
            'mantenimiento' => ['Mantenimiento', 'fa-screwdriver-wrench'],
            'general'       => ['General', 'fa-list-check'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'General'), 'fa-list-check'];
    }
}

$tareas = is_array($tareas ?? null) ? $tareas : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$filtros = is_array($filtros ?? null) ? $filtros : [];
$trabajadores = is_array($trabajadores ?? null) ? $trabajadores : [];
$trabajadorActual = is_array($trabajadorActual ?? null) ? $trabajadorActual : null;
$filtroPersonalAuto = (bool)($filtroPersonalAuto ?? false);
$tablaDisponible = (bool)($tablaDisponible ?? false);
$puedeCrear = function_exists('can') && can('habitaciones.mantenimiento');
$buscar = (string)($filtros['buscar'] ?? '');
$categoriaF = (string)($filtros['categoria'] ?? 'todos');
$estadoF = (string)($filtros['estado'] ?? 'todos');
$prioridadF = (string)($filtros['prioridad'] ?? 'todos');
$trabajadorF = (string)($filtros['trabajador_id'] ?? 'todos');
$fechaDesdeF = (string)($filtros['fecha_desde'] ?? '');
$fechaHastaF = (string)($filtros['fecha_hasta'] ?? '');
$visibles = count($tareas);
?>

<style>
.tk-page {
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
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tk-page .tk-shell { display: grid; gap: 14px; min-width: 0; width: 100%; max-width: 100%; }
.tk-page .tk-hero-section {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr);
    align-items: start !important;
    justify-content: stretch !important;
    gap: 12px !important;
    min-width: 0;
    max-width: 100%;
    width: 100%;
    box-sizing: border-box;
    padding: 2px 0 6px;
}
.tk-page.tk-page--board {
    --tk-view-accent: var(--tk-gold);
    --tk-view-soft: color-mix(in srgb, var(--tk-gold) 8%, #FFFFFF);
}
.tk-page.tk-page--board .tk-hero-icon {
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--tk-gold)));
}
.tk-page .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); grid-template-rows: auto auto; align-items: center; column-gap: 14px; justify-self: start; min-width: 0; max-width: min(100%, 760px); text-align: left; }
.tk-page .tk-title-lockup > .tk-hero-icon { grid-column: 1; grid-row: 1; }
.tk-page .tk-title-lockup > .tk-title-head { grid-column: 2; grid-row: 1; min-width: 0; }
.tk-page .tk-title-lockup > .tk-title-rest { grid-column: 2; grid-row: 2; min-width: 0; }
.tk-page .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-page .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-page .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 650; font-size: clamp(2.1rem, 4vw, 3rem); line-height: .98; overflow-wrap: anywhere; }
.tk-page .tk-subtitle { max-width: 46rem; margin: 9px 0 0; color: var(--tk-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }
.tk-page .tk-view-chip { display: inline-flex; align-items: center; gap: 7px; width: fit-content; margin-top: 10px; padding: 6px 10px; border-radius: 999px; background: var(--tk-view-soft); border: 1px solid color-mix(in srgb, var(--tk-view-accent) 24%, #fff); color: var(--tk-gold-ink); font-size: .72rem; font-weight: 650; line-height: 1; }
.tk-page .tk-hero-actions { display: flex !important; flex-wrap: wrap; align-items: center; justify-content: flex-start; justify-self: start; gap: 8px; min-width: 0; max-width: 100%; }

.tk-page .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 18px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 650; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none; white-space: nowrap;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.tk-page .tk-btn:hover { transform: translateY(-1px); }
.tk-page .tk-btn:active { transform: translateY(0) scale(.98); }
.tk-page .tk-btn:focus-visible { outline: 3px solid var(--tk-ring); outline-offset: 2px; }
.tk-page .tk-btn-gold { background: linear-gradient(135deg, color-mix(in srgb, var(--tk-gold) 86%, #fff), color-mix(in srgb, var(--tk-gold) 72%, var(--tk-brand))); color: #fff; box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--tk-gold) 42%, transparent); }
.tk-page .tk-btn-brand { background: linear-gradient(135deg, color-mix(in srgb, var(--tk-brand) 82%, #fff), color-mix(in srgb, var(--tk-brand-2) 64%, var(--tk-gold))); color: #fff; box-shadow: 0 12px 24px -16px color-mix(in srgb, var(--tk-brand) 48%, transparent); }
.tk-page .tk-btn-muted { background: var(--tk-surface); border-color: var(--tk-border); color: var(--tk-muted); }
.tk-page .tk-btn-agenda,
.tk-page .tk-btn-report {
    border-color: var(--tk-action-line, var(--tk-border));
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--tk-action, var(--tk-info)) 16%, #FFFFFF), color-mix(in srgb, var(--tk-action, var(--tk-info)) 7%, #FFFFFF));
    color: color-mix(in srgb, var(--tk-action, var(--tk-info)) 64%, var(--tk-text));
    box-shadow: 0 12px 24px -18px color-mix(in srgb, var(--tk-action, var(--tk-info)) 72%, transparent);
}
.tk-page .tk-btn-agenda { --tk-action: var(--tk-info); --tk-action-line: color-mix(in srgb, var(--tk-info) 34%, #FFFFFF); }
.tk-page .tk-btn-report { --tk-action: var(--tk-proc); --tk-action-line: color-mix(in srgb, var(--tk-proc) 34%, #FFFFFF); }
.tk-page .tk-btn-agenda i,
.tk-page .tk-btn-report i {
    color: var(--tk-action);
}
.tk-page .tk-btn-agenda:hover,
.tk-page .tk-btn-report:hover {
    border-color: color-mix(in srgb, var(--tk-action) 52%, #FFFFFF);
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--tk-action) 22%, #FFFFFF), color-mix(in srgb, var(--tk-action) 10%, #FFFFFF));
    box-shadow: 0 16px 30px -20px color-mix(in srgb, var(--tk-action) 82%, transparent);
}

.tk-page .tk-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.tk-page .tk-summary-item { position: relative; overflow: hidden; background: rgba(255,255,255,.82); border: 1px solid var(--tk-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18); }
.tk-page.tk-page--board .tk-summary-item::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 3px; background: linear-gradient(180deg, color-mix(in srgb, var(--tk-view-accent) 72%, #fff), color-mix(in srgb, var(--tk-brand) 50%, var(--tk-view-accent))); opacity: .62; }
.tk-page .tk-summary-label { color: var(--tk-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.tk-page .tk-summary-value { margin-top: 2px; font-family: var(--tk-serif); font-size: 1.7rem; font-weight: 650; line-height: 1.1; color: var(--tk-heading); }
.tk-page .tk-summary-value.is-warning { color: color-mix(in srgb, var(--tk-warning) 68%, var(--tk-text)); }
.tk-page .tk-summary-value.is-info { color: color-mix(in srgb, var(--tk-info) 66%, var(--tk-text)); }
.tk-page .tk-summary-value.is-progress { color: color-mix(in srgb, var(--tk-proc) 66%, var(--tk-text)); }
.tk-page .tk-summary-value.is-success { color: color-mix(in srgb, var(--tk-success) 68%, var(--tk-text)); }

.tk-page .tk-panel { background: rgba(255,255,255,.86); border: 1px solid var(--tk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); }
.tk-page .tk-filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(142px, 1fr)); gap: 10px; align-items: end; }
.tk-page .tk-field { display: grid; gap: 6px; min-width: 0; }
.tk-page .tk-field-label { color: var(--tk-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; line-height: 1; text-transform: uppercase; }
.tk-page .tk-control { width: 100%; min-height: 44px; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--tk-text); font-weight: 560; font-size: .88rem; transition: border-color .16s ease, box-shadow .16s ease; }
.tk-page .tk-control::placeholder { color: color-mix(in srgb, var(--tk-muted) 82%, #B8C0CB); font-weight: 520; }
.tk-page .tk-control:focus { border-color: var(--tk-gold); box-shadow: 0 0 0 3px var(--tk-ring); outline: none; }
.tk-page select.tk-control, .tk-page input[type="date"].tk-control { cursor: pointer; }
.tk-page .tk-search { grid-column: span 2; }
.tk-page .tk-search-box { position: relative; }
.tk-page .tk-search .tk-control { padding-left: 38px; }
.tk-page .tk-search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--tk-muted); font-size: .9rem; pointer-events: none; }
.tk-page .tk-filter-actions { display: flex; gap: 8px; align-items: end; }
.tk-page .tk-filter-actions .tk-btn { flex: 1 1 auto; min-width: 0; }
.tk-page .tk-filter-note { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; padding: 10px 12px; border: 1px solid color-mix(in srgb, var(--tk-gold) 28%, #ECE1D1); border-radius: 12px; background: color-mix(in srgb, var(--tk-gold) 10%, #FFFFFF); color: var(--tk-gold-ink); font-size: .82rem; font-weight: 650; line-height: 1.35; }
.tk-page .tk-filter-note-text { display: inline-flex; align-items: center; gap: 8px; min-width: 0; }
.tk-page .tk-filter-note a { color: var(--tk-gold-ink); text-decoration: underline; text-underline-offset: 3px; white-space: nowrap; }

.tk-page .tk-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px 16px; border-bottom: 1px solid var(--tk-border); }
.tk-page.tk-page--board .tk-panel-head { background: linear-gradient(90deg, var(--tk-view-soft), transparent 72%); }
.tk-page .tk-panel-title { font-size: .85rem; font-weight: 650; color: var(--tk-heading); }
.tk-page .tk-panel-sub { font-size: .75rem; color: var(--tk-muted); }
.tk-page .tk-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: color-mix(in srgb, var(--tk-gold) 10%, #FFFFFF); color: var(--tk-gold-ink); border: 1px solid color-mix(in srgb, var(--tk-gold) 28%, #ECE1D1); font-size: .72rem; font-weight: 650; white-space: nowrap; }

.tk-page .tk-desktop { display: none; }
.tk-page .tk-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.tk-page .tk-table thead { background: var(--tk-surface-warm); border-bottom: 1px solid var(--tk-border); }
.tk-page .tk-table th { padding: 12px 16px; color: var(--tk-muted); font-size: .68rem; font-weight: 650; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.tk-page .tk-table td { padding: 13px 16px; vertical-align: middle; }
.tk-page .tk-row { border-bottom: 1px solid var(--tk-border); transition: background .16s ease, box-shadow .16s ease; }
.tk-page .tk-row:last-child { border-bottom: 0; }
.tk-page .tk-row:hover { background: rgba(251,248,242,.72); box-shadow: 0 10px 24px -25px rgba(27,39,70,.32); }
.tk-page [data-task-row-url] { cursor: pointer; }
.tk-page [data-task-row-url]:focus-visible { outline: 3px solid var(--tk-ring); outline-offset: -3px; }
.tk-page .tk-name { font-weight: 650; color: var(--tk-heading); line-height: 1.25; text-decoration: none; display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tk-page .tk-name:hover { text-decoration: underline; text-decoration-color: var(--tk-gold); text-underline-offset: 3px; }
.tk-page .tk-sub { color: var(--tk-muted); font-size: .74rem; margin-top: 2px; }
.tk-page .tk-inline-action { display: inline-flex; align-items: center; gap: 5px; margin-left: 8px; color: var(--tk-gold-ink); text-decoration: none; font-size: .72rem; font-weight: 650; }
.tk-page .tk-inline-action:hover { text-decoration: underline; text-underline-offset: 3px; }
.tk-page .tk-assigned-list { display: inline-block; color: var(--tk-heading); font-weight: 650; line-height: 1.3; }
.tk-page .tk-cat { display: inline-flex; align-items: center; gap: 7px; font-weight: 560; color: var(--tk-text); }
.tk-page .tk-cat i { color: var(--tk-muted); }
.tk-page .tk-ctx-link { color: var(--tk-info); text-decoration: none; font-weight: 600; }
.tk-page .tk-ctx-link:hover { text-decoration: underline; }

.tk-page .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 650; border: 1px solid transparent; }
.tk-page .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 72%, var(--tk-text)); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 26%, #fff); }
.tk-page .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 70%, var(--tk-text)); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 24%, #fff); }
.tk-page .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 70%, var(--tk-text)); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 24%, #fff); }
.tk-page .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 70%, var(--tk-text)); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 24%, #fff); }
.tk-page .tk-badge.is-cancelada, .tk-page .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }

.tk-page .tk-prio { display: inline-flex; align-items: center; gap: 5px; font-size: .73rem; font-weight: 650; padding: 3px 9px; border-radius: 999px; border: 1px solid transparent; }
.tk-page .tk-assigned-chip { max-width: 100%; white-space: normal; line-height: 1.25; }
.tk-page .tk-prio.is-baja { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-page .tk-prio.is-media { color: color-mix(in srgb, var(--tk-info) 70%, var(--tk-text)); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 24%, #fff); }
.tk-page .tk-prio.is-alta { color: color-mix(in srgb, var(--tk-warning) 72%, var(--tk-text)); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 26%, #fff); }
.tk-page .tk-prio.is-urgente { color: color-mix(in srgb, var(--tk-danger) 72%, var(--tk-text)); background: var(--tk-danger-bg); border-color: color-mix(in srgb, var(--tk-danger) 26%, #fff); }

.tk-page .tk-mobile { display: grid; gap: 10px; padding: 12px; }
.tk-page .tk-mobile-card { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); transition: transform .16s ease, border-color .16s ease; }
.tk-page .tk-mobile-card:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--tk-gold) 38%, var(--tk-border)); }
.tk-page .tk-mobile-card[data-task-row-url]:focus-visible { outline-offset: 2px; }
.tk-page .tk-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.tk-page .tk-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
.tk-page .tk-mobile-foot { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; color: var(--tk-muted); font-size: .76rem; }

.tk-page .tk-empty { text-align: center; padding: 44px 18px; background: var(--tk-ivory-2); border: 1px dashed var(--tk-border); border-radius: 16px; }
.tk-page .tk-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--tk-gold-soft); color: var(--tk-gold-ink); font-size: 1.3rem; }
.tk-page .tk-empty h2 { color: var(--tk-heading); font-size: 1.1rem; font-weight: 650; }
.tk-page .tk-empty p { color: var(--tk-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }
.tk-page .tk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--tk-gold-soft); border: 1px solid var(--tk-gold-line); border-radius: 16px; }
.tk-page .tk-notice i { color: var(--tk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.tk-page .tk-notice strong { color: var(--tk-heading); display: block; margin-bottom: 2px; font-weight: 650; }
.tk-page .tk-notice p { color: var(--tk-muted); font-size: .88rem; margin: 0; }

.tk-page [data-tk-results-region] { transition: opacity .18s ease, filter .18s ease; }
.tk-page [data-tk-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 860px) {
    .tk-page .tk-desktop { display: block; }
    .tk-page .tk-mobile { display: none; }
}
@media (max-width: 859px) {
    .tk-page .tk-filter-form { grid-template-columns: 1fr 1fr; }
    .tk-page .tk-search, .tk-page .tk-filter-actions { grid-column: 1 / -1; }
    .tk-page .tk-filter-actions { display: grid; grid-template-columns: 1fr 1fr; }
    .tk-page .tk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tk-page .tk-title { font-size: 1.9rem; }
}

/* ════════════════════════════════════════════════════════════════════
   MÓVIL COMPACTO  ·  estética dashboard / habitaciones (≤768px)
   Se omite lo que satura en pantalla chica (ver resumen al usuario).
   ════════════════════════════════════════════════════════════════════ */
@media (max-width: 767px) {
    .tk-page { padding: 14px !important; }
    .tk-page .tk-shell { gap: 12px; }

    /* Header compacto */
    .tk-page .tk-title-lockup { grid-template-columns: 40px minmax(0, 1fr); column-gap: 11px; align-items: center; }
    .tk-page .tk-hero-icon { width: 40px; height: 40px; border-radius: 12px; font-size: 1rem; }
    .tk-page .tk-kicker { display: none; }       /* omitido */
    .tk-page .tk-title { font-size: 1.5rem; }
    .tk-page .tk-subtitle { display: none; }     /* omitido: parrafo largo */

    /* Acciones: "Nueva tarea" full; Agenda + Reporte comparten fila */
    .tk-page section.flex > .flex { width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .tk-page section.flex > .flex > .tk-btn { min-height: 44px; font-size: .82rem; }
    .tk-page section.flex > .flex > .tk-btn-gold { grid-column: 1 / -1; }

    /* Resumen 2x2 + último a lo ancho */
    .tk-page .tk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .tk-page .tk-summary-item { padding: 10px 12px; border-radius: 12px; }
    .tk-page .tk-summary-item:last-child { grid-column: 1 / -1; }
    .tk-page .tk-summary-value { font-size: 1.35rem; }

    /* Filtros compactos: search arriba + controles en 2 columnas; "Filtrar" oculto */
    .tk-page .tk-panel.p-3 { padding: 10px !important; }
    .tk-page .tk-filter-form { grid-template-columns: 1fr 1fr; gap: 7px; }
    .tk-page .tk-search { grid-column: 1 / -1; }
    .tk-page .tk-field-label { font-size: .62rem; }
    .tk-page .tk-control { min-height: 38px; font-size: .82rem; border-radius: 9px; }
    .tk-page .tk-filter-form .tk-btn-brand { display: none; }   /* omitido: redundante con busqueda en vivo */
    .tk-page .tk-filter-actions { grid-column: 1 / -1; grid-template-columns: 1fr; }
    .tk-page .tk-filter-form .tk-reset { min-height: 38px; font-size: .82rem; }
    .tk-page .tk-filter-note { align-items: flex-start; flex-direction: column; gap: 8px; font-size: .76rem; }
    .tk-page .tk-filter-note a { min-height: 32px; display: inline-flex; align-items: center; }

    /* Bandeja: tarjetas compactas */
    .tk-page .tk-panel-head { padding: 12px 14px; }
    .tk-page .tk-panel-sub { display: none; }    /* omitido: descripcion decorativa */
    .tk-page .tk-mobile { padding: 12px; gap: 8px; }
    .tk-page .tk-mobile-card { padding: 12px; border-radius: 14px; }
    .tk-page .tk-mobile-top { gap: 8px; }
    .tk-page .tk-name { font-size: .94rem; white-space: normal; }
    .tk-page .tk-chips { gap: 5px; margin-top: 9px; }
    .tk-page .tk-prio { font-size: .7rem; padding: 3px 8px; }
    .tk-page .tk-badge { font-size: .7rem; }
    .tk-page .tk-mobile-foot { gap: 8px; margin-top: 9px; font-size: .72rem; }
    .tk-page .tk-empty { padding: 28px 16px; }
}
@media (min-width: 1024px) {
    .tk-page .tk-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 16px 28px !important;
        padding-bottom: 10px;
    }
    .tk-page .tk-hero-actions {
        justify-content: flex-end;
        justify-self: end;
        min-width: max-content;
    }
}
@media (min-width: 768px) and (max-width: 1023px) {
    .tk-page .tk-hero-section {
        grid-template-columns: minmax(0, 1fr);
        align-items: stretch !important;
    }
    .tk-page .tk-hero-actions {
        justify-content: flex-start;
        max-width: 100%;
        min-width: 0;
    }
    .tk-page .tk-filter-form {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
    .tk-page .tk-search {
        grid-column: 1 / -1;
    }
}
@media (max-width: 767px) {
    .tk-page {
        --tk-mobile-ink: color-mix(in srgb, var(--tk-brand) 62%, #6F7784);
        --tk-mobile-text: color-mix(in srgb, var(--tk-brand) 42%, #778394);
        --tk-mobile-muted: #98A2B3;
        --tk-mobile-faint: #AAB3C0;
        padding: 10px 12px 18px !important;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--tk-gold) 12%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%);
    }
    .tk-page .tk-shell { gap: 10px; }
    .tk-page .tk-hero-section {
        position: relative;
        overflow: hidden;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px !important;
        min-height: 126px;
        margin: 0;
        padding: 16px 14px 14px;
        border-radius: 22px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 14%, rgba(255,255,255,.17), transparent 92px),
            linear-gradient(135deg, color-mix(in srgb, var(--tk-brand) 94%, #000) 0%, color-mix(in srgb, var(--tk-brand-2) 78%, var(--tk-gold)) 100%);
        box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--tk-brand) 72%, transparent);
    }
    .tk-page .tk-hero-section::after {
        content: "";
        position: absolute;
        right: -44px;
        top: -44px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        pointer-events: none;
    }
    .tk-page .tk-title-lockup {
        position: relative;
        z-index: 1;
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }
    .tk-page .tk-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
        background: rgba(255,255,255,.16);
        color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: none;
        backdrop-filter: blur(10px);
    }
    .tk-page .tk-kicker {
        display: block;
        color: rgba(255,255,255,.76);
        font-size: .62rem;
        letter-spacing: .14em;
    }
    .tk-page .tk-title {
        color: #fff;
        font-size: 1.72rem;
        line-height: .98;
        text-shadow: 0 8px 22px rgba(0,0,0,.28);
    }
    .tk-page .tk-subtitle {
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
    .tk-page .tk-view-chip { display: none; }
    .tk-page .tk-hero-actions {
        position: relative;
        z-index: 1;
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: flex-end;
        gap: 7px !important;
    }
    .tk-page .tk-btn {
        min-height: 44px;
        padding: 0 12px;
        border-radius: 13px;
        font-size: .76rem;
        box-shadow: none;
    }
    .tk-page .tk-hero-actions .tk-btn {
        flex: 1 1 120px;
        min-width: 0;
    }
    .tk-page .tk-hero-actions .tk-btn-muted {
        background: rgba(255,255,255,.13);
        color: #fff;
        border-color: rgba(255,255,255,.18);
    }
    .tk-page .tk-hero-actions .tk-btn-gold {
        flex-basis: 100%;
        background: #fff;
        color: var(--tk-mobile-ink);
    }
    .tk-page .tk-summary {
        display: flex;
        grid-template-columns: none;
        gap: 8px;
        margin: 0 -12px;
        padding: 0 12px 2px;
        overflow-x: auto;
        scrollbar-width: none;
    }
    .tk-page .tk-summary::-webkit-scrollbar { width: 0; height: 0; }
    .tk-page .tk-summary-item {
        flex: 0 0 118px;
        min-height: 70px;
        padding: 10px 11px;
        border-radius: 16px;
        background: rgba(255,255,255,.74);
        border-color: color-mix(in srgb, var(--tk-brand) 5%, #ECE4D8);
        box-shadow: 0 12px 22px -26px rgba(27,39,70,.24);
    }
    .tk-page .tk-summary-item:last-child { grid-column: auto; }
    .tk-page .tk-summary-label {
        color: var(--tk-mobile-muted);
        font-size: .58rem;
        font-weight: 650;
        letter-spacing: .055em;
        white-space: nowrap;
    }
    .tk-page .tk-summary-value {
        margin-top: 5px;
        font-size: 1.18rem;
        color: var(--tk-mobile-ink);
        font-weight: 650;
        white-space: nowrap;
    }
    .tk-page .tk-panel {
        border-radius: 18px;
        border-color: color-mix(in srgb, var(--tk-brand) 5%, #ECE4D8);
        box-shadow: 0 16px 28px -28px rgba(27,39,70,.28);
    }
    .tk-page .tk-panel.p-3 {
        padding: 10px !important;
        background: rgba(255,255,255,.78);
    }
    .tk-page .tk-filter-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 7px;
    }
    .tk-page .tk-search { grid-column: 1 / -1; }
    .tk-page .tk-search .tk-control { padding-left: 40px; }
    .tk-page .tk-search-icon { left: 14px; }
    .tk-page .tk-field-label {
        color: var(--tk-mobile-faint);
        font-size: .56rem;
        font-weight: 650;
    }
    .tk-page .tk-control {
        min-height: 44px;
        border-radius: 14px;
        padding: 0 10px;
        font-size: 16px;
        background: #FAFAFC;
        color: var(--tk-mobile-text);
        font-weight: 560;
        border-color: color-mix(in srgb, var(--tk-brand) 10%, var(--tk-border));
    }
    .tk-page .tk-control::placeholder {
        color: var(--tk-mobile-muted);
        font-weight: 520;
    }
    .tk-page select.tk-control {
        min-width: 0;
        font-size: .77rem;
        color: var(--tk-mobile-text);
        font-weight: 650;
        text-overflow: ellipsis;
    }
    .tk-page .tk-filter-form .tk-btn-brand { display: none; }
    .tk-page .tk-filter-actions {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1fr;
    }
    .tk-page .tk-filter-form .tk-reset { min-height: 44px; font-size: .76rem; }
    .tk-page .tk-filter-note { align-items: flex-start; flex-direction: column; gap: 8px; font-size: .76rem; }
    .tk-page .tk-filter-note a { min-height: 32px; display: inline-flex; align-items: center; }
    .tk-page .tk-panel-head {
        padding: 12px 13px;
        align-items: center;
    }
    .tk-page .tk-panel-title {
        font-size: .82rem;
        color: var(--tk-mobile-ink);
        font-weight: 650;
    }
    .tk-page .tk-panel-sub { display: none; }
    .tk-page .tk-count-pill {
        padding: .34rem .56rem;
        font-size: .66rem;
        color: color-mix(in srgb, var(--tk-gold-ink) 62%, var(--tk-mobile-text));
        background: color-mix(in srgb, var(--tk-gold) 8%, #FFFFFF);
        border-color: color-mix(in srgb, var(--tk-gold) 22%, #EFE6D8);
        font-weight: 650;
    }
    .tk-page .tk-mobile {
        gap: 8px;
        padding: 10px;
    }
    .tk-page .tk-mobile-card {
        position: relative;
        overflow: hidden;
        border-radius: 17px;
        padding: 11px;
        background: rgba(255,255,255,.78);
        border-color: color-mix(in srgb, var(--tk-brand) 5%, #ECE4D8);
        box-shadow: 0 12px 24px -26px rgba(27,39,70,.28);
    }
    .tk-page .tk-mobile-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 12px;
        bottom: 12px;
        width: 4px;
        border-radius: 0 999px 999px 0;
        background: var(--tk-gold);
        opacity: .82;
    }
    .tk-page .tk-mobile-top { gap: 9px; padding-left: 3px; }
    .tk-page .tk-mobile .tk-name {
        white-space: normal;
        font-size: .9rem;
        color: var(--tk-mobile-ink);
        font-weight: 650;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .tk-page .tk-mobile .tk-sub {
        margin-top: 2px;
        font-size: .69rem;
        color: var(--tk-mobile-muted);
        font-weight: 520;
    }
    .tk-page .tk-mobile-top .tk-badge {
        min-width: 44px;
        min-height: 44px;
        justify-content: center;
        border-radius: 12px;
        font-size: .66rem;
        text-align: center;
    }
    .tk-page .tk-chips { gap: 5px; margin-top: 9px; padding-left: 3px; }
    .tk-page .tk-prio { font-size: .7rem; padding: 3px 8px; }
    .tk-page .tk-mobile-foot { gap: 8px; margin-top: 9px; padding-left: 3px; font-size: .72rem; }
    .tk-page .tk-empty {
        padding: 30px 16px;
        border-radius: 18px;
    }
    .tk-page .tk-empty-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        margin-bottom: 12px;
    }
    .tk-page .tk-empty h2 { font-size: .98rem; }
    .tk-page .tk-empty p { font-size: .78rem; line-height: 1.35; }
}
@media (prefers-reduced-motion: reduce) {
    .tk-page * { transition-duration: .01ms !important; scroll-behavior: auto !important; }
}
</style>

<div class="tk-page tk-page--board p-4 sm:p-6">
    <div class="tk-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="tk-hero-section">
            <div class="tk-title-lockup">
                <div class="tk-hero-icon"><i class="fas fa-list-check"></i></div>
                <div class="tk-title-head">
                    <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                    <h1 class="tk-title">Tareas</h1>
                </div>
                <div class="tk-title-rest">
                    <p class="tk-subtitle">Pendientes de limpieza, mantenimiento y cosas por hacer en el hotel. Cr&eacute;alas, as&iacute;gnalas y dales seguimiento.</p>
                    <div class="tk-view-chip"><i class="fas fa-table-list"></i> Tablero de seguimiento</div>
                </div>
            </div>
            <div class="tk-hero-actions">
                <?php if ($puedeCrear): ?>
                    <a class="tk-btn tk-btn-gold ms-glass-btn" href="<?= url('tareas/crear') ?>">
                        <i class="fas fa-plus"></i>
                        Nueva tarea
                    </a>
                <?php endif; ?>
                <a class="tk-btn tk-btn-muted tk-btn-agenda ms-glass-btn" href="<?= url('tareas/agenda') ?>">
                    <i class="fas fa-calendar-day"></i>
                    Agenda
                </a>
                <a class="tk-btn tk-btn-muted tk-btn-report ms-glass-btn" href="<?= url('tareas/reporte') ?>">
                    <i class="fas fa-chart-pie"></i>
                    Reporte
                </a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="tk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para empezar a registrar tareas.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="tk-summary">
                <div class="tk-summary-item"><p class="tk-summary-label">Total</p><p class="tk-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Pendientes</p><p class="tk-summary-value is-warning"><?= number_format((int)($resumen['pendiente'] ?? 0)) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Asignadas</p><p class="tk-summary-value is-info"><?= number_format((int)($resumen['asignada'] ?? 0)) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">En proceso</p><p class="tk-summary-value is-progress"><?= number_format((int)($resumen['en_proceso'] ?? 0)) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Completadas</p><p class="tk-summary-value is-success"><?= number_format((int)($resumen['completada'] ?? 0)) ?></p></div>
            </section>

            <section class="tk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('tareas') ?>" class="tk-filter-form" data-tk-live-search-form data-auto-filter-form>
                    <input type="hidden" name="filtros" value="1">
                    <div class="tk-field tk-search">
                        <label class="tk-field-label" for="tk-filter-buscar">Buscar</label>
                        <div class="tk-search-box">
                            <i class="fas fa-search tk-search-icon" data-tk-search-icon></i>
                            <input id="tk-filter-buscar" class="tk-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                                   value="<?= tlm_safe($buscar) ?>"
                                   placeholder="T&iacute;tulo, habitaci&oacute;n o trabajador"
                                   data-tk-live-search-input>
                        </div>
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-trabajador">Personal</label>
                        <select id="tk-filter-trabajador" class="tk-control" name="trabajador_id">
                            <option value="todos" <?= $trabajadorF === 'todos' ? 'selected' : '' ?>>Todo el personal</option>
                            <option value="sin_asignar" <?= $trabajadorF === 'sin_asignar' ? 'selected' : '' ?>>Sin asignar</option>
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <?php $trabajadorIdFiltro = (int)($trabajador['id'] ?? 0); ?>
                                <?php if ($trabajadorIdFiltro <= 0) { continue; } ?>
                                <option value="<?= $trabajadorIdFiltro ?>" <?= $trabajadorF === (string)$trabajadorIdFiltro ? 'selected' : '' ?>>
                                    <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorIdFiltro)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-desde">Desde</label>
                        <input id="tk-filter-desde" class="tk-control" type="date" name="fecha_desde" value="<?= tlm_safe($fechaDesdeF) ?>">
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-hasta">Hasta</label>
                        <input id="tk-filter-hasta" class="tk-control" type="date" name="fecha_hasta" value="<?= tlm_safe($fechaHastaF) ?>">
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-categoria">Categor&iacute;a</label>
                        <select id="tk-filter-categoria" class="tk-control" name="categoria">
                            <option value="todos" <?= $categoriaF === 'todos' ? 'selected' : '' ?>>Todas</option>
                            <option value="limpieza" <?= $categoriaF === 'limpieza' ? 'selected' : '' ?>>Limpieza</option>
                            <option value="mantenimiento" <?= $categoriaF === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                            <option value="general" <?= $categoriaF === 'general' ? 'selected' : '' ?>>General</option>
                        </select>
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-estado">Estado</label>
                        <select id="tk-filter-estado" class="tk-control" name="estado">
                            <option value="todos" <?= $estadoF === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="pendiente" <?= $estadoF === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="asignada" <?= $estadoF === 'asignada' ? 'selected' : '' ?>>Asignada</option>
                            <option value="en_proceso" <?= $estadoF === 'en_proceso' ? 'selected' : '' ?>>En proceso</option>
                            <option value="completada" <?= $estadoF === 'completada' ? 'selected' : '' ?>>Completada</option>
                            <option value="cancelada" <?= $estadoF === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>
                    <div class="tk-field">
                        <label class="tk-field-label" for="tk-filter-prioridad">Prioridad</label>
                        <select id="tk-filter-prioridad" class="tk-control" name="prioridad">
                            <option value="todos" <?= $prioridadF === 'todos' ? 'selected' : '' ?>>Todas</option>
                            <option value="baja" <?= $prioridadF === 'baja' ? 'selected' : '' ?>>Baja</option>
                            <option value="media" <?= $prioridadF === 'media' ? 'selected' : '' ?>>Media</option>
                            <option value="alta" <?= $prioridadF === 'alta' ? 'selected' : '' ?>>Alta</option>
                            <option value="urgente" <?= $prioridadF === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                        </select>
                    </div>
                    <div class="tk-filter-actions">
                        <button class="tk-btn tk-btn-brand" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                        <a class="tk-btn tk-btn-muted tk-reset" href="<?= url('tareas') ?>"><i class="fas fa-times"></i> Limpiar</a>
                    </div>
                </form>
                <?php if ($filtroPersonalAuto && !empty($trabajadorActual)): ?>
                    <div class="tk-filter-note">
                        <span class="tk-filter-note-text">
                            <i class="fas fa-user-check"></i>
                            Mostrando tus tareas de hoy: <?= tlm_safe($trabajadorActual['nombre_completo'] ?? 'trabajador') ?>.
                        </span>
                        <a href="<?= url('tareas?filtros=1') ?>">Ver todo</a>
                    </div>
                <?php endif; ?>
            </section>

            <div data-tk-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($tareas)): ?>
                    <section class="tk-empty">
                        <div class="tk-empty-icon"><i class="fas fa-list-check"></i></div>
                        <h2>A&uacute;n no hay tareas</h2>
                        <p>No encontramos tareas con estos filtros. <?= $puedeCrear ? 'Crea la primera del hotel.' : 'Cambia la b&uacute;squeda para ver otras.' ?></p>
                        <?php if ($puedeCrear): ?>
                            <a class="tk-btn tk-btn-gold mt-4" href="<?= url('tareas/crear') ?>" style="display:inline-flex"><i class="fas fa-plus"></i> Nueva tarea</a>
                        <?php endif; ?>
                    </section>
                <?php else: ?>
                    <section class="tk-panel overflow-hidden">
                        <div class="tk-panel-head">
                            <div>
                                <div class="tk-panel-title">Lista de tareas</div>
                                <div class="tk-panel-sub">Ordenadas por la fecha l&iacute;mite m&aacute;s pr&oacute;xima, con responsables y estado.</div>
                            </div>
                            <span class="tk-count-pill"><i class="fas fa-list"></i> <?= number_format($visibles) ?> <?= $visibles === 1 ? 'tarea' : 'tareas' ?></span>
                        </div>

                        <div class="tk-desktop">
                            <table class="tk-table">
                                <colgroup>
                                    <col style="width: 30%;"><col style="width: 14%;"><col style="width: 13%;"><col style="width: 11%;"><col style="width: 18%;"><col style="width: 14%;">
                                </colgroup>
                                <thead>
                                    <tr><th>Tarea</th><th>Categor&iacute;a</th><th>Estado</th><th>Prioridad</th><th>Asignada a</th><th>Fechas</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tareas as $tarea): ?>
                                        <?php
                                        [$eLabel, $eClass, $eIcon] = tk_estado_meta($tarea['estado'] ?? 'pendiente');
                                        [$pLabel, $pClass, $pIcon] = tk_prioridad_meta($tarea['prioridad'] ?? 'media');
                                        [$cLabel, $cIcon] = tk_categoria_meta($tarea['categoria'] ?? 'general');
                                        $tareaId = (int)($tarea['id'] ?? 0);
                                        $tareaUrl = url('tareas/' . $tareaId);
                                        $tareaTitulo = tlm_safe($tarea['titulo'] ?? '', 'Tarea #' . $tareaId);
                                        $asignadosTexto = trim((string)($tarea['trabajadores_nombres'] ?? ($tarea['trabajadores_asignados'] ?? '')));
                                        ?>
                                        <tr class="tk-row" data-task-row-url="<?= tlm_safe($tareaUrl) ?>" tabindex="0" role="link" aria-label="Ver <?= $tareaTitulo ?>">
                                            <td>
                                                <a class="tk-name" href="<?= tlm_safe($tareaUrl) ?>"><?= $tareaTitulo ?></a>
                                                <div class="tk-sub">
                                                    #<?= $tareaId ?> &middot; origen <?= tlm_safe($tarea['origen'] ?? 'manual') ?>
                                                    <?php if ($puedeCrear): ?>
                                                        <a class="tk-inline-action" href="<?= url('tareas/' . $tareaId . '/editar') ?>"><i class="fas fa-pen"></i> Editar</a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><span class="tk-cat"><i class="fas <?= $cIcon ?>"></i> <?= $cLabel ?></span></td>
                                            <td><span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                            <td><span class="tk-prio <?= $pClass ?>"><i class="fas <?= $pIcon ?>"></i> <?= $pLabel ?></span></td>
                                            <td>
                                                <?php if ($asignadosTexto !== ''): ?>
                                                    <span class="tk-assigned-list"><?= tlm_safe($asignadosTexto) ?></span>
                                                <?php elseif (!empty($tarea['trabajador_id'])): ?>
                                                    <a class="tk-ctx-link" href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?></a>
                                                <?php else: ?>
                                                    <span class="tk-sub" style="margin:0">Sin asignar</span>
                                                <?php endif; ?>
                                                <?php if (!empty($tarea['habitacion_id'])): ?>
                                                    <div class="tk-sub"><i class="fas fa-door-closed"></i> Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></div>
                                                <?php elseif (!empty($tarea['area_id'])): ?>
                                                    <div class="tk-sub"><i class="fas fa-location-dot"></i> <?= tlm_safe($tarea['area_nombre'] ?? ('Área #' . (int)$tarea['area_id'])) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="font-weight:600">Prog: <?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></div>
                                                <div class="tk-sub">L&iacute;mite: <?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tk-mobile">
                            <?php foreach ($tareas as $tarea): ?>
                                <?php
                                [$eLabel, $eClass, $eIcon] = tk_estado_meta($tarea['estado'] ?? 'pendiente');
                                [$pLabel, $pClass, $pIcon] = tk_prioridad_meta($tarea['prioridad'] ?? 'media');
                                [$cLabel, $cIcon] = tk_categoria_meta($tarea['categoria'] ?? 'general');
                                $tareaId = (int)($tarea['id'] ?? 0);
                                $tareaUrl = url('tareas/' . $tareaId);
                                $tareaTitulo = tlm_safe($tarea['titulo'] ?? '', 'Tarea #' . $tareaId);
                                $asignadosTexto = trim((string)($tarea['trabajadores_nombres'] ?? ($tarea['trabajadores_asignados'] ?? '')));
                                ?>
                                <article class="tk-mobile-card" data-task-row-url="<?= tlm_safe($tareaUrl) ?>" tabindex="0" role="link" aria-label="Ver <?= $tareaTitulo ?>">
                                    <div class="tk-mobile-top">
                                        <div class="min-w-0">
                                            <a class="tk-name" href="<?= tlm_safe($tareaUrl) ?>"><?= $tareaTitulo ?></a>
                                            <div class="tk-sub"><span class="tk-cat"><i class="fas <?= $cIcon ?>"></i> <?= $cLabel ?></span> &middot; #<?= $tareaId ?></div>
                                        </div>
                                        <span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                                    </div>
                                    <div class="tk-chips">
                                        <span class="tk-prio <?= $pClass ?>"><i class="fas <?= $pIcon ?>"></i> <?= $pLabel ?></span>
                                        <?php if ($asignadosTexto !== ''): ?>
                                            <span class="tk-prio tk-assigned-chip is-baja"><i class="fas fa-user"></i> <?= tlm_safe($asignadosTexto) ?></span>
                                        <?php elseif (!empty($tarea['trabajador_id'])): ?>
                                            <span class="tk-prio tk-assigned-chip is-baja"><i class="fas fa-user"></i> <?= tlm_safe($tarea['trabajador_nombre'] ?? 'Asignada') ?></span>
                                        <?php else: ?>
                                            <span class="tk-prio is-baja"><i class="fas fa-user-slash"></i> Sin asignar</span>
                                        <?php endif; ?>
                                        <?php if (!empty($tarea['habitacion_id'])): ?>
                                            <span class="tk-prio is-baja"><i class="fas fa-door-closed"></i> Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></span>
                                        <?php elseif (!empty($tarea['area_id'])): ?>
                                            <span class="tk-prio is-baja"><i class="fas fa-location-dot"></i> <?= tlm_safe($tarea['area_nombre'] ?? ('Área #' . (int)$tarea['area_id'])) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tk-mobile-foot">
                                        <span>Prog: <?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></span>
                                        <span>L&iacute;mite: <?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></span>
                                        <?php if ($puedeCrear): ?>
                                            <a class="tk-inline-action" href="<?= url('tareas/' . $tareaId . '/editar') ?>"><i class="fas fa-pen"></i> Editar</a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-tk-live-search-form]');
    const input = document.querySelector('[data-tk-live-search-input]');
    const searchIcon = document.querySelector('[data-tk-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-tk-results-region]');
    const rowInteractiveSelector = 'a, button, input, select, textarea, label, summary, [role="button"], [data-no-row-nav]';
    const closestFromTarget = (target, selector) => {
        if (target instanceof Element) return target.closest(selector);
        return target?.parentElement?.closest(selector) || null;
    };

    const openTaskRow = (row) => {
        const url = row?.getAttribute('data-task-row-url') || '';
        if (url) window.location.href = url;
    };

    document.addEventListener('click', (event) => {
        const row = closestFromTarget(event.target, '[data-task-row-url]');
        if (!row || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (closestFromTarget(event.target, rowInteractiveSelector)) return;
        openTaskRow(row);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') return;
        const row = closestFromTarget(event.target, '[data-task-row-url]');
        if (!row || closestFromTarget(event.target, rowInteractiveSelector)) return;
        event.preventDefault();
        openTaskRow(row);
    });

    const setSearching = (isSearching) => {
        getResultsRegion()?.classList.toggle('is-updating', isSearching);
        getResultsRegion()?.setAttribute('aria-busy', isSearching ? 'true' : 'false');
        if (searchIcon) {
            searchIcon.classList.toggle('fa-search', !isSearching);
            searchIcon.classList.toggle('fa-circle-notch', isSearching);
            searchIcon.classList.toggle('fa-spin', isSearching);
        }
    };

    const buildSearchUrl = (targetUrl = null) => {
        const url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(form.action, window.location.origin);
        const data = new FormData(form);
        for (const [key, value] of data.entries()) {
            const normalized = String(value || '').trim();
            if (normalized && normalized !== 'todos') {
                url.searchParams.set(key, normalized);
            } else {
                url.searchParams.delete(key);
            }
        }
        return url;
    };

    const updateFromDocument = (doc) => {
        const incoming = doc.querySelector('[data-tk-results-region]');
        const current = getResultsRegion();
        if (incoming && current) current.replaceWith(incoming);
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);
        try {
            const response = await fetch(url.toString(), { credentials: 'same-origin', signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('No se pudo cargar la busqueda');
            const html = await response.text();
            updateFromDocument(parser.parseFromString(html, 'text/html'));
            if (pushState) window.history.replaceState({}, '', url.pathname + url.search);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de tareas:', error);
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            if (activeRequest === controller) { activeRequest = null; setSearching(false); }
        }
    };

    const submitLiveSearch = () => {
        const current = input.value.trim();
        if (current === lastQuery) return;
        lastQuery = current;
        fetchResults(buildSearchUrl());
    };

    form.addEventListener('submit', (event) => { event.preventDefault(); lastQuery = input.value.trim(); fetchResults(buildSearchUrl()); });
    form.querySelectorAll('select').forEach((select) => select.addEventListener('change', () => { lastQuery = input.value.trim(); fetchResults(buildSearchUrl()); }));
    form.querySelector('.tk-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        form.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
        form.querySelectorAll('input[type="date"]').forEach((dateInput) => { dateInput.value = ''; });
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });
    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        ['categoria', 'estado', 'prioridad', 'trabajador_id'].forEach((n) => { const s = form.querySelector(`[name="${n}"]`); if (s) s.value = params.get(n) || 'todos'; });
        ['fecha_desde', 'fecha_hasta'].forEach((n) => { const d = form.querySelector(`[name="${n}"]`); if (d) d.value = params.get(n) || ''; });
        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });
    input.addEventListener('compositionstart', () => { isComposing = true; });
    input.addEventListener('compositionend', () => { isComposing = false; window.clearTimeout(liveSearchTimer); liveSearchTimer = window.setTimeout(submitLiveSearch, delay); });
    input.addEventListener('input', () => { if (isComposing) return; window.clearTimeout(liveSearchTimer); liveSearchTimer = window.setTimeout(submitLiveSearch, delay); });
})();
</script>
