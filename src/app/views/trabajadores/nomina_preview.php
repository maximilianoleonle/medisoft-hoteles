<?php
$preview = is_array($preview ?? null) ? $preview : [];
$tablaDisponible = $tablaDisponible ?? false;
$trabajadores = is_array($preview['trabajadores'] ?? null) ? $preview['trabajadores'] : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$filtros = is_array($preview['filtros_normalizados'] ?? null) ? $preview['filtros_normalizados'] : [];
$bloqueos = is_array($preview['bloqueos'] ?? null) ? $preview['bloqueos'] : [];

if (!function_exists('trab_nomina_safe')) {
    function trab_nomina_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_nomina_money')) {
    function trab_nomina_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_nomina_num')) {
    function trab_nomina_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_nomina_date')) {
    function trab_nomina_date($value)
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

$fechaInicio = (string)($filtros['fecha_inicio_raw'] ?? ($filtros['fecha_inicio'] ?? ''));
$fechaFin = (string)($filtros['fecha_fin_raw'] ?? ($filtros['fecha_fin'] ?? ''));
$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$rolLaboral = (string)($filtros['rol_laboral'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
$soloConSaldo = !empty($filtros['solo_con_saldo']);
$incluirPagosCaja = !empty($filtros['incluir_pagos_caja']);
$exportQuery = http_build_query([
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : '',
    'buscar' => $buscar,
    'rol_laboral' => $rolLaboral,
    'estado' => $estado,
    'solo_con_saldo' => $soloConSaldo ? '1' : '0',
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
$exportUrl = url('trabajadores/nomina/preview/exportar' . ($exportQuery !== '' ? '?' . $exportQuery : ''));
?>

<style>
.payroll-preview {
    --payroll-brand: var(--brand-primary, #1f3f46);
    --payroll-accent: var(--brand-accent, #b58a3c);
    --payroll-line: color-mix(in srgb, var(--payroll-brand) 10%, #e5e7eb);
    --payroll-soft: color-mix(in srgb, var(--payroll-accent) 7%, #f8fafc);
    color: #243142;
}
.payroll-preview .payroll-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--payroll-brand) 92%, #111827), color-mix(in srgb, var(--payroll-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.payroll-preview .payroll-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.payroll-preview .payroll-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.payroll-preview .payroll-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.payroll-preview .payroll-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.payroll-preview .payroll-panel {
    border: 1px solid var(--payroll-line);
    background: rgba(255,255,255,.94);
}
.payroll-preview .payroll-stat {
    border: 1px solid var(--payroll-line);
    background: #fff;
    padding: 14px;
}
.payroll-preview .payroll-stat-soft {
    background: var(--payroll-soft);
}
.payroll-preview .payroll-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--payroll-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.payroll-preview .payroll-btn-primary {
    background: var(--payroll-brand);
    border-color: var(--payroll-brand);
    color: #fff;
}
.payroll-preview .payroll-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--payroll-line);
    background: #fff;
    padding: 0 12px;
}
.payroll-preview .payroll-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    border: 1px solid var(--payroll-line);
    background: #fff;
    padding: 0 12px;
    font-weight: 800;
    color: #334155;
}
.payroll-preview .payroll-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.payroll-preview .payroll-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--payroll-line);
    background: var(--payroll-soft);
    font-size: .78rem;
    font-weight: 800;
}
.payroll-preview .payroll-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.payroll-preview .payroll-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.payroll-preview .payroll-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.payroll-preview .payroll-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.payroll-preview .payroll-table td,
.payroll-preview .payroll-table th {
    border-bottom: 1px solid var(--payroll-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="payroll-preview">
    <section class="payroll-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="payroll-kicker">Personal / Pre-nomina</div>
                <h1 class="payroll-title">Preview de nomina por periodo</h1>
                <p class="payroll-subtitle">
                    Lectura operativa de bruto, deducciones informativas, pagos Caja aplicados y neto sugerido. No genera nomina oficial, no paga y no modifica Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Trabajadores</div>
                    <div class="text-2xl font-black"><?= trab_nomina_num($resumen['trabajadores_total'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Por pagar</div>
                    <div class="text-2xl font-black"><?= trab_nomina_num($resumen['por_pagar_count'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Neto sugerido</div>
                    <div class="text-xl font-black"><?= trab_nomina_money($resumen['neto_sugerido_total'] ?? 0) ?></div>
                </div>
                <div class="payroll-stat-hero">
                    <div class="text-xs opacity-75">Pendiente</div>
                    <div class="text-xl font-black"><?= trab_nomina_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="payroll-btn" href="<?= url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Personal
                </a>
                <a class="payroll-btn" href="<?= url('trabajadores/reporte') ?>">
                    <i class="fas fa-chart-pie"></i>
                    Reporte general
                </a>
                <a class="payroll-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>">
                    <i class="fas fa-file-invoice-dollar"></i>
                    Reporte Caja
                </a>
                <a class="payroll-btn" href="<?= $exportUrl ?>">
                    <i class="fas fa-file-csv"></i>
                    Exportar CSV
                </a>
            </div>
            <span class="payroll-badge">
                <i class="fas fa-lock"></i>
                Solo GET / Read-only
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="payroll-panel p-5">
                <strong>Preview no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas laborales o de Caja para calcular la pre-nomina con seguridad.</p>
            </div>
        <?php else: ?>
            <div class="payroll-panel p-4">
                <form method="GET" action="<?= url('trabajadores/nomina/preview') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[150px_150px_120px_minmax(180px,1fr)_150px_150px_auto_auto_auto] gap-3">
                    <input class="payroll-input" type="date" name="fecha_inicio" value="<?= trab_nomina_safe($fechaInicio, '') ?>">
                    <input class="payroll-input" type="date" name="fecha_fin" value="<?= trab_nomina_safe($fechaFin, '') ?>">
                    <input class="payroll-input" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="ID">
                    <input class="payroll-input" type="search" name="buscar" value="<?= trab_nomina_safe($buscar, '') ?>" placeholder="Buscar trabajador">
                    <input class="payroll-input" type="search" name="rol_laboral" value="<?= trab_nomina_safe($rolLaboral, '') ?>" placeholder="Rol">
                    <select class="payroll-input" name="estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="baja" <?= $estado === 'baja' ? 'selected' : '' ?>>Baja</option>
                    </select>
                    <label class="payroll-check">
                        <input type="hidden" name="solo_con_saldo" value="0">
                        <input type="checkbox" name="solo_con_saldo" value="1" <?= $soloConSaldo ? 'checked' : '' ?>>
                        Con saldo
                    </label>
                    <label class="payroll-check">
                        <input type="hidden" name="incluir_pagos_caja" value="0">
                        <input type="checkbox" name="incluir_pagos_caja" value="1" <?= $incluirPagosCaja ? 'checked' : '' ?>>
                        Pagos Caja
                    </label>
                    <button class="payroll-btn payroll-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Preview
                    </button>
                </form>
            </div>

            <?php if (!empty($bloqueos)): ?>
                <div class="payroll-panel p-5 bg-slate-50">
                    <strong>Periodo requerido para el preview.</strong>
                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($bloqueos as $bloqueo): ?>
                            <div><i class="fas fa-circle-info mr-2"></i><?= trab_nomina_safe($bloqueo) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                    <div class="payroll-stat payroll-stat-soft">
                        <div class="payroll-label">Bruto periodo</div>
                        <div class="text-2xl font-black mt-1"><?= trab_nomina_money($resumen['bruto_total'] ?? 0) ?></div>
                    </div>
                    <div class="payroll-stat">
                        <div class="payroll-label">Deducciones info</div>
                        <div class="text-2xl font-black mt-1"><?= trab_nomina_money($resumen['deducciones_total'] ?? 0) ?></div>
                    </div>
                    <div class="payroll-stat">
                        <div class="payroll-label">Pagos Caja</div>
                        <div class="text-2xl font-black mt-1"><?= trab_nomina_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?></div>
                    </div>
                    <div class="payroll-stat">
                        <div class="payroll-label">Reversiones</div>
                        <div class="text-2xl font-black mt-1"><?= trab_nomina_money($resumen['reversiones_detectadas_total'] ?? 0) ?></div>
                    </div>
                    <div class="payroll-stat payroll-stat-soft">
                        <div class="payroll-label">Pendiente sugerido</div>
                        <div class="text-2xl font-black mt-1"><?= trab_nomina_money($resumen['pendiente_pago_total'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="payroll-panel overflow-hidden">
                    <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-black text-lg">Detalle por trabajador</h2>
                            <p class="text-sm text-slate-500 mt-1">Periodo <?= trab_nomina_date($filtros['fecha_inicio'] ?? null) ?> - <?= trab_nomina_date($filtros['fecha_fin'] ?? null) ?></p>
                        </div>
                        <span class="payroll-badge">
                            <i class="fas fa-list-check"></i>
                            <?= trab_nomina_num(count($trabajadores)) ?> visible(s)
                        </span>
                    </div>

                    <?php if (empty($trabajadores)): ?>
                        <div class="p-8 text-center">
                            <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-clipboard-list"></i></div>
                            <h3 class="font-black text-lg">Sin trabajadores para este preview</h3>
                            <p class="text-sm text-slate-500 mt-1">Ajusta periodo o filtros para revisar trabajadores del hotel actual.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="payroll-table min-w-full text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-left">Trabajador</th>
                                        <th class="text-left">Estado</th>
                                        <th class="text-right">Bruto</th>
                                        <th class="text-right">Deducciones</th>
                                        <th class="text-right">Pagos Caja</th>
                                        <th class="text-right">Neto sugerido</th>
                                        <th class="text-left">Lectura</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($trabajadores as $trabajador): ?>
                                        <?php
                                            $estadoPreview = (string)($trabajador['estado_preview_nomina'] ?? '');
                                            $badgeClass = 'payroll-badge';
                                            if ($estadoPreview === 'por_pagar') {
                                                $badgeClass .= ' payroll-badge-ok';
                                            } elseif ($estadoPreview === 'bloqueado') {
                                                $badgeClass .= ' payroll-badge-danger';
                                            } elseif ($estadoPreview === 'cubierto') {
                                                $badgeClass .= ' payroll-badge-warn';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <a class="font-black underline" href="<?= url('trabajadores/' . (int)($trabajador['id'] ?? 0)) ?>">
                                                    <?= trab_nomina_safe($trabajador['nombre_completo'] ?? null) ?>
                                                </a>
                                                <div class="text-xs text-slate-500"><?= trab_nomina_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></div>
                                            </td>
                                            <td>
                                                <span class="<?= $badgeClass ?>">
                                                    <i class="fas fa-circle"></i>
                                                    <?= trab_nomina_safe(str_replace('_', ' ', $estadoPreview)) ?>
                                                </span>
                                                <div class="text-xs text-slate-500 mt-2"><?= trab_nomina_safe($trabajador['estado'] ?? null) ?></div>
                                            </td>
                                            <td class="text-right">
                                                <div class="font-black"><?= trab_nomina_money($trabajador['bruto_periodo'] ?? 0) ?></div>
                                                <div class="text-xs text-slate-500">
                                                    +<?= trab_nomina_money($trabajador['conceptos_a_favor'] ?? 0) ?> / -<?= trab_nomina_money($trabajador['conceptos_en_contra'] ?? 0) ?>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="font-black"><?= trab_nomina_money($trabajador['deducciones_informativas'] ?? 0) ?></div>
                                                <div class="text-xs text-slate-500">
                                                    Ant. <?= trab_nomina_money($trabajador['anticipos_saldo'] ?? 0) ?> / Prest. <?= trab_nomina_money($trabajador['prestamos_saldo'] ?? 0) ?>
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="font-black"><?= trab_nomina_money($trabajador['pagos_caja_aplicados'] ?? 0) ?></div>
                                                <div class="text-xs text-slate-500">
                                                    <?= trab_nomina_num($trabajador['pagos_caja_pagados'] ?? 0) ?> pago(s), <?= trab_nomina_num($trabajador['pagos_caja_revertidos'] ?? 0) ?> rev.
                                                </div>
                                            </td>
                                            <td class="text-right">
                                                <div class="font-black"><?= trab_nomina_money($trabajador['neto_sugerido'] ?? 0) ?></div>
                                                <div class="text-xs text-slate-500">Pendiente <?= trab_nomina_money($trabajador['pendiente_pago_sugerido'] ?? 0) ?></div>
                                            </td>
                                            <td>
                                                <?php if (trim((string)($trabajador['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                                                    <div class="text-xs text-red-700 font-bold"><?= trab_nomina_safe($trabajador['motivo_bloqueo_nomina'] ?? '') ?></div>
                                                <?php else: ?>
                                                    <div class="text-xs text-slate-500">
                                                        Conceptos <?= trab_nomina_num($trabajador['conceptos_count'] ?? 0) ?>,
                                                        reversiones <?= trab_nomina_money($trabajador['reversiones_detectadas'] ?? 0) ?>.
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>
