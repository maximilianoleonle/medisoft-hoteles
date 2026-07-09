<?php
if (!function_exists('cfin_safe')) {
    function cfin_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cfin_num')) {
    function cfin_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('cfin_money')) {
    function cfin_money($value): string
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('cfin_date')) {
    function cfin_date($value): string
    {
        if (empty($value)) {
            return '-';
        }
        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('cfin_query')) {
    function cfin_query(array $filtros, array $overrides = []): string
    {
        $query = array_merge($filtros, $overrides);
        foreach ($query as $key => $value) {
            if ($value === '' || $value === 0 || $value === '0' || $value === null || ($key === 'page' && (int)$value <= 1)) {
                unset($query[$key]);
            }
        }

        return http_build_query($query);
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$cxc = is_array($resumen['cxc'] ?? null) ? $resumen['cxc'] : [];
$cxp = is_array($resumen['cxp'] ?? null) ? $resumen['cxp'] : [];
$caja = is_array($resumen['caja'] ?? null) ? $resumen['caja'] : [];
$auditoria = is_array($resumen['auditoria'] ?? null) ? $resumen['auditoria'] : [];
$alertas = is_array($reporte['alertas_paginadas'] ?? null) ? $reporte['alertas_paginadas'] : [];
$totalesAlertas = is_array($reporte['totales_alertas'] ?? null) ? $reporte['totales_alertas'] : [];
$paginacion = is_array($reporte['paginacion'] ?? null) ? $reporte['paginacion'] : ['page' => 1, 'pages' => 1, 'total' => 0, 'limit' => 25];
$filtros = is_array($reporte['filtros'] ?? null) ? $reporte['filtros'] : [];
$tablas = is_array($reporte['tablas'] ?? null) ? $reporte['tablas'] : [];
$schemaOk = !empty($reporte['schema_ok']);

$tipoLabels = [
    'todos' => 'Todos',
    'cxc' => 'CxC',
    'cxp' => 'CxP',
    'caja' => 'Caja',
    'auditoria' => 'Auditoria',
    'sistema' => 'Sistema',
];
$severidadLabels = [
    'todos' => 'Todas',
    'ok' => 'OK',
    'warning' => 'Warning',
    'error' => 'Error',
];
?>

<style>
.cfin-page{--cfin-brand:var(--brand-primary,#1B2746);--cfin-brand-2:var(--brand-secondary,#0F172A);--cfin-accent:var(--brand-accent,#BD9441);--cfin-text:var(--brand-text,#172033);--cfin-muted:var(--brand-muted,#64748B);--cfin-border:var(--brand-border,#E5E7EB);--cfin-soft:color-mix(in srgb,var(--cfin-brand) 5%,#F8FAFC);--cfin-accent-soft:color-mix(in srgb,var(--cfin-accent) 11%,#FFFFFF);padding:24px;max-width:1380px;margin:0 auto;color:var(--cfin-text)}
.cfin-hero{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px}
.cfin-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:var(--cfin-muted);font-weight:900}
.cfin-title{font-size:30px;line-height:1.08;margin:6px 0 8px;font-weight:900;color:var(--cfin-brand-2)}
.cfin-subtitle{max-width:820px;margin:0;color:var(--cfin-muted)}
.cfin-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}
.cfin-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:10px 14px;border-radius:7px;border:1px solid var(--cfin-border);background:#fff;color:var(--cfin-text);font-weight:850;text-decoration:none}
.cfin-btn.primary{background:var(--cfin-brand);border-color:var(--cfin-brand);color:var(--brand-action-text,#fff)}
.cfin-badge{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:var(--cfin-accent-soft);color:var(--cfin-brand);border:1px solid color-mix(in srgb,var(--cfin-accent) 26%,transparent)}
.cfin-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:16px}
.cfin-card{background:#fff;border:1px solid var(--cfin-border);border-radius:8px;padding:14px;box-shadow:0 8px 22px rgba(15,23,42,.05)}
.cfin-card span{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;color:var(--cfin-muted);font-weight:900}
.cfin-card strong{display:block;margin-top:8px;font-size:24px;line-height:1.1;color:var(--cfin-brand-2)}
.cfin-card small{display:block;margin-top:7px;color:var(--cfin-muted);font-weight:700}
.cfin-band{background:linear-gradient(135deg,var(--cfin-brand),var(--cfin-brand-2));border-radius:8px;color:#fff;padding:16px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:14px}
.cfin-band strong{display:block;font-size:16px}
.cfin-band span{color:rgba(255,255,255,.78);font-size:13px}
.cfin-band-counts{display:flex;gap:8px;flex-wrap:wrap}
.cfin-chip{border-radius:999px;padding:6px 9px;font-size:12px;font-weight:900;border:1px solid rgba(255,255,255,.24);background:rgba(255,255,255,.12)}
.cfin-filter{background:#fff;border:1px solid var(--cfin-border);border-radius:8px;padding:14px;margin-bottom:16px;box-shadow:0 8px 22px rgba(15,23,42,.04)}
.cfin-filter-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;align-items:end}
.cfin-field label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--cfin-muted);font-weight:900;margin-bottom:6px}
.cfin-field input,.cfin-field select{width:100%;height:40px;border:1px solid var(--cfin-border);border-radius:6px;padding:0 10px;color:var(--cfin-text);background:#fff;font-weight:700}
.cfin-field input:focus,.cfin-field select:focus{outline:0;border-color:var(--cfin-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--cfin-brand) 14%,transparent)}
.cfin-panel{background:#fff;border:1px solid var(--cfin-border);border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.cfin-panel-head{padding:16px 18px;border-bottom:1px solid var(--cfin-border);background:var(--cfin-soft);display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.cfin-panel-title{font-size:16px;font-weight:900;margin:0;color:var(--cfin-brand-2)}
.cfin-panel-subtitle{font-size:13px;color:var(--cfin-muted);margin:4px 0 0}
.cfin-table-wrap{overflow-x:auto}
.cfin-table{width:100%;border-collapse:collapse;min-width:980px}
.cfin-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:var(--cfin-muted);background:#fff;padding:12px 14px;border-bottom:1px solid var(--cfin-border)}
.cfin-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.cfin-status{display:inline-flex;align-items:center;gap:7px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900}
.cfin-status.ok{background:#ecfdf5;color:#047857}
.cfin-status.warning{background:#fffbeb;color:#b45309}
.cfin-status.error{background:#fef2f2;color:#b91c1c}
.cfin-type{font-size:12px;font-weight:900;text-transform:uppercase;color:var(--cfin-brand)}
.cfin-code{font-size:12px;color:var(--cfin-muted);font-weight:800;margin-top:3px}
.cfin-link{font-weight:900;color:var(--cfin-brand);text-decoration:none}
.cfin-muted{color:var(--cfin-muted);font-size:13px}
.cfin-empty{padding:34px 20px;text-align:center;color:var(--cfin-muted)}
.cfin-empty strong{display:block;color:var(--cfin-brand-2);font-size:18px;margin-bottom:8px}
.cfin-pages{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 18px;background:#fbfcfe}
.cfin-page-links{display:flex;gap:8px;flex-wrap:wrap}
.cfin-schema{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;padding:14px 18px;border-top:1px solid var(--cfin-border);background:#fbfcfe}
.cfin-schema span{font-size:12px;font-weight:800;color:var(--cfin-muted)}
.cfin-schema .bad{color:#b91c1c}
@media (max-width:1180px){.cfin-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.cfin-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.cfin-schema{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:760px){.cfin-page{padding:16px}.cfin-hero{display:block}.cfin-actions{justify-content:flex-start;margin-top:12px}.cfin-title{font-size:24px}.cfin-grid,.cfin-filter-grid{grid-template-columns:1fr}.cfin-band{display:block}.cfin-band-counts{margin-top:12px}.cfin-pages{display:block}.cfin-page-links{margin-top:10px}.cfin-schema{grid-template-columns:1fr}}
</style>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cfin-page {
    --cfin-brand: var(--brand-primary, #1B2746);
    --cfin-brand-2: var(--brand-secondary, #0F172A);
    --cfin-accent: var(--brand-accent, #BD9441);
    --cfin-accent-soft: color-mix(in srgb, var(--cfin-accent) 15%, #FFFFFF);
    --cfin-accent-line: color-mix(in srgb, var(--cfin-accent) 42%, #E4D4B0);
    --cfin-accent-ink: color-mix(in srgb, var(--cfin-accent) 58%, var(--cfin-brand));
    --cfin-ivory: #F6F2EA;
    --cfin-ivory-2: #FBF8F2;
    --cfin-surface: #FFFFFF;
    --cfin-surface-warm: #FCFAF5;
    --cfin-border: color-mix(in srgb, var(--cfin-brand) 6%, #E9E1D6);
    --cfin-ring: color-mix(in srgb, var(--cfin-accent) 32%, transparent);
    --cfin-text: color-mix(in srgb, var(--cfin-brand) 46%, #707B8C);
    --cfin-muted: #8791A2;
    --cfin-heading: color-mix(in srgb, var(--cfin-brand) 66%, #566172);
    --cfin-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --cfin-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --cfin-success: #1E9E63;
    --cfin-success-bg: #E7F4EC;
    --cfin-warning: #C2841C;
    --cfin-warning-bg: #FAF0DC;
    --cfin-danger: #B4392B;
    --cfin-danger-bg: #F8EAE5;
    max-width: 1380px;
    min-height: 100%;
    margin: 0 auto;
    padding: 18px 16px 42px;
    color: var(--cfin-text);
    font-family: var(--cfin-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cfin-accent) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cfin-ivory-2), var(--cfin-ivory));
}

.cfin-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    margin: 0 0 14px;
    padding: 2px 0 6px;
}

.cfin-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    max-width: min(100%, 840px);
    min-width: 0;
}

.cfin-title-lockup > div:last-child { min-width: 0; }

.cfin-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--cfin-accent), var(--cfin-brand) 54%, color-mix(in srgb, var(--cfin-brand) 68%, var(--cfin-accent)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cfin-brand) 72%, transparent);
}

.cfin-kicker {
    margin: 0 0 2px;
    color: var(--cfin-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.cfin-title {
    margin: 0;
    color: var(--cfin-heading);
    font-family: var(--cfin-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}

.cfin-subtitle {
    max-width: 54rem;
    margin: 9px 0 0;
    color: var(--cfin-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}

.cfin-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
}

.cfin-badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--cfin-accent) 10%, #FFFFFF);
    color: var(--cfin-accent-ink);
    border: 1px solid color-mix(in srgb, var(--cfin-accent) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}

.cfin-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 16px;
    border-radius: 11px;
    border: 1px solid var(--cfin-border);
    background: var(--cfin-surface);
    color: var(--cfin-text);
    font-size: .88rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.cfin-btn:hover {
    transform: translateY(-1px);
    border-color: var(--cfin-accent-line);
    background: var(--cfin-accent-soft);
    color: var(--cfin-accent-ink);
}

.cfin-btn:active { transform: translateY(0) scale(.98); }
.cfin-btn:focus-visible { outline: 3px solid var(--cfin-ring); outline-offset: 2px; }

.cfin-btn.primary {
    border-color: transparent;
    background: linear-gradient(135deg, color-mix(in srgb, var(--cfin-accent) 86%, #fff), color-mix(in srgb, var(--cfin-accent) 72%, var(--cfin-brand)));
    color: #fff;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--cfin-accent) 42%, transparent);
}

.cfin-btn.primary:hover {
    color: #fff;
    background: linear-gradient(135deg, color-mix(in srgb, var(--cfin-accent) 90%, #fff), color-mix(in srgb, var(--cfin-accent) 75%, var(--cfin-brand)));
}

.cfin-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin: 0 0 14px;
}

.cfin-card {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--cfin-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}

.cfin-card span {
    color: var(--cfin-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    line-height: 1.18;
    text-transform: uppercase;
}

.cfin-card strong {
    margin-top: 6px;
    color: var(--cfin-heading);
    font-family: var(--cfin-serif);
    font-size: 1.62rem;
    font-weight: 650;
    line-height: 1.1;
}

.cfin-card small {
    margin-top: 6px;
    color: var(--cfin-muted);
    font-size: .74rem;
    font-weight: 560;
}

.cfin-band {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    margin: 0 0 14px;
    padding: 14px 16px;
    border: 1px solid var(--cfin-accent-line);
    border-radius: 16px;
    background: var(--cfin-accent-soft);
    color: var(--cfin-text);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 28px -25px rgba(27,39,70,.22);
}

.cfin-band strong {
    color: var(--cfin-heading);
    font-size: .92rem;
    font-weight: 650;
}

.cfin-band span {
    color: var(--cfin-muted);
    font-size: .8rem;
    font-weight: 500;
}

.cfin-band-counts {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.cfin-chip {
    border-radius: 999px;
    padding: 5px 9px;
    border: 1px solid color-mix(in srgb, var(--cfin-accent) 24%, #FFFFFF);
    background: rgba(255,255,255,.7);
    color: var(--cfin-accent-ink);
    font-size: .72rem;
    font-weight: 650;
}

.cfin-filter {
    margin: 0 0 14px;
    padding: 14px;
    border: 1px solid var(--cfin-border);
    border-radius: 16px;
    background: rgba(255,255,255,.86);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}

.cfin-filter-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr)) auto auto;
    gap: 10px;
    align-items: end;
}

.cfin-field label {
    display: block;
    margin: 0 0 5px;
    color: var(--cfin-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}

.cfin-field input,
.cfin-field select {
    width: 100%;
    min-height: 44px;
    height: 44px;
    border: 1px solid var(--cfin-border);
    border-radius: 11px;
    background: var(--cfin-surface-warm);
    color: var(--cfin-text);
    padding: 0 12px;
    font-size: .88rem;
    font-weight: 560;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}

.cfin-field input:focus,
.cfin-field select:focus {
    border-color: var(--cfin-accent);
    box-shadow: 0 0 0 3px var(--cfin-ring);
    outline: none;
}

.cfin-panel {
    overflow: hidden;
    border: 1px solid var(--cfin-border);
    border-radius: 16px;
    background: rgba(255,255,255,.86);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}

.cfin-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--cfin-border);
    background: var(--cfin-surface-warm);
}

.cfin-panel-title {
    margin: 0;
    color: var(--cfin-heading);
    font-size: .9rem;
    font-weight: 650;
}

.cfin-panel-subtitle {
    margin: 4px 0 0;
    color: var(--cfin-muted);
    font-size: .76rem;
    font-weight: 500;
}

.cfin-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 16px 16px;
}

.cfin-table {
    width: 100%;
    min-width: 980px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: .85rem;
}

.cfin-table thead {
    background: var(--cfin-surface-warm);
    border-bottom: 1px solid var(--cfin-border);
}

.cfin-table th {
    padding: 12px 16px;
    border-bottom: 1px solid var(--cfin-border);
    background: var(--cfin-surface-warm);
    color: var(--cfin-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.cfin-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--cfin-border);
    color: var(--cfin-text);
    vertical-align: top;
}

.cfin-table tbody tr {
    transition: background .16s ease, box-shadow .16s ease;
}

.cfin-table tbody tr:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}

.cfin-table tbody tr:last-child td { border-bottom: 0; }

.cfin-table td strong {
    color: var(--cfin-heading);
    font-weight: 650;
}

.cfin-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 4px 10px;
    border: 1px solid transparent;
    font-size: .74rem;
    font-weight: 650;
    white-space: nowrap;
}

.cfin-status.ok {
    background: var(--cfin-success-bg);
    border-color: color-mix(in srgb, var(--cfin-success) 24%, #fff);
    color: color-mix(in srgb, var(--cfin-success) 70%, var(--cfin-text));
}

.cfin-status.warning {
    background: var(--cfin-warning-bg);
    border-color: color-mix(in srgb, var(--cfin-warning) 26%, #fff);
    color: color-mix(in srgb, var(--cfin-warning) 72%, var(--cfin-text));
}

.cfin-status.error {
    background: var(--cfin-danger-bg);
    border-color: color-mix(in srgb, var(--cfin-danger) 24%, #fff);
    color: color-mix(in srgb, var(--cfin-danger) 72%, var(--cfin-text));
}

.cfin-type {
    color: var(--cfin-accent-ink);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.cfin-code,
.cfin-muted {
    color: var(--cfin-muted);
    font-size: .76rem;
    font-weight: 500;
    line-height: 1.45;
}

.cfin-link {
    color: var(--cfin-heading);
    font-weight: 650;
    text-decoration: none;
}

.cfin-link:hover {
    color: var(--cfin-accent-ink);
    text-decoration: underline;
    text-decoration-color: var(--cfin-accent-line);
    text-underline-offset: 3px;
}

.cfin-empty {
    margin: 14px;
    padding: 40px 18px;
    border: 1px dashed var(--cfin-border);
    border-radius: 16px;
    background: var(--cfin-ivory-2);
    color: var(--cfin-muted);
    font-size: .9rem;
    line-height: 1.5;
    text-align: center;
}

.cfin-empty strong {
    display: block;
    margin: 0 0 6px;
    color: var(--cfin-heading);
    font-size: 1.06rem;
    font-weight: 650;
}

.cfin-pages {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 16px;
    border-top: 1px solid var(--cfin-border);
    background: var(--cfin-surface-warm);
}

.cfin-page-links {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 8px;
}

.cfin-schema {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    padding: 14px 16px;
    border-top: 1px solid var(--cfin-border);
    background: rgba(255,255,255,.62);
}

.cfin-schema span {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-width: 0;
    padding: 8px 10px;
    border: 1px solid var(--cfin-border);
    border-radius: 11px;
    background: var(--cfin-surface);
    color: var(--cfin-muted);
    font-size: .75rem;
    font-weight: 560;
    overflow-wrap: anywhere;
}

.cfin-schema span i { color: var(--cfin-success); }

.cfin-schema .bad {
    border-color: color-mix(in srgb, var(--cfin-danger) 22%, #FFFFFF);
    background: var(--cfin-danger-bg);
    color: color-mix(in srgb, var(--cfin-danger) 72%, var(--cfin-text));
}

.cfin-schema .bad i { color: var(--cfin-danger); }

@media (min-width: 1024px) {
    .cfin-hero {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px 28px;
        padding-bottom: 10px;
    }

    .cfin-actions {
        justify-content: flex-end;
        justify-self: end;
        min-width: max-content;
    }
}

@media (max-width: 1180px) {
    .cfin-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cfin-filter-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .cfin-schema { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 760px) {
    .cfin-page {
        padding: 16px 12px calc(34px + env(safe-area-inset-bottom, 0px));
    }

    .cfin-hero {
        display: grid;
        margin-bottom: 12px;
    }

    .cfin-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }

    .cfin-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }

    .cfin-title {
        font-size: clamp(1.85rem, 12vw, 2.35rem);
    }

    .cfin-subtitle {
        font-size: .86rem;
        line-height: 1.42;
    }

    .cfin-actions {
        justify-content: flex-start;
        margin-top: 0;
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 2px;
        scrollbar-width: none;
    }

    .cfin-actions::-webkit-scrollbar { display: none; }

    .cfin-badge,
    .cfin-actions .cfin-btn {
        flex: 0 0 auto;
        min-height: 42px;
        max-width: 220px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cfin-grid,
    .cfin-filter-grid,
    .cfin-schema {
        grid-template-columns: 1fr;
    }

    .cfin-card strong {
        font-size: 1.38rem;
    }

    .cfin-band {
        display: block;
    }

    .cfin-band-counts {
        justify-content: flex-start;
        margin-top: 12px;
    }

    .cfin-filter {
        padding: 12px;
    }

    .cfin-field .cfin-btn,
    .cfin-field button.cfin-btn {
        width: 100%;
    }

    .cfin-panel-head {
        flex-direction: column;
        align-items: flex-start;
    }

    .cfin-table {
        min-width: 940px;
    }

    .cfin-pages {
        display: block;
    }

    .cfin-page-links {
        justify-content: flex-start;
        margin-top: 10px;
    }
}

@media (prefers-reduced-motion: reduce) {
    .cfin-page *,
    .cfin-page *::before,
    .cfin-page *::after {
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>

<div class="cfin-page">
    <div class="cfin-hero">
        <div class="cfin-title-lockup">
            <div class="cfin-hero-icon" aria-hidden="true"><i class="fas fa-shield-alt"></i></div>
            <div>
                <div class="cfin-kicker">Operacion / Solo lectura</div>
                <h1 class="cfin-title">Conciliacion financiera</h1>
                <p class="cfin-subtitle">Cruce operativo de CxC, CxP, Caja, cortes y auditoria del hotel actual. Esta pantalla no modifica saldos, no registra pagos, no cobra, no revierte y no corrige datos.</p>
            </div>
        </div>
        <div class="cfin-actions">
            <span class="cfin-badge"><i class="fas fa-lock"></i> Solo lectura</span>
            <?php $back_arrow_href = back_url('operacion/diaria'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="cfin-btn ms-back-legacy" href="<?= back_url('operacion/diaria') ?>"><i class="fas fa-arrow-left"></i> Operacion diaria</a>
        </div>
    </div>

    <div class="cfin-grid" aria-label="Resumen financiero read-only">
        <div class="cfin-card"><span>CxC abiertas</span><strong><?= cfin_num($cxc['cuentas_abiertas'] ?? 0) ?></strong><small><?= cfin_money($cxc['saldo_pendiente'] ?? 0) ?> pendiente</small></div>
        <div class="cfin-card"><span>Cobros CxC</span><strong><?= cfin_num($cxc['cobros'] ?? 0) ?></strong><small><?= cfin_money($cxc['cobros_importe'] ?? 0) ?> registrado</small></div>
        <div class="cfin-card"><span>CxP abiertas</span><strong><?= cfin_num($cxp['cuentas_abiertas'] ?? 0) ?></strong><small><?= cfin_money($cxp['saldo_pendiente'] ?? 0) ?> pendiente</small></div>
        <div class="cfin-card"><span>Pagos proveedor</span><strong><?= cfin_num($cxp['pagos'] ?? 0) ?></strong><small><?= cfin_money($cxp['pagos_importe'] ?? 0) ?> registrado</small></div>
        <div class="cfin-card"><span>Caja CxC</span><strong><?= cfin_num($caja['movimientos_cxc'] ?? 0) ?></strong><small>Neto <?= cfin_money($caja['neto_cxc'] ?? 0) ?></small></div>
        <div class="cfin-card"><span>Caja CxP</span><strong><?= cfin_num($caja['movimientos_cxp'] ?? 0) ?></strong><small>Neto <?= cfin_money($caja['neto_cxp'] ?? 0) ?></small></div>
        <div class="cfin-card"><span>Auditoria CxC</span><strong><?= cfin_num(($auditoria['cobros_cxc'] ?? 0) + ($auditoria['reversiones_cxc'] ?? 0)) ?></strong><small>Cobros y reversiones</small></div>
        <div class="cfin-card"><span>Auditoria CxP</span><strong><?= cfin_num(($auditoria['pagos_cxp'] ?? 0) + ($auditoria['reversiones_cxp'] ?? 0)) ?></strong><small>Pagos y reversiones</small></div>
    </div>

    <div class="cfin-band">
        <div>
            <strong>Estado de conciliacion al <?= cfin_date($reporte['consultado_en'] ?? null) ?></strong>
            <span><?= $schemaOk ? 'Esquema financiero disponible para lectura.' : 'Falta una tabla requerida; revisar esquema antes de interpretar alertas.' ?></span>
        </div>
        <div class="cfin-band-counts">
            <span class="cfin-chip">OK <?= cfin_num($totalesAlertas['ok'] ?? 0) ?></span>
            <span class="cfin-chip">Warnings <?= cfin_num($totalesAlertas['warning'] ?? 0) ?></span>
            <span class="cfin-chip">Errores <?= cfin_num($totalesAlertas['error'] ?? 0) ?></span>
            <span class="cfin-chip">Hallazgos <?= cfin_num($totalesAlertas['hallazgos'] ?? 0) ?></span>
        </div>
    </div>

    <form class="cfin-filter" method="get" action="<?= url('operacion/conciliacion-financiera') ?>">
        <div class="cfin-filter-grid">
            <div class="cfin-field">
                <label for="fecha_desde">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="<?= cfin_safe($filtros['fecha_desde'] ?? '', '') ?>">
            </div>
            <div class="cfin-field">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= cfin_safe($filtros['fecha_hasta'] ?? '', '') ?>">
            </div>
            <div class="cfin-field">
                <label for="tipo">Area</label>
                <select id="tipo" name="tipo">
                    <?php foreach ($tipoLabels as $value => $label): ?>
                        <option value="<?= cfin_safe($value) ?>" <?= (($filtros['tipo'] ?? 'todos') === $value) ? 'selected' : '' ?>><?= cfin_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cfin-field">
                <label for="severidad">Estado</label>
                <select id="severidad" name="severidad">
                    <?php foreach ($severidadLabels as $value => $label): ?>
                        <option value="<?= cfin_safe($value) ?>" <?= (($filtros['severidad'] ?? 'todos') === $value) ? 'selected' : '' ?>><?= cfin_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cfin-field">
                <label for="referencia">Referencia</label>
                <input type="text" id="referencia" name="referencia" value="<?= cfin_safe($filtros['referencia'] ?? '', '') ?>" maxlength="100" placeholder="REV-CXC...">
            </div>
            <div class="cfin-field">
                <label for="corte_id">Corte</label>
                <input type="number" id="corte_id" name="corte_id" min="0" value="<?= (int)($filtros['corte_id'] ?? 0) ?>">
            </div>
            <div class="cfin-field">
                <label for="limit">Filas</label>
                <select id="limit" name="limit">
                    <?php foreach ([10, 25, 50] as $limit): ?>
                        <option value="<?= $limit ?>" <?= ((int)($filtros['limit'] ?? 25) === $limit) ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cfin-field">
                <button class="cfin-btn primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
            <div class="cfin-field">
                <a class="cfin-btn" href="<?= url('operacion/conciliacion-financiera') ?>"><i class="fas fa-rotate-left"></i> Limpiar</a>
            </div>
        </div>
    </form>

    <section class="cfin-panel">
        <div class="cfin-panel-head">
            <div>
                <h2 class="cfin-panel-title">Matriz de alertas</h2>
                <p class="cfin-panel-subtitle">Reglas derivadas del preflight 9A-A, acotadas al hotel actual y sin correcciones automaticas.</p>
            </div>
            <span class="cfin-badge"><i class="fas fa-table"></i> <?= cfin_num($paginacion['total'] ?? 0) ?> reglas</span>
        </div>

        <?php if (empty($alertas)): ?>
            <div class="cfin-empty">
                <strong>Sin reglas para los filtros actuales</strong>
                Ajusta el area o el estado para ampliar la lectura.
            </div>
        <?php else: ?>
            <div class="cfin-table-wrap">
                <table class="cfin-table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Area</th>
                            <th>Validacion</th>
                            <th>Hallazgos</th>
                            <th>Detalle</th>
                            <th>Fuente</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alertas as $alerta): ?>
                            <?php $sev = (string)($alerta['severidad'] ?? 'ok'); ?>
                            <tr>
                                <td><span class="cfin-status <?= cfin_safe($sev) ?>"><i class="fas fa-circle"></i> <?= cfin_safe($alerta['estado'] ?? 'OK') ?></span></td>
                                <td><span class="cfin-type"><?= cfin_safe($tipoLabels[$alerta['tipo'] ?? ''] ?? ($alerta['tipo'] ?? '')) ?></span><div class="cfin-code"><?= cfin_safe($alerta['codigo'] ?? '') ?></div></td>
                                <td><strong><?= cfin_safe($alerta['titulo'] ?? '') ?></strong></td>
                                <td><strong><?= cfin_num($alerta['conteo'] ?? 0) ?></strong></td>
                                <td><span class="cfin-muted"><?= cfin_safe($alerta['detalle'] ?? '') ?></span></td>
                                <td>
                                    <?php if (!empty($alerta['destino'])): ?>
                                        <a class="cfin-link" href="<?= url((string)$alerta['destino']) ?>">Abrir fuente</a>
                                    <?php else: ?>
                                        <span class="cfin-muted">Sin enlace</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="cfin-pages">
            <div class="cfin-muted">Pagina <?= cfin_num($paginacion['page'] ?? 1) ?> de <?= cfin_num($paginacion['pages'] ?? 1) ?></div>
            <div class="cfin-page-links">
                <?php if ((int)($paginacion['page'] ?? 1) > 1): ?>
                    <a class="cfin-btn" href="<?= url('operacion/conciliacion-financiera') . '?' . cfin_query($filtros, ['page' => (int)$paginacion['page'] - 1]) ?>"><i class="fas fa-chevron-left"></i> Anterior</a>
                <?php endif; ?>
                <?php if ((int)($paginacion['page'] ?? 1) < (int)($paginacion['pages'] ?? 1)): ?>
                    <a class="cfin-btn" href="<?= url('operacion/conciliacion-financiera') . '?' . cfin_query($filtros, ['page' => (int)$paginacion['page'] + 1]) ?>">Siguiente <i class="fas fa-chevron-right"></i></a>
                <?php endif; ?>
            </div>
        </div>

        <div class="cfin-schema">
            <?php foreach ($tablas as $tabla => $ok): ?>
                <span class="<?= $ok ? '' : 'bad' ?>"><i class="fas <?= $ok ? 'fa-check-circle' : 'fa-triangle-exclamation' ?>"></i> <?= cfin_safe($tabla) ?></span>
            <?php endforeach; ?>
        </div>
    </section>
</div>
