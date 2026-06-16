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

$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$prioridades = is_array($reporte['prioridades'] ?? null) ? $reporte['prioridades'] : [];
$riesgos = is_array($reporte['riesgos'] ?? null) ? $reporte['riesgos'] : [];
$porTrabajador = is_array($reporte['por_trabajador'] ?? null) ? $reporte['por_trabajador'] : [];
$porHabitacion = is_array($reporte['por_habitacion'] ?? null) ? $reporte['por_habitacion'] : [];
$recientes = is_array($reporte['recientes'] ?? null) ? $reporte['recientes'] : [];
$eventosRecientes = is_array($reporte['eventos_recientes'] ?? null) ? $reporte['eventos_recientes'] : [];

$estadoLabels = [
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];
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
.tlm-report{padding:24px;max-width:1280px;margin:0 auto;color:#172033}
.tlm-report-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:22px}
.tlm-report-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:800}
.tlm-report-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.tlm-report-subtitle{color:#64748b;max-width:760px;margin:0}
.tlm-report-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:7px;background:#fff;color:#172033;border:1px solid #d1d5db;font-weight:800;padding:10px 16px;text-decoration:none;min-height:40px}
.tlm-report-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:18px 0 22px}
.tlm-report-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.tlm-report-metric span{display:block;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase}
.tlm-report-metric strong{display:block;font-size:26px;line-height:1.1;margin-top:8px;color:#111827}
.tlm-report-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.tlm-report-panel-head{padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.tlm-report-panel-title{font-size:16px;font-weight:900;color:#111827;margin:0}
.tlm-report-panel-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
.tlm-report-stack{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.tlm-report-list{padding:14px 18px}
.tlm-report-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px}
.tlm-report-row:last-child{border-bottom:0}
.tlm-report-table-wrap{overflow-x:auto}
.tlm-report-table{width:100%;border-collapse:collapse;min-width:820px}
.tlm-report-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.tlm-report-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.tlm-report-link{font-weight:900;color:#102a43;text-decoration:none}
.tlm-report-muted{color:#64748b;font-size:13px;margin-top:4px}
.tlm-report-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.tlm-report-alert{padding:14px 18px;background:#fff7ed;color:#9a3412;border-top:1px solid #fed7aa;font-size:13px;font-weight:700}
.tlm-report-empty{padding:42px 24px;text-align:center;color:#64748b}
.tlm-report-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
@media (max-width:900px){.tlm-report-head{display:block}.tlm-report-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.tlm-report-stack{grid-template-columns:1fr}.tlm-report-title{font-size:24px}.tlm-report-btn{margin-top:14px}}
</style>

<div class="tlm-report">
    <div class="tlm-report-head">
        <div>
            <div class="tlm-report-kicker">Operaciones / Tareas</div>
            <h1 class="tlm-report-title">Reporte operativo</h1>
            <p class="tlm-report-subtitle">Lectura consolidada de tareas por estado, categoria, prioridad, trabajador y habitacion. No asigna, inicia, completa ni cancela tareas.</p>
        </div>
        <a class="tlm-report-btn" href="<?= url('tareas') ?>">
            <i class="fas fa-arrow-left"></i>
            Volver a tareas
        </a>
    </div>

    <?php if (!$tablaDisponible): ?>
        <div class="tlm-report-panel">
            <div class="tlm-report-empty">
                <strong>Base de tareas no disponible</strong>
                La migracion TLM-A debe estar aplicada antes de usar este reporte.
            </div>
        </div>
    <?php else: ?>
        <div class="tlm-report-grid" aria-label="Resumen operativo de tareas">
            <div class="tlm-report-metric"><span>Total</span><strong><?= tlm_report_num($resumen['total'] ?? 0) ?></strong></div>
            <div class="tlm-report-metric"><span>Pendientes</span><strong><?= tlm_report_num($resumen['pendiente'] ?? 0) ?></strong></div>
            <div class="tlm-report-metric"><span>Asignadas</span><strong><?= tlm_report_num($resumen['asignada'] ?? 0) ?></strong></div>
            <div class="tlm-report-metric"><span>En proceso</span><strong><?= tlm_report_num($resumen['en_proceso'] ?? 0) ?></strong></div>
            <div class="tlm-report-metric"><span>Completadas</span><strong><?= tlm_report_num($resumen['completada'] ?? 0) ?></strong></div>
        </div>

        <div class="tlm-report-stack">
            <section class="tlm-report-panel">
                <div class="tlm-report-panel-head">
                    <h2 class="tlm-report-panel-title">Riesgos operativos</h2>
                    <p class="tlm-report-panel-subtitle">Solo lectura. No modifica fechas, asignaciones ni estados.</p>
                </div>
                <div class="tlm-report-list">
                    <div class="tlm-report-row"><span>Vencidas activas</span><strong><?= tlm_report_num($riesgos['vencidas'] ?? 0) ?></strong></div>
                    <div class="tlm-report-row"><span>Vencen en 24h</span><strong><?= tlm_report_num($riesgos['proximas_24h'] ?? 0) ?></strong></div>
                    <div class="tlm-report-row"><span>Activas sin asignar</span><strong><?= tlm_report_num($riesgos['sin_asignar_activas'] ?? 0) ?></strong></div>
                </div>
            </section>

            <section class="tlm-report-panel">
                <div class="tlm-report-panel-head">
                    <h2 class="tlm-report-panel-title">Prioridades y categorias</h2>
                    <p class="tlm-report-panel-subtitle">Distribucion actual del backlog operativo.</p>
                </div>
                <div class="tlm-report-list">
                    <?php foreach ($prioridadLabels as $key => $label): ?>
                        <div class="tlm-report-row"><span><?= tlm_report_safe($label) ?></span><strong><?= tlm_report_num($prioridades[$key] ?? 0) ?></strong></div>
                    <?php endforeach; ?>
                    <div class="tlm-report-row"><span>Limpieza</span><strong><?= tlm_report_num($resumen['limpieza'] ?? 0) ?></strong></div>
                    <div class="tlm-report-row"><span>Mantenimiento</span><strong><?= tlm_report_num($resumen['mantenimiento'] ?? 0) ?></strong></div>
                    <div class="tlm-report-row"><span>General</span><strong><?= tlm_report_num($resumen['general'] ?? 0) ?></strong></div>
                </div>
            </section>
        </div>

        <div class="tlm-report-stack">
            <section class="tlm-report-panel">
                <div class="tlm-report-panel-head">
                    <h2 class="tlm-report-panel-title">Carga por trabajador</h2>
                    <p class="tlm-report-panel-subtitle">Tareas vinculadas a trabajadores del hotel actual.</p>
                </div>
                <?php if (empty($porTrabajador)): ?>
                    <div class="tlm-report-empty"><strong>Sin trabajadores asignados</strong>No hay tareas vinculadas a trabajadores.</div>
                <?php else: ?>
                    <div class="tlm-report-list">
                        <?php foreach ($porTrabajador as $row): ?>
                            <div class="tlm-report-row">
                                <a class="tlm-report-link" href="<?= url('trabajadores/' . (int)($row['trabajador_id'] ?? 0)) ?>"><?= tlm_report_safe($row['trabajador_nombre'] ?? null) ?></a>
                                <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="tlm-report-panel">
                <div class="tlm-report-panel-head">
                    <h2 class="tlm-report-panel-title">Carga por habitacion</h2>
                    <p class="tlm-report-panel-subtitle">Contexto de habitacion en lectura; no cambia disponibilidad.</p>
                </div>
                <?php if (empty($porHabitacion)): ?>
                    <div class="tlm-report-empty"><strong>Sin habitaciones vinculadas</strong>No hay tareas relacionadas con habitaciones.</div>
                <?php else: ?>
                    <div class="tlm-report-list">
                        <?php foreach ($porHabitacion as $row): ?>
                            <div class="tlm-report-row">
                                <a class="tlm-report-link" href="<?= url('habitaciones/' . (int)($row['habitacion_id'] ?? 0)) ?>">Habitacion <?= tlm_report_safe($row['habitacion_numero'] ?? null) ?></a>
                                <span><?= tlm_report_num($row['activas'] ?? 0) ?> activas / <?= tlm_report_num($row['total'] ?? 0) ?> total</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="tlm-report-panel mb-4">
            <div class="tlm-report-panel-head">
                <h2 class="tlm-report-panel-title">Tareas recientes</h2>
                <p class="tlm-report-panel-subtitle">Ultimas tareas actualizadas, con enlaces al detalle operativo.</p>
            </div>
            <?php if (empty($recientes)): ?>
                <div class="tlm-report-empty"><strong>Sin tareas recientes</strong>No hay tareas operativas registradas para este hotel.</div>
            <?php else: ?>
                <div class="tlm-report-table-wrap">
                    <table class="tlm-report-table">
                        <thead>
                            <tr>
                                <th>Tarea</th>
                                <th>Categoria</th>
                                <th>Estado</th>
                                <th>Prioridad</th>
                                <th>Contexto</th>
                                <th>Limite</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recientes as $tarea): ?>
                                <?php
                                $estado = (string)($tarea['estado'] ?? 'pendiente');
                                $categoria = (string)($tarea['categoria'] ?? 'general');
                                $prioridad = (string)($tarea['prioridad'] ?? 'media');
                                ?>
                                <tr>
                                    <td>
                                        <a class="tlm-report-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                            <?= tlm_report_safe($tarea['titulo'] ?? null, 'Tarea #' . (int)($tarea['id'] ?? 0)) ?>
                                        </a>
                                        <div class="tlm-report-muted">Actualizada: <?= tlm_report_safe(tlm_report_date($tarea['updated_at'] ?? null)) ?></div>
                                    </td>
                                    <td><?= tlm_report_safe($categoriaLabels[$categoria] ?? $categoria) ?></td>
                                    <td><span class="tlm-report-pill"><?= tlm_report_safe($estadoLabels[$estado] ?? $estado) ?></span></td>
                                    <td><?= tlm_report_safe($prioridadLabels[$prioridad] ?? $prioridad) ?></td>
                                    <td>
                                        <?php if (!empty($tarea['habitacion_id'])): ?>
                                            Hab. <?= tlm_report_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($tarea['trabajador_id'])): ?>
                                            <?= tlm_report_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?>
                                        <?php elseif (empty($tarea['habitacion_id'])): ?>
                                            <span class="tlm-report-muted">Sin contexto</span>
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

        <section class="tlm-report-panel">
            <div class="tlm-report-panel-head">
                <h2 class="tlm-report-panel-title">Eventos recientes</h2>
                <p class="tlm-report-panel-subtitle">Historial tecnico de tareas, sin acciones desde este reporte.</p>
            </div>
            <?php if (empty($eventosRecientes)): ?>
                <div class="tlm-report-empty"><strong>Sin eventos recientes</strong>Cuando existan tareas, aqui aparecera su trazabilidad.</div>
            <?php else: ?>
                <div class="tlm-report-table-wrap">
                    <table class="tlm-report-table">
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Tarea</th>
                                <th>Cambio</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventosRecientes as $evento): ?>
                                <tr>
                                    <td><span class="tlm-report-pill"><?= tlm_report_safe($evento['tipo_evento'] ?? null) ?></span></td>
                                    <td>
                                        <a class="tlm-report-link" href="<?= url('tareas/' . (int)($evento['tarea_id'] ?? 0)) ?>">
                                            <?= tlm_report_safe($evento['tarea_titulo'] ?? null, 'Tarea #' . (int)($evento['tarea_id'] ?? 0)) ?>
                                        </a>
                                        <div class="tlm-report-muted"><?= tlm_report_safe($evento['comentario'] ?? null, '') ?></div>
                                    </td>
                                    <td><?= tlm_report_safe($evento['estado_anterior'] ?? null, '-') ?> -> <?= tlm_report_safe($evento['estado_nuevo'] ?? null, '-') ?></td>
                                    <td><?= tlm_report_safe($evento['usuario_nombre'] ?? null, 'Sistema') ?></td>
                                    <td><?= tlm_report_safe(tlm_report_date($evento['created_at'] ?? null)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="tlm-report-alert">Este reporte no cambia estados de habitacion, no genera asistencia, no crea pagos y no registra movimientos de Caja.</div>
        </section>
    <?php endif; ?>
</div>
