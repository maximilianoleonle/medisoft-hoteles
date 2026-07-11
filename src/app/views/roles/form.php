<?php
/**
 * Form de creación/edición de rol con matriz de permisos.
 * Recibe: $rol, $catalogo, $permisosActuales, $modulosActivos, $esAccesoTotal, $action
 */
$rol = $rol ?? null;
$catalogo = $catalogo ?? [];
$esAccesoTotal = $esAccesoTotal ?? false;
$modulosActivos = $modulosActivos ?? [];
$filtrarModulos = !empty($modulosActivos);

$marcados = old('permisos', null);
if (!is_array($marcados)) {
    $marcados = $permisosActuales ?? [];
}
$estaMarcado = static function ($perm) use ($marcados) {
    return in_array($perm, $marcados, true);
};
$nombreVal = old('nombre', $rol['nombre'] ?? '');
$descVal = old('descripcion', $rol['descripcion'] ?? '');
$esSistema = $rol ? !empty($rol['es_sistema']) : false;
?>

<style>
.rolf-view {
    --rf-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --rf-on-brand: var(--brand-action-text, #FFFEFB);
    --rf-accent: var(--brand-accent, #BD9441);
    --rf-accent-dark: color-mix(in srgb, var(--rf-accent) 72%, #3F2E12);
    --rf-accent-soft: color-mix(in srgb, var(--rf-accent) 13%, #FFFFFF);
    --rf-text: var(--brand-text, #1B2746);
    --rf-muted: var(--brand-muted, #6C7689);
    --rf-surface: #FFFFFF;
    --rf-bg: #F7F4EE;
    --rf-border: color-mix(in srgb, var(--rf-brand) 9%, #E7E1D4);
    --rf-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rf-sans: 'Manrope', -apple-system, 'Segoe UI', sans-serif;
    font-family: var(--rf-sans);
    color: var(--rf-text);
    min-height: 100vh;
    background: radial-gradient(circle at 8% 0%, var(--rf-accent-soft) 0, transparent 24%), linear-gradient(180deg, #FBFAF6, var(--rf-bg)) !important;
}
.rolf-view .rf-wrap { max-width: 980px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
.rolf-view .rf-back { display: inline-flex; align-items: center; gap: .4rem; color: var(--rf-muted); font-size: .82rem; font-weight: 600; text-decoration: none; margin-bottom: .85rem; }
.rolf-view .rf-back:hover { color: var(--rf-brand); }
.rolf-view h1 { font-family: var(--rf-serif); font-size: clamp(1.8rem, 3vw, 2.35rem); font-weight: 700; line-height: 1; color: var(--rf-brand); }
.rolf-view .rf-sub { color: var(--rf-muted); font-size: .88rem; margin: .35rem 0 1.5rem; font-weight: 500; }
.rolf-view .rf-panel { background: var(--rf-surface); border: 1px solid var(--rf-border); border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 16px 34px -30px rgba(27,39,70,.4); }
.rolf-view .rf-panel h2 { font-family: var(--rf-serif); font-size: 1.3rem; font-weight: 700; color: var(--rf-brand); margin-bottom: .25rem; }
.rolf-view .rf-panel .rf-hint { color: var(--rf-muted); font-size: .8rem; margin-bottom: 1rem; }
.rolf-view label.rf-flabel { display: block; font-size: .76rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--rf-muted); margin-bottom: .35rem; }
.rolf-view .rf-input { width: 100%; padding: .65rem .8rem; border: 1px solid var(--rf-border); border-radius: 10px; font-size: .9rem; font-family: inherit; color: var(--rf-text); background: #FCFAF5; transition: border-color .16s ease, box-shadow .16s ease; }
.rolf-view .rf-input:focus { outline: none; border-color: var(--rf-accent); box-shadow: 0 0 0 3px var(--rf-accent-soft); }
.rolf-view .rf-input[readonly] { background: #F1EFEA; color: var(--rf-muted); cursor: not-allowed; }
.rolf-view .rf-field { margin-bottom: 1rem; }
.rolf-view .rf-notice { display: flex; gap: .65rem; align-items: flex-start; padding: 14px 16px; border-radius: 12px; background: var(--rf-accent-soft); border: 1px solid color-mix(in srgb, var(--rf-accent) 28%, #FFF); color: var(--rf-accent-dark); font-size: .85rem; line-height: 1.5; }
.rolf-view .rf-notice i { margin-top: 2px; }
.rolf-view .rf-groups { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.rolf-view .rf-group { border: 1px solid var(--rf-border); border-radius: 14px; padding: 14px 15px; background: #FCFAF5; }
.rolf-view .rf-group.is-locked { opacity: .62; }
.rolf-view .rf-ghead { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
.rolf-view .rf-gtitle { font-weight: 700; font-size: .95rem; color: var(--rf-brand); }
.rolf-view .rf-gmod { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: #B42318; background: #FDECEC; border: 1px solid #F3C6C2; padding: 2px 7px; border-radius: 999px; }
.rolf-view .rf-gtoggle { font-size: .72rem; font-weight: 700; color: var(--rf-accent-dark); background: none; border: none; cursor: pointer; padding: 0; }
.rolf-view .rf-gtoggle:hover { text-decoration: underline; }
.rolf-view .rf-perm { display: flex; align-items: flex-start; gap: .55rem; padding: .35rem 0; cursor: pointer; }
.rolf-view .rf-perm input { margin-top: .2rem; width: 16px; height: 16px; accent-color: var(--rf-accent); cursor: pointer; flex-shrink: 0; }
.rolf-view .rf-perm span { font-size: .84rem; line-height: 1.3; }
.rolf-view .rf-perm .rf-ptype { display: inline-block; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-left: .35rem; padding: 1px 5px; border-radius: 4px; vertical-align: middle; }
.rolf-view .rf-ptype-accion { background: #FFF3E0; color: #B45309; }
.rolf-view .rf-ptype-wild { background: #EAF1F8; color: #234C78; }
.rolf-view .rf-actions { display: flex; gap: .6rem; justify-content: flex-end; margin-top: 1.25rem; }
.rolf-view .rf-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: 0 1.2rem; border-radius: 11px; font-size: .88rem; font-weight: 700; text-decoration: none; border: 1px solid var(--rf-border); cursor: pointer; transition: transform .16s ease; }
.rolf-view .rf-btn:hover { transform: translateY(-1px); }
.rolf-view .rf-btn-ghost { background: var(--rf-surface); color: var(--rf-text); }
.rolf-view .rf-btn-primary { background: var(--rf-brand); color: var(--rf-on-brand); border-color: var(--rf-brand); box-shadow: 0 12px 22px -16px color-mix(in srgb, var(--rf-brand) 80%, #000); }
.rolf-view .rf-btn-primary:hover { color: var(--rf-on-brand); }
@media (max-width: 640px) { .rolf-view .rf-groups { grid-template-columns: 1fr; } .rolf-view .rf-actions { flex-direction: column-reverse; } .rolf-view .rf-btn { width: 100%; justify-content: center; } }
</style>

<div class="rolf-view">
    <div class="rf-wrap">
        <a href="<?= url('configuracion/roles') ?>" class="rf-back"><i class="fas fa-arrow-left"></i> Volver a roles</a>
        <h1><?= $rol ? 'Editar rol' : 'Nuevo rol' ?></h1>
        <p class="rf-sub">
            <?php if ($esAccesoTotal): ?>
                Este es un rol base con acceso total. Puedes ajustar su nombre y descripción.
            <?php elseif ($esSistema): ?>
                Rol base del sistema. Puedes reconfigurar sus permisos, pero no eliminarlo.
            <?php else: ?>
                Define el nombre del rol y marca exactamente a qué puede acceder.
            <?php endif; ?>
        </p>

        <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
            <?= csrf_field() ?>

            <div class="rf-panel">
                <div class="rf-field">
                    <label class="rf-flabel" for="rol-nombre">Nombre del rol</label>
                    <input type="text" id="rol-nombre" name="nombre" class="rf-input" maxlength="120" required
                           value="<?= htmlspecialchars($nombreVal, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ej. Cajero, Limpieza, Supervisor de turno">
                </div>
                <div class="rf-field" style="margin-bottom:0;">
                    <label class="rf-flabel" for="rol-desc">Descripción <span style="text-transform:none;font-weight:500;">(opcional)</span></label>
                    <input type="text" id="rol-desc" name="descripcion" class="rf-input" maxlength="255"
                           value="<?= htmlspecialchars($descVal, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Para qué sirve este rol">
                </div>
            </div>

            <?php if ($esAccesoTotal): ?>
                <div class="rf-panel">
                    <div class="rf-notice">
                        <i class="fas fa-shield-halved"></i>
                        <span><strong>Acceso total.</strong> Este rol puede ver y hacer todo en el hotel, incluida la gestión de roles. Sus permisos no se editan para evitar bloqueos accidentales.</span>
                    </div>
                </div>
            <?php else: ?>
                <div class="rf-panel">
                    <h2>Permisos</h2>
                    <p class="rf-hint">Marca el acceso a cada área. Las acciones sensibles (cortes, ajustes, eliminar) se controlan por separado.</p>

                    <div class="rf-groups">
                        <?php foreach ($catalogo as $clave => $grupo): ?>
                            <?php
                            $moduloRequerido = $grupo['modulo'] ?? null;
                            $locked = $filtrarModulos && $moduloRequerido && !in_array($moduloRequerido, $modulosActivos, true);
                            ?>
                            <div class="rf-group <?= $locked ? 'is-locked' : '' ?>" data-group="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="rf-ghead">
                                    <span class="rf-gtitle"><?= htmlspecialchars($grupo['label'] ?? $clave, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($locked): ?>
                                        <span class="rf-gmod" title="El hotel no tiene contratado este módulo">Sin módulo</span>
                                    <?php else: ?>
                                        <button type="button" class="rf-gtoggle" data-toggle-group>Todos</button>
                                    <?php endif; ?>
                                </div>
                                <?php foreach ($grupo['permisos'] as $permKey => $permDef): ?>
                                    <?php $tipo = $permDef['tipo'] ?? 'accion'; ?>
                                    <label class="rf-perm">
                                        <input type="checkbox" name="permisos[]"
                                               value="<?= htmlspecialchars($permKey, ENT_QUOTES, 'UTF-8') ?>"
                                               <?= $estaMarcado($permKey) ? 'checked' : '' ?>>
                                        <span>
                                            <?= htmlspecialchars($permDef['label'] ?? $permKey, ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($tipo === 'accion'): ?>
                                                <span class="rf-ptype rf-ptype-accion">acción</span>
                                            <?php elseif ($tipo === 'wildcard'): ?>
                                                <span class="rf-ptype rf-ptype-wild">total</span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="rf-actions">
                <a href="<?= url('configuracion/roles') ?>" class="rf-btn rf-btn-ghost">Cancelar</a>
                <button type="submit" class="rf-btn rf-btn-primary">
                    <i class="fas fa-check"></i> <?= $rol ? 'Guardar cambios' : 'Crear rol' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.rolf-view [data-toggle-group]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var group = btn.closest('.rf-group');
        var boxes = group.querySelectorAll('input[type="checkbox"]');
        var allChecked = Array.prototype.every.call(boxes, function (b) { return b.checked; });
        boxes.forEach(function (b) { b.checked = !allChecked; });
        btn.textContent = allChecked ? 'Todos' : 'Ninguno';
    });
});
</script>
