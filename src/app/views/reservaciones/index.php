<?php
/**
 * Vista de Reservaciones
 * UI operativa brand-aware para recepcion hotelera.
 */

$estadisticas        = $estadisticas        ?? [];
$entradas_hoy        = $entradas_hoy        ?? [];
$salidas_hoy         = $salidas_hoy         ?? [];
$reservaciones       = $reservaciones       ?? [];
$total_reservaciones = $total_reservaciones ?? count($reservaciones);
$estados             = $estados             ?? [];
$buscar              = $buscar              ?? '';
$fecha_filtro        = $fecha_filtro        ?? date('Y-m-d');

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
$color_numerico = ['bg' => 'var(--brand-primary, #1B2746)', 'dark' => 'var(--brand-secondary, #0F172A)'];

if (!function_exists('obtenerColorHab')) {
    function obtenerColorHab($numero, $colores, $default) {
        $n = trim(strtoupper((string) $numero));
        if ($n === '' || is_numeric($n)) {
            return $default;
        }
        foreach ($colores as $nombre => $color) {
            if ($n === $nombre || strpos($n, $nombre) !== false) {
                return $color;
            }
        }
        return $default;
    }
}

if (!function_exists('reserva_lower')) {
    function reserva_lower($texto) {
        $texto = (string) $texto;
        return function_exists('mb_strtolower') ? mb_strtolower($texto, 'UTF-8') : strtolower($texto);
    }
}

if (!function_exists('reserva_upper')) {
    function reserva_upper($texto) {
        $texto = (string) $texto;
        return function_exists('mb_strtoupper') ? mb_strtoupper($texto, 'UTF-8') : strtoupper($texto);
    }
}

if (!function_exists('reserva_title')) {
    function reserva_title($texto) {
        $texto = trim(str_replace('_', ' ', (string) $texto));
        return function_exists('mb_convert_case') ? mb_convert_case(reserva_lower($texto), MB_CASE_TITLE, 'UTF-8') : ucwords(strtolower($texto));
    }
}

if (!function_exists('reserva_iniciales')) {
    function reserva_iniciales($nombre) {
        $nombre = trim((string) $nombre);
        if ($nombre === '') {
            return 'SR';
        }

        $partes = preg_split('/\s+/', $nombre);
        $primera = $partes[0] ?? '';
        $segunda = $partes[1] ?? '';
        $ini_1 = function_exists('mb_substr') ? mb_substr($primera, 0, 1, 'UTF-8') : substr($primera, 0, 1);
        $ini_2 = function_exists('mb_substr') ? mb_substr($segunda, 0, 1, 'UTF-8') : substr($segunda, 0, 1);
        return reserva_upper($ini_1 . ($ini_2 ?: ''));
    }
}

if (!function_exists('reserva_noches')) {
    function reserva_noches($entrada, $salida) {
        $inicio = strtotime((string) $entrada);
        $fin = strtotime((string) $salida);
        if (!$inicio || !$fin || $fin <= $inicio) {
            return 1;
        }
        return max(1, (int) round(($fin - $inicio) / 86400));
    }
}

if (!function_exists('reserva_estado_ui')) {
    function reserva_estado_ui($estado, $estados) {
        $base = $estados[$estado] ?? ['label' => ucfirst(str_replace('_', ' ', (string) $estado)), 'icon' => 'circle'];
        $map = [
            'confirmada'  => ['label' => 'Confirmada',  'class' => 'state-confirmada',  'icon' => 'calendar-check'],
            'checked_in'  => ['label' => 'Hospedado',   'class' => 'state-checked-in',  'icon' => 'bed'],
            'checked_out' => ['label' => 'Completada',  'class' => 'state-checked-out', 'icon' => 'check-circle'],
            'cancelada'   => ['label' => 'Cancelada',   'class' => 'state-cancelada',   'icon' => 'times-circle'],
        ];

        return $map[$estado] ?? [
            'label' => $base['label'] ?? 'Desconocido',
            'class' => 'state-neutral',
            'icon'  => $base['icon'] ?? 'circle',
        ];
    }
}

$fecha_hoy = date('Y-m-d');
$es_hoy = ($fecha_filtro === $fecha_hoy);
$meses = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$dias_semana = ['Domingo','Lunes','Martes','Miercoles','Jueves','Viernes','Sabado'];
$ts = strtotime($fecha_filtro) ?: time();
$fecha_bonita = $dias_semana[(int) date('w', $ts)] . ' ' . date('d', $ts) . ' de ' . $meses[(int) date('n', $ts) - 1] . ', ' . date('Y', $ts);

$estado_counts = [
    'confirmada'  => 0,
    'checked_in'  => 0,
    'checked_out' => 0,
    'cancelada'   => 0,
];

foreach ($reservaciones as $resumen_reserva) {
    $estado_reserva = $resumen_reserva['estado'] ?? 'confirmada';
    if (!isset($estado_counts[$estado_reserva])) {
        $estado_counts[$estado_reserva] = 0;
    }
    $estado_counts[$estado_reserva]++;
}

$entradas_count = is_countable($entradas_hoy) ? count($entradas_hoy) : (int) ($estadisticas['entradas_hoy'] ?? 0);
$salidas_count = is_countable($salidas_hoy) ? count($salidas_hoy) : (int) ($estadisticas['salidas_hoy'] ?? 0);
$total_listado = count($reservaciones);
$total_visible = $total_reservaciones ?: $total_listado;
$hotel_nombre_reservas = function_exists('current_hotel_display_name') ? (string) current_hotel_display_name() : 'Hotel';
?>

<style>
.res-bookings {
    --res-brand: var(--brand-primary, #1B2746);
    --res-brand-2: var(--brand-secondary, #0F172A);
    --res-accent: var(--brand-accent, #BD9441);
    --res-bg: color-mix(in srgb, var(--res-brand) 2%, #F7F2EA);
    --res-card: rgba(255,255,255,.94);
    --res-line: color-mix(in srgb, var(--res-brand) 8%, #E7DDD1);
    --res-soft: color-mix(in srgb, var(--res-brand) 4%, #FFFFFF);
    --res-text: #171717;
    --res-heading: #111827;
    --res-muted: #7D879A;
    --res-radius: 14px;
    min-height: 100vh;
    background:
        radial-gradient(circle at top right, color-mix(in srgb, var(--res-accent) 11%, transparent), transparent 28rem),
        linear-gradient(180deg, var(--res-bg), #FBFAF7 56%, #F7F2EA);
    color: var(--res-text);
}
.res-shell { max-width: 1680px; margin: 0 auto; padding: 28px 22px 38px; }
.res-topbar { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: start; margin-bottom: 20px; }
.res-title { margin: 0; color: var(--res-heading); font-size: clamp(2rem, 3vw, 2.8rem); font-family: Georgia, "Times New Roman", serif; font-weight: 700; letter-spacing: 0; line-height: 1; }
.res-subtitle { margin-top: 8px; color: #718096; font-size: .95rem; }
.res-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 10px; }
.res-search { position: relative; min-width: 340px; flex: 1 1 340px; }
.res-search i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #8792A6; pointer-events: none; }
.res-search input {
    width: 100%; height: 44px; box-sizing: border-box; border-radius: 11px; border: 1px solid var(--res-line);
    background: rgba(255,255,255,.92); padding: 0 15px 0 42px; outline: none;
    color: var(--res-text); box-shadow: 0 8px 20px rgba(15,23,42,.04); transition: border-color .18s ease, box-shadow .18s ease;
}
.res-search input:focus { border-color: color-mix(in srgb, var(--res-accent) 50%, var(--res-line)); box-shadow: 0 0 0 4px color-mix(in srgb, var(--res-accent) 16%, transparent); }
.res-btn {
    min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    border: 1px solid var(--res-line); border-radius: 11px; padding: 0 16px;
    background: rgba(255,255,255,.9); color: var(--res-heading); font-weight: 800; font-size: .86rem;
    text-decoration: none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease;
}
.res-btn:hover { transform: translateY(-1px); box-shadow: 0 10px 24px rgba(15,23,42,.08); border-color: color-mix(in srgb, var(--res-accent) 36%, var(--res-line)); }
.res-btn-primary { background: linear-gradient(135deg, var(--res-accent), color-mix(in srgb, var(--res-accent), #6B4B16 28%)); border-color: transparent; color: #fff; box-shadow: 0 14px 32px color-mix(in srgb, var(--res-accent) 22%, transparent); }
.res-btn-danger { color: #B42318; }
.res-btn-success { color: #067647; }
.res-metrics { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 14px; margin-bottom: 16px; }
.res-metric {
    min-height: 116px; border: 1px solid var(--res-line); border-radius: var(--res-radius); background: var(--res-card);
    padding: 18px; box-shadow: 0 14px 34px rgba(15,23,42,.06); position: relative; overflow: hidden;
}
.res-metric::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 3px; background: transparent; }
.res-metric.is-primary::after { background: linear-gradient(90deg, var(--res-accent), color-mix(in srgb, var(--res-heading) 18%, #D8C6A3)); }
.res-metric-icon { width: 36px; height: 36px; border-radius: 10px; display: grid; place-items: center; margin-bottom: 14px; }
.metric-arrivals { background: #EEF2FF; color: #635BFF; }
.metric-departures { background: #FEF3E7; color: #C65E38; }
.metric-confirmed { background: #FEF7E6; color: #BD7A12; }
.metric-house { background: #E7F7EF; color: #169A5A; }
.metric-cancelled { background: #FEECEC; color: #D94444; }
.res-metric strong { display: block; color: var(--res-heading); font-size: 1.9rem; line-height: 1; margin-bottom: 7px; }
.res-metric span { display: block; color: #69758D; font-size: .78rem; font-weight: 800; }
.res-filterbar {
    display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; align-items: center;
    border: 1px solid var(--res-line); border-radius: var(--res-radius); background: rgba(255,255,255,.94);
    padding: 12px 14px; margin-bottom: 16px; box-shadow: 0 12px 30px rgba(15,23,42,.05);
}
.res-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
.res-tab {
    border: 1px solid var(--res-line); background: rgba(255,255,255,.86); color: #40506A; border-radius: 999px;
    padding: 9px 13px; font-weight: 850; font-size: .82rem; display: inline-flex; align-items: center; gap: 7px;
}
.res-tab-dot { width: 8px; height: 8px; border-radius: 999px; background: currentColor; }
.res-tab.is-active { background: var(--res-heading); border-color: var(--res-heading); color: #fff; box-shadow: 0 12px 26px color-mix(in srgb, var(--res-heading) 14%, transparent); }
.res-date-tools { display: flex; align-items: center; justify-content: flex-end; gap: 8px; }
.res-date-tools input { height: 39px; border-radius: 10px; border: 1px solid var(--res-line); padding: 0 11px; color: var(--res-heading); background: #fff; font-weight: 700; }
.res-date-chip { height: 39px; display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; border: 1px solid var(--res-line); padding: 0 12px; background: #fff; color: #40506A; font-weight: 800; font-size: .82rem; }
.res-tab,
.res-date-chip,
.res-btn,
.res-icon-btn,
.res-row-command,
.res-action-pill,
.btn-modal-cancel,
.btn-modal-confirm,
.res-export-close,
.res-checkin-close,
.res-ci-cancel,
.res-ci-confirm:not(:disabled) { cursor: pointer; }
.res-btn:focus-visible,
.res-tab:focus-visible,
.res-date-chip:focus-visible,
.res-icon-btn:focus-visible,
.res-row-command:focus-visible,
.res-action-pill:focus-visible,
.res-export-close:focus-visible,
.res-checkin-close:focus-visible,
.btn-modal-cancel:focus-visible,
.btn-modal-confirm:focus-visible,
.res-ci-cancel:focus-visible,
.res-ci-confirm:focus-visible,
.res-detail-link:focus-visible,
.res-phone-link:focus-visible {
    outline: 2px solid color-mix(in srgb, var(--res-accent) 74%, #fff);
    outline-offset: 3px;
}
.res-btn:active,
.res-tab:active,
.res-date-chip:active,
.res-icon-btn:active,
.res-row-command:active,
.res-action-pill:active,
.btn-modal-cancel:active,
.btn-modal-confirm:active,
.res-ci-cancel:active,
.res-ci-confirm:not(:disabled):active { transform: translateY(0) scale(.99); }
.res-table-shell { border: 1px solid var(--res-line); border-radius: 18px; overflow: hidden; overflow-x: auto; scrollbar-width: thin; background: rgba(255,255,255,.95); box-shadow: 0 16px 34px rgba(15,23,42,.06); }
.res-table { width: 100%; border-collapse: separate; border-spacing: 0; min-width: 1020px; }
.res-table thead th { padding: 16px 18px; text-align: left; border-bottom: 1px solid var(--res-line); color: #8A96AA; font-size: .72rem; letter-spacing: .06em; text-transform: uppercase; background: rgba(253,251,247,.82); }
.res-table tbody td { padding: 14px 18px; border-bottom: 1px solid color-mix(in srgb, var(--res-line) 74%, transparent); vertical-align: middle; }
.res-table tbody tr { transition: background .18s ease, box-shadow .18s ease; }
.res-table tbody tr:hover { background: color-mix(in srgb, var(--res-accent) 5%, #FFFFFF); box-shadow: inset 3px 0 0 color-mix(in srgb, var(--res-accent) 46%, transparent); }
.res-folio { color: #758198; font-weight: 850; font-size: .87rem; white-space: nowrap; }
.res-detail-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    width: fit-content;
    color: inherit;
    text-decoration: none;
    border-radius: 8px;
    transition: color .18s ease, background .18s ease, text-decoration-color .18s ease;
}
.res-detail-link i { color: color-mix(in srgb, var(--res-accent) 58%, #A0AABC); font-size: .68rem; opacity: .62; transition: opacity .18s ease, transform .18s ease; }
.res-detail-link:hover,
.res-detail-link:focus-visible { color: var(--res-heading); text-decoration: underline; text-decoration-color: var(--res-accent); text-underline-offset: 3px; }
.res-detail-link:hover i,
.res-detail-link:focus-visible i,
.res-table tbody tr:hover .res-detail-link i { opacity: 1; transform: translateX(2px); }
.res-phone-link {
    color: inherit;
    text-decoration: none;
    border-radius: 7px;
}
.res-phone-link:hover,
.res-phone-link:focus-visible {
    color: var(--res-heading);
    text-decoration: underline;
    text-decoration-color: var(--res-accent);
    text-underline-offset: 3px;
}
.res-guest { display: flex; align-items: center; gap: 12px; min-width: 220px; }
.res-avatar {
    width: 40px; height: 40px; border-radius: 10px; display: grid; place-items: center;
    background: var(--res-avatar-bg, #EEF2FF) !important;
    color: var(--res-avatar-fg, #3730A3);
    border: 1px solid var(--res-avatar-border, #C7D2FE);
    font-size: .78rem; font-weight: 900;
    box-shadow: 0 10px 20px -16px var(--res-avatar-shadow, rgba(55,48,163,.34));
}
.res-table tbody tr:nth-child(6n+1) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+1) .res-avatar {
    --res-avatar-bg: #EEF2FF;
    --res-avatar-fg: #3730A3;
    --res-avatar-border: #C7D2FE;
    --res-avatar-shadow: rgba(55,48,163,.34);
}
.res-table tbody tr:nth-child(6n+2) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+2) .res-avatar {
    --res-avatar-bg: #ECFDF5;
    --res-avatar-fg: #047857;
    --res-avatar-border: #A7F3D0;
    --res-avatar-shadow: rgba(4,120,87,.3);
}
.res-table tbody tr:nth-child(6n+3) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+3) .res-avatar {
    --res-avatar-bg: #FFF7ED;
    --res-avatar-fg: #C2410C;
    --res-avatar-border: #FED7AA;
    --res-avatar-shadow: rgba(194,65,12,.3);
}
.res-table tbody tr:nth-child(6n+4) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+4) .res-avatar {
    --res-avatar-bg: #FDF2F8;
    --res-avatar-fg: #BE185D;
    --res-avatar-border: #FBCFE8;
    --res-avatar-shadow: rgba(190,24,93,.28);
}
.res-table tbody tr:nth-child(6n+5) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+5) .res-avatar {
    --res-avatar-bg: #F0FDFA;
    --res-avatar-fg: #0F766E;
    --res-avatar-border: #99F6E4;
    --res-avatar-shadow: rgba(15,118,110,.28);
}
.res-table tbody tr:nth-child(6n+6) .res-avatar,
.res-mobile-list .res-mobile-card:nth-child(6n+6) .res-avatar {
    --res-avatar-bg: #F8FAFC;
    --res-avatar-fg: #475569;
    --res-avatar-border: #CBD5E1;
    --res-avatar-shadow: rgba(71,85,105,.25);
}
.res-guest-name { color: var(--res-heading); font-weight: 900; line-height: 1.15; }
.res-guest-meta { color: #8B96A9; font-size: .78rem; margin-top: 3px; }
.res-room { display: flex; gap: 8px; align-items: flex-start; }
.res-room-mark { width: 6px; min-width: 6px; height: 24px; border-radius: 10px; margin-top: 2px; }
.res-room-main { font-weight: 900; color: var(--res-heading); }
.res-room-type { color: #8B96A9; font-size: .78rem; margin-top: 3px; }
.res-date-main { color: var(--res-heading); font-weight: 850; white-space: nowrap; }
.res-date-sub { color: #8B96A9; font-size: .78rem; margin-top: 3px; }
.res-nights { min-width: 28px; display: inline-flex; justify-content: center; border-radius: 8px; border: 1px solid var(--res-line); background: #FFFDF9; color: #6D778C; font-weight: 900; padding: 3px 8px; }
.res-state { display: inline-flex; align-items: center; gap: 7px; border-radius: 999px; padding: 7px 11px; font-weight: 900; font-size: .78rem; white-space: nowrap; }
.state-confirmada { background: #E8F6EF; color: #148653; }
.state-checked-in { background: #FDEDE4; color: #C25A36; }
.state-checked-out { background: #EEF2F7; color: #61708A; }
.state-cancelada { background: #FEECEC; color: #D94444; }
.state-neutral { background: #F3F4F6; color: #475467; }
.res-total { color: var(--res-heading); font-weight: 950; text-align: right; white-space: nowrap; }
.res-payment { font-size: .76rem; margin-top: 2px; text-align: right; }
.res-payment.is-paid { color: #067647; }
.res-payment.is-due { color: #D92D20; }
.res-row-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; }
.res-icon-btn {
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--res-line);
    display: inline-flex; align-items: center; justify-content: center; color: #65728A; background: #fff;
    transition: color .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}
.res-icon-btn:hover { color: var(--res-heading); border-color: color-mix(in srgb, var(--res-accent) 42%, var(--res-line)); background: color-mix(in srgb, var(--res-accent) 8%, #fff); box-shadow: 0 10px 20px -16px color-mix(in srgb, var(--res-accent) 70%, transparent); transform: translateY(-1px); }
.res-row-command { height: 34px; border-radius: 10px; border: 0; padding: 0 12px; color: #fff; font-weight: 900; font-size: .78rem; display: inline-flex; align-items: center; gap: 6px; transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
.res-row-command.is-checkin { background: #148653; }
.res-row-command.is-checkout { background: #D97706; }
.res-row-command:hover { transform: translateY(-1px); filter: brightness(1.03); box-shadow: 0 10px 20px -14px rgba(15,23,42,.36); }
.res-mobile-list { display: none; }
.res-mobile-card {
    border: 1px solid var(--res-line); border-radius: 16px; background: rgba(255,255,255,.96);
    padding: 14px; box-shadow: 0 12px 26px rgba(15,23,42,.06);
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.res-mobile-card:hover { border-color: color-mix(in srgb, var(--res-accent) 36%, var(--res-line)); box-shadow: 0 18px 34px rgba(15,23,42,.09); transform: translateY(-1px); }
.res-mobile-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.res-mobile-guest { display: flex; align-items: center; gap: 11px; min-width: 0; }
.res-mobile-room { display: flex; align-items: center; gap: 8px; color: var(--res-heading); font-weight: 900; margin: 10px 0; }
.res-mobile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; margin-top: 12px; }
.res-mobile-info { border-radius: 12px; background: color-mix(in srgb, var(--res-accent) 4%, #FFFFFF); border: 1px solid color-mix(in srgb, var(--res-line) 70%, transparent); padding: 10px; }
.res-mobile-info span { display: block; color: #7A8498; font-size: .7rem; font-weight: 850; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
.res-mobile-info strong { display: block; color: var(--res-heading); font-size: .9rem; }
.res-mobile-actions { display: flex; gap: 8px; margin-top: 12px; }
.res-mobile-actions > * { flex: 1; }
.res-action-pill { min-height: 38px; border-radius: 11px; display: inline-flex; justify-content: center; align-items: center; gap: 7px; border: 1px solid var(--res-line); background: #fff; color: var(--res-heading); font-weight: 900; font-size: .82rem; text-decoration: none; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background .18s ease; }
.res-action-pill:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--res-accent) 36%, var(--res-line)); box-shadow: 0 10px 20px -16px rgba(15,23,42,.38); }
.res-action-primary { background: var(--res-brand); border-color: var(--res-brand); color: #fff; }
.res-action-warning { background: #D97706; border-color: #D97706; color: #fff; }
.res-empty, .res-filter-empty { border: 1px dashed var(--res-line); border-radius: 18px; background: rgba(255,255,255,.9); padding: 42px 18px; text-align: center; color: var(--res-muted); }
.res-filter-empty { display: none; margin-top: 14px; }
.res-filter-empty.is-visible { display: block; }
.reservation-item.hidden-search { display: none !important; }
.modal-hd { background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2)); padding: 16px 20px; }
.lc-form-input { width: 100%; border: 1px solid var(--res-line); border-radius: 11px; padding: 10px 12px; outline: none; color: var(--res-brand-2); background: #fff; }
.lc-form-input:focus { border-color: var(--res-brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--res-brand) 12%, transparent); }
.btn-modal-cancel, .btn-modal-confirm { flex: 1; border-radius: 11px; padding: 11px 14px; font-weight: 900; }
.btn-modal-cancel { background: #F2F4F7; color: #475467; }
.btn-modal-confirm { background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2)); color: #fff; }

/* Los modales se renderizan FUERA de .res-bookings, así que sus tokens --res-*
   no resolvían y los degradados/bordes se perdían. Re-exponer la marca aquí. */
#modalExportarPDF, #modalExportarExcel, #modalCheckIn {
    --res-brand: var(--brand-primary, #1B2746);
    --res-brand-2: var(--brand-secondary, #0F172A);
    --res-line: color-mix(in srgb, var(--brand-primary, #1B2746) 14%, #E7DDD1);
}

/* ── Modales de exportación (rediseño premium, brand-aware) ── */
#modalExportarPDF, #modalExportarExcel { background: color-mix(in srgb, var(--res-brand-2) 60%, rgba(10,14,24,.5)); -webkit-backdrop-filter: blur(7px); backdrop-filter: blur(7px); }
.res-export-card { position: relative; width: 100%; max-width: 30rem; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 32px 80px -24px rgba(8,12,22,.6), 0 0 0 1px rgba(255,255,255,.5) inset; animation: resExportPop .28s cubic-bezier(.22,1,.36,1); }
@keyframes resExportPop { from { opacity: 0; transform: translateY(16px) scale(.97); } to { opacity: 1; transform: none; } }
.res-export-close { position: absolute; top: 14px; right: 14px; width: 30px; height: 30px; border-radius: 9px; display: inline-grid; place-items: center; color: #fff; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.26); cursor: pointer; transition: .16s; z-index: 3; font-size: .82rem; }
.res-export-close:hover { background: rgba(255,255,255,.32); }
.res-export-hd { display: flex; align-items: center; gap: 13px; padding: 20px 22px; color: #fff; background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2)); }
.res-export-hd--excel { background: linear-gradient(135deg, #16A34A, #15803D); }
.res-export-ic { width: 46px; height: 46px; border-radius: 13px; display: inline-grid; place-items: center; font-size: 1.3rem; background: rgba(255,255,255,.17); border: 1px solid rgba(255,255,255,.26); flex: none; }
.res-export-title { margin: 0; font-size: 1.12rem; font-weight: 800; line-height: 1.1; }
.res-export-sub { margin: 3px 0 0; font-size: .76rem; color: rgba(255,255,255,.74); font-weight: 600; }
.res-export-body { padding: 22px; }
.res-export-label { display: block; font-size: .7rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: var(--res-brand-2); margin-bottom: 8px; }
.res-export-body .lc-form-input { height: 48px; padding: 12px 14px; font-weight: 700; font-size: .95rem; border-radius: 12px; }
.res-export-help { margin: 10px 2px 0; font-size: .75rem; color: #667085; display: flex; align-items: center; gap: 7px; line-height: 1.4; }
.res-export-help i { color: color-mix(in srgb, var(--res-brand) 50%, #98A2B3); }
.res-export-actions { display: flex; gap: 10px; margin-top: 22px; }
.res-export-actions .btn-modal-cancel { flex: 1; border: 1px solid var(--res-line); background: #fff; color: var(--res-brand-2); border-radius: 12px; padding: 12px; font-weight: 800; transition: .16s; }
.res-export-actions .btn-modal-cancel:hover { background: color-mix(in srgb, var(--res-brand) 5%, #fff); border-color: color-mix(in srgb, var(--res-brand) 28%, var(--res-line)); }
.res-export-actions .btn-modal-confirm { flex: 1.5; border-radius: 12px; padding: 12px; font-weight: 800; color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2)); box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--res-brand) 60%, transparent); transition: .16s; border: none; cursor: pointer; }
.res-export-actions .btn-modal-confirm:hover { transform: translateY(-1px); filter: brightness(1.05); }
.res-export-actions .btn-modal-confirm--excel { background: linear-gradient(135deg, #16A34A, #15803D); box-shadow: 0 12px 26px -10px rgba(21,128,61,.55); }

/* Check-in modal: arrival desk redesign, same operational flow. */
.res-checkin-modal {
    position: fixed;
    inset: 0;
    z-index: 13000;
    align-items: center;
    justify-content: center;
    padding: clamp(12px, 2.5vw, 28px);
    background:
        radial-gradient(circle at 18% 18%, color-mix(in srgb, var(--res-brand) 22%, transparent), transparent 34%),
        linear-gradient(135deg, rgba(8, 13, 24, .82), rgba(26, 32, 45, .74));
    -webkit-backdrop-filter: blur(10px) saturate(1.1);
    backdrop-filter: blur(10px) saturate(1.1);
}
.res-checkin-modal.hidden { display: none !important; }
.res-checkin-modal:not(.hidden) { display: flex; }
.res-checkin-card {
    width: min(920px, 100%);
    max-height: min(91dvh, 860px);
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(230px, .82fr) minmax(0, 1.75fr);
    border-radius: 28px;
    background: #F8F4EC;
    border: 1px solid rgba(255,255,255,.54);
    box-shadow: 0 36px 90px -36px rgba(7, 10, 18, .78), 0 0 0 1px rgba(255,255,255,.28) inset;
    animation: resCheckInPop .32s cubic-bezier(.22, 1, .36, 1);
}
@keyframes resCheckInPop { from { opacity: 0; transform: translateY(22px) scale(.985); } to { opacity: 1; transform: none; } }
.res-checkin-head {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 24px;
    min-height: 100%;
    padding: 26px;
    color: #fff;
    position: relative;
    isolation: isolate;
    background:
        linear-gradient(155deg, color-mix(in srgb, var(--res-brand-2) 86%, #111827), color-mix(in srgb, var(--res-brand) 74%, #1F2937)),
        var(--res-brand-2);
}
.res-checkin-head::before {
    content: '';
    position: absolute;
    inset: 14px;
    z-index: -1;
    border: 1px solid rgba(255,255,255,.14);
    border-radius: 22px;
}
.res-checkin-head::after {
    content: '';
    position: absolute;
    right: -42px;
    bottom: -46px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--res-accent, #BD9441) 32%, transparent);
    filter: blur(4px);
    opacity: .65;
}
.res-checkin-title {
    display: grid;
    gap: 14px;
    min-width: 0;
    padding-top: 28px;
}
.res-checkin-icon {
    width: 52px;
    height: 52px;
    border-radius: 18px;
    display: inline-grid;
    place-items: center;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.18);
    flex: none;
}
.res-checkin-icon i { font-size: 1.15rem; }
.res-checkin-title h3 {
    margin: 0;
    max-width: 9ch;
    font-size: clamp(1.55rem, 3.8vw, 2.3rem);
    line-height: .96;
    font-weight: 950;
    text-wrap: balance;
}
.res-checkin-title p {
    margin: 0;
    max-width: 23ch;
    font-size: .84rem;
    color: rgba(255,255,255,.72);
    font-weight: 700;
    line-height: 1.45;
}
.res-checkin-close {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 38px;
    height: 38px;
    border: 1px solid rgba(255,255,255,.24);
    border-radius: 14px;
    color: #fff;
    background: rgba(255,255,255,.13);
    display: inline-grid;
    place-items: center;
    cursor: pointer;
    transition: .16s ease;
}
.res-checkin-close:hover { background: rgba(255,255,255,.25); transform: translateY(-1px); }
.res-checkin-form {
    min-height: 0;
    max-height: min(91dvh, 860px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.res-checkin-body {
    padding: 20px;
    display: grid;
    gap: 14px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: color-mix(in srgb, var(--res-brand) 28%, #D7DCE3) transparent;
}
.res-checkin-summary {
    display: grid;
    grid-template-columns: minmax(175px, .86fr) 1fr;
    gap: 12px;
}
.res-ci-total,
.res-ci-time,
.res-ci-section,
.res-ci-summary {
    border: 1px solid color-mix(in srgb, var(--res-brand) 10%, #E7DDD1);
    border-radius: 20px;
    background: rgba(255,255,255,.92);
    box-shadow: 0 1px 0 rgba(255,255,255,.76) inset;
}
.res-ci-total {
    padding: 18px;
    background:
        linear-gradient(145deg, #fff, color-mix(in srgb, var(--res-accent, #BD9441) 9%, #fff));
    position: relative;
    overflow: hidden;
}
.res-ci-total::after {
    content: '';
    position: absolute;
    right: 14px;
    top: 14px;
    width: 28px;
    height: 3px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--res-accent, #BD9441) 70%, #fff);
}
.res-ci-kicker {
    display: block;
    color: #667085;
    font-size: .72rem;
    font-weight: 850;
    letter-spacing: .055em;
    text-transform: uppercase;
}
.res-ci-amount {
    margin-top: 4px;
    color: var(--res-brand-2);
    font-size: clamp(1.55rem, 4vw, 2.2rem);
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.res-ci-time { padding: 16px; display: grid; align-content: center; gap: 8px; }
.res-ci-label {
    display: block;
    color: var(--res-brand);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.res-ci-input {
    width: 100%;
    min-height: 42px;
    border: 1px solid color-mix(in srgb, var(--res-brand) 16%, #DFE4EA);
    border-radius: 14px;
    padding: 10px 12px;
    background: #fff;
    color: var(--res-brand-2);
    font-weight: 750;
    outline: none;
    transition: .16s ease;
}
.res-ci-input:focus { border-color: var(--res-brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--res-brand) 13%, transparent); }
.res-ci-section { padding: 16px; }
.res-ci-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 10px;
}
.res-ci-section-head h4 { margin: 0; color: var(--res-brand-2); font-size: .95rem; font-weight: 900; display: flex; align-items: center; gap: 8px; }
.res-ci-section-head span { color: #7A8498; font-size: .72rem; font-weight: 750; }
.res-ci-methods { display: grid; gap: 10px; }
.res-pay-method {
    --res-pay-color: var(--res-brand);
    border: 1px solid color-mix(in srgb, var(--res-pay-color) 16%, #E5E7EB);
    border-radius: 17px;
    background: #fff;
    overflow: hidden;
    position: relative;
    transition: border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}
.res-pay-method::before {
    content: '';
    position: absolute;
    inset: 0 auto 0 0;
    width: 4px;
    background: color-mix(in srgb, var(--res-pay-color) 76%, #fff);
    opacity: .65;
}
.res-pay-method.is-open {
    border-color: color-mix(in srgb, var(--res-pay-color) 46%, #D7DCE3);
    background: color-mix(in srgb, var(--res-pay-color) 4%, #fff);
    box-shadow: 0 18px 36px -28px color-mix(in srgb, var(--res-pay-color) 70%, transparent);
    transform: translateY(-1px);
}
.res-pay-cash { --res-pay-color: #148653; }
.res-pay-card { --res-pay-color: #2563EB; }
.res-pay-transfer { --res-pay-color: #7C3AED; }
.res-pay-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 13px 14px 13px 17px;
    cursor: pointer;
    color: var(--res-brand-2);
    font-weight: 900;
    position: relative;
}
.res-pay-toggle input { width: 18px; height: 18px; accent-color: var(--res-pay-color); flex: none; }
.res-pay-toggle i { color: var(--res-pay-color); }
.res-pay-panel {
    border-top: 1px solid color-mix(in srgb, var(--res-pay-color) 18%, #E5E7EB);
    padding: 13px 14px 15px 17px;
    background: color-mix(in srgb, var(--res-pay-color) 6%, #fff);
}
.res-ci-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.res-change-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 9px;
    padding: 9px 10px;
    border-radius: 14px;
    background: rgba(255,255,255,.78);
    color: #475467;
    font-size: .82rem;
    font-weight: 800;
}
.res-change-row strong { color: #148653; font-variant-numeric: tabular-nums; }
.res-change-row strong.is-negative { color: #DC2626; }
.res-card-type { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
.res-radio-chip,
.res-invoice-choice {
    border: 1px solid color-mix(in srgb, var(--res-brand) 13%, #DCE2EA);
    border-radius: 15px;
    background: #fff;
    cursor: pointer;
    transition: .16s ease;
}
.res-radio-chip { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; color: #1E40AF; font-size: .82rem; font-weight: 850; }
.res-radio-chip input,
.res-invoice-choice input { accent-color: var(--res-brand); }
.res-radio-chip:has(input:checked) { border-color: #2563EB; background: #EFF6FF; box-shadow: 0 10px 24px -20px rgba(37,99,235,.75); }
.res-invoice-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
.res-invoice-choice { display: flex; align-items: flex-start; gap: 10px; padding: 13px; }
.res-invoice-choice strong { display: block; color: var(--res-brand-2); font-size: .88rem; }
.res-invoice-choice span { display: block; color: #667085; font-size: .72rem; line-height: 1.35; margin-top: 2px; }
#label_factura_si.is-selected { border-color: color-mix(in srgb, #2563EB 62%, #DCE2EA); background: #EFF6FF; box-shadow: 0 12px 24px -22px rgba(37,99,235,.8); }
#label_factura_no.is-selected { border-color: color-mix(in srgb, var(--res-brand) 38%, #DCE2EA); background: color-mix(in srgb, var(--res-brand) 5%, #fff); }
.res-ci-note,
.res-ci-alert {
    margin-top: 9px;
    border-radius: 12px;
    padding: 9px 10px;
    font-size: .78rem;
    font-weight: 750;
    line-height: 1.35;
}
.res-ci-note { border: 1px solid #BFDBFE; background: #EFF6FF; color: #1E40AF; }
.res-ci-alert { border: 1px solid #FECACA; background: #FEF2F2; color: #B42318; }
.res-ci-alert.is-warning { border-color: #FDE68A; background: #FFFBEB; color: #92400E; }
.res-ci-alert.is-info { border-color: #BFDBFE; background: #EFF6FF; color: #1D4ED8; }
.res-ci-alert.is-success { border-color: #BBF7D0; background: #F0FDF4; color: #047857; }
.res-ci-summary { padding: 15px 16px; background: linear-gradient(135deg, #fff, #F6F2EA); }
.res-ci-summary h5 { margin: 0 0 8px; color: var(--res-brand-2); font-size: .82rem; font-weight: 950; }
.res-ci-row { display: flex; align-items: center; justify-content: space-between; gap: 10px; color: #667085; font-size: .82rem; padding: 4px 0; }
.res-ci-row strong { color: var(--res-brand-2); font-variant-numeric: tabular-nums; }
.res-ci-row.is-paid strong { color: #148653; }
.res-ci-row.is-due strong { color: #DC2626; }
.res-ci-row.is-change strong { color: #2563EB; }
.res-checkin-actions {
    position: sticky;
    bottom: 0;
    display: flex;
    gap: 10px;
    padding: 15px 20px 20px;
    border-top: 1px solid color-mix(in srgb, var(--res-brand) 10%, #E7DDD1);
    background: color-mix(in srgb, #F8F4EC 90%, transparent);
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}
.res-ci-cancel,
.res-ci-confirm {
    min-height: 44px;
    border-radius: 13px;
    padding: 0 16px;
    font-weight: 900;
    cursor: pointer;
    transition: .16s ease;
}
.res-ci-cancel { flex: .9; border: 1px solid color-mix(in srgb, var(--res-brand) 13%, #DCE2EA); color: var(--res-brand-2); background: #fff; }
.res-ci-confirm { flex: 1.3; border: 0; color: #fff; background: linear-gradient(135deg, #148653, #0F6F49); box-shadow: 0 15px 30px -18px rgba(20,134,83,.9); display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
.res-ci-confirm:disabled { opacity: .58; cursor: not-allowed; box-shadow: none; }
.res-ci-cancel:hover,
.res-ci-confirm:not(:disabled):hover { transform: translateY(-1px); }
@media (max-width: 860px) {
    .res-checkin-card { grid-template-columns: 1fr; }
    .res-checkin-head {
        min-height: auto;
        gap: 12px;
        padding: 18px 64px 18px 18px;
    }
    .res-checkin-head::before,
    .res-checkin-head::after { display: none; }
    .res-checkin-title {
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: center;
        gap: 12px;
        padding-top: 0;
    }
    .res-checkin-icon { width: 44px; height: 44px; border-radius: 15px; }
    .res-checkin-title h3 { max-width: none; font-size: 1.08rem; line-height: 1.15; }
    .res-checkin-title p { max-width: none; font-size: .76rem; grid-column: 2; }
}
@media (max-width: 1180px) {
    .res-metrics { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .res-topbar { grid-template-columns: 1fr; }
    .res-actions { justify-content: flex-start; }
    .res-search { min-width: min(100%, 520px); }
}
@media (max-width: 1023px) {
    .res-table-shell { display: none; }
    .res-mobile-list { display: grid; gap: 12px; }
}
@media (max-width: 720px) {
    .res-shell { padding: 18px 12px 28px; }
    .res-title { font-size: 1.75rem; }
    .res-actions { display: grid; grid-template-columns: 1fr 1fr; width: 100%; }
    .res-search { grid-column: 1 / -1; min-width: 0; }
    .res-btn-primary { grid-column: span 2; }
    .res-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
    .res-metric { min-height: 96px; padding: 13px; }
    .res-filterbar { grid-template-columns: 1fr; padding: 10px; }
    .res-date-tools { justify-content: flex-start; overflow-x: auto; padding-bottom: 1px; }
    .res-mobile-grid { grid-template-columns: 1fr 1fr; }
    .res-mobile-actions { flex-direction: column; }
    .res-checkin-card { border-radius: 20px; max-height: 94dvh; }
    .res-checkin-summary,
    .res-ci-grid,
    .res-invoice-options { grid-template-columns: 1fr; }
    .res-checkin-head { padding: 16px 62px 16px 16px; }
    .res-checkin-body { padding: 14px; }
    .res-checkin-actions { padding: 12px 14px 14px; }
}
@media (max-width: 420px) {
    .res-tabs { display: grid; grid-template-columns: 1fr 1fr; }
    .res-tab { justify-content: center; }
    .res-mobile-grid { grid-template-columns: 1fr; }
    .res-checkin-modal { padding: 8px; align-items: flex-end; }
    .res-checkin-card { width: 100%; border-radius: 20px 20px 0 0; max-height: 96dvh; }
    .res-checkin-title h3 { font-size: 1rem; }
    .res-checkin-title p { font-size: .72rem; }
    .res-card-type { grid-template-columns: 1fr; }
    .res-checkin-actions { flex-direction: column; }
    .res-ci-cancel,
    .res-ci-confirm { width: 100%; }
}
</style>

<div class="res-bookings">
    <div class="res-shell">
        <header class="res-topbar">
            <div>
                <h1 class="res-title">Reservaciones</h1>
                <p class="res-subtitle">
                    <?= htmlspecialchars($hotel_nombre_reservas) ?> · <?= (int) $total_visible ?> reservas activas · <?= htmlspecialchars($fecha_bonita) ?>
                </p>
            </div>

            <div class="res-actions no-print">
                <label class="res-search" for="buscarReservacion">
                    <i class="fas fa-search"></i>
                    <input type="text" id="buscarReservacion" class="search-input" value="<?= htmlspecialchars($buscar) ?>" placeholder="Buscar folio, huesped o habitacion" autocomplete="off">
                </label>
                <a href="<?= url('reservaciones/calendario') ?>" class="res-btn" title="Abrir calendario de reservaciones">
                    <i class="fas fa-calendar-alt"></i>
                    Calendario
                </a>
                <button type="button" onclick="abrirModalExportarPDF()" class="res-btn res-btn-danger" title="Exportar reservaciones a PDF">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </button>
                <button type="button" onclick="abrirModalExportarExcel()" class="res-btn res-btn-success" title="Exportar reservaciones a Excel">
                    <i class="fas fa-file-excel"></i>
                    Excel
                </button>
                <a href="<?= url('reservaciones/crear') ?>" class="res-btn res-btn-primary" title="Crear una nueva reservación">
                    <i class="fas fa-plus"></i>
                    Nueva reservacion
                </a>
            </div>
        </header>

        <section class="res-metrics" aria-label="Resumen de reservaciones">
            <article class="res-metric is-primary">
                <div class="res-metric-icon metric-arrivals"><i class="fas fa-sign-in-alt"></i></div>
                <strong><?= (int) $entradas_count ?></strong>
                <span>Llegadas hoy</span>
            </article>
            <article class="res-metric">
                <div class="res-metric-icon metric-departures"><i class="fas fa-sign-out-alt"></i></div>
                <strong><?= (int) $salidas_count ?></strong>
                <span>Salidas hoy</span>
            </article>
            <article class="res-metric">
                <div class="res-metric-icon metric-confirmed"><i class="fas fa-calendar-check"></i></div>
                <strong><?= (int) ($estado_counts['confirmada'] ?? 0) ?></strong>
                <span>Confirmadas</span>
            </article>
            <article class="res-metric">
                <div class="res-metric-icon metric-house"><i class="fas fa-bed"></i></div>
                <strong><?= (int) ($estado_counts['checked_in'] ?? 0) ?></strong>
                <span>Hospedados</span>
            </article>
            <article class="res-metric">
                <div class="res-metric-icon metric-cancelled"><i class="fas fa-times"></i></div>
                <strong><?= (int) ($estado_counts['cancelada'] ?? 0) ?></strong>
                <span>Canceladas</span>
            </article>
        </section>

        <section class="res-filterbar no-print" aria-label="Filtros de reservaciones">
            <div class="res-tabs">
                <button type="button" onclick="filtrarEstado('todos')" class="res-tab filtro-estado is-active" data-estado="todos" title="Mostrar todas las reservaciones">
                    Todas <span><?= (int) $total_visible ?></span>
                </button>
                <button type="button" onclick="filtrarEstado('confirmada')" class="res-tab filtro-estado" data-estado="confirmada" title="Filtrar reservaciones confirmadas">
                    <span class="res-tab-dot"></span>Confirmada <span><?= (int) ($estado_counts['confirmada'] ?? 0) ?></span>
                </button>
                <button type="button" onclick="filtrarEstado('checked_in')" class="res-tab filtro-estado" data-estado="checked_in" title="Filtrar huéspedes hospedados">
                    <span class="res-tab-dot"></span>Hospedado <span><?= (int) ($estado_counts['checked_in'] ?? 0) ?></span>
                </button>
                <button type="button" onclick="filtrarEstado('checked_out')" class="res-tab filtro-estado" data-estado="checked_out" title="Filtrar reservaciones completadas">
                    <span class="res-tab-dot"></span>Completada <span><?= (int) ($estado_counts['checked_out'] ?? 0) ?></span>
                </button>
                <button type="button" onclick="filtrarEstado('cancelada')" class="res-tab filtro-estado" data-estado="cancelada" title="Filtrar reservaciones canceladas">
                    <span class="res-tab-dot"></span>Cancelada <span><?= (int) ($estado_counts['cancelada'] ?? 0) ?></span>
                </button>
            </div>

            <div class="res-date-tools">
                <input type="date" id="fechaCalendario" value="<?= htmlspecialchars($fecha_filtro) ?>" onchange="cambiarFecha(this.value)" aria-label="Fecha de reservaciones" title="Cambiar fecha del listado">
                <?php if (!$es_hoy): ?>
                    <button type="button" onclick="cambiarFecha('<?= $fecha_hoy ?>')" class="res-date-chip" title="Volver al listado de hoy">
                        <i class="fas fa-calendar-day"></i>Hoy
                    </button>
                <?php else: ?>
                    <span class="res-date-chip"><i class="fas fa-check"></i>Hoy</span>
                <?php endif; ?>
            </div>
        </section>

        <?php if (empty($reservaciones)): ?>
            <section class="res-empty">
                <div style="font-size:2rem;color:var(--res-accent);margin-bottom:10px;"><i class="fas fa-calendar-day"></i></div>
                <h2 style="font-size:1.1rem;font-weight:900;color:var(--res-heading);margin:0 0 6px;">Sin reservaciones</h2>
                <p style="margin:0 0 18px;">No hay reservaciones activas para <?= $es_hoy ? 'hoy' : htmlspecialchars(date('d/m/Y', $ts)) ?>.</p>
                <a href="<?= url('reservaciones/crear') ?>" class="res-btn res-btn-primary">
                    <i class="fas fa-plus"></i>Crear reservacion
                </a>
            </section>
        <?php else: ?>
            <div id="searchResults" class="hidden mb-3">
                <p class="text-sm text-gray-500">
                    <i class="fas fa-filter mr-1" style="color:var(--res-accent);"></i>
                    Mostrando <span id="searchCount" class="font-bold" style="color:var(--res-heading);">0</span> de <?= (int) $total_visible ?>
                </p>
            </div>

            <div id="filterEmpty" class="res-filter-empty">
                <div style="font-size:1.7rem;color:var(--res-accent);margin-bottom:8px;"><i class="fas fa-search"></i></div>
                <strong style="display:block;color:var(--res-heading);margin-bottom:4px;">Sin coincidencias</strong>
                Ajusta la busqueda o cambia el estado seleccionado.
            </div>

            <section class="res-table-shell" id="reservationsDesktop" aria-label="Listado de reservaciones">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Huesped</th>
                            <th>Habitacion</th>
                            <th>Llegada</th>
                            <th>Salida</th>
                            <th>Noches</th>
                            <th>Estado</th>
                            <th style="text-align:right;">Total</th>
                            <th style="text-align:right;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservaciones as $row): ?>
                            <?php
                                $res_id = (int) ($row['id'] ?? 0);
                                $folio = 'RSV-' . str_pad((string) $res_id, 4, '0', STR_PAD_LEFT);
                                $huesped_nombre = trim((string) ($row['huesped_nombre'] ?? 'Sin nombre'));
                                $huesped_telefono = trim((string) ($row['huesped_telefono'] ?? ''));
                                $habitacion_numero = trim((string) ($row['habitacion_numero'] ?? ''));
                                $habitacion_label = $habitacion_numero !== '' ? $habitacion_numero : 'Por asignar';
                                $habitacion_tipo = reserva_title($row['habitacion_tipo'] ?? '');
                                $total_habs_reserva = (int) ($row['total_habitaciones_reserva'] ?? 1);
                                $todas_habs = trim((string) ($row['todas_habitaciones'] ?? $habitacion_label));
                                $color = obtenerColorHab($habitacion_numero, $colores_habitacion, $color_numerico);
                                $estado = $row['estado'] ?? 'confirmada';
                                $estado_ui = reserva_estado_ui($estado, $estados);
                                $fecha_entrada_raw = $row['fecha_entrada'] ?? $fecha_hoy;
                                $fecha_salida_raw = $row['fecha_salida'] ?? $fecha_hoy;
                                $hora_llegada = !empty($row['hora_llegada_estimada']) ? substr($row['hora_llegada_estimada'], 0, 5) : (!empty($row['hora_entrada']) ? substr($row['hora_entrada'], 0, 5) : '15:00');
                                $hora_salida = !empty($row['hora_salida']) ? substr($row['hora_salida'], 0, 5) : '12:00';
                                $noches = reserva_noches($fecha_entrada_raw, $fecha_salida_raw);
                                $metodo_pago = trim((string) ($row['metodo_pago'] ?? ''));
                                $tiene_pago = $metodo_pago !== '';
                                $precio_total = (float) ($row['precio_total'] ?? 0);
                                $search_data = reserva_lower($folio . ' ' . $huesped_nombre . ' ' . $huesped_telefono . ' ' . $habitacion_label . ' ' . $todas_habs . ' ' . $estado_ui['label'] . ' ' . ($row['usuario_registro'] ?? ''));
                            ?>
                            <tr class="reservation-item" data-res-id="<?= $res_id ?>" data-search="<?= htmlspecialchars($search_data, ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                                <td>
                                    <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-folio res-detail-link" title="Ver detalle de <?= htmlspecialchars($folio) ?>">
                                        <?= htmlspecialchars($folio) ?>
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                </td>
                                <td>
                                    <div class="res-guest">
                                        <div class="res-avatar" style="background:linear-gradient(135deg, <?= htmlspecialchars($color['bg']) ?>, <?= htmlspecialchars($color['dark']) ?>);">
                                            <?= htmlspecialchars(reserva_iniciales($huesped_nombre)) ?>
                                        </div>
                                        <div>
                                            <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-guest-name res-detail-link" title="Ver reservación de <?= htmlspecialchars($huesped_nombre) ?>">
                                                <?= htmlspecialchars($huesped_nombre) ?>
                                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                            </a>
                                            <div class="res-guest-meta">
                                                <?php if ($huesped_telefono !== ''): ?>
                                                    <a href="tel:<?= htmlspecialchars($huesped_telefono) ?>" class="res-phone-link" title="Llamar a <?= htmlspecialchars($huesped_nombre) ?>"><?= htmlspecialchars($huesped_telefono) ?></a>
                                                <?php else: ?>
                                                    Sin telefono
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="res-room">
                                        <span class="res-room-mark" style="background:<?= htmlspecialchars($color['bg']) ?>;"></span>
                                        <div>
                                            <div class="res-room-main"><?= htmlspecialchars($habitacion_label) ?></div>
                                            <div class="res-room-type">
                                                <?= $total_habs_reserva > 1 ? htmlspecialchars($todas_habs) : ($habitacion_tipo !== '' ? htmlspecialchars($habitacion_tipo) : 'Habitacion') ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="res-date-main"><?= htmlspecialchars(format_date($fecha_entrada_raw, 'd M')) ?></div>
                                    <div class="res-date-sub"><?= htmlspecialchars($hora_llegada) ?></div>
                                </td>
                                <td>
                                    <div class="res-date-main"><?= htmlspecialchars(format_date($fecha_salida_raw, 'd M')) ?></div>
                                    <div class="res-date-sub"><?= htmlspecialchars($hora_salida) ?></div>
                                </td>
                                <td><span class="res-nights"><?= (int) $noches ?></span></td>
                                <td>
                                    <span class="res-state <?= htmlspecialchars($estado_ui['class']) ?>">
                                        <i class="fas fa-<?= htmlspecialchars($estado_ui['icon']) ?>"></i>
                                        <?= htmlspecialchars($estado_ui['label']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="res-total"><?= format_money($precio_total) ?></div>
                                    <div class="res-payment <?= $tiene_pago ? 'is-paid' : 'is-due' ?>">
                                        <?= $tiene_pago ? 'Pago registrado' : 'Por cobrar' ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="res-row-actions">
                                        <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-icon-btn" title="Ver detalle de <?= htmlspecialchars($folio) ?>" aria-label="Ver detalle de <?= htmlspecialchars($folio) ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($estado === 'confirmada'): ?>
                                            <button type="button" onclick="abrirModalCheckIn(<?= $res_id ?>, <?= htmlspecialchars(json_encode($precio_total), ENT_QUOTES, 'UTF-8') ?>)" class="res-row-command is-checkin" title="Hacer check-in de <?= htmlspecialchars($folio) ?>">
                                                <i class="fas fa-sign-in-alt"></i>Check-in
                                            </button>
                                        <?php elseif ($estado === 'checked_in'): ?>
                                            <button type="button" onclick="confirmarCheckOut(<?= $res_id ?>)" class="res-row-command is-checkout" title="Hacer check-out de <?= htmlspecialchars($folio) ?>">
                                                <i class="fas fa-sign-out-alt"></i>Check-out
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section class="res-mobile-list" id="reservationsMobile" aria-label="Reservaciones en movil">
                <?php foreach ($reservaciones as $row): ?>
                    <?php
                        $res_id = (int) ($row['id'] ?? 0);
                        $folio = 'RSV-' . str_pad((string) $res_id, 4, '0', STR_PAD_LEFT);
                        $huesped_nombre = trim((string) ($row['huesped_nombre'] ?? 'Sin nombre'));
                        $huesped_telefono = trim((string) ($row['huesped_telefono'] ?? ''));
                        $habitacion_numero = trim((string) ($row['habitacion_numero'] ?? ''));
                        $habitacion_label = $habitacion_numero !== '' ? $habitacion_numero : 'Por asignar';
                        $habitacion_tipo = reserva_title($row['habitacion_tipo'] ?? '');
                        $total_habs_reserva = (int) ($row['total_habitaciones_reserva'] ?? 1);
                        $todas_habs = trim((string) ($row['todas_habitaciones'] ?? $habitacion_label));
                        $color = obtenerColorHab($habitacion_numero, $colores_habitacion, $color_numerico);
                        $estado = $row['estado'] ?? 'confirmada';
                        $estado_ui = reserva_estado_ui($estado, $estados);
                        $fecha_entrada_raw = $row['fecha_entrada'] ?? $fecha_hoy;
                        $fecha_salida_raw = $row['fecha_salida'] ?? $fecha_hoy;
                        $hora_llegada = !empty($row['hora_llegada_estimada']) ? substr($row['hora_llegada_estimada'], 0, 5) : (!empty($row['hora_entrada']) ? substr($row['hora_entrada'], 0, 5) : '15:00');
                        $hora_salida = !empty($row['hora_salida']) ? substr($row['hora_salida'], 0, 5) : '12:00';
                        $noches = reserva_noches($fecha_entrada_raw, $fecha_salida_raw);
                        $metodo_pago = trim((string) ($row['metodo_pago'] ?? ''));
                        $tiene_pago = $metodo_pago !== '';
                        $precio_total = (float) ($row['precio_total'] ?? 0);
                        $search_data = reserva_lower($folio . ' ' . $huesped_nombre . ' ' . $huesped_telefono . ' ' . $habitacion_label . ' ' . $todas_habs . ' ' . $estado_ui['label'] . ' ' . ($row['usuario_registro'] ?? ''));
                    ?>
                    <article class="res-mobile-card reservation-item" data-res-id="<?= $res_id ?>" data-search="<?= htmlspecialchars($search_data, ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                        <div class="res-mobile-head">
                            <div class="res-mobile-guest">
                                <div class="res-avatar" style="background:linear-gradient(135deg, <?= htmlspecialchars($color['bg']) ?>, <?= htmlspecialchars($color['dark']) ?>);">
                                    <?= htmlspecialchars(reserva_iniciales($huesped_nombre)) ?>
                                </div>
                                <div style="min-width:0;">
                                    <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-guest-name res-detail-link" title="Ver reservación de <?= htmlspecialchars($huesped_nombre) ?>">
                                        <?= htmlspecialchars($huesped_nombre) ?>
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                    <div class="res-guest-meta">
                                        <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-detail-link" title="Ver detalle de <?= htmlspecialchars($folio) ?>"><?= htmlspecialchars($folio) ?></a>
                                        <?php if ($huesped_telefono !== ''): ?>
                                            · <a href="tel:<?= htmlspecialchars($huesped_telefono) ?>" class="res-phone-link" title="Llamar a <?= htmlspecialchars($huesped_nombre) ?>"><?= htmlspecialchars($huesped_telefono) ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <span class="res-state <?= htmlspecialchars($estado_ui['class']) ?>">
                                <i class="fas fa-<?= htmlspecialchars($estado_ui['icon']) ?>"></i>
                                <?= htmlspecialchars($estado_ui['label']) ?>
                            </span>
                        </div>

                        <div class="res-mobile-room">
                            <span class="res-room-mark" style="background:<?= htmlspecialchars($color['bg']) ?>;"></span>
                            <span><?= htmlspecialchars($habitacion_label) ?></span>
                            <small style="color:#8B96A9;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= $total_habs_reserva > 1 ? htmlspecialchars($todas_habs) : ($habitacion_tipo !== '' ? htmlspecialchars($habitacion_tipo) : 'Habitacion') ?>
                            </small>
                        </div>

                        <div class="res-mobile-grid">
                            <div class="res-mobile-info">
                                <span>Llegada</span>
                                <strong><?= htmlspecialchars(format_date($fecha_entrada_raw, 'd M')) ?> · <?= htmlspecialchars($hora_llegada) ?></strong>
                            </div>
                            <div class="res-mobile-info">
                                <span>Salida</span>
                                <strong><?= htmlspecialchars(format_date($fecha_salida_raw, 'd M')) ?> · <?= htmlspecialchars($hora_salida) ?></strong>
                            </div>
                            <div class="res-mobile-info">
                                <span>Noches</span>
                                <strong><?= (int) $noches ?></strong>
                            </div>
                            <div class="res-mobile-info">
                                <span>Total</span>
                                <strong><?= format_money($precio_total) ?></strong>
                            </div>
                        </div>

                        <div class="res-mobile-actions">
                            <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-action-pill" title="Ver detalle de <?= htmlspecialchars($folio) ?>">
                                <i class="fas fa-eye"></i>Ver
                            </a>
                            <?php if ($estado === 'confirmada'): ?>
                                <button type="button" onclick="abrirModalCheckIn(<?= $res_id ?>, <?= htmlspecialchars(json_encode($precio_total), ENT_QUOTES, 'UTF-8') ?>)" class="res-action-pill res-action-primary" title="Hacer check-in de <?= htmlspecialchars($folio) ?>">
                                    <i class="fas fa-sign-in-alt"></i>Check-in
                                </button>
                            <?php elseif ($estado === 'checked_in'): ?>
                                <button type="button" onclick="confirmarCheckOut(<?= $res_id ?>)" class="res-action-pill res-action-warning" title="Hacer check-out de <?= htmlspecialchars($folio) ?>">
                                    <i class="fas fa-sign-out-alt"></i>Check-out
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </div>
</div>

<div id="modalCheckIn" class="res-checkin-modal hidden" aria-hidden="true">
    <section class="res-checkin-card" role="dialog" aria-modal="true" aria-labelledby="checkInTitle">
        <div class="res-checkin-head">
            <div class="res-checkin-title">
                <span class="res-checkin-icon"><i class="fas fa-sign-in-alt"></i></span>
                <div>
                    <h3 id="checkInTitle">Confirmar check-in</h3>
                    <p>Revisa llegada, cobro y factura antes de confirmar.</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCheckIn()" class="res-checkin-close" aria-label="Cerrar check-in">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formCheckInModal" method="POST" action="" class="res-checkin-form">
            <?= csrf_field() ?>
            <div class="res-checkin-body">
                <div class="res-checkin-summary">
                    <div class="res-ci-total">
                        <span class="res-ci-kicker">Total a cobrar</span>
                        <div id="totalACobrar" class="res-ci-amount">$0.00</div>
                    </div>
                    <div class="res-ci-time">
                        <label for="hora_entrada_checkin_index" class="res-ci-label">Hora de llegada</label>
                        <input type="time" id="hora_entrada_checkin_index" name="hora_entrada" value="<?= date('H:i') ?>" class="res-ci-input" required>
                    </div>
                </div>

                <section class="res-ci-section">
                    <div class="res-ci-section-head">
                        <h4><i class="fas fa-wallet"></i>Metodos de pago</h4>
                        <span>Uno o varios</span>
                    </div>

                    <div id="metodosPagoContainer" class="res-ci-methods">
                        <article class="res-pay-method res-pay-cash">
                            <label class="res-pay-toggle">
                                <input type="checkbox" id="check_efectivo" onchange="toggleMetodoPago('efectivo')">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Efectivo</span>
                            </label>
                            <div id="panel_efectivo" class="res-pay-panel hidden">
                                <div class="res-ci-grid">
                                    <div>
                                        <label for="monto_efectivo" class="res-ci-label">Total a cobrar</label>
                                        <input type="number" name="monto_efectivo" id="monto_efectivo" data-money-format="true" step="0.01" min="0" readonly class="res-ci-input">
                                    </div>
                                    <div>
                                        <label for="recibido_efectivo" class="res-ci-label">Monto recibido</label>
                                        <input type="number" name="recibido_efectivo" id="recibido_efectivo" data-money-format="true" step="0.01" min="0" placeholder="0.00" oninput="calcularCambio()" onchange="calcularCambio()" class="res-ci-input">
                                    </div>
                                </div>
                                <div class="res-change-row">
                                    <span>Cambio</span>
                                    <strong id="cambio_efectivo">$0.00</strong>
                                </div>
                            </div>
                        </article>

                        <article class="res-pay-method res-pay-card">
                            <label class="res-pay-toggle">
                                <input type="checkbox" id="check_tarjeta" onchange="toggleMetodoPago('tarjeta')">
                                <i class="fas fa-credit-card"></i>
                                <span>Tarjeta</span>
                            </label>
                            <div id="panel_tarjeta" class="res-pay-panel hidden">
                                <div class="res-card-type">
                                    <label id="label_credito" class="res-radio-chip">
                                        <input type="radio" name="tipo_tarjeta" value="credito">
                                        <i class="fas fa-credit-card"></i>
                                        Credito
                                    </label>
                                    <label id="label_debito" class="res-radio-chip">
                                        <input type="radio" name="tipo_tarjeta" value="debito">
                                        <i class="fas fa-money-check-alt"></i>
                                        Debito
                                    </label>
                                </div>
                                <div class="res-ci-grid">
                                    <div>
                                        <label for="monto_tarjeta" class="res-ci-label">Monto</label>
                                        <input type="number" name="monto_tarjeta" id="monto_tarjeta" data-money-format="true" step="0.01" min="0" oninput="calcularTotales()" onchange="calcularTotales()" class="res-ci-input">
                                    </div>
                                    <div>
                                        <label for="referencia_tarjeta" class="res-ci-label">Referencia</label>
                                        <input type="text" name="referencia_tarjeta" id="referencia_tarjeta" placeholder="Ultimos 4 digitos" class="res-ci-input">
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="res-pay-method res-pay-transfer">
                            <label class="res-pay-toggle">
                                <input type="checkbox" id="check_transferencia" onchange="toggleMetodoPago('transferencia')">
                                <i class="fas fa-exchange-alt"></i>
                                <span>Transferencia</span>
                            </label>
                            <div id="panel_transferencia" class="res-pay-panel hidden">
                                <div class="res-ci-grid">
                                    <div>
                                        <label for="monto_transferencia" class="res-ci-label">Monto</label>
                                        <input type="number" name="monto_transferencia" id="monto_transferencia" data-money-format="true" step="0.01" min="0" oninput="calcularTotales()" onchange="calcularTotales()" class="res-ci-input">
                                    </div>
                                    <div>
                                        <label for="referencia_transferencia" class="res-ci-label">Referencia</label>
                                        <input type="text" name="referencia_transferencia" id="referencia_transferencia" placeholder="Numero de operacion" class="res-ci-input">
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="res-ci-section">
                    <div class="res-ci-section-head">
                        <h4><i class="fas fa-file-invoice"></i>Factura</h4>
                        <span>Requerido</span>
                    </div>
                    <div id="facturaContainer" class="res-invoice-options">
                        <label id="label_factura_si" class="res-invoice-choice">
                            <input type="radio" name="requiere_factura" id="factura_si" value="si" onchange="seleccionarFactura('si')">
                            <span>
                                <strong>Si requiere</strong>
                                <span>Registrar para facturacion.</span>
                            </span>
                        </label>
                        <label id="label_factura_no" class="res-invoice-choice">
                            <input type="radio" name="requiere_factura" id="factura_no" value="no" onchange="seleccionarFactura('no')">
                            <span>
                                <strong>No requiere</strong>
                                <span>Solo registro interno del pago.</span>
                            </span>
                        </label>
                    </div>
                    <div id="facturaValidacion" class="res-ci-alert hidden">
                        <i class="fas fa-exclamation-circle"></i>
                        Debe indicar si el cliente requiere factura.
                    </div>
                    <div id="facturaInfoInterna" class="res-ci-note hidden">
                        <i class="fas fa-info-circle"></i>
                        El pago con tarjeta o transferencia quedara en facturacion para uso interno.
                    </div>
                </section>

                <section class="res-ci-summary">
                    <h5>Resumen de pago</h5>
                    <div class="res-ci-row">
                        <span>Total a cobrar</span>
                        <strong id="resumenTotal">$0.00</strong>
                    </div>
                    <div class="res-ci-row is-paid">
                        <span>Total pagado</span>
                        <strong id="resumenPagado">$0.00</strong>
                    </div>
                    <div id="divRestante" class="res-ci-row is-due" style="display:none;">
                        <span>Restante</span>
                        <strong id="resumenRestante">$0.00</strong>
                    </div>
                    <div id="divCambio" class="res-ci-row is-change" style="display:none;">
                        <span>Cambio total</span>
                        <strong id="resumenCambio">$0.00</strong>
                    </div>
                </section>

                <div id="mensajeValidacion" class="res-ci-alert hidden"></div>
            </div>

            <div class="res-checkin-actions">
                <button type="button" onclick="cerrarModalCheckIn()" class="res-ci-cancel">Cancelar</button>
                <button type="submit" id="btnConfirmarCheckIn" class="res-ci-confirm">
                    <i class="fas fa-check"></i>
                    Confirmar check-in
                </button>
            </div>
        </form>
    </section>
</div>

<div id="modalExportarPDF" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="res-export-card">
            <button type="button" onclick="cerrarModalExportarPDF()" class="res-export-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
            <div class="res-export-hd">
                <span class="res-export-ic"><i class="fas fa-file-pdf"></i></span>
                <div>
                    <h3 class="res-export-title">Exportar a PDF</h3>
                    <p class="res-export-sub">Reporte de reservaciones del día</p>
                </div>
            </div>
            <form id="formExportarPDF" class="res-export-body">
                <label class="res-export-label">Fecha del reporte</label>
                <input type="date" id="fechaExportar" name="fecha" value="<?= date('Y-m-d') ?>" class="lc-form-input">
                <p class="res-export-help"><i class="fas fa-circle-info"></i>Se exportarán las reservaciones activas de esa fecha.</p>
                <div class="res-export-actions">
                    <button type="button" onclick="cerrarModalExportarPDF()" class="btn-modal-cancel">Cancelar</button>
                    <button type="submit" class="btn-modal-confirm">
                        <i class="fas fa-download"></i>Generar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalExportarExcel" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="res-export-card">
            <button type="button" onclick="cerrarModalExportarExcel()" class="res-export-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
            <div class="res-export-hd res-export-hd--excel">
                <span class="res-export-ic"><i class="fas fa-file-excel"></i></span>
                <div>
                    <h3 class="res-export-title">Exportar a Excel</h3>
                    <p class="res-export-sub">Hoja de cálculo de reservaciones</p>
                </div>
            </div>
            <form id="formExportarExcel" class="res-export-body">
                <label class="res-export-label">Fecha del reporte</label>
                <input type="date" id="fechaExportarExcel" name="fecha" value="<?= date('Y-m-d') ?>" class="lc-form-input">
                <p class="res-export-help"><i class="fas fa-circle-info"></i>Se exportarán las reservaciones activas de esa fecha.</p>
                <div class="res-export-actions">
                    <button type="button" onclick="cerrarModalExportarExcel()" class="btn-modal-cancel">Cancelar</button>
                    <button type="submit" class="btn-modal-confirm btn-modal-confirm--excel">
                        <i class="fas fa-download"></i>Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="formCheckOut" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="hora_salida" value="<?= date('H:i:s') ?>">
</form>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const baseUrl = '<?= url('') ?>';
let filtroEstadoActual = 'todos';
let totalReservacion = 0;

function cambiarFecha(f) {
    if (f) window.location.href = baseUrl + '/reservaciones?fecha=' + encodeURIComponent(f);
}

const searchInput = document.getElementById('buscarReservacion');
const reservationItems = document.querySelectorAll('.reservation-item');
const searchResults = document.getElementById('searchResults');
const searchCount = document.getElementById('searchCount');
const filterEmpty = document.getElementById('filterEmpty');

if (searchInput) {
    searchInput.addEventListener('input', aplicarFiltros);
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            aplicarFiltros();
            this.blur();
        }
    });
}

function aplicarFiltros() {
    const term = (searchInput?.value || '').toLowerCase().trim();
    const visibleIds = new Set();

    reservationItems.forEach((item, index) => {
        const search = item.getAttribute('data-search') || '';
        const estado = item.getAttribute('data-estado') || '';
        const id = item.getAttribute('data-res-id') || String(index);
        const matchesSearch = !term || search.includes(term);
        const matchesEstado = filtroEstadoActual === 'todos' || estado === filtroEstadoActual;
        const visible = matchesSearch && matchesEstado;

        item.classList.toggle('hidden-search', !visible);
        if (visible) visibleIds.add(id);
    });

    const visibleCount = visibleIds.size;
    if (searchResults && searchCount) {
        if (term || filtroEstadoActual !== 'todos') {
            searchResults.classList.remove('hidden');
            searchCount.textContent = visibleCount;
        } else {
            searchResults.classList.add('hidden');
        }
    }

    if (filterEmpty) {
        filterEmpty.classList.toggle('is-visible', visibleCount === 0 && reservationItems.length > 0);
    }
}

function filtrarEstado(estado) {
    filtroEstadoActual = estado;
    document.querySelectorAll('.filtro-estado').forEach(button => {
        button.classList.toggle('is-active', button.getAttribute('data-estado') === estado);
    });
    aplicarFiltros();
}

function seleccionarFactura(valor) {
    const labelSi = document.getElementById('label_factura_si');
    const labelNo = document.getElementById('label_factura_no');
    const validacion = document.getElementById('facturaValidacion');
    const infoInterna = document.getElementById('facturaInfoInterna');

    if (validacion) validacion.classList.add('hidden');
    if (labelSi) labelSi.classList.toggle('is-selected', valor === 'si');
    if (labelNo) labelNo.classList.toggle('is-selected', valor === 'no');

    if (valor === 'si') {
        if (infoInterna) infoInterna.classList.add('hidden');
    } else {
        mostrarInfoFacturaInterna();
    }
}

function mostrarInfoFacturaInterna() {
    const infoInterna = document.getElementById('facturaInfoInterna');
    const facturaNo = document.getElementById('factura_no');
    const checkTarjeta = document.getElementById('check_tarjeta');
    const checkTransferencia = document.getElementById('check_transferencia');
    if (!infoInterna) return;

    const debeMostrarse = facturaNo?.checked && ((checkTarjeta && checkTarjeta.checked) || (checkTransferencia && checkTransferencia.checked));
    infoInterna.classList.toggle('hidden', !debeMostrarse);
}

function validarFacturaCheckIn() {
    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const validacion = document.getElementById('facturaValidacion');

    if (!facturaSi?.checked && !facturaNo?.checked) {
        if (validacion) validacion.classList.remove('hidden');
        return false;
    }

    if (validacion) validacion.classList.add('hidden');
    return true;
}

function resetearFacturaCheckIn() {
    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const labelSi = document.getElementById('label_factura_si');
    const labelNo = document.getElementById('label_factura_no');
    const validacion = document.getElementById('facturaValidacion');
    const infoInterna = document.getElementById('facturaInfoInterna');

    if (facturaSi) facturaSi.checked = false;
    if (facturaNo) facturaNo.checked = false;
    if (labelSi) labelSi.classList.remove('is-selected');
    if (labelNo) labelNo.classList.remove('is-selected');
    if (validacion) validacion.classList.add('hidden');
    if (infoInterna) infoInterna.classList.add('hidden');
}

function resMoneyRead(inputOrValue) {
    if (window.MedisoftMoneyInput) {
        return window.MedisoftMoneyInput.read(inputOrValue);
    }

    const value = inputOrValue && typeof inputOrValue === 'object' && 'value' in inputOrValue
        ? inputOrValue.value
        : inputOrValue;

    return parseFloat(String(value || '').replace(/,/g, '')) || 0;
}

function resMoneySet(inputOrId, value) {
    const input = typeof inputOrId === 'string' ? document.getElementById(inputOrId) : inputOrId;
    if (!input) {
        return;
    }

    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.set(input, value);
    } else {
        input.value = Number(value || 0).toFixed(2);
    }
}

function resMoneySanitize(form) {
    if (window.MedisoftMoneyInput) {
        window.MedisoftMoneyInput.sanitize(form);
    }
}

function abrirModalCheckIn(id, total) {
    const modal = document.getElementById('modalCheckIn');
    const form = document.getElementById('formCheckInModal');
    const totalLabel = document.getElementById('totalACobrar');
    const resumenTotal = document.getElementById('resumenTotal');
    if (!modal || !form || !totalLabel) return;

    totalReservacion = parseFloat(total || 0);
    form.action = baseUrl + '/reservaciones/check-in/' + id;
    form.reset();
    resetearFormularioPago();
    resetearFacturaCheckIn();

    totalLabel.textContent = formatMoney(totalReservacion);
    if (resumenTotal) resumenTotal.textContent = formatMoney(totalReservacion);

    const hora = form.querySelector('input[name="hora_entrada"]');
    if (hora) hora.value = new Date().toTimeString().slice(0, 5);

    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
}

function cerrarModalCheckIn() {
    const modal = document.getElementById('modalCheckIn');
    const sidebar = document.getElementById('sidebar');
    if (modal) {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
    }
    if (sidebar) sidebar.style.display = '';
    document.body.classList.remove('overflow-hidden');
    resetearFormularioPago();
    resetearFacturaCheckIn();
}

function toggleMetodoPago(metodo) {
    const checkbox = document.getElementById('check_' + metodo);
    const panel = document.getElementById('panel_' + metodo);
    const montoInput = document.getElementById('monto_' + metodo);
    if (!checkbox || !panel || !montoInput) return;

    const card = checkbox.closest('.res-pay-method');
    panel.classList.toggle('hidden', !checkbox.checked);
    if (card) card.classList.toggle('is-open', checkbox.checked);

    if (checkbox.checked) {
        if (metodo === 'efectivo') {
            const totalPagadoOtros = calcularTotalPagadoSinEfectivo();
            const montoRestante = Math.max(0, totalReservacion - totalPagadoOtros);
            resMoneySet(montoInput, montoRestante);
            const recibidoInput = document.getElementById('recibido_efectivo');
            if (recibidoInput) recibidoInput.value = '';
        } else {
            montoInput.focus();
        }
    } else {
        montoInput.value = '';

        if (metodo === 'efectivo') {
            const recibidoInput = document.getElementById('recibido_efectivo');
            const cambioSpan = document.getElementById('cambio_efectivo');
            if (recibidoInput) recibidoInput.value = '';
            if (cambioSpan) {
                cambioSpan.textContent = '$0.00';
                cambioSpan.classList.remove('is-negative');
            }
        }
    }

    calcularTotales();
    mostrarInfoFacturaInterna();
}

function calcularTotalPagadoSinEfectivo() {
    let total = 0;
    ['tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);
        if (checkbox?.checked && montoInput) total += resMoneyRead(montoInput);
    });
    return total;
}

function calcularTotales() {
    const totalOtros = calcularTotalPagadoSinEfectivo();
    let totalPagado = totalOtros;
    const checkEfectivo = document.getElementById('check_efectivo');
    const montoEfectivoInput = document.getElementById('monto_efectivo');

    if (checkEfectivo?.checked && montoEfectivoInput) {
        const montoRestante = Math.max(0, totalReservacion - totalOtros);
        resMoneySet(montoEfectivoInput, montoRestante);
        totalPagado += montoRestante;
    }

    const resumenPagado = document.getElementById('resumenPagado');
    if (resumenPagado) resumenPagado.textContent = formatMoney(totalPagado);

    const totalPagadoFinal = calcularTotalPagado();
    const diferencia = totalReservacion - totalPagadoFinal;
    const divRestante = document.getElementById('divRestante');
    const divCambio = document.getElementById('divCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');

    if (divRestante) divRestante.style.display = 'none';
    if (divCambio) divCambio.style.display = 'none';

    if (Math.abs(diferencia) < 0.01) {
        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
    } else if (diferencia > 0.01) {
        if (!checkEfectivo?.checked) {
            if (divRestante) {
                divRestante.style.display = 'flex';
                const resumenRestante = document.getElementById('resumenRestante');
                if (resumenRestante) resumenRestante.textContent = formatMoney(diferencia);
            }
            mostrarMensaje('Falta completar el pago', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        } else {
            ocultarMensaje();
            if (btnConfirmar) btnConfirmar.disabled = false;
        }
    } else {
        mostrarMensaje('El monto total excede el precio de la reservacion', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    }

    if (checkEfectivo?.checked) calcularCambio();
}

function calcularCambio() {
    const checkEfectivo = document.getElementById('check_efectivo');
    if (!checkEfectivo?.checked) return;

    const montoPagarInput = document.getElementById('monto_efectivo');
    const montoRecibidoInput = document.getElementById('recibido_efectivo');
    const cambioSpan = document.getElementById('cambio_efectivo');
    const divCambio = document.getElementById('divCambio');
    const resumenCambio = document.getElementById('resumenCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');
    if (!montoPagarInput || !montoRecibidoInput || !cambioSpan) return;

    const montoPagar = resMoneyRead(montoPagarInput);
    const montoRecibido = resMoneyRead(montoRecibidoInput);

    if (montoPagar === 0) {
        cambioSpan.textContent = '$0.00';
        cambioSpan.classList.remove('is-negative');
        if (divCambio) divCambio.style.display = 'none';
        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
        return;
    }

    if (montoRecibido > 0) {
        const cambio = montoRecibido - montoPagar;
        if (cambio >= 0) {
            cambioSpan.textContent = formatMoney(cambio);
            cambioSpan.classList.remove('is-negative');
            if (divCambio && cambio > 0) {
                divCambio.style.display = 'flex';
                if (resumenCambio) resumenCambio.textContent = formatMoney(cambio);
            } else if (divCambio) {
                divCambio.style.display = 'none';
            }
            ocultarMensaje();
            if (btnConfirmar) btnConfirmar.disabled = false;
        } else {
            cambioSpan.textContent = 'Monto insuficiente';
            cambioSpan.classList.add('is-negative');
            if (divCambio) divCambio.style.display = 'none';
            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    } else {
        cambioSpan.textContent = '$0.00';
        cambioSpan.classList.remove('is-negative');
        if (divCambio) divCambio.style.display = 'none';
        if (montoPagar > 0) {
            mostrarMensaje('Ingrese el monto recibido en efectivo', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    }
}

function calcularTotalPagado() {
    let total = 0;
    ['efectivo', 'tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);
        if (checkbox?.checked && montoInput) total += resMoneyRead(montoInput);
    });
    return total;
}

function mostrarMensaje(mensaje, tipo) {
    const div = document.getElementById('mensajeValidacion');
    if (!div) return;

    div.className = 'res-ci-alert';
    if (tipo === 'warning') div.classList.add('is-warning');
    if (tipo === 'info') div.classList.add('is-info');
    if (tipo === 'success') div.classList.add('is-success');
    div.textContent = mensaje;
}

function ocultarMensaje() {
    const div = document.getElementById('mensajeValidacion');
    if (div) div.className = 'res-ci-alert hidden';
}

function resetearFormularioPago() {
    ['efectivo', 'tarjeta', 'transferencia'].forEach(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        const panel = document.getElementById('panel_' + metodo);
        const montoInput = document.getElementById('monto_' + metodo);
        const card = checkbox?.closest('.res-pay-method');

        if (checkbox) checkbox.checked = false;
        if (panel) panel.classList.add('hidden');
        if (montoInput) montoInput.value = '';
        if (card) card.classList.remove('is-open');
    });

    const recibidoEfectivo = document.getElementById('recibido_efectivo');
    if (recibidoEfectivo) recibidoEfectivo.value = '';

    const cambioEfectivo = document.getElementById('cambio_efectivo');
    if (cambioEfectivo) {
        cambioEfectivo.textContent = '$0.00';
        cambioEfectivo.classList.remove('is-negative');
    }

    const refTarjeta = document.getElementById('referencia_tarjeta');
    if (refTarjeta) refTarjeta.value = '';

    const refTransferencia = document.getElementById('referencia_transferencia');
    if (refTransferencia) refTransferencia.value = '';

    document.querySelectorAll('input[name="tipo_tarjeta"]').forEach(radio => { radio.checked = false; });

    const resumenPagado = document.getElementById('resumenPagado');
    if (resumenPagado) resumenPagado.textContent = '$0.00';

    const divRestante = document.getElementById('divRestante');
    const divCambio = document.getElementById('divCambio');
    if (divRestante) divRestante.style.display = 'none';
    if (divCambio) divCambio.style.display = 'none';

    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');
    if (btnConfirmar) btnConfirmar.disabled = false;

    ocultarMensaje();
}

document.getElementById('formCheckInModal')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!validarFacturaCheckIn()) {
        document.getElementById('facturaContainer')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    const metodosSeleccionados = ['efectivo', 'tarjeta', 'transferencia'].filter(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        return checkbox && checkbox.checked;
    });

    if (metodosSeleccionados.length === 0) {
        mostrarMensaje('Debe seleccionar al menos un metodo de pago', 'error');
        return;
    }

    const checkTarjeta = document.getElementById('check_tarjeta');
    if (checkTarjeta?.checked) {
        const tipoTarjetaSeleccionado = document.querySelector('input[name="tipo_tarjeta"]:checked');
        if (!tipoTarjetaSeleccionado) {
            mostrarMensaje('Debe seleccionar el tipo de tarjeta: credito o debito', 'error');
            document.getElementById('panel_tarjeta')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    }

    const totalPagado = calcularTotalPagado();
    if (Math.abs(totalPagado - totalReservacion) > 0.01) {
        mostrarMensaje('El total pagado no coincide con el monto de la reservacion', 'error');
        return;
    }

    const checkEfectivo = document.getElementById('check_efectivo');
    if (checkEfectivo?.checked) {
        const recibido = resMoneyRead(document.getElementById('recibido_efectivo'));
        const montoPagar = resMoneyRead(document.getElementById('monto_efectivo'));

        if (montoPagar > 0 && recibido < montoPagar) {
            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            return;
        }
    }

    resMoneySanitize(this);
    this.submit();
});

function confirmarCheckOut(id) {
    const form = document.getElementById('formCheckOut');
    if (!form) return;

    Swal.fire({
        title: 'Hacer Check-out?',
        text: 'Se registrara la salida.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--brand-primary, #1B2746)',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Si, check-out',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        customClass: { popup: 'rounded-xl', confirmButton: 'rounded-lg', cancelButton: 'rounded-lg' }
    }).then(result => {
        if (result.isConfirmed) {
            const horaSalida = form.querySelector('input[name="hora_salida"]');
            if (horaSalida) horaSalida.value = new Date().toTimeString().slice(0, 8);
            form.action = baseUrl + '/reservaciones/check-out/' + id;
            form.submit();
        }
    });
}

function abrirModalExportarPDF() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
    const modal = document.getElementById('modalExportarPDF');
    if (modal) {
        modal.classList.remove('hidden');
        const fecha = document.getElementById('fechaExportar');
        if (fecha) fecha.value = new Date().toISOString().split('T')[0];
        document.body.classList.add('overflow-hidden');
    }
}

function cerrarModalExportarPDF() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = '';
    const modal = document.getElementById('modalExportarPDF');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

document.getElementById('formExportarPDF')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fecha = document.getElementById('fechaExportar')?.value;
    if (!fecha) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una fecha', confirmButtonColor: 'var(--brand-primary, #1B2746)' });
        return;
    }
    window.open(baseUrl + '/reservaciones/exportar-pdf?fecha=' + encodeURIComponent(fecha), '_blank');
    cerrarModalExportarPDF();
});

function abrirModalExportarExcel() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
    const modal = document.getElementById('modalExportarExcel');
    if (modal) {
        modal.classList.remove('hidden');
        const fecha = document.getElementById('fechaExportarExcel');
        if (fecha) fecha.value = new Date().toISOString().split('T')[0];
        document.body.classList.add('overflow-hidden');
    }
}

function cerrarModalExportarExcel() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = '';
    const modal = document.getElementById('modalExportarExcel');
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

document.getElementById('formExportarExcel')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fecha = document.getElementById('fechaExportarExcel')?.value;
    if (!fecha) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Seleccione una fecha', confirmButtonColor: '#166534' });
        return;
    }
    window.open(baseUrl + '/reservaciones/exportar-excel?fecha=' + encodeURIComponent(fecha), '_blank');
    cerrarModalExportarExcel();
});

document.getElementById('modalCheckIn')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCheckIn();
});
document.getElementById('modalExportarPDF')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExportarPDF();
});
document.getElementById('modalExportarExcel')?.addEventListener('click', function(e) {
    if (e.target === this) cerrarModalExportarExcel();
});

function formatMoney(amount) {
    return '$' + parseFloat(amount || 0).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}
</script>
