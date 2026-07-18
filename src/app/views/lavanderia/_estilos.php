<?php
/**
 * Estilos compartidos del modulo Lavanderia (panel/lotes/pedidos y detalles).
 * Calco del lenguaje boutique de areas/_estilos.php (--ax-*, aqui --lvx-*):
 * header SIN franja, fichas stat con numero grande, chips semanticos fijos y
 * tarjetas con sombra suave. White-label: el cromado deriva de --brand-*.
 *
 * A diferencia de areas, esta vista SI trae modo oscuro de fabrica: bloque
 * html[data-theme="dark"] theme-agnostico (Deleite y Cupertino) que remapea
 * los tokens neutros al set canonico #1C1C1E / #161617 / #38383A / #F5F5F7.
 * Se incluye UNA vez por vista, antes del markup.
 */
?>
<style id="lvx-estilos">
.lvx{
  /* Identidad del hotel (fallback boutique navy/oro, calco de --ax-*) */
  --lvx-primary: var(--brand-primary, #1B2746);
  --lvx-secondary: var(--brand-secondary, #0F172A);
  --lvx-accent: var(--brand-accent, #BD9441);
  --lvx-surface:#FFFFFF; --lvx-surface-warm:#F5F5F7; --lvx-surface-sunk:#FAFAFC;
  --lvx-line:#E7E1D4; --lvx-line-soft:#F0EBE0;
  --lvx-ink:#3E4A66; --lvx-muted:#6C7689; --lvx-faint:#9AA1B2;
  --lvx-title:var(--lvx-primary);
  --lvx-btn-ink:#fff;
  /* Estados (significado fijo, mismos hex que habitaciones/areas) */
  --c-available:#1E9E63; --bg-available:#E7F4EC;
  --c-occupied:#C2603C;  --bg-occupied:#F8EAE1;
  --c-cleaning:#2F77E0;  --bg-cleaning:#E6EFFC;
  --c-maint:#C2841C;     --bg-maint:#FAF0DC;
  --c-critical:#D64539;  --bg-critical:#FBE9E7;
  --lvx-serif:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --lvx-radius:16px; --lvx-radius-lg:20px;
  --lvx-shadow-xs:0 1px 2px rgba(27,39,70,.05);
  --lvx-shadow-sm:0 1px 2px rgba(27,39,70,.05),0 2px 6px rgba(27,39,70,.05);
  --lvx-shadow:0 4px 14px rgba(27,39,70,.07),0 22px 40px -24px rgba(27,39,70,.30);
  min-height:100%;
  font-family:var(--lvx-serif);
  color:var(--lvx-ink);
  padding-bottom:90px;
}

/* ── Header (sin franja: el titulo vive directo sobre el fondo) ── */
.lvx .lvx-header{ background:transparent; border-bottom:none; box-shadow:none; padding:.625rem 0 .55rem; }
.lvx .lvx-header-shell{ max-width:80rem; margin:0 auto; padding:0 1rem; }
.lvx .lvx-header-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.lvx .lvx-header-id{ display:flex; align-items:center; gap:14px; min-width:0; }
.lvx .lvx-header-ico{
  width:44px; height:44px; flex:none; display:grid; place-items:center;
  background:linear-gradient(150deg,var(--lvx-primary),var(--lvx-secondary));
  border-radius:12px; color:#fff; font-size:1.05rem;
  box-shadow:0 6px 14px -8px color-mix(in srgb,var(--lvx-primary) 70%,transparent);
}
.lvx .lvx-header h1{
  margin:0; font-family:var(--lvx-serif); font-size:2rem; font-weight:600;
  color:var(--lvx-title); letter-spacing:0; line-height:1;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.lvx .lvx-header .lvx-header-sub{ margin:3px 0 0; font-size:.74rem; color:var(--lvx-muted); }
.lvx .lvx-header-acts{ display:flex; gap:8px; flex-wrap:wrap; }
.lvx .lvx-header .ms-subnav{ margin:10px 0 0; }

/* ── Botones (calco arx-btn) ── */
.lvx .lvx-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  padding:9px 14px; border-radius:11px; border:1px solid transparent; cursor:pointer;
  text-decoration:none; font-size:.8rem; font-weight:700; line-height:1;
  transition:transform .12s ease, box-shadow .12s ease, filter .12s ease;
  background:linear-gradient(150deg,var(--lvx-primary),var(--lvx-secondary)); color:var(--lvx-btn-ink);
  box-shadow:0 8px 18px -10px color-mix(in srgb,var(--lvx-primary) 75%,transparent);
}
.lvx .lvx-btn:hover{ transform:translateY(-1px); filter:brightness(1.05); }
.lvx .lvx-btn.is-outline{ background:var(--lvx-surface); color:var(--lvx-title); border-color:var(--lvx-line); box-shadow:var(--lvx-shadow-xs); }
.lvx .lvx-btn.is-soft{ background:color-mix(in srgb,var(--lvx-accent) 14%,var(--lvx-surface)); color:color-mix(in srgb,var(--lvx-accent) 80%,var(--lvx-title)); box-shadow:none; }
.lvx .lvx-btn.is-ok{ background:var(--c-available); }
.lvx .lvx-btn.is-warn{ background:var(--c-maint); }
.lvx .lvx-btn.is-danger{ background:var(--c-critical); }

/* ── Contenido ── */
.lvx .lvx-shell{ max-width:80rem; margin:0 auto; padding:16px 1rem 0; }
.lvx .lvx-card{
  background:var(--lvx-surface); border:1px solid var(--lvx-line);
  border-radius:var(--lvx-radius); box-shadow:var(--lvx-shadow-sm); padding:16px;
}
.lvx .lvx-card + .lvx-card{ margin-top:14px; }
.lvx .lvx-card-title{
  display:flex; align-items:center; gap:9px; margin:0 0 12px;
  font-family:var(--lvx-serif); font-size:1.05rem; font-weight:700; color:var(--lvx-title);
}
.lvx .lvx-card-title i{ color:var(--lvx-accent); font-size:.92rem; }
.lvx .lvx-card-title .lvx-card-hint{ font-size:.72rem; font-weight:600; color:var(--lvx-muted); margin-left:auto; }

/* ── Fichas stat ── */
.lvx .lvx-stats{ display:grid; gap:12px; margin:0 0 16px; grid-template-columns:repeat(var(--lvx-stats-n,4),1fr); }
.lvx .lvx-stat{
  display:flex; flex-direction:column; text-decoration:none; background:var(--lvx-surface);
  border:1px solid var(--lvx-line); border-radius:var(--lvx-radius); padding:13px 15px;
  box-shadow:var(--lvx-shadow-sm); position:relative; overflow:hidden; --sc:var(--lvx-primary);
  transition:transform .12s ease, box-shadow .12s ease;
}
.lvx a.lvx-stat{ cursor:pointer; }
.lvx a.lvx-stat:hover{ transform:translateY(-1px); box-shadow:var(--lvx-shadow); }
.lvx .lvx-stat-ic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:color-mix(in srgb,var(--sc) 13%,var(--lvx-surface)); color:var(--sc); margin-bottom:9px; }
.lvx .lvx-stat-ic i{ font-size:.95rem; }
.lvx .lvx-stat-n{ font-family:var(--lvx-serif); font-size:2.05rem; font-weight:700; line-height:1; color:var(--lvx-title); font-variant-numeric:tabular-nums; }
.lvx .lvx-stat-l{ font-size:.7rem; font-weight:700; letter-spacing:.03em; color:var(--lvx-muted); margin-top:6px; text-transform:uppercase; }
.lvx .lvx-stat.st-ok{ --sc:var(--c-available); }
.lvx .lvx-stat.st-busy{ --sc:var(--c-occupied); }
.lvx .lvx-stat.st-clean{ --sc:var(--c-cleaning); }
.lvx .lvx-stat.st-warn{ --sc:var(--c-maint); }
.lvx .lvx-stat.st-off{ --sc:var(--c-critical); }

/* ── Chips semanticos ── */
.lvx .lvx-chip{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 10px; border-radius:999px; font-size:.72rem; font-weight:700; white-space:nowrap;
}
.lvx .lvx-chip.st-ok{ background:var(--bg-available); color:var(--c-available); }
.lvx .lvx-chip.st-busy{ background:var(--bg-occupied); color:var(--c-occupied); }
.lvx .lvx-chip.st-clean{ background:var(--bg-cleaning); color:var(--c-cleaning); }
.lvx .lvx-chip.st-warn{ background:var(--bg-maint); color:var(--c-maint); }
.lvx .lvx-chip.st-off{ background:var(--bg-critical); color:var(--c-critical); }
.lvx .lvx-chip.st-pause{ background:color-mix(in srgb,var(--lvx-faint) 18%,var(--lvx-surface)); color:var(--lvx-faint); }

/* ── Grid de tarjetas de blancos ── */
.lvx .lvx-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:14px; }
.lvx .lvx-item{
  background:var(--lvx-surface); border:1px solid var(--lvx-line); border-radius:var(--lvx-radius);
  box-shadow:var(--lvx-shadow-sm); padding:14px; position:relative;
  transition:transform .12s ease, box-shadow .12s ease, opacity .15s ease;
}
.lvx .lvx-item:hover{ transform:translateY(-2px); box-shadow:var(--lvx-shadow); }
.lvx .lvx-item.is-dim{ opacity:.28; }
.lvx .lvx-item.is-pausada{ opacity:.6; }
.lvx .lvx-item-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.lvx .lvx-item-id{ display:flex; align-items:flex-start; gap:10px; min-width:0; }
.lvx .lvx-tipo-ico{
  width:38px; height:38px; border-radius:11px; flex:none; display:grid; place-items:center; font-size:1rem;
  background:color-mix(in srgb,var(--lvx-accent) 14%,var(--lvx-surface)); color:color-mix(in srgb,var(--lvx-accent) 80%,var(--lvx-title));
}
.lvx .lvx-item-id strong{ display:block; font-family:var(--lvx-serif); font-size:1.02rem; font-weight:700; color:var(--lvx-title); line-height:1.15; }
.lvx .lvx-item-id small{ display:block; margin-top:2px; font-size:.72rem; font-weight:600; color:var(--lvx-muted); }
.lvx .lvx-item-acts{ display:flex; gap:7px; flex-wrap:wrap; margin-top:12px; }
.lvx .lvx-mini{
  display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer;
  padding:7px 11px; border-radius:10px; font-size:.75rem; font-weight:700;
  border:1px solid var(--lvx-line); color:var(--lvx-title); background:var(--lvx-surface);
  transition:border-color .12s ease, transform .12s ease;
}
.lvx .lvx-mini:hover{ border-color:color-mix(in srgb,var(--lvx-accent) 45%,var(--lvx-line)); transform:translateY(-1px); }

/* ── Stock por estado dentro de la tarjeta de blanco ── */
.lvx .lvx-stock{ display:grid; grid-template-columns:repeat(3,1fr); gap:7px; margin-top:11px; }
.lvx .lvx-stock-cell{
  border:1px solid var(--lvx-line-soft); border-radius:10px; padding:7px 4px 6px; text-align:center;
  background:var(--lvx-surface-sunk);
}
.lvx .lvx-stock-cell b{ display:block; font-family:var(--lvx-serif); font-size:1.15rem; font-weight:700; line-height:1.05; font-variant-numeric:tabular-nums; color:var(--lvx-title); }
.lvx .lvx-stock-cell span{ display:block; margin-top:2px; font-size:.58rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--lvx-faint); }
.lvx .lvx-stock-cell.is-ok b{ color:var(--c-available); }
.lvx .lvx-stock-cell.is-warn b{ color:var(--c-maint); }
.lvx .lvx-stock-cell.is-clean b{ color:var(--c-cleaning); }
.lvx .lvx-alerta-min{
  display:inline-flex; align-items:center; gap:6px; margin-top:9px;
  font-size:.7rem; font-weight:700; color:var(--c-critical);
}

/* ── Tablas boutique (lotes/pedidos/partidas) ── */
.lvx .lvx-tabla-wrap{ overflow-x:auto; }
.lvx table.lvx-tabla{ width:100%; border-collapse:collapse; font-size:.84rem; }
.lvx table.lvx-tabla th{
  text-align:left; padding:8px 10px; font-size:.66rem; letter-spacing:.06em; text-transform:uppercase;
  color:var(--lvx-faint); font-weight:700; border-bottom:1px solid var(--lvx-line);
  white-space:nowrap;
}
.lvx table.lvx-tabla td{ padding:10px; border-bottom:1px solid var(--lvx-line-soft); color:var(--lvx-ink); vertical-align:middle; }
.lvx table.lvx-tabla tr:last-child td{ border-bottom:none; }
.lvx table.lvx-tabla td b{ color:var(--lvx-title); font-weight:700; }
.lvx table.lvx-tabla .num{ text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
.lvx table.lvx-tabla tr[data-easy-href]{ cursor:pointer; }
.lvx table.lvx-tabla tr[data-easy-href]:hover td{ background:color-mix(in srgb,var(--lvx-accent) 6%,var(--lvx-surface)); }

/* ── Forms inline (patron arx) ── */
.lvx .lvx-panel{ display:none; margin-top:12px; border-top:1px dashed var(--lvx-line); padding-top:14px; }
.lvx .lvx-panel.is-open{ display:block; }
.lvx .lvx-form-grid{ display:grid; gap:10px; grid-template-columns:repeat(2,minmax(0,1fr)); }
.lvx label.lvx-lbl{ display:block; font-size:.7rem; letter-spacing:.08em; text-transform:uppercase; color:var(--lvx-muted); font-weight:700; margin-bottom:4px; }
.lvx .lvx-input, .lvx select.lvx-input, .lvx textarea.lvx-input{
  width:100%; padding:10px 12px; border:1px solid var(--lvx-line); border-radius:11px;
  font-size:.88rem; background:var(--lvx-surface-warm); color:var(--lvx-ink); font-weight:600;
}
.lvx .lvx-input:focus{ outline:2px solid color-mix(in srgb,var(--lvx-accent) 45%,transparent); outline-offset:1px; }

/* ── Timeline de estado del pedido ── */
.lvx .lvx-steps{ display:flex; align-items:center; gap:0; flex-wrap:wrap; }
.lvx .lvx-step{ display:flex; align-items:center; gap:7px; font-size:.74rem; font-weight:700; color:var(--lvx-faint); }
.lvx .lvx-step i{ width:26px; height:26px; border-radius:999px; display:grid; place-items:center; font-size:.66rem; background:var(--lvx-surface-warm); border:1px solid var(--lvx-line); color:var(--lvx-faint); }
.lvx .lvx-step.is-done{ color:var(--c-available); }
.lvx .lvx-step.is-done i{ background:var(--bg-available); border-color:transparent; color:var(--c-available); }
.lvx .lvx-step.is-actual{ color:var(--lvx-title); }
.lvx .lvx-step.is-actual i{ background:linear-gradient(150deg,var(--lvx-primary),var(--lvx-secondary)); border-color:transparent; color:#fff; }
.lvx .lvx-step-sep{ flex:none; width:26px; height:1px; background:var(--lvx-line); margin:0 6px; }

/* ── Historial / bitacora ── */
.lvx .lvx-hist{ display:grid; gap:8px; }
.lvx .lvx-hist-item{ border:1px solid var(--lvx-line); border-radius:12px; padding:10px 12px; background:var(--lvx-surface-sunk); }
.lvx .lvx-hist-top{ display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.lvx .lvx-hist-top strong{ color:var(--lvx-title); font-size:.84rem; font-weight:700; }
.lvx .lvx-hist-meta{ margin-top:4px; font-size:.74rem; color:var(--lvx-muted); }

.lvx .lvx-vacio{ padding:26px 14px; text-align:center; color:var(--lvx-muted); font-size:.86rem; }
.lvx .lvx-vacio i{ display:block; font-size:1.6rem; margin-bottom:8px; color:var(--lvx-accent); }

.lvx .lvx-total{ font-family:var(--lvx-serif); font-size:1.5rem; font-weight:700; color:var(--lvx-title); font-variant-numeric:tabular-nums; }

.lvx a:focus-visible, .lvx button:focus-visible{ outline:2px solid var(--c-cleaning); outline-offset:2px; }

/* ── Responsive ── */
@media (max-width:1100px){ .lvx .lvx-stats{ grid-template-columns:repeat(2,1fr); } }
@media (max-width:640px){
  .lvx .lvx-header h1{ font-size:1.6rem; }
  .lvx .lvx-stats{ grid-template-columns:repeat(2,1fr); gap:10px; }
  .lvx .lvx-stat-n{ font-size:1.6rem; }
  .lvx .lvx-shell{ padding:12px 10px 0; }
  .lvx .lvx-form-grid{ grid-template-columns:1fr; }
  .lvx .lvx-grid{ grid-template-columns:1fr; }
}
@media (prefers-reduced-motion: reduce){
  .lvx *{ transition-duration:.01ms!important; }
}

/* ── Modo oscuro (theme-agnostico: aplica en Deleite y Cupertino) ──
   Remapeo de tokens neutros al set canonico; los semanticos conservan
   matiz con fondo oscuro tintado. */
html[data-theme="dark"] .lvx{
  --lvx-surface:#1C1C1E; --lvx-surface-warm:#242426; --lvx-surface-sunk:#161617;
  --lvx-line:#38383A; --lvx-line-soft:#2C2C2E;
  --lvx-ink:#D6D8D6; --lvx-muted:#98989D; --lvx-faint:#7C7C80;
  --lvx-title:#F5F5F7;
  --bg-available:rgba(30,158,99,.18);
  --bg-occupied:rgba(194,96,60,.18);
  --bg-cleaning:rgba(47,119,224,.20);
  --bg-maint:rgba(194,132,28,.20);
  --bg-critical:rgba(214,69,57,.18);
  --lvx-shadow-xs:0 1px 2px rgba(0,0,0,.4);
  --lvx-shadow-sm:0 1px 2px rgba(0,0,0,.4),0 2px 6px rgba(0,0,0,.35);
  --lvx-shadow:0 4px 14px rgba(0,0,0,.45),0 22px 40px -24px rgba(0,0,0,.7);
}
html[data-theme="dark"] .lvx .lvx-chip.st-ok{ color:#4BC08A; }
html[data-theme="dark"] .lvx .lvx-chip.st-clean{ color:#6FA3EC; }
html[data-theme="dark"] .lvx .lvx-chip.st-warn{ color:#DCA84E; }
html[data-theme="dark"] .lvx .lvx-chip.st-busy{ color:#DC8A66; }
html[data-theme="dark"] .lvx .lvx-chip.st-off{ color:#EC7A6E; }
html[data-theme="dark"] .lvx .lvx-stock-cell.is-ok b{ color:#4BC08A; }
html[data-theme="dark"] .lvx .lvx-stock-cell.is-warn b{ color:#DCA84E; }
html[data-theme="dark"] .lvx .lvx-stock-cell.is-clean b{ color:#6FA3EC; }
html[data-theme="dark"] .lvx .lvx-alerta-min{ color:#EC7A6E; }
html[data-theme="dark"] .lvx .lvx-step.is-done{ color:#4BC08A; }
html[data-theme="dark"] .lvx .lvx-step.is-done i{ color:#4BC08A; }
</style>
