<?php
$periodosNomina = is_array($periodosNomina ?? null) ? $periodosNomina : [];
$tablaDisponible = $tablaDisponible ?? false;
$tablaPersistenteDisponible = $tablaPersistenteDisponible ?? false;
$periodosPersistentes = is_array($periodosPersistentes ?? null) ? $periodosPersistentes : [];
$cierreTokens = is_array($cierreTokens ?? null) ? $cierreTokens : [];
$modo = (string)($modo ?? 'lista');
$periodos = is_array($periodosNomina['periodos'] ?? null) ? $periodosNomina['periodos'] : [];
$periodoDetalle = is_array($periodosNomina['periodo_detalle'] ?? null) ? $periodosNomina['periodo_detalle'] : null;
$preview = is_array($periodosNomina['preview'] ?? null) ? $periodosNomina['preview'] : [];
$trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$filtros = is_array($periodosNomina['filtros_normalizados'] ?? null) ? $periodosNomina['filtros_normalizados'] : [];
$bloqueos = is_array($periodosNomina['bloqueos'] ?? null) ? $periodosNomina['bloqueos'] : [];

if (!function_exists('trab_periodo_safe')) {
    function trab_periodo_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_periodo_money')) {
    function trab_periodo_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_periodo_num')) {
    function trab_periodo_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_periodo_date')) {
    function trab_periodo_date($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || $text === '0000-00-00') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y');
        } catch (Throwable $e) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!function_exists('trab_periodo_badge_class')) {
    function trab_periodo_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'listo_revision' || $estado === 'sin_saldo') {
            return 'period-badge period-badge-ok';
        }
        if ($estado === 'requiere_revision') {
            return 'period-badge period-badge-warn';
        }
        if ($estado === 'bloqueado') {
            return 'period-badge period-badge-danger';
        }
        return 'period-badge';
    }
}

if (!function_exists('trab_periodo_snapshot_badge_class')) {
    function trab_periodo_snapshot_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'period-badge period-badge-ok';
        }
        if ($estado === 'anulado') {
            return 'period-badge period-badge-danger';
        }
        return 'period-badge period-badge-warn';
    }
}

if (!function_exists('trab_periodo_snapshot_label')) {
    function trab_periodo_snapshot_label($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'Aprobado';
        }
        if ($estado === 'anulado') {
            return 'Anulado';
        }
        if ($estado === 'cerrado') {
            return 'Cerrado';
        }
        return 'Snapshot';
    }
}

$tipoPeriodo = (string)($filtros['tipo_periodo'] ?? 'semanal');
$fechaBase = (string)($filtros['fecha_base_raw'] ?? ($filtros['fecha_base'] ?? date('Y-m-d')));
$fechaInicio = (string)($filtros['fecha_inicio_raw'] ?? ($filtros['fecha_inicio'] ?? ''));
$fechaFin = (string)($filtros['fecha_fin_raw'] ?? ($filtros['fecha_fin'] ?? ''));
$estado = (string)($filtros['estado'] ?? 'activos');
$rolLaboral = (string)($filtros['rol_laboral'] ?? '');
$incluirPagosCaja = !empty($filtros['incluir_pagos_caja']);
$periodoActualInicio = (string)($periodoDetalle['fecha_inicio'] ?? $fechaInicio);
$periodoActualFin = (string)($periodoDetalle['fecha_fin'] ?? $fechaFin);

$baseQuery = [
    'tipo_periodo' => $tipoPeriodo,
    'fecha_base' => $fechaBase,
    'estado' => $estado,
    'rol_laboral' => $rolLaboral,
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
];
$previewQuery = http_build_query(array_merge($baseQuery, [
    'fecha_inicio' => $periodoActualInicio,
    'fecha_fin' => $periodoActualFin,
]));
$previewNominaQuery = http_build_query([
    'fecha_inicio' => $periodoActualInicio,
    'fecha_fin' => $periodoActualFin,
    'estado' => $estado,
    'rol_laboral' => $rolLaboral,
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
?>

<style>
.nomina-periodos {
    --period-brand: var(--brand-primary, #1f3f46);
    --period-accent: var(--brand-accent, #b58a3c);
    --period-line: color-mix(in srgb, var(--period-brand) 10%, #e5e7eb);
    --period-soft: color-mix(in srgb, var(--period-accent) 7%, #f8fafc);
    color: #243142;
}
.nomina-periodos .period-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--period-brand) 92%, #111827), color-mix(in srgb, var(--period-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.nomina-periodos .period-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.nomina-periodos .period-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.nomina-periodos .period-subtitle {
    margin-top: 8px;
    max-width: 60rem;
    color: rgba(255,255,255,.86);
}
.nomina-periodos .period-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.nomina-periodos .period-panel,
.nomina-periodos .period-card {
    border: 1px solid var(--period-line);
    background: rgba(255,255,255,.94);
}
.nomina-periodos .period-card-active {
    outline: 2px solid color-mix(in srgb, var(--period-accent) 44%, transparent);
}
.nomina-periodos .period-stat {
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 14px;
}
.nomina-periodos .period-stat-soft {
    background: var(--period-soft);
}
.nomina-periodos .period-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--period-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.nomina-periodos .period-btn-primary {
    background: var(--period-brand);
    border-color: var(--period-brand);
    color: #fff;
}
.nomina-periodos .period-btn-compact {
    min-height: 32px;
    padding: 0 10px;
    font-size: .82rem;
}
.nomina-periodos .period-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-periodos .period-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    border: 1px solid var(--period-line);
    background: #fff;
    padding: 0 12px;
    font-weight: 800;
    color: #334155;
}
.nomina-periodos .period-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.nomina-periodos .period-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--period-line);
    background: var(--period-soft);
    font-size: .78rem;
    font-weight: 800;
}
.nomina-periodos .period-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.nomina-periodos .period-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.nomina-periodos .period-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.nomina-periodos .period-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.nomina-periodos .period-table td,
.nomina-periodos .period-table th {
    border-bottom: 1px solid var(--period-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="nomina-periodos">
    <section class="period-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="period-kicker">Personal / Pre-nomina</div>
                <h1 class="period-title">Periodos de pre-nomina</h1>
                <p class="period-subtitle">
                    Revision interna de periodos laborales con cierre persistente controlado. No genera nomina oficial, no registra pago y no modifica Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="period-stat-hero">
                    <div class="text-xs opacity-75">Periodos</div>
                    <div class="text-2xl font-black"><?= trab_periodo_num(count($periodos)) ?></div>
                </div>
                <div class="period-stat-hero">
                    <div class="text-xs opacity-75">Trabajadores</div>
                    <div class="text-2xl font-black"><?= trab_periodo_num($resumen['trabajadores_total'] ?? 0) ?></div>
                </div>
                <div class="period-stat-hero">
                    <div class="text-xs opacity-75">Neto sugerido</div>
                    <div class="text-xl font-black"><?= trab_periodo_money($resumen['neto_sugerido_total'] ?? 0) ?></div>
                </div>
                <div class="period-stat-hero">
                    <div class="text-xs opacity-75">Pendiente</div>
                    <div class="text-xl font-black"><?= trab_periodo_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="period-btn" href="<?= url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Personal
                </a>
                <a class="period-btn" href="<?= url('trabajadores/nomina/preview') ?>">
                    <i class="fas fa-clipboard-list"></i>
                    Preview nomina
                </a>
                <a class="period-btn" href="<?= url('trabajadores/nomina/periodos/reporte') ?>">
                    <i class="fas fa-file-lines"></i>
                    Reporte snapshots
                </a>
                <a class="period-btn" href="<?= url('trabajadores/reporte') ?>">
                    <i class="fas fa-chart-pie"></i>
                    Reporte
                </a>
            </div>
            <span class="period-badge">
                <i class="fas fa-lock"></i>
                Preview GET / Cierre controlado
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="period-panel p-5">
                <strong>Periodos no disponibles.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas laborales o de Caja para calcular periodos con seguridad.</p>
            </div>
        <?php else: ?>
            <?php if (!$tablaPersistenteDisponible): ?>
                <div class="period-panel p-5 bg-slate-50">
                    <strong>Cierre persistente no disponible.</strong>
                    <p class="text-sm text-slate-500 mt-1">Faltan las tablas de snapshots de pre-nomina. El preview queda disponible sin cierre.</p>
                </div>
            <?php elseif (!empty($periodosPersistentes)): ?>
                <div class="period-panel overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black text-lg">Cierres persistentes recientes</h2>
                            <p class="text-sm text-slate-500 mt-1">Snapshots administrativos; no son pago, CFDI, timbrado ni movimiento de Caja.</p>
                        </div>
                        <span class="period-badge">
                            <i class="fas fa-box-archive"></i>
                            <?= trab_periodo_num(count($periodosPersistentes)) ?> visible(s)
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="period-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Periodo</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Trabajadores</th>
                                    <th class="text-right">Pendiente</th>
                                    <th class="text-left">Cierre</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($periodosPersistentes as $snapshot): ?>
                                    <tr>
                                        <td>
                                            <div class="font-black"><?= trab_periodo_safe($snapshot['etiqueta'] ?? 'Periodo cerrado') ?></div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_periodo_date($snapshot['fecha_inicio'] ?? '') ?> - <?= trab_periodo_date($snapshot['fecha_fin'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?= trab_periodo_snapshot_badge_class($snapshot['estado'] ?? '') ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= trab_periodo_snapshot_label($snapshot['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-black"><?= trab_periodo_num($snapshot['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= trab_periodo_money($snapshot['pendiente_pago_total'] ?? 0) ?></td>
                                        <td>
                                            <div class="text-sm font-bold"><?= trab_periodo_safe($snapshot['cerrado_por_nombre'] ?? 'Usuario') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_periodo_safe($snapshot['cerrado_at'] ?? '-') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <a class="period-badge" href="<?= url('trabajadores/nomina/periodos/' . (int)($snapshot['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver snapshot
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <div class="period-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/periodos') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[150px_150px_150px_150px_160px_minmax(170px,1fr)_auto_auto] gap-3">
                    <select class="period-input" name="tipo_periodo">
                        <option value="semanal" <?= $tipoPeriodo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="quincenal" <?= $tipoPeriodo === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                        <option value="mensual" <?= $tipoPeriodo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="manual" <?= $tipoPeriodo === 'manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                    <input class="period-input" type="date" name="fecha_base" value="<?= trab_periodo_safe($fechaBase, date('Y-m-d')) ?>">
                    <input class="period-input" type="date" name="fecha_inicio" value="<?= trab_periodo_safe($fechaInicio, '') ?>">
                    <input class="period-input" type="date" name="fecha_fin" value="<?= trab_periodo_safe($fechaFin, '') ?>">
                    <select class="period-input" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Baja</option>
                    </select>
                    <input class="period-input" type="search" name="rol_laboral" value="<?= trab_periodo_safe($rolLaboral, '') ?>" placeholder="Rol laboral">
                    <label class="period-check">
                        <input type="hidden" name="incluir_pagos_caja" value="0">
                        <input type="checkbox" name="incluir_pagos_caja" value="1" <?= $incluirPagosCaja ? 'checked' : '' ?>>
                        Caja
                    </label>
                    <button class="period-btn period-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Evaluar
                    </button>
                </form>
            </div>

            <?php if (!empty($bloqueos)): ?>
                <div class="period-panel p-5 bg-slate-50">
                    <strong>Revision bloqueada.</strong>
                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($bloqueos as $bloqueo): ?>
                            <div><i class="fas fa-circle-info mr-2"></i><?= trab_periodo_safe($bloqueo) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="xl:col-span-1 space-y-3">
                    <?php if (empty($periodos)): ?>
                        <div class="period-card p-6 text-center">
                            <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-calendar-xmark"></i></div>
                            <h2 class="font-black text-lg">Sin periodos para revisar</h2>
                            <p class="text-sm text-slate-500 mt-1">Ajusta las fechas del periodo.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($periodos as $periodo): ?>
                            <?php
                                $isActive = $periodoDetalle
                                    && (string)($periodoDetalle['fecha_inicio'] ?? '') === (string)($periodo['fecha_inicio'] ?? '')
                                    && (string)($periodoDetalle['fecha_fin'] ?? '') === (string)($periodo['fecha_fin'] ?? '');
                                $detalleQuery = http_build_query(array_merge($baseQuery, [
                                    'fecha_inicio' => $periodo['fecha_inicio'] ?? '',
                                    'fecha_fin' => $periodo['fecha_fin'] ?? '',
                                ]));
                                $detalleUrl = url('trabajadores/nomina/periodos/preview' . ($detalleQuery !== '' ? '?' . $detalleQuery : ''));
                                $nominaPreviewUrl = url('trabajadores/nomina/preview' . ($detalleQuery !== '' ? '?' . $detalleQuery : ''));
                                $periodoResumen = is_array($periodo['resumen'] ?? null) ? $periodo['resumen'] : [];
                                $snapshotId = (int)($periodo['snapshot_id'] ?? 0);
                                $snapshotEstado = (string)($periodo['snapshot_estado'] ?? '');
                                $periodoTokenKey = (string)($periodo['fecha_inicio'] ?? '') . ':' . (string)($periodo['fecha_fin'] ?? '');
                                $cierreToken = (string)($cierreTokens[$periodoTokenKey] ?? '');
                            ?>
                            <article class="period-card <?= $isActive ? 'period-card-active' : '' ?> p-4 space-y-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h2 class="font-black"><?= trab_periodo_safe($periodo['etiqueta'] ?? 'Periodo') ?></h2>
                                        <p class="text-sm text-slate-500">
                                            <?= trab_periodo_date($periodo['fecha_inicio'] ?? '') ?> - <?= trab_periodo_date($periodo['fecha_fin'] ?? '') ?>
                                        </p>
                                    </div>
                                    <span class="<?= trab_periodo_badge_class($periodo['estado_periodo'] ?? '') ?>">
                                        <i class="fas fa-circle"></i>
                                        <?= trab_periodo_safe($periodo['estado_label'] ?? 'Revision') ?>
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <div class="period-label">Trabajadores</div>
                                        <div class="font-black"><?= trab_periodo_num($periodoResumen['trabajadores_total'] ?? 0) ?></div>
                                    </div>
                                    <div>
                                        <div class="period-label">Pendiente</div>
                                        <div class="font-black"><?= trab_periodo_money($periodoResumen['pendiente_pago_total'] ?? 0) ?></div>
                                    </div>
                                </div>
                                <?php if (trim((string)($periodo['motivo_bloqueo'] ?? '')) !== ''): ?>
                                    <p class="text-xs text-red-700 font-bold"><?= trab_periodo_safe($periodo['motivo_bloqueo'] ?? '') ?></p>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-2">
                                    <a class="period-badge" href="<?= $detalleUrl ?>">
                                        <i class="fas fa-eye"></i>
                                        Detalle
                                    </a>
                                    <a class="period-badge" href="<?= $nominaPreviewUrl ?>">
                                        <i class="fas fa-table-list"></i>
                                        Preview
                                    </a>
                                    <?php if ($snapshotId > 0): ?>
                                        <a class="<?= trab_periodo_snapshot_badge_class($snapshotEstado) ?>" href="<?= url('trabajadores/nomina/periodos/' . $snapshotId) ?>">
                                            <i class="fas fa-box-archive"></i>
                                            <?= trab_periodo_snapshot_label($snapshotEstado) ?> #<?= (int)$snapshotId ?>
                                        </a>
                                    <?php elseif ($tablaPersistenteDisponible && !empty($periodo['puede_cerrar_persistente']) && $cierreToken !== ''): ?>
                                        <form method="POST" action="<?= url('trabajadores/nomina/periodos/cerrar') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="periodo_token" value="<?= trab_periodo_safe($cierreToken, '') ?>">
                                            <input type="hidden" name="tipo_periodo" value="<?= trab_periodo_safe($periodo['tipo_periodo'] ?? $tipoPeriodo, 'manual') ?>">
                                            <input type="hidden" name="etiqueta" value="<?= trab_periodo_safe($periodo['etiqueta'] ?? 'Periodo', '') ?>">
                                            <input type="hidden" name="fecha_inicio" value="<?= trab_periodo_safe($periodo['fecha_inicio'] ?? '', '') ?>">
                                            <input type="hidden" name="fecha_fin" value="<?= trab_periodo_safe($periodo['fecha_fin'] ?? '', '') ?>">
                                            <input type="hidden" name="estado" value="activos">
                                            <input type="hidden" name="rol_laboral" value="">
                                            <input type="hidden" name="incluir_pagos_caja" value="<?= $incluirPagosCaja ? '1' : '0' ?>">
                                            <button class="period-btn period-btn-primary period-btn-compact" type="submit">
                                                <i class="fas fa-lock"></i>
                                                Cerrar snapshot
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="period-badge">
                                            <i class="fas fa-lock"></i>
                                            Sin cierre disponible
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="xl:col-span-2 space-y-4">
                    <div class="period-panel p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-black text-lg">Detalle del periodo evaluado</h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    <?= trab_periodo_date($periodoActualInicio) ?> - <?= trab_periodo_date($periodoActualFin) ?>
                                </p>
                            </div>
                            <?php if ($periodoDetalle): ?>
                                <div class="flex flex-wrap gap-2">
                                    <span class="<?= trab_periodo_badge_class($periodoDetalle['estado_periodo'] ?? '') ?>">
                                        <i class="fas fa-shield-halved"></i>
                                        <?= trab_periodo_safe($periodoDetalle['estado_label'] ?? 'Revision') ?>
                                    </span>
                                    <?php if ((int)($periodoDetalle['snapshot_id'] ?? 0) > 0): ?>
                                        <a class="<?= trab_periodo_snapshot_badge_class($periodoDetalle['snapshot_estado'] ?? '') ?>" href="<?= url('trabajadores/nomina/periodos/' . (int)$periodoDetalle['snapshot_id']) ?>">
                                            <i class="fas fa-box-archive"></i>
                                            Snapshot #<?= (int)$periodoDetalle['snapshot_id'] ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                        <div class="period-stat period-stat-soft">
                            <div class="period-label">Bruto</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['bruto_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat">
                            <div class="period-label">Deducciones</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['deducciones_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat">
                            <div class="period-label">Pagos Caja</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                        </div>
                        <div class="period-stat period-stat-soft">
                            <div class="period-label">Pendiente</div>
                            <div class="text-2xl font-black mt-1"><?= trab_periodo_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="period-panel overflow-hidden">
                        <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="font-black text-lg">Trabajadores del periodo</h2>
                                <p class="text-sm text-slate-500 mt-1">El cierre guarda snapshot administrativo; no crea recibos oficiales, pagos ni movimientos de Caja.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a class="period-badge" href="<?= url('trabajadores/nomina/preview' . ($previewNominaQuery !== '' ? '?' . $previewNominaQuery : '')) ?>">
                                    <i class="fas fa-table-list"></i>
                                    Preview completo
                                </a>
                                <span class="period-badge">
                                    <i class="fas fa-users"></i>
                                    <?= trab_periodo_num(count($trabajadores)) ?> visible(s)
                                </span>
                            </div>
                        </div>

                        <?php if (empty($trabajadores)): ?>
                            <div class="p-8 text-center">
                                <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-user-clock"></i></div>
                                <h3 class="font-black text-lg">Sin trabajadores en el detalle</h3>
                                <p class="text-sm text-slate-500 mt-1">Revisa el periodo, estado o rol laboral.</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="period-table min-w-full text-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-left">Trabajador</th>
                                            <th class="text-left">Estado</th>
                                            <th class="text-right">Bruto</th>
                                            <th class="text-right">Deducciones</th>
                                            <th class="text-right">Caja</th>
                                            <th class="text-right">Neto</th>
                                            <th class="text-left">Lectura</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($trabajadores, 0, 80) as $trabajador): ?>
                                            <?php
                                                $estadoPreview = (string)($trabajador['estado_preview_nomina'] ?? '');
                                                $reciboQuery = http_build_query([
                                                    'fecha_inicio' => $periodoActualInicio,
                                                    'fecha_fin' => $periodoActualFin,
                                                    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
                                                ]);
                                                $reciboUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0) . '/recibo-laboral' . ($reciboQuery !== '' ? '?' . $reciboQuery : ''));
                                            ?>
                                            <tr>
                                                <td>
                                                    <a class="font-black underline" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>">
                                                        <?= trab_periodo_safe($trabajador['nombre_completo'] ?? null) ?>
                                                    </a>
                                                    <div class="text-xs text-slate-500"><?= trab_periodo_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                                </td>
                                                <td>
                                                    <span class="period-badge">
                                                        <i class="fas fa-circle"></i>
                                                        <?= trab_periodo_safe(str_replace('_', ' ', $estadoPreview)) ?>
                                                    </span>
                                                    <div class="text-xs text-slate-500 mt-2"><?= trab_periodo_safe($trabajador['estado'] ?? null) ?></div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['bruto_periodo'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        +<?= trab_periodo_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_periodo_money($trabajador['conceptos_en_contra'] ?? 0) ?>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['deducciones_informativas'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        Ant. <?= trab_periodo_money($trabajador['anticipos_saldo'] ?? 0) ?> / Prest. <?= trab_periodo_money($trabajador['prestamos_saldo'] ?? 0) ?>
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['pagos_caja_aplicados'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">
                                                        <?= trab_periodo_num($trabajador['pagos_caja_pagados'] ?? 0) ?> vig., <?= trab_periodo_num($trabajador['pagos_caja_revertidos'] ?? 0) ?> rev.
                                                    </div>
                                                </td>
                                                <td class="text-right">
                                                    <div class="font-black"><?= trab_periodo_money($trabajador['neto_sugerido'] ?? 0) ?></div>
                                                    <div class="text-xs text-slate-500">Pendiente <?= trab_periodo_money($trabajador['pendiente_pago_sugerido'] ?? 0) ?></div>
                                                </td>
                                                <td>
                                                    <?php if (trim((string)($trabajador['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                                        <div class="text-xs text-red-700 font-bold"><?= trab_periodo_safe($trabajador['motivo_bloqueo_nomina'] ?? '') ?></div>
                                                    <?php else: ?>
                                                        <div class="text-xs text-slate-500">Conceptos <?= trab_periodo_num($trabajador['conceptos_count'] ?? 0) ?>.</div>
                                                    <?php endif; ?>
                                                    <a class="period-badge mt-2" href="<?= $reciboUrl ?>">
                                                        <i class="fas fa-receipt"></i>
                                                        Recibo
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
