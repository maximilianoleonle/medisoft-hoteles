<?php
/**
 * Vista Principal de Caja
 * Vista hotelera
 */
?>

<!-- Sistema de diseño "Caja boutique" -->
<style>
    :root {
        --cash-navy: var(--brand-action-bg, var(--brand-primary, #1B2746));
        --cash-navy-deep: var(--brand-action-bg-hover, var(--brand-secondary, #0F172A));
        --cash-gold: var(--brand-accent, #BD9441);
        --cash-on-brand: var(--brand-action-text, #FFFEFB);

        --cash-paper: color-mix(in srgb, var(--cash-gold) 4%, #F8F5ED);
        --cash-paper-2: color-mix(in srgb, var(--cash-gold) 3%, #FBF9F4);
        --cash-surface: #FFFFFF;
        --cash-surface-soft: color-mix(in srgb, var(--cash-gold) 3%, #FFFFFF);

        --cash-border: color-mix(in srgb, var(--cash-navy) 6%, #E7DEC9);
        --cash-border-soft: color-mix(in srgb, var(--cash-navy) 4%, #F0ECE2);

        --cash-heading: var(--brand-text, #111827);
        --cash-ink: var(--brand-text, #1F2937);
        --cash-muted: var(--brand-muted, #667085);

        --cash-green: #1F9D63;
        --cash-green-bg: #E7F5EE;
        --cash-red: #D1453B;
        --cash-red-bg: #FBEAEA;
        --cash-blue: #3B6FD6;
        --cash-blue-bg: #EAF1FC;
        --cash-purple: #7C5CD6;
        --cash-purple-bg: #F0ECFB;
        --cash-gold-bg: color-mix(in srgb, var(--cash-gold) 14%, #FFFFFF);

        --cash-shadow: 0 1px 2px rgba(17,24,39,.04), 0 12px 28px -20px rgba(17,24,39,.28);
        --cash-shadow-lg: 0 18px 44px -22px rgba(17,24,39,.42);

        --cash-serif: 'Cormorant Garamond', Georgia, serif;
        --cash-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    }

    .lc-bg { background: var(--cash-paper); }
</style>

<style id="cash-boutique">
    @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

    .cash-page {
        min-height: 100vh;
        background:
            radial-gradient(1100px 480px at 88% -8%, color-mix(in srgb, var(--cash-gold) 8%, transparent), transparent 60%),
            linear-gradient(180deg, var(--cash-paper-2), var(--cash-paper) 60%, #F6F1E7) !important;
        color: var(--cash-ink);
        font-family: var(--cash-sans);
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
    }

    .cash-page *,
    #modalIngreso *,
    #modalGasto * {
        box-sizing: border-box;
    }

    .cash-page :where(p, span, a, button, input, textarea, select, label),
    #modalIngreso :where(p, span, a, button, input, textarea, select, label),
    #modalGasto :where(p, span, a, button, input, textarea, select, label) {
        font-family: var(--cash-sans);
    }

    .cash-page .max-w-7xl {
        max-width: 100% !important;
    }

    /* ── Banner de estado de caja (abierta) — pieza del diseño caja.html ── */
    .cash-page .caja-status { display: flex; align-items: center; gap: 18px; border-radius: 16px; padding: 17px 22px;
        background: linear-gradient(120deg, var(--cash-green-bg), color-mix(in srgb, var(--cash-green-bg) 45%, #fff));
        border: 1px solid color-mix(in srgb, var(--cash-green) 22%, transparent); border-left: 5px solid var(--cash-green);
        box-shadow: var(--cash-shadow); flex-wrap: wrap; }
    .cash-page .caja-status-ic { width: 50px; height: 50px; border-radius: 15px; display: grid; place-items: center; flex: none;
        color: #fff; background: var(--cash-green); box-shadow: var(--cash-shadow); font-size: 1.25rem; }
    .cash-page .caja-status-meta .t { font-family: var(--cash-serif); font-size: 1.5rem; font-weight: 600; color: var(--cash-navy); line-height: 1; }
    .cash-page .caja-status-meta .d { font-size: .82rem; color: var(--cash-muted); font-weight: 500; margin-top: 6px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .cash-page .caja-status-meta .d b { color: var(--cash-navy); font-weight: 700; }
    .cash-page .caja-pulse { display: inline-flex; align-items: center; gap: 7px; font-size: .72rem; font-weight: 800; color: var(--cash-green);
        background: color-mix(in srgb, var(--cash-green) 13%, transparent); padding: 5px 12px; border-radius: 99px; }
    .cash-page .caja-pulse .led { width: 8px; height: 8px; border-radius: 50%; background: currentColor; animation: cajaPulse 2s infinite; }
    @keyframes cajaPulse { 0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--cash-green) 42%, transparent); } 70% { box-shadow: 0 0 0 8px transparent; } 100% { box-shadow: 0 0 0 0 transparent; } }
    .cash-page .caja-status-right { margin-left: auto; text-align: right; }
    .cash-page .caja-status-right .l { font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--cash-muted); }
    .cash-page .caja-status-right .v { font-family: var(--cash-serif); font-size: 1.95rem; font-weight: 600; color: var(--cash-navy); margin-top: 2px; }
    @media (max-width: 640px) { .cash-page .caja-status-right { margin-left: 0; width: 100%; text-align: left; border-top: 1px dashed var(--cash-border); padding-top: 11px; margin-top: 2px; } }

    .cash-page .rounded-xl,
    .cash-page .rounded-2xl {
        border-radius: 16px !important;
    }

    .cash-page .header-card {
        background: linear-gradient(135deg, color-mix(in srgb, var(--cash-navy-deep) 96%, #000) 0%, var(--cash-navy-deep) 52%, var(--cash-navy) 100%) !important;
        border: 1px solid color-mix(in srgb, var(--cash-gold) 28%, rgba(255,255,255,.2)) !important;
        box-shadow: var(--cash-shadow-lg) !important;
    }

    .cash-page .header-card::before {
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        border-radius: 0 !important;
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--cash-navy-deep) 72%, transparent), transparent 70%),
            repeating-linear-gradient(135deg, rgba(255,255,255,.08) 0 1px, transparent 1px 18px) !important;
        opacity: .42;
        pointer-events: none;
    }

    .cash-page .header-card > * {
        position: relative;
        z-index: 1;
    }

    .cash-page .header-card h1 {
        font-family: var(--cash-serif);
        font-weight: 650 !important;
        letter-spacing: 0 !important;
        line-height: .95;
        color: var(--cash-on-brand) !important;
    }

    .cash-page .header-card .text-white {
        color: var(--cash-on-brand) !important;
    }

    .cash-page .header-card .text-white\/70 {
        color: color-mix(in srgb, var(--cash-on-brand) 82%, transparent) !important;
    }

    .cash-page .cash-header-icon {
        background: color-mix(in srgb, var(--cash-gold) 28%, rgba(255,255,255,.12)) !important;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
        color: var(--cash-on-brand) !important;
    }

    .cash-page .cash-meta-pill {
        border-color: rgba(255,255,255,.18) !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
    }

    .cash-page .bg-white.rounded-xl.shadow-sm,
    .cash-page .stat-card,
    .cash-page .quick-link {
        background: var(--cash-surface) !important;
        border-color: var(--cash-border) !important;
        box-shadow: var(--cash-shadow) !important;
    }

    .cash-page .stat-card {
        border-radius: 16px !important;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease !important;
    }

    .cash-page .stat-card::after {
        opacity: .9 !important;
    }

    .cash-page .stat-card:hover,
    .cash-page .quick-link:hover,
    .cash-page .metodo-card:hover,
    .cash-page .cat-item:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 16px 34px color-mix(in srgb, var(--cash-navy-deep) 10%, transparent) !important;
    }

    .cash-page .stat-card.card-inicial::after { background: var(--cash-gold) !important; }
    .cash-page .stat-card.card-ingresos::after { background: var(--cash-green) !important; }
    .cash-page .stat-card.card-gastos::after { background: var(--cash-red) !important; }

    .cash-page .card-efectivo {
        background: linear-gradient(135deg, var(--cash-navy) 0%, var(--cash-navy-deep) 100%) !important;
        border: 1px solid color-mix(in srgb, var(--cash-gold) 24%, rgba(255,255,255,.18)) !important;
        border-radius: 16px !important;
        box-shadow: var(--cash-shadow-lg) !important;
    }

    .cash-page .card-efectivo::before {
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        border-radius: 0 !important;
        background: repeating-linear-gradient(135deg, rgba(255,255,255,.08) 0 1px, transparent 1px 18px) !important;
        opacity: .34;
    }

    .cash-page .card-efectivo::after {
        display: none !important;
    }

    .cash-page .card-efectivo .text-white,
    .cash-page .card-efectivo p.text-3xl,
    .cash-page .card-efectivo .bg-white\/15 i {
        color: var(--cash-on-brand) !important;
    }

    .cash-page .card-efectivo .text-white\/70 {
        color: color-mix(in srgb, var(--cash-on-brand) 88%, transparent) !important;
    }

    .cash-page .card-efectivo .text-white\/60 {
        color: color-mix(in srgb, var(--cash-on-brand) 78%, transparent) !important;
    }

    .cash-page .icon-circle {
        border: 1px solid color-mix(in srgb, currentColor 18%, transparent);
    }

    .cash-page .icon-circle.green-ic {
        background: color-mix(in srgb, var(--cash-gold) 18%, #FFFFFF) !important;
        color: color-mix(in srgb, var(--cash-gold) 72%, #000) !important;
    }

    .cash-page .icon-circle.emerald-ic {
        background: var(--cash-green-bg) !important;
        color: var(--cash-green) !important;
    }

    .cash-page .icon-circle.red-ic {
        background: var(--cash-red-bg) !important;
        color: var(--cash-red) !important;
    }

    .cash-page .metodo-card {
        border-radius: 14px !important;
        border-width: 1px !important;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease !important;
    }

    .cash-page .metodo-card.mc-efectivo {
        border-color: color-mix(in srgb, var(--cash-green) 24%, #DCEFE5) !important;
        background: linear-gradient(135deg, #F5FBF8 0%, var(--cash-green-bg) 100%) !important;
    }

    .cash-page .metodo-card.mc-tarjeta {
        border-color: color-mix(in srgb, var(--cash-blue) 22%, #DDEAFB) !important;
        background: linear-gradient(135deg, #F7FAFF 0%, var(--cash-blue-bg) 100%) !important;
    }

    .cash-page .metodo-card.mc-transfer {
        border-color: color-mix(in srgb, var(--cash-purple) 22%, #E7E0F7) !important;
        background: linear-gradient(135deg, #FAF8FF 0%, var(--cash-purple-bg) 100%) !important;
    }

    /* ── Barra de proporción de ingresos por método (nuevo) ── */
    .cash-page .method-bar {
        display: flex;
        width: 100%;
        height: 10px;
        border-radius: 999px;
        overflow: hidden;
        background: var(--cash-border-soft);
        margin: 2px 0 14px;
    }
    .cash-page .method-bar-seg {
        height: 100%;
        transition: width .4s ease;
    }
    .cash-page .method-bar-seg.seg-efectivo { background: var(--cash-green); }
    .cash-page .method-bar-seg.seg-tarjeta { background: var(--cash-blue); }
    .cash-page .method-bar-seg.seg-transferencia { background: var(--cash-purple); }
    .cash-page .method-bar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        font-size: .76rem;
        color: var(--cash-muted);
        margin-bottom: 18px;
    }
    .cash-page .method-bar-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
    }
    .cash-page .method-bar-legend .dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex: none;
    }
    .cash-page .method-bar-legend .dot.seg-efectivo { background: var(--cash-green); }
    .cash-page .method-bar-legend .dot.seg-tarjeta { background: var(--cash-blue); }
    .cash-page .method-bar-legend .dot.seg-transferencia { background: var(--cash-purple); }

    /* ── Texto de ayuda bajo cada KPI (nuevo) ── */
    .cash-page .stat-help {
        font-size: .72rem;
        color: var(--cash-muted);
        margin-top: 6px;
        line-height: 1.3;
    }
    .cash-page .stat-help.on-dark {
        color: color-mix(in srgb, var(--cash-on-brand) 78%, transparent);
    }

    .cash-page h3,
    .cash-page h4,
    .cash-page .text-gray-800 {
        color: var(--cash-navy-deep) !important;
    }

    .cash-page .text-gray-400,
    .cash-page .text-gray-500,
    .cash-page .text-gray-600,
    .cash-page .text-gray-700 {
        color: var(--cash-muted) !important;
    }

    .cash-page .text-2xl,
    .cash-page .text-3xl,
    .cash-page .font-bold {
        font-variant-numeric: tabular-nums;
    }

    .cash-page .section-header {
        border-bottom: 1px solid rgba(255,255,255,.14);
    }

    .cash-page .section-header.sh-movs {
        background: linear-gradient(135deg, var(--cash-navy), var(--cash-navy-deep)) !important;
    }

    .cash-page .cat-item {
        gap: 14px;
        border-radius: 12px !important;
        background: var(--cash-surface) !important;
        border-color: var(--cash-border-soft) !important;
        transition: transform .15s ease, background .15s ease, border-color .15s ease !important;
    }

    .cash-page .cat-item:hover {
        background: var(--cash-surface-soft) !important;
        border-color: var(--cash-border) !important;
    }

    .cash-page .mov-item {
        border-bottom-color: var(--cash-border-soft) !important;
    }

    .cash-page .mov-item:hover {
        background: color-mix(in srgb, var(--cash-gold) 6%, #FFFFFF) !important;
    }

    .cash-page .badge-ingreso {
        background: var(--cash-green-bg) !important;
        color: #0F7048 !important;
        border-color: color-mix(in srgb, var(--cash-green) 28%, #D8EFE4) !important;
    }

    .cash-page .badge-gasto {
        background: var(--cash-red-bg) !important;
        color: #9D3028 !important;
        border-color: color-mix(in srgb, var(--cash-red) 28%, #F3D7D4) !important;
    }

    .cash-page .btn-ingreso,
    .cash-page .btn-gasto,
    .cash-page .btn-corte {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 9px 18px;
        border-radius: 10px;
        font-weight: 600;
        font-size: .875rem;
        border: none;
        cursor: pointer;
        text-decoration: none;
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease !important;
    }

    .cash-page .btn-ingreso:hover,
    .cash-page .btn-gasto:hover,
    .cash-page .btn-corte:hover {
        transform: translateY(-1px) !important;
        filter: saturate(1.04);
    }

    .cash-page .btn-ingreso {
        background: linear-gradient(135deg, var(--cash-green), color-mix(in srgb, var(--cash-green) 78%, #0B6B40)) !important;
        color: #FFFFFF !important;
        box-shadow: 0 8px 18px -10px color-mix(in srgb, var(--cash-green) 70%, transparent);
    }

    .cash-page .btn-gasto {
        background: linear-gradient(135deg, var(--cash-red), color-mix(in srgb, var(--cash-red) 78%, #8C1A14)) !important;
        color: #FFFFFF !important;
        box-shadow: 0 8px 18px -10px color-mix(in srgb, var(--cash-red) 70%, transparent);
    }

    .cash-page .btn-corte {
        background: rgba(255,255,255,.95) !important;
        border: 1px solid rgba(255,255,255,.65);
        color: var(--cash-navy-deep) !important;
    }

    .cash-page .balance-bar {
        background: linear-gradient(135deg, var(--cash-surface), var(--cash-surface-soft)) !important;
        border-color: var(--cash-border) !important;
    }

    .cash-page .quick-link {
        border-width: 1px !important;
        min-width: 0;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease !important;
    }

    .cash-page .quick-link:hover {
        background: var(--cash-surface-soft) !important;
        border-color: color-mix(in srgb, var(--cash-gold) 42%, var(--cash-border)) !important;
    }

    .cash-page .quick-link-arrow,
    .cash-page .cash-view-link i,
    .cash-page .cash-reservation-link .cash-reservation-arrow {
        opacity: .42;
        transform: translateX(-2px);
        transition: opacity .18s ease, transform .18s ease;
    }

    .cash-page .quick-link:hover .quick-link-arrow,
    .cash-page .quick-link:focus-visible .quick-link-arrow,
    .cash-page .cash-view-link:hover i,
    .cash-page .cash-view-link:focus-visible i,
    .cash-page .cash-reservation-link:hover .cash-reservation-arrow,
    .cash-page .cash-reservation-link:focus-visible .cash-reservation-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .cash-page .cash-view-link,
    .cash-page .cash-reservation-link {
        cursor: pointer;
        border-radius: 8px;
        text-decoration: none;
        transition: color .18s ease, background .18s ease;
    }

    .cash-page .cash-view-link:hover,
    .cash-page .cash-reservation-link:hover {
        text-decoration: underline;
        text-decoration-thickness: 2px;
        text-underline-offset: 3px;
    }

    .cash-page .cash-reservation-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 2px 4px;
        color: var(--cash-navy) !important;
        font-weight: 800;
    }

    .cash-page .cash-reservation-link:hover {
        color: color-mix(in srgb, var(--cash-gold) 76%, #3F2E12) !important;
        background: color-mix(in srgb, var(--cash-gold) 9%, transparent);
    }

    .cash-page .stat-card,
    .cash-page .metodo-card,
    .cash-page .cat-item,
    .cash-page .mov-item,
    .cash-page .caja-status {
        cursor: default;
    }

    .cash-page .btn-ingreso:active,
    .cash-page .btn-gasto:active,
    .cash-page .btn-corte:active,
    .cash-page .quick-link:active,
    .cash-page .cash-view-link:active,
    .cash-page .cash-reservation-link:active,
    #modalIngreso button:active,
    #modalGasto button:active {
        transform: translateY(0) scale(.98) !important;
    }

    .cash-page button,
    .cash-page a[href],
    #modalIngreso button,
    #modalGasto button,
    #modalIngreso select,
    #modalGasto select {
        cursor: pointer;
    }

    .cash-page .modal-input,
    #modalIngreso .modal-input,
    #modalGasto .modal-input {
        background: var(--cash-surface-soft) !important;
        border: 1px solid var(--cash-border) !important;
        border-radius: 10px !important;
        color: var(--cash-ink) !important;
        padding: 10px 14px !important;
    }

    .cash-page .modal-input:focus,
    #modalIngreso .modal-input:focus,
    #modalGasto .modal-input:focus {
        border-color: var(--cash-gold) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cash-gold) 22%, transparent) !important;
        background: #fff !important;
    }

    #modalIngreso,
    #modalGasto {
        background: color-mix(in srgb, var(--cash-navy-deep) 58%, rgba(10,14,24,.52)) !important;
    }

    #modalIngreso .bg-white,
    #modalGasto .bg-white {
        background: var(--cash-surface) !important;
    }

    .cash-page .modal-label,
    #modalIngreso .modal-label,
    #modalGasto .modal-label {
        color: var(--cash-navy-deep) !important;
    }

    .cash-page .modal-label-red,
    #modalGasto .modal-label-red {
        color: #8A302A !important;
    }

    .cash-page .text-emerald-600,
    .cash-page .text-emerald-700 { color: var(--cash-green) !important; }
    .cash-page .text-red-500,
    .cash-page .text-red-600 { color: var(--cash-red) !important; }
    .cash-page .text-blue-500,
    .cash-page .text-blue-600,
    .cash-page .text-blue-700 { color: var(--cash-blue) !important; }
    .cash-page .text-violet-500,
    .cash-page .text-violet-600,
    .cash-page .text-violet-700 { color: var(--cash-purple) !important; }

    .cash-page .custom-scroll::-webkit-scrollbar { width: 5px; }
    .cash-page .custom-scroll::-webkit-scrollbar-track { background: var(--cash-paper); border-radius: 10px; }
    .cash-page .custom-scroll::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--cash-gold), #fff 34%); border-radius: 10px; }
    .cash-page .custom-scroll::-webkit-scrollbar-thumb:hover { background: var(--cash-gold); }

    .cash-page a:focus-visible,
    .cash-page button:focus-visible,
    #modalIngreso button:focus-visible,
    #modalGasto button:focus-visible,
    #modalIngreso input:focus-visible,
    #modalIngreso textarea:focus-visible,
    #modalIngreso select:focus-visible,
    #modalGasto input:focus-visible,
    #modalGasto textarea:focus-visible,
    #modalGasto select:focus-visible {
        outline: none;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cash-gold) 30%, transparent) !important;
    }

    /* Layout upgrade: ledger rhythm, not just a color pass. */
    .cash-page .header-card {
        padding: 24px 28px !important;
    }

    .cash-page .header-card .flex.flex-wrap.gap-2\.5 {
        align-items: center;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child {
        display: grid !important;
        grid-template-columns: repeat(12, minmax(0, 1fr)) !important;
        gap: 14px !important;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child > .stat-card,
    .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child > .card-efectivo {
        grid-column: span 3;
        min-height: 148px;
        position: relative;
        overflow: hidden;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child > .stat-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 18px !important;
    }

    .cash-page .stat-card::before {
        content: '';
        position: absolute;
        top: 12px;
        right: 12px;
        width: 54px;
        height: 54px;
        border-radius: 14px;
        background: color-mix(in srgb, var(--cash-gold) 12%, transparent);
        opacity: .7;
        pointer-events: none;
    }

    .cash-page .stat-card .icon-circle {
        position: relative;
        z-index: 1;
        width: 42px !important;
        height: 42px !important;
        border-radius: 13px !important;
    }

    .cash-page .stat-card p:nth-child(2),
    .cash-page .card-efectivo p.text-3xl {
        font-family: var(--cash-serif);
        letter-spacing: 0;
        line-height: .95;
    }

    .cash-page .card-efectivo {
        display: flex;
        align-items: stretch;
    }

    .cash-page .card-efectivo > .flex {
        width: 100%;
        align-items: flex-start !important;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .bg-white {
        position: relative;
        overflow: hidden;
        padding: 18px !important;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .bg-white::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, color-mix(in srgb, var(--cash-gold) 7%, transparent), transparent 45%);
        pointer-events: none;
    }

    .cash-page > .max-w-7xl:nth-of-type(3) > .bg-white > * {
        position: relative;
        z-index: 1;
    }

    .cash-page .metodo-card {
        min-height: 168px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16px !important;
        background: var(--cash-surface) !important;
    }

    .cash-page .metodo-card .space-y-2 > .flex {
        padding: 7px 0;
        border-bottom: 1px solid var(--cash-border-soft);
    }

    .cash-page .metodo-card .space-y-2 > .flex:last-child {
        border-bottom: 0;
    }

    .cash-page .balance-bar {
        padding: 16px 18px !important;
        border-radius: 15px !important;
    }

    .cash-page > .max-w-7xl:nth-of-type(4) {
        align-items: start;
    }

    .cash-page > .max-w-7xl:nth-of-type(4) > .lg\:col-span-2 {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .cash-page .section-header,
    .cash-page .section-header.sh-movs {
        display: flex !important;
        align-items: center;
        gap: 10px;
        padding: 13px 18px !important;
        min-height: 48px;
        font-weight: 700;
        font-size: .92rem;
        background: var(--cash-surface-soft) !important;
        color: var(--cash-navy) !important;
        border-bottom: 1px solid var(--cash-border) !important;
    }

    .cash-page .section-header .bg-white\/20 {
        background: color-mix(in srgb, var(--cash-gold) 14%, var(--cash-surface)) !important;
        color: var(--cash-gold) !important;
        border: 1px solid var(--cash-border);
    }

    .cash-page .section-header i {
        color: color-mix(in srgb, var(--cash-gold) 78%, #3F2E12) !important;
    }

    .cash-page .section-header a {
        color: var(--cash-muted) !important;
    }

    .cash-page .cat-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 66px;
        padding: 12px !important;
    }

    .cash-page .cat-item > div:first-child {
        min-width: 0;
    }

    .cash-page .cat-item > div:first-child p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cash-page .cat-item > p:last-child {
        flex-shrink: 0;
        margin-left: 12px;
        padding: 6px 9px;
        border-radius: 999px;
        background: var(--cash-surface-soft);
        border: 1px solid var(--cash-border-soft);
        white-space: nowrap;
    }

    .cash-page .mov-item {
        margin: 10px 12px;
        padding: 12px !important;
        border: 1px solid var(--cash-border-soft) !important;
        border-radius: 14px;
        background: var(--cash-surface);
        box-shadow: 0 1px 2px color-mix(in srgb, var(--cash-navy-deep) 4%, transparent);
    }

    .cash-page .mov-item:hover {
        border-color: var(--cash-border) !important;
    }

    .cash-page > .max-w-7xl:nth-of-type(5) > .bg-white {
        padding: 18px !important;
    }

    .cash-page .quick-link {
        min-height: 78px;
        align-items: center;
    }

    .cash-page .quick-link-icon {
        width: 38px !important;
        height: 38px !important;
        border-radius: 12px !important;
        border: 1px solid var(--cash-border-soft);
    }

    @media (max-width: 768px) {
        .cash-page {
            padding: 14px !important;
        }

        .cash-page .header-card {
            padding: 18px !important;
        }

        .cash-page .header-card h1 {
            font-size: 2rem !important;
        }

        .cash-page .header-card .ml-12 {
            margin-left: 0 !important;
        }

        .cash-page .header-card .flex.flex-wrap.gap-2\.5 {
            width: 100%;
        }

        .cash-page .btn-ingreso,
        .cash-page .btn-gasto,
        .cash-page .btn-corte {
            flex: 1 1 120px;
            justify-content: center;
            min-height: 44px;
            padding-left: 12px;
            padding-right: 12px;
        }

        .cash-page .balance-bar {
            align-items: flex-start !important;
        }

        .cash-page .balance-bar .text-right {
            width: 100%;
            text-align: left !important;
        }

        .cash-page .quick-link {
            padding: 13px;
            gap: 10px;
        }

        .cash-page .mov-item p,
        .cash-page .cat-item p,
        .cash-page .quick-link p {
            overflow-wrap: anywhere;
        }

        .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child > .stat-card,
        .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child > .card-efectivo {
            grid-column: span 1;
        }

        .cash-page > .max-w-7xl:nth-of-type(4) > .lg\:col-span-2 {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 480px) {
        .cash-page .grid.grid-cols-2 {
            grid-template-columns: 1fr !important;
        }

        .cash-page .metodo-card .flex.items-center.justify-between,
        .cash-page .cat-item {
            align-items: flex-start;
        }

        .cash-page .cat-item {
            flex-direction: column;
        }

        .cash-page .cat-item > p:last-child {
            margin-left: 0;
            margin-top: 8px;
        }

        .cash-page > .max-w-7xl:nth-of-type(3) > .grid:first-child {
            grid-template-columns: 1fr !important;
        }
    }

    /* Halo dorado sutil en la esquina superior derecha */
    .cash-page {
        background:
            radial-gradient(960px 420px at 86% -10%, color-mix(in srgb, var(--cash-gold) 7%, transparent), transparent 62%),
            linear-gradient(180deg, var(--cash-paper-2), var(--cash-paper) 58%, #F7F2EA) !important;
    }

    .cash-page h3,
    .cash-page h4,
    .cash-page .text-gray-800,
    .cash-page .stat-card p:nth-child(2),
    .cash-page .caja-status-meta .t,
    .cash-page .caja-status-meta .d b,
    .cash-page .caja-status-right .v,
    .cash-page .balance-bar h4,
    .cash-page .quick-link p:first-child,
    .cash-page .cat-item .font-semibold,
    .cash-page .mov-item .font-semibold {
        color: var(--cash-heading) !important;
    }

    .cash-page .header-card {
        background: linear-gradient(135deg, color-mix(in srgb, var(--cash-navy-deep) 94%, #000) 0%, var(--cash-navy-deep) 62%, color-mix(in srgb, var(--cash-navy) 72%, var(--cash-navy-deep)) 100%) !important;
        box-shadow: 0 18px 42px -28px rgba(17, 24, 39, .58) !important;
    }

    .cash-page .cash-header-icon,
    .cash-page .section-header .bg-white\/20 {
        background: color-mix(in srgb, var(--cash-gold) 18%, rgba(255,255,255,.12)) !important;
    }

    .cash-page .caja-status {
        background: linear-gradient(120deg, #FFFFFF, color-mix(in srgb, var(--cash-green) 6%, #FFFFFF)) !important;
        border-color: color-mix(in srgb, var(--cash-green) 18%, var(--cash-border)) !important;
        box-shadow: var(--cash-shadow) !important;
    }

    .cash-page .caja-status-ic {
        background: var(--cash-green) !important;
        box-shadow: 0 10px 22px -17px color-mix(in srgb, var(--cash-green) 76%, transparent) !important;
    }

    .cash-page .card-efectivo {
        background:
            radial-gradient(circle at 86% 18%, rgba(255,255,255,.12), transparent 28%),
            linear-gradient(135deg, color-mix(in srgb, var(--cash-navy) 76%, #111827), var(--cash-navy-deep)) !important;
        box-shadow: 0 18px 42px -28px rgba(17, 24, 39, .58) !important;
    }

    .cash-page .bg-white.rounded-xl.shadow-sm,
    .cash-page .stat-card,
    .cash-page .quick-link,
    .cash-page .metodo-card,
    .cash-page .cat-item,
    .cash-page .mov-item,
    .cash-page .balance-bar {
        background: var(--cash-surface) !important;
        border-color: var(--cash-border) !important;
        box-shadow: var(--cash-shadow) !important;
    }

    .cash-page .stat-card:hover,
    .cash-page .quick-link:hover,
    .cash-page .metodo-card:hover,
    .cash-page .cat-item:hover,
    .cash-page .mov-item:hover {
        border-color: color-mix(in srgb, var(--cash-gold) 34%, var(--cash-border)) !important;
        box-shadow: 0 16px 34px -24px rgba(17, 24, 39, .38) !important;
    }

    .cash-page .stat-card.card-inicial::after {
        background: var(--cash-gold) !important;
    }

    .cash-page .stat-card::before {
        background: color-mix(in srgb, var(--cash-gold) 7%, transparent) !important;
    }

    .cash-page .icon-circle.green-ic {
        background: color-mix(in srgb, var(--cash-gold) 10%, #FFFFFF) !important;
        color: color-mix(in srgb, var(--cash-gold) 78%, var(--cash-heading)) !important;
    }

    .cash-page .metodo-card.mc-efectivo,
    .cash-page .metodo-card.mc-tarjeta,
    .cash-page .metodo-card.mc-transfer {
        background: var(--cash-surface) !important;
    }

    .cash-page .section-header,
    .cash-page .section-header.sh-movs {
        color: var(--cash-heading) !important;
        background: linear-gradient(180deg, var(--cash-surface), var(--cash-surface-soft)) !important;
    }

    .cash-page .section-header a,
    .cash-page .text-gray-400,
    .cash-page .text-gray-500,
    .cash-page .text-gray-600,
    .cash-page .text-gray-700,
    .cash-page .cash-meta-pill {
        color: var(--cash-muted) !important;
    }

    .cash-page .cash-meta-pill {
        background: rgba(255,255,255,.10) !important;
        border-color: rgba(255,255,255,.15) !important;
    }

    .cash-page .header-card .cash-meta-pill {
        background: rgba(255,255,255,.20) !important;
        border-color: rgba(255,255,255,.36) !important;
        color: var(--cash-on-brand) !important;
        font-weight: 800;
        letter-spacing: 0;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.22),
            0 8px 20px -18px rgba(0,0,0,.65);
        text-shadow: 0 1px 1px rgba(0,0,0,.22);
        backdrop-filter: blur(6px);
    }

    .cash-page .header-card .cash-meta-pill i {
        color: var(--cash-on-brand) !important;
        opacity: .98;
    }

    .cash-page .btn-corte {
        color: var(--cash-heading) !important;
        border: 1px solid rgba(255,255,255,.42) !important;
    }

    .cash-page .quick-link-icon {
        background: color-mix(in srgb, var(--cash-gold) 9%, #FFFFFF) !important;
    }

    .cash-page .cash-reservation-link {
        color: var(--cash-heading) !important;
    }

    .cash-page .cash-reservation-link:hover,
    .cash-page .cash-view-link:hover {
        color: color-mix(in srgb, var(--cash-gold) 78%, var(--cash-heading)) !important;
    }

    .cash-page .modal-input,
    #modalIngreso .modal-input,
    #modalGasto .modal-input {
        color: var(--cash-heading) !important;
        background: var(--cash-surface) !important;
        border-color: var(--cash-border) !important;
    }

    .cash-page .modal-label,
    #modalIngreso .modal-label,
    #modalGasto .modal-label {
        color: var(--cash-heading) !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .cash-page *,
        #modalIngreso *,
        #modalGasto * {
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<style id="cash-command-center-redesign">
    .cash-page.cash-command-page {
        --cash-cream: color-mix(in srgb, var(--cash-gold) 5%, #fbfaf6);
        --cash-panel: rgba(255, 255, 255, .94);
        --cash-line: color-mix(in srgb, var(--cash-navy) 10%, #e9dfca);
        --cash-soft-line: color-mix(in srgb, var(--cash-navy) 6%, #efe8d9);
        --cash-command-shadow: 0 22px 50px -44px color-mix(in srgb, var(--cash-navy-deep) 72%, transparent);
        --cash-radius-lg: 14px;
        --cash-radius-md: 10px;
        min-height: 100dvh;
        padding: clamp(14px, 1.7vw, 28px) !important;
        background:
            linear-gradient(90deg, color-mix(in srgb, var(--cash-navy) 4%, transparent) 1px, transparent 1px),
            linear-gradient(180deg, #fffdfa 0%, var(--cash-cream) 48%, color-mix(in srgb, var(--cash-gold) 10%, #f5efe3) 100%) !important;
        background-size: 28px 28px, auto;
    }

    .cash-page.cash-command-page::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        background-image:
            radial-gradient(circle at 18% 8%, rgba(255,255,255,.72), transparent 22%),
            linear-gradient(135deg, transparent 0 49%, color-mix(in srgb, var(--cash-gold) 12%, transparent) 49% 51%, transparent 51% 100%);
        background-size: auto, 18px 18px;
        opacity: .45;
        z-index: 0;
    }

    .cash-page.cash-command-page > .max-w-7xl {
        position: relative;
        z-index: 1;
        max-width: min(1680px, calc(100vw - 34px)) !important;
        width: 100%;
    }

    .cash-command-page .cash-hero-wrap,
    .cash-command-page .cash-status-wrap,
    .cash-command-page .cash-summary-wrap,
    .cash-command-page .cash-activity-wrap,
    .cash-command-page .cash-shortcuts-wrap {
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .cash-command-page .cash-hero-wrap { margin-bottom: 14px !important; }
    .cash-command-page .cash-status-wrap { margin-bottom: 14px !important; }
    .cash-command-page .cash-summary-wrap { margin-bottom: 16px !important; }
    .cash-command-page .cash-shortcuts-wrap { margin-top: 16px !important; }

    .cash-command-page .header-card {
        position: relative;
        overflow: hidden;
        padding: 0 !important;
        border-radius: var(--cash-radius-lg) !important;
        background:
            linear-gradient(90deg, var(--cash-panel), color-mix(in srgb, var(--cash-gold) 5%, #fff) 64%, color-mix(in srgb, var(--cash-navy) 7%, #fff)) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: var(--cash-command-shadow) !important;
    }

    .cash-command-page .header-card::before {
        content: '' !important;
        position: absolute !important;
        inset: 0 auto 0 0 !important;
        width: 9px !important;
        height: auto !important;
        border-radius: 0 !important;
        opacity: 1 !important;
        background: linear-gradient(180deg, var(--cash-green), var(--cash-gold), var(--cash-red)) !important;
    }

    .cash-command-page .header-card > .flex {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 22px !important;
        padding: clamp(18px, 2vw, 28px) clamp(18px, 2.2vw, 32px) clamp(18px, 2vw, 28px) clamp(24px, 2.8vw, 42px) !important;
    }

    .cash-command-page .cash-header-icon {
        width: 42px !important;
        height: 42px !important;
        border-radius: 12px !important;
        color: var(--cash-on-brand) !important;
        background: linear-gradient(135deg, var(--cash-navy), color-mix(in srgb, var(--cash-navy) 62%, var(--cash-gold))) !important;
        border: 1px solid color-mix(in srgb, var(--cash-gold) 35%, transparent) !important;
        box-shadow: 0 12px 22px -18px color-mix(in srgb, var(--cash-navy) 80%, transparent) !important;
    }

    .cash-command-page .header-card h1 {
        color: var(--cash-heading) !important;
        font-family: var(--cash-serif);
        font-size: clamp(2rem, 2.45vw, 3rem) !important;
        font-weight: 700 !important;
        line-height: .92 !important;
    }

    .cash-command-page .header-card p {
        max-width: 620px !important;
        margin-top: 8px !important;
        color: color-mix(in srgb, var(--cash-muted) 86%, var(--cash-heading)) !important;
        font-size: .88rem !important;
        line-height: 1.55 !important;
    }

    .cash-command-page .header-card .ml-12 {
        margin-left: 55px !important;
    }

    .cash-command-page .header-card .cash-meta-pill {
        min-height: 30px;
        border-radius: 8px !important;
        color: var(--cash-heading) !important;
        background: rgba(255,255,255,.66) !important;
        border: 1px solid var(--cash-soft-line) !important;
        font-weight: 800 !important;
        box-shadow: none !important;
        text-shadow: none !important;
        backdrop-filter: blur(10px);
    }

    .cash-command-page .header-card .cash-meta-pill i {
        color: color-mix(in srgb, var(--cash-gold) 80%, var(--cash-navy)) !important;
    }

    .cash-command-page .header-card .flex.flex-wrap.gap-2\.5 {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(112px, 1fr));
        min-width: min(520px, 42vw);
        gap: 8px !important;
    }

    .cash-command-page .btn-ingreso,
    .cash-command-page .btn-gasto,
    .cash-command-page .btn-corte {
        min-height: 48px !important;
        justify-content: center;
        border-radius: 10px !important;
        padding: 11px 15px !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
        box-shadow: none !important;
        border: 1px solid transparent !important;
    }

    .cash-command-page .btn-ingreso {
        background: var(--cash-green) !important;
        color: #fff !important;
    }

    .cash-command-page .btn-gasto {
        background: color-mix(in srgb, var(--cash-red) 90%, #7a1f18) !important;
        color: #fff !important;
    }

    .cash-command-page .btn-corte {
        background: var(--cash-navy) !important;
        color: var(--cash-on-brand) !important;
        border-color: color-mix(in srgb, var(--cash-gold) 38%, var(--cash-navy)) !important;
    }

    .cash-command-page .btn-ingreso:hover,
    .cash-command-page .btn-gasto:hover,
    .cash-command-page .btn-corte:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 16px 26px -20px rgba(17,24,39,.72) !important;
    }

    .cash-command-page .caja-status {
        display: grid !important;
        grid-template-columns: auto minmax(0, 1fr) minmax(260px, .38fr);
        gap: 18px !important;
        align-items: stretch !important;
        padding: 18px !important;
        border-radius: var(--cash-radius-lg) !important;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--cash-navy-deep) 94%, #05070d), color-mix(in srgb, var(--cash-navy) 88%, #111827)) !important;
        border: 1px solid color-mix(in srgb, var(--cash-gold) 24%, rgba(255,255,255,.18)) !important;
        border-left: 0 !important;
        box-shadow: var(--cash-command-shadow) !important;
    }

    .cash-command-page .caja-status-ic {
        width: 58px !important;
        height: auto !important;
        min-height: 70px;
        border-radius: 12px !important;
        background: color-mix(in srgb, var(--cash-on-brand) 13%, transparent) !important;
        color: var(--cash-on-brand) !important;
        border: 1px solid color-mix(in srgb, var(--cash-on-brand) 18%, transparent);
        box-shadow: none !important;
    }

    .cash-command-page .caja-status-meta {
        align-self: center;
        min-width: 0;
    }

    .cash-command-page .caja-status-meta .t {
        color: var(--cash-on-brand) !important;
        font-family: var(--cash-sans);
        font-size: clamp(1.4rem, 1.8vw, 2.05rem) !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    .cash-command-page .caja-status-meta .d {
        color: color-mix(in srgb, var(--cash-on-brand) 72%, transparent) !important;
        margin-top: 9px !important;
        gap: 10px !important;
    }

    .cash-command-page .caja-status-meta .d b {
        color: var(--cash-on-brand) !important;
    }

    .cash-command-page .caja-pulse {
        color: var(--cash-on-brand) !important;
        background: rgba(31,157,99,.24) !important;
        border: 1px solid rgba(82,221,153,.26);
        border-radius: 8px !important;
    }

    .cash-command-page .caja-status-right {
        margin-left: 0 !important;
        padding: 14px 16px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--cash-on-brand) 12%, transparent);
        border: 1px solid color-mix(in srgb, var(--cash-on-brand) 18%, transparent);
        text-align: left !important;
    }

    .cash-command-page .caja-status-right .l {
        color: color-mix(in srgb, var(--cash-on-brand) 66%, transparent) !important;
        letter-spacing: .08em !important;
    }

    .cash-command-page .caja-status-right .v {
        color: var(--cash-on-brand) !important;
        font-family: var(--cash-sans);
        font-size: clamp(1.55rem, 2vw, 2.35rem) !important;
        font-weight: 950 !important;
    }

    .cash-command-page .cash-summary-wrap > .grid:first-child {
        display: grid !important;
        grid-template-columns: 1.1fr 1.1fr 1.1fr 1.35fr !important;
        gap: 12px !important;
        margin-bottom: 12px !important;
    }

    .cash-page.cash-command-page .cash-summary-wrap > .grid:first-child {
        grid-template-columns: 1.1fr 1.1fr 1.1fr 1.35fr !important;
    }

    .cash-page.cash-command-page .cash-summary-wrap > .grid:first-child > .stat-card,
    .cash-page.cash-command-page .cash-summary-wrap > .grid:first-child > .card-efectivo {
        grid-column: auto !important;
    }

    .cash-command-page .stat-card,
    .cash-command-page .card-efectivo {
        position: relative;
        min-height: 128px !important;
        border-radius: var(--cash-radius-md) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: none !important;
        overflow: hidden;
    }

    .cash-command-page .stat-card {
        padding: 18px 18px 17px !important;
        background: rgba(255,255,255,.86) !important;
    }

    .cash-command-page .stat-card::before {
        display: none !important;
    }

    .cash-command-page .stat-card::after {
        content: '' !important;
        position: absolute;
        inset: auto 14px 12px 14px;
        height: 3px;
        border-radius: 999px;
        opacity: 1 !important;
    }

    .cash-command-page .stat-card .flex {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 48px;
        gap: 16px !important;
        height: 100%;
        align-items: center !important;
    }

    .cash-command-page .stat-card .flex > div:first-child,
    .cash-command-page .card-efectivo .flex > div:first-child {
        min-width: 0;
    }

    .cash-command-page .stat-card p:first-child {
        font-size: .68rem !important;
        letter-spacing: .08em !important;
        color: color-mix(in srgb, var(--cash-muted) 86%, var(--cash-heading)) !important;
    }

    .cash-command-page .card-efectivo p:first-child {
        font-size: .68rem !important;
        letter-spacing: .08em !important;
        color: color-mix(in srgb, var(--cash-on-brand) 88%, transparent) !important;
        text-shadow: 0 1px 1px rgba(0,0,0,.22);
    }

    .cash-command-page .stat-card p:nth-child(2),
    .cash-command-page .card-efectivo p.text-3xl {
        margin-top: 5px;
        font-family: var(--cash-sans);
        font-size: clamp(1.55rem, 1.9vw, 2.05rem) !important;
        font-weight: 950 !important;
        line-height: 1 !important;
        letter-spacing: 0 !important;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }

    .cash-command-page .stat-help {
        max-width: 26ch;
        margin-top: 10px !important;
        color: var(--cash-muted) !important;
        font-size: .72rem !important;
        line-height: 1.35 !important;
    }

    .cash-command-page .icon-circle,
    .cash-command-page .card-efectivo .bg-white\/15 {
        width: 46px !important;
        height: 46px !important;
        min-width: 46px !important;
        min-height: 46px !important;
        border-radius: 12px !important;
        display: inline-grid !important;
        place-items: center !important;
        align-self: center !important;
        justify-self: end !important;
        box-shadow: none !important;
    }

    .cash-command-page .card-efectivo .bg-white\/15 {
        background: color-mix(in srgb, var(--cash-on-brand) 16%, transparent) !important;
        border: 1px solid color-mix(in srgb, var(--cash-on-brand) 20%, transparent) !important;
        color: var(--cash-on-brand) !important;
    }

    .cash-command-page .icon-circle i,
    .cash-command-page .card-efectivo .bg-white\/15 i {
        display: block !important;
        line-height: 1 !important;
        transform: translateY(.5px);
        color: currentColor !important;
    }

    .cash-command-page .icon-circle.green-ic {
        background: color-mix(in srgb, var(--cash-gold) 14%, #fff) !important;
        color: color-mix(in srgb, var(--cash-gold) 78%, var(--cash-heading)) !important;
    }

    .cash-command-page .icon-circle.emerald-ic {
        background: color-mix(in srgb, var(--cash-green) 12%, #fff) !important;
        color: var(--cash-green) !important;
    }

    .cash-command-page .icon-circle.red-ic {
        background: color-mix(in srgb, var(--cash-red) 12%, #fff) !important;
        color: var(--cash-red) !important;
    }

    .cash-command-page .card-efectivo {
        display: flex !important;
        align-items: stretch !important;
        padding: 18px !important;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--cash-navy) 92%, #06110d), color-mix(in srgb, var(--cash-green) 34%, var(--cash-navy-deep))) !important;
        border-color: color-mix(in srgb, var(--cash-green) 28%, var(--cash-line)) !important;
        box-shadow: 0 20px 38px -32px color-mix(in srgb, var(--cash-green) 70%, transparent) !important;
    }

    .cash-command-page .card-efectivo::before {
        content: '' !important;
        position: absolute !important;
        inset: 0 !important;
        width: auto !important;
        height: auto !important;
        background:
            linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent),
            repeating-linear-gradient(90deg, rgba(255,255,255,.06) 0 1px, transparent 1px 16px) !important;
        opacity: .42 !important;
    }

    .cash-command-page .card-efectivo p.text-3xl {
        color: var(--cash-on-brand) !important;
        text-shadow: 0 1px 2px rgba(0,0,0,.24);
    }

    .cash-command-page .card-efectivo p.text-white\/60 {
        color: color-mix(in srgb, var(--cash-on-brand) 80%, transparent) !important;
        text-shadow: 0 1px 1px rgba(0,0,0,.2);
    }

    .cash-command-page .card-efectivo > .flex {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) 48px;
        gap: 16px !important;
        width: 100%;
        align-items: center !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white {
        position: relative;
        overflow: hidden;
        padding: 0 !important;
        border-radius: var(--cash-radius-lg) !important;
        background: rgba(255,255,255,.9) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: var(--cash-command-shadow) !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white > h3 {
        padding: 17px 18px 12px !important;
        margin: 0 !important;
        border-bottom: 1px solid var(--cash-soft-line);
        font-size: .92rem !important;
        letter-spacing: 0 !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white > h3 > div {
        background: var(--cash-navy) !important;
        color: var(--cash-on-brand) !important;
        border-radius: 8px !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white > h3 i {
        color: var(--cash-on-brand) !important;
    }

    .cash-command-page .method-bar {
        height: 16px !important;
        margin: 16px 18px 9px !important;
        border-radius: 5px !important;
        background: color-mix(in srgb, var(--cash-navy) 8%, #fff) !important;
        box-shadow: inset 0 0 0 1px var(--cash-soft-line);
    }

    .cash-command-page .method-bar-legend {
        margin: 0 18px 16px !important;
        padding-bottom: 14px;
        border-bottom: 1px dashed var(--cash-soft-line);
    }

    .cash-command-page .cash-summary-wrap > .bg-white > .stat-help {
        margin: 14px 18px 0 !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white > .grid {
        padding: 0 18px 18px !important;
        gap: 12px !important;
    }

    .cash-command-page .metodo-card {
        min-height: 162px !important;
        padding: 15px !important;
        border-radius: var(--cash-radius-md) !important;
        background: #fff !important;
        border: 1px solid var(--cash-soft-line) !important;
        box-shadow: none !important;
    }

    .cash-command-page .metodo-card.mc-efectivo { border-left: 5px solid var(--cash-green) !important; }
    .cash-command-page .metodo-card.mc-tarjeta { border-left: 5px solid var(--cash-blue) !important; }
    .cash-command-page .metodo-card.mc-transfer { border-left: 5px solid var(--cash-purple) !important; }

    .cash-command-page .metodo-card .space-y-2 > .flex {
        padding: 8px 0 !important;
        border-bottom: 1px solid var(--cash-soft-line) !important;
    }

    .cash-command-page .balance-bar {
        margin: 0 18px 18px;
        padding: 18px !important;
        border-radius: var(--cash-radius-md) !important;
        background: color-mix(in srgb, var(--cash-gold) 7%, #fff) !important;
        border: 1px solid color-mix(in srgb, var(--cash-gold) 26%, var(--cash-soft-line)) !important;
        box-shadow: none !important;
    }

    .cash-command-page .cash-activity-wrap {
        display: grid !important;
        grid-template-columns: minmax(0, 1.28fr) minmax(360px, .72fr) !important;
        gap: 16px !important;
        align-items: start !important;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px !important;
        grid-column: auto !important;
        align-items: stretch !important;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > * {
        margin-top: 0 !important;
    }

    .cash-command-page .cash-activity-wrap > .bg-white,
    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .bg-white,
    .cash-command-page .cash-shortcuts-wrap > .bg-white {
        border-radius: var(--cash-radius-lg) !important;
        background: rgba(255,255,255,.92) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: var(--cash-command-shadow) !important;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .bg-white {
        min-height: 0 !important;
        height: 100%;
        display: flex !important;
        flex-direction: column;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .bg-white > .p-4 {
        padding: 16px !important;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .bg-white .text-center.py-8 {
        flex: 1;
        min-height: 128px;
        padding-top: 26px !important;
        padding-bottom: 24px !important;
        display: grid;
        place-items: center;
        align-content: center;
    }

    .cash-command-page .section-header,
    .cash-command-page .section-header.sh-movs {
        min-height: 56px !important;
        padding: 15px 17px !important;
        color: var(--cash-heading) !important;
        background: #fff !important;
        border-bottom: 1px solid var(--cash-soft-line) !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    .cash-command-page .section-header::after {
        content: '';
        flex: none;
        width: 44px;
        height: 2px;
        margin-left: auto;
        border-radius: 999px;
        background: color-mix(in srgb, var(--cash-gold) 62%, var(--cash-navy));
        opacity: .85;
    }

    .cash-command-page .section-header.sh-movs::after {
        display: none;
    }

    .cash-command-page .section-header .bg-white\/20 {
        width: 34px !important;
        height: 34px !important;
        border-radius: 9px !important;
        background: color-mix(in srgb, var(--cash-navy) 9%, #fff) !important;
        color: var(--cash-navy) !important;
        border: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .section-header .bg-white\/20 i,
    .cash-command-page .section-header i {
        color: currentColor !important;
    }

    .cash-command-page .section-header .cash-view-link {
        margin-left: auto;
        padding: 7px 10px;
        border-radius: 8px;
        background: color-mix(in srgb, var(--cash-navy) 6%, #fff);
        color: var(--cash-heading) !important;
        text-decoration: none !important;
        border: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .cat-item {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 12px !important;
        padding: 12px !important;
        border-radius: 10px !important;
        background: #fff !important;
        border: 1px solid var(--cash-soft-line) !important;
        box-shadow: none !important;
    }

    .cash-command-page .cat-item:hover,
    .cash-command-page .mov-item:hover,
    .cash-command-page .quick-link:hover,
    .cash-command-page .metodo-card:hover,
    .cash-command-page .stat-card:hover {
        transform: translateY(-1px) !important;
        border-color: color-mix(in srgb, var(--cash-gold) 34%, var(--cash-line)) !important;
        box-shadow: 0 16px 34px -30px rgba(17,24,39,.5) !important;
    }

    .cash-command-page .cat-item > div:first-child {
        min-width: 0;
    }

    .cash-command-page .cat-item > p:last-child {
        margin: 0 !important;
        padding: 8px 10px !important;
        border-radius: 8px !important;
        background: color-mix(in srgb, currentColor 7%, #fff) !important;
        border: 1px solid var(--cash-soft-line) !important;
        white-space: nowrap;
    }

    .cash-command-page .custom-scroll {
        max-height: clamp(390px, 48vh, 520px) !important;
        padding: 8px 0 10px;
    }

    .cash-command-page .mov-item {
        margin: 8px 12px !important;
        padding: 13px !important;
        border: 1px solid var(--cash-soft-line) !important;
        border-radius: 11px !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .cash-command-page .badge-ingreso,
    .cash-command-page .badge-gasto {
        border-radius: 7px !important;
        border: 1px solid currentColor !important;
        background: #fff !important;
    }

    .cash-command-page .cash-reservation-link {
        padding: 6px 8px !important;
        border-radius: 8px !important;
        background: color-mix(in srgb, var(--cash-gold) 9%, #fff);
        color: var(--cash-heading) !important;
        text-decoration: none !important;
    }

    .cash-command-page .cash-shortcuts-wrap > .bg-white {
        padding: 0 !important;
        overflow: hidden;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .cash-shortcuts-wrap {
        grid-column: 1 / -1;
        width: 100%;
        max-width: none !important;
        margin: 0 !important;
    }

    .cash-command-page .cash-shortcuts-wrap h3 {
        margin: 0 !important;
        padding: 14px 18px !important;
        border-bottom: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .cash-shortcuts-wrap h3 > div {
        background: var(--cash-navy) !important;
        color: var(--cash-on-brand) !important;
    }

    .cash-command-page .cash-shortcuts-wrap h3 i {
        color: var(--cash-on-brand) !important;
    }

    .cash-command-page .cash-shortcuts-wrap .grid {
        padding: 14px !important;
        gap: 12px !important;
        align-items: stretch !important;
    }

    .cash-command-page .cash-movements-panel {
        min-height: 0;
        overflow: hidden;
        border-radius: var(--cash-radius-lg) !important;
        background: rgba(255,255,255,.94) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: var(--cash-command-shadow) !important;
    }

    .cash-command-page .cash-movements-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 16px;
        background: #fff;
        border-bottom: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .cash-movements-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        color: var(--cash-navy);
        background: color-mix(in srgb, var(--cash-navy) 8%, #fff);
        border: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .cash-movements-title {
        min-width: 0;
        flex: 1;
    }

    .cash-command-page .cash-movements-title h3 {
        margin: 0;
        color: var(--cash-heading) !important;
        font-size: .95rem;
        line-height: 1.15;
        font-weight: 900;
    }

    .cash-command-page .cash-movements-title p {
        margin: 4px 0 0;
        color: var(--cash-muted);
        font-size: .74rem;
        line-height: 1.25;
        font-weight: 650;
    }

    .cash-command-page .cash-movements-head .cash-view-link {
        flex: 0 0 auto;
        margin-left: auto;
        padding: 8px 10px;
        border-radius: 9px;
        color: var(--cash-heading) !important;
        background: color-mix(in srgb, var(--cash-navy) 5%, #fff);
        border: 1px solid var(--cash-soft-line);
        text-decoration: none !important;
        font-size: .72rem;
        font-weight: 850;
    }

    .cash-command-page .cash-movements-list {
        max-height: clamp(420px, 54vh, 620px) !important;
        padding: 10px;
        overflow-y: auto;
    }

    .cash-command-page .cash-movement-item {
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: start;
        padding: 12px;
        border: 1px solid var(--cash-soft-line);
        border-radius: 12px;
        background: #fff;
    }

    .cash-command-page .cash-movement-item + .cash-movement-item {
        margin-top: 9px;
    }

    .cash-command-page .cash-movement-item:hover {
        border-color: color-mix(in srgb, var(--cash-gold) 32%, var(--cash-line));
        background: color-mix(in srgb, var(--cash-gold) 3%, #fff);
    }

    .cash-command-page .cash-movement-mark {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        background: var(--cash-green-bg);
        color: var(--cash-green);
        border: 1px solid color-mix(in srgb, var(--cash-green) 20%, transparent);
    }

    .cash-command-page .cash-movement-item.is-expense .cash-movement-mark {
        background: var(--cash-red-bg);
        color: var(--cash-red);
        border-color: color-mix(in srgb, var(--cash-red) 20%, transparent);
    }

    .cash-command-page .cash-movement-main {
        min-width: 0;
    }

    .cash-command-page .cash-movement-top,
    .cash-command-page .cash-movement-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
    }

    .cash-command-page .cash-movement-type,
    .cash-command-page .cash-movement-method,
    .cash-command-page .cash-movement-time,
    .cash-command-page .cash-movement-tag,
    .cash-command-page .cash-movement-edited {
        min-height: 24px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        border: 1px solid var(--cash-soft-line);
        background: color-mix(in srgb, var(--cash-navy) 4%, #fff);
        color: var(--cash-muted);
        font-size: .68rem;
        line-height: 1;
        font-weight: 800;
        white-space: nowrap;
    }

    .cash-command-page .cash-movement-type {
        color: var(--cash-green);
        background: var(--cash-green-bg);
        border-color: color-mix(in srgb, var(--cash-green) 22%, transparent);
    }

    .cash-command-page .cash-movement-item.is-expense .cash-movement-type {
        color: var(--cash-red);
        background: var(--cash-red-bg);
        border-color: color-mix(in srgb, var(--cash-red) 22%, transparent);
    }

    .cash-command-page .cash-movement-desc {
        margin: 9px 0 8px;
        color: var(--cash-heading);
        font-size: .9rem;
        line-height: 1.35;
        font-weight: 850;
        overflow-wrap: anywhere;
    }

    .cash-command-page .cash-movement-meta {
        color: var(--cash-muted);
        font-size: .72rem;
        line-height: 1.25;
    }

    .cash-command-page .cash-movement-amount {
        align-self: center;
        padding: 8px 10px;
        border-radius: 10px;
        background: var(--cash-green-bg);
        color: var(--cash-green);
        font-size: .96rem;
        line-height: 1;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .cash-command-page .cash-movement-item.is-expense .cash-movement-amount {
        background: var(--cash-red-bg);
        color: var(--cash-red);
    }

    .cash-command-page .cash-movement-empty {
        min-height: 220px;
        display: grid;
        place-items: center;
        align-content: center;
        gap: 10px;
        padding: 28px 20px;
        color: var(--cash-muted);
        text-align: center;
    }

    .cash-command-page .cash-movement-empty i {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        color: var(--cash-navy);
        background: color-mix(in srgb, var(--cash-navy) 8%, #fff);
        border: 1px solid var(--cash-soft-line);
        font-size: 1rem;
    }

    @media (max-width: 720px) {
        .cash-command-page .cash-movements-head {
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .cash-command-page .cash-movements-head .cash-view-link {
            width: 100%;
            justify-content: center;
            margin-left: 48px;
        }

        .cash-command-page .cash-movement-item {
            grid-template-columns: 38px minmax(0, 1fr);
        }

        .cash-command-page .cash-movement-amount {
            grid-column: 2;
            justify-self: start;
        }
    }

    .cash-command-page .quick-link {
        height: auto !important;
        min-height: 86px !important;
        padding: 14px !important;
        border-radius: 10px !important;
        background: #fff !important;
        border: 1px solid var(--cash-soft-line) !important;
        box-shadow: none !important;
        display: grid !important;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 12px !important;
    }

    .cash-command-page .quick-link-icon {
        width: 44px !important;
        height: 44px !important;
        min-width: 44px !important;
        min-height: 44px !important;
        border-radius: 10px !important;
        background: color-mix(in srgb, var(--cash-gold) 10%, #fff) !important;
        border: 1px solid var(--cash-soft-line) !important;
        display: grid !important;
        place-items: center !important;
        align-self: center !important;
    }

    .cash-command-page .quick-link-icon i {
        line-height: 1 !important;
    }

    .cash-command-page .quick-link > div:nth-child(2) {
        min-width: 0;
    }

    .cash-command-page .quick-link p {
        margin: 0 !important;
        line-height: 1.25 !important;
    }

    .cash-command-page .quick-link p:first-child {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    #modalIngreso,
    #modalGasto {
        background: rgba(13, 18, 29, .62) !important;
        backdrop-filter: blur(10px);
    }

    #modalIngreso > .flex,
    #modalGasto > .flex {
        align-items: center !important;
        padding: 18px !important;
    }

    #modalIngreso .bg-white,
    #modalGasto .bg-white {
        border-radius: 14px !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: 0 28px 70px -35px rgba(0,0,0,.55) !important;
        overflow: hidden;
    }

    #modalIngreso .bg-gradient-to-r,
    #modalGasto .bg-gradient-to-r {
        background: #fff !important;
        color: var(--cash-heading) !important;
        border-bottom: 1px solid var(--cash-soft-line);
        border-radius: 0 !important;
    }

    #modalIngreso .bg-gradient-to-r h3,
    #modalGasto .bg-gradient-to-r h3,
    #modalIngreso .bg-gradient-to-r i,
    #modalGasto .bg-gradient-to-r i,
    #modalIngreso .bg-gradient-to-r button,
    #modalGasto .bg-gradient-to-r button {
        color: var(--cash-heading) !important;
    }

    #modalIngreso .bg-gradient-to-r h3 > div,
    #modalGasto .bg-gradient-to-r h3 > div {
        background: color-mix(in srgb, var(--cash-gold) 12%, #fff) !important;
        border: 1px solid var(--cash-soft-line);
    }

    #modalIngreso .modal-input,
    #modalGasto .modal-input {
        min-height: 42px;
        border-radius: 9px !important;
        background: color-mix(in srgb, var(--cash-gold) 3%, #fff) !important;
        border: 1px solid var(--cash-soft-line) !important;
    }

    @media (max-width: 1180px) {
        .cash-command-page .header-card > .flex {
            grid-template-columns: 1fr;
        }

        .cash-command-page .header-card .flex.flex-wrap.gap-2\.5 {
            min-width: 0;
            width: 100%;
        }

        .cash-command-page .cash-summary-wrap > .grid:first-child {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .cash-page.cash-command-page .cash-summary-wrap > .grid:first-child {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }

        .cash-command-page .cash-activity-wrap {
            grid-template-columns: 1fr !important;
        }

        .cash-command-page .cash-activity-wrap > .lg\:col-span-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .cash-page.cash-command-page {
            padding: 12px !important;
        }

        .cash-page.cash-command-page > .max-w-7xl {
            max-width: 100% !important;
        }

        .cash-command-page .header-card > .flex {
            padding: 18px !important;
        }

        .cash-command-page .header-card .ml-12 {
            margin-left: 0 !important;
        }

        .cash-command-page .header-card .flex.items-center.gap-3 {
            align-items: flex-start !important;
        }

        .cash-command-page .header-card .flex.flex-wrap.gap-2\.5 {
            grid-template-columns: 1fr !important;
        }

        .cash-command-page .btn-ingreso,
        .cash-command-page .btn-gasto,
        .cash-command-page .btn-corte {
            width: 100%;
        }

        .cash-command-page .caja-status {
            grid-template-columns: 1fr !important;
        }

        .cash-command-page .caja-status-ic {
            width: 46px !important;
            height: 46px !important;
            min-height: 46px;
        }

        .cash-command-page .cash-summary-wrap > .grid:first-child,
        .cash-command-page .cash-summary-wrap > .bg-white > .grid,
        .cash-command-page .cash-activity-wrap > .lg\:col-span-2,
        .cash-command-page .cash-shortcuts-wrap .grid {
            grid-template-columns: 1fr !important;
        }

        .cash-page.cash-command-page .cash-summary-wrap > .grid:first-child {
            grid-template-columns: 1fr !important;
        }

        .cash-command-page .stat-card,
        .cash-command-page .card-efectivo,
        .cash-command-page .metodo-card {
            min-height: auto !important;
        }

        .cash-command-page .balance-bar {
            margin: 0 14px 14px;
            align-items: flex-start !important;
        }

        .cash-command-page .balance-bar .text-right {
            width: 100%;
            text-align: left !important;
        }

        .cash-command-page .cat-item {
            grid-template-columns: 1fr !important;
        }

        .cash-command-page .cat-item > p:last-child {
            justify-self: flex-start;
        }

        .cash-command-page .mov-item > .flex {
            flex-direction: column;
        }

        .cash-command-page .mov-item > .flex > p {
            align-self: flex-start;
        }

        #modalIngreso .grid.grid-cols-2,
        #modalGasto .grid.grid-cols-2 {
            grid-template-columns: 1fr !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .cash-command-page *,
        #modalIngreso *,
        #modalGasto * {
            transition: none !important;
            animation: none !important;
        }
    }
</style>

<style id="cash-modal-clean-redesign">
    #modalIngreso,
    #modalGasto {
        --cash-modal-surface: var(--brand-surface, #FFFEFB);
        --cash-modal-surface-soft: var(--brand-surface-soft, #F7F3EA);
        --cash-modal-border: color-mix(in srgb, var(--brand-border, #DED8CC) 82%, transparent);
        --cash-modal-text: var(--brand-text, #202421);
        --cash-modal-muted: var(--brand-muted, #68706A);
        --cash-modal-action: var(--brand-action-bg, #223126);
        --cash-modal-action-hover: var(--brand-action-bg-hover, #17221B);
        --cash-modal-on-action: var(--brand-action-text, #FFFEFB);
        --cash-modal-income: #14784B;
        --cash-modal-expense: #A33A31;
        --cash-modal-danger-soft: #FFF3F1;
        background: rgba(16, 20, 18, .68) !important;
        backdrop-filter: none !important;
        overflow-y: auto;
        padding: 16px;
    }

    #modalIngreso .cash-modal-shell,
    #modalGasto .cash-modal-shell {
        min-height: 100%;
        display: grid;
        place-items: center;
    }

    #modalIngreso .cash-modal-dialog,
    #modalGasto .cash-modal-dialog {
        width: min(100%, 640px) !important;
        max-height: calc(100svh - 32px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 18px !important;
        background: var(--cash-modal-surface) !important;
        border: 1px solid var(--cash-modal-border) !important;
        box-shadow: 0 30px 80px -44px rgba(10, 13, 12, .7) !important;
        color: var(--cash-modal-text);
    }

    #modalIngreso .cash-modal-header,
    #modalGasto .cash-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        padding: 18px 20px;
        background: linear-gradient(180deg, var(--cash-modal-surface), var(--cash-modal-surface-soft)) !important;
        border-bottom: 1px solid var(--cash-modal-border);
    }

    #modalIngreso .cash-modal-heading,
    #modalGasto .cash-modal-heading {
        min-width: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    #modalIngreso .cash-modal-icon,
    #modalGasto .cash-modal-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: color-mix(in srgb, var(--cash-modal-action) 10%, var(--cash-modal-surface));
        color: var(--cash-modal-action);
        border: 1px solid color-mix(in srgb, var(--cash-modal-action) 18%, transparent);
    }

    #modalIngreso .cash-modal-dialog.is-income .cash-modal-icon {
        background: color-mix(in srgb, var(--cash-modal-income) 11%, var(--cash-modal-surface));
        color: var(--cash-modal-income);
        border-color: color-mix(in srgb, var(--cash-modal-income) 24%, transparent);
    }

    #modalGasto .cash-modal-dialog.is-expense .cash-modal-icon {
        background: color-mix(in srgb, var(--cash-modal-expense) 10%, var(--cash-modal-surface));
        color: var(--cash-modal-expense);
        border-color: color-mix(in srgb, var(--cash-modal-expense) 22%, transparent);
    }

    #modalIngreso .cash-modal-kicker,
    #modalGasto .cash-modal-kicker {
        margin: 0 0 3px;
        color: var(--cash-modal-muted);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .08em;
        line-height: 1.1;
        text-transform: uppercase;
    }

    #modalIngreso .cash-modal-title,
    #modalGasto .cash-modal-title {
        margin: 0;
        color: var(--cash-modal-text) !important;
        font-size: 18px;
        line-height: 1.2;
        font-weight: 850;
        letter-spacing: 0;
    }

    #modalIngreso .cash-modal-close,
    #modalGasto .cash-modal-close {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        color: var(--cash-modal-muted) !important;
        background: var(--cash-modal-surface) !important;
        border: 1px solid var(--cash-modal-border) !important;
        transition: background .18s ease, color .18s ease, transform .18s ease;
    }

    #modalIngreso .cash-modal-close:hover,
    #modalGasto .cash-modal-close:hover {
        color: var(--cash-modal-text) !important;
        background: color-mix(in srgb, var(--cash-modal-action) 7%, var(--cash-modal-surface)) !important;
    }

    #modalIngreso .cash-modal-form,
    #modalGasto .cash-modal-form {
        min-height: 0;
        overflow-y: auto;
        padding: 20px 22px 0 !important;
        background: var(--cash-modal-surface);
    }

    #modalIngreso .cash-modal-fields,
    #modalGasto .cash-modal-fields {
        display: grid;
        gap: 14px;
    }

    #modalIngreso .cash-modal-grid,
    #modalGasto .cash-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    #modalIngreso .cash-field,
    #modalGasto .cash-field {
        min-width: 0;
        display: grid;
        gap: 7px;
    }

    #modalIngreso .cash-field.hidden,
    #modalGasto .cash-field.hidden {
        display: none !important;
    }

    #modalIngreso .modal-label,
    #modalGasto .modal-label,
    #modalGasto .modal-label-red {
        margin: 0;
        color: var(--cash-modal-text) !important;
        font-size: 12px;
        font-weight: 800;
        line-height: 1.2;
    }

    #modalIngreso .modal-label span,
    #modalGasto .modal-label span {
        color: var(--cash-modal-expense) !important;
    }

    #modalIngreso .modal-input,
    #modalGasto .modal-input,
    #modalGasto .modal-input-red {
        width: 100%;
        min-height: 46px;
        border-radius: 11px !important;
        border: 1px solid var(--cash-modal-border) !important;
        background: color-mix(in srgb, var(--cash-modal-surface-soft) 62%, var(--cash-modal-surface)) !important;
        color: var(--cash-modal-text) !important;
        padding: 11px 13px !important;
        font-size: 14px;
        line-height: 1.35;
        box-shadow: none !important;
        outline: none !important;
    }

    #modalIngreso textarea.modal-input,
    #modalGasto textarea.modal-input {
        min-height: 82px;
        resize: vertical;
    }

    #modalIngreso .modal-input::placeholder,
    #modalGasto .modal-input::placeholder {
        color: color-mix(in srgb, var(--cash-modal-muted) 72%, transparent);
    }

    #modalIngreso .modal-input:focus,
    #modalGasto .modal-input:focus {
        background: var(--cash-modal-surface) !important;
        border-color: color-mix(in srgb, var(--cash-modal-action) 68%, var(--cash-modal-border)) !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--cash-modal-action) 18%, transparent) !important;
    }

    #modalIngreso .cash-money-field,
    #modalGasto .cash-money-field {
        position: relative;
    }

    #modalIngreso .cash-money-prefix,
    #modalGasto .cash-money-prefix {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--cash-modal-muted);
        font-size: 14px;
        font-weight: 850;
        pointer-events: none;
    }

    #modalIngreso .cash-money-field .modal-input,
    #modalGasto .cash-money-field .modal-input {
        padding-left: 30px !important;
        font-size: 16px;
        font-weight: 850;
        font-variant-numeric: tabular-nums;
    }

    #modalIngreso .cash-modal-actions,
    #modalGasto .cash-modal-actions {
        position: sticky;
        bottom: 0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin: 18px -22px 0;
        padding: 16px 22px 18px;
        background: linear-gradient(180deg, color-mix(in srgb, var(--cash-modal-surface) 12%, transparent), var(--cash-modal-surface) 32%);
        border-top: 1px solid var(--cash-modal-border);
    }

    #modalIngreso .cash-modal-btn,
    #modalGasto .cash-modal-btn {
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 16px;
        border-radius: 11px;
        font-size: 13px;
        font-weight: 850;
        line-height: 1;
        transition: transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    #modalIngreso .cash-modal-btn.secondary,
    #modalGasto .cash-modal-btn.secondary {
        color: var(--cash-modal-text) !important;
        background: var(--cash-modal-surface) !important;
        border: 1px solid var(--cash-modal-border) !important;
    }

    #modalIngreso .cash-modal-btn.secondary:hover,
    #modalGasto .cash-modal-btn.secondary:hover {
        background: var(--cash-modal-surface-soft) !important;
    }

    #modalIngreso .cash-modal-btn.primary {
        color: var(--cash-modal-on-action) !important;
        background: var(--cash-modal-action) !important;
        border: 1px solid var(--cash-modal-action) !important;
    }

    #modalIngreso .cash-modal-btn.primary:hover {
        background: var(--cash-modal-action-hover) !important;
        border-color: var(--cash-modal-action-hover) !important;
    }

    #modalGasto .cash-modal-btn.primary {
        color: #FFF7F5 !important;
        background: var(--cash-modal-expense) !important;
        border: 1px solid color-mix(in srgb, var(--cash-modal-expense) 88%, #4F1714) !important;
    }

    #modalGasto .cash-modal-btn.primary:hover {
        background: color-mix(in srgb, var(--cash-modal-expense) 88%, #4F1714) !important;
    }

    @media (max-width: 640px) {
        #modalIngreso,
        #modalGasto {
            padding: 10px;
        }

        #modalIngreso .cash-modal-shell,
        #modalGasto .cash-modal-shell {
            place-items: end center;
        }

        #modalIngreso .cash-modal-dialog,
        #modalGasto .cash-modal-dialog {
            max-height: calc(100svh - 20px);
            border-radius: 16px !important;
        }

        #modalIngreso .cash-modal-header,
        #modalGasto .cash-modal-header {
            padding: 16px;
        }

        #modalIngreso .cash-modal-form,
        #modalGasto .cash-modal-form {
            padding: 16px 16px 0 !important;
        }

        #modalIngreso .cash-modal-grid,
        #modalGasto .cash-modal-grid {
            grid-template-columns: 1fr;
        }

        #modalIngreso .cash-modal-actions,
        #modalGasto .cash-modal-actions {
            margin-inline: -16px;
            padding: 14px 16px 16px;
        }

        #modalIngreso .cash-modal-btn,
        #modalGasto .cash-modal-btn {
            flex: 1;
        }
    }
</style>

<?php
// ── Datos de apoyo: distribución de ingresos por método de pago (cálculo presentacional, no altera datos) ──
$cash_ing_efectivo = (float) ($resumen['ingresos']['efectivo']['total'] ?? 0);
$cash_ing_tarjeta = (float) ($resumen['ingresos']['tarjeta']['total'] ?? 0);
$cash_ing_transferencia = (float) ($resumen['ingresos']['transferencia']['total'] ?? 0);
$cash_ing_total_metodos = $cash_ing_efectivo + $cash_ing_tarjeta + $cash_ing_transferencia;

$cash_pct_efectivo = 0;
$cash_pct_tarjeta = 0;
$cash_pct_transferencia = 0;

if ($cash_ing_total_metodos > 0) {
    $cash_pct_efectivo = round(($cash_ing_efectivo / $cash_ing_total_metodos) * 100, 1);
    $cash_pct_tarjeta = round(($cash_ing_tarjeta / $cash_ing_total_metodos) * 100, 1);
    $cash_pct_transferencia = max(0, round(100 - $cash_pct_efectivo - $cash_pct_tarjeta, 1));
}

$cash_balance_general = (float) ($resumen['balance_general'] ?? 0);
$cash_total_movimientos = (int) ($resumen['total_movimientos'] ?? 0);
$cash_fecha_apertura_label = !empty($corte['fecha_apertura'])
    ? date('d/m/Y H:i', strtotime($corte['fecha_apertura']))
    : 'Sin fecha';
$cash_hora_apertura_label = !empty($corte['fecha_apertura'])
    ? date('H:i', strtotime($corte['fecha_apertura']))
    : '--:--';
$cash_caja_nombre = $caja['nombre'] ?? 'Caja';
$cash_responsable = $corte['usuario_apertura'] ?? 'Responsable no asignado';
$cash_hotel_nombre = function_exists('current_hotel_display_name') ? current_hotel_display_name() : 'Hotel';
$cash_methods = [
    'efectivo' => [
        'label' => 'Efectivo',
        'icon' => 'money-bill-wave',
        'class' => 'efectivo',
        'note' => 'Afecta caja fisica',
    ],
    'tarjeta' => [
        'label' => 'Tarjeta',
        'icon' => 'credit-card',
        'class' => 'tarjeta',
        'note' => 'Cobro bancario',
    ],
    'transferencia' => [
        'label' => 'Transferencia',
        'icon' => 'exchange-alt',
        'class' => 'transferencia',
        'note' => 'Deposito o SPEI',
    ],
];
?>
<style id="cash-control-room-redesign">
    .cash-page.cash-control-room {
        --cc-dark: color-mix(in srgb, var(--brand-text, #202421) 78%, #101827);
        --cc-dark-2: color-mix(in srgb, var(--brand-action-bg-hover, #17221B) 58%, #0F172A);
        --cc-action: var(--brand-action-bg, #223126);
        --cc-action-hover: var(--brand-action-bg-hover, #17221B);
        --cc-on-action: var(--brand-action-text, #FFFEFB);
        --cc-accent: var(--brand-accent, #BD9441);
        --cc-bg: color-mix(in srgb, var(--cc-accent) 7%, #F5F2EA);
        --cc-panel: color-mix(in srgb, var(--cc-accent) 2%, #FFFFFF);
        --cc-panel-2: color-mix(in srgb, var(--cc-action) 4%, #FFFFFF);
        --cc-line: color-mix(in srgb, var(--cc-action) 14%, #E7DDD0);
        --cc-line-soft: color-mix(in srgb, var(--cc-action) 8%, #EEE7DC);
        --cc-text: var(--brand-text, #202421);
        --cc-muted: var(--brand-muted, #68706A);
        --cc-green: #16824E;
        --cc-red: #B93A32;
        --cc-blue: #2563EB;
        --cc-violet: #6D4ED8;
        min-height: 100vh;
        padding: 18px !important;
        background:
            linear-gradient(135deg, color-mix(in srgb, var(--cc-action) 5%, transparent) 0 1px, transparent 1px 28px),
            radial-gradient(circle at 88% 0%, color-mix(in srgb, var(--cc-accent) 24%, transparent), transparent 28rem),
            linear-gradient(180deg, var(--cc-bg), #FBFAF6 56%, #EFE7DA) !important;
        color: var(--cc-text);
    }

    .cash-control-room > .cash-hero-wrap,
    .cash-control-room > .cash-status-wrap,
    .cash-control-room > .cash-summary-wrap,
    .cash-control-room > .cash-activity-wrap {
        display: none !important;
    }

    .cc-shell {
        position: relative;
        z-index: 2;
        width: min(1680px, calc(100vw - 36px));
        margin: 0 auto;
    }

    .cc-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(300px, 430px);
        gap: 18px;
        align-items: stretch;
        margin-bottom: 14px;
        border-radius: 18px;
        border: 1px solid color-mix(in srgb, var(--cc-accent) 24%, transparent);
        background:
            radial-gradient(circle at 86% 10%, color-mix(in srgb, var(--cc-accent) 32%, transparent), transparent 20rem),
            linear-gradient(135deg, var(--cc-dark), var(--cc-dark-2));
        box-shadow: 0 28px 70px -48px rgba(15, 23, 42, .9);
        overflow: hidden;
    }

    .cc-hero-main {
        min-width: 0;
        padding: clamp(22px, 2.5vw, 34px);
    }

    .cc-state {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 30px;
        padding: 0 11px;
        border-radius: 999px;
        color: #DDF8E9;
        background: rgba(22, 130, 78, .18);
        border: 1px solid rgba(92, 214, 143, .26);
        font-size: .74rem;
        font-weight: 900;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cc-state-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #60D394;
        box-shadow: 0 0 0 5px rgba(96, 211, 148, .12);
    }

    .cc-hero-title {
        margin: 16px 0 8px;
        color: var(--cc-on-action);
        font-size: clamp(2.35rem, 4.6vw, 5.2rem);
        line-height: .88;
        font-weight: 950;
        letter-spacing: 0;
        font-variant-numeric: tabular-nums;
    }

    .cc-hero-title span {
        display: block;
        margin-top: 10px;
        color: color-mix(in srgb, var(--cc-on-action) 72%, transparent);
        font-size: clamp(.82rem, 1vw, .98rem);
        line-height: 1.35;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .cc-hero-copy {
        max-width: 72ch;
        margin: 0;
        color: color-mix(in srgb, var(--cc-on-action) 76%, transparent);
        font-size: .94rem;
        line-height: 1.55;
        font-weight: 650;
    }

    .cc-meta-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 18px;
    }

    .cc-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 34px;
        padding: 0 11px;
        border-radius: 10px;
        color: color-mix(in srgb, var(--cc-on-action) 88%, transparent);
        background: rgba(255, 255, 255, .09);
        border: 1px solid rgba(255, 255, 255, .14);
        font-size: .8rem;
        font-weight: 850;
    }

    .cc-hero-side {
        display: grid;
        gap: 10px;
        align-content: center;
        padding: 18px;
        background: rgba(255, 255, 255, .07);
        border-left: 1px solid rgba(255, 255, 255, .11);
    }

    .cc-primary-actions {
        display: grid;
        gap: 10px;
    }

    .cc-action-btn {
        min-height: 54px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        width: 100%;
        padding: 0 16px;
        border-radius: 13px;
        border: 1px solid transparent;
        color: #FFFFFF;
        font-size: .92rem;
        font-weight: 950;
        text-decoration: none;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
    }

    .cc-action-btn:hover {
        transform: translateY(-1px);
        filter: saturate(1.04);
    }

    .cc-action-btn:active {
        transform: translateY(0) scale(.985);
    }

    .cc-action-btn i {
        width: 30px;
        height: 30px;
        display: inline-grid;
        place-items: center;
        border-radius: 9px;
        background: rgba(255, 255, 255, .16);
    }

    .cc-action-btn.is-income {
        background: linear-gradient(135deg, #19985E, #0F7048);
        box-shadow: 0 18px 34px -28px rgba(22, 130, 78, .9);
    }

    .cc-action-btn.is-expense {
        background: linear-gradient(135deg, #C9463B, #9A2F28);
        box-shadow: 0 18px 34px -28px rgba(185, 58, 50, .9);
    }

    .cc-action-btn.is-cut {
        background: linear-gradient(135deg, var(--cc-accent), color-mix(in srgb, var(--cc-accent) 64%, #32220A));
        box-shadow: 0 18px 34px -28px color-mix(in srgb, var(--cc-accent) 90%, transparent);
    }

    .cc-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .cc-kpi,
    .cc-panel,
    .cc-shortcuts {
        border: 1px solid var(--cc-line);
        border-radius: 16px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 18px 42px -34px rgba(15, 23, 42, .54);
    }

    .cc-kpi {
        min-height: 132px;
        display: grid;
        align-content: space-between;
        padding: 16px;
    }

    .cc-kpi-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        color: var(--cc-muted);
        font-size: .72rem;
        font-weight: 950;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cc-kpi-icon {
        width: 34px;
        height: 34px;
        display: inline-grid;
        place-items: center;
        border-radius: 10px;
        background: color-mix(in srgb, var(--cc-action) 8%, #FFFFFF);
        color: var(--cc-action);
        border: 1px solid var(--cc-line-soft);
    }

    .cc-kpi-value {
        margin: 12px 0 6px;
        color: var(--cc-text);
        font-size: clamp(1.35rem, 2vw, 2rem);
        line-height: 1;
        font-weight: 950;
        font-variant-numeric: tabular-nums;
    }

    .cc-kpi-value.is-income { color: var(--cc-green); }
    .cc-kpi-value.is-expense { color: var(--cc-red); }

    .cc-kpi-note {
        margin: 0;
        color: var(--cc-muted);
        font-size: .78rem;
        line-height: 1.35;
        font-weight: 700;
    }

    .cc-workspace {
        display: grid;
        grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
        gap: 14px;
        align-items: start;
        margin-bottom: 14px;
    }

    .cc-panel {
        overflow: hidden;
    }

    .cc-panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 15px 16px;
        background: linear-gradient(90deg, color-mix(in srgb, var(--cc-accent) 7%, #FFFFFF), #FFFFFF);
        border-bottom: 1px solid var(--cc-line-soft);
    }

    .cc-panel-kicker {
        display: block;
        color: var(--cc-muted);
        font-size: .68rem;
        font-weight: 950;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cc-panel-title {
        margin: 4px 0 0;
        color: var(--cc-text);
        font-size: 1rem;
        line-height: 1.2;
        font-weight: 950;
    }

    .cc-panel-link {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        min-height: 34px;
        padding: 0 11px;
        border-radius: 10px;
        color: var(--cc-action);
        background: color-mix(in srgb, var(--cc-action) 6%, #FFFFFF);
        border: 1px solid var(--cc-line-soft);
        font-size: .78rem;
        font-weight: 900;
        text-decoration: none;
    }

    .cc-panel-body {
        padding: 14px;
    }

    .cc-method-bar {
        display: flex;
        height: 11px;
        margin-bottom: 12px;
        overflow: hidden;
        border-radius: 999px;
        background: var(--cc-line-soft);
    }

    .cc-method-bar span:nth-child(1) { background: var(--cc-green); }
    .cc-method-bar span:nth-child(2) { background: var(--cc-blue); }
    .cc-method-bar span:nth-child(3) { background: var(--cc-violet); }

    .cc-method-list {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 10px;
    }

    .cc-method-card {
        min-width: 0;
        padding: 13px;
        border-radius: 13px;
        border: 1px solid var(--cc-line-soft);
        background: var(--cc-panel);
    }

    .cc-method-card.is-efectivo { --method-color: var(--cc-green); }
    .cc-method-card.is-tarjeta { --method-color: var(--cc-blue); }
    .cc-method-card.is-transferencia { --method-color: var(--cc-violet); }

    .cc-method-title {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--cc-text);
        font-size: .86rem;
        font-weight: 950;
    }

    .cc-method-title i {
        color: var(--method-color);
    }

    .cc-method-note {
        margin: 4px 0 12px;
        color: var(--cc-muted);
        font-size: .72rem;
        font-weight: 750;
    }

    .cc-method-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 8px 0;
        border-top: 1px solid var(--cc-line-soft);
        color: var(--cc-muted);
        font-size: .78rem;
        font-weight: 850;
    }

    .cc-money {
        color: var(--cc-text);
        font-weight: 950;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .cc-money.is-income { color: var(--cc-green); }
    .cc-money.is-expense { color: var(--cc-red); }

    .cash-control-room .cash-movements-panel {
        max-height: none;
        border-radius: 16px !important;
        background: rgba(255, 255, 255, .92) !important;
        border: 1px solid var(--cc-line) !important;
        box-shadow: 0 18px 42px -34px rgba(15, 23, 42, .54) !important;
    }

    .cash-control-room .cash-movements-head {
        padding: 15px 16px !important;
        background: linear-gradient(90deg, color-mix(in srgb, var(--cc-action) 5%, #FFFFFF), #FFFFFF) !important;
        border-bottom: 1px solid var(--cc-line-soft) !important;
    }

    .cash-control-room .cash-movements-list {
        max-height: 455px !important;
        padding: 12px !important;
    }

    .cash-control-room .cash-movement-item {
        border-radius: 12px !important;
        border-color: var(--cc-line-soft) !important;
        box-shadow: none !important;
    }

    .cc-bottom-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 14px;
        align-items: start;
    }

    .cc-category-list {
        display: grid;
        gap: 9px;
    }

    .cc-category-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 12px;
        align-items: center;
        padding: 11px;
        border: 1px solid var(--cc-line-soft);
        border-radius: 12px;
        background: #FFFFFF;
    }

    .cc-category-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
    }

    .cc-category-icon {
        width: 36px;
        height: 36px;
        display: inline-grid;
        place-items: center;
        flex: 0 0 36px;
        border-radius: 10px;
    }

    .cc-category-title {
        margin: 0;
        color: var(--cc-text);
        font-size: .88rem;
        line-height: 1.25;
        font-weight: 900;
    }

    .cc-category-sub {
        margin: 2px 0 0;
        color: var(--cc-muted);
        font-size: .73rem;
        font-weight: 750;
    }

    .cc-empty {
        display: grid;
        place-items: center;
        min-height: 148px;
        padding: 20px;
        text-align: center;
        color: var(--cc-muted);
        font-weight: 750;
    }

    .cc-shortcuts {
        grid-column: 1 / -1;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        overflow-x: auto;
    }

    .cc-shortcuts-label {
        flex: 0 0 auto;
        color: var(--cc-muted);
        font-size: .72rem;
        font-weight: 950;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cc-shortcut-link {
        flex: 0 0 auto;
        min-height: 42px;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        padding: 0 13px;
        border-radius: 11px;
        color: var(--cc-text);
        background: #FFFFFF;
        border: 1px solid var(--cc-line-soft);
        font-size: .84rem;
        font-weight: 900;
        text-decoration: none;
        transition: transform .16s ease, border-color .16s ease, background .16s ease;
    }

    .cc-shortcut-link:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--cc-accent) 34%, var(--cc-line));
        background: color-mix(in srgb, var(--cc-accent) 5%, #FFFFFF);
    }

    @media (max-width: 1180px) {
        .cc-hero,
        .cc-workspace {
            grid-template-columns: 1fr;
        }

        .cc-hero-side {
            border-left: 0;
            border-top: 1px solid rgba(255, 255, 255, .11);
        }

        .cc-primary-actions {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .cc-kpi-grid,
        .cc-method-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .cash-page.cash-control-room {
            padding: 10px !important;
        }

        .cc-shell {
            width: 100%;
        }

        .cc-primary-actions,
        .cc-kpi-grid,
        .cc-method-list,
        .cc-bottom-grid {
            grid-template-columns: 1fr;
        }

        .cc-hero-main,
        .cc-hero-side {
            padding: 16px;
        }

        .cc-hero-title {
            font-size: clamp(2.05rem, 13vw, 3.25rem);
        }

        .cc-shortcuts {
            align-items: stretch;
            flex-direction: column;
        }

        .cc-shortcuts-label,
        .cc-shortcut-link {
            width: 100%;
        }
    }
</style>

<style id="cash-notifications-index-redesign">
    .cash-page.cash-control-room {
        --cn-primary: var(--brand-primary, #1f3f46);
        --cn-secondary: var(--brand-secondary, #27333f);
        --cn-accent: var(--brand-accent, #b58a3c);
        --cn-ink: color-mix(in srgb, var(--cn-primary) 54%, #475467);
        --cn-text: #344054;
        --cn-muted: #748094;
        --cn-line: color-mix(in srgb, var(--cn-primary) 9%, #e9e2d7);
        --cn-surface: rgba(255, 255, 255, .76);
        --cn-panel: rgba(255, 255, 255, .9);
        --cn-soft: color-mix(in srgb, var(--cn-accent) 5%, #f8f5ee);
        --cn-focus: color-mix(in srgb, var(--cn-accent) 22%, transparent);
        --cn-income: #16a34a;
        --cn-expense: #dc2626;
        --cn-info: #2563eb;
        --cn-violet: #7c3aed;
        --cn-cash: #2f8a70;
        --cn-card: #3f7891;
        --cn-transfer: #6e6aa9;
        min-height: 100vh;
        padding: 0 !important;
        color: var(--cn-text);
        background:
            radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--cn-accent) 12%, transparent), transparent 25rem),
            radial-gradient(circle at 96% 8%, color-mix(in srgb, var(--cn-primary) 7%, transparent), transparent 30rem),
            linear-gradient(180deg, #fcfbf8 0%, color-mix(in srgb, var(--cn-accent) 4%, #f4f1ea) 100%) !important;
        font-family: "Inter", "Segoe UI", system-ui, sans-serif;
    }

    .cash-control-room > .cc-shell {
        display: none !important;
    }

    .cash-ntx-shell {
        width: 100%;
        max-width: 1600px;
        margin: 0 auto;
        padding: 30px clamp(34px, 4vw, 76px) 58px;
        box-sizing: border-box;
    }

    .cash-ntx-top {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 22px;
        align-items: end;
        margin-bottom: 20px;
    }

    .cash-ntx-heading {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .cash-ntx-kicker {
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 0;
        border: 0;
        background: transparent;
        color: color-mix(in srgb, var(--cn-primary) 58%, #667085);
        font-size: .76rem;
        font-weight: 720;
        letter-spacing: .045em;
        text-transform: uppercase;
    }

    .cash-ntx-kicker::after {
        content: "";
        width: 54px;
        height: 1px;
        border-radius: 999px;
        background: linear-gradient(90deg,
            color-mix(in srgb, var(--cn-accent) 58%, var(--cn-primary)),
            color-mix(in srgb, var(--cn-accent) 8%, transparent)
        );
    }

    .cash-ntx-kicker i {
        width: 24px;
        height: 24px;
        display: inline-grid;
        place-items: center;
        border: 1px solid color-mix(in srgb, var(--cn-accent) 22%, #ded6c8);
        border-radius: 8px;
        background: color-mix(in srgb, var(--cn-accent) 7%, rgba(255,255,255,.84));
        color: color-mix(in srgb, var(--cn-accent) 76%, #795a16);
        font-size: .72rem;
    }

    .cash-ntx-title {
        position: relative;
        display: block;
        margin: 0;
        color: color-mix(in srgb, var(--cn-primary) 78%, #263247);
        font-size: clamp(2rem, 3.25vw, 3.2rem);
        line-height: .98;
        font-weight: 660;
        letter-spacing: 0;
        text-wrap: balance;
        text-shadow: 0 1px 0 rgba(255,255,255,.68);
    }

    .cash-ntx-title::after {
        content: none;
    }

    .cash-ntx-subtitle {
        max-width: 680px;
        margin: 2px 0 0;
        color: #526176;
        font-size: .98rem;
        line-height: 1.55;
        font-weight: 430;
    }

    .cash-ntx-live-card {
        min-width: 300px;
        border: 1px solid var(--cn-line);
        border-radius: 16px;
        background:
            linear-gradient(135deg, rgba(255,255,255,.84), color-mix(in srgb, var(--cn-accent) 7%, rgba(255,255,255,.9)));
        box-shadow: 0 18px 42px -38px color-mix(in srgb, var(--cn-primary) 34%, transparent);
        padding: 16px;
        color: var(--cn-text);
    }

    .cash-ntx-live-card span {
        display: block;
        color: var(--cn-muted);
        font-size: .74rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .cash-ntx-live-card strong {
        display: block;
        margin-top: 5px;
        color: color-mix(in srgb, var(--cn-primary) 62%, #4b5563);
        font-size: clamp(1.95rem, 3vw, 2.45rem);
        line-height: 1;
        font-weight: 560;
        font-variant-numeric: tabular-nums;
    }

    .cash-ntx-live-card small {
        display: block;
        margin-top: 8px;
        color: color-mix(in srgb, var(--cn-accent) 72%, #7c5b16);
        font-weight: 620;
    }

    .cash-ntx-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 22px;
    }

    .cash-ntx-stat {
        position: relative;
        overflow: hidden;
        min-height: 88px;
        display: grid;
        align-content: space-between;
        border: 1px solid var(--cn-line);
        border-radius: 14px;
        background: var(--cn-surface);
        box-shadow: 0 14px 34px -32px color-mix(in srgb, var(--cn-primary) 22%, transparent);
        padding: 13px 14px;
    }

    .cash-ntx-stat::after {
        content: "";
        position: absolute;
        inset: auto 12px 0 12px;
        height: 3px;
        border-radius: 999px 999px 0 0;
        background: var(--stat-color, var(--cn-accent));
    }

    .cash-ntx-stat.is-initial { --stat-color: color-mix(in srgb, var(--cn-accent) 70%, #d89d20); }
    .cash-ntx-stat.is-income { --stat-color: var(--cn-income); }
    .cash-ntx-stat.is-expense { --stat-color: var(--cn-expense); }
    .cash-ntx-stat.is-balance { --stat-color: var(--cn-info); }

    .cash-ntx-stat span {
        color: var(--cn-muted);
        font-size: .72rem;
        font-weight: 620;
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .cash-ntx-stat strong {
        color: color-mix(in srgb, var(--cn-primary) 58%, #4b5563);
        font-size: 1.55rem;
        line-height: 1;
        font-weight: 560;
        font-variant-numeric: tabular-nums;
    }

    .cash-ntx-stat strong.is-income,
    .cash-ntx-money.is-income {
        color: var(--cn-income);
    }

    .cash-ntx-stat strong.is-expense,
    .cash-ntx-money.is-expense {
        color: var(--cn-expense);
    }

    .cash-ntx-layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(312px, 360px);
        grid-template-areas: "main side";
        gap: 18px;
        align-items: start;
    }

    .cash-ntx-side {
        grid-area: side;
        display: grid;
        grid-template-columns: 1fr;
        gap: 14px;
        align-items: start;
    }

    .cash-ntx-panel {
        border: 1px solid var(--cn-line);
        border-radius: 18px;
        background: var(--cn-panel);
        box-shadow: 0 18px 42px -36px color-mix(in srgb, var(--cn-primary) 24%, transparent);
        overflow: hidden;
    }

    .cash-ntx-panel-head {
        padding: 16px 17px 12px;
        border-bottom: 1px solid color-mix(in srgb, var(--cn-line) 84%, transparent);
    }

    .cash-ntx-panel-head h2 {
        margin: 0;
        color: var(--cn-ink);
        font-size: .98rem;
        line-height: 1.2;
        font-weight: 620;
    }

    .cash-ntx-panel-head p {
        margin: 5px 0 0;
        color: var(--cn-muted);
        font-size: .8rem;
        line-height: 1.42;
        font-weight: 420;
    }

    .cash-ntx-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 9px;
        padding: 14px;
    }

    .cash-ntx-btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        border: 1px solid var(--cn-line);
        border-radius: 12px;
        background: #fff;
        color: var(--cn-ink);
        padding: 0 12px;
        font-size: .82rem;
        font-weight: 620;
        text-decoration: none;
        cursor: pointer;
        transition: transform .18s ease, background .18s ease, border-color .18s ease, color .18s ease;
    }

    .cash-ntx-btn:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--cn-accent) 32%, var(--cn-line));
        background: color-mix(in srgb, var(--cn-accent) 8%, #fff);
    }

    .cash-ntx-btn.is-income {
        border-color: color-mix(in srgb, var(--cn-income) 24%, #d8cdbb);
        background: color-mix(in srgb, var(--cn-income) 8%, #fff);
        color: #166534;
    }

    .cash-ntx-btn.is-expense {
        border-color: #fecaca;
        background: #fef2f2;
        color: #991b1b;
    }

    .cash-ntx-btn.is-primary {
        border-color: color-mix(in srgb, var(--cn-accent) 34%, #d8cdbb);
        background: color-mix(in srgb, var(--cn-accent) 13%, #fff);
        color: color-mix(in srgb, var(--cn-primary) 58%, #667085);
    }

    .cash-ntx-methods,
    .cash-ntx-shortcuts {
        display: grid;
        gap: 10px;
        padding: 14px;
    }

    .cash-ntx-methods {
        grid-template-columns: 1fr;
    }

    .cash-ntx-shortcuts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .cash-ntx-chip,
    .cash-ntx-shortcut {
        --item-color: color-mix(in srgb, var(--cn-accent) 76%, #795a16);
        --item-soft: color-mix(in srgb, var(--item-color) 8%, #fffdf8);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
        min-height: 42px;
        border: 1px solid color-mix(in srgb, var(--item-color) 18%, #ddd5c8);
        border-radius: 12px;
        background:
            radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--item-color) 9%, transparent), transparent 5.8rem),
            linear-gradient(135deg, var(--item-soft), rgba(255, 253, 248, .92));
        color: #435164;
        padding: 0 11px;
        font-size: .75rem;
        font-weight: 590;
        text-decoration: none;
        box-shadow: 0 12px 26px -28px color-mix(in srgb, var(--item-color) 42%, transparent);
        transition: transform .18s cubic-bezier(.22, 1, .36, 1), border-color .18s ease, background .18s ease, box-shadow .18s ease, color .18s ease;
    }

    .cash-ntx-chip {
        justify-content: space-between;
    }

    .cash-ntx-chip span {
        display: inline-flex;
        min-width: 0;
        align-items: center;
        gap: 7px;
    }

    .cash-ntx-chip strong {
        margin-left: auto;
        color: color-mix(in srgb, var(--item-color) 46%, var(--cn-ink));
        font-size: .76rem;
        font-weight: 680;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .cash-ntx-chip i,
    .cash-ntx-shortcut i {
        flex: 0 0 auto;
        width: 24px;
        height: 24px;
        display: inline-grid;
        place-items: center;
        border-radius: 8px;
        background: color-mix(in srgb, var(--item-color) 12%, rgba(255, 253, 248, .9));
        color: var(--item-color);
        font-size: .72rem;
        transition: transform .18s cubic-bezier(.22, 1, .36, 1), background .18s ease, color .18s ease;
    }

    .cash-ntx-chip.is-method-efectivo,
    .cash-ntx-shortcut.is-movements {
        --item-color: #2f8a70;
    }

    .cash-ntx-chip.is-method-tarjeta,
    .cash-ntx-shortcut.is-methods {
        --item-color: #3f7891;
    }

    .cash-ntx-chip.is-method-transferencia,
    .cash-ntx-chip.is-method-transfer,
    .cash-ntx-shortcut.is-history {
        --item-color: #6e6aa9;
    }

    .cash-ntx-shortcut.is-categories {
        --item-color: #b98a35;
    }

    .cash-ntx-shortcut:hover,
    .cash-ntx-shortcut:focus-visible {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--item-color) 32%, #ddd5c8);
        background:
            radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--item-color) 13%, transparent), transparent 6rem),
            linear-gradient(135deg, color-mix(in srgb, var(--item-color) 12%, #fffdf8), rgba(255, 253, 248, .96));
        color: color-mix(in srgb, var(--item-color) 42%, #344054);
        box-shadow: 0 16px 32px -27px color-mix(in srgb, var(--item-color) 54%, transparent);
        outline: none;
    }

    .cash-ntx-shortcut:hover i,
    .cash-ntx-shortcut:focus-visible i {
        transform: scale(1.04);
        background: color-mix(in srgb, var(--item-color) 18%, rgba(255, 253, 248, .92));
    }

    .cash-ntx-shortcut:active {
        transform: translateY(0) scale(.99);
    }

    .cash-ntx-main {
        grid-area: main;
        min-width: 0;
        overflow: hidden;
        background:
            radial-gradient(circle at 0 0, color-mix(in srgb, var(--cn-accent) 11%, transparent), transparent 18rem),
            linear-gradient(180deg, rgba(255,255,255,.96), color-mix(in srgb, var(--cn-info) 4%, #fffdf8)) !important;
        border-color: color-mix(in srgb, var(--cn-accent) 22%, var(--cn-line)) !important;
    }

    .cash-ntx-inbox-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 16px;
        align-items: start;
        padding: 18px;
        border-bottom: 1px solid color-mix(in srgb, var(--cn-accent) 18%, var(--cn-line));
        background:
            radial-gradient(circle at 100% 0, color-mix(in srgb, var(--cn-info) 8%, transparent), transparent 13rem),
            linear-gradient(135deg, color-mix(in srgb, var(--cn-accent) 7%, #fff), color-mix(in srgb, var(--cn-primary) 4%, #fff));
    }

    .cash-ntx-inbox-head h2 {
        margin: 0;
        color: var(--cn-ink);
        font-size: 1.18rem;
        line-height: 1.2;
        font-weight: 620;
    }

    .cash-ntx-inbox-head p {
        margin: 5px 0 0;
        color: var(--cn-muted);
        font-size: .84rem;
        line-height: 1.45;
        font-weight: 420;
    }

    .cash-ntx-count {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        border: 1px solid color-mix(in srgb, var(--cn-info) 24%, #ddd5c8);
        border-radius: 999px;
        background: color-mix(in srgb, var(--cn-info) 9%, #fff);
        color: color-mix(in srgb, var(--cn-info) 58%, var(--cn-primary));
        padding: 0 11px;
        font-size: .78rem;
        font-weight: 620;
        white-space: nowrap;
    }

    .cash-ntx-tabs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        min-height: 66px;
        padding: 14px 18px 16px;
        border-bottom: 1px solid color-mix(in srgb, var(--cn-accent) 16%, var(--cn-line));
        background: linear-gradient(135deg, rgba(255,255,255,.56), color-mix(in srgb, var(--cn-accent) 4%, #fffdf8));
    }

    .cash-ntx-tab {
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid color-mix(in srgb, var(--cn-primary) 10%, #ddd5c8);
        border-radius: 999px;
        background: rgba(255,255,255,.82);
        color: #526176;
        padding: 0 11px;
        font-size: .78rem;
        font-weight: 560;
        text-decoration: none;
    }

    .cash-ntx-tab.is-active {
        border-color: color-mix(in srgb, var(--cn-income) 28%, #ddd5c8);
        background: color-mix(in srgb, var(--cn-income) 10%, #fff);
        color: color-mix(in srgb, var(--cn-income) 58%, var(--cn-ink));
    }

    .cash-ntx-feed {
        display: grid;
        gap: 12px;
        padding: 16px;
        background:
            radial-gradient(circle at 7% 0, color-mix(in srgb, var(--cn-income) 7%, transparent), transparent 18rem),
            radial-gradient(circle at 96% 100%, color-mix(in srgb, var(--cn-expense) 5%, transparent), transparent 18rem),
            linear-gradient(180deg, rgba(255,255,255,.54), rgba(255,255,255,.28));
    }

    .cash-ntx-row {
        position: relative;
        display: grid;
        grid-template-columns: 40px minmax(0, 1fr) auto;
        gap: 12px;
        align-items: start;
        min-height: 82px;
        border: 1px solid color-mix(in srgb, var(--row-color, var(--cn-primary)) 18%, #e8e0d3);
        border-radius: 14px;
        background:
            radial-gradient(circle at 0 50%, color-mix(in srgb, var(--row-color, var(--cn-primary)) 9%, transparent), transparent 8rem),
            linear-gradient(135deg, color-mix(in srgb, var(--row-color, var(--cn-primary)) 5%, #fff), rgba(255,255,255,.9));
        padding: 13px 14px;
        box-shadow: 0 12px 28px -25px color-mix(in srgb, var(--row-color, var(--cn-primary)) 28%, transparent);
        transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
    }

    .cash-ntx-row::before {
        content: "";
        position: absolute;
        inset: 12px auto 12px 0;
        width: 3px;
        border-radius: 0 999px 999px 0;
        background: linear-gradient(180deg, var(--row-color, #94a3b8), color-mix(in srgb, var(--row-color, #94a3b8) 62%, var(--cn-accent)));
    }

    .cash-ntx-row.is-income { --row-color: var(--cn-income); }
    .cash-ntx-row.is-expense { --row-color: var(--cn-expense); }

    .cash-ntx-row:hover {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--row-color, var(--cn-accent)) 34%, #dfd5c8);
        background:
            radial-gradient(circle at 0 50%, color-mix(in srgb, var(--row-color, var(--cn-primary)) 13%, transparent), transparent 8rem),
            linear-gradient(135deg, color-mix(in srgb, var(--row-color, var(--cn-primary)) 7%, #fff), rgba(255,255,255,.94));
        box-shadow: 0 16px 32px -26px color-mix(in srgb, var(--row-color, var(--cn-primary)) 36%, transparent);
    }

    .cash-ntx-icon {
        width: 40px;
        height: 40px;
        display: grid;
        place-items: center;
        border: 1px solid color-mix(in srgb, var(--row-color, var(--cn-accent)) 24%, #ddd5c8);
        border-radius: 12px;
        background: color-mix(in srgb, var(--row-color, var(--cn-accent)) 12%, #fff);
        color: color-mix(in srgb, var(--row-color, var(--cn-primary)) 62%, #465668);
    }

    .cash-ntx-row-title {
        color: color-mix(in srgb, var(--cn-primary) 52%, #344054);
        font-size: .98rem;
        line-height: 1.28;
        font-weight: 620;
    }

    .cash-ntx-badge {
        display: inline-flex;
        align-items: center;
        min-height: 24px;
        border-radius: 999px;
        padding: 0 8px;
        font-size: .7rem;
        font-weight: 650;
        border: 1px solid color-mix(in srgb, var(--badge-color, var(--cn-accent)) 18%, transparent);
        background: color-mix(in srgb, var(--badge-color, var(--cn-accent)) 12%, #fff);
        color: color-mix(in srgb, var(--badge-color, var(--cn-primary)) 62%, #667085);
    }

    .cash-ntx-badge.is-income {
        --badge-color: var(--cn-income);
        background: color-mix(in srgb, var(--cn-income) 14%, #fff);
        color: color-mix(in srgb, var(--cn-income) 72%, #1f2937);
    }

    .cash-ntx-badge.is-expense {
        --badge-color: var(--cn-expense);
        background: color-mix(in srgb, var(--cn-expense) 13%, #fff);
        color: color-mix(in srgb, var(--cn-expense) 72%, #1f2937);
    }

    .cash-ntx-badge.is-method-efectivo {
        --badge-color: var(--cn-cash);
    }

    .cash-ntx-badge.is-method-tarjeta {
        --badge-color: var(--cn-card);
    }

    .cash-ntx-badge.is-method-transferencia,
    .cash-ntx-badge.is-method-transfer {
        --badge-color: var(--cn-transfer);
    }

    .cash-ntx-row-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
    }

    .cash-ntx-message {
        margin: 6px 0 0;
        color: color-mix(in srgb, var(--row-color, var(--cn-primary)) 22%, #526176);
        font-size: .9rem;
        line-height: 1.48;
        font-weight: 420;
    }

    .cash-ntx-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 9px;
        margin-top: 8px;
        color: var(--cn-muted);
        font-size: .78rem;
        font-weight: 430;
    }

    .cash-ntx-meta span,
    .cash-ntx-meta a {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: color-mix(in srgb, var(--row-color, var(--cn-primary)) 24%, #748094);
        text-decoration: none;
    }

    .cash-ntx-amount {
        align-self: center;
        justify-self: end;
        color: color-mix(in srgb, var(--cn-primary) 58%, #4b5563);
        font-size: .95rem;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }

    .cash-ntx-empty {
        min-height: 230px;
        display: grid;
        place-items: center;
        border: 1px dashed color-mix(in srgb, var(--cn-accent) 28%, #d9cec0);
        border-radius: 14px;
        background: rgba(255,255,255,.72);
        padding: 28px;
        text-align: center;
    }

    .cash-ntx-empty i {
        width: 52px;
        height: 52px;
        display: inline-grid;
        place-items: center;
        border: 1px solid color-mix(in srgb, var(--cn-accent) 22%, #ddd5c8);
        border-radius: 14px;
        background: color-mix(in srgb, var(--cn-accent) 9%, #fff);
        color: color-mix(in srgb, var(--cn-primary) 58%, #667085);
        font-size: 1.2rem;
    }

    .cash-ntx-empty h3 {
        margin: 14px 0 5px;
        color: var(--cn-ink);
        font-size: 1.05rem;
        font-weight: 620;
    }

    .cash-ntx-empty p {
        max-width: 420px;
        margin: 0 auto;
        color: var(--cn-muted);
        line-height: 1.5;
        font-weight: 420;
    }

    .cash-ntx-categories {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
        margin-top: 22px;
    }

    .cash-ntx-cat-list {
        display: grid;
        gap: 10px;
        padding: 16px;
    }

    .cash-ntx-cat {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 10px;
        align-items: center;
        border: 1px solid color-mix(in srgb, var(--cn-primary) 10%, #e8e0d3);
        border-radius: 13px;
        background: rgba(255,255,255,.82);
        padding: 12px;
    }

    .cash-ntx-cat-main {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 9px;
    }

    .cash-ntx-cat-icon {
        width: 36px;
        height: 36px;
        flex: 0 0 36px;
        display: grid;
        place-items: center;
        border-radius: 11px;
    }

    .cash-ntx-cat strong {
        display: block;
        color: color-mix(in srgb, var(--cn-primary) 52%, #344054);
        font-size: .88rem;
        font-weight: 620;
    }

    .cash-ntx-cat small {
        display: block;
        margin-top: 2px;
        color: var(--cn-muted);
        font-size: .74rem;
        font-weight: 430;
    }

    @media (max-width: 1180px) {
        .cash-ntx-layout {
            grid-template-columns: 1fr;
            grid-template-areas:
                "side"
                "main";
        }

        .cash-ntx-side {
            grid-template-columns: minmax(0, 1fr) minmax(300px, .78fr);
            align-items: stretch;
        }

        .cash-ntx-shortcuts-panel {
            grid-column: 1 / -1;
        }

        .cash-ntx-live-card {
            min-width: 0;
        }
    }

    @media (max-width: 1100px) {
        .cash-ntx-top {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 760px) {
        .cash-ntx-shell {
            width: 100%;
            padding: 18px 12px 44px;
        }

        .cash-ntx-title {
            font-size: clamp(1.75rem, 9vw, 2.6rem);
        }

        .cash-ntx-stats,
        .cash-ntx-side,
        .cash-ntx-categories {
            grid-template-columns: 1fr;
        }

        .cash-ntx-shortcuts {
            grid-template-columns: 1fr;
        }

        .cash-ntx-shortcuts-panel {
            grid-column: auto;
        }

        .cash-ntx-inbox-head {
            grid-template-columns: 1fr;
        }

        .cash-ntx-row {
            grid-template-columns: 40px minmax(0, 1fr);
            padding: 13px;
        }

        .cash-ntx-icon {
            width: 40px;
            height: 40px;
        }

        .cash-ntx-amount {
            grid-column: 2;
            justify-self: start;
        }
    }
</style>

<!-- Panel Principal de Caja -->
<div class="lc-bg cash-page cash-command-page cash-control-room min-h-screen p-4 md:p-6">
    <main class="cash-ntx-shell">
        <header class="cash-ntx-top">
            <div class="cash-ntx-heading">
                <span class="cash-ntx-kicker">
                    <i class="fas fa-cash-register"></i>
                    <?= htmlspecialchars($cash_hotel_nombre) ?>
                </span>
                <h1 class="cash-ntx-title">Caja del hotel</h1>
                <p class="cash-ntx-subtitle">Turno actual, movimientos y accesos de caja en una lectura clara, compacta y respirada.</p>
            </div>

            <aside class="cash-ntx-live-card" aria-label="Resumen principal de caja">
                <span>Efectivo esperado</span>
                <strong>$<?= number_format($resumen['efectivo_en_caja'] ?? 0, 2) ?></strong>
                <small><?= htmlspecialchars($cash_caja_nombre) ?> - abierta <?= htmlspecialchars($cash_hora_apertura_label) ?></small>
            </aside>
        </header>

        <section class="cash-ntx-stats" aria-label="Resumen de caja">
            <div class="cash-ntx-stat is-initial">
                <span>Monto inicial</span>
                <strong>$<?= number_format($resumen['monto_inicial'] ?? 0, 2) ?></strong>
            </div>
            <div class="cash-ntx-stat is-income">
                <span>Ingresos</span>
                <strong class="is-income">+$<?= number_format($resumen['ingresos']['total'] ?? 0, 2) ?></strong>
            </div>
            <div class="cash-ntx-stat is-expense">
                <span>Gastos</span>
                <strong class="is-expense">-$<?= number_format($resumen['gastos']['total'] ?? 0, 2) ?></strong>
            </div>
            <div class="cash-ntx-stat is-balance">
                <span>Balance</span>
                <strong class="<?= $cash_balance_general >= 0 ? 'is-income' : 'is-expense' ?>">
                    <?= $cash_balance_general >= 0 ? '+' : '-' ?>$<?= number_format(abs($cash_balance_general), 2) ?>
                </strong>
            </div>
        </section>

        <div class="cash-ntx-layout">
            <aside class="cash-ntx-side" aria-label="Controles de caja">
                <section class="cash-ntx-panel cash-ntx-actions-panel">
                    <div class="cash-ntx-panel-head">
                        <h2>Acciones</h2>
                        <p>Registra movimientos sin salir de caja.</p>
                    </div>
                    <div class="cash-ntx-actions">
                        <button type="button" onclick="mostrarModalIngreso()" class="cash-ntx-btn is-income">
                            <i class="fas fa-plus"></i>
                            <span>Ingreso</span>
                        </button>
                        <button type="button" onclick="mostrarModalGasto()" class="cash-ntx-btn is-expense">
                            <i class="fas fa-minus"></i>
                            <span>Gasto</span>
                        </button>
                        <a href="<?= url('caja/corte') ?>" class="cash-ntx-btn is-primary">
                            <i class="fas fa-scissors"></i>
                            <span>Corte</span>
                        </a>
                    </div>
                </section>

                <section class="cash-ntx-panel cash-ntx-methods-panel">
                    <div class="cash-ntx-panel-head">
                        <h2>Metodos</h2>
                        <p>Resumen por forma de pago.</p>
                    </div>
                    <div class="cash-ntx-methods">
                        <?php foreach ($cash_methods as $method_key => $method): ?>
                            <?php
                            $method_ingresos = (float) ($resumen['ingresos'][$method_key]['total'] ?? 0);
                            $method_gastos = (float) ($resumen['gastos'][$method_key]['total'] ?? 0);
                            $method_balance = $method_ingresos - $method_gastos;
                            ?>
                            <span class="cash-ntx-chip is-method-<?= htmlspecialchars($method_key) ?>">
                                <span>
                                    <i class="fas fa-<?= htmlspecialchars($method['icon']) ?>"></i>
                                    <?= htmlspecialchars($method['label']) ?>
                                </span>
                                <strong><?= $method_balance >= 0 ? '+' : '-' ?>$<?= number_format(abs($method_balance), 2) ?></strong>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="cash-ntx-panel cash-ntx-shortcuts-panel">
                    <div class="cash-ntx-panel-head">
                        <h2>Accesos</h2>
                        <p>Consulta rapida.</p>
                    </div>
                    <div class="cash-ntx-shortcuts">
                        <a href="<?= url('caja/movimientos') ?>" class="cash-ntx-shortcut is-movements">
                            <i class="fas fa-list"></i>
                            Movimientos
                        </a>
                        <a href="<?= url('caja/historial') ?>" class="cash-ntx-shortcut is-history">
                            <i class="fas fa-history"></i>
                            Historial
                        </a>
                        <a href="<?= url('caja/reporte-metodos') ?>" class="cash-ntx-shortcut is-methods">
                            <i class="fas fa-credit-card"></i>
                            Metodos
                        </a>
                        <?php if (user_role() == 'gerente'): ?>
                            <a href="<?= url('caja/categorias') ?>" class="cash-ntx-shortcut is-categories">
                                <i class="fas fa-tags"></i>
                                Categorias
                            </a>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>

            <section class="cash-ntx-panel cash-ntx-main" aria-label="Actividad de caja">
                <div class="cash-ntx-inbox-head">
                    <div>
                        <h2>Actividad</h2>
                        <p>Ultimos movimientos del corte actual.</p>
                    </div>
                    <span class="cash-ntx-count"><?= number_format($cash_total_movimientos) ?> movimientos</span>
                </div>

                <nav class="cash-ntx-tabs" aria-label="Resumen del corte">
                    <span class="cash-ntx-tab is-active">Turno abierto</span>
                    <span class="cash-ntx-tab">Responsable <?= htmlspecialchars($cash_responsable) ?></span>
                    <span class="cash-ntx-tab"><?= htmlspecialchars($cash_fecha_apertura_label) ?></span>
                </nav>

                <div class="cash-ntx-feed">
                    <?php if (empty($ultimos_movimientos)): ?>
                        <div class="cash-ntx-empty">
                            <div>
                                <i class="fas fa-receipt"></i>
                                <h3>Sin movimientos</h3>
                                <p>Cuando registres ingresos o gastos, apareceran en esta bandeja.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ultimos_movimientos as $mov): ?>
                            <?php
                            $tipo_mostrar = (string)($mov['tipo'] ?? '');
                            $es_ingreso = ($tipo_mostrar === 'ingreso');
                            $label_tipo = $es_ingreso ? 'Ingreso' : (($tipo_mostrar === 'egreso') ? 'Devolucion' : 'Gasto');
                            $metodoPago = $metodos_pago[$mov['metodo_pago'] ?? ''] ?? [];
                            $metodoKey = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)($mov['metodo_pago'] ?? '')));
                            $metodoLabel = $metodoPago['label'] ?? ucfirst((string)($mov['metodo_pago'] ?? 'metodo'));
                            $metodoIcon = $metodoPago['icon'] ?? 'circle';
                            $movHora = !empty($mov['created_at']) ? date('H:i', strtotime($mov['created_at'])) : '--:--';
                            ?>
                            <article class="cash-ntx-row <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                                <div class="cash-ntx-icon" aria-hidden="true">
                                    <i class="fas fa-<?= $es_ingreso ? 'arrow-down' : 'arrow-up' ?>"></i>
                                </div>
                                <div>
                                    <div class="cash-ntx-row-top">
                                        <span class="cash-ntx-row-title"><?= htmlspecialchars($mov['descripcion'] ?? 'Movimiento sin descripcion') ?></span>
                                        <span class="cash-ntx-badge <?= $es_ingreso ? 'is-income' : 'is-expense' ?>"><?= htmlspecialchars($label_tipo) ?></span>
                                        <span class="cash-ntx-badge <?= $metodoKey !== '' ? 'is-method-' . htmlspecialchars($metodoKey, ENT_QUOTES, 'UTF-8') : '' ?>"><?= htmlspecialchars($metodoLabel) ?></span>
                                    </div>
                                    <p class="cash-ntx-message">
                                        <?= htmlspecialchars($mov['categoria_nombre'] ?? 'Sin categoria') ?>
                                    </p>
                                    <div class="cash-ntx-meta">
                                        <span><i class="far fa-clock"></i> <?= htmlspecialchars($movHora) ?></span>
                                        <span><i class="fas fa-<?= htmlspecialchars($metodoIcon) ?>"></i> <?= htmlspecialchars($metodoLabel) ?></span>
                                        <?php if (!empty($mov['reservacion_id'])): ?>
                                            <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>">
                                                <i class="fas fa-bed"></i> Reserva #<?= (int)$mov['reservacion_id'] ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <strong class="cash-ntx-amount <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                                    <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                                </strong>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <section class="cash-ntx-categories">
            <article class="cash-ntx-panel">
                <div class="cash-ntx-panel-head">
                    <h2>Ingresos por categoria</h2>
                    <p>Total <?= '$' . number_format($resumen['ingresos']['total'] ?? 0, 2) ?></p>
                </div>
                <div class="cash-ntx-cat-list">
                    <?php if (empty($movimientos_categoria['ingresos'])): ?>
                        <div class="cash-ntx-empty">
                            <div>
                                <i class="fas fa-circle-check"></i>
                                <h3>Sin ingresos</h3>
                                <p>No hay ingresos registrados en este corte.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                            <div class="cash-ntx-cat">
                                <div class="cash-ntx-cat-main">
                                    <span class="cash-ntx-cat-icon" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                        <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?>" style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                    </span>
                                    <div>
                                        <strong><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoria') ?></strong>
                                        <small><?= number_format($cat['cantidad'] ?? 0) ?> movimientos</small>
                                    </div>
                                </div>
                                <span class="cash-ntx-money is-income">+$<?= number_format($cat['total'] ?? 0, 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <article class="cash-ntx-panel">
                <div class="cash-ntx-panel-head">
                    <h2>Gastos por categoria</h2>
                    <p>Total <?= '$' . number_format($resumen['gastos']['total'] ?? 0, 2) ?></p>
                </div>
                <div class="cash-ntx-cat-list">
                    <?php if (empty($movimientos_categoria['gastos'])): ?>
                        <div class="cash-ntx-empty">
                            <div>
                                <i class="fas fa-circle-check"></i>
                                <h3>Sin gastos</h3>
                                <p>No hay gastos registrados en este corte.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                            <div class="cash-ntx-cat">
                                <div class="cash-ntx-cat-main">
                                    <span class="cash-ntx-cat-icon" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                        <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?>" style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                    </span>
                                    <div>
                                        <strong><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoria') ?></strong>
                                        <small><?= number_format($cat['cantidad'] ?? 0) ?> movimientos</small>
                                    </div>
                                </div>
                                <span class="cash-ntx-money is-expense">-$<?= number_format($cat['total'] ?? 0, 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>

    <main class="cc-shell">
        <section class="cc-hero" aria-label="Estado principal de caja">
            <div class="cc-hero-main">
                <span class="cc-state"><span class="cc-state-dot"></span> Caja abierta</span>
                <h1 class="cc-hero-title">
                    $<?= number_format($resumen['efectivo_en_caja'] ?? 0, 2) ?>
                    <span>Efectivo esperado en caja</span>
                </h1>
                <p class="cc-hero-copy">
                    Turno abierto a las <?= htmlspecialchars($cash_hora_apertura_label) ?>. Desde aqui puedes registrar cobros,
                    capturar salidas y preparar el corte sin perder de vista el saldo fisico.
                </p>
                <div class="cc-meta-grid" aria-label="Datos del corte actual">
                    <span class="cc-meta-pill">
                        <i class="fas fa-store"></i>
                        <?= htmlspecialchars($cash_caja_nombre) ?>
                    </span>
                    <span class="cc-meta-pill">
                        <i class="fas fa-user"></i>
                        <?= htmlspecialchars($cash_responsable) ?>
                    </span>
                    <span class="cc-meta-pill">
                        <i class="fas fa-clock"></i>
                        <?= htmlspecialchars($cash_fecha_apertura_label) ?>
                    </span>
                </div>
            </div>

            <aside class="cc-hero-side" aria-label="Acciones principales de caja">
                <div class="cc-primary-actions">
                    <button type="button" onclick="mostrarModalIngreso()" class="cc-action-btn is-income" title="Registrar un ingreso en caja">
                        <span>Registrar ingreso</span>
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" onclick="mostrarModalGasto()" class="cc-action-btn is-expense" title="Registrar un gasto de caja">
                        <span>Registrar gasto</span>
                        <i class="fas fa-minus"></i>
                    </button>
                    <a href="<?= url('caja/corte') ?>" class="cc-action-btn is-cut" title="Ir al corte de caja">
                        <span>Realizar corte</span>
                        <i class="fas fa-scissors"></i>
                    </a>
                </div>
            </aside>
        </section>

        <section class="cc-kpi-grid" aria-label="Resumen de caja">
            <article class="cc-kpi">
                <div class="cc-kpi-top">
                    <span>Monto inicial</span>
                    <span class="cc-kpi-icon"><i class="fas fa-wallet"></i></span>
                </div>
                <div>
                    <div class="cc-kpi-value">$<?= number_format($resumen['monto_inicial'] ?? 0, 2) ?></div>
                    <p class="cc-kpi-note">Base con la que se abrio el turno.</p>
                </div>
            </article>
            <article class="cc-kpi">
                <div class="cc-kpi-top">
                    <span>Ingresos</span>
                    <span class="cc-kpi-icon"><i class="fas fa-arrow-trend-up"></i></span>
                </div>
                <div>
                    <div class="cc-kpi-value is-income">+$<?= number_format($resumen['ingresos']['total'] ?? 0, 2) ?></div>
                    <p class="cc-kpi-note">Cobros registrados en el corte actual.</p>
                </div>
            </article>
            <article class="cc-kpi">
                <div class="cc-kpi-top">
                    <span>Gastos</span>
                    <span class="cc-kpi-icon"><i class="fas fa-arrow-trend-down"></i></span>
                </div>
                <div>
                    <div class="cc-kpi-value is-expense">-$<?= number_format($resumen['gastos']['total'] ?? 0, 2) ?></div>
                    <p class="cc-kpi-note">Salidas capturadas durante el turno.</p>
                </div>
            </article>
            <article class="cc-kpi">
                <div class="cc-kpi-top">
                    <span>Balance</span>
                    <span class="cc-kpi-icon"><i class="fas fa-scale-balanced"></i></span>
                </div>
                <div>
                    <div class="cc-kpi-value <?= $cash_balance_general >= 0 ? 'is-income' : 'is-expense' ?>">
                        <?= $cash_balance_general >= 0 ? '+' : '-' ?>$<?= number_format(abs($cash_balance_general), 2) ?>
                    </div>
                    <p class="cc-kpi-note"><?= number_format($cash_total_movimientos) ?> movimientos registrados.</p>
                </div>
            </article>
        </section>

        <section class="cc-workspace">
            <article class="cc-panel">
                <div class="cc-panel-head">
                    <div>
                        <span class="cc-panel-kicker">Metodos de pago</span>
                        <h2 class="cc-panel-title">Distribucion del turno</h2>
                    </div>
                    <a href="<?= url('caja/reporte-metodos') ?>" class="cc-panel-link">
                        Ver reporte <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="cc-panel-body">
                    <?php if ($cash_ing_total_metodos > 0): ?>
                        <div class="cc-method-bar" aria-label="Distribucion de ingresos por metodo">
                            <span style="width: <?= $cash_pct_efectivo ?>%;"></span>
                            <span style="width: <?= $cash_pct_tarjeta ?>%;"></span>
                            <span style="width: <?= $cash_pct_transferencia ?>%;"></span>
                        </div>
                    <?php endif; ?>
                    <div class="cc-method-list">
                        <?php foreach ($cash_methods as $method_key => $method): ?>
                            <?php
                            $method_ingresos = (float) ($resumen['ingresos'][$method_key]['total'] ?? 0);
                            $method_gastos = (float) ($resumen['gastos'][$method_key]['total'] ?? 0);
                            $method_balance = $method_ingresos - $method_gastos;
                            $method_count = (int) ($resumen['ingresos'][$method_key]['cantidad'] ?? 0)
                                + (int) ($resumen['gastos'][$method_key]['cantidad'] ?? 0);
                            ?>
                            <div class="cc-method-card is-<?= htmlspecialchars($method['class']) ?>">
                                <div class="cc-method-title">
                                    <i class="fas fa-<?= htmlspecialchars($method['icon']) ?>"></i>
                                    <?= htmlspecialchars($method['label']) ?>
                                </div>
                                <p class="cc-method-note"><?= htmlspecialchars($method['note']) ?> · <?= number_format($method_count) ?> mov.</p>
                                <div class="cc-method-row">
                                    <span>Ingresos</span>
                                    <span class="cc-money is-income">+$<?= number_format($method_ingresos, 2) ?></span>
                                </div>
                                <div class="cc-method-row">
                                    <span>Gastos</span>
                                    <span class="cc-money is-expense">-$<?= number_format($method_gastos, 2) ?></span>
                                </div>
                                <div class="cc-method-row">
                                    <span>Balance</span>
                                    <span class="cc-money <?= $method_balance >= 0 ? 'is-income' : 'is-expense' ?>">
                                        <?= $method_balance >= 0 ? '+' : '-' ?>$<?= number_format(abs($method_balance), 2) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>

            <aside class="cash-movements-panel">
                <div class="cash-movements-head">
                    <div class="cash-movements-icon">
                        <i class="fas fa-receipt" aria-hidden="true"></i>
                    </div>
                    <div class="cash-movements-title">
                        <h3>Ultimos movimientos</h3>
                        <p>Actividad reciente del corte actual.</p>
                    </div>
                    <a href="<?= url('caja/movimientos') ?>" class="cash-view-link" title="Ver todos los movimientos de caja">
                        Ver todos <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="cash-movements-list custom-scroll">
                    <?php if (empty($ultimos_movimientos)): ?>
                        <div class="cash-movement-empty">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <p class="text-sm">No hay movimientos registrados en este corte.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ultimos_movimientos as $mov): ?>
                            <?php
                            $tipo_mostrar = (string)($mov['tipo'] ?? '');
                            $es_ingreso = ($tipo_mostrar === 'ingreso');
                            $icono_flecha = $es_ingreso ? 'down' : 'up';
                            $label_tipo = ucfirst($tipo_mostrar !== '' ? $tipo_mostrar : 'movimiento');
                            if ($tipo_mostrar === 'egreso') {
                                $label_tipo = 'Devolucion';
                            }
                            $metodoPago = $metodos_pago[$mov['metodo_pago'] ?? ''] ?? [];
                            $metodoLabel = $metodoPago['label'] ?? ucfirst((string)($mov['metodo_pago'] ?? 'metodo'));
                            $metodoIcon = $metodoPago['icon'] ?? 'circle';
                            $movHora = !empty($mov['created_at']) ? date('H:i', strtotime($mov['created_at'])) : '--:--';
                            ?>
                            <article class="cash-movement-item <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                                <div class="cash-movement-mark" aria-hidden="true">
                                    <i class="fas fa-arrow-<?= $icono_flecha ?>"></i>
                                </div>
                                <div class="cash-movement-main">
                                    <div class="cash-movement-top">
                                        <span class="cash-movement-type">
                                            <i class="fas fa-arrow-<?= $icono_flecha ?>" aria-hidden="true"></i>
                                            <?= htmlspecialchars($label_tipo) ?>
                                        </span>
                                        <span class="cash-movement-method">
                                            <i class="fas fa-<?= htmlspecialchars($metodoIcon) ?>" aria-hidden="true"></i>
                                            <?= htmlspecialchars($metodoLabel) ?>
                                        </span>
                                        <span class="cash-movement-time">
                                            <i class="far fa-clock" aria-hidden="true"></i>
                                            <?= htmlspecialchars($movHora) ?>
                                        </span>
                                    </div>
                                    <p class="cash-movement-desc">
                                        <?= htmlspecialchars($mov['descripcion'] ?? 'Movimiento sin descripcion') ?>
                                    </p>
                                    <div class="cash-movement-meta">
                                        <?php if (!empty($mov['categoria_nombre'])): ?>
                                            <span class="cash-movement-tag">
                                                <i class="<?= htmlspecialchars($mov['categoria_icono'] ?? 'fas fa-tag') ?>"
                                                   style="color: <?= htmlspecialchars($mov['categoria_color'] ?? '#9CA3AF') ?>" aria-hidden="true"></i>
                                                <?= htmlspecialchars($mov['categoria_nombre']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($mov['reservacion_id'])): ?>
                                            <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>"
                                               class="cash-reservation-link"
                                               title="Ver reservacion #<?= htmlspecialchars($mov['reservacion_id']) ?>">
                                                <i class="fas fa-bed text-[10px]" aria-hidden="true"></i>
                                                Reserva #<?= (int)$mov['reservacion_id'] ?>
                                                <i class="fas fa-arrow-right text-[10px] cash-reservation-arrow" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="cash-movement-amount">
                                    <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>
        </section>

        <section class="cc-bottom-grid">
            <article class="cc-panel">
                <div class="cc-panel-head">
                    <div>
                        <span class="cc-panel-kicker">Ingresos</span>
                        <h2 class="cc-panel-title">Por categoria</h2>
                    </div>
                    <span class="cc-money is-income">+$<?= number_format($resumen['ingresos']['total'] ?? 0, 2) ?></span>
                </div>
                <div class="cc-panel-body">
                    <?php if (empty($movimientos_categoria['ingresos'])): ?>
                        <div class="cc-empty">Sin ingresos registrados en este corte.</div>
                    <?php else: ?>
                        <div class="cc-category-list">
                            <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                <div class="cc-category-item">
                                    <div class="cc-category-main">
                                        <span class="cc-category-icon" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?>"
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </span>
                                        <div>
                                            <p class="cc-category-title"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoria') ?></p>
                                            <p class="cc-category-sub"><?= number_format($cat['cantidad'] ?? 0) ?> movimientos</p>
                                        </div>
                                    </div>
                                    <span class="cc-money is-income">+$<?= number_format($cat['total'] ?? 0, 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <article class="cc-panel">
                <div class="cc-panel-head">
                    <div>
                        <span class="cc-panel-kicker">Gastos</span>
                        <h2 class="cc-panel-title">Por categoria</h2>
                    </div>
                    <span class="cc-money is-expense">-$<?= number_format($resumen['gastos']['total'] ?? 0, 2) ?></span>
                </div>
                <div class="cc-panel-body">
                    <?php if (empty($movimientos_categoria['gastos'])): ?>
                        <div class="cc-empty">Sin gastos registrados en este corte.</div>
                    <?php else: ?>
                        <div class="cc-category-list">
                            <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                                <div class="cc-category-item">
                                    <div class="cc-category-main">
                                        <span class="cc-category-icon" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?>"
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </span>
                                        <div>
                                            <p class="cc-category-title"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoria') ?></p>
                                            <p class="cc-category-sub"><?= number_format($cat['cantidad'] ?? 0) ?> movimientos</p>
                                        </div>
                                    </div>
                                    <span class="cc-money is-expense">-$<?= number_format($cat['total'] ?? 0, 2) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>

            <nav class="cc-shortcuts" aria-label="Accesos rapidos de caja">
                <span class="cc-shortcuts-label">Accesos rapidos</span>
                <a href="<?= url('caja/movimientos') ?>" class="cc-shortcut-link">
                    <i class="fas fa-list"></i> Movimientos
                </a>
                <a href="<?= url('caja/historial') ?>" class="cc-shortcut-link">
                    <i class="fas fa-history"></i> Historial
                </a>
                <a href="<?= url('caja/reporte-metodos') ?>" class="cc-shortcut-link">
                    <i class="fas fa-credit-card"></i> Metodos
                </a>
                <?php if (user_role() == 'gerente'): ?>
                    <a href="<?= url('caja/categorias') ?>" class="cc-shortcut-link">
                        <i class="fas fa-tags"></i> Categorias
                    </a>
                <?php endif; ?>
            </nav>
        </section>
    </main>
    <!-- Header Principal -->
    <div class="cash-hero-wrap max-w-7xl mx-auto mb-6">
        <div class="header-card rounded-2xl shadow-lg p-5 md:p-7">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="cash-header-icon w-9 h-9 rounded-xl flex items-center justify-center">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">
                            Sistema de Caja
                        </h1>
                    </div>
                    <p class="text-xs md:text-sm text-white/70 ml-12 mb-2 max-w-md">
                        Aquí ves cuánto dinero entró y salió en este turno, y registras nuevos ingresos o gastos.
                    </p>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs ml-12">
                        <span class="cash-meta-pill flex items-center gap-1.5 px-2.5 py-1 rounded-full">
                            <i class="fas fa-store text-xs"></i>
                            <?= htmlspecialchars($caja['nombre'] ?? '') ?>
                        </span>
                        <span class="cash-meta-pill flex items-center gap-1.5 px-2.5 py-1 rounded-full">
                            <i class="fas fa-user text-xs"></i>
                            <?= htmlspecialchars($corte['usuario_apertura'] ?? '') ?>
                        </span>
                        <span class="cash-meta-pill flex items-center gap-1.5 px-2.5 py-1 rounded-full">
                            <i class="fas fa-clock text-xs"></i>
                            Abierta: <?= date('d/m/Y H:i', strtotime($corte['fecha_apertura'])) ?>
                        </span>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="flex flex-wrap gap-2.5">
                    <button type="button" onclick="mostrarModalIngreso()" class="btn-ingreso" title="Registrar un ingreso en caja">
                        <i class="fas fa-plus-circle text-sm"></i>
                        <span class="hidden sm:inline">Registrar Ingreso</span>
                        <span class="sm:hidden">Ingreso</span>
                    </button>
                    <button type="button" onclick="mostrarModalGasto()" class="btn-gasto" title="Registrar un gasto de caja">
                        <i class="fas fa-minus-circle text-sm"></i>
                        <span class="hidden sm:inline">Registrar Gasto</span>
                        <span class="sm:hidden">Gasto</span>
                    </button>
                    <a href="<?= url('caja/corte') ?>" class="btn-corte" title="Ir al corte de caja">
                        <i class="fas fa-scissors text-sm"></i>
                        <span class="hidden sm:inline">Realizar Corte</span>
                        <span class="sm:hidden">Corte</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de la caja -->
    <div class="cash-status-wrap max-w-7xl mx-auto mb-6">
        <div class="caja-status">
            <div class="caja-status-ic"><i class="fas fa-lock-open"></i></div>
            <div class="caja-status-meta">
                <div class="t">Caja abierta</div>
                <div class="d">
                    <span class="caja-pulse"><span class="led"></span> En operación</span>
                    <?php if (!empty($corte['fecha_apertura'])): ?>
                        <span>·</span><span>Abierta <b><?= date('H:i', strtotime($corte['fecha_apertura'])) ?></b></span>
                    <?php endif; ?>
                    <?php if (!empty($corte['usuario_apertura'])): ?>
                        <span>·</span><span>Responsable <b><?= htmlspecialchars($corte['usuario_apertura']) ?></b></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="caja-status-right">
                <div class="l">Saldo esperado en efectivo</div>
                <div class="v">$<?= number_format($resumen['efectivo_en_caja'] ?? 0, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Resumen de Caja -->
    <div class="cash-summary-wrap max-w-7xl mx-auto mb-6">
        <!-- Primera fila - Resumen General -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
            <!-- Monto Inicial -->
            <div class="stat-card card-inicial shadow-sm p-5 border border-[#E5EDE0]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Monto Inicial</p>
                        <p class="text-2xl font-bold" style="color:var(--cash-heading);">
                            $<?= number_format($resumen['monto_inicial'], 2) ?>
                        </p>
                        <p class="stat-help">Con lo que se abrió la caja en este turno</p>
                    </div>
                    <div class="icon-circle green-ic">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <!-- Total Ingresos -->
            <div class="stat-card card-ingresos shadow-sm p-5 border border-[#D1F0E3]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Ingresos</p>
                        <p class="text-2xl font-bold text-emerald-600">
                            +$<?= number_format($resumen['ingresos']['total'], 2) ?>
                        </p>
                        <p class="stat-help">Todo lo que ha entrado: efectivo, tarjeta y transferencia</p>
                    </div>
                    <div class="icon-circle emerald-ic">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                </div>
            </div>

            <!-- Total Gastos -->
            <div class="stat-card card-gastos shadow-sm p-5 border border-[#FDD9D9]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Gastos</p>
                        <p class="text-2xl font-bold text-red-500">
                            -$<?= number_format($resumen['gastos']['total'], 2) ?>
                        </p>
                        <p class="stat-help">Todo lo que ha salido de la caja en este turno</p>
                    </div>
                    <div class="icon-circle red-ic">
                        <i class="fas fa-arrow-trend-down"></i>
                    </div>
                </div>
            </div>

            <!-- Efectivo en Caja -->
            <div class="card-efectivo shadow-lg p-5">
                <div class="flex items-center justify-between relative z-10">
                    <div>
                        <p class="text-xs font-semibold text-white/70 uppercase tracking-wider mb-1">Efectivo en Caja</p>
                        <p class="text-3xl font-bold text-white">
                            $<?= number_format($resumen['efectivo_en_caja'], 2) ?>
                        </p>
                        <p class="text-xs text-white/60 mt-1 flex items-center gap-1">
                            <i class="fas fa-info-circle text-[10px]"></i> Solo efectivo físico
                        </p>
                    </div>
                    <div class="bg-white/15 w-12 h-12 rounded-xl flex items-center justify-center relative z-10">
                        <i class="fas fa-money-bill-wave text-white text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda fila - Desglose por Método de Pago -->
        <div class="bg-white rounded-xl shadow-sm p-5 border border-[#E5EDE0]">
            <h3 class="text-sm font-bold mb-4 flex items-center gap-2" style="color:var(--cash-heading);">
                <div class="w-7 h-7 rounded-lg bg-[#EEF4EB] flex items-center justify-center">
                    <i class="fas fa-credit-card text-xs" style="color:var(--cash-navy);"></i>
                </div>
                Resumen por Método de Pago
            </h3>

            <?php if ($cash_ing_total_metodos > 0): ?>
                <p class="stat-help mb-1">Así se reparten los ingresos de este turno:</p>
                <div class="method-bar">
                    <?php if ($cash_pct_efectivo > 0): ?>
                        <div class="method-bar-seg seg-efectivo" style="width: <?= $cash_pct_efectivo ?>%;" title="Efectivo: <?= $cash_pct_efectivo ?>%"></div>
                    <?php endif; ?>
                    <?php if ($cash_pct_tarjeta > 0): ?>
                        <div class="method-bar-seg seg-tarjeta" style="width: <?= $cash_pct_tarjeta ?>%;" title="Tarjeta: <?= $cash_pct_tarjeta ?>%"></div>
                    <?php endif; ?>
                    <?php if ($cash_pct_transferencia > 0): ?>
                        <div class="method-bar-seg seg-transferencia" style="width: <?= $cash_pct_transferencia ?>%;" title="Transferencia: <?= $cash_pct_transferencia ?>%"></div>
                    <?php endif; ?>
                </div>
                <div class="method-bar-legend">
                    <span><span class="dot seg-efectivo"></span> Efectivo <?= $cash_pct_efectivo ?>%</span>
                    <span><span class="dot seg-tarjeta"></span> Tarjeta <?= $cash_pct_tarjeta ?>%</span>
                    <span><span class="dot seg-transferencia"></span> Transferencia <?= $cash_pct_transferencia ?>%</span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Efectivo -->
                <div class="metodo-card mc-efectivo p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-money-bill-wave text-emerald-600"></i>
                            Efectivo
                        </h4>
                        <span class="text-xs bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-medium">
                            Afecta caja
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['efectivo']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['efectivo']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['efectivo']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['efectivo']['total'], 2) ?>
                                <?php if ($resumen['gastos']['efectivo']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['efectivo']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-emerald-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['efectivo']['total'] - $resumen['gastos']['efectivo']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['efectivo']['total'] - $resumen['gastos']['efectivo']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta -->
                <div class="metodo-card mc-tarjeta p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-credit-card text-blue-500"></i>
                            Tarjeta
                        </h4>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                            No física
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['tarjeta']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['tarjeta']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['tarjeta']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['tarjeta']['total'], 2) ?>
                                <?php if ($resumen['gastos']['tarjeta']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['tarjeta']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-blue-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['tarjeta']['total'] - $resumen['gastos']['tarjeta']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['tarjeta']['total'] - $resumen['gastos']['tarjeta']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transferencia -->
                <div class="metodo-card mc-transfer p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="font-semibold text-gray-700 text-sm flex items-center gap-2">
                            <i class="fas fa-exchange-alt text-violet-500"></i>
                            Transferencia
                        </h4>
                        <span class="text-xs bg-violet-100 text-violet-700 px-2 py-0.5 rounded-full font-medium">
                            No física
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Ingresos</span>
                            <span class="font-semibold text-emerald-600">
                                +$<?= number_format($resumen['ingresos']['transferencia']['total'], 2) ?>
                                <?php if ($resumen['ingresos']['transferencia']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['ingresos']['transferencia']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Gastos</span>
                            <span class="font-semibold text-red-500">
                                -$<?= number_format($resumen['gastos']['transferencia']['total'], 2) ?>
                                <?php if ($resumen['gastos']['transferencia']['cantidad'] > 0): ?>
                                    <span class="text-xs text-gray-400 font-normal">(<?= $resumen['gastos']['transferencia']['cantidad'] ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="pt-2 border-t border-violet-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-600 text-sm">Balance</span>
                                <span class="font-bold text-base <?= ($resumen['ingresos']['transferencia']['total'] - $resumen['gastos']['transferencia']['total']) >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                                    $<?= number_format($resumen['ingresos']['transferencia']['total'] - $resumen['gastos']['transferencia']['total'], 2) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Totales Generales -->
            <div class="mt-4">
                <div class="balance-bar flex items-center justify-between flex-wrap gap-3">
                    <div>
                        <h4 class="font-bold text-sm" style="color:var(--cash-heading);">Balance General</h4>
                        <p class="text-xs text-gray-400 mt-0.5">Todos los métodos de pago</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold <?= $resumen['balance_general'] >= 0 ? 'text-emerald-600' : 'text-red-500' ?>">
                            <?= $resumen['balance_general'] >= 0 ? '+' : '' ?>$<?= number_format($resumen['balance_general'], 2) ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            <span class="text-emerald-600 font-medium">↑ $<?= number_format($resumen['ingresos']['total'], 2) ?></span>
                            <span class="mx-1 text-gray-300">|</span>
                            <span class="text-red-500 font-medium">↓ $<?= number_format($resumen['gastos']['total'], 2) ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="cash-activity-wrap max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Columna Izquierda - Movimientos por Categoría -->
        <div class="lg:col-span-2 space-y-5">
            <!-- Ingresos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#D9EDD1] overflow-hidden">
                <div class="section-header sh-ingresos rounded-t-xl">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xs"></i>
                    </div>
                    Ingresos por Categoría
                </div>
                <div class="p-4">
                    <?php if (empty($movimientos_categoria['ingresos'])): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">No hay ingresos registrados hoy. Cuando captures cobros, aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($movimientos_categoria['ingresos'] as $cat): ?>
                                <div class="cat-item">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0"
                                             style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?> text-sm"
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></p>
                                            <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?? 0 ?> movimiento<?= ($cat['cantidad'] ?? 0) != 1 ? 's' : '' ?></p>
                                        </div>
                                    </div>
                                    <p class="font-bold text-emerald-600 text-sm">
                                        +$<?= number_format($cat['total'] ?? 0, 2) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Gastos por Categoría -->
            <div class="bg-white rounded-xl shadow-sm border border-[#FDDEDE] overflow-hidden">
                <div class="section-header sh-gastos rounded-t-xl">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-pie text-white text-xs"></i>
                    </div>
                    Gastos por Categoría
                </div>
                <div class="p-4">
                    <?php if (empty($movimientos_categoria['gastos'])): ?>
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-40"></i>
                            <p class="text-sm">No hay gastos registrados hoy. Los gastos de la jornada aparecerán aquí.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($movimientos_categoria['gastos'] as $cat): ?>
                                <div class="cat-item" style="background:#FFF8F8; border-color:#FDDEDE;">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 flex items-center justify-center rounded-lg flex-shrink-0"
                                             style="background-color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>18;">
                                            <i class="<?= htmlspecialchars($cat['icono'] ?? 'fas fa-tag') ?> text-sm"
                                               style="color: <?= htmlspecialchars($cat['color'] ?? '#6B7280') ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($cat['categoria'] ?? 'Sin categoría') ?></p>
                                            <p class="text-xs text-gray-400"><?= $cat['cantidad'] ?? 0 ?> movimiento<?= ($cat['cantidad'] ?? 0) != 1 ? 's' : '' ?></p>
                                        </div>
                                    </div>
                                    <p class="font-bold text-red-500 text-sm">
                                        -$<?= number_format($cat['total'] ?? 0, 2) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enlaces Rápidos -->
            <div class="cash-shortcuts-wrap">
                <div class="bg-white rounded-xl shadow-sm p-5 border border-[#E5EDE0]">
                    <h3 class="text-sm font-bold mb-4 flex items-center gap-2" style="color:var(--cash-heading);">
                        <div class="w-7 h-7 rounded-lg bg-[#EEF4EB] flex items-center justify-center">
                            <i class="fas fa-link text-xs" style="color:var(--cash-navy);"></i>
                        </div>
                        Accesos Rápidos
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <a href="<?= url('caja/movimientos') ?>" class="quick-link" title="Ver todos los movimientos">
                            <div class="quick-link-icon" style="background: rgba(139,92,246,0.1);">
                                <i class="fas fa-list text-violet-600"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Movimientos</p>
                                <p class="text-xs text-gray-400">Ver todos</p>
                            </div>
                            <i class="fas fa-arrow-right quick-link-arrow ml-auto text-xs" aria-hidden="true"></i>
                        </a>
                        <a href="<?= url('caja/historial') ?>" class="quick-link" title="Ver historial de cortes">
                            <div class="quick-link-icon" style="background: rgba(59,130,246,0.1);">
                                <i class="fas fa-history text-blue-500"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Historial</p>
                                <p class="text-xs text-gray-400">Cortes anteriores</p>
                            </div>
                            <i class="fas fa-arrow-right quick-link-arrow ml-auto text-xs" aria-hidden="true"></i>
                        </a>
                        <a href="<?= url('caja/reporte-metodos') ?>" class="quick-link" title="Ver reporte por metodos de pago">
                            <div class="quick-link-icon" style="background: color-mix(in srgb,var(--cash-navy) 10%,transparent);">
                                <i class="fas fa-credit-card" style="color:var(--cash-navy);"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800 text-sm">Métodos de Pago</p>
                                <p class="text-xs text-gray-400">Reporte detallado</p>
                            </div>
                            <i class="fas fa-arrow-right quick-link-arrow ml-auto text-xs" aria-hidden="true"></i>
                        </a>
                        <?php if (user_role() == 'gerente'): ?>
                            <a href="<?= url('caja/categorias') ?>" class="quick-link" title="Configurar categorias de caja">
                                <div class="quick-link-icon" style="background: color-mix(in srgb,var(--cash-gold) 15%,transparent);">
                                    <i class="fas fa-tags" style="color:var(--cash-gold);"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">Categorías</p>
                                    <p class="text-xs text-gray-400">Configurar</p>
                                </div>
                                <i class="fas fa-arrow-right quick-link-arrow ml-auto text-xs" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha - Últimos Movimientos -->
        <div class="cash-movements-panel">
            <div class="cash-movements-head">
                <div class="cash-movements-icon">
                    <i class="fas fa-history" aria-hidden="true"></i>
                </div>
                <div class="cash-movements-title">
                    <h3>Últimos movimientos</h3>
                    <p>Actividad reciente del corte actual.</p>
                </div>
                <a href="<?= url('caja/movimientos') ?>"
                   class="cash-view-link"
                   title="Ver todos los movimientos de caja">
                    Ver todos <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                </a>
            </div>
            <div class="cash-movements-list custom-scroll">
                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="cash-movement-empty">
                        <i class="fas fa-receipt" aria-hidden="true"></i>
                        <p class="text-sm">No hay movimientos registrados en este corte. Registra un ingreso o gasto para ver actividad.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimos_movimientos as $mov): ?>
                        <?php
                        $tipo_mostrar = (string)($mov['tipo'] ?? '');
                        $es_ingreso = ($tipo_mostrar === 'ingreso');
                        $icono_flecha = $es_ingreso ? 'down' : 'up';
                        $label_tipo = ucfirst($tipo_mostrar !== '' ? $tipo_mostrar : 'movimiento');
                        if ($tipo_mostrar === 'egreso') {
                            $label_tipo = 'Devolución';
                        }
                        $metodoPago = $metodos_pago[$mov['metodo_pago'] ?? ''] ?? [];
                        $metodoLabel = $metodoPago['label'] ?? ucfirst((string)($mov['metodo_pago'] ?? 'metodo'));
                        $metodoIcon = $metodoPago['icon'] ?? 'circle';
                        $movHora = !empty($mov['created_at']) ? date('H:i', strtotime($mov['created_at'])) : '--:--';
                        ?>
                        <article class="cash-movement-item <?= $es_ingreso ? 'is-income' : 'is-expense' ?>">
                            <div class="cash-movement-mark" aria-hidden="true">
                                <i class="fas fa-arrow-<?= $icono_flecha ?>"></i>
                            </div>

                            <div class="cash-movement-main">
                                <div class="cash-movement-top">
                                    <span class="cash-movement-type">
                                        <i class="fas fa-arrow-<?= $icono_flecha ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($label_tipo) ?>
                                    </span>
                                    <span class="cash-movement-method">
                                        <i class="fas fa-<?= htmlspecialchars($metodoIcon) ?>" aria-hidden="true"></i>
                                        <?= htmlspecialchars($metodoLabel) ?>
                                    </span>
                                    <span class="cash-movement-time">
                                        <i class="far fa-clock" aria-hidden="true"></i>
                                        <?= htmlspecialchars($movHora) ?>
                                    </span>
                                </div>

                                <p class="cash-movement-desc">
                                    <?= htmlspecialchars($mov['descripcion'] ?? 'Movimiento sin descripción') ?>
                                </p>

                                <div class="cash-movement-meta">
                                    <?php if (!empty($mov['categoria_nombre'])): ?>
                                        <span class="cash-movement-tag">
                                            <i class="<?= htmlspecialchars($mov['categoria_icono'] ?? 'fas fa-tag') ?>"
                                               style="color: <?= htmlspecialchars($mov['categoria_color'] ?? '#9CA3AF') ?>" aria-hidden="true"></i>
                                            <?= htmlspecialchars($mov['categoria_nombre']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($mov['reservacion_id'])): ?>
                                        <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>"
                                           class="cash-reservation-link"
                                           title="Ver reservacion #<?= htmlspecialchars($mov['reservacion_id']) ?>">
                                            <i class="fas fa-bed text-[10px]" aria-hidden="true"></i>
                                            Reserva #<?= (int)$mov['reservacion_id'] ?>
                                            <i class="fas fa-arrow-right text-[10px] cash-reservation-arrow" aria-hidden="true"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($mov['habitaciones_detalle'])): ?>
                                        <span class="cash-movement-tag">
                                            <i class="fas fa-door-open" aria-hidden="true"></i>
                                            <?= htmlspecialchars($mov['habitaciones_detalle']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($mov['editado'])): ?>
                                        <span class="cash-movement-edited">
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                            Editado
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="cash-movement-amount">
                                <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enlaces Rápidos -->
</div>

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
                    <!-- Categoría -->
                    <div class="cash-field">
                        <label class="modal-label">Categoría <span class="text-red-400">*</span></label>
                        <select name="categoria_id" id="categoria_ingreso" class="modal-input" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias['ingreso'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>">
                                    <?= htmlspecialchars($cat['nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div class="cash-field">
                        <label class="modal-label">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input"
                                  placeholder="Ej: Pago habitación 101" required></textarea>
                    </div>

                    <!-- Monto y Método de Pago -->
                    <div class="cash-modal-grid">
                        <div class="cash-field">
                            <label class="modal-label">Monto <span class="text-red-400">*</span></label>
                            <div class="cash-money-field">
                                <span class="cash-money-prefix">$</span>
                                <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                       class="modal-input" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="cash-field">
                            <label class="modal-label">Método de Pago <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_ingreso" class="modal-input" required>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($metodo['label'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Referencia -->
                    <div id="referencia_ingreso_div" class="cash-field hidden">
                        <label class="modal-label">Referencia/Autorización <span class="text-red-400">*</span></label>
                        <input type="text" name="referencia" class="modal-input" placeholder="Número de referencia">
                    </div>

                    <!-- Comprobante -->
                    <div class="cash-field">
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input" placeholder="Ej: Ticket #123">
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
                    <!-- Categoría -->
                    <div class="cash-field">
                        <label class="modal-label modal-label-red">Categoría <span class="text-red-400">*</span></label>
                        <select name="categoria_id" id="categoria_gasto" class="modal-input modal-input-red" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias['gasto'] as $cat): ?>
                                <option value="<?= $cat['id'] ?>" data-icono="<?= htmlspecialchars($cat['icono'] ?? '') ?>" data-color="<?= htmlspecialchars($cat['color'] ?? '') ?>">
                                    <?= htmlspecialchars($cat['nombre'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Descripción -->
                    <div class="cash-field">
                        <label class="modal-label modal-label-red">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input modal-input-red"
                                  placeholder="Ej: Compra de productos de limpieza" required></textarea>
                    </div>

                    <!-- Proveedor -->
                    <div class="cash-field">
                        <label class="modal-label">Proveedor/Beneficiario</label>
                        <input type="text" name="proveedor" class="modal-input"
                               placeholder="Nombre del proveedor">
                    </div>

                    <!-- Monto y Método de Pago -->
                    <div class="cash-modal-grid">
                        <div class="cash-field">
                            <label class="modal-label modal-label-red">Monto <span class="text-red-400">*</span></label>
                            <div class="cash-money-field">
                                <span class="cash-money-prefix">$</span>
                                <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                       class="modal-input modal-input-red"
                                       placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="cash-field">
                            <label class="modal-label modal-label-red">Método <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_gasto"
                                    class="modal-input modal-input-red" required>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>">
                                        <?= htmlspecialchars($metodo['label'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Referencia -->
                    <div id="referencia_gasto_div" class="cash-field hidden">
                        <label class="modal-label modal-label-red">
                            Referencia/Autorización <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="referencia" class="modal-input modal-input-red"
                               placeholder="Número de referencia">
                    </div>

                    <!-- Comprobante -->
                    <div class="cash-field">
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input"
                               placeholder="Ej: Factura #ABC123">
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

function abrirModalCaja(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }

    const sidebar = obtenerSidebarCaja();
    if (sidebar) {
        sidebar.style.display = 'none';
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalCaja(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) {
        return;
    }

    const sidebar = obtenerSidebarCaja();
    if (sidebar) {
        sidebar.style.display = '';
    }

    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    const form = modal.querySelector('form');
    if (form) {
        form.reset();
    }
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
</script>
