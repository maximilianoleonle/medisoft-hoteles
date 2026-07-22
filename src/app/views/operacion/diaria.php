<?php
if (!function_exists('op_daily_safe')) {
    function op_daily_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('op_daily_num')) {
    function op_daily_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('op_daily_money')) {
    function op_daily_money($value): string
    {
        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('op_daily_date')) {
    function op_daily_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp) : '-';
    }
}

if (!function_exists('op_daily_initials')) {
    function op_daily_initials($value): string
    {
        $text = trim((string)($value ?? ''));
        if ($text === '') {
            return 'H';
        }

        $parts = preg_split('/\s+/', $text);
        $first = $parts[0] ?? '';
        $second = $parts[1] ?? '';
        $firstInitial = function_exists('mb_substr') ? mb_substr($first, 0, 1, 'UTF-8') : substr($first, 0, 1);
        $secondInitial = function_exists('mb_substr') ? mb_substr($second, 0, 1, 'UTF-8') : substr($second, 0, 1);
        $initials = $firstInitial . ($secondInitial ?: '');

        return htmlspecialchars(function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials), ENT_QUOTES, 'UTF-8');
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
$reservaciones = is_array($reporte['reservaciones'] ?? null) ? $reporte['reservaciones'] : [];
$tareas = is_array($reporte['tareas'] ?? null) ? $reporte['tareas'] : [];
$mantenimiento = is_array($reporte['mantenimiento'] ?? null) ? $reporte['mantenimiento'] : [];
$trabajadores = is_array($reporte['trabajadores'] ?? null) ? $reporte['trabajadores'] : [];
$documentos = is_array($reporte['documentos'] ?? null) ? $reporte['documentos'] : [];
$cuentasPorCobrar = is_array($reporte['cuentas_por_cobrar'] ?? null) ? $reporte['cuentas_por_cobrar'] : [];
$tablasDisponibles = is_array($tablasDisponibles ?? null) ? $tablasDisponibles : [];

$estadoReservacionLabels = [
    'confirmada' => 'Confirmada',
    'checked_in' => 'Check-in',
    'checked_out' => 'Check-out',
    'cancelada' => 'Cancelada',
];
?>

<style>
.op-daily{
    --op-brand:var(--brand-primary,#1B2746);
    --op-brand-2:var(--brand-secondary,#0F172A);
    --op-accent:var(--brand-accent,#BD9441);
    --op-bg:#F5F0E8;
    --op-card:#FFFDF9;
    --op-line:color-mix(in srgb,var(--op-brand) 10%,#E7DDD1);
    --op-muted:#6F7B90;
    --op-ink:#172033;
    --op-good:#169B62;
    --op-warn:#C2841C;
    --op-risk:#D64539;
    padding:24px;
    max-width:1320px;
    margin:0 auto;
    color:var(--op-ink);
}
.op-daily-hero{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;margin-bottom:20px}
.op-daily-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:#64748b;font-weight:900}
.op-daily-title{font-size:30px;line-height:1.1;margin:6px 0 8px;font-weight:900;color:#101828}
.op-daily-subtitle{color:#64748b;max-width:760px;margin:0}
.op-daily-actions{display:flex;gap:10px;flex-wrap:wrap}
.op-daily-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:7px;background:#fff;color:#172033;border:1px solid #d1d5db;font-weight:800;padding:10px 14px;text-decoration:none;min-height:40px}
.op-daily-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.op-daily-metric{
    position:relative;
    overflow:hidden;
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:8px;
    padding:14px;
    box-shadow:0 6px 18px rgba(15,23,42,.05);
}
.op-daily-metric::before{
    content:"";
    position:absolute;
    inset:0 auto auto 0;
    width:4px;
    height:100%;
    background:var(--metric-accent,var(--op-accent));
    opacity:.95;
}
.op-daily-metric:nth-child(1){--metric-accent:var(--op-brand)}
.op-daily-metric:nth-child(2){--metric-accent:var(--op-good)}
.op-daily-metric:nth-child(3){--metric-accent:#C2603C}
.op-daily-metric:nth-child(4){--metric-accent:#5A57D2}
.op-daily-metric:nth-child(5){--metric-accent:#2D7EBB}
.op-daily-metric:nth-child(6){--metric-accent:var(--op-warn)}
.op-daily-metric:nth-child(7){--metric-accent:#14784F}
.op-daily-metric:nth-child(8){--metric-accent:var(--op-risk)}
.op-daily-metric span{display:block;font-size:12px;color:#64748b;font-weight:800;text-transform:uppercase}
.op-daily-metric strong{display:block;font-size:25px;line-height:1.1;margin-top:8px;color:#111827}
.op-daily-panel{background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.06);overflow:hidden}
.op-daily-panel-head{padding:16px 18px;border-bottom:1px solid #e5e7eb;background:#fbfcfe}
.op-daily-panel-title{font-size:16px;font-weight:900;color:#111827;margin:0}
.op-daily-panel-subtitle{font-size:13px;color:#64748b;margin:4px 0 0}
.op-daily-stack{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;margin-bottom:16px}
.op-daily-list{padding:14px 18px}
.op-daily-row{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid #eef2f7;font-size:14px}
.op-daily-row:last-child{border-bottom:0}
.op-daily-link{font-weight:900;color:#102a43;text-decoration:none}
.op-daily-link:hover{text-decoration:underline;text-underline-offset:3px;text-decoration-color:var(--op-accent)}
.op-daily-muted{color:#64748b;font-size:13px;margin-top:4px}
.op-daily-pill{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:800;background:#eef2ff;color:#3730a3}
.op-daily-table-wrap{overflow-x:auto}
.op-daily-table{width:100%;border-collapse:collapse;min-width:820px}
.op-daily-table th{font-size:12px;text-align:left;text-transform:uppercase;letter-spacing:.08em;color:#64748b;background:#fbfcfe;padding:12px 14px;border-bottom:1px solid #e5e7eb}
.op-daily-table td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:top}
.op-daily-agenda-mobile{display:none}
.op-daily-empty{padding:34px 20px;text-align:center;color:#64748b}
.op-daily-empty strong{display:block;color:#172033;font-size:18px;margin-bottom:8px}
.op-daily-alert{padding:14px 18px;background:#f8fafc;color:#475569;border-top:1px solid #e5e7eb;font-size:13px;font-weight:700}
@media (max-width:1100px){.op-daily-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.op-daily-stack{grid-template-columns:1fr}}
@media (max-width:720px){
    .op-daily{
        max-width:none;
        min-height:100dvh;
        padding:0 0 calc(22px + env(safe-area-inset-bottom,0px));
        background:var(--op-bg);
        color:#1A1108;
        overflow-x:hidden;
    }
    .op-daily-hero{
        display:block;
        position:relative;
        overflow:hidden;
        margin:12px 12px 10px;
        padding:16px 16px 14px;
        border-radius:18px;
        background:
            radial-gradient(circle at 86% 12%,color-mix(in srgb,var(--op-accent) 20%,transparent),transparent 34%),
            linear-gradient(125deg,var(--op-brand),color-mix(in srgb,var(--op-brand) 86%,#243357));
        color:#fff;
        box-shadow:0 14px 28px rgba(28,37,62,.18);
    }
    .op-daily-hero::after{
        content:"";
        position:absolute;
        right:-48px;
        top:-44px;
        width:150px;
        height:150px;
        border-radius:50%;
        background:rgba(176,136,63,.14);
        pointer-events:none;
    }
    .op-daily-hero > *{position:relative;z-index:1}
    .op-daily-kicker{
        color:rgba(255,255,255,.64);
        font-size:.64rem;
        letter-spacing:.11em;
        font-weight:800;
    }
    .op-daily-title{
        max-width:260px;
        margin:6px 0 0;
        color:#fff;
        font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size:1.62rem;
        font-weight:700;
        line-height:1;
    }
    .op-daily-subtitle{display:none}
    .op-daily-actions{
        display:flex;
        flex-wrap:nowrap;
        gap:8px;
        margin-top:14px;
        overflow-x:auto;
        padding-bottom:1px;
        scrollbar-width:none;
    }
    .op-daily-actions::-webkit-scrollbar{display:none}
    .op-daily-btn{
        flex:0 0 auto;
        min-height:38px;
        max-width:190px;
        padding:0 12px;
        border-radius:11px;
        border-color:rgba(255,255,255,.18);
        background:rgba(255,255,255,.12);
        color:#fff;
        font-size:.78rem;
        font-weight:800;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        backdrop-filter:blur(8px);
        -webkit-backdrop-filter:blur(8px);
    }
    .op-daily-grid{
        display:flex;
        grid-template-columns:none;
        gap:8px;
        margin:0;
        padding:0 12px 10px;
        overflow-x:auto;
        scrollbar-width:none;
    }
    .op-daily-grid::-webkit-scrollbar{display:none}
    .op-daily-metric{
        flex:0 0 112px;
        min-height:76px;
        padding:11px 11px 10px;
        border-color:#E6DBC8;
        border-radius:14px;
        background:var(--op-card);
        box-shadow:0 1px 2px rgba(39,31,18,.04);
    }
    .op-daily-metric::before{
        width:34px;
        height:3px;
        left:11px;
        top:0;
        border-radius:0 0 99px 99px;
    }
    .op-daily-metric span{
        min-height:24px;
        color:#7C8798;
        font-size:.64rem;
        font-weight:800;
        letter-spacing:.045em;
        line-height:1.12;
    }
    .op-daily-metric strong{
        margin-top:7px;
        color:#1A1108;
        font-size:1.28rem;
        line-height:1;
    }
    .op-daily-panel{
        margin:0 12px 10px!important;
        border-color:#E6DBC8;
        border-radius:16px;
        background:var(--op-card);
        box-shadow:0 8px 20px rgba(39,31,18,.06);
    }
    .op-daily-stack{
        display:grid;
        grid-template-columns:1fr;
        gap:10px;
        margin:0 12px 10px;
    }
    .op-daily-stack .op-daily-panel{margin:0!important}
    .op-daily-panel-head{
        padding:12px 14px 10px;
        border-bottom-color:#EFE3D0;
        background:linear-gradient(180deg,#fff,rgba(248,243,235,.62));
    }
    .op-daily-panel-title{
        color:#1A1108;
        font-size:.98rem;
        line-height:1.15;
    }
    .op-daily-panel-subtitle{display:none}
    .op-daily-list{padding:6px 14px}
    .op-daily-row{
        align-items:flex-start;
        gap:12px;
        padding:9px 0;
        border-bottom-color:#F0E6D8;
        font-size:.84rem;
        line-height:1.25;
    }
    .op-daily-row > span{min-width:0;color:#4B5563}
    .op-daily-row > strong{
        flex:0 0 auto;
        max-width:46%;
        color:#172033;
        text-align:right;
        font-size:.88rem;
        line-height:1.18;
    }
    .op-daily-muted{
        color:#8A95A5;
        font-size:.72rem;
        line-height:1.25;
    }
    .op-daily-link{color:#172033}
    .op-daily-alert{display:none}
    .op-daily-table-wrap{display:none}
    .op-daily-agenda-mobile{
        display:grid;
        gap:8px;
        padding:9px 10px 12px;
    }
    .op-ag-card{
        display:grid;
        grid-template-columns:34px minmax(0,1fr) 14px;
        align-items:start;
        gap:9px;
        min-height:44px;
        padding:10px;
        border:1px solid #EFE3D0;
        border-left:4px solid var(--op-accent);
        border-radius:14px;
        background:#fff;
        color:#172033;
        text-decoration:none;
        box-shadow:0 1px 2px rgba(39,31,18,.04);
    }
    .op-ag-avatar{
        width:34px;
        height:34px;
        display:grid;
        place-items:center;
        border-radius:10px;
        color:#fff;
        background:linear-gradient(145deg,var(--op-brand),color-mix(in srgb,var(--op-brand) 72%,var(--op-accent)));
        font-size:.74rem;
        font-weight:900;
        line-height:1;
    }
    .op-ag-body{min-width:0}
    .op-ag-top{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:8px;
        min-width:0;
    }
    .op-ag-name{
        min-width:0;
        color:#1A1108;
        font-size:.9rem;
        font-weight:900;
        line-height:1.12;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .op-ag-status{
        flex:0 0 auto;
        max-width:96px;
        padding:4px 7px;
        border-radius:999px;
        color:var(--op-brand);
        background:color-mix(in srgb,var(--op-brand) 8%,#fff);
        font-size:.66rem;
        font-weight:900;
        line-height:1;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .op-ag-room{
        display:flex;
        align-items:center;
        gap:6px;
        min-width:0;
        margin-top:4px;
        color:#7C8798;
        font-size:.74rem;
        font-weight:750;
        line-height:1.2;
    }
    .op-ag-room span{
        min-width:0;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .op-ag-times{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
        margin-top:9px;
    }
    .op-ag-time{
        min-width:0;
        padding:7px 8px;
        border:1px solid #F0E6D8;
        border-radius:10px;
        background:#F8F3EB;
    }
    .op-ag-time small{
        display:block;
        color:#8A95A5;
        font-size:.58rem;
        font-weight:900;
        letter-spacing:.045em;
        line-height:1;
        text-transform:uppercase;
    }
    .op-ag-time b{
        display:block;
        margin-top:4px;
        color:#1A1108;
        font-size:.76rem;
        font-weight:900;
        line-height:1.1;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .op-ag-time em{
        display:block;
        margin-top:2px;
        color:#6F7B90;
        font-size:.69rem;
        font-style:normal;
        font-weight:800;
        line-height:1.05;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
    }
    .op-ag-go{
        align-self:center;
        color:#A0A8B6;
        font-size:.72rem;
    }
    .op-daily-pill{
        padding:4px 8px;
        font-size:.7rem;
        background:color-mix(in srgb,var(--op-brand) 8%,#fff);
        color:var(--op-brand);
    }
    .op-daily-empty{
        padding:22px 16px;
        color:#7C8798;
        font-size:.84rem;
    }
    .op-daily-empty strong{
        color:#1A1108;
        font-size:1rem;
        margin-bottom:5px;
    }
    .op-daily-mobile-optional{display:none}
}
</style>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.op-daily {
    --op-brand: var(--brand-primary, #1B2746);
    --op-brand-2: var(--brand-secondary, #0F172A);
    --op-accent: var(--brand-accent, #BD9441);
    --op-accent-soft: color-mix(in srgb, var(--op-accent) 15%, #FFFFFF);
    --op-accent-line: color-mix(in srgb, var(--op-accent) 42%, #E4D4B0);
    --op-accent-ink: color-mix(in srgb, var(--op-accent) 58%, var(--op-brand));
    --op-ivory: #F5F5F7;
    --op-ivory-2: #FAFAFC;
    --op-surface: #FFFFFF;
    --op-surface-warm: #F5F5F7;
    --op-border: color-mix(in srgb, var(--op-brand) 6%, #E9E1D6);
    --op-ring: color-mix(in srgb, var(--op-accent) 32%, transparent);
    --op-text: color-mix(in srgb, var(--op-brand) 46%, #707B8C);
    --op-muted: #8791A2;
    --op-heading: color-mix(in srgb, var(--op-brand) 66%, #566172);
    --op-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --op-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --op-good: #1E9E63;
    --op-good-soft: #E7F4EC;
    --op-warn: #C2841C;
    --op-warn-soft: #FAF0DC;
    --op-risk: #B4392B;
    --op-risk-soft: #F8EAE5;
    max-width: 1320px;
    min-height: 100%;
    margin: 0 auto;
    padding: 18px 16px 42px;
    color: var(--op-text);
    font-family: var(--op-sans);
    
}

.op-daily-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    margin: 0 0 14px;
    padding: 2px 0 6px;
}

.op-daily-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    max-width: min(100%, 790px);
    min-width: 0;
}

.op-daily-title-lockup > div:last-child { min-width: 0; }

.op-daily-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--op-accent), var(--op-brand) 54%, color-mix(in srgb, var(--op-brand) 68%, var(--op-accent)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--op-brand) 72%, transparent);
}

.op-daily-kicker {
    margin: 0 0 2px;
    color: var(--op-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}

.op-daily-title {
    margin: 0;
    color: var(--op-heading);
    font-family: var(--op-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}

.op-daily-subtitle {
    max-width: 50rem;
    margin: 9px 0 0;
    color: var(--op-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}

.op-daily-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-start;
    gap: 8px;
}

.op-daily-readonly {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .36rem .66rem;
    border-radius: 999px;
    background: color-mix(in srgb, var(--op-accent) 10%, #FFFFFF);
    color: var(--op-accent-ink);
    border: 1px solid color-mix(in srgb, var(--op-accent) 28%, #ECE1D1);
    font-size: .72rem;
    font-weight: 650;
    white-space: nowrap;
}

.op-daily-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 16px;
    border-radius: 11px;
    border: 1px solid var(--op-border);
    background: var(--op-surface);
    color: var(--op-text);
    font-size: .88rem;
    font-weight: 650;
    line-height: 1;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}

.op-daily-btn:hover {
    transform: translateY(-1px);
    border-color: var(--op-accent-line);
    background: var(--op-accent-soft);
    color: var(--op-accent-ink);
}

.op-daily-btn:active { transform: translateY(0) scale(.98); }
.op-daily-btn:focus-visible { outline: 3px solid var(--op-ring); outline-offset: 2px; }

.op-daily-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin: 0 0 14px;
}

.op-daily-metric {
    position: relative;
    overflow: hidden;
    background: rgba(255,255,255,.82);
    border: 1px solid var(--op-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}

.op-daily-metric::before {
    width: 34px;
    height: 3px;
    inset: 0 auto auto 14px;
    border-radius: 0 0 999px 999px;
    background: var(--metric-accent, var(--op-accent));
}

.op-daily-metric span {
    color: var(--op-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    line-height: 1.18;
    text-transform: uppercase;
}

.op-daily-metric strong {
    margin-top: 6px;
    color: var(--op-heading);
    font-family: var(--op-serif);
    font-size: 1.62rem;
    font-weight: 650;
    line-height: 1.1;
}

.op-daily-panel {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--op-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
    overflow: hidden;
}

.op-daily-stack {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 14px;
}

.op-daily-panel-head {
    padding: 13px 16px;
    border-bottom: 1px solid var(--op-border);
    background: var(--op-surface-warm);
}

.op-daily-panel-title {
    margin: 0;
    color: var(--op-heading);
    font-size: .9rem;
    font-weight: 650;
}

.op-daily-panel-subtitle {
    margin: 4px 0 0;
    color: var(--op-muted);
    font-size: .76rem;
    font-weight: 500;
}

.op-daily-list {
    padding: 7px 16px;
}

.op-daily-row {
    display: flex;
    justify-content: space-between;
    gap: 14px;
    padding: 10px 0;
    border-bottom: 1px solid var(--op-border);
    color: var(--op-text);
    font-size: .88rem;
    line-height: 1.35;
}

.op-daily-row:last-child { border-bottom: 0; }
.op-daily-row > span { min-width: 0; }

.op-daily-row > strong {
    flex: 0 0 auto;
    color: var(--op-heading);
    font-weight: 650;
    text-align: right;
}

.op-daily-link {
    color: var(--op-heading);
    font-weight: 650;
    text-decoration: none;
}

.op-daily-link:hover {
    color: var(--op-accent-ink);
    text-decoration: underline;
    text-decoration-color: var(--op-accent-line);
    text-underline-offset: 3px;
}

.op-daily-muted {
    margin-top: 4px;
    color: var(--op-muted);
    font-size: .75rem;
    line-height: 1.35;
}

.op-daily-alert {
    padding: 13px 16px;
    border-top: 1px solid var(--op-border);
    background: var(--op-accent-soft);
    color: var(--op-muted);
    font-size: .78rem;
    font-weight: 560;
    line-height: 1.45;
}

.op-daily-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 4px 10px;
    border: 1px solid color-mix(in srgb, var(--op-brand) 12%, #FFFFFF);
    background: color-mix(in srgb, var(--op-brand) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--op-brand) 66%, var(--op-text));
    font-size: .74rem;
    font-weight: 650;
    white-space: nowrap;
}

.op-daily-table-wrap {
    overflow-x: auto;
    border-radius: 0 0 16px 16px;
}

.op-daily-table {
    width: 100%;
    min-width: 860px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: .85rem;
}

.op-daily-table thead {
    background: var(--op-surface-warm);
    border-bottom: 1px solid var(--op-border);
}

.op-daily-table th {
    padding: 12px 16px;
    border-bottom: 1px solid var(--op-border);
    background: var(--op-surface-warm);
    color: var(--op-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .07em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.op-daily-table td {
    padding: 13px 16px;
    border-bottom: 1px solid var(--op-border);
    color: var(--op-text);
    vertical-align: top;
}

.op-daily-table tbody tr {
    transition: background .16s ease, box-shadow .16s ease;
}

.op-daily-table tbody tr:hover {
    background: rgba(251,248,242,.72);
    box-shadow: 0 10px 24px -25px rgba(27,39,70,.32);
}

.op-daily-table tbody tr:last-child td { border-bottom: 0; }

.op-daily-empty {
    margin: 14px;
    padding: 40px 18px;
    border: 1px dashed var(--op-border);
    border-radius: 16px;
    background: var(--op-ivory-2);
    color: var(--op-muted);
    font-size: .9rem;
    line-height: 1.5;
    text-align: center;
}

.op-daily-empty strong {
    display: block;
    margin: 0 0 6px;
    color: var(--op-heading);
    font-size: 1.06rem;
    font-weight: 650;
}

.op-ag-card {
    border-color: var(--op-border);
    border-left-color: var(--op-accent);
    background: var(--op-surface);
    box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25);
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
}

.op-ag-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--op-accent) 38%, var(--op-border));
}

.op-ag-avatar {
    font-weight: 650;
    background: linear-gradient(145deg, var(--op-brand), color-mix(in srgb, var(--op-brand) 72%, var(--op-accent)));
}

.op-ag-name,
.op-ag-time b {
    color: var(--op-heading);
    font-weight: 650;
}

.op-ag-status,
.op-ag-room,
.op-ag-time small,
.op-ag-time em,
.op-ag-go {
    color: var(--op-muted);
    font-weight: 650;
}

.op-ag-time {
    border-color: var(--op-border);
    background: var(--op-surface-warm);
}

@media (min-width: 1024px) {
    .op-daily-hero {
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 16px 28px;
        padding-bottom: 10px;
    }

    .op-daily-actions {
        justify-content: flex-end;
        justify-self: end;
        min-width: max-content;
    }
}

@media (max-width: 1100px) {
    .op-daily-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .op-daily-stack { grid-template-columns: 1fr; }
}

@media (max-width: 720px) {
    .op-daily {
        max-width: none;
        min-height: 100dvh;
        padding: 16px 12px calc(34px + env(safe-area-inset-bottom, 0px));
        color: var(--op-text);
        overflow-x: hidden;
    }

    .op-daily-hero {
        display: grid;
        margin: 0 0 12px;
        padding: 0 0 6px;
        border-radius: 0;
        background: transparent;
        color: var(--op-text);
        box-shadow: none;
    }

    .op-daily-hero::after { display: none; }
    .op-daily-hero > * { position: static; z-index: auto; }

    .op-daily-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }

    .op-daily-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }

    .op-daily-kicker {
        color: var(--op-muted);
        font-size: .68rem;
        font-weight: 650;
    }

    .op-daily-title {
        max-width: none;
        color: var(--op-heading);
        font-size: clamp(1.85rem, 12vw, 2.35rem);
        font-weight: 650;
    }

    .op-daily-subtitle {
        display: block;
        font-size: .86rem;
        line-height: 1.42;
    }

    .op-daily-actions {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        margin-top: 0;
        overflow-x: auto;
        padding-bottom: 2px;
        scrollbar-width: none;
    }

    .op-daily-actions::-webkit-scrollbar { display: none; }

    .op-daily-readonly,
    .op-daily-btn {
        flex: 0 0 auto;
        min-height: 42px;
        max-width: 210px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .op-daily-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin: 0 0 10px;
        padding: 0;
        overflow: visible;
    }

    .op-daily-metric {
        flex: initial;
        min-height: 78px;
        padding: 11px 12px 10px;
        border-radius: 14px;
    }

    .op-daily-metric::before {
        left: 12px;
        top: 0;
        width: 30px;
        height: 3px;
    }

    .op-daily-metric span {
        min-height: 24px;
        font-size: .62rem;
        line-height: 1.14;
    }

    .op-daily-metric strong {
        margin-top: 7px;
        font-size: 1.3rem;
    }

    .op-daily-panel {
        margin: 0 0 10px !important;
        border-radius: 16px;
    }

    .op-daily-stack {
        grid-template-columns: 1fr;
        gap: 10px;
        margin: 0 0 10px;
    }

    .op-daily-stack .op-daily-panel { margin: 0 !important; }

    .op-daily-panel-head {
        padding: 12px 14px 10px;
        background: var(--op-surface-warm);
    }

    .op-daily-panel-title {
        font-size: .96rem;
        line-height: 1.15;
    }

    .op-daily-panel-subtitle {
        display: block;
        font-size: .72rem;
        line-height: 1.35;
    }

    .op-daily-list { padding: 6px 14px; }

    .op-daily-row {
        align-items: flex-start;
        gap: 12px;
        padding: 9px 0;
        font-size: .84rem;
        line-height: 1.28;
    }

    .op-daily-row > span { min-width: 0; }

    .op-daily-row > strong {
        max-width: 46%;
        font-size: .86rem;
        line-height: 1.18;
    }

    .op-daily-alert {
        display: block;
        font-size: .74rem;
    }

    .op-daily-table-wrap { display: none; }

    .op-daily-agenda-mobile {
        display: grid;
        gap: 8px;
        padding: 9px 10px 12px;
    }

    .op-daily-empty {
        margin: 12px;
        padding: 24px 16px;
        font-size: .84rem;
    }

    .op-daily-empty strong {
        font-size: 1rem;
        margin-bottom: 5px;
    }

    .op-daily-mobile-optional { display: none; }
}

@media (max-width: 420px) {
    .op-daily-grid { grid-template-columns: 1fr; }
}

@media (prefers-reduced-motion: reduce) {
    .op-daily *,
    .op-daily *::before,
    .op-daily *::after {
        transition: none !important;
        scroll-behavior: auto !important;
    }
}
</style>

<div class="op-daily">
    <div class="op-daily-hero">
        <div class="op-daily-title-lockup">
            <div class="op-daily-hero-icon" aria-hidden="true"><i class="fas fa-clipboard-check"></i></div>
            <div>
                <div class="op-daily-kicker">Resumen del d&iacute;a</div>
                <h1 class="op-daily-title">El hotel hoy</h1>
                <p class="op-daily-subtitle">Todo lo del d&iacute;a en una sola pantalla: cobros, llegadas, salidas y tareas. Solo lectura: no cambia reservaciones, habitaciones, tareas, documentos, Caja ni n&oacute;mina.</p>
            </div>
        </div>
        <div class="op-daily-actions">
            <span class="op-daily-readonly"><i class="fas fa-lock" aria-hidden="true"></i> Solo lectura</span>
            <?php $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="op-daily-btn ms-back-legacy ms-glass-btn" href="<?= back_url('dashboard') ?>"><i class="fas fa-arrow-left"></i> Inicio</a>
            <a class="op-daily-btn ms-glass-btn" href="<?= url('operacion/conciliacion-financiera') ?>"><i class="fas fa-shield-alt"></i> Conciliacion financiera</a>
            <a class="op-daily-btn ms-glass-btn" href="<?= url('tareas/reporte') ?>"><i class="fas fa-tasks"></i> Reporte TLM</a>
        </div>
    </div>

    <div class="op-daily-grid" aria-label="Resumen operativo diario">
        <div class="op-daily-metric"><span>Habitaciones</span><strong><?= op_daily_num($habitaciones['total'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Disponibles</span><strong><?= op_daily_num($habitaciones['disponible'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Ocupadas</span><strong><?= op_daily_num($habitaciones['ocupada'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Llegadas hoy</span><strong><?= op_daily_num($reservaciones['llegadas_hoy'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Tareas activas</span><strong><?= op_daily_num($tareas['activas'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>Mantenimiento</span><strong><?= op_daily_num(($mantenimiento['en_proceso'] ?? 0) + ($mantenimiento['programado'] ?? 0)) ?></strong></div>
        <div class="op-daily-metric"><span>CxC estimada</span><strong><?= op_daily_money($cuentasPorCobrar['saldo_estimado'] ?? 0) ?></strong></div>
        <div class="op-daily-metric"><span>CxC pendientes</span><strong><?= op_daily_num($cuentasPorCobrar['pendientes'] ?? 0) ?></strong></div>
    </div>

    <section class="op-daily-panel" style="margin-bottom:16px">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">KPIs financieros estimados</h2>
            <p class="op-daily-panel-subtitle">Lectura derivada de reservaciones, pagos y abonos. No crea cobros, no registra pagos y no toca Caja.</p>
        </div>
        <div class="op-daily-list">
            <div class="op-daily-row"><span>CxC estimada pendiente</span><strong><?= op_daily_money($cuentasPorCobrar['saldo_estimado'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones pendientes</span><strong><?= op_daily_num($cuentasPorCobrar['pendientes'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones liquidadas</span><strong><?= op_daily_num($cuentasPorCobrar['liquidadas'] ?? 0) ?></strong></div>
            <div class="op-daily-row"><span>Reservaciones con excedente</span><strong><?= op_daily_num($cuentasPorCobrar['excedentes'] ?? 0) ?></strong></div>
        </div>
        <div class="op-daily-alert">
            CxC es estimada y read-only. Para revisar el detalle usa
            <a class="op-daily-link" href="<?= url('cuentas-por-cobrar') ?>">Cuentas por cobrar</a>.
        </div>
    </section>

    <div class="op-daily-stack">
        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Reservaciones y ocupacion</h2>
                <p class="op-daily-panel-subtitle">Solo lectura de agenda diaria y alertas de check-in/check-out.</p>
            </div>
            <div class="op-daily-list">
                <div class="op-daily-row"><span>Salidas hoy</span><strong><?= op_daily_num($reservaciones['salidas_hoy'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Estancias activas</span><strong><?= op_daily_num($reservaciones['activas'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Check-ins vencidos</span><strong><?= op_daily_num($reservaciones['checkins_vencidos'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Check-outs vencidos</span><strong><?= op_daily_num($reservaciones['checkouts_vencidos'] ?? 0) ?></strong></div>
            </div>
        </section>

        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Riesgo operativo</h2>
                <p class="op-daily-panel-subtitle">Tareas, mantenimiento y personal sin acciones desde esta pantalla.</p>
            </div>
            <div class="op-daily-list">
                <div class="op-daily-row"><span>Tareas urgentes activas</span><strong><?= op_daily_num($tareas['urgentes'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Tareas vencidas</span><strong><?= op_daily_num($tareas['vencidas'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Mantenimientos urgentes</span><strong><?= op_daily_num($mantenimiento['urgente'] ?? 0) ?></strong></div>
                <div class="op-daily-row"><span>Trabajadores activos</span><strong><?= op_daily_num($trabajadores['activos'] ?? 0) ?></strong></div>
            </div>
        </section>
    </div>

    <section class="op-daily-panel" style="margin-bottom:16px">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">Agenda de hoy</h2>
            <p class="op-daily-panel-subtitle">Entradas y salidas del dia para el hotel actual.</p>
        </div>
        <?php if (empty($reservaciones['hoy'])): ?>
            <div class="op-daily-empty"><strong>Sin agenda para hoy</strong>No hay entradas o salidas registradas para la fecha actual.</div>
        <?php else: ?>
            <div class="op-daily-table-wrap">
                <table class="op-daily-table">
                    <thead>
                        <tr>
                            <th>Reservacion</th>
                            <th>Huesped</th>
                            <th>Habitaciones</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservaciones['hoy'] as $reservacion): ?>
                            <?php $estado = (string)($reservacion['estado'] ?? ''); ?>
                            <tr>
                                <td data-label="Reservacion"><a class="op-daily-link" href="<?= url('reservaciones/ver/' . (int)($reservacion['id'] ?? 0)) ?>">#<?= (int)($reservacion['id'] ?? 0) ?></a></td>
                                <td data-label="Huesped"><?= op_daily_safe($reservacion['huesped_nombre'] ?? null) ?></td>
                                <td data-label="Habitaciones"><?= op_daily_safe($reservacion['habitaciones'] ?? null) ?></td>
                                <td data-label="Entrada"><?= op_daily_date($reservacion['fecha_entrada'] ?? null) ?><div class="op-daily-muted"><?= op_daily_safe($reservacion['hora_llegada_estimada'] ?? null, '') ?></div></td>
                                <td data-label="Salida"><?= op_daily_date($reservacion['fecha_salida'] ?? null) ?><div class="op-daily-muted"><?= op_daily_safe($reservacion['hora_salida'] ?? null, '') ?></div></td>
                                <td data-label="Estado"><span class="op-daily-pill"><?= op_daily_safe($estadoReservacionLabels[$estado] ?? $estado) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="op-daily-agenda-mobile" aria-label="Agenda movil de hoy">
                <?php foreach ($reservaciones['hoy'] as $reservacion): ?>
                    <?php
                    $resId = (int)($reservacion['id'] ?? 0);
                    $estado = (string)($reservacion['estado'] ?? '');
                    $estadoLabel = $estadoReservacionLabels[$estado] ?? $estado;
                    $huespedNombre = (string)($reservacion['huesped_nombre'] ?? 'Huesped');
                    $habitacionesTexto = (string)($reservacion['habitaciones'] ?? '');
                    $entradaHora = trim((string)($reservacion['hora_llegada_estimada'] ?? ''));
                    $salidaHora = trim((string)($reservacion['hora_salida'] ?? ''));
                    ?>
                    <a class="op-ag-card" href="<?= url('reservaciones/ver/' . $resId) ?>" title="Ver reservacion #<?= $resId ?>">
                        <span class="op-ag-avatar" aria-hidden="true"><?= op_daily_initials($huespedNombre) ?></span>
                        <span class="op-ag-body">
                            <span class="op-ag-top">
                                <span class="op-ag-name"><?= op_daily_safe($huespedNombre, 'Huesped') ?></span>
                                <span class="op-ag-status"><?= op_daily_safe($estadoLabel, 'Estado') ?></span>
                            </span>
                            <span class="op-ag-room">
                                <i class="fas fa-bed" aria-hidden="true"></i>
                                <span><?= op_daily_safe($habitacionesTexto, 'Sin habitacion') ?></span>
                            </span>
                            <span class="op-ag-times">
                                <span class="op-ag-time">
                                    <small>Entrada</small>
                                    <b><?= op_daily_date($reservacion['fecha_entrada'] ?? null) ?></b>
                                    <em><?= op_daily_safe($entradaHora, 'Sin hora') ?></em>
                                </span>
                                <span class="op-ag-time">
                                    <small>Salida</small>
                                    <b><?= op_daily_date($reservacion['fecha_salida'] ?? null) ?></b>
                                    <em><?= op_daily_safe($salidaHora, 'Sin hora') ?></em>
                                </span>
                            </span>
                        </span>
                        <span class="op-ag-go" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <div class="op-daily-stack">
        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Tareas prioritarias</h2>
                <p class="op-daily-panel-subtitle">Enlaces al detalle de tarea; sin botones de estado.</p>
            </div>
            <?php if (empty($tareas['recientes'])): ?>
                <div class="op-daily-empty"><strong>Sin tareas operativas</strong>No hay tareas para mostrar.</div>
            <?php else: ?>
                <div class="op-daily-list">
                    <?php foreach ($tareas['recientes'] as $tarea): ?>
                        <div class="op-daily-row">
                            <span>
                                <a class="op-daily-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>"><?= op_daily_safe($tarea['titulo'] ?? null) ?></a>
                                <div class="op-daily-muted">
                                    <?= op_daily_safe($tarea['categoria'] ?? null) ?> - <?= op_daily_safe($tarea['prioridad'] ?? null) ?>
                                    <?php if (!empty($tarea['habitacion_numero'])): ?> - Hab. <?= op_daily_safe($tarea['habitacion_numero']) ?><?php endif; ?>
                                </div>
                            </span>
                            <strong><?= op_daily_safe($tarea['estado'] ?? null) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="op-daily-panel">
            <div class="op-daily-panel-head">
                <h2 class="op-daily-panel-title">Mantenimiento reciente</h2>
                <p class="op-daily-panel-subtitle">Lectura historica; no cambia estado de habitacion.</p>
            </div>
            <?php if (empty($mantenimiento['recientes'])): ?>
                <div class="op-daily-empty"><strong>Sin mantenimiento</strong>No hay registros recientes para mostrar.</div>
            <?php else: ?>
                <div class="op-daily-list">
                    <?php foreach ($mantenimiento['recientes'] as $item): ?>
                        <div class="op-daily-row">
                            <span>
                                Hab. <?= op_daily_safe($item['habitacion_numero'] ?? null) ?> - <?= op_daily_safe($item['motivo'] ?? null) ?>
                                <div class="op-daily-muted"><?= op_daily_safe($item['tipo_mantenimiento'] ?? null) ?> - <?= op_daily_safe($item['prioridad'] ?? null) ?></div>
                            </span>
                            <strong><?= op_daily_safe($item['estado'] ?? null) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <section class="op-daily-panel op-daily-mobile-optional">
        <div class="op-daily-panel-head">
            <h2 class="op-daily-panel-title">Documentos recientes</h2>
            <p class="op-daily-panel-subtitle">Metadatos seguros del Centro Documental; no muestra rutas internas.</p>
        </div>
        <?php if (empty($documentos)): ?>
            <div class="op-daily-empty"><strong>Sin documentos recientes</strong>Cuando existan documentos activos, apareceran aqui.</div>
        <?php else: ?>
            <div class="op-daily-list">
                <?php foreach ($documentos as $documento): ?>
                    <div class="op-daily-row">
                        <span>
                            <a class="op-daily-link" href="<?= url('documentos/' . (int)($documento['id'] ?? 0)) ?>"><?= op_daily_safe($documento['titulo'] ?? $documento['nombre_original'] ?? null) ?></a>
                            <div class="op-daily-muted"><?= op_daily_safe($documento['entidad_tipo'] ?? 'sin entidad') ?> #<?= op_daily_safe($documento['entidad_id'] ?? '') ?> - <?= op_daily_safe($documento['mime_type'] ?? null) ?></div>
                        </span>
                        <strong><?= op_daily_date($documento['created_at'] ?? null, true) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="op-daily-alert">Este tablero es solo lectura: no crea tareas, no cambia habitaciones, no toca Caja y no genera pagos.</div>
    </section>
</div>
