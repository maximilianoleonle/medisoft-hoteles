<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista de detalle de solicitud de factura.
 * Rediseño operativo tipo expediente fiscal.
 */

$solicitud = $solicitud ?? [];
$habitaciones = $habitaciones ?? [];
$pagos = $pagos ?? [];
$notas_reservacion = $notas_reservacion ?? [];
$regimenes_fiscales = $regimenes_fiscales ?? [];
$usos_cfdi = $usos_cfdi ?? [];
$mensaje = get_mensaje();

$estatus_info = [
    'pendiente'  => ['label' => 'Pendiente',  'color' => '#B7791F', 'bg' => '#FFF8E6', 'border' => '#F4D47C', 'icon' => 'clock'],
    'en_proceso' => ['label' => 'En proceso', 'color' => '#2563EB', 'bg' => '#EEF4FF', 'border' => '#AFC8FF', 'icon' => 'spinner'],
    'completada' => ['label' => 'Completada', 'color' => '#16824E', 'bg' => '#EAF7F0', 'border' => '#9BD8B6', 'icon' => 'check-circle'],
    'cancelada'  => ['label' => 'Cancelada',  'color' => '#667085', 'bg' => '#F3F4F6', 'border' => '#D0D5DD', 'icon' => 'times-circle'],
];

$est = $estatus_info[$solicitud['estatus'] ?? 'pendiente'] ?? $estatus_info['pendiente'];
$es_editable = in_array(($solicitud['estatus'] ?? ''), ['pendiente', 'en_proceso'], true);
$es_cliente = ($solicitud['tipo'] ?? '') === 'cliente';
$monto_total = (float)($solicitud['monto_total'] ?? 0);
$status_rank = ['pendiente' => 1, 'en_proceso' => 2, 'completada' => 4, 'cancelada' => 0];
$current_rank = $status_rank[$solicitud['estatus'] ?? 'pendiente'] ?? 1;

if (!function_exists('fact_det_safe')) {
    function fact_det_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('fact_det_money')) {
    function fact_det_money($amount) {
        return '$' . number_format((float)($amount ?? 0), 2);
    }
}

if (!function_exists('fact_det_date')) {
    function fact_det_date($value, $format = 'd/m/Y H:i') {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}

if (!function_exists('fact_det_step_class')) {
    function fact_det_step_class($step, $rank, $estatus) {
        if ($estatus === 'cancelada') {
            return $step === 1 ? 'is-complete' : 'is-muted';
        }

        if ($rank > $step) {
            return 'is-complete';
        }

        if ($rank === $step) {
            return 'is-active';
        }

        return '';
    }
}

$habitaciones_labels = [];
foreach ($habitaciones as $hab) {
    $numero = $hab['numero'] ?? '';
    if ($numero !== '') {
        $habitaciones_labels[] = '#' . $numero;
    }
}
$habitaciones_texto = $habitaciones_labels ? implode(', ', $habitaciones_labels) : 'Sin habitaciones';

$pagos_total = 0;
foreach ($pagos as $pago) {
    $pagos_total += (float)($pago['monto'] ?? 0);
}
?>

<style>
.invoice-request-view {
    --inv-primary: var(--brand-primary, #1B2746);
    --inv-secondary: var(--brand-secondary, #0F172A);
    --inv-accent: var(--brand-accent, #BD9441);
    --inv-bg: color-mix(in srgb, var(--inv-accent) 8%, #F7F3EC);
    --inv-surface: color-mix(in srgb, var(--inv-accent) 3%, #FFFFFF);
    --inv-line: color-mix(in srgb, var(--inv-primary) 13%, #E7DDD1);
    --inv-soft: color-mix(in srgb, var(--inv-primary) 6%, #FFFFFF);
    --inv-text: #17233E;
    --inv-muted: #778196;
    min-height: 100vh;
    background:
        radial-gradient(circle at 92% 8%, color-mix(in srgb, var(--inv-accent) 16%, transparent), transparent 30rem),
        linear-gradient(180deg, var(--inv-bg), #FBFAF7 56%, #F5EFE7);
    color: var(--inv-text);
}
.invoice-shell {
    width: min(1560px, calc(100% - 32px));
    margin: 0 auto;
    padding: 28px 0 46px;
}
.invoice-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 22px;
    align-items: end;
    padding: clamp(22px, 3vw, 36px);
    border: 1px solid color-mix(in srgb, var(--inv-primary) 24%, transparent);
    border-radius: 24px;
    color: #FFFFFF;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--inv-primary) 96%, #FFFFFF), color-mix(in srgb, var(--inv-secondary) 94%, #000000)),
        var(--inv-primary);
    box-shadow: 0 30px 80px -48px rgba(12,18,30,.74);
    overflow: hidden;
    position: relative;
}
.invoice-hero::after {
    content: "";
    position: absolute;
    inset: auto -10% -58% 46%;
    height: 230px;
    background: radial-gradient(circle, color-mix(in srgb, var(--inv-accent) 42%, transparent), transparent 68%);
    pointer-events: none;
}
.invoice-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: color-mix(in srgb, var(--inv-accent) 84%, #FFFFFF);
    font-size: .74rem;
    font-weight: 950;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.invoice-hero h1 {
    margin: 10px 0 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2rem, 4vw, 4.45rem);
    line-height: .95;
    font-weight: 700;
}
.invoice-hero p {
    max-width: 64ch;
    margin: 0;
    color: rgba(255,255,255,.72);
    font-weight: 650;
}
.invoice-hero-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}
.invoice-action {
    min-height: 42px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 12px;
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #FFFFFF;
    background: rgba(255,255,255,.11);
    font-size: .84rem;
    font-weight: 900;
    text-decoration: none;
    transition: transform .16s ease, background .16s ease, border-color .16s ease;
}
.invoice-action:hover {
    transform: translateY(-1px);
    background: rgba(255,255,255,.18);
    border-color: rgba(255,255,255,.34);
    color: #FFFFFF;
}
.invoice-action.is-main {
    border-color: transparent;
    background: linear-gradient(135deg, var(--inv-accent), color-mix(in srgb, var(--inv-accent) 76%, #6B4B16));
}
.invoice-status-card {
    min-width: 250px;
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 18px;
    padding: 16px;
    background: rgba(255,255,255,.1);
    position: relative;
    z-index: 1;
}
.invoice-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 999px;
    padding: 7px 10px;
    border: 1px solid <?= fact_det_safe($est['border']) ?>;
    background: <?= fact_det_safe($est['bg']) ?>;
    color: <?= fact_det_safe($est['color']) ?>;
    font-size: .78rem;
    font-weight: 950;
}
.invoice-status-card strong {
    display: block;
    margin-top: 12px;
    color: #FFFFFF;
    font-size: 1.85rem;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.invoice-status-card span {
    display: block;
    margin-top: 6px;
    color: rgba(255,255,255,.68);
    font-size: .78rem;
    font-weight: 750;
}
.invoice-alert {
    margin: 16px 0;
    border-radius: 16px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 850;
}
.invoice-alert.flash-success { border: 1px solid #9BD8B6; background: #EAF7F0; color: #0B6B3E; }
.invoice-alert.flash-error { border: 1px solid #FDA29B; background: #FEF3F2; color: #B42318; }
.invoice-stage {
    margin: 16px 0;
    border: 1px solid var(--inv-line);
    border-radius: 20px;
    background: rgba(255,255,255,.88);
    box-shadow: 0 18px 44px rgba(15,23,42,.06);
    padding: 16px;
}
.invoice-steps {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}
.invoice-step {
    min-height: 92px;
    border: 1px solid var(--inv-line);
    border-radius: 16px;
    background: #FFFFFF;
    padding: 13px;
    position: relative;
    overflow: hidden;
}
.invoice-step::after {
    content: "";
    position: absolute;
    left: 13px;
    right: 13px;
    bottom: 0;
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: transparent;
}
.invoice-step-icon {
    width: 32px;
    height: 32px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: #F2F4F7;
    color: #98A2B3;
    margin-bottom: 9px;
}
.invoice-step strong {
    display: block;
    color: var(--inv-secondary);
    font-size: .85rem;
    font-weight: 950;
}
.invoice-step span {
    display: block;
    margin-top: 3px;
    color: var(--inv-muted);
    font-size: .72rem;
    font-weight: 750;
}
.invoice-step.is-active {
    border-color: #AFC8FF;
    background: #F4F7FF;
}
.invoice-step.is-active .invoice-step-icon {
    background: #2563EB;
    color: #FFFFFF;
}
.invoice-step.is-active::after { background: #2563EB; }
.invoice-step.is-complete {
    border-color: #9BD8B6;
    background: #F2FBF6;
}
.invoice-step.is-complete .invoice-step-icon {
    background: #16824E;
    color: #FFFFFF;
}
.invoice-step.is-complete::after { background: #16824E; }
.invoice-step.is-muted {
    opacity: .56;
}
.invoice-workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 360px;
    gap: 16px;
    align-items: start;
}
.invoice-main-stack,
.invoice-side-stack {
    display: grid;
    gap: 16px;
}
.invoice-side-stack {
    position: sticky;
    top: 18px;
}
.invoice-panel {
    border: 1px solid var(--inv-line);
    border-radius: 20px;
    background: rgba(255,255,255,.9);
    box-shadow: 0 18px 44px rgba(15,23,42,.06);
    overflow: hidden;
}
.invoice-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 18px;
    border-bottom: 1px solid var(--inv-line);
    background: color-mix(in srgb, var(--inv-accent) 5%, #FFFFFF);
}
.invoice-panel-title {
    display: flex;
    align-items: center;
    gap: 11px;
}
.invoice-panel-title i {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--panel-color, var(--inv-primary)) 10%, #FFFFFF);
    color: var(--panel-color, var(--inv-primary));
    border: 1px solid color-mix(in srgb, var(--panel-color, var(--inv-primary)) 16%, #FFFFFF);
}
.invoice-panel-title h2 {
    margin: 0;
    color: var(--inv-secondary);
    font-size: 1rem;
    font-weight: 950;
}
.invoice-panel-title p {
    margin: 2px 0 0;
    color: var(--inv-muted);
    font-size: .76rem;
    font-weight: 750;
}
.invoice-panel-body {
    padding: 18px;
}
.invoice-fiscal-form {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}
.invoice-field {
    min-width: 0;
}
.invoice-field.is-wide {
    grid-column: 1 / -1;
}
.invoice-label {
    display: block;
    margin-bottom: 7px;
    color: var(--inv-primary);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.invoice-label .required {
    color: #D92D20;
}
.df-input {
    width: 100%;
    min-height: 43px;
    border: 1px solid color-mix(in srgb, var(--inv-primary) 15%, #DCE2EA);
    border-radius: 12px;
    padding: 9px 12px;
    background: #FFFFFF;
    color: var(--inv-secondary);
    outline: none;
    font-size: .9rem;
    font-weight: 700;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.df-input:focus {
    border-color: var(--inv-primary);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--inv-primary) 12%, transparent);
}
.df-input:disabled {
    background: #F2F4F7;
    color: #667085;
    cursor: not-allowed;
}
.df-input-hint {
    margin: 6px 2px 0;
    color: #98A2B3;
    font-size: .72rem;
    font-weight: 700;
}
.invoice-save-row {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    padding-top: 4px;
}
.df-btn {
    min-height: 42px;
    border-radius: 12px;
    border: 0;
    padding: 0 15px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #FFFFFF;
    font-size: .84rem;
    font-weight: 950;
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}
.df-btn:hover {
    transform: translateY(-1px);
}
.df-btn-primary {
    background: linear-gradient(135deg, var(--inv-primary), var(--inv-secondary));
    box-shadow: 0 16px 34px -22px color-mix(in srgb, var(--inv-primary) 70%, transparent);
}
.df-btn-success {
    background: linear-gradient(135deg, #16824E, #0F6B40);
}
.df-btn-danger {
    background: linear-gradient(135deg, #D04437, #B42318);
}
.df-btn-secondary {
    border: 1px solid var(--inv-line);
    background: #FFFFFF;
    color: var(--inv-secondary);
}
.invoice-info-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}
.invoice-info-item {
    border: 1px solid var(--inv-line);
    border-radius: 14px;
    background: #FFFFFF;
    padding: 12px;
}
.invoice-info-item span {
    display: block;
    color: var(--inv-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}
.invoice-info-item strong,
.invoice-info-item a {
    display: block;
    margin-top: 5px;
    color: var(--inv-secondary);
    font-size: .92rem;
    font-weight: 950;
    overflow-wrap: anywhere;
    text-decoration: none;
}
.invoice-room-list {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 5px;
}
.invoice-room-chip,
.invoice-type-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 999px;
    border: 1px solid var(--inv-line);
    background: #FFFFFF;
    color: var(--inv-secondary);
    padding: 7px 10px;
    font-size: .78rem;
    font-weight: 900;
}
.invoice-type-card {
    text-align: center;
}
.invoice-type-chip {
    border-color: <?= $es_cliente ? '#AFC8FF' : '#DDD6FE' ?>;
    background: <?= $es_cliente ? '#EEF4FF' : '#F5F3FF' ?>;
    color: <?= $es_cliente ? '#1D4ED8' : '#6D28D9' ?>;
}
.invoice-type-card p {
    margin: 12px 0 0;
    color: #5C667A;
    font-size: .84rem;
    font-weight: 750;
    line-height: 1.55;
}
.invoice-pay-list,
.invoice-note-list {
    display: grid;
    gap: 10px;
}
.invoice-pay-item,
.invoice-note-item {
    border: 1px solid var(--inv-line);
    border-radius: 14px;
    background: #FFFFFF;
    padding: 12px;
}
.invoice-pay-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.invoice-pay-method {
    display: flex;
    align-items: center;
    gap: 10px;
}
.invoice-pay-method i {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--pay-color) 10%, #FFFFFF);
    color: var(--pay-color);
}
.invoice-pay-method strong {
    display: block;
    color: var(--inv-secondary);
    font-size: .86rem;
    font-weight: 950;
}
.invoice-pay-method span {
    display: block;
    color: var(--inv-muted);
    font-size: .72rem;
    font-weight: 750;
}
.invoice-pay-amount {
    color: var(--inv-secondary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.invoice-note-item header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}
.invoice-note-item strong {
    color: var(--inv-primary);
    font-size: .8rem;
    font-weight: 950;
}
.invoice-note-item time {
    color: var(--inv-muted);
    font-size: .72rem;
    font-weight: 750;
}
.invoice-note-item p,
.invoice-text-note {
    margin: 0;
    color: #475467;
    font-size: .84rem;
    line-height: 1.55;
    white-space: pre-wrap;
}
.invoice-total-card {
    border-radius: 20px;
    color: #FFFFFF;
    padding: 18px;
    background:
        radial-gradient(circle at 90% 18%, color-mix(in srgb, var(--inv-accent) 42%, transparent), transparent 12rem),
        linear-gradient(135deg, var(--inv-primary), var(--inv-secondary));
}
.invoice-total-card span {
    display: block;
    color: rgba(255,255,255,.7);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.invoice-total-card strong {
    display: block;
    margin-top: 7px;
    font-size: 2.2rem;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}
.invoice-total-card small {
    display: block;
    margin-top: 8px;
    color: rgba(255,255,255,.68);
    font-weight: 750;
}
.invoice-action-stack {
    display: grid;
    gap: 10px;
}
.invoice-action-stack form {
    margin: 0;
}
.invoice-action-stack .df-btn {
    width: 100%;
}
.invoice-help {
    margin: -2px 0 4px;
    color: var(--inv-muted);
    font-size: .76rem;
    font-weight: 750;
    text-align: center;
}
.invoice-divider {
    border: 0;
    border-top: 1px dashed var(--inv-line);
    margin: 8px 0;
}
.invoice-state-message {
    text-align: center;
    padding: 14px;
}
.invoice-state-message i {
    font-size: 2.4rem;
    margin-bottom: 10px;
}
.invoice-state-message strong {
    display: block;
    color: var(--inv-secondary);
    font-size: 1rem;
    font-weight: 950;
}
.invoice-state-message span {
    display: block;
    margin-top: 5px;
    color: var(--inv-muted);
    font-size: .8rem;
    font-weight: 750;
}
.invoice-registry {
    display: grid;
    gap: 7px;
    padding-top: 12px;
    border-top: 1px dashed var(--inv-line);
}
.invoice-registry span {
    color: var(--inv-muted);
    font-size: .74rem;
    font-weight: 750;
}
.modal-overlay-factura {
    position: fixed;
    inset: 0;
    z-index: 13000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(12,18,30,.68);
    -webkit-backdrop-filter: blur(7px);
    backdrop-filter: blur(7px);
}
.modal-overlay-factura.hidden { display: none !important; }
.modal-factura {
    width: min(420px, 100%);
    max-height: 92dvh;
    overflow-y: auto;
    border-radius: 20px;
    background: #FBFAF7;
    box-shadow: 0 34px 90px -36px rgba(10,15,25,.72);
}
.invoice-modal-head {
    padding: 18px 20px;
    color: #FFFFFF;
    background: linear-gradient(135deg, var(--modal-color), color-mix(in srgb, var(--modal-color) 74%, #101828));
}
.invoice-modal-head h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: 1.05rem;
    font-weight: 950;
}
.invoice-modal-body {
    padding: 18px;
}
.invoice-modal-body p {
    margin: 0 0 14px;
    color: #475467;
    font-size: .88rem;
    line-height: 1.5;
    font-weight: 750;
}
.invoice-modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 14px;
}
.invoice-modal-actions .df-btn {
    flex: 1;
}
@media (max-width: 1120px) {
    .invoice-workspace {
        grid-template-columns: 1fr;
    }
    .invoice-side-stack {
        position: static;
    }
}
@media (max-width: 780px) {
    .invoice-shell {
        width: min(100% - 20px, 1560px);
        padding-top: 14px;
    }
    .invoice-hero {
        grid-template-columns: 1fr;
        align-items: start;
        border-radius: 18px;
    }
    .invoice-hero-actions {
        justify-content: flex-start;
    }
    .invoice-status-card {
        min-width: 0;
        width: 100%;
    }
    .invoice-steps,
    .invoice-info-grid,
    .invoice-fiscal-form {
        grid-template-columns: 1fr;
    }
    .invoice-field.is-wide {
        grid-column: auto;
    }
    .invoice-panel,
    .invoice-stage {
        border-radius: 16px;
    }
}
@media (max-width: 540px) {
    .invoice-hero-actions,
    .invoice-modal-actions {
        display: grid;
        grid-template-columns: 1fr;
        width: 100%;
    }
    .invoice-action,
    .df-btn {
        width: 100%;
    }
}

/* Rediseño 2026: mesa de control fiscal */
.invoice-request-view {
    --inv-primary: var(--brand-primary, #2F7D73);
    --inv-secondary: var(--brand-secondary, #183B44);
    --inv-accent: var(--brand-accent, #C7A15A);
    --inv-ink: #14243A;
    --inv-muted: #758195;
    --inv-line: color-mix(in srgb, var(--inv-primary) 16%, #E9E0D0);
    --inv-paper: #FFFDF8;
    --inv-soft: color-mix(in srgb, var(--inv-accent) 8%, #F7F3EA);
    min-height: 100dvh;
    background:
        radial-gradient(circle at 12% 4%, color-mix(in srgb, var(--inv-primary) 15%, transparent), transparent 28rem),
        radial-gradient(circle at 90% 8%, color-mix(in srgb, var(--inv-accent) 18%, transparent), transparent 25rem),
        linear-gradient(180deg, #F8F4EB 0%, #FFFCF5 42%, #F4EFE5 100%);
    color: var(--inv-ink);
    isolation: isolate;
}
.invoice-request-view::before {
    content: "";
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    opacity: .26;
    background-image:
        linear-gradient(120deg, rgba(47,125,115,.08) 0 1px, transparent 1px),
        linear-gradient(0deg, rgba(20,36,58,.045) 0 1px, transparent 1px);
    background-size: 38px 38px, 100% 9px;
    -webkit-mask-image: linear-gradient(180deg, #000 0%, transparent 78%);
    mask-image: linear-gradient(180deg, #000 0%, transparent 78%);
}
.invoice-shell {
    width: min(1680px, calc(100% - 30px));
    position: relative;
    z-index: 1;
    padding: 24px 0 56px;
}
.invoice-hero {
    min-height: 236px;
    grid-template-columns: minmax(0, 1.2fr) minmax(330px, .52fr);
    align-items: stretch;
    gap: 0;
    padding: 0;
    border: 1px solid rgba(255,255,255,.34);
    border-radius: 30px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--inv-secondary) 94%, #000000), color-mix(in srgb, var(--inv-primary) 86%, #0E2E30)),
        var(--inv-secondary);
    box-shadow: 0 34px 82px -56px rgba(14,28,45,.86);
}
.invoice-hero::after {
    inset: auto -9% -70% 34%;
    height: 300px;
    opacity: .9;
    background: radial-gradient(circle, color-mix(in srgb, var(--inv-accent) 36%, transparent), transparent 68%);
}
.invoice-hero > div:first-child {
    position: relative;
    z-index: 1;
    display: flex;
    min-width: 0;
    flex-direction: column;
    justify-content: space-between;
    padding: clamp(24px, 3.2vw, 44px);
}
.invoice-hero > div:first-child::after {
    content: "";
    position: absolute;
    right: 22px;
    bottom: 20px;
    width: min(38vw, 420px);
    height: 1px;
    background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--inv-accent) 78%, #FFFFFF));
    opacity: .7;
}
.invoice-hero > div:first-child > .invoice-action {
    width: fit-content !important;
    margin: 0 0 28px !important;
    min-height: 38px;
    border-radius: 999px;
    background: rgba(255,255,255,.1);
}
.invoice-eyebrow {
    width: fit-content;
    padding: 7px 11px;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 999px;
    background: rgba(255,255,255,.08);
    color: color-mix(in srgb, var(--inv-accent) 82%, #FFFFFF);
}
.invoice-hero h1 {
    max-width: 10ch;
    margin: 14px 0 10px;
    font-size: clamp(2.45rem, 5vw, 5.65rem);
    letter-spacing: 0;
    text-wrap: balance;
}
.invoice-hero p {
    max-width: 58ch;
    color: rgba(255,255,255,.76);
    font-size: clamp(.96rem, 1vw, 1.05rem);
    line-height: 1.65;
}
.invoice-hero-actions {
    z-index: 1;
    display: grid;
    grid-template-columns: 1fr;
    align-content: stretch;
    justify-items: stretch;
    gap: 14px;
    padding: clamp(18px, 2vw, 26px);
    border-left: 1px solid rgba(255,255,255,.12);
    background: linear-gradient(180deg, rgba(255,255,255,.16), rgba(255,255,255,.07));
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
}
.invoice-hero-actions > .invoice-action {
    min-height: 48px;
    border-radius: 16px;
    background: #FFFDF8;
    color: var(--inv-secondary);
    border-color: rgba(255,255,255,.45);
    box-shadow: 0 14px 34px -28px rgba(0,0,0,.65);
}
.invoice-hero-actions > .invoice-action:hover {
    color: var(--inv-secondary);
    background: #FFFFFF;
}
.invoice-status-card {
    min-width: 0;
    display: grid;
    align-content: end;
    border-radius: 24px;
    padding: 22px;
    color: var(--inv-ink);
    border: 1px solid rgba(255,255,255,.54);
    background: linear-gradient(180deg, rgba(255,255,255,.95), rgba(255,253,248,.88));
    box-shadow: inset 0 1px 0 rgba(255,255,255,.72), 0 24px 50px -38px rgba(0,0,0,.75);
}
.invoice-status-pill {
    width: fit-content;
    border-radius: 12px;
}
.invoice-status-card strong {
    margin-top: 22px;
    color: var(--inv-ink);
    font-size: clamp(2rem, 3.6vw, 3.4rem);
    letter-spacing: 0;
}
.invoice-status-card span:last-child {
    color: var(--inv-muted);
}
.invoice-alert {
    width: min(1120px, calc(100% - 22px));
    margin: 16px auto 0;
    box-shadow: 0 18px 38px -30px rgba(20,36,58,.45);
}
.invoice-stage {
    position: relative;
    z-index: 2;
    margin: -24px clamp(10px, 2.4vw, 36px) 18px;
    padding: 10px;
    border: 1px solid rgba(255,255,255,.78);
    border-radius: 24px;
    background: rgba(255,253,248,.9);
    -webkit-backdrop-filter: blur(16px);
    backdrop-filter: blur(16px);
    box-shadow: 0 22px 54px -44px rgba(20,36,58,.75);
}
.invoice-steps {
    gap: 6px;
}
.invoice-step {
    min-height: 74px;
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr);
    grid-template-rows: auto auto;
    column-gap: 10px;
    align-items: center;
    border: 0;
    border-radius: 18px;
    background: transparent;
    padding: 12px;
}
.invoice-step::after {
    left: 52px;
    right: 12px;
    top: 34px;
    bottom: auto;
    height: 2px;
    opacity: .55;
}
.invoice-step-icon {
    grid-row: 1 / span 2;
    width: 38px;
    height: 38px;
    margin: 0;
    border-radius: 14px;
    background: #F3F0E8;
}
.invoice-step strong {
    align-self: end;
}
.invoice-step span:not(.invoice-step-icon) {
    align-self: start;
}
.invoice-step.is-active {
    background: color-mix(in srgb, #2563EB 9%, #FFFFFF);
}
.invoice-step.is-complete {
    background: color-mix(in srgb, var(--inv-primary) 9%, #FFFFFF);
}
.invoice-workspace {
    grid-template-columns: minmax(320px, 390px) minmax(0, 1fr);
    gap: 18px;
}
.invoice-side-stack {
    grid-column: 1;
    grid-row: 1;
    gap: 14px;
}
.invoice-main-stack {
    grid-column: 2;
    grid-row: 1;
    gap: 18px;
}
.invoice-panel {
    border-radius: 24px;
    border: 1px solid rgba(47,125,115,.16);
    background: rgba(255,253,248,.88);
    box-shadow: 0 20px 54px -44px rgba(20,36,58,.55);
}
.invoice-main-stack > .invoice-panel:first-child {
    border-radius: 30px;
    background: linear-gradient(180deg, #FFFFFF, #FFFCF5);
    box-shadow: 0 26px 66px -48px rgba(20,36,58,.65);
}
.invoice-panel-head {
    padding: 20px 22px 8px;
    border-bottom: 0;
    background: transparent;
}
.invoice-panel-title i {
    width: 42px;
    height: 42px;
    border-radius: 15px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--panel-color, var(--inv-primary)) 13%, #FFFFFF), #FFFFFF);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--panel-color, var(--inv-primary)) 18%, transparent);
}
.invoice-panel-title h2 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.08rem, 1.4vw, 1.42rem);
    letter-spacing: 0;
}
.invoice-panel-title p {
    max-width: 60ch;
    line-height: 1.45;
}
.invoice-panel-body {
    padding: 18px 22px 22px;
}
.invoice-fiscal-form {
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 14px;
}
.invoice-field {
    grid-column: span 3;
    min-width: 0;
}
.invoice-field.is-wide {
    grid-column: 1 / -1;
}
.invoice-field.is-half {
    grid-column: span 3;
}
.invoice-field.is-notes {
    grid-column: 1 / -1;
}
.invoice-label {
    color: color-mix(in srgb, var(--inv-primary) 82%, var(--inv-ink));
    letter-spacing: .04em;
}
.df-input {
    min-height: 48px;
    border: 1px solid color-mix(in srgb, var(--inv-primary) 18%, #DDD5C6);
    border-radius: 16px;
    padding: 11px 13px;
    background: #FFFDF9;
    color: var(--inv-ink);
    font-weight: 750;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.72);
}
.df-input:focus {
    border-color: var(--inv-primary);
    background: #FFFFFF;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--inv-primary) 13%, transparent);
}
.df-input:disabled {
    background: #F2EFE7;
    border-style: dashed;
}
textarea.df-input {
    min-height: 110px;
}
.df-btn {
    min-height: 46px;
    border-radius: 15px;
    padding: 0 17px;
}
.df-btn:hover {
    transform: translateY(-2px);
}
.df-btn:active {
    transform: translateY(0) scale(.99);
}
.df-btn-primary {
    background: linear-gradient(135deg, var(--inv-primary), color-mix(in srgb, var(--inv-primary) 76%, var(--inv-secondary)));
}
.df-btn-success {
    background: linear-gradient(135deg, #16824E, color-mix(in srgb, #16824E 74%, #0B3B2A));
}
.df-btn-danger {
    background: linear-gradient(135deg, #C24132, #8F2A21);
}
.df-btn-secondary {
    background: #FFFDF8;
    color: var(--inv-secondary);
}
.invoice-info-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}
.invoice-info-item {
    min-height: 86px;
    border-radius: 18px;
    border: 1px solid rgba(47,125,115,.13);
    background: linear-gradient(180deg, #FFFFFF, #FFFDF8);
}
.invoice-room-chip,
.invoice-type-chip {
    border-radius: 12px;
    background: color-mix(in srgb, var(--inv-primary) 5%, #FFFFFF);
}
.invoice-pay-list {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}
.invoice-pay-item {
    min-height: 82px;
    border-radius: 20px;
    background: linear-gradient(180deg, color-mix(in srgb, var(--pay-color) 7%, #FFFFFF), #FFFFFF);
    border-color: color-mix(in srgb, var(--pay-color) 20%, #EFE7D8);
}
.invoice-note-item {
    border-radius: 18px;
    background: #FFFDF8;
}
.invoice-total-card {
    position: relative;
    overflow: hidden;
    min-height: 190px;
    border: 1px solid rgba(255,255,255,.52);
    border-radius: 28px;
    padding: 24px;
    background:
        radial-gradient(circle at 86% 14%, color-mix(in srgb, var(--inv-accent) 36%, transparent), transparent 10rem),
        linear-gradient(145deg, color-mix(in srgb, var(--inv-primary) 92%, #12212F), var(--inv-secondary));
    box-shadow: 0 26px 62px -44px rgba(14,28,45,.9);
}
.invoice-total-card::after {
    content: "";
    position: absolute;
    left: 22px;
    right: 22px;
    bottom: 18px;
    height: 1px;
    background: repeating-linear-gradient(90deg, rgba(255,255,255,.44) 0 8px, transparent 8px 14px);
}
.invoice-total-card strong {
    margin-top: 14px;
    font-size: clamp(2.35rem, 4vw, 3.45rem);
    letter-spacing: 0;
}
.invoice-total-card small {
    max-width: 25ch;
    line-height: 1.45;
}
.invoice-type-card {
    text-align: left;
}
.invoice-type-chip {
    border-radius: 14px;
}
.invoice-action-stack {
    gap: 12px;
}
.invoice-help {
    text-align: left;
    margin: -4px 2px 4px;
}
.invoice-divider {
    margin: 10px 0;
}
.invoice-registry {
    margin-top: 4px;
    padding: 12px;
    border: 1px dashed rgba(47,125,115,.22);
    border-radius: 16px;
    background: rgba(255,255,255,.52);
}
.modal-overlay-factura {
    z-index: 13000;
    padding: 20px;
    background:
        radial-gradient(circle at 50% 12%, color-mix(in srgb, var(--inv-accent, #C7A15A) 24%, transparent), transparent 24rem),
        rgba(12, 26, 30, .82);
    -webkit-backdrop-filter: blur(12px);
    backdrop-filter: blur(12px);
}
.modal-factura {
    width: min(520px, 100%);
    border: 1px solid rgba(255,255,255,.58);
    border-radius: 28px;
    background: #FFFDF8;
    box-shadow: 0 38px 110px -40px rgba(0,0,0,.82);
}
.invoice-modal-head {
    padding: 22px 24px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--modal-color) 94%, #FFFFFF), color-mix(in srgb, var(--modal-color) 58%, #14243A));
}
.invoice-modal-head h3 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.35rem;
}
.invoice-modal-body {
    padding: 22px 24px 24px;
}
.invoice-modal-actions {
    justify-content: flex-end;
}
@media (max-width: 1180px) {
    .invoice-workspace {
        grid-template-columns: 1fr;
    }
    .invoice-side-stack,
    .invoice-main-stack {
        grid-column: auto;
        grid-row: auto;
    }
    .invoice-side-stack {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .invoice-side-stack > .invoice-panel:last-child,
    .invoice-side-stack > .invoice-total-card {
        grid-column: 1 / -1;
    }
}
@media (max-width: 820px) {
    .invoice-shell {
        width: min(100% - 18px, 1680px);
        padding-top: 12px;
    }
    .invoice-hero {
        grid-template-columns: 1fr;
        min-height: 0;
        border-radius: 24px;
    }
    .invoice-hero-actions {
        border-left: 0;
        border-top: 1px solid rgba(255,255,255,.12);
    }
    .invoice-stage {
        margin: 12px 0 16px;
    }
    .invoice-steps,
    .invoice-info-grid,
    .invoice-fiscal-form,
    .invoice-side-stack {
        grid-template-columns: 1fr;
    }
    .invoice-field,
    .invoice-field.is-half,
    .invoice-field.is-wide,
    .invoice-field.is-notes {
        grid-column: 1 / -1;
    }
    .invoice-step {
        min-height: 66px;
    }
    .invoice-step::after {
        display: none;
    }
}
@media (max-width: 560px) {
    .invoice-hero > div:first-child,
    .invoice-hero-actions,
    .invoice-panel-head,
    .invoice-panel-body,
    .invoice-modal-body,
    .invoice-modal-head {
        padding-left: 16px;
        padding-right: 16px;
    }
    .invoice-hero h1 {
        max-width: none;
        font-size: clamp(2.15rem, 14vw, 3.15rem);
    }
    .invoice-status-card,
    .invoice-total-card,
    .invoice-panel,
    .modal-factura {
        border-radius: 20px;
    }
    .invoice-pay-item {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="invoice-request-view">
    <div class="invoice-shell">
        <header class="invoice-hero">
            <div>
                <a href="<?= url('facturacion') ?>" class="invoice-action" style="width:fit-content;margin-bottom:14px;">
                    <i class="fas fa-arrow-left"></i>
                    Volver a facturacion
                </a>
                <div class="invoice-eyebrow">
                    <i class="fas fa-file-invoice"></i>
                    Expediente fiscal
                </div>
                <h1>Solicitud #<?= fact_det_safe($solicitud['id'] ?? '') ?></h1>
                <p>Revisa hospedaje, pagos y datos fiscales antes de cerrar la solicitud de factura.</p>
            </div>

            <div class="invoice-hero-actions">
                <a href="<?= url('reservaciones/ver/' . ($solicitud['reservacion_id'] ?? '')) ?>" class="invoice-action">
                    <i class="fas fa-bed"></i>
                    Ver reservacion
                </a>
                <div class="invoice-status-card">
                    <span class="invoice-status-pill">
                        <i class="fas fa-<?= fact_det_safe($est['icon']) ?>"></i>
                        <?= fact_det_safe($est['label']) ?>
                    </span>
                    <strong><?= fact_det_money($monto_total) ?></strong>
                    <span>Total a facturar</span>
                </div>
            </div>
        </header>

        <?php if ($mensaje): ?>
            <div class="invoice-alert flash-<?= fact_det_safe($mensaje['tipo'] ?? 'success') ?>">
                <i class="fas fa-<?= ($mensaje['tipo'] ?? '') === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= fact_det_safe($mensaje['texto'] ?? '') ?>
            </div>
        <?php endif; ?>

        <section class="invoice-stage" aria-label="Avance de facturacion">
            <div class="invoice-steps">
                <article class="invoice-step <?= fact_det_step_class(1, $current_rank, $solicitud['estatus'] ?? '') ?>">
                    <span class="invoice-step-icon"><i class="fas fa-sign-in-alt"></i></span>
                    <strong>Check-in</strong>
                    <span>Solicitud creada</span>
                </article>
                <article class="invoice-step <?= fact_det_step_class(2, $current_rank, $solicitud['estatus'] ?? '') ?>">
                    <span class="invoice-step-icon"><i class="fas fa-id-card"></i></span>
                    <strong>Datos fiscales</strong>
                    <span>RFC, regimen y CFDI</span>
                </article>
                <article class="invoice-step <?= fact_det_step_class(3, $current_rank, $solicitud['estatus'] ?? '') ?>">
                    <span class="invoice-step-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <strong>Factura Aspel</strong>
                    <span>Generacion externa</span>
                </article>
                <article class="invoice-step <?= fact_det_step_class(4, $current_rank, $solicitud['estatus'] ?? '') ?>">
                    <span class="invoice-step-icon"><i class="fas fa-check"></i></span>
                    <strong>Cierre</strong>
                    <span>Solicitud completada</span>
                </article>
            </div>
        </section>

        <section class="invoice-workspace">
            <main class="invoice-main-stack">
                <article class="invoice-panel" style="--panel-color:#16824E;">
                    <div class="invoice-panel-head">
                        <div class="invoice-panel-title">
                            <i class="fas fa-file-alt"></i>
                            <div>
                                <h2>Datos fiscales</h2>
                                <p><?= $es_editable ? 'Captura o ajusta la informacion fiscal.' : 'Informacion registrada en la solicitud.' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-panel-body">
                        <form method="POST" action="<?= url('facturacion/guardar') ?>" id="formDatosFiscales" class="invoice-fiscal-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">

                            <div class="invoice-field is-wide is-half">
                                <label class="invoice-label">
                                    RFC <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="rfc" class="df-input"
                                       value="<?= fact_det_safe($solicitud['rfc'] ?? '', '') ?>"
                                       placeholder="XAXX010101000"
                                       maxlength="13"
                                       style="text-transform: uppercase; font-family: monospace; font-size: 1rem; letter-spacing: .08em;"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                                <p class="df-input-hint">Persona fisica: 13 caracteres. Persona moral: 12 caracteres.</p>
                            </div>

                            <div class="invoice-field is-wide is-half">
                                <label class="invoice-label">
                                    Razon social <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="razon_social" class="df-input"
                                       value="<?= fact_det_safe($solicitud['razon_social'] ?? '', '') ?>"
                                       placeholder="Nombre o razon social"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    Regimen fiscal <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <select name="regimen_fiscal" class="df-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($regimenes_fiscales as $clave => $nombre): ?>
                                        <option value="<?= fact_det_safe($clave) ?>" <?= (($solicitud['regimen_fiscal'] ?? '') === $clave) ? 'selected' : '' ?>>
                                            <?= fact_det_safe($clave) ?> - <?= fact_det_safe($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    Uso de CFDI <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <select name="uso_cfdi" class="df-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($usos_cfdi as $clave => $nombre): ?>
                                        <option value="<?= fact_det_safe($clave) ?>" <?= (($solicitud['uso_cfdi'] ?? '') === $clave) ? 'selected' : '' ?>>
                                            <?= fact_det_safe($clave) ?> - <?= fact_det_safe($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    C.P. fiscal <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="codigo_postal_fiscal" class="df-input"
                                       value="<?= fact_det_safe($solicitud['codigo_postal_fiscal'] ?? '', '') ?>"
                                       placeholder="00000"
                                       maxlength="5"
                                       pattern="\d{5}"
                                       style="font-family: monospace;"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">Email para factura</label>
                                <input type="email" name="email_factura" class="df-input"
                                       value="<?= fact_det_safe($solicitud['email_factura'] ?? '', '') ?>"
                                       placeholder="correo@ejemplo.com"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field is-wide is-notes">
                                <label class="invoice-label">Notas adicionales</label>
                                <textarea name="notas" class="df-input" rows="3"
                                          placeholder="Observaciones para facturacion..."
                                          style="resize: vertical;"
                                          <?= !$es_editable ? 'disabled' : '' ?>><?= fact_det_safe($solicitud['notas'] ?? '', '') ?></textarea>
                            </div>

                            <?php if ($es_editable): ?>
                                <div class="invoice-save-row">
                                    <button type="submit" class="df-btn df-btn-primary">
                                        <i class="fas fa-save"></i>
                                        Guardar datos
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </article>

                <article class="invoice-panel" style="--panel-color:#2563EB;">
                    <div class="invoice-panel-head">
                        <div class="invoice-panel-title">
                            <i class="fas fa-bed"></i>
                            <div>
                                <h2>Reservacion vinculada</h2>
                                <p>Datos base del hospedaje y huesped.</p>
                            </div>
                        </div>
                        <a href="<?= url('reservaciones/ver/' . ($solicitud['reservacion_id'] ?? '')) ?>" class="df-btn df-btn-secondary">
                            <i class="fas fa-eye"></i>
                            Ver
                        </a>
                    </div>
                    <div class="invoice-panel-body">
                        <div class="invoice-info-grid">
                            <div class="invoice-info-item">
                                <span>Reservacion</span>
                                <strong>#<?= fact_det_safe($solicitud['reservacion_id'] ?? '') ?></strong>
                            </div>
                            <div class="invoice-info-item">
                                <span>Huesped</span>
                                <strong><?= fact_det_safe($solicitud['huesped_nombre'] ?? '') ?></strong>
                            </div>
                            <?php if (!empty($solicitud['huesped_telefono'])): ?>
                                <div class="invoice-info-item">
                                    <span>Telefono</span>
                                    <a href="tel:<?= fact_det_safe($solicitud['huesped_telefono']) ?>"><?= fact_det_safe($solicitud['huesped_telefono']) ?></a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($solicitud['huesped_email'])): ?>
                                <div class="invoice-info-item">
                                    <span>Email</span>
                                    <strong><?= fact_det_safe($solicitud['huesped_email']) ?></strong>
                                </div>
                            <?php endif; ?>
                            <div class="invoice-info-item">
                                <span>Entrada</span>
                                <strong><?= fact_det_safe(fact_det_date($solicitud['fecha_entrada'] ?? '', 'd/m/Y')) ?></strong>
                            </div>
                            <div class="invoice-info-item">
                                <span>Salida</span>
                                <strong><?= fact_det_safe(fact_det_date($solicitud['fecha_salida'] ?? '', 'd/m/Y')) ?></strong>
                            </div>
                            <div class="invoice-info-item" style="grid-column:1/-1;">
                                <span>Habitaciones</span>
                                <div class="invoice-room-list">
                                    <?php if (!empty($habitaciones)): ?>
                                        <?php foreach ($habitaciones as $hab): ?>
                                            <span class="invoice-room-chip">#<?= fact_det_safe($hab['numero'] ?? '') ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="invoice-room-chip"><?= fact_det_safe($habitaciones_texto) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <?php if (!empty($pagos)): ?>
                    <article class="invoice-panel" style="--panel-color:#7C3AED;">
                        <div class="invoice-panel-head">
                            <div class="invoice-panel-title">
                                <i class="fas fa-wallet"></i>
                                <div>
                                    <h2>Pagos registrados</h2>
                                    <p>Movimientos de caja asociados.</p>
                                </div>
                            </div>
                            <span class="invoice-room-chip"><?= fact_det_money($pagos_total) ?></span>
                        </div>
                        <div class="invoice-panel-body">
                            <div class="invoice-pay-list">
                                <?php foreach ($pagos as $pago): ?>
                                    <?php
                                        $metodo_icons = ['efectivo' => 'money-bill-wave', 'tarjeta' => 'credit-card', 'transferencia' => 'exchange-alt'];
                                        $metodo_colors = ['efectivo' => '#16824E', 'tarjeta' => '#2563EB', 'transferencia' => '#7C3AED'];
                                        $metodo = $pago['metodo_pago'] ?? '';
                                    ?>
                                    <div class="invoice-pay-item" style="--pay-color: <?= fact_det_safe($metodo_colors[$metodo] ?? '#667085') ?>;">
                                        <div class="invoice-pay-method">
                                            <i class="fas fa-<?= fact_det_safe($metodo_icons[$metodo] ?? 'circle') ?>"></i>
                                            <div>
                                                <strong><?= fact_det_safe(ucfirst((string)$metodo), 'Pago') ?></strong>
                                                <?php if (!empty($pago['referencia'])): ?>
                                                    <span>Ref: <?= fact_det_safe($pago['referencia']) ?></span>
                                                <?php else: ?>
                                                    <span>Sin referencia</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <span class="invoice-pay-amount"><?= fact_det_money($pago['monto'] ?? 0) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>

                <?php if (!empty($notas_reservacion)): ?>
                    <article class="invoice-panel" style="--panel-color:#4F46E5;">
                        <div class="invoice-panel-head">
                            <div class="invoice-panel-title">
                                <i class="fas fa-comments"></i>
                                <div>
                                    <h2>Notas de la reservacion</h2>
                                    <p><?= count($notas_reservacion) ?> registros internos.</p>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-panel-body">
                            <div class="invoice-note-list">
                                <?php foreach ($notas_reservacion as $nota): ?>
                                    <div class="invoice-note-item">
                                        <header>
                                            <strong><?= fact_det_safe($nota['usuario_nombre'] ?? '') ?></strong>
                                            <time><?= fact_det_safe(fact_det_date($nota['created_at'] ?? '')) ?></time>
                                        </header>
                                        <p><?= nl2br(fact_det_safe($nota['nota'] ?? '', '')) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>
            </main>

            <aside class="invoice-side-stack">
                <section class="invoice-total-card">
                    <span>Total a facturar</span>
                    <strong><?= fact_det_money($monto_total) ?></strong>
                    <small><?= $es_cliente ? 'Factura solicitada por cliente' : 'Factura de publico general / uso interno' ?></small>
                </section>

                <article class="invoice-panel">
                    <div class="invoice-panel-body invoice-type-card">
                        <span class="invoice-type-chip">
                            <i class="fas fa-<?= $es_cliente ? 'user' : 'building' ?>"></i>
                            <?= $es_cliente ? 'Factura solicitada por cliente' : 'Publico general' ?>
                        </span>
                        <?php if ($es_cliente): ?>
                            <p>El cliente solicito factura durante el check-in. Completa los datos fiscales para continuar.</p>
                        <?php else: ?>
                            <p>Registro automatico por pago con tarjeta o transferencia. El cliente no solicito factura.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <?php if (!empty($solicitud['notas'])): ?>
                    <article class="invoice-panel" style="--panel-color:#D97706;">
                        <div class="invoice-panel-head">
                            <div class="invoice-panel-title">
                                <i class="fas fa-sticky-note"></i>
                                <div>
                                    <h2>Notas de facturacion</h2>
                                    <p>Observaciones de la solicitud.</p>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-panel-body">
                            <p class="invoice-text-note"><?= fact_det_safe($solicitud['notas']) ?></p>
                        </div>
                    </article>
                <?php endif; ?>

                <article class="invoice-panel" style="--panel-color:#B7791F;">
                    <div class="invoice-panel-head">
                        <div class="invoice-panel-title">
                            <i class="fas fa-bolt"></i>
                            <div>
                                <h2>Acciones</h2>
                                <p>Flujo administrativo.</p>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-panel-body">
                        <div class="invoice-action-stack">
                            <?php if (($solicitud['estatus'] ?? '') === 'pendiente'): ?>
                                <form method="POST" action="<?= url('facturacion/en-proceso') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
                                    <button type="submit" class="df-btn" style="background:linear-gradient(135deg,#2563EB,#1D4ED8);">
                                        <i class="fas fa-spinner"></i>
                                        Marcar en proceso
                                    </button>
                                </form>
                                <p class="invoice-help">Indica que ya se estan capturando los datos fiscales.</p>
                            <?php endif; ?>

                            <?php if (in_array(($solicitud['estatus'] ?? ''), ['pendiente', 'en_proceso'], true)): ?>
                                <button type="button" onclick="abrirModalCompletar()" class="df-btn df-btn-success">
                                    <i class="fas fa-check-circle"></i>
                                    Marcar como facturada
                                </button>
                                <p class="invoice-help">Cuando ya se genero la factura en Aspel.</p>

                                <hr class="invoice-divider">

                                <button type="button" onclick="abrirModalCancelar()" class="df-btn df-btn-danger">
                                    <i class="fas fa-times-circle"></i>
                                    Cancelar solicitud
                                </button>
                            <?php endif; ?>

                            <?php if (($solicitud['estatus'] ?? '') === 'completada'): ?>
                                <div class="invoice-state-message">
                                    <i class="fas fa-check-circle" style="color:#16824E;"></i>
                                    <strong>Factura completada</strong>
                                    <?php if (!empty($solicitud['numero_factura'])): ?>
                                        <span>No. factura: <?= fact_det_safe($solicitud['numero_factura']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($solicitud['fecha_facturada'])): ?>
                                        <span><?= fact_det_safe(fact_det_date($solicitud['fecha_facturada'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (($solicitud['estatus'] ?? '') === 'cancelada'): ?>
                                <div class="invoice-state-message">
                                    <i class="fas fa-times-circle" style="color:#667085;"></i>
                                    <strong>Solicitud cancelada</strong>
                                    <span>Sin acciones pendientes.</span>
                                </div>
                            <?php endif; ?>

                            <div class="invoice-registry">
                                <span><i class="fas fa-user"></i> Registrado por: <?= fact_det_safe($solicitud['registrado_por'] ?? 'Sistema') ?></span>
                                <span><i class="fas fa-clock"></i> Creado: <?= fact_det_safe(fact_det_date($solicitud['created_at'] ?? '')) ?></span>
                            </div>
                        </div>
                    </div>
                </article>
            </aside>
        </section>
    </div>
</div>

<div id="modalCompletar" class="modal-overlay-factura hidden">
    <div class="modal-factura">
        <div class="invoice-modal-head" style="--modal-color:#16824E;">
            <h3>
                <i class="fas fa-check-circle"></i>
                Marcar como facturada
            </h3>
        </div>
        <form method="POST" action="<?= url('facturacion/completar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
            <div class="invoice-modal-body">
                <p>Confirma que la factura ya fue generada en Aspel. Puedes registrar el folio de la factura.</p>
                <label class="invoice-label">Numero de factura</label>
                <input type="text" name="numero_factura" class="df-input" placeholder="Ej: FA-001234" style="font-family: monospace; font-size: 1rem;">
                <p class="df-input-hint">Opcional. Folio de la factura generada en Aspel.</p>
                <div class="invoice-modal-actions">
                    <button type="button" onclick="cerrarModalCompletar()" class="df-btn df-btn-secondary">Cancelar</button>
                    <button type="submit" class="df-btn df-btn-success">
                        <i class="fas fa-check"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="modalCancelar" class="modal-overlay-factura hidden">
    <div class="modal-factura">
        <div class="invoice-modal-head" style="--modal-color:#D04437;">
            <h3>
                <i class="fas fa-times-circle"></i>
                Cancelar solicitud
            </h3>
        </div>
        <form method="POST" action="<?= url('facturacion/cancelar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
            <div class="invoice-modal-body">
                <p>Esta accion cancela la solicitud de factura. Agrega un motivo para dejar trazabilidad.</p>
                <label class="invoice-label">Motivo de cancelacion</label>
                <textarea name="motivo" class="df-input" rows="3" placeholder="Motivo de la cancelacion..." style="resize: vertical;"></textarea>
                <div class="invoice-modal-actions">
                    <button type="button" onclick="cerrarModalCancelar()" class="df-btn df-btn-secondary">No, volver</button>
                    <button type="submit" class="df-btn df-btn-danger">
                        <i class="fas fa-times"></i>
                        Si, cancelar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelector('input[name="rfc"]')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

document.querySelector('input[name="codigo_postal_fiscal"]')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '').substring(0, 5);
});

function abrirModalCompletar() {
    document.getElementById('modalCompletar').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalCompletar() {
    document.getElementById('modalCompletar').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function abrirModalCancelar() {
    document.getElementById('modalCancelar').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalCancelar() {
    document.getElementById('modalCancelar').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalCompletar();
        cerrarModalCancelar();
    }
});

document.getElementById('modalCompletar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCompletar();
});

document.getElementById('modalCancelar')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCancelar();
});
</script>
