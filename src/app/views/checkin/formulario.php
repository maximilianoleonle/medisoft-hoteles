<?php
/**
 * Pre-registro publico del huesped (bloque checkin_digital). Standalone.
 */
$hotel = $hotel ?? [];
$branding = $branding ?? [];
$link = $link ?? null;
$token = $token ?? '';
$resultado = $resultado ?? null;

$cdSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$nombreHotel = $cdSafe($branding['nombre_visual'] ?? $hotel['nombre'] ?? 'Hotel');
$colorPrimario = $cdSafe($branding['color_primary'] ?? '#1B2746');
$colorAcento = $cdSafe($branding['color_accent'] ?? '#BD9441');
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;

$completado = ($link['estado'] ?? '') === 'completado';
$expirado = ($link['estado'] ?? '') === 'expirado';
$fmtFecha = static function ($iso) {
    $ts = strtotime((string) $iso . ' 12:00:00');
    return $ts ? date('d/m/Y', $ts) : (string) $iso;
};
$datos = $link ? (json_decode((string) ($link['datos_json'] ?? ''), true) ?: []) : [];
$valor = static function ($campo) use ($datos, $link, $cdSafe) {
    if (isset($_POST[$campo])) {
        return $cdSafe($_POST[$campo]);
    }
    if (!empty($datos[$campo])) {
        return $cdSafe($datos[$campo]);
    }
    $mapa = ['nombre' => 'nombre_completo', 'telefono' => 'telefono', 'email' => 'email',
             'procedencia_estado' => 'procedencia_estado', 'procedencia_ciudad' => 'procedencia_ciudad'];
    return $cdSafe($link[$mapa[$campo] ?? ''] ?? '');
};
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Pre-registro - <?= $nombreHotel ?></title>
    <style>
    :root { --brand-primary: <?= $colorPrimario ?>; --brand-accent: <?= $colorAcento ?>; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Inter", "Segoe UI", system-ui, sans-serif; background: color-mix(in srgb, var(--brand-accent) 6%, #F7F5F0); color: #2A3242; }
    .cd-shell { max-width: 560px; margin: 0 auto; padding: 22px 16px 48px; }
    .cd-header { text-align: center; padding: 14px 0 6px; }
    .cd-header img { height: 54px; width: 54px; object-fit: contain; border-radius: 50%; background: #fff; }
    .cd-header h1 { margin: 8px 0 4px; font-size: 1.3rem; color: var(--brand-primary); }
    .cd-header p { margin: 0; color: #6B7486; font-size: .88rem; }
    .cd-card { margin-top: 16px; background: #fff; border: 1px solid color-mix(in srgb, var(--brand-primary) 12%, #E6E2D8); border-radius: 16px; padding: 20px; box-shadow: 0 16px 36px -30px rgba(20,28,45,.55); }
    .cd-fechas { display: flex; justify-content: center; gap: 16px; font-size: .85rem; color: #55607A; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px dashed #EDE9DF; }
    .cd-fechas strong { color: var(--brand-primary); }
    .cd-form { display: grid; gap: 12px; }
    .cd-field { display: grid; gap: 5px; }
    .cd-field label { font-size: .78rem; font-weight: 700; color: #55607A; }
    .cd-field input { min-height: 46px; border: 1px solid #D8D4C9; border-radius: 10px; padding: 0 12px; font-size: 1rem; width: 100%; }
    .cd-field input[type=file] { padding: 10px 12px; background: #FBFAF6; }
    .cd-field .hint { font-size: .74rem; color: #8A93A6; }
    .cd-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    .cd-btn { min-height: 48px; border: 0; border-radius: 10px; cursor: pointer; background: var(--brand-primary); color: #fff; font-size: 1rem; font-weight: 700; }
    .cd-btn:disabled { opacity: .6; }
    .cd-alerta { padding: 11px 14px; border-radius: 10px; font-size: .9rem; margin-bottom: 12px; }
    .cd-exito { text-align: center; padding: 18px 4px; }
    .cd-exito .ico { font-size: 2.4rem; }
    .cd-exito h2 { margin: 8px 0 6px; color: var(--brand-primary); font-size: 1.15rem; }
    .cd-exito p { color: #667086; font-size: .92rem; line-height: 1.55; margin: 4px 0; }
    .cd-footer { margin-top: 22px; text-align: center; font-size: .74rem; color: #9AA0AE; }
    @media (max-width: 480px) { .cd-2col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="cd-shell">
    <header class="cd-header">
        <?php if ($logoUrl): ?><img src="<?= $cdSafe($logoUrl) ?>" alt="<?= $nombreHotel ?>"><?php endif; ?>
        <h1><?= $nombreHotel ?></h1>
        <p>Pre-registro para tu llegada · toma 1 minuto</p>
    </header>

    <div class="cd-card">
        <div class="cd-fechas">
            <span>Llegada <strong><?= $cdSafe($fmtFecha($link['fecha_entrada'] ?? '')) ?></strong></span>
            <span>Salida <strong><?= $cdSafe($fmtFecha($link['fecha_salida'] ?? '')) ?></strong></span>
        </div>

        <?php if ($resultado !== null && empty($resultado['success'])): ?>
            <div class="cd-alerta" style="background:rgba(220,38,38,.08);color:#B91C1C;border:1px solid rgba(220,38,38,.2);">
                <?= $cdSafe($resultado['message'] ?? 'No se pudo guardar.') ?>
            </div>
        <?php endif; ?>

        <?php if ($completado): ?>
            <div class="cd-exito">
                <div class="ico" aria-hidden="true">✅</div>
                <h2>¡Registro completo!</h2>
                <p>Gracias, <?= $valor('nombre') ?: 'huesped' ?>. Tus datos y tu identificacion quedaron listos.</p>
                <p>Al llegar a <?= $nombreHotel ?> solo confirmas y recibes tu llave. ¡Buen viaje!</p>
            </div>
        <?php elseif ($expirado): ?>
            <div class="cd-exito">
                <div class="ico" aria-hidden="true">⌛</div>
                <h2>Esta liga ya expiro</h2>
                <p>Pide una nueva al hotel para completar tu pre-registro.</p>
            </div>
        <?php else: ?>
            <form class="cd-form" method="POST" enctype="multipart/form-data"
                  action="<?= url('h/' . ($hotel['slug'] ?? '') . '/checkin/' . $cdSafe($token) . '/completar') ?>"
                  onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Guardando...'; return true;">
                <div class="cd-field">
                    <label for="cd-nombre">Nombre completo *</label>
                    <input type="text" id="cd-nombre" name="nombre" maxlength="150" required autocomplete="name" value="<?= $valor('nombre') ?>">
                </div>
                <div class="cd-2col">
                    <div class="cd-field">
                        <label for="cd-telefono">Telefono *</label>
                        <input type="tel" id="cd-telefono" name="telefono" maxlength="20" required inputmode="tel" autocomplete="tel" value="<?= $valor('telefono') ?>">
                    </div>
                    <div class="cd-field">
                        <label for="cd-email">Correo</label>
                        <input type="email" id="cd-email" name="email" maxlength="120" autocomplete="email" value="<?= $valor('email') ?>">
                    </div>
                </div>
                <div class="cd-2col">
                    <div class="cd-field">
                        <label for="cd-pestado">Estado de procedencia</label>
                        <input type="text" id="cd-pestado" name="procedencia_estado" maxlength="80" value="<?= $valor('procedencia_estado') ?>">
                    </div>
                    <div class="cd-field">
                        <label for="cd-pciudad">Ciudad</label>
                        <input type="text" id="cd-pciudad" name="procedencia_ciudad" maxlength="80" value="<?= $valor('procedencia_ciudad') ?>">
                    </div>
                </div>
                <div class="cd-field">
                    <label for="cd-id">Identificacion oficial (INE/pasaporte) *</label>
                    <input type="file" id="cd-id" name="identificacion" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" required>
                    <span class="hint">Foto legible o PDF. Maximo 5 MB. Solo el hotel puede verla.</span>
                </div>
                <button type="submit" class="cd-btn">Completar mi registro</button>
            </form>
        <?php endif; ?>
    </div>

    <p class="cd-footer">Tus datos se usan solo para tu hospedaje en <?= $nombreHotel ?> · Impulsado por Medisoft Hoteles</p>
</div>
</body>
</html>
