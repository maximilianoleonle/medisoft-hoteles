<?php
$expediente = is_array($expediente ?? null) ? $expediente : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($expediente['registros'] ?? null) ? $expediente['registros'] : [];
$resumen = is_array($expediente['resumen'] ?? null) ? $expediente['resumen'] : [];
$porEstado = is_array($expediente['por_estado'] ?? null) ? $expediente['por_estado'] : [];
$porPeriodo = is_array($expediente['por_periodo'] ?? null) ? $expediente['por_periodo'] : [];
$bloqueos = is_array($expediente['bloqueos'] ?? null) ? $expediente['bloqueos'] : [];
$filtros = is_array($expediente['filtros_normalizados'] ?? null) ? $expediente['filtros_normalizados'] : [];
$trabajadoresFiltro = is_array($trabajadoresFiltro ?? null) ? $trabajadoresFiltro : [];

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
            'listo_revision' => 'Listo para revisión',
            'con_pendientes' => 'Con pendientes',
            'requiere_correccion' => 'Requiere corrección',
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
        if ($estado === 'listo_revision' || $estado === 'liquidado' || $estado === 'aprobado') {
            return 'nr-badge ok';
        }
        if ($estado === 'con_pendientes' || $estado === 'parcial' || $estado === 'cerrado') {
            return 'nr-badge warn';
        }
        if ($estado === 'requiere_correccion' || $estado === 'bloqueado' || $estado === 'anulado' || $estado === 'revisar') {
            return 'nr-badge danger';
        }
        return 'nr-badge neutral';
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

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Expediente</p>
            <h1 class="nom-title">Expediente administrativo de nómina</h1>
            <p class="nr-sub">Lectura interna de snapshots, auditoría, pagos Caja, reversiones y bloqueos. No modifica Caja, no modifica snapshots y no genera nómina oficial.</p>
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
            <div class="nr-kpi"><div class="nr-kpi-label">Detalles</div><div class="nr-kpi-value"><?= trab_nom_exp_num($resumen['total_registros'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Trabajadores</div><div class="nr-kpi-value"><?= trab_nom_exp_num($resumen['trabajadores_total'] ?? 0) ?></div></div>
            <div class="nr-kpi is-warn"><div class="nr-kpi-label">Bloqueos</div><div class="nr-kpi-value"><?= trab_nom_exp_num($resumen['bloqueos_total'] ?? 0) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Saldo auditoría</div><div class="nr-kpi-value"><?= trab_nom_exp_money($resumen['saldo_auditoria_total'] ?? 0) ?></div></div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <div class="nr-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Expediente no disponible.</strong>
                    <p>Requiere snapshots, pagos Caja y trazabilidad de pre-nómina. No se ejecuta ninguna escritura.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php include APP_PATH . '/views/partials/filtros.php'; ?>
        <form class="msf-bar" method="GET" action="<?= url('trabajadores/nomina/expediente') ?>">
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
                <input class="msf-control" type="date" name="fecha_inicio" value="<?= trab_nom_exp_safe($fechaInicio, '') ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Hasta</span>
                <input class="msf-control" type="date" name="fecha_fin" value="<?= trab_nom_exp_safe($fechaFin, '') ?>">
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Snapshot</span>
                <select class="msf-control" name="estado_snapshot"><?= msf_options(['todos' => 'Todos', 'cerrado' => 'Cerrado', 'aprobado' => 'Aprobado', 'anulado' => 'Anulado'], $estadoSnapshot) ?></select>
            </label>
            <label class="msf-field msf-field--sm">
                <span class="msf-label">Expediente</span>
                <select class="msf-control" name="estado_expediente"><?= msf_options(['todos' => 'Todos', 'listo_revision' => 'Listo revisión', 'con_pendientes' => 'Con pendientes', 'requiere_correccion' => 'Requiere corrección', 'bloqueado' => 'Bloqueado', 'anulado' => 'Anulado'], $estadoExpediente) ?></select>
            </label>
            <label class="msf-field msf-field--grow">
                <span class="msf-label">Buscar</span>
                <input class="msf-control" type="search" name="buscar" value="<?= trab_nom_exp_safe($buscar, '') ?>" placeholder="Ref o caja">
            </label>
            <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin">
                <span class="msf-ranges-label">Rango</span>
                <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
            </div>
            <div class="msf-actions">
                <button class="msf-btn msf-btn--primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                <a class="msf-btn" href="<?= url('trabajadores/nomina/expediente') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
            </div>
        </form>

        <section class="nr-kpis cols-5">
            <div class="nr-kpi is-ok"><div class="nr-kpi-label">Listo revisión</div><div class="nr-kpi-value"><?= trab_nom_exp_num($resumen['listo_revision_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-warn"><div class="nr-kpi-label">Con pendientes</div><div class="nr-kpi-value"><?= trab_nom_exp_num($resumen['con_pendientes_count'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Requiere corrección</div><div class="nr-kpi-value" style="color:var(--nom-danger);"><?= trab_nom_exp_num($resumen['requiere_correccion_count'] ?? 0) ?></div></div>
            <div class="nr-kpi"><div class="nr-kpi-label">Bloqueado / anulado</div><div class="nr-kpi-value"><?= trab_nom_exp_num(($resumen['bloqueado_count'] ?? 0) + ($resumen['anulado_count'] ?? 0)) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Pagos Caja</div><div class="nr-kpi-value"><?= trab_nom_exp_money($resumen['pagos_caja_total'] ?? 0) ?></div></div>
        </section>

        <div class="nr-panel">
            <div class="nr-panel-head">
                <div>
                    <h2 class="nr-panel-title">Detalle del expediente</h2>
                    <p class="nr-panel-sub">Una fila por detalle de snapshot; los bloqueos son administrativos e informativos.</p>
                </div>
                <span class="nr-pill"><i class="fas fa-list-check"></i> <?= trab_nom_exp_num(count($registros)) ?> visible(s)</span>
            </div>

            <?php if (empty($registros)): ?>
                <div class="nr-empty">
                    <div class="nr-empty-icon"><i class="fas fa-folder-open"></i></div>
                    <h3>Sin expedientes para revisar</h3>
                    <p>Ajusta filtros o revisa snapshots aprobados antes de preparar expediente administrativo.</p>
                </div>
            <?php else: ?>
                <div class="nr-table-wrap">
                    <table class="nr-table">
                        <thead>
                            <tr>
                                <th>Periodo</th>
                                <th>Trabajador</th>
                                <th>Expediente</th>
                                <th class="nr-r">Snapshot</th>
                                <th class="nr-r">Caja</th>
                                <th class="nr-r">Saldo</th>
                                <th>Bloqueos</th>
                                <th>Evidencia</th>
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
                                        <a class="nr-link" href="<?= $periodoUrl ?>">#<?= (int)($registro['periodo_id'] ?? 0) ?> <?= trab_nom_exp_safe($registro['periodo_etiqueta'] ?? '') ?></a>
                                        <div class="nr-sub-txt"><?= trab_nom_exp_date($registro['periodo_fecha_inicio'] ?? '') ?> - <?= trab_nom_exp_date($registro['periodo_fecha_fin'] ?? '') ?></div>
                                        <div style="margin-top:4px;"><span class="<?= trab_nom_exp_badge($registro['periodo_estado'] ?? '') ?>"><?= trab_nom_exp_label($registro['periodo_estado'] ?? '') ?></span></div>
                                    </td>
                                    <td>
                                        <div class="nr-strong"><?= trab_nom_exp_safe($registro['trabajador_snapshot_nombre'] ?? '') ?></div>
                                        <div class="nr-sub-txt">ID <?= (int)($registro['trabajador_id'] ?? 0) ?> · <?= trab_nom_exp_safe($registro['trabajador_snapshot_rol'] ?? '') ?></div>
                                        <div class="nr-sub-txt">Actual: <?= trab_nom_exp_safe($registro['trabajador_actual_nombre'] ?? '') ?></div>
                                    </td>
                                    <td>
                                        <span class="<?= trab_nom_exp_badge($estadoFila) ?>"><i class="fas fa-circle"></i> <?= trab_nom_exp_label($estadoFila) ?></span>
                                        <div class="nr-sub-txt" style="margin-top:6px;">Auditoría: <?= trab_nom_exp_label($registro['auditoria_estado'] ?? '') ?></div>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong"><?= trab_nom_exp_money($registro['pendiente_pago_sugerido'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Bruto <?= trab_nom_exp_money($registro['bruto_periodo'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Neto <?= trab_nom_exp_money($registro['neto_sugerido'] ?? 0) ?></div>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong" style="color:var(--nom-ok);"><?= trab_nom_exp_money($registro['pagos_caja_total'] ?? 0) ?></div>
                                        <div class="nr-sub-txt"><?= trab_nom_exp_num($registro['pagos_pagados_count'] ?? 0) ?> pago(s), <?= trab_nom_exp_num($registro['pagos_revertidos_count'] ?? 0) ?> rev.</div>
                                    </td>
                                    <td class="nr-r">
                                        <div class="nr-strong"><?= trab_nom_exp_money($registro['saldo_auditoria'] ?? 0) ?></div>
                                        <div class="nr-sub-txt">Detalle #<?= (int)($registro['detalle_id'] ?? 0) ?></div>
                                    </td>
                                    <td style="max-width:260px;">
                                        <?php if (empty($bloqueosFila)): ?>
                                            <span class="nr-badge ok"><i class="fas fa-check"></i> Sin bloqueos críticos</span>
                                        <?php else: ?>
                                            <div class="nr-stack" style="gap:4px;">
                                                <?php foreach ($bloqueosFila as $bloqueo): ?>
                                                    <div class="nr-sub-txt" style="color:var(--nom-danger);font-weight:700;"><?= trab_nom_exp_safe($bloqueo) ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width:260px;">
                                        <div class="nr-strong"><?= trab_nom_exp_safe($registro['cajas'] ?? '') ?></div>
                                        <div class="nr-sub-txt">Último pago #<?= (int)($registro['ultimo_pago_id'] ?? 0) ?> · <?= trab_nom_exp_datetime($registro['ultimo_pago'] ?? '') ?></div>
                                        <div class="nr-sub-txt" style="word-break:break-word;"><?= trab_nom_exp_safe($registro['referencias_pago'] ?? '') ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <section class="nr-grid3">
            <div class="nr-card">
                <h2 style="margin-bottom:10px;">Resumen por estado</h2>
                <div class="nr-stack" style="gap:8px;">
                    <?php foreach ($porEstado as $item): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--nom-border);border-radius:12px;padding:10px 12px;background:#fff;">
                            <span class="<?= trab_nom_exp_badge($item['estado'] ?? '') ?>"><?= trab_nom_exp_label($item['estado'] ?? '') ?></span>
                            <div style="text-align:right;">
                                <div class="nr-strong"><?= trab_nom_exp_num($item['total'] ?? 0) ?> detalle(s)</div>
                                <div class="nr-sub-txt">Saldo <?= trab_nom_exp_money($item['saldo'] ?? 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($porEstado)): ?>
                        <div class="nr-sub-txt">Sin estados visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nr-card">
                <h2 style="margin-bottom:10px;">Bloqueos detectados</h2>
                <div class="nr-stack" style="gap:8px;">
                    <?php foreach ($bloqueos as $item): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--nom-border);border-radius:12px;padding:10px 12px;background:#fff;">
                            <span class="nr-strong"><?= trab_nom_exp_safe($item['bloqueo'] ?? '') ?></span>
                            <span class="nr-badge neutral"><?= trab_nom_exp_num($item['total'] ?? 0) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($bloqueos)): ?>
                        <div class="nr-sub-txt">Sin bloqueos visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nr-card">
                <h2 style="margin-bottom:10px;">Resumen por periodo</h2>
                <div class="nr-stack" style="gap:8px;">
                    <?php foreach (array_slice($porPeriodo, 0, 8) as $item): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--nom-border);border-radius:12px;padding:10px 12px;background:#fff;">
                            <div>
                                <div class="nr-strong">#<?= (int)($item['periodo_id'] ?? 0) ?> <?= trab_nom_exp_safe($item['etiqueta'] ?? '') ?></div>
                                <div class="nr-sub-txt"><?= trab_nom_exp_date($item['fecha_inicio'] ?? '') ?> - <?= trab_nom_exp_date($item['fecha_fin'] ?? '') ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div class="nr-strong"><?= trab_nom_exp_num($item['total'] ?? 0) ?> detalle(s)</div>
                                <div class="nr-sub-txt">Bloq. <?= trab_nom_exp_num($item['bloqueos'] ?? 0) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($porPeriodo)): ?>
                        <div class="nr-sub-txt">Sin periodos visibles.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>
</div>
