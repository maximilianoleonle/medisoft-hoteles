<?php
$trabajadores = $trabajadores ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$corte = $corte ?? null;
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('trab_cash_safe')) {
    function trab_cash_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_cash_money')) {
    function trab_cash_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_cash_date')) {
    function trab_cash_date($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return $fallback;
        }

        return htmlspecialchars(substr($text, 0, 10), ENT_QUOTES, 'UTF-8');
    }
}

$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$periodoInicio = (string)($filtros['periodo_inicio'] ?? '');
$periodoFin = (string)($filtros['periodo_fin'] ?? '');
$metodoPago = (string)($filtros['metodo_pago'] ?? 'efectivo');
$monto = (string)($filtros['monto'] ?? '');
$referencia = (string)($filtros['referencia'] ?? '');

include APP_PATH . '/views/partials/nomina_report_ui.php';
?>

<div class="nom-report">
    <div class="nr-head">
        <div>
            <p class="nom-kicker">Personal · Pagos</p>
            <h1 class="nom-title">Simulador de pagos</h1>
            <p class="nr-sub">A qué trabajadores podrías pagarles ahora con Caja, según su saldo y el corte abierto. Solo consulta: no registra pagos ni cambia saldos.</p>
        </div>
        <div class="nr-toolbar">
            <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="nr-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Personal</a>
            <a class="nr-btn" href="<?= url('caja') ?>"><i class="fas fa-cash-register"></i> Abrir Caja</a>
            <span class="nr-pill"><i class="fas fa-eye"></i> Solo consulta</span>
        </div>
    </div>

    <div style="margin:14px 0 16px;">
        <?php $subnav_section = 'personal'; $subnav_active = 'pagos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
    </div>

    <div class="nr-tabs-block" style="margin-bottom:16px;">
        <span class="nr-scope">Dentro de Pagos</span>
        <nav class="nr-tabs" aria-label="Vistas de pagos">
            <a class="nr-tab" href="<?= url('trabajadores/pagos-caja/reporte') ?>"><i class="fas fa-file-invoice-dollar"></i> Historial</a>
            <span class="nr-tab is-active" aria-current="page"><i class="fas fa-cash-register"></i> Simular pago</span>
        </nav>
    </div>

    <?php if (!$tablaDisponible): ?>
        <div class="nr-notice">
            <i class="fas fa-circle-info"></i>
            <div>
                <strong>Esta sección todavía no está activada.</strong>
                <p>Faltan datos de personal, pagos laborales o de Caja para poder evaluar los pagos.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="nr-stack">
            <section class="nr-kpis">
                <div class="nr-kpi"><div class="nr-kpi-label">Trabajadores</div><div class="nr-kpi-value"><?= (int)($resumen['total'] ?? 0) ?></div></div>
                <div class="nr-kpi is-ok"><div class="nr-kpi-label">Se les puede pagar</div><div class="nr-kpi-value"><?= (int)($resumen['elegibles'] ?? 0) ?></div></div>
                <div class="nr-kpi is-accent"><div class="nr-kpi-label">Monto simulado</div><div class="nr-kpi-value"><?= trab_cash_money($resumen['monto_simulado_total'] ?? 0) ?></div></div>
                <div class="nr-kpi <?= !empty($corte) ? 'is-ok' : 'is-warn' ?>"><div class="nr-kpi-label">Corte abierto</div><div class="nr-kpi-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></div></div>
            </section>

            <div class="nr-card">
                <div class="nr-grid4">
                    <div><div class="nr-meta-label">Corte</div><div class="nr-meta-value"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div></div>
                    <div><div class="nr-meta-label">Caja</div><div class="nr-meta-value"><?= trab_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div><div class="nr-meta-sub"><?= trab_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicación') ?></div></div>
                    <div><div class="nr-meta-label">Apertura</div><div class="nr-meta-value"><?= trab_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div></div>
                    <div>
                        <div class="nr-meta-label">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="nr-badge ok" style="margin-top:4px;"><i class="fas fa-circle-check"></i> Abierto</span>
                        <?php else: ?>
                            <span class="nr-badge warn" style="margin-top:4px;"><i class="fas fa-triangle-exclamation"></i> Bloquea pagos</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php include APP_PATH . '/views/partials/filtros.php'; ?>
            <form method="GET" action="<?= url('trabajadores/pagos-caja/simulador') ?>" class="msf-bar" data-auto-filter-form>
                <label class="msf-field msf-field--grow">
                    <span class="msf-label">Buscar trabajador</span>
                    <input class="msf-control" type="search" name="buscar" value="<?= trab_cash_safe($buscar, '') ?>" placeholder="Nombre, identificación o rol">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">ID trabajador</span>
                    <input class="msf-control" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="Todos">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Periodo desde</span>
                    <input class="msf-control" type="date" name="periodo_inicio" value="<?= trab_cash_safe($periodoInicio, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Periodo hasta</span>
                    <input class="msf-control" type="date" name="periodo_fin" value="<?= trab_cash_safe($periodoFin, '') ?>">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Método</span>
                    <select class="msf-control" name="metodo_pago"><?= msf_options(['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'], $metodoPago) ?></select>
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Monto</span>
                    <input class="msf-control" type="number" data-money-format="true" min="0" step="0.01" name="monto" value="<?= trab_cash_safe($monto, '') ?>" placeholder="Sugerido">
                </label>
                <label class="msf-field msf-field--sm">
                    <span class="msf-label">Referencia</span>
                    <input class="msf-control" type="text" name="referencia" value="<?= trab_cash_safe($referencia, '') ?>" placeholder="Opcional">
                </label>
                <div class="msf-ranges" data-msf-from="periodo_inicio" data-msf-to="periodo_fin">
                    <span class="msf-ranges-label">Periodo</span>
                    <button type="button" class="msf-chip" data-msf-range="7d">7 días</button>
                    <button type="button" class="msf-chip" data-msf-range="mes">Este mes</button>
                    <button type="button" class="msf-chip" data-msf-range="mes-pasado">Mes pasado</button>
                </div>
                <div class="msf-actions">
                    <button class="msf-btn msf-btn--primary" type="submit"><i class="fas fa-calculator"></i> Evaluar</button>
                    <a class="msf-btn" href="<?= url('trabajadores/pagos-caja/simulador') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
                </div>
            </form>

            <div class="nr-panel">
                <div class="nr-panel-head">
                    <div>
                        <h2 class="nr-panel-title">Trabajadores evaluados</h2>
                        <p class="nr-panel-sub">Saldo estimado, monto simulado y elegibilidad para pago con Caja.</p>
                    </div>
                    <span class="nr-pill"><i class="fas fa-users"></i> <?= number_format(count($trabajadores)) ?></span>
                </div>
                <?php if (empty($trabajadores)): ?>
                    <div class="nr-empty">
                        <div class="nr-empty-icon"><i class="fas fa-user-clock"></i></div>
                        <h3>No hay trabajadores para evaluar</h3>
                        <p>Ajusta los filtros o vuelve al listado de personal.</p>
                    </div>
                <?php else: ?>
                    <div class="nr-table-wrap">
                        <table class="nr-table">
                            <thead>
                                <tr><th>Trabajador</th><th>Periodo y método</th><th class="nr-r">Saldo estimado</th><th class="nr-r">Monto simulado</th><th>Pagos en Caja</th><th>¿Se puede pagar?</th><th class="nr-r">Ficha</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadores as $trabajador): ?>
                                    <?php $trabajadorUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0)); ?>
                                    <tr>
                                        <td>
                                            <a class="nr-link" href="<?= $trabajadorUrl ?>">#<?= (int)($trabajador['id'] ?? 0) ?> <?= trab_cash_safe($trabajador['nombre_completo'] ?? null, 'Sin nombre') ?></a>
                                            <div class="nr-sub-txt"><?= trab_cash_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?> &middot; <?= trab_cash_safe($trabajador['estado'] ?? null, 'Sin estado') ?></div>
                                            <div class="nr-sub-txt">Ref: <?= trab_cash_safe($trabajador['referencia_evaluada'] ?? null, '-') ?></div>
                                        </td>
                                        <td>
                                            <div class="nr-strong nr-cap"><?= trab_cash_safe($trabajador['metodo_pago_simulado'] ?? null, 'efectivo') ?></div>
                                            <div class="nr-sub-txt"><?= trab_cash_date($trabajador['periodo_inicio_simulado'] ?? null) ?> a <?= trab_cash_date($trabajador['periodo_fin_simulado'] ?? null) ?></div>
                                        </td>
                                        <td class="nr-r">
                                            <div class="nr-strong"><?= trab_cash_money($trabajador['saldo_estimado'] ?? 0) ?></div>
                                            <div class="nr-sub-txt">+<?= trab_cash_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_cash_money((float)($trabajador['conceptos_en_contra'] ?? 0) + (float)($trabajador['anticipos_saldo'] ?? 0) + (float)($trabajador['prestamos_saldo'] ?? 0)) ?></div>
                                        </td>
                                        <td class="nr-r">
                                            <div class="nr-strong"><?= trab_cash_money($trabajador['monto_simulado'] ?? 0) ?></div>
                                            <div class="nr-sub-txt">Máx: <?= trab_cash_money($trabajador['monto_maximo_sugerido'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <div class="nr-strong"><?= (int)($trabajador['pagos_caja_count'] ?? 0) ?> registros</div>
                                            <div class="nr-sub-txt">Pagado: <?= trab_cash_money($trabajador['pagos_caja_total'] ?? 0) ?></div>
                                            <div class="nr-sub-txt">Último: <?= trab_cash_safe($trabajador['ultimo_pago_caja'] ?? null, '-') ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($trabajador['es_elegible_caja'])): ?>
                                                <span class="nr-badge ok"><i class="fas fa-circle-check"></i> Sí, se puede</span>
                                                <p class="nr-sub-txt" style="margin-top:6px;"><?= trab_cash_safe($trabajador['motivo_elegibilidad_caja'] ?? null, '') ?></p>
                                            <?php else: ?>
                                                <span class="nr-badge warn"><i class="fas fa-ban"></i> Todavía no</span>
                                                <p class="nr-sub-txt" style="margin-top:6px;"><?= trab_cash_safe($trabajador['motivo_bloqueo_caja'] ?? null, 'Sin diagnóstico') ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="nr-r"><a class="nr-act" href="<?= $trabajadorUrl ?>"><i class="fas fa-eye"></i> Ver</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
