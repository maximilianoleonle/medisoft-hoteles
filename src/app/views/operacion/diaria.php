<?php
if (!function_exists('op_daily_safe')) {
    function op_daily_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('op_daily_num')) {
    function op_daily_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('op_daily_money')) {
    function op_daily_money($value): string
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('op_daily_date')) {
    function op_daily_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp) : '-';
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
$reservaciones = is_array($reporte['reservaciones'] ?? null) ? $reporte['reservaciones'] : [];
$tareas = is_array($reporte['tareas'] ?? null) ? $reporte['tareas'] : [];
$mantenimiento = is_array($reporte['mantenimiento'] ?? null) ? $reporte['mantenimiento'] : [];
$trabajadores = is_array($reporte['trabajadores'] ?? null) ? $reporte['trabajadores'] : [];
$documentos = is_array($reporte['documentos'] ?? null) ? $reporte['documentos'] : [];
$cuentasPorCobrar = is_array($reporte['cuentas_por_cobrar'] ?? null) ? $reporte['cuentas_por_cobrar'] : [];
$tablasDisponibles = is_array($tablasDisponibles ?? null) ? $tablasDisponibles : [];

$estadoReservacionLabels = [
    'confirmada' => 'Confirmada',
    'checked_in' => 'Check-in',
    'checked_out' => 'Check-out',
    'cancelada' => 'Cancelada',
];
?>

<style>
.op-daily{padding:24px;max-width:1320px;margin:0 auto;color:#172033}
.op-daily-hero{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:20px}
.op-daily-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.op-daily-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.op-daily-subtitle{color:#64748b;max-width:760px;margin:0}
.op-daily-actions{display:flex;gap:10px;flex-wrap:wrap}
.op-daily-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:7px;background:#fff;color:#172033;border:1px solid #d1d5db;font-weight:800;padding:10px 14px;text-decoration:none;min-height:40px}
.op-daily-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.op-daily-metric{background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:14px;box-shadow:0 6px 18px rgba(15,23,42,.05)}
.op-daily-metric span{display:block;font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase}
.op-daily-metric strong{display:block;font-size:25px;line-height:1.1;margin-top:8px;color:#111827}
.op-daily-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.op-daily-panel-head{padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.op-daily-panel-title{font-size:16px;font-weight:900;color:#111827;margin:0}
.op-daily-panel-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
.op-daily-stack{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.op-daily-list{padding:14px 18px}
.op-daily-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px}
.op-daily-row:last-child{border-bottom:0}
.op-daily-link{font-weight:900;color:#102a43;text-decoration:none}
.op-daily-muted{color:#64748b;font-size:13px;margin-top:4px}
.op-daily-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.op-daily-table-wrap{overflow-x:auto}
.op-daily-table{width:100%;border-collapse:collapse;min-width:820px}
.op-daily-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.op-daily-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.op-daily-empty{padding:34px 20px;text-align:center;color:#64748b}
.op-daily-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
.op-daily-alert{padding:14px 18px;background:#f8fafc;color:#475569;border-top:1px solid #e5e7eb;font-size:13px;font-weight:700}
@media (max-width:1100px){.op-daily-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.op-daily-stack{grid-template-columns:1fr}}
@media (max-width:720px){.op-daily-hero{display:block}.op-daily-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.op-daily-actions{margin-top:14px}.op-daily-title{font-size:24px}}
</style>

<div class="op-daily">
    <div class="op-daily-hero">
        <div>
            <div class="op-daily-kicker">Operacion / Diario</div>
            <h1 class="op-daily-title">Tablero operativo diario</h1>
            <p class="op-daily-subtitle">Lectura consolidada del hotel actual. No cambia reservaciones, habitaciones, tareas, documentos, Caja, nomina ni sincronizacion offline.</p>
        </div>
        <div class="op-daily-actions">
            <a class="op-daily-btn" href="<?= back_url('dashboard') ?>"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a class="op-daily-btn" href="<?= url('operacion/conciliacion-financiera') ?>"><i class="fas fa-shield-alt"></i> Conciliacion financiera</a>
            <a class="op-daily-btn" href="<?= url('tareas/reporte') ?>"><i class="fas fa-tasks"></i> Reporte TLM</a>
        </div>
    </div>

    <div class="op-daily-grid" aria-label="Resumen operativo diario">
        <div class="op-daily-metric"><span>Habitaciones</span><strong><?= op_daily_num($habitaciones['total'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Disponibles</span><strong><?= op_daily_num($habitaciones['disponible'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Ocupadas</span><strong><?= op_daily_num($habitaciones['ocupada'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Llegadas hoy</span><strong><?= op_daily_num($reservaciones['llegadas_hoy'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Tareas activas</span><strong><?= op_daily_num($tareas['activas'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Mantenimiento</span><strong><?= op_daily_num(($mantenimiento['en_proceso'] ?? 0) + ($mantenimiento['programado'] ?? 0)) ?></strong></div>
        <div class="op-daily-metric"><span>CxC estimada</span><strong><?= op_daily_money($cuentasPorCobrar['saldo_estimado'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>CxC pendientes</span><strong><?= op_daily_num($cuentasPorCobrar['pendientes'] ?? 0) ?></strong></div>
    </div>

    <section class="op-daily-panel" style="margin-bottom:16px">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">KPIs financieros estimados</h2>
            <p class="op-daily-panel-subtitle">Lectura derivada de reservaciones, pagos y abonos. No crea cobros, no registra pagos y no toca Caja.</p>
        </div>
        <div class="op-daily-list">
            <div class="op-daily-row"><span>CxC estimada pendiente</span><strong><?= op_daily_money($cuentasPorCobrar['saldo_estimado'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones pendientes</span><strong><?= op_daily_num($cuentasPorCobrar['pendientes'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones liquidadas</span><strong><?= op_daily_num($cuentasPorCobrar['liquidadas'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones con excedente</span><strong><?= op_daily_num($cuentasPorCobrar['excedentes'] ?? 0) ?></strong></div>
        </div>
        <div class="op-daily-alert">
            CxC es estimada y read-only. Para revisar el detalle usa
            <a class="op-daily-link" href="<?= url('cuentas-por-cobrar') ?>">Cuentas por cobrar</a>.
        </div>
    </section>

    <div class="op-daily-stack">
        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Reservaciones y ocupacion</h2>
                <p class="op-daily-panel-subtitle">Solo lectura de agenda diaria y alertas de check-in/check-out.</p>
            </div>
            <div class="op-daily-list">
                <div class="op-daily-row"><span>Salidas hoy</span><strong><?= op_daily_num($reservaciones['salidas_hoy'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Estancias activas</span><strong><?= op_daily_num($reservaciones['activas'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Check-ins vencidos</span><strong><?= op_daily_num($reservaciones['checkins_vencidos'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Check-outs vencidos</span><strong><?= op_daily_num($reservaciones['checkouts_vencidos'] ?? 0) ?></strong></div>
            </div>
        </section>

        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Riesgo operativo</h2>
                <p class="op-daily-panel-subtitle">Tareas, mantenimiento y personal sin acciones desde esta pantalla.</p>
            </div>
            <div class="op-daily-list">
                <div class="op-daily-row"><span>Tareas urgentes activas</span><strong><?= op_daily_num($tareas['urgentes'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Tareas vencidas</span><strong><?= op_daily_num($tareas['vencidas'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Mantenimientos urgentes</span><strong><?= op_daily_num($mantenimiento['urgente'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Trabajadores activos</span><strong><?= op_daily_num($trabajadores['activos'] ?? 0) ?></strong></div>
            </div>
        </section>
    </div>

    <section class="op-daily-panel" style="margin-bottom:16px">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">Agenda de hoy</h2>
            <p class="op-daily-panel-subtitle">Entradas y salidas del dia para el hotel actual.</p>
        </div>
        <?php if (empty($reservaciones['hoy'])): ?>
            <div class="op-daily-empty"><strong>Sin agenda para hoy</strong>No hay entradas o salidas registradas para la fecha actual.</div>
        <?php else: ?>
            <div class="op-daily-table-wrap">
                <table class="op-daily-table">
                    <thead>
                        <tr>
                            <th>Reservacion</th>
                            <th>Huesped</th>
                            <th>Habitaciones</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservaciones['hoy'] as $reservacion): ?>
                            <?php $estado = (string)($reservacion['estado'] ?? ''); ?>
                            <tr>
                                <td><a class="op-daily-link" href="<?= url('reservaciones/ver/' . (int)($reservacion['id'] ?? 0)) ?>">#<?= (int)($reservacion['id'] ?? 0) ?></a></td>
                                <td><?= op_daily_safe($reservacion['huesped_nombre'] ?? null) ?></td>
                                <td><?= op_daily_safe($reservacion['habitaciones'] ?? null) ?></td>
                                <td><?= op_daily_date($reservacion['fecha_entrada'] ?? null) ?><div class="op-daily-muted"><?= op_daily_safe($reservacion['hora_llegada_estimada'] ?? null, '') ?></div></td>
                                <td><?= op_daily_date($reservacion['fecha_salida'] ?? null) ?><div class="op-daily-muted"><?= op_daily_safe($reservacion['hora_salida'] ?? null, '') ?></div></td>
                                <td><span class="op-daily-pill"><?= op_daily_safe($estadoReservacionLabels[$estado] ?? $estado) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="op-daily-stack">
        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Tareas prioritarias</h2>
                <p class="op-daily-panel-subtitle">Enlaces al detalle de tarea; sin botones de estado.</p>
            </div>
            <?php if (empty($tareas['recientes'])): ?>
                <div class="op-daily-empty"><strong>Sin tareas operativas</strong>No hay tareas para mostrar.</div>
            <?php else: ?>
                <div class="op-daily-list">
                    <?php foreach ($tareas['recientes'] as $tarea): ?>
                        <div class="op-daily-row">
                            <span>
                                <a class="op-daily-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>"><?= op_daily_safe($tarea['titulo'] ?? null) ?></a>
                                <div class="op-daily-muted">
                                    <?= op_daily_safe($tarea['categoria'] ?? null) ?> - <?= op_daily_safe($tarea['prioridad'] ?? null) ?>
                                    <?php if (!empty($tarea['habitacion_numero'])): ?> - Hab. <?= op_daily_safe($tarea['habitacion_numero']) ?><?php endif; ?>
                                </div>
                            </span>
                            <strong><?= op_daily_safe($tarea['estado'] ?? null) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Mantenimiento reciente</h2>
                <p class="op-daily-panel-subtitle">Lectura historica; no cambia estado de habitacion.</p>
            </div>
            <?php if (empty($mantenimiento['recientes'])): ?>
                <div class="op-daily-empty"><strong>Sin mantenimiento</strong>No hay registros recientes para mostrar.</div>
            <?php else: ?>
                <div class="op-daily-list">
                    <?php foreach ($mantenimiento['recientes'] as $item): ?>
                        <div class="op-daily-row">
                            <span>
                                Hab. <?= op_daily_safe($item['habitacion_numero'] ?? null) ?> - <?= op_daily_safe($item['motivo'] ?? null) ?>
                                <div class="op-daily-muted"><?= op_daily_safe($item['tipo_mantenimiento'] ?? null) ?> - <?= op_daily_safe($item['prioridad'] ?? null) ?></div>
                            </span>
                            <strong><?= op_daily_safe($item['estado'] ?? null) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="op-daily-panel">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">Documentos recientes</h2>
            <p class="op-daily-panel-subtitle">Metadatos seguros del Centro Documental; no muestra rutas internas.</p>
        </div>
        <?php if (empty($documentos)): ?>
            <div class="op-daily-empty"><strong>Sin documentos recientes</strong>Cuando existan documentos activos, apareceran aqui.</div>
        <?php else: ?>
            <div class="op-daily-list">
                <?php foreach ($documentos as $documento): ?>
                    <div class="op-daily-row">
                        <span>
                            <a class="op-daily-link" href="<?= url('documentos/' . (int)($documento['id'] ?? 0)) ?>"><?= op_daily_safe($documento['titulo'] ?? $documento['nombre_original'] ?? null) ?></a>
                            <div class="op-daily-muted"><?= op_daily_safe($documento['entidad_tipo'] ?? 'sin entidad') ?> #<?= op_daily_safe($documento['entidad_id'] ?? '') ?> - <?= op_daily_safe($documento['mime_type'] ?? null) ?></div>
                        </span>
                        <strong><?= op_daily_date($documento['created_at'] ?? null, true) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="op-daily-alert">Este tablero es solo lectura: no crea tareas, no cambia habitaciones, no toca Caja, no genera pagos y no usa `/api/sync`.</div>
    </section>
</div>
