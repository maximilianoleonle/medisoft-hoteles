<?php
$cuenta = $cuenta ?? [];
$movimientos = $movimientos ?? [];
$movimientosDisponibles = $movimientosDisponibles ?? false;
$cobroCaja = $cobroCaja ?? [];
$cobroToken = $cobroToken ?? null;
$reversionesCobro = $reversionesCobro ?? [];
$reversionTokens = $reversionTokens ?? [];
$cxcFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$cxcOldInput = isset($_SESSION['old_input']) && is_array($_SESSION['old_input']) ? $_SESSION['old_input'] : [];
$movimientosCobro = array_values(array_filter($movimientos, static function ($movimiento) {
    return (string)($movimiento['tipo_movimiento'] ?? '') === 'COBRO';
}));

if (!function_exists('cxc_op_view_safe')) {
    function cxc_op_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxc_op_view_money')) {
    function cxc_op_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxc_op_view_estado_meta')) {
    function cxc_op_view_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'    => ['Parcial', 'is-parcial', 'fa-circle-half-stroke'],
            'vencida'    => ['Vencida', 'is-vencida', 'fa-triangle-exclamation'],
            'liquidada'  => ['Liquidada', 'is-liquidada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
            'incobrable' => ['Incobrable', 'is-incobrable', 'fa-ban'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

if (!function_exists('cxc_op_view_movimiento_meta')) {
    function cxc_op_view_movimiento_meta(array $movimiento): array
    {
        $tipo = strtoupper(trim((string)($movimiento['tipo_movimiento'] ?? '')));
        $referencia = strtoupper(trim((string)($movimiento['referencia'] ?? '')));
        $notas = strtolower(trim((string)($movimiento['notas'] ?? '')));

        if ($tipo === 'AJUSTE' && (strpos($referencia, 'RES-ABONO-') === 0 || strpos($notas, 'pago registrado en la reservacion') !== false)) {
            return ['Pago desde reservacion', 'is-liquidada', 'fa-calendar-check'];
        }

        $map = [
            'CREACION' => ['Creacion', 'is-soft', 'fa-circle-plus'],
            'AJUSTE' => ['Ajuste', 'is-parcial', 'fa-sliders'],
            'COBRO' => ['Cobro en CxC', 'is-liquidada', 'fa-cash-register'],
            'CANCELACION' => ['Reversion', 'is-vencida', 'fa-rotate-left'],
            'NOTA' => ['Nota', 'is-soft', 'fa-note-sticky'],
            'RECLASIFICACION' => ['Reclasificacion', 'is-soft', 'fa-arrow-right-arrow-left'],
        ];

        return $map[$tipo] ?? [ucfirst(strtolower($tipo !== '' ? $tipo : 'Movimiento')), 'is-soft', 'fa-circle-dot'];
    }
}

$cuentaId = (int)($cuenta['id'] ?? 0);
$cxcCobroMontoValor = (string)($cxcOldInput['monto'] ?? ($cobroCaja['monto_maximo'] ?? '0.00'));
$cxcCobroMetodoValor = (string)($cxcOldInput['metodo_pago'] ?? '');
$cxcCobroReferenciaValor = (string)($cxcOldInput['referencia'] ?? '');
$cxcCobroNotasValor = (string)($cxcOldInput['notas'] ?? '');
$cxcFacturaModoValor = (string)($cxcOldInput['factura_modo_cxc'] ?? 'acumular');
$cxcFacturaClienteVinculada = !empty($cuenta['solicitud_factura_id'])
    && (string)($cuenta['factura_requiere_factura'] ?? '') === 'si'
    && (string)($cuenta['factura_tipo'] ?? '') === 'cliente';
$cxcReversionOldMovimientoId = (int)($cxcOldInput['reversion_movimiento_id'] ?? 0);
[$estadoLabel, $estadoClass, $estadoIcon] = cxc_op_view_estado_meta($cuenta['estado'] ?? null);
$cxcSaldoActual = (float)($cuenta['saldo'] ?? 0);
$cxcCuentaLiquidada = strtolower((string)($cuenta['estado'] ?? '')) === 'liquidada' || $cxcSaldoActual <= 0.004;
$cxcMovimientoPagoReservacion = null;
foreach ($movimientos as $movimiento) {
    $tipoMovimiento = strtoupper(trim((string)($movimiento['tipo_movimiento'] ?? '')));
    $referenciaMovimiento = strtoupper(trim((string)($movimiento['referencia'] ?? '')));
    $notasMovimiento = strtolower(trim((string)($movimiento['notas'] ?? '')));
    if ($tipoMovimiento === 'AJUSTE' && (strpos($referenciaMovimiento, 'RES-ABONO-') === 0 || strpos($notasMovimiento, 'pago registrado en la reservacion') !== false)) {
        $cxcMovimientoPagoReservacion = $movimiento;
        break;
    }
}
$cxcLiquidadaPorReservacion = $cxcCuentaLiquidada && $cxcMovimientoPagoReservacion !== null;
$cxcSubtitle = $cxcCuentaLiquidada
    ? 'Cuenta saldada. Aqui queda la trazabilidad del saldo y de como se liquido.'
    : 'Lo que te debe este huesped. Aqui registras cobros en Caja cuando hay un corte abierto.';

if (!function_exists('cxc_op_form_error')) {
    function cxc_op_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        return cxc_op_view_safe($messages[0] ?? '', '');
    }
}

if (!function_exists('cxc_op_form_error_class')) {
    function cxc_op_form_error_class(array $errors, string $field): string
    {
        return cxc_op_form_error($errors, $field) !== '' ? ' cx-input-error' : '';
    }
}

if (!function_exists('cxc_op_form_error_attrs')) {
    function cxc_op_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (cxc_op_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . cxc_op_view_safe($errorId, '') . '"';
    }
}
?>

<style>
.cxc-op-detail {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 78%, var(--cx-brand));
    --cx-ivory: #F5F5F7; --cx-ivory-2: #FAFAFC;
    --cx-surface: #FFFFFF; --cx-surface-warm: #F5F5F7;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: color-mix(in srgb, var(--cx-brand) 34%, #647080);
    --cx-text-soft: color-mix(in srgb, var(--cx-brand) 24%, #7B8492);
    --cx-muted: #7A8493; --cx-heading: color-mix(in srgb, var(--cx-brand) 62%, #6B7280);
    --cx-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%; color: var(--cx-text); font-family: var(--cx-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cxc-op-detail .cx-shell { display: grid; gap: 14px; }
.cxc-op-detail .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxc-op-detail .cx-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent); }
.cxc-op-detail .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxc-op-detail .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.cxc-op-detail .cx-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--cx-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.cxc-op-detail .cx-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.cxc-op-detail .cx-stat { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxc-op-detail .cx-stat-label { color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.cxc-op-detail .cx-stat-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.55rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxc-op-detail .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.cxc-op-detail .cx-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--cx-border); background: var(--cx-surface); color: var(--cx-text); font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.cxc-op-detail .cx-btn:hover { transform: translateY(-1px); border-color: var(--cx-gold-line); color: var(--cx-gold-ink); }
.cxc-op-detail .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); border-color: transparent; color: #fff; }
.cxc-op-detail .cx-btn-brand:hover { color: #fff; border-color: transparent; }
.cxc-op-detail .cx-btn-danger { background: linear-gradient(135deg, var(--cx-danger), color-mix(in srgb, var(--cx-danger) 72%, var(--cx-brand))); border-color: transparent; color: #fff; }
.cxc-op-detail .cx-btn-danger:hover { color: #fff; border-color: transparent; }

.cxc-op-detail .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.cxc-op-detail .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, var(--cx-brand)); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxc-op-detail .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxc-op-detail .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-op-detail .cx-badge.is-liquidada { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-op-detail .cx-badge.is-incobrable { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-op-detail .cx-badge.is-cancelada, .cxc-op-detail .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxc-op-detail .cx-badge-ok { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-op-detail .cx-movement-pill { display: inline-flex; align-items: center; gap: 6px; min-height: 28px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; white-space: nowrap; }
.cxc-op-detail .cx-movement-pill.is-liquidada { color: color-mix(in srgb, var(--cx-success) 78%, var(--cx-brand)); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxc-op-detail .cx-movement-pill.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, var(--cx-brand)); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxc-op-detail .cx-movement-pill.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, var(--cx-brand)); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxc-op-detail .cx-movement-pill.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }

.cxc-op-detail .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxc-op-detail .cx-panel-title { font-family: var(--cx-serif); font-size: 1.4rem; font-weight: 700; color: var(--cx-heading); }
.cxc-op-detail .cx-panel-sub { color: var(--cx-muted); font-size: .85rem; margin-top: 3px; line-height: 1.45; }
.cxc-op-detail .cx-meta-label { font-size: .68rem; color: var(--cx-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.cxc-op-detail .cx-meta-value { margin-top: 3px; font-weight: 700; color: var(--cx-heading); }
.cxc-op-detail .cx-meta-value.is-soft { font-weight: 500; color: var(--cx-text-soft); }
.cxc-op-detail .cx-meta-value.is-notes { font-weight: 500; color: var(--cx-text-soft); white-space: pre-line; }

.cxc-op-detail label.cx-meta-label { display: block; }
.cxc-op-detail .cx-input, .cxc-op-detail textarea.cx-input, .cxc-op-detail select.cx-input {
    width: 100%; min-height: 42px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 10px 12px;
    color: var(--cx-text); font-weight: 600; font-size: .9rem; font-family: var(--cx-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.cxc-op-detail textarea.cx-input { min-height: 82px; resize: vertical; }
.cxc-op-detail .cx-input::placeholder { color: color-mix(in srgb, var(--cx-muted) 72%, #fff); font-weight: 600; }
.cxc-op-detail select.cx-input { cursor: pointer; }
.cxc-op-detail .cx-input:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; background: #fff; }
.cxc-op-detail .cx-input-error { border-color: #B42318; background: #FFF7F6; }
.cxc-op-detail .cx-form-error { display: block; margin-top: 7px; color: #B42318; font-size: .76rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.cxc-op-detail .cx-pay-card { border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 13px; padding: 14px; }
.cxc-op-detail form.cx-pay-card { max-width: 880px; }
.cxc-op-detail .cx-soft-note { border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 12px; padding: 14px; font-size: .88rem; color: var(--cx-muted); }
.cxc-op-detail .cx-resolution { display: grid; grid-template-columns: 38px minmax(0, 1fr); gap: 12px; align-items: start; padding: 14px 16px; border: 1px solid color-mix(in srgb, var(--cx-success) 22%, var(--cx-border)); border-radius: 14px; background: color-mix(in srgb, var(--cx-success-bg) 72%, #fff); }
.cxc-op-detail .cx-resolution-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 12px; color: color-mix(in srgb, var(--cx-success) 82%, var(--cx-brand)); background: rgba(255,255,255,.72); }
.cxc-op-detail .cx-resolution-title { margin: 0; color: var(--cx-heading); font-weight: 700; font-size: .96rem; line-height: 1.25; }
.cxc-op-detail .cx-resolution-text { margin: 3px 0 0; color: var(--cx-text); font-size: .86rem; line-height: 1.45; font-weight: 600; }
.cxc-op-detail .cx-resolution-ref { display: inline-flex; margin-top: 8px; color: color-mix(in srgb, var(--cx-brand) 48%, var(--cx-muted)); font-size: .76rem; font-weight: 700; }

.cxc-op-detail .cx-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--cx-border); }
.cxc-op-detail .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxc-op-detail .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxc-op-detail .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.cxc-op-detail .cx-table th.is-end, .cxc-op-detail .cx-table td.is-end { text-align: right; }
.cxc-op-detail .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: middle; }
.cxc-op-detail .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxc-op-detail .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxc-op-detail .cx-strong { font-weight: 700; color: var(--cx-heading); }
.cxc-op-detail .cx-empty { text-align: center; padding: 38px 18px; }
.cxc-op-detail .cx-empty h3 { color: var(--cx-heading); font-size: 1.05rem; font-weight: 700; }
.cxc-op-detail .cx-empty p { color: var(--cx-muted); font-size: .88rem; margin-top: 6px; }

@media (min-width: 768px) { .cxc-op-detail form.cx-pay-card { grid-template-columns: minmax(120px, .65fr) minmax(150px, .8fr) minmax(220px, 1.25fr); } .cxc-op-detail form.cx-pay-card > .md\:col-span-2 { grid-column: auto; } .cxc-op-detail form.cx-pay-card > .md\:col-span-4 { grid-column: 1 / -1; } }
@media (max-width: 720px) { .cxc-op-detail .cx-stats { grid-template-columns: 1fr; } .cxc-op-detail .cx-title { font-size: 1.9rem; } .cxc-op-detail form.cx-pay-card { max-width: none; } }
</style>

<div class="cxc-op-detail p-4 sm:p-6">
    <div class="cx-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                <div>
                    <p class="cx-kicker">Cobros a hu&eacute;spedes</p>
                    <h1 class="cx-title">Cuenta #<?= $cuentaId ?></h1>
                    <p class="cx-subtitle"><?= cxc_op_view_safe($cxcSubtitle) ?></p>
                </div>
            </div>
            <span class="cx-badge <?= $estadoClass ?>"><i class="fas <?= $estadoIcon ?>"></i> <?= $estadoLabel ?></span>
        </section>

        <section class="cx-stats">
            <div class="cx-stat"><p class="cx-stat-label">Total</p><p class="cx-stat-value"><?= cxc_op_view_money($cuenta['total'] ?? 0) ?></p></div>
            <div class="cx-stat"><p class="cx-stat-label">Saldo por cobrar</p><p class="cx-stat-value"><?= cxc_op_view_money($cuenta['saldo'] ?? 0) ?></p></div>
            <div class="cx-stat"><p class="cx-stat-label">Movimientos</p><p class="cx-stat-value"><?= count($movimientos) ?></p></div>
        </section>

        <section class="cx-toolbar">
            <?php $back_arrow_href = back_url('cuentas-por-cobrar/operativas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="cx-btn ms-back-legacy" href="<?= back_url('cuentas-por-cobrar/operativas') ?>"><i class="fas fa-arrow-left"></i> Volver</a>
            <a class="cx-btn" href="<?= url('cuentas-por-cobrar/simulador-caja') ?>"><i class="fas fa-cash-register"></i> Simulador de cobros</a>
            <?php if (!empty($cuenta['reservacion_id'])): ?>
                <a class="cx-btn" href="<?= url('reservaciones/ver/' . (int)$cuenta['reservacion_id']) ?>"><i class="fas fa-calendar-check"></i> Reservaci&oacute;n</a>
            <?php endif; ?>
            <?php if (!empty($cuenta['solicitud_factura_id'])): ?>
                <a class="cx-btn" href="<?= url('facturacion/ver/' . (int)$cuenta['solicitud_factura_id']) ?>"><i class="fas fa-file-invoice"></i> Factura</a>
            <?php endif; ?>
            <?php if (!empty($cuenta['huesped_id'])): ?>
                <a class="cx-btn" href="<?= url('huespedes/' . (int)$cuenta['huesped_id']) ?>"><i class="fas fa-user"></i> Hu&eacute;sped</a>
            <?php endif; ?>
        </section>

        <?php if ($cxcLiquidadaPorReservacion): ?>
            <section class="cx-resolution">
                <div class="cx-resolution-icon"><i class="fas fa-circle-check"></i></div>
                <div>
                    <p class="cx-resolution-title">Liquidada con pago registrado desde la reservaci&oacute;n</p>
                    <p class="cx-resolution-text">
                        El hu&eacute;sped ya pag&oacute; este saldo en la reservaci&oacute;n #<?= (int)($cuenta['reservacion_id'] ?? 0) ?>.
                        Esta cuenta conserva la trazabilidad; no gener&oacute; otro ingreso de Caja desde CxC.
                    </p>
                    <span class="cx-resolution-ref">Referencia: <?= cxc_op_view_safe($cxcMovimientoPagoReservacion['referencia'] ?? null, 'Sin referencia') ?></span>
                </div>
            </section>
        <?php endif; ?>

        <div class="cx-panel p-5">
            <h2 class="cx-panel-title mb-4">Datos de la cuenta</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div><div class="cx-meta-label">Folio</div><div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div></div>
                <div><div class="cx-meta-label">Origen</div><div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['origen_tipo'] ?? null) ?><?php if (!empty($cuenta['origen_id'])): ?> #<?= (int)$cuenta['origen_id'] ?><?php endif; ?></div></div>
                <div><div class="cx-meta-label">Emisi&oacute;n</div><div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['fecha_emision'] ?? null) ?></div></div>
                <div><div class="cx-meta-label">Vencimiento</div><div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div></div>
                <div>
                    <div class="cx-meta-label">Hu&eacute;sped</div>
                    <div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['huesped_nombre'] ?? null, 'Sin huésped') ?></div>
                    <div class="cx-meta-value is-soft" style="font-size:.74rem;margin-top:2px"><?= cxc_op_view_safe($cuenta['huesped_telefono'] ?? null, 'Sin teléfono') ?></div>
                </div>
                <div><div class="cx-meta-label">Reservaci&oacute;n</div><div class="cx-meta-value"><?php if (!empty($cuenta['reservacion_id'])): ?>#<?= (int)$cuenta['reservacion_id'] ?> <?= cxc_op_view_safe($cuenta['reservacion_estado'] ?? null, '') ?><?php else: ?>Sin reservaci&oacute;n<?php endif; ?></div></div>
                <div><div class="cx-meta-label">Factura</div><div class="cx-meta-value"><?php if (!empty($cuenta['solicitud_factura_id'])): ?>#<?= (int)$cuenta['solicitud_factura_id'] ?> <?= cxc_op_view_safe($cuenta['numero_factura'] ?? null, '') ?><?php else: ?>Sin factura<?php endif; ?></div></div>
                <div><div class="cx-meta-label">Moneda</div><div class="cx-meta-value"><?= cxc_op_view_safe($cuenta['moneda'] ?? null) ?></div></div>
                <div class="md:col-span-4"><div class="cx-meta-label">Concepto</div><div class="cx-meta-value is-soft"><?= cxc_op_view_safe($cuenta['concepto'] ?? null, 'Sin concepto') ?></div></div>
                <div class="md:col-span-4"><div class="cx-meta-label">Notas</div><div class="cx-meta-value is-notes"><?= cxc_op_view_safe($cuenta['notas'] ?? null, 'Sin notas') ?></div></div>
            </div>
        </div>

        <div class="cx-panel p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h2 class="cx-panel-title"><?= $cxcCuentaLiquidada ? 'Cobro en Caja' : 'Registrar cobro (en Caja)' ?></h2>
                    <p class="cx-panel-sub">
                        <?= $cxcCuentaLiquidada
                            ? 'Esta cuenta ya no tiene saldo por cobrar. No registres otro cobro para esta misma deuda.'
                            : 'Al registrar el cobro se crea un ingreso en Caja y se descuenta del saldo de esta cuenta.' ?>
                    </p>
                </div>
                <?php if (!empty($cobroCaja['corte'])): ?>
                    <span class="cx-badge cx-badge-ok"><i class="fas fa-cash-register"></i> Corte #<?= (int)$cobroCaja['corte']['id'] ?> abierto</span>
                <?php endif; ?>
            </div>

            <?php if (!empty($cobroCaja['elegible']) && $cobroToken): ?>
                <form method="POST" action="<?= url('cuentas-por-cobrar/operativas/' . $cuentaId . '/registrar-cobro-caja') ?>" class="cx-pay-card mt-5 grid grid-cols-1 md:grid-cols-4 gap-4"
                      onsubmit="var m = this.querySelector('[name=monto]'); return confirm('Vas a registrar un cobro de $' + ((m && m.value) ? m.value : '0') + ' que ENTRA a la Caja abierta. ¿Confirmar?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cobro_token" value="<?= cxc_op_view_safe($cobroToken, '') ?>">
                    <div>
                        <label class="cx-meta-label" for="cxc_monto">Monto</label>
                        <input id="cxc_monto" class="cx-input mt-1<?= cxc_op_form_error_class($cxcFieldErrors, 'monto') ?>" type="number" data-money-format="true" name="monto" min="0.01" step="0.01" max="<?= cxc_op_view_safe($cobroCaja['monto_maximo'] ?? '0.00', '0.00') ?>" value="<?= cxc_op_view_safe($cxcCobroMontoValor, '0.00') ?>" required<?= cxc_op_form_error_attrs($cxcFieldErrors, 'monto', 'ms-form-error-cxc_monto') ?>>
                        <?php if (cxc_op_form_error($cxcFieldErrors, 'monto') !== ''): ?>
                            <span id="ms-form-error-cxc_monto" class="cx-form-error ms-form-field-error"><?= cxc_op_form_error($cxcFieldErrors, 'monto') ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="cx-meta-label" for="cxc_metodo_pago">M&eacute;todo de pago</label>
                        <select id="cxc_metodo_pago" class="cx-input mt-1<?= cxc_op_form_error_class($cxcFieldErrors, 'metodo_pago') ?>" name="metodo_pago" required<?= cxc_op_form_error_attrs($cxcFieldErrors, 'metodo_pago', 'ms-form-error-cxc_metodo_pago') ?>>
                            <?php foreach (($cobroCaja['metodos_pago'] ?? []) as $valor => $label): ?>
                                <?php $valorMetodo = (string)$valor; ?>
                                <option value="<?= cxc_op_view_safe($valorMetodo, '') ?>" <?= $cxcCobroMetodoValor !== '' && $cxcCobroMetodoValor === $valorMetodo ? 'selected' : '' ?>><?= cxc_op_view_safe($label, '') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (cxc_op_form_error($cxcFieldErrors, 'metodo_pago') !== ''): ?>
                            <span id="ms-form-error-cxc_metodo_pago" class="cx-form-error ms-form-field-error"><?= cxc_op_form_error($cxcFieldErrors, 'metodo_pago') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="md:col-span-2">
                        <label class="cx-meta-label" for="cxc_referencia">Referencia</label>
                        <input id="cxc_referencia" class="cx-input mt-1<?= cxc_op_form_error_class($cxcFieldErrors, 'referencia') ?>" type="text" name="referencia" maxlength="100" value="<?= cxc_op_view_safe($cxcCobroReferenciaValor, '') ?>" placeholder="Folio, transferencia o nota breve"<?= cxc_op_form_error_attrs($cxcFieldErrors, 'referencia', 'ms-form-error-cxc_referencia') ?>>
                        <?php if (cxc_op_form_error($cxcFieldErrors, 'referencia') !== ''): ?>
                            <span id="ms-form-error-cxc_referencia" class="cx-form-error ms-form-field-error"><?= cxc_op_form_error($cxcFieldErrors, 'referencia') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($cxcFacturaClienteVinculada): ?>
                        <div class="md:col-span-4" style="padding:12px 14px;border:1px solid #BFDBFE;border-radius:14px;background:#EFF6FF;">
                            <div class="cx-meta-label" style="color:#1E40AF;margin-bottom:8px;">Factura vinculada #<?= (int)($cuenta['solicitud_factura_id'] ?? 0) ?></div>
                            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                                <label style="display:flex;align-items:center;gap:7px;font-size:.86rem;font-weight:750;color:#1E3A8A;cursor:pointer;">
                                    <input type="radio" name="factura_modo_cxc" value="acumular" <?= $cxcFacturaModoValor !== 'separada' ? 'checked' : '' ?>>
                                    Sumar a factura pendiente
                                </label>
                                <label style="display:flex;align-items:center;gap:7px;font-size:.86rem;font-weight:750;color:#1E3A8A;cursor:pointer;">
                                    <input type="radio" name="factura_modo_cxc" value="separada" <?= $cxcFacturaModoValor === 'separada' ? 'checked' : '' ?>>
                                    Crear factura separada
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="md:col-span-4">
                        <label class="cx-meta-label" for="cxc_notas">Notas</label>
                        <textarea id="cxc_notas" class="cx-input mt-1<?= cxc_op_form_error_class($cxcFieldErrors, 'notas') ?>" name="notas" maxlength="1000" placeholder="Opcional"<?= cxc_op_form_error_attrs($cxcFieldErrors, 'notas', 'ms-form-error-cxc_notas') ?>><?= cxc_op_view_safe($cxcCobroNotasValor, '') ?></textarea>
                        <?php if (cxc_op_form_error($cxcFieldErrors, 'notas') !== ''): ?>
                            <span id="ms-form-error-cxc_notas" class="cx-form-error ms-form-field-error"><?= cxc_op_form_error($cxcFieldErrors, 'notas') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="md:col-span-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="cx-panel-sub" style="margin:0">M&aacute;ximo a cobrar: <strong style="color:var(--cx-heading)"><?= cxc_op_view_money($cobroCaja['monto_maximo'] ?? 0) ?></strong>.</p>
                        <button class="cx-btn cx-btn-brand" type="submit"><i class="fas fa-cash-register"></i> Registrar cobro</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="cx-soft-note mt-5">
                    <strong style="color:var(--cx-heading)">Por ahora no se puede cobrar.</strong>
                    <?= cxc_op_view_safe($cobroCaja['motivo_bloqueo'] ?? 'Esta cuenta no se puede cobrar con Caja en este momento.') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cx-panel p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h2 class="cx-panel-title">Revertir cobros registrados</h2>
                    <p class="cx-panel-sub">Úsalo cuando un cobro se registró con método, huésped o cuenta incorrecta. La reversión crea una salida en Caja, vuelve a abrir el saldo de esta cuenta y conserva el historial. Si después registras otro cobro, ese nuevo cobro también podrá revertirse.</p>
                </div>
            </div>

            <?php if (empty($movimientosCobro)): ?>
                <div class="cx-soft-note mt-5">
                    <?= $cxcLiquidadaPorReservacion
                        ? 'No hay cobros CxC que revertir. El saldo se cubrió desde la reservación; revisa el pago desde el botón Reservación.'
                        : 'Esta cuenta todavía no tiene cobros que se puedan revertir.' ?>
                </div>
            <?php else: ?>
                <div class="mt-5 space-y-4">
                    <?php foreach ($movimientosCobro as $movimientoCobro): ?>
                        <?php
                            $movimientoCobroId = (int)($movimientoCobro['id'] ?? 0);
                            $reversion = $reversionesCobro[$movimientoCobroId] ?? ['elegible' => false, 'motivo_bloqueo' => 'No evaluado.', 'monto' => $movimientoCobro['monto'] ?? '0.00'];
                            $reversionToken = $reversionTokens[$movimientoCobroId] ?? null;
                        ?>
                        <div class="cx-pay-card">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="cx-meta-label">Cobro #<?= $movimientoCobroId ?></div>
                                    <div class="cx-meta-value"><?= cxc_op_view_money($movimientoCobro['monto'] ?? 0) ?> <span class="cx-panel-sub" style="font-weight:500"><?= cxc_op_view_safe($movimientoCobro['referencia'] ?? null, 'Sin referencia') ?></span></div>
                                </div>
                                <?php if (!empty($reversion['corte'])): ?>
                                    <span class="cx-badge cx-badge-ok"><i class="fas fa-cash-register"></i> Corte #<?= (int)$reversion['corte']['id'] ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($reversion['elegible']) && $reversionToken): ?>
                                <p class="cx-panel-sub mt-3" style="margin-bottom:0">Este cobro puede revertirse. La accion afecta solo al cobro #<?= $movimientoCobroId ?>; otros cobros de esta cuenta se manejan por separado.</p>
                                <?php
                                    $cxcMotivoError = $cxcReversionOldMovimientoId === $movimientoCobroId
                                        ? cxc_op_form_error($cxcFieldErrors, 'motivo')
                                        : '';
                                    $cxcMotivoValor = $cxcReversionOldMovimientoId === $movimientoCobroId
                                        ? (string)($cxcOldInput['motivo'] ?? '')
                                        : '';
                                ?>
                                <form method="POST" action="<?= url('cuentas-por-cobrar/operativas/' . $cuentaId . '/movimientos/' . $movimientoCobroId . '/revertir-cobro-caja') ?>" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4 items-end"
                                      onsubmit="return confirm('Vas a revertir el cobro #<?= $movimientoCobroId ?> de $<?= number_format((float)($reversion['monto'] ?? $movimientoCobro['monto'] ?? 0), 2) ?>: el dinero SALDRÁ de Caja y la cuenta volverá a quedar pendiente. ¿Confirmar?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reversion_token" value="<?= cxc_op_view_safe($reversionToken, '') ?>">
                                    <div>
                                        <div class="cx-meta-label">Monto a revertir</div>
                                        <div class="cx-meta-value" style="margin-top:8px"><?= cxc_op_view_money($reversion['monto'] ?? $movimientoCobro['monto'] ?? 0) ?></div>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="cx-meta-label" for="cxc_reversion_motivo_<?= $movimientoCobroId ?>">Motivo de la reversi&oacute;n</label>
                                        <textarea id="cxc_reversion_motivo_<?= $movimientoCobroId ?>" class="cx-input mt-1<?= $cxcMotivoError !== '' ? ' cx-input-error' : '' ?>" name="motivo" maxlength="1000" required placeholder="Ej. metodo incorrecto, huesped equivocado o cuenta equivocada"<?= $cxcMotivoError !== '' ? ' aria-invalid="true" aria-describedby="ms-form-error-cxc_reversion_motivo_' . $movimientoCobroId . '"' : '' ?>><?= cxc_op_view_safe($cxcMotivoValor, '') ?></textarea>
                                        <?php if ($cxcMotivoError !== ''): ?>
                                            <span id="ms-form-error-cxc_reversion_motivo_<?= $movimientoCobroId ?>" class="cx-form-error ms-form-field-error"><?= $cxcMotivoError ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="md:col-span-4 flex justify-end">
                                        <button class="cx-btn cx-btn-danger" type="submit"><i class="fas fa-rotate-left"></i> Revertir este cobro</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="mt-3 cx-panel-sub"><?= cxc_op_view_safe($reversion['motivo_bloqueo'] ?? 'Este cobro no se puede revertir.') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cx-panel overflow-hidden">
            <div class="cx-panel-head">
                <h2 class="cx-panel-title">Historial de movimientos</h2>
                <span class="cx-badge is-soft"><i class="fas fa-list-check"></i> Trazabilidad</span>
            </div>
            <?php if (!$movimientosDisponibles): ?>
                <div class="cx-empty"><h3>El historial todav&iacute;a no est&aacute; disponible</h3><p>Cuando registres cobros, los movimientos aparecer&aacute;n aqu&iacute;.</p></div>
            <?php elseif (empty($movimientos)): ?>
                <div class="cx-empty"><h3>Sin movimientos todav&iacute;a</h3><p>Esta cuenta a&uacute;n no tiene cobros ni ajustes.</p></div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="cx-table">
                        <thead>
                            <tr><th>Fecha</th><th>Tipo</th><th class="is-end">Monto</th><th class="is-end">Saldo anterior</th><th class="is-end">Saldo posterior</th><th>Referencia</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                                <?php [$movimientoLabel, $movimientoClass, $movimientoIcon] = cxc_op_view_movimiento_meta($movimiento); ?>
                                <tr>
                                    <td><?= cxc_op_view_safe($movimiento['created_at'] ?? null) ?></td>
                                    <td><span class="cx-movement-pill <?= cxc_op_view_safe($movimientoClass, 'is-soft') ?>"><i class="fas <?= cxc_op_view_safe($movimientoIcon, 'fa-circle-dot') ?>"></i><?= cxc_op_view_safe($movimientoLabel) ?></span></td>
                                    <td class="is-end"><?= cxc_op_view_money($movimiento['monto'] ?? 0) ?></td>
                                    <td class="is-end"><?= cxc_op_view_money($movimiento['saldo_anterior'] ?? 0) ?></td>
                                    <td class="is-end cx-strong"><?= cxc_op_view_money($movimiento['saldo_posterior'] ?? 0) ?></td>
                                    <td><?= cxc_op_view_safe($movimiento['referencia'] ?? null, 'Sin referencia') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
