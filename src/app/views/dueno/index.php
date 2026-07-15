<?php
/**
 * Modo Dueno — resumen remoto de solo lectura (bloque modo_dueno).
 *
 * Una sola columna, movil primero. Para este rol la vista ES la app:
 * el CSS de abajo retira el cromado del layout (sidebar, headers, barra
 * inferior) SOLO en esta pagina (body.page-dueno); ninguna otra vista
 * se ve afectada. Lenguaje visual: Deleite Sereno (base marfil serena,
 * serif de despliegue, cero jerga, cero botones de escritura).
 */
$resumen = is_array($resumen ?? null) ? $resumen : [];
$duSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

// Nombre de pila del dueno (sin inventar honorificos).
$duNombre = trim((string) (function_exists('user_name') ? user_name() : ''));
$duNombrePila = $duNombre !== '' ? explode(' ', $duNombre)[0] : '';

// Fecha de hoy en espanol, sin depender del locale del servidor.
$duDias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$duMeses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$duFecha = $duDias[(int) date('w')] . ' ' . (int) date('j') . ' de ' . $duMeses[(int) date('n')];

$duHotelNombre = (string) ($resumen['hotel']['nombre'] ?? '');
$duSaludo = (string) ($resumen['saludo'] ?? 'Hola');

// Logo del hotel si su configuracion tiene uno.
$duLogo = null;
if (function_exists('current_hotel_branding') && function_exists('hotel_branding_asset_url')) {
    try {
        $duBranding = current_hotel_branding();
        $duLogo = hotel_branding_asset_url($duBranding['logo_url'] ?? null);
    } catch (Throwable $e) {
        $duLogo = null;
    }
}
?>

<style>
/* ── El Modo Dueno retira el cromado del layout SOLO en esta pagina ── */
body.page-dueno #sidebar,
body.page-dueno .mobile-header-modern,
body.page-dueno .hotel-header,
body.page-dueno #hotel-bottom-nav,
body.page-dueno .scroll-progress { display: none !important; }
body.page-dueno .main-content { padding: 0 !important; margin: 0 !important; }

/* ── Vista ── */
.du {
    /* Marca (se re-tematiza por hotel) */
    --du-brand: var(--brand-primary, #1B2746);
    --du-gold: var(--brand-accent, #BD9441);
    /* Semanticos (fijos por significado) */
    --du-success: #1E9E63; --du-success-soft: #E7F4EC;
    --du-warn: #C2841C; --du-warn-soft: #FBF3E2;
    --du-error: #D64539;
    /* Base serena */
    --du-surface: #FFFFFF; --du-warm: #FCFAF5; --du-ivory: #F6F2EA;
    --du-line: #E7E1D4; --du-ink: #20293A; --du-ink-soft: #5C6675; --du-ink-faint: #8B94A3;
    --du-radius: 22px; --du-radius-md: 15px;
    --du-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --du-ease: cubic-bezier(.22, 1, .36, 1);

    min-height: 100dvh;
    font-family: 'DM Sans', 'Outfit', system-ui, -apple-system, sans-serif;
    font-size: 18px;
    line-height: 1.55;
    color: var(--du-ink);
    background:
        radial-gradient(900px 420px at 85% -10%, color-mix(in srgb, var(--du-brand) 7%, transparent), transparent 60%),
        linear-gradient(180deg, var(--du-ivory), #FBF8F2 320px);
    padding: max(20px, env(safe-area-inset-top)) 18px calc(48px + env(safe-area-inset-bottom));
}
.du-col { max-width: 560px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px; }

/* Encabezado: saludo + fecha + hotel */
.du-hola { padding: 10px 6px 2px; }
.du-hola-fecha { font-size: 15px; color: var(--du-ink-soft); letter-spacing: .01em; }
.du-hola-titulo { font-family: var(--du-serif); font-weight: 700; font-size: clamp(30px, 8vw, 38px); line-height: 1.12; margin-top: 2px; }
.du-hola-hotel { display: flex; align-items: center; gap: 10px; margin-top: 10px; color: var(--du-ink-soft); font-size: 16px; }
.du-hola-hotel img { width: 34px; height: 34px; border-radius: 10px; object-fit: cover; background: #fff; border: 1px solid var(--du-line); }

/* Tarjeta base */
.du-card {
    background: var(--du-surface);
    border: 1px solid var(--du-line);
    border-radius: var(--du-radius);
    padding: 22px 20px;
    box-shadow: 0 14px 34px -24px rgba(20, 40, 80, .35);
}

/* Modo oscuro (remapeo del sistema via dark-theme.css + tokens propios) */
html[data-theme="dark"] .du {
    --du-surface: #211F1A; --du-warm: #1B1915; --du-ivory: #171612;
    --du-line: rgba(239, 233, 220, .13);
    --du-ink: #EFE9DC; --du-ink-soft: #B9B2A2; --du-ink-faint: #8A8478;
    background:
        radial-gradient(900px 420px at 85% -10%, color-mix(in srgb, var(--du-brand) 12%, transparent), transparent 60%),
        linear-gradient(180deg, #171612, #131210 320px);
}
html[data-theme="dark"] .du-card { box-shadow: 0 14px 34px -24px rgba(0, 0, 0, .6); }

@media (prefers-reduced-motion: reduce) {
    .du * { transition-duration: .01ms !important; animation: none !important; }
}
</style>

<div class="du" id="modoDueno">
    <div class="du-col">
        <header class="du-hola">
            <p class="du-hola-fecha"><?= $duSafe(ucfirst($duFecha)) ?></p>
            <h1 class="du-hola-titulo"><?= $duSafe($duSaludo) ?><?= $duNombrePila !== '' ? ', ' . $duSafe($duNombrePila) : '' ?></h1>
            <p class="du-hola-hotel">
                <?php if ($duLogo): ?><img src="<?= $duSafe($duLogo) ?>" alt="" aria-hidden="true"><?php endif; ?>
                <span><?= $duSafe($duHotelNombre) ?></span>
            </p>
        </header>
    </div>
</div>
