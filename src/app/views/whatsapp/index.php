<?php
/**
 * WhatsApp del hotel (bloque whatsapp): conexion Green API + toggles.
 */
$cred = $cred ?? null;
$config = $config ?? ['confirmacion_huesped' => true, 'aviso_dueno' => true];

$waSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$conectado = $cred && !empty($cred['token_configurado']) && !empty($cred['activo']);
$tokenConfigurado = !empty($cred['token_configurado']);
$automatizacionesActivas = (!empty($config['confirmacion_huesped']) ? 1 : 0) + (!empty($config['aviso_dueno']) ? 1 : 0);
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.wav {
    --wav-brand: var(--brand-primary, #1B2746);
    --wav-brand-2: var(--brand-secondary, #0F172A);
    --wav-gold: var(--brand-accent, #BD9441);
    --wav-gold-soft: color-mix(in srgb, var(--wav-gold) 15%, #FFFFFF);
    --wav-gold-line: color-mix(in srgb, var(--wav-gold) 42%, #E4D4B0);
    --wav-gold-ink: color-mix(in srgb, var(--wav-gold) 58%, var(--wav-brand));
    --wav-ivory: #F6F2EA;
    --wav-ivory-2: #FBF8F2;
    --wav-surface: #FFFFFF;
    --wav-surface-warm: #FCFAF5;
    --wav-border: color-mix(in srgb, var(--wav-brand) 6%, #E9E1D6);
    --wav-ring: color-mix(in srgb, var(--wav-gold) 32%, transparent);
    --wav-text: color-mix(in srgb, var(--wav-brand) 46%, #707B8C);
    --wav-muted: #8791A2;
    --wav-heading: color-mix(in srgb, var(--wav-brand) 66%, #566172);
    --wav-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wav-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wav-success: #1E9E63;
    --wav-success-bg: #E7F4EC;
    --wav-warning: #C2841C;
    --wav-warning-bg: #FAF0DC;
    --wav-danger: #B4392B;
    --wav-danger-bg: #F8EAE5;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--wav-text);
    font-family: var(--wav-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wav-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--wav-ivory-2), var(--wav-ivory));
}
.wav * { box-sizing: border-box; }
.wav-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 980px;
    min-width: 0;
    margin: 0 auto;
}
.wav-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.wav-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 760px);
}
.wav-title-lockup > div:last-child { min-width: 0; }
.wav-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.25rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, #1E9E63, var(--wav-brand) 54%, color-mix(in srgb, var(--wav-brand) 68%, var(--wav-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wav-brand) 72%, transparent);
}
.wav-kicker {
    margin: 0 0 2px;
    color: var(--wav-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.wav-title {
    margin: 0;
    color: var(--wav-heading);
    font-family: var(--wav-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.wav-subtitle {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--wav-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.wav-hero-actions {
    display: flex;
    justify-content: flex-end;
    justify-self: end;
    min-width: max-content;
}
.wav-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.wav-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--wav-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.wav-summary-label {
    color: var(--wav-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.wav-summary-value {
    margin-top: 2px;
    color: var(--wav-heading);
    font-family: var(--wav-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}
.wav-summary-value.is-ok { color: color-mix(in srgb, var(--wav-success) 68%, var(--wav-text)); }
.wav-summary-value.is-pending { color: color-mix(in srgb, var(--wav-warning) 72%, var(--wav-text)); }
.wav-alert {
    padding: 12px 14px;
    border-radius: 13px;
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.45;
}
.wav-alert.is-success { background: var(--wav-success-bg); color: color-mix(in srgb, var(--wav-success) 70%, var(--wav-text)); border: 1px solid color-mix(in srgb, var(--wav-success) 24%, #fff); }
.wav-alert.is-error { background: var(--wav-danger-bg); color: color-mix(in srgb, var(--wav-danger) 72%, var(--wav-text)); border: 1px solid color-mix(in srgb, var(--wav-danger) 24%, #fff); }
.wav-alert.is-info { background: var(--wav-gold-soft); color: var(--wav-gold-ink); border: 1px solid var(--wav-gold-line); }
.wav-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(280px, .65fr);
    gap: 14px;
    align-items: start;
}
.wav-panel {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--wav-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
    overflow: hidden;
}
.wav-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--wav-border);
}
.wav-panel-title {
    margin: 0;
    color: var(--wav-heading);
    font-size: .9rem;
    font-weight: 650;
}
.wav-panel-sub {
    max-width: 42rem;
    margin: 3px 0 0;
    color: var(--wav-muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.45;
}
.wav-badge {
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
.wav-badge.is-ok {
    color: color-mix(in srgb, var(--wav-success) 70%, var(--wav-text));
    background: var(--wav-success-bg);
    border-color: color-mix(in srgb, var(--wav-success) 24%, #fff);
}
.wav-badge.is-pending {
    color: color-mix(in srgb, var(--wav-warning) 72%, var(--wav-text));
    background: var(--wav-warning-bg);
    border-color: color-mix(in srgb, var(--wav-warning) 26%, #fff);
}
.wav-badge-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: currentColor;
}
.wav-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    padding: 16px;
}
.wav-field { display: grid; gap: 5px; min-width: 0; }
.wav-field.full { grid-column: 1 / -1; }
.wav-field label,
.wav-group-label {
    color: var(--wav-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .035em;
    text-transform: uppercase;
}
.wav-field input[type="text"],
.wav-field input[type="password"],
.wav-field input[type="tel"] {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--wav-border);
    border-radius: 11px;
    background: var(--wav-surface-warm);
    color: var(--wav-text);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 560;
    padding: 0 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.wav-field input::placeholder {
    color: color-mix(in srgb, var(--wav-muted) 82%, #B8C0CB);
    font-weight: 520;
}
.wav-field input:focus {
    border-color: var(--wav-gold);
    background: #fff;
    box-shadow: 0 0 0 3px var(--wav-ring);
    outline: none;
}
.wav-field .hint {
    color: var(--wav-muted);
    font-size: .74rem;
    font-weight: 500;
    line-height: 1.45;
}
.wav-secure-hint {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.wav-toggle-group {
    grid-column: 1 / -1;
    display: grid;
    gap: 9px;
    padding-top: 12px;
    border-top: 1px dashed var(--wav-border);
}
.wav-toggle {
    position: relative;
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    min-height: 54px;
    padding: 9px 10px;
    border: 1px solid var(--wav-border);
    border-radius: 13px;
    background: var(--wav-surface-warm);
    color: var(--wav-text);
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.wav-toggle:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--wav-gold) 38%, var(--wav-border));
    background: #fff;
}
.wav-toggle input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.wav-toggle-ui {
    width: 42px;
    height: 24px;
    border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--wav-brand) 12%, #DAD4C8);
    background: #fff;
    padding: 2px;
    transition: background .18s ease, border-color .18s ease, box-shadow .18s ease;
}
.wav-toggle-ui::before {
    content: "";
    display: block;
    width: 18px;
    height: 18px;
    border-radius: 999px;
    background: var(--wav-muted);
    box-shadow: 0 4px 10px -6px rgba(27,39,70,.45);
    transition: transform .18s cubic-bezier(.22,1,.36,1), background .18s ease;
}
.wav-toggle input:checked + .wav-toggle-ui {
    background: linear-gradient(135deg, var(--wav-success-bg), #fff);
    border-color: color-mix(in srgb, var(--wav-success) 40%, #fff);
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--wav-success) 14%, transparent);
}
.wav-toggle input:checked + .wav-toggle-ui::before {
    transform: translateX(18px);
    background: linear-gradient(135deg, var(--wav-success), color-mix(in srgb, var(--wav-success) 72%, #000));
}
.wav-toggle input:focus-visible + .wav-toggle-ui {
    outline: 3px solid var(--wav-ring);
    outline-offset: 2px;
}
.wav-toggle strong {
    display: block;
    color: var(--wav-heading);
    font-size: .88rem;
    font-weight: 650;
    line-height: 1.25;
}
.wav-toggle small {
    display: block;
    margin-top: 2px;
    color: var(--wav-muted);
    font-size: .74rem;
    font-weight: 500;
    line-height: 1.35;
}
.wav-actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}
.wav-test-form {
    display: flex;
    justify-content: flex-end;
    padding: 0 16px 16px;
}
.wav-hero-actions .wav-test-form {
    padding: 0;
}
.wav-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--wav-gold) 86%, #fff), color-mix(in srgb, var(--wav-gold) 72%, var(--wav-brand)));
    color: #fff;
    cursor: pointer;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 650;
    line-height: 1;
    overflow: hidden;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--wav-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease;
}
.wav-btn:hover:not(:disabled) { transform: translateY(-1px); }
.wav-btn:active:not(:disabled) { transform: translateY(0) scale(.98); }
.wav-btn:focus-visible { outline: 3px solid var(--wav-ring); outline-offset: 2px; }
.wav-btn:disabled {
    cursor: not-allowed;
    opacity: .58;
    box-shadow: none;
}
.wav-btn.sec {
    background: var(--wav-surface);
    color: var(--wav-gold-ink);
    border-color: var(--wav-gold-line);
    box-shadow: none;
}
.wav-btn-shine {
    position: absolute;
    inset: 0 auto 0 0;
    width: 42%;
    pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.5), transparent);
    transform: translateX(-160%) skewX(-18deg);
}
.wav-btn:hover:not(:disabled) .wav-btn-shine {
    transition: transform .7s ease;
    transform: translateX(320%) skewX(-18deg);
}
.wav-btn-label {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}
.wav-steps {
    padding: 14px 16px 16px;
    color: var(--wav-muted);
    font-size: .84rem;
    line-height: 1.6;
}
.wav-step-intro { margin: 0 0 10px; }
.wav-steps ol {
    display: grid;
    gap: 9px;
    margin: 0;
    padding: 0;
    list-style: none;
    counter-reset: wav-step;
}
.wav-steps li {
    counter-increment: wav-step;
    position: relative;
    padding-left: 36px;
}
.wav-steps li::before {
    content: counter(wav-step);
    position: absolute;
    left: 0;
    top: 1px;
    width: 24px;
    height: 24px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    background: var(--wav-gold-soft);
    color: var(--wav-gold-ink);
    border: 1px solid var(--wav-gold-line);
    font-size: .72rem;
    font-weight: 650;
}
.wav-steps strong {
    color: var(--wav-heading);
    font-weight: 650;
}
.wav-note {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 16px 18px;
    background: var(--wav-gold-soft);
    border: 1px solid var(--wav-gold-line);
    border-radius: 16px;
    color: var(--wav-muted);
    font-size: .88rem;
    line-height: 1.55;
}
.wav-note i {
    color: var(--wav-gold-ink);
    font-size: 1.1rem;
    margin-top: 2px;
}
.wav-note strong {
    display: block;
    margin-bottom: 2px;
    color: var(--wav-heading);
    font-weight: 650;
}

@media (max-width: 900px) {
    .wav-hero-section,
    .wav-grid {
        grid-template-columns: minmax(0, 1fr);
    }
    .wav-hero-actions {
        justify-content: flex-start;
        justify-self: start;
        min-width: 0;
    }
}
@media (max-width: 720px) {
    .wav-summary { grid-template-columns: 1fr; }
    .wav-form { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .wav { padding: 14px 12px 34px; }
    .wav-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }
    .wav-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1.05rem;
    }
    .wav-title { font-size: 2rem; }
    .wav-panel-head {
        display: grid;
        grid-template-columns: 1fr;
    }
    .wav-badge { justify-self: start; }
    .wav-btn,
    .wav-test-form,
    .wav-test-form .wav-btn,
    .wav-hero-actions,
    .wav-hero-actions form {
        width: 100%;
    }
}
@media (prefers-reduced-motion: reduce) {
    .wav *,
    .wav *::before,
    .wav *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
    .wav-btn-shine { display: none; }
}
</style>

<div class="wav">
    <div class="wav-shell">
        <section class="wav-hero-section">
            <div class="wav-title-lockup">
                <div class="wav-hero-icon" aria-hidden="true">
                    <i class="fab fa-whatsapp"></i>
                </div>
                <div>
                    <p class="wav-kicker">Mensajeria automatica</p>
                    <h1 class="wav-title">WhatsApp del hotel</h1>
                    <p class="wav-subtitle">Conecta el n&uacute;mero de tu hotel para confirmar reservas online al hu&eacute;sped y avisarte de cada venta.</p>
                </div>
            </div>
            <div class="wav-hero-actions">
                <form method="POST" action="<?= url('whatsapp/probar') ?>" class="wav-test-form">
                    <?= csrf_field() ?>
                    <button type="submit" class="wav-btn sec" <?= $conectado ? '' : 'disabled title="Primero guarda la conexion."' ?>>
                        <span class="wav-btn-label"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i>Enviar prueba</span>
                    </button>
                </form>
            </div>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="wav-alert is-<?= $waSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <section class="wav-summary" aria-label="Resumen de WhatsApp">
            <div class="wav-summary-item">
                <div class="wav-summary-label">Estado</div>
                <div class="wav-summary-value <?= $conectado ? 'is-ok' : 'is-pending' ?>"><?= $conectado ? 'Conectado' : 'Pendiente' ?></div>
            </div>
            <div class="wav-summary-item">
                <div class="wav-summary-label">Token</div>
                <div class="wav-summary-value <?= $tokenConfigurado ? 'is-ok' : 'is-pending' ?>"><?= $tokenConfigurado ? 'Guardado' : 'Falta' ?></div>
            </div>
            <div class="wav-summary-item">
                <div class="wav-summary-label">Automatizaciones</div>
                <div class="wav-summary-value"><?= (int) $automatizacionesActivas ?>/2</div>
            </div>
        </section>

        <div class="wav-grid">
            <section class="wav-panel">
                <div class="wav-panel-head">
                    <div>
                        <h2 class="wav-panel-title">Conexion Green API</h2>
                        <p class="wav-panel-sub">Guarda las credenciales de la instancia y define que mensajes automaticos debe enviar Medisoft.</p>
                    </div>
                    <span class="wav-badge <?= $conectado ? 'is-ok' : 'is-pending' ?>">
                        <span class="wav-badge-dot" aria-hidden="true"></span>
                        <?= $conectado ? 'Conectado' : 'Sin conectar' ?>
                    </span>
                </div>

                <form method="POST" action="<?= url('whatsapp/guardar') ?>" class="wav-form" autocomplete="off">
                    <?= csrf_field() ?>

                    <div class="wav-field">
                        <label for="wa-instance">ID de instancia</label>
                        <input type="text" id="wa-instance" name="id_instance" value="<?= $waSafe($cred['id_instance'] ?? '') ?>" placeholder="1101123456">
                    </div>
                    <div class="wav-field">
                        <label for="wa-token">API Token<?= $tokenConfigurado ? ' - configurado' : '' ?></label>
                        <input type="password" id="wa-token" name="api_token" placeholder="<?= $tokenConfigurado ? 'Dejar vacio para conservar' : 'd75b3a66374942c5...' ?>" autocomplete="new-password">
                        <span class="hint wav-secure-hint"><i class="fa-solid fa-lock" aria-hidden="true"></i>Se guarda cifrado; nunca se vuelve a mostrar.</span>
                    </div>
                    <div class="wav-field">
                        <label for="wa-numero">Numero para avisos</label>
                        <input type="tel" id="wa-numero" name="numero_avisos" value="<?= $waSafe($cred['numero_avisos'] ?? '') ?>" placeholder="10 digitos" inputmode="tel">
                        <span class="hint">Usa el numero de due&ntilde;o, gerencia o recepcion.</span>
                    </div>
                    <div class="wav-field">
                        <span class="wav-group-label">Estado de conexion</span>
                        <label class="wav-toggle">
                            <input type="checkbox" name="activo" value="1" <?= empty($cred) || !empty($cred['activo']) ? 'checked' : '' ?>>
                            <span class="wav-toggle-ui" aria-hidden="true"></span>
                            <span>
                                <strong>Conexion activa</strong>
                                <small>Permite que la instancia envie mensajes automaticos.</small>
                            </span>
                        </label>
                    </div>

                    <div class="wav-toggle-group">
                        <span class="wav-group-label">Mensajes automaticos del motor de reservas</span>
                        <label class="wav-toggle">
                            <input type="checkbox" name="confirmacion_huesped" value="1" <?= !empty($config['confirmacion_huesped']) ? 'checked' : '' ?>>
                            <span class="wav-toggle-ui" aria-hidden="true"></span>
                            <span>
                                <strong>Confirmar al huesped</strong>
                                <small>Envia confirmacion cuando su reserva online queda pagada.</small>
                            </span>
                        </label>
                        <label class="wav-toggle">
                            <input type="checkbox" name="aviso_dueno" value="1" <?= !empty($config['aviso_dueno']) ? 'checked' : '' ?>>
                            <span class="wav-toggle-ui" aria-hidden="true"></span>
                            <span>
                                <strong>Avisar cada reserva online</strong>
                                <small>Notifica al numero de avisos cuando entra una venta.</small>
                            </span>
                        </label>
                    </div>

                    <div class="wav-actions">
                        <button type="submit" class="wav-btn">
                            <span class="wav-btn-shine" aria-hidden="true"></span>
                            <span class="wav-btn-label"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Guardar configuracion</span>
                        </button>
                    </div>
                </form>
            </section>

            <aside class="wav-panel">
                <div class="wav-panel-head">
                    <div>
                        <h2 class="wav-panel-title">&iquest;Como conecto mi numero?</h2>
                        <p class="wav-panel-sub">El enlace se hace como una sesion de WhatsApp Web.</p>
                    </div>
                </div>
                <div class="wav-steps">
                    <p class="wav-step-intro">Usa una linea del hotel. Toma alrededor de 5 minutos:</p>
                    <ol>
                        <li>Crea una cuenta en <strong>green-api.com</strong> y abre una instancia.</li>
                        <li>Escanea el <strong>codigo QR</strong> con el WhatsApp del numero del hotel.</li>
                        <li>Copia el <strong>ID de instancia</strong> y el <strong>API Token</strong>, guarda y manda el mensaje de prueba.</li>
                    </ol>
                </div>
            </aside>
        </div>

        <div class="wav-note">
            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
            <div>
                <strong>Privacidad de credenciales</strong>
                El token queda cifrado y no se vuelve a mostrar en pantalla. Si capturas uno nuevo, reemplaza al anterior al guardar.
            </div>
        </div>
    </div>
</div>
