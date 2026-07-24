<?php
/**
 * Permisos de UNA persona dentro de su rol.
 *
 * La matriz llega marcada con lo que la persona puede HOY (rol + sus ajustes).
 * Lo que quede distinto a su rol se guarda como diferencia, así que si mañana
 * se edita el rol, esta persona sigue heredando el resto de los cambios.
 *
 * Recibe: $rol, $persona, $catalogo, $permisosRol, $ajustes, $marcados,
 *         $modulosActivos, $esUnoMismo, $action, $actionRestablecer
 */
$catalogo = $catalogo ?? [];
$permisosRol = is_array($permisosRol ?? null) ? $permisosRol : [];
$marcados = is_array($marcados ?? null) ? $marcados : [];
$ajustes = is_array($ajustes ?? null) ? $ajustes : null;
$modulosActivos = is_array($modulosActivos ?? null) ? $modulosActivos : [];
$filtrarModulos = !empty($modulosActivos);

$nombrePersona = trim((string) ($persona['nombre_completo'] ?? ''));
if ($nombrePersona === '') {
    $nombrePersona = (string) ($persona['nombre_usuario'] ?? 'esta persona');
}

$totalAjustes = $ajustes ? (count($ajustes['extra'] ?? []) + count($ajustes['quitados'] ?? [])) : 0;

if (!function_exists('rf_separar_total')) {
    /**
     * Decide qué permiso encabeza el grupo (control total, o el único que haya
     * para que todas las tarjetas se vean igual). Mismo helper que usa
     * roles/form.php — vistas autocontenidas.
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

/** Etiqueta legible de un permiso, para la lista de diferencias. */
$etiquetaPermiso = static function ($clave) use ($catalogo) {
    foreach ($catalogo as $grupo) {
        if (isset($grupo['permisos'][$clave]['label'])) {
            return $grupo['label'] . ' · ' . $grupo['permisos'][$clave]['label'];
        }
    }
    return $clave;
};
?>

<style>
.permp-view {
    --pp-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --pp-on-brand: var(--brand-action-text, #FFFEFB);
    --pp-accent: var(--brand-accent, #BD9441);
    --pp-accent-dark: color-mix(in srgb, var(--pp-accent) 72%, #3F2E12);
    --pp-accent-soft: color-mix(in srgb, var(--pp-accent) 13%, #FFFFFF);
    --pp-text: var(--brand-text, #1B2746);
    --pp-muted: var(--brand-muted, #6C7689);
    --pp-surface: #FFFFFF;
    --pp-sunken: #F5F5F7;
    --pp-bg: #F7F4EE;
    --pp-border: color-mix(in srgb, var(--pp-brand) 9%, #E7E1D4);
    --pp-add: #1F7A4D;
    --pp-add-soft: #E7F5EE;
    --pp-add-line: color-mix(in srgb, var(--pp-add) 25%, #FFFFFF);
    --pp-cut: #B42318;
    --pp-cut-soft: #FDECEC;
    --pp-cut-line: #F3C6C2;
    --pp-warn: #B45309;
    --pp-warn-soft: #FFF3E0;
    --pp-info: #234C78;
    --pp-info-soft: #EAF1F8;
    font-family: 'Manrope', -apple-system, 'Segoe UI', sans-serif;
    color: var(--pp-text);
    min-height: 100vh;
    background: radial-gradient(circle at 8% 0%, var(--pp-accent-soft) 0, transparent 24%), linear-gradient(180deg, #FBFAF6, var(--pp-bg)) !important;
}
.permp-view .pp-wrap { max-width: 980px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
.permp-view .pp-back { display: inline-flex; align-items: center; gap: .4rem; color: var(--pp-muted); font-size: .82rem; font-weight: 600; text-decoration: none; margin-bottom: .85rem; }
.permp-view .pp-back:hover { color: var(--pp-brand); }
@media (max-width: 768px) { .permp-view .pp-back { display: none; } }

.permp-view .pp-hero { display: flex; align-items: center; gap: .9rem; margin-bottom: 1.25rem; }
.permp-view .pp-av { flex-shrink: 0; width: 54px; height: 54px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 1.15rem; font-weight: 700; background: color-mix(in srgb, var(--pp-brand) 10%, #FFF); color: var(--pp-brand); border: 1px solid var(--pp-border); }
.permp-view .pp-hero h1 { font-size: clamp(1.55rem, 3vw, 2.1rem); font-weight: 700; line-height: 1.1; color: var(--pp-brand); }
.permp-view .pp-hero p { color: var(--pp-muted); font-size: .85rem; margin-top: .2rem; font-weight: 500; }
.permp-view .pp-hero b { color: var(--pp-text); font-weight: 700; }

.permp-view .pp-panel { background: var(--pp-surface); border: 1px solid var(--pp-border); border-radius: 16px; padding: 20px; margin-bottom: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 16px 34px -30px rgba(27,39,70,.4); }
.permp-view .pp-panel h2 { font-size: 1.22rem; font-weight: 700; color: var(--pp-brand); margin-bottom: .25rem; }
.permp-view .pp-hint { color: var(--pp-muted); font-size: .8rem; margin-bottom: 1rem; line-height: 1.5; }

.permp-view .pp-note { display: flex; gap: .65rem; align-items: flex-start; padding: 13px 16px; border-radius: 12px; background: var(--pp-accent-soft); border: 1px solid color-mix(in srgb, var(--pp-accent) 28%, #FFF); color: var(--pp-accent-dark); font-size: .84rem; line-height: 1.5; }
.permp-view .pp-note i { margin-top: 2px; }

.permp-view .pp-diff { display: flex; flex-direction: column; gap: .45rem; margin-bottom: 1rem; }
.permp-view .pp-diff-row { display: flex; align-items: flex-start; gap: .5rem; font-size: .82rem; line-height: 1.4; }
.permp-view .pp-pill { display: inline-flex; align-items: center; gap: .28rem; padding: 2px 8px; border-radius: 999px; font-size: .62rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; flex-shrink: 0; }
.permp-view .pp-pill-add { background: var(--pp-add-soft); color: var(--pp-add); border: 1px solid var(--pp-add-line); }
.permp-view .pp-pill-cut { background: var(--pp-cut-soft); color: var(--pp-cut); border: 1px solid var(--pp-cut-line); }

.permp-view .pp-groups { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 14px; }
.permp-view .pp-group { border: 1px solid var(--pp-border); border-radius: 14px; padding: 14px 15px; background: var(--pp-sunken); }
.permp-view .pp-group.is-locked { opacity: .62; }
.permp-view .pp-ghead { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .6rem; }
.permp-view .pp-gtitle { font-weight: 700; font-size: .95rem; color: var(--pp-brand); }
.permp-view .pp-gmod { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--pp-cut); background: var(--pp-cut-soft); border: 1px solid var(--pp-cut-line); padding: 2px 7px; border-radius: 999px; }
.permp-view .pp-gtoggle { font-size: .72rem; font-weight: 700; color: var(--pp-accent-dark); background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; }
.permp-view .pp-gtoggle:hover { text-decoration: underline; }

.permp-view .pp-perm { display: flex; align-items: flex-start; gap: .55rem; padding: .38rem .45rem; margin: 0 -.45rem; border-radius: 8px; cursor: pointer; border-left: 3px solid transparent; }
.permp-view .pp-perm input { margin-top: .2rem; width: 16px; height: 16px; accent-color: var(--pp-accent); cursor: pointer; flex-shrink: 0; }
.permp-view .pp-perm span.pp-plabel { font-size: .84rem; line-height: 1.3; }
.permp-view .pp-perm.is-extra { background: var(--pp-add-soft); border-left-color: var(--pp-add); }
.permp-view .pp-perm.is-quitado { background: var(--pp-cut-soft); border-left-color: var(--pp-cut); }
.permp-view .pp-mark { display: none; font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-left: .35rem; padding: 1px 5px; border-radius: 4px; vertical-align: middle; white-space: nowrap; }
.permp-view .pp-perm.is-extra .pp-mark-add { display: inline-block; background: var(--pp-surface); color: var(--pp-add); border: 1px solid var(--pp-add-line); }
.permp-view .pp-perm.is-quitado .pp-mark-cut { display: inline-block; background: var(--pp-surface); color: var(--pp-cut); border: 1px solid var(--pp-cut-line); }
.permp-view .pp-ptype { display: inline-block; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; margin-left: .35rem; padding: 1px 5px; border-radius: 4px; vertical-align: middle; }
.permp-view .pp-ptype-accion { background: var(--pp-warn-soft); color: var(--pp-warn); }

/* ── Control total del área: interruptor propio, no una casilla más ── */
.permp-view .pp-total { display: flex; align-items: flex-start; gap: .6rem; padding: 11px 12px; border-radius: 11px; background: var(--pp-surface); border: 1px solid var(--pp-border); border-left: 3px solid var(--pp-border); cursor: pointer; transition: background .16s ease, border-color .16s ease; }
.permp-view .pp-total:hover { border-color: color-mix(in srgb, var(--pp-accent) 40%, var(--pp-border)); }
.permp-view .pp-total input { margin-top: .15rem; width: 17px; height: 17px; accent-color: var(--pp-accent); cursor: pointer; flex-shrink: 0; }
.permp-view .pp-total-body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; }
.permp-view .pp-total-title { font-size: .85rem; font-weight: 700; line-height: 1.25; }
.permp-view .pp-total-hint { font-size: .72rem; color: var(--pp-muted); line-height: 1.35; }
.permp-view .pp-group.is-total .pp-total { background: var(--pp-accent-soft); border-color: color-mix(in srgb, var(--pp-accent) 40%, var(--pp-border)); }
.permp-view .pp-group.is-total .pp-gtoggle { display: none; }
.permp-view .pp-total.is-extra { background: var(--pp-add-soft); border-color: var(--pp-add-line); border-left-color: var(--pp-add); }
.permp-view .pp-total.is-quitado { background: var(--pp-cut-soft); border-color: var(--pp-cut-line); border-left-color: var(--pp-cut); }
.permp-view .pp-total.is-extra .pp-mark-add { display: inline-block; background: var(--pp-surface); color: var(--pp-add); border: 1px solid var(--pp-add-line); }
.permp-view .pp-total.is-quitado .pp-mark-cut { display: inline-block; background: var(--pp-surface); color: var(--pp-cut); border: 1px solid var(--pp-cut-line); }

.permp-view .pp-sep { display: flex; align-items: center; gap: .5rem; margin: .7rem 0 .1rem; font-size: .68rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; color: var(--pp-muted); }
.permp-view .pp-sep::before, .permp-view .pp-sep::after { content: ''; flex: 1; height: 1px; background: var(--pp-border); }
.permp-view .pp-sep-on { display: none; }
.permp-view .pp-group.is-total .pp-sep-off { display: none; }
.permp-view .pp-group.is-total .pp-sep-on { display: inline; color: var(--pp-accent-dark); }

/* Con el control total puesto, el detalle deja de decidir: se ve incluido, no editable. */
.permp-view .pp-group.is-total .pp-perms { opacity: .55; }
.permp-view .pp-group.is-total .pp-perm { cursor: default; background: none; border-left-color: transparent; }
.permp-view .pp-perm input:disabled { cursor: default; }

.permp-view .pp-counter { font-size: .78rem; font-weight: 700; color: var(--pp-muted); }
.permp-view .pp-counter.is-on { color: var(--pp-accent-dark); }

.permp-view .pp-actions { display: flex; gap: .6rem; justify-content: flex-end; align-items: center; flex-wrap: wrap; margin-top: 1.25rem; }
.permp-view .pp-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: 0 1.2rem; border-radius: 11px; font-size: .88rem; font-weight: 700; text-decoration: none; border: 1px solid var(--pp-border); cursor: pointer; font-family: inherit; transition: transform .16s ease; }
.permp-view .pp-btn:hover { transform: translateY(-1px); }
.permp-view .pp-btn-ghost { background: var(--pp-surface); color: var(--pp-text); }
.permp-view .pp-btn-reset { background: var(--pp-surface); color: var(--pp-cut); border-color: var(--pp-cut-line); margin-right: auto; }
.permp-view .pp-btn-reset:hover { background: var(--pp-cut-soft); }
.permp-view .pp-btn-primary { background: var(--pp-brand); color: var(--pp-on-brand); border-color: var(--pp-brand); box-shadow: 0 12px 22px -16px color-mix(in srgb, var(--pp-brand) 80%, #000); }
.permp-view .pp-btn-primary:hover { color: var(--pp-on-brand); }
/* Modo oscuro agnóstico de tema (cubre Deleite y Cupertino): se remapean los
   neutros de un golpe y los semánticos se tintan oscuro CONSERVANDO su matiz. */
html[data-theme="dark"] .permp-view {
    --pp-surface: var(--brand-surface, #1C1C1E);
    --pp-sunken: #161617;
    --pp-bg: #000000;
    --pp-border: #38383A;
    --pp-text: #D1D1D6;
    --pp-muted: #98989D;
    --pp-add: #30D158;
    --pp-add-soft: color-mix(in srgb, #30D158 16%, #1C1C1E);
    --pp-add-line: color-mix(in srgb, #30D158 34%, #1C1C1E);
    --pp-cut: #FF6961;
    --pp-cut-soft: color-mix(in srgb, #FF453A 16%, #1C1C1E);
    --pp-cut-line: color-mix(in srgb, #FF453A 34%, #1C1C1E);
    --pp-warn: #FFB340;
    --pp-warn-soft: color-mix(in srgb, #FF9F0A 18%, #1C1C1E);
    --pp-info: #6CB4FF;
    --pp-info-soft: color-mix(in srgb, #0A84FF 20%, #1C1C1E);
    --pp-accent-soft: color-mix(in srgb, var(--pp-accent) 16%, #1C1C1E);
    --pp-accent-dark: #E8C083;
    background: #000000 !important;
}
html[data-theme="dark"] .permp-view .pp-hero h1,
html[data-theme="dark"] .permp-view .pp-panel h2,
html[data-theme="dark"] .permp-view .pp-gtitle { color: #F5F5F7; }
html[data-theme="dark"] .permp-view .pp-av { background: #2C2C2E; color: #F5F5F7; }
html[data-theme="dark"] .permp-view .pp-note { border-color: color-mix(in srgb, var(--pp-accent) 34%, #1C1C1E); }
html[data-theme="dark"] .permp-view .pp-panel { box-shadow: none; }
html[data-theme="dark"] .permp-view .pp-btn-primary { box-shadow: none; }

@media (max-width: 640px) {
    .permp-view .pp-groups { grid-template-columns: 1fr; }
    .permp-view .pp-actions { flex-direction: column-reverse; align-items: stretch; }
    .permp-view .pp-btn { width: 100%; justify-content: center; }
    .permp-view .pp-btn-reset { margin-right: 0; }
}
</style>

<div class="permp-view">
    <div class="pp-wrap">
        <?php $back_arrow_href = url('configuracion/roles'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a href="<?= url('configuracion/roles') ?>" class="pp-back"><i class="fas fa-arrow-left"></i> Volver a roles</a>

        <div class="pp-hero">
            <?php
            $partes = preg_split('/\s+/u', $nombrePersona, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $iniciales = '';
            foreach (array_slice($partes, 0, 2) as $parte) {
                $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
            }
            ?>
            <span class="pp-av"><?= htmlspecialchars($iniciales !== '' ? $iniciales : '?', ENT_QUOTES, 'UTF-8') ?></span>
            <div>
                <h1><?= htmlspecialchars($nombrePersona, ENT_QUOTES, 'UTF-8') ?></h1>
                <p>
                    Rol: <b><?= htmlspecialchars($rol['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></b>
                    · @<?= htmlspecialchars($persona['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                    <?php if (empty($persona['activo'])): ?> · <b>sin acceso al hotel</b><?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($esUnoMismo ?? false): ?>
            <div class="pp-panel">
                <div class="pp-note">
                    <i class="fas fa-user-shield"></i>
                    <span><strong>Estás editando tus propios permisos.</strong> Puedes quitarte cosas, pero no darte permisos que tu rol no tiene, y tampoco quitarte la gestión de roles: si lo hicieras, no podrías volver a entrar aquí.</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($totalAjustes > 0): ?>
            <div class="pp-panel">
                <h2>Lo que hoy tiene distinto a su rol</h2>
                <p class="pp-hint">Todo lo demás lo hereda del rol <b><?= htmlspecialchars($rol['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></b>: si ese rol cambia, esta persona cambia con él.</p>
                <div class="pp-diff">
                    <?php foreach (($ajustes['extra'] ?? []) as $permiso): ?>
                        <div class="pp-diff-row">
                            <span class="pp-pill pp-pill-add"><i class="fas fa-plus"></i> De más</span>
                            <span><?= htmlspecialchars($etiquetaPermiso($permiso), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach (($ajustes['quitados'] ?? []) as $permiso): ?>
                        <div class="pp-diff-row">
                            <span class="pp-pill pp-pill-cut"><i class="fas fa-minus"></i> De menos</span>
                            <span><?= htmlspecialchars($etiquetaPermiso($permiso), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" id="pp-form">
            <?= csrf_field() ?>

            <div class="pp-panel">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                    <div>
                        <h2>Qué puede hacer esta persona</h2>
                        <p class="pp-hint" style="margin-bottom:0;">
                            Viene marcado lo que hoy puede. Desmarca para quitarle algo o marca para darle un permiso extra
                            <b>solo a ella</b>, sin tocar a las demás personas con el rol <?= htmlspecialchars($rol['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>.
                        </p>
                    </div>
                    <span class="pp-counter" id="pp-counter" data-cero="Igual que su rol">Igual que su rol</span>
                </div>

                <div class="pp-groups" style="margin-top:1rem;">
                    <?php foreach ($catalogo as $clave => $grupo): ?>
                        <?php
                        $moduloRequerido = $grupo['modulo'] ?? null;
                        $locked = $filtrarModulos && $moduloRequerido && !in_array($moduloRequerido, $modulosActivos, true);
                        ?>
                        <?php
                        // El control total del área sale de la lista y manda sobre ella.
                        // Un área de una sola opción sube esa opción al mismo lugar solo
                        // para que se vea igual ($promovido): no manda sobre nadie, así
                        // que no bloquea nada y se marca como cualquier otra casilla.
                        $cab = rf_separar_total($grupo['permisos'] ?? []);
                        $totalKey = $cab['clave'];
                        $totalDef = $cab['def'];
                        $promovido = $cab['promovido'];
                        $permisosSueltos = $cab['resto'];
                        $totalMarcado = $totalKey !== null && !$promovido && in_array($totalKey, $marcados, true);
                        ?>
                        <div class="pp-group <?= $locked ? 'is-locked' : '' ?><?= $totalMarcado ? ' is-total' : '' ?>" data-group="<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>">
                            <div class="pp-ghead">
                                <span class="pp-gtitle"><?= htmlspecialchars($grupo['label'] ?? $clave, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($locked): ?>
                                    <span class="pp-gmod" title="El hotel no tiene contratado este módulo">Sin módulo</span>
                                <?php elseif ($permisosSueltos): ?>
                                    <button type="button" class="pp-gtoggle" data-toggle-group>Todos</button>
                                <?php endif; ?>
                            </div>

                            <?php if ($totalKey !== null): ?>
                                <?php
                                $totalLoDaElRol = permission_in_list($totalKey, $permisosRol);
                                $totalCasillaMarcada = in_array($totalKey, $marcados, true);
                                $estadoTotal = '';
                                if ($totalCasillaMarcada && !$totalLoDaElRol) {
                                    $estadoTotal = ' is-extra';
                                } elseif (!$totalCasillaMarcada && $totalLoDaElRol) {
                                    $estadoTotal = ' is-quitado';
                                }
                                ?>
                                <label class="pp-total<?= $estadoTotal ?>">
                                    <input type="checkbox" name="permisos[]" <?= $promovido ? '' : 'data-total' ?>
                                           value="<?= htmlspecialchars($totalKey, ENT_QUOTES, 'UTF-8') ?>"
                                           data-rol="<?= $totalLoDaElRol ? '1' : '0' ?>"
                                           <?= $totalCasillaMarcada ? 'checked' : '' ?>>
                                    <span class="pp-total-body">
                                        <span class="pp-total-title">
                                            <?= htmlspecialchars($totalDef['label'] ?? $totalKey, ENT_QUOTES, 'UTF-8') ?>
                                            <span class="pp-mark pp-mark-add">extra</span>
                                            <span class="pp-mark pp-mark-cut">quitado</span>
                                        </span>
                                        <span class="pp-total-hint">
                                            <?= $promovido
                                                ? 'Es el único acceso de esta área.'
                                                : 'Incluye todo lo de abajo y lo que se agregue después.' ?>
                                        </span>
                                    </span>
                                </label>
                                <?php if (!$promovido): ?>
                                    <div class="pp-sep">
                                        <span class="pp-sep-off">o elige punto por punto</span>
                                        <span class="pp-sep-on">Todo esto ya va incluido</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="pp-perms">
                                <?php foreach ($permisosSueltos as $permKey => $permDef): ?>
                                    <?php
                                    $tipo = $permDef['tipo'] ?? 'accion';
                                    $loDaElRol = permission_in_list($permKey, $permisosRol);
                                    $marcado = $totalMarcado || in_array($permKey, $marcados, true);
                                    // Con el control total puesto, el detalle no decide: no se
                                    // marca como diferencia (la diferencia vive en el total).
                                    $estado = '';
                                    if (!$totalMarcado) {
                                        if ($marcado && !$loDaElRol) {
                                            $estado = ' is-extra';
                                        } elseif (!$marcado && $loDaElRol) {
                                            $estado = ' is-quitado';
                                        }
                                    }
                                    ?>
                                    <label class="pp-perm<?= $estado ?>">
                                        <input type="checkbox" name="permisos[]"
                                               value="<?= htmlspecialchars($permKey, ENT_QUOTES, 'UTF-8') ?>"
                                               data-rol="<?= $loDaElRol ? '1' : '0' ?>"
                                               <?= $marcado ? 'checked' : '' ?>
                                               <?= $totalMarcado ? 'disabled' : '' ?>>
                                        <span class="pp-plabel">
                                            <?= htmlspecialchars($permDef['label'] ?? $permKey, ENT_QUOTES, 'UTF-8') ?>
                                            <?php if ($tipo === 'accion'): ?>
                                                <span class="pp-ptype pp-ptype-accion">acción</span>
                                            <?php endif; ?>
                                            <span class="pp-mark pp-mark-add">extra</span>
                                            <span class="pp-mark pp-mark-cut">quitado</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="pp-actions">
                <?php if ($totalAjustes > 0): ?>
                    <button type="button" class="pp-btn pp-btn-reset" onclick="ppRestablecer()">
                        <i class="fas fa-rotate-left"></i> Dejarla igual que su rol
                    </button>
                <?php endif; ?>
                <a href="<?= url('configuracion/roles') ?>" class="pp-btn pp-btn-ghost">Cancelar</a>
                <button type="submit" class="pp-btn pp-btn-primary"><i class="fas fa-check"></i> Guardar</button>
            </div>
        </form>

        <?php if ($totalAjustes > 0): ?>
            <form id="pp-reset-form" method="POST" action="<?= htmlspecialchars($actionRestablecer, ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
                <?= csrf_field() ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('pp-form');
    if (!form) return;

    var contador = document.getElementById('pp-counter');

    // El control total del grupo manda: puesto, el detalle queda marcado, sin
    // editar y sin marca de diferencia (la diferencia vive en el total). Va
    // deshabilitado a propósito: así no viaja en el POST y la diferencia se
    // guarda como la clave total sola, no repitiendo a sus hijos.
    function sincronizarTotal(grupo) {
        var total = grupo.querySelector('[data-total]');
        if (!total) return;
        var puesto = total.checked;
        grupo.classList.toggle('is-total', puesto);
        grupo.querySelectorAll('.pp-perms input[type="checkbox"]').forEach(function (b) {
            if (puesto) b.checked = true;
            b.disabled = puesto;
        });
    }

    function pintar() {
        var extra = 0;
        var quitados = 0;

        form.querySelectorAll('.pp-total, .pp-perm').forEach(function (fila) {
            var box = fila.querySelector('input[type="checkbox"]');
            if (!box) return;
            fila.classList.remove('is-extra', 'is-quitado');

            // Detalle bajo un control total puesto: ni cuenta ni se pinta.
            var grupo = fila.closest('.pp-group');
            if (grupo && grupo.classList.contains('is-total') && !box.hasAttribute('data-total')) return;

            var loDaElRol = box.dataset.rol === '1';
            if (box.checked && !loDaElRol) { fila.classList.add('is-extra'); extra++; }
            else if (!box.checked && loDaElRol) { fila.classList.add('is-quitado'); quitados++; }
        });

        if (!contador) return;
        var total = extra + quitados;
        contador.classList.toggle('is-on', total > 0);
        if (total === 0) {
            contador.textContent = contador.dataset.cero;
            return;
        }
        var partes = [];
        if (extra) partes.push('+' + extra + ' de más');
        if (quitados) partes.push('−' + quitados + ' de menos');
        contador.textContent = partes.join(' · ');
    }

    form.addEventListener('change', function (e) {
        if (!e.target || e.target.type !== 'checkbox') return;
        if (e.target.hasAttribute('data-total')) sincronizarTotal(e.target.closest('.pp-group'));
        pintar();
    });

    form.querySelectorAll('[data-toggle-group]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var grupo = btn.closest('.pp-group');
            var boxes = grupo.querySelectorAll('.pp-perms input[type="checkbox"]');
            if (!boxes.length) return;
            var todas = Array.prototype.every.call(boxes, function (b) { return b.checked; });
            boxes.forEach(function (b) { b.checked = !todas; });
            btn.textContent = todas ? 'Todos' : 'Ninguno';
            pintar();
        });
    });

    form.querySelectorAll('.pp-group').forEach(sincronizarTotal);
    pintar();
})();

function ppRestablecer() {
    msConfirm({
        type: 'warning',
        icon: 'rotate-left',
        title: '¿Dejarla igual que su rol?',
        msg: 'Se borran los permisos que le ajustaste a mano y vuelve a tener exactamente lo del rol.',
        confirmLabel: 'Sí, restablecer'
    }).then(function (ok) {
        if (!ok) return;
        document.getElementById('pp-reset-form').submit();
    });
}
</script>
