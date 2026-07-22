<?php
/**
 * Tablero interno de reputacion (bloque reputacion).
 */
$kpis = $kpis ?? [];
$filas = $filas ?? [];
$slugHotel = $slugHotel ?? '';
$config = $config ?? ['google_review_url' => '', 'umbral_alerta' => 3];

$repSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$repBadge = static function ($estado) {
    $map = [
        'respondida' => ['Respondida', 'is-ok', 'fa-comment-dots'],
        'enviada' => ['Enviada, sin responder', 'is-warning', 'fa-envelope'],
        'pendiente' => ['Link listo', 'is-info', 'fa-link'],
        'expirada' => ['Expirada', 'is-muted', 'fa-hourglass-end'],
    ];
    return $map[$estado] ?? ['Sin encuesta', 'is-soft', 'fa-circle-dot'];
};
$repEstrellas = static function ($n) {
    $n = max(0, min(5, (int) $n));
    if ($n < 1) {
        return '';
    }

    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="' . ($i <= $n ? 'fa-solid' : 'fa-regular') . ' fa-star" aria-hidden="true"></i>';
    }
    return $html;
};

// Copiloto IA (bloque copiloto_ia): la UI se muestra como teaser aunque el
// hotel no tenga el bloque (el servidor administra la prueba gratis), pero
// solo si el servidor tiene la IA configurada.
$repIaOk = trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== '';
$repIaMeses = [];
$repNombresMes = [1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
for ($i = 0; $i < 6; $i++) {
    $ts = strtotime(date('Y-m-01') . " -{$i} months");
    $repIaMeses[date('Y-m', $ts)] = $repNombresMes[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

$promedio = array_key_exists('promedio', $kpis) && $kpis['promedio'] !== null
    ? number_format((float) $kpis['promedio'], 1)
    : '-';
$respondidas = (int) ($kpis['respondidas'] ?? 0);
$generadas = (int) ($kpis['generadas'] ?? 0);
$tasaRespuesta = array_key_exists('tasa_respuesta', $kpis) && $kpis['tasa_respuesta'] !== null
    ? ((int) $kpis['tasa_respuesta']) . '%'
    : '-';
$nps = array_key_exists('nps', $kpis) && $kpis['nps'] !== null ? (string) ((int) $kpis['nps']) : '-';
$totalCheckouts = count($filas);
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.rep {
    --rep-brand: var(--brand-primary, #1B2746);
    --rep-brand-2: var(--brand-secondary, #0F172A);
    --rep-gold: var(--brand-accent, #BD9441);
    --rep-gold-soft: color-mix(in srgb, var(--rep-gold) 15%, #FFFFFF);
    --rep-gold-line: color-mix(in srgb, var(--rep-gold) 42%, #E4D4B0);
    --rep-gold-ink: color-mix(in srgb, var(--rep-gold) 58%, var(--rep-brand));
    --rep-ivory: #F5F5F7;
    --rep-ivory-2: #FAFAFC;
    --rep-surface: #FFFFFF;
    --rep-surface-warm: #F5F5F7;
    --rep-border: color-mix(in srgb, var(--rep-brand) 6%, #E9E1D6);
    --rep-ring: color-mix(in srgb, var(--rep-gold) 32%, transparent);
    --rep-text: color-mix(in srgb, var(--rep-brand) 46%, #707B8C);
    --rep-muted: #8791A2;
    --rep-heading: color-mix(in srgb, var(--rep-brand) 66%, #566172);
    --rep-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rep-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rep-success: #1E9E63;
    --rep-success-bg: #E7F4EC;
    --rep-warning: #C2841C;
    --rep-warning-bg: #FAF0DC;
    --rep-danger: #B4392B;
    --rep-danger-bg: #F8EAE5;
    --rep-info: #2F77E0;
    --rep-info-bg: #E6EFFC;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 40px;
    color: var(--rep-text);
    font-family: var(--rep-sans);
    font-size: .92rem;
    
}
.rep * { box-sizing: border-box; }
.rep-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 1100px;
    min-width: 0;
    margin: 0 auto;
}
.rep-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.rep-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 780px);
}
.rep-title-lockup > div:last-child { min-width: 0; }
.rep-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--rep-gold), var(--rep-brand) 54%, color-mix(in srgb, var(--rep-brand) 68%, var(--rep-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--rep-brand) 72%, transparent);
}
.rep-kicker {
    margin: 0 0 2px;
    color: var(--rep-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.rep-title {
    margin: 0;
    color: var(--rep-heading);
    font-family: var(--rep-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.rep-subtitle {
    max-width: 50rem;
    margin: 9px 0 0;
    color: var(--rep-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.rep-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid var(--rep-gold-line);
    background: var(--rep-gold-soft);
    color: var(--rep-gold-ink);
    font-size: .78rem;
    font-weight: 650;
    white-space: nowrap;
}
.rep-alert {
    padding: 12px 14px;
    border-radius: 13px;
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.45;
}
.rep-alert.is-success { background: var(--rep-success-bg); color: color-mix(in srgb, var(--rep-success) 70%, var(--rep-text)); border: 1px solid color-mix(in srgb, var(--rep-success) 24%, #fff); }
.rep-alert.is-error { background: var(--rep-danger-bg); color: color-mix(in srgb, var(--rep-danger) 72%, var(--rep-text)); border: 1px solid color-mix(in srgb, var(--rep-danger) 24%, #fff); }
.rep-alert.is-info { background: var(--rep-gold-soft); color: var(--rep-gold-ink); border: 1px solid var(--rep-gold-line); }
.rep-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.rep-kpi {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--rep-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.rep-kpi .nombre {
    color: var(--rep-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.rep-kpi .valor {
    margin-top: 2px;
    color: var(--rep-heading);
    font-family: var(--rep-serif);
    font-size: 1.72rem;
    font-weight: 650;
    line-height: 1.1;
}
.rep-kpi .valor.is-good { color: color-mix(in srgb, var(--rep-success) 68%, var(--rep-text)); }
.rep-kpi .valor small {
    color: var(--rep-muted);
    font-family: var(--rep-sans);
    font-size: .82rem;
    font-weight: 560;
}
.rep-card {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--rep-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
    overflow: hidden;
}
.rep-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--rep-border);
}
.rep-card-title {
    margin: 0;
    color: var(--rep-heading);
    font-size: .9rem;
    font-weight: 650;
}
.rep-card-sub {
    margin: 3px 0 0;
    color: var(--rep-muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.45;
}
.rep-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rep-gold) 10%, #FFFFFF);
    color: var(--rep-gold-ink);
    border: 1px solid color-mix(in srgb, var(--rep-gold) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}
.rep-table-wrap { overflow-x: auto; }
.rep-table {
    width: 100%;
    min-width: 840px;
    border-collapse: collapse;
    font-size: .85rem;
}
.rep-table thead {
    background: var(--rep-surface-warm);
    border-bottom: 1px solid var(--rep-border);
}
.rep-table th {
    padding: 12px 16px;
    color: var(--rep-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}
.rep-table th.is-end,
.rep-table td.is-end { text-align: right; }
.rep-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--rep-border);
    vertical-align: middle;
}
.rep-table tbody tr:last-child td { border-bottom: 0; }
.rep-table tbody tr { transition: background .16s ease, box-shadow .16s ease; }
.rep-table tbody tr:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}
.rep-date,
.rep-guest-name,
.rep-cell-strong {
    color: var(--rep-heading);
    font-weight: 650;
}
.rep-date { white-space: nowrap; }
.rep-guest-sub {
    margin-top: 3px;
    color: var(--rep-muted);
    font-size: .74rem;
    font-weight: 500;
}
.rep-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    border: 1px solid transparent;
    font-size: .74rem;
    font-weight: 650;
    white-space: nowrap;
}
.rep-badge.is-ok { color: color-mix(in srgb, var(--rep-success) 70%, var(--rep-text)); background: var(--rep-success-bg); border-color: color-mix(in srgb, var(--rep-success) 24%, #fff); }
.rep-badge.is-warning { color: color-mix(in srgb, var(--rep-warning) 72%, var(--rep-text)); background: var(--rep-warning-bg); border-color: color-mix(in srgb, var(--rep-warning) 26%, #fff); }
.rep-badge.is-info { color: color-mix(in srgb, var(--rep-info) 70%, var(--rep-text)); background: var(--rep-info-bg); border-color: color-mix(in srgb, var(--rep-info) 22%, #fff); }
.rep-badge.is-muted,
.rep-badge.is-soft { color: var(--rep-muted); background: var(--rep-surface-warm); border-color: var(--rep-border); }
.rep-stars {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    color: var(--rep-gold);
    white-space: nowrap;
}
.rep-nps {
    margin-top: 4px;
    color: var(--rep-muted);
    font-size: .74rem;
    font-weight: 560;
}
.rep-comentario {
    max-width: 280px;
    margin-top: 5px;
    color: var(--rep-muted);
    font-size: .78rem;
    line-height: 1.45;
}
.rep-muted-dash { color: var(--rep-muted); }
.rep-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}
.rep-inline-form {
    display: inline-flex;
    margin: 0;
}
.rep-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid transparent;
    border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--rep-gold) 86%, #fff), color-mix(in srgb, var(--rep-gold) 72%, var(--rep-brand)));
    color: #fff;
    cursor: pointer;
    font-family: inherit;
    font-size: .84rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--rep-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease;
}
.rep-btn:hover:not(:disabled) { transform: translateY(-1px); }
.rep-btn:active:not(:disabled) { transform: translateY(0) scale(.98); }
.rep-btn:focus-visible,
.rep-control:focus-visible {
    outline: 3px solid var(--rep-ring);
    outline-offset: 2px;
}
.rep-btn.sec {
    background: var(--rep-surface);
    color: var(--rep-gold-ink);
    border-color: var(--rep-gold-line);
    box-shadow: none;
}
.rep-empty {
    padding: 42px 18px !important;
    text-align: center;
    color: var(--rep-muted);
    background: var(--rep-ivory-2);
}
.rep-config {
    display: grid;
    gap: 12px;
    padding: 16px;
}
.rep-config .rep-card-head {
    padding: 0;
    border-bottom: 0;
}
.rep-config-grid {
    display: grid;
    grid-template-columns: minmax(240px, 2fr) minmax(180px, 1fr) auto;
    gap: 10px;
    align-items: end;
}
.rep-field {
    display: grid;
    gap: 5px;
    min-width: 0;
}
.rep-field label {
    color: var(--rep-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .035em;
    text-transform: uppercase;
}
.rep-control {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--rep-border);
    border-radius: 11px;
    background: var(--rep-surface-warm);
    color: var(--rep-text);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 560;
    padding: 0 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.rep-control::placeholder {
    color: color-mix(in srgb, var(--rep-muted) 82%, #B8C0CB);
    font-weight: 520;
}
.rep-control:focus {
    border-color: var(--rep-gold);
    background: #fff;
    box-shadow: 0 0 0 3px var(--rep-ring);
    outline: none;
}
.rep-hint {
    margin: 0;
    color: var(--rep-muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.5;
}

/* Copiloto IA (bloque copiloto_ia) */
.repia-tag { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 999px; font-size: .68rem; font-weight: 650; letter-spacing: .03em; background: var(--rep-gold-soft); color: var(--rep-gold-ink); vertical-align: 2px; }
.repia-btn-resena { display: inline-flex; align-items: center; gap: 4px; margin-top: 6px; padding: 3px 9px; border: 1px solid var(--rep-gold-line); border-radius: 999px; background: var(--rep-surface); color: var(--rep-gold-ink); font-family: inherit; font-size: .74rem; font-weight: 650; cursor: pointer; }
.repia-btn-resena:hover { background: var(--rep-gold-soft); }
.repia-panel { padding: 12px 14px; background: var(--rep-ivory-2); }
.repia-texto { font-size: .88rem; color: var(--rep-text); line-height: 1.55; background: var(--rep-surface); border: 1px solid var(--rep-border); border-radius: 10px; padding: 12px 14px; white-space: normal; }
.repia-meta { font-size: .74rem; color: var(--rep-muted); margin-top: 6px; }
.repia-botones { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
.repia-upsell { font-size: .85rem; background: var(--rep-gold-soft); border: 1px solid var(--rep-gold-line); color: var(--rep-gold-ink); border-radius: 10px; padding: 12px 14px; line-height: 1.5; }
.repia-error { font-size: .85rem; background: var(--rep-danger-bg); border: 1px solid color-mix(in srgb, var(--rep-danger) 24%, #fff); color: var(--rep-danger); border-radius: 10px; padding: 10px 12px; }
.repia-controles { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; padding: 0 16px 14px; }
.repia-controles select { min-height: 40px; border: 1px solid var(--rep-border); border-radius: 11px; background: var(--rep-surface-warm); color: var(--rep-text); font-family: inherit; font-size: .88rem; font-weight: 560; padding: 0 12px; }

@media (max-width: 900px) {
    .rep-hero-section {
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 12px;
    }
    .rep-status-pill { justify-self: start; }
    .rep-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .rep-config-grid { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .rep {
        padding: 14px 12px 34px;
    }
    .rep-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }
    .rep-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }
    .rep-title { font-size: 2rem; }
    .rep-kpis { grid-template-columns: 1fr; }
    .rep-card-head {
        display: grid;
        grid-template-columns: 1fr;
    }
    .rep-count-pill { justify-self: start; }
    .rep-table { min-width: 760px; }
    .rep-actions,
    .rep-inline-form,
    .rep-actions .rep-btn,
    .rep-config-grid .rep-btn {
        width: 100%;
    }
}
@media (prefers-reduced-motion: reduce) {
    .rep *,
    .rep *::before,
    .rep *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<div class="rep">
    <div class="rep-shell">
        <section class="rep-hero-section">
            <div class="rep-title-lockup">
                <div class="rep-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-star"></i>
                </div>
                <div>
                    <p class="rep-kicker">Reputaci&oacute;n del hotel</p>
                    <h1 class="rep-title">Opiniones y encuestas</h1>
                    <p class="rep-subtitle">Manda la encuesta a tus checkouts recientes; las buenas calificaciones van a Google y las bajas te llegan a ti primero.</p>
                </div>
            </div>
            <span class="rep-status-pill">
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i>
                Ultimos 30 dias
            </span>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="rep-alert is-<?= $repSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <section class="rep-kpis" aria-label="Indicadores de reputacion">
            <div class="rep-kpi">
                <div class="nombre">Calificacion promedio</div>
                <div class="valor <?= $promedio !== '-' ? 'is-good' : '' ?>"><?= $repSafe($promedio) ?> <small>/ 5</small></div>
            </div>
            <div class="rep-kpi">
                <div class="nombre">Encuestas respondidas</div>
                <div class="valor"><?= (int) $respondidas ?> <small>de <?= (int) $generadas ?></small></div>
            </div>
            <div class="rep-kpi">
                <div class="nombre">Tasa de respuesta</div>
                <div class="valor"><?= $repSafe($tasaRespuesta) ?></div>
            </div>
            <div class="rep-kpi">
                <div class="nombre">Recomendaci&oacute;n 0-10 (NPS) &middot; 90 d&iacute;as</div>
                <div class="valor"><?= $repSafe($nps) ?></div>
            </div>
        </section>

        <section class="rep-card">
            <div class="rep-card-head">
                <div>
                    <h2 class="rep-card-title">Salidas recientes</h2>
                    <p class="rep-card-sub">Genera, envia y copia encuestas para huespedes que ya hicieron checkout.</p>
                </div>
                <span class="rep-count-pill"><?= (int) $totalCheckouts ?> checkouts</span>
            </div>
            <div class="rep-table-wrap">
                <table class="rep-table">
                    <thead>
                        <tr>
                            <th>Salida</th>
                            <th>Huesped</th>
                            <th>Encuesta</th>
                            <th>Calificacion</th>
                            <th class="is-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($filas)): ?>
                        <tr><td colspan="5" class="rep-empty">No hay salidas en los &uacute;ltimos 30 d&iacute;as.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filas as $fila): ?>
                            <?php
                            [$badgeTexto, $badgeClass, $badgeIcon] = $repBadge($fila['encuesta_estado'] ?? null);
                            $urlPublica = !empty($fila['token'])
                                ? url('h/' . $slugHotel . '/encuesta/' . $fila['token'])
                                : null;
                            $estadoEncuesta = $fila['encuesta_estado'] ?? null;
                            $telefonoDigits = preg_replace('/\D/', '', substr((string) ($fila['telefono'] ?? ''), -10));
                            ?>
                            <tr>
                                <td class="rep-date"><?= $repSafe(date('d/m/Y', strtotime((string) $fila['fecha_salida']))) ?></td>
                                <td>
                                    <div class="rep-guest-name"><?= $repSafe($fila['nombre_completo']) ?></div>
                                    <div class="rep-guest-sub"><?= $repSafe($fila['email'] ?: ($fila['telefono'] ?: 'Sin contacto')) ?></div>
                                </td>
                                <td>
                                    <span class="rep-badge <?= $repSafe($badgeClass) ?>">
                                        <i class="fa-solid <?= $repSafe($badgeIcon) ?>" aria-hidden="true"></i>
                                        <?= $repSafe($badgeTexto) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($estadoEncuesta === 'respondida'): ?>
                                        <div class="rep-stars" title="<?= (int) $fila['calificacion'] ?> de 5"><?= $repEstrellas($fila['calificacion']) ?></div>
                                        <?php if ($fila['nps'] !== null): ?>
                                            <div class="rep-nps">NPS <?= (int) $fila['nps'] ?>/10</div>
                                        <?php endif; ?>
                                        <?php if (!empty($fila['comentario'])): ?>
                                            <div class="rep-comentario">"<?= $repSafe(mb_strimwidth((string) $fila['comentario'], 0, 140, '...', 'UTF-8')) ?>"</div>
                                        <?php endif; ?>
                                        <?php if ($repIaOk && !empty($fila['encuesta_id'])): ?>
                                            <button type="button" class="repia-btn-resena" data-encuesta="<?= (int) $fila['encuesta_id'] ?>">✨ Responder con IA</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="rep-muted-dash">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="is-end">
                                    <div class="rep-actions">
                                        <?php if (!$fila['token'] || $estadoEncuesta === 'expirada'): ?>
                                            <form method="POST" action="<?= url('reputacion/generar/' . (int) $fila['reservacion_id']) ?>" class="rep-inline-form">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="rep-btn">
                                                    <i class="fa-solid fa-link" aria-hidden="true"></i>Generar encuesta
                                                </button>
                                            </form>
                                        <?php elseif (in_array($estadoEncuesta, ['pendiente', 'enviada'], true)): ?>
                                            <?php if (!empty($fila['email'])): ?>
                                                <form method="POST" action="<?= url('reputacion/enviar/' . (int) $fila['encuesta_id']) ?>" class="rep-inline-form">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="rep-btn">
                                                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>Enviar correo
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!empty($fila['telefono'])): ?>
                                                <a class="rep-btn sec" target="_blank" rel="noopener"
                                                   href="https://wa.me/52<?= $repSafe($telefonoDigits) ?>?text=<?= rawurlencode('Hola ' . $fila['nombre_completo'] . ', gracias por hospedarte con nosotros. ¿Nos cuentas cómo te fue? ' . $urlPublica) ?>">
                                                    <i class="fab fa-whatsapp" aria-hidden="true"></i>WhatsApp
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="rep-btn sec"
                                                    data-link="<?= $repSafe($urlPublica) ?>"
                                                    onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado'; })">
                                                <i class="fa-solid fa-copy" aria-hidden="true"></i>Copiar link
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($repIaOk): ?>
        <section class="rep-card">
            <div class="rep-card-head">
                <div>
                    <h2 class="rep-card-title">Análisis del mes <span class="repia-tag">✨ Copiloto IA</span></h2>
                    <p class="rep-card-sub">Lee todas las encuestas del periodo y te dice qué se repite: las quejas, los elogios y qué atender primero.</p>
                </div>
            </div>
            <div class="repia-controles">
                <select id="repia-mes">
                    <?php foreach ($repIaMeses as $repMesVal => $repMesTxt): ?>
                        <option value="<?= $repSafe($repMesVal) ?>"><?= $repSafe($repMesTxt) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="repia-analizar" class="rep-btn">Ver análisis</button>
            </div>
            <div id="repia-analisis" class="repia-panel" style="border-top:1px dashed var(--rep-border);" hidden></div>
        </section>
        <?php endif; ?>

        <section class="rep-card rep-config">
            <div class="rep-card-head">
                <div>
                    <h2 class="rep-card-title">Configuracion</h2>
                    <p class="rep-card-sub">Define el destino de Google y el umbral que dispara alertas internas.</p>
                </div>
            </div>
            <form method="POST" action="<?= url('reputacion/config') ?>">
                <?= csrf_field() ?>
                <div class="rep-config-grid">
                    <div class="rep-field">
                        <label for="rep-google">Link de rese&ntilde;as de Google</label>
                        <input type="url" id="rep-google" name="google_review_url" maxlength="500"
                               placeholder="https://g.page/r/..." value="<?= $repSafe($config['google_review_url']) ?>" class="rep-control">
                    </div>
                    <div class="rep-field">
                        <label for="rep-umbral">Alertarme si califican con</label>
                        <select id="rep-umbral" name="umbral_alerta" class="rep-control">
                            <?php foreach ([1 => '1 estrella o menos', 2 => '2 estrellas o menos', 3 => '3 estrellas o menos', 4 => '4 estrellas o menos'] as $v => $txt): ?>
                                <option value="<?= $v ?>" <?= (int) $config['umbral_alerta'] === $v ? 'selected' : '' ?>><?= $txt ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="rep-btn">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Guardar
                        </button>
                    </div>
                </div>
                <p class="rep-hint">Al huesped que califica con 4-5 estrellas se le invita a dejar resena en Google con este link. Las calificaciones bajas generan una notificacion interna para el gerente si el bloque de notificaciones esta disponible.</p>
            </form>
        </section>
    </div>
</div>

<?php if ($repIaOk): ?>
<script>
(function () {
    'use strict';
    var URL_RESENA = <?= json_encode(url('copiloto-ia/resena')) ?>;
    var URL_ANALISIS = <?= json_encode(url('copiloto-ia/analisis')) ?>;
    var TOKEN = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '') ?>;

    function post(url, params, cb) {
        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        Object.keys(params).forEach(function (k) { datos.append(k, params[k]); });
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: datos.toString()
        }).then(function (r) { return r.json(); }).then(cb).catch(function () {
            cb({ success: false, message: 'No se pudo conectar. Revisa tu internet e intenta de nuevo.' });
        });
    }

    // Texto de la IA: se escapa todo y solo se permiten **negritas** y saltos de linea.
    function iaHtml(t) {
        var d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    }

    function pintar(cont, data, recargar, textoCopiable) {
        if (data.success) {
            var partes = ['<div class="repia-texto">' + iaHtml(data.texto) + '</div>'];
            partes.push('<div class="repia-meta">' + (data.desde_cache ? 'Generado el ' + iaHtml(data.generado_en || '') : 'Recién generado') + '</div>');
            if (data.prueba && typeof data.prueba.restantes === 'number') {
                partes.push('<div class="repia-meta">✨ Prueba gratis del bloque Copiloto IA · te quedan <strong>' + data.prueba.restantes + '</strong> usos de esta función.</div>');
            }
            partes.push('<div class="repia-botones">'
                + (textoCopiable ? '<button type="button" class="rep-btn repia-copiar">Copiar</button>' : '')
                + '<button type="button" class="rep-btn sec repia-regen">Regenerar</button>'
                + '</div>');
            cont.innerHTML = partes.join('');
            var btnCopiar = cont.querySelector('.repia-copiar');
            if (btnCopiar) {
                btnCopiar.addEventListener('click', function () {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(data.texto).then(function () { btnCopiar.textContent = 'Copiado ✓'; });
                    }
                });
            }
            cont.querySelector('.repia-regen').addEventListener('click', function () { recargar(true); });
        } else if (data.upsell) {
            cont.innerHTML = '<div class="repia-upsell">🔒 ' + iaHtml(data.message) + '</div>';
        } else {
            cont.innerHTML = '<div class="repia-error">' + iaHtml(data.message || 'No se pudo generar. Intenta de nuevo.') + '</div>';
        }
    }

    // Borrador de respuesta por encuesta (panel en una fila nueva de la tabla).
    document.querySelectorAll('.repia-btn-resena').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.dataset.encuesta;
            var tr = btn.closest('tr');
            var filaPanel = document.getElementById('repia-row-' + id);
            if (filaPanel) {
                filaPanel.hidden = !filaPanel.hidden;
                return;
            }
            filaPanel = document.createElement('tr');
            filaPanel.id = 'repia-row-' + id;
            var td = document.createElement('td');
            td.colSpan = 5;
            td.style.padding = '0';
            var cont = document.createElement('div');
            cont.className = 'repia-panel';
            td.appendChild(cont);
            filaPanel.appendChild(td);
            tr.parentNode.insertBefore(filaPanel, tr.nextSibling);

            var cargar = function (regen) {
                cont.innerHTML = '<div class="repia-meta">✨ Redactando borrador…</div>';
                post(URL_RESENA, { encuesta_id: id, regenerar: regen ? '1' : '0' }, function (data) {
                    pintar(cont, data, cargar, true);
                });
            };
            cargar(false);
        });
    });

    // Analisis mensual.
    var btnAnalizar = document.getElementById('repia-analizar');
    if (btnAnalizar) {
        btnAnalizar.addEventListener('click', function () {
            var cont = document.getElementById('repia-analisis');
            var mes = document.getElementById('repia-mes').value;
            cont.hidden = false;
            var cargar = function (regen) {
                cont.innerHTML = '<div class="repia-meta">✨ Leyendo las encuestas de ese mes…</div>';
                post(URL_ANALISIS, { mes: mes, regenerar: regen ? '1' : '0' }, function (data) {
                    pintar(cont, data, cargar, false);
                });
            };
            cargar(false);
        });
    }
})();
</script>
<?php endif; ?>
