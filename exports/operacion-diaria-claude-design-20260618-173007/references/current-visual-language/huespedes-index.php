<?php
$huespedes = $huespedes ?? [];
$estados = $estados ?? [];
$guestRows = [];
$vehiculoModel = !empty($huespedes) ? new HuespedVehiculo() : null;

foreach ($huespedes as $huesped) {
    $vehiculos = $vehiculoModel ? $vehiculoModel->porHuesped($huesped['id']) : [];
    $guestRows[] = [
        'huesped' => $huesped,
        'vehiculos' => $vehiculos,
        'total_vehiculos' => count($vehiculos),
    ];
}

$huespedesVisibles = count($guestRows);
$origenesVisibles = count(array_filter(array_unique(array_column($huespedes, 'procedencia_estado'))));

$pagina_actual = max(1, (int) ($pagina_actual ?? 1));
$total_paginas = max(1, (int) ($total_paginas ?? 1));
$buscar = $buscar ?? '';
$estado_filtro = $estado_filtro ?? '';

$guestPaginationPages = [];
if ($total_paginas > 1) {
    $candidatePages = [1, $total_paginas, $pagina_actual - 1, $pagina_actual, $pagina_actual + 1];

    if ($pagina_actual <= 3) {
        $candidatePages = array_merge($candidatePages, [2, 3, 4]);
    }

    if ($pagina_actual >= $total_paginas - 2) {
        $candidatePages = array_merge($candidatePages, [$total_paginas - 3, $total_paginas - 2, $total_paginas - 1]);
    }

    foreach ($candidatePages as $candidatePage) {
        $candidatePage = (int) $candidatePage;
        if ($candidatePage >= 1 && $candidatePage <= $total_paginas) {
            $guestPaginationPages[$candidatePage] = true;
        }
    }

    $guestPaginationPages = array_keys($guestPaginationPages);
    sort($guestPaginationPages);
}
?>

<style>
.guests-page {
    --guest-brand: var(--brand-primary, #2563EB);
    --guest-brand-dark: color-mix(in srgb, var(--guest-brand), #000 26%);
    --guest-brand-soft: color-mix(in srgb, var(--guest-brand) 6%, #F8FAFC);
    --guest-brand-softer: color-mix(in srgb, var(--guest-brand) 3%, #FFFFFF);
    --guest-accent: var(--brand-accent, #F59E0B);
    --guest-border: color-mix(in srgb, var(--guest-brand) 9%, #E2E8F0);
    --guest-ring: color-mix(in srgb, var(--guest-brand) 16%, transparent);
    --guest-text: #0F172A;
    --guest-muted: #64748B;
    --guest-ink: #111827;
    color: var(--guest-text);
}

.hotel-page {
    color: var(--guest-text);
}

.hotel-page-header {
    position: relative;
}

.hotel-page-kicker {
    color: rgba(255,255,255,.72);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.hotel-page-title {
    color: rgba(255,255,255,.98);
    font-weight: 900;
    letter-spacing: 0;
}

.hotel-page-subtitle {
    margin-top: 6px;
    max-width: 44rem;
    color: rgba(255,255,255,.92);
    font-size: .92rem;
    font-weight: 650;
    line-height: 1.45;
    text-wrap: pretty;
    text-shadow: 0 1px 1px rgba(15,23,42,.22);
}

.hotel-toolbar,
.hotel-card,
.hotel-table,
.hotel-mobile-card,
.hotel-empty-state {
    border-color: var(--guest-border);
}

.hotel-btn-primary {
    background: var(--guest-brand);
    color: #fff;
}

.hotel-btn-secondary {
    background: #F1F5F9;
    color: #475569;
}

.hotel-badge {
    background: var(--guest-brand-soft);
    color: var(--guest-brand-dark);
}

.hotel-status {
    color: #334155;
}

.guest-shell {
    display: grid;
    gap: 14px;
}

.guest-hero {
    background:
        radial-gradient(circle at right top, rgba(255,255,255,.16), transparent 34%),
        linear-gradient(135deg, var(--guest-brand-dark), var(--guest-brand));
    border-radius: 14px;
    padding: 14px;
    box-shadow: 0 16px 34px color-mix(in srgb, var(--guest-brand) 16%, transparent);
    overflow: hidden;
}

.guest-hero-icon,
.guest-avatar {
    display: grid;
    place-items: center;
    flex-shrink: 0;
}

.guest-hero-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(255,255,255,.16);
    color: #fff;
    border: 1px solid rgba(255,255,255,.16);
}

.guest-primary-btn,
.guest-filter-btn,
.guest-reset-btn,
.guest-action,
.guest-card-action,
.guest-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 38px;
    border-radius: 10px;
    font-weight: 800;
    cursor: pointer;
    text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
}

.guest-primary-btn {
    padding: .55rem .85rem;
    background: #fff;
    color: var(--guest-brand-dark);
    box-shadow: 0 10px 22px rgba(15,23,42,.14);
    white-space: nowrap;
}

.guest-primary-btn:hover,
.guest-filter-btn:hover,
.guest-reset-btn:hover,
.guest-card-action:hover,
.guest-action:hover,
.guest-page-link:hover {
    transform: translateY(-1px);
}

.guest-primary-btn:focus-visible,
.guest-filter-btn:focus-visible,
.guest-reset-btn:focus-visible,
.guest-action:focus-visible,
.guest-card-action:focus-visible,
.guest-page-link:focus-visible,
.guest-detail-link:focus-visible,
.guest-contact-link:focus-visible,
.guest-chip-link:focus-visible {
    outline: 2px solid var(--guest-brand);
    outline-offset: 3px;
}

.guest-primary-btn:active,
.guest-filter-btn:active,
.guest-reset-btn:active,
.guest-action:active,
.guest-card-action:active,
.guest-page-link:active,
.guest-chip-link:active {
    transform: translateY(0) scale(.98);
}

.guest-summary-strip {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}

.guest-summary-item,
.guest-panel,
.guest-card {
    background: rgba(255,255,255,.96);
    border: 1px solid var(--guest-border);
    box-shadow: 0 8px 24px rgba(15,23,42,.045);
}

.guest-summary-item {
    border-radius: 12px;
    padding: 11px 12px;
}

.guest-summary-label {
    color: var(--guest-muted);
    font-size: .68rem;
    font-weight: 800;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.guest-summary-value {
    margin-top: 2px;
    color: var(--guest-text);
    font-size: 1.15rem;
    font-weight: 900;
    line-height: 1.15;
}

.guest-panel {
    border-radius: 14px;
}

.guest-filter-form {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(190px, 260px) auto;
    gap: 10px;
    align-items: end;
}

.guest-control {
    width: 100%;
    min-height: 38px;
    border-color: var(--guest-border) !important;
    background: color-mix(in srgb, var(--guest-brand) 3%, #fff);
    border-radius: 10px;
}

.guest-control:focus {
    border-color: var(--guest-brand) !important;
    box-shadow: 0 0 0 3px var(--guest-ring) !important;
    outline: none !important;
}

select.guest-control {
    cursor: pointer;
}

.guest-filter-btn {
    padding: .5rem .8rem;
    background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-dark));
    color: #fff;
    box-shadow: 0 10px 20px color-mix(in srgb, var(--guest-brand) 18%, transparent);
}

.guest-reset-btn {
    padding: .5rem .8rem;
    background: #F1F5F9;
    color: #475569;
}

.guest-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .62rem;
    border-radius: 999px;
    background: var(--guest-brand-soft);
    color: var(--guest-brand-dark);
    font-size: .72rem;
    font-weight: 800;
}

.guest-desktop-table {
    display: none;
}

.guest-table {
    width: 100%;
    table-layout: fixed;
}

.guest-table thead {
    background: #F8FAFC;
    border-bottom: 1px solid var(--guest-border);
}

.guest-table th {
    padding: 12px 16px;
    color: var(--guest-muted);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .055em;
    text-align: left;
    text-transform: uppercase;
}

.guest-table td {
    padding: 13px 16px;
    vertical-align: middle;
}

.guest-row {
    border-bottom: 1px solid #EEF2F7;
    transition: background .16s ease, box-shadow .16s ease;
}

.guest-row:hover {
    background: var(--guest-brand-softer);
    box-shadow: 0 8px 22px -24px rgba(15, 23, 42, .45);
}

.guest-id,
.guest-muted {
    color: var(--guest-muted);
    font-size: .74rem;
}

.guest-name {
    color: var(--guest-text);
    font-size: .9rem;
    font-weight: 900;
    line-height: 1.25;
}

.guest-detail-link {
    display: inline-flex;
    align-items: center;
    min-width: 0;
    max-width: 100%;
    gap: .35rem;
    color: inherit;
    border-radius: 8px;
    text-decoration: none;
    transition: color .16s ease, background .16s ease;
}

.guest-detail-text {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.guest-detail-icon {
    flex-shrink: 0;
    font-size: .68rem;
    opacity: 0;
    transform: translateX(-2px);
    transition: opacity .16s ease, transform .16s ease;
}

.guest-detail-link:hover {
    color: var(--guest-ink);
    text-decoration: underline;
    text-decoration-thickness: 2px;
    text-underline-offset: 3px;
}

.guest-detail-link:hover .guest-detail-icon,
.guest-detail-link:focus-visible .guest-detail-icon {
    opacity: 1;
    transform: translateX(0);
}

.guest-contact-line,
.guest-meta-line {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: .45rem;
    color: #334155;
    font-size: .8rem;
    line-height: 1.35;
}

.guest-contact-line span,
.guest-meta-line span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.guest-contact-link {
    display: inline-flex;
    min-width: 0;
    max-width: 100%;
    align-items: center;
    color: inherit;
    border-radius: 7px;
    text-decoration: none;
    transition: color .16s ease, background .16s ease;
}

.guest-contact-link:hover {
    color: var(--guest-brand-dark);
    text-decoration: underline;
    text-decoration-thickness: 1px;
    text-underline-offset: 3px;
}

.guest-cell-stack {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 4px;
}

.guest-chip {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    width: fit-content;
    max-width: 100%;
    padding: .32rem .55rem;
    border-radius: 999px;
    background: #F8FAFC;
    color: #475569;
    border: 1px solid #E2E8F0;
    font-size: .72rem;
    font-weight: 800;
}

.guest-chip-link {
    cursor: pointer;
    text-decoration: none;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.guest-chip-link:hover {
    transform: translateY(-1px);
}

.guest-chip-brand {
    background: color-mix(in srgb, var(--guest-brand) 5%, #F8FAFC);
    color: #334155;
    border-color: color-mix(in srgb, var(--guest-brand) 12%, #E2E8F0);
}

.guest-chip-warning {
    background: #FEF3C7;
    color: #92400E;
    border-color: #FDE68A;
}

.guest-avatar {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: var(--guest-avatar-bg, #EEF2FF);
    color: var(--guest-avatar-fg, #3730A3);
    font-size: .95rem;
    font-weight: 900;
    border: 1px solid var(--guest-avatar-border, rgba(99,102,241,.16));
    box-shadow: none;
}

.guest-row:nth-child(6n+1) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+1) .guest-avatar {
    --guest-avatar-bg: #EEF2FF;
    --guest-avatar-fg: #3730A3;
    --guest-avatar-border: #C7D2FE;
}

.guest-row:nth-child(6n+2) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+2) .guest-avatar {
    --guest-avatar-bg: #ECFDF5;
    --guest-avatar-fg: #047857;
    --guest-avatar-border: #A7F3D0;
}

.guest-row:nth-child(6n+3) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+3) .guest-avatar {
    --guest-avatar-bg: #FFF7ED;
    --guest-avatar-fg: #C2410C;
    --guest-avatar-border: #FED7AA;
}

.guest-row:nth-child(6n+4) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+4) .guest-avatar {
    --guest-avatar-bg: #FDF2F8;
    --guest-avatar-fg: #BE185D;
    --guest-avatar-border: #FBCFE8;
}

.guest-row:nth-child(6n+5) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+5) .guest-avatar {
    --guest-avatar-bg: #F0FDFA;
    --guest-avatar-fg: #0F766E;
    --guest-avatar-border: #99F6E4;
}

.guest-row:nth-child(6n+6) .guest-avatar,
.guest-mobile-list .guest-card:nth-child(6n+6) .guest-avatar {
    --guest-avatar-bg: #F8FAFC;
    --guest-avatar-fg: #475569;
    --guest-avatar-border: #CBD5E1;
}

.guest-action {
    width: 34px;
    height: 34px;
    background: #F8FAFC;
    color: #475569;
}

.guest-action-view { color: #2563EB; }
.guest-action-edit { color: #B45309; }
.guest-action-book { color: #047857; }
.guest-action-view:hover { background: #DBEAFE; }
.guest-action-edit:hover { background: #FEF3C7; }
.guest-action-book:hover { background: #D1FAE5; }

.guest-mobile-list {
    display: grid;
    gap: 10px;
}

.guest-card {
    border-radius: 14px;
    padding: 12px;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}

.guest-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--guest-brand) 22%, #E2E8F0);
    box-shadow: 0 12px 28px rgba(15, 23, 42, .07);
}

.guest-card-top {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 10px;
    align-items: start;
}

.guest-card-title {
    min-width: 0;
}

.guest-card-contact {
    display: grid;
    gap: 5px;
    margin-top: 9px;
}

.guest-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 10px;
}

.guest-card-actions {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 7px;
    margin-top: 11px;
}

.guest-card-action {
    min-height: 34px;
    padding: .45rem .4rem;
    border: 1px solid #E2E8F0;
    background: #fff;
    color: #334155;
    font-size: .75rem;
}

.guest-card-action.primary {
    background: var(--guest-brand);
    border-color: var(--guest-brand);
    color: #fff;
}

.guest-empty {
    border-radius: 14px;
    border: 1px solid var(--guest-border);
    background: linear-gradient(180deg, #fff, var(--guest-brand-soft));
}

.guest-empty-icon {
    display: grid;
    place-items: center;
}

.guest-pagination {
    display: grid;
    justify-items: center;
    gap: 8px;
}

.guest-pagination-shell {
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    gap: 6px;
    padding: 6px;
    border: 1px solid var(--guest-border);
    border-radius: 14px;
    background: rgba(255,255,255,.96);
    box-shadow: 0 10px 26px rgba(15,23,42,.055);
}

.guest-page-window {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.guest-page-link,
.guest-page-current,
.guest-page-disabled,
.guest-page-ellipsis {
    min-width: 36px;
    min-height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 1px solid #E2E8F0;
    background: #fff;
    color: #475569;
    font-size: .85rem;
    font-weight: 800;
}

.guest-page-link {
    transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.guest-page-link:hover {
    transform: translateY(-1px);
    border-color: var(--guest-brand);
    color: var(--guest-brand-dark);
    background: var(--guest-brand-soft);
}

.guest-page-current {
    border-color: var(--guest-brand);
    background: var(--guest-brand);
    color: #fff;
}

.guest-page-disabled {
    background: #F8FAFC;
    color: #94A3B8;
}

.guest-page-ellipsis {
    min-width: 24px;
    border-color: transparent;
    background: transparent;
    color: #94A3B8;
}

.guest-page-control {
    min-width: auto;
    padding-inline: .75rem;
    white-space: nowrap;
}

.guest-page-control-label {
    margin-inline: .25rem;
}

.guest-pagination-summary {
    color: var(--guest-muted);
    font-size: .76rem;
    font-weight: 800;
}

@media (min-width: 768px) {
    .guest-desktop-table {
        display: block;
    }

    .guest-mobile-list {
        display: none;
    }
}

@media (max-width: 767px) {
    .guests-page {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    .guest-hero {
        padding: 13px;
    }

    .guest-summary-strip {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 7px;
    }

    .guest-summary-item {
        padding: 9px 8px;
    }

    .guest-summary-label {
        font-size: .6rem;
    }

    .guest-summary-value {
        font-size: .95rem;
    }

    .guest-filter-form {
        grid-template-columns: 1fr;
    }

    .guest-filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }

    .guest-primary-btn {
        width: 100%;
    }

    .hotel-page-subtitle {
        font-size: .86rem;
        line-height: 1.42;
    }

    .guest-pagination {
        align-items: stretch;
        justify-items: stretch;
    }

    .guest-pagination-shell {
        width: 100%;
        justify-content: space-between;
    }

    .guest-page-window {
        flex: 0 1 auto;
        overflow: hidden;
    }

    .guest-page-link,
    .guest-page-current,
    .guest-page-disabled,
    .guest-page-ellipsis {
        min-width: 32px;
        min-height: 34px;
        border-radius: 9px;
        font-size: .8rem;
    }

    .guest-page-control {
        padding-inline: .62rem;
    }

    .guest-page-control-label {
        display: none;
    }
}
</style>

<!-- ════ Capa boutique (rediseño huespedes.html): ivory + navy/oro + serif. Solo tema, conserva layout. ════ -->
<style id="guest-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.guests-page {
    /* Identidad del hotel (fallback boutique navy/oro en vez de azul) */
    --guest-brand: var(--brand-primary, #1B2746);
    --guest-brand-2: var(--brand-secondary, #0F172A);
    --guest-brand-dark: color-mix(in srgb, var(--guest-brand), #000 20%);
    --guest-brand-soft: color-mix(in srgb, var(--guest-brand) 5%, #FBF8F2);
    --guest-brand-softer: color-mix(in srgb, var(--guest-brand) 2%, #FFFFFF);
    --guest-gold: var(--brand-accent, #BD9441);
    --guest-gold-soft: color-mix(in srgb, var(--guest-gold) 15%, #FFFFFF);
    --guest-gold-line: color-mix(in srgb, var(--guest-gold) 42%, #E4D4B0);
    --guest-gold-ink: color-mix(in srgb, var(--guest-gold) 72%, #000);
    --guest-ivory: #F6F2EA;  --guest-ivory-2: #FBF8F2;
    --guest-surface: #FFFFFF; --guest-surface-warm: #FCFAF5;
    --guest-border: color-mix(in srgb, var(--guest-brand) 7%, #E7E1D4);
    --guest-ring: color-mix(in srgb, var(--guest-gold) 32%, transparent);
    --guest-text: #171717; --guest-muted: #667085; --guest-heading: #111827;
    --guest-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --guest-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    /* Semánticos */
    --g-success:#1E9E63; --g-success-bg:#E7F4EC;
    --g-warning:#C2841C; --g-warning-bg:#FAF0DC;
    --g-info:#2F77E0;    --g-info-bg:#E6EFFC;
    color: var(--guest-text) !important;
    font-family: var(--guest-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--guest-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--guest-ivory-2), var(--guest-ivory)) !important;
}

/* ── Header limpio (sin banner de color) ── */
.guests-page .guest-hero { background: transparent !important; box-shadow: none !important; border-radius: 0 !important; padding: 2px 2px 4px !important; overflow: visible !important; }
.guests-page .guest-title-lockup { display: grid !important; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; max-width: min(960px, 100%); }
.guests-page .guest-title-copy { min-width: 0; padding-top: 1px; }
.guests-page .guest-hero-icon { width: 48px !important; height: 48px !important; border-radius: 15px !important; background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--guest-gold), var(--guest-brand) 54%, color-mix(in srgb, var(--guest-brand) 68%, #2F8A70)) !important; color: #fff !important; border: none !important; box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--guest-brand) 72%, transparent) !important; }
.guests-page .hotel-page-kicker { display: block; margin: 0 0 2px !important; color: var(--guest-muted) !important; font-size: .72rem !important; font-weight: 900 !important; letter-spacing: .11em !important; line-height: 1 !important; text-transform: uppercase; }
.guests-page .hotel-page-title { margin: 0 !important; font-family: var(--guest-serif) !important; color: var(--guest-heading) !important; font-weight: 700 !important; font-size: clamp(2.35rem, 4vw, 3.35rem) !important; line-height: .98 !important; letter-spacing: 0 !important; text-shadow: none !important; text-wrap: balance; }
.guests-page .hotel-page-subtitle { max-width: 920px; margin-top: 9px !important; color: var(--guest-muted) !important; font-size: .94rem !important; font-weight: 600 !important; line-height: 1.55 !important; text-shadow: none !important; }

@media (max-width: 767px) {
    .guests-page .guest-title-lockup { grid-template-columns: 44px minmax(0, 1fr); column-gap: 12px; align-items: start; }
    .guests-page .guest-hero-icon { width: 44px !important; height: 44px !important; border-radius: 14px !important; }
    .guests-page .hotel-page-title { font-size: 2rem !important; }
}

/* "Nuevo huésped" = oro (acción destacada del diseño) */
.guests-page .guest-primary-btn { background: linear-gradient(135deg, var(--guest-gold), color-mix(in srgb, var(--guest-gold) 76%, #000)) !important; color: #fff !important; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--guest-gold) 58%, transparent) !important; }

/* ── Strip de resumen ── */
.guests-page .guest-summary-item { background: var(--guest-surface) !important; border: 1px solid var(--guest-border) !important; border-radius: 14px !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22) !important; }
.guests-page .guest-summary-value { font-family: var(--guest-serif) !important; font-size: 1.65rem !important; font-weight: 700 !important; color: var(--guest-heading) !important; }
.guests-page .guest-summary-label { color: var(--guest-muted) !important; }

/* ── Paneles ── */
.guests-page .guest-panel { background: var(--guest-surface) !important; border: 1px solid var(--guest-border) !important; border-radius: 16px !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28) !important; }
.guests-page .guest-panel .border-slate-100 { border-color: var(--guest-border) !important; }
.guests-page .guest-panel h2 { color: var(--guest-heading) !important; }

/* ── Filtros ── */
.guests-page .guest-control { background: var(--guest-surface-warm) !important; border-color: var(--guest-border) !important; border-radius: 11px !important; color: var(--guest-text) !important; font-weight: 600; }
.guests-page .guest-control:focus { border-color: var(--guest-gold) !important; box-shadow: 0 0 0 3px var(--guest-ring) !important; }
.guests-page .guest-filter-form label { color: var(--guest-muted) !important; }
.guests-page .guest-filter-btn { background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-2)) !important; color: #fff !important; border-radius: 11px !important; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--guest-brand) 60%, transparent) !important; }
.guests-page .guest-reset-btn { background: var(--guest-surface) !important; border: 1px solid var(--guest-border) !important; color: var(--guest-muted) !important; border-radius: 11px !important; }
.guests-page .guest-count-pill { background: var(--guest-gold-soft) !important; color: var(--guest-gold-ink) !important; border: 1px solid var(--guest-gold-line) !important; }

/* ── Tabla ── */
.guests-page .guest-table thead { background: var(--guest-surface-warm) !important; border-bottom: 1px solid var(--guest-border) !important; }
.guests-page .guest-table th { color: var(--guest-muted) !important; letter-spacing: .07em !important; }
.guests-page .guest-row { border-bottom: 1px solid var(--guest-border) !important; }
.guests-page .guest-row:hover { background: var(--guest-ivory-2) !important; box-shadow: 0 10px 24px -24px rgba(27,39,70,.48) !important; }
.guests-page .guest-name { color: var(--guest-heading) !important; font-weight: 800 !important; }
.guests-page .guest-detail-link { color: var(--guest-heading) !important; }
.guests-page .guest-detail-link:hover { color: var(--guest-heading) !important; background: color-mix(in srgb, var(--guest-gold) 9%, transparent) !important; text-decoration-color: var(--guest-gold) !important; }
.guests-page .guest-contact-link:hover { color: var(--guest-heading) !important; text-decoration-color: var(--guest-gold) !important; }
.guests-page .guest-id, .guests-page .guest-muted { color: var(--guest-muted) !important; }
.guests-page .guest-contact-line, .guests-page .guest-meta-line { color: var(--guest-text) !important; }

/* Avatar palette keeps the hotel color from saturating every row. */
.guests-page .guest-avatar {
    border-radius: 12px !important;
    background: var(--guest-avatar-bg, #EEF2FF) !important;
    color: var(--guest-avatar-fg, #3730A3) !important;
    border: 1px solid var(--guest-avatar-border, #C7D2FE) !important;
    box-shadow: 0 10px 20px -15px var(--guest-avatar-shadow, rgba(55,48,163,.34)) !important;
}

.guests-page .guest-row:nth-child(6n+1) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+1) .guest-avatar {
    --guest-avatar-bg: #EEF2FF;
    --guest-avatar-fg: #3730A3;
    --guest-avatar-border: #C7D2FE;
    --guest-avatar-shadow: rgba(55,48,163,.34);
}

.guests-page .guest-row:nth-child(6n+2) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+2) .guest-avatar {
    --guest-avatar-bg: #ECFDF5;
    --guest-avatar-fg: #047857;
    --guest-avatar-border: #A7F3D0;
    --guest-avatar-shadow: rgba(4,120,87,.3);
}

.guests-page .guest-row:nth-child(6n+3) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+3) .guest-avatar {
    --guest-avatar-bg: #FFF7ED;
    --guest-avatar-fg: #C2410C;
    --guest-avatar-border: #FED7AA;
    --guest-avatar-shadow: rgba(194,65,12,.3);
}

.guests-page .guest-row:nth-child(6n+4) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+4) .guest-avatar {
    --guest-avatar-bg: #FDF2F8;
    --guest-avatar-fg: #BE185D;
    --guest-avatar-border: #FBCFE8;
    --guest-avatar-shadow: rgba(190,24,93,.28);
}

.guests-page .guest-row:nth-child(6n+5) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+5) .guest-avatar {
    --guest-avatar-bg: #F0FDFA;
    --guest-avatar-fg: #0F766E;
    --guest-avatar-border: #99F6E4;
    --guest-avatar-shadow: rgba(15,118,110,.28);
}

.guests-page .guest-row:nth-child(6n+6) .guest-avatar,
.guests-page .guest-mobile-list .guest-card:nth-child(6n+6) .guest-avatar {
    --guest-avatar-bg: #F8FAFC;
    --guest-avatar-fg: #475569;
    --guest-avatar-border: #CBD5E1;
    --guest-avatar-shadow: rgba(71,85,105,.25);
}

/* Chips */
.guests-page .guest-chip { background: var(--guest-surface-warm) !important; color: var(--guest-muted) !important; border: 1px solid var(--guest-border) !important; }
.guests-page .guest-chip-brand { background: var(--guest-brand-soft) !important; color: var(--guest-heading) !important; border-color: color-mix(in srgb, var(--guest-brand) 12%, var(--guest-border)) !important; }
.guests-page .guest-chip-warning { background: var(--guest-gold-soft) !important; color: var(--guest-gold-ink) !important; border-color: var(--guest-gold-line) !important; }
.guests-page .guest-chip-link:hover { border-color: var(--guest-gold-line) !important; color: var(--guest-gold-ink) !important; background: var(--guest-gold-soft) !important; }

/* Acciones por fila (semánticas, refinadas) */
.guests-page .guest-action { background: var(--guest-surface-warm) !important; border: 1px solid var(--guest-border) !important; border-radius: 10px !important; }
.guests-page .guest-action-view { color: var(--g-info) !important; } .guests-page .guest-action-view:hover { background: var(--g-info-bg) !important; }
.guests-page .guest-action-edit { color: var(--guest-gold-ink) !important; } .guests-page .guest-action-edit:hover { background: var(--guest-gold-soft) !important; }
.guests-page .guest-action-book { color: var(--g-success) !important; } .guests-page .guest-action-book:hover { background: var(--g-success-bg) !important; }

/* Cards móviles */
.guests-page .guest-card { background: var(--guest-surface) !important; border: 1px solid var(--guest-border) !important; border-radius: 16px !important; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25) !important; }
.guests-page .guest-card:hover { border-color: color-mix(in srgb, var(--guest-gold) 38%, var(--guest-border)) !important; box-shadow: 0 2px 4px rgba(27,39,70,.05), 0 16px 32px -22px rgba(27,39,70,.32) !important; }
.guests-page .guest-card-action { background: var(--guest-surface-warm) !important; border: 1px solid var(--guest-border) !important; color: var(--guest-text) !important; border-radius: 10px !important; }
.guests-page .guest-card-action:hover { border-color: var(--guest-gold-line) !important; color: var(--guest-gold-ink) !important; background: var(--guest-gold-soft) !important; }
.guests-page .guest-card-action.primary { background: linear-gradient(135deg, var(--guest-brand), var(--guest-brand-2)) !important; border-color: transparent !important; color: #fff !important; }
.guests-page .guest-card-action.primary:hover { background: linear-gradient(135deg, color-mix(in srgb, var(--guest-brand) 92%, #fff), var(--guest-brand-2)) !important; color: #fff !important; }

/* Empty state */
.guests-page .guest-empty { background: var(--guest-ivory-2) !important; border: 1px dashed var(--guest-border) !important; }
.guests-page .guest-empty-icon { background: var(--guest-gold-soft) !important; color: var(--guest-gold-ink) !important; }
.guests-page .guest-empty h2 { color: var(--guest-brand) !important; }

/* Paginación */
.guests-page .guest-pagination-shell { background: var(--guest-surface) !important; border: 1px solid var(--guest-border) !important; border-radius: 14px !important; }
.guests-page .guest-page-link, .guests-page .guest-page-current, .guests-page .guest-page-disabled, .guests-page .guest-page-ellipsis { border-color: var(--guest-border) !important; }
.guests-page .guest-page-link { color: var(--guest-muted) !important; background: var(--guest-surface) !important; }
.guests-page .guest-page-link:hover { border-color: var(--guest-gold) !important; color: var(--guest-gold-ink) !important; background: var(--guest-gold-soft) !important; }
.guests-page .guest-page-current { background: var(--guest-heading) !important; border-color: var(--guest-heading) !important; color: #fff !important; }
.guests-page .guest-pagination-summary { color: var(--guest-muted) !important; }
.guests-page [data-guest-results-region] {
    transition: opacity .18s ease, filter .18s ease;
}
.guests-page [data-guest-results-region].is-updating {
    opacity: .58;
    filter: saturate(.88);
    pointer-events: none;
}
.guests-page .guest-filter-form.is-searching [data-guest-search-icon] {
    color: var(--guest-brand) !important;
    animation: guest-live-search-pulse .72s ease-in-out infinite;
}
@keyframes guest-live-search-pulse {
    0%, 100% { transform: translateY(-50%) scale(1); opacity: .62; }
    50% { transform: translateY(-50%) scale(1.12); opacity: 1; }
}
</style>

<div class="guests-page hotel-page p-4 sm:p-6">
    <div class="guest-shell">
        <section class="guest-hero hotel-page-header">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="guest-title-lockup">
                    <div class="guest-hero-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="guest-title-copy">
                        <p class="hotel-page-kicker">Operaci&oacute;n hotelera</p>
                        <h1 class="hotel-page-title">Hu&eacute;spedes</h1>
                        <p class="hotel-page-subtitle">Directorio operativo de hu&eacute;spedes: contacto, procedencia, veh&iacute;culos e historial de reservas.</p>
                    </div>
                </div>

                <a href="<?= url('huespedes/create') ?>" class="guest-primary-btn hotel-btn-primary" title="Registrar nuevo huesped">
                    <i class="fas fa-user-plus"></i>
                    Nuevo huésped
                </a>
            </div>
        </section>

        <section class="guest-summary-strip hotel-toolbar" aria-label="Resumen de huéspedes" data-guest-summary>
            <div class="guest-summary-item hotel-card">
                <p class="guest-summary-label">Total</p>
                <p class="guest-summary-value"><?= number_format($total_huespedes) ?></p>
            </div>
            <div class="guest-summary-item hotel-card">
                <p class="guest-summary-label">Mostrando</p>
                <p class="guest-summary-value"><?= number_format($huespedesVisibles) ?></p>
            </div>
            <div class="guest-summary-item hotel-card">
                <p class="guest-summary-label">Orígenes</p>
                <p class="guest-summary-value"><?= number_format($origenesVisibles) ?></p>
            </div>
        </section>

        <section class="guest-panel hotel-card p-3 md:p-4">
            <form method="GET" action="<?= url('huespedes') ?>" class="guest-filter-form" data-guest-live-search-form data-auto-filter-form>
                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wide mb-1">Buscar huésped</label>
                    <div class="relative">
                        <input type="text"
                               name="buscar"
                               autocomplete="off"
                               inputmode="search"
                               data-guest-live-search-input
                               value="<?= htmlspecialchars($buscar ?? '') ?>"
                               title="Buscar por nombre, telefono, email o placas"
                               placeholder="Nombre, teléfono, email o placas"
                               class="guest-control pl-10 pr-4 py-2 border text-sm">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" data-guest-search-icon></i>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold text-slate-600 uppercase tracking-wide mb-1">Procedencia</label>
                    <select name="estado" class="guest-control px-3 py-2 border text-sm" title="Filtrar por procedencia">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= $estado ?>" <?= ($estado_filtro ?? '') == $estado ? 'selected' : '' ?>>
                                <?= $estado ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="guest-filter-actions flex gap-2">
                    <button type="submit" class="guest-filter-btn hotel-btn-primary" title="Aplicar filtros">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a href="<?= url('huespedes') ?>" class="guest-reset-btn hotel-btn-secondary" title="Limpiar filtros">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <div data-guest-results-region aria-live="polite" aria-busy="false">
        <?php if (!empty($guestRows)): ?>
        <section class="guest-panel hotel-card overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-sm font-black text-slate-900">Directorio de huéspedes</h2>
                    <p class="text-xs text-slate-500">Contacto, origen y acciones rápidas para recepción.</p>
                </div>
                <span class="guest-count-pill hotel-badge">
                    <i class="fas fa-list"></i>
                    <?= number_format($huespedesVisibles) ?> visibles
                </span>
            </div>

            <div class="guest-desktop-table">
                <table class="guest-table hotel-table">
                    <colgroup>
                        <col style="width: 28%;">
                        <col style="width: 27%;">
                        <col style="width: 24%;">
                        <col style="width: 11%;">
                        <col style="width: 10%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Huésped</th>
                            <th>Contacto</th>
                            <th>Origen y vehículo</th>
                            <th class="text-center">Reservas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guestRows as $guestRow): ?>
                            <?php
                            $huesped = $guestRow['huesped'];
                            $vehiculos = $guestRow['vehiculos'];
                            $total_vehiculos = $guestRow['total_vehiculos'];
                            $reservas = intval($huesped['total_reservaciones'] ?? 0);
                            $huespedUrl = url('huespedes/' . $huesped['id']);
                            $huespedEditUrl = url('huespedes/' . $huesped['id'] . '/edit');
                            $huespedBookUrl = url('reservaciones/crear?huesped_id=' . $huesped['id']);
                            ?>
                            <tr class="guest-row">
                                <td>
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="guest-avatar">
                                            <?= htmlspecialchars(strtoupper(substr(trim($huesped['nombre_completo'] ?? 'H'), 0, 1))) ?>
                                        </div>
                                        <div class="min-w-0">
                                            <a href="<?= $huespedUrl ?>"
                                               class="guest-name guest-detail-link"
                                               title="Ver detalle de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                                <span class="guest-detail-text"><?= htmlspecialchars($huesped['nombre_completo']) ?></span>
                                                <i class="fas fa-arrow-right guest-detail-icon" aria-hidden="true"></i>
                                            </a>
                                            <div class="guest-id">ID <?= $huesped['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="guest-cell-stack">
                                        <?php if (!empty($huesped['telefono'])): ?>
                                            <div class="guest-contact-line">
                                                <i class="fas fa-phone text-slate-400"></i>
                                                <a href="tel:<?= htmlspecialchars($huesped['telefono']) ?>"
                                                   class="guest-contact-link"
                                                   title="Llamar a <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                                    <span><?= htmlspecialchars($huesped['telefono']) ?></span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($huesped['email'])): ?>
                                            <div class="guest-contact-line">
                                                <i class="fas fa-envelope text-slate-400"></i>
                                                <a href="mailto:<?= htmlspecialchars($huesped['email']) ?>"
                                                   class="guest-contact-link"
                                                   title="Enviar correo a <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                                    <span><?= htmlspecialchars($huesped['email']) ?></span>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (empty($huesped['telefono']) && empty($huesped['email'])): ?>
                                            <span class="guest-muted">Sin contacto registrado</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="guest-cell-stack">
                                        <?php if (!empty($huesped['procedencia_estado'])): ?>
                                            <div class="guest-meta-line">
                                                <i class="fas fa-map-marker-alt text-slate-400"></i>
                                                <span>
                                                    <?= htmlspecialchars($huesped['procedencia_estado']) ?>
                                                    <?php if (!empty($huesped['procedencia_ciudad'])): ?>
                                                        · <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="guest-muted">Origen no especificado</span>
                                        <?php endif; ?>

                                        <?php if ($total_vehiculos > 0): ?>
                                            <div class="guest-meta-line">
                                                <i class="fas fa-car text-slate-400"></i>
                                                <span>
                                                    <?= $total_vehiculos ?> <?= $total_vehiculos == 1 ? 'vehículo' : 'vehículos' ?>
                                                    <?php if ($total_vehiculos == 1 && !empty($vehiculos[0]['placas'])): ?>
                                                        · <?= htmlspecialchars($vehiculos[0]['placas']) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <span class="guest-muted">Sin vehículo</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if ($reservas > 0): ?>
                                        <a href="<?= $huespedUrl ?>"
                                           class="guest-chip guest-chip-link <?= $reservas >= 3 ? 'guest-chip-warning' : 'guest-chip-brand' ?>"
                                           title="Ver historial de reservas de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <?= $reservas ?>
                                            <?php if ($reservas >= 3): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endif; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="guest-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <a href="<?= $huespedUrl ?>"
                                           class="guest-action guest-action-view"
                                           title="Ver detalle"
                                           aria-label="Ver detalles de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= $huespedEditUrl ?>"
                                           class="guest-action guest-action-edit"
                                           title="Editar huesped"
                                           aria-label="Editar <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= $huespedBookUrl ?>"
                                           class="guest-action guest-action-book"
                                           title="Nueva reservación"
                                           aria-label="Crear reservación para <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="guest-mobile-list p-3">
                <?php foreach ($guestRows as $guestRow): ?>
                    <?php
                    $huesped = $guestRow['huesped'];
                    $vehiculos = $guestRow['vehiculos'];
                    $total_vehiculos = $guestRow['total_vehiculos'];
                    $reservas = intval($huesped['total_reservaciones'] ?? 0);
                    $huespedUrl = url('huespedes/' . $huesped['id']);
                    $huespedEditUrl = url('huespedes/' . $huesped['id'] . '/edit');
                    $huespedBookUrl = url('reservaciones/crear?huesped_id=' . $huesped['id']);
                    ?>
                    <article class="guest-card hotel-mobile-card">
                        <div class="guest-card-top">
                            <div class="guest-avatar">
                                <?= htmlspecialchars(strtoupper(substr(trim($huesped['nombre_completo'] ?? 'H'), 0, 1))) ?>
                            </div>
                            <div class="guest-card-title">
                                <h3 class="guest-name">
                                    <a href="<?= $huespedUrl ?>"
                                       class="guest-detail-link"
                                       title="Ver detalle de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                        <span class="guest-detail-text"><?= htmlspecialchars($huesped['nombre_completo']) ?></span>
                                        <i class="fas fa-arrow-right guest-detail-icon" aria-hidden="true"></i>
                                    </a>
                                </h3>
                                <p class="guest-id">ID <?= $huesped['id'] ?></p>
                            </div>
                            <?php if ($reservas > 0): ?>
                                <a href="<?= $huespedUrl ?>"
                                   class="guest-chip guest-chip-link <?= $reservas >= 3 ? 'guest-chip-warning' : 'guest-chip-brand' ?>"
                                   title="Ver historial de reservas de <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                    <i class="fas fa-calendar-check"></i>
                                    <?= $reservas ?>
                                </a>
                            <?php else: ?>
                                <span class="guest-chip <?= $reservas >= 3 ? 'guest-chip-warning' : 'guest-chip-brand' ?>">
                                    <i class="fas fa-calendar-check"></i>
                                    <?= $reservas ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="guest-card-contact">
                            <?php if (!empty($huesped['telefono'])): ?>
                                <div class="guest-contact-line">
                                    <i class="fas fa-phone text-slate-400"></i>
                                    <a href="tel:<?= htmlspecialchars($huesped['telefono']) ?>"
                                       class="guest-contact-link"
                                       title="Llamar a <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                        <span><?= htmlspecialchars($huesped['telefono']) ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($huesped['email'])): ?>
                                <div class="guest-contact-line">
                                    <i class="fas fa-envelope text-slate-400"></i>
                                    <a href="mailto:<?= htmlspecialchars($huesped['email']) ?>"
                                       class="guest-contact-link"
                                       title="Enviar correo a <?= htmlspecialchars($huesped['nombre_completo']) ?>">
                                        <span><?= htmlspecialchars($huesped['email']) ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($huesped['telefono']) && empty($huesped['email'])): ?>
                                <div class="guest-contact-line text-slate-400">
                                    <i class="fas fa-address-book"></i>
                                    <span>Sin contacto registrado</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="guest-card-meta">
                            <?php if (!empty($huesped['procedencia_estado'])): ?>
                                <span class="guest-chip">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($huesped['procedencia_estado']) ?>
                                    <?php if (!empty($huesped['procedencia_ciudad'])): ?>
                                        · <?= htmlspecialchars($huesped['procedencia_ciudad']) ?>
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <span class="guest-chip">Origen no especificado</span>
                            <?php endif; ?>

                            <?php if ($total_vehiculos > 0): ?>
                                <span class="guest-chip">
                                    <i class="fas fa-car"></i>
                                    <?= $total_vehiculos ?> <?= $total_vehiculos == 1 ? 'vehículo' : 'vehículos' ?>
                                </span>
                            <?php else: ?>
                                <span class="guest-chip">Sin vehículo</span>
                            <?php endif; ?>
                        </div>

                        <div class="guest-card-actions">
                            <a href="<?= $huespedUrl ?>" class="guest-card-action" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                                Ver
                            </a>
                            <a href="<?= $huespedEditUrl ?>" class="guest-card-action" title="Editar huesped">
                                <i class="fas fa-edit"></i>
                                Editar
                            </a>
                            <a href="<?= $huespedBookUrl ?>" class="guest-card-action primary" title="Crear reservacion">
                                <i class="fas fa-calendar-plus"></i>
                                Reservar
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php else: ?>
        <section class="guest-empty hotel-empty-state text-center py-11 px-4">
            <div class="guest-empty-icon mx-auto mb-4 w-14 h-14 rounded-2xl" style="background:var(--guest-brand-soft);color:var(--guest-brand);">
                <i class="fas fa-users text-xl"></i>
            </div>
            <h2 class="text-lg font-black text-slate-800">No se encontraron huéspedes</h2>
            <p class="text-slate-500 mt-2 max-w-md mx-auto">Aún no hay huéspedes con esos filtros. Crea una reservación o registra un huésped para verlo aquí.</p>
            <a href="<?= url('huespedes/create') ?>" class="guest-primary-btn mt-5" title="Registrar nuevo huesped">
                <i class="fas fa-user-plus"></i>
                Nuevo huésped
            </a>
        </section>
        <?php endif; ?>

        <?php if ($total_paginas > 1): ?>
        <nav class="guest-pagination mt-2" aria-label="Paginación de huéspedes">
            <div class="guest-pagination-shell">
            <?php if ($pagina_actual > 1): ?>
                <a href="?page=<?= $pagina_actual - 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                   class="guest-page-link guest-page-control"
                   title="Cambiar pagina"
                   aria-label="Página anterior">
                    <i class="fas fa-chevron-left"></i>
                    <span class="guest-page-control-label">Anterior</span>
                </a>
            <?php else: ?>
                <span class="guest-page-disabled guest-page-control" aria-disabled="true">
                    <i class="fas fa-chevron-left"></i>
                    <span class="guest-page-control-label">Anterior</span>
                </span>
            <?php endif; ?>

            <div class="guest-page-window" aria-label="Páginas disponibles">
                <?php $lastGuestPage = null; ?>
                <?php foreach ($guestPaginationPages as $i): ?>
                    <?php if ($lastGuestPage !== null && $i > $lastGuestPage + 1): ?>
                        <span class="guest-page-ellipsis" aria-hidden="true">…</span>
                    <?php endif; ?>

                    <?php if ($i == $pagina_actual): ?>
                        <span class="guest-page-current" aria-current="page">
                            <?= $i ?>
                        </span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                           class="guest-page-link"
                           title="Ir a pagina"
                           aria-label="Ir a página <?= $i ?>">
                            <?= $i ?>
                        </a>
                    <?php endif; ?>

                    <?php $lastGuestPage = $i; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?page=<?= $pagina_actual + 1 ?>&buscar=<?= urlencode($buscar ?? '') ?>&estado=<?= urlencode($estado_filtro ?? '') ?>"
                   class="guest-page-link guest-page-control"
                   title="Cambiar pagina"
                   aria-label="Página siguiente">
                    <span class="guest-page-control-label">Siguiente</span>
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="guest-page-disabled guest-page-control" aria-disabled="true">
                    <span class="guest-page-control-label">Siguiente</span>
                    <i class="fas fa-chevron-right"></i>
                </span>
            <?php endif; ?>
            </div>
            <p class="guest-pagination-summary">Página <?= number_format($pagina_actual) ?> de <?= number_format($total_paginas) ?></p>
        </nav>
        <?php endif; ?>
        </div>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-guest-live-search-form]');
    const input = document.querySelector('[data-guest-live-search-input]');
    const searchIcon = document.querySelector('[data-guest-search-icon]');

    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-guest-results-region]');
    const getSummary = () => document.querySelector('[data-guest-summary]');

    const setSearching = (isSearching) => {
        form.classList.toggle('is-searching', isSearching);
        getResultsRegion()?.classList.toggle('is-updating', isSearching);
        getResultsRegion()?.setAttribute('aria-busy', isSearching ? 'true' : 'false');
        if (searchIcon) {
            searchIcon.classList.toggle('fa-search', !isSearching);
            searchIcon.classList.toggle('fa-circle-notch', isSearching);
            searchIcon.classList.toggle('fa-spin', isSearching);
        }
    };

    const buildSearchUrl = (targetUrl = null) => {
        const url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(form.action, window.location.origin);
        const data = new FormData(form);

        for (const [key, value] of data.entries()) {
            const normalizedValue = String(value || '').trim();
            if (normalizedValue) {
                url.searchParams.set(key, normalizedValue);
            } else {
                url.searchParams.delete(key);
            }
        }

        if (!targetUrl) {
            url.searchParams.delete('page');
        }

        return url;
    };

    const updateFromDocument = (doc) => {
        const incomingSummary = doc.querySelector('[data-guest-summary]');
        const currentSummary = getSummary();
        if (incomingSummary && currentSummary) {
            currentSummary.innerHTML = incomingSummary.innerHTML;
        }

        const incomingRegion = doc.querySelector('[data-guest-results-region]');
        const currentRegion = getResultsRegion();
        if (incomingRegion && currentRegion) {
            currentRegion.replaceWith(incomingRegion);
        }
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) {
            activeRequest.abort();
        }

        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);

        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar la busqueda');
            }

            const html = await response.text();
            const doc = parser.parseFromString(html, 'text/html');
            updateFromDocument(doc);

            if (pushState) {
                window.history.replaceState({}, '', url.pathname + url.search);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de huespedes:', error);
                HTMLFormElement.prototype.submit.call(form);
            }
        } finally {
            if (activeRequest === controller) {
                activeRequest = null;
                setSearching(false);
            }
        }
    };

    const submitLiveSearch = () => {
        const currentSearch = input.value.trim();
        if (currentSearch === lastQuery) return;
        lastQuery = currentSearch;

        fetchResults(buildSearchUrl());
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        lastQuery = input.value.trim();
        fetchResults(buildSearchUrl());
    });

    form.querySelector('[name="estado"]')?.addEventListener('change', () => {
        lastQuery = input.value.trim();
        fetchResults(buildSearchUrl());
    });

    form.querySelector('.guest-reset-btn')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';

        const estado = form.querySelector('[name="estado"]');
        if (estado) {
            estado.value = '';
        }

        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-guest-results-region] .guest-pagination a');
        if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault();

        fetchResults(buildSearchUrl(link.href));
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) {
            estado.value = params.get('estado') || '';
        }

        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });

    input.addEventListener('compositionstart', () => {
        isComposing = true;
    });

    input.addEventListener('compositionend', () => {
        isComposing = false;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });

    input.addEventListener('input', () => {
        if (isComposing) return;
        window.clearTimeout(liveSearchTimer);
        liveSearchTimer = window.setTimeout(submitLiveSearch, delay);
    });
})();
</script>
