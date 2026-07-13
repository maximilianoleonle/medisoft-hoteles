<?php
/**
 * Nomina core - detalle de un periodo cerrado (Fase 3).
 * Snapshot inmutable: cabecera, detalles por trabajador y lineas congeladas.
 */
$pd = is_array($periodo ?? null) ? $periodo : [];
$pdDetalles = is_array($detalles ?? null) ? $detalles : [];
$pdLineas = is_array($lineasPorDetalle ?? null) ? $lineasPorDetalle : [];
$pdPuedeAprobar = !empty($puedeAprobar);
$pdPuedeAnular = !empty($puedeAnular);
$pdPuedeVerRecibos = !empty($puedeVerRecibos);
$pdRecibos = is_array($recibosPorDetalle ?? null) ? $recibosPorDetalle : [];
$pdPermiteReapertura = !empty($permitirReapertura);
$pdEsV2 = ($pd['motor'] ?? 'v1') === 'v2';

$pdEstados = [
    'cerrado' => ['t' => 'Cerrado', 'c' => 'warn', 'desc' => 'Snapshot congelado; requiere aprobación para habilitar pagos.'],
    'aprobado' => ['t' => 'Aprobado', 'c' => 'ok', 'desc' => 'Listo para pagos por Caja desde el snapshot.'],
    'anulado' => ['t' => 'Anulado', 'c' => 'danger', 'desc' => 'Periodo anulado; sus créditos de ledger fueron revertidos.'],
];
$pdEstado = $pdEstados[(string) ($pd['estado'] ?? '')] ?? null;

$back_arrow_href = url('nomina/periodos');
include APP_PATH . '/views/partials/back_arrow.php';
?>
<style>
.nomina-pd-page {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
}
.nomina-pd-page .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nomina-pd-page .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 26px; margin: 2px 0 4px; font-weight: 600; }
.nomina-pd-page .pd-badge { display: inline-block; font-size: 12px; font-weight: 700; border-radius: 999px; padding: 3px 11px; vertical-align: middle; }
.nomina-pd-page .pd-badge.ok { background: rgba(46,125,50,.12); color: #2e7d32; }
.nomina-pd-page .pd-badge.warn { background: rgba(191,144,0,.14); color: #9a7400; }
.nomina-pd-page .pd-badge.danger { background: rgba(198,40,40,.10); color: #c62828; }
.nomina-pd-page .pd-sub { color: var(--nom-muted); font-size: 13px; margin: 4px 0 16px; }
.nomina-pd-page .pd-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-bottom: 16px; }
@media (max-width: 900px) { .nomina-pd-page .pd-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.nomina-pd-page .pd-kpi { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 14px; padding: 14px 16px; }
.nomina-pd-page .pd-kpi-label { font-size: 11px; letter-spacing: .1em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nomina-pd-page .pd-kpi-value { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 24px; font-weight: 600; margin-top: 4px; }
.nomina-pd-page .pd-acciones { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.nomina-pd-page .pd-btn {
    display: inline-flex; align-items: center; gap: 8px; border-radius: 11px; padding: 9px 16px;
    font-size: 13px; font-weight: 600; cursor: pointer; border: 1px solid var(--nom-border);
    background: var(--nom-card); color: var(--nom-text);
}
.nomina-pd-page .pd-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nomina-pd-page .pd-btn-danger { border-color: rgba(198,40,40,.4); color: #c62828; }
.nomina-pd-page .pd-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 14px 16px; margin-bottom: 12px; }
.nomina-pd-page .pd-emp { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
.nomina-pd-page .pd-emp .pd-neto { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 22px; font-weight: 600; }
.nomina-pd-page .pd-mini { font-size: 12px; color: var(--nom-muted); }
.nomina-pd-page details.pd-lineas summary { cursor: pointer; font-size: 12px; color: var(--nom-gold); font-weight: 700; margin-top: 6px; }
.nomina-pd-page table.pd-tabla { width: 100%; border-collapse: collapse; font-size: 12.5px; margin-top: 8px; }
.nomina-pd-page table.pd-tabla th { text-align: left; font-size: 10.5px; text-transform: uppercase; color: var(--nom-muted); padding: 6px 8px; border-bottom: 1px solid var(--nom-border); }
.nomina-pd-page table.pd-tabla td { padding: 6px 8px; border-bottom: 1px dashed var(--nom-border); }
.nomina-pd-page .pd-anular-form { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.nomina-pd-page .pd-anular-form input { border: 1px solid var(--nom-border); border-radius: 10px; padding: 8px 11px; font-size: 16px; min-width: 240px; }
.nomina-pd-page .pd-aviso { border: 1px dashed var(--nom-border); border-radius: 12px; padding: 10px 13px; font-size: 12.5px; color: var(--nom-muted); margin-bottom: 14px; }
.nomina-pd-page .pd-next {
    display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
    border-radius: 13px; padding: 12px 16px; margin-bottom: 14px;
    font-size: 13.5px; line-height: 1.45; border: 1px solid;
}
.nomina-pd-page .pd-next > i { font-size: 17px; }
.nomina-pd-page .pd-next > div { flex: 1 1 280px; min-width: 0; }
.nomina-pd-page .pd-next.is-warn { background: rgba(191,144,0,.10); border-color: rgba(191,144,0,.30); color: #7a5c00; }
.nomina-pd-page .pd-next.is-warn > i { color: #9a7400; }
.nomina-pd-page .pd-next.is-ok { background: rgba(46,125,50,.09); border-color: rgba(46,125,50,.28); color: #23531f; }
.nomina-pd-page .pd-next.is-ok > i { color: #2e7d32; }
.nomina-pd-page .pd-next.is-muted { background: color-mix(in srgb, var(--nom-surface) 55%, #fff); border-color: var(--nom-border); color: var(--nom-muted); }
</style>

<div class="nomina-pd-page">
    <p class="nom-kicker">Nómina · Periodo <?= $pdEsV2 ? 'v2' : 'pre-nómina' ?></p>
    <h1 class="nom-title">
        <?= htmlspecialchars((string) ($pd['etiqueta'] ?? '')) ?>
        <?php if ($pdEstado): ?><span class="pd-badge <?= $pdEstado['c'] ?>"><?= $pdEstado['t'] ?></span><?php endif; ?>
    </h1>
    <p class="pd-sub">
        <?= htmlspecialchars((string) ($pd['fecha_inicio'] ?? '')) ?> — <?= htmlspecialchars((string) ($pd['fecha_fin'] ?? '')) ?>
        <?= !empty($pd['grupo_nombre']) ? ' · grupo ' . htmlspecialchars((string) $pd['grupo_nombre']) : '' ?>
        <?= $pdEstado ? ' · ' . $pdEstado['desc'] : '' ?>
        <?= !empty($pd['motivo_anulacion']) ? ' · Motivo: ' . htmlspecialchars((string) $pd['motivo_anulacion']) : '' ?>
    </p>

    <?php $subnav_section = 'nomina'; $subnav_active = 'periodos'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

    <?php
    // ¿Y ahora qué sigue? — calculado del estado REAL del periodo.
    $pdEstadoRaw = (string) ($pd['estado'] ?? '');
    $pdPendiente = (float) ($pd['pendiente_pago_total'] ?? 0);
    $pdPagadoCompleto = $pdEstadoRaw === 'aprobado' && $pdPendiente <= 0.009;
    ?>
    <?php if ($pdEsV2 && $pdEstadoRaw !== 'anulado'): ?>
        <?php
        $nomina_pasos_actual = $pdEstadoRaw === 'cerrado' ? 3 : 4;
        $nomina_pasos_completado = $pdPagadoCompleto;
        include APP_PATH . '/views/partials/nomina_pasos.php';
        ?>
    <?php endif; ?>

    <?php if ($pdEsV2 && $pdEstadoRaw === 'cerrado'): ?>
    <div class="pd-next is-warn" role="status">
        <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
        <div>
            <strong>Los números ya quedaron congelados, pero todavía no se puede pagar.</strong>
            Falta el paso 3: aprobar el periodo.
            <?= $pdPuedeAprobar ? 'Revisa las cifras de abajo y, si cuadran, pica el botón verde.' : 'Pídele a alguien con permiso de aprobación que lo revise y apruebe.' ?>
        </div>
    </div>
    <?php elseif ($pdEsV2 && $pdEstadoRaw === 'aprobado' && !$pdPagadoCompleto): ?>
    <div class="pd-next is-warn" role="status">
        <i class="fas fa-hand-holding-dollar" aria-hidden="true"></i>
        <div>
            <strong>Aprobado: hay $<?= number_format($pdPendiente, 2) ?> esperando pago.</strong>
            Último paso: entrega el dinero con el botón <em>"Registrar pagos en Personal (Caja)"</em> de abajo
            (necesitas un corte de Caja abierto).
        </div>
    </div>
    <?php elseif ($pdPagadoCompleto): ?>
    <div class="pd-next is-ok" role="status">
        <i class="fas fa-circle-check" aria-hidden="true"></i>
        <div>
            <strong>Periodo pagado por completo.</strong>
            La nómina de este periodo está terminada: no hay nada más que hacer aquí.
        </div>
    </div>
    <?php elseif ($pdEstadoRaw === 'anulado'): ?>
    <div class="pd-next is-muted" role="status">
        <i class="fas fa-ban" aria-hidden="true"></i>
        <div>
            <strong>Este periodo está anulado:</strong> no cuenta para pagos. Si necesitas volver a pagarlo,
            calcula de nuevo el periodo desde la pestaña Periodos.
        </div>
    </div>
    <?php endif; ?>

    <?php if (!$pdEsV2): ?>
    <div class="pd-aviso">
        Este periodo fue generado por la pre-nómina del bloque Personal.
        Consúltalo en <a href="<?= url('trabajadores/nomina/periodos/' . (int) ($pd['id'] ?? 0)) ?>">Personal · Pre-nómina</a>.
    </div>
    <?php endif; ?>

    <div class="pd-kpis" data-ms-stagger>
        <div class="pd-kpi"><div class="pd-kpi-label">Empleados</div><div class="pd-kpi-value"><?= (int) ($pd['trabajadores_total'] ?? 0) ?></div></div>
        <div class="pd-kpi"><div class="pd-kpi-label">Bruto</div><div class="pd-kpi-value">$<?= number_format((float) ($pd['bruto_total'] ?? 0), 2) ?></div></div>
        <div class="pd-kpi"><div class="pd-kpi-label">Neto sugerido</div><div class="pd-kpi-value">$<?= number_format((float) ($pd['neto_sugerido_total'] ?? 0), 2) ?></div></div>
        <div class="pd-kpi"><div class="pd-kpi-label">Pendiente de pago</div><div class="pd-kpi-value">$<?= number_format((float) ($pd['pendiente_pago_total'] ?? 0), 2) ?></div></div>
    </div>

    <?php if (function_exists('hotel_menu_module_enabled') && hotel_menu_module_enabled('exportaciones') && can('nomina.exportar')): ?>
    <div class="pd-acciones">
        <a class="pd-btn ms-pressable" href="<?= url('nomina/periodos/' . (int) ($pd['id'] ?? 0) . '/exportar') ?>">
            <i class="fas fa-file-csv"></i> Exportar para contador (CSV)
        </a>
    </div>
    <?php endif; ?>

    <?php if ($pdEsV2 && ($pd['estado'] ?? '') !== 'anulado'): ?>
    <div class="pd-acciones">
        <?php if (($pd['estado'] ?? '') === 'cerrado' && $pdPuedeAprobar): ?>
        <form method="POST" action="<?= url('nomina/periodos/' . (int) $pd['id'] . '/aprobar') ?>"
              data-ms-confirm data-ms-type="info" data-ms-icon="wallet"
              data-ms-title="Aprobar periodo"
              data-ms-msg="Al aprobar se habilita el último paso: pagar desde Caja. Revisa las cifras antes — un periodo aprobado ya no se modifica. ¿Aprobar?"
              data-ms-ok="Aprobar">
            <?= csrf_field() ?>
            <button type="submit" class="pd-btn pd-btn-primary ms-pressable"><i class="fas fa-check-double"></i> Aprobar periodo</button>
        </form>
        <?php endif; ?>
        <?php if ($pdPuedeAnular): ?>
        <form method="POST" action="<?= url('nomina/periodos/' . (int) $pd['id'] . '/anular') ?>" class="pd-anular-form"
              data-ms-confirm data-ms-type="warning" data-ms-icon="trash"
              data-ms-title="Anular periodo"
              data-ms-msg="Se anulará el snapshot y sus créditos de ledger. Los pagos ya hechos deben revertirse antes. ¿Anular?"
              data-ms-ok="Anular">
            <?= csrf_field() ?>
            <input type="text" name="motivo" required maxlength="255" placeholder="Motivo de anulación (obligatorio)">
            <button type="submit" class="pd-btn pd-btn-danger ms-pressable"><i class="fas fa-ban"></i> Anular</button>
        </form>
        <?php endif; ?>
        <?php if (($pd['estado'] ?? '') === 'aprobado'): ?>
        <a class="pd-btn ms-pressable" href="<?= url('trabajadores/nomina/periodos/' . (int) $pd['id']) ?>">
            <i class="fas fa-hand-holding-dollar"></i> Registrar pagos en Personal (Caja)
        </a>
        <?php if ($pdPuedeAprobar): ?>
        <form method="POST" action="<?= url('nomina/periodos/' . (int) $pd['id'] . '/recibos/emitir') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="pd-btn ms-pressable"><i class="fas fa-receipt"></i> Emitir recibos internos</button>
        </form>
        <?php endif; ?>
        <?php if ($pdPuedeAnular && $pdPermiteReapertura): ?>
        <form method="POST" action="<?= url('nomina/periodos/' . (int) $pd['id'] . '/reabrir') ?>" class="pd-anular-form"
              data-ms-confirm data-ms-type="warning" data-ms-icon="key"
              data-ms-title="Reabrir periodo"
              data-ms-msg="El periodo volverá a CERRADO (sin aprobación) y sus recibos se cancelarán. El snapshot no se recalcula. ¿Reabrir?"
              data-ms-ok="Reabrir">
            <?= csrf_field() ?>
            <input type="text" name="motivo" required maxlength="255" placeholder="Motivo de reapertura (obligatorio)">
            <button type="submit" class="pd-btn ms-pressable"><i class="fas fa-rotate-left"></i> Reabrir</button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php foreach ($pdDetalles as $d): ?>
    <div class="pd-card">
        <div class="pd-emp">
            <div>
                <strong><?= htmlspecialchars((string) $d['trabajador_nombre']) ?></strong>
                <span class="pd-mini"><?= htmlspecialchars((string) ($d['trabajador_rol'] ?? '')) ?></span>
                <div class="pd-mini">
                    Percepciones $<?= number_format((float) $d['conceptos_a_favor'], 2) ?> ·
                    Deducciones $<?= number_format((float) $d['conceptos_en_contra'], 2) ?> ·
                    Anticipos/préstamos $<?= number_format((float) $d['deducciones_informativas'], 2) ?> ·
                    Pagado $<?= number_format((float) $d['pagos_caja_aplicados'], 2) ?>
                </div>
                <?php if (!empty($d['motivo_bloqueo_nomina'])): ?>
                <div class="pd-mini" style="color:#9a7400;"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars((string) $d['motivo_bloqueo_nomina']) ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <div class="pd-neto">$<?= number_format((float) $d['neto_sugerido'], 2) ?></div>
                <div class="pd-mini">pendiente $<?= number_format((float) $d['pendiente_pago_sugerido'], 2) ?></div>
                <?php $pdRecibo = $pdRecibos[(int) $d['id']] ?? null; ?>
                <?php if ($pdRecibo && $pdPuedeVerRecibos): ?>
                <div class="pd-mini" style="margin-top:4px;">
                    <a href="<?= url('nomina/recibos/' . (int) $pdRecibo['id'] . '/pdf') ?>">
                        <i class="fas fa-file-pdf"></i> Recibo <?= htmlspecialchars((string) $pdRecibo['folio']) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php $lineas = $pdLineas[(int) $d['id']] ?? []; ?>
        <?php if ($lineas !== []): ?>
        <details class="pd-lineas">
            <summary><?= count($lineas) ?> concepto(s) congelado(s)</summary>
            <table class="pd-tabla">
                <thead><tr><th>Concepto</th><th>Clasificación</th><th>Origen</th><th>Cant.</th><th>Monto</th></tr></thead>
                <tbody>
                    <?php foreach ($lineas as $l): ?>
                    <tr>
                        <td><?= $l['tipo'] === 'deduccion' ? '−' : '+' ?> <?= htmlspecialchars((string) $l['concepto_nombre']) ?></td>
                        <td><?= htmlspecialchars(str_replace('_', ' ', (string) $l['clasificacion'])) ?></td>
                        <td><?= htmlspecialchars((string) $l['origen']) ?></td>
                        <td><?= $l['cantidad'] !== null ? number_format((float) $l['cantidad'], 2) : '—' ?></td>
                        <td style="<?= $l['tipo'] === 'deduccion' ? 'color:#c62828;' : '' ?>">$<?= number_format((float) $l['monto'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </details>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
