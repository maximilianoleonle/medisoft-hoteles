<?php
header('Content-Type: text/html; charset=UTF-8');
/**
 * Vista Principal de Facturación
 * Paleta hotelera boutique
 */

$solicitudes  = $solicitudes  ?? [];
$estadisticas = $estadisticas ?? [];
$mensaje      = get_mensaje();
?>

<style>
/* ══════════════════════════════════════════
   Facturación
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-green-light: #7A9B6A;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
    --lc-cream-mid:   #EEE9DE;
}

/* ── Page ────────────────────────────────── */
.facturacion-view {
    min-height: 100vh;
    background: linear-gradient(145deg, #EFF4EC 0%, #E8EEE3 50%, #F4F1EC 100%);
    animation: fadeIn .35s ease forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Header ──────────────────────────────── */
.fact-header {
    background: linear-gradient(135deg, #3D5234 0%, #4A6340 55%, #5C7A4E 100%);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 4px 18px rgba(61,82,52,.28);
    position: sticky;
    top: 0;
    z-index: 40;
    overflow: hidden;
}
.fact-header::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(200,169,106,.08);
    pointer-events: none;
}
.fact-header a { color: var(--lc-gold); transition: color .2s; text-decoration: none; }
.fact-header a:hover { color: white; }

/* ── Stat cards ──────────────────────────── */
.stat-card {
    background: white;
    border-radius: 14px;
    padding: 18px 20px;
    border: 1px solid #DDE8D5;
    transition: transform .25s, box-shadow .25s;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    opacity: 0;
    transition: opacity .25s;
    background: var(--sc-accent, #5C7A4E);
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 28px rgba(92,122,78,.11); }
.stat-card:hover::after { opacity: 1; }

.stat-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

/* ── Filter card ─────────────────────────── */
.filter-card {
    background: white;
    border-radius: 14px;
    border: 1px solid #DDE8D5;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(92,122,78,.06);
    transition: box-shadow .25s;
}
.filter-card:hover { box-shadow: 0 4px 18px rgba(92,122,78,.09); }

.filter-hd {
    padding: 12px 18px;
    border-bottom: 1px solid #EAF0E5;
    background: linear-gradient(135deg, rgba(92,122,78,.06), rgba(92,122,78,.02));
    display: flex; align-items: center; justify-content: space-between;
}

/* ── Inputs ──────────────────────────────── */
.lc-input {
    width: 100%;
    padding: 8px 10px;
    border: 1.5px solid #C8D9BE;
    border-radius: 8px;
    font-size: .8rem;
    background: #FAFDF8;
    color: #374151;
    transition: border-color .2s, box-shadow .2s;
}
.lc-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
}
.filter-label {
    display: block;
    font-size: .68rem;
    font-weight: 700;
    color: #5C7A4E;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 4px;
}

/* ── Table ───────────────────────────────── */
.fact-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}
.fact-table thead th {
    background: #F0F5ED;
    padding: 10px 14px;
    font-size: .67rem;
    font-weight: 700;
    color: #7A9B6A;
    text-transform: uppercase;
    letter-spacing: .05em;
    border-bottom: 2px solid #DDE8D5;
    text-align: left;
    white-space: nowrap;
}
.fact-table tbody tr {
    transition: background .15s;
    border-bottom: 1px solid #F0F5ED;
}
.fact-table tbody tr:hover { background: #F7FCF4; }
.fact-table tbody td {
    padding: 12px 14px;
    font-size: .8rem;
    color: #1F2937;
    vertical-align: middle;
}

/* ── Badges ──────────────────────────────── */
.badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: .67rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .03em;
}
.badge-cliente     { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#1E40AF; border:1px solid #93C5FD; }
.badge-uso-interno { background: linear-gradient(135deg,#F5F3FF,#EDE9FE); color:#6D28D9; border:1px solid #C4B5FD; }
.badge-pendiente   { background: linear-gradient(135deg,#FFFBEB,#FEF3C7); color:#92400E; border:1px solid #FDE68A; }
.badge-en-proceso  { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color:#1E40AF; border:1px solid #93C5FD; }
.badge-completada  { background: rgba(92,122,78,.1);                       color:#3D5234; border:1px solid rgba(92,122,78,.25); }
.badge-cancelada   { background: #F9FAFB;                                   color:#6B7280; border:1px solid #D1D5DB; }

/* ── Action button ───────────────────────── */
.btn-ver {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: .75rem; font-weight: 700;
    border: none; cursor: pointer; text-decoration: none;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white;
    box-shadow: 0 2px 6px rgba(92,122,78,.22);
    transition: transform .2s, box-shadow .2s;
}
.btn-ver:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 14px rgba(92,122,78,.32);
    color: white;
}

/* ── Filter buttons ──────────────────────── */
.btn-filter {
    padding: 8px 14px;
    border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white;
    display: inline-flex; align-items: center; gap: 5px;
    transition: box-shadow .2s, transform .2s;
    box-shadow: 0 2px 8px rgba(92,122,78,.22);
}
.btn-filter:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(92,122,78,.3); }

.btn-clear {
    padding: 8px 12px;
    border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: 1.5px solid #D5E4CB;
    background: rgba(92,122,78,.06);
    color: #4A6340; cursor: pointer;
    display: inline-flex; align-items: center; gap: 5px;
    transition: background .15s;
    text-decoration: none;
}
.btn-clear:hover { background: rgba(92,122,78,.12); }

/* ── Flash messages ──────────────────────── */
.flash-msg {
    padding: 12px 16px;
    border-radius: 10px;
    font-size: .875rem; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px;
    animation: slideDown .3s ease;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.flash-success { background: rgba(92,122,78,.08);   color: #3D5234; border: 1.5px solid rgba(92,122,78,.3); }
.flash-error   { background: #FEF2F2;                color: #991B1B; border: 1.5px solid #FCA5A5; }
.flash-info    { background: #EFF6FF;                color: #1E40AF; border: 1.5px solid #93C5FD; }

/* ── Empty state ─────────────────────────── */
.empty-state {
    text-align: center;
    padding: 48px 16px;
    color: #9CA3AF;
}
.empty-state-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(92,122,78,.07);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    font-size: 28px;
    color: #A8C4A0;
}

/* ── Pagination ──────────────────────────── */
.pagination { display: flex; gap: 3px; align-items: center; flex-wrap: wrap; justify-content: center; }
.page-btn {
    padding: 5px 11px;
    border-radius: 7px;
    font-size: .75rem; font-weight: 600;
    border: 1.5px solid #D5E4CB;
    background: white; color: #4A6340;
    cursor: pointer; text-decoration: none;
    transition: border-color .15s, background .15s;
}
.page-btn:hover    { border-color: var(--lc-green); background: #F0F5ED; }
.page-btn.active   { background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark)); color: white; border-color: transparent; }
.page-btn.disabled { opacity: .4; pointer-events: none; }

/* ── RFC badge ───────────────────────────── */
.rfc-badge {
    font-family: monospace;
    font-size: .72rem; font-weight: 700;
    background: #F0F5ED;
    color: #3D5234;
    padding: 2px 7px;
    border-radius: 5px;
    border: 1px solid #C8D9BE;
    letter-spacing: .03em;
}

/* ── ID badge ────────────────────────────── */
.id-badge {
    font-weight: 800;
    color: var(--lc-green-deep);
    font-size: .82rem;
}

/* ── Reservation link ────────────────────── */
.res-link { color: #2563EB; font-weight: 700; text-decoration: none; }
.res-link:hover { text-decoration: underline; }

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { height: 4px; width: 4px; }
.lc-scroll::-webkit-scrollbar-track { background: #F0F5ED; }
.lc-scroll::-webkit-scrollbar-thumb { background: #A8C4A0; border-radius: 4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--lc-green); }

/* ── Responsive ──────────────────────────── */
@media (max-width: 1024px) {
    .fact-header { position: relative; }
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; }
    .filter-grid { grid-template-columns: 1fr 1fr !important; }
    .filter-grid .filter-actions { grid-column: 1 / -1; }
}

@media (max-width: 640px) {
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; gap: .625rem !important; margin-bottom: 1rem !important; }
    .stat-card { padding: 14px; border-radius: 11px; }
    .stat-icon { width: 36px; height: 36px; font-size: 14px; border-radius: 9px; }
    .fact-header h1 { font-size: 1rem !important; }
    .fact-header p  { display: none; }
    .filter-grid { grid-template-columns: 1fr !important; gap: .625rem !important; }
    .filter-card form { padding: 12px !important; }
    .filter-actions { flex-direction: row !important; }
    .filter-actions button, .filter-actions a { flex: 1; justify-content: center; }
    .filter-toggle-btn { display: flex !important; }

    /* Table → Cards on mobile */
    .fact-table thead { display: none; }
    .fact-table tbody tr {
        display: block;
        padding: 12px;
        margin-bottom: 10px;
        background: white;
        border-radius: 11px;
        border: 1.5px solid #DDE8D5;
        box-shadow: 0 1px 4px rgba(92,122,78,.06);
    }
    .fact-table tbody tr:hover { background: white; }
    .fact-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px dashed #EAF0E5;
    }
    .fact-table tbody td:last-child { border-bottom: none; padding-top: 10px; }
    .fact-table tbody td::before {
        content: attr(data-label);
        font-weight: 700; color: #7A9B6A;
        font-size: .67rem; text-transform: uppercase;
        flex-shrink: 0; margin-right: 10px;
    }
    .fact-table tbody td:last-child .btn-ver { width: 100%; justify-content: center; padding: 8px; }
    .flash-msg { font-size: .8125rem; padding: 10px 12px; }
    .empty-state { padding: 32px 12px; }
}

@media (max-width: 380px) {
    .stats-grid { grid-template-columns: 1fr !important; }
    .fact-header h1 { font-size: .9rem !important; }
}
</style>

<style id="facturacion-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.facturacion-view {
    --fact-brand: var(--brand-primary, #1B2746);
    --fact-brand-2: var(--brand-secondary, #0F172A);
    --fact-accent: var(--brand-accent, #BD9441);
    --fact-ivory: color-mix(in srgb, var(--fact-accent) 8%, #F8F5ED);
    --fact-ivory-2: color-mix(in srgb, var(--fact-accent) 6%, #FBF9F4);
    --fact-surface: color-mix(in srgb, var(--fact-accent) 2%, #FFFFFF);
    --fact-surface-warm: color-mix(in srgb, var(--fact-accent) 5%, #FFFFFF);
    --fact-line: color-mix(in srgb, var(--fact-accent) 24%, #E7DEC9);
    --fact-line-soft: color-mix(in srgb, var(--fact-accent) 13%, #F0ECE2);
    --fact-muted: color-mix(in srgb, var(--fact-brand-2) 48%, #94A3B8);
    --fact-success: #1E9E63;
    --fact-success-bg: #E7F4EC;
    --fact-warning: #C2841C;
    --fact-warning-bg: #FAF0DC;
    --fact-danger: #D64539;
    --fact-danger-bg: #FBE9E7;
    --fact-info: #2F77E0;
    --fact-info-bg: #E6EFFC;
    --fact-purple: #6F5FD2;
    --fact-purple-bg: #EFECFB;
    --fact-shadow: 0 2px 8px color-mix(in srgb, var(--fact-brand-2) 6%, transparent), 0 12px 28px color-mix(in srgb, var(--fact-brand-2) 7%, transparent);
    --fact-shadow-lg: 0 18px 48px color-mix(in srgb, var(--fact-brand-2) 14%, transparent);
    --fact-serif: 'Cormorant Garamond', Georgia, serif;
    --fact-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--fact-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--fact-ivory-2), var(--fact-ivory) 56%, #F7F2EA) !important;
    color: var(--fact-brand-2);
    font-family: var(--fact-sans);
    animation: none !important;
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.facturacion-view *,
.facturacion-view *::before,
.facturacion-view *::after {
    box-sizing: border-box;
}

.facturacion-view :where(p, span, a, button, input, select, textarea, th, td, label) {
    font-family: var(--fact-sans);
}

.facturacion-view > .container,
.facturacion-view .fact-header .container {
    max-width: 1440px !important;
}

.facturacion-view .fact-header {
    position: relative !important;
    top: auto !important;
    padding: 26px 0 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    color: var(--fact-brand-2) !important;
}

.facturacion-view .fact-header::before {
    display: none !important;
}

.facturacion-view .fact-header h1 {
    color: var(--fact-brand) !important;
    font-family: var(--fact-serif);
    font-size: clamp(2rem, 3.2vw, 2.6rem) !important;
    font-weight: 650 !important;
    line-height: .95 !important;
    letter-spacing: 0 !important;
}

.facturacion-view .fact-header h1 i {
    color: var(--fact-accent) !important;
}

.facturacion-view .fact-header h1 + p {
    display: block !important;
    margin-top: 8px !important;
    color: var(--fact-muted) !important;
    font-weight: 600;
}

.facturacion-view .fact-header a[style] {
    background: linear-gradient(150deg, var(--fact-brand), var(--fact-brand-2)) !important;
    color: #FFFFFF !important;
    border: 1px solid color-mix(in srgb, var(--fact-accent) 22%, transparent);
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--fact-brand) 55%, transparent);
}

.facturacion-view .fact-header a[style] i {
    color: #FFFFFF !important;
}

.facturacion-view .fact-header div[style*="width:1px"] {
    background: var(--fact-line) !important;
}

.facturacion-view .fact-header span[style*="display:flex"] {
    background: var(--fact-surface) !important;
    border: 1px solid var(--fact-line) !important;
    color: color-mix(in srgb, var(--fact-accent) 72%, #000) !important;
    box-shadow: var(--fact-shadow);
}

.facturacion-view .stat-card,
.facturacion-view .filter-card {
    background: var(--fact-surface) !important;
    border: 1px solid var(--fact-line) !important;
    border-radius: 16px !important;
    box-shadow: var(--fact-shadow) !important;
}

.facturacion-view .stat-card {
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease !important;
}

.facturacion-view .stat-card::after {
    opacity: .9 !important;
    height: 3px !important;
}

.facturacion-view .stat-card:hover,
.facturacion-view .filter-card:hover {
    transform: translateY(-1px) !important;
    border-color: color-mix(in srgb, var(--fact-accent) 42%, var(--fact-line)) !important;
    box-shadow: 0 16px 34px color-mix(in srgb, var(--fact-brand-2) 10%, transparent) !important;
}

.facturacion-view .stat-icon {
    border-radius: 12px !important;
    border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
}

.facturacion-view .stat-card p[style*="font-size:2rem"],
.facturacion-view .stat-card p[style*="font-size:1.5rem"],
.facturacion-view .id-badge,
.facturacion-view .rfc-badge,
.facturacion-view td[data-label="Monto"] span {
    font-variant-numeric: tabular-nums;
}

.facturacion-view .filter-hd {
    background: var(--fact-surface-warm) !important;
    border-bottom: 1px solid var(--fact-line) !important;
}

.facturacion-view .filter-hd h3,
.facturacion-view .filter-hd h3[style] {
    color: var(--fact-brand-2) !important;
}

.facturacion-view .filter-hd h3 div,
.facturacion-view .filter-hd h3 div[style] {
    background: color-mix(in srgb, var(--fact-accent) 14%, #FFFFFF) !important;
    border: 1px solid var(--fact-line);
}

.facturacion-view .filter-hd h3 i {
    color: color-mix(in srgb, var(--fact-accent) 72%, #000) !important;
}

.facturacion-view .lc-input {
    min-height: 40px;
    background: var(--fact-surface-warm) !important;
    border: 1px solid var(--fact-line) !important;
    border-radius: 11px !important;
    color: var(--fact-brand-2) !important;
    font-weight: 650;
}

.facturacion-view .lc-input:focus {
    border-color: var(--fact-accent) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--fact-accent) 24%, transparent) !important;
    background: #FFFFFF !important;
}

.facturacion-view .filter-label {
    color: var(--fact-muted) !important;
    letter-spacing: .07em !important;
}

.facturacion-view .btn-filter,
.facturacion-view .btn-ver,
.facturacion-view .btn-clear,
.facturacion-view .page-btn,
.facturacion-view .filter-toggle-btn {
    min-height: 38px;
    border-radius: 11px !important;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, color .18s ease !important;
}

.facturacion-view .btn-filter,
.facturacion-view .btn-ver,
.facturacion-view .page-btn.active {
    background: linear-gradient(135deg, var(--fact-brand), var(--fact-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--fact-brand) 58%, transparent) !important;
}

.facturacion-view .btn-clear,
.facturacion-view .page-btn,
.facturacion-view .filter-toggle-btn {
    background: var(--fact-surface-warm) !important;
    border: 1px solid var(--fact-line) !important;
    color: var(--fact-brand-2) !important;
}

.facturacion-view .btn-filter:hover,
.facturacion-view .btn-ver:hover,
.facturacion-view .btn-clear:hover,
.facturacion-view .page-btn:hover,
.facturacion-view .filter-toggle-btn:hover {
    transform: translateY(-1px) !important;
    border-color: color-mix(in srgb, var(--fact-accent) 42%, var(--fact-line)) !important;
}

.facturacion-view .filter-card > div[style*="background:linear-gradient(135deg,#5C7A4E"] {
    background: linear-gradient(135deg, var(--fact-brand), var(--fact-brand-2)) !important;
    border-bottom: 1px solid rgba(255,255,255,.14) !important;
}

.facturacion-view .filter-card > div[style*="background:linear-gradient(135deg,#5C7A4E"] div[style*="background:rgba"] {
    background: color-mix(in srgb, var(--fact-accent) 28%, rgba(255,255,255,.12)) !important;
    border: 1px solid rgba(255,255,255,.2);
}

.facturacion-view .fact-table thead th {
    background: var(--fact-surface-warm) !important;
    border-bottom: 1px solid var(--fact-line) !important;
    color: var(--fact-muted) !important;
    letter-spacing: .07em !important;
}

.facturacion-view .fact-table tbody tr {
    border-bottom: 1px solid var(--fact-line-soft) !important;
}

.facturacion-view .fact-table tbody tr:hover {
    background: var(--fact-surface-warm) !important;
}

.facturacion-view .fact-table tbody td {
    color: var(--fact-brand-2) !important;
}

.facturacion-view .badge {
    border-radius: 999px !important;
    font-variant-numeric: tabular-nums;
}

.facturacion-view .badge-cliente,
.facturacion-view .badge-en-proceso {
    background: var(--fact-info-bg) !important;
    color: #1F5CA8 !important;
    border-color: color-mix(in srgb, var(--fact-info) 28%, #DDEAFB) !important;
}

.facturacion-view .badge-uso-interno {
    background: var(--fact-purple-bg) !important;
    color: #5145A8 !important;
    border-color: color-mix(in srgb, var(--fact-purple) 28%, #E4DFF8) !important;
}

.facturacion-view .badge-pendiente {
    background: var(--fact-warning-bg) !important;
    color: #8A5A12 !important;
    border-color: color-mix(in srgb, var(--fact-warning) 28%, #F1DFC0) !important;
}

.facturacion-view .badge-completada {
    background: var(--fact-success-bg) !important;
    color: #0F7048 !important;
    border-color: color-mix(in srgb, var(--fact-success) 28%, #D8EFE4) !important;
}

.facturacion-view .badge-cancelada {
    background: color-mix(in srgb, var(--fact-brand-2) 6%, #FFFFFF) !important;
    color: var(--fact-muted) !important;
    border-color: var(--fact-line) !important;
}

.facturacion-view .rfc-badge,
.facturacion-view .id-badge {
    background: var(--fact-surface-warm) !important;
    border-color: var(--fact-line) !important;
    color: var(--fact-brand) !important;
}

.facturacion-view .res-link {
    color: var(--fact-info) !important;
}

.facturacion-view .empty-state {
    background: var(--fact-ivory-2);
    border: 1px dashed var(--fact-line);
    border-radius: 16px;
    margin: 16px;
}

.facturacion-view .empty-state-icon {
    border-radius: 16px !important;
    background: color-mix(in srgb, var(--fact-accent) 15%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--fact-accent) 72%, #000) !important;
}

.facturacion-view .flash-msg {
    border-width: 1px !important;
    border-left-width: 1px !important;
    box-shadow: var(--fact-shadow);
    animation: none !important;
}

.facturacion-view .flash-success {
    background: var(--fact-success-bg) !important;
    color: #0F7048 !important;
    border-color: color-mix(in srgb, var(--fact-success) 28%, #D8EFE4) !important;
}

.facturacion-view .flash-error {
    background: var(--fact-danger-bg) !important;
    color: #9D3028 !important;
    border-color: color-mix(in srgb, var(--fact-danger) 28%, #F3D7D4) !important;
}

.facturacion-view .flash-info {
    background: var(--fact-info-bg) !important;
    color: #1F5CA8 !important;
    border-color: color-mix(in srgb, var(--fact-info) 28%, #DDEAFB) !important;
}

.facturacion-view .lc-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
.facturacion-view .lc-scroll::-webkit-scrollbar-track { background: var(--fact-ivory); border-radius: 10px; }
.facturacion-view .lc-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--fact-accent), #fff 34%); border-radius: 10px; }
.facturacion-view .lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--fact-accent); }

.facturacion-view a:focus-visible,
.facturacion-view button:focus-visible,
.facturacion-view input:focus-visible,
.facturacion-view select:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--fact-accent) 30%, transparent) !important;
}

/* Layout upgrade: document workflow, compact but visibly structured. */
.facturacion-view .fact-header {
    margin-bottom: 6px;
}

.facturacion-view .fact-header .container {
    padding-bottom: 10px;
}

.facturacion-view .fact-header .flex.items-center.justify-between {
    align-items: flex-start;
}

.facturacion-view .fact-header a[style] {
    margin-top: 3px;
}

.facturacion-view > .container {
    max-width: 1320px !important;
}

.facturacion-view .stats-grid {
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
    gap: 14px !important;
    align-items: stretch;
}

.facturacion-view .stats-grid > .stat-card {
    grid-column: span 3;
    min-height: 146px;
    overflow: hidden;
    position: relative;
}

.facturacion-view .stats-grid > .stat-card::before {
    content: '';
    position: absolute;
    inset: 12px 12px auto auto;
    width: 50px;
    height: 50px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 12%, transparent);
    pointer-events: none;
}

.facturacion-view .stats-grid > .stat-card > div {
    position: relative;
    z-index: 1;
}

.facturacion-view .stat-card p[style*="font-size:2rem"],
.facturacion-view .stat-card p[style*="font-size:1.5rem"] {
    font-family: var(--fact-serif);
    letter-spacing: 0;
    line-height: .95 !important;
}

.facturacion-view .filter-card {
    overflow: hidden;
}

.facturacion-view .filter-hd {
    min-height: 50px;
    padding: 14px 18px !important;
}

.facturacion-view .filter-form .filter-grid {
    grid-template-columns: minmax(220px, 1.35fr) repeat(3, minmax(132px, 1fr)) auto !important;
    gap: 12px !important;
}

.facturacion-view .filter-actions {
    align-items: stretch;
}

.facturacion-view .btn-filter,
.facturacion-view .btn-clear {
    min-height: 42px;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] {
    background: var(--fact-surface-warm) !important;
    border-bottom: 1px solid var(--fact-line) !important;
    min-height: 52px;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] h3 {
    color: var(--fact-brand) !important;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] h3 div {
    background: color-mix(in srgb, var(--fact-accent) 14%, var(--fact-surface)) !important;
    border: 1px solid var(--fact-line);
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] h3 i {
    color: color-mix(in srgb, var(--fact-accent) 76%, #3F2E12) !important;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] > span {
    background: var(--fact-surface) !important;
    border: 1px solid var(--fact-line);
    color: var(--fact-brand) !important;
}

.facturacion-view .fact-table {
    border-collapse: separate !important;
    border-spacing: 0 9px;
    min-width: 100%;
}

.facturacion-view .fact-table thead tr {
    transform: translateY(4px);
}

.facturacion-view .fact-table tbody tr {
    background: var(--fact-surface) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--fact-brand-2) 4%, transparent);
}

.facturacion-view .fact-table tbody td {
    border-top: 1px solid var(--fact-line-soft);
    border-bottom: 1px solid var(--fact-line-soft);
    padding-top: 12px !important;
    padding-bottom: 12px !important;
}

.facturacion-view .fact-table tbody td:first-child {
    border-left: 1px solid var(--fact-line-soft);
    border-radius: 14px 0 0 14px;
}

.facturacion-view .fact-table tbody td:last-child {
    border-right: 1px solid var(--fact-line-soft);
    border-radius: 0 14px 14px 0;
}

.facturacion-view .fact-table tbody tr:hover td {
    border-color: color-mix(in srgb, var(--fact-accent) 30%, var(--fact-line));
}

.facturacion-view .id-badge {
    min-width: 38px;
    justify-content: center;
}

.facturacion-view .btn-ver {
    padding-left: 13px !important;
    padding-right: 13px !important;
}

/* Color balance: fiscal data in neutral ink, hotel color as accent. */
.facturacion-view {
    --fact-heading: #111827;
    --fact-body: #1F2937;
    --fact-muted: #667085;
    --fact-ivory: color-mix(in srgb, var(--fact-accent) 4%, #F8F5ED);
    --fact-ivory-2: color-mix(in srgb, var(--fact-accent) 3%, #FBF9F4);
    --fact-surface: #FFFFFF;
    --fact-surface-warm: color-mix(in srgb, var(--fact-accent) 3%, #FFFFFF);
    --fact-line: color-mix(in srgb, var(--fact-brand) 6%, #E7DEC9);
    --fact-line-soft: color-mix(in srgb, var(--fact-brand) 4%, #F0ECE2);
    --fact-shadow: 0 1px 2px rgba(17, 24, 39, .04), 0 14px 30px -24px rgba(17, 24, 39, .34);
    color: var(--fact-body);
    background:
        radial-gradient(circle at 12% 5%, color-mix(in srgb, var(--fact-accent) 7%, transparent) 0, transparent 28%),
        radial-gradient(circle at 88% 2%, color-mix(in srgb, var(--fact-brand) 5%, transparent) 0, transparent 26%),
        linear-gradient(180deg, var(--fact-ivory), #FFFFFF 44%, var(--fact-ivory-2)) !important;
}

.facturacion-view .fact-header h1,
.facturacion-view .filter-hd h3,
.facturacion-view .filter-card > div[style*="padding:12px 18px"] h3,
.facturacion-view .stat-card p[style*="font-size:2rem"],
.facturacion-view .stat-card p[style*="font-size:1.5rem"],
.facturacion-view .fact-table tbody td,
.facturacion-view .fact-table tbody td span[style*="font-weight:600"],
.facturacion-view td[data-label="Monto"] span,
.facturacion-view .id-badge,
.facturacion-view .rfc-badge {
    color: var(--fact-heading) !important;
}

.facturacion-view .fact-header {
    background: transparent !important;
}

.facturacion-view .stat-card,
.facturacion-view .filter-card {
    background: var(--fact-surface) !important;
    border-color: var(--fact-line) !important;
    box-shadow: var(--fact-shadow) !important;
}

.facturacion-view .stats-grid > .stat-card::before {
    background: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 8%, transparent);
}

.facturacion-view .stat-card h3,
.facturacion-view .filter-label,
.facturacion-view .fact-table tbody td::before {
    color: var(--fact-muted) !important;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] {
    background: linear-gradient(180deg, var(--fact-surface), var(--fact-surface-warm)) !important;
}

.facturacion-view .filter-card > div[style*="padding:12px 18px"] > span,
.facturacion-view .id-badge,
.facturacion-view .rfc-badge {
    background: color-mix(in srgb, var(--fact-brand) 4%, #FFFFFF) !important;
    border-color: var(--fact-line) !important;
}

.facturacion-view .btn-filter,
.facturacion-view .page-btn.active {
    background: var(--fact-heading) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 22px -18px rgba(17, 24, 39, .65) !important;
}

.facturacion-view .btn-ver {
    background: linear-gradient(135deg, color-mix(in srgb, var(--fact-brand) 86%, #111827), color-mix(in srgb, var(--fact-brand-2) 84%, #111827)) !important;
    box-shadow: 0 10px 18px -16px color-mix(in srgb, var(--fact-brand) 60%, #111827) !important;
}

.facturacion-view .btn-ver:hover,
.facturacion-view .btn-filter:hover,
.facturacion-view .page-btn.active:hover {
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--fact-accent) 52%, #111827) !important;
}

.facturacion-view .fact-table tbody tr {
    background: var(--fact-surface) !important;
    box-shadow: 0 1px 2px rgba(17, 24, 39, .035);
}

.facturacion-view .fact-table tbody tr:hover {
    background: color-mix(in srgb, var(--fact-accent) 5%, #FFFFFF) !important;
}

.facturacion-view .fact-table tbody tr:hover td {
    border-color: color-mix(in srgb, var(--fact-accent) 22%, var(--fact-line)) !important;
}

.facturacion-view .badge-cliente,
.facturacion-view .badge-en-proceso {
    background: color-mix(in srgb, var(--fact-info) 8%, #FFFFFF) !important;
    color: #1F5CA8 !important;
    border-color: color-mix(in srgb, var(--fact-info) 18%, #D9E5F8) !important;
}

.facturacion-view .badge-uso-interno {
    background: color-mix(in srgb, var(--fact-purple) 8%, #FFFFFF) !important;
    color: #5145A8 !important;
    border-color: color-mix(in srgb, var(--fact-purple) 18%, #E1DDF7) !important;
}

.facturacion-view .badge-pendiente {
    background: color-mix(in srgb, var(--fact-warning) 10%, #FFFFFF) !important;
    color: #8A5A12 !important;
    border-color: color-mix(in srgb, var(--fact-warning) 18%, #F4E0AF) !important;
}

.facturacion-view .badge-completada {
    background: color-mix(in srgb, var(--fact-success) 8%, #FFFFFF) !important;
    color: #0F7048 !important;
    border-color: color-mix(in srgb, var(--fact-success) 18%, #CFE9DC) !important;
}

.facturacion-view .badge-cancelada {
    background: #F7F7F8 !important;
    color: var(--fact-muted) !important;
    border-color: #E4E7EC !important;
}

.facturacion-view .res-link {
    color: color-mix(in srgb, var(--fact-info) 86%, #111827) !important;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}

.facturacion-view .res-link:hover {
    color: color-mix(in srgb, var(--fact-info) 70%, var(--fact-heading)) !important;
    text-decoration: underline !important;
}

.facturacion-view .lc-input:focus,
.facturacion-view .page-btn:focus-visible,
.facturacion-view .btn-ver:focus-visible,
.facturacion-view .btn-filter:focus-visible,
.facturacion-view .res-link:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--fact-accent) 22%, transparent) !important;
    outline-offset: 2px;
}

@media (max-width: 768px) {
    .facturacion-view .fact-header .container,
    .facturacion-view > .container {
        padding-left: 14px !important;
        padding-right: 14px !important;
    }

    .facturacion-view .fact-header h1 {
        font-size: 2rem !important;
    }

    .facturacion-view .fact-header h1 + p {
        display: block !important;
        font-size: .75rem !important;
    }

    .facturacion-view .stats-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 10px !important;
    }

    .facturacion-view .stat-card {
        padding: 14px !important;
    }

    .facturacion-view .filter-form[style] {
        padding: 12px !important;
    }

    .facturacion-view .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .facturacion-view .stats-grid > .stat-card {
        grid-column: span 1;
    }

    .facturacion-view .filter-form .filter-grid {
        grid-template-columns: 1fr 1fr !important;
    }

    .facturacion-view .filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .facturacion-view .fact-table tbody tr {
        background: var(--fact-surface) !important;
        border: 1px solid var(--fact-line) !important;
        border-radius: 14px !important;
        box-shadow: var(--fact-shadow) !important;
    }

    .facturacion-view .fact-table tbody td {
        border-bottom-color: var(--fact-line-soft) !important;
    }

    .facturacion-view .fact-table tbody td::before {
        color: var(--fact-muted) !important;
    }

    .facturacion-view .fact-table {
        border-spacing: 0;
    }

    .facturacion-view .fact-table tbody td:first-child,
    .facturacion-view .fact-table tbody td:last-child {
        border-left: 0;
        border-right: 0;
        border-radius: 0;
    }
}

@media (max-width: 420px) {
    .facturacion-view .stats-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .facturacion-view *,
    .facturacion-view *::before,
    .facturacion-view *::after {
        transition: none !important;
        animation: none !important;
    }
}

/* Full-width command desk redesign. */
.facturacion-view {
    min-height: 100dvh;
    background:
        radial-gradient(circle at 18% -8%, color-mix(in srgb, var(--fact-accent) 18%, transparent) 0, transparent 34%),
        radial-gradient(circle at 94% 4%, color-mix(in srgb, var(--fact-brand) 11%, transparent) 0, transparent 30%),
        linear-gradient(180deg, color-mix(in srgb, var(--fact-accent) 5%, #FBF8F2), #FFFFFF 42%, color-mix(in srgb, var(--fact-accent) 7%, #F7F2EA)) !important;
}

.facturacion-view .fact-header .container,
.facturacion-view > .container {
    width: 100% !important;
    max-width: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: clamp(22px, 2.8vw, 46px) !important;
    padding-right: clamp(22px, 2.8vw, 46px) !important;
}

.facturacion-view .fact-header {
    padding: 22px 0 0 !important;
    margin-bottom: 2px !important;
}

.facturacion-view .fact-header .flex.items-center.justify-between {
    width: 100%;
    min-height: 112px;
    align-items: center !important;
    padding: clamp(18px, 2vw, 26px) clamp(18px, 2.4vw, 30px);
    border: 1px solid color-mix(in srgb, var(--fact-accent) 22%, rgba(255,255,255,.22));
    border-radius: 24px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--fact-brand) 86%, #13201D), color-mix(in srgb, var(--fact-brand-2) 72%, var(--fact-brand)) 62%, color-mix(in srgb, var(--fact-accent) 24%, var(--fact-brand)) 100%);
    box-shadow: 0 22px 48px -34px color-mix(in srgb, var(--fact-brand) 70%, #000000);
    overflow: hidden;
    position: relative;
}

.facturacion-view .fact-header .flex.items-center.justify-between::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(115deg, rgba(255,255,255,.11) 0 1px, transparent 1px 28px),
        radial-gradient(circle at 78% -20%, rgba(255,255,255,.22), transparent 34%);
    opacity: .72;
    pointer-events: none;
}

.facturacion-view .fact-header .flex.items-center.justify-between > * {
    position: relative;
    z-index: 1;
}

.facturacion-view .fact-header h1 {
    color: #FFFFFF !important;
    text-shadow: 0 1px 0 rgba(0,0,0,.12);
}

.facturacion-view .fact-header h1 i {
    color: color-mix(in srgb, var(--fact-accent) 92%, #FFFFFF) !important;
}

.facturacion-view .fact-header h1 + p {
    max-width: 680px;
    color: rgba(255,255,255,.72) !important;
}

.facturacion-view .fact-header a[style] {
    background: rgba(255,255,255,.13) !important;
    border-color: rgba(255,255,255,.22) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.16), 0 12px 26px -18px rgba(0,0,0,.55) !important;
}

.facturacion-view .fact-header a[style]:hover {
    background: rgba(255,255,255,.2) !important;
    transform: translateY(-1px);
}

.facturacion-view .fact-header div[style*="width:1px"] {
    background: rgba(255,255,255,.2) !important;
}

.facturacion-view .fact-header span[style*="display:flex"] {
    background: rgba(255,255,255,.14) !important;
    border-color: rgba(255,255,255,.22) !important;
    color: #FFFFFF !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.16) !important;
    backdrop-filter: blur(10px);
}

.facturacion-view > .container {
    padding-top: 22px !important;
    padding-bottom: 36px !important;
}

.facturacion-view .stats-grid {
    width: 100%;
    grid-template-columns: repeat(4, minmax(210px, 1fr)) !important;
    gap: clamp(14px, 1.5vw, 22px) !important;
    margin-bottom: clamp(18px, 1.8vw, 26px) !important;
}

.facturacion-view .stats-grid > .stat-card {
    grid-column: auto !important;
    min-height: 142px;
    padding: 18px 19px 20px !important;
    border-radius: 20px !important;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 9%, #FFFFFF), #FFFFFF 48%),
        #FFFFFF !important;
    border-color: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 18%, var(--fact-line)) !important;
    box-shadow: 0 1px 2px rgba(17,24,39,.04), 0 20px 42px -34px color-mix(in srgb, var(--sc-accent, var(--fact-brand)) 44%, #111827) !important;
    isolation: isolate;
}

.facturacion-view .stats-grid > .stat-card::before {
    inset: auto -34px -54px auto;
    width: 136px;
    height: 136px;
    border-radius: 999px;
    background: radial-gradient(circle, color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 18%, transparent), transparent 68%) !important;
    opacity: 1;
}

.facturacion-view .stats-grid > .stat-card::after {
    inset: 0 auto 0 0;
    width: 5px;
    height: auto !important;
    border-radius: 20px 0 0 20px;
    background: linear-gradient(180deg, var(--sc-accent, var(--fact-accent)), color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 34%, #FFFFFF)) !important;
    opacity: 1 !important;
}

.facturacion-view .stats-grid > .stat-card:hover {
    transform: translateY(-3px) !important;
    border-color: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 32%, var(--fact-line)) !important;
    box-shadow: 0 4px 12px rgba(17,24,39,.05), 0 24px 48px -34px color-mix(in srgb, var(--sc-accent, var(--fact-brand)) 52%, #111827) !important;
}

.facturacion-view .stat-icon {
    width: 46px !important;
    height: 46px !important;
    border-radius: 14px !important;
    background: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 13%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 84%, #111827) !important;
    border-color: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 22%, var(--fact-line)) !important;
    box-shadow: inset 0 1px 0 rgba(255,255,255,.85);
}

.facturacion-view .stat-card p[style*="font-size:.67rem"] {
    color: color-mix(in srgb, var(--sc-accent, var(--fact-accent)) 74%, #111827) !important;
    letter-spacing: .08em !important;
}

.facturacion-view .stat-card p[style*="font-size:2rem"],
.facturacion-view .stat-card p[style*="font-size:1.5rem"] {
    color: var(--fact-heading) !important;
}

.facturacion-view .filter-card {
    width: 100%;
    border-radius: 22px !important;
    border-color: color-mix(in srgb, var(--fact-brand) 9%, var(--fact-line)) !important;
    box-shadow: 0 1px 2px rgba(17,24,39,.04), 0 22px 46px -36px rgba(17,24,39,.46) !important;
}

.facturacion-view .filter-card + .filter-card {
    margin-top: 18px;
}

.facturacion-view .filter-hd,
.facturacion-view .filter-card > div[style*="padding:12px 18px"] {
    min-height: 58px;
    padding: 16px 20px !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--fact-accent) 5%, #FFFFFF), #FFFFFF) !important;
}

.facturacion-view .filter-hd h3,
.facturacion-view .filter-card > div[style*="padding:12px 18px"] h3 {
    font-size: .9rem !important;
}

.facturacion-view .filter-form[style] {
    padding: 18px 20px 20px !important;
}

.facturacion-view .filter-form .filter-grid {
    grid-template-columns: minmax(320px, 1.8fr) minmax(170px, .85fr) minmax(170px, .85fr) minmax(180px, .85fr) auto !important;
    gap: 14px !important;
    align-items: end !important;
}

.facturacion-view .lc-input {
    min-height: 46px;
    border-radius: 13px !important;
    background: color-mix(in srgb, var(--fact-accent) 2%, #FFFFFF) !important;
}

.facturacion-view .btn-filter,
.facturacion-view .btn-clear {
    min-height: 46px;
    border-radius: 13px !important;
}

.facturacion-view .fact-table {
    width: 100%;
    min-width: 980px;
    border-spacing: 0 10px !important;
}

.facturacion-view .fact-table thead th {
    padding: 13px 16px !important;
    background: transparent !important;
    border-bottom: 1px solid var(--fact-line) !important;
    color: var(--fact-muted) !important;
}

.facturacion-view .fact-table tbody td {
    padding: 15px 16px !important;
}

.facturacion-view .fact-table tbody tr {
    box-shadow: 0 1px 2px rgba(17,24,39,.035), 0 12px 28px -26px rgba(17,24,39,.42) !important;
}

.facturacion-view .btn-ver {
    min-height: 38px;
    border-radius: 12px !important;
}

@media (min-width: 1600px) {
    .facturacion-view .fact-table tbody td,
    .facturacion-view .fact-table thead th {
        padding-left: 20px !important;
        padding-right: 20px !important;
    }
}

@media (max-width: 1100px) {
    .facturacion-view .stats-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .facturacion-view .filter-form .filter-grid {
        grid-template-columns: 1fr 1fr !important;
    }

    .facturacion-view .filter-actions {
        grid-column: 1 / -1;
    }
}

@media (max-width: 640px) {
    .facturacion-view .fact-header .container,
    .facturacion-view > .container {
        padding-left: 14px !important;
        padding-right: 14px !important;
    }

    .facturacion-view .fact-header .flex.items-center.justify-between {
        min-height: auto;
        padding: 16px;
        border-radius: 18px;
        align-items: flex-start !important;
    }

    .facturacion-view .stats-grid {
        grid-template-columns: 1fr !important;
    }

    .facturacion-view .filter-form .filter-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- ═══════════════════ FACTURACIÓN ══════════════════════════ -->
<div class="facturacion-view">

    <!-- ── Header ── -->
    <div class="fact-header">
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="/habitaciones"
                       style="background:rgba(255,255,255,.12);width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;transition:background .2s;"
                       onmouseover="this.style.background='rgba(255,255,255,.22)'"
                       onmouseout="this.style.background='rgba(255,255,255,.12)'">
                        <i class="fas fa-arrow-left text-sm text-white"></i>
                    </a>
                    <div style="width:1px;height:30px;background:rgba(255,255,255,.15);"></div>
                    <div>
                        <h1 class="text-xl font-bold flex items-center gap-2" style="line-height:1.2;">
                            <i class="fas fa-file-invoice" style="color:var(--lc-gold);opacity:.9;"></i>
                            Facturación
                        </h1>
                        <p style="font-size:.72rem;color:rgba(255,255,255,.6);margin-top:1px;">
                            Gestión de solicitudes de factura · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:10px;">
                    <!-- Live badge -->
                    <span style="display:flex;align-items:center;gap:5px;background:rgba(200,169,106,.18);border:1px solid rgba(200,169,106,.35);color:var(--lc-gold);padding:4px 10px;border-radius:20px;font-size:.7rem;font-weight:700;">
                        <i class="fas fa-clock text-xs"></i>
                        <?= date('d/m/Y H:i') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-5" style="max-width:1200px;">

        <!-- ── Flash ── -->
        <?php if ($mensaje): ?>
            <div class="flash-msg flash-<?= $mensaje['tipo'] ?>">
                <i class="fas fa-<?= $mensaje['tipo'] === 'success' ? 'check-circle' : ($mensaje['tipo'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <?= htmlspecialchars($mensaje['texto']) ?>
            </div>
        <?php endif; ?>

        <!-- ── Stat widgets ── -->
        <div class="stats-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">

            <!-- Pendientes -->
            <div class="stat-card" style="--sc-accent:#F59E0B;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#B45309;">Pendientes</p>
                        <p style="font-size:2rem;font-weight:800;color:#92400E;line-height:1.1;margin-top:4px;"><?= $estadisticas['pendientes'] ?? 0 ?></p>
                        <div style="display:flex;gap:10px;margin-top:5px;">
                            <span style="font-size:.67rem;color:#B45309;display:flex;align-items:center;gap:3px;">
                                <i class="fas fa-user" style="font-size:.6rem;"></i>
                                <?= $estadisticas['pendientes_cliente'] ?? 0 ?> cliente
                            </span>
                            <span style="font-size:.67rem;color:#B45309;display:flex;align-items:center;gap:3px;">
                                <i class="fas fa-building" style="font-size:.6rem;"></i>
                                <?= $estadisticas['pendientes_interno'] ?? 0 ?> interno
                            </span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);color:#D97706;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <!-- En Proceso -->
            <div class="stat-card" style="--sc-accent:#3B82F6;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#1E40AF;">En Proceso</p>
                        <p style="font-size:2rem;font-weight:800;color:#1E40AF;line-height:1.1;margin-top:4px;"><?= $estadisticas['en_proceso'] ?? 0 ?></p>
                        <p style="font-size:.67rem;color:#3B82F6;margin-top:5px;">Con datos fiscales</p>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#DBEAFE,#BFDBFE);color:#2563EB;">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>

            <!-- Completadas mes -->
            <div class="stat-card" style="--sc-accent:#5C7A4E;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#3D5234;">Facturadas (Mes)</p>
                        <p style="font-size:2rem;font-weight:800;color:#3D5234;line-height:1.1;margin-top:4px;"><?= $estadisticas['completadas_mes'] ?? 0 ?></p>
                        <p style="font-size:.67rem;color:var(--lc-green-light);margin-top:5px;"><?= date('F Y') ?></p>
                    </div>
                    <div class="stat-icon" style="background:rgba(92,122,78,.12);color:#3D5234;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Monto pendiente -->
            <div class="stat-card" style="--sc-accent:#9333EA;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                    <div>
                        <p style="font-size:.67rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#6D28D9;">Monto Pendiente</p>
                        <p style="font-size:1.5rem;font-weight:800;color:#6D28D9;line-height:1.1;margin-top:4px;">
                            $<?= number_format($estadisticas['monto_pendiente'] ?? 0, 2) ?>
                        </p>
                        <p style="font-size:.67rem;color:#7C3AED;margin-top:5px;">Por facturar</p>
                    </div>
                    <div class="stat-icon" style="background:linear-gradient(135deg,#EDE9FE,#DDD6FE);color:#7C3AED;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Filters ── -->
        <div class="filter-card" style="margin-bottom:16px;">
            <div class="filter-hd">
                <h3 style="font-size:.82rem;font-weight:700;color:#3D5234;display:flex;align-items:center;gap:6px;">
                    <div style="width:22px;height:22px;border-radius:6px;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-filter" style="font-size:.6rem;color:var(--lc-green);"></i>
                    </div>
                    Filtros
                </h3>
                <!-- Mobile toggle -->
                <button type="button" class="filter-toggle-btn"
                        onclick="this.closest('.filter-card').classList.toggle('filters-open')"
                        style="display:none;background:rgba(92,122,78,.08);border:1.5px solid #D5E4CB;border-radius:7px;padding:4px 10px;font-size:.72rem;color:#4A6340;cursor:pointer;font-weight:700;align-items:center;gap:4px;">
                    <i class="fas fa-chevron-down" style="font-size:.6rem;"></i> Mostrar
                </button>
            </div>

            <form method="GET" action="<?= url('facturacion') ?>" class="filter-form" style="padding:14px 18px;">
                <div class="filter-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr auto;gap:10px;align-items:end;">

                    <div>
                        <label class="filter-label">Buscar</label>
                        <input type="text" name="buscar"
                               value="<?= htmlspecialchars($buscar ?? '') ?>"
                               placeholder="Nombre, RFC, # reservación..."
                               class="lc-input">
                    </div>

                    <div>
                        <label class="filter-label">Tipo</label>
                        <select name="tipo" class="lc-input">
                            <option value="">Todos</option>
                            <option value="cliente"      <?= ($filtro_tipo ?? '') === 'cliente'      ? 'selected' : '' ?>>Cliente</option>
                            <option value="uso_interno"  <?= ($filtro_tipo ?? '') === 'uso_interno'  ? 'selected' : '' ?>>Público General</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label">Estatus</label>
                        <select name="estatus" class="lc-input">
                            <option value="">Todos</option>
                            <option value="pendiente"   <?= ($filtro_estatus ?? '') === 'pendiente'   ? 'selected' : '' ?>>Pendiente</option>
                            <option value="en_proceso"  <?= ($filtro_estatus ?? '') === 'en_proceso'  ? 'selected' : '' ?>>En Proceso</option>
                            <option value="completada"  <?= ($filtro_estatus ?? '') === 'completada'  ? 'selected' : '' ?>>Completada</option>
                            <option value="cancelada"   <?= ($filtro_estatus ?? '') === 'cancelada'   ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                    </div>

                    <div>
                        <label class="filter-label">Desde</label>
                        <input type="date" name="fecha_desde"
                               value="<?= htmlspecialchars($fecha_desde ?? '') ?>"
                               class="lc-input">
                    </div>

                    <div class="filter-actions" style="display:flex;gap:6px;">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search text-xs"></i> Filtrar
                        </button>
                        <a href="<?= url('facturacion') ?>" class="btn-clear">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- ── Table panel ── -->
        <div class="filter-card">
            <!-- Panel header -->
            <div style="padding:12px 18px;border-bottom:1px solid #EAF0E5;background:linear-gradient(135deg,#5C7A4E,#4A6340);display:flex;align-items:center;justify-content:space-between;">
                <h3 style="font-size:.85rem;font-weight:700;color:white;display:flex;align-items:center;gap:8px;">
                    <div style="background:rgba(255,255,255,.18);width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-list" style="font-size:.65rem;color:white;"></i>
                    </div>
                    Solicitudes de Factura
                </h3>
                <span style="background:rgba(255,255,255,.2);color:white;font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;">
                    <?= $total_registros ?? 0 ?> registro<?= ($total_registros ?? 0) != 1 ? 's' : '' ?>
                </span>
            </div>

            <?php if (empty($solicitudes)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <p style="font-size:.95rem;font-weight:700;color:#6B7280;margin-bottom:4px;">No hay solicitudes de factura</p>
                    <p style="font-size:.8rem;color:#9CA3AF;">Las solicitudes se generan automáticamente al hacer check-in</p>
                </div>

            <?php else: ?>
                <div style="overflow-x:auto;" class="lc-scroll">
                    <table class="fact-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Reservación</th>
                                <th>Huésped</th>
                                <th>Tipo</th>
                                <th>Estatus</th>
                                <th>Monto</th>

                                <th>RFC</th>
                                <th>Fecha</th>
                                <th style="text-align:center;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($solicitudes as $sol): ?>
                            <tr>
                                <td data-label="ID">
                                    <span class="id-badge"><?= $sol['id'] ?></span>
                                </td>

                                <td data-label="Reservación">
                                    <a href="<?= url('reservaciones/ver/' . $sol['reservacion_id']) ?>" class="res-link">
                                        #<?= $sol['reservacion_id'] ?>
                                    </a>
                                </td>

                                <td data-label="Huésped">
                                    <span style="font-weight:600;color:#1F2937;"><?= htmlspecialchars($sol['huesped_nombre']) ?></span>
                                    <?php if (!empty($sol['huesped_telefono'])): ?>
                                        <br>
                                        <span style="font-size:.68rem;color:#9CA3AF;display:flex;align-items:center;gap:3px;margin-top:2px;">
                                            <i class="fas fa-phone" style="font-size:.55rem;"></i>
                                            <?= htmlspecialchars($sol['huesped_telefono']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Tipo">
                                    <?php if ($sol['tipo'] === 'cliente'): ?>
                                        <span class="badge badge-cliente">
                                            <i class="fas fa-user" style="font-size:.55rem;"></i> Cliente
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-uso-interno">
                                            <i class="fas fa-building" style="font-size:.55rem;"></i> Público General
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Estatus">
                                    <?php
                                    $iconos = [
                                        'pendiente'  => 'clock',
                                        'en_proceso' => 'spinner',
                                        'completada' => 'check-circle',
                                        'cancelada'  => 'times-circle',
                                    ];
                                    $labels = [
                                        'pendiente'  => 'Pendiente',
                                        'en_proceso' => 'En Proceso',
                                        'completada' => 'Completada',
                                        'cancelada'  => 'Cancelada',
                                    ];
                                    ?>
                                    <span class="badge badge-<?= $sol['estatus'] ?>">
                                        <i class="fas fa-<?= $iconos[$sol['estatus']] ?? 'circle' ?>" style="font-size:.55rem;"></i>
                                        <?= $labels[$sol['estatus']] ?? $sol['estatus'] ?>
                                    </span>
                                </td>

                                <td data-label="Monto">
                                    <span style="font-weight:700;color:#3D5234;">$<?= number_format($sol['monto_total'], 2) ?></span>
                                </td>


                                <td data-label="RFC">
                                    <?php if (!empty($sol['rfc'])): ?>
                                        <span class="rfc-badge"><?= htmlspecialchars($sol['rfc']) ?></span>
                                    <?php else: ?>
                                        <span style="font-size:.72rem;color:#D1D5DB;font-style:italic;">Sin RFC</span>
                                    <?php endif; ?>
                                </td>

                                <td data-label="Fecha" style="white-space:nowrap;">
                                    <span style="font-size:.78rem;font-weight:600;color:#374151;"><?= date('d/m/Y', strtotime($sol['created_at'])) ?></span>
                                    <br>
                                    <span style="font-size:.68rem;color:#9CA3AF;"><?= date('H:i', strtotime($sol['created_at'])) ?></span>
                                </td>

                                <td data-label="Acción" style="text-align:center;">
                                    <a href="<?= url('facturacion/ver/' . $sol['id']) ?>" class="btn-ver">
                                        <i class="fas fa-eye text-xs"></i> Ver
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (($total_paginas ?? 1) > 1): ?>
                    <div style="padding:14px 18px;border-top:1px solid #EAF0E5;display:flex;align-items:center;justify-content:center;">
                        <div class="pagination">
                            <?php if ($pagina_actual > 1): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $pagina_actual - 1]))) ?>" class="page-btn">
                                    <i class="fas fa-chevron-left" style="font-size:.65rem;"></i>
                                </a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $pagina_actual - 2); $i <= min($total_paginas, $pagina_actual + 2); $i++): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $i]))) ?>"
                                   class="page-btn <?= $i === $pagina_actual ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($pagina_actual < $total_paginas): ?>
                                <a href="<?= url('facturacion?' . http_build_query(array_merge($_GET, ['page' => $pagina_actual + 1]))) ?>" class="page-btn">
                                    <i class="fas fa-chevron-right" style="font-size:.65rem;"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
// Filtros colapsables en mobile
(function() {
    if (window.innerWidth > 640) return;

    const filterCards = document.querySelectorAll('.filter-card');
    filterCards.forEach(card => {
        const form = card.querySelector('.filter-form');
        if (!form) return;

        const inputs  = form.querySelectorAll('input[type="text"], input[type="date"]');
        const selects = form.querySelectorAll('select');
        let hasActive = false;

        inputs.forEach(i  => { if (i.value)  hasActive = true; });
        selects.forEach(s => { if (s.value)  hasActive = true; });

        if (!hasActive) {
            form.style.display = 'none';
            card.classList.remove('filters-open');
        } else {
            card.classList.add('filters-open');
        }
    });

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.filter-toggle-btn');
        if (!btn) return;
        const card  = btn.closest('.filter-card');
        const form  = card.querySelector('.filter-form');
        if (!form) return;
        const isOpen = card.classList.contains('filters-open');
        form.style.display = isOpen ? 'none' : 'block';
        card.classList.toggle('filters-open', !isOpen);
        btn.innerHTML = isOpen
            ? '<i class="fas fa-chevron-down" style="font-size:.6rem;"></i> Mostrar'
            : '<i class="fas fa-chevron-up" style="font-size:.6rem;"></i> Ocultar';
    });
})();
</script>
