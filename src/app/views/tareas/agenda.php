<?php
if (!function_exists('tlm_agenda_safe')) {
    function tlm_agenda_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('tlm_agenda_num')) {
    function tlm_agenda_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('tlm_agenda_date')) {
    function tlm_agenda_date($value, bool $withTime = false): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        if (!$timestamp) {
            return '-';
        }

        return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
    }
}

if (!function_exists('tk_agenda_estado_meta')) {
    function tk_agenda_estado_meta($estado): array
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

$agenda = is_array($agenda ?? null) ? $agenda : [];
$trabajadores = is_array($trabajadores ?? null) ? $trabajadores : [];
$tablaDisponible = (bool)($tablaDisponible ?? false);
$filtros = is_array($agenda['filtros'] ?? null) ? $agenda['filtros'] : [];
$resumen = is_array($agenda['resumen'] ?? null) ? $agenda['resumen'] : [];
$tareas = is_array($agenda['tareas'] ?? null) ? $agenda['tareas'] : [];
$porTrabajador = is_array($resumen['por_trabajador'] ?? null) ? $resumen['por_trabajador'] : [];

$categoriaLabels = [
    'todos' => 'Todas',
    'limpieza' => 'Limpieza',
    'mantenimiento' => 'Mantenimiento',
    'general' => 'General',
];
$estadoLabels = [
    'activos' => 'Activas',
    'todos' => 'Todos',
    'pendiente' => 'Pendiente',
    'asignada' => 'Asignada',
    'en_proceso' => 'En proceso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];
?>

<style>
.tk-agenda {
    --tk-brand: var(--brand-primary, #1B2746);
    --tk-brand-2: var(--brand-secondary, #0F172A);
    --tk-gold: var(--brand-accent, #BD9441);
    --tk-gold-soft: color-mix(in srgb, var(--tk-gold) 15%, #FFFFFF);
    --tk-gold-line: color-mix(in srgb, var(--tk-gold) 42%, #E4D4B0);
    --tk-gold-ink: color-mix(in srgb, var(--tk-gold) 72%, #000);
    --tk-ivory: #F6F2EA; --tk-ivory-2: #FBF8F2;
    --tk-surface: #FFFFFF; --tk-surface-warm: #FCFAF5;
    --tk-border: color-mix(in srgb, var(--tk-brand) 7%, #E7E1D4);
    --tk-ring: color-mix(in srgb, var(--tk-gold) 32%, transparent);
    --tk-text: #171717; --tk-muted: #667085; --tk-heading: #111827;
    --tk-serif: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
    --tk-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --tk-success: #1E9E63; --tk-success-bg: #E7F4EC;
    --tk-warning: #C2841C; --tk-warning-bg: #FAF0DC;
    --tk-danger: #B4392B; --tk-danger-bg: #F8EAE5;
    --tk-info: #2F77E0; --tk-info-bg: #E6EFFC;
    --tk-proc: #0E8A8A; --tk-proc-bg: #E2F4F4;
    min-height: 100%; color: var(--tk-text); font-family: var(--tk-sans);
    background:
        radial-gradient(1100px 460px at 88% -8%, color-mix(in srgb, var(--tk-gold) 8%, transparent), transparent 60%),
        linear-gradient(180deg, var(--tk-ivory-2), var(--tk-ivory));
}
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap');

.tk-agenda .tk-shell { display: grid; gap: 14px; }
.tk-agenda .tk-title-lockup { display: grid; grid-template-columns: 48px minmax(0, 1fr); align-items: center; column-gap: 14px; min-width: 0; }
.tk-agenda .tk-hero-icon { width: 48px; height: 48px; border-radius: 15px; display: grid; place-items: center; color: #fff; font-size: 1.15rem;
    background: radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%), linear-gradient(145deg, var(--tk-gold), var(--tk-brand) 54%, color-mix(in srgb, var(--tk-brand) 68%, var(--brand-accent, #BD9441)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--tk-brand) 72%, transparent); }
.tk-agenda .tk-kicker { margin: 0 0 2px; color: var(--tk-muted); font-size: .72rem; font-weight: 700; letter-spacing: .11em; line-height: 1; text-transform: uppercase; }
.tk-agenda .tk-title { margin: 0; font-family: var(--tk-serif); color: var(--tk-heading); font-weight: 700; font-size: clamp(2rem, 3.6vw, 2.9rem); line-height: 1; }
.tk-agenda .tk-subtitle { max-width: 48rem; margin: 8px 0 0; color: var(--tk-muted); font-size: .92rem; font-weight: 500; line-height: 1.5; }

.tk-agenda .tk-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 40px; padding: 0 16px;
    border-radius: 11px; border: 1px solid transparent; font-weight: 700; font-size: .85rem; line-height: 1; cursor: pointer; text-decoration: none;
    transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, color .16s ease, background .16s ease; }
.tk-agenda .tk-btn:hover { transform: translateY(-1px); }
.tk-agenda .tk-btn-brand { background: linear-gradient(135deg, var(--tk-brand), var(--tk-brand-2)); color: #fff; box-shadow: 0 10px 22px -10px color-mix(in srgb, var(--tk-brand) 60%, transparent); }
.tk-agenda .tk-btn-muted { background: var(--tk-surface); border-color: var(--tk-border); color: var(--tk-muted); }

.tk-agenda .tk-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.tk-agenda .tk-summary-item { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 14px; padding: 12px 14px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 10px 24px -18px rgba(27,39,70,.22); }
.tk-agenda .tk-summary-label { color: var(--tk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.tk-agenda .tk-summary-value { margin-top: 2px; font-family: var(--tk-serif); font-size: 1.6rem; font-weight: 700; line-height: 1.1; color: var(--tk-heading); }

.tk-agenda .tk-panel { background: var(--tk-surface); border: 1px solid var(--tk-border); border-radius: 16px; box-shadow: 0 1px 2px rgba(27,39,70,.04), 0 14px 32px -24px rgba(27,39,70,.28); }
.tk-agenda .tk-filter-form { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)) 1.4fr repeat(2, minmax(0,1fr)) auto; gap: 10px; align-items: center; }
.tk-agenda .tk-control { width: 100%; min-height: 40px; border: 1px solid var(--tk-border); background: var(--tk-surface-warm); border-radius: 11px; padding: 0 12px; color: var(--tk-text); font-weight: 600; font-size: .85rem; transition: border-color .16s ease, box-shadow .16s ease; }
.tk-agenda .tk-control:focus { border-color: var(--tk-gold); box-shadow: 0 0 0 3px var(--tk-ring); outline: none; }
.tk-agenda select.tk-control { cursor: pointer; }

.tk-agenda .tk-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 14px; align-items: start; }
.tk-agenda .tk-table-wrap { overflow-x: auto; }
.tk-agenda .tk-table { width: 100%; border-collapse: collapse; min-width: 880px; font-size: .84rem; }
.tk-agenda .tk-table thead { background: var(--tk-surface-warm); border-bottom: 1px solid var(--tk-border); }
.tk-agenda .tk-table th { padding: 12px 14px; color: var(--tk-muted); font-size: .66rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; text-align: left; }
.tk-agenda .tk-table td { padding: 13px 14px; border-bottom: 1px solid var(--tk-border); vertical-align: top; }
.tk-agenda .tk-table tbody tr:last-child td { border-bottom: 0; }
.tk-agenda .tk-table tbody tr:hover { background: var(--tk-ivory-2); }
.tk-agenda .tk-link { font-weight: 700; color: var(--tk-heading); text-decoration: none; }
.tk-agenda .tk-link:hover { text-decoration: underline; text-decoration-color: var(--tk-gold); text-underline-offset: 3px; }
.tk-agenda .tk-sub { color: var(--tk-muted); font-size: .74rem; margin-top: 3px; }
.tk-agenda .tk-strong { font-weight: 700; color: var(--tk-heading); }

.tk-agenda .tk-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700; border: 1px solid transparent; }
.tk-agenda .tk-badge.is-pendiente { color: color-mix(in srgb, var(--tk-warning) 82%, #000); background: var(--tk-warning-bg); border-color: color-mix(in srgb, var(--tk-warning) 28%, #fff); }
.tk-agenda .tk-badge.is-asignada { color: color-mix(in srgb, var(--tk-info) 80%, #000); background: var(--tk-info-bg); border-color: color-mix(in srgb, var(--tk-info) 26%, #fff); }
.tk-agenda .tk-badge.is-proceso { color: color-mix(in srgb, var(--tk-proc) 80%, #000); background: var(--tk-proc-bg); border-color: color-mix(in srgb, var(--tk-proc) 26%, #fff); }
.tk-agenda .tk-badge.is-completada { color: color-mix(in srgb, var(--tk-success) 78%, #000); background: var(--tk-success-bg); border-color: color-mix(in srgb, var(--tk-success) 26%, #fff); }
.tk-agenda .tk-badge.is-cancelada, .tk-agenda .tk-badge.is-soft { color: var(--tk-muted); background: var(--tk-surface-warm); border-color: var(--tk-border); }

.tk-agenda .tk-side-head { padding: 15px 16px; border-bottom: 1px solid var(--tk-border); }
.tk-agenda .tk-side-title { font-family: var(--tk-serif); font-size: 1.2rem; font-weight: 700; color: var(--tk-heading); margin: 0; }
.tk-agenda .tk-side-list { padding: 8px 16px; }
.tk-agenda .tk-side-row { display: flex; justify-content: space-between; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--tk-border); font-size: .86rem; }
.tk-agenda .tk-side-row:last-child { border-bottom: 0; }
.tk-agenda .tk-side-row strong { color: var(--tk-heading); }
.tk-agenda .tk-empty { padding: 38px 18px; text-align: center; color: var(--tk-muted); }
.tk-agenda .tk-empty strong { display: block; color: var(--tk-brand); font-size: 1.05rem; margin-bottom: 6px; font-weight: 700; }
.tk-agenda .tk-note { padding: 12px 16px; border-top: 1px solid var(--tk-border); color: var(--tk-muted); font-size: .78rem; }
.tk-agenda .tk-notice { display: flex; gap: 12px; align-items: flex-start; padding: 16px 18px; background: var(--tk-gold-soft); border: 1px solid var(--tk-gold-line); border-radius: 16px; }
.tk-agenda .tk-notice i { color: var(--tk-gold-ink); font-size: 1.1rem; margin-top: 2px; }
.tk-agenda .tk-notice strong { color: var(--tk-heading); display: block; margin-bottom: 2px; }
.tk-agenda .tk-notice p { color: var(--tk-muted); font-size: .88rem; margin: 0; }

@media (max-width: 980px) { .tk-agenda .tk-filter-form { grid-template-columns: 1fr 1fr; } .tk-agenda .tk-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .tk-agenda .tk-layout { grid-template-columns: 1fr; } .tk-agenda .tk-title { font-size: 1.8rem; } }
</style>

<div class="tk-agenda p-4 sm:p-6">
    <div class="tk-shell">
        <section class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div class="tk-title-lockup">
                <div class="tk-hero-icon"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <p class="tk-kicker">Operaci&oacute;n del hotel</p>
                    <h1 class="tk-title">Agenda de tareas</h1>
                    <p class="tk-subtitle">Qui&eacute;n tiene qu&eacute; tareas y cu&aacute;ndo, por fecha. Esta vista es solo para consultar.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a class="tk-btn tk-btn-muted" href="<?= back_url('tareas') ?>"><i class="fas fa-arrow-left"></i> Tareas</a>
                <a class="tk-btn tk-btn-muted" href="<?= url('tareas/reporte') ?>"><i class="fas fa-chart-pie"></i> Reporte</a>
            </div>
        </section>

        <?php if (!$tablaDisponible): ?>
            <section class="tk-notice">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Esta secci&oacute;n todav&iacute;a no est&aacute; activada.</strong>
                    <p>P&iacute;dele al administrador del sistema que la habilite para usar la agenda.</p>
                </div>
            </section>
        <?php else: ?>
            <section class="tk-summary">
                <div class="tk-summary-item"><p class="tk-summary-label">Total en el rango</p><p class="tk-summary-value"><?= tlm_agenda_num($resumen['total'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Activas</p><p class="tk-summary-value" style="color:var(--tk-info)"><?= tlm_agenda_num($resumen['activas'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Sin asignar</p><p class="tk-summary-value" style="color:var(--tk-warning)"><?= tlm_agenda_num($resumen['sin_asignar'] ?? 0) ?></p></div>
                <div class="tk-summary-item"><p class="tk-summary-label">Trabajadores</p><p class="tk-summary-value"><?= tlm_agenda_num(count($porTrabajador)) ?></p></div>
            </section>

            <section class="tk-panel p-3 md:p-4">
                <form method="GET" action="<?= url('tareas/agenda') ?>" class="tk-filter-form" data-auto-filter-form>
                    <input class="tk-control" type="date" name="desde" value="<?= tlm_agenda_safe($filtros['desde'] ?? date('Y-m-d'), date('Y-m-d')) ?>">
                    <input class="tk-control" type="date" name="hasta" value="<?= tlm_agenda_safe($filtros['hasta'] ?? date('Y-m-d'), date('Y-m-d')) ?>">
                    <select class="tk-control" name="trabajador_id">
                        <option value="todos">Todos los trabajadores</option>
                        <?php foreach ($trabajadores as $trabajador): ?>
                            <?php $trabajadorId = (int)($trabajador['id'] ?? 0); ?>
                            <option value="<?= $trabajadorId ?>" <?= (string)($filtros['trabajador_id'] ?? 'todos') === (string)$trabajadorId ? 'selected' : '' ?>>
                                <?= tlm_agenda_safe($trabajador['nombre_completo'] ?? null) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="tk-control" name="categoria">
                        <?php foreach ($categoriaLabels as $key => $label): ?>
                            <option value="<?= tlm_agenda_safe($key) ?>" <?= ($filtros['categoria'] ?? 'todos') === $key ? 'selected' : '' ?>><?= tlm_agenda_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="tk-control" name="estado">
                        <?php foreach ($estadoLabels as $key => $label): ?>
                            <option value="<?= tlm_agenda_safe($key) ?>" <?= ($filtros['estado'] ?? 'activos') === $key ? 'selected' : '' ?>><?= tlm_agenda_safe($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="tk-btn tk-btn-brand"><i class="fas fa-filter"></i> Filtrar</button>
                </form>
            </section>

            <div class="tk-layout">
                <section class="tk-panel overflow-hidden">
                    <?php if (empty($tareas)): ?>
                        <div class="tk-empty">
                            <strong>Sin tareas en este rango</strong>
                            Ajusta la fecha, el trabajador, la categor&iacute;a o el estado.
                        </div>
                    <?php else: ?>
                        <div class="tk-table-wrap">
                            <table class="tk-table">
                                <thead>
                                    <tr><th>Fecha</th><th>Tarea</th><th>Trabajador</th><th>Contexto</th><th>Estado</th><th>Prioridad</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tareas as $tarea): ?>
                                        <?php
                                        [$eLabel, $eClass, $eIcon] = tk_agenda_estado_meta($tarea['estado'] ?? 'pendiente');
                                        $categoria = (string)($tarea['categoria'] ?? 'general');
                                        $catLabel = $categoriaLabels[$categoria] ?? ucfirst($categoria);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="tk-strong"><?= tlm_agenda_safe(tlm_agenda_date($tarea['fecha_agenda'] ?? null)) ?></div>
                                                <div class="tk-sub">Prog: <?= tlm_agenda_safe(tlm_agenda_date($tarea['fecha_programada'] ?? null, true)) ?></div>
                                            </td>
                                            <td>
                                                <a class="tk-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>"><?= tlm_agenda_safe($tarea['titulo'] ?? null, 'Tarea #' . (int)($tarea['id'] ?? 0)) ?></a>
                                                <div class="tk-sub"><?= tlm_agenda_safe($catLabel) ?> &middot; origen <?= tlm_agenda_safe($tarea['origen'] ?? 'manual') ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($tarea['trabajador_id'])): ?>
                                                    <a class="tk-link" href="<?= url('trabajadores/' . (int)$tarea['trabajador_id']) ?>"><?= tlm_agenda_safe($tarea['trabajador_nombre'] ?? null) ?></a>
                                                    <div class="tk-sub"><?= tlm_agenda_safe($tarea['trabajador_rol'] ?? null, '') ?></div>
                                                <?php else: ?>
                                                    <span class="tk-sub" style="margin:0">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($tarea['habitacion_id'])): ?>
                                                    <a class="tk-link" href="<?= url('habitaciones/' . (int)$tarea['habitacion_id']) ?>">Hab. <?= tlm_agenda_safe($tarea['habitacion_numero'] ?? (string)$tarea['habitacion_id']) ?></a>
                                                    <div class="tk-sub"><?= tlm_agenda_safe($tarea['habitacion_estado'] ?? null, '') ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($tarea['mantenimiento_id'])): ?>
                                                    <div class="tk-sub">Mant. #<?= (int)$tarea['mantenimiento_id'] ?> <?= tlm_agenda_safe($tarea['tipo_mantenimiento'] ?? null, '') ?></div>
                                                <?php elseif (empty($tarea['habitacion_id'])): ?>
                                                    <span class="tk-sub" style="margin:0">Sin contexto</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="tk-badge <?= $eClass ?>"><i class="fas <?= $eIcon ?>"></i> <?= $eLabel ?></span></td>
                                            <td><?= tlm_agenda_safe(ucfirst((string)($tarea['prioridad'] ?? 'media'))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="tk-panel overflow-hidden">
                    <div class="tk-side-head"><h2 class="tk-side-title">Carga por trabajador</h2></div>
                    <?php if (empty($porTrabajador)): ?>
                        <div class="tk-empty"><strong>Sin carga asignada</strong>Nadie tiene tareas dentro del filtro.</div>
                    <?php else: ?>
                        <div class="tk-side-list">
                            <?php foreach ($porTrabajador as $nombre => $total): ?>
                                <div class="tk-side-row"><span><?= tlm_agenda_safe($nombre) ?></span><strong><?= tlm_agenda_num($total) ?></strong></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="tk-note">Esta vista solo muestra informaci&oacute;n. Los cambios se hacen entrando a cada tarea.</div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</div>
