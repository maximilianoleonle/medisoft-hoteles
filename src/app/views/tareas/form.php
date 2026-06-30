<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$habitaciones = is_array($habitaciones ?? null) ? $habitaciones : [];
$valores = is_array($valores ?? null) ? $valores : [];
$tareaFormFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

if (!function_exists('tk_create_form_error')) {
    function tk_create_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? tlm_safe($message) : '';
    }
}

if (!function_exists('tk_create_form_error_class')) {
    function tk_create_form_error_class(array $errors, string $field): string
    {
        return tk_create_form_error($errors, $field) !== '' ? ' tk-field-error' : '';
    }
}

if (!function_exists('tk_create_form_error_attrs')) {
    function tk_create_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (tk_create_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . tlm_safe($errorId) . '"';
    }
}

$categoriaSeleccionada = (string)old('categoria', (string)($valores['categoria'] ?? 'general'));
$prioridadSeleccionada = (string)old('prioridad', (string)($valores['prioridad'] ?? 'media'));
$habitacionSeleccionada = (string)old('habitacion_id', (string)($valores['habitacion_id'] ?? ''));
$fechaProgramadaValor = old('fecha_programada', (string)($valores['fecha_programada'] ?? ''));
$fechaLimiteValor = old('fecha_limite', (string)($valores['fecha_limite'] ?? ''));
$tituloValor = old('titulo', (string)($valores['titulo'] ?? ''));
$descripcionValor = old('descripcion', (string)($valores['descripcion'] ?? ''));

$categorias = [
    'general' => 'General',
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
];
$prioridades = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];
?>

<style>
.tk-form-page {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 72%, #000);
    --tk-ivory: #F6F2EA; --tk-ivory-2: #FBF8F2;
    --tk-surface: #FFFFFF; --tk-surface-warm: #FCFAF5;
    --tk-border: color-mix(in srgb, var(--tk-brand) 7%, #E7E1D4);
    --tk-ring: color-mix(in srgb, var(--tk-gold) 32%, transparent);
    --tk-text: #171717; --tk-muted: #667085; --tk-heading: #111827;
    --tk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --tk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--tk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--tk-ivory-2), var(--tk-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.tk-form-page .tk-shell { display: grid; gap: 14px; max-width: 1040px; }
.tk-form-page .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.tk-form-page .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-form-page .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-form-page .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.tk-form-page .tk-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--tk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.tk-form-page .tk-panel { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.tk-form-page .tk-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.tk-form-page .tk-full { grid-column: 1 / -1; }
.tk-form-page label { display: block; font-size: .74rem; font-weight: 700; color: var(--tk-muted); text-transform: uppercase; letter-spacing: .045em; margin-bottom: 6px; }
.tk-form-page .tk-req { color: var(--tk-gold-ink); }
.tk-form-page .tk-field {
    width: 100%; min-height: 44px; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--tk-text); font-weight: 600; font-size: .9rem; font-family: var(--tk-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.tk-form-page textarea.tk-field { min-height: 120px; resize: vertical; }
.tk-form-page select.tk-field { cursor: pointer; }
.tk-form-page .tk-field:focus { border-color: var(--tk-gold); box-shadow: 0 0 0 3px var(--tk-ring); outline: none; background: #fff; }
.tk-form-page .tk-help { margin-top: 6px; font-size: .76rem; color: var(--tk-muted); }
.tk-form-page .tk-field-error { border-color: #B4392B; background: #FFF7F6; }
.tk-form-page .tk-form-error { display: block; margin-top: 7px; color: #B4392B; font-size: .78rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.tk-form-page .tk-error-summary { margin-bottom: 16px; padding: 12px 14px; border: 1px solid #F0B8AE; border-radius: 12px; background: #FFF7F6; color: #9E2A1D; font-size: .86rem; font-weight: 700; }

.tk-form-page .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.tk-form-page .tk-btn:hover { transform: translateY(-1px); }
.tk-form-page .tk-btn-gold { background: linear-gradient(135deg, var(--tk-gold), color-mix(in srgb, var(--tk-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--tk-gold) 58%, transparent); }
.tk-form-page .tk-btn-muted { background: var(--tk-surface); border-color: var(--tk-border); color: var(--tk-muted); }
.tk-form-page .tk-back { display: inline-flex; align-items: center; gap: 8px; color: var(--tk-muted); text-decoration: none; font-weight: 700; font-size: .85rem; }
.tk-form-page .tk-back:hover { color: var(--tk-gold-ink); }

.tk-form-page .tk-shell {
    width: min(100%, 1040px);
    margin: 0 auto;
}

.tk-form-page form.tk-panel {
    padding: 18px;
}

.tk-form-page form.tk-panel > .flex.flex-wrap {
    align-items: center;
    justify-content: flex-end;
    padding-top: 14px;
    border-top: 1px solid var(--tk-border);
}

@media (max-width: 720px) {
    .tk-form-page {
        padding: 18px 10px 26px !important;
        background:
            repeating-linear-gradient(135deg, color-mix(in srgb, var(--tk-gold) 3%, transparent) 0 1px, transparent 1px 22px),
            linear-gradient(180deg, var(--tk-ivory-2), var(--tk-ivory));
    }

    .tk-form-page .tk-shell {
        gap: 9px;
    }

    .tk-form-page .tk-back {
        min-height: 32px;
        padding: 0 4px;
        font-size: .72rem;
    }

    .tk-form-page .tk-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 10px;
        margin-bottom: 5px;
    }

    .tk-form-page .tk-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        font-size: 1rem;
    }

    .tk-form-page .tk-kicker {
        margin-bottom: 2px;
        font-size: .61rem;
        line-height: 1;
    }

    .tk-form-page .tk-title {
        font-size: 1.45rem;
        line-height: .98;
    }

    .tk-form-page .tk-subtitle {
        display: -webkit-box;
        margin-top: 3px;
        font-size: .72rem;
        line-height: 1.3;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .tk-form-page form.tk-panel {
        padding: 10px;
        border-radius: 15px;
        box-shadow: 0 10px 24px rgba(24, 33, 46, .07);
    }

    .tk-form-page .tk-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }

    .tk-form-page label {
        margin-bottom: 4px;
        font-size: .58rem;
        letter-spacing: .045em;
        line-height: 1.1;
    }

    .tk-form-page .tk-field {
        min-height: 44px;
        border-radius: 11px;
        padding: 7px 10px;
        font-size: 13px;
        line-height: 1.15;
        font-weight: 650;
    }

    .tk-form-page select.tk-field {
        font-size: 12.5px;
        text-overflow: ellipsis;
    }

    .tk-form-page textarea.tk-field {
        min-height: 76px;
        padding-top: 9px;
        line-height: 1.32;
    }

    .tk-form-page .tk-help {
        display: none;
    }

    .tk-form-page .tk-form-error {
        margin-top: 2px;
        font-size: .66rem;
        line-height: 1.25;
    }

    .tk-form-page form.tk-panel > .flex.flex-wrap {
        display: grid !important;
        grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr);
        gap: 8px;
        margin-top: 10px !important;
        padding-top: 9px;
    }

    .tk-form-page .tk-btn {
        width: 100%;
        min-height: 44px;
        border-radius: 12px;
        padding: 0 10px;
        font-size: .72rem;
        line-height: 1.1;
    }
}

@media (max-width: 380px) {
    .tk-form-page .tk-grid,
    .tk-form-page form.tk-panel > .flex.flex-wrap {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="tk-form-page p-4 sm:p-6">
    <div class="tk-shell">
        <a class="tk-back" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>

        <section class="tk-title-lockup">
            <div class="tk-hero-icon"><i class="fas fa-list-check"></i></div>
            <div>
                <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                <h1 class="tk-title">Nueva tarea</h1>
                <p class="tk-subtitle">Registra un pendiente de limpieza, mantenimiento o seguimiento general. La tarea nace en estado <strong>pendiente</strong>; despu&eacute;s la asignas y le das seguimiento.</p>
            </div>
        </section>

        <form method="POST" action="<?= url('tareas') ?>" class="tk-panel p-5">
            <?= csrf_field() ?>

            <?php if (tk_create_form_error($tareaFormFieldErrors, '_global') !== ''): ?>
                <div class="tk-error-summary ms-form-error-summary" role="alert">
                    <?= tk_create_form_error($tareaFormFieldErrors, '_global') ?>
                </div>
            <?php endif; ?>

            <div class="tk-grid">
                <div class="tk-full">
                    <label for="titulo">T&iacute;tulo <span class="tk-req">*</span></label>
                    <input class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'titulo') ?>" id="titulo" name="titulo" type="text" maxlength="160" value="<?= tlm_safe($tituloValor) ?>" placeholder="Ej. Cambiar foco del pasillo 2" required<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'titulo', 'ms-form-error-tarea_titulo') ?>>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'titulo') !== ''): ?>
                        <span id="ms-form-error-tarea_titulo" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'titulo') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="categoria">Categor&iacute;a</label>
                    <select class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'categoria') ?>" id="categoria" name="categoria"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'categoria', 'ms-form-error-tarea_categoria') ?>>
                        <?php foreach ($categorias as $key => $label): ?>
                            <option value="<?= tlm_safe($key) ?>" <?= $categoriaSeleccionada === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'categoria') !== ''): ?>
                        <span id="ms-form-error-tarea_categoria" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'categoria') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="prioridad">Prioridad</label>
                    <select class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'prioridad') ?>" id="prioridad" name="prioridad"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'prioridad', 'ms-form-error-tarea_prioridad') ?>>
                        <?php foreach ($prioridades as $key => $label): ?>
                            <option value="<?= tlm_safe($key) ?>" <?= $prioridadSeleccionada === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'prioridad') !== ''): ?>
                        <span id="ms-form-error-tarea_prioridad" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'prioridad') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="habitacion_id">Habitaci&oacute;n relacionada</label>
                    <select class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'habitacion_id') ?>" id="habitacion_id" name="habitacion_id"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'habitacion_id', 'ms-form-error-tarea_habitacion') ?>>
                        <option value="">Ninguna</option>
                        <?php foreach ($habitaciones as $habitacion): ?>
                            <option value="<?= (int)($habitacion['id'] ?? 0) ?>" <?= $habitacionSeleccionada === (string)(int)($habitacion['id'] ?? 0) ? 'selected' : '' ?>>
                                Hab. <?= tlm_safe($habitacion['numero'] ?? '') ?> - <?= tlm_safe($habitacion['estado'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="tk-help">Opcional. &Uacute;til para tareas de una habitaci&oacute;n en concreto.</div>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'habitacion_id') !== ''): ?>
                        <span id="ms-form-error-tarea_habitacion" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'habitacion_id') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="fecha_programada">Fecha programada</label>
                    <input class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'fecha_programada') ?>" id="fecha_programada" name="fecha_programada" type="datetime-local" value="<?= tlm_safe($fechaProgramadaValor) ?>"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'fecha_programada', 'ms-form-error-tarea_fecha_programada') ?>>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'fecha_programada') !== ''): ?>
                        <span id="ms-form-error-tarea_fecha_programada" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'fecha_programada') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="fecha_limite">Fecha l&iacute;mite</label>
                    <input class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'fecha_limite') ?>" id="fecha_limite" name="fecha_limite" type="datetime-local" value="<?= tlm_safe($fechaLimiteValor) ?>"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'fecha_limite', 'ms-form-error-tarea_fecha_limite') ?>>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'fecha_limite') !== ''): ?>
                        <span id="ms-form-error-tarea_fecha_limite" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'fecha_limite') ?></span>
                    <?php endif; ?>
                </div>

                <div class="tk-full">
                    <label for="descripcion">Descripci&oacute;n</label>
                    <textarea class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'descripcion') ?>" id="descripcion" name="descripcion" maxlength="2000" placeholder="Detalles de lo que hay que hacer"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'descripcion', 'ms-form-error-tarea_descripcion') ?>><?= tlm_safe($descripcionValor) ?></textarea>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'descripcion') !== ''): ?>
                        <span id="ms-form-error-tarea_descripcion" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'descripcion') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-6">
                <button class="tk-btn tk-btn-gold" type="submit"><i class="fas fa-save"></i> Crear tarea</button>
                <a class="tk-btn tk-btn-muted" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>
