<?php
/**
 * Vista de detalles de habitacion.
 * Redisenada como centro operativo de habitacion sin cambiar rutas ni formularios.
 */

$habitacion = $habitacion ?? [];
$ocupacion_actual = $ocupacion_actual ?? null;
$proxima_salida = $proxima_salida ?? null;
$historial_reciente = is_array($historial_reciente ?? null) ? $historial_reciente : [];
$mantenimiento_actual = $mantenimiento_actual ?? null;
$mantenimientos_programados = is_array($mantenimientos_programados ?? null) ? $mantenimientos_programados : [];
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

if (!function_exists('room_detail_safe')) {
    function room_detail_safe($value, $fallback = '-') {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('room_detail_status_meta')) {
    function room_detail_status_meta($estado, $estadoInfo = []) {
        $map = [
            'disponible' => ['label' => 'Disponible', 'icon' => 'check-circle', 'color' => '#157A52', 'soft' => '#E8F6ED'],
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

<style id="room-detail-redesign">
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap');

.vista-habitacion.room-detail-page {
    --rd-brand: var(--brand-primary, #1B2746);
    --rd-brand-dark: var(--brand-secondary, #0F172A);
    --rd-accent: var(--brand-accent, #BD9441);
    --rd-accent-deep: color-mix(in srgb, var(--rd-accent) 74%, #3B2D12);
    --rd-bg: color-mix(in srgb, var(--rd-accent) 8%, #F8F3EA);
    --rd-bg-soft: color-mix(in srgb, var(--rd-accent) 4%, #FFFCF6);
    --rd-panel: color-mix(in srgb, var(--rd-accent) 2%, #FFFDF8);
    --rd-panel-warm: color-mix(in srgb, var(--rd-accent) 7%, #FFFDF8);
    --rd-line: color-mix(in srgb, var(--rd-brand) 14%, #E8DCCA);
    --rd-line-strong: color-mix(in srgb, var(--rd-accent) 36%, #D7C3A2);
    --rd-muted: color-mix(in srgb, var(--rd-brand-dark) 50%, #94A3B8);
    --rd-text: #182033;
    --rd-success: #157A52;
    --rd-danger: #B9463D;
    --rd-warning: #B66A00;
    --rd-info: #2F6EA8;
    --rd-serif: 'Cormorant Garamond', Georgia, serif;
    --rd-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    opacity: 0;
    background:
        radial-gradient(circle at 92% 5%, color-mix(in srgb, var(--rd-accent) 22%, transparent), transparent 30rem),
        linear-gradient(90deg, color-mix(in srgb, var(--rd-brand) 5%, transparent) 0 1px, transparent 1px 34px),
        linear-gradient(180deg, var(--rd-bg-soft), var(--rd-bg) 58%, #F4EBDC);
    color: var(--rd-text);
    font-family: var(--rd-sans);
    -webkit-font-smoothing: antialiased;
    text-rendering: optimizeLegibility;
    transition: opacity .32s ease;
}

.vista-habitacion.room-detail-page.loaded {
    opacity: 1;
}

.room-detail-page *,
.room-detail-page *::before,
.room-detail-page *::after {
    box-sizing: border-box;
}

.room-detail-page :where(a, button, input, textarea, select, label, span, p) {
    font-family: var(--rd-sans);
}

.room-detail-page i[class*="fa-"],
.room-detail-page .fas,
.room-detail-page .far,
.room-detail-page .fab {
    font-family: "Font Awesome 5 Free", "Font Awesome 5 Brands", "FontAwesome" !important;
    font-style: normal;
}

.room-detail-page .fas {
    font-weight: 900;
}

.rd-shell {
    width: min(1500px, calc(100% - 30px));
    margin: 0 auto;
    padding: 26px 0 46px;
}

.rd-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 14px;
    color: var(--rd-muted);
    font-size: .8rem;
    font-weight: 800;
}

.rd-breadcrumb a {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: var(--rd-brand);
    transition: color .18s ease, transform .18s ease;
}

.rd-breadcrumb a:hover {
    color: var(--rd-accent-deep);
    transform: translateY(-1px);
}

.rd-hero {
    overflow: hidden;
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(340px, .92fr);
    gap: 0;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 28%, transparent);
    border-radius: 30px;
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--rd-accent) 22%, transparent), transparent 18rem),
        linear-gradient(135deg, rgba(255,253,248,.98), rgba(248,240,226,.92));
    box-shadow: 0 30px 78px -58px rgba(15, 23, 42, .76);
}

.rd-hero-copy {
    position: relative;
    min-width: 0;
    padding: clamp(22px, 3.8vw, 42px);
}

.rd-hero-copy::after {
    content: "";
    position: absolute;
    inset: auto 28px 0 auto;
    width: min(260px, 46vw);
    height: 3px;
    border-radius: 999px 999px 0 0;
    background: linear-gradient(90deg, transparent, var(--rd-accent), var(--rd-brand));
    opacity: .72;
}

.rd-kicker {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 11px;
    color: color-mix(in srgb, var(--rd-accent) 78%, var(--rd-brand));
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
}

.rd-title {
    margin: 0;
    color: var(--rd-brand);
    font-family: var(--rd-serif);
    font-size: clamp(3.2rem, 8vw, 7.5rem);
    line-height: .82;
    font-weight: 700;
    letter-spacing: 0;
    text-wrap: balance;
}

.rd-title span {
    display: block;
    color: var(--rd-muted);
    font-family: var(--rd-sans);
    font-size: clamp(.86rem, 1.3vw, 1rem);
    line-height: 1.4;
    font-weight: 900;
    letter-spacing: .08em;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.rd-subtitle {
    max-width: 68ch;
    margin: 14px 0 0;
    color: var(--rd-muted);
    font-size: .95rem;
    font-weight: 700;
    line-height: 1.58;
}

.rd-hero-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin-top: 18px;
}

.rd-chip,
.rd-status-pill,
.rd-price-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-height: 34px;
    padding: 8px 11px;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 900;
}

.rd-chip {
    border: 1px solid var(--rd-line);
    background: color-mix(in srgb, var(--rd-accent) 6%, #FFFDF8);
    color: var(--rd-brand);
}

.rd-status-pill {
    background: var(--status-soft);
    color: var(--status-color);
    border: 1px solid color-mix(in srgb, var(--status-color) 24%, transparent);
}

.rd-hero-media {
    position: relative;
    min-height: 340px;
    background:
        linear-gradient(145deg, color-mix(in srgb, var(--rd-brand) 94%, #000000), var(--rd-brand-dark));
}

.rd-hero-media img {
    width: 100%;
    height: 100%;
    min-height: 340px;
    object-fit: cover;
    display: block;
    cursor: zoom-in;
}

.rd-hero-media::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: linear-gradient(180deg, transparent 30%, rgba(15,23,42,.24));
}

.rd-media-placeholder {
    height: 100%;
    min-height: 340px;
    display: grid;
    place-items: center;
    color: rgba(255,253,248,.92);
    text-align: center;
    padding: 24px;
}

.rd-media-placeholder i {
    display: block;
    font-size: 3rem;
    margin-bottom: 12px;
    opacity: .72;
}

.rd-media-placeholder strong {
    display: block;
    font-family: var(--rd-serif);
    font-size: clamp(2.4rem, 7vw, 5rem);
    line-height: .9;
}

.rd-floating-price {
    position: absolute;
    right: 18px;
    bottom: 18px;
    z-index: 1;
    min-width: 190px;
    padding: 14px 16px;
    border: 1px solid rgba(255,253,248,.28);
    border-radius: 20px;
    background: rgba(15, 23, 42, .72);
    color: #FFFDF8;
    backdrop-filter: blur(14px);
}

.rd-floating-price span {
    display: block;
    opacity: .78;
    font-size: .72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.rd-floating-price strong {
    display: block;
    margin-top: 4px;
    font-size: 1.45rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.rd-floating-price small {
    display: block;
    margin-top: 2px;
    opacity: .74;
    font-size: .76rem;
    font-weight: 800;
}

.rd-alert {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 14px;
    margin-top: 16px;
    padding: 15px;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 34%, transparent);
    border-radius: 22px;
    background: color-mix(in srgb, var(--rd-accent) 12%, #FFFDF8);
}

.rd-alert-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    background: var(--rd-brand);
    color: #FFFDF8;
}

.rd-alert strong {
    display: block;
    color: var(--rd-brand);
    font-size: .96rem;
    font-weight: 950;
}

.rd-alert span {
    display: block;
    margin-top: 2px;
    color: var(--rd-muted);
    font-size: .8rem;
    font-weight: 750;
}

.rd-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(305px, 360px);
    gap: 18px;
    align-items: start;
    margin-top: 18px;
}

.rd-main,
.rd-side {
    min-width: 0;
}

.rd-main {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.rd-side {
    position: sticky;
    top: 18px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.rd-panel,
.rd-side-card {
    border: 1px solid var(--rd-line);
    border-radius: 23px;
    background: var(--rd-panel);
    box-shadow:
        0 1px 2px color-mix(in srgb, var(--rd-brand-dark) 4%, transparent),
        0 18px 42px -34px rgba(15, 23, 42, .56);
    overflow: hidden;
}

.rd-panel-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 17px 18px;
    border-bottom: 1px solid var(--rd-line);
    background: linear-gradient(90deg, var(--rd-panel-warm), var(--rd-panel));
}

.rd-panel-title {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    min-width: 0;
}

.rd-icon-box {
    width: 38px;
    height: 38px;
    flex: 0 0 auto;
    display: grid;
    place-items: center;
    border-radius: 14px;
    border: 1px solid var(--rd-line);
    background: color-mix(in srgb, var(--rd-accent) 12%, #FFFDF8);
    color: var(--rd-accent-deep);
}

.rd-panel h2,
.rd-side-card h3 {
    margin: 0;
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 950;
    line-height: 1.2;
}

.rd-panel p,
.rd-side-card p {
    margin: 5px 0 0;
    color: var(--rd-muted);
    font-size: .79rem;
    font-weight: 730;
    line-height: 1.45;
}

.rd-panel-body {
    padding: 18px;
}

.rd-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
}

.rd-stat {
    min-width: 0;
    padding: 14px;
    border: 1px solid var(--rd-line);
    border-radius: 17px;
    background: color-mix(in srgb, var(--rd-accent) 4%, #FFFDF8);
}

.rd-stat span {
    display: block;
    color: var(--rd-muted);
    font-size: .72rem;
    font-weight: 900;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.rd-stat strong {
    display: block;
    margin-top: 6px;
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 950;
    font-variant-numeric: tabular-nums;
}

.rd-actions {
    display: grid;
    gap: 10px;
}

.rd-action-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.rd-btn,
.rd-action {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    border: 1px solid var(--rd-line);
    border-radius: 15px;
    padding: 10px 13px;
    font-size: .82rem;
    font-weight: 950;
    line-height: 1.1;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
}

.rd-btn:active,
.rd-action:active {
    transform: translateY(1px) scale(.99);
}

.rd-btn:focus-visible,
.rd-action:focus-visible,
.rd-control:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--rd-accent) 42%, transparent);
    outline-offset: 2px;
}

.rd-action {
    background: var(--rd-panel);
    color: var(--rd-brand);
}

.rd-action > span {
    display: inline-flex;
    align-items: center;
    gap: 11px;
    min-width: 0;
}

.rd-action > i:last-child {
    margin-left: 3px;
    flex-shrink: 0;
}

.rd-action:hover {
    transform: translateY(-1px);
    border-color: var(--rd-line-strong);
    background: var(--rd-panel-warm);
    box-shadow: 0 18px 36px -30px rgba(15, 23, 42, .58);
}

.rd-btn-primary {
    border-color: color-mix(in srgb, var(--rd-brand) 78%, #000000);
    background: linear-gradient(145deg, var(--rd-brand), var(--rd-brand-dark));
    color: #FFFDF8;
    box-shadow: 0 18px 34px -24px color-mix(in srgb, var(--rd-brand) 72%, transparent);
}

.rd-btn-success {
    border-color: color-mix(in srgb, var(--rd-success) 45%, transparent);
    background: color-mix(in srgb, var(--rd-success) 12%, #FFFDF8);
    color: var(--rd-success);
}

.rd-btn-warning {
    border-color: color-mix(in srgb, var(--rd-warning) 35%, transparent);
    background: color-mix(in srgb, var(--rd-warning) 12%, #FFFDF8);
    color: var(--rd-warning);
}

.rd-btn-danger {
    border-color: color-mix(in srgb, var(--rd-danger) 35%, transparent);
    background: color-mix(in srgb, var(--rd-danger) 9%, #FFFDF8);
    color: var(--rd-danger);
}

.rd-btn-info {
    border-color: color-mix(in srgb, var(--rd-info) 35%, transparent);
    background: color-mix(in srgb, var(--rd-info) 10%, #FFFDF8);
    color: var(--rd-info);
}

.rd-empty-action {
    padding: 14px;
    border: 1px dashed var(--rd-line-strong);
    border-radius: 17px;
    color: var(--rd-muted);
    background: color-mix(in srgb, var(--rd-accent) 5%, transparent);
    font-size: .8rem;
    font-weight: 750;
    line-height: 1.45;
}

.rd-guest-card {
    border: 1px solid color-mix(in srgb, var(--rd-danger) 22%, var(--rd-line));
    background: color-mix(in srgb, var(--rd-danger) 6%, var(--rd-panel));
}

.rd-guest-grid,
.rd-maint-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
}

.rd-info-tile {
    min-width: 0;
    padding: 12px;
    border: 1px solid var(--rd-line);
    border-radius: 16px;
    background: rgba(255,253,248,.72);
}

.rd-info-tile span {
    display: block;
    color: var(--rd-muted);
    font-size: .7rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.rd-info-tile strong {
    display: block;
    margin-top: 5px;
    color: var(--rd-brand);
    font-size: .9rem;
    font-weight: 950;
    line-height: 1.3;
}

.rd-vehicle-list {
    display: grid;
    gap: 6px;
    margin-top: 6px;
}

.rd-vehicle-list span {
    color: var(--rd-brand);
    font-size: .78rem;
    font-weight: 850;
    text-transform: none;
    letter-spacing: 0;
}

.rd-gallery-main {
    position: relative;
    overflow: hidden;
    border-radius: 20px;
    background: color-mix(in srgb, var(--rd-brand) 10%, var(--rd-panel));
}

.rd-gallery-main img {
    width: 100%;
    height: clamp(260px, 42vw, 480px);
    object-fit: cover;
    display: block;
    cursor: zoom-in;
    transition: transform .5s ease;
}

.rd-gallery-main:hover img {
    transform: scale(1.025);
}

.rd-photo-count,
.rd-expand-btn {
    position: absolute;
    z-index: 1;
    border: 1px solid rgba(255,253,248,.26);
    background: rgba(15, 23, 42, .72);
    color: #FFFDF8;
    backdrop-filter: blur(12px);
}

.rd-photo-count {
    left: 14px;
    bottom: 14px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 11px;
    border-radius: 999px;
    font-size: .76rem;
    font-weight: 900;
}

.rd-expand-btn {
    right: 14px;
    top: 14px;
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    transition: transform .18s ease, background .18s ease;
}

.rd-expand-btn:hover {
    transform: translateY(-1px);
    background: rgba(15, 23, 42, .84);
}

.rd-thumb-strip {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding: 12px 2px 2px;
    scrollbar-width: thin;
}

.rd-thumb-strip img {
    width: 92px;
    height: 78px;
    flex: 0 0 auto;
    object-fit: cover;
    border: 2px solid transparent;
    border-radius: 15px;
    cursor: pointer;
    opacity: .72;
    transition: transform .18s ease, opacity .18s ease, border-color .18s ease;
}

.rd-thumb-strip img:hover,
.rd-thumb-strip img.thumb-active {
    opacity: 1;
    transform: translateY(-2px);
    border-color: var(--rd-accent);
}

.rd-empty {
    display: grid;
    place-items: center;
    min-height: 220px;
    text-align: center;
    gap: 8px;
    border: 1px dashed var(--rd-line-strong);
    border-radius: 20px;
    background: color-mix(in srgb, var(--rd-accent) 5%, transparent);
    color: var(--rd-muted);
}

.rd-empty i {
    font-size: 2.2rem;
    color: color-mix(in srgb, var(--rd-accent) 72%, var(--rd-brand));
}

.rd-empty strong {
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 950;
}

.rd-maint-card {
    border-color: color-mix(in srgb, var(--rd-warning) 26%, var(--rd-line));
    background: color-mix(in srgb, var(--rd-warning) 7%, var(--rd-panel));
}

.rd-scheduled-list {
    display: grid;
    gap: 10px;
}

.rd-scheduled-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 12px;
    align-items: center;
    padding: 13px;
    border: 1px solid color-mix(in srgb, var(--rd-warning) 24%, var(--rd-line));
    border-radius: 17px;
    background: rgba(255,253,248,.76);
}

.rd-scheduled-item strong {
    display: block;
    color: var(--rd-brand);
    font-size: .9rem;
    font-weight: 950;
}

.rd-scheduled-item span {
    display: block;
    margin-top: 3px;
    color: var(--rd-muted);
    font-size: .76rem;
    font-weight: 760;
}

.rd-focus-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.rd-focus-card {
    min-width: 0;
    border: 1px solid var(--rd-line);
    border-radius: 20px;
    background: var(--rd-panel);
    overflow: hidden;
}

.rd-focus-card header {
    padding: 13px 14px;
    border-bottom: 1px solid var(--rd-line);
    background: var(--rd-panel-warm);
}

.rd-focus-card header strong {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--rd-brand);
    font-size: .9rem;
    font-weight: 950;
}

.rd-focus-card .rd-focus-body {
    padding: 14px;
}

.rd-focus-card h3 {
    margin: 0 0 10px;
    color: var(--rd-brand);
    font-size: 1.08rem;
    font-weight: 950;
}

.rd-date-pair {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 9px;
    margin: 10px 0;
}

.rd-date-box {
    padding: 10px;
    border: 1px solid var(--rd-line);
    border-radius: 14px;
    background: color-mix(in srgb, var(--rd-accent) 4%, #FFFDF8);
}

.rd-date-box span {
    display: block;
    color: var(--rd-muted);
    font-size: .68rem;
    font-weight: 900;
    text-transform: uppercase;
}

.rd-date-box strong {
    display: block;
    margin-top: 4px;
    color: var(--rd-brand);
    font-size: .86rem;
    font-weight: 950;
}

.rd-mini-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
}

.rd-mini-tags span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 6px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rd-brand) 6%, #FFFDF8);
    color: var(--rd-brand);
    font-size: .72rem;
    font-weight: 850;
}

.rd-history-list {
    display: grid;
    gap: 10px;
}

.rd-history-item {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 14px;
    align-items: center;
    padding: 14px;
    border: 1px solid var(--rd-line);
    border-radius: 18px;
    background: color-mix(in srgb, var(--rd-accent) 3%, #FFFDF8);
    transition: transform .18s ease, border-color .18s ease, background .18s ease;
}

.rd-history-item:hover {
    transform: translateY(-1px);
    border-color: var(--rd-line-strong);
    background: var(--rd-panel);
}

.rd-history-name {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.rd-history-name strong {
    color: var(--rd-brand);
    font-size: .95rem;
    font-weight: 950;
}

.rd-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    min-height: 24px;
    padding: 5px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rd-accent) 12%, #FFFDF8);
    color: var(--rd-accent-deep);
    font-size: .68rem;
    font-weight: 900;
}

.rd-history-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-top: 9px;
}

.rd-history-meta span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 27px;
    padding: 6px 8px;
    border-radius: 999px;
    background: color-mix(in srgb, var(--rd-brand) 5%, #FFFDF8);
    color: var(--rd-muted);
    font-size: .72rem;
    font-weight: 800;
}

.rd-side-card {
    padding: 16px;
}

.rd-rate-card {
    color: #FFFDF8;
    border-color: color-mix(in srgb, var(--rd-brand) 72%, #000000);
    background:
        radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--rd-accent) 24%, transparent), transparent 13rem),
        linear-gradient(145deg, var(--rd-brand), var(--rd-brand-dark));
}

.rd-rate-card h3,
.rd-rate-card p {
    color: #FFFDF8;
}

.rd-rate-card p {
    opacity: .75;
}

.rd-rate-value {
    display: block;
    margin-top: 13px;
    font-size: 2rem;
    font-weight: 950;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}

.rd-rate-value small {
    font-size: .82rem;
    opacity: .78;
}

.rd-rate-old {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
    padding: 7px 9px;
    border-radius: 999px;
    background: rgba(255,253,248,.12);
    color: rgba(255,253,248,.78);
    font-size: .74rem;
    font-weight: 850;
}

.rd-side-list {
    display: grid;
    gap: 10px;
    margin-top: 13px;
}

.rd-side-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-top: 1px solid var(--rd-line);
}

.rd-side-row:first-child {
    border-top: 0;
    padding-top: 0;
}

.rd-side-row span {
    color: var(--rd-muted);
    font-size: .76rem;
    font-weight: 850;
}

.rd-side-row strong {
    color: var(--rd-brand);
    font-size: .82rem;
    font-weight: 950;
    text-align: right;
}

.rd-modal,
.rd-lightbox {
    position: fixed;
    inset: 0;
    z-index: 80;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 18px;
    background: rgba(15, 23, 42, .72);
    backdrop-filter: blur(10px);
}

.rd-modal.hidden,
.rd-lightbox.hidden {
    display: none !important;
}

.rd-modal-card {
    width: min(100%, 480px);
    max-height: calc(100vh - 36px);
    overflow: auto;
    border: 1px solid var(--rd-line);
    border-radius: 24px;
    background: var(--rd-panel);
    box-shadow: 0 34px 90px -40px rgba(15, 23, 42, .92);
    transform: scale(.96);
    opacity: 0;
    transition: transform .24s ease, opacity .24s ease;
}

.rd-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 17px 18px;
    border-bottom: 1px solid var(--rd-line);
    background: linear-gradient(90deg, var(--rd-panel-warm), var(--rd-panel));
}

.rd-modal-head h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: var(--rd-brand);
    font-size: 1rem;
    font-weight: 950;
}

.rd-modal-close {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border: 1px solid var(--rd-line);
    border-radius: 13px;
    background: var(--rd-panel);
    color: var(--rd-brand);
}

.rd-modal-form {
    display: grid;
    gap: 14px;
    padding: 18px;
}

.rd-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.rd-field {
    min-width: 0;
}

.rd-field-full {
    grid-column: 1 / -1;
}

.rd-label {
    display: block;
    margin-bottom: 7px;
    color: var(--rd-brand);
    font-size: .76rem;
    font-weight: 900;
}

.rd-control {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--rd-line);
    border-radius: 14px;
    background: color-mix(in srgb, var(--rd-accent) 2%, #FFFEFB);
    color: var(--rd-text);
    padding: 10px 12px;
    font-size: .88rem;
    font-weight: 750;
    transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.rd-control:focus {
    border-color: color-mix(in srgb, var(--rd-accent) 72%, var(--rd-brand));
    background: #FFFDF8;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--rd-accent) 17%, transparent);
}

.rd-modal-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    padding-top: 2px;
}

.rd-note {
    padding: 12px;
    border: 1px solid color-mix(in srgb, var(--rd-info) 22%, var(--rd-line));
    border-radius: 16px;
    background: color-mix(in srgb, var(--rd-info) 7%, var(--rd-panel));
    color: var(--rd-brand);
    font-size: .78rem;
    font-weight: 740;
    line-height: 1.45;
}

.rd-lightbox {
    z-index: 90;
    background: rgba(4, 8, 15, .94);
}

.rd-lightbox-frame {
    position: relative;
    width: min(1120px, 100%);
    max-height: 100%;
    display: grid;
    place-items: center;
}

.rd-lightbox-frame img#lightbox-img {
    max-width: 100%;
    max-height: min(78vh, 780px);
    object-fit: contain;
    border-radius: 18px;
    box-shadow: 0 28px 80px -30px rgba(0,0,0,.9);
}

.rd-lightbox-btn,
.rd-lightbox-close {
    position: absolute;
    display: grid;
    place-items: center;
    border: 1px solid rgba(255,253,248,.16);
    background: rgba(255,253,248,.10);
    color: #FFFDF8;
    backdrop-filter: blur(10px);
    transition: transform .18s ease, background .18s ease;
}

.rd-lightbox-btn {
    width: 48px;
    height: 48px;
    border-radius: 999px;
    top: 50%;
    transform: translateY(-50%);
}

.rd-lightbox-btn:hover {
    background: rgba(255,253,248,.18);
}

.rd-lightbox-prev {
    left: 14px;
}

.rd-lightbox-next {
    right: 14px;
}

.rd-lightbox-close {
    width: 44px;
    height: 44px;
    border-radius: 15px;
    top: 14px;
    right: 14px;
}

.rd-lightbox-count {
    position: absolute;
    left: 50%;
    bottom: 14px;
    transform: translateX(-50%);
    padding: 8px 12px;
    border-radius: 999px;
    background: rgba(255,253,248,.12);
    color: #FFFDF8;
    font-size: .78rem;
    font-weight: 950;
    backdrop-filter: blur(10px);
}

.rd-lightbox-thumbs {
    position: absolute;
    left: 50%;
    bottom: 58px;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    max-width: min(92vw, 720px);
    overflow-x: auto;
    padding: 4px;
}

.rd-lightbox-thumbs img {
    width: 66px;
    height: 58px;
    object-fit: cover;
    border: 2px solid transparent;
    border-radius: 12px;
    cursor: pointer;
    opacity: .62;
    transition: opacity .18s ease, transform .18s ease, border-color .18s ease;
}

.rd-lightbox-thumbs img.is-active,
.rd-lightbox-thumbs img:hover {
    opacity: 1;
    transform: translateY(-2px);
    border-color: #FFFDF8;
}

/* Paleta viva: el branding del hotel queda como firma, no como color dominante. */
.vista-habitacion.room-detail-page {
    --rd-brand-soft: color-mix(in srgb, var(--rd-brand) 7%, #F7FAFC);
    --rd-accent-soft: color-mix(in srgb, var(--rd-accent) 12%, #FFF8EA);
    --rd-sage: #5E7F69;
    --rd-sage-soft: #EDF5EE;
    --rd-sky: #477CA8;
    --rd-sky-soft: #EAF3FA;
    --rd-clay: #B86A54;
    --rd-clay-soft: #FFF0EA;
    --rd-sun: #C18A28;
    --rd-sun-soft: #FFF5D9;
    --rd-ink: #1C2635;
    --rd-bg: #F2F5F2;
    --rd-bg-soft: #FBFAF5;
    --rd-panel: rgba(255, 255, 252, .9);
    --rd-panel-warm: #FFF8EA;
    --rd-line: rgba(45, 62, 80, .12);
    --rd-line-strong: rgba(154, 119, 59, .24);
    --rd-muted: #687586;
    --rd-text: var(--rd-ink);
    background:
        radial-gradient(circle at 5% 8%, color-mix(in srgb, var(--rd-sky) 18%, transparent), transparent 26rem),
        radial-gradient(circle at 92% 4%, color-mix(in srgb, var(--rd-accent) 16%, transparent), transparent 24rem),
        radial-gradient(circle at 78% 78%, color-mix(in srgb, var(--rd-sage) 15%, transparent), transparent 28rem),
        linear-gradient(180deg, #FBFAF5 0%, #F4F6F1 42%, #EEF5F6 100%);
}

.room-detail-page :where(.rd-title, .rd-panel h2, .rd-side-card h3, .rd-stat strong, .rd-info-tile strong, .rd-side-row strong, .rd-history-name strong, .rd-focus-card h3, .rd-date-box strong, .rd-empty strong, .rd-modal-head h3, .rd-label) {
    color: var(--rd-ink);
}

.rd-breadcrumb a {
    color: color-mix(in srgb, var(--rd-brand) 62%, var(--rd-ink));
}

.rd-breadcrumb a:hover {
    color: var(--rd-clay);
}

.rd-hero {
    position: relative;
    border-color: rgba(150, 121, 75, .2);
    background:
        radial-gradient(circle at 9% 12%, color-mix(in srgb, var(--rd-sky) 15%, transparent), transparent 18rem),
        radial-gradient(circle at 89% 8%, color-mix(in srgb, var(--rd-sun) 17%, transparent), transparent 17rem),
        linear-gradient(135deg, rgba(255,255,252,.96), rgba(244,249,247,.92));
    box-shadow: 0 26px 74px -56px rgba(28, 38, 53, .62);
}

.rd-hero::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 6px;
    background: linear-gradient(180deg, var(--rd-sky), var(--rd-sage) 42%, var(--rd-accent) 72%, var(--rd-clay));
    z-index: 1;
}

.rd-hero-copy::after {
    background: linear-gradient(90deg, transparent, var(--rd-sky), var(--rd-sage), var(--rd-accent), var(--rd-clay));
    opacity: .58;
}

.rd-kicker {
    color: var(--rd-clay);
}

.rd-subtitle,
.rd-panel p,
.rd-side-card p,
.rd-alert span,
.rd-history-meta span {
    color: var(--rd-muted);
}

.rd-chip {
    color: var(--rd-ink);
    background: var(--rd-brand-soft);
}

.rd-hero-tags .rd-chip:nth-child(3) {
    border-color: color-mix(in srgb, var(--rd-sky) 25%, transparent);
    background: var(--rd-sky-soft);
    color: color-mix(in srgb, var(--rd-sky) 78%, var(--rd-ink));
}

.rd-hero-tags .rd-chip:nth-child(4) {
    border-color: color-mix(in srgb, var(--rd-sage) 26%, transparent);
    background: var(--rd-sage-soft);
    color: color-mix(in srgb, var(--rd-sage) 80%, var(--rd-ink));
}

.rd-hero-tags .rd-chip:nth-child(5),
.rd-hero-tags .rd-chip:nth-child(6) {
    border-color: color-mix(in srgb, var(--rd-sun) 26%, transparent);
    background: var(--rd-sun-soft);
    color: color-mix(in srgb, var(--rd-sun) 78%, var(--rd-ink));
}

.rd-hero-media {
    background:
        radial-gradient(circle at 32% 15%, color-mix(in srgb, var(--rd-sky) 22%, transparent), transparent 18rem),
        linear-gradient(150deg, #223044, #31484B 52%, #5E4A34);
}

.rd-floating-price,
.rd-photo-count,
.rd-expand-btn {
    background: rgba(28, 38, 53, .72);
    box-shadow: 0 18px 44px -30px rgba(28, 38, 53, .85);
}

.rd-alert {
    border-color: color-mix(in srgb, var(--rd-sun) 34%, transparent);
    background: linear-gradient(135deg, var(--rd-sun-soft), rgba(255,255,252,.86));
}

.rd-alert-icon {
    background: linear-gradient(145deg, var(--rd-sun), var(--rd-clay));
}

.rd-alert strong {
    color: var(--rd-ink);
}

.rd-panel,
.rd-side-card,
.rd-focus-card {
    background: var(--rd-panel);
    border-color: var(--rd-line);
    box-shadow: 0 1px 2px rgba(28, 38, 53, .04), 0 20px 44px -36px rgba(28, 38, 53, .42);
}

.rd-panel-head,
.rd-focus-card header,
.rd-modal-head {
    background: linear-gradient(90deg, rgba(255,248,234,.86), rgba(246,250,252,.84));
}

.rd-icon-box {
    border-color: color-mix(in srgb, var(--rd-sky) 21%, transparent);
    background: linear-gradient(145deg, var(--rd-sky-soft), rgba(255,255,252,.94));
    color: color-mix(in srgb, var(--rd-sky) 78%, var(--rd-ink));
}

.rd-main > .rd-panel:nth-child(3n + 1) .rd-icon-box {
    border-color: color-mix(in srgb, var(--rd-sage) 22%, transparent);
    background: linear-gradient(145deg, var(--rd-sage-soft), rgba(255,255,252,.94));
    color: color-mix(in srgb, var(--rd-sage) 78%, var(--rd-ink));
}

.rd-main > .rd-panel:nth-child(3n + 2) .rd-icon-box {
    border-color: color-mix(in srgb, var(--rd-sun) 24%, transparent);
    background: linear-gradient(145deg, var(--rd-sun-soft), rgba(255,255,252,.94));
    color: color-mix(in srgb, var(--rd-sun) 78%, var(--rd-ink));
}

.rd-stat {
    background: rgba(255,255,252,.72);
}

.rd-stat:nth-child(1) {
    border-color: color-mix(in srgb, var(--status-color, var(--rd-sage)) 22%, transparent);
    background: color-mix(in srgb, var(--status-soft, var(--rd-sage-soft)) 58%, rgba(255,255,252,.9));
}

.rd-stat:nth-child(2) {
    border-color: color-mix(in srgb, var(--rd-sky) 18%, transparent);
    background: var(--rd-sky-soft);
}

.rd-stat:nth-child(3) {
    border-color: color-mix(in srgb, var(--rd-sage) 18%, transparent);
    background: var(--rd-sage-soft);
}

.rd-stat:nth-child(4) {
    border-color: color-mix(in srgb, var(--rd-sun) 20%, transparent);
    background: var(--rd-sun-soft);
}

.rd-action {
    color: var(--rd-ink);
    background: rgba(255,255,252,.82);
}

.rd-action:hover {
    border-color: color-mix(in srgb, var(--rd-sky) 26%, transparent);
    background: linear-gradient(135deg, rgba(234,243,250,.92), rgba(255,255,252,.96));
}

.rd-btn-primary {
    border-color: color-mix(in srgb, var(--rd-brand) 42%, transparent);
    background: linear-gradient(145deg, color-mix(in srgb, var(--rd-brand) 82%, #263247), #263247);
    box-shadow: 0 18px 34px -26px color-mix(in srgb, var(--rd-brand) 52%, transparent);
}

.rd-guest-card {
    border-color: color-mix(in srgb, var(--rd-clay) 20%, var(--rd-line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--rd-clay-soft) 58%, rgba(255,255,252,.92)), rgba(255,255,252,.9));
}

.rd-maint-card {
    border-color: color-mix(in srgb, var(--rd-sun) 25%, var(--rd-line));
    background: linear-gradient(135deg, color-mix(in srgb, var(--rd-sun-soft) 62%, rgba(255,255,252,.92)), rgba(255,255,252,.9));
}

.rd-info-tile,
.rd-scheduled-item,
.rd-date-box {
    background: rgba(255,255,252,.76);
}

.rd-gallery-main {
    background: linear-gradient(145deg, var(--rd-sky-soft), var(--rd-sage-soft));
}

.rd-thumb-strip img:hover,
.rd-thumb-strip img.thumb-active {
    border-color: var(--rd-clay);
}

.rd-empty,
.rd-empty-action {
    background: linear-gradient(135deg, rgba(234,243,250,.58), rgba(255,248,234,.55));
}

.rd-empty i {
    color: var(--rd-sky);
}

.rd-focus-card:nth-child(odd) {
    border-color: color-mix(in srgb, var(--rd-sky) 22%, var(--rd-line));
}

.rd-focus-card:nth-child(even) {
    border-color: color-mix(in srgb, var(--rd-sage) 22%, var(--rd-line));
}

.rd-mini-tags span,
.rd-history-meta span {
    background: rgba(255,255,252,.74);
}

.rd-mini-tags span:nth-child(3n + 1),
.rd-history-meta span:nth-child(3n + 1) {
    color: color-mix(in srgb, var(--rd-sky) 72%, var(--rd-ink));
    background: var(--rd-sky-soft);
}

.rd-mini-tags span:nth-child(3n + 2),
.rd-history-meta span:nth-child(3n + 2) {
    color: color-mix(in srgb, var(--rd-sage) 72%, var(--rd-ink));
    background: var(--rd-sage-soft);
}

.rd-mini-tags span:nth-child(3n + 3),
.rd-history-meta span:nth-child(3n + 3) {
    color: color-mix(in srgb, var(--rd-sun) 74%, var(--rd-ink));
    background: var(--rd-sun-soft);
}

.rd-history-item {
    background: rgba(255,255,252,.82);
}

.rd-badge {
    background: var(--rd-accent-soft);
    color: color-mix(in srgb, var(--rd-accent) 72%, var(--rd-ink));
}

.rd-rate-card {
    position: relative;
    overflow: hidden;
    color: var(--rd-ink);
    border-color: color-mix(in srgb, var(--rd-accent) 24%, var(--rd-line));
    background:
        radial-gradient(circle at 102% 2%, color-mix(in srgb, var(--rd-accent) 18%, transparent), transparent 12rem),
        radial-gradient(circle at 0% 100%, color-mix(in srgb, var(--rd-sage) 15%, transparent), transparent 12rem),
        linear-gradient(145deg, rgba(255,255,252,.96), rgba(246,250,252,.9));
}

.rd-rate-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, var(--rd-sky), var(--rd-sage), var(--rd-accent), var(--rd-clay));
}

.rd-rate-card h3,
.rd-rate-card p {
    color: var(--rd-ink);
}

.rd-rate-card p {
    opacity: .68;
}

.rd-rate-value {
    color: color-mix(in srgb, var(--rd-brand) 70%, var(--rd-ink));
}

.rd-rate-old {
    background: var(--rd-accent-soft);
    color: color-mix(in srgb, var(--rd-accent) 72%, var(--rd-ink));
}

.rd-control {
    background: rgba(255,255,252,.88);
}

.room-detail-page .rd-title,
.room-detail-page .rd-panel h2,
.room-detail-page .rd-side-card h3,
.room-detail-page .rd-stat strong,
.room-detail-page .rd-info-tile strong,
.room-detail-page .rd-side-row strong,
.room-detail-page .rd-history-name strong,
.room-detail-page .rd-focus-card h3,
.room-detail-page .rd-date-box strong,
.room-detail-page .rd-empty strong,
.room-detail-page .rd-modal-head h3,
.room-detail-page .rd-label {
    color: var(--rd-ink);
}

.room-detail-page .rd-hero-media {
    background:
        radial-gradient(circle at 28% 20%, color-mix(in srgb, var(--rd-sky) 24%, transparent), transparent 18rem),
        radial-gradient(circle at 78% 80%, color-mix(in srgb, var(--rd-sun) 18%, transparent), transparent 18rem),
        linear-gradient(145deg, #F8FBFA, #EAF3F2 58%, #FFF5DF);
}

.room-detail-page .rd-hero-media::after {
    background: linear-gradient(180deg, transparent 45%, rgba(28, 38, 53, .08));
}

.room-detail-page .rd-media-placeholder {
    color: var(--rd-ink);
    padding: 24px 24px 132px;
}

.room-detail-page .rd-media-placeholder i {
    width: 54px;
    height: 54px;
    display: grid;
    place-items: center;
    margin: 0 auto 14px;
    border-radius: 18px;
    background: rgba(255,255,252,.74);
    color: var(--rd-sky);
    box-shadow: 0 14px 34px -28px rgba(28, 38, 53, .7);
}

.room-detail-page .rd-media-placeholder strong {
    color: var(--rd-ink);
}

.room-detail-page .rd-media-placeholder span {
    color: var(--rd-muted);
    font-weight: 800;
}

.room-detail-page .rd-rate-value {
    color: color-mix(in srgb, var(--rd-clay) 74%, var(--rd-ink));
}

/* Secciones con lectura clara: superficies neutras, acentos por funcion. */
.room-detail-page .rd-main {
    gap: 20px;
}

.room-detail-page .rd-panel,
.room-detail-page .rd-side-card,
.room-detail-page .rd-focus-card {
    background: rgba(255, 255, 255, .94);
    border-color: rgba(28, 38, 53, .12);
    box-shadow:
        0 1px 0 rgba(255,255,255,.82) inset,
        0 20px 44px -38px rgba(28, 38, 53, .55);
}

.room-detail-page .rd-panel {
    position: relative;
}

.room-detail-page .rd-panel::before {
    content: "";
    position: absolute;
    inset: 0 auto 0 0;
    width: 5px;
    background: var(--rd-section-accent, var(--rd-sky));
    opacity: .76;
}

.room-detail-page .rd-section-overview {
    --rd-section-accent: var(--rd-sky);
}

.room-detail-page .rd-section-guest {
    --rd-section-accent: var(--rd-clay);
}

.room-detail-page .rd-section-gallery {
    --rd-section-accent: var(--rd-sage);
}

.room-detail-page .rd-section-maint {
    --rd-section-accent: var(--rd-sun);
}

.room-detail-page .rd-section-movement {
    --rd-section-accent: color-mix(in srgb, var(--rd-brand) 62%, var(--rd-sky));
}

.room-detail-page .rd-section-history {
    --rd-section-accent: var(--rd-clay);
}

.room-detail-page .rd-panel-head,
.room-detail-page .rd-focus-card header,
.room-detail-page .rd-modal-head {
    background: linear-gradient(90deg, rgba(255,255,255,.98), rgba(247,249,248,.94));
    border-bottom-color: rgba(28, 38, 53, .1);
}

.room-detail-page .rd-panel-title {
    align-items: center;
}

.room-detail-page .rd-panel-title::after {
    content: "";
    align-self: center;
    width: 42px;
    height: 2px;
    border-radius: 999px;
    background: var(--rd-section-accent, var(--rd-sky));
    opacity: .42;
}

.room-detail-page .rd-icon-box {
    border-color: color-mix(in srgb, var(--rd-section-accent, var(--rd-sky)) 22%, transparent);
    background: color-mix(in srgb, var(--rd-section-accent, var(--rd-sky)) 10%, #FFFFFF);
    color: color-mix(in srgb, var(--rd-section-accent, var(--rd-sky)) 76%, var(--rd-ink));
}

.room-detail-page .rd-guest-card,
.room-detail-page .rd-maint-card {
    background: rgba(255,255,255,.94);
}

.room-detail-page .rd-stat,
.room-detail-page .rd-stat:nth-child(1),
.room-detail-page .rd-stat:nth-child(2),
.room-detail-page .rd-stat:nth-child(3),
.room-detail-page .rd-stat:nth-child(4),
.room-detail-page .rd-info-tile,
.room-detail-page .rd-date-box,
.room-detail-page .rd-scheduled-item,
.room-detail-page .rd-history-item {
    background: #FFFFFF;
    border-color: rgba(28, 38, 53, .1);
}

.room-detail-page .rd-stat {
    border-left: 4px solid var(--rd-section-accent, var(--rd-sky));
}

.room-detail-page .rd-stat:nth-child(1) {
    border-left-color: var(--status-color, var(--rd-sage));
}

.room-detail-page .rd-stat:nth-child(2) {
    border-left-color: var(--rd-sky);
}

.room-detail-page .rd-stat:nth-child(3) {
    border-left-color: var(--rd-sage);
}

.room-detail-page .rd-stat:nth-child(4) {
    border-left-color: var(--rd-sun);
}

.room-detail-page .rd-gallery-main,
.room-detail-page .rd-empty,
.room-detail-page .rd-empty-action {
    background: linear-gradient(135deg, #FFFFFF, rgba(247,249,248,.92));
}

.room-detail-page .rd-focus-grid {
    align-items: stretch;
}

.room-detail-page .rd-focus-card {
    --focus-accent: var(--rd-sky);
    position: relative;
    overflow: hidden;
}

.room-detail-page .rd-focus-card:only-child {
    grid-column: 1 / -1;
}

.room-detail-page .rd-focus-card:only-child .rd-focus-title span {
    white-space: nowrap;
}

.room-detail-page .rd-next-card {
    --focus-accent: color-mix(in srgb, var(--rd-brand) 58%, var(--rd-sky));
}

.room-detail-page .rd-last-card {
    --focus-accent: var(--rd-sage);
}

.room-detail-page .rd-focus-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 4px;
    background: var(--focus-accent);
}

.room-detail-page .rd-focus-card header {
    min-height: 68px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 16px 13px;
}

.room-detail-page .rd-focus-title {
    display: grid;
    grid-template-columns: 36px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    min-width: 0;
    color: var(--rd-ink);
    line-height: 1.12;
}

.room-detail-page .rd-focus-title i {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    border-radius: 13px;
    background: color-mix(in srgb, var(--focus-accent) 12%, #FFFFFF);
    color: color-mix(in srgb, var(--focus-accent) 78%, var(--rd-ink));
}

.room-detail-page .rd-focus-title span {
    display: block;
    font-size: .96rem;
    font-weight: 950;
    line-height: 1.12;
    text-wrap: balance;
}

.room-detail-page .rd-focus-kind {
    flex: 0 0 auto;
    min-height: 28px;
    display: inline-flex;
    align-items: center;
    padding: 6px 9px;
    border: 1px solid color-mix(in srgb, var(--focus-accent) 24%, transparent);
    border-radius: 999px;
    background: #FFFFFF;
    color: color-mix(in srgb, var(--focus-accent) 76%, var(--rd-ink));
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.room-detail-page .rd-focus-body {
    background: #FFFFFF;
}

.room-detail-page .rd-mini-tags span,
.room-detail-page .rd-history-meta span {
    border: 1px solid rgba(28, 38, 53, .08);
    background: #FFFFFF;
}

.room-detail-page .rd-maint-modal {
    padding: clamp(14px, 4vw, 28px);
    background:
        radial-gradient(circle at 18% 12%, color-mix(in srgb, var(--rd-brand) 18%, transparent), transparent 22rem),
        radial-gradient(circle at 88% 78%, color-mix(in srgb, var(--rd-accent) 16%, transparent), transparent 24rem),
        color-mix(in srgb, var(--rd-brand-dark) 82%, rgba(8, 13, 24, .72));
    backdrop-filter: blur(16px) saturate(118%);
}

.room-detail-page .rd-maint-modal-card {
    width: min(100%, 590px);
    border: 1px solid color-mix(in srgb, var(--rd-brand) 16%, rgba(255,255,255,.72));
    border-radius: 26px;
    background:
        linear-gradient(180deg, rgba(255,255,255,.98), rgba(250,250,247,.96)),
        var(--rd-panel);
    box-shadow:
        0 30px 80px -44px rgba(7, 12, 22, .9),
        0 0 0 1px rgba(255,255,255,.62) inset;
    overflow: hidden;
}

.room-detail-page .rd-maint-modal-card::before {
    content: "";
    display: block;
    height: 5px;
    background: linear-gradient(90deg,
        color-mix(in srgb, var(--rd-brand) 78%, #111827),
        color-mix(in srgb, var(--rd-accent) 74%, var(--rd-brand)),
        color-mix(in srgb, var(--rd-brand) 46%, #FFFFFF));
}

.room-detail-page .rd-maint-modal .rd-modal-head {
    align-items: flex-start;
    padding: 20px 20px 17px;
    border-bottom: 1px solid rgba(28, 38, 53, .09);
    background:
        radial-gradient(circle at 0% 0%, color-mix(in srgb, var(--rd-brand) 9%, transparent), transparent 14rem),
        linear-gradient(135deg, #FFFFFF, color-mix(in srgb, var(--rd-accent) 5%, #F9FAFB));
}

.room-detail-page .rd-maint-title {
    min-width: 0;
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr);
    gap: 12px;
    align-items: center;
}

.room-detail-page .rd-maint-icon {
    width: 44px;
    height: 44px;
    display: grid;
    place-items: center;
    border: 1px solid color-mix(in srgb, var(--rd-brand) 18%, transparent);
    border-radius: 16px;
    background:
        linear-gradient(145deg, color-mix(in srgb, var(--rd-brand) 11%, #FFFFFF), #FFFFFF);
    color: color-mix(in srgb, var(--rd-brand) 82%, #111827);
    box-shadow: 0 14px 30px -24px color-mix(in srgb, var(--rd-brand) 70%, transparent);
}

.room-detail-page .rd-maint-kicker {
    display: inline-flex;
    align-items: center;
    width: fit-content;
    margin-bottom: 5px;
    padding: 4px 8px;
    border: 1px solid color-mix(in srgb, var(--rd-accent) 18%, transparent);
    border-radius: 999px;
    background: color-mix(in srgb, var(--rd-accent) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--rd-brand-dark) 74%, #334155);
    font-size: .66rem;
    font-weight: 950;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.room-detail-page .rd-maint-modal .rd-modal-head h3 {
    display: block;
    margin: 0;
    color: #182033;
    font-size: clamp(1.05rem, 2vw, 1.22rem);
    line-height: 1.1;
}

.room-detail-page .rd-maint-modal .rd-modal-head p {
    margin: 6px 0 0;
    max-width: 38rem;
    color: #647084;
    font-size: .82rem;
    font-weight: 720;
    line-height: 1.38;
}

.room-detail-page .rd-maint-modal .rd-modal-close {
    flex: 0 0 auto;
    border-color: rgba(28, 38, 53, .11);
    background: #FFFFFF;
    color: #334155;
    box-shadow: 0 10px 24px -20px rgba(15, 23, 42, .62);
}

.room-detail-page .rd-maint-modal .rd-modal-form {
    gap: 16px;
    padding: 18px 20px 20px;
    background:
        linear-gradient(180deg, rgba(255,255,255,.72), rgba(247,249,248,.62));
}

.room-detail-page .rd-maint-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 8px;
    padding: 10px;
    border: 1px solid rgba(28, 38, 53, .09);
    border-radius: 18px;
    background: #FFFFFF;
}

.room-detail-page .rd-maint-summary span {
    min-width: 0;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 10px;
    border-radius: 14px;
    background: color-mix(in srgb, var(--rd-brand) 5%, #F8FAFC);
    color: #263247;
    font-size: .76rem;
    font-weight: 900;
    line-height: 1.1;
}

.room-detail-page .rd-maint-summary i {
    color: color-mix(in srgb, var(--rd-brand) 78%, #111827);
}

.room-detail-page .rd-maint-field-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.room-detail-page .rd-maint-modal .rd-label {
    color: #273244;
    font-size: .72rem;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.room-detail-page .rd-maint-modal .rd-control {
    min-height: 48px;
    border-color: rgba(28, 38, 53, .12);
    border-radius: 16px;
    background: #FFFFFF;
    color: #182033;
    box-shadow: 0 1px 0 rgba(255,255,255,.72) inset;
}

.room-detail-page .rd-maint-modal .rd-control:focus {
    border-color: color-mix(in srgb, var(--rd-brand) 56%, #CBD5E1);
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--rd-brand) 13%, transparent);
}

.room-detail-page .rd-maint-note {
    display: grid;
    grid-template-columns: 34px minmax(0, 1fr);
    align-items: center;
    gap: 10px;
    border-color: color-mix(in srgb, var(--rd-accent) 22%, rgba(28,38,53,.12));
    background: color-mix(in srgb, var(--rd-accent) 8%, #FFFFFF);
    color: #364153;
}

.room-detail-page .rd-maint-note i {
    width: 34px;
    height: 34px;
    display: grid;
    place-items: center;
    border-radius: 12px;
    background: #FFFFFF;
    color: color-mix(in srgb, var(--rd-accent) 76%, #111827);
}

.room-detail-page .rd-maint-modal .rd-modal-actions {
    gap: 12px;
    padding-top: 4px;
}

.room-detail-page .rd-btn-maint-cancel,
.room-detail-page .rd-btn-maint-primary {
    min-height: 46px;
    border-radius: 16px;
}

.room-detail-page .rd-btn-maint-cancel {
    border-color: rgba(28, 38, 53, .12);
    background: #FFFFFF;
    color: #334155;
}

.room-detail-page .rd-btn-maint-primary {
    border-color: color-mix(in srgb, var(--rd-brand) 56%, #111827);
    background: linear-gradient(145deg,
        color-mix(in srgb, var(--rd-brand) 84%, #111827),
        color-mix(in srgb, var(--rd-brand) 58%, #111827));
    color: #FFFFFF;
    box-shadow: 0 18px 34px -24px color-mix(in srgb, var(--rd-brand) 74%, #111827);
}

.room-detail-page .rd-btn-maint-primary:hover,
.room-detail-page .rd-btn-maint-cancel:hover {
    transform: translateY(-1px);
}

@media (max-width: 780px) {
    .room-detail-page .rd-maint-modal {
        align-items: flex-end;
        padding: 10px;
    }

    .room-detail-page .rd-maint-modal-card {
        width: 100%;
        max-height: calc(100dvh - 20px);
        border-radius: 22px;
    }

    .room-detail-page .rd-maint-modal .rd-modal-head {
        padding: 17px 16px 14px;
    }

    .room-detail-page .rd-maint-title {
        grid-template-columns: 40px minmax(0, 1fr);
        gap: 10px;
    }

    .room-detail-page .rd-maint-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
    }

    .room-detail-page .rd-maint-modal .rd-modal-form {
        padding: 15px;
    }

    .room-detail-page .rd-maint-summary {
        grid-template-columns: 1fr;
    }

    .room-detail-page .rd-maint-field-grid {
        grid-template-columns: 1fr;
    }

    .room-detail-page .rd-btn-maint-primary {
        order: -1;
    }
}

/* Rediseño neutral para modales de mantenimiento.
   Usa una paleta operacional fija para conservar contraste con cualquier branding de hotel. */
.room-detail-page #modalMantenimiento.rd-maint-modal,
.room-detail-page #modalProgramarMantenimiento.rd-maint-modal {
    --rd-maint-ink: #1C2633;
    --rd-maint-muted: #667386;
    --rd-maint-line: #DDD5C8;
    --rd-maint-surface: #FFFDF8;
    --rd-maint-soft: #F7F1E8;
    --rd-maint-panel: #FFFFFF;
    --rd-maint-amber: #A96113;
    --rd-maint-blue: #315F76;
    --rd-maint-action: var(--rd-maint-amber);
    padding: clamp(14px, 4vw, 30px);
    background:
        radial-gradient(circle at 24% 12%, rgba(255, 244, 220, .18), transparent 24rem),
        radial-gradient(circle at 86% 82%, rgba(111, 139, 150, .20), transparent 28rem),
        rgba(20, 28, 38, .76);
    backdrop-filter: blur(14px) saturate(108%);
}

.room-detail-page #modalProgramarMantenimiento.rd-maint-modal {
    --rd-maint-action: var(--rd-maint-blue);
}

.room-detail-page #modalMantenimiento .rd-maint-modal-card,
.room-detail-page #modalProgramarMantenimiento .rd-maint-modal-card {
    width: min(100%, 660px);
    border: 1px solid rgba(255, 255, 255, .74);
    border-radius: 22px;
    background:
        linear-gradient(180deg, rgba(255, 255, 255, .96), rgba(255, 253, 248, .98)),
        var(--rd-maint-surface);
    box-shadow:
        0 30px 86px -48px rgba(9, 14, 24, .92),
        0 0 0 1px rgba(255, 255, 255, .68) inset;
}

.room-detail-page #modalMantenimiento .rd-maint-modal-card::before,
.room-detail-page #modalProgramarMantenimiento .rd-maint-modal-card::before {
    height: 6px;
    background:
        linear-gradient(90deg,
            var(--rd-maint-action),
            color-mix(in srgb, var(--rd-maint-action) 64%, #FFFFFF),
            rgba(255, 255, 255, .12));
}

.room-detail-page #modalMantenimiento .rd-modal-head,
.room-detail-page #modalProgramarMantenimiento .rd-modal-head {
    padding: 22px 22px 18px;
    border-bottom: 1px solid var(--rd-maint-line);
    background:
        radial-gradient(260px 150px at 0% 0%, color-mix(in srgb, var(--rd-maint-action) 12%, transparent), transparent 78%),
        linear-gradient(180deg, #FFFFFF, var(--rd-maint-soft));
}

.room-detail-page #modalMantenimiento .rd-maint-title,
.room-detail-page #modalProgramarMantenimiento .rd-maint-title {
    grid-template-columns: 50px minmax(0, 1fr);
    gap: 14px;
}

.room-detail-page #modalMantenimiento .rd-maint-icon,
.room-detail-page #modalProgramarMantenimiento .rd-maint-icon {
    width: 50px;
    height: 50px;
    border: 1px solid color-mix(in srgb, var(--rd-maint-action) 26%, #FFFFFF);
    border-radius: 17px;
    background:
        linear-gradient(145deg, #FFFFFF, color-mix(in srgb, var(--rd-maint-action) 10%, var(--rd-maint-soft)));
    color: var(--rd-maint-action);
    box-shadow: 0 16px 30px -24px color-mix(in srgb, var(--rd-maint-action) 72%, transparent);
}

.room-detail-page #modalMantenimiento .rd-maint-kicker,
.room-detail-page #modalProgramarMantenimiento .rd-maint-kicker {
    margin-bottom: 7px;
    padding: 4px 9px;
    border: 1px solid color-mix(in srgb, var(--rd-maint-action) 18%, var(--rd-maint-line));
    border-radius: 8px;
    background: color-mix(in srgb, var(--rd-maint-action) 8%, #FFFFFF);
    color: color-mix(in srgb, var(--rd-maint-action) 78%, var(--rd-maint-ink));
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .08em;
}

.room-detail-page #modalMantenimiento .rd-modal-head h3,
.room-detail-page #modalProgramarMantenimiento .rd-modal-head h3 {
    color: var(--rd-maint-ink);
    font-size: clamp(1.28rem, 2.2vw, 1.48rem);
    font-weight: 950;
    letter-spacing: 0;
}

.room-detail-page #modalMantenimiento .rd-modal-head p,
.room-detail-page #modalProgramarMantenimiento .rd-modal-head p {
    max-width: 42rem;
    margin-top: 7px;
    color: var(--rd-maint-muted);
    font-size: .9rem;
    font-weight: 720;
    line-height: 1.45;
}

.room-detail-page #modalMantenimiento .rd-modal-close,
.room-detail-page #modalProgramarMantenimiento .rd-modal-close {
    border-color: var(--rd-maint-line);
    background: rgba(255, 255, 255, .82);
    color: var(--rd-maint-ink);
}

.room-detail-page #modalMantenimiento .rd-modal-close:hover,
.room-detail-page #modalProgramarMantenimiento .rd-modal-close:hover {
    border-color: color-mix(in srgb, var(--rd-maint-action) 36%, var(--rd-maint-line));
    background: #FFFFFF;
}

.room-detail-page #modalMantenimiento .rd-modal-form,
.room-detail-page #modalProgramarMantenimiento .rd-modal-form {
    gap: 18px;
    padding: 20px 22px 22px;
    background: var(--rd-maint-surface);
}

.room-detail-page #modalMantenimiento .rd-maint-summary,
.room-detail-page #modalProgramarMantenimiento .rd-maint-summary {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
}

.room-detail-page #modalMantenimiento .rd-maint-summary span,
.room-detail-page #modalProgramarMantenimiento .rd-maint-summary span {
    min-height: 48px;
    gap: 9px;
    padding: 10px 11px;
    border: 1px solid var(--rd-maint-line);
    border-radius: 15px;
    background: #FFFFFF;
    color: var(--rd-maint-ink);
    font-size: .8rem;
    font-weight: 920;
    box-shadow: 0 10px 22px -20px rgba(15, 23, 42, .38);
}

.room-detail-page #modalMantenimiento .rd-maint-summary i,
.room-detail-page #modalProgramarMantenimiento .rd-maint-summary i {
    width: 28px;
    height: 28px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 10px;
    background: color-mix(in srgb, var(--rd-maint-action) 10%, var(--rd-maint-soft));
    color: var(--rd-maint-action);
}

.room-detail-page #modalMantenimiento .rd-label,
.room-detail-page #modalProgramarMantenimiento .rd-label {
    color: var(--rd-maint-ink);
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .06em;
}

.room-detail-page #modalMantenimiento .rd-control,
.room-detail-page #modalProgramarMantenimiento .rd-control {
    min-height: 50px;
    border: 1px solid var(--rd-maint-line);
    border-radius: 15px;
    background: #FFFFFF;
    color: var(--rd-maint-ink);
    font-size: .92rem;
    font-weight: 780;
    box-shadow: 0 1px 0 rgba(255, 255, 255, .88) inset;
}

.room-detail-page #modalMantenimiento .rd-control::placeholder,
.room-detail-page #modalProgramarMantenimiento .rd-control::placeholder {
    color: #8A94A3;
}

.room-detail-page #modalMantenimiento .rd-control:focus,
.room-detail-page #modalProgramarMantenimiento .rd-control:focus {
    border-color: color-mix(in srgb, var(--rd-maint-action) 62%, var(--rd-maint-line));
    background: #FFFFFF;
    box-shadow: 0 0 0 4px color-mix(in srgb, var(--rd-maint-action) 15%, transparent);
}

.room-detail-page #modalProgramarMantenimiento .rd-maint-note {
    align-items: flex-start;
    gap: 12px;
    padding: 13px;
    border: 1px solid color-mix(in srgb, var(--rd-maint-blue) 22%, var(--rd-maint-line));
    border-radius: 16px;
    background:
        linear-gradient(135deg, rgba(49, 95, 118, .09), rgba(255, 255, 255, .92));
    color: #334155;
    font-size: .84rem;
    font-weight: 760;
    line-height: 1.42;
}

.room-detail-page #modalProgramarMantenimiento .rd-maint-note i {
    background: #FFFFFF;
    color: var(--rd-maint-blue);
}

.room-detail-page #modalMantenimiento .rd-modal-actions,
.room-detail-page #modalProgramarMantenimiento .rd-modal-actions {
    gap: 12px;
    padding-top: 2px;
}

.room-detail-page #modalMantenimiento .rd-btn-maint-cancel,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-cancel,
.room-detail-page #modalMantenimiento .rd-btn-maint-primary,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-primary {
    min-height: 48px;
    border-radius: 15px;
    font-weight: 950;
}

.room-detail-page #modalMantenimiento .rd-btn-maint-cancel,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-cancel {
    border: 1px solid var(--rd-maint-line);
    background: #FFFFFF;
    color: var(--rd-maint-ink);
}

.room-detail-page #modalMantenimiento .rd-btn-maint-primary,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-primary {
    border: 1px solid color-mix(in srgb, var(--rd-maint-action) 72%, #111827);
    background:
        linear-gradient(145deg,
            color-mix(in srgb, var(--rd-maint-action) 94%, #17212D),
            color-mix(in srgb, var(--rd-maint-action) 76%, #17212D));
    color: #FFFFFF;
    box-shadow: 0 16px 30px -22px color-mix(in srgb, var(--rd-maint-action) 72%, #111827);
}

.room-detail-page #modalMantenimiento .rd-btn-maint-primary:hover,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-primary:hover,
.room-detail-page #modalMantenimiento .rd-btn-maint-cancel:hover,
.room-detail-page #modalProgramarMantenimiento .rd-btn-maint-cancel:hover {
    transform: translateY(-1px);
}

@media (max-width: 780px) {
    .room-detail-page #modalMantenimiento.rd-maint-modal,
    .room-detail-page #modalProgramarMantenimiento.rd-maint-modal {
        padding: 10px;
    }

    .room-detail-page #modalMantenimiento .rd-maint-modal-card,
    .room-detail-page #modalProgramarMantenimiento .rd-maint-modal-card {
        width: 100%;
        border-radius: 20px 20px 18px 18px;
    }

    .room-detail-page #modalMantenimiento .rd-modal-head,
    .room-detail-page #modalProgramarMantenimiento .rd-modal-head {
        padding: 18px 16px 15px;
    }

    .room-detail-page #modalMantenimiento .rd-modal-form,
    .room-detail-page #modalProgramarMantenimiento .rd-modal-form {
        padding: 16px;
    }

    .room-detail-page #modalMantenimiento .rd-maint-title,
    .room-detail-page #modalProgramarMantenimiento .rd-maint-title {
        grid-template-columns: 44px minmax(0, 1fr);
    }

    .room-detail-page #modalMantenimiento .rd-maint-icon,
    .room-detail-page #modalProgramarMantenimiento .rd-maint-icon {
        width: 44px;
        height: 44px;
    }

    .room-detail-page #modalMantenimiento .rd-maint-summary,
    .room-detail-page #modalProgramarMantenimiento .rd-maint-summary,
    .room-detail-page #modalMantenimiento .rd-maint-field-grid,
    .room-detail-page #modalProgramarMantenimiento .rd-maint-field-grid {
        grid-template-columns: 1fr;
    }
}

/* Correccion: estos modales viven fuera de .room-detail-page, asi que se apuntan por ID. */
#modalMantenimiento.rd-maint-modal,
#modalProgramarMantenimiento.rd-maint-modal {
    --maint-ink: #1C2633;
    --maint-muted: #607083;
    --maint-line: #DCD4C8;
    --maint-paper: #FFFDF8;
    --maint-soft: #F7F1E8;
    --maint-amber: #A96113;
    --maint-blue: #315F76;
    --maint-action: var(--maint-amber);
    padding: clamp(14px, 4vw, 30px);
    background:
        radial-gradient(circle at 18% 10%, rgba(255, 243, 215, .08), transparent 24rem),
        radial-gradient(circle at 88% 84%, rgba(120, 147, 158, .10), transparent 28rem),
        rgba(19, 27, 38, .48) !important;
    backdrop-filter: blur(5px) saturate(102%);
}

#modalProgramarMantenimiento.rd-maint-modal {
    --maint-action: var(--maint-blue);
}

#modalMantenimiento .rd-maint-modal-card,
#modalProgramarMantenimiento .rd-maint-modal-card {
    width: min(100%, 660px) !important;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .76) !important;
    border-radius: 22px !important;
    background: var(--maint-paper) !important;
    box-shadow:
        0 34px 92px -50px rgba(4, 10, 20, .9),
        0 0 0 1px rgba(255, 255, 255, .72) inset !important;
}

#modalMantenimiento .rd-maint-modal-card::before,
#modalProgramarMantenimiento .rd-maint-modal-card::before {
    content: "";
    display: block;
    height: 6px;
    background: linear-gradient(90deg, var(--maint-action), rgba(255,255,255,.52), rgba(255,255,255,0));
}

#modalMantenimiento .rd-modal-head,
#modalProgramarMantenimiento .rd-modal-head {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 40px;
    gap: 14px;
    align-items: start;
    padding: 22px 22px 18px !important;
    border-bottom: 1px solid var(--maint-line) !important;
    background:
        radial-gradient(220px 130px at 0% 0%, rgba(169, 97, 19, .10), transparent 72%),
        linear-gradient(180deg, #FFFFFF, var(--maint-soft)) !important;
}

#modalProgramarMantenimiento .rd-modal-head {
    background:
        radial-gradient(220px 130px at 0% 0%, rgba(49, 95, 118, .12), transparent 72%),
        linear-gradient(180deg, #FFFFFF, var(--maint-soft)) !important;
}

#modalMantenimiento .rd-maint-title,
#modalProgramarMantenimiento .rd-maint-title {
    display: grid;
    grid-template-columns: 50px minmax(0, 1fr);
    gap: 14px;
    align-items: center;
}

#modalMantenimiento .rd-maint-icon,
#modalProgramarMantenimiento .rd-maint-icon {
    width: 50px;
    height: 50px;
    display: grid;
    place-items: center;
    border: 1px solid rgba(169, 97, 19, .22);
    border-radius: 17px;
    background: #FFFFFF;
    color: var(--maint-action);
    box-shadow: 0 16px 30px -24px rgba(15, 23, 42, .52);
}

#modalProgramarMantenimiento .rd-maint-icon {
    border-color: rgba(49, 95, 118, .22);
}

#modalMantenimiento .rd-maint-kicker,
#modalProgramarMantenimiento .rd-maint-kicker {
    display: inline-flex;
    width: fit-content;
    margin: 0 0 7px;
    padding: 4px 9px;
    border: 1px solid rgba(169, 97, 19, .22);
    border-radius: 8px;
    background: rgba(169, 97, 19, .08);
    color: #70400D;
    font-size: .68rem;
    font-weight: 950;
    letter-spacing: .08em;
    text-transform: uppercase;
}

#modalProgramarMantenimiento .rd-maint-kicker {
    border-color: rgba(49, 95, 118, .22);
    background: rgba(49, 95, 118, .09);
    color: #24485A;
}

#modalMantenimiento .rd-modal-head h3,
#modalProgramarMantenimiento .rd-modal-head h3 {
    display: block;
    margin: 0;
    color: var(--maint-ink) !important;
    font-size: clamp(1.24rem, 2.2vw, 1.48rem) !important;
    font-weight: 950 !important;
    line-height: 1.08;
}

#modalMantenimiento .rd-modal-head p,
#modalProgramarMantenimiento .rd-modal-head p {
    max-width: 42rem;
    margin: 7px 0 0;
    color: var(--maint-muted) !important;
    font-size: .92rem;
    font-weight: 720;
    line-height: 1.45;
}

#modalMantenimiento .rd-modal-close,
#modalProgramarMantenimiento .rd-modal-close {
    width: 40px;
    height: 40px;
    border: 1px solid var(--maint-line) !important;
    border-radius: 13px;
    background: #FFFFFF !important;
    color: var(--maint-ink) !important;
    box-shadow: 0 10px 24px -20px rgba(15, 23, 42, .55);
}

#modalMantenimiento .rd-modal-form,
#modalProgramarMantenimiento .rd-modal-form {
    gap: 18px;
    padding: 20px 22px 22px !important;
    background: var(--maint-paper) !important;
    color: var(--maint-ink) !important;
}

#modalMantenimiento .rd-maint-summary,
#modalProgramarMantenimiento .rd-maint-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    padding: 0 !important;
    border: 0 !important;
    background: transparent !important;
}

#modalMantenimiento .rd-maint-summary span,
#modalProgramarMantenimiento .rd-maint-summary span {
    min-height: 48px;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    padding: 10px 11px;
    border: 1px solid var(--maint-line);
    border-radius: 15px;
    background: #FFFFFF !important;
    color: var(--maint-ink) !important;
    font-size: .8rem;
    font-weight: 920;
    box-shadow: 0 10px 22px -20px rgba(15, 23, 42, .38);
}

#modalMantenimiento .rd-maint-summary i,
#modalProgramarMantenimiento .rd-maint-summary i,
#modalProgramarMantenimiento .rd-maint-note i {
    width: 28px;
    height: 28px;
    display: inline-grid;
    place-items: center;
    flex: 0 0 auto;
    border-radius: 10px;
    background: rgba(169, 97, 19, .09) !important;
    color: var(--maint-action) !important;
}

#modalProgramarMantenimiento .rd-maint-summary i,
#modalProgramarMantenimiento .rd-maint-note i {
    background: rgba(49, 95, 118, .10) !important;
}

#modalMantenimiento .rd-maint-note,
#modalProgramarMantenimiento .rd-maint-note {
    align-items: flex-start;
    gap: 12px;
    padding: 13px;
    border: 1px solid rgba(49, 95, 118, .22) !important;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(49, 95, 118, .08), #FFFFFF) !important;
    color: #334155 !important;
    font-size: .84rem;
    font-weight: 760;
    line-height: 1.42;
}

#modalMantenimiento .rd-label,
#modalProgramarMantenimiento .rd-label {
    margin-bottom: 8px;
    color: var(--maint-ink) !important;
    font-size: .72rem;
    font-weight: 950;
    letter-spacing: .06em;
    text-transform: uppercase;
}

#modalMantenimiento .rd-control,
#modalProgramarMantenimiento .rd-control {
    min-height: 50px;
    border: 1px solid var(--maint-line) !important;
    border-radius: 15px !important;
    background: #FFFFFF !important;
    color: var(--maint-ink) !important;
    font-size: .92rem;
    font-weight: 780;
    box-shadow: 0 1px 0 rgba(255, 255, 255, .88) inset !important;
}

#modalMantenimiento .rd-control::placeholder,
#modalProgramarMantenimiento .rd-control::placeholder {
    color: #8A94A3 !important;
}

#modalMantenimiento .rd-control:focus,
#modalProgramarMantenimiento .rd-control:focus {
    border-color: var(--maint-action) !important;
    box-shadow: 0 0 0 4px rgba(169, 97, 19, .15) !important;
}

#modalProgramarMantenimiento .rd-control:focus {
    box-shadow: 0 0 0 4px rgba(49, 95, 118, .15) !important;
}

#modalMantenimiento .rd-modal-actions,
#modalProgramarMantenimiento .rd-modal-actions {
    gap: 12px;
    padding-top: 2px;
}

#modalMantenimiento .rd-btn-maint-cancel,
#modalProgramarMantenimiento .rd-btn-maint-cancel,
#modalMantenimiento .rd-btn-maint-primary,
#modalProgramarMantenimiento .rd-btn-maint-primary {
    min-height: 48px;
    border-radius: 15px !important;
    font-weight: 950;
}

#modalMantenimiento .rd-btn-maint-cancel,
#modalProgramarMantenimiento .rd-btn-maint-cancel {
    border: 1px solid var(--maint-line) !important;
    background: #FFFFFF !important;
    color: var(--maint-ink) !important;
}

#modalMantenimiento .rd-btn-maint-primary,
#modalProgramarMantenimiento .rd-btn-maint-primary {
    border: 1px solid rgba(28, 38, 51, .16) !important;
    background: linear-gradient(145deg, var(--maint-action), #263342) !important;
    color: #FFFFFF !important;
    box-shadow: 0 16px 30px -22px rgba(15, 23, 42, .65);
}

@media (max-width: 780px) {
    #modalMantenimiento .rd-maint-modal-card,
    #modalProgramarMantenimiento .rd-maint-modal-card {
        width: 100% !important;
        border-radius: 20px 20px 18px 18px !important;
    }

    #modalMantenimiento .rd-modal-head,
    #modalProgramarMantenimiento .rd-modal-head {
        padding: 18px 16px 15px !important;
    }

    #modalMantenimiento .rd-modal-form,
    #modalProgramarMantenimiento .rd-modal-form {
        padding: 16px !important;
    }

    #modalMantenimiento .rd-maint-title,
    #modalProgramarMantenimiento .rd-maint-title {
        grid-template-columns: 44px minmax(0, 1fr);
    }

    #modalMantenimiento .rd-maint-icon,
    #modalProgramarMantenimiento .rd-maint-icon {
        width: 44px;
        height: 44px;
    }

    #modalMantenimiento .rd-maint-summary,
    #modalProgramarMantenimiento .rd-maint-summary,
    #modalMantenimiento .rd-maint-field-grid,
    #modalProgramarMantenimiento .rd-maint-field-grid {
        grid-template-columns: 1fr !important;
    }
}

@media (max-width: 1160px) {
    .rd-hero,
    .rd-layout {
        grid-template-columns: 1fr;
    }

    .rd-side {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .rd-rate-card,
    .rd-side-card:last-child {
        grid-column: 1 / -1;
    }
}

@media (max-width: 780px) {
    .rd-shell {
        width: min(100% - 22px, 1500px);
        padding: 18px 0 32px;
    }

    .rd-hero,
    .rd-panel,
    .rd-side-card {
        border-radius: 21px;
    }

    .rd-hero-copy,
    .rd-panel-body,
    .rd-panel-head {
        padding: 15px;
    }

    .rd-hero-media,
    .rd-hero-media img,
    .rd-media-placeholder {
        min-height: 250px;
    }

    .rd-title {
        font-size: clamp(2.8rem, 18vw, 5.2rem);
    }

    .rd-alert,
    .rd-history-item,
    .rd-scheduled-item {
        grid-template-columns: 1fr;
    }

    .rd-stat-grid,
    .rd-guest-grid,
    .rd-maint-grid,
    .rd-focus-grid,
    .rd-action-grid,
    .rd-side,
    .rd-form-grid,
    .rd-modal-actions,
    .rd-date-pair {
        grid-template-columns: 1fr;
    }

    .rd-floating-price {
        position: static;
        margin: 12px;
    }

    .rd-lightbox-btn {
        width: 42px;
        height: 42px;
    }

    .room-detail-page .rd-focus-card:only-child .rd-focus-title span {
        white-space: normal;
    }
}

@media (prefers-reduced-motion: reduce) {
    .room-detail-page *,
    .room-detail-page *::before,
    .room-detail-page *::after {
        animation: none !important;
        transition: none !important;
    }
}
</style>

<div class="vista-habitacion room-detail-page">
    <div class="rd-shell">
        <nav class="rd-breadcrumb" aria-label="Ruta de navegacion">
            <a href="<?= url('habitaciones') ?>">
                <i class="fas fa-bed"></i>
                Habitaciones
            </a>
            <i class="fas fa-chevron-right"></i>
            <span>Habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?></span>
        </nav>

        <header class="rd-hero">
            <section class="rd-hero-copy">
                <div class="rd-kicker">
                    <i class="fas fa-door-open"></i>
                    Detalles de habitaci&oacute;n
                </div>
                <h1 class="rd-title">
                    <span>Habitaci&oacute;n</span>
                    <?= room_detail_safe($habitacion_numero) ?>
                </h1>
                <p class="rd-subtitle">
                    Control operativo de estado, precio, hu&eacute;sped actual, mantenimiento, fotograf&iacute;as e historial reciente.
                </p>

                <div class="rd-hero-tags">
                    <span class="rd-status-pill" style="--status-color: <?= room_detail_safe($status_meta['color']) ?>; --status-soft: <?= room_detail_safe($status_meta['soft']) ?>;">
                        <i class="fas fa-<?= room_detail_safe($status_meta['icon'], 'circle') ?>"></i>
                        <?= room_detail_safe($status_meta['label']) ?>
                    </span>
                    <span class="rd-chip"><i class="fas fa-user-tie"></i>Propietario: <?= room_detail_safe($propietario_nombre) ?></span>
                    <span class="rd-chip"><i class="fas fa-layer-group"></i><?= room_detail_safe($tipo_label) ?></span>
                    <span class="rd-chip"><i class="fas fa-building"></i><?= room_detail_safe($piso_label) ?></span>
                    <?php if ($capacidad > 0): ?>
                        <span class="rd-chip"><i class="fas fa-user-group"></i><?= number_format($capacidad) ?> personas</span>
                    <?php endif; ?>
                    <?php if ($camas_total > 0): ?>
                        <span class="rd-chip"><i class="fas fa-bed"></i><?= number_format($camas_total) ?> camas</span>
                    <?php endif; ?>
                    <span class="rd-chip"><i class="fas fa-circle"></i><?= $activa ? 'Activa' : 'Inactiva' ?></span>
                </div>
            </section>

            <section class="rd-hero-media" aria-label="Fotografia principal">
                <?php if ($imagen_principal): ?>
                    <img src="<?= image_url($imagen_principal['url']) ?>"
                         alt="Habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?>"
                         onclick="abrirLightbox(this.src)">
                <?php else: ?>
                    <div class="rd-media-placeholder">
                        <div>
                            <i class="fas fa-image"></i>
                            <strong><?= room_detail_safe($habitacion_numero) ?></strong>
                            <span>Sin fotograf&iacute;a principal</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="rd-floating-price">
                    <span>Tarifa vigente</span>
                    <strong><?= format_money($precio_actual) ?></strong>
                    <small>por noche</small>
                    <?php if ($tiene_incremento): ?>
                        <small>Base: <?= format_money($precio_base_original) ?>, incremento: <?= format_money($incremento_total) ?></small>
                    <?php endif; ?>
                </div>
            </section>
        </header>

        <?php if ($reservacion_pendiente): ?>
            <section class="rd-alert">
                <div class="rd-alert-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <strong><?= room_detail_safe($reservacion_pendiente['nombre_completo'] ?? '') ?> - llegada pendiente</strong>
                    <span>
                        Hora estimada: <?= date('g:i A', strtotime($reservacion_pendiente['hora_llegada_estimada'])) ?>
                        <?php if (($reservacion_pendiente['total_habitaciones'] ?? 0) > 1): ?>
                            · Grupo de <?= (int)$reservacion_pendiente['total_habitaciones'] ?> habitaciones
                        <?php endif; ?>
                    </span>
                </div>
                <a href="<?= url('reservaciones/ver/' . $reservacion_pendiente['reservacion_id']) ?>" class="rd-btn rd-btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Check-in
                </a>
            </section>
        <?php endif; ?>

        <div class="rd-layout">
            <main class="rd-main">
                <section class="rd-panel rd-section-overview">
                    <div class="rd-panel-head">
                        <div class="rd-panel-title">
                            <span class="rd-icon-box"><i class="fas fa-gauge-high"></i></span>
                            <div>
                                <h2>Lectura operativa</h2>
                                <p>Resumen r&aacute;pido para recepci&oacute;n y mantenimiento.</p>
                            </div>
                        </div>
                    </div>
                    <div class="rd-panel-body">
                        <div class="rd-stat-grid">
                            <article class="rd-stat">
                                <span>Estado</span>
                                <strong><?= room_detail_safe($status_meta['label']) ?></strong>
                            </article>
                            <article class="rd-stat">
                                <span>Tipo</span>
                                <strong><?= room_detail_safe($tipo_label) ?></strong>
                            </article>
                            <article class="rd-stat">
                                <span>Piso</span>
                                <strong><?= room_detail_safe($piso_label) ?></strong>
                            </article>
                            <article class="rd-stat">
                                <span>Historial</span>
                                <strong><?= number_format($historial_count) ?> reservas</strong>
                            </article>
                            <article class="rd-stat">
                                <span>Camas</span>
                                <strong><?= $camas_total > 0 ? number_format($camas_total) : 'No definido' ?></strong>
                            </article>
                            <article class="rd-stat">
                                <span>Propietario</span>
                                <strong><?= room_detail_safe($propietario_nombre) ?></strong>
                            </article>
                        </div>
                    </div>
                </section>

                <?php if ($ocupacion_actual): ?>
                    <section class="rd-panel rd-guest-card rd-section-guest">
                        <div class="rd-panel-head">
                            <div class="rd-panel-title">
                                <span class="rd-icon-box"><i class="fas fa-user-check"></i></span>
                                <div>
                                    <h2>Hu&eacute;sped actual</h2>
                                    <p>Reservaci&oacute;n activa vinculada a esta habitaci&oacute;n.</p>
                                </div>
                            </div>
                            <?php if (($ocupacion_actual['fecha_salida'] ?? '') == date('Y-m-d')): ?>
                                <span class="rd-badge"><i class="fas fa-calendar-day"></i>Sale hoy</span>
                            <?php endif; ?>
                        </div>

                        <div class="rd-panel-body">
                            <div class="rd-guest-grid">
                                <article class="rd-info-tile">
                                    <span>Hu&eacute;sped</span>
                                    <strong><?= room_detail_safe($ocupacion_actual['nombre_completo'] ?? '') ?></strong>
                                </article>
                                <article class="rd-info-tile">
                                    <span>Salida</span>
                                    <strong><?= format_date($ocupacion_actual['fecha_salida'] ?? '') ?></strong>
                                </article>
                                <article class="rd-info-tile">
                                    <span>Tel&eacute;fono</span>
                                    <strong><?= room_detail_safe($ocupacion_actual['telefono'] ?? '', 'No registrado') ?></strong>
                                </article>
                                <article class="rd-info-tile">
                                    <span>Veh&iacute;culos</span>
                                    <?php
                                    if (class_exists('HuespedVehiculo') && isset($ocupacion_actual['huesped_id'])) {
                                        $vehiculoModel = new HuespedVehiculo();
                                        $vehiculos_ocupacion = $vehiculoModel->porHuespedHotel($ocupacion_actual['huesped_id']);
                                        if (!empty($vehiculos_ocupacion)): ?>
                                            <div class="rd-vehicle-list">
                                                <?php foreach ($vehiculos_ocupacion as $vehiculo): ?>
                                                    <span>
                                                        <i class="fas fa-car"></i>
                                                        <?= room_detail_safe(trim(($vehiculo['marca'] ?? '') . ' ' . ($vehiculo['modelo'] ?? '')), 'Vehiculo') ?>
                                                        (<?= room_detail_safe($vehiculo['placas'] ?? '', 'sin placas') ?>)
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <strong>Sin veh&iacute;culos registrados</strong>
                                        <?php endif;
                                    } elseif (isset($ocupacion_actual['huesped_id'])) { ?>
                                        <strong><?= room_detail_safe(get_resumen_vehiculos_huesped($ocupacion_actual['huesped_id']) ?? '', 'Sin vehiculos') ?></strong>
                                    <?php } else { ?>
                                        <strong>Sin veh&iacute;culos registrados</strong>
                                    <?php } ?>
                                </article>
                            </div>

                            <?php $reservacion_id = $ocupacion_actual['id'] ?? 0; ?>
                            <div style="margin-top: 12px;">
                                <a href="<?= url('reservaciones/ver/' . $reservacion_id) ?>" class="rd-btn rd-btn-info">
                                    <i class="fas fa-eye"></i>
                                    Ver reservaci&oacute;n completa
                                </a>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="rd-panel rd-section-gallery">
                    <div class="rd-panel-head">
                        <div class="rd-panel-title">
                            <span class="rd-icon-box"><i class="fas fa-images"></i></span>
                            <div>
                                <h2>Galer&iacute;a de la habitaci&oacute;n</h2>
                                <p>Fotograf&iacute;as disponibles para revisi&oacute;n visual.</p>
                            </div>
                        </div>
                        <?php if ($tiene_imagenes): ?>
                            <span class="rd-badge"><i class="fas fa-camera"></i><?= count($imagenes) ?> fotos</span>
                        <?php endif; ?>
                    </div>
                    <div class="rd-panel-body">
                        <?php if ($tiene_imagenes): ?>
                            <div class="rd-gallery-main">
                                <img id="imagen-principal"
                                     src="<?= image_url($imagen_principal['url']) ?>"
                                     alt="Habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?>"
                                     onclick="abrirLightbox(this.src)">

                                <?php if (count($imagenes) > 1): ?>
                                    <div class="rd-photo-count">
                                        <i class="fas fa-images"></i>
                                        <span id="contador"><?= count($imagenes) ?> fotos</span>
                                    </div>
                                <?php endif; ?>

                                <button type="button"
                                        onclick="abrirLightbox(document.getElementById('imagen-principal').src)"
                                        class="rd-expand-btn"
                                        aria-label="Abrir fotografia">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>

                            <?php if (count($imagenes) > 1): ?>
                                <div class="rd-thumb-strip" aria-label="Miniaturas de habitacion">
                                    <?php foreach ($imagenes as $index => $imagen): ?>
                                        <img src="<?= image_url($imagen['url']) ?>"
                                             alt="Foto <?= $index + 1 ?> de habitaci&oacute;n <?= room_detail_safe($habitacion_numero) ?>"
                                             class="room-thumb <?= $index === 0 ? 'thumb-active' : '' ?>"
                                             onclick="cambiarImagen(<?= htmlspecialchars(json_encode(image_url($imagen['url'])), ENT_QUOTES, 'UTF-8') ?>, <?= $index ?>)">
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="rd-empty">
                                <i class="fas fa-images"></i>
                                <strong>Sin im&aacute;genes disponibles</strong>
                                <span>Agrega fotograf&iacute;as para documentar esta habitaci&oacute;n.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($habitacion_estado == 'mantenimiento' && $mantenimiento_actual): ?>
                    <section class="rd-panel rd-maint-card rd-section-maint">
                        <div class="rd-panel-head">
                            <div class="rd-panel-title">
                                <span class="rd-icon-box"><i class="fas fa-tools"></i></span>
                                <div>
                                    <h2>Mantenimiento en progreso</h2>
                                    <p>Trabajo activo registrado para esta habitaci&oacute;n.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rd-panel-body">
                            <div class="rd-maint-grid">
                                <article class="rd-info-tile">
                                    <span>Tipo</span>
                                    <strong><?= room_detail_safe(ucfirst(str_replace('_', ' ', $mantenimiento_actual['tipo_mantenimiento'] ?? 'No especificado'))) ?></strong>
                                </article>
                                <article class="rd-info-tile">
                                    <span>Prioridad</span>
                                    <strong><?= room_detail_safe(ucfirst($mantenimiento_actual['prioridad'] ?? 'media')) ?></strong>
                                </article>
                                <article class="rd-info-tile" style="grid-column: 1 / -1;">
                                    <span>Motivo</span>
                                    <strong><?= room_detail_safe($mantenimiento_actual['motivo'] ?? '', 'No especificado') ?></strong>
                                </article>
                            </div>
                            <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/mantenimiento') ?>" style="margin-top: 12px;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="finalizar">
                                <button type="submit" class="rd-btn rd-btn-success" style="width: 100%;">
                                    <i class="fas fa-check-circle"></i>
                                    Finalizar mantenimiento
                                </button>
                            </form>
                        </div>
                    </section>
                <?php endif; ?>

                <?php
                $tareasContextuales = $tareas_contextuales;
                $tituloTareasContextuales = 'Tareas operativas';
                $subtituloTareasContextuales = 'Tareas de limpieza, mantenimiento o seguimiento vinculadas a esta habitacion.';
                include __DIR__ . '/../tareas/_contextual_list.php';
                ?>

                <?php if (!empty($mantenimientos_programados)): ?>
                    <section class="rd-panel rd-maint-card rd-section-maint">
                        <div class="rd-panel-head">
                            <div class="rd-panel-title">
                                <span class="rd-icon-box"><i class="fas fa-calendar-check"></i></span>
                                <div>
                                    <h2>Mantenimientos programados</h2>
                                    <p>Intervenciones futuras bloqueadas para esta habitaci&oacute;n.</p>
                                </div>
                            </div>
                            <span class="rd-badge"><?= number_format($mantenimientos_count) ?></span>
                        </div>
                        <div class="rd-panel-body">
                            <div class="rd-scheduled-list">
                                <?php foreach ($mantenimientos_programados as $mp): ?>
                                    <article class="rd-scheduled-item">
                                        <div>
                                            <strong>
                                                <i class="fas fa-wrench"></i>
                                                <?= room_detail_safe(ucfirst(str_replace('_', ' ', $mp['tipo_mantenimiento'] ?? 'Mantenimiento'))) ?>
                                            </strong>
                                            <span>
                                                <?= date('d/m/Y', strtotime($mp['fecha_programada'])) ?>
                                                <?php if (!empty($mp['fecha_programada_fin'])): ?>
                                                    al <?= date('d/m/Y', strtotime($mp['fecha_programada_fin'])) ?>
                                                <?php endif; ?>
                                                · Prioridad <?= room_detail_safe(ucfirst($mp['prioridad'] ?? 'media')) ?>
                                            </span>
                                            <?php if (!empty($mp['motivo'])): ?>
                                                <span><?= room_detail_safe($mp['motivo']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <form method="POST"
                                              action="<?= url('habitaciones/cancelar-mantenimiento-programado/' . $mp['id']) ?>"
                                              onsubmit="return confirm('¿Cancelar este mantenimiento programado?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="motivo_cancelacion" value="Cancelado manualmente">
                                            <button type="submit" class="rd-btn rd-btn-danger" title="Cancelar">
                                                <i class="fas fa-times"></i>
                                                Cancelar
                                            </button>
                                        </form>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ($proxima_destacada || $ultima_destacada): ?>
                    <section class="rd-panel rd-section-movement">
                        <div class="rd-panel-head">
                            <div class="rd-panel-title">
                                <span class="rd-icon-box"><i class="fas fa-calendar-days"></i></span>
                                <div>
                                    <h2>Movimiento destacado</h2>
                                    <p>Pr&oacute;xima llegada y &uacute;ltima ocupaci&oacute;n detectadas.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rd-panel-body">
                            <div class="rd-focus-grid">
                                <?php if ($proxima_destacada): ?>
                                    <?php
                                    $fecha_entrada_prox = new DateTime($proxima_destacada['fecha_entrada']);
                                    $fecha_salida_prox = new DateTime($proxima_destacada['fecha_salida']);
                                    $duracion_prox = $fecha_entrada_prox->diff($fecha_salida_prox)->days;
                                    $hoy_dt = new DateTime();
                                    $dias_hasta_entrada = $hoy_dt->diff($fecha_entrada_prox)->days;
                                    ?>
                                    <article class="rd-focus-card rd-next-card">
                                        <header>
                                            <strong class="rd-focus-title">
                                                <i class="fas fa-calendar-check"></i>
                                                <span>Pr&oacute;xima reservaci&oacute;n</span>
                                            </strong>
                                            <span class="rd-focus-kind">Llegada</span>
                                        </header>
                                        <div class="rd-focus-body">
                                            <h3><?= room_detail_safe($proxima_destacada['nombre_huesped'] ?? '') ?></h3>
                                            <div class="rd-date-pair">
                                                <div class="rd-date-box">
                                                    <span>Entrada</span>
                                                    <strong><?= $fecha_entrada_prox->format('d/m/Y') ?></strong>
                                                </div>
                                                <div class="rd-date-box">
                                                    <span>Salida</span>
                                                    <strong><?= $fecha_salida_prox->format('d/m/Y') ?></strong>
                                                </div>
                                            </div>
                                            <div class="rd-mini-tags">
                                                <span><i class="fas fa-clock"></i><?= $dias_hasta_entrada == 0 ? 'Llega hoy' : ($dias_hasta_entrada == 1 ? 'Llega manana' : 'En ' . $dias_hasta_entrada . ' dias') ?></span>
                                                <span><i class="fas fa-moon"></i><?= $duracion_prox ?> <?= $duracion_prox == 1 ? 'noche' : 'noches' ?></span>
                                                <?php if (($proxima_destacada['precio_total'] ?? 0) > 0): ?>
                                                    <span><i class="fas fa-dollar-sign"></i><?= format_money($proxima_destacada['precio_total']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top: 12px;">
                                                <a href="<?= url('/reservaciones/ver/' . $proxima_destacada['id']) ?>" class="rd-btn rd-btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    Ver
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endif; ?>

                                <?php if ($ultima_destacada): ?>
                                    <?php
                                    $fecha_entrada_ult = new DateTime($ultima_destacada['fecha_entrada']);
                                    $fecha_salida_ult = new DateTime($ultima_destacada['fecha_salida']);
                                    $duracion_ult = $fecha_entrada_ult->diff($fecha_salida_ult)->days;
                                    $hoy_dt = new DateTime();
                                    $dias_desde_salida = $fecha_salida_ult->diff($hoy_dt)->days;
                                    ?>
                                    <article class="rd-focus-card rd-last-card">
                                        <header>
                                            <strong class="rd-focus-title">
                                                <i class="fas fa-history"></i>
                                                <span>&Uacute;ltima ocupaci&oacute;n</span>
                                            </strong>
                                            <span class="rd-focus-kind">Historial</span>
                                        </header>
                                        <div class="rd-focus-body">
                                            <h3><?= room_detail_safe($ultima_destacada['nombre_huesped'] ?? '') ?></h3>
                                            <div class="rd-date-pair">
                                                <div class="rd-date-box">
                                                    <span>Entrada</span>
                                                    <strong><?= $fecha_entrada_ult->format('d/m/Y') ?></strong>
                                                </div>
                                                <div class="rd-date-box">
                                                    <span>Salida</span>
                                                    <strong><?= $fecha_salida_ult->format('d/m/Y') ?></strong>
                                                </div>
                                            </div>
                                            <div class="rd-mini-tags">
                                                <span><i class="fas fa-calendar-alt"></i><?= room_detail_relative_exit($dias_desde_salida) ?></span>
                                                <span><i class="fas fa-moon"></i><?= $duracion_ult ?> <?= $duracion_ult == 1 ? 'noche' : 'noches' ?></span>
                                                <?php if (($ultima_destacada['precio_total'] ?? 0) > 0): ?>
                                                    <span><i class="fas fa-dollar-sign"></i><?= format_money($ultima_destacada['precio_total']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="margin-top: 12px;">
                                                <a href="<?= url('/reservaciones/ver/' . $ultima_destacada['id']) ?>" class="rd-btn rd-btn-info">
                                                    <i class="fas fa-eye"></i>
                                                    Ver
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="rd-panel rd-section-history">
                    <div class="rd-panel-head">
                        <div class="rd-panel-title">
                            <span class="rd-icon-box"><i class="fas fa-timeline"></i></span>
                            <div>
                                <h2>Historial de reservaciones</h2>
                                <p>&Uacute;ltimos movimientos asociados a esta habitaci&oacute;n.</p>
                            </div>
                        </div>
                        <span class="rd-badge"><i class="fas fa-calendar-check"></i><?= min(10, $historial_count) ?> visibles</span>
                    </div>
                    <div class="rd-panel-body">
                        <?php if (!empty($historial_reciente)): ?>
                            <div class="rd-history-list">
                                <?php
                                $hoy_dt = new DateTime();
                                foreach (array_slice($historial_reciente, 0, 10) as $reservacion):
                                    $fecha_entrada = new DateTime($reservacion['fecha_entrada']);
                                    $fecha_salida = new DateTime($reservacion['fecha_salida']);
                                    $duracion = $fecha_entrada->diff($fecha_salida)->days;
                                    $dias_desde_salida = $fecha_salida->diff($hoy_dt)->days;
                                    $es_reciente = $dias_desde_salida <= 7;
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
                                    <article class="rd-history-item">
                                        <div>
                                            <div class="rd-history-name">
                                                <strong><?= room_detail_safe($reservacion['nombre_huesped'] ?? '') ?></strong>
                                                <?php if ($es_reciente): ?>
                                                    <span class="rd-badge"><i class="fas fa-star"></i>Reciente</span>
                                                <?php endif; ?>
                                                <?php if (($reservacion['total_habitaciones'] ?? 0) > 1): ?>
                                                    <span class="rd-badge"><i class="fas fa-users"></i>Grupo <?= (int)$reservacion['total_habitaciones'] ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="rd-history-meta">
                                                <span><i class="fas fa-sign-in-alt"></i><?= $fecha_entrada->format('d/m/Y') ?></span>
                                                <span><i class="fas fa-sign-out-alt"></i><?= $fecha_salida->format('d/m/Y') ?></span>
                                                <span><i class="fas fa-moon"></i><?= $duracion ?> <?= $duracion == 1 ? 'noche' : 'noches' ?></span>
                                                <span><i class="fas fa-calendar-alt"></i><?= room_detail_relative_exit($dias_desde_salida) ?></span>
                                                <?php if ($total_pagado): ?>
                                                    <span><i class="fas fa-dollar-sign"></i><?= format_money($total_pagado) ?></span>
                                                <?php endif; ?>
                                                <?php if ($procedencia !== ''): ?>
                                                    <span><i class="fas fa-map-marker-alt"></i><?= room_detail_safe($procedencia) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($reservacion['telefono'])): ?>
                                                    <span><i class="fas fa-phone"></i><?= room_detail_safe($reservacion['telefono']) ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($reservacion['vehiculos']) && $reservacion['vehiculos'] > 0): ?>
                                                    <span><i class="fas fa-car"></i><?= $reservacion['vehiculos'] > 1 ? (int)$reservacion['vehiculos'] . ' veh.' : '1 veh.' ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($reservacion['observaciones'])): ?>
                                                    <span title="<?= room_detail_safe($reservacion['observaciones']) ?>"><i class="fas fa-sticky-note"></i>Obs.</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <a href="<?= url('/reservaciones/ver/' . $reservacion['id']) ?>" class="rd-btn rd-btn-info">
                                            <i class="fas fa-eye"></i>
                                            Ver
                                        </a>
                                    </article>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($historial_count > 10): ?>
                                <div style="margin-top: 14px;">
                                    <a href="<?= url('/habitaciones/' . $habitacion_id . '/historial') ?>" class="rd-action">
                                        <span><i class="fas fa-history"></i>Ver historial completo (<?= number_format($historial_count) ?>)</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="rd-empty">
                                <i class="fas fa-inbox"></i>
                                <strong>Sin reservaciones previas</strong>
                                <span>Esta habitaci&oacute;n todav&iacute;a no tiene historial de hu&eacute;spedes.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>

            <aside class="rd-side" aria-label="Panel operativo">
                <section class="rd-side-card rd-rate-card">
                    <h3>Tarifa y estado</h3>
                    <p>Precio calculado para la fecha actual.</p>
                    <strong class="rd-rate-value"><?= format_money($precio_actual) ?> <small>/ noche</small></strong>
                    <?php if ($tiene_incremento): ?>
                        <span class="rd-rate-old">
                            Base <?= format_money($precio_base_original) ?>
                            +<?= format_money($incremento_total) ?>
                        </span>
                    <?php endif; ?>
                </section>

                <section class="rd-side-card">
                    <h3>Acciones r&aacute;pidas</h3>
                    <p>Operaciones disponibles seg&uacute;n el estado actual.</p>
                    <div class="rd-actions" style="margin-top: 13px;">
                        <a href="<?= url('habitaciones/' . $habitacion_id . '/edit') ?>" class="rd-action">
                            <span><i class="fas fa-edit"></i>Editar habitaci&oacute;n</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="<?= url('configuracion#hc-owners') ?>" class="rd-action">
                            <span><i class="fas fa-user-tie"></i>Configurar propietario</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>

                        <?php if ($habitacion_estado == 'disponible'): ?>
                            <div class="rd-action-grid">
                                <button type="button" onclick="mostrarModalMantenimiento()" class="rd-btn rd-btn-warning">
                                    <i class="fas fa-tools"></i>
                                    Iniciar mantenimiento
                                </button>
                                <a href="<?= url('reservaciones/crear?habitacion=' . $habitacion_id) ?>" class="rd-btn rd-btn-success">
                                    <i class="fas fa-calendar-plus"></i>
                                    Nueva reservaci&oacute;n
                                </a>
                                <button type="button" onclick="mostrarModalProgramarMantenimiento()" class="rd-btn rd-btn-warning" style="grid-column: 1 / -1;">
                                    <i class="fas fa-calendar-check"></i>
                                    Programar mantenimiento
                                </button>
                            </div>
                        <?php elseif ($habitacion_estado == 'limpieza'): ?>
                            <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/liberar') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="finalizar">
                                <button type="submit" class="rd-btn rd-btn-success" style="width: 100%;">
                                    <i class="fas fa-check-circle"></i>
                                    Finalizar limpieza
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="rd-empty-action">
                                No hay acciones de cambio de estado disponibles para este estado. Puedes revisar el expediente o editar la habitaci&oacute;n.
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="rd-side-card">
                    <h3>Datos de habitaci&oacute;n</h3>
                    <div class="rd-side-list">
                        <div class="rd-side-row">
                            <span>ID</span>
                            <strong>#<?= $habitacion_id ?></strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Tipo</span>
                            <strong><?= room_detail_safe($tipo_label) ?></strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Propietario</span>
                            <strong>
                                <?= room_detail_safe($propietario_nombre) ?>
                                <?php if ($propietario_pct_label !== ''): ?>
                                    <small><?= room_detail_safe($propietario_pct_label) ?></small>
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Piso</span>
                            <strong><?= room_detail_safe($piso_label) ?></strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Capacidad</span>
                            <strong><?= $capacidad > 0 ? number_format($capacidad) . ' personas' : 'No definida' ?></strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Camas</span>
                            <strong>
                                <?= $camas_total > 0 ? number_format($camas_total) . ' total' : 'No definidas' ?>
                                <?php if ($camas_total > 0): ?>
                                    <small><?= $camas_matrimoniales ?> mat. / <?= $camas_individuales ?> ind.</small>
                                <?php endif; ?>
                            </strong>
                        </div>
                        <div class="rd-side-row">
                            <span>Estado sistema</span>
                            <strong><?= $activa ? 'Activa' : 'Inactiva' ?></strong>
                        </div>
                    </div>
                    <?php if ($caracteristicas !== ''): ?>
                        <p style="margin-top: 13px;"><?= nl2br(room_detail_safe($caracteristicas)) ?></p>
                    <?php endif; ?>
                </section>

                <?php if ($proxima_salida): ?>
                    <section class="rd-side-card">
                        <h3>Pr&oacute;xima salida</h3>
                        <div class="rd-side-list">
                            <div class="rd-side-row">
                                <span>Fecha</span>
                                <strong><?= format_date($proxima_salida['fecha_salida'] ?? '') ?></strong>
                            </div>
                            <?php if (!empty($proxima_salida['nombre_completo'])): ?>
                                <div class="rd-side-row">
                                    <span>Hu&eacute;sped</span>
                                    <strong><?= room_detail_safe($proxima_salida['nombre_completo']) ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <section class="rd-side-card">
                    <h3>Historial completo</h3>
                    <p>Accede a todas las reservaciones y estad&iacute;sticas detalladas.</p>
                    <a href="<?= url('/habitaciones/' . $habitacion_id . '/historial') ?>" class="rd-action" style="margin-top: 13px;">
                        <span><i class="fas fa-history"></i>Ver historial completo</span>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </section>
            </aside>
        </div>
    </div>
</div>

<div id="modalMantenimiento" class="rd-modal rd-maint-modal hidden">
    <div class="rd-modal-card rd-maint-modal-card" id="modalContent">
        <div class="rd-modal-head">
            <div class="rd-maint-title">
                <span class="rd-maint-icon"><i class="fas fa-tools"></i></span>
                <div>
                    <span class="rd-maint-kicker">Cambio inmediato</span>
                    <h3>Iniciar mantenimiento</h3>
                    <p>Registra el trabajo y cambia la habitaci&oacute;n a mantenimiento desde este momento.</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalMantenimiento()" class="rd-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/mantenimiento') ?>" class="rd-modal-form">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="iniciar">

            <div class="rd-maint-summary">
                <span><i class="fas fa-door-open"></i>Hab. <?= room_detail_safe($habitacion_numero) ?></span>
                <span><i class="fas fa-bed"></i><?= room_detail_safe($tipo_label) ?></span>
                <span><i class="fas fa-circle"></i><?= room_detail_safe($status_meta['label'] ?? $estado_actual) ?></span>
            </div>

            <div class="rd-form-grid rd-maint-field-grid">
                <div class="rd-field">
                    <label class="rd-label">Tipo de mantenimiento</label>
                    <select name="tipo_mantenimiento" required class="rd-control">
                        <option value="">Seleccione...</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="correctivo">Correctivo</option>
                        <option value="emergencia">Emergencia</option>
                        <option value="limpieza_profunda">Limpieza profunda</option>
                    </select>
                </div>

                <div class="rd-field">
                    <label class="rd-label">Prioridad</label>
                    <select name="prioridad" required class="rd-control">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="rd-field">
                <label class="rd-label">Motivo</label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." class="rd-control">
            </div>

            <div class="rd-modal-actions">
                <button type="button" onclick="cerrarModalMantenimiento()" class="rd-btn rd-btn-maint-cancel">Cancelar</button>
                <button type="submit" class="rd-btn rd-btn-maint-primary">
                    <i class="fas fa-check"></i>
                    Iniciar
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modalProgramarMantenimiento" class="rd-modal rd-maint-modal hidden">
    <div class="rd-modal-card rd-maint-modal-card" id="modalProgramarContent">
        <div class="rd-modal-head">
            <div class="rd-maint-title">
                <span class="rd-maint-icon"><i class="fas fa-calendar-check"></i></span>
                <div>
                    <span class="rd-maint-kicker">Mantenimiento futuro</span>
                    <h3>Programar mantenimiento</h3>
                    <p>Agenda un bloqueo preventivo para evitar reservaciones que choquen con el trabajo.</p>
                </div>
            </div>
            <button type="button" onclick="cerrarModalProgramarMantenimiento()" class="rd-modal-close" aria-label="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="<?= url('habitaciones/' . $habitacion_id . '/programar-mantenimiento') ?>" class="rd-modal-form">
            <?= csrf_field() ?>

            <div class="rd-maint-summary">
                <span><i class="fas fa-door-open"></i>Hab. <?= room_detail_safe($habitacion_numero) ?></span>
                <span><i class="fas fa-bed"></i><?= room_detail_safe($tipo_label) ?></span>
                <span><i class="fas fa-circle"></i><?= room_detail_safe($status_meta['label'] ?? $estado_actual) ?></span>
            </div>

            <div class="rd-note rd-maint-note">
                <i class="fas fa-info"></i>
                <span>La habitaci&oacute;n seguir&aacute; disponible hasta la fecha programada, pero no se podr&aacute;n hacer reservaciones que conflicten con el mantenimiento.</span>
            </div>

            <div class="rd-form-grid">
                <div class="rd-field">
                    <label class="rd-label">Fecha inicio *</label>
                    <input type="date" name="fecha_programada" required min="<?= date('Y-m-d') ?>" class="rd-control">
                </div>
                <div class="rd-field">
                    <label class="rd-label">Fecha fin</label>
                    <input type="date" name="fecha_programada_fin" min="<?= date('Y-m-d') ?>" class="rd-control">
                </div>
            </div>

            <div class="rd-form-grid rd-maint-field-grid">
                <div class="rd-field">
                    <label class="rd-label">Tipo de mantenimiento</label>
                    <select name="tipo_mantenimiento" required class="rd-control">
                        <option value="">Seleccione...</option>
                        <option value="preventivo">Preventivo</option>
                        <option value="correctivo">Correctivo</option>
                        <option value="emergencia">Emergencia</option>
                        <option value="limpieza_profunda">Limpieza profunda</option>
                    </select>
                </div>

                <div class="rd-field">
                    <label class="rd-label">Prioridad</label>
                    <select name="prioridad" required class="rd-control">
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="rd-field">
                <label class="rd-label">Motivo</label>
                <input type="text" name="motivo" required placeholder="Describe el motivo del mantenimiento..." class="rd-control">
            </div>

            <div class="rd-modal-actions">
                <button type="button" onclick="cerrarModalProgramarMantenimiento()" class="rd-btn rd-btn-maint-cancel">Cancelar</button>
                <button type="submit" class="rd-btn rd-btn-maint-primary">
                    <i class="fas fa-calendar-check"></i>
                    Programar
                </button>
            </div>
        </form>
    </div>
</div>

<div id="lightbox" class="rd-lightbox hidden" onclick="cerrarLightbox()">
    <div class="rd-lightbox-frame" onclick="event.stopPropagation()">
        <img id="lightbox-img" src="" alt="Fotografia ampliada de habitacion">

        <?php if (count($imagenes ?? []) > 1): ?>
            <button type="button" onclick="navegarLightbox(-1)" class="rd-lightbox-btn rd-lightbox-prev" aria-label="Foto anterior">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" onclick="navegarLightbox(1)" class="rd-lightbox-btn rd-lightbox-next" aria-label="Foto siguiente">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="rd-lightbox-count">
                <span id="lightbox-counter">1 / <?= count($imagenes) ?></span>
            </div>

            <div class="rd-lightbox-thumbs">
                <?php foreach ($imagenes as $index => $imagen): ?>
                    <img src="<?= image_url($imagen['url']) ?>"
                         alt="Miniatura <?= $index + 1 ?>"
                         class="lightbox-thumb"
                         onclick="cambiarImagenLightbox(<?= $index ?>)"
                         data-index="<?= $index ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <button type="button" onclick="cerrarLightbox()" class="rd-lightbox-close" aria-label="Cerrar">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<script>
let currentIndex = 0;
let lightboxIndex = 0;
const imagenes = <?= json_encode(array_map(function($img) { return image_url($img['url']); }, $imagenes ?? [])) ?>;

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.vista-habitacion')?.classList.add('loaded');
    document.addEventListener('keydown', handleKeyPress);

    let touchStartX = 0;
    let touchEndX = 0;
    const lightbox = document.getElementById('lightbox');

    if (lightbox) {
        lightbox.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        });

        lightbox.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            const swipeThreshold = 50;
            const diff = touchStartX - touchEndX;

            if (Math.abs(diff) > swipeThreshold) {
                navegarLightbox(diff > 0 ? 1 : -1);
            }
        });
    }
});

function cambiarImagen(url, index) {
    const imgPrincipal = document.getElementById('imagen-principal');
    if (!imgPrincipal) return;

    imgPrincipal.src = url;
    currentIndex = index;

    const contador = document.getElementById('contador');
    if (contador) {
        contador.textContent = `${index + 1} / ${imagenes.length}`;
    }

    document.querySelectorAll('.room-thumb').forEach((img, i) => {
        img.classList.toggle('thumb-active', i === index);
    });
}

function hideSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = 'none';
}

function showSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.style.display = '';
}

function lockPage() {
    document.body.style.overflow = 'hidden';
}

function unlockPage() {
    document.body.style.overflow = '';
}

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
    if (lightbox) {
        lightbox.classList.add('hidden');
    }
    showSidebar();
    unlockPage();
}

function navegarLightbox(direccion) {
    if (imagenes.length <= 1) return;
    lightboxIndex = (lightboxIndex + direccion + imagenes.length) % imagenes.length;
    actualizarLightbox();
}

function cambiarImagenLightbox(index) {
    lightboxIndex = index;
    actualizarLightbox();
}

function actualizarLightbox() {
    const imgLightbox = document.getElementById('lightbox-img');
    if (imgLightbox && imagenes[lightboxIndex]) {
        imgLightbox.src = imagenes[lightboxIndex];
    }

    const counter = document.getElementById('lightbox-counter');
    if (counter) {
        counter.textContent = `${lightboxIndex + 1} / ${imagenes.length}`;
    }

    document.querySelectorAll('.lightbox-thumb').forEach((thumb, index) => {
        thumb.classList.toggle('is-active', index === lightboxIndex);
    });
}

function handleKeyPress(e) {
    const lightbox = document.getElementById('lightbox');
    if (!lightbox || lightbox.classList.contains('hidden')) return;

    if (e.key === 'Escape') cerrarLightbox();
    if (e.key === 'ArrowLeft') navegarLightbox(-1);
    if (e.key === 'ArrowRight') navegarLightbox(1);
}

function mostrarModalMantenimiento() {
    const modal = document.getElementById('modalMantenimiento');
    const content = document.getElementById('modalContent');
    if (!modal || !content) return;

    hideSidebar();
    modal.classList.remove('hidden');
    lockPage();
    setTimeout(() => {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 10);
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
    }, 240);
}

function realizarCheckout(reservacionId) {
    if (confirm('¿Realizar check-out de esta habitacion?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= url('reservaciones/check-out/') ?>' + reservacionId;

        const csrfField = document.createElement('input');
        csrfField.type = 'hidden';
        csrfField.name = 'csrf_token';
        csrfField.value = '<?= csrf_token() ?>';
        form.appendChild(csrfField);

        document.body.appendChild(form);
        form.submit();
    }
}

const modalMantenimiento = document.getElementById('modalMantenimiento');
if (modalMantenimiento) {
    modalMantenimiento.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalMantenimiento();
    });
}

function mostrarModalProgramarMantenimiento() {
    const modal = document.getElementById('modalProgramarMantenimiento');
    const content = document.getElementById('modalProgramarContent');
    if (!modal || !content) return;

    hideSidebar();
    modal.classList.remove('hidden');
    lockPage();
    setTimeout(() => {
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
    }, 10);
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
        showSidebar();
        unlockPage();
    }, 240);
}

const modalProgramar = document.getElementById('modalProgramarMantenimiento');
if (modalProgramar) {
    modalProgramar.addEventListener('click', function(e) {
        if (e.target === this) cerrarModalProgramarMantenimiento();
    });
}
</script>
