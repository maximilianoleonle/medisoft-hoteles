<?php
/**
 * Huesped frecuente (bloque lealtad).
 */
$frecuentes = $frecuentes ?? [];
$config = $config ?? ['min_estancias' => 3, 'descuento_pct' => 10, 'vigencia_dias' => 90];
$urlMotor = $urlMotor ?? '';
$promocionesActivo = (bool) ($promocionesActivo ?? false);

$leSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

$totalFrecuentes = count($frecuentes);
$conCupon = 0;
$correosEnviados = 0;
foreach ($frecuentes as $__f) {
    if (!empty($__f['cupon_codigo'])) {
        $conCupon++;
    }
    if (!empty($__f['correo_enviado_at'])) {
        $correosEnviados++;
    }
}
$descuentoTexto = rtrim(rtrim(number_format((float) $config['descuento_pct'], 2), '0'), '.') . '%';
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.lea {
    --lea-brand: var(--brand-primary, #1B2746);
    --lea-brand-2: var(--brand-secondary, #0F172A);
    --lea-gold: var(--brand-accent, #BD9441);
    --lea-gold-soft: color-mix(in srgb, var(--lea-gold) 15%, #FFFFFF);
    --lea-gold-line: color-mix(in srgb, var(--lea-gold) 42%, #E4D4B0);
    --lea-gold-ink: color-mix(in srgb, var(--lea-gold) 58%, var(--lea-brand));
    --lea-ivory: #F6F2EA;
    --lea-ivory-2: #FBF8F2;
    --lea-surface: #FFFFFF;
    --lea-surface-warm: #FCFAF5;
    --lea-border: color-mix(in srgb, var(--lea-brand) 6%, #E9E1D6);
    --lea-ring: color-mix(in srgb, var(--lea-gold) 32%, transparent);
    --lea-text: color-mix(in srgb, var(--lea-brand) 46%, #707B8C);
    --lea-muted: #8791A2;
    --lea-heading: color-mix(in srgb, var(--lea-brand) 66%, #566172);
    --lea-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --lea-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --lea-success: #1E9E63; --lea-success-bg: #E7F4EC;
    --lea-warning: #C2841C; --lea-warning-bg: #FAF0DC;
    --lea-danger: #B4392B; --lea-danger-bg: #F8EAE5;
    --lea-info: #2F77E0; --lea-info-bg: #E6EFFC;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--lea-text);
    font-family: var(--lea-sans);
    font-size: .92rem;
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--lea-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--lea-ivory-2), var(--lea-ivory));
}
.lea * { box-sizing: border-box; }
.lea-shell { display: grid; gap: 14px; width: 100%; max-width: 1120px; min-width: 0; margin: 0 auto; }

.lea-hero-section { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: 16px 28px; padding: 2px 0 6px; }
.lea-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; max-width: min(100%, 760px); }
.lea-title-lockup > div:last-child { min-width: 0; }
.lea-hero-icon {
    width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--lea-gold), var(--lea-brand) 54%, color-mix(in srgb, var(--lea-brand) 68%, var(--lea-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--lea-brand) 72%, transparent);
}
.lea-kicker { margin: 0 0 2px; color: var(--lea-muted); font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.lea-title { margin: 0; color: var(--lea-heading); font-family: var(--lea-serif); font-size: clamp(2.1rem, 4vw, 3rem); font-weight: 650; line-height: .98; overflow-wrap: anywhere; }
.lea-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--lea-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }

.lea-alert { padding: 12px 14px; border-radius: 13px; font-size: .88rem; font-weight: 560; line-height: 1.45; }
.lea-alert.is-success { background: var(--lea-success-bg); color: color-mix(in srgb, var(--lea-success) 70%, var(--lea-text)); border: 1px solid color-mix(in srgb, var(--lea-success) 24%, #fff); }
.lea-alert.is-error { background: var(--lea-danger-bg); color: color-mix(in srgb, var(--lea-danger) 72%, var(--lea-text)); border: 1px solid color-mix(in srgb, var(--lea-danger) 24%, #fff); }

.lea-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--lea-gold-soft); border: 1px solid var(--lea-gold-line); border-radius: 16px; }
.lea-notice i { color: var(--lea-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.lea-notice strong { display: block; margin-bottom: 2px; color: var(--lea-heading); font-weight: 650; }
.lea-notice p { color: var(--lea-muted); font-size: .88rem; margin: 0; }

.lea-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.lea-summary-item { background: rgba(255,255,255,.82); border: 1px solid var(--lea-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18); }
.lea-summary-label { color: var(--lea-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.lea-summary-value { margin-top: 2px; color: var(--lea-heading); font-family: var(--lea-serif); font-size: 1.7rem; font-weight: 650; line-height: 1.1; }
.lea-summary-value.is-ok { color: color-mix(in srgb, var(--lea-success) 68%, var(--lea-text)); }

.lea-panel { background: rgba(255,255,255,.86); border: 1px solid var(--lea-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -27px rgba(27,39,70,.22); overflow: hidden; }
.lea-panel-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 14px 16px; border-bottom: 1px solid var(--lea-border); }
.lea-panel-title { margin: 0; color: var(--lea-heading); font-size: .9rem; font-weight: 650; }
.lea-panel-sub { max-width: 42rem; margin: 3px 0 0; color: var(--lea-muted); font-size: .78rem; font-weight: 500; line-height: 1.45; }
.lea-count-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .36rem .66rem; border-radius: 999px; background: color-mix(in srgb, var(--lea-gold) 10%, #FFFFFF); color: var(--lea-gold-ink); border: 1px solid color-mix(in srgb, var(--lea-gold) 28%, #ECE1D1); font-size: .72rem; font-weight: 650; white-space: nowrap; }

.lea-config-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)) auto; gap: 12px; align-items: end; padding: 16px; }
.lea-field { display: grid; gap: 5px; min-width: 0; }
.lea-field label { color: var(--lea-muted); font-size: .72rem; font-weight: 650; letter-spacing: .035em; text-transform: uppercase; }
.lea-field input {
    width: 100%; min-height: 44px; border: 1px solid var(--lea-border); border-radius: 11px; background: var(--lea-surface-warm);
    color: var(--lea-text); font-family: inherit; font-size: .88rem; font-weight: 560; padding: 0 12px;
    transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
}
.lea-field input:focus { border-color: var(--lea-gold); background: #fff; box-shadow: 0 0 0 3px var(--lea-ring); outline: none; }
.lea-config-hint { grid-column: 1 / -1; margin: 2px 0 0; color: var(--lea-muted); font-size: .76rem; line-height: 1.5; }

.lea-btn {
    position: relative; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 18px;
    border: 1px solid transparent; border-radius: 11px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--lea-gold) 86%, #fff), color-mix(in srgb, var(--lea-gold) 72%, var(--lea-brand)));
    color: #fff; cursor: pointer; font-family: inherit; font-size: .86rem; font-weight: 650; line-height: 1; overflow: hidden;
    text-decoration: none; white-space: nowrap; box-shadow: 0 12px 24px -14px color-mix(in srgb, var(--lea-gold) 42%, transparent);
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, border-color .16s ease, color .16s ease;
}
.lea-btn:hover { transform: translateY(-1px); }
.lea-btn:active { transform: translateY(0) scale(.98); }
.lea-btn:focus-visible { outline: 3px solid var(--lea-ring); outline-offset: 2px; }
.lea-btn.sec { background: var(--lea-surface); color: var(--lea-gold-ink); border-color: var(--lea-gold-line); box-shadow: none; }
.lea-btn.sm { min-height: 36px; padding: 0 12px; font-size: .78rem; }
.lea-btn-shine { position: absolute; inset: 0 auto 0 0; width: 42%; pointer-events: none; background: linear-gradient(100deg, transparent, rgba(255,255,255,.5), transparent); transform: translateX(-160%) skewX(-18deg); }
.lea-btn:hover .lea-btn-shine { transition: transform .7s ease; transform: translateX(320%) skewX(-18deg); }
.lea-btn-label { position: relative; z-index: 1; display: inline-flex; align-items: center; gap: .45rem; }

.lea-desktop { display: none; }
.lea-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: .85rem; }
.lea-table thead { background: var(--lea-surface-warm); border-bottom: 1px solid var(--lea-border); }
.lea-table th { padding: 12px 16px; color: var(--lea-muted); font-size: .68rem; font-weight: 650; letter-spacing: .07em; text-align: left; text-transform: uppercase; }
.lea-table td { padding: 13px 16px; vertical-align: middle; }
.lea-row { border-bottom: 1px solid var(--lea-border); transition: background .16s ease; }
.lea-row:last-child { border-bottom: 0; }
.lea-row:hover { background: rgba(251,248,242,.72); }
.lea-guest-name { font-weight: 650; color: var(--lea-heading); }
.lea-guest-sub { font-size: .76rem; color: var(--lea-muted); margin-top: 1px; }
.lea-cell-muted { color: var(--lea-muted); white-space: nowrap; }

.lea-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .74rem; font-weight: 650; border: 1px solid transparent; white-space: nowrap; }
.lea-badge.is-info { color: color-mix(in srgb, var(--lea-info) 78%, var(--lea-text)); background: var(--lea-info-bg); border-color: color-mix(in srgb, var(--lea-info) 26%, #fff); }
.lea-badge.is-soft { color: var(--lea-muted); background: var(--lea-surface-warm); border-color: var(--lea-border); }

.lea-codigo { font-family: ui-monospace, 'SFMono-Regular', Menlo, monospace; font-weight: 700; letter-spacing: .05em; color: var(--lea-heading); }
.lea-codigo-meta { font-size: .74rem; color: var(--lea-muted); margin-top: 2px; }

.lea-acciones { display: flex; gap: 6px; flex-wrap: wrap; }

.lea-empty { text-align: center; padding: 44px 18px; background: var(--lea-ivory-2); border: 1px dashed var(--lea-border); border-radius: 16px; }
.lea-empty-icon { width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 18px; display: grid; place-items: center; background: var(--lea-gold-soft); color: var(--lea-gold-ink); font-size: 1.3rem; }
.lea-empty h2 { color: var(--lea-heading); font-size: 1.1rem; font-weight: 650; margin: 0; }
.lea-empty p { color: var(--lea-muted); margin: 8px auto 0; max-width: 30rem; font-size: .9rem; }

.lea-mobile { display: grid; gap: 10px; padding: 12px; }
.lea-mobile-card { background: var(--lea-surface); border: 1px solid var(--lea-border); border-radius: 16px; padding: 12px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 26px -20px rgba(27,39,70,.25); }
.lea-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.lea-mobile-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; margin-top: 11px; }
.lea-mini-label { font-size: .64rem; font-weight: 650; letter-spacing: .04em; text-transform: uppercase; color: var(--lea-muted); }
.lea-mini-value { font-weight: 650; color: var(--lea-heading); font-size: .84rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.lea-mobile-actions { display: flex; gap: 7px; flex-wrap: wrap; margin-top: 11px; }
.lea-mobile-actions .lea-btn { flex: 1 1 auto; }

@media (min-width: 900px) {
    .lea-desktop { display: block; }
    .lea-mobile { display: none; }
}
@media (max-width: 900px) {
    .lea-hero-section { grid-template-columns: minmax(0, 1fr); }
}
@media (max-width: 720px) {
    .lea-summary { grid-template-columns: 1fr 1fr; }
    .lea-config-form { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .lea { padding: 14px 12px 34px; }
    .lea-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .lea-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1.05rem; }
    .lea-title { font-size: 2rem; }
    .lea-summary { grid-template-columns: 1fr 1fr; gap: 8px; }
    .lea-config-form { grid-template-columns: 1fr; padding: 14px; }
    .lea-panel-head { display: grid; grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .lea *, .lea *::before, .lea *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    .lea-btn-shine { display: none; }
}
</style>

<div class="lea">
    <div class="lea-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>

        <section class="lea-hero-section">
            <div class="lea-title-lockup">
                <div class="lea-hero-icon" aria-hidden="true"><i class="fas fa-heart"></i></div>
                <div>
                    <p class="lea-kicker">Fidelizaci&oacute;n de hu&eacute;spedes</p>
                    <h1 class="lea-title">Hu&eacute;sped frecuente</h1>
                    <p class="lea-subtitle">Tus hu&eacute;spedes que regresan, listos para recibir un cup&oacute;n personal de agradecimiento canjeable en tu p&aacute;gina de reservas.</p>
                </div>
            </div>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error'], true) ? $tipo : 'success';
            ?>
            <div class="lea-alert is-<?= $leSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <?php if (!$promocionesActivo): ?>
            <section class="lea-notice">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <div>
                    <strong>Falta activar la p&aacute;gina de canje.</strong>
                    <p>Para que el hu&eacute;sped pueda canjear su cup&oacute;n en l&iacute;nea necesitas activos el <strong>motor de reservas</strong> y el bloque de <strong>cupones y promociones</strong>.</p>
                </div>
            </section>
        <?php endif; ?>

        <section class="lea-summary" aria-label="Resumen del programa">
            <div class="lea-summary-item">
                <div class="lea-summary-label">Hu&eacute;spedes frecuentes</div>
                <div class="lea-summary-value"><?= number_format($totalFrecuentes) ?></div>
            </div>
            <div class="lea-summary-item">
                <div class="lea-summary-label">Cupones vigentes</div>
                <div class="lea-summary-value <?= $conCupon > 0 ? 'is-ok' : '' ?>"><?= number_format($conCupon) ?></div>
            </div>
            <div class="lea-summary-item">
                <div class="lea-summary-label">Correos enviados</div>
                <div class="lea-summary-value"><?= number_format($correosEnviados) ?></div>
            </div>
            <div class="lea-summary-item">
                <div class="lea-summary-label">Descuento configurado</div>
                <div class="lea-summary-value"><?= $leSafe($descuentoTexto) ?></div>
            </div>
        </section>

        <section class="lea-panel">
            <div class="lea-panel-head">
                <div>
                    <h2 class="lea-panel-title">Reglas del programa</h2>
                    <p class="lea-panel-sub">Define a partir de cu&aacute;ntas estancias un hu&eacute;sped se vuelve frecuente y qu&eacute; cup&oacute;n recibe.</p>
                </div>
            </div>
            <form method="POST" action="<?= url('lealtad/config') ?>" class="lea-config-form">
                <?= csrf_field() ?>
                <div class="lea-field">
                    <label for="le-min">Frecuente a partir de</label>
                    <input type="number" id="le-min" name="min_estancias" min="1" max="50" step="1" value="<?= (int) $config['min_estancias'] ?>">
                </div>
                <div class="lea-field">
                    <label for="le-pct">% de descuento del cup&oacute;n</label>
                    <input type="number" id="le-pct" name="descuento_pct" min="1" max="100" step="1" value="<?= (int) $config['descuento_pct'] ?>">
                </div>
                <div class="lea-field">
                    <label for="le-vig">Vigencia del cup&oacute;n (d&iacute;as)</label>
                    <input type="number" id="le-vig" name="vigencia_dias" min="7" max="365" step="1" value="<?= (int) $config['vigencia_dias'] ?>">
                </div>
                <button type="submit" class="lea-btn">
                    <span class="lea-btn-shine" aria-hidden="true"></span>
                    <span class="lea-btn-label"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>Guardar</span>
                </button>
                <p class="lea-config-hint">Cada cup&oacute;n es personal: un solo uso, con vigencia, y aplica al hospedaje (no a extras). Un hu&eacute;sped no puede tener dos cupones vigentes a la vez.</p>
            </form>
        </section>

        <?php if (empty($frecuentes)): ?>
            <section class="lea-empty">
                <div class="lea-empty-icon" aria-hidden="true"><i class="fas fa-heart"></i></div>
                <h2>A&uacute;n no hay hu&eacute;spedes frecuentes</h2>
                <p>Nadie llega a&uacute;n a <?= (int) $config['min_estancias'] ?>+ estancias completadas. Baja el m&iacute;nimo arriba si quieres ver m&aacute;s candidatos.</p>
            </section>
        <?php else: ?>
            <section class="lea-panel overflow-hidden">
                <div class="lea-panel-head">
                    <div>
                        <div class="lea-panel-title">Hu&eacute;spedes frecuentes</div>
                        <p class="lea-panel-sub">Genera, reenv&iacute;a o comparte el cup&oacute;n de agradecimiento de cada hu&eacute;sped.</p>
                    </div>
                    <span class="lea-count-pill">
                        <i class="fas fa-heart"></i>
                        <?= number_format($totalFrecuentes) ?> <?= $totalFrecuentes === 1 ? 'hu&eacute;sped' : 'hu&eacute;spedes' ?>
                    </span>
                </div>

                <div class="lea-desktop">
                    <table class="lea-table">
                        <colgroup>
                            <col style="width: 26%;">
                            <col style="width: 12%;">
                            <col style="width: 15%;">
                            <col style="width: 22%;">
                            <col style="width: 25%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Hu&eacute;sped</th><th>Estancias</th><th>&Uacute;ltima visita</th><th>Cup&oacute;n vigente</th><th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($frecuentes as $f): ?>
                            <tr class="lea-row">
                                <td>
                                    <div class="lea-guest-name"><?= $leSafe($f['nombre_completo']) ?></div>
                                    <div class="lea-guest-sub"><?= $leSafe($f['email'] ?: ($f['telefono'] ?: '')) ?></div>
                                </td>
                                <td><span class="lea-badge is-info"><i class="fas fa-star"></i> <?= (int) $f['estancias'] ?></span></td>
                                <td class="lea-cell-muted"><?= $f['ultima_salida'] ? $leSafe(date('d/m/Y', strtotime((string) $f['ultima_salida']))) : '—' ?></td>
                                <td>
                                    <?php if (!empty($f['cupon_codigo'])): ?>
                                        <div class="lea-codigo"><?= $leSafe($f['cupon_codigo']) ?></div>
                                        <div class="lea-codigo-meta">
                                            <?= $leSafe(rtrim(rtrim(number_format((float) $f['cupon_valor'], 2), '0'), '.')) ?>%
                                            <?= $f['cupon_vigencia'] ? ' · vence ' . $leSafe(date('d/m/Y', strtotime((string) $f['cupon_vigencia']))) : '' ?>
                                            <?= $f['correo_enviado_at'] ? ' · ✉ enviado' : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="lea-badge is-soft">Sin cup&oacute;n</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="lea-acciones">
                                        <?php if (empty($f['cupon_codigo'])): ?>
                                            <form method="POST" action="<?= url('lealtad/generar/' . (int) $f['id']) ?>">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="lea-btn sm">Generar cup&oacute;n</button>
                                            </form>
                                        <?php else: ?>
                                            <?php if (!empty($f['email'])): ?>
                                                <form method="POST" action="<?= url('lealtad/enviar/' . (int) $f['lealtad_id']) ?>">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="lea-btn sm sec"><?= $f['correo_enviado_at'] ? 'Reenviar correo' : 'Enviar por correo' ?></button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!empty($f['telefono'])): ?>
                                                <a class="lea-btn sm sec" target="_blank" rel="noopener"
                                                   href="https://wa.me/52<?= $leSafe(preg_replace('/\D/', '', substr((string) $f['telefono'], -10))) ?>?text=<?= rawurlencode('Hola ' . $f['nombre_completo'] . ', gracias por tu preferencia. Te regalamos un cupon de ' . rtrim(rtrim(number_format((float) $f['cupon_valor'], 2), '0'), '.') . '% para tu proxima reserva: ' . $f['cupon_codigo'] . '. Usalo aqui: ' . $urlMotor) ?>">
                                                    WhatsApp
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="lea-btn sm sec"
                                                    data-codigo="<?= $leSafe($f['cupon_codigo']) ?>"
                                                    onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.codigo).then(() => { this.textContent='Copiado ✓'; })">
                                                Copiar c&oacute;digo
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="lea-mobile">
                    <?php foreach ($frecuentes as $f): ?>
                        <article class="lea-mobile-card">
                            <div class="lea-mobile-top">
                                <div class="min-w-0">
                                    <div class="lea-guest-name"><?= $leSafe($f['nombre_completo']) ?></div>
                                    <div class="lea-guest-sub"><?= $leSafe($f['email'] ?: ($f['telefono'] ?: '')) ?></div>
                                </div>
                                <span class="lea-badge is-info"><i class="fas fa-star"></i> <?= (int) $f['estancias'] ?></span>
                            </div>
                            <div class="lea-mobile-meta">
                                <div>
                                    <div class="lea-mini-label">&Uacute;ltima visita</div>
                                    <div class="lea-mini-value"><?= $f['ultima_salida'] ? $leSafe(date('d/m/Y', strtotime((string) $f['ultima_salida']))) : '—' ?></div>
                                </div>
                                <div>
                                    <div class="lea-mini-label">Cup&oacute;n</div>
                                    <div class="lea-mini-value"><?= !empty($f['cupon_codigo']) ? $leSafe($f['cupon_codigo']) : 'Sin cup&oacute;n' ?></div>
                                </div>
                            </div>
                            <div class="lea-mobile-actions">
                                <?php if (empty($f['cupon_codigo'])): ?>
                                    <form method="POST" action="<?= url('lealtad/generar/' . (int) $f['id']) ?>" style="flex:1 1 auto;">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="lea-btn sm" style="width:100%;">Generar cup&oacute;n</button>
                                    </form>
                                <?php else: ?>
                                    <?php if (!empty($f['email'])): ?>
                                        <form method="POST" action="<?= url('lealtad/enviar/' . (int) $f['lealtad_id']) ?>" style="flex:1 1 auto;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="lea-btn sm sec" style="width:100%;"><?= $f['correo_enviado_at'] ? 'Reenviar correo' : 'Enviar correo' ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if (!empty($f['telefono'])): ?>
                                        <a class="lea-btn sm sec" target="_blank" rel="noopener"
                                           href="https://wa.me/52<?= $leSafe(preg_replace('/\D/', '', substr((string) $f['telefono'], -10))) ?>?text=<?= rawurlencode('Hola ' . $f['nombre_completo'] . ', gracias por tu preferencia. Te regalamos un cupon de ' . rtrim(rtrim(number_format((float) $f['cupon_valor'], 2), '0'), '.') . '% para tu proxima reserva: ' . $f['cupon_codigo'] . '. Usalo aqui: ' . $urlMotor) ?>">
                                            WhatsApp
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="lea-btn sm sec"
                                            data-codigo="<?= $leSafe($f['cupon_codigo']) ?>"
                                            onclick="navigator.clipboard && navigator.clipboard.writeText(this.dataset.codigo).then(() => { this.textContent='Copiado ✓'; })">
                                        Copiar c&oacute;digo
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>
