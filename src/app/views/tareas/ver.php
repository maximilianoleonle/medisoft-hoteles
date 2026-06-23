<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_date')) {
    function tlm_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_estado_meta')) {
    function tk_estado_meta($estado): array
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'asignada'   => ['Asignada', 'is-asignada', 'fa-user-check'],
            'en_proceso' => ['En proceso', 'is-proceso', 'fa-spinner'],
            'completada' => ['Completada', 'is-completada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

if (!function_exists('tk_prioridad_meta')) {
    function tk_prioridad_meta($prioridad): array
    {
        $key = strtolower(trim((string)($prioridad ?? '')));
        $map = [
            'baja'    => ['Baja', 'is-baja', 'fa-arrow-down'],
            'media'   => ['Media', 'is-media', 'fa-equals'],
            'alta'    => ['Alta', 'is-alta', 'fa-arrow-up'],
            'urgente' => ['Urgente', 'is-urgente', 'fa-fire'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Media'), 'is-media', 'fa-equals'];
    }
}

if (!function_exists('tk_categoria_meta')) {
    function tk_categoria_meta($categoria): array
    {
        $key = strtolower(trim((string)($categoria ?? '')));
        $map = [
            'limpieza'      => ['Limpieza', 'fa-broom'],
            'mantenimiento' => ['Mantenimiento', 'fa-screwdriver-wrench'],
            'general'       => ['General', 'fa-list-check'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'General'), 'fa-list-check'];
    }
}

$tarea = is_array($tarea ?? null) ? $tarea : [];
$eventos = is_array($eventos ?? null) ? $eventos : [];
$eventosDisponibles = (bool)($eventosDisponibles ?? false);
$trabajadoresActivos = is_array($trabajadoresActivos ?? null) ? $trabajadoresActivos : [];
$puedeAsignar = (bool)($puedeAsignar ?? false);
$puedeCambiarEstado = (bool)($puedeCambiarEstado ?? false);
$documentosEntidad = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];

$estado = (string)($tarea['estado'] ?? 'pendiente');
$tareaId = (int)($tarea['id'] ?? 0);
[$eLabel, $eClass, $eIcon] = tk_estado_meta($estado);
[$pLabel, $pClass, $pIcon] = tk_prioridad_meta($tarea['prioridad'] ?? 'media');
[$cLabel, $cIcon] = tk_categoria_meta($tarea['categoria'] ?? 'general');
?>

<style>
.tk-detail {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 72%, #000);
    --tk-ivory: #F6F2EA; --tk-ivory-2: #FBF8F2;
    --tk-surface: #FFFFFF; --tk-surface-warm: #FCFAF5;
    --tk-border: color-mix(in srgb, var(--tk-brand) 7%, #E7E1D4);
    --tk-ring: color-mix(in srgb, var(--tk-gold) 32%, transparent);
    --tk-text: #171717; --tk-muted: #667085; --tk-heading: #111827;
    --tk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --tk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-success: #1E9E63; --tk-success-bg: #E7F4EC;
    --tk-warning: #C2841C; --tk-warning-bg: #FAF0DC;
    --tk-danger: #B4392B; --tk-danger-bg: #F8EAE5;
    --tk-info: #2F77E0; --tk-info-bg: #E6EFFC;
    --tk-proc: #0E8A8A; --tk-proc-bg: #E2F4F4;
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--tk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--tk-ivory-2), var(--tk-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.tk-detail .tk-shell { display: grid; gap: 14px; }
.tk-detail .tk-back { display: inline-flex; align-items: center; gap: 8px; color: var(--tk-muted); text-decoration: none; font-weight: 700; font-size: .85rem; }
.tk-detail .tk-back:hover { color: var(--tk-gold-ink); }
.tk-detail .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.tk-detail .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-detail .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-detail .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(1.9rem, 3.4vw, 2.7rem); line-height: 1.02; }
.tk-detail .tk-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }

.tk-detail .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.tk-detail .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 28%, #fff); }
.tk-detail .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 26%, #fff); }
.tk-detail .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 80%, #000); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 26%, #fff); }
.tk-detail .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 78%, #000); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 26%, #fff); }
.tk-detail .tk-badge.is-cancelada, .tk-detail .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-detail .tk-badge.is-cat { color: var(--tk-gold-ink); background: var(--tk-gold-soft); border-color: var(--tk-gold-line); }
.tk-detail .tk-badge.is-baja { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-detail .tk-badge.is-media { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 24%, #fff); }
.tk-detail .tk-badge.is-alta { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 26%, #fff); }
.tk-detail .tk-badge.is-urgente { color: color-mix(in srgb, var(--tk-danger) 82%, #000); background: var(--tk-danger-bg); border-color: color-mix(in srgb, var(--tk-danger) 26%, #fff); }

.tk-detail .tk-grid { display: grid; grid-template-columns: 1.4fr .9fr; gap: 14px; align-items: start; }
.tk-detail .tk-col { display: grid; gap: 14px; }
.tk-detail .tk-card { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; padding: 18px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.tk-detail .tk-card h2 { font-family: var(--tk-serif); font-size: 1.35rem; font-weight: 700; color: var(--tk-heading); margin: 0 0 12px; }
.tk-detail .tk-desc { color: #334155; line-height: 1.6; white-space: pre-wrap; }
.tk-detail .tk-defs { display: grid; grid-template-columns: 150px 1fr; gap: 9px 14px; font-size: .88rem; }
.tk-detail .tk-defs dt { color: var(--tk-muted); font-weight: 700; }
.tk-detail .tk-defs dd { margin: 0; color: var(--tk-heading); font-weight: 600; }
.tk-detail .tk-defs a { color: var(--tk-info); text-decoration: none; font-weight: 700; }
.tk-detail .tk-defs a:hover { text-decoration: underline; }
.tk-detail .tk-faint { color: var(--tk-muted); font-weight: 500; }

.tk-detail .tk-empty-box { padding: 22px; text-align: center; color: var(--tk-muted); border: 1px dashed var(--tk-border); border-radius: 12px; background: var(--tk-surface-warm); font-size: .88rem; }
.tk-detail .tk-label { display: block; font-size: .72rem; font-weight: 700; color: var(--tk-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.tk-detail .tk-field { width: 100%; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); border-radius: 11px; padding: 10px 12px; color: var(--tk-text); font-weight: 600; font-size: .88rem; font-family: var(--tk-sans); transition: border-color .16s ease, box-shadow .16s ease; }
.tk-detail select.tk-field { min-height: 44px; cursor: pointer; }
.tk-detail textarea.tk-field { min-height: 74px; resize: vertical; }
.tk-detail .tk-field:focus { border-color: var(--tk-gold); box-shadow: 0 0 0 3px var(--tk-ring); outline: none; background: #fff; }
.tk-detail .tk-help { font-size: .76rem; color: var(--tk-muted); margin-top: 8px; }
.tk-detail .tk-form-row { display: grid; gap: 10px; }
.tk-detail .tk-stack { display: grid; gap: 14px; }

.tk-detail .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; line-height: 1; cursor: pointer; text-decoration: none; width: 100%;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, filter .16s ease; }
.tk-detail .tk-btn:hover { transform: translateY(-1px); filter: brightness(1.03); }
.tk-detail .tk-btn-gold { background: linear-gradient(135deg, var(--tk-gold), color-mix(in srgb, var(--tk-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--tk-gold) 58%, transparent); }
.tk-detail .tk-btn-brand { background: linear-gradient(135deg, var(--tk-brand), var(--tk-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--tk-brand) 60%, transparent); }
.tk-detail .tk-btn-success { background: linear-gradient(135deg, var(--tk-success), color-mix(in srgb, var(--tk-success) 74%, #000)); color: #fff; }
.tk-detail .tk-btn-danger { background: linear-gradient(135deg, var(--tk-danger), color-mix(in srgb, var(--tk-danger) 72%, #000)); color: #fff; }

.tk-detail .tk-timeline { display: grid; gap: 10px; }
.tk-detail .tk-event { border: 1px solid var(--tk-border); border-radius: 12px; padding: 12px; background: var(--tk-surface-warm); }
.tk-detail .tk-event strong { display: block; color: var(--tk-heading); font-weight: 700; }
.tk-detail .tk-event .tk-faint { font-size: .78rem; }

@media (max-width: 900px) { .tk-detail .tk-grid { grid-template-columns: 1fr; } .tk-detail .tk-defs { grid-template-columns: 1fr; } .tk-detail .tk-title { font-size: 1.8rem; } }
</style>

<div class="tk-detail p-4 sm:p-6">
    <div class="tk-shell">
        <a class="tk-back" href="<?= url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>

        <section class="tk-title-lockup">
            <div class="tk-hero-icon"><i class="fas <?= $cIcon ?>"></i></div>
            <div>
                <p class="tk-kicker">Tarea #<?= $tareaId ?></p>
                <h1 class="tk-title"><?= tlm_safe($tarea['titulo'] ?? '', 'Tarea operativa') ?></h1>
                <div class="tk-chips">
                    <span class="tk-badge is-cat"><i class="fas <?= $cIcon ?>"></i> <?= $cLabel ?></span>
                    <span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                    <span class="tk-badge <?= $pClass ?>"><i class="fas <?= $pIcon ?>"></i> Prioridad <?= $pLabel ?></span>
                    <span class="tk-badge is-soft"><i class="fas fa-tag"></i> Origen <?= tlm_safe($tarea['origen'] ?? 'manual') ?></span>
                </div>
            </div>
        </section>

        <div class="tk-grid">
            <div class="tk-col">
                <section class="tk-card">
                    <h2>Descripci&oacute;n</h2>
                    <?php if (!empty($tarea['descripcion'])): ?>
                        <div class="tk-desc"><?= tlm_safe($tarea['descripcion']) ?></div>
                    <?php else: ?>
                        <div class="tk-empty-box">Esta tarea no tiene descripci&oacute;n.</div>
                    <?php endif; ?>
                </section>

                <section class="tk-card">
                    <h2>Detalles</h2>
                    <dl class="tk-defs">
                        <dt>Habitaci&oacute;n</dt>
                        <dd>
                            <?php if (!empty($tarea['habitacion_id'])): ?>
                                <a href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a>
                                <span class="tk-faint">(<?= tlm_safe($tarea['habitacion_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">Sin habitaci&oacute;n</span>
                            <?php endif; ?>
                        </dd>
                        <dt>Trabajador</dt>
                        <dd>
                            <?php if (!empty($tarea['trabajador_id'])): ?>
                                <a href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?></a>
                                <span class="tk-faint">(<?= tlm_safe($tarea['trabajador_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">Sin asignar</span>
                            <?php endif; ?>
                        </dd>
                        <dt>Reservaci&oacute;n</dt>
                        <dd><?= !empty($tarea['reservacion_id']) ? '#' . (int)$tarea['reservacion_id'] : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Hu&eacute;sped</dt>
                        <dd><?= !empty($tarea['huesped_nombre']) ? tlm_safe($tarea['huesped_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Mantenimiento</dt>
                        <dd>
                            <?php if (!empty($tarea['mantenimiento_id'])): ?>
                                #<?= (int)$tarea['mantenimiento_id'] ?> &middot; <?= tlm_safe($tarea['mantenimiento_motivo'] ?? '', '-') ?>
                                <span class="tk-faint">(<?= tlm_safe($tarea['mantenimiento_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">-</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </section>

                <section class="tk-card">
                    <h2>Fechas y responsables</h2>
                    <dl class="tk-defs">
                        <dt>Programada</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></dd>
                        <dt>L&iacute;mite</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></dd>
                        <dt>Inicio</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_inicio'] ?? null)) ?></dd>
                        <dt>Cierre</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_cierre'] ?? null)) ?></dd>
                        <dt>Creada por</dt><dd><?= !empty($tarea['creada_por_nombre']) ? tlm_safe($tarea['creada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Asignada por</dt><dd><?= !empty($tarea['asignada_por_nombre']) ? tlm_safe($tarea['asignada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Cerrada por</dt><dd><?= !empty($tarea['cerrada_por_nombre']) ? tlm_safe($tarea['cerrada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                    </dl>
                </section>

                <section class="tk-card">
                    <h2>Historial</h2>
                    <?php if (!$eventosDisponibles): ?>
                        <div class="tk-empty-box">El historial todav&iacute;a no est&aacute; disponible.</div>
                    <?php elseif (empty($eventos)): ?>
                        <div class="tk-empty-box">A&uacute;n no hay movimientos en esta tarea.</div>
                    <?php else: ?>
                        <div class="tk-timeline">
                            <?php foreach ($eventos as $evento): ?>
                                <div class="tk-event">
                                    <strong><?= tlm_safe($evento['tipo_evento'] ?? 'Evento') ?></strong>
                                    <span class="tk-faint"><?= tlm_safe(tlm_date($evento['created_at'] ?? null)) ?> &middot; <?= tlm_safe($evento['usuario_nombre'] ?? '', 'Sistema') ?></span>
                                    <?php if (!empty($evento['comentario'])): ?>
                                        <div class="tk-desc" style="margin-top:6px"><?= tlm_safe($evento['comentario']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="tk-col">
                <section class="tk-card">
                    <h2>Asignar a un trabajador</h2>
                    <?php if (!$puedeAsignar): ?>
                        <div class="tk-empty-box">Esta tarea no se puede asignar en su estado actual.</div>
                    <?php elseif (empty($trabajadoresActivos)): ?>
                        <div class="tk-empty-box">No hay trabajadores activos para asignar en este hotel.</div>
                    <?php else: ?>
                        <form method="POST" action="<?= url('tareas/' . $tareaId . '/asignar') ?>" class="tk-form-row">
                            <?= csrf_field() ?>
                            <div>
                                <label class="tk-label" for="tk_trabajador">Trabajador</label>
                                <select class="tk-field" id="tk_trabajador" name="trabajador_id" required>
                                    <option value="">Elige un trabajador</option>
                                    <?php foreach ($trabajadoresActivos as $trabajador): ?>
                                        <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                                        <option value="<?= $trabajadorId ?>" <?= $trabajadorId === (int)($tarea['trabajador_id'] ?? 0) ? 'selected' : '' ?>>
                                            <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorId)) ?><?= !empty($trabajador['rol_laboral']) ? ' - ' . tlm_safe($trabajador['rol_laboral']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button class="tk-btn tk-btn-gold" type="submit"><i class="fas fa-user-check"></i> Asignar</button>
                            <div class="tk-help">Solo trabajadores activos. Asignarla no genera pagos ni cambia la habitaci&oacute;n.</div>
                        </form>
                    <?php endif; ?>
                </section>

                <section class="tk-card">
                    <h2>Cambiar estado</h2>
                    <?php if (!$puedeCambiarEstado): ?>
                        <div class="tk-empty-box">Esta tarea ya no tiene cambios de estado disponibles.</div>
                    <?php else: ?>
                        <div class="tk-stack">
                            <?php if (in_array($estado, ['pendiente', 'asignada'], true)): ?>
                                <form method="POST" action="<?= url('tareas/' . $tareaId . '/iniciar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="tk-btn tk-btn-brand" type="submit"><i class="fas fa-play"></i> Iniciar</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" action="<?= url('tareas/' . $tareaId . '/completar') ?>" class="tk-form-row">
                                <?= csrf_field() ?>
                                <textarea class="tk-field" name="comentario" maxlength="800" placeholder="Nota de cierre (opcional)"></textarea>
                                <button class="tk-btn tk-btn-success" type="submit"><i class="fas fa-check"></i> Completar</button>
                            </form>

                            <form method="POST" action="<?= url('tareas/' . $tareaId . '/cancelar') ?>" class="tk-form-row">
                                <?= csrf_field() ?>
                                <textarea class="tk-field" name="comentario" maxlength="800" placeholder="Motivo de cancelaci&oacute;n (opcional)"></textarea>
                                <button class="tk-btn tk-btn-danger" type="submit"><i class="fas fa-ban"></i> Cancelar</button>
                            </form>

                            <div class="tk-help">Estos cambios solo afectan la tarea. No cambian la habitaci&oacute;n ni tocan Caja.</div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'tarea',
                'id' => $tareaId,
                'label' => 'Tarea',
            ],
            'documentosEntidadPermiteVerTodos' => true,
            'documentosEntidadPermiteVincular' => true,
        ]); ?>
    </div>
</div>
