<?php
$notificaciones = $notificaciones ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$modulos = $modulos ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$mensajeFlash = get_mensaje();

if (!function_exists('notif_safe')) {
    function notif_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('notif_date')) {
    function notif_date($value, $format = 'd/m/Y H:i') {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('notif_label')) {
    function notif_label($value) {
        $labels = [
            'activas' => 'Pendientes',
            'nueva' => 'Pendiente',
            'leida' => 'Pendiente',
            'resuelta' => 'Atendida',
            'descartada' => 'Historial',
            'info' => 'Info',
            'media' => 'Media',
            'alta' => 'Alta',
            'critica' => 'Critica',
            'caja' => 'Caja',
            'habitaciones' => 'Habitaciones',
            'facturacion' => 'Facturacion',
            'inventario' => 'Inventario',
            'reservaciones' => 'Reservaciones',
            'sistema' => 'Sistema',
        ];

        $key = (string)($value ?? '');
        return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }
}

$estadoFiltro = (string)($filtros['estado'] ?? 'activas');
$moduloFiltro = (string)($filtros['modulo'] ?? '');
$severidadFiltro = (string)($filtros['severidad'] ?? '');
$notificacionesPendientes = (int)($resumen['pendientes'] ?? ($resumen['nuevas'] ?? 0));
$notificacionesHistorial = (int)($resumen['historial'] ?? (($resumen['resueltas'] ?? 0) + ($resumen['descartadas'] ?? 0)));
?>

<style>
.notif-view {
    --nt-primary: var(--brand-primary, #1B2746);
    --nt-secondary: var(--brand-secondary, #0F172A);
    --nt-accent: var(--brand-accent, #BD9441);
    --nt-bg: color-mix(in srgb, var(--nt-accent) 6%, #F6F7F9);
    --nt-surface: #FFFFFF;
    --nt-line: color-mix(in srgb, var(--nt-primary) 14%, #E5E7EB);
    --nt-muted: #667085;
    min-height: 100vh;
    background: linear-gradient(180deg, var(--nt-bg), #FBFCFE 70%);
    color: #172033;
}
.notif-shell {
    width: min(1320px, calc(100% - 28px));
    margin: 0 auto;
    padding: 28px 0 46px;
}
.notif-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 16px;
    align-items: end;
    margin-bottom: 16px;
}
.notif-kicker {
    color: var(--nt-accent);
    font-size: .74rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.notif-title {
    margin: 5px 0 0;
    color: var(--nt-primary);
    font-size: 1.9rem;
    line-height: 1.1;
}
.notif-copy {
    max-width: 760px;
    margin: 8px 0 0;
    color: var(--nt-muted);
}
.notif-mark-form {
    margin: 0;
}
.notif-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 13px;
    border-radius: 9px;
    border: 1px solid var(--nt-line);
    background: var(--nt-surface);
    color: var(--nt-primary);
    font-size: .82rem;
    font-weight: 850;
    text-decoration: none;
    cursor: pointer;
}
.notif-btn:hover {
    background: color-mix(in srgb, var(--nt-accent) 8%, #fff);
    color: var(--nt-primary);
}
.notif-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
    margin-bottom: 14px;
}
.notif-metric,
.notif-panel {
    background: rgba(255,255,255,.92);
    border: 1px solid var(--nt-line);
    border-radius: 12px;
    box-shadow: 0 18px 48px -42px rgba(15, 23, 42, .42);
}
.notif-metric {
    padding: 15px;
}
.notif-metric span {
    display: block;
    color: var(--nt-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.notif-metric strong {
    display: block;
    margin-top: 5px;
    font-size: 1.45rem;
    color: var(--nt-primary);
}
.notif-panel {
    overflow: hidden;
}
.notif-filter {
    display: flex;
    flex-wrap: wrap;
    align-items: end;
    gap: 10px;
    padding: 15px;
    border-bottom: 1px solid var(--nt-line);
    background: color-mix(in srgb, var(--nt-accent) 4%, #fff);
}
.notif-field {
    display: grid;
    gap: 5px;
}
.notif-field label {
    color: var(--nt-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.notif-field select {
    min-width: 170px;
    height: 39px;
    padding: 0 10px;
    border: 1px solid var(--nt-line);
    border-radius: 9px;
    background: #fff;
    color: #172033;
}
.notif-list {
    display: grid;
}
.notif-item {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 12px;
    padding: 16px;
    border-bottom: 1px solid #EEF0F4;
    transition: background .16s ease, box-shadow .16s ease;
}
.notif-item:last-child {
    border-bottom: 0;
}
.notif-item.is-clickable {
    cursor: pointer;
}
.notif-item.is-clickable:hover,
.notif-item.is-clickable:focus-visible {
    background: color-mix(in srgb, var(--nt-accent) 6%, #fff);
    box-shadow: inset 3px 0 0 color-mix(in srgb, var(--nt-accent) 76%, var(--nt-primary));
    outline: none;
}
.notif-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    color: var(--nt-primary);
    background: color-mix(in srgb, var(--nt-accent) 12%, #fff);
    border: 1px solid color-mix(in srgb, var(--nt-accent) 26%, #E5E7EB);
}
.notif-item.is-nueva .notif-icon {
    background: color-mix(in srgb, var(--nt-accent) 18%, #fff);
}
.notif-item-main {
    min-width: 0;
}
.notif-topline {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 5px;
}
.notif-name {
    color: #172033;
    font-weight: 900;
}
.notif-message {
    color: #344054;
    margin: 0;
}
.notif-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
    color: var(--nt-muted);
    font-size: .78rem;
}
.notif-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: 999px;
    font-size: .72rem;
    font-weight: 900;
}
.notif-badge.estado-nueva { background:#DBEAFE; color:#1D4ED8; }
.notif-badge.estado-leida { background:#F3F4F6; color:#374151; }
.notif-badge.estado-resuelta { background:#DCFCE7; color:#166534; }
.notif-badge.estado-descartada { background:#F3F4F6; color:#4B5563; }
.notif-badge.sev-info { background:#F3F4F6; color:#374151; }
.notif-badge.sev-media { background:#FEF3C7; color:#92400E; }
.notif-badge.sev-alta { background:#FFEDD5; color:#C2410C; }
.notif-badge.sev-critica { background:#FEE2E2; color:#991B1B; }
.notif-empty {
    padding: 42px 18px;
    text-align: center;
    color: var(--nt-muted);
}
.notif-empty i {
    display: inline-grid;
    place-items: center;
    width: 48px;
    height: 48px;
    margin-bottom: 10px;
    border-radius: 12px;
    color: var(--nt-primary);
    background: color-mix(in srgb, var(--nt-accent) 12%, #fff);
}
.notif-alert {
    margin-bottom: 14px;
    padding: 12px 14px;
    border-radius: 10px;
    border: 1px solid var(--nt-line);
    background: #fff;
    color: #344054;
}
.notif-alert.success { border-color:#BBF7D0; background:#F0FDF4; color:#166534; }
.notif-alert.error { border-color:#FECACA; background:#FEF2F2; color:#991B1B; }
@media (max-width: 900px) {
    .notif-hero,
    .notif-item {
        grid-template-columns: 1fr;
    }
    .notif-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 560px) {
    .notif-shell {
        width: min(100% - 18px, 1320px);
        padding-top: 18px;
    }
    .notif-metrics {
        grid-template-columns: 1fr;
    }
    .notif-title {
        font-size: 1.45rem;
    }
    .notif-field,
    .notif-field select,
    .notif-btn {
        width: 100%;
    }
}

/* Propuesta nueva: bandeja operativa con panel de mando y timeline. */
.notif-view {
    --nt-ink: #111827;
    --nt-ink-soft: #334155;
    --nt-muted-strong: #526176;
    --nt-paper: #FFFDF8;
    --nt-warm: color-mix(in srgb, var(--nt-accent) 7%, #FFFDF8);
    --nt-brand-readable: color-mix(in srgb, var(--nt-primary) 78%, #111827);
    --nt-accent-readable: color-mix(in srgb, var(--nt-accent) 68%, #3B2D12);
    background:
        radial-gradient(circle at 10% 7%, color-mix(in srgb, var(--nt-accent) 20%, transparent), transparent 28rem),
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--nt-primary) 9%, transparent), transparent 30rem),
        linear-gradient(135deg, rgba(255,255,255,.34) 0 25%, transparent 25% 50%) 0 0 / 28px 28px,
        linear-gradient(180deg, #FFFCF7 0%, color-mix(in srgb, var(--nt-accent) 7%, #F7F3EC) 58%, #EFE7DA 100%);
    color: var(--nt-ink);
}

.notif-shell {
    width: min(1560px, calc(100% - 32px));
    padding: 30px 0 54px;
}

.notif-alert {
    border-radius: 18px;
    box-shadow: 0 18px 42px -34px rgba(17,24,39,.5);
}

.notif-command {
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(320px, .92fr);
    gap: 16px;
    align-items: stretch;
    margin-bottom: 18px;
}

.notif-hero {
    position: relative;
    overflow: hidden;
    min-height: 278px;
    display: flex;
    align-items: flex-end;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 18%, #DFD4C3);
    border-radius: 28px;
    background:
        radial-gradient(circle at 90% 0%, color-mix(in srgb, var(--nt-accent) 28%, transparent), transparent 18rem),
        linear-gradient(145deg, #111827, color-mix(in srgb, var(--nt-primary) 68%, #111827));
    box-shadow: 0 28px 72px -56px rgba(17,24,39,.82);
    padding: clamp(24px, 3vw, 38px);
    margin: 0;
}

.notif-hero::before {
    content: "";
    position: absolute;
    inset: 22px auto 22px 0;
    width: 5px;
    border-radius: 0 999px 999px 0;
    background: linear-gradient(180deg, var(--nt-accent), color-mix(in srgb, var(--nt-primary) 72%, var(--nt-accent)));
}

.notif-hero-mark {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 20px;
    background: rgba(255,255,255,.1);
    color: color-mix(in srgb, var(--nt-accent) 72%, #FFFFFF);
    font-size: 1.35rem;
    margin-bottom: 18px;
}

.notif-kicker {
    color: color-mix(in srgb, var(--nt-accent) 72%, #FFFFFF);
}

.notif-title {
    max-width: 780px;
    color: #FFFFFF;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(3rem, 6vw, 6.25rem);
    line-height: .86;
    font-weight: 800;
    letter-spacing: 0;
    text-wrap: balance;
}

.notif-copy {
    max-width: 66ch;
    color: rgba(255,255,255,.72);
    font-size: 1rem;
    line-height: 1.55;
    font-weight: 650;
}

.notif-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin: 0;
}

.notif-metric {
    position: relative;
    overflow: hidden;
    min-height: 132px;
    display: grid;
    align-content: space-between;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 18%, #DFD4C3);
    border-radius: 22px;
    background: rgba(255,255,255,.88);
    box-shadow: 0 22px 58px -50px rgba(17,24,39,.65);
    padding: 18px;
}

.notif-metric::after {
    content: "";
    position: absolute;
    inset: auto 18px 0 auto;
    width: 74px;
    height: 4px;
    border-radius: 999px 999px 0 0;
    background: var(--metric-color, var(--nt-accent));
    opacity: .9;
}

.notif-metric span {
    color: var(--nt-muted-strong);
    letter-spacing: .05em;
}

.notif-metric strong {
    color: var(--nt-ink);
    font-size: clamp(2.1rem, 4vw, 3.25rem);
    line-height: .95;
    font-variant-numeric: tabular-nums;
}

.notif-workspace {
    display: grid;
    grid-template-columns: minmax(270px, 330px) minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

.notif-filter-panel {
    position: sticky;
    top: 18px;
    display: grid;
    gap: 12px;
    min-width: 0;
}

.notif-filter-card,
.notif-panel {
    border: 1px solid color-mix(in srgb, var(--nt-accent) 18%, #DFD4C3);
    border-radius: 24px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 24px 64px -54px rgba(17,24,39,.72);
}

.notif-filter-card {
    overflow: hidden;
}

.notif-filter-heading {
    padding: 18px;
    border-bottom: 1px solid color-mix(in srgb, var(--nt-accent) 16%, #E7DCCB);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--nt-accent) 16%, transparent), transparent 12rem),
        #FFFFFF;
}

.notif-filter-heading h2,
.notif-inbox-head h2 {
    margin: 0;
    color: var(--nt-ink);
    font-size: 1.05rem;
    font-weight: 950;
    line-height: 1.2;
}

.notif-filter-heading p,
.notif-inbox-head p {
    margin: 5px 0 0;
    color: var(--nt-muted);
    font-size: .82rem;
    line-height: 1.45;
    font-weight: 700;
}

.notif-filter {
    display: grid;
    gap: 13px;
    padding: 16px;
    border-bottom: 0;
    background: transparent;
}

.notif-field {
    gap: 7px;
}

.notif-field label {
    color: var(--nt-ink-soft);
    letter-spacing: .04em;
}

.notif-field select {
    width: 100%;
    min-width: 0;
    height: 46px;
    border-color: color-mix(in srgb, var(--nt-accent) 18%, #E1D7C8);
    border-radius: 14px;
    background: #FFFFFF;
    color: var(--nt-ink);
    font-weight: 750;
    outline: none;
}

.notif-field select:focus {
    border-color: color-mix(in srgb, var(--nt-accent) 62%, var(--nt-line));
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--nt-accent) 16%, transparent);
}

.notif-filter-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.notif-btn {
    min-height: 44px;
    border-radius: 14px;
    color: var(--nt-ink);
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.notif-btn:hover {
    transform: translateY(-1px);
}

.notif-btn.is-primary {
    border-color: #111827;
    background: #111827;
    color: #FFFFFF;
}

.notif-legend {
    display: grid;
    gap: 8px;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 18%, #DFD4C3);
    border-radius: 20px;
    background: rgba(255,253,248,.82);
}

.notif-legend span {
    display: flex;
    align-items: center;
    gap: 9px;
    color: var(--nt-ink-3, #475569);
    font-size: .78rem;
    font-weight: 750;
}

.notif-legend i {
    color: var(--nt-accent-readable);
}

.notif-push-card {
    padding: 16px;
}

.notif-push-card h2 {
    margin: 0;
    color: var(--nt-ink);
    font-size: 1rem;
    font-weight: 950;
}

.notif-push-card p {
    margin: 7px 0 0;
    color: var(--nt-muted);
    font-size: .82rem;
    line-height: 1.45;
    font-weight: 700;
}

.notif-push-actions {
    display: grid;
    gap: 8px;
    margin-top: 13px;
}

.notif-push-card[data-push-state="enabled"] .notif-btn.is-primary {
    border-color: color-mix(in srgb, #DC2626 72%, #111827);
    background: color-mix(in srgb, #DC2626 88%, #111827);
}

.notif-inbox-panel {
    overflow: hidden;
}

.notif-inbox-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid color-mix(in srgb, var(--nt-accent) 16%, #E7DCCB);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--nt-accent) 14%, transparent), transparent 14rem),
        #FFFFFF;
}

.notif-inbox-count {
    display: inline-flex;
    align-items: center;
    min-height: 32px;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 24%, #E5DACA);
    border-radius: 12px;
    background: color-mix(in srgb, var(--nt-accent) 8%, #FFFFFF);
    color: var(--nt-accent-readable);
    padding: 0 10px;
    font-size: .76rem;
    font-weight: 950;
    white-space: nowrap;
}

.notif-list {
    gap: 12px;
    padding: 16px;
    background: linear-gradient(180deg, rgba(255,253,248,.52), rgba(255,255,255,.74));
}

.notif-item {
    position: relative;
    grid-template-columns: 54px minmax(0, 1fr) auto;
    align-items: start;
    gap: 14px;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 14%, #E7DCCB);
    border-radius: 20px;
    background: #FFFFFF;
    padding: 16px;
    box-shadow: 0 14px 36px -32px rgba(17,24,39,.58);
}

.notif-item::before {
    content: "";
    position: absolute;
    inset: 14px auto 14px 0;
    width: 4px;
    border-radius: 0 999px 999px 0;
    background: var(--row-color, #94A3B8);
}

.notif-item:last-child {
    border-bottom: 1px solid color-mix(in srgb, var(--nt-accent) 14%, #E7DCCB);
}

.notif-item.is-nueva,
.notif-item.is-leida {
    --row-color: color-mix(in srgb, var(--nt-accent) 70%, #F59E0B);
}

.notif-icon {
    width: 54px;
    height: 54px;
    border-radius: 18px;
    color: var(--nt-accent-readable);
    background: color-mix(in srgb, var(--nt-accent) 11%, #FFFFFF);
}

.notif-name {
    color: var(--nt-ink);
    font-size: 1rem;
}

.notif-message {
    color: var(--nt-ink-soft);
    line-height: 1.5;
    font-weight: 650;
}

.notif-meta {
    color: var(--nt-muted);
}

.notif-badge {
    border-radius: 10px;
}

.notif-open-indicator {
    align-self: center;
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--nt-accent) 18%, #E7DCCB);
    border-radius: 12px;
    color: var(--nt-muted);
    background: color-mix(in srgb, var(--nt-accent) 4%, #FFFFFF);
}

.notif-empty {
    border: 1px dashed color-mix(in srgb, var(--nt-accent) 24%, #D9CDBB);
    border-radius: 20px;
    background: rgba(255,255,255,.72);
}

.notif-empty i {
    color: var(--nt-accent-readable);
}

@media (max-width: 1180px) {
    .notif-command,
    .notif-workspace {
        grid-template-columns: 1fr;
    }

    .notif-filter-panel {
        position: static;
    }

    .notif-filter {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        align-items: end;
    }

    .notif-filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 760px) {
    .notif-shell {
        width: min(100% - 20px, 1560px);
    }

    .notif-hero {
        min-height: 240px;
        border-radius: 22px;
    }

    .notif-title {
        font-size: clamp(2.35rem, 13vw, 3.5rem);
    }

    .notif-metrics,
    .notif-filter {
        grid-template-columns: 1fr;
    }

    .notif-item {
        grid-template-columns: 48px minmax(0, 1fr);
    }

    .notif-open-indicator {
        display: none;
    }
}
</style>

<div class="notif-view">
    <div class="notif-shell">
        <?php if ($mensajeFlash): ?>
            <div class="notif-alert <?= notif_safe($mensajeFlash['tipo'] ?? 'info', 'info') ?>">
                <?= notif_safe($mensajeFlash['texto'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="notif-command">
            <div class="notif-hero">
                <div>
                    <span class="notif-hero-mark">
                        <i class="fas fa-bell"></i>
                    </span>
                    <div class="notif-kicker">Centro operativo</div>
                    <h1 class="notif-title">Notificaciones</h1>
                    <p class="notif-copy">Pendientes operativos del hotel en un solo lugar. Los avisos informativos se archivan al abrirlos; lo operativo sale de pendientes cuando se atiende en su modulo.</p>
                </div>
            </div>

            <section class="notif-metrics" aria-label="Resumen de notificaciones">
                <div class="notif-metric" style="--metric-color: color-mix(in srgb, var(--nt-accent) 70%, #F59E0B);">
                    <span>Pendientes</span>
                    <strong><?= $notificacionesPendientes ?></strong>
                </div>
                <div class="notif-metric" style="--metric-color: #DC2626;">
                    <span>Prioritarias</span>
                    <strong><?= (int)($resumen['prioritarias'] ?? 0) ?></strong>
                </div>
                <div class="notif-metric" style="--metric-color: #2563EB;">
                    <span>Hoy</span>
                    <strong><?= (int)($resumen['hoy'] ?? 0) ?></strong>
                </div>
                <div class="notif-metric" style="--metric-color: #16A34A;">
                    <span>Historial</span>
                    <strong><?= $notificacionesHistorial ?></strong>
                </div>
            </section>
        </section>

        <div class="notif-workspace">
            <aside class="notif-filter-panel" aria-label="Filtros de notificaciones">
                <section class="notif-filter-card">
                    <div class="notif-filter-heading">
                        <h2>Control de bandeja</h2>
                        <p>Filtra por estado, modulo y prioridad sin salir del centro operativo.</p>
                    </div>
                    <form method="GET" action="<?= url('notificaciones') ?>" class="notif-filter">
                        <div class="notif-field">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado">
                                <?php foreach (['activas', 'resuelta', 'descartada'] as $estado): ?>
                                    <option value="<?= notif_safe($estado, '') ?>" <?= $estadoFiltro === $estado ? 'selected' : '' ?>>
                                        <?= notif_safe(notif_label($estado)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="notif-field">
                            <label for="modulo">Modulo</label>
                            <select id="modulo" name="modulo">
                                <option value="">Todos</option>
                                <?php foreach ($modulos as $modulo): ?>
                                    <?php $moduloClave = (string)($modulo['modulo'] ?? ''); ?>
                                    <option value="<?= notif_safe($moduloClave, '') ?>" <?= $moduloFiltro === $moduloClave ? 'selected' : '' ?>>
                                        <?= notif_safe(notif_label($moduloClave)) ?> (<?= (int)($modulo['total'] ?? 0) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="notif-field">
                            <label for="severidad">Severidad</label>
                            <select id="severidad" name="severidad">
                                <option value="">Todas</option>
                                <?php foreach (['info', 'media', 'alta', 'critica'] as $severidad): ?>
                                    <option value="<?= notif_safe($severidad, '') ?>" <?= $severidadFiltro === $severidad ? 'selected' : '' ?>>
                                        <?= notif_safe(notif_label($severidad)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="notif-filter-actions">
                            <button type="submit" class="notif-btn is-primary">
                                <i class="fas fa-filter"></i>
                                <span>Filtrar</span>
                            </button>
                            <a href="<?= url('notificaciones') ?>" class="notif-btn">
                                <i class="fas fa-rotate-left"></i>
                                <span>Limpiar</span>
                            </a>
                        </div>
                    </form>
                </section>

                <div class="notif-legend">
                    <span><i class="fas fa-circle"></i> Pendiente: requiere seguimiento operativo.</span>
                    <span><i class="fas fa-bolt"></i> Prioridad alta o critica: atender primero.</span>
                    <span><i class="fas fa-arrow-up-right-from-square"></i> Los reportes vistos se limpian de pendientes automaticamente.</span>
                </div>

                <section class="notif-filter-card notif-push-card"
                         data-pwa-push-panel
                         data-public-key-url="<?= url('api/pwa-push/public-key') ?>"
                         data-subscribe-url="<?= url('api/pwa-push/subscribe') ?>"
                         data-unsubscribe-url="<?= url('api/pwa-push/unsubscribe') ?>"
                         data-test-url="<?= url('api/pwa-push/test') ?>">
                    <h2>Avisos en este dispositivo</h2>
                    <p data-pwa-push-status>Revisando compatibilidad del navegador...</p>
                    <div class="notif-push-actions">
                        <button type="button" class="notif-btn is-primary" data-pwa-push-toggle>
                            <i class="fas fa-bell"></i>
                            <span data-pwa-push-label>Activar en este dispositivo</span>
                        </button>
                        <button type="button" class="notif-btn" data-pwa-push-test hidden>
                            <i class="fas fa-paper-plane"></i>
                            <span>Enviar prueba</span>
                        </button>
                    </div>
                </section>
            </aside>

            <section class="notif-panel notif-inbox-panel">
                <div class="notif-inbox-head">
                    <div>
                        <h2>Bandeja de seguimiento</h2>
                        <p>Notificaciones ordenadas para revisar rapido lo pendiente del hotel.</p>
                    </div>
                    <span class="notif-inbox-count">
                        <?= count($notificaciones) ?> registros
                    </span>
                </div>

                <div class="notif-list">
                    <?php if (!$tablaDisponible): ?>
                        <div class="notif-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p>La tabla de notificaciones todavia no esta instalada.</p>
                        </div>
                    <?php elseif (empty($notificaciones)): ?>
                        <div class="notif-empty">
                            <i class="fas fa-circle-check"></i>
                            <p>No hay notificaciones para los filtros seleccionados.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notificaciones as $notificacion): ?>
                            <?php
                                $id = (int)($notificacion['id'] ?? 0);
                                $estado = (string)($notificacion['estado'] ?? 'nueva');
                                $severidad = (string)($notificacion['severidad'] ?? 'info');
                                $modulo = (string)($notificacion['modulo'] ?? 'sistema');
                                $esAutomatica = strpos((string)($notificacion['tipo'] ?? ''), 'regla_') === 0;
                                $urlDestino = trim((string)($notificacion['url'] ?? ''));
                                $urlDestinoFinal = ($urlDestino !== '' && $id > 0) ? url('notificaciones/' . $id . '/abrir') : '';
                                $tituloNotificacion = (string)($notificacion['titulo'] ?? 'Notificacion');
                            ?>
                            <article class="notif-item is-<?= notif_safe($estado, 'nueva') ?><?= $urlDestinoFinal !== '' ? ' is-clickable' : '' ?>"
                                     <?php if ($urlDestinoFinal !== ''): ?>
                                         data-notif-url="<?= htmlspecialchars($urlDestinoFinal, ENT_QUOTES, 'UTF-8') ?>"
                                         role="link"
                                         tabindex="0"
                                         aria-label="Ver <?= notif_safe($tituloNotificacion) ?>"
                                     <?php endif; ?>>
                                <div class="notif-icon">
                                    <?php if ($modulo === 'caja'): ?>
                                        <i class="fas fa-wallet"></i>
                                    <?php elseif ($modulo === 'habitaciones'): ?>
                                        <i class="fas fa-bed"></i>
                                    <?php elseif ($modulo === 'facturacion'): ?>
                                        <i class="fas fa-file-invoice"></i>
                                    <?php elseif ($modulo === 'reportes'): ?>
                                        <i class="fas fa-chart-line"></i>
                                    <?php else: ?>
                                        <i class="fas fa-bell"></i>
                                    <?php endif; ?>
                                </div>

                                <div class="notif-item-main">
                                    <div class="notif-topline">
                                        <span class="notif-name"><?= notif_safe($notificacion['titulo'] ?? '') ?></span>
                                        <span class="notif-badge estado-<?= notif_safe($estado, 'nueva') ?>"><?= notif_safe(notif_label($estado)) ?></span>
                                        <span class="notif-badge sev-<?= notif_safe($severidad, 'info') ?>"><?= notif_safe(notif_label($severidad)) ?></span>
                                    </div>
                                    <p class="notif-message"><?= notif_safe($notificacion['mensaje'] ?? '') ?></p>
                                    <div class="notif-meta">
                                        <span><i class="fas fa-layer-group"></i> <?= notif_safe(notif_label($modulo)) ?></span>
                                        <?php if ($esAutomatica): ?>
                                            <span><i class="fas fa-rotate"></i> Automatica</span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-clock"></i> <?= notif_safe(notif_date($notificacion['created_at'] ?? null)) ?></span>
                                    </div>
                                </div>

                                <?php if ($urlDestinoFinal !== ''): ?>
                                    <span class="notif-open-indicator" aria-hidden="true">
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
</div>

<script>
document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.notif-item[data-notif-url]');
    if (!item || event.target.closest('a, button, input, select, textarea, label')) {
        return;
    }

    window.location.href = item.dataset.notifUrl;
});

document.addEventListener('keydown', function (event) {
    if (!(event.target instanceof Element)) {
        return;
    }

    const item = event.target.closest('.notif-item[data-notif-url]');
    if (!item || !['Enter', ' '].includes(event.key)) {
        return;
    }

    event.preventDefault();
    window.location.href = item.dataset.notifUrl;
});
</script>
