<?php
/**
 * Mensajes (bloque canal_whatsapp) — cola de trabajo del dia.
 *
 * No es un log: es lo que recepcion debe MANDAR hoy. Cada tarjeta trae el
 * mensaje ya redactado; el boton abre WhatsApp con el texto listo y registra
 * el envio en el timeline (POST con CSRF). Nada aqui toca dinero.
 */
$pendientes = $pendientes ?? [];
$historial = $historial ?? [];
$resumen = $resumen ?? ['pendientes' => 0, 'enviados' => 0, 'descartados' => 0];
$tiposActivos = $tiposActivos ?? [];
$configFaltante = $configFaltante ?? [];

$msjSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$msjTipos = [
    'confirmacion' => ['Confirmación', 'fa-calendar-check', 'is-info', 'Reservas nuevas por confirmar'],
    'recordatorio' => ['Recordatorio', 'fa-bell', 'is-gold', 'Llegadas de mañana'],
    'anticipo' => ['Anticipo', 'fa-building-columns', 'is-gold', 'Anticipos'],
    'encuesta' => ['Encuesta', 'fa-star', 'is-ok', 'Salidas de hoy'],
];

$msjGrupos = [];
foreach ($pendientes as $item) {
    $msjGrupos[$item['tipo']][] = $item;
}

$msjFechaCorta = static function ($fecha) {
    $ts = strtotime((string) $fecha);
    if (!$ts) {
        return '';
    }
    $meses = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    return (int) date('j', $ts) . ' ' . $meses[(int) date('n', $ts)];
};

$msjContexto = static function (array $item) use ($msjFechaCorta) {
    $hab = trim((string) ($item['habitaciones'] ?? ''));
    $hab = $hab !== '' ? 'Hab. ' . $hab : 'Sin habitación asignada';

    switch ($item['tipo']) {
        case 'recordatorio':
            return 'Llega mañana · ' . $hab;
        case 'encuesta':
            return 'Salió hoy · ' . $hab;
        default:
            return 'Llega el ' . $msjFechaCorta($item['fecha_entrada']) . ' · ' . $hab;
    }
};

$msjNombresFaltantes = [
    'datos_deposito' => 'los datos de depósito (cuenta/CLABE)',
    'link_maps' => 'el link de Google Maps',
];
$msjFaltantesTexto = [];
foreach ($configFaltante as $clave) {
    $msjFaltantesTexto[] = $msjNombresFaltantes[$clave] ?? $clave;
}

$msjDiasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$msjMesesLargos = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$msjHoy = ucfirst($msjDiasSemana[(int) date('w')]) . ' ' . (int) date('j') . ' de ' . $msjMesesLargos[(int) date('n')];
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.msj {
    --msj-brand: var(--brand-primary, #1B2746);
    --msj-gold: var(--brand-accent, #BD9441);
    --msj-gold-soft: color-mix(in srgb, var(--msj-gold) 15%, #FFFFFF);
    --msj-gold-line: color-mix(in srgb, var(--msj-gold) 42%, #E4D4B0);
    --msj-gold-ink: color-mix(in srgb, var(--msj-gold) 58%, var(--msj-brand));
    --msj-ivory: #F5F5F7;
    --msj-ivory-2: #FAFAFC;
    --msj-surface: #FFFFFF;
    --msj-surface-warm: #F5F5F7;
    --msj-border: color-mix(in srgb, var(--msj-brand) 6%, #E9E1D6);
    --msj-ring: color-mix(in srgb, var(--msj-gold) 32%, transparent);
    --msj-text: color-mix(in srgb, var(--msj-brand) 46%, #707B8C);
    --msj-muted: #8791A2;
    --msj-heading: color-mix(in srgb, var(--msj-brand) 66%, #566172);
    --msj-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --msj-success: #1E9E63;
    --msj-success-bg: #E7F4EC;
    --msj-warning: #C2841C;
    --msj-warning-bg: #FAF0DC;
    --msj-danger: #B4392B;
    --msj-info: #2F77E0;
    --msj-info-bg: #E6EFFC;
    --msj-ease: cubic-bezier(.22, 1, .36, 1);
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 40px;
    color: var(--msj-text);
    font-family: var(--msj-sans);
    font-size: .92rem;
    
}
.msj * { box-sizing: border-box; }
.msj-shell { display: grid; gap: 14px; width: 100%; max-width: 1100px; min-width: 0; margin: 0 auto; }
.msj-shell > * { min-width: 0; }

/* ── Héroe ── */
.msj-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.msj-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 780px);
}
.msj-title-lockup > div:last-child { min-width: 0; }
.msj-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--msj-gold), var(--msj-brand) 54%, color-mix(in srgb, var(--msj-brand) 68%, var(--msj-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--msj-brand) 72%, transparent);
}
.msj-kicker { margin: 0 0 2px; color: var(--msj-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.msj-title { margin: 0; color: var(--msj-heading); font-size: clamp(2.1rem, 4vw, 3rem); font-weight: 650; line-height: .98; overflow-wrap: anywhere; }
.msj-subtitle { max-width: 50rem; margin: 9px 0 0; color: var(--msj-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }
.msj-hero-side { display: grid; gap: 8px; justify-items: end; }
.msj-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid var(--msj-gold-line);
    background: var(--msj-gold-soft);
    color: var(--msj-gold-ink);
    font-size: .78rem;
    font-weight: 650;
    white-space: nowrap;
}
.msj-config-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid var(--msj-border);
    background: var(--msj-surface);
    color: var(--msj-muted);
    font-size: .78rem;
    font-weight: 650;
    text-decoration: none;
    transition: transform .16s var(--msj-ease), box-shadow .16s var(--msj-ease), color .16s ease;
}
.msj-config-link:hover { color: var(--msj-gold-ink); transform: translateY(-1px); box-shadow: 0 10px 18px -14px rgba(27,39,70,.35); }

/* ── Avisos ── */
.msj-alert { padding: 12px 14px; border-radius: 13px; font-size: .88rem; font-weight: 560; line-height: 1.45; }
.msj-alert.is-success { background: var(--msj-success-bg); color: color-mix(in srgb, var(--msj-success) 70%, var(--msj-text)); border: 1px solid color-mix(in srgb, var(--msj-success) 24%, #fff); }
.msj-alert.is-error { background: #F8EAE5; color: color-mix(in srgb, var(--msj-danger) 72%, var(--msj-text)); border: 1px solid color-mix(in srgb, var(--msj-danger) 24%, #fff); }
.msj-alert.is-info { background: var(--msj-gold-soft); color: var(--msj-gold-ink); border: 1px solid var(--msj-gold-line); }
.msj-alert a { color: inherit; font-weight: 700; }
.msj-config-warn {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 13px;
    border: 1px solid color-mix(in srgb, var(--msj-warning) 30%, #fff);
    background: var(--msj-warning-bg);
    color: color-mix(in srgb, var(--msj-warning) 78%, var(--msj-text));
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.5;
}
.msj-config-warn i { margin-top: 2px; }
.msj-config-warn a { color: inherit; font-weight: 700; }

/* ── KPIs ── */
.msj-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.msj-kpi {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--msj-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.msj-kpi .nombre { color: var(--msj-muted); font-size: .68rem; font-weight: 650; letter-spacing: .06em; text-transform: uppercase; }
.msj-kpi .valor { margin-top: 4px; color: var(--msj-heading); font-size: 1.65rem; font-weight: 650; line-height: 1.05; }
.msj-kpi .valor small { color: var(--msj-muted); font-size: .78rem; font-weight: 600; }
.msj-kpi.is-live .valor { color: var(--msj-gold-ink); }

/* ── Tabs ── */
.msj-tabs { display: inline-flex; gap: 4px; padding: 4px; border: 1px solid var(--msj-border); border-radius: 999px; background: rgba(255,255,255,.72); width: fit-content; }
.msj-tab {
    appearance: none;
    border: 0;
    border-radius: 999px;
    min-height: 38px;
    padding: 0 16px;
    background: transparent;
    color: var(--msj-muted);
    font-family: inherit;
    font-size: .84rem;
    font-weight: 650;
    cursor: pointer;
    transition: background .16s ease, color .16s ease;
}
.msj-tab[aria-selected="true"] {
    background: linear-gradient(145deg, var(--msj-gold), color-mix(in srgb, var(--msj-gold) 72%, var(--msj-brand)));
    color: #fff;
    box-shadow: 0 8px 16px -10px color-mix(in srgb, var(--msj-gold) 70%, transparent);
}
.msj-tab:focus-visible { outline: 3px solid var(--msj-ring); outline-offset: 2px; }

/* ── Grupos y tarjetas de la cola ── */
.msj-grupo { display: grid; gap: 8px; }
.msj-grupo-head { display: flex; align-items: baseline; gap: 8px; padding: 6px 2px 0; }
.msj-grupo-head h2 { margin: 0; color: var(--msj-heading); font-size: 1.02rem; font-weight: 650; }
.msj-grupo-head .cuantos { color: var(--msj-muted); font-size: .78rem; font-weight: 650; }
.msj-cards { display: grid; gap: 8px; }
.msj-card {
    display: grid;
    gap: 10px;
    padding: 14px;
    border: 1px solid var(--msj-border);
    border-radius: 16px;
    background: var(--msj-surface);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 24px -22px rgba(27,39,70,.22);
    transition: transform .16s var(--msj-ease), box-shadow .16s var(--msj-ease), opacity .3s ease;
}
.msj-card:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(27,39,70,.04), 0 16px 30px -22px rgba(27,39,70,.3); }
.msj-card.is-saliendo { opacity: 0; transform: translateY(-6px) scale(.985); }
.msj-card-top { display: grid; grid-template-columns: 40px minmax(0, 1fr) auto; align-items: center; gap: 11px; }
.msj-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: .95rem;
    background: var(--msj-ivory-2);
    border: 1px solid var(--msj-border);
    color: var(--msj-gold-ink);
}
.msj-card-quien { min-width: 0; }
.msj-card-quien .nombre { margin: 0; color: var(--msj-heading); font-size: .98rem; font-weight: 650; overflow-wrap: anywhere; }
.msj-card-quien .contexto { margin: 2px 0 0; color: var(--msj-muted); font-size: .8rem; font-weight: 560; }
.msj-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: .7rem;
    font-weight: 650;
    letter-spacing: .03em;
    white-space: nowrap;
}
.msj-chip.is-info { background: var(--msj-info-bg); color: color-mix(in srgb, var(--msj-info) 78%, var(--msj-text)); }
.msj-chip.is-gold { background: var(--msj-gold-soft); color: var(--msj-gold-ink); }
.msj-chip.is-ok { background: var(--msj-success-bg); color: color-mix(in srgb, var(--msj-success) 74%, var(--msj-text)); }
.msj-chip.is-warn { background: var(--msj-warning-bg); color: color-mix(in srgb, var(--msj-warning) 80%, var(--msj-text)); }
.msj-chip.is-muted { background: var(--msj-ivory-2); color: var(--msj-muted); }
/* Los chips de aviso llevan frases: envuelven en vez de forzar el ancho del grid. */
.msj-chip.is-warn, .msj-chip.is-muted { white-space: normal; text-align: left; line-height: 1.4; padding-top: 5px; padding-bottom: 5px; }
.msj-card-tel { display: inline-flex; align-items: center; gap: 6px; color: var(--msj-muted); font-size: .8rem; font-weight: 560; }
.msj-preview { border: 1px solid var(--msj-border); border-radius: 12px; background: var(--msj-surface-warm); }
.msj-preview summary {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 12px;
    color: var(--msj-gold-ink);
    font-size: .8rem;
    font-weight: 650;
    cursor: pointer;
    list-style: none;
    user-select: none;
}
.msj-preview summary::-webkit-details-marker { display: none; }
.msj-preview summary i { transition: transform .18s var(--msj-ease); }
.msj-preview[open] summary i { transform: rotate(90deg); }
.msj-preview summary:focus-visible { outline: 3px solid var(--msj-ring); outline-offset: -3px; border-radius: 12px; }
.msj-preview-texto {
    margin: 0;
    padding: 0 12px 12px;
    color: var(--msj-text);
    font-size: .86rem;
    font-weight: 500;
    line-height: 1.55;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
}
.msj-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.msj-acciones { display: flex; gap: 8px; flex-wrap: wrap; }
.msj-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 12px;
    font-family: inherit;
    font-size: .86rem;
    font-weight: 650;
    cursor: pointer;
    text-decoration: none;
    transition: transform .16s var(--msj-ease), box-shadow .16s var(--msj-ease), background .16s ease, opacity .16s ease;
}
.msj-btn:focus-visible { outline: 3px solid var(--msj-ring); outline-offset: 2px; }
.msj-btn[disabled] { opacity: .55; cursor: not-allowed; }
.msj-btn.pri {
    color: #fff;
    background: linear-gradient(145deg, var(--msj-gold), color-mix(in srgb, var(--msj-gold) 68%, var(--msj-brand)));
    box-shadow: 0 12px 22px -12px color-mix(in srgb, var(--msj-gold) 78%, transparent);
}
.msj-btn.pri:hover:not([disabled]) { transform: translateY(-1px); box-shadow: 0 14px 26px -12px color-mix(in srgb, var(--msj-gold) 88%, transparent); }
.msj-btn.sec { background: var(--msj-surface); color: var(--msj-muted); border-color: var(--msj-border); }
.msj-btn.sec:hover:not([disabled]) { color: var(--msj-heading); border-color: color-mix(in srgb, var(--msj-muted) 40%, var(--msj-border)); }
.msj-btn .fa-whatsapp { font-size: 1.05rem; }

/* ── Vacíos ── */
.msj-vacio {
    display: grid;
    justify-items: center;
    gap: 10px;
    padding: 46px 18px;
    border: 1px dashed color-mix(in srgb, var(--msj-gold) 34%, var(--msj-border));
    border-radius: 16px;
    background: rgba(255,255,255,.6);
    text-align: center;
}
.msj-vacio .icono {
    width: 52px;
    height: 52px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    font-size: 1.3rem;
    color: var(--msj-success);
    background: var(--msj-success-bg);
    animation: msjPop .5s var(--msj-ease);
}
.msj-vacio .titulo { margin: 0; color: var(--msj-heading); font-size: 1.05rem; font-weight: 650; }
.msj-vacio .detalle { margin: 0; color: var(--msj-muted); font-size: .86rem; font-weight: 520; max-width: 34rem; }
@keyframes msjPop { 0% { transform: scale(.5); opacity: 0; } 60% { transform: scale(1.08); } 100% { transform: scale(1); opacity: 1; } }

/* ── Historial ── */
.msj-panel { border: 1px solid var(--msj-border); border-radius: 16px; background: var(--msj-surface); overflow: hidden; }
.msj-hist-item { display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: 11px; padding: 13px 14px; border-top: 1px solid var(--msj-border); }
.msj-hist-item:first-child { border-top: 0; }
.msj-hist-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center; font-size: .9rem; }
.msj-hist-icon.is-enviado { background: var(--msj-success-bg); color: var(--msj-success); }
.msj-hist-icon.is-descartado { background: var(--msj-ivory-2); color: var(--msj-muted); }
.msj-hist-body { min-width: 0; display: grid; gap: 3px; }
.msj-hist-linea1 { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.msj-hist-linea1 .nombre { color: var(--msj-heading); font-size: .92rem; font-weight: 650; }
.msj-hist-meta { color: var(--msj-muted); font-size: .78rem; font-weight: 560; }
.msj-hist-meta a { color: var(--msj-gold-ink); font-weight: 650; text-decoration: none; }
.msj-hist-meta a:hover { text-decoration: underline; }
.msj-hist-item .msj-preview { margin-top: 6px; }

/* ── Responsive ── */
@media (max-width: 900px) {
    .msj-hero-section { grid-template-columns: minmax(0, 1fr); align-items: start; gap: 12px; }
    .msj-hero-side { justify-items: start; grid-auto-flow: column; }
}
@media (max-width: 640px) {
    .msj { padding: 14px 12px 34px; }
    .msj-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .msj-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1rem; }
    .msj-title { font-size: 2rem; }
    .msj-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 7px; }
    .msj-kpi { padding: 10px 11px; }
    .msj-kpi .valor { font-size: 1.3rem; }
    .msj-tabs { width: 100%; }
    .msj-tab { flex: 1; }
    .msj-card-foot { align-items: stretch; flex-direction: column; }
    .msj-acciones { width: 100%; }
    .msj-acciones .msj-btn { flex: 1; }
}
@media (prefers-reduced-motion: reduce) {
    .msj *, .msj *::before, .msj *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<div class="msj" id="msjApp">
    <div class="msj-shell">
        <section class="msj-hero-section">
            <div class="msj-title-lockup">
                <div class="msj-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-comment-dots"></i>
                </div>
                <div>
                    <p class="msj-kicker">Canal WhatsApp</p>
                    <h1 class="msj-title">Mensajes</h1>
                    <p class="msj-subtitle">Lo que toca mandar hoy, ya redactado: confirma reservas nuevas, recuerda las llegadas de mañana y agradece a quien se fue con tu encuesta.</p>
                </div>
            </div>
            <div class="msj-hero-side">
                <span class="msj-status-pill">
                    <i class="fa-solid fa-calendar-day" aria-hidden="true"></i>
                    <?= $msjSafe($msjHoy) ?>
                </span>
                <a class="msj-config-link" href="<?= url('mensajes/configuracion') ?>">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    Configuración
                </a>
            </div>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipoFlash = (string) ($mensaje['tipo'] ?? 'info');
                $tipoFlash = in_array($tipoFlash, ['success', 'error', 'info'], true) ? $tipoFlash : 'info';
            ?>
            <div class="msj-alert is-<?= $msjSafe($tipoFlash) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msjFaltantesTexto)): ?>
            <div class="msj-config-warn" role="status">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <div>
                    Para que tus mensajes salgan completos falta capturar <?= $msjSafe(implode(' y ', $msjFaltantesTexto)) ?>.
                    <a href="<?= url('mensajes/configuracion') ?>">Completar configuración</a>
                </div>
            </div>
        <?php endif; ?>

        <section class="msj-kpis" aria-label="Pulso de mensajes de hoy">
            <div class="msj-kpi is-live">
                <div class="nombre">Por enviar hoy</div>
                <div class="valor" id="msjKpiPendientes"><?= (int) $resumen['pendientes'] ?></div>
            </div>
            <div class="msj-kpi">
                <div class="nombre">Enviados hoy</div>
                <div class="valor" id="msjKpiEnviados"><?= (int) $resumen['enviados'] ?></div>
            </div>
            <div class="msj-kpi">
                <div class="nombre">Descartados hoy</div>
                <div class="valor" id="msjKpiDescartados"><?= (int) $resumen['descartados'] ?></div>
            </div>
        </section>

        <div class="msj-tabs" role="tablist" aria-label="Cola e historial">
            <button type="button" class="msj-tab" role="tab" aria-selected="true" aria-controls="msjPanelCola" id="msjTabCola">
                Por enviar hoy
            </button>
            <button type="button" class="msj-tab" role="tab" aria-selected="false" aria-controls="msjPanelHistorial" id="msjTabHistorial">
                Historial
            </button>
        </div>

        <section id="msjPanelCola" role="tabpanel" aria-labelledby="msjTabCola" style="display: grid; gap: 14px;">
            <?php foreach (['confirmacion', 'recordatorio', 'encuesta'] as $msjTipo): ?>
                <?php
                    $items = $msjGrupos[$msjTipo] ?? [];
                    if (!$items) {
                        continue;
                    }
                    [$labelTipo, $iconoTipo, $tonoTipo, $tituloGrupo] = $msjTipos[$msjTipo];
                ?>
                <div class="msj-grupo" data-msj-grupo>
                    <div class="msj-grupo-head">
                        <h2><?= $msjSafe($tituloGrupo) ?></h2>
                        <span class="cuantos" data-msj-cuantos><?= count($items) ?> por enviar</span>
                    </div>
                    <div class="msj-cards">
                        <?php foreach ($items as $item): ?>
                            <?php
                                $telUsable = $item['telefono_wa'] !== null;
                                $sinConfig = !empty($item['faltantes']);
                                $linkWa = $telUsable
                                    ? 'https://wa.me/' . rawurlencode($item['telefono_wa']) . '?text=' . rawurlencode($item['texto'])
                                    : '';
                            ?>
                            <article class="msj-card"
                                     data-msj-card
                                     data-reservacion="<?= (int) $item['reservacion_id'] ?>"
                                     data-tipo="<?= $msjSafe($item['tipo']) ?>">
                                <div class="msj-card-top">
                                    <div class="msj-card-icon" aria-hidden="true"><i class="fa-solid <?= $msjSafe($iconoTipo) ?>"></i></div>
                                    <div class="msj-card-quien">
                                        <p class="nombre"><?= $msjSafe($item['huesped_nombre']) ?></p>
                                        <p class="contexto"><?= $msjSafe($msjContexto($item)) ?></p>
                                    </div>
                                    <span class="msj-chip <?= $msjSafe($tonoTipo) ?>">
                                        <i class="fa-solid <?= $msjSafe($iconoTipo) ?>" aria-hidden="true"></i>
                                        <?= $msjSafe($labelTipo) ?>
                                    </span>
                                </div>

                                <?php if ($telUsable): ?>
                                    <span class="msj-card-tel"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> <?= $msjSafe($item['telefono']) ?></span>
                                <?php else: ?>
                                    <span class="msj-chip is-warn"><i class="fa-solid fa-phone-slash" aria-hidden="true"></i> <?= $msjSafe($item['motivo_telefono']) ?> — revisa la ficha del huésped</span>
                                <?php endif; ?>

                                <details class="msj-preview">
                                    <summary><i class="fa-solid fa-chevron-right" aria-hidden="true"></i> Ver mensaje redactado</summary>
                                    <p class="msj-preview-texto"><?= $msjSafe($item['texto']) ?></p>
                                </details>

                                <div class="msj-card-foot">
                                    <?php if ($sinConfig): ?>
                                        <span class="msj-chip is-warn">
                                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                            Falta configuración — <a href="<?= url('mensajes/configuracion') ?>" style="color: inherit;">completar</a>
                                        </span>
                                    <?php else: ?>
                                        <span></span>
                                    <?php endif; ?>
                                    <div class="msj-acciones">
                                        <button type="button" class="msj-btn sec" data-msj-descartar>
                                            Descartar
                                        </button>
                                        <?php if ($telUsable && !$sinConfig): ?>
                                            <button type="button" class="msj-btn pri" data-msj-enviar data-link="<?= $msjSafe($linkWa) ?>">
                                                <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                                                Enviar por WhatsApp
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="msj-vacio" id="msjVacio" <?= $pendientes ? 'hidden' : '' ?>>
                <div class="icono" aria-hidden="true"><i class="fa-solid fa-check"></i></div>
                <p class="titulo">Todo al día</p>
                <p class="detalle">No hay mensajes esperando. Las reservas nuevas, las llegadas de mañana y las salidas de hoy aparecerán aquí solitas.</p>
            </div>
        </section>

        <section id="msjPanelHistorial" role="tabpanel" aria-labelledby="msjTabHistorial" hidden>
            <?php if ($historial): ?>
                <div class="msj-panel">
                    <?php foreach ($historial as $h): ?>
                        <?php
                            $esEnviado = ($h['estado'] ?? '') === 'enviado';
                            [$labelTipoH, $iconoTipoH, $tonoTipoH] = $msjTipos[$h['tipo']] ?? ['Mensaje', 'fa-comment', 'is-muted'];
                            $cuando = $h['enviado_en'] ?: ($h['updated_at'] ?? '');
                            $cuandoTs = $cuando ? strtotime((string) $cuando) : false;
                        ?>
                        <div class="msj-hist-item">
                            <div class="msj-hist-icon <?= $esEnviado ? 'is-enviado' : 'is-descartado' ?>" aria-hidden="true">
                                <i class="fa-solid <?= $esEnviado ? 'fa-check' : 'fa-ban' ?>"></i>
                            </div>
                            <div class="msj-hist-body">
                                <div class="msj-hist-linea1">
                                    <span class="nombre"><?= $msjSafe($h['huesped_nombre']) ?></span>
                                    <span class="msj-chip <?= $msjSafe($tonoTipoH) ?>"><?= $msjSafe($labelTipoH) ?></span>
                                    <?php if (!$esEnviado): ?>
                                        <span class="msj-chip is-muted">Descartado<?= $h['motivo'] ? ': ' . $msjSafe($h['motivo']) : '' ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="msj-hist-meta">
                                    <?= $esEnviado ? 'Enviado' : 'Registrado' ?>
                                    <?= $cuandoTs ? 'el ' . date('d/m/Y', $cuandoTs) . ' a las ' . date('H:i', $cuandoTs) : '' ?>
                                    <?= !empty($h['usuario_nombre']) ? 'por ' . $msjSafe($h['usuario_nombre']) : '' ?>
                                    · <a href="<?= url('reservaciones/ver/' . (int) $h['reservacion_id']) ?>">Ver reservación</a>
                                </div>
                                <?php if (!empty($h['contenido'])): ?>
                                    <details class="msj-preview">
                                        <summary><i class="fa-solid fa-chevron-right" aria-hidden="true"></i> Ver texto enviado</summary>
                                        <p class="msj-preview-texto"><?= $msjSafe($h['contenido']) ?></p>
                                    </details>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="msj-vacio">
                    <div class="icono" aria-hidden="true"><i class="fa-solid fa-inbox"></i></div>
                    <p class="titulo">Aún no hay historial</p>
                    <p class="detalle">Cuando envíes o descartes un mensaje quedará registrado aquí: qué se mandó, a quién, cuándo y quién lo hizo.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <input type="hidden" id="msjCsrf" value="<?= csrf_token() ?>">
</div>

<script>
(function () {
    'use strict';

    var app = document.getElementById('msjApp');
    if (!app) return;

    var csrf = (document.getElementById('msjCsrf') || {}).value || '';
    var esMovil = window.matchMedia('(max-width: 900px), (pointer: coarse)').matches;

    // ── Tabs ──
    var tabs = [
        { btn: document.getElementById('msjTabCola'), panel: document.getElementById('msjPanelCola') },
        { btn: document.getElementById('msjTabHistorial'), panel: document.getElementById('msjPanelHistorial') }
    ];
    tabs.forEach(function (t) {
        if (!t.btn) return;
        t.btn.addEventListener('click', function () {
            tabs.forEach(function (o) {
                var activo = o === t;
                if (o.btn) o.btn.setAttribute('aria-selected', activo ? 'true' : 'false');
                if (o.panel) o.panel.hidden = !activo;
            });
        });
    });

    function kpi(id, delta) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + delta);
    }

    function aviso(texto, tipo) {
        var previo = app.querySelector('[data-msj-aviso]');
        if (previo) previo.remove();
        var div = document.createElement('div');
        div.className = 'msj-alert is-' + (tipo || 'info');
        div.setAttribute('data-msj-aviso', '');
        div.setAttribute('role', 'status');
        div.textContent = texto;
        var shell = app.querySelector('.msj-shell');
        shell.insertBefore(div, shell.children[1] || null);
        setTimeout(function () { div.remove(); }, 6000);
    }

    function quitarCard(card) {
        var grupo = card.closest('[data-msj-grupo]');
        card.classList.add('is-saliendo');
        setTimeout(function () {
            card.remove();
            if (grupo) {
                var quedan = grupo.querySelectorAll('[data-msj-card]').length;
                var cuantos = grupo.querySelector('[data-msj-cuantos]');
                if (cuantos) cuantos.textContent = quedan + ' por enviar';
                if (!quedan) grupo.remove();
            }
            if (!document.querySelector('[data-msj-card]')) {
                var vacio = document.getElementById('msjVacio');
                if (vacio) vacio.hidden = false;
            }
        }, 320);
    }

    function post(accion, card, extra) {
        var datos = new URLSearchParams();
        datos.set('reservacion_id', card.getAttribute('data-reservacion') || '');
        datos.set('tipo', card.getAttribute('data-tipo') || '');
        datos.set('csrf_token', csrf);
        Object.keys(extra || {}).forEach(function (k) { datos.set(k, extra[k]); });

        return fetch('<?= url('mensajes') ?>/' + accion, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrf
            },
            body: datos.toString()
        }).then(function (r) { return r.json(); });
    }

    // ── Enviar: registra el envio y abre WhatsApp con el texto listo ──
    app.addEventListener('click', function (ev) {
        var btnEnviar = ev.target.closest('[data-msj-enviar]');
        var btnDescartar = ev.target.closest('[data-msj-descartar]');

        if (btnEnviar) {
            var card = btnEnviar.closest('[data-msj-card]');
            if (!card || btnEnviar.disabled) return;
            btnEnviar.disabled = true;

            // En escritorio la pestana se abre DENTRO del gesto del usuario
            // (los popup blockers no perdonan aperturas tardias); en movil se
            // navega en la misma pestana y el sistema abre la app de WhatsApp.
            var ventana = esMovil ? null : window.open('', '_blank');

            post('enviar', card).then(function (r) {
                if (r && r.success && r.link) {
                    if (ventana) { ventana.location = r.link; } else { window.location.href = r.link; }
                    kpi('msjKpiPendientes', -1);
                    kpi('msjKpiEnviados', 1);
                    quitarCard(card);
                } else {
                    if (ventana) ventana.close();
                    btnEnviar.disabled = false;
                    if (r && r.descartado) {
                        aviso((r.motivo || 'El teléfono no es utilizable.') + ' Quedó descartado en el historial.', 'error');
                        kpi('msjKpiPendientes', -1);
                        kpi('msjKpiDescartados', 1);
                        quitarCard(card);
                    } else {
                        aviso((r && r.motivo) || 'No se pudo preparar el mensaje. Intenta de nuevo.', 'error');
                    }
                }
            }).catch(function () {
                if (ventana) ventana.close();
                btnEnviar.disabled = false;
                aviso('Sin conexión con el sistema. El mensaje NO se marcó como enviado.', 'error');
            });
        }

        if (btnDescartar) {
            var cardD = btnDescartar.closest('[data-msj-card]');
            if (!cardD || btnDescartar.disabled) return;
            btnDescartar.disabled = true;

            post('descartar', cardD).then(function (r) {
                if (r && r.success) {
                    kpi('msjKpiPendientes', -1);
                    kpi('msjKpiDescartados', 1);
                    quitarCard(cardD);
                } else {
                    btnDescartar.disabled = false;
                    aviso((r && r.motivo) || 'No se pudo descartar.', 'error');
                }
            }).catch(function () {
                btnDescartar.disabled = false;
                aviso('Sin conexión con el sistema. Intenta de nuevo.', 'error');
            });
        }
    });
})();
</script>
