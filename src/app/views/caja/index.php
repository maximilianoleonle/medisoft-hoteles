<?php
/**
 * Vista Principal de Caja
 * Vista hotelera
 */
$cajaFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];
$cajaOldInput = is_array($_SESSION['old_input'] ?? null) ? $_SESSION['old_input'] : [];
$cajaOldOrigin = (string)($cajaOldInput['form_origen'] ?? $cajaOldInput['tipo'] ?? '');
$cajaIngresoOld = $cajaOldOrigin === 'ingreso' ? $cajaOldInput : [];
$cajaGastoOld = $cajaOldOrigin === 'gasto' ? $cajaOldInput : [];
$cajaIngresoErrors = $cajaOldOrigin === 'ingreso' ? $cajaFieldErrors : [];
$cajaGastoErrors = $cajaOldOrigin === 'gasto' ? $cajaFieldErrors : [];

if (!function_exists('caja_form_safe')) {
    function caja_form_safe($value, string $default = ''): string
    {
        $value = $value ?? $default;
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('caja_form_old')) {
    function caja_form_old(array $old, string $field, string $default = ''): string
    {
        return caja_form_safe($old[$field] ?? $default);
    }
}

if (!function_exists('caja_form_selected')) {
    function caja_form_selected(array $old, string $field, string $value, bool $default = false): string
    {
        $current = (string)($old[$field] ?? '');
        $selected = $current !== '' ? $current === $value : $default;

        return $selected ? ' selected' : '';
    }
}

if (!function_exists('caja_form_error')) {
    function caja_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? caja_form_safe($message) : '';
    }
}

if (!function_exists('caja_form_error_class')) {
    function caja_form_error_class(array $errors, string $field): string
    {
        return caja_form_error($errors, $field) !== '' ? ' modal-input-error' : '';
    }
}

if (!function_exists('caja_form_error_attrs')) {
    function caja_form_error_attrs(array $errors, string $field, string $errorId): string
    {
        if (caja_form_error($errors, $field) === '') {
            return '';
        }

        return ' aria-invalid="true" aria-describedby="' . caja_form_safe($errorId) . '"';
    }
}
if (!function_exists('cj_finance_label')) {
    function cj_finance_label($value) {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return 'Sin concepto';
        }

        $key = strtolower($text);
        $labels = [
            'cobro cxc' => 'Cobro de cuenta pendiente',
            'reversion cobro cxc' => 'Cancelacion de cobro pendiente',
            'anticipo reservacion' => 'Anticipo de reservacion',
            'reverso anticipo' => 'Cancelacion de anticipo',
            'reverso de anticipo' => 'Cancelacion de anticipo',
            'devolucion' => 'Dinero devuelto',
            'devoluciones' => 'Dinero devuelto',
            'hospedaje' => 'Pago de hospedaje',
            'pago proveedor' => 'Pago a proveedor',
            'reversion pago proveedor' => 'Cancelacion de pago a proveedor',
            'pago laboral' => 'Pago al personal',
            'reversion pago laboral' => 'Cancelacion de pago al personal',
        ];

        return $labels[$key] ?? str_replace(['CxC', 'CXC', 'CxP', 'CXP'], ['cuenta pendiente', 'cuenta pendiente', 'cuenta por pagar', 'cuenta por pagar'], $text);
    }
}

if (!function_exists('cj_finance_sentence')) {
    function cj_finance_sentence($value) {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return '';
        }

        return str_ireplace(
            ['Cobro CxC', 'Reversion Cobro CxC', 'Anticipo reservacion', 'Reverso de anticipo', 'Reverso anticipo', 'Devoluciones'],
            ['Cobro de cuenta pendiente', 'Cancelacion de cobro pendiente', 'Anticipo de reservacion', 'Cancelacion de anticipo', 'Cancelacion de anticipo', 'Dinero devuelto'],
            $text
        );
    }
}
?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Manrope:wght@400;500;600;700;800&display=swap');

/* ── Design tokens ── */
:root {
    --cj-navy:   var(--brand-action-bg,  #1B2746);
    --cj-navy2:  var(--brand-action-bg-hover, #111B38);
    --cj-gold:   var(--brand-accent, #B0883F);
    --cj-ivory:  color-mix(in srgb, var(--cj-gold) 6%, #F7F4EC);
    --cj-surface:#FFFFFF;
    --cj-line:   color-mix(in srgb, var(--cj-navy) 10%, #DDD6C4);
    --cj-text:   var(--brand-text, #1F2937);
    --cj-muted:  #6B7280;
    --cj-on-dark:#FFFEFB;
    --cj-green:  #1E9E63;
    --cj-red:    #D64539;
    --cj-blue:   #2F77E0;
    --cj-purple: #5A57D2;
    --cj-r-xs:8px; --cj-r-sm:11px; --cj-r:15px; --cj-r-lg:20px; --cj-r-xl:26px; --cj-r-pill:999px;
    --cj-shadow-sm: 0 1px 3px rgba(17,24,39,.06);
    --cj-shadow:    0 4px 16px -6px rgba(17,24,39,.11), 0 1px 3px rgba(17,24,39,.05);
    --cj-shadow-lg: 0 22px 52px -30px rgba(17,24,39,.30), 0 4px 18px -8px rgba(17,24,39,.08);
    --cj-serif: 'Cormorant Garamond', Georgia, serif;
    --cj-sans:  'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* ── Page shell ── */
.cj-page {
    min-height:100vh;
    background:
        radial-gradient(900px 500px at 90% -4%, color-mix(in srgb, var(--cj-gold) 9%, transparent), transparent 64%),
        linear-gradient(180deg, var(--cj-ivory), #F2EDE0 60%, #E9E0CC);
    font-family:var(--cj-sans);
    color:var(--cj-text);
    -webkit-font-smoothing:antialiased;
}

/* ── Topbar ── */
.cj-topbar {
    display:flex; align-items:center; gap:10px;
    padding:12px 24px;
    border-bottom:1px solid var(--cj-line);
    background:rgba(255,255,255,.85);
    backdrop-filter:blur(12px);
    -webkit-backdrop-filter:blur(12px);
    position:sticky; top:0; z-index:20;
}
.cj-back {
    width:34px; height:34px;
    display:grid; place-items:center;
    border-radius:var(--cj-r-xs);
    border:1px solid var(--cj-line);
    background:var(--cj-surface);
    color:var(--cj-muted);
    text-decoration:none; flex:0 0 34px;
    transition:background .15s,color .15s;
}
.cj-back:hover { background:var(--cj-ivory); color:var(--cj-text); }
.cj-crumbs {
    display:flex; flex-wrap:wrap; align-items:center; gap:5px;
    list-style:none; margin:0; padding:0;
    flex:1; min-width:0; overflow:hidden;
}
.cj-crumbs li { font-size:.8rem; font-weight:600; color:var(--cj-muted); white-space:nowrap; }
.cj-crumbs li:not(:first-child)::before { content:'›'; margin-right:5px; opacity:.5; }
.cj-crumbs li:last-child { color:var(--cj-text); }
.cj-crumbs a { color:inherit; text-decoration:none; transition:color .15s; }
.cj-crumbs a:hover { color:var(--cj-text); }
.cj-topbar-acts { display:flex; align-items:center; gap:8px; margin-left:auto; }
.cj-btn-ghost {
    display:inline-flex; align-items:center; gap:6px;
    height:32px; padding:0 11px;
    border-radius:var(--cj-r-sm);
    border:1px solid var(--cj-line);
    background:transparent;
    color:var(--cj-text); font-size:.78rem; font-weight:600;
    text-decoration:none; transition:background .15s;
}
.cj-btn-ghost:hover { background:var(--cj-ivory); }

/* ── Inner container ── */
.cj-inner { max-width:none; margin:0 auto; padding:26px 32px 60px; }

/* ── resh (header card) ── */
.cj-resh {
    position:relative; overflow:hidden;
    border-radius:var(--cj-r-lg);
    background:
        radial-gradient(ellipse 60% 70% at 90% -5%, color-mix(in srgb, var(--cj-gold) 22%, transparent), transparent 55%),
        var(--brand-action-bg, #1B2746);
    border:1px solid color-mix(in srgb, var(--cj-gold) 18%, transparent);
    box-shadow:var(--cj-shadow-lg);
    padding: 28px 32px !important;
    display:flex !important;
    align-items:center;
    justify-content:space-between;
    gap:28px;
    flex-wrap:wrap;
    margin-bottom:22px;
}

/* bloque izquierdo: crece, nunca se estrecha hasta 0 */
.cj-resh-info {
    flex:1 1 280px;
    min-width:0;
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    gap:0;
    /* padding extra de seguridad por si el outer padding falla */
    padding-left:0;
}

.cj-resh-state {
    display:inline-flex; align-items:center; gap:7px;
    padding:4px 11px; border-radius:var(--cj-r-pill);
    background:rgba(34,197,94,.14);
    border:1px solid rgba(34,197,94,.28);
    color:#86EFAC;
    font-size:.68rem; font-weight:700; letter-spacing:.09em; text-transform:uppercase;
    margin-bottom:14px;
    /* asegura que el badge no se pegue al borde superior del card */
    margin-top:0;
}
.cj-resh-dot {
    width:6px; height:6px; border-radius:50%;
    background:#4ADE80;
    box-shadow:0 0 0 3px rgba(74,222,128,.22);
    animation:cj-pulse 2.2s ease-in-out infinite;
}
@keyframes cj-pulse {
    0%,100% { box-shadow:0 0 0 3px rgba(74,222,128,.22); }
    50%      { box-shadow:0 0 0 6px rgba(74,222,128,.08); }
}

/* título más equilibrado: reducido para no dominar el header */
.cj-resh-name {
    margin:0 0 5px;
    font-family:var(--cj-serif);
    font-size:clamp(1.6rem, 2.6vw, 2.2rem);
    font-weight:600; line-height:1.1;
    color:#FFFFFF;
    letter-spacing:-.01em;
}

.cj-resh-hotel {
    margin:0 0 18px;
    color:rgba(255,255,255,.60);
    font-size:.82rem; font-weight:500;
    letter-spacing:.02em;
}

.cj-resh-meta {
    display:flex; flex-wrap:wrap; gap:6px;
    font-size:.78rem; font-weight:500;
}
.cj-resh-meta span {
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 11px;
    border-radius:var(--cj-r-pill);
    background:rgba(255,255,255,.10);
    border:1px solid rgba(255,255,255,.12);
    color:rgba(255,255,255,.80);
    white-space:nowrap;
    line-height:1;
}
.cj-resh-meta i { font-size:.68rem; opacity:.75; }

/* ── Bloque de botones: lado derecho, columna fija ── */
.cj-resh-acts {
    flex:0 0 auto;
    display:flex;
    flex-direction:column;
    gap:8px;
    min-width:170px;
}

.cj-act-btn {
    display:inline-flex; align-items:center; justify-content:center;
    gap:9px; min-height:40px; width:100%; padding:0 20px;
    border-radius:var(--cj-r-sm);
    font-family:var(--cj-sans); font-size:.82rem; font-weight:700;
    text-decoration:none; cursor:pointer; white-space:nowrap;
    transition:transform .13s, filter .13s;
    border:none;
}
.cj-act-btn:hover  { transform:translateY(-1px); filter:brightness(1.1); }
.cj-act-btn:active { transform:scale(.97); }

.cj-act-income  { background:#1E9E63; color:#ffffff; }
.cj-act-expense { background:#D04A3E; color:#ffffff; }
.cj-act-corte   {
    background:#2F77E0;
    color:#ffffff;
}

/* ── Two-col grid ── */
.cj-grid {
    display:grid;
    grid-template-columns:minmax(0,1fr) 370px;
    gap:20px; align-items:start;
}
.cj-col { display:flex; flex-direction:column; gap:20px; }

/* ── Card component ── */
.cj-card {
    background:var(--cj-surface);
    border:1px solid var(--cj-line);
    border-radius:var(--cj-r-lg);
    box-shadow:var(--cj-shadow);
    overflow:hidden;
}
.cj-card-head {
    display:flex; align-items:center; gap:12px;
    padding:16px 20px;
    border-bottom:1px solid var(--cj-line);
    background:linear-gradient(180deg, var(--cj-surface), color-mix(in srgb, var(--cj-gold) 3%, var(--cj-surface)));
}
.cj-card-ico {
    width:36px; height:36px; flex:0 0 36px;
    display:grid; place-items:center;
    border-radius:var(--cj-r-sm);
    background:color-mix(in srgb, var(--cj-navy) 7%, var(--cj-ivory));
    border:1px solid color-mix(in srgb, var(--cj-navy) 12%, var(--cj-line));
    color:var(--cj-navy); font-size:.85rem;
}
.cj-card-head h2 {
    margin:0;
    font-family:var(--cj-serif);
    font-size:1.12rem; font-weight:600;
    color:var(--cj-text); line-height:1.2;
    flex:1; min-width:0;
}
/* En PC los títulos de bloque ganan un poco más de presencia */
@media (min-width:769px) {
    .cj-card-head h2 {
        font-size:1.26rem; font-weight:700;
        color:var(--cj-navy);
    }
}
.cj-card-tail { display:flex; align-items:center; gap:6px; margin-left:auto; min-width:0; }
.cj-card-tail a, .cj-card-tail span {
    font-size:.75rem; font-weight:600; color:var(--cj-muted);
    text-decoration:none; padding:4px 9px;
    border-radius:var(--cj-r-xs); border:1px solid var(--cj-line);
    transition:background .15s;
    max-width:100%;
}
.cj-card-tail a:hover { background:var(--cj-ivory); color:var(--cj-text); }
.cj-card-body { padding:20px; }

/* ── KPI tiles ── */
.cj-kpis {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(170px, 1fr));
    gap:12px;
}
.cj-kpi {
    padding:14px 16px;
    border-radius:var(--cj-r);
    border:1px solid var(--cj-line);
    background:color-mix(in srgb, var(--cj-gold) 3%, var(--cj-surface));
    display:flex; flex-direction:column; gap:6px;
}
.cj-kpi-label { font-size:.68rem; font-weight:700; color:var(--cj-muted); text-transform:uppercase; letter-spacing:.06em; }
.cj-kpi-value {
    font-family:var(--cj-serif);
    font-size:1.48rem; font-weight:600;
    color:var(--cj-text); line-height:1;
    font-variant-numeric:tabular-nums;
}
.cj-kpi-value.is-income { color:var(--cj-green); }
.cj-kpi-value.is-expense { color:var(--cj-red); }
.cj-kpi-value.is-pos { color:var(--cj-green); }
.cj-kpi-value.is-neg { color:var(--cj-red); }

/* ── Payment methods ── */
.cj-methods { display:flex; flex-direction:column; }
.cj-method {
    display:flex; align-items:center; gap:13px;
    padding:13px 0;
    border-bottom:1px solid var(--cj-line);
}
.cj-method:first-child { padding-top:0; }
.cj-method:last-child { border-bottom:none; padding-bottom:0; }
.cj-method-ico {
    width:36px; height:36px; flex:0 0 36px;
    display:grid; place-items:center;
    border-radius:var(--cj-r-sm); font-size:.82rem;
}
.cj-method-ico.m-efectivo     { background:color-mix(in srgb, var(--cj-green) 11%, transparent); color:var(--cj-green);  border:1px solid color-mix(in srgb, var(--cj-green)  22%, transparent); }
.cj-method-ico.m-tarjeta      { background:color-mix(in srgb, var(--cj-blue)  10%, transparent); color:var(--cj-blue);   border:1px solid color-mix(in srgb, var(--cj-blue)   20%, transparent); }
.cj-method-ico.m-transferencia{ background:color-mix(in srgb, var(--cj-purple)10%, transparent); color:var(--cj-purple); border:1px solid color-mix(in srgb, var(--cj-purple) 20%, transparent); }
.cj-method-info { flex:1; min-width:0; }
.cj-method-name { font-size:.88rem; font-weight:600; color:var(--cj-text); }
.cj-method-note { font-size:.74rem; color:var(--cj-muted); margin-top:2px; }
.cj-method-bar  { width:100%; height:3px; border-radius:2px; background:var(--cj-line); margin-top:5px; overflow:hidden; }
.cj-method-fill { height:100%; border-radius:2px; transition:width .5s ease; }
.cj-method-fill.m-efectivo     { background:var(--cj-green); }
.cj-method-fill.m-tarjeta      { background:var(--cj-blue); }
.cj-method-fill.m-transferencia{ background:var(--cj-purple); }
.cj-method-pct { font-size:.7rem; font-weight:700; color:var(--cj-muted); margin-top:3px; }
.cj-method-amount {
    text-align:right;
    font-family:var(--cj-serif);
    font-size:1.06rem; font-weight:600;
    color:var(--cj-text); white-space:nowrap;
    font-variant-numeric:tabular-nums;
}

/* ── Shortcuts ── */
.cj-shortcuts { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:10px; }
.cj-shortcut {
    display:flex; align-items:center; gap:10px;
    padding:11px 13px;
    border-radius:var(--cj-r);
    border:1px solid var(--cj-line);
    background:var(--cj-surface);
    color:var(--cj-text); text-decoration:none;
    font-size:.82rem; font-weight:600;
    transition:background .15s, border-color .15s, transform .12s;
}
.cj-shortcut:hover { background:var(--cj-ivory); border-color:color-mix(in srgb, var(--cj-navy) 18%, var(--cj-line)); transform:translateY(-1px); }
.cj-shortcut-ico {
    width:32px; height:32px; flex:0 0 32px;
    display:grid; place-items:center; border-radius:9px;
    background:color-mix(in srgb, var(--cj-navy) 7%, var(--cj-ivory));
    color:var(--cj-navy); font-size:.78rem;
}

/* ── Movements feed ── */
.cj-mov-list { display:flex; flex-direction:column; }
.cj-cut-divider {
    position:relative;
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    align-items:center;
    gap:12px;
    margin:18px -20px 12px;
    padding:14px 18px 14px 54px;
    border-top:2px solid color-mix(in srgb, var(--cj-navy) 36%, var(--cj-line));
    border-bottom:1px solid color-mix(in srgb, var(--cj-navy) 10%, var(--cj-line));
    border-radius:0;
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--cj-navy) 9%, var(--cj-surface)), var(--cj-surface) 68%),
        var(--cj-surface);
}
.cj-cut-divider.is-current {
    border-top-color:color-mix(in srgb, var(--cj-green) 50%, var(--cj-line));
    background:
        linear-gradient(90deg, color-mix(in srgb, var(--cj-green) 10%, var(--cj-surface)), var(--cj-surface) 68%),
        var(--cj-surface);
}
.cj-cut-mark {
    position:absolute;
    left:18px;
    top:50%;
    width:24px;
    height:24px;
    display:grid;
    place-items:center;
    transform:translateY(-50%);
    border-radius:8px;
    color:#fff;
    background:var(--cj-navy);
    box-shadow:0 0 0 4px color-mix(in srgb, var(--cj-navy) 9%, transparent);
    font-size:.66rem;
}
.cj-cut-divider.is-current .cj-cut-mark {
    background:var(--cj-green);
    box-shadow:0 0 0 4px color-mix(in srgb, var(--cj-green) 12%, transparent);
}
.cj-cut-info { min-width:0; flex:1; }
.cj-cut-eyebrow {
    display:flex;
    align-items:center;
    gap:6px;
    margin-bottom:4px;
    font-size:.62rem;
    font-weight:700;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--cj-navy);
}
.cj-cut-divider.is-current .cj-cut-eyebrow { color:var(--cj-green); }
.cj-cut-title {
    font-size:.9rem;
    font-weight:700;
    color:var(--cj-text);
    line-height:1.2;
}
.cj-cut-note {
    margin-top:3px;
    font-size:.72rem;
    font-weight:600;
    color:var(--cj-muted);
    line-height:1.25;
}
.cj-cut-chip {
    justify-self:end;
    align-self:center;
    padding:5px 9px;
    border-radius:var(--cj-r-pill);
    border:1px solid color-mix(in srgb, var(--cj-navy) 16%, var(--cj-line));
    background:color-mix(in srgb, var(--cj-navy) 5%, #fff);
    color:var(--cj-navy);
    font-size:.66rem;
    font-weight:700;
    white-space:nowrap;
}
.cj-cut-divider.is-current .cj-cut-chip {
    color:var(--cj-green);
    border-color:color-mix(in srgb, var(--cj-green) 20%, var(--cj-line));
    background:color-mix(in srgb, var(--cj-green) 7%, #fff);
}
.cj-mov {
    display:grid;
    grid-template-columns:36px minmax(0,1fr) auto;
    gap:11px; align-items:start;
    padding:12px 0;
    border-bottom:1px solid var(--cj-line);
}
.cj-mov:first-child { padding-top:0; }
.cj-mov:last-child  { border-bottom:none; padding-bottom:0; }
.cj-mov.is-linked { cursor:pointer; border-radius:10px; margin:0 -8px; padding-left:8px; padding-right:8px; transition:background .15s ease; }
.cj-mov.is-linked:hover { background:color-mix(in srgb, var(--cj-blue, #2563EB) 6%, transparent); }
.cj-mov.is-linked:focus-visible { outline:2px solid color-mix(in srgb, var(--cj-blue, #2563EB) 45%, transparent); outline-offset:-2px; }
.cj-mov-ico {
    width:36px; height:36px;
    display:grid; place-items:center;
    border-radius:var(--cj-r-sm); font-size:.76rem;
}
.cj-mov-ico.is-income  { background:color-mix(in srgb, var(--cj-green) 11%, transparent); color:var(--cj-green); border:1px solid color-mix(in srgb, var(--cj-green) 22%, transparent); }
.cj-mov-ico.is-expense { background:color-mix(in srgb, var(--cj-red)   9%,  transparent); color:var(--cj-red);   border:1px solid color-mix(in srgb, var(--cj-red)   19%, transparent); }
.cj-mov-body { min-width:0; }
.cj-mov-title { font-size:.86rem; font-weight:600; color:var(--cj-text); line-height:1.3; }
.cj-mov-sub {
    font-size:.74rem; color:var(--cj-muted); margin-top:3px;
    display:flex; flex-wrap:wrap; gap:5px; align-items:center;
}
.cj-mov-sub span { display:flex; align-items:center; gap:4px; }
.cj-badge {
    display:inline-flex; align-items:center;
    padding:1px 7px; border-radius:var(--cj-r-pill);
    font-size:.66rem; font-weight:700;
}
.cj-badge.income  { background:color-mix(in srgb, var(--cj-green) 12%, transparent); color:var(--cj-green); }
.cj-badge.expense { background:color-mix(in srgb, var(--cj-red)   10%, transparent); color:var(--cj-red); }
.cj-mov-amount {
    font-family:var(--cj-serif);
    font-size:1rem; font-weight:600;
    font-variant-numeric:tabular-nums; white-space:nowrap;
    padding-top:3px;
}
.cj-mov-amount.is-income  { color:var(--cj-green); }
.cj-mov-amount.is-expense { color:var(--cj-red); }

/* ── Empty state ── */
.cj-empty {
    display:flex; flex-direction:column; align-items:center;
    justify-content:center; gap:10px; min-height:160px;
    text-align:center; color:var(--cj-muted); font-size:.88rem;
}
.cj-empty i { font-size:1.5rem; opacity:.28; display:block; }

/* ── Categories breakdown ── */
.cj-cats { display:grid; grid-template-columns:repeat(auto-fit, minmax(220px,1fr)); gap:16px; }
.cj-cat-hd {
    font-size:.68rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.07em; color:var(--cj-muted); margin:0 0 10px;
    display:flex; align-items:center; gap:6px;
}
.cj-cat-hd::before { content:''; display:block; width:8px; height:8px; border-radius:50%; }
.cj-cat-hd.income::before  { background:var(--cj-green); }
.cj-cat-hd.expense::before { background:var(--cj-red); }
.cj-cat-list { display:flex; flex-direction:column; gap:7px; }
.cj-cat-item {
    display:flex; align-items:center; gap:9px;
    padding:9px 12px;
    border-radius:var(--cj-r-sm);
    border:1px solid var(--cj-line);
    background:color-mix(in srgb, var(--cj-gold) 2%, var(--cj-surface));
}
.cj-cat-ico { width:28px; height:28px; flex:0 0 28px; display:grid; place-items:center; border-radius:8px; font-size:.72rem; }
.cj-cat-info { flex:1; min-width:0; }
.cj-cat-name  { font-size:.8rem; font-weight:600; color:var(--cj-text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cj-cat-count { font-size:.7rem; color:var(--cj-muted); }
.cj-cat-total { font-size:.86rem; font-weight:700; font-variant-numeric:tabular-nums; white-space:nowrap; }

/* ── Modal CSS (preserved) ── */
#modalIngreso, #modalGasto {
    --cash-modal-surface:var(--brand-surface, #FFFEFB);
    --cash-modal-surface-soft:var(--brand-surface-soft, #F7F3EA);
    --cash-modal-border:color-mix(in srgb, var(--brand-border, #DED8CC) 82%, transparent);
    --cash-modal-text:var(--brand-text, #202421);
    --cash-modal-muted:var(--brand-muted, #68706A);
    --cash-modal-action:var(--brand-action-bg, #223126);
    --cash-modal-action-hover:var(--brand-action-bg-hover, #17221B);
    --cash-modal-on-action:var(--brand-action-text, #FFFEFB);
    --cash-modal-income:#14784B;
    --cash-modal-expense:#A33A31;
    --cash-ease:cubic-bezier(.22,1,.36,1);
    --cash-sk:color-mix(in srgb, var(--cash-modal-muted) 20%, var(--cash-modal-surface-soft));
    background:rgba(16,20,18,.68) !important;
    backdrop-filter:none !important;
    overflow-y:auto; padding:16px;
}
#modalIngreso .cash-modal-shell, #modalGasto .cash-modal-shell {
    min-height:100%; display:grid; place-items:center;
}
#modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog {
    width:min(100%, 640px) !important;
    max-height:calc(100svh - 32px);
    display:flex; flex-direction:column; overflow:hidden;
    border-radius:18px !important;
    background:var(--cash-modal-surface) !important;
    border:1px solid var(--cash-modal-border) !important;
    box-shadow:0 30px 80px -44px rgba(10,13,12,.7) !important;
    color:var(--cash-modal-text);
}
#modalIngreso .cash-modal-header, #modalGasto .cash-modal-header {
    display:flex; align-items:center; justify-content:space-between;
    gap:18px; padding:18px 20px;
    background:linear-gradient(180deg, var(--cash-modal-surface), var(--cash-modal-surface-soft)) !important;
    border-bottom:1px solid var(--cash-modal-border);
}
#modalIngreso .cash-modal-heading, #modalGasto .cash-modal-heading {
    min-width:0; display:flex; align-items:center; gap:12px;
}
#modalIngreso .cash-modal-icon, #modalGasto .cash-modal-icon {
    width:42px; height:42px; flex:0 0 42px;
    display:grid; place-items:center; border-radius:12px;
    background:color-mix(in srgb, var(--cash-modal-action) 10%, var(--cash-modal-surface));
    color:var(--cash-modal-action);
    border:1px solid color-mix(in srgb, var(--cash-modal-action) 18%, transparent);
}
#modalIngreso .cash-modal-dialog.is-income .cash-modal-icon {
    background:color-mix(in srgb, var(--cash-modal-income) 11%, var(--cash-modal-surface));
    color:var(--cash-modal-income);
    border-color:color-mix(in srgb, var(--cash-modal-income) 24%, transparent);
}
#modalGasto .cash-modal-dialog.is-expense .cash-modal-icon {
    background:color-mix(in srgb, var(--cash-modal-expense) 10%, var(--cash-modal-surface));
    color:var(--cash-modal-expense);
    border-color:color-mix(in srgb, var(--cash-modal-expense) 22%, transparent);
}
#modalIngreso .cash-modal-kicker, #modalGasto .cash-modal-kicker {
    margin:0 0 3px; color:var(--cash-modal-muted);
    font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase;
}
#modalIngreso .cash-modal-title, #modalGasto .cash-modal-title {
    margin:0; color:var(--cash-modal-text) !important;
    font-size:18px; line-height:1.2; font-weight:850;
}
#modalIngreso .cash-modal-close, #modalGasto .cash-modal-close {
    width:38px; height:38px; flex:0 0 38px;
    display:grid; place-items:center; border-radius:10px;
    color:var(--cash-modal-muted) !important;
    background:var(--cash-modal-surface) !important;
    border:1px solid var(--cash-modal-border) !important;
    transition:background .18s, color .18s;
}
#modalIngreso .cash-modal-close:hover, #modalGasto .cash-modal-close:hover {
    color:var(--cash-modal-text) !important;
    background:color-mix(in srgb, var(--cash-modal-action) 7%, var(--cash-modal-surface)) !important;
}
#modalIngreso .cash-modal-form, #modalGasto .cash-modal-form {
    min-height:0; overflow-y:auto;
    padding:20px 22px 0 !important;
    background:var(--cash-modal-surface);
}
#modalIngreso .cash-modal-fields, #modalGasto .cash-modal-fields { display:grid; gap:14px; }
#modalIngreso .cash-modal-grid, #modalGasto .cash-modal-grid {
    display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px;
}
#modalIngreso .cash-field, #modalGasto .cash-field { min-width:0; display:grid; gap:7px; }
#modalIngreso .cash-field.hidden, #modalGasto .cash-field.hidden { display:none !important; }
#modalIngreso .modal-label, #modalGasto .modal-label, #modalGasto .modal-label-red {
    margin:0; color:var(--cash-modal-text) !important;
    font-size:12px; font-weight:800; line-height:1.2;
}
#modalIngreso .modal-label span, #modalGasto .modal-label span { color:var(--cash-modal-expense) !important; }
#modalIngreso .modal-input, #modalGasto .modal-input, #modalGasto .modal-input-red {
    width:100%; min-height:46px;
    border-radius:11px !important;
    border:1px solid var(--cash-modal-border) !important;
    background:color-mix(in srgb, var(--cash-modal-surface-soft) 62%, var(--cash-modal-surface)) !important;
    color:var(--cash-modal-text) !important;
    padding:11px 13px !important; font-size:14px; line-height:1.35;
    box-shadow:none !important; outline:none !important;
}
#modalIngreso textarea.modal-input, #modalGasto textarea.modal-input { min-height:82px; resize:vertical; }
#modalIngreso .modal-input::placeholder, #modalGasto .modal-input::placeholder {
    color:color-mix(in srgb, var(--cash-modal-muted) 72%, transparent);
}
#modalIngreso .modal-input:focus, #modalGasto .modal-input:focus {
    background:var(--cash-modal-surface) !important;
    border-color:color-mix(in srgb, var(--cash-modal-action) 68%, var(--cash-modal-border)) !important;
    box-shadow:0 0 0 3px color-mix(in srgb, var(--cash-modal-action) 18%, transparent) !important;
}
#modalIngreso .modal-input.modal-input-error, #modalGasto .modal-input.modal-input-error {
    border-color:#B4392B !important;
    background:#FFF7F6 !important;
}
#modalIngreso .cash-form-error, #modalGasto .cash-form-error {
    display:block;
    color:#B4392B;
    font-size:12px;
    font-weight:800;
    line-height:1.35;
}
#modalIngreso .cash-error-summary, #modalGasto .cash-error-summary {
    padding:11px 13px;
    border:1px solid #F0B8AE;
    border-radius:11px;
    background:#FFF7F6;
    color:#9E2A1D;
    font-size:13px;
    font-weight:800;
    line-height:1.35;
}
#modalIngreso .cash-money-field, #modalGasto .cash-money-field { position:relative; }
#modalIngreso .cash-money-prefix, #modalGasto .cash-money-prefix {
    position:absolute; left:13px; top:50%; transform:translateY(-50%);
    color:var(--cash-modal-muted); font-size:14px; font-weight:850; pointer-events:none;
}
#modalIngreso .cash-money-field .modal-input, #modalGasto .cash-money-field .modal-input {
    padding-left:30px !important; font-size:16px; font-weight:850; font-variant-numeric:tabular-nums;
}
#modalIngreso .cash-modal-actions, #modalGasto .cash-modal-actions {
    position:sticky; bottom:0;
    display:flex; justify-content:flex-end; gap:10px;
    margin:18px -22px 0; padding:16px 22px 18px;
    background:linear-gradient(180deg, color-mix(in srgb, var(--cash-modal-surface) 12%, transparent), var(--cash-modal-surface) 32%);
    border-top:1px solid var(--cash-modal-border);
}
#modalIngreso .cash-modal-btn, #modalGasto .cash-modal-btn {
    min-height:42px;
    display:inline-flex; align-items:center; justify-content:center; gap:8px;
    padding:0 16px; border-radius:11px;
    font-size:13px; font-weight:850; line-height:1;
    transition:transform .18s, background .18s, border-color .18s;
}
#modalIngreso .cash-modal-btn.secondary, #modalGasto .cash-modal-btn.secondary {
    color:var(--cash-modal-text) !important;
    background:var(--cash-modal-surface) !important;
    border:1px solid var(--cash-modal-border) !important;
}
#modalIngreso .cash-modal-btn.secondary:hover, #modalGasto .cash-modal-btn.secondary:hover {
    background:var(--cash-modal-surface-soft) !important;
}
#modalIngreso .cash-modal-btn.primary {
    color:var(--cash-modal-on-action) !important;
    background:var(--cash-modal-action) !important;
    border:1px solid var(--cash-modal-action) !important;
}
#modalIngreso .cash-modal-btn.primary:hover {
    background:var(--cash-modal-action-hover) !important;
    border-color:var(--cash-modal-action-hover) !important;
}
#modalGasto .cash-modal-btn.primary {
    color:#FFF7F5 !important;
    background:var(--cash-modal-expense) !important;
    border:1px solid color-mix(in srgb, var(--cash-modal-expense) 88%, #4F1714) !important;
}
#modalGasto .cash-modal-btn.primary:hover {
    background:color-mix(in srgb, var(--cash-modal-expense) 88%, #4F1714) !important;
}

/* ── Responsive ── */
@media (max-width:1100px) {
    .cj-grid { grid-template-columns:1fr; }
    .cj-kpis { grid-template-columns:repeat(2, minmax(0,1fr)); }
}

/* tablet: los botones bajan debajo del bloque informativo */
@media (max-width:768px) {
    .cj-resh {
        padding:24px 24px;
        gap:20px;
    }
    .cj-resh-acts {
        flex-direction:row;
        flex-wrap:wrap;
        flex:1 1 100%;
        min-width:0;
    }
    .cj-act-btn { flex:1 1 auto; min-width:0; }
}

@media (max-width:640px) {
    .cj-inner  { padding:14px 14px 48px; }
    .cj-topbar { padding:11px 14px; }
    .cj-resh   { padding:20px 20px; gap:16px; }
    .cj-resh-name { font-size:clamp(1.4rem,5.5vw,1.8rem); }
    .cj-resh-hotel { margin-bottom:12px; }
    .cj-resh-meta { gap:5px; }
    .cj-resh-acts { flex-direction:row; flex-wrap:wrap; }
    .cj-act-btn   { min-height:38px; font-size:.8rem; }
    .cj-card-head {
        display:grid;
        grid-template-columns:36px minmax(0, 1fr);
        align-items:center;
        gap:8px 12px;
        padding:14px 16px;
    }
    .cj-card-head h2 { font-size:1.05rem; }
    .cj-card-tail {
        grid-column:2 / -1;
        width:100%;
        margin-left:0;
        justify-content:flex-start;
    }
    .cj-card-tail a,
    .cj-card-tail span {
        width:fit-content;
        max-width:100%;
        white-space:normal;
        overflow-wrap:anywhere;
        line-height:1.3;
    }
    .cj-card-body { padding:16px; }
    .cj-kpis      { grid-template-columns:repeat(2, minmax(0,1fr)); }
    .cj-cats      { grid-template-columns:1fr; }
    .cj-shortcuts { grid-template-columns:1fr; }
    .cj-cut-divider {
        grid-template-columns:1fr;
        align-items:start;
        gap:8px;
        margin:16px -16px 10px;
        padding:12px 14px 12px 46px;
    }
    .cj-cut-mark { left:14px; width:22px; height:22px; font-size:.62rem; }
    .cj-cut-eyebrow { font-size:.58rem; letter-spacing:.1em; }
    .cj-cut-title { font-size:.82rem; }
    .cj-cut-note { font-size:.68rem; }
    .cj-cut-chip { justify-self:start; padding:4px 8px; font-size:.62rem; }
    #modalIngreso, #modalGasto { padding:10px; }
    #modalIngreso .cash-modal-shell, #modalGasto .cash-modal-shell { place-items:end center; }
    #modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog {
        max-height:calc(100svh - 20px); border-radius:16px !important;
    }
    #modalIngreso .cash-modal-header, #modalGasto .cash-modal-header { padding:16px; }
    #modalIngreso .cash-modal-form, #modalGasto .cash-modal-form { padding:16px 16px 0 !important; }
    #modalIngreso .cash-modal-grid, #modalGasto .cash-modal-grid { grid-template-columns:1fr; }
    #modalIngreso .cash-modal-actions, #modalGasto .cash-modal-actions { margin-inline:-16px; padding:14px 16px 16px; }
    #modalIngreso .cash-modal-btn, #modalGasto .cash-modal-btn { flex:1; }
}

/* ══ Deleite Sereno: entrada del modal + skeleton de carga ══ */
/* Fondo oscuro: aparece con un desvanecido suave */
#modalIngreso, #modalGasto {
    opacity:0;
    transition:opacity .26s var(--cash-ease);
}
#modalIngreso.is-open, #modalGasto.is-open { opacity:1; }

/* Diálogo: entra con un "pop" (leve subida + escala) en escritorio */
#modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog {
    position:relative;
    opacity:0;
    transform:translateY(18px) scale(.965);
    transform-origin:center bottom;
    transition:opacity .3s ease, transform .42s var(--cash-ease);
    will-change:transform, opacity;
}
#modalIngreso.is-open .cash-modal-dialog, #modalGasto.is-open .cash-modal-dialog {
    opacity:1;
    transform:translateY(0) scale(1);
}

/* Skeleton: cubre el diálogo mientras "carga" y se desvanece al revelar el form */
#modalIngreso .cash-modal-skeleton, #modalGasto .cash-modal-skeleton {
    position:absolute; inset:0; z-index:5;
    display:flex; flex-direction:column; gap:14px;
    padding:18px 20px 20px;
    background:var(--cash-modal-surface);
    border-radius:inherit;
    opacity:1;
    transition:opacity .34s ease;
}
#modalIngreso .cash-modal-dialog:not(.is-loading) .cash-modal-skeleton,
#modalGasto  .cash-modal-dialog:not(.is-loading) .cash-modal-skeleton {
    opacity:0; pointer-events:none;
}
#modalIngreso .cash-sk-head, #modalGasto .cash-sk-head {
    display:flex; align-items:center; gap:12px; margin-bottom:4px;
}
#modalIngreso .cash-sk-chip, #modalGasto .cash-sk-chip {
    width:42px; height:42px; flex:0 0 42px; border-radius:12px;
}
#modalIngreso .cash-sk-heading, #modalGasto .cash-sk-heading {
    flex:1; min-width:0; display:grid; gap:7px;
}
#modalIngreso .cash-sk-field, #modalGasto .cash-sk-field { display:grid; gap:8px; }
#modalIngreso .cash-sk-row, #modalGasto .cash-sk-row {
    display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:14px;
}
#modalIngreso .cash-sk-actions, #modalGasto .cash-sk-actions {
    display:flex; justify-content:flex-end; gap:10px; margin-top:auto; padding-top:8px;
}
#modalIngreso .cash-sk-bar, #modalGasto .cash-sk-bar {
    position:relative; overflow:hidden; border-radius:9px;
    background:var(--cash-sk);
}
#modalIngreso .cash-sk-bar::after, #modalGasto .cash-sk-bar::after {
    content:''; position:absolute; inset:0; transform:translateX(-100%);
    background:linear-gradient(90deg, transparent, color-mix(in srgb, #fff 78%, transparent), transparent);
    animation:cashSkShimmer 1.25s ease-in-out infinite;
}
#modalIngreso .cash-sk-bar.sk-label, #modalGasto .cash-sk-bar.sk-label { height:11px; width:38%; }
#modalIngreso .cash-sk-bar.sk-label.sk-sm, #modalGasto .cash-sk-bar.sk-label.sk-sm { width:26%; }
#modalIngreso .cash-sk-bar.sk-input, #modalGasto .cash-sk-bar.sk-input { height:46px; }
#modalIngreso .cash-sk-bar.sk-area, #modalGasto .cash-sk-bar.sk-area { height:78px; }
#modalIngreso .cash-sk-bar.sk-title, #modalGasto .cash-sk-bar.sk-title { height:15px; width:62%; }
#modalIngreso .cash-sk-bar.sk-kicker, #modalGasto .cash-sk-bar.sk-kicker { height:9px; width:44%; }
#modalIngreso .cash-sk-bar.sk-btn, #modalGasto .cash-sk-bar.sk-btn { height:42px; width:104px; border-radius:11px; }
@keyframes cashSkShimmer { 100% { transform:translateX(100%); } }

/* Móvil: el diálogo entra como hoja inferior (sube desde abajo) + agarradera */
@media (max-width:640px) {
    #modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog {
        opacity:1;
        transform:translateY(100%);
        transition:transform .4s var(--cash-ease);
    }
    #modalIngreso.is-open .cash-modal-dialog, #modalGasto.is-open .cash-modal-dialog {
        transform:translateY(0);
    }
    #modalIngreso .cash-modal-dialog::before, #modalGasto .cash-modal-dialog::before {
        content:''; position:absolute; top:7px; left:50%; transform:translateX(-50%);
        width:42px; height:4px; border-radius:999px; z-index:6;
        background:color-mix(in srgb, var(--cash-modal-muted) 42%, transparent);
    }
    #modalIngreso .cash-modal-header, #modalGasto .cash-modal-header { padding-top:20px; }
    #modalIngreso .cash-modal-skeleton, #modalGasto .cash-modal-skeleton { padding-top:22px; }
}

/* Respeta a quien prefiere menos movimiento */
@media (prefers-reduced-motion: reduce) {
    #modalIngreso, #modalGasto,
    #modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog,
    #modalIngreso .cash-modal-skeleton, #modalGasto .cash-modal-skeleton {
        transition-duration:.01ms !important;
    }
    #modalIngreso .cash-modal-dialog, #modalGasto .cash-modal-dialog {
        transform:none !important;
    }
    #modalIngreso .cash-sk-bar::after, #modalGasto .cash-sk-bar::after {
        animation:none !important;
    }
}
</style>

<?php
// Distribución de ingresos por método de pago
$cash_ing_efectivo      = (float) ($resumen['ingreso_neto']['efectivo']['total'] ?? ($resumen['ingresos']['efectivo']['total'] ?? 0));
$cash_ing_tarjeta       = (float) ($resumen['ingreso_neto']['tarjeta']['total'] ?? ($resumen['ingresos']['tarjeta']['total'] ?? 0));
$cash_ing_transferencia = (float) ($resumen['ingreso_neto']['transferencia']['total'] ?? ($resumen['ingresos']['transferencia']['total'] ?? 0));
$cash_ing_total_metodos = $cash_ing_efectivo + $cash_ing_tarjeta + $cash_ing_transferencia;
$cash_ing_bruto_total   = (float) ($resumen['ingresos']['total'] ?? 0);
$cash_reversos_total    = (float) ($resumen['reversos']['total'] ?? 0);
$cash_gastos_reales_total = (float) ($resumen['gastos_reales']['total'] ?? ($resumen['gastos']['total'] ?? 0));
$cash_balance_operativo = (float) ($resumen['balance_operativo'] ?? ($cash_ing_total_metodos - $cash_gastos_reales_total));

$cash_pct_efectivo = $cash_pct_tarjeta = $cash_pct_transferencia = 0;
if ($cash_ing_total_metodos > 0) {
    $cash_pct_efectivo      = round(($cash_ing_efectivo / $cash_ing_total_metodos) * 100, 1);
    $cash_pct_tarjeta       = round(($cash_ing_tarjeta  / $cash_ing_total_metodos) * 100, 1);
    $cash_pct_transferencia = max(0, round(100 - $cash_pct_efectivo - $cash_pct_tarjeta, 1));
}

$cash_balance_general      = (float) ($resumen['balance_general'] ?? 0);
$cash_total_movimientos    = (int)   ($resumen['total_movimientos'] ?? 0);
$cash_fecha_apertura_label = !empty($corte['fecha_apertura'])
    ? date('d/m/Y H:i', strtotime($corte['fecha_apertura']))
    : 'Sin fecha';
$cash_hora_apertura_label  = !empty($corte['fecha_apertura'])
    ? date('H:i', strtotime($corte['fecha_apertura']))
    : '--:--';
$cash_caja_nombre   = $caja['nombre'] ?? 'Caja';
$cash_responsable   = $corte['usuario_apertura'] ?? 'Responsable no asignado';
$cash_hotel_nombre  = function_exists('current_hotel_display_name') ? current_hotel_display_name() : 'Hotel';
$cash_methods = [
    'efectivo'      => ['label' => 'Efectivo',      'icon' => 'money-bill-wave', 'note' => 'Afecta caja física'],
    'tarjeta'       => ['label' => 'Tarjeta',       'icon' => 'credit-card',     'note' => 'Cobro bancario'],
    'transferencia' => ['label' => 'Transferencia', 'icon' => 'exchange-alt',    'note' => 'Depósito o SPEI'],
];
?>

<div class="cj-page">

    <!-- ── Topbar ── -->
    <nav class="cj-topbar">
        <?php $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a href="<?= back_url('dashboard') ?>" class="cj-back ms-back-legacy" title="Volver">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>
        <ol class="cj-crumbs">
            <li><a href="<?= url('dashboard') ?>">Inicio</a></li>
            <li><?= htmlspecialchars($cash_hotel_nombre) ?></li>
            <li>Caja</li>
        </ol>
        <div class="cj-topbar-acts">
            <?php if (user_role() == 'gerente'): ?>
            <a href="<?= url('caja/categorias') ?>" class="cj-btn-ghost">
                <i class="fas fa-tags"></i> Conceptos
            </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- ── Inner container ── -->
    <div class="cj-inner">

        <!-- ── Header card (resh) ── -->
        <div class="cj-resh">
            <div class="cj-resh-info">
                <div class="cj-resh-state">
                    <span class="cj-resh-dot"></span>
                    Caja abierta
                </div>
                <h1 class="cj-resh-name"><?= htmlspecialchars($cash_caja_nombre) ?></h1>
                <p class="cj-resh-hotel"><?= htmlspecialchars($cash_hotel_nombre) ?></p>
                <div class="cj-resh-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($cash_responsable) ?></span>
                    <span><i class="fas fa-clock"></i> Apertura <?= htmlspecialchars($cash_hora_apertura_label) ?></span>
                    <span><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($cash_fecha_apertura_label) ?></span>
                </div>
            </div>
            <div class="cj-resh-acts">
                <button id="cop-ancla-ingreso" type="button" onclick="mostrarModalIngreso()" class="cj-act-btn cj-act-income">
                    <i class="fas fa-plus"></i> Registrar Ingreso
                </button>
                <button id="cop-ancla-gasto" type="button" onclick="mostrarModalGasto()" class="cj-act-btn cj-act-expense">
                    <i class="fas fa-minus"></i> Registrar Gasto
                </button>
                <a id="cop-ancla-corte" href="<?= url('caja/corte') ?>" class="cj-act-btn cj-act-corte">
                    <i class="fas fa-scissors"></i> Hacer Corte
                </a>
            </div>
        </div><!-- /.cj-resh -->

        <!-- ── Two-col grid ── -->
        <div class="cj-grid">

            <!-- Left column -->
            <div class="cj-col">

                <!-- KPI card -->
                <div class="cj-card">
                    <div class="cj-card-head">
                        <div class="cj-card-ico"><i class="fas fa-chart-pie"></i></div>
                        <h2>Resumen del turno</h2>
                        <div class="cj-card-tail">
                            <span><?= $cash_total_movimientos ?> movimiento<?= $cash_total_movimientos !== 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    <div class="cj-card-body">
                        <div class="cj-kpis">
                            <div class="cj-kpi">
                                <span class="cj-kpi-label">Monto inicial</span>
                                <strong class="cj-kpi-value">$<?= number_format($resumen['monto_inicial'] ?? 0, 2) ?></strong>
                            </div>
                            <div class="cj-kpi">
                                <span class="cj-kpi-label">Balance del corte</span>
                                <strong class="cj-kpi-value <?= $cash_balance_operativo >= 0 ? 'is-income' : 'is-expense' ?>">
                                    <?= $cash_balance_operativo >= 0 ? '+' : '-' ?>$<?= number_format(abs($cash_balance_operativo), 2) ?>
                                </strong>
                            </div>
                            <div class="cj-kpi">
                                <span class="cj-kpi-label">Devuelto/cancelado</span>
                                <strong class="cj-kpi-value is-expense">-$<?= number_format($cash_reversos_total, 2) ?></strong>
                            </div>
                            <div class="cj-kpi">
                                <span class="cj-kpi-label">Gastos del hotel</span>
                                <strong class="cj-kpi-value is-expense">-$<?= number_format($cash_gastos_reales_total, 2) ?></strong>
                            </div>
                            <div class="cj-kpi">
                                <span class="cj-kpi-label">Efectivo en caja</span>
                                <strong class="cj-kpi-value <?= ($resumen['efectivo_en_caja'] ?? 0) >= 0 ? 'is-pos' : 'is-neg' ?>">
                                    $<?= number_format(abs($resumen['efectivo_en_caja'] ?? 0), 2) ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment methods card -->
                <div class="cj-card">
                    <div class="cj-card-head">
                        <div class="cj-card-ico"><i class="fas fa-credit-card"></i></div>
                        <h2>Por forma de pago</h2>
                        <div class="cj-card-tail">
                            <span>Entro $<?= number_format($cash_ing_bruto_total, 2) ?> &middot; Devuelto/cancelado $<?= number_format($cash_reversos_total, 2) ?></span>
                        </div>
                    </div>
                    <div class="cj-card-body">
                        <div class="cj-methods">
                            <?php foreach ($cash_methods as $key => $method):
                                $pct_var = "cash_pct_{$key}";
                                $pct     = max(0, min(100, $$pct_var ?? 0));
                                $total   = (float) ($resumen['ingreso_neto'][$key]['total'] ?? ($resumen['ingresos'][$key]['total'] ?? 0));
                            ?>
                            <div class="cj-method">
                                <div class="cj-method-ico m-<?= $key ?>">
                                    <i class="fas fa-<?= $method['icon'] ?>"></i>
                                </div>
                                <div class="cj-method-info">
                                    <div class="cj-method-name"><?= htmlspecialchars($method['label']) ?></div>
                                    <div class="cj-method-note"><?= htmlspecialchars($method['note']) ?></div>
                                    <div class="cj-method-bar">
                                        <div class="cj-method-fill m-<?= $key ?>" style="width:<?= $pct ?>%"></div>
                                    </div>
                                    <div class="cj-method-pct"><?= $pct ?>% del dinero cobrado</div>
                                </div>
                                <div class="cj-method-amount"><?= $total < 0 ? '-' : '' ?>$<?= number_format(abs($total), 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Categories card -->
                <div class="cj-card">
                    <div class="cj-card-head">
                        <div class="cj-card-ico"><i class="fas fa-tags"></i></div>
                        <h2>Por concepto</h2>
                    </div>
                    <div class="cj-card-body">
                        <div class="cj-cats">
                            <div>
                                <p class="cj-cat-hd income">Entradas</p>
                                <div class="cj-cat-list">
                                    <?php if (!empty($movimientos_categoria['ingresos'])): ?>
                                        <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                        <div class="cj-cat-item">
                                            <div class="cj-cat-ico"
                                                 style="background:<?= htmlspecialchars($cat['color'] ?? '#e5e7eb') ?>1a;color:<?= htmlspecialchars($cat['color'] ?? '#6b7280') ?>">
                                                <i class="fas fa-<?= htmlspecialchars($cat['icono'] ?? 'circle') ?>"></i>
                                            </div>
                                            <div class="cj-cat-info">
                                                <div class="cj-cat-name"><?= htmlspecialchars(cj_finance_label($cat['categoria'] ?? '')) ?></div>
                                                <div class="cj-cat-count"><?= $cat['cantidad'] ?? 0 ?> mov.</div>
                                            </div>
                                            <div class="cj-cat-total" style="color:var(--cj-green)">
                                                $<?= number_format($cat['total'] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="cj-empty" style="min-height:70px"><i class="fas fa-inbox"></i>Sin ingresos</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <p class="cj-cat-hd expense">Devuelto/cancelado</p>
                                <div class="cj-cat-list">
                                    <?php if (!empty($movimientos_categoria['reversos'])): ?>
                                        <?php foreach ($movimientos_categoria['reversos'] as $cat): ?>
                                        <div class="cj-cat-item">
                                            <div class="cj-cat-ico"
                                                 style="background:<?= htmlspecialchars($cat['color'] ?? '#e5e7eb') ?>1a;color:<?= htmlspecialchars($cat['color'] ?? '#6b7280') ?>">
                                                <i class="fas fa-<?= htmlspecialchars($cat['icono'] ?? 'circle') ?>"></i>
                                            </div>
                                            <div class="cj-cat-info">
                                                <div class="cj-cat-name"><?= htmlspecialchars(cj_finance_label($cat['categoria'] ?? '')) ?></div>
                                                <div class="cj-cat-count"><?= $cat['cantidad'] ?? 0 ?> mov.</div>
                                            </div>
                                            <div class="cj-cat-total" style="color:var(--cj-red)">
                                                $<?= number_format($cat['total'] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="cj-empty" style="min-height:70px"><i class="fas fa-inbox"></i>Sin dinero devuelto</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <p class="cj-cat-hd expense">Gastos del hotel</p>
                                <div class="cj-cat-list">
                                    <?php if (!empty($movimientos_categoria['gastos_reales'])): ?>
                                        <?php foreach ($movimientos_categoria['gastos_reales'] as $cat): ?>
                                        <div class="cj-cat-item">
                                            <div class="cj-cat-ico"
                                                 style="background:<?= htmlspecialchars($cat['color'] ?? '#e5e7eb') ?>1a;color:<?= htmlspecialchars($cat['color'] ?? '#6b7280') ?>">
                                                <i class="fas fa-<?= htmlspecialchars($cat['icono'] ?? 'circle') ?>"></i>
                                            </div>
                                            <div class="cj-cat-info">
                                                <div class="cj-cat-name"><?= htmlspecialchars(cj_finance_label($cat['categoria'] ?? '')) ?></div>
                                                <div class="cj-cat-count"><?= $cat['cantidad'] ?? 0 ?> mov.</div>
                                            </div>
                                            <div class="cj-cat-total" style="color:var(--cj-red)">
                                                $<?= number_format($cat['total'] ?? 0, 2) ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="cj-empty" style="min-height:70px"><i class="fas fa-inbox"></i>Sin gastos del hotel</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shortcuts card -->
                <div class="cj-card">
                    <div class="cj-card-head">
                        <div class="cj-card-ico"><i class="fas fa-bolt"></i></div>
                        <h2>Accesos rápidos</h2>
                    </div>
                    <div class="cj-card-body">
                        <div class="cj-shortcuts">
                            <a href="<?= url('caja/movimientos') ?>" class="cj-shortcut">
                                <div class="cj-shortcut-ico"><i class="fas fa-list-ul"></i></div>
                                Movimientos
                            </a>
                            <a href="<?= url('caja/historial') ?>" class="cj-shortcut">
                                <div class="cj-shortcut-ico"><i class="fas fa-history"></i></div>
                                Historial de cortes
                            </a>
                            <a href="<?= url('caja/reporte-metodos') ?>" class="cj-shortcut">
                                <div class="cj-shortcut-ico"><i class="fas fa-chart-bar"></i></div>
                                Reporte por forma de pago
                            </a>
                            <?php if (user_role() == 'gerente'): ?>
                            <a href="<?= url('caja/categorias') ?>" class="cj-shortcut">
                                <div class="cj-shortcut-ico"><i class="fas fa-tags"></i></div>
                                Conceptos
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!-- /left col -->

            <!-- Right column: movements feed -->
            <div class="cj-col">
                <div class="cj-card">
                    <div class="cj-card-head">
                        <div class="cj-card-ico"><i class="fas fa-stream"></i></div>
                        <h2>Últimos movimientos</h2>
                        <div class="cj-card-tail">
                            <a href="<?= url('caja/movimientos') ?>">Ver todos</a>
                        </div>
                    </div>
                    <div class="cj-card-body">
                        <?php if (!empty($ultimos_movimientos)): ?>
                        <div class="cj-mov-list">
                            <?php $cj_last_cut_key = null; ?>
                            <?php foreach ($ultimos_movimientos as $mov):
                                $cj_current_cut_id = (int)($corte['id'] ?? 0);
                                $cj_mov_cut_id = (int)($mov['corte_id'] ?? 0);
                                $cj_cut_key = $cj_mov_cut_id > 0 ? (string)$cj_mov_cut_id : 'sin-corte';
                                $cj_is_current_cut = $cj_mov_cut_id > 0 && $cj_mov_cut_id === $cj_current_cut_id;
                                $cj_cut_date = !empty($mov['created_at']) ? date('d/m/Y', strtotime($mov['created_at'])) : '';
                                $cj_cut_eyebrow = $cj_is_current_cut
                                    ? 'Movimientos del corte actual'
                                    : ($cj_mov_cut_id > 0 ? 'Aqui empieza otro corte' : 'Movimientos fuera de corte');
                                $cj_cut_title = $cj_mov_cut_id > 0
                                    ? 'Corte #' . $cj_mov_cut_id
                                    : 'Movimientos sin corte asignado';
                                $cj_cut_note = $cj_is_current_cut
                                    ? 'Turno que esta abierto ahora'
                                    : 'Los siguientes movimientos pertenecen a otro turno de caja';
                                if ($cj_cut_date !== '') {
                                    $cj_cut_note .= ' - ' . $cj_cut_date;
                                }
                                $cj_cut_chip = $cj_is_current_cut ? 'Activo' : ($cj_mov_cut_id > 0 ? 'Corte anterior' : 'Sin corte');
                                $es_ingreso = ($mov['tipo'] ?? '') === 'ingreso';
                                $cj_mov_categoria = strtolower(trim((string)($mov['categoria_nombre'] ?? ($mov['categoria'] ?? ''))));
                                $cj_mov_desc = strtolower(trim((string)($mov['descripcion'] ?? '')));
                                $es_reverso = !$es_ingreso && (
                                    strpos($cj_mov_categoria, 'devoluc') === 0 ||
                                    strpos($cj_mov_categoria, 'reverso anticipo') === 0 ||
                                    strpos($cj_mov_categoria, 'reverso de anticipo') === 0 ||
                                    strpos($cj_mov_categoria, 'reversion cobro cxc') === 0 ||
                                    strpos($cj_mov_desc, 'reverso de anticipo') === 0 ||
                                    strpos($cj_mov_desc, 'reversion cobro cxc') === 0
                                );
                                $cj_mov_label = $es_ingreso ? 'Entrada' : ($es_reverso ? 'Devuelto/cancelado' : 'Gasto');
                                // Destino al clic: reservacion -> su detalle; pago laboral -> ficha del trabajador.
                                $cj_mov_href = '';
                                if (!empty($mov['reservacion_id'])) {
                                    $cj_mov_href = url('reservaciones/ver/' . (int)$mov['reservacion_id']);
                                } elseif (!empty($mov['trabajador_id'])) {
                                    $cj_mov_href = url('trabajadores/' . (int)$mov['trabajador_id']);
                                }
                            ?>
                            <?php if ($cj_cut_key !== $cj_last_cut_key): ?>
                            <div class="cj-cut-divider<?= $cj_is_current_cut ? ' is-current' : '' ?>">
                                <div class="cj-cut-mark" aria-hidden="true"><i class="fas fa-cash-register"></i></div>
                                <div class="cj-cut-info">
                                    <div class="cj-cut-eyebrow"><?= htmlspecialchars($cj_cut_eyebrow) ?></div>
                                    <div class="cj-cut-title"><?= htmlspecialchars($cj_cut_title) ?></div>
                                    <div class="cj-cut-note"><?= htmlspecialchars($cj_cut_note) ?></div>
                                </div>
                                <div class="cj-cut-chip"><?= htmlspecialchars($cj_cut_chip) ?></div>
                            </div>
                            <?php $cj_last_cut_key = $cj_cut_key; ?>
                            <?php endif; ?>
                            <div class="cj-mov<?= $cj_mov_href !== '' ? ' is-linked' : '' ?>"<?= $cj_mov_href !== '' ? ' data-href="' . htmlspecialchars($cj_mov_href, ENT_QUOTES, 'UTF-8') . '" role="link" tabindex="0" title="Abrir detalle"' : '' ?>>
                                <div class="cj-mov-ico <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                                    <i class="fas fa-<?= !empty($mov['categoria_icono']) ? htmlspecialchars($mov['categoria_icono']) : ($es_ingreso ? 'plus' : 'minus') ?>"></i>
                                </div>
                                <div class="cj-mov-body">
                                    <div class="cj-mov-title"><?= htmlspecialchars(cj_finance_sentence($mov['descripcion'] ?? '')) ?></div>
                                    <div class="cj-mov-sub">
                                        <span class="cj-badge <?= $es_ingreso ? 'income' : 'expense' ?>">
                                            <?= $cj_mov_label ?>
                                        </span>
                                        <?php if (!empty($mov['categoria_nombre'])): ?>
                                        <span><?= htmlspecialchars(cj_finance_label($mov['categoria_nombre'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($mov['metodo_pago'])): ?>
                                        <span><i class="fas fa-credit-card" style="font-size:.6rem"></i> <?= htmlspecialchars(ucfirst($mov['metodo_pago'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($mov['created_at'])): ?>
                                        <span><i class="fas fa-clock" style="font-size:.6rem"></i> <?= date('H:i', strtotime($mov['created_at'])) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($mov['reservacion_id'])): ?>
                                        <span>
                                            <a href="<?= url('reservaciones/ver/' . (int)$mov['reservacion_id']) ?>"
                                               style="color:var(--cj-blue);text-decoration:none">
                                                Res. #<?= (int)$mov['reservacion_id'] ?>
                                            </a>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="cj-mov-amount <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                                    <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="cj-empty">
                            <i class="fas fa-inbox"></i>
                            No hay movimientos en este turno
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div><!-- /right col -->

        </div><!-- /.cj-grid -->

    </div><!-- /.cj-inner -->

</div><!-- /.cj-page -->

<!-- Modal Registrar Ingreso -->
<div id="modalIngreso" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalIngresoTitulo">
    <div class="cash-modal-shell">
        <div class="cash-modal-dialog is-income">
            <div class="cash-modal-header">
                <div class="cash-modal-heading">
                    <div class="cash-modal-icon" aria-hidden="true">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div>
                        <p class="cash-modal-kicker">Movimiento de caja</p>
                        <h3 id="modalIngresoTitulo" class="cash-modal-title">Registrar Ingreso</h3>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalIngreso()" class="cash-modal-close" title="Cerrar modal" aria-label="Cerrar modal de ingreso">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('caja/ingreso') ?>" class="cash-modal-form">
                <?= csrf_field() ?>

                <div class="cash-modal-fields">
                    <?php if (caja_form_error($cajaIngresoErrors, '_global') !== ''): ?>
                        <div class="cash-error-summary ms-form-error-summary" role="alert">
                            <?= caja_form_error($cajaIngresoErrors, '_global') ?>
                        </div>
                    <?php endif; ?>
                    <!-- Categoría -->
                    <div class="cash-field">
                        <label class="modal-label">Concepto <span class="text-red-400">*</span></label>
                        <select name="categoria_id" id="categoria_ingreso" class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'categoria_id') ?>" required<?= caja_form_error_attrs($cajaIngresoErrors, 'categoria_id', 'ms-form-error-caja_ingreso_categoria') ?>>
                            <option value="">Seleccione un concepto</option>
                            <?php foreach ($categorias['ingreso'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>"<?= caja_form_selected($cajaIngresoOld, 'categoria_id', (string)($cat['id'] ?? '')) ?>>
                                    <?= htmlspecialchars(cj_finance_label($cat['nombre'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (caja_form_error($cajaIngresoErrors, 'categoria_id') !== ''): ?>
                            <span id="ms-form-error-caja_ingreso_categoria" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'categoria_id') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción -->
                    <div class="cash-field">
                        <label class="modal-label">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'descripcion') ?>"
                                  placeholder="Ej: Pago habitación 101" required<?= caja_form_error_attrs($cajaIngresoErrors, 'descripcion', 'ms-form-error-caja_ingreso_descripcion') ?>><?= caja_form_old($cajaIngresoOld, 'descripcion') ?></textarea>
                        <?php if (caja_form_error($cajaIngresoErrors, 'descripcion') !== ''): ?>
                            <span id="ms-form-error-caja_ingreso_descripcion" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'descripcion') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Monto y Método de Pago -->
                    <div class="cash-modal-grid">
                        <div class="cash-field">
                            <label class="modal-label">Monto <span class="text-red-400">*</span></label>
                            <div class="cash-money-field">
                                <span class="cash-money-prefix">$</span>
                                <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                       class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'monto') ?>" placeholder="0.00" value="<?= caja_form_old($cajaIngresoOld, 'monto') ?>" required<?= caja_form_error_attrs($cajaIngresoErrors, 'monto', 'ms-form-error-caja_ingreso_monto') ?>>
                            </div>
                            <?php if (caja_form_error($cajaIngresoErrors, 'monto') !== ''): ?>
                                <span id="ms-form-error-caja_ingreso_monto" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'monto') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="cash-field">
                            <label class="modal-label">Forma de pago <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_ingreso" class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'metodo_pago') ?>" required<?= caja_form_error_attrs($cajaIngresoErrors, 'metodo_pago', 'ms-form-error-caja_ingreso_metodo') ?>>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>"<?= caja_form_selected($cajaIngresoOld, 'metodo_pago', (string)$key) ?>><?= htmlspecialchars($metodo['label'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (caja_form_error($cajaIngresoErrors, 'metodo_pago') !== ''): ?>
                                <span id="ms-form-error-caja_ingreso_metodo" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'metodo_pago') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Referencia -->
                    <div id="referencia_ingreso_div" class="cash-field hidden">
                        <label class="modal-label">Referencia/Autorización <span class="text-red-400">*</span></label>
                        <input type="text" name="referencia" class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'referencia') ?>" placeholder="Número de referencia" value="<?= caja_form_old($cajaIngresoOld, 'referencia') ?>"<?= caja_form_error_attrs($cajaIngresoErrors, 'referencia', 'ms-form-error-caja_ingreso_referencia') ?>>
                        <?php if (caja_form_error($cajaIngresoErrors, 'referencia') !== ''): ?>
                            <span id="ms-form-error-caja_ingreso_referencia" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'referencia') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Comprobante -->
                    <div class="cash-field">
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input<?= caja_form_error_class($cajaIngresoErrors, 'comprobante') ?>" placeholder="Ej: Ticket #123" value="<?= caja_form_old($cajaIngresoOld, 'comprobante') ?>"<?= caja_form_error_attrs($cajaIngresoErrors, 'comprobante', 'ms-form-error-caja_ingreso_comprobante') ?>>
                        <?php if (caja_form_error($cajaIngresoErrors, 'comprobante') !== ''): ?>
                            <span id="ms-form-error-caja_ingreso_comprobante" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaIngresoErrors, 'comprobante') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones -->
                <div class="cash-modal-actions">
                    <button type="button" onclick="cerrarModalIngreso()"
                            class="cash-modal-btn secondary"
                            title="Cancelar ingreso">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="cash-modal-btn primary"
                            title="Guardar ingreso">
                        <i class="fas fa-save text-xs"></i>
                        Guardar Ingreso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Gasto -->
<div id="modalGasto" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="modalGastoTitulo">
    <div class="cash-modal-shell">
        <div class="cash-modal-dialog is-expense">
            <!-- Cabecera -->
            <div class="cash-modal-header">
                <div class="cash-modal-heading">
                    <div class="cash-modal-icon" aria-hidden="true">
                        <i class="fas fa-minus"></i>
                    </div>
                    <div>
                        <p class="cash-modal-kicker">Movimiento de caja</p>
                        <h3 id="modalGastoTitulo" class="cash-modal-title">Registrar Gasto</h3>
                    </div>
                </div>
                <button type="button" onclick="cerrarModalGasto()" class="cash-modal-close" title="Cerrar modal" aria-label="Cerrar modal de gasto">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('caja/gasto') ?>" class="cash-modal-form">
                <?= csrf_field() ?>

                <div class="cash-modal-fields">
                    <?php if (caja_form_error($cajaGastoErrors, '_global') !== ''): ?>
                        <div class="cash-error-summary ms-form-error-summary" role="alert">
                            <?= caja_form_error($cajaGastoErrors, '_global') ?>
                        </div>
                    <?php endif; ?>
                    <!-- Categoría -->
                    <div class="cash-field">
                        <label class="modal-label modal-label-red">Concepto <span class="text-red-400">*</span></label>
                        <select name="categoria_id" id="categoria_gasto" class="modal-input modal-input-red<?= caja_form_error_class($cajaGastoErrors, 'categoria_id') ?>" required<?= caja_form_error_attrs($cajaGastoErrors, 'categoria_id', 'ms-form-error-caja_gasto_categoria') ?>>
                            <option value="">Seleccione un concepto</option>
                            <?php foreach ($categorias['gasto'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>"<?= caja_form_selected($cajaGastoOld, 'categoria_id', (string)($cat['id'] ?? '')) ?>>
                                    <?= htmlspecialchars(cj_finance_label($cat['nombre'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (caja_form_error($cajaGastoErrors, 'categoria_id') !== ''): ?>
                            <span id="ms-form-error-caja_gasto_categoria" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'categoria_id') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Descripción -->
                    <div class="cash-field">
                        <label class="modal-label modal-label-red">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input modal-input-red<?= caja_form_error_class($cajaGastoErrors, 'descripcion') ?>"
                                  placeholder="Ej: Compra de productos de limpieza" required<?= caja_form_error_attrs($cajaGastoErrors, 'descripcion', 'ms-form-error-caja_gasto_descripcion') ?>><?= caja_form_old($cajaGastoOld, 'descripcion') ?></textarea>
                        <?php if (caja_form_error($cajaGastoErrors, 'descripcion') !== ''): ?>
                            <span id="ms-form-error-caja_gasto_descripcion" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'descripcion') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Proveedor -->
                    <div class="cash-field">
                        <label class="modal-label">Proveedor/Beneficiario</label>
                        <input type="text" name="proveedor" class="modal-input<?= caja_form_error_class($cajaGastoErrors, 'proveedor') ?>"
                               placeholder="Nombre del proveedor" value="<?= caja_form_old($cajaGastoOld, 'proveedor') ?>"<?= caja_form_error_attrs($cajaGastoErrors, 'proveedor', 'ms-form-error-caja_gasto_proveedor') ?>>
                        <?php if (caja_form_error($cajaGastoErrors, 'proveedor') !== ''): ?>
                            <span id="ms-form-error-caja_gasto_proveedor" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'proveedor') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Monto y Método de Pago -->
                    <div class="cash-modal-grid">
                        <div class="cash-field">
                            <label class="modal-label modal-label-red">Monto <span class="text-red-400">*</span></label>
                            <div class="cash-money-field">
                                <span class="cash-money-prefix">$</span>
                                <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                       class="modal-input modal-input-red<?= caja_form_error_class($cajaGastoErrors, 'monto') ?>"
                                       placeholder="0.00" value="<?= caja_form_old($cajaGastoOld, 'monto') ?>" required<?= caja_form_error_attrs($cajaGastoErrors, 'monto', 'ms-form-error-caja_gasto_monto') ?>>
                            </div>
                            <?php if (caja_form_error($cajaGastoErrors, 'monto') !== ''): ?>
                                <span id="ms-form-error-caja_gasto_monto" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'monto') ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="cash-field">
                            <label class="modal-label modal-label-red">Forma de pago <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_gasto"
                                    class="modal-input modal-input-red<?= caja_form_error_class($cajaGastoErrors, 'metodo_pago') ?>" required<?= caja_form_error_attrs($cajaGastoErrors, 'metodo_pago', 'ms-form-error-caja_gasto_metodo') ?>>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>"<?= caja_form_selected($cajaGastoOld, 'metodo_pago', (string)$key) ?>>
                                        <?= htmlspecialchars($metodo['label'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (caja_form_error($cajaGastoErrors, 'metodo_pago') !== ''): ?>
                                <span id="ms-form-error-caja_gasto_metodo" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'metodo_pago') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Referencia -->
                    <div id="referencia_gasto_div" class="cash-field hidden">
                        <label class="modal-label modal-label-red">
                            Referencia/Autorización <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="referencia" class="modal-input modal-input-red<?= caja_form_error_class($cajaGastoErrors, 'referencia') ?>"
                               placeholder="Número de referencia" value="<?= caja_form_old($cajaGastoOld, 'referencia') ?>"<?= caja_form_error_attrs($cajaGastoErrors, 'referencia', 'ms-form-error-caja_gasto_referencia') ?>>
                        <?php if (caja_form_error($cajaGastoErrors, 'referencia') !== ''): ?>
                            <span id="ms-form-error-caja_gasto_referencia" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'referencia') ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Comprobante -->
                    <div class="cash-field">
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input<?= caja_form_error_class($cajaGastoErrors, 'comprobante') ?>"
                               placeholder="Ej: Factura #ABC123" value="<?= caja_form_old($cajaGastoOld, 'comprobante') ?>"<?= caja_form_error_attrs($cajaGastoErrors, 'comprobante', 'ms-form-error-caja_gasto_comprobante') ?>>
                        <?php if (caja_form_error($cajaGastoErrors, 'comprobante') !== ''): ?>
                            <span id="ms-form-error-caja_gasto_comprobante" class="cash-form-error ms-form-field-error"><?= caja_form_error($cajaGastoErrors, 'comprobante') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones -->
                <div class="cash-modal-actions">
                    <button type="button" onclick="cerrarModalGasto()"
                            class="cash-modal-btn secondary"
                            title="Cancelar gasto">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="cash-modal-btn primary"
                            title="Guardar gasto">
                        <i class="fas fa-save text-xs"></i>
                        Guardar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Scripts del sidebar y modal-fix -->
<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>
<script src="<?= asset('js/modal-sidebar-fix.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function obtenerSidebarCaja() {
    return document.getElementById('sidebar') || document.querySelector('[data-sidebar]');
}

// ── Deleite Sereno: entrada coreografiada + skeleton de carga ──
const CASH_SKELETON_MS = 520;

function cashPrefersReduced() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
}

function cashEnsureSkeleton(dialog) {
    if (!dialog || dialog.querySelector('.cash-modal-skeleton')) {
        return;
    }
    const sk = document.createElement('div');
    sk.className = 'cash-modal-skeleton';
    sk.setAttribute('aria-hidden', 'true');
    sk.innerHTML =
        '<div class="cash-sk-head">' +
            '<div class="cash-sk-bar cash-sk-chip"></div>' +
            '<div class="cash-sk-heading">' +
                '<div class="cash-sk-bar sk-kicker"></div>' +
                '<div class="cash-sk-bar sk-title"></div>' +
            '</div>' +
        '</div>' +
        '<div class="cash-sk-field"><div class="cash-sk-bar sk-label"></div><div class="cash-sk-bar sk-input"></div></div>' +
        '<div class="cash-sk-field"><div class="cash-sk-bar sk-label sk-sm"></div><div class="cash-sk-bar sk-area"></div></div>' +
        '<div class="cash-sk-row">' +
            '<div class="cash-sk-field"><div class="cash-sk-bar sk-label"></div><div class="cash-sk-bar sk-input"></div></div>' +
            '<div class="cash-sk-field"><div class="cash-sk-bar sk-label"></div><div class="cash-sk-bar sk-input"></div></div>' +
        '</div>' +
        '<div class="cash-sk-field"><div class="cash-sk-bar sk-label sk-sm"></div><div class="cash-sk-bar sk-input"></div></div>' +
        '<div class="cash-sk-actions"><div class="cash-sk-bar sk-btn"></div><div class="cash-sk-bar sk-btn"></div></div>';
    dialog.appendChild(sk);
}

function abrirModalCaja(modalId, opts) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }
    opts = opts || {};

    const sidebar = obtenerSidebarCaja();
    if (sidebar) {
        sidebar.style.display = 'none';
    }

    const dialog = modal.querySelector('.cash-modal-dialog');
    const usarSkeleton = opts.skeleton !== false && !cashPrefersReduced();
    if (dialog) {
        if (usarSkeleton) {
            cashEnsureSkeleton(dialog);
            dialog.classList.add('is-loading');
            if (dialog._skTimer) {
                clearTimeout(dialog._skTimer);
            }
            dialog._skTimer = setTimeout(function () {
                dialog.classList.remove('is-loading');
            }, CASH_SKELETON_MS);
        } else {
            dialog.classList.remove('is-loading');
        }
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    // Fuerza el cálculo del estado inicial (oculto) y dispara la transición de entrada.
    void modal.offsetWidth;
    modal.classList.add('is-open');
}

function cerrarModalCaja(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }

    const dialog = modal.querySelector('.cash-modal-dialog');

    const finalizar = function () {
        modal.classList.add('hidden');

        const sidebar = obtenerSidebarCaja();
        if (sidebar) {
            sidebar.style.display = '';
        }

        document.body.classList.remove('overflow-hidden');

        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }

        if (dialog) {
            if (dialog._skTimer) {
                clearTimeout(dialog._skTimer);
            }
            dialog.classList.remove('is-loading');
        }
    };

    modal.classList.remove('is-open');

    if (!dialog || cashPrefersReduced()) {
        finalizar();
        return;
    }

    // Espera a que termine la transición del diálogo (o un respaldo por tiempo).
    let cerrado = false;
    const alTerminar = function (e) {
        if (e && (e.target !== dialog || (e.propertyName && e.propertyName !== 'transform'))) {
            return;
        }
        if (cerrado) {
            return;
        }
        cerrado = true;
        dialog.removeEventListener('transitionend', alTerminar);
        finalizar();
    };
    dialog.addEventListener('transitionend', alTerminar);
    setTimeout(alTerminar, 500);
}

function mostrarModalIngreso() {
    abrirModalCaja('modalIngreso');
}

function cerrarModalIngreso() {
    cerrarModalCaja('modalIngreso');
}

function mostrarModalGasto() {
    abrirModalCaja('modalGasto');
}

function cerrarModalGasto() {
    cerrarModalCaja('modalGasto');
}

// Cerrar al hacer clic en el fondo oscuro (fuera del diálogo)
function cashBindOverlayClose(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }
    modal.addEventListener('mousedown', function (e) {
        const esFondo = e.target === modal ||
            (e.target.classList && e.target.classList.contains('cash-modal-shell'));
        if (esFondo) {
            cerrarModalCaja(modalId);
        }
    });
}
cashBindOverlayClose('modalIngreso');
cashBindOverlayClose('modalGasto');

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalIngreso();
        cerrarModalGasto();
    }
});

// Mostrar/ocultar campo de referencia según método de pago
const metodoPagoIngreso = document.getElementById('metodo_pago_ingreso');
if (metodoPagoIngreso) {
    metodoPagoIngreso.addEventListener('change', function() {
        const referenciaDiv = document.getElementById('referencia_ingreso_div');
        if (!referenciaDiv) {
            return;
        }

        const referenciaInput = referenciaDiv.querySelector('input');
        if (!referenciaInput) {
            return;
        }

        if (this.value === 'tarjeta' || this.value === 'transferencia') {
            referenciaDiv.classList.remove('hidden');
            referenciaInput.setAttribute('required', 'required');
        } else {
            referenciaDiv.classList.add('hidden');
            referenciaInput.removeAttribute('required');
            referenciaInput.value = '';
        }
    });
}

const metodoPagoGasto = document.getElementById('metodo_pago_gasto');
if (metodoPagoGasto) {
    metodoPagoGasto.addEventListener('change', function() {
        const referenciaDiv = document.getElementById('referencia_gasto_div');
        if (!referenciaDiv) {
            return;
        }

        const referenciaInput = referenciaDiv.querySelector('input');
        if (!referenciaInput) {
            return;
        }

        if (this.value === 'tarjeta' || this.value === 'transferencia') {
            referenciaDiv.classList.remove('hidden');
            referenciaInput.setAttribute('required', 'required');
        } else {
            referenciaDiv.classList.add('hidden');
            referenciaInput.removeAttribute('required');
            referenciaInput.value = '';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const cajaOldOrigin = <?= json_encode($cajaOldOrigin, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const modalId = cajaOldOrigin === 'ingreso'
        ? 'modalIngreso'
        : (cajaOldOrigin === 'gasto' ? 'modalGasto' : '');

    if (!modalId) {
        return;
    }

    // Reapertura por error de validación: mostramos el contenido al instante (sin skeleton).
    abrirModalCaja(modalId, { skeleton: false });

    if (cajaOldOrigin === 'ingreso' && metodoPagoIngreso) {
        metodoPagoIngreso.dispatchEvent(new Event('change'));
    }

    if (cajaOldOrigin === 'gasto' && metodoPagoGasto) {
        metodoPagoGasto.dispatchEvent(new Event('change'));
    }

    const firstInvalid = document.querySelector(`#${modalId} [aria-invalid="true"]`);
    if (firstInvalid && typeof firstInvalid.focus === 'function') {
        firstInvalid.focus();
    }
});

// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + I para ingreso
    if ((e.ctrlKey || e.metaKey) && e.key === 'i') {
        e.preventDefault();
        mostrarModalIngreso();
    }
    // Ctrl/Cmd + G para gasto
    if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
        e.preventDefault();
        mostrarModalGasto();
    }
});

// Auto-refresh cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);

// Clic en cualquier parte del movimiento -> su destino (reservacion o ficha del trabajador).
// Respeta el enlace interno "Res. #".
(function () {
    function destino(target) {
        if (target.closest('a, button, input, textarea, select, label')) return null;
        return target.closest('.cj-mov.is-linked[data-href]');
    }
    document.addEventListener('click', function (e) {
        const row = destino(e.target);
        if (!row) return;
        const href = row.getAttribute('data-href');
        if (!href) return;
        if (e.ctrlKey || e.metaKey || e.button === 1) {
            window.open(href, '_blank', 'noopener');
        } else {
            window.location.href = href;
        }
    });
    document.addEventListener('auxclick', function (e) {
        if (e.button !== 1) return;
        const row = destino(e.target);
        if (!row) return;
        const href = row.getAttribute('data-href');
        if (href) { e.preventDefault(); window.open(href, '_blank', 'noopener'); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        const row = e.target.closest && e.target.closest('.cj-mov.is-linked[data-href]');
        if (!row || row !== e.target) return;
        e.preventDefault();
        window.location.href = row.getAttribute('data-href');
    });
})();
</script>

<?php clear_old_input(); ?>
