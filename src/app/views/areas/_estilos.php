<?php
/**
 * Estilos compartidos de la seccion "Habitaciones y areas" (index/ver/mapa).
 * Calco del lenguaje boutique de habitaciones/index.php (#hb-boutique-refinement):
 * mismos tokens --hb-* (aqui --ax-*), mismo header glass, mismas fichas stat
 * con numeros Manrope y mismos separadores de piso. White-label: todo deriva
 * de --brand-*; los semanticos de estado son fijos (mismos hex que habitaciones).
 * Se incluye UNA vez por vista, antes del markup.
 */
?>
<style id="arx-estilos">
.arx{
  /* Identidad del hotel (fallback boutique navy/oro, calco de --hb-*) */
  --ax-primary: var(--brand-primary, #1B2746);
  --ax-secondary: var(--brand-secondary, #0F172A);
  --ax-accent: var(--brand-accent, #BD9441);
  --ax-ivory:#F6F2EA; --ax-ivory-2:#FBF8F2;
  --ax-surface:#FFFFFF; --ax-surface-warm:#FCFAF5;
  --ax-line:#E7E1D4; --ax-line-soft:#F0EBE0;
  --ax-slate-700:#3E4A66; --ax-slate-500:#6C7689; --ax-slate-400:#9AA1B2;
  /* Estados (significado fijo, mismos hex que habitaciones) */
  --c-available:#1E9E63; --bg-available:#E7F4EC;
  --c-occupied:#C2603C;  --bg-occupied:#F8EAE1;
  --c-cleaning:#2F77E0;  --bg-cleaning:#E6EFFC;
  --c-maint:#C2841C;     --bg-maint:#FAF0DC;
  --c-critical:#D64539;  --bg-critical:#FBE9E7;
  --ax-serif:'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --ax-radius:16px; --ax-radius-lg:20px;
  --ax-shadow-xs:0 1px 2px rgba(27,39,70,.05);
  --ax-shadow-sm:0 1px 2px rgba(27,39,70,.05),0 2px 6px rgba(27,39,70,.05);
  --ax-shadow:0 4px 14px rgba(27,39,70,.07),0 22px 40px -24px rgba(27,39,70,.30);
  min-height:100%;
  font-family:var(--ax-serif);
  color:var(--ax-slate-700);
  background:
    radial-gradient(1100px 460px at 85% -12%, color-mix(in srgb, var(--ax-accent) 9%, transparent), transparent 60%),
    linear-gradient(180deg, var(--ax-ivory-2), var(--ax-ivory));
  padding-bottom:90px;
}

/* ── Header glass (calco de .modern-header de habitaciones) ── */
.arx .arx-header{
  background:color-mix(in srgb,#fff 86%,transparent);
  backdrop-filter:blur(20px) saturate(1.3);
  -webkit-backdrop-filter:blur(20px) saturate(1.3);
  border-bottom:1px solid var(--ax-line);
  box-shadow:0 1px 0 rgba(255,255,255,.7) inset,0 10px 26px -22px rgba(27,39,70,.6);
  padding:.625rem 0 .55rem;
}
.arx .arx-header-shell{ max-width:80rem; margin:0 auto; padding:0 1rem; }
.arx .arx-header-row{ display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.arx .arx-header-id{ display:flex; align-items:center; gap:14px; min-width:0; }
.arx .arx-header-ico{
  width:44px; height:44px; flex:none; display:grid; place-items:center;
  background:linear-gradient(150deg,var(--ax-primary),var(--ax-secondary));
  border-radius:12px; color:#fff; font-size:1.05rem;
  box-shadow:0 6px 14px -8px color-mix(in srgb,var(--ax-primary) 70%,transparent);
}
.arx .arx-header h1{
  margin:0; font-family:var(--ax-serif); font-size:2rem; font-weight:600;
  color:var(--ax-primary); letter-spacing:0; line-height:1;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.arx .arx-header .arx-header-sub{ margin:3px 0 0; font-size:.74rem; color:var(--ax-slate-500); }
.arx .arx-header-acts{ display:flex; gap:8px; flex-wrap:wrap; }

/* Botones del header (calco btn-modern btn-brand / outline / soft) */
.arx .arx-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  padding:9px 14px; border-radius:11px; border:1px solid transparent; cursor:pointer;
  text-decoration:none; font-size:.8rem; font-weight:700; line-height:1;
  transition:transform .12s ease, box-shadow .12s ease, filter .12s ease;
  background:linear-gradient(150deg,var(--ax-primary),var(--ax-secondary)); color:#fff;
  box-shadow:0 8px 18px -10px color-mix(in srgb,var(--ax-primary) 75%,transparent);
}
.arx .arx-btn:hover{ transform:translateY(-1px); filter:brightness(1.05); }
.arx .arx-btn.is-outline{ background:#fff; color:var(--ax-primary); border-color:var(--ax-line); box-shadow:var(--ax-shadow-xs); }
.arx .arx-btn.is-soft{ background:color-mix(in srgb,var(--ax-accent) 14%,#fff); color:color-mix(in srgb,var(--ax-accent) 80%,var(--ax-primary)); box-shadow:none; }
.arx .arx-btn.is-ok{ background:var(--c-available); }
.arx .arx-btn.is-warn{ background:var(--c-maint); }
.arx .arx-btn.is-danger{ background:var(--c-critical); }

/* La subnav de seccion vive DENTRO del header: pestanas persistentes */
.arx .arx-header .ms-subnav{ margin:10px 0 0; }

/* ── Contenido ── */
.arx .arx-shell{ max-width:80rem; margin:0 auto; padding:16px 1rem 0; }
.arx .arx-card{
  background:var(--ax-surface); border:1px solid var(--ax-line);
  border-radius:var(--ax-radius); box-shadow:var(--ax-shadow-sm); padding:16px;
}

/* ── Fichas stat (calco .hb-stat: icono, numero serif grande, label) ── */
.arx .arx-stats{ display:grid; gap:12px; margin:0 0 16px; grid-template-columns:repeat(var(--ax-stats-n,4),1fr); }
.arx .arx-stat{
  display:flex; flex-direction:column; text-decoration:none; background:var(--ax-surface);
  border:1px solid var(--ax-line); border-radius:var(--ax-radius); padding:13px 15px;
  box-shadow:var(--ax-shadow-sm); position:relative; overflow:hidden; --sc:var(--ax-primary);
  cursor:pointer; transition:transform .12s ease, box-shadow .12s ease;
}
.arx .arx-stat:hover{ transform:translateY(-1px); box-shadow:var(--ax-shadow); }
.arx .arx-stat.is-active{ border-color:color-mix(in srgb,var(--sc) 55%,var(--ax-line)); box-shadow:0 0 0 1px color-mix(in srgb,var(--sc) 55%,transparent) inset,var(--ax-shadow-sm); }
.arx .arx-stat-ic{ width:34px; height:34px; border-radius:10px; display:grid; place-items:center; background:color-mix(in srgb,var(--sc) 13%,#fff); color:var(--sc); margin-bottom:9px; }
.arx .arx-stat-ic i{ font-size:.95rem; }
.arx .arx-stat-n{ font-family:var(--ax-serif); font-size:2.05rem; font-weight:700; line-height:1; color:var(--ax-primary); font-variant-numeric:tabular-nums; }
.arx .arx-stat-l{ font-size:.7rem; font-weight:700; letter-spacing:.03em; color:var(--ax-slate-500); margin-top:6px; text-transform:uppercase; }
.arx .arx-stat.st-ok{ --sc:var(--c-available); }
.arx .arx-stat.st-busy{ --sc:var(--c-occupied); }
.arx .arx-stat.st-clean{ --sc:var(--c-cleaning); }
.arx .arx-stat.st-warn{ --sc:var(--c-maint); }
.arx .arx-stat.st-off{ --sc:var(--c-critical); }

/* ── Separador de piso (calco .floor-label de habitaciones) ── */
.arx .arx-floor{ display:flex; align-items:center; gap:14px; margin:22px 2px 13px; }
.arx .arx-floor:first-child{ margin-top:4px; }
.arx .arx-floor-t{ font-family:var(--ax-serif); font-size:1.35rem; font-weight:600; color:var(--ax-primary); white-space:nowrap; line-height:1; }
.arx .arx-floor-rule{ flex:1; height:1px; background:var(--ax-line); }
.arx .arx-floor-ct{ font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--ax-slate-400); white-space:nowrap; }

/* ── Chips de estado (semanticos fijos) ── */
.arx .arx-chip{
  display:inline-flex; align-items:center; gap:6px;
  padding:4px 10px; border-radius:999px; font-size:.72rem; font-weight:700; white-space:nowrap;
}
.arx .arx-chip.st-ok{ background:var(--bg-available); color:var(--c-available); }
.arx .arx-chip.st-busy{ background:var(--bg-occupied); color:var(--c-occupied); }
.arx .arx-chip.st-clean{ background:var(--bg-cleaning); color:var(--c-cleaning); }
.arx .arx-chip.st-warn{ background:var(--bg-maint); color:var(--c-maint); }
.arx .arx-chip.st-off{ background:var(--bg-critical); color:var(--c-critical); }
.arx .arx-chip.st-pause{ background:#EEEDE9; color:var(--ax-slate-400); }

/* ── Tarjetas de area (grid calco .rgrid) ── */
.arx .arx-grid{ display:grid; grid-template-columns:repeat(auto-fill,minmax(232px,1fr)); gap:14px; }
.arx .arx-item{
  background:var(--ax-surface); border:1px solid var(--ax-line); border-radius:var(--ax-radius);
  box-shadow:var(--ax-shadow-sm); padding:14px; position:relative;
  transition:transform .12s ease, box-shadow .12s ease, opacity .15s ease;
}
.arx .arx-item:hover{ transform:translateY(-2px); box-shadow:var(--ax-shadow); }
.arx .arx-item.is-dim{ opacity:.28; }
.arx .arx-item.is-pausada{ opacity:.6; }
.arx .arx-item-top{ display:flex; align-items:flex-start; justify-content:space-between; gap:8px; }
.arx .arx-item-id{ display:flex; align-items:flex-start; gap:10px; min-width:0; }
.arx .arx-tipo-ico{
  width:38px; height:38px; border-radius:11px; flex:none; display:grid; place-items:center; font-size:1rem;
  background:color-mix(in srgb,var(--ax-accent) 14%,#fff); color:color-mix(in srgb,var(--ax-accent) 80%,var(--ax-primary));
}
.arx .arx-item-id strong{ display:block; font-family:var(--ax-serif); font-size:1.02rem; font-weight:700; color:var(--ax-primary); line-height:1.15; }
.arx .arx-item-id small{ display:block; margin-top:2px; font-size:.72rem; font-weight:600; color:var(--ax-slate-500); }
.arx .arx-item-meta{ display:flex; gap:12px; flex-wrap:wrap; margin-top:10px; font-size:.74rem; color:var(--ax-slate-500); }
.arx .arx-item-meta b{ color:var(--ax-slate-700); font-weight:700; }
.arx .arx-item-acts{ display:flex; gap:7px; flex-wrap:wrap; margin-top:12px; }
.arx .arx-mini{
  display:inline-flex; align-items:center; gap:6px; text-decoration:none; cursor:pointer;
  padding:7px 11px; border-radius:10px; font-size:.75rem; font-weight:700;
  border:1px solid var(--ax-line); color:var(--ax-primary); background:#fff;
  transition:border-color .12s ease, transform .12s ease;
}
.arx .arx-mini:hover{ border-color:color-mix(in srgb,var(--ax-accent) 45%,var(--ax-line)); transform:translateY(-1px); }

/* ── Fichas del mapa: habitacion (cuadrada) y area (ancha) ── */
.arx .arx-map-grid{ display:grid; gap:9px; grid-template-columns:repeat(auto-fill,minmax(92px,1fr)); }
.arx .arx-room{
  position:relative; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;
  aspect-ratio:1/.9; border-radius:13px; text-decoration:none;
  background:var(--ax-surface); border:1px solid var(--ax-line); box-shadow:var(--ax-shadow-xs);
  transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease, opacity .15s ease;
}
.arx .arx-room:hover{ transform:translateY(-2px); box-shadow:var(--ax-shadow); border-color:color-mix(in srgb,var(--ax-accent) 45%,var(--ax-line)); }
.arx .arx-room b{ font-family:var(--ax-serif); font-size:1.4rem; font-weight:700; color:var(--ax-primary); line-height:1; font-variant-numeric:tabular-nums; max-width:92%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.arx .arx-room small{ font-size:.6rem; letter-spacing:.05em; text-transform:uppercase; font-weight:700; color:var(--ax-slate-400); max-width:92%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.arx .arx-dot{ position:absolute; top:8px; right:8px; width:9px; height:9px; border-radius:999px; }
.arx .st-ok .arx-dot{ background:var(--c-available); }
.arx .st-busy .arx-dot{ background:var(--c-occupied); }
.arx .st-clean .arx-dot{ background:var(--c-cleaning); animation:arxPulse 2.4s ease-in-out infinite; }
.arx .st-warn .arx-dot{ background:var(--c-maint); animation:arxPulse 2.4s ease-in-out infinite; }
.arx .st-off .arx-dot{ background:var(--c-critical); }
.arx .arx-room.st-busy{ background:color-mix(in srgb,var(--c-occupied) 5%,var(--ax-surface)); }
.arx .arx-room.st-clean{ background:color-mix(in srgb,var(--c-cleaning) 5%,var(--ax-surface)); }
.arx .arx-room.st-warn{ background:color-mix(in srgb,var(--c-maint) 6%,var(--ax-surface)); }
.arx .arx-room.st-off{ background:color-mix(in srgb,var(--c-critical) 5%,var(--ax-surface)); }
.arx .is-dim.arx-room, .arx .is-dim.arx-area{ opacity:.22; }
@keyframes arxPulse{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.45);opacity:.55;}}

.arx .arx-map-areas{ display:grid; gap:9px; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); margin-top:9px; }
.arx .arx-area{
  display:flex; align-items:center; gap:10px; padding:11px 12px; border-radius:13px; text-decoration:none;
  background:var(--ax-surface); border:1px solid var(--ax-line); box-shadow:var(--ax-shadow-xs);
  transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease, opacity .15s ease;
}
.arx .arx-area:hover{ transform:translateY(-2px); box-shadow:var(--ax-shadow); border-color:color-mix(in srgb,var(--ax-accent) 45%,var(--ax-line)); }
.arx .arx-area .arx-tipo-ico{ width:34px; height:34px; font-size:.9rem; }
.arx .arx-area-txt{ min-width:0; flex:1; }
.arx .arx-area-txt strong{ display:block; font-family:var(--ax-serif); font-size:.88rem; font-weight:700; color:var(--ax-primary); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.arx .arx-area-txt small{ font-size:.68rem; color:var(--ax-slate-500); font-weight:600; }

/* ── Forms y paneles ── */
.arx .arx-panel{ display:none; margin-top:12px; border-top:1px dashed var(--ax-line); padding-top:14px; }
.arx .arx-panel.is-open{ display:block; }
.arx .arx-form-grid{ display:grid; gap:10px; grid-template-columns:repeat(2,minmax(0,1fr)); }
.arx label.arx-lbl{ display:block; font-size:.7rem; letter-spacing:.08em; text-transform:uppercase; color:var(--ax-slate-500); font-weight:700; margin-bottom:4px; }
.arx .arx-input, .arx select.arx-input{
  width:100%; padding:10px 12px; border:1px solid var(--ax-line); border-radius:11px;
  font-size:.88rem; background:var(--ax-surface-warm); color:var(--ax-slate-700); font-weight:600;
}
.arx .arx-input:focus{ outline:2px solid color-mix(in srgb,var(--ax-accent) 45%,transparent); outline-offset:1px; }
.arx .arx-check{ display:flex; align-items:center; gap:9px; padding:8px 10px; border:1px solid var(--ax-line); border-radius:10px; background:var(--ax-surface-warm); font-size:.84rem; color:var(--ax-slate-700); font-weight:600; cursor:pointer; }
.arx .arx-check input{ width:16px; height:16px; accent-color:var(--ax-primary); }
.arx .arx-personal{ display:grid; gap:7px; grid-template-columns:repeat(2,minmax(0,1fr)); }

/* ── Historial ── */
.arx .arx-hist{ display:grid; gap:8px; }
.arx .arx-hist-item{ border:1px solid var(--ax-line); border-radius:12px; padding:10px 12px; background:var(--ax-surface-warm); }
.arx .arx-hist-top{ display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
.arx .arx-hist-top strong{ color:var(--ax-primary); font-size:.84rem; font-weight:700; }
.arx .arx-hist-meta{ margin-top:4px; font-size:.74rem; color:var(--ax-slate-500); }

.arx .arx-vacio{ padding:26px 14px; text-align:center; color:var(--ax-slate-500); font-size:.86rem; }
.arx .arx-vacio i{ display:block; font-size:1.6rem; margin-bottom:8px; color:var(--ax-accent); }
.arx .arx-leyenda{ display:flex; gap:14px; flex-wrap:wrap; font-size:.72rem; color:var(--ax-slate-500); font-weight:700; }
.arx .arx-leyenda i{ font-size:.6rem; margin-right:4px; }
.arx .arx-leyenda .st-ok i{ color:var(--c-available); } .arx .arx-leyenda .st-busy i{ color:var(--c-occupied); }
.arx .arx-leyenda .st-clean i{ color:var(--c-cleaning); } .arx .arx-leyenda .st-warn i{ color:var(--c-maint); }
.arx .arx-leyenda .st-off i{ color:var(--c-critical); }

.arx a:focus-visible, .arx button:focus-visible{ outline:2px solid var(--c-cleaning); outline-offset:2px; }

/* ── Responsive ── */
@media (max-width:1100px){ .arx .arx-stats{ grid-template-columns:repeat(3,1fr); } }
@media (max-width:640px){
  .arx .arx-header h1{ font-size:1.6rem; }
  .arx .arx-stats{ grid-template-columns:repeat(2,1fr); gap:10px; }
  .arx .arx-stat-n{ font-size:1.6rem; }
  .arx .arx-shell{ padding:12px 10px 0; }
  .arx .arx-map-grid{ grid-template-columns:repeat(auto-fill,minmax(76px,1fr)); }
  .arx .arx-form-grid, .arx .arx-personal{ grid-template-columns:1fr; }
}
@media (prefers-reduced-motion: reduce){
  .arx .arx-dot{ animation:none!important; }
  .arx *{ transition-duration:.01ms!important; }
}
</style>
