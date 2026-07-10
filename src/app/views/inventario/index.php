<style>
/* ══════════════════════════════════════════
   Control de Inventario
   Sage green / gold / warm cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:        #5C7A4E;
    --lc-green-dark:   #4A6340;
    --lc-green-deep:   #3D5234;
    --lc-green-light:  #7A9B6A;
    --lc-gold:         #C8A96A;
    --lc-gold-light:   #D9BF8A;
    --lc-cream:        #F7F4EE;
    --lc-cream-mid:    #EEE9DE;
}

/* Background */
.inv-page { background: linear-gradient(145deg,#EFF4EC 0%,#E8EFE3 45%,#F4F1EB 100%); min-height:100vh; }

/* ── Top bar ─────────────────────────────── */
.inv-topbar {
    background: #fff;
    border-bottom: 1px solid #DDE8D5;
    position: relative;
}
.inv-topbar::after {
    content:'';
    position:absolute; bottom:0; left:0; right:0; height:2px;
    background: linear-gradient(90deg, var(--lc-green-deep), var(--lc-gold), var(--lc-green-deep));
}

/* ── Stat pills ─────────────────────────── */
.inv-pill {
    display:inline-flex; align-items:center; gap:6px;
    background:#F0F5ED; border:1px solid #D5E4CB;
    border-radius:20px; padding:3px 10px;
    font-size:0.7rem; font-weight:600; color:#4A6340;
    transition: box-shadow .2s;
}
.inv-pill:hover { box-shadow: 0 3px 8px rgba(92,122,78,.15); }
.inv-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ── Action buttons ──────────────────────── */
.btn-inv {
    display:inline-flex; align-items:center; justify-content:center;
    gap:6px; padding:7px 13px; border-radius:9px;
    font-size:0.78rem; font-weight:700;
    transition: transform .2s, box-shadow .2s; text-decoration:none;
    border: none; cursor: pointer;
}
.btn-inv:hover { transform:translateY(-1px); }
.btn-inv.primary { background:linear-gradient(135deg,#5C7A4E,#4A6340); color:#fff; }
.btn-inv.primary:hover { box-shadow: 0 6px 16px rgba(74,99,64,.3); }
.btn-inv.entrada { background:linear-gradient(135deg,#10b981,#059669); color:#fff; }
.btn-inv.entrada:hover { box-shadow: 0 6px 16px rgba(16,185,129,.3); }
.btn-inv.salida { background:linear-gradient(135deg,#f59e0b,#d97706); color:#fff; }
.btn-inv.salida:hover { box-shadow: 0 6px 16px rgba(245,158,11,.3); }
.btn-inv.movimientos { background:linear-gradient(135deg,#3B6FD6,#2563eb); color:#fff; }
.btn-inv.movimientos:hover { box-shadow: 0 6px 16px rgba(59,111,214,.3); }
.btn-inv.pdf { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; }
.btn-inv.pdf:hover { box-shadow: 0 6px 16px rgba(239,68,68,.3); }

/* ── Stat widgets ────────────────────────── */
.stat-widget {
    background:#fff; border-radius:16px;
    border:1px solid #E0EBD8;
    padding:18px 16px; position:relative; overflow:hidden;
    transition: transform .25s, box-shadow .25s;
}
.stat-widget::before {
    content:''; position:absolute;
    bottom:-20px; right:-20px;
    width:70px; height:70px; border-radius:50%;
    opacity:.06; background: var(--accent-color, #5C7A4E);
    pointer-events:none;
}
.stat-widget:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(92,122,78,.12); }
.stat-icon {
    width:40px; height:40px; border-radius:11px;
    display:flex; align-items:center; justify-content:center;
    font-size:15px; flex-shrink:0;
}
.bar-track { height:3px; background:#E5EDE0; border-radius:2px; margin-top:10px; overflow:hidden; }
.bar-fill  { height:100%; border-radius:2px; transition:width 1s ease; }

/* ── Panels ──────────────────────────────── */
.inv-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.panel-hd {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));
    padding:13px 16px;
    display:flex; align-items:center; justify-content:space-between;
}
.panel-hd-icon {
    width:28px; height:28px; border-radius:8px;
    background:rgba(255,255,255,.18);
    display:flex; align-items:center; justify-content:center;
}

/* ── Search ──────────────────────────────── */
.inv-search {
    padding:6px 12px 6px 30px;
    border:1.5px solid rgba(255,255,255,.5);
    border-radius:8px; font-size:0.78rem;
    background:rgba(255,255,255,.88); color:#374151;
    width:160px; transition:width .25s, border-color .2s, box-shadow .2s;
}
.inv-search::placeholder { color:#9CA3AF; }
.inv-search:focus {
    outline:none; width:200px;
    border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.2);
    background:#fff;
}

/* ── Table (desktop) ────────────────────── */
.inv-th {
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    padding:10px 8px; white-space:nowrap;
}
.inv-tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
.inv-tr:hover { background:#F7FCF4; }
.inv-tr:last-child { border-bottom:none; }
.code-tag {
    font-family:ui-monospace,monospace; font-size:.7rem;
    background:#F0F5ED; color:#4A6340;
    border:1px solid #D0E0C8; padding:2px 7px; border-radius:5px;
}

/* ── Stock badges ────────────────────────── */
.badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 9px; border-radius:20px;
    font-size:.69rem; font-weight:700;
}
.badge-ok   { background:rgba(16,185,129,.1);  color:#065F46; border:1px solid rgba(16,185,129,.22); }
.badge-low  { background:rgba(245,158,11,.1);  color:#92400E; border:1px solid rgba(245,158,11,.22); }
.badge-out  { background:rgba(239,68,68,.1);   color:#991B1B; border:1px solid rgba(239,68,68,.22);  }
.badge-auto { background:rgba(92,122,78,.1);   color:#3D5234; border:1px solid rgba(92,122,78,.22); }

/* ── Action icons ────────────────────────── */
.act-btn {
    width:28px; height:28px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.7rem; transition:background .15s; border:none; cursor:pointer;
    text-decoration:none;
}
.act-btn.edit  { color:#5C7A4E; background:transparent; }
.act-btn.edit:hover  { background:#EEF4EB; }
.act-btn.del   { color:#DC2626; background:transparent; }
.act-btn.del:hover   { background:#FEF2F2; }
.act-btn.del.is-confirming {
    background:#FEF2F2;
    box-shadow: inset 0 0 0 1px rgba(220,38,38,.22);
}

/* ── Movement items ──────────────────────── */
.mov-row { padding:11px 14px; border-bottom:1px solid #F0F5ED; transition:background .15s; }
.mov-row:hover { background:#FAFDF8; }
.mov-row:last-child { border-bottom:none; }
.mov-icon-w {
    width:30px; height:30px; border-radius:9px;
    display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0;
}

/* ── Alerts ──────────────────────────────── */
.alert-stock {
    border-radius:0 10px 10px 0; padding:12px 14px;
    display:flex; align-items:flex-start; gap:10px;
    margin-bottom:16px;
}
.alert-stock.amber { background:linear-gradient(135deg,#FFFBEB,#FEF3C7); border:1px solid #F5D48A; }
.alert-stock.green { background:linear-gradient(135deg,#ECFDF5,#D1FAE5); border:1px solid #B9D6B1; }

/* ── Custom scrollbar ────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

/* ══════════════════════════════════════════
   MOBILE CARD VIEW - Products
   ══════════════════════════════════════════ */
.producto-card-mobile {
    background:#fff;
    border:1px solid #E0EBD8;
    border-radius:10px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    transition: background .15s;
}
.producto-card-mobile:hover { background:#F7FCF4; }
.producto-card-mobile + .producto-card-mobile { margin-top:6px; }

.producto-card-mobile .prod-info { flex:1; min-width:0; }
.producto-card-mobile .prod-name {
    font-size:.78rem; font-weight:600; color:#374151;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.producto-card-mobile .prod-cat {
    font-size:.65rem; color:#9CA3AF; margin-top:1px;
}
.producto-card-mobile .prod-actions {
    display:flex; align-items:center; gap:4px; flex-shrink:0;
}

/* Hide table on mobile, show cards */
@media (max-width: 639px) {
    .inv-table-desktop { display:none !important; }
    .inv-cards-mobile  { display:block !important; }
    .inv-search { width:120px; font-size:.72rem; }
    .inv-search:focus { width:150px; }
}
@media (min-width: 640px) {
    .inv-cards-mobile  { display:none !important; }
}

/* ── Config button (more visible) ────────── */
.btn-config-inv {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:8px;
    font-size:.75rem; font-weight:600;
    background:linear-gradient(135deg, #F0F5ED, #E5EDE0);
    color:#4A6340; border:1px solid #C8D8BE;
    transition: all .2s; text-decoration:none;
}
.btn-config-inv:hover {
    background:linear-gradient(135deg, #E5EDE0, #D5E4CB);
    box-shadow: 0 3px 10px rgba(92,122,78,.15);
    transform:translateY(-1px);
}
</style>

<style id="inventory-boutique">
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.inv-page {
    --inv-brand: var(--brand-primary, #1B2746);
    --inv-brand-2: var(--brand-secondary, #0F172A);
    --inv-accent: var(--brand-accent, #BD9441);
    --inv-ivory: color-mix(in srgb, var(--inv-accent) 8%, #F8F5ED);
    --inv-ivory-2: color-mix(in srgb, var(--inv-accent) 6%, #FBF9F4);
    --inv-surface: color-mix(in srgb, var(--inv-accent) 2%, #FFFFFF);
    --inv-surface-warm: color-mix(in srgb, var(--inv-accent) 5%, #FFFFFF);
    --inv-line: color-mix(in srgb, var(--inv-accent) 24%, #E7DEC9);
    --inv-line-soft: color-mix(in srgb, var(--inv-accent) 13%, #F0ECE2);
    --inv-muted: color-mix(in srgb, var(--inv-brand-2) 48%, #94A3B8);
    --inv-success: #1E9E63;
    --inv-success-bg: #E7F4EC;
    --inv-warning: #C2841C;
    --inv-warning-bg: #FAF0DC;
    --inv-danger: #D64539;
    --inv-danger-bg: #FBE9E7;
    --inv-info: #2F77E0;
    --inv-info-bg: #E6EFFC;
    --inv-auto: #6F5FD2;
    --inv-auto-bg: #EFECFB;
    --inv-shadow: 0 2px 8px color-mix(in srgb, var(--inv-brand-2) 6%, transparent), 0 12px 28px color-mix(in srgb, var(--inv-brand-2) 7%, transparent);
    --inv-serif: 'Cormorant Garamond', Georgia, serif;
    --inv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--inv-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--inv-ivory-2), var(--inv-ivory) 56%, #F7F2EA) !important;
    color: var(--inv-brand-2);
    font-family: var(--inv-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.inv-page *,
.inv-page *::before,
.inv-page *::after {
    box-sizing: border-box;
}

.inv-page :where(p, span, a, button, input, select, textarea, th, td) {
    font-family: var(--inv-sans);
}

.inv-page > .px-4,
.inv-page .inv-topbar > div {
    max-width: 1440px;
    margin-left: auto;
    margin-right: auto;
}

.inv-page .inv-topbar {
    background: transparent !important;
    border-bottom: none !important;
}

.inv-page .inv-topbar::after {
    display: none !important;
}

.inv-page .inv-topbar > div {
    padding-top: 26px !important;
}

.inv-page .inv-topbar > div > .flex {
    align-items: flex-start !important;
}

.inv-page .inv-topbar h1 {
    color: var(--inv-brand) !important;
    font-family: var(--inv-serif);
    font-size: clamp(2rem, 3.2vw, 2.6rem) !important;
    font-weight: 650 !important;
    line-height: .95 !important;
    letter-spacing: 0 !important;
}

.inv-page .inv-topbar h1 + p {
    margin-top: 8px !important;
    color: var(--inv-muted) !important;
    font-weight: 600;
}

.inv-page .stat-icon {
    border-radius: 12px !important;
    border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
}

.inv-page .inv-topbar .stat-icon {
    background: linear-gradient(150deg, var(--inv-brand), var(--inv-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--inv-brand) 55%, transparent);
}

.inv-page .btn-inv,
.inv-page .btn-config-inv,
.inv-page .act-btn {
    min-height: 44px;
    border-radius: 11px !important;
    transition: transform .18s ease, box-shadow .18s ease, filter .18s ease, background .18s ease, border-color .18s ease !important;
}

.inv-page .act-btn {
    width: 44px !important;
    min-width: 44px !important;
    height: 44px !important;
}

.inv-page .inv-action-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(126px, 1fr));
    gap: 8px;
    width: min(100%, 760px);
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    padding-bottom: 2px;
    scrollbar-width: thin;
    -webkit-overflow-scrolling: touch;
}

.inv-page .inv-action-grid::-webkit-scrollbar {
    height: 4px;
}

.inv-page .inv-action-grid::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--inv-brand) 28%, transparent);
    border-radius: 999px;
}

.inv-page .inv-action-grid .btn-inv {
    width: 100%;
    min-height: 44px;
    gap: 8px;
    padding: 0 12px;
    white-space: nowrap;
    line-height: 1;
    font-size: .75rem;
}

.inv-page .inv-action-grid .btn-inv i {
    width: 14px;
    min-width: 14px;
    text-align: center;
    font-size: .76rem !important;
}

.inv-page .inv-action-grid .btn-inv span {
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
}

.inv-page .btn-inv:hover,
.inv-page .btn-config-inv:hover,
.inv-page .act-btn:hover {
    transform: translateY(-1px) !important;
}

.inv-page .btn-inv:active,
.inv-page .btn-config-inv:active,
.inv-page .act-btn:active {
    transform: translateY(0) !important;
}

.inv-page .btn-inv.primary {
    background: linear-gradient(135deg, var(--inv-accent), color-mix(in srgb, var(--inv-accent) 76%, #000)) !important;
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--inv-accent) 58%, transparent);
}

.inv-page .btn-inv.entrada {
    background: linear-gradient(135deg, var(--inv-success), #0F7048) !important;
}

.inv-page .btn-inv.salida {
    background: linear-gradient(135deg, var(--inv-warning), color-mix(in srgb, var(--inv-warning) 76%, #000)) !important;
}

.inv-page .btn-inv.movimientos {
    background: linear-gradient(135deg, var(--inv-info), color-mix(in srgb, var(--inv-info) 76%, #000)) !important;
}

.inv-page .btn-inv.pdf {
    background: linear-gradient(135deg, var(--inv-danger), color-mix(in srgb, var(--inv-danger) 78%, #000)) !important;
}

.inv-page .inv-pill {
    background: var(--inv-surface) !important;
    border: 1px solid var(--inv-line) !important;
    border-radius: 999px !important;
    color: var(--inv-brand-2) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--inv-brand-2) 4%, transparent);
}

.inv-page .inv-dot {
    box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 11%, transparent);
}

.inv-page .alert-stock {
    border: 1px solid var(--inv-line) !important;
    border-left-width: 1px !important;
    border-radius: 14px !important;
    box-shadow: var(--inv-shadow) !important;
}

.inv-page .alert-stock.amber {
    background: linear-gradient(135deg, var(--inv-warning-bg), color-mix(in srgb, var(--inv-warning) 7%, #FFFFFF)) !important;
    border-color: color-mix(in srgb, var(--inv-warning) 24%, var(--inv-line)) !important;
}

.inv-page .alert-stock.green {
    background: linear-gradient(135deg, var(--inv-success-bg), color-mix(in srgb, var(--inv-success) 6%, #FFFFFF)) !important;
    border-color: color-mix(in srgb, var(--inv-success) 24%, var(--inv-line)) !important;
}

.inv-page .alert-stock p,
.inv-page .alert-stock strong {
    color: var(--inv-brand-2) !important;
}

.inv-page .stat-widget,
.inv-page .inv-panel,
.inv-page .producto-card-mobile {
    background: var(--inv-surface) !important;
    border: 1px solid var(--inv-line) !important;
    box-shadow: var(--inv-shadow) !important;
}

.inv-page .stat-widget {
    border-radius: 16px !important;
    transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease !important;
}

.inv-page .stat-widget::before {
    display: none !important;
}

.inv-page .stat-widget:hover,
.inv-page .inv-panel:hover {
    transform: translateY(-1px) !important;
    border-color: color-mix(in srgb, var(--inv-accent) 42%, var(--inv-line)) !important;
    box-shadow: 0 16px 34px color-mix(in srgb, var(--inv-brand-2) 10%, transparent) !important;
}

.inv-page .bar-track {
    background: var(--inv-line-soft) !important;
    height: 4px !important;
}

.inv-page .bar-fill {
    transition: width .6s ease !important;
}

.inv-page .panel-hd {
    background: linear-gradient(135deg, var(--inv-brand), var(--inv-brand-2)) !important;
    border-bottom: 1px solid rgba(255,255,255,.14);
}

.inv-page .panel-hd-icon {
    border: 1px solid rgba(255,255,255,.2);
    background: color-mix(in srgb, var(--inv-accent) 28%, rgba(255,255,255,.12)) !important;
}

.inv-page .inv-search {
    min-height: 44px;
    background: rgba(255,255,255,.92) !important;
    border: 1px solid rgba(255,255,255,.58) !important;
    border-radius: 11px !important;
    color: var(--inv-brand-2) !important;
    font-weight: 700;
}

.inv-page .inv-search:focus {
    border-color: var(--inv-accent) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--inv-accent) 24%, transparent) !important;
}

.inv-page .inv-th {
    color: var(--inv-muted) !important;
    letter-spacing: .07em !important;
    background: var(--inv-surface-warm);
    border-bottom: 1px solid var(--inv-line);
}

.inv-page .inv-tr {
    border-bottom-color: var(--inv-line-soft) !important;
}

.inv-page .inv-tr:hover,
.inv-page .mov-row:hover,
.inv-page .producto-card-mobile:hover {
    background: var(--inv-surface-warm) !important;
}

.inv-page .code-tag {
    background: var(--inv-surface-warm) !important;
    border-color: var(--inv-line) !important;
    color: var(--inv-brand) !important;
}

.inv-page .badge {
    border-radius: 999px !important;
    font-variant-numeric: tabular-nums;
}

.inv-page .badge-ok {
    background: var(--inv-success-bg) !important;
    color: #0F7048 !important;
    border-color: color-mix(in srgb, var(--inv-success) 28%, #D8EFE4) !important;
}

.inv-page .badge-low {
    background: var(--inv-warning-bg) !important;
    color: #8A5A12 !important;
    border-color: color-mix(in srgb, var(--inv-warning) 28%, #F1DFC0) !important;
}

.inv-page .badge-out {
    background: var(--inv-danger-bg) !important;
    color: #9D3028 !important;
    border-color: color-mix(in srgb, var(--inv-danger) 28%, #F3D7D4) !important;
}

.inv-page .badge-auto {
    background: var(--inv-auto-bg) !important;
    color: #5145A8 !important;
    border-color: color-mix(in srgb, var(--inv-auto) 28%, #E4DFF8) !important;
}

.inv-page .producto-card-mobile.is-auto-stock {
    border-color: color-mix(in srgb, var(--inv-auto) 32%, var(--inv-line)) !important;
    background: linear-gradient(135deg, var(--inv-surface), color-mix(in srgb, var(--inv-auto) 4%, #FFFFFF)) !important;
}

.inv-page .prod-name,
.inv-page .text-gray-800 {
    color: var(--inv-brand-2) !important;
}

.inv-page .text-gray-300,
.inv-page .text-gray-400,
.inv-page .text-gray-500,
.inv-page .prod-cat {
    color: var(--inv-muted) !important;
}

.inv-page .text-emerald-600 { color: var(--inv-success) !important; }
.inv-page .text-amber-500,
.inv-page .text-amber-600 { color: var(--inv-warning) !important; }
.inv-page .text-red-500,
.inv-page .text-red-600 { color: var(--inv-danger) !important; }

.inv-page .act-btn.edit {
    color: var(--inv-brand) !important;
    background: var(--inv-surface-warm) !important;
    border: 1px solid var(--inv-line) !important;
}

.inv-page .act-btn.edit:hover {
    color: var(--inv-accent) !important;
    background: color-mix(in srgb, var(--inv-accent) 12%, #FFFFFF) !important;
}

.inv-page .act-btn.del {
    color: var(--inv-danger) !important;
    background: var(--inv-danger-bg) !important;
    border: 1px solid color-mix(in srgb, var(--inv-danger) 20%, #F3D7D4) !important;
}

.inv-page .mov-row {
    border-bottom-color: var(--inv-line-soft) !important;
}

.inv-page .mov-icon-w {
    border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
}

.inv-page .btn-config-inv {
    min-height: 44px;
    background: var(--inv-surface-warm) !important;
    color: var(--inv-brand-2) !important;
    border: 1px solid var(--inv-line) !important;
    box-shadow: none !important;
}

.inv-page .btn-config-inv:hover {
    color: color-mix(in srgb, var(--inv-accent) 72%, #000) !important;
    border-color: color-mix(in srgb, var(--inv-accent) 42%, var(--inv-line)) !important;
    background: color-mix(in srgb, var(--inv-accent) 10%, #FFFFFF) !important;
}

.inv-page .lc-scroll::-webkit-scrollbar { width: 5px; height: 5px; }
.inv-page .lc-scroll::-webkit-scrollbar-track { background: var(--inv-ivory); border-radius: 10px; }
.inv-page .lc-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--inv-accent), #fff 34%); border-radius: 10px; }
.inv-page .lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--inv-accent); }

.inv-page a:focus-visible,
.inv-page button:focus-visible,
.inv-page input:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--inv-accent) 30%, transparent) !important;
}

.inv-page .inv-title-lockup {
    display: grid !important;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(960px, 100%);
}

.inv-page .inv-title-copy {
    min-width: 0;
    padding-top: 1px;
}

.inv-page .inv-page-kicker {
    display: block;
    margin: 0 0 2px;
    color: var(--inv-muted) !important;
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.inv-page .inv-page-title {
    margin: 0;
    color: var(--inv-heading) !important;
    font-family: var(--inv-serif);
    font-size: clamp(2.35rem, 4vw, 3.35rem) !important;
    font-weight: 700 !important;
    line-height: .98 !important;
    letter-spacing: 0 !important;
    text-wrap: balance;
}

.inv-page .inv-page-subtitle {
    max-width: 920px;
    margin-top: 9px !important;
    color: var(--inv-muted) !important;
    font-size: .94rem !important;
    font-weight: 600;
    line-height: 1.55;
}

.inv-page .inv-hero-icon {
    width: 48px !important;
    height: 48px !important;
    display: grid !important;
    place-items: center;
    flex: 0 0 48px;
    border: 0 !important;
    border-radius: 15px !important;
    color: #fff !important;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--inv-accent), var(--inv-brand) 54%, color-mix(in srgb, var(--inv-brand) 68%, var(--brand-accent, #BD9441))) !important;
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--inv-brand) 72%, transparent) !important;
}

/* Layout upgrade: stock control with shelf-like rows and stronger hierarchy. */
.inv-page .inv-topbar > div {
    padding-bottom: 18px !important;
}

.inv-page .inv-topbar > div > .flex:first-child {
    padding: 4px 0 12px;
}

.inv-page .inv-topbar .flex.flex-wrap.gap-2.mt-3 {
    display: grid !important;
    grid-template-columns: repeat(5, minmax(130px, 1fr));
    gap: 8px !important;
    padding-top: 14px !important;
}

.inv-page .inv-pill {
    min-height: 42px;
    justify-content: flex-start;
    border-radius: 12px !important;
    padding: 8px 11px !important;
}

.inv-page .inv-pill b {
    margin-left: auto;
    color: var(--inv-brand);
    font-variant-numeric: tabular-nums;
}

.inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 {
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
}

.inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget {
    grid-column: span 3;
    min-height: 156px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.inv-page .stat-widget::after {
    content: '';
    position: absolute;
    inset: auto 14px 12px 14px;
    height: 1px;
    background: color-mix(in srgb, var(--accent-color, var(--inv-accent)) 36%, var(--inv-line));
    opacity: .85;
}

.inv-page .stat-widget > .flex {
    position: relative;
    z-index: 1;
}

.inv-page .stat-widget > p,
.inv-page .stat-widget .bar-track {
    position: relative;
    z-index: 1;
}

.inv-page .stat-widget .text-2xl {
    font-family: var(--inv-serif);
    font-size: 2.05rem !important;
    letter-spacing: 0;
    line-height: 1;
}

.inv-page .alert-stock {
    min-height: 56px;
    align-items: flex-start;
}

.inv-page .panel-hd {
    min-height: 52px;
    background: var(--inv-surface-warm) !important;
    border-bottom: 1px solid var(--inv-line) !important;
}

.inv-page .panel-hd h3,
.inv-page .panel-hd .text-white {
    color: var(--inv-brand) !important;
}

.inv-page .panel-hd-icon {
    background: color-mix(in srgb, var(--inv-accent) 14%, var(--inv-surface)) !important;
    color: color-mix(in srgb, var(--inv-accent) 76%, #3F2E12) !important;
    border-color: var(--inv-line) !important;
}

.inv-page .panel-hd-icon i {
    color: color-mix(in srgb, var(--inv-accent) 76%, #3F2E12) !important;
}

.inv-page .panel-hd > span {
    background: var(--inv-surface) !important;
    color: var(--inv-brand) !important;
    border: 1px solid var(--inv-line);
}

.inv-page .inv-table-desktop {
    margin-top: -4px;
}

.inv-page #tablaProductos {
    border-collapse: separate !important;
    border-spacing: 0 8px;
}

.inv-page #tablaProductos thead tr {
    transform: translateY(4px);
}

.inv-page #tablaProductos tbody tr {
    background: var(--inv-surface) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--inv-brand-2) 4%, transparent);
}

.inv-page #tablaProductos tbody td {
    border-top: 1px solid var(--inv-line-soft);
    border-bottom: 1px solid var(--inv-line-soft);
    padding-top: 11px !important;
    padding-bottom: 11px !important;
}

.inv-page #tablaProductos tbody td:first-child {
    border-left: 1px solid var(--inv-line-soft);
    border-radius: 13px 0 0 13px;
    padding-left: 12px !important;
}

.inv-page #tablaProductos tbody td:last-child {
    border-right: 1px solid var(--inv-line-soft);
    border-radius: 0 13px 13px 0;
    padding-right: 12px !important;
}

.inv-page #tablaProductos tbody tr:hover td {
    border-color: color-mix(in srgb, var(--inv-accent) 30%, var(--inv-line));
}

.inv-page .code-tag {
    font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
    letter-spacing: .02em;
}

.inv-page .prod-name,
.inv-page #tablaProductos tbody td:nth-child(2) p:first-child {
    font-size: .84rem !important;
    font-weight: 800 !important;
}

.inv-page .mov-row {
    margin: 10px 12px;
    padding: 12px !important;
    border: 1px solid var(--inv-line-soft) !important;
    border-radius: 14px;
    background: var(--inv-surface);
}

.inv-page .mov-row:hover {
    border-color: var(--inv-line) !important;
}

/* Keep the movements panel content-height instead of stretching to match
   the (much taller) products table in the shared grid row. */
.inv-page .mov-panel {
    align-self: start;
}

/* Scroll container + day separators for Movimientos Recientes */
.inv-page .mov-scroll {
    padding-bottom: 8px;
    overscroll-behavior: contain;
}

.inv-page .mov-day-sep {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 6px 14px 2px;
    padding: 4px 0 2px;
}

.inv-page .mov-day-sep:first-child {
    margin-top: 2px;
}

.inv-page .mov-day-sep::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--inv-line-soft);
}

.inv-page .mov-day-sep span {
    display: inline-flex;
    align-items: center;
    padding: 3px 11px;
    border-radius: 999px;
    background: var(--inv-surface-warm);
    border: 1px solid var(--inv-line);
    color: var(--inv-muted);
    font-size: .64rem;
    font-weight: 800;
    letter-spacing: .06em;
    text-transform: uppercase;
    font-variant-numeric: tabular-nums;
    box-shadow: 0 1px 2px rgba(17, 24, 39, .03);
}

.inv-page .producto-card-mobile {
    min-height: 78px;
    gap: 12px;
}

.inv-page .btn-config-inv {
    min-height: 44px;
    padding-left: 16px;
    padding-right: 16px;
}

@media (max-width: 768px) {
    .inv-page .inv-topbar > div,
    .inv-page > .px-4 {
        padding-left: 14px !important;
        padding-right: 14px !important;
    }

    .inv-page .inv-title-lockup {
        grid-template-columns: 44px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }

    .inv-page .inv-hero-icon {
        width: 44px !important;
        height: 44px !important;
        flex-basis: 44px;
        border-radius: 14px !important;
    }

    .inv-page .inv-topbar h1,
    .inv-page .inv-page-title {
        font-size: 2rem !important;
    }

    .inv-page .btn-inv {
        min-height: 44px;
        padding-left: 12px;
        padding-right: 12px;
    }

    .inv-page .inv-action-grid {
        display: flex;
        align-items: stretch;
        flex-wrap: nowrap;
        gap: 7px;
        width: 100%;
        max-width: 100%;
        margin-inline: -2px;
        padding: 2px 2px 8px;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
    }

    .inv-page .inv-action-grid::-webkit-scrollbar {
        display: none;
    }

    .inv-page .inv-action-grid .btn-inv {
        flex: 0 0 auto;
        width: auto;
        min-width: clamp(112px, 31vw, 142px);
        max-width: 152px;
        min-height: 40px;
        padding: 0 10px;
        gap: 6px;
        border-radius: 999px !important;
        font-size: .68rem;
        box-shadow: 0 9px 18px -15px currentColor !important;
        scroll-snap-align: start;
    }

    .inv-page .inv-action-grid .btn-inv i {
        width: 12px;
        min-width: 12px;
        font-size: .68rem !important;
    }

    .inv-page .inv-action-grid .btn-inv span {
        max-width: 104px;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .inv-page .inv-pill {
        flex: 1 1 calc(50% - 8px);
        justify-content: center;
    }

    .inv-page .panel-hd {
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .inv-page .panel-hd .relative {
        width: 100%;
    }

    .inv-page .inv-search,
    .inv-page .inv-search:focus {
        width: 100% !important;
    }

    .inv-page .producto-card-mobile {
        border-radius: 14px !important;
        align-items: flex-start;
    }

    .inv-page .inv-topbar .flex.flex-wrap.gap-2.mt-3,
    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget {
        grid-column: span 1;
    }
}

@media (max-width: 480px) {
    .inv-page .inv-action-grid {
        gap: 6px;
        padding-bottom: 6px;
    }

    .inv-page .inv-action-grid .btn-inv {
        min-width: 108px;
        max-width: 132px;
        min-height: 44px;
        padding: 0 9px;
        font-size: .64rem;
    }

    .inv-page .inv-action-grid .btn-inv span {
        max-width: 88px;
    }

    .inv-page .grid.grid-cols-2 {
        grid-template-columns: 1fr !important;
    }

    .inv-page .inv-pill {
        flex-basis: 100%;
    }

    .inv-page .inv-topbar .flex.flex-wrap.gap-2.mt-3,
    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: 1fr !important;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px !important;
        margin-bottom: 14px !important;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget {
        grid-column: span 1;
        min-height: 88px;
        padding: 10px 10px 9px !important;
        border-radius: 13px !important;
        justify-content: flex-start;
        box-shadow: 0 1px 2px rgba(17, 24, 39, .04), 0 10px 22px -20px rgba(17, 24, 39, .45) !important;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget::before {
        width: 44px;
        height: 44px;
        right: -18px;
        bottom: -18px;
        opacity: .04;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget::after {
        display: none;
    }

    .inv-page .stat-widget > .flex {
        margin-bottom: 6px !important;
        align-items: flex-start !important;
    }

    .inv-page .stat-widget .stat-icon {
        width: 30px;
        height: 30px;
        border-radius: 9px !important;
        font-size: .72rem;
    }

    .inv-page .stat-widget .text-2xl {
        font-size: 1.28rem !important;
        line-height: 1 !important;
    }

    .inv-page .stat-widget > p {
        line-height: 1.15;
    }

    .inv-page .stat-widget > p:first-of-type {
        font-size: .61rem !important;
        letter-spacing: .045em !important;
    }

    .inv-page .stat-widget > p:nth-of-type(2) {
        font-size: .66rem !important;
        margin-top: 2px !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .inv-page .stat-widget .bar-track {
        height: 2px;
        margin-top: 7px;
    }
}

/* Color balance: keep hotel branding as an accent, not the whole inventory surface. */
.inv-page {
    --inv-heading: #111827;
    --inv-body: #1F2937;
    --inv-muted: #667085;
    --inv-ivory: color-mix(in srgb, var(--inv-accent) 4%, #F8F5ED);
    --inv-ivory-2: color-mix(in srgb, var(--inv-accent) 3%, #FBF9F4);
    --inv-surface: #FFFFFF;
    --inv-surface-warm: color-mix(in srgb, var(--inv-accent) 3%, #FFFFFF);
    --inv-line: color-mix(in srgb, var(--inv-brand) 6%, #E7DEC9);
    --inv-line-soft: color-mix(in srgb, var(--inv-brand) 4%, #F0ECE2);
    --inv-shadow: 0 1px 2px rgba(17, 24, 39, .04), 0 14px 30px -24px rgba(17, 24, 39, .34);
    color: var(--inv-body);
    background:
        radial-gradient(960px 420px at 86% -10%, color-mix(in srgb, var(--inv-accent) 7%, transparent), transparent 62%),
        linear-gradient(180deg, var(--inv-ivory-2), var(--inv-ivory) 58%, #F7F2EA) !important;
}

.inv-page .inv-topbar h1,
.inv-page .stat-widget .text-2xl,
.inv-page .inv-pill b,
.inv-page .panel-hd h3,
.inv-page .panel-hd .text-white,
.inv-page .prod-name,
.inv-page #tablaProductos tbody td:nth-child(2) p:first-child,
.inv-page .mov-row .font-semibold,
.inv-page .alert-stock p,
.inv-page .alert-stock strong {
    color: var(--inv-heading) !important;
}

.inv-page .inv-topbar .stat-icon {
    background: linear-gradient(150deg, color-mix(in srgb, var(--inv-brand) 78%, #111827), var(--inv-brand-2)) !important;
    box-shadow: 0 10px 22px -16px color-mix(in srgb, var(--inv-brand) 64%, transparent) !important;
}

.inv-page .btn-inv.primary {
    background: linear-gradient(135deg, var(--inv-accent), color-mix(in srgb, var(--inv-accent) 76%, #000)) !important;
    box-shadow: 0 12px 24px -16px color-mix(in srgb, var(--inv-accent) 60%, transparent) !important;
}

.inv-page .btn-inv.entrada,
.inv-page .btn-inv.salida,
.inv-page .btn-inv.pdf {
    box-shadow: 0 10px 22px -17px currentColor !important;
}

.inv-page .stat-widget,
.inv-page .inv-panel,
.inv-page .producto-card-mobile,
.inv-page #tablaProductos tbody tr {
    background: var(--inv-surface) !important;
    border-color: var(--inv-line) !important;
    box-shadow: var(--inv-shadow) !important;
}

.inv-page .stat-widget:hover,
.inv-page .inv-panel:hover,
.inv-page .producto-card-mobile:hover {
    border-color: color-mix(in srgb, var(--inv-accent) 34%, var(--inv-line)) !important;
    box-shadow: 0 16px 34px -24px rgba(17, 24, 39, .38) !important;
}

.inv-page .stat-icon {
    background: color-mix(in srgb, var(--accent-color, var(--inv-accent)) 10%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--accent-color, var(--inv-accent)) 82%, var(--inv-heading)) !important;
    border-color: color-mix(in srgb, var(--accent-color, var(--inv-accent)) 16%, var(--inv-line)) !important;
}

.inv-page .inv-topbar .inv-hero-icon {
    color: #fff !important;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--inv-accent), var(--inv-brand) 54%, color-mix(in srgb, var(--inv-brand) 68%, var(--brand-accent, #BD9441))) !important;
    border-color: transparent !important;
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--inv-brand) 72%, transparent) !important;
}

.inv-page .inv-pill {
    color: var(--inv-body) !important;
    background: var(--inv-surface) !important;
    border-color: var(--inv-line) !important;
}

.inv-page .code-tag,
.inv-page .badge-auto,
.inv-page .inline-flex[title="Auto check-in"] {
    background: color-mix(in srgb, var(--inv-auto) 8%, #FFFFFF) !important;
    color: #5145A8 !important;
    border-color: color-mix(in srgb, var(--inv-auto) 20%, var(--inv-line)) !important;
}

.inv-page .badge-ok {
    background: color-mix(in srgb, var(--inv-success) 8%, #FFFFFF) !important;
}

.inv-page .badge-low {
    background: color-mix(in srgb, var(--inv-warning) 10%, #FFFFFF) !important;
}

.inv-page .badge-out {
    background: color-mix(in srgb, var(--inv-danger) 9%, #FFFFFF) !important;
}

.inv-page .panel-hd {
    background: linear-gradient(180deg, var(--inv-surface), var(--inv-surface-warm)) !important;
}

.inv-page .panel-hd-icon {
    background: color-mix(in srgb, var(--inv-accent) 10%, #FFFFFF) !important;
}

.inv-page .inv-search {
    color: var(--inv-heading) !important;
    background: var(--inv-surface) !important;
    border-color: var(--inv-line) !important;
}

.inv-page .inv-tr:hover,
.inv-page .mov-row:hover,
.inv-page .producto-card-mobile:hover {
    background: color-mix(in srgb, var(--inv-accent) 5%, #FFFFFF) !important;
}

.inv-page .act-btn.edit {
    color: var(--inv-heading) !important;
}

.inv-page .act-btn.edit:hover {
    color: color-mix(in srgb, var(--inv-accent) 80%, #111827) !important;
}

.inv-page .mov-icon-w {
    background: var(--inv-surface-warm) !important;
}

/* Viewport alignment: match the broad operational canvas used by huespedes/facturacion. */
.inv-page {
    padding: 1rem !important;
}

.inv-page .inv-topbar > div,
.inv-page > .px-4 {
    width: 100%;
    max-width: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.inv-page .inv-topbar > div {
    padding-top: 0 !important;
}

.inv-page > .px-4 {
    padding-top: 1rem !important;
    padding-bottom: 0 !important;
}

@media (min-width: 640px) {
    .inv-page {
        padding: 2rem !important;
    }
}

/* Documents-aligned polish: calmer ink, lighter weights, same boutique rhythm. */
.inv-page {
    --inv-heading: color-mix(in srgb, var(--inv-brand) 66%, #566172);
    --inv-body: color-mix(in srgb, var(--inv-brand) 46%, #707B8C);
    --inv-muted: #8791A2;
    --inv-ivory: #F6F2EA;
    --inv-ivory-2: #FBF8F2;
    --inv-surface: #FFFFFF;
    --inv-surface-warm: #FCFAF5;
    --inv-line: color-mix(in srgb, var(--inv-brand) 6%, #E9E1D6);
    --inv-line-soft: color-mix(in srgb, var(--inv-brand) 4%, #F3EEE6);
    --inv-accent-soft: color-mix(in srgb, var(--inv-accent) 10%, #FFFFFF);
    --inv-accent-line: color-mix(in srgb, var(--inv-accent) 28%, #ECE1D1);
    --inv-accent-ink: color-mix(in srgb, var(--inv-accent) 58%, var(--inv-brand));
    --inv-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
    color: var(--inv-body);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--inv-accent) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--inv-ivory-2), var(--inv-ivory)) !important;
}

.inv-page .font-bold,
.inv-page .font-semibold {
    font-weight: 650 !important;
}

.inv-page .inv-page-kicker,
.inv-page .inv-th,
.inv-page .stat-widget > p:first-of-type,
.inv-page .mov-day-sep span {
    color: var(--inv-muted) !important;
    font-weight: 650 !important;
}

.inv-page .inv-page-title,
.inv-page .inv-topbar h1 {
    color: var(--inv-heading) !important;
    font-weight: 650 !important;
    font-size: clamp(2.1rem, 4vw, 3rem) !important;
}

.inv-page .inv-page-subtitle,
.inv-page .inv-topbar h1 + p {
    color: var(--inv-muted) !important;
    font-weight: 500 !important;
}

.inv-page .stat-widget,
.inv-page .inv-panel,
.inv-page .producto-card-mobile,
.inv-page #tablaProductos tbody tr {
    background: rgba(255,255,255,.86) !important;
    border-color: var(--inv-line) !important;
    box-shadow: var(--inv-shadow) !important;
}

.inv-page .stat-widget:hover,
.inv-page .inv-panel:hover,
.inv-page .producto-card-mobile:hover {
    border-color: color-mix(in srgb, var(--inv-accent) 34%, var(--inv-line)) !important;
    box-shadow: 0 16px 30px -27px rgba(27,39,70,.28) !important;
}

.inv-page .stat-widget .text-2xl,
.inv-page .inv-pill b {
    color: var(--inv-heading) !important;
    font-family: var(--inv-serif);
    font-weight: 650 !important;
}

.inv-page .stat-widget > p:nth-of-type(2),
.inv-page .text-gray-300,
.inv-page .text-gray-400,
.inv-page .text-gray-500,
.inv-page .prod-cat {
    color: var(--inv-muted) !important;
}

.inv-page .btn-inv,
.inv-page .btn-config-inv,
.inv-page .badge,
.inv-page .code-tag,
.inv-page .act-btn {
    font-weight: 650 !important;
}

.inv-page .btn-inv.primary {
    background: linear-gradient(135deg, color-mix(in srgb, var(--inv-accent) 86%, #fff), color-mix(in srgb, var(--inv-accent) 72%, var(--inv-brand))) !important;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--inv-accent) 42%, transparent) !important;
}

.inv-page .btn-inv.entrada,
.inv-page .btn-inv.salida,
.inv-page .btn-inv.movimientos,
.inv-page .btn-inv.pdf {
    border: 1px solid var(--inv-action-line, var(--inv-line)) !important;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--inv-action, var(--inv-info)) 16%, #FFFFFF), color-mix(in srgb, var(--inv-action, var(--inv-info)) 7%, #FFFFFF)) !important;
    color: color-mix(in srgb, var(--inv-action, var(--inv-info)) 66%, var(--inv-body)) !important;
    box-shadow: 0 12px 24px -18px color-mix(in srgb, var(--inv-action, var(--inv-info)) 72%, transparent) !important;
}

.inv-page .btn-inv.entrada { --inv-action: var(--inv-success); --inv-action-line: color-mix(in srgb, var(--inv-success) 30%, #FFFFFF); }
.inv-page .btn-inv.salida { --inv-action: var(--inv-warning); --inv-action-line: color-mix(in srgb, var(--inv-warning) 30%, #FFFFFF); }
.inv-page .btn-inv.movimientos { --inv-action: var(--inv-info); --inv-action-line: color-mix(in srgb, var(--inv-info) 30%, #FFFFFF); }
.inv-page .btn-inv.pdf { --inv-action: var(--inv-danger); --inv-action-line: color-mix(in srgb, var(--inv-danger) 30%, #FFFFFF); }

.inv-page .btn-inv.entrada i,
.inv-page .btn-inv.salida i,
.inv-page .btn-inv.movimientos i,
.inv-page .btn-inv.pdf i {
    color: var(--inv-action);
}

.inv-page .inv-pill {
    min-height: 40px;
    background: rgba(255,255,255,.82) !important;
    color: var(--inv-body) !important;
    border-color: var(--inv-line) !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18) !important;
}

.inv-page .panel-hd {
    background: linear-gradient(90deg, var(--inv-accent-soft), transparent 72%) !important;
    border-bottom-color: var(--inv-line) !important;
}

.inv-page .panel-hd h3,
.inv-page .panel-hd .text-white,
.inv-page .prod-name,
.inv-page #tablaProductos tbody td:nth-child(2) p:first-child,
.inv-page .mov-row .font-semibold,
.inv-page .alert-stock p,
.inv-page .alert-stock strong {
    color: var(--inv-heading) !important;
    font-weight: 650 !important;
}

.inv-page .panel-hd-icon {
    background: var(--inv-accent-soft) !important;
    color: var(--inv-accent-ink) !important;
    border-color: var(--inv-accent-line) !important;
}

.inv-page .panel-hd-icon i {
    color: var(--inv-accent-ink) !important;
}

.inv-page .panel-hd > span,
.inv-page .code-tag {
    background: var(--inv-accent-soft) !important;
    color: var(--inv-accent-ink) !important;
    border-color: var(--inv-accent-line) !important;
}

.inv-page .inv-search {
    color: var(--inv-body) !important;
    background: var(--inv-surface-warm) !important;
    border-color: var(--inv-line) !important;
    font-weight: 560 !important;
}

.inv-page .inv-search::placeholder {
    color: color-mix(in srgb, var(--inv-muted) 82%, #B8C0CB) !important;
    font-weight: 520;
}

.inv-page .inv-th {
    background: var(--inv-surface-warm) !important;
    letter-spacing: .07em !important;
}

.inv-page #tablaProductos tbody td:nth-child(2) p:first-child,
.inv-page .prod-name {
    font-size: .84rem !important;
    font-weight: 650 !important;
}

.inv-page .badge-ok {
    color: color-mix(in srgb, var(--inv-success) 70%, var(--inv-body)) !important;
    background: var(--inv-success-bg) !important;
}

.inv-page .badge-low {
    color: color-mix(in srgb, var(--inv-warning) 72%, var(--inv-body)) !important;
    background: var(--inv-warning-bg) !important;
}

.inv-page .badge-out {
    color: color-mix(in srgb, var(--inv-danger) 72%, var(--inv-body)) !important;
    background: var(--inv-danger-bg) !important;
}

.inv-page .badge-auto,
.inv-page .inline-flex[title="Auto check-in"] {
    color: color-mix(in srgb, var(--inv-auto) 68%, var(--inv-body)) !important;
    background: var(--inv-auto-bg) !important;
}

.inv-page .act-btn.edit {
    color: var(--inv-accent-ink) !important;
    background: var(--inv-surface-warm) !important;
    border-color: var(--inv-line) !important;
}

.inv-page .act-btn.edit:hover {
    color: var(--inv-accent-ink) !important;
    background: var(--inv-accent-soft) !important;
    border-color: var(--inv-accent-line) !important;
}

.inv-page .act-btn.del {
    color: color-mix(in srgb, var(--inv-danger) 72%, var(--inv-body)) !important;
    background: var(--inv-danger-bg) !important;
}

.inv-page .alert-stock {
    box-shadow: var(--inv-shadow) !important;
}

@media (max-width: 767px) {
    .inv-page {
        --inv-mobile-ink: color-mix(in srgb, var(--inv-brand) 62%, #6F7784);
        --inv-mobile-text: color-mix(in srgb, var(--inv-brand) 42%, #778394);
        --inv-mobile-muted: #98A2B3;
        --inv-mobile-faint: #AAB3C0;
        padding: 10px 12px 18px !important;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--inv-accent) 12%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%) !important;
    }

    .inv-page .inv-topbar > div > .flex:first-child {
        position: relative;
        overflow: hidden;
        display: grid !important;
        grid-template-columns: minmax(0, 1fr);
        gap: 12px !important;
        min-height: 126px;
        margin: 0;
        padding: 16px 14px 14px !important;
        border-radius: 22px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 14%, rgba(255,255,255,.17), transparent 92px),
            linear-gradient(135deg, color-mix(in srgb, var(--inv-brand) 94%, #000) 0%, color-mix(in srgb, var(--inv-brand-2) 78%, var(--inv-accent)) 100%);
        box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--inv-brand) 72%, transparent);
    }

    .inv-page .inv-topbar > div > .flex:first-child::after {
        content: "";
        position: absolute;
        right: -44px;
        top: -44px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        pointer-events: none;
    }

    .inv-page .inv-title-lockup,
    .inv-page .inv-action-grid {
        position: relative;
        z-index: 1;
    }

    .inv-page .inv-hero-icon {
        background: rgba(255,255,255,.16) !important;
        color: #fff !important;
        border: 1px solid rgba(255,255,255,.22) !important;
        box-shadow: none !important;
        backdrop-filter: blur(10px);
    }

    .inv-page .inv-page-kicker {
        color: rgba(255,255,255,.76) !important;
        font-size: .62rem;
        letter-spacing: .14em;
    }

    .inv-page .inv-page-title {
        color: #fff !important;
        font-size: 1.72rem !important;
        text-shadow: 0 8px 22px rgba(0,0,0,.28);
    }

    .inv-page .inv-page-subtitle {
        display: -webkit-box;
        max-width: none;
        margin-top: 6px !important;
        color: rgba(255,255,255,.82) !important;
        font-size: .73rem !important;
        line-height: 1.35;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .inv-page .inv-action-grid .btn-inv {
        background: rgba(255,255,255,.13) !important;
        color: #fff !important;
        border-color: rgba(255,255,255,.18) !important;
        box-shadow: none !important;
    }

    .inv-page .inv-action-grid .btn-inv i {
        color: #fff !important;
    }

    .inv-page .inv-action-grid .btn-inv.primary {
        background: #fff !important;
        color: var(--inv-mobile-ink) !important;
    }

    .inv-page .inv-action-grid .btn-inv.primary i {
        color: var(--inv-mobile-ink) !important;
    }

    .inv-page .inv-topbar .flex.flex-wrap.gap-2.mt-3 {
        display: flex !important;
        grid-template-columns: none !important;
        gap: 8px !important;
        margin: 0 -12px;
        padding: 10px 12px 2px !important;
        border-top: 0 !important;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .inv-page .inv-topbar .flex.flex-wrap.gap-2.mt-3::-webkit-scrollbar,
    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4::-webkit-scrollbar {
        width: 0;
        height: 0;
    }

    .inv-page .inv-pill {
        flex: 0 0 126px;
        min-height: 62px;
        align-items: flex-start;
        justify-content: center;
        flex-direction: column;
        gap: 4px;
        border-radius: 16px !important;
        padding: 10px 11px !important;
    }

    .inv-page .inv-pill b {
        margin-left: 0;
        font-size: 1.05rem;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 {
        display: flex !important;
        grid-template-columns: none !important;
        gap: 8px !important;
        margin: 0 -12px 14px !important;
        padding: 0 12px 2px;
        overflow-x: auto;
        scrollbar-width: none;
    }

    .inv-page > .px-4 .grid.grid-cols-2.lg\:grid-cols-4 > .stat-widget {
        flex: 0 0 124px;
        grid-column: auto !important;
        min-height: 86px;
        padding: 10px 11px !important;
        border-radius: 16px !important;
        box-shadow: 0 12px 22px -26px rgba(27,39,70,.24) !important;
    }

    .inv-page .stat-widget .stat-icon {
        width: 32px;
        height: 32px;
    }

    .inv-page .stat-widget .text-2xl {
        font-size: 1.22rem !important;
    }

    .inv-page .stat-widget > p:first-of-type {
        font-size: .58rem !important;
        white-space: nowrap;
    }

    .inv-page .stat-widget > p:nth-of-type(2) {
        font-size: .66rem !important;
    }

    .inv-page .panel-hd {
        padding: 12px 13px !important;
        border-radius: 18px 18px 0 0;
    }

    .inv-page .inv-panel {
        border-radius: 18px !important;
    }

    .inv-page .producto-card-mobile {
        background: rgba(255,255,255,.78) !important;
        border-color: color-mix(in srgb, var(--inv-brand) 5%, #ECE4D8) !important;
        box-shadow: 0 12px 24px -26px rgba(27,39,70,.28) !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .inv-page *,
    .inv-page *::before,
    .inv-page *::after {
        transition: none !important;
        animation: none !important;
    }
}
</style>

<!-- ═══════════════════════════════════════ INVENTARIO PAGE ═══ -->
<div class="inv-page">

    <!-- Top Bar -->
    <div class="inv-topbar bg-white">
        <div class="px-4 sm:px-5 lg:px-8 py-4">
            <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-3">

                <!-- Title -->
                <div class="inv-title-lockup">
                    <div class="stat-icon inv-hero-icon">
                        <i class="fas fa-boxes text-lg"></i>
                    </div>
                    <div class="inv-title-copy">
                        <p class="inv-page-kicker">Operaci&oacute;n hotelera</p>
                        <h1 class="inv-page-title">Control de inventario</h1>
                        <p class="inv-page-subtitle">Gesti&oacute;n y monitoreo de productos con lectura clara de stock, movimientos y alertas &middot; <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="inv-action-grid">
                    <a href="<?= url('inventario/nuevo') ?>" class="btn-inv primary">
                        <i class="fas fa-plus text-xs"></i>
                        <span>Nuevo producto</span>
                    </a>
                    <a href="<?= url('inventario/entrada') ?>" class="btn-inv entrada">
                        <i class="fas fa-arrow-down text-xs"></i>
                        <span>Registrar entrada</span>
                    </a>
                    <a href="<?= url('inventario/salida') ?>" class="btn-inv salida">
                        <i class="fas fa-arrow-up text-xs"></i>
                        <span>Registrar salida</span>
                    </a>
                    <a href="<?= url('inventario/movimientos') ?>" class="btn-inv movimientos">
                        <i class="fas fa-exchange-alt text-xs"></i>
                        <span>Movimientos</span>
                    </a>
                    <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                    <a href="<?= url('inventario/exportar') ?>" class="btn-inv pdf">
                        <i class="fas fa-file-pdf text-xs"></i>
                        <span>Exportar PDF</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t border-[#EAF0E5]">
                <?php
                $total_productos     = count($productos);
                $productos_ok        = count(array_filter($productos, fn($p) => $p['stock_actual'] > $p['stock_minimo']));
                $productos_bajos     = count(array_filter($productos, fn($p) => $p['stock_actual'] <= $p['stock_minimo'] && $p['stock_actual'] > 0));
                $productos_agotados  = count(array_filter($productos, fn($p) => $p['stock_actual'] == 0));
                $productos_auto_cnt  = count(array_filter($productos, fn($p) => $p['descuento_automatico']));
                ?>
                <span class="inv-pill"><span class="inv-dot" style="background:#5C7A4E"></span>Total: <b><?= $total_productos ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#10b981"></span>Correcto: <b><?= $productos_ok ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#f59e0b"></span>Stock bajo: <b><?= $productos_bajos ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#ef4444"></span>Agotados: <b><?= $productos_agotados ?></b></span>
                <span class="inv-pill"><span class="inv-dot" style="background:#8b5cf6"></span>Auto check-in: <b><?= $productos_auto_cnt ?></b></span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 sm:px-5 lg:px-8 py-5">

        <!-- Alerts -->
        <?php
        $productos_alerta    = array_filter($productos, fn($p) => $p['stock_actual'] <= $p['stock_minimo']);
        $productos_auto_list = array_filter($productos, fn($p) => $p['descuento_automatico']);
        ?>
        <?php if (!empty($productos_alerta)): ?>
        <div class="alert-stock amber shadow-sm">
            <i class="fas fa-exclamation-triangle text-amber-500 text-sm mt-0.5 flex-shrink-0"></i>
            <p class="text-xs sm:text-sm text-amber-800">
                <strong>Atención:</strong> <?= count($productos_alerta) ?> producto(s) con stock bajo o crítico requieren reposición.
            </p>
        </div>
        <?php endif; ?>
        <?php if (!empty($productos_auto_list)): ?>
        <div class="alert-stock green shadow-sm">
            <i class="fas fa-bed text-[#5C7A4E] text-sm mt-0.5 flex-shrink-0"></i>
            <div>
                <p class="text-xs sm:text-sm text-[#3D5234]">
                    <strong>Descuento automático activo:</strong> <?= count($productos_auto_list) ?> producto(s) se descuentan al registrar check-in.
                </p>
                <p class="text-xs text-[#4A6340] mt-1">
                    Los marcados con <span class="badge badge-auto ml-1"><i class="fas fa-check-circle"></i>Auto</span> se reducen automáticamente.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stat Widgets -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">

            <!-- Total -->
            <div class="stat-widget" style="--accent-color:#5C7A4E">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(92,122,78,.12);color:#5C7A4E;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <span class="text-2xl font-bold text-[#3D5234]"><?= $total_productos ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Productos</p>
                <p class="text-xs text-gray-400 mt-0.5">en catálogo</p>
                <div class="bar-track"><div class="bar-fill" style="width:100%;background:#5C7A4E"></div></div>
            </div>

            <!-- OK -->
            <div class="stat-widget" style="--accent-color:#10b981">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(16,185,129,.12);color:#059669;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="text-2xl font-bold text-emerald-600"><?= $productos_ok ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Correcto</p>
                <p class="text-xs text-gray-400 mt-0.5"><?= $total_productos > 0 ? round($productos_ok/$total_productos*100) : 0 ?>% óptimo</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_ok/$total_productos*100 : 0 ?>%;background:#10b981"></div></div>
            </div>

            <!-- Bajo -->
            <div class="stat-widget" style="--accent-color:#f59e0b">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(245,158,11,.12);color:#d97706;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <span class="text-2xl font-bold text-amber-600"><?= $productos_bajos ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Stock Bajo</p>
                <p class="text-xs text-gray-400 mt-0.5">requieren atención</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_bajos/$total_productos*100 : 0 ?>%;background:#f59e0b"></div></div>
            </div>

            <!-- Agotados -->
            <div class="stat-widget" style="--accent-color:#ef4444">
                <div class="flex items-center justify-between mb-3">
                    <div class="stat-icon" style="background:rgba(239,68,68,.12);color:#dc2626;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <span class="text-2xl font-bold text-red-500"><?= $productos_agotados ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sin Stock</p>
                <p class="text-xs text-gray-400 mt-0.5">agotados</p>
                <div class="bar-track"><div class="bar-fill" style="width:<?= $total_productos > 0 ? $productos_agotados/$total_productos*100 : 0 ?>%;background:#ef4444"></div></div>
            </div>
        </div>

        <!-- Table + Movements -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            <!-- Products Table -->
            <div class="lg:col-span-2 inv-panel">
                <div class="panel-hd">
                    <div class="flex items-center gap-2.5">
                        <div class="panel-hd-icon"><i class="fas fa-list text-white text-xs"></i></div>
                        <h3 class="text-sm font-bold text-white">Productos en Inventario</h3>
                    </div>
                    <div class="relative">
                        <input type="text" id="buscarProducto" placeholder="Buscar..." class="inv-search">
                        <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div class="p-3 sm:p-4">
                    <?php if (empty($productos)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-4xl mb-3" style="color:#D5E4CB"></i>
                            <p class="text-gray-400 text-sm mb-3">No hay productos registrados</p>
                            <a href="<?= url('inventario/nuevo') ?>" class="btn-inv primary text-xs">
                                <i class="fas fa-plus"></i> Agregar primer producto
                            </a>
                        </div>
                    <?php else: ?>

                        <!-- ═══ DESKTOP TABLE ═══ -->
                        <div class="inv-table-desktop overflow-x-auto lc-scroll -mx-4 px-4">
                            <table class="min-w-full" id="tablaProductos">
                                <thead>
                                    <tr class="border-b border-[#EAF0E5]">
                                        <th class="inv-th text-left">Código</th>
                                        <th class="inv-th text-left">Producto</th>
                                        <th class="inv-th text-center">Stock</th>
                                        <th class="inv-th text-center hidden sm:table-cell">Mín</th>
                                        <th class="inv-th text-center">Auto</th>
                                        <th class="inv-th text-center"><i class="fas fa-ellipsis-h text-gray-300"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos as $producto): ?>
                                    <tr class="inv-tr <?= $producto['descuento_automatico'] ? 'bg-[#FAFDF8]' : '' ?>"
                                        data-producto="<?= htmlspecialchars(strtolower($producto['nombre'].' '.$producto['codigo'])) ?>">

                                        <td class="py-3 px-1">
                                            <span class="code-tag"><?= htmlspecialchars($producto['codigo']) ?></span>
                                        </td>

                                        <td class="py-3 px-1">
                                            <div class="flex items-center gap-1.5 mb-0.5">
                                                <p class="text-xs font-semibold text-gray-800 truncate max-w-[140px] sm:max-w-none">
                                                    <?= htmlspecialchars($producto['nombre']) ?>
                                                </p>
                                                <?php if ($producto['descuento_automatico']): ?>
                                                    <span class="inline-flex items-center px-1 py-0.5 rounded" style="background:rgba(92,122,78,.12);color:#4A6340;" title="Auto check-in">
                                                        <i class="fas fa-bed text-xs"></i>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-xs text-gray-400"><?= htmlspecialchars($producto['categoria_nombre']) ?></p>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <?php
                                            $pct = $producto['stock_minimo'] > 0 ? ($producto['stock_actual']/$producto['stock_minimo'])*100 : 100;
                                            if ($pct <= 0)  { $bc='badge-out'; $bi='<i class="fas fa-times-circle"></i>'; }
                                            elseif ($pct<=50){ $bc='badge-low'; $bi='<i class="fas fa-exclamation-circle"></i>'; }
                                            else            { $bc='badge-ok';  $bi=''; }
                                            ?>
                                            <span class="badge <?= $bc ?>"><?= $bi ?><?= $producto['stock_actual'] ?></span>
                                        </td>

                                        <td class="py-3 px-1 text-center text-xs text-gray-500 hidden sm:table-cell">
                                            <?= $producto['stock_minimo'] ?>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <?php if ($producto['descuento_automatico']): ?>
                                                <span class="badge badge-auto" title="Descuento automático al check-in">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="hidden sm:inline">Auto</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-300 text-xs"><i class="fas fa-times"></i></span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="py-3 px-1 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <a href="<?= url('inventario/editar/'.$producto['id']) ?>" class="act-btn edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?= url('inventario/ajuste/'.$producto['id']) ?>" class="act-btn edit" title="Ajustar stock">
                                                    <i class="fas fa-sliders-h"></i>
                                                </a>
                                                <button type="button"
                                                        onclick="confirmarEliminarProducto(this, <?= (int) $producto['id'] ?>, <?= htmlspecialchars(json_encode((string) $producto['nombre'], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, <?= (int) $producto['stock_actual'] ?>)"
                                                        class="act-btn del" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- ═══ MOBILE CARDS ═══ -->
                        <div class="inv-cards-mobile" style="display:none;">
                            <?php foreach ($productos as $producto): ?>
                            <div class="producto-card-mobile <?= $producto['descuento_automatico'] ? 'is-auto-stock' : '' ?>"
                                 data-producto-card="<?= htmlspecialchars(strtolower($producto['nombre'].' '.$producto['codigo'])) ?>">

                                <!-- Stock badge -->
                                <div style="flex-shrink:0;">
                                    <?php
                                    $pct = $producto['stock_minimo'] > 0 ? ($producto['stock_actual']/$producto['stock_minimo'])*100 : 100;
                                    if ($pct <= 0)  { $bc='badge-out'; $bi='<i class="fas fa-times-circle"></i>'; }
                                    elseif ($pct<=50){ $bc='badge-low'; $bi='<i class="fas fa-exclamation-circle"></i>'; }
                                    else            { $bc='badge-ok';  $bi=''; }
                                    ?>
                                    <span class="badge <?= $bc ?>"><?= $bi ?><?= $producto['stock_actual'] ?></span>
                                </div>

                                <!-- Info -->
                                <div class="prod-info">
                                    <div class="flex items-center gap-1">
                                        <p class="prod-name"><?= htmlspecialchars($producto['nombre']) ?></p>
                                        <?php if ($producto['descuento_automatico']): ?>
                                            <i class="fas fa-bed text-[10px]" style="color:#5C7A4E;" title="Auto check-in"></i>
                                        <?php endif; ?>
                                    </div>
                                    <p class="prod-cat">
                                        <span class="code-tag" style="font-size:.6rem;padding:1px 5px;"><?= htmlspecialchars($producto['codigo']) ?></span>
                                        <?= htmlspecialchars($producto['categoria_nombre']) ?>
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="prod-actions">
                                    <a href="<?= url('inventario/editar/'.$producto['id']) ?>" class="act-btn edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= url('inventario/ajuste/'.$producto['id']) ?>" class="act-btn edit" title="Ajustar stock">
                                        <i class="fas fa-sliders-h"></i>
                                    </a>
                                    <button type="button"
                                            onclick="confirmarEliminarProducto(this, <?= (int) $producto['id'] ?>, <?= htmlspecialchars(json_encode((string) $producto['nombre'], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, <?= (int) $producto['stock_actual'] ?>)"
                                            class="act-btn del" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Config button (more visible) -->
                        <div class="mt-3 pt-3 border-t border-[#EAF0E5] text-center">
                            <a href="<?= url('inventario/configuracion') ?>" class="btn-config-inv">
                                <i class="fas fa-cog"></i>Configuración de inventario
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Movements -->
            <div class="inv-panel mov-panel">
                <div class="panel-hd">
                    <div class="flex items-center gap-2.5">
                        <div class="panel-hd-icon"><i class="fas fa-history text-white text-xs"></i></div>
                        <h3 class="text-sm font-bold text-white">Movimientos Recientes</h3>
                    </div>
                    <span class="bg-white/20 text-white text-xs font-bold px-2.5 py-1 rounded-full">
                        <?= count($movimientos_recientes ?? []) ?>
                    </span>
                </div>

                <?php if (empty($movimientos_recientes)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-clipboard-list text-3xl mb-2" style="color:#D5E4CB"></i>
                        <p class="text-gray-400 text-sm">Sin movimientos recientes</p>
                    </div>
                <?php else: ?>
                    <div class="max-h-[560px] overflow-y-auto lc-scroll mov-scroll">
                        <?php
                        $mov_dia_actual = null;
                        $mov_meses = [1=>'ene',2=>'feb',3=>'mar',4=>'abr',5=>'may',6=>'jun',7=>'jul',8=>'ago',9=>'sep',10=>'oct',11=>'nov',12=>'dic'];
                        $mov_hoy  = date('Y-m-d');
                        $mov_ayer = date('Y-m-d', strtotime('-1 day'));
                        foreach ($movimientos_recientes as $mov):
                            $mov_ts  = strtotime($mov['created_at']);
                            $mov_dia = date('Y-m-d', $mov_ts);
                            if ($mov_dia !== $mov_dia_actual):
                                $mov_dia_actual = $mov_dia;
                                if ($mov_dia === $mov_hoy) {
                                    $mov_dia_label = 'Hoy';
                                } elseif ($mov_dia === $mov_ayer) {
                                    $mov_dia_label = 'Ayer';
                                } else {
                                    $mov_dia_label = (int)date('j', $mov_ts) . ' ' . $mov_meses[(int)date('n', $mov_ts)] . '. ' . date('Y', $mov_ts);
                                }
                        ?>
                        <div class="mov-day-sep"><span><?= $mov_dia_label ?></span></div>
                        <?php endif; ?>
                        <div class="mov-row">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <?php if ($mov['tipo_movimiento'] == 'ENTRADA'): ?>
                                        <div class="mov-icon-w" style="background:rgba(16,185,129,.12);color:#059669;">
                                            <i class="fas fa-arrow-down"></i>
                                        </div>
                                    <?php elseif ($mov['tipo_movimiento'] == 'SALIDA'): ?>
                                        <div class="mov-icon-w" style="background:rgba(245,158,11,.12);color:#d97706;">
                                            <i class="fas fa-arrow-up"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="mov-icon-w" style="background:rgba(92,122,78,.12);color:#5C7A4E;">
                                            <i class="fas fa-sync"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-800 leading-tight">
                                            <?= htmlspecialchars($mov['producto_nombre']) ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            <?= date('H:i', $mov_ts) ?>
                                            <?php if ($mov['habitacion_numero']): ?> · Hab <?= $mov['habitacion_numero'] ?><?php endif; ?>
                                        </p>
                                        <?php if ($mov['motivo']): ?>
                                            <p class="text-xs text-gray-400 mt-0.5 italic"><?= htmlspecialchars($mov['motivo']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <span class="text-sm font-bold whitespace-nowrap <?= $mov['tipo_movimiento']=='ENTRADA' ? 'text-emerald-600' : 'text-amber-600' ?>">
                                    <?= $mov['tipo_movimiento']=='ENTRADA' ? '+' : '−' ?><?= $mov['cantidad'] ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- end grid -->
    </div><!-- end main content -->
</div><!-- end page -->

<!-- Scripts -->
<script>
document.getElementById('buscarProducto').addEventListener('keyup', function() {
    const q = this.value.toLowerCase();
    // Desktop table
    document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
        const txt = row.getAttribute('data-producto') || '';
        row.style.display = txt.includes(q) ? '' : 'none';
    });
    // Mobile cards
    document.querySelectorAll('[data-producto-card]').forEach(card => {
        const txt = card.getAttribute('data-producto-card') || '';
        card.style.display = txt.includes(q) ? '' : 'none';
    });
});

function confirmarEliminarProducto(trigger, id, nombre, stock) {
    const stockMessage = stock > 0
        ? `${nombre} tiene ${stock} unidades. Se perderá el registro de stock y movimientos asociados.`
        : `Se eliminará ${nombre}. Esta acción no se puede deshacer.`;
    msConfirm({
        type: 'error',
        icon: 'trash',
        title: '¿Eliminar producto?',
        msg: stockMessage,
        confirmLabel: 'Sí, eliminar'
    }).then(ok => {
        if (ok) eliminarProducto(id);
    });
}

function eliminarProducto(id) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("inventario/eliminar/") ?>' + id;
    const csrfField = '<?= csrf_field() ?>';
    if (csrfField) form.innerHTML += csrfField;
    document.body.appendChild(form);
    form.submit();
}
</script>
