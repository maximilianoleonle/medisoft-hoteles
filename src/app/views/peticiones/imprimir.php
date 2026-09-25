<?php
/**
 * Hoja imprimible de QR por habitacion (standalone). Tarjetas de ~9 x 12 cm,
 * dos por renglon en carta; el QR va negro sobre blanco para que salga bien
 * en cualquier impresora (tambien en blanco y negro).
 */
$tarjetas = $tarjetas ?? [];
$branding = $branding ?? [];
$nombreHotel = (string) ($nombreHotel ?? 'Hotel');
$config = $config ?? [];
$conWifi = !empty($conWifi);

$imSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};
$color = trim((string) ($branding['color_primary'] ?? ''));
$color = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#1B2746';
$colorTexto = EstanciaService::colorTextoSobre($color);
$logoUrl = function_exists('hotel_branding_asset_url') ? hotel_branding_asset_url($branding['logo_url'] ?? null) : null;
$wifiRed = trim((string) ($config['wifi_red'] ?? ''));
$wifiClave = trim((string) ($config['wifi_clave'] ?? ''));
$mostrarWifi = $conWifi && ($wifiRed !== '' || $wifiClave !== '');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>QR de habitaciones · <?= $imSafe($nombreHotel) ?></title>
    <link rel="stylesheet" href="<?= asset('vendor/fontawesome/css/all.min.css') ?>">
    <style>
    :root { --im-brand: <?= $imSafe($color) ?>; --im-on-brand: <?= $imSafe($colorTexto) ?>; --im-brand-ink: color-mix(in srgb, var(--im-brand) 58%, #0F172A); }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif; background: #EEF0F3; color: #1F2937; }
    .im-barra {
        position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px;
        padding: 12px 16px; background: #FFFFFF; border-bottom: 1px solid #E3E5EA;
    }
    .im-barra h1 { margin: 0; font-size: 1.05rem; }
    .im-barra p { margin: 0; color: #5B6475; font-size: .9rem; }
    .im-barra .im-esp { flex: 1; }
    .im-btn {
        display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 18px; border-radius: 12px;
        border: 1px solid var(--im-brand); background: var(--im-brand); color: var(--im-on-brand); font: inherit; font-weight: 600; cursor: pointer;
    }
    .im-btn.is-sec { background: #fff; color: #1F2937; border-color: #D5D8DE; text-decoration: none; }
    .im-hoja {
        display: grid; grid-template-columns: repeat(2, 90mm); justify-content: center; gap: 8mm;
        padding: 12mm 0;
    }
    .im-tarjeta {
        width: 90mm; min-height: 120mm; background: #FFFFFF; border: 1px solid #D5D8DE; border-radius: 5mm;
        padding: 6.5mm 7mm 5.5mm; display: flex; flex-direction: column; align-items: center; text-align: center;
        break-inside: avoid; page-break-inside: avoid; position: relative; overflow: hidden;
    }
    .im-tarjeta::before { content: ""; position: absolute; inset: 0 0 auto; height: 2.2mm; background: var(--im-brand); }
    .im-hotel { display: flex; align-items: center; justify-content: center; gap: 2.5mm; min-height: 10mm; }
    .im-hotel img { width: 10mm; height: 10mm; object-fit: contain; }
    .im-hotel span { font-size: 10pt; font-weight: 600; color: #5B6475; }
    .im-hab { margin: 2mm 0 0; font-size: 9pt; letter-spacing: .12em; text-transform: uppercase; color: #5B6475; }
    .im-num { margin: 0; font-size: 24pt; font-weight: 700; line-height: 1.05; color: #111827; overflow-wrap: anywhere; }
    .im-qr { width: 46mm; height: 46mm; margin: 3mm 0 2.5mm; }
    .im-qr svg { width: 100%; height: 100%; display: block; }
    .im-cta { margin: 0; font-size: 11pt; font-weight: 700; color: #111827; }
    .im-lista { list-style: none; margin: 2mm 0 0; padding: 0; font-size: 9pt; color: #374151; line-height: 1.5; }
    .im-lista i { width: 4.5mm; color: var(--im-brand-ink); }
    .im-wifi { margin-top: auto; width: 100%; border-top: 1px dashed #D5D8DE; padding-top: 2.5mm; font-size: 9pt; line-height: 1.45; color: #374151; overflow-wrap: anywhere; }
    .im-wifi span { white-space: nowrap; }
    .im-wifi strong { color: #111827; }
    .im-vacio { max-width: 480px; margin: 40px auto; padding: 24px; background: #fff; border-radius: 16px; text-align: center; }
    @page { size: letter; margin: 10mm; }
    @media print {
        body { background: #FFFFFF; }
        .im-barra { display: none; }
        .im-hoja { padding: 0; gap: 6mm 8mm; }
        .im-tarjeta { border-color: #BFC4CC; }
        .im-tarjeta::before, .im-lista i { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
    @media screen and (max-width: 420px) {
        .im-hoja { grid-template-columns: 90mm; }
    }
    </style>
</head>
<body>
<header class="im-barra">
    <div>
        <h1>QR de habitaciones</h1>
        <p><?= count($tarjetas) ?> <?= count($tarjetas) === 1 ? 'tarjeta' : 'tarjetas' ?> · <?= $imSafe($nombreHotel) ?> · recórtalas y pégalas a la vista en cada cuarto.</p>
    </div>
    <span class="im-esp"></span>
    <a class="im-btn is-sec" href="<?= url('peticiones/qr') ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i>Volver</a>
    <button type="button" class="im-btn" onclick="window.print()"><i class="fas fa-print" aria-hidden="true"></i>Imprimir</button>
</header>

<?php if (empty($tarjetas)): ?>
    <div class="im-vacio">
        <p>No hay habitaciones para imprimir con ese filtro.</p>
        <p><a href="<?= url('peticiones/qr') ?>">Volver a QR de habitaciones</a></p>
    </div>
<?php else: ?>
    <main class="im-hoja">
        <?php foreach ($tarjetas as $t): ?>
            <article class="im-tarjeta">
                <div class="im-hotel">
                    <?php if ($logoUrl): ?><img src="<?= $imSafe($logoUrl) ?>" alt="" onerror="this.remove()"><?php endif; ?>
                    <span><?= $imSafe($nombreHotel) ?></span>
                </div>
                <p class="im-hab">Habitación</p>
                <p class="im-num"><?= $imSafe($t['numero']) ?></p>
                <div class="im-qr"><?= $t['svg'] ?></div>
                <p class="im-cta">Escanéame con tu celular</p>
                <ul class="im-lista">
                    <li><i class="fas fa-wifi" aria-hidden="true"></i> Wi-Fi y horarios del hotel</li>
                    <li><i class="fas fa-bath" aria-hidden="true"></i> Pide toallas, amenidades o limpieza</li>
                    <li><i class="fas fa-screwdriver-wrench" aria-hidden="true"></i> Avísanos si algo no funciona</li>
                </ul>
                <?php if ($mostrarWifi): ?>
                    <div class="im-wifi">
                        <?php if ($wifiRed !== ''): ?><span>Wi-Fi: <strong><?= $imSafe($wifiRed) ?></strong></span><?php endif; ?>
                        <?php if ($wifiRed !== '' && $wifiClave !== ''): ?> · <?php endif; ?>
                        <?php if ($wifiClave !== ''): ?><span>Clave: <strong><?= $imSafe($wifiClave) ?></strong></span><?php endif; ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </main>
<?php endif; ?>
</body>
</html>
