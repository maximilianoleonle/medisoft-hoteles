<?php
/**
 * Vista de Reservaciones del Día - UNA CARD POR HABITACIÓN
 * Los Cedros — Paleta sage green / gold / cream
 */

$estadisticas        = $estadisticas        ?? [];
$entradas_hoy        = $entradas_hoy        ?? [];
$salidas_hoy         = $salidas_hoy         ?? [];
$reservaciones       = $reservaciones       ?? [];
$total_reservaciones = $total_reservaciones ?? 0;
$estados             = $estados             ?? [];
$buscar              = $buscar              ?? '';
$fecha_filtro        = $fecha_filtro        ?? date('Y-m-d');

// ═══ COLORES POR HABITACIÓN (según BD) — SE CONSERVAN INTACTOS ═══
$colores_habitacion = [
    'MOKA'       => ['bg' => '#7B5B3A', 'dark' => '#5C3D20'],
    'PURPURA'    => ['bg' => '#8B45A6', 'dark' => '#6B2586'],
    'ORO'        => ['bg' => '#C8A832', 'dark' => '#A08820'],
    'AMARILLO'   => ['bg' => '#D4B830', 'dark' => '#B89E18'],
    'MARRON'     => ['bg' => '#8B6B4A', 'dark' => '#6B4B2A'],
    'CEREZA'     => ['bg' => '#C0334D', 'dark' => '#9B1830'],
    'VIOLETA'    => ['bg' => '#7B5EA7', 'dark' => '#5B3E87'],
    'LIMON'      => ['bg' => '#A8B820', 'dark' => '#8A9A10'],
    'NARANJA'    => ['bg' => '#E07830', 'dark' => '#C05818'],
    'MARFIL'     => ['bg' => '#B8A878', 'dark' => '#988858'],
    'AZUL'       => ['bg' => '#4080C0', 'dark' => '#2860A0'],
    'ROSA'       => ['bg' => '#D46B8A', 'dark' => '#B44B6A'],
    'VERDE'      => ['bg' => '#4CAF50', 'dark' => '#357A38'],
    'UVA'        => ['bg' => '#7B4B8B', 'dark' => '#5B2B6B'],
    'MENTA'      => ['bg' => '#5BBF8A', 'dark' => '#3B9F6A'],
    'AMBAR'      => ['bg' => '#D49B30', 'dark' => '#B47B10'],
    'VINO'       => ['bg' => '#8B2040', 'dark' => '#6B0020'],
    'GRIS'       => ['bg' => '#7A8890', 'dark' => '#5A6870'],
    'CHOCOLATE'  => ['bg' => '#6B3F20', 'dark' => '#4B2010'],
    'CORAL'      => ['bg' => '#E07060', 'dark' => '#C05040'],
    'TURQUESA'   => ['bg' => '#30A8A0', 'dark' => '#188880'],
    'MAGENTA'    => ['bg' => '#C030A0', 'dark' => '#A01080'],
];
$color_numerico = ['bg' => '#5C7A4E', 'dark' => '#4A6340'];

function obtenerColorHab($numero, $colores, $default) {
    $n = trim(strtoupper($numero));
    if (is_numeric($n)) return $default;
    foreach ($colores as $nombre => $color) {
        if ($n === $nombre || strpos($n, $nombre) !== false) return $color;
    }
    return ['bg' => '#5C7A4E', 'dark' => '#4A6340'];
}

$fecha_hoy   = date('Y-m-d');
$es_hoy      = ($fecha_filtro === $fecha_hoy);
$meses       = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias_semana = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$ts          = strtotime($fecha_filtro);
$fecha_bonita = $dias_semana[date('w',$ts)] . ' ' . date('d',$ts) . ' de ' . $meses[date('n',$ts)-1] . ', ' . date('Y',$ts);
?>

<style>
/* ══════════════════════════════════════════
   LOS CEDROS · Reservaciones del Día
   Sage green / gold / cream palette
   ══════════════════════════════════════════ */
:root {
    --lc-green:      #5C7A4E;
    --lc-green-dark: #4A6340;
    --lc-green-deep: #3D5234;
    --lc-gold:       #C8A96A;
    --lc-gold-dark:  #B8994A;
    --lc-cream:      #F7F4EE;
    --radius: 12px;
    --shadow-hover: 0 8px 30px rgba(61,82,52,.12), 0 2px 8px rgba(61,82,52,.06);
    --transition: all .25s cubic-bezier(.4,0,.2,1);
}

/* ── Page background ─────────────────────── */
.res-page {
    min-height: 100vh;
    background: linear-gradient(145deg, #EFF4EC 0%, #E8EEE3 50%, #F4F1EC 100%);
}

/* ── Animations ──────────────────────────── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

/* ── Reservation cards ───────────────────── */
.card-reservacion {
    animation: fadeUp .4s ease both;
    transition: var(--transition);
}
.card-reservacion:hover  { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
.card-reservacion:nth-child(1)  { animation-delay: .03s }
.card-reservacion:nth-child(2)  { animation-delay: .06s }
.card-reservacion:nth-child(3)  { animation-delay: .09s }
.card-reservacion:nth-child(4)  { animation-delay: .12s }
.card-reservacion:nth-child(5)  { animation-delay: .15s }
.card-reservacion:nth-child(6)  { animation-delay: .18s }
.card-reservacion:nth-child(7)  { animation-delay: .21s }
.card-reservacion:nth-child(8)  { animation-delay: .24s }
.card-reservacion:nth-child(9)  { animation-delay: .27s }
.card-reservacion:nth-child(10) { animation-delay: .30s }
.card-reservacion:nth-child(11) { animation-delay: .33s }
.card-reservacion:nth-child(12) { animation-delay: .36s }

/* Card header — color del cuarto (conservado) */
.card-header-room {
    padding: 14px 16px;
    border-radius: var(--radius) var(--radius) 0 0;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.card-header-room::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.15) 0%, transparent 60%);
    pointer-events: none;
}
.room-number {
    font-size: 1.35rem; font-weight: 800;
    letter-spacing: .5px; text-shadow: 0 1px 3px rgba(0,0,0,.25);
}
.room-type {
    font-size: .65rem; letter-spacing: .8px;
    opacity: .92; font-weight: 600; margin-top: 2px; text-transform: uppercase;
}

/* Card body */
.card-body-info { padding: 14px 16px; }
.info-row-card {
    display: flex; align-items: center;
    padding: 5px 0;
    border-bottom: 1px solid #F0F5ED;
    font-size: .8rem;
}
.info-row-card:last-child { border-bottom: none; }
.info-label-card { color: #9CA3AF; font-weight: 500; width: 80px; flex-shrink: 0; font-size: .72rem; }
.info-value-card { color: #1F2937; font-weight: 600; flex: 1; text-align: right; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Badges */
.badge-estado {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: .65rem; font-weight: 700; letter-spacing: .3px; text-transform: uppercase;
}
.badge-confirmada  { background: #DBEAFE; color: #1E40AF; }
.badge-checked_in  { background: rgba(92,122,78,.12); color: #3D5234; }
.badge-checked_out { background: #F3F4F6; color: #6B7280; }
.badge-cancelada   { background: #FEE2E2; color: #991B1B; }
.badge-pagado      { background: rgba(200,169,106,.15); color: #92400E; border: 1px solid rgba(200,169,106,.3); }
.badge-id {
    background: rgba(0,0,0,.18); color: #fff;
    font-size: .68rem; font-weight: 700;
    padding: 2px 8px; border-radius: 6px;
}
.badge-fecha {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px; border-radius: 6px; font-size: .68rem; font-weight: 600;
}
.badge-multi {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 8px; border-radius: 5px;
    font-size: .62rem; font-weight: 700;
    background: rgba(255,255,255,.25); color: #fff;
    backdrop-filter: blur(2px);
}

/* Card actions */
.card-actions { padding: 10px 16px 14px; border-top: 1px solid #F0F5ED; }
.btn-ticket-full {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 9px 12px; border-radius: 8px;
    font-size: .78rem; font-weight: 700; color: #fff;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    cursor: pointer; border: none; text-decoration: none;
    transition: var(--transition);
    box-shadow: 0 2px 6px rgba(92,122,78,.22);
}
.btn-ticket-full:hover {
    background: linear-gradient(135deg, var(--lc-green-dark), var(--lc-green-deep));
    transform: scale(1.02);
    box-shadow: 0 5px 16px rgba(92,122,78,.35);
    color: #fff;
}

/* Price */
.price-display {
    font-size: 1.1rem; font-weight: 800;
    color: var(--lc-green-deep); letter-spacing: -.3px;
}

/* ── Search ──────────────────────────────── */
.search-container { position: relative; }
.search-icon {
    position: absolute; left: 14px; top: 50%;
    transform: translateY(-50%);
    color: #9CA3AF; font-size: .85rem; pointer-events: none;
}
.search-input {
    width: 100%;
    padding: 10px 14px 10px 40px;
    border: 1.5px solid #C8D9BE;
    border-radius: 10px; font-size: .85rem;
    background: #FAFDF8; color: #374151;
    transition: var(--transition);
}
.search-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
    background: white;
}
.search-input::placeholder { color: #A8C4A0; }

/* ── Date picker ─────────────────────────── */
.date-picker-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.date-picker-input {
    padding: 9px 14px;
    border: 1.5px solid #C8D9BE;
    border-radius: 10px; font-size: .85rem; font-weight: 600;
    color: #374151; background: #FAFDF8; cursor: pointer;
    transition: var(--transition); min-width: 160px;
}
.date-picker-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
}
.date-label { font-size: .82rem; font-weight: 600; color: #5C7A4E; white-space: nowrap; }
.btn-hoy {
    padding: 7px 14px; border-radius: 8px;
    font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .3px;
    border: 1.5px solid var(--lc-green);
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: #fff; cursor: pointer; transition: var(--transition);
}
.btn-hoy:hover { filter: brightness(1.1); }
.btn-hoy.is-today {
    background: rgba(92,122,78,.08);
    color: var(--lc-green-deep);
    border-color: #A8C4A0;
    cursor: default;
}
.fecha-bonita { font-size: .78rem; color: #7A9B6A; font-weight: 500; }

/* ── Widgets ─────────────────────────────── */
.widget-stat {
    animation: fadeUp .3s ease both;
    transition: var(--transition);
}
.widget-stat:hover { transform: translateY(-2px); }
.widget-stat:nth-child(1) { animation-delay: .05s }
.widget-stat:nth-child(2) { animation-delay: .10s }
.widget-stat:nth-child(3) { animation-delay: .15s }
.widget-stat:nth-child(4) { animation-delay: .20s }
.widget-stat:nth-child(5) { animation-delay: .25s }

/* ── Arrival / Departure panels ──────────── */
.arrival-departure-scroll {
    max-height: 160px; overflow-y: auto;
    scrollbar-width: thin; scrollbar-color: #A8C4A0 transparent;
}
.arrival-departure-scroll::-webkit-scrollbar { width: 4px; }
.arrival-departure-scroll::-webkit-scrollbar-thumb { background: #A8C4A0; border-radius: 4px; }

/* ── Filter state buttons ────────────────── */
.filtro-estado-base {
    flex: 1 0 auto;
    padding: 7px 12px; border-radius: 8px; border-width: 1.5px;
    font-size: .75rem; font-weight: 700; cursor: pointer; transition: var(--transition);
    border-style: solid;
}

/* ── Modals ──────────────────────────────── */
.modal-hd {
    padding: 16px 20px; border-radius: var(--radius) var(--radius) 0 0;
    background: linear-gradient(135deg, var(--lc-green-deep), var(--lc-green));
}
.lc-form-input {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid #C8D9BE;
    border-radius: 8px; font-size: .85rem;
    background: #FAFDF8; color: #374151;
    transition: border-color .2s, box-shadow .2s;
}
.lc-form-input:focus {
    outline: none;
    border-color: var(--lc-gold);
    box-shadow: 0 0 0 3px rgba(200,169,106,.15);
}
.btn-modal-cancel {
    flex: 1; padding: 9px 12px; border-radius: 8px;
    border: 1.5px solid #D5E4CB;
    background: rgba(92,122,78,.06); color: #4A6340;
    font-size: .85rem; font-weight: 600; cursor: pointer; transition: background .15s;
}
.btn-modal-cancel:hover { background: rgba(92,122,78,.12); }
.btn-modal-confirm {
    flex: 1; padding: 9px 12px; border-radius: 8px;
    background: linear-gradient(135deg, var(--lc-green), var(--lc-green-dark));
    color: white; font-size: .85rem; font-weight: 700;
    border: none; cursor: pointer;
    box-shadow: 0 2px 8px rgba(92,122,78,.25);
    transition: box-shadow .2s, transform .2s;
}
.btn-modal-confirm:hover { transform: translateY(-1px); box-shadow: 0 5px 14px rgba(92,122,78,.35); }

/* ── Misc ────────────────────────────────── */
.empty-state { animation: fadeIn .5s ease; }
.card-reservacion.hidden-search { display: none !important; }

/* ── Responsive ──────────────────────────── */
@media (max-width: 640px) {
    .card-header-room { padding: 12px 14px; }
    .card-body-info   { padding: 10px 14px; }
    .room-number      { font-size: 1.15rem; }
    .room-type        { font-size: .6rem; }
    .info-label-card  { width: 72px; font-size: .68rem; }
    .info-value-card  { font-size: .76rem; }
    .btn-ticket-full  { padding: 8px 10px; font-size: .75rem; }
    .search-input     { font-size: .8rem; padding: 8px 12px 8px 36px; }
    .date-picker-input{ font-size: .8rem; padding: 8px 10px; min-width: 140px; }
    .date-picker-wrap { gap: 8px; }
    .fecha-bonita     { font-size: .7rem; }
}
@media print {
    .no-print { display: none !important; }
    .card-reservacion { break-inside: avoid; }
}
</style>

<!-- ═══════════════════ RESERVACIONES ════════════════════════ -->
<div class="res-page">

    <!-- ── Header ── -->
    <div style="background:linear-gradient(135deg,#3D5234,#4A6340,#5C7A4E);position:relative;overflow:hidden;" class="no-print">
        <!-- Decorative circle -->
        <div style="position:absolute;top:-35px;right:-35px;width:160px;height:160px;border-radius:50%;background:rgba(200,169,106,.07);pointer-events:none;"></div>

        <div class="px-3 sm:px-4 lg:px-6 py-3 relative z-10">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">

                <!-- Title -->
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <div style="background:rgba(255,255,255,.15);width:40px;height:40px;border-radius:11px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-calendar-day text-white text-sm"></i>
                        </div>
                        <?php if (($estadisticas['entradas_hoy'] ?? 0) > 0): ?>
                        <div class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center font-bold" style="font-size:.6rem;">
                            <?= $estadisticas['entradas_hoy'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-white" style="line-height:1.2;">Reservaciones</h1>
                        <p style="font-size:.72rem;color:rgba(255,255,255,.6);margin-top:1px;">
                            <?= $total_reservaciones ?> habitaciones activas<?= $es_hoy ? ' · Hoy' : '' ?>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2 w-full sm:w-auto">
                    <a href="<?= url('reservaciones/calendario') ?>"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition-colors"
                       style="background:rgba(255,255,255,.12);color:white;border:1px solid rgba(255,255,255,.2);"
                       onmouseover="this.style.background='rgba(255,255,255,.22)'"
                       onmouseout="this.style.background='rgba(255,255,255,.12)'">
                        <i class="fas fa-calendar mr-1.5"></i>
                        <span class="hidden sm:inline">Calendario</span>
                        <span class="sm:hidden">Cal.</span>
                    </a>
                    <button onclick="abrirModalExportarPDF()"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition-colors bg-red-600 hover:bg-red-700 text-white">
                        <i class="fas fa-file-pdf mr-1.5"></i>PDF
                    </button>
                    <button onclick="abrirModalExportarExcel()"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition-colors bg-emerald-700 hover:bg-emerald-800 text-white">
                        <i class="fas fa-file-excel mr-1.5"></i>Excel
                    </button>
                    <a href="<?= url('reservaciones/crear') ?>"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition-colors"
                       style="background:linear-gradient(135deg,var(--lc-gold),var(--lc-gold-dark));color:#3D5234;">
                        <i class="fas fa-plus mr-1.5"></i>Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="px-3 sm:px-4 lg:px-6 py-3">

        <!-- ── Widgets ── -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 mb-4">

            <!-- Llegadas -->
            <div class="widget-stat bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <div style="background:rgba(37,99,235,.1);width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-plane-arrival text-blue-600 text-xs"></i>
                    </div>
                    <span class="text-lg font-bold text-gray-800"><?= $estadisticas['entradas_hoy'] ?? 0 ?></span>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Llegadas</h3>
                <p class="text-xs text-blue-500 mt-0.5">Desde 15:00h</p>
            </div>

            <!-- Check-ins -->
            <div class="widget-stat bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <div style="background:rgba(92,122,78,.1);width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-user-check text-xs" style="color:var(--lc-green);"></i>
                    </div>
                    <span class="text-lg font-bold text-gray-800"><?= $estadisticas['entradas']['completadas'] ?? 0 ?></span>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Check-ins</h3>
                <?php $te=max($estadisticas['entradas_hoy']??1,1);$co=$estadisticas['entradas']['completadas']??0;$pc=min(($co/$te)*100,100); ?>
                <div class="w-full bg-gray-200 rounded-full mt-1" style="height:3px;">
                    <div style="width:<?= $pc ?>%;height:3px;border-radius:9999px;background:linear-gradient(90deg,#5C7A4E,#7A9B6A);"></div>
                </div>
            </div>

            <!-- Salidas -->
            <div class="widget-stat bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <div style="background:rgba(245,158,11,.1);width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-plane-departure text-amber-500 text-xs"></i>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-gray-800"><?= $estadisticas['salidas_hoy'] ?? 0 ?></span>
                        <?php if (($estadisticas['salidas']['pendientes'] ?? 0) > 0): ?>
                        <div class="bg-red-100 text-red-600 px-1 py-0.5 rounded text-xs font-bold mt-0.5" style="font-size:.6rem;">
                            <?= $estadisticas['salidas']['pendientes'] ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Salidas</h3>
                <p class="text-xs text-amber-500 mt-0.5">Hasta 12:00h</p>
            </div>

            <!-- Ocupación -->
            <div class="widget-stat bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <div style="background:rgba(139,92,246,.1);width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-bed text-violet-600 text-xs"></i>
                    </div>
                    <span class="text-lg font-bold text-gray-800"><?= $estadisticas['porcentaje_ocupacion'] ?? 0 ?>%</span>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Ocupación</h3>
                <div class="flex items-center text-xs text-gray-500 mt-0.5">
                    <span class="font-bold text-gray-700"><?= $estadisticas['habitaciones_ocupadas'] ?? 0 ?></span>
                    <span class="mx-0.5">/</span>
                    <span><?= $estadisticas['total_habitaciones'] ?? 0 ?></span>
                </div>
            </div>

            <!-- Ingresos -->
            <div class="widget-stat bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <div style="background:rgba(92,122,78,.1);width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-dollar-sign text-xs" style="color:var(--lc-green);"></i>
                    </div>
                </div>
                <h3 class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Ingresos</h3>
                <div class="text-sm font-bold mt-0.5" style="color:var(--lc-green-deep);"><?= format_money($estadisticas['ingresos_dia']['total'] ?? 0) ?></div>
                <div class="text-xs text-gray-400 mt-0.5">Efec: <?= format_money($estadisticas['ingresos_dia']['efectivo'] ?? 0) ?></div>
            </div>
        </div>

        <!-- ── Llegadas & Salidas ── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-4">

            <!-- Llegadas -->
            <div class="bg-white rounded-xl shadow-sm border border-[#DDE8D5] overflow-hidden">
                <div style="background:linear-gradient(135deg,#2563EB,#3B82F6);" class="p-2.5 rounded-t-xl">
                    <h3 class="text-sm font-bold text-white flex items-center">
                        <i class="fas fa-clock mr-2 opacity-80"></i>Llegadas de Hoy
                        <?php if (!empty($entradas_hoy)): ?>
                        <span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full" style="background:rgba(255,255,255,.2);color:white;"><?= count($entradas_hoy) ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="p-2">
                    <?php if (empty($entradas_hoy)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-check text-gray-300 text-xl mb-1.5"></i>
                            <p class="text-gray-400 text-xs">Sin llegadas</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-1 arrival-departure-scroll">
                            <?php foreach (array_slice($entradas_hoy, 0, 8) as $entrada): ?>
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-[#F0F5ED] transition-colors" style="background:#F7FCF4;">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($entrada['huesped_nombre'] ?? $entrada['nombre_completo'] ?? 'Sin nombre') ?></p>
                                    <div class="flex items-center gap-2 text-xs mt-0.5">
                                        <?php if (!empty($entrada['habitaciones_numeros'])): ?>
                                        <span class="px-1.5 py-0.5 rounded font-bold" style="background:rgba(92,122,78,.12);color:#3D5234;font-size:.65rem;">
                                            <?= htmlspecialchars($entrada['habitaciones_numeros']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="text-blue-500 font-semibold"><?= !empty($entrada['hora_llegada_estimada']) ? substr($entrada['hora_llegada_estimada'],0,5) : '15:00' ?></span>
                                    </div>
                                </div>
                                <a href="<?= url('reservaciones/ver/' . $entrada['id']) ?>"
                                   style="color:var(--lc-green);width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;transition:background .15s;"
                                   onmouseover="this.style.background='rgba(92,122,78,.1)'"
                                   onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Salidas -->
            <div class="bg-white rounded-xl shadow-sm border border-[#DDE8D5] overflow-hidden">
                <div style="background:linear-gradient(135deg,#D97706,#F59E0B);" class="p-2.5 rounded-t-xl">
                    <h3 class="text-sm font-bold text-white flex items-center">
                        <i class="fas fa-sign-out-alt mr-2 opacity-80"></i>Salidas de Hoy
                        <?php if (!empty($salidas_hoy)): ?>
                        <span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full" style="background:rgba(255,255,255,.2);color:white;"><?= count($salidas_hoy) ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                <div class="p-2">
                    <?php if (empty($salidas_hoy)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-sign-out-alt text-gray-300 text-xl mb-1.5"></i>
                            <p class="text-gray-400 text-xs">Sin salidas</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-1 arrival-departure-scroll">
                            <?php foreach (array_slice($salidas_hoy, 0, 8) as $salida): ?>
                            <div class="flex items-center justify-between p-2 rounded-lg hover:bg-amber-50 transition-colors" style="background:#FFFBEB;">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($salida['huesped_nombre'] ?? $salida['nombre_completo'] ?? 'Sin nombre') ?></p>
                                    <div class="flex items-center gap-2 text-xs mt-0.5">
                                        <?php if (!empty($salida['habitaciones_numeros'])): ?>
                                        <span class="px-1.5 py-0.5 rounded font-bold" style="background:#FDE68A;color:#92400E;font-size:.65rem;">
                                            <?= htmlspecialchars($salida['habitaciones_numeros']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <span class="text-red-500 font-semibold">Hasta 12:00</span>
                                    </div>
                                </div>
                                <button onclick="confirmarCheckOut(<?= $salida['id'] ?>)"
                                        class="text-white px-2.5 py-1 rounded-lg text-xs font-bold hover:bg-amber-600 transition-colors"
                                        style="background:#D97706;">
                                    <i class="fas fa-sign-out-alt"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Search + Date + Filters ── -->
        <div class="mb-4 no-print">
            <div class="bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-3">

                <!-- Search -->
                <div class="search-container mb-3">
                    <input type="text" id="buscarReservacion" class="search-input"
                           placeholder="Buscar por cliente, teléfono, habitación o # reserva..."
                           value="<?= htmlspecialchars($buscar) ?>" autocomplete="off">
                    <i class="fas fa-search search-icon"></i>
                </div>

                <!-- Date + Estado filters -->
                <div class="flex flex-col sm:flex-row gap-3 items-stretch sm:items-center">

                    <div class="date-picker-wrap">
                        <span class="date-label">
                            <i class="fas fa-calendar-alt mr-1.5" style="color:var(--lc-gold-dark);"></i>Fecha:
                        </span>
                        <input type="date" id="fechaCalendario" class="date-picker-input"
                               value="<?= $fecha_filtro ?>" onchange="cambiarFecha(this.value)">
                        <?php if (!$es_hoy): ?>
                            <button onclick="cambiarFecha('<?= $fecha_hoy ?>')" class="btn-hoy">Hoy</button>
                        <?php else: ?>
                            <span class="btn-hoy is-today"><i class="fas fa-check mr-1"></i>Hoy</span>
                        <?php endif; ?>
                        <span class="fecha-bonita hidden sm:inline"><?= $fecha_bonita ?></span>
                    </div>

                    <!-- Estado filters -->
                    <div class="flex gap-2 flex-1 sm:justify-end">
                        <button onclick="filtrarEstado('todos')"
                                class="filtro-estado active flex-1 sm:flex-none filtro-estado-base"
                                data-estado="todos"
                                style="border-color:var(--lc-green);background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));color:white;">
                            Todos <span class="opacity-80 ml-0.5"><?= $total_reservaciones ?></span>
                        </button>
                        <button onclick="filtrarEstado('checked_in')"
                                class="filtro-estado flex-1 sm:flex-none filtro-estado-base"
                                data-estado="checked_in"
                                style="border-color:#A8C4A0;background:rgba(92,122,78,.07);color:#3D5234;">
                            <i class="fas fa-user-check mr-1"></i>Check-in
                        </button>
                        <button onclick="filtrarEstado('confirmada')"
                                class="filtro-estado flex-1 sm:flex-none filtro-estado-base"
                                data-estado="confirmada"
                                style="border-color:#93C5FD;background:#EFF6FF;color:#1E40AF;">
                            <i class="fas fa-calendar-check mr-1"></i>Confirmada
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Card grid ── -->
        <?php if (empty($reservaciones)): ?>
            <div class="empty-state bg-white rounded-xl shadow-sm border border-[#DDE8D5] p-12 text-center">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(92,122,78,.08);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                    <i class="fas fa-calendar-day text-2xl" style="color:#A8C4A0;"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">Sin reservaciones</h3>
                <p class="text-gray-400 text-sm mb-5">
                    No hay reservaciones activas para <?= $es_hoy ? 'hoy' : date('d/m/Y', $ts) ?>. Crea una reservación o revisa otra fecha.
                </p>
                <div class="flex gap-3 justify-center flex-wrap">
                    <a href="<?= url('reservaciones/crear') ?>"
                       class="inline-flex items-center px-4 py-2 text-white rounded-lg text-sm font-bold"
                       style="background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));">
                        <i class="fas fa-plus mr-2"></i>Crear Reservación
                    </a>
                    <?php if (!$es_hoy): ?>
                    <a href="<?= url('reservaciones') ?>"
                       class="inline-flex items-center px-4 py-2 rounded-lg text-sm font-bold transition-colors"
                       style="background:rgba(92,122,78,.08);color:#4A6340;border:1.5px solid #D5E4CB;">
                        <i class="fas fa-calendar-day mr-2"></i>Ver Hoy
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <div id="searchResults" class="hidden mb-3">
                <p class="text-sm text-gray-400">
                    <i class="fas fa-filter mr-1" style="color:var(--lc-green);"></i>
                    Mostrando <span id="searchCount" class="font-bold" style="color:#3D5234;">0</span> de <?= $total_reservaciones ?>
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4" id="gridCards">
                <?php foreach ($reservaciones as $row): ?>
                <?php
                    $hab_numero           = $row['habitacion_numero'] ?? '?';
                    $hab_tipo             = str_replace('_', ' ', mb_strtoupper($row['habitacion_tipo'] ?? ''));
                    $total_habs_reserva   = intval($row['total_habitaciones_reserva'] ?? 1);
                    $todas_habs           = $row['todas_habitaciones'] ?? $hab_numero;
                    $color                = obtenerColorHab($hab_numero, $colores_habitacion, $color_numerico);
                    $estado               = $row['estado'] ?? 'confirmada';
                    $estado_info          = $estados[$estado] ?? ['label' => 'Desconocido', 'color' => 'gray'];
                    $metodo_pago          = $row['metodo_pago'] ?? '';
                    $tiene_pago           = !empty($metodo_pago);
                    $search_data          = strtolower(
                        ($row['huesped_nombre']    ?? '') . ' ' .
                        ($row['huesped_telefono']  ?? '') . ' ' .
                        $hab_numero . ' ' . $todas_habs . ' ' .
                        $row['id'] . ' ' .
                        ($row['usuario_registro']  ?? '')
                    );
                ?>
                <div class="card-reservacion bg-white rounded-xl shadow-sm border border-[#DDE8D5] overflow-hidden"
                     data-search="<?= htmlspecialchars($search_data) ?>"
                     data-estado="<?= $estado ?>">

                    <!-- Header: color of THIS room (preserved) -->
                    <div class="card-header-room" style="background:linear-gradient(135deg,<?= $color['bg'] ?>,<?= $color['dark'] ?>);">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <i class="fas fa-bed text-lg opacity-80"></i>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="room-number"><?= htmlspecialchars($hab_numero) ?></span>
                                        <?php if ($total_habs_reserva > 1): ?>
                                        <span class="badge-multi">
                                            <i class="fas fa-link" style="font-size:.5rem;"></i>
                                            <?= $total_habs_reserva ?> habitaciones
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($hab_tipo): ?>
                                    <div class="room-type"><?= htmlspecialchars($hab_tipo) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <span class="badge-id">#<?= $row['id'] ?></span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body-info">
                        <div class="flex items-center justify-between mb-2">
                            <span class="badge-fecha" style="background:#F0F5ED;color:#4A6340;">
                                <i class="far fa-calendar-alt"></i>
                                <?= format_date($row['fecha_entrada'], 'd/m/Y') ?>
                                <span style="color:#A8C4A0;margin:0 1px;">&rarr;</span>
                                <?= format_date($row['fecha_salida'], 'd/m/Y') ?>
                            </span>
                        </div>
                        <div class="info-row-card">
                            <span class="info-label-card"><i class="fas fa-user mr-1 text-gray-300"></i>Cliente:</span>
                            <span class="info-value-card"><?= htmlspecialchars($row['huesped_nombre'] ?? 'Sin nombre') ?></span>
                        </div>
                        <div class="info-row-card">
                            <span class="info-label-card"><i class="fas fa-phone mr-1 text-gray-300"></i>Teléfono:</span>
                            <span class="info-value-card"><?= htmlspecialchars($row['huesped_telefono'] ?? 'N/A') ?></span>
                        </div>
                        <div class="info-row-card">
                            <span class="info-label-card"><i class="fas fa-user-tie mr-1 text-gray-300"></i>Usuario:</span>
                            <span class="info-value-card"><?= htmlspecialchars($row['usuario_registro'] ?? 'Sistema') ?></span>
                        </div>

                        <?php if ($total_habs_reserva > 1): ?>
                        <div class="info-row-card">
                            <span class="info-label-card"><i class="fas fa-door-open mr-1 text-gray-300"></i>Habs:</span>
                            <span class="info-value-card" style="color:#2563EB;"><?= htmlspecialchars($todas_habs) ?></span>
                        </div>
                        <?php endif; ?>

                        <div class="flex items-center justify-between mt-3 pt-2" style="border-top:1px solid #F0F5ED;">
                            <div>
                                <?php if ($tiene_pago && $estado == 'checked_in'): ?>
                                    <span class="badge-estado badge-pagado">PAGADO · <?= strtoupper($metodo_pago) ?></span>
                                <?php else: ?>
                                    <span class="badge-estado badge-<?= $estado ?>"><?= strtoupper($estado_info['label']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="price-display"><?= format_money($row['precio_total'] ?? 0) ?></span>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="card-actions">
                        <a href="<?= url('reservaciones/ver/' . $row['id']) ?>" class="btn-ticket-full">
                            <i class="fas fa-receipt"></i> ver
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══════ Modal Check-in ══════ -->
<div id="modalCheckIn" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden">
            <div class="modal-hd">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-sign-in-alt opacity-80"></i>Check-in
                </h3>
            </div>
            <form id="formCheckInModal" method="POST" action="" class="p-5">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <div style="background:#F0F5ED;border:1px solid #D5E4CB;border-radius:10px;padding:14px;text-align:center;">
                        <span class="text-xs font-bold uppercase tracking-wide" style="color:#7A9B6A;">Total a cobrar</span>
                        <div class="text-2xl font-black mt-1" style="color:#3D5234;" id="totalACobrar">$0.00</div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:#5C7A4E;text-transform:uppercase;letter-spacing:.04em;">Hora</label>
                        <input type="time" name="hora_entrada" value="<?= date('H:i') ?>" class="lc-form-input" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1.5" style="color:#5C7A4E;text-transform:uppercase;letter-spacing:.04em;">Método de pago</label>
                        <select name="metodo_pago" required class="lc-form-input">
                            <option value="">Seleccionar...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
                    </div>
                </div>
                <div class="flex gap-2 mt-5">
                    <button type="button" onclick="cerrarModalCheckIn()" class="btn-modal-cancel">Cancelar</button>
                    <button type="submit" class="btn-modal-confirm">Confirmar Check-in</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Modal Exportar PDF ══════ -->
<div id="modalExportarPDF" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="modal-hd">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-file-pdf opacity-80"></i>Exportar a PDF
                </h3>
            </div>
            <form id="formExportarPDF" class="p-5">
                <div class="mb-5">
                    <label class="block text-xs font-bold mb-1.5" style="color:#5C7A4E;text-transform:uppercase;letter-spacing:.04em;">Fecha</label>
                    <input type="date" id="fechaExportar" name="fecha" value="<?= date('Y-m-d') ?>" class="lc-form-input">
                    <p class="mt-1.5 text-xs" style="color:#9CA3AF;">Exportará las reservaciones activas de esa fecha</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalExportarPDF()" class="btn-modal-cancel">Cancelar</button>
                    <button type="submit" class="btn-modal-confirm">
                        <i class="fas fa-download mr-1.5"></i>Generar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════ Modal Exportar Excel ══════ -->
<div id="modalExportarExcel" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
            <div class="modal-hd" style="background:linear-gradient(135deg,#166534,#15803d);">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fas fa-file-excel opacity-80"></i>Exportar a Excel
                </h3>
            </div>
            <form id="formExportarExcel" class="p-5">
                <div class="mb-5">
                    <label class="block text-xs font-bold mb-1.5" style="color:#166534;text-transform:uppercase;letter-spacing:.04em;">Fecha</label>
                    <input type="date" id="fechaExportarExcel" name="fecha" value="<?= date('Y-m-d') ?>" class="lc-form-input">
                    <p class="mt-1.5 text-xs" style="color:#9CA3AF;">Exportará las reservaciones activas de esa fecha</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="cerrarModalExportarExcel()" class="btn-modal-cancel">Cancelar</button>
                    <button type="submit" class="btn-modal-confirm" style="background:linear-gradient(135deg,#166534,#15803d);">
                        <i class="fas fa-download mr-1.5"></i>Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden checkout form -->
<form id="formCheckOut" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const baseUrl = '<?= url('') ?>';
let filtroEstadoActual = 'todos';

function cambiarFecha(f) {
    if (f) window.location.href = baseUrl + '/reservaciones?fecha=' + f;
}

const searchInput   = document.getElementById('buscarReservacion');
const cards         = document.querySelectorAll('.card-reservacion');
const searchResults = document.getElementById('searchResults');
const searchCount   = document.getElementById('searchCount');

if (searchInput) {
    searchInput.addEventListener('input', aplicarFiltros);
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { this.value = ''; aplicarFiltros(); this.blur(); }
    });
}

function aplicarFiltros() {
    const t = (searchInput?.value || '').toLowerCase().trim();
    let v = 0;
    cards.forEach(c => {
        const s = c.getAttribute('data-search') || '';
        const e = c.getAttribute('data-estado') || '';
        if ((!t || s.includes(t)) && (filtroEstadoActual === 'todos' || e === filtroEstadoActual)) {
            c.classList.remove('hidden-search'); v++;
        } else {
            c.classList.add('hidden-search');
        }
    });
    if (searchResults && searchCount) {
        if (t || filtroEstadoActual !== 'todos') {
            searchResults.classList.remove('hidden');
            searchCount.textContent = v;
        } else {
            searchResults.classList.add('hidden');
        }
    }
}

function filtrarEstado(estado) {
    filtroEstadoActual = estado;
    document.querySelectorAll('.filtro-estado').forEach(b => {
        const e = b.getAttribute('data-estado');
        if (e === estado) {
            b.style.cssText = 'border-color:var(--lc-green);background:linear-gradient(135deg,var(--lc-green),var(--lc-green-dark));color:white;';
        } else {
            if (e === 'checked_in')  b.style.cssText = 'border-color:#A8C4A0;background:rgba(92,122,78,.07);color:#3D5234;';
            else if (e === 'confirmada') b.style.cssText = 'border-color:#93C5FD;background:#EFF6FF;color:#1E40AF;';
            else b.style.cssText = 'border-color:#E5E7EB;background:white;color:#4B5563;';
        }
    });
    aplicarFiltros();
}

function abrirModalCheckIn(id, total) {
    const m = document.getElementById('modalCheckIn');
    const f = document.getElementById('formCheckInModal');
    const t = document.getElementById('totalACobrar');
    if (!m || !f || !t) return;
    f.action = baseUrl + '/reservaciones/check-in/' + id;
    t.textContent = formatMoney(parseFloat(total));
    f.reset();
    f.querySelector('input[name="hora_entrada"]').value = new Date().toTimeString().slice(0, 5);
    m.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}
function cerrarModalCheckIn() {
    const m = document.getElementById('modalCheckIn');
    if (m) { m.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
}

function confirmarCheckOut(id) {
    const f = document.getElementById('formCheckOut');
    if (!f) return;
    Swal.fire({
        title: '¿Hacer Check-out?',
        text: 'Se registrará la salida',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#5C7A4E',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, check-out',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' }
    }).then(r => {
        if (r.isConfirmed) {
            f.querySelector('input[name="hora_salida"]').value = new Date().toTimeString().slice(0, 8);
            f.action = baseUrl + '/reservaciones/check-out/' + id;
            f.submit();
        }
    });
}

function abrirModalExportarPDF() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = 'none';
    const m = document.getElementById('modalExportarPDF');
    if (m) {
        m.classList.remove('hidden');
        document.getElementById('fechaExportar').value = new Date().toISOString().split('T')[0];
        document.body.classList.add('overflow-hidden');
    }
}
function cerrarModalExportarPDF() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = '';
    const m = document.getElementById('modalExportarPDF');
    if (m) { m.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
}

document.getElementById('formExportarPDF')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const f = document.getElementById('fechaExportar').value;
    if (!f) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una fecha', confirmButtonColor: '#5C7A4E' });
        return;
    }
    window.open(baseUrl + '/reservaciones/exportar-pdf?fecha=' + f, '_blank');
    cerrarModalExportarPDF();
});

document.getElementById('modalCheckIn')?.addEventListener('click', function(e) { if (e.target === this) cerrarModalCheckIn(); });
document.getElementById('modalExportarPDF')?.addEventListener('click', function(e) { if (e.target === this) cerrarModalExportarPDF(); });

// ══════ Excel Export ══════
function abrirModalExportarExcel() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = 'none';
    const m = document.getElementById('modalExportarExcel');
    if (m) {
        m.classList.remove('hidden');
        document.getElementById('fechaExportarExcel').value = new Date().toISOString().split('T')[0];
        document.body.classList.add('overflow-hidden');
    }
}

function cerrarModalExportarExcel() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = '';
    const m = document.getElementById('modalExportarExcel');
    if (m) { m.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
}

document.getElementById('formExportarExcel')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const f = document.getElementById('fechaExportarExcel').value;
    if (!f) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una fecha', confirmButtonColor: '#166534' });
        return;
    }
    window.open(baseUrl + '/reservaciones/exportar-excel?fecha=' + f, '_blank');
    cerrarModalExportarExcel();
});

document.getElementById('modalExportarExcel')?.addEventListener('click', function(e) { if (e.target === this) cerrarModalExportarExcel(); });

function formatMoney(a) {
    return '$' + parseFloat(a).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
