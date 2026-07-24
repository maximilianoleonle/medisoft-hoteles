<?php
if (!function_exists('tlm_safe')) {
    function tlm_safe($value, string $fallback = ''): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tk_detail_form_error')) {
    function tk_detail_form_error(array $errors, string $field): string
    {
        $messages = $errors[$field] ?? [];
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $message = trim((string)($messages[0] ?? ''));
        return $message !== '' ? tlm_safe($message) : '';
    }
}

if (!function_exists('tlm_date')) {
    function tlm_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

if (!function_exists('tk_detail_can_view_start_delay')) {
    function tk_detail_can_view_start_delay(): bool
    {
        // Dato de supervision (cuanto tardo en arrancar la tarea): lo ve quien
        // manda en Tareas. Antes miraba el rol del hotel Y el rol GLOBAL del
        // usuario (usuarios.rol), que es ajeno a este hotel: alguien con rol
        // global 'gerente' pero camarista en este hotel lo veia igual.
        // Auditoria de accesos, 23 jul 2026: una sola verdad, el permiso.
        return function_exists('can') && can('tareas.all');
    }
}

if (!function_exists('tk_detail_minute_timestamp')) {
    function tk_detail_minute_timestamp($value)
    {
        if (empty($value)) {
            return null;
        }

        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            return null;
        }

        $minuteTimestamp = strtotime(date('Y-m-d H:i:00', $timestamp));
        return $minuteTimestamp === false ? null : $minuteTimestamp;
    }
}

if (!function_exists('tk_detail_minutes_text')) {
    function tk_detail_minutes_text(int $minutes): string
    {
        $minutes = abs($minutes);
        return $minutes === 1 ? '1 minuto' : $minutes . ' minutos';
    }
}

if (!function_exists('tk_detail_start_delay_meta')) {
    function tk_detail_start_delay_meta(array $tarea)
    {
        $programada = tk_detail_minute_timestamp($tarea['fecha_programada'] ?? null);
        $inicio = tk_detail_minute_timestamp($tarea['fecha_inicio'] ?? null);

        if ($programada === null || $inicio === null) {
            return null;
        }

        $diffMinutes = intdiv($inicio - $programada, 60);
        $title = 'Programada ' . tlm_date($tarea['fecha_programada'] ?? null) . ' - Inicio ' . tlm_date($tarea['fecha_inicio'] ?? null);

        if ($diffMinutes > 0) {
            return [
                'label' => 'Retraso al iniciar',
                'text' => tk_detail_minutes_text($diffMinutes) . ' tarde',
                'class' => 'is-late',
                'icon' => 'fa-triangle-exclamation',
                'title' => $title,
            ];
        }

        if ($diffMinutes < 0) {
            return [
                'label' => 'Inicio vs programa',
                'text' => tk_detail_minutes_text($diffMinutes) . ' antes',
                'class' => 'is-early',
                'icon' => 'fa-circle-check',
                'title' => $title,
            ];
        }

        return [
            'label' => 'Inicio vs programa',
            'text' => 'A tiempo',
            'class' => 'is-on-time',
            'icon' => 'fa-circle-check',
            'title' => $title,
        ];
    }
}

if (!function_exists('tk_estado_meta')) {
    function tk_estado_meta($estado): array
    {
        $key = strtolower(trim((string)($estado ?? '')));
        $map = [
            'pendiente'  => ['Pendiente', 'is-pendiente', 'fa-clock'],
            'asignada'   => ['Asignada', 'is-asignada', 'fa-user-check'],
            'en_proceso' => ['En proceso', 'is-proceso', 'fa-spinner'],
            'completada' => ['Completada', 'is-completada', 'fa-circle-check'],
            'cancelada'  => ['Cancelada', 'is-cancelada', 'fa-circle-xmark'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Sin estado'), 'is-soft', 'fa-circle-dot'];
    }
}

if (!function_exists('tk_prioridad_meta')) {
    function tk_prioridad_meta($prioridad): array
    {
        $key = strtolower(trim((string)($prioridad ?? '')));
        $map = [
            'baja'    => ['Baja', 'is-baja', 'fa-arrow-down'],
            'media'   => ['Media', 'is-media', 'fa-equals'],
            'alta'    => ['Alta', 'is-alta', 'fa-arrow-up'],
            'urgente' => ['Urgente', 'is-urgente', 'fa-fire'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'Media'), 'is-media', 'fa-equals'];
    }
}

if (!function_exists('tk_categoria_meta')) {
    function tk_categoria_meta($categoria): array
    {
        $key = strtolower(trim((string)($categoria ?? '')));
        $map = [
            'limpieza'      => ['Limpieza', 'fa-broom'],
            'mantenimiento' => ['Mantenimiento', 'fa-screwdriver-wrench'],
            'general'       => ['General', 'fa-list-check'],
        ];
        return $map[$key] ?? [ucfirst($key !== '' ? $key : 'General'), 'fa-list-check'];
    }
}

$tarea = is_array($tarea ?? null) ? $tarea : [];
$eventos = is_array($eventos ?? null) ? $eventos : [];
$eventosDisponibles = (bool)($eventosDisponibles ?? false);
$trabajadoresActivos = is_array($trabajadoresActivos ?? null) ? $trabajadoresActivos : [];
$trabajadoresAsignados = is_array($trabajadoresAsignados ?? null) ? $trabajadoresAsignados : [];
$puedeAsignar = (bool)($puedeAsignar ?? false);
$puedeCambiarEstado = (bool)($puedeCambiarEstado ?? false);
$puedeEditar = function_exists('can') && can('habitaciones.mantenimiento');
$documentosEntidad = is_array($documentosEntidad ?? null) ? $documentosEntidad : [];
$tareaDetailFieldErrors = isset($layoutFieldErrors) && is_array($layoutFieldErrors) ? $layoutFieldErrors : [];

$estado = (string)($tarea['estado'] ?? 'pendiente');
$tareaId = (int)($tarea['id'] ?? 0);
[$eLabel, $eClass, $eIcon] = tk_estado_meta($estado);
[$pLabel, $pClass, $pIcon] = tk_prioridad_meta($tarea['prioridad'] ?? 'media');
[$cLabel, $cIcon] = tk_categoria_meta($tarea['categoria'] ?? 'general');
$puedeVerRetrasoInicio = tk_detail_can_view_start_delay();
$retrasoInicioMeta = $puedeVerRetrasoInicio ? tk_detail_start_delay_meta($tarea) : null;

$tareaDetailOldAction = (string)old('tarea_form_action', '');

// IDs de trabajadores preseleccionados en el formulario de asignacion.
$trabajadoresSeleccionados = [];
if ($tareaDetailOldAction === 'asignar' && is_array($_SESSION['old_input']['trabajador_ids'] ?? null)) {
    foreach ($_SESSION['old_input']['trabajador_ids'] as $tid) {
        $tid = (int)$tid;
        if ($tid > 0) {
            $trabajadoresSeleccionados[$tid] = true;
        }
    }
} else {
    foreach ($trabajadoresAsignados as $asignado) {
        $tid = (int)($asignado['trabajador_id'] ?? 0);
        if ($tid > 0) {
            $trabajadoresSeleccionados[$tid] = true;
        }
    }
}
$comentarioCompletarValor = $tareaDetailOldAction === 'completar' ? old('comentario', '') : '';
$comentarioCancelarValor = $tareaDetailOldAction === 'cancelar' ? old('comentario', '') : '';
$asignarGlobalError = $tareaDetailOldAction === 'asignar' ? tk_detail_form_error($tareaDetailFieldErrors, '_global') : '';
$estadoGlobalError = in_array($tareaDetailOldAction, ['iniciar', 'completar', 'cancelar'], true) ? tk_detail_form_error($tareaDetailFieldErrors, '_global') : '';
$trabajadorError = $tareaDetailOldAction === 'asignar' ? tk_detail_form_error($tareaDetailFieldErrors, 'trabajador_id') : '';
$comentarioCompletarError = $tareaDetailOldAction === 'completar' ? tk_detail_form_error($tareaDetailFieldErrors, 'comentario') : '';
$comentarioCancelarError = $tareaDetailOldAction === 'cancelar' ? tk_detail_form_error($tareaDetailFieldErrors, 'comentario') : '';

$responsablesResumen = [];
foreach ($trabajadoresAsignados as $asignado) {
    $nombre = trim((string)($asignado['nombre_completo'] ?? ''));
    if ($nombre !== '') {
        $responsablesResumen[] = $nombre;
    }
}
if (empty($responsablesResumen) && !empty($tarea['trabajador_nombre'])) {
    $responsablesResumen[] = trim((string)$tarea['trabajador_nombre']);
}
$responsablesTexto = !empty($responsablesResumen) ? implode(', ', $responsablesResumen) : 'Sin asignar';
$tieneResponsables = !empty($responsablesResumen);

$ubicacionTexto = 'Sin ubicación vinculada';
$ubicacionUrl = '';
$ubicacionEstado = '';
if (!empty($tarea['habitacion_id'])) {
    $ubicacionTexto = 'Hab. ' . trim((string)($tarea['habitacion_numero'] ?? $tarea['habitacion_id']));
    $ubicacionUrl = url('habitaciones/' . (int)$tarea['habitacion_id']);
    $ubicacionEstado = trim((string)($tarea['habitacion_estado'] ?? ''));
} elseif (!empty($tarea['area_id'])) {
    $ubicacionTexto = trim((string)($tarea['area_nombre'] ?? ('Área #' . (int)$tarea['area_id'])));
    $ubicacionUrl = url('areas/' . (int)$tarea['area_id']);
    $ubicacionEstado = trim((string)($tarea['area_estado'] ?? ''));
}

$fechaObjetivoValor = !empty($tarea['fecha_limite']) ? $tarea['fecha_limite'] : ($tarea['fecha_programada'] ?? null);
$fechaObjetivoEtiqueta = !empty($tarea['fecha_limite']) ? 'Fecha límite' : 'Programada';
$pasoActual = $estado === 'pendiente' ? 1 : ($estado === 'asignada' ? 2 : ($estado === 'en_proceso' ? 3 : ($estado === 'completada' ? 4 : 0)));

$siguientePaso = [
    'icono' => 'fa-user-check',
    'titulo' => 'Asigna responsables',
    'texto' => 'Elige quién se encargará para que el equipo sepa a quién le corresponde.',
];
if ($estado === 'asignada') {
    $siguientePaso = [
        'icono' => 'fa-play',
        'titulo' => 'Inicia la tarea',
        'texto' => 'La responsabilidad ya está clara. Iníciala cuando el trabajo comience.',
    ];
} elseif ($estado === 'en_proceso') {
    $siguientePaso = [
        'icono' => 'fa-circle-check',
        'titulo' => 'Finaliza el trabajo',
        'texto' => 'Al terminar, agrega una nota si hace falta y marca la tarea como completada.',
    ];
} elseif ($estado === 'completada') {
    $siguientePaso = [
        'icono' => 'fa-circle-check',
        'titulo' => 'Trabajo completado',
        'texto' => 'La tarea ya terminó. Consulta el historial si necesitas revisar qué ocurrió.',
    ];
} elseif ($estado === 'cancelada') {
    $siguientePaso = [
        'icono' => 'fa-circle-xmark',
        'titulo' => 'Tarea cancelada',
        'texto' => 'Ya no requiere acciones. El motivo y los movimientos permanecen en el historial.',
    ];
}
?>

<style>
.tk-detail {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 78%, var(--tk-brand));
    --tk-ivory: #F5F5F7; --tk-ivory-2: #FAFAFC;
    --tk-surface: #FFFFFF; --tk-surface-warm: #F5F5F7;
    --tk-border: color-mix(in srgb, var(--tk-brand) 7%, #E7E1D4);
    --tk-ring: color-mix(in srgb, var(--tk-gold) 32%, transparent);
    --tk-text: color-mix(in srgb, var(--tk-brand) 36%, #596474);
    --tk-text-soft: color-mix(in srgb, var(--tk-brand) 30%, #6C7788);
    --tk-muted: #828B99;
    --tk-heading: color-mix(in srgb, var(--tk-brand) 62%, #667284);
    --tk-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-success: #1E9E63; --tk-success-bg: #E7F4EC;
    --tk-warning: #C2841C; --tk-warning-bg: #FAF0DC;
    --tk-danger: #B4392B; --tk-danger-bg: #F8EAE5;
    --tk-info: #2F77E0; --tk-info-bg: #E6EFFC;
    --tk-proc: #0E8A8A; --tk-proc-bg: #E2F4F4;
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    
}
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.tk-detail .tk-shell { display: grid; gap: 18px; max-width: 1320px; margin: 0 auto; }
.tk-detail.tk-detail--case {
    --tk-view-accent: var(--tk-brand);
    --tk-view-soft: color-mix(in srgb, var(--tk-brand) 7%, #FFFFFF);
    
}
.tk-detail .tk-topbar { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.tk-detail .tk-back { display: inline-flex; align-items: center; gap: 8px; color: var(--tk-muted); text-decoration: none; font-weight: 700; font-size: .85rem; }
.tk-detail .tk-back:hover { color: var(--tk-gold-ink); }
.tk-detail.tk-detail--case .tk-title-lockup {
    padding: 15px 16px;
    border-radius: 18px;
    background:
        linear-gradient(135deg, rgba(255,255,255,.92), rgba(255,255,255,.72)),
        linear-gradient(90deg, color-mix(in srgb, var(--tk-brand) 10%, transparent), transparent);
    border: 1px solid color-mix(in srgb, var(--tk-brand) 14%, var(--tk-border));
    box-shadow: 0 14px 32px -28px color-mix(in srgb, var(--tk-brand) 70%, transparent);
}
.tk-detail.tk-detail--case .tk-hero-icon {
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.25), transparent 34%), linear-gradient(145deg, var(--tk-brand), color-mix(in srgb, var(--tk-brand) 70%, var(--tk-gold)));
}
.tk-detail .tk-title-lockup { display: grid; grid-template-columns: 52px minmax(0, 1fr); align-items: center; column-gap: 16px; min-width: 0; }
.tk-detail .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-detail .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-detail .tk-title { margin: 0; max-width: 980px; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(1.75rem, 3vw, 2.45rem); line-height: 1.08; text-wrap: balance; }
.tk-detail .tk-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.tk-detail .tk-view-chip { display: none; }

.tk-detail .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 999px; font-size: .76rem; font-weight: 700; border: 1px solid transparent; }
.tk-detail .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 28%, #fff); }
.tk-detail .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 26%, #fff); }
.tk-detail .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 80%, #000); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 26%, #fff); }
.tk-detail .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 78%, #000); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 26%, #fff); }
.tk-detail .tk-badge.is-cancelada, .tk-detail .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-detail .tk-badge.is-cat { color: var(--tk-gold-ink); background: var(--tk-gold-soft); border-color: var(--tk-gold-line); }
.tk-detail .tk-badge.is-baja { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }
.tk-detail .tk-badge.is-media { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 24%, #fff); }
.tk-detail .tk-badge.is-alta { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 26%, #fff); }
.tk-detail .tk-badge.is-urgente { color: color-mix(in srgb, var(--tk-danger) 82%, #000); background: var(--tk-danger-bg); border-color: color-mix(in srgb, var(--tk-danger) 26%, #fff); }

.tk-detail .tk-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(340px, .85fr); gap: 18px; align-items: start; }
.tk-detail .tk-col { display: grid; gap: 14px; }
.tk-detail .tk-card { position: relative; overflow: hidden; background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; padding: 18px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.tk-detail.tk-detail--case .tk-card::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: color-mix(in srgb, var(--tk-view-accent) 26%, transparent); }
.tk-detail.tk-detail--case .tk-card:hover::before { background: color-mix(in srgb, var(--tk-gold) 55%, var(--tk-view-accent)); }
.tk-detail .tk-card h2 { font-family: var(--tk-serif); font-size: 1.2rem; font-weight: 700; color: var(--tk-heading); margin: 0 0 12px; }
.tk-detail .tk-desc { color: var(--tk-text-soft); line-height: 1.6; white-space: pre-wrap; }
.tk-detail .tk-defs { display: grid; grid-template-columns: 150px 1fr; gap: 9px 14px; font-size: .88rem; }
.tk-detail .tk-defs dt { color: var(--tk-muted); font-weight: 700; }
.tk-detail .tk-defs dd { margin: 0; color: var(--tk-text); font-weight: 600; }
.tk-detail .tk-defs a { color: var(--tk-info); text-decoration: none; font-weight: 700; }
.tk-detail .tk-defs a:hover { text-decoration: underline; }
.tk-detail .tk-faint { color: var(--tk-muted); font-weight: 500; }
.tk-detail .tk-start-delay { display: inline-flex; align-items: center; gap: 7px; width: fit-content; max-width: 100%; padding: 5px 10px; border-radius: 999px; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); color: var(--tk-text); font-size: .82rem; font-weight: 700; line-height: 1.15; }
.tk-detail .tk-start-delay.is-late { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 32%, #fff); }
.tk-detail .tk-start-delay.is-on-time { color: color-mix(in srgb, var(--tk-success) 78%, #000); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 28%, #fff); }
.tk-detail .tk-start-delay.is-early { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 28%, #fff); }

.tk-detail .tk-empty-box { padding: 22px; text-align: center; color: var(--tk-muted); border: 1px dashed var(--tk-border); border-radius: 12px; background: var(--tk-surface-warm); font-size: .88rem; }
.tk-detail .tk-label { display: block; font-size: .72rem; font-weight: 700; color: var(--tk-muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.tk-detail .tk-field { width: 100%; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); border-radius: 11px; padding: 10px 12px; color: var(--tk-text); font-weight: 600; font-size: .88rem; font-family: var(--tk-sans); transition: border-color .16s ease, box-shadow .16s ease; }
.tk-detail select.tk-field { min-height: 44px; cursor: pointer; }
.tk-detail textarea.tk-field { min-height: 74px; resize: vertical; }
.tk-detail .tk-field:focus { border-color: var(--tk-gold); box-shadow: 0 0 0 3px var(--tk-ring); outline: none; background: #fff; }
.tk-detail .tk-help { font-size: .76rem; color: var(--tk-muted); margin-top: 8px; }
.tk-detail .tk-field-error { border-color: var(--tk-danger); background: #FFF7F6; }
.tk-detail .tk-form-error { display: block; margin-top: 7px; color: var(--tk-danger); font-size: .78rem; font-weight: 800; line-height: 1.35; letter-spacing: 0; text-transform: none; }
.tk-detail .tk-error-summary { padding: 11px 13px; border: 1px solid color-mix(in srgb, var(--tk-danger) 32%, #fff); border-radius: 12px; background: #FFF7F6; color: color-mix(in srgb, var(--tk-danger) 86%, #000); font-size: .84rem; font-weight: 800; }
.tk-detail .tk-form-row { display: grid; gap: 10px; }
.tk-detail .tk-workers { display: grid; gap: 7px; max-height: 286px; overflow-y: auto; padding: 4px; scrollbar-gutter: stable; }
.tk-detail .tk-worker { display: flex; align-items: center; gap: 9px; padding: 9px 11px; border: 1px solid var(--tk-border); border-radius: 10px; background: var(--tk-surface-warm); cursor: pointer; transition: border-color .14s ease, box-shadow .14s ease, background .14s ease; }
.tk-detail .tk-worker:hover { border-color: var(--tk-gold-line); }
.tk-detail .tk-worker input { width: 17px; height: 17px; accent-color: var(--tk-gold); cursor: pointer; flex: 0 0 auto; }
.tk-detail .tk-worker:has(input:checked) { border-color: var(--tk-gold); background: var(--tk-gold-soft); box-shadow: 0 0 0 2px var(--tk-ring); }
.tk-detail .tk-worker-name { font-size: .85rem; font-weight: 700; color: var(--tk-text); line-height: 1.2; min-width: 0; }
.tk-detail .tk-worker-role { display: block; font-size: .72rem; font-weight: 600; color: var(--tk-muted); }
.tk-detail .tk-stack { display: grid; gap: 14px; }

.tk-detail .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 44px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; line-height: 1; cursor: pointer; text-decoration: none; width: 100%;
    transition: transform .16s ease, box-shadow .16s ease, background .16s ease, filter .16s ease; }
.tk-detail .tk-btn:hover { transform: translateY(-1px); filter: brightness(1.03); }
.tk-detail .tk-btn-gold { background: linear-gradient(135deg, var(--tk-gold), color-mix(in srgb, var(--tk-gold) 76%, #000)); color: #fff; box-shadow: 0 12px 26px -10px color-mix(in srgb, var(--tk-gold) 58%, transparent); }
.tk-detail .tk-btn-brand { background: linear-gradient(135deg, var(--tk-brand), var(--tk-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--tk-brand) 60%, transparent); }
.tk-detail .tk-btn-success { background: linear-gradient(135deg, var(--tk-success), color-mix(in srgb, var(--tk-success) 74%, #000)); color: #fff; }
.tk-detail .tk-btn-danger { background: linear-gradient(135deg, var(--tk-danger), color-mix(in srgb, var(--tk-danger) 72%, #000)); color: #fff; }

.tk-detail .tk-timeline { display: grid; gap: 10px; }
.tk-detail .tk-event { border: 1px solid var(--tk-border); border-radius: 12px; padding: 12px; background: var(--tk-surface-warm); }
.tk-detail.tk-detail--case .tk-event { border-left: 4px solid color-mix(in srgb, var(--tk-gold) 50%, var(--tk-border)); }
.tk-detail .tk-event strong { display: block; color: var(--tk-text); font-weight: 700; }
.tk-detail .tk-event .tk-faint { font-size: .78rem; }

.tk-detail .tk-essentials { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
.tk-detail .tk-essential { min-width: 0; display: grid; grid-template-columns: 38px minmax(0, 1fr); gap: 10px; align-items: center; padding: 12px 14px; border: 1px solid var(--tk-border); border-radius: 14px; background: color-mix(in srgb, var(--tk-surface) 86%, var(--tk-view-soft)); }
.tk-detail .tk-essential-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; background: var(--tk-view-soft); color: var(--tk-brand); }
.tk-detail .tk-essential-label { display: block; color: var(--tk-muted); font-size: .68rem; font-weight: 800; letter-spacing: .06em; line-height: 1.2; text-transform: uppercase; }
.tk-detail .tk-essential-value { display: block; margin-top: 3px; overflow: hidden; color: var(--tk-heading); font-size: .9rem; font-weight: 800; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }
.tk-detail .tk-essential-value a { color: inherit; text-decoration: none; }
.tk-detail .tk-essential-value a:hover { text-decoration: underline; }
.tk-detail .tk-essential-note { display: block; margin-top: 2px; overflow: hidden; color: var(--tk-muted); font-size: .72rem; line-height: 1.25; text-overflow: ellipsis; white-space: nowrap; }

.tk-detail .tk-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px; }
.tk-detail .tk-card-head h2 { margin-bottom: 2px; }
.tk-detail .tk-card-subtitle { margin: 0; color: var(--tk-muted); font-size: .8rem; line-height: 1.4; }
.tk-detail .tk-current-assignment { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; padding: 11px 12px; border-radius: 12px; background: var(--tk-view-soft); border: 1px solid color-mix(in srgb, var(--tk-brand) 12%, var(--tk-border)); }
.tk-detail .tk-current-assignment > i { width: 32px; height: 32px; display: grid; flex: 0 0 auto; place-items: center; border-radius: 10px; background: var(--tk-surface); color: var(--tk-brand); }
.tk-detail .tk-current-assignment strong { display: block; color: var(--tk-heading); font-size: .86rem; line-height: 1.25; }
.tk-detail .tk-current-assignment span { display: block; margin-top: 2px; color: var(--tk-muted); font-size: .74rem; line-height: 1.3; }

.tk-detail .tk-next-callout { display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: 11px; align-items: start; margin-bottom: 14px; padding: 13px; border-radius: 14px; background: var(--tk-view-soft); border: 1px solid color-mix(in srgb, var(--tk-brand) 14%, var(--tk-border)); }
.tk-detail .tk-next-callout > i { width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; color: #fff; background: var(--tk-brand); }
.tk-detail .tk-next-callout strong { display: block; color: var(--tk-heading); font-size: .94rem; }
.tk-detail .tk-next-callout p { margin: 3px 0 0; color: var(--tk-text-soft); font-size: .8rem; line-height: 1.45; }
.tk-detail .tk-progress { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin: 0 0 14px; padding: 0; list-style: none; }
.tk-detail .tk-progress li { min-width: 0; display: flex; align-items: center; gap: 6px; padding: 7px 8px; border-radius: 10px; background: var(--tk-surface-warm); color: var(--tk-muted); font-size: .68rem; font-weight: 800; line-height: 1.15; }
.tk-detail .tk-progress b { width: 20px; height: 20px; display: grid; flex: 0 0 auto; place-items: center; border: 1px solid var(--tk-border); border-radius: 50%; background: var(--tk-surface); font-size: .65rem; }
.tk-detail .tk-progress li.is-active { color: var(--tk-heading); background: var(--tk-info-bg); }
.tk-detail .tk-progress li.is-active b { border-color: var(--tk-info); background: var(--tk-info); color: #fff; }
.tk-detail .tk-progress li.is-done { color: color-mix(in srgb, var(--tk-success) 80%, #000); background: var(--tk-success-bg); }
.tk-detail .tk-progress li.is-done b { border-color: var(--tk-success); background: var(--tk-success); color: #fff; }

.tk-detail .tk-disclosure { border-top: 1px solid var(--tk-border); margin-top: 12px; padding-top: 4px; }
.tk-detail .tk-disclosure summary { min-height: 44px; display: flex; align-items: center; gap: 8px; color: var(--tk-text); cursor: pointer; font-size: .82rem; font-weight: 800; list-style: none; }
.tk-detail .tk-disclosure summary::-webkit-details-marker { display: none; }
.tk-detail .tk-disclosure summary::after { content: '\f078'; margin-left: auto; color: var(--tk-muted); font-family: 'Font Awesome 6 Free'; font-size: .7rem; font-weight: 900; transition: transform .18s ease; }
.tk-detail .tk-disclosure[open] summary::after { transform: rotate(180deg); }
.tk-detail .tk-disclosure-body { padding: 4px 0 2px; }
.tk-detail .tk-action-col { display: flex; flex-direction: column; gap: 14px; }
.tk-detail .tk-action-col .tk-state-card { order: 1; }
.tk-detail .tk-action-col .tk-assignment-card { order: 2; }
.tk-detail .tk-action-col .tk-final-card { order: 3; }
.tk-detail .tk-action-col.is-pendiente .tk-assignment-card { order: 1; }
.tk-detail .tk-action-col.is-pendiente .tk-state-card { order: 2; }
.tk-detail .tk-secondary-note { margin: 0 0 10px; color: var(--tk-muted); font-size: .78rem; line-height: 1.45; }
.tk-detail .tk-btn-muted { color: var(--tk-text); background: var(--tk-surface); border-color: var(--tk-border); box-shadow: none; }
.tk-detail .tk-btn-muted:hover { border-color: var(--tk-gold-line); background: var(--tk-gold-soft); }
.tk-detail .tk-action-col.is-pendiente .tk-state-card .tk-btn-brand { color: var(--tk-text); background: var(--tk-surface); border-color: var(--tk-border); box-shadow: none; }
.tk-detail .tk-action-col.is-pendiente .tk-state-card .tk-btn-brand:hover { border-color: var(--tk-gold-line); background: var(--tk-gold-soft); }
.tk-detail .tk-btn-danger { box-shadow: none; }
.tk-detail .tk-topbar .tk-btn { width: auto; min-width: 148px; }

@media (prefers-reduced-motion: reduce) {
    .tk-detail *, .tk-detail *::before, .tk-detail *::after { scroll-behavior: auto !important; transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
}

@media (max-width: 980px) {
    .tk-detail .tk-grid { display: flex; flex-direction: column; }
    .tk-detail .tk-action-col { display: grid; }
    .tk-detail .tk-action-col { order: -1; }
}
@media (max-width: 680px) {
    .tk-detail { padding: 12px !important; }
    .tk-detail.tk-detail--case .tk-title-lockup { padding: 14px; }
    .tk-detail .tk-title-lockup { grid-template-columns: 44px minmax(0, 1fr); column-gap: 11px; }
    .tk-detail .tk-hero-icon { width: 44px; height: 44px; border-radius: 13px; }
    .tk-detail .tk-title { font-size: clamp(1.45rem, 7vw, 1.85rem); }
    .tk-detail .tk-chips { grid-column: 1 / -1; }
    .tk-detail .tk-badge { font-size: .7rem; }
    .tk-detail .tk-essentials { grid-template-columns: 1fr; }
    .tk-detail .tk-essential-value, .tk-detail .tk-essential-note { white-space: normal; }
    .tk-detail .tk-defs { grid-template-columns: 1fr; gap: 4px; }
    .tk-detail .tk-defs dd { margin-bottom: 8px; }
    .tk-detail .tk-progress { grid-template-columns: 1fr; }
    .tk-detail .tk-card { padding: 16px; }
    .tk-detail .tk-topbar .tk-btn { width: auto; min-height: 44px; }
}
</style>

<div class="tk-detail tk-detail--case p-4 sm:p-6">
    <div class="tk-shell">
        <div class="tk-topbar">
            <?php $back_arrow_href = back_url('tareas'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="tk-back ms-back-legacy" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Volver a tareas</a>
            <?php if ($puedeEditar && $tareaId > 0): ?>
                <a class="tk-btn tk-btn-muted" href="<?= url('tareas/' . $tareaId . '/editar') ?>"><i class="fas fa-pen"></i> Editar tarea</a>
            <?php endif; ?>
        </div>

        <section class="tk-title-lockup">
            <div class="tk-hero-icon"><i class="fas <?= $cIcon ?>"></i></div>
            <div>
                <p class="tk-kicker">Tarea #<?= $tareaId ?></p>
                <h1 class="tk-title"><?= tlm_safe($tarea['titulo'] ?? '', 'Tarea operativa') ?></h1>
                <div class="tk-view-chip"><i class="fas fa-folder-open"></i> Expediente de tarea</div>
                <div class="tk-chips">
                    <span class="tk-badge is-cat"><i class="fas <?= $cIcon ?>"></i> <?= $cLabel ?></span>
                    <span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span>
                    <span class="tk-badge <?= $pClass ?>"><i class="fas <?= $pIcon ?>"></i> Prioridad <?= $pLabel ?></span>
                    <span class="tk-badge is-soft"><i class="fas fa-tag"></i> Origen <?= tlm_safe($tarea['origen'] ?? 'manual') ?></span>
                </div>
            </div>
        </section>

        <section class="tk-essentials" aria-label="Resumen de la tarea">
            <div class="tk-essential">
                <span class="tk-essential-icon"><i class="fas fa-location-dot" aria-hidden="true"></i></span>
                <span>
                    <span class="tk-essential-label">Dónde</span>
                    <strong class="tk-essential-value">
                        <?php if ($ubicacionUrl !== ''): ?>
                            <a href="<?= $ubicacionUrl ?>"><?= tlm_safe($ubicacionTexto) ?></a>
                        <?php else: ?>
                            <?= tlm_safe($ubicacionTexto) ?>
                        <?php endif; ?>
                    </strong>
                    <?php if ($ubicacionEstado !== ''): ?><span class="tk-essential-note">Estado: <?= tlm_safe($ubicacionEstado) ?></span><?php endif; ?>
                </span>
            </div>
            <div class="tk-essential">
                <span class="tk-essential-icon"><i class="fas <?= $tieneResponsables ? 'fa-user-check' : 'fa-user-clock' ?>" aria-hidden="true"></i></span>
                <span>
                    <span class="tk-essential-label">Responsable<?= count($responsablesResumen) === 1 ? '' : 's' ?></span>
                    <strong class="tk-essential-value"><?= tlm_safe($responsablesTexto) ?></strong>
                    <span class="tk-essential-note"><?= $tieneResponsables ? count($responsablesResumen) . ' persona' . (count($responsablesResumen) === 1 ? '' : 's') . ' asignada' . (count($responsablesResumen) === 1 ? '' : 's') : 'Requiere asignación' ?></span>
                </span>
            </div>
            <div class="tk-essential">
                <span class="tk-essential-icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
                <span>
                    <span class="tk-essential-label"><?= tlm_safe($fechaObjetivoEtiqueta) ?></span>
                    <strong class="tk-essential-value"><?= tlm_safe(tlm_date($fechaObjetivoValor)) ?></strong>
                    <span class="tk-essential-note"><?= !empty($tarea['fecha_limite']) ? 'Momento objetivo para terminar' : 'Momento previsto para comenzar' ?></span>
                </span>
            </div>
        </section>

        <div class="tk-grid">
            <div class="tk-col tk-primary-col">
                <section class="tk-card">
                    <div class="tk-card-head">
                        <div>
                            <h2>Qué hay que hacer</h2>
                            <p class="tk-card-subtitle">Instrucciones y contexto para realizar el trabajo.</p>
                        </div>
                    </div>
                    <?php if (!empty($tarea['descripcion'])): ?>
                        <div class="tk-desc"><?= tlm_safe($tarea['descripcion']) ?></div>
                    <?php else: ?>
                        <div class="tk-empty-box">Esta tarea no tiene descripci&oacute;n.</div>
                    <?php endif; ?>
                </section>

                <section class="tk-card">
                    <h2>Información relacionada</h2>
                    <dl class="tk-defs">
                        <dt>Ubicaci&oacute;n</dt>
                        <dd>
                            <?php if (!empty($tarea['habitacion_id'])): ?>
                                <a href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a>
                                <span class="tk-faint">(<?= tlm_safe($tarea['habitacion_estado'] ?? '-') ?>)</span>
                            <?php elseif (!empty($tarea['area_id'])): ?>
                                <a href="<?= url('areas/' . (int)$tarea['area_id']) ?>"><?= tlm_safe($tarea['area_nombre'] ?? ('Área #' . (int)$tarea['area_id'])) ?></a>
                                <span class="tk-faint">(<?= tlm_safe($tarea['area_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">Sin habitaci&oacute;n ni &aacute;rea</span>
                            <?php endif; ?>
                        </dd>
                        <dt>Trabajadores</dt>
                        <dd>
                            <?php if (!empty($trabajadoresAsignados)): ?>
                                <?php foreach ($trabajadoresAsignados as $i => $asignado): ?>
                                    <?php $taId = (int)($asignado['trabajador_id'] ?? 0); ?>
                                    <a href="<?= url('trabajadores/' . $taId) ?>"><?= tlm_safe($asignado['nombre_completo'] ?? ('Trabajador #' . $taId)) ?></a><?php if ($i === 0): ?> <span class="tk-faint">(responsable)</span><?php endif; ?><?= $i < count($trabajadoresAsignados) - 1 ? ', ' : '' ?>
                                <?php endforeach; ?>
                            <?php elseif (!empty($tarea['trabajador_id'])): ?>
                                <a href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_safe($tarea['trabajador_nombre'] ?? 'Trabajador #' . (int)$tarea['trabajador_id']) ?></a>
                                <span class="tk-faint">(<?= tlm_safe($tarea['trabajador_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">Sin asignar</span>
                            <?php endif; ?>
                        </dd>
                        <dt>Reservaci&oacute;n</dt>
                        <dd><?= !empty($tarea['reservacion_id']) ? '#' . (int)$tarea['reservacion_id'] : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Hu&eacute;sped</dt>
                        <dd><?= !empty($tarea['huesped_nombre']) ? tlm_safe($tarea['huesped_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Mantenimiento</dt>
                        <dd>
                            <?php if (!empty($tarea['mantenimiento_id'])): ?>
                                #<?= (int)$tarea['mantenimiento_id'] ?> &middot; <?= tlm_safe($tarea['mantenimiento_motivo'] ?? '', '-') ?>
                                <span class="tk-faint">(<?= tlm_safe($tarea['mantenimiento_estado'] ?? '-') ?>)</span>
                            <?php else: ?>
                                <span class="tk-faint">-</span>
                            <?php endif; ?>
                        </dd>
                    </dl>
                </section>

                <section class="tk-card">
                    <h2>Fechas y seguimiento</h2>
                    <dl class="tk-defs">
                        <dt>Programada</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_programada'] ?? null)) ?></dd>
                        <dt>L&iacute;mite</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_limite'] ?? null)) ?></dd>
                        <dt>Inicio</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_inicio'] ?? null)) ?></dd>
                        <?php if ($retrasoInicioMeta !== null): ?>
                            <dt><?= tlm_safe($retrasoInicioMeta['label'] ?? 'Inicio vs programa') ?></dt>
                            <dd>
                                <span class="tk-start-delay <?= tlm_safe($retrasoInicioMeta['class'] ?? '') ?>" title="<?= tlm_safe($retrasoInicioMeta['title'] ?? '') ?>" aria-label="<?= tlm_safe(($retrasoInicioMeta['label'] ?? 'Inicio vs programa') . ': ' . ($retrasoInicioMeta['text'] ?? '')) ?>">
                                    <i class="fas <?= tlm_safe($retrasoInicioMeta['icon'] ?? 'fa-clock') ?>"></i>
                                    <?= tlm_safe($retrasoInicioMeta['text'] ?? '') ?>
                                </span>
                            </dd>
                        <?php endif; ?>
                        <dt>Cierre</dt><dd><?= tlm_safe(tlm_date($tarea['fecha_cierre'] ?? null)) ?></dd>
                        <dt>Creada por</dt><dd><?= !empty($tarea['creada_por_nombre']) ? tlm_safe($tarea['creada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Asignada por</dt><dd><?= !empty($tarea['asignada_por_nombre']) ? tlm_safe($tarea['asignada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                        <dt>Cerrada por</dt><dd><?= !empty($tarea['cerrada_por_nombre']) ? tlm_safe($tarea['cerrada_por_nombre']) : '<span class="tk-faint">-</span>' ?></dd>
                    </dl>
                </section>

                <section class="tk-card">
                    <div class="tk-card-head">
                        <div>
                            <h2>Historial</h2>
                            <p class="tk-card-subtitle">Quién hizo cada cambio y cuándo ocurrió.</p>
                        </div>
                    </div>
                    <?php if (!$eventosDisponibles): ?>
                        <div class="tk-empty-box">El historial todav&iacute;a no est&aacute; disponible.</div>
                    <?php elseif (empty($eventos)): ?>
                        <div class="tk-empty-box">A&uacute;n no hay movimientos en esta tarea.</div>
                    <?php else: ?>
                        <div class="tk-timeline">
                            <?php foreach ($eventos as $evento): ?>
                                <div class="tk-event">
                                    <strong><?= tlm_safe($evento['tipo_evento'] ?? 'Evento') ?></strong>
                                    <span class="tk-faint"><?= tlm_safe(tlm_date($evento['created_at'] ?? null)) ?> &middot; <?= tlm_safe($evento['usuario_nombre'] ?? '', 'Sistema') ?></span>
                                    <?php if (!empty($evento['comentario'])): ?>
                                        <div class="tk-desc" style="margin-top:6px"><?= tlm_safe($evento['comentario']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <div class="tk-col tk-action-col <?= $eClass ?>">
                <section class="tk-card tk-assignment-card" id="tk-responsables">
                    <div class="tk-card-head">
                        <div>
                            <h2>Responsables</h2>
                            <p class="tk-card-subtitle">Deja claro quién se encargará de esta tarea.</p>
                        </div>
                    </div>
                    <div class="tk-current-assignment">
                        <i class="fas <?= $tieneResponsables ? 'fa-user-check' : 'fa-user-clock' ?>" aria-hidden="true"></i>
                        <div>
                            <strong><?= tlm_safe($responsablesTexto) ?></strong>
                            <span><?= $tieneResponsables ? 'Asignación actual' : 'Aún no hay una persona responsable' ?></span>
                        </div>
                    </div>
                    <?php if (!$puedeAsignar): ?>
                        <div class="tk-empty-box">La asignación ya no se puede cambiar en el estado actual.</div>
                    <?php elseif (empty($trabajadoresActivos)): ?>
                        <div class="tk-empty-box">No hay trabajadores activos para asignar en este hotel.</div>
                    <?php else: ?>
                        <?php if ($tieneResponsables): ?>
                            <details class="tk-disclosure"<?= $trabajadorError !== '' || $asignarGlobalError !== '' ? ' open' : '' ?>>
                                <summary><i class="fas fa-people-arrows-left-right" aria-hidden="true"></i>Cambiar responsables</summary>
                                <div class="tk-disclosure-body">
                        <?php endif; ?>
                        <form method="POST" action="<?= url('tareas/' . $tareaId . '/asignar') ?>" class="tk-form-row">
                            <?= csrf_field() ?>
                            <?php if ($asignarGlobalError !== ''): ?>
                                <div class="tk-error-summary ms-form-error-summary" role="alert"><?= $asignarGlobalError ?></div>
                            <?php endif; ?>
                            <div>
                                <label class="tk-label">Trabajadores</label>
                                <div class="tk-workers<?= $trabajadorError !== '' ? ' tk-field-error' : '' ?>" role="group" aria-label="Trabajadores disponibles"<?= $trabajadorError !== '' ? ' aria-describedby="ms-form-error-tk_trabajador"' : '' ?>>
                                    <?php foreach ($trabajadoresActivos as $trabajador): ?>
                                        <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                                        <?php if ($trabajadorId <= 0) { continue; } ?>
                                        <label class="tk-worker">
                                            <input type="checkbox" name="trabajador_ids[]" value="<?= $trabajadorId ?>"<?= isset($trabajadoresSeleccionados[$trabajadorId]) ? ' checked' : '' ?>>
                                            <span class="tk-worker-name">
                                                <?= tlm_safe($trabajador['nombre_completo'] ?? ('Trabajador #' . $trabajadorId)) ?>
                                                <?php if (!empty($trabajador['rol_laboral'])): ?>
                                                    <span class="tk-worker-role"><?= tlm_safe($trabajador['rol_laboral']) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ($trabajadorError !== ''): ?>
                                    <span id="ms-form-error-tk_trabajador" class="tk-form-error ms-form-field-error"><?= $trabajadorError ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="tk-btn tk-btn-gold" type="submit"><i class="fas fa-user-check"></i> Guardar asignaci&oacute;n</button>
                            <div class="tk-help">Puedes elegir a varias personas. La primera queda como responsable principal; esto no genera pagos ni cambia la ubicación.</div>
                        </form>
                        <?php if ($tieneResponsables): ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>

                <section class="tk-card tk-state-card" id="tk-siguiente-paso">
                    <div class="tk-card-head">
                        <div>
                            <h2>Siguiente paso</h2>
                            <p class="tk-card-subtitle">Avanza la tarea según el momento del trabajo.</p>
                        </div>
                    </div>
                    <ol class="tk-progress" aria-label="Progreso de la tarea">
                        <li class="<?= $pasoActual > 1 ? 'is-done' : ($pasoActual === 1 ? 'is-active' : '') ?>"><b><?php if ($pasoActual > 1): ?><i class="fas fa-check" aria-hidden="true"></i><?php else: ?>1<?php endif; ?></b><span>Asignar</span></li>
                        <li class="<?= $pasoActual > 2 ? 'is-done' : ($pasoActual === 2 ? 'is-active' : '') ?>"><b><?php if ($pasoActual > 2): ?><i class="fas fa-check" aria-hidden="true"></i><?php else: ?>2<?php endif; ?></b><span>Iniciar</span></li>
                        <li class="<?= $pasoActual > 3 ? 'is-done' : ($pasoActual === 3 ? 'is-active' : '') ?>"><b><?php if ($pasoActual > 3): ?><i class="fas fa-check" aria-hidden="true"></i><?php else: ?>3<?php endif; ?></b><span>Finalizar</span></li>
                    </ol>
                    <div class="tk-next-callout">
                        <i class="fas <?= tlm_safe($siguientePaso['icono']) ?>" aria-hidden="true"></i>
                        <div>
                            <strong><?= tlm_safe($siguientePaso['titulo']) ?></strong>
                            <p><?= tlm_safe($siguientePaso['texto']) ?></p>
                        </div>
                    </div>
                    <?php if (!$puedeCambiarEstado): ?>
                        <div class="tk-empty-box">Esta tarea ya no necesita cambios de estado.</div>
                    <?php else: ?>
                        <div class="tk-stack">
                            <?php if ($estadoGlobalError !== ''): ?>
                                <div class="tk-error-summary ms-form-error-summary" role="alert"><?= $estadoGlobalError ?></div>
                            <?php endif; ?>

                            <?php if (in_array($estado, ['pendiente', 'asignada'], true)): ?>
                                <form method="POST" action="<?= url('tareas/' . $tareaId . '/iniciar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="tk-btn tk-btn-brand" type="submit"><i class="fas fa-play"></i> <?= $estado === 'asignada' ? 'Iniciar tarea' : 'Iniciar sin asignar' ?></button>
                                </form>
                            <?php endif; ?>

                            <?php if ($estado === 'en_proceso'): ?>
                                <a class="tk-btn tk-btn-brand" href="#tk-finalizar"><i class="fas fa-arrow-down"></i> Ir a finalizar</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="tk-card tk-final-card" id="tk-finalizar">
                    <div class="tk-card-head">
                        <div>
                            <h2><?= $estado === 'en_proceso' ? 'Finalizar trabajo' : 'Otras acciones' ?></h2>
                            <p class="tk-card-subtitle"><?= $estado === 'en_proceso' ? 'Confirma el resultado cuando el trabajo termine.' : 'Completa directamente o cancela solo cuando corresponda.' ?></p>
                        </div>
                    </div>
                    <?php if (!$puedeCambiarEstado): ?>
                        <div class="tk-empty-box">No hay acciones adicionales disponibles.</div>
                    <?php else: ?>
                        <div class="tk-stack">

                            <?php if ($estado !== 'en_proceso'): ?>
                            <details class="tk-disclosure"<?= $comentarioCompletarError !== '' ? ' open' : '' ?>>
                                <summary><i class="fas fa-check" aria-hidden="true"></i>Completar directamente</summary>
                                <div class="tk-disclosure-body">
                            <?php endif; ?>
                            <form method="POST" action="<?= url('tareas/' . $tareaId . '/completar') ?>" class="tk-form-row">
                                <?= csrf_field() ?>
                                <label class="tk-label" for="tk_completar_comentario">Nota de cierre</label>
                                <textarea class="tk-field<?= $comentarioCompletarError !== '' ? ' tk-field-error' : '' ?>" id="tk_completar_comentario" name="comentario" maxlength="800" placeholder="Nota de cierre (opcional)"<?= $comentarioCompletarError !== '' ? ' aria-invalid="true" aria-describedby="ms-form-error-tk_completar_comentario"' : '' ?>><?= $comentarioCompletarValor ?></textarea>
                                <?php if ($comentarioCompletarError !== ''): ?>
                                    <span id="ms-form-error-tk_completar_comentario" class="tk-form-error ms-form-field-error"><?= $comentarioCompletarError ?></span>
                                <?php endif; ?>
                                <button class="tk-btn tk-btn-success" type="submit"><i class="fas fa-check"></i> Marcar como completada</button>
                            </form>
                            <?php if ($estado !== 'en_proceso'): ?>
                                </div>
                            </details>
                            <?php endif; ?>

                            <details class="tk-disclosure"<?= $comentarioCancelarError !== '' ? ' open' : '' ?>>
                                <summary><i class="fas fa-ban" aria-hidden="true"></i>Cancelar tarea</summary>
                                <div class="tk-disclosure-body">
                            <form method="POST" action="<?= url('tareas/' . $tareaId . '/cancelar') ?>" class="tk-form-row">
                                <?= csrf_field() ?>
                                <label class="tk-label" for="tk_cancelar_comentario">Motivo de cancelaci&oacute;n</label>
                                <textarea class="tk-field<?= $comentarioCancelarError !== '' ? ' tk-field-error' : '' ?>" id="tk_cancelar_comentario" name="comentario" maxlength="800" placeholder="Motivo de cancelaci&oacute;n (opcional)"<?= $comentarioCancelarError !== '' ? ' aria-invalid="true" aria-describedby="ms-form-error-tk_cancelar_comentario"' : '' ?>><?= $comentarioCancelarValor ?></textarea>
                                <?php if ($comentarioCancelarError !== ''): ?>
                                    <span id="ms-form-error-tk_cancelar_comentario" class="tk-form-error ms-form-field-error"><?= $comentarioCancelarError ?></span>
                                <?php endif; ?>
                                <button class="tk-btn tk-btn-danger" type="submit"><i class="fas fa-ban"></i> Confirmar cancelación</button>
                            </form>
                                </div>
                            </details>

                            <div class="tk-help">Estas acciones no tocan Caja. En tareas de limpieza, iniciar manda la ubicación a limpieza y completar o cancelar la libera cuando aplica.</div>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        <?php View::partial('documentos_entidad', [
            'documentosEntidad' => $documentosEntidad,
            'documentosEntidadContexto' => [
                'tipo' => 'tarea',
                'id' => $tareaId,
                'label' => 'Tarea',
            ],
            'documentosEntidadPermiteVerTodos' => true,
            'documentosEntidadPermiteVincular' => true,
        ]); ?>
    </div>
</div>
