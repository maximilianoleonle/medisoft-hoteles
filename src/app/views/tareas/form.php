<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$habitaciones = is_array($habitaciones ?? null) ? $habitaciones : [];
$trabajadores = is_array($trabajadores ?? null) ? $trabajadores : [];
$valores = is_array($valores ?? null) ? $valores : [];
$tareaFormFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$modo = (string)($modo ?? 'crear');
$esEdicion = $modo === 'editar';
$tareaId = (int)($valores['id'] ?? ($tarea['id'] ?? 0));
$formAction = $esEdicion && $tareaId > 0 ? url('tareas/' . $tareaId . '/actualizar') : url('tareas');
$formTitle = $esEdicion ? 'Editar tarea' : 'Nueva tarea';
$submitLabel = $esEdicion ? 'Guardar cambios' : 'Crear tarea';
$tareasExtraOld = [];
if (!$esEdicion) {
    $oldExtras = $_SESSION['old_input']['tareas_extra'] ?? [];
    if (is_array($oldExtras)) {
        foreach ($oldExtras as $extra) {
            if (is_array($extra)) {
                $tareasExtraOld[] = $extra;
            }
        }
    }
}

// IDs preseleccionados (tras un error de validacion volvemos a marcarlos).
$trabajadoresSeleccionados = [];
$oldTrabajadores = $_SESSION['old_input']['trabajador_ids'] ?? ($valores['trabajador_ids'] ?? []);
if (is_array($oldTrabajadores)) {
    foreach ($oldTrabajadores as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $trabajadoresSeleccionados[$tid] = true;
        }
    }
}

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

if (!function_exists('tk_form_datetime_local')) {
    function tk_form_datetime_local($value): string
    {
        $value = trim((string)($value ?? ''));
        if ($value === '') {
            return '';
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d\TH:i', $timestamp) : $value;
    }
}

if (!function_exists('tk_task_extra_value')) {
    function tk_task_extra_value(array $datos, string $campo, string $default = ''): string
    {
        return trim((string)($datos[$campo] ?? $default));
    }
}

if (!function_exists('tk_task_extra_workers')) {
    function tk_task_extra_workers(array $datos): array
    {
        $seleccionados = [];
        $raw = $datos['trabajador_ids'] ?? [];
        if (!is_array($raw)) {
            $raw = $raw !== '' && $raw !== null ? [$raw] : [];
        }

        foreach ($raw as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $seleccionados[$id] = true;
            }
        }

        return $seleccionados;
    }
}

if (!function_exists('tk_render_extra_task_section')) {
    function tk_render_extra_task_section($index, array $datos, array $categorias, array $prioridades, array $habitaciones, array $trabajadores): void
    {
        $index = (string)$index;
        $indexSafe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $index);
        $prefix = 'tareas_extra[' . $index . ']';
        $categoria = tk_task_extra_value($datos, 'categoria', 'general');
        $prioridad = tk_task_extra_value($datos, 'prioridad', 'media');
        $habitacion = tk_task_extra_value($datos, 'habitacion_id', '');
        $seleccionados = tk_task_extra_workers($datos);
        ?>
        <fieldset class="tk-extra-task" data-extra-task>
            <div class="tk-extra-top">
                <legend><span data-extra-task-title>Tarea adicional</span></legend>
                <button class="tk-extra-remove" type="button" data-remove-task>
                    <i class="fas fa-trash"></i>
                    <span>Quitar</span>
                </button>
            </div>

            <div class="tk-grid">
                <div class="tk-full">
                    <label for="titulo_extra_<?= tlm_safe($indexSafe) ?>">T&iacute;tulo <span class="tk-req">*</span></label>
                    <input class="tk-field" id="titulo_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[titulo]" type="text" maxlength="160" value="<?= tlm_safe(tk_task_extra_value($datos, 'titulo')) ?>" placeholder="Ej. Revisar aire acondicionado" data-extra-title>
                </div>

                <div>
                    <label for="categoria_extra_<?= tlm_safe($indexSafe) ?>">Categor&iacute;a</label>
                    <select class="tk-field" id="categoria_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[categoria]">
                        <?php foreach ($categorias as $key => $label): ?>
                            <option value="<?= tlm_safe($key) ?>" <?= $categoria === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="prioridad_extra_<?= tlm_safe($indexSafe) ?>">Prioridad</label>
                    <select class="tk-field" id="prioridad_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[prioridad]">
                        <?php foreach ($prioridades as $key => $label): ?>
                            <option value="<?= tlm_safe($key) ?>" <?= $prioridad === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="habitacion_extra_<?= tlm_safe($indexSafe) ?>">Habitaci&oacute;n relacionada</label>
                    <select class="tk-field" id="habitacion_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[habitacion_id]">
                        <option value="">Ninguna</option>
                        <?php foreach ($habitaciones as $habitacionRow): ?>
                            <?php $habitacionId = (int)($habitacionRow['id'] ?? 0); ?>
                            <option value="<?= $habitacionId ?>" <?= $habitacion === (string)$habitacionId ? 'selected' : '' ?>>
                                Hab. <?= tlm_safe($habitacionRow['numero'] ?? '') ?> - <?= tlm_safe($habitacionRow['estado'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="fecha_programada_extra_<?= tlm_safe($indexSafe) ?>">Fecha programada</label>
                    <div class="tk-date-row">
                        <input class="tk-field" id="fecha_programada_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[fecha_programada]" type="datetime-local" value="<?= tlm_safe(tk_form_datetime_local(tk_task_extra_value($datos, 'fecha_programada'))) ?>">
                        <button class="tk-now-btn" type="button" data-current-datetime-target="#fecha_programada_extra_<?= tlm_safe($indexSafe) ?>" aria-label="Poner fecha y hora actual en fecha programada">
                            <i class="fas fa-clock"></i>
                            <span>Ahora</span>
                        </button>
                    </div>
                </div>

                <div>
                    <label for="fecha_limite_extra_<?= tlm_safe($indexSafe) ?>">Fecha l&iacute;mite</label>
                    <div class="tk-date-row">
                        <input class="tk-field" id="fecha_limite_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[fecha_limite]" type="datetime-local" value="<?= tlm_safe(tk_form_datetime_local(tk_task_extra_value($datos, 'fecha_limite'))) ?>">
                        <button class="tk-now-btn" type="button" data-current-datetime-target="#fecha_limite_extra_<?= tlm_safe($indexSafe) ?>" aria-label="Poner fecha y hora actual en fecha limite">
                            <i class="fas fa-clock"></i>
                            <span>Ahora</span>
                        </button>
                    </div>
                </div>

                <div class="tk-full">
                    <label>Asignar trabajadores</label>
                    <?php if (empty($trabajadores)): ?>
                        <div class="tk-workers-empty">No hay trabajadores activos en este hotel.</div>
                    <?php else: ?>
                        <div class="tk-workers" role="group" aria-label="Trabajadores disponibles">
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                                <?php if ($trabajadorId <= 0) { continue; } ?>
                                <label class="tk-worker">
                                    <input type="checkbox" name="<?= tlm_safe($prefix) ?>[trabajador_ids][]" value="<?= $trabajadorId ?>"<?= isset($seleccionados[$trabajadorId]) ? ' checked' : '' ?>>
                                    <span class="tk-worker-name">
                                        <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorId)) ?>
                                        <?php if (!empty($trabajador['rol_laboral'])): ?>
                                            <span class="tk-worker-role"><?= tlm_safe($trabajador['rol_laboral']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="tk-full">
                    <label for="descripcion_extra_<?= tlm_safe($indexSafe) ?>">Descripci&oacute;n</label>
                    <textarea class="tk-field" id="descripcion_extra_<?= tlm_safe($indexSafe) ?>" name="<?= tlm_safe($prefix) ?>[descripcion]" maxlength="2000" placeholder="Detalles de lo que hay que hacer"><?= tlm_safe(tk_task_extra_value($datos, 'descripcion')) ?></textarea>
                </div>
            </div>
        </fieldset>
        <?php
    }
}

$categoriaSeleccionada = (string)old('categoria', (string)($valores['categoria'] ?? 'general'));
$prioridadSeleccionada = (string)old('prioridad', (string)($valores['prioridad'] ?? 'media'));
$habitacionSeleccionada = (string)old('habitacion_id', (string)($valores['habitacion_id'] ?? ''));
$fechaProgramadaValor = old('fecha_programada', tk_form_datetime_local($valores['fecha_programada'] ?? ''));
$fechaLimiteValor = old('fecha_limite', tk_form_datetime_local($valores['fecha_limite'] ?? ''));
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
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tk-form-page .tk-shell { display: grid; gap: 14px; max-width: 1040px; }
.tk-form-page.tk-form-page--capture {
    --tk-view-accent: var(--tk-success, #1E9E63);
    --tk-view-soft: color-mix(in srgb, var(--tk-view-accent) 10%, #FFFFFF);
    background:
        radial-gradient(880px 360px at 92% -8%, color-mix(in srgb, var(--tk-view-accent) 9%, transparent), transparent 62%),
        linear-gradient(180deg, var(--tk-ivory-2), var(--tk-ivory));
}
.tk-form-page.tk-form-page--capture .tk-title-lockup {
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--tk-view-accent) 18%, var(--tk-border));
    border-radius: 18px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--tk-view-accent) 7%, #fff), rgba(255,255,255,.74));
}
.tk-form-page.tk-form-page--capture .tk-hero-icon {
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.25), transparent 34%), linear-gradient(145deg, var(--tk-view-accent), var(--tk-brand) 56%, color-mix(in srgb, var(--tk-gold) 36%, var(--tk-brand)));
}
.tk-form-page .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.tk-form-page .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-form-page .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-form-page .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.tk-form-page .tk-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--tk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }
.tk-form-page .tk-view-chip { display: inline-flex; align-items: center; gap: 7px; width: fit-content; margin-top: 10px; padding: 6px 10px; border-radius: 999px; background: var(--tk-view-soft); border: 1px solid color-mix(in srgb, var(--tk-view-accent) 28%, #fff); color: color-mix(in srgb, var(--tk-view-accent) 76%, #000); font-size: .72rem; font-weight: 700; line-height: 1; }

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
.tk-form-page .tk-date-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: stretch; }
.tk-form-page .tk-date-row .tk-field { min-width: 0; }
.tk-form-page .tk-now-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 44px; padding: 0 12px; border: 1px solid var(--tk-border); border-radius: 11px; background: var(--tk-surface); color: var(--tk-muted); font-size: .82rem; font-weight: 700; line-height: 1; cursor: pointer; white-space: nowrap; transition: transform .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease; }
.tk-form-page .tk-now-btn:hover { transform: translateY(-1px); border-color: var(--tk-gold-line); color: var(--tk-gold-ink); box-shadow: 0 8px 18px -14px color-mix(in srgb, var(--tk-brand) 45%, transparent); }
.tk-form-page .tk-now-btn:focus-visible { outline: 3px solid var(--tk-ring); outline-offset: 2px; }

.tk-form-page .tk-workers { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 8px; max-height: 240px; overflow-y: auto; padding: 4px; border: 1px solid var(--tk-border); border-radius: 11px; background: var(--tk-surface-warm); }
.tk-form-page .tk-worker { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border: 1px solid var(--tk-border); border-radius: 10px; background: var(--tk-surface); cursor: pointer; transition: border-color .14s ease, box-shadow .14s ease, background .14s ease; }
.tk-form-page .tk-worker:hover { border-color: var(--tk-gold-line); }
.tk-form-page .tk-worker input { width: 17px; height: 17px; accent-color: var(--tk-gold); cursor: pointer; flex: 0 0 auto; }
.tk-form-page .tk-worker:has(input:checked) { border-color: var(--tk-gold); background: var(--tk-gold-soft); box-shadow: 0 0 0 2px var(--tk-ring); }
.tk-form-page .tk-worker-name { font-size: .85rem; font-weight: 700; color: var(--tk-text); line-height: 1.2; min-width: 0; }
.tk-form-page .tk-worker-role { display: block; font-size: .72rem; font-weight: 600; color: var(--tk-muted); }
.tk-form-page .tk-workers-empty { padding: 14px; border: 1px dashed var(--tk-border); border-radius: 11px; background: var(--tk-surface-warm); color: var(--tk-muted); font-size: .84rem; }

.tk-form-page .tk-multi-area { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--tk-border); }
.tk-form-page .tk-multi-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.tk-form-page .tk-multi-title { color: var(--tk-heading); font-weight: 700; font-size: .95rem; }
.tk-form-page .tk-multi-count { display: inline-flex; align-items: center; min-height: 30px; padding: 0 10px; border-radius: 999px; background: var(--tk-gold-soft); border: 1px solid var(--tk-gold-line); color: var(--tk-gold-ink); font-size: .74rem; font-weight: 700; white-space: nowrap; }
.tk-form-page .tk-extra-list { display: grid; gap: 12px; }
.tk-form-page .tk-extra-task { margin: 0; padding: 13px; border: 1px dashed var(--tk-gold-line); border-radius: 14px; background: color-mix(in srgb, var(--tk-gold) 5%, #fff); }
.tk-form-page .tk-extra-task legend { padding: 0; color: var(--tk-heading); font-size: .88rem; font-weight: 700; }
.tk-form-page .tk-extra-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.tk-form-page .tk-extra-remove { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 36px; padding: 0 10px; border: 1px solid color-mix(in srgb, #B4392B 28%, #fff); border-radius: 10px; background: #FFF7F6; color: #9E2A1D; font-size: .76rem; font-weight: 700; cursor: pointer; }
.tk-form-page .tk-extra-remove:hover { border-color: color-mix(in srgb, #B4392B 48%, #fff); }
.tk-form-page .tk-add-task { margin-top: 12px; }

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
    position: relative;
    overflow: hidden;
    padding: 18px;
}

.tk-form-page.tk-form-page--capture form.tk-panel::before {
    content: "";
    position: absolute;
    inset: 0 0 auto;
    height: 5px;
    background: linear-gradient(90deg, var(--tk-view-accent), color-mix(in srgb, var(--tk-gold) 70%, var(--tk-view-accent)));
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

    .tk-form-page .tk-date-row {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .tk-form-page .tk-now-btn {
        width: 100%;
        min-height: 38px;
        font-size: .7rem;
    }

    .tk-form-page .tk-multi-area {
        margin-top: 12px;
        padding-top: 12px;
    }

    .tk-form-page .tk-multi-head {
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .tk-form-page .tk-multi-title {
        font-size: .84rem;
    }

    .tk-form-page .tk-extra-task {
        padding: 10px;
        border-radius: 13px;
    }

    .tk-form-page .tk-extra-top {
        align-items: flex-start;
        margin-bottom: 8px;
    }

    .tk-form-page .tk-extra-remove {
        min-height: 34px;
        padding: 0 9px;
        font-size: .68rem;
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

<div class="tk-form-page tk-form-page--capture p-4 sm:p-6">
    <div class="tk-shell">
        <?php $back_arrow_href = back_url('tareas'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a class="tk-back ms-back-legacy" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>

        <section class="tk-title-lockup">
            <div class="tk-hero-icon"><i class="fas fa-list-check"></i></div>
            <div>
                <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                <h1 class="tk-title"><?= tlm_safe($formTitle) ?></h1>
                <p class="tk-subtitle">Registra o ajusta un pendiente de limpieza, mantenimiento o seguimiento general. Si eliges trabajadores, la tarea queda asignada y el primero queda como responsable principal.</p>
                <div class="tk-view-chip"><i class="fas fa-pen-to-square"></i> Mesa de captura</div>
            </div>
        </section>

        <form method="POST" action="<?= $formAction ?>" class="tk-panel p-5">
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
                    <div class="tk-date-row">
                        <input class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'fecha_programada') ?>" id="fecha_programada" name="fecha_programada" type="datetime-local" value="<?= tlm_safe($fechaProgramadaValor) ?>"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'fecha_programada', 'ms-form-error-tarea_fecha_programada') ?>>
                        <button class="tk-now-btn" type="button" data-current-datetime-target="#fecha_programada" aria-label="Poner fecha y hora actual en fecha programada">
                            <i class="fas fa-clock"></i>
                            <span>Ahora</span>
                        </button>
                    </div>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'fecha_programada') !== ''): ?>
                        <span id="ms-form-error-tarea_fecha_programada" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'fecha_programada') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="fecha_limite">Fecha l&iacute;mite</label>
                    <div class="tk-date-row">
                        <input class="tk-field<?= tk_create_form_error_class($tareaFormFieldErrors, 'fecha_limite') ?>" id="fecha_limite" name="fecha_limite" type="datetime-local" value="<?= tlm_safe($fechaLimiteValor) ?>"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'fecha_limite', 'ms-form-error-tarea_fecha_limite') ?>>
                        <button class="tk-now-btn" type="button" data-current-datetime-target="#fecha_limite" aria-label="Poner fecha y hora actual en fecha limite">
                            <i class="fas fa-clock"></i>
                            <span>Ahora</span>
                        </button>
                    </div>
                    <?php if (tk_create_form_error($tareaFormFieldErrors, 'fecha_limite') !== ''): ?>
                        <span id="ms-form-error-tarea_fecha_limite" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'fecha_limite') ?></span>
                    <?php endif; ?>
                </div>

                <div class="tk-full">
                    <label>Asignar trabajadores</label>
                    <?php if (empty($trabajadores)): ?>
                        <div class="tk-workers-empty">No hay trabajadores activos en este hotel. Puedes crear la tarea sin asignar y asignarla despu&eacute;s.</div>
                    <?php else: ?>
                        <div class="tk-workers<?= tk_create_form_error_class($tareaFormFieldErrors, 'trabajador_id') ?>" role="group" aria-label="Trabajadores disponibles"<?= tk_create_form_error_attrs($tareaFormFieldErrors, 'trabajador_id', 'ms-form-error-tarea_trabajador') ?>>
                            <?php foreach ($trabajadores as $trabajador): ?>
                                <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                                <?php if ($trabajadorId <= 0) { continue; } ?>
                                <label class="tk-worker">
                                    <input type="checkbox" name="trabajador_ids[]" value="<?= $trabajadorId ?>"<?= isset($trabajadoresSeleccionados[$trabajadorId]) ? ' checked' : '' ?>>
                                    <span class="tk-worker-name">
                                        <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorId)) ?>
                                        <?php if (!empty($trabajador['rol_laboral'])): ?>
                                            <span class="tk-worker-role"><?= tlm_safe($trabajador['rol_laboral']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (tk_create_form_error($tareaFormFieldErrors, 'trabajador_id') !== ''): ?>
                            <span id="ms-form-error-tarea_trabajador" class="tk-form-error ms-form-field-error"><?= tk_create_form_error($tareaFormFieldErrors, 'trabajador_id') ?></span>
                        <?php endif; ?>
                        <div class="tk-help">Opcional. Puedes elegir uno o varios. Si asignas al menos uno, la tarea nace en estado <strong>asignada</strong>; el primero queda como responsable principal.</div>
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

            <?php if (!$esEdicion): ?>
                <section class="tk-multi-area" data-extra-task-area data-next-index="<?= count($tareasExtraOld) ?>">
                    <div class="tk-multi-head">
                        <div class="tk-multi-title">Tareas adicionales</div>
                        <span class="tk-multi-count" data-task-count>1 tarea</span>
                    </div>

                    <div class="tk-extra-list" data-extra-task-list>
                        <?php foreach ($tareasExtraOld as $extraIndex => $extraTask): ?>
                            <?php tk_render_extra_task_section((string)$extraIndex, $extraTask, $categorias, $prioridades, $habitaciones, $trabajadores); ?>
                        <?php endforeach; ?>
                    </div>

                    <button class="tk-btn tk-btn-muted tk-add-task" type="button" data-add-task>
                        <i class="fas fa-plus"></i>
                        Agregar otra tarea
                    </button>
                </section>

                <template id="tkExtraTaskTemplate">
                    <?php tk_render_extra_task_section('__INDEX__', [], $categorias, $prioridades, $habitaciones, $trabajadores); ?>
                </template>
            <?php endif; ?>

            <div class="flex flex-wrap gap-3 mt-6">
                <button class="tk-btn tk-btn-gold" type="submit" data-task-submit><i class="fas fa-save"></i> <span data-task-submit-label><?= tlm_safe($submitLabel) ?></span></button>
                <a class="tk-btn tk-btn-muted" href="<?= $esEdicion && $tareaId > 0 ? url('tareas/' . $tareaId) : back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const currentDateTimeLocal = function () {
        const now = new Date();
        const local = new Date(now.getTime() - (now.getTimezoneOffset() * 60000));
        return local.toISOString().slice(0, 16);
    };

    document.addEventListener('click', function (event) {
        const button = event.target.closest('[data-current-datetime-target]');
        if (!button) return;

        let field = null;
        try {
            field = document.querySelector(button.getAttribute('data-current-datetime-target') || '');
        } catch (error) {
            field = null;
        }

        if (!field) return;

        field.value = currentDateTimeLocal();
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        field.focus({ preventScroll: true });
    });
})();
</script>

<?php if (!$esEdicion): ?>
<script>
(function () {
    const area = document.querySelector('[data-extra-task-area]');
    const template = document.getElementById('tkExtraTaskTemplate');
    if (!area || !template) return;

    const list = area.querySelector('[data-extra-task-list]');
    const addButton = area.querySelector('[data-add-task]');
    const countLabel = area.querySelector('[data-task-count]');
    const submitLabel = document.querySelector('[data-task-submit-label]');
    let nextIndex = parseInt(area.getAttribute('data-next-index') || '0', 10);
    if (!Number.isFinite(nextIndex) || nextIndex < 0) nextIndex = 0;

    const updateCount = function () {
        const total = 1 + list.querySelectorAll('[data-extra-task]').length;
        if (countLabel) countLabel.textContent = total === 1 ? '1 tarea' : total + ' tareas';
        if (submitLabel) submitLabel.textContent = total === 1 ? 'Crear tarea' : 'Crear ' + total + ' tareas';
        list.querySelectorAll('[data-extra-task-title]').forEach(function (title, index) {
            title.textContent = 'Tarea adicional ' + (index + 2);
        });
    };

    const addTask = function () {
        const holder = document.createElement('template');
        holder.innerHTML = template.innerHTML.replace(/__INDEX__/g, String(nextIndex)).trim();
        nextIndex += 1;

        const node = holder.content.firstElementChild;
        if (!node) return;

        list.appendChild(node);
        updateCount();

        const firstInput = node.querySelector('[data-extra-title]');
        if (firstInput) firstInput.focus();
    };

    addButton?.addEventListener('click', addTask);
    list.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-task]');
        if (!removeButton) return;

        const section = removeButton.closest('[data-extra-task]');
        if (section) {
            section.remove();
            updateCount();
        }
    });

    updateCount();
})();
</script>
<?php endif; ?>
