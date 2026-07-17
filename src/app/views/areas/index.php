<?php
/**
 * Areas del hotel (bloque habitaciones y areas).
 * Mobile-first: alta rapida inline + tarjetas con estado accionable.
 */

if (!function_exists('ars_safe')) {
    function ars_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$areas = $areas ?? [];
$tipos = $tipos ?? [];
$conteoEstados = $conteo_estados ?? [];
$puedeGestionar = !empty($puede_gestionar);

$estadoMeta = [
    'disponible'    => ['label' => 'Disponible',       'clase' => 'is-ok',     'icono' => 'fa-circle-check'],
    'limpieza'      => ['label' => 'En limpieza',      'clase' => 'is-info',   'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'En mantenimiento', 'clase' => 'is-warn',   'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',          'clase' => 'is-danger', 'icono' => 'fa-ban'],
];
?>

<?php
$subnav_section = 'habitaciones';
$subnav_active = 'areas';
include APP_PATH . '/views/partials/section_subnav.php';
?>

<style>
.ars {
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
.ars .ars-shell { max-width: 960px; margin: 0 auto; display: grid; gap: 14px; }
.ars .ars-card {
    background: var(--ar-surface);
    border: 1px solid var(--ar-border);
    border-radius: 16px;
    box-shadow: 0 10px 26px -20px color-mix(in srgb, var(--ar-brand) 45%, transparent);
    padding: 16px;
}
.ars .ars-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ars .ars-head h1 { margin: 0; font-size: 1.2rem; color: var(--ar-heading); font-weight: 700; }
.ars .ars-head p { margin: 2px 0 0; font-size: .8rem; color: var(--ar-muted); }
.ars .ars-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 10px 14px; border-radius: 12px; border: none; cursor: pointer; text-decoration: none;
    background: var(--ar-brand); color: #FFF; font-weight: 700; font-size: .82rem;
}
.ars .ars-btn.is-ghost { background: #FFF; color: var(--ar-heading); border: 1px solid var(--ar-border); }

.ars .ars-kpis { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.ars .ars-kpi {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 999px; font-size: .74rem; font-weight: 700;
    border: 1px solid var(--ar-border); background: color-mix(in srgb, var(--ar-brand) 2%, #FCFAF5);
    color: var(--ar-heading);
}
.ars .ars-kpi b { font-size: .86rem; }
.ars .ars-kpi.is-ok b { color: var(--ar-ok); }
.ars .ars-kpi.is-info b { color: var(--ar-info); }
.ars .ars-kpi.is-warn b { color: var(--ar-warn); }
.ars .ars-kpi.is-danger b { color: var(--ar-danger); }

.ars .ars-grid { display: grid; gap: 10px; }
@media (min-width: 700px) { .ars .ars-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.ars .ars-item { border: 1px solid var(--ar-border); border-radius: 14px; padding: 13px 14px; background: color-mix(in srgb, var(--ar-brand) 2%, #FCFAF5); }
.ars .ars-item.is-cerrada { border-color: color-mix(in srgb, var(--ar-danger) 40%, var(--ar-border)); background: color-mix(in srgb, var(--ar-danger) 4%, #FCFAF5); }
.ars .ars-item.is-pausada { opacity: .62; }
.ars .ars-item-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; }
.ars .ars-item-top strong { color: var(--ar-heading); font-size: .95rem; font-weight: 700; }
.ars .ars-item-top small { display: block; margin-top: 1px; font-size: .75rem; color: var(--ar-muted); }
.ars .ars-tipo-ico {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: 10px; flex: none; margin-right: 10px;
    background: color-mix(in srgb, var(--ar-gold) 14%, #FFF); color: color-mix(in srgb, var(--ar-gold) 80%, var(--ar-brand));
    font-size: .95rem;
}
.ars .ars-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; white-space: nowrap;
}
.ars .ars-chip.is-ok { background: var(--ar-ok-bg); color: var(--ar-ok); }
.ars .ars-chip.is-warn { background: var(--ar-warn-bg); color: var(--ar-warn); }
.ars .ars-chip.is-danger { background: var(--ar-danger-bg); color: var(--ar-danger); }
.ars .ars-chip.is-info { background: var(--ar-info-bg); color: var(--ar-info); }
.ars .ars-chip.is-off { background: #EEEDE9; color: var(--ar-muted); }

.ars .ars-item-meta { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 9px; font-size: .76rem; color: var(--ar-muted); }
.ars .ars-item-meta b { color: var(--ar-heading); font-weight: 700; }
.ars .ars-item-acts { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 11px; }
.ars .ars-mini {
    display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer;
    padding: 7px 11px; border-radius: 10px; font-size: .76rem; font-weight: 700;
    border: 1px solid var(--ar-border); color: var(--ar-heading); background: #FFF;
}
.ars .ars-mini:hover { border-color: color-mix(in srgb, var(--ar-gold) 45%, var(--ar-border)); }

.ars .ars-vacio { padding: 26px 14px; text-align: center; color: var(--ar-muted); font-size: .86rem; }
.ars .ars-vacio i { display: block; font-size: 1.6rem; margin-bottom: 8px; color: var(--ar-gold); }

.ars .ars-form { display: none; margin-top: 12px; border-top: 1px dashed var(--ar-border); padding-top: 14px; }
.ars .ars-form.is-open { display: block; }
.ars .ars-form-grid { display: grid; gap: 10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
@media (max-width: 560px) { .ars .ars-form-grid { grid-template-columns: 1fr; } }
.ars label.ars-lbl { display: block; font-size: .7rem; letter-spacing: .08em; text-transform: uppercase; color: var(--ar-muted); font-weight: 700; margin-bottom: 4px; }
.ars .ars-input, .ars select.ars-input {
    width: 100%; padding: 10px 12px; border: 1px solid var(--ar-border); border-radius: 11px;
    font-size: .88rem; background: #FFF; color: var(--ar-heading);
}
</style>

<div class="ars">
    <div class="ars-shell">

        <section class="ars-card">
            <div class="ars-head">
                <div>
                    <h1><i class="fas fa-map-location-dot" style="color:var(--ar-gold);margin-right:8px;"></i>Áreas del hotel</h1>
                    <p>Alberca, lobby, jardín... el mapa de tu hotel más allá de los cuartos.</p>
                </div>
                <?php if ($puedeGestionar): ?>
                    <button type="button" class="ars-btn" onclick="arsToggleForm()">
                        <i class="fas fa-plus"></i> Nueva área
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($areas)): ?>
            <div class="ars-kpis">
                <?php foreach ($estadoMeta as $estadoKey => $meta): ?>
                    <span class="ars-kpi <?= $meta['clase'] ?>">
                        <i class="fas <?= $meta['icono'] ?>"></i>
                        <?= ars_safe($meta['label']) ?> <b><?= (int)($conteoEstados[$estadoKey] ?? 0) ?></b>
                    </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($puedeGestionar): ?>
            <form class="ars-form" id="arsForm" method="POST" action="<?= url('areas/guardar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="" id="arsFormId">
                <div class="ars-form-grid">
                    <div>
                        <label class="ars-lbl">Nombre del área</label>
                        <input type="text" name="nombre" id="arsFormNombre" required maxlength="120" placeholder="Alberca principal" class="ars-input">
                    </div>
                    <div>
                        <label class="ars-lbl">Tipo</label>
                        <select name="tipo" id="arsFormTipo" class="ars-input">
                            <?php foreach ($tipos as $tipoKey => $tipoMeta): ?>
                                <option value="<?= ars_safe($tipoKey) ?>"><?= ars_safe($tipoMeta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="ars-lbl">Piso (vacío = exterior / planta baja)</label>
                        <input type="number" name="piso" id="arsFormPiso" min="0" max="60" placeholder="1" class="ars-input">
                    </div>
                    <div>
                        <label class="ars-lbl">Descripción (opcional)</label>
                        <input type="text" name="descripcion" id="arsFormDescripcion" maxlength="500" placeholder="Junto a recepción, horario 8-20 h..." class="ars-input">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="ars-btn"><i class="fas fa-check"></i> Guardar área</button>
                    <button type="button" class="ars-btn is-ghost" onclick="arsToggleForm(false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>
        </section>

        <section class="ars-card">
            <?php if (empty($areas)): ?>
                <div class="ars-vacio">
                    <i class="fas fa-map-location-dot"></i>
                    <strong style="display:block;color:var(--ar-heading);font-weight:700;">Aún no registras áreas</strong>
                    Da de alta tu alberca, lobby o jardín y contrólalos como un cuarto más: limpieza, mantenimiento y cierres.
                </div>
            <?php else: ?>
                <div class="ars-grid">
                    <?php foreach ($areas as $a): ?>
                        <?php
                        $estaActiva = (int)($a['activa'] ?? 1) === 1;
                        $estado = $estadoMeta[$a['estado'] ?? 'disponible'] ?? $estadoMeta['disponible'];
                        $tipoMeta = $tipos[$a['tipo'] ?? 'otra'] ?? ['label' => 'Otra área', 'icono' => 'fa-location-dot'];
                        $pisoTexto = ($a['piso'] === null || $a['piso'] === '') ? 'Exterior / PB' : 'Piso ' . (int)$a['piso'];
                        $claseItem = !$estaActiva ? 'is-pausada' : ((($a['estado'] ?? '') === 'cerrada') ? 'is-cerrada' : '');
                        ?>
                        <div class="ars-item <?= $claseItem ?>">
                            <div class="ars-item-top">
                                <div style="display:flex;align-items:flex-start;">
                                    <span class="ars-tipo-ico"><i class="fas <?= ars_safe($tipoMeta['icono']) ?>"></i></span>
                                    <div>
                                        <strong><?= ars_safe($a['nombre']) ?></strong>
                                        <small><?= ars_safe($tipoMeta['label']) ?> · <?= ars_safe($pisoTexto) ?></small>
                                    </div>
                                </div>
                                <?php if ($estaActiva): ?>
                                    <span class="ars-chip <?= $estado['clase'] ?>"><i class="fas <?= $estado['icono'] ?>"></i> <?= ars_safe($estado['label']) ?></span>
                                <?php else: ?>
                                    <span class="ars-chip is-off"><i class="fas fa-pause"></i> Pausada</span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($a['descripcion']) || !empty($a['tarea_limpieza_id']) || !empty($a['mantenimiento_abierto_id'])): ?>
                            <div class="ars-item-meta">
                                <?php if (!empty($a['tarea_limpieza_id'])): ?>
                                    <span><i class="fas fa-broom" style="color:var(--ar-info);"></i> <b>Limpieza pendiente</b></span>
                                <?php endif; ?>
                                <?php if (!empty($a['mantenimiento_abierto_id'])): ?>
                                    <span><i class="fas fa-person-digging" style="color:var(--ar-warn);"></i> <b>En servicio</b></span>
                                <?php endif; ?>
                                <?php if (!empty($a['descripcion'])): ?>
                                    <span><?= ars_safe(mb_strimwidth((string)$a['descripcion'], 0, 90, '…')) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <div class="ars-item-acts">
                                <a class="ars-mini" href="<?= url('areas/' . (int)$a['id']) ?>">
                                    <i class="fas fa-arrow-up-right-from-square"></i> Ver y operar
                                </a>
                                <?php if ($puedeGestionar): ?>
                                <button type="button" class="ars-mini"
                                        onclick='arsEditar(<?= json_encode([
                                            'id' => (int)$a['id'],
                                            'nombre' => (string)$a['nombre'],
                                            'tipo' => (string)($a['tipo'] ?? 'otra'),
                                            'piso' => ($a['piso'] === null ? '' : (string)(int)$a['piso']),
                                            'descripcion' => (string)($a['descripcion'] ?? ''),
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                    <i class="fas fa-pen"></i> Editar
                                </button>
                                <form method="POST" action="<?= url('areas/' . (int)$a['id'] . '/toggle') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="ars-mini">
                                        <i class="fas <?= $estaActiva ? 'fa-pause' : 'fa-play' ?>"></i>
                                        <?= $estaActiva ? 'Pausar' : 'Reactivar' ?>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<script>
function arsToggleForm(abrir) {
    var form = document.getElementById('arsForm');
    if (!form) return;
    var abre = typeof abrir === 'boolean' ? abrir : !form.classList.contains('is-open');
    form.classList.toggle('is-open', abre);
    if (abre) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        form.reset();
        document.getElementById('arsFormId').value = '';
    }
}

function arsEditar(a) {
    arsToggleForm(true);
    document.getElementById('arsFormId').value = a.id || '';
    document.getElementById('arsFormNombre').value = a.nombre || '';
    document.getElementById('arsFormTipo').value = a.tipo || 'otra';
    document.getElementById('arsFormPiso').value = a.piso || '';
    document.getElementById('arsFormDescripcion').value = a.descripcion || '';
}
</script>
