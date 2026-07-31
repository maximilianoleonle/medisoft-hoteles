<?php
/**
 * Detalle de area (bloque habitaciones y areas): acciones operativas
 * (limpieza con personal, mantenimiento, cerrar/reabrir) + historial.
 * Lenguaje calcado de habitaciones/index.php: header glass con subnav
 * integrada y chip de estado semantico. Contratos intactos: rutas POST,
 * personal_confirmado/trabajador_ids/sin_personal, arvTogglePanel.
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
// Activos con preventivo que viven en esta area (bomba de la alberca, minisplit
// del lobby). Vacio = el hotel no tiene el bloque o no le ha dado de alta ninguno.
$arvActivos = is_array($activos ?? null) ? $activos : [];
$arvActivoMeta = [
    'vencido'      => ['clase' => 'st-warn', 'icono' => 'fa-triangle-exclamation', 'label' => 'Vencido'],
    'por_vencer'   => ['clase' => 'st-warn', 'icono' => 'fa-clock',                'label' => 'Por vencer'],
    'al_dia'       => ['clase' => 'st-ok',   'icono' => 'fa-circle-check',         'label' => 'Al día'],
    'sin_programa' => ['clase' => '',        'icono' => 'fa-calendar-xmark',       'label' => 'Sin programa'],
    'inactivo'     => ['clase' => '',        'icono' => 'fa-pause',                'label' => 'Pausado'],
];
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
    'disponible'    => ['label' => 'Disponible',       'clase' => 'st-ok',    'icono' => 'fa-circle-check'],
    'limpieza'      => ['label' => 'En limpieza',      'clase' => 'st-clean', 'icono' => 'fa-broom'],
    'mantenimiento' => ['label' => 'En mantenimiento', 'clase' => 'st-warn',  'icono' => 'fa-wrench'],
    'cerrada'       => ['label' => 'Cerrada',          'clase' => 'st-off',   'icono' => 'fa-ban'],
];
$estadoActual = $estadoMeta[$estado] ?? $estadoMeta['disponible'];

$estadoTareaMeta = [
    'pendiente'  => ['label' => 'Pendiente',  'clase' => 'st-warn'],
    'asignada'   => ['label' => 'Asignada',   'clase' => 'st-clean'],
    'en_proceso' => ['label' => 'En proceso', 'clase' => 'st-clean'],
    'completada' => ['label' => 'Completada', 'clase' => 'st-ok'],
    'cancelada'  => ['label' => 'Cancelada',  'clase' => 'st-pause'],
];
$estadoMantMeta = [
    'en_proceso' => ['label' => 'En proceso', 'clase' => 'st-warn'],
    'programado' => ['label' => 'Programado', 'clase' => 'st-clean'],
    'completado' => ['label' => 'Completado', 'clase' => 'st-ok'],
    'cancelado'  => ['label' => 'Cancelado',  'clase' => 'st-pause'],
];
?>

<?php include __DIR__ . '/_estilos.php'; ?>

<div class="arx">

    <div class="arx-header">
        <div class="arx-header-shell">
            <div class="arx-header-row">
                <div class="arx-header-id">
                    <span class="arx-header-ico"><i class="fas <?= arv_safe($tipoMeta['icono']) ?>"></i></span>
                    <div style="min-width:0;">
                        <h1><?= arv_safe($area['nombre'] ?? '') ?></h1>
                        <p class="arx-header-sub"><?= arv_safe($tipoMeta['label']) ?> · <?= arv_safe($pisoTexto) ?><?= !empty($area['descripcion']) ? ' · ' . arv_safe(mb_strimwidth((string)$area['descripcion'], 0, 70, '…')) : '' ?></p>
                    </div>
                </div>
                <div class="arx-header-acts" style="align-items:center;">
                    <?php if ($estaActiva): ?>
                        <span class="arx-chip <?= $estadoActual['clase'] ?>" style="font-size:.78rem;padding:6px 13px;"><i class="fas <?= $estadoActual['icono'] ?>"></i> <?= arv_safe($estadoActual['label']) ?></span>
                    <?php else: ?>
                        <span class="arx-chip st-pause" style="font-size:.78rem;padding:6px 13px;"><i class="fas fa-pause"></i> Pausada</span>
                    <?php endif; ?>
                    <a class="arx-btn is-outline" href="<?= url('areas') ?>" data-prefetch><i class="fas fa-arrow-left"></i> <span>Todas las áreas</span></a>
                </div>
            </div>
            <?php
            $subnav_section = 'habitaciones';
            $subnav_active = 'areas';
            include APP_PATH . '/views/partials/section_subnav.php';
            ?>
        </div>
    </div>

    <div class="arx-shell">

        <?php if ($estaActiva): ?>
        <section class="arx-card" style="margin-bottom:14px;">
            <div style="display:flex;gap:8px;flex-wrap:wrap;">

                <?php if ($estado === 'disponible'): ?>
                    <form method="POST" action="<?= url('areas/' . $areaId . '/limpieza') ?>" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="iniciar">
                        <button type="submit" class="arx-btn is-outline"><i class="fas fa-broom" style="color:var(--c-cleaning);"></i> Mandar a limpieza</button>
                    </form>
                    <?php if ($puedeMantenimiento): ?>
                        <button type="button" class="arx-btn is-warn" onclick="arvTogglePanel('arvPanelMant')"><i class="fas fa-wrench"></i> Reportar mantenimiento</button>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/cerrar') ?>" style="display:inline;"
                              onsubmit="return confirm('El área quedará cerrada y fuera de servicio hasta que la reabras. ¿Cerrar el área?');">
                            <?= csrf_field() ?>
                            <button type="submit" class="arx-btn is-outline"><i class="fas fa-ban" style="color:var(--c-critical);"></i> Cerrar área</button>
                        </form>
                    <?php endif; ?>

                <?php elseif ($estado === 'limpieza'): ?>
                    <button type="button" class="arx-btn is-ok" onclick="arvTogglePanel('arvPanelLimpieza')"><i class="fas fa-check"></i> Marcar limpia</button>

                <?php elseif ($estado === 'mantenimiento'): ?>
                    <?php if ($puedeMantenimiento): ?>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/mantenimiento') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="finalizar">
                            <button type="submit" class="arx-btn is-ok"><i class="fas fa-check"></i> Finalizar mantenimiento</button>
                        </form>
                    <?php else: ?>
                        <span class="arx-vacio" style="padding:6px 0;">En mantenimiento: lo finaliza quien tenga permiso de mantenimiento.</span>
                    <?php endif; ?>

                <?php elseif ($estado === 'cerrada'): ?>
                    <?php if ($puedeMantenimiento): ?>
                        <form method="POST" action="<?= url('areas/' . $areaId . '/cerrar') ?>" style="display:inline;">
                            <?= csrf_field() ?>
                            <button type="submit" class="arx-btn is-ok"><i class="fas fa-door-open"></i> Reabrir área</button>
                        </form>
                    <?php else: ?>
                        <span class="arx-vacio" style="padding:6px 0;">Área cerrada: la reabre quien tenga permiso de mantenimiento.</span>
                    <?php endif; ?>
                <?php endif; ?>

            </div>

            <?php if ($estado === 'limpieza'): ?>
            <form class="arx-panel" id="arvPanelLimpieza" method="POST" action="<?= url('areas/' . $areaId . '/limpieza') ?>" data-ms-no-summary="1">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="completar">
                <input type="hidden" name="personal_confirmado" value="1">
                <label class="arx-lbl">¿Quién hizo la limpieza?</label>
                <?php if (!empty($personalLimpieza)): ?>
                    <div class="arx-personal">
                        <?php foreach ($personalLimpieza as $t): ?>
                            <label class="arx-check">
                                <input type="checkbox" name="trabajador_ids[]" value="<?= (int)$t['id'] ?>">
                                <span><?= arv_safe($t['nombre_completo']) ?><?= !empty($t['rol_laboral']) ? ' · ' . arv_safe($t['rol_laboral']) : '' ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label class="arx-check" style="margin-top:8px;">
                    <input type="checkbox" name="sin_personal" value="1">
                    <span>Sin registrar personal</span>
                </label>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="arx-btn is-ok"><i class="fas fa-check"></i> Confirmar limpieza</button>
                    <button type="button" class="arx-btn is-outline" onclick="arvTogglePanel('arvPanelLimpieza', false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>

            <?php if ($estado === 'disponible' && $puedeMantenimiento): ?>
            <form class="arx-panel" id="arvPanelMant" method="POST" action="<?= url('areas/' . $areaId . '/mantenimiento') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="iniciar">
                <div class="arx-form-grid">
                    <div>
                        <label class="arx-lbl">Motivo</label>
                        <input type="text" name="motivo" required maxlength="255" placeholder="Fuga en la bomba, pintura..." class="arx-input">
                    </div>
                    <div>
                        <label class="arx-lbl">Tipo</label>
                        <select name="tipo_mantenimiento" class="arx-input">
                            <?php foreach ($tiposMantenimiento as $tKey => $tLabel): ?>
                                <option value="<?= arv_safe($tKey) ?>" <?= $tKey === 'correctivo' ? 'selected' : '' ?>><?= arv_safe(is_array($tLabel) ? ($tLabel['label'] ?? $tKey) : $tLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="arx-lbl">Prioridad</label>
                        <select name="prioridad" class="arx-input">
                            <?php foreach ($prioridadesMantenimiento as $pKey => $pLabel): ?>
                                <option value="<?= arv_safe($pKey) ?>" <?= $pKey === 'media' ? 'selected' : '' ?>><?= arv_safe(is_array($pLabel) ? ($pLabel['label'] ?? $pKey) : $pLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($arvActivos)): ?>
                    <div>
                        <label class="arx-lbl">¿A qué equipo le das servicio?</label>
                        <select name="activo_id" class="arx-input">
                            <option value="">Ninguno en particular</option>
                            <?php foreach ($arvActivos as $arvActivo): ?>
                                <option value="<?= (int)$arvActivo['id'] ?>"><?= arv_safe($arvActivo['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="arx-hint">Si eliges uno, al finalizar se le adelanta su próximo servicio preventivo.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <button type="submit" class="arx-btn is-warn"><i class="fas fa-wrench"></i> Iniciar mantenimiento</button>
                    <button type="button" class="arx-btn is-outline" onclick="arvTogglePanel('arvPanelMant', false)">Cancelar</button>
                </div>
            </form>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if (!empty($arvActivos)): ?>
        <div class="arx-floor">
            <span class="arx-floor-t">Equipos de esta área</span>
            <span class="arx-floor-rule"></span>
            <span class="arx-floor-ct"><?= count($arvActivos) ?> activo<?= count($arvActivos) === 1 ? '' : 's' ?></span>
        </div>
        <section class="arx-card">
            <ul class="arx-activos">
                <?php foreach ($arvActivos as $arvActivo): ?>
                    <?php $arvMeta = $arvActivoMeta[$arvActivo['vencimiento'] ?? 'sin_programa'] ?? $arvActivoMeta['sin_programa']; ?>
                    <li class="arx-activo">
                        <div class="arx-activo-info">
                            <a class="arx-activo-nombre" href="<?= url('mantenimientos/activos/' . (int)$arvActivo['id']) ?>"><?= arv_safe($arvActivo['nombre']) ?></a>
                            <small>
                                Servicio cada <?= (int)$arvActivo['periodicidad_dias'] ?> días<?php
                                if (!empty($arvActivo['proximo_servicio'])): ?> · próximo <?= arv_safe(date('d/m/Y', strtotime((string)$arvActivo['proximo_servicio']))) ?><?php endif; ?>
                            </small>
                        </div>
                        <span class="arx-chip <?= $arvMeta['clase'] ?>"><i class="fas <?= $arvMeta['icono'] ?>"></i> <?= arv_safe($arvMeta['label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <div class="arx-floor">
            <span class="arx-floor-t">Limpiezas</span>
            <span class="arx-floor-rule"></span>
            <span class="arx-floor-ct"><?= count($historial['limpiezas']) ?> registro<?= count($historial['limpiezas']) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (empty($historial['limpiezas'])): ?>
            <section class="arx-card"><div class="arx-vacio">Sin limpiezas registradas todavía.</div></section>
        <?php else: ?>
            <div class="arx-hist">
                <?php foreach ($historial['limpiezas'] as $l): ?>
                    <?php $lm = $estadoTareaMeta[$l['estado'] ?? ''] ?? ['label' => (string)($l['estado'] ?? ''), 'clase' => 'st-pause']; ?>
                    <div class="arx-hist-item">
                        <div class="arx-hist-top">
                            <strong><?= arv_safe($l['titulo']) ?></strong>
                            <span class="arx-chip <?= $lm['clase'] ?>"><?= arv_safe($lm['label']) ?></span>
                        </div>
                        <div class="arx-hist-meta">
                            <?= arv_fecha($l['fecha_cierre'] ?? $l['fecha_programada'] ?? $l['created_at']) ?>
                            <?= !empty($l['notas_cierre']) ? ' · ' . arv_safe($l['notas_cierre']) : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="arx-floor">
            <span class="arx-floor-t">Mantenimientos</span>
            <span class="arx-floor-rule"></span>
            <span class="arx-floor-ct"><?= count($historial['mantenimientos']) ?> registro<?= count($historial['mantenimientos']) === 1 ? '' : 's' ?></span>
        </div>
        <?php if (empty($historial['mantenimientos'])): ?>
            <section class="arx-card"><div class="arx-vacio">Sin mantenimientos registrados todavía.</div></section>
        <?php else: ?>
            <div class="arx-hist">
                <?php foreach ($historial['mantenimientos'] as $m): ?>
                    <?php $mm = $estadoMantMeta[$m['estado'] ?? ''] ?? ['label' => (string)($m['estado'] ?? ''), 'clase' => 'st-pause']; ?>
                    <div class="arx-hist-item">
                        <div class="arx-hist-top">
                            <strong><?= arv_safe($m['motivo']) ?></strong>
                            <span class="arx-chip <?= $mm['clase'] ?>"><?= arv_safe($mm['label']) ?></span>
                        </div>
                        <div class="arx-hist-meta">
                            <?= arv_safe($m['tipo_mantenimiento']) ?> · <?= arv_fecha($m['fecha_inicio']) ?><?= !empty($m['fecha_fin']) ? ' → ' . arv_fecha($m['fecha_fin']) : '' ?>
                            <?= ($m['costo'] !== null && $m['costo'] !== '') ? ' · $' . number_format((float)$m['costo'], 2) : '' ?>
                            <?= !empty($m['proveedor']) ? ' · ' . arv_safe($m['proveedor']) : '' ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

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
