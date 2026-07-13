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
            return 'nr-badge ok';
        }
        if ($estado === 'anulado') {
            return 'nr-badge danger';
        }
        return 'nr-badge warn';
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

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Informes</p>
            <h1 class="nom-title">Snapshots de pre-nómina</h1>
            <p class="nr-sub">Historial de solo lectura de periodos cerrados, aprobados o anulados. Consulta importes congelados del snapshot; no recalcula nómina, no registra pagos y no modifica Caja.</p>
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
            <div class="nr-kpi"><div class="nr-kpi-label">Snapshots</div><div class="nr-kpi-value"><?= trab_nomina_report_num($resumen['total_registros'] ?? 0) ?></div></div>
            <div class="nr-kpi is-ok"><div class="nr-kpi-label">Aprobados</div><div class="nr-kpi-value"><?= trab_nomina_report_num($resumen['aprobados_count'] ?? 0) ?></div></div>
            <div class="nr-kpi is-accent"><div class="nr-kpi-label">Neto sugerido</div><div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['neto_sugerido_total'] ?? 0) ?></div></div>
            <div class="nr-kpi is-warn"><div class="nr-kpi-label">Pendiente</div><div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['pendiente_pago_total'] ?? 0) ?></div></div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <div class="nr-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Reporte no disponible.</strong>
                    <p>Faltan tablas de snapshots de pre-nómina para consultar el historial.</p>
                </div>
            </div>
        <?php else: ?>
            <?php include APP_PATH . '/views/partials/filtros.php'; ?>
            <form method="GET" action="<?= url('trabajadores/nomina/periodos/reporte') ?>" class="msf-bar">
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Desde</span>
                    <input class="msf-control" type="date" name="fecha_inicio" value="<?= trab_nomina_report_safe($fechaInicio, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Hasta</span>
                    <input class="msf-control" type="date" name="fecha_fin" value="<?= trab_nomina_report_safe($fechaFin, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Estado</span>
                    <select class="msf-control" name="estado"><?= msf_options(['todos' => 'Todos', 'cerrado' => 'Cerrado', 'aprobado' => 'Aprobado', 'anulado' => 'Anulado'], $estado) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Tipo de periodo</span>
                    <select class="msf-control" name="tipo_periodo"><?= msf_options(['todos' => 'Todos los tipos', 'semanal' => 'Semanal', 'quincenal' => 'Quincenal', 'mensual' => 'Mensual', 'manual' => 'Manual'], $tipoPeriodo) ?></select>
                </label>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Buscar</span>
                    <input class="msf-control" type="search" name="buscar" value="<?= trab_nomina_report_safe($buscar, '') ?>" placeholder="Folio, etiqueta, usuario o motivo">
                </label>
                <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin">
                    <span class="msf-ranges-label">Rango</span>
                    <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                    <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
                </div>
                <div class="msf-actions">
                    <button class="msf-btn msf-btn--primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                    <a class="msf-btn" href="<?= url('trabajadores/nomina/periodos/reporte') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
                </div>
            </form>

            <div class="nr-grid4">
                <div class="nr-kpi is-accent">
                    <div class="nr-kpi-label">Bruto congelado</div>
                    <div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['bruto_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot"><?= trab_nomina_report_num($resumen['trabajadores_total'] ?? 0) ?> trabajador(es) acumulados</div>
                </div>
                <div class="nr-kpi">
                    <div class="nr-kpi-label">Deducciones</div>
                    <div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['deducciones_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot">Anticipos y préstamos informativos del snapshot</div>
                </div>
                <div class="nr-kpi">
                    <div class="nr-kpi-label">Pagos Caja aplicados</div>
                    <div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot">Histórico congelado; no mueve Caja</div>
                </div>
                <div class="nr-kpi">
                    <div class="nr-kpi-label">Reversiones detectadas</div>
                    <div class="nr-kpi-value"><?= trab_nomina_report_money($resumen['reversiones_detectadas_total'] ?? 0) ?></div>
                    <div class="nr-kpi-foot">Dato informativo del snapshot</div>
                </div>
            </div>

            <div class="nr-panel">
                <div class="nr-panel-head"><h2 class="nr-panel-title">Resumen por estado</h2></div>
                <div class="nr-table-wrap">
                    <table class="nr-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="nr-r">Snapshots</th>
                                <th class="nr-r">Trabajadores</th>
                                <th class="nr-r">Neto sugerido</th>
                                <th class="nr-r">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($porEstado)): ?>
                                <tr><td colspan="5" class="nr-empty-cell">Sin snapshots para resumir.</td></tr>
                            <?php else: foreach ($porEstado as $item): ?>
                                <tr>
                                    <td><span class="<?= trab_nomina_report_badge_class($item['estado'] ?? '') ?>"><?= trab_nomina_report_label($item['estado'] ?? '') ?></span></td>
                                    <td class="nr-r"><?= trab_nomina_report_num($item['total'] ?? 0) ?></td>
                                    <td class="nr-r"><?= trab_nomina_report_num($item['trabajadores_total'] ?? 0) ?></td>
                                    <td class="nr-r"><?= trab_nomina_report_money($item['neto_sugerido_total'] ?? 0) ?></td>
                                    <td class="nr-r"><?= trab_nomina_report_money($item['pendiente_pago_total'] ?? 0) ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="nr-panel">
                <div class="nr-panel-head">
                    <div>
                        <h2 class="nr-panel-title">Snapshots persistentes</h2>
                        <p class="nr-panel-sub">Listado limitado a los registros filtrados del hotel actual.</p>
                    </div>
                    <span class="nr-pill"><i class="fas fa-list-check"></i> <?= trab_nomina_report_num(count($registros)) ?> visible(s)</span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="nr-empty">
                        <div class="nr-empty-icon"><i class="fas fa-box-archive"></i></div>
                        <h3>Sin snapshots de pre-nómina</h3>
                        <p>Ajusta los filtros o cierra un periodo desde la pantalla de periodos cuando corresponda.</p>
                    </div>
                <?php else: ?>
                    <div class="nr-table-wrap">
                        <table class="nr-table">
                            <thead>
                                <tr>
                                    <th>Periodo</th>
                                    <th>Estado</th>
                                    <th class="nr-r">Trabajadores</th>
                                    <th class="nr-r">Importes</th>
                                    <th>Responsables</th>
                                    <th>Motivo</th>
                                    <th class="nr-r">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <tr>
                                        <td>
                                            <div class="nr-strong"><?= trab_nomina_report_safe($registro['etiqueta'] ?? 'Snapshot') ?></div>
                                            <div class="nr-sub-txt">#<?= (int)($registro['id'] ?? 0) ?> / <?= trab_nomina_report_safe($registro['tipo_periodo'] ?? '') ?></div>
                                            <div class="nr-sub-txt"><?= trab_nomina_report_date($registro['fecha_inicio'] ?? '') ?> - <?= trab_nomina_report_date($registro['fecha_fin'] ?? '') ?></div>
                                        </td>
                                        <td>
                                            <span class="<?= trab_nomina_report_badge_class($registro['estado'] ?? '') ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= trab_nomina_report_label($registro['estado'] ?? '') ?>
                                            </span>
                                        </td>
                                        <td class="nr-r nr-strong"><?= trab_nomina_report_num($registro['trabajadores_total'] ?? 0) ?></td>
                                        <td class="nr-r">
                                            <div><span class="nr-sub-txt">Bruto:</span> <strong><?= trab_nomina_report_money($registro['bruto_total'] ?? 0) ?></strong></div>
                                            <div><span class="nr-sub-txt">Neto:</span> <strong><?= trab_nomina_report_money($registro['neto_sugerido_total'] ?? 0) ?></strong></div>
                                            <div><span class="nr-sub-txt">Pendiente:</span> <strong><?= trab_nomina_report_money($registro['pendiente_pago_total'] ?? 0) ?></strong></div>
                                        </td>
                                        <td>
                                            <div class="nr-sub-txt">Cierre</div>
                                            <div class="nr-strong"><?= trab_nomina_report_user($registro, 'cerrado_por') ?></div>
                                            <div class="nr-sub-txt"><?= trab_nomina_report_datetime($registro['cerrado_at'] ?? null) ?></div>
                                            <?php if (!empty($registro['aprobado_at'])): ?>
                                                <div class="nr-sub-txt" style="margin-top:6px;">Aprobación</div>
                                                <div class="nr-strong"><?= trab_nomina_report_user($registro, 'aprobado_por') ?></div>
                                                <div class="nr-sub-txt"><?= trab_nomina_report_datetime($registro['aprobado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($registro['anulado_at'])): ?>
                                                <div class="nr-sub-txt" style="margin-top:6px;">Anulación</div>
                                                <div class="nr-strong"><?= trab_nomina_report_user($registro, 'anulado_por') ?></div>
                                                <div class="nr-sub-txt"><?= trab_nomina_report_datetime($registro['anulado_at'] ?? null) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width:260px;">
                                            <div class="nr-sub-txt" style="font-size:12.5px;"><?= trab_nomina_report_safe($registro['motivo_anulacion'] ?? '', 'Sin motivo') ?></div>
                                        </td>
                                        <td class="nr-r">
                                            <a class="nr-act" href="<?= url('trabajadores/nomina/periodos/' . (int)($registro['id'] ?? 0)) ?>"><i class="fas fa-eye"></i> Ver snapshot</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
