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
?>

<style>
.worker-cash-page {
    --worker-cash-brand: var(--brand-primary, #1f3f46);
    --worker-cash-accent: var(--brand-accent, #b58a3c);
    --worker-cash-line: color-mix(in srgb, var(--worker-cash-brand) 10%, #e5e7eb);
    --worker-cash-soft: color-mix(in srgb, var(--worker-cash-accent) 7%, #f8fafc);
    color: #243142;
}
.worker-cash-page .worker-cash-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--worker-cash-brand) 92%, #111827), color-mix(in srgb, var(--worker-cash-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.worker-cash-page .worker-cash-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.worker-cash-page .worker-cash-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.worker-cash-page .worker-cash-subtitle {
    margin-top: 8px;
    max-width: 58rem;
    color: rgba(255,255,255,.86);
}
.worker-cash-page .worker-cash-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.worker-cash-page .worker-cash-panel {
    border: 1px solid var(--worker-cash-line);
    background: rgba(255,255,255,.94);
}
.worker-cash-page .worker-cash-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--worker-cash-line);
    font-weight: 800;
    white-space: nowrap;
}
.worker-cash-page .worker-cash-btn-primary {
    background: var(--worker-cash-brand);
    border-color: var(--worker-cash-brand);
    color: #fff;
}
.worker-cash-page .worker-cash-btn-muted {
    background: #fff;
    color: #334155;
}
.worker-cash-page .worker-cash-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--worker-cash-line);
    background: #fff;
    padding: 0 12px;
}
.worker-cash-page .worker-cash-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.worker-cash-page .worker-cash-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.worker-cash-page .worker-cash-table td,
.worker-cash-page .worker-cash-table th {
    border-bottom: 1px solid var(--worker-cash-line);
    padding: 14px 12px;
    vertical-align: top;
}
.worker-cash-page .worker-cash-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--worker-cash-line);
    background: var(--worker-cash-soft);
    font-size: .78rem;
    font-weight: 800;
}
.worker-cash-page .worker-cash-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.worker-cash-page .worker-cash-badge-blocked {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.worker-cash-page .worker-cash-badge-warn {
    background: #fffbeb;
    border-color: #fde68a;
    color: #92400e;
}
</style>

<div class="worker-cash-page">
    <section class="worker-cash-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="worker-cash-kicker">Personal / Caja</div>
                <h1 class="worker-cash-title">Simulador de pago laboral con Caja</h1>
                <p class="worker-cash-subtitle">
                    Diagnostico previo para revisar trabajadores, saldo laboral estimado, corte abierto y referencia antes de una fase futura de pago controlado.
                    Esta pantalla no registra pagos, no cambia saldos y no crea movimientos.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="worker-cash-stat">
                    <div class="text-xs opacity-75">Trabajadores</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['total'] ?? 0) ?></div>
                </div>
                <div class="worker-cash-stat">
                    <div class="text-xs opacity-75">Elegibles</div>
                    <div class="text-2xl font-black"><?= (int)($resumen['elegibles'] ?? 0) ?></div>
                </div>
                <div class="worker-cash-stat">
                    <div class="text-xs opacity-75">Monto simulado</div>
                    <div class="text-xl font-black"><?= trab_cash_money($resumen['monto_simulado_total'] ?? 0) ?></div>
                </div>
                <div class="worker-cash-stat">
                    <div class="text-xs opacity-75">Corte abierto</div>
                    <div class="text-xl font-black"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'No' ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="worker-cash-btn worker-cash-btn-muted" href="<?= url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver a Personal
                </a>
                <a class="worker-cash-btn worker-cash-btn-muted" href="<?= url('caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Ver Caja
                </a>
            </div>
            <span class="worker-cash-badge">
                <i class="fas fa-lock"></i>
                Solo GET / Read-only
            </span>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="worker-cash-panel p-5">
                <strong>Simulador no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas de Personal, pagos laborales o Caja para evaluar esta fase con seguridad.</p>
            </div>
        <?php else: ?>
            <div class="worker-cash-panel p-5 mb-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <div class="worker-cash-label">Corte</div>
                        <div class="font-black mt-1"><?= !empty($corte) ? '#' . (int)$corte['id'] : 'Sin corte abierto' ?></div>
                    </div>
                    <div>
                        <div class="worker-cash-label">Caja</div>
                        <div class="font-black mt-1"><?= trab_cash_safe($corte['caja_nombre'] ?? null, 'No disponible') ?></div>
                        <div class="text-xs text-slate-500"><?= trab_cash_safe($corte['caja_ubicacion'] ?? null, 'Sin ubicacion') ?></div>
                    </div>
                    <div>
                        <div class="worker-cash-label">Apertura</div>
                        <div class="font-black mt-1"><?= trab_cash_safe($corte['fecha_apertura'] ?? null, 'Pendiente') ?></div>
                    </div>
                    <div>
                        <div class="worker-cash-label">Estado</div>
                        <?php if (!empty($corte)): ?>
                            <span class="worker-cash-badge worker-cash-badge-ok mt-1">
                                <i class="fas fa-check-circle"></i>
                                Abierto
                            </span>
                        <?php else: ?>
                            <span class="worker-cash-badge worker-cash-badge-warn mt-1">
                                <i class="fas fa-triangle-exclamation"></i>
                                Bloquea pagos
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="worker-cash-panel p-4 mb-4">
                <form method="GET" action="<?= url('trabajadores/pagos-caja/simulador') ?>" class="grid grid-cols-1 xl:grid-cols-[120px_minmax(180px,1fr)_150px_150px_170px_160px_minmax(180px,1fr)_auto] gap-3">
                    <input class="worker-cash-input" type="number" min="1" name="trabajador_id" value="<?= $trabajadorId > 0 ? (int)$trabajadorId : '' ?>" placeholder="ID">
                    <input class="worker-cash-input" type="search" name="buscar" value="<?= trab_cash_safe($buscar, '') ?>" placeholder="Buscar trabajador, identificacion o rol">
                    <input class="worker-cash-input" type="date" name="periodo_inicio" value="<?= trab_cash_safe($periodoInicio, '') ?>">
                    <input class="worker-cash-input" type="date" name="periodo_fin" value="<?= trab_cash_safe($periodoFin, '') ?>">
                    <select class="worker-cash-input" name="metodo_pago">
                        <option value="efectivo" <?= $metodoPago === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                        <option value="tarjeta" <?= $metodoPago === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                        <option value="transferencia" <?= $metodoPago === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    </select>
                    <input class="worker-cash-input" type="number" min="0" step="0.01" name="monto" value="<?= trab_cash_safe($monto, '') ?>" placeholder="Monto">
                    <input class="worker-cash-input" type="text" name="referencia" value="<?= trab_cash_safe($referencia, '') ?>" placeholder="Referencia opcional">
                    <button class="worker-cash-btn worker-cash-btn-primary" type="submit">
                        <i class="fas fa-filter"></i>
                        Evaluar
                    </button>
                </form>
            </div>

            <div class="worker-cash-panel overflow-hidden">
                <?php if (empty($trabajadores)): ?>
                    <div class="p-8 text-center">
                        <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-user-clock"></i></div>
                        <h2 class="font-black text-lg">No hay trabajadores para evaluar</h2>
                        <p class="text-sm text-slate-500 mt-1">Ajusta los filtros o vuelve al listado de Personal para seleccionar un trabajador.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="worker-cash-table min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left">Trabajador</th>
                                    <th class="text-left">Periodo y metodo</th>
                                    <th class="text-right">Saldo estimado</th>
                                    <th class="text-right">Monto simulado</th>
                                    <th class="text-left">Pagos Caja</th>
                                    <th class="text-left">Diagnostico</th>
                                    <th class="text-right">Accion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trabajadores as $trabajador): ?>
                                    <?php $trabajadorUrl = url('trabajadores/' . (int)($trabajador['id'] ?? 0)); ?>
                                    <tr>
                                        <td>
                                            <a class="font-black text-slate-800 underline" href="<?= $trabajadorUrl ?>">
                                                #<?= (int)($trabajador['id'] ?? 0) ?> <?= trab_cash_safe($trabajador['nombre_completo'] ?? null, 'Sin nombre') ?>
                                            </a>
                                            <div class="text-xs text-slate-500 mt-1">
                                                <?= trab_cash_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?> · <?= trab_cash_safe($trabajador['estado'] ?? null, 'Sin estado') ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Ref: <?= trab_cash_safe($trabajador['referencia_evaluada'] ?? null, '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-black"><?= trab_cash_safe($trabajador['metodo_pago_simulado'] ?? null, 'efectivo') ?></div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_cash_date($trabajador['periodo_inicio_simulado'] ?? null) ?> a <?= trab_cash_date($trabajador['periodo_fin_simulado'] ?? null) ?>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="font-black"><?= trab_cash_money($trabajador['saldo_estimado'] ?? 0) ?></div>
                                            <div class="text-xs text-slate-500">
                                                +<?= trab_cash_money($trabajador['conceptos_a_favor'] ?? 0) ?>
                                                / -<?= trab_cash_money((float)($trabajador['conceptos_en_contra'] ?? 0) + (float)($trabajador['anticipos_saldo'] ?? 0) + (float)($trabajador['prestamos_saldo'] ?? 0)) ?>
                                            </div>
                                        </td>
                                        <td class="text-right">
                                            <div class="font-black"><?= trab_cash_money($trabajador['monto_simulado'] ?? 0) ?></div>
                                            <div class="text-xs text-slate-500">Max: <?= trab_cash_money($trabajador['monto_maximo_sugerido'] ?? 0) ?></div>
                                        </td>
                                        <td>
                                            <div class="font-black"><?= (int)($trabajador['pagos_caja_count'] ?? 0) ?> registros</div>
                                            <div class="text-xs text-slate-500">
                                                Pagado: <?= trab_cash_money($trabajador['pagos_caja_total'] ?? 0) ?>
                                            </div>
                                            <div class="text-xs text-slate-500">
                                                Ultimo: <?= trab_cash_safe($trabajador['ultimo_pago_caja'] ?? null, '-') ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($trabajador['es_elegible_caja'])): ?>
                                                <span class="worker-cash-badge worker-cash-badge-ok">
                                                    <i class="fas fa-check-circle"></i>
                                                    Elegible
                                                </span>
                                                <p class="text-xs text-slate-500 mt-2"><?= trab_cash_safe($trabajador['motivo_elegibilidad_caja'] ?? null, '') ?></p>
                                            <?php else: ?>
                                                <span class="worker-cash-badge worker-cash-badge-blocked">
                                                    <i class="fas fa-ban"></i>
                                                    Bloqueado
                                                </span>
                                                <p class="text-xs text-slate-500 mt-2"><?= trab_cash_safe($trabajador['motivo_bloqueo_caja'] ?? null, 'Sin diagnostico') ?></p>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <a class="worker-cash-btn worker-cash-btn-muted" href="<?= $trabajadorUrl ?>">
                                                <i class="fas fa-eye"></i>
                                                Ver ficha
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
