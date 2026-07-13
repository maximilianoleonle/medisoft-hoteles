<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$porPeriodo = is_array($reporte['por_periodo'] ?? null) ? $reporte['por_periodo'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];
$trabajadoresFiltro = is_array($trabajadoresFiltro ?? null) ? $trabajadoresFiltro : [];

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
        if ($estado === 'liquidado' || $estado === 'aprobado') {
            return 'nr-badge ok';
        }
        if ($estado === 'parcial' || $estado === 'cerrado') {
            return 'nr-badge warn';
        }
        if ($estado === 'revisar' || $estado === 'anulado') {
            return 'nr-badge danger';
        }
        return 'nr-badge neutral';
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

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Auditoría</p>
            <h1 class="nom-title">Auditoría consolidada de nómina</h1>
            <p class="nr-sub">Lectura administrativa de snapshots, pagos Caja vinculados, reversiones y saldo pendiente por detalle. No registra pagos, no modifica Caja y no genera nómina oficial.</p>
        </div>
        <div class="nr-toolbar">
            <?php $back_arrow_href = back_url('trabajadores/informes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="nr-btn ms-back-legacy" href="<?= back_url('trabajadores/informes') ?>"><i class="fas fa-arrow-left"></i> Informes</a>
            <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
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
            <div class="nr-kpi"><div class="nr-kpi-label">Detalles</div><div class="nr-kpi-value"><?= trab_nom_audit_num($resumen['total_registros'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Trabajadores</div><div class="nr-kpi-value"><?= trab_nom_audit_num($resumen['trabajadores_total'] ?? 0) ?></div></div>
            <div class="nr-kpi is-ok"><div class="nr-kpi-label">Pagos Caja</div><div class="nr-kpi-value"><?= trab_nom_audit_money($resumen['pagos_caja_total'] ?? 0) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Saldo auditoría</div><div class="nr-kpi-value"><?= trab_nom_audit_money($resumen['saldo_auditoria_total'] ?? 0) ?></div></div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <div class="nr-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Auditoría no disponible.</strong>
                    <p>Requiere las tablas de snapshots, pagos Caja y trazabilidad. No se ejecuta ninguna escritura.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php include APP_PATH . '/views/partials/filtros.php'; ?>
        <form class="msf-bar" method="get" action="<?= url('trabajadores/nomina/auditoria') ?>">
            <label class="msf-field msf-field--grow">
                <span class="msf-label">Trabajador</span>
                <select class="msf-control" name="trabajador_id"><?= msf_worker_options($trabajadoresFiltro, $trabajadorId) ?></select>
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Periodo ID</span>
                <input class="msf-control" type="number" min="0" name="periodo_id" value="<?= $periodoId > 0 ? $periodoId : '' ?>" placeholder="Todos">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Desde</span>
                <input class="msf-control" type="date" name="fecha_inicio" value="<?= trab_nom_audit_safe($fechaInicio, '') ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Hasta</span>
                <input class="msf-control" type="date" name="fecha_fin" value="<?= trab_nom_audit_safe($fechaFin, '') ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Snapshot</span>
                <select class="msf-control" name="estado_snapshot"><?= msf_options(['todos' => 'Todos', 'cerrado' => 'Cerrado', 'aprobado' => 'Aprobado', 'anulado' => 'Anulado'], $estadoSnapshot) ?></select>
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Auditoría</span>
                <select class="msf-control" name="estado_auditoria"><?= msf_options(['todos' => 'Todos', 'liquidado' => 'Liquidado', 'parcial' => 'Parcial', 'sin_pago' => 'Sin pago', 'revisar' => 'Revisar'], $estadoAuditoria) ?></select>
            </label>
            <label class="msf-field msf-field--grow">
                <span class="msf-label">Buscar</span>
                <input class="msf-control" type="search" name="buscar" value="<?= trab_nom_audit_safe($buscar, '') ?>" placeholder="Ref o caja">
            </label>
            <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin">
                <span class="msf-ranges-label">Rango</span>
                <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
            </div>
            <div class="msf-actions">
                <button class="msf-btn msf-btn--primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                <a class="msf-btn" href="<?= url('trabajadores/nomina/auditoria') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
            </div>
        </form>

        <section class="nr-kpis cols-5">
            <div class="nr-kpi is-ok"><div class="nr-kpi-label">Liquidado</div><div class="nr-kpi-value"><?= trab_nom_audit_num($resumen['liquidado_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-warn"><div class="nr-kpi-label">Parcial</div><div class="nr-kpi-value"><?= trab_nom_audit_num($resumen['parcial_count'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Sin pago</div><div class="nr-kpi-value"><?= trab_nom_audit_num($resumen['sin_pago_count'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Revisar</div><div class="nr-kpi-value" style="color:var(--nom-danger);"><?= trab_nom_audit_num($resumen['revisar_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Pendiente snapshot</div><div class="nr-kpi-value"><?= trab_nom_audit_money($resumen['pendiente_snapshot_total'] ?? 0) ?></div></div>
        </section>

        <div class="nr-panel">
            <div class="nr-panel-head">
                <div>
                    <h2 class="nr-panel-title">Detalle consolidado</h2>
                    <p class="nr-panel-sub">Una fila por detalle de snapshot; los pagos Caja se muestran agregados.</p>
                </div>
                <span class="nr-pill"><i class="fas fa-list-check"></i> <?= trab_nom_audit_num(count($registros)) ?> visible(s)</span>
            </div>

            <?php if (empty($registros)): ?>
                <div class="nr-empty">
                    <div class="nr-empty-icon"><i class="fas fa-clipboard-check"></i></div>
                    <h3>Sin detalles para auditar</h3>
                    <p>Ajusta filtros o crea/aprueba snapshots de pre-nómina antes de revisar pagos.</p>
                </div>
            <?php else: ?>
                <div class="nr-table-wrap">
                    <table class="nr-table">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>Trabajador</th>
                                <th>Estado</th>
                                <th class="nr-r">Snapshot</th>
                                <th class="nr-r">Caja</th>
                                <th class="nr-r">Saldo</th>
                                <th>Trazabilidad</th>
                                <th>Referencias</th>
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
                                        <a class="nr-link" href="<?= $periodoUrl ?>">#<?= (int)($registro['periodo_id'] ?? 0) ?> <?= trab_nom_audit_safe($registro['periodo_etiqueta'] ?? '') ?></a>
                                        <div class="nr-sub-txt"><?= trab_nom_audit_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_nom_audit_date($registro['periodo_fecha_fin'] ?? '') ?></div>
                                        <div style="margin-top:4px;"><span class="<?= trab_nom_audit_badge_class($registro['periodo_estado'] ?? '') ?>"><?= trab_nom_audit_label($registro['periodo_estado'] ?? '') ?></span></div>
                                    </td>
                                    <td>
                                        <div class="nr-strong"><?= trab_nom_audit_safe($registro['trabajador_snapshot_nombre'] ?? '') ?></div>
                                        <div class="nr-sub-txt">ID <?= (int)($registro['trabajador_id'] ?? 0) ?> · <?= trab_nom_audit_safe($registro['trabajador_snapshot_rol'] ?? '') ?></div>
                                        <?php if (trim((string)($registro['trabajador_actual_nombre'] ?? '')) !== ''): ?>
                                            <div class="nr-sub-txt">Actual: <?= trab_nom_audit_safe($registro['trabajador_actual_nombre'] ?? '') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="<?= trab_nom_audit_badge_class($estadoFila) ?>"><i class="fas fa-circle"></i> <?= trab_nom_audit_label($estadoFila) ?></span>
                                        <?php if ((int)($registro['inconsistencias_count'] ?? 0) > 0): ?>
                                            <div class="nr-sub-txt" style="color:var(--nom-danger);font-weight:700;margin-top:6px;"><?= trab_nom_audit_num($registro['inconsistencias_count'] ?? 0) ?> inconsistencia(s)</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong"><?= trab_nom_audit_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Bruto <?= trab_nom_audit_money($registro['bruto_periodo'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Neto <?= trab_nom_audit_money($registro['neto_sugerido'] ?? 0) ?></div>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong" style="color:var(--nom-ok);"><?= trab_nom_audit_money($registro['pagos_caja_total'] ?? 0) ?></div>
                                        <div class="nr-sub-txt"><?= trab_nom_audit_num($registro['pagos_pagados_count'] ?? 0) ?> pago(s), <?= trab_nom_audit_num($registro['pagos_revertidos_count'] ?? 0) ?> rev.</div>
                                        <?php if ((float)($registro['reversiones_total'] ?? 0) > 0): ?>
                                            <div class="nr-sub-txt" style="color:var(--nom-warn);">Revertido <?= trab_nom_audit_money($registro['reversiones_total'] ?? 0) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong"><?= trab_nom_audit_money($registro['saldo_auditoria'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Detalle #<?= (int)($registro['detalle_id'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <div class="nr-strong"><?= trab_nom_audit_safe($registro['cajas'] ?? '') ?></div>
                                        <div class="nr-sub-txt">Último pago #<?= (int)($registro['ultimo_pago_id'] ?? 0) ?> · <?= trab_nom_audit_datetime($registro['ultimo_pago'] ?? '') ?></div>
                                    </td>
                                    <td style="max-width:260px;">
                                        <div class="nr-sub-txt" style="word-break:break-word;"><?= trab_nom_audit_safe($registro['referencias_pago'] ?? '') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($porEstado) || !empty($porPeriodo)): ?>
            <section class="nr-grid2">
                <div class="nr-card">
                    <h2 style="margin-bottom:10px;">Resumen por estado</h2>
                    <div class="nr-stack" style="gap:8px;">
                        <?php foreach ($porEstado as $item): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--nom-border);border-radius:12px;padding:10px 12px;background:#fff;">
                                <span class="<?= trab_nom_audit_badge_class($item['estado'] ?? '') ?>"><?= trab_nom_audit_label($item['estado'] ?? '') ?></span>
                                <div style="text-align:right;">
                                    <div class="nr-strong"><?= trab_nom_audit_num($item['total'] ?? 0) ?> detalle(s)</div>
                                    <div class="nr-sub-txt">Saldo <?= trab_nom_audit_money($item['saldo'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="nr-card">
                    <h2 style="margin-bottom:10px;">Resumen por periodo</h2>
                    <div class="nr-stack" style="gap:8px;">
                        <?php foreach (array_slice($porPeriodo, 0, 8) as $item): ?>
                            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--nom-border);border-radius:12px;padding:10px 12px;background:#fff;">
                                <div>
                                    <div class="nr-strong">#<?= (int)($item['periodo_id'] ?? 0) ?> <?= trab_nom_audit_safe($item['etiqueta'] ?? '') ?></div>
                                    <div class="nr-sub-txt"><?= trab_nom_audit_date($item['fecha_inicio'] ?? '') ?> - <?= trab_nom_audit_date($item['fecha_fin'] ?? '') ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="nr-strong"><?= trab_nom_audit_num($item['total'] ?? 0) ?> detalle(s)</div>
                                    <div class="nr-sub-txt">Caja <?= trab_nom_audit_money($item['pagos_caja'] ?? 0) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
