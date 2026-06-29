<?php
// ── Datos del formulario ─────────────────────────────────────────
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
    : ['pantalla' => 'Pantalla', 'balcon' => 'Balcón', 'jacuzzi' => 'Jacuzzi', 'amplia' => 'Más amplia'];

$habitacionCaracteristicasPlain = strtolower((string)($habitacion['caracteristicas'] ?? ''));
$habitacionCaracteristicasAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $habitacionCaracteristicasPlain);
if ($habitacionCaracteristicasAscii !== false) {
    $habitacionCaracteristicasPlain = $habitacionCaracteristicasAscii;
}
$habitacionCaracteristicasLimpias = trim((string)($habitacion['caracteristicas'] ?? ''));
$habitacionTipoCatalogoMarker = null;
if (preg_match('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*([^\[\r\n,;]+?)\s*\[([a-z0-9_\-]+)\]/i', (string)($habitacion['caracteristicas'] ?? ''), $habitacionTipoCatalogoMatch)) {
    $habitacionTipoCatalogoCodigo = strtolower(trim((string)($habitacionTipoCatalogoMatch[2] ?? '')));
    $habitacionTipoCatalogoLabel  = trim((string)($habitacionTipoCatalogoMatch[1] ?? ''));
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

$habitacionOldInput       = is_array($_SESSION['old_input'] ?? null) ? $_SESSION['old_input'] : [];
$habitacionTieneOldInput  = !empty($habitacionOldInput);
$habitacionCampo = static function (string $campo, $valorActual) use ($habitacionOldInput, $habitacionTieneOldInput) {
    return $habitacionTieneOldInput && array_key_exists($campo, $habitacionOldInput)
        ? $habitacionOldInput[$campo]
        : $valorActual;
};

$habitacionTipoFormulario            = (string)$habitacionCampo('tipo', $habitacionTipoSeleccionado);
$habitacionNumeroFormulario          = (string)$habitacionCampo('numero', $habitacion['numero'] ?? '');
$habitacionPisoFormulario            = (int)$habitacionCampo('piso', $habitacion['piso'] ?? 0);
$habitacionPrecioFormulario          = (string)$habitacionCampo('precio_base', $habitacion['precio_base'] ?? '');
$habitacionCapacidadFormulario       = (string)$habitacionCampo('capacidad_personas', $habitacion['capacidad_personas'] ?? 2);
$habitacionCamasMatrimonialesForm    = (string)$habitacionCampo('camas_matrimoniales', $habitacion['camas_matrimoniales'] ?? 1);
$habitacionCamasIndividualesForm     = (string)$habitacionCampo('camas_individuales', $habitacion['camas_individuales'] ?? 0);
$habitacionCaracteristicasFormulario = (string)$habitacionCampo('caracteristicas', $habitacionCaracteristicasLimpias);
$habitacionActivaFormulario          = $habitacionTieneOldInput ? !empty($habitacionOldInput['activa']) : !empty($habitacion['activa']);
$habitacionEspecialesFormulario      = null;
if ($habitacionTieneOldInput) {
    $habitacionEspecialesFormulario = $habitacionOldInput['caracteristicas_especiales'] ?? [];
    if (!is_array($habitacionEspecialesFormulario)) {
        $habitacionEspecialesFormulario = [$habitacionEspecialesFormulario];
    }
    $habitacionEspecialesFormulario = array_map('strval', $habitacionEspecialesFormulario);
}

$habitacionTipoLabel = $tiposHabitacion[$habitacionTipoFormulario]
    ?? ($tiposHabitacion[$habitacion['tipo']] ?? get_tipo_habitacion($habitacion['tipo']));

$rangosHabitacion = Habitacion::getRangoPrecios();
if (function_exists('hotel_room_catalog_type_rows')) {
    foreach (hotel_room_catalog_type_rows(null, true) as $catalogRow) {
        $catalogCode  = (string)($catalogRow['codigo'] ?? '');
        $catalogPrice = (float)($catalogRow['precio_base_default'] ?? 0);
        if ($catalogCode !== '' && $catalogPrice > 0 && !isset($rangosHabitacion[$catalogCode])) {
            $rangosHabitacion[$catalogCode] = ['min' => $catalogPrice, 'max' => $catalogPrice];
        }
    }
}

$reservacionesBloqueantes     = is_array($reservaciones_bloqueantes_eliminacion ?? null) ? $reservaciones_bloqueantes_eliminacion : [];
$hayReservacionesBloqueantes  = count($reservacionesBloqueantes) > 0;
$estadosResEliminacion        = ['confirmada' => 'Confirmada', 'checked_in' => 'Check-in'];
$habitacionNumeroEliminacion  = (string)($habitacion['numero'] ?? '');

$_ehEstados    = Habitacion::getEstados();
$_ehEstado     = $_ehEstados[$habitacion['estado']] ?? ['label' => 'Sin estado', 'color' => 'gray', 'icon' => 'circle'];
$_ehEstadoIcon = [
    'disponible'   => 'fa-check-circle',
    'ocupada'      => 'fa-user-lock',
    'mantenimiento'=> 'fa-tools',
    'limpieza'     => 'fa-broom',
][($habitacion['estado'] ?? '')] ?? 'fa-circle';

$pisosAbajo  = [];
$pisosArriba = [];
foreach ($pisosHabitacion as $pn => $pl) {
    if ((int)$pn < 0) { $pisosAbajo[(int)$pn]  = $pl; }
    else               { $pisosArriba[(int)$pn] = $pl; }
}

$amenidadMeta = [
    'pantalla' => ['icon' => 'fa-tv',                 'label' => 'Pantalla'],
    'balcon'   => ['icon' => 'fa-home',               'label' => 'Balcón'],
    'jacuzzi'  => ['icon' => 'fa-bath',               'label' => 'Jacuzzi', 'disabled_for' => ['doble_jacuzzi','sencilla_jacuzzi']],
    'amplia'   => ['icon' => 'fa-expand-arrows-alt',  'label' => 'Más amplia'],
];

$habitacionImagenModel    = new HabitacionImagen();
$imagenesExistentes       = $habitacionImagenModel->porHabitacion($habitacion['id']);
$totalImagenes            = count($imagenesExistentes);
$rangoActual              = $rangosHabitacion[$habitacionTipoFormulario] ?? ['min' => 500, 'max' => 3000];
?>
<style id="edit-hab-gc">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.edit-hab-page {
    --gc-brand:         var(--brand-primary, #1B2746);
    --gc-brand-2:       var(--brand-secondary, #0F172A);
    --gc-accent:        var(--brand-accent, #BD9441);
    --gc-accent-dark:   color-mix(in srgb, var(--gc-accent) 72%, #3F2E12);
    --gc-accent-soft:   color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF);
    --gc-accent-line:   color-mix(in srgb, var(--gc-accent) 32%, #E8DDCA);
    --gc-ivory:         color-mix(in srgb, var(--gc-accent) 8%, #F8F5ED);
    --gc-ivory-2:       color-mix(in srgb, var(--gc-accent) 5%, #FCFAF5);
    --gc-surface:       color-mix(in srgb, var(--gc-accent) 2%, #FFFFFF);
    --gc-surface-warm:  color-mix(in srgb, var(--gc-accent) 5%, #FFFFFF);
    --gc-line:          color-mix(in srgb, var(--gc-accent) 20%, #E7DEC9);
    --gc-line-soft:     color-mix(in srgb, var(--gc-accent) 11%, #F0ECE2);
    --gc-muted:         color-mix(in srgb, var(--gc-brand-2) 48%, #94A3B8);
    --gc-text:          var(--gc-brand-2);
    --gc-success:       #1E9E63;
    --gc-success-bg:    #E7F4EC;
    --gc-info:          #2F77E0;
    --gc-info-bg:       #E6EFFC;
    --gc-danger:        #D64539;
    --gc-danger-bg:     #FBE9E7;
    --gc-serif:         'Cormorant Garamond', Georgia, serif;
    --gc-sans:          'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--gc-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--gc-ivory-2), var(--gc-ivory) 58%, #F7F2EA);
    color: var(--gc-text);
    font-family: var(--gc-sans);
    -webkit-font-smoothing: antialiased;
}
.edit-hab-page *, .edit-hab-page *::before, .edit-hab-page *::after { box-sizing: border-box; }
.edit-hab-page :where(p, span, a, button, input, select, textarea, label) { font-family: var(--gc-sans); }

.gc-wrap  { width: min(100%, 1280px); margin: 0 auto; padding: 26px 18px 34px; }

/* Breadcrumb */
.gc-breadcrumb { display: inline-flex; align-items: center; gap: 9px; color: var(--gc-muted); font-size: .78rem; font-weight: 700; margin-bottom: 18px; }
.gc-breadcrumb a { color: #111827; display: inline-flex; align-items: center; gap: 7px; transition: color .18s ease, transform .18s ease; }
.gc-breadcrumb a:hover { color: var(--gc-accent-dark); transform: translateY(-1px); }

/* Hero */
.gc-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 18px; }
.gc-hero-main { display: flex; align-items: flex-start; gap: 14px; min-width: 0; flex: 1 1 auto; }
.gc-hero-icon { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; flex: 0 0 auto; background: linear-gradient(150deg, var(--gc-brand), var(--gc-brand-2)); color: #fff; box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--gc-brand) 58%, transparent); }
.gc-kicker { margin: 0 0 4px; color: var(--gc-accent-dark); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
.gc-title { margin: 0; color: #111827; font-family: var(--gc-serif); font-size: clamp(2rem, 3.6vw, 2.75rem); font-weight: 650; letter-spacing: 0; line-height: .96; text-wrap: balance; }
.gc-title-accent { color: var(--gc-brand); }
.gc-subtitle { max-width: 62ch; margin: 8px 0 0; color: var(--gc-muted); font-size: .88rem; font-weight: 600; line-height: 1.55; }
.gc-flow-pill { display: inline-flex; align-items: center; gap: 8px; flex: 0 0 auto; min-height: 38px; padding: 8px 12px; border: 1px solid var(--gc-accent-line); border-radius: 999px; background: var(--gc-accent-soft); color: var(--gc-accent-dark); font-size: .76rem; font-weight: 800; white-space: nowrap; }

/* Layout */
.gc-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(286px, 318px); gap: 18px; align-items: start; }
.gc-form { display: flex; flex-direction: column; gap: 15px; min-width: 0; }

/* Sections */
.gc-section, .gc-side-card, .gc-actions {
    border: 1px solid var(--gc-line); border-radius: 18px;
    background: var(--gc-surface);
    box-shadow: 0 1px 2px color-mix(in srgb, var(--gc-brand-2) 4%, transparent), 0 14px 32px -24px color-mix(in srgb, var(--gc-brand-2) 34%, transparent);
    min-width: 0;
}
.gc-section { overflow: hidden; }
.gc-section-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; min-height: 58px; padding: 14px 17px; border-bottom: 1px solid var(--gc-line); background: var(--gc-surface-warm); min-width: 0; }
.gc-section-title-wrap { display: flex; align-items: center; gap: 10px; min-width: 0; }
.gc-section-icon { width: 34px; height: 34px; border-radius: 11px; border: 1px solid var(--gc-line); background: color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF); color: var(--gc-accent-dark); display: grid; place-items: center; flex: 0 0 auto; }
.gc-section h2 { margin: 0; color: #111827; font-size: .92rem; font-weight: 850; letter-spacing: 0; text-wrap: balance; }
.gc-section-sub { margin: 2px 0 0; color: var(--gc-muted); font-size: .72rem; font-weight: 600; }
.gc-section-body { padding: 17px; }

/* Grid & fields */
.gc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; min-width: 0; }
.gc-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.gc-field { min-width: 0; }
.gc-field-full { grid-column: 1 / -1; }
.gc-label { display: flex; align-items: center; gap: 6px; margin-bottom: 7px; color: var(--gc-muted); font-size: .72rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }
.gc-required { color: var(--gc-danger); }
.gc-form-error { display: block; margin-top: 7px; color: var(--gc-danger); font-size: .76rem; font-weight: 850; line-height: 1.35; }
.gc-field-hint { margin: 7px 0 0; color: var(--gc-muted); font-size: .76rem; font-weight: 650; line-height: 1.45; }
.gc-input-wrap { position: relative; }
.gc-input-wrap i, .gc-input-wrap span.prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: color-mix(in srgb, var(--gc-brand) 42%, var(--gc-muted)); font-size: .78rem; pointer-events: none; }
.gc-input-wrap span.prefix { font-size: .9rem; font-weight: 700; }
.gc-control { width: 100%; min-height: 42px; border: 1px solid var(--gc-line); border-radius: 12px; background: var(--gc-surface-warm); color: var(--gc-text); font-size: .88rem; font-weight: 650; padding: 10px 12px; outline: none; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; min-width: 0; }
.gc-control.has-icon { padding-left: 36px; }
.gc-control.has-prefix { padding-left: 26px; }
.gc-control::placeholder { color: color-mix(in srgb, var(--gc-muted) 72%, #CBD5E1); font-weight: 500; }
.gc-control:focus { border-color: var(--gc-accent); background: #FFFFFF; box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 22%, transparent); }

/* Note */
.gc-note { margin: 12px 0 0; padding: 11px 12px; border: 1px solid var(--gc-line); border-radius: 13px; background: var(--gc-ivory-2); color: var(--gc-muted); font-size: .78rem; font-weight: 600; line-height: 1.5; }
.gc-note i { color: var(--gc-accent-dark); margin-right: 6px; }

/* Radio cards (piso) */
.gc-radio-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(96px, 1fr)); gap: 9px; }
.gc-radio-card { min-height: 68px; display: grid; place-items: center; gap: 4px; border: 1px solid var(--gc-line); border-radius: 13px; background: var(--gc-surface); color: #111827; text-align: center; font-size: .78rem; font-weight: 800; transition: border-color .18s ease, background .18s ease, color .18s ease, box-shadow .18s ease; overflow: hidden; cursor: pointer; padding: 8px 6px; }
.gc-radio-card i { font-size: 1rem; }
.eh-peer { display: none; }
.eh-peer:checked ~ .gc-radio-card { border-color: var(--gc-brand); background: var(--gc-brand); color: #FFFFFF; box-shadow: 0 10px 22px -14px color-mix(in srgb, var(--gc-brand) 68%, transparent); }
.eh-peer:focus ~ .gc-radio-card { box-shadow: 0 0 0 3px color-mix(in srgb, var(--gc-accent) 24%, transparent); }

/* Checkbox amenidades */
.gc-check-item { display: flex; align-items: center; gap: 11px; padding: 11px 13px; border: 1px solid var(--gc-line); border-radius: 13px; background: var(--gc-surface-warm); cursor: pointer; transition: border-color .18s ease, background .18s ease; }
.gc-check-item:hover { border-color: var(--gc-accent-line); background: var(--gc-accent-soft); }
.gc-check-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--gc-brand); flex-shrink: 0; cursor: pointer; }
.gc-check-item.is-disabled { opacity: .52; cursor: not-allowed; }
.gc-check-icon { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: color-mix(in srgb, var(--gc-accent) 12%, #fff); color: var(--gc-accent-dark); font-size: .78rem; flex-shrink: 0; border: 1px solid var(--gc-line); }
.gc-check-name { font-size: .84rem; font-weight: 750; color: #111827; flex: 1; }
.gc-check-badge { font-size: .68rem; color: var(--gc-muted); font-weight: 700; }

/* Photo section */
.gc-photo-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.gc-photo-thumb { position: relative; border-radius: 12px; overflow: hidden; aspect-ratio: 4/3; }
.gc-photo-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.gc-photo-badge { position: absolute; top: 6px; left: 6px; background: var(--gc-success); color: #fff; border-radius: 999px; padding: 2px 8px; font-size: .68rem; font-weight: 800; display: inline-flex; align-items: center; gap: 4px; }
.gc-photo-more { display: flex; align-items: center; justify-content: center; border: 1px dashed var(--gc-line); border-radius: 12px; aspect-ratio: 4/3; color: var(--gc-muted); font-size: .82rem; font-weight: 700; background: var(--gc-ivory-2); }
.gc-upload-zone { border: 2px dashed var(--gc-line); border-radius: 16px; padding: 28px 20px; text-align: center; cursor: pointer; transition: border-color .18s ease, background .18s ease; }
.gc-upload-zone:hover, .gc-upload-zone.drag-over { border-color: var(--gc-accent-line); background: var(--gc-accent-soft); }
.gc-upload-zone i { font-size: 2rem; color: var(--gc-muted); display: block; margin-bottom: 10px; }
.gc-upload-zone p { color: var(--gc-muted); font-size: .84rem; font-weight: 700; margin: 0 0 4px; }
.gc-upload-zone small { color: color-mix(in srgb, var(--gc-muted) 72%, #CBD5E1); font-size: .76rem; font-weight: 600; }
.gc-upload-alert { display: none; align-items: flex-start; gap: 10px; margin: 12px 0 0; padding: 10px 13px; border: 1px solid color-mix(in srgb, var(--gc-danger) 24%, transparent); border-radius: 12px; background: var(--gc-danger-bg); color: var(--gc-danger); font-size: .82rem; font-weight: 800; }
.gc-upload-alert.is-visible { display: flex; }
.gc-upload-preview { margin-top: 14px; }
.gc-upload-preview h5 { font-size: .78rem; font-weight: 800; color: #111827; margin: 0 0 9px; }
.gc-upload-preview-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }

/* Sidebar */
.gc-side { position: sticky; top: 18px; display: flex; flex-direction: column; gap: 13px; min-width: 0; }
.gc-side-card { padding: 16px; }
.gc-side-card h3 { margin: 0 0 11px; display: flex; align-items: center; gap: 9px; color: #111827; font-size: .92rem; font-weight: 850; }
.gc-side-icon { width: 34px; height: 34px; border-radius: 11px; border: 1px solid var(--gc-line); background: color-mix(in srgb, var(--gc-accent) 13%, #FFFFFF); color: var(--gc-accent-dark); display: grid; place-items: center; flex: 0 0 auto; }

/* Preview card */
.gc-preview-inner { border: 1px solid var(--gc-accent-line); border-radius: 14px; padding: 14px; background: linear-gradient(135deg, var(--gc-ivory-2), var(--gc-surface-warm)); }
.gc-preview-room { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
.gc-preview-num { font-family: var(--gc-serif); font-size: 2.2rem; font-weight: 700; color: var(--gc-brand); line-height: 1; }
.gc-preview-tipo { font-size: .8rem; font-weight: 700; color: var(--gc-muted); margin-top: 3px; }
.gc-preview-piso-label { font-size: .66rem; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: var(--gc-muted); }
.gc-preview-piso-val { font-size: 1rem; font-weight: 800; color: #111827; }
.gc-preview-divider { height: 1px; background: var(--gc-line); margin: 10px 0; }
.gc-preview-precio-label { font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--gc-muted); }
.gc-preview-precio-val { font-family: var(--gc-serif); font-size: 1.6rem; font-weight: 700; color: var(--gc-accent-dark); }
.gc-preview-badges { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 10px; min-height: 22px; }
.gc-preview-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 999px; background: color-mix(in srgb, var(--gc-brand) 10%, #FFFFFF); border: 1px solid color-mix(in srgb, var(--gc-brand) 18%, transparent); color: var(--gc-brand); font-size: .68rem; font-weight: 800; }

/* Toggle activa */
.gc-toggle-row { display: flex; align-items: flex-start; gap: 12px; padding: 13px; border: 1px solid var(--gc-line-soft); border-radius: 14px; background: var(--gc-surface-warm); cursor: pointer; }
.gc-toggle-row input[type="checkbox"] { width: 18px; height: 18px; accent-color: var(--gc-brand); flex-shrink: 0; margin-top: 1px; cursor: pointer; }
.gc-toggle-row-copy strong { display: block; font-size: .86rem; font-weight: 850; color: #111827; }
.gc-toggle-row-copy p { margin: 3px 0 0; font-size: .76rem; color: var(--gc-muted); font-weight: 650; }

/* Resumen info */
.gc-info-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--gc-line-soft); font-size: .82rem; }
.gc-info-row:last-child { border-bottom: none; }
.gc-info-row span:first-child { color: var(--gc-muted); font-weight: 650; }
.gc-info-row span:last-child  { font-weight: 800; color: #111827; }

/* Actions */
.gc-actions { display: flex; justify-content: flex-end; gap: 10px; padding: 14px; align-items: center; margin-top: 2px; }
.gc-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 12px; padding: 10px 15px; font-size: .86rem; font-weight: 850; transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease; white-space: nowrap; border: none; cursor: pointer; }
.gc-btn:hover { transform: translateY(-1px); }
.gc-btn:active { transform: translateY(0) scale(.99); }
.gc-btn-secondary { border: 1px solid var(--gc-line); background: var(--gc-surface-warm); color: var(--gc-muted); }
.gc-btn-secondary:hover { border-color: var(--gc-accent-line); color: var(--gc-accent-dark); background: var(--gc-accent-soft); }
.gc-btn-primary { border: 1px solid color-mix(in srgb, var(--gc-accent) 32%, transparent); background: linear-gradient(135deg, var(--gc-brand), var(--gc-brand-2)); color: #FFFFFF; box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--gc-brand) 60%, transparent); }
.gc-btn-primary:hover { color: #FFFFFF; box-shadow: 0 16px 30px -14px color-mix(in srgb, var(--gc-brand) 66%, transparent); }
.gc-btn-danger { border: 1px solid color-mix(in srgb, var(--gc-danger) 26%, transparent); background: var(--gc-danger-bg); color: var(--gc-danger); }
.gc-btn-danger:hover { background: color-mix(in srgb, var(--gc-danger) 14%, #FFFFFF); }

/* Delete zone */
.gc-danger-zone { margin-top: 18px; border: 1px solid color-mix(in srgb, var(--gc-danger) 24%, #F3D7D4); border-radius: 18px; overflow: hidden; background: #FFFFFF; }
.gc-danger-head { display: flex; align-items: center; gap: 12px; padding: 14px 17px; border-bottom: 1px solid color-mix(in srgb, var(--gc-danger) 16%, #F3D7D4); background: var(--gc-danger-bg); }
.gc-danger-icon { width: 34px; height: 34px; border-radius: 11px; background: var(--gc-danger); color: #fff; display: grid; place-items: center; flex-shrink: 0; }
.gc-danger-head h2 { margin: 0; font-size: .92rem; font-weight: 850; color: #7f1d1d; }
.gc-danger-body { padding: 17px; display: flex; flex-direction: column; gap: 12px; }
.gc-blocker-card { border: 1px solid color-mix(in srgb, var(--gc-danger) 18%, #F3D7D4); border-radius: 14px; padding: 13px 14px; background: rgba(255,255,255,.82); }
.gc-blocker-card + .gc-blocker-card { margin-top: 8px; }
.gc-blocker-card strong { display: block; color: #7f1d1d; font-size: .86rem; font-weight: 850; }
.gc-blocker-card p { margin: 3px 0 0; color: #991b1b; font-size: .78rem; font-weight: 700; }
.gc-blocker-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
.gc-blocker-meta span { display: inline-flex; align-items: center; gap: 4px; font-size: .72rem; font-weight: 800; color: #7f1d1d; }
.gc-blocker-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 9px; min-height: 36px; padding: 0 12px; border: 1px solid color-mix(in srgb, var(--gc-danger) 26%, #F3D7D4); border-radius: 10px; background: #FFFFFF; color: #991b1b; font-size: .76rem; font-weight: 850; text-decoration: none; transition: transform .16s, border-color .16s, background .16s; }
.gc-blocker-link:hover { transform: translateY(-1px); background: var(--gc-danger-bg); }

/* Scrollbar */
.edit-hab-page ::-webkit-scrollbar { width: 6px; height: 6px; }
.edit-hab-page ::-webkit-scrollbar-track { background: var(--gc-ivory); }
.edit-hab-page ::-webkit-scrollbar-thumb { background: color-mix(in srgb, var(--gc-accent), #fff 34%); border-radius: 999px; }

/* ── Responsive ── */
@media (min-width: 1440px) {
    .gc-wrap { width: min(100%, 1360px); }
    .gc-layout { grid-template-columns: minmax(0, 1fr) 330px; gap: 22px; }
}
@media (max-width: 1180px) {
    .gc-layout { grid-template-columns: minmax(0, 1fr) 284px; gap: 16px; }
    .gc-photo-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 1024px) {
    .gc-layout { grid-template-columns: 1fr; }
    .gc-side { position: static; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 860px) {
    .gc-hero { flex-direction: column; align-items: stretch; }
    .gc-flow-pill { align-self: flex-start; }
    .gc-side { grid-template-columns: 1fr; }
    .gc-grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 700px) {
    .gc-wrap { padding: 10px 10px 16px; }
    .gc-breadcrumb { margin-bottom: 14px; font-size: .66rem; gap: 5px; white-space: nowrap; overflow-x: auto; scrollbar-width: none; }
    .gc-breadcrumb::-webkit-scrollbar { display: none; }
    .gc-hero { gap: 12px; margin-bottom: 18px; }
    .gc-hero-icon { width: 38px; height: 38px; border-radius: 12px; font-size: .88rem; flex-basis: 38px; }
    .gc-kicker { font-size: .57rem; }
    .gc-title { font-size: 1.38rem; }
    .gc-subtitle { font-size: .68rem; }
    .gc-layout, .gc-form { gap: 9px; }
    .gc-section { border-radius: 15px; }
    .gc-section-head { padding: 9px 10px; min-height: 0; }
    .gc-section-icon { width: 29px; height: 29px; border-radius: 10px; flex-basis: 29px; }
    .gc-section h2 { font-size: .86rem; }
    .gc-section-sub, .gc-field-hint, .gc-note, .gc-side { display: none; }
    .gc-section-body { padding: 10px; }
    .gc-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
    .gc-grid-3 { grid-template-columns: 1fr; }
    .gc-label { margin-bottom: 4px; font-size: .58rem; }
    .gc-control { min-height: 44px; border-radius: 11px; padding: 7px 10px; font-size: 16px; font-weight: 650; }
    .gc-control.has-icon { padding-left: 30px; }
    .gc-control.has-prefix { padding-left: 22px; }
    .gc-photo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gc-upload-preview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gc-actions { grid-template-columns: minmax(0,.78fr) minmax(0,1.22fr); display: grid; padding: 9px; border-radius: 15px; margin-top: 0; }
    .gc-btn { width: 100%; min-height: 44px; border-radius: 12px; padding: 0 10px; font-size: .72rem; }
}
@media (max-width: 380px) {
    .gc-grid, .gc-actions { grid-template-columns: 1fr; }
    .gc-actions .gc-btn-primary { order: -1; }
}
@media (prefers-reduced-motion: reduce) {
    .edit-hab-page *, .edit-hab-page *::before, .edit-hab-page *::after { transition: none !important; animation: none !important; }
}
</style>

<div class="edit-hab-page">
<div class="gc-wrap">

    <!-- Breadcrumb -->
    <nav class="gc-breadcrumb">
        <a href="<?= url('habitaciones') ?>"><i class="fas fa-bed"></i> Habitaciones</a>
        <i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.5"></i>
        <a href="<?= url('habitaciones/' . $habitacion['id']) ?>">Hab. <?= htmlspecialchars($habitacion['numero'], ENT_QUOTES, 'UTF-8') ?></a>
        <i class="fas fa-chevron-right" style="font-size:.6rem;opacity:.5"></i>
        <span>Editar</span>
    </nav>

    <!-- Hero -->
    <header class="gc-hero">
        <div class="gc-hero-main">
            <div class="gc-hero-icon"><i class="fas fa-door-open"></i></div>
            <div>
                <p class="gc-kicker">Editar habitación</p>
                <h1 class="gc-title">Habitación <span class="gc-title-accent"><?= htmlspecialchars($habitacionNumeroFormulario, ENT_QUOTES, 'UTF-8') ?></span></h1>
                <p class="gc-subtitle">
                    <?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?> &middot;
                    <?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario] ?? ('Piso ' . $habitacionPisoFormulario)), ENT_QUOTES, 'UTF-8') ?>
                    — actualiza los datos para mantener el inventario al día.
                </p>
            </div>
        </div>
        <span class="gc-flow-pill">
            <i class="fas <?= $_ehEstadoIcon ?>"></i>
            <?= htmlspecialchars((string)($_ehEstado['label'] ?? 'Sin estado'), ENT_QUOTES, 'UTF-8') ?>
        </span>
    </header>

    <!-- Formulario -->
    <form method="POST"
          action="<?= url('habitaciones/' . $habitacion['id'] . '/update') ?>"
          enctype="multipart/form-data"
          id="formEditHab">
        <?= csrf_field() ?>

        <div class="gc-layout">

            <!-- ── Main ── -->
            <main class="gc-form">

                <!-- Sección: Identificación y precio -->
                <section class="gc-section">
                    <div class="gc-section-head">
                        <div class="gc-section-title-wrap">
                            <span class="gc-section-icon"><i class="fas fa-hashtag"></i></span>
                            <div>
                                <h2>Identificación y precio</h2>
                                <p class="gc-section-sub">Número, tipo de habitación y tarifa por noche.</p>
                            </div>
                        </div>
                    </div>
                    <div class="gc-section-body">
                        <div class="gc-grid">
                            <!-- Número -->
                            <div class="gc-field">
                                <label class="gc-label">Número <span class="gc-required">*</span></label>
                                <div class="gc-input-wrap">
                                    <i class="fas fa-hashtag"></i>
                                    <input type="text"
                                           name="numero"
                                           id="input-numero"
                                           value="<?= htmlspecialchars($habitacionNumeroFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           required
                                           placeholder="Ej. 101"
                                           class="gc-control has-icon">
                                </div>
                                <?php if (form_error('numero')): ?>
                                    <span class="gc-form-error"><?= form_error('numero') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Tipo -->
                            <div class="gc-field">
                                <label class="gc-label">Tipo <span class="gc-required">*</span></label>
                                <div class="gc-input-wrap">
                                    <i class="fas fa-tag"></i>
                                    <select name="tipo" id="input-tipo" required class="gc-control has-icon">
                                        <?php foreach ($tiposHabitacion as $key => $label): ?>
                                            <option value="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= $habitacionTipoFormulario == $key ? 'selected' : '' ?>>
                                                <?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php if (form_error('tipo')): ?>
                                    <span class="gc-form-error"><?= form_error('tipo') ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Precio -->
                            <div class="gc-field gc-field-full">
                                <label class="gc-label">
                                    Precio por noche <span class="gc-required">*</span>
                                    <span id="rango-precio" style="font-weight:600;text-transform:none;letter-spacing:0;color:var(--gc-muted)">
                                        — Rango sugerido: $<?= number_format($rangoActual['min']) ?> - $<?= number_format($rangoActual['max']) ?>
                                    </span>
                                </label>
                                <div class="gc-input-wrap">
                                    <span class="prefix">$</span>
                                    <input type="number"
                                           name="precio_base"
                                           id="input-precio"
                                           data-money-format="true"
                                           value="<?= htmlspecialchars($habitacionPrecioFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           step="50"
                                           required
                                           placeholder="0.00"
                                           class="gc-control has-prefix"
                                           style="font-size:1.1rem;font-weight:800">
                                </div>
                                <?php if (form_error('precio_base')): ?>
                                    <span class="gc-form-error"><?= form_error('precio_base') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Sección: Capacidad -->
                <section class="gc-section">
                    <div class="gc-section-head">
                        <div class="gc-section-title-wrap">
                            <span class="gc-section-icon"><i class="fas fa-users"></i></span>
                            <div>
                                <h2>Capacidad y camas</h2>
                                <p class="gc-section-sub">Personas que puede alojar y configuración de camas.</p>
                            </div>
                        </div>
                    </div>
                    <div class="gc-section-body">
                        <div class="gc-grid gc-grid-3">
                            <div class="gc-field">
                                <label class="gc-label">Personas <span class="gc-required">*</span></label>
                                <div class="gc-input-wrap">
                                    <i class="fas fa-user"></i>
                                    <input type="number"
                                           name="capacidad_personas"
                                           id="input-capacidad"
                                           value="<?= htmlspecialchars($habitacionCapacidadFormulario, ENT_QUOTES, 'UTF-8') ?>"
                                           min="1" max="30" step="1" required
                                           class="gc-control has-icon">
                                </div>
                                <?php if (form_error('capacidad_personas')): ?>
                                    <span class="gc-form-error"><?= form_error('capacidad_personas') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="gc-field">
                                <label class="gc-label">Camas matrimoniales <span class="gc-required">*</span></label>
                                <div class="gc-input-wrap">
                                    <i class="fas fa-bed"></i>
                                    <input type="number"
                                           name="camas_matrimoniales"
                                           value="<?= htmlspecialchars($habitacionCamasMatrimonialesForm, ENT_QUOTES, 'UTF-8') ?>"
                                           min="0" max="20" step="1" required
                                           class="gc-control has-icon">
                                </div>
                                <?php if (form_error('camas_matrimoniales')): ?>
                                    <span class="gc-form-error"><?= form_error('camas_matrimoniales') ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="gc-field">
                                <label class="gc-label">Camas individuales</label>
                                <div class="gc-input-wrap">
                                    <i class="fas fa-bed"></i>
                                    <input type="number"
                                           name="camas_individuales"
                                           value="<?= htmlspecialchars($habitacionCamasIndividualesForm, ENT_QUOTES, 'UTF-8') ?>"
                                           min="0" max="20" step="1"
                                           class="gc-control has-icon">
                                </div>
                                <?php if (form_error('camas_individuales')): ?>
                                    <span class="gc-form-error"><?= form_error('camas_individuales') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Sección: Piso -->
                <section class="gc-section">
                    <div class="gc-section-head">
                        <div class="gc-section-title-wrap">
                            <span class="gc-section-icon"><i class="fas fa-building"></i></span>
                            <div>
                                <h2>Ubicación — Piso</h2>
                                <p class="gc-section-sub">Selecciona el nivel donde se encuentra la habitación.</p>
                            </div>
                        </div>
                    </div>
                    <div class="gc-section-body">
                        <?php if (!empty($pisosAbajo)): ?>
                        <div style="margin-bottom:14px;">
                            <p class="gc-label" style="margin-bottom:9px;"><i class="fas fa-arrow-down"></i> Niveles inferiores</p>
                            <div class="gc-radio-grid">
                                <?php foreach ($pisosAbajo as $pn => $pl): ?>
                                <label>
                                    <input type="radio" name="piso" value="<?= $pn ?>" class="eh-peer"
                                           <?= $habitacionPisoFormulario == $pn ? 'checked' : '' ?> required>
                                    <div class="gc-radio-card">
                                        <i class="fas fa-arrow-down"></i>
                                        <span><?= htmlspecialchars((string)$pl, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($pisosArriba)): ?>
                        <div>
                            <p class="gc-label" style="margin-bottom:9px;"><i class="fas fa-building"></i> Pisos</p>
                            <div class="gc-radio-grid">
                                <?php foreach ($pisosArriba as $pn => $pl): ?>
                                <label>
                                    <input type="radio" name="piso" value="<?= $pn ?>" class="eh-peer"
                                           <?= $habitacionPisoFormulario == $pn ? 'checked' : '' ?> required>
                                    <div class="gc-radio-card">
                                        <i class="fas fa-building"></i>
                                        <span><?= htmlspecialchars((string)$pl, ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (form_error('piso')): ?>
                            <span class="gc-form-error" style="margin-top:8px;display:block"><?= form_error('piso') ?></span>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Sección: Características -->
                <section class="gc-section">
                    <div class="gc-section-head">
                        <div class="gc-section-title-wrap">
                            <span class="gc-section-icon"><i class="fas fa-list-check"></i></span>
                            <div>
                                <h2>Características especiales</h2>
                                <p class="gc-section-sub">Amenidades adicionales que distinguen a esta habitación.</p>
                            </div>
                        </div>
                    </div>
                    <div class="gc-section-body">
                        <div class="gc-grid" style="margin-bottom:14px;">
                            <?php
                            $caracteristicasActuales = strtr(strtolower($habitacionCaracteristicasFormulario), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
                            $keywords = ['pantalla' => 'pantalla', 'balcon' => 'balcon', 'jacuzzi' => 'jacuzzi', 'amplia' => 'amplia'];
                            foreach ($amenidadesHabitacion as $key => $label):
                                $meta       = $amenidadMeta[$key] ?? ['icon' => 'fa-check-circle', 'label' => $label];
                                $keyword    = $keywords[$key] ?? strtolower((string)$label);
                                $isChecked  = $habitacionEspecialesFormulario !== null
                                    ? in_array((string)$key, $habitacionEspecialesFormulario, true)
                                    : strpos($caracteristicasActuales, $keyword) !== false;
                                $isDisabled = isset($meta['disabled_for']) && in_array($habitacionTipoFormulario, $meta['disabled_for'], true);
                                $isChecked  = $isChecked || $isDisabled;
                            ?>
                            <label class="gc-check-item<?= $isDisabled ? ' is-disabled' : '' ?>">
                                <input type="checkbox"
                                       name="caracteristicas_especiales[]"
                                       value="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>"
                                       <?= $isChecked ? 'checked' : '' ?>
                                       <?= $isDisabled ? 'disabled' : '' ?>>
                                <span class="gc-check-icon"><i class="fas <?= htmlspecialchars((string)($meta['icon'] ?? 'fa-check'), ENT_QUOTES, 'UTF-8') ?>"></i></span>
                                <span class="gc-check-name"><?= htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($isDisabled): ?><span class="gc-check-badge">(del tipo)</span><?php endif; ?>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="gc-field">
                            <label class="gc-label"><i class="fas fa-align-left"></i> Descripción completa</label>
                            <textarea name="caracteristicas"
                                      rows="3"
                                      placeholder="Ej: 2 camas matrimoniales, pantalla, balcón, jacuzzi, Wifi, agua caliente..."
                                      class="gc-control"><?= htmlspecialchars($habitacionCaracteristicasFormulario, ENT_QUOTES, 'UTF-8') ?></textarea>
                            <span class="gc-field-hint">Se genera automáticamente al seleccionar las amenidades, pero puede personalizarse libremente.</span>
                        </div>
                    </div>
                </section>

                <!-- Sección: Fotografías -->
                <section class="gc-section">
                    <div class="gc-section-head">
                        <div class="gc-section-title-wrap">
                            <span class="gc-section-icon"><i class="fas fa-images"></i></span>
                            <div>
                                <h2>Fotografías (<?= $totalImagenes ?>/10)</h2>
                                <p class="gc-section-sub">Imágenes actuales y nuevas a agregar.</p>
                            </div>
                        </div>
                        <a href="<?= url('habitaciones/' . $habitacion['id'] . '/imagenes') ?>"
                           style="font-size:.78rem;font-weight:800;color:var(--gc-accent-dark);display:inline-flex;align-items:center;gap:6px;white-space:nowrap;flex-shrink:0;text-decoration:none">
                            <i class="fas fa-cog"></i> Gestionar
                        </a>
                    </div>
                    <div class="gc-section-body">
                        <?php if ($totalImagenes > 0): ?>
                        <div class="gc-photo-grid" style="margin-bottom:16px;">
                            <?php foreach (array_slice($imagenesExistentes, 0, 4) as $idx => $img): ?>
                            <div class="gc-photo-thumb">
                                <img src="<?= image_url($img['url']) ?>" alt="Imagen <?= $idx + 1 ?>">
                                <?php if ($img['es_principal']): ?>
                                    <span class="gc-photo-badge"><i class="fas fa-star"></i> Principal</span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php if ($totalImagenes > 4): ?>
                            <div class="gc-photo-more">+<?= $totalImagenes - 4 ?> más</div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($totalImagenes < 10): ?>
                        <div class="gc-upload-zone" id="drop-zone">
                            <input type="file" name="fotos[]" accept="image/*"
                                   id="fotos-input" multiple class="eh-peer" style="display:none">
                            <label for="fotos-input" style="cursor:pointer;display:block">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Clic para agregar imágenes</p>
                                <small>JPG, PNG, WebP — máx. 5 MB por imagen</small>
                            </label>
                        </div>

                        <div id="upload-alert" class="gc-upload-alert" role="alert" aria-live="assertive" hidden>
                            <i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:2px"></i>
                            <span></span>
                        </div>
                        <?php if (form_error('fotos[]')): ?>
                            <span class="gc-form-error"><?= form_error('fotos[]') ?></span>
                        <?php endif; ?>

                        <div id="preview-container" class="gc-upload-preview" style="display:none">
                            <h5>Imágenes a agregar</h5>
                            <div id="preview-grid" class="gc-upload-preview-grid"></div>
                        </div>
                        <?php else: ?>
                        <div class="gc-note">
                            <i class="fas fa-info-circle"></i>
                            Límite de 10 imágenes alcanzado. Elimina alguna antes de agregar nuevas.
                        </div>
                        <?php endif; ?>

                        <p class="gc-note">
                            <i class="fas fa-info-circle"></i>
                            La imagen marcada como <strong>Principal</strong> se muestra primero en el listado. Gestiona el orden desde el panel de imágenes.
                        </p>
                    </div>
                </section>

            </main><!-- /.gc-form -->

            <!-- ── Sidebar ── -->
            <aside class="gc-side">

                <!-- Preview en tiempo real -->
                <div class="gc-side-card">
                    <h3><span class="gc-side-icon"><i class="fas fa-eye"></i></span> Vista previa</h3>
                    <div class="gc-preview-inner">
                        <div class="gc-preview-room">
                            <div>
                                <p class="gc-preview-num" id="preview-numero"><?= htmlspecialchars($habitacion['numero'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="gc-preview-tipo" id="preview-tipo"><?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <div style="text-align:right">
                                <p class="gc-preview-piso-label">Piso</p>
                                <p class="gc-preview-piso-val" id="preview-piso"><?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario] ?? ('Piso ' . $habitacionPisoFormulario)), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <div class="gc-preview-divider"></div>
                        <p class="gc-preview-precio-label">Precio por noche</p>
                        <p class="gc-preview-precio-val" id="preview-precio">$<?= number_format((float)($habitacion['precio_base'] ?? 0), 0) ?></p>
                        <div class="gc-preview-badges" id="preview-badges"></div>
                    </div>
                </div>

                <!-- Estado activo -->
                <div class="gc-side-card">
                    <h3><span class="gc-side-icon"><i class="fas fa-toggle-on"></i></span> Disponibilidad</h3>
                    <label class="gc-toggle-row">
                        <input type="checkbox" name="activa" value="1"
                               <?= $habitacionActivaFormulario ? 'checked' : '' ?>>
                        <div class="gc-toggle-row-copy">
                            <strong>Habitación activa</strong>
                            <p>Desmarca para ocultarla del sistema de reservas sin eliminarla.</p>
                        </div>
                    </label>
                </div>

                <!-- Resumen -->
                <div class="gc-side-card">
                    <h3><span class="gc-side-icon"><i class="fas fa-circle-info"></i></span> Datos actuales</h3>
                    <div>
                        <div class="gc-info-row">
                            <span>Estado</span>
                            <span><?= htmlspecialchars((string)($_ehEstado['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="gc-info-row">
                            <span>Tipo</span>
                            <span><?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="gc-info-row">
                            <span>Piso</span>
                            <span><?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario] ?? ('Piso ' . $habitacionPisoFormulario)), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <div class="gc-info-row">
                            <span>Capacidad</span>
                            <span><?= (int)($habitacion['capacidad_personas'] ?? 0) ?> personas</span>
                        </div>
                        <?php
                        $totalCamas = (int)($habitacion['camas_matrimoniales'] ?? 0) + (int)($habitacion['camas_individuales'] ?? 0);
                        ?>
                        <div class="gc-info-row">
                            <span>Camas</span>
                            <span><?= $totalCamas > 0 ? $totalCamas . ' en total' : 'Sin definir' ?></span>
                        </div>
                        <div class="gc-info-row">
                            <span>Fotos</span>
                            <span><?= $totalImagenes ?>/10</span>
                        </div>
                    </div>
                </div>

            </aside><!-- /.gc-side -->

        </div><!-- /.gc-layout -->

        <!-- Botones -->
        <div class="gc-actions">
            <a href="<?= back_url('habitaciones/' . $habitacion['id']) ?>" class="gc-btn gc-btn-secondary">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="gc-btn gc-btn-primary">
                <i class="fas fa-save"></i> Guardar cambios
            </button>
        </div>

    </form>

    <!-- ── Zona de eliminación (fuera del form principal) ── -->
    <?php if (can('habitaciones.delete')): ?>
    <div class="gc-danger-zone">
        <div class="gc-danger-head">
            <span class="gc-danger-icon"><i class="fas fa-trash-alt"></i></span>
            <div>
                <h2>Eliminar habitación</h2>
            </div>
        </div>
        <div class="gc-danger-body">
            <?php if ($hayReservacionesBloqueantes): ?>
                <div id="delete-room-blocker" style="padding:12px 14px;background:var(--gc-danger-bg);border:1px solid color-mix(in srgb,var(--gc-danger) 22%,transparent);border-radius:13px;color:#7f1d1d;font-size:.84rem;font-weight:700" role="alert">
                    <p style="margin:0 0 10px"><i class="fas fa-ban" style="margin-right:6px"></i>
                    <strong>No se puede eliminar aún.</strong> Hay <?= count($reservacionesBloqueantes) ?> reservación(es) activa(s) vinculada(s).</p>
                    <?php foreach ($reservacionesBloqueantes as $rb): ?>
                    <div class="gc-blocker-card">
                        <strong>Reservación #<?= (int)($rb['id'] ?? 0) ?></strong>
                        <p><?= htmlspecialchars((string)($rb['nombre_completo'] ?? 'Huésped'), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="gc-blocker-meta">
                            <?php
                            $rbEstado  = $estadosResEliminacion[$rb['estado'] ?? ''] ?? ucfirst((string)($rb['estado'] ?? ''));
                            $rbEntrada = !empty($rb['fecha_entrada']) ? date('d/m/Y', strtotime((string)$rb['fecha_entrada'])) : '—';
                            $rbSalida  = !empty($rb['fecha_salida'])  ? date('d/m/Y', strtotime((string)$rb['fecha_salida']))  : '—';
                            ?>
                            <span><i class="fas fa-circle" style="font-size:.5rem"></i><?= htmlspecialchars($rbEstado, ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i class="fas fa-calendar-alt"></i><?= htmlspecialchars($rbEntrada . ' - ' . $rbSalida, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <a href="<?= url('reservaciones/ver/' . (int)($rb['id'] ?? 0)) ?>" class="gc-blocker-link">
                            <i class="fas fa-external-link-alt"></i> Ver reservación y resolver
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="confirmarEliminacion(this)"
                        class="gc-btn gc-btn-danger" style="width:100%;cursor:not-allowed;opacity:.6"
                        aria-describedby="delete-room-blocker" disabled>
                    <i class="fas fa-lock"></i> Eliminación bloqueada
                </button>

            <?php elseif ($habitacion['estado'] == 'disponible'): ?>
                <p style="font-size:.84rem;color:var(--gc-muted);font-weight:650;margin:0">
                    Eliminar esta habitación la quitará del listado operativo y de las opciones de reserva. Su historial de reservaciones se conservará.
                </p>
                <button type="button" onclick="confirmarEliminacion(this)"
                        class="gc-btn gc-btn-danger" style="width:100%">
                    <i class="fas fa-trash-alt"></i> Eliminar habitación <?= htmlspecialchars($habitacionNumeroEliminacion, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php else: ?>
                <p style="font-size:.84rem;color:var(--gc-muted);font-weight:650;margin:0">
                    <i class="fas fa-info-circle" style="margin-right:6px;color:var(--gc-accent-dark)"></i>
                    Solo se pueden eliminar habitaciones en estado <strong>Disponible</strong>. Estado actual: <strong><?= htmlspecialchars((string)($_ehEstado['label'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>.
                </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /.gc-wrap -->
</div><!-- /.edit-hab-page -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tiposInfo    = <?= json_encode($tiposHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const pisosInfo    = <?= json_encode($pisosHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const rangosPrec   = <?= json_encode($rangosHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK) ?>;
    const amenidadesI  = <?= json_encode($amenidadesHabitacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const numInput     = document.getElementById('input-numero');
    const tipoSel      = document.getElementById('input-tipo');
    const pisoInputs   = document.querySelectorAll('input[name="piso"]');
    const precioInput  = document.getElementById('input-precio');
    const amCheckboxes = document.querySelectorAll('input[name="caracteristicas_especiales[]"]');

    const pvNum    = document.getElementById('preview-numero');
    const pvTipo   = document.getElementById('preview-tipo');
    const pvPiso   = document.getElementById('preview-piso');
    const pvPrecio = document.getElementById('preview-precio');
    const pvBadges = document.getElementById('preview-badges');
    const rangoEl  = document.getElementById('rango-precio');

    function updatePreview() {
        pvNum.textContent  = numInput.value || '—';
        const tipo = tipoSel.value;
        pvTipo.textContent = tiposInfo[tipo] || tipo;

        const pisoEl = document.querySelector('input[name="piso"]:checked');
        pvPiso.textContent = pisoEl ? (pisosInfo[pisoEl.value] || pisoEl.value) : '—';

        const precio = window.MedisoftMoneyInput
            ? window.MedisoftMoneyInput.read(precioInput)
            : (parseFloat(String(precioInput.value).replace(/,/g, '')) || 0);
        pvPrecio.textContent = '$' + precio.toLocaleString('es-MX');

        updateBadges(tipo);
        updateRango(tipo);
        updateJacuzzi(tipo);
    }

    function updateBadges(tipo) {
        pvBadges.innerHTML = '';
        const sel = [];
        amCheckboxes.forEach(cb => {
            if (cb.checked && !cb.disabled) sel.push(amenidadesI[cb.value] || cb.value);
        });
        if (tipo === 'doble_jacuzzi' || tipo === 'sencilla_jacuzzi') {
            if (!sel.includes('Jacuzzi')) sel.unshift('Jacuzzi');
        }
        if (!sel.length) {
            pvBadges.innerHTML = '<span style="font-size:.7rem;color:var(--gc-muted)">Sin especiales</span>';
            return;
        }
        sel.forEach(s => {
            const span = document.createElement('span');
            span.className = 'gc-preview-badge';
            span.textContent = s;
            pvBadges.appendChild(span);
        });
    }

    function updateRango(tipo) {
        if (!rangoEl) return;
        const r = rangosPrec[tipo];
        if (r && tiposInfo[tipo]) {
            rangoEl.textContent = '— Rango sugerido: $' + Number(r.min).toLocaleString() + ' - $' + Number(r.max).toLocaleString();
        } else {
            rangoEl.textContent = '';
        }
    }

    function updateJacuzzi(tipo) {
        const cb = document.querySelector('input[name="caracteristicas_especiales[]"][value="jacuzzi"]');
        if (!cb) return;
        const label = cb.closest('.gc-check-item');
        const isJacType = tipo === 'doble_jacuzzi' || tipo === 'sencilla_jacuzzi';
        cb.disabled = isJacType;
        if (isJacType) { cb.checked = true; label && label.classList.add('is-disabled'); }
        else           { label && label.classList.remove('is-disabled'); }
    }

    numInput.addEventListener('input', updatePreview);
    tipoSel.addEventListener('change', updatePreview);
    pisoInputs.forEach(i => i.addEventListener('change', updatePreview));
    precioInput.addEventListener('input', updatePreview);
    amCheckboxes.forEach(cb => cb.addEventListener('change', updatePreview));
    precioInput.addEventListener('blur', function () {
        const v = parseFloat(this.value);
        if (v) { this.value = Math.round(v / 50) * 50; updatePreview(); }
    });

    updatePreview();
});

// ── Eliminar habitación ──────────────────────────────────────────
const deleteRoomBlocked = <?= $hayReservacionesBloqueantes ? 'true' : 'false' ?>;
const deleteRoomNumber  = <?= json_encode($habitacionNumeroEliminacion, JSON_UNESCAPED_UNICODE) ?>;

function confirmarEliminacion(trigger) {
    if (deleteRoomBlocked) {
        const blocker = document.getElementById('delete-room-blocker');
        if (blocker) {
            blocker.scrollIntoView({ behavior: 'smooth', block: 'center' });
            blocker.style.outline = '3px solid rgba(185,28,28,.4)';
            setTimeout(() => blocker.style.outline = '', 1800);
        }
        return;
    }
    const submit = function () {
        const f = document.createElement('form');
        f.method = 'POST';
        f.action = '<?= url('habitaciones/' . $habitacion['id'] . '/delete') ?>';
        const csrf = document.createElement('input');
        csrf.type = 'hidden'; csrf.name = 'csrf_token'; csrf.value = '<?= csrf_token() ?>';
        f.appendChild(csrf);
        document.body.appendChild(f);
        f.submit();
    };
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Eliminar habitación ' + (deleteRoomNumber || ''),
            html: 'Esta acción la quitará del listado y de nuevas reservas. El historial se conserva.',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#991b1b',
            reverseButtons: true
        }).then(r => { if (r.isConfirmed) submit(); });
        return;
    }
    if (confirm('¿Eliminar habitación ' + (deleteRoomNumber || '') + '?')) submit();
}

// ── Upload de imágenes ───────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const input    = document.getElementById('fotos-input');
    const dropZone = document.getElementById('drop-zone');
    const prevCont = document.getElementById('preview-container');
    const prevGrid = document.getElementById('preview-grid');
    const alert_   = document.getElementById('upload-alert');
    if (!input) return;

    let selectedFiles = [];
    const maxFiles    = <?= 10 - $totalImagenes ?>;
    const maxSize     = 5 * 1024 * 1024;
    const allowed     = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    function showErr(msg) {
        if (!alert_) return;
        alert_.querySelector('span').textContent = msg;
        alert_.hidden = false;
        alert_.classList.add('is-visible');
    }
    function clearErr() {
        if (!alert_) return;
        alert_.hidden = true;
        alert_.classList.remove('is-visible');
    }
    function renderPrev() {
        if (!prevGrid || !prevCont) return;
        prevGrid.innerHTML = '';
        if (!selectedFiles.length) { prevCont.style.display = 'none'; return; }
        prevCont.style.display = '';
        selectedFiles.forEach((file, idx) => {
            const rd = new FileReader();
            rd.onload = e => {
                const d = document.createElement('div');
                d.style.cssText = 'position:relative;border-radius:10px;overflow:hidden;aspect-ratio:4/3;';
                d.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;display:block">
                    <button type="button" onclick="window.__removeImg(${idx})"
                        style="position:absolute;top:4px;right:4px;background:#D64539;color:#fff;border:none;border-radius:6px;width:24px;height:24px;cursor:pointer;font-size:.7rem">✕</button>
                    <p style="font-size:.66rem;color:var(--gc-muted);padding:3px 5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;background:rgba(255,255,255,.9)">${file.name}</p>`;
                prevGrid.appendChild(d);
            };
            rd.readAsDataURL(file);
        });
    }
    function syncInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }
    function handleFiles(files) {
        clearErr();
        const arr = Array.from(files);
        if (arr.length > maxFiles) { showErr(`Solo puedes agregar ${maxFiles} imagen${maxFiles > 1 ? 'es' : ''} más.`); return; }
        selectedFiles = [];
        for (const f of arr) {
            if (!allowed.includes(f.type))  { showErr(f.name + ': tipo no válido'); continue; }
            if (f.size > maxSize)           { showErr(f.name + ': supera el máximo de 5 MB'); continue; }
            selectedFiles.push(f);
        }
        renderPrev(); syncInput();
    }
    window.__removeImg = idx => { selectedFiles.splice(idx, 1); renderPrev(); syncInput(); };

    input.addEventListener('change', e => handleFiles(e.target.files));
    if (dropZone) {
        ['dragenter','dragover','dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }));
        ['dragenter','dragover'].forEach(ev => dropZone.addEventListener(ev, () => dropZone.classList.add('drag-over')));
        ['dragleave','drop'].forEach(ev => dropZone.addEventListener(ev, () => dropZone.classList.remove('drag-over')));
        dropZone.addEventListener('drop', e => handleFiles(e.dataTransfer.files));
    }
});
</script>
