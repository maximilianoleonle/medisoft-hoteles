<?php
/**
 * Activos del hotel con mantenimiento preventivo (bloque mantenimiento).
 * Mobile-first: alta rapida y semaforo de vencimiento.
 */

// Areas del hotel para el selector. Vacio = el hotel no tiene areas (o el bloque
// no esta migrado) y el campo simplemente no se pinta.
$mactAreas = is_array($areas ?? null) ? $areas : [];

if (!function_exists('mact_safe')) {
    function mact_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mact_fecha')) {
    function mact_fecha($value): string
    {
        if (empty($value)) {
            return '-';
        }
        $ts = strtotime((string)$value);
        return $ts ? date('d/m/Y', $ts) : '-';
    }
}

$activos = $activos ?? [];
$habitaciones = $habitaciones ?? [];
$puedeGestionar = !empty($puede_gestionar);

$vencMeta = [
    'vencido' => ['label' => 'Vencido', 'clase' => 'is-danger', 'icono' => 'fa-triangle-exclamation'],
    'por_vencer' => ['label' => 'Por vencer', 'clase' => 'is-warn', 'icono' => 'fa-hourglass-half'],
    'al_dia' => ['label' => 'Al día', 'clase' => 'is-ok', 'icono' => 'fa-circle-check'],
    'sin_programa' => ['label' => 'Sin programa', 'clase' => 'is-info', 'icono' => 'fa-circle-question'],
    'inactivo' => ['label' => 'Pausado', 'clase' => 'is-off', 'icono' => 'fa-pause'],
];

$hayVencidos = false;
foreach ($activos as $a) {
    if (($a['vencimiento'] ?? '') === 'vencido') {
        $hayVencidos = true;
        break;
    }
}
?>

<style>
.mact {
    --ma-brand: var(--brand-primary, #1B2746);
    --ma-gold: var(--brand-accent, #BD9441);
    --ma-ivory: #F5F5F7; --ma-ivory-2: #FAFAFC;
    --ma-surface: #FFFFFF;
    --ma-border: color-mix(in srgb, var(--ma-brand) 7%, #E7E1D4);
    --ma-text: color-mix(in srgb, var(--ma-brand) 36%, #596474);
    --ma-muted: #828B99;
    --ma-heading: color-mix(in srgb, var(--ma-brand) 62%, #667284);
    --ma-ok: #1E9E63; --ma-ok-bg: #E7F4EC;
    --ma-warn: #C2841C; --ma-warn-bg: #FAF0DC;
    --ma-danger: #B4392B; --ma-danger-bg: #F8EAE5;
    --ma-info: #2F77E0; --ma-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--ma-text);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    
    padding: 16px 14px 90px;
}
.mact .mact-shell { max-width: 960px; margin: 0 auto; display: grid; gap: 14px; }
.mact .mact-card {
    background: var(--ma-surface);
    border: 1px solid var(--ma-border);
    border-radius: 16px;
    box-shadow: 0 10px 26px -20px color-mix(in srgb, var(--ma-brand) 45%, transparent);
    padding: 16px;
}
.mact .mact-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.mact .mact-head h1 { margin: 0; font-size: 1.2rem; color: var(--ma-heading); font-weight: 700; }
.mact .mact-head p { margin: 2px 0 0; font-size: .8rem; color: var(--ma-muted); }
.mact .mact-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 14px; border-radius: 12px; border: none; cursor: pointer; text-decoration: none;
    background: var(--ma-brand); color: #FFF; font-weight: 700; font-size: .82rem;
}
.mact .mact-btn.is-ghost { background: #FFF; color: var(--ma-heading); border: 1px solid var(--ma-border); }
.mact .mact-btn.is-gold { background: color-mix(in srgb, var(--ma-gold) 88%, var(--ma-brand)); }

.mact .mact-grid { display: grid; gap: 10px; }
@media (min-width: 700px) { .mact .mact-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.mact .mact-item { border: 1px solid var(--ma-border); border-radius: 14px; padding: 13px 14px; background: color-mix(in srgb, var(--ma-brand) 2%, #F5F5F7); }
.mact .mact-item.is-vencido { border-color: color-mix(in srgb, var(--ma-danger) 40%, var(--ma-border)); background: color-mix(in srgb, var(--ma-danger) 4%, #F5F5F7); }
.mact .mact-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.mact .mact-item-top strong { color: var(--ma-heading); font-size: .95rem; font-weight: 700; }
.mact .mact-item-top small { display: block; margin-top: 1px; font-size: .75rem; color: var(--ma-muted); }
.mact .mact-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap;
}
.mact .mact-chip.is-ok { background: var(--ma-ok-bg); color: var(--ma-ok); }
.mact .mact-chip.is-warn { background: var(--ma-warn-bg); color: var(--ma-warn); }
.mact .mact-chip.is-danger { background: var(--ma-danger-bg); color: var(--ma-danger); }
.mact .mact-chip.is-info { background: var(--ma-info-bg); color: var(--ma-info); }
.mact .mact-chip.is-off { background: #EEEDE9; color: var(--ma-muted); }

.mact .mact-item-meta { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 9px; font-size: .76rem; color: var(--ma-muted); }
.mact .mact-item-meta b { color: var(--ma-heading); font-weight: 700; }
.mact .mact-item-acts { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 11px; }
.mact .mact-mini {
    display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer;
    padding: 7px 11px; border-radius: 10px; font-size: .76rem; font-weight: 700;
    border: 1px solid var(--ma-border); color: var(--ma-heading); background: #FFF;
}
.mact .mact-mini:hover { border-color: color-mix(in srgb, var(--ma-gold) 45%, var(--ma-border)); }

.mact .mact-vacio { padding: 26px 14px; text-align: center; color: var(--ma-muted); font-size: .86rem; }
.mact .mact-vacio i { display: block; font-size: 1.6rem; margin-bottom: 8px; color: var(--ma-gold); }

.mact .mact-form { display: none; margin-top: 12px; border-top: 1px dashed var(--ma-border); padding-top: 14px; }
.mact .mact-form.is-open { display: block; }
.mact .mact-form-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (max-width: 560px) { .mact .mact-form-grid { grid-template-columns: 1fr; } }
.mact label.mact-lbl { display: block; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: var(--ma-muted); font-weight: 700; margin-bottom: 4px; }
.mact .mact-hint { font-size: .74rem; color: var(--ma-muted); margin: 5px 0 0; line-height: 1.4; }
.mact .mact-area-link { color: inherit; text-decoration: underline; text-underline-offset: 2px; }
.mact .mact-area-link:hover { color: var(--ma-brand, inherit); }
.mact .mact-input, .mact select.mact-input {
    width: 100%; padding: 10px 12px; border: 1px solid var(--ma-border); border-radius: 11px;
    font-size: .88rem; background: #FFF; color: var(--ma-heading);
}
</style>

<div class="mact">
    <div class="mact-shell">

        <section class="mact-card">
            <div class="mact-head">
                <div>
                    <h1><i class="fas fa-toolbox" style="color:var(--ma-gold);margin-right:8px;"></i>Equipos con servicio programado</h1>
                    <p>Boiler, bombas, aires... con servicio programado. El sistema genera el mantenimiento al vencer.</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if ($puedeGestionar && $hayVencidos): ?>
                        <form method="POST" action="<?= url('mantenimientos/activos/generar') ?>" style="display:inline;"
                              onsubmit="return confirm('Se crearán las órdenes de mantenimiento de los equipos con servicio vencido. ¿Continuar?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="mact-btn is-gold">
                                <i class="fas fa-bolt"></i> Crear mantenimientos vencidos
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if ($puedeGestionar): ?>
                        <button type="button" class="mact-btn" onclick="mactToggleForm()">
                            <i class="fas fa-plus"></i> Nuevo activo
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Los activos cuelgan de habitaciones y areas: se navegan como parte
            // de esa seccion (Mapa · Habitaciones · Áreas · Activos).
            $subnav_section = 'habitaciones';
            $subnav_active = 'activos';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>

            <?php if ($puedeGestionar): ?>
            <form class="mact-form" id="mactForm" method="POST" action="<?= url('mantenimientos/activos/guardar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="" id="mactFormId">
                <div class="mact-form-grid">
                    <div>
                        <label class="mact-lbl">Nombre del activo</label>
                        <input type="text" name="nombre" id="mactFormNombre" required maxlength="160" placeholder="Boiler principal" class="mact-input">
                    </div>
                    <div>
                        <label class="mact-lbl">Ubicaci&oacute;n libre (opcional)</label>
                        <input type="text" name="ubicacion" id="mactFormUbicacion" maxlength="160" placeholder="Azotea, cuarto de maquinas..." class="mact-input">
                    </div>
                    <div>
                        <label class="mact-lbl">Habitaci&oacute;n (opcional)</label>
                        <select name="habitacion_id" id="mactFormHabitacion" class="mact-input">
                            <option value="">Sin habitaci&oacute;n (general)</option>
                            <?php foreach ($habitaciones as $hab): ?>
                                <option value="<?= (int)$hab['id'] ?>">Hab. <?= mact_safe($hab['numero']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($mactAreas)): ?>
                    <div>
                        <label class="mact-lbl">&Aacute;rea (opcional)</label>
                        <select name="area_id" id="mactFormArea" class="mact-input">
                            <option value="">Sin &aacute;rea</option>
                            <?php foreach ($mactAreas as $mactArea): ?>
                                <option value="<?= (int)$mactArea['id'] ?>"><?= mact_safe($mactArea['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mact-hint">El activo vive en una habitaci&oacute;n <strong>o</strong> en un &aacute;rea, no en las dos.</p>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label class="mact-lbl">Servicio cada (d&iacute;as)</label>
                        <input type="number" name="periodicidad_dias" id="mactFormPeriodicidad" required min="1" max="3650" value="180" placeholder="180" class="mact-input">
                    </div>
                    <div>
                        <label class="mact-lbl">&Uacute;ltimo servicio (opcional)</label>
                        <input type="date" name="ultimo_servicio" id="mactFormUltimo" class="mact-input">
                    </div>
                    <div>
                        <label class="mact-lbl">Pr&oacute;ximo servicio (se calcula si lo dejas vac&iacute;o)</label>
                        <input type="date" name="proximo_servicio" id="mactFormProximo" class="mact-input">
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label class="mact-lbl">Notas (opcional)</label>
                    <input type="text" name="notas" id="mactFormNotas" maxlength="500" placeholder="Marca, capacidad, proveedor de confianza..." class="mact-input">
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="mact-btn"><i class="fas fa-check"></i> Guardar activo</button>
                    <button type="button" class="mact-btn is-ghost" onclick="mactToggleForm(false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>
        </section>

        <section class="mact-card">
            <?php if (empty($activos)): ?>
                <div class="mact-vacio">
                    <i class="fas fa-fire-burner"></i>
                    <strong style="display:block;color:var(--ma-heading);font-weight:700;">Sin activos registrados</strong>
                    Registra tu boiler, bombas o aires y el preventivo se programa solo.
                </div>
            <?php else: ?>
                <div class="mact-grid">
                    <?php foreach ($activos as $a): ?>
                        <?php
                        $venc = $vencMeta[$a['vencimiento'] ?? 'sin_programa'] ?? $vencMeta['sin_programa'];
                        // Orden de precedencia: habitacion > area > texto libre.
                        // Habitacion y area son excluyentes (lo impone el controlador).
                        if (trim((string)($a['habitacion_numero'] ?? '')) !== '') {
                            $ubic = 'Hab. ' . $a['habitacion_numero'];
                        } elseif (trim((string)($a['area_nombre'] ?? '')) !== '') {
                            $ubic = (string)$a['area_nombre'];
                        } else {
                            $ubic = trim((string)($a['ubicacion'] ?? '')) ?: 'Instalaciones generales';
                        }
                        $esDeArea = trim((string)($a['area_nombre'] ?? '')) !== '';
                        ?>
                        <div class="mact-item <?= ($a['vencimiento'] ?? '') === 'vencido' ? 'is-vencido' : '' ?>">
                            <div class="mact-item-top">
                                <div>
                                    <strong><?= mact_safe($a['nombre']) ?></strong>
                                    <small><i class="fas <?= $esDeArea ? 'fa-map-location-dot' : 'fa-location-dot' ?>"></i> <?php if ($esDeArea): ?><a class="mact-area-link" href="<?= url('areas/' . (int)$a['area_id']) ?>"><?= mact_safe($ubic) ?></a><?php else: ?><?= mact_safe($ubic) ?><?php endif; ?> · cada <?= (int)$a['periodicidad_dias'] ?> d&iacute;as</small>
                                </div>
                                <span class="mact-chip <?= $venc['clase'] ?>"><i class="fas <?= $venc['icono'] ?>"></i> <?= mact_safe($venc['label']) ?></span>
                            </div>
                            <div class="mact-item-meta">
                                <span>&Uacute;ltimo: <b><?= mact_fecha($a['ultimo_servicio'] ?? null) ?></b></span>
                                <span>Pr&oacute;ximo: <b><?= mact_fecha($a['proximo_servicio'] ?? null) ?></b></span>
                                <?php if (!empty($a['mantenimiento_abierto_id'])): ?>
                                    <span><i class="fas fa-person-digging" style="color:var(--ma-warn);"></i> <b>En servicio</b></span>
                                <?php endif; ?>
                            </div>
                            <div class="mact-item-acts">
                                <a class="mact-mini" href="<?= url('mantenimientos/activos/' . (int)$a['id']) ?>">
                                    <i class="fas fa-clock-rotate-left"></i> Historial y costo
                                </a>
                                <?php if (!empty($a['mantenimiento_abierto_id'])): ?>
                                    <a class="mact-mini" href="<?= url('mantenimientos/' . (int)$a['mantenimiento_abierto_id']) ?>">
                                        <i class="fas fa-wrench"></i> Ver servicio abierto
                                    </a>
                                <?php endif; ?>
                                <?php if ($puedeGestionar): ?>
                                    <button type="button" class="mact-mini"
                                            onclick='mactEditar(<?= json_encode([
                                                'id' => (int)$a['id'],
                                                'nombre' => (string)$a['nombre'],
                                                'ubicacion' => (string)($a['ubicacion'] ?? ''),
                                                'habitacion_id' => (int)($a['habitacion_id'] ?? 0),
                                                'periodicidad_dias' => (int)$a['periodicidad_dias'],
                                                'ultimo_servicio' => substr((string)($a['ultimo_servicio'] ?? ''), 0, 10),
                                                'proximo_servicio' => substr((string)($a['proximo_servicio'] ?? ''), 0, 10),
                                                'notas' => (string)($a['notas'] ?? ''),
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                    <form method="POST" action="<?= url('mantenimientos/activos/' . (int)$a['id'] . '/toggle') ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="mact-mini">
                                            <i class="fas <?= (int)($a['activo'] ?? 1) === 1 ? 'fa-pause' : 'fa-play' ?>"></i>
                                            <?= (int)($a['activo'] ?? 1) === 1 ? 'Pausar' : 'Reactivar' ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="mact-card" style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end;">
            <a class="mact-mini" href="<?= url('reportes/mantenimiento') ?>"><i class="fas fa-chart-column"></i> Reporte de mantenimiento</a>
            <a class="mact-mini" href="<?= url('tareas') ?>"><i class="fas fa-list-check"></i> Tareas operativas</a>
        </section>

    </div>
</div>

<script>
function mactToggleForm(abrir) {
    var form = document.getElementById('mactForm');
    if (!form) return;
    var abre = typeof abrir === 'boolean' ? abrir : !form.classList.contains('is-open');
    form.classList.toggle('is-open', abre);
    if (abre) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        form.reset();
        document.getElementById('mactFormId').value = '';
    }
}

function mactEditar(a) {
    mactToggleForm(true);
    document.getElementById('mactFormId').value = a.id || '';
    document.getElementById('mactFormNombre').value = a.nombre || '';
    document.getElementById('mactFormUbicacion').value = a.ubicacion || '';
    document.getElementById('mactFormHabitacion').value = a.habitacion_id > 0 ? String(a.habitacion_id) : '';
    var mactArea = document.getElementById('mactFormArea');
    if (mactArea) { mactArea.value = a.area_id > 0 ? String(a.area_id) : ''; }
    document.getElementById('mactFormPeriodicidad').value = a.periodicidad_dias || 180;
    document.getElementById('mactFormUltimo').value = a.ultimo_servicio || '';
    document.getElementById('mactFormProximo').value = a.proximo_servicio || '';
    document.getElementById('mactFormNotas').value = a.notas || '';
}

/* Habitacion y area son excluyentes: elegir una limpia la otra en el acto, para
   que el error del servidor ("elige una, no las dos") no llegue nunca. */
(function () {
    var hab = document.getElementById('mactFormHabitacion');
    var area = document.getElementById('mactFormArea');
    if (!hab || !area) return;
    hab.addEventListener('change', function () { if (hab.value) area.value = ''; });
    area.addEventListener('change', function () { if (area.value) hab.value = ''; });
})();
</script>
