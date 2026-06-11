<?php
/**
 * Vista Principal de Caja
 * Vista hotelera
 */
?>

<!-- Sistema de diseño "Caja boutique" -->
<style>
    :root {
        --cash-navy: var(--brand-primary, #1B2746);
        --cash-navy-deep: var(--brand-secondary, #0F172A);
        --cash-gold: var(--brand-accent, #BD9441);

        --cash-paper: color-mix(in srgb, var(--cash-gold) 4%, #F8F5ED);
        --cash-paper-2: color-mix(in srgb, var(--cash-gold) 3%, #FBF9F4);
        --cash-surface: #FFFFFF;
        --cash-surface-soft: color-mix(in srgb, var(--cash-gold) 3%, #FFFFFF);

        --cash-border: color-mix(in srgb, var(--cash-navy) 6%, #E7DEC9);
        --cash-border-soft: color-mix(in srgb, var(--cash-navy) 4%, #F0ECE2);

        --cash-heading: #111827;
        --cash-ink: #1F2937;
        --cash-muted: #667085;

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
    }

    .cash-page .cash-header-icon {
        background: color-mix(in srgb, var(--cash-gold) 28%, rgba(255,255,255,.12)) !important;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
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
        color: rgba(255,255,255,.6);
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
        color: #FFFFFF !important;
        font-weight: 800;
        letter-spacing: 0;
        box-shadow:
            inset 0 1px 0 rgba(255,255,255,.22),
            0 8px 20px -18px rgba(0,0,0,.65);
        text-shadow: 0 1px 1px rgba(0,0,0,.22);
        backdrop-filter: blur(6px);
    }

    .cash-page .header-card .cash-meta-pill i {
        color: #FFFFFF !important;
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
        color: #fff !important;
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
        background: var(--cash-heading) !important;
        color: #fff !important;
        border-color: color-mix(in srgb, var(--cash-gold) 38%, var(--cash-heading)) !important;
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
        background: rgba(255,255,255,.12) !important;
        color: #fff !important;
        border: 1px solid rgba(255,255,255,.16);
        box-shadow: none !important;
    }

    .cash-command-page .caja-status-meta {
        align-self: center;
        min-width: 0;
    }

    .cash-command-page .caja-status-meta .t {
        color: #fff !important;
        font-family: var(--cash-sans);
        font-size: clamp(1.4rem, 1.8vw, 2.05rem) !important;
        font-weight: 900 !important;
        letter-spacing: 0 !important;
    }

    .cash-command-page .caja-status-meta .d {
        color: rgba(255,255,255,.66) !important;
        margin-top: 9px !important;
        gap: 10px !important;
    }

    .cash-command-page .caja-status-meta .d b {
        color: #fff !important;
    }

    .cash-command-page .caja-pulse {
        color: #fff !important;
        background: rgba(31,157,99,.24) !important;
        border: 1px solid rgba(82,221,153,.26);
        border-radius: 8px !important;
    }

    .cash-command-page .caja-status-right {
        margin-left: 0 !important;
        padding: 14px 16px;
        border-radius: 12px;
        background: rgba(255,255,255,.11);
        border: 1px solid rgba(255,255,255,.16);
        text-align: left !important;
    }

    .cash-command-page .caja-status-right .l {
        color: rgba(255,255,255,.58) !important;
        letter-spacing: .08em !important;
    }

    .cash-command-page .caja-status-right .v {
        color: #fff !important;
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
        min-height: 144px !important;
        border-radius: var(--cash-radius-md) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: none !important;
        overflow: hidden;
    }

    .cash-command-page .stat-card {
        padding: 17px !important;
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
        height: 100%;
        align-items: flex-start !important;
    }

    .cash-command-page .stat-card p:first-child,
    .cash-command-page .card-efectivo p:first-child {
        font-size: .68rem !important;
        letter-spacing: .08em !important;
        color: color-mix(in srgb, var(--cash-muted) 86%, var(--cash-heading)) !important;
    }

    .cash-command-page .stat-card p:nth-child(2),
    .cash-command-page .card-efectivo p.text-3xl {
        margin-top: 5px;
        font-family: var(--cash-sans);
        font-size: clamp(1.6rem, 2vw, 2.2rem) !important;
        font-weight: 950 !important;
        line-height: 1 !important;
        letter-spacing: 0 !important;
    }

    .cash-command-page .stat-help {
        max-width: 26ch;
        margin-top: 12px !important;
        color: var(--cash-muted) !important;
        font-size: .76rem !important;
        line-height: 1.35 !important;
    }

    .cash-command-page .icon-circle,
    .cash-command-page .card-efectivo .bg-white\/15 {
        width: 42px !important;
        height: 42px !important;
        border-radius: 11px !important;
        box-shadow: none !important;
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
            linear-gradient(135deg, color-mix(in srgb, var(--cash-navy) 88%, #06110d), color-mix(in srgb, var(--cash-green) 58%, var(--cash-navy-deep))) !important;
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

    .cash-command-page .card-efectivo > .flex {
        width: 100%;
        align-items: flex-start !important;
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
        background: var(--cash-heading) !important;
        color: #fff !important;
        border-radius: 8px !important;
    }

    .cash-command-page .cash-summary-wrap > .bg-white > h3 i {
        color: #fff !important;
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
        grid-template-columns: minmax(0, 1.18fr) minmax(360px, .82fr) !important;
        gap: 16px !important;
        align-items: start !important;
    }

    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px !important;
        grid-column: auto !important;
    }

    .cash-command-page .cash-activity-wrap > .bg-white,
    .cash-command-page .cash-activity-wrap > .lg\:col-span-2 > .bg-white,
    .cash-command-page .cash-shortcuts-wrap > .bg-white {
        border-radius: var(--cash-radius-lg) !important;
        background: rgba(255,255,255,.92) !important;
        border: 1px solid var(--cash-line) !important;
        box-shadow: var(--cash-command-shadow) !important;
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
        background: color-mix(in srgb, var(--cash-gold) 74%, var(--cash-navy));
        opacity: .85;
    }

    .cash-command-page .section-header.sh-movs::after {
        display: none;
    }

    .cash-command-page .section-header .bg-white\/20 {
        width: 34px !important;
        height: 34px !important;
        border-radius: 9px !important;
        background: color-mix(in srgb, var(--cash-gold) 12%, #fff) !important;
        color: color-mix(in srgb, var(--cash-gold) 78%, var(--cash-heading)) !important;
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
        padding: 13px !important;
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
        padding: 16px 18px !important;
        border-bottom: 1px solid var(--cash-soft-line);
    }

    .cash-command-page .cash-shortcuts-wrap h3 > div {
        background: var(--cash-heading) !important;
        color: #fff !important;
    }

    .cash-command-page .cash-shortcuts-wrap h3 i {
        color: #fff !important;
    }

    .cash-command-page .cash-shortcuts-wrap .grid {
        padding: 14px !important;
        gap: 10px !important;
    }

    .cash-command-page .quick-link {
        min-height: 76px;
        padding: 13px !important;
        border-radius: 10px !important;
        background: #fff !important;
        border: 1px solid var(--cash-soft-line) !important;
        box-shadow: none !important;
    }

    .cash-command-page .quick-link-icon {
        width: 42px !important;
        height: 42px !important;
        border-radius: 10px !important;
        background: color-mix(in srgb, var(--cash-gold) 10%, #fff) !important;
        border: 1px solid var(--cash-soft-line) !important;
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
?>
<!-- Panel Principal de Caja -->
<div class="lc-bg cash-page cash-command-page min-h-screen p-4 md:p-6">
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
        <div class="bg-white rounded-xl shadow-sm border border-[#E0ECD8] overflow-hidden">
            <div class="section-header sh-movs rounded-t-xl">
                <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-history text-white text-xs"></i>
                </div>
                <span class="flex-1">Últimos Movimientos</span>
                <a href="<?= url('caja/movimientos') ?>"
                   class="cash-view-link text-white/70 hover:text-white text-xs transition flex items-center gap-1"
                   title="Ver todos los movimientos de caja">
                    Ver todos <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>
            <div class="custom-scroll divide-y-0 max-h-[600px] overflow-y-auto">
                <?php if (empty($ultimos_movimientos)): ?>
                    <div class="text-center py-10 text-gray-400">
                        <i class="fas fa-receipt text-3xl mb-2 opacity-40"></i>
                        <p class="text-sm">No hay movimientos registrados en este corte. Registra un ingreso o gasto para ver actividad.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ultimos_movimientos as $mov): ?>
    <div class="mov-item">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-1.5 mb-1.5">
                    <?php
                    $tipo_mostrar = $mov['tipo'];
                    $es_ingreso = ($tipo_mostrar == 'ingreso');
                    $color_badge = $es_ingreso ? 'green' : 'red';
                    $icono_flecha = $es_ingreso ? 'down' : 'up';
                    $label_tipo = ucfirst($tipo_mostrar);
                    if ($tipo_mostrar == 'egreso') { $label_tipo = 'Devolución'; }
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $es_ingreso ? 'badge-ingreso' : 'badge-gasto' ?>">
                        <i class="fas fa-arrow-<?= $icono_flecha ?> mr-1 text-[10px]"></i>
                        <?= $label_tipo ?>
                    </span>
                    <?php
                    $metodoPago = $metodos_pago[$mov['metodo_pago']] ?? [];
                    ?>
                    <span class="text-xs text-gray-400 flex items-center gap-1">
                        <i class="fas fa-<?= htmlspecialchars($metodoPago['icon'] ?? 'circle') ?> text-[10px]"></i>
                        <?= htmlspecialchars($metodoPago['label'] ?? ucfirst($mov['metodo_pago'])) ?>
                    </span>
                </div>
                <p class="text-sm font-semibold text-gray-800 mb-1 leading-tight">
                    <?= htmlspecialchars($mov['descripcion'] ?? '') ?>
                </p>
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1">
                        <i class="far fa-clock"></i>
                        <?= date('H:i', strtotime($mov['created_at'])) ?>
                    </span>
                    <?php if (!empty($mov['categoria_nombre'])): ?>
                        <span class="flex items-center gap-1">
                            <i class="<?= htmlspecialchars($mov['categoria_icono'] ?? 'fas fa-tag') ?>"
                               style="color: <?= htmlspecialchars($mov['categoria_color'] ?? '#9CA3AF') ?>"></i>
                            <?= htmlspecialchars($mov['categoria_nombre']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if ($mov['reservacion_id']): ?>
                    <div class="mt-1.5 text-xs">
                        <a href="<?= url('reservaciones/ver/' . $mov['reservacion_id']) ?>"
                           class="cash-reservation-link"
                           title="Ver reservacion #<?= htmlspecialchars($mov['reservacion_id']) ?>">
                            <i class="fas fa-bed text-[10px]"></i>
                            Reserva #<?= $mov['reservacion_id'] ?>
                            <i class="fas fa-arrow-right text-[10px] cash-reservation-arrow" aria-hidden="true"></i>
                        </a>
                        <?php if (!empty($mov['habitaciones_detalle'])): ?>
                            <span class="text-gray-400 flex items-center gap-1 mt-0.5">
                                <i class="fas fa-door-open text-[10px]"></i>
                                <?= htmlspecialchars($mov['habitaciones_detalle']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <p class="text-base font-bold <?= $es_ingreso ? 'text-emerald-600' : 'text-red-500' ?> whitespace-nowrap">
                <?= $es_ingreso ? '+' : '-' ?>$<?= number_format($mov['monto'] ?? 0, 2) ?>
            </p>
        </div>
        <?php if (!empty($mov['editado']) && $mov['editado']): ?>
            <p class="text-xs text-amber-600 mt-1.5 flex items-center gap-1">
                <i class="fas fa-pen text-[10px]"></i> Editado
            </p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Enlaces Rápidos -->
</div>

<!-- Modal Registrar Ingreso -->
<div id="modalIngreso" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all border border-[#E0ECD8]">
            <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 p-5 rounded-t-2xl flex items-center justify-between">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-plus text-white text-sm"></i>
                    </div>
                    Registrar Ingreso
                </h3>
                <button type="button" onclick="cerrarModalIngreso()" class="text-white/70 hover:text-white transition" title="Cerrar modal" aria-label="Cerrar modal de ingreso">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="<?= url('caja/ingreso') ?>" class="p-5">
                <?= csrf_field() ?>

                <div class="space-y-4">
                    <!-- Categoría -->
                    <div>
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
                    <div>
                        <label class="modal-label">Descripción <span class="text-red-400">*</span></label>
                        <textarea name="descripcion" rows="2" class="modal-input"
                                  placeholder="Ej: Pago habitación 101" required></textarea>
                    </div>

                    <!-- Monto y Método de Pago -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="modal-label">Monto <span class="text-red-400">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                       class="modal-input pl-7" placeholder="0.00" required>
                            </div>
                        </div>
                        <div>
                            <label class="modal-label">Método de Pago <span class="text-red-400">*</span></label>
                            <select name="metodo_pago" id="metodo_pago_ingreso" class="modal-input" required>
                                <?php foreach ($metodos_pago as $key => $metodo): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($metodo['label'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Referencia -->
                    <div id="referencia_ingreso_div" class="hidden">
                        <label class="modal-label">Referencia/Autorización <span class="text-red-400">*</span></label>
                        <input type="text" name="referencia" class="modal-input" placeholder="Número de referencia">
                    </div>

                    <!-- Comprobante -->
                    <div>
                        <label class="modal-label">Número de Comprobante</label>
                        <input type="text" name="comprobante" class="modal-input" placeholder="Ej: Ticket #123">
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-2 mt-5 pt-4 border-t border-gray-100">
                    <button type="button" onclick="cerrarModalIngreso()"
                            class="px-4 py-2 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium"
                            title="Cancelar ingreso">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg hover:shadow-md transition font-semibold flex items-center gap-1.5"
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
<div id="modalGasto" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm transform transition-all my-8 border border-[#FDDEDE]">
            <!-- Cabecera -->
            <div class="bg-gradient-to-r from-red-500 to-red-600 p-5 rounded-t-2xl flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <div class="w-7 h-7 bg-white/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-minus text-white text-sm"></i>
                    </div>
                    Registrar Gasto
                </h3>
                <button type="button" onclick="cerrarModalGasto()" class="text-white/70 hover:text-white transition" title="Cerrar modal" aria-label="Cerrar modal de gasto">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Contenido con scroll -->
            <div class="overflow-y-auto max-h-[calc(100vh-200px)]">
                <form method="POST" action="<?= url('caja/gasto') ?>" class="p-4">
                    <?= csrf_field() ?>

                    <div class="space-y-3">
                        <!-- Categoría -->
                        <div>
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
                        <div>
                            <label class="modal-label modal-label-red">Descripción <span class="text-red-400">*</span></label>
                            <textarea name="descripcion" rows="2" class="modal-input modal-input-red"
                                      placeholder="Ej: Compra de productos de limpieza" required></textarea>
                        </div>

                        <!-- Proveedor -->
                        <div>
                            <label class="modal-label">Proveedor/Beneficiario</label>
                            <input type="text" name="proveedor" class="modal-input"
                                   placeholder="Nombre del proveedor">
                        </div>

                        <!-- Monto y Método de Pago -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="modal-label modal-label-red">Monto <span class="text-red-400">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">$</span>
                                    <input type="number" name="monto" data-money-format="true" step="0.01" min="0.01"
                                           class="modal-input modal-input-red pl-7"
                                           placeholder="0.00" required>
                                </div>
                            </div>

                            <div>
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
                        <div id="referencia_gasto_div" class="hidden">
                            <label class="modal-label modal-label-red">
                                Referencia/Autorización <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="referencia" class="modal-input modal-input-red"
                                   placeholder="Número de referencia">
                        </div>

                        <!-- Comprobante -->
                        <div>
                            <label class="modal-label">Número de Comprobante</label>
                            <input type="text" name="comprobante" class="modal-input"
                                   placeholder="Ej: Factura #ABC123">
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-2 mt-4 pt-3 border-t border-gray-100">
                        <button type="button" onclick="cerrarModalGasto()"
                                class="px-3 py-1.5 text-sm border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition font-medium"
                                title="Cancelar gasto">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-3 py-1.5 text-sm bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:shadow-md transition font-semibold flex items-center gap-1.5"
                                title="Guardar gasto">
                            <i class="fas fa-save text-xs"></i>
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Scripts del sidebar y modal-fix -->
<script src="<?= asset('js/sidebar-scripts.js') ?>"></script>
<script src="<?= asset('js/modal-sidebar-fix.js') ?>"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Funciones para modales
function mostrarModalIngreso() {
    document.getElementById('modalIngreso').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalIngreso() {
    document.getElementById('modalIngreso').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalIngreso').querySelector('form').reset();
}

function mostrarModalGasto() {
    document.getElementById('modalGasto').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalGasto() {
    document.getElementById('modalGasto').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalGasto').querySelector('form').reset();
}

// Reemplazar tus funciones originales con estas
function mostrarModalIngreso() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalIngreso').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalIngreso() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalIngreso').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalIngreso').querySelector('form').reset();
}

function mostrarModalGasto() {
    document.getElementById('sidebar').style.display = 'none';
    document.getElementById('modalGasto').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalGasto() {
    document.getElementById('sidebar').style.display = '';
    document.getElementById('modalGasto').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    document.getElementById('modalGasto').querySelector('form').reset();
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalIngreso();
        cerrarModalGasto();
    }
});

// Mostrar/ocultar campo de referencia según método de pago
document.getElementById('metodo_pago_ingreso').addEventListener('change', function() {
    const referenciaDiv = document.getElementById('referencia_ingreso_div');
    const referenciaInput = referenciaDiv.querySelector('input');

    if (this.value === 'tarjeta' || this.value === 'transferencia') {
        referenciaDiv.classList.remove('hidden');
        referenciaInput.setAttribute('required', 'required');
    } else {
        referenciaDiv.classList.add('hidden');
        referenciaInput.removeAttribute('required');
        referenciaInput.value = '';
    }
});

document.getElementById('metodo_pago_gasto').addEventListener('change', function() {
    const referenciaDiv = document.getElementById('referencia_gasto_div');
    const referenciaInput = referenciaDiv.querySelector('input');

    if (this.value === 'tarjeta' || this.value === 'transferencia') {
        referenciaDiv.classList.remove('hidden');
        referenciaInput.setAttribute('required', 'required');
    } else {
        referenciaDiv.classList.add('hidden');
        referenciaInput.removeAttribute('required');
        referenciaInput.value = '';
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
</script>
