<?php require_once APP_PATH . '/views/layout/header.php'; ?>
<?php
$productos_automaticos = $productos_automaticos ?? [];
$configuracion = $configuracion ?? [];
$tipos_habitacion = $tipos_habitacion ?? [];
$iconos = $iconos ?? [
    'sencilla' => 'fa-bed',
    'doble' => 'fa-bed',
    'triple' => 'fa-bed',
    'cuadruple' => 'fa-bed',
    'sencilla_manolo' => 'fa-bed',
    'doble_manolo' => 'fa-bed',
    'doble_jacuzzi' => 'fa-hot-tub',
    'sencilla_jacuzzi' => 'fa-hot-tub',
];

if (!function_exists('discount_safe')) {
    function discount_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

$tipoEstilos = [
    'sencilla' => ['color' => '#3B83BD', 'bg' => '#E8F3FA', 'label' => 'Sencilla'],
    'doble' => ['color' => '#16824E', 'bg' => '#EAF6EF', 'label' => 'Doble'],
    'triple' => ['color' => '#8B5CF6', 'bg' => '#F1EBFF', 'label' => 'Triple'],
    'cuadruple' => ['color' => '#B7791F', 'bg' => '#FFF4D8', 'label' => 'Cuádruple'],
    'sencilla_manolo' => ['color' => '#0F766E', 'bg' => '#E6FFFA', 'label' => 'Sencilla Manolo'],
    'doble_manolo' => ['color' => '#0891B2', 'bg' => '#E8F7FB', 'label' => 'Doble Manolo'],
    'doble_jacuzzi' => ['color' => '#BE5B8D', 'bg' => '#FDF2F8', 'label' => 'Doble jacuzzi'],
    'sencilla_jacuzzi' => ['color' => '#4F46E5', 'bg' => '#EEF2FF', 'label' => 'Sencilla jacuzzi'],
];

$configIndex = [];
$totalConfigurado = 0;
foreach ($configuracion as $tipo => $configs) {
    foreach ((array)$configs as $config) {
        $productoId = (int)($config['producto_id'] ?? 0);
        $cantidad = (int)($config['cantidad_descontar'] ?? 0);
        $configIndex[$tipo][$productoId] = $cantidad;
        $totalConfigurado += $cantidad;
    }
}

$productosCount = count($productos_automaticos);
$tiposCount = count($tipos_habitacion);
$stockTotal = array_sum(array_map(fn($producto) => (int)($producto['stock_actual'] ?? 0), $productos_automaticos));
$reglasActivas = 0;
foreach ($configIndex as $configs) {
    foreach ($configs as $cantidad) {
        if ((int)$cantidad > 0) {
            $reglasActivas++;
        }
    }
}

$hotelNombre = function_exists('current_hotel_display_name')
    ? current_hotel_display_name('Medisoft Hoteles')
    : 'Medisoft Hoteles';
?>

<style>
.discount-config-view {
    --disc-primary: var(--brand-primary, #1B2746);
    --disc-secondary: var(--brand-secondary, #0F172A);
    --disc-accent: var(--brand-accent, #BD9441);
    --disc-bg: color-mix(in srgb, var(--disc-accent) 8%, #F6F1E8);
    --disc-surface: color-mix(in srgb, var(--disc-accent) 3%, #FFFDF8);
    --disc-soft: color-mix(in srgb, var(--disc-primary) 5%, #FFFDF8);
    --disc-line: color-mix(in srgb, var(--disc-primary) 13%, #E8DCCA);
    --disc-line-soft: color-mix(in srgb, var(--disc-primary) 8%, #F1E8DA);
    --disc-text: #17233E;
    --disc-muted: #748096;
    --disc-good: #16824E;
    --disc-warn: #B7791F;
    min-height: 100vh;
    background:
        radial-gradient(circle at 12% 7%, color-mix(in srgb, var(--disc-accent) 22%, transparent), transparent 28rem),
        linear-gradient(135deg, color-mix(in srgb, var(--disc-primary) 6%, transparent) 0 1px, transparent 1px 30px),
        linear-gradient(180deg, var(--disc-bg), #FBFAF7 54%, #F1EAE0);
    color: var(--disc-text);
    opacity: 0;
    transition: opacity .24s ease;
}

.discount-config-view.loaded {
    opacity: 1;
}

.disc-shell {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 30px clamp(34px, 4vw, 76px) 50px;
    box-sizing: border-box;
}

.disc-hero {
    display: grid;
    grid-template-columns: minmax(0, 1.16fr) minmax(330px, .84fr);
    gap: 18px;
    margin-bottom: 18px;
}

.disc-hero-main,
.disc-side-panel,
.disc-metric,
.disc-panel,
.disc-preview-card,
.disc-empty {
    border: 1px solid var(--disc-line);
    background: var(--disc-surface);
    box-shadow: 0 20px 54px -42px rgba(15, 23, 42, .5);
}

.disc-hero-main {
    position: relative;
    min-height: 325px;
    overflow: hidden;
    border-radius: 28px;
    padding: clamp(24px, 4vw, 44px);
    border-color: color-mix(in srgb, var(--disc-accent) 28%, transparent);
    background:
        radial-gradient(circle at 86% 18%, color-mix(in srgb, var(--disc-accent) 30%, transparent), transparent 22rem),
        linear-gradient(135deg,
            color-mix(in srgb, var(--disc-primary) 54%, #101827),
            color-mix(in srgb, var(--disc-secondary) 58%, #060A12)
        );
}

.disc-hero-main::after {
    content: "";
    position: absolute;
    inset: auto -11% -42% 42%;
    height: 250px;
    background: radial-gradient(circle, color-mix(in srgb, var(--disc-accent) 42%, transparent), transparent 67%);
    pointer-events: none;
}

.disc-back,
.disc-btn,
.disc-counter button {
    transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
}

.disc-back {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    min-height: 38px;
    padding: 0 13px;
    border: 1px solid rgba(255, 255, 255, .16);
    border-radius: 13px;
    background: rgba(255, 255, 255, .09);
    color: #FFFDF8;
    font-weight: 850;
    text-decoration: none;
}

.disc-back:hover {
    transform: translateY(-1px);
    border-color: rgba(255, 255, 255, .32);
    background: rgba(255, 255, 255, .14);
}

.disc-kicker,
.disc-label,
.disc-section-kicker,
.disc-table th,
.disc-mini-label {
    color: var(--disc-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .07em;
    text-transform: uppercase;
}

.disc-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-top: 26px;
    width: fit-content;
    padding: 8px 11px;
    border: 1px solid rgba(255,255,255,.16);
    border-radius: 999px;
    background: rgba(255,255,255,.1);
    color: #FFFDF8;
    text-shadow: 0 1px 1px rgba(0,0,0,.22);
}

.disc-kicker i {
    color: color-mix(in srgb, var(--disc-accent) 42%, #FFFDF8);
}

.disc-hero-main h1 {
    position: relative;
    z-index: 1;
    max-width: 12ch;
    margin: 14px 0 14px;
    color: #FFFDF8;
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(2.4rem, 5vw, 5.15rem);
    font-weight: 700;
    line-height: .9;
    letter-spacing: 0;
    text-wrap: balance;
}

.disc-hero-main p {
    position: relative;
    z-index: 1;
    max-width: 68ch;
    margin: 0;
    color: rgba(255, 255, 255, .73);
    font-weight: 650;
    line-height: 1.6;
}

.disc-hero-actions {
    position: relative;
    z-index: 1;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 22px;
}

.disc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-height: 42px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--disc-accent) 45%, var(--disc-primary));
    border-radius: 13px;
    background: var(--disc-primary);
    color: #FFFDF8;
    font-weight: 900;
    text-decoration: none;
    cursor: pointer;
}

.disc-btn.is-accent {
    border-color: color-mix(in srgb, var(--disc-primary) 72%, #111827);
    background: color-mix(in srgb, var(--disc-primary) 88%, #111827);
    color: #FFFDF8;
    box-shadow: 0 16px 34px -28px color-mix(in srgb, var(--disc-primary) 72%, transparent);
}

.disc-btn.is-accent i {
    color: color-mix(in srgb, var(--disc-accent) 36%, #FFFDF8);
}

.disc-hero-main .disc-btn.is-accent {
    border-color: rgba(255,255,255,.74);
    background: #FFFDF8;
    color: color-mix(in srgb, var(--disc-primary) 82%, #111827);
    box-shadow: 0 18px 38px -28px rgba(0,0,0,.62);
}

.disc-hero-main .disc-btn.is-accent i {
    color: color-mix(in srgb, var(--disc-accent) 76%, var(--disc-primary));
}

.disc-btn.is-soft {
    border-color: rgba(255, 255, 255, .22);
    background: rgba(255, 255, 255, .1);
    color: #FFFDF8;
}

.disc-btn.is-warning {
    border-color: color-mix(in srgb, var(--disc-warn) 72%, #6B4208);
    background: linear-gradient(135deg, color-mix(in srgb, var(--disc-warn) 94%, #9A5F13), color-mix(in srgb, var(--disc-warn) 82%, #C78622));
    color: #1F1607;
    font-weight: 900;
    text-shadow: 0 1px 0 rgba(255, 255, 255, .22);
    box-shadow: 0 14px 28px -24px color-mix(in srgb, var(--disc-warn) 82%, #111827);
}

.disc-btn.is-warning i {
    color: #1F1607;
}

.disc-btn:hover,
.disc-counter button:hover {
    transform: translateY(-1px);
    box-shadow: 0 18px 32px -25px rgba(15, 23, 42, .58);
}

.disc-side-panel {
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 14px;
    border-radius: 25px;
    padding: 20px;
}

.disc-side-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    order: 1;
    margin-top: 0;
    padding: 15px;
    border: 1px solid var(--disc-line-soft);
    border-radius: 18px;
    background:
        linear-gradient(135deg, color-mix(in srgb, var(--disc-primary) 4%, #FFFDF8), color-mix(in srgb, var(--disc-accent) 4%, #FFFDF8));
}

.disc-side-title {
    margin-top: 6px;
    color: var(--disc-primary);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.45rem, 2.7vw, 2.1rem);
    font-weight: 700;
    line-height: 1;
}

.disc-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 38px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--disc-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--disc-accent) 82%, var(--disc-primary));
    line-height: 1;
}

.disc-icon > i {
    width: 1em;
    height: 1em;
    display: inline-grid;
    place-items: center;
    line-height: 1;
    margin: 0;
    transform: translateY(.5px);
}

.disc-flow {
    order: 2;
    display: grid;
    gap: 10px;
}

.disc-flow-item {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr);
    gap: 13px;
    align-items: center;
    padding: 12px 14px;
    border: 1px solid var(--disc-line-soft);
    border-radius: 16px;
    background: color-mix(in srgb, var(--disc-accent) 2%, #FFFDF8);
}

.disc-flow-item > .disc-icon {
    justify-self: center;
    align-self: center;
    width: 38px;
    height: 38px;
    border-radius: 13px;
}

.disc-flow-item strong {
    display: block;
    color: var(--disc-primary);
    font-weight: 950;
}

.disc-flow-item div span {
    display: block;
    color: var(--disc-muted);
    font-size: .78rem;
    font-weight: 750;
}

.disc-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.disc-metric {
    min-width: 0;
    overflow: hidden;
    border-radius: 21px;
    padding: 18px;
}

.disc-metric-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.disc-value {
    margin-top: 13px;
    color: var(--disc-primary);
    font-size: clamp(1.45rem, 2.8vw, 2.2rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.disc-note {
    margin-top: 9px;
    color: var(--disc-muted);
    font-size: .8rem;
    font-weight: 750;
}

.disc-panel {
    border-radius: 22px;
    overflow: hidden;
    margin-bottom: 18px;
}

.disc-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--disc-line-soft);
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--disc-accent) 6%, #FFFDF8), var(--disc-surface));
}

.disc-section-head h2,
.disc-section-head h3 {
    margin: 0;
    color: var(--disc-primary);
    font-size: 1.05rem;
    font-weight: 950;
}

.disc-section-body {
    padding: 18px 20px 20px;
}

.disc-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--disc-primary) 7%, #FFFDF8);
    color: var(--disc-primary);
    font-size: .73rem;
    font-weight: 900;
    white-space: nowrap;
}

.disc-table-wrap {
    overflow-x: auto;
}

.disc-table {
    width: 100%;
    min-width: max(920px, 100%);
    border-collapse: separate;
    border-spacing: 0;
}

.disc-table th {
    padding: 13px 10px;
    border-bottom: 1px solid var(--disc-line);
    background: color-mix(in srgb, var(--disc-accent) 7%, #FFFDF8);
    text-align: center;
    vertical-align: top;
}

.disc-table th:first-child,
.disc-table td:first-child {
    position: sticky;
    left: 0;
    z-index: 2;
    text-align: left;
    background: var(--disc-surface);
}

.disc-table th:first-child {
    z-index: 3;
    background: color-mix(in srgb, var(--disc-accent) 7%, #FFFDF8);
}

.disc-table td {
    padding: 13px 10px;
    border-bottom: 1px solid var(--disc-line-soft);
    text-align: center;
}

.disc-table tbody tr {
    transition: background .18s ease;
}

.disc-table tbody tr:hover {
    background: color-mix(in srgb, var(--disc-accent) 5%, #FFFDF8);
}

.disc-product-head strong {
    display: block;
    max-width: 145px;
    margin: 0 auto;
    color: var(--disc-primary);
    font-size: .78rem;
    font-weight: 950;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.disc-product-head span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    margin-top: 6px;
    padding: 4px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--disc-good) 10%, #FFFDF8);
    color: var(--disc-good);
    font-size: .66rem;
    font-weight: 900;
}

.disc-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 0 11px;
    border-radius: 13px;
    font-size: .82rem;
    font-weight: 950;
    white-space: nowrap;
}

.disc-type-icon {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: rgba(255,255,255,.62);
    font-size: .72rem;
}

.disc-counter {
    width: 104px;
    display: inline-grid;
    grid-template-columns: 30px 44px 30px;
    align-items: center;
    overflow: hidden;
    border: 1px solid var(--disc-line);
    border-radius: 14px;
    background: color-mix(in srgb, var(--disc-accent) 3%, #FFFDF8);
}

.disc-counter button {
    width: 30px;
    height: 34px;
    border: 0;
    background: transparent;
    color: var(--disc-primary);
    cursor: pointer;
}

.disc-counter button:hover {
    background: color-mix(in srgb, var(--disc-accent) 10%, #FFFDF8);
}

.disc-counter input {
    width: 44px;
    height: 34px;
    border: 0;
    background: transparent;
    color: var(--disc-primary);
    font-size: .9rem;
    font-weight: 950;
    text-align: center;
    outline: none;
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
}

.disc-counter input::-webkit-outer-spin-button,
.disc-counter input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.disc-total {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 42px;
    min-height: 30px;
    padding: 0 10px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--disc-accent) 15%, #FFFDF8);
    color: var(--disc-primary);
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.disc-actions {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 20px;
    border-top: 1px solid var(--disc-line-soft);
    background: color-mix(in srgb, var(--disc-accent) 3%, #FFFDF8);
}

.disc-mobile-grid {
    display: none;
    gap: 12px;
}

.disc-mobile-card {
    border: 1px solid var(--disc-line-soft);
    border-radius: 18px;
    background: color-mix(in srgb, var(--disc-primary) 3%, #FFFDF8);
    overflow: hidden;
}

.disc-mobile-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px;
    border-bottom: 1px solid var(--disc-line-soft);
}

.disc-mobile-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 13px;
    border-top: 1px solid var(--disc-line-soft);
}

.disc-mobile-row:first-of-type {
    border-top: 0;
}

.disc-mobile-row strong {
    color: var(--disc-primary);
    font-size: .86rem;
    font-weight: 850;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.disc-mobile-row span {
    display: block;
    color: var(--disc-muted);
    font-size: .72rem;
    font-weight: 750;
}

.disc-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
    gap: 14px;
}

.disc-preview-card {
    border-radius: 20px;
    padding: 17px;
    transition: transform .2s ease, border-color .2s ease, background .2s ease;
}

.disc-preview-card:hover {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--disc-accent) 34%, var(--disc-line));
    background: color-mix(in srgb, var(--disc-accent) 4%, var(--disc-surface));
}

.disc-preview-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 13px;
}

.disc-preview-head strong {
    display: block;
    color: var(--disc-primary);
    font-size: .96rem;
    font-weight: 950;
}

.disc-preview-head div > span {
    display: block;
    margin-top: 3px;
    color: var(--disc-muted);
    font-size: .76rem;
    font-weight: 750;
}

.disc-preview-head > .disc-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: grid;
    place-items: center;
    align-self: flex-start;
    margin: 0;
    padding: 0;
    border-radius: 14px;
}

.disc-preview-head > .disc-icon > i {
    width: auto;
    height: auto;
    display: block;
    line-height: 1;
    margin: 0;
    transform: none;
    text-align: center;
}

.disc-consumo {
    display: flex;
    align-items: baseline;
    gap: 6px;
    color: var(--disc-primary);
}

.disc-consumo strong {
    font-size: 2rem;
    line-height: 1;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.disc-progress {
    height: 8px;
    overflow: hidden;
    margin: 14px 0 11px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--disc-primary) 8%, #FFFDF8);
}

.disc-progress span {
    display: block;
    width: var(--progress, 0%);
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, var(--disc-good), color-mix(in srgb, var(--disc-accent) 78%, #FFFDF8));
    transition: width .25s ease;
}

.disc-empty {
    display: grid;
    place-items: center;
    min-height: 360px;
    border-radius: 24px;
    padding: 42px 20px;
    text-align: center;
}

.disc-empty i {
    width: 70px;
    height: 70px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
    border-radius: 24px;
    background: color-mix(in srgb, var(--disc-accent) 13%, #FFFDF8);
    color: color-mix(in srgb, var(--disc-accent) 82%, var(--disc-primary));
    font-size: 1.8rem;
}

.disc-empty h2 {
    margin: 0;
    color: var(--disc-primary);
    font-family: Georgia, "Times New Roman", serif;
    font-size: clamp(1.7rem, 4vw, 2.6rem);
    line-height: 1;
}

.disc-empty p {
    max-width: 520px;
    margin: 12px auto 22px;
    color: var(--disc-muted);
    font-weight: 750;
    line-height: 1.55;
}

@media (max-width: 1180px) {
    .disc-hero {
        grid-template-columns: 1fr;
    }

    .disc-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 720px) {
    .disc-shell {
        width: 100%;
        padding: 18px 12px 34px;
    }

    .disc-hero-main,
    .disc-side-panel,
    .disc-metric,
    .disc-panel,
    .disc-preview-card {
        border-radius: 18px;
    }

    .disc-hero-main {
        min-height: auto;
        padding: 22px;
    }

    .disc-kicker {
        margin-top: 20px;
    }

    .disc-hero-main h1 {
        max-width: 10ch;
        font-size: clamp(2.1rem, 14vw, 3.55rem);
    }

    .disc-metrics,
    .disc-actions {
        grid-template-columns: 1fr;
        flex-direction: column;
    }

    .disc-section-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .disc-table-desktop {
        display: none;
    }

    .disc-mobile-grid {
        display: grid;
    }
}

@media print {
    .discount-config-view {
        background: #fff !important;
    }

    .disc-hero-actions,
    .disc-back,
    .disc-actions,
    .discount-config-view button {
        display: none !important;
    }

    .disc-shell {
        width: 100%;
        padding: 0;
    }

    .disc-panel,
    .disc-metric {
        box-shadow: none !important;
    }
}
</style>

<div class="discount-config-view config-view">
    <main class="disc-shell">
        <section class="disc-hero">
            <div class="disc-hero-main">
                <a href="<?= url('inventario') ?>" class="disc-back">
                    <i class="fas fa-arrow-left"></i>
                    Inventario
                </a>

                <div class="disc-kicker">
                    <i class="fas fa-layer-group"></i>
                    Configuración de descuentos
                </div>

                <h1>Matriz de consumo automático</h1>
                <p>
                    Define cuántas unidades se descuentan al check-in según el tipo de habitación. La configuración se guarda como reglas operativas para el hotel.
                </p>

                <div class="disc-hero-actions">
                    <?php if (!empty($productos_automaticos)): ?>
                        <a href="#matrizDescuentos" class="disc-btn is-accent">
                            <i class="fas fa-sliders-h"></i>
                            Configurar matriz
                        </a>
                    <?php endif; ?>
                    <a href="<?= url('inventario') ?>" class="disc-btn is-soft">
                        <i class="fas fa-boxes"></i>
                        Ver inventario
                    </a>
                </div>
            </div>

            <aside class="disc-side-panel">
                <div class="disc-side-top">
                    <div>
                        <span class="disc-label">Hotel activo</span>
                        <div class="disc-side-title"><?= discount_safe($hotelNombre, 'Medisoft Hoteles') ?></div>
                    </div>
                    <span class="disc-icon">
                        <i class="fas fa-hotel"></i>
                    </span>
                </div>

                <div class="disc-flow">
                    <div class="disc-flow-item">
                        <span class="disc-icon"><i class="fas fa-check-circle"></i></span>
                        <div>
                            <strong>Productos elegibles</strong>
                            <span>Solo aparecen productos con descuento automático activo.</span>
                        </div>
                    </div>
                    <div class="disc-flow-item">
                        <span class="disc-icon"><i class="fas fa-sign-in-alt"></i></span>
                        <div>
                            <strong>Aplicación al check-in</strong>
                            <span>El descuento se ejecuta una vez al iniciar la estancia.</span>
                        </div>
                    </div>
                    <div class="disc-flow-item">
                        <span class="disc-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        <div>
                            <strong>Control de stock</strong>
                            <span>Si no hay stock disponible, no se descuenta inventario.</span>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        <section class="disc-metrics" aria-label="Resumen de descuentos automáticos">
            <article class="disc-metric">
                <div class="disc-metric-head">
                    <span class="disc-label">Productos</span>
                    <span class="disc-icon"><i class="fas fa-box"></i></span>
                </div>
                <div class="disc-value"><?= number_format($productosCount) ?></div>
                <div class="disc-note">Con auto-descuento activo.</div>
            </article>

            <article class="disc-metric">
                <div class="disc-metric-head">
                    <span class="disc-label">Tipos</span>
                    <span class="disc-icon"><i class="fas fa-door-open"></i></span>
                </div>
                <div class="disc-value"><?= number_format($tiposCount) ?></div>
                <div class="disc-note">Tipos de habitación configurables.</div>
            </article>

            <article class="disc-metric">
                <div class="disc-metric-head">
                    <span class="disc-label">Reglas activas</span>
                    <span class="disc-icon"><i class="fas fa-sliders-h"></i></span>
                </div>
                <div class="disc-value"><?= number_format($reglasActivas) ?></div>
                <div class="disc-note"><?= number_format($totalConfigurado) ?> unidades configuradas.</div>
            </article>

            <article class="disc-metric">
                <div class="disc-metric-head">
                    <span class="disc-label">Stock visible</span>
                    <span class="disc-icon"><i class="fas fa-warehouse"></i></span>
                </div>
                <div class="disc-value"><?= number_format($stockTotal) ?></div>
                <div class="disc-note">Unidades en productos elegibles.</div>
            </article>
        </section>

        <?php if (empty($productos_automaticos)): ?>
            <section class="disc-empty">
                <div>
                    <i class="fas fa-box-open"></i>
                    <h2>No hay productos configurados</h2>
                    <p>
                        Todavía no hay productos marcados para descuento automático. Activa esa opción desde inventario para poder definir consumos por tipo de habitación.
                    </p>
                    <a href="<?= url('inventario') ?>" class="disc-btn is-accent">
                        <i class="fas fa-cog"></i>
                        Configurar productos
                    </a>
                </div>
            </section>
        <?php else: ?>
            <form method="POST" action="<?= url('inventario/guardarConfiguracion') ?>" id="formConfiguracion">
                <?= csrf_field() ?>

                <section class="disc-panel" id="matrizDescuentos">
                    <div class="disc-section-head">
                        <div>
                            <span class="disc-section-kicker">Matriz por habitación</span>
                            <h2>Cantidad a descontar por check-in</h2>
                        </div>
                        <span class="disc-pill"><?= number_format($productosCount) ?> productos · <?= number_format($tiposCount) ?> tipos</span>
                    </div>

                    <div class="disc-table-desktop">
                        <div class="disc-table-wrap">
                            <table class="disc-table">
                                <thead>
                                    <tr>
                                        <th>Tipo de habitación</th>
                                        <?php foreach ($productos_automaticos as $producto): ?>
                                            <th>
                                                <div class="disc-product-head">
                                                    <strong title="<?= discount_safe($producto['nombre']) ?>"><?= discount_safe($producto['nombre']) ?></strong>
                                                    <span><i class="fas fa-box"></i> Stock <?= number_format((int)($producto['stock_actual'] ?? 0)) ?></span>
                                                </div>
                                            </th>
                                        <?php endforeach; ?>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): ?>
                                        <?php $tipoStyle = $tipoEstilos[$tipo_key] ?? ['color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $tipo_nombre]; ?>
                                        <tr>
                                            <td>
                                                <span class="disc-type-badge" style="background: <?= $tipoStyle['bg'] ?>; color: <?= $tipoStyle['color'] ?>;">
                                                    <span class="disc-type-icon">
                                                        <i class="fas <?= discount_safe($iconos[$tipo_key] ?? 'fa-bed') ?>"></i>
                                                    </span>
                                                    <?= discount_safe($tipo_nombre) ?>
                                                </span>
                                            </td>
                                            <?php foreach ($productos_automaticos as $producto): ?>
                                                <?php
                                                $productoId = (int)($producto['id'] ?? 0);
                                                $cantidad_actual = (int)($configIndex[$tipo_key][$productoId] ?? 0);
                                                ?>
                                                <td>
                                                    <div class="disc-counter">
                                                        <button type="button" class="btn-decrease"
                                                                data-tipo="<?= discount_safe($tipo_key) ?>"
                                                                data-producto="<?= $productoId ?>"
                                                                aria-label="Restar">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number"
                                                               class="cantidad-input"
                                                               name="config[<?= discount_safe($tipo_key) ?>][<?= $productoId ?>]"
                                                               value="<?= $cantidad_actual ?>"
                                                               min="0"
                                                               max="10"
                                                               data-tipo="<?= discount_safe($tipo_key) ?>"
                                                               data-producto="<?= $productoId ?>">
                                                        <button type="button" class="btn-increase"
                                                                data-tipo="<?= discount_safe($tipo_key) ?>"
                                                                data-producto="<?= $productoId ?>"
                                                                aria-label="Sumar">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <span class="disc-total total-row" data-tipo="<?= discount_safe($tipo_key) ?>">0</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="disc-mobile-grid disc-section-body">
                        <?php foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): ?>
                            <?php $tipoStyle = $tipoEstilos[$tipo_key] ?? ['color' => '#64748B', 'bg' => '#F1F5F9', 'label' => $tipo_nombre]; ?>
                            <article class="disc-mobile-card">
                                <div class="disc-mobile-head">
                                    <span class="disc-type-badge" style="background: <?= $tipoStyle['bg'] ?>; color: <?= $tipoStyle['color'] ?>;">
                                        <span class="disc-type-icon">
                                            <i class="fas <?= discount_safe($iconos[$tipo_key] ?? 'fa-bed') ?>"></i>
                                        </span>
                                        <?= discount_safe($tipo_nombre) ?>
                                    </span>
                                    <span class="disc-total total-row" data-tipo="<?= discount_safe($tipo_key) ?>">0</span>
                                </div>

                                <?php foreach ($productos_automaticos as $producto): ?>
                                    <?php
                                    $productoId = (int)($producto['id'] ?? 0);
                                    $cantidad_actual = (int)($configIndex[$tipo_key][$productoId] ?? 0);
                                    ?>
                                    <div class="disc-mobile-row">
                                        <div>
                                            <strong><?= discount_safe($producto['nombre']) ?></strong>
                                            <span>Stock <?= number_format((int)($producto['stock_actual'] ?? 0)) ?> unidades</span>
                                        </div>
                                        <div class="disc-counter">
                                            <button type="button" class="btn-decrease"
                                                    data-tipo="<?= discount_safe($tipo_key) ?>"
                                                    data-producto="<?= $productoId ?>"
                                                    aria-label="Restar">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number"
                                                   class="cantidad-input"
                                                   name="config_m[<?= discount_safe($tipo_key) ?>][<?= $productoId ?>]"
                                                   value="<?= $cantidad_actual ?>"
                                                   min="0"
                                                   max="10"
                                                   data-tipo="<?= discount_safe($tipo_key) ?>"
                                                   data-producto="<?= $productoId ?>">
                                            <button type="button" class="btn-increase"
                                                    data-tipo="<?= discount_safe($tipo_key) ?>"
                                                    data-producto="<?= $productoId ?>"
                                                    aria-label="Sumar">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="disc-actions">
                        <button type="button" class="disc-btn is-warning" id="btnReset">
                            <i class="fas fa-undo"></i>
                            Restablecer a 0
                        </button>
                        <button type="submit" class="disc-btn is-accent">
                            <i class="fas fa-save"></i>
                            Guardar configuración
                        </button>
                        <button type="button" class="disc-btn is-warning" id="btnResetMobile" style="display:none;">
                            <i class="fas fa-undo"></i>
                            Restablecer
                        </button>
                    </div>
                </section>
            </form>

            <section class="disc-panel">
                <div class="disc-section-head">
                    <div>
                        <span class="disc-section-kicker">Vista previa</span>
                        <h2>Consumo estimado por producto</h2>
                    </div>
                    <span class="disc-pill">Estimación diaria</span>
                </div>
                <div class="disc-section-body">
                    <div class="disc-preview-grid">
                        <?php foreach ($productos_automaticos as $producto): ?>
                            <?php
                            $productoId = (int)($producto['id'] ?? 0);
                            $stock = (int)($producto['stock_actual'] ?? 0);
                            $progress = $stock > 0 ? min(100, max(7, 20)) : 0;
                            ?>
                            <article class="disc-preview-card">
                                <div class="disc-preview-head">
                                    <div>
                                        <strong><?= discount_safe($producto['nombre']) ?></strong>
                                        <span>Producto con descuento automático</span>
                                    </div>
                                    <span class="disc-icon"><i class="fas fa-box"></i></span>
                                </div>
                                <div class="disc-consumo">
                                    <strong class="consumo-diario" data-producto="<?= $productoId ?>">0</strong>
                                    <span class="disc-mini-label">uds/día</span>
                                </div>
                                <div class="disc-progress">
                                    <span style="--progress: <?= $progress ?>%;"></span>
                                </div>
                                <div class="disc-note">Stock actual: <strong><?= number_format($stock) ?> uds</strong></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>

<script>
function syncInputs(tipo, productoId, value) {
    document.querySelectorAll(`input[data-tipo="${tipo}"][data-producto="${productoId}"]`).forEach(input => {
        input.value = value;
    });

    const mobileInput = document.querySelector(`input[name="config_m[${tipo}][${productoId}]"]`);
    const desktopInput = document.querySelector(`input[name="config[${tipo}][${productoId}]"]`);
    if (mobileInput && desktopInput) {
        mobileInput.value = desktopInput.value = value;
    }
}

document.getElementById('formConfiguracion')?.addEventListener('submit', function(event) {
    document.querySelectorAll('input[name^="config_m["]').forEach(mobileInput => {
        const name = mobileInput.name.replace('config_m[', 'config[');
        const desktopInput = document.querySelector(`input[name="${name}"]`);
        if (desktopInput) {
            desktopInput.value = mobileInput.value;
        }
    });

    let hayConfiguracion = false;
    document.querySelectorAll('input[name^="config["]').forEach(input => {
        if ((parseInt(input.value, 10) || 0) > 0) {
            hayConfiguracion = true;
        }
    });

    if (!hayConfiguracion) {
        event.preventDefault();
        if (confirm('No hay ninguna configuración establecida. ¿Desea continuar?')) {
            this.submit();
        }
    }
});

document.querySelectorAll('.btn-decrease').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const value = parseInt(input.value, 10) || 0;
        if (value > 0) {
            const newValue = value - 1;
            syncInputs(input.dataset.tipo, input.dataset.producto, newValue);
            actualizarTotales();
        }
    });
});

document.querySelectorAll('.btn-increase').forEach(button => {
    button.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const value = parseInt(input.value, 10) || 0;
        if (value < 10) {
            const newValue = value + 1;
            syncInputs(input.dataset.tipo, input.dataset.producto, newValue);
            actualizarTotales();
        }
    });
});

document.querySelectorAll('.cantidad-input').forEach(input => {
    input.addEventListener('change', function() {
        syncInputs(this.dataset.tipo, this.dataset.producto, this.value);
        actualizarTotales();
    });

    input.addEventListener('input', function() {
        syncInputs(this.dataset.tipo, this.dataset.producto, this.value);
        actualizarTotales();
    });
});

function actualizarTotales() {
    <?php foreach ($tipos_habitacion as $tipo_key => $tipo_nombre): ?>
    let total_<?= preg_replace('/[^a-zA-Z0-9_]/', '_', $tipo_key) ?> = 0;
    document.querySelectorAll(`input[data-tipo="<?= discount_safe($tipo_key) ?>"][name^="config["]`).forEach(input => {
        total_<?= preg_replace('/[^a-zA-Z0-9_]/', '_', $tipo_key) ?> += parseInt(input.value, 10) || 0;
    });
    document.querySelectorAll(`.total-row[data-tipo="<?= discount_safe($tipo_key) ?>"]`).forEach(element => {
        element.textContent = total_<?= preg_replace('/[^a-zA-Z0-9_]/', '_', $tipo_key) ?>;
    });
    <?php endforeach; ?>

    actualizarConsumoDiario();
}

function actualizarConsumoDiario() {
    <?php foreach ($productos_automaticos as $producto): ?>
    let consumo_<?= (int)$producto['id'] ?> = 0;
    document.querySelectorAll(`input[data-producto="<?= (int)$producto['id'] ?>"][name^="config["]`).forEach(input => {
        consumo_<?= (int)$producto['id'] ?> += parseInt(input.value, 10) || 0;
    });
    consumo_<?= (int)$producto['id'] ?> = Math.round(consumo_<?= (int)$producto['id'] ?> * 0.5);
    document.querySelectorAll(`.consumo-diario[data-producto="<?= (int)$producto['id'] ?>"]`).forEach(element => {
        element.textContent = consumo_<?= (int)$producto['id'] ?>;
    });
    <?php endforeach; ?>
}

function resetAll() {
    if (confirm('¿Está seguro de restablecer toda la configuración a 0?')) {
        document.querySelectorAll('.cantidad-input').forEach(input => {
            input.value = 0;
        });
        actualizarTotales();
    }
}

document.getElementById('btnReset')?.addEventListener('click', resetAll);
document.getElementById('btnResetMobile')?.addEventListener('click', resetAll);

document.addEventListener('DOMContentLoaded', function() {
    const view = document.querySelector('.config-view');
    if (view) {
        requestAnimationFrame(() => view.classList.add('loaded'));
    }
    actualizarTotales();
});
</script>

<?php require_once APP_PATH . '/views/layout/footer.php'; ?>
