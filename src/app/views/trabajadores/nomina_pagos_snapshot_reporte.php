<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porPeriodo = is_array($reporte['por_periodo'] ?? null) ? $reporte['por_periodo'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];
$trabajadoresFiltro = is_array($trabajadoresFiltro ?? null) ? $trabajadoresFiltro : [];

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
        return (string)$estado === 'ok' ? 'nr-badge ok' : 'nr-badge warn';
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
$puedeExportar = !function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones');

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Conciliación</p>
            <h1 class="nom-title">Pagos desde snapshot</h1>
            <p class="nr-sub">Reporte de solo lectura de pagos laborales trazados a snapshots de pre-nómina, detalle congelado y movimiento de Caja. No registra pagos ni modifica Caja.</p>
        </div>
        <div class="nr-toolbar">
            <?php $back_arrow_href = back_url('trabajadores/informes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="nr-btn ms-back-legacy" href="<?= back_url('trabajadores/informes') ?>"><i class="fas fa-arrow-left"></i> Informes</a>
            <?php if ($tablaDisponible && $puedeExportar): ?>
            <a class="nr-btn" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a>
            <?php endif; ?>
            <span class="nr-pill"><i class="fas fa-lock"></i> Solo lectura</span>
        </div>
    </div>

    <div style="margin:14px 0 16px;">
        <?php $subnav_section = 'personal'; $subnav_active = 'informes'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
    </div>

    <div class="nr-stack">
        <section class="nr-kpis">
            <div class="nr-kpi"><div class="nr-kpi-label">Registros</div><div class="nr-kpi-value"><?= trab_snap_pay_num($resumen['total_registros'] ?? 0) ?></div></div>
            <div class="nr-kpi is-ok"><div class="nr-kpi-label">OK</div><div class="nr-kpi-value"><?= trab_snap_pay_num($resumen['ok_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-warn"><div class="nr-kpi-label">Revisar</div><div class="nr-kpi-value"><?= trab_snap_pay_num($resumen['revisar_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Monto</div><div class="nr-kpi-value"><?= trab_snap_pay_money($resumen['monto_total'] ?? 0) ?></div></div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <div class="nr-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Conciliación no disponible.</strong>
                    <p>Faltan tablas o columnas de trazabilidad snapshot en pagos laborales con Caja.</p>
                </div>
            </div>
        <?php else: ?>
            <?php include APP_PATH . '/views/partials/filtros.php'; ?>
            <form method="GET" action="<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>" class="msf-bar" data-auto-filter-form>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Trabajador</span>
                    <select class="msf-control" name="trabajador_id"><?= msf_worker_options($trabajadoresFiltro, $trabajadorId) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Snapshot</span>
                    <input class="msf-control" type="number" min="0" name="periodo_id" value="<?= $periodoId > 0 ? (int)$periodoId : '' ?>" placeholder="# periodo">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Desde pago</span>
                    <input class="msf-control" type="date" name="fecha_inicio" value="<?= trab_snap_pay_safe($fechaInicio, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Hasta pago</span>
                    <input class="msf-control" type="date" name="fecha_fin" value="<?= trab_snap_pay_safe($fechaFin, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Estado</span>
                    <select class="msf-control" name="estado"><?= msf_options(['todos' => 'Todos', 'pagado' => 'Pagado', 'revertido' => 'Revertido'], $estado) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Conciliación</span>
                    <select class="msf-control" name="conciliacion"><?= msf_options(['todos' => 'Todos', 'ok' => 'OK', 'revisar' => 'Revisar'], $conciliacion) ?></select>
                </label>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Buscar</span>
                    <input class="msf-control" type="search" name="buscar" value="<?= trab_snap_pay_safe($buscar, '') ?>" placeholder="Referencia o caja">
                </label>
                <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin">
                    <span class="msf-ranges-label">Rango</span>
                    <button type="button" class="msf-chip" data-msf-range="hoy">Hoy</button>
                    <button type="button" class="msf-chip" data-msf-range="7d">7 días</button>
                    <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                    <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
                </div>
                <div class="msf-actions">
                    <a class="msf-btn" href="<?= url('trabajadores/nomina/periodos/pagos-snapshot') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
                </div>
            </form>

            <div class="nr-grid3">
                <div class="nr-kpi is-accent">
                    <div class="nr-kpi-label">Pagado vigente</div>
                    <div class="nr-kpi-value"><?= trab_snap_pay_money($resumen['monto_pagado_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot"><?= trab_snap_pay_num($resumen['pagados_count'] ?? 0) ?> pago(s)</div>
                </div>
                <div class="nr-kpi">
                    <div class="nr-kpi-label">Revertido</div>
                    <div class="nr-kpi-value"><?= trab_snap_pay_money($resumen['monto_revertido_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot"><?= trab_snap_pay_num($resumen['revertidos_count'] ?? 0) ?> registro(s)</div>
                </div>
                <div class="nr-kpi">
                    <div class="nr-kpi-label">Ingreso por reversión</div>
                    <div class="nr-kpi-value"><?= trab_snap_pay_money($resumen['reversion_caja_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot">Devuelto a Caja</div>
                </div>
            </div>

            <?php if (!empty($porPeriodo)): ?>
                <div class="nr-card">
                    <div class="nr-card-hint" style="margin-bottom:10px;">Pagos por snapshot (clic para abrir el periodo)</div>
                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                        <?php foreach ($porPeriodo as $item): ?>
                            <a class="nr-badge brand" style="text-decoration:none;" href="<?= url('trabajadores/nomina/periodos/' . (int)($item['periodo_id'] ?? 0)) ?>">
                                <i class="fas fa-box-archive"></i>
                                #<?= (int)($item['periodo_id'] ?? 0) ?> · <?= trab_snap_pay_money($item['monto'] ?? 0) ?>
                                <span style="opacity:.7;"><?= trab_snap_pay_num($item['total'] ?? 0) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="nr-panel">
                <div class="nr-panel-head">
                    <div>
                        <h2 class="nr-panel-title">Conciliación trazada</h2>
                        <p class="nr-panel-sub">Compara pago laboral, snapshot, detalle congelado, corte y movimiento de Caja.</p>
                    </div>
                    <span class="nr-pill"><i class="fas fa-list-check"></i> <?= trab_snap_pay_num(count($registros)) ?> visible(s)</span>
                </div>
                <div class="nr-table-wrap">
                    <table class="nr-table">
                        <thead>
                            <tr>
                                <th>Pago</th>
                                <th>Snapshot</th>
                                <th>Trabajador</th>
                                <th class="nr-r">Importe</th>
                                <th>Caja / corte</th>
                                <th>Movimiento</th>
                                <th>Conciliación</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registros)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="nr-empty">
                                            <div class="nr-empty-icon"><i class="fas fa-link-slash"></i></div>
                                            <h3>Sin pagos trazados visibles</h3>
                                            <p>Registra un pago desde un snapshot aprobado para verlo aquí.</p>
                                        </div>
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
                                        <div class="nr-strong">Pago #<?= (int)($registro['id'] ?? 0) ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_datetime($registro['fecha_pago'] ?? '') ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['metodo_pago'] ?? '') ?> · <?= trab_snap_pay_safe($registro['referencia'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <a class="nr-link" href="<?= $periodoLink ?>">Periodo #<?= (int)($registro['nomina_periodo_id'] ?? 0) ?></a>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['periodo_etiqueta'] ?? '') ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_snap_pay_date($registro['periodo_fecha_fin'] ?? '') ?></div>
                                        <div class="nr-sub-txt">Estado: <?= trab_snap_pay_safe($registro['periodo_estado'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <a class="nr-link" href="<?= $trabajadorLink ?>"><?= trab_snap_pay_safe($registro['trabajador_actual_nombre'] ?? '') ?></a>
                                        <div class="nr-sub-txt">Snapshot: <?= trab_snap_pay_safe($registro['detalle_trabajador_nombre'] ?? '') ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['detalle_trabajador_rol'] ?? $registro['trabajador_actual_rol'] ?? '') ?></div>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong"><?= trab_snap_pay_money($registro['monto'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Bruto <?= trab_snap_pay_money($registro['bruto_periodo'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Pend. snapshot <?= trab_snap_pay_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <a class="nr-link" href="<?= $corteLink ?>">Corte #<?= (int)($registro['corte_id'] ?? 0) ?></a>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['caja_nombre'] ?? '') ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['corte_estado'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <div class="nr-strong">Mov. #<?= (int)($registro['movimiento_caja_id'] ?? 0) ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['movimiento_categoria'] ?? '') ?> / <?= trab_snap_pay_safe($registro['movimiento_tipo'] ?? '') ?></div>
                                        <div class="nr-sub-txt"><?= trab_snap_pay_safe($registro['movimiento_referencia'] ?? '') ?></div>
                                        <?php if ((int)($registro['movimiento_reversion_id'] ?? 0) > 0): ?>
                                            <div class="nr-sub-txt" style="color:var(--nom-ok);font-weight:700;">Rev. Caja #<?= (int)($registro['movimiento_reversion_id'] ?? 0) ?> <?= trab_snap_pay_money($registro['movimiento_reversion_monto'] ?? 0) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= trab_snap_pay_badge($conciliacionFila) ?>">
                                            <i class="fas <?= $conciliacionFila === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                                            <?= $conciliacionFila === 'ok' ? 'OK' : 'Revisar' ?>
                                        </span>
                                        <div class="nr-sub-txt" style="margin-top:6px;"><?= trab_snap_pay_safe($registro['estado'] ?? '') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
