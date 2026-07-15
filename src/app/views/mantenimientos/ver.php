<?php
/**
 * Detalle de mantenimiento (bloque mantenimiento_plus).
 * Mobile-first: quien lo usa esta frente al problema con el celular.
 */

if (!function_exists('mdet_safe')) {
    function mdet_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mdet_fecha')) {
    function mdet_fecha($value): string
    {
        if (empty($value)) {
            return '-';
        }
        $ts = strtotime((string)$value);
        return $ts ? date('d/m/Y H:i', $ts) : '-';
    }
}

$mant = $mantenimiento;
$mantId = (int)($mant['id'] ?? 0);
$estado = (string)($mant['estado'] ?? 'en_proceso');
$tipos = Mantenimiento::getTipos();
$prioridades = Mantenimiento::getPrioridades();
$estados = Mantenimiento::getEstados();

$estadoMeta = [
    'en_proceso' => ['label' => 'En proceso', 'clase' => 'is-proc', 'icono' => 'fa-person-digging'],
    'programado' => ['label' => 'Programado', 'clase' => 'is-info', 'icono' => 'fa-calendar-check'],
    'completado' => ['label' => 'Completado', 'clase' => 'is-ok', 'icono' => 'fa-circle-check'],
    'cancelado'  => ['label' => 'Cancelado', 'clase' => 'is-off', 'icono' => 'fa-ban'],
][$estado] ?? ['label' => ucfirst($estado), 'clase' => 'is-info', 'icono' => 'fa-wrench'];

$esActivo = in_array($estado, ['en_proceso', 'programado'], true);
$habitacionNumero = trim((string)($mant['habitacion_numero'] ?? ''));
$activoNombre = trim((string)($mant['activo_nombre'] ?? ''));
if ($habitacionNumero !== '') {
    $ubicacionLabel = 'Hab. ' . $habitacionNumero;
} elseif ($activoNombre !== '') {
    $ubicacionLabel = $activoNombre;
    $activoUbicacion = trim((string)($mant['activo_ubicacion'] ?? ''));
    if ($activoUbicacion !== '') {
        $ubicacionLabel .= ' · ' . $activoUbicacion;
    }
} else {
    $ubicacionLabel = 'Instalaciones generales';
}

$momentos = [
    'reporte' => [
        'titulo' => 'El problema',
        'sub' => 'Evidencia al reportar la incidencia',
        'icono' => 'fa-triangle-exclamation',
        'vacio' => 'Sin fotos del problema todavia.',
    ],
    'resuelto' => [
        'titulo' => 'El arreglo',
        'sub' => 'Evidencia al resolver el trabajo',
        'icono' => 'fa-circle-check',
        'vacio' => 'Sin fotos del arreglo todavia.',
    ],
];
?>

<style>
.mdet {
    --md-brand: var(--brand-primary, #1B2746);
    --md-gold: var(--brand-accent, #BD9441);
    --md-ivory: #F6F2EA; --md-ivory-2: #FBF8F2;
    --md-surface: #FFFFFF;
    --md-border: color-mix(in srgb, var(--md-brand) 7%, #E7E1D4);
    --md-text: color-mix(in srgb, var(--md-brand) 36%, #596474);
    --md-muted: #828B99;
    --md-heading: color-mix(in srgb, var(--md-brand) 62%, #667284);
    --md-ok: #1E9E63; --md-ok-bg: #E7F4EC;
    --md-warn: #C2841C; --md-warn-bg: #FAF0DC;
    --md-danger: #B4392B; --md-danger-bg: #F8EAE5;
    --md-info: #2F77E0; --md-info-bg: #E6EFFC;
    --md-proc: #0E8A8A; --md-proc-bg: #E2F4F4;
    min-height: 100%;
    color: var(--md-text);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--md-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--md-ivory-2), var(--md-ivory));
    padding: 16px 14px 90px;
}
.mdet .mdet-shell { max-width: 860px; margin: 0 auto; display: grid; gap: 14px; }

.mdet .mdet-card {
    background: var(--md-surface);
    border: 1px solid var(--md-border);
    border-radius: 16px;
    box-shadow: 0 10px 26px -20px color-mix(in srgb, var(--md-brand) 45%, transparent);
    padding: 16px;
}

.mdet .mdet-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.mdet .mdet-kicker { font-size: .72rem; letter-spacing: .12em; text-transform: uppercase; color: var(--md-muted); font-weight: 700; }
.mdet h1 { margin: 2px 0 0; font-size: 1.25rem; color: var(--md-heading); font-weight: 700; }
.mdet .mdet-motivo { margin: 8px 0 0; font-size: .95rem; color: var(--md-text); }
.mdet .mdet-desc { margin: 6px 0 0; font-size: .85rem; color: var(--md-muted); white-space: pre-line; }

.mdet .mdet-estado {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 999px;
    font-size: .8rem; font-weight: 700; white-space: nowrap;
}
.mdet .mdet-estado.is-proc { background: var(--md-proc-bg); color: var(--md-proc); }
.mdet .mdet-estado.is-ok { background: var(--md-ok-bg); color: var(--md-ok); }
.mdet .mdet-estado.is-info { background: var(--md-info-bg); color: var(--md-info); }
.mdet .mdet-estado.is-off { background: #EEEDE9; color: var(--md-muted); }

.mdet .mdet-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
@media (min-width: 640px) { .mdet .mdet-meta { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
.mdet .mdet-meta-item { background: color-mix(in srgb, var(--md-brand) 3%, #FCFAF5); border: 1px solid var(--md-border); border-radius: 12px; padding: 9px 11px; }
.mdet .mdet-meta-item small { display: block; font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; color: var(--md-muted); font-weight: 700; }
.mdet .mdet-meta-item span { display: block; margin-top: 2px; font-size: .86rem; color: var(--md-heading); font-weight: 700; }

.mdet .mdet-sec-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.mdet .mdet-sec-title { display: flex; align-items: center; gap: 10px; }
.mdet .mdet-sec-title i {
    width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
    background: color-mix(in srgb, var(--md-gold) 14%, #FFFFFF); color: color-mix(in srgb, var(--md-gold) 78%, var(--md-brand));
    font-size: .9rem;
}
.mdet .mdet-sec-title h2 { margin: 0; font-size: 1rem; color: var(--md-heading); font-weight: 700; }
.mdet .mdet-sec-title p { margin: 0; font-size: .76rem; color: var(--md-muted); }
.mdet .mdet-count { font-size: .76rem; color: var(--md-muted); font-weight: 700; white-space: nowrap; }

.mdet .mdet-galeria { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
.mdet .mdet-foto {
    position: relative; display: block; aspect-ratio: 4 / 3; border-radius: 12px; overflow: hidden;
    border: 1px solid var(--md-border); background: var(--md-ivory);
}
.mdet .mdet-foto img { width: 100%; height: 100%; object-fit: cover; display: block; }
.mdet .mdet-foto small {
    position: absolute; left: 0; right: 0; bottom: 0;
    padding: 3px 7px; font-size: .62rem; color: #FFF;
    background: linear-gradient(transparent, rgba(15, 23, 42, .72));
}
.mdet .mdet-vacio {
    margin-top: 12px; padding: 14px; border: 1px dashed var(--md-border); border-radius: 12px;
    font-size: .82rem; color: var(--md-muted); text-align: center; background: color-mix(in srgb, var(--md-brand) 2%, #FCFAF5);
}

.mdet .mdet-upload { margin-top: 12px; }
.mdet .mdet-upload-row { display: flex; gap: 8px; align-items: stretch; flex-wrap: wrap; }
.mdet .mdet-file-btn {
    flex: 1 1 auto; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 14px; border-radius: 12px; cursor: pointer;
    border: 1px dashed color-mix(in srgb, var(--md-gold) 45%, var(--md-border));
    background: color-mix(in srgb, var(--md-gold) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--md-gold) 80%, var(--md-brand));
    font-weight: 700; font-size: .84rem;
}
.mdet .mdet-file-btn input { display: none; }
.mdet .mdet-file-btn .mdet-file-num { color: var(--md-muted); font-weight: 700; }
.mdet .mdet-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 11px 16px; border-radius: 12px; border: none; cursor: pointer;
    background: var(--md-brand); color: #FFF; font-weight: 700; font-size: .84rem;
}
.mdet .mdet-btn:disabled { opacity: .5; cursor: not-allowed; }
.mdet .mdet-hint { margin: 8px 0 0; font-size: .72rem; color: var(--md-muted); }

.mdet .mdet-tarea { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.mdet .mdet-tarea-info strong { color: var(--md-heading); font-size: .9rem; font-weight: 700; }
.mdet .mdet-tarea-info span { display: block; font-size: .76rem; color: var(--md-muted); margin-top: 2px; }
.mdet .mdet-link {
    display: inline-flex; align-items: center; gap: 7px; text-decoration: none;
    padding: 9px 13px; border-radius: 11px; font-size: .8rem; font-weight: 700;
    border: 1px solid var(--md-border); color: var(--md-heading); background: #FFF;
}
.mdet .mdet-link:hover { border-color: color-mix(in srgb, var(--md-gold) 45%, var(--md-border)); color: color-mix(in srgb, var(--md-gold) 80%, var(--md-brand)); }

@media (max-width: 480px) {
    .mdet { padding: 12px 10px 90px; }
    .mdet .mdet-galeria { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

<div class="mdet">
    <div class="mdet-shell">

        <!-- Encabezado -->
        <section class="mdet-card">
            <div class="mdet-hero-top">
                <div>
                    <span class="mdet-kicker">Mantenimiento #<?= $mantId ?> · <?= mdet_safe($tipos[$mant['tipo_mantenimiento'] ?? ''] ?? ucfirst((string)($mant['tipo_mantenimiento'] ?? 'General'))) ?></span>
                    <h1><?= mdet_safe($ubicacionLabel) ?></h1>
                </div>
                <span class="mdet-estado <?= $estadoMeta['clase'] ?>">
                    <i class="fas <?= $estadoMeta['icono'] ?>"></i> <?= mdet_safe($estadoMeta['label']) ?>
                </span>
            </div>

            <p class="mdet-motivo"><strong><?= mdet_safe($mant['motivo'] ?? '', 'Sin motivo registrado') ?></strong></p>
            <?php if (!empty($mant['descripcion'])): ?>
                <p class="mdet-desc"><?= mdet_safe($mant['descripcion']) ?></p>
            <?php endif; ?>

            <div class="mdet-meta">
                <div class="mdet-meta-item">
                    <small>Prioridad</small>
                    <span><?= mdet_safe($prioridades[$mant['prioridad'] ?? ''] ?? ucfirst((string)($mant['prioridad'] ?? 'Media'))) ?></span>
                </div>
                <div class="mdet-meta-item">
                    <small>Inicio</small>
                    <span><?= mdet_fecha($mant['fecha_inicio'] ?? null) ?></span>
                </div>
                <div class="mdet-meta-item">
                    <small>Cierre</small>
                    <span><?= mdet_fecha($mant['fecha_fin'] ?? null) ?></span>
                </div>
                <div class="mdet-meta-item">
                    <small>Reportado por</small>
                    <span><?= mdet_safe($mant['usuario_registro_nombre'] ?? '', 'Sistema') ?></span>
                </div>
            </div>
        </section>

        <!-- Galeria antes / despues -->
        <?php foreach ($momentos as $momentoClave => $momentoMeta): ?>
            <?php
            $fotosMomento = $fotos[$momentoClave] ?? [];
            $cupo = max(0, (int)$max_fotos - count($fotosMomento));
            $permitirSubida = $puede_gestionar && $cupo > 0
                && ($momentoClave === 'reporte' ? $esActivo : true);
            ?>
            <section class="mdet-card">
                <div class="mdet-sec-head">
                    <div class="mdet-sec-title">
                        <i class="fas <?= $momentoMeta['icono'] ?>"></i>
                        <div>
                            <h2><?= mdet_safe($momentoMeta['titulo']) ?></h2>
                            <p><?= mdet_safe($momentoMeta['sub']) ?></p>
                        </div>
                    </div>
                    <span class="mdet-count"><?= count($fotosMomento) ?>/<?= (int)$max_fotos ?> fotos</span>
                </div>

                <?php if (!empty($fotosMomento)): ?>
                    <div class="mdet-galeria">
                        <?php foreach ($fotosMomento as $foto): ?>
                            <a class="mdet-foto" href="<?= image_url(mdet_safe($foto['ruta'])) ?>" target="_blank" rel="noopener">
                                <img src="<?= image_url(mdet_safe($foto['ruta'])) ?>" alt="Evidencia <?= mdet_safe($momentoMeta['titulo']) ?>" loading="lazy">
                                <small><?= mdet_fecha($foto['created_at'] ?? null) ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="mdet-vacio"><i class="fas fa-camera"></i> <?= mdet_safe($momentoMeta['vacio']) ?></div>
                <?php endif; ?>

                <?php if ($permitirSubida): ?>
                    <form class="mdet-upload" method="POST" action="<?= url('mantenimientos/' . $mantId . '/fotos') ?>" enctype="multipart/form-data" data-mdet-upload>
                        <?= csrf_field() ?>
                        <input type="hidden" name="momento" value="<?= mdet_safe($momentoClave) ?>">
                        <div class="mdet-upload-row">
                            <label class="mdet-file-btn">
                                <input type="file" name="fotos[]" accept="image/*" capture="environment" multiple data-mdet-input>
                                <i class="fas fa-camera"></i> Tomar o elegir fotos
                                <span class="mdet-file-num" data-mdet-num></span>
                            </label>
                            <button type="submit" class="mdet-btn" disabled data-mdet-submit>
                                <i class="fas fa-upload"></i> Subir
                            </button>
                        </div>
                        <p class="mdet-hint">Hasta <?= $cupo ?> foto<?= $cupo === 1 ? '' : 's' ?> m&aacute;s · JPG, PNG o WebP · m&aacute;x 5MB c/u</p>
                    </form>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>

        <!-- Costo y gasto en caja -->
        <?php
        $costoReal = isset($mant['costo']) && $mant['costo'] !== null ? (float)$mant['costo'] : null;
        $gastoRegistrado = !empty($mant['gasto_movimiento_id']);
        $metodosPago = $metodos_pago ?? [];
        ?>
        <?php if ($estado === 'completado' || $costoReal !== null): ?>
            <section class="mdet-card">
                <div class="mdet-sec-head">
                    <div class="mdet-sec-title">
                        <i class="fas fa-coins"></i>
                        <div>
                            <h2>Costo del trabajo</h2>
                            <p>Lo que costo resolver esta incidencia</p>
                        </div>
                    </div>
                    <?php if ($costoReal !== null && $costoReal > 0): ?>
                        <?php if ($gastoRegistrado): ?>
                            <span class="mdet-estado is-ok"><i class="fas fa-circle-check"></i> Registrado en gastos</span>
                        <?php else: ?>
                            <span class="mdet-estado is-warn" style="background:var(--md-warn-bg);color:var(--md-warn);"><i class="fas fa-hourglass-half"></i> Por registrar</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="mdet-meta">
                    <div class="mdet-meta-item">
                        <small>Costo real</small>
                        <span><?= $costoReal !== null ? '$' . number_format($costoReal, 2) : '-' ?></span>
                    </div>
                    <div class="mdet-meta-item">
                        <small>Estimado</small>
                        <span><?= isset($mant['costo_estimado']) && $mant['costo_estimado'] !== null ? '$' . number_format((float)$mant['costo_estimado'], 2) : '-' ?></span>
                    </div>
                    <div class="mdet-meta-item">
                        <small>Proveedor</small>
                        <span><?= mdet_safe($mant['proveedor'] ?? '', '-') ?></span>
                    </div>
                    <div class="mdet-meta-item">
                        <small>Gasto en caja</small>
                        <span>
                            <?php if ($gastoRegistrado): ?>
                                <i class="fas fa-check" style="color:var(--md-ok);"></i> MANT-<?= $mantId ?> · <?= mdet_fecha($mant['gasto_registrado_en'] ?? null) ?>
                            <?php elseif ($costoReal !== null && $costoReal > 0): ?>
                                Pendiente
                            <?php else: ?>
                                Sin costo
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <?php if (!empty($mant['nota_costo'])): ?>
                    <p class="mdet-desc" style="margin-top:10px;"><i class="fas fa-note-sticky" style="color:var(--md-gold);margin-right:6px;"></i><?= mdet_safe($mant['nota_costo']) ?></p>
                <?php endif; ?>

                <?php if (!$gastoRegistrado && $costoReal !== null && $costoReal > 0 && !empty($puede_caja)): ?>
                    <form class="mdet-upload" method="POST" action="<?= url('mantenimientos/' . $mantId . '/registrar-gasto') ?>" data-mdet-once>
                        <?= csrf_field() ?>
                        <div class="mdet-upload-row">
                            <select name="metodo_pago" class="mdet-file-btn" style="cursor:pointer;border-style:solid;appearance:auto;">
                                <?php foreach ($metodosPago as $mpClave => $mpMeta): ?>
                                    <option value="<?= mdet_safe($mpClave) ?>"><?= mdet_safe($mpMeta['label'] ?? ucfirst($mpClave)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="mdet-btn" data-mdet-once-btn>
                                <i class="fas fa-cash-register"></i> Registrar en gastos
                            </button>
                        </div>
                        <p class="mdet-hint">Crea el egreso en la caja abierta con referencia MANT-<?= $mantId ?>. Si la caja est&aacute; cerrada, quedar&aacute; pendiente y aparecer&aacute; en la cola al abrirla.</p>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Cierre con costo y evidencia -->
        <?php if ($esActivo && !empty($puede_gestionar)): ?>
            <section class="mdet-card" id="cerrarMantenimiento">
                <div class="mdet-sec-head">
                    <div class="mdet-sec-title">
                        <i class="fas fa-flag-checkered"></i>
                        <div>
                            <h2>Cerrar mantenimiento</h2>
                            <p>Foto del arreglo, costo real y registro del gasto</p>
                        </div>
                    </div>
                </div>

                <form class="mdet-upload" method="POST" action="<?= url('mantenimientos/' . $mantId . '/cerrar') ?>" enctype="multipart/form-data" data-mdet-once>
                    <?= csrf_field() ?>

                    <div style="display:grid;gap:12px;margin-top:4px;">
                        <label class="mdet-file-btn">
                            <input type="file" name="fotos_resuelto[]" accept="image/*" capture="environment" multiple data-mdet-input>
                            <i class="fas fa-camera"></i> Foto del arreglo (opcional)
                            <span class="mdet-file-num" data-mdet-num></span>
                        </label>

                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
                            <div>
                                <label class="mdet-label" style="display:block;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--md-muted);font-weight:700;margin-bottom:4px;">Costo real</label>
                                <input type="text" name="costo" inputmode="decimal" data-money-format="true" placeholder="0.00" class="mdet-input" style="width:100%;padding:10px 12px;border:1px solid var(--md-border);border-radius:11px;font-size:.9rem;">
                            </div>
                            <div>
                                <label class="mdet-label" style="display:block;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--md-muted);font-weight:700;margin-bottom:4px;">Proveedor (opcional)</label>
                                <input type="text" name="proveedor" maxlength="200" placeholder="Plomeria Lopez..." class="mdet-input" style="width:100%;padding:10px 12px;border:1px solid var(--md-border);border-radius:11px;font-size:.9rem;">
                            </div>
                        </div>

                        <div>
                            <label class="mdet-label" style="display:block;font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;color:var(--md-muted);font-weight:700;margin-bottom:4px;">Nota del costo (opcional)</label>
                            <input type="text" name="nota_costo" maxlength="255" placeholder="Refacciones + mano de obra..." class="mdet-input" style="width:100%;padding:10px 12px;border:1px solid var(--md-border);border-radius:11px;font-size:.9rem;">
                        </div>

                        <?php if (!empty($puede_caja)): ?>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 12px;border:1px solid var(--md-border);border-radius:11px;background:color-mix(in srgb, var(--md-brand) 2%, #FCFAF5);">
                                <label style="display:inline-flex;align-items:center;gap:8px;font-size:.84rem;font-weight:700;color:var(--md-heading);cursor:pointer;">
                                    <input type="checkbox" name="registrar_gasto" value="1" checked>
                                    Registrar en gastos de caja
                                </label>
                                <select name="metodo_pago" style="padding:8px 10px;border:1px solid var(--md-border);border-radius:9px;font-size:.82rem;">
                                    <?php foreach ($metodosPago as $mpClave => $mpMeta): ?>
                                        <option value="<?= mdet_safe($mpClave) ?>"><?= mdet_safe($mpMeta['label'] ?? ucfirst($mpClave)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="mdet-btn" data-mdet-once-btn style="background:var(--md-ok);">
                            <i class="fas fa-check-circle"></i> Cerrar mantenimiento
                        </button>
                        <p class="mdet-hint" style="margin-top:-4px;">Sin costo tambi&eacute;n se puede cerrar; la habitaci&oacute;n vuelve a disponible.</p>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <!-- Tarea vinculada -->
        <?php if (!empty($tarea_activa)): ?>
            <section class="mdet-card">
                <div class="mdet-tarea">
                    <div class="mdet-tarea-info">
                        <strong><i class="fas fa-list-check" style="margin-right:6px;color:var(--md-gold);"></i><?= mdet_safe($tarea_activa['titulo'] ?? 'Tarea de seguimiento') ?></strong>
                        <span>
                            Estado: <?= mdet_safe(ucfirst(str_replace('_', ' ', (string)($tarea_activa['estado'] ?? 'pendiente')))) ?>
                            <?php if (!empty($tarea_activa['trabajadores_asignados'] ?? $tarea_activa['trabajador_nombre'] ?? '')): ?>
                                · <?= mdet_safe($tarea_activa['trabajadores_asignados'] ?? $tarea_activa['trabajador_nombre']) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <a class="mdet-link" href="<?= url('tareas/' . (int)($tarea_activa['id'] ?? 0)) ?>">
                        <i class="fas fa-arrow-right"></i> Ver tarea
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <!-- Accesos -->
        <section class="mdet-card">
            <div class="mdet-tarea">
                <div class="mdet-tarea-info">
                    <strong>Accesos r&aacute;pidos</strong>
                    <span>Habitaci&oacute;n y reporte general de mantenimiento.</span>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <?php if (!empty($mant['habitacion_id'])): ?>
                        <a class="mdet-link" href="<?= url('habitaciones/' . (int)$mant['habitacion_id']) ?>">
                            <i class="fas fa-door-open"></i> Habitaci&oacute;n
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($mant['activo_id'])): ?>
                        <a class="mdet-link" href="<?= url('mantenimientos/activos/' . (int)$mant['activo_id']) ?>">
                            <i class="fas fa-fire-burner"></i> Historial del activo
                        </a>
                    <?php endif; ?>
                    <a class="mdet-link" href="<?= url('reportes/mantenimiento') ?>">
                        <i class="fas fa-chart-column"></i> Reporte
                    </a>
                </div>
            </div>
        </section>

    </div>
</div>

<script>
(function () {
    // Contador de fotos elegidas junto a cada input de archivo.
    document.querySelectorAll('[data-mdet-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var form = input.closest('form');
            var num = form ? form.querySelector('[data-mdet-num]') : null;
            var n = input.files ? input.files.length : 0;
            if (num) num.textContent = n > 0 ? '(' + n + ')' : '';
            var submit = form ? form.querySelector('[data-mdet-submit]') : null;
            if (submit) submit.disabled = n === 0;
        });
    });

    // Formularios de solo-fotos: exigen al menos un archivo.
    document.querySelectorAll('[data-mdet-upload]').forEach(function (form) {
        var submit = form.querySelector('[data-mdet-submit]');
        if (!submit) return;
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Subiendo...';
        });
    });

    // Guardia anti doble clic (cerrar / registrar gasto): un solo envio.
    document.querySelectorAll('[data-mdet-once]').forEach(function (form) {
        var enviado = false;
        form.addEventListener('submit', function (e) {
            if (enviado) {
                e.preventDefault();
                return;
            }
            enviado = true;
            var btn = form.querySelector('[data-mdet-once-btn]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            }
        });
    });
})();
</script>
