<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$porPeriodo = is_array($reporte['por_periodo'] ?? null) ? $reporte['por_periodo'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

if (!function_exists('trab_nom_audit_safe')) {
    function trab_nom_audit_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nom_audit_money')) {
    function trab_nom_audit_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nom_audit_num')) {
    function trab_nom_audit_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nom_audit_date')) {
    function trab_nom_audit_date($value)
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

if (!function_exists('trab_nom_audit_datetime')) {
    function trab_nom_audit_datetime($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '' || $text === '0000-00-00 00:00:00' || $text === '0000-00-00') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

if (!function_exists('trab_nom_audit_badge_class')) {
    function trab_nom_audit_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'liquidado') {
            return 'audit-badge audit-badge-ok';
        }
        if ($estado === 'parcial') {
            return 'audit-badge audit-badge-warn';
        }
        if ($estado === 'revisar') {
            return 'audit-badge audit-badge-danger';
        }
        return 'audit-badge';
    }
}

if (!function_exists('trab_nom_audit_label')) {
    function trab_nom_audit_label($estado)
    {
        $labels = [
            'liquidado' => 'Liquidado',
            'parcial' => 'Parcial',
            'sin_pago' => 'Sin pago',
            'revisar' => 'Revisar',
            'cerrado' => 'Cerrado',
            'aprobado' => 'Aprobado',
            'anulado' => 'Anulado',
        ];

        $estado = (string)$estado;
        return $labels[$estado] ?? ($estado !== '' ? ucfirst(str_replace('_', ' ', $estado)) : '-');
    }
}

$periodoId = (int)($filtros['periodo_id'] ?? 0);
$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estadoSnapshot = (string)($filtros['estado_snapshot'] ?? 'todos');
$estadoAuditoria = (string)($filtros['estado_auditoria'] ?? 'todos');
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$exportParams = [
    'periodo_id' => $periodoId > 0 ? $periodoId : null,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : null,
    'buscar' => $buscar !== '' ? $buscar : null,
    'estado_snapshot' => $estadoSnapshot !== '' ? $estadoSnapshot : 'todos',
    'estado_auditoria' => $estadoAuditoria !== '' ? $estadoAuditoria : 'todos',
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/nomina/auditoria/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.nomina-audit {
    --audit-brand: var(--brand-primary, #1f3f46);
    --audit-accent: var(--brand-accent, #b58a3c);
    --audit-line: color-mix(in srgb, var(--audit-brand) 10%, #e5e7eb);
    --audit-soft: color-mix(in srgb, var(--audit-accent) 7%, #f8fafc);
    color: #243142;
}
.nomina-audit .audit-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--audit-brand) 92%, #111827), color-mix(in srgb, var(--audit-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.nomina-audit .audit-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.nomina-audit .audit-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.nomina-audit .audit-subtitle {
    margin-top: 8px;
    max-width: 66rem;
    color: rgba(255,255,255,.86);
}
.nomina-audit .audit-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.nomina-audit .audit-panel,
.nomina-audit .audit-card {
    border: 1px solid var(--audit-line);
    background: rgba(255,255,255,.94);
}
.nomina-audit .audit-stat {
    border: 1px solid var(--audit-line);
    background: #fff;
    padding: 14px;
}
.nomina-audit .audit-stat-soft {
    background: var(--audit-soft);
}
.nomina-audit .audit-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.nomina-audit .audit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--audit-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.nomina-audit .audit-btn-primary {
    background: var(--audit-brand);
    border-color: var(--audit-brand);
    color: #fff;
}
.nomina-audit .audit-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--audit-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-audit .audit-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--audit-line);
    background: var(--audit-soft);
    font-size: .78rem;
    font-weight: 900;
}
.nomina-audit .audit-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.nomina-audit .audit-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.nomina-audit .audit-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.nomina-audit .audit-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.nomina-audit .audit-table td,
.nomina-audit .audit-table th {
    border-bottom: 1px solid var(--audit-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="nomina-audit">
    <section class="audit-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="audit-kicker">Personal / Auditoria nomina</div>
                <h1 class="audit-title">Auditoria consolidada de nomina</h1>
                <p class="audit-subtitle">
                    Lectura administrativa de snapshots, pagos Caja vinculados, reversiones y saldo pendiente por detalle. No registra pagos, no modifica Caja y no genera nomina oficial.
                </p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 min-w-full xl:min-w-[760px]">
                <div class="audit-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Detalles</div>
                    <div class="text-2xl font-black"><?= trab_nom_audit_num($resumen['total_registros'] ?? 0) ?></div>
                </div>
                <div class="audit-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Trabajadores</div>
                    <div class="text-2xl font-black"><?= trab_nom_audit_num($resumen['trabajadores_total'] ?? 0) ?></div>
                </div>
                <div class="audit-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Pagos Caja</div>
                    <div class="text-2xl font-black"><?= trab_nom_audit_money($resumen['pagos_caja_total'] ?? 0) ?></div>
                </div>
                <div class="audit-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Saldo auditoria</div>
                    <div class="text-2xl font-black"><?= trab_nom_audit_money($resumen['saldo_auditoria_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <div class="p-5 space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="audit-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Personal</a>
                <a class="audit-btn" href="<?= url('trabajadores/nomina/periodos') ?>"><i class="fas fa-calendar-check"></i> Periodos</a>
                <a class="audit-btn" href="<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>"><i class="fas fa-scale-balanced"></i> Pagos snapshot</a>
                <a class="audit-btn" href="<?= url('trabajadores/nomina/expediente') ?>"><i class="fas fa-folder-open"></i> Expediente</a>
                <a class="audit-btn audit-btn-primary" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a>
            </div>
            <span class="audit-badge"><i class="fas fa-lock"></i> GET / read-only</span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="audit-panel p-4 text-sm font-semibold text-amber-900 bg-amber-50">
                La auditoria consolidada requiere las tablas de snapshots, pagos Caja y trazabilidad 5E-P-A. No se ejecuta ninguna escritura.
            </div>
        <?php endif; ?>

        <form class="audit-panel p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-8 gap-3" method="get" action="<?= url('trabajadores/nomina/auditoria') ?>">
            <label>
                <span class="audit-label">Periodo ID</span>
                <input class="audit-input" type="number" min="0" name="periodo_id" value="<?= $periodoId > 0 ? $periodoId : '' ?>" placeholder="Todos">
            </label>
            <label>
                <span class="audit-label">Trabajador ID</span>
                <input class="audit-input" type="number" min="0" name="trabajador_id" value="<?= $trabajadorId > 0 ? $trabajadorId : '' ?>" placeholder="Todos">
            </label>
            <label>
                <span class="audit-label">Desde</span>
                <input class="audit-input" type="date" name="fecha_inicio" value="<?= trab_nom_audit_safe($fechaInicio, '') ?>">
            </label>
            <label>
                <span class="audit-label">Hasta</span>
                <input class="audit-input" type="date" name="fecha_fin" value="<?= trab_nom_audit_safe($fechaFin, '') ?>">
            </label>
            <label>
                <span class="audit-label">Snapshot</span>
                <select class="audit-input" name="estado_snapshot">
                    <?php foreach (['todos' => 'Todos', 'cerrado' => 'Cerrado', 'aprobado' => 'Aprobado', 'anulado' => 'Anulado'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $estadoSnapshot === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="audit-label">Auditoria</span>
                <select class="audit-input" name="estado_auditoria">
                    <?php foreach (['todos' => 'Todos', 'liquidado' => 'Liquidado', 'parcial' => 'Parcial', 'sin_pago' => 'Sin pago', 'revisar' => 'Revisar'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $estadoAuditoria === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="xl:col-span-1">
                <span class="audit-label">Buscar</span>
                <input class="audit-input" type="search" name="buscar" value="<?= trab_nom_audit_safe($buscar, '') ?>" placeholder="Trabajador, ref, caja">
            </label>
            <div class="flex items-end">
                <button class="audit-btn audit-btn-primary w-full" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </form>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <div class="audit-stat audit-stat-soft">
                <div class="audit-label">Liquidado</div>
                <div class="text-2xl font-black text-emerald-700"><?= trab_nom_audit_num($resumen['liquidado_count'] ?? 0) ?></div>
            </div>
            <div class="audit-stat">
                <div class="audit-label">Parcial</div>
                <div class="text-2xl font-black text-amber-700"><?= trab_nom_audit_num($resumen['parcial_count'] ?? 0) ?></div>
            </div>
            <div class="audit-stat">
                <div class="audit-label">Sin pago</div>
                <div class="text-2xl font-black"><?= trab_nom_audit_num($resumen['sin_pago_count'] ?? 0) ?></div>
            </div>
            <div class="audit-stat">
                <div class="audit-label">Revisar</div>
                <div class="text-2xl font-black text-red-700"><?= trab_nom_audit_num($resumen['revisar_count'] ?? 0) ?></div>
            </div>
            <div class="audit-stat">
                <div class="audit-label">Pendiente snapshot</div>
                <div class="text-2xl font-black"><?= trab_nom_audit_money($resumen['pendiente_snapshot_total'] ?? 0) ?></div>
            </div>
        </section>

        <section class="audit-panel overflow-x-auto">
            <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-black m-0">Detalle consolidado</h2>
                    <p class="text-sm text-slate-500 m-0">Una fila por detalle de snapshot; los pagos Caja se muestran agregados.</p>
                </div>
                <span class="audit-badge"><i class="fas fa-list-check"></i> <?= trab_nom_audit_num(count($registros)) ?> visible(s)</span>
            </div>

            <?php if (empty($registros)): ?>
                <div class="py-16 text-center text-slate-500">
                    <i class="fas fa-clipboard-check text-4xl text-slate-300 mb-3"></i>
                    <h3 class="text-lg font-black text-slate-700">Sin detalles para auditar</h3>
                    <p>Ajusta filtros o crea/aprueba snapshots de pre-nomina antes de revisar pagos.</p>
                </div>
            <?php else: ?>
                <table class="audit-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Periodo</th>
                            <th class="text-left">Trabajador</th>
                            <th class="text-left">Estado</th>
                            <th class="text-right">Snapshot</th>
                            <th class="text-right">Caja</th>
                            <th class="text-right">Saldo</th>
                            <th class="text-left">Trazabilidad</th>
                            <th class="text-left">Referencias</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <?php
                            $estadoFila = (string)($registro['auditoria_estado'] ?? 'revisar');
                            $periodoUrl = url('trabajadores/nomina/periodos/' . (int)($registro['periodo_id'] ?? 0));
                            ?>
                            <tr>
                                <td>
                                    <a class="font-black text-slate-800 underline" href="<?= $periodoUrl ?>">
                                        #<?= (int)($registro['periodo_id'] ?? 0) ?> <?= trab_nom_audit_safe($registro['periodo_etiqueta'] ?? '') ?>
                                    </a>
                                    <div class="text-xs text-slate-500">
                                        <?= trab_nom_audit_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_nom_audit_date($registro['periodo_fecha_fin'] ?? '') ?>
                                    </div>
                                    <div class="mt-1">
                                        <span class="audit-badge"><?= trab_nom_audit_label($registro['periodo_estado'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-black"><?= trab_nom_audit_safe($registro['trabajador_snapshot_nombre'] ?? '') ?></div>
                                    <div class="text-xs text-slate-500">
                                        ID <?= (int)($registro['trabajador_id'] ?? 0) ?> - <?= trab_nom_audit_safe($registro['trabajador_snapshot_rol'] ?? '') ?>
                                    </div>
                                    <?php if (trim((string)($registro['trabajador_actual_nombre'] ?? '')) !== ''): ?>
                                        <div class="text-xs text-slate-500">Actual: <?= trab_nom_audit_safe($registro['trabajador_actual_nombre'] ?? '') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?= trab_nom_audit_badge_class($estadoFila) ?>">
                                        <i class="fas fa-circle"></i> <?= trab_nom_audit_label($estadoFila) ?>
                                    </span>
                                    <?php if ((int)($registro['inconsistencias_count'] ?? 0) > 0): ?>
                                        <div class="text-xs text-red-700 mt-2 font-bold">
                                            <?= trab_nom_audit_num($registro['inconsistencias_count'] ?? 0) ?> inconsistencia(s)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div class="font-black"><?= trab_nom_audit_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Bruto <?= trab_nom_audit_money($registro['bruto_periodo'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Neto <?= trab_nom_audit_money($registro['neto_sugerido'] ?? 0) ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="font-black text-emerald-700"><?= trab_nom_audit_money($registro['pagos_caja_total'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?= trab_nom_audit_num($registro['pagos_pagados_count'] ?? 0) ?> pago(s), <?= trab_nom_audit_num($registro['pagos_revertidos_count'] ?? 0) ?> rev.
                                    </div>
                                    <?php if ((float)($registro['reversiones_total'] ?? 0) > 0): ?>
                                        <div class="text-xs text-amber-700">Revertido <?= trab_nom_audit_money($registro['reversiones_total'] ?? 0) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <div class="font-black"><?= trab_nom_audit_money($registro['saldo_auditoria'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Detalle #<?= (int)($registro['detalle_id'] ?? 0) ?></div>
                                </td>
                                <td>
                                    <div class="font-semibold"><?= trab_nom_audit_safe($registro['cajas'] ?? '') ?></div>
                                    <div class="text-xs text-slate-500">
                                        Ultimo pago #<?= (int)($registro['ultimo_pago_id'] ?? 0) ?> - <?= trab_nom_audit_datetime($registro['ultimo_pago'] ?? '') ?>
                                    </div>
                                </td>
                                <td class="max-w-[260px]">
                                    <div class="text-xs text-slate-600 break-words"><?= trab_nom_audit_safe($registro['referencias_pago'] ?? '') ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <?php if (!empty($porEstado) || !empty($porPeriodo)): ?>
            <section class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="audit-panel p-4">
                    <h2 class="text-lg font-black m-0 mb-3">Resumen por estado</h2>
                    <div class="space-y-2">
                        <?php foreach ($porEstado as $item): ?>
                            <div class="audit-card p-3 flex items-center justify-between gap-3">
                                <span class="<?= trab_nom_audit_badge_class($item['estado'] ?? '') ?>"><?= trab_nom_audit_label($item['estado'] ?? '') ?></span>
                                <div class="text-right text-sm">
                                    <div class="font-black"><?= trab_nom_audit_num($item['total'] ?? 0) ?> detalle(s)</div>
                                    <div class="text-slate-500">Saldo <?= trab_nom_audit_money($item['saldo'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="audit-panel p-4">
                    <h2 class="text-lg font-black m-0 mb-3">Resumen por periodo</h2>
                    <div class="space-y-2">
                        <?php foreach (array_slice($porPeriodo, 0, 8) as $item): ?>
                            <div class="audit-card p-3 flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-black">#<?= (int)($item['periodo_id'] ?? 0) ?> <?= trab_nom_audit_safe($item['etiqueta'] ?? '') ?></div>
                                    <div class="text-xs text-slate-500"><?= trab_nom_audit_date($item['fecha_inicio'] ?? '') ?> - <?= trab_nom_audit_date($item['fecha_fin'] ?? '') ?></div>
                                </div>
                                <div class="text-right text-sm">
                                    <div class="font-black"><?= trab_nom_audit_num($item['total'] ?? 0) ?> detalle(s)</div>
                                    <div class="text-slate-500">Caja <?= trab_nom_audit_money($item['pagos_caja'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
