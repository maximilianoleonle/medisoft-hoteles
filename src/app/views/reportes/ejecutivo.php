<?php
if (!function_exists('exec_safe')) {
    function exec_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('exec_money')) {
    function exec_money($value): string
    {
        if (function_exists('format_money')) {
            return format_money((float)($value ?? 0));
        }

        return '$' . number_format((float)($value ?? 0), 2);
    }
}

if (!function_exists('exec_num')) {
    function exec_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('exec_pct')) {
    function exec_pct($value): string
    {
        return number_format((float)($value ?? 0), 1) . '%';
    }
}

if (!function_exists('exec_date')) {
    function exec_date($value, bool $withTime = false): string
    {
        $time = strtotime((string)($value ?? ''));
        if (!$time) {
            return '-';
        }

        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $time);
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$filtros = is_array($reporte['filtros'] ?? null) ? $reporte['filtros'] : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$operacion = is_array($reporte['operacion'] ?? null) ? $reporte['operacion'] : [];
$finanzas = is_array($reporte['finanzas'] ?? null) ? $reporte['finanzas'] : [];
$inventario = is_array($reporte['inventario'] ?? null) ? $reporte['inventario'] : [];
$personal = is_array($reporte['personal'] ?? null) ? $reporte['personal'] : [];
$documentos = is_array($reporte['documentos'] ?? null) ? $reporte['documentos'] : [];
$alertas = is_array($reporte['alertas'] ?? null) ? $reporte['alertas'] : [];
$totalesAlertas = is_array($reporte['totales_alertas'] ?? null) ? $reporte['totales_alertas'] : [];
$schemaOk = !empty($reporte['schema_ok']);

$periodos = ['hoy' => 'Hoy', '7d' => '7 dias', '30d' => '30 dias', 'mes' => 'Mes', 'custom' => 'Custom'];
$areas = ['todas' => 'Todas', 'operacion' => 'Operacion', 'finanzas' => 'Finanzas', 'inventario' => 'Inventario', 'personal' => 'Personal', 'documentos' => 'Documentos'];
$severidades = ['todas' => 'Todas', 'ok' => 'OK', 'warning' => 'Advertencias', 'error' => 'Errores'];
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');
</style><style>
.exec-page{--exec-brand:var(--brand-primary,#1B2746);--exec-brand-2:var(--brand-secondary,#0F172A);--exec-accent:var(--brand-accent,#BD9441);--exec-text:var(--brand-text,#172033);--exec-muted:var(--brand-muted,#64748B);--exec-border:var(--brand-border,#E5E7EB);--exec-soft:color-mix(in srgb,var(--exec-brand) 5%,#F8FAFC);--exec-accent-soft:color-mix(in srgb,var(--exec-accent) 12%,#FFFFFF);max-width:1440px;margin:0 auto;padding:24px;color:var(--exec-text)}
.exec-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:18px;padding-bottom:16px;border-bottom:1px solid var(--exec-border)}
.exec-kicker{font-size:12px;text-transform:uppercase;letter-spacing:.12em;color:var(--exec-muted);font-weight:700}
.exec-title{margin:5px 0 7px;color:var(--exec-brand-2);font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:32px;line-height:1.08;font-weight:700;letter-spacing:0}
.exec-subtitle{margin:0;max-width:820px;color:var(--exec-muted);font-size:14px;line-height:1.5}
.exec-actions{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}
.exec-btn,.exec-badge{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:9px 12px;border-radius:8px;border:1px solid var(--exec-border);background:#fff;color:var(--exec-text);font-size:13px;font-weight:700;text-decoration:none}
.exec-btn.primary{background:var(--exec-brand);border-color:var(--exec-brand);color:var(--brand-action-text,#fff)}
.exec-badge{background:var(--exec-accent-soft);border-color:color-mix(in srgb,var(--exec-accent) 34%,#fff);color:var(--exec-brand)}
.exec-filter{margin-bottom:16px;padding:14px;border:1px solid var(--exec-border);border-radius:8px;background:#fff}
.exec-filter-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:10px;align-items:end}
.exec-field label{display:block;margin-bottom:6px;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.exec-field input,.exec-field select{width:100%;height:38px;border:1px solid var(--exec-border);border-radius:7px;background:#fff;color:var(--exec-text);font-size:13px;font-weight:600;padding:0 10px}
.exec-field input:focus,.exec-field select:focus{outline:0;border-color:var(--exec-brand);box-shadow:0 0 0 3px color-mix(in srgb,var(--exec-brand) 13%,transparent)}
.exec-metrics{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:16px}
.exec-metric{border:1px solid var(--exec-border);border-radius:8px;background:#fff;padding:14px;min-height:104px}
.exec-metric span{display:block;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.exec-metric strong{display:block;margin-top:8px;color:var(--exec-brand-2);font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:24px;line-height:1.08;font-weight:700}
.exec-metric small{display:block;margin-top:7px;color:var(--exec-muted);font-size:12px;font-weight:600}
.exec-layout{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(360px,.8fr);gap:14px}
.exec-stack{display:grid;gap:14px}
.exec-panel{border:1px solid var(--exec-border);border-radius:8px;background:#fff;overflow:hidden}
.exec-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid var(--exec-border);background:var(--exec-soft)}
.exec-panel-title{margin:0;color:var(--exec-brand-2);font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:18px;font-weight:700}
.exec-panel-subtitle{margin:4px 0 0;color:var(--exec-muted);font-size:12px}
.exec-panel-body{padding:14px 16px}
.exec-stat-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.exec-stat{border:1px solid color-mix(in srgb,var(--exec-border) 78%,transparent);border-radius:8px;padding:11px;background:#fff}
.exec-stat span{display:block;color:var(--exec-muted);font-size:11px;font-weight:700}
.exec-stat strong{display:block;margin-top:5px;color:var(--exec-brand);font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:20px;font-weight:700}
.exec-row{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:42px;border-top:1px solid color-mix(in srgb,var(--exec-border) 70%,transparent)}
.exec-row:first-child{border-top:0}
.exec-row span{color:var(--exec-muted);font-size:13px;font-weight:600}
.exec-row strong{color:var(--exec-brand-2);font-size:14px;font-weight:700;text-align:right}
.exec-table-wrap{overflow-x:auto}
.exec-table{width:100%;border-collapse:collapse;min-width:620px}
.exec-table th{padding:10px 12px;text-align:left;color:var(--exec-muted);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;border-bottom:1px solid var(--exec-border)}
.exec-table td{padding:11px 12px;border-bottom:1px solid #eef2f7;vertical-align:top;font-size:13px}
.exec-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:700}
.exec-status.ok{background:#ecfdf5;color:#047857}
.exec-status.warning{background:#fffbeb;color:#b45309}
.exec-status.error{background:#fef2f2;color:#b91c1c}
.exec-link{color:var(--exec-brand);font-weight:700;text-decoration:none}
.exec-empty{padding:24px;text-align:center;color:var(--exec-muted)}
.exec-empty strong{display:block;color:var(--exec-brand-2);margin-bottom:6px}
.exec-footnote{margin-top:10px;color:var(--exec-muted);font-size:12px;font-weight:500}
@media (max-width:1220px){.exec-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}.exec-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.exec-layout{grid-template-columns:1fr}.exec-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:720px){.exec-page{padding:16px}.exec-hero{display:block}.exec-actions{justify-content:flex-start;margin-top:12px}.exec-title{font-size:24px}.exec-metrics,.exec-filter-grid,.exec-stat-grid{grid-template-columns:1fr}.exec-table{min-width:540px}}

/* Executive compact refresh: aligned with guest/reservation creation screens */
.exec-page{
    --exec-brand:var(--brand-primary,#1B2746);
    --exec-brand-2:var(--brand-secondary,#0F172A);
    --exec-accent:var(--brand-accent,#BD9441);
    --exec-accent-dark:color-mix(in srgb,var(--exec-accent) 72%,#3F2E12);
    --exec-accent-soft:color-mix(in srgb,var(--exec-accent) 7%,#FFFFFF);
    --exec-accent-line:color-mix(in srgb,var(--exec-accent) 18%,#E8DDCA);
    --exec-ivory:color-mix(in srgb,var(--exec-accent) 3%,#F8FAFC);
    --exec-surface:#FFFFFF;
    --exec-surface-warm:#FFFFFF;
    --exec-line:color-mix(in srgb,var(--exec-brand-2) 11%,#E5E7EB);
    --exec-line-soft:color-mix(in srgb,var(--exec-brand-2) 7%,#F1F5F9);
    --exec-muted:color-mix(in srgb,var(--exec-brand-2) 48%,#94A3B8);
    width:100%;
    max-width:none;
    margin:0;
    padding:22px 30px 38px;
    background:linear-gradient(180deg,#fff,#f8faf7);
    border-radius:0;
    font-family:'Manrope',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
    -webkit-font-smoothing:antialiased;
    text-rendering:optimizeLegibility;
}
.exec-page :where(p,span,a,button,input,select,label,small,td,th){font-family:'Manrope',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
.exec-hero{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px;padding:0 2px;border-bottom:0}
.exec-hero-main{display:flex;align-items:flex-start;gap:14px;min-width:0}
.exec-hero-icon{display:grid;place-items:center;flex:0 0 44px;width:44px;height:44px;border:1px solid var(--exec-line);border-radius:13px;background:#fff;color:var(--exec-brand);box-shadow:none}
.exec-kicker{margin:0 0 4px;color:var(--exec-muted);font-size:.7rem;font-weight:800;letter-spacing:.08em}
.exec-title{margin:0;color:#111827;font-size:clamp(2rem,3vw,2.65rem);line-height:.96;font-weight:650;text-wrap:balance}
.exec-subtitle{max-width:64ch;margin:8px 0 0;color:var(--exec-muted);font-size:.88rem;font-weight:600;line-height:1.55}
.exec-actions{align-items:center;gap:8px}
.exec-btn,.exec-badge{min-height:38px;padding:8px 12px;border-color:var(--exec-line);border-radius:10px;background:var(--exec-surface);color:var(--exec-brand-2);font-size:.76rem;font-weight:800;box-shadow:none}
.exec-btn:hover{border-color:var(--exec-accent-line);background:#fff;transform:translateY(-1px)}
.exec-btn.primary{width:100%;border-color:var(--exec-brand);background:var(--exec-brand);color:#fff;box-shadow:none}
.exec-badge{border-color:var(--exec-line);background:#fff;color:var(--exec-muted)}
.exec-filter{margin-bottom:16px;padding:10px;border-color:var(--exec-line);border-radius:14px;background:#fff;box-shadow:none}
.exec-filter-head{display:none;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:10px;padding:0 2px}
.exec-filter-title{margin:0;color:#111827;font-size:.9rem;font-weight:900;line-height:1.1}
.exec-filter-note{margin:0;color:var(--exec-muted);font-size:.72rem;font-weight:700;line-height:1.25;text-align:right}
.exec-filter-grid{grid-template-columns:1fr repeat(2,minmax(128px,.92fr)) repeat(3,minmax(112px,.8fr)) minmax(112px,.72fr);gap:8px;align-items:end}
.exec-field label{margin-bottom:5px;color:var(--exec-muted);font-size:.63rem;font-weight:850;letter-spacing:.075em}
.exec-field input,.exec-field select{height:38px;border-color:var(--exec-line);border-radius:11px;background:#FFFFFF;color:#111827;font-size:.78rem;font-weight:750;padding:0 10px}
.exec-field input:focus,.exec-field select:focus{border-color:var(--exec-accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--exec-accent) 18%,transparent)}
.exec-metrics{grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;margin-bottom:16px}
.exec-metric{position:relative;min-height:86px;padding:12px 13px;border-color:var(--exec-line);border-radius:14px;background:#fff;box-shadow:none;overflow:hidden}
.exec-metric::before{content:"";position:absolute;inset:0 auto 0 0;width:3px;height:auto;background:color-mix(in srgb,var(--exec-brand) 22%,#DDE3EA)}
.exec-metric:nth-child(2)::before,
.exec-metric:nth-child(3)::before,
.exec-metric:nth-child(4)::before,
.exec-metric:nth-child(5)::before,
.exec-metric:nth-child(6)::before{background:color-mix(in srgb,var(--exec-brand) 22%,#DDE3EA)}
.exec-metric span{color:var(--exec-muted);font-size:.6rem;font-weight:850;letter-spacing:.07em;line-height:1.15}
.exec-metric strong{margin-top:8px;color:#111827;font-size:1.36rem;line-height:1;font-weight:700;overflow-wrap:anywhere}
.exec-metric small{margin-top:6px;color:var(--exec-muted);font-size:.68rem;font-weight:700;line-height:1.25}
.exec-layout{grid-template-columns:minmax(0,1.65fr) minmax(340px,.8fr);gap:16px;align-items:start}
.exec-stack{gap:16px}
.exec-panel{
    --exec-section:#6F7F8F;
    position:relative;
    border-color:color-mix(in srgb,var(--exec-section) 22%,var(--exec-line));
    border-radius:14px;
    background:#fff;
    box-shadow:0 14px 28px -28px rgba(17,24,39,.32);
    scroll-margin-top:18px;
    transition:border-color .18s ease,box-shadow .18s ease,transform .18s ease;
}
.exec-panel::before{
    content:"";
    position:absolute;
    inset:0 auto 0 0;
    width:4px;
    background:var(--exec-section);
    opacity:.72;
}
.exec-panel:focus-within{
    border-color:color-mix(in srgb,var(--exec-section) 48%,var(--exec-line));
    box-shadow:0 0 0 3px color-mix(in srgb,var(--exec-section) 12%,transparent),0 18px 34px -30px rgba(17,24,39,.36);
}
@media (hover:hover){
    .exec-panel:hover{
        border-color:color-mix(in srgb,var(--exec-section) 38%,var(--exec-line));
        box-shadow:0 18px 34px -30px rgba(17,24,39,.38);
    }
}
.exec-panel-head{
    align-items:center;
    padding:13px 15px 13px 18px;
    border-bottom-color:color-mix(in srgb,var(--exec-section) 16%,var(--exec-line-soft));
    background:linear-gradient(90deg,color-mix(in srgb,var(--exec-section) 8%,#fff) 0%,#fff 62%);
}
.exec-panel-title{display:flex;align-items:center;gap:8px;font-size:1.02rem;line-height:1.1;font-weight:700}
.exec-panel-title::before{
    content:"";
    width:8px;
    height:8px;
    flex:0 0 8px;
    border-radius:999px;
    background:var(--exec-section);
    box-shadow:0 0 0 3px color-mix(in srgb,var(--exec-section) 14%,transparent);
}
.exec-panel-subtitle{font-size:.72rem;font-weight:700;line-height:1.35}
.exec-layout > .exec-stack:first-child > .exec-panel:nth-child(1){--exec-section:#5F7D68}
.exec-layout > .exec-stack:first-child > .exec-panel:nth-child(2){--exec-section:#9B7A47}
.exec-layout > .exec-stack:nth-child(2) > .exec-panel:nth-child(1){--exec-section:#A06A3A}
.exec-layout > .exec-stack:nth-child(2) > .exec-panel:nth-child(2){--exec-section:#547C70}
.exec-layout > .exec-stack:nth-child(2) > .exec-panel:nth-child(3){--exec-section:#5E728E}
.exec-layout > .exec-stack:nth-child(2) > .exec-panel:nth-child(4){--exec-section:#6F7480}
.exec-panel-body{padding:12px 14px}
.exec-stat-grid{gap:8px}
.exec-stat{min-height:68px;padding:9px 10px;border-color:var(--exec-line-soft);border-radius:12px;background:#fbfcfd}
.exec-stat span{color:var(--exec-muted);font-size:.66rem;font-weight:800;line-height:1.2}
.exec-stat strong{margin-top:5px;color:#111827;font-size:1.3rem;line-height:1;font-weight:700}
.exec-row{min-height:40px;padding:9px 0;border-top-color:var(--exec-line-soft)}
.exec-row span{min-width:0;color:var(--exec-muted);font-size:.78rem;font-weight:700;line-height:1.35}
.exec-row span small{display:inline-block;margin-top:3px;color:color-mix(in srgb,var(--exec-muted) 86%,#111827);font-size:.7rem;line-height:1.35}
.exec-row strong{color:#111827;font-size:.82rem;font-weight:850;line-height:1.3}
.exec-table-wrap{border-top:1px solid var(--exec-line-soft);overflow-x:auto}
.exec-table{min-width:620px}
.exec-table th{padding:10px 12px;color:var(--exec-muted);font-size:.63rem;font-weight:850;border-bottom-color:var(--exec-line)}
.exec-table td{padding:10px 12px;border-bottom-color:var(--exec-line-soft);color:#111827;font-size:.78rem;font-weight:650}
.exec-link{color:var(--exec-brand);font-weight:850}
.exec-status{border:1px solid transparent;min-height:24px;padding:4px 8px;font-size:.66rem;line-height:1}
.exec-status.ok{border-color:#CFE9DA;background:#F4FBF7;color:#276749}
.exec-status.warning{border-color:#ECD9A8;background:#FFFCF2;color:#806117}
.exec-status.error{border-color:#E8C5C5;background:#FFF7F7;color:#8B2E2E}
.exec-empty{padding:22px 16px;color:var(--exec-muted)}
.exec-empty strong{color:#111827}
.exec-footnote{margin-top:9px;color:var(--exec-muted);font-size:.72rem;font-weight:700;line-height:1.35}
@media (max-width:1220px){
    .exec-filter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    .exec-metrics{grid-template-columns:repeat(3,minmax(0,1fr))}
    .exec-layout{grid-template-columns:1fr}
}
@media (max-width:720px){
    .exec-page{padding:10px 10px 18px;background:linear-gradient(180deg,#fff,#f8faf7)}
    .exec-hero{display:block;margin-bottom:14px;padding:0 4px}
    .exec-hero-main{gap:9px}
    .exec-hero-icon{width:38px;height:38px;flex-basis:38px;border-radius:12px;font-size:.88rem}
    .exec-kicker{font-size:.57rem;letter-spacing:.12em}
    .exec-title{font-size:1.38rem;line-height:.96}
    .exec-subtitle{display:-webkit-box;max-width:none;margin-top:3px;font-size:.68rem;line-height:1.25;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .exec-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin-top:12px}
    .exec-actions .exec-badge{grid-column:1/-1}
    .exec-btn,.exec-badge{width:100%;min-height:36px;padding:7px 8px;border-radius:11px;font-size:.64rem}
    .exec-filter{margin-bottom:9px;padding:10px;border-radius:15px}
    .exec-filter-head{display:none}
    .exec-filter-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .exec-field:last-child{grid-column:1/-1}
    .exec-field label{font-size:.55rem}
    .exec-field input,.exec-field select{height:38px;border-radius:10px;font-size:.72rem;padding:0 8px}
    .exec-metrics{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px;margin-bottom:9px}
    .exec-metric{min-height:76px;padding:9px 9px 8px;border-radius:14px}
    .exec-metric span{font-size:.54rem}
    .exec-metric strong{margin-top:6px;font-size:1.18rem}
    .exec-metric small{font-size:.6rem}
    .exec-layout,.exec-stack{gap:9px}
    .exec-panel{border-radius:15px}
    .exec-panel-head{align-items:center;padding:9px 10px;gap:8px}
    .exec-panel-title{font-size:.9rem}
    .exec-panel-subtitle{font-size:.66rem}
    .exec-panel-head .exec-btn,.exec-panel-head .exec-badge{width:auto;min-width:max-content;min-height:32px;padding:6px 8px}
    .exec-panel-body{padding:9px 10px}
    .exec-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .exec-stat{min-height:56px;padding:8px;border-radius:12px}
    .exec-stat span{font-size:.58rem}
    .exec-stat strong{font-size:1.08rem}
    .exec-row{align-items:flex-start;min-height:0;margin-bottom:7px;padding:8px 9px;border:1px solid var(--exec-line-soft);border-radius:12px;background:#FFFFFF}
    .exec-row:last-child{margin-bottom:0}
    .exec-row span{font-size:.7rem}
    .exec-row span small{font-size:.63rem}
    .exec-row strong{font-size:.73rem}
    .exec-status{min-height:22px;padding:3px 7px;font-size:.58rem}
    .exec-table-wrap{padding:8px 9px;border-top-color:var(--exec-line-soft);overflow:visible;background:#fbfcfd}
    .exec-table{display:block;min-width:0;width:100%;border-collapse:separate}
    .exec-table thead{display:none}
    .exec-table tbody{display:grid;gap:8px}
    .exec-table tr{display:grid;gap:5px;padding:10px;border:1px solid var(--exec-line-soft);border-radius:13px;background:#fff;box-shadow:none}
    .exec-table td{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;min-width:0;padding:0;border:0;font-size:.72rem;line-height:1.3;text-align:right;overflow-wrap:anywhere}
    .exec-table td::before{content:attr(data-label);flex:0 0 auto;color:var(--exec-muted);font-size:.56rem;font-weight:850;letter-spacing:.06em;text-align:left;text-transform:uppercase}
    .exec-table td.exec-empty{display:block;padding:12px 6px;text-align:center}
    .exec-table td.exec-empty::before{display:none}
    .exec-footnote{font-size:.66rem}
}
@media (max-width:380px){
    .exec-filter-grid{grid-template-columns:1fr}
    .exec-field:last-child{grid-column:auto}
    .exec-actions{grid-template-columns:1fr}
    .exec-metrics{grid-template-columns:1fr}
}
@media (prefers-reduced-motion:reduce){
    .exec-page *{transition:none!important}
}
</style>

<div class="exec-page">
    <div class="exec-hero">
        <div class="exec-hero-main">
            <div class="exec-hero-icon" aria-hidden="true">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div>
                <div class="exec-kicker">Reportes / Solo lectura</div>
                <h1 class="exec-title">Tablero ejecutivo</h1>
                <p class="exec-subtitle">KPIs consolidados del periodo, alertas por area y enlaces de contexto del hotel actual.</p>
            </div>
        </div>
        <div class="exec-actions">
            <span class="exec-badge"><i class="fas fa-lock"></i> Solo lectura</span>
            <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="exec-btn ms-back-legacy ms-glass-btn" href="<?= back_url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="exec-btn ms-glass-btn" href="<?= url('operacion/conciliacion-financiera') ?>"><i class="fas fa-balance-scale"></i> Conciliacion</a>
        </div>
    </div>

    <form class="exec-filter" method="get" action="<?= url('reportes/ejecutivo') ?>">
        <div class="exec-filter-head">
            <div>
                <p class="exec-filter-title">Filtros del tablero</p>
            </div>
            <p class="exec-filter-note">Conserva el periodo y area al consultar.</p>
        </div>
        <div class="exec-filter-grid">
            <div class="exec-field">
                <label for="periodo">Periodo</label>
                <select id="periodo" name="periodo">
                    <?php foreach ($periodos as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['periodo'] ?? 'mes') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="fecha_desde">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde" value="<?= exec_safe($filtros['fecha_desde'] ?? '', '') ?>">
            </div>
            <div class="exec-field">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= exec_safe($filtros['fecha_hasta'] ?? '', '') ?>">
            </div>
            <div class="exec-field">
                <label for="area">Area</label>
                <select id="area" name="area">
                    <?php foreach ($areas as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['area'] ?? 'todas') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="severidad">Estado</label>
                <select id="severidad" name="severidad">
                    <?php foreach ($severidades as $value => $label): ?>
                        <option value="<?= exec_safe($value) ?>" <?= (($filtros['severidad'] ?? 'todas') === $value) ? 'selected' : '' ?>><?= exec_safe($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <label for="limit">Alertas</label>
                <select id="limit" name="limit">
                    <?php foreach ([5, 10, 25] as $limit): ?>
                        <option value="<?= $limit ?>" <?= ((int)($filtros['limit'] ?? 10) === $limit) ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="exec-field">
                <button class="exec-btn primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
            </div>
        </div>
    </form>

    <div class="exec-metrics">
        <div class="exec-metric"><span>Ingresos</span><strong><?= exec_money($resumen['ingresos_periodo'] ?? 0) ?></strong><small><?= exec_safe($filtros['fecha_desde'] ?? '') ?> a <?= exec_safe($filtros['fecha_hasta'] ?? '') ?></small></div>
        <div class="exec-metric"><span>Gastos</span><strong><?= exec_money($resumen['gastos_periodo'] ?? 0) ?></strong><small>Neto <?= exec_money($resumen['neto_periodo'] ?? 0) ?></small></div>
        <div class="exec-metric"><span>Por cobrar (CxC)</span><strong><?= exec_money($resumen['saldo_cxc'] ?? 0) ?></strong><small><?= exec_num($finanzas['cxc_pendientes'] ?? 0) ?> cuentas</small></div>
        <div class="exec-metric"><span>Por pagar (CxP)</span><strong><?= exec_money($resumen['saldo_cxp'] ?? 0) ?></strong><small><?= exec_num($finanzas['cxp_pendientes'] ?? 0) ?> cuentas</small></div>
        <div class="exec-metric"><span>Ocupacion</span><strong><?= exec_pct($operacion['ocupacion_pct'] ?? 0) ?></strong><small><?= exec_num($operacion['habitaciones_ocupadas'] ?? 0) ?> de <?= exec_num($operacion['habitaciones_total'] ?? 0) ?> habitaciones</small></div>
        <div class="exec-metric"><span>Alertas</span><strong><?= exec_num(($totalesAlertas['warning'] ?? 0) + ($totalesAlertas['error'] ?? 0)) ?></strong><small><?= $schemaOk ? 'Información completa' : 'Faltan algunos datos' ?></small></div>
    </div>

    <div class="exec-layout">
        <div class="exec-stack">
            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Operacion hotelera</h2>
                        <p class="exec-panel-subtitle">Habitaciones, agenda y reservaciones proximas.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('operacion/diaria') ?>">El hotel hoy</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-stat-grid">
                        <div class="exec-stat"><span>Disponibles</span><strong><?= exec_num($operacion['habitaciones_disponibles'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Ocupadas</span><strong><?= exec_num($operacion['habitaciones_ocupadas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Limpieza</span><strong><?= exec_num($operacion['habitaciones_limpieza'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Mantenimiento</span><strong><?= exec_num($operacion['habitaciones_mantenimiento'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Check-ins hoy</span><strong><?= exec_num($operacion['checkins_hoy'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Check-outs hoy</span><strong><?= exec_num($operacion['checkouts_hoy'] ?? 0) ?></strong></div>
                    </div>
                </div>
                <div class="exec-table-wrap">
                    <table class="exec-table">
                        <thead><tr><th>Reservacion</th><th>Huesped</th><th>Entrada</th><th>Salida</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach (($operacion['proximas_reservaciones'] ?? []) as $reservacion): ?>
                            <tr>
                                <td data-label="Reservacion"><a class="exec-link" href="<?= url('reservaciones/ver/' . (int)($reservacion['id'] ?? 0)) ?>">#<?= (int)($reservacion['id'] ?? 0) ?></a></td>
                                <td data-label="Huesped"><?= exec_safe($reservacion['huesped'] ?? 'Sin huesped') ?></td>
                                <td data-label="Entrada"><?= exec_date($reservacion['fecha_entrada'] ?? '') ?></td>
                                <td data-label="Salida"><?= exec_date($reservacion['fecha_salida'] ?? '') ?></td>
                                <td data-label="Estado"><?= exec_safe($reservacion['estado'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($operacion['proximas_reservaciones'])): ?>
                            <tr><td colspan="5" class="exec-empty"><strong>Sin reservaciones proximas</strong></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Finanzas y Caja</h2>
                        <p class="exec-panel-subtitle">Cartera, movimientos por metodo y cortes.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('caja/arqueo-metodos') ?>">Arqueo</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-stat-grid">
                        <div class="exec-stat"><span>CxC vencidas</span><strong><?= exec_num($finanzas['cxc_vencidas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>CxP vencidas</span><strong><?= exec_num($finanzas['cxp_vencidas'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cortes abiertos</span><strong><?= exec_num($resumen['cortes_abiertos'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cortes con diferencia</span><strong><?= exec_num($finanzas['cortes_con_diferencia'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Cobros CxC</span><strong><?= exec_num($finanzas['cxc_cobros_periodo'] ?? 0) ?></strong></div>
                        <div class="exec-stat"><span>Pagos CxP</span><strong><?= exec_num($finanzas['cxp_pagos_periodo'] ?? 0) ?></strong></div>
                    </div>
                    <div class="exec-footnote">Reversiones del periodo: CxC <?= exec_num($finanzas['cxc_reversiones_periodo'] ?? 0) ?> / CxP <?= exec_num($finanzas['cxp_reversiones_periodo'] ?? 0) ?>.</div>
                </div>
                <div class="exec-table-wrap">
                    <table class="exec-table">
                        <thead><tr><th>Metodo</th><th>Ingresos</th><th>Total ingresos</th><th>Gastos</th><th>Total gastos</th></tr></thead>
                        <tbody>
                        <?php foreach (($finanzas['metodos'] ?? []) as $metodo): ?>
                            <tr>
                                <td data-label="Metodo"><?= exec_safe($metodo['metodo_pago'] ?? 'sin metodo') ?></td>
                                <td data-label="Ingresos"><?= exec_num($metodo['ingresos_count'] ?? 0) ?></td>
                                <td data-label="Total ingresos"><?= exec_money($metodo['ingresos_total'] ?? 0) ?></td>
                                <td data-label="Gastos"><?= exec_num($metodo['gastos_count'] ?? 0) ?></td>
                                <td data-label="Total gastos"><?= exec_money($metodo['gastos_total'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($finanzas['metodos'])): ?>
                            <tr><td colspan="5" class="exec-empty"><strong>Sin movimientos por metodo</strong></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div class="exec-stack">
            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Alertas</h2>
                        <p class="exec-panel-subtitle">Filtradas por area y estado.</p>
                    </div>
                    <span class="exec-badge"><?= exec_num(count($alertas)) ?> visibles</span>
                </div>
                <div class="exec-panel-body">
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="exec-row">
                            <span>
                                <span class="exec-status <?= exec_safe($alerta['severidad'] ?? 'warning') ?>"><?= exec_safe($alerta['severidad'] ?? 'warning') ?></span>
                                <?= exec_safe($alerta['titulo'] ?? '') ?><br>
                                <small><?= exec_safe($alerta['detalle'] ?? '') ?></small>
                            </span>
                            <strong>
                                <?= exec_num($alerta['valor'] ?? 0) ?>
                                <?php if (!empty($alerta['href'])): ?><br><a class="exec-link" href="<?= url($alerta['href']) ?>">Ver</a><?php endif; ?>
                            </strong>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($alertas)): ?>
                        <div class="exec-empty"><strong>Sin alertas para los filtros actuales</strong></div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Inventario y compras</h2>
                        <p class="exec-panel-subtitle">Stock, compras recibidas y movimientos.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('inventario') ?>">Inventario</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Productos bajo minimo</span><strong><?= exec_num($inventario['productos_bajo_minimo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Sin movimiento en 30 dias</span><strong><?= exec_num($inventario['productos_sin_movimiento_30d'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Compras recibidas</span><strong><?= exec_num($inventario['compras_recibidas_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Total compras</span><strong><?= exec_money($inventario['compras_total_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Entradas / salidas</span><strong><?= exec_num($inventario['movimientos_entrada'] ?? 0) ?> / <?= exec_num($inventario['movimientos_salida'] ?? 0) ?></strong></div>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Tareas y personal</h2>
                        <p class="exec-panel-subtitle">Carga activa por operacion.</p>
                    </div>
                    <a class="exec-btn" href="<?= url('tareas') ?>">Tareas</a>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Trabajadores activos</span><strong><?= exec_num($personal['trabajadores_activos'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Tareas activas</span><strong><?= exec_num($personal['tareas_activas'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Tareas vencidas</span><strong><?= exec_num($personal['tareas_vencidas'] ?? 0) ?></strong></div>
                    <?php foreach (($personal['tareas_por_categoria'] ?? []) as $categoria): ?>
                        <div class="exec-row"><span><?= exec_safe($categoria['categoria'] ?? 'Sin categoria') ?></span><strong><?= exec_num($categoria['total'] ?? 0) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="exec-panel">
                <div class="exec-panel-head">
                    <div>
                        <h2 class="exec-panel-title">Documentos y auditoria</h2>
                        <p class="exec-panel-subtitle">Resumen documental del periodo.</p>
                    </div>
                </div>
                <div class="exec-panel-body">
                    <div class="exec-row"><span>Documentos del periodo</span><strong><?= exec_num($documentos['documentos_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Documentos archivados</span><strong><?= exec_num($documentos['documentos_archivados'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Eventos de auditoria</span><strong><?= exec_num($documentos['auditoria_periodo'] ?? 0) ?></strong></div>
                    <div class="exec-row"><span>Auditoria historica sin hotel</span><strong><?= exec_num($documentos['auditoria_sin_hotel'] ?? 0) ?></strong></div>
                    <?php foreach (($documentos['documentos_por_entidad'] ?? []) as $entidad): ?>
                        <div class="exec-row"><span><?= exec_safe($entidad['entidad_tipo'] ?? 'Entidad') ?></span><strong><?= exec_num($entidad['total'] ?? 0) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>
</div>
