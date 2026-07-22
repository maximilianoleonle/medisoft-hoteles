<?php
/**
 * Calendario de reservaciones
 * Diseño inspirado en dashboard + ficha de habitación (DM Sans, Outfit, paleta verde).
 */

if (!function_exists('hcal_safe')) {
    function hcal_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('hcal_estado_info')) {
    function hcal_estado_info($estado) {
        $map = [
            'confirmada'  => ['label' => 'Confirmada',   'icon' => 'fa-calendar-check',      'key' => 'confirmada'],
            'checked_in'  => ['label' => 'Hospedado',    'icon' => 'fa-right-to-bracket',     'key' => 'checked-in'],
            'checked_out' => ['label' => 'Completada',   'icon' => 'fa-right-from-bracket',   'key' => 'checked-out'],
            'cancelada'   => ['label' => 'Cancelada',    'icon' => 'fa-ban',                  'key' => 'cancelada'],
        ];
        $key = (string)($estado ?? '');
        return $map[$key] ?? [
            'label' => ucfirst(str_replace('_', ' ', $key ?: 'Reserva')),
            'icon'  => 'fa-calendar-day',
            'key'   => preg_replace('/[^a-z0-9-]+/', '-', strtolower($key ?: 'otra')),
        ];
    }
}

if (!function_exists('hcal_mes_url')) {
    function hcal_mes_url($mes, $anio) {
        return url('reservaciones/calendario?' . http_build_query([
            'mes' => (int)$mes,
            'año' => (int)$anio,
        ]));
    }
}

if (!function_exists('hcal_primer_nombre')) {
    function hcal_primer_nombre($nombre) {
        $partes = preg_split('/\s+/', trim((string)$nombre));
        return ($partes && $partes[0] !== '') ? $partes[0] : 'Reserva';
    }
}

// ── Data prep ─────────────────────────────────────────────────────────────
$primerDia    = mktime(0, 0, 0, $mes, 1, $año);
$diasEnMes    = (int)date('t', $primerDia);
$mesAnterior  = $mes - 1;
$mesSiguiente = $mes + 1;
$añoAnterior  = $año;
$añoSiguiente = $año;

if ($mesAnterior < 1)   { $mesAnterior = 12; $añoAnterior--; }
if ($mesSiguiente > 12) { $mesSiguiente = 1;  $añoSiguiente++; }

$mesesNombres = [
    1 => 'Enero', 2 => 'Febrero',   3 => 'Marzo',     4 => 'Abril',
    5 => 'Mayo',  6 => 'Junio',     7 => 'Julio',      8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$diasAbr = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];

$totalReservaciones = count($reservaciones);
$confirmadas = $checkIn = $checkOut = $canceladas = 0;
foreach ($reservaciones as $r) {
    switch ($r['estado']) {
        case 'confirmada':   $confirmadas++; break;
        case 'checked_in':  $checkIn++;     break;
        case 'checked_out': $checkOut++;    break;
        case 'cancelada':   $canceladas++;  break;
    }
}

$diasOcupados = 0;
$totalDiasHab = count($habitaciones) * $diasEnMes;
foreach ($calendario as $diaKey => $habsRes) {
    foreach ($habsRes as $habId => $reserva) {
        if (($reserva['estado'] ?? '') === 'checked_in') $diasOcupados++;
    }
}
$ocupacion = $totalDiasHab > 0 ? round(($diasOcupados / $totalDiasHab) * 100) : 0;

$hoyNum     = (int)date('j');
$mesActNum  = (int)date('n');
$añoActNum  = (int)date('Y');
$esEsteMes  = ($mes === $mesActNum && $año === $añoActNum);

$resHoyIds      = [];
$reservacionesHoy = [];
if ($esEsteMes && isset($calendario[$hoyNum])) {
    foreach ($calendario[$hoyNum] as $habId => $reserva) {
        $rid = (int)($reserva['id'] ?? 0);
        if ($rid && !isset($resHoyIds[$rid])) {
            $resHoyIds[$rid] = true;
            $reservacionesHoy[] = $reserva;
        }
    }
}
$disponiblesHoy   = max(0, count($habitaciones) - count($resHoyIds));
$mesActualTexto   = ($mesesNombres[$mes] ?? '') . ' ' . $año;
$hotelNombre      = (function_exists('current_hotel_nombre') && current_hotel_nombre())
    ? current_hotel_nombre()
    : ((function_exists('current_hotel_display_name') ? current_hotel_display_name() : '') ?: 'Hotel');

// ── Datos para la vista móvil (rediseño tipo agenda) ──────────────────────
$hcalHabNumById  = [];
$hcalHabTipoById = [];
foreach ($habitaciones as $h) {
    $hid = (int)($h['id'] ?? 0);
    $hcalHabNumById[$hid]  = (string)($h['numero'] ?? '?');
    $hcalHabTipoById[$hid] = ucfirst(str_replace('_', ' ', (string)($h['tipo'] ?? 'Habitación')));
}

$hcalPrimerDiaSemana = (int)date('w', $primerDia); // 0=Dom … 6=Sáb
$hcalDiasNombres = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
$hcalDaysData = [];
$hcalPesoEstado = ['llegada' => 0, 'estancia' => 1, 'salida' => 2, 'cancelada' => 3];

for ($d = 1; $d <= $diasEnMes; $d++) {
    $fTs    = mktime(0, 0, 0, $mes, $d, $año);
    $fTexto = date('Y-m-d', $fTs);
    $entries = $calendario[$d] ?? [];

    $seen = [];
    $reservasDia = [];
    $ocupadas = $llegadas = $salidas = 0;
    $flags = ['llegada' => false, 'estancia' => false, 'salida' => false, 'cancelada' => false];

    foreach ($entries as $habId => $rr) {
        $estado = (string)($rr['estado'] ?? '');
        if ($estado !== 'cancelada') $ocupadas++;

        $rid = (int)($rr['id'] ?? 0);
        if ($rid && isset($seen[$rid])) continue;
        if ($rid) $seen[$rid] = true;

        $ent = substr((string)($rr['fecha_entrada'] ?? ''), 0, 10);
        $sal = substr((string)($rr['fecha_salida']  ?? ''), 0, 10);
        $esLlegada = ($ent !== '' && $ent === $fTexto);
        $esSalida  = ($sal !== '' && $sal === $fTexto);

        if ($estado === 'cancelada')      { $flags['cancelada'] = true; $cat = 'cancelada'; }
        elseif ($esLlegada)               { $llegadas++; $flags['llegada'] = true; $cat = 'llegada'; }
        elseif ($esSalida)                { $salidas++;  $flags['salida']  = true; $cat = 'salida'; }
        else                              { $flags['estancia'] = true; $cat = 'estancia'; }

        $eInfo  = hcal_estado_info($estado);
        $habNumR = (string)($rr['habitacion_numero'] ?? ($hcalHabNumById[(int)$habId] ?? '—'));
        $noches  = ($ent && $sal)
            ? max(1, (int)round((strtotime($sal) - strtotime($ent)) / 86400))
            : 0;

        $reservasDia[] = [
            'id'      => $rid,
            'key'     => $eInfo['key'],
            'icon'    => $eInfo['icon'],
            'label'   => $eInfo['label'],
            'nombre'  => (string)($rr['huesped_nombre'] ?? 'Reserva'),
            'hab'     => $habNumR,
            'tipo'    => (string)($hcalHabTipoById[(int)$habId] ?? ''),
            'entrada' => $ent,
            'salida'  => $sal,
            'noches'  => $noches,
            'cat'     => $cat,
            'w'       => $hcalPesoEstado[$cat] ?? 4,
        ];
    }

    usort($reservasDia, function ($a, $b) {
        return ($a['w'] <=> $b['w']) ?: strcmp($a['nombre'], $b['nombre']);
    });

    $dots = array_values(array_filter([
        $flags['llegada']  ? 'llegada'  : null,
        $flags['estancia'] ? 'estancia' : null,
        $flags['salida']   ? 'salida'   : null,
        $flags['cancelada']? 'cancelada': null,
    ]));

    $hcalDaysData[$d] = [
        'fecha'     => $fTexto,
        'diaSemana' => $hcalDiasNombres[(int)date('w', $fTs)] ?? '',
        'ocupadas'  => $ocupadas,
        'llegadas'  => $llegadas,
        'salidas'   => $salidas,
        'libres'    => max(0, count($habitaciones) - $ocupadas),
        'dots'      => $dots,
        'reservas'  => $reservasDia,
    ];
}
$hcalDefaultDay = $esEsteMes ? $hoyNum : 1;
$hcalTotalHabs  = count($habitaciones);
?>
<style>
@import url('<?= asset('vendor/fonts/marca2.css') ?>');

/* ── Root tokens ─────────────────────────────────────────────── */
.hcal {
    --green:         #596066;
    --green-dark:    #424850;
    --green-soft:    #EDEFF1;
    --hcal-action-bg: var(--brand-action-bg, var(--green));
    --hcal-action-bg-hover: var(--brand-action-bg-hover, var(--green-dark));
    --hcal-action-text: var(--brand-action-text, #FFFEFB);
    --blue:          #2F6EA8;
    --blue-dark:     #1E5C9A;
    --blue-soft:     #EAF3FF;
    --indigo:        #5B5EA6;
    --indigo-soft:   #EDEDF8;
    --red:           #B9463D;
    --red-soft:      #FEF2F1;
    --amber:         #C8956C;
    --amber-soft:    #FDF3EC;
    --yellow:        #B66A00;
    --yellow-soft:   #FFF4D8;
    --surface:       #F5F6F7;
    --card:          #FFFFFF;
    --border:        rgba(89,96,102,.12);
    --border-mid:    rgba(89,96,102,.2);
    --border-strong: rgba(89,96,102,.32);
    --text:          #1E2226;
    --muted:         #5E666E;
    --subtle:        #98A0A8;
    --radius:        14px;
    --shadow:        0 1px 3px rgba(30,34,38,.06), 0 4px 16px rgba(30,34,38,.07);
    --shadow-md:     0 2px 8px rgba(30,34,38,.08), 0 8px 24px rgba(30,34,38,.09);

    font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
    color: var(--text);
    background: var(--surface);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}
.hcal *, .hcal *::before, .hcal *::after { box-sizing: border-box; }
.hcal h1, .hcal h2, .hcal h3, .hcal h4 {
    font-family: 'Outfit', 'DM Sans', sans-serif;
    letter-spacing: -.01em;
    margin: 0;
}
.hcal i[class*="fa-"], .hcal .fas, .hcal .far {
    font-family: "Font Awesome 5 Free", "FontAwesome" !important;
    font-style: normal;
}
.hcal .fas { font-weight: 900; }
.hcal a { color: inherit; text-decoration: none; }

/* ── Shell ────────────────────────────────────────────────────── */
.hcal-shell {
    width: 100%;
    padding: 22px 20px 36px;
}

/* ── Breadcrumb ───────────────────────────────────────────────── */
.hcal-crumb {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 18px;
    font-size: .76rem;
    font-weight: 600;
    color: var(--subtle);
}
.hcal-crumb a {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: var(--green);
    transition: opacity .15s;
}
.hcal-crumb a:hover { opacity: .7; }
.hcal-crumb-sep { font-size: .65rem; opacity: .4; }

/* ── Page header ──────────────────────────────────────────────── */
.hcal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 18px;
    margin-bottom: 20px;
}
.hcal-title-block h1 {
    font-size: clamp(1.7rem, 3.5vw, 2.5rem);
    font-weight: 800;
    color: var(--text);
    line-height: 1.05;
}
.hcal-title-block h1 span {
    display: block;
    font-size: .76rem;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    color: var(--subtle);
    margin-bottom: 4px;
    letter-spacing: 0;
}
.hcal-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 12px;
    align-items: center;
}
.hcal-stat {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 700;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--muted);
}
.hcal-stat-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: currentColor;
}
.hcal-stat.is-amber  { color: var(--yellow);  background: var(--yellow-soft);  border-color: rgba(182,106,0,.2); }
.hcal-stat.is-blue   { color: var(--blue);    background: var(--blue-soft);    border-color: rgba(47,110,168,.2); }
.hcal-stat.is-green  { color: var(--green);   background: var(--green-soft);   border-color: rgba(89,96,102,.2); }
.hcal-stat.is-indigo { color: var(--indigo);  background: var(--indigo-soft);  border-color: rgba(91,94,166,.2); }
.hcal-stat.is-red    { color: var(--red);     background: var(--red-soft);     border-color: rgba(185,70,61,.2); }

/* ── Controls: month nav + actions ───────────────────────────── */
.hcal-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}
.hcal-month-nav {
    display: inline-flex;
    align-items: stretch;
    background: var(--card);
    border: 1px solid var(--border-mid);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
}
.hcal-mnav-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    height: 42px;
    padding: 0 14px;
    border: none;
    background: transparent;
    color: var(--muted);
    font-family: inherit;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .14s, color .14s;
    white-space: nowrap;
}
.hcal-mnav-btn:hover { background: var(--green-soft); color: var(--green-dark); }
.hcal-mnav-center {
    display: flex;
    align-items: center;
    padding: 0 18px;
    height: 42px;
    border-left: 1px solid var(--border);
    border-right: 1px solid var(--border);
    font-family: 'Outfit', sans-serif;
    font-size: .98rem;
    font-weight: 700;
    color: var(--text);
    white-space: nowrap;
}
.hcal-mnav-today {
    border-left: 1px solid var(--border);
    font-size: .76rem;
    color: var(--green);
}
.hcal-mnav-today:hover { background: var(--green-soft); }

.hcal-actions {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.hcal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    height: 36px;
    padding: 0 13px;
    border-radius: 10px;
    font-family: inherit;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--border-mid);
    background: var(--card);
    color: var(--muted);
    box-shadow: 0 1px 3px rgba(30,34,38,.05);
    transition: background .14s, color .14s, border-color .14s, transform .14s;
}
.hcal-btn:hover {
    background: var(--green-soft);
    color: var(--green-dark);
    border-color: rgba(89,96,102,.28);
    transform: translateY(-1px);
}
.hcal-btn.is-primary {
    background: var(--hcal-action-bg);
    color: var(--hcal-action-text) !important;
    border-color: color-mix(in srgb, var(--hcal-action-bg) 78%, #000);
    box-shadow: 0 8px 18px -12px color-mix(in srgb, var(--hcal-action-bg) 70%, transparent);
}
.hcal-btn.is-primary i { color: currentColor !important; }
.hcal-btn.is-primary:hover {
    background: var(--hcal-action-bg-hover);
    color: var(--hcal-action-text) !important;
    border-color: color-mix(in srgb, var(--hcal-action-bg-hover) 78%, #000);
}

/* ── 2-col layout ─────────────────────────────────────────────── */
.hcal-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 310px;
    gap: 18px;
    align-items: start;
}

/* ── Card ─────────────────────────────────────────────────────── */
.hcal-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.hcal-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--border);
}
.hcal-card-head h2 { font-size: .92rem; font-weight: 700; color: var(--text); }
.hcal-card-head p  { font-size: .76rem; color: var(--subtle); font-weight: 500; margin: 2px 0 0; }

/* ── Legend ───────────────────────────────────────────────────── */
.hcal-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}
.hcal-leg {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: .69rem;
    font-weight: 700;
    border: 1px solid transparent;
}
.hcal-leg-dot { width: 8px; height: 8px; border-radius: 3px; }
.hcal-leg.is-confirmada  { background: var(--blue-soft);   border-color: rgba(47,110,168,.18); color: var(--blue-dark); }
.hcal-leg.is-checked-in  { background: var(--green-soft);  border-color: rgba(89,96,102,.18);  color: var(--green-dark); }
.hcal-leg.is-checked-out { background: var(--indigo-soft); border-color: rgba(91,94,166,.18);  color: var(--indigo); }
.hcal-leg.is-cancelada   { background: var(--red-soft);    border-color: rgba(185,70,61,.18);  color: var(--red); }
.hcal-leg.is-libre       { background: var(--surface);     border-color: var(--border-mid);    color: var(--subtle); }
.hcal-leg.is-confirmada  .hcal-leg-dot { background: var(--blue); }
.hcal-leg.is-checked-in  .hcal-leg-dot { background: var(--green); }
.hcal-leg.is-checked-out .hcal-leg-dot { background: var(--indigo); }
.hcal-leg.is-cancelada   .hcal-leg-dot { background: var(--red); }
.hcal-leg.is-libre       .hcal-leg-dot { background: transparent; border: 1px dashed var(--subtle); }

/* ── Calendar scroll + table ──────────────────────────────────── */
.hcal-scroll {
    overflow: auto;
    max-height: calc(100dvh - 260px);
    min-height: 420px;
    scrollbar-width: thin;
    scrollbar-color: rgba(89,96,102,.2) var(--surface);
}
.hcal-scroll::-webkit-scrollbar { width: 7px; height: 7px; }
.hcal-scroll::-webkit-scrollbar-track { background: var(--surface); }
.hcal-scroll::-webkit-scrollbar-thumb {
    background: rgba(89,96,102,.22);
    border-radius: 999px;
    border: 2px solid var(--surface);
}

.hcal-table {
    width: 100%;
    min-width: <?= max(1000, 196 + ($diasEnMes * 52)) ?>px;
    border-collapse: collapse;
    table-layout: fixed;
}

/* Sticky room column */
.hcal-sticky {
    position: sticky;
    left: 0;
    z-index: 8;
    width: 196px;
    min-width: 196px;
}

/* Day header cells */
.hcal-day-th {
    position: sticky;
    top: 0;
    z-index: 6;
    width: 52px;
    min-width: 52px;
    padding: 8px 2px 6px;
    background: var(--card);
    border-bottom: 2px solid var(--border-mid);
    text-align: center;
    vertical-align: middle;
}
.hcal-day-th.is-weekend { background: color-mix(in srgb, var(--amber-soft) 50%, var(--card)); }
.hcal-day-th.is-today   { background: var(--blue-soft); border-bottom-color: var(--blue); }

.hcal-day-abbr {
    display: block;
    font-size: .58rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--subtle);
    line-height: 1;
    margin-bottom: 4px;
}
.hcal-day-th.is-weekend .hcal-day-abbr { color: var(--amber); }
.hcal-day-th.is-today   .hcal-day-abbr { color: var(--blue); }

.hcal-day-num {
    display: block;
    font-family: 'Outfit', sans-serif;
    font-size: .95rem;
    font-weight: 800;
    color: var(--text);
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.hcal-day-th.is-today .hcal-day-num { color: var(--blue-dark); }

.hcal-today-pip {
    display: block;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: var(--blue);
    margin: 4px auto 0;
}

/* Room-column header */
.hcal-room-th {
    position: sticky;
    top: 0; left: 0;
    z-index: 12;
    padding: 10px 12px;
    background: var(--card);
    border-bottom: 2px solid var(--border-mid);
    border-right: 2px solid var(--border-mid);
    text-align: left;
    vertical-align: middle;
}
.hcal-room-th span {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: .07em;
    text-transform: uppercase;
    color: var(--subtle);
}

/* Room label cell */
.hcal-room-td {
    padding: 6px 10px 6px 12px;
    background: var(--card);
    border-bottom: 1px solid var(--border);
    border-right: 2px solid var(--border-mid);
    vertical-align: middle;
}
.hcal-room-row {
    display: flex;
    align-items: center;
    gap: 9px;
}
.hcal-room-badge {
    flex: 0 0 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 9px;
    background: var(--green-soft);
    border: 1px solid rgba(89,96,102,.16);
    font-family: 'Outfit', sans-serif;
    font-size: .88rem;
    font-weight: 800;
    color: var(--green-dark);
    font-variant-numeric: tabular-nums;
}
.hcal-room-info { min-width: 0; }
.hcal-room-name {
    display: block;
    font-size: .8rem;
    font-weight: 700;
    color: var(--text);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.hcal-room-type {
    display: block;
    font-size: .68rem;
    font-weight: 500;
    color: var(--subtle);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 1px;
}

/* Calendar data cells */
.hcal-cell {
    height: 54px;
    padding: 5px 3px;
    border-bottom: 1px solid var(--border);
    border-left: 1px solid var(--border);
    vertical-align: middle;
    background: var(--card);
}
.hcal-cell.is-weekend { background: color-mix(in srgb, var(--amber-soft) 28%, var(--card)); }
.hcal-cell.is-today   { background: color-mix(in srgb, var(--blue-soft) 70%, var(--card)); border-left-color: rgba(47,110,168,.22); }
.hcal-cell.is-week-sep { border-left: 2px solid var(--border-strong); }

/* Row hover */
tr:hover .hcal-cell                { background: color-mix(in srgb, var(--green-soft) 50%, var(--card)); }
tr:hover .hcal-cell.is-today       { background: color-mix(in srgb, var(--blue-soft) 60%, var(--green-soft)); }
tr:hover .hcal-room-td             { background: var(--green-soft); }

/* Continuous bar padding adjustments */
.hcal-cell.is-cont-left  { padding-left: 0; }
.hcal-cell.is-cont-right { padding-right: 0; }

/* ── Reservation bar ──────────────────────────────────────────── */
.hcal-res-bar {
    width: 100%;
    height: 44px;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 8px;
    border-radius: 8px;
    border: 1px solid transparent;
    font-family: inherit;
    font-size: .71rem;
    font-weight: 700;
    cursor: pointer;
    overflow: hidden;
    transition: filter .13s, transform .13s;
}
.hcal-res-bar:hover  { filter: brightness(.91); }
.hcal-res-bar:active { transform: scale(.98); filter: brightness(.86); }
.hcal-res-bar.cont-left  { border-top-left-radius: 0;  border-bottom-left-radius: 0;  border-left-color: transparent;  margin-left: -1px;  }
.hcal-res-bar.cont-right { border-top-right-radius: 0; border-bottom-right-radius: 0; border-right-color: transparent; margin-right: -1px; }

.hcal-res-bar.is-confirmada  { background: var(--blue-soft);   border-color: rgba(47,110,168,.38);  color: var(--blue-dark); }
.hcal-res-bar.is-checked-in  { background: var(--green-soft);  border-color: rgba(89,96,102,.34);   color: var(--green-dark); }
.hcal-res-bar.is-checked-out { background: var(--indigo-soft); border-color: rgba(91,94,166,.32);   color: var(--indigo); }
.hcal-res-bar.is-cancelada   { background: var(--red-soft);    border-color: rgba(185,70,61,.28);   color: var(--red); opacity: .8; }

.hcal-res-bar i    { font-size: .7rem; flex-shrink: 0; }
.hcal-res-bar span { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* ── Available cell button ────────────────────────────────────── */
.hcal-avail-btn {
    width: 100%;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    border: 1px dashed var(--border-mid);
    background: transparent;
    color: var(--subtle);
    font-family: inherit;
    font-size: .7rem;
    cursor: pointer;
    opacity: .45;
    transition: background .13s, border-color .13s, color .13s, opacity .13s;
}
.hcal-avail-btn:hover {
    background: var(--green-soft);
    border-color: rgba(89,96,102,.3);
    color: var(--green);
    opacity: 1;
}

/* ── Sidebar ──────────────────────────────────────────────────── */
.hcal-side {
    display: flex;
    flex-direction: column;
    gap: 12px;
    position: sticky;
    top: 20px;
}

/* Occupancy ring */
.hcal-card-body { padding: 14px 16px; }
.hcal-occ-wrap {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 6px 0 12px;
}
.hcal-occ-ring {
    flex-shrink: 0;
    position: relative;
    width: 68px; height: 68px;
}
.hcal-occ-ring svg { width: 68px; height: 68px; transform: rotate(-90deg); }
.hcal-occ-pct {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    font-family: 'Outfit', sans-serif;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--text);
}
.hcal-occ-meta strong {
    display: block;
    font-family: 'Outfit', sans-serif;
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
    line-height: 1.2;
}
.hcal-occ-meta span { font-size: .74rem; color: var(--muted); font-weight: 500; }

.hcal-mini-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.hcal-mini {
    padding: 10px 11px;
    border-radius: 10px;
    background: var(--surface);
    border: 1px solid var(--border);
}
.hcal-mini span   { display: block; font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--subtle); }
.hcal-mini strong { display: block; font-family: 'Outfit', sans-serif; font-size: 1.3rem; font-weight: 800; color: var(--text); margin-top: 3px; }

/* Today list */
.hcal-today-list { display: flex; flex-direction: column; gap: 6px; padding: 12px 14px 14px; }
.hcal-today-item {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 11px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    transition: background .13s, border-color .13s, transform .13s;
}
.hcal-today-item:hover {
    background: var(--green-soft);
    border-color: rgba(89,96,102,.26);
    transform: translateY(-1px);
}
.hcal-today-ico {
    flex-shrink: 0;
    width: 32px; height: 32px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    font-size: .76rem;
}
.hcal-today-ico.is-confirmada  { background: var(--blue-soft);   color: var(--blue); }
.hcal-today-ico.is-checked-in  { background: var(--green-soft);  color: var(--green); }
.hcal-today-ico.is-checked-out { background: var(--indigo-soft); color: var(--indigo); }
.hcal-today-ico.is-cancelada   { background: var(--red-soft);    color: var(--red); }
.hcal-today-body { flex: 1; min-width: 0; }
.hcal-today-body strong { display: block; font-size: .8rem; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.hcal-today-body span   { display: block; font-size: .7rem; color: var(--muted); font-weight: 500; margin-top: 1px; }
.hcal-today-pill {
    flex-shrink: 0;
    padding: 3px 7px;
    border-radius: 6px;
    font-size: .66rem;
    font-weight: 700;
}
.hcal-today-pill.is-confirmada  { background: var(--blue-soft);   color: var(--blue-dark); }
.hcal-today-pill.is-checked-in  { background: var(--green-soft);  color: var(--green-dark); }
.hcal-today-pill.is-checked-out { background: var(--indigo-soft); color: var(--indigo); }
.hcal-today-pill.is-cancelada   { background: var(--red-soft);    color: var(--red); }

.hcal-empty-box {
    padding: 20px 16px;
    text-align: center;
    color: var(--subtle);
    font-size: .8rem;
    font-weight: 500;
}
.hcal-empty-box i { display: block; font-size: 1.5rem; opacity: .35; margin-bottom: 8px; }

/* Quick actions */
.hcal-side-actions { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 6px; }
.hcal-side-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--surface);
    color: var(--muted);
    font-family: inherit;
    font-size: .8rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .13s, border-color .13s, color .13s;
}
.hcal-side-btn:hover { background: var(--green-soft); border-color: rgba(89,96,102,.26); color: var(--green-dark); }
.hcal-side-btn i { width: 15px; text-align: center; font-size: .78rem; }

/* ── Reservation tooltip ──────────────────────────────────────── */
#hcalTooltip {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
    opacity: 0;
    transform: translateY(6px) scale(.97);
    transition: opacity .16s ease, transform .16s ease;
    will-change: opacity, transform;
}
#hcalTooltip.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
}
.hcal-tt {
    min-width: 230px;
    max-width: 290px;
    padding: 14px 16px 13px;
    background: #fff;
    border: 1px solid #D1D9CC;
    border-radius: 13px;
    box-shadow: 0 12px 32px -8px rgba(0,0,0,.18), 0 2px 8px rgba(0,0,0,.10);
}
.hcal-tt-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 11px;
}
.hcal-tt-name {
    font-family: 'Outfit', sans-serif;
    font-size: .92rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.25;
    flex: 1;
    min-width: 0;
}
.hcal-tt-badge {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: .69rem;
    font-weight: 700;
    white-space: nowrap;
}
.hcal-tt-badge.is-confirmada  { background: #DBEAFE; color: #1D4ED8; }
.hcal-tt-badge.is-checked-in  { background: #E9ECEF; color: #424850; }
.hcal-tt-badge.is-checked-out { background: #EDE9FE; color: #5B21B6; }
.hcal-tt-badge.is-cancelada   { background: #FEE2E2; color: #B91C1C; }

.hcal-tt-rows { display: flex; flex-direction: column; gap: 7px; }
.hcal-tt-row {
    display: flex;
    align-items: center;
    gap: 9px;
    font-size: .8rem;
    color: #374151;
    font-weight: 500;
    line-height: 1.3;
}
.hcal-tt-row i {
    flex-shrink: 0;
    width: 15px;
    text-align: center;
    font-size: .72rem;
    color: #596066;
}
.hcal-tt-row strong { color: #111827; font-weight: 700; }
.hcal-tt-divider {
    height: 1px;
    background: #E5E7EB;
    margin: 10px 0 9px;
}
.hcal-tt-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .75rem;
    color: #6B7280;
    font-weight: 600;
}
.hcal-tt-nights {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 6px;
    background: #F3F4F6;
    border: 1px solid #E5E7EB;
    font-weight: 700;
    color: #374151;
    font-size: .73rem;
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 1200px) {
    .hcal-layout { grid-template-columns: 1fr; }
    .hcal-side { position: static; flex-direction: row; flex-wrap: wrap; }
    .hcal-side .hcal-card { flex: 1 1 240px; }
}
@media (max-width: 720px) {
    .hcal-shell { padding: 13px 13px 36px; }
    .hcal-header { flex-direction: column; }
    .hcal-controls { align-items: stretch; width: 100%; }
    .hcal-month-nav { width: 100%; }
    .hcal-actions { justify-content: stretch; }
    .hcal-btn { flex: 1; }
    .hcal-sticky { width: 118px; min-width: 118px; }
    .hcal-room-badge { flex: 0 0 30px; height: 30px; font-size: .78rem; border-radius: 7px; }
    .hcal-room-type { display: none; }
    .hcal-cell { height: 46px; padding: 4px 2px; }
    .hcal-res-bar { height: 38px; }
    .hcal-res-bar span { display: none; }
    .hcal-avail-btn { height: 38px; }
    .hcal-table { min-width: <?= max(680, 118 + ($diasEnMes * 40)) ?>px !important; }
    .hcal-day-th { width: 40px; min-width: 40px; }
    .hcal-mnav-label { display: none; }
}
@media (prefers-reduced-motion: reduce) {
    .hcal *, .hcal *::before, .hcal *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
    }
}

/* ══════════════════════════════════════════════════════════════
   ESCRITORIO · vista Mes + toggle Mes/Habitaciones
   ══════════════════════════════════════════════════════════════ */
.hcal-calcol { display: flex; flex-direction: column; gap: 18px; min-width: 0; }
.hcal-calcol .hcal-tableview { display: none; }
.hcal-calcol.show-hab .hcal-monthview { display: none; }
.hcal-calcol.show-hab .hcal-tableview { display: block; }

.hcal-viewtoggle {
    display: inline-flex; gap: 2px; padding: 3px;
    background: var(--card); border: 1px solid var(--border);
    border-radius: 12px; box-shadow: var(--shadow);
}
.hcal-vt-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border: 0; border-radius: 9px; cursor: pointer;
    background: transparent; color: var(--muted);
    font-family: inherit; font-size: .8rem; font-weight: 600;
    transition: background .15s ease, color .15s ease;
}
.hcal-vt-btn i { font-size: .76rem; }
.hcal-vt-btn.is-on { background: var(--green); color: #fff; box-shadow: var(--shadow); }

.hcal-mv { padding: 6px 16px 18px; }
/* minmax(0,1fr): sin piso min-content — nombres largos no ensanchan las
   columnas (con 1fr el mes crecía a ~1870px y la card lo recortaba). */
.hcal-mv-week { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); margin-bottom: 8px; }
.hcal-mv-week span {
    text-align: center; font-size: .72rem; font-weight: 700;
    letter-spacing: .04em; text-transform: uppercase; color: var(--subtle); padding: 4px 0;
}
.hcal-mv-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 8px; }
.hcal-mv-cell {
    min-height: 120px; border-radius: 12px;
    border: 1px solid var(--border); background: var(--card);
    padding: 8px; display: flex; flex-direction: column; gap: 5px;
    transition: box-shadow .15s ease, border-color .15s ease;
}
.hcal-mv-cell.is-blank { background: transparent; border-color: transparent; }
.hcal-mv-cell.is-weekend { background: color-mix(in srgb, var(--amber-soft) 38%, var(--card)); }
.hcal-mv-cell.is-past { opacity: .6; }
.hcal-mv-cell.is-today { border-color: var(--blue); box-shadow: inset 0 0 0 1px var(--blue); }
.hcal-mv-cell:not(.is-blank):hover { box-shadow: var(--shadow-md); }

.hcal-mv-cell-top { display: flex; align-items: center; justify-content: space-between; min-height: 24px; }
.hcal-mv-num { font-family: 'Outfit', sans-serif; font-size: .95rem; font-weight: 700; color: var(--text); }
.hcal-mv-cell.is-today .hcal-mv-num {
    background: var(--blue); color: #fff; width: 24px; height: 24px;
    border-radius: 50%; display: grid; place-items: center; font-size: .8rem;
}
.hcal-mv-flags { display: flex; gap: 4px; }
.hcal-mv-flag {
    display: inline-flex; align-items: center; gap: 2px;
    font-size: .64rem; font-weight: 700; padding: 1px 5px; border-radius: 6px;
}
.hcal-mv-flag i { font-size: .56rem; }
.hcal-mv-flag.is-in  { background: var(--green-soft); color: var(--green); }
.hcal-mv-flag.is-out { background: var(--amber-soft); color: var(--amber); }

.hcal-mv-chips { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.hcal-mv-chip {
    display: flex; align-items: center; gap: 6px;
    padding: 3px 6px; border-radius: 7px; min-width: 0;
    background: var(--green-soft); font-size: .72rem; font-weight: 600; color: var(--text);
    transition: filter .12s ease;
}
.hcal-mv-chip.is-extra { display: none; }
.hcal-mv-cell.is-expanded .hcal-mv-chip.is-extra { display: flex; }
.hcal-mv-chip-dot { width: 6px; height: 6px; border-radius: 50%; flex: none; background: var(--subtle); }
.hcal-mv-chip-txt { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hcal-mv-chip-hab {
    flex: none; font-size: .63rem; font-weight: 700; color: var(--muted);
    background: rgba(0,0,0,.045); padding: 0 5px; border-radius: 5px;
}
.hcal-mv-chip.is-confirmada  { background: var(--blue-soft); }
.hcal-mv-chip.is-confirmada  .hcal-mv-chip-dot { background: var(--blue); }
.hcal-mv-chip.is-checked-in  { background: var(--green-soft); }
.hcal-mv-chip.is-checked-in  .hcal-mv-chip-dot { background: var(--green); }
.hcal-mv-chip.is-checked-out { background: var(--amber-soft); }
.hcal-mv-chip.is-checked-out .hcal-mv-chip-dot { background: var(--amber); }
.hcal-mv-chip.is-cancelada   { background: var(--red-soft); }
.hcal-mv-chip.is-cancelada   .hcal-mv-chip-dot { background: var(--red); }
.hcal-mv-chip.is-cancelada   .hcal-mv-chip-txt { text-decoration: line-through; opacity: .7; }
.hcal-mv-chip:hover { filter: brightness(.97); }

.hcal-mv-more {
    align-self: flex-start; border: 0; background: transparent; cursor: pointer;
    font-family: inherit; font-size: .68rem; font-weight: 700; color: var(--blue-dark); padding: 2px 4px;
}
.hcal-mv-more:hover { text-decoration: underline; }

.hcal-mv-free {
    margin: auto auto 6px; width: 28px; height: 28px; border-radius: 9px;
    display: grid; place-items: center; color: var(--border-strong);
    opacity: 0; transition: opacity .15s ease, background-color .15s ease, color .15s ease;
}
.hcal-mv-cell:hover .hcal-mv-free { opacity: 1; }
.hcal-mv-free:hover { background: var(--green-soft); color: var(--green); }

@media (max-width: 1200px) {
    .hcal-viewtoggle { order: 3; }
}

/* ══════════════════════════════════════════════════════════════
   VISTA MÓVIL · agenda (rediseño tipo app de calendario)
   ══════════════════════════════════════════════════════════════ */
.hcal-m { display: none; }

@media (max-width: 767px) {
    /* Ocultar la vista de escritorio (la tabla queda en el DOM para
       exportar CSV / imprimir, solo oculta visualmente). */
    .hcal-header, .hcal-controls, .hcal-layout { display: none !important; }
    .hcal-shell { padding: 12px 14px 28px; }
    .hcal-m { display: block; }

    /* ── Encabezado de mes ── */
    .hcal-m-top {
        display: flex; align-items: flex-end; justify-content: space-between;
        gap: 12px; margin: 4px 2px 16px;
    }
    .hcal-m-eyebrow {
        display: block; font-size: .68rem; font-weight: 600; letter-spacing: .08em;
        text-transform: uppercase; color: var(--subtle); margin-bottom: 3px;
    }
    .hcal-m-month {
        font-family: 'Outfit', sans-serif; font-size: 1.72rem; font-weight: 700;
        color: var(--text); line-height: 1; letter-spacing: -.02em;
    }
    .hcal-m-month span { color: var(--subtle); font-weight: 500; }
    .hcal-m-nav { display: flex; align-items: center; gap: 6px; flex: none; }
    .hcal-m-navbtn {
        width: 34px; height: 34px; border-radius: 11px;
        display: grid; place-items: center; flex: none;
        background: var(--card); border: 1px solid var(--border);
        color: var(--text); font-size: .8rem; box-shadow: var(--shadow);
    }
    .hcal-m-navbtn:active { transform: scale(.94); }
    .hcal-m-today {
        height: 34px; padding: 0 13px; border-radius: 11px;
        background: var(--green); color: #fff; border: 0;
        font-family: inherit; font-size: .78rem; font-weight: 600; cursor: pointer;
        box-shadow: var(--shadow);
    }
    .hcal-m-today:active { transform: scale(.96); }

    /* ── Métricas ── */
    .hcal-m-stats {
        display: grid; grid-template-columns: repeat(3, 1fr); gap: 9px;
        margin-bottom: 16px;
    }
    .hcal-m-stat {
        background: var(--card); border: 1px solid var(--border);
        border-radius: 15px; padding: 12px 10px; text-align: center;
        box-shadow: var(--shadow);
    }
    .hcal-m-stat-v {
        display: block; font-family: 'Outfit', sans-serif; font-size: 1.5rem;
        font-weight: 700; color: var(--text); line-height: 1;
    }
    .hcal-m-stat-v small { font-size: .9rem; font-weight: 600; color: var(--subtle); }
    .hcal-m-stat-l {
        display: block; font-size: .68rem; font-weight: 500; color: var(--muted);
        margin-top: 5px; letter-spacing: .01em;
    }

    /* ── Cuadrícula ── */
    .hcal-m-cal {
        background: var(--card); border: 1px solid var(--border);
        border-radius: 20px; padding: 14px 10px 10px; box-shadow: var(--shadow-md);
        margin-bottom: 16px;
    }
    .hcal-m-week {
        display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));
        margin-bottom: 6px;
    }
    .hcal-m-week span {
        text-align: center; font-size: .66rem; font-weight: 700;
        letter-spacing: .04em; text-transform: uppercase; color: var(--subtle);
    }
    .hcal-m-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); }
    .hcal-m-blank { height: 50px; }
    .hcal-m-day {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; gap: 3px; height: 50px;
        border: 0; background: none; cursor: pointer; padding: 0;
        font-family: inherit; -webkit-tap-highlight-color: transparent;
    }
    .hcal-m-daynum {
        width: 36px; height: 36px; border-radius: 50%;
        display: grid; place-items: center;
        font-size: .92rem; font-weight: 600; color: var(--text);
        transition: background-color .16s ease, color .16s ease, box-shadow .16s ease;
    }
    .hcal-m-day.has-act .hcal-m-daynum { background: var(--green-soft); }
    .hcal-m-day.is-today .hcal-m-daynum {
        box-shadow: inset 0 0 0 2px var(--blue); color: var(--blue-dark); font-weight: 700;
    }
    .hcal-m-day.is-sel .hcal-m-daynum {
        background: var(--green) !important; color: #fff !important; box-shadow: none;
    }
    .hcal-m-day.is-past { opacity: .4; }
    .hcal-m-day:active .hcal-m-daynum { transform: scale(.9); }
    .hcal-m-dots { height: 6px; display: flex; gap: 3px; align-items: center; justify-content: center; }
    .hcal-m-dot { width: 5px; height: 5px; border-radius: 50%; }
    .hcal-m-dot.is-llegada  { background: var(--green); }
    .hcal-m-dot.is-estancia { background: var(--blue); }
    .hcal-m-dot.is-salida   { background: var(--amber); }
    .hcal-m-dot.is-cancelada{ background: var(--red); }
    .hcal-m-day.is-sel .hcal-m-dot { background: rgba(255,255,255,.9) !important; }

    /* ── Agenda del día ── */
    .hcal-m-agenda { margin-top: 2px; }
    .hcal-m-agenda-head {
        display: flex; align-items: center; gap: 12px; margin: 0 2px 12px;
    }
    .hcal-m-agenda-daynum {
        font-family: 'Outfit', sans-serif; font-size: 2.4rem; font-weight: 700;
        color: var(--green); line-height: .9; flex: none; min-width: 46px; text-align: center;
    }
    .hcal-m-agenda-meta { flex: 1; min-width: 0; }
    .hcal-m-agenda-meta strong {
        display: block; font-family: 'Outfit', sans-serif; font-size: 1rem;
        font-weight: 600; color: var(--text);
    }
    .hcal-m-agenda-meta span { font-size: .8rem; color: var(--muted); font-weight: 500; }
    .hcal-m-agenda-add {
        width: 42px; height: 42px; border-radius: 13px; flex: none;
        display: grid; place-items: center;
        background: var(--hcal-action-bg); color: var(--hcal-action-text) !important; font-size: 1rem;
        box-shadow: 0 6px 16px color-mix(in srgb, var(--hcal-action-bg) 34%, transparent);
    }
    .hcal-m-agenda-add i { color: currentColor !important; }
    .hcal-m-agenda-add:active { transform: scale(.94); }

    .hcal-m-agenda-list { display: flex; flex-direction: column; gap: 9px; }
    .hcal-m-ag-item {
        display: flex; align-items: center; gap: 12px;
        background: var(--card); border: 1px solid var(--border);
        border-radius: 16px; padding: 13px 14px; box-shadow: var(--shadow);
        position: relative; overflow: hidden;
    }
    .hcal-m-ag-item::before {
        content: ''; position: absolute; left: 0; top: 10px; bottom: 10px;
        width: 3px; border-radius: 0 3px 3px 0; background: var(--subtle);
    }
    .hcal-m-ag-item.is-confirmada::before  { background: var(--blue); }
    .hcal-m-ag-item.is-checked-in::before  { background: var(--green); }
    .hcal-m-ag-item.is-checked-out::before { background: var(--amber); }
    .hcal-m-ag-item.is-cancelada::before   { background: var(--red); }
    .hcal-m-ag-item:active { transform: scale(.99); }

    .hcal-m-ag-ico {
        width: 40px; height: 40px; border-radius: 12px; flex: none;
        display: grid; place-items: center; font-size: .92rem;
        background: var(--green-soft); color: var(--green);
    }
    .hcal-m-ag-item.is-confirmada  .hcal-m-ag-ico { background: var(--blue-soft);   color: var(--blue-dark); }
    .hcal-m-ag-item.is-checked-in  .hcal-m-ag-ico { background: var(--green-soft);  color: var(--green); }
    .hcal-m-ag-item.is-checked-out .hcal-m-ag-ico { background: var(--amber-soft);  color: var(--amber); }
    .hcal-m-ag-item.is-cancelada   .hcal-m-ag-ico { background: var(--red-soft);    color: var(--red); }

    .hcal-m-ag-body { flex: 1; min-width: 0; }
    .hcal-m-ag-body strong {
        display: block; font-size: .94rem; font-weight: 600; color: var(--text);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .hcal-m-ag-sub {
        display: block; font-size: .76rem; color: var(--muted); margin-top: 2px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .hcal-m-ag-side { flex: none; display: flex; flex-direction: column; align-items: flex-end; gap: 5px; }
    .hcal-m-ag-tag {
        font-size: .66rem; font-weight: 700; padding: 3px 9px; border-radius: 99px;
        letter-spacing: .01em; white-space: nowrap;
        background: color-mix(in srgb, var(--subtle) 16%, var(--card)); color: var(--muted);
    }
    .hcal-m-ag-tag.is-llegada  { background: var(--green-soft); color: var(--green); }
    .hcal-m-ag-tag.is-salida   { background: var(--amber-soft); color: var(--amber); }
    .hcal-m-ag-tag.is-estancia { background: var(--blue-soft);  color: var(--blue-dark); }
    .hcal-m-ag-tag.is-cancelada{ background: var(--red-soft);   color: var(--red); }
    .hcal-m-ag-range { font-size: .7rem; color: var(--subtle); font-weight: 600; }

    .hcal-m-empty {
        text-align: center; padding: 28px 18px;
        background: var(--card); border: 1px dashed var(--border-mid);
        border-radius: 16px; color: var(--muted);
    }
    .hcal-m-empty i { font-size: 1.5rem; color: var(--subtle); }
    .hcal-m-empty p { margin: 8px 0 14px; font-size: .86rem; }
    .hcal .hcal-m-empty-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 16px; border-radius: 11px;
        background: var(--hcal-action-bg);
        color: var(--hcal-action-text) !important;
        border: 1px solid color-mix(in srgb, var(--hcal-action-text) 18%, transparent);
        box-shadow: 0 8px 18px -12px color-mix(in srgb, var(--hcal-action-bg) 70%, transparent);
        font-size: .82rem; font-weight: 700;
    }
    .hcal .hcal-m-empty-btn i {
        color: currentColor !important;
        font-size: .82rem;
    }
    .hcal .hcal-m-empty-btn:hover {
        background: var(--hcal-action-bg-hover);
        color: var(--hcal-action-text) !important;
    }
}

/* ── Print ────────────────────────────────────────────────────── */
@media print {
    .hcal { background: #fff !important; }
    .hcal-crumb, .hcal-controls, .hcal-side { display: none !important; }
    .hcal-layout { display: block !important; }
    .hcal-scroll { max-height: none !important; overflow: visible !important; }
    .hcal-table  { min-width: 0 !important; font-size: 7.5pt; }
    .hcal-sticky, .hcal-day-th { position: static !important; }
    .hcal-cell, .hcal-res-bar, .hcal-avail-btn { height: 22px !important; min-height: 0 !important; }
    .hcal-res-bar { font-size: 6pt !important; padding: 0 3px !important; }
    @page { size: landscape; margin: 1cm; }
    tr { page-break-inside: avoid; }
}
</style>

<div class="hcal">
<div class="hcal-shell">

    <!-- Breadcrumb -->
    <nav class="hcal-crumb" aria-label="Navegación">
        <a href="<?= url('') ?>"><i class="fas fa-house"></i> Inicio</a>
        <span class="hcal-crumb-sep"><i class="fas fa-chevron-right"></i></span>
        <a href="<?= url('reservaciones') ?>">Reservaciones</a>
        <span class="hcal-crumb-sep"><i class="fas fa-chevron-right"></i></span>
        <span>Calendario</span>
    </nav>

    <?php
    $hcalCatLabel = ['llegada' => 'Llegada', 'salida' => 'Salida', 'estancia' => 'En estancia', 'cancelada' => 'Cancelada'];
    $hcalDefInfo  = $hcalDaysData[$hcalDefaultDay] ?? ['diaSemana' => '', 'reservas' => [], 'llegadas' => 0, 'salidas' => 0, 'ocupadas' => 0];
    $hcalDefFecha = $hcalDaysData[$hcalDefaultDay]['fecha'] ?? date('Y-m-d', $primerDia);
    $hcalResumen = function ($info) {
        $parts = [];
        if (($info['llegadas'] ?? 0) > 0) $parts[] = $info['llegadas'] . ' llegada' . ($info['llegadas'] > 1 ? 's' : '');
        if (($info['salidas'] ?? 0) > 0)  $parts[] = $info['salidas'] . ' salida' . ($info['salidas'] > 1 ? 's' : '');
        if (($info['ocupadas'] ?? 0) > 0 && empty($parts)) $parts[] = $info['ocupadas'] . ' en estancia';
        return $parts ? implode(' · ', $parts) : 'Sin movimientos';
    };
    ?>
    <!-- ═══════════ Vista móvil · agenda de reservaciones ═══════════ -->
    <div class="hcal-m" aria-label="Calendario de reservaciones">

        <!-- Encabezado de mes -->
        <div class="hcal-m-top">
            <div class="hcal-m-monthblock">
                <span class="hcal-m-eyebrow"><?= hcal_safe($hotelNombre) ?></span>
                <h1 class="hcal-m-month"><?= hcal_safe($mesesNombres[$mes] ?? '') ?> <span><?= (int)$año ?></span></h1>
            </div>
            <div class="hcal-m-nav">
                <a href="<?= hcal_mes_url($mesAnterior, $añoAnterior) ?>" class="hcal-m-navbtn" aria-label="Mes anterior"><i class="fas fa-chevron-left"></i></a>
                <button type="button" class="hcal-m-today" onclick="irAHoy()">Hoy</button>
                <a href="<?= hcal_mes_url($mesSiguiente, $añoSiguiente) ?>" class="hcal-m-navbtn" aria-label="Mes siguiente"><i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <!-- Métricas del mes -->
        <div class="hcal-m-stats">
            <div class="hcal-m-stat">
                <span class="hcal-m-stat-v"><?= (int)$ocupacion ?><small>%</small></span>
                <span class="hcal-m-stat-l">Ocupación</span>
            </div>
            <div class="hcal-m-stat">
                <span class="hcal-m-stat-v"><?= (int)$totalReservaciones ?></span>
                <span class="hcal-m-stat-l">Reservas</span>
            </div>
            <div class="hcal-m-stat">
                <span class="hcal-m-stat-v" style="color:var(--green)"><?= (int)$disponiblesHoy ?></span>
                <span class="hcal-m-stat-l">Libres hoy</span>
            </div>
        </div>

        <!-- Cuadrícula del mes -->
        <div class="hcal-m-cal">
            <div class="hcal-m-week">
                <?php foreach ($diasAbr as $ab): ?><span><?= $ab ?></span><?php endforeach; ?>
            </div>
            <div class="hcal-m-grid" id="hcalMGrid">
                <?php for ($b = 0; $b < $hcalPrimerDiaSemana; $b++): ?>
                    <span class="hcal-m-blank" aria-hidden="true"></span>
                <?php endfor; ?>
                <?php for ($d = 1; $d <= $diasEnMes; $d++):
                    $info    = $hcalDaysData[$d];
                    $isToday = ($esEsteMes && $d === $hoyNum);
                    $isSel   = ($d === $hcalDefaultDay);
                    $isPast  = ($esEsteMes && $d < $hoyNum);
                    $cls = 'hcal-m-day';
                    if (($info['ocupadas'] ?? 0) > 0) $cls .= ' has-act';
                    if ($isToday) $cls .= ' is-today';
                    if ($isSel)   $cls .= ' is-sel';
                    if ($isPast)  $cls .= ' is-past';
                ?>
                    <button type="button" class="<?= $cls ?>" data-day="<?= $d ?>" aria-label="Día <?= $d ?>">
                        <span class="hcal-m-daynum"><?= $d ?></span>
                        <span class="hcal-m-dots" aria-hidden="true">
                            <?php foreach ($info['dots'] as $dot): ?><i class="hcal-m-dot is-<?= hcal_safe($dot) ?>"></i><?php endforeach; ?>
                        </span>
                    </button>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Agenda del día seleccionado -->
        <div class="hcal-m-agenda">
            <div class="hcal-m-agenda-head">
                <div class="hcal-m-agenda-daynum" id="hcalMAgDay"><?= (int)$hcalDefaultDay ?></div>
                <div class="hcal-m-agenda-meta">
                    <strong id="hcalMAgWeekday"><?= hcal_safe($hcalDefInfo['diaSemana']) ?></strong>
                    <span id="hcalMAgSummary"><?= hcal_safe($hcalResumen($hcalDefInfo)) ?></span>
                </div>
                <a href="<?= url('reservaciones/crear') ?>?fecha_entrada=<?= hcal_safe($hcalDefFecha) ?>"
                   class="hcal-m-agenda-add" id="hcalMAgAdd" aria-label="Nueva reserva este día">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
            <div class="hcal-m-agenda-list" id="hcalMAgList">
                <?php if (empty($hcalDefInfo['reservas'])): ?>
                    <div class="hcal-m-empty">
                        <i class="fas fa-moon"></i>
                        <p>Sin reservaciones este día.</p>
                        <a href="<?= url('reservaciones/crear') ?>?fecha_entrada=<?= hcal_safe($hcalDefFecha) ?>" class="hcal-m-empty-btn">
                            <i class="fas fa-plus"></i> Crear reserva
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($hcalDefInfo['reservas'] as $res): ?>
                        <a href="<?= url('reservaciones/ver/' . (int)$res['id']) ?>" class="hcal-m-ag-item is-<?= hcal_safe($res['key']) ?>">
                            <span class="hcal-m-ag-ico"><i class="fas <?= hcal_safe($res['icon']) ?>"></i></span>
                            <span class="hcal-m-ag-body">
                                <strong><?= hcal_safe($res['nombre']) ?></strong>
                                <span class="hcal-m-ag-sub">Hab. <?= hcal_safe($res['hab']) ?><?= $res['tipo'] !== '' ? ' · ' . hcal_safe($res['tipo']) : '' ?></span>
                            </span>
                            <span class="hcal-m-ag-side">
                                <span class="hcal-m-ag-tag is-<?= hcal_safe($res['cat']) ?>"><?= hcal_safe($hcalCatLabel[$res['cat']] ?? $res['label']) ?></span>
                                <?php if ($res['noches'] > 0): ?><span class="hcal-m-ag-range"><i class="fas fa-moon"></i> <?= (int)$res['noches'] ?></span><?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="hcal-header">
        <div class="hcal-title-block">
            <h1>
                <span><?= hcal_safe($hotelNombre) ?></span>
                Calendario
            </h1>
            <div class="hcal-stats">
                <span class="hcal-stat is-amber">
                    <i class="fas fa-chart-pie" style="font-size:.68rem"></i>
                    <?= (int)$ocupacion ?>% ocupación
                </span>
                <span class="hcal-stat is-blue">
                    <span class="hcal-stat-dot"></span>
                    <?= (int)$confirmadas ?> confirmadas
                </span>
                <span class="hcal-stat is-green">
                    <span class="hcal-stat-dot"></span>
                    <?= (int)$checkIn ?> en estancia
                </span>
                <?php if ($checkOut > 0): ?>
                <span class="hcal-stat is-indigo">
                    <span class="hcal-stat-dot"></span>
                    <?= (int)$checkOut ?> check-out
                </span>
                <?php endif; ?>
                <?php if ($canceladas > 0): ?>
                <span class="hcal-stat is-red">
                    <span class="hcal-stat-dot"></span>
                    <?= (int)$canceladas ?> canceladas
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="hcal-controls">
            <div class="hcal-month-nav" role="navigation" aria-label="Navegar por mes">
                <a href="<?= hcal_mes_url($mesAnterior, $añoAnterior) ?>"
                   class="hcal-mnav-btn" data-month-nav="prev"
                   title="<?= hcal_safe($mesesNombres[$mesAnterior] . ' ' . $añoAnterior) ?>">
                    <i class="fas fa-chevron-left"></i>
                    <span class="hcal-mnav-label"><?= hcal_safe($mesesNombres[$mesAnterior]) ?></span>
                </a>
                <span class="hcal-mnav-center"><?= hcal_safe($mesActualTexto) ?></span>
                <a href="<?= hcal_mes_url($mesSiguiente, $añoSiguiente) ?>"
                   class="hcal-mnav-btn" data-month-nav="next"
                   title="<?= hcal_safe($mesesNombres[$mesSiguiente] . ' ' . $añoSiguiente) ?>">
                    <span class="hcal-mnav-label"><?= hcal_safe($mesesNombres[$mesSiguiente]) ?></span>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <button type="button" onclick="irAHoy()" class="hcal-mnav-btn hcal-mnav-today">
                    <i class="fas fa-circle-dot"></i> Hoy
                </button>
            </div>

            <div class="hcal-viewtoggle" role="tablist" aria-label="Tipo de vista">
                <button type="button" class="hcal-vt-btn is-on" data-view="mes" role="tab" aria-selected="true">
                    <i class="fas fa-calendar"></i> Mes
                </button>
                <button type="button" class="hcal-vt-btn" data-view="hab" role="tab" aria-selected="false">
                    <i class="fas fa-table-cells-large"></i> Habitaciones
                </button>
            </div>

            <div class="hcal-actions">
                <a href="<?= url('reservaciones') ?>" class="hcal-btn">
                    <i class="fas fa-list-ul"></i> Lista
                </a>
                <button type="button" onclick="exportarCalendario()" class="hcal-btn">
                    <i class="fas fa-file-csv"></i> CSV
                </button>
                <button type="button" onclick="imprimirCalendario()" class="hcal-btn ms-print-hide-mobile" title="Imprimir">
                    <i class="fas fa-print"></i>
                </button>
                <a href="<?= url('reservaciones/crear') ?>" class="hcal-btn is-primary">
                    <i class="fas fa-plus"></i> Nueva reserva
                </a>
            </div>
        </div>
    </div>

    <!-- Main layout -->
    <div class="hcal-layout">

      <!-- Columna del calendario (alterna entre vista Mes y vista Habitaciones) -->
      <div class="hcal-calcol" id="hcalCalCol">

        <!-- ═══ Vista Mes (grande, tipo agenda) ═══ -->
        <section class="hcal-card hcal-monthview" aria-label="Calendario mensual">
            <div class="hcal-card-head">
                <div>
                    <h2>
                        <i class="fas fa-calendar-day" style="color:var(--green);margin-right:7px;font-size:.85rem"></i>
                        <?= hcal_safe($mesesNombres[$mes] ?? '') ?> <?= (int)$año ?>
                    </h2>
                    <p>Clic en una <strong>reserva</strong> → abrir ficha &nbsp;·&nbsp; clic en <strong>+ N más</strong> → ver el día completo</p>
                </div>
                <div class="hcal-legend" aria-label="Leyenda de movimientos">
                    <span class="hcal-leg is-checked-in"><span class="hcal-leg-dot"></span>Llegada</span>
                    <span class="hcal-leg is-confirmada"><span class="hcal-leg-dot"></span>En estancia</span>
                    <span class="hcal-leg is-checked-out"><span class="hcal-leg-dot"></span>Salida</span>
                    <span class="hcal-leg is-cancelada"><span class="hcal-leg-dot"></span>Cancelada</span>
                </div>
            </div>

            <div class="hcal-mv">
                <div class="hcal-mv-week">
                    <?php foreach ($diasAbr as $ab): ?><span><?= $ab ?></span><?php endforeach; ?>
                </div>
                <div class="hcal-mv-grid">
                    <?php for ($b = 0; $b < $hcalPrimerDiaSemana; $b++): ?>
                        <div class="hcal-mv-cell is-blank" aria-hidden="true"></div>
                    <?php endfor; ?>
                    <?php
                    $mvCatColor = ['llegada' => 'checked-in', 'estancia' => 'confirmada', 'salida' => 'checked-out', 'cancelada' => 'cancelada'];
                    for ($d = 1; $d <= $diasEnMes; $d++):
                        $info    = $hcalDaysData[$d];
                        $fTs     = mktime(0, 0, 0, $mes, $d, $año);
                        $diaSem  = (int)date('w', $fTs);
                        $isWe    = ($diaSem === 0 || $diaSem === 6);
                        $isToday = ($esEsteMes && $d === $hoyNum);
                        $isPast  = ($esEsteMes && $d < $hoyNum);
                        $totRes  = count($info['reservas']);
                        $cls = 'hcal-mv-cell';
                        if ($isWe)    $cls .= ' is-weekend';
                        if ($isToday) $cls .= ' is-today';
                        if ($isPast)  $cls .= ' is-past';
                    ?>
                        <div class="<?= $cls ?>" data-day="<?= $d ?>">
                            <div class="hcal-mv-cell-top">
                                <span class="hcal-mv-num"><?= $d ?></span>
                                <?php if ($info['llegadas'] > 0 || $info['salidas'] > 0): ?>
                                    <span class="hcal-mv-flags">
                                        <?php if ($info['llegadas'] > 0): ?><span class="hcal-mv-flag is-in" title="Llegadas"><i class="fas fa-arrow-right-to-bracket"></i><?= (int)$info['llegadas'] ?></span><?php endif; ?>
                                        <?php if ($info['salidas'] > 0): ?><span class="hcal-mv-flag is-out" title="Salidas"><i class="fas fa-arrow-right-from-bracket"></i><?= (int)$info['salidas'] ?></span><?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($totRes > 0): ?>
                                <div class="hcal-mv-chips">
                                    <?php foreach ($info['reservas'] as $i => $res): ?>
                                        <a href="<?= url('reservaciones/ver/' . (int)$res['id']) ?>"
                                           class="hcal-mv-chip is-<?= hcal_safe($mvCatColor[$res['cat']] ?? 'confirmada') ?><?= $i >= 3 ? ' is-extra' : '' ?>"
                                           title="<?= hcal_safe($res['nombre']) ?> · Hab. <?= hcal_safe($res['hab']) ?>">
                                            <span class="hcal-mv-chip-dot"></span>
                                            <span class="hcal-mv-chip-txt"><?= hcal_safe(hcal_primer_nombre($res['nombre'])) ?></span>
                                            <span class="hcal-mv-chip-hab"><?= hcal_safe($res['hab']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php if ($totRes > 3): ?>
                                        <button type="button" class="hcal-mv-more" data-more><i class="fas fa-plus" style="font-size:.6rem"></i> <?= $totRes - 3 ?> más</button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <a href="<?= url('reservaciones/crear') ?>?fecha_entrada=<?= hcal_safe($info['fecha']) ?>" class="hcal-mv-free" title="Día libre · crear reserva">
                                    <i class="fas fa-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <!-- ═══ Vista Habitaciones (matriz habitación × días) ═══ -->
        <section class="hcal-card hcal-tableview" aria-label="Calendario mensual de habitaciones">
            <div class="hcal-card-head">
                <div>
                    <h2>
                        <i class="fas fa-table-cells-large" style="color:var(--green);margin-right:7px;font-size:.85rem"></i>
                        <?= count($habitaciones) ?> habitaciones &middot; <?= (int)$diasEnMes ?> días
                    </h2>
                    <p>Clic en una <strong>barra de color</strong> → abrir reserva &nbsp;·&nbsp; Clic en un espacio <strong>libre</strong> → nueva reserva</p>
                </div>
                <div class="hcal-legend" aria-label="Leyenda de estados">
                    <span class="hcal-leg is-confirmada"><span class="hcal-leg-dot"></span>Confirmada</span>
                    <span class="hcal-leg is-checked-in"><span class="hcal-leg-dot"></span>En estancia</span>
                    <span class="hcal-leg is-checked-out"><span class="hcal-leg-dot"></span>Check-out</span>
                    <span class="hcal-leg is-cancelada"><span class="hcal-leg-dot"></span>Cancelada</span>
                    <span class="hcal-leg is-libre"><span class="hcal-leg-dot"></span>Libre</span>
                </div>
            </div>

            <div class="hcal-scroll" id="hcalScroll">
                <table class="hcal-table" id="hcalTable">
                    <thead>
                        <tr>
                            <th class="hcal-sticky hcal-room-th">
                                <span><i class="fas fa-bed"></i>&nbsp; Habitación</span>
                            </th>
                            <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                <?php
                                $fTs     = mktime(0, 0, 0, $mes, $dia, $año);
                                $diaSem  = (int)date('w', $fTs);
                                $esWe    = ($diaSem === 0 || $diaSem === 6);
                                $esHoyTh = ($esEsteMes && $dia === $hoyNum);
                                $thCls   = 'hcal-day-th';
                                if ($esWe) $thCls .= ' is-weekend';
                                if ($esHoyTh) $thCls .= ' is-today';
                                ?>
                                <th class="<?= $thCls ?>">
                                    <span class="hcal-day-abbr"><?= $diasAbr[$diaSem] ?></span>
                                    <span class="hcal-day-num"><?= $dia ?></span>
                                    <?php if ($esHoyTh): ?><span class="hcal-today-pip"></span><?php endif; ?>
                                </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($habitaciones as $habitacion): ?>
                            <?php
                            $habId   = (int)($habitacion['id'] ?? 0);
                            $habNum  = (string)($habitacion['numero'] ?? '?');
                            $habTipo = ucfirst(str_replace('_', ' ', (string)($habitacion['tipo'] ?? 'Habitación')));
                            ?>
                            <tr>
                                <td class="hcal-sticky hcal-room-td">
                                    <div class="hcal-room-row">
                                        <span class="hcal-room-badge"><?= hcal_safe($habNum) ?></span>
                                        <span class="hcal-room-info">
                                            <span class="hcal-room-name">Hab. <?= hcal_safe($habNum) ?></span>
                                            <span class="hcal-room-type"><?= hcal_safe($habTipo) ?></span>
                                        </span>
                                    </div>
                                </td>

                                <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                    <?php
                                    $fTs     = mktime(0, 0, 0, $mes, $dia, $año);
                                    $fTexto  = date('Y-m-d', $fTs);
                                    $diaSem  = (int)date('w', $fTs);
                                    $esWe    = ($diaSem === 0 || $diaSem === 6);
                                    $esHoyTd = ($esEsteMes && $dia === $hoyNum);
                                    $esLunes = ($diaSem === 1);
                                    $reserva = $calendario[$dia][$habId] ?? null;

                                    $contLeft = $contRight = false;
                                    if ($reserva) {
                                        $rid      = (int)($reserva['id'] ?? 0);
                                        $prevR    = ($dia > 1)          ? ($calendario[$dia - 1][$habId] ?? null) : null;
                                        $nextR    = ($dia < $diasEnMes) ? ($calendario[$dia + 1][$habId] ?? null) : null;
                                        $contLeft  = $prevR && (int)($prevR['id'] ?? 0) === $rid;
                                        $contRight = $nextR && (int)($nextR['id'] ?? 0) === $rid;
                                    }

                                    $cellCls = 'hcal-cell';
                                    if ($esWe)      $cellCls .= ' is-weekend';
                                    if ($esHoyTd)   $cellCls .= ' is-today';
                                    if ($esLunes)   $cellCls .= ' is-week-sep';
                                    if ($contLeft)  $cellCls .= ' is-cont-left';
                                    if ($contRight) $cellCls .= ' is-cont-right';
                                    ?>
                                    <td class="<?= $cellCls ?>">
                                        <?php if ($reserva): ?>
                                            <?php
                                            $eInfo    = hcal_estado_info($reserva['estado'] ?? '');
                                            $nombre   = (string)($reserva['huesped_nombre'] ?? 'Reserva');
                                            $barCls   = 'hcal-res-bar is-' . hcal_safe($eInfo['key']);
                                            if ($contLeft)  $barCls .= ' cont-left';
                                            if ($contRight) $barCls .= ' cont-right';
                                            $ttEntrada = substr((string)($reserva['fecha_entrada'] ?? ''), 0, 10);
                                            $ttSalida  = substr((string)($reserva['fecha_salida']  ?? ''), 0, 10);
                                            $ttNoches  = ($ttEntrada && $ttSalida)
                                                ? max(1, (int)round((strtotime($ttSalida) - strtotime($ttEntrada)) / 86400))
                                                : '?';
                                            ?>
                                            <button type="button"
                                                    class="<?= $barCls ?>"
                                                    onclick="verReservacion(<?= (int)$reserva['id'] ?>)"
                                                    data-tt-nombre="<?= hcal_safe($nombre) ?>"
                                                    data-tt-estado="<?= hcal_safe($eInfo['key']) ?>"
                                                    data-tt-label="<?= hcal_safe($eInfo['label']) ?>"
                                                    data-tt-icon="<?= hcal_safe($eInfo['icon']) ?>"
                                                    data-tt-entrada="<?= hcal_safe($ttEntrada) ?>"
                                                    data-tt-salida="<?= hcal_safe($ttSalida) ?>"
                                                    data-tt-noches="<?= hcal_safe((string)$ttNoches) ?>"
                                                    data-tt-hab="<?= hcal_safe($habNum) ?>"
                                                    data-tt-id="<?= (int)$reserva['id'] ?>">
                                                <?php if (!$contLeft): ?>
                                                    <i class="fas <?= hcal_safe($eInfo['icon']) ?>"></i>
                                                    <span><?= hcal_safe(hcal_primer_nombre($nombre)) ?></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="hcal-avail-btn"
                                                    onclick="crearReservacion('<?= hcal_safe($fTexto) ?>', <?= $habId ?>)"
                                                    title="Libre · crear reserva">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
      </div><!-- /.hcal-calcol -->

        <!-- ── Sidebar ── -->
        <aside class="hcal-side">

            <!-- Ocupación del mes -->
            <div class="hcal-card">
                <div class="hcal-card-head">
                    <div>
                        <h2>Ocupación</h2>
                        <p><?= hcal_safe($mesActualTexto) ?></p>
                    </div>
                </div>
                <div class="hcal-card-body">
                    <?php
                    $r     = 26;
                    $circ  = 2 * M_PI * $r;
                    $dash  = ($circ * min(100, $ocupacion)) / 100;
                    $gap   = $circ - $dash;
                    ?>
                    <div class="hcal-occ-wrap">
                        <div class="hcal-occ-ring">
                            <svg viewBox="0 0 68 68" aria-hidden="true">
                                <circle cx="34" cy="34" r="<?= $r ?>" fill="none" stroke="var(--border-mid)" stroke-width="7"/>
                                <circle cx="34" cy="34" r="<?= $r ?>" fill="none"
                                        stroke="var(--blue)" stroke-width="7"
                                        stroke-linecap="round"
                                        stroke-dasharray="<?= round($dash, 2) ?> <?= round($gap, 2) ?>"/>
                            </svg>
                            <span class="hcal-occ-pct"><?= (int)$ocupacion ?>%</span>
                        </div>
                        <div class="hcal-occ-meta">
                            <strong>Ocupación del mes</strong>
                            <span>Habitaciones con check-in activo.</span>
                        </div>
                    </div>
                    <div class="hcal-mini-row">
                        <div class="hcal-mini">
                            <span>Total reservas</span>
                            <strong><?= (int)$totalReservaciones ?></strong>
                        </div>
                        <div class="hcal-mini">
                            <span>Libres hoy</span>
                            <strong style="color:var(--green)"><?= (int)$disponiblesHoy ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad de hoy -->
            <div class="hcal-card">
                <div class="hcal-card-head">
                    <div>
                        <h2>Hoy</h2>
                        <p><?= date('d') ?> de <?= strtolower($mesesNombres[(int)date('n')] ?? '') ?></p>
                    </div>
                </div>
                <?php if (empty($reservacionesHoy)): ?>
                    <div class="hcal-empty-box">
                        <i class="fas fa-moon"></i>
                        Sin reservaciones hoy.
                    </div>
                <?php else: ?>
                    <div class="hcal-today-list">
                        <?php foreach ($reservacionesHoy as $res): ?>
                            <?php
                            $eInfo  = hcal_estado_info($res['estado'] ?? '');
                            $nombre = (string)($res['huesped_nombre'] ?? 'Reserva');
                            $habN   = $res['habitacion_numero'] ?? '—';
                            $ek     = hcal_safe($eInfo['key']);
                            ?>
                            <a href="<?= url('reservaciones/ver/' . (int)$res['id']) ?>" class="hcal-today-item">
                                <span class="hcal-today-ico is-<?= $ek ?>">
                                    <i class="fas <?= hcal_safe($eInfo['icon']) ?>"></i>
                                </span>
                                <span class="hcal-today-body">
                                    <strong><?= hcal_safe($nombre) ?></strong>
                                    <span>Hab. <?= hcal_safe($habN) ?></span>
                                </span>
                                <span class="hcal-today-pill is-<?= $ek ?>"><?= hcal_safe($eInfo['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Acciones rápidas -->
            <div class="hcal-card">
                <div class="hcal-card-head">
                    <div><h2>Acciones rápidas</h2></div>
                </div>
                <div class="hcal-side-actions">
                    <button type="button" onclick="exportarCalendario()" class="hcal-side-btn">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </button>
                    <button type="button" onclick="imprimirCalendario()" class="hcal-side-btn ms-print-hide-mobile">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <a href="<?= url('reportes/ocupacion') ?>" class="hcal-side-btn">
                        <i class="fas fa-chart-line"></i> Ver reportes
                    </a>
                    <a href="<?= url('reservaciones/crear') ?>" class="hcal-side-btn">
                        <i class="fas fa-plus-circle"></i> Nueva reserva
                    </a>
                </div>
            </div>

        </aside>
    </div>
</div>
</div>

<!-- Tooltip flotante de reservación -->
<div id="hcalTooltip" role="tooltip" aria-hidden="true">
    <div class="hcal-tt">
        <div class="hcal-tt-header">
            <span class="hcal-tt-name" id="hcalTtName"></span>
            <span class="hcal-tt-badge" id="hcalTtBadge"></span>
        </div>
        <div class="hcal-tt-rows">
            <div class="hcal-tt-row">
                <i class="fas fa-bed"></i>
                <span>Habitación <strong id="hcalTtHab"></strong></span>
            </div>
            <div class="hcal-tt-row">
                <i class="fas fa-arrow-right-to-bracket"></i>
                <span>Entrada <strong id="hcalTtEntrada"></strong></span>
            </div>
            <div class="hcal-tt-row">
                <i class="fas fa-arrow-right-from-bracket"></i>
                <span>Salida <strong id="hcalTtSalida"></strong></span>
            </div>
        </div>
        <div class="hcal-tt-divider"></div>
        <div class="hcal-tt-footer">
            <span>Reserva #<strong id="hcalTtId"></strong></span>
            <span class="hcal-tt-nights"><i class="fas fa-moon"></i> <strong id="hcalTtNoches"></strong> noches</span>
        </div>
    </div>
</div>

<script>
// ── Tooltip de reservación ───────────────────────────────────────
(function () {
    const tt      = document.getElementById('hcalTooltip');
    const ttName  = document.getElementById('hcalTtName');
    const ttBadge = document.getElementById('hcalTtBadge');
    const ttHab   = document.getElementById('hcalTtHab');
    const ttEnt   = document.getElementById('hcalTtEntrada');
    const ttSal   = document.getElementById('hcalTtSalida');
    const ttId    = document.getElementById('hcalTtId');
    const ttNoch  = document.getElementById('hcalTtNoches');
    let hideTimer = null;

    function fmtDate(str) {
        if (!str) return '—';
        const [y, m, d] = str.split('-');
        const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        return d + ' ' + (meses[parseInt(m, 10) - 1] || m) + ' ' + y;
    }

    function positionTooltip(x, y) {
        const margin = 12;
        const ttW = tt.offsetWidth  || 280;
        const ttH = tt.offsetHeight || 160;
        const vw  = window.innerWidth;
        const vh  = window.innerHeight;

        let left = x + margin;
        let top  = y - ttH / 2;

        if (left + ttW > vw - margin) left = x - ttW - margin;
        if (top < margin)              top  = margin;
        if (top + ttH > vh - margin)   top  = vh - ttH - margin;

        tt.style.left = left + 'px';
        tt.style.top  = top  + 'px';
    }

    function showTooltip(btn, x, y) {
        const estado  = btn.dataset.ttEstado  || '';
        const label   = btn.dataset.ttLabel   || '';
        const icon    = btn.dataset.ttIcon    || '';

        ttName.textContent  = btn.dataset.ttNombre  || '—';
        ttHab.textContent   = btn.dataset.ttHab     || '—';
        ttEnt.textContent   = fmtDate(btn.dataset.ttEntrada);
        ttSal.textContent   = fmtDate(btn.dataset.ttSalida);
        ttId.textContent    = btn.dataset.ttId      || '—';
        ttNoch.textContent  = btn.dataset.ttNoches  || '?';

        ttBadge.textContent = label;
        ttBadge.className   = 'hcal-tt-badge is-' + estado;
        if (icon) {
            const ico = document.createElement('i');
            ico.className = 'fas ' + icon;
            ttBadge.prepend(ico, ' ');
        }

        tt.style.left = '-9999px';
        tt.style.top  = '-9999px';
        tt.classList.add('is-visible');
        tt.removeAttribute('aria-hidden');

        requestAnimationFrame(() => positionTooltip(x, y));
    }

    function hideTooltip() {
        tt.classList.remove('is-visible');
        tt.setAttribute('aria-hidden', 'true');
    }

    document.addEventListener('mousemove', function (e) {
        if (tt.classList.contains('is-visible')) {
            positionTooltip(e.clientX, e.clientY);
        }
    });

    document.addEventListener('mouseover', function (e) {
        const btn = e.target.closest('[data-tt-nombre]');
        if (!btn) return;
        clearTimeout(hideTimer);
        showTooltip(btn, e.clientX, e.clientY);
    });

    document.addEventListener('mouseout', function (e) {
        const btn = e.target.closest('[data-tt-nombre]');
        if (!btn) return;
        const rel = e.relatedTarget;
        if (rel && rel.closest('[data-tt-nombre]') === btn) return;
        hideTimer = setTimeout(hideTooltip, 80);
    });

    document.addEventListener('click', hideTooltip);
    document.addEventListener('scroll', hideTooltip, true);
})();

// ── Navegación ───────────────────────────────────────────────────
function verReservacion(id) {
    window.location.href = '<?= url('reservaciones/ver/') ?>' + id;
}

function crearReservacion(fecha, habId) {
    window.location.href = '<?= url('reservaciones/crear') ?>?fecha_entrada=' + fecha + '&habitacion_id=' + habId;
}

function irAHoy() {
    const hoy = new Date();
    const p = new URLSearchParams({ mes: String(hoy.getMonth() + 1), 'año': String(hoy.getFullYear()) });
    window.location.href = '<?= url('reservaciones/calendario') ?>?' + p.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    const todayCell = document.querySelector('.hcal-cell.is-today');
    if (todayCell) {
        setTimeout(() => todayCell.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' }), 140);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.target && e.target.matches('input,textarea,select,button')) return;
    if (e.key === 'ArrowLeft')       { e.preventDefault(); document.querySelector('[data-month-nav="prev"]')?.click(); }
    if (e.key === 'ArrowRight')      { e.preventDefault(); document.querySelector('[data-month-nav="next"]')?.click(); }
    if (e.key.toLowerCase() === 'h') { e.preventDefault(); irAHoy(); }
});

// ── Exportar CSV ─────────────────────────────────────────────────
function exportarCalendario() {
    const mesNombre = '<?= hcal_safe($mesesNombres[$mes] ?? '') ?>';
    const anio = '<?= (int)$año ?>';
    const tabla = document.getElementById('hcalTable');
    if (!tabla) return;

    let csv = 'data:text/csv;charset=utf-8,';
    csv += `Calendario de Reservaciones - ${mesNombre} ${anio}\n\n`;
    csv += 'Habitacion';
    for (let d = 1; d <= <?= (int)$diasEnMes ?>; d++) csv += `,${d}`;
    csv += '\n';

    // CSV injection: una celda que inicia con = + - @ o tab se ejecuta como
    // formula al abrir en Excel/Sheets. Nombres de huesped/habitacion son
    // texto capturado por usuarios: se neutralizan con apostrofe.
    const csvCelda = (v) => {
        const texto = String(v || '').replace(/,/g, ';').trim();
        return /^[=+\-@\t]/.test(texto) ? "'" + texto : texto;
    };

    tabla.querySelectorAll('tbody tr').forEach(tr => {
        const badge = tr.querySelector('.hcal-room-badge');
        csv += csvCelda(badge ? badge.textContent : '');
        tr.querySelectorAll('td:not(.hcal-sticky)').forEach(td => {
            const bar = td.querySelector('.hcal-res-bar');
            csv += ',' + (bar ? csvCelda(bar.dataset.ttNombre || 'Reservado') : 'Libre');
        });
        csv += '\n';
    });

    const link = document.createElement('a');
    link.href = encodeURI(csv);
    link.download = `calendario_${mesNombre}_${anio}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function imprimirCalendario() {
    window.print();
}

// ── Vista móvil: agenda interactiva por día ──────────────────────
window.hcalDays = <?= json_encode($hcalDaysData, JSON_UNESCAPED_UNICODE) ?>;
(function () {
    var grid = document.getElementById('hcalMGrid');
    if (!grid) return;

    var elDay  = document.getElementById('hcalMAgDay');
    var elWk   = document.getElementById('hcalMAgWeekday');
    var elSum  = document.getElementById('hcalMAgSummary');
    var elAdd  = document.getElementById('hcalMAgAdd');
    var elList = document.getElementById('hcalMAgList');
    var CREAR  = '<?= url('reservaciones/crear') ?>';
    var VER    = '<?= url('reservaciones/ver/') ?>';
    var CATLBL = { llegada: 'Llegada', salida: 'Salida', estancia: 'En estancia', cancelada: 'Cancelada' };

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    function resumen(info) {
        var p = [];
        if (info.llegadas > 0) p.push(info.llegadas + ' llegada' + (info.llegadas > 1 ? 's' : ''));
        if (info.salidas > 0)  p.push(info.salidas + ' salida' + (info.salidas > 1 ? 's' : ''));
        if (info.ocupadas > 0 && !p.length) p.push(info.ocupadas + ' en estancia');
        return p.length ? p.join(' · ') : 'Sin movimientos';
    }

    function render(day) {
        var info = window.hcalDays[day];
        if (!info) return;

        elDay.textContent = day;
        elWk.textContent  = info.diaSemana;
        elSum.textContent = resumen(info);
        elAdd.href = CREAR + '?fecha_entrada=' + info.fecha;

        if (!info.reservas.length) {
            elList.innerHTML =
                '<div class="hcal-m-empty"><i class="fas fa-moon"></i>' +
                '<p>Sin reservaciones este día.</p>' +
                '<a href="' + CREAR + '?fecha_entrada=' + info.fecha + '" class="hcal-m-empty-btn">' +
                '<i class="fas fa-plus"></i> Crear reserva</a></div>';
            return;
        }

        var html = '';
        info.reservas.forEach(function (r) {
            var tag   = CATLBL[r.cat] || r.label;
            var range = r.noches > 0 ? '<span class="hcal-m-ag-range"><i class="fas fa-moon"></i> ' + r.noches + '</span>' : '';
            html +=
                '<a href="' + VER + r.id + '" class="hcal-m-ag-item is-' + esc(r.key) + '">' +
                    '<span class="hcal-m-ag-ico"><i class="fas ' + esc(r.icon) + '"></i></span>' +
                    '<span class="hcal-m-ag-body">' +
                        '<strong>' + esc(r.nombre) + '</strong>' +
                        '<span class="hcal-m-ag-sub">Hab. ' + esc(r.hab) + (r.tipo ? ' · ' + esc(r.tipo) : '') + '</span>' +
                    '</span>' +
                    '<span class="hcal-m-ag-side">' +
                        '<span class="hcal-m-ag-tag is-' + esc(r.cat) + '">' + esc(tag) + '</span>' + range +
                    '</span>' +
                '</a>';
        });
        elList.innerHTML = html;
    }

    grid.addEventListener('click', function (e) {
        var btn = e.target.closest('.hcal-m-day');
        if (!btn) return;
        grid.querySelectorAll('.hcal-m-day.is-sel').forEach(function (x) { x.classList.remove('is-sel'); });
        btn.classList.add('is-sel');
        render(parseInt(btn.dataset.day, 10));
    });
})();

// ── Escritorio: toggle Mes / Habitaciones + expandir "+N más" ─────
(function () {
    var col = document.getElementById('hcalCalCol');
    if (!col) return;

    var KEY  = 'hcalView';
    var btns = document.querySelectorAll('.hcal-vt-btn');

    function setView(v) {
        col.classList.toggle('show-hab', v === 'hab');
        btns.forEach(function (b) {
            var on = b.dataset.view === v;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        try { localStorage.setItem(KEY, v); } catch (e) {}
    }

    btns.forEach(function (b) {
        b.addEventListener('click', function () { setView(b.dataset.view); });
    });

    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch (e) {}
    if (saved === 'hab') setView('hab');

    // "+N más" expande / colapsa las reservas ocultas de una celda
    col.addEventListener('click', function (e) {
        var more = e.target.closest('.hcal-mv-more');
        if (!more) return;
        e.preventDefault();
        var cell = more.closest('.hcal-mv-cell');
        if (!cell) return;
        var open = cell.classList.toggle('is-expanded');
        var n = cell.querySelectorAll('.hcal-mv-chip.is-extra').length;
        more.innerHTML = open
            ? '<i class="fas fa-minus" style="font-size:.6rem"></i> menos'
            : '<i class="fas fa-plus" style="font-size:.6rem"></i> ' + n + ' más';
    });
})();
</script>
