<!-- Vista Editar Habitación -->
<?php
$tiposHabitacion = is_array($tipos ?? null) && !empty($tipos) ? $tipos : Habitacion::getTipos();
if (!isset($tiposHabitacion[$habitacion['tipo']])) {
    $tiposHabitacion[$habitacion['tipo']] = function_exists('get_tipo_habitacion')
        ? get_tipo_habitacion($habitacion['tipo'])
        : ucfirst(str_replace('_', ' ', (string) $habitacion['tipo']));
}

$pisosHabitacion = is_array($pisos ?? null) && !empty($pisos) ? $pisos : Habitacion::getPisos();
if (!isset($pisosHabitacion[(int) $habitacion['piso']])) {
    $pisosHabitacion[(int) $habitacion['piso']] = 'Piso ' . (int) $habitacion['piso'];
}

$amenidadesHabitacion = is_array($amenidades ?? null) && !empty($amenidades)
    ? $amenidades
    : [
        'pantalla' => 'Pantalla',
        'balcon' => 'Balcon',
        'jacuzzi' => 'Jacuzzi',
        'amplia' => 'Mas amplia',
    ];

$habitacionCaracteristicasPlain = strtolower((string)($habitacion['caracteristicas'] ?? ''));
$habitacionCaracteristicasAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $habitacionCaracteristicasPlain);
if ($habitacionCaracteristicasAscii !== false) {
    $habitacionCaracteristicasPlain = $habitacionCaracteristicasAscii;
}
$habitacionCaracteristicasLimpias = trim((string)($habitacion['caracteristicas'] ?? ''));
$habitacionTipoCatalogoMarker = null;
if (preg_match('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*([^\[\r\n,;]+?)\s*\[([a-z0-9_\-]+)\]/i', (string)($habitacion['caracteristicas'] ?? ''), $habitacionTipoCatalogoMatch)) {
    $habitacionTipoCatalogoCodigo = strtolower(trim((string)($habitacionTipoCatalogoMatch[2] ?? '')));
    $habitacionTipoCatalogoLabel = trim((string)($habitacionTipoCatalogoMatch[1] ?? ''));
    if ($habitacionTipoCatalogoCodigo !== '') {
        $habitacionTipoCatalogoMarker = $habitacionTipoCatalogoCodigo;
        if (!isset($tiposHabitacion[$habitacionTipoCatalogoCodigo])) {
            $tiposHabitacion[$habitacionTipoCatalogoCodigo] = $habitacionTipoCatalogoLabel !== ''
                ? $habitacionTipoCatalogoLabel
                : ucfirst(str_replace('_', ' ', $habitacionTipoCatalogoCodigo));
        }
    }
    $habitacionCaracteristicasLimpias = preg_replace('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*[^\[\r\n,;]+?\s*\[[a-z0-9_\-]+\]/i', '', $habitacionCaracteristicasLimpias);
    $habitacionCaracteristicasLimpias = trim(preg_replace('/\s*,\s*,+/', ',', (string)$habitacionCaracteristicasLimpias), " \t\n\r\0\x0B,;");
}

$habitacionTipoSeleccionado = (string)($habitacion['tipo'] ?? '');
if ($habitacionTipoCatalogoMarker !== null) {
    $habitacionTipoSeleccionado = $habitacionTipoCatalogoMarker;
} elseif (strpos($habitacionCaracteristicasPlain, 'jacuzzi') !== false) {
    if ($habitacionTipoSeleccionado === 'doble') {
        $habitacionTipoSeleccionado = 'doble_jacuzzi';
    } elseif ($habitacionTipoSeleccionado === 'sencilla') {
        $habitacionTipoSeleccionado = 'sencilla_jacuzzi';
    }
}

$habitacionOldInput = is_array($_SESSION['old_input'] ?? null) ? $_SESSION['old_input'] : [];
$habitacionTieneOldInput = !empty($habitacionOldInput);
$habitacionCampoFormulario = static function (string $campo, $valorActual) use ($habitacionOldInput, $habitacionTieneOldInput) {
    return $habitacionTieneOldInput && array_key_exists($campo, $habitacionOldInput)
        ? $habitacionOldInput[$campo]
        : $valorActual;
};

$habitacionTipoFormulario = (string)$habitacionCampoFormulario('tipo', $habitacionTipoSeleccionado);
$habitacionNumeroFormulario = (string)$habitacionCampoFormulario('numero', $habitacion['numero'] ?? '');
$habitacionPisoFormulario = (int)$habitacionCampoFormulario('piso', $habitacion['piso'] ?? 0);
$habitacionPrecioFormulario = (string)$habitacionCampoFormulario('precio_base', $habitacion['precio_base'] ?? '');
$habitacionCapacidadFormulario = (string)$habitacionCampoFormulario('capacidad_personas', $habitacion['capacidad_personas'] ?? 2);
$habitacionCamasMatrimonialesFormulario = (string)$habitacionCampoFormulario('camas_matrimoniales', $habitacion['camas_matrimoniales'] ?? 1);
$habitacionCamasIndividualesFormulario = (string)$habitacionCampoFormulario('camas_individuales', $habitacion['camas_individuales'] ?? 0);
$habitacionCaracteristicasFormulario = (string)$habitacionCampoFormulario('caracteristicas', $habitacionCaracteristicasLimpias);
$habitacionActivaFormulario = $habitacionTieneOldInput ? !empty($habitacionOldInput['activa']) : !empty($habitacion['activa']);
$habitacionEspecialesFormulario = null;
if ($habitacionTieneOldInput) {
    $habitacionEspecialesFormulario = $habitacionOldInput['caracteristicas_especiales'] ?? [];
    if (!is_array($habitacionEspecialesFormulario)) {
        $habitacionEspecialesFormulario = [$habitacionEspecialesFormulario];
    }
    $habitacionEspecialesFormulario = array_map('strval', $habitacionEspecialesFormulario);
}

$habitacionTipoLabel = $tiposHabitacion[$habitacionTipoFormulario] ?? ($tiposHabitacion[$habitacion['tipo']] ?? get_tipo_habitacion($habitacion['tipo']));

$rangosHabitacion = Habitacion::getRangoPrecios();
if (function_exists('hotel_room_catalog_type_rows')) {
    foreach (hotel_room_catalog_type_rows(null, true) as $catalogTypeRow) {
        $catalogTypeCode = (string) ($catalogTypeRow['codigo'] ?? '');
        $catalogTypePrice = (float) ($catalogTypeRow['precio_base_default'] ?? 0);

        if ($catalogTypeCode !== '' && $catalogTypePrice > 0 && !isset($rangosHabitacion[$catalogTypeCode])) {
            $rangosHabitacion[$catalogTypeCode] = [
                'min' => $catalogTypePrice,
                'max' => $catalogTypePrice,
            ];
        }
    }
}

$reservacionesBloqueantesEliminacion = is_array($reservaciones_bloqueantes_eliminacion ?? null)
    ? $reservaciones_bloqueantes_eliminacion
    : [];
$hayReservacionesBloqueantesEliminacion = count($reservacionesBloqueantesEliminacion) > 0;
$estadosReservacionEliminacion = [
    'confirmada' => 'Confirmada',
    'checked_in' => 'Check-in',
];
$habitacionNumeroEliminacion = (string)($habitacion['numero'] ?? '');
?>

<style id="edit-room-redesign">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.edit-room-page {
    --er-brand: var(--brand-primary, #1B2746);
    --er-brand-dark: var(--brand-secondary, #0F172A);
    --er-accent: var(--brand-accent, #BD9441);
    --er-ink: #1C2635;
    --er-muted: #687586;
    --er-sky: #477CA8;
    --er-sage: #5E7F69;
    --er-clay: #B86A54;
    --er-sun: #C18A28;
    --er-line: rgba(28, 38, 53, .12);
    --er-panel: rgba(255, 255, 255, .94);
    min-height: 100vh;
    background:
        radial-gradient(circle at 6% 8%, color-mix(in srgb, var(--er-sky) 16%, transparent), transparent 24rem),
        radial-gradient(circle at 92% 5%, color-mix(in srgb, var(--er-accent) 14%, transparent), transparent 25rem),
        radial-gradient(circle at 74% 86%, color-mix(in srgb, var(--er-sage) 13%, transparent), transparent 28rem),
        linear-gradient(180deg, #FBFAF5 0%, #F4F6F1 44%, #EEF5F6 100%);
    color: var(--er-ink);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.edit-room-page * {
    box-sizing: border-box;
}

.edit-room-page :where(a, button, input, textarea, select, label, span, p) {
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.edit-room-page .edit-room-breadcrumb {
    color: var(--er-muted);
    font-weight: 800;
}

.edit-room-page .edit-room-breadcrumb a {
    color: color-mix(in srgb, var(--er-brand) 62%, var(--er-ink));
    transition: color .18s ease, transform .18s ease;
}

.edit-room-page .edit-room-breadcrumb a:hover {
    color: var(--er-clay);
    transform: translateY(-1px);
}

.edit-room-page .edit-room-hero-card,
.edit-room-page .edit-room-card,
.edit-room-page .edit-room-status-card,
.edit-room-page .edit-room-preview-card,
.edit-room-page .edit-room-active-card {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--er-line);
    border-radius: 24px;
    background: var(--er-panel);
    box-shadow:
        0 1px 0 rgba(255,255,255,.82) inset,
        0 22px 48px -38px rgba(28, 38, 53, .58);
}

.edit-room-page .edit-room-hero-card {
    border-left: 0;
    padding: clamp(22px, 3vw, 32px) !important;
    background:
        radial-gradient(circle at 95% 6%, color-mix(in srgb, var(--er-sun) 16%, transparent), transparent 16rem),
        linear-gradient(135deg, rgba(255,255,255,.98), rgba(247,250,248,.92));
}

.edit-room-page .edit-room-hero-card::before,
.edit-room-page .edit-room-card::before,
.edit-room-page .edit-room-status-card::before,
.edit-room-page .edit-room-preview-card::before,
.edit-room-page .edit-room-active-card::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: var(--er-section-accent, var(--er-sky));
    opacity: .78;
}

.edit-room-page .edit-room-hero-card::before {
    background: linear-gradient(180deg, var(--er-sky), var(--er-sage) 42%, var(--er-accent) 72%, var(--er-clay));
}

.edit-room-page h1 {
    color: var(--er-ink) !important;
    font-family: 'Cormorant Garamond', Georgia, serif;
    font-size: clamp(3.2rem, 6vw, 5.6rem) !important;
    line-height: .88;
    letter-spacing: 0;
}

.edit-room-page .edit-room-title-kicker {
    display: block;
    margin-bottom: 8px;
    color: var(--er-muted);
    font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    font-size: clamp(.84rem, 1.2vw, .98rem);
    font-weight: 950;
    letter-spacing: .08em;
    line-height: 1.25;
    text-transform: uppercase;
}

.edit-room-page h1 + p {
    color: var(--er-muted) !important;
    font-weight: 760;
}

.edit-room-page .edit-room-hero-icon {
    background: linear-gradient(145deg, #FFFFFF, #EAF3FA) !important;
    box-shadow: 0 16px 34px -30px rgba(28, 38, 53, .72);
}

.edit-room-page .edit-room-hero-icon i {
    color: color-mix(in srgb, var(--er-sky) 72%, var(--er-ink)) !important;
}

.edit-room-page .edit-room-card {
    --er-section-accent: var(--er-sky);
}

.edit-room-page .edit-section-features {
    --er-section-accent: var(--er-sage);
}

.edit-room-page .edit-section-photos {
    --er-section-accent: var(--er-clay);
}

.edit-room-page .edit-room-status-card {
    --er-section-accent: color-mix(in srgb, var(--er-brand) 58%, var(--er-sky));
}

.edit-room-page .edit-room-preview-card {
    --er-section-accent: var(--er-sun);
}

.edit-room-page .edit-room-active-card {
    --er-section-accent: var(--er-clay);
}

.edit-room-page .edit-room-card > div:first-child {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 18px 20px !important;
    border-bottom: 1px solid rgba(28, 38, 53, .1);
    background: linear-gradient(90deg, rgba(255,255,255,.98), rgba(247,249,248,.94)) !important;
}

.edit-room-page .edit-room-card > div:first-child h2 {
    margin: 0;
    color: var(--er-ink) !important;
    font-size: 1.04rem !important;
    font-weight: 950;
}

.edit-room-page .edit-room-card > div:first-child h2 i {
    width: 38px;
    height: 38px;
    display: inline-grid;
    place-items: center;
    margin-right: 10px !important;
    border: 1px solid color-mix(in srgb, var(--er-section-accent) 22%, transparent);
    border-radius: 14px;
    background: color-mix(in srgb, var(--er-section-accent) 10%, #FFFFFF);
    color: color-mix(in srgb, var(--er-section-accent) 76%, var(--er-ink)) !important;
}

.edit-room-page .edit-room-card > div:first-child h2::after {
    content: "";
    display: inline-block;
    width: 42px;
    height: 2px;
    margin-left: 14px;
    border-radius: 999px;
    background: var(--er-section-accent);
    opacity: .42;
    vertical-align: middle;
}

.edit-room-page .edit-room-card > div:last-child,
.edit-room-page .edit-room-status-card,
.edit-room-page .edit-room-preview-card,
.edit-room-page .edit-room-active-card {
    background: rgba(255,255,255,.94) !important;
}

.edit-room-page :where(input[type="text"], input[type="number"], textarea, select) {
    border: 1px solid rgba(28, 38, 53, .14) !important;
    background: #FFFFFF !important;
    color: var(--er-ink) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.9) inset;
}

.edit-room-page :where(input[type="text"], input[type="number"], textarea, select):focus {
    border-color: color-mix(in srgb, var(--er-section-accent, var(--er-sky)) 62%, var(--er-brand)) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--er-section-accent, var(--er-sky)) 16%, transparent) !important;
    outline: none;
}

.edit-room-page label,
.edit-room-page h3,
.edit-room-page h4 {
    color: var(--er-ink) !important;
}

.edit-room-error {
    display: block;
    margin-top: 0.45rem;
    color: #b42318;
    font-size: 0.78rem;
    font-weight: 850;
}

.edit-room-page .text-gray-500,
.edit-room-page .text-gray-600,
.edit-room-page .text-gray-700 {
    color: var(--er-muted) !important;
}

.edit-room-page .edit-room-card .border-2,
.edit-room-page .edit-room-preview-card .border-2 {
    border-width: 1px !important;
    border-color: rgba(28, 38, 53, .1) !important;
    background: #FFFFFF;
}

.edit-room-page input[type="radio"]:checked + div {
    border-color: color-mix(in srgb, var(--er-section-accent, var(--er-sky)) 34%, transparent) !important;
    background: color-mix(in srgb, var(--er-section-accent, var(--er-sky)) 10%, #FFFFFF) !important;
    color: color-mix(in srgb, var(--er-section-accent, var(--er-sky)) 78%, var(--er-ink)) !important;
}

.edit-room-page input[type="checkbox"] {
    accent-color: var(--er-section-accent, var(--er-sun));
}

.edit-room-page .edit-room-status-card {
    color: var(--er-ink) !important;
}

.edit-room-page .edit-room-status-card > div,
.edit-room-page .edit-room-status-card .bg-white\/10,
.edit-room-page .edit-room-status-card .bg-blue-500\/20 {
    border: 1px solid rgba(28, 38, 53, .1);
    background: #FFFFFF !important;
    color: var(--er-ink) !important;
    backdrop-filter: none;
}

.edit-room-page .edit-room-preview-inner {
    border-color: rgba(28, 38, 53, .1) !important;
    background: linear-gradient(135deg, #FFFFFF, rgba(247,249,248,.94)) !important;
}

.edit-room-page #preview-numero,
.edit-room-page #preview-precio {
    color: color-mix(in srgb, var(--er-clay) 74%, var(--er-ink)) !important;
}

.edit-room-page .edit-room-active-card {
    border-color: rgba(28, 38, 53, .12) !important;
}

.edit-room-page .edit-room-actions button[type="submit"] {
    background: linear-gradient(145deg, color-mix(in srgb, var(--er-brand) 82%, #263247), #263247) !important;
    box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--er-brand) 52%, transparent);
}

.edit-room-page .edit-room-actions button.is-confirming {
    background: linear-gradient(145deg, #b91c1c, #7f1d1d) !important;
    border-color: rgba(185, 28, 28, .34) !important;
    color: #FFFFFF !important;
}

.edit-room-delete-warning {
    display: grid;
    gap: 0.9rem;
    padding: 1rem;
    border: 1px solid rgba(185, 28, 28, .24);
    border-radius: 1.15rem;
    background: linear-gradient(135deg, rgba(254, 242, 242, .96), rgba(255, 247, 237, .92));
    color: #7f1d1d;
}

.edit-room-delete-warning__head {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.edit-room-delete-warning__icon {
    width: 2.35rem;
    height: 2.35rem;
    flex: 0 0 auto;
    display: inline-grid;
    place-items: center;
    border-radius: 0.9rem;
    background: #991b1b;
    color: #FFFFFF;
}

.edit-room-delete-warning strong {
    display: block;
    color: #7f1d1d;
    font-size: 0.95rem;
    font-weight: 950;
    line-height: 1.2;
}

.edit-room-delete-warning p {
    margin: 0.24rem 0 0;
    color: #991b1b !important;
    font-size: 0.82rem;
    font-weight: 780;
    line-height: 1.45;
}

.edit-room-delete-list {
    display: grid;
    gap: 0.65rem;
}

.edit-room-delete-reservation {
    display: grid;
    gap: 0.6rem;
    padding: 0.75rem;
    border: 1px solid rgba(185, 28, 28, .16);
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, .76);
}

.edit-room-delete-reservation__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
    color: #7f1d1d;
    font-size: 0.76rem;
    font-weight: 850;
}

.edit-room-delete-reservation__meta span {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.edit-room-delete-reservation a {
    min-height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.45rem;
    border: 1px solid rgba(185, 28, 28, .25);
    border-radius: 0.75rem;
    background: #FFFFFF;
    color: #991b1b;
    font-size: 0.78rem;
    font-weight: 930;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.edit-room-delete-reservation a:hover {
    transform: translateY(-1px);
    border-color: rgba(153, 27, 27, .45);
    background: rgba(254, 242, 242, .95);
}

.edit-room-delete-disabled {
    opacity: .78;
    cursor: not-allowed;
}

.edit-room-upload-alert {
    display: none;
    align-items: flex-start;
    gap: 0.65rem;
    margin: 1rem 0 0;
    padding: 0.85rem 0.95rem;
    border: 1px solid rgba(196, 69, 54, 0.24);
    border-radius: 1rem;
    background: rgba(254, 242, 242, 0.92);
    color: #991b1b;
    font-size: 0.86rem;
    font-weight: 800;
    line-height: 1.4;
}

.edit-room-upload-alert.is-visible {
    display: flex;
}

.edit-room-upload-alert i {
    margin-top: 0.12rem;
    color: #b91c1c;
}

.edit-room-page .edit-room-actions a,
.edit-room-page .edit-room-actions button {
    transform: none !important;
}

.edit-room-page .edit-room-actions a:hover,
.edit-room-page .edit-room-actions button:hover {
    transform: translateY(-1px) !important;
}

.edit-room-page .edit-room-form-grid {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(305px, 360px) !important;
    gap: 20px !important;
    align-items: start;
}

.edit-room-page .edit-room-main,
.edit-room-page .edit-room-side {
    grid-column: auto !important;
    min-width: 0;
}

.edit-room-page .edit-room-side {
    position: sticky;
    top: 18px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.edit-room-page .bg-purple-50,
.edit-room-page .bg-yellow-50,
.edit-room-page .bg-gray-50,
.edit-room-page .bg-gray-100 {
    background: linear-gradient(135deg, #FFFFFF, rgba(247,249,248,.94)) !important;
}

.edit-room-page .text-purple-600,
.edit-room-page .text-purple-700,
.edit-room-page .text-purple-800 {
    color: color-mix(in srgb, var(--er-clay) 70%, var(--er-ink)) !important;
}

@media (max-width: 780px) {
    .edit-room-page {
        padding: 18px 12px !important;
    }

    .edit-room-page h1 {
        font-size: clamp(2.6rem, 16vw, 4rem) !important;
    }

    .edit-room-page .edit-room-card > div:first-child h2::after {
        display: none;
    }

    .edit-room-page .edit-room-form-grid {
        grid-template-columns: 1fr !important;
    }

    .edit-room-page .edit-room-side {
        position: static;
    }
}
</style>

<div class="edit-room-page min-h-screen bg-gradient-to-br from-hotel-cream to-white p-6">
    <!-- Header elegante -->
    <div class="max-w-7xl mx-auto mb-8">
        <div class="edit-room-breadcrumb flex items-center text-sm text-gray-600 mb-4">
            <a href="<?= url('habitaciones') ?>" class="hover:text-hotel-brown">
                <i class="fas fa-bed mr-1"></i>Habitaciones
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="hover:text-hotel-brown">
                Habitación <?= htmlspecialchars($habitacion['numero']) ?>
            </a>
            <i class="fas fa-chevron-right mx-2 text-xs"></i>
            <span>Editar</span>
        </div>

        <div class="edit-room-hero-card bg-white rounded-2xl shadow-xl p-8 border-l-8 border-hotel-gold">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-hotel-brown font-playfair mb-2">
                        <span class="edit-room-title-kicker">Editar habitación</span>
                        <?= htmlspecialchars($habitacion['numero']) ?>
                    </h1>
                    <p class="text-gray-600">
                        <?php
                        echo htmlspecialchars((string) ($habitacionTipoLabel ?? 'Tipo especial'), ENT_QUOTES, 'UTF-8');
                        ?>
                    </p>
                </div>
                <div class="hidden lg:block">
                    <div class="relative">
                        <div class="edit-room-hero-icon bg-hotel-cream p-6 rounded-full">
                            <i class="fas fa-edit text-5xl text-hotel-brown"></i>
                        </div>
                        <!-- Estado actual badge -->
                        <?php
                        $estados = Habitacion::getEstados();
                        $estadoActual = $estados[$habitacion['estado']] ?? ['label' => 'Desconocido', 'color' => 'gray'];
                        $badgeColors = [
                            'disponible' => 'bg-green-500',
                            'ocupada' => 'bg-red-500',
                            'mantenimiento' => 'bg-yellow-500',
                            'limpieza' => 'bg-blue-500'
                        ];
                        ?>
                        <div class="absolute -bottom-2 -right-2 <?= $badgeColors[$habitacion['estado']] ?? 'bg-gray-500' ?> text-white px-3 py-1 rounded-full text-sm font-medium shadow-lg">
                            <?= $estadoActual['label'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulario con diseño moderno -->
    <div class="max-w-7xl mx-auto">
        <form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/update') ?>"
              enctype="multipart/form-data" class="space-y-8">
            <?= csrf_field() ?>

            <div class="edit-room-form-grid grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Columna principal (2/3) -->
                <div class="edit-room-main lg:col-span-2 space-y-6">
                    <!-- Card de Información Básica -->
                    <div class="edit-room-card edit-section-basic bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-hotel-brown to-hotel-brown-dark p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-info-circle mr-3"></i>
                                Información Básica
                            </h2>
                        </div>

                        <div class="p-6 space-y-6">
                            <!-- Número y Tipo en grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Número de habitación -->
                                <div class="relative">
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Número de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <i class="fas fa-hashtag text-gray-400"></i>
                                        </div>
                                        <input type="text"
                                               name="numero"
                                               value="<?= htmlspecialchars($habitacionNumeroFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                               required
                                               class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                    </div>
                                    <?php if (form_error('numero')): ?>
                                        <span class="edit-room-error"><?= form_error('numero') ?></span>
                                    <?php endif; ?>

                                </div>

                                <!-- Tipo de habitación - ACTUALIZADO -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Tipo de Habitación <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="tipo"
                                                required
                                                class="w-full pl-4 pr-10 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all appearance-none">
                                            <?php foreach ($tiposHabitacion as $key => $tipo): ?>
                                                <option value="<?= $key ?>" <?= $habitacionTipoFormulario == $key ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars((string) $tipo, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                    <?php if (form_error('tipo')): ?>
                                        <span class="edit-room-error"><?= form_error('tipo') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Piso y Precio en grid - ACTUALIZADO PARA SÓTANOS -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Piso con selección ACTUALIZADA para incluir sótanos -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Ubicación - Piso <span class="text-red-500">*</span>
                                    </label>
                                    <div class="space-y-3">
                                        <?php
                                        $pisosAbajo = [];
                                        $pisosArriba = [];
                                        foreach ($pisosHabitacion as $pisoCatalogoNumero => $pisoCatalogoLabel) {
                                            if ((int) $pisoCatalogoNumero < 0) {
                                                $pisosAbajo[(int) $pisoCatalogoNumero] = $pisoCatalogoLabel;
                                            } else {
                                                $pisosArriba[(int) $pisoCatalogoNumero] = $pisoCatalogoLabel;
                                            }
                                        }
                                        ?>
                                        <!-- Sótanos -->
                                        <div class="border-2 border-gray-200 rounded-xl p-4">
                                            <p class="text-xs font-medium text-gray-600 mb-2">Niveles abajo</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php
                                                $sotanos = $pisosAbajo;
                                                foreach ($sotanos as $piso_num => $label):
                                                ?>
                                                <label class="relative">
                                                    <input type="radio"
                                                           name="piso"
                                                           value="<?= $piso_num ?>"
                                                           <?= $habitacionPisoFormulario == $piso_num ? 'checked' : '' ?>
                                                           required
                                                           class="sr-only peer">
                                                    <div class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer transition-all peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white hover:border-gray-300">
                                                        <i class="fas fa-arrow-down text-lg mb-1"></i>
                                                        <span class="text-xs font-medium"><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></span>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <!-- Pisos superiores -->
                                        <div class="border-2 border-gray-200 rounded-xl p-4">
                                            <p class="text-xs font-medium text-gray-600 mb-2">Pisos Superiores</p>
                                            <div class="grid grid-cols-3 gap-2">
                                                <?php
                                                $pisos_sup = $pisosArriba;
                                                foreach ($pisos_sup as $piso_num => $label):
                                                ?>
                                                <label class="relative">
                                                    <input type="radio"
                                                           name="piso"
                                                           value="<?= $piso_num ?>"
                                                           <?= $habitacionPisoFormulario == $piso_num ? 'checked' : '' ?>
                                                           required
                                                           class="sr-only peer">
                                                    <div class="flex flex-col items-center justify-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer transition-all peer-checked:border-hotel-brown peer-checked:bg-hotel-brown peer-checked:text-white hover:border-gray-300">
                                                        <i class="fas fa-building text-lg mb-1"></i>
                                                        <span class="text-xs font-medium"><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></span>
                                                    </div>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (form_error('piso')): ?>
                                        <span class="edit-room-error"><?= form_error('piso') ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Precio con rangos por tipo -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Precio por Noche <span class="text-red-500">*</span>
                                    </label>
                                    <?php
                                    // Mostrar rango sugerido según el tipo actual
                                    $rangos = $rangosHabitacion;
                                    $rango_actual = $rangos[$habitacionTipoFormulario] ?? ['min' => 500, 'max' => 1600];
                                    ?>
                                    <div class="mb-2">
                                        <span class="text-xs text-gray-500" id="rango-precio">
                                            Rango sugerido para <?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?>:
                                            $<?= number_format($rango_actual['min']) ?> - $<?= number_format($rango_actual['max']) ?>
                                        </span>
                                    </div>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                                            <span class="text-xl font-bold text-hotel-gold">$</span>
                                        </div>
                                        <input type="number"
                                               name="precio_base"
                                               value="<?= htmlspecialchars($habitacionPrecioFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                               step="50"
                                               required
                                               class="w-full pl-10 pr-4 py-3 text-xl font-semibold border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                                            <span class="text-sm text-gray-500">MXN</span>
                                        </div>
                                    </div>
                                    <?php if (form_error('precio_base')): ?>
                                        <span class="edit-room-error"><?= form_error('precio_base') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Capacidad de Personas <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="capacidad_personas"
                                           value="<?= htmlspecialchars($habitacionCapacidadFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           min="1"
                                           max="30"
                                           step="1"
                                           required
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                    <?php if (form_error('capacidad_personas')): ?>
                                        <span class="edit-room-error"><?= form_error('capacidad_personas') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Camas Matrimoniales <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number"
                                           name="camas_matrimoniales"
                                           value="<?= htmlspecialchars($habitacionCamasMatrimonialesFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           min="0"
                                           max="20"
                                           step="1"
                                           required
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                    <?php if (form_error('camas_matrimoniales')): ?>
                                        <span class="edit-room-error"><?= form_error('camas_matrimoniales') ?></span>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        Camas Individuales
                                    </label>
                                    <input type="number"
                                           name="camas_individuales"
                                           value="<?= htmlspecialchars($habitacionCamasIndividualesFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           min="0"
                                           max="20"
                                           step="1"
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-hotel-brown/20 focus:border-hotel-brown transition-all">
                                    <?php if (form_error('camas_individuales')): ?>
                                        <span class="edit-room-error"><?= form_error('camas_individuales') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Características - ACTUALIZADO -->
                    <div class="edit-room-card edit-section-features bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-list-check mr-3"></i>
                                Características de la habitación
                            </h2>
                        </div>

                        <div class="p-6">
                            <!-- Características estándar del hotel -->
                            <div class="mb-6">
                                <p class="text-sm font-medium text-gray-700 mb-3">Características incluidas en todas las habitaciones:</p>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <?php
                                    $caracteristicas_base = [
                                        ['icon' => 'wifi', 'label' => 'Wi-Fi', 'color' => 'blue'],
                                        ['icon' => 'tv', 'label' => 'Cablevisión', 'color' => 'purple'],
                                        ['icon' => 'bath', 'label' => 'Baño Privado', 'color' => 'green'],
                                        ['icon' => 'car', 'label' => 'Estacionamiento', 'color' => 'gray'],
                                        ['icon' => 'shower', 'label' => 'Agua Caliente', 'color' => 'red'],
                                        ['icon' => 'fan', 'label' => 'Ventilador', 'color' => 'cyan']
                                    ];

                                    foreach ($caracteristicas_base as $caract):
                                    ?>
                                    <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                                        <i class="fas fa-<?= $caract['icon'] ?> mr-2 text-<?= $caract['color'] ?>-600"></i>
                                        <span class="text-xs font-medium"><?= $caract['label'] ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Características especiales con checkboxes -->
                            <div class="mb-6">
                                <p class="text-sm font-medium text-gray-700 mb-3">Características especiales:</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <?php
                                    $amenidadIconos = [
                                        'pantalla' => ['icon' => 'tv', 'color' => 'purple'],
                                        'balcon' => ['icon' => 'home', 'color' => 'green'],
                                        'jacuzzi' => ['icon' => 'bath', 'color' => 'blue', 'disabled_for' => ['doble_jacuzzi', 'sencilla_jacuzzi']],
                                        'amplia' => ['icon' => 'expand-arrows-alt', 'color' => 'yellow']
                                    ];
                                    $caracteristicas_especiales = [];
                                    foreach ($amenidadesHabitacion as $amenidadKey => $amenidadLabel) {
                                        $meta = $amenidadIconos[$amenidadKey] ?? ['icon' => 'check-circle', 'color' => 'blue'];
                                        $meta['label'] = $amenidadLabel;
                                        $caracteristicas_especiales[$amenidadKey] = $meta;
                                    }

                                    // Parsear características existentes
                                    $caracteristicas_actuales = strtolower($habitacionCaracteristicasFormulario);
                                    $caracteristicas_actuales = strtr($caracteristicas_actuales, [
                                        'á' => 'a',
                                        'é' => 'e',
                                        'í' => 'i',
                                        'ó' => 'o',
                                        'ú' => 'u',
                                        'ñ' => 'n',
                                    ]);

                                    foreach ($caracteristicas_especiales as $key => $especial):
                                        $keywords = [
                                            'pantalla' => 'pantalla',
                                            'balcon' => 'balcon',
                                            'jacuzzi' => 'jacuzzi',
                                            'amplia' => 'amplia'
                                        ];
                                        $keyword = $keywords[$key] ?? strtolower((string) $especial['label']);
                                        $isChecked = $habitacionEspecialesFormulario !== null
                                            ? in_array((string)$key, $habitacionEspecialesFormulario, true)
                                            : strpos($caracteristicas_actuales, $keyword) !== false;
                                        $isDisabled = isset($especial['disabled_for']) && in_array($habitacionTipoFormulario, $especial['disabled_for'], true);
                                        $isChecked = $isDisabled || $isChecked;
                                    ?>
                                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-<?= $especial['color'] ?>-300 hover:bg-<?= $especial['color'] ?>-50 transition-all group <?= $isDisabled ? 'opacity-50 cursor-not-allowed' : '' ?>">
                                        <input type="checkbox"
                                               name="caracteristicas_especiales[]"
                                               value="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>"
                                               <?= $isChecked ? 'checked' : '' ?>
                                               <?= $isDisabled ? 'disabled' : '' ?>
                                               class="mr-3 w-4 h-4 text-<?= $especial['color'] ?>-600 focus:ring-<?= $especial['color'] ?>-500 rounded">
                                        <i class="fas fa-<?= $especial['icon'] ?> mr-2 text-gray-600 group-hover:text-<?= $especial['color'] ?>-600"></i>
                                        <span class="text-sm font-medium"><?= htmlspecialchars((string) $especial['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php if ($isDisabled): ?>
                                            <span class="ml-2 text-xs text-gray-500">(incluido en el tipo)</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Descripción completa (solo lectura mejorada) -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Descripción completa de características
                                </label>
                                <textarea name="caracteristicas"
                                          rows="4"
                                          placeholder="Ejemplo: 2 camas matrimoniales, pantalla, balcón, baño, ventilador, agua caliente, Wifi, Cablevisión, estacionamiento"
                                          class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"><?= htmlspecialchars($habitacionCaracteristicasFormulario, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    Esta descripción se genera automáticamente en base al tipo y características seleccionadas, pero puede personalizarse.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card de Imágenes MÚLTIPLES -->
                    <div class="edit-room-card edit-section-photos bg-white rounded-2xl shadow-lg overflow-hidden transform hover:shadow-xl transition-shadow duration-300">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6">
                            <h2 class="text-xl font-semibold text-white flex items-center">
                                <i class="fas fa-images mr-3"></i>
                                Fotografías de la Habitación
                            </h2>
                        </div>

                        <div class="p-6">
                            <?php
                            // Obtener imágenes existentes
                            $habitacionImagenModel = new HabitacionImagen();
                            $imagenes_existentes = $habitacionImagenModel->porHabitacion($habitacion['id']);
                            $total_imagenes = count($imagenes_existentes);
                            ?>

                            <!-- Imágenes actuales -->
                            <!-- Encabezado de imágenes con enlace de gestión siempre visible -->
<div class="flex justify-between items-center mb-4">
    <h4 class="text-sm font-semibold text-gray-700">
        Imágenes de la habitación (<?= $total_imagenes ?>/10)
    </h4>
    <a href="<?= url('habitaciones/' . $habitacion['id'] . '/imagenes') ?>"
       class="text-sm text-purple-600 hover:underline">
        <i class="fas fa-cog mr-1"></i>Gestionar imágenes
    </a>
</div>

<!-- Imágenes actuales -->
<?php if ($total_imagenes > 0): ?>
<div class="mb-6">

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <?php foreach (array_slice($imagenes_existentes, 0, 4) as $index => $imagen): ?>
                                    <div class="relative group">
                                        <img src="<?= image_url($imagen['url']) ?>"
                                             alt="Imagen <?= $index + 1 ?>"
                                             class="w-full h-24 object-cover rounded-lg shadow">
                                        <?php if ($imagen['es_principal']): ?>
                                        <div class="absolute top-1 left-1 bg-green-500 text-white px-2 py-0.5 rounded text-xs">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>

                                    <?php if ($total_imagenes > 4): ?>
                                    <div class="flex items-center justify-center bg-gray-100 rounded-lg h-24">
                                        <span class="text-gray-600 text-sm">+<?= $total_imagenes - 4 ?> más</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Upload de nuevas imágenes -->
                            <?php if ($total_imagenes < 10): ?>
                                <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-purple-400 transition-all" id="drop-zone">
                                    <input type="file"
                                           name="fotos[]"
                                           accept="image/*"
                                           id="fotos-input"
                                           multiple
                                           class="hidden">
                                    <label for="fotos-input" class="cursor-pointer">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                                        <p class="text-gray-600 font-medium mb-1">Click para agregar nuevas imagenes</p>
                                        <p class="text-sm text-gray-500">JPG, PNG, GIF o WebP - Maximo 5MB por imagen</p>
                                        <p class="text-xs text-blue-600 mt-2">O arrastra y suelta las imagenes aqui</p>
                                    </label>
                                </div>


                                <!-- Vista previa de nuevas imágenes -->
                                <div id="upload-alert" class="edit-room-upload-alert" role="alert" aria-live="assertive" hidden>
                                    <i class="fas fa-circle-exclamation"></i>
                                    <span></span>
                                </div>
                                <?php if (form_error('fotos[]')): ?>
                                    <span class="edit-room-error"><?= form_error('fotos[]') ?></span>
                                <?php endif; ?>

                                <div id="preview-container" class="mt-4 hidden">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Nuevas imágenes a agregar:</h5>
                                    <div id="preview-grid" class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                        <!-- Las previsualizaciones se agregarán aquí dinámicamente -->
                                    </div>
                                </div>
                            <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <p class="text-yellow-800 text-sm">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Has alcanzado el límite máximo de 10 imágenes. Para agregar nuevas, primero debes eliminar algunas existentes.
                                </p>
                            </div>
                            <?php endif; ?>

                            <!-- Información -->
                            <div class="mt-4 bg-purple-50 rounded-lg p-4">
                                <h4 class="text-sm font-semibold text-purple-800 mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Información sobre las imágenes
                                </h4>
                                <ul class="text-xs text-purple-700 space-y-1">
                                    <li>• Máximo 10 imágenes por habitación</li>
                                    <li>• La imagen marcada con <i class="fas fa-star"></i> es la principal</li>
                                    <li>• Puedes gestionar el orden y eliminar imágenes en la sección de gestión</li>
                                    <li>• Las nuevas imágenes se agregarán al final de la galería</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                <!-- Columna lateral (1/3) -->
                <div class="edit-room-side space-y-6">
                    <!-- Card de Estado Actual con información del hotel -->
                    <div class="edit-room-status-card bg-gradient-to-br from-gray-800 to-gray-900 rounded-2xl shadow-xl p-6 text-white">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Información de la Habitación
                        </h3>

                        <div class="bg-white/10 backdrop-blur rounded-xl p-4 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm opacity-80">Estado:</span>
                                <span class="font-bold text-lg flex items-center">
                                    <i class="fas fa-<?= $estadoActual['icon'] ?? 'question' ?> mr-2"></i>
                                    <?= $estadoActual['label'] ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm opacity-80">Ubicación:</span>
                                <span class="font-medium">
                                    <?= htmlspecialchars((string) ($pisosHabitacion[(int) $habitacion['piso']] ?? ('Piso ' . (int) $habitacion['piso'])), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-80">Capacidad:</span>
                                <span class="font-medium">
                                    <?= $habitacion['capacidad_personas'] ?> personas
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-sm opacity-80">Camas:</span>
                                <span class="font-medium">
                                    <?php $totalCamasEdicion = (int)($habitacion['camas_matrimoniales'] ?? 0) + (int)($habitacion['camas_individuales'] ?? 0); ?>
                                    <?= $totalCamasEdicion > 0 ? $totalCamasEdicion . ' en total' : 'No definidas' ?>
                                </span>
                            </div>
                        </div>

                        <div class="bg-blue-500/20 rounded-lg p-3">
                            <p class="text-xs flex items-start">
                                <i class="fas fa-info-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                Esta habitación pertenece al hotel actual.
                            </p>
                        </div>
                    </div>

                    <!-- Preview en tiempo real MEJORADO -->
                    <div class="edit-room-preview-card bg-white rounded-2xl shadow-xl p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-eye mr-2 text-hotel-brown"></i>
                            Vista Previa
                        </h3>

                        <div class="edit-room-preview-inner bg-gradient-to-br from-hotel-cream to-white rounded-xl p-4 border-2 border-hotel-brown/20">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-2xl font-bold text-hotel-brown" id="preview-numero">
                                        <?= htmlspecialchars($habitacion['numero']) ?>
                                    </p>
                                    <p class="text-sm text-gray-600" id="preview-tipo">
                                        <?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">Ubicación</p>
                                    <p class="text-lg font-bold text-gray-800" id="preview-piso">
                                        <?= htmlspecialchars((string) ($pisosHabitacion[(int) $habitacion['piso']] ?? ('Piso ' . (int) $habitacion['piso'])), ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </div>
                            <div class="border-t pt-3">
                                <p class="text-xs text-gray-500">Precio por noche</p>
                                <p class="text-2xl font-bold text-hotel-gold" id="preview-precio">
                                    $<?= number_format($habitacion['precio_base'], 0) ?>
                                </p>
                            </div>

                            <!-- Características especiales en el preview -->
                            <div class="mt-3 pt-3 border-t" id="preview-caracteristicas">
                                <p class="text-xs text-gray-500 mb-1">Características especiales:</p>
                                <div class="flex flex-wrap gap-1" id="preview-badges">
                                    <!-- Se llenarán con JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estado Activo/Inactivo -->
                    <div class="edit-room-active-card bg-yellow-50 rounded-2xl p-6 border-2 border-yellow-200">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox"
                                   name="activa"
                                   value="1"
                                   <?= $habitacionActivaFormulario ? 'checked' : '' ?>
                                   class="mt-1 mr-3 w-5 h-5 text-yellow-600 focus:ring-yellow-500 rounded">
                            <div>
                                <span class="font-semibold text-gray-800">Habitación Activa</span>
                                <p class="text-sm text-gray-600 mt-1">
                                    Desmarque para ocultar la habitación del sistema de reservas
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Botones de acción -->
                    <div class="edit-room-actions space-y-3">
                        <button type="submit"
                                class="w-full bg-gradient-to-r from-hotel-brown to-hotel-brown-dark text-white font-semibold py-4 px-6 rounded-xl hover:shadow-xl transform hover:scale-105 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-save mr-3"></i>
                            Guardar Cambios
                        </button>

                        <a href="<?= back_url('habitaciones/' . $habitacion['id']) ?>"
                           class="w-full bg-white border-2 border-gray-300 text-gray-700 font-semibold py-4 px-6 rounded-xl hover:bg-gray-50 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-times mr-3"></i>
                            Cancelar
                        </a>

                        <?php if (can('habitaciones.delete') && $hayReservacionesBloqueantesEliminacion): ?>
                            <div id="delete-room-blocker" class="edit-room-delete-warning" role="alert" aria-live="polite">
                                <div class="edit-room-delete-warning__head">
                                    <span class="edit-room-delete-warning__icon" aria-hidden="true">
                                        <i class="fas fa-ban"></i>
                                    </span>
                                    <div>
                                        <strong>No se puede eliminar esta habitaci&oacute;n todav&iacute;a.</strong>
                                        <p>
                                            La habitaci&oacute;n <?= htmlspecialchars($habitacionNumeroEliminacion, ENT_QUOTES, 'UTF-8') ?>
                                            tiene <?= count($reservacionesBloqueantesEliminacion) ?> reservaci&oacute;n(es) activa(s) o futura(s).
                                            Antes de eliminarla, cancela esas reservaciones o cambia la habitaci&oacute;n asignada desde cada detalle.
                                        </p>
                                    </div>
                                </div>

                                <div class="edit-room-delete-list">
                                    <?php foreach ($reservacionesBloqueantesEliminacion as $reservacionBloqueante): ?>
                                        <?php
                                        $reservacionBloqueanteId = (int)($reservacionBloqueante['id'] ?? 0);
                                        $reservacionBloqueanteEstado = (string)($reservacionBloqueante['estado'] ?? '');
                                        $reservacionBloqueanteEstadoLabel = $estadosReservacionEliminacion[$reservacionBloqueanteEstado] ?? ucfirst(str_replace('_', ' ', $reservacionBloqueanteEstado));
                                        $reservacionBloqueanteEntrada = !empty($reservacionBloqueante['fecha_entrada']) ? date('d/m/Y', strtotime((string)$reservacionBloqueante['fecha_entrada'])) : 'Sin entrada';
                                        $reservacionBloqueanteSalida = !empty($reservacionBloqueante['fecha_salida']) ? date('d/m/Y', strtotime((string)$reservacionBloqueante['fecha_salida'])) : 'Sin salida';
                                        ?>
                                        <div class="edit-room-delete-reservation">
                                            <div>
                                                <strong>Reservaci&oacute;n #<?= $reservacionBloqueanteId ?></strong>
                                                <p><?= htmlspecialchars((string)($reservacionBloqueante['nombre_completo'] ?? 'Huesped sin nombre'), ENT_QUOTES, 'UTF-8') ?></p>
                                            </div>
                                            <div class="edit-room-delete-reservation__meta">
                                                <span><i class="fas fa-circle" aria-hidden="true"></i><?= htmlspecialchars($reservacionBloqueanteEstadoLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span><i class="fas fa-calendar-alt" aria-hidden="true"></i><?= htmlspecialchars($reservacionBloqueanteEntrada . ' - ' . $reservacionBloqueanteSalida, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if (!empty($reservacionBloqueante['telefono'])): ?>
                                                    <span><i class="fas fa-phone" aria-hidden="true"></i><?= htmlspecialchars((string)$reservacionBloqueante['telefono'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <a href="<?= url('reservaciones/ver/' . $reservacionBloqueanteId) ?>">
                                                <i class="fas fa-external-link-alt" aria-hidden="true"></i>
                                                Ver reservaci&oacute;n y resolver bloqueo
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button type="button"
                                    onclick="confirmarEliminacion(this)"
                                    class="edit-room-delete-disabled w-full bg-red-50 border-2 border-red-200 text-red-700 font-semibold py-4 px-6 rounded-xl transition-all duration-200 flex items-center justify-center"
                                    aria-describedby="delete-room-blocker">
                                <i class="fas fa-lock mr-3"></i>
                                Eliminaci&oacute;n bloqueada por reservaciones
                            </button>
                        <?php endif; ?>

                        <?php if (can('habitaciones.delete') && !$hayReservacionesBloqueantesEliminacion && $habitacion['estado'] == 'disponible'): ?>
                        <button type="button"
                                onclick="confirmarEliminacion(this)"
                                class="w-full bg-red-50 border-2 border-red-200 text-red-600 font-semibold py-4 px-6 rounded-xl hover:bg-red-100 transition-all duration-200 flex items-center justify-center">
                            <i class="fas fa-trash-alt mr-3"></i>
                            Eliminar Habitación
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Script de la vista hotelera -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tiposInfo = <?= json_encode($tiposHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pisosInfo = <?= json_encode($pisosHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const rangosPrecio = <?= json_encode($rangosHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
    const amenidadesInfo = <?= json_encode($amenidadesHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // Preview en tiempo real
    const numeroInput = document.querySelector('input[name="numero"]');
    const tipoSelect = document.querySelector('select[name="tipo"]');
    const pisoInputs = document.querySelectorAll('input[name="piso"]');
    const precioInput = document.querySelector('input[name="precio_base"]');
    const caracteristicasCheckboxes = document.querySelectorAll('input[name="caracteristicas_especiales[]"]');

    // Elementos del preview
    const previewNumero = document.getElementById('preview-numero');
    const previewTipo = document.getElementById('preview-tipo');
    const previewPiso = document.getElementById('preview-piso');
    const previewPrecio = document.getElementById('preview-precio');
    const previewBadges = document.getElementById('preview-badges');

    // Actualizar preview
    function updatePreview() {
        // Número
        previewNumero.textContent = numeroInput.value || '---';

        // Tipo
        const tipoSeleccionado = tipoSelect.value;
        previewTipo.textContent = tiposInfo[tipoSeleccionado] || 'Sin tipo';

        // Piso
        const pisoSeleccionado = document.querySelector('input[name="piso"]:checked');
        if (pisoSeleccionado) {
            previewPiso.textContent = pisosInfo[pisoSeleccionado.value] || pisoSeleccionado.value;
        } else {
            previewPiso.textContent = 'Sin definir';
        }

        // Precio
        const precio = parseFloat(precioInput.value) || 0;
        previewPrecio.textContent = '$' + precio.toLocaleString('es-MX');

        // Características especiales
        updateCaracteristicasBadges();

        // Actualizar rango de precio sugerido
        updateRangoPrecio(tipoSeleccionado);

        // Manejar checkboxes especiales para tipos con jacuzzi
        manejarJacuzziCheckbox(tipoSeleccionado);
    }

    // Actualizar badges de características
    function updateCaracteristicasBadges() {
        const caracteristicasSeleccionadas = [];
        caracteristicasCheckboxes.forEach(checkbox => {
            if (checkbox.checked && !checkbox.disabled) {
                caracteristicasSeleccionadas.push(amenidadesInfo[checkbox.value] || checkbox.value);
            }
        });

        // Agregar jacuzzi automáticamente si es tipo con jacuzzi
        const tipoSeleccionado = tipoSelect.value;
        if ((tipoSeleccionado === 'doble_jacuzzi' || tipoSeleccionado === 'sencilla_jacuzzi') && !caracteristicasSeleccionadas.includes('Jacuzzi')) {
            caracteristicasSeleccionadas.unshift('Jacuzzi');
        }

        previewBadges.innerHTML = '';
        if (caracteristicasSeleccionadas.length > 0) {
            caracteristicasSeleccionadas.forEach(caract => {
                const badge = document.createElement('span');
                badge.className = 'px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium';
                badge.textContent = caract;
                previewBadges.appendChild(badge);
            });
        } else {
            previewBadges.innerHTML = '<span class="text-xs text-gray-500">Ninguna especial</span>';
        }
    }

    // Actualizar rango de precio sugerido
    function updateRangoPrecio(tipo) {
        const rango = rangosPrecio[tipo];
        if (rango) {
            // Buscar el elemento de rango sugerido y actualizarlo
            const rangoTexto = document.getElementById('rango-precio');
            if (rangoTexto) {
                rangoTexto.textContent = `Rango sugerido para ${tiposInfo[tipo]}: $${rango.min.toLocaleString()} - $${rango.max.toLocaleString()}`;
            }
        }
    }

    // Manejar checkbox de jacuzzi para tipos con jacuzzi incluido
    function manejarJacuzziCheckbox(tipo) {
        const jacuzziCheckbox = document.querySelector('input[name="caracteristicas_especiales[]"][value="jacuzzi"]');
        if (jacuzziCheckbox) {
            const label = jacuzziCheckbox.closest('label');
            if (tipo === 'doble_jacuzzi' || tipo === 'sencilla_jacuzzi') {
                jacuzziCheckbox.disabled = true;
                jacuzziCheckbox.checked = true;
                label.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                jacuzziCheckbox.disabled = false;
                label.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    }

    // Event listeners
    numeroInput.addEventListener('input', updatePreview);
    tipoSelect.addEventListener('change', updatePreview);
    pisoInputs.forEach(input => input.addEventListener('change', updatePreview));
    precioInput.addEventListener('input', updatePreview);
    caracteristicasCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updatePreview));

    // Formatear precio al perder foco
    precioInput.addEventListener('blur', function() {
        if (this.value) {
            const valor = parseFloat(this.value);
            // Redondear a múltiplos de 50
            const valorRedondeado = Math.round(valor / 50) * 50;
            this.value = valorRedondeado;
            updatePreview();
        }
    });

    // Inicializar preview
    updatePreview();
});

const deleteRoomBlocked = <?= $hayReservacionesBloqueantesEliminacion ? 'true' : 'false' ?>;
const deleteRoomNumber = <?= json_encode($habitacionNumeroEliminacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
let deleteRoomPending = false;
let deleteRoomTimer = null;

function showRoomActionNotice(message) {
    let notice = document.querySelector('[data-room-action-notice]');
    if (!notice) {
        notice = document.createElement('div');
        notice.setAttribute('data-room-action-notice', '1');
        notice.setAttribute('role', 'status');
        notice.style.position = 'fixed';
        notice.style.right = '24px';
        notice.style.bottom = '24px';
        notice.style.zIndex = '9999';
        notice.style.maxWidth = '360px';
        notice.style.padding = '12px 14px';
        notice.style.border = '1px solid rgba(185, 28, 28, .24)';
        notice.style.borderRadius = '14px';
        notice.style.background = 'rgba(254, 242, 242, .98)';
        notice.style.color = '#991b1b';
        notice.style.boxShadow = '0 16px 36px rgba(60, 20, 20, .16)';
        notice.style.fontSize = '14px';
        notice.style.fontWeight = '800';
        document.body.appendChild(notice);
    }

    notice.textContent = message;
    notice.style.display = 'block';
    window.clearTimeout(notice._hideTimer);
    notice._hideTimer = window.setTimeout(() => {
        notice.style.display = 'none';
    }, 4200);
}

function resetDeleteRoomConfirmation(trigger) {
    deleteRoomPending = false;
    if (trigger) {
        trigger.classList.remove('is-confirming');
        trigger.innerHTML = trigger.dataset.defaultHtml || trigger.innerHTML;
    }
}

// Confirmar eliminación
function confirmarEliminacion(trigger) {
    if (deleteRoomBlocked) {
        const blocker = document.getElementById('delete-room-blocker');
        if (blocker) {
            blocker.scrollIntoView({ behavior: 'smooth', block: 'center' });
            blocker.style.boxShadow = '0 0 0 4px rgba(185, 28, 28, .14)';
            window.setTimeout(() => {
                blocker.style.boxShadow = '';
            }, 1800);
        }
        showRoomActionNotice('No se puede eliminar esta habitacion hasta resolver sus reservaciones vinculadas.');
        return;
    }

    const submitDeleteRoom = function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('habitaciones/' . $habitacion['id'] . '/delete') ?>';

        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = 'csrf_token';
        csrfField.value = '<?= csrf_token() ?>';
        form.appendChild(csrfField);

        document.body.appendChild(form);
        form.submit();
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Eliminar habitacion ' + (deleteRoomNumber || ''),
            html: 'Esta accion quitara la habitacion del listado operativo y de nuevas reservas. El historial asociado se conservara para auditoria.',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar habitacion',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#991b1b',
            cancelButtonColor: '#6b7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                submitDeleteRoom();
            }
        });
        return;
    }

    if (window.confirm('Eliminar habitacion ' + (deleteRoomNumber || '') + '? Se quitara del listado operativo y se conservara su historial.')) {
        submitDeleteRoom();
    }
}

// Script para manejo de múltiples imágenes en edición
document.addEventListener('DOMContentLoaded', function() {
    const fotosInput = document.getElementById('fotos-input');
    const previewContainer = document.getElementById('preview-container');
    const previewGrid = document.getElementById('preview-grid');
    const dropZone = document.getElementById('drop-zone');
    const uploadAlert = document.getElementById('upload-alert');

    if (!fotosInput) return;

    let selectedFiles = [];
    const maxFiles = <?= 10 - $total_imagenes ?>;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    // Manejar selección de archivos
    fotosInput.addEventListener('change', function(e) {
        handleFiles(e.target.files);
    });

    // Drag and drop
    if (dropZone) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('border-purple-400', 'bg-purple-50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('border-purple-400', 'bg-purple-50');
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }, false);
    }

    function handleFiles(files) {
        const newFiles = Array.from(files);
        clearUploadError();

        // Validar cantidad
        if (newFiles.length > maxFiles) {
            showError(`Solo puedes agregar ${maxFiles} imagen${maxFiles > 1 ? 'es' : ''} mas.`);
            return;
        }

        // Validar cada archivo
        selectedFiles = [];
        for (let file of newFiles) {
            if (!allowedTypes.includes(file.type)) {
                showError(`${file.name} no es una imagen válida`);
                continue;
            }

            if (file.size > maxSize) {
                showError(`${file.name} excede el tamaño máximo de 5MB`);
                continue;
            }

            selectedFiles.push(file);
        }

        updatePreview();
        updateFileInput();
    }

    function updatePreview() {
        if (!previewGrid || !previewContainer) return;

        previewGrid.innerHTML = '';

        if (selectedFiles.length === 0) {
            previewContainer.classList.add('hidden');
            return;
        }

        previewContainer.classList.remove('hidden');

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}"
                         alt="${file.name}"
                         class="w-full h-24 object-cover rounded-lg shadow">
                    <button type="button"
                            onclick="removeNewImage(${index})"
                            class="absolute top-1 right-1 bg-red-500 text-white p-1 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                    <p class="text-xs text-gray-600 mt-1 truncate">${file.name}</p>
                `;
                previewGrid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    window.removeNewImage = function(index) {
        selectedFiles.splice(index, 1);
        updatePreview();
        updateFileInput();
    };

    function updateFileInput() {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach(file => {
            dataTransfer.items.add(file);
        });
        fotosInput.files = dataTransfer.files;
    }

    function showError(message) {
        if (!uploadAlert) {
            return;
        }

        const text = uploadAlert.querySelector('span');
        if (text) {
            text.textContent = message;
        }

        uploadAlert.hidden = false;
        uploadAlert.classList.add('is-visible');
        uploadAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function clearUploadError() {
        if (!uploadAlert) {
            return;
        }

        uploadAlert.hidden = true;
        uploadAlert.classList.remove('is-visible');
    }
});
</script>
