<?php
$cuenta = $cuenta ?? [];
$movimientos = $movimientos ?? [];
$movimientosDisponibles = $movimientosDisponibles ?? false;
$pagoCaja = $pagoCaja ?? [];
$pagoToken = $pagoToken ?? null;
$reversionesPago = $reversionesPago ?? [];
$reversionTokens = $reversionTokens ?? [];
$movimientosPago = $movimientosPago ?? [];
$cxpFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$cxpOldInput = isset($_SESSION['old_input']) && is_array($_SESSION['old_input']) ? $_SESSION['old_input'] : [];

if (!function_exists('cxp_view_safe')) {
    function cxp_view_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cxp_view_money')) {
    function cxp_view_money($value)
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cxp_view_estado_meta')) {
    function cxp_view_estado_meta($estado)
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente' => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'parcial'   => ['Parcial',   'is-parcial',   'fa-circle-half-stroke'],
            'vencida'   => ['Vencida',   'is-vencida',   'fa-triangle-exclamation'],
            'pagada'    => ['Pagada',    'is-pagada',    'fa-circle-check'],
            'cancelada' => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

[$estadoLabel, $estadoClass, $estadoIcon] = cxp_view_estado_meta($cuenta['estado'] ?? null);
$cxpPagoMontoValor = (string)($cxpOldInput['monto'] ?? ($pagoCaja['monto_maximo'] ?? ($cuenta['saldo'] ?? '0.00')));
$cxpPagoMetodoValor = (string)($cxpOldInput['metodo_pago'] ?? '');
$cxpPagoReferenciaValor = (string)($cxpOldInput['referencia'] ?? '');
$cxpPagoNotasValor = (string)($cxpOldInput['notas'] ?? '');
$cxpReversionOldMovimientoId = (int)($cxpOldInput['reversion_movimiento_id'] ?? 0);

if (!function_exists('cxp_form_error')) {
    function cxp_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        return cxp_view_safe($messages[0] ?? '', '');
    }
}

if (!function_exists('cxp_form_error_class')) {
    function cxp_form_error_class(array $errors, string $field): string
    {
        return cxp_form_error($errors, $field) !== '' ? ' cx-input-error' : '';
    }
}

if (!function_exists('cxp_form_error_attrs')) {
    function cxp_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (cxp_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . cxp_view_safe($errorId, '') . '"';
    }
}
?>

<style>
.cxp-detail-page {
    --cx-brand: var(--brand-primary, #1B2746);
    --cx-brand-2: var(--brand-secondary, #0F172A);
    --cx-gold: var(--brand-accent, #BD9441);
    --cx-gold-soft: color-mix(in srgb, var(--cx-gold) 15%, #FFFFFF);
    --cx-gold-line: color-mix(in srgb, var(--cx-gold) 42%, #E4D4B0);
    --cx-gold-ink: color-mix(in srgb, var(--cx-gold) 72%, #000);
    --cx-ivory: #F6F2EA;
    --cx-ivory-2: #FBF8F2;
    --cx-surface: #FFFFFF;
    --cx-surface-warm: #FCFAF5;
    --cx-border: color-mix(in srgb, var(--cx-brand) 7%, #E7E1D4);
    --cx-ring: color-mix(in srgb, var(--cx-gold) 32%, transparent);
    --cx-text: #171717;
    --cx-muted: #667085;
    --cx-heading: #111827;
    --cx-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cx-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cx-success: #1E9E63; --cx-success-bg: #E7F4EC;
    --cx-warning: #C2841C; --cx-warning-bg: #FAF0DC;
    --cx-danger: #B4392B; --cx-danger-bg: #F8EAE5;
    --cx-info: #2F77E0; --cx-info-bg: #E6EFFC;
    min-height: 100%;
    color: var(--cx-text);
    font-family: var(--cx-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cx-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cx-ivory-2), var(--cx-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.cxp-detail-page .cx-shell { display: grid; gap: 14px; }
.cxp-detail-page .cx-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.cxp-detail-page .cx-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cx-gold), var(--cx-brand) 54%, color-mix(in srgb, var(--cx-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cx-brand) 72%, transparent);
}
.cxp-detail-page .cx-kicker { margin: 0 0 2px; color: var(--cx-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cxp-detail-page .cx-title { margin: 0; font-family: var(--cx-serif); color: var(--cx-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.cxp-detail-page .cx-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--cx-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.cxp-detail-page .cx-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.cxp-detail-page .cx-stat { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.cxp-detail-page .cx-stat-label { color: var(--cx-muted); font-size: .68rem; font-weight: 700; letter-spacing: .045em; text-transform: uppercase; }
.cxp-detail-page .cx-stat-value { margin-top: 2px; font-family: var(--cx-serif); font-size: 1.55rem; font-weight: 700; line-height: 1.1; color: var(--cx-heading); }

.cxp-detail-page .cx-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
.cxp-detail-page .cx-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid var(--cx-border); background: var(--cx-surface); color: var(--cx-text); font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease;
}
.cxp-detail-page .cx-btn:hover { transform: translateY(-1px); border-color: var(--cx-gold-line); color: var(--cx-gold-ink); }
.cxp-detail-page .cx-btn:focus-visible { outline: 3px solid var(--cx-ring); outline-offset: 2px; }
.cxp-detail-page .cx-btn-brand { background: linear-gradient(135deg, var(--cx-brand), var(--cx-brand-2)); border-color: transparent; color: #fff; }
.cxp-detail-page .cx-btn-brand:hover { color: #fff; border-color: transparent; }
.cxp-detail-page .cx-btn-danger { background: linear-gradient(135deg, var(--cx-danger), color-mix(in srgb, var(--cx-danger) 72%, #000)); border-color: transparent; color: #fff; }
.cxp-detail-page .cx-btn-danger:hover { color: #fff; border-color: transparent; }

.cxp-detail-page .cx-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.cxp-detail-page .cx-badge.is-pendiente { color: color-mix(in srgb, var(--cx-info) 80%, #000); background: var(--cx-info-bg); border-color: color-mix(in srgb, var(--cx-info) 26%, #fff); }
.cxp-detail-page .cx-badge.is-parcial { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }
.cxp-detail-page .cx-badge.is-vencida { color: color-mix(in srgb, var(--cx-danger) 82%, #000); background: var(--cx-danger-bg); border-color: color-mix(in srgb, var(--cx-danger) 26%, #fff); }
.cxp-detail-page .cx-badge.is-pagada { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-detail-page .cx-badge.is-cancelada, .cxp-detail-page .cx-badge.is-soft { color: var(--cx-muted); background: var(--cx-surface-warm); border-color: var(--cx-border); }
.cxp-detail-page .cx-badge-ok { color: color-mix(in srgb, var(--cx-success) 78%, #000); background: var(--cx-success-bg); border-color: color-mix(in srgb, var(--cx-success) 26%, #fff); }
.cxp-detail-page .cx-badge-blocked { color: color-mix(in srgb, var(--cx-warning) 82%, #000); background: var(--cx-warning-bg); border-color: color-mix(in srgb, var(--cx-warning) 28%, #fff); }

.cxp-detail-page .cx-panel { background: var(--cx-surface); border: 1px solid var(--cx-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.cxp-detail-page .cx-panel-title { font-family: var(--cx-serif); font-size: 1.4rem; font-weight: 700; color: var(--cx-heading); }
.cxp-detail-page .cx-panel-sub { color: var(--cx-muted); font-size: .85rem; margin-top: 3px; line-height: 1.45; }
.cxp-detail-page .cx-meta-label { font-size: .68rem; color: var(--cx-muted); font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.cxp-detail-page .cx-meta-value { margin-top: 3px; font-weight: 700; color: var(--cx-heading); }
.cxp-detail-page .cx-meta-value.is-soft { font-weight: 500; color: #334155; }
.cxp-detail-page .cx-meta-value.is-notes { font-weight: 500; color: #334155; white-space: pre-line; }

.cxp-detail-page label.cx-meta-label { display: block; }
.cxp-detail-page .cx-input, .cxp-detail-page textarea.cx-input, .cxp-detail-page select.cx-input {
    width: 100%; min-height: 42px; border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 11px; padding: 10px 12px;
    color: var(--cx-text); font-weight: 600; font-size: .9rem; font-family: var(--cx-sans); transition: border-color .16s ease, box-shadow .16s ease;
}
.cxp-detail-page textarea.cx-input { min-height: 84px; resize: vertical; }
.cxp-detail-page select.cx-input { cursor: pointer; }
.cxp-detail-page .cx-input:focus { border-color: var(--cx-gold); box-shadow: 0 0 0 3px var(--cx-ring); outline: none; background: #fff; }
.cxp-detail-page .cx-input-error { border-color: #B42318; background: #FFF7F6; }
.cxp-detail-page .cx-form-error { display: block; margin-top: 7px; color: #B42318; font-size: .76rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }

.cxp-detail-page .cx-pay-card { border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 13px; padding: 14px; }
.cxp-detail-page .cx-soft-note { border: 1px solid var(--cx-border); background: var(--cx-surface-warm); border-radius: 12px; padding: 14px; font-size: .88rem; color: var(--cx-muted); }

.cxp-detail-page .cx-panel-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 18px; border-bottom: 1px solid var(--cx-border); }
.cxp-detail-page .cx-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.cxp-detail-page .cx-table thead { background: var(--cx-surface-warm); border-bottom: 1px solid var(--cx-border); }
.cxp-detail-page .cx-table th { padding: 12px 14px; color: var(--cx-muted); font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; text-align: left; }
.cxp-detail-page .cx-table th.is-end, .cxp-detail-page .cx-table td.is-end { text-align: right; }
.cxp-detail-page .cx-table td { padding: 13px 14px; border-bottom: 1px solid var(--cx-border); vertical-align: middle; }
.cxp-detail-page .cx-table tbody tr:last-child td { border-bottom: 0; }
.cxp-detail-page .cx-table tbody tr:hover { background: var(--cx-ivory-2); }
.cxp-detail-page .cx-strong { font-weight: 700; color: var(--cx-heading); }
.cxp-detail-page .cx-empty { text-align: center; padding: 38px 18px; }
.cxp-detail-page .cx-empty h3 { color: var(--cx-brand); font-size: 1.05rem; font-weight: 700; }
.cxp-detail-page .cx-empty p { color: var(--cx-muted); font-size: .88rem; margin-top: 6px; }

@media (max-width: 720px) { .cxp-detail-page .cx-stats { grid-template-columns: 1fr; } .cxp-detail-page .cx-title { font-size: 1.9rem; } }
</style>

<div class="cxp-detail-page p-4 sm:p-6">
    <div class="cx-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="cx-title-lockup">
                <div class="cx-hero-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div>
                    <p class="cx-kicker">Pagos a proveedores</p>
                    <h1 class="cx-title">Cuenta #<?= (int)($cuenta['id'] ?? 0) ?></h1>
                    <p class="cx-subtitle">Lo que le debes a este proveedor. Aqu&iacute; registras pagos desde Caja cuando hay un corte abierto.</p>
                </div>
            </div>
            <span class="cx-badge <?= $estadoClass ?>">
                <i class="fas <?= $estadoIcon ?>"></i>
                <?= $estadoLabel ?>
            </span>
        </section>

        <section class="cx-stats">
            <div class="cx-stat"><p class="cx-stat-label">Total</p><p class="cx-stat-value"><?= cxp_view_money($cuenta['total'] ?? 0) ?></p></div>
            <div class="cx-stat"><p class="cx-stat-label">Saldo por pagar</p><p class="cx-stat-value"><?= cxp_view_money($cuenta['saldo'] ?? 0) ?></p></div>
            <div class="cx-stat"><p class="cx-stat-label">Movimientos</p><p class="cx-stat-value"><?= count($movimientos) ?></p></div>
        </section>

        <section class="cx-toolbar">
            <div class="flex flex-wrap gap-2">
                <a class="cx-btn" href="<?= back_url('cuentas-por-pagar') ?>">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <a class="cx-btn" href="<?= url('cuentas-por-pagar/simulador-caja') ?>">
                    <i class="fas fa-cash-register"></i>
                    Simulador de pagos
                </a>
                <?php if (!empty($cuenta['compra_id'])): ?>
                    <a class="cx-btn" href="<?= url('compras/' . (int)$cuenta['compra_id']) ?>">
                        <i class="fas fa-receipt"></i>
                        Ver compra
                    </a>
                <?php endif; ?>
                <?php if (!empty($cuenta['proveedor_id'])): ?>
                    <a class="cx-btn" href="<?= url('proveedores/' . (int)$cuenta['proveedor_id']) ?>">
                        <i class="fas fa-truck-field"></i>
                        Ver proveedor
                    </a>
                <?php endif; ?>
            </div>
        </section>

        <div class="cx-panel p-5">
            <h2 class="cx-panel-title mb-4">Datos de la cuenta</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <div class="cx-meta-label">Proveedor</div>
                    <div class="cx-meta-value"><?= cxp_view_safe($cuenta['proveedor_nombre'] ?? null) ?></div>
                    <div class="cx-sub" style="color:var(--cx-muted);font-size:.74rem;margin-top:2px"><?= cxp_view_safe($cuenta['proveedor_rfc'] ?? null, 'Sin RFC') ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Folio</div>
                    <div class="cx-meta-value"><?= cxp_view_safe($cuenta['folio'] ?? null, 'Sin folio') ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Emisi&oacute;n</div>
                    <div class="cx-meta-value"><?= cxp_view_safe($cuenta['fecha_emision'] ?? null) ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Vencimiento</div>
                    <div class="cx-meta-value"><?= cxp_view_safe($cuenta['fecha_vencimiento'] ?? null, 'Sin vencimiento') ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Compra vinculada</div>
                    <div class="cx-meta-value">
                        <?php if (!empty($cuenta['compra_id'])): ?>
                            #<?= (int)$cuenta['compra_id'] ?> <?= cxp_view_safe($cuenta['compra_folio'] ?? null, '') ?>
                        <?php else: ?>
                            Sin compra vinculada
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div class="cx-meta-label">Subtotal</div>
                    <div class="cx-meta-value"><?= cxp_view_money($cuenta['subtotal'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Impuestos</div>
                    <div class="cx-meta-value"><?= cxp_view_money($cuenta['impuestos'] ?? 0) ?></div>
                </div>
                <div>
                    <div class="cx-meta-label">Moneda</div>
                    <div class="cx-meta-value"><?= cxp_view_safe($cuenta['moneda'] ?? null) ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cx-meta-label">Descripci&oacute;n</div>
                    <div class="cx-meta-value is-soft"><?= cxp_view_safe($cuenta['descripcion'] ?? null, 'Sin descripci&oacute;n') ?></div>
                </div>
                <div class="md:col-span-4">
                    <div class="cx-meta-label">Notas</div>
                    <div class="cx-meta-value is-notes"><?= cxp_view_safe($cuenta['notas'] ?? null, 'Sin notas') ?></div>
                </div>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad ?? [],
            'documentosEntidadContexto' => $documentosEntidadContexto ?? [],
        ]); ?>

        <div class="cx-panel p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h2 class="cx-panel-title">Registrar pago (desde Caja)</h2>
                    <?php if (!empty($pagoCaja['elegible'])): ?>
                        <p class="cx-panel-sub">
                            <?= cxp_view_safe($pagoCaja['motivo_elegibilidad'] ?? null) ?>
                            Al registrar el pago se crea un movimiento de gasto en Caja y se descuenta del saldo de esta cuenta.
                        </p>
                    <?php else: ?>
                        <p class="cx-panel-sub">
                            <?= cxp_view_safe($pagoCaja['motivo_bloqueo'] ?? null, 'Esta cuenta no se puede pagar con Caja en este momento.') ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="flex flex-wrap gap-2 items-start">
                    <?php if (!empty($pagoCaja['corte'])): ?>
                        <span class="cx-badge cx-badge-ok">
                            <i class="fas fa-cash-register"></i>
                            Corte #<?= (int)$pagoCaja['corte']['id'] ?> abierto
                        </span>
                    <?php else: ?>
                        <span class="cx-badge cx-badge-blocked">
                            <i class="fas fa-ban"></i>
                            Sin corte abierto
                        </span>
                    <?php endif; ?>
                    <a class="cx-btn" href="<?= url('cuentas-por-pagar/simulador-caja') ?>">
                        <i class="fas fa-magnifying-glass"></i>
                        Ver simulador
                    </a>
                </div>
            </div>

            <?php if (!empty($pagoCaja['elegible']) && !empty($pagoToken)): ?>
                <form method="POST" action="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0) . '/registrar-pago-caja') ?>" class="cx-pay-card mt-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="pago_token" value="<?= cxp_view_safe($pagoToken, '') ?>">

                    <div>
                        <label class="cx-meta-label" for="cxp_pago_monto">Monto</label>
                        <input
                            id="cxp_pago_monto"
                            class="cx-input mt-1<?= cxp_form_error_class($cxpFieldErrors, 'monto') ?>"
                            type="number"
                            data-money-format="true"
                            name="monto"
                            min="0.01"
                            max="<?= cxp_view_safe($pagoCaja['monto_maximo'] ?? ($cuenta['saldo'] ?? 0), '0.00') ?>"
                            step="0.01"
                            value="<?= cxp_view_safe($cxpPagoMontoValor, '0.00') ?>"
                            required
                            <?= cxp_form_error_attrs($cxpFieldErrors, 'monto', 'ms-form-error-cxp_pago_monto') ?>
                        >
                        <?php if (cxp_form_error($cxpFieldErrors, 'monto') !== ''): ?>
                            <span id="ms-form-error-cxp_pago_monto" class="cx-form-error ms-form-field-error"><?= cxp_form_error($cxpFieldErrors, 'monto') ?></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <label class="cx-meta-label" for="cxp_pago_metodo">M&eacute;todo de pago</label>
                        <select id="cxp_pago_metodo" class="cx-input mt-1<?= cxp_form_error_class($cxpFieldErrors, 'metodo_pago') ?>" name="metodo_pago" required<?= cxp_form_error_attrs($cxpFieldErrors, 'metodo_pago', 'ms-form-error-cxp_pago_metodo') ?>>
                            <?php foreach (($pagoCaja['metodos_pago'] ?? []) as $metodo => $label): ?>
                                <?php $metodoValor = (string)$metodo; ?>
                                <option value="<?= cxp_view_safe($metodoValor, '') ?>" <?= $cxpPagoMetodoValor !== '' && $cxpPagoMetodoValor === $metodoValor ? 'selected' : '' ?>><?= cxp_view_safe($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (cxp_form_error($cxpFieldErrors, 'metodo_pago') !== ''): ?>
                            <span id="ms-form-error-cxp_pago_metodo" class="cx-form-error ms-form-field-error"><?= cxp_form_error($cxpFieldErrors, 'metodo_pago') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="md:col-span-2">
                        <label class="cx-meta-label" for="cxp_pago_referencia">Referencia</label>
                        <input
                            id="cxp_pago_referencia"
                            class="cx-input mt-1<?= cxp_form_error_class($cxpFieldErrors, 'referencia') ?>"
                            type="text"
                            name="referencia"
                            maxlength="100"
                            value="<?= cxp_view_safe($cxpPagoReferenciaValor, '') ?>"
                            placeholder="Folio, transferencia o nota breve"
                            <?= cxp_form_error_attrs($cxpFieldErrors, 'referencia', 'ms-form-error-cxp_pago_referencia') ?>
                        >
                        <?php if (cxp_form_error($cxpFieldErrors, 'referencia') !== ''): ?>
                            <span id="ms-form-error-cxp_pago_referencia" class="cx-form-error ms-form-field-error"><?= cxp_form_error($cxpFieldErrors, 'referencia') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="md:col-span-3">
                        <label class="cx-meta-label" for="cxp_pago_notas">Notas</label>
                        <textarea id="cxp_pago_notas" class="cx-input mt-1<?= cxp_form_error_class($cxpFieldErrors, 'notas') ?>" name="notas" maxlength="1000" placeholder="Opcional"<?= cxp_form_error_attrs($cxpFieldErrors, 'notas', 'ms-form-error-cxp_pago_notas') ?>><?= cxp_view_safe($cxpPagoNotasValor, '') ?></textarea>
                        <?php if (cxp_form_error($cxpFieldErrors, 'notas') !== ''): ?>
                            <span id="ms-form-error-cxp_pago_notas" class="cx-form-error ms-form-field-error"><?= cxp_form_error($cxpFieldErrors, 'notas') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="cx-btn cx-btn-brand w-full justify-center">
                            <i class="fas fa-money-bill-wave"></i>
                            Registrar pago
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="cx-panel p-5">
            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div>
                    <h2 class="cx-panel-title">Revertir un pago</h2>
                    <p class="cx-panel-sub">
                        Si registraste un pago por error, puedes revertirlo: se crea el movimiento inverso y el dinero vuelve a Caja. No borra ni edita el pago original.
                    </p>
                </div>
            </div>

            <?php if (empty($movimientosPago)): ?>
                <div class="cx-soft-note mt-5">
                    Esta cuenta todav&iacute;a no tiene pagos que se puedan revertir.
                </div>
            <?php else: ?>
                <div class="mt-5 space-y-4">
                    <?php foreach ($movimientosPago as $movimientoPago): ?>
                        <?php
                        $movimientoPagoId = (int)($movimientoPago['id'] ?? 0);
                        $reversion = $reversionesPago[$movimientoPagoId] ?? ['elegible' => false, 'motivo_bloqueo' => 'No evaluado.'];
                        $tokenReversion = $reversionTokens[$movimientoPagoId] ?? null;
                        ?>
                        <div class="cx-pay-card">
                            <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 flex-1">
                                    <div>
                                        <div class="cx-meta-label">Pago</div>
                                        <div class="cx-meta-value">#<?= $movimientoPagoId ?></div>
                                    </div>
                                    <div>
                                        <div class="cx-meta-label">Monto</div>
                                        <div class="cx-meta-value"><?= cxp_view_money($movimientoPago['monto'] ?? 0) ?></div>
                                    </div>
                                    <div>
                                        <div class="cx-meta-label">Referencia original</div>
                                        <div class="cx-meta-value"><?= cxp_view_safe($movimientoPago['referencia'] ?? null, 'Autom&aacute;tica') ?></div>
                                    </div>
                                    <div>
                                        <div class="cx-meta-label">Referencia reversi&oacute;n</div>
                                        <div class="cx-meta-value"><?= cxp_view_safe($reversion['referencia_reversion'] ?? null, 'No disponible') ?></div>
                                    </div>
                                </div>
                                <?php if (!empty($reversion['elegible'])): ?>
                                    <span class="cx-badge cx-badge-ok"><i class="fas fa-circle-check"></i> Se puede revertir</span>
                                <?php else: ?>
                                    <span class="cx-badge cx-badge-blocked"><i class="fas fa-ban"></i> Bloqueado</span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($reversion['elegible']) && !empty($tokenReversion)): ?>
                                <?php
                                $cxpMotivoError = $cxpReversionOldMovimientoId === $movimientoPagoId
                                    ? cxp_form_error($cxpFieldErrors, 'motivo')
                                    : '';
                                $cxpMotivoValor = $cxpReversionOldMovimientoId === $movimientoPagoId
                                    ? (string)($cxpOldInput['motivo'] ?? '')
                                    : '';
                                ?>
                                <form method="POST" action="<?= url('cuentas-por-pagar/' . (int)($cuenta['id'] ?? 0) . '/movimientos/' . $movimientoPagoId . '/revertir-pago-caja') ?>" class="mt-4 grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-3 items-end">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="reversion_token" value="<?= cxp_view_safe($tokenReversion, '') ?>">
                                    <div>
                                        <label class="cx-meta-label" for="cxp_reversion_motivo_<?= $movimientoPagoId ?>">Motivo de la reversi&oacute;n</label>
                                        <textarea
                                            id="cxp_reversion_motivo_<?= $movimientoPagoId ?>"
                                            class="cx-input mt-1<?= $cxpMotivoError !== '' ? ' cx-input-error' : '' ?>"
                                            name="motivo"
                                            maxlength="1000"
                                            required
                                            placeholder="Explica por qu&eacute; reviertes este pago"
                                            <?= $cxpMotivoError !== '' ? 'aria-invalid="true" aria-describedby="ms-form-error-cxp_reversion_motivo_' . $movimientoPagoId . '"' : '' ?>
                                        ><?= cxp_view_safe($cxpMotivoValor, '') ?></textarea>
                                        <?php if ($cxpMotivoError !== ''): ?>
                                            <span id="ms-form-error-cxp_reversion_motivo_<?= $movimientoPagoId ?>" class="cx-form-error ms-form-field-error"><?= $cxpMotivoError ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <button type="submit" class="cx-btn cx-btn-danger justify-center">
                                            <i class="fas fa-rotate-left"></i>
                                            Revertir pago
                                        </button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="mt-3 cx-panel-sub">
                                    <?= cxp_view_safe($reversion['motivo_bloqueo'] ?? null, 'Este pago no se puede revertir.') ?>
                                </div>
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
                <div class="cx-empty">
                    <h3>El historial todav&iacute;a no est&aacute; disponible</h3>
                    <p>Cuando registres pagos en esta cuenta, los movimientos aparecer&aacute;n aqu&iacute;.</p>
                </div>
            <?php elseif (empty($movimientos)): ?>
                <div class="cx-empty">
                    <h3>Sin movimientos todav&iacute;a</h3>
                    <p>Esta cuenta a&uacute;n no tiene pagos ni ajustes registrados.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="cx-table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th class="is-end">Monto</th>
                                <th class="is-end">Saldo anterior</th>
                                <th class="is-end">Saldo posterior</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento): ?>
                                <tr>
                                    <td><?= cxp_view_safe($movimiento['created_at'] ?? null) ?></td>
                                    <td><?= cxp_view_safe($movimiento['tipo_movimiento'] ?? null) ?></td>
                                    <td class="is-end"><?= cxp_view_money($movimiento['monto'] ?? 0) ?></td>
                                    <td class="is-end"><?= cxp_view_money($movimiento['saldo_anterior'] ?? 0) ?></td>
                                    <td class="is-end cx-strong"><?= cxp_view_money($movimiento['saldo_posterior'] ?? 0) ?></td>
                                    <td><?= cxp_view_safe($movimiento['referencia'] ?? null, 'Sin referencia') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
