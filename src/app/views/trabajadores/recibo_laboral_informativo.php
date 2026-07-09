<?php
$recibo = is_array($recibo ?? null) ? $recibo : [];
$trabajador = is_array($recibo['trabajador'] ?? null) ? $recibo['trabajador'] : [];
$calculo = is_array($recibo['calculo'] ?? null) ? $recibo['calculo'] : [];
$periodo = is_array($recibo['periodo'] ?? null) ? $recibo['periodo'] : [];
$resumen = is_array($recibo['resumen'] ?? null) ? $recibo['resumen'] : [];
$bloqueos = is_array($recibo['bloqueos'] ?? null) ? $recibo['bloqueos'] : [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('trab_recibo_safe')) {
    function trab_recibo_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_recibo_money')) {
    function trab_recibo_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_recibo_num')) {
    function trab_recibo_num($value)
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('trab_recibo_date')) {
    function trab_recibo_date($value)
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

if (!function_exists('trab_recibo_datetime')) {
    function trab_recibo_datetime($value)
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return '-';
        }

        try {
            return (new DateTime($text))->format('d/m/Y H:i');
        } catch (Throwable $e) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
    }
}

$trabajadorId = (int)($trabajador['id'] ?? 0);
$fechaInicio = (string)($periodo['fecha_inicio_raw'] ?? ($periodo['fecha_inicio'] ?? ''));
$fechaFin = (string)($periodo['fecha_fin_raw'] ?? ($periodo['fecha_fin'] ?? ''));
$incluirPagosCaja = !empty($periodo['incluir_pagos_caja']);
$estadoPreview = (string)($calculo['estado_preview_nomina'] ?? '');
$estadoLabel = $estadoPreview !== '' ? str_replace('_', ' ', $estadoPreview) : 'sin calculo';
$estadoClass = 'receipt-badge';
if ($estadoPreview === 'por_pagar') {
    $estadoClass .= ' receipt-badge-ok';
} elseif ($estadoPreview === 'bloqueado') {
    $estadoClass .= ' receipt-badge-danger';
} elseif ($estadoPreview === 'cubierto') {
    $estadoClass .= ' receipt-badge-warn';
}

$previewQuery = http_build_query([
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'trabajador_id' => $trabajadorId > 0 ? $trabajadorId : '',
    'estado' => 'todos',
    'solo_con_saldo' => '0',
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
$previewUrl = url('trabajadores/nomina/preview' . ($previewQuery !== '' ? '?' . $previewQuery : ''));
$pdfQuery = http_build_query([
    'fecha_inicio' => $fechaInicio,
    'fecha_fin' => $fechaFin,
    'incluir_pagos_caja' => $incluirPagosCaja ? '1' : '0',
]);
$pdfUrl = url('trabajadores/' . $trabajadorId . '/recibo-laboral/pdf' . ($pdfQuery !== '' ? '?' . $pdfQuery : ''));
$volverUrl = back_url($trabajadorId > 0 ? 'trabajadores/' . $trabajadorId : 'trabajadores');
$folio = 'REC-TRAB-' . ($trabajadorId > 0 ? $trabajadorId : '0') . '-' . date('Ymd');
?>

<style>
.labor-receipt {
    --receipt-brand: var(--brand-primary, #1f3f46);
    --receipt-accent: var(--brand-accent, #b58a3c);
    --receipt-line: color-mix(in srgb, var(--receipt-brand) 12%, #e5e7eb);
    --receipt-soft: color-mix(in srgb, var(--receipt-accent) 7%, #f8fafc);
    color: #243142;
}
.labor-receipt .receipt-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--receipt-brand) 92%, #111827), color-mix(in srgb, var(--receipt-accent) 54%, #5b4730));
    color: #fff;
    padding: 28px;
}
.labor-receipt .receipt-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .78;
    font-weight: 900;
}
.labor-receipt .receipt-title {
    margin: 6px 0 0;
    font-size: clamp(1.5rem, 2.4vw, 2.2rem);
    font-weight: 900;
    letter-spacing: 0;
}
.labor-receipt .receipt-subtitle {
    margin-top: 8px;
    max-width: 60rem;
    color: rgba(255,255,255,.86);
}
.labor-receipt .receipt-hero-card {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.labor-receipt .receipt-panel,
.labor-receipt .receipt-sheet {
    border: 1px solid var(--receipt-line);
    background: rgba(255,255,255,.96);
}
.labor-receipt .receipt-sheet {
    box-shadow: 0 18px 45px rgba(15, 23, 42, .06);
}
.labor-receipt .receipt-stat {
    border: 1px solid var(--receipt-line);
    background: #fff;
    padding: 14px;
}
.labor-receipt .receipt-stat-soft {
    background: var(--receipt-soft);
}
.labor-receipt .receipt-label {
    color: #64748b;
    font-size: .7rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.labor-receipt .receipt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 0 14px;
    border: 1px solid var(--receipt-line);
    background: #fff;
    color: #334155;
    font-weight: 900;
    white-space: nowrap;
}
.labor-receipt .receipt-btn-primary {
    background: var(--receipt-brand);
    border-color: var(--receipt-brand);
    color: #fff;
}
.labor-receipt .receipt-input {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--receipt-line);
    background: #fff;
    padding: 0 12px;
}
.labor-receipt .receipt-check {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 40px;
    border: 1px solid var(--receipt-line);
    background: #fff;
    padding: 0 12px;
    font-weight: 900;
    color: #334155;
}
.labor-receipt .receipt-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 10px;
    border: 1px solid var(--receipt-line);
    background: #f8fafc;
    color: #334155;
    font-size: .78rem;
    font-weight: 900;
}
.labor-receipt .receipt-badge-ok {
    background: #ecfdf5;
    border-color: #a7f3d0;
    color: #047857;
}
.labor-receipt .receipt-badge-warn {
    background: #fff7ed;
    border-color: #fed7aa;
    color: #9a3412;
}
.labor-receipt .receipt-badge-danger {
    background: #fef2f2;
    border-color: #fecaca;
    color: #991b1b;
}
.labor-receipt .receipt-line-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid var(--receipt-line);
    padding: 13px 0;
}
.labor-receipt .receipt-line-item:last-child {
    border-bottom: 0;
}
@media print {
    .labor-receipt .receipt-no-print {
        display: none !important;
    }
    .labor-receipt .receipt-sheet {
        box-shadow: none;
    }
}
</style>

<div class="labor-receipt">
    <section class="receipt-hero">
        <div class="flex flex-col xl:flex-row xl:items-end xl:justify-between gap-6">
            <div>
                <div class="receipt-kicker">Personal / Recibo read-only</div>
                <h1 class="receipt-title">Recibo laboral informativo</h1>
                <p class="receipt-subtitle">
                    Resumen interno del periodo seleccionado. No fiscal, no genera pago, no modifica Caja y no sustituye nomina oficial.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[360px]">
                <div class="receipt-hero-card">
                    <div class="text-xs opacity-75">Folio lectura</div>
                    <div class="text-lg font-black"><?= trab_recibo_safe($folio) ?></div>
                </div>
                <div class="receipt-hero-card">
                    <div class="text-xs opacity-75">Periodo</div>
                    <div class="text-lg font-black"><?= trab_recibo_date($periodo['fecha_inicio'] ?? null) ?> - <?= trab_recibo_date($periodo['fecha_fin'] ?? null) ?></div>
                </div>
                <div class="receipt-hero-card">
                    <div class="text-xs opacity-75">Estado</div>
                    <div class="text-lg font-black"><?= trab_recibo_safe($estadoLabel) ?></div>
                </div>
                <div class="receipt-hero-card">
                    <div class="text-xs opacity-75">Generado</div>
                    <div class="text-lg font-black"><?= trab_recibo_datetime($recibo['generado_en'] ?? null) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6 space-y-4">
        <div class="receipt-no-print">
            <?php $subnav_section = 'personal'; $subnav_active = 'equipo'; include APP_PATH . '/views/partials/section_subnav.php'; ?>
        </div>
        <div class="receipt-no-print flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <?php $back_arrow_href = $volverUrl; $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="receipt-btn ms-back-legacy" href="<?= $volverUrl ?>">
                    <i class="fas fa-arrow-left"></i>
                    Trabajador
                </a>
                <a class="receipt-btn" href="<?= $previewUrl ?>">
                    <i class="fas fa-table-list"></i>
                    Preview del periodo
                </a>
                <?php if ($tablaDisponible && empty($bloqueos) && !empty($calculo)): ?>
                    <a class="receipt-btn" href="<?= $pdfUrl ?>">
                        <i class="fas fa-file-pdf"></i>
                        PDF informativo
                    </a>
                <?php endif; ?>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="receipt-badge">
                    <i class="fas fa-lock"></i>
                    Solo lectura
                </span>
                <span class="receipt-badge receipt-badge-warn">
                    <i class="fas fa-file-circle-exclamation"></i>
                    No fiscal
                </span>
                <span class="receipt-badge receipt-badge-danger">
                    <i class="fas fa-ban"></i>
                    No genera pago
                </span>
            </div>
        </div>

        <div class="receipt-panel receipt-no-print p-4">
            <form method="GET" action="<?= url('trabajadores/' . $trabajadorId . '/recibo-laboral') ?>" class="grid grid-cols-1 md:grid-cols-[160px_160px_auto_auto] gap-3">
                <input class="receipt-input" type="date" name="fecha_inicio" value="<?= trab_recibo_safe($fechaInicio, '') ?>">
                <input class="receipt-input" type="date" name="fecha_fin" value="<?= trab_recibo_safe($fechaFin, '') ?>">
                <label class="receipt-check">
                    <input type="hidden" name="incluir_pagos_caja" value="0">
                    <input type="checkbox" name="incluir_pagos_caja" value="1" <?= $incluirPagosCaja ? 'checked' : '' ?>>
                    Pagos Caja
                </label>
                <button class="receipt-btn receipt-btn-primary" type="submit">
                    <i class="fas fa-rotate"></i>
                    Actualizar recibo
                </button>
            </form>
        </div>

        <?php if (!$tablaDisponible): ?>
            <div class="receipt-panel p-5 bg-slate-50">
                <strong>Recibo no disponible.</strong>
                <p class="text-sm text-slate-500 mt-1">Faltan tablas laborales o de Caja para generar la lectura informativa con seguridad.</p>
            </div>
        <?php else: ?>
            <?php if (!empty($bloqueos)): ?>
                <div class="receipt-panel p-5 bg-slate-50">
                    <strong>Periodo requerido para generar el recibo informativo.</strong>
                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        <?php foreach ($bloqueos as $bloqueo): ?>
                            <div><i class="fas fa-circle-info mr-2"></i><?= trab_recibo_safe($bloqueo) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="receipt-sheet p-6 md:p-8 space-y-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <div class="receipt-label">Trabajador</div>
                        <h2 class="text-2xl font-black mt-1"><?= trab_recibo_safe($trabajador['nombre_completo'] ?? null) ?></h2>
                        <p class="text-sm text-slate-500 mt-1">
                            ID <?= (int)$trabajadorId ?> / <?= trab_recibo_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?> / <?= trab_recibo_safe($trabajador['estado'] ?? null) ?>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="<?= $estadoClass ?>">
                            <i class="fas fa-circle"></i>
                            <?= trab_recibo_safe($estadoLabel) ?>
                        </span>
                        <span class="receipt-badge">
                            <i class="fas fa-calendar-days"></i>
                            <?= trab_recibo_date($periodo['fecha_inicio'] ?? null) ?> - <?= trab_recibo_date($periodo['fecha_fin'] ?? null) ?>
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
                    <div class="receipt-stat receipt-stat-soft">
                        <div class="receipt-label">Bruto laboral</div>
                        <div class="text-3xl font-black mt-1"><?= trab_recibo_money($calculo['bruto_periodo'] ?? 0) ?></div>
                    </div>
                    <div class="receipt-stat">
                        <div class="receipt-label">Deducciones info</div>
                        <div class="text-3xl font-black mt-1"><?= trab_recibo_money($calculo['deducciones_informativas'] ?? 0) ?></div>
                    </div>
                    <div class="receipt-stat">
                        <div class="receipt-label">Pagos Caja</div>
                        <div class="text-3xl font-black mt-1"><?= trab_recibo_money($calculo['pagos_caja_aplicados'] ?? 0) ?></div>
                    </div>
                    <div class="receipt-stat">
                        <div class="receipt-label">Neto sugerido</div>
                        <div class="text-3xl font-black mt-1"><?= trab_recibo_money($calculo['neto_sugerido'] ?? 0) ?></div>
                    </div>
                    <div class="receipt-stat receipt-stat-soft">
                        <div class="receipt-label">Pendiente sugerido</div>
                        <div class="text-3xl font-black mt-1"><?= trab_recibo_money($calculo['pendiente_pago_sugerido'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="receipt-panel p-5">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
                            <div>
                                <h3 class="font-black text-lg">Conceptos del periodo</h3>
                                <p class="text-sm text-slate-500">Bonos y ajustes informativos detectados.</p>
                            </div>
                            <span class="receipt-badge"><?= trab_recibo_num($calculo['conceptos_count'] ?? 0) ?></span>
                        </div>
                        <div class="mt-2">
                            <div class="receipt-line-item">
                                <div>
                                    <div class="font-black">Conceptos a favor</div>
                                    <div class="text-xs text-slate-500">Suman al bruto laboral.</div>
                                </div>
                                <div class="font-black text-teal-700"><?= trab_recibo_money($calculo['conceptos_a_favor'] ?? 0) ?></div>
                            </div>
                            <div class="receipt-line-item">
                                <div>
                                    <div class="font-black">Conceptos en contra</div>
                                    <div class="text-xs text-slate-500">Restan al bruto laboral.</div>
                                </div>
                                <div class="font-black text-amber-700"><?= trab_recibo_money($calculo['conceptos_en_contra'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-panel p-5">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
                            <div>
                                <h3 class="font-black text-lg">Deducciones informativas</h3>
                                <p class="text-sm text-slate-500">Saldos laborales pendientes; no generan egreso.</p>
                            </div>
                            <span class="receipt-badge"><?= trab_recibo_money($calculo['deducciones_informativas'] ?? 0) ?></span>
                        </div>
                        <div class="mt-2">
                            <div class="receipt-line-item">
                                <div>
                                    <div class="font-black">Anticipos pendientes</div>
                                    <div class="text-xs text-slate-500"><?= trab_recibo_num($calculo['anticipos_count'] ?? 0) ?> registro(s).</div>
                                </div>
                                <div class="font-black text-amber-700"><?= trab_recibo_money($calculo['anticipos_saldo'] ?? 0) ?></div>
                            </div>
                            <div class="receipt-line-item">
                                <div>
                                    <div class="font-black">Prestamos vigentes</div>
                                    <div class="text-xs text-slate-500"><?= trab_recibo_num($calculo['prestamos_count'] ?? 0) ?> registro(s).</div>
                                </div>
                                <div class="font-black text-amber-700"><?= trab_recibo_money($calculo['prestamos_saldo'] ?? 0) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="receipt-panel p-5">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-3 border-b border-slate-200 pb-3">
                        <div>
                            <h3 class="font-black text-lg">Lectura contra Caja</h3>
                            <p class="text-sm text-slate-500">Pagos laborales detectados para este periodo, incluidos solo para descontar del pendiente sugerido.</p>
                        </div>
                        <span class="receipt-badge">
                            <i class="fas fa-lock"></i>
                            <?= $incluirPagosCaja ? 'Pagos Caja incluidos' : 'Pagos Caja excluidos' ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-4">
                        <div class="receipt-stat">
                            <div class="receipt-label">Pagos vigentes</div>
                            <div class="text-2xl font-black mt-1"><?= trab_recibo_num($calculo['pagos_caja_pagados'] ?? 0) ?></div>
                        </div>
                        <div class="receipt-stat">
                            <div class="receipt-label">Aplicado</div>
                            <div class="text-2xl font-black mt-1"><?= trab_recibo_money($calculo['pagos_caja_aplicados'] ?? 0) ?></div>
                        </div>
                        <div class="receipt-stat">
                            <div class="receipt-label">Revertidos</div>
                            <div class="text-2xl font-black mt-1"><?= trab_recibo_num($calculo['pagos_caja_revertidos'] ?? 0) ?></div>
                        </div>
                        <div class="receipt-stat">
                            <div class="receipt-label">Ultimo pago</div>
                            <div class="text-2xl font-black mt-1"><?= trab_recibo_datetime($calculo['ultimo_pago_caja'] ?? null) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (trim((string)($calculo['motivo_bloqueo_nomina'] ?? '')) !== ''): ?>
                    <div class="receipt-panel p-5 bg-red-50 border-red-200">
                        <strong class="text-red-800">Observacion del calculo:</strong>
                        <p class="text-sm text-red-700 mt-1"><?= trab_recibo_safe($calculo['motivo_bloqueo_nomina'] ?? '') ?></p>
                    </div>
                <?php endif; ?>

                <div class="receipt-panel p-5 bg-slate-50">
                    <div class="font-black">Aviso interno</div>
                    <p class="text-sm text-slate-600 mt-1">
                        Este recibo laboral informativo es una lectura operativa del hotel actual. No fiscal, no timbra, no dispersa nomina,
                        no genera movimientos de Caja y no registra pagos.
                    </p>
                    <div class="text-xs text-slate-500 mt-3">
                        Resumen visible: bruto <?= trab_recibo_money($resumen['bruto_total'] ?? 0) ?>,
                        pagos Caja <?= trab_recibo_money($resumen['pagos_caja_aplicados_total'] ?? 0) ?>,
                        pendiente <?= trab_recibo_money($resumen['pendiente_pago_total'] ?? 0) ?>.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>
