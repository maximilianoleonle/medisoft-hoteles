<?php
/**
 * Vista de listado de usuarios
 * Paleta hotelera boutique
 */
$esGestionHotel = $esGestionHotel ?? false;
$puedeCrearUsuarios = $puedeCrearUsuarios ?? can('usuarios.create');
$puedeEditarUsuarios = $puedeEditarUsuarios ?? can('usuarios.edit');
?>

<style>
/* ══════════════════════════════════════════
   Gestión de Usuarios
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
}

.usuarios-view { opacity:0; transition:opacity .3s ease; }
.usuarios-view.loaded { opacity:1; }

/* Page background */
.usr-bg { background: linear-gradient(145deg,#EFF4EC 0%,#E8EEE3 50%,#F4F1EC 100%); min-height:100vh; }

/* ── Hero header ─────────────────────────── */
.usr-hero {
    background: linear-gradient(135deg,#3D5234 0%,#4A6340 55%,#5C7A4E 100%);
    position:relative; overflow:hidden;
}
.usr-hero::before {
    content:''; position:absolute;
    top:-50px; right:-50px; width:240px; height:240px;
    border-radius:50%; background:rgba(200,169,106,.08); pointer-events:none;
}
.usr-hero::after {
    content:''; position:absolute;
    bottom:-70px; left:-30px; width:180px; height:180px;
    border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none;
}
.gold-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:rgba(200,169,106,.18); border:1px solid rgba(200,169,106,.35);
    color:var(--lc-gold-dark); border-radius:20px;
    padding:3px 11px; font-size:.7rem; font-weight:700;
}

/* ── New user button ─────────────────────── */
.btn-nuevo {
    display:inline-flex; align-items:center; gap:7px;
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234; padding:8px 16px; border-radius:10px;
    font-size:.82rem; font-weight:700; text-decoration:none;
    transition:box-shadow .2s, transform .2s;
    box-shadow:0 3px 10px rgba(200,169,106,.3);
}
.btn-nuevo:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(200,169,106,.4); color:#3D5234; }

/* ── Stat widgets ────────────────────────── */
.usr-widget {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; padding:20px;
    transition:transform .25s, box-shadow .25s;
    position:relative; overflow:hidden;
}
.usr-widget::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; opacity:0; transition:opacity .25s;
    background:var(--w-accent,#5C7A4E);
}
.usr-widget:hover { transform:translateY(-3px); box-shadow:0 10px 26px rgba(92,122,78,.11); }
.usr-widget:hover::after { opacity:1; }
.w-icon {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center; font-size:18px;
}

/* ── Avatar ──────────────────────────────── */
.usr-avatar {
    width:40px; height:40px; border-radius:12px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.85rem; font-weight:800;
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234; letter-spacing:.05em;
}

/* ── Table panel ─────────────────────────── */
.usr-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.usr-panel-hd {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));
    padding:14px 18px;
    display:flex; align-items:center; justify-content:space-between;
}
.usr-th {
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    padding:10px 14px; white-space:nowrap;
}
.usr-tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
.usr-tr:hover { background:#F7FCF4; }
.usr-tr:last-child { border-bottom:none; }

/* ── Role badges ─────────────────────────── */
.rol-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 10px; border-radius:20px;
    font-size:.68rem; font-weight:700;
}
.rol-admin        { background:linear-gradient(135deg,#F3E8FF,#E9D5FF); color:#7C3AED; border:1px solid #C084FC; }
.rol-recepcion    { background:linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#2563EB; border:1px solid #93C5FD; }
.rol-limpieza     { background:rgba(92,122,78,.1);                       color:#3D5234; border:1px solid rgba(92,122,78,.25); }
.rol-mantenimiento{ background:linear-gradient(135deg,#FFFBEB,#FEF3C7); color:#D97706; border:1px solid #FDE047; }
.rol-contador     { background:linear-gradient(135deg,#FEF2F2,#FEE2E2); color:#DC2626; border:1px solid #FCA5A5; }

/* ── Status badges ───────────────────────── */
.status-on  { background:rgba(16,185,129,.1); color:#065F46; border:1px solid rgba(16,185,129,.25); display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.68rem; font-weight:700; }
.status-off { background:rgba(239,68,68,.1);  color:#991B1B; border:1px solid rgba(239,68,68,.25);  display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:.68rem; font-weight:700; }

/* ── Action buttons ──────────────────────── */
.act-btn {
    width:30px; height:30px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.72rem; transition:background .15s; border:none; cursor:pointer;
    text-decoration:none;
}
.act-edit   { color:#5C7A4E; background:transparent; }
.act-edit:hover   { background:#EEF4EB; }
.act-deact  { color:#DC2626; background:transparent; }
.act-deact:hover  { background:#FEF2F2; }
.act-act    { color:#059669; background:transparent; }
.act-act:hover    { background:#ECFDF5; }

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:#5C7A4E; }

/* ── User row animation ──────────────────── */
.usr-tr { opacity:0; transform:translateX(-8px); }
.usr-tr.visible { transition:opacity .3s ease, transform .3s ease; opacity:1; transform:translateX(0); }
</style>

<style id="usuarios-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.usuarios-view {
    --user-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --user-brand-2: var(--brand-action-bg-hover, var(--brand-secondary, #0F172A));
    --user-on-brand: var(--brand-action-text, #FFFEFB);
    --user-accent: var(--brand-accent, #BD9441);
    --user-accent-dark: color-mix(in srgb, var(--user-accent) 72%, #3F2E12);
    --user-accent-soft: color-mix(in srgb, var(--user-accent) 14%, #FFFFFF);
    --user-accent-line: color-mix(in srgb, var(--user-accent) 34%, #E8DDCA);
    --user-bg: #F6F2EA;
    --user-bg-2: #FBF8F2;
    --user-surface: rgba(255,255,255,.96);
    --user-surface-warm: #FCFAF5;
    --user-border: color-mix(in srgb, var(--user-brand) 11%, #E7E1D4);
    --user-text: #1B2746;
    --user-muted: #6C7689;
    --user-success: #1E9E63;
    --user-success-soft: #E8F4ED;
    --user-danger: #B42318;
    --user-danger-soft: #FDECEC;
    --user-info: #2F77E0;
    --user-info-soft: #E8F0FC;
    --user-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --user-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--user-text);
    font-family: var(--user-sans);
}

.usr-bg {
    background:
        linear-gradient(135deg, rgba(255,255,255,.34) 0 25%, transparent 25% 50%) 0 0 / 22px 22px,
        linear-gradient(180deg, var(--user-bg-2), var(--user-bg)) !important;
}

.usr-hero {
    background: transparent !important;
    border-bottom: 1px solid var(--user-border);
    overflow: visible !important;
}
.usr-hero::before,
.usr-hero::after {
    content: none !important;
}
.usr-hero .container {
    max-width: 1680px;
    padding-top: 1.55rem;
    padding-bottom: 1.25rem;
}
.usr-hero div[style] {
    width: 46px !important;
    height: 46px !important;
    border-radius: 13px !important;
    background: linear-gradient(150deg, var(--user-brand), var(--user-brand-2)) !important;
    color: var(--user-on-brand) !important;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--user-brand) 58%, transparent) !important;
}
.usuarios-view .usr-hero div[style] i {
    color: var(--user-on-brand) !important;
}
.usr-hero h1 {
    color: var(--user-brand) !important;
    font-family: var(--user-serif);
    font-size: clamp(2rem, 3vw, 2.6rem);
    font-weight: 700;
    letter-spacing: 0;
    line-height: 1;
}
.usr-hero p {
    color: var(--user-muted) !important;
    font-weight: 500;
}
.gold-badge {
    background: var(--user-accent-soft) !important;
    border: 1px solid var(--user-accent-line) !important;
    color: var(--user-accent-dark) !important;
    border-radius: 999px !important;
    padding: 6px 11px !important;
    font-weight: 800 !important;
}

.btn-nuevo {
    min-height: 42px;
    border-radius: 11px !important;
    background: linear-gradient(135deg, var(--user-accent), var(--user-accent-dark)) !important;
    color: #FFFFFF !important;
    font-weight: 800 !important;
    box-shadow: 0 12px 24px -12px color-mix(in srgb, var(--user-accent) 70%, transparent) !important;
}
.btn-nuevo:hover {
    color: #FFFFFF !important;
    transform: translateY(-1px);
    box-shadow: 0 16px 30px -14px color-mix(in srgb, var(--user-accent) 72%, transparent) !important;
}
.btn-nuevo:focus-visible,
.act-btn:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--user-accent) 36%, transparent);
    outline-offset: 3px;
}

.usuarios-view .container.max-w-none {
    max-width: 1680px;
}
.usuarios-view .grid > .usr-widget:nth-child(1) { --w-accent: var(--user-brand) !important; }
.usuarios-view .grid > .usr-widget:nth-child(2) { --w-accent: var(--user-success) !important; }
.usuarios-view .grid > .usr-widget:nth-child(3) { --w-accent: var(--user-accent) !important; }
.usr-widget {
    background: var(--user-surface) !important;
    border: 1px solid var(--user-border) !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease !important;
}
.usr-widget::after {
    height: 1px !important;
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 44%, var(--user-border)) !important;
}
.usr-widget:hover {
    border-color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 28%, var(--user-border)) !important;
    box-shadow: 0 16px 34px -24px color-mix(in srgb, var(--w-accent, var(--user-brand)) 58%, #172033) !important;
}
.usr-widget .w-icon {
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 12%, #FFFFFF) !important;
    color: var(--w-accent, var(--user-brand)) !important;
}
.usr-widget > div:first-child > span {
    color: var(--user-brand) !important;
    font-family: var(--user-serif);
    font-size: 1.9rem !important;
    font-weight: 700 !important;
    letter-spacing: 0;
}
.usr-widget p {
    color: var(--user-muted) !important;
}
.usr-widget p:first-of-type {
    color: var(--user-muted) !important;
    letter-spacing: .07em;
}
.usr-widget p span {
    color: var(--user-brand) !important;
}

.usr-panel {
    background: var(--user-surface) !important;
    border: 1px solid var(--user-border) !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 16px 36px -26px rgba(27,39,70,.32);
}
.usr-panel-hd {
    background: var(--user-surface-warm) !important;
    border-bottom: 1px solid var(--user-border);
    padding: 15px 18px !important;
}
.usr-panel-hd div[style] {
    background: linear-gradient(150deg, var(--user-brand), var(--user-brand-2)) !important;
}
.usr-panel-hd h3 {
    color: var(--user-brand) !important;
    font-weight: 800 !important;
}
.usr-panel-hd .text-white {
    color: var(--user-brand) !important;
}
.usr-panel-hd div[style] .text-white {
    color: var(--user-on-brand) !important;
}
.usr-panel-hd > span {
    background: var(--user-accent-soft) !important;
    border: 1px solid var(--user-accent-line);
    color: var(--user-accent-dark) !important;
}

.usr-th {
    color: var(--user-muted) !important;
    background: var(--user-surface-warm);
    border-bottom: 1px solid var(--user-border);
    font-weight: 800 !important;
}
.usr-tr {
    border-bottom: 1px solid var(--user-border) !important;
    opacity: 1 !important;
    transform: none !important;
}
.usr-tr.visible {
    transition: background .16s ease !important;
    opacity: 1 !important;
    transform: none !important;
}
.usr-tr:hover {
    background: var(--user-bg-2) !important;
}
.usuarios-view .text-gray-800,
.usuarios-view .text-gray-600 {
    color: var(--user-text) !important;
}
.usuarios-view .text-gray-400,
.usuarios-view .text-gray-300 {
    color: var(--user-muted) !important;
}

.usr-avatar {
    background: linear-gradient(150deg, var(--user-brand), var(--user-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--user-brand) 58%, transparent);
}

.rol-badge,
.status-on,
.status-off {
    border-radius: 999px !important;
    font-weight: 800 !important;
    letter-spacing: .01em;
}
.rol-admin {
    background: var(--user-accent-soft) !important;
    border: 1px solid var(--user-accent-line) !important;
    color: var(--user-accent-dark) !important;
}
.rol-recepcion {
    background: var(--user-info-soft) !important;
    border: 1px solid color-mix(in srgb, var(--user-info) 24%, #FFFFFF) !important;
    color: var(--user-info) !important;
}
.rol-limpieza {
    background: var(--user-success-soft) !important;
    border: 1px solid color-mix(in srgb, var(--user-success) 24%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--user-success) 72%, #123322) !important;
}
.rol-mantenimiento {
    background: #FAF0DC !important;
    border: 1px solid color-mix(in srgb, var(--user-accent) 30%, #FFFFFF) !important;
    color: var(--user-accent-dark) !important;
}
.rol-contador {
    background: var(--user-danger-soft) !important;
    border: 1px solid color-mix(in srgb, var(--user-danger) 20%, #FFFFFF) !important;
    color: var(--user-danger) !important;
}
.status-on {
    background: var(--user-success-soft) !important;
    border: 1px solid color-mix(in srgb, var(--user-success) 24%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--user-success) 68%, #123322) !important;
}
.status-off {
    background: var(--user-danger-soft) !important;
    border: 1px solid color-mix(in srgb, var(--user-danger) 20%, #FFFFFF) !important;
    color: var(--user-danger) !important;
}

.act-btn {
    width: 32px !important;
    height: 32px !important;
    border-radius: 10px !important;
    background: var(--user-surface-warm) !important;
    border: 1px solid var(--user-border) !important;
    transition: transform .16s ease, background .16s ease, border-color .16s ease !important;
}
.act-btn:hover {
    transform: translateY(-1px);
}
.act-edit {
    color: var(--user-accent-dark) !important;
}
.act-edit:hover {
    background: var(--user-accent-soft) !important;
    border-color: var(--user-accent-line) !important;
}
.act-deact {
    color: var(--user-danger) !important;
}
.act-deact:hover {
    background: var(--user-danger-soft) !important;
    border-color: color-mix(in srgb, var(--user-danger) 22%, #FFFFFF) !important;
}
.act-act {
    color: var(--user-success) !important;
}
.act-act:hover {
    background: var(--user-success-soft) !important;
    border-color: color-mix(in srgb, var(--user-success) 24%, #FFFFFF) !important;
}
.act-btn.is-confirming {
    color: #FFFFFF !important;
    background: linear-gradient(135deg, var(--user-accent), var(--user-accent-dark)) !important;
    border-color: var(--user-accent-line) !important;
    box-shadow: 0 12px 22px -14px color-mix(in srgb, var(--user-accent) 70%, transparent);
}

.usr-inline-notice {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 16px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid var(--user-accent-line);
    background: #FFFFFF;
    color: var(--user-text);
    font-size: .84rem;
    font-weight: 750;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 30px -26px rgba(27,39,70,.28);
}

.usr-inline-notice[hidden] {
    display: none;
}

.usr-inline-notice i {
    margin-top: 2px;
    color: var(--user-accent-dark);
}

.usr-inline-notice.is-danger {
    border-color: color-mix(in srgb, var(--user-danger) 24%, #FFFFFF);
    background: var(--user-danger-soft);
    color: var(--user-danger);
}

.usr-inline-notice.is-danger i {
    color: var(--user-danger);
}

.usr-inline-notice.is-success {
    border-color: color-mix(in srgb, var(--user-success) 24%, #FFFFFF);
    background: var(--user-success-soft);
    color: color-mix(in srgb, var(--user-success) 70%, #123322);
}

.usr-inline-notice.is-success i {
    color: var(--user-success);
}

.usuarios-view .text-center.py-16 {
    background: var(--user-bg-2);
    border: 1px dashed var(--user-border);
    border-radius: 14px;
    margin: 18px;
}
.usuarios-view .text-center.py-16 h3 {
    color: var(--user-brand) !important;
}

.lc-scroll::-webkit-scrollbar-track { background: var(--user-bg-2) !important; }
.lc-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--user-brand) 24%, #D7CCBA) !important; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--user-brand) !important; }

/* Layout upgrade: staff directory with card-like rows and clearer access states. */
.usuarios-view .usr-hero {
    margin-bottom: 2px;
}

.usuarios-view .usr-hero .flex.flex-col.lg\:flex-row {
    align-items: flex-start !important;
}

.usuarios-view .container.mx-auto.px-5 > .grid:first-child {
    grid-template-columns: minmax(0, 1.2fr) minmax(0, .9fr) minmax(0, .9fr) !important;
    align-items: stretch;
}

.usuarios-view .usr-widget {
    min-height: 142px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.usuarios-view .usr-widget::before {
    content: '';
    position: absolute;
    inset: 12px 12px auto auto;
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 11%, transparent);
    pointer-events: none;
}

.usuarios-view .usr-widget > * {
    position: relative;
    z-index: 1;
}

.usuarios-view .usr-panel {
    overflow: hidden;
}

.usuarios-view .usr-panel-hd {
    min-height: 52px;
}

.usuarios-view .usr-panel-hd > div:first-child > div {
    background: color-mix(in srgb, var(--user-brand) 12%, var(--user-surface)) !important;
    border: 1px solid var(--user-border);
    color: var(--user-brand) !important;
}

.usuarios-view .usr-panel-hd > div:first-child > div i {
    color: var(--user-brand) !important;
}

.usuarios-view table {
    border-collapse: separate !important;
    border-spacing: 0 8px;
}

.usuarios-view thead tr {
    transform: translateY(4px);
}

.usuarios-view .usr-tr {
    background: var(--user-surface) !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04);
}

.usuarios-view .usr-tr td {
    border-top: 1px solid color-mix(in srgb, var(--user-border) 76%, transparent);
    border-bottom: 1px solid color-mix(in srgb, var(--user-border) 76%, transparent);
    padding-top: 12px !important;
    padding-bottom: 12px !important;
}

.usuarios-view .usr-tr td:first-child {
    border-left: 1px solid color-mix(in srgb, var(--user-border) 76%, transparent);
    border-radius: 14px 0 0 14px;
}

.usuarios-view .usr-tr td:last-child {
    border-right: 1px solid color-mix(in srgb, var(--user-border) 76%, transparent);
    border-radius: 0 14px 14px 0;
}

.usuarios-view .usr-tr:hover td {
    border-color: color-mix(in srgb, var(--user-accent) 30%, var(--user-border));
}

.usuarios-view .usr-avatar {
    width: 38px !important;
    height: 38px !important;
    border-radius: 12px !important;
    font-size: .76rem !important;
    letter-spacing: .03em;
}

.usuarios-view .usr-tr td:first-child p:first-of-type {
    font-size: .84rem !important;
    font-weight: 800 !important;
}

.usuarios-view .rol-badge,
.usuarios-view .status-on,
.usuarios-view .status-off {
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px !important;
}

.usuarios-view .act-btn {
    box-shadow: 0 1px 2px rgba(27,39,70,.05);
}

/* Color balance: neutral user data, varied initials, hotel brand as accent. */
.usuarios-view {
    --user-heading: var(--brand-text, #111827);
    --user-text: var(--brand-text, #1F2937);
    --user-muted: var(--brand-muted, #667085);
    --user-bg: #F8F5ED;
    --user-bg-2: #FBFAF6;
    --user-surface: #FFFFFF;
    --user-surface-warm: color-mix(in srgb, var(--user-accent) 3%, #FFFFFF);
    --user-border: color-mix(in srgb, var(--user-brand) 6%, #E7E1D4);
    --user-shadow: 0 1px 2px rgba(17,24,39,.035), 0 14px 30px -24px rgba(17,24,39,.34);
    color: var(--user-text);
}

.usr-bg {
    background:
        radial-gradient(circle at 9% 0%, color-mix(in srgb, var(--user-accent) 6%, transparent) 0, transparent 26%),
        radial-gradient(circle at 92% 2%, color-mix(in srgb, var(--user-brand) 4%, transparent) 0, transparent 24%),
        linear-gradient(180deg, var(--user-bg-2), #FFFFFF 46%, var(--user-bg)) !important;
}

.usuarios-view .usr-hero h1,
.usuarios-view .usr-panel-hd h3,
.usuarios-view .usr-widget > div:first-child > span,
.usuarios-view .usr-widget p span,
.usuarios-view .usr-tr td:first-child p:first-of-type,
.usuarios-view .usr-tr td:nth-child(2) p:first-child,
.usuarios-view .usr-tr td:nth-child(5) p:first-child,
.usuarios-view .text-gray-800,
.usuarios-view .text-gray-600 {
    color: var(--user-heading) !important;
}

.usuarios-view .usr-hero div[style],
.usuarios-view .usr-panel-hd div[style] {
    background: linear-gradient(150deg, var(--user-brand), var(--user-brand-2)) !important;
    color: var(--user-on-brand) !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--user-brand) 72%, #111827) !important;
}

.usuarios-view .gold-badge,
.usuarios-view .usr-panel-hd > span {
    background: var(--user-surface) !important;
    border-color: var(--user-border) !important;
    color: var(--user-heading) !important;
    box-shadow: 0 10px 22px -20px rgba(17,24,39,.45);
}

.usuarios-view .btn-nuevo {
    background: var(--user-brand) !important;
    color: var(--user-on-brand) !important;
    box-shadow: 0 12px 22px -18px rgba(17,24,39,.7) !important;
}

.usuarios-view .btn-nuevo:hover {
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--user-accent) 48%, #111827) !important;
}

.usuarios-view .usr-widget,
.usuarios-view .usr-panel,
.usuarios-view .usr-tr {
    background: var(--user-surface) !important;
    border-color: var(--user-border) !important;
    box-shadow: var(--user-shadow) !important;
}

.usuarios-view .usr-widget::after {
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 24%, var(--user-border)) !important;
}

.usuarios-view .usr-widget::before {
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 7%, transparent) !important;
}

.usuarios-view .usr-widget .w-icon {
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 8%, #FFFFFF) !important;
    border: 1px solid color-mix(in srgb, var(--w-accent, var(--user-brand)) 16%, var(--user-border));
    color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 78%, #111827) !important;
}

.usuarios-view .usr-panel-hd,
.usuarios-view .usr-th {
    background: linear-gradient(180deg, var(--user-surface), var(--user-surface-warm)) !important;
}

.usuarios-view .usr-tr:hover {
    background: color-mix(in srgb, var(--user-accent) 4%, #FFFFFF) !important;
}

.usuarios-view .usr-tr:hover td {
    border-color: color-mix(in srgb, var(--user-accent) 20%, var(--user-border)) !important;
}

.usuarios-view .usr-avatar {
    background: #EAF1F8 !important;
    color: #234C78 !important;
    border: 1px solid color-mix(in srgb, #3B6EA8 16%, #FFFFFF);
    box-shadow: none !important;
}
.usuarios-view .usr-tr:nth-child(2n) .usr-avatar {
    background: #E8F4ED !important;
    color: #276749 !important;
    border-color: color-mix(in srgb, #2F855A 16%, #FFFFFF);
}
.usuarios-view .usr-tr:nth-child(3n) .usr-avatar {
    background: #F8F0DC !important;
    color: #7C4A03 !important;
    border-color: color-mix(in srgb, #A16207 16%, #FFFFFF);
}
.usuarios-view .usr-tr:nth-child(4n) .usr-avatar {
    background: #EEF1F4 !important;
    color: #334155 !important;
    border-color: color-mix(in srgb, #64748B 18%, #FFFFFF);
}
.usuarios-view .usr-tr:nth-child(5n) .usr-avatar {
    background: color-mix(in srgb, var(--user-accent) 9%, #FFFFFF) !important;
    color: var(--user-accent-dark) !important;
    border-color: color-mix(in srgb, var(--user-accent) 16%, #FFFFFF);
}

.usuarios-view .rol-admin {
    background: #F3F0FA !important;
    color: #5B4A8F !important;
    border-color: #DED7F0 !important;
}
.usuarios-view .rol-gerente,
.usuarios-view .rol-administrador {
    background: #F3F0FA !important;
    color: #5B4A8F !important;
    border-color: #DED7F0 !important;
}
.usuarios-view .rol-recepcion {
    background: #EAF1F8 !important;
    color: #234C78 !important;
    border-color: #D6E2EF !important;
}
.usuarios-view .rol-recepcionista {
    background: #EAF1F8 !important;
    color: #234C78 !important;
    border-color: #D6E2EF !important;
}
.usuarios-view .rol-limpieza {
    background: #E8F4ED !important;
    color: #276749 !important;
    border-color: #D4E8DC !important;
}
.usuarios-view .rol-mantenimiento {
    background: #F8F0DC !important;
    color: #7C4A03 !important;
    border-color: #EFE1BA !important;
}
.usuarios-view .rol-contador {
    background: #F9ECEB !important;
    color: #9D3028 !important;
    border-color: #EFD1CE !important;
}

.usuarios-view .status-on {
    background: #E8F4ED !important;
    color: #276749 !important;
    border-color: #D4E8DC !important;
}
.usuarios-view .status-off {
    background: #F7F7F8 !important;
    color: #667085 !important;
    border-color: #E4E7EC !important;
}

.usuarios-view .act-edit {
    color: var(--user-heading) !important;
}
.usuarios-view .act-edit:hover {
    background: color-mix(in srgb, var(--user-accent) 8%, #FFFFFF) !important;
    border-color: color-mix(in srgb, var(--user-accent) 18%, var(--user-border)) !important;
}

.usuarios-view .lc-scroll::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--user-brand) 42%, #667085) !important;
}

/* Stats cards redesign: compact operational tiles. */
.usuarios-view .container.mx-auto.px-5 > .grid:first-child {
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 14px !important;
    margin-bottom: 1.35rem !important;
}

.usuarios-view .usr-widget {
    min-height: 148px;
    padding: 18px 19px 22px !important;
    border-radius: 18px !important;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--w-accent, var(--user-brand)) 7%, #FFFFFF) 0%, #FFFFFF 48%),
        #FFFFFF !important;
    border: 1px solid color-mix(in srgb, var(--w-accent, var(--user-brand)) 16%, var(--user-border)) !important;
    box-shadow: 0 1px 2px rgba(17,24,39,.035), 0 18px 36px -30px rgba(17,24,39,.5) !important;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 0;
}

.usuarios-view .usr-widget::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    height: auto;
    border-radius: 18px 0 0 18px;
    background: linear-gradient(180deg, var(--w-accent, var(--user-brand)), color-mix(in srgb, var(--w-accent, var(--user-brand)) 42%, #FFFFFF));
    opacity: .95;
}

.usuarios-view .usr-widget::after {
    content: '';
    position: absolute;
    left: 19px;
    right: 19px;
    bottom: 12px;
    height: 3px !important;
    border-radius: 999px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--w-accent, var(--user-brand)) 74%, #FFFFFF), color-mix(in srgb, var(--w-accent, var(--user-brand)) 16%, transparent) 72%, transparent) !important;
    opacity: .68;
}

.usuarios-view .usr-widget:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 28%, var(--user-border)) !important;
    box-shadow: 0 3px 8px rgba(17,24,39,.045), 0 22px 42px -30px color-mix(in srgb, var(--w-accent, var(--user-brand)) 45%, #111827) !important;
}

.usuarios-view .usr-widget:hover::after {
    opacity: .9;
}

.usuarios-view .usr-widget > div:first-child {
    display: flex !important;
    align-items: flex-start !important;
    justify-content: space-between !important;
    gap: 16px;
    margin: 0 !important;
}

.usuarios-view .usr-widget .w-icon {
    width: 42px !important;
    height: 42px !important;
    flex: 0 0 42px;
    border-radius: 13px !important;
    background: color-mix(in srgb, var(--w-accent, var(--user-brand)) 10%, #FFFFFF) !important;
    border: 1px solid color-mix(in srgb, var(--w-accent, var(--user-brand)) 20%, var(--user-border)) !important;
    color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 82%, #111827) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.85);
    font-size: 16px !important;
}

.usuarios-view .usr-widget > div:first-child > span {
    color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 86%, #111827) !important;
    font-family: var(--user-serif);
    font-size: clamp(2.2rem, 3vw, 2.85rem) !important;
    line-height: .9;
    font-weight: 800 !important;
    letter-spacing: 0;
}

.usuarios-view .usr-widget > p:first-of-type {
    margin: 16px 0 0 !important;
    color: var(--user-heading) !important;
    font-size: .72rem !important;
    line-height: 1.05;
    font-weight: 900 !important;
    letter-spacing: .115em !important;
}

.usuarios-view .usr-widget > div:last-child,
.usuarios-view .usr-widget > p:last-child {
    margin-top: 9px !important;
    padding-top: 9px;
    border-top: 1px solid color-mix(in srgb, var(--w-accent, var(--user-brand)) 13%, var(--user-border));
    color: var(--user-muted) !important;
    font-size: .78rem !important;
    line-height: 1.45;
}

.usuarios-view .usr-widget > div:last-child > span:first-child {
    color: color-mix(in srgb, var(--w-accent, var(--user-brand)) 82%, #111827) !important;
    font-size: 1.05rem !important;
    font-weight: 900 !important;
}

.usuarios-view .usr-widget > div:last-child > span:last-child,
.usuarios-view .usr-widget > p:last-child {
    color: var(--user-muted) !important;
}

.usuarios-view .usr-widget > p:last-child span {
    color: var(--user-heading) !important;
    font-weight: 800 !important;
}

@media (max-width: 640px) {
    .usr-hero .container {
        padding-top: 1.15rem;
    }
    .usr-hero p,
    .usr-hero .ml-14 {
        margin-left: 0 !important;
    }
    .usr-hero h1 {
        font-size: 2rem;
    }
    .usr-panel {
        border-radius: 14px !important;
    }

    .usuarios-view .container.mx-auto.px-5 > .grid:first-child {
        grid-template-columns: 1fr !important;
    }

    .usuarios-view table {
        border-spacing: 0;
    }
}
</style>

<!-- ═══════════════════ USUARIOS PAGE ══════════════════════ -->
<div class="usuarios-view usr-bg">

    <!-- Hero Header -->
    <div class="usr-hero">
        <div class="container mx-auto px-5 sm:px-7 py-5 relative z-10">
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1.5">
                        <div style="background:rgba(255,255,255,.12);width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-users text-white text-lg"></i>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white"><?= $esGestionHotel ? 'Trabajadores del Hotel' : 'Gestión de Usuarios' ?></h1>
                    </div>
                    <p class="text-white/55 text-sm ml-14">Control de accesos y roles de trabajadores · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="flex flex-wrap items-center gap-3 ml-14 lg:ml-0">
                    <span class="gold-badge"><i class="fas fa-shield-alt text-xs"></i> Administración</span>
                    <?php if ($puedeCrearUsuarios): ?>
                    <a href="<?= url('usuarios/create') ?>" class="btn-nuevo">
                        <i class="fas fa-plus text-xs"></i> <?= $esGestionHotel ? 'Nuevo Trabajador' : 'Nuevo Usuario' ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-5 sm:px-7 py-6 max-w-none">

        <!-- Stat Widgets -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

            <!-- Total -->
            <div class="usr-widget" style="--w-accent:#5C7A4E">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(92,122,78,.1);color:#5C7A4E;">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="text-2xl font-bold text-[#3D5234]"><?= count($usuarios) ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= $esGestionHotel ? 'Total Trabajadores' : 'Total Usuarios' ?></p>
                <div class="flex items-baseline gap-1.5 mt-1.5">
                    <span class="text-lg font-bold text-[#3D5234]">
                        <?= count(array_filter($usuarios, fn($u) => $u['activo'])) ?>
                    </span>
                    <span class="text-xs text-gray-400"><?= $esGestionHotel ? 'activos en el hotel' : 'activos en el sistema' ?></span>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="usr-widget" style="--w-accent:#10b981">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(16,185,129,.1);color:#059669;">
                        <i class="fas fa-signal"></i>
                    </div>
                    <span class="text-2xl font-bold text-emerald-600">
                        <?php
                        echo count(array_filter($usuarios, fn($u) =>
                            $u['ultimo_login'] && strtotime($u['ultimo_login']) > strtotime('-24 hours')
                        ));
                        ?>
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Actividad Reciente</p>
                <p class="text-xs text-gray-400 mt-1.5">Últimas 24 horas</p>
            </div>

            <!-- Roles -->
            <div class="usr-widget" style="--w-accent:#8b5cf6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-icon" style="background:rgba(139,92,246,.1);color:#7C3AED;">
                        <i class="fas fa-user-tag"></i>
                    </div>
                    <span class="text-2xl font-bold text-violet-700">
                        <?= count(array_unique(array_column($usuarios, 'rol'))) ?>
                    </span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Roles Activos</p>
                <?php
                $rolesCounts = array_count_values(array_column($usuarios, 'rol'));
                $topRole = !empty($rolesCounts) ? array_search(max($rolesCounts), $rolesCounts) : null;
                ?>
                <p class="text-xs text-gray-400 mt-1.5">
                    <?php if ($topRole): ?>
                        Mayor: <span class="font-semibold text-gray-600"><?= ucfirst($topRole) ?> (<?= $rolesCounts[$topRole] ?>)</span>
                    <?php else: ?>
                        Sin roles registrados
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div id="usuarios-action-notice" class="usr-inline-notice" role="status" aria-live="polite" hidden>
            <i class="fas fa-circle-info"></i>
            <span></span>
        </div>

        <!-- Users Table -->
        <div class="usr-panel">
            <!-- Panel header -->
            <div class="usr-panel-hd">
                <div class="flex items-center gap-2.5">
                    <div style="background:rgba(255,255,255,.18);width:28px;height:28px;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list text-white text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-white">Usuarios del Sistema</h3>
                </div>
                <span class="bg-white/20 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                    <?= count($usuarios) ?>
                </span>
            </div>

            <?php if (empty($usuarios)): ?>
                <div class="text-center py-16">
                    <i class="fas fa-users text-5xl mb-4" style="color:#D5E4CB"></i>
                    <h3 class="text-base font-bold text-gray-600 mb-1">No hay usuarios registrados</h3>
                    <p class="text-sm text-gray-400 mb-5"><?= $esGestionHotel ? 'Aún no se han registrado trabajadores para este hotel.' : 'Aún no se han creado usuarios en el sistema.' ?></p>
                    <?php if ($puedeCrearUsuarios): ?>
                        <a href="<?= url('usuarios/create') ?>" class="btn-nuevo">
                            <i class="fas fa-plus text-xs"></i> <?= $esGestionHotel ? 'Crear primer trabajador' : 'Crear primer usuario' ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto lc-scroll">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-[#EAF0E5]">
                                <th class="usr-th text-left">Usuario</th>
                                <th class="usr-th text-left">Información</th>
                                <th class="usr-th text-left">Rol</th>
                                <th class="usr-th text-left">Estado</th>
                                <th class="usr-th text-left">Último Acceso</th>
                                <th class="usr-th text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr class="usr-tr">
                                <!-- Avatar + username -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="usr-avatar">
                                            <?= strtoupper(substr($usuario['nombre_usuario'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800"><?= htmlspecialchars($usuario['nombre_usuario']) ?></p>
                                            <p class="text-xs text-gray-400">ID: #<?= $usuario['id'] ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Info -->
                                <td class="px-4 py-3">
                                    <p class="text-xs font-semibold text-gray-800"><?= htmlspecialchars($usuario['nombre_completo']) ?></p>
                                    <?php if ($usuario['email']): ?>
                                        <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5">
                                            <i class="fas fa-envelope text-xs"></i>
                                            <?= htmlspecialchars($usuario['email']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($usuario['telefono']): ?>
                                        <p class="text-xs text-gray-400 flex items-center gap-1">
                                            <i class="fas fa-phone text-xs"></i>
                                            <?= htmlspecialchars($usuario['telefono']) ?>
                                        </p>
                                    <?php endif; ?>
                                </td>

                                <!-- Rol -->
                                <td class="px-4 py-3">
                                    <span class="rol-badge rol-<?= $usuario['rol'] ?>">
                                        <?= ucfirst($usuario['rol']) ?>
                                    </span>
                                </td>

                                <!-- Estado -->
                                <td class="px-4 py-3">
                                    <?php if ($usuario['activo']): ?>
                                        <span class="status-on">
                                            <i class="fas fa-check-circle text-xs"></i> Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="status-off">
                                            <i class="fas fa-times-circle text-xs"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Último acceso -->
                                <td class="px-4 py-3">
                                    <?php if ($usuario['ultimo_login']): ?>
                                        <p class="text-xs font-semibold text-gray-800"><?= format_datetime($usuario['ultimo_login']) ?></p>
                                        <p class="text-xs text-gray-400">
                                            <?php
                                            $hace = time() - strtotime($usuario['ultimo_login']);
                                            if ($hace < 3600) echo 'Hace ' . round($hace/60) . ' min';
                                            elseif ($hace < 86400) echo 'Hace ' . round($hace/3600) . ' h';
                                            else echo 'Hace ' . round($hace/86400) . ' días';
                                            ?>
                                        </p>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300 italic">Nunca</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <?php if ($puedeEditarUsuarios): ?>
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="<?= url("usuarios/{$usuario['id']}/edit") ?>"
                                           class="act-btn act-edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($usuario['id'] != user_id()): ?>
                                            <?php if ($usuario['activo']): ?>
                                                <button onclick="cambiarEstadoUsuario(<?= (int) $usuario['id'] ?>, false, <?= json_encode($usuario['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, this)"
                                                        class="act-btn act-deact" title="Desactivar">
                                                    <i class="fas fa-user-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="cambiarEstadoUsuario(<?= (int) $usuario['id'] ?>, true, <?= json_encode($usuario['nombre_completo'] ?? '', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, this)"
                                                        class="act-btn act-act" title="Activar">
                                                    <i class="fas fa-user-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300">Solo lectura</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let userStatePending = null;
let userStateTimer = null;

function resetUserStateConfirmation() {
    window.clearTimeout(userStateTimer);
    document.querySelectorAll('.act-btn.is-confirming').forEach(btn => {
        btn.classList.remove('is-confirming');
        btn.disabled = false;
        const activating = btn.classList.contains('act-act');
        btn.title = activating ? 'Activar' : 'Desactivar';
        btn.innerHTML = activating
            ? '<i class="fas fa-user-check"></i>'
            : '<i class="fas fa-user-slash"></i>';
    });
    userStatePending = null;
    const notice = document.getElementById('usuarios-action-notice');
    if (notice) {
        notice.hidden = true;
        notice.classList.remove('is-danger', 'is-success');
    }
}

function showUserStateNotice(message, activar) {
    const notice = document.getElementById('usuarios-action-notice');
    if (!notice) return;
    const icon = notice.querySelector('i');
    const text = notice.querySelector('span');
    notice.classList.toggle('is-success', !!activar);
    notice.classList.toggle('is-danger', !activar);
    if (icon) icon.className = activar ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation';
    if (text) text.textContent = message;
    notice.hidden = false;
}

function submitUserStateChange(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url('usuarios/') ?>' + id + '/toggle';
    const csrf = document.createElement('input');
    csrf.type = 'hidden';
    csrf.name = 'csrf_token';
    csrf.value = '<?= csrf_token() ?>';
    form.appendChild(csrf);
    document.body.appendChild(form);
    form.submit();
}

function cambiarEstadoUsuario(id, activar, nombre, trigger) {
    const actionKey = `${id}:${activar ? 'activar' : 'desactivar'}`;

    if (userStatePending === actionKey) {
        if (trigger) {
            trigger.disabled = true;
            trigger.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        submitUserStateChange(id);
        return;
    }

    resetUserStateConfirmation();
    userStatePending = actionKey;

    if (trigger) {
        trigger.classList.add('is-confirming');
        trigger.title = activar ? 'Confirmar activacion' : 'Confirmar desactivacion';
        trigger.innerHTML = '<i class="fas fa-check"></i>';
    }

    const persona = nombre || 'este usuario';
    showUserStateNotice(
        activar
            ? `Presiona otra vez para habilitar el acceso de ${persona}.`
            : `Presiona otra vez para bloquear el acceso de ${persona}.`,
        activar
    );
    userStateTimer = window.setTimeout(() => resetUserStateConfirmation(), 7000);
}

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.usuarios-view');
    if (view) view.classList.add('loaded');

    document.querySelectorAll('.usr-tr').forEach((row, i) => {
        setTimeout(() => row.classList.add('visible'), i * 45);
    });
});
</script>
