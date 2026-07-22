<?php
/**
 * Areas del hotel (bloque habitaciones y areas).
 * Lenguaje visual calcado de habitaciones/index.php: header glass con la
 * subnav integrada, fichas stat con numero grande (filtran al tocar) y grid
 * de tarjetas boutique. Contratos intactos: form inline arsForm* (alta y
 * edicion por hidden id), toggle activa, gates por $puede_gestionar.
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
    'disponible'    => ['label' => 'Disponible',       'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
    'limpieza'      => ['label' => 'En limpieza',      'clase' => 'st-clean', 'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'Mantenimiento',    'clase' => 'st-warn',  'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',          'clase' => 'st-off',   'icono' => 'fa-ban'],
];
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="arx">

    <div class="arx-header">
        <div class="arx-header-shell">
            <div class="arx-header-row">
                <div class="arx-header-id">
                    <span class="arx-header-ico"><i class="fas fa-map-location-dot"></i></span>
                    <div style="min-width:0;">
                        <h1>Áreas del hotel</h1>
                        <p class="arx-header-sub">Alberca, lobby, jardín... el mapa de tu hotel más allá de los cuartos</p>
                    </div>
                </div>
                <?php if ($puedeGestionar): ?>
                <div class="arx-header-acts">
                    <button type="button" class="arx-btn" onclick="arsToggleForm()">
                        <i class="fas fa-plus-circle"></i> <span>Nueva área</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $subnav_section = 'habitaciones';
            $subnav_active = 'areas';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="arx-shell">

        <?php if (!empty($areas)): ?>
        <div class="arx-stats" style="--ax-stats-n:4;" id="arsStats">
            <?php foreach ($estadoMeta as $estadoKey => $meta): ?>
            <div class="arx-stat <?= $meta['clase'] ?>" data-estado="<?= $estadoKey ?>" role="button" tabindex="0"
                 onclick="arsFiltrar(this)" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();arsFiltrar(this);}"
                 title="Filtrar áreas: <?= ars_safe($meta['label']) ?>">
                <span class="arx-stat-ic"><i class="fas <?= $meta['icono'] ?>"></i></span>
                <span class="arx-stat-n"><?= (int)($conteoEstados[$estadoKey] ?? 0) ?></span>
                <span class="arx-stat-l"><?= ars_safe($meta['label']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($puedeGestionar): ?>
        <section class="arx-card" id="arsFormCard" style="margin-bottom:14px; <?= empty($areas) ? '' : 'display:none;' ?>">
            <form id="arsForm" method="POST" action="<?= url('areas/guardar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="" id="arsFormId">
                <div class="arx-form-grid">
                    <div>
                        <label class="arx-lbl">Nombre del área</label>
                        <input type="text" name="nombre" id="arsFormNombre" required maxlength="120" placeholder="Alberca principal" class="arx-input">
                    </div>
                    <div>
                        <label class="arx-lbl">Tipo</label>
                        <select name="tipo" id="arsFormTipo" class="arx-input">
                            <?php foreach ($tipos as $tipoKey => $tipoMeta): ?>
                                <option value="<?= ars_safe($tipoKey) ?>"><?= ars_safe($tipoMeta['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="arx-lbl">Piso (vacío = exterior / planta baja)</label>
                        <input type="number" name="piso" id="arsFormPiso" min="0" max="60" placeholder="1" class="arx-input">
                    </div>
                    <div>
                        <label class="arx-lbl">Descripción (opcional)</label>
                        <input type="text" name="descripcion" id="arsFormDescripcion" maxlength="500" placeholder="Junto a recepción, horario 8-20 h..." class="arx-input">
                    </div>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="arx-btn"><i class="fas fa-check"></i> Guardar área</button>
                    <button type="button" class="arx-btn is-outline" onclick="arsToggleForm(false)">Cancelar</button>
                </div>
            </form>
        </section>
        <?php endif; ?>

        <?php if (empty($areas)): ?>
            <section class="arx-card">
                <div class="arx-vacio">
                    <i class="fas fa-map-location-dot"></i>
                    <strong style="display:block;color:var(--ax-primary);font-weight:700;">Aún no registras áreas</strong>
                    Da de alta tu alberca, lobby o jardín y contrólalos como un cuarto más: limpieza, mantenimiento y cierres.
                </div>
            </section>
        <?php else: ?>
            <div class="arx-grid" id="arsGrid">
                <?php foreach ($areas as $a): ?>
                    <?php
                    $estaActiva = (int)($a['activa'] ?? 1) === 1;
                    $estadoKey = (string)($a['estado'] ?? 'disponible');
                    $estado = $estadoMeta[$estadoKey] ?? $estadoMeta['disponible'];
                    $tipoMeta = $tipos[$a['tipo'] ?? 'otra'] ?? ['label' => 'Otra área', 'icono' => 'fa-location-dot'];
                    $pisoTexto = ($a['piso'] === null || $a['piso'] === '') ? 'Exterior / PB' : 'Piso ' . (int)$a['piso'];
                    ?>
                    <div class="arx-item <?= $estaActiva ? '' : 'is-pausada' ?>" data-estado="<?= ars_safe($estadoKey) ?>">
                        <div class="arx-item-top">
                            <div class="arx-item-id">
                                <span class="arx-tipo-ico"><i class="fas <?= ars_safe($tipoMeta['icono']) ?>"></i></span>
                                <div style="min-width:0;">
                                    <strong><?= ars_safe($a['nombre']) ?></strong>
                                    <small><?= ars_safe($tipoMeta['label']) ?> · <?= ars_safe($pisoTexto) ?></small>
                                </div>
                            </div>
                            <?php if ($estaActiva): ?>
                                <span class="arx-chip <?= $estado['clase'] ?>"><i class="fas <?= $estado['icono'] ?>"></i> <?= ars_safe($estado['label']) ?></span>
                            <?php else: ?>
                                <span class="arx-chip st-pause"><i class="fas fa-pause"></i> Pausada</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($a['descripcion']) || !empty($a['tarea_limpieza_id']) || !empty($a['mantenimiento_abierto_id'])): ?>
                        <div class="arx-item-meta">
                            <?php if (!empty($a['tarea_limpieza_id'])): ?>
                                <span><i class="fas fa-broom" style="color:var(--c-cleaning);"></i> <b>Limpieza pendiente</b></span>
                            <?php endif; ?>
                            <?php if (!empty($a['mantenimiento_abierto_id'])): ?>
                                <span><i class="fas fa-person-digging" style="color:var(--c-maint);"></i> <b>En servicio</b></span>
                            <?php endif; ?>
                            <?php if (!empty($a['descripcion'])): ?>
                                <span><?= ars_safe(mb_strimwidth((string)$a['descripcion'], 0, 90, '…')) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <div class="arx-item-acts">
                            <a class="arx-mini" href="<?= url('areas/' . (int)$a['id']) ?>" data-prefetch>
                                <i class="fas fa-arrow-up-right-from-square"></i> Ver y operar
                            </a>
                            <?php if ($puedeGestionar): ?>
                            <button type="button" class="arx-mini"
                                    onclick='arsEditar(<?= json_encode([
                                        'id' => (int)$a['id'],
                                        'nombre' => (string)$a['nombre'],
                                        'tipo' => (string)($a['tipo'] ?? 'otra'),
                                        'piso' => ($a['piso'] === null ? '' : (string)(int)$a['piso']),
                                        'descripcion' => (string)($a['descripcion'] ?? ''),
                                    ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                <i class="fas fa-pen"></i> Editar
                            </button>
                            <form method="POST" action="<?= url('areas/' . (int)$a['id'] . '/toggle') ?>" style="display:inline;"
                                  <?= $estaActiva ? 'onsubmit="return confirm(\'El área dejará de aparecer en la operación del hotel hasta que la reactives. ¿Pausar ' . htmlspecialchars((string)($a['nombre'] ?? 'el área'), ENT_QUOTES, 'UTF-8') . '?\');"' : '' ?>>
                                <?= csrf_field() ?>
                                <button type="submit" class="arx-mini">
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

    </div>
</div>

<script>
function arsToggleForm(abrir) {
    var card = document.getElementById('arsFormCard');
    var form = document.getElementById('arsForm');
    if (!card || !form) return;
    var abre = typeof abrir === 'boolean' ? abrir : card.style.display === 'none';
    card.style.display = abre ? '' : 'none';
    if (abre) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        var nombre = document.getElementById('arsFormNombre');
        if (nombre) { nombre.focus(); }
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

// Fichas stat como filtro (calco del patron hbSetEstado de habitaciones):
// tocar una ficha atenua las tarjetas de otros estados; tocarla de nuevo limpia.
function arsFiltrar(ficha) {
    var estado = ficha.getAttribute('data-estado') || '';
    var activa = ficha.classList.contains('is-active');
    document.querySelectorAll('#arsStats .arx-stat').forEach(function (f) { f.classList.remove('is-active'); });
    var items = document.querySelectorAll('#arsGrid .arx-item');
    if (activa) {
        items.forEach(function (i) { i.classList.remove('is-dim'); });
        return;
    }
    ficha.classList.add('is-active');
    items.forEach(function (i) {
        i.classList.toggle('is-dim', (i.getAttribute('data-estado') || '') !== estado);
    });
}
</script>
