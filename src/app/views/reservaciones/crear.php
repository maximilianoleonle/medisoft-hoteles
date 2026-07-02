<?php

date_default_timezone_set('America/Mexico_City');
/**
 * Vista de Crear Reservación - CON SELECCIÓN MANUAL DE CORTESÍAS
 * Paleta hotelera boutique
 */

$habitacion_preseleccionada = $_GET['habitacion_id'] ?? null;
$fecha_entrada_pre = $_GET['fecha_entrada'] ?? null;
$fecha_salida_pre = $_GET['fecha_salida'] ?? null;
$hora_llegada_pre = $_GET['hora_llegada'] ?? null;
$es_preseleccion = $_GET['preseleccion'] ?? null;
$reservacionOldInput = is_array($_SESSION['old_input'] ?? null) ? $_SESSION['old_input'] : [];
$reservacionTieneOldInput = !empty($reservacionOldInput);
$oldHabitacionesReservacion = array_values(array_unique(array_map('strval', (array)($reservacionOldInput['habitaciones'] ?? []))));
$oldCortesiasReservacion = array_values(array_unique(array_map('strval', (array)($reservacionOldInput['cortesias'] ?? []))));

$tiposHabitacionReservacion = [
    'sencilla' => 'Sencilla',
    'doble' => 'Doble',
    'triple' => 'Triple',
    'cuadruple' => 'Cuadruple',
    'doble_jacuzzi' => 'Doble con Jacuzzi',
    'sencilla_jacuzzi' => 'Sencilla con Jacuzzi',
];
if (function_exists('hotel_room_catalog_types_for_select')) {
    try {
        $catalogoTiposReservacion = hotel_room_catalog_types_for_select();
        if (!empty($catalogoTiposReservacion)) {
            $tiposHabitacionReservacion = $catalogoTiposReservacion;
        }
    } catch (Throwable $e) {
        error_log('No se pudo cargar catalogo de tipos en nueva reservacion: ' . $e->getMessage());
    }
} elseif (function_exists('hotel_room_catalog_types')) {
    try {
        $catalogoTiposReservacion = hotel_room_catalog_types();
        if (!empty($catalogoTiposReservacion)) {
            $tiposHabitacionReservacion = $catalogoTiposReservacion;
        }
    } catch (Throwable $e) {
        error_log('No se pudo cargar catalogo de tipos en nueva reservacion: ' . $e->getMessage());
    }
}

$horaLlegadaModoPre = old('hora_llegada_modo', $hora_llegada_pre ? 'manual' : 'despues');
$horaLlegadaModoPre = in_array($horaLlegadaModoPre, ['manual', 'ahora', 'despues'], true) ? $horaLlegadaModoPre : 'manual';
?>

<style>
/* ══════════════════════════════════════════
   Nueva Reservación
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:       #5C7A4E;
    --lc-green-dark:  #4A6340;
    --lc-green-deep:  #3D5234;
    --lc-green-light: #7A9B6A;
    --lc-gold:        #C8A96A;
    --lc-gold-dark:   #B8994A;
    --lc-cream:       #F7F4EE;
    --lc-cream-mid:   #EEE9DE;

    /* Backward-compat aliases used by SweetAlert confirmButtonColor calls */
    --hotel-brown:      #5C7A4E;
    --hotel-brown-dark: #4A6340;
    --hotel-gold:       #C8A96A;
    --hotel-cream:      #F7F4EE;

    --color-ocupada:       #EF4444;
    --color-ocupada-light: #FEE2E2;
}

/* ── Page ────────────────────────────────── */
.vista-reservacion {
    opacity: 0;
    transition: opacity 0.4s ease;
    background: linear-gradient(145deg, #EFF4EC 0%, #E8EEE3 50%, #F4F1EC 100%);
    min-height: 100vh;
}
.vista-reservacion.loaded { opacity: 1; }

/* ── Card hover ──────────────────────────── */
.card-animate { transition: all 0.3s ease; will-change: transform; }
.card-animate:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(61,82,52,.1); }

/* ── Scrollbars ──────────────────────────── */
.resumen-scroll {
    max-height: 400px;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: 8px;
    margin-right: -8px;
    scrollbar-width: thin;
    scrollbar-color: #A8C4A0 #F0F5ED;
}
.resumen-scroll::-webkit-scrollbar { width: 4px; }
.resumen-scroll::-webkit-scrollbar-track { background: #F0F5ED; border-radius: 4px; }
.resumen-scroll::-webkit-scrollbar-thumb { background: #A8C4A0; border-radius: 4px; }
.resumen-scroll::-webkit-scrollbar-thumb:hover { background: var(--lc-green); }

/* Scroll fade indicator */
.scroll-indicator {
    position: absolute; bottom: 0; left: 0; right: 0; height: 40px;
    background: linear-gradient(to top, rgba(255,255,255,1) 0%, rgba(255,255,255,0) 100%);
    pointer-events: none; transition: opacity .3s ease;
}
.scroll-indicator.hidden { opacity: 0; }

/* ── Room cards base ─────────────────────── */
.habitacion-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    position: relative;
}
.habitacion-card.disponible:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(92,122,78,.15);
}

/* OCUPADA */
.habitacion-card.ocupada { cursor: not-allowed !important; opacity: 0.95; }
.habitacion-card.ocupada > div {
    background: linear-gradient(135deg, var(--color-ocupada-light) 0%, #FECACA 100%) !important;
    border: 2px solid var(--color-ocupada) !important;
    position: relative;
}

/* Occupation info box */
.info-ocupacion {
    background: white;
    border: 1px solid var(--color-ocupada);
    border-radius: 6px;
    padding: 8px;
    margin-top: 8px;
    font-size: 11px;
}
.info-ocupacion .huesped-nombre { font-weight: 600; color: #1F2937; margin-bottom: 2px; }
.info-ocupacion .fechas          { color: #6B7280; font-size: 10px; }

/* SELECTED — Green glow */
.habitacion-card.selected > div {
    border-color: #059669 !important;
    border-width: 4px !important;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%) !important;
    box-shadow:
        0 0 0 6px rgba(5,150,105,.15),
        0 16px 40px rgba(5,150,105,.25),
        inset 0 2px 0 rgba(255,255,255,.8) !important;
    transform: translateY(-6px) scale(1.03) !important;
    position: relative; z-index: 10;
}

/* CORTESÍA — Amber glow */
.habitacion-card.es-cortesia > div {
    border-color: #D97706 !important;
    border-width: 4px !important;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%) !important;
    box-shadow:
        0 0 0 6px rgba(217,119,6,.15),
        0 16px 40px rgba(217,119,6,.25) !important;
}

/* Courtesy badge */
.badge-cortesia {
    position: absolute; top: 8px; right: 8px; z-index: 20;
    background: linear-gradient(135deg, #D97706 0%, #B45309 100%);
    color: white; padding: 6px 12px; border-radius: 8px;
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    display: flex; align-items: center; gap: 6px;
    box-shadow: 0 4px 12px rgba(217,119,6,.4);
}

/* ── Search box ──────────────────────────── */
.buscador-habitaciones {
    background: #F0F5ED;
    border: 1px solid #D5E4CB;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 20px;
    position: sticky; top: 10px; z-index: 20;
}
.input-busqueda {
    width: 100%;
    padding: 10px 40px 10px 12px;
    border: 2px solid #C8D9BE;
    border-radius: 8px;
    font-size: 14px;
    background: #FAFDF8;
    color: #374151;
    transition: all 0.3s ease;
}
.input-busqueda:focus {
    border-color: var(--lc-gold);
    outline: none;
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
    background: white;
}
.icono-busqueda {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF; pointer-events: none;
}

/* ── Selection pulse ─────────────────────── */
@keyframes pulse-selection {
    0%   { box-shadow: 0 0 0 0 rgba(5,150,105,.6); }
    70%  { box-shadow: 0 0 0 20px rgba(5,150,105,0); }
    100% { box-shadow: 0 0 0 0 rgba(5,150,105,0); }
}
.habitacion-card.pulse-selection { animation: pulse-selection 1s ease-out; }

/* ── Selection counter ───────────────────── */
.contador-habitaciones {
    background: linear-gradient(135deg, #5C7A4E 0%, #4A6340 100%);
    color: white;
    padding: 4px 12px; border-radius: 20px;
    font-weight: 700; min-width: 32px; text-align: center;
    box-shadow: 0 2px 8px rgba(92,122,78,.3);
    transition: all 0.3s ease;
}
.contador-habitaciones.animate { transform: scale(1.2); }

/* ── Courtesy section ────────────────────── */
.seccion-cortesias {
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
    border: 2px solid #D97706;
    border-radius: 12px;
    padding: 16px;
    margin-top: 20px;
}
.titulo-cortesias {
    display: flex; align-items: center; gap: 8px;
    font-size: 16px; font-weight: 700; color: #92400E; margin-bottom: 12px;
}
.lista-cortesias { display: grid; gap: 8px; }
.item-cortesia {
    background: white; border: 2px solid #FDE68A;
    border-radius: 8px; padding: 12px;
    display: flex; align-items: center; justify-content: space-between;
    transition: all 0.3s ease;
}
.item-cortesia:hover       { border-color: #D97706; box-shadow: 0 4px 12px rgba(217,119,6,.2); }
.item-cortesia.activa      { border-color: #D97706; background: #FFFBEB; }
.checkbox-cortesia { width: 20px; height: 20px; cursor: pointer; accent-color: #D97706; }

/* ── Mobile floating summary ─────────────── */
.resumen-flotante {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: white;
    border-top: 2px solid var(--lc-green);
    box-shadow: 0 -4px 20px rgba(61,82,52,.12);
    z-index: 40;
    transform: translateY(100%);
    transition: transform 0.3s ease;
}
.resumen-flotante.activo { transform: translateY(0); }
@media (min-width: 1280px) { .resumen-flotante { display: none; } }

/* ── Barra slim tipo checkout (fila total + Guardar) ── */
.resumen-flotante { padding-bottom: env(safe-area-inset-bottom, 0px); }
.rf-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
}
.rf-info {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 6px 2px;
    border: 0;
    background: transparent;
    text-align: left;
    cursor: pointer;
}
.rf-info-text { display: flex; flex-direction: column; min-width: 0; line-height: 1.1; }
.rf-info-text strong { font-size: 1.18rem; font-weight: 800; color: var(--lc-green); font-variant-numeric: tabular-nums; }
.rf-info-text small { margin-top: 2px; font-size: .72rem; font-weight: 600; color: #6B7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rf-caret { flex: 0 0 auto; color: #9CA3AF; font-size: .8rem; transition: transform .25s ease; }
.resumen-flotante.expanded .rf-caret { transform: rotate(180deg); }
.rf-save {
    flex: 0 0 auto;
    width: auto !important;
    min-width: 138px;
    margin: 0 !important;
}
.rf-detail {
    max-height: 0;
    overflow: hidden;
    padding: 0 14px;
    transition: max-height .3s ease, padding .25s ease;
}
.resumen-flotante.expanded .rf-detail {
    max-height: 48vh;
    overflow-y: auto;
    padding: 13px 14px 2px;
    border-bottom: 1px solid #EAF0E5;
}
.rf-cotizacion { margin-top: 10px !important; }

/* ── Panel headers ───────────────────────── */
.panel-hd-guest   { background: linear-gradient(135deg, #5C7A4E, #4A6340); }
.panel-hd-dates   { background: linear-gradient(135deg, #4A6340, #3D5234); }
.panel-hd-rooms   { background: linear-gradient(135deg, #3D5234, #4A6340); }
.panel-hd-notes   { background: linear-gradient(135deg, var(--lc-gold-dark), #C8A96A); }
.panel-hd-summary { background: linear-gradient(135deg, #3D5234, #5C7A4E); }

/* ── Form inputs ─────────────────────────── */
.lc-input {
    border: 1.5px solid #C8D9BE;
    border-radius: 8px;
    padding: 8px 12px;
    width: 100%;
    background: #FAFDF8;
    color: #374151;
    transition: border-color .2s, box-shadow .2s;
}
.lc-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
}

/* ── Back button ─────────────────────────── */
.btn-back {
    background: rgba(255,255,255,.15);
    color: white;
    border: 1px solid rgba(255,255,255,.25);
    padding: 7px 14px; border-radius: 9px;
    font-size: .82rem; font-weight: 600;
    display: inline-flex; align-items: center; gap: 6px;
    transition: background .2s;
    text-decoration: none;
}
.btn-back:hover { background: rgba(255,255,255,.25); color: white; }

/* ── Guest nuevo button ──────────────────── */
.btn-gold {
    display: inline-flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, var(--lc-gold), var(--lc-gold-dark));
    color: #3D5234; padding: 8px 14px; border-radius: 8px;
    font-size: .82rem; font-weight: 700;
    text-decoration: none;
    transition: box-shadow .2s, transform .2s;
    box-shadow: 0 3px 10px rgba(200,169,106,.3);
    white-space: nowrap;
}
.btn-gold:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(200,169,106,.4); color: #3D5234; }

/* ── Hour button ─────────────────────────── */
.btn-hour {
    padding: 8px 12px; border-radius: 8px;
    background: rgba(92,122,78,.1); color: var(--lc-green);
    border: 1.5px solid #C8D9BE;
    transition: background .15s; cursor: pointer;
}
.btn-hour:hover { background: rgba(92,122,78,.18); }

/* ── Guest selected info box ─────────────── */
.guest-info-box {
    background: linear-gradient(135deg, rgba(92,122,78,.07), rgba(92,122,78,.03));
    border: 1.5px solid #C8D9BE;
    border-radius: 10px; padding: 14px;
}

/* ── Preselection alert ──────────────────── */
.alert-presel {
    background: linear-gradient(135deg, rgba(92,122,78,.08), rgba(92,122,78,.04));
    border: 1.5px solid #C8D9BE;
    border-left: 4px solid var(--lc-green);
    border-radius: 0 10px 10px 0;
    padding: 12px 14px;
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 16px;
}

/* ── Info sidebar card ───────────────────── */
.info-card {
    background: var(--lc-cream);
    border: 1.5px solid rgba(200,169,106,.3);
    border-radius: 14px; padding: 16px;
}

/* ── Save button ─────────────────────────── */
.btn-save {
    width: 100%; padding: 12px 16px; border-radius: 10px;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white; font-weight: 700; font-size: .9rem;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: box-shadow .2s, transform .2s;
    box-shadow: 0 4px 12px rgba(92,122,78,.25);
    border: none; cursor: pointer;
}
.btn-save:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(92,122,78,.35); }
.btn-save:disabled { background: #D1D5DB; color: #9CA3AF; cursor: not-allowed; box-shadow: none; }

/* ── Cancel button ───────────────────────── */
.btn-cancel {
    width: 100%; padding: 9px 16px; border-radius: 9px;
    background: rgba(92,122,78,.07);
    color: #4A6340; font-weight: 600; font-size: .85rem;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background .15s;
    text-decoration: none; border: 1.5px solid #D5E4CB;
}
.btn-cancel:hover { background: rgba(92,122,78,.14); color: #3D5234; }
/* ── Quotation button ───────────────────── */
.btn-cotizacion {
    width: 100%; padding: 10px 16px; border-radius: 9px;
    background: linear-gradient(135deg, var(--lc-gold), var(--lc-gold-dark));
    color: #3D5234; font-weight: 700; font-size: .85rem;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    transition: box-shadow .2s, transform .2s;
    box-shadow: 0 3px 10px rgba(200,169,106,.3);
    border: none; cursor: pointer;
}
.btn-cotizacion:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(200,169,106,.4);
    color: #3D5234;
}
.btn-cotizacion:disabled {
    background: #E5E7EB; color: #9CA3AF;
    cursor: not-allowed; box-shadow: none;
}
/* ── Room stats bar ──────────────────────── */
.room-stats-bar {
    background: linear-gradient(135deg, rgba(92,122,78,.07), rgba(92,122,78,.03));
    border: 1px solid #D5E4CB;
    border-radius: 10px; padding: 10px 14px;
    margin-bottom: 16px;
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
}

/* ── Summary total box ───────────────────── */
.total-box {
    background: var(--lc-cream);
    border: 1.5px solid rgba(200,169,106,.35);
    border-radius: 10px; padding: 12px 14px;
}

/* ── Select2 override ────────────────────── */
.select2-container--default .select2-selection--single {
    border: 1.5px solid #C8D9BE !important;
    border-radius: 8px !important;
    height: 38px !important;
    background: #FAFDF8 !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    color: #374151 !important;
    padding-left: 10px !important;
}
.select2-container--default .select2-selection--single:focus,
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--lc-gold) !important;
    box-shadow: 0 0 0 3px rgba(200,169,106,.15) !important;
    outline: none !important;
}
.select2-dropdown {
    border: 1.5px solid #C8D9BE !important;
    border-radius: 8px !important;
    box-shadow: 0 8px 24px rgba(61,82,52,.12) !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--lc-green) !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field {
    border: 1.5px solid #C8D9BE !important;
    border-radius: 6px !important;
    padding: 6px 10px !important;
}
.select2-container--default .select2-search--dropdown .select2-search__field:focus {
    border-color: var(--lc-gold) !important;
    outline: none !important;
}
</style>

<style id="reservation-create-boutique">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.vista-reservacion {
    --rc-brand: var(--brand-primary, #1B2746);
    --rc-brand-2: var(--brand-secondary, #0F172A);
    --rc-accent: var(--brand-accent, #BD9441);
    --rc-accent-dark: color-mix(in srgb, var(--rc-accent) 72%, #3F2E12);
    --rc-accent-soft: color-mix(in srgb, var(--rc-accent) 12%, #FFFFFF);
    --rc-accent-line: color-mix(in srgb, var(--rc-accent) 30%, #E8DDCA);
    --rc-ivory: color-mix(in srgb, var(--rc-accent) 8%, #F8F5ED);
    --rc-ivory-2: color-mix(in srgb, var(--rc-accent) 5%, #FCFAF5);
    --rc-surface: color-mix(in srgb, var(--rc-accent) 2%, #FFFFFF);
    --rc-surface-warm: color-mix(in srgb, var(--rc-accent) 5%, #FFFFFF);
    --rc-line: color-mix(in srgb, var(--rc-accent) 19%, #E7DEC9);
    --rc-line-soft: color-mix(in srgb, var(--rc-accent) 11%, #F0ECE2);
    --rc-muted: color-mix(in srgb, var(--rc-brand-2) 48%, #94A3B8);
    --rc-text: var(--rc-brand-2);
    --rc-success: #1E9E63;
    --rc-success-soft: #E7F4EC;
    --rc-warning: #C47B18;
    --rc-warning-soft: #FFF6E5;
    --rc-danger: #D64539;
    --rc-danger-soft: #FBE9E7;
    --rc-info: #2F77E0;
    --rc-info-soft: #E6EFFC;
    --rc-serif: 'Cormorant Garamond', Georgia, serif;
    --rc-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    background:
        repeating-linear-gradient(135deg, color-mix(in srgb, var(--rc-accent) 3%, transparent) 0 1px, transparent 1px 22px),
        linear-gradient(180deg, var(--rc-ivory-2), var(--rc-ivory) 58%, #F7F2EA);
    color: var(--rc-text);
    font-family: var(--rc-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
}

.vista-reservacion *,
.vista-reservacion *::before,
.vista-reservacion *::after {
    box-sizing: border-box;
}

.vista-reservacion :where(p, span, a, button, input, select, textarea, label) {
    font-family: var(--rc-sans);
}

.vista-reservacion > div:first-of-type {
    background: transparent !important;
    overflow: visible !important;
}

.vista-reservacion > div:first-of-type > div[style*="position:absolute"] {
    display: none !important;
}

.vista-reservacion > div:first-of-type > .px-5,
.vista-reservacion > .px-5 {
    width: min(100%, 1280px);
    margin: 0 auto;
}

.vista-reservacion > div:first-of-type > .px-5 {
    padding: 26px 18px 0 !important;
}

.vista-reservacion > .px-5 {
    padding: 18px 18px 112px !important;
}

.vista-reservacion > .pt-4 {
    width: min(100%, 1280px);
    margin: 0 auto;
    padding: 14px 18px 0 !important;
}

.vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 {
    display: inline-flex !important;
    align-items: center !important;
    gap: 9px !important;
    color: var(--rc-muted) !important;
    font-size: .78rem !important;
    font-weight: 700 !important;
    margin-bottom: 18px !important;
}

.vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 a {
    color: #111827 !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 7px !important;
    transition: color .18s ease, transform .18s ease !important;
}

.vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 a:hover {
    color: #111827 !important;
    transform: translateY(-1px);
}

.vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 span,
.vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 i {
    color: var(--rc-muted) !important;
}

.vista-reservacion > div:first-of-type .flex.items-center.justify-between.gap-3 {
    align-items: flex-start !important;
    gap: 18px !important;
}

.vista-reservacion > div:first-of-type .flex.items-center.gap-3 {
    align-items: flex-start !important;
    min-width: 0;
}

.vista-reservacion > div:first-of-type .flex.items-center.gap-3 > div[style*="width:46px"] {
    width: 48px !important;
    height: 48px !important;
    border-radius: 14px !important;
    background: linear-gradient(150deg, var(--rc-brand), var(--rc-brand-2)) !important;
    color: #FFFFFF !important;
    box-shadow: 0 12px 24px -10px color-mix(in srgb, var(--rc-brand) 58%, transparent);
}

.vista-reservacion > div:first-of-type h1 {
    color: #111827 !important;
    font-family: var(--rc-serif) !important;
    font-size: clamp(2rem, 3.6vw, 2.75rem) !important;
    font-weight: 650 !important;
    letter-spacing: 0 !important;
    line-height: .96 !important;
    text-wrap: balance;
}

.vista-reservacion > div:first-of-type h1 .rc-title-accent {
    color: var(--rc-brand) !important;
}

.vista-reservacion > div:first-of-type h1 + p {
    max-width: 62ch;
    margin: 8px 0 0 !important;
    color: var(--rc-muted) !important;
    font-size: .88rem !important;
    font-weight: 600 !important;
    line-height: 1.55 !important;
}

.vista-reservacion .btn-back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 38px;
    padding: 8px 12px;
    border: 1px solid var(--rc-accent-line);
    border-radius: 999px;
    background: var(--rc-accent-soft);
    color: var(--rc-accent-dark);
    font-size: .76rem;
    font-weight: 800;
    text-decoration: none;
    white-space: nowrap;
    transition: transform .18s ease, background .18s ease, border-color .18s ease;
}

.vista-reservacion .btn-back:hover {
    color: var(--rc-accent-dark);
    background: color-mix(in srgb, var(--rc-accent) 17%, #FFFFFF);
    transform: translateY(-1px);
}

.vista-reservacion .alert-presel {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin: 0;
    padding: 13px 15px;
    border: 1px solid color-mix(in srgb, var(--rc-info) 22%, var(--rc-line)) !important;
    border-left-width: 1px !important;
    border-radius: 15px !important;
    background: color-mix(in srgb, var(--rc-info) 8%, var(--rc-surface)) !important;
    color: #111827;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--rc-brand-2) 4%, transparent);
}

.vista-reservacion .alert-presel i {
    color: var(--rc-info) !important;
    margin-top: 2px;
}

.vista-reservacion #formReservacion > .grid {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) minmax(286px, 318px) !important;
    gap: 18px !important;
    align-items: start;
}

.vista-reservacion #formReservacion > .grid > .xl\:col-span-3,
.vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
    grid-column: auto !important;
    min-width: 0;
}

.vista-reservacion #formReservacion > .grid > .xl\:col-span-3 {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.vista-reservacion .card-animate,
.vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white,
.vista-reservacion .info-card {
    border: 1px solid var(--rc-line) !important;
    border-radius: 18px !important;
    background: var(--rc-surface) !important;
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--rc-brand-2) 4%, transparent),
        0 14px 32px -24px color-mix(in srgb, var(--rc-brand-2) 34%, transparent) !important;
    overflow: hidden;
    min-width: 0;
}

.vista-reservacion .card-animate:hover,
.vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white:hover,
.vista-reservacion .info-card:hover {
    transform: translateY(-1px);
    box-shadow:
        0 2px 4px color-mix(in srgb, var(--rc-brand-2) 5%, transparent),
        0 18px 38px -26px color-mix(in srgb, var(--rc-brand-2) 38%, transparent) !important;
}

.vista-reservacion .panel-hd-guest,
.vista-reservacion .panel-hd-dates,
.vista-reservacion .panel-hd-rooms,
.vista-reservacion .panel-hd-notes,
.vista-reservacion .panel-hd-summary {
    min-height: 62px;
    padding: 15px 17px !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--rc-accent) 7%, #FFFFFF), color-mix(in srgb, var(--rc-accent) 3%, #FFFFFF)) !important;
    border-bottom: 1px solid var(--rc-line-soft);
}

.vista-reservacion .panel-hd-guest h2,
.vista-reservacion .panel-hd-dates h2,
.vista-reservacion .panel-hd-rooms h2,
.vista-reservacion .panel-hd-notes h2,
.vista-reservacion .panel-hd-summary h3 {
    color: #111827 !important;
    font-size: .95rem !important;
    font-weight: 850 !important;
    letter-spacing: 0 !important;
    line-height: 1.2;
}

.vista-reservacion .panel-hd-guest h2 i,
.vista-reservacion .panel-hd-dates h2 i,
.vista-reservacion .panel-hd-rooms h2 i,
.vista-reservacion .panel-hd-notes h2 i,
.vista-reservacion .panel-hd-summary h3 i {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    background: var(--rc-accent-soft);
    color: var(--rc-accent-dark) !important;
    opacity: 1 !important;
    font-size: .82rem;
}

.vista-reservacion .card-animate > .p-5,
.vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white .p-4 {
    padding: 18px !important;
}

.vista-reservacion label {
    color: #111827 !important;
    font-size: .77rem !important;
    font-weight: 850 !important;
    letter-spacing: .04em;
    line-height: 1.2;
    text-transform: uppercase;
}

.vista-reservacion label i {
    color: var(--rc-accent-dark) !important;
}

.vista-reservacion .res-form-error {
    display: block;
    margin-top: 0.45rem;
    color: #B42318;
    font-size: 0.78rem;
    font-weight: 800;
    line-height: 1.35;
}

.vista-reservacion .lc-input,
.vista-reservacion .input-busqueda {
    width: 100% !important;
    min-height: 44px !important;
    border: 1px solid var(--rc-line) !important;
    border-radius: 13px !important;
    background: var(--rc-surface-warm) !important;
    color: var(--rc-text) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.75) inset !important;
    font-size: .9rem !important;
    font-weight: 650 !important;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease !important;
}

.vista-reservacion .lc-input:focus,
.vista-reservacion .input-busqueda:focus {
    border-color: var(--rc-accent) !important;
    background: #FFFFFF !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-accent) 24%, transparent) !important;
    outline: none !important;
}

.vista-reservacion textarea.lc-input {
    min-height: 104px !important;
    line-height: 1.55;
}

.vista-reservacion .select2-container {
    width: 100% !important;
}

.vista-reservacion .select2-container--default .select2-selection--single {
    min-height: 44px !important;
    height: 44px !important;
    border: 1px solid var(--rc-line) !important;
    border-radius: 13px !important;
    background: var(--rc-surface-warm) !important;
}

.vista-reservacion .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--rc-text) !important;
    line-height: 42px !important;
    padding-left: 13px !important;
    padding-right: 34px !important;
    font-size: .9rem;
    font-weight: 650;
}

.vista-reservacion .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 42px !important;
    right: 8px !important;
}

.vista-reservacion .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--rc-accent) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-accent) 24%, transparent) !important;
}

.select2-dropdown {
    border: 1px solid var(--brand-accent, #BD9441) !important;
    border-radius: 13px !important;
    overflow: hidden;
    box-shadow: 0 18px 38px -22px rgba(15,23,42,.35) !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background: var(--brand-primary, #1B2746) !important;
}

.vista-reservacion .res-guest-card {
    overflow: visible !important;
    position: relative;
    z-index: 20;
}

.vista-reservacion .res-guest-card .panel-hd-guest {
    border-radius: 1rem 1rem 0 0;
}

.vista-reservacion .res-guest-combobox-wrap {
    position: relative;
    min-width: 0;
}

.vista-reservacion .res-guest-native-select {
    position: absolute !important;
    left: 18px;
    bottom: 8px;
    width: 1px !important;
    height: 1px !important;
    opacity: 0;
    pointer-events: none;
}

.vista-reservacion .res-guest-combobox {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--rc-line);
    border-radius: 13px;
    background: var(--rc-surface-warm);
    color: var(--rc-text);
    box-shadow: 0 1px 0 rgba(255,255,255,.75) inset;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.vista-reservacion .res-guest-combobox:focus-within,
.vista-reservacion .res-guest-combobox.is-open {
    border-color: var(--rc-accent);
    background: #FFFFFF;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-accent) 24%, transparent);
}

.vista-reservacion .res-guest-search-icon {
    flex: 0 0 auto;
    margin-left: 14px;
    color: color-mix(in srgb, var(--rc-brand) 58%, #9CA3AF);
    font-size: .78rem;
}

.vista-reservacion .res-guest-input {
    flex: 1;
    min-width: 0;
    height: 42px;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--rc-text);
    font-size: .9rem;
    font-weight: 700;
}

.vista-reservacion .res-guest-input::placeholder {
    color: #9AA3B2;
    font-weight: 700;
}

.vista-reservacion .res-guest-input::-webkit-search-cancel-button,
.vista-reservacion .res-guest-input::-webkit-search-decoration {
    -webkit-appearance: none;
    appearance: none;
}

.vista-reservacion .res-guest-clear {
    flex: 0 0 auto;
    width: 32px;
    height: 32px;
    margin-right: 6px;
    border: 0;
    border-radius: 10px;
    background: color-mix(in srgb, var(--rc-brand) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--rc-brand) 70%, #6B7280);
    transition: transform .18s ease, background .18s ease, color .18s ease;
}

.vista-reservacion .res-guest-clear:hover {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--rc-brand) 14%, #FFFFFF);
    color: var(--rc-brand);
}

.vista-reservacion .res-guest-results {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    z-index: 60;
    max-height: 292px;
    overflow-y: auto;
    padding: 7px;
    border: 1px solid var(--rc-accent-line);
    border-radius: 15px;
    background: rgba(255,255,255,.98);
    box-shadow: 0 22px 44px -26px rgba(15,23,42,.42), 0 1px 0 rgba(255,255,255,.86) inset;
    backdrop-filter: blur(10px);
}

.vista-reservacion .res-guest-results.hidden {
    display: none;
}

.vista-reservacion .res-guest-option,
.vista-reservacion .res-guest-state {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 11px;
    padding: 11px 12px;
    border: 0;
    border-radius: 11px;
    background: transparent;
    text-align: left;
}

.vista-reservacion .res-guest-option {
    cursor: pointer;
    color: var(--rc-text);
    transition: background .16s ease, transform .16s ease, color .16s ease;
}

.vista-reservacion .res-guest-option:hover,
.vista-reservacion .res-guest-option.is-active {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--rc-brand) 8%, #FFFFFF);
}

.vista-reservacion .res-guest-avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 34px;
    width: 34px;
    height: 34px;
    border-radius: 11px;
    background: linear-gradient(135deg, var(--rc-brand), color-mix(in srgb, var(--rc-brand) 74%, var(--rc-accent)));
    color: #FFFFFF;
    font-size: .78rem;
    font-weight: 900;
    box-shadow: 0 10px 18px -14px color-mix(in srgb, var(--rc-brand) 80%, transparent);
}

.vista-reservacion .res-guest-option-main {
    min-width: 0;
}

.vista-reservacion .res-guest-option-name {
    display: block;
    color: var(--rc-text);
    font-size: .88rem;
    font-weight: 850;
    line-height: 1.2;
}

.vista-reservacion .res-guest-option-meta {
    display: block;
    margin-top: 3px;
    color: var(--rc-muted);
    font-size: .76rem;
    font-weight: 650;
}

.vista-reservacion .res-guest-state {
    color: var(--rc-muted);
    font-size: .86rem;
    font-weight: 750;
}

.vista-reservacion .res-guest-state i {
    color: var(--rc-accent);
}

.vista-reservacion .res-guest-state.is-error i {
    color: #DC2626;
}

.vista-reservacion .btn-gold,
.vista-reservacion .btn-hour,
.vista-reservacion .btn-save,
.vista-reservacion .btn-cotizacion,
.vista-reservacion .btn-cancel {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 44px;
    border-radius: 13px;
    font-size: .84rem;
    font-weight: 850;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease, opacity .18s ease;
}

.vista-reservacion .btn-gold,
.vista-reservacion .btn-hour {
    border: 1px solid var(--rc-accent-line);
    background: var(--rc-accent-soft);
    color: var(--rc-accent-dark);
    padding: 10px 14px;
}

.vista-reservacion .btn-save {
    width: 100%;
    border: 1px solid color-mix(in srgb, var(--rc-accent) 32%, transparent);
    background: linear-gradient(135deg, var(--rc-brand), var(--rc-brand-2));
    color: #FFFFFF;
    box-shadow: 0 12px 26px -12px color-mix(in srgb, var(--rc-brand) 60%, transparent);
}

.vista-reservacion .btn-cotizacion {
    width: 100%;
    border: 1px solid var(--rc-accent-line);
    background: var(--rc-accent-soft);
    color: var(--rc-accent-dark);
}

.vista-reservacion .btn-cancel {
    width: 100%;
    border: 1px solid var(--rc-line);
    background: var(--rc-surface-warm);
    color: var(--rc-muted);
}

.vista-reservacion .btn-gold:hover,
.vista-reservacion .btn-hour:hover,
.vista-reservacion .btn-save:hover,
.vista-reservacion .btn-cotizacion:hover,
.vista-reservacion .btn-cancel:hover {
    transform: translateY(-1px);
}

.vista-reservacion .btn-save:disabled,
.vista-reservacion .btn-cotizacion:disabled {
    cursor: not-allowed;
    filter: saturate(.45);
    opacity: .58;
    transform: none;
}

.vista-reservacion .guest-info-box,
.vista-reservacion .info-card,
.vista-reservacion .seccion-cortesias {
    border: 1px solid var(--rc-line) !important;
    border-radius: 15px !important;
    background: var(--rc-surface-warm) !important;
    box-shadow: 0 1px 2px color-mix(in srgb, var(--rc-brand-2) 4%, transparent);
}

.vista-reservacion .guest-info-box {
    padding: 14px !important;
}

.vista-reservacion .guest-info-box p,
.vista-reservacion .info-card p,
.vista-reservacion .info-card span,
.vista-reservacion .info-card li {
    color: var(--rc-muted) !important;
}

.vista-reservacion .guest-info-box .font-bold,
.vista-reservacion .info-card h4 {
    color: #111827 !important;
}

.vista-reservacion .res-guest-selected {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 13px;
}

.vista-reservacion .res-guest-selected-avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: inline-grid;
    place-items: center;
    background: linear-gradient(135deg, var(--rc-brand), color-mix(in srgb, var(--rc-brand) 70%, var(--rc-accent)));
    color: #FFFFFF;
    font-weight: 900;
    box-shadow: 0 12px 22px -16px color-mix(in srgb, var(--rc-brand) 80%, transparent);
}

.vista-reservacion .res-guest-selected-main {
    min-width: 0;
}

.vista-reservacion .res-guest-selected-name {
    margin: 0;
    color: #111827 !important;
    font-size: 1rem;
    font-weight: 900;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.vista-reservacion .res-guest-selected-meta,
.vista-reservacion .res-guest-summary-grid {
    color: var(--rc-muted);
    font-size: .78rem;
    font-weight: 700;
}

.vista-reservacion .res-guest-summary-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 8px;
}

.vista-reservacion .res-guest-summary-grid span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 5px 9px;
    border: 1px solid var(--rc-line);
    border-radius: 999px;
    background: #FFFFFF;
    color: var(--rc-muted) !important;
}

.vista-reservacion .res-guest-summary-grid i {
    color: var(--rc-accent-dark);
}

.vista-reservacion .res-guest-change {
    min-height: 34px;
    padding: 7px 10px;
    border: 1px solid var(--rc-line);
    border-radius: 11px;
    background: #FFFFFF;
    color: var(--rc-brand);
    font-size: .75rem;
    font-weight: 850;
    text-decoration: none;
    white-space: nowrap;
}

.vista-reservacion .res-guest-change:hover {
    border-color: var(--rc-accent-line);
    color: var(--rc-brand);
    background: var(--rc-accent-soft);
}

.vista-reservacion .guest-info-box > .flex.items-start.justify-between {
    align-items: center;
    gap: 14px;
}

.vista-reservacion .guest-info-box > .flex.items-start.justify-between > div:first-child {
    min-width: 0;
}

.vista-reservacion .guest-info-box > .flex.items-start.justify-between > div:first-child > p:first-child {
    color: var(--rc-accent-dark) !important;
}

.vista-reservacion .guest-info-box > .flex.items-start.justify-between > div:first-child > p:nth-child(2) {
    color: #111827 !important;
    font-size: 1rem !important;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.vista-reservacion .guest-info-box > .flex.items-start.justify-between > a {
    min-height: 34px;
    padding: 7px 10px;
    border: 1px solid var(--rc-line);
    border-radius: 11px;
    background: #FFFFFF;
    color: var(--rc-brand) !important;
    white-space: nowrap;
    text-decoration: none;
}

.vista-reservacion .arrival-planner {
    display: grid;
    gap: 9px;
}

.vista-reservacion .arrival-mode-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 7px;
}

.vista-reservacion .arrival-mode-btn {
    min-height: 38px;
    padding: 7px 8px;
    border: 1px solid var(--rc-line);
    border-radius: 12px;
    background: var(--rc-surface-warm);
    color: var(--rc-muted);
    font-size: .72rem;
    font-weight: 850;
    transition: background .18s ease, border-color .18s ease, color .18s ease, transform .18s ease;
}

.vista-reservacion .arrival-mode-btn:hover,
.vista-reservacion .arrival-mode-btn.is-active {
    border-color: var(--rc-accent-line);
    background: var(--rc-accent-soft);
    color: var(--rc-accent-dark);
    transform: translateY(-1px);
}

.vista-reservacion .arrival-help {
    color: var(--rc-muted) !important;
    font-size: .76rem;
    font-weight: 700;
}

.vista-reservacion .room-type-group {
    border: 1px solid var(--rc-line);
    border-radius: 17px;
    background: color-mix(in srgb, var(--rc-accent) 3%, #FFFFFF);
    overflow: hidden;
}

.vista-reservacion .room-type-group + .room-type-group {
    margin-top: 14px;
}

.vista-reservacion .room-type-group-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 13px 15px;
    border-bottom: 1px solid var(--rc-line-soft);
    background: linear-gradient(180deg, color-mix(in srgb, var(--rc-accent) 7%, #FFFFFF), #FFFFFF);
}

.vista-reservacion .room-type-title {
    min-width: 0;
}

.vista-reservacion .room-type-title h4 {
    margin: 0;
    color: #111827;
    font-size: .95rem;
    font-weight: 900;
    line-height: 1.2;
}

.vista-reservacion .room-type-title p {
    margin: 3px 0 0;
    color: var(--rc-muted);
    font-size: .76rem;
    font-weight: 700;
}

.vista-reservacion .room-type-counts {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 6px;
}

.vista-reservacion .room-type-counts span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 25px;
    padding: 4px 8px;
    border-radius: 999px;
    font-size: .68rem;
    font-weight: 850;
    white-space: nowrap;
}

.vista-reservacion .room-type-body {
    padding: 14px;
}

.vista-reservacion .room-type-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

@media (max-width: 820px) {
    .vista-reservacion .room-type-grid,
    .vista-reservacion .arrival-mode-grid {
        grid-template-columns: 1fr;
    }

    .vista-reservacion .room-type-group-head,
    .vista-reservacion .res-guest-selected {
        align-items: flex-start;
    }

    .vista-reservacion .room-type-group-head {
        flex-direction: column;
    }
}

.vista-reservacion .buscador-habitaciones {
    position: sticky;
    top: 10px;
    z-index: 8;
    margin-bottom: 16px !important;
    padding: 14px !important;
    border: 1px solid var(--rc-line);
    border-radius: 16px;
    background: color-mix(in srgb, var(--rc-surface-warm) 88%, #FFFFFF);
    box-shadow: 0 10px 24px -22px color-mix(in srgb, var(--rc-brand-2) 42%, transparent);
}

.vista-reservacion .icono-busqueda {
    color: var(--rc-accent-dark) !important;
}

.vista-reservacion .room-stats-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 0 0 14px;
    padding: 12px 13px;
    border: 1px solid var(--rc-line);
    border-radius: 15px;
    background: var(--rc-surface-warm);
}

.vista-reservacion .contador-habitaciones {
    display: inline-grid;
    place-items: center;
    min-width: 26px;
    height: 26px;
    border-radius: 999px;
    background: var(--rc-brand);
    color: #FFFFFF !important;
    font-weight: 900;
}

.vista-reservacion .habitacion-card > div {
    border: 1px solid var(--rc-line) !important;
    border-radius: 15px !important;
    background: #FFFFFF !important;
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--rc-brand-2) 5%, transparent),
        0 12px 24px -24px color-mix(in srgb, var(--rc-brand-2) 36%, transparent);
}

.vista-reservacion .habitacion-card.disponible {
    cursor: pointer;
}

.vista-reservacion .habitacion-card.disponible > div {
    border-color: var(--rc-line) !important;
    background: #FFFFFF !important;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease, color .18s ease !important;
}

.vista-reservacion .habitacion-card.disponible:hover > div {
    border-color: color-mix(in srgb, var(--rc-success) 34%, var(--rc-line)) !important;
    background: #FFFFFF !important;
    box-shadow: 0 16px 30px -26px color-mix(in srgb, var(--rc-success) 45%, transparent);
    transform: translateY(-1px);
}

.vista-reservacion .habitacion-card.ocupada:not(.en-mantenimiento) > div {
    border-color: color-mix(in srgb, var(--rc-danger) 52%, var(--rc-line)) !important;
    background:
        linear-gradient(135deg,
            color-mix(in srgb, var(--rc-danger) 15%, #FFFFFF) 0%,
            color-mix(in srgb, var(--rc-danger) 9%, #FFFFFF) 56%,
            #FFFFFF 100%) !important;
    box-shadow:
        inset 0 0 0 1px color-mix(in srgb, var(--rc-danger) 16%, transparent),
        0 14px 28px -25px color-mix(in srgb, var(--rc-danger) 58%, transparent);
}

.vista-reservacion .habitacion-card.en-mantenimiento > div {
    border-color: color-mix(in srgb, var(--rc-warning) 54%, var(--rc-line)) !important;
    background:
        linear-gradient(135deg,
            color-mix(in srgb, var(--rc-warning) 18%, #FFFFFF) 0%,
            color-mix(in srgb, var(--rc-warning) 11%, #FFFFFF) 58%,
            #FFFFFF 100%) !important;
    box-shadow:
        inset 0 0 0 1px color-mix(in srgb, var(--rc-warning) 18%, transparent),
        0 14px 28px -25px color-mix(in srgb, var(--rc-warning) 60%, transparent);
}

.vista-reservacion .habitacion-card h5 {
    color: #111827 !important;
    letter-spacing: 0 !important;
}

.vista-reservacion .habitacion-card h5 i {
    color: var(--rc-accent-dark) !important;
}

.vista-reservacion .habitacion-card .w-4.h-4 {
    border-color: var(--rc-line) !important;
    background: var(--rc-surface-warm);
}

.vista-reservacion .habitacion-card.selected .w-4.h-4 {
    border-color: var(--rc-success) !important;
    background: var(--rc-success) !important;
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--rc-success) 16%, transparent);
}

.vista-reservacion .badge-cortesia {
    border-radius: 0 0 10px 10px !important;
    background: color-mix(in srgb, var(--rc-warning) 86%, #FFFFFF) !important;
    box-shadow: 0 8px 16px -14px color-mix(in srgb, var(--rc-warning) 70%, transparent);
}

.vista-reservacion .habitacion-card.es-cortesia > div {
    border-color: color-mix(in srgb, var(--rc-warning) 38%, var(--rc-line)) !important;
    background: color-mix(in srgb, var(--rc-warning) 5%, #FFFFFF) !important;
}

.vista-reservacion .info-ocupacion {
    border: 1px solid color-mix(in srgb, var(--rc-danger) 26%, var(--rc-line)) !important;
    border-radius: 12px !important;
    background: color-mix(in srgb, var(--rc-danger) 5%, #FFFFFF) !important;
}

.vista-reservacion .habitacion-card.selected > div,
.vista-reservacion .habitacion-card.selected.es-cortesia > div {
    border-color: color-mix(in srgb, var(--rc-success) 66%, var(--rc-brand)) !important;
    background: color-mix(in srgb, var(--rc-success) 22%, #FFFFFF) !important;
    box-shadow:
        0 0 0 2px color-mix(in srgb, var(--rc-success) 12%, transparent),
        0 14px 26px -24px color-mix(in srgb, var(--rc-success) 56%, transparent) !important;
    transform: none !important;
}

.vista-reservacion .habitacion-card.disponible.selected:hover > div,
.vista-reservacion .habitacion-card.disponible.selected.es-cortesia:hover > div {
    border-color: color-mix(in srgb, var(--rc-success) 72%, var(--rc-brand)) !important;
    background:
        linear-gradient(135deg,
            color-mix(in srgb, var(--rc-success) 28%, #FFFFFF) 0%,
            color-mix(in srgb, var(--rc-success) 18%, #FFFFFF) 58%,
            color-mix(in srgb, var(--rc-success) 10%, #FFFFFF) 100%) !important;
    box-shadow:
        0 0 0 2px color-mix(in srgb, var(--rc-success) 16%, transparent),
        0 16px 30px -24px color-mix(in srgb, var(--rc-success) 58%, transparent) !important;
    transform: translateY(-1px) !important;
}

.vista-reservacion .habitacion-card.selected > div .border-t {
    border-color: color-mix(in srgb, var(--rc-success) 20%, var(--rc-line)) !important;
}

.vista-reservacion .habitacion-card.selected .w-4.h-4 i {
    color: #FFFFFF !important;
    opacity: 1 !important;
}

.vista-reservacion .rc-room-card {
    min-height: 166px;
    padding: 15px !important;
    display: flex;
    flex-direction: column;
}

.vista-reservacion .rc-room-main {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    grid-template-areas:
        "number price"
        "meta meta";
    align-items: start;
    gap: 11px 14px;
    min-width: 0;
}

.vista-reservacion .rc-room-number {
    grid-area: number;
    min-height: 0;
    min-width: 0;
    display: grid;
    align-content: start;
    justify-items: start;
    gap: 4px;
}

.vista-reservacion .rc-room-label {
    color: var(--rc-muted);
    font-size: .62rem;
    font-weight: 850;
    letter-spacing: .08em;
    line-height: 1;
    text-transform: uppercase;
}

.vista-reservacion .rc-room-number strong {
    color: #111827;
    font-family: var(--rc-serif);
    max-width: 100%;
    font-size: clamp(1.55rem, 4vw, 2.05rem);
    font-weight: 700;
    line-height: .94;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}

.vista-reservacion .rc-room-meta {
    grid-area: meta;
    min-width: 0;
    display: grid;
    gap: 8px;
}

.vista-reservacion .rc-room-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px 7px;
    min-width: 0;
}

.vista-reservacion .rc-room-chip {
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 23px;
    padding: 4px 8px;
    border: 1px solid var(--rc-line);
    border-radius: 999px;
    background: color-mix(in srgb, var(--rc-accent) 4%, #FFFFFF);
    color: var(--rc-muted);
    font-size: .68rem;
    font-weight: 850;
    line-height: 1;
    white-space: nowrap;
}

.vista-reservacion .rc-room-chip--available {
    border-color: color-mix(in srgb, var(--rc-success) 28%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-success) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--rc-success) 72%, #123B2B);
}

.vista-reservacion .rc-room-chip--occupied {
    border-color: color-mix(in srgb, var(--rc-danger) 48%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-danger) 16%, #FFFFFF);
    color: color-mix(in srgb, var(--rc-danger) 88%, #5A1713);
}

.vista-reservacion .rc-room-chip--maintenance,
.vista-reservacion .rc-room-chip--courtesy {
    border-color: color-mix(in srgb, var(--rc-warning) 50%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-warning) 18%, #FFFFFF);
    color: color-mix(in srgb, var(--rc-warning) 88%, #5C2C05);
}

.vista-reservacion .rc-room-chip--info {
    border-color: color-mix(in srgb, var(--rc-info) 24%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-info) 7%, #FFFFFF);
    color: color-mix(in srgb, var(--rc-info) 86%, #12345D);
}

.vista-reservacion .rc-room-floor,
.vista-reservacion .rc-room-features {
    color: var(--rc-muted);
    font-size: .78rem;
    font-weight: 650;
    line-height: 1.35;
    min-width: 0;
    overflow-wrap: anywhere;
}

.vista-reservacion .rc-room-floor i,
.vista-reservacion .rc-room-features i {
    color: var(--rc-accent-dark);
    margin-right: 5px;
}

.vista-reservacion .rc-room-price {
    grid-area: price;
    min-width: 104px;
    max-width: 132px;
    text-align: right;
    white-space: nowrap;
}

.vista-reservacion .rc-room-price strong {
    display: block;
    color: #111827;
    font-size: 1.02rem;
    font-weight: 900;
    line-height: 1.05;
    font-variant-numeric: tabular-nums;
}

.vista-reservacion .rc-room-price span {
    color: var(--rc-muted);
    font-size: .68rem;
    font-weight: 750;
}

.vista-reservacion .rc-room-price.is-muted strong {
    color: color-mix(in srgb, var(--rc-muted) 76%, #FFFFFF);
    text-decoration: line-through;
}

.vista-reservacion .rc-room-detail {
    margin-top: 12px;
    min-width: 0;
    overflow-wrap: anywhere;
}

.vista-reservacion .rc-room-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid var(--rc-line-soft);
}

.vista-reservacion .rc-room-action-text {
    color: var(--rc-muted);
    font-size: .72rem;
    font-weight: 800;
}

.vista-reservacion .rc-room-check {
    flex: 0 0 auto;
    width: 20px !important;
    height: 20px !important;
    border-radius: 999px !important;
}

.vista-reservacion .habitacion-card.selected .rc-room-number strong,
.vista-reservacion .habitacion-card.selected .rc-room-price strong {
    color: color-mix(in srgb, var(--rc-success) 88%, #123322) !important;
}

.vista-reservacion .habitacion-card.selected .rc-room-action-text {
    color: color-mix(in srgb, var(--rc-success) 88%, #123322);
}

.vista-reservacion .habitacion-card.disponible.selected:hover .rc-room-chip--available,
.vista-reservacion .habitacion-card.disponible.selected:hover .rc-room-action-text {
    color: color-mix(in srgb, var(--rc-success) 90%, #10351F) !important;
}

@media (max-width: 560px) {
    .vista-reservacion .rc-room-main {
        grid-template-columns: 1fr;
        grid-template-areas:
            "number"
            "price"
            "meta";
        gap: 9px;
    }

    .vista-reservacion .rc-room-price {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        min-width: 0;
        max-width: none;
        padding-top: 8px;
        border-top: 1px solid var(--rc-line-soft);
        text-align: left;
    }
}

@media (min-width: 1180px) and (max-width: 1360px) {
    .vista-reservacion .rc-room-main {
        grid-template-columns: 1fr;
        grid-template-areas:
            "number"
            "price"
            "meta";
    }

    .vista-reservacion .rc-room-price {
        min-width: 0;
        max-width: none;
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        padding-top: 8px;
        border-top: 1px solid var(--rc-line-soft);
        text-align: left;
    }
}

.vista-reservacion .seccion-cortesias {
    margin-top: 16px !important;
    padding: 15px !important;
    background: var(--rc-warning-soft) !important;
}

.vista-reservacion .titulo-cortesias {
    color: color-mix(in srgb, var(--rc-warning) 72%, #5C2C05) !important;
}

.vista-reservacion .item-cortesia {
    border: 1px solid color-mix(in srgb, var(--rc-warning) 22%, var(--rc-line)) !important;
    border-radius: 13px !important;
    background: #FFFFFF !important;
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.vista-reservacion .item-cortesia.activa {
    border-color: var(--rc-warning) !important;
    background: color-mix(in srgb, var(--rc-warning) 8%, #FFFFFF) !important;
}

.vista-reservacion .total-box {
    border: 1px solid var(--rc-accent-line) !important;
    border-radius: 14px !important;
    background: var(--rc-accent-soft) !important;
}

.vista-reservacion .resumen-scroll {
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--rc-accent), #fff 34%) transparent;
}

.vista-reservacion .xl\:col-span-1 > .bg-white.sticky {
    top: 14px !important;
}

.vista-reservacion .reservation-side-column {
    position: sticky;
    top: 14px;
    z-index: 4;
    align-self: start;
    gap: 18px !important;
}

.vista-reservacion .reservation-side-column > .reservation-summary-card {
    position: relative !important;
    top: auto !important;
    z-index: 2;
}

.vista-reservacion .reservation-summary-card .resumen-scroll {
    max-height: clamp(180px, calc(100vh - 500px), 400px);
}

.vista-reservacion .reservation-summary-actions {
    position: relative;
    z-index: 2;
    margin-top: 16px !important;
    padding-top: 16px !important;
    background: var(--rc-surface) !important;
}

.vista-reservacion .reservation-important-card {
    position: relative;
    z-index: 1;
}

.vista-reservacion .xl\:col-span-1 .border-t {
    border-color: var(--rc-line-soft) !important;
    background: transparent !important;
}

.resumen-flotante {
    border: 1px solid var(--brand-accent, #BD9441) !important;
    border-radius: 18px 18px 0 0 !important;
    background: color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFFFF) !important;
    box-shadow: 0 -18px 40px -24px rgba(15,23,42,.38) !important;
}

.resumen-flotante h4 {
    color: #111827 !important;
}

.vista-reservacion ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.vista-reservacion ::-webkit-scrollbar-track {
    background: var(--rc-ivory);
}

.vista-reservacion ::-webkit-scrollbar-thumb {
    background: color-mix(in srgb, var(--rc-accent), #fff 34%);
    border-radius: 999px;
}

@media (min-width: 1440px) {
    .vista-reservacion > div:first-of-type > .px-5,
    .vista-reservacion > .px-5,
    .vista-reservacion > .pt-4 {
        width: min(100%, 1360px);
    }

    .vista-reservacion #formReservacion > .grid {
        grid-template-columns: minmax(0, 1fr) 330px !important;
        gap: 22px !important;
    }

    .vista-reservacion .card-animate > .p-5,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white .p-4 {
        padding: 19px !important;
    }
}

@media (max-width: 1180px) {
    .vista-reservacion > div:first-of-type > .px-5,
    .vista-reservacion > .px-5,
    .vista-reservacion > .pt-4 {
        width: min(100%, 1100px);
    }

    .vista-reservacion #formReservacion > .grid {
        grid-template-columns: minmax(0, 1fr) 284px !important;
        gap: 16px !important;
    }
}

@media (max-width: 1024px) {
    .vista-reservacion #formReservacion > .grid {
        grid-template-columns: 1fr !important;
    }

    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .vista-reservacion .xl\:col-span-1 > .bg-white.sticky,
    .vista-reservacion .buscador-habitaciones {
        position: static !important;
    }

    .vista-reservacion .reservation-side-column {
        position: static !important;
        top: auto !important;
        z-index: auto;
    }

    .vista-reservacion .reservation-summary-card .resumen-scroll {
        max-height: 360px;
    }
}

@media (max-width: 860px) {
    .vista-reservacion > div:first-of-type .flex.items-center.justify-between.gap-3 {
        flex-direction: column;
        align-items: stretch !important;
    }

    .vista-reservacion .btn-back {
        align-self: flex-start;
    }

    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
        grid-template-columns: 1fr;
    }

    .vista-reservacion .room-stats-bar {
        align-items: flex-start;
        flex-direction: column;
    }
}

@media (max-width: 700px) {
    .vista-reservacion > div:first-of-type > .px-5 {
        padding: 18px 14px 0 !important;
    }

    .vista-reservacion > .px-5 {
        padding: 16px 14px 116px !important;
    }

    .vista-reservacion > .pt-4 {
        padding-left: 14px !important;
        padding-right: 14px !important;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 {
        gap: 11px !important;
    }

    .vista-reservacion .card-animate > .p-5,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white .p-4,
    .vista-reservacion .info-card {
        padding: 14px !important;
    }

    .vista-reservacion .panel-hd-guest,
    .vista-reservacion .panel-hd-dates,
    .vista-reservacion .panel-hd-rooms,
    .vista-reservacion .panel-hd-notes,
    .vista-reservacion .panel-hd-summary {
        min-height: auto;
        padding: 14px !important;
    }

    .vista-reservacion .lc-input,
    .vista-reservacion .input-busqueda,
    .vista-reservacion .select2-container--default .select2-selection--single {
        font-size: 16px !important;
    }

    .vista-reservacion .btn-gold,
    .vista-reservacion .btn-hour,
    .vista-reservacion .btn-save,
    .vista-reservacion .btn-cotizacion,
    .vista-reservacion .btn-cancel {
        min-height: 46px;
    }
}

@media (max-width: 480px) {
    .vista-reservacion > div:first-of-type > .px-5,
    .vista-reservacion > .px-5,
    .vista-reservacion > .pt-4 {
        padding-left: 12px !important;
        padding-right: 12px !important;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 {
        width: 100%;
        overflow-x: auto;
        padding-bottom: 2px;
        white-space: nowrap;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 > div[style*="width:46px"] {
        width: 42px !important;
        height: 42px !important;
        border-radius: 12px !important;
    }

    .vista-reservacion > div:first-of-type h1 {
        font-size: 2rem !important;
    }

    .vista-reservacion > div:first-of-type h1 + p {
        font-size: .84rem !important;
    }

    .vista-reservacion .panel-hd-guest h2 i,
    .vista-reservacion .panel-hd-dates h2 i,
    .vista-reservacion .panel-hd-rooms h2 i,
    .vista-reservacion .panel-hd-notes h2 i,
    .vista-reservacion .panel-hd-summary h3 i {
        width: 31px;
        height: 31px;
        border-radius: 10px;
    }

    .vista-reservacion .res-guest-search-row {
        flex-direction: column;
    }

    .vista-reservacion .btn-gold {
        width: 100%;
    }
}

@media (max-width: 360px) {
    .vista-reservacion > div:first-of-type > .px-5,
    .vista-reservacion > .px-5,
    .vista-reservacion > .pt-4 {
        padding-left: 10px !important;
        padding-right: 10px !important;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 {
        flex-direction: column;
    }

    .vista-reservacion .btn-back,
    .vista-reservacion .btn-gold,
    .vista-reservacion .btn-hour,
    .vista-reservacion .btn-save,
    .vista-reservacion .btn-cotizacion,
    .vista-reservacion .btn-cancel {
        width: 100%;
    }
}

/* ════════════════════════════════════════════════════════════════════
   MÓVIL COMPACTO  ·  estética dashboard / habitaciones (≤768px)
   Cards de habitación copiadas del estilo .dm-card del dashboard móvil.
   ════════════════════════════════════════════════════════════════════ */
@media (max-width: 768px) {
    /* ── FIX del hueco: el contenedor de la alerta arrastraba 116px de
       padding-bottom (regla de 700px). Lo reseteamos; solo el contenedor
       del formulario conserva espacio para la barra flotante inferior. ── */
    .vista-reservacion > .px-5 { padding-top: 12px !important; padding-bottom: 12px !important; }
    .vista-reservacion > .px-5:has(#formReservacion) { padding-bottom: 96px !important; }

    /* Layout: stack flex limpio (sin tracks fantasma del grid) */
    .vista-reservacion #formReservacion > .grid {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        grid-template-columns: none !important;
    }
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-3,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        width: 100% !important;
    }

    /* ── Header compacto ── */
    .vista-reservacion > div:first-of-type .px-5 { padding-top: 14px !important; padding-bottom: 12px !important; }
    .vista-reservacion > div:first-of-type h1 { font-size: 1.5rem !important; }
    .vista-reservacion > div:first-of-type h1 + p { display: none; }   /* omitido: subtitulo decorativo */
    .vista-reservacion .btn-back { display: none !important; }         /* omitido: el breadcrumb ya permite volver */

    /* Headers de panel mas compactos */
    .vista-reservacion .panel-hd-guest,
    .vista-reservacion .panel-hd-dates,
    .vista-reservacion .panel-hd-rooms,
    .vista-reservacion .panel-hd-notes,
    .vista-reservacion .panel-hd-summary { min-height: 0 !important; padding: 12px 14px !important; }

    /* ── Cards de habitación: estilo .dm-card del dashboard móvil ── */
    .vista-reservacion .room-type-grid { grid-template-columns: 1fr !important; gap: 8px !important; }
    .vista-reservacion .room-type-group { margin-bottom: 14px; }
    /* Más margen izquierdo para el tipo ("Sencilla") y los badges de conteo */
    .vista-reservacion .room-type-group-head { padding: 10px 10px 8px 14px !important; }
    .vista-reservacion .room-type-title h4 { font-size: .95rem; }
    .vista-reservacion .room-type-title p { display: none; }           /* omitido: "N habitaciones en este tipo" (ya está en los badges) */

    .vista-reservacion .rc-room-card {
        min-height: 0;
        padding: 14px 14px 14px 17px !important;   /* +margen izquierdo al contenido de la card */
        border-radius: 15px;
        box-shadow: 0 2px 8px rgba(27, 39, 70, .05), 0 12px 26px -20px rgba(27, 39, 70, .2);
    }
    .vista-reservacion .rc-room-main { gap: 8px 12px; }
    .vista-reservacion .rc-room-number strong { font-size: 1.4rem; }
    .vista-reservacion .rc-room-label { font-size: .58rem; }
    .vista-reservacion .rc-room-chips { gap: 5px 6px; }
    .vista-reservacion .rc-room-chip { min-height: 22px; padding: 3px 7px; font-size: .64rem; }
    .vista-reservacion .rc-room-floor { font-size: .74rem; }
    .vista-reservacion .rc-room-features {
        font-size: .74rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .vista-reservacion .rc-room-price strong { font-size: .98rem; }
    .vista-reservacion .rc-room-footer { padding-top: 10px; }
    .vista-reservacion .rc-room-action-text { font-size: .7rem; }

    /* Estadísticas de selección: compactas */
    .vista-reservacion .room-stats-bar { padding: 11px 13px !important; gap: 8px; }

    /* ── PROPUESTA resumen: en móvil usamos SOLO la barra flotante inferior ──
       Se oculta el bloque inline (resumen + "Información Importante") que
       apilado ocupaba muchísima pantalla. La barra flotante (#resumenFlotante)
       ya aparece al seleccionar habitaciones con total + Guardar + Cotización. */
    .vista-reservacion .reservation-side-column { display: none !important; }
}

/* Guest-create inspired mobile refresh */
@media (max-width: 768px) {
    .vista-reservacion {
        background:
            repeating-linear-gradient(135deg, color-mix(in srgb, var(--rc-accent) 2%, transparent) 0 1px, transparent 1px 20px),
            linear-gradient(180deg, var(--rc-ivory-2), var(--rc-ivory));
    }

    .vista-reservacion > div:first-of-type > .px-5 {
        padding: 10px 10px 0 !important;
    }

    .vista-reservacion > .px-5:has(#formReservacion) {
        padding: 18px 10px 98px !important;
    }

    .vista-reservacion > .pt-4 {
        padding: 10px 10px 0 !important;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3 {
        width: 100%;
        margin-bottom: 16px !important;
        gap: 5px !important;
        overflow-x: auto;
        padding-bottom: 0;
        white-space: nowrap;
        font-size: .66rem !important;
        scrollbar-width: none;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.text-xs.mb-3::-webkit-scrollbar {
        display: none;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.justify-between.gap-3 {
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 12px !important;
        margin-bottom: 14px;
        padding: 0 4px 2px;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 {
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 9px !important;
        min-width: 0;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 > div[style*="width:46px"] {
        width: 38px !important;
        height: 38px !important;
        flex: 0 0 38px !important;
        border-radius: 12px !important;
        font-size: .88rem;
    }

    .vista-reservacion > div:first-of-type .flex.items-center.gap-3 > div[style*="width:46px"] i {
        font-size: .9rem !important;
    }

    .vista-reservacion > div:first-of-type h1 {
        font-size: 1.38rem !important;
        line-height: .96 !important;
    }

    .vista-reservacion > div:first-of-type h1 + p {
        display: -webkit-box !important;
        max-width: none;
        margin-top: 3px !important;
        color: var(--rc-muted) !important;
        font-size: .68rem !important;
        line-height: 1.25 !important;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .vista-reservacion .btn-back {
        display: none !important;
    }

    .vista-reservacion .alert-presel {
        padding: 9px 10px !important;
        border-radius: 13px !important;
        gap: 8px;
        font-size: .71rem;
        line-height: 1.3;
    }

    .vista-reservacion #formReservacion > .grid,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-3,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 {
        gap: 9px !important;
    }

    .vista-reservacion .card-animate,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white,
    .vista-reservacion .info-card {
        border-radius: 15px !important;
        box-shadow: 0 10px 24px rgba(24, 33, 46, .07) !important;
    }

    .vista-reservacion .card-animate:hover,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white:hover,
    .vista-reservacion .info-card:hover {
        transform: none;
    }

    .vista-reservacion .panel-hd-guest,
    .vista-reservacion .panel-hd-dates,
    .vista-reservacion .panel-hd-rooms,
    .vista-reservacion .panel-hd-notes,
    .vista-reservacion .panel-hd-summary {
        min-height: 0 !important;
        padding: 9px 10px !important;
        background: var(--rc-surface-warm) !important;
        border-bottom: 1px solid var(--rc-line);
    }

    .vista-reservacion .panel-hd-guest h2,
    .vista-reservacion .panel-hd-dates h2,
    .vista-reservacion .panel-hd-rooms h2,
    .vista-reservacion .panel-hd-notes h2,
    .vista-reservacion .panel-hd-summary h3 {
        gap: 8px !important;
        color: #111827 !important;
        font-size: .86rem !important;
        line-height: 1.1 !important;
    }

    .vista-reservacion .panel-hd-guest h2 i,
    .vista-reservacion .panel-hd-dates h2 i,
    .vista-reservacion .panel-hd-rooms h2 i,
    .vista-reservacion .panel-hd-notes h2 i,
    .vista-reservacion .panel-hd-summary h3 i {
        width: 29px !important;
        height: 29px !important;
        flex: 0 0 29px !important;
        border-radius: 10px !important;
        font-size: .78rem !important;
    }

    .vista-reservacion .card-animate > .p-5,
    .vista-reservacion #formReservacion > .grid > .xl\:col-span-1 > .bg-white .p-4,
    .vista-reservacion .info-card {
        padding: 10px !important;
    }

    .vista-reservacion label {
        margin-bottom: 4px !important;
        gap: 4px;
        font-size: .58rem !important;
        letter-spacing: .045em;
        line-height: 1.1;
    }

    .vista-reservacion label i {
        font-size: .68rem;
    }

    .vista-reservacion .lc-input,
    .vista-reservacion .input-busqueda {
        min-height: 44px !important;
        border-radius: 11px !important;
        padding: 7px 10px !important;
        font-size: 13px !important;
        line-height: 1.15 !important;
        font-weight: 650 !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .66) !important;
    }

    .vista-reservacion .lc-input::placeholder,
    .vista-reservacion .input-busqueda::placeholder,
    .vista-reservacion .res-guest-input::placeholder {
        font-size: 12px;
        font-weight: 600;
    }

    .vista-reservacion select.lc-input {
        font-size: 12.5px !important;
        text-overflow: ellipsis;
    }

    .vista-reservacion textarea.lc-input {
        min-height: 76px !important;
        padding-top: 9px !important;
        line-height: 1.32 !important;
    }

    .vista-reservacion .select2-container--default .select2-selection--single {
        min-height: 44px !important;
        height: 44px !important;
        border-radius: 11px !important;
    }

    .vista-reservacion .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important;
        padding-left: 10px !important;
        padding-right: 28px !important;
        font-size: 12.5px !important;
        font-weight: 650 !important;
    }

    .vista-reservacion .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        right: 6px !important;
    }

    .vista-reservacion .res-guest-search-row {
        flex-direction: row !important;
        gap: 8px !important;
        align-items: stretch;
    }

    .vista-reservacion .res-guest-combobox {
        min-height: 44px;
        border-radius: 11px;
        gap: 7px;
    }

    .vista-reservacion .res-guest-search-icon {
        margin-left: 10px;
        font-size: .68rem;
    }

    .vista-reservacion .res-guest-input {
        height: 42px;
        font-size: 13px;
        font-weight: 650;
    }

    .vista-reservacion .res-guest-clear {
        width: 44px;
        height: 44px;
        margin-right: 0;
        border-radius: 11px;
    }

    .vista-reservacion .res-guest-results {
        top: calc(100% + 6px);
        max-height: 248px;
        padding: 6px;
        border-radius: 13px;
    }

    .vista-reservacion .res-guest-option,
    .vista-reservacion .res-guest-state {
        gap: 8px;
        padding: 9px;
        border-radius: 10px;
    }

    .vista-reservacion .res-guest-avatar {
        width: 30px;
        height: 30px;
        flex-basis: 30px;
        border-radius: 10px;
        font-size: .7rem;
    }

    .vista-reservacion .res-guest-option-name {
        font-size: .78rem;
    }

    .vista-reservacion .res-guest-option-meta,
    .vista-reservacion .res-guest-state {
        font-size: .66rem;
    }

    .vista-reservacion #infoHuesped.guest-info-box {
        margin-top: 8px;
        min-height: 0 !important;
        padding: 9px 10px !important;
        border-radius: 13px !important;
    }

    .vista-reservacion #infoHuesped > p {
        margin: 0 0 7px !important;
        color: var(--rc-muted) !important;
        font-size: .58rem !important;
        line-height: 1.1;
        letter-spacing: .055em;
    }

    .vista-reservacion #detallesHuesped {
        min-height: 0;
    }

    .vista-reservacion #detallesHuesped > .grid {
        display: flex !important;
        flex-wrap: wrap;
        align-items: baseline;
        align-content: flex-start;
        gap: 6px 13px !important;
        min-height: 0;
        font-size: .66rem !important;
        line-height: 1.35;
    }

    .vista-reservacion #detallesHuesped > .grid > div {
        width: auto;
        min-height: 0;
        display: inline-flex;
        align-items: baseline;
        gap: 4px;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        color: var(--rc-muted);
    }

    .vista-reservacion #detallesHuesped strong {
        color: #111827;
        font-size: .64rem;
        font-weight: 850;
    }

    .vista-reservacion .btn-gold {
        width: 44px !important;
        min-width: 44px;
        min-height: 44px !important;
        padding: 0 !important;
        border-radius: 11px;
    }

    .vista-reservacion .btn-gold span {
        display: none !important;
    }

    .vista-reservacion .panel-hd-dates + .p-5 > .grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px !important;
    }

    .vista-reservacion .panel-hd-dates + .p-5 > .grid > div:nth-child(3) {
        grid-column: 1 / -1;
    }

    .vista-reservacion .arrival-planner {
        gap: 7px;
    }

    .vista-reservacion .arrival-mode-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 6px !important;
    }

    .vista-reservacion .arrival-mode-btn {
        min-height: 44px;
        padding: 6px 5px;
        border-radius: 11px;
        font-size: .61rem;
        line-height: 1.1;
    }

    .vista-reservacion .arrival-mode-btn i {
        display: block;
        margin: 0 0 3px !important;
        font-size: .72rem;
    }

    .vista-reservacion .arrival-help,
    .vista-reservacion .text-xs.text-gray-400 {
        margin-top: 4px !important;
        font-size: .62rem !important;
        line-height: 1.25;
    }

    .vista-reservacion .buscador-habitaciones {
        position: static !important;
        margin-bottom: 10px !important;
        padding: 9px !important;
        border-radius: 13px;
        box-shadow: none;
    }

    .vista-reservacion .buscador-habitaciones .mt-2\.5 {
        gap: 6px 9px !important;
        font-size: .61rem !important;
        line-height: 1.15;
    }

    .vista-reservacion .icono-busqueda {
        right: 10px;
        font-size: .72rem;
    }

    .vista-reservacion .room-type-grid {
        grid-template-columns: 1fr !important;
        gap: 8px !important;
    }

    .vista-reservacion .room-type-group {
        margin-bottom: 10px;
        border-radius: 14px;
    }

    .vista-reservacion .room-type-group-head {
        padding: 9px 10px !important;
        gap: 8px;
    }

    .vista-reservacion .room-type-title h4 {
        font-size: .84rem;
        line-height: 1.1;
    }

    .vista-reservacion .room-type-count {
        min-height: 24px;
        padding: 4px 8px;
        font-size: .62rem;
    }

    .vista-reservacion .room-type-body {
        padding: 8px;
    }

    .vista-reservacion .rc-room-card {
        min-height: 0;
        padding: 12px 12px 12px 15px !important;
        border-radius: 14px !important;
    }

    .vista-reservacion .rc-room-main {
        gap: 8px 10px;
    }

    .vista-reservacion .rc-room-number strong {
        font-size: 1.32rem;
    }

    .vista-reservacion .rc-room-label {
        font-size: .55rem;
    }

    .vista-reservacion .rc-room-chip {
        min-height: 21px;
        padding: 3px 7px;
        font-size: .6rem;
    }

    .vista-reservacion .rc-room-floor,
    .vista-reservacion .rc-room-features {
        font-size: .7rem;
        line-height: 1.28;
    }

    .vista-reservacion .rc-room-price strong {
        font-size: .92rem;
    }

    .vista-reservacion .rc-room-price span,
    .vista-reservacion .rc-room-action-text {
        font-size: .62rem;
    }

    .vista-reservacion .rc-room-footer {
        padding-top: 9px;
    }

    .vista-reservacion .seccion-cortesias {
        margin-top: 10px !important;
        padding: 10px !important;
        border-radius: 13px !important;
    }

    .vista-reservacion .titulo-cortesias {
        gap: 6px;
        margin-bottom: 8px;
        font-size: .78rem;
    }

    .vista-reservacion .item-cortesia {
        padding: 9px;
        border-radius: 11px !important;
    }

    .vista-reservacion .checkbox-cortesia,
    .vista-reservacion .rc-room-check {
        width: 22px !important;
        height: 22px !important;
    }

    .vista-reservacion .res-form-error {
        margin-top: 2px;
        font-size: .66rem;
        line-height: 1.25;
    }

    .resumen-flotante {
        left: 8px;
        right: 8px;
        bottom: 8px;
        border-radius: 16px !important;
        border: 1px solid var(--brand-accent, #BD9441) !important;
        padding-bottom: max(8px, env(safe-area-inset-bottom, 0px));
        box-shadow: 0 -14px 34px -24px rgba(15, 23, 42, .42) !important;
        overflow: hidden;
    }

    .rf-row {
        gap: 8px;
        padding: 8px;
    }

    .rf-info {
        min-height: 44px;
        padding: 6px 8px;
        border: 1px solid color-mix(in srgb, var(--brand-accent, #BD9441) 18%, #E5E7EB);
        border-radius: 12px;
        background: #FFFFFF;
    }

    .rf-info-text strong {
        font-size: 1rem;
    }

    .rf-info-text small {
        font-size: .62rem;
    }

    .rf-save {
        min-width: 112px;
        min-height: 44px !important;
        border-radius: 12px;
        font-size: .72rem;
    }

    .rf-detail {
        padding-left: 8px;
        padding-right: 8px;
    }

    .resumen-flotante.expanded .rf-detail {
        max-height: 44vh;
        padding: 9px 8px 2px;
    }
}

@media (max-width: 380px) {
    .vista-reservacion > div:first-of-type > .px-5,
    .vista-reservacion > .px-5:has(#formReservacion),
    .vista-reservacion > .pt-4 {
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .vista-reservacion > div:first-of-type h1 {
        font-size: 1.22rem !important;
    }

    .vista-reservacion .panel-hd-dates + .p-5 > .grid {
        grid-template-columns: 1fr !important;
    }

    .vista-reservacion .arrival-mode-grid {
        grid-template-columns: 1fr !important;
    }

    .vista-reservacion .res-guest-search-row {
        flex-direction: column !important;
    }

    .vista-reservacion .btn-gold {
        width: 100% !important;
    }

    .vista-reservacion .btn-gold span {
        display: inline !important;
    }
}

@media (max-width: 768px) {
    .select2-dropdown {
        font-size: 13px !important;
    }
}

@media (prefers-reduced-motion: reduce) {
    .vista-reservacion *,
    .vista-reservacion *::before,
    .vista-reservacion *::after {
        transition: none !important;
        animation: none !important;
    }
}
</style>

<!-- ═══════════════════ NUEVA RESERVACIÓN ════════════════════ -->
<div class="vista-reservacion">

    <!-- ── Top bar ── -->
    <div style="background: linear-gradient(135deg, #3D5234, #4A6340, #5C7A4E); position:relative; overflow:hidden;">
        <!-- Decorative circle -->
        <div style="position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(200,169,106,.07);pointer-events:none;"></div>

        <div class="px-5 sm:px-7 py-4 relative z-10">
            <!-- Breadcrumb -->
            <div class="flex items-center text-xs mb-3" style="color:rgba(255,255,255,.6);">
                <a href="<?= url('reservaciones') ?>" style="color:rgba(255,255,255,.7);text-decoration:none;display:flex;align-items:center;gap:5px;transition:color .15s;"
                   onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.7)'">
                    <i class="fas fa-calendar-alt" style="font-size:.65rem;"></i>
                    Reservaciones
                </a>
                <i class="fas fa-chevron-right mx-2" style="font-size:.55rem;opacity:.5;"></i>
                <span style="color:rgba(255,255,255,.9);font-weight:600;">Nueva Reservación</span>
            </div>

            <!-- Title row -->
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div style="background:rgba(255,255,255,.12);width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-calendar-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 style="color:white;font-size:1.2rem;font-weight:800;line-height:1.2;">Nueva <span class="rc-title-accent">Reservación</span></h1>
                        <p style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:2px;">Seleccione habitaciones y configure cortesías del hotel</p>
                    </div>
                </div>
                <?php $back_arrow_href = back_url('reservaciones'); $back_arrow_class = 'ms-back--inline ms-back--glass'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
                <a href="<?= back_url('reservaciones') ?>" class="btn-back ms-back-legacy">
                    <i class="fas fa-arrow-left text-xs"></i>
                    <span class="hidden sm:inline">Volver</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Preselección alert -->
    <?php if ($es_preseleccion): ?>
    <div class="px-5 sm:px-7 pt-4">
        <div class="alert-presel">
            <i class="fas fa-bolt" style="color:var(--lc-green);flex-shrink:0;"></i>
            <p class="text-sm" style="color:#3D5234;">
                <strong>Reservación rápida.</strong>
                <?= $habitacion_preseleccionada
                    ? 'Las fechas y habitación han sido prellenadas automáticamente.'
                    : 'Las fechas han sido prellenadas automáticamente. Selecciona una o más habitaciones para continuar.' ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Main grid ── -->
    <div class="px-5 sm:px-7 py-5 pb-24 xl:pb-6">
        <form method="POST" action="<?= url('reservaciones/guardar') ?>" id="formReservacion" data-no-draft data-no-unsaved-warning data-no-submit-state>
            <?= csrf_field() ?>
            <input type="hidden" name="descuento_aplicado" id="descuento_aplicado" value="">

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">

                <!-- ════ Left column (3/4) ════ -->
                <div class="xl:col-span-3 space-y-5">

                    <!-- ── 1. Huésped ── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden card-animate res-guest-card">
                        <div class="panel-hd-guest p-4">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fas fa-user opacity-80"></i>
                                Información del Huésped
                            </h2>
                        </div>

                        <div class="p-5">
                            <?php if (isset($huesped_preseleccionado)): ?>
                                <input type="hidden" name="huesped_id" value="<?= $huesped_preseleccionado['id'] ?>">
                                <div class="guest-info-box">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-wider mb-1" style="color:#7A9B6A;">Huésped Seleccionado</p>
                                            <p class="font-bold text-lg text-[#3D5234]"><?= htmlspecialchars($huesped_preseleccionado['nombre_completo']) ?></p>
                                            <?php if ($huesped_preseleccionado['telefono']): ?>
                                                <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-1">
                                                    <i class="fas fa-phone text-xs" style="color:var(--lc-green);"></i>
                                                    <?= htmlspecialchars($huesped_preseleccionado['telefono']) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($huesped_preseleccionado['procedencia_estado']): ?>
                                                <p class="text-sm text-gray-500 flex items-center gap-1.5 mt-0.5">
                                                    <i class="fas fa-map-marker-alt text-xs" style="color:var(--lc-green);"></i>
                                                    <?= htmlspecialchars($huesped_preseleccionado['procedencia_estado']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= url('reservaciones/crear') ?>"
                                           class="text-xs font-semibold flex items-center gap-1 transition-colors"
                                           style="color:var(--lc-green);"
                                           onmouseover="this.style.color='#3D5234'" onmouseout="this.style.color='var(--lc-green)'">
                                            <i class="fas fa-edit"></i> Cambiar
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <div>
                                        <label for="huesped_busqueda" class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                            Buscar Huésped <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-3 res-guest-search-row">
                                            <div class="res-guest-combobox-wrap flex-1">
                                                <select name="huesped_id" id="huesped_id" class="res-guest-native-select" required aria-hidden="true" tabindex="-1">
                                                    <option value="">-- Buscar por nombre o teléfono --</option>
                                                </select>
                                                <div class="res-guest-combobox" id="huespedSearchBox">
                                                    <i class="fas fa-search res-guest-search-icon" aria-hidden="true"></i>
                                                    <input
                                                        type="search"
                                                        id="huesped_busqueda"
                                                        class="res-guest-input"
                                                        placeholder="Buscar huésped por nombre o teléfono..."
                                                        autocomplete="off"
                                                        role="combobox"
                                                        aria-autocomplete="list"
                                                        aria-required="true"
                                                        aria-expanded="false"
                                                        aria-controls="huesped_results"
                                                    >
                                                    <button type="button" id="limpiarHuesped" class="res-guest-clear hidden" aria-label="Limpiar huésped">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div id="huesped_results" class="res-guest-results hidden" role="listbox"></div>
                                            </div>
                                            <a href="<?= url('huespedes/create?return_to=reservacion') ?>" class="btn-gold js-nuevo-huesped-link">
                                                <i class="fas fa-user-plus text-xs"></i>
                                                <span class="hidden sm:inline">Nuevo</span>
                                            </a>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                                            <i class="fas fa-info-circle"></i>
                                            Escriba al menos 2 caracteres para buscar
                                        </p>
                                    </div>

                                    <div id="infoHuesped" class="hidden guest-info-box">
                                        <p class="text-xs font-bold uppercase tracking-wider mb-2" style="color:#7A9B6A;">Datos del Huésped</p>
                                        <div id="detallesHuesped" class="text-sm text-gray-700"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (form_error('huesped_id')): ?>
                                <span class="res-form-error"><?= form_error('huesped_id') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ── 2. Fechas ── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden card-animate">
                        <div class="panel-hd-dates p-4">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fas fa-calendar opacity-80"></i>
                                Fechas y Horario de Estadía
                            </h2>
                        </div>

                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Entrada -->
                                <div>
                                    <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                        <i class="fas fa-calendar-plus mr-1" style="color:var(--lc-green);"></i>
                                        Fecha de Entrada <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="fecha_entrada" id="fecha_entrada"
                                           class="lc-input"
                                           required
                                           value="<?= old('fecha_entrada', $fecha_entrada_pre) ?>">
                                    <?php if (form_error('fecha_entrada')): ?>
                                        <span class="res-form-error"><?= form_error('fecha_entrada') ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Salida -->
                                <div>
                                    <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                        <i class="fas fa-calendar-minus mr-1" style="color:var(--lc-green);"></i>
                                        Fecha de Salida <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="fecha_salida" id="fecha_salida"
                                           class="lc-input"
                                           required
                                           value="<?= old('fecha_salida', $fecha_salida_pre) ?>">
                                    <?php if (form_error('fecha_salida')): ?>
                                        <span class="res-form-error"><?= form_error('fecha_salida') ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Hora -->
                                <div>
                                    <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                        <i class="fas fa-clock mr-1" style="color:var(--lc-green);"></i>
                                        Hora de Llegada
                                    </label>
                                    <div class="arrival-planner">
                                        <input type="hidden" name="hora_llegada_modo" id="hora_llegada_modo" value="<?= htmlspecialchars($horaLlegadaModoPre, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="arrival-mode-grid" aria-label="Modo de llegada">
                                            <button type="button" class="arrival-mode-btn" data-arrival-mode="manual">
                                                <i class="fas fa-keyboard mr-1"></i> Manual
                                            </button>
                                            <button type="button" class="arrival-mode-btn" data-arrival-mode="ahora" id="btnHoraActual">
                                                <i class="fas fa-clock mr-1"></i> Ahora
                                            </button>
                                            <button type="button" class="arrival-mode-btn" data-arrival-mode="despues">
                                                <i class="fas fa-calendar-check mr-1"></i> Despues
                                            </button>
                                        </div>
                                        <input type="time" name="hora_llegada" id="hora_llegada"
                                               class="lc-input"
                                               value="<?= old('hora_llegada', $hora_llegada_pre) ?>">
                                    </div>
                                    <p class="arrival-help mt-1.5 flex items-center gap-1" id="arrivalHelpText">
                                        <i class="fas fa-info-circle"></i>
                                        Check-in oficial: 3:00 PM. Puedes dejar la hora por definir si el huesped aun no confirma.
                                    </p>
                                    <?php if (form_error('hora_llegada')): ?>
                                        <span class="res-form-error"><?= form_error('hora_llegada') ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── 3. Habitaciones ── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden card-animate">
                        <div class="panel-hd-rooms p-4">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fas fa-bed opacity-80"></i>
                                Selección de Habitaciones
                            </h2>
                        </div>

                        <div class="p-5">
                            <!-- Search -->
                            <div class="buscador-habitaciones">
                                <div class="relative">
                                    <input type="text"
                                           id="buscarHabitacion"
                                           class="input-busqueda"
                                           placeholder="Buscar por número o tipo (sencilla, doble, triple, jacuzzi...)">
                                    <i class="fas fa-search icono-busqueda"></i>
                                </div>
                                <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#5C7A4E;"></span>Disponible</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>Ocupada</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>Mantenimiento</span>
                                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Cortesía</span>
                                </div>
                            </div>

                            <!-- Room grid -->
                            <div id="contenedorHabitaciones">
                                <div class="text-center py-12 text-gray-400">
                                    <div style="width:64px;height:64px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                                        <i class="fas fa-calendar-day text-2xl" style="color:#A8C4A0;"></i>
                                    </div>
                                    <h3 class="text-base font-bold mb-1" style="color:#6B7280;">Seleccione las fechas primero</h3>
                                    <p class="text-sm">Complete las fechas de entrada y salida para ver las habitaciones disponibles</p>
                                </div>
                            </div>
                            <?php if (form_error('habitaciones')): ?>
                                <span class="res-form-error"><?= form_error('habitaciones') ?></span>
                            <?php endif; ?>

                            <!-- Courtesy section -->
                            <div id="seccionCortesias" class="seccion-cortesias hidden">
                                <div class="titulo-cortesias">
                                    <i class="fas fa-gift"></i>
                                    <span>Seleccione Habitaciones de Cortesía</span>
                                    <span class="ml-auto text-white text-sm px-3 py-1 rounded-full font-bold" style="background:#92400E;">
                                        Habitaciones de cortesía
                                    </span>
                                </div>
                                <p class="text-sm mb-3" style="color:#92400E;">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Seleccione las habitaciones que serán de cortesía (precio $0):
                                </p>
                                <div id="listaCortesias" class="lista-cortesias"></div>
                            </div>
                        </div>
                    </div>

                    <!-- ── 4. Notas ── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden card-animate">
                        <div class="panel-hd-notes p-4">
                            <h2 class="text-base font-bold text-white flex items-center gap-2">
                                <i class="fas fa-sticky-note opacity-80"></i>
                                Notas y Observaciones
                            </h2>
                        </div>

                        <div class="p-5">
                            <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                <i class="fas fa-edit mr-1" style="color:var(--lc-gold-dark);"></i>
                                Comentarios Adicionales
                            </label>
                            <textarea name="notas" rows="4"
                                      class="lc-input resize-none"
                                      placeholder="Peticiones especiales, alergias, preferencias, observaciones importantes..."><?= old('notas') ?></textarea>
                            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                El método de pago se registrará durante el check-in
                            </p>
                        </div>

                        <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('anticipos')): ?>
                        <div class="p-5 border-t border-gray-100">
                            <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                <i class="fas fa-hand-holding-dollar mr-1" style="color:var(--lc-gold-dark);"></i>
                                Anticipo inicial (opcional)
                            </label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <input type="number" name="anticipo_inicial" min="0.01" step="0.01" data-money-format="true" data-anticipo-inicial-monto oninput="toggleTipoTarjetaAnticipoInicial()"
                                       placeholder="0.00" class="lc-input" style="flex:1;min-width:120px;" value="<?= old('anticipo_inicial') ?>">
                                <select name="anticipo_metodo" class="lc-input" data-anticipo-inicial-metodo onchange="toggleTipoTarjetaAnticipoInicial()" style="min-width:140px;">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="tarjeta">Tarjeta</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                                <div data-anticipo-inicial-tarjeta style="display:none;width:100%;margin-top:6px;">
                                    <span class="block text-xs font-bold mb-2" style="color:#6B7280;">Tipo de tarjeta</span>
                                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                        <label style="display:flex;align-items:center;gap:6px;border:1px solid #BFDBFE;border-radius:10px;padding:8px 10px;font-size:.83rem;font-weight:700;cursor:pointer;background:#FFFFFF;color:#334155;">
                                            <input type="radio" name="tipo_tarjeta_anticipo_inicial" value="debito" disabled>
                                            <i class="fas fa-money-check-alt"></i> Debito
                                        </label>
                                        <label style="display:flex;align-items:center;gap:6px;border:1px solid #BFDBFE;border-radius:10px;padding:8px 10px;font-size:.83rem;font-weight:700;cursor:pointer;background:#FFFFFF;color:#334155;">
                                            <input type="radio" name="tipo_tarjeta_anticipo_inicial" value="credito" disabled>
                                            <i class="fas fa-credit-card"></i> Credito
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                Si capturas un anticipo, se registra en caja al crear la reservación (requiere caja abierta).
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ════ Right column (1/4) ════ -->
                <div class="xl:col-span-1 space-y-4 reservation-side-column">

                    <!-- Summary panel -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden sticky top-5 reservation-summary-card">
                        <div class="panel-hd-summary p-4">
                            <h3 class="font-bold text-white flex items-center gap-2">
                                <i class="fas fa-receipt opacity-80"></i>
                                Resumen de Reservación
                            </h3>
                        </div>

                        <div class="p-4 relative">
                            <div id="resumenReservacion" class="resumen-scroll">
                                <div class="text-center py-8">
                                    <div style="width:52px;height:52px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                                        <i class="fas fa-clipboard-list text-xl" style="color:#A8C4A0;"></i>
                                    </div>
                                    <p class="text-gray-400 text-sm">Complete el formulario para ver el resumen</p>
                                </div>
                            </div>

                            <div class="border-t border-[#EAF0E5] pt-4 mt-4 space-y-2.5 bg-white reservation-summary-actions">
                                <button type="submit" id="btnGuardar" disabled class="btn-save">
                                    <i class="fas fa-save"></i>
                                    <span>Guardar Reservación</span>
                                </button>
                                <button type="button" id="btnCotizacion" disabled class="btn-cotizacion">
    <i class="fas fa-file-pdf"></i>
    <span>Generar Cotización PDF</span>
</button>
                                <a href="<?= back_url('reservaciones') ?>" class="btn-cancel">
                                    <i class="fas fa-times text-xs"></i>
                                    <span>Cancelar</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Info card -->
                    <div class="info-card reservation-important-card">
                        <h4 class="font-bold mb-3 flex items-center gap-2" style="color:#4A6340;">
                            <i class="fas fa-lightbulb" style="color:var(--lc-gold-dark);"></i>
                            Información Importante
                        </h4>
                        <ul class="space-y-2.5 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-clock mt-0.5 flex-shrink-0" style="color:var(--lc-green);"></i>
                                <span>Check-in: 3:00 PM · Check-out: 12:00 PM</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-credit-card mt-0.5 flex-shrink-0" style="color:#2563EB;"></i>
                                <span>Pago total al momento del check-in</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-gift mt-0.5 flex-shrink-0" style="color:var(--lc-gold-dark);"></i>
                                <span class="font-semibold" style="color:#92400E;">Habitaciones de cortesía disponibles</span>
                            </li>
                        </ul>
                    </div>
                </div>

            </div><!-- end grid -->
        </form>
    </div>
</div><!-- end page -->

<!-- ── Barra resumen móvil (slim, tipo checkout) ── -->
<div class="resumen-flotante" id="resumenFlotante">
    <!-- Detalle expandible (oculto por defecto) -->
    <div class="rf-detail" id="rfDetail">
        <div id="resumenMovil">
            <p class="text-gray-400 text-sm text-center">Sin habitaciones seleccionadas</p>
        </div>
        <button type="button" id="btnCotizacionMovil" disabled class="btn-cotizacion rf-cotizacion">
            <i class="fas fa-file-pdf"></i>
            <span>Cotización PDF</span>
        </button>
    </div>
    <!-- Fila slim siempre visible: total + Guardar -->
    <div class="rf-row">
        <button type="button" class="rf-info" id="rfToggle" aria-expanded="false" aria-controls="rfDetail">
            <span class="rf-info-text">
                <strong id="rfTotal">$0</strong>
                <small id="rfMeta">Selecciona habitaciones</small>
            </span>
            <i class="fas fa-chevron-up rf-caret" aria-hidden="true"></i>
        </button>
        <button type="submit" form="formReservacion" id="btnGuardarMovil" disabled class="btn-save rf-save">
            <i class="fas fa-save"></i>
            <span>Guardar</span>
        </button>
    </div>
</div>

<!-- ── Libraries ── -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // ── Global state ──────────────────────────────────────────
    let habitacionesSeleccionadas = [];
    let habitacionesCortesiaSeleccionadas = [];
    let todasLasHabitaciones = [];
    let habitacionesDisponibles = [];
    let habitacionesOcupadas = [];
    let busquedaActiva = '';
    let huespedSeleccionadoActual = null;
    const HUESPED_PRESELECCIONADO = <?= json_encode($huesped_preseleccionado ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const ROOM_TYPE_LABELS = <?= json_encode($tiposHabitacionReservacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const TIENE_OLD_RESERVACION = <?= $reservacionTieneOldInput ? 'true' : 'false' ?>;
    const OLD_HABITACIONES = <?= json_encode($oldHabitacionesReservacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const OLD_CORTESIAS = <?= json_encode($oldCortesiasReservacion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const RESERVA_URL_PARAMS = new URLSearchParams(window.location.search);
    const TIENE_FECHAS_URL = !!(RESERVA_URL_PARAMS.get('fecha_entrada') && RESERVA_URL_PARAMS.get('fecha_salida'));
    const ES_RESERVACION_RAPIDA = !!(RESERVA_URL_PARAMS.get('habitacion_id') || RESERVA_URL_PARAMS.get('preseleccion') || TIENE_FECHAS_URL);
    const DEBE_CARGAR_HABITACIONES_INICIALES = ES_RESERVACION_RAPIDA || TIENE_OLD_RESERVACION;
    let seleccionInicialPendiente = OLD_HABITACIONES.length > 0;

    // Boot
    $('.vista-reservacion').addClass('loaded');
    if (ES_RESERVACION_RAPIDA) {
        if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }
    inicializarReservacionRapida();

    function inicializarReservacionRapida() {
        const fechaEntrada = $('#fecha_entrada').val();
        const fechaSalida  = $('#fecha_salida').val();
        const habitacionId = RESERVA_URL_PARAMS.get('habitacion_id');

        if (fechaEntrada && fechaSalida && DEBE_CARGAR_HABITACIONES_INICIALES) {
            $('#contenedorHabitaciones').html(spinnerHtml('Preparando reservación rápida...'));

            setTimeout(function() {
                cargarTodasLasHabitaciones(fechaEntrada, fechaSalida);

                if (habitacionId) {
                    const checkInterval = setInterval(function() {
                        const checkbox = $(`input[name="habitaciones[]"][value="${habitacionId}"]`);
                        if (checkbox.length > 0) {
                            clearInterval(checkInterval);
                            checkbox.prop('checked', true).trigger('change');

                            const card = checkbox.closest('.habitacion-card');
                            if (card.length > 0) {
                                setTimeout(function() {
                                    card.addClass('pulse-selection');
                                    if (!card.hasClass('selected')) card.addClass('selected');

                                    Swal.fire({
                                        position: 'top-end', icon: 'success',
                                        title: 'Habitación preseleccionada',
                                        text: `Habitación ${card.find('[data-numero]').data('numero')} lista para reservar`,
                                        showConfirmButton: false, timer: 2000, toast: true
                                    });
                                }, 500);
                            }
                        }
                    }, 100);
                    setTimeout(() => clearInterval(checkInterval), 10000);
                }
            }, 500);
        }
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function normalizarTexto(value) {
        return String(value || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function contieneJacuzzi(hab) {
        return normalizarTexto(`${hab.tipo || ''} ${hab.caracteristicas || ''}`).includes('jacuzzi');
    }

    function tipoRealHabitacion(hab) {
        const tipo = String(hab.tipo || '').trim();
        let label = String(hab.tipo_label || ROOM_TYPE_LABELS[tipo] || tipo.replace(/_/g, ' ') || 'Otros').trim();
        label = label ? label.charAt(0).toUpperCase() + label.slice(1) : 'Otros';

        const normal = normalizarTexto(label);
        if (contieneJacuzzi(hab) && !normal.includes('jacuzzi')) {
            if (normal.includes('doble')) return 'Doble con Jacuzzi';
            if (normal.includes('sencilla') || normal.includes('simple')) return 'Sencilla con Jacuzzi';
            return `${label} con Jacuzzi`;
        }

        return label;
    }

    function ordenTipoHabitacion(label) {
        const normal = normalizarTexto(label);
        if ((normal === 'sencilla' || normal.startsWith('sencilla ')) && !normal.includes('jacuzzi')) return 10;
        if ((normal === 'doble' || normal.startsWith('doble ')) && !normal.includes('jacuzzi')) return 20;
        if (normal.includes('triple')) return 30;
        if (normal.includes('sencilla') && normal.includes('jacuzzi')) return 40;
        if (normal.includes('doble') && normal.includes('jacuzzi')) return 50;
        if (normal.includes('cuadruple') || normal.includes('cuadru')) return 60;
        return 100;
    }

    function capacidadHabitacionLabel(hab) {
        const capacidad = parseInt(hab.capacidad_personas || hab.capacidad || 0, 10);
        if (!capacidad || capacidad < 1) return '';
        return `${capacidad} persona${capacidad === 1 ? '' : 's'}`;
    }

    function camasHabitacionLabel(hab) {
        const mat = parseInt(hab.camas_matrimoniales || 0, 10);
        const ind = parseInt(hab.camas_individuales || 0, 10);
        const total = Math.max(0, mat + ind);
        if (!total) return '';
        return `${total} cama${total === 1 ? '' : 's'}`;
    }

    function estadoHabitacion(hab) {
        const enMant = hab.en_mantenimiento || hab.estado === 'mantenimiento';
        const ocupada = (hab.ocupada || false) && !enMant;
        if (enMant) return 'mantenimiento';
        if (ocupada) return 'ocupada';
        return 'disponible';
    }

    function horaLlegadaModoActual() {
        return $('#hora_llegada_modo').val() || 'manual';
    }

    function horaLlegadaResumen() {
        const modo = horaLlegadaModoActual();
        const hora = $('#hora_llegada').val();
        if (modo === 'despues') return 'Por definir';
        return hora || 'Por definir';
    }

    function aplicarModoHora(modo, options = {}) {
        const modoFinal = ['manual', 'ahora', 'despues'].includes(modo) ? modo : 'manual';
        $('#hora_llegada_modo').val(modoFinal);
        $('.arrival-mode-btn').toggleClass('is-active', false);
        $(`.arrival-mode-btn[data-arrival-mode="${modoFinal}"]`).addClass('is-active');

        if (modoFinal === 'ahora') {
            establecerHoraActual(false);
        } else if (modoFinal === 'despues') {
            $('#hora_llegada').val('').prop('readonly', true);
            $('#arrivalHelpText').html('<i class="fas fa-info-circle"></i>La hora quedara pendiente y no se guardara una hora estimada falsa.');
        } else {
            $('#hora_llegada').prop('readonly', false);
            $('#arrivalHelpText').html('<i class="fas fa-info-circle"></i>Captura la hora estimada de llegada o usa Ahora/Despues.');
            if (!$('#hora_llegada').val() && options.keepEmpty !== true) {
                $('#hora_llegada').val('15:00');
            }
        }

        verificarFormularioCompleto();
        calcularPrecio();
    }

    <?php if (!isset($huesped_preseleccionado)): ?>
    // Buscador de huésped: un solo input visible y el id se mantiene en name="huesped_id".
    const $huespedSelect = $('#huesped_id');
    const $huespedInput = $('#huesped_busqueda');
    const $huespedResults = $('#huesped_results');
    const $huespedBox = $('#huespedSearchBox');
    const $limpiarHuesped = $('#limpiarHuesped');
    let huespedSearchTimer = null;
    let huespedSearchRequest = null;
    let huespedSearchToken = 0;
    let huespedActiveIndex = -1;
    let huespedResultadosActuales = [];

    function inicialesHuesped(nombre) {
        const partes = String(nombre || 'H').trim().split(/\s+/).filter(Boolean);
        return (partes[0]?.[0] || 'H') + (partes[1]?.[0] || '');
    }

    function etiquetaHuesped(data) {
        return `${data.nombre}${data.telefono ? ' - ' + data.telefono : ''}`;
    }

    function setResultadosAbiertos(abierto) {
        $huespedInput.attr('aria-expanded', abierto ? 'true' : 'false');
        $huespedBox.toggleClass('is-open', abierto);
        $huespedResults.toggleClass('hidden', !abierto);
    }

    function estadoResultados(icono, texto, extraClass = '') {
        huespedResultadosActuales = [];
        huespedActiveIndex = -1;
        $huespedResults.html(`
            <div class="res-guest-state ${extraClass}">
                <i class="${icono}" aria-hidden="true"></i>
                <span>${escapeHtml(texto)}</span>
            </div>
        `);
        setResultadosAbiertos(true);
    }

    function normalizarHuesped(huesped) {
        const nombre = huesped.nombre_completo || huesped.nombre || huesped.text || `Huésped #${huesped.id}`;
        return {
            id: String(huesped.id || ''),
            nombre,
            telefono: huesped.telefono || huesped.celular || huesped.telefono_principal || '',
            procedencia: huesped.procedencia_estado || huesped.procedencia || huesped.estado || ''
        };
    }

    function activarResultado(index) {
        huespedActiveIndex = index;
        $huespedResults.find('.res-guest-option').removeClass('is-active').attr('aria-selected', 'false');
        const $option = $huespedResults.find(`.res-guest-option[data-index="${index}"]`);
        $option.addClass('is-active').attr('aria-selected', 'true');
        $option[0]?.scrollIntoView({ block: 'nearest' });
    }

    function renderResultadosHuespedes(rows) {
        huespedResultadosActuales = rows.map(normalizarHuesped).filter(h => h.id);
        huespedActiveIndex = -1;

        if (huespedResultadosActuales.length === 0) {
            estadoResultados('fas fa-user-slash', 'No encontramos huéspedes con esa búsqueda.');
            return;
        }

        $huespedResults.html(huespedResultadosActuales.map((huesped, index) => `
            <button type="button" class="res-guest-option" role="option" aria-selected="false" data-index="${index}">
                <span class="res-guest-avatar">${escapeHtml(inicialesHuesped(huesped.nombre).toUpperCase())}</span>
                <span class="res-guest-option-main">
                    <span class="res-guest-option-name">${escapeHtml(huesped.nombre)}</span>
                    <span class="res-guest-option-meta">${escapeHtml([huesped.telefono || 'Sin teléfono', huesped.procedencia].filter(Boolean).join(' · '))}</span>
                </span>
            </button>
        `).join(''));
        setResultadosAbiertos(true);
    }

    function seleccionarHuesped(data) {
        huespedSeleccionadoActual = data;
        const label = etiquetaHuesped(data);
        $huespedSelect.empty().append(new Option(label, data.id, true, true)).val(data.id).trigger('change');
        $huespedInput.val(label);
        $limpiarHuesped.removeClass('hidden');
        setResultadosAbiertos(false);

        $('#infoHuesped').removeClass('hidden');
        $('#detallesHuesped').html(`
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><strong>Nombre:</strong> ${escapeHtml(data.nombre)}</div>
                <div><strong>Teléfono:</strong> ${escapeHtml(data.telefono || 'No registrado')}</div>
                ${data.procedencia ? `<div class="md:col-span-2"><strong>Procedencia:</strong> ${escapeHtml(data.procedencia)}</div>` : ''}
            </div>
        `);

        verificarFormularioCompleto();
        // Recotizar para aplicar el descuento del huesped
        if (typeof calcularPrecio === 'function' && habitacionesSeleccionadas.length) {
            calcularPrecio();
        }
        if (!ES_RESERVACION_RAPIDA) {
            window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
        }
    }

    function limpiarSeleccionHuesped(mantenerTexto = false) {
        huespedSeleccionadoActual = null;
        $huespedSelect.empty().append(new Option('-- Buscar por nombre o teléfono --', '', true, true)).val('').trigger('change');
        if (!mantenerTexto) {
            $huespedInput.val('');
            $limpiarHuesped.addClass('hidden');
            setResultadosAbiertos(false);
        }
        $('#infoHuesped').addClass('hidden');
        $('#detallesHuesped').empty();
        verificarFormularioCompleto();
        // Quitar el descuento del huesped recotizando sin huesped
        if (typeof calcularPrecio === 'function' && habitacionesSeleccionadas.length) {
            calcularPrecio();
        }
    }

    function procesarRespuestaHuespedes(response, token) {
        if (token !== huespedSearchToken) return;
        const rows = Array.isArray(response) ? response : (response?.success && Array.isArray(response.data) ? response.data : []);
        window.OfflineData?.guardarHuespedes?.(rows);
        renderResultadosHuespedes(rows);
    }

    function buscarHuespedes(termino) {
        if (huespedSearchRequest?.abort) {
            huespedSearchRequest.abort();
        }

        const token = ++huespedSearchToken;
        const sinConexion = !navigator.onLine || window.PWA?.isOnline?.() === false;
        estadoResultados('fas fa-spinner fa-spin', 'Buscando huésped...');

        if (sinConexion && window.OfflineData?.buscarHuespedes) {
            window.OfflineData.buscarHuespedes(termino)
                .then(data => procesarRespuestaHuespedes(data, token))
                .catch(() => estadoResultados('fas fa-triangle-exclamation', 'No se pudo buscar sin conexión.', 'is-error'));
            return;
        }

        huespedSearchRequest = $.ajax({
            url: '<?= url('api/huespedes/search') ?>',
            dataType: 'json',
            data: { q: termino }
        }).done(response => {
            procesarRespuestaHuespedes(response, token);
        }).fail((xhr, status) => {
            if (status !== 'abort') {
                estadoResultados('fas fa-triangle-exclamation', 'No se pudo completar la búsqueda.', 'is-error');
            }
        });
    }

    $huespedInput.on('input', function() {
        const valor = this.value;
        const termino = valor.trim();
        $limpiarHuesped.toggleClass('hidden', valor.length === 0);

        if (huespedSeleccionadoActual && valor !== etiquetaHuesped(huespedSeleccionadoActual)) {
            limpiarSeleccionHuesped(true);
        }

        clearTimeout(huespedSearchTimer);

        if (termino.length === 0) {
            setResultadosAbiertos(false);
            return;
        }

        if (termino.length < 2) {
            estadoResultados('fas fa-keyboard', 'Escriba al menos 2 caracteres para buscar.');
            return;
        }

        huespedSearchTimer = setTimeout(() => buscarHuespedes(termino), 240);
    });

    $huespedInput.on('focus', function() {
        const termino = this.value.trim();
        if (!huespedSeleccionadoActual && termino.length >= 2 && huespedResultadosActuales.length > 0) {
            setResultadosAbiertos(true);
        }
    });

    $huespedInput.on('keydown', function(e) {
        const resultadosAbiertos = !$huespedResults.hasClass('hidden');

        if (e.key === 'Enter') {
            e.preventDefault();
            if (resultadosAbiertos && huespedActiveIndex >= 0) {
                seleccionarHuesped(huespedResultadosActuales[huespedActiveIndex]);
            } else if (resultadosAbiertos && huespedResultadosActuales.length === 1) {
                seleccionarHuesped(huespedResultadosActuales[0]);
            }
            return;
        }

        if (!resultadosAbiertos) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activarResultado(Math.min(huespedActiveIndex + 1, huespedResultadosActuales.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activarResultado(Math.max(huespedActiveIndex - 1, 0));
        } else if (e.key === 'Escape') {
            setResultadosAbiertos(false);
        }
    });

    $huespedResults.on('mousedown', '.res-guest-option', function(e) {
        e.preventDefault();
        const index = Number($(this).data('index'));
        if (Number.isInteger(index) && huespedResultadosActuales[index]) {
            seleccionarHuesped(huespedResultadosActuales[index]);
        }
    });

    $limpiarHuesped.on('click', function() {
        limpiarSeleccionHuesped(false);
        $huespedInput.trigger('focus');
    });

    $(document).on('mousedown', function(e) {
        if (!$(e.target).closest('.res-guest-combobox-wrap').length) {
            setResultadosAbiertos(false);
        }
    });
    <?php else: ?>
    verificarFormularioCompleto();
    <?php endif; ?>

    // ── Search ────────────────────────────────────────────────
    $('#buscarHabitacion').on('input', function() {
        busquedaActiva = $(this).val().toLowerCase();
        aplicarFiltros();
    });

    // ── Date events ───────────────────────────────────────────
    $('#fecha_entrada').on('change', function() {
        const fe = $(this).val();
        if (fe) {
            const e = new Date(fe);
            e.setDate(e.getDate() + 1);
            $('#fecha_salida').val(e.toISOString().split('T')[0]);
            validarFechas();
        }
    });
    $('#fecha_salida').on('change', validarFechas);
    $('#hora_llegada').on('input change', function() {
        if ($(this).val() && horaLlegadaModoActual() === 'despues') {
            aplicarModoHora('manual', { keepEmpty: true });
        }
        verificarFormularioCompleto();
        calcularPrecio();
    });
    $('.arrival-mode-btn').on('click', function() {
        aplicarModoHora($(this).data('arrival-mode'), { keepEmpty: true });
    });
    aplicarModoHora($('#hora_llegada_modo').val() || ($('#hora_llegada').val() ? 'manual' : 'despues'), { keepEmpty: true });

    $('.js-nuevo-huesped-link').on('click', function() {
        const params = new URLSearchParams();
        params.set('return_to', ES_RESERVACION_RAPIDA ? 'reservacion_rapida' : 'reservacion');

        const habitacionId = RESERVA_URL_PARAMS.get('habitacion_id');
        const fechaEntrada = $('#fecha_entrada').val();
        const fechaSalida = $('#fecha_salida').val();
        const horaLlegada = $('#hora_llegada').val();

        if (habitacionId) params.set('habitacion_id', habitacionId);
        if (ES_RESERVACION_RAPIDA || RESERVA_URL_PARAMS.get('preseleccion')) params.set('preseleccion', 'true');
        if (fechaEntrada) params.set('fecha_entrada', fechaEntrada);
        if (fechaSalida) params.set('fecha_salida', fechaSalida);
        if (horaLlegada && horaLlegadaModoActual() !== 'despues') params.set('hora_llegada', horaLlegada);

        this.href = '<?= url('huespedes/create') ?>?' + params.toString();
    });

    // Close mobile summary
    // Barra slim: toque en el total expande/colapsa el detalle hacia arriba
    $('#rfToggle').on('click', function () {
        const expandido = $('#resumenFlotante').toggleClass('expanded').hasClass('expanded');
        $(this).attr('aria-expanded', expandido ? 'true' : 'false');
    });
    $('#resumenReservacion').on('scroll', manejarScrollResumen);

    function manejarScrollResumen() {
        const el = document.getElementById('resumenReservacion');
        const ind = document.getElementById('scrollIndicator');
        if (!el || !ind) return;
        const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;
        ind.classList.toggle('hidden', atBottom || el.scrollHeight <= el.clientHeight);
    }

    function establecerHoraActual(actualizarModo = true) {
        const now = new Date();
        const h = String(now.getHours()).padStart(2,'0');
        const m = String(now.getMinutes()).padStart(2,'0');
        if (actualizarModo) {
            $('#hora_llegada_modo').val('ahora');
            $('.arrival-mode-btn').removeClass('is-active');
            $('.arrival-mode-btn[data-arrival-mode="ahora"]').addClass('is-active');
        }
        $('#hora_llegada').prop('readonly', false);
        $('#arrivalHelpText').html('<i class="fas fa-info-circle"></i>Se guardara la hora actual como llegada estimada.');
        $('#hora_llegada').val(`${h}:${m}`).trigger('change');
    }

    function validarFechas() {
        const fe = $('#fecha_entrada').val();
        const fs = $('#fecha_salida').val();
        if (!fe || !fs) return;

        if (fs <= fe) {
            Swal.fire({
                icon: 'error', title: fs === fe ? 'Fechas iguales' : 'Error en fechas',
                text: fs === fe
                    ? 'Las fechas de entrada y salida no pueden ser iguales'
                    : 'La fecha de salida debe ser posterior a la entrada',
                confirmButtonColor: 'var(--lc-green)'
            });
            $('#fecha_salida').val('');
            return;
        }
        cargarTodasLasHabitaciones(fe, fs);
    }

    // ── Load rooms ────────────────────────────────────────────
    function cargarTodasLasHabitaciones(fe, fs) {
        $('#contenedorHabitaciones').html(spinnerHtml('Cargando habitaciones...'));

        const sinConexion = !navigator.onLine || window.PWA?.isOnline?.() === false;
        if (sinConexion && window.OfflineData?.obtenerHabitaciones) {
            cargarHabitacionesOffline(fe, fs);
            return;
        }

        $.ajax({
            url: '<?= url('api/habitaciones/disponibles') ?>',
            method: 'GET',
            data: { fecha_entrada: fe, fecha_salida: fs },
            success: function(response) {
                if (response.success && response.data) {
                    habitacionesDisponibles = response.data;
                    cargarHabitacionesConOcupacion(fe, fs);
                }
            },
            error: function() {
                cargarHabitacionesOffline(fe, fs);
            }
        });
    }

    async function cargarHabitacionesOffline(fe, fs) {
        try {
            const habitaciones = await window.OfflineData.obtenerHabitaciones(fe, fs);
            if (!habitaciones || habitaciones.length === 0) {
                mostrarHabitaciones([]);
                window.PWA?.showToast('No hay habitaciones guardadas para trabajar offline.', 'warning');
                return;
            }

            habitacionesDisponibles = habitaciones.filter(h =>
                !h.ocupada && !h.en_mantenimiento && h.estado !== 'mantenimiento'
            );
            todasLasHabitaciones = habitaciones;
            habitacionesOcupadas = habitaciones.filter(h => h.ocupada);
            mostrarHabitaciones(todasLasHabitaciones);
        } catch (err) {
            console.error('[ReservacionesOffline] No se pudieron cargar habitaciones:', err);
            Swal.fire({ icon:'error', title:'Sin datos offline', text:'Conectate una vez para guardar habitaciones y poder reservar sin internet.', confirmButtonColor:'var(--lc-green)' });
        }
    }

    function cargarHabitacionesConOcupacion(fe, fs) {
        $.ajax({
            url: '<?= url('api/habitaciones/todas-con-ocupacion') ?>',
            method: 'GET',
            data: { fecha_entrada: fe, fecha_salida: fs },
            success: function(response) {
                if (response.success && response.data) {
                    const idsDisp = habitacionesDisponibles.map(h => h.id.toString());
                    todasLasHabitaciones = response.data.map(h => {
                        const enDisp = idsDisp.includes(h.id.toString());
                        if (!h.en_mantenimiento && !h.ocupada && !enDisp) {
                            h.en_mantenimiento = true;
                            if (h.estado === 'limpieza') h.en_mantenimiento = false;
                        }
                        if (h.estado === 'mantenimiento') h.en_mantenimiento = true;
                        return h;
                    });
                    habitacionesOcupadas = todasLasHabitaciones.filter(h => h.ocupada);
                    mostrarHabitaciones(todasLasHabitaciones);
                }
            },
            error: function() {
                todasLasHabitaciones = habitacionesDisponibles;
                mostrarHabitaciones(habitacionesDisponibles);
            }
        });
    }

    function mostrarHabitaciones(habs) {
        if (!habs.length) {
            $('#contenedorHabitaciones').html(`
                <div class="text-center py-12 text-gray-400">
                    <div style="width:56px;height:56px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="fas fa-bed text-2xl" style="color:#A8C4A0;"></i>
                    </div>
                    <h3 class="text-base font-bold mb-1">No hay habitaciones</h3>
                    <p class="text-sm">No se encontraron habitaciones para estas fechas. Ajusta el rango o revisa disponibilidad.</p>
                </div>
            `);
            return;
        }

        let seleccionInicial = null;
        if (seleccionInicialPendiente) {
            const idsDisponibles = new Set(
                habs
                    .filter(h => estadoHabitacion(h) === 'disponible')
                    .map(h => h.id.toString())
            );
            seleccionInicial = OLD_HABITACIONES.filter(id => idsDisponibles.has(id.toString()));
            habitacionesCortesiaSeleccionadas = OLD_CORTESIAS.filter(id => seleccionInicial.includes(id.toString()));
            seleccionInicialPendiente = false;
        }

        renderizarHabitaciones(habs, seleccionInicial);
    }

    function aplicarFiltros() {
        const prevSel = [];
        $('.habitacion-check:checked').each(function() { prevSel.push($(this).val()); });

        let filtradas = [...todasLasHabitaciones];
        if (busquedaActiva) {
            filtradas = filtradas.filter(h =>
                h.numero.toString().toLowerCase().includes(busquedaActiva) ||
                tipoRealHabitacion(h).toLowerCase().includes(busquedaActiva) ||
                String(h.tipo || '').toLowerCase().includes(busquedaActiva) ||
                String(h.caracteristicas || '').toLowerCase().includes(busquedaActiva)
            );
        }
        renderizarHabitaciones(filtradas, prevSel);
    }

    function renderizarHabitaciones(habs, selPrev = null) {
        const seleccionadas = selPrev || [];
        if (!selPrev) {
            $('.habitacion-check:checked').each(function() { seleccionadas.push($(this).val()); });
        }

        if (!habs.length) {
            $('#contenedorHabitaciones').html(`
                <div class="text-center py-12 text-gray-400">
                    <div style="width:56px;height:56px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                        <i class="fas fa-search text-2xl" style="color:#A8C4A0;"></i>
                    </div>
                    <h3 class="text-base font-bold mb-1">Sin resultados</h3>
                    <p class="text-sm">No hay habitaciones que coincidan con la busqueda actual.</p>
                </div>
            `);
            return;
        }

        // Sort: named rooms first, then numeric
        const esNum = n => /^\d+$/.test(n.toString().trim());
        habs.sort((a, b) => {
            const tipoA = tipoRealHabitacion(a);
            const tipoB = tipoRealHabitacion(b);
            const ordenA = ordenTipoHabitacion(tipoA);
            const ordenB = ordenTipoHabitacion(tipoB);
            if (ordenA !== ordenB) return ordenA - ordenB;
            const tipoCompare = tipoA.localeCompare(tipoB, 'es');
            if (tipoCompare !== 0) return tipoCompare;
            const aNum = esNum(a.numero), bNum = esNum(b.numero);
            if (!aNum && bNum)  return -1;
            if (aNum && !bNum)  return 1;
            if (!aNum && !bNum) return a.numero.toString().localeCompare(b.numero.toString(), 'es');
            return parseInt(a.numero) - parseInt(b.numero);
        });

        const totDisp  = habs.filter(h => !h.ocupada && !h.en_mantenimiento && h.estado !== 'mantenimiento').length;
        const totOcup  = habs.filter(h => h.ocupada && !h.en_mantenimiento && h.estado !== 'mantenimiento').length;
        const totMant  = habs.filter(h => h.en_mantenimiento || h.estado === 'mantenimiento').length;

        let html = `
            <div class="room-stats-bar">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-bold" style="color:#3D5234;">
                        <i class="fas fa-bed mr-1"></i>${habs.length} habitaciones
                    </span>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full" style="background:rgba(92,122,78,.12);color:#3D5234;">${totDisp} disponibles</span>
                    ${totOcup  > 0 ? `<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-700">${totOcup} ocupadas</span>` : ''}
                    ${totMant  > 0 ? `<span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700">${totMant} mantenimiento</span>` : ''}
                </div>
                <span class="text-xs font-bold flex items-center gap-2" style="color:#3D5234;">
                    Seleccionadas:
                    <span id="contadorSeleccionadas" class="contador-habitaciones">0</span>
                </span>
            </div>
        `;

        let grupoActual = null;
        const resumenGrupo = (tipoLabel) => {
            const habitacionesTipo = habs.filter(h => tipoRealHabitacion(h) === tipoLabel);
            const disponibles = habitacionesTipo.filter(h => estadoHabitacion(h) === 'disponible').length;
            const ocupadas = habitacionesTipo.filter(h => estadoHabitacion(h) === 'ocupada').length;
            const mantenimiento = habitacionesTipo.filter(h => estadoHabitacion(h) === 'mantenimiento').length;
            return { total: habitacionesTipo.length, disponibles, ocupadas, mantenimiento };
        };

        habs.forEach(hab => {
            const tipoLabel = tipoRealHabitacion(hab);
            if (grupoActual !== tipoLabel) {
                if (grupoActual !== null) {
                    html += '</div></div></section>';
                }
                const conteo = resumenGrupo(tipoLabel);
                html += `
                    <section class="room-type-group" data-room-type="${escapeHtml(tipoLabel)}">
                        <div class="room-type-group-head">
                            <div class="room-type-title">
                                <h4>${escapeHtml(tipoLabel)}</h4>
                                <p>${conteo.total} habitacion${conteo.total === 1 ? '' : 'es'} en este tipo</p>
                            </div>
                            <div class="room-type-counts">
                                <span style="background:var(--rc-success-soft);color:color-mix(in srgb, var(--rc-success) 82%, #123B2B);"><i class="fas fa-door-open"></i>${conteo.disponibles}</span>
                                ${conteo.ocupadas ? `<span style="background:var(--rc-danger-soft);color:color-mix(in srgb, var(--rc-danger) 86%, #5A1713);"><i class="fas fa-door-closed"></i>${conteo.ocupadas}</span>` : ''}
                                ${conteo.mantenimiento ? `<span style="background:var(--rc-warning-soft);color:color-mix(in srgb, var(--rc-warning) 86%, #5C2C05);"><i class="fas fa-tools"></i>${conteo.mantenimiento}</span>` : ''}
                            </div>
                        </div>
                        <div class="room-type-body">
                            <div class="room-type-grid">
                `;
                grupoActual = tipoLabel;
            }
            const piso = {'-4':'4 niveles abajo','-2':'2 niveles abajo','-1':'Un nivel abajo','1':'Nivel de piso','2':'2º Nivel','3':'3º Nivel'}[hab.piso] || `Piso ${hab.piso}`;
            const checked   = seleccionadas.includes(hab.id.toString());
            const jacuzzi   = contieneJacuzzi(hab);
            const enMant    = hab.en_mantenimiento || hab.estado === 'mantenimiento';
            const ocupada   = (hab.ocupada || false) && !enMant;
            const cortesia  = habitacionesCortesiaSeleccionadas.includes(hab.id.toString());
            const tipoBadge = `<span class="rc-room-chip rc-room-chip--info"><i class="fas fa-tag"></i>${escapeHtml(tipoLabel)}</span>`;
            const jacBadge  = jacuzzi ? `<span class="rc-room-chip rc-room-chip--info"><i class="fas fa-hot-tub"></i>Jacuzzi</span>` : '';
            const capacidadBadge = capacidadHabitacionLabel(hab) ? `<span class="rc-room-chip"><i class="fas fa-users"></i>${escapeHtml(capacidadHabitacionLabel(hab))}</span>` : '';
            const camasBadge = camasHabitacionLabel(hab) ? `<span class="rc-room-chip"><i class="fas fa-bed"></i>${escapeHtml(camasHabitacionLabel(hab))}</span>` : '';
            const precioBase = parseFloat(hab.precio_base).toLocaleString();

            if (enMant) {
                html += `
                    <div class="habitacion-card ocupada en-mantenimiento block relative">
                        <div class="rc-room-card rc-room-card--maintenance relative overflow-hidden">
                            <div class="rc-room-main">
                                <div class="rc-room-number">
                                    <span class="rc-room-label">Habitación</span>
                                    <strong>${hab.numero}</strong>
                                </div>
                                <div class="rc-room-meta">
                                    <div class="rc-room-chips">
                                        <span class="rc-room-chip rc-room-chip--maintenance">
                                            <i class="fas fa-tools"></i>${hab.info_mantenimiento?.programado ? 'Programado' : 'Mantenimiento'}
                                        </span>
                                        ${tipoBadge}
                                        ${jacBadge}
                                        ${capacidadBadge}
                                        ${camasBadge}
                                    </div>
                                    <p class="rc-room-floor"><i class="fas fa-layer-group"></i>${piso}</p>
                                </div>
                                <div class="rc-room-price is-muted">
                                    <strong>$${precioBase}</strong>
                                    <span>No disponible</span>
                                </div>
                            </div>
                            <div class="rc-room-detail info-ocupacion">
                                ${hab.info_mantenimiento ? `
                                    <div class="huesped-nombre">
                                        <i class="fas fa-calendar-alt mr-1"></i>${hab.info_mantenimiento.programado ? 'Programado' : 'En proceso'}
                                    </div>
                                    ${hab.info_mantenimiento.fecha_programada ? `<div class="fechas"><i class="fas fa-clock mr-1"></i>Desde: ${hab.info_mantenimiento.fecha_programada}${hab.info_mantenimiento.fecha_programada_fin ? ' hasta: '+hab.info_mantenimiento.fecha_programada_fin : ''}</div>` : ''}
                                    ${hab.info_mantenimiento.motivo ? `<div class="fechas mt-1"><i class="fas fa-wrench mr-1"></i>${hab.info_mantenimiento.motivo}</div>` : ''}
                                ` : `<div class="huesped-nombre"><i class="fas fa-exclamation-triangle mr-1"></i>No disponible</div>`}
                            </div>
                        </div>
                    </div>
                `;
            } else if (ocupada) {
                html += `
                    <div class="habitacion-card ocupada block relative">
                        <div class="rc-room-card rc-room-card--occupied relative overflow-hidden">
                            <div class="rc-room-main">
                                <div class="rc-room-number">
                                    <span class="rc-room-label">Habitación</span>
                                    <strong>${hab.numero}</strong>
                                </div>
                                <div class="rc-room-meta">
                                    <div class="rc-room-chips">
                                        <span class="rc-room-chip rc-room-chip--occupied"><i class="fas fa-door-closed"></i>Ocupada</span>
                                        ${tipoBadge}
                                        ${jacBadge}
                                        ${capacidadBadge}
                                        ${camasBadge}
                                    </div>
                                    <p class="rc-room-floor"><i class="fas fa-layer-group"></i>${piso}</p>
                                </div>
                                <div class="rc-room-price is-muted">
                                    <strong>$${precioBase}</strong>
                                    <span>No disponible</span>
                                </div>
                            </div>
                            ${hab.info_ocupacion ? `
                                <div class="rc-room-detail info-ocupacion">
                                    <div class="huesped-nombre"><i class="fas fa-user mr-1"></i>${hab.info_ocupacion.huesped_nombre || 'Huésped'}</div>
                                    <div class="fechas">${hab.info_ocupacion.estado === 'checked_in' ? '<span style="color:#16a34a;"><i class="fas fa-check-circle mr-1"></i>Check-in realizado</span>' : '<span style="color:#2563eb;"><i class="fas fa-calendar-check mr-1"></i>Reservada</span>'}</div>
                                    ${hab.info_ocupacion.noches_ocupadas && hab.info_ocupacion.fechas_ocupadas ? `
                                        <div style="margin-top:6px;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:6px;">
                                            <div style="color:#991b1b;font-weight:600;font-size:11px;margin-bottom:2px;"><i class="fas fa-exclamation-triangle mr-1"></i>Ocupada:</div>
                                            ${hab.info_ocupacion.fechas_ocupadas.map(n => `<div style="color:#b91c1c;font-size:10px;">• Noche ${n.noche} (${n.fecha_formateada})</div>`).join('')}
                                        </div>
                                    ` : hab.info_ocupacion.fecha_salida ? `<div class="fechas mt-1"><i class="fas fa-sign-out-alt mr-1"></i>Sale: ${formatearFechaCorta(hab.info_ocupacion.fecha_salida)}</div>` : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <label class="habitacion-card disponible block relative ${checked ? 'selected' : ''} ${cortesia ? 'es-cortesia' : ''}">
                        <input type="checkbox"
                               name="habitaciones[]"
                               value="${hab.id}"
                               data-precio="${hab.precio_base}"
                               data-numero="${hab.numero}"
                               data-tipo="${hab.tipo}"
                               data-tipo-label="${escapeHtml(tipoLabel)}"
                               data-piso="${hab.piso}"
                               data-caracteristicas="${hab.caracteristicas || ''}"
                               class="sr-only habitacion-check"
                               ${checked ? 'checked' : ''}>

                        ${cortesia ? `<div class="badge-cortesia"><i class="fas fa-gift"></i>CORTESÍA</div>` : ''}

                        <div class="rc-room-card relative overflow-hidden transition-all duration-300">
                            <div class="rc-room-main">
                                <div class="rc-room-number">
                                    <span class="rc-room-label">Habitación</span>
                                    <strong>${hab.numero}</strong>
                                </div>
                                <div class="rc-room-meta">
                                    <div class="rc-room-chips">
                                        <span class="rc-room-chip ${cortesia ? 'rc-room-chip--courtesy' : 'rc-room-chip--available'}">
                                            <i class="fas ${cortesia ? 'fa-gift' : 'fa-door-open'}"></i>${cortesia ? 'Cortesía' : 'Disponible'}
                                        </span>
                                        ${tipoBadge}
                                        ${jacBadge}
                                        ${capacidadBadge}
                                        ${camasBadge}
                                    </div>
                                    <p class="rc-room-floor"><i class="fas fa-layer-group"></i>${piso}</p>
                                </div>
                                <div class="rc-room-price ${cortesia ? 'is-muted' : ''}">
                                    <strong>$${precioBase}</strong>
                                    <span>${cortesia ? 'gratis' : 'por noche'}</span>
                                </div>
                            </div>

                            ${hab.caracteristicas ? `
                                <p class="rc-room-features"><i class="fas fa-star"></i>${hab.caracteristicas}</p>
                            ` : ''}

                            <div class="rc-room-footer">
                                <span class="rc-room-action-text">${checked ? 'Seleccionada' : 'Toca para seleccionar'}</span>
                                <div class="rc-room-check w-4 h-4 border-2 border-gray-300 rounded-full transition-all duration-300 flex items-center justify-center">
                                    <i class="fas fa-check text-white text-xs opacity-0 transition-opacity duration-300"></i>
                                </div>
                            </div>
                        </div>
                    </label>
                `;
            }
        });

        if (grupoActual !== null) {
            html += '</div></div></section>';
        }
        $('#contenedorHabitaciones').html(html);

        // Checkbox events
        $('.habitacion-check').on('change', function() {
            const card      = $(this).parent();
            const indicator = card.find('.w-4.h-4 i');
            const contador  = $('#contadorSeleccionadas');

            if ($(this).is(':checked')) {
                card.addClass('selected pulse-selection');
                setTimeout(() => card.removeClass('pulse-selection'), 1000);
                card.find('.w-4.h-4').addClass('bg-green-600 border-green-600');
                card.find('.rc-room-action-text').text('Seleccionada');
                indicator.removeClass('opacity-0');
                contador.addClass('animate');
                setTimeout(() => contador.removeClass('animate'), 300);
                if (navigator.vibrate) navigator.vibrate(50);
            } else {
                card.removeClass('selected pulse-selection es-cortesia');
                card.find('.badge-cortesia').remove();
                card.find('.w-4.h-4').removeClass('bg-green-600 border-green-600');
                card.find('.rc-room-action-text').text('Toca para seleccionar');
                indicator.addClass('opacity-0');

                const habId = $(this).val();
                const idx = habitacionesCortesiaSeleccionadas.indexOf(habId);
                if (idx > -1) habitacionesCortesiaSeleccionadas.splice(idx, 1);
            }
            actualizarSeleccion();
        });

        actualizarSeleccion();
    }

    // ── Selection state ───────────────────────────────────────
    function actualizarSeleccion() {
        habitacionesSeleccionadas = [];
        $('.habitacion-check:checked').each(function() {
            habitacionesSeleccionadas.push({
                id:             $(this).val(),
                precio:         parseFloat($(this).data('precio')),
                numero:         $(this).data('numero'),
                tipo:           $(this).data('tipo'),
                tipo_label:     $(this).data('tipo-label') || $(this).data('tipo'),
                piso:           $(this).data('piso'),
                caracteristicas:$(this).data('caracteristicas')
            });
        });

        const total = habitacionesSeleccionadas.length;
        $('#contadorSeleccionadas').text(total);

        if (total > 0) {
            $('#seccionCortesias').removeClass('hidden');
            mostrarOpcionesCortesias(total);
        } else {
            $('#seccionCortesias').addClass('hidden');
            habitacionesCortesiaSeleccionadas = [];
        }

        if (total > 0) {
            calcularPrecio();
            if (window.innerWidth < 1280) $('#resumenFlotante').addClass('activo');
        } else {
            const emptyHtml = `
                <div class="text-center py-8">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <i class="fas fa-bed" style="color:#A8C4A0;"></i>
                    </div>
                    <p class="text-gray-400 text-sm">Seleccione habitaciones para ver el resumen</p>
                </div>
            `;
            $('#resumenReservacion, #resumenMovil').html(emptyHtml);
            $('#resumenFlotante').removeClass('activo expanded');
            $('#rfTotal').text('$0');
            $('#rfMeta').text('Selecciona habitaciones');
            $('#rfToggle').attr('aria-expanded', 'false');
        }
        verificarFormularioCompleto();
    }

    // ── Courtesy options ──────────────────────────────────────
    function mostrarOpcionesCortesias(maxCort) {
        let html = '';
        habitacionesSeleccionadas.forEach(hab => {
            const esCort = habitacionesCortesiaSeleccionadas.includes(hab.id.toString());
            html += `
                <div class="item-cortesia ${esCort ? 'activa' : ''}">
                    <div class="flex items-center gap-3">
                        <input type="checkbox"
                               class="checkbox-cortesia"
                               data-habitacion-id="${hab.id}"
                               ${esCort ? 'checked' : ''}
>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">Habitación ${hab.numero}</p>
                            <p class="text-xs text-gray-500">${escapeHtml(hab.tipo_label || hab.tipo || 'Habitacion')} · $${hab.precio.toLocaleString()}/noche</p>
                        </div>
                    </div>
                    <p class="text-sm font-bold ${esCort ? 'text-amber-600' : 'text-gray-300'}">${esCort ? 'GRATIS' : ''}</p>
                </div>
            `;
        });
        $('#listaCortesias').html(html);

        $('.checkbox-cortesia').on('change', function() {
            const habId = $(this).data('habitacion-id').toString();

            if ($(this).is(':checked')) {
                                habitacionesCortesiaSeleccionadas.push(habId);
                const card = $(`.habitacion-check[value="${habId}"]`).parent();
                card.addClass('es-cortesia');
                if (!card.find('.badge-cortesia').length) {
                    card.prepend(`<div class="badge-cortesia"><i class="fas fa-gift"></i>CORTESÍA</div>`);
                }
                $(this).closest('.item-cortesia').addClass('activa');
            } else {
                const idx = habitacionesCortesiaSeleccionadas.indexOf(habId);
                if (idx > -1) habitacionesCortesiaSeleccionadas.splice(idx, 1);

                const card = $(`.habitacion-check[value="${habId}"]`).parent();
                card.removeClass('es-cortesia');
                card.find('.badge-cortesia').remove();
                $(this).closest('.item-cortesia').removeClass('activa');
            }

            calcularPrecio();
        });
    }

    // ── Price calculation ─────────────────────────────────────
    let descuentoOverrideResumen = null; // null = descuento automatico; numero = ajuste manual
    let ultimoResumenData = null;        // ultimo objeto pasado a actualizarResumen (para re-render al ajustar)

    function calcularPrecio() {
        const fe = $('#fecha_entrada').val();
        const fs = $('#fecha_salida').val();
        if (!fe || !fs || !habitacionesSeleccionadas.length) return;

        let noches = Math.ceil((new Date(fs) - new Date(fe)) / 86400000);
        if (noches === 0) noches = 1;

        let precioTotal = 0, precioSinDesc = 0;
        habitacionesSeleccionadas.forEach(hab => {
            const p = hab.precio * noches;
            precioSinDesc += p;
            if (!habitacionesCortesiaSeleccionadas.includes(hab.id.toString())) precioTotal += p;
        });

        // Render inmediato con el estimado local (respaldo / offline)
        descuentoOverrideResumen = null;
        actualizarResumen({
            noches,
            totalHabs:         habitacionesSeleccionadas.length,
            habsCortesia:      habitacionesCortesiaSeleccionadas.length,
            precioTotal,
            precioSinDescuento: precioSinDesc,
            ahorro:            precioSinDesc - precioTotal,
            habitaciones:      habitacionesSeleccionadas,
            habitacionesCortesia: habitacionesCortesiaSeleccionadas,
            subtotal: precioTotal, descuentoTipo: 0, descuentoHuesped: 0, descuentoTotal: 0
        });

        // Cotizacion precisa (incrementos + descuentos por tipo/huesped) via el motor del backend.
        // Solo cuando no hay cortesias seleccionadas (esas se resuelven al guardar).
        if (habitacionesCortesiaSeleccionadas.length > 0) return;

        const hid = $('input[name="huesped_id"]').val() || $('#huesped_id').val() || '';
        const hl = $('#hora_llegada').val() || '';
        const params = new URLSearchParams({
            habitaciones: habitacionesSeleccionadas.map(h => h.id).join(','),
            fecha_entrada: fe, fecha_salida: fs, hora_llegada: hl
        });
        if (hid) params.append('huesped_id', hid);

        fetch(`<?= url('api/habitaciones/calcular-precio') ?>?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.ok ? r.json() : null)
            .then(resp => {
                if (!resp || !resp.success || !resp.data) return;
                const dd = resp.data;
                descuentoOverrideResumen = null;
                actualizarResumen({
                    noches,
                    totalHabs:         habitacionesSeleccionadas.length,
                    habsCortesia:      0,
                    precioTotal:       Number(dd.precio_con_descuento != null ? dd.precio_con_descuento : dd.precio_total),
                    precioSinDescuento: Number(dd.subtotal != null ? dd.subtotal : dd.precio_total),
                    ahorro:            0,
                    habitaciones:      habitacionesSeleccionadas,
                    habitacionesCortesia: [],
                    subtotal:          Number(dd.subtotal != null ? dd.subtotal : dd.precio_total),
                    descuentoTipo:     Number(dd.descuento_tipo || 0),
                    descuentoHuesped:  Number(dd.descuento_huesped || 0),
                    descuentoTotal:    Number(dd.descuento_total || 0)
                });
            })
            .catch(() => { /* respaldo: ya se mostro el estimado local */ });
    }

    function actualizarResumen(d) {
        const fe = $('#fecha_entrada').val();
        const fs = $('#fecha_salida').val();
        const pisoLabel = p => ({'-4':'4 niveles abajo','-2':'2 niveles abajo','-1':'Un nivel abajo','1':'Nivel de piso','2':'2º Nivel','3':'3º Nivel'}[p] || `Piso ${p}`);

        let html = '<div class="space-y-4">';

        // Dates row
        html += `
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Check-in:</span><span class="font-semibold text-gray-700">${formatearFecha(fe)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Check-out:</span><span class="font-semibold text-gray-700">${formatearFecha(fs)}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Llegada:</span><span class="font-semibold text-gray-700">${horaLlegadaResumen()}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Noches:</span><span class="font-semibold text-gray-700">${d.noches}</span></div>
                <div class="flex justify-between"><span class="text-gray-500">Habitaciones:</span><span class="font-semibold text-gray-700">${d.totalHabs}</span></div>
            </div>
        `;

        // Room list
        if (d.habitaciones.length) {
            html += `<div style="background:#FAFDF8;border:1px solid #DDE8D5;border-radius:10px;padding:12px;">
                <p class="text-xs font-bold uppercase tracking-wider mb-2" style="color:#7A9B6A;">Habitaciones</p>
                <div class="space-y-1">`;
            d.habitaciones.forEach(hab => {
                const cort = d.habitacionesCortesia?.includes(hab.id.toString());
                html += `
                    <div class="flex justify-between items-center text-xs rounded px-2 py-1.5 ${cort ? 'border-2 border-amber-300' : 'border border-[#EAF0E5]'}" style="background:${cort?'#FFFBEB':'white'};">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-gray-700">Hab. ${hab.numero}</span>
                            <span class="text-gray-400">${pisoLabel(hab.piso)}</span>
                            ${normalizarTexto(`${hab.tipo_label || ''} ${hab.tipo || ''} ${hab.caracteristicas || ''}`).includes('jacuzzi') ? '<i class="fas fa-hot-tub text-blue-400 text-xs"></i>' : ''}
                            ${cort ? '<span class="bg-amber-500 text-white px-1.5 py-0.5 rounded text-xs font-bold">CORTESÍA</span>' : ''}
                        </div>
                        <span class="font-bold ${cort ? 'text-amber-500 line-through' : ''}" style="${cort?'':'color:var(--lc-green);'}">$${(hab.precio * d.noches).toLocaleString()}</span>
                    </div>
                `;
            });
            html += '</div></div>';
        }

        // Courtesy savings
        if (d.habsCortesia > 0) {
            html += `
                <div style="background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:10px;padding:12px;">
                    <p class="text-sm font-bold text-amber-800"><i class="fas fa-gift mr-1"></i>¡Cortesías aplicadas!</p>
                    <p class="text-xs text-amber-700">${d.habsCortesia} habitación${d.habsCortesia>1?'es':''} de cortesía</p>
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-gray-400">Subtotal:</span><span class="text-gray-400 line-through">$${d.precioSinDescuento.toLocaleString()}</span></div>
                    <div class="flex justify-between"><span class="text-amber-600 font-semibold">Descuento:</span><span class="text-amber-600 font-semibold">-$${d.ahorro.toLocaleString()}</span></div>
                </div>
            `;
        }

        // ===== Descuento de precio (por tipo + por huésped) =====
        ultimoResumenData = d;
        const autoDesc = Number(d.descuentoTotal || 0);
        const subBase = Number(d.subtotal != null ? d.subtotal : d.precioSinDescuento);
        let descAplicado = (descuentoOverrideResumen !== null) ? Number(descuentoOverrideResumen) : autoDesc;
        if (!isFinite(descAplicado) || descAplicado < 0) descAplicado = 0;
        if (descAplicado > subBase) descAplicado = subBase;
        const inpDesc = document.getElementById('descuento_aplicado');
        if (inpDesc) inpDesc.value = descAplicado.toFixed(2);

        const hayDescuento = (autoDesc > 0 || descAplicado > 0);
        if (hayDescuento) {
            const dt = Number(d.descuentoTipo || 0), dh = Number(d.descuentoHuesped || 0);
            html += `<div class="space-y-1 text-sm">`;
            html += `<div class="flex justify-between"><span class="text-gray-500">Subtotal:</span><span class="font-semibold text-gray-700">$${subBase.toLocaleString()}</span></div>`;
            if (dt > 0) html += `<div class="flex justify-between"><span class="text-rose-600">Descuento por tipo:</span><span class="text-rose-600 font-semibold">-$${dt.toLocaleString()}</span></div>`;
            if (dh > 0) html += `<div class="flex justify-between"><span class="text-rose-600">Descuento del huésped:</span><span class="text-rose-600 font-semibold">-$${dh.toLocaleString()}</span></div>`;
            html += `<div class="flex justify-between"><span class="text-rose-700 font-bold">Descuento aplicado:</span><span class="text-rose-700 font-bold">-$${descAplicado.toLocaleString()}</span></div>`;
            html += `<div class="flex items-center gap-2 pt-1">
                        <span class="text-gray-500 text-xs">Ajustar:</span>
                        <input type="number" min="0" step="0.01" value="${descAplicado.toFixed(2)}" id="descuento_input_visible" class="form-input" style="max-width:120px;font-size:.8rem;" onchange="ajustarDescuentoResumen(this.value)">
                        <button type="button" class="text-gray-500 underline text-xs" onclick="restaurarDescuentoAutoResumen()">Auto</button>
                        <button type="button" class="text-gray-500 underline text-xs" onclick="quitarDescuentoResumen()">Quitar</button>
                     </div>`;
            html += `</div>`;
        }

        const displayTotal = hayDescuento ? (subBase - descAplicado) : Number(d.precioTotal || 0);

        // Total
        html += `
            <div class="total-box">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-sm" style="color:#4A6340;">Total a pagar:</span>
                    <span class="font-black text-xl" style="color:var(--lc-green);">$${displayTotal.toLocaleString()}</span>
                </div>
            </div>
        </div>`;

        $('#resumenReservacion').html(html);

        // Mobile summary
        $('#resumenMovil').html(`
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <div class="text-sm">
                        <p class="font-bold text-gray-700">${d.totalHabs} hab. × ${d.noches} noche${d.noches>1?'s':''}</p>
                        ${d.habsCortesia>0 ? `<p class="text-xs text-amber-600">${d.habsCortesia} cortesía${d.habsCortesia>1?'s':''}</p>` : ''}
                        ${hayDescuento ? `<p class="text-xs text-rose-600">Descuento: -$${descAplicado.toLocaleString()}</p>` : ''}
                    </div>
                    <p class="font-black text-lg" style="color:var(--lc-green);">$${displayTotal.toLocaleString()}</p>
                </div>
                <div class="text-xs border-t border-[#EAF0E5] pt-2 space-y-1 max-h-[100px] overflow-y-auto">
                    ${d.habitaciones.map(h => {
                        const c = d.habitacionesCortesia?.includes(h.id.toString());
                        return `<div class="flex justify-between text-gray-500">
                            <span>Hab. ${h.numero}${c?' (Cortesía)':''}</span>
                            <span ${c?'class="line-through"':''}>$${(h.precio*d.noches).toLocaleString()}</span>
                        </div>`;
                    }).join('')}
                </div>
            </div>
        `);

        // Barra slim: total + meta siempre visibles
        $('#rfTotal').text('$' + displayTotal.toLocaleString());
        $('#rfMeta').text(`${d.totalHabs} hab · ${d.noches} noche${d.noches > 1 ? 's' : ''}`);
    }

    // Ajuste manual del descuento por el operador (override). Re-renderiza con el ultimo desglose.
    function ajustarDescuentoResumen(valor) {
        const n = parseFloat(String(valor).replace(/,/g, ''));
        descuentoOverrideResumen = (isFinite(n) && n >= 0) ? n : 0;
        if (ultimoResumenData) actualizarResumen(ultimoResumenData);
    }
    function quitarDescuentoResumen() { ajustarDescuentoResumen(0); }
    function restaurarDescuentoAutoResumen() {
        descuentoOverrideResumen = null;
        if (ultimoResumenData) actualizarResumen(ultimoResumenData);
    }
    window.ajustarDescuentoResumen = ajustarDescuentoResumen;
    window.quitarDescuentoResumen = quitarDescuentoResumen;
    window.restaurarDescuentoAutoResumen = restaurarDescuentoAutoResumen;

    // ── Helpers ───────────────────────────────────────────────
    function formatearFecha(f) {
        const [y,m,d] = f.split('-');
        return `${d} ${'Ene Feb Mar Abr May Jun Jul Ago Sep Oct Nov Dic'.split(' ')[+m-1]} ${y}`;
    }
    function formatearFechaCorta(f) {
        const [y,m,d] = f.split('-'); return `${d}/${m}/${y}`;
    }

    function spinnerHtml(msg='Cargando...') {
        return `<div class="text-center py-10">
            <div style="width:52px;height:52px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                <i class="fas fa-spinner fa-spin text-xl" style="color:var(--lc-green);"></i>
            </div>
            <p class="text-gray-400 text-sm">${msg}</p>
        </div>`;
    }

    function verificarFormularioCompleto() {
        const hid  = $('input[name="huesped_id"]').val() || $('#huesped_id').val();
        const fe   = $('#fecha_entrada').val();
        const fs   = $('#fecha_salida').val();
        const hl   = $('#hora_llegada').val();
        const horaOk = horaLlegadaModoActual() === 'despues' || !!hl;
        const habs = $('.habitacion-check:checked').length;
        const ok   = hid && fe && fs && horaOk && habs > 0;
        $('#btnGuardar, #btnGuardarMovil').prop('disabled', !ok);
        $('#btnCotizacion, #btnCotizacionMovil').prop('disabled', !ok);
    }

    async function estaSinConexionReal() {
        if (!navigator.onLine) return true;
        if (window.PWA?.checkOnline) {
            return !(await window.PWA.checkOnline(true));
        }
        return window.PWA?.isOnline?.() === false;
    }

    function obtenerHuespedSeleccionado() {
        const id = $('input[name="huesped_id"]').val() || $('#huesped_id').val();

        if (HUESPED_PRESELECCIONADO) {
            return {
                id: HUESPED_PRESELECCIONADO.id,
                nombre_completo: HUESPED_PRESELECCIONADO.nombre_completo,
                telefono: HUESPED_PRESELECCIONADO.telefono || '',
                procedencia_estado: HUESPED_PRESELECCIONADO.procedencia_estado || '',
            };
        }

        const data = huespedSeleccionadoActual || {};
        const selectedText = $('#huesped_id option:selected').text() || '';
        return {
            id,
            nombre_completo: data.nombre || selectedText.replace(/\s-\s.*$/, '') || `Huesped #${id}`,
            telefono: data.telefono || '',
            procedencia_estado: data.procedencia || '',
        };
    }

    function calcularTotalLocal() {
        const fe = $('#fecha_entrada').val();
        const fs = $('#fecha_salida').val();
        let noches = Math.ceil((new Date(fs) - new Date(fe)) / 86400000);
        if (noches <= 0) noches = 1;

        return habitacionesSeleccionadas.reduce((total, hab) => {
            if (habitacionesCortesiaSeleccionadas.includes(hab.id.toString())) return total;
            return total + (Number(hab.precio || 0) * noches);
        }, 0);
    }

    async function crearReservacionOffline() {
        if (!window.OfflineData) {
            Swal.fire({ icon:'error', title:'Offline no disponible', text:'No se pudo abrir el almacenamiento local.', confirmButtonColor:'var(--lc-green)' });
            return;
        }

        const huesped = obtenerHuespedSeleccionado();
        const fe = $('#fecha_entrada').val();
        const fs = $('#fecha_salida').val();
        const hl = horaLlegadaModoActual() === 'despues' ? null : $('#hora_llegada').val();
        const notas = $('textarea[name="notas"]').val() || '';
        const tempId = `tmp_res_${Date.now()}_${Math.random().toString(16).slice(2)}`;
        const total = calcularTotalLocal();

        const payload = {
            client_temp_id: tempId,
            huesped_id: Number(huesped.id),
            fecha_entrada: fe,
            fecha_salida: fs,
            hora_llegada: hl,
            habitaciones: habitacionesSeleccionadas.map(h => Number(h.id)),
            cortesias: habitacionesCortesiaSeleccionadas.map(id => Number(id)),
            notas,
            precio_total: total,
            estado: 'confirmada',
        };

        const localReservacion = {
            id: tempId,
            huesped_id: Number(huesped.id),
            huesped_nombre: huesped.nombre_completo,
            huesped_telefono: huesped.telefono || '',
            fecha_entrada: fe,
            fecha_salida: fs,
            hora_llegada_estimada: hl,
            precio_total: total,
            estado: 'confirmada',
            notas,
            total_habitaciones: habitacionesSeleccionadas.length,
            habitaciones_numeros: habitacionesSeleccionadas.map(h => h.numero).join(', '),
            habitaciones_ids: habitacionesSeleccionadas.map(h => h.id).join(','),
            habitaciones_tipos: habitacionesSeleccionadas.map(h => h.tipo || '').join('||'),
            offline_pendiente: true,
        };

        try {
            await window.OfflineData.guardarHuespedes?.([huesped]);
            await window.OfflineData.encolarOperacion(
                'crear_reservacion',
                payload,
                `Crear reservacion ${localReservacion.habitaciones_numeros} - ${huesped.nombre_completo}`
            );
            await window.OfflineData.guardarReservacionLocal(localReservacion);

            await Swal.fire({
                icon: 'success',
                title: 'Reservacion guardada',
                text: 'Quedo lista en este equipo. Podras hacer check-in o check-out y se sincronizara al volver internet.',
                confirmButtonColor: 'var(--lc-green)'
            });
            window.location.href = '<?= url('reservaciones') ?>';
        } catch (err) {
            console.error('[ReservacionesOffline] Error al crear reservacion offline:', err);
            Swal.fire({ icon:'error', title:'No se pudo guardar offline', text:'Revisa los datos e intenta de nuevo.', confirmButtonColor:'var(--lc-green)' });
        }
    }

    // ── Form submit ───────────────────────────────────────────
    $('#formReservacion').on('submit', function(e) {
        e.preventDefault();
        if (this.dataset.enviandoReservacion === '1') return;
        const totalHabs        = $('.habitacion-check:checked').length;
        const cortesiasAplicadas = habitacionesCortesiaSeleccionadas.length;

        // Inject hidden inputs para cortesias
        const $formRef = $(this);
        $('.cortesia-hidden').remove();
        habitacionesCortesiaSeleccionadas.forEach(id => {
            $formRef.append('<input type="hidden" name="cortesias[]" value="' + id + '" class="cortesia-hidden">');
        });

        let texto = `Se creará la reservación con ${totalHabs} habitación${totalHabs>1?'es':''}.`;
        if (cortesiasAplicadas > 0) {
            texto += `\n\n🎁 ${cortesiasAplicadas} habitación${cortesiasAplicadas>1?'es':''} de cortesía aplicada${cortesiasAplicadas>1?'s':''}.`;
        }

        const formRef = this;
        Swal.fire({
            title: '¿Confirmar reservación?',
            text: texto,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--lc-green)',
            cancelButtonColor: '#6B7280',
            confirmButtonText: '<i class="fas fa-check mr-2"></i>Crear reservación',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then(async result => {
            if (!result.isConfirmed) return;

            if (await estaSinConexionReal()) {
                crearReservacionOffline();
                return;
            }

            formRef.dataset.enviandoReservacion = '1';
            $('#btnGuardar, #btnGuardarMovil').prop('disabled', true);
            Swal.fire({
                title: 'Guardando reservacion...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(formRef.action, {
                    method: formRef.method || 'POST',
                    body: new FormData(formRef),
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const payload = await response.json().catch(() => ({}));

                if (payload.redirect) {
                    window.location.assign(payload.redirect);
                    return;
                }

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || 'No se pudo guardar la reservacion.');
                }

                window.location.assign('<?= url('reservaciones') ?>');
            } catch (err) {
                console.error('[Reservaciones] Error al guardar reservacion:', err);
                delete formRef.dataset.enviandoReservacion;
                verificarFormularioCompleto();
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    text: err.message || 'Revisa los datos e intenta de nuevo.',
                    confirmButtonColor: 'var(--lc-green)'
                });
            }
        });
    });
    // ── Cotización PDF ────────────────────────────────────────
$('#btnCotizacion, #btnCotizacionMovil').on('click', function() {
    const huespedId = $('input[name="huesped_id"]').val() || $('#huesped_id').val();
    const fe = $('#fecha_entrada').val();
    const fs = $('#fecha_salida').val();
    const hl = horaLlegadaModoActual() === 'despues' ? '' : $('#hora_llegada').val();
    const notas = $('textarea[name="notas"]').val() || '';
    const habsChecked = $('.habitacion-check:checked');

    if (!huespedId || !fe || !fs || (horaLlegadaModoActual() !== 'despues' && !hl) || habsChecked.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Datos incompletos',
            text: 'Complete todos los campos y seleccione al menos una habitación.',
            confirmButtonColor: 'var(--lc-green)'
        });
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= url("reservaciones/cotizacion-pdf") ?>';
    form.target = '_blank';

    const addHidden = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    };

    const csrfToken = $('input[name="csrf_token"]').val();
    if (csrfToken) addHidden('csrf_token', csrfToken);

    addHidden('huesped_id', huespedId);
    addHidden('fecha_entrada', fe);
    addHidden('fecha_salida', fs);
    addHidden('hora_llegada_modo', horaLlegadaModoActual());
    addHidden('hora_llegada', hl);
    addHidden('notas', notas);

    const visibleDescuento = document.getElementById('descuento_input_visible');
    const hiddenDescuento = document.getElementById('descuento_aplicado');
    if (visibleDescuento && hiddenDescuento) {
        const descuentoManual = parseFloat(String(visibleDescuento.value || '').replace(/,/g, ''));
        if (isFinite(descuentoManual) && descuentoManual >= 0) {
            hiddenDescuento.value = descuentoManual.toFixed(2);
        }
    }
    const descuentoAplicado = hiddenDescuento ? String(hiddenDescuento.value || '').trim() : '';
    if (descuentoAplicado !== '') {
        addHidden('descuento_aplicado', descuentoAplicado);
    }

    habsChecked.each(function() {
        addHidden('habitaciones[]', $(this).val());
    });

    if (typeof habitacionesCortesiaSeleccionadas !== 'undefined') {
        habitacionesCortesiaSeleccionadas.forEach(id => {
            addHidden('cortesias[]', id);
        });
    }

    document.body.appendChild(form);
    if (window.MedisoftMobileFiles && window.MedisoftMobileFiles.isMobile()) {
        window.MedisoftMobileFiles.showHint('cotizacion PDF', false);
    }
    form.submit();
    document.body.removeChild(form);
});

window.toggleTipoTarjetaAnticipoInicial = function() {
    const metodo = document.querySelector('[data-anticipo-inicial-metodo]');
    const panel = document.querySelector('[data-anticipo-inicial-tarjeta]');
    if (!metodo || !panel) {
        return;
    }

    const montoInput = document.querySelector('[data-anticipo-inicial-monto]');
    const monto = montoInput ? parseFloat(String(montoInput.value || '').replace(/,/g, '')) : 0;
    const mostrar = metodo.value === 'tarjeta';
    const requerir = mostrar && isFinite(monto) && monto > 0;
    panel.style.display = mostrar ? 'block' : 'none';
    panel.querySelectorAll('input[name="tipo_tarjeta_anticipo_inicial"]').forEach((input) => {
        input.disabled = !mostrar;
        input.required = requerir;
        if (!mostrar) {
            input.checked = false;
        }
    });
};

window.toggleTipoTarjetaAnticipoInicial();
});
</script>
