<?php
/**
 * Panel de valor del Copiloto (bloque copiloto). Solo gerencia, solo lectura:
 * todo sale del log copiloto_mensajes + notificaciones (cero tablas nuevas).
 */
$uso = $uso ?? [];
$nombreAsistente = trim((string) ($nombreAsistente ?? 'Copiloto'));
$iaDisponible = (bool) ($iaDisponible ?? false);

$cpvSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$cpvNum = static function ($v) {
    return number_format((int) $v);
};

$total = (int) ($uso['total'] ?? 0);
$fuentes = (array) ($uso['por_fuente'] ?? []);
$reglas = (int) ($fuentes['reglas'] ?? 0);
$ia = (int) ($fuentes['ia'] ?? 0);
$fallback = (int) ($fuentes['fallback'] ?? 0);
$pctReglas = $total > 0 ? (int) round($reglas * 100 / $total) : 0;
$accionesOk = (int) ($uso['acciones_ok'] ?? 0);
$avisos = (int) ($uso['briefings'] ?? 0) + (int) ($uso['alertas'] ?? 0);
$usuarios = (int) ($uso['usuarios'] ?? 0);
$serie = (array) ($uso['serie'] ?? []);
$top = (array) ($uso['top'] ?? []);
$dias = (int) ($uso['dias'] ?? 30);

$serieMax = 1;
foreach ($serie as $p) {
    $serieMax = max($serieMax, (int) $p['n']);
}
$topMax = !empty($top) ? max(1, (int) $top[0]['n']) : 1;

$diasCortos = ['Mon' => 'lun', 'Tue' => 'mar', 'Wed' => 'mié', 'Thu' => 'jue', 'Fri' => 'vie', 'Sat' => 'sáb', 'Sun' => 'dom'];
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.cpv {
    --cpv-brand: var(--brand-primary, #1B2746);
    --cpv-gold: var(--brand-accent, #BD9441);
    --cpv-gold-soft: color-mix(in srgb, var(--cpv-gold) 15%, #FFFFFF);
    --cpv-gold-line: color-mix(in srgb, var(--cpv-gold) 42%, #E4D4B0);
    --cpv-gold-ink: color-mix(in srgb, var(--cpv-gold) 58%, var(--cpv-brand));
    --cpv-ivory: #F6F2EA;
    --cpv-ivory-2: #FBF8F2;
    --cpv-surface: rgba(255,255,255,.86);
    --cpv-surface-warm: #FCFAF5;
    --cpv-border: color-mix(in srgb, var(--cpv-brand) 6%, #E9E1D6);
    --cpv-text: color-mix(in srgb, var(--cpv-brand) 46%, #707B8C);
    --cpv-muted: #8791A2;
    --cpv-heading: color-mix(in srgb, var(--cpv-brand) 66%, #566172);
    --cpv-track: color-mix(in srgb, var(--cpv-brand) 8%, #EFECE3);
    --cpv-warning: #C2841C; --cpv-warning-bg: #FAF0DC; --cpv-warning-line: #ECD9AE;
    --cpv-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--cpv-text);
    font-family: var(--cpv-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--cpv-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--cpv-ivory-2), var(--cpv-ivory));
}
.cpv * { box-sizing: border-box; }
.cpv-shell { display: grid; gap: 14px; width: 100%; max-width: 1120px; min-width: 0; margin: 0 auto; }

.cpv-hero { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; padding: 2px 0 6px; }
.cpv-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--cpv-gold), var(--cpv-brand) 54%, color-mix(in srgb, var(--cpv-brand) 68%, var(--cpv-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--cpv-brand) 72%, transparent);
}
.cpv-kicker { margin: 0 0 2px; color: var(--cpv-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.cpv-title { margin: 0; color: var(--cpv-heading); font-size: clamp(1.9rem, 4vw, 2.7rem); font-weight: 650; line-height: 1; overflow-wrap: anywhere; }
.cpv-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--cpv-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; grid-column: 1 / -1; }

.cpv-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.cpv-tile { background: var(--cpv-surface); border: 1px solid var(--cpv-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18); }
.cpv-tile-label { color: var(--cpv-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.cpv-tile-value { margin-top: 2px; color: var(--cpv-heading); font-size: 1.7rem; font-weight: 650; line-height: 1.1; }
.cpv-tile-hint { margin-top: 3px; color: var(--cpv-muted); font-size: .74rem; font-weight: 500; line-height: 1.35; }

.cpv-panel { background: var(--cpv-surface); border: 1px solid var(--cpv-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); overflow: hidden; }
.cpv-panel-head { padding: 14px 16px 0; }
.cpv-panel-title { margin: 0; color: var(--cpv-heading); font-size: .9rem; font-weight: 650; }
.cpv-panel-sub { margin: 4px 0 0; color: var(--cpv-muted); font-size: .78rem; font-weight: 500; line-height: 1.45; }

.cpv-cols { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 14px; align-items: start; }

.cpv-barras { display: flex; align-items: flex-end; gap: 5px; height: 130px; padding: 16px 16px 6px; }
.cpv-barra-col { flex: 1; min-width: 0; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 4px; }
.cpv-barra { width: 100%; max-width: 34px; min-height: 3px; border-radius: 5px 5px 2px 2px; position: relative; background: linear-gradient(180deg, color-mix(in srgb, var(--cpv-brand) 55%, #fff), var(--cpv-brand)); }
.cpv-barra.is-top { background: linear-gradient(180deg, color-mix(in srgb, var(--cpv-gold) 55%, #fff), var(--cpv-gold)); }
.cpv-barra:hover::after {
    content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
    margin-bottom: 6px; background: var(--cpv-brand); color: #fff; font-size: .7rem; font-weight: 650;
    padding: 4px 9px; border-radius: 7px; white-space: nowrap; z-index: 5; box-shadow: 0 8px 18px -10px rgba(27,39,70,.5);
}
.cpv-barra-lbl { color: var(--cpv-muted); font-size: .62rem; font-weight: 650; line-height: 1; white-space: nowrap; }
.cpv-barras-vacio { padding: 22px 16px 20px; color: var(--cpv-muted); font-size: .85rem; }

.cpv-lista { display: grid; gap: 9px; padding: 14px 16px 16px; }
.cpv-fila { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 4px 10px; }
.cpv-fila-lbl { color: var(--cpv-heading); font-size: .84rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cpv-fila-n { color: var(--cpv-gold-ink); font-size: .82rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.cpv-fila-track { grid-column: 1 / -1; display: block; height: 8px; border-radius: 999px; background: var(--cpv-track); overflow: hidden; }
.cpv-fila-fill { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, var(--cpv-brand), color-mix(in srgb, var(--cpv-brand) 62%, var(--cpv-gold))); }

.cpv-ia { display: grid; gap: 10px; padding: 14px 16px 16px; }
.cpv-ia-fila { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; border-bottom: 1px dashed var(--cpv-border); padding-bottom: 9px; }
.cpv-ia-fila:last-child { border-bottom: 0; padding-bottom: 0; }
.cpv-ia-k { color: var(--cpv-muted); font-size: .8rem; font-weight: 600; }
.cpv-ia-v { color: var(--cpv-heading); font-size: .96rem; font-weight: 650; font-variant-numeric: tabular-nums; }
.cpv-aviso { display: flex; gap: 10px; align-items: flex-start; margin: 0 16px 16px; padding: 11px 13px; border: 1px solid var(--cpv-warning-line); border-radius: 12px; background: var(--cpv-warning-bg); color: color-mix(in srgb, var(--cpv-warning) 72%, var(--cpv-text)); font-size: .8rem; line-height: 1.5; }
.cpv-aviso i { margin-top: 2px; }

.cpv-vacio { text-align: center; padding: 40px 20px 44px; }
.cpv-vacio-icono { width: 58px; height: 58px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; color: #fff; font-size: 1.35rem; background: linear-gradient(145deg, var(--cpv-gold), var(--cpv-brand)); }
.cpv-vacio-titulo { margin: 0; color: var(--cpv-heading); font-size: 1.15rem; font-weight: 650; }
.cpv-vacio-texto { max-width: 34rem; margin: 8px auto 0; color: var(--cpv-muted); font-size: .9rem; line-height: 1.55; }

@media (max-width: 860px) {
    .cpv-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .cpv-cols { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 560px) {
    .cpv { padding: 14px 12px 40px; }
    .cpv-barras { gap: 3px; }
    .cpv-barra-lbl:nth-child(odd) { font-size: .58rem; }
}

/* Modo oscuro: mismas superficies que el resto del cromado nocturno. */
html[data-theme="dark"] .cpv {
    --cpv-surface: rgba(33,31,26,.92);
    --cpv-surface-warm: #26231D;
    --cpv-border: rgba(239,233,220,.12);
    --cpv-text: #B8B2A4;
    --cpv-muted: #8A8478;
    --cpv-heading: #EFE9DC;
    --cpv-track: rgba(239,233,220,.1);
    --cpv-gold-ink: color-mix(in srgb, var(--cpv-gold) 70%, #EFE9DC);
    --cpv-warning-bg: rgba(194,132,28,.14);
    --cpv-warning-line: rgba(194,132,28,.36);
    background: linear-gradient(180deg, #191813, #14130F);
}
html[data-theme="dark"] .cpv-tile,
html[data-theme="dark"] .cpv-panel { box-shadow: 0 1px 2px rgba(0,0,0,.4), 0 14px 30px -27px rgba(0,0,0,.6); }
html[data-theme="dark"] .cpv-barra:hover::after { background: #26231D; }
</style>

<div class="cpv">
    <div class="cpv-shell">

        <header class="cpv-hero">
            <div class="cpv-hero-icon" aria-hidden="true"><i class="fas fa-robot"></i></div>
            <div>
                <p class="cpv-kicker">Asistente del hotel</p>
                <h1 class="cpv-title"><?= $cpvSafe($nombreAsistente) ?> en números</h1>
            </div>
            <p class="cpv-subtitle">
                Lo que tu equipo le preguntó y lo que resolvió en los últimos <?= (int) $dias ?> días:
                respuestas al instante, acciones ejecutadas desde el chat y avisos que se adelantaron al problema.
            </p>
        </header>

        <?php if ($total === 0 && $avisos === 0): ?>
        <section class="cpv-panel">
            <div class="cpv-vacio">
                <div class="cpv-vacio-icono" aria-hidden="true"><i class="fas fa-comment-dots"></i></div>
                <h2 class="cpv-vacio-titulo">Todavía no hay conversaciones</h2>
                <p class="cpv-vacio-texto">
                    Cuando tu equipo empiece a usar a <?= $cpvSafe($nombreAsistente) ?> aquí verás cuánto se usa,
                    qué se pregunta más y qué resolvió solo. Abre el asistente con el botón flotante y
                    pruébalo con "¿cómo pinta la semana?" o "¿quién llega hoy?".
                </p>
            </div>
        </section>
        <?php else: ?>

        <section class="cpv-summary" aria-label="Resumen de uso">
            <div class="cpv-tile">
                <div class="cpv-tile-label">Preguntas respondidas</div>
                <div class="cpv-tile-value"><?= $cpvNum($total) ?></div>
                <div class="cpv-tile-hint"><?= $cpvNum($usuarios) ?> persona(s) de tu equipo lo usaron</div>
            </div>
            <div class="cpv-tile">
                <div class="cpv-tile-label">Al instante</div>
                <div class="cpv-tile-value"><?= (int) $pctReglas ?>%</div>
                <div class="cpv-tile-hint"><?= $cpvNum($reglas) ?> respuestas con datos vivos, sin costo de IA</div>
            </div>
            <div class="cpv-tile">
                <div class="cpv-tile-label">Acciones ejecutadas</div>
                <div class="cpv-tile-value"><?= $cpvNum($accionesOk) ?></div>
                <div class="cpv-tile-hint">Limpiezas programadas o asignadas desde el chat</div>
            </div>
            <div class="cpv-tile">
                <div class="cpv-tile-label">Avisos proactivos</div>
                <div class="cpv-tile-value"><?= $cpvNum($avisos) ?></div>
                <div class="cpv-tile-hint"><?= $cpvNum((int) ($uso['briefings'] ?? 0)) ?> briefing(s) y <?= $cpvNum((int) ($uso['alertas'] ?? 0)) ?> alerta(s) push</div>
            </div>
        </section>

        <section class="cpv-panel" aria-label="Actividad diaria">
            <div class="cpv-panel-head">
                <h2 class="cpv-panel-title">Actividad de los últimos 14 días</h2>
                <p class="cpv-panel-sub">Preguntas por día; el día más activo va resaltado.</p>
            </div>
            <?php
            $haySerie = false;
            foreach ($serie as $p) {
                if ((int) $p['n'] > 0) {
                    $haySerie = true;
                    break;
                }
            }
            ?>
            <?php if (!$haySerie): ?>
                <p class="cpv-barras-vacio">Sin actividad en las últimas dos semanas. El histórico de los <?= (int) $dias ?> días de arriba sigue contando.</p>
            <?php else: ?>
            <div class="cpv-barras" aria-hidden="true">
                <?php foreach ($serie as $p):
                    $n = (int) $p['n'];
                    $pct = (int) round($n * 100 / $serieMax);
                    $ts = strtotime((string) $p['fecha'] . ' 12:00:00');
                    $lbl = ($diasCortos[date('D', $ts)] ?? '') . ' ' . date('j', $ts);
                ?>
                <div class="cpv-barra-col">
                    <div class="cpv-barra <?= $n === $serieMax && $n > 0 ? 'is-top' : '' ?>" style="height: <?= max($pct, 3) ?>%" data-tip="<?= $cpvSafe($lbl . ': ' . $n . ' pregunta(s)') ?>"></div>
                    <span class="cpv-barra-lbl"><?= $cpvSafe($lbl) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <div class="cpv-cols">
            <section class="cpv-panel" aria-label="Preguntas más frecuentes">
                <div class="cpv-panel-head">
                    <h2 class="cpv-panel-title">Lo que más se pregunta</h2>
                    <p class="cpv-panel-sub">Los temas reales de tu operación en los últimos <?= (int) $dias ?> días.</p>
                </div>
                <?php if (empty($top)): ?>
                    <p class="cpv-barras-vacio">Aún no hay preguntas suficientes para armar el ranking.</p>
                <?php else: ?>
                <div class="cpv-lista">
                    <?php foreach ($top as $t): ?>
                    <div class="cpv-fila">
                        <span class="cpv-fila-lbl"><?= $cpvSafe($t['etiqueta']) ?></span>
                        <span class="cpv-fila-n"><?= $cpvNum((int) $t['n']) ?></span>
                        <span class="cpv-fila-track"><span class="cpv-fila-fill" style="width: <?= max(4, (int) round((int) $t['n'] * 100 / $topMax)) ?>%"></span></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <section class="cpv-panel" aria-label="Asistencia de la IA">
                <div class="cpv-panel-head">
                    <h2 class="cpv-panel-title">Asistencia de la IA</h2>
                    <p class="cpv-panel-sub">Lo que no cae en una regla se responde con IA (si está encendida).</p>
                </div>
                <div class="cpv-ia">
                    <div class="cpv-ia-fila">
                        <span class="cpv-ia-k">Respuestas asistidas por IA</span>
                        <span class="cpv-ia-v"><?= $cpvNum($ia) ?></span>
                    </div>
                    <div class="cpv-ia-fila">
                        <span class="cpv-ia-k">Sin respuesta (sugerencias)</span>
                        <span class="cpv-ia-v"><?= $cpvNum($fallback) ?></span>
                    </div>
                    <div class="cpv-ia-fila">
                        <span class="cpv-ia-k">Tokens consumidos</span>
                        <span class="cpv-ia-v"><?= $cpvNum((int) ($uso['tokens_entrada'] ?? 0) + (int) ($uso['tokens_salida'] ?? 0)) ?></span>
                    </div>
                </div>
                <?php if (!$iaDisponible): ?>
                <div class="cpv-aviso" role="status">
                    <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                    <span>
                        La IA está apagada o la llave del servidor no es válida: esas preguntas hoy reciben una sugerencia.
                        Pide a Medisoft revisarla (diagnóstico: <code>tools/diagnosticar_ia.php</code>).
                    </span>
                </div>
                <?php endif; ?>
            </section>
        </div>

        <?php endif; ?>
    </div>
</div>
