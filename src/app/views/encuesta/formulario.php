<?php
/**
 * Encuesta post-estancia publica del huesped (bloque reputacion). Standalone.
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$encuesta = $encuesta ?? null;
$token = $token ?? '';
$resultado = $resultado ?? null;
$googleUrl = $googleUrl ?? '';

$enSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$nombreHotel = $enSafe($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel');
$colorPrimario = $enSafe($branding['color_primary'] ?? '#1B2746');
$colorAcento = $enSafe($branding['color_accent'] ?? '#BD9441');
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;

$respondida = ($encuesta['estado'] ?? '') === 'respondida';
$expirada = ($encuesta['estado'] ?? '') === 'expirada';
$mostrarGoogle = ($respondida && (int) ($encuesta['calificacion'] ?? 0) >= 4 && $googleUrl !== '')
    || (!empty($resultado['mostrar_google']) && $googleUrl !== '');
$nombreHuesped = trim((string) ($encuesta['nombre_completo'] ?? ''));
$primerNombre = $nombreHuesped !== '' ? explode(' ', $nombreHuesped)[0] : '';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Tu opinion - <?= $nombreHotel ?></title>
    <style>
    :root { --brand-primary: <?= $colorPrimario ?>; --brand-accent: <?= $colorAcento ?>; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Inter", "Segoe UI", system-ui, sans-serif; background: color-mix(in srgb, var(--brand-accent) 6%, #F7F5F0); color: #2A3242; }
    .en-shell { max-width: 560px; margin: 0 auto; padding: 22px 16px 48px; }
    .en-header { text-align: center; padding: 14px 0 6px; }
    .en-header img { height: 54px; width: 54px; object-fit: contain; border-radius: 50%; background: #fff; }
    .en-header h1 { margin: 8px 0 4px; font-size: 1.3rem; color: var(--brand-primary); }
    .en-header p { margin: 0; color: #6B7486; font-size: .88rem; }
    .en-card { margin-top: 16px; background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary) 12%, #E6E2D8); border-radius: 16px; padding: 20px; box-shadow: 0 16px 36px -30px rgba(20,28,45,.55); }
    .en-form { display: grid; gap: 18px; }
    .en-field label, .en-field .lbl { display: block; font-size: .82rem; font-weight: 700; color: #55607A; margin-bottom: 8px; }
    .en-estrellas { display: flex; flex-direction: row-reverse; justify-content: center; gap: 6px; }
    .en-estrellas input { position: absolute; opacity: 0; pointer-events: none; }
    .en-estrellas label { font-size: 2.4rem; line-height: 1; color: #D8D4C9; cursor: pointer; transition: transform .08s ease; margin: 0; }
    .en-estrellas label:hover { transform: scale(1.12); }
    .en-estrellas input:checked ~ label,
    .en-estrellas label:hover, .en-estrellas label:hover ~ label { color: var(--brand-accent); }
    .en-nps { display: grid; grid-template-columns: repeat(11, 1fr); gap: 4px; }
    .en-nps input { position: absolute; opacity: 0; pointer-events: none; }
    .en-nps label { display: grid; place-items: center; min-height: 38px; border: 1px solid #D8D4C9; border-radius: 8px; font-size: .85rem; font-weight: 700; color: #55607A; cursor: pointer; margin: 0; }
    .en-nps input:checked + label { background: var(--brand-primary); border-color: var(--brand-primary); color: #fff; }
    .en-nps-ejes { display: flex; justify-content: space-between; font-size: .7rem; color: #8A93A6; margin-top: 4px; }
    .en-field textarea { width: 100%; min-height: 96px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 10px 12px; font-size: 1rem; font-family: inherit; resize: vertical; }
    .en-btn { min-height: 48px; border: 0; border-radius: 10px; cursor: pointer; background: var(--brand-primary); color: #fff; font-size: 1rem; font-weight: 700; }
    .en-btn:disabled { opacity: .6; }
    .en-btn-google { display: inline-flex; align-items: center; justify-content: center; gap: 8px; min-height: 48px; padding: 0 20px; border-radius: 10px; background: var(--brand-accent); color: #fff; font-size: 1rem; font-weight: 700; text-decoration: none; }
    .en-alerta { padding: 11px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 12px; }
    .en-exito { text-align: center; padding: 18px 4px; }
    .en-exito .ico { font-size: 2.4rem; }
    .en-exito h2 { margin: 8px 0 6px; color: var(--brand-primary); font-size: 1.15rem; }
    .en-exito p { color: #667086; font-size: .92rem; line-height: 1.55; margin: 4px 0; }
    .en-footer { margin-top: 22px; text-align: center; font-size: .74rem; color: #9AA0AE; }
    @media (max-width: 420px) { .en-estrellas label { font-size: 2.1rem; } }
    </style>
</head>
<body>
<div class="en-shell">
    <header class="en-header">
        <?php if ($logoUrl): ?><img src="<?= $enSafe($logoUrl) ?>" alt="<?= $nombreHotel ?>"><?php endif; ?>
        <h1><?= $nombreHotel ?></h1>
        <p>Tu opinion nos ayuda a mejorar · toma menos de 1 minuto</p>
    </header>

    <div class="en-card">
        <?php if ($resultado !== null && empty($resultado['success'])): ?>
            <div class="en-alerta" style="background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);">
                <?= $enSafe($resultado['message'] ?? 'No se pudo guardar.') ?>
            </div>
        <?php endif; ?>

        <?php if ($respondida): ?>
            <div class="en-exito">
                <div class="ico" aria-hidden="true">💛</div>
                <h2>¡Gracias<?= $primerNombre !== '' ? ', ' . $enSafe($primerNombre) : '' ?>!</h2>
                <p>Recibimos tu opinion sobre tu estancia en <?= $nombreHotel ?>.</p>
                <?php if ($mostrarGoogle): ?>
                    <p>¿Nos ayudas con una resena? A otros viajeros les sirve muchisimo.</p>
                    <p style="margin-top:14px;"><a class="en-btn-google" href="<?= $enSafe($googleUrl) ?>" rel="noopener">⭐ Dejar resena en Google</a></p>
                <?php else: ?>
                    <p>Esperamos verte pronto de nuevo. ¡Buen viaje!</p>
                <?php endif; ?>
            </div>
        <?php elseif ($expirada): ?>
            <div class="en-exito">
                <div class="ico" aria-hidden="true">⌛</div>
                <h2>Esta encuesta ya expiro</h2>
                <p>De todos modos, gracias por hospedarte en <?= $nombreHotel ?>.</p>
            </div>
        <?php else: ?>
            <form class="en-form" method="POST"
                  action="<?= url('h/' . ($hotel['slug'] ?? '') . '/encuesta/' . $enSafe($token) . '/responder') ?>"
                  onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Enviando...'; return true;">
                <div class="en-field" style="text-align:center;">
                    <span class="lbl">¿Como calificas tu estancia? *</span>
                    <div class="en-estrellas">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="en-cal-<?= $i ?>" name="calificacion" value="<?= $i ?>" <?= $i === 5 ? 'required' : '' ?>>
                            <label for="en-cal-<?= $i ?>" title="<?= $i ?> de 5">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="en-field">
                    <span class="lbl">¿Que tanto nos recomendarias a un amigo o familiar?</span>
                    <div class="en-nps">
                        <?php for ($i = 0; $i <= 10; $i++): ?>
                            <input type="radio" id="en-nps-<?= $i ?>" name="nps" value="<?= $i ?>">
                            <label for="en-nps-<?= $i ?>"><?= $i ?></label>
                        <?php endfor; ?>
                    </div>
                    <div class="en-nps-ejes"><span>Nada probable</span><span>Muy probable</span></div>
                </div>
                <div class="en-field">
                    <label for="en-comentario">¿Algo que quieras contarnos?</label>
                    <textarea id="en-comentario" name="comentario" maxlength="1000" placeholder="Lo que mas te gusto, o lo que podemos mejorar..."></textarea>
                </div>
                <button type="submit" class="en-btn">Enviar mi opinion</button>
            </form>
        <?php endif; ?>
    </div>

    <p class="en-footer">Tu opinion se comparte solo con <?= $nombreHotel ?> · Impulsado por Medisoft Hoteles</p>
</div>
</body>
</html>
