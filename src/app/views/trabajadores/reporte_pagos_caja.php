<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$porMetodo = is_array($reporte['por_metodo'] ?? null) ? $reporte['por_metodo'] : [];
$porCorte = is_array($reporte['por_corte'] ?? null) ? $reporte['por_corte'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];
$trabajadoresFiltro = is_array($trabajadoresFiltro ?? null) ? $trabajadoresFiltro : [];

if (!function_exists('trab_cash_report_safe')) {
    function trab_cash_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_cash_report_money')) {
    function trab_cash_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_cash_report_num')) {
    function trab_cash_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_cash_report_datetime')) {
    function trab_cash_report_datetime($value)
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

$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$metodoPago = (string)($filtros['metodo_pago'] ?? 'todos');
$corteId = (int)($filtros['corte_id'] ?? 0);
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
$exportParams = [
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : null,
    'buscar' => $buscar !== '' ? $buscar : null,
    'estado' => $estado !== '' ? $estado : 'todos',
    'metodo_pago' => $metodoPago !== '' ? $metodoPago : 'todos',
    'corte_id' => $corteId > 0 ? $corteId : null,
    'fecha_inicio' => $fechaInicio !== '' ? $fechaInicio : null,
    'fecha_fin' => $fechaFin !== '' ? $fechaFin : null,
];
$exportParams = array_filter($exportParams, static function ($value) {
    return $value !== null && trim((string)$value) !== '';
});
$exportQuery = http_build_query($exportParams);
$exportUrl = url('trabajadores/pagos-caja/reporte/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Pagos</p>
            <h1 class="nom-title">Pagos en Caja</h1>
            <p class="nr-sub">Todos los pagos que le has hecho a tu personal desde Caja, con su corte, referencia y estado. Es solo para consultar.</p>
        </div>
        <div class="nr-toolbar">
            <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="nr-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Personal</a>
            <?php if ($tablaDisponible && (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones'))): ?>
            <a class="nr-btn" href="<?= $exportUrl ?>"><i class="fas fa-file-csv"></i> Exportar CSV</a>
            <?php endif; ?>
            <span class="nr-pill"><i class="fas fa-eye"></i> Solo consulta</span>
        </div>
    </div>

    <div style="margin:14px 0 16px;">
        <?php $subnav_section = 'personal'; $subnav_active = 'pagos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
    </div>

    <div class="nr-tabs-block" style="margin-bottom:16px;">
        <span class="nr-scope">Dentro de Pagos</span>
        <nav class="nr-tabs" aria-label="Vistas de pagos">
            <span class="nr-tab is-active" aria-current="page"><i class="fas fa-file-invoice-dollar"></i> Historial</span>
            <a class="nr-tab" href="<?= url('trabajadores/pagos-caja/simulador') ?>"><i class="fas fa-cash-register"></i> Simular pago</a>
        </nav>
    </div>

    <?php if (!$tablaDisponible): ?>
        <div class="nr-notice">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Esta sección todavía no está activada.</strong>
                <p>Faltan datos de personal o de Caja para consultar los pagos laborales.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="nr-stack">
            <?php include APP_PATH . '/views/partials/filtros.php'; ?>
            <form method="GET" action="<?= url('trabajadores/pagos-caja/reporte') ?>" class="msf-bar" data-auto-filter-form>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Trabajador</span>
                    <select class="msf-control" name="trabajador_id"><?= msf_worker_options($trabajadoresFiltro, $trabajadorId) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Estado</span>
                    <select class="msf-control" name="estado"><?= msf_options(['todos' => 'Todos', 'pagado' => 'Pagado', 'revertido' => 'Revertido'], $estado) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Método</span>
                    <select class="msf-control" name="metodo_pago"><?= msf_options(['todos' => 'Todos los métodos', 'efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'], $metodoPago) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Corte</span>
                    <input class="msf-control" type="number" min="1" name="corte_id" value="<?= $corteId > 0 ? (int)$corteId : '' ?>" placeholder="Todos">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Desde</span>
                    <input class="msf-control" type="date" name="fecha_inicio" value="<?= trab_cash_report_safe($fechaInicio, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Hasta</span>
                    <input class="msf-control" type="date" name="fecha_fin" value="<?= trab_cash_report_safe($fechaFin, '') ?>">
                </label>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Buscar</span>
                    <input class="msf-control" type="search" name="buscar" value="<?= trab_cash_report_safe($buscar, '') ?>" placeholder="Referencia o caja">
                </label>
                <div class="msf-ranges" data-msf-from="fecha_inicio" data-msf-to="fecha_fin">
                    <span class="msf-ranges-label">Rango</span>
                    <button type="button" class="msf-chip" data-msf-range="hoy">Hoy</button>
                    <button type="button" class="msf-chip" data-msf-range="7d">7 días</button>
                    <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                    <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
                </div>
                <div class="msf-actions">
                    <a class="msf-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
                </div>
            </form>

            <section class="nr-kpis">
                <div class="nr-kpi is-ok"><div class="nr-kpi-label">Pagado (vigente)</div><div class="nr-kpi-value"><?= trab_cash_report_money($resumen['pagado_vigente_total'] ?? 0) ?></div><div class="nr-kpi-foot"><?= trab_cash_report_num($resumen['pagados_count'] ?? 0) ?> pago(s) aplicados</div></div>
                <div class="nr-kpi"><div class="nr-kpi-label">Total emitido</div><div class="nr-kpi-value"><?= trab_cash_report_money($resumen['egreso_original_total'] ?? 0) ?></div><div class="nr-kpi-foot">Suma histórica</div></div>
                <div class="nr-kpi is-warn"><div class="nr-kpi-label">Revertido</div><div class="nr-kpi-value"><?= trab_cash_report_money($resumen['revertido_total'] ?? 0) ?></div><div class="nr-kpi-foot"><?= trab_cash_report_num($resumen['revertidos_count'] ?? 0) ?> registro(s)</div></div>
                <div class="nr-kpi is-accent"><div class="nr-kpi-label">Devuelto a Caja</div><div class="nr-kpi-value"><?= trab_cash_report_money($resumen['reversion_caja_total'] ?? 0) ?></div><div class="nr-kpi-foot">Por reversiones</div></div>
            </section>

            <div class="nr-grid3">
                <div class="nr-panel">
                    <div class="nr-panel-head"><h2 class="nr-panel-title">Por método</h2></div>
                    <div class="nr-table-wrap">
                        <table class="nr-table">
                            <thead><tr><th>Método</th><th class="nr-r">Pagado</th><th class="nr-r">Revertido</th><th class="nr-r">Devuelto</th></tr></thead>
                            <tbody>
                                <?php if (empty($porMetodo)): ?>
                                    <tr><td colspan="4" class="nr-empty-cell">Sin movimientos.</td></tr>
                                <?php else: foreach ($porMetodo as $item): ?>
                                    <tr>
                                        <td class="nr-strong nr-cap"><?= trab_cash_report_safe($item['metodo_pago'] ?? null) ?></td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['reversion_caja'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="nr-panel">
                    <div class="nr-panel-head"><h2 class="nr-panel-title">Por estado</h2></div>
                    <div class="nr-table-wrap">
                        <table class="nr-table">
                            <thead><tr><th>Estado</th><th class="nr-r">Registros</th><th class="nr-r">Monto</th></tr></thead>
                            <tbody>
                                <?php if (empty($porEstado)): ?>
                                    <tr><td colspan="3" class="nr-empty-cell">Sin estados.</td></tr>
                                <?php else: foreach ($porEstado as $item): ?>
                                    <tr>
                                        <td><span class="nr-badge <?= ($item['estado'] ?? '') === 'pagado' ? 'ok' : 'warn' ?> nr-cap"><?= trab_cash_report_safe($item['estado'] ?? null) ?></span></td>
                                        <td class="nr-r"><?= trab_cash_report_num($item['total'] ?? 0) ?></td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['monto'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="nr-panel">
                    <div class="nr-panel-head"><h2 class="nr-panel-title">Por corte</h2></div>
                    <div class="nr-table-wrap" style="max-height:260px">
                        <table class="nr-table">
                            <thead><tr><th>Corte</th><th class="nr-r">Pagado</th><th class="nr-r">Revertido</th></tr></thead>
                            <tbody>
                                <?php if (empty($porCorte)): ?>
                                    <tr><td colspan="3" class="nr-empty-cell">Sin cortes.</td></tr>
                                <?php else: foreach ($porCorte as $item): ?>
                                    <tr>
                                        <td>
                                            <a class="nr-link" href="<?= url('caja/corte/' . (int)($item['corte_id'] ?? 0)) ?>">#<?= (int)($item['corte_id'] ?? 0) ?></a>
                                            <div class="nr-sub-txt"><?= trab_cash_report_safe($item['caja_nombre'] ?? null) ?> &middot; <?= trab_cash_report_safe($item['corte_estado'] ?? null) ?></div>
                                        </td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                        <td class="nr-r"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="nr-panel">
                <div class="nr-panel-head">
                    <div>
                        <h2 class="nr-panel-title">Pagos registrados</h2>
                        <p class="nr-panel-sub">Pagos laborales con Caja del hotel actual, según tus filtros.</p>
                    </div>
                    <span class="nr-pill"><i class="fas fa-list-check"></i> <?= trab_cash_report_num(count($registros)) ?></span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="nr-empty">
                        <div class="nr-empty-icon"><i class="fas fa-receipt"></i></div>
                        <h3>Sin pagos laborales con Caja</h3>
                        <p>Ajusta los filtros o registra pagos desde la ficha del trabajador.</p>
                    </div>
                <?php else: ?>
                    <div class="nr-table-wrap">
                        <table class="nr-table">
                            <thead>
                                <tr><th>Fecha</th><th>Trabajador</th><th class="nr-r">Monto</th><th>Caja / corte</th><th>Referencia</th><th>Estado</th><th>Reversión</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <?php
                                        $estadoRegistro = (string)($registro['estado'] ?? '');
                                        $creadoPor = trim((string)($registro['creado_por_nombre'] ?? ''));
                                        if ($creadoPor === '') { $creadoPor = trim((string)($registro['creado_por_login'] ?? '')); }
                                        $actualizadoPor = trim((string)($registro['actualizado_por_nombre'] ?? ''));
                                        if ($actualizadoPor === '') { $actualizadoPor = trim((string)($registro['actualizado_por_login'] ?? '')); }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="nr-strong"><?= trab_cash_report_datetime($registro['fecha_pago'] ?? null) ?></div>
                                            <div class="nr-sub-txt">Registro #<?= (int)($registro['id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <a class="nr-link" href="<?= url('trabajadores/' . (int)($registro['trabajador_id'] ?? 0)) ?>"><?= trab_cash_report_safe($registro['trabajador_nombre'] ?? null) ?></a>
                                            <div class="nr-sub-txt"><?= trab_cash_report_safe($registro['trabajador_rol'] ?? null, 'Sin rol') ?></div>
                                        </td>
                                        <td class="nr-r">
                                            <div class="nr-strong"><?= trab_cash_report_money($registro['monto'] ?? 0) ?></div>
                                            <div class="nr-sub-txt nr-cap"><?= trab_cash_report_safe($registro['metodo_pago'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <div class="nr-strong"><?= trab_cash_report_safe($registro['caja_nombre'] ?? null) ?></div>
                                            <a class="nr-link nr-sub-txt" href="<?= url('caja/corte/' . (int)($registro['corte_id'] ?? 0)) ?>">Corte #<?= (int)($registro['corte_id'] ?? 0) ?></a>
                                        </td>
                                        <td>
                                            <div class="nr-strong"><?= trab_cash_report_safe($registro['referencia'] ?? null) ?></div>
                                            <div class="nr-sub-txt">Mov. Caja #<?= (int)($registro['movimiento_caja_id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <span class="nr-badge <?= $estadoRegistro === 'pagado' ? 'ok' : 'warn' ?>">
                                                <i class="fas <?= $estadoRegistro === 'pagado' ? 'fa-circle-check' : 'fa-rotate-left' ?>"></i>
                                                <span class="nr-cap"><?= trab_cash_report_safe($estadoRegistro) ?></span>
                                            </span>
                                            <div class="nr-sub-txt" style="margin-top:6px;"><?= $creadoPor !== '' ? 'Por ' . trab_cash_report_safe($creadoPor) : 'Usuario no disponible' ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($registro['movimiento_reversion_id'])): ?>
                                                <div class="nr-strong">Mov. Caja #<?= (int)$registro['movimiento_reversion_id'] ?></div>
                                                <div class="nr-sub-txt"><?= trab_cash_report_datetime($registro['movimiento_reversion_created_at'] ?? null) ?></div>
                                                <div class="nr-sub-txt"><?= $actualizadoPor !== '' ? 'Por ' . trab_cash_report_safe($actualizadoPor) : 'Usuario no disponible' ?></div>
                                            <?php elseif ($estadoRegistro === 'revertido'): ?>
                                                <span class="nr-badge warn"><i class="fas fa-triangle-exclamation"></i> Sin ingreso vinculado</span>
                                            <?php else: ?>
                                                <span class="nr-badge neutral"><i class="fas fa-lock"></i> Sin reversión</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (trim((string)($registro['notas'] ?? '')) !== ''): ?>
                                        <tr><td colspan="7" class="nr-sub-txt"><strong style="color:var(--nom-text)">Notas:</strong> <?= trab_cash_report_safe($registro['notas'] ?? null) ?></td></tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
