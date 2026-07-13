<?php
/**
 * UI compartida de los reportes de Personal / Nómina (Deleite Sereno, white-label).
 *
 * Un solo sistema .nr-* derivado de --brand-*, para que TODAS las vistas de
 * reportes (pagos de caja, periodos, conciliación, auditoría, expediente) vayan
 * con la misma secuencia visual que el módulo Nómina (empleados, periodos,
 * ficha, detalle del snapshot). Reemplaza las convenciones fragmentadas
 * (wk-*, snap-*, payroll-*, audit-*, exp-*) por una sola familia.
 *
 * Uso en una vista:
 *   <?php include APP_PATH . '/views/partials/nomina_report_ui.php'; ?>
 *   <div class="nom-report"> … markup .nr-* … </div>
 *
 * Los filtros siguen usando el partial compartido .msf-* (partials/filtros.php).
 * Los assets se emiten una sola vez por request.
 */
if (defined('MS_NOMINA_REPORT_UI')) {
    return;
}
define('MS_NOMINA_REPORT_UI', 1);
?>
<style>
.nom-report {
    --nom-brand: var(--brand-primary, #1B2746);
    --nom-gold: var(--brand-accent, #BD9441);
    --nom-surface: var(--brand-surface, #F6F2EA);
    --nom-text: var(--brand-text, #232323);
    --nom-muted: var(--brand-muted, #6d675e);
    --nom-border: var(--brand-border, #e3dccd);
    --nom-card: color-mix(in srgb, var(--nom-surface) 55%, #ffffff);
    --nom-ok: #2e7d32; --nom-warn: #9a7400; --nom-danger: #c62828;
    color: var(--nom-text); max-width: 1120px; margin: 0 auto; padding: 4px 4px 40px;
    font-weight: 450; line-height: 1.5;
}
.nom-report .grid { min-height: 0; } /* neutraliza el .grid{min-height} global */

/* --- Encabezado --- */
.nom-report .nr-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 14px; }
.nom-report .nom-kicker { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--nom-gold); font-weight: 700; margin: 0; }
.nom-report .nom-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 28px; margin: 2px 0 0; font-weight: 600; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; line-height: 1.15; }
.nom-report .nr-sub { color: var(--nom-muted); font-size: 13.5px; margin: 6px 0 0; line-height: 1.5; max-width: 60rem; }

/* --- Botones / toolbar / pill --- */
.nom-report .nr-toolbar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.nom-report .nr-btn { display: inline-flex; align-items: center; gap: 7px; border: 1px solid var(--nom-border); border-radius: 11px; padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; background: var(--nom-card); color: var(--nom-text); text-decoration: none; white-space: nowrap; transition: border-color .15s ease, filter .15s ease; }
.nom-report .nr-btn:hover { border-color: color-mix(in srgb, var(--nom-gold) 45%, var(--nom-border)); color: var(--nom-text); }
.nom-report .nr-btn-primary { background: var(--nom-brand); border-color: var(--nom-brand); color: #fff; }
.nom-report .nr-btn-primary:hover { color: #fff; filter: brightness(1.08); }
.nom-report .nr-btn-danger { background: #fff; border-color: rgba(198,40,40,.4); color: var(--nom-danger); }
.nom-report .nr-btn-danger:hover { background: rgba(198,40,40,.06); border-color: rgba(198,40,40,.55); }
.nom-report .nr-btn.w-full { width: 100%; justify-content: center; }
.nom-report .nr-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; background: var(--nom-card); color: var(--nom-muted); border: 1px solid var(--nom-border); font-size: 12px; font-weight: 700; }

/* --- Badges --- */
.nom-report .nr-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; font-weight: 700; border-radius: 999px; padding: 3px 11px; border: 1px solid transparent; }
.nom-report .nr-badge i { font-size: 9px; }
.nom-report .nr-badge.ok { background: rgba(46,125,50,.12); color: var(--nom-ok); border-color: rgba(46,125,50,.20); }
.nom-report .nr-badge.warn { background: rgba(191,144,0,.14); color: var(--nom-warn); border-color: rgba(191,144,0,.24); }
.nom-report .nr-badge.danger { background: rgba(198,40,40,.10); color: var(--nom-danger); border-color: rgba(198,40,40,.20); }
.nom-report .nr-badge.neutral { background: rgba(0,0,0,.05); color: var(--nom-muted); }
.nom-report .nr-badge.brand { background: color-mix(in srgb, var(--nom-brand) 10%, transparent); color: var(--nom-brand); }

/* --- Toggle de sub-pestañas (Historial | Simular) --- */
.nom-report .nr-tabs-block { display: grid; gap: 7px; justify-items: start; }
.nom-report .nr-scope { display: inline-flex; align-items: center; gap: 8px; padding-left: 2px; font-size: 10.5px; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: var(--nom-muted); }
.nom-report .nr-scope::before { content: ""; width: 14px; height: 2px; border-radius: 999px; background: var(--nom-gold); }
.nom-report .nr-tabs { display: flex; gap: 4px; padding: 5px; width: fit-content; max-width: 100%; background: color-mix(in srgb, var(--nom-surface) 55%, #fff); border: 1px solid var(--nom-border); border-radius: 14px; }
.nom-report .nr-tab { display: inline-flex; align-items: center; gap: 8px; border: 1px solid transparent; border-radius: 10px; background: transparent; color: var(--nom-muted); min-height: 38px; padding: 0 15px; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; text-decoration: none; transition: color .15s ease, background .15s ease; }
.nom-report .nr-tab i { font-size: 12px; }
.nom-report .nr-tab:hover { color: var(--nom-text); background: rgba(255,255,255,.6); }
.nom-report .nr-tab.is-active { background: #fff; color: var(--nom-brand); border-color: var(--nom-border); }
.nom-report .nr-tab.is-active i { color: var(--nom-gold); }

/* --- KPIs / stats --- */
.nom-report .nr-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; }
.nom-report .nr-kpi { border: 1px solid var(--nom-border); border-radius: 14px; padding: 13px 15px; background: #fff; }
.nom-report .nr-kpi.is-accent { background: color-mix(in srgb, var(--nom-gold) 7%, #fff); border-color: color-mix(in srgb, var(--nom-gold) 28%, var(--nom-border)); }
.nom-report .nr-kpi.is-ok { background: rgba(46,125,50,.06); border-color: rgba(46,125,50,.22); }
.nom-report .nr-kpi.is-warn { background: rgba(191,144,0,.07); border-color: rgba(191,144,0,.26); }
.nom-report .nr-kpi-label { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nom-report .nr-kpi-value { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 24px; font-weight: 600; margin-top: 4px; line-height: 1.1; font-variant-numeric: tabular-nums; }
.nom-report .nr-kpi.is-ok .nr-kpi-value { color: var(--nom-ok); }
.nom-report .nr-kpi-foot { font-size: 11.5px; color: var(--nom-muted); margin-top: 3px; }

/* --- Cards / panels --- */
.nom-report .nr-card { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; padding: 18px 20px; }
.nom-report .nr-card h2, .nom-report .nr-panel-title { font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 18px; margin: 0; font-weight: 600; color: var(--nom-text); }
.nom-report .nr-card-hint { font-size: 12.5px; color: var(--nom-muted); margin: 3px 0 0; line-height: 1.45; }
.nom-report .nr-panel { background: var(--nom-card); border: 1px solid var(--nom-border); border-radius: 16px; overflow: hidden; }
.nom-report .nr-panel-head { padding: 15px 18px; border-bottom: 1px solid var(--nom-border); display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 10px; }
.nom-report .nr-panel-sub { font-size: 12.5px; color: var(--nom-muted); margin-top: 3px; }
.nom-report .nr-panel-body { padding: 16px 18px; }

/* --- Meta (etiqueta/valor) --- */
.nom-report .nr-meta-label { font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; }
.nom-report .nr-meta-value { font-size: 14.5px; font-weight: 600; margin-top: 3px; }
.nom-report .nr-meta-sub { font-size: 11.5px; color: var(--nom-muted); margin-top: 2px; }

/* --- Grids / stack --- */
.nom-report .nr-stack { display: grid; gap: 14px; }
.nom-report .nr-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
.nom-report .nr-grid3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 14px; }
.nom-report .nr-grid4 { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 14px; }

/* --- Tablas --- */
.nom-report .nr-table-wrap { overflow-x: auto; }
.nom-report .nr-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.nom-report .nr-table thead th { text-align: left; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; color: var(--nom-muted); font-weight: 700; padding: 11px 13px; border-bottom: 1px solid var(--nom-border); background: color-mix(in srgb, var(--nom-surface) 40%, #fff); white-space: nowrap; }
.nom-report .nr-table td { padding: 12px 13px; border-bottom: 1px dashed var(--nom-border); vertical-align: top; }
.nom-report .nr-table tbody tr:last-child td { border-bottom: 0; }
.nom-report .nr-table tbody tr:hover td { background: color-mix(in srgb, var(--nom-gold) 5%, transparent); }
.nom-report .nr-table th.nr-r, .nom-report .nr-table td.nr-r { text-align: right; }
.nom-report .nr-r { text-align: right; font-variant-numeric: tabular-nums; }
.nom-report .nr-strong { font-weight: 600; color: var(--nom-text); }
.nom-report .nr-sub-txt { color: var(--nom-muted); font-size: 11.5px; }
.nom-report .nr-neg { color: var(--nom-danger); }
.nom-report .nr-link { font-weight: 600; color: var(--nom-brand); text-decoration: none; }
.nom-report .nr-link:hover { text-decoration: underline; text-underline-offset: 2px; }
.nom-report .nr-empty-cell { text-align: center; color: var(--nom-muted); padding: 18px; font-size: 12.5px; }
.nom-report .nr-cap { text-transform: capitalize; }

/* --- Estado vacío / aviso --- */
.nom-report .nr-empty { text-align: center; padding: 40px 18px; }
.nom-report .nr-empty-icon { width: 52px; height: 52px; margin: 0 auto 12px; border-radius: 16px; display: grid; place-items: center; background: color-mix(in srgb, var(--nom-gold) 14%, #fff); color: var(--nom-gold); font-size: 20px; }
.nom-report .nr-empty h3 { color: var(--nom-text); font-size: 16px; font-weight: 600; margin: 0; }
.nom-report .nr-empty p { color: var(--nom-muted); font-size: 13px; margin: 6px 0 0; }
.nom-report .nr-notice { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px; border-radius: 14px; background: color-mix(in srgb, var(--nom-gold) 9%, #fff); border: 1px solid color-mix(in srgb, var(--nom-gold) 30%, #fff); }
.nom-report .nr-notice i { color: var(--nom-gold); font-size: 16px; margin-top: 2px; }
.nom-report .nr-notice strong { color: var(--nom-text); display: block; margin-bottom: 2px; font-weight: 700; }
.nom-report .nr-notice p { color: var(--nom-muted); font-size: 13px; margin: 0; }

/* --- Timeline (auditoría / bitácora) --- */
.nom-report .nr-timeline { display: flex; flex-direction: column; }
.nom-report .nr-tl-item { position: relative; padding: 0 0 16px 22px; border-left: 2px solid var(--nom-border); }
.nom-report .nr-tl-item:last-child { border-left-color: transparent; padding-bottom: 2px; }
.nom-report .nr-tl-item::before { content: ""; position: absolute; left: -6px; top: 3px; width: 10px; height: 10px; border-radius: 999px; background: var(--nom-gold); box-shadow: 0 0 0 3px color-mix(in srgb, var(--nom-gold) 18%, transparent); }
.nom-report .nr-tl-title { font-weight: 600; font-size: 13.5px; }
.nom-report .nr-tl-meta { font-size: 11.5px; color: var(--nom-muted); margin-top: 2px; }
.nom-report .nr-tl-body { font-size: 12.5px; margin-top: 4px; color: var(--nom-text); }

/* --- Ayuda contextual --- */
.nom-report .nr-help { border-bottom: 1px dotted currentColor; cursor: help; }
.nom-report .nr-act { display: inline-flex; align-items: center; gap: 6px; min-height: 32px; padding: 0 11px; border-radius: 9px; background: var(--nom-card); border: 1px solid var(--nom-border); color: var(--nom-brand); font-size: 12px; font-weight: 700; text-decoration: none; }
.nom-report .nr-act:hover { border-color: color-mix(in srgb, var(--nom-gold) 45%, var(--nom-border)); }

/* --- Responsive --- */
@media (max-width: 960px) { .nom-report .nr-grid4 { grid-template-columns: repeat(2, 1fr); } .nom-report .nr-grid3 { grid-template-columns: 1fr; } .nom-report .nr-kpis { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 720px) { .nom-report .nr-grid2 { grid-template-columns: 1fr; } }
@media (max-width: 640px) {
    .nom-report .nr-kpis, .nom-report .nr-grid4 { grid-template-columns: 1fr; }
    .nom-report .nr-head { flex-direction: column; }
    .nom-report .nr-tabs { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
    .nom-report .nr-tabs::-webkit-scrollbar { display: none; }
    .nom-report .nr-tab { flex: 1 0 auto; justify-content: center; }
    .nom-report .nr-toolbar .nr-btn { flex: 1 1 auto; justify-content: center; }
}

/* KPIs de 5 columnas (auditoría). Responsive definido con la misma
   especificidad para que gane sobre la base en pantallas chicas. */
.nom-report .nr-kpis.cols-5 { grid-template-columns: repeat(5, minmax(0,1fr)); }
@media (max-width: 1024px) { .nom-report .nr-kpis.cols-5 { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 720px) { .nom-report .nr-kpis.cols-5 { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .nom-report .nr-kpis.cols-5 { grid-template-columns: 1fr; } }
</style>
