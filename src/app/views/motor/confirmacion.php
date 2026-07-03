<?php
/**
 * Pagina publica de confirmacion del pago online (motor_reservas).
 * Estados: pendiente (auto-refresca; el webhook puede tardar unos segundos),
 * pagado/conciliado (exito con folio), reembolsado/fallido/expirado (aviso).
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$estado = $estado ?? ['estado' => 'pendiente'];

$nombreHotel = htmlspecialchars((string) ($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel'), ENT_QUOTES, 'UTF-8');
$colorPrimario = htmlspecialchars((string) ($branding['color_primary'] ?? '#1B2746'), ENT_QUOTES, 'UTF-8');
$colorAcento = htmlspecialchars((string) ($branding['color_accent'] ?? '#BD9441'), ENT_QUOTES, 'UTF-8');
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;
$monedaSimbolo = htmlspecialchars((string) ($hotel['moneda_simbolo'] ?? '$'), ENT_QUOTES, 'UTF-8');

$estadoPago = (string) ($estado['estado'] ?? 'pendiente');
$exito = in_array($estadoPago, ['pagado', 'conciliado'], true);
$pendiente = $estadoPago === 'pendiente';
$fmt = function ($n) use ($monedaSimbolo) {
    return $monedaSimbolo . number_format((float) $n, 2);
};
$fmtFecha = function ($iso) {
    $ts = strtotime((string) $iso . ' 12:00:00');
    return $ts ? date('d/m/Y', $ts) : (string) $iso;
};
$saldoRestante = max(0, (float) ($estado['precio_total_estancia'] ?? 0) - (float) ($estado['monto'] ?? 0));
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <?php if ($pendiente): ?><meta http-equiv="refresh" content="5"><?php endif; ?>
    <title>Confirmacion - <?= $nombreHotel ?></title>
    <style>
    :root { --brand-primary: <?= $colorPrimario ?>; --brand-accent: <?= $colorAcento ?>; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Inter", "Segoe UI", system-ui, sans-serif; background: color-mix(in srgb, var(--brand-accent) 6%, #F7F5F0); color: #2A3242; display: grid; place-items: center; min-height: 100vh; padding: 20px; }
    .cf-card { max-width: 460px; width: 100%; background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary) 12%, #E6E2D8); border-radius: 16px; padding: 28px 24px; text-align: center; box-shadow: 0 16px 36px -30px rgba(20,28,45,.55); }
    .cf-card img { height: 54px; width: 54px; object-fit: contain; border-radius: 50%; background: #fff; }
    .cf-ico { font-size: 2.4rem; margin: 8px 0 2px; }
    h1 { margin: 8px 0 6px; font-size: 1.25rem; color: var(--brand-primary); }
    p { margin: 6px 0; color: #667086; font-size: .92rem; line-height: 1.5; }
    .cf-folio { display: inline-block; margin: 12px 0; padding: 8px 18px; border-radius: 10px; background: color-mix(in srgb, var(--brand-accent) 10%, #FBF9F3); color: var(--brand-primary); font-weight: 800; font-size: 1.05rem; letter-spacing: .04em; }
    dl { margin: 16px 0 0; padding: 0; text-align: left; border-top: 1px dashed #E8E4DA; }
    .fila { display: flex; justify-content: space-between; gap: 10px; padding: 9px 2px; font-size: .88rem; border-bottom: 1px dashed #EEEAE0; }
    dt { color: #77809A; } dd { margin: 0; font-weight: 700; }
    .fila.anticipo dd { color: color-mix(in srgb, var(--brand-accent) 85%, #6d520f); }
    .cf-nota { margin-top: 16px; font-size: .78rem; color: #9AA0AE; }
    .cf-spin { width: 34px; height: 34px; margin: 10px auto; border: 3px solid #EDE9DF; border-top-color: var(--brand-primary); border-radius: 50%; animation: cf-rot 1s linear infinite; }
    @keyframes cf-rot { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="cf-card">
    <?php if ($logoUrl): ?><img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $nombreHotel ?>"><?php endif; ?>

    <?php if ($exito): ?>
        <div class="cf-ico">✅</div>
        <h1>¡Reservacion confirmada!</h1>
        <p>Gracias, <?= htmlspecialchars((string) ($estado['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>. Tu anticipo fue recibido y tu habitacion esta apartada.</p>
        <?php if (!empty($estado['reservacion_id'])): ?>
            <div class="cf-folio">Folio #<?= (int) $estado['reservacion_id'] ?></div>
        <?php endif; ?>
        <dl>
            <div class="fila"><dt>Llegada</dt><dd><?= htmlspecialchars($fmtFecha($estado['entrada'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="fila"><dt>Salida</dt><dd><?= htmlspecialchars($fmtFecha($estado['salida'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="fila"><dt>Habitacion</dt><dd style="text-transform:capitalize;"><?= htmlspecialchars((string) ($estado['tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div class="fila anticipo"><dt>Anticipo pagado</dt><dd><?= $fmt($estado['monto'] ?? 0) ?></dd></div>
            <div class="fila"><dt>Pagas al llegar</dt><dd><?= $fmt($saldoRestante) ?></dd></div>
        </dl>
        <p class="cf-nota">Presenta tu folio al llegar a <?= $nombreHotel ?>. Guarda o toma captura de esta pagina.</p>
    <?php elseif ($pendiente): ?>
        <div class="cf-spin" aria-hidden="true"></div>
        <h1>Confirmando tu pago...</h1>
        <p>Estamos verificando tu pago con el banco. Esta pagina se actualizara sola en unos segundos.</p>
        <p class="cf-nota">Si pagaste y esta pantalla no cambia en unos minutos, contacta al hotel con tu comprobante.</p>
    <?php elseif ($estadoPago === 'reembolsado'): ?>
        <div class="cf-ico">↩️</div>
        <h1>Pago reembolsado</h1>
        <p>La habitacion se ocupo justo antes de confirmar tu pago, asi que te devolvimos tu dinero automaticamente. Elige otras fechas o contacta al hotel.</p>
    <?php else: ?>
        <div class="cf-ico">⚠️</div>
        <h1>No pudimos completar tu reservacion</h1>
        <p>Tu pago no se completo o hubo un problema al confirmarlo. Si ya pagaste, contacta al hotel con tu comprobante para resolverlo.</p>
    <?php endif; ?>
</div>
</body>
</html>
