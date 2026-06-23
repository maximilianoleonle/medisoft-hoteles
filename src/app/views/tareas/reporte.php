<?php
if (!function_exists('tlm_report_safe')) {
    function tlm_report_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_report_num')) {
    function tlm_report_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('tlm_report_date')) {
    function tlm_report_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_report_estado_meta')) {
    function tk_report_estado_meta($estado): array
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

$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$prioridades = is_array($reporte['prioridades'] ?? null) ? $reporte['prioridades'] : [];
$riesgos = is_array($reporte['riesgos'] ?? null) ? $reporte['riesgos'] : [];
$porTrabajador = is_array($reporte['por_trabajador'] ?? null) ? $reporte['por_trabajador'] : [];
$porHabitacion = is_array($reporte['por_habitacion'] ?? null) ? $reporte['por_habitacion'] : [];
$recientes = is_array($reporte['recientes'] ?? null) ? $reporte['recientes'] : [];
$eventosRecientes = is_array($reporte['eventos_recientes'] ?? null) ? $reporte['eventos_recientes'] : [];

$categoriaLabels = [
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
$prioridadLabels = [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'urgente' => 'Urgente',
];
?>

<style>
.tk-report {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 72%, #000);
    --tk-ivory: #F6F2EA; --tk-ivory-2: #FBF8F2;
    --tk-surface: #FFFFFF; --tk-surface-warm: #FCFAF5;
    --tk-border: color-mix(in srgb, var(--tk-brand) 7%, #E7E1D4);
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

.tk-report .tk-shell { display: grid; gap: 14px; }
.tk-report .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.tk-report .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, #2F8A70));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-report .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-report .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.tk-report .tk-subtitle { max-width: 50rem; margin: 8px 0 0; color: var(--tk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.tk-report .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--tk-border); background: var(--tk-surface); color: var(--tk-muted); font-weight: 700; font-size: .85rem; text-decoration: none;
    transition: transform .16s ease, border-color .16s ease, color .16s ease; }
.tk-report .tk-btn:hover { transform: translateY(-1px); border-color: var(--tk-gold-line); color: var(--tk-gold-ink); }

.tk-report .tk-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
.tk-report .tk-summary-item { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.tk-report .tk-summary-label { color: var(--tk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.tk-report .tk-summary-value { margin-top: 2px; font-family: var(--tk-serif); font-size: 1.6rem; font-weight: 700; line-height: 1.1; color: var(--tk-heading); }

.tk-report .tk-stack { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
.tk-report .tk-panel { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); overflow: hidden; }
.tk-report .tk-panel-head { padding: 15px 18px; border-bottom: 1px solid var(--tk-border); }
.tk-report .tk-panel-title { font-family: var(--tk-serif); font-size: 1.3rem; font-weight: 700; color: var(--tk-heading); margin: 0; }
.tk-report .tk-panel-sub { font-size: .78rem; color: var(--tk-muted); margin: 3px 0 0; }
.tk-report .tk-list { padding: 8px 18px; }
.tk-report .tk-list-row { display: flex; justify-content: space-between; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--tk-border); font-size: .88rem; align-items: center; }
.tk-report .tk-list-row:last-child { border-bottom: 0; }
.tk-report .tk-list-row strong { color: var(--tk-heading); }
.tk-report .tk-list-row.is-risk strong { color: var(--tk-danger); }
.tk-report .tk-list-row a { color: var(--tk-info); text-decoration: none; font-weight: 700; }
.tk-report .tk-list-row a:hover { text-decoration: underline; }
.tk-report .tk-dot { display: inline-flex; align-items: center; gap: 8px; }
.tk-report .tk-dot i { width: 16px; text-align: center; }

.tk-report .tk-table-wrap { overflow-x: auto; }
.tk-report .tk-table { width: 100%; border-collapse: collapse; min-width: 760px; font-size: .84rem; }
.tk-report .tk-table thead { background: var(--tk-surface-warm); border-bottom: 1px solid var(--tk-border); }
.tk-report .tk-table th { padding: 12px 14px; color: var(--tk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.tk-report .tk-table td { padding: 13px 14px; border-bottom: 1px solid var(--tk-border); vertical-align: top; }
.tk-report .tk-table tbody tr:last-child td { border-bottom: 0; }
.tk-report .tk-table tbody tr:hover { background: var(--tk-ivory-2); }
.tk-report .tk-link { font-weight: 700; color: var(--tk-heading); text-decoration: none; }
.tk-report .tk-link:hover { text-decoration: underline; text-decoration-color: var(--tk-gold); text-underline-offset: 3px; }
.tk-report .tk-sub { color: var(--tk-muted); font-size: .74rem; margin-top: 3px; }

.tk-report .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.tk-report .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 28%, #fff); }
.tk-report .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 26%, #fff); }
.tk-report .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 80%, #000); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 26%, #fff); }
.tk-report .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 78%, #000); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 26%, #fff); }
.tk-report .tk-badge.is-cancelada, .tk-report .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-report .tk-empty { padding: 36px 18px; text-align: center; color: var(--tk-muted); }
.tk-report .tk-empty strong { display: block; color: var(--tk-brand); font-size: 1.02rem; margin-bottom: 6px; font-weight: 700; }
.tk-report .tk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--tk-gold-soft); border: 1px solid var(--tk-gold-line); border-radius: 16px; }
.tk-report .tk-notice i { color: var(--tk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.tk-report .tk-notice strong { color: var(--tk-heading); display: block; margin-bottom: 2px; }
.tk-report .tk-notice p { color: var(--tk-muted); font-size: .88rem; margin: 0; }

@media (max-width: 900px) { .tk-report .tk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .tk-report .tk-stack { grid-template-columns: 1fr; } .tk-report .tk-title { font-size: 1.8rem; } }
</style>

<div class="tk-report p-4 sm:p-6">
    <div class="tk-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="tk-title-lockup">
                <div class="tk-hero-icon"><i class="fas fa-chart-pie"></i></div>
                <div>
                    <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                    <h1 class="tk-title">Reporte de tareas</h1>
                    <p class="tk-subtitle">Resumen de tus tareas por estado, prioridad, categor&iacute;a, trabajador y habitaci&oacute;n. Solo para consultar.</p>
                </div>
            </div>
            <a class="tk-btn" href="<?= url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="tk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para ver el reporte.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="tk-summary">
                <div class="tk-summary-item"><p class="tk-summary-label">Total</p><p class="tk-summary-value"><?= tlm_report_num($resumen['total'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Pendientes</p><p class="tk-summary-value" style="color:var(--tk-warning)"><?= tlm_report_num($resumen['pendiente'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Asignadas</p><p class="tk-summary-value" style="color:var(--tk-info)"><?= tlm_report_num($resumen['asignada'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">En proceso</p><p class="tk-summary-value" style="color:var(--tk-proc)"><?= tlm_report_num($resumen['en_proceso'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Completadas</p><p class="tk-summary-value" style="color:var(--tk-success)"><?= tlm_report_num($resumen['completada'] ?? 0) ?></p></div>
            </section>

            <div class="tk-stack">
                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <h2 class="tk-panel-title">Alertas</h2>
                        <p class="tk-panel-sub">Tareas que necesitan atenci&oacute;n.</p>
                    </div>
                    <div class="tk-list">
                        <div class="tk-list-row is-risk"><span class="tk-dot"><i class="fas fa-triangle-exclamation" style="color:var(--tk-danger)"></i> Vencidas</span><strong><?= tlm_report_num($riesgos['vencidas'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-clock" style="color:var(--tk-warning)"></i> Vencen en 24 h</span><strong><?= tlm_report_num($riesgos['proximas_24h'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-user-slash" style="color:var(--tk-muted)"></i> Activas sin asignar</span><strong><?= tlm_report_num($riesgos['sin_asignar_activas'] ?? 0) ?></strong></div>
                    </div>
                </section>

                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <h2 class="tk-panel-title">Prioridades y categor&iacute;as</h2>
                        <p class="tk-panel-sub">C&oacute;mo se reparten tus tareas.</p>
                    </div>
                    <div class="tk-list">
                        <?php foreach ($prioridadLabels as $key => $label): ?>
                            <div class="tk-list-row"><span><?= tlm_report_safe($label) ?></span><strong><?= tlm_report_num($prioridades[$key] ?? 0) ?></strong></div>
                        <?php endforeach; ?>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-broom" style="color:var(--tk-muted)"></i> Limpieza</span><strong><?= tlm_report_num($resumen['limpieza'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-screwdriver-wrench" style="color:var(--tk-muted)"></i> Mantenimiento</span><strong><?= tlm_report_num($resumen['mantenimiento'] ?? 0) ?></strong></div>
                        <div class="tk-list-row"><span class="tk-dot"><i class="fas fa-list-check" style="color:var(--tk-muted)"></i> General</span><strong><?= tlm_report_num($resumen['general'] ?? 0) ?></strong></div>
                    </div>
                </section>
            </div>

            <div class="tk-stack">
                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <h2 class="tk-panel-title">Carga por trabajador</h2>
                        <p class="tk-panel-sub">Tareas a cargo de cada persona.</p>
                    </div>
                    <?php if (empty($porTrabajador)): ?>
                        <div class="tk-empty"><strong>Sin trabajadores con tareas</strong>Nadie tiene tareas asignadas todav&iacute;a.</div>
                    <?php else: ?>
                        <div class="tk-list">
                            <?php foreach ($porTrabajador as $row): ?>
                                <div class="tk-list-row">
                                    <a href="<?= url('trabajadores/' . (int)($row['trabajador_id'] ?? 0)) ?>"><?= tlm_report_safe($row['trabajador_nombre'] ?? null) ?></a>
                                    <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="tk-panel">
                    <div class="tk-panel-head">
                        <h2 class="tk-panel-title">Carga por habitaci&oacute;n</h2>
                        <p class="tk-panel-sub">Tareas relacionadas con cada habitaci&oacute;n.</p>
                    </div>
                    <?php if (empty($porHabitacion)): ?>
                        <div class="tk-empty"><strong>Sin habitaciones con tareas</strong>No hay tareas ligadas a una habitaci&oacute;n.</div>
                    <?php else: ?>
                        <div class="tk-list">
                            <?php foreach ($porHabitacion as $row): ?>
                                <div class="tk-list-row">
                                    <a href="<?= url('habitaciones/' . (int)($row['habitacion_id'] ?? 0)) ?>">Habitaci&oacute;n <?= tlm_report_safe($row['habitacion_numero'] ?? null) ?></a>
                                    <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <section class="tk-panel">
                <div class="tk-panel-head">
                    <h2 class="tk-panel-title">Tareas recientes</h2>
                    <p class="tk-panel-sub">Las &uacute;ltimas tareas actualizadas, con enlace al detalle.</p>
                </div>
                <?php if (empty($recientes)): ?>
                    <div class="tk-empty"><strong>Sin tareas recientes</strong>A&uacute;n no hay tareas registradas en este hotel.</div>
                <?php else: ?>
                    <div class="tk-table-wrap">
                        <table class="tk-table">
                            <thead>
                                <tr><th>Tarea</th><th>Categor&iacute;a</th><th>Estado</th><th>Prioridad</th><th>Contexto</th><th>L&iacute;mite</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recientes as $tarea): ?>
                                    <?php
                                    [$eLabel, $eClass, $eIcon] = tk_report_estado_meta($tarea['estado'] ?? 'pendiente');
                                    $categoria = (string)($tarea['categoria'] ?? 'general');
                                    $prioridad = (string)($tarea['prioridad'] ?? 'media');
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="tk-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>"><?= tlm_report_safe($tarea['titulo'] ?? null, 'Tarea #' . (int)($tarea['id'] ?? 0)) ?></a>
                                            <div class="tk-sub">Actualizada: <?= tlm_report_safe(tlm_report_date($tarea['updated_at'] ?? null)) ?></div>
                                        </td>
                                        <td><?= tlm_report_safe($categoriaLabels[$categoria] ?? $categoria) ?></td>
                                        <td><span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                        <td><?= tlm_report_safe($prioridadLabels[$prioridad] ?? $prioridad) ?></td>
                                        <td>
                                            <?php if (!empty($tarea['habitacion_id'])): ?>
                                                Hab. <?= tlm_report_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($tarea['trabajador_id'])): ?>
                                                <?= tlm_report_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?>
                                            <?php elseif (empty($tarea['habitacion_id'])): ?>
                                                <span class="tk-sub" style="margin:0">Sin contexto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= tlm_report_safe(tlm_report_date($tarea['fecha_limite'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tk-panel">
                <div class="tk-panel-head">
                    <h2 class="tk-panel-title">Movimientos recientes</h2>
                    <p class="tk-panel-sub">Cambios registrados en las tareas.</p>
                </div>
                <?php if (empty($eventosRecientes)): ?>
                    <div class="tk-empty"><strong>Sin movimientos recientes</strong>Cuando haya tareas, aqu&iacute; ver&aacute;s su historial.</div>
                <?php else: ?>
                    <div class="tk-table-wrap">
                        <table class="tk-table">
                            <thead>
                                <tr><th>Evento</th><th>Tarea</th><th>Cambio</th><th>Usuario</th><th>Fecha</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventosRecientes as $evento): ?>
                                    <tr>
                                        <td><span class="tk-badge is-soft"><?= tlm_report_safe($evento['tipo_evento'] ?? null) ?></span></td>
                                        <td>
                                            <a class="tk-link" href="<?= url('tareas/' . (int)($evento['tarea_id'] ?? 0)) ?>"><?= tlm_report_safe($evento['tarea_titulo'] ?? null, 'Tarea #' . (int)($evento['tarea_id'] ?? 0)) ?></a>
                                            <div class="tk-sub"><?= tlm_report_safe($evento['comentario'] ?? null, '') ?></div>
                                        </td>
                                        <td><?= tlm_report_safe($evento['estado_anterior'] ?? null, '-') ?> &rarr; <?= tlm_report_safe($evento['estado_nuevo'] ?? null, '-') ?></td>
                                        <td><?= tlm_report_safe($evento['usuario_nombre'] ?? null, 'Sistema') ?></td>
                                        <td><?= tlm_report_safe(tlm_report_date($evento['created_at'] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
