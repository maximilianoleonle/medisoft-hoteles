<?php
/**
 * Vista de Reservaciones
 * UI operativa brand-aware para recepcion hotelera.
 */

$estadisticas        = $estadisticas        ?? [];
$entradas_hoy        = $entradas_hoy        ?? [];
$salidas_hoy         = $salidas_hoy         ?? [];
$reservaciones       = $reservaciones       ?? [];
$proximas_reservaciones = $proximas_reservaciones ?? [];
$checkins_pendientes = isset($checkins_pendientes) && is_array($checkins_pendientes) ? $checkins_pendientes : [];
$checkouts_vencidos  = isset($checkouts_vencidos) && is_array($checkouts_vencidos) ? $checkouts_vencidos : [];
$llegadas_tardias    = isset($llegadas_tardias) && is_array($llegadas_tardias) ? $llegadas_tardias : [];
$total_reservaciones = $total_reservaciones ?? count($reservaciones);
$estados             = $estados             ?? [];
$buscar              = $buscar              ?? '';
$fecha_filtro        = $fecha_filtro        ?? date('Y-m-d');
$res_hotel_checkin_hora = function_exists('hotel_config_get')
    ? (string) hotel_config_get('operacion.checkin_hora', '15:00')
    : '15:00';
$res_hotel_checkin_hora = substr(trim($res_hotel_checkin_hora), 0, 5);
if (!preg_match('/^\d{2}:\d{2}$/', $res_hotel_checkin_hora)) {
    $res_hotel_checkin_hora = '15:00';
}

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

if (!function_exists('reserva_habitaciones_lista')) {
    function reserva_habitaciones_lista($row) {
        $habitaciones = [];
        $fuente = trim((string) ($row['todas_habitaciones'] ?? ''));

        if ($fuente === '') {
            $fuente = trim((string) ($row['habitacion_numero'] ?? ''));
        }

        foreach (preg_split('/\s*,\s*/', $fuente, -1, PREG_SPLIT_NO_EMPTY) as $habitacion) {
            $habitacion = trim((string) $habitacion);
            if ($habitacion === '') {
                continue;
            }

            $habitaciones[$habitacion] = $habitacion;
        }

        return array_values($habitaciones);
    }
}

if (!function_exists('reserva_agrupadas_index')) {
    function reserva_agrupadas_index($reservaciones) {
        $agrupadas = [];
        $orden = [];

        foreach ($reservaciones as $indice => $row) {
            $res_id = trim((string) ($row['id'] ?? ''));
            $clave = $res_id !== '' ? 'res-' . $res_id : 'row-' . $indice;

            if (!isset($agrupadas[$clave])) {
                $row['_habitaciones_lista'] = [];
                $row['_habitaciones_map'] = [];
                $agrupadas[$clave] = $row;
                $orden[] = $clave;
            }

            foreach (reserva_habitaciones_lista($row) as $habitacion) {
                if (!isset($agrupadas[$clave]['_habitaciones_map'][$habitacion])) {
                    $agrupadas[$clave]['_habitaciones_map'][$habitacion] = true;
                    $agrupadas[$clave]['_habitaciones_lista'][] = $habitacion;
                }
            }
        }

        $resultado = [];
        foreach ($orden as $clave) {
            $row = $agrupadas[$clave];
            $habitaciones = $row['_habitaciones_lista'] ?? [];
            $total_habitaciones = count($habitaciones);

            if ($total_habitaciones > 0) {
                $row['todas_habitaciones'] = implode(', ', $habitaciones);
                $row['total_habitaciones_reserva'] = max((int) ($row['total_habitaciones_reserva'] ?? 1), $total_habitaciones);
            }

            unset($row['_habitaciones_map']);
            $resultado[] = $row;
        }

        return $resultado;
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

$reservaciones = reserva_agrupadas_index($reservaciones);
$proximas_reservaciones = reserva_agrupadas_index($proximas_reservaciones);
$total_reservaciones = count($reservaciones);
$total_proximas = count($proximas_reservaciones);
$proxima_reserva = $proximas_reservaciones[0] ?? null;

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
$total_visible = $total_reservaciones;
$total_alertas_pendientes = count($checkouts_vencidos) + count($checkins_pendientes) + count($llegadas_tardias);
$abrir_agenda_proxima = ($total_visible === 0 && $total_proximas > 0);
$hotel_nombre_reservas = function_exists('current_hotel_display_name') ? (string) current_hotel_display_name() : 'Hotel';
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.res-bookings {
    --res-brand: var(--brand-primary, #1B2746);
    --res-brand-2: var(--brand-secondary, #0F172A);
    --res-accent: var(--brand-accent, #BD9441);
    --res-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --res-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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
    font-family: var(--res-sans);
}
.res-shell { max-width: 1680px; margin: 0 auto; padding: 28px 22px 38px; }
.res-topbar { display: flex; flex-wrap: wrap; gap: 18px; align-items: flex-start; justify-content: space-between; margin-bottom: 20px; }
.res-title-lockup { flex: 1 1 420px; display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: start; column-gap: 14px; min-width: min(100%, 360px); max-width: min(960px, 100%); }
.res-title-copy { min-width: 0; padding-top: 1px; }
.res-hero-icon { width: 48px; height: 48px; display: grid; place-items: center; flex: 0 0 48px; border-radius: 15px; color: #fff; background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--res-accent), var(--res-brand) 54%, color-mix(in srgb, var(--res-brand) 68%, var(--brand-accent, #BD9441))); box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--res-brand) 72%, transparent); }
.res-kicker { display: block; margin: 0 0 2px; color: var(--res-muted); font-size: .72rem; font-weight: 900; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.res-title { margin: 0; color: var(--res-heading); font-size: clamp(2.35rem, 4vw, 3.35rem); font-family: var(--res-serif); font-weight: 700; letter-spacing: 0; line-height: .98; text-wrap: balance; }
.res-subtitle { max-width: 920px; margin-top: 9px; color: #718096; font-size: .94rem; font-weight: 600; line-height: 1.55; }
.res-actions { flex: 1 1 680px; min-width: min(100%, 680px); display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 10px; }
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
.res-date-chip { height: 39px; display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; border: 1px solid var(--res-line); padding: 0 12px; background: #fff; color: #40506A; font-weight: 800; font-size: .82rem; text-decoration: none; white-space: nowrap; }
.res-date-chip.is-upcoming { border-color: color-mix(in srgb, var(--res-accent) 34%, var(--res-line)); color: var(--res-heading); background: color-mix(in srgb, var(--res-accent) 7%, #fff); }
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
.res-alerts-btn:focus-visible,
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
.res-guest-copy { min-width: 0; }
.res-guest-name-row { min-width: 0; }
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
.res-room-list { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; max-width: 360px; }
.res-room-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 8px;
    border: 1px solid color-mix(in srgb, var(--res-accent) 24%, var(--res-line));
    background: color-mix(in srgb, var(--res-accent) 7%, #fff);
    color: var(--res-heading);
    padding: 3px 7px;
    font-size: .72rem;
    font-weight: 900;
    line-height: 1;
}
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
.res-row-actions { display: flex; justify-content: flex-end; align-items: center; gap: 8px; min-width: max-content; }
.res-icon-btn {
    width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--res-line);
    display: inline-flex; align-items: center; justify-content: center; color: #65728A; background: #fff;
    transition: color .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}
.res-icon-btn:hover { color: var(--res-heading); border-color: color-mix(in srgb, var(--res-accent) 42%, var(--res-line)); background: color-mix(in srgb, var(--res-accent) 8%, #fff); box-shadow: 0 10px 20px -16px color-mix(in srgb, var(--res-accent) 70%, transparent); transform: translateY(-1px); }
.res-row-command { min-width: 88px; height: 34px; border-radius: 10px; border: 0; padding: 0 12px; color: #fff; font-weight: 900; font-size: .76rem; line-height: 1; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: transform .18s ease, box-shadow .18s ease, filter .18s ease; }
.res-row-command i { flex: 0 0 auto; font-size: .82rem; }
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
.res-mobile-room { display: flex; align-items: flex-start; gap: 8px; color: var(--res-heading); font-weight: 900; margin: 10px 0; }
.res-mobile-room-body { min-width: 0; display: grid; gap: 4px; }
.res-mobile-room .res-room-list { max-width: 100%; margin-top: 2px; }
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
.res-empty-actions { display: inline-flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
.res-filter-empty { display: none; margin-top: 14px; }
.res-filter-empty.is-visible { display: block; }
.reservation-item.hidden-search { display: none !important; }
.reservation-item.is-linked,
.res-upcoming-card.is-linked { cursor: pointer; }
tr.reservation-item.is-linked:hover { background: color-mix(in srgb, var(--res-accent, #1E9E63) 6%, transparent); }
.reservation-item.is-linked:focus-visible,
.res-upcoming-card.is-linked:focus-visible { outline: 2px solid color-mix(in srgb, var(--res-accent, #1E9E63) 55%, transparent); outline-offset: -2px; }
.res-upcoming {
    margin-top: 18px;
    border: 1px solid var(--res-line);
    border-radius: 20px;
    background: linear-gradient(135deg, rgba(255,255,255,.98), color-mix(in srgb, var(--res-accent) 4%, #FFFFFF));
    box-shadow: 0 18px 42px rgba(15,23,42,.07);
    overflow: hidden;
}
.res-upcoming-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--res-line);
    background: rgba(255,255,255,.74);
    cursor: pointer;
    user-select: none;
}
.res-upcoming:not(.is-open) .res-upcoming-head {
    border-bottom-color: transparent;
}
.res-upcoming-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.res-upcoming-chevron {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 10px;
    border: 1px solid var(--res-line);
    background: rgba(255,255,255,.8);
    color: var(--res-heading);
    font-size: .82rem;
    flex-shrink: 0;
    transition: transform .3s cubic-bezier(.22,1,.36,1), background .18s ease, border-color .18s ease;
}
.res-upcoming.is-open .res-upcoming-chevron {
    transform: rotate(180deg);
    background: color-mix(in srgb, var(--res-accent) 8%, #fff);
    border-color: color-mix(in srgb, var(--res-accent) 34%, var(--res-line));
}
.res-upcoming-body {
    display: grid;
    grid-template-rows: 0fr;
    transition: grid-template-rows .3s cubic-bezier(.22,1,.36,1);
}
.res-upcoming.is-open .res-upcoming-body {
    grid-template-rows: 1fr;
}
.res-upcoming-body-inner {
    overflow: hidden;
    min-height: 0;
}
.res-section-kicker {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 5px;
    color: color-mix(in srgb, var(--res-accent) 68%, var(--res-heading));
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}
.res-upcoming-head h2 {
    margin: 0;
    color: var(--res-heading);
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.42rem;
    font-weight: 800;
    line-height: 1.05;
}
.res-upcoming-head p {
    margin: 5px 0 0;
    color: #748096;
    font-size: .86rem;
    font-weight: 700;
}
.res-upcoming-grid {
    display: grid;
    gap: 10px;
    padding: 14px;
}
.res-upcoming-card {
    display: grid;
    grid-template-columns: 66px minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    border: 1px solid color-mix(in srgb, var(--res-line) 78%, transparent);
    border-radius: 16px;
    background: rgba(255,255,255,.92);
    padding: 12px 14px;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}
.res-upcoming-card:hover {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--res-accent) 36%, var(--res-line));
    box-shadow: 0 16px 34px rgba(15,23,42,.08);
}
.res-upcoming-date {
    min-height: 64px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    align-content: center;
    gap: 1px;
    color: var(--res-heading);
    background: color-mix(in srgb, var(--res-accent) 10%, #fff);
    border: 1px solid color-mix(in srgb, var(--res-accent) 24%, var(--res-line));
}
.res-upcoming-date span {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1;
}
.res-upcoming-date strong {
    font-size: .68rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: #728096;
}
.res-upcoming-main { min-width: 0; }
.res-upcoming-title {
    display: flex;
    flex-wrap: wrap;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 5px;
}
.res-upcoming-title a {
    color: var(--res-heading);
    font-weight: 950;
    text-decoration: none;
}
.res-upcoming-title a:hover { text-decoration: underline; text-decoration-color: var(--res-accent); text-underline-offset: 3px; }
.res-upcoming-title span {
    color: #8B96A9;
    font-size: .75rem;
    font-weight: 900;
}
.res-upcoming-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 12px;
    color: #758198;
    font-size: .78rem;
    font-weight: 800;
}
.res-upcoming-meta span { display: inline-flex; align-items: center; gap: 6px; }
.res-upcoming-side {
    display: grid;
    justify-items: end;
    gap: 8px;
    min-width: 148px;
}
.res-upcoming-total {
    color: var(--res-heading);
    font-size: 1rem;
    font-weight: 950;
}
.res-upcoming-view {
    width: 38px;
    min-width: 38px;
    height: 34px;
    min-height: 34px;
    padding: 0;
}

@media (max-width: 780px) {
    .res-empty-actions { width: 100%; }
    .res-empty-actions .res-btn,
    .res-empty-actions .res-date-chip { flex: 1 1 160px; justify-content: center; }
    .res-upcoming { margin: 14px 12px 0; border-radius: 18px; }
    .res-upcoming-head { flex-direction: column; padding: 16px; }
    .res-upcoming-card { grid-template-columns: 54px minmax(0, 1fr); align-items: start; padding: 12px; }
    .res-upcoming-date { min-height: 54px; border-radius: 12px; }
    .res-upcoming-date span { font-size: 1.35rem; }
    .res-upcoming-side { grid-column: 1 / -1; grid-template-columns: 1fr auto auto; align-items: center; justify-items: start; min-width: 0; }
    .res-upcoming-side .res-action-pill { min-height: 34px; padding: 0 12px; }
    .res-upcoming-side .res-upcoming-view { padding: 0; }
}
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
.res-export-error { display: none; margin: 9px 2px 0; border: 1px solid #FECACA; border-radius: 10px; background: #FEF2F2; color: #B42318; padding: 8px 10px; font-size: .75rem; font-weight: 800; line-height: 1.35; }
.res-export-error.is-visible { display: block; }
.res-export-actions { display: flex; gap: 10px; margin-top: 22px; }
.res-export-actions .btn-modal-cancel { flex: 1; border: 1px solid var(--res-line); background: #fff; color: var(--res-brand-2); border-radius: 12px; padding: 12px; font-weight: 800; transition: .16s; }
.res-export-actions .btn-modal-cancel:hover { background: color-mix(in srgb, var(--res-brand) 5%, #fff); border-color: color-mix(in srgb, var(--res-brand) 28%, var(--res-line)); }
.res-export-actions .btn-modal-confirm { flex: 1.5; border-radius: 12px; padding: 12px; font-weight: 800; color: #fff; display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2)); box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--res-brand) 60%, transparent); transition: .16s; border: none; cursor: pointer; }
.res-export-actions .btn-modal-confirm:hover { transform: translateY(-1px); filter: brightness(1.05); }
.res-export-actions .btn-modal-confirm--excel { background: linear-gradient(135deg, #16A34A, #15803D); box-shadow: 0 12px 26px -10px rgba(21,128,61,.55); }
.res-inline-toast { position: fixed; right: 22px; bottom: 22px; z-index: 15000; max-width: min(360px, calc(100vw - 32px)); border-radius: 14px; border: 1px solid transparent; padding: 12px 14px; box-shadow: 0 18px 42px rgba(24, 32, 48, .18); font-size: .82rem; font-weight: 850; line-height: 1.42; opacity: 0; transform: translateY(10px); pointer-events: none; transition: opacity .18s ease, transform .18s ease; }
.res-inline-toast.is-visible { opacity: 1; transform: translateY(0); }
.res-inline-toast.is-info { border-color: #BFD4F5; background: #F0F6FF; color: #255AA7; }
.res-inline-toast.is-warning { border-color: #F4D38E; background: #FFF8E8; color: #9A5F10; }
.res-inline-toast.is-error { border-color: #FECACA; background: #FEF2F2; color: #B42318; }
.res-inline-toast.is-success { border-color: #BFE9D3; background: #F0FBF5; color: #15835A; }

.swal2-popup.res-swal-checkout {
    border-radius: 20px !important;
    border: 1px solid color-mix(in srgb, #F97316 18%, #E7DDD1) !important;
    box-shadow: 0 32px 80px -42px rgba(15, 23, 42, .68) !important;
}
.res-checkout-confirm {
    text-align: left;
    display: grid;
    gap: 12px;
}
.res-checkout-confirm__lead {
    margin: 0;
    color: #475467;
    font-size: .92rem;
    line-height: 1.45;
}
.res-checkout-confirm__note {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    margin: 0;
    padding: 11px 12px;
    border: 1px solid #FED7AA;
    border-radius: 14px;
    background: #FFF7ED;
    color: #9A3412;
    font-size: .82rem;
    font-weight: 750;
    line-height: 1.4;
}
.res-swal-checkout-confirm,
.res-swal-checkout-cancel {
    min-height: 42px !important;
    border-radius: 12px !important;
    font-weight: 900 !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
}

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
.res-ci-prompt { margin: -2px 0 12px; color: #687386; font-size: .78rem; font-weight: 650; line-height: 1.45; }
.res-ci-breakdown {
    display: grid;
    gap: 10px;
    margin: 0 0 14px;
    padding: 13px;
    border: 1px solid color-mix(in srgb, var(--res-brand) 12%, #DCE2EA);
    border-radius: 16px;
    background:
        linear-gradient(145deg, color-mix(in srgb, var(--res-brand) 4%, #FFFFFF), #fff);
}
.res-ci-breakdown[hidden] { display: none; }
.res-ci-breakdown-head {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.res-ci-breakdown-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: inline-grid;
    place-items: center;
    flex: none;
    color: color-mix(in srgb, var(--res-brand) 88%, #263247);
    background: color-mix(in srgb, var(--res-brand) 8%, #FFFFFF);
    border: 1px solid color-mix(in srgb, var(--res-brand) 14%, #DCE2EA);
}
.res-ci-breakdown-title {
    margin: 0;
    color: var(--res-brand-2);
    font-size: .86rem;
    font-weight: 950;
    line-height: 1.2;
}
.res-ci-breakdown-sub {
    margin: 2px 0 0;
    color: #687386;
    font-size: .73rem;
    font-weight: 700;
    line-height: 1.35;
}
.res-ci-breakdown-lines {
    display: grid;
    gap: 5px;
    padding-top: 8px;
    border-top: 1px dashed color-mix(in srgb, var(--res-brand) 13%, #DCE2EA);
}
.res-ci-breakdown-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #667085;
    font-size: .78rem;
    font-weight: 750;
}
.res-ci-breakdown-line strong {
    color: var(--res-brand-2);
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.res-ci-breakdown-line.is-discount strong,
.res-ci-breakdown-line.is-paid strong { color: #148653; }
.res-ci-breakdown-line.is-due {
    margin-top: 4px;
    padding-top: 8px;
    border-top: 1px solid color-mix(in srgb, var(--res-brand) 10%, #E7DDD1);
    color: var(--res-brand-2);
    font-weight: 900;
}
.res-ci-breakdown-line.is-due strong {
    color: #DC2626;
    font-size: .92rem;
}
/* Atajos de pago: minimalistas, uniformes y responsivos (auto-fit, sin desbordes).
   Superficie neutra; el color semantico vive solo en el chip del icono. */
.res-ci-shortcuts {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    margin-bottom: 12px;
}
/* El 5.º atajo (numero impar) ocupa el ancho para no dejar una celda vacia. */
.res-ci-shortcuts .res-ci-shortcut:last-child { grid-column: 1 / -1; }
.res-ci-shortcut {
    --res-shortcut-color: var(--res-brand);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;
    min-width: 0;
    min-height: 46px;
    padding: 8px 12px;
    border: 1px solid color-mix(in srgb, var(--res-brand) 12%, #E4E7EC);
    border-radius: 12px;
    background: #fff;
    color: var(--res-brand-2);
    font-size: .8rem;
    font-weight: 700;
    line-height: 1.2;
    text-align: center;
    cursor: pointer;
    transition: border-color .16s ease, background .16s ease, transform .16s ease, box-shadow .16s ease;
}
.res-ci-shortcut > i {
    flex: none;
    width: 26px;
    height: 26px;
    display: inline-grid;
    place-items: center;
    border-radius: 8px;
    font-size: .8rem;
    color: var(--res-shortcut-color);
    background: color-mix(in srgb, var(--res-shortcut-color) 11%, #fff);
}
.res-ci-shortcut:hover,
.res-ci-shortcut:focus-visible {
    border-color: color-mix(in srgb, var(--res-shortcut-color) 42%, #E4E7EC);
    background: color-mix(in srgb, var(--res-shortcut-color) 4%, #fff);
    transform: translateY(-1px);
    box-shadow: 0 8px 18px -14px color-mix(in srgb, var(--res-shortcut-color) 55%, transparent);
    outline: none;
}
.res-ci-shortcut--cash { --res-shortcut-color: #148653; }
.res-ci-shortcut--card { --res-shortcut-color: #2563EB; }
.res-ci-shortcut--transfer { --res-shortcut-color: #7C3AED; }
.res-ci-shortcut--split { --res-shortcut-color: var(--res-brand); }
.res-ci-shortcut--cash-transfer { --res-shortcut-color: var(--res-brand); }
.res-ci-pending-option {
    display: grid;
    grid-template-columns: auto auto minmax(0, 1fr);
    align-items: center;
    gap: 11px;
    margin: 0 0 12px;
    padding: 12px;
    border: 1px solid color-mix(in srgb, #D97706 25%, #E7DDD1);
    border-radius: 15px;
    background: #FFF8EA;
    color: #7C4A12;
    cursor: pointer;
    transition: border-color .16s ease, background .16s ease, box-shadow .16s ease, transform .16s ease;
}
.res-ci-pending-option:hover,
.res-ci-pending-option:focus-within {
    border-color: color-mix(in srgb, #D97706 48%, #E7DDD1);
    background: #FFF5DC;
    box-shadow: 0 14px 28px -26px rgba(217,119,6,.75);
    transform: translateY(-1px);
}
.res-ci-pending-option input { width: 18px; height: 18px; accent-color: #D97706; }
.res-ci-pending-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: rgba(217,119,6,.12);
    color: #B45309;
}
.res-ci-pending-option strong {
    display: block;
    color: #6B3B08;
    font-size: .84rem;
    font-weight: 950;
}
.res-ci-pending-option small {
    display: block;
    margin-top: 2px;
    color: #8A5A18;
    font-size: .73rem;
    font-weight: 720;
    line-height: 1.35;
}
.res-ci-pending-preview {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    margin: -2px 0 12px;
}
.res-ci-pending-preview[hidden] { display: none !important; }
.res-ci-pending-preview > div {
    min-height: 58px;
    padding: 10px 12px;
    border: 1px solid color-mix(in srgb, #D97706 24%, #E7DDD1);
    border-radius: 12px;
    background: #FFFDF7;
}
.res-ci-pending-preview span {
    display: block;
    margin-bottom: 4px;
    color: #7C4A12;
    font-size: .72rem;
    font-weight: 820;
}
.res-ci-pending-preview strong {
    color: #263247;
    font-size: 1rem;
    font-weight: 950;
}
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
.res-invoice-result {
    margin-top: 10px;
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 10px 12px;
    border: 1px solid color-mix(in srgb, var(--res-brand) 12%, #DCE2EA);
    border-radius: 12px;
    background: color-mix(in srgb, var(--res-brand) 4%, #FFFFFF);
    color: #566176;
    font-size: .76rem;
    font-weight: 750;
    line-height: 1.4;
}
.res-invoice-result i { margin-top: 2px; color: var(--res-brand); }
.res-invoice-result.is-client { border-color: #BFDBFE; background: #EFF6FF; color: #1D4ED8; }
.res-invoice-result.is-internal { border-color: #FED7AA; background: #FFF7ED; color: #9A3412; }
.res-invoice-result.is-none { border-color: #D1D5DB; background: #F9FAFB; color: #4B5563; }
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
    /* En movil el encabezado se apila sobre el formulario: la card pasa a flex
       column y el form/body llenan el alto disponible (sin su propio max-height),
       para que el footer sticky nunca quede recortado bajo el viewport. */
    .res-checkin-card {
        grid-template-columns: 1fr;
        display: flex;
        flex-direction: column;
    }
    .res-checkin-form {
        max-height: none;
        flex: 1 1 auto;
        min-height: 0;
    }
    .res-checkin-body {
        flex: 1 1 auto;
        min-height: 0;
    }
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
    .res-topbar { flex-direction: column; }
    .res-title-lockup,
    .res-actions { width: 100%; }
    .res-title-lockup { flex: 0 1 auto; }
    .res-actions { flex: 0 1 auto; }
    .res-actions { min-width: 0; justify-content: flex-start; }
    .res-search { flex: 1 1 min(100%, 520px); min-width: min(100%, 520px); }
}
@media (max-width: 1023px) {
    .res-table-shell { display: none; }
    .res-mobile-list { display: grid; gap: 12px; }
}
@media (max-width: 720px) {
    .res-shell { padding: 18px 12px 28px; }
    .res-title-lockup { grid-template-columns: 44px minmax(0, 1fr); column-gap: 12px; align-items: start; }
    .res-hero-icon { width: 44px; height: 44px; flex-basis: 44px; border-radius: 14px; }
    .res-title { font-size: 2rem; }
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
    .res-checkin-modal { padding: 8px 8px max(8px, env(safe-area-inset-bottom, 8px)); align-items: flex-end; }
    .res-checkin-card { width: 100%; border-radius: 20px 20px 0 0; max-height: 94dvh; }
    .res-checkin-title h3 { font-size: 1rem; }
    .res-checkin-title p { font-size: .72rem; }
    .res-card-type { grid-template-columns: 1fr; }
    .res-checkin-actions { flex-direction: column; }
    .res-ci-cancel,
    .res-ci-confirm { width: 100%; }
}

/* La flecha del desglose solo aparece en el modo por pasos (movil). */
.res-ci-breakdown-chevron { display: none; }

/* ===== Check-in movil por pasos (no afecta escritorio ni tablet) ===== */
@media (max-width: 640px) {
    .res-checkin-card.ci-stepped .ci-step-hidden { display: none !important; }

    /* Desglose "por que se cobra": colapsable para aligerar el paso de cobro */
    .res-checkin-card.ci-stepped .res-ci-breakdown-head { cursor: pointer; }
    .res-checkin-card.ci-stepped .res-ci-breakdown-chevron {
        display: inline-block;
        align-self: center;
        margin-left: auto;
        color: color-mix(in srgb, var(--res-brand) 55%, #94A3B8);
        transition: transform .18s ease;
    }
    .res-checkin-card.ci-stepped .res-ci-breakdown.ci-collapsed .res-ci-breakdown-lines { display: none; }
    .res-checkin-card.ci-stepped .res-ci-breakdown.ci-collapsed .res-ci-breakdown-chevron { transform: rotate(-90deg); }

    /* Encabezado: subtitulo fuera, indicador de paso dentro */
    .res-checkin-card.ci-stepped .res-checkin-sub-desktop { display: none; }
    .ci-step-pill {
        display: inline-flex;
        align-items: center;
        width: max-content;
        margin-top: 8px;
        padding: 3px 11px;
        border-radius: 999px;
        background: rgba(255,255,255,.16);
        border: 1px solid rgba(255,255,255,.26);
        color: #fff;
        font-size: .7rem;
        font-weight: 850;
        letter-spacing: .03em;
    }
    .ci-step-pill[hidden] { display: none; }

    /* Densidad mas comoda: total/hora y atajos en una sola columna (etiqueta en una linea) */
    .res-checkin-card.ci-stepped .res-checkin-body { gap: 12px; }
    .res-checkin-card.ci-stepped .res-checkin-summary { grid-template-columns: 1fr; gap: 10px; }
    .res-checkin-card.ci-stepped .res-ci-shortcuts { grid-template-columns: 1fr; }

    /* Footer compacto en fila (solo 2 botones visibles a la vez) */
    .res-checkin-card.ci-stepped .res-checkin-actions { flex-direction: row; }
    .res-checkin-card.ci-stepped .res-ci-cancel,
    .res-checkin-card.ci-stepped .res-ci-confirm,
    .res-checkin-card.ci-stepped .res-ci-back,
    .res-checkin-card.ci-stepped .res-ci-next { width: auto; }

    .res-ci-back,
    .res-ci-next {
        min-height: 46px;
        border-radius: 13px;
        padding: 0 16px;
        font-weight: 900;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: transform .16s ease;
    }
    .res-ci-back {
        flex: .9;
        border: 1px solid color-mix(in srgb, var(--res-brand) 14%, #DCE2EA);
        color: var(--res-brand-2);
        background: #fff;
    }
    .res-ci-next {
        flex: 1.3;
        border: 0;
        color: #fff;
        background: linear-gradient(135deg, var(--res-brand), var(--res-brand-2));
        box-shadow: 0 15px 30px -18px color-mix(in srgb, var(--res-brand) 70%, transparent);
    }
    .res-ci-back:hover,
    .res-ci-next:hover { transform: translateY(-1px); }
}

/* Nueva reservacion: selector de cliente + hora, consistente con habitaciones. */
.swal2-container.res-swal-reservation-container.swal2-backdrop-show,
.swal2-container.res-swal-reservation-container.swal2-noanimation {
    background:
        radial-gradient(740px 320px at 50% -12%, color-mix(in srgb, var(--res-accent) 14%, transparent), transparent 62%),
        rgba(15, 23, 42, .36) !important;
    backdrop-filter: blur(5px) !important;
}
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival {
    --hb-modal-primary: var(--brand-primary, #1B2746);
    --hb-modal-secondary: var(--brand-secondary, #0F172A);
    --hb-modal-accent: var(--brand-accent, #BD9441);
    --hb-modal-ink: #172033;
    --hb-modal-soft-ink: #475569;
    --hb-modal-muted: #6B7280;
    --hb-modal-line: color-mix(in srgb, var(--hb-modal-primary) 12%, #E6DCCB);
    --hb-modal-danger: #D43F3A;
    --hb-modal-danger-dark: #A92B28;
    --hb-modal-coral: #D76B4F;
    --hb-modal-sage: #4E9478;
    --hb-modal-blue: #497AA8;
    --hb-modal-amber: #C79235;
    --hb-modal-action: var(--hb-modal-blue);
    --hb-modal-action-text: #FFFFFF;
    width: min(720px, calc(100vw - 32px)) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-accent) 18%, #E8DFD1) !important;
    border-radius: 18px !important;
    background:
        radial-gradient(520px 180px at 105% -8%, color-mix(in srgb, var(--hb-modal-coral) 15%, transparent), transparent 72%),
        radial-gradient(420px 150px at -8% 0%, color-mix(in srgb, var(--hb-modal-sage) 13%, transparent), transparent 74%),
        #fff !important;
    color: var(--hb-modal-ink) !important;
    box-shadow:
        0 34px 78px -46px rgba(15, 23, 42, .62),
        0 1px 0 rgba(255, 255, 255, .96) inset !important;
    animation: resReserveModalIn .18s cubic-bezier(.22, 1, .36, 1) both;
}
.swal2-popup.hb-swal-arrival {
    width: min(560px, calc(100vw - 32px)) !important;
}
.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before {
    content: "";
    display: block;
    height: 7px;
    background: linear-gradient(90deg, var(--hb-modal-danger), var(--hb-modal-coral) 34%, var(--hb-modal-amber) 66%, var(--hb-modal-sage));
}
.hb-swal .swal2-title:empty {
    display: none !important;
}
.hb-swal .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}
.hb-swal .swal2-close {
    position: absolute !important;
    top: 14px !important;
    right: 14px !important;
    z-index: 30 !important;
    width: 36px !important;
    height: 36px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-danger) 18%, #fff) !important;
    border-radius: 999px !important;
    background: linear-gradient(180deg, var(--hb-modal-danger), var(--hb-modal-danger-dark)) !important;
    color: #fff !important;
    font-size: 1.15rem !important;
    cursor: pointer !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--hb-modal-danger) 72%, transparent) !important;
    transition: transform .16s ease, background .16s ease, box-shadow .16s ease;
}
.hb-swal .swal2-close:hover,
.hb-swal .swal2-close:focus-visible {
    transform: translateY(-1px) scale(1.03);
    background: linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-danger) 92%, #fff), var(--hb-modal-danger-dark)) !important;
    outline: 3px solid color-mix(in srgb, var(--hb-modal-danger) 18%, transparent) !important;
}
.hb-client-choice,
.hb-reservation-step {
    width: 100%;
    text-align: left;
}
.hb-client-choice__head,
.hb-arrival-head {
    position: relative;
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    padding: 18px 20px 16px;
    border-bottom: 1px solid color-mix(in srgb, var(--hb-modal-accent) 16%, var(--hb-modal-line));
    background:
        radial-gradient(420px 160px at 92% -10%, color-mix(in srgb, var(--hb-modal-coral) 12%, transparent), transparent 70%),
        radial-gradient(320px 140px at 0% 0%, color-mix(in srgb, var(--hb-modal-sage) 11%, transparent), transparent 74%),
        linear-gradient(180deg, #fff, color-mix(in srgb, var(--hb-modal-amber) 5%, #fff));
}
.hb-client-choice__head::after,
.hb-arrival-head::after {
    content: "";
    position: absolute;
    left: 20px;
    right: 20px;
    bottom: -1px;
    height: 2px;
    border-radius: 999px;
    background: linear-gradient(90deg, color-mix(in srgb, var(--hb-modal-coral) 76%, transparent), color-mix(in srgb, var(--hb-modal-amber) 58%, transparent), color-mix(in srgb, var(--hb-modal-sage) 70%, transparent));
}
.hb-client-choice__mark,
.hb-arrival-mark {
    display: grid;
    place-items: center;
    width: 38px;
    height: 38px;
    border-radius: 12px;
    color: #fff !important;
    box-shadow: 0 14px 24px -18px rgba(15, 23, 42, .44);
}
.hb-client-choice__mark {
    background: linear-gradient(135deg, var(--hb-modal-coral), color-mix(in srgb, var(--hb-modal-danger) 64%, var(--hb-modal-coral))) !important;
}
.hb-arrival-mark {
    background: linear-gradient(135deg, var(--hb-modal-blue), color-mix(in srgb, var(--hb-modal-sage) 74%, var(--hb-modal-blue))) !important;
}
.hb-client-choice__eyebrow {
    display: block;
    margin: 0 0 4px;
    color: color-mix(in srgb, var(--hb-modal-blue) 68%, var(--hb-modal-soft-ink)) !important;
    font-size: .68rem;
    font-weight: 850;
    letter-spacing: 0;
    line-height: 1.1;
    text-transform: uppercase;
}
.hb-client-choice h3,
.hb-arrival-head h3 {
    margin: 0;
    color: var(--hb-modal-ink) !important;
    font-size: 1.15rem;
    font-weight: 850;
    letter-spacing: 0;
    line-height: 1.12;
}
.hb-client-choice__head p,
.hb-arrival-head p {
    max-width: 52ch;
    margin: 6px 0 0;
    color: var(--hb-modal-muted) !important;
    font-size: .84rem;
    font-weight: 500;
    line-height: 1.45;
}
.hb-client-choice__options {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    padding: 16px 18px 18px;
}
.hb-client-option {
    --hb-modal-action: var(--hb-modal-blue);
    position: relative;
    display: grid !important;
    grid-template-rows: auto 1fr auto;
    gap: 12px;
    min-height: 156px;
    padding: 14px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 20%, var(--hb-modal-line)) !important;
    border-radius: 16px !important;
    background:
        radial-gradient(240px 120px at 100% 0%, color-mix(in srgb, var(--hb-modal-action) 8%, transparent), transparent 72%),
        #fff !important;
    color: var(--hb-modal-ink) !important;
    box-shadow: 0 1px 0 rgba(255,255,255,.9) inset, 0 16px 32px -30px rgba(15,23,42,.44) !important;
    text-align: left !important;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}
.hb-client-option::before {
    content: "";
    position: absolute;
    inset: 12px auto 12px 0;
    width: 3px;
    border-radius: 0 999px 999px 0;
    background: color-mix(in srgb, var(--hb-modal-action) 68%, #D8C8A8);
}
.hb-client-option--new,
.hb-reservation-pill--new {
    --hb-modal-action: var(--hb-modal-coral);
}
.hb-client-option--existing,
.hb-reservation-pill--existing {
    --hb-modal-action: var(--hb-modal-sage);
}
.hb-client-option:hover,
.hb-client-option:focus-visible {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--hb-modal-action) 34%, #D9CDBA) !important;
    background:
        radial-gradient(260px 130px at 100% 0%, color-mix(in srgb, var(--hb-modal-action) 12%, transparent), transparent 72%),
        #fff !important;
    box-shadow: 0 20px 40px -30px color-mix(in srgb, var(--hb-modal-action) 48%, transparent) !important;
    outline: none;
}
.hb-client-option__top,
.hb-client-option__body,
.hb-client-option__cta {
    position: relative;
    z-index: 1;
}
.hb-client-option__top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.hb-client-option__icon {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 12px;
    background: color-mix(in srgb, var(--hb-modal-action) 13%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 82%, var(--hb-modal-ink)) !important;
}
.hb-client-option__tag,
.hb-reservation-pill {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 16%, #ECE5D8);
    border-radius: 999px;
    background: color-mix(in srgb, var(--hb-modal-action) 7%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-soft-ink)) !important;
    font-size: .67rem;
    font-weight: 800;
    line-height: 1;
}
.hb-client-option__body {
    display: grid;
    gap: 6px;
}
.hb-client-option__body strong {
    color: var(--hb-modal-ink) !important;
    font-size: .98rem;
    font-weight: 850;
    line-height: 1.15;
}
.hb-client-option__body span {
    color: var(--hb-modal-muted) !important;
    font-size: .82rem;
    font-weight: 500;
    line-height: 1.38;
}
.hb-client-option__cta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 34px;
    padding-top: 10px;
    border-top: 1px solid color-mix(in srgb, var(--hb-modal-action) 10%, #ECE5D8) !important;
    color: var(--hb-modal-action) !important;
    font-size: .78rem;
    font-weight: 850;
}
.hb-arrival-context {
    display: grid;
    gap: 12px;
    padding: 15px 18px 18px;
}
.hb-reservation-pill {
    justify-content: flex-start;
    gap: 8px;
    width: fit-content;
    min-height: 32px;
    margin: 0 !important;
    padding: 0 11px;
    font-size: .74rem;
}
.hb-reservation-summary {
    display: grid !important;
    gap: 12px;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
    box-shadow: none !important;
}
.hb-reservation-dates {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
}
.hb-date-row,
.hb-arrival-card {
    background:
        radial-gradient(220px 110px at 100% 0%, color-mix(in srgb, var(--hb-modal-action, var(--hb-modal-blue)) 8%, transparent), transparent 72%),
        #fff !important;
}
.hb-date-row {
    display: grid;
    gap: 5px;
    min-height: 68px;
    padding: 11px 12px !important;
    border: 1px solid var(--hb-modal-line) !important;
    border-radius: 14px;
}
.hb-date-row span {
    color: var(--hb-modal-muted) !important;
    font-size: .68rem;
    font-weight: 820;
    letter-spacing: 0;
    line-height: 1;
    text-transform: uppercase;
}
.hb-date-row strong {
    color: var(--hb-modal-ink) !important;
    font-size: .93rem;
    font-weight: 850;
    line-height: 1.15;
}
.hb-arrival-card {
    --hb-modal-action: var(--hb-modal-blue);
    display: grid;
    gap: 10px;
    padding: 14px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-blue) 22%, var(--hb-modal-line)) !important;
    border-radius: 16px;
}
.hb-arrival-label {
    margin: 0 !important;
    color: var(--hb-modal-ink) !important;
    font-size: .9rem;
    font-weight: 850;
    line-height: 1.15;
}
.hb-arrival-copy {
    margin: -5px 0 0 !important;
    color: var(--hb-modal-muted) !important;
    font-size: .79rem;
    font-weight: 500;
    line-height: 1.35;
}
.hb-arrival-control {
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 8px;
    align-items: center;
}
.hb-arrival-input {
    width: 100%;
    height: 42px;
    padding: 0 12px !important;
    border: 1px solid color-mix(in srgb, var(--hb-modal-blue) 18%, #D8CDBB) !important;
    border-radius: 12px !important;
    background: #FFFFFF !important;
    color: var(--hb-modal-ink) !important;
    font-size: .96rem !important;
    font-weight: 780;
    outline: none;
    box-shadow: none !important;
}
.hb-arrival-input:focus {
    border-color: color-mix(in srgb, var(--hb-modal-blue) 46%, #D8CDBB) !important;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--hb-modal-blue) 12%, transparent) !important;
}
.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm {
    min-height: 42px !important;
    border: 0 !important;
    border-radius: 12px !important;
    background: linear-gradient(135deg, var(--hb-modal-blue), color-mix(in srgb, var(--hb-modal-sage) 60%, var(--hb-modal-blue))) !important;
    color: var(--hb-modal-action-text) !important;
    box-shadow: 0 14px 24px -18px color-mix(in srgb, var(--hb-modal-blue) 76%, transparent) !important;
    font-weight: 850 !important;
}
.hb-arrival-now {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 13px !important;
    font-size: .82rem;
}
.hb-arrival-later {
    width: 100%;
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 10px;
    align-items: center;
    padding: 10px 11px;
    border: 1px solid color-mix(in srgb, var(--hb-modal-amber) 30%, var(--hb-modal-line)) !important;
    border-radius: 13px;
    background:
        radial-gradient(200px 100px at 100% 0%, color-mix(in srgb, var(--hb-modal-amber) 14%, transparent), transparent 72%),
        color-mix(in srgb, var(--hb-modal-amber) 4%, #fff) !important;
    color: var(--hb-modal-ink);
    text-align: left;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
}
.hb-arrival-later__icon {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 11px;
    background: color-mix(in srgb, var(--hb-modal-amber) 13%, #fff);
    color: color-mix(in srgb, var(--hb-modal-amber) 76%, var(--hb-modal-soft-ink));
}
.hb-arrival-later strong,
.hb-arrival-later small {
    display: block;
}
.hb-arrival-later strong {
    font-size: .84rem;
    font-weight: 900;
    line-height: 1.15;
}
.hb-arrival-later small {
    margin-top: 2px;
    color: var(--hb-modal-muted);
    font-size: .74rem;
    font-weight: 650;
    line-height: 1.25;
}
.hb-arrival-later:hover,
.hb-arrival-later:focus-visible,
.hb-arrival-now:hover,
.hb-arrival-now:focus-visible,
.hb-swal-arrival .hb-swal-confirm:hover,
.hb-swal-arrival .hb-swal-confirm:focus-visible {
    transform: translateY(-1px);
    outline: none;
}
.hb-swal .swal2-actions {
    width: 100% !important;
    gap: 8px !important;
    margin: 0 !important;
    padding: 0 18px 18px !important;
}
.swal2-popup.hb-swal-arrival .swal2-actions {
    display: grid !important;
    grid-template-columns: minmax(0, .85fr) minmax(0, 1.15fr);
}
.swal2-popup.hb-swal-client .swal2-actions {
    justify-content: stretch !important;
}
.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel,
.hb-swal-arrival .hb-swal-confirm {
    min-height: 42px !important;
    margin: 0 !important;
    padding: 0 14px !important;
    border-radius: 12px !important;
    font-size: .84rem !important;
    letter-spacing: 0 !important;
}
.hb-swal-arrival .hb-swal-cancel {
    order: 1;
}
.hb-swal-arrival .hb-swal-confirm {
    order: 2;
}
.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel {
    border: 1px solid color-mix(in srgb, var(--hb-modal-danger) 30%, var(--hb-modal-line)) !important;
    background: linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-danger) 9%, #fff), color-mix(in srgb, var(--hb-modal-danger) 5%, #fff)) !important;
    color: color-mix(in srgb, var(--hb-modal-danger-dark) 82%, var(--hb-modal-ink)) !important;
    box-shadow: 0 12px 24px -22px color-mix(in srgb, var(--hb-modal-danger) 46%, transparent) !important;
}
.hb-swal-client .hb-swal-cancel {
    width: 100% !important;
}
.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--hb-modal-danger) 46%, var(--hb-modal-line)) !important;
    background: linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-danger) 16%, #fff), color-mix(in srgb, var(--hb-modal-danger) 8%, #fff)) !important;
    outline: none;
}
.swal2-popup.hb-swal-client.swal2-hide,
.swal2-popup.hb-swal-arrival.swal2-hide {
    animation: resReserveModalOut .13s ease-in both !important;
}
.swal2-container.res-swal-reservation-container.swal2-backdrop-hide {
    background: rgba(15, 23, 42, 0) !important;
    backdrop-filter: blur(0) !important;
    transition: background .13s ease, backdrop-filter .13s ease !important;
}

/* Propuesta sobria: menos color, mas blanco calido y acentos silenciosos. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival {
    --hb-modal-surface: #fffdfa;
    --hb-modal-paper: #ffffff;
    --hb-modal-ink: #202a32;
    --hb-modal-soft-ink: #4d5963;
    --hb-modal-muted: #6f7882;
    --hb-modal-line: color-mix(in srgb, var(--brand-primary, #1B2746) 8%, #e7ded2);
    --hb-modal-clay: #9b6a5f;
    --hb-modal-clay-deep: #745048;
    --hb-modal-moss: #657a70;
    --hb-modal-moss-deep: #4f6259;
    --hb-modal-blue: #65798d;
    --hb-modal-blue-deep: #4e6173;
    --hb-modal-amber: #a88c5a;
    --hb-modal-action: var(--hb-modal-blue);
    --hb-modal-action-text: #ffffff;
    background:
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(255,253,249,.98)),
        var(--hb-modal-surface) !important;
    border-color: color-mix(in srgb, var(--brand-primary, #1B2746) 9%, #e7ded2) !important;
    box-shadow:
        0 30px 70px -46px rgba(20, 28, 36, .56),
        0 1px 0 rgba(255,255,255,.95) inset !important;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before {
    height: 4px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--brand-primary, #1B2746) 24%, #e8ded0),
        color-mix(in srgb, var(--brand-accent, #BD9441) 24%, #eee6da)) !important;
}

.hb-swal .swal2-close {
    border-color: color-mix(in srgb, var(--hb-modal-clay) 22%, #e9ded3) !important;
    background: rgba(255,255,255,.94) !important;
    color: var(--hb-modal-clay-deep) !important;
    box-shadow: 0 12px 24px -20px rgba(116, 80, 72, .42) !important;
}

.hb-swal .swal2-close:hover,
.hb-swal .swal2-close:focus-visible {
    background: color-mix(in srgb, var(--hb-modal-clay) 8%, #fff) !important;
    color: var(--hb-modal-clay-deep) !important;
    outline: 3px solid color-mix(in srgb, var(--hb-modal-clay) 14%, transparent) !important;
}

.hb-client-choice__head,
.hb-arrival-head {
    border-bottom-color: var(--hb-modal-line) !important;
    background:
        linear-gradient(180deg, #ffffff, color-mix(in srgb, var(--brand-accent, #BD9441) 4%, #fffdf8)) !important;
}

.hb-client-choice__head::after,
.hb-arrival-head::after {
    height: 1px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--brand-primary, #1B2746) 18%, transparent),
        color-mix(in srgb, var(--brand-accent, #BD9441) 24%, transparent)) !important;
}

.hb-client-choice__mark,
.hb-arrival-mark {
    border: 1px solid color-mix(in srgb, var(--hb-modal-action) 18%, var(--hb-modal-line));
    background: color-mix(in srgb, var(--hb-modal-action) 9%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 76%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-client-choice__mark {
    --hb-modal-action: var(--hb-modal-clay);
}

.hb-arrival-mark {
    --hb-modal-action: var(--hb-modal-blue);
}

.hb-client-choice__eyebrow {
    color: color-mix(in srgb, var(--brand-primary, #1B2746) 48%, var(--hb-modal-muted)) !important;
}

.hb-client-option--new,
.hb-reservation-pill--new {
    --hb-modal-action: var(--hb-modal-clay);
}

.hb-client-option--existing,
.hb-reservation-pill--existing {
    --hb-modal-action: var(--hb-modal-moss);
}

.hb-client-option,
.hb-date-row,
.hb-arrival-card {
    background: var(--hb-modal-paper) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action, var(--hb-modal-blue)) 14%, var(--hb-modal-line)) !important;
    box-shadow:
        0 1px 0 rgba(255,255,255,.9) inset,
        0 16px 34px -34px rgba(24, 32, 40, .38) !important;
}

.hb-client-option::before {
    background: color-mix(in srgb, var(--hb-modal-action) 44%, #d9cfc2) !important;
    opacity: .72;
}

.hb-client-option:hover,
.hb-client-option:focus-visible {
    background: color-mix(in srgb, var(--hb-modal-action) 3%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 24%, var(--hb-modal-line)) !important;
    box-shadow: 0 20px 40px -34px color-mix(in srgb, var(--hb-modal-action) 28%, transparent) !important;
}

.hb-client-option__icon,
.hb-client-option__tag,
.hb-reservation-pill {
    background: color-mix(in srgb, var(--hb-modal-action) 6%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 12%, var(--hb-modal-line)) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 68%, var(--hb-modal-soft-ink)) !important;
}

.hb-arrival-card {
    --hb-modal-action: var(--hb-modal-blue);
}

.hb-arrival-later {
    border-color: color-mix(in srgb, var(--hb-modal-amber) 18%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-amber) 4%, #fff) !important;
}

.hb-arrival-later__icon {
    background: color-mix(in srgb, var(--hb-modal-amber) 8%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-amber) 70%, var(--hb-modal-soft-ink)) !important;
}

.hb-arrival-now,
.hb-swal-arrival .hb-swal-confirm {
    background: linear-gradient(135deg, var(--hb-modal-blue), var(--hb-modal-blue-deep)) !important;
    color: #fff !important;
    box-shadow: 0 14px 24px -20px rgba(78, 97, 115, .5) !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel {
    border-color: color-mix(in srgb, var(--hb-modal-clay) 18%, var(--hb-modal-line)) !important;
    background: #fff !important;
    color: color-mix(in srgb, var(--hb-modal-clay-deep) 70%, var(--hb-modal-ink)) !important;
    box-shadow: none !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible {
    border-color: color-mix(in srgb, var(--hb-modal-clay) 28%, var(--hb-modal-line)) !important;
    background: color-mix(in srgb, var(--hb-modal-clay) 5%, #fff) !important;
}

/* Toques cromaticos moderados: accion clara sin saturar el modal. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival {
    --hb-modal-clay: #aa6958;
    --hb-modal-clay-deep: #7d4b40;
    --hb-modal-moss: #4f806f;
    --hb-modal-moss-deep: #3d6557;
    --hb-modal-blue: #4e7398;
    --hb-modal-blue-deep: #3f5f80;
    --hb-modal-amber: #aa7f3f;
}

.swal2-popup.hb-swal-client::before,
.swal2-popup.hb-swal-arrival::before {
    height: 5px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--hb-modal-clay) 46%, #eadfd4),
        color-mix(in srgb, var(--hb-modal-blue) 38%, #e6edf2),
        color-mix(in srgb, var(--hb-modal-moss) 42%, #e5eee9)) !important;
}

.hb-client-option--new .hb-client-option__icon {
    background: color-mix(in srgb, var(--hb-modal-clay) 16%, #fff) !important;
    color: var(--hb-modal-clay-deep) !important;
}

.hb-client-option--existing .hb-client-option__icon {
    background: color-mix(in srgb, var(--hb-modal-moss) 16%, #fff) !important;
    color: var(--hb-modal-moss-deep) !important;
}

.hb-client-option--new .hb-client-option__cta,
.hb-client-option--existing .hb-client-option__cta {
    min-height: 38px;
    margin-top: 2px;
    padding: 10px 12px 0;
    border-top-color: color-mix(in srgb, var(--hb-modal-action) 16%, var(--hb-modal-line)) !important;
    color: color-mix(in srgb, var(--hb-modal-action) 86%, var(--hb-modal-ink)) !important;
}

.hb-client-option__tag,
.hb-reservation-pill {
    background: color-mix(in srgb, var(--hb-modal-action) 10%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-action) 18%, var(--hb-modal-line)) !important;
}

.hb-arrival-mark {
    background: color-mix(in srgb, var(--hb-modal-blue) 15%, #fff) !important;
    color: var(--hb-modal-blue-deep) !important;
}

.hb-arrival-now {
    background: linear-gradient(135deg, color-mix(in srgb, var(--hb-modal-blue) 92%, #fff), var(--hb-modal-blue-deep)) !important;
}

.hb-swal-arrival .hb-swal-confirm {
    background: linear-gradient(135deg, color-mix(in srgb, var(--brand-primary, #1B2746) 62%, var(--hb-modal-blue)), var(--hb-modal-blue-deep)) !important;
}

.hb-arrival-later {
    border-color: color-mix(in srgb, var(--hb-modal-amber) 32%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-amber) 8%, #fff), #fff) !important;
}

.hb-arrival-later__icon {
    background: color-mix(in srgb, var(--hb-modal-amber) 15%, #fff) !important;
    color: color-mix(in srgb, var(--hb-modal-amber) 84%, var(--hb-modal-ink)) !important;
}

/* Estados de color claros: verde registrado, verde suave despues, cancelar rojo bajo. */
.swal2-popup.hb-swal-client,
.swal2-popup.hb-swal-arrival {
    --hb-modal-green: #2f8f62;
    --hb-modal-green-deep: #216b4a;
    --hb-modal-new-strong: #c46349;
    --hb-modal-new-deep: #934634;
    --hb-modal-cancel: #b95b57;
    --hb-modal-cancel-deep: #8f403d;
}

.hb-client-option--existing:hover,
.hb-client-option--existing:focus-visible {
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-green) 15%, #fff), #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-green) 48%, var(--hb-modal-line)) !important;
    box-shadow: 0 22px 42px -32px color-mix(in srgb, var(--hb-modal-green) 55%, transparent) !important;
}

.hb-client-option--existing:hover .hb-client-option__icon,
.hb-client-option--existing:focus-visible .hb-client-option__icon {
    background: linear-gradient(135deg, var(--hb-modal-green), var(--hb-modal-green-deep)) !important;
    color: #fff !important;
}

.hb-client-option--existing:hover .hb-client-option__tag,
.hb-client-option--existing:focus-visible .hb-client-option__tag {
    background: color-mix(in srgb, var(--hb-modal-green) 18%, #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-green) 34%, var(--hb-modal-line)) !important;
    color: var(--hb-modal-green-deep) !important;
}

.hb-client-option--existing:hover .hb-client-option__cta,
.hb-client-option--existing:focus-visible .hb-client-option__cta {
    color: var(--hb-modal-green-deep) !important;
}

.hb-client-option--new:hover,
.hb-client-option--new:focus-visible {
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-new-strong) 13%, #fff), #fff) !important;
    border-color: color-mix(in srgb, var(--hb-modal-new-strong) 42%, var(--hb-modal-line)) !important;
    box-shadow: 0 22px 42px -32px color-mix(in srgb, var(--hb-modal-new-strong) 50%, transparent) !important;
}

.hb-client-option--new:hover .hb-client-option__icon,
.hb-client-option--new:focus-visible .hb-client-option__icon {
    background: linear-gradient(135deg, var(--hb-modal-new-strong), var(--hb-modal-new-deep)) !important;
    color: #fff !important;
}

.hb-arrival-later {
    border-color: color-mix(in srgb, var(--hb-modal-green) 30%, var(--hb-modal-line)) !important;
    background:
        radial-gradient(190px 92px at 100% 0%, color-mix(in srgb, var(--hb-modal-green) 10%, transparent), transparent 72%),
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-green) 7%, #fff), #fff) !important;
}

.hb-arrival-later__icon {
    background: color-mix(in srgb, var(--hb-modal-green) 14%, #fff) !important;
    color: var(--hb-modal-green-deep) !important;
}

.hb-arrival-later:hover,
.hb-arrival-later:focus-visible {
    border-color: color-mix(in srgb, var(--hb-modal-green) 44%, var(--hb-modal-line)) !important;
    box-shadow: 0 14px 26px -24px color-mix(in srgb, var(--hb-modal-green) 45%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel,
.hb-swal-arrival .hb-swal-cancel {
    border-color: color-mix(in srgb, var(--hb-modal-cancel) 30%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-cancel) 12%, #fff), color-mix(in srgb, var(--hb-modal-cancel) 6%, #fff)) !important;
    color: var(--hb-modal-cancel-deep) !important;
    box-shadow: 0 14px 22px -22px color-mix(in srgb, var(--hb-modal-cancel) 54%, transparent) !important;
}

.hb-swal-client .hb-swal-cancel:hover,
.hb-swal-client .hb-swal-cancel:focus-visible,
.hb-swal-arrival .hb-swal-cancel:hover,
.hb-swal-arrival .hb-swal-cancel:focus-visible {
    border-color: color-mix(in srgb, var(--hb-modal-cancel) 44%, var(--hb-modal-line)) !important;
    background:
        linear-gradient(180deg, color-mix(in srgb, var(--hb-modal-cancel) 17%, #fff), color-mix(in srgb, var(--hb-modal-cancel) 9%, #fff)) !important;
    color: var(--hb-modal-cancel-deep) !important;
    box-shadow: 0 16px 26px -22px color-mix(in srgb, var(--hb-modal-cancel) 62%, transparent) !important;
}
@keyframes resReserveModalIn {
    from { opacity: 0; transform: translateY(8px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes resReserveModalOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to { opacity: 0; transform: translateY(8px) scale(.985); }
}
@media (max-width: 640px) {
    .swal2-container.res-swal-reservation-container {
        align-items: flex-end !important;
        padding: 10px !important;
    }
    .swal2-popup.hb-swal-client,
    .swal2-popup.hb-swal-arrival {
        width: 100% !important;
        max-width: none !important;
        border-radius: 18px 18px 12px 12px !important;
    }
    .hb-client-choice__head,
    .hb-arrival-head {
        padding: 18px 16px 14px;
    }
    .hb-client-choice__options,
    .hb-arrival-context {
        padding: 14px;
    }
    .hb-client-choice__options,
    .hb-reservation-dates,
    .swal2-popup.hb-swal-arrival .swal2-actions {
        grid-template-columns: 1fr;
    }
    .hb-client-option {
        min-height: 132px;
    }
    .hb-swal .swal2-actions {
        padding: 0 14px calc(14px + env(safe-area-inset-bottom)) !important;
    }
}

/* Nueva reservacion: wizard visual alineado al index de habitaciones. */
.swal2-container.res-swal-reservation-container.swal2-backdrop-show,
.swal2-container.res-swal-reservation-container.swal2-noanimation {
    background: rgba(8, 13, 20, .58) !important;
    -webkit-backdrop-filter: none !important;
    backdrop-filter: none !important;
}

.swal2-container.res-swal-reservation-container.swal2-backdrop-hide {
    background: rgba(8, 13, 20, 0) !important;
    -webkit-backdrop-filter: none !important;
    backdrop-filter: none !important;
    transition: background .14s ease !important;
}

.swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal {
    width: min(780px, calc(100vw - 32px)) !important;
    padding: 0 !important;
    overflow: hidden !important;
    border: 1px solid color-mix(in srgb, var(--brand-primary, #1B2746) 12%, #E8DFD1) !important;
    border-radius: 24px !important;
    background: #FBFAF7 !important;
    color: #172033 !important;
    box-shadow: 0 34px 78px -42px rgba(8, 13, 20, .70) !important;
    animation: resReserveSheetIn .18s cubic-bezier(.22, 1, .36, 1) both;
}

.swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal.swal2-hide {
    animation: resReserveSheetOut .13s ease-in both !important;
}

.swal2-container.res-swal-reservation-container .swal2-html-container {
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
}

.swal2-container.res-swal-reservation-container .swal2-close {
    top: 20px !important;
    right: 18px !important;
    z-index: 40 !important;
    width: 32px !important;
    height: 32px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 10%, #E8DFD1) !important;
    border-radius: 999px !important;
    background: rgba(255, 255, 255, .64) !important;
    color: color-mix(in srgb, var(--brand-secondary, #0F172A) 48%, #8A93A4) !important;
    font-size: 1.08rem !important;
    line-height: 1 !important;
    box-shadow: none !important;
    opacity: .72 !important;
    transition: opacity .16s ease, transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease !important;
}

.swal2-container.res-swal-reservation-container .swal2-close:hover,
.swal2-container.res-swal-reservation-container .swal2-close:focus-visible {
    transform: translateY(-1px);
    opacity: 1 !important;
    border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 18%, #E8DFD1) !important;
    background: rgba(255, 255, 255, .94) !important;
    color: color-mix(in srgb, var(--brand-secondary, #0F172A) 82%, #111827) !important;
    outline: 3px solid color-mix(in srgb, var(--brand-accent, #BD9441) 10%, transparent) !important;
}

.res-reserve-shell {
    --res-reserve-brand: var(--brand-primary, #1B2746);
    --res-reserve-brand-2: var(--brand-secondary, #0F172A);
    --res-reserve-accent: var(--brand-accent, #BD9441);
    --res-reserve-line: color-mix(in srgb, var(--brand-primary, #1B2746) 10%, #E8DFD1);
    --res-reserve-surface: #FBFAF7;
    --res-reserve-paper: #FFFFFF;
    --res-reserve-ink: #172033;
    --res-reserve-muted: #6B7686;
    --res-reserve-new: color-mix(in srgb, var(--brand-accent, #BD9441) 72%, #B76B52);
    --res-reserve-existing: #20A66B;
    display: grid;
    grid-template-columns: 300px minmax(0, 1fr);
    min-height: 482px;
    text-align: left;
    background: var(--res-reserve-surface);
}

.res-reserve-side {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    display: grid;
    grid-template-rows: auto auto auto auto 1fr;
    align-content: start;
    padding: 26px 26px;
    color: #FFFFFF;
    background:
        radial-gradient(260px 210px at 100% 10%, rgba(255,255,255,.10), transparent 62%),
        linear-gradient(155deg, color-mix(in srgb, var(--res-reserve-brand-2) 92%, #101827), color-mix(in srgb, var(--res-reserve-brand) 78%, #1F2937));
}

.res-reserve-side::after {
    content: '';
    position: absolute;
    top: -34px;
    right: -70px;
    width: 230px;
    height: 230px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--res-reserve-accent) 20%, transparent);
    opacity: .65;
    z-index: -1;
}

.res-reserve-side__eyebrow,
.res-reserve-main__eyebrow {
    display: block;
    margin: 0 0 12px;
    font-size: .70rem;
    font-weight: 900;
    letter-spacing: .18em;
    line-height: 1;
    text-transform: uppercase;
}

.res-reserve-side__eyebrow {
    color: color-mix(in srgb, var(--res-reserve-accent) 70%, #FFFFFF);
}

.res-reserve-side h2 {
    margin: 0;
    max-width: 9ch;
    color: #FFFFFF;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.72rem;
    font-weight: 780;
    line-height: 1.03;
    letter-spacing: 0;
}

.res-reserve-side > p {
    margin: 12px 0 18px;
    max-width: 25ch;
    color: rgba(255,255,255,.78);
    font-size: .86rem;
    font-weight: 650;
    line-height: 1.45;
}

.res-reserve-datebox {
    display: grid;
    gap: 0;
    padding: 8px 14px;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 16px;
    background: rgba(255,255,255,.08);
}

.res-reserve-dateitem {
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    gap: 9px;
    align-items: center;
    min-height: 50px;
}

.res-reserve-dateitem + .res-reserve-dateitem {
    border-top: 1px solid rgba(255,255,255,.12);
}

.res-reserve-dateicon {
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    background: rgba(255,255,255,.12);
    color: color-mix(in srgb, var(--res-reserve-accent) 62%, #FFFFFF);
    font-size: .78rem;
}

.res-reserve-dateitem small {
    display: block;
    color: rgba(255,255,255,.54);
    font-size: .68rem;
    font-weight: 900;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.res-reserve-dateitem strong {
    display: block;
    margin-top: 2px;
    color: #FFFFFF;
    font-size: .88rem;
    font-weight: 900;
}

.res-reserve-steps {
    display: grid;
    gap: 12px;
    margin: 18px 0 0;
    padding: 0;
    list-style: none;
}

.res-reserve-steps li {
    display: grid;
    grid-template-columns: 30px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,.60);
    font-size: .88rem;
    font-weight: 850;
}

.res-reserve-steps li span {
    width: 30px;
    height: 30px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.20);
    color: rgba(255,255,255,.72);
    font-variant-numeric: tabular-nums;
}

.res-reserve-steps li.is-active,
.res-reserve-steps li.is-complete {
    color: #FFFFFF;
}

.res-reserve-steps li.is-active span {
    border-color: color-mix(in srgb, var(--res-reserve-accent) 76%, #FFFFFF);
    background: color-mix(in srgb, var(--res-reserve-accent) 82%, #9B7236);
    color: #FFFFFF;
}

.res-reserve-steps li.is-complete span {
    border-color: #20A66B;
    background: #20A66B;
    color: #FFFFFF;
}

.res-reserve-main {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 482px;
    padding: 32px 24px 0;
    background: #FFFEFB;
}

.res-reserve-main__eyebrow {
    color: #8791A3;
    margin-bottom: 24px;
}

.res-reserve-main h3 {
    margin: 0;
    color: var(--res-reserve-ink);
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.35rem;
    font-weight: 760;
    line-height: 1.18;
}

.res-reserve-main > p {
    max-width: 49ch;
    margin: 7px 0 18px;
    color: var(--res-reserve-muted);
    font-size: .88rem;
    font-weight: 600;
    line-height: 1.45;
}

.res-reserve-choice-list {
    display: grid;
    gap: 10px;
}

.res-reserve-choice {
    --res-choice-color: var(--res-reserve-accent);
    width: 100%;
    min-height: 92px;
    display: grid;
    grid-template-columns: 42px minmax(0, 1fr) 28px;
    align-items: center;
    gap: 14px;
    padding: 16px 18px;
    border: 1px solid var(--res-reserve-line);
    border-radius: 14px;
    background: var(--res-reserve-paper);
    color: var(--res-reserve-ink);
    text-align: left;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.res-reserve-choice--new { --res-choice-color: var(--res-reserve-new); }
.res-reserve-choice--existing { --res-choice-color: var(--res-reserve-existing); }

.res-reserve-choice:hover,
.res-reserve-choice:focus-visible,
.res-reserve-choice.is-selected {
    transform: translateY(-1px);
    border-color: color-mix(in srgb, var(--res-choice-color) 60%, var(--res-reserve-line));
    background: color-mix(in srgb, var(--res-choice-color) 10%, #FFFFFF);
    box-shadow: 0 18px 36px -32px color-mix(in srgb, var(--res-choice-color) 54%, transparent);
    outline: none;
}

.res-reserve-choice__icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    color: color-mix(in srgb, var(--res-choice-color) 78%, var(--res-reserve-ink));
    background: color-mix(in srgb, var(--res-choice-color) 10%, #FFFFFF);
}

.res-reserve-choice__copy {
    display: grid;
    gap: 6px;
    min-width: 0;
}

.res-reserve-choice__copy > span {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}

.res-reserve-choice__copy strong {
    min-width: 0;
    color: var(--res-reserve-ink);
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 1.08rem;
    font-weight: 760;
    line-height: 1.1;
}

.res-reserve-choice__copy em {
    flex: 0 0 auto;
    min-height: 20px;
    display: inline-flex;
    align-items: center;
    padding: 0 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--res-choice-color) 12%, #FFFFFF);
    color: color-mix(in srgb, var(--res-choice-color) 84%, var(--res-reserve-ink));
    font-size: .62rem;
    font-style: normal;
    font-weight: 900;
    line-height: 1;
    white-space: nowrap;
    word-break: keep-all;
    overflow-wrap: normal;
}

.res-reserve-choice__copy small {
    color: var(--res-reserve-muted);
    font-size: .82rem;
    font-weight: 600;
    line-height: 1.35;
}

.res-reserve-choice__check {
    width: 24px;
    height: 24px;
    display: grid;
    place-items: center;
    border-radius: 999px;
    border: 2px solid color-mix(in srgb, var(--res-choice-color) 20%, #DCD4C8);
    color: transparent;
    background: #FFFFFF;
    font-size: .70rem;
}

.res-reserve-choice.is-selected .res-reserve-choice__check {
    border-color: var(--res-choice-color);
    background: var(--res-choice-color);
    color: #FFFFFF;
}

.res-reserve-timechips {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin: 3px 0 14px;
}

.res-reserve-timechip {
    min-height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 0 16px;
    border: 1px solid color-mix(in srgb, var(--res-reserve-accent) 22%, var(--res-reserve-line));
    border-radius: 999px;
    background: #FFFFFF;
    color: #4F5969;
    font-size: .84rem;
    font-weight: 850;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, color .16s ease;
}

.res-reserve-timechip:hover,
.res-reserve-timechip:focus-visible,
.res-reserve-timechip.is-active {
    transform: translateY(-1px);
    border-color: var(--res-reserve-brand-2);
    background: var(--res-reserve-brand-2);
    color: #FFFFFF;
    outline: none;
}

.res-reserve-timefield {
    min-height: 60px;
    display: grid;
    grid-template-columns: 24px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    padding: 0 18px;
    border: 1px solid var(--res-reserve-line);
    border-radius: 12px;
    background: #FFFFFF;
}

.res-reserve-timefield i {
    color: #A8B1BF;
}

.res-reserve-timefield .hb-arrival-input {
    height: 58px !important;
    min-height: 58px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    color: var(--res-reserve-brand-2) !important;
    font-size: 1.28rem !important;
    font-weight: 900 !important;
    box-shadow: none !important;
}

.res-reserve-hint {
    margin: 10px 0 0 !important;
    display: flex;
    align-items: center;
    gap: 7px;
    color: #9AA4B5 !important;
    font-size: .78rem !important;
    font-weight: 650 !important;
}

.res-reserve-validation {
    margin: 10px 0 0;
    padding: 10px 12px;
    border: 1px solid #F3B8B6;
    border-radius: 12px;
    background: #FFF3F2;
    color: #A4423E;
    font-size: .80rem;
    font-weight: 800;
}

.res-reserve-validation.hidden {
    display: none;
}

.res-reserve-footer {
    display: grid;
    grid-template-columns: minmax(150px, .88fr) minmax(190px, 1.22fr);
    gap: 12px;
    margin: auto -24px 0;
    padding: 16px 24px;
    border-top: 1px solid var(--res-reserve-line);
    background: #FFFEFB;
}

.res-reserve-btn {
    min-height: 46px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border-radius: 12px;
    padding: 0 16px;
    font-size: .88rem;
    font-weight: 900;
    cursor: pointer;
    transition: transform .16s ease, border-color .16s ease, background .16s ease, box-shadow .16s ease;
}

.res-reserve-btn:hover,
.res-reserve-btn:focus-visible {
    transform: translateY(-1px);
    outline: none;
}

.res-reserve-btn--ghost {
    border: 1px solid var(--res-reserve-line);
    background: #FFFFFF;
    color: var(--res-reserve-brand-2);
}

.res-reserve-btn--ghost:hover,
.res-reserve-btn--ghost:focus-visible {
    border-color: color-mix(in srgb, var(--res-reserve-accent) 32%, var(--res-reserve-line));
    background: color-mix(in srgb, var(--res-reserve-accent) 5%, #FFFFFF);
}

.res-reserve-btn--primary {
    border: 0;
    background: var(--res-reserve-brand-2);
    color: #FFFFFF;
    box-shadow: 0 16px 28px -22px color-mix(in srgb, var(--res-reserve-brand-2) 70%, transparent);
}

.res-reserve-btn--primary:hover,
.res-reserve-btn--primary:focus-visible {
    background: color-mix(in srgb, var(--res-reserve-brand-2) 86%, var(--res-reserve-brand));
}

@keyframes resReserveSheetIn {
    from { opacity: 0; transform: translateY(10px) scale(.985); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes resReserveSheetOut {
    from { opacity: 1; transform: translateY(0) scale(1); }
    to { opacity: 0; transform: translateY(10px) scale(.985); }
}

@media (max-width: 760px) {
    .swal2-container.res-swal-reservation-container {
        align-items: flex-end !important;
        padding: 10px !important;
    }

    .swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal {
        width: 100% !important;
        max-width: none !important;
        border-radius: 22px 22px 12px 12px !important;
    }

    .res-reserve-shell {
        grid-template-columns: 1fr;
        min-height: 0;
    }

    .res-reserve-side {
        min-height: auto;
        padding: 20px 18px 16px;
    }

    .res-reserve-side h2 {
        max-width: none;
        font-size: 1.42rem;
    }

    .res-reserve-side > p {
        max-width: none;
        margin-bottom: 14px;
    }

    .res-reserve-datebox {
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        padding: 10px;
    }

    .res-reserve-dateitem {
        min-height: 56px;
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .res-reserve-dateitem + .res-reserve-dateitem {
        border-top: 0;
    }

    .res-reserve-dateicon {
        display: none;
    }

    .res-reserve-steps {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    .res-reserve-main {
        min-height: 0;
        padding: 22px 16px 0;
    }

    .res-reserve-choice-list,
    .res-reserve-footer {
        grid-template-columns: 1fr;
    }

    .res-reserve-choice {
        min-height: 88px;
        grid-template-columns: 38px minmax(0, 1fr) 26px;
        padding: 14px;
    }

    .res-reserve-timefield .hb-arrival-input {
        font-size: 1.08rem !important;
    }

    .res-reserve-footer {
        margin-left: -16px;
        margin-right: -16px;
        padding: 14px 16px calc(14px + env(safe-area-inset-bottom));
    }
}

/* Nueva reservacion: paridad visual con el wizard de habitaciones. */
.res-reserve-mobile-dates,
.res-reserve-mobile-intro {
    display: none;
}

.res-reserve-footer--single {
    grid-template-columns: 1fr !important;
}

.swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal .swal2-close {
    display: inline-flex !important;
}

@media (min-width: 761px) {
    .swal2-container.res-swal-reservation-container.swal2-backdrop-show,
    .swal2-container.res-swal-reservation-container.swal2-noanimation {
        background: rgba(17, 24, 39, .42) !important;
        backdrop-filter: none !important;
    }

    .swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal {
        width: min(820px, calc(100vw - 56px)) !important;
        border-radius: 22px !important;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 12%, #E8DED1) !important;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB) !important;
        box-shadow:
            0 34px 90px -48px color-mix(in srgb, var(--brand-secondary, #0F172A) 78%, transparent),
            0 1px 0 rgba(255,255,255,.86) inset !important;
    }

    .res-reserve-shell {
        --res-reserve-brand: var(--brand-primary, #1B2746);
        --res-reserve-brand-2: var(--brand-secondary, #0F172A);
        --res-reserve-accent: var(--brand-accent, #BD9441);
        --res-reserve-warm: color-mix(in srgb, var(--brand-secondary, #0F172A) 72%, var(--brand-accent, #BD9441));
        --res-reserve-line: color-mix(in srgb, var(--brand-secondary, #0F172A) 13%, #E9DFD1);
        --res-reserve-surface: color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB);
        --res-reserve-paper: #FFFEFB;
        --res-reserve-ink: color-mix(in srgb, var(--brand-secondary, #0F172A) 88%, #121826);
        --res-reserve-muted: color-mix(in srgb, var(--brand-secondary, #0F172A) 52%, #8D96A5);
        --res-reserve-new: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
        grid-template-columns: 318px minmax(0, 1fr);
        min-height: 528px;
        background: var(--res-reserve-paper);
    }

    .res-reserve-side {
        padding: 28px 32px;
        grid-template-rows: auto auto auto auto 1fr;
        background:
            radial-gradient(240px 210px at 108% 9%, rgba(255,255,255,.12), transparent 64%),
            linear-gradient(158deg, color-mix(in srgb, var(--res-reserve-warm) 88%, #5F514A), color-mix(in srgb, var(--res-reserve-brand-2) 84%, #3C302D)) !important;
    }

    .res-reserve-side::after {
        top: -26px;
        right: -78px;
        width: 250px;
        height: 250px;
        background: rgba(255,255,255,.08);
        opacity: .88;
    }

    .res-reserve-side__eyebrow {
        margin-bottom: 14px;
        color: rgba(255,255,255,.68);
        font-size: .68rem;
        letter-spacing: .22em;
    }

    .res-reserve-side h2 {
        max-width: 10ch;
        font-size: 2rem;
        line-height: 1.02;
        text-shadow: 0 1px 0 rgba(0,0,0,.08);
    }

    .res-reserve-side > p {
        margin: 14px 0 22px;
        max-width: 27ch;
        color: rgba(255,255,255,.80);
        font-size: .88rem;
        font-weight: 620;
    }

    .res-reserve-datebox {
        padding: 10px 16px;
        border-radius: 15px;
        border-color: rgba(255,255,255,.16);
        background: rgba(255,255,255,.09);
        box-shadow: 0 1px 0 rgba(255,255,255,.10) inset;
    }

    .res-reserve-dateitem {
        min-height: 58px;
        grid-template-columns: 34px minmax(0, 1fr);
        gap: 12px;
    }

    .res-reserve-dateicon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: rgba(255,255,255,.12);
        color: rgba(255,255,255,.86);
        font-size: .82rem;
    }

    .res-reserve-dateitem small {
        color: rgba(255,255,255,.55);
        font-size: .66rem;
        letter-spacing: .09em;
    }

    .res-reserve-dateitem strong {
        font-size: .91rem;
        letter-spacing: .01em;
    }

    .res-reserve-steps {
        gap: 14px;
        margin-top: 22px;
    }

    .res-reserve-steps li {
        grid-template-columns: 32px minmax(0, 1fr);
        gap: 12px;
        color: rgba(255,255,255,.58);
    }

    .res-reserve-steps li span {
        width: 32px;
        height: 32px;
        border-color: rgba(255,255,255,.18);
        background: rgba(255,255,255,.05);
    }

    .res-reserve-steps li.is-active span {
        border-color: rgba(255,255,255,.20);
        background: color-mix(in srgb, var(--res-reserve-warm) 78%, #FFFEFB);
        box-shadow: 0 14px 26px -20px rgba(0,0,0,.45);
    }

    .res-reserve-main {
        min-height: 528px;
        padding: 42px 36px 0;
        background:
            linear-gradient(180deg, #FFFEFB, color-mix(in srgb, var(--brand-accent, #BD9441) 2%, #FFFEFB)) !important;
    }

    .res-reserve-main__eyebrow {
        margin-bottom: 24px;
        color: #8E97A7;
        font-size: .69rem;
        letter-spacing: .18em;
    }

    .res-reserve-main h3 {
        font-size: 1.42rem;
        line-height: 1.16;
    }

    .res-reserve-main > p {
        max-width: 48ch;
        margin: 8px 0 22px;
        font-size: .88rem;
        line-height: 1.48;
    }

    .res-reserve-choice-list {
        gap: 14px;
    }

    .res-reserve-choice {
        min-height: 98px;
        grid-template-columns: 46px minmax(0, 1fr) 30px;
        gap: 16px;
        padding: 17px 20px;
        border-radius: 12px;
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 11%, #E9DFD1);
        background: #FFFEFB;
        box-shadow: 0 1px 0 rgba(255,255,255,.86) inset;
    }

    .res-reserve-choice:hover,
    .res-reserve-choice:focus-visible,
    .res-reserve-choice.is-selected {
        transform: translateY(-1px);
        border-color: color-mix(in srgb, var(--res-choice-color) 58%, #D9CABE);
        background:
            radial-gradient(260px 140px at 100% 0%, color-mix(in srgb, var(--res-choice-color) 9%, transparent), transparent 68%),
            color-mix(in srgb, var(--res-choice-color) 5%, #FFFEFB);
        box-shadow:
            0 1px 0 rgba(255,255,255,.92) inset,
            0 18px 38px -34px color-mix(in srgb, var(--res-choice-color) 62%, transparent);
    }

    .res-reserve-choice__icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        background: color-mix(in srgb, var(--res-choice-color) 8%, #F7F2EC);
        color: color-mix(in srgb, var(--res-choice-color) 68%, var(--res-reserve-ink));
    }

    .res-reserve-choice__copy {
        gap: 7px;
    }

    .res-reserve-choice__copy strong {
        font-size: 1.04rem;
    }

    .res-reserve-choice__copy em {
        min-height: 19px;
        padding: 0 8px;
        background: color-mix(in srgb, var(--res-choice-color) 9%, #F8F3EE);
        color: color-mix(in srgb, var(--res-choice-color) 72%, var(--res-reserve-muted));
    }

    .res-reserve-choice__copy small {
        max-width: 42ch;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 46%, #8D96A5);
        font-size: .82rem;
        line-height: 1.42;
    }

    .res-reserve-choice__check {
        width: 30px;
        height: 30px;
        border-color: color-mix(in srgb, var(--res-choice-color) 24%, #D9CEC4);
    }

    .res-reserve-choice.is-selected .res-reserve-choice__check {
        border-color: color-mix(in srgb, var(--res-choice-color) 88%, #FFFEFB);
        background: color-mix(in srgb, var(--res-choice-color) 88%, #FFFEFB);
    }

    .res-reserve-footer {
        grid-template-columns: minmax(160px, .9fr) minmax(220px, 1.15fr);
        gap: 18px;
        margin: auto -36px 0;
        padding: 24px 36px 24px;
        border-top-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 9%, #E9DFD1);
        background: linear-gradient(180deg, color-mix(in srgb, var(--brand-accent, #BD9441) 1%, #FFFEFB), #FFFEFB);
    }

    .res-reserve-footer--single {
        grid-template-columns: 1fr !important;
    }

    .res-reserve-btn {
        min-height: 50px;
        border-radius: 12px;
        font-size: .88rem;
    }

    .res-reserve-btn--ghost {
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 12%, #E9DFD1);
        background: #FFFEFB;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 78%, #384153);
    }

    .res-reserve-btn--primary {
        background: linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 84%, #4D5566), var(--brand-secondary, #0F172A));
        color: #FFFEFB;
        box-shadow: 0 18px 34px -24px color-mix(in srgb, var(--brand-secondary, #0F172A) 72%, transparent);
    }
}

@media (max-width: 760px) {
    .swal2-container.res-swal-reservation-container.swal2-backdrop-show,
    .swal2-container.res-swal-reservation-container.swal2-noanimation {
        align-items: flex-end !important;
        padding: 0 18px calc(18px + env(safe-area-inset-bottom)) !important;
        background: rgba(17, 24, 39, .54) !important;
        backdrop-filter: none !important;
    }

    .swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal {
        width: min(390px, calc(100vw - 36px)) !important;
        max-width: 390px !important;
        max-height: 86dvh !important;
        border: 0 !important;
        border-radius: 24px 24px 16px 16px !important;
        background: #FFFEFB !important;
        box-shadow: 0 -12px 40px rgba(18,22,34,.22) !important;
    }

    .swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal .swal2-html-container,
    .res-reserve-shell,
    .res-reserve-main {
        max-height: 86dvh !important;
    }

    .swal2-container.res-swal-reservation-container .swal2-popup.res-reserve-swal .swal2-html-container {
        overflow: hidden !important;
    }

    .res-reserve-shell {
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: #FFFEFB;
    }

    .res-reserve-side {
        display: none !important;
    }

    .res-reserve-main {
        min-height: 0;
        padding: 0 !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
        background: #FFFEFB !important;
    }

    .res-reserve-mobile-intro {
        display: block;
        position: relative;
        padding: 26px 20px 0 !important;
    }

    .res-reserve-mobile-intro::before {
        content: '';
        position: absolute;
        top: 10px !important;
        left: 50%;
        width: 40px !important;
        height: 5px !important;
        border-radius: 999px;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 26%, #D7D0C7) !important;
        transform: translateX(-50%);
    }

    .res-reserve-mobile-kicker {
        display: block;
        margin: 0 48px 6px 0 !important;
        color: #8A93A4;
        font-size: .64rem !important;
        font-weight: 900;
        letter-spacing: .16em !important;
        line-height: 1;
        text-transform: uppercase;
    }

    .res-reserve-mobile-title {
        margin: 0 48px 12px 0 !important;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #111827);
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        font-size: 1.36rem !important;
        font-weight: 850;
        letter-spacing: -.02em;
        line-height: 1.05 !important;
    }

    .res-reserve-mobile-copy {
        display: none !important;
    }

    .res-reserve-mobile-datebox {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin: 0 0 14px !important;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 18%, #E9D8C8);
        border-radius: 13px !important;
        background: linear-gradient(180deg, #FFFEFB, color-mix(in srgb, var(--brand-accent, #BD9441) 3%, #FFFEFB));
        overflow: hidden;
    }

    .res-reserve-mobile-dateitem {
        min-width: 0;
        display: grid;
        grid-template-columns: 24px minmax(0, 1fr) !important;
        align-items: center;
        gap: 6px !important;
        padding: 10px 7px !important;
    }

    .res-reserve-mobile-dateitem + .res-reserve-mobile-dateitem {
        border-left: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 10%, #E9D8C8);
    }

    .res-reserve-mobile-dateicon {
        width: 24px !important;
        height: 24px !important;
        display: grid;
        place-items: center;
        border-radius: 8px !important;
        background: color-mix(in srgb, var(--brand-accent, #BD9441) 7%, #F8F2EC);
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 70%, var(--brand-accent, #BD9441));
        font-size: .66rem !important;
    }

    .res-reserve-mobile-dateitem small {
        display: block;
        color: #8A93A4;
        font-size: .50rem !important;
        font-weight: 900;
        letter-spacing: .07em !important;
        line-height: 1;
        text-transform: uppercase;
    }

    .res-reserve-mobile-dateitem strong {
        display: block;
        margin-top: 3px !important;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 88%, #111827);
        font-size: .68rem !important;
        font-weight: 850;
        line-height: 1.1;
        white-space: nowrap;
    }

    .res-reserve-mobile-progress {
        display: grid;
        grid-template-columns: 86px minmax(0, 1fr) !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 0 16px !important;
    }

    .res-reserve-mobile-progress-title {
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 56%, #6B7280);
        font-size: .74rem !important;
        font-weight: 820;
        line-height: 26px !important;
    }

    .res-reserve-mobile-steps {
        display: grid;
        grid-template-columns: 1fr 1fr;
        position: relative;
        min-width: 0;
    }

    .res-reserve-mobile-steps::before {
        content: '';
        position: absolute;
        top: 12px !important;
        left: 24px !important;
        right: 24px !important;
        height: 2px;
        background: color-mix(in srgb, var(--brand-secondary, #0F172A) 16%, #D9DEE6);
    }

    .res-reserve-mobile-step {
        position: relative;
        z-index: 1;
        display: grid;
        justify-items: center;
        gap: 0 !important;
        color: #7A8496;
        font-size: .72rem;
        font-weight: 820;
        text-align: center;
    }

    .res-reserve-mobile-step span {
        width: 26px !important;
        height: 26px !important;
        display: grid;
        place-items: center;
        border-radius: 999px;
        border: 1px solid color-mix(in srgb, var(--brand-secondary, #0F172A) 20%, #DADDE3);
        background: #FFFEFB;
        color: #7A8496;
        font-size: .70rem !important;
        font-weight: 850;
        font-variant-numeric: tabular-nums;
    }

    .res-reserve-mobile-step strong {
        display: none !important;
    }

    .res-reserve-mobile-step.is-active {
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
    }

    .res-reserve-mobile-step.is-active span {
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 34%, var(--brand-accent, #BD9441));
        background: color-mix(in srgb, var(--brand-secondary, #0F172A) 68%, var(--brand-accent, #BD9441));
        color: #FFFEFB;
    }

    .res-reserve-mobile-step.is-complete span {
        border-color: #20A66B;
        background: #20A66B;
        color: #FFFEFB;
    }

    .res-reserve-main > .res-reserve-main__eyebrow {
        display: none;
    }

    .res-reserve-main h3 {
        margin: 0 0 14px !important;
        padding: 0 20px !important;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 92%, #111827);
        font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
        font-size: 1.06rem !important;
        font-weight: 850;
        letter-spacing: -.02em;
        line-height: 1.15 !important;
    }

    .res-reserve-main > p {
        display: none !important;
    }

    .res-reserve-choice-list {
        grid-template-columns: 1fr;
        gap: 8px !important;
        padding: 0 20px !important;
    }

    .res-reserve-choice {
        min-height: 68px !important;
        grid-template-columns: 38px minmax(0, 1fr) 28px !important;
        gap: 11px !important;
        padding: 11px 12px !important;
        border-radius: 12px !important;
    }

    .res-reserve-choice__icon {
        width: 38px !important;
        height: 38px !important;
        border-radius: 11px !important;
        font-size: .88rem !important;
    }

    .res-reserve-choice__copy {
        gap: 3px !important;
    }

    .res-reserve-choice__copy > span {
        gap: 7px !important;
    }

    .res-reserve-choice__copy strong {
        font-size: .94rem !important;
    }

    .res-reserve-choice__copy em {
        min-height: 18px !important;
        padding: 0 7px !important;
        font-size: .58rem !important;
    }

    .res-reserve-choice__copy small {
        display: none !important;
    }

    .res-reserve-choice__check {
        width: 28px !important;
        height: 28px !important;
        font-size: .72rem !important;
    }

    .res-reserve-timechips {
        flex-wrap: wrap;
        gap: 7px !important;
        padding: 0 20px !important;
        margin: 0 !important;
    }

    .res-reserve-timechip {
        min-height: 38px !important;
        flex: 1 1 calc(50% - 4px) !important;
        min-width: 0;
        justify-content: center;
        padding: 8px 7px !important;
        font-size: .76rem !important;
    }

    .res-reserve-timefield {
        min-height: 52px !important;
        margin: 10px 20px 0 !important;
        padding: 0 14px !important;
    }

    .res-reserve-timefield .hb-arrival-input {
        height: 50px !important;
        min-height: 50px !important;
        font-size: .98rem !important;
    }

    .res-reserve-hint {
        display: none !important;
    }

    .res-reserve-validation {
        margin: 8px 20px 0 !important;
    }

    .res-reserve-footer {
        grid-template-columns: 1fr 1fr;
        gap: 10px !important;
        position: sticky;
        bottom: 0;
        z-index: 4;
        margin: 14px 0 0 !important;
        padding: 12px 20px calc(14px + env(safe-area-inset-bottom)) !important;
        border-top: 0;
        background:
            linear-gradient(180deg, rgba(255,254,251,0), #FFFEFB 20%),
            #FFFEFB;
    }

    .res-reserve-footer--single {
        grid-template-columns: 1fr !important;
    }

    .res-reserve-btn {
        min-height: 46px !important;
        border-radius: 13px !important;
        font-size: .84rem !important;
    }

    .res-reserve-btn--ghost {
        border-color: color-mix(in srgb, var(--brand-secondary, #0F172A) 13%, #E9D8C8);
        background: #FFFEFB;
        color: color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, #394154);
    }

    .res-reserve-btn--primary {
        background: linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, var(--brand-accent, #BD9441)), var(--brand-secondary, #0F172A));
        box-shadow: 0 18px 36px -26px color-mix(in srgb, var(--brand-secondary, #0F172A) 76%, transparent);
    }
}
/* Viewport alignment: match the broad operational canvas used by huespedes/facturacion/inventario. */
.res-bookings {
    padding: 1rem !important;
}

.res-shell {
    width: 100%;
    max-width: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    padding: 0 !important;
}

@media (min-width: 640px) {
    .res-bookings {
        padding: 2rem !important;
    }
}

/* ════════════════════════════════════════════════════════════════════════
   BOUTIQUE MOBILE  ·  ≤780px
   Warm-cream editorial layout para teléfonos (iPhone 12 Pro 390px base).
   Font-weight cap: 700 en sans-serif (regla boutique).
   ════════════════════════════════════════════════════════════════════════ */

/* FAB bottom bar — oculto en desktop, visible en mobile */
.res-mob-bottom { display: none; }

@media (max-width: 780px) {

    .main-content { overflow-x: hidden !important; }

    /* 1 ── Canvas ─────────────────────────────────────────────────────── */
    .res-bookings {
        padding: 0 !important;
        background: #F1EDE5 !important;
        background-image: none !important;
        overflow-x: hidden;
        max-width: 100%;
    }
    .res-shell {
        padding: 0 !important;
        padding-bottom: calc(84px + env(safe-area-inset-bottom, 0px)) !important;
        overflow-x: hidden;
        max-width: 100%;
    }

    /* 2 ── Header hero card — réplica exacta de .rdv3-hero (ver.php) ────── */
    .res-topbar {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 0;
        margin: 14px 16px 0 !important;
        padding: 18px 18px 18px 26px;
        border-radius: 17px;
        background: linear-gradient(125deg, var(--res-brand), color-mix(in srgb, var(--res-brand) 86%, #243357));
        color: #fff;
        box-shadow: 0 15px 30px rgba(28, 37, 62, .18);
        max-width: calc(100% - 32px);
    }
    /* Círculo dorado tenue arriba-derecha (idéntico a .rdv3-hero::after) */
    .res-topbar::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: rgba(176, 136, 63, .14);
        pointer-events: none;
    }
    .res-title-lockup {
        position: relative;
        z-index: 1;
        grid-template-columns: 40px minmax(0, 1fr);
        column-gap: 12px;
        align-items: center;
        min-width: 0;
        max-width: none;
    }
    .res-title-copy {
        min-width: 0;
        overflow: hidden;
    }
    .res-hero-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        border-radius: 12px;
        background: rgba(255, 255, 255, .12) !important;
        border: 1px solid rgba(255, 255, 255, .2);
        color: #fff !important;
        box-shadow: none !important;
    }
    .res-kicker { display: none; }
    .res-title {
        font-size: 1.55rem;
        font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
        font-weight: 600;
        line-height: 1;
        letter-spacing: 0;
        color: #fff;
    }
    .res-subtitle {
        font-size: .72rem;
        font-weight: 500;
        margin-top: 6px;
        color: rgba(255, 255, 255, .68);
        white-space: normal;
        line-height: 1.45;
        max-width: 100%;
    }
    /* Buscador oculto SOLO en móvil (en PC sigue visible). */
    .res-actions {
        display: none !important;
    }
    .res-search {
        display: block !important;
        width: 100%;
        min-width: 0;
        position: relative;
    }
    .res-search i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        display: block !important;
        color: #939BAD;
        pointer-events: none;
        z-index: 2;
        font-size: .9rem;
    }
    .res-search input,
    .res-search input.search-input {
        width: 100%;
        height: 44px;
        font-size: .88rem;
        font-weight: 500;
        border-radius: 12px;
        background: #FFFFFF;
        border: 1px solid transparent;
        color: #1B2746;
        padding: 0 14px 0 40px !important;
        box-shadow: 0 6px 16px -6px rgba(0, 0, 0, .28);
    }
    .res-search input::placeholder { color: #939BAD; font-weight: 500; }
    .res-search input:focus {
        border-color: var(--res-accent);
        background: #FFFFFF;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--res-accent) 22%, transparent);
    }
    /* Ocultar todos los botones — van al FAB inferior */
    .res-actions > a,
    .res-actions > button { display: none !important; }

    /* 3 ── Stat pills horizontales (diseño "sumrow") ──────────────────── */
    .res-metrics {
        display: flex;
        grid-template-columns: none;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 8px;
        padding: 11px 16px 4px;
        margin: 0;
        scrollbar-width: none;
    }
    .res-metrics::-webkit-scrollbar { display: none; }
    .res-metric {
        flex: 0 0 auto;
        min-height: 0;
        min-width: 84px;
        padding: 9px 14px 8px 28px;
        border-radius: 13px;
        border: 1px solid #E6DBC8;
        background: #FFFDF9;
        box-shadow: 0 1px 2px rgba(39, 31, 18, .04);
        overflow: visible;
    }
    .res-metric.is-primary { grid-column: auto !important; }
    .res-metric::after { display: none; }
    /* Punto de color (estado) arriba-izquierda */
    .res-metric::before {
        content: "";
        position: absolute;
        left: 13px;
        top: 14px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--res-pill-dot, #5A57D2);
    }
    .res-metric:nth-child(1) { --res-pill-dot: #5A57D2; }  /* Llegadas */
    .res-metric:nth-child(2) { --res-pill-dot: #C2841C; }  /* Salidas */
    .res-metric:nth-child(3) { --res-pill-dot: #1E9E63; }  /* Confirmadas */
    .res-metric:nth-child(4) { --res-pill-dot: #C2603C; }  /* Hospedados */
    .res-metric:nth-child(5) { --res-pill-dot: #D64539; }  /* Canceladas */
    .res-metric strong {
        font-size: 1.2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 3px;
    }
    .res-metric span {
        font-size: .66rem;
        font-weight: 600;
        white-space: nowrap;
        color: #6C7689;
    }
    /* Icono no aplica en formato pill */
    .res-metric-icon { display: none; }

    /* 4 ── Filterbar ──────────────────────────────────────────────────── */
    .res-filterbar {
        display: block;
        border: none;
        border-radius: 0;
        border-top: 1px solid #E6DBC8;
        border-bottom: 1px solid #E6DBC8;
        background: transparent;
        padding: 0;
        margin: 12px 0 0;
        box-shadow: none;
    }
    .res-tabs {
        display: flex;
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 6px;
        padding: 8px 16px;
        scrollbar-width: none;
    }
    .res-tabs::-webkit-scrollbar { display: none; }
    .res-tab {
        flex: 0 0 auto;
        white-space: nowrap;
        font-size: .78rem;
        font-weight: 600;
        padding: 7px 11px;
        border-radius: 999px;
        border-color: #E6DBC8;
        background: #FFFDF9;
        color: #5C5040;
        cursor: pointer;
    }
    .res-tab.is-active {
        font-weight: 700;
        background: var(--res-brand);
        border-color: transparent;
        color: #fff;
        box-shadow: 0 6px 16px color-mix(in srgb, var(--res-brand) 22%, transparent);
    }
    .res-tab-dot { width: 7px; height: 7px; }
    .res-date-tools {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px 8px;
        overflow-x: auto;
        scrollbar-width: none;
        border-top: 1px solid #EFE3D0;
    }
    .res-date-tools::-webkit-scrollbar { display: none; }
    .res-date-tools input {
        height: 36px;
        border-radius: 9px;
        border-color: #E6DBC8;
        background: #FFFDF9;
        font-size: .82rem;
        font-weight: 600;
        color: #1A1108;
        flex: 0 0 auto;
    }
    .res-date-chip {
        height: 36px;
        border-radius: 9px;
        border-color: #E6DBC8;
        background: #FFFDF9;
        color: #5C5040;
        font-size: .78rem;
        font-weight: 600;
        flex: 0 0 auto;
        white-space: nowrap;
        cursor: pointer;
        gap: 6px;
    }

    /* 5 ── Mobile card list ────────────────────────────────────────────── */
    .res-mobile-list {
        display: grid !important;
        gap: 10px;
        padding: 12px 16px;
    }

    /* 5a — Card shell con franja de estado (diseño "rescard .stripe") */
    .res-mobile-card {
        position: relative;
        border: 1px solid #E6DBC8;
        border-radius: 16px;
        background: #FFFDF9;
        padding: 15px 15px 14px 19px;
        box-shadow: 0 1px 2px rgba(39, 31, 18, .05);
        overflow: hidden;
        transition: box-shadow .18s ease, transform .12s ease;
    }
    .res-mobile-card:active { transform: scale(.985); }
    .res-mobile-card:hover {
        box-shadow: 0 8px 22px rgba(39, 31, 18, .09);
        transform: none;
        border-color: color-mix(in srgb, var(--res-accent) 30%, #E6DBC8);
    }
    /* Franja lateral coloreada según estado */
    .res-mobile-card::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        background: var(--res-stripe, #939BAD);
    }
    .res-mobile-card[data-estado="confirmada"]  { --res-stripe: #1E9E63; }
    .res-mobile-card[data-estado="checked_in"]  { --res-stripe: #C2603C; }
    .res-mobile-card[data-estado="checked_out"] { --res-stripe: #5B6B86; }
    .res-mobile-card[data-estado="cancelada"]   { --res-stripe: #D64539; }
    .res-mobile-card[data-estado="pendiente"]   { --res-stripe: #C2841C; }
    .res-mobile-card[data-estado="por_llegar"]  { --res-stripe: #5A57D2; }

    /* 5b — Cabecera: avatar + guest + estado */
    .res-mobile-head {
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
    }
    .res-avatar {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        font-size: .76rem;
        font-weight: 700;
        border: none;
        box-shadow: none;
    }
    .res-guest-name {
        font-size: .94rem;
        font-weight: 700;
        color: #1A1108;
        line-height: 1.2;
    }
    .res-guest-name i { font-size: .7rem; opacity: .55; }
    .res-guest-meta {
        font-size: .74rem;
        font-weight: 500;
        color: #9BA3B6;
        margin-top: 2px;
    }
    .res-state {
        font-size: .7rem;
        font-weight: 600;
        padding: 5px 9px;
        flex: 0 0 auto;
    }

    /* 5c — Habitación */
    .res-mobile-room {
        margin: 8px 0 0;
        gap: 8px;
        font-size: .88rem;
        font-weight: 700;
        color: #1A1108;
    }
    .res-room-mark {
        width: 4px;
        min-width: 4px;
        height: 18px;
        margin-top: 3px;
        border-radius: 8px;
    }
    .res-mobile-room-body { gap: 2px; }
    .res-mobile-room-body small {
        font-size: .74rem;
        font-weight: 500;
    }
    .res-room-chip {
        font-size: .68rem;
        font-weight: 600;
        border-radius: 6px;
        padding: 2px 6px;
    }

    /* 5d — Info grid 2×2 */
    .res-mobile-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 10px;
    }
    .res-mobile-info {
        border-radius: 10px;
        background: #F8F3EB;
        border: 1px solid #EFE3D0;
        padding: 8px 10px;
    }
    .res-mobile-info span {
        font-size: .64rem;
        font-weight: 600;
        color: #9BA3B6;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: 3px;
    }
    .res-mobile-info strong {
        font-size: .86rem;
        font-weight: 700;
        color: #1A1108;
        line-height: 1.2;
    }

    /* 5e — Acciones */
    .res-mobile-actions {
        flex-direction: row;
        gap: 8px;
        margin-top: 10px;
    }
    .res-action-pill {
        min-height: 42px;
        border-radius: 10px;
        font-size: .8rem;
        font-weight: 700;
        border: 1px solid #E6DBC8;
        background: #F8F3EB;
        color: #1A1108;
        transition: box-shadow .18s ease, transform .18s ease;
    }
    .res-action-pill:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(39, 31, 18, .09); }
    .res-action-primary {
        background: var(--res-brand);
        border-color: transparent;
        color: #fff;
    }
    .res-action-warning {
        background: #D97706;
        border-color: transparent;
        color: #fff;
    }

    /* 6 ── Estado vacío ───────────────────────────────────────────────── */
    .res-empty,
    .res-filter-empty {
        border-radius: 14px;
        margin: 0 16px;
        border-color: #E6DBC8;
        background: #FFFDF9;
    }

    /* 7 ── FAB bottom bar ─────────────────────────────────────────────── */
    .res-mob-bottom {
        display: flex;
        position: fixed;
        left: auto;
        right: 16px;
        bottom: calc(16px + env(safe-area-inset-bottom, 0px));
        z-index: 50;
        align-items: center;
        justify-content: flex-end;
        gap: 7px;
        width: auto;
        max-width: calc(100vw - 32px);
        padding: 6px;
        background: rgba(255, 253, 249, .82);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        border: 1px solid rgba(230, 219, 200, .92);
        border-radius: 18px;
        box-shadow: 0 12px 26px rgba(39, 31, 18, .12);
    }
    .res-mob-bottom::before {
        content: '';
        position: absolute;
        inset: 1px;
        border-radius: 17px;
        background: rgba(255, 255, 255, .28);
        pointer-events: none;
    }
    .res-mob-bottom > a {
        position: relative;
        z-index: 1;
        width: 44px;
        min-width: 44px;
        height: 44px;
        min-height: 44px;
    }
    .res-mob-bottom-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: rgba(248, 243, 235, .86);
        border: 1px solid rgba(230, 219, 200, .88);
        color: #5C5040;
        font-size: .94rem;
        flex: 0 0 44px;
        text-decoration: none;
        transition: background .18s ease, border-color .18s ease, transform .18s ease;
    }
    .res-mob-bottom-icon:hover {
        background: #EFE3D0;
        border-color: #D8C8B0;
        transform: translateY(-1px);
    }
    .res-mob-bottom a:focus-visible {
        outline: 2px solid color-mix(in srgb, var(--res-accent) 72%, #fff);
        outline-offset: 2px;
    }
    .res-mob-bottom-cta {
        flex: 0 0 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        border-radius: 14px;
        background: var(--brand-action-bg, var(--res-brand));
        color: #fff;
        font-size: .96rem;
        font-weight: 700;
        text-decoration: none;
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 20px -14px color-mix(in srgb, var(--res-brand) 55%, transparent);
        transition: filter .18s ease, transform .18s ease;
    }
    .res-mob-bottom-cta:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .res-mob-bottom a:active { transform: scale(.96); }
    .res-mob-bottom-label {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    body.has-hotel-bottom-nav .res-shell {
        padding-bottom: calc(var(--hbn-offset, calc(84px + env(safe-area-inset-bottom, 0px))) + 66px) !important;
    }

    body.has-hotel-bottom-nav .res-mob-bottom {
        bottom: calc(var(--hbn-offset, calc(84px + env(safe-area-inset-bottom, 0px))) + 10px);
        z-index: 970;
    }
}

@media (max-width: 420px) {
    .res-mobile-grid { grid-template-columns: 1fr 1fr; }
    .res-mobile-actions { flex-direction: row; }
}

@media (max-width: 780px) {
    .res-bookings .res-topbar .res-title-lockup {
        padding-left: 12px !important;
        box-sizing: border-box;
    }
}
</style>

<div class="res-bookings">
    <div class="res-shell">
        <?php include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <header class="res-topbar">
            <div class="res-title-lockup">
                <div class="res-hero-icon" aria-hidden="true">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="res-title-copy">
                    <p class="res-kicker">Operaci&oacute;n hotelera</p>
                    <h1 class="res-title">Reservaciones</h1>
                    <p class="res-subtitle">
                        <?= htmlspecialchars($hotel_nombre_reservas) ?> &middot; <?= (int) $total_visible ?> reservas en esta fecha &middot; <?= htmlspecialchars($fecha_bonita) ?>
                        <?php if ($total_proximas > 0): ?>
                            &middot; <?= (int) $total_proximas ?> pr&oacute;ximas
                        <?php endif; ?>
                    </p>
                </div>
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
                <?php if (!function_exists('hotel_menu_module_enabled') || hotel_menu_module_enabled('exportaciones')): ?>
                <button type="button" onclick="abrirModalExportarPDF()" class="res-btn res-btn-danger" title="Exportar reservaciones a PDF">
                    <i class="fas fa-file-pdf"></i>
                    PDF
                </button>
                <button type="button" onclick="abrirModalExportarExcel()" class="res-btn res-btn-success" title="Exportar reservaciones a Excel">
                    <i class="fas fa-file-excel"></i>
                    Excel
                </button>
                <?php endif; ?>
                <a id="cop-ancla-nueva-reserva" href="<?= url('reservaciones/crear') ?>" onclick="return resAbrirSelectorNuevaReserva(event)" class="res-btn res-btn-primary" title="Crear una nueva reservación">
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

        <?php include APP_PATH . '/views/partials/reservaciones_alertas_pendientes.php'; ?>

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
                <h2 style="font-size:1.1rem;font-weight:900;color:var(--res-heading);margin:0 0 6px;">
                    <?= $total_proximas > 0 ? 'Sin reservaciones en esta fecha' : 'Sin reservaciones' ?>
                </h2>
                <p style="margin:0 0 18px;">
                    <?php if ($total_proximas > 0 && $proxima_reserva): ?>
                        No hay reservaciones para <?= $es_hoy ? 'hoy' : htmlspecialchars(date('d/m/Y', $ts)) ?>.
                        La siguiente llegada es el <?= htmlspecialchars(format_date($proxima_reserva['fecha_entrada'] ?? $fecha_hoy, 'd M Y')) ?>.
                    <?php else: ?>
                        No hay reservaciones para <?= $es_hoy ? 'hoy' : htmlspecialchars(date('d/m/Y', $ts)) ?>.
                    <?php endif; ?>
                </p>
                <div class="res-empty-actions">
                    <?php if ($total_proximas > 0): ?>
                        <a href="#proximasReservaciones" class="res-date-chip is-upcoming">
                            <i class="fas fa-forward"></i>Ver agenda pr&oacute;xima
                        </a>
                    <?php endif; ?>
                    <a href="<?= url('reservaciones/crear') ?>" onclick="return resAbrirSelectorNuevaReserva(event)" class="res-btn res-btn-primary">
                        <i class="fas fa-plus"></i>Crear reservacion
                    </a>
                </div>
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
                            <th>Habitaciones</th>
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
                                $habitacion_tipo = reserva_title($row['habitacion_tipo'] ?? '');
                                $habitaciones_lista = is_array($row['_habitaciones_lista'] ?? null) ? $row['_habitaciones_lista'] : reserva_habitaciones_lista($row);
                                $todas_habs = trim((string) ($row['todas_habitaciones'] ?? ''));
                                if ($todas_habs === '' && !empty($habitaciones_lista)) {
                                    $todas_habs = implode(', ', $habitaciones_lista);
                                }
                                $total_habs_reserva = max((int) ($row['total_habitaciones_reserva'] ?? 1), count($habitaciones_lista));
                                $habitacion_label = $total_habs_reserva > 1 ? $total_habs_reserva . ' habitaciones' : ($habitacion_numero !== '' ? $habitacion_numero : 'Por asignar');
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
                            <tr class="reservation-item is-linked" data-res-id="<?= $res_id ?>" data-href="<?= url('reservaciones/ver/' . $res_id) ?>" data-search="<?= htmlspecialchars($search_data, ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= htmlspecialchars($estado) ?>">
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
                                        <div class="res-guest-copy">
                                            <div class="res-guest-name-row">
                                            <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-guest-name res-detail-link" title="Ver reservación de <?= htmlspecialchars($huesped_nombre) ?>">
                                                <?= htmlspecialchars($huesped_nombre) ?>
                                                <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                            </a>
                                            </div>
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
                                            <?php if ($total_habs_reserva > 1 && !empty($habitaciones_lista)): ?>
                                                <div class="res-room-list" aria-label="Habitaciones de la reservacion">
                                                    <?php foreach ($habitaciones_lista as $habitacion_item): ?>
                                                        <span class="res-room-chip"><?= htmlspecialchars($habitacion_item) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="res-room-type">
                                                    <?= $habitacion_tipo !== '' ? htmlspecialchars($habitacion_tipo) : 'Habitacion' ?>
                                                </div>
                                            <?php endif; ?>
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
                        $habitacion_tipo = reserva_title($row['habitacion_tipo'] ?? '');
                        $habitaciones_lista = is_array($row['_habitaciones_lista'] ?? null) ? $row['_habitaciones_lista'] : reserva_habitaciones_lista($row);
                        $todas_habs = trim((string) ($row['todas_habitaciones'] ?? ''));
                        if ($todas_habs === '' && !empty($habitaciones_lista)) {
                            $todas_habs = implode(', ', $habitaciones_lista);
                        }
                        $total_habs_reserva = max((int) ($row['total_habitaciones_reserva'] ?? 1), count($habitaciones_lista));
                        $habitacion_label = $total_habs_reserva > 1 ? $total_habs_reserva . ' habitaciones' : ($habitacion_numero !== '' ? $habitacion_numero : 'Por asignar');
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
                    <article class="res-mobile-card reservation-item is-linked" data-res-id="<?= $res_id ?>" data-href="<?= url('reservaciones/ver/' . $res_id) ?>" data-search="<?= htmlspecialchars($search_data, ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                        <div class="res-mobile-head">
                            <div class="res-mobile-guest">
                                <div class="res-avatar" style="background:linear-gradient(135deg, <?= htmlspecialchars($color['bg']) ?>, <?= htmlspecialchars($color['dark']) ?>);">
                                    <?= htmlspecialchars(reserva_iniciales($huesped_nombre)) ?>
                                </div>
                                <div class="res-guest-copy">
                                    <div class="res-guest-name-row">
                                    <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-guest-name res-detail-link" title="Ver reservación de <?= htmlspecialchars($huesped_nombre) ?>">
                                        <?= htmlspecialchars($huesped_nombre) ?>
                                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                                    </a>
                                    </div>
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
                            <div class="res-mobile-room-body">
                                <span><?= htmlspecialchars($habitacion_label) ?></span>
                                <?php if ($total_habs_reserva > 1 && !empty($habitaciones_lista)): ?>
                                    <div class="res-room-list" aria-label="Habitaciones de la reservacion">
                                        <?php foreach ($habitaciones_lista as $habitacion_item): ?>
                                            <span class="res-room-chip"><?= htmlspecialchars($habitacion_item) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <small style="color:#8B96A9;font-weight:800;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                        <?= $habitacion_tipo !== '' ? htmlspecialchars($habitacion_tipo) : 'Habitacion' ?>
                                    </small>
                                <?php endif; ?>
                            </div>
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

        <?php if ($total_proximas > 0): ?>
            <section class="res-upcoming no-print<?= $abrir_agenda_proxima ? ' is-open' : '' ?>" id="proximasReservaciones" aria-label="Agenda de proximas reservaciones">
                <div class="res-upcoming-head" onclick="resUpcomingToggle(this)" role="button" aria-expanded="<?= $abrir_agenda_proxima ? 'true' : 'false' ?>" aria-controls="upcomingGrid" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();resUpcomingToggle(this);}">
                    <div>
                        <span class="res-section-kicker"><i class="fas fa-route"></i>Agenda pr&oacute;xima</span>
                        <h2>Pr&oacute;ximas reservaciones</h2>
                        <p>Confirmadas despu&eacute;s de la fecha seleccionada, ordenadas por llegada.</p>
                    </div>
                    <div class="res-upcoming-toggle">
                        <a href="<?= url('reservaciones/calendario') ?>" class="res-date-chip" onclick="event.stopPropagation()">
                            <i class="fas fa-calendar-alt"></i>Calendario
                        </a>
                        <span class="res-upcoming-chevron" aria-hidden="true"><i class="fas fa-chevron-down"></i></span>
                    </div>
                </div>

                <div class="res-upcoming-body">
                <div class="res-upcoming-body-inner">
                <div class="res-upcoming-grid" id="upcomingGrid">
                    <?php foreach ($proximas_reservaciones as $row): ?>
                        <?php
                            $res_id = (int) ($row['id'] ?? 0);
                            $folio = 'RSV-' . str_pad((string) $res_id, 4, '0', STR_PAD_LEFT);
                            $huesped_nombre = trim((string) ($row['huesped_nombre'] ?? 'Sin nombre'));
                            $huesped_telefono = trim((string) ($row['huesped_telefono'] ?? ''));
                            $habitacion_numero = trim((string) ($row['habitacion_numero'] ?? ''));
                            $habitacion_tipo = reserva_title($row['habitacion_tipo'] ?? '');
                            $habitaciones_lista = is_array($row['_habitaciones_lista'] ?? null) ? $row['_habitaciones_lista'] : reserva_habitaciones_lista($row);
                            $todas_habs = trim((string) ($row['todas_habitaciones'] ?? ''));
                            if ($todas_habs === '' && !empty($habitaciones_lista)) {
                                $todas_habs = implode(', ', $habitaciones_lista);
                            }
                            $total_habs_reserva = max((int) ($row['total_habitaciones_reserva'] ?? 1), count($habitaciones_lista));
                            $habitacion_label = $total_habs_reserva > 1 ? $total_habs_reserva . ' habitaciones' : ($habitacion_numero !== '' ? $habitacion_numero : 'Por asignar');
                            $estado = $row['estado'] ?? 'confirmada';
                            $estado_ui = reserva_estado_ui($estado, $estados);
                            $fecha_entrada_raw = $row['fecha_entrada'] ?? $fecha_hoy;
                            $fecha_salida_raw = $row['fecha_salida'] ?? $fecha_hoy;
                            $entrada_ts = strtotime((string) $fecha_entrada_raw) ?: time();
                            $mes_abrev = substr($meses[(int) date('n', $entrada_ts) - 1] ?? '', 0, 3);
                            $hora_llegada = !empty($row['hora_llegada_estimada']) ? substr($row['hora_llegada_estimada'], 0, 5) : (!empty($row['hora_entrada']) ? substr($row['hora_entrada'], 0, 5) : $res_hotel_checkin_hora);
                            $noches = reserva_noches($fecha_entrada_raw, $fecha_salida_raw);
                            $precio_total = (float) ($row['precio_total'] ?? 0);
                            $search_data = reserva_lower($folio . ' ' . $huesped_nombre . ' ' . $huesped_telefono . ' ' . $habitacion_label . ' ' . $todas_habs . ' ' . $habitacion_tipo . ' ' . $estado_ui['label']);
                        ?>
                        <article class="res-upcoming-card is-linked" data-res-id="future-<?= $res_id ?>" data-href="<?= url('reservaciones/ver/' . $res_id) ?>" data-search="<?= htmlspecialchars($search_data, ENT_QUOTES, 'UTF-8') ?>" data-estado="<?= htmlspecialchars($estado) ?>">
                            <div class="res-upcoming-date" aria-label="Llegada <?= htmlspecialchars(format_date($fecha_entrada_raw, 'd M Y')) ?>">
                                <span><?= htmlspecialchars(date('d', $entrada_ts)) ?></span>
                                <strong><?= htmlspecialchars(reserva_upper($mes_abrev)) ?></strong>
                            </div>

                            <div class="res-upcoming-main">
                                <div class="res-upcoming-title">
                                    <a href="<?= url('reservaciones/ver/' . $res_id) ?>" title="Ver detalle de <?= htmlspecialchars($folio) ?>">
                                        <?= htmlspecialchars($huesped_nombre) ?>
                                    </a>
                                    <span><?= htmlspecialchars($folio) ?></span>
                                </div>
                                <div class="res-upcoming-meta">
                                    <span><i class="fas fa-clock"></i><?= htmlspecialchars($hora_llegada) ?></span>
                                    <span><i class="fas fa-moon"></i><?= (int) $noches ?> noche<?= $noches === 1 ? '' : 's' ?></span>
                                    <span><i class="fas fa-bed"></i><?= htmlspecialchars($habitacion_label) ?></span>
                                    <?php if ($habitacion_tipo !== '' && $total_habs_reserva <= 1): ?>
                                        <span><i class="fas fa-tag"></i><?= htmlspecialchars($habitacion_tipo) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($habitaciones_lista)): ?>
                                    <div class="res-room-list" aria-label="Habitaciones de la reservacion">
                                        <?php foreach ($habitaciones_lista as $habitacion_item): ?>
                                            <span class="res-room-chip"><?= htmlspecialchars($habitacion_item) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="res-upcoming-side">
                                <span class="res-state <?= htmlspecialchars($estado_ui['class']) ?>">
                                    <i class="fas fa-<?= htmlspecialchars($estado_ui['icon']) ?>"></i>
                                    <?= htmlspecialchars($estado_ui['label']) ?>
                                </span>
                                <strong class="res-upcoming-total"><?= format_money($precio_total) ?></strong>
                                <a href="<?= url('reservaciones/ver/' . $res_id) ?>" class="res-action-pill res-upcoming-view" title="Ver detalle de <?= htmlspecialchars($folio) ?>" aria-label="Ver detalle de <?= htmlspecialchars($folio) ?>">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                </div><!-- /.res-upcoming-body-inner -->
                </div><!-- /.res-upcoming-body -->
            </section>
        <?php endif; ?>
    </div>
    <!-- FAB móvil: sólo visible en ≤780px vía CSS -->
    <div class="res-mob-bottom no-print" role="navigation" aria-label="Acciones rapidas de reservaciones">
        <a href="<?= url('reservaciones/calendario') ?>" class="res-mob-bottom-icon" title="Calendario de reservaciones" aria-label="Calendario de reservaciones">
            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
        </a>
        <a href="<?= url('reservaciones/crear') ?>" onclick="return resAbrirSelectorNuevaReserva(event)" class="res-mob-bottom-cta" title="Nueva reservaci&oacute;n" aria-label="Nueva reservaci&oacute;n">
            <i class="fas fa-plus" aria-hidden="true"></i>
            <span class="res-mob-bottom-label">Nueva reservaci&oacute;n</span>
        </a>
    </div>
</div>

<div id="modalCheckIn" class="res-checkin-modal hidden" aria-hidden="true">
    <section class="res-checkin-card" role="dialog" aria-modal="true" aria-labelledby="checkInTitle">
        <div class="res-checkin-head">
            <div class="res-checkin-title">
                <span class="res-checkin-icon"><i class="fas fa-sign-in-alt"></i></span>
                <div>
                    <h3 id="checkInTitle">Confirmar check-in</h3>
                    <p class="res-checkin-sub-desktop">Revisa llegada, cobro y factura antes de confirmar.</p>
                    <span id="ciStepPill" class="ci-step-pill" hidden>Paso 1 de 2</span>
                </div>
            </div>
            <button type="button" onclick="cerrarModalCheckIn()" class="res-checkin-close" aria-label="Cerrar check-in">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formCheckInModal" method="POST" action="" class="res-checkin-form">
            <?= csrf_field() ?>
            <input type="hidden" name="permitir_saldo_pendiente" id="permitir_saldo_pendiente" value="0">
            <input type="hidden" name="checkin_return_to" id="checkin_return_to" value="reservaciones">
            <div class="res-checkin-body">
                <div class="res-checkin-summary" data-ci-step="1">
                    <div class="res-ci-total">
                        <span class="res-ci-kicker">Total a cobrar</span>
                        <div id="totalACobrar" class="res-ci-amount">$0.00</div>
                    </div>
                    <div class="res-ci-time">
                        <label for="hora_entrada_checkin_index" class="res-ci-label">Hora de llegada</label>
                        <input type="time" id="hora_entrada_checkin_index" name="hora_entrada" value="<?= date('H:i') ?>" class="res-ci-input" required>
                    </div>
                </div>

                <section class="res-ci-section" data-ci-step="1">
                    <div class="res-ci-section-head">
                        <h4><i class="fas fa-wallet"></i>Metodos de pago</h4>
                        <span>Uno o varios</span>
                    </div>
                    <p class="res-ci-prompt">Elige como esta pagando el huesped. Si solo pagara una parte hoy, activa la opcion de dejar el resto pendiente.</p>

                    <div id="resCiBreakdown" class="res-ci-breakdown" aria-live="polite" hidden>
                        <div class="res-ci-breakdown-head" onclick="ciToggleBreakdown()" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();ciToggleBreakdown();}" role="button" tabindex="0" aria-expanded="true">
                            <span class="res-ci-breakdown-icon"><i class="fas fa-circle-info"></i></span>
                            <div>
                                <p class="res-ci-breakdown-title">Por que se cobra este monto</p>
                                <p id="resCiBreakdownSub" class="res-ci-breakdown-sub">Resumen informativo del precio, anticipos y saldo pendiente.</p>
                            </div>
                            <i class="fas fa-chevron-down res-ci-breakdown-chevron" aria-hidden="true"></i>
                        </div>
                        <div class="res-ci-breakdown-lines">
                            <div class="res-ci-breakdown-line" id="resCiSubtotalRow">
                                <span>Subtotal antes de descuentos</span>
                                <strong id="resCiSubtotal">$0.00</strong>
                            </div>
                            <div class="res-ci-breakdown-line is-discount" id="resCiDiscountRow" hidden>
                                <span>Descuentos aplicados</span>
                                <strong id="resCiDiscount">-$0.00</strong>
                            </div>
                            <div class="res-ci-breakdown-line">
                                <span>Total de la reservacion</span>
                                <strong id="resCiTotalFinal">$0.00</strong>
                            </div>
                            <div class="res-ci-breakdown-line is-paid" id="resCiAdvanceRow" hidden>
                                <span>Anticipos registrados</span>
                                <strong id="resCiAdvances">-$0.00</strong>
                            </div>
                            <div class="res-ci-breakdown-line is-paid" id="resCiPaymentsRow" hidden>
                                <span>Pagos registrados</span>
                                <strong id="resCiPayments">-$0.00</strong>
                            </div>
                            <div class="res-ci-breakdown-line is-due">
                                <span>Saldo a cobrar en check-in</span>
                                <strong id="resCiDue">$0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="res-ci-shortcuts" aria-label="Atajos de metodo de pago">
                        <button type="button" class="res-ci-shortcut res-ci-shortcut--cash" onclick="aplicarPagoRapido('efectivo')">
                            <i class="fas fa-money-bill-wave"></i>Efectivo exacto
                        </button>
                        <button type="button" class="res-ci-shortcut res-ci-shortcut--card" onclick="aplicarPagoRapido('tarjeta')">
                            <i class="fas fa-credit-card"></i>Tarjeta exacta
                        </button>
                        <button type="button" class="res-ci-shortcut res-ci-shortcut--transfer" onclick="aplicarPagoRapido('transferencia')">
                            <i class="fas fa-university"></i>Transferencia exacta
                        </button>
                        <button type="button" class="res-ci-shortcut res-ci-shortcut--split" onclick="dividirPagoRapido()">
                            <i class="fas fa-code-branch"></i>Mitad efectivo/tarjeta
                        </button>
                        <button type="button" class="res-ci-shortcut res-ci-shortcut--cash-transfer" onclick="dividirPagoEfectivoTransferencia()">
                            <i class="fas fa-exchange-alt"></i>Efectivo + transferencia
                        </button>
                    </div>

                    <label class="res-ci-pending-option" for="check_saldo_pendiente">
                        <input type="checkbox" id="check_saldo_pendiente" onchange="toggleSaldoPendienteCheckIn()">
                        <span class="res-ci-pending-icon"><i class="fas fa-clock"></i></span>
                        <span>
                            <strong>Dejar saldo pendiente</strong>
                            <small>Permite hacer check-in con pago parcial. Lo que falte aparecera en Cuentas por cobrar.</small>
                        </span>
                    </label>

                    <div id="checkinPendingPreview" class="res-ci-pending-preview" hidden aria-live="polite">
                        <div>
                            <span>Pago de hoy</span>
                            <strong id="checkinPagoHoy">$0.00</strong>
                        </div>
                        <div>
                            <span>Quedara pendiente</span>
                            <strong id="checkinQuedaPendiente">$0.00</strong>
                        </div>
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
                                        <label for="monto_efectivo" id="label_monto_efectivo" class="res-ci-label">Monto a cobrar en efectivo</label>
                                        <input type="number" name="monto_efectivo" id="monto_efectivo" data-money-format="true" step="0.01" min="0" readonly oninput="calcularTotales()" onchange="calcularTotales()" class="res-ci-input">
                                    </div>
                                    <div>
                                        <label for="recibido_efectivo" id="label_recibido_efectivo" class="res-ci-label">Dinero recibido</label>
                                        <input type="number" name="recibido_efectivo" id="recibido_efectivo" data-money-format="true" step="0.01" min="0" placeholder="0.00" oninput="calcularCambio()" onchange="calcularCambio()" class="res-ci-input">
                                        <button type="button" class="res-ci-shortcut res-ci-shortcut--cash" style="width:100%; min-height:36px; margin-top:8px;" onclick="marcarEfectivoExacto()">Recibi exacto</button>
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
                                        <label for="monto_tarjeta" id="label_monto_tarjeta" class="res-ci-label">Monto con tarjeta</label>
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
                                        <label for="monto_transferencia" id="label_monto_transferencia" class="res-ci-label">Monto por transferencia</label>
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

                <section class="res-ci-section" data-ci-step="2">
                    <div class="res-ci-section-head">
                        <h4><i class="fas fa-file-invoice"></i>Factura</h4>
                        <span>Requerido</span>
                    </div>
                    <div id="facturaContainer" class="res-invoice-options">
                        <label id="label_factura_si" class="res-invoice-choice">
                            <input type="radio" name="requiere_factura" id="factura_si" value="si" onchange="seleccionarFactura('si')">
                            <span>
                                <strong>Factura para cliente</strong>
                                <span>Se genera solicitud de factura para el huesped.</span>
                            </span>
                        </label>
                        <label id="label_factura_no" class="res-invoice-choice">
                            <input type="radio" name="requiere_factura" id="factura_no" value="no" onchange="seleccionarFactura('no')">
                            <span>
                                <strong>Sin factura del cliente</strong>
                                <span>Si hay tarjeta o transferencia quedara como uso interno.</span>
                            </span>
                        </label>
                    </div>
                    <div id="facturaResultado" class="res-invoice-result">
                        <i class="fas fa-circle-info"></i>
                        <span>Selecciona una opcion para ver como quedara registrada la facturacion.</span>
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

                <section class="res-ci-summary" data-ci-step="2">
                    <h5>Resumen de pago</h5>
                    <div class="res-ci-row">
                        <span>Total a cobrar</span>
                        <strong id="resumenTotal">$0.00</strong>
                    </div>
                    <div class="res-ci-row is-paid">
                        <span>Total pagado</span>
                        <strong id="resumenPagado">$0.00</strong>
                    </div>
                    <div class="res-ci-row">
                        <span>Metodo seleccionado</span>
                        <strong id="resumenMetodoPago">Sin seleccionar</strong>
                    </div>
                    <div id="divRestante" class="res-ci-row is-due" style="display:none;">
                        <span>Cuenta pendiente</span>
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
                <button type="button" onclick="ciGoToStep(1)" class="res-ci-back" style="display:none;"><i class="fas fa-arrow-left"></i>Atras</button>
                <button type="button" onclick="ciStepNext()" class="res-ci-next" style="display:none;">Continuar<i class="fas fa-chevron-right"></i></button>
                <button type="submit" id="btnConfirmarCheckIn" class="res-ci-confirm">
                    <i class="fas fa-check"></i>
                    Confirmar check-in
                </button>
            </div>
        </form>
    </section>
</div>

<div id="modalExportarPDF" class="fixed inset-0 z-50 hidden" data-ms-overlay-close>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="res-export-card ms-anim-panel">
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
                <p id="fechaExportarError" class="res-export-error" aria-live="polite"></p>
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

<div id="modalExportarExcel" class="fixed inset-0 z-50 hidden" data-ms-overlay-close>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="res-export-card ms-anim-panel">
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
                <p id="fechaExportarExcelError" class="res-export-error" aria-live="polite"></p>
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

<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<script>
const baseUrl = '<?= url('') ?>';
const resIndexDefaultDate = <?= json_encode($fecha_filtro ?: date('Y-m-d')) ?>;
let filtroEstadoActual = 'todos';
let totalReservacion = 0;

let resReservaSwalTimer = null;

function resUpcomingToggle(headEl) {
    const section = headEl.closest('.res-upcoming');
    const isOpen = section.classList.toggle('is-open');
    headEl.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

function resIndexToast(mensaje, tipo = 'info', duracion = 5200) {
    let toast = document.getElementById('resIndexToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'resIndexToast';
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');
        document.body.appendChild(toast);
    }

    window.clearTimeout(toast._resTimer);
    toast.className = `res-inline-toast is-${tipo}`;
    toast.textContent = mensaje;
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    toast._resTimer = window.setTimeout(() => toast.classList.remove('is-visible'), duracion);
}

function resMostrarErrorExportacion(inputId, errorId, mensaje) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (error) {
        error.textContent = mensaje;
        error.classList.add('is-visible');
    }
    if (input) {
        input.classList.add('ms-form-invalid');
        input.focus();
    }
}

function resLimpiarErrorExportacion(inputId, errorId) {
    const input = document.getElementById(inputId);
    const error = document.getElementById(errorId);
    if (error) {
        error.textContent = '';
        error.classList.remove('is-visible');
    }
    input?.classList.remove('ms-form-invalid');
}

function resFechaValida(fechaTexto) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(fechaTexto || ''));
}

function resFormatearFechaLocal(fecha) {
    const anio = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    return `${anio}-${mes}-${dia}`;
}

function resObtenerDatosReservaDefault() {
    const hoy = new Date();
    const entradaBase = resFechaValida(resIndexDefaultDate)
        ? new Date(resIndexDefaultDate + 'T00:00:00')
        : new Date(hoy);
    const salidaBase = new Date(entradaBase);
    salidaBase.setDate(salidaBase.getDate() + 1);

    return {
        fechaEntrada: resFormatearFechaLocal(entradaBase),
        fechaSalida: resFormatearFechaLocal(salidaBase),
        horaActual: hoy.toTimeString().slice(0, 5)
    };
}

const resHotelCheckinHora = <?= json_encode($res_hotel_checkin_hora, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
const resHotelCheckinHoraLabel = resReservaFormatearHoraChip(resHotelCheckinHora);

function resReservaFormatearHoraChip(hora) {
    const match = String(hora || '').match(/^(\d{1,2}):(\d{2})/);
    if (!match) return '03:00 p. m.';

    const horas24 = parseInt(match[1], 10);
    const minutos = match[2];
    const periodo = horas24 >= 12 ? 'p. m.' : 'a. m.';
    const horas12 = horas24 % 12 || 12;

    return `${String(horas12).padStart(2, '0')}:${minutos} ${periodo}`;
}

function resCerrarSwalReserva(callback, delay = 160) {
    if (resReservaSwalTimer) {
        clearTimeout(resReservaSwalTimer);
        resReservaSwalTimer = null;
    }

    const run = () => {
        resReservaSwalTimer = null;
        if (typeof callback === 'function') {
            callback();
        }
    };

    const swalVisible = typeof Swal !== 'undefined'
        && typeof Swal.isVisible === 'function'
        && Swal.isVisible();

    if (swalVisible && typeof Swal.close === 'function') {
        Swal.close();
    }

    resReservaSwalTimer = window.setTimeout(run, swalVisible ? delay : Math.min(delay, 80));
}

function resAbrirSelectorNuevaReserva(event) {
    if (typeof Swal === 'undefined') {
        return true;
    }

    if (event) event.preventDefault();
    resMostrarSelectorTipoCliente(resObtenerDatosReservaDefault());
    return false;
}

function resReservaCalcularNoches(fechaEntrada, fechaSalida) {
    const entrada = new Date(fechaEntrada + 'T00:00:00');
    const salida = new Date(fechaSalida + 'T00:00:00');
    const diff = Math.round((salida - entrada) / 86400000);
    const noches = Number.isFinite(diff) && diff > 0 ? diff : 1;
    return noches + ' ' + (noches === 1 ? 'noche' : 'noches');
}

function resReservaSidebar(fechaEntrada, fechaSalida, paso) {
    const entradaLabel = resFormatearFechaReservaCorta(new Date(fechaEntrada + 'T00:00:00'));
    const salidaLabel = resFormatearFechaReservaCorta(new Date(fechaSalida + 'T00:00:00'));
    const nochesLabel = resReservaCalcularNoches(fechaEntrada, fechaSalida);
    const pasoActual = parseInt(paso, 10) || 1;

    return `
        <aside class="res-reserve-side" aria-label="Resumen de nueva reservacion">
            <span class="res-reserve-side__eyebrow">Nueva reservacion</span>
            <h2>Crear<br>reservacion</h2>
            <p>Configura los datos iniciales para preparar la estancia del huesped.</p>

            <div class="res-reserve-datebox">
                <div class="res-reserve-dateitem">
                    <span class="res-reserve-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Entrada</small><strong>${entradaLabel}</strong></span>
                </div>
                <div class="res-reserve-dateitem">
                    <span class="res-reserve-dateicon"><i class="fas fa-moon"></i></span>
                    <span><small>Noches</small><strong>${nochesLabel}</strong></span>
                </div>
                <div class="res-reserve-dateitem">
                    <span class="res-reserve-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Salida</small><strong>${salidaLabel}</strong></span>
                </div>
            </div>

            <ol class="res-reserve-steps" aria-label="Progreso">
                <li class="${pasoActual === 1 ? 'is-active' : 'is-complete'}">
                    <span>1</span>
                    <strong>Tipo de cliente</strong>
                </li>
                <li class="${pasoActual === 2 ? 'is-active' : ''}">
                    <span>2</span>
                    <strong>Hora de llegada</strong>
                </li>
            </ol>
        </aside>
    `;
}

function resReservaMobileIntro(fechaEntrada, fechaSalida, paso) {
    const entradaLabel = resFormatearFechaReservaCorta(new Date(fechaEntrada + 'T00:00:00'));
    const salidaLabel = resFormatearFechaReservaCorta(new Date(fechaSalida + 'T00:00:00'));
    const nochesLabel = resReservaCalcularNoches(fechaEntrada, fechaSalida);
    const pasoActual = parseInt(paso, 10) || 1;

    return `
        <div class="res-reserve-mobile-intro" role="group" aria-label="Resumen de nueva reservacion">
            <span class="res-reserve-mobile-kicker">Nueva reservacion</span>
            <h2 class="res-reserve-mobile-title">Crear reservacion</h2>
            <p class="res-reserve-mobile-copy">Configura los datos iniciales para preparar la estancia del huesped.</p>

            <div class="res-reserve-mobile-datebox">
                <div class="res-reserve-mobile-dateitem">
                    <span class="res-reserve-mobile-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Entrada</small><strong>${entradaLabel}</strong></span>
                </div>
                <div class="res-reserve-mobile-dateitem">
                    <span class="res-reserve-mobile-dateicon"><i class="fas fa-moon"></i></span>
                    <span><small>Noches</small><strong>${nochesLabel}</strong></span>
                </div>
                <div class="res-reserve-mobile-dateitem">
                    <span class="res-reserve-mobile-dateicon"><i class="fas fa-calendar-day"></i></span>
                    <span><small>Salida</small><strong>${salidaLabel}</strong></span>
                </div>
            </div>

            <div class="res-reserve-mobile-progress" aria-label="Progreso">
                <span class="res-reserve-mobile-progress-title">Paso ${pasoActual} de 2</span>
                <div class="res-reserve-mobile-steps" role="list">
                    <div class="res-reserve-mobile-step ${pasoActual === 1 ? 'is-active' : 'is-complete'}" role="listitem">
                        <span>1</span>
                        <strong>Tipo de cliente</strong>
                    </div>
                    <div class="res-reserve-mobile-step ${pasoActual === 2 ? 'is-active' : ''}" role="listitem">
                        <span>2</span>
                        <strong>Hora de llegada</strong>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function resElegirTipoCliente(button, tipo) {
    const shell = button?.closest('.res-reserve-shell');
    if (!shell) return;
    shell.dataset.tipo = tipo;
    shell.querySelectorAll('.res-reserve-choice').forEach(option => {
        option.classList.toggle('is-selected', option === button);
        option.setAttribute('aria-pressed', option === button ? 'true' : 'false');
    });
}

function resContinuarTipoClienteDesdeShell(fechaEntrada, fechaSalida, horaActual) {
    const shell = document.querySelector('.res-reserve-shell');
    const tipo = shell?.dataset.tipo || 'nuevo';
    resSeleccionarTipoCliente(tipo, fechaEntrada, fechaSalida, horaActual);
}

function resReservaSetHora(valor, button) {
    const input = document.getElementById('resHoraLlegadaRapida');
    if (input) input.value = valor;
    const shell = button?.closest('.res-reserve-shell');
    if (shell) {
        shell.querySelectorAll('.res-reserve-timechip').forEach(chip => chip.classList.remove('is-active'));
    }
    if (button) button.classList.add('is-active');
    const validation = document.getElementById('resReserveValidation');
    if (validation) validation.classList.add('hidden');
}

function resCrearReservacionDesdeHora(tipo, fechaEntrada, fechaSalida) {
    const input = document.getElementById('resHoraLlegadaRapida');
    const validation = document.getElementById('resReserveValidation');
    const horaSeleccionada = input ? input.value : '';

    if (!horaSeleccionada) {
        if (validation) validation.classList.remove('hidden');
        if (input) input.focus();
        return;
    }

    resContinuarReservacionDesdeModal(tipo, fechaEntrada, fechaSalida, horaSeleccionada);
}

function resMostrarSelectorTipoCliente(datosReserva) {
    const fechaEntrada = datosReserva.fechaEntrada;
    const fechaSalida = datosReserva.fechaSalida;
    const horaActual = datosReserva.horaActual;

    Swal.fire({
        title: '',
        html: `
            <div class="res-reserve-shell" data-tipo="nuevo">
                ${resReservaSidebar(fechaEntrada, fechaSalida, 1)}
                <section class="res-reserve-main" aria-label="Tipo de cliente">
                    ${resReservaMobileIntro(fechaEntrada, fechaSalida, 1)}
                    <span class="res-reserve-main__eyebrow">Paso 1 de 2</span>
                    <h3>&iquest;Para quien es la reservacion?</h3>
                    <p>Elige si vas a registrar un huesped nuevo o si la reservacion sera para un cliente que ya existe.</p>

                    <div class="res-reserve-choice-list" role="group" aria-label="Tipo de cliente">
                        <button onclick="resElegirTipoCliente(this, 'nuevo'); return false;"
                                type="button"
                                class="res-reserve-choice res-reserve-choice--new is-selected"
                                aria-pressed="true">
                            <span class="res-reserve-choice__icon"><i class="fas fa-user-plus"></i></span>
                            <span class="res-reserve-choice__copy">
                                <span><strong>Cliente nuevo</strong><em>Registro</em></span>
                                <small>Registra al huesped y vuelve al flujo con las fechas listas.</small>
                            </span>
                            <span class="res-reserve-choice__check"><i class="fas fa-check"></i></span>
                        </button>
                        <button onclick="resElegirTipoCliente(this, 'existente'); return false;"
                                type="button"
                                class="res-reserve-choice res-reserve-choice--existing"
                                aria-pressed="false">
                            <span class="res-reserve-choice__icon"><i class="fas fa-user-check"></i></span>
                            <span class="res-reserve-choice__copy">
                                <span><strong>Cliente registrado</strong><em>Existente</em></span>
                                <small>Continua directo a crear la reservacion y busca al huesped.</small>
                            </span>
                            <span class="res-reserve-choice__check"><i class="fas fa-check"></i></span>
                        </button>
                    </div>

                    <div class="res-reserve-footer res-reserve-footer--single">
                        <button type="button" onclick="resContinuarTipoClienteDesdeShell('${fechaEntrada}', '${fechaSalida}', '${horaActual}')" class="res-reserve-btn res-reserve-btn--primary">
                            Continuar <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </section>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: false,
        showCloseButton: true,
        allowOutsideClick: true,
        allowEscapeKey: true,
        returnFocus: false,
        closeButtonAriaLabel: 'Cerrar',
        width: '820px',
        customClass: {
            container: 'res-swal-reservation-container',
            popup: 'hb-swal hb-swal-client res-reserve-swal',
            htmlContainer: 'hb-swal-html'
        },
        buttonsStyling: false
    });
}

function resSeleccionarTipoCliente(tipo, fechaEntrada, fechaSalida, horaActual) {
    resCerrarSwalReserva(() => {
        Swal.fire({
            title: '',
            html: `
                <div class="res-reserve-shell" data-tipo="${tipo}">
                    ${resReservaSidebar(fechaEntrada, fechaSalida, 2)}
                    <section class="res-reserve-main" aria-label="Hora de llegada">
                        ${resReservaMobileIntro(fechaEntrada, fechaSalida, 2)}
                        <span class="res-reserve-main__eyebrow">Paso 2 de 2</span>
                        <h3>&iquest;A que hora llega?</h3>
                        <p>Elige una opcion rapida o escribe la hora. Tambien puedes capturarla despues dentro de la reservacion.</p>

                        <div class="res-reserve-timechips" aria-label="Opciones rapidas de hora">
                            <button type="button" onclick="resReservaSetHora('${horaActual}', this)" class="res-reserve-timechip is-active"><i class="far fa-clock"></i> Ahora</button>
                            <button type="button" onclick="resReservaSetHora(resHotelCheckinHora, this)" class="res-reserve-timechip" title="Hora de check-in configurada" aria-label="Usar hora de check-in configurada: ${resHotelCheckinHoraLabel}">${resHotelCheckinHoraLabel}</button>
                            <button type="button" onclick="resReservaSetHora('20:00', this)" class="res-reserve-timechip">08:00 p. m.</button>
                            <button type="button" onclick="resContinuarReservacionSinHora('${tipo}', '${fechaEntrada}', '${fechaSalida}'); return false;" class="res-reserve-timechip"><i class="far fa-calendar-plus"></i> Definir despues</button>
                        </div>

                        <label class="res-reserve-timefield" for="resHoraLlegadaRapida">
                            <i class="far fa-clock"></i>
                            <input type="time" id="resHoraLlegadaRapida" value="${horaActual}" class="hb-arrival-input" oninput="document.getElementById('resReserveValidation')?.classList.add('hidden')">
                        </label>
                        <p class="res-reserve-hint"><i class="far fa-eye"></i> Esta hora ayuda a preparar la habitacion a tiempo; no es obligatoria si decides definirla despues.</p>
                        <p id="resReserveValidation" class="res-reserve-validation hidden">Ingresa la hora de llegada o usa Definir despues.</p>

                        <div class="res-reserve-footer">
                            <button type="button" onclick="resCerrarSwalReserva(() => resMostrarSelectorTipoCliente({ fechaEntrada: '${fechaEntrada}', fechaSalida: '${fechaSalida}', horaActual: '${horaActual}' }), 90)" class="res-reserve-btn res-reserve-btn--ghost">
                                <i class="fas fa-arrow-left"></i> Volver
                            </button>
                            <button type="button" onclick="resCrearReservacionDesdeHora('${tipo}', '${fechaEntrada}', '${fechaSalida}')" class="res-reserve-btn res-reserve-btn--primary">
                                <i class="fas fa-check"></i> Crear reservacion
                            </button>
                        </div>
                    </section>
                </div>
            `,
            showConfirmButton: false,
            showCancelButton: false,
            showCloseButton: true,
            allowOutsideClick: true,
            allowEscapeKey: true,
            returnFocus: false,
            closeButtonAriaLabel: 'Cerrar',
            width: '820px',
            customClass: {
                container: 'res-swal-reservation-container',
                popup: 'hb-swal hb-swal-arrival res-reserve-swal',
                htmlContainer: 'hb-swal-html'
            },
            buttonsStyling: false
        });
    });
}

function resContinuarReservacionSinHora(tipo, fechaEntrada, fechaSalida) {
    resCerrarSwalReserva(() => {
        resContinuarReservacionDesdeModal(tipo, fechaEntrada, fechaSalida, '');
    }, 80);
}

function resContinuarReservacionDesdeModal(tipo, fechaEntrada, fechaSalida, horaSeleccionada) {
    const horaFinal = horaSeleccionada || '';

    try {
        sessionStorage.setItem('reservacionRapida', JSON.stringify({
            habitacionId: null,
            fechaEntrada: fechaEntrada,
            fechaSalida: fechaSalida,
            horaLlegada: horaFinal || null,
            origen: 'reservaciones_index'
        }));
    } catch (error) {
        // Si sessionStorage no esta disponible, la URL conserva los datos necesarios.
    }

    if (tipo === 'nuevo') {
        const params = new URLSearchParams({
            return_to: 'reservacion_rapida',
            fecha_entrada: fechaEntrada,
            fecha_salida: fechaSalida
        });

        if (horaFinal) {
            params.set('hora_llegada', horaFinal);
        }

        window.location.href = '<?= url('huespedes/create') ?>?' + params.toString();
        return;
    }

    const params = new URLSearchParams({
        fecha_entrada: fechaEntrada,
        fecha_salida: fechaSalida
    });

    if (horaFinal) {
        params.set('hora_llegada', horaFinal);
    }

    window.location.href = '<?= url('reservaciones/crear') ?>?' + params.toString();
}

function resFormatearFechaCorta(fecha) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return `${fecha.getDate()} ${meses[fecha.getMonth()]}`;
}

function resFormatearFechaReservaCorta(fecha) {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const anio = String(fecha.getFullYear()).slice(-2);
    return `${fecha.getDate()} ${meses[fecha.getMonth()]} ${anio}`;
}

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

    actualizarResultadoFacturaCheckIn();
}

function mostrarInfoFacturaInterna() {
    const infoInterna = document.getElementById('facturaInfoInterna');
    const facturaNo = document.getElementById('factura_no');
    const checkTarjeta = document.getElementById('check_tarjeta');
    const checkTransferencia = document.getElementById('check_transferencia');
    if (!infoInterna) return;

    const debeMostrarse = facturaNo?.checked && ((checkTarjeta && checkTarjeta.checked) || (checkTransferencia && checkTransferencia.checked));
    infoInterna.classList.toggle('hidden', !debeMostrarse);
    actualizarResultadoFacturaCheckIn();
}

function actualizarResultadoFacturaCheckIn() {
    const resultado = document.getElementById('facturaResultado');
    if (!resultado) return;

    const facturaSi = document.getElementById('factura_si');
    const facturaNo = document.getElementById('factura_no');
    const checkTarjeta = document.getElementById('check_tarjeta');
    const checkTransferencia = document.getElementById('check_transferencia');
    const usaElectronico = Boolean((checkTarjeta && checkTarjeta.checked) || (checkTransferencia && checkTransferencia.checked));

    resultado.className = 'res-invoice-result';

    if (facturaSi?.checked) {
        resultado.classList.add('is-client');
        resultado.innerHTML = '<i class="fas fa-file-invoice"></i><span>Factura para cliente: se creara solicitud para facturacion del huesped con el metodo de pago seleccionado.</span>';
        return;
    }

    if (facturaNo?.checked && usaElectronico) {
        resultado.classList.add('is-internal');
        resultado.innerHTML = '<i class="fas fa-building"></i><span>Uso interno del hotel: como hay tarjeta o transferencia, se dejara registro interno para control administrativo.</span>';
        return;
    }

    if (facturaNo?.checked) {
        resultado.classList.add('is-none');
        resultado.innerHTML = '<i class="fas fa-receipt"></i><span>Sin facturacion: no se generara solicitud de factura para esta reservacion.</span>';
        return;
    }

    resultado.innerHTML = '<i class="fas fa-circle-info"></i><span>Selecciona una opcion para ver como quedara registrada la facturacion.</span>';
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
    actualizarResultadoFacturaCheckIn();
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

function resSplitMoneyParts(total, count) {
    const safeCount = Math.max(1, parseInt(count, 10) || 1);
    const totalCents = Math.round((parseFloat(total) || 0) * 100);
    const baseCents = Math.floor(totalCents / safeCount);
    const remainder = totalCents - (baseCents * safeCount);

    return Array.from({ length: safeCount }, function(_, index) {
        return (baseCents + (index < remainder ? 1 : 0)) / 100;
    });
}

function resCheckInNumber(value, fallback) {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : (fallback || 0);
}

function resCheckInSetMoney(id, value, prefix) {
    const el = document.getElementById(id);
    if (!el) return;
    el.textContent = (prefix || '') + formatMoney(value);
}

function resCheckInToggleRow(id, show) {
    const el = document.getElementById(id);
    if (!el) return;
    el.hidden = !show;
}

function actualizarDesgloseCheckIn(data, fallbackTotal, estado) {
    const box = document.getElementById('resCiBreakdown');
    if (!box) return;

    const info = data || {};
    const total = Math.max(0, resCheckInNumber(info.total, fallbackTotal));
    const descuento = Math.max(0, resCheckInNumber(info.descuento_total, 0));
    const pagos = Math.max(0, resCheckInNumber(info.pagos, 0));
    const abonos = Math.max(0, resCheckInNumber(info.abonos, 0));
    const pagado = Math.max(0, resCheckInNumber(info.pagado, pagos + abonos));
    const saldo = Math.max(0, resCheckInNumber(info.saldo, Math.max(0, total - pagado)));
    let subtotal = resCheckInNumber(info.subtotal || info.total_antes_descuento, total + descuento);

    if (subtotal <= 0 || subtotal < total) {
        subtotal = total + descuento;
    }

    const sub = document.getElementById('resCiBreakdownSub');
    if (sub) {
        if (estado && estado.loading) {
            sub.textContent = 'Consultando anticipos, pagos y descuentos registrados.';
        } else if (estado && estado.error) {
            sub.textContent = 'No se pudo cargar el desglose completo; se muestra el total disponible.';
        } else {
            sub.textContent = 'Incluye descuentos, anticipos y pagos ya registrados para esta reservacion.';
        }
    }

    resCheckInSetMoney('resCiSubtotal', subtotal);
    resCheckInSetMoney('resCiDiscount', descuento, '-');
    resCheckInSetMoney('resCiTotalFinal', total);
    resCheckInSetMoney('resCiAdvances', abonos, '-');
    resCheckInSetMoney('resCiPayments', pagos, '-');
    resCheckInSetMoney('resCiDue', saldo);

    resCheckInToggleRow('resCiSubtotalRow', descuento > 0.004);
    resCheckInToggleRow('resCiDiscountRow', descuento > 0.004);
    resCheckInToggleRow('resCiAdvanceRow', abonos > 0.004);
    resCheckInToggleRow('resCiPaymentsRow', pagos > 0.004);
    box.hidden = false;
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
    const returnInput = document.getElementById('checkin_return_to');
    if (returnInput) {
        returnInput.value = 'reservaciones' + window.location.search;
    }
    resetearFormularioPago();
    resetearFacturaCheckIn();

    totalLabel.textContent = formatMoney(totalReservacion);
    if (resumenTotal) resumenTotal.textContent = formatMoney(totalReservacion);
    actualizarDesgloseCheckIn({ total: totalReservacion, saldo: totalReservacion }, totalReservacion, { loading: true });

    const hora = form.querySelector('input[name="hora_entrada"]');
    if (hora) hora.value = new Date().toTimeString().slice(0, 5);

    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');

    // En movil el cobro se divide en pasos (Cobro -> Factura); en escritorio se ve todo.
    ciResetSteps();

    // Saldo-aware: cobrar el SALDO (total menos anticipos ya pagados), no el total bruto.
    fetch(baseUrl + '/api/reservaciones/' + id + '/resumen-pagos', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d && d.success && d.data) {
                var saldo = parseFloat(d.data.saldo);
                actualizarDesgloseCheckIn(d.data, totalReservacion);
                if (!isNaN(saldo) && saldo >= 0 && saldo < totalReservacion - 0.004) {
                    totalReservacion = saldo;
                    totalLabel.textContent = formatMoney(saldo);
                    if (resumenTotal) resumenTotal.textContent = formatMoney(saldo);
                    resetearFormularioPago();
                }
            }
        })
        .catch(function () {
            actualizarDesgloseCheckIn({ total: totalReservacion, saldo: totalReservacion }, totalReservacion, { error: true });
        });
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

function isSaldoPendienteCheckInActivo() {
    return document.getElementById('check_saldo_pendiente')?.checked === true;
}

function setSaldoPendienteCheckInHidden(activo) {
    const hidden = document.getElementById('permitir_saldo_pendiente');
    if (hidden) hidden.value = activo ? '1' : '0';
}

function setCheckInText(id, text) {
    const element = document.getElementById(id);
    if (element) element.textContent = text;
}

function actualizarVistaSaldoPendienteCheckIn(totalPagado = null) {
    const activo = isSaldoPendienteCheckInActivo();
    const pagoHoy = totalPagado === null ? calcularTotalPagado() : totalPagado;
    const pendiente = Math.max(0, totalReservacion - pagoHoy);
    const preview = document.getElementById('checkinPendingPreview');

    setCheckInText('label_monto_efectivo', activo ? 'Pago de hoy en efectivo' : 'Monto a cobrar en efectivo');
    setCheckInText('label_recibido_efectivo', activo ? 'Dinero recibido hoy' : 'Dinero recibido');
    setCheckInText('label_monto_tarjeta', activo ? 'Pago de hoy con tarjeta' : 'Monto con tarjeta');
    setCheckInText('label_monto_transferencia', activo ? 'Pago de hoy por transferencia' : 'Monto por transferencia');

    setCheckInText('checkinPagoHoy', formatMoney(pagoHoy));
    setCheckInText('checkinQuedaPendiente', formatMoney(pendiente));

    if (preview) {
        preview.hidden = !activo;
        preview.style.display = activo ? 'grid' : 'none';
    }
}

function ajustarEfectivoPendienteDesdeRecibido() {
    if (!isSaldoPendienteCheckInActivo()) return false;

    const checkEfectivo = document.getElementById('check_efectivo');
    const montoInput = document.getElementById('monto_efectivo');
    const recibidoInput = document.getElementById('recibido_efectivo');

    if (!checkEfectivo?.checked || !montoInput || !recibidoInput) return false;

    if (document.activeElement === montoInput) {
        montoInput.dataset.saldoPendienteManual = '1';
        return false;
    }

    const recibido = resMoneyRead(recibidoInput);
    const totalPagadoOtros = calcularTotalPagadoSinEfectivo();
    const maximoEfectivo = Math.max(0, totalReservacion - totalPagadoOtros);
    const montoActual = resMoneyRead(montoInput);
    const montoNuevo = Math.min(recibido, maximoEfectivo);
    const montoManual = montoInput.dataset.saldoPendienteManual === '1';

    if (document.activeElement === recibidoInput && !montoManual) {
        if (Math.abs(montoActual - montoNuevo) > 0.01) {
            resMoneySet(montoInput, montoNuevo);
            return true;
        }
        return false;
    }

    if (recibido <= 0 || montoManual) return false;

    const montoCompletoAutomatico = montoActual <= 0.01 || Math.abs(montoActual - maximoEfectivo) < 0.01;

    if (montoNuevo > 0 && recibido < montoActual - 0.01 && montoCompletoAutomatico) {
        resMoneySet(montoInput, montoNuevo);
        return true;
    }

    if (montoActual <= 0.01 && montoNuevo > 0) {
        resMoneySet(montoInput, montoNuevo);
        return true;
    }

    return false;
}

function toggleSaldoPendienteCheckIn() {
    const activo = isSaldoPendienteCheckInActivo();
    const montoEfectivo = document.getElementById('monto_efectivo');
    const checkEfectivo = document.getElementById('check_efectivo');
    const recibidoEfectivo = document.getElementById('recibido_efectivo');
    if (montoEfectivo) {
        montoEfectivo.readOnly = !activo;
        delete montoEfectivo.dataset.saldoPendienteManual;
        if (activo && checkEfectivo?.checked) {
            const recibido = resMoneyRead(recibidoEfectivo);
            resMoneySet(montoEfectivo, Math.max(0, Math.min(recibido, totalReservacion)));
        }
    }
    setSaldoPendienteCheckInHidden(activo);
    actualizarVistaSaldoPendienteCheckIn();
    calcularTotales({ preserveCash: activo });
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
            const recibidoInput = document.getElementById('recibido_efectivo');
            if (isSaldoPendienteCheckInActivo()) {
                delete montoInput.dataset.saldoPendienteManual;
                const recibido = resMoneyRead(recibidoInput);
                resMoneySet(montoInput, Math.min(recibido, montoRestante));
            } else if (resMoneyRead(montoInput) <= 0) {
                resMoneySet(montoInput, montoRestante);
            }
            montoInput.readOnly = !isSaldoPendienteCheckInActivo();
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

function setCheckInPaymentChecked(metodo, checked, shouldRecalculate = true) {
    const checkbox = document.getElementById('check_' + metodo);
    const panel = document.getElementById('panel_' + metodo);
    const montoInput = document.getElementById('monto_' + metodo);
    const card = checkbox?.closest('.res-pay-method');

    if (!checkbox) return false;

    checkbox.checked = checked;

    if (checked) {
        if (panel) panel.classList.remove('hidden');
        if (card) card.classList.add('is-open');
        if (metodo === 'efectivo' && montoInput) {
            montoInput.readOnly = !isSaldoPendienteCheckInActivo();
        }
    } else {
        if (panel) panel.classList.add('hidden');
        if (card) card.classList.remove('is-open');
        if (montoInput) {
            montoInput.value = '';
            delete montoInput.dataset.saldoPendienteManual;
        }

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

    if (shouldRecalculate) {
        calcularTotales({ preserveCash: true });
        mostrarInfoFacturaInterna();
    }

    return true;
}

function aplicarPagoRapido(metodo) {
    if (!['efectivo', 'tarjeta', 'transferencia'].includes(metodo)) return;

    resetearFormularioPago();
    setCheckInPaymentChecked(metodo, true, false);
    resMoneySet('monto_' + metodo, totalReservacion);

    if (metodo === 'efectivo') {
        resMoneySet('recibido_efectivo', totalReservacion);
        calcularCambio();
    }

    calcularTotales({ preserveCash: true });
    mostrarMensaje('Pago exacto aplicado. Revisa la factura antes de confirmar.', 'success');
}

function marcarEfectivoExacto() {
    setCheckInPaymentChecked('efectivo', true, false);
    calcularTotales();
    const montoEfectivo = resMoneyRead(document.getElementById('monto_efectivo'));
    resMoneySet('recibido_efectivo', montoEfectivo);
    calcularCambio();
}

function dividirPagoRapido() {
    resetearFormularioPago();
    const partes = resSplitMoneyParts(totalReservacion, 2);

    setCheckInPaymentChecked('efectivo', true, false);
    setCheckInPaymentChecked('tarjeta', true, false);
    resMoneySet('monto_efectivo', partes[0]);
    resMoneySet('recibido_efectivo', partes[0]);
    resMoneySet('monto_tarjeta', partes[1]);

    calcularTotales({ preserveCash: true });
    calcularCambio();
    mostrarMensaje('Pago dividido entre efectivo y tarjeta.', 'success');
}

function dividirPagoEfectivoTransferencia() {
    resetearFormularioPago();
    const partes = resSplitMoneyParts(totalReservacion, 2);

    setCheckInPaymentChecked('efectivo', true, false);
    setCheckInPaymentChecked('transferencia', true, false);
    resMoneySet('monto_efectivo', partes[0]);
    resMoneySet('recibido_efectivo', partes[0]);
    resMoneySet('monto_transferencia', partes[1]);

    calcularTotales({ preserveCash: true });
    calcularCambio();
    mostrarMensaje('Pago dividido entre efectivo y transferencia.', 'success');
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

function actualizarResumenMetodoPago() {
    const resumenMetodo = document.getElementById('resumenMetodoPago');
    if (!resumenMetodo) return;

    const labels = {
        efectivo: 'Efectivo',
        tarjeta: 'Tarjeta',
        transferencia: 'Transferencia'
    };
    const metodos = ['efectivo', 'tarjeta', 'transferencia'].filter(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        return checkbox && checkbox.checked;
    });

    resumenMetodo.textContent = metodos.length ? metodos.map(metodo => labels[metodo] || metodo).join(' + ') : 'Sin seleccionar';
}

function calcularTotales(options = {}) {
    const preserveCash = options && options.preserveCash === true;
    const skipCashChange = options && options.skipCashChange === true;
    const permitirPendiente = isSaldoPendienteCheckInActivo();
    ajustarEfectivoPendienteDesdeRecibido();
    const totalOtros = calcularTotalPagadoSinEfectivo();
    let totalPagado = totalOtros;
    const checkEfectivo = document.getElementById('check_efectivo');
    const montoEfectivoInput = document.getElementById('monto_efectivo');

    if (checkEfectivo?.checked && montoEfectivoInput) {
        montoEfectivoInput.readOnly = !permitirPendiente;
        if (permitirPendiente || preserveCash) {
            totalPagado += resMoneyRead(montoEfectivoInput);
        } else {
            const montoRestante = Math.max(0, totalReservacion - totalOtros);
            resMoneySet(montoEfectivoInput, montoRestante);
            totalPagado += montoRestante;
        }
    }

    const resumenPagado = document.getElementById('resumenPagado');
    if (resumenPagado) resumenPagado.textContent = formatMoney(totalPagado);

    const totalPagadoFinal = calcularTotalPagado();
    const diferencia = totalReservacion - totalPagadoFinal;
    actualizarVistaSaldoPendienteCheckIn(totalPagadoFinal);
    const divRestante = document.getElementById('divRestante');
    const divCambio = document.getElementById('divCambio');
    const btnConfirmar = document.getElementById('btnConfirmarCheckIn');
    const resumenRestante = document.getElementById('resumenRestante');
    const haySaldoPendiente = diferencia > 0.01;

    if (divRestante) divRestante.style.display = 'none';
    if (divCambio) divCambio.style.display = 'none';
    setSaldoPendienteCheckInHidden(permitirPendiente && haySaldoPendiente);

    if (checkEfectivo?.checked && !skipCashChange) calcularCambio();
    const efectivoInsuficiente = checkEfectivo?.checked && montoEfectivoInput
        && resMoneyRead(montoEfectivoInput) > 0
        && resMoneyRead(document.getElementById('recibido_efectivo')) < resMoneyRead(montoEfectivoInput);

    if (efectivoInsuficiente) {
        mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    } else if (Math.abs(diferencia) < 0.01) {
        ocultarMensaje();
        if (btnConfirmar) btnConfirmar.disabled = false;
    } else if (diferencia > 0.01) {
        if (divRestante) {
            divRestante.style.display = 'flex';
            if (resumenRestante) resumenRestante.textContent = formatMoney(diferencia);
        }

        if (permitirPendiente && totalPagadoFinal > 0) {
            mostrarMensaje('Se hara check-in y el saldo restante quedara en Cuentas por cobrar.', 'info');
            if (btnConfirmar) btnConfirmar.disabled = false;
        } else {
            mostrarMensaje('Falta completar el pago. Si el huesped pagara despues, activa Dejar saldo pendiente.', 'warning');
            if (btnConfirmar) btnConfirmar.disabled = true;
        }
    } else {
        mostrarMensaje('El monto total excede el precio de la reservacion', 'error');
        if (btnConfirmar) btnConfirmar.disabled = true;
    }

    actualizarResumenMetodoPago();
    actualizarResultadoFacturaCheckIn();
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

    const montoAjustadoPorPendiente = ajustarEfectivoPendienteDesdeRecibido();
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

    if (montoAjustadoPorPendiente) {
        calcularTotales({ preserveCash: true, skipCashChange: true });
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
    // Avisos de validación → toast flotante (no empuja el layout del cobro).
    if (window.msToast) { window.msToast(tipo || 'info', null, mensaje); return; }

    // Fallback al aviso inline si el toast no está disponible.
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
        if (montoInput) {
            montoInput.value = '';
            delete montoInput.dataset.saldoPendienteManual;
        }
        if (metodo === 'efectivo' && montoInput) montoInput.readOnly = true;
        if (card) card.classList.remove('is-open');
    });

    const saldoPendienteCheck = document.getElementById('check_saldo_pendiente');
    if (saldoPendienteCheck) saldoPendienteCheck.checked = false;
    setSaldoPendienteCheckInHidden(false);
    actualizarVistaSaldoPendienteCheckIn(0);

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
    actualizarResumenMetodoPago();
    actualizarResultadoFacturaCheckIn();
}

// ====== Check-in por pasos: solo se activa en la vista movil (<=640px) ======
let ciStepMode = false;
let ciCurrentStep = 1;
const CI_TOTAL_STEPS = 2;

function ciIsMobileCheckIn() {
    return window.matchMedia('(max-width: 640px)').matches;
}

function ciApplyStepUI() {
    const card = document.querySelector('#modalCheckIn .res-checkin-card');
    if (!card) return;
    const pill = document.getElementById('ciStepPill');
    const btnCancel = card.querySelector('.res-ci-cancel');
    const btnConfirm = card.querySelector('#btnConfirmarCheckIn');
    const btnBack = card.querySelector('.res-ci-back');
    const btnNext = card.querySelector('.res-ci-next');
    const steps = card.querySelectorAll('[data-ci-step]');

    if (!ciStepMode) {
        card.classList.remove('ci-stepped');
        steps.forEach(s => s.classList.remove('ci-step-hidden'));
        if (pill) pill.hidden = true;
        if (btnCancel) btnCancel.style.display = '';
        if (btnConfirm) btnConfirm.style.display = '';
        if (btnBack) btnBack.style.display = 'none';
        if (btnNext) btnNext.style.display = 'none';
        return;
    }

    card.classList.add('ci-stepped');
    steps.forEach(s => {
        s.classList.toggle('ci-step-hidden', String(s.dataset.ciStep) !== String(ciCurrentStep));
    });
    if (pill) {
        pill.hidden = false;
        pill.textContent = 'Paso ' + ciCurrentStep + ' de ' + CI_TOTAL_STEPS;
    }
    const enStep1 = ciCurrentStep <= 1;
    if (btnCancel) btnCancel.style.display = enStep1 ? '' : 'none';
    if (btnNext) btnNext.style.display = enStep1 ? '' : 'none';
    if (btnBack) btnBack.style.display = enStep1 ? 'none' : '';
    if (btnConfirm) btnConfirm.style.display = enStep1 ? 'none' : '';
}

function ciGoToStep(step) {
    ciCurrentStep = Math.min(CI_TOTAL_STEPS, Math.max(1, step));
    ciApplyStepUI();
    const body = document.querySelector('#modalCheckIn .res-checkin-body');
    if (body) body.scrollTop = 0;
}

function ciFocusStep(step) {
    if (ciStepMode && ciCurrentStep !== step) ciGoToStep(step);
}

function ciStepNext() {
    if (!ciValidarYPrepararPago()) return;
    ciGoToStep(2);
}

function ciResetSteps() {
    ciStepMode = ciIsMobileCheckIn();
    ciCurrentStep = 1;
    // En movil el desglose arranca colapsado (solo el encabezado); en escritorio siempre visible.
    const box = document.getElementById('resCiBreakdown');
    if (box) {
        box.classList.toggle('ci-collapsed', ciStepMode);
        const head = box.querySelector('.res-ci-breakdown-head');
        if (head) head.setAttribute('aria-expanded', ciStepMode ? 'false' : 'true');
    }
    ciApplyStepUI();
}

function ciToggleBreakdown() {
    if (!ciStepMode) return;
    const box = document.getElementById('resCiBreakdown');
    if (!box) return;
    const collapsed = box.classList.toggle('ci-collapsed');
    const head = box.querySelector('.res-ci-breakdown-head');
    if (head) head.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
}

// Valida el cobro (metodos, tarjeta, saldo, efectivo) y prepara el saldo pendiente.
// Devuelve true si el pago es valido; muestra el aviso correspondiente si no.
function ciValidarYPrepararPago() {
    const metodosSeleccionados = ['efectivo', 'tarjeta', 'transferencia'].filter(metodo => {
        const checkbox = document.getElementById('check_' + metodo);
        return checkbox && checkbox.checked;
    });

    if (metodosSeleccionados.length === 0 && totalReservacion > 0.01) {
        mostrarMensaje('Debe seleccionar al menos un metodo de pago', 'error');
        return false;
    }

    const checkTarjeta = document.getElementById('check_tarjeta');
    if (checkTarjeta?.checked) {
        const tipoTarjetaSeleccionado = document.querySelector('input[name="tipo_tarjeta"]:checked');
        if (!tipoTarjetaSeleccionado) {
            mostrarMensaje('Debe seleccionar el tipo de tarjeta: credito o debito', 'error');
            document.getElementById('panel_tarjeta')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return false;
        }
    }

    ajustarEfectivoPendienteDesdeRecibido();

    const totalPagado = calcularTotalPagado();
    const saldoRestante = totalReservacion - totalPagado;
    const permitirPendiente = isSaldoPendienteCheckInActivo();
    setSaldoPendienteCheckInHidden(permitirPendiente && saldoRestante > 0.01);

    if (saldoRestante > 0.01 && (!permitirPendiente || totalPagado <= 0.01)) {
        mostrarMensaje('Para hacer check-in con pago parcial, activa Dejar saldo pendiente y registra el monto recibido.', 'error');
        return false;
    }

    if (saldoRestante < -0.01) {
        mostrarMensaje('El monto total excede el precio de la reservacion', 'error');
        return false;
    }

    const checkEfectivo = document.getElementById('check_efectivo');
    if (checkEfectivo?.checked) {
        const recibido = resMoneyRead(document.getElementById('recibido_efectivo'));
        const montoPagar = resMoneyRead(document.getElementById('monto_efectivo'));

        if (montoPagar > 0 && recibido < montoPagar) {
            mostrarMensaje('El monto recibido en efectivo es insuficiente', 'error');
            return false;
        }
    }

    return true;
}

document.getElementById('formCheckInModal')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!validarFacturaCheckIn()) {
        ciFocusStep(2);
        document.getElementById('facturaContainer')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    if (!ciValidarYPrepararPago()) {
        ciFocusStep(1);
        return;
    }

    resMoneySanitize(this);
    this.submit();
});

(function mostrarResultadoCheckInIndex() {
    const params = new URLSearchParams(window.location.search);
    const reservacionId = parseInt(params.get('checkin_ok') || '0', 10);
    if (!reservacionId) return;

    params.delete('checkin_ok');
    const cleanQuery = params.toString();
    const cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '') + window.location.hash;
    window.history.replaceState({}, document.title, cleanUrl);

    const verUrl = baseUrl + '/reservaciones/ver/' + reservacionId;
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Check-in registrado',
            text: 'La reservacion paso a check-in y el listado ya se actualizo.',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-eye"></i> Ver reservacion',
            cancelButtonText: 'Quedarme aqui',
            confirmButtonColor: 'var(--brand-primary, #1B2746)',
            cancelButtonColor: '#6B7280',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = verUrl;
            }
        });
        return;
    }

    if (typeof resIndexToast === 'function') {
        resIndexToast('Check-in registrado. El listado ya se actualizo.', 'success');
    }
})();

function confirmarCheckOut(id) {
    const form = document.getElementById('formCheckOut');
    if (!form) {
        resIndexToast('No se encontró el formulario para registrar el check-out.', 'error');
        return;
    }

    const enviarCheckOut = async function() {
        // Selector opcional de responsable de limpieza (cancelable)
        if (window.CheckoutLimpieza) {
            const asignaciones = await CheckoutLimpieza.seleccionar({
                infoUrl: baseUrl + '/api/reservaciones/' + id + '/limpieza-personal'
            });
            if (asignaciones === null) return; // usuario cancelo el check-out
            CheckoutLimpieza.aplicarAForm(form, asignaciones);
        }
        const horaSalida = form.querySelector('input[name="hora_salida"]');
        if (horaSalida) horaSalida.value = new Date().toTimeString().slice(0, 8);
        form.action = baseUrl + '/reservaciones/check-out/' + id;
        form.submit();
    };

    if (typeof Swal === 'undefined') {
        if (window.confirm('Registrar check-out de la reservacion #' + id + '?')) {
            enviarCheckOut();
        }
        return;
    }

    Swal.fire({
        title: 'Confirmar check-out',
        html: `
            <div class="res-checkout-confirm">
                <p class="res-checkout-confirm__lead">Reservacion #${id}. Se registrara la salida del huesped y las habitaciones pasaran a limpieza.</p>
                <p class="res-checkout-confirm__note">
                    <i class="fas fa-circle-info"></i>
                    <span>Esta accion actualiza el estado operativo de la reservacion. Revisa que el huesped ya haya desocupado antes de confirmar.</span>
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-right-from-bracket"></i> Registrar check-out',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#EA580C',
        cancelButtonColor: '#6B7280',
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'res-swal-checkout',
            confirmButton: 'res-swal-checkout-confirm',
            cancelButton: 'res-swal-checkout-cancel'
        }
    }).then(function(result) {
        if (result.isConfirmed) {
            enviarCheckOut();
        }
    });
}

function abrirModalExportarPDF() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
    const modal = document.getElementById('modalExportarPDF');
    if (modal) {
        const fecha = document.getElementById('fechaExportar');
        if (fecha) fecha.value = new Date().toISOString().split('T')[0];
        resLimpiarErrorExportacion('fechaExportar', 'fechaExportarError');
        if (window.msModal) {
            window.msModal.open(modal);
        } else {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }
}

function cerrarModalExportarPDF() {
    const sidebar = document.getElementById('sidebar');
    const modal = document.getElementById('modalExportarPDF');
    const restoreSidebar = function() { if (sidebar) sidebar.style.display = ''; };
    if (modal && window.msModal) {
        window.msModal.close(modal, { onClose: restoreSidebar });
    } else {
        restoreSidebar();
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
}

document.getElementById('formExportarPDF')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fecha = document.getElementById('fechaExportar')?.value;
    if (!fecha) {
        resMostrarErrorExportacion('fechaExportar', 'fechaExportarError', 'Selecciona una fecha para generar el PDF.');
        return;
    }
    const url = baseUrl + '/reservaciones/exportar-pdf?fecha=' + encodeURIComponent(fecha);
    const mobileFileHelper = window.MedisoftMobileFiles;
    const ventana = mobileFileHelper
        ? mobileFileHelper.open(url, { label: 'PDF de reservaciones' })
        : window.open(url, '_blank');
    if (!ventana && !mobileFileHelper) {
        resIndexToast('El navegador bloqueó la nueva pestaña. Abriré el PDF aquí.', 'warning');
        window.location.href = url;
        return;
    }
    cerrarModalExportarPDF();
});

document.getElementById('fechaExportar')?.addEventListener('input', function() {
    resLimpiarErrorExportacion('fechaExportar', 'fechaExportarError');
});

function abrirModalExportarExcel() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
    const modal = document.getElementById('modalExportarExcel');
    if (modal) {
        const fecha = document.getElementById('fechaExportarExcel');
        if (fecha) fecha.value = new Date().toISOString().split('T')[0];
        resLimpiarErrorExportacion('fechaExportarExcel', 'fechaExportarExcelError');
        if (window.msModal) {
            window.msModal.open(modal);
        } else {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    }
}

function cerrarModalExportarExcel() {
    const sidebar = document.getElementById('sidebar');
    const modal = document.getElementById('modalExportarExcel');
    const restoreSidebar = function() { if (sidebar) sidebar.style.display = ''; };
    if (modal && window.msModal) {
        window.msModal.close(modal, { onClose: restoreSidebar });
    } else {
        restoreSidebar();
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
}

document.getElementById('formExportarExcel')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const fecha = document.getElementById('fechaExportarExcel')?.value;
    if (!fecha) {
        resMostrarErrorExportacion('fechaExportarExcel', 'fechaExportarExcelError', 'Selecciona una fecha para descargar Excel.');
        return;
    }
    const url = baseUrl + '/reservaciones/exportar-excel?fecha=' + encodeURIComponent(fecha);
    const ventana = window.open(url, '_blank');
    if (!ventana) {
        resIndexToast('El navegador bloqueó la nueva pestaña. Abriré el Excel aquí.', 'warning');
        window.location.href = url;
        return;
    }
    cerrarModalExportarExcel();
});

document.getElementById('fechaExportarExcel')?.addEventListener('input', function() {
    resLimpiarErrorExportacion('fechaExportarExcel', 'fechaExportarExcelError');
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


// Clic en cualquier parte de la fila/tarjeta de reservacion -> abre su detalle.
// Respeta enlaces y botones internos (folio, telefono, check-in/out, acciones).
(function () {
    function destino(target) {
        if (target.closest('a, button, input, textarea, select, label')) return null;
        return target.closest('.reservation-item.is-linked[data-href], .res-upcoming-card.is-linked[data-href]');
    }

    document.addEventListener('click', function (e) {
        const row = destino(e.target);
        if (!row) return;
        const href = row.getAttribute('data-href');
        if (!href) return;
        if (e.ctrlKey || e.metaKey || e.button === 1) {
            window.open(href, '_blank', 'noopener');
        } else {
            window.location.href = href;
        }
    });

    document.addEventListener('auxclick', function (e) {
        if (e.button !== 1) return;
        const row = destino(e.target);
        if (!row) return;
        const href = row.getAttribute('data-href');
        if (href) { e.preventDefault(); window.open(href, '_blank', 'noopener'); }
    });
})();
</script>
