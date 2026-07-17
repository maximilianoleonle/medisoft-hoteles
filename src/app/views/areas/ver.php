<?php
/**
 * Detalle de area (bloque habitaciones y areas): acciones operativas
 * (limpieza con personal, mantenimiento, cerrar/reabrir) + historial.
 * Mobile-first, mismos tokens boutique que areas/index.php.
 */

if (!function_exists('arv_safe')) {
    function arv_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('arv_fecha')) {
    function arv_fecha($value): string
    {
        if (empty($value)) {
            return '-';
        }
        $ts = strtotime((string)$value);
        return $ts ? date('d/m/Y H:i', $ts) : '-';
    }
}

$area = $area ?? [];
$tipos = $tipos ?? [];
$historial = $historial ?? ['limpiezas' => [], 'mantenimientos' => []];
$personalLimpieza = $personal_limpieza ?? [];
$tiposMantenimiento = $tipos_mantenimiento ?? [];
$prioridadesMantenimiento = $prioridades_mantenimiento ?? [];
$puedeMantenimiento = !empty($puede_mantenimiento);

$areaId = (int)($area['id'] ?? 0);
$estado = (string)($area['estado'] ?? 'disponible');
$estaActiva = (int)($area['activa'] ?? 1) === 1;
$tipoMeta = $tipos[$area['tipo'] ?? 'otra'] ?? ['label' => 'Otra área', 'icono' => 'fa-location-dot'];
$pisoTexto = ($area['piso'] === null || $area['piso'] === '') ? 'Exterior / PB' : 'Piso ' . (int)$area['piso'];

$estadoMeta = [
    'disponible'    => ['label' => 'Disponible',       'clase' => 'is-ok',     'icono' => 'fa-circle-check'],
    'limpieza'      => ['label' => 'En limpieza',      'clase' => 'is-info',   'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'En mantenimiento', 'clase' => 'is-warn',   'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',          'clase' => 'is-danger', 'icono' => 'fa-ban'],
];
$estadoActual = $estadoMeta[$estado] ?? $estadoMeta['disponible'];

$estadoTareaMeta = [
    'pendiente'  => ['label' => 'Pendiente',  'clase' => 'is-warn'],
    'asignada'   => ['label' => 'Asignada',   'clase' => 'is-info'],
    'en_proceso' => ['label' => 'En proceso', 'clase' => 'is-info'],
    'completada' => ['label' => 'Completada', 'clase' => 'is-ok'],
    'cancelada'  => ['label' => 'Cancelada',  'clase' => 'is-off'],
];
$estadoMantMeta = [
    'en_proceso' => ['label' => 'En proceso', 'clase' => 'is-warn'],
    'programado' => ['label' => 'Programado', 'clase' => 'is-info'],
    'completado' => ['label' => 'Completado', 'clase' => 'is-ok'],
    'cancelado'  => ['label' => 'Cancelado',  'clase' => 'is-off'],
];
?>

<?php
$subnav_section = 'habitaciones';
$subnav_active = 'areas';
include APP_PATH . '/views/partials/section_subnav.php';
?>

<style>
.arv {
    --ar-brand: var(--brand-primary, #1B2746);
    --ar-gold: var(--brand-accent, #BD9441);
    --ar-ivory: #F6F2EA; --ar-ivory-2: #FBF8F2;
    --ar-surface: #FFFFFF;
    --ar-border: color-mix(in srgb, var(--ar-brand) 7%, #E7E1D4);
    --ar-text: color-mix(in srgb, var(--ar-brand) 36%, #596474);
    --ar-muted: #828B99;
    --ar-heading: color-mix(in srgb, var(--ar-brand) 62%, #667284);
    --ar-ok: #1E9E63; --ar-ok-bg: #E7F4EC;
    --ar-warn: #C2841C; --ar-warn-bg: #FAF0DC;
    --ar-danger: #B4392B; --ar-danger-bg: #F8EAE5;
    --ar-info: #2F77E0; --ar-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--ar-text);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--ar-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--ar-ivory-2), var(--ar-ivory));
    padding: 16px 14px 90px;
}
.arv .arv-shell { max-width: 960px; margin: 0 auto; display: grid; gap: 14px; }
.arv .arv-card {
    background: var(--ar-surface);
    border: 1px solid var(--ar-border);
    border-radius: 16px;
    box-shadow: 0 10px 26px -20px color-mix(in srgb, var(--ar-brand) 45%, transparent);
    padding: 16px;
}
.arv .arv-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.arv .arv-hero h1 { margin: 0; font-size: 1.25rem; color: var(--ar-heading); font-weight: 700; }
.arv .arv-hero p { margin: 3px 0 0; font-size: .8rem; color: var(--ar-muted); }
.arv .arv-tipo-ico {
    display: inline-flex; align-items: center; justify-content: center;
    width: 44px; height: 44px; border-radius: 13px; flex: none; margin-right: 12px;
    background: color-mix(in srgb, var(--ar-gold) 14%, #FFF); color: color-mix(in srgb, var(--ar-gold) 80%, var(--ar-brand));
    font-size: 1.2rem;
}
.arv .arv-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 999px; font-size: .75rem; font-weight: 700; white-space: nowrap;
}
.arv .arv-chip.is-ok { background: var(--ar-ok-bg); color: var(--ar-ok); }
.arv .arv-chip.is-warn { background: var(--ar-warn-bg); color: var(--ar-warn); }
.arv .arv-chip.is-danger { background: var(--ar-danger-bg); color: var(--ar-danger); }
.arv .arv-chip.is-info { background: var(--ar-info-bg); color: var(--ar-info); }
.arv .arv-chip.is-off { background: #EEEDE9; color: var(--ar-muted); }

.arv .arv-acts { display: flex; gap: 8px; flex-wrap: wrap; }
.arv .arv-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 14px; border-radius: 12px; border: none; cursor: pointer; text-decoration: none;
    background: var(--ar-brand); color: #FFF; font-weight: 700; font-size: .82rem;
}
.arv .arv-btn.is-ghost { background: #FFF; color: var(--ar-heading); border: 1px solid var(--ar-border); }
.arv .arv-btn.is-ok { background: var(--ar-ok); }
.arv .arv-btn.is-warn { background: color-mix(in srgb, var(--ar-warn) 88%, var(--ar-brand)); }
.arv .arv-btn.is-danger { background: var(--ar-danger); }

.arv h2.arv-sub { margin: 0 0 10px; font-size: .95rem; color: var(--ar-heading); font-weight: 700; }
.arv .arv-panel { display: none; margin-top: 12px; border-top: 1px dashed var(--ar-border); padding-top: 14px; }
.arv .arv-panel.is-open { display: block; }
.arv .arv-form-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (max-width: 560px) { .arv .arv-form-grid { grid-template-columns: 1fr; } }
.arv label.arv-lbl { display: block; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: var(--ar-muted); font-weight: 700; margin-bottom: 4px; }
.arv .arv-input, .arv select.arv-input {
    width: 100%; padding: 10px 12px; border: 1px solid var(--ar-border); border-radius: 11px;
    font-size: .88rem; background: #FFF; color: var(--ar-heading);
}
.arv .arv-check { display: flex; align-items: center; gap: 9px; padding: 8px 10px; border: 1px solid var(--ar-border); border-radius: 10px; background: #FCFAF5; font-size: .84rem; color: var(--ar-heading); cursor: pointer; }
.arv .arv-check input { width: 16px; height: 16px; accent-color: var(--ar-brand); }
.arv .arv-personal { display: grid; gap: 7px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (max-width: 560px) { .arv .arv-personal { grid-template-columns: 1fr; } }

.arv .arv-hist { display: grid; gap: 8px; }
.arv .arv-hist-item { border: 1px solid var(--ar-border); border-radius: 12px; padding: 10px 12px; background: color-mix(in srgb, var(--ar-brand) 2%, #FCFAF5); }
.arv .arv-hist-top { display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.arv .arv-hist-top strong { color: var(--ar-heading); font-size: .84rem; font-weight: 700; }
.arv .arv-hist-meta { margin-top: 4px; font-size: .74rem; color: var(--ar-muted); }
.arv .arv-vacio { padding: 14px; text-align: center; color: var(--ar-muted); font-size: .82rem; }
</style>

<div class="arv">
    <div class="arv-shell">

        <section class="arv-card">
            <div class="arv-hero">
                <div style="display:flex;align-items:flex-start;">
                    <span class="arv-tipo-ico"><i class="fas <?= arv_safe($tipoMeta['icono']) ?>"></i></span>
                    <div>
                        <h1><?= arv_safe($area['nombre'] ?? '') ?></h1>
                        <p><?= arv_safe($tipoMeta['label']) ?> · <?= arv_safe($pisoTexto) ?><?= $estaActiva ? '' : ' · PAUSADA' ?></p>
                        <?php if (!empty($area['descripcion'])): ?>
                            <p style="margin-top:6px;"><?= arv_safe($area['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($estaActiva): ?>
                    <span class="arv-chip <?= $estadoActual['clase'] ?>"><i class="fas <?= $estadoActual['icono'] ?>"></i> <?= arv_safe($estadoActual['label']) ?></span>
                <?php else: ?>
                    <span class="arv-chip is-off"><i class="fas fa-pause"></i> Pausada</span>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($estaActiva): ?>
        <section class="arv-card">
            <h2 class="arv-sub"><i class="fas fa-bolt" style="color:var(--ar-gold);margin-right:6px;"></i>Acciones</h2>
            <div class="arv-acts">

                <?php if ($estado === 'disponible'): ?>
                    <form method="POST" action="<?= url('areas/' . $areaId . '/limpieza') ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="iniciar">
                        <button type="submit" class="arv-btn is-ghost"><i class="fas fa-broom" style="color:var(--ar-info);"></i> Mandar a limpieza</button>
                    </form>
                    <?php if ($puedeMantenimiento): ?>
                        <button type="button" class="arv-btn is-warn" onclick="arvTogglePanel('arvPanelMant')"><i class="fas fa-wrench"></i> Reportar mantenimiento</button>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/cerrar') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="arv-btn is-ghost"><i class="fas fa-ban" style="color:var(--ar-danger);"></i> Cerrar área</button>
                        </form>
                    <?php endif; ?>

                <?php elseif ($estado === 'limpieza'): ?>
                    <button type="button" class="arv-btn is-ok" onclick="arvTogglePanel('arvPanelLimpieza')"><i class="fas fa-check"></i> Marcar limpia</button>

                <?php elseif ($estado === 'mantenimiento'): ?>
                    <?php if ($puedeMantenimiento): ?>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/mantenimiento') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="finalizar">
                            <button type="submit" class="arv-btn is-ok"><i class="fas fa-check"></i> Finalizar mantenimiento</button>
                        </form>
                    <?php else: ?>
                        <span class="arv-vacio">En mantenimiento: lo finaliza quien tenga permiso de mantenimiento.</span>
                    <?php endif; ?>

                <?php elseif ($estado === 'cerrada'): ?>
                    <?php if ($puedeMantenimiento): ?>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/cerrar') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="arv-btn is-ok"><i class="fas fa-door-open"></i> Reabrir área</button>
                        </form>
                    <?php else: ?>
                        <span class="arv-vacio">Área cerrada: la reabre quien tenga permiso de mantenimiento.</span>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

            <?php if ($estado === 'limpieza'): ?>
            <form class="arv-panel" id="arvPanelLimpieza" method="POST" action="<?= url('areas/' . $areaId . '/limpieza') ?>" data-ms-no-summary="1">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="completar">
                <input type="hidden" name="personal_confirmado" value="1">
                <label class="arv-lbl">¿Quién hizo la limpieza?</label>
                <?php if (!empty($personalLimpieza)): ?>
                    <div class="arv-personal">
                        <?php foreach ($personalLimpieza as $t): ?>
                            <label class="arv-check">
                                <input type="checkbox" name="trabajador_ids[]" value="<?= (int)$t['id'] ?>">
                                <span><?= arv_safe($t['nombre_completo']) ?><?= !empty($t['rol_laboral']) ? ' · ' . arv_safe($t['rol_laboral']) : '' ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label class="arv-check" style="margin-top:8px;">
                    <input type="checkbox" name="sin_personal" value="1">
                    <span>Sin registrar personal</span>
                </label>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="arv-btn is-ok"><i class="fas fa-check"></i> Confirmar limpieza</button>
                    <button type="button" class="arv-btn is-ghost" onclick="arvTogglePanel('arvPanelLimpieza', false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($estado === 'disponible' && $puedeMantenimiento): ?>
            <form class="arv-panel" id="arvPanelMant" method="POST" action="<?= url('areas/' . $areaId . '/mantenimiento') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="iniciar">
                <div class="arv-form-grid">
                    <div>
                        <label class="arv-lbl">Motivo</label>
                        <input type="text" name="motivo" required maxlength="255" placeholder="Fuga en la bomba, pintura..." class="arv-input">
                    </div>
                    <div>
                        <label class="arv-lbl">Tipo</label>
                        <select name="tipo_mantenimiento" class="arv-input">
                            <?php foreach ($tiposMantenimiento as $tKey => $tLabel): ?>
                                <option value="<?= arv_safe($tKey) ?>" <?= $tKey === 'correctivo' ? 'selected' : '' ?>><?= arv_safe(is_array($tLabel) ? ($tLabel['label'] ?? $tKey) : $tLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="arv-lbl">Prioridad</label>
                        <select name="prioridad" class="arv-input">
                            <?php foreach ($prioridadesMantenimiento as $pKey => $pLabel): ?>
                                <option value="<?= arv_safe($pKey) ?>" <?= $pKey === 'media' ? 'selected' : '' ?>><?= arv_safe(is_array($pLabel) ? ($pLabel['label'] ?? $pKey) : $pLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="arv-btn is-warn"><i class="fas fa-wrench"></i> Iniciar mantenimiento</button>
                    <button type="button" class="arv-btn is-ghost" onclick="arvTogglePanel('arvPanelMant', false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <section class="arv-card">
            <h2 class="arv-sub"><i class="fas fa-broom" style="color:var(--ar-info);margin-right:6px;"></i>Historial de limpiezas</h2>
            <?php if (empty($historial['limpiezas'])): ?>
                <div class="arv-vacio">Sin limpiezas registradas todavía.</div>
            <?php else: ?>
                <div class="arv-hist">
                    <?php foreach ($historial['limpiezas'] as $l): ?>
                        <?php $lm = $estadoTareaMeta[$l['estado'] ?? ''] ?? ['label' => (string)($l['estado'] ?? ''), 'clase' => 'is-off']; ?>
                        <div class="arv-hist-item">
                            <div class="arv-hist-top">
                                <strong><?= arv_safe($l['titulo']) ?></strong>
                                <span class="arv-chip <?= $lm['clase'] ?>"><?= arv_safe($lm['label']) ?></span>
                            </div>
                            <div class="arv-hist-meta">
                                <?= arv_fecha($l['fecha_cierre'] ?? $l['fecha_programada'] ?? $l['created_at']) ?>
                                <?= !empty($l['notas_cierre']) ? ' · ' . arv_safe($l['notas_cierre']) : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="arv-card">
            <h2 class="arv-sub"><i class="fas fa-wrench" style="color:var(--ar-warn);margin-right:6px;"></i>Historial de mantenimientos</h2>
            <?php if (empty($historial['mantenimientos'])): ?>
                <div class="arv-vacio">Sin mantenimientos registrados todavía.</div>
            <?php else: ?>
                <div class="arv-hist">
                    <?php foreach ($historial['mantenimientos'] as $m): ?>
                        <?php $mm = $estadoMantMeta[$m['estado'] ?? ''] ?? ['label' => (string)($m['estado'] ?? ''), 'clase' => 'is-off']; ?>
                        <div class="arv-hist-item">
                            <div class="arv-hist-top">
                                <strong><?= arv_safe($m['motivo']) ?></strong>
                                <span class="arv-chip <?= $mm['clase'] ?>"><?= arv_safe($mm['label']) ?></span>
                            </div>
                            <div class="arv-hist-meta">
                                <?= arv_safe($m['tipo_mantenimiento']) ?> · <?= arv_fecha($m['fecha_inicio']) ?><?= !empty($m['fecha_fin']) ? ' → ' . arv_fecha($m['fecha_fin']) : '' ?>
                                <?= ($m['costo'] !== null && $m['costo'] !== '') ? ' · $' . number_format((float)$m['costo'], 2) : '' ?>
                                <?= !empty($m['proveedor']) ? ' · ' . arv_safe($m['proveedor']) : '' ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="arv-card" style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
            <a class="arv-btn is-ghost" href="<?= url('areas') ?>"><i class="fas fa-arrow-left"></i> Todas las áreas</a>
        </section>

    </div>
</div>

<script>
function arvTogglePanel(id, abrir) {
    var panel = document.getElementById(id);
    if (!panel) return;
    var abre = typeof abrir === 'boolean' ? abrir : !panel.classList.contains('is-open');
    panel.classList.toggle('is-open', abre);
    if (abre) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}
</script>
