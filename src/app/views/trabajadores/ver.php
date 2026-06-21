<?php
$trabajador = $trabajador ?? [];
$resumenLedger = $resumenLedger ?? [];
$conceptosLaborales = is_array($conceptosLaborales ?? null) ? $conceptosLaborales : [];
$pagosCajaLaborales = is_array($pagosCajaLaborales ?? null) ? $pagosCajaLaborales : [];
$anticiposRecientes = is_array($anticiposRecientes ?? null) ? $anticiposRecientes : [];
$prestamosRecientes = is_array($prestamosRecientes ?? null) ? $prestamosRecientes : [];
$asistenciasRecientes = $asistenciasRecientes ?? [];
$ledgerDisponible = $ledgerDisponible ?? [];
$tareasContextuales = is_array($tareasContextuales ?? null) ? $tareasContextuales : [];
$pagoCaja = is_array($pagoCaja ?? null) ? $pagoCaja : [];
$pagoCajaToken = $pagoCajaToken ?? null;

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

if (!function_exists('trab_view_date')) {
    function trab_view_date($value)
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

if (!function_exists('trab_view_datetime')) {
    function trab_view_datetime($value)
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

if (!function_exists('trab_view_signed_money')) {
    function trab_view_signed_money($value, $direction = 'neutral')
    {
        $amount = (float)($value ?? 0);
        if ($amount == 0.0) {
            return trab_view_money(0);
        }

        $prefix = $direction === 'minus' ? '- ' : ($direction === 'plus' ? '+ ' : '');
        return $prefix . trab_view_money(abs($amount));
    }
}

$trabajadorId = (int)($trabajador['id'] ?? 0);
$pagoCajaSaldo = is_array($pagoCaja['saldo'] ?? null) ? $pagoCaja['saldo'] : [];
$pagoCajaMontoMaximo = (float)($pagoCaja['monto_maximo'] ?? 0);
$pagoCajaReferencia = 'NOM-TRAB-' . $trabajadorId . '-' . date('YmdHis');
$pagoCajaConcepto = 'Pago laboral trabajador #' . $trabajadorId;
$puedeRegistrarConcepto = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_pagos']);
$puedeRegistrarAnticipo = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_anticipos']);
$puedeRegistrarPrestamo = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_prestamos']);
$puedeRegistrarAsistencia = ($trabajador['estado'] ?? '') === 'activo' && !empty($ledgerDisponible['trabajador_asistencias']);
$ledgerConceptosFavor = (float)($resumenLedger['conceptos_a_favor'] ?? 0);
$ledgerConceptosContra = (float)($resumenLedger['conceptos_en_contra'] ?? 0);
$ledgerAnticiposPendientes = (float)($resumenLedger['anticipos_saldo'] ?? 0);
$ledgerPrestamosVigentes = (float)($resumenLedger['prestamos_saldo'] ?? 0);
$ledgerSaldoInformativo = (float)($resumenLedger['saldo_informativo'] ?? 0);
$ledgerTotalDescuentos = $ledgerConceptosContra + $ledgerAnticiposPendientes + $ledgerPrestamosVigentes;
$ledgerMovimientosCount = (int)($resumenLedger['pagos_count'] ?? 0)
    + (int)($resumenLedger['anticipos_count'] ?? 0)
    + (int)($resumenLedger['prestamos_count'] ?? 0)
    + (int)($resumenLedger['asistencias_count'] ?? 0);
$ledgerTieneMovimientos = $ledgerMovimientosCount > 0;
$ledgerSaldoEstado = $ledgerSaldoInformativo > 0
    ? 'A favor del trabajador'
    : ($ledgerSaldoInformativo < 0 ? 'Pendiente por descontar/cubrir' : 'Sin saldo pendiente');
$ledgerSaldoClase = $ledgerSaldoInformativo > 0
    ? 'is-positive'
    : ($ledgerSaldoInformativo < 0 ? 'is-negative' : 'is-neutral');
$pagoCajaSaldoBase = (float)($pagoCajaSaldo['saldo_base'] ?? $ledgerSaldoInformativo);
$pagoCajaPagosAplicados = (float)($pagoCajaSaldo['pagos_caja_total'] ?? 0);
$pagoCajaSaldoDisponible = array_key_exists('saldo_disponible', $pagoCajaSaldo)
    ? (float)$pagoCajaSaldo['saldo_disponible']
    : $pagoCajaMontoMaximo;
if (abs($pagoCajaSaldoDisponible) < 0.005) {
    $pagoCajaSaldoDisponible = 0.0;
}
$pagoCajaDisponibleEstado = $pagoCajaSaldoDisponible > 0
    ? 'Disponible para pago'
    : ($pagoCajaSaldoDisponible < 0 ? 'Revisar sobrepago' : 'Sin saldo disponible');
$pagoCajaDisponibleClase = $pagoCajaSaldoDisponible > 0
    ? 'is-positive'
    : ($pagoCajaSaldoDisponible < 0 ? 'is-negative' : 'is-neutral');
$pagosCajaLaboralesTotal = 0.0;
$pagosCajaLaboralesPagados = 0;
$pagosCajaLaboralesRevertidos = 0;
foreach ($pagosCajaLaborales as $pagoCajaLaboral) {
    if (($pagoCajaLaboral['estado'] ?? '') === 'pagado') {
        $pagosCajaLaboralesPagados++;
        $pagosCajaLaboralesTotal += (float)($pagoCajaLaboral['monto'] ?? 0);
    } elseif (($pagoCajaLaboral['estado'] ?? '') === 'revertido') {
        $pagosCajaLaboralesRevertidos++;
    }
}
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
.worker-detail-page .worker-input {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--trab-line);
    border-radius: 12px;
    background: #fff;
    color: #243142;
    padding: 9px 12px;
    font-size: .9rem;
    font-weight: 650;
    outline: none;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}
.worker-detail-page textarea.worker-input {
    resize: vertical;
}
.worker-detail-page .worker-input:focus {
    border-color: color-mix(in srgb, var(--trab-accent) 52%, var(--trab-line));
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--trab-accent) 16%, transparent);
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
.worker-detail-page .worker-ledger-overview {
    display: grid;
    grid-template-columns: minmax(270px, .95fr) minmax(0, 1.35fr);
    gap: 16px;
    margin-bottom: 16px;
}
.worker-detail-page .worker-ledger-balance {
    position: relative;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--trab-brand) 14%, #dbe3ea);
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--trab-accent) 18%, transparent), transparent 15rem),
        linear-gradient(135deg, color-mix(in srgb, var(--trab-brand) 10%, #fff), #fff);
    padding: 18px;
}
.worker-detail-page .worker-ledger-balance-value {
    margin-top: 8px;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1;
    font-weight: 950;
    letter-spacing: 0;
    color: var(--trab-brand);
}
.worker-detail-page .worker-ledger-balance.is-positive .worker-ledger-balance-value {
    color: #0f766e;
}
.worker-detail-page .worker-ledger-balance.is-negative .worker-ledger-balance-value {
    color: #b45309;
}
.worker-detail-page .worker-ledger-balance.is-neutral .worker-ledger-balance-value {
    color: #334155;
}
.worker-detail-page .worker-ledger-balance-caption {
    margin-top: 8px;
    color: #64748b;
    font-size: .84rem;
    line-height: 1.45;
    font-weight: 650;
}
.worker-detail-page .worker-ledger-equation {
    border: 1px solid var(--trab-line);
    background: #fff;
    padding: 16px;
}
.worker-detail-page .worker-ledger-equation-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    color: #334155;
    font-weight: 900;
}
.worker-detail-page .worker-ledger-formula {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.worker-detail-page .worker-ledger-factor {
    min-width: 0;
    border: 1px solid color-mix(in srgb, var(--factor-color, var(--trab-brand)) 16%, var(--trab-line));
    border-radius: 14px;
    background: color-mix(in srgb, var(--factor-color, var(--trab-brand)) 7%, #fff);
    padding: 12px;
}
.worker-detail-page .worker-ledger-factor.is-plus {
    --factor-color: #0f766e;
}
.worker-detail-page .worker-ledger-factor.is-minus {
    --factor-color: #b45309;
}
.worker-detail-page .worker-ledger-factor small {
    display: block;
    color: #64748b;
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.worker-detail-page .worker-ledger-factor strong {
    display: block;
    margin-top: 5px;
    color: color-mix(in srgb, var(--factor-color, var(--trab-brand)) 78%, #243142);
    font-size: 1rem;
    font-weight: 950;
}
.worker-detail-page .worker-ledger-help {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    margin: 14px 0 16px;
}
.worker-detail-page .worker-ledger-help-item {
    border: 1px solid var(--trab-line);
    background: color-mix(in srgb, var(--trab-brand) 3%, #fff);
    padding: 12px;
}
.worker-detail-page .worker-ledger-help-item i {
    color: var(--trab-accent);
}
.worker-detail-page .worker-ledger-help-item strong,
.worker-detail-page .worker-ledger-help-item span {
    display: block;
}
.worker-detail-page .worker-ledger-help-item strong {
    margin-top: 7px;
    color: #243142;
    font-size: .83rem;
    font-weight: 900;
}
.worker-detail-page .worker-ledger-help-item span {
    margin-top: 4px;
    color: #64748b;
    font-size: .76rem;
    line-height: 1.42;
    font-weight: 650;
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
.worker-detail-page .worker-ledger-row:hover {
    background: color-mix(in srgb, var(--trab-brand) 3%, #fff);
}
.worker-detail-page .worker-ledger-amount {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 4px 9px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--amount-color, #64748b) 9%, #fff);
    color: color-mix(in srgb, var(--amount-color, #64748b) 80%, #243142);
    font-weight: 950;
    white-space: nowrap;
}
.worker-detail-page .worker-ledger-amount.is-plus {
    --amount-color: #0f766e;
}
.worker-detail-page .worker-ledger-amount.is-minus,
.worker-detail-page .worker-ledger-amount.is-debt {
    --amount-color: #b45309;
}
.worker-detail-page .worker-ledger-empty {
    display: grid;
    place-items: center;
    min-height: 170px;
    padding: 28px 18px;
    text-align: center;
    color: #64748b;
}
.worker-detail-page .worker-ledger-empty i {
    margin-bottom: 10px;
    color: color-mix(in srgb, var(--trab-brand) 24%, #cbd5e1);
    font-size: 2rem;
}
.worker-detail-page .worker-ledger-empty strong {
    display: block;
    color: #334155;
    font-weight: 950;
}
.worker-detail-page .worker-ledger-empty span {
    display: block;
    max-width: 26rem;
    margin-top: 5px;
    font-size: .84rem;
    line-height: 1.45;
}
.worker-detail-page .worker-ledger-note {
    border: 1px solid color-mix(in srgb, var(--trab-accent) 22%, #e5e7eb);
    background: color-mix(in srgb, var(--trab-accent) 8%, #fff);
    color: #475569;
    padding: 12px 14px;
    font-size: .86rem;
}
.worker-detail-page .worker-ledger-actions {
    display: grid;
    gap: 10px;
    margin-bottom: 16px;
}
.worker-detail-page .worker-ledger-details {
    border: 1px solid var(--trab-line);
    background: #fff;
}
.worker-detail-page .worker-ledger-details summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    min-height: 48px;
    padding: 0 14px;
    cursor: pointer;
    color: #334155;
    font-size: .9rem;
    font-weight: 900;
    list-style: none;
}
.worker-detail-page .worker-ledger-details summary::-webkit-details-marker {
    display: none;
}
.worker-detail-page .worker-ledger-details summary::after {
    content: "+";
    display: grid;
    place-items: center;
    width: 24px;
    height: 24px;
    border-radius: 999px;
    background: var(--trab-soft);
    color: var(--trab-brand);
    font-weight: 950;
}
.worker-detail-page .worker-ledger-details[open] summary {
    border-bottom: 1px solid var(--trab-line);
}
.worker-detail-page .worker-ledger-details[open] summary::after {
    content: "-";
}
@media (max-width: 1080px) {
    .worker-detail-page .worker-ledger-overview,
    .worker-detail-page .worker-ledger-formula,
    .worker-detail-page .worker-ledger-help {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="worker-detail-page">
    <section class="worker-hero">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div>
                <div class="worker-kicker">Personal / Trabajador</div>
                <h1 class="worker-title"><?= trab_view_safe($trabajador['nombre_completo'] ?? null) ?></h1>
                <p class="worker-subtitle">
                    Ficha laboral del hotel actual. El ledger mantiene conceptos, anticipos, prestamos y asistencias; el pago real con Caja se registra solo desde el panel controlado.
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
                <a class="worker-btn" href="<?= url('trabajadores/pagos-caja/simulador?trabajador_id=' . $trabajadorId) ?>">
                    <i class="fas fa-cash-register"></i>
                    Simulador Caja
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
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 class="font-black text-lg">Resumen laboral</h2>
                        <p class="text-sm text-slate-500 mt-1">Lectura informativa del historial laboral.</p>
                    </div>
                    <span class="worker-badge">
                        <i class="fas fa-scale-balanced"></i>
                        <?= trab_view_safe($pagoCajaDisponibleEstado) ?>
                    </span>
                </div>
                <div class="worker-ledger-balance <?= $pagoCajaDisponibleClase ?>">
                    <div class="worker-meta-label">Saldo disponible para pago</div>
                    <div class="worker-ledger-balance-value"><?= trab_view_money($pagoCajaSaldoDisponible) ?></div>
                    <p class="worker-ledger-balance-caption">
                        Bruto laboral: <?= trab_view_money($pagoCajaSaldoBase) ?>. Pagos Caja aplicados: <?= trab_view_money($pagoCajaPagosAplicados) ?>.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3 text-sm">
                    <div class="worker-ledger-factor is-plus">
                        <small>A favor</small>
                        <strong><?= trab_view_money($ledgerConceptosFavor) ?></strong>
                    </div>
                    <div class="worker-ledger-factor is-minus">
                        <small>Por descontar</small>
                        <strong><?= trab_view_money($ledgerTotalDescuentos) ?></strong>
                    </div>
                    <div class="worker-ledger-factor is-minus">
                        <small>Pagos Caja</small>
                        <strong><?= trab_view_money($pagoCajaPagosAplicados) ?></strong>
                    </div>
                    <div class="worker-ledger-factor <?= $pagoCajaDisponibleClase ?>">
                        <small>Disponible</small>
                        <strong><?= trab_view_money($pagoCajaSaldoDisponible) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="worker-panel p-5 mb-4">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-black text-lg">Pago laboral con Caja</h2>
                    <p class="text-sm text-slate-500 mt-1">Registra un egreso en Caja y un pago laboral trazable solo cuando el trabajador sea elegible.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($pagoCaja['corte'])): ?>
                        <span class="worker-badge">
                            <i class="fas fa-cash-register"></i>
                            Corte #<?= (int)$pagoCaja['corte']['id'] ?>
                        </span>
                    <?php endif; ?>
                    <span class="worker-badge">
                        <i class="fas fa-shield-halved"></i>
                        Token un solo uso
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 text-sm">
                <div class="worker-ledger-factor is-plus">
                    <small>Saldo base</small>
                    <strong><?= trab_view_money($pagoCajaSaldoBase) ?></strong>
                </div>
                <div class="worker-ledger-factor is-minus">
                    <small>Pagos Caja</small>
                    <strong><?= trab_view_money($pagoCajaPagosAplicados) ?></strong>
                </div>
                <div class="worker-ledger-factor <?= $pagoCajaMontoMaximo > 0 ? 'is-plus' : 'is-minus' ?>">
                    <small>Maximo elegible</small>
                    <strong><?= trab_view_money($pagoCajaMontoMaximo) ?></strong>
                </div>
                <div class="worker-ledger-factor">
                    <small>Estado</small>
                    <strong><?= !empty($pagoCaja['elegible']) ? 'Elegible' : 'Bloqueado' ?></strong>
                </div>
            </div>

            <?php if (!empty($pagoCaja['elegible']) && !empty($pagoCajaToken)): ?>
                <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/registrar-pago-caja') ?>" class="grid grid-cols-1 lg:grid-cols-4 gap-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pago_token" value="<?= trab_view_safe($pagoCajaToken, '') ?>">
                    <div>
                        <label class="worker-meta-label" for="pago_caja_monto">Monto</label>
                        <input id="pago_caja_monto" class="worker-input mt-1" type="number" min="0.01" max="<?= trab_view_safe($pagoCaja['monto_maximo'] ?? '0.00') ?>" step="0.01" name="monto" value="<?= trab_view_safe($pagoCaja['monto_maximo'] ?? '0.00') ?>" required>
                    </div>
                    <div>
                        <label class="worker-meta-label" for="pago_caja_metodo">Metodo</label>
                        <select id="pago_caja_metodo" class="worker-input mt-1" name="metodo_pago" required>
                            <?php foreach (($pagoCaja['metodos_pago'] ?? []) as $metodo => $label): ?>
                                <option value="<?= trab_view_safe($metodo) ?>"><?= trab_view_safe($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="worker-meta-label" for="pago_caja_referencia">Referencia</label>
                        <input id="pago_caja_referencia" class="worker-input mt-1" type="text" maxlength="100" name="referencia" value="<?= trab_view_safe($pagoCajaReferencia, '') ?>" required>
                    </div>
                    <div>
                        <label class="worker-meta-label" for="pago_caja_concepto">Concepto</label>
                        <input id="pago_caja_concepto" class="worker-input mt-1" type="text" maxlength="160" name="concepto" value="<?= trab_view_safe($pagoCajaConcepto, '') ?>">
                    </div>
                    <div>
                        <label class="worker-meta-label" for="pago_caja_periodo_inicio">Periodo inicio</label>
                        <input id="pago_caja_periodo_inicio" class="worker-input mt-1" type="date" name="periodo_inicio">
                    </div>
                    <div>
                        <label class="worker-meta-label" for="pago_caja_periodo_fin">Periodo fin</label>
                        <input id="pago_caja_periodo_fin" class="worker-input mt-1" type="date" name="periodo_fin">
                    </div>
                    <div class="lg:col-span-2">
                        <label class="worker-meta-label" for="pago_caja_notas">Notas</label>
                        <input id="pago_caja_notas" class="worker-input mt-1" type="text" maxlength="1000" name="notas" placeholder="Opcional">
                    </div>
                    <div class="lg:col-span-4 flex flex-wrap items-center justify-between gap-3 pt-1">
                        <p class="text-sm text-slate-500">El envio crea un egreso en Caja y consume el token de pago.</p>
                        <button class="worker-btn" style="background: var(--trab-brand); color: #fff; border-color: transparent;" type="submit">
                            <i class="fas fa-cash-register"></i>
                            Registrar pago laboral
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="worker-ledger-note">
                    <?= trab_view_safe($pagoCaja['motivo_bloqueo'] ?? null, 'Este trabajador no es elegible para pago laboral con Caja.') ?>
                    <a class="font-black underline ml-1" href="<?= url('trabajadores/pagos-caja/simulador?trabajador_id=' . $trabajadorId) ?>">Revisar simulador</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="worker-panel p-5 mb-4">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-black text-lg">Pagos laborales con Caja</h2>
                    <p class="text-sm text-slate-500 mt-1">Historial read-only de egresos laborales vinculados a Caja, corte y movimiento.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="worker-badge">
                        <i class="fas fa-list-check"></i>
                        <?= count($pagosCajaLaborales) ?> registros
                    </span>
                    <span class="worker-badge">
                        <i class="fas fa-cash-register"></i>
                        Pagado <?= trab_view_money($pagosCajaLaboralesTotal) ?>
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4 text-sm">
                <div class="worker-ledger-factor is-plus">
                    <small>Pagados</small>
                    <strong><?= (int)$pagosCajaLaboralesPagados ?></strong>
                </div>
                <div class="worker-ledger-factor is-minus">
                    <small>Revertidos</small>
                    <strong><?= (int)$pagosCajaLaboralesRevertidos ?></strong>
                </div>
                <div class="worker-ledger-factor">
                    <small>Saldo disponible actual</small>
                    <strong><?= trab_view_money($pagoCajaSaldoDisponible) ?></strong>
                </div>
            </div>

            <?php if (empty($pagosCajaLaborales)): ?>
                <div class="worker-ledger-empty">
                    <i class="fas fa-receipt"></i>
                    <strong>Sin pagos laborales con Caja</strong>
                    <span>Cuando registres un pago desde el panel controlado, se mostrara aqui con su corte, movimiento y referencia.</span>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="worker-table w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left">Fecha</th>
                                <th class="text-left">Pago</th>
                                <th class="text-left">Caja / corte</th>
                                <th class="text-left">Periodo</th>
                                <th class="text-left">Referencia</th>
                                <th class="text-left">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagosCajaLaborales as $pagoLaboral): ?>
                                <?php
                                    $estadoPago = (string)($pagoLaboral['estado'] ?? '');
                                    $estadoClase = $estadoPago === 'pagado' ? 'is-plus' : 'is-minus';
                                    $periodoInicio = trab_view_date($pagoLaboral['periodo_inicio'] ?? null);
                                    $periodoFin = trab_view_date($pagoLaboral['periodo_fin'] ?? null);
                                    $periodoTexto = ($periodoInicio === '-' && $periodoFin === '-')
                                        ? 'Sin periodo'
                                        : $periodoInicio . ' a ' . $periodoFin;
                                    $creadoPor = trim((string)($pagoLaboral['creado_por_nombre'] ?? ''));
                                    if ($creadoPor === '') {
                                        $creadoPor = trim((string)($pagoLaboral['creado_por_login'] ?? ''));
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="font-black text-slate-800"><?= trab_view_datetime($pagoLaboral['fecha_pago'] ?? null) ?></div>
                                        <div class="text-xs text-slate-500">Registro #<?= (int)($pagoLaboral['id'] ?? 0) ?></div>
                                    </td>
                                    <td>
                                        <span class="worker-ledger-amount <?= $estadoClase ?>">
                                            <?= trab_view_money($pagoLaboral['monto'] ?? 0) ?>
                                        </span>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?= trab_view_safe($pagoLaboral['metodo_pago'] ?? null) ?> · Mov. Caja #<?= (int)($pagoLaboral['movimiento_caja_id'] ?? 0) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-black text-slate-800"><?= trab_view_safe($pagoLaboral['caja_nombre'] ?? null) ?></div>
                                        <div class="text-xs text-slate-500">
                                            <a class="font-black underline" href="<?= url('caja/corte/' . (int)($pagoLaboral['corte_id'] ?? 0)) ?>">
                                                Corte #<?= (int)($pagoLaboral['corte_id'] ?? 0) ?>
                                            </a>
                                            · <?= trab_view_safe($pagoLaboral['corte_estado'] ?? null) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-slate-700"><?= trab_view_safe($periodoTexto) ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_view_safe($pagoLaboral['concepto'] ?? null, 'Sin concepto') ?></div>
                                    </td>
                                    <td>
                                        <div class="font-black text-slate-800"><?= trab_view_safe($pagoLaboral['referencia'] ?? null) ?></div>
                                        <div class="text-xs text-slate-500"><?= trab_view_safe($pagoLaboral['movimiento_categoria'] ?? 'Pago laboral') ?></div>
                                    </td>
                                    <td>
                                        <span class="worker-badge">
                                            <i class="fas <?= $estadoPago === 'pagado' ? 'fa-circle-check' : 'fa-rotate-left' ?>"></i>
                                            <?= trab_view_safe($estadoPago) ?>
                                        </span>
                                        <div class="text-xs text-slate-500 mt-2">
                                            <?= $creadoPor !== '' ? 'Por ' . trab_view_safe($creadoPor) : 'Usuario no disponible' ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php if (trim((string)($pagoLaboral['notas'] ?? '')) !== ''): ?>
                                    <tr>
                                        <td colspan="6" class="text-xs text-slate-500">
                                            <strong>Notas:</strong> <?= trab_view_safe($pagoLaboral['notas'] ?? null) ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="worker-panel p-5 mb-4">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div>
                    <h2 class="font-black text-lg">Ledger laboral</h2>
                    <p class="text-sm text-slate-500 mt-1">Historial laboral informativo: lo que suma, lo que descuenta y lo que queda pendiente. No genera Caja.</p>
                </div>
                <span class="worker-badge">
                    <i class="fas fa-lock"></i>
                    Sin pago real
                </span>
            </div>

            <div class="worker-ledger-overview">
                <section class="worker-ledger-balance <?= $ledgerSaldoClase ?>" aria-label="Saldo informativo del trabajador">
                    <div class="worker-meta-label">Saldo informativo bruto</div>
                    <div class="worker-ledger-balance-value"><?= trab_view_money($ledgerSaldoInformativo) ?></div>
                    <p class="worker-ledger-balance-caption">
                        <?= trab_view_safe($ledgerSaldoEstado) ?>. Disponible para pago despues de Caja: <?= trab_view_money($pagoCajaSaldoDisponible) ?>.
                    </p>
                    <?php if (!$ledgerTieneMovimientos): ?>
                        <div class="worker-ledger-note mt-4">
                            Este trabajador todavia no tiene movimientos laborales. Cuando registres un concepto, anticipo, prestamo o asistencia, aparecera aqui.
                        </div>
                    <?php endif; ?>
                </section>

                <section class="worker-ledger-equation" aria-label="Formula del saldo laboral">
                    <div class="worker-ledger-equation-title">
                        <i class="fas fa-calculator"></i>
                        Como se lee este saldo bruto
                    </div>
                    <div class="worker-ledger-formula">
                        <div class="worker-ledger-factor is-plus">
                            <small>+ Conceptos a favor</small>
                            <strong><?= trab_view_signed_money($ledgerConceptosFavor, 'plus') ?></strong>
                        </div>
                        <div class="worker-ledger-factor is-minus">
                            <small>- Conceptos en contra</small>
                            <strong><?= trab_view_signed_money($ledgerConceptosContra, 'minus') ?></strong>
                        </div>
                        <div class="worker-ledger-factor is-minus">
                            <small>- Anticipos pendientes</small>
                            <strong><?= trab_view_signed_money($ledgerAnticiposPendientes, 'minus') ?></strong>
                        </div>
                        <div class="worker-ledger-factor is-minus">
                            <small>- Prestamos vigentes</small>
                            <strong><?= trab_view_signed_money($ledgerPrestamosVigentes, 'minus') ?></strong>
                        </div>
                    </div>
                </section>
            </div>

            <div class="worker-ledger-help">
                <div class="worker-ledger-help-item">
                    <i class="fas fa-circle-plus"></i>
                    <strong>Conceptos</strong>
                    <span>Bonos y comisiones suman; descuentos restan.</span>
                </div>
                <div class="worker-ledger-help-item">
                    <i class="fas fa-hand-holding-dollar"></i>
                    <strong>Anticipos y prestamos</strong>
                    <span>Solo muestran saldo pendiente; no son egresos de Caja.</span>
                </div>
                <div class="worker-ledger-help-item">
                    <i class="fas fa-calendar-check"></i>
                    <strong>Asistencias</strong>
                    <span>Registro operativo diario; no genera nomina automatica.</span>
                </div>
            </div>

            <?php if ($puedeRegistrarConcepto): ?>
                <div class="worker-ledger-actions">
                    <details class="worker-ledger-details">
                        <summary><span><i class="fas fa-plus mr-2"></i>Registrar concepto laboral</span></summary>
                        <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/conceptos-laborales') ?>" class="p-4">
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
                    </details>
                </div>
            <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                <div class="worker-ledger-note mb-4">Solo se pueden registrar conceptos laborales a trabajadores activos.</div>
            <?php endif; ?>

            <div class="worker-ledger-grid">
                <article class="worker-ledger-card">
                    <header class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black">Conceptos laborales</h3>
                            <p class="text-xs text-slate-500 mt-1">Bonos, comisiones, descuentos y ajustes registrados en el ledger.</p>
                        </div>
                        <span class="worker-badge"><?= (int)($resumenLedger['pagos_count'] ?? 0) ?></span>
                    </header>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_pagos'])): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-triangle-exclamation"></i>
                                <strong>Conceptos no disponibles</strong>
                                <span>La tabla de conceptos laborales no esta disponible en esta instalacion.</span>
                            </div>
                        <?php elseif (empty($conceptosLaborales)): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-file-circle-plus"></i>
                                <strong>Sin conceptos registrados</strong>
                                <span>Registra bonos, comisiones, descuentos o ajustes para que el saldo empiece a calcularse.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conceptosLaborales as $concepto): ?>
                                <?php
                                $conceptoEsContra = ($concepto['efecto'] ?? '') === 'en_contra';
                                $conceptoDireccion = $conceptoEsContra ? 'minus' : 'plus';
                                ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($concepto['concepto'] ?? null, 'Sin concepto') ?></div>
                                            <div class="text-xs text-slate-500">
                                                <?= trab_view_date($concepto['fecha'] ?? null) ?> - <?= trab_view_safe($concepto['tipo'] ?? null) ?> - <?= trab_view_safe($concepto['efecto'] ?? null) ?>
                                            </div>
                                        </div>
                                        <strong class="worker-ledger-amount is-<?= $conceptoDireccion ?>"><?= trab_view_signed_money($concepto['monto'] ?? 0, $conceptoDireccion) ?></strong>
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
                    <header class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black">Anticipos</h3>
                            <p class="text-xs text-slate-500 mt-1">Registros laborales informativos; no son egresos de Caja en esta fase.</p>
                        </div>
                        <span class="worker-badge"><?= (int)($resumenLedger['anticipos_count'] ?? 0) ?></span>
                    </header>
                    <?php if ($puedeRegistrarAnticipo): ?>
                        <details class="worker-ledger-details">
                            <summary><span><i class="fas fa-plus mr-2"></i>Registrar anticipo</span></summary>
                            <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/anticipos') ?>" class="p-4">
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
                        </details>
                    <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                        <div class="worker-ledger-note m-4">Solo se pueden registrar anticipos a trabajadores activos.</div>
                    <?php endif; ?>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_anticipos'])): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-triangle-exclamation"></i>
                                <strong>Anticipos no disponibles</strong>
                                <span>La tabla de anticipos no esta disponible en esta instalacion.</span>
                            </div>
                        <?php elseif (empty($anticiposRecientes)): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-hand-holding-dollar"></i>
                                <strong>Sin anticipos registrados</strong>
                                <span>Cuando registres un anticipo, aqui se mostrara su monto y saldo pendiente.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($anticiposRecientes as $anticipo): ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($anticipo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_view_date($anticipo['fecha'] ?? null) ?> - <?= trab_view_safe($anticipo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong class="worker-ledger-amount is-debt"><?= trab_view_money($anticipo['monto'] ?? 0) ?></strong>
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
                    <header class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black">Prestamos</h3>
                            <p class="text-xs text-slate-500 mt-1">Deuda laboral del trabajador registrada fuera de Caja.</p>
                        </div>
                        <span class="worker-badge"><?= (int)($resumenLedger['prestamos_count'] ?? 0) ?></span>
                    </header>
                    <?php if ($puedeRegistrarPrestamo): ?>
                        <details class="worker-ledger-details">
                            <summary><span><i class="fas fa-plus mr-2"></i>Registrar prestamo</span></summary>
                            <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/prestamos') ?>" class="p-4">
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
                        </details>
                    <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                        <div class="worker-ledger-note m-4">Solo se pueden registrar prestamos a trabajadores activos.</div>
                    <?php endif; ?>
                    <div class="worker-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_prestamos'])): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-triangle-exclamation"></i>
                                <strong>Prestamos no disponibles</strong>
                                <span>La tabla de prestamos no esta disponible en esta instalacion.</span>
                            </div>
                        <?php elseif (empty($prestamosRecientes)): ?>
                            <div class="worker-ledger-empty">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <strong>Sin prestamos registrados</strong>
                                <span>Cuando registres un prestamo, aqui se mostrara su saldo vigente y abono sugerido.</span>
                            </div>
                        <?php else: ?>
                            <?php foreach ($prestamosRecientes as $prestamo): ?>
                                <div class="worker-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-black"><?= trab_view_safe($prestamo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="text-xs text-slate-500"><?= trab_view_date($prestamo['fecha'] ?? null) ?> - <?= trab_view_safe($prestamo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong class="worker-ledger-amount is-debt"><?= trab_view_money($prestamo['monto'] ?? 0) ?></strong>
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
                    <i class="fas fa-calendar-check"></i>
                    Captura manual
                </span>
            </div>

            <?php if (empty($ledgerDisponible['trabajador_asistencias'])): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-slate-300 mb-3"><i class="fas fa-calendar-xmark"></i></div>
                    <h3 class="font-black text-lg">Asistencias no disponibles</h3>
                    <p class="text-sm text-slate-500 mt-1">La tabla de asistencias laborales no esta disponible en esta instalacion.</p>
                </div>
            <?php else: ?>
                <?php if ($puedeRegistrarAsistencia): ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/asistencias') ?>" class="p-5 border-b border-slate-200">
                        <?= csrf_field() ?>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="worker-meta-label" for="asistencia_fecha">Fecha</label>
                                <input id="asistencia_fecha" class="worker-input mt-1" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div>
                                <label class="worker-meta-label" for="asistencia_tipo">Tipo</label>
                                <select id="asistencia_tipo" class="worker-input mt-1" name="tipo" required>
                                    <option value="asistencia">Asistencia</option>
                                    <option value="retardo">Retardo</option>
                                    <option value="falta">Falta</option>
                                    <option value="permiso">Permiso</option>
                                    <option value="incapacidad">Incapacidad</option>
                                    <option value="descanso">Descanso</option>
                                    <option value="horas_extra">Horas extra</option>
                                </select>
                            </div>
                            <div>
                                <label class="worker-meta-label" for="asistencia_hora_entrada">Entrada</label>
                                <input id="asistencia_hora_entrada" class="worker-input mt-1" type="time" name="hora_entrada">
                            </div>
                            <div>
                                <label class="worker-meta-label" for="asistencia_hora_salida">Salida</label>
                                <input id="asistencia_hora_salida" class="worker-input mt-1" type="time" name="hora_salida">
                            </div>
                            <div>
                                <label class="worker-meta-label" for="asistencia_horas">Horas</label>
                                <input id="asistencia_horas" class="worker-input mt-1" type="number" min="0" step="0.25" name="horas" placeholder="Opcional">
                            </div>
                            <div>
                                <label class="worker-meta-label" for="asistencia_horas_extra">Horas extra</label>
                                <input id="asistencia_horas_extra" class="worker-input mt-1" type="number" min="0" step="0.25" name="horas_extra" placeholder="Opcional">
                            </div>
                            <div class="md:col-span-2">
                                <label class="worker-meta-label" for="asistencia_observaciones">Observaciones</label>
                                <input id="asistencia_observaciones" class="worker-input mt-1" type="text" maxlength="255" name="observaciones" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-slate-500">Un registro por trabajador y dia. No genera nomina, pagos reales ni movimientos de Caja.</div>
                            <button class="worker-btn" type="submit">
                                <i class="fas fa-calendar-plus"></i>
                                Registrar asistencia
                            </button>
                        </div>
                    </form>
                <?php elseif (($trabajador['estado'] ?? '') !== 'activo'): ?>
                    <div class="worker-ledger-note m-4">Solo se pueden registrar asistencias a trabajadores activos.</div>
                <?php endif; ?>

                <?php if (empty($asistenciasRecientes)): ?>
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
            <?php endif; ?>
        </div>
    </section>
</div>
