<?php
/**
 * Tablero movil de limpieza (bloque camarista). Tarjetas grandes por
 * habitacion con accion de un toque; primero lo que hay que limpiar.
 */
$habitaciones = $habitaciones ?? [];
$salidasHoy = $salidasHoy ?? [];
$ocupacion = $ocupacion ?? [];
$personal = $personal ?? [];
$tareasLimpieza = $tareasLimpieza ?? [];
$tareasLimpiezaAreas = $tareasLimpiezaAreas ?? [];

// Hora de salida estándar del hotel (HH:MM); vacía si no aplica.
$horaSalidaFmt = trim((string) ($horaSalida ?? ''));
$horaSalidaFmt = $horaSalidaFmt !== '' ? substr($horaSalidaFmt, 0, 5) : '';
$camHayPersonal = !empty($personal);

$camSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$camHoy = date('Y-m-d');
$camManana = date('Y-m-d', strtotime('+1 day'));

// Orden de trabajo: en limpieza primero, luego salidas de hoy, luego el resto.
$prioridad = static function ($hab) use ($salidasHoy) {
    $id = (int) $hab['id'];
    if ($hab['estado'] === 'limpieza') return 0;
    if (isset($salidasHoy[$id]) && $hab['estado'] !== 'disponible') return 1;
    if ($hab['estado'] === 'ocupada') return 2;
    if ($hab['estado'] === 'disponible') return 3;
    return 4; // mantenimiento
};
usort($habitaciones, static function ($a, $b) use ($prioridad) {
    $pa = $prioridad($a);
    $pb = $prioridad($b);
    if ($pa !== $pb) return $pa <=> $pb;
    return (int) $a['numero'] <=> (int) $b['numero'];
});

$porLimpiar = count(array_filter($habitaciones, static function ($h) {
    return $h['estado'] === 'limpieza';
}));

// Deleite Sereno: progreso de limpieza del turno (aditivo, solo lectura).
$camLimpias   = count(array_filter($habitaciones, static function ($h) {
    return $h['estado'] === 'disponible';
}));
$camCiclo     = $camLimpias + $porLimpiar;             // habitaciones dentro del ciclo limpio/sucio
$camPct       = $camCiclo > 0 ? (int) round($camLimpias / $camCiclo * 100) : 100;
$camTodoAlDia = ($porLimpiar === 0);

// Areas del hotel (bloque habitaciones y areas): mismo tablero, mismo flujo de
// limpieza. El controlador ya filtra a disponible/limpieza (lo que toca a
// limpieza); mantenimiento/cerrada son de recepcion.
$areas = $areas ?? [];
$tiposArea = $tiposArea ?? [];
$areasPorLimpiar = array_values(array_filter($areas, static function ($a) { return ($a['estado'] ?? '') === 'limpieza'; }));
$areasListas     = array_values(array_filter($areas, static function ($a) { return ($a['estado'] ?? '') === 'disponible'; }));
?>

<style>
/* ════════════════════════════════════════════════════════════════
   Tablero de camarista — lenguaje "Deleite Sereno".
   Base serena (marfil cálido + serif de despliegue) + microrecompensas
   (progreso del turno, brillo del CTA, lift de tarjeta, destello).
   White-label: cromado desde --brand-*; estados = colores semánticos.
   ════════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700&family=Manrope:wght@400;500;600;700&display=swap');

.cam{
  --dx-brand: var(--brand-primary, #1B2746);
  --dx-gold: var(--brand-accent, #BD9441);
  /* Identidad de limpieza: azul fresco = --c-cleaning del sistema (igual que el modal) */
  --dx-clean:#2F77E0; --dx-clean-deep:#1E5FBF; --dx-clean-bright:#5A9BF2; --dx-clean-soft:#E6EFFC; --dx-clean-mist:#F3F8FF;
  --dx-success:#1E9E63; --dx-success-2:#17864F; --dx-success-soft:#E7F4EC;
  --dx-occ:#C2603C; --dx-occ-soft:#F8EAE1;
  --dx-slate:#64748B; --dx-slate-soft:#EEF1F4;
  --dx-surface:#FFFFFF; --dx-surface-warm:#F5F5F7; --dx-ivory:#F5F5F7;
  --dx-line:#E7E1D4; --dx-line-cool:#D5E3F6; --dx-ink:#20293A; --dx-ink-soft:#5C6675; --dx-ink-faint:#8B94A3;
  --dx-radius:20px; --dx-radius-md:15px; --dx-radius-sm:11px;
  --dx-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --dx-ease:cubic-bezier(.22,1,.36,1);
  width: 100%; min-height: 100vh;
  font-family: 'DM Sans','Outfit',system-ui,-apple-system,sans-serif; color: var(--dx-ink);
  background:
    radial-gradient(1100px 460px at 88% -12%, color-mix(in srgb, var(--dx-clean) 9%, transparent), transparent 60%),
    linear-gradient(180deg, var(--dx-clean-mist), #FAFAFC 300px) !important;
}
/* Contenido centrado y ancho (mismo tope que el index de habitaciones: max-w-7xl) */
.cam-inner{ max-width: 1280px; margin: 0 auto; padding: 22px 16px 84px; }
.cam *{ box-sizing: border-box; }

/* ── Hero: identidad de limpieza (azul fresco + burbujas), hermano del modal ── */
.cam-hero{ position:relative; overflow:hidden; display:flex; align-items:center; gap:14px; padding:22px; margin-bottom:16px;
  border-radius:var(--dx-radius); color:#fff;
  background:linear-gradient(135deg, var(--dx-clean-deep) 0%, var(--dx-clean) 52%, var(--dx-clean-bright) 100%);
  box-shadow:0 22px 44px -26px rgba(30,95,191,.65), 0 0 0 1px rgba(213,227,246,.35); }
.cam-hero::after{ content:''; position:absolute; inset:0; pointer-events:none;
  background:radial-gradient(120% 80% at 16% -20%, rgba(255,255,255,.34), transparent 60%); }
.cam-bubbles{ position:absolute; inset:0; overflow:hidden; pointer-events:none; }
.cam-bubble{ position:absolute; bottom:-24px; border-radius:50%; opacity:0;
  background:radial-gradient(circle at 32% 30%, rgba(255,255,255,.9), rgba(255,255,255,.28) 45%, rgba(255,255,255,.06) 70%);
  box-shadow:inset 0 0 6px rgba(255,255,255,.4); animation:camRise linear infinite; }
.cam-bubble.b1{ left:10%; width:14px; height:14px; animation-duration:7s;   animation-delay:0s; }
.cam-bubble.b2{ left:30%; width:9px;  height:9px;  animation-duration:9s;   animation-delay:1.4s; }
.cam-bubble.b3{ left:52%; width:18px; height:18px; animation-duration:8s;   animation-delay:.6s; }
.cam-bubble.b4{ left:72%; width:11px; height:11px; animation-duration:10s;  animation-delay:2.1s; }
.cam-bubble.b5{ left:88%; width:7px;  height:7px;  animation-duration:6.5s; animation-delay:.9s; }
/* En táctil (donde las camaristas viven) las 5 burbujas perpetuas se apagan:
   5 capas animándose 24/7 en el hero gastan compositor y pila. El destello
   del emblema se queda como guiño vivo. */
@media (pointer: coarse){ .cam-bubble{ animation:none; } }
.cam-emblem{ position:relative; z-index:1; flex:0 0 auto; width:54px; height:54px; border-radius:16px; display:grid; place-items:center;
  background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.34);
  box-shadow:inset 0 1px 0 rgba(255,255,255,.4), 0 8px 18px -10px rgba(0,0,0,.4);
  -webkit-backdrop-filter:blur(4px); backdrop-filter:blur(4px); }
.cam-emblem i{ font-size:1.35rem; color:#fff; }
.cam-emblem__spark{ position:absolute; top:-4px; right:-4px; width:14px; height:14px;
  background:linear-gradient(135deg,#fff,var(--dx-gold));
  clip-path:polygon(50% 0,60% 40%,100% 50%,60% 60%,50% 100%,40% 60%,0 50%,40% 40%);
  filter:drop-shadow(0 0 4px rgba(255,255,255,.7));
  animation:camTwinkle 2.4s ease-in-out infinite; }
.cam-hero__t{ position:relative; z-index:1; min-width:0; }
.cam h1{ margin:0; font-family:var(--dx-serif); font-weight:700; font-size:1.95rem; line-height:1; letter-spacing:.01em; color:#fff; }
.cam .sub{ margin:3px 0 0; color:rgba(255,255,255,.86); font-size:.86rem; }

/* ── Flash ── */
.cam-flash{ display:flex; align-items:center; gap:10px; margin-bottom:14px; padding:12px 14px; border-radius:var(--dx-radius-md); font-size:.88rem; font-weight:600; }
.cam-flash i{ font-size:1.1rem; flex:0 0 auto; }
.cam-flash.ok{ background:var(--dx-success-soft); color:var(--dx-success-2); border:1px solid color-mix(in srgb, var(--dx-success) 22%, #fff); }
.cam-flash.ok i{ animation:camPop .5s var(--dx-ease) both; }
.cam-flash.err{ background:#FBE9E7; color:#B0352B; border:1px solid #F0B9B2; }

/* ── Tablero: stats + progreso del turno ── */
.cam-board{ background:var(--dx-surface); border:1px solid var(--dx-line); border-radius:var(--dx-radius);
  box-shadow:0 12px 32px -22px rgba(20,40,80,.4); padding:16px; margin-bottom:18px; }
.cam-stats{ display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:14px; }
.cam-stat{ background:var(--dx-surface-warm); border-radius:var(--dx-radius-md); padding:13px 8px; text-align:center; }
.cam-stat__n{ display:block; font-family:var(--dx-serif); font-weight:700; font-size:1.75rem; line-height:1; color:var(--dx-ink); }
.cam-stat__l{ display:block; margin-top:4px; font-size:.72rem; font-weight:600; color:var(--dx-ink-soft); }
.cam-stat--pend .cam-stat__n{ color:var(--dx-clean-deep); }
.cam-stat--done .cam-stat__n{ color:var(--dx-success); }
.cam-progress-row{ display:flex; align-items:baseline; justify-content:space-between; gap:12px; margin-bottom:8px; font-size:.8rem; color:var(--dx-ink-soft); }
.cam-progress-row strong{ font-family:var(--dx-serif); font-size:1.15rem; font-weight:700; color:var(--dx-clean-deep); }
.cam-progress{ height:8px; border-radius:999px; background:var(--dx-clean-soft); overflow:hidden; }
.cam-progress > span{ display:block; height:100%; border-radius:999px;
  background:linear-gradient(90deg, var(--dx-clean), var(--dx-clean-bright));
  transition:width .5s var(--dx-ease); }
.cam-done{ display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:var(--dx-radius-md);
  background:var(--dx-success-soft); border:1px solid color-mix(in srgb, var(--dx-success) 25%, #fff); color:var(--dx-success-2); font-weight:700; font-size:.9rem; }
.cam-done i{ font-size:1.15rem; animation:camPop .5s var(--dx-ease) both; }

/* ── Secciones del tablero (agrupadas por lo que necesita cada cuarto) ── */
.cam-section{ margin-bottom:22px; }
.cam-section__head{ display:flex; align-items:center; gap:12px; margin:0 2px 12px; }
summary.cam-section__head{ cursor:pointer; list-style:none; -webkit-tap-highlight-color:transparent; }
summary.cam-section__head::-webkit-details-marker{ display:none; }
details.cam-section:not([open]) > summary.cam-section__head{ margin-bottom:0; }
.cam-section__ico{ flex:0 0 auto; width:36px; height:36px; border-radius:11px; display:grid; place-items:center; font-size:.98rem; }
.cam-section__meta{ min-width:0; }
.cam-section__t{ display:flex; align-items:center; gap:9px; font-family:var(--dx-serif); font-weight:700; font-size:1.08rem; line-height:1.15; color:var(--dx-ink); }
.cam-section__count{ font-size:.74rem; font-weight:700; padding:2px 9px; border-radius:999px; }
.cam-section__hint{ margin-top:2px; font-size:.76rem; color:var(--dx-ink-faint); }
.cam-section__chev{ margin-left:auto; flex:0 0 auto; color:var(--dx-ink-faint); transition:transform .2s var(--dx-ease); }
details.cam-section[open] .cam-section__chev{ transform:rotate(180deg); }
.cam-section--pend  .cam-section__ico  { background:var(--dx-clean-soft);   color:var(--dx-clean-deep); }
.cam-section--pend  .cam-section__count{ background:var(--dx-clean-soft);   color:var(--dx-clean-deep); }
.cam-section--occ   .cam-section__ico  { background:var(--dx-occ-soft);     color:var(--dx-occ); }
.cam-section--occ   .cam-section__count{ background:var(--dx-occ-soft);     color:var(--dx-occ); }
.cam-section--done  .cam-section__ico  { background:var(--dx-success-soft); color:var(--dx-success-2); }
.cam-section--done  .cam-section__count{ background:var(--dx-success-soft); color:var(--dx-success-2); }
.cam-section--maint .cam-section__ico  { background:var(--dx-slate-soft);   color:var(--dx-slate); }
.cam-section--maint .cam-section__count{ background:var(--dx-slate-soft);   color:var(--dx-slate); }

/* ── Grid de habitaciones ── */
.cam-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(212px, 1fr)); gap:12px; }
.cam-card{ position:relative; display:flex; flex-direction:column; gap:12px; padding:15px;
  background:var(--dx-surface); border:1.5px solid var(--dx-line); border-radius:var(--dx-radius-md);
  box-shadow:0 1px 2px rgba(20,40,80,.04);
  transition:transform .18s var(--dx-ease), box-shadow .18s var(--dx-ease), border-color .18s var(--dx-ease); }
.cam-card:hover{ transform:translateY(-2px); box-shadow:0 14px 26px -18px rgba(20,40,80,.4); }
.cam-card.is-pend{ border-color:var(--dx-clean);
  background:linear-gradient(180deg, var(--dx-clean-mist), var(--dx-surface) 62%);
  box-shadow:0 0 0 1px color-mix(in srgb, var(--dx-clean) 30%, transparent) inset, 0 12px 24px -18px color-mix(in srgb, var(--dx-clean) 55%, transparent); }
.cam-card.is-salida{ border-color:color-mix(in srgb, var(--dx-occ) 34%, var(--dx-line)); }
.cam-num{ font-family:var(--dx-serif); font-weight:700; font-size:1.55rem; line-height:1; color:var(--dx-brand); }
.cam-tipo{ font-size:.74rem; color:var(--dx-ink-faint); margin-top:4px; }
.cam-badge{ display:inline-flex; align-items:center; gap:6px; padding:5px 11px; border-radius:999px; font-size:.72rem; font-weight:700; width:fit-content; }
.cam-badge i{ font-size:.72rem; }
.cam-badge.b-pend{ background:var(--dx-clean-soft); color:var(--dx-clean-deep); }
.cam-badge.b-occ{ background:var(--dx-occ-soft); color:var(--dx-occ); }
.cam-badge.b-done{ background:var(--dx-success-soft); color:var(--dx-success-2); }
.cam-badge.b-maint{ background:var(--dx-slate-soft); color:var(--dx-slate); }
.cam-card form{ margin-top:auto; }

/* ── Botones ── */
.cam-btn{ position:relative; overflow:hidden; display:inline-flex; align-items:center; justify-content:center; gap:8px;
  width:100%; min-height:48px; border:0; border-radius:12px; cursor:pointer; font-family:inherit; font-size:.9rem; font-weight:700;
  transition:transform .15s var(--dx-ease), box-shadow .18s var(--dx-ease), filter .18s var(--dx-ease), background .18s var(--dx-ease); }
.cam-btn:active{ transform:scale(.98); }
.cam-btn:focus-visible{ outline:2px solid var(--dx-brand); outline-offset:2px; }
.cam-btn.done{ color:#fff; background:linear-gradient(135deg, var(--dx-success), var(--dx-success-2));
  box-shadow:0 12px 22px -12px rgba(30,158,99,.7); }
.cam-btn.done:hover{ filter:brightness(1.05); box-shadow:0 16px 28px -12px rgba(30,158,99,.8); }
.cam-btn.pend{ background:var(--dx-surface); color:var(--dx-clean-deep); border:1.5px solid color-mix(in srgb, var(--dx-clean) 40%, #fff); }
.cam-btn.pend:hover{ background:var(--dx-clean-soft); }
.cam-btn__shine{ position:absolute; inset:0 auto 0 0; width:42%; pointer-events:none;
  background:linear-gradient(100deg, transparent, rgba(255,255,255,.55), transparent); transform:translateX(-160%) skewX(-18deg); }
.cam-btn.done:hover .cam-btn__shine{ transition:transform .7s ease; transform:translateX(330%) skewX(-18deg); }

/* ── Estancia en tarjeta ocupada: huésped + salida ── */
.cam-stay{ display:flex; flex-direction:column; gap:6px; padding:9px 11px; border-radius:var(--dx-radius-sm);
  background:var(--dx-surface-warm); border:1px solid var(--dx-line); }
.cam-stay__row{ display:flex; align-items:center; gap:7px; min-width:0; font-size:.78rem; color:var(--dx-ink-soft); }
.cam-stay__row > i{ width:14px; flex:0 0 auto; text-align:center; color:var(--dx-ink-faint); font-size:.74rem; }
.cam-stay__row span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cam-stay__guest{ font-weight:600; color:var(--dx-ink); }
.cam-stay__out{ font-weight:700; color:var(--dx-ink-soft); }
.cam-stay__out--hoy{ color:var(--dx-occ); } .cam-stay__out--hoy > i{ color:var(--dx-occ); }
.cam-stay__out--pronto{ color:#8A6A1F; } .cam-stay__out--pronto > i{ color:var(--dx-gold); }

/* ── Meta de limpieza en tarjeta: personal asignado / fecha programada ── */
.cam-meta{ display:flex; flex-wrap:wrap; gap:6px; }
.cam-chip{ display:inline-flex; align-items:center; gap:5px; max-width:100%; padding:4px 10px; border-radius:999px;
  font-size:.7rem; font-weight:600; background:var(--dx-ivory); color:var(--dx-ink-soft); }
.cam-chip i{ font-size:.66rem; color:var(--dx-ink-faint); flex:0 0 auto; }
.cam-chip span{ overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cam-chip--staff{ background:var(--dx-clean-soft); color:var(--dx-clean-deep); }
.cam-chip--staff i{ color:var(--dx-clean); }
.cam-chip--unassigned{ background:var(--dx-slate-soft); color:var(--dx-slate); }
.cam-chip--unassigned i{ color:var(--dx-slate); }
.cam-chip--fecha{ background:#FBF3E2; color:#8A6A1F; }
.cam-chip--fecha i{ color:var(--dx-gold); }
.cam-chip--salida{ background:var(--dx-occ-soft); color:var(--dx-occ); }
.cam-chip--salida i{ color:var(--dx-occ); }

/* ── Acciones de tarjeta ── */
.cam-foot{ margin-top:auto; display:flex; flex-direction:column; gap:8px; }
.cam-foot form{ margin:0; }
.cam-btn.mini{ min-height:44px; font-size:.82rem; background:transparent; color:var(--dx-ink-soft);
  border:1.5px dashed var(--dx-line); }
.cam-btn.mini:hover{ color:var(--dx-clean-deep); border-color:var(--dx-clean); background:var(--dx-clean-mist); }

/* ── Modales del tablero (quién limpió / programar limpieza) ── */
.cam-modal{ position:fixed; inset:0; z-index:120; display:flex; align-items:flex-end; justify-content:center;
  background:rgba(18,22,34,.5); -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px);
  opacity:0; transition:opacity .22s var(--dx-ease); }
.cam-modal.is-open{ opacity:1; }
.cam-modal.hidden{ display:none; }
.cam-modal__panel{ width:100%; max-width:520px; max-height:88vh; display:flex; flex-direction:column; overflow:hidden;
  background:var(--dx-surface); border-radius:22px 22px 0 0; box-shadow:0 -18px 44px -20px rgba(20,40,80,.55);
  transform:translateY(24px); transition:transform .24s var(--dx-ease); }
.cam-modal.is-open .cam-modal__panel{ transform:translateY(0); }
@media (min-width:640px){
  .cam-modal{ align-items:center; padding:20px; }
  .cam-modal__panel{ border-radius:22px; transform:translateY(12px) scale(.98); }
  .cam-modal.is-open .cam-modal__panel{ transform:translateY(0) scale(1); }
}
.cam-modal__head{ position:relative; padding:20px 54px 14px 20px; color:#fff;
  background:linear-gradient(135deg, var(--dx-clean-deep), var(--dx-clean) 60%, var(--dx-clean-bright)); }
.cam-modal__head h2{ margin:0; font-family:var(--dx-serif); font-weight:700; font-size:1.45rem; line-height:1.1; color:#fff; }
.cam-modal__sub{ margin:4px 0 0; font-size:.8rem; color:rgba(255,255,255,.88); }
.cam-modal__close{ position:absolute; top:14px; right:14px; width:34px; height:34px; display:grid; place-items:center;
  border:1px solid rgba(255,255,255,.3); border-radius:10px; background:rgba(255,255,255,.14); color:#fff; cursor:pointer; }
.cam-modal__close:active{ transform:scale(.94); }
.cam-modal__body{ padding:14px 16px; overflow-y:auto; flex:1 1 auto; }
.cam-modal__label{ display:block; margin:0 0 6px; font-size:.78rem; font-weight:700; color:var(--dx-ink-soft); }
.cam-modal__fecha{ width:100%; min-height:46px; padding:10px 12px; margin-bottom:12px; font-family:inherit; font-size:.92rem;
  color:var(--dx-ink); background:var(--dx-surface-warm); border:1.5px solid var(--dx-line); border-radius:12px; }
.cam-modal__fecha:focus{ outline:2px solid var(--dx-clean); outline-offset:1px; }
.cam-persona{ position:relative; display:flex; align-items:center; gap:11px; padding:11px 12px; margin-bottom:8px; cursor:pointer;
  background:var(--dx-surface); border:1.5px solid var(--dx-line); border-radius:13px;
  transition:border-color .15s var(--dx-ease), background .15s var(--dx-ease); }
.cam-persona:hover{ border-color:var(--dx-clean); background:var(--dx-clean-mist); }
.cam-persona input{ position:absolute; opacity:0; width:1px; height:1px; pointer-events:none; }
.cam-persona__check{ flex:0 0 auto; width:22px; height:22px; display:grid; place-items:center; border-radius:7px;
  border:1.5px solid var(--dx-line); background:#fff; color:#fff; transition:all .16s var(--dx-ease); }
.cam-persona__check i{ font-size:.62rem; opacity:0; transform:scale(.4); transition:all .16s var(--dx-ease); }
.cam-persona:has(input:checked){ border-color:var(--dx-clean); background:var(--dx-clean-soft); }
.cam-persona:has(input:checked) .cam-persona__check{ border-color:var(--dx-clean); background:var(--dx-clean); }
.cam-persona:has(input:checked) .cam-persona__check i{ opacity:1; transform:scale(1); }
.cam-persona__nombre{ font-size:.92rem; font-weight:600; color:var(--dx-ink); min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cam-persona__rol{ margin-left:auto; flex:0 0 auto; font-size:.68rem; font-weight:600; color:var(--dx-ink-faint);
  background:var(--dx-ivory); padding:3px 8px; border-radius:999px; }
.cam-persona--none{ border-style:dashed; }
.cam-persona--none .cam-persona__nombre{ color:var(--dx-ink-soft); }
.cam-persona--none:hover{ border-color:var(--dx-ink-faint); background:var(--dx-surface-warm); }
.cam-persona--none:has(input:checked){ border-color:var(--dx-ink-faint); background:var(--dx-slate-soft); }
.cam-persona--none:has(input:checked) .cam-persona__check{ border-color:var(--dx-slate); background:var(--dx-slate); }
.cam-modal__error{ margin:2px 2px 0; padding:10px 12px; border-radius:11px; font-size:.8rem; font-weight:600;
  color:#B0352B; background:#FBE9E7; border:1px solid #F0B9B2; }
.cam-modal__error.hidden{ display:none; }
.cam-modal__foot{ display:flex; gap:10px; padding:12px 16px; padding-bottom:max(12px, env(safe-area-inset-bottom));
  border-top:1px solid var(--dx-line); background:var(--dx-surface-warm); }
.cam-modal__foot .cam-btn{ flex:1; }
.cam-btn.ghost{ background:transparent; color:var(--dx-ink-soft); border:1.5px solid var(--dx-line); flex:0 0 auto; padding:0 18px; width:auto; }
.cam-btn.ghost:hover{ background:var(--dx-surface-warm); color:var(--dx-ink); }

/* ── Vacío ── */
.cam-empty{ text-align:center; padding:44px 16px; color:var(--dx-ink-soft); }
.cam-empty i{ display:block; margin-bottom:10px; font-size:1.9rem; color:var(--dx-ink-faint); }
.cam-empty p{ margin:0; font-size:.9rem; }

/* ── Animaciones ── */
@keyframes camTwinkle{ 0%,100%{ transform:scale(.7) rotate(0); opacity:.6; } 50%{ transform:scale(1) rotate(90deg); opacity:1; } }
@keyframes camPop{ 0%{ transform:scale(0); } 60%{ transform:scale(1.15); } 100%{ transform:scale(1); } }
@keyframes camRise{ 0%{ transform:translateY(0) scale(.6); opacity:0; } 15%{ opacity:.6; } 80%{ opacity:.45; } 100%{ transform:translateY(-150px) scale(1); opacity:0; } }

/* ── Responsive ── */
@media (max-width:560px){
  .cam-inner{ padding:14px 12px 80px; }
  .cam h1{ font-size:1.65rem; }
  .cam-emblem{ width:46px; height:46px; }
  .cam-stat__n{ font-size:1.5rem; }
  .cam-grid{ grid-template-columns:1fr; }
}

/* ── Accesibilidad: reduce-motion ── */
@media (prefers-reduced-motion: reduce){
  .cam-emblem__spark, .cam-flash.ok i, .cam-done i, .cam-bubble{ animation:none !important; }
  .cam-bubble{ display:none; }
  .cam-btn.done .cam-btn__shine{ display:none; }
  .cam *{ transition-duration:.01ms !important; }
}
</style>

<div class="cam">
  <div class="cam-inner">
    <div class="cam-hero">
        <div class="cam-bubbles" aria-hidden="true">
            <span class="cam-bubble b1"></span><span class="cam-bubble b2"></span><span class="cam-bubble b3"></span><span class="cam-bubble b4"></span><span class="cam-bubble b5"></span>
        </div>
        <span class="cam-emblem" aria-hidden="true">
            <i class="fas fa-broom"></i>
            <span class="cam-emblem__spark"></span>
        </span>
        <div class="cam-hero__t">
            <h1>Limpieza</h1>
            <p class="sub">Cada sección te dice qué hacer. Empieza por las que están por limpiar.</p>
        </div>
    </div>

    <?php if ($mensaje = get_mensaje()): ?>
        <?php $flashOk = ($mensaje['tipo'] ?? 'info') !== 'error'; ?>
        <div class="cam-flash <?= $flashOk ? 'ok' : 'err' ?>">
            <i class="fas <?= $flashOk ? 'fa-circle-check' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
            <span><?= $mensaje['texto'] ?? '' ?></span>
        </div>
    <?php endif; ?>

    <div class="cam-board">
        <div class="cam-stats">
            <div class="cam-stat cam-stat--pend">
                <span class="cam-stat__n"><?= (int) $porLimpiar ?></span>
                <span class="cam-stat__l">Por limpiar</span>
            </div>
            <div class="cam-stat cam-stat--done">
                <span class="cam-stat__n"><?= (int) $camLimpias ?></span>
                <span class="cam-stat__l">Listas</span>
            </div>
            <div class="cam-stat">
                <span class="cam-stat__n"><?= count($salidasHoy) ?></span>
                <span class="cam-stat__l">Salidas hoy</span>
            </div>
        </div>

        <?php if ($camTodoAlDia): ?>
            <div class="cam-done">
                <i class="fas fa-circle-check" aria-hidden="true"></i>
                <span>¡Todo al día! No hay habitaciones por limpiar.</span>
            </div>
        <?php else: ?>
            <div class="cam-progress-row">
                <span>Progreso del turno</span>
                <span><strong><?= (int) $camPct ?>%</strong> listas</span>
            </div>
            <div class="cam-progress" role="progressbar" aria-label="Progreso de limpieza del turno" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $camPct ?>">
                <span style="width: <?= (int) $camPct ?>%;"></span>
            </div>
        <?php endif; ?>
    </div>

    <?php
    // Agrupamos los cuartos por lo que necesitan; el orden global (por prioridad
    // y número) se conserva dentro de cada balde.
    $camBuckets = ['limpieza' => [], 'ocupada' => [], 'disponible' => [], 'mantenimiento' => []];
    foreach ($habitaciones as $__h) {
        $__e = (string) $__h['estado'];
        $camBuckets[isset($camBuckets[$__e]) ? $__e : 'mantenimiento'][] = $__h;
    }
    $camSecciones = [
        ['key' => 'limpieza',      'mod' => 'pend',  'tag' => 'section', 'ico' => 'fa-broom',              't' => 'Por limpiar',      'hint' => 'Termina y toca “Ya quedó limpia”.'],
        ['key' => 'ocupada',       'mod' => 'occ',   'tag' => 'section', 'ico' => 'fa-bed',                't' => 'Ocupadas',         'hint' => 'En uso. Programa su limpieza para cuando salga el huésped.'],
        ['key' => 'disponible',    'mod' => 'done',  'tag' => 'details', 'ico' => 'fa-circle-check',       't' => 'Listas',           'hint' => 'Al día. Márcala por limpiar solo si se volvió a ensuciar.'],
        ['key' => 'mantenimiento', 'mod' => 'maint', 'tag' => 'section', 'ico' => 'fa-screwdriver-wrench', 't' => 'En mantenimiento', 'hint' => 'Fuera de servicio por ahora.'],
    ];
    foreach ($camSecciones as $sec):
        $lista = $camBuckets[$sec['key']];
        if (empty($lista)) { continue; }
        $esDetails = $sec['tag'] === 'details';
    ?>
    <<?= $esDetails ? 'details' : 'section' ?> class="cam-section cam-section--<?= $sec['mod'] ?>"<?= $esDetails && $porLimpiar === 0 ? ' open' : '' ?>>
        <<?= $esDetails ? 'summary' : 'div' ?> class="cam-section__head">
            <span class="cam-section__ico"><i class="fas <?= $sec['ico'] ?>" aria-hidden="true"></i></span>
            <div class="cam-section__meta">
                <div class="cam-section__t"><?= $camSafe($sec['t']) ?><span class="cam-section__count"><?= count($lista) ?></span></div>
                <div class="cam-section__hint"><?= $camSafe($sec['hint']) ?></div>
            </div>
            <?php if ($esDetails): ?><i class="fas fa-chevron-down cam-section__chev" aria-hidden="true"></i><?php endif; ?>
        </<?= $esDetails ? 'summary' : 'div' ?>>
        <div class="cam-grid">
        <?php foreach ($lista as $hab): ?>
            <?php
            $id = (int) $hab['id'];
            $estado = (string) $hab['estado'];
            $esSalidaHoy = isset($salidasHoy[$id]);
            $cardMod = $estado === 'limpieza' ? ' is-pend' : (($esSalidaHoy && $estado !== 'disponible') ? ' is-salida' : '');

            // Limpieza activa del cuarto: personal asignado y/o fecha programada.
            $tarea = $tareasLimpieza[$id] ?? null;
            $asignadosCsv = $tarea ? implode(',', $tarea['trabajador_ids']) : '';
            $asignadosNombres = $tarea ? trim((string) $tarea['trabajador_nombres']) : '';
            $fechaProg = '';
            $fechaProgLabel = '';
            if ($tarea && (string) $tarea['fecha_programada'] !== '') {
                $ts = strtotime((string) $tarea['fecha_programada']);
                if ($ts) {
                    $fechaProg = date('Y-m-d', $ts);
                    if ($fechaProg > $camHoy) {
                        $fechaProgLabel = $fechaProg === $camManana ? 'mañana' : date('d/m', $ts);
                    }
                }
            }
            ?>
            <div class="cam-card<?= $cardMod ?>">
                <div class="cam-head">
                    <div class="cam-num">Hab <?= $camSafe($hab['numero']) ?></div>
                    <div class="cam-tipo"><?= $camSafe(ucfirst((string) $hab['tipo'])) ?><?= $hab['piso'] !== null && $hab['piso'] !== '' ? ' · Piso ' . $camSafe($hab['piso']) : '' ?></div>
                </div>

                <?php if ($estado === 'limpieza'): ?>
                    <span class="cam-badge b-pend"><i class="fas fa-broom" aria-hidden="true"></i>Por limpiar</span>
                <?php elseif ($estado === 'ocupada'): ?>
                    <span class="cam-badge b-occ"><i class="fas fa-bed" aria-hidden="true"></i>Ocupada</span>
                <?php elseif ($estado === 'disponible'): ?>
                    <span class="cam-badge b-done"><i class="fas fa-check" aria-hidden="true"></i>Limpia</span>
                <?php else: ?>
                    <span class="cam-badge b-maint"><i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>Mantenimiento</span>
                <?php endif; ?>

                <?php if ($estado === 'ocupada' && (isset($ocupacion[$id]) || $esSalidaHoy)): ?>
                    <?php
                    $occ = $ocupacion[$id] ?? null;
                    $salidaLbl = '';
                    $salidaTono = '';
                    if ($occ && (string) $occ['fecha_salida'] !== '') {
                        $sts = strtotime((string) $occ['fecha_salida']);
                        $sdia = $sts ? date('Y-m-d', $sts) : '';
                        $conHora = $horaSalidaFmt !== '' ? ' · ' . $horaSalidaFmt : '';
                        if ($sdia === $camHoy) {
                            $salidaLbl = 'Sale hoy' . $conHora; $salidaTono = ' cam-stay__out--hoy';
                        } elseif ($sdia === $camManana) {
                            $salidaLbl = 'Sale mañana' . $conHora; $salidaTono = ' cam-stay__out--pronto';
                        } elseif ($sdia !== '') {
                            $noches = (int) floor((strtotime($sdia) - strtotime($camHoy)) / 86400);
                            $salidaLbl = 'Sale el ' . date('d/m', $sts) . ($noches > 0 ? ' · ' . $noches . ' noche' . ($noches === 1 ? '' : 's') : '');
                        }
                    } elseif ($esSalidaHoy) {
                        $salidaLbl = 'Sale hoy' . ($horaSalidaFmt !== '' ? ' · ' . $horaSalidaFmt : ''); $salidaTono = ' cam-stay__out--hoy';
                    }
                    ?>
                    <div class="cam-stay">
                        <?php if ($occ && trim((string) $occ['huesped']) !== ''): ?>
                            <div class="cam-stay__row cam-stay__guest" title="<?= $camSafe($occ['huesped']) ?>">
                                <i class="fas fa-user" aria-hidden="true"></i><span><?= $camSafe($occ['huesped']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($salidaLbl !== ''): ?>
                            <div class="cam-stay__row cam-stay__out<?= $salidaTono ?>">
                                <i class="fas fa-right-from-bracket" aria-hidden="true"></i><span><?= $camSafe($salidaLbl) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php $recienDesocupada = ($estado === 'limpieza' && $esSalidaHoy); ?>
                <?php if ($asignadosNombres !== '' || $fechaProgLabel !== '' || $recienDesocupada): ?>
                <div class="cam-meta">
                    <?php if ($recienDesocupada): ?>
                        <span class="cam-chip cam-chip--salida"><i class="fas fa-right-from-bracket" aria-hidden="true"></i><span>Recién desocupada</span></span>
                    <?php endif; ?>
                    <?php if ($fechaProgLabel !== ''): ?>
                        <span class="cam-chip cam-chip--fecha"><i class="fas fa-calendar-day" aria-hidden="true"></i><span>Programada <?= $camSafe($fechaProgLabel) ?></span></span>
                    <?php endif; ?>
                    <?php if ($asignadosNombres !== ''): ?>
                        <span class="cam-chip cam-chip--staff" title="<?= $camSafe($asignadosNombres) ?>"><i class="fas fa-user" aria-hidden="true"></i><span><?= $camSafe($asignadosNombres) ?></span></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php if ($estado === 'limpieza'): ?>
                    <div class="cam-foot">
                        <?php if ($camHayPersonal): ?>
                            <button type="button" class="cam-btn done js-cam-marcar"
                                    data-hab-id="<?= $id ?>" data-hab-numero="Hab <?= $camSafe($hab['numero']) ?>"
                                    data-asignados="<?= $camSafe($asignadosCsv) ?>">
                                <span class="cam-btn__shine" aria-hidden="true"></span>
                                <i class="fas fa-check" aria-hidden="true"></i>Ya quedó limpia
                            </button>
                            <button type="button" class="cam-btn mini js-cam-prog"
                                    data-hab-id="<?= $id ?>" data-hab-numero="Hab <?= $camSafe($hab['numero']) ?>"
                                    data-asignados="<?= $camSafe($asignadosCsv) ?>"
                                    data-fecha="<?= $camSafe($fechaProg !== '' ? $fechaProg : $camHoy) ?>">
                                <i class="fas fa-user-plus" aria-hidden="true"></i><?= $asignadosNombres !== '' ? 'Cambiar personal' : 'Asignar personal' ?>
                            </button>
                        <?php else: ?>
                            <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>"
                                  data-ms-confirm data-ms-type="success" data-ms-icon="check"
                                  data-ms-title="¿Ya quedó limpia?"
                                  data-ms-msg="Confirma que la Hab <?= $camSafe($hab['numero']) ?> quedó lista para recibir huéspedes."
                                  data-ms-ok="Sí, quedó limpia">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="disponible">
                                <button type="submit" class="cam-btn done">
                                    <span class="cam-btn__shine" aria-hidden="true"></span>
                                    <i class="fas fa-check" aria-hidden="true"></i>Ya quedó limpia
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($estado === 'ocupada' && $camHayPersonal): ?>
                    <div class="cam-foot">
                        <button type="button" class="cam-btn mini js-cam-prog"
                                data-hab-id="<?= $id ?>" data-hab-numero="Hab <?= $camSafe($hab['numero']) ?>"
                                data-asignados="<?= $camSafe($asignadosCsv) ?>"
                                data-fecha="<?= $camSafe($fechaProg !== '' ? $fechaProg : $camManana) ?>">
                            <i class="fas fa-calendar-plus" aria-hidden="true"></i><?= $fechaProgLabel !== '' ? 'Reprogramar limpieza' : 'Programar limpieza' ?>
                        </button>
                    </div>
                <?php elseif ($estado === 'disponible'): ?>
                    <div class="cam-foot">
                        <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>"
                              data-ms-confirm data-ms-type="info" data-ms-icon="info"
                              data-ms-title="¿Marcar por limpiar?"
                              data-ms-msg="La Hab <?= $camSafe($hab['numero']) ?> pasará a la lista de habitaciones por limpiar."
                              data-ms-ok="Sí, marcar">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="limpieza">
                            <button type="submit" class="cam-btn mini">
                                <i class="fas fa-broom" aria-hidden="true"></i>Marcar por limpiar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </<?= $esDetails ? 'details' : 'section' ?>>
    <?php endforeach; ?>

    <?php
    // ── Areas del hotel: mismas tarjetas, mismo modal de personal ──
    $camAreaSecs = [
        ['lista' => $areasPorLimpiar, 'estado' => 'limpieza',   'tag' => 'section', 'mod' => 'pend', 'ico' => 'fa-broom',        't' => 'Áreas por limpiar', 'hint' => 'Alberca, lobby, jardín... termina y toca “Ya quedó limpia”.'],
        ['lista' => $areasListas,     'estado' => 'disponible', 'tag' => 'details', 'mod' => 'done', 'ico' => 'fa-circle-check', 't' => 'Áreas listas',      'hint' => 'Al día. Márcala por limpiar solo si se volvió a ensuciar.'],
    ];
    foreach ($camAreaSecs as $asec):
        if (empty($asec['lista'])) { continue; }
        $aEsDetails = $asec['tag'] === 'details';
    ?>
    <<?= $aEsDetails ? 'details' : 'section' ?> class="cam-section cam-section--<?= $asec['mod'] ?>"<?= $aEsDetails && empty($areasPorLimpiar) ? ' open' : '' ?>>
        <<?= $aEsDetails ? 'summary' : 'div' ?> class="cam-section__head">
            <span class="cam-section__ico"><i class="fas <?= $asec['ico'] ?>" aria-hidden="true"></i></span>
            <div class="cam-section__meta">
                <div class="cam-section__t"><?= $camSafe($asec['t']) ?><span class="cam-section__count"><?= count($asec['lista']) ?></span></div>
                <div class="cam-section__hint"><?= $camSafe($asec['hint']) ?></div>
            </div>
            <?php if ($aEsDetails): ?><i class="fas fa-chevron-down cam-section__chev" aria-hidden="true"></i><?php endif; ?>
        </<?= $aEsDetails ? 'summary' : 'div' ?>>
        <div class="cam-grid">
        <?php foreach ($asec['lista'] as $ar): ?>
            <?php
            $aid = (int) $ar['id'];
            $aEstado = (string) $ar['estado'];
            $tm = $tiposArea[$ar['tipo'] ?? 'otra'] ?? ['label' => 'Área', 'icono' => 'fa-location-dot'];
            $aPiso = ($ar['piso'] === null || $ar['piso'] === '') ? 'Planta baja o exterior' : 'Piso ' . (int) $ar['piso'];
            $aTarea = $tareasLimpiezaAreas[$aid] ?? null;
            $aAsignadosCsv = $aTarea ? implode(',', $aTarea['trabajador_ids']) : '';
            $aAsignadosNombres = $aTarea ? trim((string) $aTarea['trabajador_nombres']) : '';
            ?>
            <div class="cam-card<?= $aEstado === 'limpieza' ? ' is-pend' : '' ?>">
                <div class="cam-head">
                    <div class="cam-num" style="font-size:1.2rem;line-height:1.2;word-break:break-word;"><?= $camSafe($ar['nombre']) ?></div>
                    <div class="cam-tipo"><i class="fas <?= $camSafe($tm['icono']) ?>" aria-hidden="true"></i> <?= $camSafe($tm['label']) ?> · <?= $camSafe($aPiso) ?></div>
                </div>

                <?php if ($aEstado === 'limpieza'): ?>
                    <span class="cam-badge b-pend"><i class="fas fa-broom" aria-hidden="true"></i>Por limpiar</span>
                <?php else: ?>
                    <span class="cam-badge b-done"><i class="fas fa-check" aria-hidden="true"></i>Limpia</span>
                <?php endif; ?>

                <?php if ($aEstado === 'limpieza' && $aTarea): ?>
                    <div class="cam-meta" aria-label="Asignación de limpieza">
                        <?php if ($aAsignadosNombres !== ''): ?>
                            <span class="cam-chip cam-chip--staff" title="Asignada a <?= $camSafe($aAsignadosNombres) ?>">
                                <i class="fas fa-user-check" aria-hidden="true"></i>
                                <span>Asignada a <?= $camSafe($aAsignadosNombres) ?></span>
                            </span>
                        <?php else: ?>
                            <span class="cam-chip cam-chip--unassigned">
                                <i class="fas fa-user-slash" aria-hidden="true"></i>
                                <span>Sin personal asignado</span>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($aEstado === 'limpieza'): ?>
                    <div class="cam-foot">
                        <?php if ($camHayPersonal): ?>
                            <button type="button" class="cam-btn done js-cam-marcar"
                                    data-hab-id="<?= $aid ?>" data-hab-numero="<?= $camSafe($ar['nombre']) ?>"
                                    data-asignados="<?= $camSafe($aAsignadosCsv) ?>" data-action-base="<?= url('camarista/area/marcar') ?>">
                                <span class="cam-btn__shine" aria-hidden="true"></span>
                                <i class="fas fa-check" aria-hidden="true"></i>Ya quedó limpia
                            </button>
                        <?php else: ?>
                            <form method="POST" action="<?= url('camarista/area/marcar/' . $aid) ?>"
                                  data-ms-confirm data-ms-type="success" data-ms-icon="check"
                                  data-ms-title="¿Ya quedó limpia?"
                                  data-ms-msg="Confirma que <?= $camSafe($ar['nombre']) ?> quedó lista."
                                  data-ms-ok="Sí, quedó limpia">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="disponible">
                                <button type="submit" class="cam-btn done">
                                    <span class="cam-btn__shine" aria-hidden="true"></span>
                                    <i class="fas fa-check" aria-hidden="true"></i>Ya quedó limpia
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="cam-foot">
                        <form method="POST" action="<?= url('camarista/area/marcar/' . $aid) ?>"
                              data-ms-confirm data-ms-type="info" data-ms-icon="info"
                              data-ms-title="¿Marcar por limpiar?"
                              data-ms-msg="<?= $camSafe($ar['nombre']) ?> pasará a la lista de áreas por limpiar."
                              data-ms-ok="Sí, marcar">
                            <?= csrf_field() ?>
                            <input type="hidden" name="estado" value="limpieza">
                            <button type="submit" class="cam-btn mini">
                                <i class="fas fa-broom" aria-hidden="true"></i>Marcar por limpiar
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </<?= $aEsDetails ? 'details' : 'section' ?>>
    <?php endforeach; ?>

    <?php if (empty($habitaciones) && empty($areas)): ?>
        <div class="cam-empty">
            <i class="fas fa-broom" aria-hidden="true"></i>
            <p>No hay habitaciones activas para mostrar.</p>
        </div>
    <?php endif; ?>

    <?php if ($camHayPersonal): ?>
    <!-- Modal: ¿quién hizo la limpieza? (obligatorio al marcar como limpia) -->
    <div id="camModalMarcar" class="cam-modal hidden" role="dialog" aria-modal="true" aria-labelledby="camMarcarTitulo">
        <div class="cam-modal__panel">
            <div class="cam-modal__head">
                <h2 id="camMarcarTitulo">¿Quién hizo la limpieza?</h2>
                <p class="cam-modal__sub"><span data-cam-numero></span> · elige a una o más personas.</p>
                <button type="button" class="cam-modal__close" data-cam-cerrar aria-label="Cerrar"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <form method="POST" action="" data-action-base="<?= url('camarista/marcar') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="estado" value="disponible">
                <div class="cam-modal__body">
                    <?php foreach ($personal as $p): ?>
                    <label class="cam-persona">
                        <input type="checkbox" name="trabajador_ids[]" value="<?= (int) $p['id'] ?>">
                        <span class="cam-persona__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <span class="cam-persona__nombre"><?= $camSafe($p['nombre']) ?></span>
                        <?php if ((string) $p['rol'] !== ''): ?><span class="cam-persona__rol"><?= $camSafe($p['rol']) ?></span><?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                    <label class="cam-persona cam-persona--none">
                        <input type="checkbox" name="sin_personal" value="1" class="js-cam-sin">
                        <span class="cam-persona__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <span class="cam-persona__nombre">Sin registrar personal</span>
                    </label>
                    <p class="cam-modal__error hidden" data-cam-error>Selecciona quién hizo la limpieza o marca "Sin registrar personal".</p>
                </div>
                <div class="cam-modal__foot">
                    <button type="button" class="cam-btn ghost" data-cam-cerrar>Cancelar</button>
                    <button type="submit" class="cam-btn done">
                        <span class="cam-btn__shine" aria-hidden="true"></span>
                        <i class="fas fa-check" aria-hidden="true"></i>Confirmar limpieza
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: programar limpieza (fecha + personal, incluso con el cuarto ocupado) -->
    <div id="camModalProg" class="cam-modal hidden" role="dialog" aria-modal="true" aria-labelledby="camProgTitulo">
        <div class="cam-modal__panel">
            <div class="cam-modal__head">
                <h2 id="camProgTitulo">Programar limpieza</h2>
                <p class="cam-modal__sub"><span data-cam-numero></span> · elige fecha y quién se encargará.</p>
                <button type="button" class="cam-modal__close" data-cam-cerrar aria-label="Cerrar"><i class="fas fa-times" aria-hidden="true"></i></button>
            </div>
            <form method="POST" action="" data-action-base="<?= url('camarista/programar') ?>">
                <?= csrf_field() ?>
                <div class="cam-modal__body">
                    <label class="cam-modal__label" for="camProgFecha">¿Para cuándo?</label>
                    <input type="date" id="camProgFecha" name="fecha" class="cam-modal__fecha" required
                           min="<?= $camHoy ?>" max="<?= date('Y-m-d', strtotime('+60 days')) ?>" value="<?= $camHoy ?>">
                    <span class="cam-modal__label">¿Quién se encargará?</span>
                    <?php foreach ($personal as $p): ?>
                    <label class="cam-persona">
                        <input type="checkbox" name="trabajador_ids[]" value="<?= (int) $p['id'] ?>">
                        <span class="cam-persona__check" aria-hidden="true"><i class="fas fa-check"></i></span>
                        <span class="cam-persona__nombre"><?= $camSafe($p['nombre']) ?></span>
                        <?php if ((string) $p['rol'] !== ''): ?><span class="cam-persona__rol"><?= $camSafe($p['rol']) ?></span><?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                    <p class="cam-modal__error hidden" data-cam-error>Selecciona al menos una persona para programar la limpieza.</p>
                </div>
                <div class="cam-modal__foot">
                    <button type="button" class="cam-btn ghost" data-cam-cerrar>Cancelar</button>
                    <button type="submit" class="cam-btn done">
                        <span class="cam-btn__shine" aria-hidden="true"></span>
                        <i class="fas fa-calendar-check" aria-hidden="true"></i>Programar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var marcarModal = document.getElementById('camModalMarcar');
        var progModal = document.getElementById('camModalProg');
        if (!marcarModal && !progModal) return;

        function abrir(modal, datos) {
            if (!modal) return;
            var form = modal.querySelector('form');
            // Habitaciones usan el data-action-base del form; las areas pasan el
            // suyo por boton (endpoint /camarista/area/marcar).
            var base = datos.actionBase || form.getAttribute('data-action-base');
            form.action = base + '/' + datos.habId;

            modal.querySelectorAll('[data-cam-numero]').forEach(function (el) { el.textContent = datos.numero || ''; });

            // Preseleccionar al personal ya asignado a la limpieza activa.
            var pre = (datos.asignados || '').split(',').map(function (s) { return parseInt(s, 10) || 0; }).filter(function (v) { return v > 0; });
            form.querySelectorAll('input[name="trabajador_ids[]"]').forEach(function (chk) {
                chk.checked = pre.indexOf(parseInt(chk.value, 10)) !== -1;
            });
            var sin = form.querySelector('.js-cam-sin');
            if (sin) sin.checked = false;
            var err = modal.querySelector('[data-cam-error]');
            if (err) err.classList.add('hidden');

            modal.classList.remove('hidden');
            void modal.offsetWidth;
            modal.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function cerrar(modal) {
            modal.classList.remove('is-open');
            setTimeout(function () {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }, 230);
        }

        document.querySelectorAll('.js-cam-marcar').forEach(function (btn) {
            btn.addEventListener('click', function () {
                abrir(marcarModal, { habId: btn.getAttribute('data-hab-id'), numero: btn.getAttribute('data-hab-numero'), asignados: btn.getAttribute('data-asignados'), actionBase: btn.getAttribute('data-action-base') });
            });
        });

        document.querySelectorAll('.js-cam-prog').forEach(function (btn) {
            btn.addEventListener('click', function () {
                abrir(progModal, { habId: btn.getAttribute('data-hab-id'), numero: btn.getAttribute('data-hab-numero'), asignados: btn.getAttribute('data-asignados') });
                var fecha = progModal ? progModal.querySelector('input[name="fecha"]') : null;
                if (fecha) fecha.value = btn.getAttribute('data-fecha') || fecha.min;
            });
        });

        [marcarModal, progModal].forEach(function (modal) {
            if (!modal) return;
            modal.addEventListener('click', function (e) { if (e.target === modal) cerrar(modal); });
            modal.querySelectorAll('[data-cam-cerrar]').forEach(function (b) { b.addEventListener('click', function () { cerrar(modal); }); });

            var form = modal.querySelector('form');
            var sin = form.querySelector('.js-cam-sin');
            var checks = form.querySelectorAll('input[name="trabajador_ids[]"]');

            // "Sin registrar personal" y las personas son excluyentes.
            if (sin) {
                sin.addEventListener('change', function () {
                    if (sin.checked) checks.forEach(function (c) { c.checked = false; });
                });
            }
            checks.forEach(function (c) {
                c.addEventListener('change', function () {
                    if (c.checked && sin) sin.checked = false;
                });
            });

            // No dejar pasar sin la elección: personas o "sin registrar personal".
            form.addEventListener('submit', function (e) {
                var alguno = Array.prototype.some.call(checks, function (c) { return c.checked; });
                var sinOk = !!(sin && sin.checked);
                if (!alguno && !sinOk) {
                    e.preventDefault();
                    var err = modal.querySelector('[data-cam-error]');
                    if (err) {
                        err.classList.remove('hidden');
                        err.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            });
        });

        // Cerrar con Escape.
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            [marcarModal, progModal].forEach(function (modal) {
                if (modal && !modal.classList.contains('hidden')) cerrar(modal);
            });
        });
    })();
    </script>
    <?php endif; ?>
  </div>
</div>
