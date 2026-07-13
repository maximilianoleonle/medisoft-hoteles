<?php
/**
 * Nomina core - previsualizacion de periodo v2 (Fase 3).
 * Solo lectura: nada se guarda hasta cerrar.
 */
$pv = is_array($preview ?? null) ? $preview : [];
$pvGrupo = is_array($pv['grupo'] ?? null) ? $pv['grupo'] : [];
$pvFilas = is_array($pv['trabajadores'] ?? null) ? $pv['trabajadores'] : [];
$pvTotales = is_array($pv['totales'] ?? null) ? $pv['totales'] : [];
$pvAlertas = is_array($pv['alertas'] ?? null) ? $pv['alertas'] : [];
$pvBloqueado = !empty($pv['bloqueado']);
$pvPuedeCerrar = !empty($puedeCerrar);

$back_arrow_href = url('nomina/periodos');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-pv-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-pv-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-pv-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 26px; margin: 2px 0 4px; font-weight: 600; }
.nomina-pv-page .pv-sub { color: var(--nom-muted); font-size: 13.5px; margin: 0 0 16px; }
.nomina-pv-page .pv-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
@media (max-width: 900px) { .nomina-pv-page .pv-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.nomina-pv-page .pv-kpi { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 14px; padding: 14px 16px; }
.nomina-pv-page .pv-kpi-label { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nomina-pv-page .pv-kpi-value { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 24px; font-weight: 600; margin-top: 4px; }
.nomina-pv-page .pv-alerta {
    display: flex; gap: 10px; align-items: flex-start;
    border: 1px solid rgba(191,144,0,.4); background: rgba(191,144,0,.08);
    border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; font-size: 13px;
}
.nomina-pv-page .pv-alerta.bloqueante { border-color: rgba(198,40,40,.45); background: rgba(198,40,40,.07); }
.nomina-pv-page .pv-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 14px 16px; margin-bottom: 12px; }
.nomina-pv-page .pv-emp { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.nomina-pv-page .pv-emp strong { font-size: 14.5px; }
.nomina-pv-page .pv-emp .pv-neto { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 22px; font-weight: 600; }
.nomina-pv-page .pv-badge { display: inline-block; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 2px 9px; }
.nomina-pv-page .pv-badge.por_pagar { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-pv-page .pv-badge.sin_saldo, .nomina-pv-page .pv-badge.sin_movimientos { background: rgba(120,120,120,.14); color: #666; }
.nomina-pv-page details.pv-lineas { margin-top: 8px; }
.nomina-pv-page details.pv-lineas summary { cursor: pointer; font-size: 12px; color: var(--nom-gold); font-weight: 700; }
.nomina-pv-page table.pv-tabla { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-top: 8px; }
.nomina-pv-page table.pv-tabla th { text-align: left; font-size: 10.5px; text-transform: uppercase; color: var(--nom-muted); padding: 6px 8px; border-bottom: 1px solid var(--nom-border); }
.nomina-pv-page table.pv-tabla td { padding: 6px 8px; border-bottom: 1px dashed var(--nom-border); }
.nomina-pv-page .pv-linea-ded td:nth-child(4) { color: #c62828; }
.nomina-pv-page .pv-mini { font-size: 12px; color: var(--nom-muted); }
.nomina-pv-page .pv-cerrar { display: flex; justify-content: flex-end; margin-top: 16px; }
.nomina-pv-page .pv-btn {
    display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--nom-brand); border-radius: 12px;
    padding: 11px 20px; font-size: 14px; font-weight: 700; cursor: pointer; background: var(--nom-brand); color: #fff;
}
.nomina-pv-page .pv-btn[disabled] { opacity: .5; cursor: not-allowed; }
</style>

<div class="nomina-pv-page">
    <p class="nom-kicker">Nómina · Previsualización (nada se guarda todavía)</p>
    <h1 class="nom-title"><?= htmlspecialchars((string) ($pvGrupo['nombre'] ?? '')) ?> · <?= htmlspecialchars((string) ($pv['fecha_inicio'] ?? '')) ?> — <?= htmlspecialchars((string) ($pv['fecha_fin'] ?? '')) ?></h1>
    <p class="pv-sub"><?= (int) ($pv['dias'] ?? 0) ?> días · periodicidad <?= htmlspecialchars((string) ($pvGrupo['periodicidad'] ?? '')) ?> · redondeo a <?= ($pv['redondeo'] ?? 'centavos') === 'pesos' ? 'pesos enteros' : 'centavos' ?></p>

    <?php $subnav_section = 'nomina'; $subnav_active = 'periodos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php $nomina_pasos_actual = 2; include APP_PATH . '/views/partials/nomina_pasos.php'; ?>

    <?php foreach ($pvAlertas as $a): ?>
    <div class="pv-alerta <?= $pvBloqueado ? 'bloqueante' : '' ?>">
        <i class="fas fa-triangle-exclamation" style="color:<?= $pvBloqueado ? '#c62828' : '#9a7400' ?>; margin-top:2px;"></i>
        <div><?= htmlspecialchars($a) ?></div>
    </div>
    <?php endforeach; ?>

    <div class="pv-kpis" data-ms-stagger>
        <div class="pv-kpi"><div class="pv-kpi-label">Empleados</div><div class="pv-kpi-value"><?= count($pvFilas) ?></div></div>
        <div class="pv-kpi"><div class="pv-kpi-label">Percepciones</div><div class="pv-kpi-value">$<?= number_format((float) ($pvTotales['percepciones'] ?? 0), 2) ?></div></div>
        <div class="pv-kpi"><div class="pv-kpi-label">Bruto</div><div class="pv-kpi-value">$<?= number_format((float) ($pvTotales['bruto'] ?? 0), 2) ?></div></div>
        <div class="pv-kpi"><div class="pv-kpi-label">Neto sugerido</div><div class="pv-kpi-value">$<?= number_format((float) ($pvTotales['neto'] ?? 0), 2) ?></div></div>
    </div>

    <?php foreach ($pvFilas as $f): ?>
    <?php $t = $f['trabajador']; ?>
    <div class="pv-card">
        <div class="pv-emp">
            <div>
                <strong><?= htmlspecialchars((string) $t['nombre_completo']) ?></strong>
                <span class="pv-badge <?= htmlspecialchars((string) $f['estado_preview']) ?>"><?= htmlspecialchars(str_replace('_', ' ', (string) $f['estado_preview'])) ?></span>
                <div class="pv-mini">
                    Percepciones $<?= number_format($f['percepciones'], 2) ?> ·
                    Deducciones $<?= number_format($f['deducciones_lineas'], 2) ?> ·
                    Anticipos/préstamos $<?= number_format($f['deducciones_informativas'], 2) ?>
                </div>
            </div>
            <div class="pv-neto">$<?= number_format($f['neto'], 2) ?></div>
        </div>
        <?php foreach ($f['alertas'] as $al): ?>
        <div class="pv-mini" style="color:#9a7400;"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($al) ?></div>
        <?php endforeach; ?>
        <?php if ($f['lineas'] !== []): ?>
        <details class="pv-lineas">
            <summary><?= count($f['lineas']) ?> concepto(s) — ver desglose</summary>
            <table class="pv-tabla">
                <thead><tr><th>Concepto</th><th>Origen</th><th>Cant.</th><th>Monto</th></tr></thead>
                <tbody>
                    <?php foreach ($f['lineas'] as $l): ?>
                    <tr class="<?= $l['tipo'] === 'deduccion' ? 'pv-linea-ded' : '' ?>">
                        <td><?= $l['tipo'] === 'deduccion' ? '−' : '+' ?> <?= htmlspecialchars((string) $l['concepto_nombre']) ?></td>
                        <td><?= htmlspecialchars((string) $l['origen']) ?></td>
                        <td><?= $l['cantidad'] !== null ? number_format((float) $l['cantidad'], 2) : '—' ?></td>
                        <td>$<?= number_format((float) $l['monto'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if ($pvPuedeCerrar): ?>
    <div class="pv-cerrar">
        <form method="POST" action="<?= url('nomina/periodos/cerrar') ?>"
              data-ms-confirm data-ms-type="warning" data-ms-icon="wallet"
              data-ms-title="Cerrar periodo de nómina"
              data-ms-msg="Los totales quedarán congelados tal como los ves. OJO: cerrar NO paga todavía — después viene el paso 3 (aprobar) y al final el pago desde Caja. ¿Cerrar el periodo?"
              data-ms-ok="Cerrar periodo">
            <?= csrf_field() ?>
            <input type="hidden" name="grupo_id" value="<?= (int) ($pvGrupo['id'] ?? 0) ?>">
            <input type="hidden" name="inicio" value="<?= htmlspecialchars((string) ($pv['fecha_inicio'] ?? '')) ?>">
            <input type="hidden" name="fin" value="<?= htmlspecialchars((string) ($pv['fecha_fin'] ?? '')) ?>">
            <button type="submit" class="pv-btn ms-pressable" <?= $pvBloqueado || $pvFilas === [] ? 'disabled' : '' ?>>
                <i class="fas fa-lock"></i> Cerrar periodo
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>
