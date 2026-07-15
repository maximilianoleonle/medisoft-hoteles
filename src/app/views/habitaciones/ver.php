<?php
/**
 * Vista de detalles de habitacion.
 */

$habitacion = $habitacion ?? [];
$ocupacion_actual = $ocupacion_actual ?? null;
$proxima_salida = $proxima_salida ?? null;
$historial_reciente = is_array($historial_reciente ?? null) ? $historial_reciente : [];
$mantenimiento_actual = $mantenimiento_actual ?? null;
$mantenimientos_programados = is_array($mantenimientos_programados ?? null) ? $mantenimientos_programados : [];
$reservas_mantenimiento_futuras = is_array($reservas_mantenimiento_futuras ?? null) ? $reservas_mantenimiento_futuras : [];
$reservas_mantenimiento_proximas = is_array($reservas_mantenimiento_proximas ?? null) ? $reservas_mantenimiento_proximas : [];
$tareas_contextuales = is_array($tareas_contextuales ?? null) ? $tareas_contextuales : [];
$reservacion_pendiente = $reservacion_pendiente ?? null;
$estados = $estados ?? [];
$tipos = $tipos ?? [];

$habitacion_id = (int)($habitacion['id'] ?? 0);
$habitacion_numero = (string)($habitacion['numero'] ?? '');
$habitacion_tipo = (string)($habitacion['tipo'] ?? '');
$habitacion_estado = (string)($habitacion['estado'] ?? 'desconocido');
$estado_actual = (string)($habitacion['estado_display'] ?? $habitacion_estado);
$piso_label = class_exists('Habitacion') ? Habitacion::getNombrePiso($habitacion['piso'] ?? '') : ('Piso ' . ($habitacion['piso'] ?? '-'));
$capacidad = (int)($habitacion['capacidad_personas'] ?? 0);
$caracteristicas = trim((string)($habitacion['caracteristicas'] ?? ''));
$camas_matrimoniales = (int)($habitacion['camas_matrimoniales'] ?? 0);
$camas_individuales = (int)($habitacion['camas_individuales'] ?? 0);
$camas_total = max(0, $camas_matrimoniales + $camas_individuales);
$tipo_label = $tipos[$habitacion_tipo] ?? (function_exists('get_tipo_habitacion') ? get_tipo_habitacion($habitacion_tipo) : ucfirst($habitacion_tipo));
if (preg_match('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*([^\[\r\n,;]+?)\s*\[([a-z0-9_\-]+)\]/i', $caracteristicas, $tipoCatalogoMatch)) {
    $tipoCatalogoCodigo = strtolower(trim((string)($tipoCatalogoMatch[2] ?? '')));
    $tipoCatalogoLabel = trim((string)($tipoCatalogoMatch[1] ?? ''));
    if ($tipoCatalogoCodigo !== '') {
        $tipo_label = $tipos[$tipoCatalogoCodigo] ?? ($tipoCatalogoLabel !== '' ? $tipoCatalogoLabel : $tipo_label);
    }
    $caracteristicas = preg_replace('/(?:^|[,;\r\n]\s*)Tipo catalogo\s*:\s*[^\[\r\n,;]+?\s*\[[a-z0-9_\-]+\]/i', '', $caracteristicas);
    $caracteristicas = trim(preg_replace('/\s*,\s*,+/', ',', (string)$caracteristicas), " \t\n\r\0\x0B,;");
}
$caracteristicas_plain = strtolower($caracteristicas);
$caracteristicas_ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $caracteristicas_plain);
if ($caracteristicas_ascii !== false) {
    $caracteristicas_plain = $caracteristicas_ascii;
}
if (strpos($caracteristicas_plain, 'jacuzzi') !== false && stripos($tipo_label, 'jacuzzi') === false) {
    if ($habitacion_tipo === 'doble') {
        $tipo_label = 'Doble con Jacuzzi';
    } elseif ($habitacion_tipo === 'sencilla') {
        $tipo_label = 'Sencilla con Jacuzzi';
    }
}
$precio_actual = (float)($habitacion['precio_actual'] ?? $habitacion['precio_base'] ?? 0);
$precio_base = (float)($habitacion['precio_base'] ?? 0);
$precio_base_original = (float)($habitacion['precio_base_original'] ?? $precio_base);
$incremento_total = (float)($habitacion['incremento_total'] ?? 0);
$tiene_incremento = !empty($habitacion['tiene_incremento']);
$activa = !empty($habitacion['activa']);
$propietario_nombre = trim((string)($habitacion['propietario_nombre'] ?? ''));
$propietario_nombre = $propietario_nombre !== '' ? $propietario_nombre : 'Sin propietario';
$propietario_pct = is_numeric($habitacion['propietario_participacion_pct'] ?? null)
    ? (float)$habitacion['propietario_participacion_pct']
    : 100.0;
$propietario_pct_label = '';
if ($propietario_pct < 99.995) {
    $propietario_pct_label = rtrim(rtrim(number_format($propietario_pct, 2, '.', ''), '0'), '.') . '%';
}
$reservas_mantenimiento_payload = array_map(static function ($reserva) {
    return [
        'id' => (int)($reserva['id'] ?? 0),
        'nombre' => (string)($reserva['nombre_completo'] ?? 'Huesped'),
        'telefono' => (string)($reserva['telefono'] ?? ''),
        'fecha_entrada' => substr((string)($reserva['fecha_entrada'] ?? ''), 0, 10),
        'fecha_salida' => substr((string)($reserva['fecha_salida'] ?? ''), 0, 10),
        'hora_llegada' => substr((string)($reserva['hora_llegada_estimada'] ?? ''), 0, 5),
        'estado' => (string)($reserva['estado'] ?? ''),
        'total_habitaciones' => (int)($reserva['total_habitaciones'] ?? 1),
    ];
}, $reservas_mantenimiento_futuras);
$reservas_mantenimiento_json = json_encode(
    $reservas_mantenimiento_payload,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
) ?: '[]';

if (!function_exists('room_detail_safe')) {
    function room_detail_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('room_detail_status_meta')) {
    function room_detail_status_meta($estado, $estadoInfo = []) {
        $map = [
            'disponible' => ['label' => 'Disponible', 'icon' => 'check-circle', 'color' => '#596066', 'soft' => '#EDEFF1'],
            'ocupada' => ['label' => 'Ocupada', 'icon' => 'user-lock', 'color' => '#B9463D', 'soft' => '#FFF0EF'],
            'mantenimiento' => ['label' => 'Mantenimiento', 'icon' => 'tools', 'color' => '#B66A00', 'soft' => '#FFF4D8'],
            'limpieza' => ['label' => 'Limpieza', 'icon' => 'broom', 'color' => '#2F6EA8', 'soft' => '#EAF3FF'],
            'por_llegar' => ['label' => 'Por llegar', 'icon' => 'clock', 'color' => '#7A4A12', 'soft' => '#F8ECD9'],
        ];

        $meta = $map[$estado] ?? ['label' => ucfirst((string)$estado), 'icon' => 'circle', 'color' => '#64748B', 'soft' => '#F1F5F9'];

        if (!empty($estadoInfo['label'])) {
            $meta['label'] = $estadoInfo['label'];
        }

        if (!empty($estadoInfo['icon'])) {
            $meta['icon'] = $estadoInfo['icon'];
        }

        return $meta;
    }
}

if (!function_exists('room_detail_relative_exit')) {
    function room_detail_relative_exit($days) {
        $days = (int)$days;
        if ($days === 0) return 'Salio hoy';
        if ($days === 1) return 'Ayer';
        if ($days < 7) return 'Hace ' . $days . ' dias';
        if ($days < 30) {
            $weeks = (int)floor($days / 7);
            return 'Hace ' . $weeks . ' ' . ($weeks === 1 ? 'semana' : 'semanas');
        }
        if ($days < 365) {
            $months = (int)floor($days / 30);
            return 'Hace ' . $months . ' ' . ($months === 1 ? 'mes' : 'meses');
        }
        $years = (int)floor($days / 365);
        return 'Hace ' . $years . ' ' . ($years === 1 ? 'ano' : 'anos');
    }
}

if (!function_exists('room_detail_relative_stay')) {
    // Etiqueta relativa consciente del signo para el historial: futuro / en curso / pasado.
    // room_detail_relative_exit() asume pasado, por eso una reserva futura salia como "Hace X dias".
    function room_detail_relative_stay($fecha_entrada, $fecha_salida) {
        $hoy     = new DateTime('today');
        $entrada = (new DateTime($fecha_entrada))->setTime(0, 0, 0);
        $salida  = (new DateTime($fecha_salida))->setTime(0, 0, 0);

        // Aun no llega: reserva futura
        if ($entrada > $hoy) {
            $dias = (int)$hoy->diff($entrada)->days;
            return $dias === 1 ? 'Manana' : 'En ' . $dias . ' dias';
        }
        // Ya entro pero aun no sale: estadia en curso
        if ($salida > $hoy) {
            return 'En estadia';
        }
        // Ya salio: reutiliza la escala pasada existente
        return room_detail_relative_exit((int)$salida->diff($hoy)->days);
    }
}

$estados_completos = $estados;
$estados_completos['por_llegar'] = ['label' => 'Por llegar', 'color' => 'amber', 'icon' => 'clock'];
$estado_info = $estados_completos[$estado_actual] ?? [];
$status_meta = room_detail_status_meta($estado_actual, $estado_info);

$imagenes = [];
if (class_exists('HabitacionImagen')) {
    $habitacionImagenModel = new HabitacionImagen();
    $imagenes = $habitacionImagenModel->porHabitacion($habitacion_id);
}

$tiene_imagenes = !empty($imagenes);
$imagen_principal = null;
if ($tiene_imagenes) {
    $imagen_principal = $imagenes[0];
    foreach ($imagenes as $img) {
        if (!empty($img['es_principal'])) {
            $imagen_principal = $img;
            break;
        }
    }
}

$hoy = date('Y-m-d');
$proxima_destacada = null;
$ultima_destacada = null;

if (!empty($historial_reciente)) {
    $menor_dias_proxima = PHP_INT_MAX;
    $menor_dias_ultima = PHP_INT_MAX;

    foreach ($historial_reciente as $res) {
        if (!isset($res['estado']) || empty($res['fecha_entrada']) || empty($res['fecha_salida'])) {
            continue;
        }

        if ($res['estado'] === 'confirmada' && $res['fecha_entrada'] >= $hoy) {
            $dias_hasta = (strtotime($res['fecha_entrada']) - strtotime($hoy)) / 86400;
            if ($dias_hasta < $menor_dias_proxima) {
                $menor_dias_proxima = $dias_hasta;
                $proxima_destacada = $res;
            }
        }

        if ($res['estado'] === 'checked_out' && $res['fecha_salida'] < $hoy) {
            $dias_desde = (strtotime($hoy) - strtotime($res['fecha_salida'])) / 86400;
            if ($dias_desde < $menor_dias_ultima) {
                $menor_dias_ultima = $dias_desde;
                $ultima_destacada = $res;
            }
        }
    }

    usort($historial_reciente, function($a, $b) {
        $fechaA = strtotime($a['fecha_salida'] ?? '1970-01-01');
        $fechaB = strtotime($b['fecha_salida'] ?? '1970-01-01');
        return $fechaB - $fechaA;
    });
}

$historial_count = count($historial_reciente);
$mantenimientos_count = count($mantenimientos_programados);
?>

<style>
@import url('<?= asset('vendor/fonts/marca2.css') ?>');

.hdv {
    --green: #596066;
    --green-dark: #424850;
    --green-soft: #EDEFF1;
    --amber: #C8956C;
    --amber-soft: #FDF3EC;
    --red: #B9463D;
    --red-soft: #FEF2F1;
    --blue: #2F6EA8;
    --blue-soft: #EAF3FF;
    --yellow: #B66A00;
    --yellow-soft: #FFF4D8;
    --surface: #F5F6F7;
    --card: #FFFFFF;
    --border: rgba(89,96,102,.12);
    --border-strong: rgba(89,96,102,.22);
    --text: #1E2226;
    --muted: #5E666E;
    --subtle: #98A0A8;
    --radius: 14px;
    --shadow: 0 1px 3px rgba(30,34,38,.06), 0 4px 16px rgba(30,34,38,.07);
    --shadow-md: 0 2px 8px rgba(30,34,38,.08), 0 8px 24px rgba(30,34,38,.09);
    font-family: 'DM Sans', system-ui, -apple-system, sans-serif;
    color: var(--text);
    background: var(--surface);
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
}

.hdv *, .hdv *::before, .hdv *::after { box-sizing: border-box; }
.hdv h1,.hdv h2,.hdv h3,.hdv h4 { font-family: 'Outfit', 'DM Sans', sans-serif; letter-spacing: -.01em; }
.hdv i[class*="fa-"],.hdv .fas,.hdv .far,.hdv .fab {
    font-family: "Font Awesome 5 Free","Font Awesome 5 Brands","FontAwesome" !important;
    font-style: normal;
}
.hdv .fas { font-weight: 900; }

/* ── Shell ── */
.hdv-shell {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 24px 48px;
}

/* ── Breadcrumb ── */
.hdv-crumb {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 18px;
    font-size: .8rem;
    font-weight: 600;
    color: var(--subtle);
}
.hdv-crumb a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--green);
    text-decoration: none;
    transition: opacity .15s;
}
.hdv-crumb a:hover { opacity: .75; }

/* ── Page header ── */
.hdv-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.hdv-header-left { min-width: 0; }
.hdv-room-number {
    margin: 0 0 10px;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    color: var(--text);
    line-height: 1;
}
.hdv-room-number span {
    display: block;
    font-size: .875rem;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    color: var(--subtle);
    letter-spacing: 0;
    margin-bottom: 4px;
}
.hdv-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    align-items: center;
}
.hdv-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 11px;
    border-radius: 999px;
    font-size: .78rem;
    font-weight: 600;
    background: var(--green-soft);
    color: var(--green-dark);
    border: 1px solid var(--border);
}
.hdv-badge-status {
    background: var(--status-soft);
    color: var(--status-color);
    border-color: color-mix(in srgb, var(--status-color) 20%, transparent);
    font-weight: 700;
}

/* ── Pending check-in banner ── */
.hdv-banner {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    margin-bottom: 20px;
    border-radius: var(--radius);
    background: var(--yellow-soft);
    border: 1px solid color-mix(in srgb, var(--yellow) 28%, transparent);
}
.hdv-banner-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: var(--yellow);
    color: #fff;
    font-size: .95rem;
}
.hdv-banner-body { flex: 1; min-width: 0; }
.hdv-banner-body strong {
    display: block;
    font-weight: 700;
    font-size: .95rem;
    color: var(--text);
}
.hdv-banner-body span {
    display: block;
    font-size: .8rem;
    color: var(--muted);
    margin-top: 2px;
}

/* ── 2-column layout ── */
.hdv-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 320px;
    gap: 20px;
    align-items: start;
}
.hdv-main { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
.hdv-side { position: sticky; top: 20px; display: flex; flex-direction: column; gap: 14px; }

/* ── Cards ── */
.hdv-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}
.hdv-card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    background: #FAFCFA;
}
.hdv-card-title {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.hdv-card-icon {
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: var(--green-soft);
    color: var(--green-dark);
    font-size: .85rem;
}
.hdv-card-head h2 {
    margin: 0;
    font-size: .95rem;
    font-weight: 700;
    color: var(--text);
}
.hdv-card-head p {
    margin: 2px 0 0;
    font-size: .75rem;
    color: var(--subtle);
    font-weight: 500;
}
.hdv-card-body { padding: 16px; }

/* ── Small count badge ── */
.hdv-count {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 999px;
    background: var(--green-soft);
    color: var(--green-dark);
    font-size: .72rem;
    font-weight: 700;
}

/* ── Gallery ── */
.hdv-gallery-wrap { position: relative; border-radius: 10px; overflow: hidden; background: var(--green-soft); }
.hdv-gallery-wrap img#imagen-principal {
    width: 100%;
    height: clamp(220px, 36vw, 420px);
    object-fit: cover;
    display: block;
    cursor: zoom-in;
    transition: transform .4s ease;
}
.hdv-gallery-wrap:hover img#imagen-principal { transform: scale(1.02); }
.hdv-gallery-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    display: flex;
    gap: 8px;
}
.hdv-gallery-btn {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    background: rgba(30,34,38,.62);
    color: #fff;
    border: 1px solid rgba(255,255,255,.18);
    cursor: pointer;
    backdrop-filter: blur(8px);
    font-size: .8rem;
    transition: background .15s;
}
.hdv-gallery-btn:hover { background: rgba(30,34,38,.82); }
.hdv-gallery-count {
    position: absolute;
    left: 10px;
    bottom: 10px;
    padding: 5px 10px;
    border-radius: 999px;
    background: rgba(30,34,38,.62);
    color: #fff;
    font-size: .72rem;
    font-weight: 700;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.18);
}
.hdv-thumbs {
    display: flex;
    gap: 8px;
    overflow-x: auto;
    padding: 10px 2px 2px;
    scrollbar-width: thin;
}
.hdv-thumbs img {
    width: 80px;
    height: 64px;
    flex: 0 0 auto;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid transparent;
    cursor: pointer;
    opacity: .7;
    transition: opacity .15s, border-color .15s, transform .15s;
}
.hdv-thumbs img:hover, .hdv-thumbs img.thumb-active {
    opacity: 1;
    border-color: var(--green);
    transform: translateY(-2px);
}
.hdv-no-photo {
    height: 180px;
    display: grid;
    place-items: center;
    text-align: center;
    color: var(--subtle);
    gap: 8px;
    border: 2px dashed var(--border-strong);
    border-radius: 10px;
}
.hdv-no-photo i { font-size: 1.8rem; color: var(--green); opacity: .5; }
.hdv-no-photo span { font-size: .82rem; font-weight: 500; }

/* ── Guest card accent ── */
.hdv-card-guest { border-color: color-mix(in srgb, var(--red) 18%, var(--border)); }
.hdv-card-guest .hdv-card-head { background: color-mix(in srgb, var(--red-soft) 60%, #FAFCFA); }
.hdv-card-guest .hdv-card-icon { background: var(--red-soft); color: var(--red); }

/* ── Maintenance card accent ── */
.hdv-card-maint { border-color: color-mix(in srgb, var(--yellow) 18%, var(--border)); }
.hdv-card-maint .hdv-card-head { background: color-mix(in srgb, var(--yellow-soft) 60%, #FAFCFA); }
.hdv-card-maint .hdv-card-icon { background: var(--yellow-soft); color: var(--yellow); }

/* ── Data grid (guest info tiles, room data, etc.) ── */
.hdv-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px; }
.hdv-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; }
.hdv-tile {
    padding: 11px 13px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #FAFCFA;
}
.hdv-tile-label {
    display: block;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--subtle);
}
.hdv-tile-value {
    display: block;
    font-size: .92rem;
    font-weight: 700;
    color: var(--text);
    margin-top: 4px;
    line-height: 1.3;
}
.hdv-full { grid-column: 1 / -1; }
.hdv-vehicle-list { display: grid; gap: 4px; margin-top: 4px; }
.hdv-vehicle-list span {
    font-size: .8rem;
    font-weight: 600;
    color: var(--text);
    text-transform: none;
    letter-spacing: 0;
}

/* ── Buttons ── */
.hdv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 40px;
    padding: 9px 16px;
    border-radius: 10px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    border: 1px solid transparent;
    text-decoration: none;
    transition: transform .15s, box-shadow .15s, background .15s;
    font-family: 'DM Sans', sans-serif;
}
.hdv-btn:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
.hdv-btn:active { transform: translateY(0); }
.hdv-btn-primary {
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    color: #fff;
    border-color: var(--green-dark);
    box-shadow: 0 2px 8px rgba(89,96,102,.28);
}
.hdv-btn-success {
    background: linear-gradient(135deg, #596066, #424850);
    color: #fff;
    border-color: #424850;
    box-shadow: 0 2px 8px rgba(89,96,102,.28);
}
.hdv-btn-danger {
    background: linear-gradient(135deg, var(--red), #922F28);
    color: #fff;
    border-color: #922F28;
    box-shadow: 0 2px 8px rgba(185,70,61,.28);
}
.hdv-btn-warning {
    background: linear-gradient(135deg, var(--yellow), #9B5300);
    color: #fff;
    border-color: #9B5300;
    box-shadow: 0 2px 8px rgba(182,106,0,.28);
}
.hdv-btn-info {
    background: linear-gradient(135deg, var(--blue), #245E8E);
    color: #fff;
    border-color: #245E8E;
    box-shadow: 0 2px 8px rgba(47,110,168,.28);
}
.hdv-btn-ghost {
    background: var(--card);
    color: var(--text);
    border-color: var(--border-strong);
}
.hdv-btn-ghost:hover { background: var(--green-soft); border-color: var(--green); color: var(--green-dark); }
.hdv-btn-full { width: 100%; }
.hdv-btn-sm { min-height: 34px; padding: 7px 12px; font-size: .77rem; }

/* ── Action list (sidebar) ── */
.hdv-action-list { display: flex; flex-direction: column; gap: 8px; }
.hdv-action {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 11px 14px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text);
    text-decoration: none;
    font-size: .83rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, border-color .15s, transform .15s;
    font-family: 'DM Sans', sans-serif;
}
.hdv-action:hover {
    background: var(--green-soft);
    border-color: var(--green);
    color: var(--green-dark);
    transform: translateX(2px);
}
.hdv-action span { display: flex; align-items: center; gap: 9px; }
.hdv-action .hdv-action-arr { opacity: .45; font-size: .75rem; }

/* ── Room data rows ── */
.hdv-data-list { display: flex; flex-direction: column; }
.hdv-data-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border);
    font-size: .83rem;
}
.hdv-data-row:last-child { border-bottom: 0; padding-bottom: 0; }
.hdv-data-row dt { color: var(--subtle); font-weight: 500; flex-shrink: 0; }
.hdv-data-row dd { color: var(--text); font-weight: 700; text-align: right; margin: 0; }
.hdv-price-big {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--green-dark);
    line-height: 1;
    font-family: 'Outfit', sans-serif;
    margin: 4px 0 2px;
}
.hdv-price-label { font-size: .75rem; color: var(--subtle); font-weight: 500; }
.hdv-price-note {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 5px 9px;
    border-radius: 999px;
    background: var(--amber-soft);
    color: var(--amber);
    font-size: .72rem;
    font-weight: 700;
    border: 1px solid color-mix(in srgb, var(--amber) 22%, transparent);
}

/* ── History list ── */
.hdv-history-list { display: flex; flex-direction: column; gap: 10px; }
.hdv-history-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #FAFCFA;
    transition: border-color .15s, background .15s;
}
.hdv-history-item:hover { border-color: var(--border-strong); background: var(--card); }
.hdv-history-info { flex: 1; min-width: 0; }
.hdv-history-name {
    font-size: .9rem;
    font-weight: 700;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
}
.hdv-history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-top: 6px;
}
.hdv-meta-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 999px;
    background: var(--green-soft);
    color: var(--muted);
    font-size: .7rem;
    font-weight: 600;
}
.hdv-recent-badge {
    font-size: .68rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
    background: var(--amber-soft);
    color: var(--amber);
    border: 1px solid color-mix(in srgb, var(--amber) 22%, transparent);
}

/* ── Focus cards (next / last reservation spotlight) ── */
.hdv-spotlight {
    display: grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap: 12px;
}
.hdv-spotlight-card {
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #FAFCFA;
    overflow: hidden;
}
.hdv-spotlight-card:only-child { grid-column: 1 / -1; }
.hdv-spotlight-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px 13px;
    border-bottom: 1px solid var(--border);
    background: var(--card);
}
.hdv-spotlight-head strong {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .85rem;
    font-weight: 700;
    color: var(--text);
}
.hdv-spotlight-kind {
    font-size: .68rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 999px;
    background: var(--green-soft);
    color: var(--green-dark);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.hdv-spotlight-body { padding: 13px; }
.hdv-spotlight-body h3 { margin: 0 0 9px; font-size: .95rem; font-weight: 700; color: var(--text); }
.hdv-date-pair { display: grid; grid-template-columns: repeat(2,1fr); gap: 8px; margin-bottom: 10px; }
.hdv-date-box {
    padding: 9px 10px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: var(--card);
}
.hdv-date-box span { display: block; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--subtle); }
.hdv-date-box strong { display: block; font-size: .84rem; font-weight: 700; color: var(--text); margin-top: 3px; }

/* ── Maintenance scheduled item ── */
.hdv-maint-list { display: flex; flex-direction: column; gap: 9px; }
.hdv-maint-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 13px;
    border: 1px solid color-mix(in srgb, var(--yellow) 22%, var(--border));
    border-radius: 10px;
    background: color-mix(in srgb, var(--yellow-soft) 58%, var(--card));
}
.hdv-maint-item-body strong { display: block; font-size: .88rem; font-weight: 700; color: var(--text); }
.hdv-maint-item-body span { display: block; font-size: .76rem; color: var(--muted); margin-top: 2px; }

/* ── Empty state ── */
.hdv-empty {
    display: grid;
    place-items: center;
    min-height: 140px;
    text-align: center;
    gap: 8px;
    border: 2px dashed var(--border-strong);
    border-radius: 10px;
    color: var(--subtle);
    padding: 24px;
}
.hdv-empty i { font-size: 1.6rem; color: var(--green); opacity: .5; }
.hdv-empty strong { display: block; font-size: .9rem; font-weight: 700; color: var(--muted); }
.hdv-empty span { font-size: .8rem; }

/* ── Sidebar note ── */
.hdv-note {
    padding: 11px 13px;
    border-radius: 10px;
    background: var(--green-soft);
    border: 1px solid var(--border);
    color: var(--muted);
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.5;
}

/* ── Modal styles (maintenance modals — kept functional) ── */
.hdv-modal {
    --green: #596066;
    --green-dark: #424850;
    --green-soft: #EDEFF1;
    --blue: #2F6EA8;
    --blue-soft: #EAF3FF;
    --yellow: #B66A00;
    --yellow-soft: #FFF4D8;
    --card: #FFFFFF;
    --border: rgba(89,96,102,.12);
    --border-strong: rgba(89,96,102,.22);
    --text: #1E2226;
    --muted: #5E666E;
    --subtle: #6E7478;
    --shadow-md: 0 2px 8px rgba(30,34,38,.08), 0 8px 24px rgba(30,34,38,.09);
    position: fixed;
    inset: 0;
    z-index: 10040;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(15,18,20,.58);
    backdrop-filter: blur(8px);
    color: var(--text);
}
.hdv-modal.hidden { display: none !important; }
.hdv-modal-card {
    width: min(100%, 540px);
    max-height: calc(100vh - 32px);
    overflow: auto;
    background: var(--card, #FFFFFF);
    border-radius: 18px;
    border: 1px solid var(--border-strong, rgba(89,96,102,.22));
    box-shadow: 0 24px 64px rgba(15,18,20,.32);
    transform: scale(.96);
    opacity: 0;
    transition: transform .22s ease, opacity .22s ease;
}
.hdv-modal-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 14px;
    padding: 18px 20px;
    border-bottom: 1px solid var(--border, rgba(89,96,102,.12));
    background: #FAFCFA;
}
.hdv-modal-title { display: flex; align-items: center; gap: 12px; min-width: 0; }
.hdv-modal-icon {
    width: 44px;
    height: 44px;
    flex: 0 0 44px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: var(--yellow-soft);
    color: var(--yellow);
    border: 1px solid color-mix(in srgb, var(--yellow) 22%, transparent);
    font-size: 1rem;
}
.hdv-modal-title-text { min-width: 0; }
.hdv-modal-kicker {
    display: inline-flex;
    align-items: center;
    margin-bottom: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    background: var(--yellow-soft);
    color: var(--yellow);
    border: 1px solid color-mix(in srgb, var(--yellow) 18%, transparent);
    font-size: .65rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}
.hdv-modal-head h3 { margin: 0; font-size: 1.05rem; font-weight: 700; color: var(--text); }
.hdv-modal-head p { margin: 4px 0 0; font-size: .8rem; color: var(--subtle); font-weight: 500; line-height: 1.4; }
.hdv-modal-close {
    width: 36px;
    height: 36px;
    flex: 0 0 36px;
    display: grid;
    place-items: center;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--card);
    color: var(--text);
    cursor: pointer;
    transition: background .15s;
}
.hdv-modal-close:hover { background: var(--green-soft); }
.hdv-modal-body { padding: 20px; display: flex; flex-direction: column; gap: 14px; background: var(--card, #FFFFFF); color: var(--text, #1E2226); }
.hdv-maint-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0,1fr));
    gap: 8px;
}
.hdv-maint-summary span {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 11px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: var(--card);
    font-size: .78rem;
    font-weight: 700;
    color: var(--text);
}
.hdv-maint-summary i { color: var(--yellow); }
.hdv-modal-label {
    display: block;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--text);
    margin-bottom: 7px;
}
.hdv-modal-input {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--border-strong);
    border-radius: 10px;
    background: var(--card);
    color: var(--text);
    padding: 10px 12px;
    font-size: .88rem;
    font-weight: 500;
    font-family: 'DM Sans', sans-serif;
    transition: border-color .15s, box-shadow .15s;
}
.hdv-modal-input:focus {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--green) 14%, transparent);
}
.hdv-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 12px; }
.hdv-modal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.hdv-modal-note {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 13px;
    border-radius: 10px;
    background: var(--blue-soft);
    border: 1px solid color-mix(in srgb, var(--blue) 22%, transparent);
    color: var(--blue);
    font-size: .8rem;
    font-weight: 500;
    line-height: 1.45;
}
.hdv-modal-note i {
    flex: 0 0 auto;
    width: 28px;
    height: 28px;
    display: grid;
    place-items: center;
    border-radius: 8px;
    background: #fff;
    color: var(--blue);
    font-size: .8rem;
}

/* ── Risk conflict box inside modals ── */
.rd-maint-risk {
    display: grid;
    gap: 10px;
    padding: 13px;
    border: 1px solid color-mix(in srgb, #D97706 36%, var(--border));
    border-radius: 12px;
    background: linear-gradient(135deg, color-mix(in srgb, #F59E0B 10%, #FFFFFF), #FFFFFF);
    color: var(--text);
}
.rd-maint-risk.hidden { display: none; }
.rd-maint-risk-head { display: flex; align-items: flex-start; gap: 10px; }
.rd-maint-risk-icon {
    width: 32px; height: 32px; flex: 0 0 32px;
    display: grid; place-items: center;
    border-radius: 10px; background: #FFF7ED; color: #B45309;
    border: 1px solid #FED7AA;
}
.rd-maint-risk-title { display: block; font-size: .88rem; font-weight: 700; line-height: 1.2; }
.rd-maint-risk-copy { display: block; margin-top: 2px; color: var(--subtle); font-size: .76rem; font-weight: 500; line-height: 1.4; }
.rd-maint-risk-list { display: grid; gap: 7px; }
.rd-maint-risk-item {
    display: grid; grid-template-columns: minmax(0,1fr) auto;
    gap: 10px; align-items: center;
    padding: 9px 10px; border: 1px solid #F3DEC5; border-radius: 10px; background: rgba(255,255,255,.82);
}
.rd-maint-risk-item strong { display: block; font-size: .82rem; font-weight: 700; }
.rd-maint-risk-item span { display: block; margin-top: 2px; color: var(--subtle); font-size: .72rem; }
.rd-maint-risk-badge {
    display: inline-flex; align-items: center; min-height: 24px; padding: 0 8px;
    border-radius: 999px; background: #FEF3C7; color: #92400E; font-size: .67rem; font-weight: 700;
}
.rd-maint-confirm {
    display: grid; grid-template-columns: 20px minmax(0,1fr); gap: 9px;
    align-items: flex-start; padding: 10px 11px; border-radius: 10px;
    background: #FFFFFF; border: 1px dashed #E2B66B;
    color: #374151; font-size: .77rem; font-weight: 700; line-height: 1.4; cursor: pointer;
}
.rd-maint-confirm input { width: 18px; height: 18px; margin-top: 1px; accent-color: #B45309; }
.rd-maint-risk-error { display: none; color: #B91C1C; font-size: .73rem; font-weight: 700; }
.rd-maint-risk.has-error .rd-maint-risk-error { display: block; }

/* ── Lightbox ── */
.hdv-lightbox {
    position: fixed;
    inset: 0;
    z-index: 10050;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    background: rgba(4,8,15,.93);
    backdrop-filter: blur(6px);
}
.hdv-lightbox.hidden { display: none !important; }
.hdv-lightbox-frame { position: relative; width: min(1600px,97vw); max-height: 100%; display: grid; place-items: center; }
.hdv-lightbox-frame img#lightbox-img {
    width: 100%; height: 90vh; max-width: 97vw;
    object-fit: contain; border-radius: 12px;
    box-shadow: 0 24px 72px rgba(0,0,0,.88);
}
.hdv-lb-btn,.hdv-lb-close {
    position: absolute; display: grid; place-items: center;
    border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.1);
    color: #fff; backdrop-filter: blur(8px); cursor: pointer;
    transition: background .15s;
}
.hdv-lb-btn { width: 46px; height: 46px; border-radius: 999px; top: 50%; transform: translateY(-50%); }
.hdv-lb-btn:hover { background: rgba(255,255,255,.2); }
.hdv-lb-prev { left: 12px; }
.hdv-lb-next { right: 12px; }
.hdv-lb-close { width: 40px; height: 40px; border-radius: 12px; top: 12px; right: 12px; }
.hdv-lb-count {
    position: absolute; left: 50%; bottom: 12px; transform: translateX(-50%);
    padding: 6px 12px; border-radius: 999px; background: rgba(255,255,255,.12);
    color: #fff; font-size: .76rem; font-weight: 700; backdrop-filter: blur(8px);
}
.hdv-lb-thumbs {
    position: absolute; left: 50%; bottom: 52px; transform: translateX(-50%);
    display: flex; gap: 7px; max-width: min(92vw,720px); overflow-x: auto; padding: 4px;
}
.hdv-lb-thumbs img {
    width: 60px; height: 52px; object-fit: cover;
    border: 2px solid transparent; border-radius: 10px; cursor: pointer;
    opacity: .6; transition: opacity .15s, border-color .15s;
}
.hdv-lb-thumbs img.is-active,.hdv-lb-thumbs img:hover { opacity: 1; border-color: #fff; }

/* ── Responsive ── */
@media (max-width: 1100px) {
    .hdv-layout { grid-template-columns: 1fr; }
    .hdv-side { position: static; display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media (max-width: 720px) {
    .hdv-shell { padding: 14px 14px 32px; }
    .hdv-side { grid-template-columns: 1fr; }
    .hdv-grid-2,.hdv-form-grid,.hdv-spotlight,.hdv-modal-actions { grid-template-columns: 1fr; }
    .hdv-grid-3,.hdv-maint-summary { grid-template-columns: repeat(2, minmax(0,1fr)); }
    .hdv-room-number { font-size: 2rem; }
    .hdv-modal-card { width: 100%; border-radius: 16px; }
    .hdv-date-pair { grid-template-columns: repeat(2,1fr); }
}
@media (prefers-reduced-motion: reduce) {
    .hdv *,.hdv *::before,.hdv *::after { animation: none !important; transition: none !important; }
}
</style>

<div class="hdv">
<div class="hdv-shell">

    <!-- Breadcrumb -->
    <nav class="hdv-crumb" aria-label="Ruta de navegacion">
        <?php $back_arrow_href = back_url('habitaciones'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
        <a href="<?= url('habitaciones') ?>"><i class="fas fa-bed"></i> Habitaciones</a>
        <i class="fas fa-chevron-right"></i>
        <span>Habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?></span>
    </nav>

    <!-- Page header -->
    <div class="hdv-header">
        <div class="hdv-header-left">
            <h1 class="hdv-room-number">
                <span>Habitaci&oacute;n</span>
                <?= room_detail_safe($habitacion_numero) ?>
            </h1>
            <div class="hdv-badges">
                <span class="hdv-badge hdv-badge-status"
                      style="--status-color:<?= room_detail_safe($status_meta['color']) ?>;--status-soft:<?= room_detail_safe($status_meta['soft']) ?>;">
                    <i class="fas fa-<?= room_detail_safe($status_meta['icon'],'circle') ?>"></i>
                    <?= room_detail_safe($status_meta['label']) ?>
                </span>
                <span class="hdv-badge"><i class="fas fa-layer-group"></i><?= room_detail_safe($tipo_label) ?></span>
                <span class="hdv-badge"><i class="fas fa-building"></i><?= room_detail_safe($piso_label) ?></span>
                <?php if ($capacidad > 0): ?>
                    <span class="hdv-badge"><i class="fas fa-user-group"></i><?= number_format($capacidad) ?> personas</span>
                <?php endif; ?>
                <?php if (!$activa): ?>
                    <span class="hdv-badge" style="background:#FEF2F1;color:#B9463D;border-color:rgba(185,70,61,.2);">
                        <i class="fas fa-ban"></i>Inactiva
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pending check-in alert -->
    <?php if ($reservacion_pendiente): ?>
        <div class="hdv-banner">
            <div class="hdv-banner-icon"><i class="fas fa-clock"></i></div>
            <div class="hdv-banner-body">
                <strong><?= room_detail_safe($reservacion_pendiente['nombre_completo'] ?? '') ?> &mdash; llegada pendiente</strong>
                <span>
                    Hora estimada: <?= date('g:i A', strtotime($reservacion_pendiente['hora_llegada_estimada'])) ?>
                    <?php if (($reservacion_pendiente['total_habitaciones'] ?? 0) > 1): ?>
                        &middot; Grupo de <?= (int)$reservacion_pendiente['total_habitaciones'] ?> habitaciones
                    <?php endif; ?>
                </span>
            </div>
            <a href="<?= url('reservaciones/ver/' . $reservacion_pendiente['reservacion_id']) ?>" class="hdv-btn hdv-btn-success">
                <i class="fas fa-sign-in-alt"></i> Check-in
            </a>
        </div>
    <?php endif; ?>

    <!-- 2-column layout -->
    <div class="hdv-layout">

        <!-- ═══ MAIN COLUMN ═══ -->
        <main class="hdv-main">

            <!-- Gallery -->
            <div class="hdv-card">
                <div class="hdv-card-head">
                    <div class="hdv-card-title">
                        <span class="hdv-card-icon"><i class="fas fa-images"></i></span>
                        <div>
                            <h2>Fotograf&iacute;as</h2>
                            <p>Im&aacute;genes de la habitaci&oacute;n</p>
                        </div>
                    </div>
                    <?php if ($tiene_imagenes): ?>
                        <span class="hdv-count"><i class="fas fa-camera"></i><?= count($imagenes) ?> fotos</span>
                    <?php endif; ?>
                </div>
                <div class="hdv-card-body">
                    <?php if ($tiene_imagenes): ?>
                        <div class="hdv-gallery-wrap">
                            <img id="imagen-principal"
                                 src="<?= image_url($imagen_principal['url']) ?>"
                                 alt="Habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?>"
                                 onclick="abrirLightbox(this.src)">
                            <?php if (count($imagenes) > 1): ?>
                                <div class="hdv-gallery-count">
                                    <i class="fas fa-images"></i>
                                    <span id="contador"><?= count($imagenes) ?> fotos</span>
                                </div>
                            <?php endif; ?>
                            <div class="hdv-gallery-overlay">
                                <button type="button" onclick="abrirLightbox(document.getElementById('imagen-principal').src)"
                                        class="hdv-gallery-btn" title="Ver en grande">
                                    <i class="fas fa-expand"></i>
                                </button>
                                <a href="<?= url('habitaciones/' . $habitacion_id . '/imagenes') ?>"
                                   class="hdv-gallery-btn" title="Gestionar fotos">
                                    <i class="fas fa-pencil-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php if (count($imagenes) > 1): ?>
                            <div class="hdv-thumbs" style="margin-top:10px;">
                                <?php foreach ($imagenes as $index => $imagen): ?>
                                    <img src="<?= image_url($imagen['url']) ?>"
                                         alt="Foto <?= $index + 1 ?>"
                                         class="room-thumb <?= $index === 0 ? 'thumb-active' : '' ?>"
                                         onclick="cambiarImagen(<?= htmlspecialchars(json_encode(image_url($imagen['url'])), ENT_QUOTES, 'UTF-8') ?>, <?= $index ?>)">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="hdv-no-photo">
                            <i class="fas fa-camera-retro"></i>
                            <span>Sin fotograf&iacute;as. <a href="<?= url('habitaciones/' . $habitacion_id . '/imagenes') ?>" style="color:var(--green);font-weight:700;">Agregar fotos</a></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Current guest (only when occupied) -->
            <?php if ($ocupacion_actual): ?>
                <div class="hdv-card hdv-card-guest">
                    <div class="hdv-card-head">
                        <div class="hdv-card-title">
                            <span class="hdv-card-icon"><i class="fas fa-user-check"></i></span>
                            <div>
                                <h2>Hu&eacute;sped actual</h2>
                                <p>Reservaci&oacute;n activa en esta habitaci&oacute;n</p>
                            </div>
                        </div>
                        <?php if (($ocupacion_actual['fecha_salida'] ?? '') == date('Y-m-d')): ?>
                            <span class="hdv-count" style="background:#FEF2F1;color:#B9463D;border:1px solid rgba(185,70,61,.2);">
                                <i class="fas fa-calendar-day"></i>Sale hoy
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="hdv-card-body">
                        <div class="hdv-grid-2">
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Hu&eacute;sped</span>
                                <span class="hdv-tile-value"><?= room_detail_safe($ocupacion_actual['nombre_completo'] ?? '') ?></span>
                            </div>
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Fecha de salida</span>
                                <span class="hdv-tile-value"><?= format_date($ocupacion_actual['fecha_salida'] ?? '') ?></span>
                            </div>
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Tel&eacute;fono</span>
                                <span class="hdv-tile-value"><?= room_detail_safe($ocupacion_actual['telefono'] ?? '', 'No registrado') ?></span>
                            </div>
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Veh&iacute;culos</span>
                                <?php
                                $vehiculos_mostrar = [];
                                if (class_exists('HuespedVehiculo') && isset($ocupacion_actual['huesped_id'])) {
                                    $vehiculoModel = new HuespedVehiculo();
                                    $vehiculos_mostrar = $vehiculoModel->porHuespedHotel($ocupacion_actual['huesped_id']);
                                }
                                if (!empty($vehiculos_mostrar)): ?>
                                    <div class="hdv-vehicle-list">
                                        <?php foreach ($vehiculos_mostrar as $vehiculo): ?>
                                            <span class="hdv-tile-value">
                                                <i class="fas fa-car" style="color:var(--green);font-size:.8rem;"></i>
                                                <?= room_detail_safe(trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? '')), 'Veh.') ?>
                                                (<?= room_detail_safe($vehiculo['placas'] ?? '', 'sin placas') ?>)
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (isset($ocupacion_actual['huesped_id'])): ?>
                                    <span class="hdv-tile-value"><?= room_detail_safe(function_exists('get_resumen_vehiculos_huesped') ? get_resumen_vehiculos_huesped($ocupacion_actual['huesped_id']) : '', 'Sin veh&iacute;culos') ?></span>
                                <?php else: ?>
                                    <span class="hdv-tile-value">Sin veh&iacute;culos</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="margin-top:12px;">
                            <a href="<?= url('reservaciones/ver/' . ($ocupacion_actual['id'] ?? 0)) ?>" class="hdv-btn hdv-btn-info">
                                <i class="fas fa-eye"></i> Ver reservaci&oacute;n completa
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Active maintenance -->
            <?php if ($habitacion_estado == 'mantenimiento' && $mantenimiento_actual): ?>
                <div class="hdv-card hdv-card-maint">
                    <div class="hdv-card-head">
                        <div class="hdv-card-title">
                            <span class="hdv-card-icon"><i class="fas fa-tools"></i></span>
                            <div>
                                <h2>Mantenimiento en progreso</h2>
                                <p>Trabajo activo registrado</p>
                            </div>
                        </div>
                    </div>
                    <div class="hdv-card-body">
                        <div class="hdv-grid-3">
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Tipo</span>
                                <span class="hdv-tile-value"><?= room_detail_safe(ucfirst(str_replace('_', ' ', $mantenimiento_actual['tipo_mantenimiento'] ?? 'No especificado'))) ?></span>
                            </div>
                            <div class="hdv-tile">
                                <span class="hdv-tile-label">Prioridad</span>
                                <span class="hdv-tile-value"><?= room_detail_safe(ucfirst($mantenimiento_actual['prioridad'] ?? 'media')) ?></span>
                            </div>
                            <div class="hdv-tile hdv-full">
                                <span class="hdv-tile-label">Motivo</span>
                                <span class="hdv-tile-value"><?= room_detail_safe($mantenimiento_actual['motivo'] ?? '', 'No especificado') ?></span>
                            </div>
                        </div>
                        <?php if (function_exists('current_hotel_has_module') && current_hotel_has_module('mantenimiento_plus') && !empty($mantenimiento_actual['id'])): ?>
                            <a href="<?= url('mantenimientos/' . (int)$mantenimiento_actual['id']) ?>" class="hdv-btn hdv-btn-info hdv-btn-full" style="margin-top:12px;">
                                <i class="fas fa-camera"></i> Evidencia y detalle
                            </a>
                        <?php endif; ?>
                        <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/mantenimiento') ?>" style="margin-top:12px;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="finalizar">
                            <button type="submit" class="hdv-btn hdv-btn-success hdv-btn-full">
                                <i class="fas fa-check-circle"></i> Finalizar mantenimiento
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Contextual tasks -->
            <?php
            $tareasContextuales = $tareas_contextuales;
            $tituloTareasContextuales = 'Tareas operativas';
            $subtituloTareasContextuales = 'Tareas de limpieza, mantenimiento o seguimiento vinculadas a esta habitacion.';
            include __DIR__ . '/../tareas/_contextual_list.php';
            ?>

            <!-- Scheduled maintenances -->
            <?php if (!empty($mantenimientos_programados)): ?>
                <div class="hdv-card hdv-card-maint">
                    <div class="hdv-card-head">
                        <div class="hdv-card-title">
                            <span class="hdv-card-icon"><i class="fas fa-calendar-check"></i></span>
                            <div>
                                <h2>Mantenimientos programados</h2>
                                <p>Intervenciones futuras agendadas</p>
                            </div>
                        </div>
                        <span class="hdv-count"><?= number_format($mantenimientos_count) ?></span>
                    </div>
                    <div class="hdv-card-body">
                        <div class="hdv-maint-list">
                            <?php foreach ($mantenimientos_programados as $mp): ?>
                                <div class="hdv-maint-item">
                                    <div class="hdv-maint-item-body">
                                        <strong>
                                            <i class="fas fa-wrench" style="color:var(--yellow);margin-right:6px;"></i>
                                            <?= room_detail_safe(ucfirst(str_replace('_', ' ', $mp['tipo_mantenimiento'] ?? 'Mantenimiento'))) ?>
                                            &middot; Prioridad <?= room_detail_safe(ucfirst($mp['prioridad'] ?? 'media')) ?>
                                        </strong>
                                        <span>
                                            <?= date('d/m/Y', strtotime($mp['fecha_programada'])) ?>
                                            <?php if (!empty($mp['fecha_programada_fin'])): ?>
                                                al <?= date('d/m/Y', strtotime($mp['fecha_programada_fin'])) ?>
                                            <?php endif; ?>
                                            <?php if (!empty($mp['motivo'])): ?>
                                                &mdash; <?= room_detail_safe($mp['motivo']) ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <form method="POST"
                                          action="<?= url('habitaciones/cancelar-mantenimiento-programado/' . $mp['id']) ?>"
                                          data-ms-confirm
                                          data-ms-type="error"
                                          data-ms-icon="x"
                                          data-ms-title="¿Cancelar mantenimiento programado?"
                                          data-ms-msg="El mantenimiento programado quedará cancelado."
                                          data-ms-ok="Sí, cancelar">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="motivo_cancelacion" value="Cancelado manualmente">
                                        <button type="submit" class="hdv-btn hdv-btn-danger hdv-btn-sm">
                                            <i class="fas fa-times"></i> Cancelar
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Next / Last reservation spotlight -->
            <?php if ($proxima_destacada || $ultima_destacada): ?>
                <div class="hdv-card">
                    <div class="hdv-card-head">
                        <div class="hdv-card-title">
                            <span class="hdv-card-icon"><i class="fas fa-calendar-days"></i></span>
                            <div>
                                <h2>Pr&oacute;xima llegada y &uacute;ltima estad&iacute;a</h2>
                                <p>Reservaciones destacadas de esta habitaci&oacute;n</p>
                            </div>
                        </div>
                    </div>
                    <div class="hdv-card-body">
                        <div class="hdv-spotlight">
                            <?php if ($proxima_destacada): ?>
                                <?php
                                $fp_e = new DateTime($proxima_destacada['fecha_entrada']);
                                $fp_s = new DateTime($proxima_destacada['fecha_salida']);
                                $fp_noches = $fp_e->diff($fp_s)->days;
                                // Base a medianoche para contar dias calendario (evita truncar por la hora actual).
                                $fp_dias  = (new DateTime('today'))->diff($fp_e)->days;
                                ?>
                                <div class="hdv-spotlight-card">
                                    <div class="hdv-spotlight-head">
                                        <strong><i class="fas fa-calendar-check"></i>Pr&oacute;xima llegada</strong>
                                        <span class="hdv-spotlight-kind">Confirmada</span>
                                    </div>
                                    <div class="hdv-spotlight-body">
                                        <h3><?= room_detail_safe($proxima_destacada['nombre_huesped'] ?? '') ?></h3>
                                        <div class="hdv-date-pair">
                                            <div class="hdv-date-box">
                                                <span>Entrada</span>
                                                <strong><?= $fp_e->format('d/m/Y') ?></strong>
                                            </div>
                                            <div class="hdv-date-box">
                                                <span>Salida</span>
                                                <strong><?= $fp_s->format('d/m/Y') ?></strong>
                                            </div>
                                        </div>
                                        <div class="hdv-history-meta">
                                            <span class="hdv-meta-pill"><i class="fas fa-clock"></i><?= $fp_dias == 0 ? 'Llega hoy' : ($fp_dias == 1 ? 'Manana' : 'En ' . $fp_dias . ' dias') ?></span>
                                            <span class="hdv-meta-pill"><i class="fas fa-moon"></i><?= $fp_noches ?> <?= $fp_noches == 1 ? 'noche' : 'noches' ?></span>
                                            <?php if (($proxima_destacada['precio_total'] ?? 0) > 0): ?>
                                                <?php $pd_habs = (int)($proxima_destacada['total_habitaciones'] ?? 1); ?>
                                                <span class="hdv-meta-pill"><i class="fas fa-dollar-sign"></i><?= format_money($proxima_destacada['precio_total']) ?><?= $pd_habs > 1 ? ' &middot; grupo de ' . $pd_habs . ' hab.' : '' ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= url('/reservaciones/ver/' . $proxima_destacada['id']) ?>" class="hdv-btn hdv-btn-info hdv-btn-sm" style="margin-top:10px;">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($ultima_destacada): ?>
                                <?php
                                $fl_e = new DateTime($ultima_destacada['fecha_entrada']);
                                $fl_s = new DateTime($ultima_destacada['fecha_salida']);
                                $fl_noches = $fl_e->diff($fl_s)->days;
                                $fl_dias   = $fl_s->diff(new DateTime('today'))->days;
                                ?>
                                <div class="hdv-spotlight-card">
                                    <div class="hdv-spotlight-head">
                                        <strong><i class="fas fa-history"></i>&Uacute;ltima estad&iacute;a</strong>
                                        <span class="hdv-spotlight-kind">Historial</span>
                                    </div>
                                    <div class="hdv-spotlight-body">
                                        <h3><?= room_detail_safe($ultima_destacada['nombre_huesped'] ?? '') ?></h3>
                                        <div class="hdv-date-pair">
                                            <div class="hdv-date-box">
                                                <span>Entrada</span>
                                                <strong><?= $fl_e->format('d/m/Y') ?></strong>
                                            </div>
                                            <div class="hdv-date-box">
                                                <span>Salida</span>
                                                <strong><?= $fl_s->format('d/m/Y') ?></strong>
                                            </div>
                                        </div>
                                        <div class="hdv-history-meta">
                                            <span class="hdv-meta-pill"><i class="fas fa-calendar-alt"></i><?= room_detail_relative_exit($fl_dias) ?></span>
                                            <span class="hdv-meta-pill"><i class="fas fa-moon"></i><?= $fl_noches ?> <?= $fl_noches == 1 ? 'noche' : 'noches' ?></span>
                                            <?php if (($ultima_destacada['precio_total'] ?? 0) > 0): ?>
                                                <?php $ud_habs = (int)($ultima_destacada['total_habitaciones'] ?? 1); ?>
                                                <span class="hdv-meta-pill"><i class="fas fa-dollar-sign"></i><?= format_money($ultima_destacada['precio_total']) ?><?= $ud_habs > 1 ? ' &middot; grupo de ' . $ud_habs . ' hab.' : '' ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= url('/reservaciones/ver/' . $ultima_destacada['id']) ?>" class="hdv-btn hdv-btn-info hdv-btn-sm" style="margin-top:10px;">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reservation history -->
            <div class="hdv-card">
                <div class="hdv-card-head">
                    <div class="hdv-card-title">
                        <span class="hdv-card-icon"><i class="fas fa-clock-rotate-left"></i></span>
                        <div>
                            <h2>Historial de reservaciones</h2>
                            <p>Los &uacute;ltimos movimientos de esta habitaci&oacute;n</p>
                        </div>
                    </div>
                    <?php if ($historial_count > 0): ?>
                        <span class="hdv-count"><i class="fas fa-calendar-check"></i><?= min(10, $historial_count) ?> visibles</span>
                    <?php endif; ?>
                </div>
                <div class="hdv-card-body">
                    <?php if (!empty($historial_reciente)): ?>
                        <div class="hdv-history-list">
                            <?php
                            $hoy_dt = new DateTime('today');
                            foreach (array_slice($historial_reciente, 0, 10) as $reservacion):
                                $fhi_e = new DateTime($reservacion['fecha_entrada']);
                                $fhi_s = new DateTime($reservacion['fecha_salida']);
                                $fhi_noches = $fhi_e->diff($fhi_s)->days;
                                $fhi_rel = room_detail_relative_stay($reservacion['fecha_entrada'], $reservacion['fecha_salida']);
                                // "Reciente" solo si ya llego y salio hace <=7 dias (o sigue en estadia); nunca a futuro.
                                $fhi_e_solo = (new DateTime($reservacion['fecha_entrada']))->setTime(0, 0, 0);
                                $fhi_s_solo = (new DateTime($reservacion['fecha_salida']))->setTime(0, 0, 0);
                                $dias_desde_salida = (int)$fhi_s_solo->diff($hoy_dt)->days * ($fhi_s_solo > $hoy_dt ? -1 : 1);
                                $es_reciente = $fhi_e_solo <= $hoy_dt && $dias_desde_salida <= 7;
                                $total_pagado = null;
                                if (isset($reservacion['precio_total']) && $reservacion['precio_total'] > 0) {
                                    $total_pagado = $reservacion['precio_total'];
                                } elseif (isset($reservacion['total']) && $reservacion['total'] > 0) {
                                    $total_pagado = $reservacion['total'];
                                }
                                $procedencia = '';
                                if (!empty($reservacion['procedencia'])) {
                                    $procedencia = $reservacion['procedencia'];
                                } elseif (!empty($reservacion['estado_procedencia'])) {
                                    $procedencia = $reservacion['estado_procedencia'];
                                }
                            ?>
                                <div class="hdv-history-item">
                                    <div class="hdv-history-info">
                                        <div class="hdv-history-name">
                                            <?= room_detail_safe($reservacion['nombre_huesped'] ?? '') ?>
                                            <?php if ($es_reciente): ?>
                                                <span class="hdv-recent-badge"><i class="fas fa-star"></i> Reciente</span>
                                            <?php endif; ?>
                                            <?php if (($reservacion['total_habitaciones'] ?? 0) > 1): ?>
                                                <span class="hdv-recent-badge"><i class="fas fa-users"></i> Grupo <?= (int)$reservacion['total_habitaciones'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="hdv-history-meta">
                                            <span class="hdv-meta-pill"><i class="fas fa-sign-in-alt"></i><?= $fhi_e->format('d/m/Y') ?></span>
                                            <span class="hdv-meta-pill"><i class="fas fa-sign-out-alt"></i><?= $fhi_s->format('d/m/Y') ?></span>
                                            <span class="hdv-meta-pill"><i class="fas fa-moon"></i><?= $fhi_noches ?> <?= $fhi_noches == 1 ? 'noche' : 'noches' ?></span>
                                            <span class="hdv-meta-pill"><i class="fas fa-calendar-alt"></i><?= $fhi_rel ?></span>
                                            <?php if ($total_pagado): ?>
                                                <span class="hdv-meta-pill"><i class="fas fa-dollar-sign"></i><?= format_money($total_pagado) ?></span>
                                            <?php endif; ?>
                                            <?php if ($procedencia !== ''): ?>
                                                <span class="hdv-meta-pill"><i class="fas fa-map-marker-alt"></i><?= room_detail_safe($procedencia) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($reservacion['telefono'])): ?>
                                                <span class="hdv-meta-pill"><i class="fas fa-phone"></i><?= room_detail_safe($reservacion['telefono']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <a href="<?= url('/reservaciones/ver/' . $reservacion['id']) ?>" class="hdv-btn hdv-btn-ghost hdv-btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($historial_count > 10): ?>
                            <div style="margin-top:14px;">
                                <a href="<?= url('/habitaciones/' . $habitacion_id . '/historial') ?>" class="hdv-btn hdv-btn-ghost hdv-btn-full">
                                    <i class="fas fa-history"></i> Ver historial completo (<?= number_format($historial_count) ?> reservaciones)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="hdv-empty">
                            <i class="fas fa-inbox"></i>
                            <strong>Sin reservaciones previas</strong>
                            <span>Esta habitaci&oacute;n todav&iacute;a no tiene historial.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>

        <!-- ═══ SIDEBAR ═══ -->
        <aside class="hdv-side" aria-label="Panel operativo">

            <!-- Quick actions -->
            <div class="hdv-card">
                <div class="hdv-card-head">
                    <div class="hdv-card-title">
                        <span class="hdv-card-icon"><i class="fas fa-bolt"></i></span>
                        <div><h2>Acciones r&aacute;pidas</h2></div>
                    </div>
                </div>
                <div class="hdv-card-body">
                    <div class="hdv-action-list">
                        <a href="<?= url('habitaciones/' . $habitacion_id . '/edit') ?>" class="hdv-action">
                            <span><i class="fas fa-edit"></i> Editar habitaci&oacute;n</span>
                            <i class="fas fa-arrow-right hdv-action-arr"></i>
                        </a>
                        <a href="<?= url('habitaciones/' . $habitacion_id . '/imagenes') ?>" class="hdv-action">
                            <span><i class="fas fa-images"></i> Gestionar fotograf&iacute;as</span>
                            <i class="fas fa-arrow-right hdv-action-arr"></i>
                        </a>
                        <a href="<?= url('configuracion#hc-owners') ?>" class="hdv-action">
                            <span><i class="fas fa-user-tie"></i> Configurar propietario</span>
                            <i class="fas fa-arrow-right hdv-action-arr"></i>
                        </a>

                        <?php if ($habitacion_estado == 'disponible'): ?>
                            <a href="<?= url('reservaciones/crear?habitacion=' . $habitacion_id) ?>" class="hdv-btn hdv-btn-success hdv-btn-full" style="margin-top:4px;">
                                <i class="fas fa-calendar-plus"></i> Nueva reservaci&oacute;n
                            </a>
                            <button type="button" onclick="mostrarModalMantenimiento()" class="hdv-btn hdv-btn-warning hdv-btn-full">
                                <i class="fas fa-tools"></i> Iniciar mantenimiento
                            </button>
                            <button type="button" onclick="mostrarModalProgramarMantenimiento()" class="hdv-btn hdv-btn-ghost hdv-btn-full">
                                <i class="fas fa-calendar-check"></i> Programar mantenimiento
                            </button>
                        <?php elseif ($habitacion_estado == 'limpieza'): ?>
                            <form method="POST"
                                  action="<?= url('habitaciones/' . $habitacion_id . '/liberar') ?>"
                                  class="js-finalizar-limpieza-form"
                                  data-room-number="<?= room_detail_safe($habitacion_numero) ?>"
                                  data-habitacion-id="<?= (int)$habitacion_id ?>"
                                  style="margin-top:4px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="finalizar">
                                <button type="submit" class="hdv-btn hdv-btn-success hdv-btn-full">
                                    <i class="fas fa-check-circle"></i> Finalizar limpieza
                                </button>
                            </form>
                        <?php else: ?>
                            <p class="hdv-note" style="margin-top:4px;">
                                No hay cambios de estado disponibles para este estado. Edita la habitaci&oacute;n o revisa el historial.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Room data -->
            <div class="hdv-card">
                <div class="hdv-card-head">
                    <div class="hdv-card-title">
                        <span class="hdv-card-icon"><i class="fas fa-circle-info"></i></span>
                        <div><h2>Datos de la habitaci&oacute;n</h2></div>
                    </div>
                </div>
                <div class="hdv-card-body">
                    <!-- Price prominent -->
                    <div style="margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                        <p class="hdv-price-label">Tarifa vigente</p>
                        <p class="hdv-price-big"><?= format_money($precio_actual) ?> <span style="font-size:.9rem;font-weight:500;color:var(--subtle);">/ noche</span></p>
                        <?php if ($tiene_incremento): ?>
                            <span class="hdv-price-note">
                                <i class="fas fa-info-circle"></i>
                                Base <?= format_money($precio_base_original) ?> + <?= format_money($incremento_total) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <dl class="hdv-data-list">
                        <div class="hdv-data-row">
                            <dt>Tipo</dt>
                            <dd><?= room_detail_safe($tipo_label) ?></dd>
                        </div>
                        <div class="hdv-data-row">
                            <dt>Piso</dt>
                            <dd><?= room_detail_safe($piso_label) ?></dd>
                        </div>
                        <div class="hdv-data-row">
                            <dt>Capacidad</dt>
                            <dd><?= $capacidad > 0 ? number_format($capacidad) . ' personas' : 'No definida' ?></dd>
                        </div>
                        <div class="hdv-data-row">
                            <dt>Camas</dt>
                            <dd>
                                <?= $camas_total > 0 ? number_format($camas_total) . ' total' : 'No definidas' ?>
                                <?php if ($camas_total > 0): ?>
                                    <br><small style="font-weight:500;color:var(--subtle);"><?= $camas_matrimoniales ?> mat. / <?= $camas_individuales ?> ind.</small>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="hdv-data-row">
                            <dt>Propietario</dt>
                            <dd>
                                <?= room_detail_safe($propietario_nombre) ?>
                                <?php if ($propietario_pct_label !== ''): ?>
                                    <br><small style="font-weight:500;color:var(--subtle);"><?= room_detail_safe($propietario_pct_label) ?></small>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="hdv-data-row">
                            <dt>Sistema</dt>
                            <dd style="color:<?= $activa ? 'var(--green)' : 'var(--red)' ?>;"><?= $activa ? 'Activa' : 'Inactiva' ?></dd>
                        </div>
                    </dl>

                    <?php if ($caracteristicas !== ''): ?>
                        <p style="margin-top:13px;padding-top:13px;border-top:1px solid var(--border);font-size:.8rem;color:var(--muted);line-height:1.5;">
                            <?= nl2br(room_detail_safe($caracteristicas)) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Next checkout -->
            <?php if ($proxima_salida): ?>
                <div class="hdv-card">
                    <div class="hdv-card-head">
                        <div class="hdv-card-title">
                            <span class="hdv-card-icon" style="background:var(--red-soft);color:var(--red);"><i class="fas fa-sign-out-alt"></i></span>
                            <div><h2>Pr&oacute;xima salida</h2></div>
                        </div>
                    </div>
                    <div class="hdv-card-body">
                        <dl class="hdv-data-list">
                            <div class="hdv-data-row">
                                <dt>Fecha</dt>
                                <dd><?= format_date($proxima_salida['fecha_salida'] ?? '') ?></dd>
                            </div>
                            <?php if (!empty($proxima_salida['nombre_completo'])): ?>
                                <div class="hdv-data-row">
                                    <dt>Hu&eacute;sped</dt>
                                    <dd><?= room_detail_safe($proxima_salida['nombre_completo']) ?></dd>
                                </div>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Full history link -->
            <div class="hdv-card">
                <div class="hdv-card-body">
                    <a href="<?= url('/habitaciones/' . $habitacion_id . '/historial') ?>" class="hdv-action">
                        <span><i class="fas fa-history"></i> Ver historial completo</span>
                        <i class="fas fa-arrow-right hdv-action-arr"></i>
                    </a>
                </div>
            </div>

        </aside>
    </div>
</div>
</div>

<!-- ═══ MODAL: Iniciar mantenimiento ═══ -->
<div id="modalMantenimiento" class="hdv-modal hidden">
    <div class="hdv-modal-card" id="modalContent">
        <div class="hdv-modal-head">
            <div class="hdv-modal-title">
                <span class="hdv-modal-icon"><i class="fas fa-tools"></i></span>
                <div class="hdv-modal-title-text">
                    <span class="hdv-modal-kicker">Cambio inmediato</span>
                    <h3>Iniciar mantenimiento</h3>
                    <p>Registra el trabajo y cambia la habitaci&oacute;n a mantenimiento desde este momento.</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalMantenimiento()" class="hdv-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/mantenimiento') ?>" class="hdv-modal-body" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="iniciar">

            <div class="hdv-maint-summary">
                <span><i class="fas fa-door-open"></i>Hab. <?= room_detail_safe($habitacion_numero) ?></span>
                <span><i class="fas fa-bed"></i><?= room_detail_safe($tipo_label) ?></span>
                <span><i class="fas fa-circle"></i><?= room_detail_safe($status_meta['label'] ?? $estado_actual) ?></span>
            </div>

            <?php if (!empty($reservas_mantenimiento_proximas)): ?>
                <div class="rd-maint-risk" role="alert">
                    <div class="rd-maint-risk-head">
                        <span class="rd-maint-risk-icon"><i class="fas fa-triangle-exclamation"></i></span>
                        <div>
                            <strong class="rd-maint-risk-title">Reserva pr&oacute;xima detectada</strong>
                            <span class="rd-maint-risk-copy">Revisa si la habitaci&oacute;n debe reasignarse o si el trabajo termina antes de la llegada.</span>
                        </div>
                    </div>
                    <div class="rd-maint-risk-list">
                        <?php foreach (array_slice($reservas_mantenimiento_proximas, 0, 3) as $reservaRiesgo): ?>
                            <?php
                            $fechaRiesgo = !empty($reservaRiesgo['fecha_entrada']) ? date('d/m/Y', strtotime((string)$reservaRiesgo['fecha_entrada'])) : '-';
                            $horaRiesgo = !empty($reservaRiesgo['hora_llegada_estimada']) ? substr((string)$reservaRiesgo['hora_llegada_estimada'], 0, 5) : '';
                            ?>
                            <div class="rd-maint-risk-item">
                                <div>
                                    <strong><?= room_detail_safe($reservaRiesgo['nombre_completo'] ?? 'Huesped') ?></strong>
                                    <span>Reserva #<?= (int)($reservaRiesgo['id'] ?? 0) ?> &mdash; llega <?= room_detail_safe($fechaRiesgo . ($horaRiesgo !== '' ? ' ' . $horaRiesgo : '')) ?></span>
                                </div>
                                <span class="rd-maint-risk-badge"><?= room_detail_safe(ucfirst((string)($reservaRiesgo['estado'] ?? 'confirmada'))) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <label class="rd-maint-confirm">
                        <input type="checkbox" name="confirmar_conflicto_mantenimiento" value="1" required>
                        <span>Confirmo que el hotel ya revis&oacute; esta reservaci&oacute;n y acepta iniciar mantenimiento.</span>
                    </label>
                </div>
            <?php endif; ?>

            <div class="hdv-form-grid">
                <div>
                    <label class="hdv-modal-label">Tipo de mantenimiento</label>
                    <select name="tipo_mantenimiento" required class="hdv-modal-input">
                        <option value="">Seleccione...</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="correctivo">Correctivo</option>
                        <option value="emergencia">Emergencia</option>
                        <option value="limpieza_profunda">Limpieza profunda</option>
                    </select>
                </div>
                <div>
                    <label class="hdv-modal-label">Prioridad</label>
                    <select name="prioridad" required class="hdv-modal-input">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="hdv-modal-label">Motivo</label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." class="hdv-modal-input">
            </div>

            <?php if (function_exists('current_hotel_has_module') && current_hotel_has_module('mantenimiento_plus')): ?>
            <div>
                <label class="hdv-modal-label">Fotos del problema <span style="font-weight:400;color:var(--muted,#828B99);">(opcional, m&aacute;x 3)</span></label>
                <label style="display:flex;align-items:center;justify-content:center;gap:8px;padding:11px 14px;border:1px dashed rgba(189,148,65,.5);border-radius:12px;cursor:pointer;background:rgba(189,148,65,.06);font-weight:700;font-size:.84rem;">
                    <input type="file" name="fotos_reporte[]" accept="image/*" capture="environment" multiple style="display:none;" onchange="var n=this.files?this.files.length:0;this.nextElementSibling.nextElementSibling.textContent=n>0?'('+n+')':'';">
                    <i class="fas fa-camera"></i> Tomar o elegir fotos
                    <span></span>
                </label>
            </div>
            <?php endif; ?>

            <div class="hdv-modal-actions">
                <button type="button" onclick="cerrarModalMantenimiento()" class="hdv-btn hdv-btn-ghost">Cancelar</button>
                <button type="submit" class="hdv-btn hdv-btn-warning">
                    <i class="fas fa-check"></i> Iniciar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ MODAL: Programar mantenimiento ═══ -->
<div id="modalProgramarMantenimiento" class="hdv-modal hidden">
    <div class="hdv-modal-card" id="modalProgramarContent">
        <div class="hdv-modal-head">
            <div class="hdv-modal-title">
                <span class="hdv-modal-icon" style="background:var(--blue-soft);color:var(--blue);border-color:rgba(47,110,168,.22);">
                    <i class="fas fa-calendar-check"></i>
                </span>
                <div class="hdv-modal-title-text">
                    <span class="hdv-modal-kicker" style="background:var(--blue-soft);color:var(--blue);border-color:rgba(47,110,168,.18);">Futuro</span>
                    <h3>Programar mantenimiento</h3>
                    <p>Agenda un bloqueo preventivo para evitar reservaciones que choquen con el trabajo.</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalProgramarMantenimiento()" class="hdv-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/programar-mantenimiento') ?>" class="hdv-modal-body">
            <?= csrf_field() ?>

            <div class="hdv-maint-summary">
                <span><i class="fas fa-door-open"></i>Hab. <?= room_detail_safe($habitacion_numero) ?></span>
                <span><i class="fas fa-bed"></i><?= room_detail_safe($tipo_label) ?></span>
                <span><i class="fas fa-circle"></i><?= room_detail_safe($status_meta['label'] ?? $estado_actual) ?></span>
            </div>

            <div class="hdv-modal-note">
                <i class="fas fa-info"></i>
                <span>La habitaci&oacute;n seguir&aacute; disponible hasta la fecha programada, pero no se podr&aacute;n hacer reservaciones que conflicten con el mantenimiento.</span>
            </div>

            <div id="programarMantenimientoRiesgo" class="rd-maint-risk hidden" role="alert" aria-live="polite">
                <div class="rd-maint-risk-head">
                    <span class="rd-maint-risk-icon"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <strong class="rd-maint-risk-title">Reservaciones en el rango seleccionado</strong>
                        <span class="rd-maint-risk-copy">El mantenimiento se empalma con llegadas o estancias activas. Confirma solo si ya se har&aacute; reasignaci&oacute;n.</span>
                    </div>
                </div>
                <div class="rd-maint-risk-list" data-maint-risk-list></div>
                <label class="rd-maint-confirm">
                    <input type="checkbox" name="confirmar_conflicto_mantenimiento" value="1" data-maint-risk-confirm>
                    <span>Confirmo que el hotel ya revis&oacute; estas reservaciones y acepta programar mantenimiento.</span>
                </label>
                <span class="rd-maint-risk-error">Marca la confirmaci&oacute;n para continuar.</span>
            </div>

            <div class="hdv-form-grid">
                <div>
                    <label class="hdv-modal-label">Fecha inicio *</label>
                    <input type="date" name="fecha_programada" required min="<?= date('Y-m-d') ?>" class="hdv-modal-input">
                </div>
                <div>
                    <label class="hdv-modal-label">Fecha fin</label>
                    <input type="date" name="fecha_programada_fin" min="<?= date('Y-m-d') ?>" class="hdv-modal-input">
                </div>
            </div>

            <div class="hdv-form-grid">
                <div>
                    <label class="hdv-modal-label">Tipo de mantenimiento</label>
                    <select name="tipo_mantenimiento" required class="hdv-modal-input">
                        <option value="">Seleccione...</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="correctivo">Correctivo</option>
                        <option value="emergencia">Emergencia</option>
                        <option value="limpieza_profunda">Limpieza profunda</option>
                    </select>
                </div>
                <div>
                    <label class="hdv-modal-label">Prioridad</label>
                    <select name="prioridad" required class="hdv-modal-input">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="hdv-modal-label">Motivo</label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." class="hdv-modal-input">
            </div>

            <div class="hdv-modal-actions">
                <button type="button" onclick="cerrarModalProgramarMantenimiento()" class="hdv-btn hdv-btn-ghost">Cancelar</button>
                <button type="submit" class="hdv-btn hdv-btn-info">
                    <i class="fas fa-calendar-check"></i> Programar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══ LIGHTBOX ═══ -->
<div id="lightbox" class="hdv-lightbox hidden" onclick="cerrarLightbox()">
    <div class="hdv-lightbox-frame" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" alt="Fotografia ampliada">

        <?php if (count($imagenes ?? []) > 1): ?>
            <button type="button" onclick="navegarLightbox(-1)" class="hdv-lb-btn hdv-lb-prev" aria-label="Foto anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" onclick="navegarLightbox(1)" class="hdv-lb-btn hdv-lb-next" aria-label="Foto siguiente">
                <i class="fas fa-chevron-right"></i>
            </button>
            <div class="hdv-lb-count"><span id="lightbox-counter">1 / <?= count($imagenes) ?></span></div>
            <div class="hdv-lb-thumbs">
                <?php foreach ($imagenes as $index => $imagen): ?>
                    <img src="<?= image_url($imagen['url']) ?>"
                         alt="Miniatura <?= $index + 1 ?>"
                         class="lightbox-thumb"
                         onclick="cambiarImagenLightbox(<?= $index ?>)"
                         data-index="<?= $index ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="button" onclick="cerrarLightbox()" class="hdv-lb-close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
let currentIndex = 0;
let lightboxIndex = 0;
const imagenes = <?= json_encode(array_map(function($img) { return image_url($img['url']); }, $imagenes ?? [])) ?>;

document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', handleKeyPress);

    let touchStartX = 0;
    const lightbox = document.getElementById('lightbox');
    if (lightbox) {
        lightbox.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
        lightbox.addEventListener('touchend', e => {
            const diff = touchStartX - e.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) navegarLightbox(diff > 0 ? 1 : -1);
        });
    }
});

function cambiarImagen(url, index) {
    const img = document.getElementById('imagen-principal');
    if (!img) return;
    img.src = url;
    currentIndex = index;
    const contador = document.getElementById('contador');
    if (contador) contador.textContent = `${index + 1} / ${imagenes.length}`;
    document.querySelectorAll('.room-thumb').forEach((t, i) => t.classList.toggle('thumb-active', i === index));
}

function hideSidebar() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = 'none';
}
function showSidebar() {
    const s = document.getElementById('sidebar');
    if (s) s.style.display = '';
}
function lockPage() { document.body.style.overflow = 'hidden'; }
function unlockPage() { document.body.style.overflow = ''; }

function abrirLightbox(url) {
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    if (!lightbox || !img) return;
    hideSidebar();
    lightboxIndex = imagenes.indexOf(url);
    if (lightboxIndex === -1) lightboxIndex = currentIndex || 0;
    img.src = url;
    lightbox.classList.remove('hidden');
    lockPage();
    actualizarLightbox();
}

function cerrarLightbox() {
    const lightbox = document.getElementById('lightbox');
    if (lightbox) lightbox.classList.add('hidden');
    showSidebar();
    unlockPage();
}

function navegarLightbox(dir) {
    if (imagenes.length <= 1) return;
    lightboxIndex = (lightboxIndex + dir + imagenes.length) % imagenes.length;
    actualizarLightbox();
}

function cambiarImagenLightbox(index) {
    lightboxIndex = index;
    actualizarLightbox();
}

function actualizarLightbox() {
    const img = document.getElementById('lightbox-img');
    if (img && imagenes[lightboxIndex]) img.src = imagenes[lightboxIndex];
    const counter = document.getElementById('lightbox-counter');
    if (counter) counter.textContent = `${lightboxIndex + 1} / ${imagenes.length}`;
    document.querySelectorAll('.lightbox-thumb').forEach((t, i) => t.classList.toggle('is-active', i === lightboxIndex));
}

function handleKeyPress(e) {
    const lb = document.getElementById('lightbox');
    if (!lb || lb.classList.contains('hidden')) return;
    if (e.key === 'Escape') cerrarLightbox();
    if (e.key === 'ArrowLeft') navegarLightbox(-1);
    if (e.key === 'ArrowRight') navegarLightbox(1);
}

const reservasMantenimientoHabitacion = <?= $reservas_mantenimiento_json ?>;

function maintAddDays(dateString, days) {
    const date = new Date(dateString + 'T00:00:00');
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}
function maintFormatDate(ds) {
    if (!ds) return '-';
    const p = ds.split('-');
    return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : ds;
}
function maintRenderRiskItem(reserva) {
    const item = document.createElement('div');
    item.className = 'rd-maint-risk-item';
    const body = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = reserva.nombre || 'Huesped';
    const meta = document.createElement('span');
    const hora = reserva.hora_llegada ? ' ' + reserva.hora_llegada : '';
    meta.textContent = 'Reserva #' + reserva.id + ' — llega ' + maintFormatDate(reserva.fecha_entrada) + hora;
    body.appendChild(title);
    body.appendChild(meta);
    const badge = document.createElement('span');
    badge.className = 'rd-maint-risk-badge';
    badge.textContent = reserva.estado || 'confirmada';
    item.appendChild(body);
    item.appendChild(badge);
    return item;
}

function actualizarRiesgoMantenimientoProgramado() {
    const form = document.querySelector('#modalProgramarMantenimiento form');
    const panel = document.getElementById('programarMantenimientoRiesgo');
    if (!form || !panel) return;
    const inicioInput = form.querySelector('input[name="fecha_programada"]');
    const finInput = form.querySelector('input[name="fecha_programada_fin"]');
    const confirmInput = form.querySelector('[data-maint-risk-confirm]');
    const list = panel.querySelector('[data-maint-risk-list]');
    const inicio = inicioInput ? inicioInput.value : '';
    const fin = finInput && finInput.value ? finInput.value : inicio;
    panel.classList.remove('has-error');
    if (!inicio || !fin || fin < inicio) {
        panel.classList.add('hidden');
        if (confirmInput) { confirmInput.required = false; confirmInput.checked = false; }
        if (list) list.replaceChildren();
        return;
    }
    const finExclusivo = maintAddDays(fin, 1);
    const conflictos = reservasMantenimientoHabitacion.filter(r => r.fecha_entrada < finExclusivo && r.fecha_salida > inicio);
    if (!conflictos.length) {
        panel.classList.add('hidden');
        if (confirmInput) { confirmInput.required = false; confirmInput.checked = false; }
        if (list) list.replaceChildren();
        return;
    }
    panel.classList.remove('hidden');
    if (confirmInput) confirmInput.required = true;
    if (list) {
        list.replaceChildren();
        conflictos.slice(0, 4).forEach(r => list.appendChild(maintRenderRiskItem(r)));
    }
}

function mostrarModalMantenimiento() {
    const modal = document.getElementById('modalMantenimiento');
    const content = document.getElementById('modalContent');
    if (!modal || !content) return;
    hideSidebar();
    modal.classList.remove('hidden');
    lockPage();
    setTimeout(() => { content.style.transform = 'scale(1)'; content.style.opacity = '1'; }, 10);
}

function cerrarModalMantenimiento() {
    const modal = document.getElementById('modalMantenimiento');
    const content = document.getElementById('modalContent');
    if (!modal || !content) return;
    content.style.transform = 'scale(0.96)';
    content.style.opacity = '0';
    setTimeout(() => {
        modal.classList.add('hidden');
        const form = modal.querySelector('form');
        if (form) form.reset();
        showSidebar();
        unlockPage();
    }, 220);
}

function mostrarModalProgramarMantenimiento() {
    const modal = document.getElementById('modalProgramarMantenimiento');
    const content = document.getElementById('modalProgramarContent');
    if (!modal || !content) return;
    hideSidebar();
    modal.classList.remove('hidden');
    actualizarRiesgoMantenimientoProgramado();
    lockPage();
    setTimeout(() => { content.style.transform = 'scale(1)'; content.style.opacity = '1'; }, 10);
}

function cerrarModalProgramarMantenimiento() {
    const modal = document.getElementById('modalProgramarMantenimiento');
    const content = document.getElementById('modalProgramarContent');
    if (!modal || !content) return;
    content.style.transform = 'scale(0.96)';
    content.style.opacity = '0';
    setTimeout(() => {
        modal.classList.add('hidden');
        const form = modal.querySelector('form');
        if (form) form.reset();
        actualizarRiesgoMantenimientoProgramado();
        showSidebar();
        unlockPage();
    }, 220);
}

const modalMantenimiento = document.getElementById('modalMantenimiento');
if (modalMantenimiento) {
    modalMantenimiento.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalMantenimiento();
    });
}

const modalProgramar = document.getElementById('modalProgramarMantenimiento');
if (modalProgramar) {
    modalProgramar.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalProgramarMantenimiento();
    });
    const formProgramar = modalProgramar.querySelector('form');
    if (formProgramar) {
        formProgramar.querySelectorAll('input[name="fecha_programada"], input[name="fecha_programada_fin"]').forEach(input => {
            input.addEventListener('change', actualizarRiesgoMantenimientoProgramado);
            input.addEventListener('input', actualizarRiesgoMantenimientoProgramado);
        });
        formProgramar.addEventListener('submit', function(e) {
            actualizarRiesgoMantenimientoProgramado();
            const panel = document.getElementById('programarMantenimientoRiesgo');
            const confirmInput = formProgramar.querySelector('[data-maint-risk-confirm]');
            if (panel && !panel.classList.contains('hidden') && confirmInput && !confirmInput.checked) {
                e.preventDefault();
                panel.classList.add('has-error');
                confirmInput.focus();
            }
        });
    }
}

async function realizarCheckout(reservacionId) {
    const ok = await msConfirm({
        type: 'warning',
        icon: 'logout',
        title: '¿Realizar check-out?',
        msg: 'Se registrará la salida del huésped y la habitación pasará a limpieza.',
        confirmLabel: 'Registrar check-out'
    });
    if (ok) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('reservaciones/check-out/') ?>' + reservacionId;
        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = 'csrf_token';
        csrfField.value = '<?= csrf_token() ?>';
        form.appendChild(csrfField);

        // Selector opcional de responsable de limpieza (cancelable)
        if (window.CheckoutLimpieza) {
            const asignaciones = await CheckoutLimpieza.seleccionar({
                infoUrl: '<?= url('api/reservaciones') ?>/' + reservacionId + '/limpieza-personal'
            });
            if (asignaciones === null) return; // usuario cancelo el check-out
            CheckoutLimpieza.aplicarAForm(form, asignaciones);
        }

        document.body.appendChild(form);
        form.submit();
    }
}

document.querySelectorAll('.js-finalizar-limpieza-form').forEach(function(form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const roomNumber = form.dataset.roomNumber || 'esta habitacion';
        const originalButtonHtml = submitButton ? submitButton.innerHTML : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
        }
        const showError = function(message) {
            if (submitButton) { submitButton.disabled = false; submitButton.innerHTML = originalButtonHtml; }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'No se pudo finalizar', text: message || 'Intentalo de nuevo.', confirmButtonColor: '#dc2626' });
                return;
            }
            window.msToast('error', 'No se pudo finalizar', message || 'La habitacion no pudo marcarse como disponible.');
        };

        // Selector obligatorio: quien hizo la limpieza (una o mas personas)
        // o la eleccion explicita "Sin registrar personal".
        if (window.LimpiezaPersonal) {
            const seleccionPersonal = await LimpiezaPersonal.elegir({
                infoUrl: '<?= url('api/habitaciones/limpieza-personal') ?>',
                habitacionId: parseInt(form.dataset.habitacionId || '0', 10) || undefined
            });
            if (seleccionPersonal === null) {
                if (submitButton) { submitButton.disabled = false; submitButton.innerHTML = originalButtonHtml; }
                return; // usuario cancelo
            }
            // Evitar duplicados si el envio anterior fallo y se reintenta.
            form.querySelectorAll('input[name="personal_confirmado"], input[name="sin_personal"], input[name="trabajador_ids[]"]').forEach(function(el) { el.remove(); });
            LimpiezaPersonal.aplicarAForm(form, seleccionPersonal);
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Procesando...', html: 'Actualizando el estado de la habitacion.', allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false, didOpen: () => Swal.showLoading() });
        }
        fetch(form.action, {
            method: form.method || 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new URLSearchParams(new FormData(form)).toString()
        })
        .then(function(response) {
            return response.text().then(function(text) {
                let data = null;
                try { data = text ? JSON.parse(text) : null; } catch (err) { throw new Error('El servidor no devolvio una respuesta valida.'); }
                if (!response.ok || !data || data.success !== true) throw new Error(data?.message || 'No se pudo actualizar el estado.');
                return data;
            });
        })
        .then(function(data) {
            const numero = data.numero || roomNumber;
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Habitacion disponible', text: 'La habitacion ' + numero + ' ya quedo lista.', confirmButtonColor: '#596066', confirmButtonText: 'Entendido' }).then(() => window.location.reload());
                return;
            }
            window.msToast('success', 'Habitación disponible', 'La habitacion ' + numero + ' ya quedo disponible.');
            setTimeout(function(){ window.location.reload(); }, 1200);
        })
        .catch(function(error) { showError(error.message); });
    });
});
</script>
