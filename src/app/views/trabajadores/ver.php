<?php
$trabajador = $trabajador ?? [];
$resumenLedger = $resumenLedger ?? [];
$conceptosLaborales = is_array($conceptosLaborales ?? null) ? $conceptosLaborales : [];
$anticiposRecientes = is_array($anticiposRecientes ?? null) ? $anticiposRecientes : [];
$prestamosRecientes = is_array($prestamosRecientes ?? null) ? $prestamosRecientes : [];
$asistenciasRecientes = $asistenciasRecientes ?? [];
$ledgerDisponible = $ledgerDisponible ?? [];
$tareasContextuales = is_array($tareasContextuales ?? null) ? $tareasContextuales : [];

if (!function_exists('trab_view_safe')) {
    function trab_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_view_money')) {
    function trab_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('trab_view_qty')) {
    function trab_view_qty($value)
    {
        return number_format((float)($value ?? 0), 2, '.', '');
    }
}

$trabajadorId = (int)($trabajador['id'] ?? 0);
$puedeRegistrarConcepto = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_pagos']);
$puedeRegistrarAnticipo = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_anticipos']);
$puedeRegistrarPrestamo = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_prestamos']);
?>

<style>
.worker-detail-page {
    --trab-brand: var(--brand-primary, #1f3f46);
    --trab-accent: var(--brand-accent, #b58a3c);
    --trab-line: color-mix(in srgb, var(--trab-brand) 10%, #e5e7eb);
    --trab-soft: color-mix(in srgb, var(--trab-accent) 7%, #f8fafc);
    color: #243142;
}
.worker-detail-page .worker-hero {
    background: linear-gradient(135deg, color-mix(in srgb, var(--trab-brand) 92%, #111827), color-mix(in srgb, var(--trab-accent) 58%, #5b4730));
    color: #fff;
    padding: 28px;
}
.worker-detail-page .worker-kicker {
    font-size: .72rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    opacity: .76;
    font-weight: 800;
}
.worker-detail-page .worker-title {
    margin: 6px 0 0;
    font-size: clamp(1.45rem, 2.4vw, 2.15rem);
    font-weight: 900;
    letter-spacing: 0;
}
.worker-detail-page .worker-subtitle {
    margin-top: 8px;
    max-width: 52rem;
    color: rgba(255,255,255,.86);
}
.worker-detail-page .worker-stat {
    border: 1px solid rgba(255,255,255,.22);
    background: rgba(255,255,255,.11);
    padding: 12px 14px;
}
.worker-detail-page .worker-panel {
    border: 1px solid var(--trab-line);
    background: rgba(255,255,255,.92);
}
.worker-detail-page .worker-btn {
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
.worker-detail-page .worker-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 9px;
    border: 1px solid var(--trab-line);
    background: var(--trab-soft);
    font-size: .78rem;
    font-weight: 800;
}
.worker-detail-page .worker-table th {
    color: #64748b;
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.worker-detail-page .worker-table td,
.worker-detail-page .worker-table th {
    border-bottom: 1px solid var(--trab-line);
    padding: 14px 12px;
}
.worker-detail-page .worker-meta-label {
    font-size: .72rem;
    color: #64748b;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.worker-detail-page .worker-ledger-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.worker-detail-page .worker-ledger-card {
    border: 1px solid var(--trab-line);
    background: #fff;
}
.worker-detail-page .worker-ledger-card header {
    border-bottom: 1px solid var(--trab-line);
    padding: 14px 16px;
}
.worker-detail-page .worker-ledger-body {
    max-height: 360px;
    overflow: auto;
}
.worker-detail-page .worker-ledger-row {
    border-bottom: 1px solid var(--trab-line);
    padding: 14px 16px;
}
.worker-detail-page .worker-ledger-row:last-child {
    border-bottom: 0;
}
.worker-detail-page .worker-ledger-note {
    border: 1px solid color-mix(in srgb, var(--trab-accent) 22%, #e5e7eb);
    background: color-mix(in srgb, var(--trab-accent) 8%, #fff);
    color: #475569;
    padding: 12px 14px;
    font-size: .86rem;
}
</style>

<div class="worker-detail-page">
    <section class="worker-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="worker-kicker">Personal / Trabajador</div>
                <h1 class="worker-title"><?= trab_view_safe($trabajador['nombre_completo'] ?? null) ?></h1>
                <p class="worker-subtitle">
                    Ficha laboral del hotel actual en modo lectura. No registra pagos, anticipos, prestamos ni movimientos de caja.
                </p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 min-w-[340px]">
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Conceptos</div>
                    <div class="text-2xl font-black"><?= (int)($resumenLedger['pagos_count'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Anticipos</div>
                    <div class="text-2xl font-black"><?= (int)($resumenLedger['anticipos_count'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Prestamos</div>
                    <div class="text-2xl font-black"><?= (int)($resumenLedger['prestamos_count'] ?? 0) ?></div>
                </div>
                <div class="worker-stat">
                    <div class="text-xs opacity-75">Docs</div>
                    <div class="text-2xl font-black"><?= (int)($resumenLedger['documentos_count'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </section>

    <section class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <a class="worker-btn" href="<?= url('trabajadores') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="worker-btn" href="<?= url('trabajadores/' . $trabajadorId . '/editar') ?>">
                    <i class="fas fa-pen"></i>
                    Editar
                </a>
                <?php if (($trabajador['estado'] ?? '') === 'baja'): ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/reactivar') ?>">
                        <?= csrf_field() ?>
                        <button class="worker-btn" type="submit">
                            <i class="fas fa-rotate-left"></i>
                            Reactivar
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/baja-logica') ?>" onsubmit="return confirm('Confirmar baja logica del trabajador. No se borrara el registro.');">
                        <?= csrf_field() ?>
                        <button class="worker-btn" type="submit">
                            <i class="fas fa-user-slash"></i>
                            Baja logica
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <span class="worker-badge">
                <i class="fas fa-circle-dot"></i>
                <?= trab_view_safe($trabajador['estado'] ?? null) ?>
            </span>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_minmax(360px,420px)] gap-4 mb-4">
            <div class="worker-panel p-5">
                <h2 class="font-black text-lg mb-4">Datos laborales</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="worker-meta-label">Nombre completo</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['nombre_completo'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Identificacion</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['identificacion'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Rol laboral</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['rol_laboral'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Usuario vinculado</div>
                        <div class="font-black mt-1">
                            <?= trab_view_safe($trabajador['usuario_nombre'] ?? $trabajador['usuario_login'] ?? null, 'Sin usuario') ?>
                        </div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Telefono</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['telefono'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Correo</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['email'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Fecha alta</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['fecha_alta'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Fecha baja</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['fecha_baja'] ?? null) ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Salario base</div>
                        <div class="font-black mt-1"><?= array_key_exists('salario_base', $trabajador) && $trabajador['salario_base'] !== null ? trab_view_money($trabajador['salario_base']) : '-' ?></div>
                    </div>
                    <div>
                        <div class="worker-meta-label">Periodicidad</div>
                        <div class="font-black mt-1"><?= trab_view_safe($trabajador['periodicidad_pago'] ?? null) ?></div>
                    </div>
                    <div class="md:col-span-2">
                        <div class="worker-meta-label">Notas</div>
                        <div class="mt-1 text-slate-700 whitespace-pre-line"><?= trab_view_safe($trabajador['notas'] ?? null, 'Sin notas') ?></div>
                    </div>
                </div>
            </div>

            <div class="worker-panel p-5">
                <h2 class="font-black text-lg mb-4">Resumen laboral</h2>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Conceptos a favor</span>
                        <strong><?= trab_view_money($resumenLedger['conceptos_a_favor'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Conceptos en contra</span>
                        <strong><?= trab_view_money($resumenLedger['conceptos_en_contra'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Total conceptos activos</span>
                        <strong><?= trab_view_money($resumenLedger['pagos_total'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Saldo anticipos</span>
                        <strong><?= trab_view_money($resumenLedger['anticipos_saldo'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Saldo prestamos</span>
                        <strong><?= trab_view_money($resumenLedger['prestamos_saldo'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Asistencias</span>
                        <strong><?= (int)($resumenLedger['asistencias_count'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-slate-500 font-bold">Documentos laborales</span>
                        <strong><?= (int)($resumenLedger['documentos_count'] ?? 0) ?></strong>
                    </div>
                    <div class="flex items-center justify-between gap-4 pt-3 border-t border-slate-200">
                        <span class="text-slate-700 font-black">Saldo informativo</span>
                        <strong><?= trab_view_money($resumenLedger['saldo_informativo'] ?? 0) ?></strong>
                    </div>
                </div>
                <div class="mt-5 text-xs text-slate-500">
                    Lectura derivada del ledger laboral; no representa movimiento de Caja ni pago real.
                </div>
            </div>
        </div>

        <div class="worker-panel p-5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-black text-lg">Ledger laboral</h2>
                    <p class="text-sm text-slate-500 mt-1">Conceptos, anticipos y prestamos del trabajador en el hotel actual. No genera Caja.</p>
                </div>
                <span class="worker-badge">
                    <i class="fas fa-lock"></i>
                    Sin pago real
                </span>
            </div>

            <div class="worker-ledger-note mb-4">
                Este bloque no crea pagos, no genera abonos, no descuenta Caja y no modifica saldos reales. El saldo mostrado es informativo y deriva de tablas de Personal.
            </div>

            <?php if ($puedeRegistrarConcepto): ?>
                <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/conceptos-laborales') ?>" class="worker-ledger-card p-4 mb-4">
                    <?= csrf_field() ?>
                    <div class="grid grid-cols-1 lg:grid-cols-[160px_150px_150px_1fr] gap-3">
                        <div>
                            <label class="worker-meta-label" for="concepto_tipo">Tipo</label>
                            <select id="concepto_tipo" class="worker-input mt-1" name="tipo" required>
                                <option value="comision">Comision</option>
                                <option value="bono">Bono</option>
                                <option value="descuento">Descuento</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_efecto">Efecto</label>
                            <select id="concepto_efecto" class="worker-input mt-1" name="efecto">
                                <option value="">Automatico</option>
                                <option value="a_favor">A favor</option>
                                <option value="en_contra">En contra</option>
                            </select>
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_monto">Monto</label>
                            <input id="concepto_monto" class="worker-input mt-1" type="number" min="0.01" step="0.01" name="monto" required>
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_texto">Concepto</label>
                            <input id="concepto_texto" class="worker-input mt-1" type="text" maxlength="160" name="concepto" required placeholder="Ej. bono por desempeno">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
                        <div>
                            <label class="worker-meta-label" for="concepto_fecha">Fecha</label>
                            <input id="concepto_fecha" class="worker-input mt-1" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_periodo_inicio">Periodo inicio</label>
                            <input id="concepto_periodo_inicio" class="worker-input mt-1" type="date" name="periodo_inicio">
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_periodo_fin">Periodo fin</label>
                            <input id="concepto_periodo_fin" class="worker-input mt-1" type="date" name="periodo_fin">
                        </div>
                        <div>
                            <label class="worker-meta-label" for="concepto_referencia">Referencia</label>
                            <input id="concepto_referencia" class="worker-input mt-1" type="text" maxlength="120" name="referencia" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="worker-meta-label" for="concepto_notas">Notas</label>
                        <textarea id="concepto_notas" class="worker-input mt-1 min-h-[86px] py-3" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="text-xs text-slate-500">Comision/bono suman a favor; descuento resta; ajuste permite elegir efecto. No crea pagos reales.</div>
                        <button class="worker-btn" type="submit">
                            <i class="fas fa-plus"></i>
                            Registrar concepto
                        </button>
                    </div>
                </form>
            <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                <div class="worker-ledger-note mb-4">Solo se pueden registrar conceptos laborales a trabajadores activos.</div>
            <?php endif; ?>

            <div class="worker-ledger-grid">
                <article class="worker-ledger-card">
                    <header>
                        <h3 class="font-black">Conceptos laborales</h3>
                        <p class="text-xs text-slate-500 mt-1">Bonos, comisiones, descuentos y ajustes registrados en el ledger.</p>
                    </header>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_pagos'])): ?>
                            <div class="p-5 text-sm text-slate-500">La tabla de conceptos laborales no esta disponible.</div>
                        <?php elseif (empty($conceptosLaborales)): ?>
                            <div class="p-5 text-sm text-slate-500">Sin conceptos laborales registrados.</div>
                        <?php else: ?>
                            <?php foreach ($conceptosLaborales as $concepto): ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($concepto['concepto'] ?? null, 'Sin concepto') ?></div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_view_safe($concepto['fecha'] ?? null) ?> - <?= trab_view_safe($concepto['tipo'] ?? null) ?> - <?= trab_view_safe($concepto['efecto'] ?? null) ?>
                                            </div>
                                        </div>
                                        <strong><?= trab_view_money($concepto['monto'] ?? 0) ?></strong>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-2">
                                        Estado: <?= trab_view_safe($concepto['estado'] ?? null) ?> - Ref: <?= trab_view_safe($concepto['referencia'] ?? null, 'Sin referencia') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="worker-ledger-card">
                    <header>
                        <h3 class="font-black">Anticipos</h3>
                        <p class="text-xs text-slate-500 mt-1">Registros laborales informativos; no son egresos de Caja en esta fase.</p>
                    </header>
                    <?php if ($puedeRegistrarAnticipo): ?>
                        <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/anticipos') ?>" class="p-4 border-b border-slate-200">
                            <?= csrf_field() ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="worker-meta-label" for="anticipo_monto">Monto</label>
                                    <input id="anticipo_monto" class="worker-input mt-1" type="number" min="0.01" step="0.01" name="monto" required>
                                </div>
                                <div>
                                    <label class="worker-meta-label" for="anticipo_fecha">Fecha</label>
                                    <input id="anticipo_fecha" class="worker-input mt-1" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="anticipo_motivo">Motivo</label>
                                    <input id="anticipo_motivo" class="worker-input mt-1" type="text" maxlength="160" name="motivo" required placeholder="Ej. anticipo de sueldo">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="anticipo_referencia">Referencia</label>
                                    <input id="anticipo_referencia" class="worker-input mt-1" type="text" maxlength="120" name="referencia" placeholder="Opcional">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="anticipo_notas">Notas</label>
                                    <textarea id="anticipo_notas" class="worker-input mt-1 min-h-[70px] py-3" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="text-xs text-slate-500">Saldo pendiente inicia igual al monto. No crea pago ni Caja.</div>
                                <button class="worker-btn" type="submit">
                                    <i class="fas fa-plus"></i>
                                    Registrar anticipo
                                </button>
                            </div>
                        </form>
                    <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                        <div class="worker-ledger-note m-4">Solo se pueden registrar anticipos a trabajadores activos.</div>
                    <?php endif; ?>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_anticipos'])): ?>
                            <div class="p-5 text-sm text-slate-500">La tabla de anticipos no esta disponible.</div>
                        <?php elseif (empty($anticiposRecientes)): ?>
                            <div class="p-5 text-sm text-slate-500">Sin anticipos registrados.</div>
                        <?php else: ?>
                            <?php foreach ($anticiposRecientes as $anticipo): ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($anticipo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_view_safe($anticipo['fecha'] ?? null) ?> - <?= trab_view_safe($anticipo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong><?= trab_view_money($anticipo['monto'] ?? 0) ?></strong>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-2">
                                        Saldo pendiente: <?= trab_view_money($anticipo['saldo_pendiente'] ?? 0) ?> - Ref: <?= trab_view_safe($anticipo['referencia'] ?? null, 'Sin referencia') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="worker-ledger-card">
                    <header>
                        <h3 class="font-black">Prestamos</h3>
                        <p class="text-xs text-slate-500 mt-1">Deuda laboral del trabajador registrada fuera de Caja.</p>
                    </header>
                    <?php if ($puedeRegistrarPrestamo): ?>
                        <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/prestamos') ?>" class="p-4 border-b border-slate-200">
                            <?= csrf_field() ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="worker-meta-label" for="prestamo_monto">Monto</label>
                                    <input id="prestamo_monto" class="worker-input mt-1" type="number" min="0.01" step="0.01" name="monto" required>
                                </div>
                                <div>
                                    <label class="worker-meta-label" for="prestamo_fecha">Fecha</label>
                                    <input id="prestamo_fecha" class="worker-input mt-1" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div>
                                    <label class="worker-meta-label" for="prestamo_plazo">Plazo meses</label>
                                    <input id="prestamo_plazo" class="worker-input mt-1" type="number" min="1" step="1" name="plazo_meses" placeholder="Opcional">
                                </div>
                                <div>
                                    <label class="worker-meta-label" for="prestamo_abono">Abono informativo</label>
                                    <input id="prestamo_abono" class="worker-input mt-1" type="number" min="0" step="0.01" name="abono_periodico" placeholder="Opcional">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="prestamo_motivo">Motivo</label>
                                    <input id="prestamo_motivo" class="worker-input mt-1" type="text" maxlength="160" name="motivo" required placeholder="Ej. prestamo interno">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="prestamo_referencia">Referencia</label>
                                    <input id="prestamo_referencia" class="worker-input mt-1" type="text" maxlength="120" name="referencia" placeholder="Opcional">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="worker-meta-label" for="prestamo_notas">Notas</label>
                                    <textarea id="prestamo_notas" class="worker-input mt-1 min-h-[70px] py-3" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                <div class="text-xs text-slate-500">Saldo pendiente inicia igual al monto. No crea abonos ni Caja.</div>
                                <button class="worker-btn" type="submit">
                                    <i class="fas fa-plus"></i>
                                    Registrar prestamo
                                </button>
                            </div>
                        </form>
                    <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                        <div class="worker-ledger-note m-4">Solo se pueden registrar prestamos a trabajadores activos.</div>
                    <?php endif; ?>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_prestamos'])): ?>
                            <div class="p-5 text-sm text-slate-500">La tabla de prestamos no esta disponible.</div>
                        <?php elseif (empty($prestamosRecientes)): ?>
                            <div class="p-5 text-sm text-slate-500">Sin prestamos registrados.</div>
                        <?php else: ?>
                            <?php foreach ($prestamosRecientes as $prestamo): ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($prestamo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_view_safe($prestamo['fecha'] ?? null) ?> - <?= trab_view_safe($prestamo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong><?= trab_view_money($prestamo['monto'] ?? 0) ?></strong>
                                    </div>
                                    <div class="text-xs text-slate-500 mt-2">
                                        Saldo pendiente: <?= trab_view_money($prestamo['saldo_pendiente'] ?? 0) ?> - Abono sugerido: <?= trab_view_money($prestamo['abono_periodico'] ?? 0) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        </div>

        <div class="mb-4">
            <?php
            $tituloTareasContextuales = 'Tareas asignadas';
            $subtituloTareasContextuales = 'Tareas operativas vinculadas a este trabajador. No representan asistencia ni pago.';
            include __DIR__ . '/../tareas/_contextual_list.php';
            ?>
        </div>

        <div class="worker-panel overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-black text-lg">Asistencias recientes</h2>
                <span class="worker-badge">
                    <i class="fas fa-lock"></i>
                    Solo lectura
                </span>
            </div>

            <?php if (empty($ledgerDisponible['trabajador_asistencias'])): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-calendar-xmark"></i></div>
                    <h3 class="font-black text-lg">Asistencias no disponibles</h3>
                    <p class="text-sm text-slate-500 mt-1">La tabla de asistencias laborales no esta disponible en esta instalacion.</p>
                </div>
            <?php elseif (empty($asistenciasRecientes)): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-calendar-day"></i></div>
                    <h3 class="font-black text-lg">Sin asistencias registradas</h3>
                    <p class="text-sm text-slate-500 mt-1">Este trabajador aun no tiene asistencias capturadas en el hotel actual.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="worker-table min-w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Tipo</th>
                                <th class="text-left">Entrada</th>
                                <th class="text-left">Salida</th>
                                <th class="text-right">Horas</th>
                                <th class="text-right">Extra</th>
                                <th class="text-left">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($asistenciasRecientes as $asistencia): ?>
                                <tr>
                                    <td><?= trab_view_safe($asistencia['fecha'] ?? null) ?></td>
                                    <td>
                                        <span class="worker-badge">
                                            <i class="fas fa-circle-dot"></i>
                                            <?= trab_view_safe($asistencia['tipo'] ?? null) ?>
                                        </span>
                                    </td>
                                    <td><?= trab_view_safe($asistencia['hora_entrada'] ?? null) ?></td>
                                    <td><?= trab_view_safe($asistencia['hora_salida'] ?? null) ?></td>
                                    <td class="text-right"><?= trab_view_qty($asistencia['horas'] ?? 0) ?></td>
                                    <td class="text-right"><?= trab_view_qty($asistencia['horas_extra'] ?? 0) ?></td>
                                    <td><?= trab_view_safe($asistencia['observaciones'] ?? null, 'Sin observaciones') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
