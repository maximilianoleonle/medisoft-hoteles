<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista de detalle de solicitud de factura.
 * Diseno operativo desde cero.
 */

$solicitud = $solicitud ?? [];
$habitaciones = $habitaciones ?? [];
$pagos = $pagos ?? [];
$notas_reservacion = $notas_reservacion ?? [];
$regimenes_fiscales = $regimenes_fiscales ?? [];
$usos_cfdi = $usos_cfdi ?? [];
$mensaje = get_mensaje();

$estatus_info = [
    'pendiente'  => ['label' => 'Pendiente',  'color' => '#A16207', 'bg' => '#FFF7E0', 'border' => '#E7C873', 'icon' => 'clock'],
    'en_proceso' => ['label' => 'En proceso', 'color' => '#2563EB', 'bg' => '#EEF4FF', 'border' => '#AFC8FF', 'icon' => 'spinner'],
    'completada' => ['label' => 'Completada', 'color' => '#08734B', 'bg' => '#EAF7F0', 'border' => '#98D7B6', 'icon' => 'check-circle'],
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
.invoice-desk {
    --invoice-brand: var(--brand-action-bg, var(--brand-primary, #2f7d73));
    --invoice-brand-dark: var(--brand-action-bg-hover, var(--brand-secondary, #173f45));
    --invoice-on-brand: var(--brand-action-text, #FFFEFB);
    --invoice-accent: var(--brand-accent, #c8a24e);
    --invoice-ink: var(--brand-text, #172033);
    --invoice-muted: var(--brand-muted, #687386);
    --invoice-subtle: #8a94a6;
    --invoice-paper: #fffdf8;
    --invoice-page: #f6f1e8;
    --invoice-line: color-mix(in srgb, var(--invoice-brand) 15%, #e4d7c4);
    min-height: 100dvh;
    padding: 22px 0 48px;
    color: var(--invoice-ink);
    background:
        linear-gradient(90deg, rgba(23,32,51,.035) 1px, transparent 1px),
        linear-gradient(180deg, color-mix(in srgb, var(--invoice-accent) 9%, #fbf8f1), #f6f1e8 52%, #fffdf8);
    background-size: 34px 34px, auto;
}

.invoice-shell {
    width: min(1540px, calc(100% - 32px));
    margin: 0 auto;
}

.invoice-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin-bottom: 14px;
}

.invoice-nav-left,
.invoice-nav-right {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.invoice-back,
.invoice-link-button,
.invoice-button {
    min-height: 42px;
    border: 1px solid var(--invoice-line);
    border-radius: 13px;
    padding: 0 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: rgba(255,253,248,.92);
    color: var(--invoice-brand-dark);
    font-size: .84rem;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.invoice-back:hover,
.invoice-link-button:hover,
.invoice-button:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--invoice-brand) 30%, #d8ccb9);
    background: #ffffff;
    color: var(--invoice-brand-dark);
    box-shadow: 0 14px 34px -30px rgba(23,32,51,.55);
}

.invoice-current {
    min-width: 0;
}

.invoice-current span {
    display: block;
    color: var(--invoice-subtle);
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.invoice-current strong {
    display: block;
    color: var(--invoice-ink);
    font-size: .98rem;
    font-weight: 950;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.invoice-brief {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(290px, 360px);
    gap: 14px;
    margin-bottom: 14px;
}

.invoice-title-card,
.invoice-amount-card,
.invoice-card {
    border: 1px solid var(--invoice-line);
    border-radius: 24px;
    background: rgba(255,253,248,.94);
    box-shadow: 0 22px 62px -52px rgba(23,32,51,.62);
}

.invoice-title-card {
    min-height: 190px;
    padding: clamp(22px, 3vw, 34px);
    display: grid;
    align-content: space-between;
    position: relative;
    overflow: hidden;
}

.invoice-title-card::after {
    content: "";
    position: absolute;
    right: -70px;
    top: -70px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: radial-gradient(circle, color-mix(in srgb, var(--invoice-accent) 24%, transparent), transparent 70%);
    pointer-events: none;
}

.invoice-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: fit-content;
    color: var(--invoice-brand);
    font-size: .74rem;
    font-weight: 950;
    letter-spacing: .1em;
    text-transform: uppercase;
}

.invoice-title-card h1 {
    position: relative;
    z-index: 1;
    margin: 12px 0 8px;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.3rem, 4.8vw, 5.15rem);
    line-height: .92;
    letter-spacing: 0;
    color: var(--invoice-ink);
    text-wrap: balance;
}

.invoice-title-card p {
    position: relative;
    z-index: 1;
    max-width: 64ch;
    margin: 0;
    color: var(--invoice-muted);
    font-size: .96rem;
    line-height: 1.58;
    font-weight: 650;
}

.invoice-amount-card {
    padding: 20px;
    display: grid;
    align-content: space-between;
    gap: 18px;
    background:
        radial-gradient(circle at 88% 16%, color-mix(in srgb, var(--invoice-accent) 36%, transparent), transparent 10rem),
        linear-gradient(145deg, var(--invoice-brand-dark), color-mix(in srgb, var(--invoice-brand) 78%, #102d34));
    color: var(--invoice-on-brand);
}

.invoice-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    border: 1px solid rgba(255,255,255,.28);
    border-radius: 12px;
    padding: 7px 10px;
    background: rgba(255,255,255,.12);
    color: var(--invoice-on-brand);
    font-size: .78rem;
    font-weight: 950;
}

.invoice-amount-card strong {
    display: block;
    font-size: clamp(2rem, 3.3vw, 3rem);
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.invoice-amount-card span {
    display: block;
    margin-top: 8px;
    color: rgba(255,255,255,.68);
    font-size: .82rem;
    font-weight: 750;
}

.invoice-alert {
    margin-bottom: 14px;
    border-radius: 16px;
    padding: 13px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 850;
}
.invoice-alert.flash-success { border: 1px solid #9bd8b6; background: #eaf7f0; color: #0b6b3e; }
.invoice-alert.flash-error { border: 1px solid #fda29b; background: #fef3f2; color: #b42318; }

.invoice-progress {
    margin-bottom: 14px;
    border: 1px solid var(--invoice-line);
    border-radius: 20px;
    background: rgba(255,253,248,.86);
    padding: 10px;
}

.invoice-steps {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
}

.invoice-step {
    min-height: 66px;
    border: 1px solid transparent;
    border-radius: 15px;
    padding: 10px;
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    grid-template-rows: auto auto;
    column-gap: 10px;
    align-items: center;
}

.invoice-step-icon {
    grid-row: 1 / span 2;
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--invoice-brand) 8%, #ffffff);
    color: var(--invoice-brand);
}

.invoice-step strong {
    align-self: end;
    color: var(--invoice-ink);
    font-size: .82rem;
    font-weight: 950;
}

.invoice-step span:not(.invoice-step-icon) {
    align-self: start;
    margin-top: 2px;
    color: var(--invoice-muted);
    font-size: .72rem;
    font-weight: 750;
}

.invoice-step.is-active {
    border-color: color-mix(in srgb, #2563eb 26%, #ffffff);
    background: color-mix(in srgb, #2563eb 8%, #ffffff);
}

.invoice-step.is-active .invoice-step-icon {
    background: #2563eb;
    color: #FFFEFB;
}

.invoice-step.is-complete {
    border-color: color-mix(in srgb, var(--invoice-brand) 26%, #ffffff);
    background: color-mix(in srgb, var(--invoice-brand) 8%, #ffffff);
}

.invoice-step.is-complete .invoice-step-icon {
    background: var(--invoice-brand);
    color: var(--invoice-on-brand);
}

.invoice-step.is-muted {
    opacity: .54;
}

.invoice-board {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(320px, 380px);
    gap: 16px;
    align-items: start;
}

.invoice-main,
.invoice-aside {
    display: grid;
    gap: 16px;
}

.invoice-aside {
    position: sticky;
    top: 16px;
}

.invoice-card {
    overflow: hidden;
}

.invoice-card-head {
    padding: 18px 20px 6px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}

.invoice-card-title {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    min-width: 0;
}

.invoice-card-title i {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    background: color-mix(in srgb, var(--card-tone, var(--invoice-brand)) 9%, #ffffff);
    color: var(--card-tone, var(--invoice-brand));
    border: 1px solid color-mix(in srgb, var(--card-tone, var(--invoice-brand)) 18%, #ffffff);
}

.invoice-card-title h2 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.08rem, 1.25vw, 1.34rem);
    line-height: 1.1;
    color: var(--invoice-ink);
}

.invoice-card-title p {
    margin: 4px 0 0;
    color: var(--invoice-muted);
    font-size: .78rem;
    font-weight: 750;
    line-height: 1.45;
}

.invoice-card-body {
    padding: 18px 20px 20px;
}

.invoice-fiscal-form {
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 14px;
}

.invoice-field {
    grid-column: span 6;
    min-width: 0;
}

.invoice-field.is-wide,
.invoice-field.is-notes {
    grid-column: 1 / -1;
}

.invoice-field.is-half {
    grid-column: span 6;
}

.invoice-label {
    display: block;
    margin-bottom: 7px;
    color: var(--invoice-brand-dark);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.invoice-label .required {
    color: #d92d20;
}

.invoice-input {
    width: 100%;
    min-height: 48px;
    border: 1px solid color-mix(in srgb, var(--invoice-brand) 16%, #d8cdbb);
    border-radius: 13px;
    padding: 11px 13px;
    background: #ffffff;
    color: var(--invoice-ink);
    outline: none;
    font-size: .9rem;
    font-weight: 700;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.invoice-input:focus {
    border-color: var(--invoice-brand);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--invoice-brand) 13%, transparent);
}

.invoice-input:disabled {
    background: color-mix(in srgb, var(--invoice-brand) 4%, #f5f0e8);
    color: #667085;
    cursor: not-allowed;
}

textarea.invoice-input {
    min-height: 108px;
    resize: vertical;
}

.invoice-hint {
    margin: 6px 2px 0;
    color: var(--invoice-subtle);
    font-size: .72rem;
    font-weight: 700;
}

.invoice-save-row {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-start;
    padding-top: 4px;
}

.invoice-button {
    border-color: transparent;
    color: #ffffff;
    background: linear-gradient(135deg, var(--invoice-brand), var(--invoice-brand-dark));
}

.invoice-button.is-secondary {
    border-color: var(--invoice-line);
    background: #ffffff;
    color: var(--invoice-brand-dark);
}

.invoice-button.is-success {
    background: linear-gradient(135deg, #16824e, #0b5f39);
}

.invoice-button.is-danger {
    background: linear-gradient(135deg, #c24132, #91251c);
}

.invoice-button.is-blue {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
}

.invoice-info-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.invoice-info-item {
    min-height: 84px;
    border: 1px solid color-mix(in srgb, var(--invoice-brand) 11%, #e7dccb);
    border-radius: 15px;
    background: #ffffff;
    padding: 12px;
}

.invoice-info-item span {
    display: block;
    color: var(--invoice-muted);
    font-size: .71rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.invoice-info-item strong,
.invoice-info-item a {
    display: block;
    margin-top: 5px;
    color: var(--invoice-ink);
    font-size: .9rem;
    font-weight: 950;
    overflow-wrap: anywhere;
    text-decoration: none;
}

.invoice-room-list {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 7px;
}

.invoice-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    border-radius: 12px;
    border: 1px solid var(--invoice-line);
    background: color-mix(in srgb, var(--invoice-brand) 4%, #ffffff);
    color: var(--invoice-ink);
    padding: 7px 10px;
    font-size: .78rem;
    font-weight: 900;
}

.invoice-pay-list,
.invoice-note-list {
    display: grid;
    gap: 10px;
}

.invoice-pay-item,
.invoice-note-item {
    border: 1px solid color-mix(in srgb, var(--invoice-brand) 11%, #e7dccb);
    border-radius: 15px;
    background: #ffffff;
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
    min-width: 0;
}

.invoice-pay-method i {
    width: 34px;
    height: 34px;
    border-radius: 11px;
    display: grid;
    place-items: center;
    background: color-mix(in srgb, var(--pay-color) 10%, #ffffff);
    color: var(--pay-color);
}

.invoice-pay-method strong {
    display: block;
    color: var(--invoice-ink);
    font-size: .86rem;
    font-weight: 950;
}

.invoice-pay-method span {
    display: block;
    color: var(--invoice-muted);
    font-size: .72rem;
    font-weight: 750;
}

.invoice-pay-amount {
    color: var(--invoice-ink);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}

.invoice-note-item header {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}

.invoice-note-item strong {
    color: var(--invoice-brand-dark);
    font-size: .8rem;
    font-weight: 950;
}

.invoice-note-item time {
    color: var(--invoice-muted);
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
    border: 1px solid color-mix(in srgb, var(--invoice-brand) 22%, #d8ccb9);
    border-radius: 22px;
    padding: 20px;
    color: #ffffff;
    background:
        radial-gradient(circle at 84% 12%, color-mix(in srgb, var(--invoice-accent) 42%, transparent), transparent 9rem),
        linear-gradient(145deg, var(--invoice-brand-dark), color-mix(in srgb, var(--invoice-brand) 78%, #102d34));
    box-shadow: 0 24px 62px -46px rgba(23,63,69,.75);
}

.invoice-total-card span {
    display: block;
    color: rgba(255,255,255,.72);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.invoice-total-card strong {
    display: block;
    margin-top: 9px;
    font-size: 2.45rem;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.invoice-total-card small {
    display: block;
    margin-top: 8px;
    color: rgba(255,255,255,.7);
    font-weight: 750;
    line-height: 1.45;
}

.invoice-type-copy {
    color: #5c667a;
    font-size: .84rem;
    font-weight: 750;
    line-height: 1.55;
}

.invoice-action-stack {
    display: grid;
    gap: 10px;
}

.invoice-action-stack form {
    margin: 0;
}

.invoice-action-stack .invoice-button {
    width: 100%;
}

.invoice-help {
    margin: -2px 2px 4px;
    color: var(--invoice-muted);
    font-size: .76rem;
    font-weight: 750;
}

.invoice-divider {
    border: 0;
    border-top: 1px dashed var(--invoice-line);
    margin: 8px 0;
}

.invoice-state-message {
    text-align: center;
    padding: 14px;
}

.invoice-state-message i {
    font-size: 2.3rem;
    margin-bottom: 10px;
}

.invoice-state-message strong {
    display: block;
    color: var(--invoice-ink);
    font-size: 1rem;
    font-weight: 950;
}

.invoice-state-message span {
    display: block;
    margin-top: 5px;
    color: var(--invoice-muted);
    font-size: .8rem;
    font-weight: 750;
}

.invoice-registry {
    display: grid;
    gap: 7px;
    margin-top: 4px;
    padding: 12px;
    border: 1px dashed color-mix(in srgb, var(--invoice-brand) 22%, #d8ccb9);
    border-radius: 15px;
    background: color-mix(in srgb, var(--invoice-brand) 4%, #ffffff);
}

.invoice-registry span {
    color: var(--invoice-muted);
    font-size: .74rem;
    font-weight: 750;
}

.invoice-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 13000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(12,18,32,.72);
    -webkit-backdrop-filter: blur(12px);
    backdrop-filter: blur(12px);
}

.invoice-modal-overlay.hidden {
    display: none !important;
}

.invoice-modal {
    --modal-tone: rgb(8 115 75);
    --modal-tone-dark: rgb(5 83 54);
    --modal-soft: rgb(234 247 240);
    --modal-border: rgb(143 211 178);
    width: min(520px, 100%);
    max-height: calc(100dvh - 36px);
    overflow-y: auto;
    border: 1px solid color-mix(in srgb, var(--modal-tone) 24%, rgb(226 218 204));
    border-radius: 22px;
    background: linear-gradient(180deg, rgb(255 253 248), rgb(250 247 240));
    box-shadow: 0 34px 100px -42px rgba(12,18,32,.86);
}

.invoice-modal.is-complete {
    --modal-tone: rgb(8 115 75);
    --modal-tone-dark: rgb(5 83 54);
    --modal-soft: rgb(234 247 240);
    --modal-border: rgb(143 211 178);
}

.invoice-modal.is-cancel {
    --modal-tone: rgb(180 35 24);
    --modal-tone-dark: rgb(127 29 29);
    --modal-soft: rgb(255 241 240);
    --modal-border: rgb(253 162 155);
}

.invoice-modal-head {
    display: grid;
    grid-template-columns: 46px minmax(0, 1fr);
    gap: 13px;
    align-items: center;
    padding: 22px 24px 18px;
    color: var(--invoice-ink);
    background:
        radial-gradient(circle at 90% 0%, color-mix(in srgb, var(--modal-tone) 14%, transparent), transparent 38%),
        linear-gradient(180deg, rgb(255 253 248), var(--modal-soft));
    border-bottom: 1px solid var(--modal-border);
}

.invoice-modal-icon {
    width: 46px;
    height: 46px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: rgb(255 253 248);
    background: linear-gradient(145deg, var(--modal-tone), var(--modal-tone-dark));
    box-shadow: 0 16px 30px -18px color-mix(in srgb, var(--modal-tone) 80%, transparent);
}

.invoice-modal-copy {
    min-width: 0;
}

.invoice-modal-kicker {
    display: block;
    margin-bottom: 3px;
    color: color-mix(in srgb, var(--modal-tone) 78%, var(--invoice-ink));
    font-size: .69rem;
    font-weight: 950;
    letter-spacing: .08em;
    line-height: 1;
    text-transform: uppercase;
}

.invoice-modal-head h3 {
    margin: 0;
    font-family: Georgia, "Times New Roman", serif;
    font-size: 1.28rem;
    font-weight: 950;
    line-height: 1.08;
}

.invoice-modal-body {
    padding: 22px 24px 24px;
}

.invoice-modal-body p {
    margin: 0 0 14px;
    color: #475467;
    font-size: .88rem;
    line-height: 1.5;
    font-weight: 750;
}

.invoice-modal-alert {
    display: grid;
    gap: 4px;
    margin-bottom: 16px;
    border: 1px solid var(--modal-border);
    border-radius: 16px;
    background: var(--modal-soft);
    padding: 13px 14px;
}

.invoice-modal-alert strong {
    color: color-mix(in srgb, var(--modal-tone) 76%, var(--invoice-ink));
    font-size: .88rem;
    font-weight: 950;
}

.invoice-modal-alert span {
    color: #475467;
    font-size: .78rem;
    font-weight: 760;
    line-height: 1.45;
}

.invoice-modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 18px;
    padding-top: 14px;
    border-top: 1px solid color-mix(in srgb, var(--modal-tone) 14%, rgb(231 220 203));
}

.invoice-modal-actions .invoice-button {
    flex: 1;
}

@media (max-width: 1180px) {
    .invoice-brief,
    .invoice-board {
        grid-template-columns: 1fr;
    }

    .invoice-aside {
        position: static;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .invoice-aside > .invoice-card:last-child,
    .invoice-aside > .invoice-total-card {
        grid-column: 1 / -1;
    }
}

@media (max-width: 820px) {
    .invoice-desk {
        padding-top: 12px;
    }

    .invoice-shell {
        width: min(100% - 18px, 1540px);
    }

    .invoice-topbar,
    .invoice-nav-left,
    .invoice-nav-right {
        align-items: stretch;
    }

    .invoice-topbar {
        display: grid;
        grid-template-columns: 1fr;
    }

    .invoice-steps,
    .invoice-info-grid,
    .invoice-fiscal-form,
    .invoice-aside {
        grid-template-columns: 1fr;
    }

    .invoice-field,
    .invoice-field.is-half,
    .invoice-field.is-wide,
    .invoice-field.is-notes {
        grid-column: 1 / -1;
    }

    .invoice-step {
        min-height: 58px;
    }
}

@media (max-width: 560px) {
    .invoice-title-card,
    .invoice-amount-card,
    .invoice-card-head,
    .invoice-card-body,
    .invoice-modal-head,
    .invoice-modal-body {
        padding-left: 16px;
        padding-right: 16px;
    }

    .invoice-title-card h1 {
        font-size: clamp(2.1rem, 14vw, 3rem);
    }

    .invoice-pay-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .invoice-modal-overlay {
        align-items: flex-end;
        padding: 12px;
    }

    .invoice-modal {
        height: auto;
        max-height: 88dvh;
        border-radius: 22px;
    }

    .invoice-modal-actions,
    .invoice-nav-right {
        display: grid;
        grid-template-columns: 1fr;
    }
}
</style>

<div class="invoice-desk">
    <div class="invoice-shell">
        <nav class="invoice-topbar" aria-label="Navegacion de facturacion">
            <div class="invoice-nav-left">
                <a href="<?= back_url('facturacion') ?>" class="invoice-back">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                <div class="invoice-current">
                    <span>Facturacion</span>
                    <strong>Solicitud #<?= fact_det_safe($solicitud['id'] ?? '') ?></strong>
                </div>
            </div>
            <div class="invoice-nav-right">
                <a href="<?= url('reservaciones/ver/' . ($solicitud['reservacion_id'] ?? '')) ?>" class="invoice-link-button">
                    <i class="fas fa-bed"></i>
                    Ver reservacion
                </a>
            </div>
        </nav>

        <?php if ($mensaje): ?>
            <div class="invoice-alert flash-<?= fact_det_safe($mensaje['tipo'] ?? 'success') ?>">
                <i class="fas fa-<?= ($mensaje['tipo'] ?? '') === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <?= fact_det_safe($mensaje['texto'] ?? '') ?>
            </div>
        <?php endif; ?>

        <header class="invoice-brief">
            <section class="invoice-title-card">
                <div>
                    <div class="invoice-kicker">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Expediente fiscal
                    </div>
                    <h1>Solicitud #<?= fact_det_safe($solicitud['id'] ?? '') ?></h1>
                </div>
                <p>Confirma los datos fiscales, revisa los pagos registrados y cierra la solicitud cuando la factura ya exista en Aspel.</p>
            </section>

            <aside class="invoice-amount-card" aria-label="Resumen de factura">
                <span class="invoice-status-pill">
                    <i class="fas fa-<?= fact_det_safe($est['icon']) ?>"></i>
                    <?= fact_det_safe($est['label']) ?>
                </span>
                <div>
                    <strong><?= fact_det_money($monto_total) ?></strong>
                    <span>Total a facturar</span>
                </div>
            </aside>
        </header>

        <section class="invoice-progress" aria-label="Avance de facturacion">
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
                    <span class="invoice-step-icon"><i class="fas fa-file-invoice"></i></span>
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

        <section class="invoice-board">
            <main class="invoice-main">
                <article class="invoice-card" style="--card-tone:#16824E;">
                    <div class="invoice-card-head">
                        <div class="invoice-card-title">
                            <i class="fas fa-file-alt"></i>
                            <div>
                                <h2>Datos fiscales</h2>
                                <p><?= $es_editable ? 'Captura o ajusta la informacion fiscal.' : 'Informacion registrada en la solicitud.' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-card-body">
                        <?php
                            $datosFiscalesOld = [
                                'rfc' => old('rfc', fact_det_safe($solicitud['rfc'] ?? '', '')),
                                'razon_social' => old('razon_social', fact_det_safe($solicitud['razon_social'] ?? '', '')),
                                'regimen_fiscal' => old('regimen_fiscal', $solicitud['regimen_fiscal'] ?? ''),
                                'uso_cfdi' => old('uso_cfdi', $solicitud['uso_cfdi'] ?? ''),
                                'codigo_postal_fiscal' => old('codigo_postal_fiscal', fact_det_safe($solicitud['codigo_postal_fiscal'] ?? '', '')),
                                'email_factura' => old('email_factura', fact_det_safe($solicitud['email_factura'] ?? '', '')),
                                'notas' => old('notas', fact_det_safe($solicitud['notas'] ?? '', '')),
                            ];
                        ?>
                        <form method="POST" action="<?= url('facturacion/guardar') ?>" id="formDatosFiscales" class="invoice-fiscal-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">

                            <div class="invoice-field is-half">
                                <label class="invoice-label">
                                    RFC <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="rfc" class="invoice-input"
                                       value="<?= $datosFiscalesOld['rfc'] ?>"
                                       placeholder="XAXX010101000"
                                       maxlength="13"
                                       style="text-transform: uppercase; font-family: monospace; font-size: 1rem; letter-spacing: .08em;"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                                <p class="invoice-hint">Persona fisica: 13 caracteres. Persona moral: 12 caracteres.</p>
                            </div>

                            <div class="invoice-field is-half">
                                <label class="invoice-label">
                                    Razon social <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="razon_social" class="invoice-input"
                                       value="<?= $datosFiscalesOld['razon_social'] ?>"
                                       placeholder="Nombre o razon social"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    Regimen fiscal <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <select name="regimen_fiscal" class="invoice-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($regimenes_fiscales as $clave => $nombre): ?>
                                        <option value="<?= fact_det_safe($clave) ?>" <?= ($datosFiscalesOld['regimen_fiscal'] === $clave) ? 'selected' : '' ?>>
                                            <?= fact_det_safe($clave) ?> - <?= fact_det_safe($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    Uso de CFDI <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <select name="uso_cfdi" class="invoice-input" <?= !$es_editable ? 'disabled' : '' ?>>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($usos_cfdi as $clave => $nombre): ?>
                                        <option value="<?= fact_det_safe($clave) ?>" <?= ($datosFiscalesOld['uso_cfdi'] === $clave) ? 'selected' : '' ?>>
                                            <?= fact_det_safe($clave) ?> - <?= fact_det_safe($nombre) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">
                                    C.P. fiscal <?= $es_cliente ? '<span class="required">*</span>' : '' ?>
                                </label>
                                <input type="text" name="codigo_postal_fiscal" class="invoice-input"
                                       value="<?= $datosFiscalesOld['codigo_postal_fiscal'] ?>"
                                       placeholder="00000"
                                       maxlength="5"
                                       pattern="\d{5}"
                                       style="font-family: monospace;"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field">
                                <label class="invoice-label">Email para factura</label>
                                <input type="email" name="email_factura" class="invoice-input"
                                       value="<?= $datosFiscalesOld['email_factura'] ?>"
                                       placeholder="correo@ejemplo.com"
                                       <?= !$es_editable ? 'disabled' : '' ?>>
                            </div>

                            <div class="invoice-field is-notes">
                                <label class="invoice-label">Notas adicionales</label>
                                <textarea name="notas" class="invoice-input" rows="3"
                                          placeholder="Observaciones para facturacion..."
                                          <?= !$es_editable ? 'disabled' : '' ?>><?= $datosFiscalesOld['notas'] ?></textarea>
                            </div>

                            <?php if ($es_editable): ?>
                                <div class="invoice-save-row">
                                    <button type="submit" class="invoice-button">
                                        <i class="fas fa-save"></i>
                                        Guardar datos
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </article>

                <article class="invoice-card" style="--card-tone:#2563EB;">
                    <div class="invoice-card-head">
                        <div class="invoice-card-title">
                            <i class="fas fa-bed"></i>
                            <div>
                                <h2>Reservacion vinculada</h2>
                                <p>Datos base del hospedaje y huesped.</p>
                            </div>
                        </div>
                        <a href="<?= url('reservaciones/ver/' . ($solicitud['reservacion_id'] ?? '')) ?>" class="invoice-button is-secondary">
                            <i class="fas fa-eye"></i>
                            Ver
                        </a>
                    </div>
                    <div class="invoice-card-body">
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
                                            <span class="invoice-chip">#<?= fact_det_safe($hab['numero'] ?? '') ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="invoice-chip"><?= fact_det_safe($habitaciones_texto) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <?php if (!empty($pagos)): ?>
                    <article class="invoice-card" style="--card-tone:#7C3AED;">
                        <div class="invoice-card-head">
                            <div class="invoice-card-title">
                                <i class="fas fa-wallet"></i>
                                <div>
                                    <h2>Pagos registrados</h2>
                                    <p>Movimientos de caja asociados.</p>
                                </div>
                            </div>
                            <span class="invoice-chip"><?= fact_det_money($pagos_total) ?></span>
                        </div>
                        <div class="invoice-card-body">
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
                    <article class="invoice-card" style="--card-tone:#4F46E5;">
                        <div class="invoice-card-head">
                            <div class="invoice-card-title">
                                <i class="fas fa-comments"></i>
                                <div>
                                    <h2>Notas de la reservacion</h2>
                                    <p><?= count($notas_reservacion) ?> registros internos.</p>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-card-body">
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

            <aside class="invoice-aside">
                <section class="invoice-total-card">
                    <span>Total a facturar</span>
                    <strong><?= fact_det_money($monto_total) ?></strong>
                    <small><?= $es_cliente ? 'Factura solicitada por cliente' : 'Factura de publico general / uso interno' ?></small>
                </section>

                <article class="invoice-card">
                    <div class="invoice-card-body">
                        <span class="invoice-chip">
                            <i class="fas fa-<?= $es_cliente ? 'user' : 'building' ?>"></i>
                            <?= $es_cliente ? 'Factura solicitada por cliente' : 'Publico general' ?>
                        </span>
                        <?php if ($es_cliente): ?>
                            <p class="invoice-type-copy" style="margin:12px 0 0;">El cliente solicito factura durante el check-in. Completa los datos fiscales para continuar.</p>
                        <?php else: ?>
                            <p class="invoice-type-copy" style="margin:12px 0 0;">Registro automatico por pago con tarjeta o transferencia. El cliente no solicito factura.</p>
                        <?php endif; ?>
                    </div>
                </article>

                <?php if (!empty($solicitud['notas'])): ?>
                    <article class="invoice-card" style="--card-tone:#D97706;">
                        <div class="invoice-card-head">
                            <div class="invoice-card-title">
                                <i class="fas fa-sticky-note"></i>
                                <div>
                                    <h2>Notas de facturacion</h2>
                                    <p>Observaciones de la solicitud.</p>
                                </div>
                            </div>
                        </div>
                        <div class="invoice-card-body">
                            <p class="invoice-text-note"><?= fact_det_safe($solicitud['notas']) ?></p>
                        </div>
                    </article>
                <?php endif; ?>

                <article class="invoice-card" style="--card-tone:#B7791F;">
                    <div class="invoice-card-head">
                        <div class="invoice-card-title">
                            <i class="fas fa-bolt"></i>
                            <div>
                                <h2>Acciones</h2>
                                <p>Flujo administrativo.</p>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-card-body">
                        <div class="invoice-action-stack">
                            <?php if (($solicitud['estatus'] ?? '') === 'pendiente'): ?>
                                <form method="POST" action="<?= url('facturacion/en-proceso') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
                                    <button type="submit" class="invoice-button is-blue">
                                        <i class="fas fa-spinner"></i>
                                        Marcar en proceso
                                    </button>
                                </form>
                                <p class="invoice-help">Indica que ya se estan capturando los datos fiscales.</p>
                            <?php endif; ?>

                            <?php if (in_array(($solicitud['estatus'] ?? ''), ['pendiente', 'en_proceso'], true)): ?>
                                <button type="button" onclick="abrirModalCompletar()" class="invoice-button is-success">
                                    <i class="fas fa-check-circle"></i>
                                    Marcar como facturada
                                </button>
                                <p class="invoice-help">Cuando ya se genero la factura en Aspel.</p>

                                <hr class="invoice-divider">

                                <button type="button" onclick="abrirModalCancelar()" class="invoice-button is-danger">
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

<div id="modalCompletar" class="invoice-modal-overlay hidden">
    <div class="invoice-modal is-complete" role="dialog" aria-modal="true" aria-labelledby="modalCompletarTitulo">
        <div class="invoice-modal-head">
            <div class="invoice-modal-icon" aria-hidden="true">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="invoice-modal-copy">
                <span class="invoice-modal-kicker">Confirmacion final</span>
                <h3 id="modalCompletarTitulo">Marcar como facturada</h3>
            </div>
        </div>
        <form method="POST" action="<?= url('facturacion/completar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
            <div class="invoice-modal-body">
                <div class="invoice-modal-alert">
                    <strong>La solicitud pasara a completada.</strong>
                    <span>Usa esta accion solamente cuando la factura ya exista en Aspel.</span>
                </div>
                <label class="invoice-label">Numero de factura</label>
                <input type="text" name="numero_factura" class="invoice-input" placeholder="Ej: FA-001234" style="font-family: monospace; font-size: 1rem;">
                <p class="invoice-hint">Opcional. Folio de la factura generada en Aspel.</p>
                <div class="invoice-modal-actions">
                    <button type="button" onclick="cerrarModalCompletar()" class="invoice-button is-secondary">Cancelar</button>
                    <button type="submit" class="invoice-button is-success">
                        <i class="fas fa-check"></i>
                        Confirmar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="modalCancelar" class="invoice-modal-overlay hidden">
    <div class="invoice-modal is-cancel" role="dialog" aria-modal="true" aria-labelledby="modalCancelarTitulo">
        <div class="invoice-modal-head">
            <div class="invoice-modal-icon" aria-hidden="true">
                <i class="fas fa-ban"></i>
            </div>
            <div class="invoice-modal-copy">
                <span class="invoice-modal-kicker">Accion de riesgo</span>
                <h3 id="modalCancelarTitulo">Cancelar solicitud</h3>
            </div>
        </div>
        <form method="POST" action="<?= url('facturacion/cancelar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="solicitud_id" value="<?= fact_det_safe($solicitud['id'] ?? '') ?>">
            <div class="invoice-modal-body">
                <div class="invoice-modal-alert">
                    <strong>La solicitud saldra del flujo de facturacion.</strong>
                    <span>Agrega un motivo para que recepcion y administracion sepan por que se cancelo.</span>
                </div>
                <label class="invoice-label">Motivo de cancelacion</label>
                <textarea name="motivo" class="invoice-input" rows="3" placeholder="Motivo de la cancelacion..."></textarea>
                <div class="invoice-modal-actions">
                    <button type="button" onclick="cerrarModalCancelar()" class="invoice-button is-secondary">No, volver</button>
                    <button type="submit" class="invoice-button is-danger">
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
