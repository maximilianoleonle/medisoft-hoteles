<?php
/**
 * Tablero de canales iCal (bloque canales_ical): export por habitacion +
 * calendarios importados con estado de sincronizacion.
 */
$habitaciones = $habitaciones ?? [];
$feeds = $feeds ?? [];
$token = $token ?? '';
$slug = $slug ?? '';

$cnSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$cnEtiqueta = static function ($hab) {
    $numero = trim((string) ($hab['habitacion_numero'] ?? $hab['numero'] ?? ''));
    $tipo = trim((string) ($hab['habitacion_tipo'] ?? $hab['tipo'] ?? ''));
    return $numero !== '' ? ('Hab ' . $numero . ($tipo !== '' ? ' - ' . ucfirst($tipo) : '')) : ucfirst($tipo);
};

$habitacionesTotal = count($habitaciones);
$feedsTotal = count($feeds);
$bloqueosActivos = 0;
foreach ($feeds as $feed) {
    $bloqueosActivos += (int) ($feed['eventos_activos'] ?? 0);
}
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cnl {
    --cnl-brand: var(--brand-primary, #1B2746);
    --cnl-brand-2: var(--brand-secondary, #0F172A);
    --cnl-gold: var(--brand-accent, #BD9441);
    --cnl-gold-soft: color-mix(in srgb, var(--cnl-gold) 15%, #FFFFFF);
    --cnl-gold-line: color-mix(in srgb, var(--cnl-gold) 42%, #E4D4B0);
    --cnl-gold-ink: color-mix(in srgb, var(--cnl-gold) 58%, var(--cnl-brand));
    --cnl-ivory: #F6F2EA;
    --cnl-ivory-2: #FBF8F2;
    --cnl-surface: #FFFFFF;
    --cnl-surface-warm: #FCFAF5;
    --cnl-border: color-mix(in srgb, var(--cnl-brand) 6%, #E9E1D6);
    --cnl-ring: color-mix(in srgb, var(--cnl-gold) 32%, transparent);
    --cnl-text: color-mix(in srgb, var(--cnl-brand) 46%, #707B8C);
    --cnl-muted: #8791A2;
    --cnl-heading: color-mix(in srgb, var(--cnl-brand) 66%, #566172);
    --cnl-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cnl-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cnl-success: #1E9E63;
    --cnl-success-bg: #E7F4EC;
    --cnl-warning: #C2841C;
    --cnl-warning-bg: #FAF0DC;
    --cnl-danger: #B4392B;
    --cnl-danger-bg: #F8EAE5;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 42px;
    color: var(--cnl-text);
    font-family: var(--cnl-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cnl-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cnl-ivory-2), var(--cnl-ivory));
}
.cnl * { box-sizing: border-box; }
.cnl-shell {
    display: grid;
    gap: 14px;
    min-width: 0;
    width: 100%;
    max-width: 1100px;
    margin: 0 auto;
}
.cnl-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    min-width: 0;
    padding: 2px 0 6px;
}
.cnl-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 760px);
}
.cnl-title-lockup > div:last-child { min-width: 0; }
.cnl-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--cnl-gold), var(--cnl-brand) 54%, color-mix(in srgb, var(--cnl-brand) 68%, var(--cnl-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cnl-brand) 72%, transparent);
}
.cnl-kicker {
    margin: 0 0 2px;
    color: var(--cnl-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.cnl-title {
    margin: 0;
    color: var(--cnl-heading);
    font-family: var(--cnl-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.cnl-subtitle {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--cnl-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.cnl-hero-actions {
    display: flex;
    justify-content: flex-end;
    justify-self: end;
    min-width: max-content;
}
.cnl-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.cnl-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--cnl-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.cnl-summary-label {
    color: var(--cnl-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.cnl-summary-value {
    margin-top: 2px;
    color: var(--cnl-heading);
    font-family: var(--cnl-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}
.cnl-summary-value.is-active { color: color-mix(in srgb, var(--cnl-success) 68%, var(--cnl-text)); }
.cnl-alert {
    padding: 12px 14px;
    border-radius: 13px;
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.45;
}
.cnl-alert.is-success { background: var(--cnl-success-bg); color: color-mix(in srgb, var(--cnl-success) 70%, var(--cnl-text)); border: 1px solid color-mix(in srgb, var(--cnl-success) 24%, #fff); }
.cnl-alert.is-warning { background: var(--cnl-warning-bg); color: color-mix(in srgb, var(--cnl-warning) 72%, var(--cnl-text)); border: 1px solid color-mix(in srgb, var(--cnl-warning) 26%, #fff); }
.cnl-alert.is-error { background: var(--cnl-danger-bg); color: color-mix(in srgb, var(--cnl-danger) 72%, var(--cnl-text)); border: 1px solid color-mix(in srgb, var(--cnl-danger) 24%, #fff); }
.cnl-alert.is-info { background: var(--cnl-gold-soft); color: var(--cnl-gold-ink); border: 1px solid var(--cnl-gold-line); }
.cnl-panel {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--cnl-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
    overflow: hidden;
}
.cnl-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--cnl-border);
}
.cnl-panel-title {
    margin: 0;
    color: var(--cnl-heading);
    font-size: .9rem;
    font-weight: 650;
}
.cnl-panel-sub {
    max-width: 52rem;
    margin: 3px 0 0;
    color: var(--cnl-muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.45;
}
.cnl-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--cnl-gold) 10%, #FFFFFF);
    color: var(--cnl-gold-ink);
    border: 1px solid color-mix(in srgb, var(--cnl-gold) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}
.cnl-table-wrap { overflow-x: auto; }
.cnl-table {
    width: 100%;
    min-width: 760px;
    border-collapse: collapse;
    font-size: .85rem;
}
.cnl-table thead {
    background: var(--cnl-surface-warm);
    border-bottom: 1px solid var(--cnl-border);
}
.cnl-table th {
    padding: 12px 16px;
    color: var(--cnl-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}
.cnl-table th.is-end,
.cnl-table td.is-end { text-align: right; }
.cnl-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--cnl-border);
    vertical-align: middle;
}
.cnl-table tbody tr:last-child td { border-bottom: 0; }
.cnl-table tbody tr { transition: background .16s ease, box-shadow .16s ease; }
.cnl-table tbody tr:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}
.cnl-room,
.cnl-platform-name,
.cnl-cell-strong {
    color: var(--cnl-heading);
    font-weight: 650;
}
.cnl-room { white-space: nowrap; }
.cnl-link-code,
.cnl-url-preview {
    color: var(--cnl-muted);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: .76rem;
    line-height: 1.45;
    word-break: break-all;
}
.cnl-url-preview {
    margin-top: 3px;
    font-size: .72rem;
}
.cnl-empty {
    padding: 34px 18px !important;
    text-align: center;
    color: var(--cnl-muted);
    background: var(--cnl-ivory-2);
}
.cnl-form {
    display: grid;
    grid-template-columns: minmax(140px, 1fr) minmax(160px, 1fr) minmax(280px, 2fr) auto;
    gap: 10px;
    padding: 14px 16px 16px;
    align-items: end;
}
.cnl-form label {
    display: block;
    margin-bottom: 5px;
    color: var(--cnl-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .035em;
    text-transform: uppercase;
}
.cnl-field {
    width: 100%;
    min-height: 42px;
    padding: 0 12px;
    border: 1px solid var(--cnl-border);
    border-radius: 11px;
    background: var(--cnl-surface-warm);
    color: var(--cnl-text);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 560;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.cnl-field::placeholder {
    color: color-mix(in srgb, var(--cnl-muted) 82%, #B8C0CB);
    font-weight: 520;
}
.cnl-field:focus {
    border-color: var(--cnl-gold);
    background: #fff;
    box-shadow: 0 0 0 3px var(--cnl-ring);
    outline: none;
}
.cnl-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid transparent;
    border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--cnl-gold) 86%, #fff), color-mix(in srgb, var(--cnl-gold) 72%, var(--cnl-brand)));
    color: #fff;
    cursor: pointer;
    font-family: inherit;
    font-size: .88rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--cnl-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.cnl-btn:hover { transform: translateY(-1px); }
.cnl-btn:active { transform: translateY(0) scale(.98); }
.cnl-btn:focus-visible { outline: 3px solid var(--cnl-ring); outline-offset: 2px; }
.cnl-btn.sec {
    background: var(--cnl-surface);
    color: var(--cnl-gold-ink);
    border-color: var(--cnl-gold-line);
    box-shadow: none;
}
.cnl-btn.rojo {
    background: var(--cnl-surface);
    color: var(--cnl-danger);
    border-color: color-mix(in srgb, var(--cnl-danger) 24%, #E9E1D6);
    box-shadow: none;
}
.cnl-badge {
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
.cnl-badge.is-muted {
    color: var(--cnl-muted);
    background: var(--cnl-surface-warm);
    border-color: var(--cnl-border);
}
.cnl-badge.is-ok {
    color: color-mix(in srgb, var(--cnl-success) 70%, var(--cnl-text));
    background: var(--cnl-success-bg);
    border-color: color-mix(in srgb, var(--cnl-success) 24%, #fff);
}
.cnl-badge.is-error {
    color: color-mix(in srgb, var(--cnl-danger) 72%, var(--cnl-text));
    background: var(--cnl-danger-bg);
    border-color: color-mix(in srgb, var(--cnl-danger) 24%, #fff);
}
.cnl-inline-form { display: inline-flex; margin: 0; }
.cnl-sync-form { margin: 0; }
.cnl-help {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 16px 18px;
    background: var(--cnl-gold-soft);
    border: 1px solid var(--cnl-gold-line);
    border-radius: 16px;
    color: var(--cnl-muted);
    font-size: .88rem;
    line-height: 1.55;
}
.cnl-help i {
    color: var(--cnl-gold-ink);
    font-size: 1.1rem;
    margin-top: 2px;
}
.cnl-help strong {
    display: block;
    margin-bottom: 2px;
    color: var(--cnl-heading);
    font-weight: 650;
}

@media (max-width: 900px) {
    .cnl-hero-section {
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 12px;
    }
    .cnl-hero-actions {
        justify-content: flex-start;
        justify-self: start;
        min-width: 0;
    }
    .cnl-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .cnl-form { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .cnl {
        padding: 14px 12px 34px;
    }
    .cnl-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }
    .cnl-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }
    .cnl-title { font-size: 2rem; }
    .cnl-summary { grid-template-columns: 1fr; }
    .cnl-panel-head {
        display: grid;
        grid-template-columns: 1fr;
    }
    .cnl-count-pill { justify-self: start; }
    .cnl-table { min-width: 700px; }
    .cnl-btn { width: 100%; }
    .cnl-inline-form,
    .cnl-inline-form .cnl-btn,
    .cnl-hero-actions,
    .cnl-hero-actions .cnl-sync-form { width: 100%; }
}
</style>

<div class="cnl">
    <div class="cnl-shell">
        <section class="cnl-hero-section">
            <div class="cnl-title-lockup">
                <div class="cnl-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <p class="cnl-kicker">Canales conectados</p>
                    <h1 class="cnl-title">Canales iCal</h1>
                    <p class="cnl-subtitle">Sincroniza tu calendario con Airbnb y Booking para no vender dos veces la misma habitaci&oacute;n.</p>
                </div>
            </div>
            <div class="cnl-hero-actions">
                <form method="POST" action="<?= url('canales/sincronizar') ?>" class="cnl-sync-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="cnl-btn">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                        Sincronizar ahora
                    </button>
                </form>
            </div>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'warning', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="cnl-alert is-<?= $cnSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <section class="cnl-summary" aria-label="Resumen de canales">
            <div class="cnl-summary-item">
                <div class="cnl-summary-label">Habitaciones activas</div>
                <div class="cnl-summary-value"><?= (int) $habitacionesTotal ?></div>
            </div>
            <div class="cnl-summary-item">
                <div class="cnl-summary-label">Calendarios importados</div>
                <div class="cnl-summary-value"><?= (int) $feedsTotal ?></div>
            </div>
            <div class="cnl-summary-item">
                <div class="cnl-summary-label">Bloqueos activos</div>
                <div class="cnl-summary-value is-active"><?= (int) $bloqueosActivos ?></div>
            </div>
        </section>

        <section class="cnl-panel">
            <div class="cnl-panel-head">
                <div>
                    <h2 class="cnl-panel-title">Exporta tus reservas hacia las plataformas</h2>
                    <p class="cnl-panel-sub">Copia el link de cada habitaci&oacute;n y p&eacute;galo en Airbnb o Booking. Las plataformas leer&aacute;n tus reservas autom&aacute;ticamente.</p>
                </div>
                <span class="cnl-count-pill"><?= (int) $habitacionesTotal ?> habitaciones</span>
            </div>
            <div class="cnl-table-wrap">
                <table class="cnl-table">
                    <thead>
                        <tr>
                            <th>Habitaci&oacute;n</th>
                            <th>Link iCal para pegar en la plataforma</th>
                            <th class="is-end">Acci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($habitaciones)): ?>
                        <tr><td colspan="3" class="cnl-empty">No hay habitaciones activas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($habitaciones as $hab): ?>
                            <?php $urlIcs = url('h/' . $slug . '/ical/' . $token . '/' . (int) $hab['id'] . '.ics'); ?>
                            <tr>
                                <td class="cnl-room"><?= $cnSafe($cnEtiqueta($hab)) ?></td>
                                <td><div class="cnl-link-code"><?= $cnSafe($urlIcs) ?></div></td>
                                <td class="is-end">
                                    <button type="button" class="cnl-btn sec"
                                            data-link="<?= $cnSafe($urlIcs) ?>"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.link).then(() => { this.textContent='Copiado'; })">
                                        Copiar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="cnl-panel">
            <div class="cnl-panel-head">
                <div>
                    <h2 class="cnl-panel-title">Importa los calendarios de las plataformas</h2>
                    <p class="cnl-panel-sub">Pega aqu&iacute; el link iCal que te da cada plataforma por habitaci&oacute;n. Sus reservas bloquear&aacute;n la habitaci&oacute;n aqu&iacute; y en tu p&aacute;gina de reservas.</p>
                </div>
                <span class="cnl-count-pill"><?= (int) $feedsTotal ?> calendarios</span>
            </div>

            <form method="POST" action="<?= url('canales/feed/guardar') ?>" class="cnl-form">
                <?= csrf_field() ?>
                <div>
                    <label>Habitaci&oacute;n</label>
                    <select name="habitacion_id" class="cnl-field" required>
                        <option value="">Elige...</option>
                        <?php foreach ($habitaciones as $hab): ?>
                            <option value="<?= (int) $hab['id'] ?>"><?= $cnSafe($cnEtiqueta($hab)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="cnl-field" maxlength="100" placeholder="Ej. Airbnb Hab 5" required>
                </div>
                <div>
                    <label>Link iCal de la plataforma (.ics)</label>
                    <input type="url" name="url" class="cnl-field" maxlength="500" placeholder="https://www.airbnb.mx/calendar/ical/..." required>
                </div>
                <button type="submit" class="cnl-btn">Agregar</button>
            </form>

            <div class="cnl-table-wrap">
                <table class="cnl-table">
                    <thead>
                        <tr>
                            <th>Habitaci&oacute;n</th>
                            <th>Calendario</th>
                            <th>Ultima sincronizaci&oacute;n</th>
                            <th>Bloqueos</th>
                            <th class="is-end">Acci&oacute;n</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($feeds)): ?>
                        <tr><td colspan="5" class="cnl-empty">A&uacute;n no importas ning&uacute;n calendario.</td></tr>
                    <?php else: ?>
                        <?php foreach ($feeds as $feed): ?>
                            <tr>
                                <td class="cnl-room"><?= $cnSafe($cnEtiqueta($feed)) ?></td>
                                <td>
                                    <div class="cnl-platform-name"><?= $cnSafe($feed['nombre']) ?></div>
                                    <div class="cnl-url-preview"><?= $cnSafe(mb_strimwidth((string) $feed['url'], 0, 70, '...')) ?></div>
                                </td>
                                <td>
                                    <?php if (!$feed['last_sync_at']): ?>
                                        <span class="cnl-badge is-muted">Sin sincronizar</span>
                                    <?php elseif ($feed['last_sync_estado'] === 'ok'): ?>
                                        <span class="cnl-badge is-ok"><?= $cnSafe(date('d/m H:i', strtotime((string) $feed['last_sync_at']))) ?></span>
                                    <?php else: ?>
                                        <span class="cnl-badge is-error" title="<?= $cnSafe($feed['last_sync_error']) ?>">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td class="cnl-cell-strong"><?= (int) $feed['eventos_activos'] ?></td>
                                <td class="is-end">
                                    <form method="POST" action="<?= url('canales/feed/eliminar/' . (int) $feed['id']) ?>" class="cnl-inline-form cnl-form-eliminar">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="cnl-btn rojo">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <div class="cnl-help">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
                <strong>&iquest;C&oacute;mo funciona?</strong>
                La sincronizaci&oacute;n corre autom&aacute;ticamente varias veces al d&iacute;a y tambi&eacute;n con el bot&oacute;n superior.
                Las fechas ocupadas en Airbnb/Booking bloquean la habitaci&oacute;n en recepci&oacute;n y en la p&aacute;gina de reservas.
                Si cancelan all&aacute;, el bloqueo se libera en la siguiente sincronizaci&oacute;n.
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('.cnl-form-eliminar').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.msOk === '1') { delete form.dataset.msOk; return; }
            e.preventDefault();
            var continuar = function (ok) {
                if (!ok) return;
                form.dataset.msOk = '1';
                if (form.requestSubmit) form.requestSubmit();
                else form.submit();
            };
            if (window.msConfirm) {
                msConfirm({
                    type: 'warning',
                    icon: 'alert',
                    title: '\u00bfEliminar este calendario?',
                    msg: 'Se dejar\u00e1n de importar sus reservas y sus bloqueos se liberar\u00e1n.',
                    confirmLabel: 'Eliminar'
                }).then(continuar);
            } else {
                continuar(confirm('\u00bfEliminar este calendario?'));
            }
        });
    });
})();
</script>
