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

$tareas = is_array($tareas ?? null) ? $tareas : [];
$resumen = is_array($resumen ?? null) ? $resumen : [];
$filtros = is_array($filtros ?? null) ? $filtros : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$puedeCrear = function_exists('can') && can('habitaciones.mantenimiento');

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
.tlm-page{padding:24px;max-width:1280px;margin:0 auto;color:#172033}
.tlm-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:22px}
.tlm-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:800}
.tlm-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.tlm-subtitle{color:#64748b;max-width:720px;margin:0}
.tlm-status{padding:10px 12px;border-radius:8px;background:#f8fafc;border:1px solid #e2e8f0;color:#475569;font-size:13px}
.tlm-actions{display:flex;flex-direction:column;gap:10px;align-items:flex-end}
.tlm-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin:18px 0 22px}
.tlm-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.tlm-metric span{display:block;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase}
.tlm-metric strong{display:block;font-size:26px;line-height:1.1;margin-top:8px;color:#111827}
.tlm-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.tlm-filter{display:grid;grid-template-columns:2fr repeat(3,1fr) auto;gap:10px;padding:16px;border-bottom:1px solid #e5e7eb;background:#f8fafc}
.tlm-control{width:100%;border:1px solid #d1d5db;border-radius:7px;padding:10px 11px;font-size:14px;background:#fff;color:#172033}
.tlm-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:7px;background:#172033;color:#fff;font-weight:800;padding:10px 16px;cursor:pointer;text-decoration:none;min-height:40px}
.tlm-btn--light{background:#fff;color:#172033;border:1px solid #d1d5db}
.tlm-table-wrap{overflow-x:auto}
.tlm-table{width:100%;border-collapse:collapse;min-width:920px}
.tlm-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.tlm-table td{padding:14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.tlm-table tr:hover td{background:#fafcff}
.tlm-main-link{font-weight:900;color:#102a43;text-decoration:none}
.tlm-muted{color:#64748b;font-size:13px;margin-top:4px}
.tlm-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.tlm-pill--pendiente{background:#fff7ed;color:#9a3412}
.tlm-pill--asignada{background:#eff6ff;color:#1d4ed8}
.tlm-pill--en_proceso{background:#ecfeff;color:#0e7490}
.tlm-pill--completada{background:#ecfdf5;color:#047857}
.tlm-pill--cancelada{background:#f1f5f9;color:#475569}
.tlm-empty{padding:46px 24px;text-align:center;color:#64748b}
.tlm-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
@media (max-width:900px){.tlm-head{display:block}.tlm-actions{align-items:flex-start;margin-top:14px}.tlm-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.tlm-filter{grid-template-columns:1fr}.tlm-title{font-size:24px}}
</style>

<div class="tlm-page">
    <div class="tlm-head">
        <div>
            <div class="tlm-kicker">Operaciones</div>
            <h1 class="tlm-title">Tareas operativas</h1>
            <p class="tlm-subtitle">Tareas de limpieza, mantenimiento y pendientes generales por hotel. La creacion manual no cambia estados de habitaciones ni integra Caja.</p>
        </div>
        <div class="tlm-actions">
            <?php if ($puedeCrear): ?>
                <a class="tlm-btn" href="<?= url('tareas/crear') ?>">
                    <i class="fas fa-plus"></i>
                    Nueva tarea
                </a>
            <?php endif; ?>
            <a class="tlm-btn tlm-btn--light" href="<?= url('tareas/reporte') ?>">
                <i class="fas fa-chart-pie"></i>
                Reporte
            </a>
            <a class="tlm-btn tlm-btn--light" href="<?= url('tareas/agenda') ?>">
                <i class="fas fa-calendar-day"></i>
                Agenda
            </a>
            <div class="tlm-status">
                Fase TLM-I-A: operacion manual controlada y reporte read-only. Sin cambios de habitacion ni Caja.
            </div>
        </div>
    </div>

    <div class="tlm-grid" aria-label="Resumen de tareas">
        <div class="tlm-metric"><span>Total</span><strong><?= number_format((int)($resumen['total'] ?? 0)) ?></strong></div>
        <div class="tlm-metric"><span>Pendientes</span><strong><?= number_format((int)($resumen['pendiente'] ?? 0)) ?></strong></div>
        <div class="tlm-metric"><span>Asignadas</span><strong><?= number_format((int)($resumen['asignada'] ?? 0)) ?></strong></div>
        <div class="tlm-metric"><span>En proceso</span><strong><?= number_format((int)($resumen['en_proceso'] ?? 0)) ?></strong></div>
        <div class="tlm-metric"><span>Completadas</span><strong><?= number_format((int)($resumen['completada'] ?? 0)) ?></strong></div>
    </div>

    <div class="tlm-panel">
        <form method="GET" action="<?= url('tareas') ?>" class="tlm-filter">
            <input class="tlm-control" type="search" name="buscar" value="<?= tlm_safe($filtros['buscar'] ?? '') ?>" placeholder="Buscar por titulo, descripcion, habitacion o trabajador">
            <select class="tlm-control" name="categoria">
                <option value="todos">Todas las categorias</option>
                <?php foreach ($categoriaLabels as $key => $label): ?>
                    <option value="<?= tlm_safe($key) ?>" <?= ($filtros['categoria'] ?? '') === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tlm-control" name="estado">
                <option value="todos">Todos los estados</option>
                <?php foreach ($estadoLabels as $key => $label): ?>
                    <option value="<?= tlm_safe($key) ?>" <?= ($filtros['estado'] ?? '') === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="tlm-control" name="prioridad">
                <option value="todos">Todas las prioridades</option>
                <?php foreach ($prioridadLabels as $key => $label): ?>
                    <option value="<?= tlm_safe($key) ?>" <?= ($filtros['prioridad'] ?? '') === $key ? 'selected' : '' ?>><?= tlm_safe($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="tlm-btn">Filtrar</button>
        </form>

        <?php if (!$tablaDisponible): ?>
            <div class="tlm-empty">
                <strong>Base de tareas no disponible</strong>
                La migracion TLM-A debe estar aplicada antes de usar esta vista.
            </div>
        <?php elseif (empty($tareas)): ?>
            <div class="tlm-empty">
                <strong>No hay tareas operativas registradas</strong>
                <?php if ($puedeCrear): ?>
                    Crea la primera tarea manual para el hotel actual cuando sea necesario.
                <?php else: ?>
                    No hay tareas registradas para el hotel actual.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="tlm-table-wrap">
                <table class="tlm-table">
                    <thead>
                        <tr>
                            <th>Tarea</th>
                            <th>Categoria</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Contexto</th>
                            <th>Fechas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas as $tarea): ?>
                            <?php
                            $estado = (string)($tarea['estado'] ?? 'pendiente');
                            $categoria = (string)($tarea['categoria'] ?? 'general');
                            $prioridad = (string)($tarea['prioridad'] ?? 'media');
                            ?>
                            <tr>
                                <td>
                                    <a class="tlm-main-link" href="<?= url('tareas/' . (int)$tarea['id']) ?>">
                                        <?= tlm_safe($tarea['titulo'] ?? '', 'Tarea #' . (int)$tarea['id']) ?>
                                    </a>
                                    <div class="tlm-muted">#<?= (int)$tarea['id'] ?> · origen <?= tlm_safe($tarea['origen'] ?? 'manual') ?></div>
                                </td>
                                <td><?= tlm_safe($categoriaLabels[$categoria] ?? $categoria) ?></td>
                                <td><span class="tlm-pill tlm-pill--<?= tlm_safe($estado) ?>"><?= tlm_safe($estadoLabels[$estado] ?? $estado) ?></span></td>
                                <td><?= tlm_safe($prioridadLabels[$prioridad] ?? $prioridad) ?></td>
                                <td>
                                    <?php if (!empty($tarea['habitacion_id'])): ?>
                                        <a href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a><br>
                                    <?php endif; ?>
                                    <?php if (!empty($tarea['trabajador_id'])): ?>
                                        <a href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?></a>
                                    <?php elseif (empty($tarea['habitacion_id'])): ?>
                                        <span class="tlm-muted">Sin contexto vinculado</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>Prog: <?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></div>
                                    <div class="tlm-muted">Limite: <?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
