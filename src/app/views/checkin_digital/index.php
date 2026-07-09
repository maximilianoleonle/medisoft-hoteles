<?php
/**
 * Tablero interno de check-in digital (bloque checkin_digital).
 */
$filas = $filas ?? [];
$slugHotel = $slugHotel ?? '';

$cdiSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$cdiBadge = static function ($estado) {
    $map = [
        'completado' => ['Completado', 'is-completed', 'fa-circle-check'],
        'pendiente' => ['Enviado, sin llenar', 'is-pending', 'fa-hourglass-half'],
        'expirado' => ['Expirado', 'is-expired', 'fa-clock-rotate-left'],
    ];
    return $map[$estado] ?? ['Sin link', 'is-empty', 'fa-link-slash'];
};

$cdiFecha = static function ($fecha) {
    $timestamp = strtotime((string) $fecha);
    return $timestamp ? date('d/m/Y', $timestamp) : '-';
};

$cdiTotal = count($filas);
$cdiCompletados = 0;
$cdiPendientes = 0;
$cdiSinLink = 0;
$cdiExpirados = 0;
foreach ($filas as $fila) {
    $estado = (string)($fila['link_estado'] ?? '');
    if ($estado === 'completado') {
        $cdiCompletados++;
    } elseif ($estado === 'pendiente') {
        $cdiPendientes++;
    } elseif ($estado === 'expirado') {
        $cdiExpirados++;
    } else {
        $cdiSinLink++;
    }
}
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.checkin-digital-page {
    --cdi-brand: var(--brand-primary, #1B2746);
    --cdi-brand-2: var(--brand-secondary, #0F172A);
    --cdi-gold: var(--brand-accent, #BD9441);
    --cdi-gold-soft: color-mix(in srgb, var(--cdi-gold) 15%, #FFFFFF);
    --cdi-gold-line: color-mix(in srgb, var(--cdi-gold) 42%, #E4D4B0);
    --cdi-gold-ink: color-mix(in srgb, var(--cdi-gold) 58%, var(--cdi-brand));
    --cdi-ivory: #F6F2EA;
    --cdi-ivory-2: #FBF8F2;
    --cdi-surface: #FFFFFF;
    --cdi-surface-warm: #FCFAF5;
    --cdi-border: color-mix(in srgb, var(--cdi-brand) 6%, #E9E1D6);
    --cdi-ring: color-mix(in srgb, var(--cdi-gold) 32%, transparent);
    --cdi-text: color-mix(in srgb, var(--cdi-brand) 46%, #707B8C);
    --cdi-muted: #8791A2;
    --cdi-heading: color-mix(in srgb, var(--cdi-brand) 66%, #566172);
    --cdi-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cdi-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cdi-success: #1E9E63;
    --cdi-success-bg: #E7F4EC;
    --cdi-warning: #C2841C;
    --cdi-warning-bg: #FAF0DC;
    --cdi-info: #2F77E0;
    --cdi-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--cdi-text);
    font-family: var(--cdi-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cdi-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cdi-ivory-2), var(--cdi-ivory));
}

.checkin-digital-page .cdi-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 1120px;
    margin: 0 auto;
    padding: 18px 16px 42px;
    box-sizing: border-box;
}

.checkin-digital-page .cdi-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: start;
    gap: 12px;
    padding: 2px 0 6px;
}

.checkin-digital-page .cdi-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    max-width: min(100%, 790px);
    min-width: 0;
}

.checkin-digital-page .cdi-title-lockup > div:last-child { min-width: 0; }

.checkin-digital-page .cdi-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--cdi-gold), var(--cdi-brand) 54%, color-mix(in srgb, var(--cdi-brand) 68%, var(--cdi-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cdi-brand) 72%, transparent);
}

.checkin-digital-page .cdi-kicker {
    margin: 0 0 2px;
    color: var(--cdi-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.checkin-digital-page .cdi-title {
    margin: 0;
    color: var(--cdi-heading);
    font-family: var(--cdi-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}

.checkin-digital-page .cdi-subtitle {
    max-width: 49rem;
    margin: 9px 0 0;
    color: var(--cdi-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}

.checkin-digital-page .cdi-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
}

.checkin-digital-page .cdi-top-pill,
.checkin-digital-page .cdi-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--cdi-gold) 10%, #FFFFFF);
    color: var(--cdi-gold-ink);
    border: 1px solid color-mix(in srgb, var(--cdi-gold) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}

.checkin-digital-page .cdi-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.checkin-digital-page .cdi-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--cdi-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}

.checkin-digital-page .cdi-summary-label {
    color: var(--cdi-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.checkin-digital-page .cdi-summary-value {
    margin-top: 2px;
    color: var(--cdi-heading);
    font-family: var(--cdi-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}

.checkin-digital-page .cdi-summary-value.is-success { color: color-mix(in srgb, var(--cdi-success) 68%, var(--cdi-text)); }
.checkin-digital-page .cdi-summary-value.is-warning { color: color-mix(in srgb, var(--cdi-warning) 74%, var(--cdi-text)); }

.checkin-digital-page .cdi-alert {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px 16px;
    border-radius: 16px;
    border: 1px solid var(--cdi-border);
    background: rgba(255,255,255,.86);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 28px -25px rgba(27,39,70,.22);
    color: var(--cdi-text);
    font-size: .9rem;
    font-weight: 560;
}

.checkin-digital-page .cdi-alert i {
    width: 28px;
    height: 28px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    flex: 0 0 28px;
}

.checkin-digital-page .cdi-alert.is-success i {
    background: var(--cdi-success-bg);
    color: var(--cdi-success);
}

.checkin-digital-page .cdi-alert.is-error i {
    background: #F8EAE5;
    color: #B4392B;
}

.checkin-digital-page .cdi-panel {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--cdi-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}

.checkin-digital-page .cdi-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--cdi-border);
}

.checkin-digital-page .cdi-panel-title {
    margin: 0;
    color: var(--cdi-heading);
    font-size: .85rem;
    font-weight: 650;
}

.checkin-digital-page .cdi-panel-sub {
    margin: 2px 0 0;
    color: var(--cdi-muted);
    font-size: .75rem;
}

.checkin-digital-page .cdi-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 16px 16px;
}

.checkin-digital-page .cdi-table {
    width: 100%;
    min-width: 920px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: .85rem;
}

.checkin-digital-page .cdi-table thead {
    background: var(--cdi-surface-warm);
    border-bottom: 1px solid var(--cdi-border);
}

.checkin-digital-page .cdi-table th {
    padding: 12px 16px;
    color: var(--cdi-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.checkin-digital-page .cdi-table th.is-actions { text-align: right; }

.checkin-digital-page .cdi-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--cdi-border);
    vertical-align: middle;
}

.checkin-digital-page .cdi-table td.is-actions { text-align: right; }
.checkin-digital-page .cdi-table tr:last-child td { border-bottom: 0; }
.checkin-digital-page .cdi-table tbody tr { transition: background .16s ease, box-shadow .16s ease; }
.checkin-digital-page .cdi-table tbody tr:hover { background: rgba(251,248,242,.72); box-shadow: 0 10px 24px -25px rgba(27,39,70,.32); }

.checkin-digital-page .cdi-date,
.checkin-digital-page .cdi-guest strong,
.checkin-digital-page .cdi-reservation-link {
    color: var(--cdi-heading);
    font-weight: 650;
}

.checkin-digital-page .cdi-date { white-space: nowrap; }

.checkin-digital-page .cdi-meta {
    margin-top: 2px;
    color: var(--cdi-muted);
    font-size: .74rem;
}

.checkin-digital-page .cdi-reservation-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: var(--cdi-heading);
    text-decoration: none;
}

.checkin-digital-page .cdi-reservation-link:hover {
    color: var(--cdi-gold-ink);
    text-decoration: underline;
    text-decoration-color: var(--cdi-gold-line);
    text-underline-offset: 3px;
}

.checkin-digital-page .cdi-badge {
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

.checkin-digital-page .cdi-badge.is-completed {
    background: var(--cdi-success-bg);
    border-color: color-mix(in srgb, var(--cdi-success) 24%, #fff);
    color: color-mix(in srgb, var(--cdi-success) 70%, var(--cdi-text));
}

.checkin-digital-page .cdi-badge.is-pending {
    background: var(--cdi-warning-bg);
    border-color: color-mix(in srgb, var(--cdi-warning) 26%, #fff);
    color: color-mix(in srgb, var(--cdi-warning) 72%, var(--cdi-text));
}

.checkin-digital-page .cdi-badge.is-expired,
.checkin-digital-page .cdi-badge.is-empty {
    background: var(--cdi-surface-warm);
    border-color: var(--cdi-border);
    color: var(--cdi-muted);
}

.checkin-digital-page .cdi-actions {
    display: flex;
    justify-content: flex-end;
    gap: 7px;
    flex-wrap: wrap;
}

.checkin-digital-page .cdi-actions form {
    margin: 0;
}

.checkin-digital-page .cdi-btn {
    position: relative;
    overflow: hidden;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 44px;
    padding: 0 14px;
    border: 1px solid transparent;
    border-radius: 11px;
    cursor: pointer;
    font-size: .82rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.checkin-digital-page .cdi-btn:hover { transform: translateY(-1px); }
.checkin-digital-page .cdi-btn:active { transform: translateY(0) scale(.98); }
.checkin-digital-page .cdi-btn:focus-visible { outline: 3px solid var(--cdi-ring); outline-offset: 2px; }

.checkin-digital-page .cdi-btn-gold {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cdi-gold) 86%, #fff), color-mix(in srgb, var(--cdi-gold) 72%, var(--cdi-brand)));
    color: #fff;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--cdi-gold) 42%, transparent);
}

.checkin-digital-page .cdi-btn-muted {
    background: var(--cdi-surface);
    border-color: var(--cdi-border);
    color: var(--cdi-muted);
}

.checkin-digital-page .cdi-btn-whatsapp {
    background: color-mix(in srgb, var(--cdi-success) 10%, #FFFFFF);
    border-color: color-mix(in srgb, var(--cdi-success) 24%, #FFFFFF);
    color: color-mix(in srgb, var(--cdi-success) 70%, var(--cdi-text));
}

.checkin-digital-page .cdi-btn__shine {
    position: absolute;
    inset: 0 auto 0 0;
    width: 42%;
    pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.48), transparent);
    transform: translateX(-160%) skewX(-18deg);
}

.checkin-digital-page .cdi-btn-gold:hover .cdi-btn__shine {
    transition: transform .7s ease;
    transform: translateX(320%) skewX(-18deg);
}

.checkin-digital-page .cdi-empty {
    padding: 44px 18px;
    text-align: center;
    background: var(--cdi-ivory-2);
    border: 1px dashed var(--cdi-border);
    border-radius: 16px;
    margin: 14px;
}

.checkin-digital-page .cdi-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: var(--cdi-gold-soft);
    color: var(--cdi-gold-ink);
    font-size: 1.3rem;
}

.checkin-digital-page .cdi-empty h2 {
    margin: 0;
    color: var(--cdi-heading);
    font-size: 1.1rem;
    font-weight: 650;
}

.checkin-digital-page .cdi-empty p {
    max-width: 31rem;
    margin: 8px auto 0;
    color: var(--cdi-muted);
    font-size: .9rem;
    line-height: 1.5;
}

.checkin-digital-page .cdi-footnote {
    margin: 0;
    color: var(--cdi-muted);
    font-size: .76rem;
    line-height: 1.5;
}

@media (min-width: 1024px) {
    .checkin-digital-page .cdi-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px 28px;
        padding-bottom: 10px;
    }

    .checkin-digital-page .cdi-hero-actions {
        justify-content: flex-end;
        justify-self: end;
    }
}

@media (max-width: 940px) {
    .checkin-digital-page .cdi-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .checkin-digital-page .cdi-actions { justify-content: flex-start; }
    .checkin-digital-page .cdi-table td.is-actions { text-align: left; }
}

@media (max-width: 640px) {
    .checkin-digital-page .cdi-shell { padding: 16px 12px 34px; }
    .checkin-digital-page .cdi-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .checkin-digital-page .cdi-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1rem; }
    .checkin-digital-page .cdi-title { font-size: clamp(1.85rem, 12vw, 2.35rem); }
    .checkin-digital-page .cdi-summary { grid-template-columns: 1fr; }
    .checkin-digital-page .cdi-panel-head { align-items: flex-start; flex-direction: column; }
    .checkin-digital-page .cdi-actions { flex-direction: column; align-items: stretch; }
    .checkin-digital-page .cdi-actions form,
    .checkin-digital-page .cdi-btn { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .checkin-digital-page .cdi-btn__shine { display: none; }
    .checkin-digital-page *,
    .checkin-digital-page *::before,
    .checkin-digital-page *::after {
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>

<div class="checkin-digital-page">
    <div class="cdi-shell">
        <section class="cdi-hero-section" aria-labelledby="cdi-title">
            <div class="cdi-title-lockup">
                <div class="cdi-hero-icon" aria-hidden="true"><i class="fas fa-id-badge"></i></div>
                <div>
                    <p class="cdi-kicker">Recepcion / Pre-registro</p>
                    <h1 class="cdi-title" id="cdi-title">Check-in digital</h1>
                    <p class="cdi-subtitle">Manda el link a tus llegadas pr&oacute;ximas para que el hu&eacute;sped complete sus datos y suba su identificaci&oacute;n antes de llegar.</p>
                </div>
            </div>
            <div class="cdi-hero-actions">
                <span class="cdi-top-pill"><i class="fas fa-link" aria-hidden="true"></i> Links seguros por reservaci&oacute;n</span>
            </div>
        </section>

        <section class="cdi-summary" aria-label="Resumen de check-in digital">
            <div class="cdi-summary-item">
                <div class="cdi-summary-label">Llegadas visibles</div>
                <div class="cdi-summary-value"><?= number_format($cdiTotal) ?></div>
            </div>
            <div class="cdi-summary-item">
                <div class="cdi-summary-label">Completados</div>
                <div class="cdi-summary-value is-success"><?= number_format($cdiCompletados) ?></div>
            </div>
            <div class="cdi-summary-item">
                <div class="cdi-summary-label">Pendientes</div>
                <div class="cdi-summary-value is-warning"><?= number_format($cdiPendientes) ?></div>
            </div>
            <div class="cdi-summary-item">
                <div class="cdi-summary-label">Sin link</div>
                <div class="cdi-summary-value"><?= number_format($cdiSinLink + $cdiExpirados) ?></div>
            </div>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php $tipo = $mensaje['tipo'] ?? 'info'; ?>
            <div class="cdi-alert <?= $tipo === 'error' ? 'is-error' : 'is-success' ?>" role="status">
                <i class="fas <?= $tipo === 'error' ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>" aria-hidden="true"></i>
                <div><?= $cdiSafe($mensaje['texto'] ?? '') ?></div>
            </div>
        <?php endif; ?>

        <section class="cdi-panel" aria-labelledby="cdi-list-title">
            <div class="cdi-panel-head">
                <div>
                    <h2 class="cdi-panel-title" id="cdi-list-title">Llegadas pr&oacute;ximas</h2>
                    <p class="cdi-panel-sub">Genera, comparte y revisa el pre-registro sin cambiar el estado operativo de la reservaci&oacute;n.</p>
                </div>
                <span class="cdi-count-pill"><i class="fas fa-calendar-check" aria-hidden="true"></i> <?= number_format($cdiTotal) ?> visible(s)</span>
            </div>

            <?php if (empty($filas)): ?>
                <div class="cdi-empty">
                    <div class="cdi-empty-icon" aria-hidden="true"><i class="fas fa-calendar-day"></i></div>
                    <h2>No hay llegadas pr&oacute;ximas confirmadas</h2>
                    <p>Cuando existan reservaciones confirmadas para los siguientes d&iacute;as, aparecer&aacute;n aqu&iacute; para generar su link de pre-registro.</p>
                </div>
            <?php else: ?>
                <div class="cdi-table-wrap">
                    <table class="cdi-table">
                        <colgroup>
                            <col style="width: 13%;">
                            <col style="width: 24%;">
                            <col style="width: 15%;">
                            <col style="width: 18%;">
                            <col style="width: 30%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Llegada</th>
                                <th>Hu&eacute;sped</th>
                                <th>Reservaci&oacute;n</th>
                                <th>Pre-registro</th>
                                <th class="is-actions">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filas as $fila): ?>
                                <?php
                                [$badgeTexto, $badgeClase, $badgeIcono] = $cdiBadge($fila['link_estado'] ?? null);
                                $urlPublica = !empty($fila['token'])
                                    ? url('h/' . $slugHotel . '/checkin/' . $fila['token'])
                                    : null;
                                ?>
                                <tr>
                                    <td class="cdi-date"><?= $cdiSafe($cdiFecha($fila['fecha_entrada'] ?? '')) ?></td>
                                    <td class="cdi-guest">
                                        <strong><?= $cdiSafe($fila['nombre_completo'] ?? 'Huesped') ?></strong>
                                        <div class="cdi-meta"><?= $cdiSafe(($fila['telefono'] ?? '') ?: 'Sin telefono') ?></div>
                                    </td>
                                    <td>
                                        <a class="cdi-reservation-link" href="<?= url('reservaciones/ver/' . (int) ($fila['reservacion_id'] ?? 0)) ?>">
                                            <i class="fas fa-bookmark" aria-hidden="true"></i>
                                            #<?= (int) ($fila['reservacion_id'] ?? 0) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="cdi-badge <?= $badgeClase ?>">
                                            <i class="fas <?= $badgeIcono ?>" aria-hidden="true"></i>
                                            <?= $badgeTexto ?>
                                        </span>
                                    </td>
                                    <td class="is-actions">
                                        <div class="cdi-actions">
                                            <?php if (empty($fila['token']) || ($fila['link_estado'] ?? null) === 'expirado'): ?>
                                                <form method="POST" action="<?= url('checkin-digital/generar/' . (int) ($fila['reservacion_id'] ?? 0)) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="cdi-btn cdi-btn-gold">
                                                        <span class="cdi-btn__shine" aria-hidden="true"></span>
                                                        <i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i>
                                                        Generar link
                                                    </button>
                                                </form>
                                            <?php elseif (($fila['link_estado'] ?? null) === 'pendiente'): ?>
                                                <button type="button" class="cdi-btn cdi-btn-muted"
                                                        data-link="<?= $cdiSafe($urlPublica) ?>"
                                                        onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado'; })">
                                                    <i class="fas fa-copy" aria-hidden="true"></i>
                                                    Copiar link
                                                </button>
                                                <a class="cdi-btn cdi-btn-whatsapp" target="_blank" rel="noopener"
                                                   href="https://wa.me/52<?= $cdiSafe(preg_replace('/\D/', '', substr((string) ($fila['telefono'] ?? ''), -10))) ?>?text=<?= rawurlencode('Hola ' . ($fila['nombre_completo'] ?? '') . ', completa tu pre-registro para tu llegada aqui: ' . $urlPublica) ?>">
                                                    <i class="fab fa-whatsapp" aria-hidden="true"></i>
                                                    Enviar por WhatsApp
                                                </a>
                                            <?php elseif (($fila['link_estado'] ?? null) === 'completado'): ?>
                                                <?php if (!empty($fila['id_documento_path'])): ?>
                                                    <a class="cdi-btn cdi-btn-muted" target="_blank" href="<?= url('checkin-digital/id/' . (int) ($fila['reservacion_id'] ?? 0)) ?>">
                                                        <i class="fas fa-id-card" aria-hidden="true"></i>
                                                        Ver ID
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <p class="cdi-footnote">El link permite pre-registro del hu&eacute;sped; la operaci&oacute;n formal de check-in se conserva en recepci&oacute;n.</p>
    </div>
</div>
