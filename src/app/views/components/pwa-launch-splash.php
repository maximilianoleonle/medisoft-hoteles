<?php
$pwaLaunchBranding = $layoutBranding ?? ($loginBranding ?? null);
$pwaLaunchName = $layoutNombreVisual ?? ($loginHotelNombre ?? 'Medisoft Hoteles');
$pwaLaunchLogo = $layoutPwaIcon512Url ?? null;
$pwaLaunchLogo = $pwaLaunchLogo ?: ($layoutPwaIcon192Url ?? null);

if (!$pwaLaunchLogo && is_array($pwaLaunchBranding) && function_exists('hotel_branding_pwa_icon_asset_url')) {
    $pwaLaunchLogo = hotel_branding_pwa_icon_asset_url($pwaLaunchBranding['pwa_icon_512_url'] ?? null, 512);
    $pwaLaunchLogo = $pwaLaunchLogo ?: hotel_branding_pwa_icon_asset_url($pwaLaunchBranding['pwa_icon_192_url'] ?? null, 192);
}

$pwaLaunchLogo = $pwaLaunchLogo ?: ($layoutLogoUrl ?? ($loginLogoUrl ?? null));
$pwaLaunchLogo = $pwaLaunchLogo ?: (function_exists('hotel_branding_default_logo_url') ? hotel_branding_default_logo_url() : (function_exists('asset') ? asset('img/logo.png') : '/img/logo.png'));

$pwaLaunchPrimary = '#1B2746';
$pwaLaunchSecondary = '#0F172A';
$pwaLaunchAccent = '#BD9441';
$pwaLaunchSurface = '#F5F5F7';

if (is_array($pwaLaunchBranding) && function_exists('hotel_branding_hex')) {
    $pwaLaunchPrimary = hotel_branding_hex($pwaLaunchBranding['color_primary'] ?? null, $pwaLaunchPrimary);
    $pwaLaunchSecondary = hotel_branding_hex($pwaLaunchBranding['color_secondary'] ?? null, $pwaLaunchSecondary);
    $pwaLaunchAccent = hotel_branding_hex($pwaLaunchBranding['color_accent'] ?? null, $pwaLaunchAccent);
}

if (function_exists('hotel_branding_mix')) {
    $pwaLaunchSurface = hotel_branding_mix($pwaLaunchAccent, '#F5F5F7', 8);
}

$pwaLaunchStyle = sprintf(
    '--pwa-launch-primary:%s;--pwa-launch-secondary:%s;--pwa-launch-accent:%s;--pwa-launch-bg:%s;',
    htmlspecialchars($pwaLaunchPrimary, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($pwaLaunchSecondary, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($pwaLaunchAccent, ENT_QUOTES, 'UTF-8'),
    htmlspecialchars($pwaLaunchSurface, ENT_QUOTES, 'UTF-8')
);
?>

<div id="pwaLaunchSplash"
     class="pwa-launch-splash"
     aria-live="polite"
     aria-busy="true"
     style="<?= $pwaLaunchStyle ?>">
    <div class="pwa-launch-splash__content">
        <div class="pwa-launch-splash__logo">
            <img src="<?= htmlspecialchars($pwaLaunchLogo, ENT_QUOTES, 'UTF-8') ?>"
                 alt="<?= htmlspecialchars($pwaLaunchName, ENT_QUOTES, 'UTF-8') ?>"
                 decoding="async">
        </div>
        <p class="pwa-launch-splash__name"><?= htmlspecialchars($pwaLaunchName, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="pwa-launch-splash__bar" aria-hidden="true"><span></span></div>
    </div>
</div>
