<?php
if (!function_exists('tlm_agenda_safe')) {
    function tlm_agenda_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_agenda_num')) {
    function tlm_agenda_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('tlm_agenda_date')) {
    function tlm_agenda_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if (!$timestamp) {
            return '-';
        }

        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
    }
}

$agenda = is_array($agenda ?? null) ? $agenda : [];
$trabajadores = is_array($trabajadores ?? null) ? $trabajadores : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$filtros = is_array($agenda['filtros'] ?? null) ? $agenda['filtros'] : [];
$resumen = is_array($agenda['resumen'] ?? null) ? $agenda['resumen'] : [];
$tareas = is_array($agenda['tareas'] ?? null) ? $agenda['tareas'] : [];
$porTrabajador = is_array($resumen['por_trabajador'] ?? null) ? $resumen['por_trabajador'] : [];

$estadoLabels = [
    'activos' => 'Activas',
    'todos' => 'Todos',
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];
$categoriaLabels = [
    'todos' => 'Todas',
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
?>

<style>
.tlm-agenda{padding:24px;max-width:1320px;margin:0 auto;color:#172033}
.tlm-agenda-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;margin-bottom:20px}
.tlm-agenda-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:800}
.tlm-agenda-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.tlm-agenda-subtitle{color:#64748b;max-width:760px;margin:0}
.tlm-agenda-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
.tlm-agenda-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:7px;background:#172033;color:#fff;font-weight:800;padding:10px 16px;text-decoration:none;min-height:40px;cursor:pointer}
.tlm-agenda-btn--light{background:#fff;color:#172033;border:1px solid #d1d5db}
.tlm-agenda-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:18px 0 18px}
.tlm-agenda-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.tlm-agenda-metric span{display:block;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase}
.tlm-agenda-metric strong{display:block;font-size:26px;line-height:1.1;margin-top:8px;color:#111827}
.tlm-agenda-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden;margin-bottom:16px}
.tlm-agenda-filter{display:grid;grid-template-columns:repeat(2,minmax(0,1fr)) 1.4fr repeat(2,minmax(0,1fr)) auto;gap:10px;padding:16px;background:#f8fafc;border-bottom:1px solid #e5e7eb}
.tlm-agenda-control{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:10px 11px;font-size:14px;background:#fff;color:#172033}
.tlm-agenda-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:16px}
.tlm-agenda-table-wrap{overflow-x:auto}
.tlm-agenda-table{width:100%;border-collapse:collapse;min-width:940px}
.tlm-agenda-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.tlm-agenda-table td{padding:14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.tlm-agenda-table tr:hover td{background:#fafcff}
.tlm-agenda-link{font-weight:900;color:#102a43;text-decoration:none}
.tlm-agenda-muted{color:#64748b;font-size:13px;margin-top:4px}
.tlm-agenda-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.tlm-agenda-pill--pendiente{background:#fff7ed;color:#9a3412}
.tlm-agenda-pill--asignada{background:#eff6ff;color:#1d4ed8}
.tlm-agenda-pill--en_proceso{background:#ecfeff;color:#0e7490}
.tlm-agenda-pill--completada{background:#ecfdf5;color:#047857}
.tlm-agenda-pill--cancelada{background:#f1f5f9;color:#475569}
.tlm-agenda-side-head{padding:15px 16px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.tlm-agenda-side-title{font-size:15px;font-weight:900;margin:0;color:#111827}
.tlm-agenda-list{padding:12px 16px}
.tlm-agenda-row{display:flex;justify-content:space-between;gap:10px;padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px}
.tlm-agenda-row:last-child{border-bottom:0}
.tlm-agenda-empty{padding:42px 24px;text-align:center;color:#64748b}
.tlm-agenda-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
.tlm-agenda-note{padding:12px 16px;background:#f8fafc;border-top:1px solid #e5e7eb;color:#64748b;font-size:13px}
@media (max-width:980px){.tlm-agenda-head{display:block}.tlm-agenda-actions{justify-content:flex-start;margin-top:14px}.tlm-agenda-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.tlm-agenda-filter{grid-template-columns:1fr}.tlm-agenda-layout{grid-template-columns:1fr}.tlm-agenda-title{font-size:24px}}
</style>

<div class="tlm-agenda">
    <div class="tlm-agenda-head">
        <div>
            <div class="tlm-agenda-kicker">Operaciones / Tareas</div>
            <h1 class="tlm-agenda-title">Agenda de tareas</h1>
            <p class="tlm-agenda-subtitle">Carga operativa por fecha, trabajador, categoria y estado. Esta vista es solo lectura y no cambia asignaciones ni estados.</p>
        </div>
        <div class="tlm-agenda-actions">
            <a class="tlm-agenda-btn tlm-agenda-btn--light" href="<?= url('tareas') ?>">
                <i class="fas fa-arrow-left"></i>
                Tareas
            </a>
            <a class="tlm-agenda-btn tlm-agenda-btn--light" href="<?= url('tareas/reporte') ?>">
                <i class="fas fa-chart-pie"></i>
                Reporte
            </a>
        </div>
    </div>

    <div class="tlm-agenda-grid" aria-label="Resumen de agenda">
        <div class="tlm-agenda-metric"><span>Total en rango</span><strong><?= tlm_agenda_num($resumen['total'] ?? 0) ?></strong></div>
        <div class="tlm-agenda-metric"><span>Activas</span><strong><?= tlm_agenda_num($resumen['activas'] ?? 0) ?></strong></div>
        <div class="tlm-agenda-metric"><span>Sin asignar</span><strong><?= tlm_agenda_num($resumen['sin_asignar'] ?? 0) ?></strong></div>
        <div class="tlm-agenda-metric"><span>Trabajadores</span><strong><?= tlm_agenda_num(count($porTrabajador)) ?></strong></div>
    </div>

    <section class="tlm-agenda-panel">
        <form method="GET" action="<?= url('tareas/agenda') ?>" class="tlm-agenda-filter">
            <input class="tlm-agenda-control" type="date" name="desde" value="<?= tlm_agenda_safe($filtros['desde'] ?? date('Y-m-d'), date('Y-m-d')) ?>">
            <input class="tlm-agenda-control" type="date" name="hasta" value="<?= tlm_agenda_safe($filtros['hasta'] ?? date('Y-m-d'), date('Y-m-d')) ?>">
            <select class="tlm-agenda-control" name="trabajador_id">
                <option value="todos">Todos los trabajadores</option>
                <?php foreach ($trabajadores as $trabajador): ?>
                    <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                    <option value="<?= $trabajadorId ?>" <?= (string)($filtros['trabajador_id'] ?? 'todos') === (string)$trabajadorId ? 'selected' : '' ?>>
                        <?= tlm_agenda_safe($trabajador['nombre_completo'] ?? null) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select class="tlm-agenda-control" name="categoria">
                <?php foreach ($categoriaLabels as $key => $label): ?>
                    <option value="<?= tlm_agenda_safe($key) ?>" <?= ($filtros['categoria'] ?? 'todos') === $key ? 'selected' : '' ?>><?= tlm_agenda_safe($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tlm-agenda-control" name="estado">
                <?php foreach ($estadoLabels as $key => $label): ?>
                    <option value="<?= tlm_agenda_safe($key) ?>" <?= ($filtros['estado'] ?? 'activos') === $key ? 'selected' : '' ?>><?= tlm_agenda_safe($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="tlm-agenda-btn">
                <i class="fas fa-filter"></i>
                Filtrar
            </button>
        </form>
    </section>

    <?php if (!$tablaDisponible): ?>
        <section class="tlm-agenda-panel">
            <div class="tlm-agenda-empty">
                <strong>Base de tareas no disponible</strong>
                La migracion TLM-A debe estar aplicada antes de usar esta agenda.
            </div>
        </section>
    <?php else: ?>
        <div class="tlm-agenda-layout">
            <section class="tlm-agenda-panel">
                <?php if (empty($tareas)): ?>
                    <div class="tlm-agenda-empty">
                        <strong>Sin tareas en el rango</strong>
                        Ajusta fecha, trabajador, categoria o estado para revisar otra carga operativa.
                    </div>
                <?php else: ?>
                    <div class="tlm-agenda-table-wrap">
                        <table class="tlm-agenda-table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tarea</th>
                                    <th>Trabajador</th>
                                    <th>Contexto</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tareas as $tarea): ?>
                                    <?php
                                    $estado = (string)($tarea['estado'] ?? 'pendiente');
                                    $categoria = (string)($tarea['categoria'] ?? 'general');
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= tlm_agenda_safe(tlm_agenda_date($tarea['fecha_agenda'] ?? null)) ?></strong>
                                            <div class="tlm-agenda-muted">Prog: <?= tlm_agenda_safe(tlm_agenda_date($tarea['fecha_programada'] ?? null, true)) ?></div>
                                        </td>
                                        <td>
                                            <a class="tlm-agenda-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                                <?= tlm_agenda_safe($tarea['titulo'] ?? null, 'Tarea #' . (int)($tarea['id'] ?? 0)) ?>
                                            </a>
                                            <div class="tlm-agenda-muted"><?= tlm_agenda_safe($categoriaLabels[$categoria] ?? $categoria) ?> - origen <?= tlm_agenda_safe($tarea['origen'] ?? 'manual') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($tarea['trabajador_id'])): ?>
                                                <a class="tlm-agenda-link" href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>">
                                                    <?= tlm_agenda_safe($tarea['trabajador_nombre'] ?? null) ?>
                                                </a>
                                                <div class="tlm-agenda-muted"><?= tlm_agenda_safe($tarea['trabajador_rol'] ?? null, '') ?></div>
                                            <?php else: ?>
                                                <span class="tlm-agenda-muted">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($tarea['habitacion_id'])): ?>
                                                <a href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_agenda_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a>
                                                <div class="tlm-agenda-muted"><?= tlm_agenda_safe($tarea['habitacion_estado'] ?? null, '') ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($tarea['mantenimiento_id'])): ?>
                                                <div class="tlm-agenda-muted">Mant. #<?= (int)$tarea['mantenimiento_id'] ?> <?= tlm_agenda_safe($tarea['tipo_mantenimiento'] ?? null, '') ?></div>
                                            <?php elseif (empty($tarea['habitacion_id'])): ?>
                                                <span class="tlm-agenda-muted">Sin entidad vinculada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="tlm-agenda-pill tlm-agenda-pill--<?= tlm_agenda_safe($estado) ?>"><?= tlm_agenda_safe($estadoLabels[$estado] ?? $estado) ?></span></td>
                                        <td><?= tlm_agenda_safe(ucfirst((string)($tarea['prioridad'] ?? 'media'))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="tlm-agenda-panel">
                <div class="tlm-agenda-side-head">
                    <h2 class="tlm-agenda-side-title">Carga por trabajador</h2>
                </div>
                <?php if (empty($porTrabajador)): ?>
                    <div class="tlm-agenda-empty">
                        <strong>Sin carga asignada</strong>
                        No hay trabajadores con tareas dentro del filtro.
                    </div>
                <?php else: ?>
                    <div class="tlm-agenda-list">
                        <?php foreach ($porTrabajador as $nombre => $total): ?>
                            <div class="tlm-agenda-row">
                                <span><?= tlm_agenda_safe($nombre) ?></span>
                                <strong><?= tlm_agenda_num($total) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="tlm-agenda-note">
                    TLM-J-A es solo lectura: sin asignar, iniciar, completar, cancelar, Caja, nomina, offline ni /api/sync.
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
