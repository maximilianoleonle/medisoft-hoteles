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
            return 'nom-det-badge nom-det-badge-ok';
        }
        if ($estado === 'anulado') {
            return 'nom-det-badge nom-det-badge-danger';
        }
        return 'nom-det-badge nom-det-badge-warn';
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
?>

<style>
.nomina-detalle {
    --nom-brand: var(--brand-primary, #1f3f46);
    --nom-accent: var(--brand-accent, #b58a3c);
    --nom-line: color-mix(in srgb, var(--nom-brand) 10%, #e5e7eb);
    --nom-soft: color-mix(in srgb, var(--nom-accent) 7%, #f8fafc);
    color: #243142;
}
.nomina-detalle .nom-det-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--nom-brand) 92%, #111827), color-mix(in srgb, var(--nom-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.nomina-detalle .nom-det-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .78;
    font-weight: 800;
}
.nomina-detalle .nom-det-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.nomina-detalle .nom-det-panel,
.nomina-detalle .nom-det-card {
    border: 1px solid var(--nom-line);
    background: rgba(255,255,255,.95);
}
.nomina-detalle .nom-det-stat {
    border: 1px solid var(--nom-line);
    background: #fff;
    padding: 14px;
}
.nomina-detalle .nom-det-stat-soft {
    background: var(--nom-soft);
}
.nomina-detalle .nom-det-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--nom-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.nomina-detalle .nom-det-btn-primary {
    background: var(--nom-brand);
    border-color: var(--nom-brand);
    color: #fff;
}
.nomina-detalle .nom-det-btn-danger {
    background: #991b1b;
    border-color: #991b1b;
    color: #fff;
}
.nomina-detalle .nom-det-input {
    width: 100%;
    min-height: 38px;
    border: 1px solid var(--nom-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-detalle .nom-det-select {
    width: 100%;
    min-height: 38px;
    border: 1px solid var(--nom-line);
    background: #fff;
    padding: 0 12px;
}
.nomina-detalle .nom-det-paybox {
    min-width: 280px;
}
.nomina-detalle .nom-det-payfacts {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
}
.nomina-detalle .nom-det-payfact {
    border: 1px solid var(--nom-line);
    background: color-mix(in srgb, var(--nom-brand) 4%, #f8fafc);
    padding: 7px 8px;
}
.nomina-detalle .nom-det-payfact span {
    display: block;
    color: #64748b;
    font-size: .64rem;
    font-weight: 900;
    letter-spacing: .05em;
    line-height: 1.1;
    text-transform: uppercase;
}
.nomina-detalle .nom-det-payfact strong {
    display: block;
    margin-top: 3px;
    color: #243142;
    font-size: .86rem;
    font-weight: 900;
}
.nomina-detalle .nom-det-payfacts-muted {
    opacity: .82;
}
.nomina-detalle .nom-det-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.nomina-detalle .nom-det-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--nom-line);
    background: var(--nom-soft);
    font-size: .78rem;
    font-weight: 800;
}
.nomina-detalle .nom-det-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.nomina-detalle .nom-det-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.nomina-detalle .nom-det-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.nomina-detalle .nom-det-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.nomina-detalle .nom-det-table td,
.nomina-detalle .nom-det-table th {
    border-bottom: 1px solid var(--nom-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="nomina-detalle">
    <section class="nom-det-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="nom-det-kicker">Personal / Snapshot pre-nomina</div>
                <h1 class="nom-det-title">Periodo #<?= (int)($periodo['id'] ?? 0) ?></h1>
                <p class="mt-2 max-w-4xl text-white/85">
                    Snapshot administrativo de pre-nomina. No genera nomina oficial, pago, CFDI, timbrado, dispersion ni movimiento de Caja.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="<?= trab_nom_det_status_class($periodo['estado'] ?? '') ?>">
                    <i class="fas fa-circle"></i>
                    <?= trab_nom_det_status_label($periodo['estado'] ?? '') ?>
                </span>
                <span class="nom-det-badge">
                    <i class="fas fa-lock"></i>
                    Pago individual controlado
                </span>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="nom-det-btn" href="<?= back_url('trabajadores/nomina/periodos') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Periodos
                </a>
                <a class="nom-det-btn" href="<?= url('trabajadores/nomina/preview?fecha_inicio=' . urlencode((string)($periodo['fecha_inicio'] ?? '')) . '&fecha_fin=' . urlencode((string)($periodo['fecha_fin'] ?? ''))) ?>">
                    <i class="fas fa-table-list"></i>
                    Preview actual
                </a>
                <a class="nom-det-btn" href="<?= url('trabajadores/nomina/periodos/pagos-snapshot?periodo_id=' . (int)($periodo['id'] ?? 0)) ?>">
                    <i class="fas fa-link"></i>
                    Conciliacion pagos
                </a>
            </div>
        </div>

        <?php if (!$tablaPersistenteDisponible): ?>
            <div class="nom-det-panel p-5">
                <strong>Snapshot no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas de persistencia de pre-nomina.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_380px] gap-4">
                <div class="space-y-4">
                    <div class="nom-det-panel p-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div>
                                <div class="nom-det-label">Etiqueta</div>
                                <div class="font-black"><?= trab_nom_det_safe($periodo['etiqueta'] ?? 'Periodo') ?></div>
                            </div>
                            <div>
                                <div class="nom-det-label">Rango</div>
                                <div class="font-black"><?= trab_nom_det_date($periodo['fecha_inicio'] ?? '') ?> - <?= trab_nom_det_date($periodo['fecha_fin'] ?? '') ?></div>
                            </div>
                            <div>
                                <div class="nom-det-label">Tipo</div>
                                <div class="font-black"><?= trab_nom_det_safe($periodo['tipo_periodo'] ?? 'manual') ?></div>
                            </div>
                            <div>
                                <div class="nom-det-label">Cerrado por</div>
                                <div class="font-black"><?= trab_nom_det_safe($periodo['cerrado_por_nombre'] ?? 'Usuario') ?></div>
                                <div class="text-xs text-slate-500"><?= trab_nom_det_safe($periodo['cerrado_at'] ?? '-') ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                        <div class="nom-det-stat nom-det-stat-soft">
                            <div class="nom-det-label">Trabajadores</div>
                            <div class="text-2xl font-black mt-1"><?= trab_nom_det_num($periodo['trabajadores_total'] ?? 0) ?></div>
                        </div>
                        <div class="nom-det-stat">
                            <div class="nom-det-label">Bruto</div>
                            <div class="text-2xl font-black mt-1"><?= trab_nom_det_money($periodo['bruto_total'] ?? 0) ?></div>
                        </div>
                        <div class="nom-det-stat">
                            <div class="nom-det-label">Deducciones</div>
                            <div class="text-2xl font-black mt-1"><?= trab_nom_det_money($periodo['deducciones_total'] ?? 0) ?></div>
                        </div>
                        <div class="nom-det-stat nom-det-stat-soft">
                            <div class="nom-det-label">Pendiente</div>
                            <div class="text-2xl font-black mt-1"><?= trab_nom_det_money($periodo['pendiente_pago_total'] ?? 0) ?></div>
                        </div>
                    </div>

                    <div class="nom-det-panel overflow-hidden">
                        <div class="p-5 border-b border-slate-200">
                            <h2 class="font-black text-lg">Detalle congelado por trabajador</h2>
                            <p class="text-sm text-slate-500 mt-1">Estos importes son la fotografia guardada al cerrar el periodo.</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="nom-det-table min-w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left">Trabajador</th>
                                        <th class="text-left">Estado</th>
                                        <th class="text-right">Bruto</th>
                                        <th class="text-right">Deducciones</th>
                                        <th class="text-right">Caja aplicada</th>
                                        <th class="text-right">Neto</th>
                                        <th class="text-right">Pendiente</th>
                                        <th class="text-left">Pago Caja</th>
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
                                            <td>
                                                <a class="font-black underline" href="<?= url('trabajadores/' . (int)($detalle['trabajador_id'] ?? 0)) ?>">
                                                    <?= trab_nom_det_safe($detalle['trabajador_nombre'] ?? 'Trabajador') ?>
                                                </a>
                                                <div class="text-xs text-slate-500"><?= trab_nom_det_safe($detalle['trabajador_rol'] ?? 'Sin rol') ?></div>
                                            </td>
                                            <td>
                                                <span class="nom-det-badge">
                                                    <i class="fas fa-circle"></i>
                                                    <?= trab_nom_det_safe(str_replace('_', ' ', (string)($detalle['estado_preview_nomina'] ?? ''))) ?>
                                                </span>
                                                <?php if (trim((string)($detalle['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                                    <div class="text-xs text-red-700 font-bold mt-2"><?= trab_nom_det_safe($detalle['motivo_bloqueo_nomina'] ?? '') ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right font-black"><?= trab_nom_det_money($detalle['bruto_periodo'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_nom_det_money($detalle['deducciones_informativas'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_nom_det_money($detalle['pagos_caja_aplicados'] ?? 0) ?></td>
                                            <td class="text-right font-black"><?= trab_nom_det_money($detalle['neto_sugerido'] ?? 0) ?></td>
                                            <td class="text-right font-black"><?= trab_nom_det_money($detalle['pendiente_pago_sugerido'] ?? 0) ?></td>
                                            <td>
                                                <?php if ((string)($periodo['estado'] ?? '') !== 'aprobado'): ?>
                                                    <span class="nom-det-badge nom-det-badge-warn">
                                                        <i class="fas fa-lock"></i>
                                                        Requiere aprobacion
                                                    </span>
                                                <?php elseif ($evaluacionPago && !empty($evaluacionPago['elegible']) && $pagoToken): ?>
                                                    <form class="nom-det-paybox space-y-2" method="POST" action="<?= url('trabajadores/nomina/periodos/' . (int)($periodo['id'] ?? 0) . '/detalles/' . $detalleId . '/registrar-pago-caja') ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="pago_token" value="<?= trab_nom_det_safe($pagoToken, '') ?>">
                                                        <div class="nom-det-payfacts">
                                                            <div class="nom-det-payfact">
                                                                <span>Snapshot</span>
                                                                <strong><?= trab_nom_det_money($snapshotPendientePago) ?></strong>
                                                            </div>
                                                            <div class="nom-det-payfact">
                                                                <span>Saldo vivo</span>
                                                                <strong><?= trab_nom_det_money($saldoVivoPago) ?></strong>
                                                            </div>
                                                            <div class="nom-det-payfact">
                                                                <span>Maximo</span>
                                                                <strong><?= trab_nom_det_money($montoMaximoPago) ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                            <div>
                                                                <label class="nom-det-label" for="snapshot_pago_monto_<?= $detalleId ?>">Monto</label>
                                                                <input
                                                                    class="nom-det-input"
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
                                                                <label class="nom-det-label" for="snapshot_pago_metodo_<?= $detalleId ?>">Metodo</label>
                                                                <select class="nom-det-select" id="snapshot_pago_metodo_<?= $detalleId ?>" name="metodo_pago" required>
                                                                    <?php foreach ($metodosPago as $metodoKey => $metodoLabel): ?>
                                                                        <option value="<?= trab_nom_det_safe($metodoKey, '') ?>"><?= trab_nom_det_safe($metodoLabel, '') ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <input class="nom-det-input" type="text" name="referencia" maxlength="100" required placeholder="Referencia obligatoria">
                                                        <input class="nom-det-input" type="text" name="notas" maxlength="700" placeholder="Notas opcionales">
                                                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                                            <span>Maximo <?= trab_nom_det_money($montoMaximoPago) ?></span>
                                                            <button class="nom-det-btn nom-det-btn-primary" type="submit">
                                                                <i class="fas fa-cash-register"></i>
                                                                Registrar pago
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="nom-det-badge nom-det-badge-danger">
                                                        <i class="fas fa-ban"></i>
                                                        Bloqueado
                                                    </span>
                                                    <div class="text-xs text-slate-500 mt-2">
                                                        <?= trab_nom_det_safe($evaluacionPago['motivo_bloqueo'] ?? 'No elegible para pago con Caja') ?>
                                                    </div>
                                                    <?php if ($evaluacionPago): ?>
                                                        <div class="nom-det-payfacts nom-det-payfacts-muted mt-2">
                                                            <div class="nom-det-payfact">
                                                                <span>Snapshot</span>
                                                                <strong><?= trab_nom_det_money($snapshotPendientePago) ?></strong>
                                                            </div>
                                                            <div class="nom-det-payfact">
                                                                <span>Saldo vivo</span>
                                                                <strong><?= trab_nom_det_money($saldoVivoPago) ?></strong>
                                                            </div>
                                                            <div class="nom-det-payfact">
                                                                <span>Maximo</span>
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
                </div>

                <aside class="space-y-4">
                    <div class="nom-det-panel p-5">
                        <h2 class="font-black text-lg">Acciones controladas</h2>
                        <p class="text-sm text-slate-500 mt-1">Cambian solo el estado administrativo del snapshot.</p>
                        <div class="mt-4 space-y-3">
                            <?php if ((string)($periodo['estado'] ?? '') === 'cerrado' && $aprobarToken): ?>
                                <form method="POST" action="<?= url('trabajadores/nomina/periodos/' . (int)($periodo['id'] ?? 0) . '/aprobar') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_token" value="<?= trab_nom_det_safe($aprobarToken, '') ?>">
                                    <button class="nom-det-btn nom-det-btn-primary w-full" type="submit">
                                        <i class="fas fa-check"></i>
                                        Aprobar snapshot
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ((string)($periodo['estado'] ?? '') !== 'anulado' && $anularToken): ?>
                                <form method="POST" action="<?= url('trabajadores/nomina/periodos/' . (int)($periodo['id'] ?? 0) . '/anular') ?>" class="space-y-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="periodo_token" value="<?= trab_nom_det_safe($anularToken, '') ?>">
                                    <input class="nom-det-input" type="text" name="motivo" maxlength="255" required placeholder="Motivo de anulacion">
                                    <button class="nom-det-btn nom-det-btn-danger w-full" type="submit">
                                        <i class="fas fa-ban"></i>
                                        Anular snapshot
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ((string)($periodo['estado'] ?? '') === 'anulado'): ?>
                                <div class="nom-det-badge nom-det-badge-danger">
                                    <i class="fas fa-lock"></i>
                                    Snapshot anulado
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="nom-det-panel p-5">
                        <h2 class="font-black text-lg">Control</h2>
                        <div class="mt-3 space-y-3 text-sm">
                            <div>
                                <div class="nom-det-label">Pagos Caja aplicados</div>
                                <div class="font-black"><?= trab_nom_det_money($periodo['pagos_caja_aplicados_total'] ?? 0) ?></div>
                            </div>
                            <div>
                                <div class="nom-det-label">Reversiones detectadas</div>
                                <div class="font-black"><?= trab_nom_det_money($periodo['reversiones_detectadas_total'] ?? 0) ?></div>
                            </div>
                            <?php if ((string)($periodo['estado'] ?? '') === 'aprobado'): ?>
                                <div>
                                    <div class="nom-det-label">Aprobado por</div>
                                    <div class="font-black"><?= trab_nom_det_safe($periodo['aprobado_por_nombre'] ?? 'Usuario') ?></div>
                                    <div class="text-xs text-slate-500"><?= trab_nom_det_safe($periodo['aprobado_at'] ?? '-') ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ((string)($periodo['estado'] ?? '') === 'anulado'): ?>
                                <div>
                                    <div class="nom-det-label">Motivo anulacion</div>
                                    <div class="font-black"><?= trab_nom_det_safe($periodo['motivo_anulacion'] ?? '-') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="nom-det-panel overflow-hidden">
                        <div class="p-5 border-b border-slate-200">
                            <h2 class="font-black text-lg">Eventos</h2>
                        </div>
                        <div class="divide-y divide-slate-200">
                            <?php foreach ($eventos as $evento): ?>
                                <div class="p-4">
                                    <div class="font-black"><?= trab_nom_det_safe($evento['descripcion'] ?? 'Evento') ?></div>
                                    <div class="text-xs text-slate-500 mt-1">
                                        <?= trab_nom_det_safe($evento['created_at'] ?? '-') ?> - <?= trab_nom_det_safe($evento['creado_por_nombre'] ?? ($evento['creado_por_login'] ?? 'Usuario')) ?>
                                    </div>
                                    <?php if (trim((string)($evento['motivo'] ?? '')) !== ''): ?>
                                        <div class="text-xs text-slate-600 mt-2"><?= trab_nom_det_safe($evento['motivo'] ?? '') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </section>
</div>
