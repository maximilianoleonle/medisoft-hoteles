<?php
/**
 * Calendario de reservaciones
 * Rediseño visual de la vista, conserva datos, rutas y acciones existentes.
 */

if (!function_exists('cal_safe')) {
    function cal_safe($value, $fallback = '-')
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cal_estado_key')) {
    function cal_estado_key($value)
    {
        $key = strtolower(trim((string)($value ?? '')));
        $key = preg_replace('/[^a-z0-9_-]+/', '-', $key);
        return $key !== '' ? $key : 'sin-estado';
    }
}

if (!function_exists('cal_estado_info')) {
    function cal_estado_info($estado)
    {
        $map = [
            'confirmada' => [
                'label' => 'Confirmada',
                'icon' => 'fa-calendar-check',
                'class' => 'confirmada',
            ],
            'checked_in' => [
                'label' => 'En estancia',
                'icon' => 'fa-sign-in-alt',
                'class' => 'checked-in',
            ],
            'checked_out' => [
                'label' => 'Check-out',
                'icon' => 'fa-sign-out-alt',
                'class' => 'checked-out',
            ],
            'cancelada' => [
                'label' => 'Cancelada',
                'icon' => 'fa-times-circle',
                'class' => 'cancelada',
            ],
        ];

        $key = (string)($estado ?? '');
        return $map[$key] ?? [
            'label' => ucfirst(str_replace('_', ' ', $key ?: 'Reserva')),
            'icon' => 'fa-calendar-day',
            'class' => cal_estado_key($key),
        ];
    }
}

if (!function_exists('cal_mes_url')) {
    function cal_mes_url($mes, $anio)
    {
        return url('reservaciones/calendario?' . http_build_query([
            'mes' => (int)$mes,
            'año' => (int)$anio,
        ]));
    }
}

if (!function_exists('cal_primer_nombre')) {
    function cal_primer_nombre($nombre)
    {
        $partes = preg_split('/\s+/', trim((string)$nombre));
        return $partes && $partes[0] !== '' ? $partes[0] : 'Reserva';
    }
}

$primerDia = mktime(0, 0, 0, $mes, 1, $año);
$diasEnMes = (int)date('t', $primerDia);
$mesAnterior = $mes - 1;
$mesSiguiente = $mes + 1;
$añoAnterior = $año;
$añoSiguiente = $año;

if ($mesAnterior < 1) {
    $mesAnterior = 12;
    $añoAnterior--;
}

if ($mesSiguiente > 12) {
    $mesSiguiente = 1;
    $añoSiguiente++;
}

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$diasSemana = ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'];
$diasSemanaCorto = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];

$totalReservaciones = count($reservaciones);
$confirmadas = 0;
$checkIn = 0;
$checkOut = 0;
$canceladas = 0;

foreach ($reservaciones as $r) {
    switch ($r['estado']) {
        case 'confirmada':
            $confirmadas++;
            break;
        case 'checked_in':
            $checkIn++;
            break;
        case 'checked_out':
            $checkOut++;
            break;
        case 'cancelada':
            $canceladas++;
            break;
    }
}

$diasOcupados = 0;
$totalDiasHabitacion = count($habitaciones) * $diasEnMes;

foreach ($calendario as $dia => $habitacionesReservadas) {
    foreach ($habitacionesReservadas as $habId => $reserva) {
        if (($reserva['estado'] ?? '') === 'checked_in') {
            $diasOcupados++;
        }
    }
}

$ocupacionPromedio = $totalDiasHabitacion > 0 ? round(($diasOcupados / $totalDiasHabitacion) * 100) : 0;

$hoy = (int)date('j');
$reservacionesHoy = [];
if (isset($calendario[$hoy])) {
    foreach ($calendario[$hoy] as $habId => $reserva) {
        $reservacionesHoy[] = $reserva;
    }
}

$hotelNombre = (function_exists('current_hotel_nombre') && current_hotel_nombre())
    ? current_hotel_nombre()
    : ((function_exists('current_hotel_display_name') && current_hotel_display_name()) ? current_hotel_display_name() : 'Hotel');

$disponiblesHoy = max(0, count($habitaciones) - count($reservacionesHoy));
$mesActualTexto = ($meses[$mes] ?? '') . ' ' . $año;
$prevTexto = $meses[$mesAnterior] ?? 'Anterior';
$nextTexto = $meses[$mesSiguiente] ?? 'Siguiente';
?>

<style>
.rescal-page {
    --rc-primary: var(--brand-primary, #1f3f46);
    --rc-secondary: var(--brand-secondary, #27333f);
    --rc-accent: var(--brand-accent, #b58a3c);
    --rc-action: var(--brand-action-bg, var(--rc-primary));
    --rc-on-action: var(--brand-action-text, #fffdf8);
    --rc-bg: #fbfaf6;
    --rc-surface: rgba(255,255,255,.84);
    --rc-surface-strong: #fffdf8;
    --rc-line: color-mix(in srgb, var(--rc-primary) 10%, #e8dfd2);
    --rc-ink: color-mix(in srgb, var(--rc-primary) 54%, #455265);
    --rc-text: #334155;
    --rc-muted: #738094;
    --rc-blue: #4b7f99;
    --rc-sage: #3b8a72;
    --rc-indigo: #6a70a6;
    --rc-coral: #b76c62;
    --rc-amber: #b98a35;
    --rc-soft-blue: color-mix(in srgb, var(--rc-blue) 9%, #fffdf8);
    --rc-soft-sage: color-mix(in srgb, var(--rc-sage) 8%, #fffdf8);
    --rc-soft-amber: color-mix(in srgb, var(--rc-amber) 8%, #fffdf8);
    min-height: 100vh;
    color: var(--rc-text);
    background:
        radial-gradient(circle at 8% 0%, color-mix(in srgb, var(--rc-accent) 12%, transparent), transparent 25rem),
        radial-gradient(circle at 42% 4%, color-mix(in srgb, var(--rc-sage) 8%, transparent), transparent 24rem),
        radial-gradient(circle at 92% 0%, color-mix(in srgb, var(--rc-blue) 8%, transparent), transparent 28rem),
        linear-gradient(180deg, #fdfbf7 0%, color-mix(in srgb, var(--rc-accent) 4%, #f3f0e8) 100%);
    font-family: "Inter", "Segoe UI", system-ui, sans-serif;
}

.rescal-shell {
    width: min(1480px, calc(100% - 32px));
    margin: 0 auto;
    padding: 26px 0 52px;
}

.rescal-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 18px;
    align-items: end;
    margin-bottom: 16px;
}

.rescal-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 30px;
    padding: 0 11px;
    border: 1px solid color-mix(in srgb, var(--rc-accent) 24%, #ded6c8);
    border-radius: 999px;
    background: rgba(255,255,255,.74);
    color: color-mix(in srgb, var(--rc-primary) 58%, #667085);
    font-size: .78rem;
    font-weight: 640;
}

.rescal-kicker i {
    color: color-mix(in srgb, var(--rc-accent) 76%, #795a16);
}

.rescal-title {
    margin: 11px 0 0;
    color: var(--rc-ink);
    font-size: clamp(1.75rem, 3vw, 2.85rem);
    line-height: 1.08;
    font-weight: 540;
    letter-spacing: 0;
    text-wrap: balance;
}

.rescal-subtitle {
    max-width: 760px;
    margin: 10px 0 0;
    color: #526176;
    font-size: .98rem;
    line-height: 1.55;
    font-weight: 430;
}

.rescal-actions,
.rescal-tool-row,
.rescal-month-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    align-items: center;
}

.rescal-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    border: 1px solid var(--rc-line);
    border-radius: 12px;
    background: rgba(255,255,255,.78);
    color: var(--rc-ink);
    font-size: .84rem;
    font-weight: 700;
    line-height: 1;
    text-decoration: none;
    cursor: pointer;
    box-shadow: 0 14px 34px -32px color-mix(in srgb, var(--rc-primary) 42%, transparent);
    transition: transform .18s cubic-bezier(.22, 1, .36, 1), border-color .18s ease, background .18s ease, color .18s ease, box-shadow .18s ease;
}

.rescal-btn:hover,
.rescal-btn:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--rc-accent) 34%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-accent) 7%, #fffdf8);
    color: color-mix(in srgb, var(--rc-primary) 72%, #334155);
    box-shadow: 0 18px 38px -32px color-mix(in srgb, var(--rc-accent) 42%, transparent);
    outline: none;
}

.rescal-btn:active {
    transform: translateY(0) scale(.99);
}

.rescal-btn-primary {
    border-color: color-mix(in srgb, var(--rc-action) 18%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-action) 92%, #243044);
    color: var(--rc-on-action);
}

.rescal-btn-primary:hover,
.rescal-btn-primary:focus-visible {
    background: color-mix(in srgb, var(--rc-action) 82%, #111827);
    color: var(--rc-on-action);
}

.rescal-panel {
    border: 1px solid var(--rc-line);
    border-radius: 18px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--rc-accent) 8%, transparent), transparent 18rem),
        linear-gradient(180deg, rgba(255,255,255,.86), rgba(255,253,248,.78));
    box-shadow: 0 20px 46px -40px color-mix(in srgb, var(--rc-primary) 42%, transparent);
}

.rescal-command {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    margin-bottom: 14px;
    padding: 14px;
}

.rescal-month-card {
    display: grid;
    justify-items: center;
    gap: 5px;
    min-width: 260px;
    padding: 10px 16px;
    border-radius: 14px;
    background: rgba(255,255,255,.72);
}

.rescal-month-card span {
    color: var(--rc-muted);
    font-size: .72rem;
    font-weight: 740;
    letter-spacing: .08em;
    line-height: 1;
    text-transform: uppercase;
}

.rescal-month-card strong {
    color: var(--rc-ink);
    font-size: 1.32rem;
    font-weight: 580;
    line-height: 1.1;
}

.rescal-month-card button {
    border: 0;
    background: transparent;
    color: color-mix(in srgb, var(--rc-blue) 78%, #334155);
    font-size: .78rem;
    font-weight: 720;
    cursor: pointer;
}

.rescal-month-card button:hover,
.rescal-month-card button:focus-visible {
    color: color-mix(in srgb, var(--rc-blue) 92%, #111827);
    outline: none;
}

.rescal-metrics {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 14px;
}

.rescal-metric {
    min-width: 0;
    padding: 14px;
    border: 1px solid color-mix(in srgb, var(--metric-c, var(--rc-accent)) 18%, var(--rc-line));
    border-radius: 14px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--metric-c, var(--rc-accent)) 10%, transparent), transparent 7rem),
        rgba(255,255,255,.78);
    color: var(--rc-text);
}

.rescal-metric span {
    display: block;
    color: var(--rc-muted);
    font-size: .72rem;
    font-weight: 720;
    letter-spacing: .05em;
    line-height: 1.1;
    text-transform: uppercase;
}

.rescal-metric strong {
    display: block;
    margin-top: 8px;
    color: color-mix(in srgb, var(--metric-c, var(--rc-primary)) 52%, var(--rc-ink));
    font-size: 1.65rem;
    line-height: 1;
    font-weight: 580;
    font-variant-numeric: tabular-nums;
}

.rescal-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 330px;
    gap: 14px;
    align-items: start;
}

.rescal-board-shell {
    overflow: hidden;
}

.rescal-board-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 15px 16px;
    border-bottom: 1px solid var(--rc-line);
}

.rescal-board-head h2,
.rescal-side-card h3 {
    margin: 0;
    color: var(--rc-ink);
    font-size: 1rem;
    font-weight: 760;
    letter-spacing: 0;
}

.rescal-board-head p,
.rescal-side-card p {
    margin: 5px 0 0;
    color: var(--rc-muted);
    font-size: .82rem;
    line-height: 1.35;
}

.rescal-legend {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 7px;
}

.rescal-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 28px;
    padding: 0 9px;
    border: 1px solid color-mix(in srgb, var(--state-c) 18%, var(--rc-line));
    border-radius: 999px;
    background: color-mix(in srgb, var(--state-c) 7%, #fffdf8);
    color: color-mix(in srgb, var(--state-c) 70%, #334155);
    font-size: .74rem;
    font-weight: 700;
}

.rescal-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: var(--state-c);
}

.calendar-container {
    max-height: calc(100dvh - 360px);
    min-height: 430px;
    overflow: auto;
    position: relative;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--rc-primary) 18%, #d6cec0) color-mix(in srgb, var(--rc-accent) 5%, #f5f1e9);
}

.calendar-container::-webkit-scrollbar {
    width: 10px;
    height: 10px;
}

.calendar-container::-webkit-scrollbar-track {
    background: color-mix(in srgb, var(--rc-accent) 5%, #f5f1e9);
}

.calendar-container::-webkit-scrollbar-thumb {
    border: 2px solid color-mix(in srgb, var(--rc-accent) 5%, #f5f1e9);
    border-radius: 999px;
    background: color-mix(in srgb, var(--rc-primary) 18%, #d6cec0);
}

.calendar-table {
    width: 100%;
    min-width: <?= max(980, 210 + ($diasEnMes * 52)) ?>px;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed;
    background: color-mix(in srgb, var(--rc-accent) 3%, #fbfaf6);
}

.calendar-table th,
.calendar-table td {
    border: 0;
}

.sticky-column {
    position: sticky !important;
    left: 0;
    z-index: 8;
    width: 210px;
    min-width: 210px;
}

.rescal-room-head {
    top: 0;
    z-index: 12;
    padding: 12px 14px;
    background: color-mix(in srgb, var(--rc-primary) 7%, #fffdf8);
    color: var(--rc-ink);
    text-align: left;
    box-shadow: 8px 0 18px -20px rgba(17,24,39,.45);
}

.rescal-room-head span {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: .75rem;
    font-weight: 820;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.rescal-day-head {
    position: sticky;
    top: 0;
    z-index: 6;
    width: 52px;
    min-width: 52px;
    padding: 9px 4px;
    border-bottom: 1px solid var(--rc-line);
    background: rgba(255,253,248,.96);
    text-align: center;
}

.rescal-day-head.is-weekend {
    background: color-mix(in srgb, var(--rc-amber) 7%, #fffdf8);
}

.rescal-day-head.is-today {
    background: color-mix(in srgb, var(--rc-blue) 11%, #fffdf8);
    box-shadow: inset 0 -2px 0 color-mix(in srgb, var(--rc-blue) 68%, #fffdf8);
}

.rescal-day-num {
    display: block;
    color: var(--rc-ink);
    font-size: .92rem;
    font-weight: 800;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.rescal-day-name {
    display: block;
    margin-top: 4px;
    color: var(--rc-muted);
    font-size: .65rem;
    font-weight: 720;
    line-height: 1;
    text-transform: uppercase;
}

.rescal-day-short {
    display: none;
}

.rescal-room-cell {
    padding: 9px 12px;
    border-bottom: 1px solid color-mix(in srgb, var(--rc-primary) 8%, #eee6d8);
    background: rgba(255,253,248,.98);
    box-shadow: 8px 0 18px -20px rgba(17,24,39,.45);
}

.rescal-room {
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
}

.rescal-room-number {
    display: grid;
    place-items: center;
    width: 42px;
    height: 38px;
    border: 1px solid color-mix(in srgb, var(--rc-accent) 24%, var(--rc-line));
    border-radius: 12px;
    background: color-mix(in srgb, var(--rc-accent) 8%, #fffdf8);
    color: color-mix(in srgb, var(--rc-primary) 72%, #334155);
    font-size: .88rem;
    font-weight: 820;
    font-variant-numeric: tabular-nums;
}

.rescal-room-type {
    min-width: 0;
    color: var(--rc-muted);
    font-size: .76rem;
    font-weight: 620;
    line-height: 1.25;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.calendar-cell {
    height: 48px;
    min-height: 48px;
    padding: 5px;
    border-bottom: 1px solid color-mix(in srgb, var(--rc-primary) 7%, #eee6d8);
    border-left: 1px solid color-mix(in srgb, var(--rc-primary) 5%, #f0e9dd);
    background: rgba(255,253,248,.62);
    vertical-align: middle;
}

.calendar-cell.is-weekend {
    background: color-mix(in srgb, var(--rc-amber) 4%, #fffdf8);
}

.calendar-cell.is-today {
    background: color-mix(in srgb, var(--rc-blue) 7%, #fffdf8);
}

.rescal-row:hover .calendar-cell,
.rescal-row:focus-within .calendar-cell {
    background: color-mix(in srgb, var(--rc-blue) 4%, #fffdf8);
}

.reservation-block,
.available-cell {
    width: 100%;
    height: 38px;
    border: 1px solid transparent;
    border-radius: 11px;
    font-family: inherit;
    line-height: 1;
    cursor: pointer;
    transition: transform .16s cubic-bezier(.22, 1, .36, 1), border-color .16s ease, background .16s ease, box-shadow .16s ease, color .16s ease;
}

.reservation-block {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 6px;
    align-items: center;
    justify-content: center;
    padding: 0 8px;
    background: color-mix(in srgb, var(--state-c) 12%, #fffdf8);
    border-color: color-mix(in srgb, var(--state-c) 23%, var(--rc-line));
    color: color-mix(in srgb, var(--state-c) 72%, #263241);
    font-size: .72rem;
    font-weight: 780;
    overflow: hidden;
}

.reservation-block i {
    font-size: .72rem;
}

.reservation-block span {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.reservation-block:hover,
.reservation-block:focus-visible {
    transform: translateY(-1px);
    background: color-mix(in srgb, var(--state-c) 18%, #fffdf8);
    border-color: color-mix(in srgb, var(--state-c) 38%, var(--rc-line));
    box-shadow: 0 14px 22px -20px color-mix(in srgb, var(--state-c) 58%, transparent);
    outline: none;
}

.reservation-block:active,
.available-cell:active {
    transform: translateY(0) scale(.98);
}

.available-cell {
    display: grid;
    place-items: center;
    background: transparent;
    color: color-mix(in srgb, var(--rc-sage) 30%, #c8c2b6);
}

.available-cell span {
    display: grid;
    place-items: center;
    width: 22px;
    height: 22px;
    border: 1px solid color-mix(in srgb, var(--rc-sage) 14%, var(--rc-line));
    border-radius: 999px;
    background: rgba(255,255,255,.62);
    opacity: .42;
    transition: transform .16s ease, opacity .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.available-cell:hover,
.available-cell:focus-visible {
    background: color-mix(in srgb, var(--rc-sage) 8%, #fffdf8);
    border-color: color-mix(in srgb, var(--rc-sage) 22%, var(--rc-line));
    color: color-mix(in srgb, var(--rc-sage) 80%, #334155);
    outline: none;
}

.available-cell:hover span,
.available-cell:focus-visible span {
    transform: rotate(90deg);
    opacity: 1;
    border-color: color-mix(in srgb, var(--rc-sage) 28%, var(--rc-line));
    background: color-mix(in srgb, var(--rc-sage) 9%, #fffdf8);
}

.rescal-state-confirmada { --state-c: var(--rc-blue); }
.rescal-state-checked-in { --state-c: var(--rc-sage); }
.rescal-state-checked-out { --state-c: var(--rc-indigo); }
.rescal-state-cancelada {
    --state-c: var(--rc-coral);
    opacity: .74;
}

.rescal-side {
    display: grid;
    gap: 12px;
}

.rescal-side-card {
    padding: 15px;
}

.rescal-progress {
    height: 9px;
    margin: 12px 0 14px;
    overflow: hidden;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rc-primary) 8%, #eee7db);
}

.rescal-progress span {
    display: block;
    width: var(--progress, 0%);
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(90deg, color-mix(in srgb, var(--rc-blue) 76%, #fffdf8), color-mix(in srgb, var(--rc-sage) 78%, #fffdf8));
}

.rescal-mini-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.rescal-mini {
    padding: 10px;
    border: 1px solid color-mix(in srgb, var(--mini-c, var(--rc-accent)) 16%, var(--rc-line));
    border-radius: 12px;
    background: color-mix(in srgb, var(--mini-c, var(--rc-accent)) 6%, #fffdf8);
}

.rescal-mini span {
    display: block;
    color: var(--rc-muted);
    font-size: .7rem;
    font-weight: 720;
}

.rescal-mini strong {
    display: block;
    margin-top: 5px;
    color: color-mix(in srgb, var(--mini-c, var(--rc-primary)) 62%, var(--rc-ink));
    font-size: 1.12rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}

.rescal-today-list {
    display: grid;
    gap: 8px;
    margin-top: 12px;
}

.rescal-today-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 10px;
    align-items: center;
    padding: 10px;
    border: 1px solid color-mix(in srgb, var(--state-c, var(--rc-blue)) 15%, var(--rc-line));
    border-radius: 12px;
    background: color-mix(in srgb, var(--state-c, var(--rc-blue)) 5%, #fffdf8);
    color: var(--rc-text);
    text-decoration: none;
    transition: transform .16s ease, border-color .16s ease, background .16s ease;
}

.rescal-today-item:hover,
.rescal-today-item:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--state-c, var(--rc-blue)) 28%, var(--rc-line));
    background: color-mix(in srgb, var(--state-c, var(--rc-blue)) 8%, #fffdf8);
    outline: none;
}

.rescal-today-main {
    min-width: 0;
}

.rescal-today-main strong {
    display: block;
    min-width: 0;
    color: var(--rc-ink);
    font-size: .86rem;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.rescal-today-room {
    display: block;
    margin-top: 4px;
    color: var(--rc-muted);
    font-size: .74rem;
    font-weight: 620;
}

.rescal-state-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 26px;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--state-c, var(--rc-blue)) 10%, #fffdf8);
    color: color-mix(in srgb, var(--state-c, var(--rc-blue)) 76%, #334155);
    font-size: .7rem;
    font-weight: 760;
    white-space: nowrap;
}

.rescal-empty {
    margin: 12px 0 0;
    padding: 18px;
    border: 1px dashed color-mix(in srgb, var(--rc-primary) 14%, #d8d0c3);
    border-radius: 14px;
    background: rgba(255,255,255,.54);
    color: var(--rc-muted);
    text-align: center;
    font-size: .84rem;
}

.print-title {
    display: none;
}

.rescal-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 60;
    max-width: min(360px, calc(100vw - 32px));
    padding: 13px 16px;
    border: 1px solid color-mix(in srgb, var(--toast-c, var(--rc-blue)) 22%, var(--rc-line));
    border-radius: 13px;
    background: color-mix(in srgb, var(--toast-c, var(--rc-blue)) 9%, #fffdf8);
    color: color-mix(in srgb, var(--toast-c, var(--rc-blue)) 70%, #334155);
    box-shadow: 0 22px 40px -32px rgba(17,24,39,.48);
    font-size: .88rem;
    font-weight: 720;
    transform: translateX(420px);
    transition: transform .24s cubic-bezier(.22, 1, .36, 1), opacity .24s ease;
}

.rescal-toast.is-visible {
    transform: translateX(0);
}

@media (max-width: 1180px) {
    .rescal-metrics {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .rescal-layout {
        grid-template-columns: 1fr;
    }

    .rescal-side {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}

@media (max-width: 860px) {
    .rescal-shell {
        width: min(100% - 22px, 1480px);
        padding-top: 18px;
    }

    .rescal-hero,
    .rescal-command,
    .rescal-board-head {
        grid-template-columns: 1fr;
    }

    .rescal-actions,
    .rescal-tool-row,
    .rescal-month-nav,
    .rescal-legend {
        justify-content: stretch;
    }

    .rescal-btn,
    .rescal-actions .rescal-btn {
        flex: 1 1 140px;
    }

    .rescal-month-card {
        min-width: 0;
    }

    .rescal-metrics,
    .rescal-side {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .calendar-container {
        min-height: 380px;
        max-height: 62dvh;
    }
}

@media (max-width: 560px) {
    .rescal-metrics,
    .rescal-side,
    .rescal-mini-grid {
        grid-template-columns: 1fr;
    }

    .sticky-column {
        width: 132px;
        min-width: 132px;
    }

    .rescal-room {
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 7px;
    }

    .rescal-room-number {
        width: 34px;
        height: 32px;
        font-size: .78rem;
    }

    .rescal-room-type {
        font-size: .68rem;
    }

    .calendar-table {
        min-width: <?= max(760, 132 + ($diasEnMes * 42)) ?>px;
    }

    .rescal-day-head {
        width: 42px;
        min-width: 42px;
    }

    .rescal-day-long {
        display: none;
    }

    .rescal-day-short {
        display: inline;
    }

    .calendar-cell {
        height: 44px;
        padding: 4px;
    }

    .reservation-block,
    .available-cell {
        height: 34px;
    }

    .reservation-block span {
        display: none;
    }
}

@media (prefers-reduced-motion: reduce) {
    .rescal-page *,
    .rescal-page *::before,
    .rescal-page *::after {
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
        scroll-behavior: auto !important;
    }
}

@media print {
    .rescal-page {
        background: #fffdf8 !important;
        color: #111827 !important;
    }

    .rescal-hero,
    .rescal-command,
    .rescal-side,
    .rescal-board-head,
    .rescal-metrics,
    .rescal-toast {
        display: none !important;
    }

    .print-title {
        display: block !important;
        margin: 0 0 14px;
        text-align: center;
        color: #111827;
    }

    .rescal-shell {
        width: 100%;
        padding: 0;
    }

    .rescal-panel,
    .rescal-board-shell {
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
    }

    .calendar-container {
        max-height: none !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    .calendar-table {
        min-width: 0 !important;
        width: 100% !important;
        font-size: 8pt;
        border: 1px solid #111827;
    }

    .sticky-column,
    .rescal-day-head {
        position: static !important;
    }

    .calendar-table th,
    .calendar-table td {
        border: 1px solid #d1d5db !important;
        padding: 2px !important;
    }

    .calendar-cell {
        height: 24px !important;
        min-height: 24px !important;
    }

    .reservation-block {
        height: 18px !important;
        min-height: 18px !important;
        padding: 0 2px !important;
        font-size: 6.5pt !important;
    }

    .available-cell {
        height: 18px !important;
    }

    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    @page {
        size: landscape;
        margin: 1cm;
    }

    tr {
        page-break-inside: avoid;
    }
}
</style>

<div class="rescal-page">
    <div class="rescal-shell">
        <section class="rescal-hero">
            <div>
                <span class="rescal-kicker">
                    <i class="fas fa-calendar-check"></i>
                    Agenda operativa
                </span>
                <h1 class="rescal-title">Calendario de reservaciones</h1>
                <p class="rescal-subtitle">
                    Consulta ocupacion por habitacion, detecta huecos disponibles y abre reservas sin cambiar de pantalla.
                </p>
            </div>

            <div class="rescal-actions">
                <a href="<?= url('reservaciones') ?>" class="rescal-btn">
                    <i class="fas fa-list"></i>
                    Vista lista
                </a>
                <a href="<?= url('reservaciones/crear') ?>" class="rescal-btn rescal-btn-primary">
                    <i class="fas fa-plus"></i>
                    Nueva reserva
                </a>
            </div>
        </section>

        <section class="rescal-panel rescal-command" aria-label="Controles del calendario">
            <div class="rescal-month-nav">
                <a href="<?= cal_mes_url($mesAnterior, $añoAnterior) ?>" class="rescal-btn" data-month-nav="prev">
                    <i class="fas fa-chevron-left"></i>
                    <?= cal_safe($prevTexto) ?>
                </a>
            </div>

            <div class="rescal-month-card">
                <span>Periodo consultado</span>
                <strong><?= cal_safe($mesActualTexto) ?></strong>
                <button type="button" onclick="irAHoy()">
                    <i class="fas fa-calendar-day"></i>
                    Ir a hoy
                </button>
            </div>

            <div class="rescal-tool-row">
                <a href="<?= cal_mes_url($mesSiguiente, $añoSiguiente) ?>" class="rescal-btn" data-month-nav="next">
                    <?= cal_safe($nextTexto) ?>
                    <i class="fas fa-chevron-right"></i>
                </a>
                <button type="button" onclick="exportarCalendario()" class="rescal-btn">
                    <i class="fas fa-download"></i>
                    CSV
                </button>
                <button type="button" onclick="imprimirCalendario()" class="rescal-btn">
                    <i class="fas fa-print"></i>
                    Imprimir
                </button>
            </div>
        </section>

        <section class="rescal-metrics" aria-label="Resumen del mes">
            <div class="rescal-metric" style="--metric-c: var(--rc-blue)">
                <span>Ocupacion</span>
                <strong><?= (int)$ocupacionPromedio ?>%</strong>
            </div>
            <div class="rescal-metric" style="--metric-c: var(--rc-accent)">
                <span>Reservas</span>
                <strong><?= (int)$totalReservaciones ?></strong>
            </div>
            <div class="rescal-metric" style="--metric-c: var(--rc-blue)">
                <span>Confirmadas</span>
                <strong><?= (int)$confirmadas ?></strong>
            </div>
            <div class="rescal-metric" style="--metric-c: var(--rc-sage)">
                <span>En estancia</span>
                <strong><?= (int)$checkIn ?></strong>
            </div>
            <div class="rescal-metric" style="--metric-c: var(--rc-indigo)">
                <span>Check-out</span>
                <strong><?= (int)$checkOut ?></strong>
            </div>
            <div class="rescal-metric" style="--metric-c: var(--rc-coral)">
                <span>Canceladas</span>
                <strong><?= (int)$canceladas ?></strong>
            </div>
        </section>

        <div class="rescal-layout">
            <section class="rescal-panel rescal-board-shell" aria-label="Matriz mensual de reservaciones">
                <div class="rescal-board-head">
                    <div>
                        <h2>Habitaciones por dia</h2>
                        <p><?= count($habitaciones) ?> habitaciones, <?= (int)$diasEnMes ?> dias visibles.</p>
                    </div>

                    <div class="rescal-legend" aria-label="Leyenda de estados">
                        <span class="rescal-legend-item" style="--state-c: var(--rc-blue)">
                            <span class="rescal-dot"></span>
                            Confirmada
                        </span>
                        <span class="rescal-legend-item" style="--state-c: var(--rc-sage)">
                            <span class="rescal-dot"></span>
                            En estancia
                        </span>
                        <span class="rescal-legend-item" style="--state-c: var(--rc-indigo)">
                            <span class="rescal-dot"></span>
                            Check-out
                        </span>
                        <span class="rescal-legend-item" style="--state-c: var(--rc-coral)">
                            <span class="rescal-dot"></span>
                            Cancelada
                        </span>
                    </div>
                </div>

                <div class="calendar-container">
                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th class="sticky-column rescal-room-head">
                                    <span>
                                        <i class="fas fa-bed"></i>
                                        Habitacion
                                    </span>
                                </th>
                                <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                    <?php
                                    $fecha = mktime(0, 0, 0, $mes, $dia, $año);
                                    $diaSemanaActual = (int)date('w', $fecha);
                                    $esFinDeSemana = ($diaSemanaActual === 0 || $diaSemanaActual === 6);
                                    $esHoy = (date('Y-m-d', $fecha) === date('Y-m-d'));
                                    ?>
                                    <th class="rescal-day-head calendar-day <?= $esHoy ? 'is-today' : '' ?> <?= $esFinDeSemana ? 'is-weekend' : '' ?>">
                                        <span class="rescal-day-num"><?= (int)$dia ?></span>
                                        <span class="rescal-day-name">
                                            <span class="rescal-day-long"><?= cal_safe($diasSemana[$diaSemanaActual]) ?></span>
                                            <span class="rescal-day-short"><?= cal_safe($diasSemanaCorto[$diaSemanaActual]) ?></span>
                                        </span>
                                    </th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($habitaciones as $habitacion): ?>
                                <?php
                                $habitacionId = (int)($habitacion['id'] ?? 0);
                                $numeroHabitacion = (string)($habitacion['numero'] ?? '');
                                $tipoHabitacion = ucfirst(str_replace('_', ' ', (string)($habitacion['tipo'] ?? 'Habitacion')));
                                ?>
                                <tr class="rescal-row">
                                    <td class="sticky-column rescal-room-cell" data-room-number="<?= cal_safe($numeroHabitacion) ?>" data-room-type="<?= cal_safe($tipoHabitacion) ?>">
                                        <div class="rescal-room">
                                            <span class="rescal-room-number"><?= cal_safe($numeroHabitacion) ?></span>
                                            <span class="rescal-room-type"><?= cal_safe($tipoHabitacion) ?></span>
                                        </div>
                                    </td>

                                    <?php for ($dia = 1; $dia <= $diasEnMes; $dia++): ?>
                                        <?php
                                        $fecha = mktime(0, 0, 0, $mes, $dia, $año);
                                        $fechaTexto = date('Y-m-d', $fecha);
                                        $diaSemanaActual = (int)date('w', $fecha);
                                        $esFinDeSemana = ($diaSemanaActual === 0 || $diaSemanaActual === 6);
                                        $esHoy = (date('Y-m-d', $fecha) === date('Y-m-d'));
                                        $reserva = $calendario[$dia][$habitacionId] ?? null;
                                        ?>
                                        <td class="calendar-cell <?= $esHoy ? 'is-today' : '' ?> <?= $esFinDeSemana ? 'is-weekend' : '' ?>">
                                            <?php if ($reserva): ?>
                                                <?php
                                                $estadoInfo = cal_estado_info($reserva['estado'] ?? '');
                                                $huespedNombre = (string)($reserva['huesped_nombre'] ?? 'Reservacion');
                                                ?>
                                                <button type="button"
                                                        class="reservation-block rescal-state-<?= cal_safe($estadoInfo['class']) ?>"
                                                        onclick="verReservacion(<?= (int)$reserva['id'] ?>)"
                                                        data-toggle="tooltip"
                                                        data-guest="<?= cal_safe($huespedNombre) ?>"
                                                        data-state="<?= cal_safe($estadoInfo['label']) ?>"
                                                        title="<?= cal_safe($huespedNombre) ?> - <?= cal_safe($estadoInfo['label']) ?>">
                                                    <i class="fas <?= cal_safe($estadoInfo['icon']) ?>"></i>
                                                    <span><?= cal_safe(cal_primer_nombre($huespedNombre)) ?></span>
                                                </button>
                                            <?php else: ?>
                                                <button type="button"
                                                        class="available-cell"
                                                        onclick="crearReservacion('<?= cal_safe($fechaTexto) ?>', <?= $habitacionId ?>)"
                                                        title="Disponible, crear reserva">
                                                    <span><i class="fas fa-plus"></i></span>
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

            <aside class="rescal-side" aria-label="Informacion del calendario">
                <section class="rescal-panel rescal-side-card">
                    <h3>Resumen del mes</h3>
                    <p>Ocupacion calculada sobre habitaciones con check-in.</p>
                    <div class="rescal-progress" aria-label="Ocupacion promedio">
                        <span style="--progress: <?= (int)$ocupacionPromedio ?>%"></span>
                    </div>
                    <div class="rescal-mini-grid">
                        <div class="rescal-mini" style="--mini-c: var(--rc-accent)">
                            <span>Total</span>
                            <strong><?= (int)$totalReservaciones ?></strong>
                        </div>
                        <div class="rescal-mini" style="--mini-c: var(--rc-sage)">
                            <span>Hoy libres</span>
                            <strong><?= (int)$disponiblesHoy ?></strong>
                        </div>
                    </div>
                </section>

                <section class="rescal-panel rescal-side-card">
                    <h3>Reservaciones de hoy</h3>
                    <p>Accesos rapidos a las reservas del dia.</p>

                    <?php if (empty($reservacionesHoy)): ?>
                        <div class="rescal-empty">No hay reservaciones para hoy.</div>
                    <?php else: ?>
                        <div class="rescal-today-list">
                            <?php foreach ($reservacionesHoy as $reserva): ?>
                                <?php
                                $estadoInfo = cal_estado_info($reserva['estado'] ?? '');
                                $huespedNombre = (string)($reserva['huesped_nombre'] ?? 'Reservacion');
                                $habitacionNumero = $reserva['habitacion_numero'] ?? 'N/A';
                                ?>
                                <a href="<?= url('reservaciones/ver/' . (int)$reserva['id']) ?>" class="rescal-today-item rescal-state-<?= cal_safe($estadoInfo['class']) ?>">
                                    <span class="rescal-today-main">
                                        <strong><?= cal_safe($huespedNombre) ?></strong>
                                        <span class="rescal-today-room">Hab. <?= cal_safe($habitacionNumero) ?></span>
                                    </span>
                                    <span class="rescal-state-badge">
                                        <i class="fas <?= cal_safe($estadoInfo['icon']) ?>"></i>
                                        <?= cal_safe($estadoInfo['label']) ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="rescal-panel rescal-side-card">
                    <h3>Acciones</h3>
                    <p>Exporta o revisa reportes sin perder el periodo.</p>
                    <div class="rescal-today-list">
                        <button type="button" onclick="exportarCalendario()" class="rescal-btn">
                            <i class="fas fa-download"></i>
                            Exportar calendario
                        </button>
                        <button type="button" onclick="imprimirCalendario()" class="rescal-btn">
                            <i class="fas fa-print"></i>
                            Imprimir
                        </button>
                        <a href="<?= url('reportes/ocupacion') ?>" class="rescal-btn">
                            <i class="fas fa-chart-line"></i>
                            Ver reportes
                        </a>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</div>

<script>
function verReservacion(id) {
    window.location.href = '<?= url('reservaciones/ver/') ?>' + id;
}

function crearReservacion(fecha, habitacionId) {
    window.location.href = '<?= url('reservaciones/crear') ?>?fecha_entrada=' + fecha + '&habitacion_id=' + habitacionId;
}

function irAHoy() {
    const hoy = new Date();
    const params = new URLSearchParams({
        mes: String(hoy.getMonth() + 1),
        'año': String(hoy.getFullYear())
    });
    window.location.href = '<?= url('reservaciones/calendario') ?>?' + params.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    const hoyElement = document.querySelector('.calendar-cell.is-today');
    if (hoyElement && window.innerWidth > 768) {
        setTimeout(function() {
            hoyElement.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }, 120);
    }
});

document.addEventListener('keydown', function(e) {
    if (e.target && e.target.matches('input, textarea, select, button')) {
        return;
    }

    if (e.key === 'ArrowLeft') {
        e.preventDefault();
        const prev = document.querySelector('[data-month-nav="prev"]');
        if (prev) prev.click();
    }

    if (e.key === 'ArrowRight') {
        e.preventDefault();
        const next = document.querySelector('[data-month-nav="next"]');
        if (next) next.click();
    }

    if (e.key.toLowerCase() === 'h') {
        e.preventDefault();
        irAHoy();
    }
});

function exportarCalendario() {
    const mesNombre = '<?= cal_safe($meses[$mes] ?? '') ?>';
    const anio = '<?= (int)$año ?>';
    const tabla = document.querySelector('.calendar-table');

    if (!tabla) {
        mostrarNotificacion('No se encontro el calendario para exportar.', 'error');
        return;
    }

    let csvContent = 'data:text/csv;charset=utf-8,';
    csvContent += `Calendario de Reservaciones - ${mesNombre} ${anio}\n\n`;
    csvContent += 'Habitacion,Tipo';

    for (let dia = 1; dia <= <?= (int)$diasEnMes ?>; dia++) {
        csvContent += `,${dia}`;
    }
    csvContent += '\n';

    const filas = tabla.querySelectorAll('tbody tr');
    filas.forEach(function(fila) {
        const habitacion = fila.querySelector('.rescal-room-cell');
        const numeroHab = habitacion ? (habitacion.dataset.roomNumber || '').trim() : '';
        const tipoHab = habitacion ? (habitacion.dataset.roomType || '').trim() : '';

        csvContent += `${numeroHab},${tipoHab}`;

        const celdas = fila.querySelectorAll('td:not(.sticky-column)');
        celdas.forEach(function(celda) {
            const reservacion = celda.querySelector('.reservation-block');
            if (reservacion) {
                const huesped = (reservacion.dataset.guest || 'Reservado').replace(/,/g, ';');
                const estado = reservacion.dataset.state || '';
                csvContent += `,${huesped} (${estado})`;
            } else {
                csvContent += ',Disponible';
            }
        });

        csvContent += '\n';
    });

    csvContent += '\n\nResumen del Mes\n';
    csvContent += `Total Reservaciones,<?= (int)$totalReservaciones ?>\n`;
    csvContent += `Confirmadas,<?= (int)$confirmadas ?>\n`;
    csvContent += `Check-in,<?= (int)$checkIn ?>\n`;
    csvContent += `Check-out,<?= (int)$checkOut ?>\n`;
    csvContent += `Canceladas,<?= (int)$canceladas ?>\n`;
    csvContent += `Ocupacion Promedio,<?= (int)$ocupacionPromedio ?>%\n`;

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `calendario_${mesNombre}_${anio}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    mostrarNotificacion('Calendario exportado correctamente.', 'success');
}

function imprimirCalendario() {
    const calendario = document.querySelector('.calendar-container');
    if (!calendario) {
        window.print();
        return;
    }

    const titulo = document.createElement('div');
    titulo.className = 'print-title';
    titulo.innerHTML = `
        <h1 style="text-align: center; margin-bottom: 8px; font-size: 18pt;">
            Calendario de Reservaciones - <?= cal_safe($mesActualTexto) ?>
        </h1>
        <p style="text-align: center; margin: 0 0 16px; font-size: 10pt;">
            <?= cal_safe($hotelNombre) ?>
        </p>
    `;

    calendario.parentNode.insertBefore(titulo, calendario);
    window.print();

    setTimeout(function() {
        titulo.remove();
    }, 1000);
}

function mostrarNotificacion(mensaje, tipo = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[tipo](mensaje);
        return;
    }

    const notificacion = document.createElement('div');
    notificacion.className = 'rescal-toast';
    notificacion.textContent = mensaje;

    const colores = {
        success: 'var(--rc-sage)',
        error: 'var(--rc-coral)',
        warning: 'var(--rc-amber)',
        info: 'var(--rc-blue)'
    };

    notificacion.style.setProperty('--toast-c', colores[tipo] || colores.info);
    document.body.appendChild(notificacion);

    requestAnimationFrame(function() {
        notificacion.classList.add('is-visible');
    });

    setTimeout(function() {
        notificacion.classList.remove('is-visible');
        setTimeout(function() {
            if (notificacion.parentNode) {
                notificacion.parentNode.removeChild(notificacion);
            }
        }, 260);
    }, 3000);
}
</script>
