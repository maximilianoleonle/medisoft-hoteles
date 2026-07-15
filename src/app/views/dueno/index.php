<?php
/**
 * Modo Dueno — resumen remoto de solo lectura (bloque modo_dueno).
 *
 * Una sola columna, movil primero. Para este rol la vista ES la app:
 * el CSS de abajo retira el cromado del layout (sidebar, headers, barra
 * inferior) SOLO en esta pagina (body.page-dueno); ninguna otra vista
 * se ve afectada. Lenguaje visual: Deleite Sereno (base marfil serena,
 * serif de despliegue, cero jerga, cero botones que escriban nada).
 *
 * Cada tarjeta se pinta SOLO si su bloque llego del controlador (permiso
 * + modulo): la vista se ve completa y armonica con cualquier subconjunto.
 */
$resumen = is_array($resumen ?? null) ? $resumen : [];
$duSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Nombre de pila del dueno (sin inventar honorificos).
$duNombre = trim((string) (function_exists('user_name') ? user_name() : ''));
$duNombrePila = $duNombre !== '' ? explode(' ', $duNombre)[0] : '';

// Fecha de hoy en espanol, sin depender del locale del servidor.
$duDias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$duMeses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$duFecha = $duDias[(int) date('w')] . ' ' . (int) date('j') . ' de ' . $duMeses[(int) date('n')];

$duHotelNombre = (string) ($resumen['hotel_nombre'] ?? '');
$duSaludo = (string) ($resumen['saludo'] ?? 'Hola');
$duSemaforo = $resumen['semaforo'] ?? ['score' => null, 'estado' => 'verde', 'etiqueta' => 'Día tranquilo', 'detalle' => '', 'desglose' => []];
$duScore = $duSemaforo['score'] ?? null;
$duDesglose = is_array($duSemaforo['desglose'] ?? null) ? $duSemaforo['desglose'] : [];
$duEsBuenDia = ($duSemaforo['estado'] ?? '') === 'verde' && $duScore !== null && $duScore >= 80;
$duDinero = $resumen['dinero'] ?? null;
$duHotel = $resumen['hotel'] ?? null;
$duGuardian = $resumen['guardian'] ?? null;
$duResenas = $resumen['resenas'] ?? null;

// Logo del hotel si su configuracion tiene uno.
$duLogo = null;
if (function_exists('current_hotel_branding') && function_exists('hotel_branding_asset_url')) {
    try {
        $duBranding = current_hotel_branding();
        $duLogo = hotel_branding_asset_url($duBranding['logo_url'] ?? null);
    } catch (Throwable $e) {
        $duLogo = null;
    }
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;700&display=swap');

/* ── El Modo Dueno retira el cromado del layout SOLO en esta pagina ── */
body.page-dueno #sidebar,
body.page-dueno .mobile-header-modern,
body.page-dueno .hotel-header,
body.page-dueno #hotel-bottom-nav,
body.page-dueno .scroll-progress { display: none !important; }
body.page-dueno .main-content { padding: 0 !important; margin: 0 !important; }
/* El body reserva 64px para el header movil fijo; aqui ese header no existe. */
body.page-dueno { padding-top: 0 !important; }

/* ── Vista ── */
.du {
    /* Marca (se re-tematiza por hotel) */
    --du-brand: var(--brand-primary, #1B2746);
    --du-gold: var(--brand-accent, #BD9441);
    /* Semanticos (fijos por significado) */
    --du-success: #1E9E63; --du-success-soft: #E7F4EC;
    --du-warn: #C2841C; --du-warn-soft: #FBF3E2;
    --du-error: #D64539;
    /* Base serena */
    --du-surface: #FFFFFF; --du-warm: #FCFAF5; --du-ivory: #F6F2EA;
    --du-line: #E7E1D4; --du-ink: #20293A; --du-ink-soft: #5C6675; --du-ink-faint: #8B94A3;
    --du-radius: 22px; --du-radius-md: 15px;
    --du-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --du-ease: cubic-bezier(.22, 1, .36, 1);

    min-height: 100dvh;
    font-family: 'DM Sans', 'Outfit', system-ui, -apple-system, sans-serif;
    font-size: 18px;
    line-height: 1.55;
    color: var(--du-ink);
    background:
        radial-gradient(900px 420px at 85% -10%, color-mix(in srgb, var(--du-brand) 7%, transparent), transparent 60%),
        linear-gradient(180deg, var(--du-ivory), #FBF8F2 320px);
    padding: max(20px, env(safe-area-inset-top)) 18px calc(40px + env(safe-area-inset-bottom));
}
.du-col { max-width: 560px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }

/* Encabezado: saludo + fecha + hotel */
.du-hola { padding: 10px 6px 2px; }
.du-hola-fecha { font-size: 15px; color: var(--du-ink-soft); letter-spacing: .01em; }
.du-hola-titulo { font-family: var(--du-serif); font-weight: 700; font-size: clamp(30px, 8vw, 38px); line-height: 1.12; margin-top: 2px; }
.du-hola-hotel { display: flex; align-items: center; gap: 10px; margin-top: 10px; color: var(--du-ink-soft); font-size: 16px; }
.du-hola-hotel img { width: 34px; height: 34px; border-radius: 10px; object-fit: cover; background: #fff; border: 1px solid var(--du-line); }

/* Tarjeta base */
.du-card {
    background: var(--du-surface);
    border: 1px solid var(--du-line);
    border-radius: var(--du-radius);
    padding: 22px 20px;
    box-shadow: 0 14px 34px -24px rgba(20, 40, 80, .35);
}
.du-kicker { font-size: 14px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--du-ink-faint); margin-bottom: 8px; }
.du-linea { display: flex; align-items: flex-start; gap: 10px; font-size: 18px; color: var(--du-ink-soft); margin-top: 10px; }
.du-linea i { width: 22px; text-align: center; margin-top: 4px; font-size: 15px; color: var(--du-ink-faint); }

/* Semaforo del dia */
.du-card[data-estado] { border-left: 6px solid var(--du-success); }
.du-card[data-estado="ambar"] { border-left-color: var(--du-warn); }
.du-sem-fila { display: flex; align-items: center; gap: 12px; }
.du-sem-dot { width: 16px; height: 16px; border-radius: 50%; flex: none; background: var(--du-success); box-shadow: 0 0 0 5px var(--du-success-soft); }
.du-card[data-estado="ambar"] .du-sem-dot { background: var(--du-warn); box-shadow: 0 0 0 5px var(--du-warn-soft); }
.du-sem-etiqueta { font-family: var(--du-serif); font-weight: 700; font-size: 30px; line-height: 1.15; }
.du-sem-detalle { margin-top: 8px; color: var(--du-ink-soft); font-size: 17px; }
.du-sem-score { margin-left: auto; align-self: flex-start; font-size: 16px; font-weight: 700; color: var(--du-ink-faint); background: var(--du-ivory); border-radius: 999px; padding: 4px 12px; }
/* Destello dorado: microrecompensa cuando el dia esta bien (la etiqueta manda). */
.du-spark { width: 15px; height: 15px; flex: none; background: linear-gradient(135deg, #fff, var(--du-gold)); clip-path: polygon(50% 0, 60% 40%, 100% 50%, 60% 60%, 50% 100%, 40% 60%, 0 50%, 40% 40%); animation: duTwinkle 2.6s ease-in-out infinite; }
@keyframes duTwinkle { 0%, 100% { transform: scale(.7) rotate(0); opacity: .55; } 50% { transform: scale(1) rotate(90deg); opacity: 1; } }
.du-sem-toggle { display: flex; align-items: center; gap: 9px; min-height: 48px; margin-top: 6px; padding: 0 4px; border: 0; background: none; cursor: pointer; font: inherit; font-size: 17px; font-weight: 700; color: var(--du-ink-soft); }
.du-sem-toggle:focus-visible { outline: 3px solid color-mix(in srgb, var(--du-brand) 40%, #fff); outline-offset: 2px; border-radius: 8px; }
.du-sem-toggle i { font-size: 13px; transition: transform .25s var(--du-ease); }
.du-sem-toggle[aria-expanded="true"] i { transform: rotate(180deg); }
.du-sem-desglose { margin: 4px 0 2px; padding: 0 0 0 2px; list-style: none; display: flex; flex-direction: column; gap: 9px; }
.du-sem-desglose[hidden] { display: none; }
.du-sem-desglose li { position: relative; padding-left: 22px; color: var(--du-ink-soft); font-size: 17px; }
.du-sem-desglose li::before { content: ''; position: absolute; left: 4px; top: .62em; width: 7px; height: 7px; border-radius: 50%; background: var(--du-line); }

/* Cifra protagonista (dinero) */
.du-cifra { font-family: var(--du-serif); font-weight: 700; font-size: clamp(34px, 9vw, 40px); line-height: 1.05; letter-spacing: -.01em; }
.du-sub { color: var(--du-ink-soft); font-size: 17px; margin-top: 6px; }
.du-caja-linea { display: flex; align-items: center; gap: 9px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--du-line); color: var(--du-ink-soft); font-size: 17px; }
.du-caja-dot { width: 9px; height: 9px; border-radius: 50%; flex: none; background: var(--du-ink-faint); }
.du-caja-linea[data-abierta="1"] .du-caja-dot { background: var(--du-success); }

/* Ocupacion */
.du-ocupa-texto { font-family: var(--du-serif); font-weight: 700; font-size: 27px; line-height: 1.2; }
.du-progress { height: 8px; border-radius: 999px; background: var(--du-ivory); overflow: hidden; margin-top: 12px; }
.du-progress > span { display: block; height: 100%; width: 0; border-radius: 999px; background: linear-gradient(90deg, var(--du-brand), color-mix(in srgb, var(--du-brand) 55%, #fff)); transition: width .7s var(--du-ease); }

/* Guardian */
.du-guard { display: flex; align-items: center; gap: 14px; }
.du-guard-check { width: 46px; height: 46px; border-radius: 50%; flex: none; display: grid; place-items: center; font-size: 20px; background: var(--du-success-soft); color: var(--du-success); animation: duPop .45s var(--du-ease) both; }
.du-card[data-guard="temas"] .du-guard-check { background: var(--du-warn-soft); color: var(--du-warn); }
.du-guard-texto { font-family: var(--du-serif); font-weight: 700; font-size: 26px; line-height: 1.2; }
.du-guard-detalle { color: var(--du-ink-soft); font-size: 16px; margin-top: 2px; }
.du-guard-link { display: inline-flex; align-items: center; gap: 8px; min-height: 48px; margin-top: 10px; padding: 0 4px; font-size: 17px; font-weight: 700; color: var(--du-warn); text-decoration: none; }
.du-guard-link:active { opacity: .7; }
@keyframes duPop { 0% { transform: scale(.55); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }

/* Resenas */
.du-estrellas { display: flex; align-items: baseline; gap: 12px; }
.du-estrellas-num { font-family: var(--du-serif); font-weight: 700; font-size: 38px; line-height: 1; }
.du-estrellas-iconos { color: var(--du-gold); font-size: 16px; letter-spacing: 2px; }
.du-estrellas-iconos .apagada { color: var(--du-line); }
.du-resena { margin-top: 12px; color: var(--du-ink-soft); font-size: 17px; font-style: italic; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

/* Pie: actualizar */
.du-pie { text-align: center; padding: 4px 0 8px; }
.du-reloj { font-size: 15px; color: var(--du-ink-faint); margin-bottom: 12px; }
.du-refrescar {
    position: relative; overflow: hidden;
    width: 100%; min-height: 56px; border: 0; border-radius: 16px; cursor: pointer;
    font: inherit; font-size: 18px; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, var(--du-brand), color-mix(in srgb, var(--du-brand) 80%, #000));
    box-shadow: 0 10px 24px -12px color-mix(in srgb, var(--du-brand) 70%, transparent);
    transition: transform .16s var(--du-ease), box-shadow .2s ease;
}
.du-refrescar:hover { transform: translateY(-1px); }
.du-refrescar:active { transform: translateY(0) scale(.98); }
.du-refrescar:focus-visible { outline: 3px solid color-mix(in srgb, var(--du-brand) 40%, #fff); outline-offset: 2px; }
.du-refrescar .du-shine { position: absolute; inset: 0 auto 0 0; width: 40%; pointer-events: none; background: linear-gradient(100deg, transparent, rgba(255,255,255,.45), transparent); transform: translateX(-160%) skewX(-18deg); }
.du-refrescar:hover .du-shine { transition: transform .7s ease; transform: translateX(320%) skewX(-18deg); }
.du-refrescar[disabled] { opacity: .7; cursor: wait; }
.du-refrescar i { margin-right: 9px; }
.du-refrescar.cargando i { animation: duGira 0.9s linear infinite; }
@keyframes duGira { to { transform: rotate(360deg); } }
.du-error { display: none; margin-top: 12px; font-size: 16px; color: var(--du-error); }
.du-error.visible { display: block; }

/* Modo oscuro (tokens propios remapeados) */
html[data-theme="dark"] .du {
    --du-surface: #211F1A; --du-warm: #1B1915; --du-ivory: #2A2822;
    --du-line: rgba(239, 233, 220, .13);
    --du-ink: #EFE9DC; --du-ink-soft: #B9B2A2; --du-ink-faint: #8A8478;
    --du-success-soft: rgba(30, 158, 99, .18);
    --du-warn-soft: rgba(194, 132, 28, .2);
    background:
        radial-gradient(900px 420px at 85% -10%, color-mix(in srgb, var(--du-brand) 12%, transparent), transparent 60%),
        linear-gradient(180deg, #171612, #131210 320px);
}
html[data-theme="dark"] .du-card { box-shadow: 0 14px 34px -24px rgba(0, 0, 0, .6); }
html[data-theme="dark"] .du-warn-text,
html[data-theme="dark"] .du-guard-link { color: #E3A63C; }

@media (prefers-reduced-motion: reduce) {
    .du * { transition-duration: .01ms !important; animation: none !important; }
    .du .du-shine { display: none; }
}
</style>

<div class="du" id="modoDueno" data-datos-url="<?= $duSafe(url('dueno/datos')) ?>">
    <div class="du-col">

        <!-- 1 · Saludo -->
        <header class="du-hola">
            <p class="du-hola-fecha"><?= $duSafe(ucfirst($duFecha)) ?></p>
            <h1 class="du-hola-titulo"><?= $duSafe($duSaludo) ?><?= $duNombrePila !== '' ? ', ' . $duSafe($duNombrePila) : '' ?></h1>
            <p class="du-hola-hotel">
                <?php if ($duLogo): ?><img src="<?= $duSafe($duLogo) ?>" alt="" aria-hidden="true"><?php endif; ?>
                <span><?= $duSafe($duHotelNombre) ?></span>
            </p>
        </header>

        <!-- 2 · ¿Como va el dia? (score determinista, Fase 3) -->
        <section class="du-card" data-estado="<?= $duSafe($duSemaforo['estado']) ?>" data-du-card="semaforo" aria-label="Cómo va el día">
            <p class="du-kicker">¿Cómo va el día?</p>
            <div class="du-sem-fila">
                <span class="du-sem-dot" aria-hidden="true"></span>
                <h2 class="du-sem-etiqueta" data-du="semaforo-etiqueta"><?= $duSafe($duSemaforo['etiqueta']) ?></h2>
                <span class="du-spark" data-du-spark aria-hidden="true" <?= $duEsBuenDia ? '' : 'hidden' ?>></span>
                <span class="du-sem-score" data-du="semaforo-score" <?= $duScore === null ? 'hidden' : '' ?>><?= $duScore !== null ? (int) $duScore : '' ?></span>
            </div>
            <p class="du-sem-detalle" data-du="semaforo-detalle"><?= $duSafe($duSemaforo['detalle']) ?></p>
            <button type="button" class="du-sem-toggle" data-du-sem-toggle aria-expanded="false" <?= empty($duDesglose) ? 'hidden' : '' ?>>
                <span data-du-toggle-texto>Ver el detalle</span>
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="du-sem-desglose" data-du-desglose hidden>
                <?php foreach ($duDesglose as $duLineaSem): ?>
                <li><?= $duSafe($duLineaSem) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <?php if ($duDinero !== null): ?>
        <!-- 3 · ¿Cuanto ha entrado hoy? -->
        <section class="du-card" data-du-card="dinero" aria-label="Cuánto ha entrado hoy">
            <p class="du-kicker">¿Cuánto ha entrado hoy?</p>
            <p class="du-cifra" data-du="dinero-ingresos"><?= $duSafe($duDinero['ingresos']) ?></p>
            <p class="du-sub" data-du="dinero-gastos"><?= $duSafe($duDinero['gastos_linea']) ?></p>
            <p class="du-caja-linea" data-du-caja data-abierta="<?= !empty($duDinero['caja_abierta']) ? '1' : '0' ?>">
                <span class="du-caja-dot" aria-hidden="true"></span>
                <span data-du="dinero-caja"><?= $duSafe($duDinero['caja_linea']) ?></span>
            </p>
        </section>
        <?php endif; ?>

        <?php if ($duHotel !== null): ?>
        <!-- 4 · ¿Como esta el hotel? -->
        <section class="du-card" data-du-card="hotel" aria-label="Cómo está el hotel">
            <p class="du-kicker">¿Cómo está el hotel?</p>
            <p class="du-ocupa-texto" data-du="hotel-ocupacion"><?= $duSafe($duHotel['ocupacion_texto']) ?></p>
            <div class="du-progress" role="img" aria-label="Ocupación: <?= (int) $duHotel['ocupacion_pct'] ?> por ciento">
                <span data-du-barra data-pct="<?= (int) $duHotel['ocupacion_pct'] ?>"></span>
            </div>
            <?php if ($duHotel['llegadas_linea'] !== null): ?>
            <p class="du-linea"><i class="fas fa-arrow-right-to-bracket" aria-hidden="true"></i><span data-du="hotel-llegadas"><?= $duSafe($duHotel['llegadas_linea']) ?></span></p>
            <?php endif; ?>
            <?php if ($duHotel['salidas_linea'] !== null): ?>
            <p class="du-linea"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i><span data-du="hotel-salidas"><?= $duSafe($duHotel['salidas_linea']) ?></span></p>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php if ($duGuardian !== null): ?>
        <!-- 5 · ¿Todo en orden? -->
        <section class="du-card" data-du-card="guardian" data-guard="<?= !empty($duGuardian['ok']) ? 'ok' : 'temas' ?>" aria-label="Todo en orden">
            <p class="du-kicker">¿Todo en orden?</p>
            <div class="du-guard">
                <span class="du-guard-check" aria-hidden="true"><i class="fas <?= !empty($duGuardian['ok']) ? 'fa-check' : 'fa-magnifying-glass' ?>" data-du-guard-icono></i></span>
                <div>
                    <h2 class="du-guard-texto" data-du="guardian-texto"><?= $duSafe($duGuardian['texto']) ?></h2>
                    <p class="du-guard-detalle" data-du="guardian-detalle"><?= $duSafe($duGuardian['detalle']) ?></p>
                </div>
            </div>
            <a class="du-guard-link" data-du-guard-link href="<?= $duSafe($duGuardian['url'] ?? '#') ?>" <?= empty($duGuardian['url']) ? 'hidden' : '' ?>>
                Ver de qué se trata <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        </section>
        <?php endif; ?>

        <?php if ($duResenas !== null): ?>
        <!-- 6 · ¿Que dicen los huespedes? -->
        <section class="du-card" data-du-card="resenas" aria-label="Qué dicen los huéspedes">
            <p class="du-kicker">¿Qué dicen los huéspedes?</p>
            <div class="du-estrellas">
                <?php if ($duResenas['promedio'] !== null): ?>
                <span class="du-estrellas-num" data-du="resenas-promedio"><?= $duSafe($duResenas['promedio']) ?></span>
                <?php endif; ?>
                <span class="du-estrellas-iconos" data-du-estrellas aria-label="<?= $duSafe($duResenas['promedio_linea']) ?>">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?= $i <= (int) $duResenas['estrellas'] ? '' : ' apagada' ?>" aria-hidden="true"></i>
                    <?php endfor; ?>
                </span>
            </div>
            <p class="du-resena" data-du="resenas-reciente"><?= $duSafe($duResenas['reciente']) ?></p>
        </section>
        <?php endif; ?>

        <!-- Pie: frescura del dato + actualizar -->
        <footer class="du-pie">
            <p class="du-reloj" aria-live="polite">Actualizado <span data-du-reloj data-desde="<?= (int) ($resumen['generado_en'] ?? time()) ?>">hace un momento</span></p>
            <button type="button" class="du-refrescar" data-du-refrescar>
                <span class="du-shine" aria-hidden="true"></span>
                <i class="fas fa-rotate" aria-hidden="true"></i>Actualizar
            </button>
            <p class="du-error" data-du-error role="alert">No pudimos actualizar. Revisa tu conexión e intenta de nuevo.</p>
        </footer>

    </div>
</div>

<script>
(function () {
    var raiz = document.getElementById('modoDueno');
    if (!raiz) { return; }

    // La barra de ocupacion se llena al cargar (recompensa serena, una vez).
    var barra = raiz.querySelector('[data-du-barra]');
    function pintarBarra() {
        if (barra) {
            requestAnimationFrame(function () {
                barra.style.width = (parseInt(barra.getAttribute('data-pct'), 10) || 0) + '%';
            });
        }
    }
    pintarBarra();

    // "Actualizado hace X" — el dueno remoto necesita saber que el dato es fresco.
    var reloj = raiz.querySelector('[data-du-reloj]');
    function pintarReloj() {
        if (!reloj) { return; }
        var desde = parseInt(reloj.getAttribute('data-desde'), 10) || 0;
        var mins = Math.max(0, Math.round((Date.now() / 1000 - desde) / 60));
        reloj.textContent = mins < 1 ? 'hace un momento'
            : (mins === 1 ? 'hace 1 minuto'
            : (mins < 60 ? 'hace ' + mins + ' minutos' : 'hace más de una hora'));
    }
    pintarReloj();
    window.setInterval(pintarReloj, 30000);

    function poner(clave, texto) {
        var el = raiz.querySelector('[data-du="' + clave + '"]');
        if (el && typeof texto === 'string') { el.textContent = texto; }
    }

    function aplicar(r) {
        if (!r) { return; }

        if (r.semaforo) {
            var sem = raiz.querySelector('[data-du-card="semaforo"]');
            if (sem) { sem.setAttribute('data-estado', r.semaforo.estado || 'verde'); }
            poner('semaforo-etiqueta', r.semaforo.etiqueta);
            poner('semaforo-detalle', r.semaforo.detalle);

            var score = raiz.querySelector('[data-du="semaforo-score"]');
            if (score) {
                score.hidden = (r.semaforo.score === null || r.semaforo.score === undefined);
                if (!score.hidden) { score.textContent = String(r.semaforo.score); }
            }
            var spark = raiz.querySelector('[data-du-spark]');
            if (spark) {
                spark.hidden = !(r.semaforo.estado === 'verde' && r.semaforo.score >= 80);
            }
            var lista = raiz.querySelector('[data-du-desglose]');
            var toggle = raiz.querySelector('[data-du-sem-toggle]');
            if (lista && Array.isArray(r.semaforo.desglose)) {
                lista.textContent = '';
                r.semaforo.desglose.forEach(function (linea) {
                    var li = document.createElement('li');
                    li.textContent = linea;
                    lista.appendChild(li);
                });
                if (toggle) { toggle.hidden = r.semaforo.desglose.length === 0; }
            }
        }
        if (r.dinero) {
            poner('dinero-ingresos', r.dinero.ingresos);
            poner('dinero-gastos', r.dinero.gastos_linea);
            poner('dinero-caja', r.dinero.caja_linea);
            var caja = raiz.querySelector('[data-du-caja]');
            if (caja) { caja.setAttribute('data-abierta', r.dinero.caja_abierta ? '1' : '0'); }
        }
        if (r.hotel) {
            poner('hotel-ocupacion', r.hotel.ocupacion_texto);
            poner('hotel-llegadas', r.hotel.llegadas_linea);
            poner('hotel-salidas', r.hotel.salidas_linea);
            if (barra) {
                barra.setAttribute('data-pct', String(r.hotel.ocupacion_pct || 0));
                pintarBarra();
            }
        }
        if (r.guardian) {
            var g = raiz.querySelector('[data-du-card="guardian"]');
            poner('guardian-texto', r.guardian.texto);
            poner('guardian-detalle', r.guardian.detalle);
            if (g) {
                g.setAttribute('data-guard', r.guardian.ok ? 'ok' : 'temas');
                var icono = g.querySelector('[data-du-guard-icono]');
                if (icono) { icono.className = 'fas ' + (r.guardian.ok ? 'fa-check' : 'fa-magnifying-glass'); }
                var liga = g.querySelector('[data-du-guard-link]');
                if (liga) {
                    if (r.guardian.url) { liga.hidden = false; liga.setAttribute('href', r.guardian.url); }
                    else { liga.hidden = true; }
                }
            }
        }
        if (r.resenas) {
            poner('resenas-promedio', r.resenas.promedio || '');
            poner('resenas-reciente', r.resenas.reciente);
            var estrellas = raiz.querySelectorAll('[data-du-estrellas] i');
            estrellas.forEach(function (e, i) {
                e.classList.toggle('apagada', i >= (r.resenas.estrellas || 0));
            });
        }
        if (reloj && r.generado_en) {
            reloj.setAttribute('data-desde', String(r.generado_en));
            pintarReloj();
        }
    }

    // Desglose del dia: se abre al tocar (nada se mueve solo).
    var semToggle = raiz.querySelector('[data-du-sem-toggle]');
    if (semToggle) {
        semToggle.addEventListener('click', function () {
            var lista = raiz.querySelector('[data-du-desglose]');
            var abierto = semToggle.getAttribute('aria-expanded') === 'true';
            semToggle.setAttribute('aria-expanded', abierto ? 'false' : 'true');
            if (lista) { lista.hidden = abierto; }
            var texto = semToggle.querySelector('[data-du-toggle-texto]');
            if (texto) { texto.textContent = abierto ? 'Ver el detalle' : 'Ocultar el detalle'; }
        });
    }

    var boton = raiz.querySelector('[data-du-refrescar]');
    var error = raiz.querySelector('[data-du-error]');
    if (boton) {
        boton.addEventListener('click', function () {
            boton.disabled = true;
            boton.classList.add('cargando');
            if (error) { error.classList.remove('visible'); }

            fetch(raiz.getAttribute('data-datos-url'), { headers: { 'Accept': 'application/json' } })
                .then(function (resp) {
                    if (!resp.ok) { throw new Error('estado ' + resp.status); }
                    return resp.json();
                })
                .then(function (data) { aplicar(data && data.resumen); })
                .catch(function () {
                    if (error) { error.classList.add('visible'); }
                })
                .finally(function () {
                    boton.disabled = false;
                    boton.classList.remove('cargando');
                });
        });
    }
})();
</script>
