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

// Se lee crudo de la sesion (no con old()): old() pasa el valor por
// htmlspecialchars() y con un arreglo eso es un TypeError fatal en PHP 8.
$marcados = $_SESSION['old_input']['permisos'] ?? null;
if (!is_array($marcados)) {
    $marcados = $permisosActuales ?? [];
}
$estaMarcado = static function ($perm) use ($marcados) {
    return in_array($perm, $marcados, true);
};
if (!function_exists('rf_separar_total')) {
    /**
     * Decide qué permiso encabeza el grupo. Lo comparten el form del rol y la
     * pantalla de permisos por persona.
     *
     * - Si el grupo tiene un permiso de control total (tipo wildcard), ese
     *   encabeza y el resto queda como detalle que él manda.
     * - Si el grupo tiene UN SOLO permiso, ese se sube al mismo lugar para que
     *   todas las tarjetas se vean igual. Es promocion VISUAL: no manda sobre
     *   nadie (no hay detalle) y se guarda como cualquier otra casilla.
     *
     * @return array{clave: ?string, def: ?array, resto: array, promovido: bool}
     */
    function rf_separar_total(array $permisos)
    {
        foreach ($permisos as $clave => $def) {
            if (($def['tipo'] ?? '') === 'wildcard') {
                $resto = $permisos;
                unset($resto[$clave]);
                return ['clave' => $clave, 'def' => $def, 'resto' => $resto, 'promovido' => false];
            }
        }

        if (count($permisos) === 1) {
            $clave = array_key_first($permisos);
            return ['clave' => $clave, 'def' => $permisos[$clave], 'resto' => [], 'promovido' => true];
        }

        return ['clave' => null, 'def' => null, 'resto' => $permisos, 'promovido' => false];
    }
}

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
    --rf-sunken: #F5F5F7;
    --rf-cut: #B42318;
    --rf-cut-soft: #FDECEC;
    --rf-cut-line: #F3C6C2;
    --rf-warn: #B45309;
    --rf-warn-soft: #FFF3E0;
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
.rolf-view .rf-input { width: 100%; padding: .65rem .8rem; border: 1px solid var(--rf-border); border-radius: 10px; font-size: .9rem; font-family: inherit; color: var(--rf-text); background: var(--rf-sunken); transition: border-color .16s ease, box-shadow .16s ease; }
.rolf-view .rf-input:focus { outline: none; border-color: var(--rf-accent); box-shadow: 0 0 0 3px var(--rf-accent-soft); }
.rolf-view .rf-input[readonly] { background: var(--rf-sunken); color: var(--rf-muted); cursor: not-allowed; }
.rolf-view .rf-field { margin-bottom: 1rem; }
.rolf-view .rf-notice { display: flex; gap: .65rem; align-items: flex-start; padding: 14px 16px; border-radius: 12px; background: var(--rf-accent-soft); border: 1px solid color-mix(in srgb, var(--rf-accent) 28%, #FFF); color: var(--rf-accent-dark); font-size: .85rem; line-height: 1.5; }
.rolf-view .rf-notice i { margin-top: 2px; }
.rolf-view .rf-groups { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.rolf-view .rf-group { border: 1px solid var(--rf-border); border-radius: 14px; padding: 14px 15px; background: var(--rf-sunken); }
.rolf-view .rf-group.is-locked { opacity: .62; }
.rolf-view .rf-ghead { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
.rolf-view .rf-gtitle { font-weight: 700; font-size: .95rem; color: var(--rf-brand); }
.rolf-view .rf-gmod { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--rf-cut); background: var(--rf-cut-soft); border: 1px solid var(--rf-cut-line); padding: 2px 7px; border-radius: 999px; }
.rolf-view .rf-gtoggle { font-size: .72rem; font-weight: 700; color: var(--rf-accent-dark); background: none; border: none; cursor: pointer; padding: 0; }
.rolf-view .rf-gtoggle:hover { text-decoration: underline; }
.rolf-view .rf-perm { display: flex; align-items: flex-start; gap: .55rem; padding: .35rem 0; cursor: pointer; }
.rolf-view .rf-perm input { margin-top: .2rem; width: 16px; height: 16px; accent-color: var(--rf-accent); cursor: pointer; flex-shrink: 0; }
.rolf-view .rf-perm span { font-size: .84rem; line-height: 1.3; }
.rolf-view .rf-perm .rf-ptype { display: inline-block; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-left: .35rem; padding: 1px 5px; border-radius: 4px; vertical-align: middle; }
.rolf-view .rf-ptype-accion { background: var(--rf-warn-soft); color: var(--rf-warn); }

/* ── Control total del área: interruptor propio, no una casilla más ── */
.rolf-view .rf-total { display: flex; align-items: flex-start; gap: .6rem; padding: 11px 12px; border-radius: 11px; background: var(--rf-surface); border: 1px solid var(--rf-border); cursor: pointer; transition: background .16s ease, border-color .16s ease; }
.rolf-view .rf-total:hover { border-color: color-mix(in srgb, var(--rf-accent) 40%, var(--rf-border)); }
.rolf-view .rf-total input { margin-top: .15rem; width: 17px; height: 17px; accent-color: var(--rf-accent); cursor: pointer; flex-shrink: 0; }
.rolf-view .rf-total-body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.rolf-view .rf-total-title { font-size: .85rem; font-weight: 700; line-height: 1.25; }
.rolf-view .rf-total-hint { font-size: .72rem; color: var(--rf-muted); line-height: 1.35; }
.rolf-view .rf-group.is-total .rf-total { background: var(--rf-accent-soft); border-color: color-mix(in srgb, var(--rf-accent) 40%, var(--rf-border)); }
.rolf-view .rf-group.is-total .rf-total-title { color: var(--rf-accent-dark); }
.rolf-view .rf-group.is-total .rf-gtoggle { display: none; }

.rolf-view .rf-sep { display: flex; align-items: center; gap: .5rem; margin: .7rem 0 .1rem; font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--rf-muted); }
.rolf-view .rf-sep::before, .rolf-view .rf-sep::after { content: ''; flex: 1; height: 1px; background: var(--rf-border); }
.rolf-view .rf-sep-on { display: none; }
.rolf-view .rf-group.is-total .rf-sep-off { display: none; }
.rolf-view .rf-group.is-total .rf-sep-on { display: inline; color: var(--rf-accent-dark); }

/* Con el control total puesto, el detalle deja de decidir: se ve incluido, no editable. */
.rolf-view .rf-group.is-total .rf-perms { opacity: .55; }
.rolf-view .rf-group.is-total .rf-perm { cursor: default; }
.rolf-view .rf-perm input:disabled { cursor: default; }

/* Modo oscuro agnóstico de tema. El tema ya remapeaba --rf-surface/-text, pero
   las tarjetas de grupo y el acento suave seguían fijos en claro: la matriz
   entera salía gris claro con texto claro (ilegible). */
html[data-theme="dark"] .rolf-view {
    --rf-surface: var(--brand-surface, #1C1C1E);
    --rf-sunken: #161617;
    --rf-bg: #000000;
    --rf-border: #38383A;
    --rf-text: #D1D1D6;
    --rf-muted: #98989D;
    --rf-accent-soft: color-mix(in srgb, var(--rf-accent) 16%, #1C1C1E);
    --rf-accent-dark: #E8C083;
    --rf-cut: #FF6961;
    --rf-cut-soft: color-mix(in srgb, #FF453A 16%, #1C1C1E);
    --rf-cut-line: color-mix(in srgb, #FF453A 34%, #1C1C1E);
    --rf-warn: #FFB340;
    --rf-warn-soft: color-mix(in srgb, #FF9F0A 18%, #1C1C1E);
    background: #000000 !important;
}
html[data-theme="dark"] .rolf-view h1,
html[data-theme="dark"] .rolf-view .rf-panel h2,
html[data-theme="dark"] .rolf-view .rf-gtitle { color: #F5F5F7; }
html[data-theme="dark"] .rolf-view .rf-notice { border-color: color-mix(in srgb, var(--rf-accent) 34%, #1C1C1E); }
html[data-theme="dark"] .rolf-view .rf-panel { box-shadow: none; }
html[data-theme="dark"] .rolf-view .rf-btn-primary { box-shadow: none; }
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
                            // El "control total" del área manda sobre los demás permisos del
                            // grupo: se saca de la lista y se presenta como el interruptor de
                            // arriba. Mezclado al final se leía como una opción más.
                            $cab = rf_separar_total($grupo['permisos'] ?? []);
                            $totalKey = $cab['clave'];
                            $promovido = $cab['promovido'];
                            $permisosSueltos = $cab['resto'];
                            $totalMarcado = $totalKey !== null && !$promovido && $estaMarcado($totalKey);
                            ?>
                            <div class="rf-group <?= $locked ? 'is-locked' : '' ?><?= $totalMarcado ? ' is-total' : '' ?>" data-group="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>">
                                <div class="rf-ghead">
                                    <span class="rf-gtitle"><?= htmlspecialchars($grupo['label'] ?? $clave, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($locked): ?>
                                        <span class="rf-gmod" title="El hotel no tiene contratado este módulo">Sin módulo</span>
                                    <?php elseif ($permisosSueltos): ?>
                                        <button type="button" class="rf-gtoggle" data-toggle-group>Todos</button>
                                    <?php endif; ?>
                                </div>

                                <?php if ($totalKey !== null): ?>
                                    <label class="rf-total">
                                        <input type="checkbox" name="permisos[]" <?= $promovido ? '' : 'data-total' ?>
                                               value="<?= htmlspecialchars($totalKey, ENT_QUOTES, 'UTF-8') ?>"
                                               <?= $estaMarcado($totalKey) ? 'checked' : '' ?>>
                                        <span class="rf-total-body">
                                            <span class="rf-total-title"><?= htmlspecialchars($cab['def']['label'] ?? $totalKey, ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="rf-total-hint">
                                                <?= $promovido
                                                    ? 'Es el único acceso de esta área.'
                                                    : 'Incluye todo lo de abajo y lo que se agregue después.' ?>
                                            </span>
                                        </span>
                                    </label>
                                    <?php if (!$promovido): ?>
                                        <div class="rf-sep">
                                            <span class="rf-sep-off">o elige punto por punto</span>
                                            <span class="rf-sep-on">Todo esto ya va incluido</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="rf-perms">
                                    <?php foreach ($permisosSueltos as $permKey => $permDef): ?>
                                        <?php $tipo = $permDef['tipo'] ?? 'accion'; ?>
                                        <label class="rf-perm">
                                            <input type="checkbox" name="permisos[]"
                                                   value="<?= htmlspecialchars($permKey, ENT_QUOTES, 'UTF-8') ?>"
                                                   <?= ($totalMarcado || $estaMarcado($permKey)) ? 'checked' : '' ?>
                                                   <?= $totalMarcado ? 'disabled' : '' ?>>
                                            <span>
                                                <?= htmlspecialchars($permDef['label'] ?? $permKey, ENT_QUOTES, 'UTF-8') ?>
                                                <?php if ($tipo === 'accion'): ?>
                                                    <span class="rf-ptype rf-ptype-accion">acción</span>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
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
(function () {
    // El control total del grupo manda: cuando está puesto, el detalle queda
    // marcado y sin editar (deshabilitado no viaja en el POST, así el rol se
    // guarda con la clave total sola en vez de repetir sus hijos).
    function sincronizarTotal(grupo) {
        var total = grupo.querySelector('[data-total]');
        if (!total) return;
        var puesto = total.checked;
        grupo.classList.toggle('is-total', puesto);
        grupo.querySelectorAll('.rf-perms input[type="checkbox"]').forEach(function (b) {
            if (puesto) b.checked = true;
            b.disabled = puesto;
        });
    }

    document.querySelectorAll('.rolf-view .rf-group').forEach(function (grupo) {
        var total = grupo.querySelector('[data-total]');
        if (total) total.addEventListener('change', function () { sincronizarTotal(grupo); });
        sincronizarTotal(grupo);
    });

    document.querySelectorAll('.rolf-view [data-toggle-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var grupo = btn.closest('.rf-group');
            var boxes = grupo.querySelectorAll('.rf-perms input[type="checkbox"]');
            if (!boxes.length) return;
            var todas = Array.prototype.every.call(boxes, function (b) { return b.checked; });
            boxes.forEach(function (b) { b.checked = !todas; });
            btn.textContent = todas ? 'Todos' : 'Ninguno';
        });
    });
})();
</script>
