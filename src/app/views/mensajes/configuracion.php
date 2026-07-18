<?php
/**
 * Configuracion del bloque canal_whatsapp: que mensajes ofrece la cola,
 * datos del hotel que usan las plantillas y plantillas propias por tipo.
 */
$config = $config ?? [];
$plantillasSugeridas = $plantillasSugeridas ?? [];
$reputacionActiva = $reputacionActiva ?? false;
$configFaltante = $configFaltante ?? [];

$msjSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$msjTiposMeta = [
    'confirmacion' => ['confirmacion_activa', 'Confirmación de reserva', 'Al crear una reserva, ofrece confirmarla por WhatsApp con fechas, habitación y total.', 'fa-calendar-check'],
    'recordatorio' => ['recordatorio_activo', 'Recordatorio de llegada', 'Un día antes de la llegada: "te esperamos mañana", hora de check-in y cómo llegar.', 'fa-bell'],
    'anticipo' => ['anticipo_activo', 'Aviso de anticipo', 'Mensaje informativo con el monto sugerido y tus datos de depósito. No registra pagos.', 'fa-building-columns'],
    'encuesta' => ['encuesta_activa', 'Encuesta post-estancia', 'Al salir el huésped, agradece la visita y comparte el link de tu encuesta.', 'fa-star'],
];

$msjVariables = '{huesped} {hotel} {fecha_llegada} {fecha_salida} {habitacion} {total} {anticipo} {datos_deposito} {link_maps} {link_encuesta} {hora_checkin}';
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
.msj-shell { display: grid; gap: 14px; width: 100%; max-width: 860px; min-width: 0; margin: 0 auto; }
.msj-hero-section { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 12px 24px; padding: 2px 0 6px; }
.msj-kicker { margin: 0 0 2px; color: var(--msj-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.msj-title { margin: 0; color: var(--msj-heading); font-size: clamp(1.7rem, 3.4vw, 2.4rem); font-weight: 650; line-height: 1.02; overflow-wrap: anywhere; }
.msj-subtitle { max-width: 46rem; margin: 8px 0 0; color: var(--msj-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }
.msj-volver {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 40px;
    padding: 0 14px;
    border-radius: 999px;
    border: 1px solid var(--msj-border);
    background: var(--msj-surface);
    color: var(--msj-muted);
    font-size: .8rem;
    font-weight: 650;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .16s var(--msj-ease), color .16s ease, box-shadow .16s var(--msj-ease);
}
.msj-volver:hover { color: var(--msj-gold-ink); transform: translateY(-1px); box-shadow: 0 10px 18px -14px rgba(27,39,70,.35); }
.msj-alert { padding: 12px 14px; border-radius: 13px; font-size: .88rem; font-weight: 560; line-height: 1.45; }
.msj-alert.is-success { background: var(--msj-success-bg); color: color-mix(in srgb, var(--msj-success) 70%, var(--msj-text)); border: 1px solid color-mix(in srgb, var(--msj-success) 24%, #fff); }
.msj-alert.is-error { background: #F8EAE5; color: color-mix(in srgb, var(--msj-danger) 72%, var(--msj-text)); border: 1px solid color-mix(in srgb, var(--msj-danger) 24%, #fff); }
.msj-alert.is-info { background: var(--msj-gold-soft); color: var(--msj-gold-ink); border: 1px solid var(--msj-gold-line); }
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
.msj-panel {
    border: 1px solid var(--msj-border);
    border-radius: 16px;
    background: var(--msj-surface);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 12px 24px -22px rgba(27,39,70,.22);
    overflow: hidden;
}
.msj-panel-head { padding: 14px 16px 4px; }
.msj-panel-head h2 { margin: 0; color: var(--msj-heading); font-size: 1.05rem; font-weight: 650; }
.msj-panel-head p { margin: 4px 0 0; color: var(--msj-muted); font-size: .82rem; font-weight: 520; line-height: 1.5; }
.msj-panel-body { display: grid; gap: 12px; padding: 14px 16px 16px; }

/* Toggle de tipo: checkbox real + check que se rellena */
.msj-toggle {
    display: grid;
    grid-template-columns: 24px 34px minmax(0, 1fr);
    align-items: start;
    gap: 12px;
    padding: 12px;
    border: 1px solid var(--msj-border);
    border-radius: 13px;
    background: var(--msj-surface-warm);
    cursor: pointer;
    transition: border-color .16s ease, background .16s ease, box-shadow .16s var(--msj-ease);
}
.msj-toggle:has(input:checked) {
    border-color: color-mix(in srgb, var(--msj-gold) 46%, var(--msj-border));
    background: color-mix(in srgb, var(--msj-gold) 5%, var(--msj-surface));
    box-shadow: 0 0 0 1px color-mix(in srgb, var(--msj-gold) 30%, transparent) inset;
}
.msj-toggle.is-bloqueado { opacity: .62; cursor: not-allowed; }
.msj-toggle input { position: absolute; opacity: 0; width: 1px; height: 1px; }
.msj-toggle .caja {
    width: 24px;
    height: 24px;
    margin-top: 2px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    background: #fff;
    border: 2px solid color-mix(in srgb, var(--msj-muted) 34%, #E1E6EE);
    transition: all .18s var(--msj-ease);
}
.msj-toggle .caja i { opacity: 0; transform: scale(.4); transition: all .18s var(--msj-ease); color: #fff; font-size: .7rem; }
.msj-toggle input:checked ~ .caja {
    background: linear-gradient(135deg, var(--msj-gold), color-mix(in srgb, var(--msj-gold) 68%, var(--msj-brand)));
    border-color: transparent;
    box-shadow: 0 6px 14px -6px var(--msj-gold);
}
.msj-toggle input:checked ~ .caja i { opacity: 1; transform: scale(1); }
.msj-toggle input:focus-visible ~ .caja { outline: 3px solid var(--msj-ring); outline-offset: 2px; }
.msj-toggle .icono-tipo {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: .85rem;
    color: var(--msj-gold-ink);
    background: var(--msj-ivory-2);
    border: 1px solid var(--msj-border);
}
.msj-toggle .textos .nombre { margin: 0; color: var(--msj-heading); font-size: .92rem; font-weight: 650; }
.msj-toggle .textos .detalle { margin: 2px 0 0; color: var(--msj-muted); font-size: .8rem; font-weight: 520; line-height: 1.45; }
.msj-toggle .textos .nota { margin: 4px 0 0; color: color-mix(in srgb, var(--msj-warning) 80%, var(--msj-text)); font-size: .76rem; font-weight: 650; }

.msj-field { display: grid; gap: 5px; min-width: 0; }
.msj-field label { color: var(--msj-muted); font-size: .72rem; font-weight: 650; letter-spacing: .035em; text-transform: uppercase; }
.msj-control {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--msj-border);
    border-radius: 11px;
    background: var(--msj-surface-warm);
    color: var(--msj-text);
    font-family: inherit;
    font-size: .88rem;
    font-weight: 560;
    padding: 10px 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.msj-control::placeholder { color: color-mix(in srgb, var(--msj-muted) 82%, #B8C0CB); font-weight: 520; }
.msj-control:focus { border-color: var(--msj-gold); background: #fff; box-shadow: 0 0 0 3px var(--msj-ring); outline: none; }
textarea.msj-control { resize: vertical; line-height: 1.5; }
.msj-hint { margin: 0; color: var(--msj-muted); font-size: .78rem; font-weight: 500; line-height: 1.5; }
.msj-vars { padding: 10px 12px; border-radius: 11px; background: var(--msj-ivory-2); border: 1px dashed var(--msj-gold-line); color: var(--msj-muted); font-size: .78rem; font-weight: 560; line-height: 1.6; overflow-wrap: anywhere; }
.msj-vars code { color: var(--msj-gold-ink); font-weight: 650; }

.msj-plantilla { border: 1px solid var(--msj-border); border-radius: 13px; background: var(--msj-surface-warm); }
.msj-plantilla summary {
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 48px;
    padding: 0 14px;
    color: var(--msj-heading);
    font-size: .88rem;
    font-weight: 650;
    cursor: pointer;
    list-style: none;
    user-select: none;
}
.msj-plantilla summary::-webkit-details-marker { display: none; }
.msj-plantilla summary i.flecha { color: var(--msj-gold-ink); transition: transform .18s var(--msj-ease); }
.msj-plantilla[open] summary i.flecha { transform: rotate(90deg); }
.msj-plantilla summary .propia { margin-left: auto; }
.msj-plantilla summary:focus-visible { outline: 3px solid var(--msj-ring); outline-offset: -3px; border-radius: 13px; }
.msj-plantilla-body { display: grid; gap: 8px; padding: 0 14px 14px; }
.msj-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: .7rem; font-weight: 650; letter-spacing: .03em; white-space: nowrap; }
.msj-chip.is-gold { background: var(--msj-gold-soft); color: var(--msj-gold-ink); }

.msj-guardar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 48px;
    padding: 0 24px;
    border: 0;
    border-radius: 13px;
    color: #fff;
    background: linear-gradient(145deg, var(--msj-gold), color-mix(in srgb, var(--msj-gold) 68%, var(--msj-brand)));
    box-shadow: 0 14px 26px -12px color-mix(in srgb, var(--msj-gold) 78%, transparent);
    font-family: inherit;
    font-size: .92rem;
    font-weight: 650;
    cursor: pointer;
    transition: transform .16s var(--msj-ease), box-shadow .16s var(--msj-ease);
}
.msj-guardar:hover { transform: translateY(-1px); box-shadow: 0 16px 30px -12px color-mix(in srgb, var(--msj-gold) 88%, transparent); }
.msj-guardar:focus-visible { outline: 3px solid var(--msj-ring); outline-offset: 2px; }

@media (max-width: 640px) {
    .msj { padding: 14px 12px 34px; }
    .msj-hero-section { grid-template-columns: minmax(0, 1fr); }
    .msj-volver { justify-self: start; }
    .msj-guardar { width: 100%; }
}
@media (prefers-reduced-motion: reduce) {
    .msj *, .msj *::before, .msj *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
    }
}
</style>

<div class="msj">
    <div class="msj-shell">
        <section class="msj-hero-section">
            <div>
                <p class="msj-kicker">Canal WhatsApp · Configuración</p>
                <h1 class="msj-title">Así hablan tus mensajes</h1>
                <p class="msj-subtitle">Elige qué mensajes ofrece tu cola, captura los datos que usan y, si quieres, dales tu propia voz.</p>
            </div>
            <a class="msj-volver" href="<?= url('mensajes') ?>">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Volver a la cola
            </a>
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

        <?php if (!empty($configFaltante)): ?>
            <div class="msj-config-warn" role="status">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <div>
                    Hay mensajes activos que aún no pueden salir completos: captura
                    <?= in_array('datos_deposito', $configFaltante, true) ? 'tus datos de depósito' : '' ?><?= count($configFaltante) > 1 ? ' y ' : '' ?><?= in_array('link_maps', $configFaltante, true) ? 'tu link de Google Maps' : '' ?>
                    aquí abajo.
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= url('mensajes/configuracion/guardar') ?>" style="display: grid; gap: 14px;">
            <?= csrf_field() ?>

            <section class="msj-panel">
                <div class="msj-panel-head">
                    <h2>Qué mensajes ofrece tu cola</h2>
                    <p>Apaga los que tu hotel no use; desaparecen de la cola y de la ficha de la reservación.</p>
                </div>
                <div class="msj-panel-body">
                    <?php foreach ($msjTiposMeta as $tipo => [$campo, $nombre, $detalle, $icono]): ?>
                        <?php $bloqueada = $tipo === 'encuesta' && !$reputacionActiva; ?>
                        <label class="msj-toggle <?= $bloqueada ? 'is-bloqueado' : '' ?>">
                            <input type="checkbox" name="<?= $msjSafe($campo) ?>" value="1"
                                <?= !empty($config[$campo]) && !$bloqueada ? 'checked' : '' ?>
                                <?= $bloqueada ? 'disabled' : '' ?>>
                            <span class="caja" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
                            <span class="icono-tipo" aria-hidden="true"><i class="fa-solid <?= $msjSafe($icono) ?>"></i></span>
                            <span class="textos">
                                <span class="nombre" style="display: block;"><?= $msjSafe($nombre) ?></span>
                                <span class="detalle" style="display: block;"><?= $msjSafe($detalle) ?></span>
                                <?php if ($bloqueada): ?>
                                    <span class="nota" style="display: block;">Necesita el bloque Reputación y encuestas activo (ahí vive el link público).</span>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="msj-panel">
                <div class="msj-panel-head">
                    <h2>Datos que usan tus mensajes</h2>
                    <p>Se insertan solos donde la plantilla diga <code>{datos_deposito}</code> o <code>{link_maps}</code>.</p>
                </div>
                <div class="msj-panel-body">
                    <div class="msj-field">
                        <label for="msjDatosDeposito">Datos de depósito (para el aviso de anticipo)</label>
                        <textarea class="msj-control" id="msjDatosDeposito" name="datos_deposito" rows="3"
                            placeholder="Ej. BBVA · Cuenta 0123456789&#10;CLABE 012345678901234567&#10;A nombre de Hotel Ejemplo S.A."><?= $msjSafe($config['datos_deposito'] ?? '') ?></textarea>
                        <p class="msj-hint">Banco, cuenta o CLABE tal como quieres que el huésped los lea.</p>
                    </div>
                    <div class="msj-field">
                        <label for="msjLinkMaps">Link de Google Maps (para el recordatorio de llegada)</label>
                        <input class="msj-control" id="msjLinkMaps" name="link_maps" type="url" inputmode="url"
                            placeholder="https://maps.app.goo.gl/..." value="<?= $msjSafe($config['link_maps'] ?? '') ?>">
                        <p class="msj-hint">Abre tu hotel en Google Maps, toca Compartir y pega aquí el enlace.</p>
                    </div>
                </div>
            </section>

            <section class="msj-panel">
                <div class="msj-panel-head">
                    <h2>Plantillas</h2>
                    <p>Cada mensaje ya viene redactado en tono cálido y a nombre de tu hotel. Si quieres el tuyo, escríbelo; déjalo vacío para volver al sugerido.</p>
                </div>
                <div class="msj-panel-body">
                    <div class="msj-vars">
                        Variables disponibles (se rellenan solas con los datos de la reserva):<br>
                        <code><?= $msjSafe($msjVariables) ?></code>
                    </div>
                    <?php foreach ($msjTiposMeta as $tipo => [$campo, $nombre, $detalle, $icono]): ?>
                        <?php $propia = trim((string) ($config['plantilla_' . $tipo] ?? '')); ?>
                        <details class="msj-plantilla" <?= $propia !== '' ? 'open' : '' ?>>
                            <summary>
                                <i class="fa-solid fa-chevron-right flecha" aria-hidden="true"></i>
                                <i class="fa-solid <?= $msjSafe($icono) ?>" aria-hidden="true"></i>
                                <?= $msjSafe($nombre) ?>
                                <?php if ($propia !== ''): ?>
                                    <span class="msj-chip is-gold propia">Plantilla propia</span>
                                <?php endif; ?>
                            </summary>
                            <div class="msj-plantilla-body">
                                <textarea class="msj-control" name="plantilla_<?= $msjSafe($tipo) ?>" rows="6"
                                    placeholder="<?= $msjSafe($plantillasSugeridas[$tipo] ?? '') ?>"><?= $msjSafe($propia) ?></textarea>
                                <p class="msj-hint">Vacío = usar la plantilla sugerida (la que ves en gris).</p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>

            <div>
                <button type="submit" class="msj-guardar">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    Guardar configuración
                </button>
            </div>
        </form>
    </div>
</div>
