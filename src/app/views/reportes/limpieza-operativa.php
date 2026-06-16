<?php
if (!function_exists('lim_rep_safe')) {
    function lim_rep_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lim_rep_num')) {
    function lim_rep_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('lim_rep_date')) {
    function lim_rep_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
$porPiso = is_array($reporte['por_piso'] ?? null) ? $reporte['por_piso'] : [];
$tareasActivas = is_array($reporte['tareas_activas'] ?? null) ? $reporte['tareas_activas'] : [];
$puedeCrearTareaLimpieza = function_exists('can') ? can('habitaciones.mantenimiento') : false;
?>

<style>
.lim-rep{padding:24px;max-width:1320px;margin:0 auto;color:#172033}
.lim-rep-hero{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:20px}
.lim-rep-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.lim-rep-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.lim-rep-subtitle{color:#64748b;max-width:820px;margin:0}
.lim-rep-actions{display:flex;gap:10px;flex-wrap:wrap}
.lim-rep-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:7px;background:#fff;color:#172033;border:1px solid #d1d5db;font-weight:800;padding:10px 14px;text-decoration:none;min-height:40px}
.lim-rep-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}
.lim-rep-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.lim-rep-metric span{display:block;font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase}
.lim-rep-metric strong{display:block;font-size:25px;line-height:1.1;margin-top:8px;color:#111827}
.lim-rep-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px}
.lim-rep-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.lim-rep-panel-head{padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.lim-rep-panel-title{font-size:16px;font-weight:900;color:#111827;margin:0}
.lim-rep-panel-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
.lim-rep-table-wrap{overflow-x:auto}
.lim-rep-table{width:100%;border-collapse:collapse;min-width:860px}
.lim-rep-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.lim-rep-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top;font-size:14px}
.lim-rep-link{font-weight:900;color:#102a43;text-decoration:none}
.lim-rep-muted{color:#64748b;font-size:13px;margin-top:4px}
.lim-rep-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900;background:#e0f2fe;color:#075985}
.lim-rep-pill.is-task{background:#eef2ff;color:#3730a3}
.lim-rep-pill.is-empty{background:#f8fafc;color:#64748b}
.lim-rep-inline-form{margin-top:9px}
.lim-rep-task-create{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:0;border-radius:7px;background:#172033;color:#fff;font-size:12px;font-weight:900;padding:8px 10px;cursor:pointer}
.lim-rep-task-create:hover{background:#0f172a}
.lim-rep-side{display:flex;flex-direction:column;gap:16px}
.lim-rep-list{display:flex;flex-direction:column}
.lim-rep-list-item{padding:12px 14px;border-bottom:1px solid #eef2f7}
.lim-rep-list-item:last-child{border-bottom:0}
.lim-rep-empty{padding:38px 20px;text-align:center;color:#64748b}
.lim-rep-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
.lim-rep-note{padding:14px 18px;background:#f8fafc;color:#475569;border-top:1px solid #e5e7eb;font-size:13px;font-weight:700}
@media (max-width:1100px){.lim-rep-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.lim-rep-layout{grid-template-columns:1fr}}
@media (max-width:720px){.lim-rep-hero{display:block}.lim-rep-actions{margin-top:14px}.lim-rep-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.lim-rep-title{font-size:24px}}
</style>

<div class="lim-rep">
    <div class="lim-rep-hero">
        <div>
            <div class="lim-rep-kicker">Reportes / Limpieza</div>
            <h1 class="lim-rep-title">Limpieza operativa</h1>
            <p class="lim-rep-subtitle">Vista read-only de habitaciones en limpieza y tareas activas asociadas al hotel actual. No libera habitaciones, no crea tareas y no ejecuta automatizaciones.</p>
        </div>
        <div class="lim-rep-actions">
            <a class="lim-rep-btn" href="<?= url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="lim-rep-btn" href="<?= url('habitaciones?estado=limpieza') ?>"><i class="fas fa-broom"></i> Habitaciones</a>
        </div>
    </div>

    <div class="lim-rep-grid" aria-label="Resumen de limpieza">
        <div class="lim-rep-metric"><span>Habitaciones activas</span><strong><?= lim_rep_num($resumen['habitaciones_activas'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>En limpieza</span><strong><?= lim_rep_num($resumen['en_limpieza'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Con tarea activa</span><strong><?= lim_rep_num($resumen['con_tarea_activa'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Sin tarea activa</span><strong><?= lim_rep_num($resumen['sin_tarea_activa'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Tareas limpieza</span><strong><?= lim_rep_num($resumen['tareas_limpieza_activas'] ?? 0) ?></strong></div>
    </div>

    <div class="lim-rep-layout">
        <section class="lim-rep-panel">
            <div class="lim-rep-panel-head">
                <h2 class="lim-rep-panel-title">Habitaciones en limpieza</h2>
                <p class="lim-rep-panel-subtitle">Ordenadas por ultima actualizacion conocida. La fecha no reemplaza bitacora formal de limpieza.</p>
            </div>

            <?php if (empty($habitaciones)): ?>
                <div class="lim-rep-empty">
                    <strong>Sin habitaciones en limpieza</strong>
                    <p>No hay pendientes de limpieza para el hotel actual.</p>
                </div>
            <?php else: ?>
                <div class="lim-rep-table-wrap">
                    <table class="lim-rep-table">
                        <thead>
                            <tr>
                                <th>Habitacion</th>
                                <th>Tipo / piso</th>
                                <th>Ultima salida</th>
                                <th>Ultima actualizacion</th>
                                <th>Tarea activa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($habitaciones as $habitacion): ?>
                                <?php
                                $tareasCount = (int)($habitacion['tareas_activas_limpieza'] ?? 0);
                                $tareaId = (int)($habitacion['tarea_activa_id'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <a class="lim-rep-link" href="<?= url('habitaciones/' . (int)($habitacion['id'] ?? 0)) ?>">
                                            Habitacion <?= lim_rep_safe($habitacion['numero'] ?? null) ?>
                                        </a>
                                        <div class="lim-rep-muted"><?= lim_rep_safe(ucfirst((string)($habitacion['estado'] ?? 'limpieza'))) ?></div>
                                    </td>
                                    <td>
                                        <?= lim_rep_safe($habitacion['tipo'] ?? null) ?>
                                        <div class="lim-rep-muted">Piso <?= lim_rep_safe($habitacion['piso'] ?? null) ?></div>
                                    </td>
                                    <td><?= lim_rep_safe(lim_rep_date($habitacion['ultima_salida'] ?? null)) ?></td>
                                    <td><?= lim_rep_safe(lim_rep_date($habitacion['updated_at'] ?? null)) ?></td>
                                    <td>
                                        <?php if ($tareasCount > 0 && $tareaId > 0): ?>
                                            <a class="lim-rep-pill is-task" href="<?= url('tareas/' . $tareaId) ?>">
                                                <?= lim_rep_safe($habitacion['tarea_activa_titulo'] ?? ('Tarea #' . $tareaId)) ?>
                                            </a>
                                            <div class="lim-rep-muted"><?= lim_rep_num($tareasCount) ?> tarea(s) activa(s)</div>
                                        <?php else: ?>
                                            <span class="lim-rep-pill is-empty">Sin tarea activa</span>
                                            <?php if ($puedeCrearTareaLimpieza): ?>
                                                <form class="lim-rep-inline-form"
                                                      method="POST"
                                                      action="<?= url('tareas/desde-limpieza/' . (int)($habitacion['id'] ?? 0)) ?>"
                                                      onsubmit="return confirm('Crear una tarea de limpieza para esta habitacion?')">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="lim-rep-task-create">
                                                        <i class="fas fa-tasks"></i>
                                                        Crear tarea
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="lim-rep-note">
                LIM-B-A agrega solo creacion manual de tarea de limpieza con permiso y CSRF. No libera habitaciones, no cambia estados, no descuenta inventario y no toca Caja, pagos, abonos, nomina, offline ni /api/sync.
            </div>
        </section>

        <aside class="lim-rep-side">
            <section class="lim-rep-panel">
                <div class="lim-rep-panel-head">
                    <h2 class="lim-rep-panel-title">Por piso</h2>
                    <p class="lim-rep-panel-subtitle">Distribucion actual de habitaciones en limpieza.</p>
                </div>
                <div class="lim-rep-list">
                    <?php if (empty($porPiso)): ?>
                        <div class="lim-rep-list-item lim-rep-muted">Sin habitaciones en limpieza.</div>
                    <?php else: ?>
                        <?php foreach ($porPiso as $piso): ?>
                            <div class="lim-rep-list-item">
                                <strong>Piso <?= lim_rep_safe($piso['piso'] ?? null) ?></strong>
                                <div class="lim-rep-muted"><?= lim_rep_num($piso['total'] ?? 0) ?> habitacion(es)</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lim-rep-panel">
                <div class="lim-rep-panel-head">
                    <h2 class="lim-rep-panel-title">Tareas activas</h2>
                    <p class="lim-rep-panel-subtitle">Categoria limpieza, solo seguimiento operativo.</p>
                </div>
                <div class="lim-rep-list">
                    <?php if (empty($tareasActivas)): ?>
                        <div class="lim-rep-list-item lim-rep-muted">Sin tareas activas de limpieza.</div>
                    <?php else: ?>
                        <?php foreach ($tareasActivas as $tarea): ?>
                            <div class="lim-rep-list-item">
                                <a class="lim-rep-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                    <?= lim_rep_safe($tarea['titulo'] ?? ('Tarea #' . (int)($tarea['id'] ?? 0))) ?>
                                </a>
                                <div class="lim-rep-muted">
                                    <?= lim_rep_safe(ucfirst((string)($tarea['estado'] ?? ''))) ?> -
                                    <?= lim_rep_safe(ucfirst((string)($tarea['prioridad'] ?? ''))) ?>
                                    <?php if (!empty($tarea['habitacion_numero'])): ?>
                                        - Hab. <?= lim_rep_safe($tarea['habitacion_numero']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</div>
