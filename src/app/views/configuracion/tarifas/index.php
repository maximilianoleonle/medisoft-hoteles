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
<link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/datatables/dataTables.bootstrap4.min.css') ?>">
<link rel="stylesheet" href="<?= asset('vendor/toastr/toastr.min.css') ?>">
<link href="<?= asset('vendor/tailwind/tailwind-2.2.19.min.css') ?>" rel="stylesheet">

<script src="<?= asset('vendor/jquery/jquery-3.6.0.min.js') ?>"></script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= asset('vendor/datatables/dataTables.bootstrap4.min.js') ?>"></script>
<script src="<?= asset('vendor/toastr/toastr.min.js') ?>"></script>

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

/* ── Badge de origen Copiloto IA ─────────── */
.copiloto-badge {
    display:inline-flex; align-items:center; gap:3px;
    padding:2px 8px; border-radius:999px; font-size:.66rem; font-weight:700;
    background:linear-gradient(135deg,rgba(189,148,65,.14),rgba(189,148,65,.06));
    color:#7A5C1E; border:1px solid rgba(189,148,65,.32);
    white-space:nowrap; vertical-align:1px;
}

.copiloto-resultado {
    margin:3px 0 0; font-size:.7rem; color:#6B7280; line-height:1.45;
}
.copiloto-resultado strong { color:#374151; font-weight:700; }
.copiloto-resultado .rc-up { color:#059669; font-weight:700; }
.copiloto-resultado .rc-down { color:#B4392B; font-weight:700; }

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
.icn-del.is-confirming { background:#FEF2F2; box-shadow: inset 0 0 0 1px rgba(220,38,38,.2); }
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
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tar-page {
    --tar-brand: var(--brand-primary, #1B2746);
    --tar-brand-2: var(--brand-secondary, #0F172A);
    --tar-accent: var(--brand-accent, #BD9441);
    --tar-accent-dark: color-mix(in srgb, var(--tar-accent) 72%, #3F2E12);
    --tar-accent-soft: color-mix(in srgb, var(--tar-accent) 14%, #FFFFFF);
    --tar-accent-line: color-mix(in srgb, var(--tar-accent) 34%, #E8DDCA);
    --tar-bg: #F5F5F7;
    --tar-bg-2: #FAFAFC;
    --tar-surface: rgba(255,255,255,.96);
    --tar-surface-warm: #F5F5F7;
    --tar-border: color-mix(in srgb, var(--tar-brand) 11%, #E7E1D4);
    --tar-text: #1B2746;
    --tar-muted: #6C7689;
    --tar-success: #1E9E63;
    --tar-success-soft: #E8F4ED;
    --tar-danger: #B42318;
    --tar-danger-soft: #FDECEC;
    --tar-info: #2F77E0;
    --tar-info-soft: #E8F0FC;
    --tar-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
.icn-del.is-confirming {
    background: var(--tar-danger-soft) !important;
    border-color: color-mix(in srgb, var(--tar-danger) 30%, #FFFFFF) !important;
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--tar-danger) 16%, transparent) !important;
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

/* Calculadora de precios: consulta guiada, sin modificar tarifas. */
#modalPrecios.tar-price-modal {
    --tp-brand: var(--brand-primary, #1B2746);
    --tp-brand-2: var(--brand-secondary, #0F172A);
    --tp-accent: var(--brand-accent, #BD9441);
    --tp-text: #172033;
    --tp-muted: #667085;
    --tp-bg: #F5F5F7;
    --tp-surface: #FFFFFF;
    --tp-line: color-mix(in srgb, var(--tp-brand) 11%, #E8E2D8);
    --tp-success: #148653;
    --tp-success-soft: #EAF7F0;
    --tp-warning: #A96E12;
    --tp-warning-soft: #FFF6E6;
    --tp-danger: #B42318;
    z-index: 13000;
    color: var(--tp-text);
    font-family: var(--tar-sans, 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif);
}

#modalPrecios .tar-price-modal__dialog {
    width: min(1120px, calc(100vw - 32px)) !important;
    max-width: 1120px !important;
    margin: max(16px, 4dvh) auto;
}

#modalPrecios .tar-price-modal__content {
    max-height: min(92dvh, 860px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid color-mix(in srgb, var(--tp-brand) 13%, #FFFFFF) !important;
    border-radius: 26px !important;
    background: var(--tp-surface);
    box-shadow: 0 38px 90px -34px rgba(12, 18, 31, .58) !important;
}

#modalPrecios .tar-price-modal__header {
    flex: 0 0 auto;
    min-height: 94px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 20px 22px !important;
    border-bottom: 1px solid var(--tp-line) !important;
    background:
        radial-gradient(circle at 92% 8%, color-mix(in srgb, var(--tp-accent) 15%, transparent), transparent 14rem),
        linear-gradient(145deg, #FFFFFF, color-mix(in srgb, var(--tp-brand) 4%, #F8F7F4)) !important;
}

#modalPrecios .tar-price-modal__title-lockup {
    min-width: 0;
    display: flex;
    align-items: center;
    gap: 14px;
}

#modalPrecios .tar-price-modal__icon {
    width: 50px;
    height: 50px;
    flex: 0 0 50px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    color: #FFFFFF;
    background: linear-gradient(145deg, var(--tp-brand), var(--tp-brand-2));
    box-shadow: 0 16px 28px -16px color-mix(in srgb, var(--tp-brand) 72%, transparent);
}

#modalPrecios .tar-price-modal__icon i {
    font-size: 1.05rem;
}

#modalPrecios .tar-price-modal__eyebrow {
    margin: 0 0 2px;
    color: color-mix(in srgb, var(--tp-accent) 72%, #59401B);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .1em;
    text-transform: uppercase;
}

#modalPrecios .tar-price-modal__title {
    margin: 0;
    color: var(--tp-brand) !important;
    font-size: clamp(1.05rem, 2vw, 1.28rem) !important;
    font-weight: 900 !important;
    line-height: 1.2;
}

#modalPrecios .tar-price-modal__subtitle {
    margin: 4px 0 0;
    color: var(--tp-muted);
    font-size: .79rem;
    line-height: 1.45;
}

#modalPrecios .tar-price-modal__close {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    display: grid;
    place-items: center;
    margin: 0;
    padding: 0;
    border: 1px solid var(--tp-line);
    border-radius: 13px;
    color: var(--tp-brand) !important;
    background: rgba(255, 255, 255, .82);
    opacity: 1 !important;
    text-shadow: none !important;
    transition: transform .16s ease, background-color .16s ease, border-color .16s ease;
}

#modalPrecios .tar-price-modal__close:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--tp-brand) 24%, var(--tp-line));
    background: #FFFFFF;
}

#modalPrecios .tar-price-modal__close:focus-visible,
#modalPrecios .tar-price-date:focus-visible,
#modalPrecios .tar-price-group__toggle:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--tp-accent) 34%, transparent) !important;
    outline-offset: 2px;
}

#modalPrecios .tar-price-modal__body {
    flex: 1 1 auto;
    min-height: 0;
    display: grid;
    grid-template-columns: minmax(250px, .72fr) minmax(0, 2fr);
    padding: 0 !important;
    overflow: hidden;
    background: var(--tp-bg) !important;
}

#modalPrecios .tar-price-controls {
    min-width: 0;
    padding: 24px;
    border-right: 1px solid var(--tp-line);
    background: var(--tp-surface);
}

#modalPrecios .tar-price-step {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

#modalPrecios .tar-price-step__number {
    width: 32px;
    height: 32px;
    flex: 0 0 32px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    color: #FFFFFF;
    background: var(--tp-brand);
    font-size: .78rem;
    font-weight: 900;
}

#modalPrecios .tar-price-step__copy h6 {
    margin: 1px 0 3px;
    color: var(--tp-brand);
    font-size: .92rem;
    font-weight: 900;
}

#modalPrecios .tar-price-step__copy p {
    margin: 0;
    color: var(--tp-muted);
    font-size: .75rem;
    line-height: 1.45;
}

#modalPrecios .tar-price-field {
    margin-top: 22px;
}

#modalPrecios .tar-price-field label {
    display: block;
    margin: 0 0 7px;
    color: var(--tp-text);
    font-size: .78rem;
    font-weight: 900;
}

#modalPrecios .tar-price-date-wrap {
    position: relative;
}

#modalPrecios .tar-price-date-wrap > i {
    position: absolute;
    top: 50%;
    left: 15px;
    transform: translateY(-50%);
    color: var(--tp-accent);
    pointer-events: none;
}

#modalPrecios .tar-price-date {
    width: 100%;
    min-height: 50px;
    padding: 10px 12px 10px 42px;
    border: 1px solid var(--tp-line);
    border-radius: 13px;
    color: var(--tp-text);
    background: var(--tp-bg);
    font: inherit;
    font-size: .88rem;
    font-weight: 800;
    transition: border-color .16s ease, box-shadow .16s ease, background-color .16s ease;
}

#modalPrecios .tar-price-date:hover,
#modalPrecios .tar-price-date:focus {
    border-color: color-mix(in srgb, var(--tp-accent) 58%, var(--tp-line));
    background: #FFFFFF;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--tp-accent) 15%, transparent);
}

#modalPrecios .tar-price-field__help {
    display: block;
    margin-top: 7px;
    color: var(--tp-muted);
    font-size: .71rem;
    line-height: 1.45;
}

#modalPrecios .tar-price-readonly-note {
    margin-top: 20px;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 11px 12px;
    border: 1px solid color-mix(in srgb, var(--tp-success) 20%, #DDEDE4);
    border-radius: 12px;
    color: color-mix(in srgb, var(--tp-success) 70%, #173C2A);
    background: var(--tp-success-soft);
    font-size: .71rem;
    font-weight: 700;
    line-height: 1.45;
}

#modalPrecios .tar-price-readonly-note i {
    margin-top: 2px;
}

#modalPrecios .tar-price-results {
    min-width: 0;
    min-height: 0;
    padding: 22px;
    overflow-y: auto;
    overscroll-behavior: contain;
    scrollbar-gutter: stable;
}

#modalPrecios .tar-price-state {
    min-height: 360px;
    display: grid;
    place-items: center;
    padding: 32px 20px;
    text-align: center;
}

#modalPrecios .tar-price-state__inner {
    width: min(390px, 100%);
}

#modalPrecios .tar-price-state__icon {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    margin: 0 auto 14px;
    border: 1px solid color-mix(in srgb, var(--tp-brand) 12%, #E5E7EB);
    border-radius: 17px;
    color: var(--tp-brand);
    background: color-mix(in srgb, var(--tp-brand) 7%, #FFFFFF);
    font-size: 1.15rem;
}

#modalPrecios .tar-price-state.is-error .tar-price-state__icon {
    border-color: color-mix(in srgb, var(--tp-danger) 20%, #FEE2E2);
    color: var(--tp-danger);
    background: #FFF3F2;
}

#modalPrecios .tar-price-state h6 {
    margin: 0 0 5px;
    color: var(--tp-brand);
    font-size: .94rem;
    font-weight: 900;
}

#modalPrecios .tar-price-state p {
    margin: 0;
    color: var(--tp-muted);
    font-size: .78rem;
    line-height: 1.55;
}

#modalPrecios .tar-price-summary {
    margin-bottom: 16px;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 16px;
}

#modalPrecios .tar-price-summary__eyebrow {
    margin: 0 0 3px;
    color: var(--tp-muted);
    font-size: .67rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

#modalPrecios .tar-price-summary h6 {
    margin: 0;
    color: var(--tp-brand);
    font-size: 1.02rem;
    font-weight: 900;
}

#modalPrecios .tar-price-summary__hint {
    margin: 4px 0 0;
    color: var(--tp-muted);
    font-size: .73rem;
}

#modalPrecios .tar-price-metrics {
    display: flex;
    gap: 8px;
}

#modalPrecios .tar-price-metric {
    min-width: 92px;
    padding: 9px 11px;
    border: 1px solid var(--tp-line);
    border-radius: 12px;
    background: #FFFFFF;
}

#modalPrecios .tar-price-metric strong {
    display: block;
    color: var(--tp-brand);
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.1;
}

#modalPrecios .tar-price-metric span {
    display: block;
    margin-top: 3px;
    color: var(--tp-muted);
    font-size: .62rem;
    font-weight: 800;
}

#modalPrecios .tar-price-metric.is-adjusted {
    border-color: color-mix(in srgb, var(--tp-accent) 28%, var(--tp-line));
    background: var(--tp-warning-soft);
}

#modalPrecios .tar-price-metric.is-adjusted strong {
    color: var(--tp-warning);
}

#modalPrecios .tar-price-groups {
    display: grid;
    gap: 10px;
}

#modalPrecios .tar-price-group {
    overflow: hidden;
    border: 1px solid var(--tp-line);
    border-radius: 15px;
    background: var(--tp-surface);
    box-shadow: 0 10px 28px -26px rgba(23, 32, 51, .55);
}

#modalPrecios .tar-price-group__toggle {
    width: 100%;
    min-height: 58px;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto auto;
    align-items: center;
    gap: 11px;
    padding: 10px 14px;
    border: 0;
    color: inherit;
    background: #FFFFFF;
    text-align: left;
    cursor: pointer;
    transition: background-color .16s ease;
}

#modalPrecios .tar-price-group__toggle:hover {
    background: color-mix(in srgb, var(--tp-brand) 3%, #FFFFFF);
}

#modalPrecios .tar-price-group__icon {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    color: var(--tp-brand);
    background: color-mix(in srgb, var(--tp-brand) 8%, #FFFFFF);
}

#modalPrecios .tar-price-group__copy {
    min-width: 0;
}

#modalPrecios .tar-price-group__copy strong,
#modalPrecios .tar-price-group__copy span {
    display: block;
}

#modalPrecios .tar-price-group__copy strong {
    overflow: hidden;
    color: var(--tp-text);
    font-size: .8rem;
    font-weight: 900;
    text-overflow: ellipsis;
    white-space: nowrap;
}

#modalPrecios .tar-price-group__copy span {
    margin-top: 2px;
    color: var(--tp-muted);
    font-size: .67rem;
}

#modalPrecios .tar-price-group__status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 8px;
    border-radius: 999px;
    color: var(--tp-warning);
    background: var(--tp-warning-soft);
    font-size: .62rem;
    font-weight: 900;
    white-space: nowrap;
}

#modalPrecios .tar-price-group__chevron {
    color: var(--tp-muted);
    font-size: .7rem;
    transition: transform .18s ease;
}

#modalPrecios .tar-price-group__toggle[aria-expanded="true"] .tar-price-group__chevron {
    transform: rotate(180deg);
}

#modalPrecios .tar-price-group__body {
    border-top: 1px solid var(--tp-line);
}

#modalPrecios .tar-price-table-wrap {
    overflow-x: auto;
}

#modalPrecios .tar-price-table {
    width: 100%;
    min-width: 720px;
    border-collapse: collapse;
}

#modalPrecios .tar-price-table th {
    padding: 9px 12px;
    border-bottom: 1px solid var(--tp-line);
    color: var(--tp-muted);
    background: var(--tp-bg);
    font-size: .61rem;
    font-weight: 900;
    letter-spacing: .05em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

#modalPrecios .tar-price-table th.is-number,
#modalPrecios .tar-price-table td.is-number {
    text-align: right;
}

#modalPrecios .tar-price-table td {
    padding: 11px 12px;
    border-bottom: 1px solid color-mix(in srgb, var(--tp-line) 72%, #FFFFFF);
    color: var(--tp-text);
    font-size: .73rem;
    vertical-align: middle;
}

#modalPrecios .tar-price-table tbody tr:last-child td {
    border-bottom: 0;
}

#modalPrecios .tar-price-table tbody tr.is-adjusted {
    background: color-mix(in srgb, var(--tp-warning-soft) 48%, #FFFFFF);
}

#modalPrecios .tar-price-room {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-weight: 900;
    white-space: nowrap;
}

#modalPrecios .tar-price-room i {
    color: var(--tp-brand);
}

#modalPrecios .tar-price-final {
    color: var(--tp-brand);
    font-size: .8rem;
    font-weight: 900;
    white-space: nowrap;
}

#modalPrecios tr.is-adjusted .tar-price-final {
    color: var(--tp-warning);
}

#modalPrecios .tar-price-difference {
    color: var(--tp-warning);
    font-weight: 900;
    white-space: nowrap;
}

#modalPrecios .tar-price-difference small {
    display: block;
    margin-top: 2px;
    color: var(--tp-muted);
    font-size: .61rem;
    font-weight: 700;
}

#modalPrecios .tar-price-no-change {
    color: var(--tp-muted);
    font-weight: 700;
}

#modalPrecios .tar-price-rules {
    display: grid;
    gap: 4px;
}

#modalPrecios .tar-price-rule {
    display: flex;
    align-items: baseline;
    gap: 5px;
    color: var(--tp-muted);
    line-height: 1.35;
}

#modalPrecios .tar-price-rule i {
    color: var(--tp-accent);
    font-size: .62rem;
}

#modalPrecios .tar-price-rule strong {
    color: var(--tp-success);
}

@media (max-width: 840px) {
    #modalPrecios .tar-price-modal__dialog {
        width: min(760px, calc(100vw - 20px)) !important;
        margin: 10px auto;
    }

    #modalPrecios .tar-price-modal__content {
        max-height: calc(100dvh - 20px);
    }

    #modalPrecios .tar-price-modal__body {
        display: block;
        overflow-y: auto;
    }

    #modalPrecios .tar-price-controls {
        padding: 16px 18px;
        border-right: 0;
        border-bottom: 1px solid var(--tp-line);
    }

    #modalPrecios .tar-price-controls__layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(230px, .72fr);
        align-items: end;
        gap: 16px;
    }

    #modalPrecios .tar-price-field {
        margin-top: 0;
    }

    #modalPrecios .tar-price-readonly-note {
        grid-column: 1 / -1;
        margin-top: 0;
    }

    #modalPrecios .tar-price-results {
        padding: 18px;
        overflow: visible;
    }
}

@media (max-width: 640px) {
    #modalPrecios.tar-price-modal {
        padding: 8px 8px max(8px, env(safe-area-inset-bottom));
    }

    #modalPrecios .tar-price-modal__dialog {
        width: 100% !important;
        margin: 0 auto;
    }

    #modalPrecios .tar-price-modal__content {
        max-height: calc(100dvh - 16px - env(safe-area-inset-bottom));
        border-radius: 22px 22px 16px 16px !important;
    }

    #modalPrecios .tar-price-modal__header {
        min-height: 78px;
        padding: 14px 16px !important;
    }

    #modalPrecios .tar-price-modal__icon {
        width: 42px;
        height: 42px;
        flex-basis: 42px;
        border-radius: 13px;
    }

    #modalPrecios .tar-price-modal__subtitle {
        display: none;
    }

    #modalPrecios .tar-price-controls__layout {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    #modalPrecios .tar-price-readonly-note {
        grid-column: auto;
    }

    #modalPrecios .tar-price-results {
        padding: 14px;
    }

    #modalPrecios .tar-price-summary {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    #modalPrecios .tar-price-metrics {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    #modalPrecios .tar-price-metric {
        min-width: 0;
        padding: 8px;
    }

    #modalPrecios .tar-price-group__toggle {
        grid-template-columns: auto minmax(0, 1fr) auto;
        padding: 9px 11px;
    }

    #modalPrecios .tar-price-group__status {
        display: none;
    }

    #modalPrecios .tar-price-table-wrap {
        overflow: visible;
    }

    #modalPrecios .tar-price-table,
    #modalPrecios .tar-price-table tbody,
    #modalPrecios .tar-price-table tr,
    #modalPrecios .tar-price-table td {
        display: block;
        width: 100%;
        min-width: 0;
    }

    #modalPrecios .tar-price-table thead {
        display: none;
    }

    #modalPrecios .tar-price-table tr {
        padding: 12px;
        border-bottom: 1px solid var(--tp-line);
        background: #FFFFFF;
    }

    #modalPrecios .tar-price-table tr:last-child {
        border-bottom: 0;
    }

    #modalPrecios .tar-price-table tbody tr.is-adjusted {
        background: color-mix(in srgb, var(--tp-warning-soft) 52%, #FFFFFF);
    }

    #modalPrecios .tar-price-table td {
        min-height: 32px;
        display: grid;
        grid-template-columns: minmax(100px, .8fr) minmax(0, 1.2fr);
        align-items: baseline;
        gap: 10px;
        padding: 5px 0;
        border: 0;
        text-align: right !important;
    }

    #modalPrecios .tar-price-table td::before {
        content: attr(data-label);
        color: var(--tp-muted);
        font-size: .61rem;
        font-weight: 900;
        letter-spacing: .04em;
        text-align: left;
        text-transform: uppercase;
    }

    #modalPrecios .tar-price-table td.tar-price-room-cell {
        display: block;
        min-height: 0;
        margin-bottom: 5px;
        padding-bottom: 9px;
        border-bottom: 1px dashed var(--tp-line);
        text-align: left !important;
    }

    #modalPrecios .tar-price-table td.tar-price-room-cell::before {
        content: none;
    }

    #modalPrecios .tar-price-rules {
        justify-items: end;
    }

    #modalPrecios .tar-price-rule {
        justify-content: flex-end;
        text-align: right;
    }
}

@media (max-width: 380px) {
    #modalPrecios .tar-price-modal__eyebrow,
    #modalPrecios .tar-price-summary__hint {
        display: none;
    }

    #modalPrecios .tar-price-metrics {
        gap: 5px;
    }

    #modalPrecios .tar-price-metric span {
        font-size: .57rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    #modalPrecios,
    #modalPrecios * {
        scroll-behavior: auto !important;
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
    }
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
    --tar-ivory: #F5F5F7;
    --tar-ivory-2: #FAFAFC;
    --tar-heading: #111827;
    --tar-sky: #3E7CB1;
    --tar-teal: #2F7D72;
    --tar-plum: #7C4F86;
    --tar-coral: #C66A5A;
    --tar-amber: #D0963A;
    padding: 1rem;

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

/* Index sereno: la lista y las decisiones dominan sobre la decoración. */
.tarifa-index-page {
    --ti-line: color-mix(in srgb, var(--tar-brand) 11%, #E8E2D8);
    --ti-soft: color-mix(in srgb, var(--tar-brand) 4%, #F7F7F8);
    --ti-success: #148653;
    --ti-success-soft: #EAF7F0;
    --ti-info: #356F9E;
    --ti-info-soft: #EDF4F9;
    --ti-warning: #A96E12;
    --ti-warning-soft: #FFF6E6;
    padding: clamp(12px, 2vw, 24px) !important;
    background: var(--tar-bg) !important;
}

.tarifa-index-page .tar-topbar,
.tarifa-index-page .tar-topbar.bg-white {
    max-width: 1480px;
    margin: 0 auto 14px !important;
    overflow: hidden;
    border: 1px solid var(--ti-line) !important;
    border-radius: 24px !important;
    background:
        radial-gradient(circle at 96% 0%, color-mix(in srgb, var(--tar-accent) 11%, transparent), transparent 18rem),
        linear-gradient(145deg, #FFFFFF, var(--ti-soft)) !important;
    box-shadow: 0 20px 48px -38px rgba(17, 24, 39, .42);
}

.tarifa-index-page .tar-topbar > div {
    padding: 18px 20px !important;
}

.tarifa-index-page > .px-3 {
    max-width: 1480px !important;
    padding: 0 !important;
    margin: 0 auto !important;
}

.tarifa-index-hero-inner {
    align-items: center;
    gap: 20px;
}

.tarifa-index-title-lockup {
    grid-template-columns: 46px minmax(0, 1fr);
    column-gap: 13px;
}

.tarifa-index-hero-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(145deg, var(--tar-brand), var(--tar-brand-2)) !important;
}

.tarifa-index-page .tarifa-page-kicker {
    margin-bottom: 4px;
    color: color-mix(in srgb, var(--tar-accent) 72%, #59401B);
    font-size: .68rem;
}

.tarifa-index-page .tarifa-page-title {
    font-size: clamp(1.55rem, 2.7vw, 2.15rem) !important;
    font-weight: 900 !important;
    line-height: 1.08 !important;
}

.tarifa-index-page .tarifa-page-subtitle,
.tarifa-index-page .tar-topbar p.text-gray-400 {
    max-width: 760px;
    margin-top: 5px !important;
    font-size: .82rem !important;
    font-weight: 600;
    line-height: 1.45;
}

.tarifa-index-actions {
    min-width: 0;
    gap: 8px;
}

.tarifa-index-page .btn-tar {
    min-height: 44px;
    padding: 10px 14px !important;
    border: 1px solid transparent !important;
    border-radius: 12px !important;
    box-shadow: none !important;
}

.tarifa-index-page .btn-tar.calc {
    border-color: color-mix(in srgb, var(--tar-brand) 18%, var(--ti-line)) !important;
    background: #FFFFFF !important;
    color: var(--tar-brand) !important;
}

.tarifa-index-page .btn-tar.calc:hover {
    border-color: color-mix(in srgb, var(--tar-brand) 34%, var(--ti-line)) !important;
    background: var(--ti-soft) !important;
    color: var(--tar-brand) !important;
}

.tarifa-index-page .btn-tar.new {
    background: linear-gradient(145deg, var(--tar-brand), var(--tar-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 14px 26px -16px color-mix(in srgb, var(--tar-brand) 72%, transparent) !important;
}

.tarifa-index-summary.tarifa-metrics-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
    gap: 0 !important;
    margin-bottom: 14px !important;
    overflow: hidden;
    border: 1px solid var(--ti-line) !important;
    border-radius: 20px !important;
    background: #FFFFFF !important;
    box-shadow: 0 16px 40px -36px rgba(17, 24, 39, .44) !important;
}

.tar-metric-card {
    --metric: var(--tar-brand);
    grid-column: auto !important;
    min-height: 112px;
    gap: 7px;
    padding: 15px 16px;
    border: 0;
    border-right: 1px solid var(--ti-line);
    border-radius: 0;
    background: #FFFFFF;
    box-shadow: none;
}

.tar-metric-card:last-child {
    border-right: 0;
}

.tar-metric-card::before {
    inset: 0 0 auto;
    width: auto;
    height: 3px;
    background: color-mix(in srgb, var(--metric) 78%, var(--tar-brand));
}

.tar-metric-card::after,
.tar-metric-line {
    display: none;
}

.tar-metric-card:hover {
    z-index: 2;
    transform: none;
    border-color: var(--ti-line);
    background: color-mix(in srgb, var(--metric) 3%, #FFFFFF);
    box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--metric) 15%, transparent);
}

.tar-metric-icon {
    width: 32px;
    height: 32px;
    flex-basis: 32px;
    border-radius: 10px;
}

.tar-metric-label span {
    font-size: .64rem;
}

.tar-metric-label small {
    margin-top: 3px;
    font-size: .69rem;
}

.tar-metric-number {
    font-size: 2rem;
    line-height: .9;
}

.tar-metric-status {
    min-height: 24px;
    padding: 4px 7px;
    font-size: .65rem;
}

.tar-type-breakdown {
    gap: 5px;
}

.tar-type-row {
    min-height: 28px;
    padding: 4px 6px;
    border-radius: 9px;
}

.tarifa-workspace {
    display: grid;
    grid-template-columns: minmax(250px, 292px) minmax(0, 1fr);
    align-items: start;
    gap: 14px;
}

.tarifa-index-page .tar-filters,
.tarifa-index-page .tar-panel {
    margin: 0;
    border: 1px solid var(--ti-line) !important;
    border-radius: 20px !important;
    background: #FFFFFF !important;
    box-shadow: 0 16px 40px -36px rgba(17, 24, 39, .44);
}

.tarifa-index-page .tar-filters {
    position: sticky;
    top: 16px;
}

.tarifa-index-page .tar-filters::before,
.tarifa-index-page .tar-panel::before {
    content: none;
}

.tarifa-section-header,
.tar-panel-hd {
    min-height: 74px;
    padding: 14px 16px !important;
    background: linear-gradient(145deg, #FFFFFF, var(--ti-soft)) !important;
}

.tarifa-section-title {
    align-items: flex-start;
    gap: 10px;
}

.tarifa-section-icon {
    width: 34px;
    height: 34px;
    flex-basis: 34px;
    border-radius: 10px;
    color: #FFFFFF;
    background: var(--tar-brand);
    border: 0;
}

.tarifa-section-copy {
    min-width: 0;
}

.tarifa-section-eyebrow {
    display: block;
    margin: 0 0 2px;
    color: var(--tar-muted);
    font-size: .62rem;
    font-weight: 900;
    letter-spacing: .08em;
    line-height: 1.2;
    text-transform: uppercase;
}

.tarifa-section-title h2,
.tar-panel-title {
    margin: 0;
    color: var(--tar-heading) !important;
    font-size: .9rem;
    font-weight: 900;
    line-height: 1.25;
}

.tarifa-section-title small,
.tar-panel-subtitle {
    margin-top: 3px;
    font-size: .7rem;
    line-height: 1.4;
}

.tarifa-index-page .btn-reset {
    min-width: 44px;
    min-height: 44px;
    display: inline-grid;
    place-items: center;
    padding: 0;
    border-radius: 12px;
}

.tarifa-index-page .btn-reset span {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
}

.tarifa-index-page .tar-filters > .p-4 {
    grid-template-columns: 1fr !important;
    gap: 14px !important;
    padding: 16px !important;
    background: #FFFFFF;
}

.tarifa-index-page .filter-label {
    display: block;
    margin-bottom: 6px;
    color: var(--tar-text) !important;
    font-size: .68rem;
    letter-spacing: .04em;
}

.tarifa-index-page .filter-select {
    min-height: 46px;
    padding: 9px 11px;
    border: 1px solid var(--ti-line) !important;
    border-radius: 12px;
    color: var(--tar-text);
    background: var(--tar-bg) !important;
    font-size: .78rem;
    font-weight: 700;
}

.tarifa-filter-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin: 0 16px 16px;
    padding: 10px 11px;
    border: 1px solid color-mix(in srgb, var(--ti-info) 18%, var(--ti-line));
    border-radius: 11px;
    color: color-mix(in srgb, var(--ti-info) 68%, #25384A);
    background: var(--ti-info-soft);
    font-size: .68rem;
    font-weight: 700;
    line-height: 1.4;
}

.tarifa-filter-note i {
    margin-top: 2px;
}

.tar-panel-count {
    border-color: var(--ti-line);
    border-radius: 10px;
    color: var(--tar-brand);
    background: #FFFFFF;
}

.tarifa-index-page .tar-panel-body {
    padding: 14px 16px 18px !important;
    background: var(--tar-bg);
}

.tarifa-index-page .dataTables_wrapper .dataTables_filter {
    position: relative;
    width: min(360px, 100%);
}

.tarifa-index-page .dataTables_wrapper .dataTables_filter label {
    width: 100%;
    margin: 0;
    color: transparent;
    font-size: 0;
}

.tarifa-index-page .dataTables_wrapper .dataTables_filter input {
    width: 100% !important;
    min-height: 44px;
    margin: 0 !important;
    padding: 9px 12px 9px 38px !important;
    border: 1px solid var(--ti-line) !important;
    border-radius: 12px !important;
    color: var(--tar-text) !important;
    background:
        linear-gradient(#FFFFFF, #FFFFFF) padding-box,
        #FFFFFF !important;
    font-size: .78rem !important;
}

.tarifa-index-page .dataTables_wrapper .dataTables_filter::before {
    content: "\f002";
    position: absolute;
    z-index: 2;
    margin: 13px 0 0 14px;
    color: var(--tar-muted);
    font-family: "Font Awesome 5 Free";
    font-size: .75rem;
    font-weight: 900;
    pointer-events: none;
}

.tarifa-index-page #tablaTarifas {
    border-collapse: separate !important;
    border-spacing: 0 8px !important;
}

.tarifa-index-page #tablaTarifas thead th {
    padding: 5px 10px !important;
}

.tarifa-index-page #tablaTarifas tbody td {
    padding: 11px 10px !important;
    border-top: 1px solid var(--ti-line) !important;
    border-bottom: 1px solid var(--ti-line) !important;
    background: #FFFFFF;
}

.tarifa-index-page #tablaTarifas tbody td:first-child {
    border-left: 1px solid var(--ti-line) !important;
    border-radius: 13px 0 0 13px;
}

.tarifa-index-page #tablaTarifas tbody td:last-child {
    border-right: 1px solid var(--ti-line) !important;
    border-radius: 0 13px 13px 0;
}

.tarifa-index-page .icn-btn {
    width: 40px !important;
    height: 40px !important;
    border-radius: 11px !important;
}

.tarifa-index-page .empty-state {
    min-height: 330px;
    display: grid;
    place-items: center;
    padding: 34px 20px;
    border: 1px dashed color-mix(in srgb, var(--tar-brand) 18%, var(--ti-line));
    border-radius: 16px;
    background: #FFFFFF !important;
}

.tarifa-empty-inner {
    width: min(430px, 100%);
    text-align: center;
}

.tarifa-empty-icon {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    margin: 0 auto 14px;
    border: 1px solid color-mix(in srgb, var(--tar-brand) 14%, var(--ti-line));
    border-radius: 17px;
    color: var(--tar-brand);
    background: var(--ti-soft);
    font-size: 1.1rem;
}

.tarifa-empty-title {
    margin: 0 0 6px;
    color: var(--tar-heading);
    font-size: .96rem;
    font-weight: 900;
}

.tarifa-empty-copy {
    margin: 0 auto 17px;
    color: var(--tar-muted);
    font-size: .78rem;
    line-height: 1.5;
}

@media (max-width: 1050px) {
    .tarifa-index-summary.tarifa-metrics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    }
    .tar-metric-card:nth-child(2) { border-right: 0; }
    .tar-metric-card:nth-child(-n+2) { border-bottom: 1px solid var(--ti-line); }
    .tarifa-workspace { grid-template-columns: 230px minmax(0, 1fr); }
}

@media (max-width: 820px) {
    .tarifa-index-hero-inner { align-items: flex-start; }
    .tarifa-workspace { grid-template-columns: 1fr; }
    .tarifa-index-page .tar-filters { position: static; }
    .tarifa-index-page .tar-filters > .p-4 { grid-template-columns: repeat(3, minmax(0, 1fr)) !important; }
    .tarifa-filter-note { display: none; }
}

@media (max-width: 640px) {
    .tarifa-index-page { padding: 8px 8px calc(86px + env(safe-area-inset-bottom)) !important; }
    .tarifa-index-page .tar-topbar { border-radius: 20px !important; }
    .tarifa-index-page .tar-topbar > div { padding: 14px !important; }
    .tarifa-index-hero-inner { gap: 14px; }
    .tarifa-index-title-lockup { grid-template-columns: 42px minmax(0, 1fr); }
    .tarifa-index-hero-icon { width: 42px; height: 42px; border-radius: 13px; }
    .tarifa-index-page .tarifa-page-title { font-size: 1.5rem !important; }
    .tarifa-index-page .tarifa-page-subtitle { font-size: .75rem !important; }
    .tarifa-index-actions { display: grid; grid-template-columns: 1fr 1fr; }
    .tarifa-index-actions .btn-tar { width: 100%; padding-inline: 10px !important; }
    .tarifa-index-summary.tarifa-metrics-grid { border-radius: 17px !important; }
    .tar-metric-card { min-height: 104px; padding: 13px 12px; }
    .tar-metric-card:nth-child(odd) { border-right: 1px solid var(--ti-line); }
    .tar-metric-card:nth-child(even) { border-right: 0; }
    .tar-metric-label small, .tar-metric-status { display: none; }
    .tar-metric-number { font-size: 1.75rem; }
    .tar-type-breakdown { grid-template-columns: 1fr; }
    .tar-type-row { min-height: 24px; }
    .tarifa-index-page .tar-filters > .p-4 { grid-template-columns: 1fr !important; }
    .tarifa-section-header, .tar-panel-hd { min-height: 68px; align-items: center; flex-direction: row; }
    .tarifa-section-title small, .tar-panel-subtitle { display: none; }
    .tarifa-index-page .tar-panel-body { padding: 10px !important; }
    .tarifa-index-page .dataTables_wrapper .dataTables_filter { width: 100%; }
    .tarifa-index-page #tablaTarifas,
    .tarifa-index-page #tablaTarifas tbody,
    .tarifa-index-page #tablaTarifas tr,
    .tarifa-index-page #tablaTarifas td { display: block !important; width: 100% !important; }
    .tarifa-index-page #tablaTarifas thead { display: none !important; }
    .tarifa-index-page #tablaTarifas { border-spacing: 0 10px !important; }
    .tarifa-index-page #tablaTarifas tbody tr { overflow: hidden; border: 1px solid var(--ti-line); border-radius: 15px; background: #FFFFFF; }
    .tarifa-index-page #tablaTarifas tbody td {
        min-height: 38px;
        display: grid !important;
        grid-template-columns: minmax(88px, .7fr) minmax(0, 1.3fr) !important;
        align-items: center;
        gap: 10px;
        padding: 7px 12px !important;
        border: 0 !important;
        border-bottom: 1px solid color-mix(in srgb, var(--ti-line) 70%, #FFFFFF) !important;
        border-radius: 0 !important;
        text-align: right !important;
    }
    .tarifa-index-page #tablaTarifas tbody td::before {
        content: attr(data-label);
        color: var(--tar-muted);
        font-size: .62rem;
        font-weight: 900;
        letter-spacing: .04em;
        text-align: left;
        text-transform: uppercase;
    }
    .tarifa-index-page #tablaTarifas tbody td:last-child { border-bottom: 0 !important; }
    .tarifa-index-page #tablaTarifas tbody td.tarifa-name-cell {
        display: block !important;
        padding-top: 12px !important;
        padding-bottom: 10px !important;
        border-bottom-style: dashed !important;
        text-align: left !important;
    }
    .tarifa-index-page #tablaTarifas tbody td.tarifa-name-cell::before { content: none; }
    .tarifa-index-page #tablaTarifas tbody td.tarifa-created-cell { display: none !important; }
    .tarifa-index-page #tablaTarifas tbody td .flex { justify-content: flex-end !important; }
    .tarifa-index-page #tablaTarifas tbody td.tarifa-name-cell .flex { justify-content: flex-start !important; }
    .tarifa-index-page #tablaTarifas tbody td p { max-width: none !important; text-align: right; }
    .tarifa-index-page #tablaTarifas tbody td.tarifa-name-cell p { text-align: left; }
    .tarifa-index-page .icn-btn { width: 44px !important; height: 44px !important; }
    .tarifa-index-page .dataTables_info, .tarifa-index-page .dataTables_paginate { width: 100%; text-align: center !important; }
}

@media (max-width: 380px) {
    .tarifa-index-actions { grid-template-columns: 1fr; }
    .tarifa-index-summary.tarifa-metrics-grid { grid-template-columns: 1fr !important; }
    .tar-metric-card { border-right: 0 !important; border-bottom: 1px solid var(--ti-line); }
    .tar-metric-card:last-child { border-bottom: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .tarifa-index-page, .tarifa-index-page * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>

<div class="tar-page tarifa-index-page hotel-page min-h-screen">

    <!-- Top Bar -->
    <div class="tar-topbar tarifa-index-hero bg-white" aria-labelledby="tarifas-page-title">
        <div class="px-3 sm:px-5 lg:px-7 py-4">
            <?php $back_arrow_href = back_url('configuracion'); include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <div class="tarifa-index-hero-inner">

                <!-- Title -->
                <div class="tarifa-index-title-lockup">
                    <div class="tarifa-index-hero-icon" aria-hidden="true">
                        <i class="fas fa-tags text-lg"></i>
                    </div>
                    <div class="tarifa-index-title-copy">
                        <p class="tarifa-page-kicker">Tarifas din&aacute;micas</p>
                        <h1 id="tarifas-page-title" class="tarifa-page-title text-base sm:text-xl font-bold text-[#3D5234] leading-tight">Precios y temporadas</h1>
                        <p class="tarifa-page-subtitle text-xs text-gray-400 mt-0.5">Administra reglas de precio por temporada y revisa cuánto cobrarás antes de aplicarlas · <?= htmlspecialchars(function_exists('current_hotel_display_name') ? current_hotel_display_name('Medisoft Hoteles') : 'Medisoft Hoteles', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="tarifa-index-actions">
                    <button type="button" onclick="previsualizarPrecios()" class="btn-tar calc flex-1 sm:flex-none">
                        <i class="fas fa-calculator text-xs"></i>
                        <span>Simular precios</span>
                    </button>
                    <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new flex-1 sm:flex-none">
                        <i class="fas fa-plus text-xs"></i>
                        <span>Crear ajuste</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-3 sm:px-5 lg:px-7 py-5">

        <!-- Stat Widgets -->
        <?php $total_tipos_incremento = (int)($estadisticas['porcentaje'] ?? 0) + (int)($estadisticas['monto_fijo'] ?? 0); ?>
        <div class="tarifa-index-summary tarifa-metrics-grid mb-5" aria-label="Resumen de tarifas dinámicas">

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

        </div>

        <div class="tarifa-workspace">
        <!-- Filters -->
        <aside class="tar-filters" aria-labelledby="tarifa-filter-title">
            <div class="tar-filters-hd tarifa-section-header">
                <div class="tarifa-section-title">
                    <div class="tarifa-section-icon" aria-hidden="true">
                        <i class="fas fa-filter text-xs"></i>
                    </div>
                    <div class="tarifa-section-copy">
                        <span class="tarifa-section-eyebrow">1 · Define la vista</span>
                        <h2 id="tarifa-filter-title">Filtrar ajustes</h2>
                        <small>Combina estado, duración y alcance.</small>
                    </div>
                </div>
                <button type="button" onclick="resetFiltros()" class="btn-reset" aria-label="Limpiar filtros" title="Limpiar filtros">
                    <i class="fas fa-undo-alt text-xs" aria-hidden="true"></i><span>Limpiar filtros</span>
                </button>
            </div>
            <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="filter-label" for="filtroEstado">Estado</label>
                    <select id="filtroEstado" class="filter-select">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activos</option>
                        <option value="vigente">Vigentes Hoy</option>
                        <option value="futuro">Futuros</option>
                        <option value="pasado">Finalizados</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="filtroTipo">Duración</label>
                    <select id="filtroTipo" class="filter-select">
                        <option value="">Todos los tipos</option>
                        <option value="permanente">Permanentes</option>
                        <option value="temporal">Temporales</option>
                    </select>
                </div>
                <div>
                    <label class="filter-label" for="filtroAlcance">Alcance</label>
                    <select id="filtroAlcance" class="filter-select">
                        <option value="">Todos los alcances</option>
                        <option value="global">Global</option>
                        <option value="tipo_habitacion">Por Tipo de Habitación</option>
                        <option value="habitacion">Por Habitación</option>
                    </select>
                </div>
            </div>
            <p class="tarifa-filter-note">
                <i class="fas fa-info-circle" aria-hidden="true"></i>
                <span>Los filtros sólo cambian esta lista; no modifican ninguna regla de precio.</span>
            </p>
        </aside>

        <!-- Table Panel -->
        <section class="tar-panel" aria-labelledby="tarifa-rules-title">
            <div class="tar-panel-hd">
                <div class="tarifa-section-title">
                    <div class="tarifa-section-icon" aria-hidden="true">
                        <i class="fas fa-layer-group text-xs"></i>
                    </div>
                    <div class="tarifa-section-copy">
                        <span class="tarifa-section-eyebrow">2 · Revisa y administra</span>
                        <h2 class="tar-panel-title" id="tarifa-rules-title">Ajustes configurados</h2>
                        <small class="tar-panel-subtitle">Consulta vigencia, alcance y estado de cada regla.</small>
                    </div>
                </div>
                <div class="tar-panel-count" aria-live="polite"><?= number_format(count($incrementos ?? [])) ?> registros</div>
            </div>
            <div class="tar-panel-body">
                <?php if (empty($incrementos)): ?>
                    <div class="empty-state">
                        <div class="tarifa-empty-inner">
                            <div class="tarifa-empty-icon" aria-hidden="true">
                                <i class="fas fa-tags"></i>
                            </div>
                            <h3 class="tarifa-empty-title">Aún no hay ajustes de precio</h3>
                            <p class="tarifa-empty-copy">Crea una regla para subir o bajar precios por temporada, evento o habitación específica.</p>
                            <a href="<?= url('configuracion/tarifas/crear') ?>" class="btn-tar new">
                                <i class="fas fa-plus text-xs" aria-hidden="true"></i> Crear primer ajuste
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto lc-scroll">
                        <table class="min-w-full" id="tablaTarifas">
                            <caption class="sr-only">Listado de ajustes de precio configurados</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="text-left">
                                        <span class="flex items-center gap-1">
                                            <i class="fas fa-sort-numeric-up"></i>
                                            <span class="hidden xs:inline">Prior.</span>
                                        </span>
                                    </th>
                                    <th scope="col" class="text-left">Ajuste</th>
                                    <th scope="col" class="text-left hidden sm:table-cell">Valor</th>
                                    <th scope="col" class="text-left hidden md:table-cell">Alcance</th>
                                    <th scope="col" class="text-left">Vigencia</th>
                                    <th scope="col" class="text-center">
                                        <span class="hidden sm:inline">Estado</span>
                                        <i class="fas fa-toggle-on sm:hidden"></i>
                                    </th>
                                    <th scope="col" class="text-left hidden lg:table-cell">Creado</th>
                                    <th scope="col" class="text-center">
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
                                    <td data-label="Prioridad">
                                        <span class="prio-badge"><?= $inc['prioridad'] ?></span>
                                    </td>

                                    <!-- Nombre -->
                                    <td class="tarifa-name-cell" data-label="Ajuste">
                                        <p class="text-xs font-semibold text-gray-800 truncate max-w-[160px]">
                                            <?= htmlspecialchars($inc['nombre']) ?>
                                        </p>
                                        <?php if (($inc['origen'] ?? null) === 'copiloto'): ?>
                                            <span class="copiloto-badge" title="Creado desde el consejo del Copiloto IA y aprobado por una persona">&#10024; Copiloto</span>
                                            <?php if (!empty($inc['resultado_copiloto'])): $rc = $inc['resultado_copiloto']; ?>
                                                <p class="copiloto-resultado">
                                                    Resultado: ocupaci&oacute;n <strong><?= number_format((float) $rc['ocupacion_real'], 1) ?>%</strong> real<?php
                                                        if ($rc['ocupacion_proyectada'] !== null):
                                                            $mejoro = $rc['ocupacion_real'] >= $rc['ocupacion_proyectada'];
                                                    ?> vs <?= number_format($rc['ocupacion_proyectada'], 1) ?>% proyectada al aplicar
                                                        <span class="<?= $mejoro ? 'rc-up' : 'rc-down' ?>"><?= $mejoro ? '▲' : '▼' ?></span><?php endif; ?><?php
                                                        if ($rc['tarifa_real'] !== null): ?> &middot; tarifa prom. $<?= number_format((float) $rc['tarifa_real'], 2) ?><?php
                                                            if ($rc['tarifa_al_aplicar'] !== null): ?> (al aplicar $<?= number_format($rc['tarifa_al_aplicar'], 2) ?>)<?php endif; ?><?php endif; ?>
                                                </p>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($inc['descripcion']): ?>
                                            <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">
                                                <?= htmlspecialchars($inc['descripcion']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <!-- Valor en móvil -->
                                        <div class="sm:hidden mt-1">
                                            <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                                <span class="val-badge"><i class="fas fa-percentage text-xs"></i><?= (($inc['clase'] ?? 'incremento') === 'descuento') ? '&minus;' : '+' ?><?= number_format($inc['valor_incremento'],2) ?>%</span>
                                            <?php else: ?>
                                                <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i><?= (($inc['clase'] ?? 'incremento') === 'descuento') ? '&minus;' : '+' ?><?= format_currency($inc['valor_incremento']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- Valor desktop -->
                                    <td class="hidden sm:table-cell" data-label="Valor">
                                        <?php if ($inc['tipo_incremento'] == 'porcentaje'): ?>
                                            <span class="val-badge"><i class="fas fa-percentage text-xs"></i><?= (($inc['clase'] ?? 'incremento') === 'descuento') ? '&minus;' : '+' ?><?= number_format($inc['valor_incremento'],2) ?>%</span>
                                        <?php else: ?>
                                            <span class="val-badge"><i class="fas fa-dollar-sign text-xs"></i><?= (($inc['clase'] ?? 'incremento') === 'descuento') ? '&minus;' : '+' ?><?= format_currency($inc['valor_incremento']) ?></span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Alcance -->
                                    <td class="hidden md:table-cell" data-label="Alcance">
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
                                    <td data-label="Vigencia">
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
                                    <td class="text-center" data-label="Estado">
                                        <label class="tarifa-switch relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox"
                                                   class="sr-only peer toggle-activo"
                                                   data-id="<?= $inc['id'] ?>"
                                                   aria-label="<?= htmlspecialchars(($inc['activo'] ? 'Desactivar ' : 'Activar ') . $inc['nombre'], ENT_QUOTES, 'UTF-8') ?>"
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
                                    <td class="tarifa-created-cell hidden lg:table-cell" data-label="Creado por">
                                        <p class="text-xs font-semibold text-gray-700"><?= htmlspecialchars($inc['usuario_nombre']) ?></p>
                                        <p class="text-xs text-gray-400"><?= format_date($inc['created_at'],'d/m/Y H:i') ?></p>
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center" data-label="Acciones">
                                        <div class="flex items-center justify-center gap-0.5">
                                            <a href="<?= url('configuracion/tarifas/editar/'.$inc['id']) ?>"
                                               class="icn-btn icn-edit" title="Editar" aria-label="Editar <?= htmlspecialchars($inc['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-edit" aria-hidden="true"></i>
                                            </a>
                                            <button type="button" class="icn-btn icn-view btn-detalle"
                                                    data-id="<?= $inc['id'] ?>" title="Ver detalles" aria-label="Ver detalles de <?= htmlspecialchars($inc['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fas fa-eye" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="icn-btn icn-del btn-eliminar"
                                                    data-id="<?= $inc['id'] ?>" title="Eliminar" aria-label="Eliminar <?= htmlspecialchars($inc['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ($inc['activo'] && $estado_fecha=='vigente') ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash" aria-hidden="true"></i>
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
        </section>
        </div><!-- end workspace -->
    </div>
</div><!-- end page -->

<!-- ══════ Modal Simulador de Precios ══════ -->
<div class="modal fade tar-price-modal"
     id="modalPrecios"
     tabindex="-1"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalPreciosTitulo"
     aria-describedby="modalPreciosDescripcion">
    <div class="modal-dialog tar-price-modal__dialog" role="document">
        <div class="modal-content tar-price-modal__content">
            <header class="modal-header tar-price-modal__header">
                <div class="tar-price-modal__title-lockup">
                    <span class="tar-price-modal__icon" aria-hidden="true">
                        <i class="fas fa-calculator"></i>
                    </span>
                    <div>
                        <p class="tar-price-modal__eyebrow">Vista previa</p>
                        <h5 class="modal-title tar-price-modal__title" id="modalPreciosTitulo">Simula el precio por habitación</h5>
                        <p class="tar-price-modal__subtitle" id="modalPreciosDescripcion">Consulta cuánto cobrarías en una fecha antes de crear o modificar una tarifa.</p>
                    </div>
                </div>
                <button type="button"
                        class="close tar-price-modal__close"
                        data-dismiss="modal"
                        aria-label="Cerrar simulador">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body tar-price-modal__body">
                <aside class="tar-price-controls" aria-labelledby="tarPriceStepTitle">
                    <div class="tar-price-controls__layout">
                        <div class="tar-price-step">
                            <span class="tar-price-step__number" aria-hidden="true">1</span>
                            <div class="tar-price-step__copy">
                                <h6 id="tarPriceStepTitle">Elige la fecha</h6>
                                <p>Usaremos las tarifas activas y vigentes para ese día.</p>
                            </div>
                        </div>

                        <div class="tar-price-field">
                            <label for="fechaPreview">Fecha de hospedaje</label>
                            <div class="tar-price-date-wrap">
                                <i class="fas fa-calendar-day" aria-hidden="true"></i>
                                <input type="date"
                                       class="tar-price-date"
                                       id="fechaPreview"
                                       value="<?= date('Y-m-d') ?>"
                                       min="<?= date('Y-m-d') ?>"
                                       aria-describedby="fechaPreviewAyuda">
                            </div>
                            <small class="tar-price-field__help" id="fechaPreviewAyuda">El resultado se actualiza automáticamente al cambiar el día.</small>
                        </div>

                        <div class="tar-price-readonly-note">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                            <span>Esta consulta es sólo informativa. No guarda cambios ni modifica reservaciones.</span>
                        </div>
                    </div>
                </aside>

                <section class="tar-price-results lc-scroll"
                         id="resultadoPrecios"
                         aria-live="polite"
                         aria-busy="false"
                         aria-label="Resultado del cálculo">
                    <div class="tar-price-state">
                        <div class="tar-price-state__inner">
                            <span class="tar-price-state__icon" aria-hidden="true"><i class="fas fa-calendar-check"></i></span>
                            <h6>Revisa el precio antes de aplicarlo</h6>
                            <p>Selecciona una fecha para ver el precio base, los incrementos vigentes y el precio final de cada habitación.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style id="tar-detail-serene">
.tar-detail-modal .modal-dialog { max-width: 760px; margin: 1.75rem auto; }
.tar-detail-modal .modal-content { overflow: hidden; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #e7e2d9); border-radius: 22px; background: #fff; box-shadow: 0 28px 80px rgba(18,27,42,.25); }
.tar-detail-modal .modal-header { min-height: 82px; display: flex; align-items: center; justify-content: space-between; padding: 17px 20px; border-bottom: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 11%, #e9e5dd); background: linear-gradient(135deg, color-mix(in srgb, var(--brand-primary, #1B2746) 7%, #fff), #fff 62%); }
.tar-detail-modal__heading { display: flex; align-items: center; gap: 12px; min-width: 0; }
.tar-detail-modal__icon { width: 42px; height: 42px; flex: 0 0 42px; display: grid; place-items: center; border-radius: 13px; color: #fff; background: var(--brand-primary, #1B2746); box-shadow: 0 9px 22px color-mix(in srgb, var(--brand-primary, #1B2746) 20%, transparent); }
.tar-detail-modal__eyebrow { margin: 0 0 2px; color: #7d8999; font-size: .64rem; font-weight: 850; letter-spacing: .11em; text-transform: uppercase; }
.tar-detail-modal .modal-title { margin: 0; color: #172033; font-size: 1.08rem; font-weight: 850; line-height: 1.2; }
.tar-detail-modal .close { width: 42px; height: 42px; display: grid; place-items: center; margin: 0; padding: 0; border: 1px solid #ded9d0; border-radius: 12px; color: #586579; background: rgba(255,255,255,.8); opacity: 1; font-size: 1rem; text-shadow: none; }
.tar-detail-modal .close:hover { color: #172033; border-color: #c8c1b6; background: #fff; }
.tar-detail-modal .modal-body { max-height: calc(100dvh - 150px); overflow-y: auto; padding: 18px 20px 20px; background: #fbfbfa; }
.tar-detail-hero { display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 16px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #e3ded5); border-radius: 17px; background: #fff; }
.tar-detail-hero__copy { min-width: 0; }
.tar-detail-hero__label { margin: 0 0 4px; color: #8590a0; font-size: .65rem; font-weight: 850; letter-spacing: .09em; text-transform: uppercase; }
.tar-detail-hero h3 { margin: 0; overflow: hidden; color: #172033; font-size: 1.18rem; font-weight: 850; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
.tar-detail-hero p { margin: 5px 0 0; color: #6c7889; font-size: .77rem; line-height: 1.4; }
.tar-detail-value { flex: 0 0 auto; min-width: 120px; padding: 11px 13px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 15%, #ded9d0); border-radius: 14px; color: var(--brand-primary, #1B2746); background: color-mix(in srgb, var(--brand-primary, #1B2746) 5%, #fff); text-align: right; }
.tar-detail-value small { display: block; margin-bottom: 2px; color: #7d8999; font-size: .62rem; font-weight: 800; text-transform: uppercase; }
.tar-detail-value strong { display: block; font-size: 1.14rem; line-height: 1.15; }
.tar-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 12px; }
.tar-detail-section { min-width: 0; padding: 15px 16px; border: 1px solid #e5e0d7; border-radius: 17px; background: #fff; }
.tar-detail-section__title { display: flex; align-items: center; gap: 8px; margin: 0 0 9px; color: #3f4c5f; font-size: .71rem; font-weight: 850; letter-spacing: .07em; text-transform: uppercase; }
.tar-detail-section__title i { color: var(--brand-primary, #1B2746); }
.tar-detail-list { margin: 0; }
.tar-detail-row { display: grid; grid-template-columns: 92px minmax(0, 1fr); gap: 10px; padding: 9px 0; border-bottom: 1px solid #f0ede7; }
.tar-detail-row:last-child { border-bottom: 0; }
.tar-detail-row dt { color: #8792a1; font-size: .7rem; font-weight: 720; }
.tar-detail-row dd { min-width: 0; margin: 0; color: #263246; font-size: .76rem; font-weight: 730; line-height: 1.45; text-align: right; overflow-wrap: anywhere; }
.tar-detail-tags { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 5px; }
.tar-detail-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 7px; border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 13%, #ded9d0); border-radius: 999px; color: #526074; background: #faf9f7; font-size: .66rem; font-weight: 760; }
.tar-detail-status { display: inline-flex; align-items: center; gap: 6px; padding: 5px 8px; border-radius: 999px; font-size: .68rem; font-weight: 820; }
.tar-detail-status.is-active { color: #116945; background: #eaf8f1; }
.tar-detail-status.is-inactive { color: #875247; background: #fbefec; }
.tar-detail-meta { display: flex; align-items: center; gap: 8px; margin: 12px 2px 0; color: #818c9b; font-size: .68rem; }
.tar-detail-meta i { color: color-mix(in srgb, var(--brand-accent, #BD9441) 78%, #5b4520); }
@media (max-width: 767px) {
    .tar-detail-modal .modal-dialog { margin: .7rem; }
    .tar-detail-modal .modal-content { border-radius: 19px; }
    .tar-detail-modal .modal-header { min-height: 72px; padding: 13px 14px; }
    .tar-detail-modal .modal-body { max-height: calc(100dvh - 100px); padding: 13px; }
    .tar-detail-hero { align-items: flex-start; padding: 14px; }
    .tar-detail-hero h3 { white-space: normal; }
    .tar-detail-value { min-width: 104px; }
    .tar-detail-grid { grid-template-columns: 1fr; }
}
@media (max-width: 470px) {
    .tar-detail-hero { display: block; }
    .tar-detail-value { margin-top: 12px; text-align: left; }
    .tar-detail-row { grid-template-columns: 84px minmax(0, 1fr); }
}
</style>

<!-- ══════ Modal de detalle del ajuste ══════ -->
<div class="modal fade tar-detail-modal" id="modalDetalle" tabindex="-1" role="dialog" aria-labelledby="modalDetalleTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div class="tar-detail-modal__heading">
                    <span class="tar-detail-modal__icon" aria-hidden="true"><i class="fas fa-tag"></i></span>
                    <div>
                        <p class="tar-detail-modal__eyebrow">Tarifa dinámica</p>
                        <h5 class="modal-title" id="modalDetalleTitle">Detalle del ajuste</h5>
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar detalle">
                    <i class="fas fa-times" aria-hidden="true"></i>
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
    const $tablaTarifas = $('#tablaTarifas');
    let tabla = null;

    function coincideConFiltros($fila) {
        const estado = $('#filtroEstado').val();
        const tipo = $('#filtroTipo').val();
        const alcance = $('#filtroAlcance').val();

        if (estado === 'vigente' && !($fila.data('vigencia') === 'vigente' && $fila.data('estado') === 'activo')) return false;
        if (estado === 'activo' && $fila.data('estado') !== 'activo') return false;
        if (estado && estado !== 'vigente' && estado !== 'activo' && $fila.data('vigencia') !== estado) return false;
        if (tipo && $fila.data('tipo') !== tipo) return false;
        if (alcance && $fila.data('alcance') !== alcance) return false;

        return true;
    }

    if ($tablaTarifas.length) {
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (!settings.nTable || settings.nTable.id !== 'tablaTarifas') return true;
            const fila = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
            return fila ? coincideConFiltros($(fila)) : true;
        });

        tabla = $tablaTarifas.DataTable({
            language: {
                search: '',
                searchPlaceholder: 'Buscar ajuste…',
                emptyTable: 'Todavía no hay ajustes configurados.',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ ajustes',
                infoEmpty: 'Mostrando 0 ajustes',
                infoFiltered: '(filtrados de _MAX_ ajustes)',
                lengthMenu: 'Mostrar _MENU_ ajustes',
                loadingRecords: 'Cargando ajustes…',
                processing: 'Procesando…',
                zeroRecords: 'No encontramos ajustes con estos filtros.',
                paginate: {
                    first: 'Primera',
                    last: 'Última',
                    next: 'Siguiente',
                    previous: 'Anterior'
                },
                aria: {
                    sortAscending: ': ordenar ascendente',
                    sortDescending: ': ordenar descendente'
                }
            },
            order: [[0,'desc'],[4,'desc']],
            pageLength: 10,
            lengthMenu: [[10,25,50,-1],[10,25,50,'Todos']],
            columnDefs: [{ orderable:false, targets:[7] }],
            dom: '<"flex flex-col sm:flex-row justify-between items-center mb-4"<"flex items-center"f>>rt<"flex flex-col sm:flex-row justify-between items-center mt-4"<"text-xs text-gray-400"i><"flex items-center"p>>',
            initComplete: function() {
                $('.dataTables_length').hide();
                actualizarConteoResultados();
            }
        });

        tabla.on('draw.dt', actualizarConteoResultados);
        actualizarConteoResultados();
    }

    $('[data-toggle="tooltip"]').tooltip();

    // Filtros
    $('#filtroEstado, #filtroTipo, #filtroAlcance').change(function() {
        if (tabla) tabla.draw();
    });

    function actualizarConteoResultados() {
        if (!tabla) return;
        const total = tabla.rows({ search: 'applied' }).count();
        $('.tar-panel-count').text(`${total} ${total === 1 ? 'registro' : 'registros'}`);
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

    function tarifaDetailEscape(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function tarifaDetailTags(items, formatter) {
        const values = Array.isArray(items) ? items : [];
        if (!values.length) return '<span class="tar-detail-tag">Sin selección registrada</span>';
        return `<span class="tar-detail-tags">${values.map(item => `<span class="tar-detail-tag">${tarifaDetailEscape(formatter(item))}</span>`).join('')}</span>`;
    }

    function mostrarDetalle(inc) {
        const esDescuento = inc.clase === 'descuento';
        const signo = esDescuento ? '−' : '+';
        const operacion = esDescuento ? 'Descuento' : 'Incremento';
        const tipo = inc.tipo_incremento === 'porcentaje' ? 'Porcentaje' : 'Monto fijo';
        const valor = inc.tipo_incremento === 'porcentaje'
            ? `${signo}${tarifaDetailEscape(inc.valor_incremento)}%`
            : `${signo}${tarifaDetailEscape(formatCurrency(inc.valor_incremento))}`;
        const estaActivo = inc.activo === true || Number(inc.activo) === 1;
        const esPermanente = inc.es_permanente === true || Number(inc.es_permanente) === 1;

        let alcanceNombre = 'Todas las habitaciones';
        let alcanceDetalle = '<span class="tar-detail-tag"><i class="fas fa-globe" aria-hidden="true"></i> Todo el hotel</span>';
        if (inc.alcance === 'tipo_habitacion') {
            alcanceNombre = 'Por tipo de habitación';
            alcanceDetalle = tarifaDetailTags(inc.tipos_habitacion_array, gettipoHabitacion);
        } else if (inc.alcance === 'habitacion') {
            alcanceNombre = 'Habitaciones específicas';
            alcanceDetalle = tarifaDetailTags(inc.numeros_habitaciones || inc.habitaciones_array, item => `Hab. ${item}`);
        }

        const vigenciaNombre = esPermanente ? 'Permanente' : 'Temporal';
        const vigenciaFechas = esPermanente
            ? `Desde ${formatDate(inc.fecha_inicio)}`
            : `${formatDate(inc.fecha_inicio)} – ${formatDate(inc.fecha_fin)}`;

        const html = `
            <section class="tar-detail-hero">
                <div class="tar-detail-hero__copy">
                    <p class="tar-detail-hero__label">Ajuste configurado</p>
                    <h3>${tarifaDetailEscape(inc.nombre || 'Sin nombre')}</h3>
                    <p>${tarifaDetailEscape(inc.descripcion || 'Sin descripción')}</p>
                </div>
                <div class="tar-detail-value">
                    <small>${operacion}</small>
                    <strong>${valor}</strong>
                </div>
            </section>

            <div class="tar-detail-grid">
                <section class="tar-detail-section" aria-labelledby="tarDetailConfigTitle">
                    <h4 class="tar-detail-section__title" id="tarDetailConfigTitle"><i class="fas fa-sliders-h" aria-hidden="true"></i> Configuración</h4>
                    <dl class="tar-detail-list">
                        <div class="tar-detail-row"><dt>Operación</dt><dd>${operacion}</dd></div>
                        <div class="tar-detail-row"><dt>Cálculo</dt><dd>${tipo}</dd></div>
                        <div class="tar-detail-row"><dt>Prioridad</dt><dd>${tarifaDetailEscape(inc.prioridad ?? 0)}</dd></div>
                        <div class="tar-detail-row"><dt>Estado</dt><dd><span class="tar-detail-status ${estaActivo ? 'is-active' : 'is-inactive'}"><i class="fas ${estaActivo ? 'fa-check-circle' : 'fa-pause-circle'}" aria-hidden="true"></i>${estaActivo ? 'Activo' : 'Inactivo'}</span></dd></div>
                    </dl>
                </section>

                <section class="tar-detail-section" aria-labelledby="tarDetailApplyTitle">
                    <h4 class="tar-detail-section__title" id="tarDetailApplyTitle"><i class="fas fa-calendar-check" aria-hidden="true"></i> Aplicación</h4>
                    <dl class="tar-detail-list">
                        <div class="tar-detail-row"><dt>Alcance</dt><dd>${tarifaDetailEscape(alcanceNombre)}</dd></div>
                        <div class="tar-detail-row"><dt>Selección</dt><dd>${alcanceDetalle}</dd></div>
                        <div class="tar-detail-row"><dt>Vigencia</dt><dd>${vigenciaNombre}<br><span style="color:#818c9b;font-weight:600">${tarifaDetailEscape(vigenciaFechas)}</span></dd></div>
                    </dl>
                </section>
            </div>

            <p class="tar-detail-meta">
                <i class="fas fa-user-clock" aria-hidden="true"></i>
                Creado por <strong>${tarifaDetailEscape(inc.usuario_nombre || 'Usuario desconocido')}</strong> · ${tarifaDetailEscape(formatDate(inc.created_at, true))}
            </p>`;

        $('#contenidoDetalle').html(html);
        $('#modalDetalle').modal('show');
    }

    // Eliminar
    $('.btn-eliminar').click(async function() {
        const $btn = $(this), id = $btn.data('id');
        if ($btn.prop('disabled')) return;

        const ok = await msConfirm({
            type: 'error',
            icon: 'trash',
            title: '¿Eliminar tarifa?',
            msg: 'Esta acción no se puede deshacer.',
            confirmLabel: 'Sí, eliminar'
        });
        if (!ok) return;
        $btn.prop('disabled', true);

        $.post('<?= url("configuracion/tarifas/eliminar") ?>', { id:id, csrf_token:'<?= csrf_token() ?>' })
        .done(function(r) {
            if (r.success) {
                toastr.success(r.message);
                $btn.closest('tr').fadeOut(() => tabla.row($btn.closest('tr')).remove().draw());
            } else {
                $btn.prop('disabled', false);
                toastr.error(r.message||'Error al eliminar');
            }
        })
        .fail(() => {
            $btn.prop('disabled', false);
            toastr.error('Error de conexion');
        });
    });

    // Previsualizar precios. Es una consulta de solo lectura: no guarda cambios.
    const $modalPrecios = $('#modalPrecios');
    const $resultadoPrecios = $('#resultadoPrecios');
    let tarifaPreviewTrigger = null;

    // Como hijo directo de <body>, el modal no hereda restricciones del shell.
    if ($modalPrecios.parent()[0] !== document.body) {
        $modalPrecios.appendTo(document.body);
    }

    window.previsualizarPrecios = function() {
        tarifaPreviewTrigger = document.activeElement;
        $modalPrecios.modal('show');
        cargarPreciosPreview();
    };

    $modalPrecios.on('shown.bs.modal', function() {
        document.getElementById('fechaPreview')?.focus();
    });

    $modalPrecios.on('hidden.bs.modal', function() {
        if (tarifaPreviewTrigger && document.contains(tarifaPreviewTrigger)) {
            tarifaPreviewTrigger.focus();
        }
        tarifaPreviewTrigger = null;
    });

    $('#fechaPreview').change(cargarPreciosPreview);

    $resultadoPrecios.on('click', '.tar-price-group__toggle', function() {
        const panelId = this.getAttribute('aria-controls');
        const panel = panelId ? document.getElementById(panelId) : null;
        if (!panel) return;

        const expanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        panel.hidden = expanded;
    });

    function tarifaPreviewEscape(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function mostrarEstadoPrecios(tipo, titulo, detalle) {
        const iconos = {
            loading: 'fa-spinner fa-spin',
            error: 'fa-exclamation-triangle',
            empty: 'fa-bed'
        };
        const clase = tipo === 'error' ? ' is-error' : '';
        const role = tipo === 'error' ? ' role="alert"' : ' role="status"';
        $resultadoPrecios.html(
            `<div class="tar-price-state${clase}"${role}>
                <div class="tar-price-state__inner">
                    <span class="tar-price-state__icon" aria-hidden="true"><i class="fas ${iconos[tipo] || iconos.empty}"></i></span>
                    <h6>${tarifaPreviewEscape(titulo)}</h6>
                    <p>${tarifaPreviewEscape(detalle)}</p>
                </div>
            </div>`
        );
    }

    function cargarPreciosPreview() {
        const fecha = $('#fechaPreview').val();
        if (!fecha) {
            $resultadoPrecios.attr('aria-busy', 'false');
            mostrarEstadoPrecios('empty', 'Selecciona una fecha', 'Elige el día de hospedaje para consultar los precios aplicables.');
            return;
        }

        $resultadoPrecios.attr('aria-busy', 'true');
        mostrarEstadoPrecios('loading', 'Calculando precios', 'Estamos aplicando las tarifas activas y vigentes para la fecha seleccionada.');

        $.post('<?= url("configuracion/tarifas/previsualizar") ?>', { fecha:fecha, csrf_token:'<?= csrf_token() ?>' })
        .done(function(r) {
            $resultadoPrecios.attr('aria-busy', 'false');
            if (r && r.success) {
                mostrarTablaPrecios(r);
                return;
            }
            mostrarEstadoPrecios('error', 'No pudimos calcular los precios', 'Cambia la fecha o inténtalo nuevamente. No se modificó ninguna tarifa.');
        })
        .fail(function() {
            $resultadoPrecios.attr('aria-busy', 'false');
            mostrarEstadoPrecios('error', 'No pudimos cargar los precios', 'Revisa tu conexión y cambia la fecha para volver a intentarlo. No se modificó ninguna tarifa.');
        });
    }

    function mostrarTablaPrecios(data) {
        const precios = Array.isArray(data.precios) ? data.precios : [];
        if (!precios.length) {
            mostrarEstadoPrecios('empty', 'No hay habitaciones para mostrar', 'No encontramos habitaciones activas con precios para la fecha seleccionada.');
            return;
        }

        const habitacionesConIncremento = precios.filter(p => Number(p.incremento) > 0).length;
        const habitacionesSinCambio = precios.length - habitacionesConIncremento;
        const fechaFormateada = tarifaPreviewEscape(data.fecha_formateada || data.fecha || '');

        let html = `<section class="tar-price-summary" aria-label="Resumen del cálculo">
            <div>
                <p class="tar-price-summary__eyebrow">2 · Revisa el resultado</p>
                <h6>Precios para el ${fechaFormateada}</h6>
                <p class="tar-price-summary__hint">Precio base + incrementos vigentes = precio final por noche.</p>
            </div>
            <div class="tar-price-metrics">
                <div class="tar-price-metric"><strong>${precios.length}</strong><span>Habitaciones</span></div>
                <div class="tar-price-metric is-adjusted"><strong>${habitacionesConIncremento}</strong><span>Con incremento</span></div>
                <div class="tar-price-metric"><strong>${habitacionesSinCambio}</strong><span>Sin cambio</span></div>
            </div>
        </section>`;

        const porTipo = {};
        precios.forEach(p => {
            const tipo = String(p.tipo || 'Sin tipo de habitación');
            if (!porTipo[tipo]) porTipo[tipo] = [];
            porTipo[tipo].push(p);
        });

        html += '<div class="tar-price-groups">';
        Object.keys(porTipo).forEach((tipo, idx) => {
            const habitaciones = porTipo[tipo];
            const ajustadas = habitaciones.filter(h => Number(h.incremento) > 0).length;
            const panelId = `precio-tipo-${idx}`;
            const expandido = idx === 0;

            html += `<article class="tar-price-group">
                <button type="button"
                        class="tar-price-group__toggle"
                        aria-expanded="${expandido ? 'true' : 'false'}"
                        aria-controls="${panelId}">
                    <span class="tar-price-group__icon" aria-hidden="true"><i class="fas fa-bed"></i></span>
                    <span class="tar-price-group__copy">
                        <strong>${tarifaPreviewEscape(tipo)}</strong>
                        <span>${habitaciones.length} ${habitaciones.length === 1 ? 'habitación' : 'habitaciones'}</span>
                    </span>
                    ${ajustadas > 0 ? `<span class="tar-price-group__status"><i class="fas fa-tag" aria-hidden="true"></i>${ajustadas} con incremento</span>` : ''}
                    <i class="fas fa-chevron-down tar-price-group__chevron" aria-hidden="true"></i>
                </button>
                <div class="tar-price-group__body" id="${panelId}"${expandido ? '' : ' hidden'}>
                    <div class="tar-price-table-wrap lc-scroll">
                        <table class="tar-price-table">
                            <thead>
                                <tr>
                                    <th>Habitación</th>
                                    <th class="is-number">Precio base</th>
                                    <th>Tarifas aplicadas</th>
                                    <th class="is-number">Precio final</th>
                                    <th class="is-number">Diferencia</th>
                                </tr>
                            </thead>
                            <tbody>`;

            habitaciones.forEach(habitacion => {
                const incremento = Number(habitacion.incremento) || 0;
                const precioBase = Number(habitacion.precio_base) || 0;
                const tieneIncremento = incremento > 0;
                const incrementos = Array.isArray(habitacion.incrementos) ? habitacion.incrementos : [];

                html += `<tr class="${tieneIncremento ? 'is-adjusted' : ''}">
                    <td class="tar-price-room-cell" data-label="Habitación">
                        <span class="tar-price-room"><i class="fas fa-door-closed" aria-hidden="true"></i>Hab. ${tarifaPreviewEscape(habitacion.habitacion)}</span>
                    </td>
                    <td class="is-number" data-label="Precio base">${formatCurrency(precioBase)}</td>
                    <td data-label="Tarifas aplicadas">`;

                if (incrementos.length) {
                    html += '<div class="tar-price-rules">';
                    incrementos.forEach(incrementoAplicado => {
                        const porcentaje = incrementoAplicado.tipo === 'porcentaje'
                            ? ` <span>(${tarifaPreviewEscape(incrementoAplicado.valor)}%)</span>`
                            : '';
                        html += `<div class="tar-price-rule">
                            <i class="fas fa-tag" aria-hidden="true"></i>
                            <span>${tarifaPreviewEscape(incrementoAplicado.nombre)}: <strong>+${formatCurrency(incrementoAplicado.aumento)}</strong>${porcentaje}</span>
                        </div>`;
                    });
                    html += '</div>';
                } else {
                    html += '<span class="tar-price-no-change">Sin incrementos</span>';
                }

                html += `</td>
                    <td class="is-number" data-label="Precio final"><span class="tar-price-final">${formatCurrency(habitacion.precio_final)}</span></td>
                    <td class="is-number" data-label="Diferencia">`;

                if (tieneIncremento) {
                    const porcentajeDiferencia = precioBase > 0 ? ((incremento / precioBase) * 100).toFixed(1) : '0.0';
                    html += `<span class="tar-price-difference">+${formatCurrency(incremento)}<small>+${porcentajeDiferencia}%</small></span>`;
                } else {
                    html += '<span class="tar-price-no-change">Sin cambio</span>';
                }

                html += '</td></tr>';
            });

            html += '</tbody></table></div></div></article>';
        });

        html += '</div>';
        $resultadoPrecios.html(html);
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
    window.resetFiltros = function() {
        $('#filtroEstado,#filtroTipo,#filtroAlcance').val('');
        if (tabla) tabla.draw();
    };
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
