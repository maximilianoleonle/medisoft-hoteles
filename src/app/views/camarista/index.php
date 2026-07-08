<?php
/**
 * Tablero movil de limpieza (bloque camarista). Tarjetas grandes por
 * habitacion con accion de un toque; primero lo que hay que limpiar.
 */
$habitaciones = $habitaciones ?? [];
$salidasHoy = $salidasHoy ?? [];

$camSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

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
?>

<style>
/* ════════════════════════════════════════════════════════════════
   Tablero de camarista — lenguaje "Deleite Sereno".
   Base serena (marfil cálido + serif de despliegue) + microrecompensas
   (progreso del turno, brillo del CTA, lift de tarjeta, destello).
   White-label: cromado desde --brand-*; estados = colores semánticos.
   ════════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap');

.cam{
  --dx-brand: var(--brand-primary, #1B2746);
  --dx-gold: var(--brand-accent, #BD9441);
  /* Identidad de limpieza: azul fresco = --c-cleaning del sistema (igual que el modal) */
  --dx-clean:#2F77E0; --dx-clean-deep:#1E5FBF; --dx-clean-bright:#5A9BF2; --dx-clean-soft:#E6EFFC; --dx-clean-mist:#F3F8FF;
  --dx-success:#1E9E63; --dx-success-2:#17864F; --dx-success-soft:#E7F4EC;
  --dx-occ:#C2603C; --dx-occ-soft:#F8EAE1;
  --dx-slate:#64748B; --dx-slate-soft:#EEF1F4;
  --dx-surface:#FFFFFF; --dx-surface-warm:#FCFAF5; --dx-ivory:#F6F2EA;
  --dx-line:#E7E1D4; --dx-line-cool:#D5E3F6; --dx-ink:#20293A; --dx-ink-soft:#5C6675; --dx-ink-faint:#8B94A3;
  --dx-radius:20px; --dx-radius-md:15px; --dx-radius-sm:11px;
  --dx-serif:'Cormorant Garamond', Georgia, 'Times New Roman', serif;
  --dx-ease:cubic-bezier(.22,1,.36,1);
  width: 100%; min-height: 100vh;
  font-family: 'DM Sans','Outfit',system-ui,-apple-system,sans-serif; color: var(--dx-ink);
  background:
    radial-gradient(1100px 460px at 88% -12%, color-mix(in srgb, var(--dx-clean) 9%, transparent), transparent 60%),
    linear-gradient(180deg, var(--dx-clean-mist), #FBF8F2 300px) !important;
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
            <p class="sub">Toca el botón de cada habitación cuando termines. Arriba van las urgentes.</p>
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

    <div class="cam-grid">
        <?php foreach ($habitaciones as $hab): ?>
            <?php
            $id = (int) $hab['id'];
            $estado = (string) $hab['estado'];
            $esSalidaHoy = isset($salidasHoy[$id]);
            $cardMod = $estado === 'limpieza' ? ' is-pend' : (($esSalidaHoy && $estado !== 'disponible') ? ' is-salida' : '');
            ?>
            <div class="cam-card<?= $cardMod ?>">
                <div class="cam-head">
                    <div class="cam-num">Hab <?= $camSafe($hab['numero']) ?></div>
                    <div class="cam-tipo"><?= $camSafe(ucfirst((string) $hab['tipo'])) ?><?= $hab['piso'] !== null && $hab['piso'] !== '' ? ' · Piso ' . $camSafe($hab['piso']) : '' ?></div>
                </div>

                <?php if ($estado === 'limpieza'): ?>
                    <span class="cam-badge b-pend"><i class="fas fa-broom" aria-hidden="true"></i>Por limpiar</span>
                <?php elseif ($estado === 'ocupada'): ?>
                    <span class="cam-badge b-occ"><i class="fas fa-bed" aria-hidden="true"></i>Ocupada<?= $esSalidaHoy ? ' · sale hoy' : '' ?></span>
                <?php elseif ($estado === 'disponible'): ?>
                    <span class="cam-badge b-done"><i class="fas fa-check" aria-hidden="true"></i>Limpia</span>
                <?php else: ?>
                    <span class="cam-badge b-maint"><i class="fas fa-screwdriver-wrench" aria-hidden="true"></i>Mantenimiento</span>
                <?php endif; ?>

                <?php if ($estado === 'limpieza'): ?>
                    <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="disponible">
                        <button type="submit" class="cam-btn done">
                            <span class="cam-btn__shine" aria-hidden="true"></span>
                            <i class="fas fa-check" aria-hidden="true"></i>Ya quedó limpia
                        </button>
                    </form>
                <?php elseif ($estado === 'disponible'): ?>
                    <form method="POST" action="<?= url('camarista/marcar/' . $id) ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="estado" value="limpieza">
                        <button type="submit" class="cam-btn pend">
                            <i class="fas fa-broom" aria-hidden="true"></i>Marcar por limpiar
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($habitaciones)): ?>
        <div class="cam-empty">
            <i class="fas fa-broom" aria-hidden="true"></i>
            <p>No hay habitaciones activas para mostrar.</p>
        </div>
    <?php endif; ?>
  </div>
</div>
