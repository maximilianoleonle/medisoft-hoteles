<?php
$cuentas = $cuentas ?? [];
$resumen = $resumen ?? [];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('cxc_safe')) {
    function cxc_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_money')) {
    function cxc_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estadoReservacion = (string)($filtros['estado_reservacion'] ?? 'todas');
$estadoSaldo = (string)($filtros['estado_saldo'] ?? 'pendiente');
?>

<style>
.cxc-page {
    --cxc-brand: var(--brand-primary, #1f3f46);
    --cxc-accent: var(--brand-accent, #b58a3c);
    --cxc-line: color-mix(in srgb, var(--cxc-brand) 10%, #e5e7eb);
    --cxc-soft: color-mix(in srgb, var(--cxc-accent) 7%, #f8fafc);
    color: #243142;
}
.cxc-page .cxc-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--cxc-brand) 90%, #111827), color-mix(in srgb, var(--cxc-accent) 54%, #47321f));
    color: #fff;
    padding: 28px;
}
.cxc-page .cxc-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .78;
    font-weight: 800;
}
.cxc-page .cxc-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.cxc-page .cxc-subtitle {
    margin-top: 8px;
    max-width: 56rem;
    color: rgba(255,255,255,.86);
}
.cxc-page .cxc-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.cxc-page .cxc-panel {
    border: 1px solid var(--cxc-line);
    background: rgba(255,255,255,.94);
}
.cxc-page .cxc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--cxc-line);
    font-weight: 800;
}
.cxc-page .cxc-btn-primary {
    background: var(--cxc-brand);
    border-color: var(--cxc-brand);
    color: #fff;
}
.cxc-page .cxc-btn-muted {
    background: #fff;
    color: #334155;
}
.cxc-page .cxc-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--cxc-line);
    background: #fff;
    padding: 0 12px;
}
.cxc-page .cxc-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.cxc-page .cxc-table td,
.cxc-page .cxc-table th {
    border-bottom: 1px solid var(--cxc-line);
    padding: 14px 12px;
}
.cxc-page .cxc-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--cxc-line);
    background: var(--cxc-soft);
    font-size: .78rem;
    font-weight: 800;
}
</style>

<div class="cxc-page">
    <section class="cxc-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="cxc-kicker">Reservaciones / Lectura financiera</div>
                <h1 class="cxc-title">Cuentas por cobrar</h1>
                <p class="cxc-subtitle">
                    Reporte derivado de reservaciones, pagos, abonos y facturacion. No crea cobros, no registra pagos, no toca Caja y no modifica reservaciones.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[340px]">
                <div class="cxc-stat">
                    <div class="text-xs opacity-75">Registros</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="cxc-stat">
                    <div class="text-xs opacity-75">Pendientes</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['pendientes'] ?? 0) ?></div>
                </div>
                <div class="cxc-stat">
                    <div class="text-xs opacity-75">Cubierto</div>
                    <div class="text-xl font-black"><?= cxc_money($resumen['total_cubierto'] ?? 0) ?></div>
                </div>
                <div class="cxc-stat">
                    <div class="text-xs opacity-75">Saldo estimado</div>
                    <div class="text-xl font-black"><?= cxc_money($resumen['saldo_estimado'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <?php if (!$tablaDisponible): ?>
            <div class="cxc-panel p-5">
                <strong>Reporte no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan fuentes base de reservaciones, pagos, abonos o facturacion.</p>
            </div>
        <?php else: ?>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <span class="cxc-badge">
                    <i class="fas fa-lock"></i>
                    Solo lectura
                </span>
                <span class="text-sm text-slate-500">Los saldos son estimados y derivados; no son una cuenta contable nueva.</span>
            </div>

            <div class="cxc-panel p-4 mb-4">
                <form method="GET" action="<?= url('cuentas-por-cobrar') ?>" class="grid grid-cols-1 md:grid-cols-[1fr_180px_180px_auto] gap-3">
                    <input class="cxc-input" type="search" name="buscar" value="<?= cxc_safe($buscar, '') ?>" placeholder="Buscar por huesped o reservacion">
                    <select class="cxc-input" name="estado_reservacion">
                        <option value="todas" <?= $estadoReservacion === 'todas' ? 'selected' : '' ?>>Todas</option>
                        <option value="confirmada" <?= $estadoReservacion === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                        <option value="checked_in" <?= $estadoReservacion === 'checked_in' ? 'selected' : '' ?>>Check-in</option>
                        <option value="checked_out" <?= $estadoReservacion === 'checked_out' ? 'selected' : '' ?>>Check-out</option>
                        <option value="cancelada" <?= $estadoReservacion === 'cancelada' ? 'selected' : '' ?>>Canceladas</option>
                    </select>
                    <select class="cxc-input" name="estado_saldo">
                        <option value="todas" <?= $estadoSaldo === 'todas' ? 'selected' : '' ?>>Todos los saldos</option>
                        <option value="pendiente" <?= $estadoSaldo === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="liquidada" <?= $estadoSaldo === 'liquidada' ? 'selected' : '' ?>>Liquidadas</option>
                        <option value="excedente" <?= $estadoSaldo === 'excedente' ? 'selected' : '' ?>>Excedentes</option>
                    </select>
                    <button class="cxc-btn cxc-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                </form>
            </div>

            <div class="cxc-panel overflow-hidden">
                <?php if (empty($cuentas)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-hand-holding-dollar"></i></div>
                        <h2 class="font-black text-lg">Sin cuentas por cobrar derivadas</h2>
                        <p class="text-sm text-slate-500 mt-1">No hay reservaciones que coincidan con los filtros seleccionados.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="cxc-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Reservacion</th>
                                    <th class="text-left">Huesped</th>
                                    <th class="text-left">Fechas</th>
                                    <th class="text-left">Estado</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-right">Cubierto</th>
                                    <th class="text-right">Saldo estimado</th>
                                    <th class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cuentas as $cuenta): ?>
                                    <?php
                                    $reservacionId = (int)($cuenta['reservacion_id'] ?? 0);
                                    $facturaId = (int)($cuenta['solicitud_factura_id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= url('reservaciones/ver/' . $reservacionId) ?>">
                                                #<?= $reservacionId ?>
                                            </a>
                                            <div class="text-xs text-slate-500">Hotel actual</div>
                                        </td>
                                        <td>
                                            <div class="font-bold"><?= cxc_safe($cuenta['huesped_nombre'] ?? null) ?></div>
                                            <div class="text-xs text-slate-500"><?= cxc_safe($cuenta['huesped_telefono'] ?? null, 'Sin telefono') ?></div>
                                        </td>
                                        <td>
                                            <?= cxc_safe($cuenta['fecha_entrada'] ?? null) ?>
                                            <span class="text-slate-400">/</span>
                                            <?= cxc_safe($cuenta['fecha_salida'] ?? null) ?>
                                        </td>
                                        <td>
                                            <span class="cxc-badge">
                                                <i class="fas fa-circle-dot"></i>
                                                <?= cxc_safe($cuenta['estado_saldo'] ?? null) ?>
                                            </span>
                                            <div class="text-xs text-slate-500 mt-1"><?= cxc_safe($cuenta['reservacion_estado'] ?? null) ?></div>
                                        </td>
                                        <td class="text-right"><?= cxc_money($cuenta['precio_total'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <?= cxc_money($cuenta['monto_cubierto'] ?? 0) ?>
                                            <div class="text-xs text-slate-500">
                                                P: <?= cxc_money($cuenta['pagos_total'] ?? 0) ?> / A: <?= cxc_money($cuenta['abonos_total'] ?? 0) ?>
                                            </div>
                                        </td>
                                        <td class="text-right font-black"><?= cxc_money($cuenta['saldo_estimado'] ?? 0) ?></td>
                                        <td class="text-right">
                                            <div class="inline-flex gap-2">
                                                <a class="cxc-btn cxc-btn-muted" href="<?= url('reservaciones/ver/' . $reservacionId) ?>">
                                                    <i class="fas fa-eye"></i>
                                                    Reservacion
                                                </a>
                                                <?php if ($facturaId > 0): ?>
                                                    <a class="cxc-btn cxc-btn-muted" href="<?= url('facturacion/ver/' . $facturaId) ?>">
                                                        <i class="fas fa-file-invoice"></i>
                                                        Factura
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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
