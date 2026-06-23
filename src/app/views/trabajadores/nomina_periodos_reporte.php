<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

if (!function_exists('trab_nomina_report_safe')) {
    function trab_nomina_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nomina_report_money')) {
    function trab_nomina_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nomina_report_num')) {
    function trab_nomina_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nomina_report_date')) {
    function trab_nomina_report_date($value)
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

if (!function_exists('trab_nomina_report_datetime')) {
    function trab_nomina_report_datetime($value)
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

if (!function_exists('trab_nomina_report_badge_class')) {
    function trab_nomina_report_badge_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'payroll-report-badge payroll-report-badge-ok';
        }
        if ($estado === 'anulado') {
            return 'payroll-report-badge payroll-report-badge-danger';
        }
        return 'payroll-report-badge payroll-report-badge-warn';
    }
}

if (!function_exists('trab_nomina_report_label')) {
    function trab_nomina_report_label($estado)
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

if (!function_exists('trab_nomina_report_user')) {
    function trab_nomina_report_user(array $registro, string $prefijo)
    {
        $nombre = trim((string)($registro[$prefijo . '_nombre'] ?? ''));
        if ($nombre !== '') {
            return trab_nomina_report_safe($nombre);
        }

        return trab_nomina_report_safe($registro[$prefijo . '_login'] ?? '');
    }
}

$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$tipoPeriodo = (string)($filtros['tipo_periodo'] ?? 'todos');
$buscar = (string)($filtros['buscar'] ?? '');
$exportParams = [
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
    'estado' => $estado !== '' ? $estado : 'todos',
    'tipo_periodo' => $tipoPeriodo !== '' ? $tipoPeriodo : 'todos',
    'buscar' => $buscar !== '' ? $buscar : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/nomina/periodos/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.payroll-snapshot-report {
    --payroll-brand: var(--brand-primary, #1f3f46);
    --payroll-accent: var(--brand-accent, #b58a3c);
    --payroll-line: color-mix(in srgb, var(--payroll-brand) 10%, #e5e7eb);
    --payroll-soft: color-mix(in srgb, var(--payroll-accent) 7%, #f8fafc);
    color: #243142;
}
.payroll-snapshot-report .payroll-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--payroll-brand) 92%, #111827), color-mix(in srgb, var(--payroll-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.payroll-snapshot-report .payroll-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.payroll-snapshot-report .payroll-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.payroll-snapshot-report .payroll-subtitle {
    margin-top: 8px;
    max-width: 60rem;
    color: rgba(255,255,255,.86);
}
.payroll-snapshot-report .payroll-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.payroll-snapshot-report .payroll-panel {
    border: 1px solid var(--payroll-line);
    background: rgba(255,255,255,.94);
}
.payroll-snapshot-report .payroll-stat {
    border: 1px solid var(--payroll-line);
    background: #fff;
    padding: 14px;
}
.payroll-snapshot-report .payroll-stat-soft {
    background: var(--payroll-soft);
}
.payroll-snapshot-report .payroll-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--payroll-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.payroll-snapshot-report .payroll-btn-primary {
    background: var(--payroll-brand);
    border-color: var(--payroll-brand);
    color: #fff;
}
.payroll-snapshot-report .payroll-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--payroll-line);
    background: #fff;
    padding: 0 12px;
}
.payroll-snapshot-report .payroll-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.payroll-snapshot-report .payroll-report-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--payroll-line);
    background: var(--payroll-soft);
    font-size: .78rem;
    font-weight: 800;
}
.payroll-snapshot-report .payroll-report-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.payroll-snapshot-report .payroll-report-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.payroll-snapshot-report .payroll-report-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.payroll-snapshot-report .payroll-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.payroll-snapshot-report .payroll-table td,
.payroll-snapshot-report .payroll-table th {
    border-bottom: 1px solid var(--payroll-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="payroll-snapshot-report">
    <section class="payroll-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="payroll-kicker">Personal / Pre-nomina</div>
                <h1 class="payroll-title">Reporte de snapshots de pre-nomina</h1>
                <p class="payroll-subtitle">
                    Historial read-only de periodos cerrados, aprobados o anulados. Consulta importes congelados del snapshot; no recalcula nomina, no registra pagos y no modifica Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Snapshots</div>
                    <div class="text-2xl font-black"><?= trab_nomina_report_num($resumen['total_registros'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Aprobados</div>
                    <div class="text-2xl font-black"><?= trab_nomina_report_num($resumen['aprobados_count'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Neto sugerido</div>
                    <div class="text-xl font-black"><?= trab_nomina_report_money($resumen['neto_sugerido_total'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Pendiente</div>
                    <div class="text-xl font-black"><?= trab_nomina_report_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="payroll-btn" href="<?= back_url('trabajadores/nomina/periodos') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Periodos
                </a>
                <a class="payroll-btn" href="<?= url('trabajadores/nomina/preview') ?>">
                    <i class="fas fa-clipboard-list"></i>
                    Preview nomina
                </a>
                <a class="payroll-btn" href="<?= $exportUrl ?>">
                    <i class="fas fa-file-csv"></i>
                    Exportar CSV
                </a>
            </div>
            <span class="payroll-report-badge">
                <i class="fas fa-lock"></i>
                Solo GET / Read-only
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="payroll-panel p-5">
                <strong>Reporte no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas de snapshots de pre-nomina para consultar el historial.</p>
            </div>
        <?php else: ?>
            <div class="payroll-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/periodos/reporte') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[150px_150px_160px_160px_minmax(190px,1fr)_auto_auto] gap-3">
                    <input class="payroll-input" type="date" name="fecha_inicio" value="<?= trab_nomina_report_safe($fechaInicio, '') ?>">
                    <input class="payroll-input" type="date" name="fecha_fin" value="<?= trab_nomina_report_safe($fechaFin, '') ?>">
                    <select class="payroll-input" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="cerrado" <?= $estado === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                        <option value="aprobado" <?= $estado === 'aprobado' ? 'selected' : '' ?>>Aprobado</option>
                        <option value="anulado" <?= $estado === 'anulado' ? 'selected' : '' ?>>Anulado</option>
                    </select>
                    <select class="payroll-input" name="tipo_periodo">
                        <option value="todos" <?= $tipoPeriodo === 'todos' ? 'selected' : '' ?>>Todos los tipos</option>
                        <option value="semanal" <?= $tipoPeriodo === 'semanal' ? 'selected' : '' ?>>Semanal</option>
                        <option value="quincenal" <?= $tipoPeriodo === 'quincenal' ? 'selected' : '' ?>>Quincenal</option>
                        <option value="mensual" <?= $tipoPeriodo === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                        <option value="manual" <?= $tipoPeriodo === 'manual' ? 'selected' : '' ?>>Manual</option>
                    </select>
                    <input class="payroll-input" type="search" name="buscar" value="<?= trab_nomina_report_safe($buscar, '') ?>" placeholder="Buscar folio, etiqueta, usuario o motivo">
                    <button class="payroll-btn payroll-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="payroll-btn" href="<?= url('trabajadores/nomina/periodos/reporte') ?>">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="payroll-stat payroll-stat-soft">
                    <div class="payroll-label">Bruto congelado</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['bruto_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_nomina_report_num($resumen['trabajadores_total'] ?? 0) ?> trabajador(es) acumulados</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Deducciones</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['deducciones_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Anticipos y prestamos informativos del snapshot</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Pagos Caja aplicados</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Historico congelado; no mueve Caja</div>
                </div>
                <div class="payroll-stat">
                    <div class="payroll-label">Reversiones detectadas</div>
                    <div class="text-2xl font-black mt-1"><?= trab_nomina_report_money($resumen['reversiones_detectadas_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Dato informativo del snapshot</div>
                </div>
            </div>

            <div class="payroll-panel overflow-hidden">
                <div class="p-4 border-b border-slate-200">
                    <h2 class="font-black">Resumen por estado</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="payroll-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Estado</th>
                                <th class="text-right">Snapshots</th>
                                <th class="text-right">Trabajadores</th>
                                <th class="text-right">Neto sugerido</th>
                                <th class="text-right">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porEstado)): ?>
                                <tr><td colspan="5" class="text-center text-slate-500">Sin snapshots para resumir.</td></tr>
                            <?php else: ?>
                                <?php foreach ($porEstado as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="<?= trab_nomina_report_badge_class($item['estado'] ?? '') ?>">
                                                <?= trab_nomina_report_label($item['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right"><?= trab_nomina_report_num($item['total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_num($item['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_money($item['neto_sugerido_total'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_nomina_report_money($item['pendiente_pago_total'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="payroll-panel overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-black text-lg">Snapshots persistentes</h2>
                        <p class="text-sm text-slate-500 mt-1">Listado limitado a los registros filtrados del hotel actual.</p>
                    </div>
                    <span class="payroll-report-badge">
                        <i class="fas fa-list-check"></i>
                        <?= trab_nomina_report_num(count($registros)) ?> visible(s)
                    </span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-box-archive"></i></div>
                        <h3 class="font-black text-lg">Sin snapshots de pre-nomina</h3>
                        <p class="text-sm text-slate-500 mt-1">Ajusta los filtros o cierra un periodo desde la pantalla de periodos cuando corresponda.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="payroll-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Periodo</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Trabajadores</th>
                                    <th class="text-right">Importes</th>
                                    <th class="text-left">Responsables</th>
                                    <th class="text-left">Motivo</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td>
                                            <div class="font-black"><?= trab_nomina_report_safe($registro['etiqueta'] ?? 'Snapshot') ?></div>
                                            <div class="text-xs text-slate-500">
                                                #<?= (int)($registro['id'] ?? 0) ?> / <?= trab_nomina_report_safe($registro['tipo_periodo'] ?? '') ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_nomina_report_date($registro['fecha_inicio'] ?? '') ?> - <?= trab_nomina_report_date($registro['fecha_fin'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="<?= trab_nomina_report_badge_class($registro['estado'] ?? '') ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= trab_nomina_report_label($registro['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="text-right font-black"><?= trab_nomina_report_num($registro['trabajadores_total'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <div><span class="text-slate-500">Bruto:</span> <strong><?= trab_nomina_report_money($registro['bruto_total'] ?? 0) ?></strong></div>
                                            <div><span class="text-slate-500">Neto:</span> <strong><?= trab_nomina_report_money($registro['neto_sugerido_total'] ?? 0) ?></strong></div>
                                            <div><span class="text-slate-500">Pendiente:</span> <strong><?= trab_nomina_report_money($registro['pendiente_pago_total'] ?? 0) ?></strong></div>
                                        </td>
                                        <td>
                                            <div class="text-xs text-slate-500">Cierre</div>
                                            <div class="font-bold"><?= trab_nomina_report_user($registro, 'cerrado_por') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['cerrado_at'] ?? null) ?></div>
                                            <?php if (!empty($registro['aprobado_at'])): ?>
                                                <div class="text-xs text-slate-500 mt-2">Aprobacion</div>
                                                <div class="font-bold"><?= trab_nomina_report_user($registro, 'aprobado_por') ?></div>
                                                <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['aprobado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($registro['anulado_at'])): ?>
                                                <div class="text-xs text-slate-500 mt-2">Anulacion</div>
                                                <div class="font-bold"><?= trab_nomina_report_user($registro, 'anulado_por') ?></div>
                                                <div class="text-xs text-slate-500"><?= trab_nomina_report_datetime($registro['anulado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="max-w-[260px]">
                                            <div class="text-sm text-slate-600"><?= trab_nomina_report_safe($registro['motivo_anulacion'] ?? '', 'Sin motivo') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <a class="payroll-report-badge" href="<?= url('trabajadores/nomina/periodos/' . (int)($registro['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver snapshot
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
