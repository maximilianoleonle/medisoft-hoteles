<?php
$expediente = is_array($expediente ?? null) ? $expediente : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($expediente['registros'] ?? null) ? $expediente['registros'] : [];
$resumen = is_array($expediente['resumen'] ?? null) ? $expediente['resumen'] : [];
$porEstado = is_array($expediente['por_estado'] ?? null) ? $expediente['por_estado'] : [];
$porPeriodo = is_array($expediente['por_periodo'] ?? null) ? $expediente['por_periodo'] : [];
$bloqueos = is_array($expediente['bloqueos'] ?? null) ? $expediente['bloqueos'] : [];
$filtros = is_array($expediente['filtros_normalizados'] ?? null) ? $expediente['filtros_normalizados'] : [];

if (!function_exists('trab_nom_exp_safe')) {
    function trab_nom_exp_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nom_exp_money')) {
    function trab_nom_exp_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nom_exp_num')) {
    function trab_nom_exp_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nom_exp_date')) {
    function trab_nom_exp_date($value)
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

if (!function_exists('trab_nom_exp_datetime')) {
    function trab_nom_exp_datetime($value)
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

if (!function_exists('trab_nom_exp_label')) {
    function trab_nom_exp_label($estado)
    {
        $labels = [
            'listo_revision' => 'Listo para revision',
            'con_pendientes' => 'Con pendientes',
            'requiere_correccion' => 'Requiere correccion',
            'bloqueado' => 'Bloqueado',
            'anulado' => 'Anulado',
            'liquidado' => 'Liquidado',
            'parcial' => 'Parcial',
            'sin_pago' => 'Sin pago',
            'revisar' => 'Revisar',
            'cerrado' => 'Cerrado',
            'aprobado' => 'Aprobado',
        ];

        $estado = (string)$estado;
        return $labels[$estado] ?? ($estado !== '' ? ucfirst(str_replace('_', ' ', $estado)) : '-');
    }
}

if (!function_exists('trab_nom_exp_badge')) {
    function trab_nom_exp_badge($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'listo_revision') {
            return 'exp-badge exp-badge-ok';
        }
        if ($estado === 'con_pendientes') {
            return 'exp-badge exp-badge-warn';
        }
        if ($estado === 'requiere_correccion' || $estado === 'bloqueado' || $estado === 'anulado') {
            return 'exp-badge exp-badge-danger';
        }
        return 'exp-badge';
    }
}

$periodoId = (int)($filtros['periodo_id'] ?? 0);
$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estadoSnapshot = (string)($filtros['estado_snapshot'] ?? 'todos');
$estadoExpediente = (string)($filtros['estado_expediente'] ?? 'todos');
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$exportParams = [
    'periodo_id' => $periodoId > 0 ? $periodoId : null,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : null,
    'buscar' => $buscar !== '' ? $buscar : null,
    'estado_snapshot' => $estadoSnapshot !== '' ? $estadoSnapshot : 'todos',
    'estado_expediente' => $estadoExpediente !== '' ? $estadoExpediente : 'todos',
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/nomina/expediente/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.nomina-exp {
    --exp-brand: var(--brand-primary, #1f3f46);
    --exp-accent: var(--brand-accent, #b58a3c);
    --exp-line: color-mix(in srgb, var(--exp-brand) 10%, #e5e7eb);
    --exp-soft: color-mix(in srgb, var(--exp-accent) 7%, #f8fafc);
    color: #243142;
}
.nomina-exp .exp-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--exp-brand) 92%, #111827), color-mix(in srgb, var(--exp-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.nomina-exp .exp-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.nomina-exp .exp-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.nomina-exp .exp-subtitle {
    margin-top: 8px;
    max-width: 66rem;
    color: rgba(255,255,255,.86);
}
.nomina-exp .exp-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.nomina-exp .exp-panel,
.nomina-exp .exp-card {
    border: 1px solid var(--exp-line);
    background: rgba(255,255,255,.94);
}
.nomina-exp .exp-stat {
    border: 1px solid var(--exp-line);
    background: #fff;
    padding: 14px;
}
.nomina-exp .exp-stat-soft {
    background: var(--exp-soft);
}
.nomina-exp .exp-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.nomina-exp .exp-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--exp-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.nomina-exp .exp-btn-primary {
    background: var(--exp-brand);
    border-color: var(--exp-brand);
    color: #fff;
}
.nomina-exp .exp-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--exp-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-exp .exp-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--exp-line);
    background: var(--exp-soft);
    font-size: .78rem;
    font-weight: 900;
}
.nomina-exp .exp-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.nomina-exp .exp-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.nomina-exp .exp-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.nomina-exp .exp-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.nomina-exp .exp-table td,
.nomina-exp .exp-table th {
    border-bottom: 1px solid var(--exp-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="nomina-exp">
    <section class="exp-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="exp-kicker">Personal / Expediente nomina</div>
                <h1 class="exp-title">Expediente administrativo de nomina</h1>
                <p class="exp-subtitle">
                    Lectura interna de snapshots, auditoria, pagos Caja, reversiones y bloqueos. No modifica Caja, no modifica snapshots y no genera nomina oficial.
                </p>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 min-w-full xl:min-w-[760px]">
                <div class="exp-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Detalles</div>
                    <div class="text-2xl font-black"><?= trab_nom_exp_num($resumen['total_registros'] ?? 0) ?></div>
                </div>
                <div class="exp-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Trabajadores</div>
                    <div class="text-2xl font-black"><?= trab_nom_exp_num($resumen['trabajadores_total'] ?? 0) ?></div>
                </div>
                <div class="exp-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Bloqueos</div>
                    <div class="text-2xl font-black"><?= trab_nom_exp_num($resumen['bloqueos_total'] ?? 0) ?></div>
                </div>
                <div class="exp-stat-hero">
                    <div class="text-xs opacity-75 font-bold">Saldo auditoria</div>
                    <div class="text-2xl font-black"><?= trab_nom_exp_money($resumen['saldo_auditoria_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <div class="p-5 space-y-5">
        <?php $subnav_section = 'personal'; $subnav_active = 'informes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = back_url('trabajadores/informes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="exp-btn ms-back-legacy" href="<?= back_url('trabajadores/informes') ?>"><i class="fas fa-arrow-left"></i> Informes</a>
                <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?><a class="exp-btn exp-btn-primary" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a><?php endif; ?>
            </div>
            <span class="exp-badge"><i class="fas fa-lock"></i> GET / read-only</span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="exp-panel p-4 text-sm font-semibold text-amber-900 bg-amber-50">
                El expediente requiere snapshots, pagos Caja y trazabilidad de pre-nomina. No se ejecuta ninguna escritura.
            </div>
        <?php endif; ?>

        <form class="exp-panel p-4 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-8 gap-3" method="GET" action="<?= url('trabajadores/nomina/expediente') ?>">
            <label>
                <span class="exp-label">Periodo ID</span>
                <input class="exp-input" type="number" min="0" name="periodo_id" value="<?= $periodoId > 0 ? $periodoId : '' ?>" placeholder="Todos">
            </label>
            <label>
                <span class="exp-label">Trabajador ID</span>
                <input class="exp-input" type="number" min="0" name="trabajador_id" value="<?= $trabajadorId > 0 ? $trabajadorId : '' ?>" placeholder="Todos">
            </label>
            <label>
                <span class="exp-label">Desde</span>
                <input class="exp-input" type="date" name="fecha_inicio" value="<?= trab_nom_exp_safe($fechaInicio, '') ?>">
            </label>
            <label>
                <span class="exp-label">Hasta</span>
                <input class="exp-input" type="date" name="fecha_fin" value="<?= trab_nom_exp_safe($fechaFin, '') ?>">
            </label>
            <label>
                <span class="exp-label">Snapshot</span>
                <select class="exp-input" name="estado_snapshot">
                    <?php foreach (['todos' => 'Todos', 'cerrado' => 'Cerrado', 'aprobado' => 'Aprobado', 'anulado' => 'Anulado'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $estadoSnapshot === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span class="exp-label">Expediente</span>
                <select class="exp-input" name="estado_expediente">
                    <?php foreach (['todos' => 'Todos', 'listo_revision' => 'Listo revision', 'con_pendientes' => 'Con pendientes', 'requiere_correccion' => 'Requiere correccion', 'bloqueado' => 'Bloqueado', 'anulado' => 'Anulado'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $estadoExpediente === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="xl:col-span-1">
                <span class="exp-label">Buscar</span>
                <input class="exp-input" type="search" name="buscar" value="<?= trab_nom_exp_safe($buscar, '') ?>" placeholder="Trabajador, ref, caja">
            </label>
            <div class="flex items-end">
                <button class="exp-btn exp-btn-primary w-full" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </form>

        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
            <div class="exp-stat exp-stat-soft">
                <div class="exp-label">Listo revision</div>
                <div class="text-2xl font-black text-emerald-700"><?= trab_nom_exp_num($resumen['listo_revision_count'] ?? 0) ?></div>
            </div>
            <div class="exp-stat">
                <div class="exp-label">Con pendientes</div>
                <div class="text-2xl font-black text-amber-700"><?= trab_nom_exp_num($resumen['con_pendientes_count'] ?? 0) ?></div>
            </div>
            <div class="exp-stat">
                <div class="exp-label">Requiere correccion</div>
                <div class="text-2xl font-black text-red-700"><?= trab_nom_exp_num($resumen['requiere_correccion_count'] ?? 0) ?></div>
            </div>
            <div class="exp-stat">
                <div class="exp-label">Bloqueado / anulado</div>
                <div class="text-2xl font-black"><?= trab_nom_exp_num(($resumen['bloqueado_count'] ?? 0) + ($resumen['anulado_count'] ?? 0)) ?></div>
            </div>
            <div class="exp-stat">
                <div class="exp-label">Pagos Caja</div>
                <div class="text-2xl font-black"><?= trab_nom_exp_money($resumen['pagos_caja_total'] ?? 0) ?></div>
            </div>
        </section>

        <section class="exp-panel overflow-x-auto">
            <div class="p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 border-b border-slate-200">
                <div>
                    <h2 class="text-lg font-black m-0">Detalle del expediente</h2>
                    <p class="text-sm text-slate-500 m-0">Una fila por detalle de snapshot; los bloqueos son administrativos e informativos.</p>
                </div>
                <span class="exp-badge"><i class="fas fa-list-check"></i> <?= trab_nom_exp_num(count($registros)) ?> visible(s)</span>
            </div>

            <?php if (empty($registros)): ?>
                <div class="py-16 text-center text-slate-500">
                    <i class="fas fa-folder-open text-4xl text-slate-300 mb-3"></i>
                    <h3 class="text-lg font-black text-slate-700">Sin expedientes para revisar</h3>
                    <p>Ajusta filtros o revisa snapshots aprobados antes de preparar expediente administrativo.</p>
                </div>
            <?php else: ?>
                <table class="exp-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">Periodo</th>
                            <th class="text-left">Trabajador</th>
                            <th class="text-left">Expediente</th>
                            <th class="text-right">Snapshot</th>
                            <th class="text-right">Caja</th>
                            <th class="text-right">Saldo</th>
                            <th class="text-left">Bloqueos</th>
                            <th class="text-left">Evidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $registro): ?>
                            <?php
                            $estadoFila = (string)($registro['estado_expediente'] ?? 'bloqueado');
                            $periodoUrl = url('trabajadores/nomina/periodos/' . (int)($registro['periodo_id'] ?? 0));
                            $bloqueosFila = is_array($registro['bloqueos'] ?? null) ? $registro['bloqueos'] : [];
                            ?>
                            <tr>
                                <td>
                                    <a class="font-black text-slate-800 underline" href="<?= $periodoUrl ?>">
                                        #<?= (int)($registro['periodo_id'] ?? 0) ?> <?= trab_nom_exp_safe($registro['periodo_etiqueta'] ?? '') ?>
                                    </a>
                                    <div class="text-xs text-slate-500">
                                        <?= trab_nom_exp_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_nom_exp_date($registro['periodo_fecha_fin'] ?? '') ?>
                                    </div>
                                    <div class="mt-1">
                                        <span class="exp-badge"><?= trab_nom_exp_label($registro['periodo_estado'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="font-black"><?= trab_nom_exp_safe($registro['trabajador_snapshot_nombre'] ?? '') ?></div>
                                    <div class="text-xs text-slate-500">
                                        ID <?= (int)($registro['trabajador_id'] ?? 0) ?> - <?= trab_nom_exp_safe($registro['trabajador_snapshot_rol'] ?? '') ?>
                                    </div>
                                    <div class="text-xs text-slate-500">Actual: <?= trab_nom_exp_safe($registro['trabajador_actual_nombre'] ?? '') ?></div>
                                </td>
                                <td>
                                    <span class="<?= trab_nom_exp_badge($estadoFila) ?>">
                                        <i class="fas fa-circle"></i> <?= trab_nom_exp_label($estadoFila) ?>
                                    </span>
                                    <div class="text-xs text-slate-500 mt-2">Auditoria: <?= trab_nom_exp_label($registro['auditoria_estado'] ?? '') ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="font-black"><?= trab_nom_exp_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Bruto <?= trab_nom_exp_money($registro['bruto_periodo'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Neto <?= trab_nom_exp_money($registro['neto_sugerido'] ?? 0) ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="font-black text-emerald-700"><?= trab_nom_exp_money($registro['pagos_caja_total'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">
                                        <?= trab_nom_exp_num($registro['pagos_pagados_count'] ?? 0) ?> pago(s), <?= trab_nom_exp_num($registro['pagos_revertidos_count'] ?? 0) ?> rev.
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div class="font-black"><?= trab_nom_exp_money($registro['saldo_auditoria'] ?? 0) ?></div>
                                    <div class="text-xs text-slate-500">Detalle #<?= (int)($registro['detalle_id'] ?? 0) ?></div>
                                </td>
                                <td class="max-w-[260px]">
                                    <?php if (empty($bloqueosFila)): ?>
                                        <span class="exp-badge exp-badge-ok"><i class="fas fa-check"></i> Sin bloqueos criticos</span>
                                    <?php else: ?>
                                        <div class="space-y-1">
                                            <?php foreach ($bloqueosFila as $bloqueo): ?>
                                                <div class="text-xs font-bold text-red-800"><?= trab_nom_exp_safe($bloqueo) ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="max-w-[260px]">
                                    <div class="font-semibold"><?= trab_nom_exp_safe($registro['cajas'] ?? '') ?></div>
                                    <div class="text-xs text-slate-500">
                                        Ultimo pago #<?= (int)($registro['ultimo_pago_id'] ?? 0) ?> - <?= trab_nom_exp_datetime($registro['ultimo_pago'] ?? '') ?>
                                    </div>
                                    <div class="text-xs text-slate-600 break-words"><?= trab_nom_exp_safe($registro['referencias_pago'] ?? '') ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="exp-panel p-4">
                <h2 class="text-lg font-black m-0 mb-3">Resumen por estado</h2>
                <div class="space-y-2">
                    <?php foreach ($porEstado as $item): ?>
                        <div class="exp-card p-3 flex items-center justify-between gap-3">
                            <span class="<?= trab_nom_exp_badge($item['estado'] ?? '') ?>"><?= trab_nom_exp_label($item['estado'] ?? '') ?></span>
                            <div class="text-right text-sm">
                                <div class="font-black"><?= trab_nom_exp_num($item['total'] ?? 0) ?> detalle(s)</div>
                                <div class="text-slate-500">Saldo <?= trab_nom_exp_money($item['saldo'] ?? 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($porEstado)): ?>
                        <div class="text-sm text-slate-500">Sin estados visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="exp-panel p-4">
                <h2 class="text-lg font-black m-0 mb-3">Bloqueos detectados</h2>
                <div class="space-y-2">
                    <?php foreach ($bloqueos as $item): ?>
                        <div class="exp-card p-3 flex items-center justify-between gap-3">
                            <span class="font-bold text-slate-700"><?= trab_nom_exp_safe($item['bloqueo'] ?? '') ?></span>
                            <span class="exp-badge"><?= trab_nom_exp_num($item['total'] ?? 0) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($bloqueos)): ?>
                        <div class="text-sm text-slate-500">Sin bloqueos visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="exp-panel p-4">
                <h2 class="text-lg font-black m-0 mb-3">Resumen por periodo</h2>
                <div class="space-y-2">
                    <?php foreach (array_slice($porPeriodo, 0, 8) as $item): ?>
                        <div class="exp-card p-3 flex items-center justify-between gap-3">
                            <div>
                                <div class="font-black">#<?= (int)($item['periodo_id'] ?? 0) ?> <?= trab_nom_exp_safe($item['etiqueta'] ?? '') ?></div>
                                <div class="text-xs text-slate-500"><?= trab_nom_exp_date($item['fecha_inicio'] ?? '') ?> - <?= trab_nom_exp_date($item['fecha_fin'] ?? '') ?></div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="font-black"><?= trab_nom_exp_num($item['total'] ?? 0) ?> detalle(s)</div>
                                <div class="text-slate-500">Bloq. <?= trab_nom_exp_num($item['bloqueos'] ?? 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($porPeriodo)): ?>
                        <div class="text-sm text-slate-500">Sin periodos visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
