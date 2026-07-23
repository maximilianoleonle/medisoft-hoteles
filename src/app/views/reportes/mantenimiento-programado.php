<?php
if (!function_exists('mant_prog_safe')) {
    function mant_prog_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('mant_prog_num')) {
    function mant_prog_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('mant_prog_date')) {
    function mant_prog_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y', $timestamp) : '-';
    }
}

$preview = is_array($preview ?? null) ? $preview : [];
$resumen = is_array($preview['resumen'] ?? null) ? $preview['resumen'] : [];
$registros = is_array($preview['registros'] ?? null) ? $preview['registros'] : [];
$dias = (int)($dias ?? ($preview['dias'] ?? 30));
$dias = max(0, min(90, $dias));

$categoriaLabels = [
    'vencido' => 'Vencido',
    'hoy' => 'Hoy',
    'proximo' => 'Proximo',
];

$categoriaClass = [
    'vencido' => 'is-danger',
    'hoy' => 'is-warning',
    'proximo' => 'is-neutral',
];

$puedeActivarMantenimiento = function_exists('can') ? can('habitaciones.mantenimiento') : false;
?>

<style>
.mant-prog{
    --mp-ink:#172033;
    --mp-muted:#64748b;
    --mp-line:#DDE3EA;
    --mp-soft:#F4F6F9;
    --mp-panel:#FFFFFF;
    --mp-machine:#3e4a43;
    --mp-accent:#b58a38;
    --mp-teal:#0f766e;
    width:100%;
    max-width:none;
    margin:0;
    padding:24px 28px 36px;
    color:var(--mp-ink);
    background:
        repeating-linear-gradient(135deg, rgba(181,138,56,.055) 0 1px, transparent 1px 22px),
        linear-gradient(180deg,#FFFFFF,var(--mp-soft));
}
.mant-prog-hero{
    display:grid;
    grid-template-columns:minmax(0,1fr) auto;
    gap:22px;
    align-items:start;
    margin-bottom:20px;
}
.mant-prog-kicker{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.1em;
    color:var(--mp-muted);
    font-weight:900;
}
.mant-prog-kicker::before{
    content:"";
    width:24px;
    height:1px;
    background:var(--mp-machine);
    opacity:.55;
}
.mant-prog-title{
    margin:4px 0 4px;
    color:#111827;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size:clamp(2.2rem,3.2vw,3.15rem);
    line-height:.98;
    font-weight:700;
    letter-spacing:0;
}
.mant-prog-subtitle{
    max-width:760px;
    margin:0;
    color:var(--mp-muted);
    font-size:.95rem;
    font-weight:650;
    line-height:1.42;
}
.mant-prog-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.mant-prog-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    min-height:42px;
    padding:0 15px;
    border:1px solid var(--mp-line);
    border-radius:8px;
    background:#fff;
    color:var(--mp-ink);
    font-size:.84rem;
    font-weight:900;
    text-decoration:none;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
}
.mant-prog-btn:hover{border-color:color-mix(in srgb,var(--mp-accent) 48%,var(--mp-line));background:#fffaf0}
.mant-prog-grid{
    display:grid;
    grid-template-columns:repeat(6,minmax(0,1fr));
    gap:14px;
    margin-bottom:18px;
}
.mant-prog-metric{
    position:relative;
    min-height:104px;
    padding:16px 18px;
    border:1px solid var(--mp-line);
    border-radius:8px;
    background:rgba(255,255,255,.86);
    box-shadow:0 8px 22px -18px rgba(15,23,42,.35);
    overflow:hidden;
}
.mant-prog-metric::before{
    content:"";
    position:absolute;
    inset:0 auto 0 0;
    width:4px;
    background:var(--mp-machine);
}
.mant-prog-metric:nth-child(2)::before{background:#a33b32}
.mant-prog-metric:nth-child(3)::before{background:var(--mp-accent)}
.mant-prog-metric:nth-child(4)::before{background:#2e6f93}
.mant-prog-metric:nth-child(5)::before{background:#7b4a9e}
.mant-prog-metric:nth-child(6)::before{background:var(--mp-teal)}
.mant-prog-metric span{
    display:block;
    color:var(--mp-muted);
    font-size:.74rem;
    font-weight:900;
    letter-spacing:.055em;
    line-height:1.1;
    text-transform:uppercase;
}
.mant-prog-metric strong{
    display:block;
    margin-top:14px;
    color:#111827;
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size:2.65rem;
    font-weight:750;
    line-height:.82;
}
.mant-prog-panel{
    border:1px solid var(--mp-line);
    border-radius:10px;
    background:var(--mp-panel);
    box-shadow:0 14px 34px -28px rgba(15,23,42,.42);
    overflow:hidden;
}
.mant-prog-panel-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:center;
    padding:18px 20px;
    border-bottom:1px solid var(--mp-line);
    background:linear-gradient(90deg,#FFFFFF,#F5F5F7);
}
.mant-prog-panel-title{margin:0;color:#111827;font-size:1.12rem;font-weight:950;line-height:1.15}
.mant-prog-panel-subtitle{margin:5px 0 0;color:var(--mp-muted);font-size:.86rem;font-weight:750;line-height:1.35}
.mant-prog-tabs{
    display:inline-grid;
    grid-template-columns:repeat(3,minmax(48px,1fr));
    gap:4px;
    padding:4px;
    border:1px solid var(--mp-line);
    border-radius:8px;
    background:#fff;
}
.mant-prog-tab{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-height:38px;
    padding:0 13px;
    border:0;
    border-radius:6px;
    background:transparent;
    color:#475569;
    font-size:.78rem;
    font-weight:950;
    text-decoration:none;
    white-space:nowrap;
}
.mant-prog-tab.is-active{background:var(--mp-machine);color:#fff}
.mant-prog-table-wrap{overflow-x:auto}
.mant-prog-table{width:100%;border-collapse:collapse;min-width:1120px}
.mant-prog-table th{
    padding:13px 16px;
    border-bottom:1px solid var(--mp-line);
    background:#F8FAFC;
    color:var(--mp-muted);
    font-size:.72rem;
    font-weight:950;
    letter-spacing:.075em;
    text-align:left;
    text-transform:uppercase;
}
.mant-prog-table td{
    padding:16px;
    border-bottom:1px solid #ece8dc;
    vertical-align:top;
    color:var(--mp-ink);
    font-size:.92rem;
}
.mant-prog-table tbody tr:hover{background:#fffaf0}
.mant-prog-link{color:#102a43;font-size:1rem;font-weight:950;text-decoration:none}
.mant-prog-link:hover{color:var(--mp-accent)}
.mant-prog-muted{margin-top:5px;color:var(--mp-muted);font-size:.82rem;font-weight:700;line-height:1.38}
.mant-prog-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    min-height:30px;
    padding:5px 10px;
    border:1px solid #dbe3ee;
    border-radius:999px;
    background:#f4f7fb;
    color:#334155;
    font-size:.76rem;
    font-weight:950;
    line-height:1;
}
.mant-prog-pill::before{
    content:"";
    width:6px;
    height:6px;
    border-radius:999px;
    background:currentColor;
    opacity:.82;
}
.mant-prog-pill.is-danger{border-color:#f2b8b5;background:#fff0ef;color:#9f2f28}
.mant-prog-pill.is-warning{border-color:#ecd08d;background:#fff8e4;color:#8a5b12}
.mant-prog-pill.is-neutral{border-color:#b8dbe8;background:#eefaff;color:#075985}
.mant-prog-warnings{display:flex;flex-direction:column;gap:6px}
.mant-prog-warning{
    display:block;
    padding:7px 9px;
    border:1px solid #e4e0d6;
    border-radius:7px;
    background:#F8FAFC;
    color:#475569;
    font-size:.78rem;
    font-weight:800;
    line-height:1.28;
}
.mant-prog-linked-tasks{display:flex;flex-direction:column;gap:6px;margin-top:9px}
.mant-prog-linked-task{
    display:block;
    padding:8px 9px;
    border:1px solid #e4e0d6;
    border-radius:7px;
    background:#fff;
    color:var(--mp-ink);
    font-size:.8rem;
    font-weight:900;
    line-height:1.25;
    text-decoration:none;
}
.mant-prog-linked-task span{display:block;margin-top:3px;color:var(--mp-muted);font-size:.72rem;font-weight:750}
.mant-prog-linked-empty{margin-top:9px;color:#94a3b8;font-size:.76rem;font-weight:900}
.mant-prog-inline-form{margin-top:9px}
.mant-prog-mobile-list{display:none}
.mant-prog-activate,
.mant-prog-task-create{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    min-height:38px;
    padding:0 13px;
    border:0;
    border-radius:7px;
    color:#fff;
    font-size:.78rem;
    font-weight:950;
    cursor:pointer;
}
.mant-prog-activate{background:var(--mp-teal)}
.mant-prog-activate:hover{background:#115e59}
.mant-prog-task-create{background:var(--mp-machine)}
.mant-prog-task-create:hover{background:#26332c}
.mant-prog-activate.is-confirming,
.mant-prog-task-create.is-confirming{background:#8a5b12}
.mant-prog-toast{
    position:fixed;
    right:18px;
    bottom:calc(18px + env(safe-area-inset-bottom));
    z-index:15000;
    max-width:min(390px,calc(100vw - 32px));
    padding:11px 13px;
    border:1px solid #e7c470;
    border-radius:8px;
    background:#fff8e8;
    color:#7c4d08;
    box-shadow:0 18px 42px rgba(24,32,48,.18);
    font-size:.78rem;
    font-weight:850;
    line-height:1.42;
    opacity:0;
    transform:translateY(10px);
    pointer-events:none;
    transition:opacity .18s ease,transform .18s ease;
}
.mant-prog-toast.is-visible{opacity:1;transform:translateY(0)}
.mant-prog-empty{padding:28px 18px;text-align:center;color:var(--mp-muted)}
.mant-prog-empty strong{display:block;margin-bottom:6px;color:var(--mp-ink);font-size:1rem}
.mant-prog-empty p{margin:0;font-size:.82rem}
.mant-prog-note{
    padding:14px 18px;
    border-top:1px solid var(--mp-line);
    background:#F8FAFC;
    color:#5f6b7a;
    font-size:.8rem;
    font-weight:750;
    line-height:1.38;
}
@media (max-width:1100px){
    .mant-prog-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    .mant-prog-table{min-width:880px}
}
@media (max-width:720px){
    .mant-prog{padding:12px 10px 18px}
    .mant-prog-hero{grid-template-columns:1fr;gap:9px;margin-bottom:9px}
    .mant-prog-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));justify-content:stretch}
    .mant-prog-btn{width:100%;min-height:40px;padding:0 8px;font-size:.68rem}
    .mant-prog-title{font-size:1.45rem}
    .mant-prog-subtitle{display:-webkit-box;font-size:.72rem;line-height:1.32;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .mant-prog-grid{grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;margin-bottom:8px}
    .mant-prog-metric{min-height:62px;padding:8px 8px 7px}
    .mant-prog-metric span{font-size:.52rem;letter-spacing:.035em}
    .mant-prog-metric strong{margin-top:6px;font-size:1.45rem}
    .mant-prog-panel{
        border-radius:16px;
        overflow:hidden;
    }
    .mant-prog-panel-head{
        display:grid;
        grid-template-columns:1fr;
        gap:8px;
        padding:12px;
        border-bottom:1px solid var(--mp-line);
    }
    .mant-prog-panel-title{font-size:.84rem}
    .mant-prog-panel-subtitle{font-size:.68rem}
    .mant-prog-tabs{width:100%;grid-template-columns:repeat(3,minmax(0,1fr))}
    .mant-prog-tab{min-height:34px;font-size:.64rem}
    .mant-prog-table-wrap{
        overflow:visible;
        padding:8px;
        background:#f7f3ea;
    }
    .mant-prog-table,
    .mant-prog-table thead,
    .mant-prog-table tbody,
    .mant-prog-table tr,
    .mant-prog-table td{display:block;width:100%;min-width:0}
    .mant-prog-table thead{position:absolute;width:1px;height:1px;margin:-1px;overflow:hidden;clip:rect(0 0 0 0)}
    .mant-prog-table tr{
        position:relative;
        display:grid;
        grid-template-columns:1fr;
        gap:8px;
        margin:0 0 10px;
        width:100%;
        max-width:100%;
        min-width:0;
        padding:10px;
        border:1px solid var(--mp-line);
        border-radius:14px;
        background:#fffdf9;
        box-shadow:0 12px 28px -24px rgba(15,23,42,.48);
        box-sizing:border-box;
        overflow:hidden;
    }
    .mant-prog-table tr::before{
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:4px;
        border-radius:14px 0 0 14px;
        background:var(--mp-machine);
    }
    .mant-prog-table td{
        display:block;
        width:100%;
        max-width:100%;
        min-width:0;
        padding:0;
        border-bottom:0;
        font-size:.75rem;
        box-sizing:border-box;
        overflow-wrap:anywhere;
    }
    .mant-prog-table td::before{
        content:none!important;
        display:none!important;
    }
    .mant-prog-table td:nth-child(1){
        padding-left:3px;
    }
    .mant-prog-table td:nth-child(1) .mant-prog-link{
        font-size:1rem;
    }
    .mant-prog-table td:nth-child(2){
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:8px;
        padding:8px 9px;
        border:1px solid #eee1c7;
        border-radius:11px;
        background:#fff8e8;
    }
    .mant-prog-table td:nth-child(2) .mant-prog-muted{
        margin-top:0;
        text-align:right;
    }
    .mant-prog-table td:nth-child(3),
    .mant-prog-table td:nth-child(4){
        display:grid;
        grid-template-columns:1fr;
        gap:3px;
        padding:8px 9px;
        border:1px solid #ebe6dc;
        border-radius:11px;
        background:#F8FAFC;
    }
    .mant-prog-table td:nth-child(5){
        padding:2px 0;
    }
    .mant-prog-table td:nth-child(6){
        display:flex;
        align-items:flex-start;
        flex-wrap:wrap;
        gap:6px;
        width:100%;
        min-width:0;
        max-width:100%;
        padding-top:2px;
        overflow:hidden;
    }
    .mant-prog-table td:nth-child(6) > *{
        max-width:100%;
        min-width:0;
    }
    .mant-prog-table{
        display:none!important;
    }
    .mant-prog-mobile-list{
        display:grid;
        gap:10px;
        padding:10px;
        background:#f7f3ea;
    }
    .mant-prog-card{
        position:relative;
        display:grid;
        gap:9px;
        min-width:0;
        overflow:hidden;
        border:1px solid var(--mp-line);
        border-radius:16px;
        background:#fffdf9;
        box-shadow:0 12px 26px -22px rgba(15,23,42,.42);
    }
    .mant-prog-card::before{
        content:"";
        position:absolute;
        inset:0 auto 0 0;
        width:4px;
        background:var(--mp-machine);
    }
    .mant-prog-card-main{
        display:grid;
        gap:9px;
        min-width:0;
        padding:12px 12px 4px 15px;
    }
    .mant-prog-card-head{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:10px;
        min-width:0;
    }
    .mant-prog-card-room{
        display:block;
        color:#102a43;
        font-size:1rem;
        font-weight:950;
        line-height:1.1;
        text-decoration:none;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-meta{
        display:block;
        margin-top:3px;
        color:#516070;
        font-size:.72rem;
        font-weight:800;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-head .mant-prog-pill{
        flex:0 0 auto;
    }
    .mant-prog-card-schedule{
        display:flex;
        align-items:center;
        gap:9px;
        min-width:0;
        padding:9px 10px;
        border:1px solid #eee1c7;
        border-radius:12px;
        background:#fff8e8;
    }
    .mant-prog-card-schedule i{
        display:grid;
        place-items:center;
        width:28px;
        height:28px;
        flex:0 0 28px;
        border-radius:9px;
        background:#fff;
        color:#8a5b12;
        font-size:.76rem;
    }
    .mant-prog-card-schedule span,
    .mant-prog-card-cell span,
    .mant-prog-card-review-label{
        display:block;
        color:#64748b;
        font-size:.58rem;
        font-weight:950;
        letter-spacing:.06em;
        line-height:1.1;
        text-transform:uppercase;
    }
    .mant-prog-card-schedule strong{
        display:block;
        margin-top:2px;
        color:#172033;
        font-size:.78rem;
        font-weight:950;
        line-height:1.25;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-grid{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
    }
    .mant-prog-card-cell{
        min-width:0;
        padding:8px 9px;
        border:1px solid #ebe6dc;
        border-radius:12px;
        background:#F8FAFC;
    }
    .mant-prog-card-cell strong{
        display:block;
        margin-top:4px;
        color:#172033;
        font-size:.8rem;
        font-weight:950;
        line-height:1.2;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-cell small{
        display:block;
        margin-top:3px;
        color:#64748b;
        font-size:.68rem;
        font-weight:750;
        line-height:1.25;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-alerts{
        display:grid;
        gap:5px;
        min-width:0;
    }
    .mant-prog-card-review{
        display:grid;
        gap:8px;
        min-width:0;
        padding:10px 12px 12px 15px;
        border-top:1px solid #eee9de;
        background:#fffaf0;
    }
    .mant-prog-card-review-top{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:8px;
        min-width:0;
    }
    .mant-prog-card-motive{
        color:#64748b;
        font-size:.68rem;
        font-weight:800;
        line-height:1.25;
        overflow-wrap:anywhere;
    }
    .mant-prog-card-actions{
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:7px;
        min-width:0;
    }
    .mant-prog-card-actions:empty{
        display:none;
    }
    .mant-prog-card-actions .mant-prog-inline-form{
        display:block;
        margin:0;
        min-width:0;
    }
    .mant-prog-card-actions .mant-prog-inline-form:only-child{
        grid-column:1 / -1;
    }
    .mant-prog-card-actions .mant-prog-activate,
    .mant-prog-card-actions .mant-prog-task-create{
        width:100%;
        min-height:40px;
        border-radius:11px;
        font-size:.68rem;
    }
    .mant-prog-card-tasks{
        display:grid;
        gap:6px;
        min-width:0;
    }
    .mant-prog-card-tasks .mant-prog-linked-task,
    .mant-prog-card-tasks .mant-prog-linked-empty{
        width:100%;
        margin:0;
        min-width:0;
        max-width:100%;
        overflow-wrap:anywhere;
    }
    .mant-prog-link{font-size:.94rem}
    .mant-prog-muted{font-size:.7rem}
    .mant-prog-pill{min-height:24px;padding:4px 7px;font-size:.62rem}
    .mant-prog-warning{
        width:100%;
        border-radius:10px;
        font-size:.68rem;
        white-space:normal;
        overflow-wrap:anywhere;
    }
    .mant-prog-warnings{
        gap:5px;
    }
    .mant-prog-linked-tasks{
        flex:0 0 100%;
        width:100%;
        min-width:0;
        max-width:100%;
        margin-top:0;
        order:4;
    }
    .mant-prog-linked-task{
        width:100%;
        max-width:100%;
        min-width:0;
        display:block;
        border-radius:10px;
        font-size:.7rem;
        white-space:normal;
        overflow-wrap:anywhere;
        word-break:break-word;
        box-sizing:border-box;
    }
    .mant-prog-linked-task span{
        overflow-wrap:anywhere;
        word-break:break-word;
    }
    .mant-prog-linked-empty{
        flex:0 0 100%;
        width:100%;
        min-width:0;
        max-width:100%;
        margin-top:0;
        font-size:.66rem;
        overflow-wrap:anywhere;
    }
    .mant-prog-inline-form{display:inline-flex;flex:0 0 auto;margin:0}
    .mant-prog-activate,
    .mant-prog-task-create{min-height:36px;font-size:.66rem}
    .mant-prog-note{padding:9px;font-size:.66rem}
}
@media (max-width:380px){
    .mant-prog-actions,
    .mant-prog-grid{grid-template-columns:1fr 1fr}
    .mant-prog-table td{grid-template-columns:82px minmax(0,1fr)}
}
@media (prefers-reduced-motion:reduce){
    .mant-prog *{transition:none!important}
}
</style>

<div class="mant-prog">
    <div class="mant-prog-hero">
        <div>
            <div class="mant-prog-kicker">Reportes / Mantenimiento</div>
            <h1 class="mant-prog-title">Preview de mantenimiento programado</h1>
            <p class="mant-prog-subtitle">Lectura de mantenimientos vencidos o proximos para el hotel actual. Esta pantalla no activa mantenimientos, no cambia habitaciones y no ejecuta automatizaciones.</p>
        </div>
        <div class="mant-prog-actions">
            <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="mant-prog-btn ms-back-legacy" href="<?= back_url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="mant-prog-btn" href="<?= url('reportes/mantenimiento') ?>"><i class="fas fa-chart-line"></i> Reporte general</a>
        </div>
    </div>

    <div class="mant-prog-grid" aria-label="Resumen de mantenimiento programado">
        <div class="mant-prog-metric"><span>Total</span><strong><?= mant_prog_num($resumen['total'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Vencidos</span><strong><?= mant_prog_num($resumen['vencidos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Hoy</span><strong><?= mant_prog_num($resumen['hoy'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Proximos</span><strong><?= mant_prog_num($resumen['proximos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Conflictos</span><strong><?= mant_prog_num($resumen['con_conflictos'] ?? 0) ?></strong></div>
        <div class="mant-prog-metric"><span>Candidatos</span><strong><?= mant_prog_num($resumen['candidatos'] ?? 0) ?></strong></div>
    </div>

    <section class="mant-prog-panel">
        <div class="mant-prog-panel-head">
            <div>
                <h2 class="mant-prog-panel-title">Candidatos y advertencias</h2>
                <p class="mant-prog-panel-subtitle">Ventana: <?= mant_prog_num($dias) ?> dias, hasta <?= mant_prog_safe(mant_prog_date($preview['hasta'] ?? null)) ?>.</p>
            </div>
            <div class="mant-prog-tabs" aria-label="Ventana de revision">
                <?php foreach ([7, 30, 90] as $opcionDias): ?>
                    <a class="mant-prog-tab <?= $dias === $opcionDias ? 'is-active' : '' ?>" href="<?= url('reportes/mantenimiento-programado?dias=' . $opcionDias) ?>"><?= $opcionDias ?> dias</a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($registros)): ?>
            <div class="mant-prog-empty">
                <strong>Sin mantenimientos programados en la ventana</strong>
                <p>No hay candidatos vencidos o proximos para revisar en este momento.</p>
            </div>
        <?php else: ?>
            <div class="mant-prog-table-wrap">
                <table class="mant-prog-table">
                    <thead>
                        <tr>
                            <th>Habitacion</th>
                            <th>Programacion</th>
                            <th>Tipo / prioridad</th>
                            <th>Estado habitacion</th>
                            <th>Advertencias</th>
                            <th>Revision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registros as $item): ?>
                            <?php
                            $categoria = (string)($item['categoria_preview'] ?? 'proximo');
                            $pillClass = $categoriaClass[$categoria] ?? 'is-neutral';
                            $advertencias = is_array($item['preview_advertencias'] ?? null) ? $item['preview_advertencias'] : [];
                            $tareasVinculadas = is_array($item['tareas_vinculadas'] ?? null) ? $item['tareas_vinculadas'] : [];
                            $tareaActivaVinculada = is_array($item['tarea_activa_vinculada'] ?? null) ? $item['tarea_activa_vinculada'] : null;
                            ?>
                            <tr>
                                <td>
                                    <a class="mant-prog-link" href="<?= url('habitaciones/' . (int)($item['habitacion_id'] ?? 0)) ?>">
                                        Habitacion <?= mant_prog_safe($item['habitacion_numero'] ?? null) ?>
                                    </a>
                                    <div class="mant-prog-muted"><?= mant_prog_safe($item['habitacion_tipo'] ?? null) ?> - Piso <?= mant_prog_safe($item['habitacion_piso'] ?? null) ?></div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill <?= $pillClass ?>"><?= mant_prog_safe($categoriaLabels[$categoria] ?? $categoria) ?></span>
                                    <div class="mant-prog-muted"><?= mant_prog_date($item['fecha_programada'] ?? null) ?><?= !empty($item['fecha_programada_fin']) ? ' - ' . mant_prog_date($item['fecha_programada_fin']) : '' ?></div>
                                </td>
                                <td>
                                    <strong><?= mant_prog_safe(ucfirst(str_replace('_', ' ', (string)($item['tipo_mantenimiento'] ?? '')))) ?></strong>
                                    <div class="mant-prog-muted">Prioridad <?= mant_prog_safe(ucfirst((string)($item['prioridad'] ?? 'media'))) ?></div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill"><?= mant_prog_safe(ucfirst((string)($item['habitacion_estado'] ?? ''))) ?></span>
                                    <div class="mant-prog-muted">Reservaciones conflictivas: <?= mant_prog_num($item['reservaciones_conflicto'] ?? 0) ?></div>
                                </td>
                                <td>
                                    <div class="mant-prog-warnings">
                                        <?php foreach ($advertencias as $advertencia): ?>
                                            <span class="mant-prog-warning"><?= mant_prog_safe($advertencia) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="mant-prog-pill <?= !empty($item['preview_candidato']) ? 'is-warning' : 'is-neutral' ?>">
                                        <?= !empty($item['preview_candidato']) ? 'Candidato' : 'Solo lectura' ?>
                                    </span>
                                    <?php if (!empty($item['preview_candidato']) && $puedeActivarMantenimiento): ?>
                                        <form class="mant-prog-inline-form"
                                              method="POST"
                                              action="<?= url('habitaciones/activar-mantenimiento-programado/' . (int)($item['id'] ?? 0)) ?>"
                                              data-ms-confirm
                                              data-ms-type="warning"
                                              data-ms-icon="alert"
                                              data-ms-title="¿Activar mantenimiento programado?"
                                              data-ms-msg="Se activará el mantenimiento programado y la habitación quedará en mantenimiento."
                                              data-ms-ok="Confirmar activación">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="dias" value="<?= (int)$dias ?>">
                                            <input type="hidden" name="return_to" value="preview">
                                            <button type="submit" class="mant-prog-activate">
                                                <i class="fas fa-play"></i>
                                                Activar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($item['motivo'])): ?>
                                        <div class="mant-prog-muted"><?= mant_prog_safe($item['motivo']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($tareasVinculadas)): ?>
                                        <div class="mant-prog-linked-tasks" aria-label="Tareas vinculadas">
                                            <?php foreach ($tareasVinculadas as $tarea): ?>
                                                <a class="mant-prog-linked-task" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                                    <?= mant_prog_safe($tarea['titulo'] ?? ('Tarea #' . (int)($tarea['id'] ?? 0))) ?>
                                                    <span>
                                                        <?= mant_prog_safe(ucfirst((string)($tarea['estado'] ?? ''))) ?>
                                                        - <?= mant_prog_safe(ucfirst((string)($tarea['prioridad'] ?? ''))) ?>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mant-prog-linked-empty">Sin tareas vinculadas.</div>
                                    <?php endif; ?>
                                    <?php if ($tareaActivaVinculada): ?>
                                        <div class="mant-prog-linked-empty">
                                            Tarea activa vinculada: #<?= (int)($tareaActivaVinculada['id'] ?? 0) ?>.
                                        </div>
                                    <?php elseif ($puedeActivarMantenimiento): ?>
                                        <form class="mant-prog-inline-form"
                                              method="POST"
                                              action="<?= url('tareas/desde-mantenimiento/' . (int)($item['id'] ?? 0)) ?>"
                                              data-ms-confirm
                                              data-ms-type="info"
                                              data-ms-icon="check"
                                              data-ms-title="¿Crear tarea operativa?"
                                              data-ms-msg="Se creará una tarea operativa vinculada a este mantenimiento."
                                              data-ms-ok="Confirmar tarea">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="dias" value="<?= (int)$dias ?>">
                                            <button type="submit" class="mant-prog-task-create">
                                                <i class="fas fa-tasks"></i>
                                                Crear tarea
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="mant-prog-mobile-list" aria-label="Mantenimientos programados en movil">
                    <?php foreach ($registros as $item): ?>
                        <?php
                        $categoria = (string)($item['categoria_preview'] ?? 'proximo');
                        $pillClass = $categoriaClass[$categoria] ?? 'is-neutral';
                        $advertencias = is_array($item['preview_advertencias'] ?? null) ? $item['preview_advertencias'] : [];
                        $tareasVinculadas = is_array($item['tareas_vinculadas'] ?? null) ? $item['tareas_vinculadas'] : [];
                        $tareaActivaVinculada = is_array($item['tarea_activa_vinculada'] ?? null) ? $item['tarea_activa_vinculada'] : null;
                        $fechaProgramadaTexto = mant_prog_date($item['fecha_programada'] ?? null) . (!empty($item['fecha_programada_fin']) ? ' - ' . mant_prog_date($item['fecha_programada_fin']) : '');
                        ?>
                        <article class="mant-prog-card">
                            <div class="mant-prog-card-main">
                                <div class="mant-prog-card-head">
                                    <div>
                                        <a class="mant-prog-card-room" href="<?= url('habitaciones/' . (int)($item['habitacion_id'] ?? 0)) ?>">
                                            Habitacion <?= mant_prog_safe($item['habitacion_numero'] ?? null) ?>
                                        </a>
                                        <span class="mant-prog-card-meta">
                                            <?= mant_prog_safe($item['habitacion_tipo'] ?? null) ?> - Piso <?= mant_prog_safe($item['habitacion_piso'] ?? null) ?>
                                        </span>
                                    </div>
                                    <span class="mant-prog-pill <?= $pillClass ?>"><?= mant_prog_safe($categoriaLabels[$categoria] ?? $categoria) ?></span>
                                </div>

                                <div class="mant-prog-card-schedule">
                                    <i class="fas fa-calendar-day" aria-hidden="true"></i>
                                    <div>
                                        <span>Programacion</span>
                                        <strong><?= mant_prog_safe($fechaProgramadaTexto) ?></strong>
                                    </div>
                                </div>

                                <div class="mant-prog-card-grid">
                                    <div class="mant-prog-card-cell">
                                        <span>Trabajo</span>
                                        <strong><?= mant_prog_safe(ucfirst(str_replace('_', ' ', (string)($item['tipo_mantenimiento'] ?? '')))) ?></strong>
                                        <small>Prioridad <?= mant_prog_safe(ucfirst((string)($item['prioridad'] ?? 'media'))) ?></small>
                                    </div>
                                    <div class="mant-prog-card-cell">
                                        <span>Habitacion</span>
                                        <strong><?= mant_prog_safe(ucfirst((string)($item['habitacion_estado'] ?? ''))) ?></strong>
                                        <small>Conflictos: <?= mant_prog_num($item['reservaciones_conflicto'] ?? 0) ?></small>
                                    </div>
                                </div>

                                <?php if (!empty($advertencias)): ?>
                                    <div class="mant-prog-card-alerts" aria-label="Advertencias">
                                        <?php foreach ($advertencias as $advertencia): ?>
                                            <span class="mant-prog-warning"><?= mant_prog_safe($advertencia) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mant-prog-card-review">
                                <div class="mant-prog-card-review-top">
                                    <span class="mant-prog-card-review-label">Revision</span>
                                    <span class="mant-prog-pill <?= !empty($item['preview_candidato']) ? 'is-warning' : 'is-neutral' ?>">
                                        <?= !empty($item['preview_candidato']) ? 'Candidato' : 'Solo lectura' ?>
                                    </span>
                                </div>

                                <?php if (!empty($item['motivo'])): ?>
                                    <div class="mant-prog-card-motive"><?= mant_prog_safe($item['motivo']) ?></div>
                                <?php endif; ?>

                                <div class="mant-prog-card-actions">
                                    <?php if (!empty($item['preview_candidato']) && $puedeActivarMantenimiento): ?>
                                        <form class="mant-prog-inline-form"
                                              method="POST"
                                              action="<?= url('habitaciones/activar-mantenimiento-programado/' . (int)($item['id'] ?? 0)) ?>"
                                              data-ms-confirm
                                              data-ms-type="warning"
                                              data-ms-icon="alert"
                                              data-ms-title="¿Activar mantenimiento programado?"
                                              data-ms-msg="Se activará el mantenimiento programado y la habitación quedará en mantenimiento."
                                              data-ms-ok="Confirmar activación">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="dias" value="<?= (int)$dias ?>">
                                            <input type="hidden" name="return_to" value="preview">
                                            <button type="submit" class="mant-prog-activate">
                                                <i class="fas fa-play"></i>
                                                Activar
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if (!$tareaActivaVinculada && $puedeActivarMantenimiento): ?>
                                        <form class="mant-prog-inline-form"
                                              method="POST"
                                              action="<?= url('tareas/desde-mantenimiento/' . (int)($item['id'] ?? 0)) ?>"
                                              data-ms-confirm
                                              data-ms-type="info"
                                              data-ms-icon="check"
                                              data-ms-title="¿Crear tarea operativa?"
                                              data-ms-msg="Se creará una tarea operativa vinculada a este mantenimiento."
                                              data-ms-ok="Confirmar tarea">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="dias" value="<?= (int)$dias ?>">
                                            <button type="submit" class="mant-prog-task-create">
                                                <i class="fas fa-tasks"></i>
                                                Crear tarea
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>

                                <div class="mant-prog-card-tasks" aria-label="Tareas vinculadas">
                                    <?php if (!empty($tareasVinculadas)): ?>
                                        <?php foreach ($tareasVinculadas as $tarea): ?>
                                            <a class="mant-prog-linked-task" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                                <?= mant_prog_safe($tarea['titulo'] ?? ('Tarea #' . (int)($tarea['id'] ?? 0))) ?>
                                                <span>
                                                    <?= mant_prog_safe(ucfirst((string)($tarea['estado'] ?? ''))) ?>
                                                    - <?= mant_prog_safe(ucfirst((string)($tarea['prioridad'] ?? ''))) ?>
                                                </span>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="mant-prog-linked-empty">Sin tareas vinculadas.</div>
                                    <?php endif; ?>

                                    <?php if ($tareaActivaVinculada): ?>
                                        <div class="mant-prog-linked-empty">
                                            Tarea activa vinculada: #<?= (int)($tareaActivaVinculada['id'] ?? 0) ?>.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mant-prog-note">
            Esta vista no llama activaciones automaticas, no crea tareas automaticamente y no toca Caja, pagos, abonos, nomina, offline ni /api/sync. Las acciones manuales disponibles requieren permiso, CSRF y validacion backend.
        </div>
    </section>
</div>

<script>
/* Las confirmaciones usan el modal global msConfirm (data-ms-confirm en los forms). */
</script>
