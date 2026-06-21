<?php
$reporte = is_array($reporte ?? null) ? $reporte : [];
$tablaDisponible = $tablaDisponible ?? false;
$registros = is_array($reporte['registros'] ?? null) ? $reporte['registros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$porEstado = is_array($reporte['por_estado'] ?? null) ? $reporte['por_estado'] : [];
$porMetodo = is_array($reporte['por_metodo'] ?? null) ? $reporte['por_metodo'] : [];
$porCorte = is_array($reporte['por_corte'] ?? null) ? $reporte['por_corte'] : [];
$filtros = is_array($reporte['filtros_normalizados'] ?? null) ? $reporte['filtros_normalizados'] : [];

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

if (!function_exists('trab_cash_report_date')) {
    function trab_cash_report_date($value)
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

$trabajadorId = (int)($filtros['trabajador_id'] ?? 0);
$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'todos');
$metodoPago = (string)($filtros['metodo_pago'] ?? 'todos');
$corteId = (int)($filtros['corte_id'] ?? 0);
$fechaInicio = (string)($filtros['fecha_inicio'] ?? '');
$fechaFin = (string)($filtros['fecha_fin'] ?? '');
?>

<style>
.labor-cash-report {
    --labor-cash-brand: var(--brand-primary, #1f3f46);
    --labor-cash-accent: var(--brand-accent, #b58a3c);
    --labor-cash-line: color-mix(in srgb, var(--labor-cash-brand) 10%, #e5e7eb);
    --labor-cash-soft: color-mix(in srgb, var(--labor-cash-accent) 7%, #f8fafc);
    color: #243142;
}
.labor-cash-report .report-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--labor-cash-brand) 92%, #111827), color-mix(in srgb, var(--labor-cash-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.labor-cash-report .report-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.labor-cash-report .report-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.labor-cash-report .report-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.labor-cash-report .report-stat-hero {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.labor-cash-report .report-panel {
    border: 1px solid var(--labor-cash-line);
    background: rgba(255,255,255,.94);
}
.labor-cash-report .report-stat {
    border: 1px solid var(--labor-cash-line);
    background: #fff;
    padding: 14px;
}
.labor-cash-report .report-stat-soft {
    background: var(--labor-cash-soft);
}
.labor-cash-report .report-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--labor-cash-line);
    background: #fff;
    color: #334155;
    font-weight: 800;
    white-space: nowrap;
}
.labor-cash-report .report-btn-primary {
    background: var(--labor-cash-brand);
    border-color: var(--labor-cash-brand);
    color: #fff;
}
.labor-cash-report .report-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--labor-cash-line);
    background: #fff;
    padding: 0 12px;
}
.labor-cash-report .report-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.labor-cash-report .report-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--labor-cash-line);
    background: var(--labor-cash-soft);
    font-size: .78rem;
    font-weight: 800;
}
.labor-cash-report .report-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.labor-cash-report .report-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.labor-cash-report .report-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.labor-cash-report .report-table td,
.labor-cash-report .report-table th {
    border-bottom: 1px solid var(--labor-cash-line);
    padding: 13px 12px;
    vertical-align: top;
}
</style>

<div class="labor-cash-report">
    <section class="report-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="report-kicker">Personal / Caja</div>
                <h1 class="report-title">Reporte de pagos laborales con Caja</h1>
                <p class="report-subtitle">
                    Cuadre read-only de egresos laborales, reversiones, cortes, metodos y trabajadores. No registra pagos, no revierte movimientos y no modifica Caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="report-stat-hero">
                    <div class="text-xs opacity-75">Registros</div>
                    <div class="text-2xl font-black"><?= trab_cash_report_num($resumen['total_registros'] ?? 0) ?></div>
                </div>
                <div class="report-stat-hero">
                    <div class="text-xs opacity-75">Pagados</div>
                    <div class="text-2xl font-black"><?= trab_cash_report_num($resumen['pagados_count'] ?? 0) ?></div>
                </div>
                <div class="report-stat-hero">
                    <div class="text-xs opacity-75">Revertidos</div>
                    <div class="text-2xl font-black"><?= trab_cash_report_num($resumen['revertidos_count'] ?? 0) ?></div>
                </div>
                <div class="report-stat-hero">
                    <div class="text-xs opacity-75">Impacto neto</div>
                    <div class="text-xl font-black"><?= trab_cash_report_money($resumen['impacto_caja_neto'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="report-btn" href="<?= url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Personal
                </a>
                <a class="report-btn" href="<?= url('trabajadores/reporte') ?>">
                    <i class="fas fa-chart-pie"></i>
                    Reporte general
                </a>
                <a class="report-btn" href="<?= url('trabajadores/pagos-caja/simulador') ?>">
                    <i class="fas fa-cash-register"></i>
                    Simulador Caja
                </a>
            </div>
            <span class="report-badge">
                <i class="fas fa-lock"></i>
                Solo GET / Read-only
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="report-panel p-5">
                <strong>Reporte no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas de Personal o Caja para consultar pagos laborales con seguridad.</p>
            </div>
        <?php else: ?>
            <div class="report-panel p-4">
                <form method="GET" action="<?= url('trabajadores/pagos-caja/reporte') ?>" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[120px_minmax(190px,1fr)_150px_170px_120px_150px_150px_auto_auto] gap-3">
                    <input class="report-input" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="ID">
                    <input class="report-input" type="search" name="buscar" value="<?= trab_cash_report_safe($buscar, '') ?>" placeholder="Buscar trabajador, referencia o caja">
                    <select class="report-input" name="estado">
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="pagado" <?= $estado === 'pagado' ? 'selected' : '' ?>>Pagado</option>
                        <option value="revertido" <?= $estado === 'revertido' ? 'selected' : '' ?>>Revertido</option>
                    </select>
                    <select class="report-input" name="metodo_pago">
                        <option value="todos" <?= $metodoPago === 'todos' ? 'selected' : '' ?>>Todos los metodos</option>
                        <option value="efectivo" <?= $metodoPago === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                        <option value="tarjeta" <?= $metodoPago === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                        <option value="transferencia" <?= $metodoPago === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    </select>
                    <input class="report-input" type="number" min="1" name="corte_id" value="<?= $corteId > 0 ? (int)$corteId : '' ?>" placeholder="Corte">
                    <input class="report-input" type="date" name="fecha_inicio" value="<?= trab_cash_report_safe($fechaInicio, '') ?>">
                    <input class="report-input" type="date" name="fecha_fin" value="<?= trab_cash_report_safe($fechaFin, '') ?>">
                    <button class="report-btn report-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="report-btn" href="<?= url('trabajadores/pagos-caja/reporte') ?>">
                        <i class="fas fa-rotate-left"></i>
                        Limpiar
                    </a>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="report-stat report-stat-soft">
                    <div class="report-label">Pagado vigente</div>
                    <div class="text-2xl font-black mt-1"><?= trab_cash_report_money($resumen['pagado_vigente_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_cash_report_num($resumen['pagados_count'] ?? 0) ?> pago(s) aun aplicados</div>
                </div>
                <div class="report-stat">
                    <div class="report-label">Egreso original</div>
                    <div class="text-2xl font-black mt-1"><?= trab_cash_report_money($resumen['egreso_original_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Suma historica de pagos emitidos</div>
                </div>
                <div class="report-stat">
                    <div class="report-label">Revertido laboral</div>
                    <div class="text-2xl font-black mt-1"><?= trab_cash_report_money($resumen['revertido_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1"><?= trab_cash_report_num($resumen['revertidos_count'] ?? 0) ?> registro(s) revertidos</div>
                </div>
                <div class="report-stat">
                    <div class="report-label">Ingreso por reversion</div>
                    <div class="text-2xl font-black mt-1"><?= trab_cash_report_money($resumen['reversion_caja_total'] ?? 0) ?></div>
                    <div class="text-xs text-slate-500 mt-1">Movimientos Caja de reversion detectados</div>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
                <div class="report-panel overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <h2 class="font-black">Por metodo</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Metodo</th>
                                    <th class="text-right">Pagado</th>
                                    <th class="text-right">Revertido</th>
                                    <th class="text-right">Reversion Caja</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porMetodo)): ?>
                                    <tr><td colspan="4" class="text-center text-slate-500">Sin movimientos.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porMetodo as $item): ?>
                                        <tr>
                                            <td class="font-black"><?= trab_cash_report_safe($item['metodo_pago'] ?? null) ?></td>
                                            <td class="text-right"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_cash_report_money($item['reversion_caja'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-panel overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <h2 class="font-black">Por estado</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Registros</th>
                                    <th class="text-right">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porEstado)): ?>
                                    <tr><td colspan="3" class="text-center text-slate-500">Sin estados.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porEstado as $item): ?>
                                        <tr>
                                            <td>
                                                <span class="report-badge <?= ($item['estado'] ?? '') === 'pagado' ? 'report-badge-ok' : 'report-badge-warn' ?>">
                                                    <?= trab_cash_report_safe($item['estado'] ?? null) ?>
                                                </span>
                                            </td>
                                            <td class="text-right"><?= trab_cash_report_num($item['total'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_cash_report_money($item['monto'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="report-panel overflow-hidden">
                    <div class="p-4 border-b border-slate-200">
                        <h2 class="font-black">Por corte</h2>
                    </div>
                    <div class="overflow-x-auto max-h-[260px]">
                        <table class="report-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Corte</th>
                                    <th class="text-right">Pagado</th>
                                    <th class="text-right">Revertido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($porCorte)): ?>
                                    <tr><td colspan="3" class="text-center text-slate-500">Sin cortes.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($porCorte as $item): ?>
                                        <tr>
                                            <td>
                                                <a class="font-black underline" href="<?= url('caja/corte/' . (int)($item['corte_id'] ?? 0)) ?>">#<?= (int)($item['corte_id'] ?? 0) ?></a>
                                                <div class="text-xs text-slate-500"><?= trab_cash_report_safe($item['caja_nombre'] ?? null) ?> / <?= trab_cash_report_safe($item['corte_estado'] ?? null) ?></div>
                                            </td>
                                            <td class="text-right"><?= trab_cash_report_money($item['pagado_vigente'] ?? 0) ?></td>
                                            <td class="text-right"><?= trab_cash_report_money($item['revertido'] ?? 0) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="report-panel overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-black text-lg">Movimientos laborales con Caja</h2>
                        <p class="text-sm text-slate-500 mt-1">Listado limitado a los registros filtrados del hotel actual.</p>
                    </div>
                    <span class="report-badge">
                        <i class="fas fa-list-check"></i>
                        <?= trab_cash_report_num(count($registros)) ?> visible(s)
                    </span>
                </div>

                <?php if (empty($registros)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-receipt"></i></div>
                        <h3 class="font-black text-lg">Sin pagos laborales con Caja</h3>
                        <p class="text-sm text-slate-500 mt-1">Ajusta los filtros o registra pagos desde la ficha del trabajador cuando corresponda.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Fecha</th>
                                    <th class="text-left">Trabajador</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-left">Caja / corte</th>
                                    <th class="text-left">Referencia</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-left">Reversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $registro): ?>
                                    <?php
                                        $estadoRegistro = (string)($registro['estado'] ?? '');
                                        $creadoPor = trim((string)($registro['creado_por_nombre'] ?? ''));
                                        if ($creadoPor === '') {
                                            $creadoPor = trim((string)($registro['creado_por_login'] ?? ''));
                                        }
                                        $actualizadoPor = trim((string)($registro['actualizado_por_nombre'] ?? ''));
                                        if ($actualizadoPor === '') {
                                            $actualizadoPor = trim((string)($registro['actualizado_por_login'] ?? ''));
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="font-black"><?= trab_cash_report_datetime($registro['fecha_pago'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500">Registro #<?= (int)($registro['id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <a class="font-black underline" href="<?= url('trabajadores/' . (int)($registro['trabajador_id'] ?? 0)) ?>">
                                                <?= trab_cash_report_safe($registro['trabajador_nombre'] ?? null) ?>
                                            </a>
                                            <div class="text-xs text-slate-500"><?= trab_cash_report_safe($registro['trabajador_rol'] ?? null, 'Sin rol') ?></div>
                                        </td>
                                        <td class="text-right">
                                            <div class="font-black"><?= trab_cash_report_money($registro['monto'] ?? 0) ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_cash_report_safe($registro['metodo_pago'] ?? null) ?></div>
                                        </td>
                                        <td>
                                            <div class="font-black"><?= trab_cash_report_safe($registro['caja_nombre'] ?? null) ?></div>
                                            <a class="text-xs font-black underline text-slate-500" href="<?= url('caja/corte/' . (int)($registro['corte_id'] ?? 0)) ?>">
                                                Corte #<?= (int)($registro['corte_id'] ?? 0) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="font-black"><?= trab_cash_report_safe($registro['referencia'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500">Mov. Caja #<?= (int)($registro['movimiento_caja_id'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <span class="report-badge <?= $estadoRegistro === 'pagado' ? 'report-badge-ok' : 'report-badge-warn' ?>">
                                                <i class="fas <?= $estadoRegistro === 'pagado' ? 'fa-circle-check' : 'fa-rotate-left' ?>"></i>
                                                <?= trab_cash_report_safe($estadoRegistro) ?>
                                            </span>
                                            <div class="text-xs text-slate-500 mt-2">
                                                <?= $creadoPor !== '' ? 'Por ' . trab_cash_report_safe($creadoPor) : 'Usuario no disponible' ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($registro['movimiento_reversion_id'])): ?>
                                                <div class="font-black">Mov. Caja #<?= (int)$registro['movimiento_reversion_id'] ?></div>
                                                <div class="text-xs text-slate-500"><?= trab_cash_report_datetime($registro['movimiento_reversion_created_at'] ?? null) ?></div>
                                                <div class="text-xs text-slate-500"><?= $actualizadoPor !== '' ? 'Por ' . trab_cash_report_safe($actualizadoPor) : 'Usuario no disponible' ?></div>
                                            <?php elseif ($estadoRegistro === 'revertido'): ?>
                                                <span class="report-badge report-badge-warn">
                                                    <i class="fas fa-triangle-exclamation"></i>
                                                    Sin ingreso vinculado
                                                </span>
                                            <?php else: ?>
                                                <span class="report-badge">
                                                    <i class="fas fa-lock"></i>
                                                    Sin reversion
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php if (trim((string)($registro['notas'] ?? '')) !== ''): ?>
                                        <tr>
                                            <td colspan="7" class="text-xs text-slate-500">
                                                <strong>Notas:</strong> <?= trab_cash_report_safe($registro['notas'] ?? null) ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
