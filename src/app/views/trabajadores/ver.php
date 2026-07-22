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
$reversionesPagoCaja = is_array($reversionesPagoCaja ?? null) ? $reversionesPagoCaja : [];
$reversionPagoCajaTokens = is_array($reversionPagoCajaTokens ?? null) ? $reversionPagoCajaTokens : [];
$avisoNominaPendiente = in_array($avisoNominaPendiente ?? null, ['sin_grupo', 'sin_salario'], true)
    ? $avisoNominaPendiente
    : null;

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

if (!function_exists('trab_view_inicial')) {
    function trab_view_inicial($value)
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars(strtoupper(mb_substr($text !== '' ? $text : 'T', 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('trab_view_estado_meta')) {
    function trab_view_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'activo'   => ['Activo', 'is-activo', 'fa-circle-check'],
            'inactivo' => ['Inactivo', 'is-inactivo', 'fa-circle-pause'],
            'baja'     => ['Baja', 'is-baja', 'fa-user-slash'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

$trabajadorId = (int)($trabajador['id'] ?? 0);
$estadoTrabajador = (string)($trabajador['estado'] ?? '');
[$estadoLabel, $estadoClass, $estadoIcon] = trab_view_estado_meta($estadoTrabajador);

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
    --wk-brand: var(--brand-primary, #1B2746);
    --wk-brand-2: var(--brand-secondary, #0F172A);
    --wk-gold: var(--brand-accent, #BD9441);
    --wk-gold-soft: color-mix(in srgb, var(--wk-gold) 15%, #FFFFFF);
    --wk-gold-line: color-mix(in srgb, var(--wk-gold) 42%, #E4D4B0);
    --wk-gold-ink: color-mix(in srgb, var(--wk-gold) 58%, var(--wk-brand));
    --wk-ivory: #F5F5F7; --wk-ivory-2: #FAFAFC;
    --wk-surface: #FFFFFF; --wk-surface-warm: #F5F5F7;
    --wk-border: color-mix(in srgb, var(--wk-brand) 7%, #E7E1D4);
    --wk-ring: color-mix(in srgb, var(--wk-gold) 32%, transparent);
    --wk-text: color-mix(in srgb, var(--wk-brand) 46%, #707B8C);
    --wk-muted: #8791A2;
    --wk-heading: color-mix(in srgb, var(--wk-brand) 66%, #566172);
    --wk-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --wk-success: #1E9E63; --wk-success-bg: #E7F4EC;
    --wk-warning: #C2841C; --wk-warning-bg: #FAF0DC;
    --wk-danger: #B4392B; --wk-danger-bg: #F8EAE5;
    --wk-info: #2F77E0; --wk-info-bg: #E6EFFC;
    --wk-muted-2: color-mix(in srgb, var(--wk-brand) 34%, #8590A1);
    min-height: 100%; color: var(--wk-text); font-family: var(--wk-sans); font-weight: 450; line-height: 1.5;
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.worker-detail-page .wk-shell { display: grid; gap: 20px; }
.worker-detail-page .wk-title-lockup { display: grid; grid-template-columns: 52px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.worker-detail-page .wk-avatar { width: 52px; height: 52px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.3rem; font-weight: 700; font-family: var(--wk-serif);
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--wk-gold), var(--wk-brand) 54%, color-mix(in srgb, var(--wk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--wk-brand) 72%, transparent); }
.worker-detail-page .wk-kicker { margin: 0 0 2px; color: var(--wk-muted); font-size: .72rem; font-weight: 600; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.worker-detail-page .wk-title { margin: 0; font-family: var(--wk-serif); color: var(--wk-heading); font-weight: 650; font-size: clamp(1.9rem, 3.4vw, 2.7rem); line-height: 1.02; }
.worker-detail-page .wk-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }

.worker-detail-page .wk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .75rem; font-weight: 600; border: 1px solid transparent; }
.worker-detail-page .wk-badge.is-activo { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.worker-detail-page .wk-badge.is-inactivo { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }
.worker-detail-page .wk-badge.is-baja { color: color-mix(in srgb, var(--wk-danger) 82%, #000); background: var(--wk-danger-bg); border-color: color-mix(in srgb, var(--wk-danger) 26%, #fff); }
.worker-detail-page .wk-badge.is-soft { color: var(--wk-muted); background: var(--wk-surface-warm); border-color: var(--wk-border); }
.worker-detail-page .wk-badge.is-ok { color: color-mix(in srgb, var(--wk-success) 78%, #000); background: var(--wk-success-bg); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.worker-detail-page .wk-badge.is-warn { color: color-mix(in srgb, var(--wk-warning) 82%, #000); background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 28%, #fff); }

.worker-detail-page .wk-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
/* Neutraliza el .grid{min-height:200px} global de performance-optimization.css */
.worker-detail-page .grid { min-height: 0; }

/* Pestañas de la ficha: control segmentado (local), distinto de la subnav de sección */
.worker-detail-page .wk-tabs-block { display: grid; gap: 7px; justify-items: start; }
.worker-detail-page .wk-scope-label {
    display: inline-flex; align-items: center; gap: 8px; padding-left: 4px;
    font-size: .64rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: var(--wk-muted); max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.worker-detail-page .wk-scope-label::before {
    content: ""; flex: 0 0 auto; width: 14px; height: 2px; border-radius: 999px; background: var(--wk-gold);
}
.worker-detail-page .wk-tabs {
    display: flex; gap: 4px; padding: 5px; width: fit-content; max-width: 100%;
    background: color-mix(in srgb, var(--wk-brand) 5%, var(--wk-ivory-2));
    border: 1px solid var(--wk-border); border-radius: 15px;
}
.worker-detail-page .wk-tab {
    position: relative; display: inline-flex; align-items: center; gap: 8px;
    border: 1px solid transparent; border-radius: 11px;
    background: transparent; color: var(--wk-muted-2);
    min-height: 40px; padding: 0 16px 2px; font-size: .85rem; font-weight: 650; cursor: pointer;
    font-family: var(--wk-sans); line-height: 1; white-space: nowrap;
    transition: color .16s ease, background .16s ease, box-shadow .16s ease;
}
.worker-detail-page .wk-tab i { font-size: .8rem; color: color-mix(in srgb, var(--wk-muted) 80%, #fff); transition: color .16s ease; }
.worker-detail-page .wk-tab:hover { color: var(--wk-heading); background: rgba(255,255,255,.65); }
.worker-detail-page .wk-tab:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--wk-ring); }
.worker-detail-page .wk-tab.is-active {
    background: var(--wk-surface); color: var(--wk-heading); border-color: var(--wk-border);
    box-shadow: 0 1px 2px rgba(27,39,70,.05), 0 6px 14px -8px color-mix(in srgb, var(--wk-brand) 38%, transparent);
}
.worker-detail-page .wk-tab.is-active i { color: var(--wk-gold-ink); }
.worker-detail-page .wk-tab.is-active::after {
    content: ""; position: absolute; left: 16px; right: 16px; bottom: 5px; height: 2px; border-radius: 999px;
    background: linear-gradient(90deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 40%, #fff));
}
.worker-detail-page.wk-tabs-ready .wk-tabpane:not(.is-open) { display: none; }
.worker-detail-page.wk-tabs-ready .wk-tabpane.is-open { animation: wkRise .32s var(--wk-ease, cubic-bezier(.22,1,.36,1)); }
.worker-detail-page .wk-tabpane { display: grid; gap: 20px; }
@keyframes wkRise { from { opacity: 0; transform: translateY(7px); } to { opacity: 1; transform: none; } }
@media (max-width: 768px) {
    .worker-detail-page .wk-tabs-block { justify-items: stretch; }
    .worker-detail-page .wk-tabs { width: 100%; flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .worker-detail-page .wk-tabs::-webkit-scrollbar { display: none; }
    .worker-detail-page .wk-tab { flex: 1 0 auto; justify-content: center; }
}
.worker-detail-page .wk-toolbar form { display: inline-flex; margin: 0; }
.worker-detail-page .wk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 15px;
    border-radius: 11px; border: 1px solid var(--wk-border); background: rgba(255,255,255,.86); color: var(--wk-text); font-weight: 650; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.worker-detail-page .wk-btn:hover { transform: translateY(-1px); border-color: var(--wk-gold-line); color: var(--wk-gold-ink); }
.worker-detail-page .wk-btn-gold { position: relative; overflow: hidden; background: linear-gradient(135deg, var(--wk-gold), color-mix(in srgb, var(--wk-gold) 76%, #000)); border-color: transparent; color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--wk-gold) 58%, transparent); }
.worker-detail-page .wk-btn-gold:hover { color: #fff; border-color: transparent; }
.worker-detail-page .wk-btn-gold::after {
    content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent);
    transform: translateX(-170%) skewX(-18deg);
}
.worker-detail-page .wk-btn-gold:hover::after { transition: transform .7s ease; transform: translateX(330%) skewX(-18deg); }
.worker-detail-page .wk-btn-off { color: var(--wk-danger); border-color: color-mix(in srgb, var(--wk-danger) 24%, var(--wk-border)); }
.worker-detail-page .wk-btn-off:hover { color: var(--wk-danger); background: var(--wk-danger-bg); }
.worker-detail-page .wk-btn-on { color: var(--wk-success); border-color: color-mix(in srgb, var(--wk-success) 24%, var(--wk-border)); }
.worker-detail-page .wk-btn-on:hover { color: var(--wk-success); background: var(--wk-success-bg); }
.worker-detail-page .wk-btn-off.is-confirming { background: linear-gradient(135deg, var(--wk-warning), color-mix(in srgb, var(--wk-warning) 72%, #000)); color: #fff; border-color: transparent; }

.worker-action-toast { position: fixed; right: 22px; bottom: 22px; z-index: 15000; max-width: min(390px, calc(100vw - 32px));
    border: 1px solid var(--wk-gold-line, #E4D4B0); border-radius: 14px; background: var(--wk-gold-soft, #FBF3DE); color: var(--wk-gold-ink, #6b521f);
    padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 600; line-height: 1.42;
    opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
.worker-action-toast.is-visible { opacity: 1; transform: translateY(0); }

.worker-detail-page .wk-panel { background: rgba(255,255,255,.88); border: 1px solid var(--wk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); }
.worker-detail-page .wk-panel-pad { padding: 20px; }
.worker-detail-page .wk-panel-head { padding: 15px 18px; border-bottom: 1px solid var(--wk-border); border-radius: 16px 16px 0 0; background: linear-gradient(180deg, color-mix(in srgb, var(--wk-surface-warm) 82%, #fff), rgba(255,255,255,.92)); display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.worker-detail-page .wk-panel-title { font-family: var(--wk-serif); font-size: 1.35rem; font-weight: 650; color: var(--wk-heading); line-height: 1.1; }
.worker-detail-page .wk-panel-sub { font-size: .84rem; color: var(--wk-muted); margin-top: 4px; line-height: 1.5; max-width: 64ch; font-weight: 500; }
.worker-detail-page .wk-sec-head { display: flex; align-items: center; gap: 12px; min-width: 0; }
.worker-detail-page .wk-sec-icon { flex: none; width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; font-size: 1.05rem; border: 1px solid transparent; }
.worker-detail-page .wk-sec-icon.is-navy { background: color-mix(in srgb, var(--wk-brand) 12%, #fff); color: var(--wk-brand); border-color: color-mix(in srgb, var(--wk-brand) 20%, #fff); }
.worker-detail-page .wk-sec-icon.is-gold { background: var(--wk-gold-soft); color: var(--wk-gold-ink); border-color: var(--wk-gold-line); }
.worker-detail-page .wk-sec-icon.is-green { background: var(--wk-success-bg); color: var(--wk-success); border-color: color-mix(in srgb, var(--wk-success) 26%, #fff); }
.worker-detail-page .wk-sec-icon.is-blue { background: var(--wk-info-bg); color: var(--wk-info); border-color: color-mix(in srgb, var(--wk-info) 24%, #fff); }
.worker-detail-page .wk-sec-icon.is-amber { background: var(--wk-warning-bg); color: var(--wk-warning); border-color: color-mix(in srgb, var(--wk-warning) 26%, #fff); }

/* Resumen de cuenta (hero) */
.worker-detail-page .wk-summary { display: grid; grid-template-columns: minmax(260px, .9fr) minmax(0, 1.3fr); gap: 14px; }
.worker-detail-page .wk-balance { position: relative; overflow: hidden; border-radius: 16px; border: 1px solid var(--wk-border); padding: 18px; background: linear-gradient(160deg, rgba(255,255,255,.9), var(--wk-surface-warm)); box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 28px -25px rgba(27,39,70,.22); }
.worker-detail-page .wk-balance::after {
    content: ""; position: absolute; right: -36px; bottom: -48px; width: 150px; height: 150px; border-radius: 999px; pointer-events: none;
    border: 1.5px solid color-mix(in srgb, var(--wk-gold) 34%, transparent);
    box-shadow: 0 0 0 24px color-mix(in srgb, var(--wk-gold) 7%, transparent), 0 0 0 52px color-mix(in srgb, var(--wk-gold) 4%, transparent);
}
.worker-detail-page .wk-balance > * { position: relative; z-index: 1; }
.worker-detail-page .wk-balance-label { color: var(--wk-muted); font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.worker-detail-page .wk-balance-value { margin-top: 6px; font-family: var(--wk-serif); font-size: clamp(2.25rem, 5vw, 3rem); font-weight: 650; line-height: 1; color: var(--wk-heading); animation: wkRise .5s cubic-bezier(.22,1,.36,1) .08s backwards; }
.worker-detail-page .wk-balance.is-positive .wk-balance-value { color: var(--wk-success); }
.worker-detail-page .wk-balance.is-negative .wk-balance-value { color: var(--wk-warning); }
.worker-detail-page .wk-balance-caption { margin-top: 10px; color: var(--wk-muted-2); font-size: .86rem; line-height: 1.55; }
.worker-detail-page .wk-balance-cta {
    display: inline-flex; align-items: center; gap: 8px; margin-top: 14px;
    min-height: 40px; padding: 0 18px; border-radius: 11px; border: 1px solid transparent;
    background: var(--wk-brand); color: #fff; font-weight: 650; font-size: .85rem; line-height: 1;
    text-decoration: none; cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease;
}
.worker-detail-page .wk-balance-cta i { font-size: .8rem; color: rgba(255,255,255,.85); }
.worker-detail-page .wk-balance-cta:hover { color: #fff; transform: translateY(-1px); box-shadow: 0 10px 20px -12px color-mix(in srgb, var(--wk-brand) 65%, transparent); }
.worker-detail-page .wk-balance-cta:focus-visible { outline: none; box-shadow: 0 0 0 3px var(--wk-ring); }
.worker-detail-page .wk-factors { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; align-content: start; }
.worker-detail-page .wk-factor { position: relative; border: 1px solid var(--wk-border); border-radius: 14px; padding: 13px 14px 13px 18px; background: var(--wk-surface); }
.worker-detail-page .wk-factor::before { content: ""; position: absolute; left: 7px; top: 13px; bottom: 13px; width: 3px; border-radius: 999px; background: var(--wk-border); }
.worker-detail-page .wk-factor.is-plus::before { background: color-mix(in srgb, var(--wk-success) 55%, #fff); }
.worker-detail-page .wk-factor.is-minus::before { background: color-mix(in srgb, var(--wk-danger) 45%, #fff); }
.worker-detail-page .wk-factor.is-gold::before { background: var(--wk-gold-line); }
.worker-detail-page .wk-factor small { display: block; color: var(--wk-muted); font-size: .69rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
.worker-detail-page .wk-factor strong { display: block; margin-top: 5px; font-family: var(--wk-serif); font-size: 1.4rem; font-weight: 650; color: var(--wk-heading); }
.worker-detail-page .wk-factor.is-plus strong { color: var(--wk-success); }
.worker-detail-page .wk-factor.is-minus strong { color: var(--wk-danger); }
.worker-detail-page .wk-factor.is-gold { background: var(--wk-gold-soft); border-color: var(--wk-gold-line); }
.worker-detail-page .wk-factor.is-gold strong { color: var(--wk-gold-ink); }

/* Meta grid */
.worker-detail-page .wk-meta-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.worker-detail-page .wk-meta-label { font-size: .69rem; color: var(--wk-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
.worker-detail-page .wk-meta-value { margin-top: 3px; font-weight: 600; color: var(--wk-heading); word-break: break-word; }
.worker-detail-page .wk-meta-value.is-soft { font-weight: 500; color: var(--wk-text); }
.worker-detail-page .wk-meta-value.is-notes { font-weight: 500; color: var(--wk-text); white-space: pre-line; }

/* Forms */
.worker-detail-page label.wk-label { display: block; font-size: .71rem; color: var(--wk-muted); font-weight: 650; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 5px; }
.worker-detail-page .wk-input, .worker-detail-page textarea.wk-input, .worker-detail-page select.wk-input {
    width: 100%; min-height: 40px; border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 11px; padding: 9px 12px;
    color: var(--wk-text); font-weight: 560; font-size: .88rem; font-family: var(--wk-sans); transition: border-color .16s ease, box-shadow .16s ease, background .16s ease; }
.worker-detail-page .wk-input::placeholder, .worker-detail-page textarea.wk-input::placeholder { color: color-mix(in srgb, var(--wk-muted) 78%, #B8C0CB); font-weight: 520; }
.worker-detail-page textarea.wk-input { min-height: 80px; resize: vertical; }
.worker-detail-page select.wk-input { cursor: pointer; }
.worker-detail-page .wk-input:focus { border-color: var(--wk-gold); box-shadow: 0 0 0 3px var(--wk-ring); outline: none; background: #fff; }
.worker-detail-page .wk-hint { font-size: .8rem; color: var(--wk-muted-2); }
.worker-detail-page .wk-note { border: 1px solid var(--wk-border); background: var(--wk-surface-warm); border-radius: 12px; padding: 13px 15px; font-size: .88rem; color: var(--wk-muted-2); }
.worker-detail-page .wk-note.is-warn { background: var(--wk-warning-bg); border-color: color-mix(in srgb, var(--wk-warning) 26%, #fff); color: color-mix(in srgb, var(--wk-warning) 84%, #000); }

/* Details (registrar) */
.worker-detail-page .wk-details { border: 1px solid var(--wk-border); border-radius: 13px; background: var(--wk-surface-warm); overflow: hidden; }
.worker-detail-page .wk-details summary { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 48px; padding: 0 14px; cursor: pointer; color: var(--wk-gold-ink); font-size: .9rem; font-weight: 600; list-style: none; }
.worker-detail-page .wk-details summary::-webkit-details-marker { display: none; }
.worker-detail-page .wk-details summary::after { content: "+"; display: grid; place-items: center; width: 26px; height: 26px; border-radius: 999px; background: var(--wk-gold-soft); color: var(--wk-gold-ink); border: 1px solid var(--wk-gold-line); font-weight: 700; }
.worker-detail-page .wk-details[open] summary { border-bottom: 1px solid var(--wk-border); }
.worker-detail-page .wk-details[open] summary::after { content: "\2212"; }
.worker-detail-page .wk-details .wk-form-pad { padding: 14px; background: rgba(255,255,255,.92); }
.worker-detail-page .wk-panel-pad > .wk-details { max-width: 980px; }
.worker-detail-page .wk-panel-pad > .wk-details .wk-form-pad > .grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}

/* Pago caja layout */
.worker-detail-page .wk-pay-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(180px, 1fr));
    gap: 12px;
    max-width: 980px;
    align-items: end;
    padding: 16px;
    border: 1px solid color-mix(in srgb, var(--wk-success) 16%, var(--wk-border));
    border-radius: 14px;
    background: color-mix(in srgb, var(--wk-success-bg) 22%, #fff);
}
.worker-detail-page .wk-pay-grid > div { min-width: 0; }
.worker-detail-page .wk-pay-grid > [style*="grid-column"] { grid-column: 1 / -1 !important; }

/* Pago cards */
.worker-detail-page .wk-pay-list { display: grid; gap: 12px; }
.worker-detail-page .wk-pay-card { border: 1px solid var(--wk-border); border-radius: 14px; padding: 14px; background: rgba(255,255,255,.9); transition: border-color .16s ease, box-shadow .16s ease; }
.worker-detail-page .wk-pay-card:hover { border-color: var(--wk-gold-line); box-shadow: 0 12px 26px -22px rgba(27,39,70,.35); }
.worker-detail-page .wk-pay-top { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
.worker-detail-page .wk-pay-amount { font-family: var(--wk-serif); font-size: 1.42rem; font-weight: 650; color: var(--wk-heading); }
.worker-detail-page .wk-pay-meta { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px 14px; margin-top: 11px; }
.worker-detail-page .wk-revert { margin-top: 12px; border-top: 1px solid var(--wk-border); padding-top: 12px; display: grid; grid-template-columns: 1fr auto; gap: 10px; align-items: end; }

/* Ledger cards */
.worker-detail-page .wk-ledger-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.worker-detail-page .wk-ledger-card { border: 1px solid var(--wk-border); border-radius: 16px; background: rgba(255,255,255,.9); overflow: hidden; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 28px -25px rgba(27,39,70,.22); }
.worker-detail-page .wk-ledger-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 14px 16px; border-bottom: 1px solid var(--wk-border); }
.worker-detail-page .wk-ledger-card.is-green .wk-ledger-head { background: color-mix(in srgb, var(--wk-success-bg) 60%, #fff); border-bottom-color: color-mix(in srgb, var(--wk-success) 20%, var(--wk-border)); }
.worker-detail-page .wk-ledger-card.is-amber .wk-ledger-head { background: color-mix(in srgb, var(--wk-warning-bg) 60%, #fff); border-bottom-color: color-mix(in srgb, var(--wk-warning) 20%, var(--wk-border)); }
.worker-detail-page .wk-ledger-card.is-danger .wk-ledger-head { background: color-mix(in srgb, var(--wk-danger-bg) 55%, #fff); border-bottom-color: color-mix(in srgb, var(--wk-danger) 18%, var(--wk-border)); }
.worker-detail-page .wk-ledger-head h3 { font-family: var(--wk-serif); font-size: 1.15rem; font-weight: 650; color: var(--wk-heading); }
.worker-detail-page .wk-ledger-head p { font-size: .78rem; color: var(--wk-muted-2); margin-top: 2px; }
.worker-detail-page .wk-ledger-body { padding: 14px 16px; display: grid; gap: 10px; max-height: 420px; overflow: auto; }
.worker-detail-page .wk-ledger-row { border: 1px solid var(--wk-border); border-radius: 12px; padding: 11px 12px; background: color-mix(in srgb, var(--wk-surface-warm) 74%, #fff); }
.worker-detail-page .wk-ledger-amount { font-weight: 700; white-space: nowrap; }
.worker-detail-page .wk-ledger-amount.is-plus { color: var(--wk-success); }
.worker-detail-page .wk-ledger-amount.is-minus, .worker-detail-page .wk-ledger-amount.is-debt { color: var(--wk-danger); }
.worker-detail-page .wk-empty-box { display: grid; place-items: center; min-height: 150px; padding: 24px; text-align: center; color: var(--wk-muted); }
.worker-detail-page .wk-empty-box i { width: 54px; height: 54px; display: grid; place-items: center; border-radius: 16px; background: var(--wk-gold-soft); color: var(--wk-gold-ink); font-size: 1.2rem; margin-bottom: 10px; }
.worker-detail-page .wk-empty-box strong { display: block; color: var(--wk-heading); font-weight: 650; }
.worker-detail-page .wk-empty-box span { display: block; max-width: 26rem; margin-top: 4px; font-size: .84rem; line-height: 1.45; }

.worker-detail-page .wk-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
.worker-detail-page .wk-table thead { background: var(--wk-surface-warm); border-bottom: 1px solid var(--wk-border); }
.worker-detail-page .wk-table th { padding: 11px 13px; color: var(--wk-muted); font-size: .66rem; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.worker-detail-page .wk-table th.is-end, .worker-detail-page .wk-table td.is-end { text-align: right; }
.worker-detail-page .wk-table td { padding: 12px 13px; border-bottom: 1px solid var(--wk-border); vertical-align: middle; }
.worker-detail-page .wk-table tbody tr { transition: background .14s ease; }
.worker-detail-page .wk-table tbody tr:hover { background: var(--wk-ivory-2); }
.worker-detail-page .wk-table tbody tr:last-child td { border-bottom: 0; }
.worker-detail-page .wk-strong { font-weight: 620; color: var(--wk-heading); }
.worker-detail-page .wk-sub { color: var(--wk-muted-2); font-size: .78rem; }
.worker-detail-page .wk-link { color: var(--wk-info); font-weight: 600; text-decoration: none; }
.worker-detail-page .wk-link:hover { text-decoration: underline; }

@media (max-width: 1080px) {
    .worker-detail-page .wk-summary, .worker-detail-page .wk-pay-grid, .worker-detail-page .wk-ledger-grid { grid-template-columns: 1fr; }
    .worker-detail-page .wk-meta-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .worker-detail-page .wk-pay-grid > [style*="grid-column"] { grid-column: 1 / -1 !important; }
}
@media (max-width: 640px) {
    .worker-detail-page .wk-factors, .worker-detail-page .wk-meta-grid { grid-template-columns: 1fr; }
    .worker-detail-page .wk-revert { grid-template-columns: 1fr; }
    .worker-detail-page .wk-panel-pad > .wk-details .wk-form-pad > .grid { grid-template-columns: 1fr !important; }
    .worker-detail-page .wk-toolbar { justify-content: flex-start; }
}
.worker-detail-page .wk-aviso-nomina {
    display: flex; align-items: center; flex-wrap: wrap; gap: 12px;
    padding: 12px 16px; border-radius: 14px;
    background: rgba(191, 144, 0, .10);
    border: 1px solid rgba(191, 144, 0, .30);
    color: #7a5c00; font-size: 14px; line-height: 1.45;
}
.worker-detail-page .wk-aviso-nomina > i { font-size: 18px; color: #9a7400; }
.worker-detail-page .wk-aviso-nomina > div { flex: 1 1 260px; min-width: 0; }
.worker-detail-page .wk-aviso-nomina-btn { white-space: nowrap; }
.worker-detail-page .wk-aviso-nomina-hint { font-size: 13px; font-style: italic; }
@media (prefers-reduced-motion: reduce) {
    .worker-detail-page .wk-btn-gold::after { display: none; }
    .worker-detail-page .wk-balance-value,
    .worker-detail-page.wk-tabs-ready .wk-tabpane.is-open { animation: none !important; }
    .worker-detail-page * { transition-duration: .01ms !important; }
}
</style>

<div class="worker-detail-page p-4 sm:p-6">
    <div class="wk-shell">

        <!-- Encabezado -->
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="wk-title-lockup">
                <div class="wk-avatar"><?= trab_view_inicial($trabajador['nombre_completo'] ?? '') ?></div>
                <div>
                    <p class="wk-kicker">Personal del hotel</p>
                    <h1 class="wk-title"><?= trab_view_safe($trabajador['nombre_completo'] ?? null) ?></h1>
                    <div class="wk-chips">
                        <span class="wk-badge <?= $estadoClass ?>"><i class="fas <?= $estadoIcon ?>"></i> <?= $estadoLabel ?></span>
                        <span class="wk-badge is-soft"><i class="fas fa-briefcase"></i> <?= trab_view_safe($trabajador['rol_laboral'] ?? null, 'Sin rol') ?></span>
                        <?php if (!empty($trabajador['periodicidad_pago'])): ?>
                            <span class="wk-badge is-soft"><i class="fas fa-calendar-day"></i> Pago <?= trab_view_safe($trabajador['periodicidad_pago']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="wk-toolbar lg:justify-end">
                <?php $back_arrow_href = back_url('trabajadores'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a class="wk-btn ms-back-legacy" href="<?= back_url('trabajadores') ?>"><i class="fas fa-arrow-left"></i> Volver</a>
                <a class="wk-btn" href="<?= url('trabajadores/' . $trabajadorId . '/editar') ?>"><i class="fas fa-pen"></i> Editar datos</a>
                <a class="wk-btn" href="<?= url('trabajadores/pagos-caja/simulador?trabajador_id=' . $trabajadorId) ?>"><i class="fas fa-cash-register"></i> Simulador</a>
                <?php if ($estadoTrabajador === 'baja'): ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/reactivar') ?>">
                        <?= csrf_field() ?>
                        <button class="wk-btn wk-btn-on" type="submit"><i class="fas fa-rotate-left"></i> Reactivar</button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/baja-logica') ?>" data-ms-confirm data-ms-type="warning" data-ms-icon="logout" data-ms-title="¿Dar de baja al trabajador?" data-ms-msg="La baja conserva el registro del trabajador; podrás reactivarlo después." data-ms-ok="Confirmar baja">
                        <?= csrf_field() ?>
                        <button class="wk-btn wk-btn-off" type="submit"><i class="fas fa-user-slash"></i> Dar de baja</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <?php $subnav_section = 'personal'; $subnav_active = 'equipo'; include APP_PATH . '/views/partials/section_subnav.php'; ?>

        <?php if ($avisoNominaPendiente !== null): ?>
        <!-- Aviso: configuracion de nomina incompleta -->
        <section class="wk-aviso-nomina" role="status">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <div>
                <?php if ($avisoNominaPendiente === 'sin_grupo'): ?>
                    <strong>Le falta su grupo de pago en N&oacute;mina.</strong>
                    Hasta que se lo asignes, no entrar&aacute; a los periodos de n&oacute;mina (no aparecer&aacute; al calcularla).
                <?php else: ?>
                    <strong>Le falta registrar su salario en N&oacute;mina.</strong>
                    Ya tiene grupo de pago, pero sin salario registrado la n&oacute;mina le calcular&aacute; sueldo de $0.
                <?php endif; ?>
            </div>
            <?php if (function_exists('can') && can('nomina.view')): ?>
                <a class="wk-btn wk-aviso-nomina-btn" href="<?= url('nomina/empleados/' . $trabajadorId) ?>"><i class="fas fa-user-gear"></i> <?= $avisoNominaPendiente === 'sin_grupo' ? 'Asignar grupo de pago' : 'Registrar salario' ?></a>
            <?php else: ?>
                <span class="wk-aviso-nomina-hint">P&iacute;dele a un administrador completarlo en N&oacute;mina &rarr; Empleados.</span>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Resumen de cuenta -->
        <section class="wk-summary">
            <div class="wk-balance <?= $pagoCajaDisponibleClase ?>">
                <div class="wk-balance-label">Disponible para pagar ahora</div>
                <div class="wk-balance-value"><?= trab_view_money($pagoCajaSaldoDisponible) ?></div>
                <p class="wk-balance-caption">
                    Es lo que se le puede pagar hoy desde Caja, despu&eacute;s de restar lo que ya se le pag&oacute;.<br>
                    Bruto del trabajo: <strong><?= trab_view_money($pagoCajaSaldoBase) ?></strong> &middot; Ya pagado en Caja: <strong><?= trab_view_money($pagoCajaPagosAplicados) ?></strong>
                </p>
                <?php if ($pagoCajaSaldoDisponible > 0 && !empty($pagoCaja['elegible']) && !empty($pagoCajaToken)): ?>
                    <a class="wk-balance-cta ms-pressable" href="#t=pagos" data-wk-goto-tab="pagos"><i class="fas fa-money-bill-wave"></i> Pagarle ahora</a>
                <?php endif; ?>
            </div>
            <div class="wk-factors">
                <div class="wk-factor is-plus"><small>Le suma (a favor)</small><strong><?= trab_view_money($ledgerConceptosFavor) ?></strong></div>
                <div class="wk-factor is-minus"><small>Le resta (descuentos)</small><strong><?= trab_view_money($ledgerTotalDescuentos) ?></strong></div>
                <div class="wk-factor"><small>Ya pagado en Caja</small><strong><?= trab_view_money($pagoCajaPagosAplicados) ?></strong></div>
                <div class="wk-factor is-gold"><small>Disponible</small><strong><?= trab_view_money($pagoCajaSaldoDisponible) ?></strong></div>
            </div>
        </section>

        <div class="wk-tabs-block">
            <span class="wk-scope-label">Ficha de <?= trab_view_safe($trabajador['nombre_completo'] ?? null, 'este trabajador') ?></span>
            <div class="wk-tabs" role="tablist" aria-label="Secciones de la ficha">
                <button type="button" class="wk-tab is-active" data-wk-tab="resumen" role="tab" aria-selected="true"><i class="fas fa-id-card"></i> Resumen</button>
                <button type="button" class="wk-tab" data-wk-tab="pagos" role="tab" aria-selected="false"><i class="fas fa-money-bill-wave"></i> Pagarle</button>
                <button type="button" class="wk-tab" data-wk-tab="cuenta" role="tab" aria-selected="false"><i class="fas fa-scale-balanced"></i> Cuenta</button>
                <button type="button" class="wk-tab" data-wk-tab="actividad" role="tab" aria-selected="false"><i class="fas fa-calendar-check"></i> Actividad</button>
            </div>
        </div>

        <div class="wk-tabpane is-open" data-wk-pane="resumen">
        <!-- Datos del trabajador -->
        <section class="wk-panel">
            <div class="wk-panel-head">
                <div class="wk-sec-head">
                    <span class="wk-sec-icon is-navy"><i class="fas fa-id-card"></i></span>
                    <h2 class="wk-panel-title">Datos del trabajador</h2>
                </div>
            </div>
            <div class="wk-panel-pad">
            <div class="wk-meta-grid">
                <div><div class="wk-meta-label">Nombre completo</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['nombre_completo'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Identificaci&oacute;n</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['identificacion'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Rol o puesto</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['rol_laboral'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Usuario del sistema</div><div class="wk-meta-value is-soft"><?= trab_view_safe($trabajador['usuario_nombre'] ?? $trabajador['usuario_login'] ?? null, 'Sin usuario') ?></div></div>
                <div><div class="wk-meta-label">Tel&eacute;fono</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['telefono'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Correo</div><div class="wk-meta-value is-soft"><?= trab_view_safe($trabajador['email'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Fecha de alta</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['fecha_alta'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Fecha de baja</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['fecha_baja'] ?? null) ?></div></div>
                <div><div class="wk-meta-label">Salario base</div><div class="wk-meta-value"><?= array_key_exists('salario_base', $trabajador) && $trabajador['salario_base'] !== null ? trab_view_money($trabajador['salario_base']) : '-' ?></div></div>
                <div><div class="wk-meta-label">Cada cu&aacute;ndo se le paga</div><div class="wk-meta-value"><?= trab_view_safe($trabajador['periodicidad_pago'] ?? null) ?></div></div>
                <div style="grid-column: span 2"><div class="wk-meta-label">Notas</div><div class="wk-meta-value is-notes"><?= trab_view_safe($trabajador['notas'] ?? null, 'Sin notas') ?></div></div>
            </div>
            </div>
        </section>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>
        </div>

        <div class="wk-tabpane" data-wk-pane="pagos">
        <!-- Pagar al trabajador con Caja -->
        <section class="wk-panel">
            <div class="wk-panel-head">
                <div class="wk-sec-head">
                    <span class="wk-sec-icon is-green"><i class="fas fa-money-bill-wave"></i></span>
                    <div>
                        <h2 class="wk-panel-title">Pagar al trabajador (desde Caja)</h2>
                        <p class="wk-panel-sub">Esto saca el dinero de la Caja abierta y lo registra como pago a este trabajador. Solo se puede si hay un corte de Caja abierto y queda saldo disponible.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <?php if (!empty($pagoCaja['corte'])): ?>
                        <span class="wk-badge is-ok"><i class="fas fa-cash-register"></i> Corte #<?= (int)$pagoCaja['corte']['id'] ?> abierto</span>
                    <?php else: ?>
                        <span class="wk-badge is-warn"><i class="fas fa-ban"></i> Sin corte abierto</span>
                    <?php endif; ?>
                    <?php if (!empty($pagoCaja['elegible'])): ?>
                        <span class="wk-badge is-ok"><i class="fas fa-circle-check"></i> Se puede pagar</span>
                    <?php else: ?>
                        <span class="wk-badge is-warn"><i class="fas fa-lock"></i> Aún no se puede pagar</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="wk-panel-pad">

            <?php if (!empty($pagoCaja['elegible']) && !empty($pagoCajaToken)): ?>
                <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/registrar-pago-caja') ?>" class="wk-pay-grid"
                      onsubmit="var m = this.querySelector('[name=monto]'); return confirm('Vas a registrar un pago de $' + ((m && m.value) ? m.value : '0') + ' a este trabajador. El dinero SALE de la Caja abierta. ¿Confirmar?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pago_token" value="<?= trab_view_safe($pagoCajaToken, '') ?>">
                    <div>
                        <label class="wk-label" for="pago_caja_monto">Monto a pagar</label>
                        <input id="pago_caja_monto" class="wk-input" type="number" data-money-format="true" min="0.01" max="<?= trab_view_safe($pagoCaja['monto_maximo'] ?? '0.00') ?>" step="0.01" name="monto" value="<?= trab_view_safe($pagoCaja['monto_maximo'] ?? '0.00') ?>" required>
                    </div>
                    <div>
                        <label class="wk-label" for="pago_caja_metodo">M&eacute;todo</label>
                        <select id="pago_caja_metodo" class="wk-input" name="metodo_pago" required>
                            <?php foreach (($pagoCaja['metodos_pago'] ?? []) as $metodo => $label): ?>
                                <option value="<?= trab_view_safe($metodo) ?>"><?= trab_view_safe($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="wk-label" for="pago_caja_referencia">Referencia</label>
                        <input id="pago_caja_referencia" class="wk-input" type="text" maxlength="100" name="referencia" value="<?= trab_view_safe($pagoCajaReferencia, '') ?>" required>
                    </div>
                    <div>
                        <label class="wk-label" for="pago_caja_concepto">Concepto</label>
                        <input id="pago_caja_concepto" class="wk-input" type="text" maxlength="160" name="concepto" value="<?= trab_view_safe($pagoCajaConcepto, '') ?>">
                    </div>
                    <div>
                        <label class="wk-label" for="pago_caja_periodo_inicio">Periodo: desde</label>
                        <input id="pago_caja_periodo_inicio" class="wk-input" type="date" name="periodo_inicio">
                    </div>
                    <div>
                        <label class="wk-label" for="pago_caja_periodo_fin">Periodo: hasta</label>
                        <input id="pago_caja_periodo_fin" class="wk-input" type="date" name="periodo_fin">
                    </div>
                    <div style="grid-column: span 2">
                        <label class="wk-label" for="pago_caja_notas">Notas</label>
                        <input id="pago_caja_notas" class="wk-input" type="text" maxlength="1000" name="notas" placeholder="Opcional">
                    </div>
                    <div style="grid-column: 1 / -1" class="flex flex-wrap items-center justify-between gap-3">
                        <p class="wk-hint">M&aacute;ximo a pagar hoy: <strong style="color:var(--wk-heading)"><?= trab_view_money($pagoCajaMontoMaximo) ?></strong>. Al guardar se registra el egreso en Caja.</p>
                        <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-money-bill-wave"></i> Registrar pago</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="wk-note is-warn">
                    <strong style="color:inherit">Por ahora no se puede pagar a este trabajador desde Caja.</strong><br>
                    <?= trab_view_safe($pagoCaja['motivo_bloqueo'] ?? null, 'Este trabajador no es elegible para pago laboral con Caja.') ?>
                    <a class="wk-link" style="margin-left:4px" href="<?= url('trabajadores/pagos-caja/simulador?trabajador_id=' . $trabajadorId) ?>">Revisar simulador</a>
                </div>
            <?php endif; ?>
            </div>
        </section>

        <!-- Historial de pagos en Caja -->
        <section class="wk-panel">
            <div class="wk-panel-head">
                <div class="wk-sec-head">
                    <span class="wk-sec-icon is-gold"><i class="fas fa-receipt"></i></span>
                    <div>
                        <h2 class="wk-panel-title">Pagos hechos en Caja</h2>
                        <p class="wk-panel-sub">Cada pago que se le ha hecho desde Caja, con su corte y referencia. Si te equivocaste, puedes revertir uno.</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="wk-badge is-soft" href="<?= url('trabajadores/pagos-caja/reporte?trabajador_id=' . $trabajadorId) ?>" style="text-decoration:none"><i class="fas fa-file-invoice-dollar"></i> Reporte</a>
                    <span class="wk-badge is-ok"><i class="fas fa-circle-check"></i> Pagado <?= trab_view_money($pagosCajaLaboralesTotal) ?></span>
                </div>
            </div>
            <div class="wk-panel-pad">
                <?php if (empty($pagosCajaLaborales)): ?>
                    <div class="wk-empty-box">
                        <i class="fas fa-receipt"></i>
                        <strong>Sin pagos en Caja todav&iacute;a</strong>
                        <span>Cuando registres un pago con el panel de arriba, aparecer&aacute; aqu&iacute; con su corte y referencia.</span>
                    </div>
                <?php else: ?>
                    <div class="wk-pay-list">
                        <?php foreach ($pagosCajaLaborales as $pagoLaboral): ?>
                            <?php
                                $pagoLaboralId = (int)($pagoLaboral['id'] ?? 0);
                                $estadoPago = (string)($pagoLaboral['estado'] ?? '');
                                $reversionPago = is_array($reversionesPagoCaja[$pagoLaboralId] ?? null) ? $reversionesPagoCaja[$pagoLaboralId] : [];
                                $reversionToken = (string)($reversionPagoCajaTokens[$pagoLaboralId] ?? '');
                                $puedeRevertirPago = $estadoPago === 'pagado' && !empty($reversionPago['elegible']) && $reversionToken !== '';
                                $periodoInicio = trab_view_date($pagoLaboral['periodo_inicio'] ?? null);
                                $periodoFin = trab_view_date($pagoLaboral['periodo_fin'] ?? null);
                                $periodoTexto = ($periodoInicio === '-' && $periodoFin === '-') ? 'Sin periodo' : $periodoInicio . ' a ' . $periodoFin;
                                $creadoPor = trim((string)($pagoLaboral['creado_por_nombre'] ?? '')); if ($creadoPor === '') { $creadoPor = trim((string)($pagoLaboral['creado_por_login'] ?? '')); }
                                $actualizadoPor = trim((string)($pagoLaboral['actualizado_por_nombre'] ?? '')); if ($actualizadoPor === '') { $actualizadoPor = trim((string)($pagoLaboral['actualizado_por_login'] ?? '')); }
                            ?>
                            <article class="wk-pay-card">
                                <div class="wk-pay-top">
                                    <div class="wk-pay-amount"><?= trab_view_money($pagoLaboral['monto'] ?? 0) ?></div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="wk-badge <?= $estadoPago === 'pagado' ? 'is-ok' : 'is-warn' ?>">
                                            <i class="fas <?= $estadoPago === 'pagado' ? 'fa-circle-check' : 'fa-rotate-left' ?>"></i>
                                            <?= trab_view_safe($estadoPago) ?>
                                        </span>
                                        <span class="wk-sub"><?= trab_view_datetime($pagoLaboral['fecha_pago'] ?? null) ?></span>
                                    </div>
                                </div>
                                <div class="wk-pay-meta">
                                    <div><div class="wk-meta-label">M&eacute;todo</div><div class="wk-strong" style="text-transform:capitalize"><?= trab_view_safe($pagoLaboral['metodo_pago'] ?? null) ?></div></div>
                                    <div><div class="wk-meta-label">Caja / corte</div><div class="wk-strong"><?= trab_view_safe($pagoLaboral['caja_nombre'] ?? null) ?> <a class="wk-link" href="<?= url('caja/corte/' . (int)($pagoLaboral['corte_id'] ?? 0)) ?>">#<?= (int)($pagoLaboral['corte_id'] ?? 0) ?></a></div></div>
                                    <div><div class="wk-meta-label">Periodo</div><div class="wk-strong"><?= trab_view_safe($periodoTexto) ?></div></div>
                                    <div><div class="wk-meta-label">Referencia</div><div class="wk-strong"><?= trab_view_safe($pagoLaboral['referencia'] ?? null) ?></div></div>
                                    <div><div class="wk-meta-label">Registr&oacute;</div><div class="wk-sub" style="margin:0"><?= $creadoPor !== '' ? trab_view_safe($creadoPor) : 'Usuario no disponible' ?></div></div>
                                    <?php if (trim((string)($pagoLaboral['concepto'] ?? '')) !== ''): ?>
                                        <div><div class="wk-meta-label">Concepto</div><div class="wk-sub" style="margin:0"><?= trab_view_safe($pagoLaboral['concepto']) ?></div></div>
                                    <?php endif; ?>
                                </div>
                                <?php if (trim((string)($pagoLaboral['notas'] ?? '')) !== ''): ?>
                                    <p class="wk-sub" style="margin-top:8px"><strong style="color:var(--wk-heading)">Notas:</strong> <?= trab_view_safe($pagoLaboral['notas']) ?></p>
                                <?php endif; ?>

                                <?php if ($puedeRevertirPago): ?>
                                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/pagos-caja/' . $pagoLaboralId . '/revertir') ?>" class="wk-revert">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="reversion_token" value="<?= trab_view_safe($reversionToken, '') ?>">
                                        <div>
                                            <label class="wk-label" for="reversion_pago_caja_motivo_<?= $pagoLaboralId ?>">Motivo de la reversi&oacute;n</label>
                                            <input id="reversion_pago_caja_motivo_<?= $pagoLaboralId ?>" class="wk-input" type="text" maxlength="1000" name="motivo" placeholder="Explica por qu&eacute; reviertes este pago" required>
                                        </div>
                                        <button class="wk-btn wk-btn-off" type="submit"><i class="fas fa-rotate-left"></i> Revertir pago</button>
                                    </form>
                                    <p class="wk-sub" style="margin-top:8px">Devuelve <?= trab_view_money($pagoLaboral['monto'] ?? 0) ?> a la Caja. Ref. <?= trab_view_safe($reversionPago['referencia_reversion'] ?? null) ?></p>
                                <?php elseif ($estadoPago === 'pagado'): ?>
                                    <p class="wk-sub" style="margin-top:10px">No se puede revertir: <?= trab_view_safe($reversionPago['motivo_bloqueo'] ?? null, 'No evaluada para este pago.') ?></p>
                                <?php elseif ($estadoPago === 'revertido' && $actualizadoPor !== ''): ?>
                                    <p class="wk-sub" style="margin-top:10px">Revertido por <?= trab_view_safe($actualizadoPor) ?>.</p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        </div>

        <div class="wk-tabpane" data-wk-pane="cuenta">
        <!-- Cuenta del trabajador (ledger) -->
        <section class="wk-panel">
            <div class="wk-panel-head">
                <div class="wk-sec-head">
                    <span class="wk-sec-icon is-blue"><i class="fas fa-scale-balanced"></i></span>
                    <div>
                        <h2 class="wk-panel-title">Cuenta del trabajador</h2>
                        <p class="wk-panel-sub">Lo que le sumas (bonos, comisiones) y lo que le restas (descuentos, anticipos, pr&eacute;stamos). Es informativo para llevar la cuenta: <strong>no saca dinero de Caja</strong>.</p>
                    </div>
                </div>
                <span class="wk-badge is-soft"><i class="fas fa-lock"></i> Sin pago real</span>
            </div>
            <div class="wk-panel-pad">

            <?php if ($puedeRegistrarConcepto): ?>
                <details class="wk-details" style="margin-bottom:14px">
                    <summary><span><i class="fas fa-plus" style="margin-right:8px"></i>Registrar un concepto (bono, comisi&oacute;n, descuento o ajuste)</span></summary>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/conceptos-laborales') ?>" class="wk-form-pad">
                        <?= csrf_field() ?>
                        <div class="grid grid-cols-1 lg:grid-cols-[160px_150px_150px_1fr] gap-3">
                            <div>
                                <label class="wk-label" for="concepto_tipo">Tipo</label>
                                <select id="concepto_tipo" class="wk-input" name="tipo" required>
                                    <option value="comision">Comisi&oacute;n</option>
                                    <option value="bono">Bono</option>
                                    <option value="descuento">Descuento</option>
                                    <option value="ajuste">Ajuste</option>
                                </select>
                            </div>
                            <div>
                                <label class="wk-label" for="concepto_efecto">Efecto</label>
                                <select id="concepto_efecto" class="wk-input" name="efecto">
                                    <option value="">Autom&aacute;tico</option>
                                    <option value="a_favor">A favor (suma)</option>
                                    <option value="en_contra">En contra (resta)</option>
                                </select>
                            </div>
                            <div>
                                <label class="wk-label" for="concepto_monto">Monto</label>
                                <input id="concepto_monto" class="wk-input" type="number" data-money-format="true" min="0.01" step="0.01" name="monto" required>
                            </div>
                            <div>
                                <label class="wk-label" for="concepto_texto">Concepto</label>
                                <input id="concepto_texto" class="wk-input" type="text" maxlength="160" name="concepto" required placeholder="Ej. bono por desempe&ntilde;o">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mt-3">
                            <div><label class="wk-label" for="concepto_fecha">Fecha</label><input id="concepto_fecha" class="wk-input" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                            <div><label class="wk-label" for="concepto_periodo_inicio">Periodo: desde</label><input id="concepto_periodo_inicio" class="wk-input" type="date" name="periodo_inicio"></div>
                            <div><label class="wk-label" for="concepto_periodo_fin">Periodo: hasta</label><input id="concepto_periodo_fin" class="wk-input" type="date" name="periodo_fin"></div>
                            <div><label class="wk-label" for="concepto_referencia">Referencia</label><input id="concepto_referencia" class="wk-input" type="text" maxlength="120" name="referencia" placeholder="Opcional"></div>
                        </div>
                        <div class="mt-3">
                            <label class="wk-label" for="concepto_notas">Notas</label>
                            <textarea id="concepto_notas" class="wk-input" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="wk-hint">Bono y comisi&oacute;n suman; descuento resta; el ajuste te deja elegir. No crea pagos reales.</div>
                            <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-plus"></i> Registrar concepto</button>
                        </div>
                    </form>
                </details>
            <?php elseif ($estadoTrabajador !== 'activo'): ?>
                <div class="wk-note" style="margin-bottom:14px">Solo se pueden registrar movimientos a trabajadores activos.</div>
            <?php endif; ?>

            <div class="wk-ledger-grid">
                <!-- Conceptos -->
                <article class="wk-ledger-card is-green">
                    <div class="wk-ledger-head">
                        <div><h3>Bonos y descuentos</h3><p>Lo que suma o resta a su cuenta.</p></div>
                        <span class="wk-badge is-soft"><?= (int)($resumenLedger['pagos_count'] ?? 0) ?></span>
                    </div>
                    <div class="wk-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_pagos'])): ?>
                            <div class="wk-empty-box"><i class="fas fa-triangle-exclamation"></i><strong>No disponible</strong><span>Esta secci&oacute;n no est&aacute; activada en esta instalaci&oacute;n.</span></div>
                        <?php elseif (empty($conceptosLaborales)): ?>
                            <div class="wk-empty-box"><i class="fas fa-file-circle-plus"></i><strong>Sin registros</strong><span>Registra bonos, comisiones o descuentos para que la cuenta empiece a sumar.</span></div>
                        <?php else: ?>
                            <?php foreach ($conceptosLaborales as $concepto): ?>
                                <?php $conceptoEsContra = ($concepto['efecto'] ?? '') === 'en_contra'; $conceptoDireccion = $conceptoEsContra ? 'minus' : 'plus'; ?>
                                <div class="wk-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="wk-strong"><?= trab_view_safe($concepto['concepto'] ?? null, 'Sin concepto') ?></div>
                                            <div class="wk-sub"><?= trab_view_date($concepto['fecha'] ?? null) ?> &middot; <?= trab_view_safe($concepto['tipo'] ?? null) ?></div>
                                        </div>
                                        <strong class="wk-ledger-amount is-<?= $conceptoDireccion ?>"><?= trab_view_signed_money($concepto['monto'] ?? 0, $conceptoDireccion) ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <!-- Anticipos -->
                <article class="wk-ledger-card is-amber">
                    <div class="wk-ledger-head">
                        <div><h3>Anticipos</h3><p>Adelantos de sueldo pendientes.</p></div>
                        <span class="wk-badge is-soft"><?= (int)($resumenLedger['anticipos_count'] ?? 0) ?></span>
                    </div>
                    <?php if ($puedeRegistrarAnticipo): ?>
                        <details class="wk-details" style="margin:0 16px 12px">
                            <summary><span><i class="fas fa-plus" style="margin-right:8px"></i>Registrar anticipo</span></summary>
                            <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/anticipos') ?>" class="wk-form-pad">
                                <?= csrf_field() ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div><label class="wk-label" for="anticipo_monto">Monto</label><input id="anticipo_monto" class="wk-input" type="number" data-money-format="true" min="0.01" step="0.01" name="monto" required></div>
                                    <div><label class="wk-label" for="anticipo_fecha">Fecha</label><input id="anticipo_fecha" class="wk-input" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="anticipo_motivo">Motivo</label><input id="anticipo_motivo" class="wk-input" type="text" maxlength="160" name="motivo" required placeholder="Ej. anticipo de sueldo"></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="anticipo_referencia">Referencia</label><input id="anticipo_referencia" class="wk-input" type="text" maxlength="120" name="referencia" placeholder="Opcional"></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="anticipo_notas">Notas</label><textarea id="anticipo_notas" class="wk-input" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea></div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <div class="wk-hint">El saldo pendiente inicia igual al monto. No crea pago ni Caja.</div>
                                    <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-plus"></i> Registrar anticipo</button>
                                </div>
                            </form>
                        </details>
                    <?php endif; ?>
                    <div class="wk-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_anticipos'])): ?>
                            <div class="wk-empty-box"><i class="fas fa-triangle-exclamation"></i><strong>No disponible</strong><span>Esta secci&oacute;n no est&aacute; activada en esta instalaci&oacute;n.</span></div>
                        <?php elseif (empty($anticiposRecientes)): ?>
                            <div class="wk-empty-box"><i class="fas fa-hand-holding-dollar"></i><strong>Sin anticipos</strong><span>Cuando registres uno, aqu&iacute; ver&aacute;s su monto y saldo pendiente.</span></div>
                        <?php else: ?>
                            <?php foreach ($anticiposRecientes as $anticipo): ?>
                                <div class="wk-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="wk-strong"><?= trab_view_safe($anticipo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="wk-sub"><?= trab_view_date($anticipo['fecha'] ?? null) ?> &middot; <?= trab_view_safe($anticipo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong class="wk-ledger-amount is-debt"><?= trab_view_money($anticipo['monto'] ?? 0) ?></strong>
                                    </div>
                                    <div class="wk-sub" style="margin-top:6px">Saldo pendiente: <?= trab_view_money($anticipo['saldo_pendiente'] ?? 0) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <!-- Prestamos -->
                <article class="wk-ledger-card is-danger">
                    <div class="wk-ledger-head">
                        <div><h3>Pr&eacute;stamos</h3><p>Deuda del trabajador (fuera de Caja).</p></div>
                        <span class="wk-badge is-soft"><?= (int)($resumenLedger['prestamos_count'] ?? 0) ?></span>
                    </div>
                    <?php if ($puedeRegistrarPrestamo): ?>
                        <details class="wk-details" style="margin:0 16px 12px">
                            <summary><span><i class="fas fa-plus" style="margin-right:8px"></i>Registrar pr&eacute;stamo</span></summary>
                            <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/prestamos') ?>" class="wk-form-pad">
                                <?= csrf_field() ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div><label class="wk-label" for="prestamo_monto">Monto</label><input id="prestamo_monto" class="wk-input" type="number" data-money-format="true" min="0.01" step="0.01" name="monto" required></div>
                                    <div><label class="wk-label" for="prestamo_fecha">Fecha</label><input id="prestamo_fecha" class="wk-input" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                                    <div><label class="wk-label" for="prestamo_plazo">Plazo (meses)</label><input id="prestamo_plazo" class="wk-input" type="number" min="1" step="1" name="plazo_meses" placeholder="Opcional"></div>
                                    <div><label class="wk-label" for="prestamo_abono">Abono sugerido</label><input id="prestamo_abono" class="wk-input" type="number" data-money-format="true" min="0" step="0.01" name="abono_periodico" placeholder="Opcional"></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="prestamo_motivo">Motivo</label><input id="prestamo_motivo" class="wk-input" type="text" maxlength="160" name="motivo" required placeholder="Ej. pr&eacute;stamo interno"></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="prestamo_referencia">Referencia</label><input id="prestamo_referencia" class="wk-input" type="text" maxlength="120" name="referencia" placeholder="Opcional"></div>
                                    <div class="md:col-span-2"><label class="wk-label" for="prestamo_notas">Notas</label><textarea id="prestamo_notas" class="wk-input" maxlength="1000" name="notas" placeholder="Opcional. No se registra en Caja."></textarea></div>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <div class="wk-hint">El saldo pendiente inicia igual al monto. No crea abonos ni Caja.</div>
                                    <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-plus"></i> Registrar pr&eacute;stamo</button>
                                </div>
                            </form>
                        </details>
                    <?php endif; ?>
                    <div class="wk-ledger-body">
                        <?php if (empty($ledgerDisponible['trabajador_prestamos'])): ?>
                            <div class="wk-empty-box"><i class="fas fa-triangle-exclamation"></i><strong>No disponible</strong><span>Esta secci&oacute;n no est&aacute; activada en esta instalaci&oacute;n.</span></div>
                        <?php elseif (empty($prestamosRecientes)): ?>
                            <div class="wk-empty-box"><i class="fas fa-file-invoice-dollar"></i><strong>Sin pr&eacute;stamos</strong><span>Cuando registres uno, aqu&iacute; ver&aacute;s su saldo y abono sugerido.</span></div>
                        <?php else: ?>
                            <?php foreach ($prestamosRecientes as $prestamo): ?>
                                <div class="wk-ledger-row">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="wk-strong"><?= trab_view_safe($prestamo['motivo'] ?? null, 'Sin motivo') ?></div>
                                            <div class="wk-sub"><?= trab_view_date($prestamo['fecha'] ?? null) ?> &middot; <?= trab_view_safe($prestamo['estado'] ?? null) ?></div>
                                        </div>
                                        <strong class="wk-ledger-amount is-debt"><?= trab_view_money($prestamo['monto'] ?? 0) ?></strong>
                                    </div>
                                    <div class="wk-sub" style="margin-top:6px">Saldo: <?= trab_view_money($prestamo['saldo_pendiente'] ?? 0) ?> &middot; Abono sugerido: <?= trab_view_money($prestamo['abono_periodico'] ?? 0) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
            </div>
        </section>
        </div>

        <div class="wk-tabpane" data-wk-pane="actividad">
        <!-- Tareas asignadas -->
        <section>
            <?php
            $tituloTareasContextuales = 'Tareas asignadas';
            $subtituloTareasContextuales = 'Tareas operativas de este trabajador. No representan asistencia ni pago.';
            include __DIR__ . '/../tareas/_contextual_list.php';
            ?>
        </section>

        <!-- Asistencias -->
        <section class="wk-panel">
            <div class="wk-panel-head">
                <div class="wk-sec-head">
                    <span class="wk-sec-icon is-amber"><i class="fas fa-calendar-check"></i></span>
                    <div>
                        <h2 class="wk-panel-title">Asistencias</h2>
                        <p class="wk-panel-sub">Registro diario de entradas, faltas, permisos y horas. Es de control: no genera n&oacute;mina ni pagos.</p>
                    </div>
                </div>
                <span class="wk-badge is-soft"><i class="fas fa-calendar-check"></i> Captura manual</span>
            </div>

            <?php if (empty($ledgerDisponible['trabajador_asistencias'])): ?>
                <div class="wk-panel-pad">
                    <div class="wk-empty-box"><i class="fas fa-calendar-xmark"></i><strong>No disponible</strong><span>Esta secci&oacute;n no est&aacute; activada en esta instalaci&oacute;n.</span></div>
                </div>
            <?php else: ?>
                <?php if ($puedeRegistrarAsistencia): ?>
                    <form method="POST" action="<?= url('trabajadores/' . $trabajadorId . '/asistencias') ?>" class="wk-panel-pad" style="border-bottom:1px solid var(--wk-border)">
                        <?= csrf_field() ?>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div><label class="wk-label" for="asistencia_fecha">Fecha</label><input id="asistencia_fecha" class="wk-input" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                            <div>
                                <label class="wk-label" for="asistencia_tipo">Tipo</label>
                                <select id="asistencia_tipo" class="wk-input" name="tipo" required>
                                    <option value="asistencia">Asistencia</option>
                                    <option value="retardo">Retardo</option>
                                    <option value="falta">Falta</option>
                                    <option value="permiso">Permiso</option>
                                    <option value="incapacidad">Incapacidad</option>
                                    <option value="descanso">Descanso</option>
                                    <option value="horas_extra">Horas extra</option>
                                </select>
                            </div>
                            <div><label class="wk-label" for="asistencia_hora_entrada">Entrada</label><input id="asistencia_hora_entrada" class="wk-input" type="time" name="hora_entrada"></div>
                            <div><label class="wk-label" for="asistencia_hora_salida">Salida</label><input id="asistencia_hora_salida" class="wk-input" type="time" name="hora_salida"></div>
                            <div><label class="wk-label" for="asistencia_horas">Horas</label><input id="asistencia_horas" class="wk-input" type="number" min="0" step="0.25" name="horas" placeholder="Opcional"></div>
                            <div><label class="wk-label" for="asistencia_horas_extra">Horas extra</label><input id="asistencia_horas_extra" class="wk-input" type="number" min="0" step="0.25" name="horas_extra" placeholder="Opcional"></div>
                            <div class="md:col-span-2"><label class="wk-label" for="asistencia_observaciones">Observaciones</label><input id="asistencia_observaciones" class="wk-input" type="text" maxlength="255" name="observaciones" placeholder="Opcional"></div>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                            <div class="wk-hint">Un registro por d&iacute;a. No genera n&oacute;mina, pagos reales ni movimientos de Caja.</div>
                            <button class="wk-btn wk-btn-gold" type="submit"><i class="fas fa-calendar-plus"></i> Registrar asistencia</button>
                        </div>
                    </form>
                <?php elseif ($estadoTrabajador !== 'activo'): ?>
                    <div class="wk-panel-pad"><div class="wk-note">Solo se pueden registrar asistencias a trabajadores activos.</div></div>
                <?php endif; ?>

                <?php if (empty($asistenciasRecientes)): ?>
                    <div class="wk-panel-pad">
                        <div class="wk-empty-box"><i class="fas fa-calendar-day"></i><strong>Sin asistencias todav&iacute;a</strong><span>Este trabajador a&uacute;n no tiene asistencias capturadas.</span></div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="wk-table">
                            <thead>
                                <tr><th>Fecha</th><th>Tipo</th><th>Entrada</th><th>Salida</th><th class="is-end">Horas</th><th class="is-end">Extra</th><th>Observaciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($asistenciasRecientes as $asistencia): ?>
                                    <tr>
                                        <td class="wk-strong"><?= trab_view_safe($asistencia['fecha'] ?? null) ?></td>
                                        <td><span class="wk-badge is-soft" style="text-transform:capitalize"><?= trab_view_safe(str_replace('_', ' ', (string)($asistencia['tipo'] ?? ''))) ?></span></td>
                                        <td><?= trab_view_safe($asistencia['hora_entrada'] ?? null) ?></td>
                                        <td><?= trab_view_safe($asistencia['hora_salida'] ?? null) ?></td>
                                        <td class="is-end"><?= trab_view_qty($asistencia['horas'] ?? 0) ?></td>
                                        <td class="is-end"><?= trab_view_qty($asistencia['horas_extra'] ?? 0) ?></td>
                                        <td class="wk-sub" style="margin:0"><?= trab_view_safe($asistencia['observaciones'] ?? null, 'Sin observaciones') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        </div>
    </div>
</div>

<script>
/* La confirmación de baja usa el modal global msConfirm (data-ms-confirm en el form). */
/* Pestañas de la ficha: solo presentación. Sin JS, todas las secciones quedan visibles. */
(function () {
    var page = document.querySelector('.worker-detail-page');
    if (!page) return;
    var tabs = page.querySelectorAll('.wk-tab[data-wk-tab]');
    var panes = page.querySelectorAll('.wk-tabpane[data-wk-pane]');
    if (!tabs.length || !panes.length) return;

    function activar(clave, actualizarHash) {
        var existe = false;
        panes.forEach(function (p) {
            var abierta = p.getAttribute('data-wk-pane') === clave;
            p.classList.toggle('is-open', abierta);
            if (abierta) existe = true;
        });
        if (!existe) return activar('resumen', false);
        tabs.forEach(function (t) {
            var activa = t.getAttribute('data-wk-tab') === clave;
            t.classList.toggle('is-active', activa);
            t.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        if (actualizarHash && history.replaceState) {
            history.replaceState(null, '', '#t=' + clave);
        }
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            activar(t.getAttribute('data-wk-tab'), true);
        });
    });

    /* CTA "Pagarle ahora" de la tarjeta de saldo: activa la pestaña y acerca la vista. */
    page.querySelectorAll('[data-wk-goto-tab]').forEach(function (el) {
        el.addEventListener('click', function (ev) {
            ev.preventDefault();
            activar(el.getAttribute('data-wk-goto-tab'), true);
            var bloque = page.querySelector('.wk-tabs-block');
            if (bloque && bloque.scrollIntoView) bloque.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    var hash = (location.hash || '').replace(/^#t=/, '');
    page.classList.add('wk-tabs-ready');
    activar(hash || 'resumen', false);
})();
</script>
