<?php
/**
 * Asesor inteligente: resumen gerencial diario narrado (bloque ia_ejecutiva).
 * Vista interna con layout estandar.
 */
$fecha = $fecha ?? date('Y-m-d');
$resultado = $resultado ?? ['success' => false, 'message' => 'Sin datos.'];
$configurado = $configurado ?? false;

$iaSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/**
 * Render minimo de Markdown a HTML seguro: escapa todo primero y luego
 * aplica solo negritas, encabezados y listas. Sin HTML libre del modelo.
 */
$iaMarkdown = static function ($texto) {
    $lineas = explode("\n", (string) $texto);
    $html = '';
    $enLista = false;

    foreach ($lineas as $linea) {
        $linea = htmlspecialchars(rtrim($linea), ENT_QUOTES, 'UTF-8');
        $linea = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $linea);

        if (preg_match('/^#{1,3}\s*(.+)$/', $linea, $m)) {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<h3>' . $m[1] . '</h3>';
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (preg_match('/^\s*\d+\.\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (trim($linea) === '') {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
        } else {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<p>' . $linea . '</p>';
        }
    }

    if ($enLista) {
        $html .= '</ul>';
    }

    return $html;
};

$hayResumen = !empty($resultado['success']);
$fechaTs = strtotime((string) $fecha) ?: time();
$fechaHumana = date('d/m/Y', $fechaTs);
$generadoHumano = '';
if (!empty($resultado['generado_en'])) {
    $generadoTs = strtotime((string) $resultado['generado_en']);
    $generadoHumano = $generadoTs ? date('d/m/Y H:i', $generadoTs) : '';
}
$estadoTexto = $hayResumen ? 'Listo' : 'Pendiente';
$configTexto = $configurado ? 'Activo' : 'Sin configurar';
$ctaTexto = $hayResumen ? 'Regenerar con datos actuales' : 'Generar resumen de hoy';
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.iav {
    --iav-brand: var(--brand-primary, #1B2746);
    --iav-brand-2: var(--brand-secondary, #0F172A);
    --iav-gold: var(--brand-accent, #BD9441);
    --iav-gold-soft: color-mix(in srgb, var(--iav-gold) 15%, #FFFFFF);
    --iav-gold-line: color-mix(in srgb, var(--iav-gold) 42%, #E4D4B0);
    --iav-gold-ink: color-mix(in srgb, var(--iav-gold) 58%, var(--iav-brand));
    --iav-ivory: #F6F2EA;
    --iav-ivory-2: #FBF8F2;
    --iav-surface: #FFFFFF;
    --iav-surface-warm: #FCFAF5;
    --iav-border: color-mix(in srgb, var(--iav-brand) 6%, #E9E1D6);
    --iav-ring: color-mix(in srgb, var(--iav-gold) 32%, transparent);
    --iav-text: color-mix(in srgb, var(--iav-brand) 46%, #707B8C);
    --iav-muted: #8791A2;
    --iav-heading: color-mix(in srgb, var(--iav-brand) 66%, #566172);
    --iav-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --iav-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --iav-success: #1E9E63;
    --iav-success-bg: #E7F4EC;
    --iav-warning: #C2841C;
    --iav-warning-bg: #FAF0DC;
    --iav-danger: #B4392B;
    --iav-danger-bg: #F8EAE5;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--iav-text);
    font-family: var(--iav-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--iav-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--iav-ivory-2), var(--iav-ivory));
}
.iav * { box-sizing: border-box; }
.iav-shell {
    display: grid;
    gap: 14px;
    width: 100%;
    max-width: 1040px;
    min-width: 0;
    margin: 0 auto;
}
.iav-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.iav-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 760px);
}
.iav-title-lockup > div:last-child { min-width: 0; }
.iav-hero-icon {
    width: 48px;
    height: 48px;
    border-radius: 15px;
    display: grid;
    place-items: center;
    color: #fff;
    font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--iav-gold), var(--iav-brand) 54%, color-mix(in srgb, var(--iav-brand) 68%, var(--iav-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--iav-brand) 72%, transparent);
}
.iav-kicker {
    margin: 0 0 2px;
    color: var(--iav-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .11em;
    line-height: 1;
    text-transform: uppercase;
}
.iav-title {
    margin: 0;
    color: var(--iav-heading);
    font-family: var(--iav-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650;
    line-height: .98;
    overflow-wrap: anywhere;
}
.iav-subtitle {
    max-width: 48rem;
    margin: 9px 0 0;
    color: var(--iav-muted);
    font-size: .94rem;
    font-weight: 500;
    line-height: 1.5;
}
.iav-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 36px;
    padding: 0 12px;
    border-radius: 999px;
    border: 1px solid var(--iav-gold-line);
    background: var(--iav-gold-soft);
    color: var(--iav-gold-ink);
    font-size: .78rem;
    font-weight: 650;
    white-space: nowrap;
}
.iav-status-pill i { font-size: .84rem; }
.iav-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
}
.iav-summary-item {
    background: rgba(255,255,255,.82);
    border: 1px solid var(--iav-border);
    border-radius: 14px;
    padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.iav-summary-label {
    color: var(--iav-muted);
    font-size: .68rem;
    font-weight: 650;
    letter-spacing: .045em;
    text-transform: uppercase;
}
.iav-summary-value {
    margin-top: 2px;
    color: var(--iav-heading);
    font-family: var(--iav-serif);
    font-size: 1.7rem;
    font-weight: 650;
    line-height: 1.1;
}
.iav-summary-value.is-ok { color: color-mix(in srgb, var(--iav-success) 68%, var(--iav-text)); }
.iav-summary-value.is-pending { color: color-mix(in srgb, var(--iav-warning) 72%, var(--iav-text)); }
.iav-alert {
    padding: 12px 14px;
    border-radius: 13px;
    font-size: .88rem;
    font-weight: 560;
    line-height: 1.45;
}
.iav-alert.is-success { background: var(--iav-success-bg); color: color-mix(in srgb, var(--iav-success) 70%, var(--iav-text)); border: 1px solid color-mix(in srgb, var(--iav-success) 24%, #fff); }
.iav-alert.is-error { background: var(--iav-danger-bg); color: color-mix(in srgb, var(--iav-danger) 72%, var(--iav-text)); border: 1px solid color-mix(in srgb, var(--iav-danger) 24%, #fff); }
.iav-alert.is-info { background: var(--iav-gold-soft); color: var(--iav-gold-ink); border: 1px solid var(--iav-gold-line); }
.iav-panel,
.iav-reading {
    background: rgba(255,255,255,.86);
    border: 1px solid var(--iav-border);
    border-radius: 16px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22);
}
.iav-panel {
    display: grid;
    gap: 12px;
    padding: 14px 16px;
}
.iav-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.iav-panel-title {
    margin: 0;
    color: var(--iav-heading);
    font-size: .9rem;
    font-weight: 650;
}
.iav-panel-sub {
    margin: 3px 0 0;
    color: var(--iav-muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.45;
}
.iav-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: end;
    justify-content: space-between;
}
.iav-toolbar form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: end;
}
.iav-field {
    display: grid;
    gap: 5px;
    min-width: 190px;
}
.iav-field label {
    color: var(--iav-muted);
    font-size: .72rem;
    font-weight: 650;
    letter-spacing: .035em;
    text-transform: uppercase;
}
.iav-field input {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--iav-border);
    border-radius: 11px;
    background: var(--iav-surface-warm);
    color: var(--iav-text);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 560;
    padding: 0 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.iav-field input:focus {
    border-color: var(--iav-gold);
    background: #fff;
    box-shadow: 0 0 0 3px var(--iav-ring);
    outline: none;
}
.iav-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    min-height: 44px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--iav-gold) 86%, #fff), color-mix(in srgb, var(--iav-gold) 72%, var(--iav-brand)));
    color: #fff;
    cursor: pointer;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 650;
    line-height: 1;
    overflow: hidden;
    text-decoration: none;
    white-space: nowrap;
    box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--iav-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease, opacity .16s ease;
}
.iav-btn:hover:not(:disabled) { transform: translateY(-1px); }
.iav-btn:active:not(:disabled) { transform: translateY(0) scale(.98); }
.iav-btn:focus-visible { outline: 3px solid var(--iav-ring); outline-offset: 2px; }
.iav-btn:disabled {
    cursor: not-allowed;
    opacity: .58;
    box-shadow: none;
}
.iav-btn.sec {
    background: var(--iav-surface);
    color: var(--iav-gold-ink);
    border-color: var(--iav-gold-line);
    box-shadow: none;
}
.iav-btn-shine {
    position: absolute;
    inset: 0 auto 0 0;
    width: 42%;
    pointer-events: none;
    background: linear-gradient(100deg, transparent, rgba(255,255,255,.5), transparent);
    transform: translateX(-160%) skewX(-18deg);
}
.iav-btn:hover:not(:disabled) .iav-btn-shine {
    transition: transform .7s ease;
    transform: translateX(320%) skewX(-18deg);
}
.iav-btn-label {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
}
.iav-reading {
    overflow: hidden;
}
.iav-reading-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--iav-border);
    background: var(--iav-surface-warm);
}
.iav-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin: 0;
    color: var(--iav-muted);
    font-size: .78rem;
    font-weight: 560;
}
.iav-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    background: var(--iav-gold-soft);
    color: var(--iav-gold-ink);
    border: 1px solid var(--iav-gold-line);
    font-size: .74rem;
    font-weight: 650;
    white-space: nowrap;
}
.iav-read-date {
    color: var(--iav-heading);
    font-weight: 650;
}
.iav-card-body {
    padding: 24px 26px;
}
.iav-resumen {
    max-width: 760px;
    color: var(--iav-text);
    font-size: .98rem;
    line-height: 1.68;
}
.iav-resumen h3 {
    margin: 20px 0 9px;
    color: var(--iav-heading);
    font-family: var(--iav-serif);
    font-size: 1.38rem;
    font-weight: 650;
    line-height: 1.08;
}
.iav-resumen h3:first-child { margin-top: 0; }
.iav-resumen p { margin: 0 0 13px; }
.iav-resumen ul {
    display: grid;
    gap: 7px;
    margin: 0 0 14px;
    padding: 0;
    list-style: none;
}
.iav-resumen li {
    position: relative;
    padding-left: 22px;
}
.iav-resumen li::before {
    content: "";
    position: absolute;
    left: 1px;
    top: .7em;
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: var(--iav-gold);
    box-shadow: 0 0 0 4px var(--iav-gold-soft);
}
.iav-resumen strong {
    color: var(--iav-heading);
    font-weight: 650;
}
.iav-vacio {
    display: grid;
    justify-items: center;
    gap: 8px;
    padding: 48px 18px;
    text-align: center;
    color: var(--iav-muted);
    background: var(--iav-ivory-2);
}
.iav-vacio .ico {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: var(--iav-gold-soft);
    color: var(--iav-gold-ink);
    border: 1px solid var(--iav-gold-line);
    font-size: 1.25rem;
}
.iav-vacio strong {
    color: var(--iav-heading);
    font-size: 1rem;
    font-weight: 650;
}
.iav-vacio p {
    max-width: 34rem;
    margin: 0;
    font-size: .88rem;
    line-height: 1.55;
}

@media (max-width: 900px) {
    .iav-hero-section {
        grid-template-columns: minmax(0, 1fr);
        align-items: start;
        gap: 12px;
    }
    .iav-status-pill {
        justify-self: start;
    }
    .iav-toolbar {
        display: grid;
        grid-template-columns: 1fr;
    }
}
@media (max-width: 720px) {
    .iav-summary {
        grid-template-columns: 1fr;
    }
    .iav-toolbar form,
    .iav-field,
    .iav-btn {
        width: 100%;
    }
}
@media (max-width: 640px) {
    .iav {
        padding: 14px 12px 34px;
    }
    .iav-title-lockup {
        grid-template-columns: 42px minmax(0, 1fr);
        column-gap: 12px;
    }
    .iav-hero-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        font-size: 1rem;
    }
    .iav-title {
        font-size: 2rem;
    }
    .iav-panel-head,
    .iav-reading-head {
        display: grid;
        grid-template-columns: 1fr;
    }
    .iav-card-body {
        padding: 20px 16px;
    }
    .iav-resumen {
        font-size: .94rem;
    }
}
@media (prefers-reduced-motion: reduce) {
    .iav *,
    .iav *::before,
    .iav *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
    .iav-btn-shine {
        display: none;
    }
}
</style>

<div class="iav">
    <div class="iav-shell">
        <section class="iav-hero-section">
            <div class="iav-title-lockup">
                <div class="iav-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div>
                    <p class="iav-kicker">Briefing gerencial</p>
                    <h1 class="iav-title">Asesor inteligente</h1>
                    <p class="iav-subtitle">Tu resumen gerencial del d&iacute;a, narrado y accionable. Generado a partir de los datos reales del hotel.</p>
                </div>
            </div>
            <span class="iav-status-pill">
                <i class="fa-solid <?= $hayResumen ? 'fa-circle-check' : 'fa-clock' ?>" aria-hidden="true"></i>
                <?= $iaSafe($estadoTexto) ?>
            </span>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="iav-alert is-<?= $iaSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <section class="iav-summary" aria-label="Resumen del asesor">
            <div class="iav-summary-item">
                <div class="iav-summary-label">D&iacute;a consultado</div>
                <div class="iav-summary-value"><?= $iaSafe($fechaHumana) ?></div>
            </div>
            <div class="iav-summary-item">
                <div class="iav-summary-label">Estado</div>
                <div class="iav-summary-value <?= $hayResumen ? 'is-ok' : 'is-pending' ?>"><?= $iaSafe($estadoTexto) ?></div>
            </div>
            <div class="iav-summary-item">
                <div class="iav-summary-label">Motor IA</div>
                <div class="iav-summary-value <?= $configurado ? 'is-ok' : 'is-pending' ?>"><?= $iaSafe($configTexto) ?></div>
            </div>
        </section>

        <section class="iav-panel">
            <div class="iav-panel-head">
                <div>
                    <h2 class="iav-panel-title">Consulta y generaci&oacute;n</h2>
                    <p class="iav-panel-sub">Selecciona el d&iacute;a que quieres revisar y actualiza el briefing cuando necesites una lectura fresca.</p>
                </div>
            </div>
            <div class="iav-toolbar">
                <form method="GET" action="<?= url('ia/resumen-diario') ?>">
                    <div class="iav-field">
                        <label for="iav-fecha">D&iacute;a del resumen</label>
                        <input type="date" id="iav-fecha" name="fecha" value="<?= $iaSafe($fecha) ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <button type="submit" class="iav-btn sec">
                        <span class="iav-btn-label"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i>Ver resumen</span>
                    </button>
                </form>
                <form method="POST" action="<?= url('ia/regenerar-resumen') ?>"
                      onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Generando...'; return true;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="fecha" value="<?= $iaSafe($fecha) ?>">
                    <button type="submit" class="iav-btn" <?= $configurado ? '' : 'disabled title="El asesor inteligente no esta configurado en el servidor."' ?>>
                        <span class="iav-btn-shine" aria-hidden="true"></span>
                        <span class="iav-btn-label"><i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i><?= $iaSafe($ctaTexto) ?></span>
                    </button>
                </form>
            </div>
        </section>

        <article class="iav-reading">
            <div class="iav-reading-head">
                <p class="iav-meta">
                    <span class="iav-chip"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>Asesor inteligente</span>
                    <span class="iav-read-date">Resumen del <?= $iaSafe($fechaHumana) ?></span>
                    <?php if ($generadoHumano !== ''): ?>
                        <span>Generado el <?= $iaSafe($generadoHumano) ?></span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($hayResumen): ?>
                <div class="iav-card-body">
                    <div class="iav-resumen"><?= $iaMarkdown($resultado['resumen'] ?? '') ?></div>
                </div>
            <?php else: ?>
                <div class="iav-vacio">
                    <div class="ico" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                    <strong>A&uacute;n no hay resumen para este d&iacute;a</strong>
                    <p><?= $iaSafe($resultado['message'] ?? 'Usa el boton "Generar resumen" para crear el briefing del dia.') ?></p>
                </div>
            <?php endif; ?>
        </article>
    </div>
</div>
