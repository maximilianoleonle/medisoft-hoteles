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

$usuarioSeleccionado = (int)($trabajador['usuario_id'] ?? 0);
?>

<style>
.worker-form-page {
    --trab-brand: var(--brand-primary, #1f3f46);
    --trab-accent: var(--brand-accent, #b58a3c);
    --trab-line: color-mix(in srgb, var(--trab-brand) 10%, #e5e7eb);
    color: #243142;
}
.worker-form-page .worker-form-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--trab-brand) 92%, #111827), color-mix(in srgb, var(--trab-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.worker-form-page .worker-form-panel {
    border: 1px solid var(--trab-line);
    background: rgba(255,255,255,.92);
}
.worker-form-page .worker-input,
.worker-form-page .worker-textarea {
    width: 100%;
    border: 1px solid var(--trab-line);
    background: #fff;
    padding: 10px 12px;
}
.worker-form-page .worker-input {
    min-height: 42px;
}
.worker-form-page .worker-textarea {
    min-height: 92px;
    resize: vertical;
}
.worker-form-page label {
    display: block;
    font-size: .78rem;
    font-weight: 900;
    color: #475569;
    margin-bottom: 6px;
}
.worker-form-page .worker-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 15px;
    border: 1px solid var(--trab-line);
    font-weight: 900;
}
.worker-form-page .worker-btn-primary {
    background: var(--trab-brand);
    border-color: var(--trab-brand);
    color: #fff;
}
.worker-form-page .worker-btn-muted {
    background: #fff;
    color: #334155;
}
</style>

<div class="worker-form-page">
    <section class="worker-form-hero">
        <div class="text-xs uppercase tracking-widest opacity-75 font-black">Personal</div>
        <h1 class="text-2xl md:text-3xl font-black mt-2"><?= $esEditar ? 'Editar trabajador' : 'Nuevo trabajador' ?></h1>
        <p class="mt-2 text-white/80 max-w-3xl">
            Informacion laboral basica por hotel. Esta fase no registra pagos, anticipos, prestamos, asistencia ni Caja.
        </p>
    </section>

    <section class="p-6">
        <form method="POST" action="<?= $action ?>" class="worker-form-panel p-5 max-w-5xl">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="nombre_completo">Nombre completo *</label>
                    <input class="worker-input" id="nombre_completo" name="nombre_completo" type="text" maxlength="150" required
                           value="<?= trab_form_safe($trabajador['nombre_completo'] ?? '') ?>">
                </div>

                <div>
                    <label for="usuario_id">Usuario vinculado</label>
                    <select class="worker-input" id="usuario_id" name="usuario_id">
                        <option value="">Sin usuario del sistema</option>
                        <?php foreach ($usuariosVinculables as $usuario): ?>
                            <?php $usuarioId = (int)($usuario['id'] ?? 0); ?>
                            <option value="<?= $usuarioId ?>" <?= $usuarioId === $usuarioSeleccionado ? 'selected' : '' ?>>
                                <?= trab_form_safe(($usuario['nombre_completo'] ?? '') . ' (' . ($usuario['nombre_usuario'] ?? '') . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="identificacion">Identificacion</label>
                    <input class="worker-input" id="identificacion" name="identificacion" type="text" maxlength="60"
                           value="<?= trab_form_safe($trabajador['identificacion'] ?? '') ?>">
                </div>

                <div>
                    <label for="rol_laboral">Rol laboral</label>
                    <input class="worker-input" id="rol_laboral" name="rol_laboral" type="text" maxlength="80"
                           value="<?= trab_form_safe($trabajador['rol_laboral'] ?? '') ?>">
                </div>

                <div>
                    <label for="telefono">Telefono</label>
                    <input class="worker-input" id="telefono" name="telefono" type="text" maxlength="30"
                           value="<?= trab_form_safe($trabajador['telefono'] ?? '') ?>">
                </div>

                <div>
                    <label for="email">Correo</label>
                    <input class="worker-input" id="email" name="email" type="email" maxlength="120"
                           value="<?= trab_form_safe($trabajador['email'] ?? '') ?>">
                </div>

                <div>
                    <label for="fecha_alta">Fecha de alta</label>
                    <input class="worker-input" id="fecha_alta" name="fecha_alta" type="date"
                           value="<?= trab_form_safe($trabajador['fecha_alta'] ?? '') ?>">
                </div>

                <div>
                    <label for="periodicidad_pago">Periodicidad de referencia</label>
                    <select class="worker-input" id="periodicidad_pago" name="periodicidad_pago">
                        <?php $periodicidad = (string)($trabajador['periodicidad_pago'] ?? ''); ?>
                        <option value="">Sin periodicidad</option>
                        <option value="semanal" <?= $periodicidad === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="quincenal" <?= $periodicidad === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                        <option value="mensual" <?= $periodicidad === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="por_evento" <?= $periodicidad === 'por_evento' ? 'selected' : '' ?>>Por evento</option>
                    </select>
                </div>

                <div>
                    <label for="salario_base">Salario base de referencia</label>
                    <input class="worker-input" id="salario_base" name="salario_base" type="number" min="0" step="0.01"
                           value="<?= trab_form_safe($trabajador['salario_base'] ?? '') ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="notas">Notas internas</label>
                    <textarea class="worker-textarea" id="notas" name="notas" maxlength="1000"><?= trab_form_safe($trabajador['notas'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mt-5">
                <button class="worker-btn worker-btn-primary" type="submit">
                    <i class="fas fa-save"></i>
                    Guardar
                </button>
                <a class="worker-btn worker-btn-muted" href="<?= $esEditar ? url('trabajadores/' . $trabajadorId) : url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
            </div>
        </form>
    </section>
</div>
