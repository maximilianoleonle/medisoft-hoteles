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

$categoriaSeleccionada = (string)($valores['categoria'] ?? 'general');
$prioridadSeleccionada = (string)($valores['prioridad'] ?? 'media');

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
.tlm-form-page{padding:24px;max-width:1080px;margin:0 auto;color:#172033}
.tlm-back{display:inline-flex;align-items:center;gap:8px;color:#475569;text-decoration:none;font-weight:800;margin-bottom:18px}
.tlm-form-hero{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:22px;box-shadow:0 10px 28px rgba(15,23,42,.06);margin-bottom:18px}
.tlm-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.tlm-title{font-size:30px;line-height:1.1;margin:6px 0 10px;font-weight:900;color:#111827}
.tlm-subtitle{color:#64748b;margin:0;max-width:720px}
.tlm-form-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 8px 22px rgba(15,23,42,.05);padding:20px}
.tlm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.tlm-form-field--full{grid-column:1/-1}
.tlm-label{display:block;font-size:12px;text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-weight:900;margin-bottom:7px}
.tlm-input,.tlm-select,.tlm-textarea{width:100%;border:1px solid #d1d5db;border-radius:7px;background:#fff;color:#172033;padding:10px 11px;font-size:14px}
.tlm-input,.tlm-select{min-height:42px}
.tlm-textarea{min-height:118px;resize:vertical}
.tlm-help{font-size:13px;color:#64748b;margin-top:6px}
.tlm-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px}
.tlm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:7px;background:#172033;color:#fff;font-weight:900;padding:10px 16px;text-decoration:none;min-height:40px;cursor:pointer}
.tlm-btn--light{background:#fff;color:#172033;border:1px solid #d1d5db}
.tlm-note{margin-top:18px;padding:12px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:8px;color:#475569;font-size:13px}
@media (max-width:800px){.tlm-form-grid{grid-template-columns:1fr}.tlm-title{font-size:24px}}
</style>

<div class="tlm-form-page">
    <a class="tlm-back" href="<?= url('tareas') ?>">
        <i class="fas fa-arrow-left"></i>
        Volver a tareas
    </a>

    <section class="tlm-form-hero">
        <div class="tlm-kicker">Operaciones</div>
        <h1 class="tlm-title">Nueva tarea operativa</h1>
        <p class="tlm-subtitle">
            Alta manual para pendientes de limpieza, mantenimiento o seguimiento general. Esta accion no cambia estados de habitaciones, no asigna personal y no toca Caja.
        </p>
    </section>

    <form method="POST" action="<?= url('tareas') ?>" class="tlm-form-card">
        <?= csrf_field() ?>

        <div class="tlm-form-grid">
            <div class="tlm-form-field--full">
                <label class="tlm-label" for="titulo">Titulo *</label>
                <input class="tlm-input" id="titulo" name="titulo" type="text" maxlength="160" required>
            </div>

            <div>
                <label class="tlm-label" for="categoria">Categoria</label>
                <select class="tlm-select" id="categoria" name="categoria">
                    <?php foreach ($categorias as $key => $label): ?>
                        <option value="<?= tlm_safe($key) ?>" <?= $categoriaSeleccionada === $key ? 'selected' : '' ?>>
                            <?= tlm_safe($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="tlm-label" for="prioridad">Prioridad</label>
                <select class="tlm-select" id="prioridad" name="prioridad">
                    <?php foreach ($prioridades as $key => $label): ?>
                        <option value="<?= tlm_safe($key) ?>" <?= $prioridadSeleccionada === $key ? 'selected' : '' ?>>
                            <?= tlm_safe($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="tlm-label" for="habitacion_id">Habitacion vinculada</label>
                <select class="tlm-select" id="habitacion_id" name="habitacion_id">
                    <option value="">Sin habitacion</option>
                    <?php foreach ($habitaciones as $habitacion): ?>
                        <option value="<?= (int)($habitacion['id'] ?? 0) ?>">
                            Hab. <?= tlm_safe($habitacion['numero'] ?? '') ?> - <?= tlm_safe($habitacion['estado'] ?? '-') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="tlm-help">Solo se permiten habitaciones activas del hotel actual.</div>
            </div>

            <div>
                <label class="tlm-label" for="fecha_programada">Fecha programada</label>
                <input class="tlm-input" id="fecha_programada" name="fecha_programada" type="datetime-local">
            </div>

            <div>
                <label class="tlm-label" for="fecha_limite">Fecha limite</label>
                <input class="tlm-input" id="fecha_limite" name="fecha_limite" type="datetime-local">
            </div>

            <div class="tlm-form-field--full">
                <label class="tlm-label" for="descripcion">Descripcion</label>
                <textarea class="tlm-textarea" id="descripcion" name="descripcion" maxlength="2000"></textarea>
            </div>
        </div>

        <div class="tlm-note">
            La tarea se crea en estado pendiente y registra un evento inicial. La asignacion, inicio, cierre y mantenimiento avanzado quedan para fases posteriores.
        </div>

        <div class="tlm-actions">
            <button class="tlm-btn" type="submit">
                <i class="fas fa-save"></i>
                Crear tarea
            </button>
            <a class="tlm-btn tlm-btn--light" href="<?= url('tareas') ?>">
                <i class="fas fa-arrow-left"></i>
                Cancelar
            </a>
        </div>
    </form>
</div>
