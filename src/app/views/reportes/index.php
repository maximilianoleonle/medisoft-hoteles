<?php
// Centro de Reportes — rediseño boutique (Claude Design reportes.html, lenguaje Deleite Sereno).
$repTieneDistribucion = !function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('reportes_distribucion');
$repTieneTablero = !function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('tablero_ejecutivo');
$repAreas = 4 + ($repTieneDistribucion ? 1 : 0) + ($repTieneTablero ? 2 : 0);
$repRol = function_exists('user_role') ? trim((string) user_role()) : '';
if ($repRol !== '') {
    $repRol = function_exists('mb_convert_case') ? mb_convert_case($repRol, MB_CASE_TITLE, 'UTF-8') : ucfirst($repRol);
} else {
    $repRol = 'Gerencia';
}
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

/* ── Tokens (white-label: cromado derivado de --brand-*) ── */
.reportes-view {
    --rp-brand: var(--brand-action-bg, var(--brand-primary, #1B2746));
    --rp-brand-2: var(--brand-action-bg-hover, color-mix(in srgb, var(--rp-brand) 86%, #0B1220));
    --rp-on-brand: var(--brand-action-text, #FFFFFF);
    --rp-gold: var(--brand-accent, #B0883F);
    --rp-gold-soft: color-mix(in srgb, var(--rp-gold) 55%, var(--rp-on-brand));
    --rp-gold-line: color-mix(in srgb, var(--rp-gold) 32%, #F0E7D6);
    --rp-gold-bg: color-mix(in srgb, var(--rp-gold) 14%, #FFFDF7);
    /* Superficies serenas */
    --rp-ivory: #F6F2EA;
    --rp-ivory-2: #FBF8F2;
    --rp-surface: #FFFFFF;
    --rp-line: #ECE5D8;
    /* Tinta */
    --rp-heading: var(--brand-text, #1B2746);
    --rp-ink: var(--brand-text, #1B2746);
    --rp-ink-2: #3E4A66;
    --rp-muted: #6C7689;
    /* Ritmo */
    --rp-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --rp-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --rp-ease: cubic-bezier(.22,1,.36,1);
    --rp-shadow-xs: 0 1px 2px rgba(27,39,70,.05);
    --rp-shadow: 0 2px 8px rgba(27,39,70,.045), 0 12px 28px rgba(27,39,70,.055);
    color: var(--rp-ink);
    font-family: var(--rp-sans);
    opacity: 0;
    transition: opacity .35s ease;
}
.reportes-view.loaded { opacity: 1; }

.reportes-view.rep-bg {
    min-height: 100vh;
    background: linear-gradient(180deg, var(--rp-ivory-2), var(--rp-ivory) 62%);
}

.rp-hic svg, .rp-tag svg, .rp-ic svg, .rp-list svg, .rp-btn svg { fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }

/* ── Cabecera ── */
.rp-head { display: flex; align-items: flex-start; gap: 16px; }
.rp-hic {
    width: 56px; height: 56px; border-radius: 16px; flex: none; position: relative;
    display: grid; place-items: center;
    background: linear-gradient(150deg, var(--rp-brand), var(--rp-brand-2));
    color: var(--rp-gold-soft);
    box-shadow: 0 14px 26px -18px color-mix(in srgb, var(--rp-brand) 72%, #111827);
}
.rp-hic svg { width: 28px; height: 28px; }
.rp-spark {
    position: absolute; top: -4px; right: -4px; width: 13px; height: 13px;
    background: linear-gradient(135deg, #FFFDF5, var(--rp-gold));
    clip-path: polygon(50% 0, 60% 40%, 100% 50%, 60% 60%, 50% 100%, 40% 60%, 0 50%, 40% 40%);
    animation: rpTwinkle 2.6s ease-in-out infinite;
}
@keyframes rpTwinkle {
    0%, 100% { transform: scale(.7) rotate(0); opacity: .55; }
    50% { transform: scale(1) rotate(90deg); opacity: 1; }
}
.reportes-view .rp-head h1 {
    font-family: var(--rp-serif);
    font-size: clamp(1.7rem, 3vw, 2.375rem);
    font-weight: 600; line-height: 1; margin: 0;
    color: var(--rp-heading);
    letter-spacing: -.01em;
}
.reportes-view .rp-sub { color: var(--rp-muted); font-size: .875rem; font-weight: 500; margin-top: 8px; }
.rp-tags { margin-left: auto; display: flex; gap: 10px; align-items: center; flex: none; flex-wrap: wrap; }
.rp-tag {
    display: inline-flex; align-items: center; gap: 7px;
    font-size: .78rem; font-weight: 700; padding: 9px 14px; border-radius: 999px;
    background: var(--rp-surface); border: 1px solid var(--rp-line);
    color: var(--rp-ink-2); box-shadow: var(--rp-shadow-xs); white-space: nowrap;
}
.rp-tag svg { width: 15px; height: 15px; color: var(--rp-gold); }

/* ── Título de sección con hairline dorada ── */
.rp-sect { margin: 30px 0 0; }
.reportes-view .rp-sect h2 {
    font-family: var(--rp-serif); font-size: 1.5rem; font-weight: 600;
    color: var(--rp-heading); margin: 0;
    display: flex; align-items: center; gap: 12px;
}
.rp-sect h2::after { content: ''; flex: 1; height: 1px; background: var(--rp-gold-line); }
.reportes-view .rp-sect p { color: var(--rp-muted); font-size: .845rem; font-weight: 500; margin-top: 6px; }

/* ── Grid de tarjetas ── */
.rp-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 20px; margin-top: 20px; }
@media (max-width: 1279px) { .rp-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (max-width: 1023px) { .rp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

.rp-card {
    display: flex; flex-direction: column; position: relative;
    background: var(--rp-surface); border: 1px solid var(--rp-line);
    border-radius: 20px; box-shadow: var(--rp-shadow-xs); padding: 22px;
    transition: transform .18s var(--rp-ease), box-shadow .18s ease, border-color .18s ease;
    animation: rpUp .5s var(--rp-ease) both;
}
.rp-card:hover { transform: translateY(-4px); box-shadow: var(--rp-shadow); border-color: var(--rp-gold-line); }
@keyframes rpUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.rp-card:nth-child(1) { animation-delay: .05s; }
.rp-card:nth-child(2) { animation-delay: .11s; }
.rp-card:nth-child(3) { animation-delay: .17s; }
.rp-card:nth-child(4) { animation-delay: .23s; }
.rp-card:nth-child(5) { animation-delay: .29s; }
.rp-card:nth-child(6) { animation-delay: .35s; }
.rp-card:nth-child(7) { animation-delay: .41s; }

.rp-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.rp-ic {
    width: 52px; height: 52px; border-radius: 15px; flex: none;
    display: grid; place-items: center;
    background: var(--cb); color: var(--cc);
    transition: transform .18s var(--rp-ease);
}
.rp-ic svg { width: 25px; height: 25px; }
.rp-card:hover .rp-ic { transform: translateY(-2px); }
.rp-cat {
    font-size: .625rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    padding: 5px 11px; border-radius: 999px; white-space: nowrap;
    background: var(--cb); color: var(--cc);
}
.reportes-view .rp-card h3 {
    font-family: var(--rp-serif); font-size: 1.25rem; font-weight: 600;
    color: var(--rp-heading); margin: 18px 0 0; line-height: 1.15;
}
.reportes-view .rp-desc { font-size: .8125rem; color: var(--rp-muted); line-height: 1.55; margin-top: 8px; }
.rp-list { list-style: none; margin: 16px 0 0; padding: 0; display: flex; flex-direction: column; gap: 9px; }
.rp-list li { display: flex; align-items: center; gap: 10px; font-size: .8125rem; font-weight: 600; color: var(--rp-ink-2); }
.rp-list li svg { width: 16px; height: 16px; color: var(--cc); flex: none; }

/* ── Acciones ── */
.rp-acts { margin-top: auto; padding-top: 18px; display: flex; flex-direction: column; gap: 9px; }
.rp-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: 9px;
    font-family: var(--rp-sans); font-size: .845rem; font-weight: 700;
    padding: 12px 16px; border-radius: 12px; min-height: 44px;
    border: 1px solid transparent; text-decoration: none; cursor: pointer;
    transition: transform .14s var(--rp-ease), box-shadow .14s ease, background .14s ease, border-color .14s ease, color .14s ease;
    position: relative; overflow: hidden;
}
.rp-btn svg { width: 16px; height: 16px; stroke-width: 2; }
.rp-btn.primary { background: var(--rp-brand); color: var(--rp-on-brand); box-shadow: 0 10px 20px -14px color-mix(in srgb, var(--rp-brand) 75%, #111827); }
.rp-btn.primary:hover { background: var(--rp-brand-2); transform: translateY(-1px); }
.rp-btn.ghost { background: var(--rp-surface); color: var(--rp-ink-2); border-color: var(--rp-line); }
.rp-btn.ghost:hover { border-color: var(--rp-gold-line); color: var(--rp-heading); }
.rp-btn:focus-visible { outline: 3px solid color-mix(in srgb, var(--rp-brand) 30%, transparent); outline-offset: 2px; }
.rp-btn__shine {
    position: absolute; inset: 0 auto 0 0; width: 40%; pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent);
    transform: translateX(-160%) skewX(-18deg);
}
.rp-btn.primary:hover .rp-btn__shine { transition: transform .65s ease; transform: translateX(320%) skewX(-18deg); }

/* ── Móvil compacto (patrón de la casa, ≤680px) ── */
@media (max-width: 680px) {
    .rp-head { gap: .6rem; flex-wrap: wrap; }
    .rp-hic { width: 40px; height: 40px; border-radius: 12px; }
    .rp-hic svg { width: 20px; height: 20px; }
    .rp-spark { width: 10px; height: 10px; top: -3px; right: -3px; }
    .reportes-view .rp-head h1 { font-size: 1.4rem; }
    .reportes-view .rp-sub { font-size: .72rem; margin-top: 4px; line-height: 1.3; }
    .rp-tags { margin-left: 0; width: 100%; gap: .4rem; }
    .rp-tag { font-size: .62rem; padding: 6px 10px; gap: 5px; }
    .rp-tag svg { width: 12px; height: 12px; }

    .rp-sect { margin-top: 1.1rem; }
    .reportes-view .rp-sect h2 { font-size: 1.08rem; gap: .55rem; }
    .reportes-view .rp-sect p { font-size: .72rem; line-height: 1.25; }

    .rp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: .55rem; margin-top: .8rem; }
    .rp-card { padding: .66rem; border-radius: .9rem; }
    .rp-top { gap: .4rem; }
    .rp-ic { width: 2rem; height: 2rem; border-radius: .58rem; }
    .rp-ic svg { width: 1rem; height: 1rem; }
    .rp-cat { max-width: 5.4rem; padding: .16rem .45rem; font-size: .5rem; letter-spacing: .05em; overflow: hidden; text-overflow: ellipsis; }
    .reportes-view .rp-card h3 {
        margin-top: .5rem; font-size: .95rem; line-height: 1.12;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .reportes-view .rp-desc {
        margin-top: .28rem; font-size: .62rem; line-height: 1.24;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .rp-list { display: none; }
    .rp-acts { padding-top: .55rem; gap: .34rem; }
    .rp-btn { min-height: 2.15rem; padding: .45rem .5rem; gap: .34rem; border-radius: .62rem; font-size: .62rem; white-space: nowrap; }
    .rp-btn svg { width: .72rem; height: .72rem; }
}

@media (max-width: 380px) {
    .rp-grid { gap: .45rem; }
    .rp-card { padding: .56rem; }
    .rp-cat { max-width: 4.6rem; font-size: .48rem; }
    .reportes-view .rp-card h3 { font-size: .88rem; }
    .reportes-view .rp-desc { display: none; }
    .rp-btn { min-height: 2rem; padding-inline: .42rem; font-size: .58rem; }
}

/* ── Movimiento reducido ── */
@media (prefers-reduced-motion: reduce) {
    .reportes-view .rp-spark { animation: none !important; display: none; }
    .reportes-view .rp-btn__shine { display: none; }
    .reportes-view .rp-card { animation: none !important; }
    .reportes-view, .reportes-view * { transition-duration: .01ms !important; }
    .reportes-view { opacity: 1; }
}
</style>

<svg style="display:none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <symbol id="rp-i-trend" viewBox="0 0 24 24"><path d="M3 17l5-5 3.5 3.5L20 7"/><path d="M15 7h5v5"/></symbol>
  <symbol id="rp-i-user" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.4"/><path d="M5 20a7 7 0 0 1 14 0"/></symbol>
  <symbol id="rp-i-box" viewBox="0 0 24 24"><path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5 12 12l8.5-4.5M12 12v9"/></symbol>
  <symbol id="rp-i-check" viewBox="0 0 24 24"><path d="M12 3a9 9 0 1 0 9 9 9 9 0 0 0-9-9zm-1.2 12.5L7 11.7l1.1-1.1 2.7 2.7 4.9-4.9 1.1 1.1z" fill="currentColor" stroke="none"/></symbol>
  <symbol id="rp-i-scale" viewBox="0 0 24 24"><path d="M12 3v18M7 21h10M12 6l-6 2 3 5a3 3 0 0 1-6 0l3-5M12 6l6 2-3 5a3 3 0 0 0 6 0l-3-5M6 8l12-4"/></symbol>
  <symbol id="rp-i-map" viewBox="0 0 24 24"><path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14M15 6v14"/></symbol>
  <symbol id="rp-i-trophy" viewBox="0 0 24 24"><path d="M7 4h10v4a5 5 0 0 1-10 0V4zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3M9 20h6M12 14v6"/></symbol>
  <symbol id="rp-i-wrench" viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.2L3 17.8 6.2 21l6.3-6.3a4 4 0 0 0 5.2-5.4l-2.5 2.5-2.3-2.3z"/></symbol>
  <symbol id="rp-i-cal" viewBox="0 0 24 24"><rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9h18M8 2.5v4M16 2.5v4"/></symbol>
  <symbol id="rp-i-broom" viewBox="0 0 24 24"><path d="M19 5 14 10M9.5 14.5 4 20m0 0h4l1-4 5-2 4-4-3-3-4 4-2 5z"/></symbol>
  <symbol id="rp-i-share" viewBox="0 0 24 24"><path d="M14 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-4M14 9l7-7M15 2h6v6"/></symbol>
  <symbol id="rp-i-shield" viewBox="0 0 24 24"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3z"/><path d="M9 12l2 2 4-4"/></symbol>
  <symbol id="rp-i-globe" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.5 3.5 6 3.5 9s-1 6.5-3.5 9c-2.5-2.5-3.5-6-3.5-9s1-6.5 3.5-9z"/></symbol>
  <symbol id="rp-i-brief" viewBox="0 0 24 24"><rect x="3" y="7" width="18" height="13" rx="2.5"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M3 12h18"/></symbol>
  <symbol id="rp-i-gauge" viewBox="0 0 24 24"><path d="M4 18a8 8 0 1 1 16 0M12 14l4-4"/><circle cx="12" cy="18" r="1.3" fill="currentColor" stroke="none"/></symbol>
  <symbol id="rp-i-report" viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6z"/><path d="M8 12h8M8 15h8M8 18h5"/></symbol>
  <symbol id="rp-i-dash2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2.5"/><path d="M3 9h18M9 21V9"/></symbol>
</svg>

<div class="reportes-view rep-bg">
    <div class="container mx-auto px-5 sm:px-7 pt-7 pb-12 max-w-7xl">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>

        <!-- Cabecera -->
        <div class="rp-head">
            <div class="rp-hic">
                <svg><use href="#rp-i-trend"/></svg>
                <span class="rp-spark" aria-hidden="true"></span>
            </div>
            <div>
                <h1>Centro de Reportes y Análisis</h1>
                <p class="rp-sub">Consulta los reportes activos del hotel con filtros propios en cada módulo.</p>
            </div>
            <div class="rp-tags">
                <span class="rp-tag"><svg><use href="#rp-i-user"/></svg> <?= htmlspecialchars($repRol) ?></span>
                <span class="rp-tag"><svg><use href="#rp-i-box"/></svg> <?= (int) $repAreas ?> áreas activas</span>
            </div>
        </div>

        <!-- Sección -->
        <div class="rp-sect">
            <h2>Reportes disponibles</h2>
            <p>Elige el área a revisar; cada reporte conserva sus filtros, tablas y exportaciones existentes.</p>
        </div>

        <!-- Tarjetas -->
        <div class="rp-grid">

            <!-- Financiero: limpieza azul semántico -->
            <div class="rp-card" style="--cc:#2F77E0;--cb:#E6EFFC;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-scale"/></svg></div>
                    <span class="rp-cat">Financiero</span>
                </div>
                <h3>Análisis de Ingresos vs Gastos</h3>
                <p class="rp-desc">Compara movimientos del hotel por período para revisar balance y utilidad.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Ingresos y gastos por categoría</li>
                    <li><svg><use href="#rp-i-check"/></svg> Evolución diaria</li>
                    <li><svg><use href="#rp-i-check"/></svg> Utilidad del período</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/ingresos-gastos') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-report"/></svg> Abrir reporte</a>
                </div>
            </div>

            <!-- Geográfico: verde disponible/éxito -->
            <div class="rp-card" style="--cc:#1E9E63;--cb:#E7F4EC;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-map"/></svg></div>
                    <span class="rp-cat">Geográfico</span>
                </div>
                <h3>Procedencia de Huéspedes</h3>
                <p class="rp-desc">Revisa de dónde llegan los huéspedes y cómo se distribuye su actividad.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Distribución por estados</li>
                    <li><svg><use href="#rp-i-check"/></svg> Top geográfico</li>
                    <li><svg><use href="#rp-i-check"/></svg> Evolución por origen</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/procedencia') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-globe"/></svg> Abrir reporte</a>
                </div>
            </div>

            <!-- Performance: ámbar mantenimiento -->
            <div class="rp-card" style="--cc:#C2841C;--cb:#FAF0DC;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-trophy"/></svg></div>
                    <span class="rp-cat">Performance</span>
                </div>
                <h3>Habitaciones más Rentables</h3>
                <p class="rp-desc">Identifica rendimiento por habitación, tipo y ocupación del período.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Ranking por ingresos</li>
                    <li><svg><use href="#rp-i-check"/></svg> Ocupación y precio promedio</li>
                    <li><svg><use href="#rp-i-check"/></svg> Análisis por tipo</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/habitaciones-rentables') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-trophy"/></svg> Abrir reporte</a>
                </div>
            </div>

            <!-- Operativo: slate ocupada -->
            <div class="rp-card" style="--cc:#5B6B86;--cb:#ECEFF4;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-wrench"/></svg></div>
                    <span class="rp-cat">Operativo</span>
                </div>
                <h3>Mantenimiento de Habitaciones</h3>
                <p class="rp-desc">Consulta trabajos, costos y prioridades sin mezclarlo con el flujo de caja.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Tipos y prioridades</li>
                    <li><svg><use href="#rp-i-check"/></svg> Costos y duración</li>
                    <li><svg><use href="#rp-i-check"/></svg> Responsables y registro</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/mantenimiento') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-wrench"/></svg> Abrir reporte</a>
                    <a href="<?= url('reportes/mantenimiento-programado') ?>" class="rp-btn ghost"><svg><use href="#rp-i-cal"/></svg> Ver programados</a>
                    <a href="<?= url('reportes/limpieza') ?>" class="rp-btn ghost"><svg><use href="#rp-i-broom"/></svg> Ver limpieza</a>
                </div>
            </div>

            <?php if ($repTieneDistribucion): ?>
            <!-- Seguridad: cian información -->
            <div class="rp-card" style="--cc:#0E96B8;--cb:#E2F2F6;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-share"/></svg></div>
                    <span class="rp-cat">Seguridad</span>
                </div>
                <h3>Exportaciones y Links Seguros</h3>
                <p class="rp-desc">Administra PDFs y reportes ya generados para compartirlos con expiración y revocación.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Exportaciones guardadas</li>
                    <li><svg><use href="#rp-i-check"/></svg> Vencimiento de links</li>
                    <li><svg><use href="#rp-i-check"/></svg> Control de accesos</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/links') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-shield"/></svg> Ver exportaciones</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($repTieneTablero): ?>
            <!-- Gerencial: violeta por llegar -->
            <div class="rp-card" style="--cc:#5A57D2;--cb:#ECEBFB;">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-brief"/></svg></div>
                    <span class="rp-cat">Gerencial</span>
                </div>
                <h3>Reporte Gerencial Diario</h3>
                <p class="rp-desc">Resume ingresos, ocupación, agenda, caja y pendientes críticos del día.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Vista ejecutiva diaria</li>
                    <li><svg><use href="#rp-i-check"/></svg> Pendientes accionables</li>
                    <li><svg><use href="#rp-i-check"/></svg> Notificación automática</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/gerencial-diario') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-globe"/></svg> Abrir reporte</a>
                </div>
            </div>

            <!-- Ejecutivo: oro de marca -->
            <div class="rp-card" style="--cc:var(--rp-gold);--cb:var(--rp-gold-bg);">
                <div class="rp-top">
                    <div class="rp-ic"><svg><use href="#rp-i-gauge"/></svg></div>
                    <span class="rp-cat">Ejecutivo</span>
                </div>
                <h3>Tablero Ejecutivo</h3>
                <p class="rp-desc">Consolida KPIs operativos, financieros, inventario, tareas y documentos.</p>
                <ul class="rp-list">
                    <li><svg><use href="#rp-i-check"/></svg> Solo lectura</li>
                    <li><svg><use href="#rp-i-check"/></svg> Filtros por período</li>
                    <li><svg><use href="#rp-i-check"/></svg> Alertas por área</li>
                </ul>
                <div class="rp-acts">
                    <a href="<?= url('reportes/ejecutivo') ?>" class="rp-btn primary"><span class="rp-btn__shine" aria-hidden="true"></span><svg><use href="#rp-i-dash2"/></svg> Abrir tablero</a>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /rp-grid -->
    </div>
</div><!-- /reportes-view -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    var view = document.querySelector('.reportes-view');
    if (view) setTimeout(function () { view.classList.add('loaded'); }, 60);
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
