<?php
/**
 * Listado de roles configurables del hotel.
 * Recibe: $roles, $catalogo
 */
$roles = $roles ?? [];
$usuariosPorRol = is_array($usuariosPorRol ?? null) ? $usuariosPorRol : [];
$usuariosSinRol = is_array($usuariosSinRol ?? null) ? $usuariosSinRol : [];

/** Nombre que se muestra de una persona (cae al usuario si no hay nombre). */
$nombrePersona = static function (array $p) {
    $nombre = trim((string) ($p['nombre_completo'] ?? ''));
    return $nombre !== '' ? $nombre : (string) ($p['nombre_usuario'] ?? '—');
};

/** Iniciales para el círculo de la persona (máximo 2 letras). */
$inicialesPersona = static function ($nombre) {
    $partes = preg_split('/\s+/u', trim((string) $nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $iniciales = '';

    foreach (array_slice($partes, 0, 2) as $parte) {
        $iniciales .= mb_strtoupper(mb_substr($parte, 0, 1, 'UTF-8'), 'UTF-8');
    }

    return $iniciales !== '' ? $iniciales : '?';
};

/** Cuántos permisos se le ajustaron a mano a esta persona. */
$cambiosPersona = static function (array $p) {
    $ajustes = $p['ajustes'] ?? null;

    if (!is_array($ajustes)) {
        return 0;
    }

    return count($ajustes['extra'] ?? []) + count($ajustes['quitados'] ?? []);
};

$resumenPermisos = static function ($permisosJson) {
    $permisos = json_decode($permisosJson ?? '[]', true);
    if (!is_array($permisos)) {
        return ['texto' => 'Sin permisos', 'total' => 0, 'total_acceso' => false];
    }
    if (in_array('*', $permisos, true)) {
        return ['texto' => 'Acceso total', 'total' => 0, 'total_acceso' => true];
    }
    $n = count($permisos);
    return ['texto' => $n . ' permiso' . ($n === 1 ? '' : 's'), 'total' => $n, 'total_acceso' => false];
};
?>

<style>
.roles-view {
    --rv-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --rv-brand-2: var(--brand-action-bg-hover, var(--brand-secondary, #0F172A));
    --rv-on-brand: var(--brand-action-text, #FFFEFB);
    --rv-accent: var(--brand-accent, #BD9441);
    --rv-accent-dark: color-mix(in srgb, var(--rv-accent) 72%, #3F2E12);
    --rv-accent-soft: color-mix(in srgb, var(--rv-accent) 13%, #FFFFFF);
    --rv-text: var(--brand-text, #1B2746);
    --rv-muted: var(--brand-muted, #6C7689);
    --rv-surface: #FFFFFF;
    --rv-bg: #F7F4EE;
    --rv-border: color-mix(in srgb, var(--rv-brand) 9%, #E7E1D4);
    --rv-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rv-sans: 'Manrope', -apple-system, 'Segoe UI', sans-serif;
    font-family: var(--rv-sans);
    color: var(--rv-text);
    min-height: 100vh;
    background:
        radial-gradient(circle at 8% 0%, var(--rv-accent-soft) 0, transparent 24%),
        linear-gradient(180deg, #FBFAF6, var(--rv-bg)) !important;
}
.roles-view .rv-wrap { max-width: 1200px; margin: 0 auto; padding: 1.5rem 1.25rem 3rem; }
.roles-view .rv-hero { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; }
.roles-view .rv-hero h1 { font-family: var(--rv-serif); font-size: clamp(1.9rem, 3vw, 2.5rem); font-weight: 700; line-height: 1; color: var(--rv-brand); }
.roles-view .rv-hero p { color: var(--rv-muted); font-size: .9rem; margin-top: .35rem; font-weight: 500; }
.roles-view .rv-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 42px; padding: 0 1.05rem; border-radius: 11px; font-size: .85rem; font-weight: 700; text-decoration: none; border: none; cursor: pointer; transition: transform .16s ease, box-shadow .16s ease; }
.roles-view .rv-btn-primary { background: var(--rv-brand); color: var(--rv-on-brand); box-shadow: 0 12px 22px -16px color-mix(in srgb, var(--rv-brand) 80%, #000); }
.roles-view .rv-btn-primary:hover { transform: translateY(-1px); color: var(--rv-on-brand); }
.roles-view .rv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.roles-view .rv-card { background: var(--rv-surface); border: 1px solid var(--rv-border); border-radius: 16px; padding: 18px; display: flex; flex-direction: column; gap: .65rem; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 16px 34px -28px rgba(27,39,70,.4); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
.roles-view .rv-card:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--rv-accent) 28%, var(--rv-border)); }
.roles-view .rv-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
.roles-view .rv-rolename { font-family: var(--rv-serif); font-size: 1.35rem; font-weight: 700; line-height: 1.1; color: var(--rv-brand); }
.roles-view .rv-roldesc { color: var(--rv-muted); font-size: .8rem; line-height: 1.45; min-height: 2.3em; }
.roles-view .rv-chip { display: inline-flex; align-items: center; gap: .3rem; padding: 3px 9px; border-radius: 999px; font-size: .64rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
.roles-view .rv-chip-sys { background: var(--rv-accent-soft); color: var(--rv-accent-dark); border: 1px solid color-mix(in srgb, var(--rv-accent) 30%, #FFF); }
.roles-view .rv-chip-custom { background: #EAF1F8; color: #234C78; border: 1px solid #D6E2EF; }
.roles-view .rv-meta { display: flex; flex-wrap: wrap; gap: .4rem .9rem; font-size: .78rem; color: var(--rv-muted); border-top: 1px solid var(--rv-border); padding-top: .65rem; }
.roles-view .rv-meta b { color: var(--rv-text); font-weight: 700; }
.roles-view .rv-actions { display: flex; gap: .5rem; margin-top: .25rem; }
.roles-view .rv-abtn { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; min-height: 38px; border-radius: 10px; font-size: .8rem; font-weight: 700; text-decoration: none; cursor: pointer; border: 1px solid var(--rv-border); background: #F5F5F7; color: var(--rv-text); transition: background .16s ease, border-color .16s ease, color .16s ease; }
.roles-view .rv-abtn:hover { background: var(--rv-accent-soft); border-color: color-mix(in srgb, var(--rv-accent) 30%, var(--rv-border)); color: var(--rv-accent-dark); }
.roles-view .rv-abtn.is-danger { color: #B42318; }
.roles-view .rv-abtn.is-danger:hover { background: #FDECEC; border-color: #F3C6C2; color: #B42318; }
.roles-view .rv-abtn[disabled] { opacity: .45; cursor: not-allowed; }
.roles-view .rv-empty { text-align: center; padding: 3rem 1rem; background: #FBFAF6; border: 1px dashed var(--rv-border); border-radius: 16px; }
.roles-view .rv-empty i { font-size: 2.5rem; color: color-mix(in srgb, var(--rv-accent) 50%, #D9CFBE); }
.roles-view .rv-empty h3 { font-family: var(--rv-serif); font-size: 1.4rem; color: var(--rv-brand); margin: .6rem 0 .25rem; font-weight: 700; }
.roles-view .rv-empty p { color: var(--rv-muted); font-size: .85rem; margin-bottom: 1rem; }
.roles-view .rv-back { display: inline-flex; align-items: center; gap: .4rem; color: var(--rv-muted); font-size: .82rem; font-weight: 600; text-decoration: none; margin-bottom: .85rem; }
.roles-view .rv-back:hover { color: var(--rv-brand); }
/* En móvil el regreso lo cubre la flecha minimalista global (partials/back_arrow.php) */
@media (max-width: 768px) { .roles-view .rv-back { display: none; } }

/* ── Quién tiene cada rol ─────────────────────────────────────────── */
.roles-view .rv-people { border-top: 1px solid var(--rv-border); padding-top: .7rem; }
.roles-view .rv-people-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .5rem; }
.roles-view .rv-people-title { font-size: .68rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--rv-muted); }
.roles-view .rv-people-list { display: flex; flex-direction: column; gap: 2px; }
.roles-view .rv-person { display: flex; align-items: center; gap: .55rem; padding: .35rem .4rem; border-radius: 9px; text-decoration: none; color: inherit; transition: background .14s ease; }
.roles-view a.rv-person:hover { background: var(--rv-accent-soft); }
.roles-view .rv-person-av { flex-shrink: 0; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; letter-spacing: .02em; background: color-mix(in srgb, var(--rv-brand) 10%, #FFF); color: var(--rv-brand); border: 1px solid var(--rv-border); }
.roles-view .rv-person-body { min-width: 0; flex: 1; }
.roles-view .rv-person-name { font-size: .84rem; font-weight: 600; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.roles-view .rv-person-sub { font-size: .68rem; color: var(--rv-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.roles-view .rv-person-go { flex-shrink: 0; font-size: .7rem; color: var(--rv-muted); opacity: 0; transition: opacity .14s ease; }
.roles-view a.rv-person:hover .rv-person-go { opacity: 1; }
.roles-view .rv-person.is-off { opacity: .5; }
.roles-view .rv-tag { display: inline-flex; align-items: center; gap: .22rem; padding: 1px 6px; border-radius: 999px; font-size: .58rem; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; white-space: nowrap; }
.roles-view .rv-tag-tuned { background: var(--rv-accent-soft); color: var(--rv-accent-dark); border: 1px solid color-mix(in srgb, var(--rv-accent) 30%, #FFF); }
.roles-view .rv-tag-off { background: var(--rv-tag-off-bg, #F1F1F3); color: var(--rv-muted); border: 1px solid var(--rv-tag-off-line, #E3E3E7); }
.roles-view .rv-people-more { align-self: flex-start; margin-top: .2rem; background: none; border: none; padding: .2rem .4rem; font-size: .72rem; font-weight: 700; color: var(--rv-accent-dark); cursor: pointer; font-family: inherit; }
.roles-view .rv-people-more:hover { text-decoration: underline; }
.roles-view .rv-people-empty { font-size: .76rem; color: var(--rv-muted); font-style: italic; padding: .2rem 0 .1rem; }
.roles-view .rv-orphans { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem .9rem; padding: 13px 16px; margin-bottom: 1rem; border-radius: 13px; background: var(--rv-warn-soft, #FFF8E8); border: 1px solid var(--rv-warn-line, #F0DFB6); color: var(--rv-warn-ink, #7A5B12); font-size: .84rem; line-height: 1.45; }
.roles-view .rv-orphans a { color: inherit; font-weight: 700; text-decoration: underline; }
.roles-view .rv-orphans-names { font-weight: 700; }
@media (max-width: 640px) { .roles-view .rv-person-go { opacity: 1; } }

/* Modo oscuro de lo agregado aquí; los tokens --rv-* base ya los remapea el tema. */
html[data-theme="dark"] .roles-view {
    --rv-tag-off-bg: #2C2C2E;
    --rv-tag-off-line: #3A3A3C;
    --rv-warn-soft: color-mix(in srgb, #FF9F0A 16%, #1C1C1E);
    --rv-warn-line: color-mix(in srgb, #FF9F0A 34%, #1C1C1E);
    --rv-warn-ink: #FFC876;
    /* El tema oscuro remapea los neutros --rv-*, pero no el acento suave: sin
       esto los chips dorados (Base, A la medida) quedan casi blancos. */
    --rv-accent-soft: color-mix(in srgb, var(--rv-accent) 18%, #1C1C1E);
    --rv-accent-dark: #E8C083;
}
html[data-theme="dark"] .roles-view .rv-person-av { background: #2C2C2E; color: #F5F5F7; }
/* Los botones de la tarjeta traían el gris claro fijo del tema base. */
html[data-theme="dark"] .roles-view .rv-abtn { background: #2C2C2E; }
html[data-theme="dark"] .roles-view .rv-people-empty { color: var(--rv-muted); }
</style>

<div class="roles-view">
    <div class="rv-wrap">
        <?php $back_arrow_href = back_url('configuracion'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a href="<?= url('configuracion') ?>" class="rv-back"><i class="fas fa-arrow-left"></i> Volver a Configuración</a>

        <div class="rv-hero">
            <div>
                <h1>Roles y permisos</h1>
                <p>Define qué puede ver y hacer cada rol de
                    <?= htmlspecialchars(current_hotel_display_name('tu hotel'), ENT_QUOTES, 'UTF-8') ?>.</p>
            </div>
            <a href="<?= url('configuracion/roles/crear') ?>" class="rv-btn rv-btn-primary">
                <i class="fas fa-plus"></i> Nuevo rol
            </a>
        </div>

        <?php if (!empty($usuariosSinRol)): ?>
            <?php
            $nombresSinRol = array_map($nombrePersona, $usuariosSinRol);
            $muestraSinRol = array_slice($nombresSinRol, 0, 3);
            $restoSinRol = count($nombresSinRol) - count($muestraSinRol);
            ?>
            <div class="rv-orphans">
                <span><i class="fas fa-triangle-exclamation"></i></span>
                <span>
                    <span class="rv-orphans-names"><?= htmlspecialchars(implode(', ', $muestraSinRol), ENT_QUOTES, 'UTF-8') ?><?= $restoSinRol > 0 ? ' y ' . $restoSinRol . ' más' : '' ?></span>
                    <?= count($nombresSinRol) === 1 ? 'no tiene' : 'no tienen' ?> ningún rol de esta lista, así que
                    <?= count($nombresSinRol) === 1 ? 'sigue' : 'siguen' ?> con los permisos antiguos del sistema.
                    <a href="<?= url('usuarios') ?>">Asignarles un rol</a>
                </span>
            </div>
        <?php endif; ?>

        <?php if (empty($roles)): ?>
            <div class="rv-empty">
                <i class="fas fa-user-shield"></i>
                <h3>Aún no hay roles</h3>
                <p>Si acabas de activar esta función y no ves los roles, pide ayuda a soporte de Medisoft para cargarlos.</p>
                <a href="<?= url('configuracion/roles/crear') ?>" class="rv-btn rv-btn-primary"><i class="fas fa-plus"></i> Crear primer rol</a>
            </div>
        <?php else: ?>
            <div class="rv-grid">
                <?php foreach ($roles as $rol): ?>
                    <?php
                    $resumen = $resumenPermisos($rol['permisos_json'] ?? '[]');
                    $esSistema = !empty($rol['es_sistema']);
                    $usuariosCount = (int) ($rol['usuarios_count'] ?? 0);
                    $protegido = in_array($rol['clave'] ?? '', ['propietario', 'superadmin'], true);
                    $personas = $usuariosPorRol[(int) $rol['id']] ?? [];
                    // Un rol de acceso total no se recorta por persona (mismo
                    // criterio que su formulario): se listan, no se enlazan.
                    $puedeAjustarPersona = !$protegido && empty($resumen['total_acceso']);
                    $tope = 5;
                    ?>
                    <div class="rv-card">
                        <div class="rv-card-top">
                            <div>
                                <div class="rv-rolename"><?= htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <span class="rv-chip <?= $esSistema ? 'rv-chip-sys' : 'rv-chip-custom' ?>">
                                <i class="fas <?= $esSistema ? 'fa-shield-halved' : 'fa-user-pen' ?>"></i>
                                <?= $esSistema ? 'Base' : 'Personalizado' ?>
                            </span>
                        </div>

                        <div class="rv-roldesc"><?= htmlspecialchars($rol['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="rv-meta">
                            <span><i class="fas fa-key"></i> <b><?= htmlspecialchars($resumen['texto'], ENT_QUOTES, 'UTF-8') ?></b></span>
                            <span><i class="fas fa-users"></i> <b><?= $usuariosCount ?></b> persona<?= $usuariosCount === 1 ? '' : 's' ?></span>
                        </div>

                        <div class="rv-people">
                            <div class="rv-people-head">
                                <span class="rv-people-title">Quién lo tiene</span>
                            </div>

                            <?php if (empty($personas)): ?>
                                <div class="rv-people-empty">Todavía nadie tiene este rol.</div>
                            <?php else: ?>
                                <div class="rv-people-list">
                                    <?php foreach ($personas as $i => $persona): ?>
                                        <?php
                                        $nombre = $nombrePersona($persona);
                                        $cambios = $cambiosPersona($persona);
                                        $inactiva = empty($persona['activo']);
                                        $oculta = $i >= $tope;
                                        $clases = 'rv-person' . ($inactiva ? ' is-off' : '');
                                        $estilo = $oculta ? ' style="display:none;" data-extra' : '';
                                        $sub = '@' . ($persona['nombre_usuario'] ?? '');
                                        ?>
                                        <?php if ($puedeAjustarPersona): ?>
                                            <a class="<?= $clases ?>"<?= $estilo ?>
                                               href="<?= url('configuracion/roles/' . (int) $rol['id'] . '/persona/' . (int) $persona['usuario_id']) ?>"
                                               title="Ajustar los permisos de <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>">
                                        <?php else: ?>
                                            <div class="<?= $clases ?>"<?= $estilo ?>>
                                        <?php endif; ?>
                                                <span class="rv-person-av"><?= htmlspecialchars($inicialesPersona($nombre), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="rv-person-body">
                                                    <span class="rv-person-name">
                                                        <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
                                                        <?php if ($cambios > 0): ?>
                                                            <span class="rv-tag rv-tag-tuned" title="<?= $cambios ?> permiso<?= $cambios === 1 ? '' : 's' ?> distinto<?= $cambios === 1 ? '' : 's' ?> a los de su rol">
                                                                <i class="fas fa-wand-magic-sparkles"></i> A la medida
                                                            </span>
                                                        <?php endif; ?>
                                                        <?php if ($inactiva): ?>
                                                            <span class="rv-tag rv-tag-off">Sin acceso</span>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="rv-person-sub"><?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?></span>
                                                </span>
                                                <?php if ($puedeAjustarPersona): ?>
                                                    <i class="fas fa-sliders rv-person-go"></i>
                                                <?php endif; ?>
                                        <?php if ($puedeAjustarPersona): ?>
                                            </a>
                                        <?php else: ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if (count($personas) > $tope): ?>
                                        <button type="button" class="rv-people-more" data-ver-mas
                                                data-mas="Ver las <?= count($personas) - $tope ?> restantes"
                                                data-menos="Ver menos">
                                            Ver las <?= count($personas) - $tope ?> restantes
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="rv-actions">
                            <a href="<?= url('configuracion/roles/' . (int) $rol['id'] . '/editar') ?>" class="rv-abtn">
                                <i class="fas fa-sliders"></i> <?= $protegido ? 'Ver' : 'Editar' ?>
                            </a>
                            <?php if (!$esSistema): ?>
                                <button type="button" class="rv-abtn is-danger"
                                        <?= $usuariosCount > 0 ? 'disabled title="Reasigna sus usuarios antes de eliminar"' : '' ?>
                                        onclick="rolEliminar(<?= (int) $rol['id'] ?>, <?= htmlspecialchars(json_encode($rol['nombre'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>)">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<form id="rol-eliminar-form" method="POST" style="display:none;">
    <?= csrf_field() ?>
</form>

<script>
// "Ver las N restantes" de cada tarjeta de rol.
document.querySelectorAll('.roles-view [data-ver-mas]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var lista = btn.closest('.rv-people-list');
        if (!lista) return;
        var ocultas = lista.querySelectorAll('[data-extra]');
        var abierto = btn.dataset.abierto === '1';
        ocultas.forEach(function (fila) { fila.style.display = abierto ? 'none' : ''; });
        btn.dataset.abierto = abierto ? '0' : '1';
        btn.textContent = abierto ? btn.dataset.mas : btn.dataset.menos;
    });
});

function rolEliminar(id, nombre) {
    msConfirm({
        type: 'error',
        icon: 'trash',
        title: '¿Eliminar rol?',
        msg: 'Se eliminará el rol "' + nombre + '". Esta acción no se puede deshacer.',
        confirmLabel: 'Sí, eliminar'
    }).then(ok => {
        if (!ok) return;
        const form = document.getElementById('rol-eliminar-form');
        form.action = '<?= url('configuracion/roles/') ?>' + id + '/eliminar';
        form.submit();
    });
}
</script>
