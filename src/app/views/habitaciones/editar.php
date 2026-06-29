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
<style id="edit-hab-s2">
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800;900&display=swap');

.edit-hab-page {
    --s-bg:#F1F5F9; --s-card:#fff; --s-border:#E2E8F0;
    --s-ink:#0F172A; --s-muted:#64748B; --s-subtle:#94A3B8;
    --s-indigo:#6366F1; --s-indigo-2:#4F46E5; --s-indigo-s:#EEF2FF;
    --s-green:#059669; --s-green-s:#ECFDF5;
    --s-red:#DC2626; --s-red-s:#FEF2F2;
    --s-shadow:0 1px 3px rgba(15,23,42,.05),0 8px 24px rgba(15,23,42,.07);
    --s-r:13px;
    min-height:100vh; background:var(--s-bg); color:var(--s-ink);
    font-family:'Manrope',system-ui,-apple-system,sans-serif;
    -webkit-font-smoothing:antialiased;
}
.edit-hab-page *,.edit-hab-page *::before,.edit-hab-page *::after{box-sizing:border-box}
.s-shell{width:100%;max-width:1300px;margin:0 auto;padding:22px 20px 48px}
.s-crumb{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;color:var(--s-muted);margin-bottom:20px}
.s-crumb a{color:var(--s-indigo);text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:opacity .15s}
.s-crumb a:hover{opacity:.72}
.s-crumb-sep{font-size:.58rem;opacity:.4}
/* Hero */
.s-hero{display:grid;grid-template-columns:1fr auto;gap:20px;align-items:end;padding-bottom:22px;border-bottom:1.5px solid var(--s-border);margin-bottom:22px}
.s-hero-eyebrow{font-size:.64rem;font-weight:900;text-transform:uppercase;letter-spacing:.14em;color:var(--s-indigo);display:block;margin-bottom:6px}
.s-hero-num{font-size:clamp(3.8rem,10vw,6.5rem);font-weight:900;color:var(--s-ink);line-height:.86;margin:0;letter-spacing:-.04em}
.s-hero-meta{display:flex;align-items:center;gap:10px;margin-top:10px;font-size:.86rem;font-weight:600;color:var(--s-muted)}
.s-hero-meta strong{color:var(--s-ink);font-weight:800}
.s-hero-meta-dot{width:3px;height:3px;border-radius:50%;background:var(--s-subtle)}
.s-hero-right{display:flex;flex-direction:column;align-items:flex-end;gap:10px}
.s-status-pill{display:inline-flex;align-items:center;gap:7px;padding:6px 13px;border-radius:999px;font-size:.74rem;font-weight:800;background:var(--s-green-s);color:var(--s-green);border:1px solid rgba(5,150,105,.18)}
.s-hero-link{font-size:.74rem;font-weight:800;color:var(--s-indigo);text-decoration:none;display:inline-flex;align-items:center;gap:5px;transition:opacity .15s}
.s-hero-link:hover{opacity:.7}
/* Layout */
.s-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:18px;align-items:start}
.s-main{display:flex;flex-direction:column;gap:14px;min-width:0}
/* Card */
.s-card{background:var(--s-card);border:1px solid var(--s-border);border-radius:var(--s-r);box-shadow:var(--s-shadow);overflow:hidden}
/* Section header */
.s-sec-head{display:flex;align-items:flex-start;gap:13px;padding:15px 18px;border-bottom:1px solid var(--s-border);background:#FAFBFC}
.s-sec-num{font-size:.58rem;font-weight:900;letter-spacing:.1em;color:var(--s-indigo);background:var(--s-indigo-s);border:1px solid rgba(99,102,241,.22);border-radius:6px;padding:3px 7px;flex-shrink:0;margin-top:1px;font-variant-numeric:tabular-nums}
.s-sec-title{font-size:.93rem;font-weight:800;color:var(--s-ink);margin:0 0 2px}
.s-sec-sub{font-size:.73rem;font-weight:600;color:var(--s-muted);margin:0;line-height:1.35}
.s-sec-action{margin-left:auto;flex-shrink:0;font-size:.75rem;font-weight:800;color:var(--s-indigo);text-decoration:none;display:inline-flex;align-items:center;gap:5px}
.s-sec-action:hover{opacity:.7}
.s-sec-body{padding:18px}
/* Grid & fields */
.s-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}
.s-grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.s-full{grid-column:1/-1}
.s-field{min-width:0;display:flex;flex-direction:column;gap:5px}
.s-label{font-size:.67rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--s-muted)}
.s-req{color:var(--s-red)}
.s-hint{font-size:.73rem;font-weight:600;color:var(--s-subtle);line-height:1.4}
.s-err{font-size:.74rem;font-weight:800;color:var(--s-red)}
.s-wrap{position:relative}
.s-wrap-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--s-subtle);font-size:.72rem;pointer-events:none}
.s-wrap-pre{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--s-muted);font-size:.88rem;font-weight:800;pointer-events:none}
.s-inp{width:100%;height:42px;padding:0 12px;border:1.5px solid var(--s-border);border-radius:10px;font-family:inherit;font-size:.87rem;font-weight:700;color:var(--s-ink);background:var(--s-card);outline:none;-webkit-appearance:none;appearance:none;transition:border-color .14s,box-shadow .14s}
.s-inp::placeholder{color:var(--s-subtle);font-weight:500}
.s-inp:focus{border-color:var(--s-indigo);box-shadow:0 0 0 3px rgba(99,102,241,.12)}
.s-inp.has-ico{padding-left:32px}
.s-inp.has-pre{padding-left:24px}
textarea.s-inp{height:auto;padding-top:10px;padding-bottom:10px;resize:vertical;line-height:1.5}
.s-note{padding:10px 13px;border-radius:10px;background:var(--s-indigo-s);border:1px solid rgba(99,102,241,.16);font-size:.76rem;font-weight:650;color:var(--s-muted);line-height:1.45;margin-top:12px}
.s-note i{color:var(--s-indigo);margin-right:5px}
/* Piso */
.s-floor-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(72px,1fr));gap:7px}
.s-floor-input{display:none}
.s-floor-label{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;padding:10px 6px;border:1.5px solid var(--s-border);border-radius:10px;background:var(--s-card);cursor:pointer;font-size:.7rem;font-weight:800;color:var(--s-muted);text-align:center;line-height:1.1;transition:border-color .14s,background .14s,color .14s;user-select:none}
.s-floor-num{font-size:1.15rem;font-weight:900;color:var(--s-ink);font-variant-numeric:tabular-nums;line-height:1}
.s-floor-input:checked+.s-floor-label{border-color:var(--s-indigo);background:var(--s-indigo-s);color:var(--s-indigo)}
.s-floor-input:checked+.s-floor-label .s-floor-num{color:var(--s-indigo-2)}
.s-floor-label:hover{border-color:rgba(99,102,241,.4);background:var(--s-indigo-s)}
.s-floor-group-label{font-size:.6rem;font-weight:900;text-transform:uppercase;letter-spacing:.08em;color:var(--s-subtle);margin-bottom:6px;display:flex;align-items:center;gap:7px}
.s-floor-group-label::after{content:'';flex:1;height:1px;background:var(--s-border)}
/* Amenidades */
.s-amenity-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.s-amenity-input{display:none}
.s-amenity-chip{display:flex;align-items:center;gap:9px;padding:11px 13px;border:1.5px solid var(--s-border);border-radius:10px;background:var(--s-card);cursor:pointer;transition:border-color .14s,background .14s;user-select:none}
.s-amenity-chip-ico{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:var(--s-bg);border:1px solid var(--s-border);font-size:.76rem;color:var(--s-muted);flex-shrink:0;transition:background .14s,color .14s,border-color .14s}
.s-amenity-chip-name{font-size:.82rem;font-weight:700;color:var(--s-ink);flex:1}
.s-amenity-chip-badge{font-size:.65rem;font-weight:800;color:var(--s-subtle)}
.s-amenity-input:checked+.s-amenity-chip{border-color:var(--s-indigo);background:var(--s-indigo-s)}
.s-amenity-input:checked+.s-amenity-chip .s-amenity-chip-ico{background:var(--s-indigo);color:#fff;border-color:var(--s-indigo-2)}
.s-amenity-input:checked+.s-amenity-chip .s-amenity-chip-name{color:var(--s-indigo-2)}
.s-amenity-input:disabled+.s-amenity-chip{opacity:.52;cursor:not-allowed}
/* Fotos */
.s-photo-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:8px;margin-bottom:14px}
.s-photo-thumb{position:relative;border-radius:10px;overflow:hidden;aspect-ratio:4/3}
.s-photo-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.s-photo-star{position:absolute;top:5px;left:5px;background:var(--s-green);color:#fff;border-radius:999px;padding:2px 7px;font-size:.62rem;font-weight:900}
.s-photo-more{display:flex;align-items:center;justify-content:center;border:1.5px dashed var(--s-border);border-radius:10px;aspect-ratio:4/3;color:var(--s-muted);font-size:.78rem;font-weight:700}
.s-upload-zone{border:2px dashed var(--s-border);border-radius:12px;padding:28px 16px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s}
.s-upload-zone.drag-over,.s-upload-zone:hover{border-color:var(--s-indigo);background:var(--s-indigo-s)}
.s-upload-ico{font-size:1.8rem;color:var(--s-subtle);display:block;margin-bottom:8px}
.s-upload-txt{font-size:.84rem;font-weight:700;color:var(--s-muted);margin:0 0 4px}
.s-upload-sub{font-size:.72rem;color:var(--s-subtle);font-weight:600}
.s-upload-alert{display:none;gap:9px;align-items:flex-start;margin-top:11px;padding:10px 13px;border-radius:10px;background:var(--s-red-s);border:1px solid rgba(220,38,38,.2);font-size:.8rem;font-weight:700;color:var(--s-red)}
.s-upload-alert.is-visible{display:flex}
.s-upload-prev{margin-top:14px}
.s-upload-prev-title{font-size:.74rem;font-weight:800;color:var(--s-ink);margin:0 0 8px}
.s-upload-prev-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:7px}
/* Aside */
.s-aside{position:sticky;top:18px;display:flex;flex-direction:column;gap:12px}
/* Key card */
.s-keycard{border-radius:16px;overflow:hidden;box-shadow:0 8px 28px rgba(15,23,42,.16);background:linear-gradient(145deg,#1E293B,#0F172A)}
.s-keycard-stripe{height:5px;background:linear-gradient(90deg,var(--s-indigo),#818CF8,var(--s-indigo-2))}
.s-keycard-body{padding:20px 18px 18px}
.s-keycard-hotel{font-size:.6rem;font-weight:900;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.4);margin-bottom:16px;display:block}
.s-keycard-room-label{font-size:.58rem;font-weight:900;text-transform:uppercase;letter-spacing:.14em;color:rgba(255,255,255,.4);display:block;margin-bottom:3px}
.s-keycard-room-num{font-size:3.2rem;font-weight:900;line-height:.88;color:#fff;letter-spacing:-.04em;display:block;margin-bottom:4px;font-variant-numeric:tabular-nums}
.s-keycard-tipo{font-size:.78rem;font-weight:700;color:rgba(255,255,255,.58)}
.s-keycard-divider{height:1px;background:rgba(255,255,255,.1);margin:14px 0}
.s-keycard-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.s-keycard-row:last-of-type{margin-bottom:0}
.s-keycard-k{font-size:.66rem;font-weight:700;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em}
.s-keycard-v{font-size:.82rem;font-weight:800;color:rgba(255,255,255,.9)}
.s-keycard-precio{font-size:1.2rem;font-weight:900;color:#fff;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
.s-keycard-features{display:flex;flex-wrap:wrap;gap:5px;margin-top:14px}
.s-keycard-feat{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:6px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.7);font-size:.65rem;font-weight:800}
/* Aside cards */
.s-aside-card{background:var(--s-card);border:1px solid var(--s-border);border-radius:var(--s-r);box-shadow:var(--s-shadow);overflow:hidden}
.s-aside-head{padding:12px 16px;border-bottom:1px solid var(--s-border);display:flex;align-items:center;gap:9px}
.s-aside-head-ico{width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:var(--s-indigo-s);color:var(--s-indigo);font-size:.72rem;flex-shrink:0}
.s-aside-head h3{font-size:.85rem;font-weight:800;color:var(--s-ink);margin:0}
.s-aside-body{padding:14px 16px}
.s-toggle-row{display:flex;align-items:flex-start;gap:11px}
.s-toggle-row input[type=checkbox]{width:17px;height:17px;accent-color:var(--s-indigo);flex-shrink:0;margin-top:2px;cursor:pointer}
.s-toggle-label strong{display:block;font-size:.84rem;font-weight:800;color:var(--s-ink)}
.s-toggle-label p{margin:3px 0 0;font-size:.73rem;font-weight:600;color:var(--s-muted);line-height:1.35}
.s-info-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid var(--s-border);font-size:.8rem}
.s-info-row:last-child{border-bottom:none;padding-bottom:0}
.s-info-row span:first-child{color:var(--s-muted);font-weight:600}
.s-info-row span:last-child{font-weight:800;color:var(--s-ink)}
/* Actions */
.s-foot{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}
.s-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;height:42px;padding:0 18px;border-radius:10px;font-family:inherit;font-size:.86rem;font-weight:800;cursor:pointer;border:none;text-decoration:none;transition:opacity .15s,transform .15s,box-shadow .15s}
.s-btn:hover{transform:translateY(-1px)}
.s-btn:active{transform:scale(.98)}
.s-btn-ghost{background:var(--s-card);border:1.5px solid var(--s-border);color:var(--s-muted)}
.s-btn-ghost:hover{border-color:var(--s-indigo);color:var(--s-indigo);background:var(--s-indigo-s)}
.s-btn-primary{background:var(--s-indigo-2);color:#fff;box-shadow:0 8px 20px rgba(79,70,229,.3)}
.s-btn-primary:hover{background:#4338CA;box-shadow:0 10px 24px rgba(79,70,229,.36)}
.s-btn-danger{background:var(--s-red-s);border:1.5px solid rgba(220,38,38,.22);color:var(--s-red)}
.s-btn-danger:hover{background:#FEE2E2}
/* Danger zone */
.s-danger{margin-top:22px;border:1.5px solid rgba(220,38,38,.18);border-radius:var(--s-r);overflow:hidden}
.s-danger-head{display:flex;align-items:center;gap:11px;padding:13px 18px;background:var(--s-red-s);border-bottom:1px solid rgba(220,38,38,.14)}
.s-danger-head-ico{width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:var(--s-red);color:#fff;font-size:.72rem;flex-shrink:0}
.s-danger-head h2{font-size:.88rem;font-weight:800;color:#991B1B;margin:0}
.s-danger-body{padding:16px 18px;display:flex;flex-direction:column;gap:12px;background:var(--s-card)}
.s-blocker{border:1px solid rgba(220,38,38,.16);border-radius:10px;padding:12px 14px}
.s-blocker strong{display:block;font-size:.84rem;font-weight:800;color:#7F1D1D}
.s-blocker p{margin:3px 0 0;font-size:.76rem;font-weight:650;color:#991B1B}
.s-blocker-meta{display:flex;flex-wrap:wrap;gap:7px;margin-top:8px}
.s-blocker-meta span{display:inline-flex;align-items:center;gap:4px;font-size:.7rem;font-weight:800;color:#7F1D1D}
.s-blocker-link{display:inline-flex;align-items:center;gap:6px;margin-top:9px;padding:6px 12px;border:1px solid rgba(220,38,38,.2);border-radius:8px;background:#fff;color:#991B1B;font-size:.74rem;font-weight:800;text-decoration:none;transition:background .14s,transform .14s}
.s-blocker-link:hover{background:var(--s-red-s);transform:translateY(-1px)}
/* Responsive */
@media(max-width:1180px){.s-layout{grid-template-columns:minmax(0,1fr) 280px}.s-photo-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:1024px){.s-layout{grid-template-columns:1fr}.s-aside{position:static;display:grid;grid-template-columns:repeat(2,1fr)}}
@media(max-width:860px){.s-hero{grid-template-columns:1fr}.s-aside{grid-template-columns:1fr}.s-grid-3{grid-template-columns:repeat(2,1fr)}}
@media(max-width:700px){
    .s-shell{padding:12px 12px 32px}
    .s-hero-num{font-size:clamp(3rem,18vw,4.5rem)}
    .s-sec-sub,.s-hint,.s-note{display:none}
    .s-grid{grid-template-columns:repeat(2,1fr);gap:9px}
    .s-grid-3{grid-template-columns:1fr}
    .s-sec-body{padding:12px}
    .s-sec-head{padding:10px 12px}
    .s-inp{font-size:16px}
    .s-photo-grid{grid-template-columns:repeat(2,1fr)}
    .s-upload-prev-grid{grid-template-columns:repeat(2,1fr)}
    .s-amenity-grid{grid-template-columns:1fr}
    .s-foot{display:grid;grid-template-columns:1fr 1.6fr}
    .s-btn{width:100%}
}
@media(max-width:380px){.s-foot{grid-template-columns:1fr}.s-btn-primary{order:-1}}
@media(prefers-reduced-motion:reduce){.edit-hab-page *,.edit-hab-page *::before,.edit-hab-page *::after{transition:none!important}}
</style>

<div class="edit-hab-page">
<div class="s-shell">

<nav class="s-crumb">
    <a href="<?= url('habitaciones') ?>"><i class="fas fa-bed"></i> Habitaciones</a>
    <span class="s-crumb-sep"><i class="fas fa-chevron-right"></i></span>
    <a href="<?= url('habitaciones/' . $habitacion['id']) ?>">Hab. <?= htmlspecialchars($habitacion['numero'], ENT_QUOTES, 'UTF-8') ?></a>
    <span class="s-crumb-sep"><i class="fas fa-chevron-right"></i></span>
    <span>Editar</span>
</nav>

<div class="s-hero">
    <div>
        <span class="s-hero-eyebrow">Editar habitación</span>
        <h1 class="s-hero-num" id="preview-numero"><?= htmlspecialchars($habitacionNumeroFormulario, ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="s-hero-meta">
            <strong id="preview-tipo"><?= htmlspecialchars((string)$habitacionTipoLabel, ENT_QUOTES, 'UTF-8') ?></strong>
            <span class="s-hero-meta-dot"></span>
            <span id="preview-piso"><?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario] ?? ('Piso ' . $habitacionPisoFormulario)), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="s-hero-right">
        <span class="s-status-pill"><i class="fas <?= $_ehEstadoIcon ?>"></i> <?= htmlspecialchars((string)($_ehEstado['label'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
        <a href="<?= url('habitaciones/' . $habitacion['id']) ?>" class="s-hero-link">Ver ficha <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<form method="POST" action="<?= url('habitaciones/' . $habitacion['id'] . '/update') ?>" enctype="multipart/form-data" id="formEditHab">
    <?= csrf_field() ?>
    <div class="s-layout">
    <main class="s-main">

    <!-- 01 -->
    <div class="s-card">
        <div class="s-sec-head">
            <span class="s-sec-num">01</span>
            <div><h2 class="s-sec-title">Identificación y precio</h2><p class="s-sec-sub">Número, tipo y tarifa por noche.</p></div>
        </div>
        <div class="s-sec-body">
            <div class="s-grid">
                <div class="s-field">
                    <label class="s-label">Número <span class="s-req">*</span></label>
                    <div class="s-wrap"><i class="fas fa-hashtag s-wrap-ico"></i>
                        <input type="text" name="numero" id="input-numero" value="<?= htmlspecialchars($habitacionNumeroFormulario, ENT_QUOTES, 'UTF-8') ?>" required placeholder="Ej. 101" class="s-inp has-ico">
                    </div>
                    <?php if(form_error('numero')): ?><span class="s-err"><?= form_error('numero') ?></span><?php endif; ?>
                </div>
                <div class="s-field">
                    <label class="s-label">Tipo <span class="s-req">*</span></label>
                    <div class="s-wrap"><i class="fas fa-tag s-wrap-ico"></i>
                        <select name="tipo" id="input-tipo" required class="s-inp has-ico">
                            <?php foreach($tiposHabitacion as $k=>$lbl): ?>
                            <option value="<?= htmlspecialchars((string)$k,ENT_QUOTES,'UTF-8') ?>" <?= $habitacionTipoFormulario==$k?'selected':'' ?>><?= htmlspecialchars((string)$lbl,ENT_QUOTES,'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if(form_error('tipo')): ?><span class="s-err"><?= form_error('tipo') ?></span><?php endif; ?>
                </div>
                <div class="s-field s-full">
                    <label class="s-label">Precio por noche <span class="s-req">*</span>
                        <span id="rango-precio" style="font-weight:600;text-transform:none;letter-spacing:0;margin-left:6px">— sugerido: $<?= number_format($rangoActual['min']) ?>–$<?= number_format($rangoActual['max']) ?></span>
                    </label>
                    <div class="s-wrap"><span class="s-wrap-pre">$</span>
                        <input type="number" name="precio_base" id="input-precio" data-money-format="true" value="<?= htmlspecialchars($habitacionPrecioFormulario,ENT_QUOTES,'UTF-8') ?>" step="50" required placeholder="0.00" class="s-inp has-pre" style="font-size:1.05rem;font-weight:900">
                    </div>
                    <?php if(form_error('precio_base')): ?><span class="s-err"><?= form_error('precio_base') ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 02 -->
    <div class="s-card">
        <div class="s-sec-head">
            <span class="s-sec-num">02</span>
            <div><h2 class="s-sec-title">Capacidad y camas</h2><p class="s-sec-sub">Personas y configuración de camas.</p></div>
        </div>
        <div class="s-sec-body">
            <div class="s-grid s-grid-3">
                <div class="s-field">
                    <label class="s-label">Personas <span class="s-req">*</span></label>
                    <div class="s-wrap"><i class="fas fa-user s-wrap-ico"></i>
                        <input type="number" name="capacidad_personas" id="input-capacidad" value="<?= htmlspecialchars($habitacionCapacidadFormulario,ENT_QUOTES,'UTF-8') ?>" min="1" max="30" step="1" required class="s-inp has-ico">
                    </div>
                    <?php if(form_error('capacidad_personas')): ?><span class="s-err"><?= form_error('capacidad_personas') ?></span><?php endif; ?>
                </div>
                <div class="s-field">
                    <label class="s-label">Matrimoniales <span class="s-req">*</span></label>
                    <div class="s-wrap"><i class="fas fa-bed s-wrap-ico"></i>
                        <input type="number" name="camas_matrimoniales" value="<?= htmlspecialchars($habitacionCamasMatrimonialesForm,ENT_QUOTES,'UTF-8') ?>" min="0" max="20" step="1" required class="s-inp has-ico">
                    </div>
                    <?php if(form_error('camas_matrimoniales')): ?><span class="s-err"><?= form_error('camas_matrimoniales') ?></span><?php endif; ?>
                </div>
                <div class="s-field">
                    <label class="s-label">Individuales</label>
                    <div class="s-wrap"><i class="fas fa-bed s-wrap-ico"></i>
                        <input type="number" name="camas_individuales" value="<?= htmlspecialchars($habitacionCamasIndividualesForm,ENT_QUOTES,'UTF-8') ?>" min="0" max="20" step="1" class="s-inp has-ico">
                    </div>
                    <?php if(form_error('camas_individuales')): ?><span class="s-err"><?= form_error('camas_individuales') ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 03 -->
    <div class="s-card">
        <div class="s-sec-head">
            <span class="s-sec-num">03</span>
            <div><h2 class="s-sec-title">Ubicación — Piso</h2><p class="s-sec-sub">Nivel donde se encuentra la habitación.</p></div>
        </div>
        <div class="s-sec-body">
            <?php if(!empty($pisosAbajo)): ?>
            <div style="margin-bottom:14px">
                <p class="s-floor-group-label"><i class="fas fa-arrow-down" style="font-size:.62rem"></i> Niveles inferiores</p>
                <div class="s-floor-grid">
                    <?php foreach($pisosAbajo as $pn=>$pl): ?>
                    <label><input type="radio" name="piso" value="<?= $pn ?>" class="s-floor-input" <?= $habitacionPisoFormulario==$pn?'checked':'' ?> required>
                    <span class="s-floor-label"><span class="s-floor-num"><?= abs($pn) ?></span><span><?= htmlspecialchars((string)$pl,ENT_QUOTES,'UTF-8') ?></span></span></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($pisosArriba)): ?>
            <?php if(!empty($pisosAbajo)): ?><p class="s-floor-group-label" style="margin-top:14px"><i class="fas fa-building" style="font-size:.62rem"></i> Pisos</p><?php endif; ?>
            <div class="s-floor-grid">
                <?php foreach($pisosArriba as $pn=>$pl): ?>
                <label><input type="radio" name="piso" value="<?= $pn ?>" class="s-floor-input" <?= $habitacionPisoFormulario==$pn?'checked':'' ?> required>
                <span class="s-floor-label"><span class="s-floor-num"><?= $pn ?></span><span><?= htmlspecialchars((string)$pl,ENT_QUOTES,'UTF-8') ?></span></span></label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if(form_error('piso')): ?><span class="s-err" style="margin-top:8px;display:block"><?= form_error('piso') ?></span><?php endif; ?>
        </div>
    </div>

    <!-- 04 -->
    <div class="s-card">
        <div class="s-sec-head">
            <span class="s-sec-num">04</span>
            <div><h2 class="s-sec-title">Características especiales</h2><p class="s-sec-sub">Amenidades que distinguen esta habitación.</p></div>
        </div>
        <div class="s-sec-body" style="display:flex;flex-direction:column;gap:14px">
            <div class="s-amenity-grid">
                <?php
                $carAct=strtr(strtolower($habitacionCaracteristicasFormulario),['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
                $kwMap=['pantalla'=>'pantalla','balcon'=>'balcon','jacuzzi'=>'jacuzzi','amplia'=>'amplia'];
                foreach($amenidadesHabitacion as $key=>$label):
                    $meta=$amenidadMeta[$key]??['icon'=>'fa-check-circle','label'=>$label];
                    $kw=$kwMap[$key]??strtolower((string)$label);
                    $isChecked=$habitacionEspecialesFormulario!==null?in_array((string)$key,$habitacionEspecialesFormulario,true):strpos($carAct,$kw)!==false;
                    $isDisabled=isset($meta['disabled_for'])&&in_array($habitacionTipoFormulario,$meta['disabled_for'],true);
                    $isChecked=$isChecked||$isDisabled;
                ?>
                <label>
                    <input type="checkbox" name="caracteristicas_especiales[]" value="<?= htmlspecialchars((string)$key,ENT_QUOTES,'UTF-8') ?>" class="s-amenity-input" <?= $isChecked?'checked':'' ?> <?= $isDisabled?'disabled':'' ?>>
                    <span class="s-amenity-chip">
                        <span class="s-amenity-chip-ico"><i class="fas <?= htmlspecialchars((string)($meta['icon']??'fa-check'),ENT_QUOTES,'UTF-8') ?>"></i></span>
                        <span class="s-amenity-chip-name"><?= htmlspecialchars((string)$label,ENT_QUOTES,'UTF-8') ?></span>
                        <?php if($isDisabled): ?><span class="s-amenity-chip-badge">del tipo</span><?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="s-field">
                <label class="s-label">Descripción libre</label>
                <textarea name="caracteristicas" rows="3" class="s-inp" placeholder="Ej: 2 camas matrimoniales, pantalla, balcón..."><?= htmlspecialchars($habitacionCaracteristicasFormulario,ENT_QUOTES,'UTF-8') ?></textarea>
                <span class="s-hint">Personalizable — se auto-genera con las amenidades seleccionadas.</span>
            </div>
        </div>
    </div>

    <!-- 05 -->
    <div class="s-card">
        <div class="s-sec-head">
            <span class="s-sec-num">05</span>
            <div><h2 class="s-sec-title">Fotografías (<?= $totalImagenes ?>/10)</h2><p class="s-sec-sub">Imágenes actuales y carga de nuevas.</p></div>
            <a href="<?= url('habitaciones/' . $habitacion['id'] . '/imagenes') ?>" class="s-sec-action"><i class="fas fa-cog"></i> Gestionar</a>
        </div>
        <div class="s-sec-body">
            <?php if($totalImagenes>0): ?>
            <div class="s-photo-grid">
                <?php foreach(array_slice($imagenesExistentes,0,4) as $i=>$img): ?>
                <div class="s-photo-thumb"><img src="<?= image_url($img['url']) ?>" alt="Imagen <?= $i+1 ?>"><?php if($img['es_principal']): ?><span class="s-photo-star"><i class="fas fa-star"></i></span><?php endif; ?></div>
                <?php endforeach; ?>
                <?php if($totalImagenes>4): ?><div class="s-photo-more">+<?= $totalImagenes-4 ?> más</div><?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if($totalImagenes<10): ?>
            <div class="s-upload-zone" id="drop-zone">
                <input type="file" name="fotos[]" accept="image/*" id="fotos-input" multiple style="display:none">
                <label for="fotos-input" style="cursor:pointer;display:block">
                    <i class="fas fa-cloud-upload-alt s-upload-ico"></i>
                    <p class="s-upload-txt">Clic para agregar imágenes</p>
                    <small class="s-upload-sub">JPG, PNG, WebP — máx. 5 MB</small>
                </label>
            </div>
            <div id="upload-alert" class="s-upload-alert" role="alert" aria-live="assertive" hidden><i class="fas fa-circle-exclamation" style="flex-shrink:0;margin-top:1px"></i><span></span></div>
            <?php if(form_error('fotos[]')): ?><span class="s-err"><?= form_error('fotos[]') ?></span><?php endif; ?>
            <div id="preview-container" class="s-upload-prev" style="display:none">
                <p class="s-upload-prev-title">A agregar:</p>
                <div id="preview-grid" class="s-upload-prev-grid"></div>
            </div>
            <?php else: ?>
            <p class="s-note"><i class="fas fa-info-circle"></i> Límite de 10 imágenes alcanzado. Elimina alguna para agregar nuevas.</p>
            <?php endif; ?>
        </div>
    </div>

    </main>

    <aside class="s-aside">
        <div class="s-keycard">
            <div class="s-keycard-stripe"></div>
            <div class="s-keycard-body">
                <span class="s-keycard-hotel"><?= htmlspecialchars(function_exists('current_hotel_display_name')?current_hotel_display_name():'Hotel',ENT_QUOTES,'UTF-8') ?></span>
                <span class="s-keycard-room-label">Habitación</span>
                <span class="s-keycard-room-num" id="preview-numero-kc"><?= htmlspecialchars($habitacionNumeroFormulario,ENT_QUOTES,'UTF-8') ?></span>
                <span class="s-keycard-tipo" id="preview-tipo-kc"><?= htmlspecialchars((string)$habitacionTipoLabel,ENT_QUOTES,'UTF-8') ?></span>
                <div class="s-keycard-divider"></div>
                <div class="s-keycard-row"><span class="s-keycard-k">Piso</span><span class="s-keycard-v" id="preview-piso-kc"><?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario]??('Piso '.$habitacionPisoFormulario)),ENT_QUOTES,'UTF-8') ?></span></div>
                <div class="s-keycard-row"><span class="s-keycard-k">Precio / noche</span><span class="s-keycard-precio" id="preview-precio">$<?= number_format((float)($habitacion['precio_base']??0),0) ?></span></div>
                <div class="s-keycard-features" id="preview-badges"></div>
            </div>
        </div>

        <div class="s-aside-card">
            <div class="s-aside-head"><span class="s-aside-head-ico"><i class="fas fa-toggle-on"></i></span><h3>Disponibilidad</h3></div>
            <div class="s-aside-body">
                <label class="s-toggle-row">
                    <input type="checkbox" name="activa" value="1" <?= $habitacionActivaFormulario?'checked':'' ?>>
                    <div class="s-toggle-label"><strong>Habitación activa</strong><p>Desmarca para ocultarla de reservas sin eliminarla.</p></div>
                </label>
            </div>
        </div>

        <div class="s-aside-card">
            <div class="s-aside-head"><span class="s-aside-head-ico"><i class="fas fa-circle-info"></i></span><h3>Datos actuales</h3></div>
            <div class="s-aside-body">
                <div class="s-info-row"><span>Estado</span><span><?= htmlspecialchars((string)($_ehEstado['label']??'—'),ENT_QUOTES,'UTF-8') ?></span></div>
                <div class="s-info-row"><span>Tipo</span><span><?= htmlspecialchars((string)$habitacionTipoLabel,ENT_QUOTES,'UTF-8') ?></span></div>
                <div class="s-info-row"><span>Piso</span><span><?= htmlspecialchars((string)($pisosHabitacion[$habitacionPisoFormulario]??'—'),ENT_QUOTES,'UTF-8') ?></span></div>
                <?php $tc=(int)($habitacion['camas_matrimoniales']??0)+(int)($habitacion['camas_individuales']??0); ?>
                <div class="s-info-row"><span>Camas</span><span><?= $tc>0?$tc.' total':'—' ?></span></div>
                <div class="s-info-row"><span>Fotos</span><span><?= $totalImagenes ?>/10</span></div>
            </div>
        </div>
    </aside>
    </div>

    <div class="s-foot">
        <a href="<?= back_url('habitaciones/' . $habitacion['id']) ?>" class="s-btn s-btn-ghost"><i class="fas fa-times"></i> Cancelar</a>
        <button type="submit" class="s-btn s-btn-primary"><i class="fas fa-save"></i> Guardar cambios</button>
    </div>
</form>

<?php if(can('habitaciones.delete')): ?>
<div class="s-danger">
    <div class="s-danger-head">
        <span class="s-danger-head-ico"><i class="fas fa-trash-alt"></i></span>
        <h2>Eliminar habitación <?= htmlspecialchars($habitacionNumeroEliminacion,ENT_QUOTES,'UTF-8') ?></h2>
    </div>
    <div class="s-danger-body">
        <?php if($hayReservacionesBloqueantes): ?>
        <div id="delete-room-blocker" style="padding:12px 14px;background:var(--s-red-s);border:1px solid rgba(220,38,38,.18);border-radius:10px;color:#7F1D1D;font-size:.82rem;font-weight:700" role="alert">
            <p style="margin:0 0 10px"><i class="fas fa-ban" style="margin-right:6px"></i><strong>Eliminación bloqueada</strong> — <?= count($reservacionesBloqueantes) ?> reservación(es) activa(s).</p>
            <?php foreach($reservacionesBloqueantes as $rb): ?>
            <div class="s-blocker">
                <strong>Reservación #<?= (int)($rb['id']??0) ?></strong>
                <p><?= htmlspecialchars((string)($rb['nombre_completo']??'Huésped'),ENT_QUOTES,'UTF-8') ?></p>
                <?php $rbE=$estadosResEliminacion[$rb['estado']??'']??ucfirst((string)($rb['estado']??'')); $rbIn=!empty($rb['fecha_entrada'])?date('d/m/Y',strtotime((string)$rb['fecha_entrada'])):'—'; $rbSa=!empty($rb['fecha_salida'])?date('d/m/Y',strtotime((string)$rb['fecha_salida'])):'—'; ?>
                <div class="s-blocker-meta">
                    <span><i class="fas fa-circle" style="font-size:.45rem"></i><?= htmlspecialchars($rbE,ENT_QUOTES,'UTF-8') ?></span>
                    <span><i class="fas fa-calendar-alt"></i><?= htmlspecialchars("$rbIn – $rbSa",ENT_QUOTES,'UTF-8') ?></span>
                </div>
                <a href="<?= url('reservaciones/ver/'.(int)($rb['id']??0)) ?>" class="s-blocker-link"><i class="fas fa-external-link-alt"></i> Ir a reservación</a>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" disabled class="s-btn s-btn-danger" style="width:100%;cursor:not-allowed;opacity:.6"><i class="fas fa-lock"></i> Eliminación bloqueada</button>
        <?php elseif($habitacion['estado']=='disponible'): ?>
        <p style="font-size:.82rem;color:var(--s-muted);font-weight:650;margin:0">Eliminar la quitará del listado. El historial de reservas se conserva.</p>
        <button type="button" onclick="confirmarEliminacion()" class="s-btn s-btn-danger" style="width:100%"><i class="fas fa-trash-alt"></i> Eliminar habitación <?= htmlspecialchars($habitacionNumeroEliminacion,ENT_QUOTES,'UTF-8') ?></button>
        <?php else: ?>
        <p style="font-size:.82rem;color:var(--s-muted);font-weight:650;margin:0"><i class="fas fa-info-circle" style="color:var(--s-indigo);margin-right:6px"></i> Solo disponible en estado <strong>Disponible</strong>. Actual: <strong><?= htmlspecialchars((string)($_ehEstado['label']??'—'),ENT_QUOTES,'UTF-8') ?></strong>.</p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div></div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    var tiposInfo=<?= json_encode($tiposHabitacion,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    var pisosInfo=<?= json_encode($pisosHabitacion,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    var rangosP=<?= json_encode($rangosHabitacion,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK) ?>;
    var amenI=<?= json_encode($amenidadesHabitacion,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
    var numInput=document.getElementById('input-numero');
    var tipoSel=document.getElementById('input-tipo');
    var pisoInputs=document.querySelectorAll('input[name="piso"]');
    var precInput=document.getElementById('input-precio');
    var amCbs=document.querySelectorAll('input[name="caracteristicas_especiales[]"]');
    var pvHeroNum=document.getElementById('preview-numero');
    var pvHeroTipo=document.getElementById('preview-tipo');
    var pvHeroPiso=document.getElementById('preview-piso');
    var pvKcNum=document.getElementById('preview-numero-kc');
    var pvKcTipo=document.getElementById('preview-tipo-kc');
    var pvKcPiso=document.getElementById('preview-piso-kc');
    var pvPrecio=document.getElementById('preview-precio');
    var pvBadges=document.getElementById('preview-badges');
    var rangoEl=document.getElementById('rango-precio');
    function updatePreview(){
        var num=numInput.value||'—';
        var tipo=tipoSel.value;
        var pisoEl=document.querySelector('input[name="piso"]:checked');
        var pisoLbl=pisoEl?(pisosInfo[pisoEl.value]||pisoEl.value):'—';
        var tipoLbl=tiposInfo[tipo]||tipo;
        var precio=window.MedisoftMoneyInput?window.MedisoftMoneyInput.read(precInput):(parseFloat(String(precInput.value).replace(/,/g,''))||0);
        if(pvHeroNum)pvHeroNum.textContent=num;
        if(pvHeroTipo)pvHeroTipo.textContent=tipoLbl;
        if(pvHeroPiso)pvHeroPiso.textContent=pisoLbl;
        if(pvKcNum)pvKcNum.textContent=num;
        if(pvKcTipo)pvKcTipo.textContent=tipoLbl;
        if(pvKcPiso)pvKcPiso.textContent=pisoLbl;
        if(pvPrecio)pvPrecio.textContent='$'+precio.toLocaleString('es-MX');
        updateBadges(tipo);updateRango(tipo);updateJacuzzi(tipo);
    }
    function updateBadges(tipo){
        if(!pvBadges)return;pvBadges.innerHTML='';
        var sel=[];
        amCbs.forEach(function(cb){if(cb.checked&&!cb.disabled)sel.push(amenI[cb.value]||cb.value);});
        if(tipo==='doble_jacuzzi'||tipo==='sencilla_jacuzzi'){if(sel.indexOf('Jacuzzi')<0)sel.unshift('Jacuzzi');}
        sel.forEach(function(s){var sp=document.createElement('span');sp.className='s-keycard-feat';sp.textContent=s;pvBadges.appendChild(sp);});
    }
    function updateRango(tipo){
        if(!rangoEl)return;
        var r=rangosP[tipo];
        rangoEl.textContent=r&&tiposInfo[tipo]?'— sugerido: $'+Number(r.min).toLocaleString()+'–$'+Number(r.max).toLocaleString():'';
    }
    function updateJacuzzi(tipo){
        var cb=document.querySelector('input[name="caracteristicas_especiales[]"][value="jacuzzi"]');
        if(!cb)return;var chip=cb.nextElementSibling;
        var isJ=tipo==='doble_jacuzzi'||tipo==='sencilla_jacuzzi';
        cb.disabled=isJ;if(isJ)cb.checked=true;
        if(chip)chip.style.opacity=isJ?'.52':'1';
    }
    numInput.addEventListener('input',updatePreview);
    tipoSel.addEventListener('change',updatePreview);
    pisoInputs.forEach(function(i){i.addEventListener('change',updatePreview);});
    precInput.addEventListener('input',updatePreview);
    amCbs.forEach(function(cb){cb.addEventListener('change',updatePreview);});
    precInput.addEventListener('blur',function(){var v=parseFloat(this.value);if(v){this.value=Math.round(v/50)*50;updatePreview();}});
    updatePreview();
});
var deleteRoomBlocked=<?= $hayReservacionesBloqueantes?'true':'false' ?>;
var deleteRoomNumber=<?= json_encode($habitacionNumeroEliminacion,JSON_UNESCAPED_UNICODE) ?>;
function confirmarEliminacion(){
    if(deleteRoomBlocked)return;
    var submit=function(){var f=document.createElement('form');f.method='POST';f.action='<?= url('habitaciones/'.$habitacion['id'].'/delete') ?>';var c=document.createElement('input');c.type='hidden';c.name='csrf_token';c.value='<?= csrf_token() ?>';f.appendChild(c);document.body.appendChild(f);f.submit();};
    if(typeof Swal!=='undefined'){Swal.fire({icon:'warning',title:'Eliminar hab. '+(deleteRoomNumber||''),html:'Esta acción la quitará del listado. El historial se conserva.',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#DC2626',reverseButtons:true}).then(function(r){if(r.isConfirmed)submit();});}
    else if(confirm('Eliminar habitación '+(deleteRoomNumber||'')+'?'))submit();
}
document.addEventListener('DOMContentLoaded',function(){
    var inp=document.getElementById('fotos-input');
    var drop=document.getElementById('drop-zone');
    var pCont=document.getElementById('preview-container');
    var pGrid=document.getElementById('preview-grid');
    var alertEl=document.getElementById('upload-alert');
    if(!inp)return;
    var files=[],maxF=<?= 10-$totalImagenes ?>,maxS=5242880,ok=['image/jpeg','image/png','image/gif','image/webp'];
    function showErr(m){if(!alertEl)return;alertEl.querySelector('span').textContent=m;alertEl.hidden=false;alertEl.classList.add('is-visible');}
    function clrErr(){if(!alertEl)return;alertEl.hidden=true;alertEl.classList.remove('is-visible');}
    function render(){if(!pGrid||!pCont)return;pGrid.innerHTML='';if(!files.length){pCont.style.display='none';return;}pCont.style.display='';files.forEach(function(f,i){var rd=new FileReader();rd.onload=function(e){var d=document.createElement('div');d.style.cssText='position:relative;border-radius:9px;overflow:hidden;aspect-ratio:4/3';d.innerHTML='<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;display:block"><button type="button" onclick="window.__rmImg('+i+')" style="position:absolute;top:4px;right:4px;background:#DC2626;color:#fff;border:none;border-radius:6px;width:22px;height:22px;cursor:pointer;font-size:.62rem">x</button>';pGrid.appendChild(d);};rd.readAsDataURL(f);});}
    function sync(){var dt=new DataTransfer();files.forEach(function(f){dt.items.add(f);});inp.files=dt.files;}
    function handle(fs){clrErr();var a=Array.from(fs);if(a.length>maxF){showErr('Max '+maxF+' imagen'+(maxF>1?'es':'')+' mas.');return;}files=[];for(var j=0;j<a.length;j++){var f=a[j];if(ok.indexOf(f.type)<0){showErr(f.name+': tipo no valido');continue;}if(f.size>maxS){showErr(f.name+': supera 5 MB');continue;}files.push(f);}render();sync();}
    window.__rmImg=function(i){files.splice(i,1);render();sync();};
    inp.addEventListener('change',function(e){handle(e.target.files);});
    if(drop){['dragenter','dragover','dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();e.stopPropagation();});});['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(){drop.classList.add('drag-over');});});['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(){drop.classList.remove('drag-over');});});drop.addEventListener('drop',function(e){handle(e.dataTransfer.files);});}
});
</script>
