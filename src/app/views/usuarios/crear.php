<?php
$esGestionHotel = $esGestionHotel ?? false;
$workerLabel = $esGestionHotel ? 'Trabajador' : 'Usuario';
$workerLabelLower = $esGestionHotel ? 'trabajador' : 'usuario';
$usuarioFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$usuarioRolValor = (string) old('rol', '');

if (!function_exists('worker_form_initials')) {
    function worker_form_initials($name) {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/', $name);
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';
        $a = function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1);
        $b = function_exists('mb_substr') ? mb_substr($second, 0, 1, 'UTF-8') : substr($second, 0, 1);
        $initials = $a . ($b ?: '');
        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    }
}

if (!function_exists('usuario_form_error')) {
    function usuario_form_error(array $errors, string $field): string
    {
        if (empty($errors[$field])) {
            return '';
        }

        $message = is_array($errors[$field]) ? reset($errors[$field]) : $errors[$field];
        return htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('usuario_form_error_class')) {
    function usuario_form_error_class(array $errors, string $field): string
    {
        return usuario_form_error($errors, $field) !== '' ? ' worker-input-error' : '';
    }
}

if (!function_exists('usuario_form_error_attrs')) {
    function usuario_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (usuario_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') . '"';
    }
}
?>

<style>
.worker-page {
    --worker-brand: var(--brand-primary, #2563EB);
    --worker-brand-dark: color-mix(in srgb, var(--worker-brand), #000 28%);
    --worker-brand-soft: color-mix(in srgb, var(--worker-brand) 7%, #F8FAFC);
    --worker-accent: var(--brand-accent, #F59E0B);
    --worker-border: color-mix(in srgb, var(--worker-brand) 10%, #E2E8F0);
    --worker-ring: color-mix(in srgb, var(--worker-brand) 16%, transparent);
    --worker-text: #0F172A;
    --worker-muted: #64748B;
    color: var(--worker-text);
}

.worker-shell {
    display: grid;
    gap: 14px;
}

.worker-hero {
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 14px;
    border-radius: 14px;
    background:
        radial-gradient(circle at right top, rgba(255,255,255,.16), transparent 34%),
        linear-gradient(135deg, var(--worker-brand-dark), var(--worker-brand));
    box-shadow: 0 16px 34px color-mix(in srgb, var(--worker-brand) 16%, transparent);
}

.worker-hero-main {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
}

.worker-hero-icon,
.worker-preview-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.worker-hero-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    color: #fff;
    background: rgba(255,255,255,.16);
    border: 1px solid rgba(255,255,255,.16);
}

.worker-kicker {
    color: rgba(255,255,255,.72);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.worker-title {
    margin: 1px 0 0;
    color: rgba(255,255,255,.98);
    font-size: clamp(1.55rem, 3vw, 2.25rem);
    font-weight: 900;
    letter-spacing: 0;
    line-height: 1.05;
}

.worker-subtitle {
    margin-top: 6px;
    max-width: 44rem;
    color: rgba(255,255,255,.92);
    font-size: .92rem;
    font-weight: 650;
    line-height: 1.45;
    text-shadow: 0 1px 1px rgba(15,23,42,.22);
}

.worker-back-btn,
.worker-action-btn,
.worker-secondary-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 38px;
    border-radius: 10px;
    font-weight: 850;
    text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease;
}

.worker-back-btn {
    flex: 0 0 auto;
    padding: .55rem .85rem;
    color: var(--worker-brand-dark);
    background: #fff;
    box-shadow: 0 10px 22px rgba(15,23,42,.14);
    white-space: nowrap;
}

.worker-back-btn:hover,
.worker-action-btn:hover,
.worker-secondary-btn:hover {
    transform: translateY(-1px);
}

.worker-form-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr);
    gap: 14px;
    align-items: start;
}

.worker-main-stack,
.worker-side-stack {
    display: grid;
    gap: 14px;
}

.worker-panel,
.worker-actions-bar {
    border: 1px solid var(--worker-border);
    border-radius: 14px;
    background: rgba(255,255,255,.96);
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}

.worker-panel {
    overflow: hidden;
}

.worker-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 14px 12px;
    border-bottom: 1px solid var(--worker-border);
    background:
        radial-gradient(circle at right top, color-mix(in srgb, var(--worker-brand) 8%, transparent), transparent 38%),
        linear-gradient(180deg, #fff, var(--worker-brand-soft));
}

.worker-panel-title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.worker-panel-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    flex: 0 0 34px;
    border-radius: 10px;
    color: var(--worker-brand-dark);
    background: #fff;
    border: 1px solid var(--worker-border);
}

.worker-panel-title h2,
.worker-preview-name {
    margin: 0;
    color: var(--worker-text);
    font-size: .98rem;
    font-weight: 900;
    line-height: 1.15;
}

.worker-panel-title p,
.worker-panel-copy,
.worker-preview-user,
.worker-help {
    color: var(--worker-muted);
    font-size: .78rem;
    font-weight: 650;
    line-height: 1.35;
}

.worker-panel-body {
    padding: 14px;
}

.worker-fields {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.worker-field {
    display: grid;
    gap: 6px;
}

.worker-field.is-full {
    grid-column: 1 / -1;
}

.worker-label {
    color: #334155;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .035em;
    text-transform: uppercase;
}

.worker-optional {
    color: var(--worker-muted);
    font-weight: 750;
    letter-spacing: 0;
    text-transform: none;
}

.worker-control-wrap {
    position: relative;
}

.worker-control-icon,
.worker-control-action,
.worker-select-caret,
.worker-state-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
}

.worker-control-icon {
    left: 12px;
}

.worker-control-action,
.worker-state-icon,
.worker-select-caret {
    right: 12px;
}

.worker-control-action {
    border: 0;
    background: transparent;
    cursor: pointer;
}

.worker-input {
    width: 100%;
    min-height: 42px;
    padding: 0 12px 0 38px;
    border: 1px solid var(--worker-border);
    border-radius: 11px;
    color: var(--worker-text);
    background: #fff;
    font-size: .9rem;
    font-weight: 650;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.worker-input.has-right {
    padding-right: 38px;
}

.worker-input:focus {
    outline: none;
    border-color: var(--worker-brand);
    box-shadow: 0 0 0 4px var(--worker-ring);
}

.worker-input.worker-input-error {
    border-color: #B4392B;
    background: #FFF7F6;
}

.worker-input:disabled {
    background: #F1F5F9;
    color: #64748B;
    cursor: not-allowed;
}

.worker-message {
    min-height: 16px;
    font-size: .74rem;
    font-weight: 800;
}

.worker-field-error {
    display: block;
    margin-top: 2px;
    color: #B4392B;
    font-size: .76rem;
    font-weight: 800;
    line-height: 1.35;
}

.worker-error-summary {
    margin-bottom: 14px;
    padding: 12px 14px;
    border: 1px solid #F0B8AE;
    border-radius: 13px;
    background: #FFF7F6;
    color: #9E2A1D;
    font-size: .84rem;
    font-weight: 800;
    line-height: 1.35;
}

.worker-form-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--worker-brand) 14%, #E7E1D4);
    background: #FFFFFF;
    color: var(--worker-text);
    font-size: .82rem;
    font-weight: 750;
    box-shadow: 0 10px 24px -20px rgba(15,23,42,.28);
}

.worker-form-alert[hidden] {
    display: none;
}

.worker-form-alert i {
    margin-top: 2px;
    color: var(--worker-brand);
}

.worker-form-alert.is-error {
    border-color: color-mix(in srgb, #DC2626 34%, #FECACA);
    background: #FEF2F2;
    color: #991B1B;
}

.worker-form-alert.is-error i {
    color: #DC2626;
}

.worker-form-alert.is-success {
    border-color: color-mix(in srgb, #059669 34%, #A7F3D0);
    background: #ECFDF5;
    color: #065F46;
}

.worker-form-alert.is-success i {
    color: #059669;
}

.worker-strength-track {
    width: 100%;
    height: 6px;
    overflow: hidden;
    border-radius: 999px;
    background: #E2E8F0;
}

.worker-strength-bar {
    height: 100%;
    width: 0;
    border-radius: inherit;
    transition: width .2s ease, background .2s ease;
}

.worker-preview-card {
    padding: 14px;
}

.worker-preview-top {
    display: flex;
    align-items: center;
    gap: 12px;
}

.worker-preview-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    color: var(--worker-brand-dark);
    background: color-mix(in srgb, var(--worker-accent) 18%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--worker-accent) 34%, var(--worker-border));
    font-size: 1rem;
    font-weight: 950;
}

.worker-preview-list,
.worker-permission-list {
    display: grid;
    gap: 8px;
    margin-top: 14px;
}

.worker-preview-row,
.worker-permission,
.worker-note {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    min-height: 34px;
    padding: 8px 10px;
    border: 1px solid var(--worker-border);
    border-radius: 11px;
    background: #fff;
}

.worker-preview-row span,
.worker-note span {
    color: var(--worker-muted);
    font-size: .76rem;
    font-weight: 800;
}

.worker-preview-row strong,
.worker-note strong {
    color: var(--worker-text);
    font-size: .8rem;
    font-weight: 900;
    text-align: right;
    overflow-wrap: anywhere;
}

.worker-permission {
    justify-content: flex-start;
    color: #475569;
    font-size: .78rem;
    font-weight: 750;
}

.worker-permission i {
    width: 16px;
    color: var(--worker-brand);
}

.worker-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 12px;
}

.worker-required-note {
    color: var(--worker-muted);
    font-size: .78rem;
    font-weight: 750;
}

.worker-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
}

.worker-secondary-btn,
.worker-action-btn {
    border: 0;
    padding: .65rem .95rem;
    cursor: pointer;
}

.worker-secondary-btn {
    color: #475569;
    background: #F1F5F9;
}

.worker-action-btn {
    color: #fff;
    background: var(--worker-brand);
    box-shadow: 0 12px 24px color-mix(in srgb, var(--worker-brand) 18%, transparent);
}

.worker-action-btn.is-confirming {
    background: linear-gradient(135deg, var(--worker-accent), color-mix(in srgb, var(--worker-accent) 72%, #000));
}

@media (max-width: 1100px) {
    .worker-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 720px) {
    .worker-hero,
    .worker-actions-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .worker-fields {
        grid-template-columns: 1fr;
    }

    .worker-back-btn,
    .worker-action-btn,
    .worker-secondary-btn {
        width: 100%;
    }
}
</style>

<style id="usuarios-form-boutique">
@import url('<?= asset('vendor/fonts/marca.css') ?>');
.worker-page {
    --worker-brand: var(--brand-primary, #1B2746) !important;
    --worker-brand-dark: color-mix(in srgb, var(--worker-brand), #000 20%) !important;
    --worker-brand-soft: color-mix(in srgb, var(--worker-brand) 4%, #FBF8F2) !important;
    --worker-accent: var(--brand-accent, #BD9441) !important;
    --worker-border: color-mix(in srgb, var(--worker-brand) 7%, #E7E1D4) !important;
    --worker-ring: color-mix(in srgb, var(--brand-accent, #BD9441) 30%, transparent) !important;
    --worker-text: #171717 !important;
    --worker-muted: #667085 !important;
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 72%, #000);
    --wk-ivory: #F6F2EA; --wk-ivory-2: #FBF8F2; --wk-heading: #111827;
    --wk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif !important;
    min-height: 100%;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--wk-ivory-2), var(--wk-ivory)) !important;
}
/* Header limpio (sin banner de color) */
.worker-page .worker-hero { background: transparent !important; box-shadow: none !important; border-radius: 0 !important; padding: 2px 2px 6px !important; overflow: visible !important; }
.worker-page .worker-hero-icon { width: 48px !important; height: 48px !important; border-radius: 15px !important; color: #fff !important; border: none !important;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--worker-brand) 54%, color-mix(in srgb, var(--worker-brand) 68%, var(--brand-accent, #BD9441))) !important;
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--worker-brand) 72%, transparent) !important; }
.worker-page .worker-kicker { color: var(--worker-muted) !important; font-weight: 700 !important; letter-spacing: .11em !important; }
.worker-page .worker-title { color: var(--wk-heading) !important; font-family: var(--wk-serif) !important; font-weight: 700 !important; font-size: clamp(2.1rem, 4vw, 3rem) !important; line-height: .98 !important; text-shadow: none !important; }
.worker-page .worker-subtitle { color: var(--worker-muted) !important; font-weight: 500 !important; text-shadow: none !important; }
.worker-page .worker-back-btn { color: var(--worker-muted) !important; background: #fff !important; border: 1px solid var(--worker-border) !important; box-shadow: 0 1px 2px rgba(27,39,70,.05) !important; font-weight: 700 !important; }
/* Paneles */
.worker-page .worker-panel, .worker-page .worker-actions-bar { background: #FFFFFF !important; border: 1px solid var(--worker-border) !important; border-radius: 16px !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28) !important; }
.worker-page .worker-panel-head { background: var(--wk-ivory-2) !important; border-bottom: 1px solid var(--worker-border) !important; }
.worker-page .worker-panel-title h2, .worker-page .worker-preview-name { color: var(--wk-heading) !important; font-weight: 700 !important; }
.worker-page .worker-panel-icon { color: var(--wk-gold-ink) !important; background: var(--wk-gold-soft) !important; border-color: var(--wk-gold-line) !important; }
.worker-page .worker-label { color: var(--worker-muted) !important; font-weight: 700 !important; }
/* Inputs */
.worker-page .worker-input { background: #FCFAF5 !important; border-color: var(--worker-border) !important; border-radius: 11px !important; font-weight: 600 !important; }
.worker-page .worker-input:focus { border-color: var(--wk-gold) !important; box-shadow: 0 0 0 3px var(--worker-ring) !important; background: #fff !important; }
.worker-page .worker-input.worker-input-error { border-color: #B4392B !important; background: #FFF7F6 !important; }
/* Botón primario = oro */
.worker-page .worker-action-btn { background: linear-gradient(135deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 76%, #000)) !important; color: #fff !important; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--wk-gold) 58%, transparent) !important; }
.worker-page .worker-secondary-btn { background: #fff !important; border: 1px solid var(--worker-border) !important; color: var(--worker-muted) !important; }
/* Preview + permisos */
.worker-page .worker-preview-avatar { background: linear-gradient(145deg, var(--wk-gold-soft), #fff) !important; color: var(--wk-gold-ink) !important; border-color: var(--wk-gold-line) !important; }
.worker-page .worker-preview-row, .worker-page .worker-permission, .worker-page .worker-note { background: #FCFAF5 !important; border-color: var(--worker-border) !important; }
.worker-page .worker-permission i { color: var(--wk-gold-ink) !important; }
</style>

<div class="worker-page hotel-page p-4 sm:p-6">
    <div class="worker-shell">
        <section class="worker-hero">
            <div class="worker-hero-main">
                <div class="worker-hero-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div>
                    <div class="worker-kicker">Equipo del hotel</div>
                    <h1 class="worker-title">Nuevo <?= htmlspecialchars($workerLabel, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="worker-subtitle">
                        Crea una cuenta operativa clara, con rol definido, contacto y acceso listo para el equipo de recepcion y administracion.
                    </p>
                </div>
            </div>
            <?php $back_arrow_href = back_url('usuarios'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a href="<?= back_url('usuarios') ?>" class="worker-back-btn ms-back-legacy">
                <i class="fas fa-arrow-left"></i>
                Volver
            </a>
        </section>

        <form method="POST" action="<?= url('usuarios/store') ?>" id="createUserForm">
            <?= csrf_field() ?>

            <?php if (usuario_form_error($usuarioFieldErrors, '_global') !== ''): ?>
                <div class="worker-error-summary ms-form-error-summary" role="alert">
                    <?= usuario_form_error($usuarioFieldErrors, '_global') ?>
                </div>
            <?php endif; ?>

            <div class="worker-form-grid">
                <div class="worker-main-stack">
                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-key"></i></div>
                                <div>
                                    <h2>Acceso al sistema</h2>
                                    <p>Usuario y contrasena para iniciar sesion.</p>
                                </div>
                            </div>
                        </div>
                        <div class="worker-panel-body">
                            <div class="worker-fields">
                                <div class="worker-field is-full">
                                    <label for="nombre_usuario" class="worker-label">Nombre de usuario *</label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-at"></i></span>
                                        <input type="text"
                                               id="nombre_usuario"
                                               name="nombre_usuario"
                                               value="<?= htmlspecialchars(old('nombre_usuario'), ENT_QUOTES, 'UTF-8') ?>"
                                               required
                                               minlength="4"
                                               pattern="[a-zA-Z0-9_]+"
                                               class="worker-input has-right<?= usuario_form_error_class($usuarioFieldErrors, 'nombre_usuario') ?>"
                                               placeholder="usuario123"
                                               onkeyup="verificarDisponibilidad()"<?= usuario_form_error_attrs($usuarioFieldErrors, 'nombre_usuario', 'ms-form-error-usuario_nombre_usuario') ?>>
                                        <span id="availability-icon" class="worker-state-icon hidden">
                                            <i class="fas fa-circle-check"></i>
                                        </span>
                                    </div>
                                    <?php if (usuario_form_error($usuarioFieldErrors, 'nombre_usuario') !== ''): ?>
                                        <span id="ms-form-error-usuario_nombre_usuario" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'nombre_usuario') ?></span>
                                    <?php endif; ?>
                                    <p id="availability-message" class="worker-message hidden"></p>
                                </div>

                                <div class="worker-field">
                                    <label for="password" class="worker-label">Contrasena *</label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-lock"></i></span>
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               required
                                               minlength="10"
                                               class="worker-input has-right<?= usuario_form_error_class($usuarioFieldErrors, 'password') ?>"
                                               placeholder="**********"
                                               onkeyup="checkPasswordStrength()"<?= usuario_form_error_attrs($usuarioFieldErrors, 'password', 'ms-form-error-usuario_password') ?>>
                                        <button type="button" onclick="togglePassword()" class="worker-control-action" aria-label="Mostrar contrasena">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                    <?php if (usuario_form_error($usuarioFieldErrors, 'password') !== ''): ?>
                                        <span id="ms-form-error-usuario_password" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'password') ?></span>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-2">
                                        <div class="worker-strength-track">
                                            <div id="strength-bar" class="worker-strength-bar"></div>
                                        </div>
                                        <span id="strength-text" class="worker-message"></span>
                                    </div>
                                </div>

                                <div class="worker-field">
                                    <label for="password_confirmation" class="worker-label">Confirmar contrasena *</label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-lock"></i></span>
                                        <input type="password"
                                               id="password_confirmation"
                                               name="password_confirmation"
                                               required
                                               minlength="10"
                                               class="worker-input has-right"
                                               placeholder="**********"
                                               onkeyup="checkPasswordMatch()">
                                        <span id="match-icon" class="worker-state-icon hidden">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    </div>
                                    <p id="match-message" class="worker-message hidden"></p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-id-card"></i></div>
                                <div>
                                    <h2>Datos del <?= htmlspecialchars($workerLabelLower, ENT_QUOTES, 'UTF-8') ?></h2>
                                    <p>Informacion visible para operacion interna.</p>
                                </div>
                            </div>
                        </div>
                        <div class="worker-panel-body">
                            <div class="worker-fields">
                                <div class="worker-field is-full">
                                    <label for="nombre_completo" class="worker-label">Nombre completo *</label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-user"></i></span>
                                        <input type="text"
                                               id="nombre_completo"
                                               name="nombre_completo"
                                               value="<?= htmlspecialchars(old('nombre_completo'), ENT_QUOTES, 'UTF-8') ?>"
                                               required
                                               class="worker-input<?= usuario_form_error_class($usuarioFieldErrors, 'nombre_completo') ?>"
                                               placeholder="Nombre y apellidos"
                                               onkeyup="actualizarPreview()"<?= usuario_form_error_attrs($usuarioFieldErrors, 'nombre_completo', 'ms-form-error-usuario_nombre_completo') ?>>
                                    </div>
                                    <?php if (usuario_form_error($usuarioFieldErrors, 'nombre_completo') !== ''): ?>
                                        <span id="ms-form-error-usuario_nombre_completo" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'nombre_completo') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="worker-field">
                                    <label for="email" class="worker-label">Email <span class="worker-optional">(opcional)</span></label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-envelope"></i></span>
                                        <input type="email"
                                               id="email"
                                               name="email"
                                               value="<?= htmlspecialchars(old('email'), ENT_QUOTES, 'UTF-8') ?>"
                                               class="worker-input<?= usuario_form_error_class($usuarioFieldErrors, 'email') ?>"
                                               placeholder="correo@hotel.com"<?= usuario_form_error_attrs($usuarioFieldErrors, 'email', 'ms-form-error-usuario_email') ?>>
                                    </div>
                                    <?php if (usuario_form_error($usuarioFieldErrors, 'email') !== ''): ?>
                                        <span id="ms-form-error-usuario_email" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'email') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="worker-field">
                                    <label for="telefono" class="worker-label">Telefono <span class="worker-optional">(opcional)</span></label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-phone"></i></span>
                                        <input type="tel"
                                               id="telefono"
                                               name="telefono"
                                               value="<?= htmlspecialchars(old('telefono'), ENT_QUOTES, 'UTF-8') ?>"
                                               class="worker-input<?= usuario_form_error_class($usuarioFieldErrors, 'telefono') ?>"
                                               placeholder="(555) 123-4567"<?= usuario_form_error_attrs($usuarioFieldErrors, 'telefono', 'ms-form-error-usuario_telefono') ?>>
                                    </div>
                                    <?php if (usuario_form_error($usuarioFieldErrors, 'telefono') !== ''): ?>
                                        <span id="ms-form-error-usuario_telefono" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'telefono') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-shield-alt"></i></div>
                                <div>
                                    <h2>Rol operativo</h2>
                                    <p>Define el alcance de acceso dentro del hotel.</p>
                                </div>
                            </div>
                        </div>
                        <div class="worker-panel-body">
                            <div class="worker-field">
                                <label for="rol" class="worker-label">Rol *</label>
                                <div class="worker-control-wrap">
                                    <span class="worker-control-icon"><i class="fas fa-user-tag"></i></span>
                                    <?php $rolesHotelForm = $rolesHotel ?? []; ?>
                                    <?php if (!empty($rolesHotelForm)): ?>
                                    <?php $roleIdSel = (string) old('role_id', ''); ?>
                                    <select id="role_id" name="role_id" required class="worker-input has-right appearance-none<?= usuario_form_error_class($usuarioFieldErrors, 'rol') ?>"
                                            onchange="var pr=document.getElementById('preview-rol'); if(pr&&this.selectedIndex>=0) pr.textContent=this.options[this.selectedIndex].text.trim();"<?= usuario_form_error_attrs($usuarioFieldErrors, 'rol', 'ms-form-error-usuario_rol') ?>>
                                        <option value="">Seleccione un rol</option>
                                        <?php foreach ($rolesHotelForm as $rolOpcion): ?>
                                        <option value="<?= (int) $rolOpcion['id'] ?>" <?= $roleIdSel === (string) $rolOpcion['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($rolOpcion['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php else: ?>
                                    <select id="rol" name="rol" required class="worker-input has-right appearance-none<?= usuario_form_error_class($usuarioFieldErrors, 'rol') ?>" onchange="actualizarPermisos()"<?= usuario_form_error_attrs($usuarioFieldErrors, 'rol', 'ms-form-error-usuario_rol') ?>>
                                        <option value="">Seleccione un rol</option>
                                        <option value="gerente" <?= $usuarioRolValor === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                        <option value="administrador" <?= $usuarioRolValor === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                        <option value="recepcionista" <?= $usuarioRolValor === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                    </select>
                                    <?php endif; ?>
                                    <span class="worker-select-caret"><i class="fas fa-chevron-down"></i></span>
                                </div>
                                <?php if (usuario_form_error($usuarioFieldErrors, 'rol') !== ''): ?>
                                    <span id="ms-form-error-usuario_rol" class="worker-field-error ms-form-field-error"><?= usuario_form_error($usuarioFieldErrors, 'rol') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="worker-side-stack">
                    <section class="worker-panel worker-preview-card">
                        <div class="worker-preview-top">
                            <div id="preview-avatar" class="worker-preview-avatar">?</div>
                            <div class="min-w-0">
                                <p id="preview-nombre" class="worker-preview-name">Nombre del <?= htmlspecialchars($workerLabelLower, ENT_QUOTES, 'UTF-8') ?></p>
                                <p id="preview-usuario" class="worker-preview-user">@usuario</p>
                            </div>
                        </div>

                        <div class="worker-preview-list">
                            <div class="worker-preview-row">
                                <span>Rol</span>
                                <strong id="preview-rol">-</strong>
                            </div>
                            <div class="worker-preview-row">
                                <span>Email</span>
                                <strong id="preview-email">-</strong>
                            </div>
                            <div class="worker-preview-row">
                                <span>Telefono</span>
                                <strong id="preview-telefono">-</strong>
                            </div>
                        </div>
                    </section>

                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-user-shield"></i></div>
                                <div>
                                    <h2>Permisos del rol</h2>
                                    <p>Resumen rapido del acceso.</p>
                                </div>
                            </div>
                        </div>
                        <div class="worker-panel-body">
                            <div id="permisosRol" class="worker-permission-list">
                                <p class="worker-panel-copy"><i class="fas fa-info-circle mr-1"></i>Selecciona un rol.</p>
                            </div>
                        </div>
                    </section>

                    <section class="worker-panel">
                        <div class="worker-panel-body">
                            <div class="worker-note">
                                <span>Estado inicial</span>
                                <strong>Activo al crear</strong>
                            </div>
                            <div class="worker-note mt-2">
                                <span>Seguridad</span>
                                <strong>10 caracteres minimo</strong>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <div id="user-form-alert" class="worker-form-alert" role="alert" aria-live="assertive" hidden>
                <i class="fas fa-circle-info"></i>
                <span></span>
            </div>

            <div class="worker-actions-bar mt-4">
                <div class="worker-required-note">
                    <i class="fas fa-asterisk text-red-500"></i>
                    Campos obligatorios
                </div>
                <div class="worker-actions">
                    <a href="<?= back_url('usuarios') ?>" class="worker-secondary-btn">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="worker-action-btn" id="submitBtn">
                        <i class="fas fa-user-plus"></i>
                        Crear <?= htmlspecialchars($workerLabel, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirmation');
    const toggleIcon = document.getElementById('toggleIcon');

    if (!passwordInput || !passwordConfirmInput || !toggleIcon) return;

    const show = passwordInput.type === 'password';
    passwordInput.type = show ? 'text' : 'password';
    passwordConfirmInput.type = show ? 'text' : 'password';
    toggleIcon.classList.toggle('fa-eye', !show);
    toggleIcon.classList.toggle('fa-eye-slash', show);
}

let checkTimeout;
function verificarDisponibilidad() {
    clearTimeout(checkTimeout);
    const input = document.getElementById('nombre_usuario');
    const icon = document.getElementById('availability-icon');
    const message = document.getElementById('availability-message');

    if (!input || !icon || !message) return;

    if (input.value.length < 4) {
        icon.classList.add('hidden');
        message.classList.add('hidden');
        return;
    }

    checkTimeout = setTimeout(() => {
        const disponible = !['admin', 'user', 'test'].includes(input.value.toLowerCase());
        icon.classList.remove('hidden');
        message.classList.remove('hidden');

        if (disponible) {
            icon.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i>';
            message.textContent = 'Disponible';
            message.className = 'worker-message text-emerald-600';
        } else {
            icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
            message.textContent = 'No disponible';
            message.className = 'worker-message text-red-600';
        }
    }, 400);
}

function checkPasswordStrength() {
    const password = document.getElementById('password')?.value || '';
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');
    if (!strengthBar || !strengthText) return;

    let strength = 0;
    if (password.length >= 10) strength++;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;

    const levels = [
        { width: '25%', color: '#EF4444', text: 'Debil', className: 'text-red-600' },
        { width: '50%', color: '#F97316', text: 'Regular', className: 'text-orange-600' },
        { width: '75%', color: '#EAB308', text: 'Buena', className: 'text-yellow-700' },
        { width: '100%', color: '#10B981', text: 'Excelente', className: 'text-emerald-600' }
    ];

    const level = levels[Math.max(0, strength - 1)] || levels[0];
    strengthBar.style.width = password.length > 0 ? level.width : '0';
    strengthBar.style.background = level.color;
    strengthText.textContent = password.length > 0 ? level.text : '';
    strengthText.className = 'worker-message ' + level.className;
}

function checkPasswordMatch() {
    const password = document.getElementById('password')?.value || '';
    const confirmation = document.getElementById('password_confirmation')?.value || '';
    const icon = document.getElementById('match-icon');
    const message = document.getElementById('match-message');
    if (!icon || !message) return;

    if (confirmation.length === 0) {
        icon.classList.add('hidden');
        message.classList.add('hidden');
        return;
    }

    icon.classList.remove('hidden');
    message.classList.remove('hidden');

    if (password === confirmation) {
        icon.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i>';
        message.textContent = 'Coinciden';
        message.className = 'worker-message text-emerald-600';
    } else {
        icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
        message.textContent = 'No coinciden';
        message.className = 'worker-message text-red-600';
    }
}

const permisosPorRol = {
    gerente: [
        { icon: 'fa-crown', permiso: 'Acceso total al sistema' },
        { icon: 'fa-users', permiso: 'Gestion completa de usuarios' },
        { icon: 'fa-chart-line', permiso: 'Todos los reportes' },
        { icon: 'fa-cog', permiso: 'Configuracion del sistema' }
    ],
    administrador: [
        { icon: 'fa-tachometer-alt', permiso: 'Gestion operativa' },
        { icon: 'fa-chart-bar', permiso: 'Reportes' },
        { icon: 'fa-cash-register', permiso: 'Caja' }
    ],
    recepcionista: [
        { icon: 'fa-calendar-check', permiso: 'Check-in y check-out' },
        { icon: 'fa-bed', permiso: 'Consultas basicas' }
    ]
};

function actualizarPermisos() {
    const rolSelect = document.getElementById('rol');
    const permisosDiv = document.getElementById('permisosRol');
    const rolActual = rolSelect?.value || '';

    actualizarPreview();
    if (!permisosDiv) return;

    if (!rolActual) {
        permisosDiv.innerHTML = '<p class="worker-panel-copy"><i class="fas fa-info-circle mr-1"></i>Selecciona un rol.</p>';
        return;
    }

    permisosDiv.innerHTML = '';
    (permisosPorRol[rolActual] || []).forEach(item => {
        permisosDiv.innerHTML += `
            <div class="worker-permission">
                <i class="fas ${item.icon}"></i>
                <span>${item.permiso}</span>
            </div>
        `;
    });
}

function titleRole(rol) {
    return rol ? rol.charAt(0).toUpperCase() + rol.slice(1) : '-';
}

function actualizarPreview() {
    const nombreCompleto = document.getElementById('nombre_completo')?.value || 'Nombre del <?= htmlspecialchars($workerLabelLower, ENT_QUOTES, 'UTF-8') ?>';
    const nombreUsuario = document.getElementById('nombre_usuario')?.value || 'usuario';
    const email = document.getElementById('email')?.value || '-';
    const telefono = document.getElementById('telefono')?.value || '-';
    const rol = document.getElementById('rol')?.value || '';
    const avatar = document.getElementById('preview-avatar');

    if (avatar) {
        if (nombreCompleto.indexOf('Nombre del') !== 0) {
            const iniciales = nombreCompleto.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
            avatar.textContent = iniciales || '?';
        } else {
            avatar.textContent = '?';
        }
    }

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    setText('preview-nombre', nombreCompleto);
    setText('preview-usuario', '@' + nombreUsuario);
    setText('preview-email', email);
    setText('preview-telefono', telefono);
    setText('preview-rol', titleRole(rol));
}

function formatPhoneInput(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formattedValue = '';

    if (value.length > 0) {
        if (value.length <= 3) {
            formattedValue = `(${value}`;
        } else if (value.length <= 6) {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3)}`;
        } else {
            formattedValue = `(${value.slice(0, 3)}) ${value.slice(3, 6)}-${value.slice(6, 10)}`;
        }
    }

    e.target.value = formattedValue;
    actualizarPreview();
}

document.addEventListener('DOMContentLoaded', function() {
    const telefono = document.getElementById('telefono');
    if (telefono) telefono.addEventListener('input', formatPhoneInput);

    ['nombre_completo', 'nombre_usuario', 'email'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', actualizarPreview);
    });

    const form = document.getElementById('createUserForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('user-form-alert');
    function setFormAlert(message, type = 'info') {
        if (!formAlert) return;
        const icon = formAlert.querySelector('i');
        const text = formAlert.querySelector('span');
        formAlert.classList.remove('is-error', 'is-success');
        if (type === 'error') formAlert.classList.add('is-error');
        if (type === 'success') formAlert.classList.add('is-success');
        if (icon) {
            icon.className = type === 'error'
                ? 'fas fa-triangle-exclamation'
                : (type === 'success' ? 'fas fa-circle-check' : 'fas fa-circle-info');
        }
        if (text) text.textContent = message;
        formAlert.hidden = false;
    }

    function clearFormAlert() {
        if (!formAlert) return;
        formAlert.hidden = true;
        formAlert.classList.remove('is-error', 'is-success');
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (submitBtn?.disabled) return;
            if (!form.checkValidity()) {
                setFormAlert('Revisa los campos obligatorios antes de crear el acceso.', 'error');
                form.reportValidity();
                return;
            }

            const password = document.getElementById('password')?.value || '';
            const confirmation = document.getElementById('password_confirmation')?.value || '';
            const nombreCompleto = document.getElementById('nombre_completo')?.value || '';
            const nombreUsuario = document.getElementById('nombre_usuario')?.value || '';

            if (password !== confirmation) {
                setFormAlert('Las contrasenas no coinciden. Corrige la confirmacion y vuelve a intentar.', 'error');
                document.getElementById('password_confirmation')?.focus();
                return;
            }

            msConfirm({
                type: 'success',
                icon: 'login',
                title: '¿Crear acceso?',
                msg: nombreCompleto && nombreUsuario
                    ? `Se creará el acceso de ${nombreCompleto} (@${nombreUsuario}) al sistema.`
                    : 'Se creará el nuevo acceso al sistema.',
                confirmLabel: 'Crear acceso'
            }).then(ok => {
                if (!ok) return;
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando...';
                }
                clearFormAlert();
                form.submit();
            });
        });
    }

    if (document.getElementById('rol')?.value) actualizarPermisos();
    actualizarPreview();
});
</script>

<?php clear_old_input(); ?>
