<?php
/**
 * Forecast de ocupacion (bloque forecast). Solo lectura.
 */
$totalHabitaciones = (int) ($totalHabitaciones ?? 0);
$porDia = $porDia ?? [];
$kpis = $kpis ?? ['ocupacion_30' => null, 'ocupacion_60' => null, 'ocupacion_90' => null];
$pickup = $pickup ?? [];
$semanas = $semanas ?? [];

$fcSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$fcPct = static function ($v) {
    return $v !== null ? number_format((float) $v, 1) . '%' : '—';
};
$fcFecha = static function ($iso) {
    $ts = strtotime((string) $iso . ' 12:00:00');
    return $ts ? date('d/m', $ts) : (string) $iso;
};

if (!function_exists('fc_ocup_class')) {
    function fc_ocup_class($ocup)
    {
        if ($ocup === null) {
            return 'is-soft';
        }
        if ($ocup >= 66) {
            return 'is-success';
        }
        if ($ocup >= 33) {
            return 'is-warning';
        }
        return 'is-danger';
    }
}

$reservas7 = (int) ($pickup['reservas_7'] ?? 0);
$reservasPrev = (int) ($pickup['reservas_prev'] ?? 0);
$deltaPickup = $reservas7 - $reservasPrev;

$dias30 = array_slice($porDia, 0, 30, true);

// Copiloto IA (bloque copiloto_ia): teaser visible aunque el hotel no tenga el
// bloque (el servidor administra la prueba gratis); requiere IA en el servidor.
$fcIaOk = trim((string) (getenv('ANTHROPIC_API_KEY') ?: '')) !== '';
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.fc {
    --fc-brand: var(--brand-primary, #1B2746);
    --fc-brand-2: var(--brand-secondary, #0F172A);
    --fc-gold: var(--brand-accent, #BD9441);
    --fc-gold-soft: color-mix(in srgb, var(--fc-gold) 15%, #FFFFFF);
    --fc-gold-line: color-mix(in srgb, var(--fc-gold) 42%, #E4D4B0);
    --fc-gold-ink: color-mix(in srgb, var(--fc-gold) 58%, var(--fc-brand));
    --fc-ivory: #F6F2EA;
    --fc-ivory-2: #FBF8F2;
    --fc-surface: #FFFFFF;
    --fc-surface-warm: #FCFAF5;
    --fc-border: color-mix(in srgb, var(--fc-brand) 6%, #E9E1D6);
    --fc-ring: color-mix(in srgb, var(--fc-gold) 32%, transparent);
    --fc-text: color-mix(in srgb, var(--fc-brand) 46%, #707B8C);
    --fc-muted: #8791A2;
    --fc-heading: color-mix(in srgb, var(--fc-brand) 66%, #566172);
    --fc-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --fc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --fc-success: #1E9E63; --fc-success-bg: #E7F4EC;
    --fc-warning: #C2841C; --fc-warning-bg: #FAF0DC;
    --fc-danger: #B4392B; --fc-danger-bg: #F8EAE5;
    --fc-info: #2F77E0; --fc-info-bg: #E6EFFC;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--fc-text);
    font-family: var(--fc-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--fc-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--fc-ivory-2), var(--fc-ivory));
}
.fc * { box-sizing: border-box; }
.fc-shell { display: grid; gap: 14px; width: 100%; max-width: 1120px; min-width: 0; margin: 0 auto; }

.fc-hero-section { display: grid; grid-template-columns: minmax(0, 1fr); align-items: center; gap: 16px 28px; padding: 2px 0 6px; }
.fc-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; max-width: min(100%, 760px); }
.fc-title-lockup > div:last-child { min-width: 0; }
.fc-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--fc-gold), var(--fc-brand) 54%, color-mix(in srgb, var(--fc-brand) 68%, var(--fc-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--fc-brand) 72%, transparent);
}
.fc-kicker { margin: 0 0 2px; color: var(--fc-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.fc-title { margin: 0; color: var(--fc-heading); font-family: var(--fc-serif); font-size: clamp(2.1rem, 4vw, 3rem); font-weight: 650; line-height: .98; overflow-wrap: anywhere; }
.fc-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--fc-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.fc-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.fc-summary-item { background: rgba(255,255,255,.82); border: 1px solid var(--fc-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18); }
.fc-summary-label { color: var(--fc-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.fc-summary-value { margin-top: 2px; color: var(--fc-heading); font-family: var(--fc-serif); font-size: 1.7rem; font-weight: 650; line-height: 1.1; }
.fc-summary-delta { display: inline-flex; align-items: center; gap: 3px; margin-left: 6px; font-family: var(--fc-sans); font-size: .74rem; font-weight: 700; vertical-align: 2px; }
.fc-summary-delta.is-up { color: color-mix(in srgb, var(--fc-success) 70%, var(--fc-text)); }
.fc-summary-delta.is-down { color: color-mix(in srgb, var(--fc-danger) 72%, var(--fc-text)); }

.fc-panel { background: rgba(255,255,255,.86); border: 1px solid var(--fc-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); overflow: hidden; }
.fc-panel-head { padding: 14px 16px 0; }
.fc-panel-title { margin: 0; color: var(--fc-heading); font-size: .9rem; font-weight: 650; }
.fc-panel-sub { margin: 4px 0 0; color: var(--fc-muted); font-size: .78rem; font-weight: 500; line-height: 1.45; }
.fc-chip { display: inline-flex; align-items: center; gap: 6px; padding: 3px 10px; margin-left: 8px; border-radius: 999px; background: var(--fc-gold-soft); color: var(--fc-gold-ink); border: 1px solid var(--fc-gold-line); font-size: .68rem; font-weight: 700; letter-spacing: .02em; vertical-align: 2px; white-space: nowrap; }

.fc-barras { display: flex; align-items: flex-end; gap: 3px; height: 140px; padding: 16px 16px 6px; }
.fc-barra { flex: 1; min-width: 4px; background: linear-gradient(180deg, var(--fc-brand), color-mix(in srgb, var(--fc-brand) 70%, #000)); border-radius: 4px 4px 0 0; position: relative; transition: transform .16s ease; }
.fc-barra.finde { background: linear-gradient(180deg, var(--fc-gold), color-mix(in srgb, var(--fc-gold) 74%, var(--fc-brand))); }
.fc-barra:hover { transform: scaleY(1.03); }
.fc-barra:hover::after {
    content: attr(data-tip); position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%);
    margin-bottom: 6px; background: var(--fc-brand); color: #fff; font-size: .7rem; font-weight: 650;
    padding: 4px 9px; border-radius: 7px; white-space: nowrap; z-index: 5; box-shadow: 0 8px 18px -10px rgba(27,39,70,.5);
}
.fc-ejes { display: flex; justify-content: space-between; padding: 0 16px 16px; font-size: .72rem; color: var(--fc-muted); font-weight: 650; }

.fc-table-wrap { overflow-x: auto; padding: 0 0 4px; }
.fc-table { width: 100%; min-width: 460px; border-collapse: collapse; font-size: .85rem; }
.fc-table thead { background: var(--fc-surface-warm); border-bottom: 1px solid var(--fc-border); border-top: 1px solid var(--fc-border); }
.fc-table th { padding: 11px 16px; color: var(--fc-muted); font-size: .68rem; font-weight: 650; letter-spacing: .06em; text-align: left; text-transform: uppercase; white-space: nowrap; }
.fc-table td { padding: 12px 16px; border-bottom: 1px solid var(--fc-border); white-space: nowrap; }
.fc-table tbody tr:last-child td { border-bottom: 0; }
.fc-cell-strong { font-weight: 650; color: var(--fc-heading); }
.fc-cell-muted { color: var(--fc-muted); }

.fc-delta-pos, .fc-delta-neg { display: inline-flex; align-items: center; gap: 3px; font-weight: 700; }
.fc-delta-pos { color: color-mix(in srgb, var(--fc-success) 70%, var(--fc-text)); }
.fc-delta-neg { color: color-mix(in srgb, var(--fc-danger) 72%, var(--fc-text)); }

.fc-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 58px; padding: 4px 10px; border-radius: 999px; font-size: .78rem; font-weight: 700; border: 1px solid transparent; }
.fc-badge.is-success { color: color-mix(in srgb, var(--fc-success) 70%, var(--fc-text)); background: var(--fc-success-bg); border-color: color-mix(in srgb, var(--fc-success) 24%, #fff); }
.fc-badge.is-warning { color: color-mix(in srgb, var(--fc-warning) 72%, var(--fc-text)); background: var(--fc-warning-bg); border-color: color-mix(in srgb, var(--fc-warning) 26%, #fff); }
.fc-badge.is-danger { color: color-mix(in srgb, var(--fc-danger) 72%, var(--fc-text)); background: var(--fc-danger-bg); border-color: color-mix(in srgb, var(--fc-danger) 24%, #fff); }
.fc-badge.is-soft { color: var(--fc-muted); background: var(--fc-surface-warm); border-color: var(--fc-border); }

/* Copiloto IA — hooks fcia-* consumidos por el JS existente, solo se re-estilizan */
.fcia-controles { padding: 14px 16px 16px; }
.fc-btn, .fcia-btn {
    position: relative; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 16px;
    border: 1px solid transparent; border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--fc-gold) 86%, #fff), color-mix(in srgb, var(--fc-gold) 72%, var(--fc-brand)));
    color: #fff; cursor: pointer; font-family: inherit; font-size: .86rem; font-weight: 650; line-height: 1;
    text-decoration: none; white-space: nowrap; box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--fc-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.fc-btn:hover, .fcia-btn:hover { transform: translateY(-1px); }
.fc-btn:active, .fcia-btn:active { transform: translateY(0) scale(.98); }
.fc-btn.sec, .fcia-btn.sec { background: var(--fc-surface); color: var(--fc-gold-ink); border-color: var(--fc-gold-line); box-shadow: none; }
.fcia-panel { padding: 0 16px 16px; }
.fcia-texto { font-size: .9rem; color: var(--fc-text); line-height: 1.6; background: var(--fc-surface-warm); border: 1px solid var(--fc-border); border-radius: 12px; padding: 14px 16px; }
.fcia-meta { font-size: .76rem; color: var(--fc-muted); margin-top: 8px; line-height: 1.4; }
.fcia-botones { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
.fcia-upsell { font-size: .87rem; background: var(--fc-gold-soft); border: 1px solid var(--fc-gold-line); color: var(--fc-gold-ink); border-radius: 12px; padding: 13px 15px; line-height: 1.5; }
.fcia-error { font-size: .87rem; background: var(--fc-danger-bg); border: 1px solid color-mix(in srgb, var(--fc-danger) 24%, #fff); color: color-mix(in srgb, var(--fc-danger) 72%, var(--fc-text)); border-radius: 12px; padding: 11px 14px; }

/* Sugerencias accionables del consejo (Fase 2): tarjeta por ventana */
.fcia-cards { display: grid; gap: 10px; margin-top: 12px; }
.fcia-card { display: flex; flex-direction: column; gap: 9px; background: var(--fc-surface-warm); border: 1px solid var(--fc-border); border-radius: 14px; padding: 14px; }
.fcia-card-top { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
.fcia-accion { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 700; border: 1px solid transparent; }
.fcia-accion.subir { color: color-mix(in srgb, var(--fc-success) 70%, var(--fc-text)); background: var(--fc-success-bg); border-color: color-mix(in srgb, var(--fc-success) 24%, #fff); }
.fcia-accion.bajar { color: color-mix(in srgb, var(--fc-info) 74%, var(--fc-text)); background: var(--fc-info-bg); border-color: color-mix(in srgb, var(--fc-info) 24%, #fff); }
.fcia-accion.mantener { color: var(--fc-muted); background: var(--fc-surface); border-color: var(--fc-border); }
.fcia-pct { color: var(--fc-heading); font-family: var(--fc-serif); font-size: 1.55rem; font-weight: 700; line-height: 1; }
.fcia-ventana { margin-left: auto; color: var(--fc-muted); font-size: .72rem; font-weight: 650; letter-spacing: .05em; text-transform: uppercase; white-space: nowrap; }
.fcia-fechas { color: var(--fc-heading); font-size: .85rem; font-weight: 650; }
.fcia-motivo { color: var(--fc-muted); font-size: .8rem; line-height: 1.45; }
.fcia-preview { display: grid; gap: 4px; padding: 9px 11px; background: var(--fc-surface); border: 1px solid var(--fc-border); border-radius: 10px; font-size: .82rem; }
.fcia-preview-row { display: flex; justify-content: space-between; gap: 8px; }
.fcia-preview-row .np { font-weight: 700; color: var(--fc-heading); white-space: nowrap; }
.fcia-acciones-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 2px; }
.fcia-stepper { display: inline-flex; align-items: center; gap: 2px; border: 1px solid var(--fc-border); border-radius: 10px; background: var(--fc-surface); overflow: hidden; }
.fcia-step-btn { width: 38px; min-height: 38px; border: 0; background: transparent; color: var(--fc-gold-ink); font-size: 1rem; font-weight: 700; cursor: pointer; }
.fcia-step-btn:disabled { opacity: .35; cursor: default; }
.fcia-step-val { min-width: 44px; text-align: center; color: var(--fc-heading); font-weight: 700; font-size: .92rem; }
.fcia-exito { font-size: .87rem; background: var(--fc-success-bg); border: 1px solid color-mix(in srgb, var(--fc-success) 24%, #fff); color: color-mix(in srgb, var(--fc-success) 70%, var(--fc-text)); border-radius: 12px; padding: 12px 14px; line-height: 1.5; }
.fcia-exito a { color: inherit; font-weight: 700; }
.fcia-conflicto { font-size: .84rem; background: var(--fc-warning-bg); border: 1px solid color-mix(in srgb, var(--fc-warning) 26%, #fff); color: color-mix(in srgb, var(--fc-warning) 72%, var(--fc-text)); border-radius: 12px; padding: 12px 14px; line-height: 1.5; }
.fcia-conflicto a { color: inherit; font-weight: 700; }
@media (min-width: 760px) {
    .fcia-cards { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); }
}

@media (min-width: 900px) {
    .fc-hero-section { grid-template-columns: minmax(0, 1fr) auto; }
}
@media (max-width: 720px) {
    .fc-summary { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .fc { padding: 14px 12px 34px; }
    .fc-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .fc-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1.05rem; }
    .fc-title { font-size: 2rem; }
    .fc-summary { grid-template-columns: 1fr 1fr; gap: 8px; }
    .fc-barras { height: 110px; padding: 14px 12px 6px; }
    .fc-ejes { padding: 0 12px 14px; }
}
@media (prefers-reduced-motion: reduce) {
    .fc *, .fc *::before, .fc *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
}
</style>

<div class="fc">
    <div class="fc-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>

        <section class="fc-hero-section">
            <div class="fc-title-lockup">
                <div class="fc-hero-icon" aria-hidden="true"><i class="fas fa-chart-line"></i></div>
                <div>
                    <p class="fc-kicker">Proyecci&oacute;n de ocupaci&oacute;n</p>
                    <h1 class="fc-title">Forecast de ocupaci&oacute;n</h1>
                    <p class="fc-subtitle">C&oacute;mo pinta tu ocupaci&oacute;n hacia adelante y a qu&eacute; ritmo est&aacute;s vendiendo. Solo lectura: nada de esto modifica reservaciones.</p>
                </div>
            </div>
        </section>

        <section class="fc-summary" aria-label="Indicadores de forecast">
            <div class="fc-summary-item">
                <div class="fc-summary-label">Ocupaci&oacute;n pr&oacute;x. 30 d&iacute;as</div>
                <div class="fc-summary-value"><?= $fcPct($kpis['ocupacion_30']) ?></div>
            </div>
            <div class="fc-summary-item">
                <div class="fc-summary-label">Pr&oacute;x. 60 d&iacute;as</div>
                <div class="fc-summary-value"><?= $fcPct($kpis['ocupacion_60']) ?></div>
            </div>
            <div class="fc-summary-item">
                <div class="fc-summary-label">Pr&oacute;x. 90 d&iacute;as</div>
                <div class="fc-summary-value"><?= $fcPct($kpis['ocupacion_90']) ?></div>
            </div>
            <div class="fc-summary-item">
                <div class="fc-summary-label">Reservas &uacute;ltimos 7 d&iacute;as</div>
                <div class="fc-summary-value">
                    <?= $reservas7 ?>
                    <span class="fc-summary-delta <?= $deltaPickup >= 0 ? 'is-up' : 'is-down' ?>">
                        <?= $deltaPickup >= 0 ? '▲' : '▼' ?> <?= abs($deltaPickup) ?>
                    </span>
                </div>
            </div>
        </section>

        <section class="fc-panel">
            <div class="fc-panel-head">
                <h2 class="fc-panel-title">Pr&oacute;ximos 30 d&iacute;as, d&iacute;a por d&iacute;a</h2>
                <p class="fc-panel-sub"><?= (int) $totalHabitaciones ?> habitaciones activas &middot; barras doradas = fin de semana &middot; pasa el cursor para el detalle</p>
            </div>
            <div class="fc-barras">
                <?php foreach ($dias30 as $fecha => $ocupadas): ?>
                    <?php
                    $pct = $totalHabitaciones > 0 ? min(100, round($ocupadas * 100 / $totalHabitaciones)) : 0;
                    $altura = max(3, $pct);
                    $diaSemana = (int) date('N', strtotime($fecha . ' 12:00:00'));
                    ?>
                    <div class="fc-barra <?= $diaSemana >= 5 ? 'finde' : '' ?>"
                         style="height: <?= $altura ?>%;"
                         data-tip="<?= $fcSafe($fcFecha($fecha)) ?>: <?= (int) $ocupadas ?> hab &middot; <?= $pct ?>%"></div>
                <?php endforeach; ?>
            </div>
            <div class="fc-ejes">
                <span><?= $fcSafe($fcFecha(array_key_first($dias30) ?? '')) ?></span>
                <span><?= $fcSafe($fcFecha(array_key_last($dias30) ?? '')) ?></span>
            </div>
        </section>

        <section class="fc-panel">
            <div class="fc-panel-head">
                <h2 class="fc-panel-title">Ritmo de ventas (pickup)</h2>
                <p class="fc-panel-sub">Reservas tomadas, sin importar para qu&eacute; fechas son.</p>
            </div>
            <div class="fc-table-wrap">
                <table class="fc-table">
                    <thead>
                        <tr><th></th><th>Reservas</th><th>Noches-habitaci&oacute;n</th><th>Monto</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fc-cell-strong">&Uacute;ltimos 7 d&iacute;as</td>
                            <td><?= $reservas7 ?></td>
                            <td><?= (int) ($pickup['noches_7'] ?? 0) ?></td>
                            <td>$<?= number_format((float) ($pickup['monto_7'] ?? 0), 2) ?></td>
                        </tr>
                        <tr>
                            <td class="fc-cell-muted">7 d&iacute;as anteriores</td>
                            <td class="fc-cell-muted"><?= $reservasPrev ?></td>
                            <td class="fc-cell-muted"><?= (int) ($pickup['noches_prev'] ?? 0) ?></td>
                            <td class="fc-cell-muted">$<?= number_format((float) ($pickup['monto_prev'] ?? 0), 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($fcIaOk): ?>
        <section class="fc-panel">
            <div class="fc-panel-head">
                <h2 class="fc-panel-title">Consejo de tarifa<span class="fc-chip"><i class="fa-solid fa-wand-magic-sparkles"></i>Copiloto IA</span></h2>
                <p class="fc-panel-sub">Lee tu proyecci&oacute;n, tu ritmo de ventas y la comparativa anual, y te sugiere si conviene subir, mantener o bajar tarifa. La decisi&oacute;n siempre es tuya.</p>
            </div>
            <div class="fcia-controles">
                <button type="button" id="fcia-generar" class="fc-btn">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>Ver el consejo de hoy
                </button>
            </div>
            <div id="fcia-consejo" class="fcia-panel" hidden></div>
        </section>
        <?php endif; ?>

        <section class="fc-panel">
            <div class="fc-panel-head">
                <h2 class="fc-panel-title">Pr&oacute;ximas 12 semanas vs el a&ntilde;o pasado</h2>
                <p class="fc-panel-sub">La columna "hace 1 a&ntilde;o" usa la ocupaci&oacute;n real que tuviste en esas mismas fechas del a&ntilde;o anterior.</p>
            </div>
            <div class="fc-table-wrap">
                <table class="fc-table">
                    <thead>
                        <tr><th>Semana</th><th>Noches vendidas</th><th>Ocupaci&oacute;n proyectada</th><th>Hace 1 a&ntilde;o</th><th>Diferencia</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($semanas as $sem): ?>
                        <?php $ocup = $sem['ocupacion']; ?>
                        <tr>
                            <td class="fc-cell-strong"><?= $fcSafe($fcFecha($sem['inicio'])) ?> &ndash; <?= $fcSafe($fcFecha($sem['fin'])) ?></td>
                            <td><?= (int) $sem['noches'] ?></td>
                            <td><span class="fc-badge <?= fc_ocup_class($ocup) ?>"><?= $fcPct($ocup) ?></span></td>
                            <td class="fc-cell-muted"><?= $fcPct($sem['ocupacion_anterior']) ?></td>
                            <td>
                                <?php if ($sem['delta'] === null): ?>
                                    <span class="fc-cell-muted">—</span>
                                <?php else: ?>
                                    <span class="<?= $sem['delta'] >= 0 ? 'fc-delta-pos' : 'fc-delta-neg' ?>">
                                        <?= $sem['delta'] >= 0 ? '▲' : '▼' ?> <?= number_format(abs((float) $sem['delta']), 1) ?> pts
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<?php if ($fcIaOk): ?>
<script>
(function () {
    'use strict';
    var URL_TARIFA = <?= json_encode(url('copiloto-ia/tarifa')) ?>;
    var URL_APLICAR = <?= json_encode(url('copiloto-ia/tarifa/aplicar')) ?>;
    var TOKEN = <?= json_encode(function_exists('csrf_token') ? csrf_token() : '') ?>;

    function post(url, params, cb) {
        var datos = new URLSearchParams();
        datos.append('csrf_token', TOKEN);
        Object.keys(params).forEach(function (k) { datos.append(k, params[k]); });
        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': TOKEN, 'X-Requested-With': 'XMLHttpRequest' },
            body: datos.toString()
        }).then(function (r) { return r.json(); }).then(cb).catch(function () {
            cb({ success: false, message: 'No se pudo conectar. Revisa tu internet e intenta de nuevo.' });
        });
    }

    // Texto de la IA: se escapa todo y solo se permiten **negritas** y saltos de linea.
    function iaHtml(t) {
        var d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>');
    }

    var btn = document.getElementById('fcia-generar');
    var cont = document.getElementById('fcia-consejo');
    if (!btn || !cont) { return; }

    // ── Sugerencias accionables (Fase 2): tarjeta por ventana ──
    var MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    function el(tag, clase, texto) {
        var n = document.createElement(tag);
        if (clase) { n.className = clase; }
        if (texto != null) { n.textContent = texto; }
        return n;
    }
    function fmtFecha(iso) {
        var p = String(iso || '').split('-');
        return p.length === 3 ? parseInt(p[2], 10) + ' ' + (MESES[parseInt(p[1], 10) - 1] || '') : iso;
    }
    function fmtDinero(n) {
        return '$' + Number(n).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtPct(n) {
        return String(Math.round(n * 10) / 10).replace(/\.0$/, '');
    }

    function tarjeta(s, data) {
        var esMantener = s.accion === 'mantener';
        var dir = s.accion === 'bajar' ? -1 : 1;
        var pctMax = Number(data.pct_max) || 15;
        var pct = Math.min(pctMax, Math.max(1, Math.round(s.pct)));

        var card = el('div', 'fcia-card');
        var top = el('div', 'fcia-card-top');
        var badge = el('span', 'fcia-accion ' + s.accion,
            esMantener ? '· Mantener tarifa' : (s.accion === 'subir' ? '▲ Subir tarifa' : '▼ Bajar tarifa'));
        top.appendChild(badge);
        var pctEl = null;
        if (!esMantener) {
            pctEl = el('span', 'fcia-pct', (dir < 0 ? '−' : '+') + fmtPct(pct) + '%');
            top.appendChild(pctEl);
        }
        top.appendChild(el('span', 'fcia-ventana', 'Próx. ' + s.ventana + ' días'));
        card.appendChild(top);

        card.appendChild(el('div', 'fcia-fechas', fmtFecha(s.desde) + ' – ' + fmtFecha(s.hasta)));
        if (s.motivo) { card.appendChild(el('div', 'fcia-motivo', s.motivo)); }
        if (esMantener) { return card; }

        // Preview real de precios (viene del servidor solo con permiso de tarifas)
        var previewFilas = [];
        if (s.preview && s.preview.length) {
            var pv = el('div', 'fcia-preview');
            s.preview.forEach(function (p) {
                var row = el('div', 'fcia-preview-row');
                row.appendChild(el('span', null, p.nombre));
                var np = el('span', 'np');
                row.appendChild(np);
                pv.appendChild(row);
                previewFilas.push({ p: p, np: np });
            });
            card.appendChild(pv);
        }

        function nuevoPrecio(p) { return Math.max(0, p.actual + dir * pct * p.delta_punto); }
        function pintarPreview() {
            previewFilas.forEach(function (f) {
                f.np.textContent = fmtDinero(f.p.actual) + ' → ' + fmtDinero(nuevoPrecio(f.p));
            });
        }
        function repintar() {
            if (pctEl) { pctEl.textContent = (dir < 0 ? '−' : '+') + fmtPct(pct) + '%'; }
            pintarPreview();
            menos.disabled = pct <= 1;
            mas.disabled = pct >= pctMax;
        }

        // Sin permiso de tarifas: la tarjeta informa, pero no hay stepper ni boton.
        if (!data.puede_aplicar) { pintarPreview(); return card; }

        var fila = el('div', 'fcia-acciones-row');
        var stepper = el('div', 'fcia-stepper');
        var menos = el('button', 'fcia-step-btn', '−');
        var val = el('span', 'fcia-step-val', fmtPct(pct) + '%');
        var mas = el('button', 'fcia-step-btn', '+');
        menos.type = 'button'; mas.type = 'button';
        menos.setAttribute('aria-label', 'Bajar un punto porcentual');
        mas.setAttribute('aria-label', 'Subir un punto porcentual');
        stepper.appendChild(menos); stepper.appendChild(val); stepper.appendChild(mas);
        fila.appendChild(stepper);

        var aplicar = el('button', 'fc-btn fcia-aplicar', 'Aplicar ajuste');
        aplicar.type = 'button';
        fila.appendChild(aplicar);
        card.appendChild(fila);

        menos.addEventListener('click', function () { if (pct > 1) { pct--; val.textContent = fmtPct(pct) + '%'; repintar(); } });
        mas.addEventListener('click', function () { if (pct < pctMax) { pct++; val.textContent = fmtPct(pct) + '%'; repintar(); } });

        aplicar.addEventListener('click', function () {
            var resumen = 'Se creará un ajuste de tarifa: ' + (dir < 0 ? 'bajar' : 'subir') + ' ' + fmtPct(pct) + '%'
                + ' del ' + fmtFecha(s.desde) + ' al ' + fmtFecha(s.hasta) + ' (todas las habitaciones).';
            if (previewFilas.length) {
                resumen += ' Ejemplo: ' + previewFilas.map(function (f) {
                    return f.p.nombre + ' ' + fmtDinero(f.p.actual) + ' → ' + fmtDinero(nuevoPrecio(f.p));
                }).join(' · ') + '.';
            }
            resumen += ' Podrás editarlo o borrarlo en Tarifas como cualquier otro ajuste.';

            window.msConfirm({
                type: 'warning',
                icon: 'money',
                title: 'Aplicar ajuste del Copiloto',
                msg: resumen,
                confirmLabel: 'Sí, crear ajuste'
            }).then(function (ok) {
                if (!ok) { return; }
                aplicar.disabled = true;
                aplicar.textContent = 'Creando…';
                post(URL_APLICAR, { ventana: s.ventana, pct: pct }, function (r) {
                    if (r.success) {
                        var exito = el('div', 'fcia-exito');
                        exito.appendChild(el('span', null, '✓ Ajuste creado: ' + (r.nombre || '') + '. Las cotizaciones de esas fechas ya lo aplican. '));
                        var link = el('a', null, 'Ver en tarifas');
                        link.href = r.url_tarifas || data.url_tarifas || '#';
                        exito.appendChild(link);
                        card.replaceChildren(top, exito);
                        if (window.msToast) { window.msToast('success', 'Tarifa ajustada', r.message || 'Ajuste creado.'); }
                        return;
                    }
                    aplicar.disabled = false;
                    aplicar.textContent = 'Aplicar ajuste';
                    if (r.conflicto) {
                        var viejo = card.querySelector('.fcia-conflicto');
                        if (viejo) { viejo.remove(); }
                        var box = el('div', 'fcia-conflicto');
                        var lineas = (r.conflictos || []).map(function (c) {
                            return '“' + c.nombre + '” (' + c.valor + ') del ' + fmtFecha(c.desde) + (c.hasta ? ' al ' + fmtFecha(c.hasta) : ', permanente');
                        });
                        box.appendChild(el('span', null, 'Ya tienes un ajuste vigente en esas fechas: ' + (lineas.join('; ') || '') + '. No se apilan ajustes en automático. '));
                        var verLink = el('a', null, 'Ver tarifas');
                        verLink.href = data.url_tarifas || '#';
                        box.appendChild(verLink);
                        card.insertBefore(box, fila);
                        return;
                    }
                    if (window.msToast) { window.msToast('error', 'No se aplicó', r.message || 'Intenta de nuevo.'); }
                });
            });
        });

        repintar();
        return card;
    }

    function pintar(data) {
        if (data.success) {
            var partes = ['<div class="fcia-texto">' + iaHtml(data.texto) + '</div>'];
            partes.push('<div class="fcia-meta">' + (data.desde_cache ? 'Generado el ' + iaHtml(data.generado_en || '') : 'Recién generado') + ' · Sugerencia orientativa: ningún precio se cambia solo.</div>');
            if (data.prueba && typeof data.prueba.restantes === 'number') {
                partes.push('<div class="fcia-meta">✨ Prueba gratis del bloque Copiloto IA · te quedan <strong>' + data.prueba.restantes + '</strong> usos de esta función.</div>');
            }
            partes.push('<div class="fcia-botones"><button type="button" class="fcia-btn sec" id="fcia-regen">Regenerar</button></div>');
            cont.innerHTML = partes.join('');
            if (data.sugerencias && data.sugerencias.length) {
                var cards = el('div', 'fcia-cards');
                data.sugerencias.forEach(function (s) { cards.appendChild(tarjeta(s, data)); });
                var texto = cont.querySelector('.fcia-texto');
                if (texto) { texto.insertAdjacentElement('afterend', cards); }
            }
            document.getElementById('fcia-regen').addEventListener('click', function () { cargar(true); });
        } else if (data.upsell) {
            cont.innerHTML = '<div class="fcia-upsell">🔒 ' + iaHtml(data.message) + '</div>';
        } else {
            cont.innerHTML = '<div class="fcia-error">' + iaHtml(data.message || 'No se pudo generar. Intenta de nuevo.') + '</div>';
        }
    }

    function cargar(regen) {
        cont.hidden = false;
        cont.innerHTML = '<div class="fcia-meta">💡 Leyendo tu proyección y ritmo de ventas…</div>';
        post(URL_TARIFA, { regenerar: regen ? '1' : '0' }, pintar);
    }

    btn.addEventListener('click', function () { cargar(false); });
})();
</script>
<?php endif; ?>
