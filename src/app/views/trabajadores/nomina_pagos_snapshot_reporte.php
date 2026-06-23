<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porPeriodo = is_array($reporte['por_periodo'] ?? null) ? $reporte['por_periodo'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

if (!function_exists('trab_snap_pay_safe')) {
    function trab_snap_pay_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_snap_pay_money')) {
    function trab_snap_pay_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_snap_pay_num')) {
    function trab_snap_pay_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_snap_pay_date')) {
    function trab_snap_pay_date($value)
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

if (!function_exists('trab_snap_pay_datetime')) {
    function trab_snap_pay_datetime($value)
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

if (!function_exists('trab_snap_pay_badge')) {
    function trab_snap_pay_badge($estado)
    {
        return (string)$estado === 'ok'
            ? 'snap-pay-badge snap-pay-badge-ok'
            : 'snap-pay-badge snap-pay-badge-warn';
    }
}

$periodoId = (int)($filtros['periodo_id'] ?? 0);
$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$conciliacion = (string)($filtros['conciliacion'] ?? 'todos');
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$exportParams = [
    'periodo_id' => $periodoId > 0 ? $periodoId : null,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : null,
    'buscar' => $buscar !== '' ? $buscar : null,
    'estado' => $estado !== '' ? $estado : 'todos',
    'conciliacion' => $conciliacion !== '' ? $conciliacion : 'todos',
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/nomina/periodos/pagos-snapshot/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.snapshot-pay-report {
    --snap-brand: var(--brand-primary, #1f3f46);
    --snap-accent: var(--brand-accent, #b58a3c);
    --snap-line: color-mix(in srgb, var(--snap-brand) 10%, #e5e7eb);
    --snap-soft: color-mix(in srgb, var(--snap-accent) 7%, #f8fafc);
    color: #243142;
}
.snapshot-pay-report .snap-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--snap-brand) 92%, #111827), color-mix(in srgb, var(--snap-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.snapshot-pay-report .snap-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.snapshot-pay-report .snap-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.snapshot-pay-report .snap-subtitle {
    margin-top: 8px;
    max-width: 62rem;
    color: rgba(255,255,255,.86);
}
.snapshot-pay-report .snap-panel {
    border: 1px solid var(--snap-line);
    background: rgba(255,255,255,.94);
}
.snapshot-pay-report .snap-stat {
    border: 1px solid var(--snap-line);
    background: #fff;
    padding: 14px;
}
.snapshot-pay-report .snap-stat-soft {
    background: var(--snap-soft);
}
.snapshot-pay-report .snap-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--snap-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.snapshot-pay-report .snap-btn-primary {
    background: var(--snap-brand);
    border-color: var(--snap-brand);
    color: #fff;
}
.snapshot-pay-report .snap-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--snap-line);
    background: #fff;
    padding: 0 12px;
}
.snapshot-pay-report .snap-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.snapshot-pay-report .snap-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--snap-line);
    background: var(--snap-soft);
    font-size: .78rem;
    font-weight: 800;
}
.snapshot-pay-report .snap-pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--snap-line);
    background: var(--snap-soft);
    font-size: .78rem;
    font-weight: 900;
}
.snapshot-pay-report .snap-pay-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.snapshot-pay-report .snap-pay-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.snapshot-pay-report .snap-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.snapshot-pay-report .snap-table td,
.snapshot-pay-report .snap-table th {
    border-bottom: 1px solid var(--snap-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="snapshot-pay-report">
    <section class="snap-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="snap-kicker">Personal / Conciliacion</div>
                <h1 class="snap-title">Pagos desde snapshot</h1>
                <p class="snap-subtitle">
                    Reporte read-only de pagos laborales trazados a snapshots de pre-nomina, detalle congelado y movimiento de Caja. Solo GET; no registra pagos ni modifica Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[min(100%,760px)]">
                <div class="snap-stat bg-white/10 border-white/20">
                    <div class="text-xs font-black uppercase text-white/70">Registros</div>
                    <div class="text-2xl font-black"><?= trab_snap_pay_num($resumen['total_registros'] ?? 0) ?></div>
                </div>
                <div class="snap-stat bg-white/10 border-white/20">
                    <div class="text-xs font-black uppercase text-white/70">OK</div>
                    <div class="text-2xl font-black"><?= trab_snap_pay_num($resumen['ok_count'] ?? 0) ?></div>
                </div>
                <div class="snap-stat bg-white/10 border-white/20">
                    <div class="text-xs font-black uppercase text-white/70">Revisar</div>
                    <div class="text-2xl font-black"><?= trab_snap_pay_num($resumen['revisar_count'] ?? 0) ?></div>
                </div>
                <div class="snap-stat bg-white/10 border-white/20">
                    <div class="text-xs font-black uppercase text-white/70">Monto</div>
                    <div class="text-2xl font-black"><?= trab_snap_pay_money($resumen['monto_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="snap-btn" href="<?= back_url('trabajadores/nomina/periodos') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Periodos
                </a>
                <a class="snap-btn" href="<?= url('trabajadores/nomina/periodos/reporte') ?>">
                    <i class="fas fa-file-lines"></i>
                    Reporte snapshots
                </a>
                <a class="snap-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>">
                    <i class="fas fa-cash-register"></i>
                    Pagos Caja
                </a>
                <a class="snap-btn" href="<?= url('trabajadores/nomina/auditoria') ?>">
                    <i class="fas fa-list-check"></i>
                    Auditoria nomina
                </a>
                <a class="snap-btn" href="<?= url('trabajadores/nomina/expediente') ?>">
                    <i class="fas fa-folder-open"></i>
                    Expediente
                </a>
            </div>
            <span class="snap-badge">
                <i class="fas fa-lock"></i>
                Solo GET / CSV en memoria
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="snap-panel p-5">
                <strong>Conciliacion no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas o columnas de trazabilidad snapshot en pagos laborales con Caja.</p>
            </div>
        <?php else: ?>
            <div class="snap-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[120px_140px_140px_140px_140px_minmax(190px,1fr)_auto_auto] gap-3 items-end">
                    <label>
                        <span class="snap-label">Snapshot</span>
                        <input class="snap-input mt-1" type="number" min="0" name="periodo_id" value="<?= $periodoId > 0 ? (int)$periodoId : '' ?>" placeholder="# periodo">
                    </label>
                    <label>
                        <span class="snap-label">Trabajador</span>
                        <input class="snap-input mt-1" type="number" min="0" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="# trabajador">
                    </label>
                    <label>
                        <span class="snap-label">Desde pago</span>
                        <input class="snap-input mt-1" type="date" name="fecha_inicio" value="<?= trab_snap_pay_safe($fechaInicio, '') ?>">
                    </label>
                    <label>
                        <span class="snap-label">Hasta pago</span>
                        <input class="snap-input mt-1" type="date" name="fecha_fin" value="<?= trab_snap_pay_safe($fechaFin, '') ?>">
                    </label>
                    <label>
                        <span class="snap-label">Estado</span>
                        <select class="snap-input mt-1" name="estado">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                            <option value="revertido" <?= $estado === 'revertido' ? 'selected' : '' ?>>Revertido</option>
                        </select>
                    </label>
                    <label>
                        <span class="snap-label">Buscar</span>
                        <input class="snap-input mt-1" type="search" name="buscar" value="<?= trab_snap_pay_safe($buscar, '') ?>" placeholder="Referencia, trabajador, caja">
                    </label>
                    <label>
                        <span class="snap-label">Conciliacion</span>
                        <select class="snap-input mt-1" name="conciliacion">
                            <option value="todos" <?= $conciliacion === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="ok" <?= $conciliacion === 'ok' ? 'selected' : '' ?>>OK</option>
                            <option value="revisar" <?= $conciliacion === 'revisar' ? 'selected' : '' ?>>Revisar</option>
                        </select>
                    </label>
                    <div class="flex gap-2">
                        <button class="snap-btn snap-btn-primary" type="submit">
                            <i class="fas fa-filter"></i>
                            Filtrar
                        </button>
                        <a class="snap-btn" href="<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="snap-stat snap-stat-soft">
                    <div class="snap-label">Pagado vigente</div>
                    <div class="text-2xl font-black mt-1"><?= trab_snap_pay_money($resumen['monto_pagado_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_snap_pay_num($resumen['pagados_count'] ?? 0) ?> pago(s)</div>
                </div>
                <div class="snap-stat">
                    <div class="snap-label">Revertido</div>
                    <div class="text-2xl font-black mt-1"><?= trab_snap_pay_money($resumen['monto_revertido_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_snap_pay_num($resumen['revertidos_count'] ?? 0) ?> registro(s)</div>
                </div>
                <div class="snap-stat">
                    <div class="snap-label">Ingreso por reversion</div>
                    <div class="text-2xl font-black mt-1"><?= trab_snap_pay_money($resumen['reversion_caja_total'] ?? 0) ?></div>
                </div>
                <div class="snap-stat snap-stat-soft">
                    <div class="snap-label">Export</div>
                    <a class="snap-btn mt-2" href="<?= $exportUrl ?>">
                        <i class="fas fa-download"></i>
                        Exportar CSV
                    </a>
                </div>
            </div>

            <?php if (!empty($porPeriodo)): ?>
                <div class="snap-panel p-5">
                    <div class="flex flex-wrap items-center gap-2">
                        <?php foreach ($porPeriodo as $item): ?>
                            <a class="snap-badge" href="<?= url('trabajadores/nomina/periodos/' . (int)($item['periodo_id'] ?? 0)) ?>">
                                <i class="fas fa-box-archive"></i>
                                #<?= (int)($item['periodo_id'] ?? 0) ?>
                                <?= trab_snap_pay_money($item['monto'] ?? 0) ?>
                                <span class="text-slate-500"><?= trab_snap_pay_num($item['total'] ?? 0) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="snap-panel overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-black text-lg">Conciliacion trazada</h2>
                        <p class="text-sm text-slate-500 mt-1">Compara pago laboral, snapshot, detalle congelado, corte y movimiento de Caja.</p>
                    </div>
                    <span class="snap-badge">
                        <i class="fas fa-list-check"></i>
                        <?= trab_snap_pay_num(count($registros)) ?> visible(s)
                    </span>
                </div>
                <div class="overflow-x-auto">
                    <table class="snap-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Pago</th>
                                <th class="text-left">Snapshot</th>
                                <th class="text-left">Trabajador</th>
                                <th class="text-right">Importe</th>
                                <th class="text-left">Caja / corte</th>
                                <th class="text-left">Movimiento</th>
                                <th class="text-left">Conciliacion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-slate-500">
                                        <i class="fas fa-link-slash text-3xl text-slate-300 mb-3 block"></i>
                                        <div class="font-black text-slate-700">Sin pagos trazados visibles</div>
                                        <div>Registra un pago desde un snapshot aprobado para verlo aqui.</div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($registros as $registro): ?>
                                <?php
                                $conciliacionFila = (string)($registro['conciliacion_estado'] ?? 'revisar');
                                $periodoLink = url('trabajadores/nomina/periodos/' . (int)($registro['nomina_periodo_id'] ?? 0));
                                $trabajadorLink = url('trabajadores/' . (int)($registro['trabajador_id'] ?? 0));
                                $corteLink = url('caja/corte/' . (int)($registro['corte_id'] ?? 0));
                                ?>
                                <tr>
                                    <td>
                                        <div class="font-black">Pago #<?= (int)($registro['id'] ?? 0) ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_datetime($registro['fecha_pago'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['metodo_pago'] ?? '') ?> · <?= trab_snap_pay_safe($registro['referencia'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <a class="font-black underline" href="<?= $periodoLink ?>">Periodo #<?= (int)($registro['nomina_periodo_id'] ?? 0) ?></a>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['periodo_etiqueta'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_snap_pay_date($registro['periodo_fecha_fin'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500">Estado: <?= trab_snap_pay_safe($registro['periodo_estado'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <a class="font-black underline" href="<?= $trabajadorLink ?>"><?= trab_snap_pay_safe($registro['trabajador_actual_nombre'] ?? '') ?></a>
                                        <div class="text-xs text-slate-500">Snapshot: <?= trab_snap_pay_safe($registro['detalle_trabajador_nombre'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['detalle_trabajador_rol'] ?? $registro['trabajador_actual_rol'] ?? '') ?></div>
                                    </td>
                                    <td class="text-right">
                                        <div class="font-black"><?= trab_snap_pay_money($registro['monto'] ?? 0) ?></div>
                                        <div class="text-xs text-slate-500">Bruto <?= trab_snap_pay_money($registro['bruto_periodo'] ?? 0) ?></div>
                                        <div class="text-xs text-slate-500">Pend. snapshot <?= trab_snap_pay_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <a class="font-black underline" href="<?= $corteLink ?>">Corte #<?= (int)($registro['corte_id'] ?? 0) ?></a>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['caja_nombre'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['corte_estado'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="font-black">Mov. #<?= (int)($registro['movimiento_caja_id'] ?? 0) ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['movimiento_categoria'] ?? '') ?> / <?= trab_snap_pay_safe($registro['movimiento_tipo'] ?? '') ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_snap_pay_safe($registro['movimiento_referencia'] ?? '') ?></div>
                                        <?php if ((int)($registro['movimiento_reversion_id'] ?? 0) > 0): ?>
                                            <div class="text-xs font-bold text-emerald-700">Rev. Caja #<?= (int)($registro['movimiento_reversion_id'] ?? 0) ?> <?= trab_snap_pay_money($registro['movimiento_reversion_monto'] ?? 0) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= trab_snap_pay_badge($conciliacionFila) ?>">
                                            <i class="fas <?= $conciliacionFila === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                                            <?= $conciliacionFila === 'ok' ? 'OK' : 'Revisar' ?>
                                        </span>
                                        <div class="text-xs text-slate-500 mt-2"><?= trab_snap_pay_safe($registro['estado'] ?? '') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
