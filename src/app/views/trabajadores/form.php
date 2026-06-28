<?php
$modo = $modo ?? 'crear';
$trabajador = $trabajador ?? [];
$usuariosVinculables = $usuariosVinculables ?? [];
$esEditar = $modo === 'editar';
$trabajadorId = (int)($trabajador['id'] ?? 0);
$action = $esEditar
    ? url('trabajadores/' . $trabajadorId . '/actualizar')
    : url('trabajadores');

if (!function_exists('trab_form_safe')) {
    function trab_form_safe($value, $fallback = '')
    {
        $text = (string)($value ?? $fallback);
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

$trabajadorFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

if (!function_exists('trab_form_error')) {
    function trab_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? trab_form_safe($message) : '';
    }
}

if (!function_exists('trab_form_error_class')) {
    function trab_form_error_class(array $errors, string $field): string
    {
        return trab_form_error($errors, $field) !== '' ? ' wk-field-error' : '';
    }
}

if (!function_exists('trab_form_error_attrs')) {
    function trab_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (trab_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . trab_form_safe($errorId) . '"';
    }
}

$usuarioSeleccionado = (int) old('usuario_id', (string)($trabajador['usuario_id'] ?? 0));
$periodicidad = (string) old('periodicidad_pago', (string)($trabajador['periodicidad_pago'] ?? ''));
$valoresFormulario = [
    'nombre_completo' => old('nombre_completo', trab_form_safe($trabajador['nombre_completo'] ?? '')),
    'identificacion' => old('identificacion', trab_form_safe($trabajador['identificacion'] ?? '')),
    'rol_laboral' => old('rol_laboral', trab_form_safe($trabajador['rol_laboral'] ?? '')),
    'telefono' => old('telefono', trab_form_safe($trabajador['telefono'] ?? '')),
    'email' => old('email', trab_form_safe($trabajador['email'] ?? '')),
    'fecha_alta' => old('fecha_alta', trab_form_safe($trabajador['fecha_alta'] ?? '')),
    'salario_base' => old('salario_base', trab_form_safe($trabajador['salario_base'] ?? '')),
    'notas' => old('notas', trab_form_safe($trabajador['notas'] ?? '')),
];
?>

<style>
.worker-form-page {
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 72%, #000);
    --wk-ivory: #F6F2EA; --wk-ivory-2: #FBF8F2;
    --wk-surface: #FFFFFF; --wk-surface-warm: #FCFAF5;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --wk-text: #171717; --wk-muted: #667085; --wk-heading: #111827;
    --wk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--wk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--wk-ivory-2), var(--wk-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.worker-form-page .wk-shell { display: grid; gap: 14px; max-width: 1040px; }
.worker-form-page .wk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.worker-form-page .wk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.worker-form-page .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.worker-form-page .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 700; font-size: clamp(2rem, 3.4vw, 2.7rem); line-height: 1; }
.worker-form-page .wk-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--wk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.worker-form-page .wk-panel { background: var(--wk-surface); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.worker-form-page label { display: block; font-size: .74rem; font-weight: 700; color: var(--wk-muted); text-transform: uppercase; letter-spacing: .045em; margin-bottom: 6px; }
.worker-form-page .wk-req { color: var(--wk-gold-ink); }
.worker-form-page .wk-input, .worker-form-page .wk-textarea {
    width: 100%; min-height: 44px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 11px 13px;
    color: var(--wk-text); font-weight: 600; font-size: .9rem; font-family: var(--wk-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.worker-form-page textarea.wk-textarea { min-height: 96px; resize: vertical; }
.worker-form-page select.wk-input { cursor: pointer; }
.worker-form-page .wk-input:focus, .worker-form-page .wk-textarea:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; background: #fff; }
.worker-form-page .wk-hint { margin-top: 6px; font-size: .74rem; color: var(--wk-muted); font-weight: 600; }
.worker-form-page .wk-field-error { border-color: #B4392B; background: #FFF7F6; }
.worker-form-page .wk-form-error { display: block; margin-top: 7px; color: #B4392B; font-size: .78rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.worker-form-page .wk-error-summary { margin-bottom: 16px; padding: 12px 14px; border: 1px solid #F0B8AE; border-radius: 12px; background: #FFF7F6; color: #9E2A1D; font-size: .86rem; font-weight: 700; }

.worker-form-page .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 20px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .9rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease; }
.worker-form-page .wk-btn:hover { transform: translateY(-1px); }
.worker-form-page .wk-btn-gold { background: linear-gradient(135deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--wk-gold) 58%, transparent); }
.worker-form-page .wk-btn-muted { background: var(--wk-surface); border-color: var(--wk-border); color: var(--wk-muted); }
.worker-form-page .wk-back { display: inline-flex; align-items: center; gap: 8px; color: var(--wk-muted); text-decoration: none; font-weight: 700; font-size: .85rem; }
.worker-form-page .wk-back:hover { color: var(--wk-gold-ink); }

.worker-form-page .wk-date-row { display: flex; align-items: stretch; gap: 8px; }
.worker-form-page .wk-date-row .wk-input { flex: 1 1 auto; min-width: 0; }
.worker-form-page .wk-today-btn { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; min-height: 44px; padding: 0 15px;
    border-radius: 11px; border: 1px solid color-mix(in srgb, var(--wk-gold) 40%, #fff); background: color-mix(in srgb, var(--wk-gold) 12%, #fff);
    color: var(--wk-gold-ink); font-family: var(--wk-sans); font-weight: 700; font-size: .82rem; line-height: 1; cursor: pointer; white-space: nowrap;
    transition: background .16s ease, border-color .16s ease, transform .16s ease; }
.worker-form-page .wk-today-btn:hover { background: color-mix(in srgb, var(--wk-gold) 20%, #fff); border-color: var(--wk-gold); transform: translateY(-1px); }
.worker-form-page .wk-today-btn:active { transform: translateY(0); }
.worker-form-page .wk-today-btn i { font-size: .78rem; }
</style>

<div class="worker-form-page p-4 sm:p-6">
    <div class="wk-shell">
        <a class="wk-back" href="<?= back_url($esEditar ? 'trabajadores/' . $trabajadorId : 'trabajadores') ?>"><i class="fas fa-arrow-left"></i> Volver</a>

        <section class="wk-title-lockup">
            <div class="wk-hero-icon"><i class="fas fa-user-plus"></i></div>
            <div>
                <p class="wk-kicker">Personal del hotel</p>
                <h1 class="wk-title"><?= $esEditar ? 'Editar trabajador' : 'Nuevo trabajador' ?></h1>
                <p class="wk-subtitle">Datos b&aacute;sicos de la persona. El salario y la periodicidad son de referencia; los pagos se registran despu&eacute;s desde su ficha.</p>
            </div>
        </section>

        <form method="POST" action="<?= $action ?>" class="wk-panel p-5">
            <?= csrf_field() ?>

            <?php if (trab_form_error($trabajadorFieldErrors, '_global') !== ''): ?>
                <div class="wk-error-summary ms-form-error-summary" role="alert">
                    <?= trab_form_error($trabajadorFieldErrors, '_global') ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre_completo">Nombre completo <span class="wk-req">*</span></label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'nombre_completo') ?>" id="nombre_completo" name="nombre_completo" type="text" maxlength="150" required value="<?= $valoresFormulario['nombre_completo'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'nombre_completo', 'ms-form-error-trab_nombre_completo') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'nombre_completo') !== ''): ?>
                        <span id="ms-form-error-trab_nombre_completo" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'nombre_completo') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="usuario_id">Usuario del sistema</label>
                    <select class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'usuario_id') ?>" id="usuario_id" name="usuario_id"<?= trab_form_error_attrs($trabajadorFieldErrors, 'usuario_id', 'ms-form-error-trab_usuario_id') ?>>
                        <option value="">Sin usuario del sistema</option>
                        <?php foreach ($usuariosVinculables as $usuario): ?>
                            <?php $usuarioId = (int)($usuario['id'] ?? 0); ?>
                            <option value="<?= $usuarioId ?>" <?= $usuarioId === $usuarioSeleccionado ? 'selected' : '' ?>>
                                <?= trab_form_safe(($usuario['nombre_completo'] ?? '') . ' (' . ($usuario['nombre_usuario'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="wk-hint">Opcional. S&oacute;lo si esta persona tambi&eacute;n entra al sistema.</p>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'usuario_id') !== ''): ?>
                        <span id="ms-form-error-trab_usuario_id" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'usuario_id') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="identificacion">Identificaci&oacute;n</label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'identificacion') ?>" id="identificacion" name="identificacion" type="text" maxlength="60" placeholder="N&uacute;mero de empleado, INE, etc." value="<?= $valoresFormulario['identificacion'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'identificacion', 'ms-form-error-trab_identificacion') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'identificacion') !== ''): ?>
                        <span id="ms-form-error-trab_identificacion" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'identificacion') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="rol_laboral">Rol o puesto</label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'rol_laboral') ?>" id="rol_laboral" name="rol_laboral" type="text" maxlength="80" placeholder="Ej. Recepci&oacute;n, Limpieza" value="<?= $valoresFormulario['rol_laboral'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'rol_laboral', 'ms-form-error-trab_rol_laboral') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'rol_laboral') !== ''): ?>
                        <span id="ms-form-error-trab_rol_laboral" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'rol_laboral') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="telefono">Tel&eacute;fono</label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'telefono') ?>" id="telefono" name="telefono" type="tel" inputmode="numeric" maxlength="15" data-max-digits="15" placeholder="Solo n&uacute;meros" value="<?= $valoresFormulario['telefono'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'telefono', 'ms-form-error-trab_telefono') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'telefono') !== ''): ?>
                        <span id="ms-form-error-trab_telefono" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'telefono') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="email">Correo</label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'email') ?>" id="email" name="email" type="email" maxlength="120" placeholder="correo@ejemplo.com" value="<?= $valoresFormulario['email'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'email', 'ms-form-error-trab_email') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'email') !== ''): ?>
                        <span id="ms-form-error-trab_email" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'email') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="fecha_alta">Fecha de alta</label>
                    <div class="wk-date-row">
                        <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'fecha_alta') ?>" id="fecha_alta" name="fecha_alta" type="date" value="<?= $valoresFormulario['fecha_alta'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'fecha_alta', 'ms-form-error-trab_fecha_alta') ?>>
                        <button type="button" class="wk-today-btn" data-today-for="fecha_alta" aria-label="Poner la fecha de hoy"><i class="fas fa-calendar-day" aria-hidden="true"></i> Hoy</button>
                    </div>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'fecha_alta') !== ''): ?>
                        <span id="ms-form-error-trab_fecha_alta" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'fecha_alta') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="periodicidad_pago">Cada cu&aacute;ndo se le paga</label>
                    <select class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'periodicidad_pago') ?>" id="periodicidad_pago" name="periodicidad_pago"<?= trab_form_error_attrs($trabajadorFieldErrors, 'periodicidad_pago', 'ms-form-error-trab_periodicidad_pago') ?>>
                        <option value="">Sin definir</option>
                        <option value="semanal" <?= $periodicidad === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="quincenal" <?= $periodicidad === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                        <option value="mensual" <?= $periodicidad === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="por_evento" <?= $periodicidad === 'por_evento' ? 'selected' : '' ?>>Por evento</option>
                    </select>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'periodicidad_pago') !== ''): ?>
                        <span id="ms-form-error-trab_periodicidad_pago" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'periodicidad_pago') ?></span>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="salario_base">Salario base de referencia</label>
                    <input class="wk-input<?= trab_form_error_class($trabajadorFieldErrors, 'salario_base') ?>" id="salario_base" name="salario_base" type="number" data-money-format="true" min="0" step="0.01" placeholder="0.00" value="<?= $valoresFormulario['salario_base'] ?>"<?= trab_form_error_attrs($trabajadorFieldErrors, 'salario_base', 'ms-form-error-trab_salario_base') ?>>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'salario_base') !== ''): ?>
                        <span id="ms-form-error-trab_salario_base" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'salario_base') ?></span>
                    <?php endif; ?>
                </div>

                <div class="md:col-span-2">
                    <label for="notas">Notas internas</label>
                    <textarea class="wk-textarea<?= trab_form_error_class($trabajadorFieldErrors, 'notas') ?>" id="notas" name="notas" maxlength="1000" placeholder="Lo que quieras recordar de esta persona"<?= trab_form_error_attrs($trabajadorFieldErrors, 'notas', 'ms-form-error-trab_notas') ?>><?= $valoresFormulario['notas'] ?></textarea>
                    <?php if (trab_form_error($trabajadorFieldErrors, 'notas') !== ''): ?>
                        <span id="ms-form-error-trab_notas" class="wk-form-error ms-form-field-error"><?= trab_form_error($trabajadorFieldErrors, 'notas') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-6">
                <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-save"></i> <?= $esEditar ? 'Guardar cambios' : 'Guardar trabajador' ?></button>
                <a class="wk-btn wk-btn-muted" href="<?= back_url($esEditar ? 'trabajadores/' . $trabajadorId : 'trabajadores') ?>"><i class="fas fa-arrow-left"></i> Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
// Limita los campos de telefono a solo digitos con tope de longitud
document.querySelectorAll('input[type="tel"][data-max-digits]').forEach(function(input) {
    input.addEventListener('input', function(e) {
        const maxDigits = parseInt(e.target.dataset.maxDigits || '0', 10);
        let value = e.target.value.replace(/\D/g, '');
        if (maxDigits > 0 && value.length > maxDigits) {
            value = value.slice(0, maxDigits);
        }
        e.target.value = value;
    });
});

// Acceso rapido "Hoy": fija la fecha actual (hora local, sin desfase de zona) en el campo enlazado
document.querySelectorAll('[data-today-for]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = document.getElementById(btn.dataset.todayFor);
        if (!input) return;
        var now = new Date();
        var local = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
        input.value = local.toISOString().slice(0, 10);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        input.focus();
    });
});
</script>
