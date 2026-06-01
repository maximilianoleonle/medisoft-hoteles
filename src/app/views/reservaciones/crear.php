<?php

date_default_timezone_set('America/Mexico_City');
/**
 * Vista de Crear Reservación - CON SELECCIÓN MANUAL DE CORTESÍAS
 * Los Cedros — Paleta sage green / gold / cream
 */
 
$habitacion_preseleccionada = $_GET['habitacion_id'] ?? null;
$fecha_entrada_pre = $_GET['fecha_entrada'] ?? null; 
$fecha_salida_pre = $_GET['fecha_salida'] ?? null;
$hora_llegada_pre = $_GET['hora_llegada'] ?? null;
$es_preseleccion = $_GET['preseleccion'] ?? null;
?>

<style>
/* ══════════════════════════════════════════
   LOS CEDROS · Nueva Reservación
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
                        <h1 style="color:white;font-size:1.2rem;font-weight:800;line-height:1.2;">Nueva Reservación</h1>
                        <p style="color:rgba(255,255,255,.6);font-size:.75rem;margin-top:2px;">Seleccione habitaciones y configure cortesías del hotel</p>
                    </div>
                </div>
                <a href="<?= url('reservaciones') ?>" class="btn-back">
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
                <strong>Reservación rápida.</strong> Las fechas y habitación han sido prellenadas automáticamente.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Main grid ── -->
    <div class="px-5 sm:px-7 py-5 pb-24 xl:pb-6">
        <form method="POST" action="<?= url('reservaciones/guardar') ?>" id="formReservacion">
            <?= csrf_field() ?>

            <div class="grid grid-cols-1 xl:grid-cols-4 gap-5">

                <!-- ════ Left column (3/4) ════ -->
                <div class="xl:col-span-3 space-y-5">

                    <!-- ── 1. Huésped ── -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden card-animate">
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
                                        <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                            Buscar Huésped <span class="text-red-500">*</span>
                                        </label>
                                        <div class="flex gap-3">
                                            <select name="huesped_id" id="huesped_id" class="flex-1" required>
                                                <option value="">-- Buscar por nombre o teléfono --</option>
                                            </select>
                                            <a href="<?= url('huespedes/create?return_to=reservacion') ?>" class="btn-gold">
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
                                </div>

                                <!-- Hora -->
                                <div>
                                    <label class="block text-sm font-bold mb-2" style="color:#4A6340;">
                                        <i class="fas fa-clock mr-1" style="color:var(--lc-green);"></i>
                                        Hora de Llegada <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-2">
                                        <input type="time" name="hora_llegada" id="hora_llegada"
                                               class="lc-input flex-1"
                                               value="<?= old('hora_llegada', $hora_llegada_pre) ?>">
                                        <button type="button" id="btnHoraActual" class="btn-hour" title="Hora actual">
                                            <i class="fas fa-clock text-sm"></i>
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-1.5 flex items-center gap-1">
                                        <i class="fas fa-info-circle"></i>
                                        Check-in oficial: 3:00 PM
                                    </p>
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
                    </div>
                </div>

                <!-- ════ Right column (1/4) ════ -->
                <div class="xl:col-span-1 space-y-4">

                    <!-- Summary panel -->
                    <div class="bg-white rounded-2xl shadow-sm border border-[#DDE8D5] overflow-hidden sticky top-5">
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

                            <div class="border-t border-[#EAF0E5] pt-4 mt-4 space-y-2.5 bg-white">
                                <button type="submit" id="btnGuardar" disabled class="btn-save">
                                    <i class="fas fa-save"></i>
                                    <span>Guardar Reservación</span>
                                </button>
                                <button type="button" id="btnCotizacion" disabled class="btn-cotizacion">
    <i class="fas fa-file-pdf"></i>
    <span>Generar Cotización PDF</span>
</button>
                                <a href="<?= url('reservaciones') ?>" class="btn-cancel">
                                    <i class="fas fa-times text-xs"></i>
                                    <span>Cancelar</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Info card -->
                    <div class="info-card">
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

<!-- ── Mobile floating summary ── -->
<div class="resumen-flotante" id="resumenFlotante">
    <div class="p-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="font-bold text-gray-800">Resumen de Reservación</h4>
            <button type="button" id="btnCerrarResumen" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="resumenMovil" class="mb-4 max-h-[200px] overflow-y-auto" style="scrollbar-width:thin;">
            <p class="text-gray-400 text-sm text-center">Sin habitaciones seleccionadas</p>
        </div>
        <button type="submit" form="formReservacion" id="btnGuardarMovil" disabled class="btn-save">
            <i class="fas fa-save"></i>
            <span>Guardar Reservación</span>
        </button>
        <button type="button" id="btnCotizacionMovil" disabled class="btn-cotizacion mt-2">
    <i class="fas fa-file-pdf"></i>
    <span>Cotización PDF</span>
</button>
    </div>
</div>

<!-- ── Libraries ── -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
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
    const HUESPED_PRESELECCIONADO = <?= json_encode($huesped_preseleccionado ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    // Boot
    $('.vista-reservacion').addClass('loaded');
    inicializarReservacionRapida();

    function inicializarReservacionRapida() {
        const fechaEntrada = $('#fecha_entrada').val();
        const fechaSalida  = $('#fecha_salida').val();
        const urlParams    = new URLSearchParams(window.location.search);
        const esPresel     = urlParams.get('preseleccion');
        const habitacionId = urlParams.get('habitacion_id');

        if (fechaEntrada && fechaSalida && (esPresel || habitacionId)) {
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
                                    card[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
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

    <?php if (!isset($huesped_preseleccionado)): ?>
    // Select2 - guest search
    $('#huesped_id').select2({
        language: 'es',
        placeholder: 'Buscar huésped por nombre o teléfono...',
        minimumInputLength: 2,
        ajax: {
            url: '<?= url('api/huespedes/search') ?>',
            dataType: 'json', delay: 250,
            data: params => ({ q: params.term }),
            transport: function(params, success, failure) {
                const termino = params.data?.q || '';
                const sinConexion = !navigator.onLine || window.PWA?.isOnline?.() === false;

                if (sinConexion && window.OfflineData?.buscarHuespedes) {
                    window.OfflineData.buscarHuespedes(termino)
                        .then(data => success({ success: true, data }))
                        .catch(failure);
                    return { abort: function() {} };
                }

                const request = $.ajax(params);
                request.then(success);
                request.fail(failure);
                return request;
            },
            processResults: function(response) {
                if (response.success && response.data) {
                    window.OfflineData?.guardarHuespedes?.(response.data);
                    return {
                        results: response.data.map(h => ({
                            id: h.id,
                            text: h.nombre_completo + ' - ' + (h.telefono || 'Sin teléfono'),
                            nombre: h.nombre_completo,
                            telefono: h.telefono,
                            procedencia: h.procedencia_estado
                        }))
                    };
                }
                return { results: [] };
            },
            cache: true
        }
    });

    $('#huesped_id').on('select2:select', function(e) {
        const data = e.params.data;
        $('#infoHuesped').removeClass('hidden');
        $('#detallesHuesped').html(`
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                <div><strong>Nombre:</strong> ${data.nombre}</div>
                <div><strong>Teléfono:</strong> ${data.telefono || 'No registrado'}</div>
                ${data.procedencia ? `<div class="md:col-span-2"><strong>Procedencia:</strong> ${data.procedencia}</div>` : ''}
            </div>
        `);
        verificarFormularioCompleto();
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
    $('#hora_llegada').on('change', validarFechas);
    $('#btnHoraActual').on('click', establecerHoraActual);

    // Close mobile summary
    $('#btnCerrarResumen').on('click', () => $('#resumenFlotante').removeClass('activo'));
    $('#resumenReservacion').on('scroll', manejarScrollResumen);

    function manejarScrollResumen() {
        const el = document.getElementById('resumenReservacion');
        const ind = document.getElementById('scrollIndicator');
        if (!el || !ind) return;
        const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 10;
        ind.classList.toggle('hidden', atBottom || el.scrollHeight <= el.clientHeight);
    }

    function establecerHoraActual() {
        const now = new Date();
        const h = String(now.getHours()).padStart(2,'0');
        const m = String(now.getMinutes()).padStart(2,'0');
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
        renderizarHabitaciones(habs);
    }

    function aplicarFiltros() {
        const prevSel = [];
        $('.habitacion-check:checked').each(function() { prevSel.push($(this).val()); });

        let filtradas = [...todasLasHabitaciones];
        if (busquedaActiva) {
            filtradas = filtradas.filter(h =>
                h.numero.toString().toLowerCase().includes(busquedaActiva) ||
                h.tipo.toLowerCase().includes(busquedaActiva)
            );
        }
        renderizarHabitaciones(filtradas, prevSel);
    }

    function renderizarHabitaciones(habs, selPrev = null) {
        const seleccionadas = selPrev || [];
        if (!selPrev) {
            $('.habitacion-check:checked').each(function() { seleccionadas.push($(this).val()); });
        }

        // Sort: named rooms first, then numeric
        const esNum = n => /^\d+$/.test(n.toString().trim());
        habs.sort((a, b) => {
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

        html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';

        habs.forEach(hab => {
            const piso = {'-4':'4 niveles abajo','-2':'2 niveles abajo','-1':'Un nivel abajo','1':'Nivel de piso','2':'2º Nivel','3':'3º Nivel'}[hab.piso] || `Piso ${hab.piso}`;
            const checked   = seleccionadas.includes(hab.id.toString());
            const jacuzzi   = hab.tipo.includes('jacuzzi');
            const enMant    = hab.en_mantenimiento || hab.estado === 'mantenimiento';
            const ocupada   = (hab.ocupada || false) && !enMant;
            const cortesia  = habitacionesCortesiaSeleccionadas.includes(hab.id.toString());
            const jacBadge  = jacuzzi ? `<div class="absolute top-0 right-0 text-white px-3 py-1 rounded-bl-lg text-xs font-semibold z-10" style="background:linear-gradient(135deg,#2563EB,#3B82F6);"><i class="fas fa-hot-tub mr-1"></i>Jacuzzi</div>` : '';
            const topPad    = jacuzzi || cortesia ? 'mt-6' : '';

            if (enMant) {
                html += `
                    <div class="habitacion-card ocupada block relative">
                        <div class="border-2 rounded-xl p-4 relative overflow-hidden" style="background:linear-gradient(135deg,#FEF3C7,#FDE68A);border-color:#F59E0B;">
                            ${jacBadge}
                            <div class="flex justify-between items-start mb-3 ${topPad}">
                                <div>
                                    <h5 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-tools text-yellow-600"></i>Hab. ${hab.numero}
                                    </h5>
                                    <p class="text-sm text-gray-500"><i class="fas fa-layer-group mr-1"></i>${piso}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-xl text-gray-400 line-through">$${parseFloat(hab.precio_base).toLocaleString()}</p>
                                    <p class="text-xs font-bold text-yellow-700"><i class="fas fa-tools mr-1"></i>${hab.info_mantenimiento?.programado ? 'MANT. PROGRAMADO' : 'MANTENIMIENTO'}</p>
                                </div>
                            </div>
                            <div style="background:white;border:1px solid #F59E0B;border-radius:6px;padding:8px;font-size:11px;">
                                ${hab.info_mantenimiento ? `
                                    <div style="font-weight:600;color:#92400E;margin-bottom:2px;">
                                        <i class="fas fa-calendar-alt mr-1"></i>${hab.info_mantenimiento.programado ? 'Programado' : 'En proceso'}
                                    </div>
                                    ${hab.info_mantenimiento.fecha_programada ? `<div style="color:#6B7280;font-size:10px;"><i class="fas fa-clock mr-1"></i>Desde: ${hab.info_mantenimiento.fecha_programada}${hab.info_mantenimiento.fecha_programada_fin ? ' hasta: '+hab.info_mantenimiento.fecha_programada_fin : ''}</div>` : ''}
                                    ${hab.info_mantenimiento.motivo ? `<div style="color:#6B7280;font-size:10px;margin-top:2px;"><i class="fas fa-wrench mr-1"></i>${hab.info_mantenimiento.motivo}</div>` : ''}
                                ` : `<div style="font-weight:600;color:#92400E;"><i class="fas fa-exclamation-triangle mr-1"></i>No disponible</div>`}
                            </div>
                        </div>
                    </div>
                `;
            } else if (ocupada) {
                html += `
                    <div class="habitacion-card ocupada block relative">
                        <div class="border-2 rounded-xl p-4 relative overflow-hidden">
                            ${jacBadge}
                            <div class="flex justify-between items-start mb-3 ${topPad}">
                                <div>
                                    <h5 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-door-closed text-red-500"></i>Hab. ${hab.numero}
                                    </h5>
                                    <p class="text-sm text-gray-500"><i class="fas fa-layer-group mr-1"></i>${piso}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-xl text-gray-400 line-through">$${parseFloat(hab.precio_base).toLocaleString()}</p>
                                    <p class="text-xs font-bold text-red-600">OCUPADA</p>
                                </div>
                            </div>
                            ${hab.info_ocupacion ? `
                                <div class="info-ocupacion">
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
                               data-piso="${hab.piso}"
                               data-caracteristicas="${hab.caracteristicas || ''}"
                               class="sr-only habitacion-check"
                               ${checked ? 'checked' : ''}>

                        ${cortesia ? `<div class="badge-cortesia"><i class="fas fa-gift"></i>CORTESÍA</div>` : ''}

                        <div class="border-2 border-gray-200 rounded-xl p-4 relative overflow-hidden bg-white transition-all duration-300">
                            ${jacBadge}

                            <div class="flex justify-between items-start mb-3 ${topPad}">
                                <div>
                                    <h5 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-door-open" style="color:var(--lc-green);"></i>
                                        Hab. ${hab.numero}
                                    </h5>
                                    <p class="text-sm text-gray-500"><i class="fas fa-layer-group mr-1"></i>${piso}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-xl ${cortesia ? 'line-through text-amber-600' : ''}" style="${cortesia ? '' : 'color:var(--lc-green);'}">$${parseFloat(hab.precio_base).toLocaleString()}</p>
                                    <p class="text-xs text-gray-400">${cortesia ? 'GRATIS' : 'por noche'}</p>
                                </div>
                            </div>

                            ${hab.caracteristicas ? `
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-xs text-gray-500 italic flex items-center gap-1">
                                        <i class="fas fa-star" style="color:var(--lc-gold);"></i>
                                        ${hab.caracteristicas}
                                    </p>
                                </div>
                            ` : ''}

                            <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-xs text-gray-400 font-medium flex items-center gap-1.5">
                                    <i class="fas fa-circle text-xs ${cortesia ? 'text-amber-500' : ''}" style="${cortesia ? '' : 'color:var(--lc-green);'}"></i>
                                    ${cortesia ? 'Cortesía' : 'Disponible'}
                                </span>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-300">Toque para seleccionar</span>
                                    <div class="w-4 h-4 border-2 border-gray-300 rounded-full transition-all duration-300 flex items-center justify-center">
                                        <i class="fas fa-check text-white text-xs opacity-0 transition-opacity duration-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                `;
            }
        });

        html += '</div>';
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
                indicator.removeClass('opacity-0');
                contador.addClass('animate');
                setTimeout(() => contador.removeClass('animate'), 300);
                if (navigator.vibrate) navigator.vibrate(50);
            } else {
                card.removeClass('selected pulse-selection es-cortesia');
                card.find('.badge-cortesia').remove();
                card.find('.w-4.h-4').removeClass('bg-green-600 border-green-600');
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
            $('#resumenFlotante').removeClass('activo');
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
                            <p class="text-xs text-gray-500">${hab.tipo.replace('_',' ')} · $${hab.precio.toLocaleString()}/noche</p>
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

        actualizarResumen({
            noches,
            totalHabs:         habitacionesSeleccionadas.length,
            habsCortesia:      habitacionesCortesiaSeleccionadas.length,
            precioTotal,
            precioSinDescuento: precioSinDesc,
            ahorro:            precioSinDesc - precioTotal,
            habitaciones:      habitacionesSeleccionadas,
            habitacionesCortesia: habitacionesCortesiaSeleccionadas
        });
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
                            ${hab.tipo.includes('jacuzzi') ? '<i class="fas fa-hot-tub text-blue-400 text-xs"></i>' : ''}
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

        // Total
        html += `
            <div class="total-box">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-sm" style="color:#4A6340;">Total a pagar:</span>
                    <span class="font-black text-xl" style="color:var(--lc-green);">$${d.precioTotal.toLocaleString()}</span>
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
                    </div>
                    <p class="font-black text-lg" style="color:var(--lc-green);">$${d.precioTotal.toLocaleString()}</p>
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
    }

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
        const habs = $('.habitacion-check:checked').length;
        const ok   = hid && fe && fs && hl && habs > 0;
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

        const data = $('#huesped_id').select2?.('data')?.[0] || {};
        return {
            id,
            nombre_completo: data.nombre || data.text || `Huesped #${id}`,
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
        const hl = $('#hora_llegada').val();
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

            formRef.submit();
        });
    });
    // ── Cotización PDF ────────────────────────────────────────
$('#btnCotizacion, #btnCotizacionMovil').on('click', function() {
    const huespedId = $('input[name="huesped_id"]').val() || $('#huesped_id').val();
    const fe = $('#fecha_entrada').val();
    const fs = $('#fecha_salida').val();
    const hl = $('#hora_llegada').val();
    const notas = $('textarea[name="notas"]').val() || '';
    const habsChecked = $('.habitacion-check:checked');

    if (!huespedId || !fe || !fs || !hl || habsChecked.length === 0) {
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
    addHidden('hora_llegada', hl);
    addHidden('notas', notas);

    habsChecked.each(function() {
        addHidden('habitaciones[]', $(this).val());
    });

    if (typeof habitacionesCortesiaSeleccionadas !== 'undefined') {
        habitacionesCortesiaSeleccionadas.forEach(id => {
            addHidden('cortesias[]', id);
        });
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});
});
</script>
