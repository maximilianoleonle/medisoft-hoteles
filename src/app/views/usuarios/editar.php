<?php
$esGestionHotel = $esGestionHotel ?? false;
$workerLabel = $esGestionHotel ? 'Trabajador' : 'Usuario';
$workerLabelLower = $esGestionHotel ? 'trabajador' : 'usuario';
$usuario = $usuario ?? [];
$usuarioNombre = (string)($usuario['nombre_completo'] ?? '');
$usuarioLogin = (string)($usuario['nombre_usuario'] ?? '');
$usuarioRol = (string)($usuario['rol'] ?? '');
$usuarioActivo = !empty($usuario['activo']);

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
.worker-select-caret {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    color: #94A3B8;
}

.worker-control-icon {
    left: 12px;
}

.worker-control-action,
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

.worker-input:disabled {
    background: #F1F5F9;
    color: #64748B;
    cursor: not-allowed;
}

.worker-message {
    min-height: 16px;
    color: var(--worker-muted);
    font-size: .74rem;
    font-weight: 800;
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

.worker-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 30px;
    padding: 6px 10px;
    border-radius: 999px;
    font-size: .74rem;
    font-weight: 900;
}

.worker-status-pill.is-active {
    color: #047857;
    background: #ECFDF5;
    border: 1px solid #A7F3D0;
}

.worker-status-pill.is-inactive {
    color: #B91C1C;
    background: #FEF2F2;
    border: 1px solid #FECACA;
}

.worker-current-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px;
}

.worker-current-main {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
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
.worker-permission-list,
.worker-system-list {
    display: grid;
    gap: 8px;
    margin-top: 14px;
}

.worker-preview-row,
.worker-permission,
.worker-note,
.worker-system-row {
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
.worker-note span,
.worker-system-row span {
    color: var(--worker-muted);
    font-size: .76rem;
    font-weight: 800;
}

.worker-preview-row strong,
.worker-note strong,
.worker-system-row strong {
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
    .worker-current-card,
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
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');
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
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--worker-brand) 54%, color-mix(in srgb, var(--worker-brand) 68%, #2F8A70)) !important;
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
                    <i class="fas fa-user-edit"></i>
                </div>
                <div>
                    <div class="worker-kicker">Equipo del hotel</div>
                    <h1 class="worker-title">Editar <?= htmlspecialchars($workerLabel, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="worker-subtitle">
                        Actualiza los datos de acceso, contacto y rol operativo sin perder de vista el estado actual de la cuenta.
                    </p>
                </div>
            </div>
            <a href="<?= url('usuarios') ?>" class="worker-back-btn">
                <i class="fas fa-arrow-left"></i>
                Volver al listado
            </a>
        </section>

        <section class="worker-panel worker-current-card">
            <div class="worker-current-main">
                <div class="worker-preview-avatar"><?= htmlspecialchars(worker_form_initials($usuarioNombre ?: $usuarioLogin), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="min-w-0">
                    <p class="worker-preview-name"><?= htmlspecialchars($usuarioNombre, ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="worker-preview-user">@<?= htmlspecialchars($usuarioLogin, ENT_QUOTES, 'UTF-8') ?> &middot; ID #<?= (int)($usuario['id'] ?? 0) ?></p>
                </div>
            </div>
            <?php if ($usuarioActivo): ?>
                <span class="worker-status-pill is-active"><i class="fas fa-check-circle"></i>Cuenta activa</span>
            <?php else: ?>
                <span class="worker-status-pill is-inactive"><i class="fas fa-times-circle"></i>Cuenta inactiva</span>
            <?php endif; ?>
        </section>

        <form method="POST" action="<?= url("usuarios/{$usuario['id']}/update") ?>" id="editUserForm">
            <?= csrf_field() ?>

            <div class="worker-form-grid">
                <div class="worker-main-stack">
                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-key"></i></div>
                                <div>
                                    <h2>Acceso al sistema</h2>
                                    <p>Usuario, contrasena opcional y rol operativo.</p>
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
                                               value="<?= htmlspecialchars(old('nombre_usuario', $usuarioLogin), ENT_QUOTES, 'UTF-8') ?>"
                                               required
                                               minlength="4"
                                               pattern="[a-zA-Z0-9_]+"
                                               title="Solo letras, numeros y guiones bajos"
                                               class="worker-input">
                                    </div>
                                    <p class="worker-message">Minimo 4 caracteres. Usa letras, numeros o guion bajo.</p>
                                </div>

                                <div class="worker-field">
                                    <label for="password" class="worker-label">Nueva contrasena</label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-lock"></i></span>
                                        <input type="password"
                                               id="password"
                                               name="password"
                                               minlength="10"
                                               class="worker-input has-right"
                                               placeholder="Dejar vacio para mantener">
                                        <button type="button" onclick="togglePassword()" class="worker-control-action" aria-label="Mostrar contrasena">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                    <p class="worker-message">Solo llena este campo si deseas cambiarla.</p>
                                </div>

                                <div class="worker-field">
                                    <label for="rol" class="worker-label">Rol *</label>
                                    <?php if (($usuario['id'] ?? null) == user_id()): ?>
                                        <div class="worker-control-wrap">
                                            <span class="worker-control-icon"><i class="fas fa-user-tag"></i></span>
                                            <input type="text"
                                                   value="<?= htmlspecialchars(ucfirst($usuarioRol), ENT_QUOTES, 'UTF-8') ?>"
                                                   disabled
                                                   class="worker-input">
                                            <input type="hidden" name="rol" value="<?= htmlspecialchars($usuarioRol, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                        <p class="worker-message text-amber-700"><i class="fas fa-exclamation-triangle"></i> No puedes cambiar tu propio rol.</p>
                                    <?php else: ?>
                                        <div class="worker-control-wrap">
                                            <span class="worker-control-icon"><i class="fas fa-user-tag"></i></span>
                                            <select id="rol" name="rol" required class="worker-input has-right appearance-none" onchange="actualizarPermisos()">
                                                <option value="gerente" <?= $usuarioRol == 'gerente' ? 'selected' : '' ?>>Gerente</option>
                                                <option value="administrador" <?= $usuarioRol == 'administrador' ? 'selected' : '' ?>>Administrador</option>
                                                <option value="recepcionista" <?= $usuarioRol == 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                                            </select>
                                            <span class="worker-select-caret"><i class="fas fa-chevron-down"></i></span>
                                        </div>
                                    <?php endif; ?>
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
                                    <p>Informacion de contacto para operacion interna.</p>
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
                                               value="<?= htmlspecialchars(old('nombre_completo', $usuarioNombre), ENT_QUOTES, 'UTF-8') ?>"
                                               required
                                               class="worker-input">
                                    </div>
                                </div>

                                <div class="worker-field">
                                    <label for="email" class="worker-label">Email <span class="worker-optional">(opcional)</span></label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-envelope"></i></span>
                                        <input type="email"
                                               id="email"
                                               name="email"
                                               value="<?= htmlspecialchars(old('email', $usuario['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               class="worker-input"
                                               placeholder="correo@hotel.com">
                                    </div>
                                </div>

                                <div class="worker-field">
                                    <label for="telefono" class="worker-label">Telefono <span class="worker-optional">(opcional)</span></label>
                                    <div class="worker-control-wrap">
                                        <span class="worker-control-icon"><i class="fas fa-phone"></i></span>
                                        <input type="tel"
                                               id="telefono"
                                               name="telefono"
                                               value="<?= htmlspecialchars(old('telefono', $usuario['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                               class="worker-input"
                                               placeholder="(555) 123-4567">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="worker-side-stack">
                    <section class="worker-panel worker-preview-card">
                        <div class="worker-preview-top">
                            <div id="preview-avatar" class="worker-preview-avatar"><?= htmlspecialchars(worker_form_initials($usuarioNombre ?: $usuarioLogin), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="min-w-0">
                                <p id="preview-nombre" class="worker-preview-name"><?= htmlspecialchars($usuarioNombre, ENT_QUOTES, 'UTF-8') ?></p>
                                <p id="preview-usuario" class="worker-preview-user">@<?= htmlspecialchars($usuarioLogin, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <div class="worker-preview-list">
                            <div class="worker-preview-row">
                                <span>Rol</span>
                                <strong id="preview-rol"><?= htmlspecialchars(ucfirst($usuarioRol), ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="worker-preview-row">
                                <span>Email</span>
                                <strong id="preview-email"><?= htmlspecialchars(($usuario['email'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></strong>
                            </div>
                            <div class="worker-preview-row">
                                <span>Telefono</span>
                                <strong id="preview-telefono"><?= htmlspecialchars(($usuario['telefono'] ?? '') ?: '-', ENT_QUOTES, 'UTF-8') ?></strong>
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
                            <div id="permisosRol" class="worker-permission-list"></div>
                        </div>
                    </section>

                    <section class="worker-panel">
                        <div class="worker-panel-head">
                            <div class="worker-panel-title">
                                <div class="worker-panel-icon"><i class="fas fa-clock"></i></div>
                                <div>
                                    <h2>Actividad</h2>
                                    <p>Datos de seguimiento de la cuenta.</p>
                                </div>
                            </div>
                        </div>
                        <div class="worker-panel-body">
                            <div class="worker-system-list">
                                <div class="worker-system-row">
                                    <span>Creado</span>
                                    <strong><?= !empty($usuario['created_at']) ? htmlspecialchars(format_datetime($usuario['created_at']), ENT_QUOTES, 'UTF-8') : '-' ?></strong>
                                </div>
                                <div class="worker-system-row">
                                    <span>Ultimo login</span>
                                    <strong><?= !empty($usuario['ultimo_login']) ? htmlspecialchars(format_datetime($usuario['ultimo_login']), ENT_QUOTES, 'UTF-8') : 'Nunca' ?></strong>
                                </div>
                                <?php if (!empty($usuario['ip_ultimo_login'])): ?>
                                    <div class="worker-system-row">
                                        <span>IP ultimo login</span>
                                        <strong><?= htmlspecialchars($usuario['ip_ultimo_login'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                <?php endif; ?>
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
                    <a href="<?= url('usuarios') ?>" class="worker-secondary-btn">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" class="worker-action-btn" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Actualizar <?= htmlspecialchars($workerLabel, ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const rolInicial = <?= json_encode($usuarioRol, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    if (!passwordInput || !toggleIcon) return;

    const show = passwordInput.type === 'password';
    passwordInput.type = show ? 'text' : 'password';
    toggleIcon.classList.toggle('fa-eye', !show);
    toggleIcon.classList.toggle('fa-eye-slash', show);
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

function titleRole(rol) {
    return rol ? rol.charAt(0).toUpperCase() + rol.slice(1) : '-';
}

function actualizarPermisos() {
    const rolSelect = document.getElementById('rol');
    const permisosDiv = document.getElementById('permisosRol');
    const rolActual = rolSelect ? rolSelect.value : rolInicial;
    if (!permisosDiv) return;

    permisosDiv.innerHTML = '';
    (permisosPorRol[rolActual] || []).forEach(item => {
        permisosDiv.innerHTML += `
            <div class="worker-permission">
                <i class="fas ${item.icon}"></i>
                <span>${item.permiso}</span>
            </div>
        `;
    });

    actualizarPreview();
}

function actualizarPreview() {
    const nombreCompleto = document.getElementById('nombre_completo')?.value || 'Nombre del <?= htmlspecialchars($workerLabelLower, ENT_QUOTES, 'UTF-8') ?>';
    const nombreUsuario = document.getElementById('nombre_usuario')?.value || 'usuario';
    const email = document.getElementById('email')?.value || '-';
    const telefono = document.getElementById('telefono')?.value || '-';
    const rol = document.getElementById('rol')?.value || rolInicial;
    const avatar = document.getElementById('preview-avatar');

    if (avatar) {
        const iniciales = nombreCompleto.split(' ').filter(Boolean).map(n => n[0]).join('').substring(0, 2).toUpperCase();
        avatar.textContent = iniciales || '?';
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

    const rolSelect = document.getElementById('rol');
    if (rolSelect) rolSelect.addEventListener('change', actualizarPermisos);

    const form = document.getElementById('editUserForm');
    const submitBtn = document.getElementById('submitBtn');
    const formAlert = document.getElementById('user-form-alert');
    let submitArmed = false;
    let submitResetTimer = null;

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

    function resetSubmitConfirmation(keepAlert = false) {
        submitArmed = false;
        window.clearTimeout(submitResetTimer);
        if (submitBtn) {
            submitBtn.classList.remove('is-confirming');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> Actualizar <?= htmlspecialchars($workerLabel, ENT_QUOTES, 'UTF-8') ?>';
        }
        if (!keepAlert) clearFormAlert();
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (submitBtn?.disabled) return;
            if (!form.checkValidity()) {
                resetSubmitConfirmation(true);
                setFormAlert('Revisa los campos marcados antes de guardar los cambios.', 'error');
                form.reportValidity();
                return;
            }

            if (!submitArmed) {
                submitArmed = true;
                if (submitBtn) {
                    submitBtn.classList.add('is-confirming');
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirmar cambios';
                }
                setFormAlert('Vuelve a presionar para guardar los cambios de este acceso.', 'info');
                submitResetTimer = window.setTimeout(() => resetSubmitConfirmation(), 7000);
                return;
            }

            window.clearTimeout(submitResetTimer);
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            }
            clearFormAlert();
            this.submit();
        });

        form.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('input', () => resetSubmitConfirmation());
            field.addEventListener('change', () => resetSubmitConfirmation());
        });
    }

    actualizarPermisos();
    actualizarPreview();
});
</script>

<?php clear_old_input(); ?>
