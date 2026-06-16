<?php
$reporte = $reporte ?? [];
$tablaDisponible = $tablaDisponible ?? false;
$trabajadores = $reporte['trabajadores'] ?? [];
$ledger = $reporte['ledger'] ?? [];
$asistencias = $reporte['asistencias'] ?? [];
$documentos = $reporte['documentos'] ?? [];
$tareas = $reporte['tareas'] ?? [];
$trabajadoresRelevantes = $reporte['trabajadores_relevantes'] ?? [];

if (!function_exists('trab_report_safe')) {
    function trab_report_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_report_money')) {
    function trab_report_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_report_num')) {
    function trab_report_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}
?>

<style>
.workers-report {
    --trab-brand: var(--brand-primary, #1f3f46);
    --trab-accent: var(--brand-accent, #b58a3c);
    --trab-line: color-mix(in srgb, var(--trab-brand) 10%, #e5e7eb);
    --trab-soft: color-mix(in srgb, var(--trab-accent) 7%, #f8fafc);
    color: #243142;
}
.workers-report .report-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--trab-brand) 92%, #111827), color-mix(in srgb, var(--trab-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.workers-report .report-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.workers-report .report-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.workers-report .report-subtitle {
    margin-top: 8px;
    max-width: 54rem;
    color: rgba(255,255,255,.86);
}
.workers-report .report-panel {
    border: 1px solid var(--trab-line);
    background: rgba(255,255,255,.94);
}
.workers-report .report-stat {
    border: 1px solid var(--trab-line);
    background: #fff;
    padding: 14px;
}
.workers-report .report-stat-muted {
    background: var(--trab-soft);
}
.workers-report .report-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--trab-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
}
.workers-report .report-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.workers-report .report-table td,
.workers-report .report-table th {
    border-bottom: 1px solid var(--trab-line);
    padding: 13px 12px;
}
.workers-report .report-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--trab-line);
    background: var(--trab-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="workers-report">
    <section class="report-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="report-kicker">Administracion / Personal</div>
                <h1 class="report-title">Reporte de Personal</h1>
                <p class="report-subtitle">
                    Consolidado read-only de trabajadores, ledger laboral, asistencia, documentos y tareas asignadas. No genera nomina, pagos, abonos ni movimientos de Caja.
                </p>
            </div>
            <a class="report-btn" href="<?= url('trabajadores') ?>">
                <i class="fas fa-arrow-left"></i>
                Volver a Personal
            </a>
        </div>
    </section>

    <section class="p-6 space-y-5">
        <?php if (!$tablaDisponible): ?>
            <div class="report-panel p-5">
                <strong>Personal no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">La migracion base de Personal aun no esta aplicada en esta instalacion.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="report-stat">
                    <div class="text-xs text-slate-500 font-bold">Trabajadores</div>
                    <div class="text-2xl font-black"><?= trab_report_num($trabajadores['total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500">Activos: <?= trab_report_num($trabajadores['activos'] ?? 0) ?></div>
                </div>
                <div class="report-stat report-stat-muted">
                    <div class="text-xs text-slate-500 font-bold">Saldo laboral informativo</div>
                    <div class="text-2xl font-black"><?= trab_report_money($ledger['saldo_informativo'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500">No representa pago real ni Caja</div>
                </div>
                <div class="report-stat">
                    <div class="text-xs text-slate-500 font-bold">Asistencias</div>
                    <div class="text-2xl font-black"><?= trab_report_num($asistencias['total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500">Ultima: <?= trab_report_safe($asistencias['ultima_fecha'] ?? null, 'Sin registros') ?></div>
                </div>
                <div class="report-stat">
                    <div class="text-xs text-slate-500 font-bold">Documentos laborales</div>
                    <div class="text-2xl font-black"><?= trab_report_num($documentos['total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500">Trabajadores con docs: <?= trab_report_num($documentos['trabajadores_con_documentos'] ?? 0) ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="report-panel p-5">
                    <h2 class="font-black text-lg mb-3">Ledger laboral</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><span>Conceptos</span><strong><?= trab_report_num($ledger['conceptos_count'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>A favor</span><strong><?= trab_report_money($ledger['conceptos_a_favor'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>En contra</span><strong><?= trab_report_money($ledger['conceptos_en_contra'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>Anticipos pendientes</span><strong><?= trab_report_money($ledger['anticipos_saldo'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>Prestamos vigentes</span><strong><?= trab_report_money($ledger['prestamos_saldo'] ?? 0) ?></strong></div>
                    </div>
                </div>

                <div class="report-panel p-5">
                    <h2 class="font-black text-lg mb-3">Asistencia</h2>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <?php foreach (['asistencia', 'falta', 'retardo', 'permiso', 'incapacidad', 'descanso', 'horas_extra'] as $tipo): ?>
                            <div class="report-badge justify-between">
                                <span><?= trab_report_safe(str_replace('_', ' ', $tipo)) ?></span>
                                <strong><?= trab_report_num($asistencias[$tipo] ?? 0) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="report-panel p-5">
                    <h2 class="font-black text-lg mb-3">Tareas asignadas</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><span>Total</span><strong><?= trab_report_num($tareas['total'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>Pendientes</span><strong><?= trab_report_num($tareas['pendiente'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>Asignadas</span><strong><?= trab_report_num($tareas['asignada'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>En proceso</span><strong><?= trab_report_num($tareas['en_proceso'] ?? 0) ?></strong></div>
                        <div class="flex justify-between gap-4"><span>Completadas</span><strong><?= trab_report_num($tareas['completada'] ?? 0) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="report-panel overflow-hidden">
                <div class="p-5 border-b border-slate-200">
                    <h2 class="font-black text-lg">Trabajadores y saldos informativos</h2>
                    <p class="text-sm text-slate-500 mt-1">Lectura limitada a trabajadores del hotel actual. Los saldos no son movimientos de Caja.</p>
                </div>
                <?php if (empty($trabajadoresRelevantes)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-chart-pie"></i></div>
                        <h3 class="font-black text-lg">Sin trabajadores para reportar</h3>
                        <p class="text-sm text-slate-500 mt-1">Cuando existan trabajadores, este reporte mostrara su resumen laboral sin activar flujos financieros.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Trabajador</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">A favor</th>
                                    <th class="text-right">En contra</th>
                                    <th class="text-right">Anticipos</th>
                                    <th class="text-right">Prestamos</th>
                                    <th class="text-right">Saldo informativo</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadoresRelevantes as $trabajador): ?>
                                    <?php $resumen = $trabajador['resumen_laboral'] ?? []; ?>
                                    <tr>
                                        <td>
                                            <strong><?= trab_report_safe($trabajador['nombre_completo'] ?? null) ?></strong>
                                            <div class="text-xs text-slate-500"><?= trab_report_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                        </td>
                                        <td><span class="report-badge"><?= trab_report_safe($trabajador['estado'] ?? null) ?></span></td>
                                        <td class="text-right"><?= trab_report_money($resumen['conceptos_a_favor'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_report_money($resumen['conceptos_en_contra'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_report_money($resumen['anticipos_saldo'] ?? 0) ?></td>
                                        <td class="text-right"><?= trab_report_money($resumen['prestamos_saldo'] ?? 0) ?></td>
                                        <td class="text-right font-black"><?= trab_report_money($resumen['saldo_informativo'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <a class="report-btn" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
