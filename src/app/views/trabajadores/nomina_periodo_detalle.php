<?php
$periodo = is_array($periodoNomina ?? null) ? $periodoNomina : [];
$tablaPersistenteDisponible = $tablaPersistenteDisponible ?? false;
$detalles = is_array($periodo['detalles'] ?? null) ? $periodo['detalles'] : [];
$eventos = is_array($periodo['eventos'] ?? null) ? $periodo['eventos'] : [];
$aprobarToken = $aprobarToken ?? null;
$anularToken = $anularToken ?? null;
$pagosSnapshot = is_array($pagosSnapshot ?? null) ? $pagosSnapshot : [];
$pagoSnapshotTokens = is_array($pagoSnapshotTokens ?? null) ? $pagoSnapshotTokens : [];

if (!function_exists('trab_nom_det_safe')) {
    function trab_nom_det_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nom_det_money')) {
    function trab_nom_det_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nom_det_num')) {
    function trab_nom_det_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nom_det_date')) {
    function trab_nom_det_date($value)
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

if (!function_exists('trab_nom_det_status_class')) {
    function trab_nom_det_status_class($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'nd-badge ok';
        }
        if ($estado === 'anulado') {
            return 'nd-badge danger';
        }
        return 'nd-badge warn';
    }
}

if (!function_exists('trab_nom_det_status_label')) {
    function trab_nom_det_status_label($estado)
    {
        $estado = (string)$estado;
        if ($estado === 'aprobado') {
            return 'Aprobado';
        }
        if ($estado === 'anulado') {
            return 'Anulado';
        }
        return 'Cerrado';
    }
}

$ndEstado = (string)($periodo['estado'] ?? '');
$ndPeriodoId = (int)($periodo['id'] ?? 0);
?>

<style>
.nomina-det-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-det-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-det-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 2px; font-weight: 600; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
.nomina-det-page .nd-sub { color: var(--nom-muted); font-size: 13.5px; margin: 0 0 16px; line-height: 1.5; }

.nomina-det-page .nd-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 3px 11px; vertical-align: middle; }
.nomina-det-page .nd-badge i { font-size: 8px; }
.nomina-det-page .nd-badge.ok { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-det-page .nd-badge.warn { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-det-page .nd-badge.danger { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-det-page .nd-badge.brand { background: color-mix(in srgb, var(--nom-brand) 10%, transparent); color: var(--nom-brand); }
.nomina-det-page .nd-badge.neutral { background: rgba(0,0,0,.05); color: var(--nom-muted); }

.nomina-det-page .nd-toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
.nomina-det-page .nd-btn {
    display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-border); border-radius: 11px;
    padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--nom-card); color: var(--nom-text); text-decoration: none; white-space: nowrap;
}
.nomina-det-page .nd-btn:hover { border-color: color-mix(in srgb, var(--nom-gold) 40%, var(--nom-border)); }
.nomina-det-page .nd-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nomina-det-page .nd-btn-primary:hover { border-color: var(--nom-brand); filter: brightness(1.08); }
.nomina-det-page .nd-btn-danger { background: #fff; border-color: rgba(198,40,40,.4); color: #c62828; }
.nomina-det-page .nd-btn-danger:hover { background: rgba(198,40,40,.06); border-color: rgba(198,40,40,.55); }
.nomina-det-page .nd-btn.w-full { width: 100%; justify-content: center; }

.nomina-det-page .nd-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; margin-bottom: 16px; }
.nomina-det-page .nd-card h2 { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 19px; margin: 0 0 4px; font-weight: 600; }
.nomina-det-page .nd-card-hint { font-size: 12.5px; color: var(--nom-muted); margin: 0 0 14px; line-height: 1.45; }
.nomina-det-page .nd-card-flush { padding: 18px 20px 4px; }

.nomina-det-page .nd-guia {
    padding: 12px 15px; border-radius: 12px; font-size: 13px; line-height: 1.55; margin-bottom: 16px;
    background: color-mix(in srgb, var(--nom-gold) 9%, #ffffff); border: 1px solid color-mix(in srgb, var(--nom-gold) 30%, #ffffff);
    color: color-mix(in srgb, var(--nom-gold) 74%, #000);
}
.nomina-det-page .nd-guia strong { font-weight: 700; }

.nomina-det-page .nd-notice { border: 1px dashed var(--nom-border); border-radius: 14px; padding: 16px 18px; background: color-mix(in srgb, var(--nom-surface) 50%, #fff); }
.nomina-det-page .nd-notice strong { font-weight: 700; }
.nomina-det-page .nd-notice p { margin: 4px 0 0; font-size: 13px; color: var(--nom-muted); }

.nomina-det-page .nd-meta { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
@media (max-width: 720px) { .nomina-det-page .nd-meta { grid-template-columns: repeat(2, 1fr); } }
.nomina-det-page .nd-meta-label { font-size: 11px; letter-spacing: .06em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; margin-bottom: 3px; }
.nomina-det-page .nd-meta-value { font-size: 14.5px; font-weight: 600; }
.nomina-det-page .nd-meta-sub { font-size: 11.5px; color: var(--nom-muted); margin-top: 2px; }

.nomina-det-page .nd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
@media (max-width: 720px) { .nomina-det-page .nd-kpis { grid-template-columns: repeat(2, 1fr); } }
.nomina-det-page .nd-kpi { border: 1px solid var(--nom-border); border-radius: 14px; padding: 13px 15px; background: #fff; }
.nomina-det-page .nd-kpi.is-accent { background: color-mix(in srgb, var(--nom-gold) 7%, #fff); border-color: color-mix(in srgb, var(--nom-gold) 28%, var(--nom-border)); }
.nomina-det-page .nd-kpi-label { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nomina-det-page .nd-kpi-value { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 26px; font-weight: 600; margin-top: 4px; line-height: 1.1; font-variant-numeric: tabular-nums; }

.nomina-det-page .nd-actions { display: flex; flex-direction: column; gap: 12px; }
.nomina-det-page .nd-actions form { display: flex; flex-direction: column; gap: 8px; }
.nomina-det-page .nd-hint-ok, .nomina-det-page .nd-hint-wait {
    display: flex; align-items: flex-start; gap: 9px; padding: 10px 13px; border-radius: 11px; font-size: 12.5px; line-height: 1.45;
}
.nomina-det-page .nd-hint-ok { background: rgba(46,125,50,.07); border: 1px solid rgba(46,125,50,.24); color: #2e6b31; }
.nomina-det-page .nd-hint-wait { background: rgba(191,144,0,.08); border: 1px solid rgba(191,144,0,.28); color: #7a5c00; }
.nomina-det-page .nd-hint-ok i, .nomina-det-page .nd-hint-wait i { margin-top: 2px; }

.nomina-det-page .nd-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 4px; padding-top: 14px; border-top: 1px dashed var(--nom-border); }
@media (max-width: 720px) { .nomina-det-page .nd-facts { grid-template-columns: repeat(2, 1fr); } }
.nomina-det-page .nd-fact-label { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nomina-det-page .nd-fact-value { font-size: 14.5px; font-weight: 600; margin-top: 2px; font-variant-numeric: tabular-nums; }
.nomina-det-page .nd-fact-sub { font-size: 11.5px; color: var(--nom-muted); margin-top: 1px; }

.nomina-det-page .nd-label { display: block; font-size: 11.5px; font-weight: 700; color: var(--nom-muted); margin-bottom: 4px; }
.nomina-det-page .nd-input, .nomina-det-page .nd-select {
    width: 100%; border: 1px solid var(--nom-border); border-radius: 10px; padding: 9px 11px; font-size: 16px; background: #fff; color: var(--nom-text);
}

.nomina-det-page table.nd-tabla { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.nomina-det-page table.nd-tabla th { text-align: left; font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; padding: 10px; border-bottom: 1px solid var(--nom-border); vertical-align: bottom; }
.nomina-det-page table.nd-tabla td { padding: 13px 10px; border-bottom: 1px dashed var(--nom-border); vertical-align: top; }
.nomina-det-page table.nd-tabla tr:last-child td { border-bottom: 0; }
.nomina-det-page .nd-r { text-align: right; font-variant-numeric: tabular-nums; }
.nomina-det-page .nd-strong { font-weight: 600; }
.nomina-det-page .nd-neg { color: #c62828; }
.nomina-det-page .nd-trab-link { font-weight: 600; color: var(--nom-brand); text-decoration: none; }
.nomina-det-page .nd-trab-link:hover { text-decoration: underline; }
.nomina-det-page .nd-trab-rol { font-size: 11.5px; color: var(--nom-muted); margin-top: 2px; }

.nomina-det-page .nd-ded-breakdown { margin-top: 4px; font-size: 11.5px; color: var(--nom-muted); line-height: 1.5; }
.nomina-det-page .nd-ded-breakdown span { font-weight: 700; text-transform: uppercase; letter-spacing: .04em; margin-right: 5px; color: #6b6459; }
.nomina-det-page .nd-ded-none { color: #9a938a; font-size: 12.5px; font-style: italic; }
.nomina-det-page .nd-ded-note { margin-top: 5px; font-size: 11px; color: #9a938a; line-height: 1.45; }
.nomina-det-page .nd-ded-note strong { color: var(--nom-muted); font-weight: 700; }

.nomina-det-page .nd-help { border-bottom: 1px dotted currentColor; cursor: help; }

.nomina-det-page .nd-paybox { min-width: 250px; display: flex; flex-direction: column; gap: 8px; }
.nomina-det-page .nd-payfacts { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
.nomina-det-page .nd-payfact { border: 1px solid var(--nom-border); border-radius: 9px; background: color-mix(in srgb, var(--nom-surface) 40%, #fff); padding: 6px 8px; }
.nomina-det-page .nd-payfact span { display: block; font-size: 9.5px; letter-spacing: .04em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; line-height: 1.15; }
.nomina-det-page .nd-payfact strong { display: block; margin-top: 2px; font-size: 13px; font-weight: 600; font-variant-numeric: tabular-nums; }
.nomina-det-page .nd-payfacts.is-muted { opacity: .68; }
.nomina-det-page .nd-payrow { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.nomina-det-page .nd-payfoot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; font-size: 11.5px; color: var(--nom-muted); }
.nomina-det-page .nd-payblock-note { font-size: 11.5px; color: var(--nom-muted); margin-top: 6px; line-height: 1.4; }

.nomina-det-page .nd-eventos { display: flex; flex-direction: column; }
.nomina-det-page .nd-evento { padding: 11px 0; border-bottom: 1px dashed var(--nom-border); }
.nomina-det-page .nd-evento:last-child { border-bottom: 0; padding-bottom: 2px; }
.nomina-det-page .nd-evento-title { font-weight: 600; font-size: 13.5px; }
.nomina-det-page .nd-evento-meta { font-size: 11.5px; color: var(--nom-muted); margin-top: 2px; }
.nomina-det-page .nd-evento-motivo { font-size: 12.5px; margin-top: 4px; color: var(--nom-text); }
.nomina-det-page .nd-vacio { text-align: center; padding: 26px 16px; color: var(--nom-muted); font-size: 13px; }
.nomina-det-page .nd-vacio i { font-size: 22px; color: var(--nom-gold); display: block; margin-bottom: 8px; }

@media (max-width: 860px) {
    .nomina-det-page table.nd-tabla thead { display: none; }
    .nomina-det-page table.nd-tabla tr { display: block; border: 1px solid var(--nom-border); border-radius: 14px; margin: 12px 0; padding: 6px 12px; background: #fff; }
    .nomina-det-page table.nd-tabla tr:last-child td { border-bottom: 1px dashed var(--nom-border); }
    .nomina-det-page table.nd-tabla td { display: block; border: 0; padding: 8px 2px; text-align: left; }
    .nomina-det-page table.nd-tabla td.nd-r { text-align: left; }
    .nomina-det-page table.nd-tabla td[data-label]::before { content: attr(data-label); display: block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: var(--nom-muted); font-weight: 700; margin-bottom: 3px; }
    .nomina-det-page .nd-paybox { min-width: 0; }
}
</style>

<div class="nomina-det-page">
    <p class="nom-kicker">Personal · Pre-nómina</p>
    <h1 class="nom-title">
        Periodo #<?= $ndPeriodoId ?>
        <span class="<?= trab_nom_det_status_class($ndEstado) ?>">
            <i class="fas fa-circle"></i>
            <?= trab_nom_det_status_label($ndEstado) ?>
        </span>
    </h1>
    <p class="nd-sub">
        Foto congelada del periodo para revisarlo y registrar pagos por Caja &mdash; el pago de cada trabajador se controla de forma individual.
    </p>

    <?php $subnav_section = 'personal'; $subnav_active = 'prenomina'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <div class="nd-toolbar">
        <?php $back_arrow_href = back_url('trabajadores/nomina/periodos'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a class="nd-btn ms-back-legacy" href="<?= back_url('trabajadores/nomina/periodos') ?>">
            <i class="fas fa-arrow-left"></i>
            Periodos
        </a>
        <a class="nd-btn" href="<?= url('trabajadores/nomina/preview?fecha_inicio=' . urlencode((string)($periodo['fecha_inicio'] ?? '')) . '&fecha_fin=' . urlencode((string)($periodo['fecha_fin'] ?? ''))) ?>">
            <i class="fas fa-table-list"></i>
            Preview actual
        </a>
        <a class="nd-btn" href="<?= url('trabajadores/nomina/periodos/pagos-snapshot?periodo_id=' . $ndPeriodoId) ?>">
            <i class="fas fa-link"></i>
            Conciliación de pagos
        </a>
    </div>

    <div class="nd-guia">
        Un <strong>snapshot</strong> es la foto congelada de los números al cerrar el periodo: sirve para revisar y pagar en Caja, pero por sí solo <strong>no genera nómina oficial</strong>, pago, CFDI, timbrado, dispersión ni movimiento de Caja.
    </div>

    <?php if (!$tablaPersistenteDisponible): ?>
        <div class="nd-card">
            <div class="nd-notice">
                <strong>Snapshot no disponible.</strong>
                <p>Faltan tablas de persistencia de pre-nómina.</p>
            </div>
        </div>
    <?php else: ?>

        <!-- Resumen del periodo -->
        <div class="nd-card">
            <h2>Resumen del periodo</h2>
            <p class="nd-card-hint">Datos y totales congelados al cerrar el periodo.</p>
            <div class="nd-meta">
                <div>
                    <div class="nd-meta-label">Etiqueta</div>
                    <div class="nd-meta-value"><?= trab_nom_det_safe($periodo['etiqueta'] ?? 'Periodo') ?></div>
                </div>
                <div>
                    <div class="nd-meta-label">Rango</div>
                    <div class="nd-meta-value"><?= trab_nom_det_date($periodo['fecha_inicio'] ?? '') ?> &ndash; <?= trab_nom_det_date($periodo['fecha_fin'] ?? '') ?></div>
                </div>
                <div>
                    <div class="nd-meta-label">Tipo</div>
                    <div class="nd-meta-value"><?= trab_nom_det_safe($periodo['tipo_periodo'] ?? 'manual') ?></div>
                </div>
                <div>
                    <div class="nd-meta-label">Cerrado por</div>
                    <div class="nd-meta-value"><?= trab_nom_det_safe($periodo['cerrado_por_nombre'] ?? 'Usuario') ?></div>
                    <div class="nd-meta-sub"><?= trab_nom_det_safe($periodo['cerrado_at'] ?? '-') ?></div>
                </div>
            </div>

            <div class="nd-kpis">
                <div class="nd-kpi">
                    <div class="nd-kpi-label">Trabajadores</div>
                    <div class="nd-kpi-value"><?= trab_nom_det_num($periodo['trabajadores_total'] ?? 0) ?></div>
                </div>
                <div class="nd-kpi">
                    <div class="nd-kpi-label">Bruto</div>
                    <div class="nd-kpi-value"><?= trab_nom_det_money($periodo['bruto_total'] ?? 0) ?></div>
                </div>
                <div class="nd-kpi">
                    <div class="nd-kpi-label">
                        <span class="nd-help" title="Suma de anticipos y préstamos por saldar de todos los trabajadores. Los descuentos por concepto (faltas, sanciones) no entran aquí: ya van restados dentro del Bruto.">Deducciones</span>
                    </div>
                    <div class="nd-kpi-value"><?= trab_nom_det_money($periodo['deducciones_total'] ?? 0) ?></div>
                </div>
                <div class="nd-kpi is-accent">
                    <div class="nd-kpi-label">Pendiente de pago</div>
                    <div class="nd-kpi-value"><?= trab_nom_det_money($periodo['pendiente_pago_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <!-- Estado y acciones -->
        <div class="nd-card">
            <h2>Estado y acciones</h2>
            <p class="nd-card-hint">Estas acciones cambian solo el estado administrativo del snapshot; no mueven dinero por sí mismas.</p>

            <div class="nd-actions">
                <?php if ($ndEstado === 'cerrado'): ?>
                    <div class="nd-hint-wait">
                        <i class="fas fa-lock"></i>
                        <span>Aprueba el snapshot para <strong>habilitar el registro de pagos por Caja</strong> de cada trabajador (abajo).</span>
                    </div>
                <?php elseif ($ndEstado === 'aprobado'): ?>
                    <div class="nd-hint-ok">
                        <i class="fas fa-circle-check"></i>
                        <span>Snapshot aprobado: ya puedes <strong>registrar los pagos por Caja</strong> de cada trabajador en la tabla de abajo.</span>
                    </div>
                <?php elseif ($ndEstado === 'anulado'): ?>
                    <div class="nd-badge danger">
                        <i class="fas fa-lock"></i>
                        Snapshot anulado
                    </div>
                <?php endif; ?>

                <?php if ($ndEstado === 'cerrado' && $aprobarToken): ?>
                    <form method="POST" action="<?= url('trabajadores/nomina/periodos/' . $ndPeriodoId . '/aprobar') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="periodo_token" value="<?= trab_nom_det_safe($aprobarToken, '') ?>">
                        <button class="nd-btn nd-btn-primary w-full ms-pressable" type="submit">
                            <i class="fas fa-check"></i>
                            Aprobar snapshot
                        </button>
                    </form>
                <?php endif; ?>

                <?php if ($ndEstado !== 'anulado' && $anularToken): ?>
                    <form method="POST" action="<?= url('trabajadores/nomina/periodos/' . $ndPeriodoId . '/anular') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="periodo_token" value="<?= trab_nom_det_safe($anularToken, '') ?>">
                        <input class="nd-input" type="text" name="motivo" maxlength="255" required placeholder="Motivo de anulación">
                        <button class="nd-btn nd-btn-danger w-full ms-pressable" type="submit">
                            <i class="fas fa-ban"></i>
                            Anular snapshot
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="nd-facts">
                <div>
                    <div class="nd-fact-label"><span class="nd-help" title="Total pagado por Caja aplicado a este periodo.">Pagos Caja aplicados</span></div>
                    <div class="nd-fact-value"><?= trab_nom_det_money($periodo['pagos_caja_aplicados_total'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="nd-fact-label"><span class="nd-help" title="Pagos que fueron revertidos o cancelados después de aplicarse.">Reversiones detectadas</span></div>
                    <div class="nd-fact-value"><?= trab_nom_det_money($periodo['reversiones_detectadas_total'] ?? 0) ?></div>
                </div>
                <?php if ($ndEstado === 'aprobado'): ?>
                    <div>
                        <div class="nd-fact-label">Aprobado por</div>
                        <div class="nd-fact-value"><?= trab_nom_det_safe($periodo['aprobado_por_nombre'] ?? 'Usuario') ?></div>
                        <div class="nd-fact-sub"><?= trab_nom_det_safe($periodo['aprobado_at'] ?? '-') ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($ndEstado === 'anulado'): ?>
                    <div>
                        <div class="nd-fact-label">Motivo de anulación</div>
                        <div class="nd-fact-value"><?= trab_nom_det_safe($periodo['motivo_anulacion'] ?? '-') ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Detalle por trabajador -->
        <div class="nd-card nd-card-flush">
            <h2>Detalle congelado por trabajador</h2>
            <p class="nd-card-hint">Estos importes son la fotografía guardada al cerrar el periodo. El pago por Caja se registra por trabajador.</p>
            <div style="overflow-x:auto;">
                <table class="nd-tabla">
                    <thead>
                        <tr>
                            <th>Trabajador</th>
                            <th>Estado</th>
                            <th class="nd-r"><span class="nd-help" title="Lo que ganó en el periodo antes de restar deducciones o pagos (sus conceptos a favor menos los descuentos por concepto).">Bruto</span></th>
                            <th class="nd-r"><span class="nd-help" title="Anticipos + préstamos por saldar. Los descuentos por concepto ya van restados dentro del Bruto.">Deducciones</span></th>
                            <th class="nd-r"><span class="nd-help" title="Lo que ya se le pagó por Caja en este periodo. Se descuenta de lo que se le debe.">Caja aplicada</span></th>
                            <th class="nd-r"><span class="nd-help" title="Lo que se le debe tras restar todo: Bruto - Deducciones - Caja aplicada.">Neto</span></th>
                            <th class="nd-r"><span class="nd-help" title="Lo que todavía falta pagarle en este periodo.">Pendiente</span></th>
                            <th>Pago Caja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $detalle): ?>
                            <?php
                            $detalleId = (int)($detalle['id'] ?? 0);
                            $evaluacionPago = $pagosSnapshot[$detalleId] ?? null;
                            $pagoToken = $pagoSnapshotTokens[$detalleId] ?? null;
                            $montoMaximoPago = number_format((float)($evaluacionPago['monto_maximo'] ?? 0), 2, '.', '');
                            $snapshotPendientePago = number_format((float)($evaluacionPago['snapshot_pendiente'] ?? ($detalle['pendiente_pago_sugerido'] ?? 0)), 2, '.', '');
                            $saldoVivoPago = number_format((float)($evaluacionPago['saldo_vivo'] ?? 0), 2, '.', '');
                            $metodosPago = is_array($evaluacionPago['metodos_pago'] ?? null)
                                ? $evaluacionPago['metodos_pago']
                                : ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'transferencia' => 'Transferencia'];
                            ?>
                            <tr>
                                <td data-label="Trabajador">
                                    <a class="nd-trab-link" href="<?= url('trabajadores/' . (int)($detalle['trabajador_id'] ?? 0)) ?>">
                                        <?= trab_nom_det_safe($detalle['trabajador_nombre'] ?? 'Trabajador') ?>
                                    </a>
                                    <div class="nd-trab-rol"><?= trab_nom_det_safe($detalle['trabajador_rol'] ?? 'Sin rol') ?></div>
                                </td>
                                <td data-label="Estado">
                                    <span class="nd-badge neutral">
                                        <i class="fas fa-circle"></i>
                                        <?= trab_nom_det_safe(str_replace('_', ' ', (string)($detalle['estado_preview_nomina'] ?? ''))) ?>
                                    </span>
                                    <?php if (trim((string)($detalle['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                        <div class="nd-neg" style="font-size:11.5px; font-weight:600; margin-top:6px;"><?= trab_nom_det_safe($detalle['motivo_bloqueo_nomina'] ?? '') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="nd-r nd-strong" data-label="Bruto"><?= trab_nom_det_money($detalle['bruto_periodo'] ?? 0) ?></td>
                                <td class="nd-r" data-label="Deducciones">
                                    <?php
                                    $ddTotal = (float)($detalle['deducciones_informativas'] ?? 0);
                                    $ddAntSaldo = (float)($detalle['anticipos_saldo'] ?? 0);
                                    $ddAntN = (int)($detalle['anticipos_count'] ?? 0);
                                    $ddPreSaldo = (float)($detalle['prestamos_saldo'] ?? 0);
                                    $ddPreN = (int)($detalle['prestamos_count'] ?? 0);
                                    $ddEnContra = (float)($detalle['conceptos_en_contra'] ?? 0);
                                    ?>
                                    <?php if ($ddTotal > 0): ?>
                                        <div class="nd-strong nd-neg">-<?= trab_nom_det_money($ddTotal) ?></div>
                                        <div class="nd-ded-breakdown">
                                            <?php if ($ddAntSaldo > 0): ?>
                                                <div><span>Anticipos</span><?= trab_nom_det_money($ddAntSaldo) ?><?= $ddAntN > 0 ? ' · ' . trab_nom_det_num($ddAntN) : '' ?></div>
                                            <?php endif; ?>
                                            <?php if ($ddPreSaldo > 0): ?>
                                                <div><span>Préstamos</span><?= trab_nom_det_money($ddPreSaldo) ?><?= $ddPreN > 0 ? ' · ' . trab_nom_det_num($ddPreN) : '' ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($ddEnContra <= 0): ?>
                                        <div class="nd-ded-none">Sin deducciones</div>
                                    <?php endif; ?>
                                    <?php if ($ddEnContra > 0): ?>
                                        <div class="nd-ded-note" title="Descuentos registrados como concepto en contra (p. ej. faltas, sanciones). Ya están restados dentro del Bruto, por eso no se suman aquí.">
                                            <strong>Descuento en concepto</strong> -<?= trab_nom_det_money($ddEnContra) ?> · ya en el bruto
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="nd-r" data-label="Caja aplicada"><?= trab_nom_det_money($detalle['pagos_caja_aplicados'] ?? 0) ?></td>
                                <td class="nd-r nd-strong" data-label="Neto"><?= trab_nom_det_money($detalle['neto_sugerido'] ?? 0) ?></td>
                                <td class="nd-r nd-strong" data-label="Pendiente"><?= trab_nom_det_money($detalle['pendiente_pago_sugerido'] ?? 0) ?></td>
                                <td data-label="Pago Caja">
                                    <?php if ($ndEstado !== 'aprobado'): ?>
                                        <span class="nd-badge warn">
                                            <i class="fas fa-lock"></i>
                                            Requiere aprobación
                                        </span>
                                    <?php elseif ($evaluacionPago && !empty($evaluacionPago['elegible']) && $pagoToken): ?>
                                        <form class="nd-paybox" method="POST" action="<?= url('trabajadores/nomina/periodos/' . $ndPeriodoId . '/detalles/' . $detalleId . '/registrar-pago-caja') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="pago_token" value="<?= trab_nom_det_safe($pagoToken, '') ?>">
                                            <div class="nd-payfacts">
                                                <div class="nd-payfact">
                                                    <span title="Lo que quedó pendiente según la foto congelada al cerrar el periodo.">Snapshot</span>
                                                    <strong><?= trab_nom_det_money($snapshotPendientePago) ?></strong>
                                                </div>
                                                <div class="nd-payfact">
                                                    <span title="Lo que falta hoy de verdad, ya restando los pagos hechos después de cerrar el periodo.">Saldo vivo</span>
                                                    <strong><?= trab_nom_det_money($saldoVivoPago) ?></strong>
                                                </div>
                                                <div class="nd-payfact">
                                                    <span title="Lo máximo que puedes pagar ahora: el menor entre el snapshot y el saldo vivo.">Máximo</span>
                                                    <strong><?= trab_nom_det_money($montoMaximoPago) ?></strong>
                                                </div>
                                            </div>
                                            <div class="nd-payrow">
                                                <div>
                                                    <label class="nd-label" for="snapshot_pago_monto_<?= $detalleId ?>">Monto</label>
                                                    <input
                                                        class="nd-input"
                                                        id="snapshot_pago_monto_<?= $detalleId ?>"
                                                        type="number"
                                                        name="monto"
                                                        min="0.01"
                                                        max="<?= trab_nom_det_safe($montoMaximoPago, '0.00') ?>"
                                                        step="0.01"
                                                        value="<?= trab_nom_det_safe($montoMaximoPago, '0.00') ?>"
                                                        required
                                                    >
                                                </div>
                                                <div>
                                                    <label class="nd-label" for="snapshot_pago_metodo_<?= $detalleId ?>">Método</label>
                                                    <select class="nd-select" id="snapshot_pago_metodo_<?= $detalleId ?>" name="metodo_pago" required>
                                                        <?php foreach ($metodosPago as $metodoKey => $metodoLabel): ?>
                                                            <option value="<?= trab_nom_det_safe($metodoKey, '') ?>"><?= trab_nom_det_safe($metodoLabel, '') ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <input class="nd-input" type="text" name="referencia" maxlength="100" required placeholder="Referencia obligatoria">
                                            <input class="nd-input" type="text" name="notas" maxlength="700" placeholder="Notas opcionales">
                                            <div class="nd-payfoot">
                                                <span>Máximo <?= trab_nom_det_money($montoMaximoPago) ?></span>
                                                <button class="nd-btn nd-btn-primary ms-pressable" type="submit">
                                                    <i class="fas fa-cash-register"></i>
                                                    Registrar pago
                                                </button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <span class="nd-badge danger">
                                            <i class="fas fa-ban"></i>
                                            Bloqueado
                                        </span>
                                        <div class="nd-payblock-note">
                                            <?= trab_nom_det_safe($evaluacionPago['motivo_bloqueo'] ?? 'No elegible para pago con Caja') ?>
                                        </div>
                                        <?php if ($evaluacionPago): ?>
                                            <div class="nd-payfacts is-muted" style="margin-top:8px;">
                                                <div class="nd-payfact">
                                                    <span title="Lo que quedó pendiente según la foto congelada al cerrar el periodo.">Snapshot</span>
                                                    <strong><?= trab_nom_det_money($snapshotPendientePago) ?></strong>
                                                </div>
                                                <div class="nd-payfact">
                                                    <span title="Lo que falta hoy de verdad, ya restando los pagos hechos después de cerrar el periodo.">Saldo vivo</span>
                                                    <strong><?= trab_nom_det_money($saldoVivoPago) ?></strong>
                                                </div>
                                                <div class="nd-payfact">
                                                    <span title="Lo máximo que puedes pagar ahora: el menor entre el snapshot y el saldo vivo.">Máximo</span>
                                                    <strong><?= trab_nom_det_money($montoMaximoPago) ?></strong>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bitácora -->
        <div class="nd-card">
            <h2>Bitácora del periodo</h2>
            <p class="nd-card-hint">Historial de cambios de estado del snapshot.</p>
            <?php if ($eventos === []): ?>
                <div class="nd-vacio"><i class="fas fa-clock-rotate-left"></i> Aún no hay eventos registrados.</div>
            <?php else: ?>
                <div class="nd-eventos">
                    <?php foreach ($eventos as $evento): ?>
                        <div class="nd-evento">
                            <div class="nd-evento-title"><?= trab_nom_det_safe($evento['descripcion'] ?? 'Evento') ?></div>
                            <div class="nd-evento-meta">
                                <?= trab_nom_det_safe($evento['created_at'] ?? '-') ?> · <?= trab_nom_det_safe($evento['creado_por_nombre'] ?? ($evento['creado_por_login'] ?? 'Usuario')) ?>
                            </div>
                            <?php if (trim((string)($evento['motivo'] ?? '')) !== ''): ?>
                                <div class="nd-evento-motivo"><?= trab_nom_det_safe($evento['motivo'] ?? '') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>
