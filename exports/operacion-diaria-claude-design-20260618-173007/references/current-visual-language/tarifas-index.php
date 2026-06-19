<?php
$tarifaRoomTypeLabels = [];
if (function_exists('hotel_room_catalog_types')) {
    $tarifaRoomTypeLabels = hotel_room_catalog_types();
}
if (empty($tarifaRoomTypeLabels) && class_exists('Habitacion')) {
    $tarifaRoomTypeLabels = Habitacion::getTipos();
}
?>

<!-- ── Librerías ─────────────────────────── -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* ══════════════════════════════════════════
   Tarifas Dinámicas
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
}

/* Page */
.tar-page { background:linear-gradient(145deg,#EFF4EC 0%,#E8EEE3 50%,#F4F1EC 100%); min-height:100vh; }

/* ── Top bar ─────────────────────────────── */
.tar-topbar {
    background:#fff;
    border-bottom:1px solid #DDE8D5;
    position:relative;
}
.tar-topbar::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
    background:linear-gradient(90deg,var(--lc-green-deep),var(--lc-gold),var(--lc-green-deep));
}

/* ── Action buttons ──────────────────────── */
.btn-tar {
    display:inline-flex; align-items:center; justify-content:center;
    gap:6px; padding:7px 13px; border-radius:9px;
    font-size:.78rem; font-weight:700;
    transition:transform .2s, box-shadow .2s;
    text-decoration:none; border:none; cursor:pointer;
}
.btn-tar:hover { transform:translateY(-1px); }
.btn-tar.calc {
    background:linear-gradient(135deg,#5C7A4E,#4A6340); color:#fff;
}
.btn-tar.calc:hover { box-shadow:0 6px 16px rgba(74,99,64,.3); color:#fff; }
.btn-tar.new {
    background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));
    color:#3D5234;
}
.btn-tar.new:hover { box-shadow:0 6px 16px rgba(200,169,106,.35); color:#3D5234; }

/* ── Stat widgets ────────────────────────── */
.tar-stat {
    background:#fff; border-radius:14px; border:1px solid #DDE8D5;
    padding:16px; transition:transform .25s, box-shadow .25s;
    position:relative; overflow:hidden;
}
.tar-stat::after {
    content:''; position:absolute; bottom:0; left:0; right:0;
    height:2px; opacity:0; transition:opacity .25s;
    background:var(--ws, #5C7A4E);
}
.tar-stat:hover { transform:translateY(-3px); box-shadow:0 10px 26px rgba(92,122,78,.11); }
.tar-stat:hover::after { opacity:1; }
.tar-stat-icon {
    width:36px; height:36px; border-radius:10px;
    display:flex; align-items:center; justify-content:center; font-size:14px;
}

/* ── Filters panel ───────────────────────── */
.tar-filters {
    background:#fff; border-radius:14px; border:1px solid #DDE8D5;
    overflow:hidden; margin-bottom:16px;
}
.tar-filters-hd {
    background:linear-gradient(135deg,rgba(92,122,78,.07),rgba(92,122,78,.03));
    border-bottom:1px solid #EAF0E5;
    padding:12px 16px;
    display:flex; align-items:center; justify-content:space-between;
}
.filter-select {
    border:1.5px solid #C8D9BE; border-radius:8px;
    padding:6px 10px; font-size:.78rem; color:#374151;
    background:#FAFDF8; width:100%;
    transition:border-color .2s, box-shadow .2s;
}
.filter-select:focus {
    outline:none; border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.15);
}
.filter-label { font-size:.7rem; font-weight:700; color:#5C7A4E; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.btn-reset { font-size:.72rem; color:#9CA3AF; background:none; border:none; cursor:pointer; transition:color .15s; }
.btn-reset:hover { color:var(--lc-green); }

/* ── Main table panel ────────────────────── */
.tar-panel {
    background:#fff; border-radius:14px;
    border:1px solid #DDE8D5; overflow:hidden;
}
.tar-panel-body { padding:16px; }

/* Table override for DataTables */
#tablaTarifas { width:100% !important; }
#tablaTarifas thead tr th {
    background:#F0F5ED !important;
    font-size:.67rem; font-weight:700; letter-spacing:.05em;
    text-transform:uppercase; color:#7A9B6A;
    border-bottom:2px solid #DDE8D5 !important;
    padding:10px 10px !important; white-space:nowrap;
}
#tablaTarifas tbody tr { border-bottom:1px solid #F0F5ED; transition:background .15s; }
#tablaTarifas tbody tr:hover { background:#F7FCF4 !important; }
#tablaTarifas tbody td { padding:10px 10px; font-size:.8rem; vertical-align:middle; border-top:none !important; }

/* ── Priority badge ──────────────────────── */
.prio-badge {
    width:28px; height:28px; border-radius:8px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.72rem; font-weight:800;
    background:linear-gradient(135deg,rgba(92,122,78,.12),rgba(92,122,78,.06));
    color:#3D5234; border:1px solid rgba(92,122,78,.2);
}

/* ── Value badges ────────────────────────── */
.val-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:3px 9px; border-radius:8px; font-size:.72rem; font-weight:700;
    background:linear-gradient(135deg,rgba(16,185,129,.1),rgba(5,150,105,.06));
    color:#065F46; border:1px solid rgba(16,185,129,.2);
}

/* ── Scope badges ────────────────────────── */
.scope-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:3px 9px; border-radius:8px; font-size:.7rem; font-weight:600;
}
.scope-global   { background:rgba(37,99,235,.09);  color:#1D4ED8; border:1px solid rgba(37,99,235,.18); }
.scope-tipo     { background:rgba(92,122,78,.09);   color:#3D5234; border:1px solid rgba(92,122,78,.2); }
.scope-hab      { background:rgba(245,158,11,.09);  color:#92400E; border:1px solid rgba(245,158,11,.2); }

/* ── Vigency badges ──────────────────────── */
.vig-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; border-radius:6px; font-size:.68rem; font-weight:600;
}
.vig-vigente    { background:rgba(16,185,129,.1);  color:#065F46; border:1px solid rgba(16,185,129,.2); }
.vig-futuro     { background:rgba(37,99,235,.08);  color:#1D4ED8; border:1px solid rgba(37,99,235,.15); }
.vig-pasado     { background:#F3F4F6;               color:#6B7280; border:1px solid #E5E7EB; }
.vig-perm       { background:rgba(92,122,78,.08);   color:#4A6340; border:1px solid rgba(92,122,78,.18); }

/* ── Toggle switch override ──────────────── */
.peer-checked\:bg-gradient-to-r:checked ~ div {
    background:linear-gradient(to right,#5C7A4E,#7A9B6A) !important;
}
/* Force green for checked toggle */
input.toggle-activo:checked ~ div {
    background-image: linear-gradient(to right, #5C7A4E, #7A9B6A) !important;
}

/* ── Action icon buttons ─────────────────── */
.icn-btn {
    width:28px; height:28px; border-radius:7px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:.7rem; transition:background .15s; border:none; cursor:pointer;
    background:transparent; text-decoration:none;
}
.icn-edit   { color:#5C7A4E; }
.icn-edit:hover   { background:#EEF4EB; color:#3D5234; }
.icn-view   { color:#2563EB; }
.icn-view:hover   { background:#EFF6FF; }
.icn-del    { color:#DC2626; }
.icn-del:hover    { background:#FEF2F2; }
.icn-btn:disabled { opacity:.35; cursor:not-allowed; }

/* ── Empty state ─────────────────────────── */
.empty-state {
    text-align:center; padding:48px 24px;
}

/* ── Modal overrides ─────────────────────── */
.modal-content {
    border:none !important; border-radius:16px !important; overflow:hidden;
    box-shadow:0 20px 50px rgba(61,82,52,.18) !important;
}
.modal-header {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark)) !important;
    border-bottom:none !important; padding:16px 20px !important;
}
.modal-header .modal-title { color:#fff !important; font-size:.95rem; font-weight:700; }
.modal-header .close { color:rgba(255,255,255,.7) !important; text-shadow:none !important; font-size:1.4rem; }
.modal-header .close:hover { color:#fff !important; }
.modal-body { padding:20px !important; }

/* Modal detail fields */
.detail-field {
    background:#FAFDF8; border:1px solid #E5EDE0;
    border-radius:10px; padding:12px 14px; margin-bottom:10px;
}
.detail-field-label { font-size:.68rem; font-weight:700; color:#7A9B6A; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
.detail-field-val   { font-size:.82rem; color:#374151; }

/* Date input */
.date-input {
    border:1.5px solid #C8D9BE; border-radius:9px;
    padding:8px 12px; font-size:.82rem; width:100%;
    background:#FAFDF8; color:#374151;
    transition:border-color .2s, box-shadow .2s;
}
.date-input:focus {
    outline:none; border-color:var(--lc-gold);
    box-shadow:0 0 0 3px rgba(200,169,106,.15);
}

/* ── DataTables custom styles ────────────── */
.dataTables_filter input {
    border:1.5px solid #C8D9BE !important; border-radius:8px !important;
    padding:5px 10px !important; font-size:.78rem !important;
    background:#FAFDF8 !important; color:#374151 !important;
    margin-left:6px !important;
}
.dataTables_filter input:focus {
    outline:none !important; border-color:var(--lc-gold) !important;
    box-shadow:0 0 0 3px rgba(200,169,106,.15) !important;
}
.dataTables_info { font-size:.72rem; color:#9CA3AF; }
.paginate_button {
    border-radius:7px !important; font-size:.72rem !important;
    border:none !important; padding:4px 9px !important;
}
.paginate_button:hover:not(.disabled) {
    background:#EEF4EB !important; color:#3D5234 !important;
    border:none !important;
}
.paginate_button.current, .paginate_button.current:hover {
    background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark)) !important;
    color:#fff !important; border:none !important;
}

/* ── Scrollbar ───────────────────────────── */
.lc-scroll::-webkit-scrollbar { width:4px; height:4px; }
.lc-scroll::-webkit-scrollbar-track { background:#F0F5ED; }
.lc-scroll::-webkit-scrollbar-thumb { background:#A8C4A0; border-radius:4px; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background:var(--lc-green); }

/* Toast overrides */
.toast-success { background-color:#ECFDF5 !important; color:#065F46 !important; border:1px solid rgba(16,185,129,.24) !important; }
.toast-error   { background-color:#FEF2F2 !important; color:#991B1B !important; border:1px solid rgba(239,68,68,.24) !important; }
.toast-info    { background-color:#EFF6FF !important; color:#1E40AF !important; border:1px solid rgba(59,130,246,.24) !important; }

/* Disable Bootstrap overriding Tailwind gradients */
.bg-gradient-to-br { background-image:none !important; }
</style>

<!-- ════════════════ TARIFAS PAGE ══════════════════════════ -->
<style id="tarifas-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.tar-page {
    --tar-brand: var(--brand-primary, #1B2746);
    --tar-brand-2: var(--brand-secondary, #0F172A);
    --tar-accent: var(--brand-accent, #BD9441);
    --tar-accent-dark: color-mix(in srgb, var(--tar-accent) 72%, #3F2E12);
    --tar-accent-soft: color-mix(in srgb, var(--tar-accent) 14%, #FFFFFF);
    --tar-accent-line: color-mix(in srgb, var(--tar-accent) 34%, #E8DDCA);
    --tar-bg: #F6F2EA;
    --tar-bg-2: #FBF8F2;
    --tar-surface: rgba(255,255,255,.96);
    --tar-surface-warm: #FCFAF5;
    --tar-border: color-mix(in srgb, var(--tar-brand) 11%, #E7E1D4);
    --tar-text: #1B2746;
    --tar-muted: #6C7689;
    --tar-success: #1E9E63;
    --tar-success-soft: #E8F4ED;
    --tar-danger: #B42318;
    --tar-danger-soft: #FDECEC;
    --tar-info: #2F77E0;
    --tar-info-soft: #E8F0FC;
    --tar-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --tar-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    color: var(--tar-text);
    font-family: var(--tar-sans);
    background:
        linear-gradient(135deg, rgba(255,255,255,.34) 0 25%, transparent 25% 50%) 0 0 / 22px 22px,
        linear-gradient(180deg, var(--tar-bg-2), var(--tar-bg)) !important;
}

.tar-topbar {
    background: transparent !important;
    border-bottom: 1px solid var(--tar-border) !important;
}
.tar-topbar::after {
    content: none !important;
}
.tar-topbar > div,
.tar-page > .px-3 {
    max-width: 1680px;
    margin-left: auto;
    margin-right: auto;
}
.tar-topbar .flex.items-center.gap-3 > div[style] {
    width: 46px !important;
    height: 46px !important;
    border-radius: 13px !important;
    background: linear-gradient(150deg, var(--tar-brand), var(--tar-brand-2)) !important;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--tar-brand) 58%, transparent) !important;
}
.tar-topbar .flex.items-center.gap-3 > div[style] i {
    color: #FFFFFF !important;
}
.tar-topbar h1 {
    color: var(--tar-brand) !important;
    font-family: var(--tar-serif);
    font-size: clamp(2rem, 3vw, 2.6rem) !important;
    font-weight: 700 !important;
    letter-spacing: 0;
    line-height: 1 !important;
}
.tar-topbar p {
    color: var(--tar-muted) !important;
    font-weight: 500;
}

.btn-tar {
    min-height: 42px;
    border-radius: 11px !important;
    font-weight: 800 !important;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease !important;
}
.btn-tar:focus-visible,
.btn-reset:focus-visible,
.icn-btn:focus-visible,
.filter-select:focus,
.date-input:focus,
.dataTables_filter input:focus {
    outline: 3px solid color-mix(in srgb, var(--tar-accent) 34%, transparent) !important;
    outline-offset: 2px;
}
.btn-tar.calc {
    background: linear-gradient(135deg, var(--tar-brand), var(--tar-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 24px -12px color-mix(in srgb, var(--tar-brand) 66%, transparent);
}
.btn-tar.new {
    background: linear-gradient(135deg, var(--tar-accent), var(--tar-accent-dark)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 24px -12px color-mix(in srgb, var(--tar-accent) 70%, transparent);
}
.btn-tar.calc:hover,
.btn-tar.new:hover {
    color: #FFFFFF !important;
    box-shadow: 0 16px 30px -14px rgba(27,39,70,.32);
}

.tar-page .grid > .tar-stat:nth-child(1) { --ws: var(--tar-brand) !important; }
.tar-page .grid > .tar-stat:nth-child(2) { --ws: var(--tar-success) !important; }
.tar-page .grid > .tar-stat:nth-child(3) { --ws: var(--tar-info) !important; }
.tar-page .grid > .tar-stat:nth-child(4) { --ws: var(--tar-accent) !important; }
.tar-stat,
.tar-filters,
.tar-panel {
    background: var(--tar-surface) !important;
    border: 1px solid var(--tar-border) !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
}
.tar-stat {
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease !important;
}
.tar-stat::after {
    height: 1px !important;
    background: color-mix(in srgb, var(--ws, var(--tar-brand)) 44%, var(--tar-border)) !important;
}
.tar-stat:hover {
    border-color: color-mix(in srgb, var(--ws, var(--tar-brand)) 28%, var(--tar-border)) !important;
    box-shadow: 0 16px 34px -24px color-mix(in srgb, var(--ws, var(--tar-brand)) 58%, #172033) !important;
}
.tar-stat-icon {
    width: 42px !important;
    height: 42px !important;
    border-radius: 12px !important;
    background: color-mix(in srgb, var(--ws, var(--tar-brand)) 12%, #FFFFFF) !important;
    color: var(--ws, var(--tar-brand)) !important;
}
.tar-stat > div:first-child > span {
    color: var(--tar-brand) !important;
    font-family: var(--tar-serif);
    font-size: 1.75rem !important;
    font-weight: 700 !important;
    letter-spacing: 0;
}
.tar-stat p,
.tar-page .text-gray-400,
.tar-page .text-gray-500,
.tar-page .text-gray-300 {
    color: var(--tar-muted) !important;
}
.tar-page .text-gray-800,
.tar-page .text-gray-700,
.tar-page .text-gray-600 {
    color: var(--tar-text) !important;
}

.tar-filters-hd {
    background: var(--tar-surface-warm) !important;
    border-bottom: 1px solid var(--tar-border) !important;
}
.tar-filters-hd div[style] {
    background: var(--tar-accent-soft) !important;
    border: 1px solid var(--tar-accent-line);
}
.tar-filters-hd div[style] i,
.tar-filters-hd span {
    color: var(--tar-brand) !important;
}
.btn-reset {
    color: var(--tar-muted) !important;
    font-weight: 800;
}
.btn-reset:hover {
    color: var(--tar-accent-dark) !important;
}
.filter-label {
    color: var(--tar-brand) !important;
    font-weight: 800 !important;
    letter-spacing: .07em;
}
.filter-select,
.date-input,
.dataTables_filter input {
    background: var(--tar-surface-warm) !important;
    border: 1px solid var(--tar-border) !important;
    border-radius: 11px !important;
    color: var(--tar-text) !important;
    font-weight: 600;
    box-shadow: none !important;
}
.filter-select:focus,
.date-input:focus,
.dataTables_filter input:focus {
    border-color: var(--tar-accent) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--tar-accent) 24%, transparent) !important;
}

.tar-panel-body {
    padding: 18px !important;
}
#tablaTarifas thead tr th,
.tar-panel table thead th {
    background: var(--tar-surface-warm) !important;
    color: var(--tar-muted) !important;
    border-bottom: 1px solid var(--tar-border) !important;
    font-weight: 800 !important;
}
#tablaTarifas tbody tr,
.tar-panel table tbody tr {
    border-bottom: 1px solid var(--tar-border) !important;
}
#tablaTarifas tbody tr:hover,
.tar-panel table tbody tr:hover {
    background: var(--tar-bg-2) !important;
}
#tablaTarifas tbody td {
    color: var(--tar-text);
}

.prio-badge,
.val-badge,
.scope-badge,
.vig-badge {
    border-radius: 999px !important;
    font-weight: 800 !important;
}
.prio-badge {
    background: var(--tar-surface-warm) !important;
    border: 1px solid var(--tar-border) !important;
    color: var(--tar-brand) !important;
}
.val-badge {
    background: var(--tar-success-soft) !important;
    border: 1px solid color-mix(in srgb, var(--tar-success) 24%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--tar-success) 68%, #123322) !important;
}
.scope-global,
.vig-futuro {
    background: var(--tar-info-soft) !important;
    border: 1px solid color-mix(in srgb, var(--tar-info) 24%, #FFFFFF) !important;
    color: var(--tar-info) !important;
}
.scope-tipo,
.vig-perm {
    background: var(--tar-accent-soft) !important;
    border: 1px solid var(--tar-accent-line) !important;
    color: var(--tar-accent-dark) !important;
}
.scope-hab {
    background: var(--tar-bg-2) !important;
    border: 1px solid var(--tar-border) !important;
    color: var(--tar-brand) !important;
}
.vig-vigente {
    background: var(--tar-success-soft) !important;
    border: 1px solid color-mix(in srgb, var(--tar-success) 24%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--tar-success) 68%, #123322) !important;
}
.vig-pasado {
    background: #F1F3F5 !important;
    border: 1px solid #E2E6EA !important;
    color: var(--tar-muted) !important;
}

input.toggle-activo:checked ~ div {
    background-image: linear-gradient(to right, var(--tar-brand), var(--tar-brand-2)) !important;
}
.toggle-activo ~ div {
    border: 1px solid var(--tar-border);
}
.toggle-activo ~ span {
    color: var(--tar-muted) !important;
    font-weight: 800;
}
.toggle-activo:checked ~ span {
    color: var(--tar-brand) !important;
}

.icn-btn {
    width: 32px !important;
    height: 32px !important;
    border-radius: 10px !important;
    background: var(--tar-surface-warm) !important;
    border: 1px solid var(--tar-border) !important;
    transition: transform .16s ease, background .16s ease, border-color .16s ease !important;
}
.icn-btn:hover {
    transform: translateY(-1px);
}
.icn-edit {
    color: var(--tar-accent-dark) !important;
}
.icn-edit:hover {
    background: var(--tar-accent-soft) !important;
    border-color: var(--tar-accent-line) !important;
}
.icn-view {
    color: var(--tar-info) !important;
}
.icn-view:hover {
    background: var(--tar-info-soft) !important;
    border-color: color-mix(in srgb, var(--tar-info) 24%, #FFFFFF) !important;
}
.icn-del {
    color: var(--tar-danger) !important;
}
.icn-del:hover {
    background: var(--tar-danger-soft) !important;
    border-color: color-mix(in srgb, var(--tar-danger) 20%, #FFFFFF) !important;
}
.icn-btn:disabled {
    opacity: .36 !important;
    transform: none !important;
}

.empty-state {
    background: var(--tar-bg-2);
    border: 1px dashed var(--tar-border);
    border-radius: 14px;
}
.empty-state h3 {
    color: var(--tar-brand) !important;
}
.empty-state div[style] {
    background: var(--tar-accent-soft) !important;
    border: 1px solid var(--tar-accent-line);
}
.empty-state div[style] i {
    color: var(--tar-accent-dark) !important;
}

.modal-content {
    border: 1px solid var(--tar-border) !important;
    border-radius: 18px !important;
    box-shadow: 0 26px 70px -34px rgba(27,39,70,.55) !important;
}
.modal-header {
    background: linear-gradient(135deg, var(--tar-brand), var(--tar-brand-2)) !important;
}
.modal-header .modal-title {
    color: #FFFFFF !important;
    font-weight: 800 !important;
}
.modal-body {
    background: var(--tar-bg-2);
}
.detail-field {
    background: var(--tar-surface) !important;
    border: 1px solid var(--tar-border) !important;
    border-radius: 12px !important;
}
.detail-field-label {
    color: var(--tar-muted) !important;
    font-weight: 800 !important;
}
.detail-field-val {
    color: var(--tar-text) !important;
}

.dataTables_wrapper .dataTables_info {
    color: var(--tar-muted) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    border-radius: 9px !important;
    color: var(--tar-muted) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.disabled) {
    background: var(--tar-accent-soft) !important;
    color: var(--tar-accent-dark) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--tar-brand) !important;
    color: #FFFFFF !important;
}
.toast-success,
.toast-error,
.toast-info {
    border-radius: 12px !important;
    box-shadow: 0 16px 34px -24px rgba(27,39,70,.42) !important;
}

.lc-scroll::-webkit-scrollbar-track { background: var(--tar-bg-2) !important; }
.lc-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--tar-brand) 24%, #D7CCBA) !important; }
.lc-scroll::-webkit-scrollbar-thumb:hover { background: var(--tar-brand) !important; }

/* Layout upgrade: pricing rules as a control board. */
.tar-page .tar-topbar > div {
    padding-bottom: 18px !important;
}

.tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 {
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
}

.tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat {
    grid-column: span 3;
    min-height: 146px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    position: relative;
}

.tar-page .tar-stat::before {
    content: '';
    position: absolute;
    inset: 12px 12px auto auto;
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--ws, var(--tar-brand)) 11%, transparent);
    pointer-events: none;
}

.tar-page .tar-stat > * {
    position: relative;
    z-index: 1;
}

.tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat > .flex:first-child {
    display: grid !important;
    grid-template-columns: 1fr auto 1fr;
    align-items: center !important;
    column-gap: 12px;
}

.tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat > .flex:first-child .tar-stat-icon {
    grid-column: 2;
    justify-self: center;
}

.tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat > .flex:first-child > span {
    grid-column: 3;
    justify-self: end;
}

.tar-page .tar-stat-icon {
    display: grid !important;
    place-items: center;
    flex: 0 0 36px;
    line-height: 1;
    text-align: center;
}

.tar-page .tar-stat-icon i {
    display: block;
    line-height: 1;
    margin: 0;
}

.tar-page .tar-filters {
    margin-bottom: 16px;
    overflow: hidden;
}

.tar-page .tar-filters-hd {
    min-height: 50px;
    padding: 14px 16px !important;
}

.tar-page .tar-filters > .p-4 {
    grid-template-columns: repeat(3, minmax(180px, 1fr)) !important;
    gap: 12px !important;
}

.tar-page .filter-select,
.tar-page .date-input {
    min-height: 42px;
}

.tar-page .tar-panel {
    overflow: hidden;
}

.tar-page .tar-panel-body {
    padding: 16px !important;
}

.tar-page #tablaTarifas,
.tar-page .tar-panel table {
    border-collapse: separate !important;
    border-spacing: 0 8px;
}

.tar-page #tablaTarifas thead tr,
.tar-page .tar-panel table thead tr {
    transform: translateY(4px);
}

.tar-page #tablaTarifas tbody tr,
.tar-page .tar-panel table tbody tr {
    background: var(--tar-surface) !important;
    box-shadow: 0 1px 2px rgba(27,39,70,.04);
}

.tar-page #tablaTarifas tbody td,
.tar-page .tar-panel table tbody td {
    border-top: 1px solid color-mix(in srgb, var(--tar-border) 76%, transparent);
    border-bottom: 1px solid color-mix(in srgb, var(--tar-border) 76%, transparent);
    padding-top: 12px !important;
    padding-bottom: 12px !important;
}

.tar-page #tablaTarifas tbody td:first-child,
.tar-page .tar-panel table tbody td:first-child {
    border-left: 1px solid color-mix(in srgb, var(--tar-border) 76%, transparent);
    border-radius: 14px 0 0 14px;
    padding-left: 12px !important;
}

.tar-page #tablaTarifas tbody td:last-child,
.tar-page .tar-panel table tbody td:last-child {
    border-right: 1px solid color-mix(in srgb, var(--tar-border) 76%, transparent);
    border-radius: 0 14px 14px 0;
    padding-right: 12px !important;
}

.tar-page #tablaTarifas tbody tr:hover td,
.tar-page .tar-panel table tbody tr:hover td {
    border-color: color-mix(in srgb, var(--tar-accent) 30%, var(--tar-border));
}

.tar-page #tablaTarifas tbody td:nth-child(2) p:first-child {
    font-size: .84rem !important;
    font-weight: 800 !important;
}

.tar-page .prio-badge {
    min-width: 34px;
    justify-content: center;
}

.tar-page .val-badge,
.tar-page .scope-badge,
.tar-page .vig-badge {
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 9px !important;
}

.tar-page .dataTables_wrapper .dataTables_filter input {
    min-height: 38px;
    margin-left: 8px;
}

.tar-page .modal-content {
    overflow: hidden;
}

@media (max-width: 640px) {
    .tar-topbar h1 {
        font-size: 1.9rem !important;
    }
    .tar-page > .px-3 {
        padding-left: .875rem;
        padding-right: .875rem;
    }
    .tar-panel-body {
        padding: 12px !important;
    }

    .tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4,
    .tar-page .tar-filters > .p-4 {
        grid-template-columns: 1fr !important;
    }

    .tar-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat {
        grid-column: span 1;
    }

    .tar-page #tablaTarifas,
    .tar-page .tar-panel table {
        border-spacing: 0;
    }
}
</style>

<style id="tarifas-index-create-style">
.tarifa-index-page {
    --tar-gold: var(--brand-accent, #BD9441);
    --tar-gold-soft: color-mix(in srgb, var(--tar-gold) 15%, #FFFFFF);
    --tar-gold-line: color-mix(in srgb, var(--tar-gold) 42%, #E4D4B0);
    --tar-gold-ink: color-mix(in srgb, var(--tar-gold) 72%, #000);
    --tar-ivory: #F6F2EA;
    --tar-ivory-2: #FBF8F2;
    --tar-heading: #111827;
    --tar-sky: #3E7CB1;
    --tar-teal: #2F7D72;
    --tar-plum: #7C4F86;
    --tar-coral: #C66A5A;
    --tar-amber: #D0963A;
    padding: 1rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--tar-gold) 10%, transparent), transparent 60%),
        radial-gradient(900px 360px at 22% 10%, color-mix(in srgb, var(--tar-teal) 6%, transparent), transparent 58%),
        linear-gradient(180deg, var(--tar-ivory-2), var(--tar-ivory)) !important;
}

.tarifa-index-page .tar-topbar,
.tarifa-index-page .tar-topbar.bg-white {
    margin: 0 auto 18px;
    background: transparent !important;
    border: 0 !important;
}

.tarifa-index-page .tar-topbar > div,
.tarifa-index-page > .px-3 {
    width: 100% !important;
    max-width: none !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
}

.tarifa-index-page .tar-topbar > div {
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}

.tarifa-index-hero-inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
}

.tarifa-index-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(960px, 100%);
}

.tarifa-index-hero-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex: 0 0 48px;
    border-radius: 15px;
    color: #fff;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--tar-plum), var(--tar-brand) 54%, var(--tar-teal));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tar-brand) 72%, transparent);
}

.tarifa-index-title-copy {
    min-width: 0;
    padding-top: 1px;
}

.tarifa-index-page .tarifa-page-kicker {
    margin: 0 0 2px;
    color: var(--tar-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.tarifa-index-page .tarifa-page-title {
    margin: 0;
    color: var(--tar-brand) !important;
    font-family: var(--tar-serif);
    font-size: clamp(2.35rem, 4vw, 3.35rem) !important;
    font-weight: 700 !important;
    line-height: .98 !important;
    letter-spacing: 0;
    text-wrap: balance;
}

.tarifa-index-page .tarifa-page-subtitle,
.tarifa-index-page .tar-topbar p.text-gray-400 {
    max-width: 920px;
    margin-top: 9px !important;
    color: var(--tar-muted) !important;
    font-size: .94rem !important;
    font-weight: 600;
    line-height: 1.55;
}

.tarifa-index-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 10px;
    min-width: 280px;
}

.tarifa-index-page .btn-tar {
    min-height: 44px;
    padding: 10px 15px !important;
    border-radius: 12px !important;
}

.tarifa-index-page .btn-tar.calc {
    background: linear-gradient(135deg, var(--tar-brand), color-mix(in srgb, var(--tar-sky) 48%, var(--tar-brand-2))) !important;
}

.tarifa-index-page .btn-tar.new {
    background: linear-gradient(135deg, var(--tar-success), color-mix(in srgb, var(--tar-teal) 80%, #111827)) !important;
    box-shadow: 0 13px 26px -13px color-mix(in srgb, var(--tar-success) 66%, transparent);
}

.tarifa-index-summary {
    --tar-summary-line: color-mix(in srgb, var(--tar-brand) 10%, #E7E1D4);
    margin-bottom: 18px !important;
    padding: 14px;
    border: 1px solid var(--tar-summary-line);
    border-radius: 22px;
    background:
        radial-gradient(720px 180px at 0% 0%, color-mix(in srgb, var(--tar-plum) 7%, transparent), transparent 70%),
        radial-gradient(620px 180px at 100% 20%, color-mix(in srgb, var(--tar-sky) 7%, transparent), transparent 72%),
        rgba(255,255,255,.42);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.78);
    backdrop-filter: blur(8px);
}

.tarifa-index-summary .tar-stat {
    min-height: 156px !important;
    padding: 18px !important;
    border-radius: 16px !important;
    display: grid !important;
    grid-template-rows: auto auto 1fr;
    align-content: stretch;
    gap: 9px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--ws, var(--tar-brand)) 8%, #FFFFFF), #FFFFFF 48%),
        var(--tar-surface) !important;
}

.tarifa-index-summary .tar-stat::before {
    content: none !important;
}

.tarifa-index-summary .tar-stat::after {
    height: 3px !important;
    opacity: 1 !important;
    background: linear-gradient(90deg, var(--ws, var(--tar-brand)), color-mix(in srgb, var(--ws, var(--tar-brand)) 34%, transparent)) !important;
}

.tarifa-index-summary .tar-stat-icon {
    width: 44px !important;
    height: 44px !important;
    border-radius: 14px !important;
    background: linear-gradient(145deg, color-mix(in srgb, var(--ws, var(--tar-brand)) 18%, #FFFFFF), #FFFFFF) !important;
    border: 1px solid color-mix(in srgb, var(--ws, var(--tar-brand)) 28%, var(--tar-border));
    color: color-mix(in srgb, var(--ws, var(--tar-brand)) 78%, #111827) !important;
}

.tarifa-index-summary .tar-stat > .flex:first-child {
    display: flex !important;
    align-items: flex-start !important;
    justify-content: space-between !important;
    gap: 14px;
    margin-bottom: 0 !important;
}

.tarifa-index-summary .tar-stat > .flex:first-child .tar-stat-icon {
    grid-column: auto !important;
    justify-self: auto !important;
}

.tarifa-index-summary .tar-stat > .flex:first-child > span {
    grid-column: auto !important;
    justify-self: auto !important;
    min-width: 2.5ch;
    color: color-mix(in srgb, var(--ws, var(--tar-brand)) 72%, #111827) !important;
    font-family: var(--tar-serif);
    font-size: clamp(2rem, 3vw, 2.7rem) !important;
    font-weight: 700 !important;
    line-height: .9;
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.tarifa-index-summary .tar-stat > p:first-of-type {
    align-self: end;
    color: color-mix(in srgb, var(--ws, var(--tar-brand)) 64%, var(--tar-muted)) !important;
    font-size: .7rem !important;
    font-weight: 900 !important;
    letter-spacing: .1em !important;
    line-height: 1.2;
    margin: 0 !important;
}

.tarifa-index-summary .tar-stat > p:last-child {
    max-width: 16rem;
    margin-top: 0 !important;
    color: var(--tar-muted) !important;
    font-size: .8rem !important;
    font-weight: 600;
    line-height: 1.38;
}

.tarifa-index-summary .tar-stat-types .space-y-1 {
    margin-top: 0 !important;
    align-self: end;
    display: grid;
    gap: 7px;
}

.tarifa-index-summary .tar-stat-types .space-y-1 > div {
    min-height: 28px;
    padding: 5px 8px;
    border: 1px solid color-mix(in srgb, var(--tar-amber) 16%, var(--tar-border));
    border-radius: 10px;
    background: rgba(255,255,255,.7);
}

.tarifa-index-summary .tar-stat-types .space-y-1 > div span:last-child {
    color: color-mix(in srgb, var(--tar-amber) 72%, #111827) !important;
    font-variant-numeric: tabular-nums;
}

.tarifa-index-summary .tar-stat:hover {
    transform: translateY(-2px);
}

.tarifa-index-page .tar-filters,
.tarifa-index-page .tar-panel {
    position: relative;
    border-radius: 18px !important;
    overflow: hidden;
}

.tarifa-index-page .tar-filters {
    --tar-card-accent: var(--tar-sky);
    margin-bottom: 16px;
}

.tarifa-index-page .tar-panel {
    --tar-card-accent: var(--tar-teal);
}

.tarifa-index-page .tar-filters::before,
.tarifa-index-page .tar-panel::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--tar-card-accent), color-mix(in srgb, var(--tar-card-accent) 20%, transparent));
    opacity: .92;
    z-index: 2;
}

.tarifa-section-header,
.tar-panel-hd {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 18px 15px 20px !important;
    border-bottom: 1px solid var(--tar-border) !important;
    background:
        radial-gradient(360px 120px at 0% 0%, color-mix(in srgb, var(--tar-card-accent, var(--tar-gold)) 12%, transparent), transparent 70%),
        linear-gradient(180deg, #FFFFFF, var(--tar-surface-warm)) !important;
}

.tarifa-section-title {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.tarifa-section-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    flex: 0 0 34px;
    border-radius: 11px;
    color: color-mix(in srgb, var(--tar-card-accent, var(--tar-brand)) 76%, #111827);
    background: color-mix(in srgb, var(--tar-card-accent, var(--tar-brand)) 12%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--tar-card-accent, var(--tar-brand)) 24%, var(--tar-border));
}

.tarifa-section-title span,
.tar-panel-title {
    display: block;
    color: var(--tar-heading) !important;
    font-size: .96rem;
    font-weight: 900;
    letter-spacing: 0;
}

.tarifa-section-title small,
.tar-panel-subtitle {
    display: block;
    margin-top: 2px;
    color: var(--tar-muted);
    font-size: .78rem;
    font-weight: 600;
    line-height: 1.35;
}

.tarifa-index-page .btn-reset {
    min-height: 36px;
    padding: 8px 12px;
    border: 1px solid var(--tar-border);
    border-radius: 11px;
    background: #FFFFFF;
    color: var(--tar-muted) !important;
    transition: transform .16s ease, border-color .16s ease, background .16s ease;
}

.tarifa-index-page .btn-reset:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--tar-coral) 24%, var(--tar-border));
    background: color-mix(in srgb, var(--tar-coral) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--tar-coral) 72%, #111827) !important;
}

.tar-panel-count {
    flex: 0 0 auto;
    padding: 8px 12px;
    border: 1px solid color-mix(in srgb, var(--tar-card-accent) 22%, var(--tar-border));
    border-radius: 999px;
    background: #FFFFFF;
    color: color-mix(in srgb, var(--tar-card-accent) 70%, #111827);
    font-size: .78rem;
    font-weight: 900;
}

.tarifa-index-page .tar-filters > .p-4 {
    padding: 16px 18px 18px 20px !important;
    background: rgba(255,255,255,.74);
}

.tarifa-index-page .filter-select,
.tarifa-index-page .date-input,
.tarifa-index-page .dataTables_filter input {
    background: #FFFFFF !important;
}

.tarifa-index-page .filter-select:hover,
.tarifa-index-page .date-input:hover,
.tarifa-index-page .dataTables_filter input:hover {
    border-color: color-mix(in srgb, var(--tar-sky) 26%, var(--tar-border)) !important;
}

.tarifa-index-page .tar-panel-body {
    padding: 16px 18px 20px 20px !important;
    background: rgba(255,255,255,.7);
}

.tarifa-index-page #tablaTarifas thead tr th {
    background: transparent !important;
    color: var(--tar-muted) !important;
    border-bottom: 0 !important;
    font-size: .68rem !important;
}

.tarifa-index-page #tablaTarifas tbody td {
    background: #FFFFFF;
    transition: border-color .16s ease, background .16s ease;
}

.tarifa-index-page #tablaTarifas tbody td:first-child {
    box-shadow: inset 4px 0 0 var(--tar-gold);
}

.tarifa-index-page #tablaTarifas tbody tr[data-vigencia="vigente"] td:first-child {
    box-shadow: inset 4px 0 0 var(--tar-success);
}

.tarifa-index-page #tablaTarifas tbody tr[data-vigencia="futuro"] td:first-child {
    box-shadow: inset 4px 0 0 var(--tar-sky);
}

.tarifa-index-page #tablaTarifas tbody tr[data-vigencia="pasado"] td:first-child {
    box-shadow: inset 4px 0 0 #A6ADB8;
}

.tarifa-index-page #tablaTarifas tbody tr:hover td {
    background:
        radial-gradient(260px 90px at 0 0, color-mix(in srgb, var(--tar-card-accent) 9%, transparent), transparent 75%),
        #FFFFFF !important;
}

.tarifa-index-page .prio-badge {
    background: color-mix(in srgb, var(--tar-gold) 12%, #FFFFFF) !important;
    border-color: var(--tar-gold-line) !important;
    color: var(--tar-gold-ink) !important;
}

.tarifa-index-page .val-badge {
    background: color-mix(in srgb, var(--tar-success) 13%, #FFFFFF) !important;
}

.tarifa-index-page .scope-global,
.tarifa-index-page .vig-futuro {
    background: color-mix(in srgb, var(--tar-sky) 12%, #FFFFFF) !important;
}

.tarifa-index-page .scope-tipo,
.tarifa-index-page .vig-perm {
    background: color-mix(in srgb, var(--tar-plum) 10%, #FFFFFF) !important;
    border-color: color-mix(in srgb, var(--tar-plum) 22%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--tar-plum) 72%, #111827) !important;
}

.tarifa-index-page .scope-hab {
    background: color-mix(in srgb, var(--tar-amber) 12%, #FFFFFF) !important;
    border-color: color-mix(in srgb, var(--tar-amber) 28%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--tar-amber) 68%, #111827) !important;
}

.tarifa-index-page .icn-btn {
    background: #FFFFFF !important;
}

.tarifa-index-page .empty-state {
    padding: 42px 20px;
    background:
        radial-gradient(360px 160px at 50% 0%, color-mix(in srgb, var(--tar-gold) 10%, transparent), transparent 72%),
        #FFFFFF !important;
}

@media (max-width: 900px) {
    .tarifa-index-hero-inner {
        flex-direction: column;
    }
    .tarifa-index-actions {
        width: 100%;
        min-width: 0;
        justify-content: stretch;
    }
    .tarifa-index-actions .btn-tar {
        flex: 1 1 0;
    }
    .tarifa-index-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .tarifa-index-page > .px-3 > .grid.grid-cols-2.lg\:grid-cols-4 > .tar-stat {
        grid-column: span 1 !important;
    }
}

@media (min-width: 640px) {
    .tarifa-index-page {
        padding: 1.5rem !important;
    }
}

@media (max-width: 640px) {
    .tarifa-index-page {
        padding: 1rem !important;
    }
    .tarifa-index-title-lockup {
        grid-template-columns: 44px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }
    .tarifa-index-hero-icon {
        width: 44px;
        height: 44px;
        flex-basis: 44px;
        border-radius: 14px;
    }
    .tarifa-index-page .tarifa-page-title {
        font-size: 2rem !important;
    }
    .tarifa-index-summary .tar-stat {
        min-height: 112px !important;
    }
    .tarifa-section-header,
    .tar-panel-hd {
        align-items: flex-start;
        flex-direction: column;
    }
    .tar-panel-count {
        align-self: flex-start;
    }
}

/* Redesigned dynamic-pricing metrics. */
.tarifa-index-summary.tarifa-metrics-grid {
    display: grid !important;
    grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
    gap: 12px !important;
    margin-bottom: 18px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
    backdrop-filter: none !important;
}

.tar-metric-card {
    --metric: var(--tar-brand);
    grid-column: span 3;
    position: relative;
    min-height: 142px;
    display: grid;
    grid-template-rows: auto auto auto;
    gap: 9px;
    overflow: hidden;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--metric) 16%, var(--tar-border));
    border-radius: 18px;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--metric) 5%, #FFFFFF), #FFFFFF 68%),
        #FFFFFF;
    box-shadow: 0 1px 2px rgba(17, 24, 39, .04), 0 18px 34px -28px color-mix(in srgb, var(--metric) 45%, rgba(17,24,39,.28));
    transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.tarifa-index-summary > .tar-stat {
    display: none !important;
}

.tar-metric-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: linear-gradient(180deg, var(--metric), color-mix(in srgb, var(--metric) 20%, transparent));
}

.tar-metric-card::after {
    content: "";
    position: absolute;
    top: -42px;
    right: -32px;
    width: 92px;
    height: 92px;
    border-radius: 26px;
    background: color-mix(in srgb, var(--metric) 9%, transparent);
    transform: rotate(14deg);
    pointer-events: none;
}

.tar-metric-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--metric) 32%, var(--tar-border));
    box-shadow: 0 1px 2px rgba(17, 24, 39, .04), 0 22px 42px -30px color-mix(in srgb, var(--metric) 58%, rgba(17,24,39,.34));
}

.tar-metric-card > * {
    position: relative;
    z-index: 1;
}

.tar-metric-head,
.tar-metric-value-row,
.tar-type-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}

.tar-metric-label {
    min-width: 0;
}

.tar-metric-label span {
    display: block;
    color: color-mix(in srgb, var(--metric) 66%, var(--tar-muted));
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .1em;
    line-height: 1.1;
    text-transform: uppercase;
}

.tar-metric-label small {
    display: block;
    margin-top: 5px;
    color: var(--tar-muted);
    font-size: .72rem;
    font-weight: 700;
    line-height: 1.28;
}

.tar-metric-icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    flex: 0 0 34px;
    border-radius: 12px;
    color: color-mix(in srgb, var(--metric) 82%, #111827);
    background: color-mix(in srgb, var(--metric) 10%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--metric) 22%, var(--tar-border));
}

.tar-metric-number {
    color: color-mix(in srgb, var(--metric) 68%, var(--tar-heading));
    font-family: var(--tar-serif);
    font-size: clamp(2rem, 3.2vw, 2.65rem);
    font-weight: 750;
    line-height: .82;
    letter-spacing: 0;
    font-variant-numeric: tabular-nums;
}

.tar-metric-status {
    min-height: 26px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: 999px;
    color: color-mix(in srgb, var(--metric) 72%, #111827);
    background: color-mix(in srgb, var(--metric) 8%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--metric) 18%, var(--tar-border));
    font-size: .7rem;
    font-weight: 900;
    white-space: nowrap;
}

.tar-metric-line {
    width: 100%;
    height: 3px;
    overflow: hidden;
    border-radius: 999px;
    background: color-mix(in srgb, var(--metric) 10%, #EEF2F7);
}

.tar-metric-line span {
    display: block;
    width: var(--metric-line, 42%);
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--metric), color-mix(in srgb, var(--metric) 35%, #FFFFFF));
}

.tar-metric-active { --metric: var(--tar-plum); --metric-line: 48%; }
.tar-metric-current { --metric: var(--tar-success); --metric-line: 62%; }
.tar-metric-scheduled { --metric: var(--tar-sky); --metric-line: 38%; }
.tar-metric-types { --metric: var(--tar-amber); }

.tar-type-breakdown {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px;
    align-self: auto;
}

.tar-type-row {
    min-height: 32px;
    padding: 5px 7px;
    border: 1px solid color-mix(in srgb, var(--metric) 18%, var(--tar-border));
    border-radius: 11px;
    background: rgba(255,255,255,.7);
}

.tar-type-row span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-width: 0;
    color: var(--tar-muted);
    font-size: .7rem;
    font-weight: 800;
}

.tar-type-row strong {
    color: color-mix(in srgb, var(--metric) 70%, #111827);
    font-size: .8rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.tar-type-dot {
    width: 8px;
    height: 8px;
    flex: 0 0 8px;
    border-radius: 999px;
    background: var(--dot-color, var(--metric));
}

@media (max-width: 1100px) {
    .tar-metric-card {
        grid-column: span 6;
    }
}

@media (max-width: 640px) {
    .tarifa-index-summary.tarifa-metrics-grid {
        grid-template-columns: 1fr !important;
    }

    .tar-metric-card {
        grid-column: 1 / -1;
        min-height: 124px;
    }

    .tar-type-breakdown {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="tar-page tarifa-index-page hotel-page min-h-screen">

    <!-- Top Bar -->
    <div class="tar-topbar tarifa-index-hero bg-white">
        <div class="px-3 sm:px-5 lg:px-7 py-4">
            <div class="tarifa-index-hero-inner">

                <!-- Title -->
                <div class="tarifa-index-title-lockup">
                    <div class="tarifa-index-hero-icon">
                        <i class="fas fa-tags text-lg"></i>
                    </div>
                    <div class="tarifa-index-title-copy">
                        <p class="tarifa-page-kicker">Operaci&oacute;n hotelera</p>
                        <h1 class="tarifa-page-title text-base sm:text-xl font-bold text-[#3D5234] leading-tight">Tarifas din&aacute;micas</h1>
                        <p class="tarifa-page-subtitle text-xs text-gray-400 mt-0.5">Administra incrementos y promociones de precios con lectura clara de vigencia, alcance y estado · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="tarifa-index-actions">
                    <button onclick="previsualizarPrecios()" class="btn-tar calc flex-1 sm:flex-none">
                        <i class="fas fa-calculator text-xs"></i>
                        <span>Calcular</span><span class="hidden sm:inline"> Precios</span>
                    </button>
                    <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new flex-1 sm:flex-none">
                        <i class="fas fa-plus text-xs"></i>
                        <span>Nuevo</span><span class="hidden sm:inline"> Incremento</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-3 sm:px-5 lg:px-7 py-5">

        <!-- Stat Widgets -->
        <?php $total_tipos_incremento = (int)($estadisticas['porcentaje'] ?? 0) + (int)($estadisticas['monto_fijo'] ?? 0); ?>
        <div class="tarifa-index-summary tarifa-metrics-grid mb-5" aria-label="Resumen de tarifas dinamicas">

            <!-- Activos -->
            <article class="tar-metric-card tar-metric-active">
                <div class="tar-metric-head">
                    <div class="tar-metric-label">
                        <span>Activos</span>
                        <small>Incrementos habilitados</small>
                    </div>
                    <div class="tar-metric-icon"><i class="fas fa-list"></i></div>
                </div>
                <div class="tar-metric-value-row">
                    <strong class="tar-metric-number"><?= (int)($estadisticas['activos'] ?? 0) ?></strong>
                    <span class="tar-metric-status"><i class="fas fa-bolt"></i> Listos</span>
                </div>
                <div class="tar-metric-line"><span></span></div>
            </article>

            <!-- Vigentes -->
            <article class="tar-metric-card tar-metric-current">
                <div class="tar-metric-head">
                    <div class="tar-metric-label">
                        <span>Vigentes</span>
                        <small>Aplic&aacute;ndose hoy</small>
                    </div>
                    <div class="tar-metric-icon"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="tar-metric-value-row">
                    <strong class="tar-metric-number"><?= (int)($estadisticas['vigentes'] ?? 0) ?></strong>
                    <span class="tar-metric-status"><i class="fas fa-clock"></i> Hoy</span>
                </div>
                <div class="tar-metric-line"><span></span></div>
            </article>

            <!-- Programados -->
            <article class="tar-metric-card tar-metric-scheduled">
                <div class="tar-metric-head">
                    <div class="tar-metric-label">
                        <span>Programados</span>
                        <small>Listos para fechas futuras</small>
                    </div>
                    <div class="tar-metric-icon"><i class="fas fa-calendar-alt"></i></div>
                </div>
                <div class="tar-metric-value-row">
                    <strong class="tar-metric-number"><?= (int)($estadisticas['futuros'] ?? 0) ?></strong>
                    <span class="tar-metric-status"><i class="fas fa-calendar-day"></i> Futuro</span>
                </div>
                <div class="tar-metric-line"><span></span></div>
            </article>

            <!-- Distribucion -->
            <article class="tar-metric-card tar-metric-types">
                <div class="tar-metric-head">
                    <div class="tar-metric-label">
                        <span>Distribuci&oacute;n</span>
                        <small>Porcentaje y monto fijo</small>
                    </div>
                    <div class="tar-metric-icon"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="tar-metric-value-row">
                    <strong class="tar-metric-number"><?= $total_tipos_incremento ?></strong>
                    <span class="tar-metric-status"><i class="fas fa-layer-group"></i> Tipos</span>
                </div>
                <div class="tar-type-breakdown">
                    <div class="tar-type-row">
                        <span><i class="tar-type-dot" style="--dot-color:#7C4F86"></i>Porcentaje</span>
                        <strong><?= (int)($estadisticas['porcentaje'] ?? 0) ?></strong>
                    </div>
                    <div class="tar-type-row">
                        <span><i class="tar-type-dot" style="--dot-color:#2F7D72"></i>Monto fijo</span>
                        <strong><?= (int)($estadisticas['monto_fijo'] ?? 0) ?></strong>
                    </div>
                </div>
            </article>

            <!-- Vigentes -->
            <div class="tar-stat tar-stat-current" style="--ws:var(--tar-success)">
                <div class="flex items-center justify-between mb-3">
                    <div class="tar-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <span class="text-xl font-bold text-emerald-600"><?= $estadisticas['vigentes'] ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Vigentes</p>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Aplicándose hoy</p>
            </div>

            <!-- Programados -->
            <div class="tar-stat tar-stat-scheduled" style="--ws:var(--tar-sky)">
                <div class="flex items-center justify-between mb-3">
                    <div class="tar-stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <span class="text-xl font-bold text-blue-600"><?= $estadisticas['futuros'] ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Programados</p>
                <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Para fechas futuras</p>
            </div>

            <!-- Por tipo -->
            <div class="tar-stat tar-stat-types" style="--ws:var(--tar-amber)">
                <div class="flex items-center justify-between mb-2">
                    <div class="tar-stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <span class="text-xl font-bold text-gray-700"><?= (int)($estadisticas['porcentaje'] ?? 0) + (int)($estadisticas['monto_fijo'] ?? 0) ?></span>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Por Tipo</p>
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 flex items-center gap-1">
                            <i class="fas fa-circle text-[#8b5cf6]" style="font-size:.45rem"></i>
                            <span class="hidden xs:inline">Porcentaje</span><span class="xs:hidden">%</span>
                        </span>
                        <span class="font-bold text-gray-700"><?= $estadisticas['porcentaje'] ?? 0 ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 flex items-center gap-1">
                            <i class="fas fa-circle text-[#10b981]" style="font-size:.45rem"></i>
                            <span class="hidden xs:inline">Monto Fijo</span><span class="xs:hidden">$</span>
                        </span>
                        <span class="font-bold text-gray-700"><?= $estadisticas['monto_fijo'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="tar-filters">
            <div class="tar-filters-hd tarifa-section-header">
                <div class="tarifa-section-title">
                    <div class="tarifa-section-icon">
                        <i class="fas fa-filter text-xs"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-[#3D5234]">Filtros de tarifas</span>
                        <small>Depura el tablero por estado, tipo y alcance.</small>
                    </div>
                </div>
                <button onclick="resetFiltros()" class="btn-reset">
                    <i class="fas fa-sync-alt mr-1 text-xs"></i>Limpiar
                </button>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <p class="filter-label">Estado</p>
                    <select id="filtroEstado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="vigente">Vigentes Hoy</option>
                        <option value="futuro">Futuros</option>
                        <option value="pasado">Finalizados</option>
                    </select>
                </div>
                <div>
                    <p class="filter-label">Tipo</p>
                    <select id="filtroTipo" class="filter-select">
                        <option value="">Todos los tipos</option>
                        <option value="permanente">Permanentes</option>
                        <option value="temporal">Temporales</option>
                    </select>
                </div>
                <div>
                    <p class="filter-label">Alcance</p>
                    <select id="filtroAlcance" class="filter-select">
                        <option value="">Todos los alcances</option>
                        <option value="global">Global</option>
                        <option value="tipo_habitacion">Por Tipo de Habitación</option>
                        <option value="habitacion">Por Habitación</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Panel -->
        <div class="tar-panel">
            <div class="tar-panel-hd">
                <div class="tarifa-section-title">
                    <div class="tarifa-section-icon">
                        <i class="fas fa-layer-group text-xs"></i>
                    </div>
                    <div>
                        <span class="tar-panel-title">Incrementos configurados</span>
                        <small class="tar-panel-subtitle">Vista operativa de reglas activas, programadas y finalizadas.</small>
                    </div>
                </div>
                <div class="tar-panel-count"><?= number_format(count($incrementos ?? [])) ?> registros</div>
            </div>
            <div class="tar-panel-body">
                <?php if (empty($incrementos)): ?>
                    <div class="empty-state">
                        <div style="width:64px;height:64px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                            <i class="fas fa-tags text-2xl" style="color:#A8C4A0"></i>
                        </div>
                        <h3 class="text-sm font-bold text-gray-600 mb-2">No hay incrementos configurados</h3>
                        <p class="text-xs text-gray-400 mb-5 max-w-xs mx-auto">
                            Crea tu primer incremento para gestionar precios dinámicamente.
                        </p>
                        <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new">
                            <i class="fas fa-plus text-xs"></i> Crear Primer Incremento
                        </a>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto lc-scroll">
                        <table class="min-w-full" id="tablaTarifas">
                            <thead>
                                <tr>
                                    <th class="text-left">
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-sort-numeric-up"></i>
                                            <span class="hidden xs:inline">Prior.</span>
                                        </span>
                                    </th>
                                    <th class="text-left">Incremento</th>
                                    <th class="text-left hidden sm:table-cell">Valor</th>
                                    <th class="text-left hidden md:table-cell">Alcance</th>
                                    <th class="text-left">Vigencia</th>
                                    <th class="text-center">
                                        <span class="hidden sm:inline">Estado</span>
                                        <i class="fas fa-toggle-on sm:hidden"></i>
                                    </th>
                                    <th class="text-left hidden lg:table-cell">Creado</th>
                                    <th class="text-center">
                                        <span class="hidden sm:inline">Acciones</span>
                                        <i class="fas fa-ellipsis-h sm:hidden"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($incrementos as $inc): ?>
                                <?php
                                    $hoy = date('Y-m-d');
                                    if (!$inc['es_permanente']) {
                                        if ($inc['fecha_inicio'] > $hoy) $estado_fecha = 'futuro';
                                        elseif ($inc['fecha_fin'] && $inc['fecha_fin'] < $hoy) $estado_fecha = 'pasado';
                                        else $estado_fecha = 'vigente';
                                    } else {
                                        $estado_fecha = $inc['fecha_inicio'] <= $hoy ? 'vigente' : 'futuro';
                                    }
                                ?>
                                <tr data-id="<?= $inc['id'] ?>"
                                    data-estado="<?= $inc['activo'] ? 'activo' : 'inactivo' ?>"
                                    data-tipo="<?= $inc['es_permanente'] ? 'permanente' : 'temporal' ?>"
                                    data-alcance="<?= $inc['alcance'] ?>"
                                    data-vigencia="<?= $estado_fecha ?>"
                                    class="<?= !$inc['activo'] ? 'opacity-60' : '' ?>">

                                    <!-- Prioridad -->
                                    <td>
                                        <span class="prio-badge"><?= $inc['prioridad'] ?></span>
                                    </td>

                                    <!-- Nombre -->
                                    <td>
                                        <p class="text-xs font-semibold text-gray-800 truncate max-w-[160px]">
                                            <?= htmlspecialchars($inc['nombre']) ?>
                                        </p>
                                        <?php if ($inc['descripcion']): ?>
                                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">
                                                <?= htmlspecialchars($inc['descripcion']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <!-- Valor en móvil -->
                                        <div class="sm:hidden mt-1">
                                            <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                                <span class="val-badge"><i class="fas fa-percentage text-xs"></i>+<?= number_format($inc['valor_incremento'],2) ?>%</span>
                                            <?php else: ?>
                                                <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+<?= format_currency($inc['valor_incremento']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Valor desktop -->
                                    <td class="hidden sm:table-cell">
                                        <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                            <span class="val-badge"><i class="fas fa-percentage text-xs"></i>+<?= number_format($inc['valor_incremento'],2) ?>%</span>
                                        <?php else: ?>
                                            <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+<?= format_currency($inc['valor_incremento']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Alcance -->
                                    <td class="hidden md:table-cell">
                                        <?php switch($inc['alcance']):
                                            case 'global': ?>
                                                <span class="scope-badge scope-global"><i class="fas fa-globe text-xs"></i>Global</span>
                                                <?php break; case 'tipo_habitacion':
                                                $tipos = json_decode($inc['tipos_habitacion'],true) ?: []; ?>
                                                <span class="scope-badge scope-tipo" title="<?= implode(', ',array_map('get_tipo_habitacion',$tipos)) ?>">
                                                    <i class="fas fa-bed text-xs"></i><?= count($tipos) ?> tipos
                                                </span>
                                                <?php break; case 'habitacion':
                                                $habs = json_decode($inc['habitaciones'],true) ?: []; ?>
                                                <span class="scope-badge scope-hab">
                                                    <i class="fas fa-door-open text-xs"></i><?= count($habs) ?> hab.
                                                </span>
                                                <?php break; endswitch; ?>
                                    </td>

                                    <!-- Vigencia -->
                                    <td>
                                        <?php if ($inc['es_permanente']): ?>
                                            <span class="vig-badge vig-perm"><i class="fas fa-infinity text-xs"></i><span class="hidden xs:inline">Permanente</span></span>
                                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">Desde <?= format_date($inc['fecha_inicio'],'d/m/Y') ?></p>
                                        <?php else: ?>
                                            <?php if ($estado_fecha=='vigente'): ?>
                                                <span class="vig-badge vig-vigente"><i class="fas fa-check-circle text-xs"></i><span class="hidden xs:inline">Vigente</span></span>
                                            <?php elseif ($estado_fecha=='futuro'): ?>
                                                <span class="vig-badge vig-futuro"><i class="fas fa-clock text-xs"></i><span class="hidden xs:inline">Programado</span></span>
                                            <?php else: ?>
                                                <span class="vig-badge vig-pasado"><i class="fas fa-times-circle text-xs"></i><span class="hidden xs:inline">Finalizado</span></span>
                                            <?php endif; ?>
                                            <p class="text-xs text-gray-400 mt-0.5">
                                                <?= format_date($inc['fecha_inicio'],'d/m') ?> – <?= format_date($inc['fecha_fin'],'d/m/Y') ?>
                                            </p>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Toggle -->
                                    <td class="text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="sr-only peer toggle-activo"
                                                   data-id="<?= $inc['id'] ?>"
                                                   <?= $inc['activo'] ? 'checked' : '' ?>>
                                            <div class="w-9 h-5 bg-gray-200 rounded-full peer
                                                        peer-checked:after:translate-x-full peer-checked:after:border-white
                                                        after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                        after:bg-white after:border-gray-300 after:border after:rounded-full
                                                        after:h-4 after:w-4 after:transition-all
                                                        peer-checked:bg-green-600"></div>
                                            <span class="ml-2 text-xs font-medium text-gray-500 peer-checked:text-[#3D5234] hidden sm:inline">
                                                <?= $inc['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </label>
                                    </td>

                                    <!-- Creado -->
                                    <td class="hidden lg:table-cell">
                                        <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($inc['usuario_nombre']) ?></p>
                                        <p class="text-xs text-gray-400"><?= format_date($inc['created_at'],'d/m/Y H:i') ?></p>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <a href="<?= url('configuracion/tarifas/editar/'.$inc['id']) ?>"
                                               class="icn-btn icn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="icn-btn icn-view btn-detalle"
                                                    data-id="<?= $inc['id'] ?>" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="icn-btn icn-del btn-eliminar"
                                                    data-id="<?= $inc['id'] ?>" title="Eliminar"
                                                    <?= ($inc['activo'] && $estado_fecha=='vigente') ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
</div><!-- end page -->

<!-- ══════ Modal Calculadora de Precios ══════ -->
<div class="modal fade" id="modalPrecios" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title flex items-center gap-2">
                    <i class="fas fa-calculator opacity-80"></i>
                    Calculadora de Precios con Incrementos
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-5">
                    <label class="filter-label block mb-1.5">Seleccionar fecha para calcular precios</label>
                    <input type="date" class="date-input" id="fechaPreview"
                           value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                </div>
                <div id="resultadoPrecios">
                    <div class="empty-state py-12">
                        <div style="width:56px;height:56px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <i class="fas fa-calendar-check text-xl" style="color:#A8C4A0"></i>
                        </div>
                        <p class="text-sm text-gray-400">Seleccione una fecha para ver los precios aplicables</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════ Modal Detalle Incremento ══════ -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title flex items-center gap-2">
                    <i class="fas fa-info-circle opacity-80"></i>
                    Detalle del Incremento
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="contenidoDetalle">
                <!-- Llenado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
window.tipoHabitacionLabels = <?= json_encode($tarifaRoomTypeLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> || {};
window.gettipoHabitacion = t => window.tipoHabitacionLabels[t] || String(t || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
</script>

<script>
$(document).ready(function() {
    var tabla = $('#tablaTarifas').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        order: [[0,'desc'],[4,'desc']],
        pageLength: 10,
        lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
        columnDefs: [{ orderable:false, targets:[7] }],
        dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"<"flex items-center"f>>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"<"text-xs text-gray-400"i><"flex items-center"p>>',
        initComplete: function() {
            $('.dataTables_length').hide();
        }
    });

    $('[data-toggle="tooltip"]').tooltip();

    // Filtros
    $('#filtroEstado, #filtroTipo, #filtroAlcance').change(function() {
        aplicarFiltros();
    });

    function aplicarFiltros() {
        var estado   = $('#filtroEstado').val();
        var tipo     = $('#filtroTipo').val();
        var alcance  = $('#filtroAlcance').val();

        $('#tablaTarifas tbody tr').each(function() {
            var $r = $(this); var mostrar = true;
            if (estado) {
                if (estado === 'vigente') mostrar = $r.data('vigencia')==='vigente' && $r.data('estado')==='activo';
                else if (estado === 'activo') mostrar = $r.data('estado')==='activo';
                else mostrar = $r.data('vigencia')===estado;
            }
            if (tipo   && mostrar) mostrar = $r.data('tipo')===tipo;
            if (alcance && mostrar) mostrar = $r.data('alcance')===alcance;
            $r.toggle(mostrar);
        });
        tabla.draw();
    }

    // Toggle activo
    $('.toggle-activo').change(function() {
        const $sw = $(this), id = $sw.data('id'), activo = $sw.prop('checked');
        $.post('<?= url("configuracion/tarifas/toggle") ?>', { id:id, csrf_token:'<?= csrf_token() ?>' })
        .done(function(response) {
            if (response.success) {
                toastr.success(response.message);
                $sw.siblings('span').text(activo ? 'Activo' : 'Inactivo');
                const $row = $sw.closest('tr');
                activo ? $row.removeClass('opacity-60') : $row.addClass('opacity-60');
                $row.data('estado', activo ? 'activo' : 'inactivo');
                if ($row.data('vigencia')==='vigente') $row.find('.btn-eliminar').prop('disabled', activo);
            } else {
                toastr.error('Error al cambiar estado');
                $sw.prop('checked', !activo);
            }
        })
        .fail(function() { toastr.error('Error de conexión'); $sw.prop('checked', !activo); });
    });

    // Ver detalle
    $('.btn-detalle').click(function() {
        const id = $(this).data('id');
        $.get('<?= url("configuracion/tarifas/detalle") ?>', { id:id })
        .done(function(response) { if (response.success) mostrarDetalle(response.incremento); });
    });

    function mostrarDetalle(inc) {
        let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        // Col izquierda
        html += '<div>';
        html += '<p class="text-xs font-bold text-[#5C7A4E] uppercase tracking-wider mb-3 flex items-center gap-1.5"><i class="fas fa-info-circle"></i>Información General</p>';

        html += `<div class="detail-field"><p class="detail-field-label">Nombre</p><p class="detail-field-val">${inc.nombre}</p></div>`;
        html += `<div class="detail-field"><p class="detail-field-label">Descripción</p><p class="detail-field-val">${inc.descripcion||'Sin descripción'}</p></div>`;

        html += '<div class="detail-field"><p class="detail-field-label">Tipo de Incremento</p><p class="detail-field-val">';
        if (inc.tipo_incremento==='porcentaje')
            html += `<span class="val-badge"><i class="fas fa-percentage text-xs"></i>+${inc.valor_incremento}%</span>`;
        else
            html += `<span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i>+${formatCurrency(inc.valor_incremento)}</span>`;
        html += '</p></div>';

        html += `<div class="detail-field"><p class="detail-field-label">Prioridad</p><p class="detail-field-val"><span class="prio-badge">${inc.prioridad}</span></p></div>`;
        html += '</div>';

        // Col derecha
        html += '<div>';
        html += '<p class="text-xs font-bold text-[#5C7A4E] uppercase tracking-wider mb-3 flex items-center gap-1.5"><i class="fas fa-sliders-h"></i>Configuración de Aplicación</p>';

        html += '<div class="detail-field"><p class="detail-field-label">Alcance</p><p class="detail-field-val">';
        switch(inc.alcance) {
            case 'global':
                html += '<span class="scope-badge scope-global"><i class="fas fa-globe text-xs"></i>Global</span>';
                html += '<p class="text-xs text-gray-400 mt-1">Aplica a todas las habitaciones</p>'; break;
            case 'tipo_habitacion':
                html += '<span class="scope-badge scope-tipo"><i class="fas fa-bed text-xs"></i>Por Tipo</span>';
                html += '<div class="flex flex-wrap gap-1 mt-1.5">';
                inc.tipos_habitacion_array.forEach(t => { html += `<span class="vig-badge vig-perm text-xs">${gettipoHabitacion(t)}</span>`; });
                html += '</div>'; break;
            case 'habitacion':
                html += '<span class="scope-badge scope-hab"><i class="fas fa-door-open text-xs"></i>Habitaciones Específicas</span>';
                html += '<div class="flex flex-wrap gap-1 mt-1.5">';
                (inc.numeros_habitaciones||inc.habitaciones_array).forEach(h => { html += `<span class="vig-badge vig-perm text-xs">Hab. ${h}</span>`; });
                html += '</div>'; break;
        }
        html += '</p></div>';

        html += '<div class="detail-field"><p class="detail-field-label">Vigencia</p><p class="detail-field-val">';
        if (inc.es_permanente) {
            html += `<span class="vig-badge vig-perm"><i class="fas fa-infinity text-xs"></i>Permanente</span>`;
            html += `<p class="text-xs text-gray-400 mt-1">Desde: ${formatDate(inc.fecha_inicio)}</p>`;
        } else {
            html += `<span class="vig-badge vig-futuro"><i class="fas fa-calendar-alt text-xs"></i>Temporal</span>`;
            html += `<p class="text-xs text-gray-400 mt-1">${formatDate(inc.fecha_inicio)} – ${formatDate(inc.fecha_fin)}</p>`;
        }
        html += '</p></div>';

        html += '<div class="detail-field"><p class="detail-field-label">Estado</p><p class="detail-field-val">';
        html += inc.activo
            ? '<span class="vig-badge vig-vigente"><i class="fas fa-check-circle text-xs"></i>Activo</span>'
            : '<span class="vig-badge vig-pasado"><i class="fas fa-times-circle text-xs"></i>Inactivo</span>';
        html += '</p></div>';
        html += '</div></div>';

        html += `<div class="mt-4 pt-4 border-t border-[#EAF0E5]">
            <p class="text-xs text-gray-400 flex items-center gap-1.5">
                <i class="fas fa-user-clock" style="color:#A8C4A0"></i>
                Creado por <span class="font-semibold text-gray-600 ml-1">${inc.usuario_nombre}</span>
                &nbsp;·&nbsp; ${formatDate(inc.created_at, true)}
            </p></div>`;

        $('#contenidoDetalle').html(html);
        $('#modalDetalle').modal('show');
    }

    // Eliminar
    $('.btn-eliminar').click(function() {
        const $btn = $(this), id = $btn.data('id');
        if ($btn.prop('disabled')) return;
        Swal.fire({
            title: '¿Está seguro?', text: 'Esta acción no se puede deshacer',
            icon: 'warning', showCancelButton: true,
            confirmButtonColor: '#DC2626', cancelButtonColor: '#5C7A4E',
            confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
            reverseButtons: true,
            customClass: { popup:'rounded-xl', confirmButton:'rounded-lg', cancelButton:'rounded-lg' }
        }).then(result => {
            if (result.isConfirmed) {
                $.post('<?= url("configuracion/tarifas/eliminar") ?>', { id:id, csrf_token:'<?= csrf_token() ?>' })
                .done(function(r) {
                    if (r.success) {
                        toastr.success(r.message);
                        $btn.closest('tr').fadeOut(() => tabla.row($btn.closest('tr')).remove().draw());
                    } else toastr.error(r.message||'Error al eliminar');
                })
                .fail(() => toastr.error('Error de conexión'));
            }
        });
    });

    // Previsualizar precios
    window.previsualizarPrecios = function() {
        $('#modalPrecios').modal('show');
        cargarPreciosPreview();
    };

    $('#fechaPreview').change(cargarPreciosPreview);

    function cargarPreciosPreview() {
        const fecha = $('#fechaPreview').val();
        $('#resultadoPrecios').html(
            '<div class="text-center py-10">' +
            '<div style="width:52px;height:52px;border-radius:50%;background:rgba(92,122,78,.1);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">' +
            '<i class="fas fa-spinner fa-spin text-xl" style="color:#5C7A4E"></i></div>' +
            '<p class="text-xs text-gray-400">Calculando precios...</p></div>'
        );
        $.post('<?= url("configuracion/tarifas/previsualizar") ?>', { fecha:fecha, csrf_token:'<?= csrf_token() ?>' })
        .done(function(r) { if (r.success) mostrarTablaPrecios(r); })
        .fail(() => {
            $('#resultadoPrecios').html(
                '<div class="p-3 rounded-xl" style="background:#FEF2F2;border:1px solid #FECACA;">' +
                '<p class="text-xs text-red-700"><i class="fas fa-exclamation-triangle mr-1.5"></i>Error al cargar los precios</p></div>'
            );
        });
    }

    function mostrarTablaPrecios(data) {
        let html = `<div class="flex items-center justify-between mb-4">
            <p class="text-xs font-bold text-[#3D5234]">Precios para el ${data.fecha_formateada}</p>
            <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1"><i class="fas fa-circle text-xs" style="color:#C8A96A"></i>Con incremento</span>
                <span class="flex items-center gap-1"><i class="fas fa-circle text-xs text-gray-200"></i>Sin incremento</span>
            </div></div>`;

        const porTipo = {};
        data.precios.forEach(p => { if (!porTipo[p.tipo]) porTipo[p.tipo]=[]; porTipo[p.tipo].push(p); });

        html += '<div class="space-y-3">';
        Object.keys(porTipo).forEach((tipo, idx) => {
            const habs = porTipo[tipo];
            const tieneInc = habs.some(h => h.incremento > 0);
            html += `<div class="tar-panel overflow-hidden">
                <button class="w-full px-4 py-3 text-left flex items-center justify-between hover:bg-[#FAFDF8] transition-colors"
                        onclick="$('#precio-tipo-${idx}').toggleClass('hidden')">
                    <span class="text-xs font-bold text-gray-700">${tipo}</span>
                    <div class="flex items-center gap-2">
                        ${tieneInc ? '<span class="vig-badge" style="background:rgba(200,169,106,.12);color:#B8994A;border:1px solid rgba(200,169,106,.25);"><i class="fas fa-tag text-xs"></i>Con incremento</span>' : ''}
                        <i class="fas fa-chevron-down text-gray-300 text-xs"></i>
                    </div>
                </button>
                <div id="precio-tipo-${idx}" class="${idx>0?'hidden':''}">
                    <div class="overflow-x-auto lc-scroll">
                    <table class="min-w-full">
                        <thead><tr style="border-bottom:2px solid #DDE8D5;">
                            <th class="px-4 py-2 text-left" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Habitación</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">P. Base</th>
                            <th class="px-4 py-2 text-left hidden sm:table-cell" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Incrementos</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">P. Final</th>
                            <th class="px-4 py-2 text-right" style="font-size:.65rem;font-weight:700;color:#7A9B6A;text-transform:uppercase;letter-spacing:.05em;">Dif.</th>
                        </tr></thead>
                        <tbody>`;
            habs.forEach(h => {
                const tiInc = h.incremento > 0;
                html += `<tr style="border-bottom:1px solid #F0F5ED;${tiInc?'background:#FAFDF8;':''}">
                    <td class="px-4 py-2 text-xs font-semibold text-gray-800">Hab. ${h.habitacion}</td>
                    <td class="px-4 py-2 text-xs text-gray-500 text-right">${formatCurrency(h.precio_base)}</td>
                    <td class="px-4 py-2 text-xs hidden sm:table-cell">`;
                if (h.incrementos.length) {
                    h.incrementos.forEach(i => {
                        html += `<div class="flex items-center gap-1 mb-0.5"><i class="fas fa-tag text-xs" style="color:#C8A96A"></i><span class="text-gray-600">${i.nombre}: <strong class="text-emerald-600">+${formatCurrency(i.aumento)}</strong>${i.tipo==='porcentaje'?` <span class="text-gray-400">(${i.valor}%)</span>`:''}</span></div>`;
                    });
                } else html += '<span class="text-gray-300 text-xs">Sin incrementos</span>';
                html += `</td>
                    <td class="px-4 py-2 text-xs font-bold text-right ${tiInc?'text-[#B8994A]':'text-gray-800'}">${formatCurrency(h.precio_final)}</td>
                    <td class="px-4 py-2 text-xs text-right">`;
                if (h.incremento>0) {
                    const pct = ((h.incremento/h.precio_base)*100).toFixed(1);
                    html += `<span class="text-red-500 font-semibold">+${formatCurrency(h.incremento)}</span><span class="text-gray-400 block text-xs">(+${pct}%)</span>`;
                } else html += '<span class="text-gray-300">—</span>';
                html += `</td></tr>`;
            });
            html += '</tbody></table></div></div></div>';
        });
        html += '</div>';
        $('#resultadoPrecios').html(html);
    }

    window.formatCurrency = val => '$'+parseFloat(val).toFixed(2).replace(/\d(?=(\d{3})+\.)/g,'$&,');
    window.formatDate = (str,time=false) => {
        if (!str) return '';
        const d = new Date(str);
        const o = {year:'numeric',month:'short',day:'numeric'};
        if (time) { o.hour='2-digit'; o.minute='2-digit'; }
        return d.toLocaleDateString('es-MX',o);
    };
    const tipoHabitacionLabels = <?= json_encode($tarifaRoomTypeLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?> || {};
    window.gettipoHabitacion = t => tipoHabitacionLabels[t] || String(t || '').replace(/[_-]+/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    window.resetFiltros = () => $('#filtroEstado,#filtroTipo,#filtroAlcance').val('').trigger('change');
});
</script>

<script>
toastr.options = {
    closeButton:true, progressBar:true,
    positionClass:'toast-top-right',
    timeOut:'3000', extendedTimeOut:'1000',
    showMethod:'fadeIn', hideMethod:'fadeOut'
};
</script>

<?php include __DIR__ . '/../../layout/footer.php'; ?>
