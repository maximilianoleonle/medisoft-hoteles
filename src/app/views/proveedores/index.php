<?php
$proveedores = $proveedores ?? [];
$resumen = $resumen ?? ['total' => 0, 'activos' => 0, 'inactivos' => 0];
$filtros = $filtros ?? [];
$tablaDisponible = $tablaDisponible ?? false;

if (!function_exists('prov_safe')) {
    function prov_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('prov_url')) {
    function prov_url(array $overrides = [])
    {
        $query = array_merge($_GET, $overrides);
        $query = array_filter($query, static function ($value) {
            return $value !== null && $value !== '';
        });

        return url('proveedores' . (!empty($query) ? '?' . http_build_query($query) : ''));
    }
}

if (!function_exists('prov_inicial')) {
    function prov_inicial($value)
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars(strtoupper(mb_substr($text !== '' ? $text : 'P', 0, 1, 'UTF-8')), ENT_QUOTES, 'UTF-8');
    }
}

$buscar = (string)($filtros['buscar'] ?? '');
$estado = (string)($filtros['estado'] ?? 'activos');
$visibles = count($proveedores);
?>

<style>
.providers-page {
    --pv-brand: var(--brand-primary, #1B2746);
    --pv-brand-2: var(--brand-secondary, #0F172A);
    --pv-brand-dark: color-mix(in srgb, var(--pv-brand), #000 20%);
    --pv-brand-soft: color-mix(in srgb, var(--pv-brand) 5%, #FBF8F2);
    --pv-gold: var(--brand-accent, #BD9441);
    --pv-gold-soft: color-mix(in srgb, var(--pv-gold) 15%, #FFFFFF);
    --pv-gold-line: color-mix(in srgb, var(--pv-gold) 42%, #E4D4B0);
    --pv-gold-ink: color-mix(in srgb, var(--pv-gold) 72%, #000);
    --pv-ivory: #F6F2EA;
    --pv-ivory-2: #FBF8F2;
    --pv-surface: #FFFFFF;
    --pv-surface-warm: #FCFAF5;
    --pv-border: color-mix(in srgb, var(--pv-brand) 7%, #E7E1D4);
    --pv-ring: color-mix(in srgb, var(--pv-gold) 32%, transparent);
    --pv-text: #171717;
    --pv-muted: #667085;
    --pv-heading: #111827;
    --pv-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --pv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --pv-success: #1E9E63;
    --pv-success-bg: #E7F4EC;
    --pv-info: #2F77E0;
    --pv-info-bg: #E6EFFC;
    --pv-danger: #B4392B;
    --pv-danger-bg: #F8EAE5;
    min-height: 100%;
    color: var(--pv-text);
    font-family: var(--pv-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--pv-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--pv-ivory-2), var(--pv-ivory));
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.providers-page .pv-shell {
    display: grid;
    gap: 14px;
}

/* Encabezado limpio */
.providers-page .pv-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
}
.providers-page .pv-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--pv-gold), var(--pv-brand) 54%, color-mix(in srgb, var(--pv-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--pv-brand) 72%, transparent);
}
.providers-page .pv-kicker {
    margin: 0 0 2px;
    color: var(--pv-muted);
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.providers-page .pv-title {
    margin: 0;
    font-family: var(--pv-serif);
    color: var(--pv-heading);
    font-weight: 700;
    font-size: clamp(2.2rem, 4vw, 3.1rem);
    line-height: .98;
}
.providers-page .pv-subtitle {
    max-width: 44rem;
    margin: 9px 0 0;
    color: var(--pv-muted);
    font-size: .94rem;
    font-weight: 600;
    line-height: 1.5;
}

/* Botones base */
.providers-page .pv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 42px;
    padding: 0 18px;
    border-radius: 11px;
    border: 1px solid transparent;
    font-weight: 700;
    font-size: .9rem;
    line-height: 1;
    cursor: pointer;
    text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.providers-page .pv-btn:hover { transform: translateY(-1px); }
.providers-page .pv-btn:active { transform: translateY(0) scale(.98); }
.providers-page .pv-btn:focus-visible {
    outline: 3px solid var(--pv-ring);
    outline-offset: 2px;
}
.providers-page .pv-btn-gold {
    background: linear-gradient(135deg, var(--pv-gold), color-mix(in srgb, var(--pv-gold) 76%, #000));
    color: #fff;
    box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--pv-gold) 58%, transparent);
}
.providers-page .pv-btn-brand {
    background: linear-gradient(135deg, var(--pv-brand), var(--pv-brand-2));
    color: #fff;
    box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--pv-brand) 60%, transparent);
}
.providers-page .pv-btn-muted {
    background: var(--pv-surface);
    border-color: var(--pv-border);
    color: var(--pv-muted);
}

/* Tira de resumen */
.providers-page .pv-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.providers-page .pv-summary-item {
    background: var(--pv-surface);
    border: 1px solid var(--pv-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22);
}
.providers-page .pv-summary-label {
    color: var(--pv-muted);
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.providers-page .pv-summary-value {
    margin-top: 2px;
    font-family: var(--pv-serif);
    font-size: 1.7rem;
    font-weight: 700;
    line-height: 1.1;
    color: var(--pv-heading);
}
.providers-page .pv-summary-value.is-active { color: var(--pv-success); }
.providers-page .pv-summary-value.is-inactive { color: var(--pv-muted); }

/* Panel */
.providers-page .pv-panel {
    background: var(--pv-surface);
    border: 1px solid var(--pv-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28);
}

/* Filtros */
.providers-page .pv-filter-form {
    display: grid;
    grid-template-columns: minmax(240px, 1fr) minmax(170px, 220px) auto auto;
    gap: 10px;
    align-items: center;
}
.providers-page .pv-control {
    width: 100%;
    min-height: 40px;
    border: 1px solid var(--pv-border);
    background: var(--pv-surface-warm);
    border-radius: 11px;
    padding: 0 12px;
    color: var(--pv-text);
    font-weight: 600;
    font-size: .88rem;
    transition: border-color .16s ease, box-shadow .16s ease;
}
.providers-page .pv-control:focus {
    border-color: var(--pv-gold);
    box-shadow: 0 0 0 3px var(--pv-ring);
    outline: none;
}
.providers-page select.pv-control { cursor: pointer; }
.providers-page .pv-search { position: relative; }
.providers-page .pv-search .pv-control { padding-left: 38px; }
.providers-page .pv-search-icon {
    position: absolute;
    left: 13px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--pv-muted);
    font-size: .9rem;
    pointer-events: none;
}

/* Cabecera de panel */
.providers-page .pv-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--pv-border);
}
.providers-page .pv-panel-title {
    font-size: .85rem;
    font-weight: 700;
    color: var(--pv-heading);
}
.providers-page .pv-panel-sub {
    font-size: .75rem;
    color: var(--pv-muted);
}
.providers-page .pv-count-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: var(--pv-gold-soft);
    color: var(--pv-gold-ink);
    border: 1px solid var(--pv-gold-line);
    font-size: .72rem;
    font-weight: 700;
    white-space: nowrap;
}

/* Tabla */
.providers-page .pv-desktop { display: none; }
.providers-page .pv-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    font-size: .85rem;
}
.providers-page .pv-table thead {
    background: var(--pv-surface-warm);
    border-bottom: 1px solid var(--pv-border);
}
.providers-page .pv-table th {
    padding: 12px 16px;
    color: var(--pv-muted);
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
}
.providers-page .pv-table th.is-end { text-align: right; }
.providers-page .pv-table td {
    padding: 13px 16px;
    vertical-align: middle;
}
.providers-page .pv-row {
    border-bottom: 1px solid var(--pv-border);
    transition: background .16s ease, box-shadow .16s ease;
}
.providers-page .pv-row:last-child { border-bottom: 0; }
.providers-page .pv-row:hover {
    background: var(--pv-ivory-2);
    box-shadow: 0 10px 24px -24px rgba(27,39,70,.48);
}
.providers-page .pv-id-cell { display: flex; align-items: center; gap: 11px; min-width: 0; }
.providers-page .pv-avatar {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    font-weight: 700;
    font-size: .9rem;
    background: var(--pv-avatar-bg, #EEF2FF);
    color: var(--pv-avatar-fg, #3730A3);
    border: 1px solid var(--pv-avatar-border, #C7D2FE);
}
.providers-page .pv-name {
    font-weight: 700;
    color: var(--pv-heading);
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.providers-page .pv-name-link { color: inherit; text-decoration: none; }
.providers-page .pv-name-link:hover { text-decoration: underline; text-decoration-color: var(--pv-gold); text-underline-offset: 3px; }
.providers-page .pv-sub {
    color: var(--pv-muted);
    font-size: .74rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.providers-page .pv-line {
    display: flex;
    align-items: center;
    gap: .45rem;
    min-width: 0;
    color: #334155;
    font-size: .82rem;
    line-height: 1.35;
}
.providers-page .pv-line + .pv-line { margin-top: 3px; }
.providers-page .pv-line i { color: var(--pv-muted); width: 14px; text-align: center; flex-shrink: 0; }
.providers-page .pv-line span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.providers-page .pv-link { color: inherit; text-decoration: none; }
.providers-page .pv-link:hover { color: var(--pv-gold-ink); text-decoration: underline; text-underline-offset: 3px; }
.providers-page .pv-muted-text { color: var(--pv-muted); font-size: .8rem; }
.providers-page .pv-rfc { font-family: 'Roboto Mono', ui-monospace, SFMono-Regular, Menlo, monospace; color: #334155; font-size: .82rem; }

/* Badge de estado */
.providers-page .pv-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .74rem;
    font-weight: 700;
    border: 1px solid transparent;
}
.providers-page .pv-badge.is-active {
    color: color-mix(in srgb, var(--pv-success) 78%, #000);
    background: var(--pv-success-bg);
    border-color: color-mix(in srgb, var(--pv-success) 26%, #fff);
}
.providers-page .pv-badge.is-inactive {
    color: #5F5E5A;
    background: #F1EFE8;
    border-color: #D3D1C7;
}

/* Acciones por fila */
.providers-page .pv-actions { display: flex; justify-content: flex-end; gap: 7px; }
.providers-page .pv-actions form { display: inline-flex; margin: 0; }
.providers-page .pv-action {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--pv-surface-warm);
    border: 1px solid var(--pv-border);
    color: var(--pv-muted);
    cursor: pointer;
    text-decoration: none;
    transition: transform .16s ease, background .16s ease, color .16s ease, border-color .16s ease;
}
.providers-page .pv-action:hover { transform: translateY(-1px); }
.providers-page .pv-action:focus-visible { outline: 2px solid var(--pv-gold); outline-offset: 2px; }
.providers-page .pv-action-view { color: var(--pv-info); }
.providers-page .pv-action-view:hover { background: var(--pv-info-bg); }
.providers-page .pv-action-edit { color: var(--pv-gold-ink); }
.providers-page .pv-action-edit:hover { background: var(--pv-gold-soft); }
.providers-page .pv-action-off { color: var(--pv-danger); }
.providers-page .pv-action-off:hover { background: var(--pv-danger-bg); }
.providers-page .pv-action-on { color: var(--pv-success); }
.providers-page .pv-action-on:hover { background: var(--pv-success-bg); }

/* Avatares rotativos */
.providers-page .pv-row:nth-child(6n+1) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+1) .pv-avatar { --pv-avatar-bg:#EEF2FF; --pv-avatar-fg:#3730A3; --pv-avatar-border:#C7D2FE; }
.providers-page .pv-row:nth-child(6n+2) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+2) .pv-avatar { --pv-avatar-bg:#ECFDF5; --pv-avatar-fg:#047857; --pv-avatar-border:#A7F3D0; }
.providers-page .pv-row:nth-child(6n+3) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+3) .pv-avatar { --pv-avatar-bg:#FFF7ED; --pv-avatar-fg:#C2410C; --pv-avatar-border:#FED7AA; }
.providers-page .pv-row:nth-child(6n+4) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+4) .pv-avatar { --pv-avatar-bg:#FDF2F8; --pv-avatar-fg:#BE185D; --pv-avatar-border:#FBCFE8; }
.providers-page .pv-row:nth-child(6n+5) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+5) .pv-avatar { --pv-avatar-bg:#F0FDFA; --pv-avatar-fg:#0F766E; --pv-avatar-border:#99F6E4; }
.providers-page .pv-row:nth-child(6n+6) .pv-avatar, .providers-page .pv-mobile-card:nth-child(6n+6) .pv-avatar { --pv-avatar-bg:#F8FAFC; --pv-avatar-fg:#475569; --pv-avatar-border:#CBD5E1; }

/* Tarjetas móviles */
.providers-page .pv-mobile { display: grid; gap: 10px; padding: 12px; }
.providers-page .pv-mobile-card {
    background: var(--pv-surface);
    border: 1px solid var(--pv-border);
    border-radius: 16px;
    padding: 12px;
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25);
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}
.providers-page .pv-mobile-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--pv-gold) 38%, var(--pv-border));
    box-shadow: 0 2px 4px rgba(27,39,70,.05), 0 16px 32px -22px rgba(27,39,70,.32);
}
.providers-page .pv-mobile-top {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 10px;
    align-items: start;
}
.providers-page .pv-mobile-contact { display: grid; gap: 5px; margin-top: 10px; }
.providers-page .pv-mobile-actions {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 7px;
    margin-top: 11px;
}
.providers-page .pv-mobile-actions form { display: flex; }
.providers-page .pv-card-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    width: 100%;
    min-height: 36px;
    padding: 0 .5rem;
    border-radius: 10px;
    background: var(--pv-surface-warm);
    border: 1px solid var(--pv-border);
    color: var(--pv-text);
    font-size: .76rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: background .16s ease, color .16s ease, border-color .16s ease;
}
.providers-page .pv-card-btn:hover { border-color: var(--pv-gold-line); color: var(--pv-gold-ink); background: var(--pv-gold-soft); }
.providers-page .pv-card-btn.is-off { color: var(--pv-danger); }
.providers-page .pv-card-btn.is-off:hover { background: var(--pv-danger-bg); border-color: color-mix(in srgb, var(--pv-danger) 30%, var(--pv-border)); color: var(--pv-danger); }
.providers-page .pv-card-btn.is-on { color: var(--pv-success); }
.providers-page .pv-card-btn.is-on:hover { background: var(--pv-success-bg); border-color: color-mix(in srgb, var(--pv-success) 30%, var(--pv-border)); color: var(--pv-success); }

/* Estado vacío y aviso */
.providers-page .pv-empty {
    text-align: center;
    padding: 44px 18px;
    background: var(--pv-ivory-2);
    border: 1px dashed var(--pv-border);
    border-radius: 16px;
}
.providers-page .pv-empty-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: var(--pv-gold-soft);
    color: var(--pv-gold-ink);
    font-size: 1.3rem;
}
.providers-page .pv-empty h2 { color: var(--pv-brand); font-size: 1.1rem; font-weight: 700; }
.providers-page .pv-empty p { color: var(--pv-muted); margin: 8px auto 0; max-width: 28rem; font-size: .9rem; }
.providers-page .pv-notice {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 16px 18px;
    background: var(--pv-gold-soft);
    border: 1px solid var(--pv-gold-line);
    border-radius: 16px;
    color: var(--pv-text);
}
.providers-page .pv-notice i { color: var(--pv-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.providers-page .pv-notice strong { color: var(--pv-heading); display: block; margin-bottom: 2px; }
.providers-page .pv-notice p { color: var(--pv-muted); font-size: .88rem; margin: 0; }

/* Búsqueda en vivo */
.providers-page [data-prov-results-region] { transition: opacity .18s ease, filter .18s ease; }
.providers-page [data-prov-results-region].is-updating { opacity: .58; filter: saturate(.88); pointer-events: none; }

@media (min-width: 768px) {
    .providers-page .pv-desktop { display: block; }
    .providers-page .pv-mobile { display: none; }
}
@media (max-width: 767px) {
    .providers-page .pv-filter-form { grid-template-columns: 1fr; }
    .providers-page .pv-filter-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .providers-page .pv-summary-value { font-size: 1.4rem; }
    .providers-page .pv-title { font-size: 2rem; }
}

/* Documents-aligned polish: calmer ink, lighter weights, same provider rhythm. */
.providers-page {
    --pv-gold-ink: color-mix(in srgb, var(--pv-gold) 58%, var(--pv-brand));
    --pv-border: color-mix(in srgb, var(--pv-brand) 6%, #E9E1D6);
    --pv-text: color-mix(in srgb, var(--pv-brand) 46%, #707B8C);
    --pv-muted: #8791A2;
    --pv-heading: color-mix(in srgb, var(--pv-brand) 66%, #566172);
}
.providers-page .font-bold,
.providers-page .font-semibold {
    font-weight: 650 !important;
}
.providers-page .pv-shell {
    min-width: 0;
    width: 100%;
    max-width: 100%;
}
.providers-page .pv-hero-section {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr);
    align-items: start !important;
    justify-content: stretch !important;
    gap: 12px !important;
    min-width: 0;
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    padding: 2px 0 6px;
}
.providers-page .pv-title-lockup {
    justify-self: start;
    max-width: min(100%, 780px);
}
.providers-page .pv-title-lockup > div:last-child {
    min-width: 0;
}
.providers-page .pv-kicker,
.providers-page .pv-title,
.providers-page .pv-btn,
.providers-page .pv-summary-label,
.providers-page .pv-summary-value,
.providers-page .pv-panel-title,
.providers-page .pv-count-pill,
.providers-page .pv-table th,
.providers-page .pv-avatar,
.providers-page .pv-name,
.providers-page .pv-badge,
.providers-page .pv-card-btn,
.providers-page .pv-empty h2,
.providers-page .pv-notice strong {
    font-weight: 650;
}
.providers-page .pv-title {
    color: var(--pv-heading);
    font-size: clamp(2.1rem, 4vw, 3rem);
    overflow-wrap: anywhere;
}
.providers-page .pv-subtitle {
    max-width: 48rem;
    color: var(--pv-muted);
    font-weight: 500;
}
.providers-page .pv-btn {
    min-height: 44px;
    white-space: nowrap;
}
.providers-page .pv-btn-gold {
    background: linear-gradient(135deg, color-mix(in srgb, var(--pv-gold) 86%, #fff), color-mix(in srgb, var(--pv-gold) 72%, var(--pv-brand)));
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--pv-gold) 42%, transparent);
}
.providers-page .pv-btn-brand {
    background: color-mix(in srgb, var(--pv-gold) 10%, #FFFFFF);
    border-color: color-mix(in srgb, var(--pv-gold) 28%, #ECE1D1);
    color: var(--pv-gold-ink);
    box-shadow: 0 8px 18px -18px color-mix(in srgb, var(--pv-gold) 34%, transparent);
}
.providers-page .pv-btn-muted {
    background: rgba(255,255,255,.86);
    border-color: var(--pv-border);
    color: var(--pv-muted);
}
.providers-page .pv-summary-item,
.providers-page .pv-panel,
.providers-page .pv-mobile-card {
    background: rgba(255,255,255,.86);
    border-color: var(--pv-border);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}
.providers-page .pv-summary-value.is-active {
    color: color-mix(in srgb, var(--pv-success) 68%, var(--pv-text));
}
.providers-page .pv-summary-value.is-inactive {
    color: color-mix(in srgb, var(--pv-muted) 82%, var(--pv-text));
}
.providers-page .pv-control {
    min-height: 44px;
    color: var(--pv-text);
    font-weight: 560;
}
.providers-page .pv-control::placeholder {
    color: color-mix(in srgb, var(--pv-muted) 82%, #B8C0CB);
    font-weight: 520;
}
.providers-page .pv-panel-head,
.providers-page .pv-table thead {
    background: var(--pv-surface-warm);
}
.providers-page .pv-count-pill {
    background: color-mix(in srgb, var(--pv-gold) 10%, #FFFFFF);
    border-color: color-mix(in srgb, var(--pv-gold) 28%, #ECE1D1);
}
.providers-page .pv-row:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}
.providers-page .pv-name,
.providers-page .pv-rfc {
    color: var(--pv-heading);
}
.providers-page .pv-line {
    color: var(--pv-text);
}
.providers-page .pv-avatar {
    color: color-mix(in srgb, var(--pv-avatar-fg, var(--pv-brand)) 76%, var(--pv-text));
    background: color-mix(in srgb, var(--pv-avatar-bg, var(--pv-surface-warm)) 74%, #FFFFFF);
    border-color: color-mix(in srgb, var(--pv-avatar-border, var(--pv-border)) 78%, #FFFFFF);
}
.providers-page .pv-badge.is-active {
    color: color-mix(in srgb, var(--pv-success) 70%, var(--pv-text));
}
.providers-page .pv-badge.is-inactive {
    color: color-mix(in srgb, var(--pv-muted) 78%, var(--pv-text));
    background: var(--pv-surface-warm);
    border-color: var(--pv-border);
}
.providers-page .pv-action {
    width: 44px;
    min-width: 44px;
    height: 44px;
    border-radius: 11px;
    border-color: var(--pv-border);
    background: var(--pv-surface-warm);
    color: var(--pv-muted);
}
.providers-page .pv-action-view {
    color: var(--pv-info);
}
.providers-page .pv-action-edit {
    color: var(--pv-gold-ink);
}
.providers-page .pv-action-off {
    color: color-mix(in srgb, var(--pv-danger) 76%, var(--pv-text));
}
.providers-page .pv-action-on {
    color: color-mix(in srgb, var(--pv-success) 76%, var(--pv-text));
}
.providers-page .pv-card-btn {
    min-height: 44px;
    color: var(--pv-text);
}
.providers-page .pv-card-btn.is-off {
    color: color-mix(in srgb, var(--pv-danger) 76%, var(--pv-text));
}
.providers-page .pv-card-btn.is-on {
    color: color-mix(in srgb, var(--pv-success) 76%, var(--pv-text));
}
.providers-page .pv-empty {
    background: rgba(251,248,242,.78);
    border-color: var(--pv-border);
}
.providers-page .pv-empty h2 {
    color: var(--pv-heading);
}
.providers-page .pv-notice {
    background: color-mix(in srgb, var(--pv-gold) 10%, #FFFFFF);
    border-color: color-mix(in srgb, var(--pv-gold) 28%, #ECE1D1);
}

@media (min-width: 1024px) {
    .providers-page .pv-hero-section {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center !important;
        gap: 16px 28px !important;
        padding-bottom: 10px;
    }
    .providers-page .pv-hero-section > .pv-btn {
        justify-self: end;
        min-width: max-content;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .providers-page .pv-filter-form {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
    .providers-page .pv-search {
        grid-column: 1 / -1;
    }
}

@media (max-width: 767px) {
    .providers-page {
        --pv-mobile-ink: color-mix(in srgb, var(--pv-brand) 62%, #6F7784);
        --pv-mobile-text: color-mix(in srgb, var(--pv-brand) 42%, #778394);
        --pv-mobile-muted: #98A2B3;
        padding: 10px 12px 18px !important;
        background:
            radial-gradient(520px 220px at 92% -6%, color-mix(in srgb, var(--pv-gold) 12%, transparent), transparent 62%),
            linear-gradient(180deg, #FBF8F0 0%, #F3EDE2 100%);
    }
    .providers-page .pv-shell {
        gap: 10px;
    }
    .providers-page .pv-hero-section {
        position: relative;
        overflow: hidden;
        min-height: 126px;
        margin: 0;
        padding: 16px 14px 14px;
        border-radius: 22px;
        color: #fff;
        background:
            radial-gradient(circle at 88% 14%, rgba(255,255,255,.17), transparent 92px),
            linear-gradient(135deg, color-mix(in srgb, var(--pv-brand) 94%, #000) 0%, color-mix(in srgb, var(--pv-brand-2) 78%, var(--pv-gold)) 100%);
        box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--pv-brand) 72%, transparent);
    }
    .providers-page .pv-hero-section::after {
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
    .providers-page .pv-title-lockup {
        position: relative;
        z-index: 1;
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
        align-items: start;
    }
    .providers-page .pv-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
        background: rgba(255,255,255,.16);
        color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: none;
        backdrop-filter: blur(10px);
    }
    .providers-page .pv-kicker {
        color: rgba(255,255,255,.76);
        font-size: .64rem;
    }
    .providers-page .pv-title {
        color: #fff;
        font-size: clamp(2rem, 12vw, 2.55rem);
    }
    .providers-page .pv-subtitle {
        color: rgba(255,255,255,.72);
        font-size: .84rem;
        line-height: 1.43;
    }
    .providers-page .pv-hero-section > .pv-btn {
        position: relative;
        z-index: 1;
        width: 100%;
        background: rgba(255,255,255,.94);
        border-color: rgba(255,255,255,.28);
        color: var(--pv-brand);
        box-shadow: 0 12px 24px -18px rgba(0,0,0,.38);
    }
    .providers-page .pv-summary {
        display: flex;
        grid-template-columns: none;
        gap: 8px;
        overflow-x: auto;
        padding: 0 2px 2px;
        margin: 0 -2px;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
    }
    .providers-page .pv-summary::-webkit-scrollbar {
        display: none;
    }
    .providers-page .pv-summary-item {
        flex: 0 0 145px;
        padding: 10px 12px;
        border-radius: 14px;
    }
    .providers-page .pv-summary-label {
        font-size: .62rem;
    }
    .providers-page .pv-summary-value {
        color: var(--pv-mobile-ink);
        font-size: 1.36rem;
    }
    .providers-page .pv-filter-form {
        gap: 8px;
    }
    .providers-page .pv-filter-form .pv-btn {
        width: 100%;
    }
    .providers-page .pv-panel {
        border-radius: 15px;
    }
    .providers-page .pv-panel-head {
        align-items: flex-start;
        padding: 12px;
    }
    .providers-page .pv-panel-title,
    .providers-page .pv-mobile-card .pv-name,
    .providers-page .pv-rfc {
        color: var(--pv-mobile-ink);
    }
    .providers-page .pv-mobile {
        padding: 10px;
    }
    .providers-page .pv-mobile-card {
        border-radius: 15px;
        padding: 12px;
    }
    .providers-page .pv-mobile-top {
        grid-template-columns: auto minmax(0, 1fr);
    }
    .providers-page .pv-mobile-top .pv-badge {
        grid-column: 1 / -1;
        width: fit-content;
    }
    .providers-page .pv-mobile-contact {
        gap: 7px;
    }
    .providers-page .pv-mobile-actions {
        grid-template-columns: 1fr;
    }
}

@media (prefers-reduced-motion: reduce) {
    .providers-page *,
    .providers-page *::before,
    .providers-page *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<div class="providers-page p-4 sm:p-6">
    <div class="pv-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <section class="pv-hero-section flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="pv-title-lockup">
                <div class="pv-hero-icon"><i class="fas fa-truck-field"></i></div>
                <div>
                    <p class="pv-kicker">Compras y abastecimiento</p>
                    <h1 class="pv-title">Proveedores</h1>
                    <p class="pv-subtitle">Tu lista de proveedores del hotel: a qui&eacute;n le compras, c&oacute;mo contactarlo y si sigue activo. Aqu&iacute; los preparas; las compras y los pagos se registran despu&eacute;s.</p>
                </div>
            </div>
            <a class="pv-btn pv-btn-gold" href="<?= url('proveedores/crear') ?>" title="Registrar nuevo proveedor">
                <i class="fas fa-plus"></i>
                Nuevo proveedor
            </a>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="pv-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para empezar a registrar tus proveedores.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="pv-summary">
                <div class="pv-summary-item">
                    <p class="pv-summary-label">Total</p>
                    <p class="pv-summary-value"><?= number_format((int)($resumen['total'] ?? 0)) ?></p>
                </div>
                <div class="pv-summary-item">
                    <p class="pv-summary-label">Activos</p>
                    <p class="pv-summary-value is-active"><?= number_format((int)($resumen['activos'] ?? 0)) ?></p>
                </div>
                <div class="pv-summary-item">
                    <p class="pv-summary-label">Inactivos</p>
                    <p class="pv-summary-value is-inactive"><?= number_format((int)($resumen['inactivos'] ?? 0)) ?></p>
                </div>
            </section>

            <section class="pv-panel p-3 md:p-4">
                <form method="GET" action="<?= url('proveedores') ?>" class="pv-filter-form" data-prov-live-search-form data-auto-filter-form>
                    <div class="pv-search">
                        <i class="fas fa-search pv-search-icon" data-prov-search-icon></i>
                        <input class="pv-control" type="search" name="buscar" autocomplete="off" inputmode="search"
                               value="<?= prov_safe($buscar, '') ?>"
                               placeholder="Buscar por nombre, RFC, tel&eacute;fono o correo"
                               data-prov-live-search-input>
                    </div>
                    <select class="pv-control" name="estado" title="Filtrar por estado">
                        <option value="activos" <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                    </select>
                    <button class="pv-btn pv-btn-brand pv-filter-submit" type="submit">
                        <i class="fas fa-filter"></i>
                        Filtrar
                    </button>
                    <a class="pv-btn pv-btn-muted pv-reset" href="<?= url('proveedores') ?>">
                        <i class="fas fa-times"></i>
                        Limpiar
                    </a>
                </form>
            </section>

            <div data-prov-results-region aria-live="polite" aria-busy="false">
                <?php if (empty($proveedores)): ?>
                    <section class="pv-empty">
                        <div class="pv-empty-icon"><i class="fas fa-truck-field"></i></div>
                        <h2>A&uacute;n no encontramos proveedores</h2>
                        <p>No hay proveedores con estos filtros. Crea el primero del hotel o cambia la b&uacute;squeda.</p>
                        <a class="pv-btn pv-btn-gold mt-4" href="<?= url('proveedores/crear') ?>" style="display:inline-flex">
                            <i class="fas fa-plus"></i>
                            Nuevo proveedor
                        </a>
                    </section>
                <?php else: ?>
                    <section class="pv-panel overflow-hidden">
                        <div class="pv-panel-head">
                            <div>
                                <div class="pv-panel-title">Lista de proveedores</div>
                                <div class="pv-panel-sub">Contacto y estado para tus compras.</div>
                            </div>
                            <span class="pv-count-pill">
                                <i class="fas fa-list"></i>
                                <?= number_format($visibles) ?> <?= $visibles === 1 ? 'proveedor' : 'proveedores' ?>
                            </span>
                        </div>

                        <div class="pv-desktop">
                            <table class="pv-table">
                                <colgroup>
                                    <col style="width: 30%;">
                                    <col style="width: 28%;">
                                    <col style="width: 18%;">
                                    <col style="width: 12%;">
                                    <col style="width: 12%;">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>Proveedor</th>
                                        <th>Contacto</th>
                                        <th>RFC</th>
                                        <th>Estado</th>
                                        <th class="is-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <?php
                                        $activo = (int)($proveedor['activo'] ?? 0) === 1;
                                        $provId = (int)($proveedor['id'] ?? 0);
                                        $provUrl = url('proveedores/' . $provId);
                                        $tieneRazon = trim((string)($proveedor['razon_social'] ?? '')) !== '';
                                        $tieneTel = trim((string)($proveedor['telefono'] ?? '')) !== '';
                                        $tieneMail = trim((string)($proveedor['email'] ?? '')) !== '';
                                        $tieneRfc = trim((string)($proveedor['rfc'] ?? '')) !== '';
                                        ?>
                                        <tr class="pv-row" data-easy-href="<?= prov_safe($provUrl, '') ?>" role="link" tabindex="0" title="Abrir proveedor <?= prov_safe($proveedor['nombre']) ?>" aria-label="Abrir proveedor <?= prov_safe($proveedor['nombre']) ?>">
                                            <td>
                                                <div class="pv-id-cell">
                                                    <div class="pv-avatar"><?= prov_inicial($proveedor['nombre'] ?? '') ?></div>
                                                    <div class="min-w-0">
                                                        <a class="pv-name pv-name-link" href="<?= $provUrl ?>" title="Ver ficha de <?= prov_safe($proveedor['nombre']) ?>">
                                                            <?= prov_safe($proveedor['nombre']) ?>
                                                        </a>
                                                        <?php if ($tieneRazon): ?>
                                                            <div class="pv-sub"><?= prov_safe($proveedor['razon_social']) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($tieneTel): ?>
                                                    <div class="pv-line">
                                                        <i class="fas fa-phone"></i>
                                                        <a class="pv-link" href="tel:<?= prov_safe($proveedor['telefono'], '') ?>"><?= prov_safe($proveedor['telefono']) ?></a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($tieneMail): ?>
                                                    <div class="pv-line">
                                                        <i class="fas fa-envelope"></i>
                                                        <a class="pv-link" href="mailto:<?= prov_safe($proveedor['email'], '') ?>"><?= prov_safe($proveedor['email']) ?></a>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!$tieneTel && !$tieneMail): ?>
                                                    <span class="pv-muted-text">Sin contacto registrado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tieneRfc): ?>
                                                    <span class="pv-rfc"><?= prov_safe($proveedor['rfc']) ?></span>
                                                <?php else: ?>
                                                    <span class="pv-muted-text">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="pv-badge <?= $activo ? 'is-active' : 'is-inactive' ?>">
                                                    <i class="fas <?= $activo ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                                                    <?= $activo ? 'Activo' : 'Inactivo' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="pv-actions">
                                                    <a class="pv-action pv-action-view" href="<?= $provUrl ?>" title="Ver ficha" aria-label="Ver proveedor">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a class="pv-action pv-action-edit" href="<?= url('proveedores/' . $provId . '/editar') ?>" title="Editar" aria-label="Editar proveedor">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    <?php if ($activo): ?>
                                                        <form method="POST" action="<?= url('proveedores/' . $provId . '/desactivar') ?>">
                                                            <?= csrf_field() ?>
                                                            <button class="pv-action pv-action-off" type="submit" title="Desactivar" aria-label="Desactivar proveedor">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" action="<?= url('proveedores/' . $provId . '/reactivar') ?>">
                                                            <?= csrf_field() ?>
                                                            <button class="pv-action pv-action-on" type="submit" title="Reactivar" aria-label="Reactivar proveedor">
                                                                <i class="fas fa-rotate-left"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="pv-mobile">
                            <?php foreach ($proveedores as $proveedor): ?>
                                <?php
                                $activo = (int)($proveedor['activo'] ?? 0) === 1;
                                $provId = (int)($proveedor['id'] ?? 0);
                                $provUrl = url('proveedores/' . $provId);
                                $tieneRazon = trim((string)($proveedor['razon_social'] ?? '')) !== '';
                                $tieneTel = trim((string)($proveedor['telefono'] ?? '')) !== '';
                                $tieneMail = trim((string)($proveedor['email'] ?? '')) !== '';
                                $tieneRfc = trim((string)($proveedor['rfc'] ?? '')) !== '';
                                ?>
                                <article class="pv-mobile-card" data-easy-href="<?= prov_safe($provUrl, '') ?>" role="link" tabindex="0" title="Abrir proveedor <?= prov_safe($proveedor['nombre']) ?>" aria-label="Abrir proveedor <?= prov_safe($proveedor['nombre']) ?>">
                                    <div class="pv-mobile-top">
                                        <div class="pv-avatar"><?= prov_inicial($proveedor['nombre'] ?? '') ?></div>
                                        <div class="min-w-0">
                                            <a class="pv-name pv-name-link" href="<?= $provUrl ?>"><?= prov_safe($proveedor['nombre']) ?></a>
                                            <?php if ($tieneRazon): ?>
                                                <div class="pv-sub"><?= prov_safe($proveedor['razon_social']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <span class="pv-badge <?= $activo ? 'is-active' : 'is-inactive' ?>">
                                            <i class="fas <?= $activo ? 'fa-circle-check' : 'fa-circle-pause' ?>"></i>
                                            <?= $activo ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </div>

                                    <div class="pv-mobile-contact">
                                        <?php if ($tieneTel): ?>
                                            <div class="pv-line">
                                                <i class="fas fa-phone"></i>
                                                <a class="pv-link" href="tel:<?= prov_safe($proveedor['telefono'], '') ?>"><?= prov_safe($proveedor['telefono']) ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($tieneMail): ?>
                                            <div class="pv-line">
                                                <i class="fas fa-envelope"></i>
                                                <a class="pv-link" href="mailto:<?= prov_safe($proveedor['email'], '') ?>"><?= prov_safe($proveedor['email']) ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <div class="pv-line">
                                            <i class="fas fa-id-card"></i>
                                            <span><?= $tieneRfc ? 'RFC ' . prov_safe($proveedor['rfc']) : 'Sin RFC' ?></span>
                                        </div>
                                    </div>

                                    <div class="pv-mobile-actions">
                                        <a class="pv-card-btn" href="<?= $provUrl ?>">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                        <a class="pv-card-btn" href="<?= url('proveedores/' . $provId . '/editar') ?>">
                                            <i class="fas fa-pen"></i>
                                            Editar
                                        </a>
                                        <?php if ($activo): ?>
                                            <form method="POST" action="<?= url('proveedores/' . $provId . '/desactivar') ?>">
                                                <?= csrf_field() ?>
                                                <button class="pv-card-btn is-off" type="submit">
                                                    <i class="fas fa-ban"></i>
                                                    Desactivar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= url('proveedores/' . $provId . '/reactivar') ?>">
                                                <?= csrf_field() ?>
                                                <button class="pv-card-btn is-on" type="submit">
                                                    <i class="fas fa-rotate-left"></i>
                                                    Reactivar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(() => {
    const form = document.querySelector('[data-prov-live-search-form]');
    const input = document.querySelector('[data-prov-live-search-input]');
    const searchIcon = document.querySelector('[data-prov-search-icon]');
    if (!form || !input) return;

    let liveSearchTimer = null;
    let isComposing = false;
    let lastQuery = input.value.trim();
    let activeRequest = null;
    const parser = new DOMParser();
    const delay = 280;

    const getResultsRegion = () => document.querySelector('[data-prov-results-region]');

    const setSearching = (isSearching) => {
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
            const normalized = String(value || '').trim();
            if (normalized) {
                url.searchParams.set(key, normalized);
            } else {
                url.searchParams.delete(key);
            }
        }
        return url;
    };

    const updateFromDocument = (doc) => {
        const incoming = doc.querySelector('[data-prov-results-region]');
        const current = getResultsRegion();
        if (incoming && current) {
            current.replaceWith(incoming);
        }
    };

    const fetchResults = async (url, { pushState = true } = {}) => {
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        setSearching(true);
        try {
            const response = await fetch(url.toString(), {
                credentials: 'same-origin',
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error('No se pudo cargar la busqueda');
            const html = await response.text();
            const doc = parser.parseFromString(html, 'text/html');
            updateFromDocument(doc);
            if (pushState) {
                window.history.replaceState({}, '', url.pathname + url.search);
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error en busqueda de proveedores:', error);
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
        const current = input.value.trim();
        if (current === lastQuery) return;
        lastQuery = current;
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

    form.querySelector('.pv-reset')?.addEventListener('click', (event) => {
        event.preventDefault();
        input.value = '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = 'activos';
        lastQuery = '';
        fetchResults(new URL(event.currentTarget.href, window.location.origin));
        input.focus();
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        input.value = params.get('buscar') || '';
        const estado = form.querySelector('[name="estado"]');
        if (estado) estado.value = params.get('estado') || 'activos';
        lastQuery = input.value.trim();
        fetchResults(new URL(window.location.href), { pushState: false });
    });

    input.addEventListener('compositionstart', () => { isComposing = true; });
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
