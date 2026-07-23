<?php
if (!function_exists('lim_rep_safe')) {
    function lim_rep_safe($value, string $fallback = '-'): string
    {
        $text = trim((string)($value ?? ''));
        return htmlspecialchars($text !== '' ? $text : $fallback, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lim_rep_num')) {
    function lim_rep_num($value): string
    {
        return number_format((int)($value ?? 0));
    }
}

if (!function_exists('lim_rep_date')) {
    function lim_rep_date($value): string
    {
        if (empty($value)) {
            return '-';
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
    }
}

$reporte = is_array($reporte ?? null) ? $reporte : [];
$resumen = is_array($reporte['resumen'] ?? null) ? $reporte['resumen'] : [];
$habitaciones = is_array($reporte['habitaciones'] ?? null) ? $reporte['habitaciones'] : [];
$porPiso = is_array($reporte['por_piso'] ?? null) ? $reporte['por_piso'] : [];
$tareasActivas = is_array($reporte['tareas_activas'] ?? null) ? $reporte['tareas_activas'] : [];
$puedeCrearTareaLimpieza = function_exists('can') ? can('habitaciones.mantenimiento') : false;
?>

<style>
.lim-rep{
    --lr-ink:#172033;
    --lr-muted:#64748b;
    --lr-line:#d8e3df;
    --lr-panel:#FFFFFF;
    --lr-clean:#28766f;
    --lr-clean-dark:#24443f;
    --lr-water:#2f83a5;
    --lr-sun:#b58a38;
    width:100%;
    max-width:none;
    margin:0;
    padding:24px 28px 36px;
    color:var(--lr-ink);
}
.lim-rep-hero{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:22px;align-items:start;margin-bottom:20px}
.lim-rep-kicker{display:inline-flex;align-items:center;gap:8px;color:var(--lr-muted);font-size:11px;font-weight:900;letter-spacing:.1em;text-transform:uppercase}
.lim-rep-kicker::before{content:"";width:24px;height:1px;background:var(--lr-clean-dark);opacity:.55}
.lim-rep-title{margin:4px 0 4px;color:#111827;font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:clamp(2.2rem,3.2vw,3.15rem);font-weight:700;line-height:.98;letter-spacing:0}
.lim-rep-subtitle{max-width:780px;margin:0;color:var(--lr-muted);font-size:.95rem;font-weight:650;line-height:1.42}
.lim-rep-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}
.lim-rep-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:42px;padding:0 15px;border:1px solid var(--lr-line);border-radius:8px;background:#fff;color:var(--lr-ink);font-size:.84rem;font-weight:900;text-decoration:none;box-shadow:0 1px 2px rgba(15,23,42,.04)}
.lim-rep-btn:hover{border-color:color-mix(in srgb,var(--lr-clean) 42%,var(--lr-line));background:#F5F5F7}
.lim-rep-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px}
.lim-rep-metric{position:relative;min-height:104px;padding:16px 18px;border:1px solid var(--lr-line);border-radius:8px;background:rgba(255,255,255,.88);box-shadow:0 8px 22px -18px rgba(15,23,42,.35);overflow:hidden}
.lim-rep-metric::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--lr-clean)}
.lim-rep-metric:nth-child(2)::before{background:var(--lr-water)}
.lim-rep-metric:nth-child(3)::before{background:#647acb}
.lim-rep-metric:nth-child(4)::before{background:var(--lr-sun)}
.lim-rep-metric:nth-child(5)::before{background:var(--lr-clean-dark)}
.lim-rep-metric span{display:block;color:var(--lr-muted);font-size:.74rem;font-weight:900;letter-spacing:.055em;line-height:1.1;text-transform:uppercase}
.lim-rep-metric strong{display:block;margin-top:14px;color:#111827;font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;font-size:2.65rem;font-weight:750;line-height:.82}
.lim-rep-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,380px);gap:18px;align-items:start}
.lim-rep-panel{border:1px solid var(--lr-line);border-radius:10px;background:var(--lr-panel);box-shadow:0 14px 34px -28px rgba(15,23,42,.42);overflow:hidden}
.lim-rep-panel-head{padding:18px 20px;border-bottom:1px solid var(--lr-line);background:#fff}
.lim-rep-panel-title{margin:0;color:#111827;font-size:1.12rem;font-weight:950;line-height:1.15}
.lim-rep-panel-subtitle{margin:5px 0 0;color:var(--lr-muted);font-size:.86rem;font-weight:750;line-height:1.35}
.lim-rep-table-wrap{overflow-x:auto}
.lim-rep-table{width:100%;border-collapse:collapse;min-width:960px}
.lim-rep-table th{padding:13px 16px;border-bottom:1px solid var(--lr-line);background:#fff;color:var(--lr-muted);font-size:.72rem;font-weight:950;letter-spacing:.075em;text-align:left;text-transform:uppercase}
.lim-rep-table td{padding:16px;border-bottom:1px solid #e9f0ed;vertical-align:top;color:var(--lr-ink);font-size:.92rem}
.lim-rep-table tbody tr:hover{background:#F5F5F7}
.lim-rep-link{color:#102a43;font-size:1rem;font-weight:950;text-decoration:none}
.lim-rep-link:hover{color:var(--lr-clean)}
.lim-rep-muted{margin-top:5px;color:var(--lr-muted);font-size:.82rem;font-weight:700;line-height:1.38}
.lim-rep-pill{display:inline-flex;align-items:center;gap:6px;min-height:30px;padding:5px 10px;border:1px solid #badfd8;border-radius:999px;background:#e9f8f4;color:#17645d;font-size:.76rem;font-weight:950;line-height:1}
.lim-rep-pill::before{content:"";width:6px;height:6px;border-radius:999px;background:currentColor;opacity:.82}
.lim-rep-pill.is-task{border-color:#c7cef6;background:#f0f2ff;color:#3730a3}
.lim-rep-pill.is-empty{border-color:#d8dee8;background:#f8fafc;color:#64748b}
.lim-rep-inline-form{margin-top:9px}
.lim-rep-task-create{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:38px;padding:0 13px;border:0;border-radius:7px;background:var(--lr-clean-dark);color:#fff;font-size:.78rem;font-weight:950;cursor:pointer}
.lim-rep-task-create:hover{background:#172f2b}
.lim-rep-task-create.is-confirming{background:#8a5b12}
.lim-rep-toast{position:fixed;right:18px;bottom:calc(18px + env(safe-area-inset-bottom));z-index:15000;max-width:min(390px,calc(100vw - 32px));padding:11px 13px;border:1px solid #e7c470;border-radius:8px;background:#fff8e8;color:#7c4d08;box-shadow:0 18px 42px rgba(24,32,48,.18);font-size:.78rem;font-weight:850;line-height:1.42;opacity:0;transform:translateY(10px);pointer-events:none;transition:opacity .18s ease,transform .18s ease}
.lim-rep-toast.is-visible{opacity:1;transform:translateY(0)}
.lim-rep-side{display:flex;flex-direction:column;gap:16px}
.lim-rep-list{display:flex;flex-direction:column}
.lim-rep-list-item{padding:14px 16px;border-bottom:1px solid #e9f0ed}
.lim-rep-list-item:last-child{border-bottom:0}
.lim-rep-empty{padding:34px 20px;text-align:center;color:var(--lr-muted)}
.lim-rep-empty strong{display:block;margin-bottom:8px;color:var(--lr-ink);font-size:1rem}
.lim-rep-empty p{margin:0;font-size:.84rem}
.lim-rep-note{padding:14px 18px;border-top:1px solid var(--lr-line);background:#fff;color:#5f6b7a;font-size:.8rem;font-weight:750;line-height:1.38}
.lim-rep-mobile-list{display:none}
@media (max-width:1100px){
    .lim-rep-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
    .lim-rep-layout{grid-template-columns:1fr}
    .lim-rep-table{min-width:900px}
}
@media (max-width:720px){
    .lim-rep{padding:12px 10px 18px}
    .lim-rep-hero{grid-template-columns:1fr;gap:9px;margin-bottom:9px}
    .lim-rep-actions{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));justify-content:stretch}
    .lim-rep-btn{width:100%;min-height:40px;padding:0 8px;font-size:.68rem}
    .lim-rep-title{font-size:1.45rem}
    .lim-rep-subtitle{display:-webkit-box;font-size:.72rem;line-height:1.32;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .lim-rep-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:6px;margin-bottom:8px}
    .lim-rep-metric{min-height:62px;padding:8px 8px 7px}
    .lim-rep-metric span{font-size:.52rem;letter-spacing:.035em}
    .lim-rep-metric strong{margin-top:6px;font-size:1.45rem}
    .lim-rep-layout{gap:10px}
    .lim-rep-panel{border-radius:16px;overflow:hidden}
    .lim-rep-panel-head{padding:12px;border-bottom:1px solid var(--lr-line)}
    .lim-rep-panel-title{font-size:.84rem}
    .lim-rep-panel-subtitle{font-size:.68rem}
    .lim-rep-table-wrap{overflow:visible;padding:0;background:#F5F5F7}
    .lim-rep-table{display:none!important}
    .lim-rep-mobile-list{display:grid;gap:10px;padding:10px;background:#F5F5F7}
    .lim-rep-room-card{position:relative;display:grid;gap:9px;min-width:0;overflow:hidden;border:1px solid var(--lr-line);border-radius:16px;background:#FFFFFF;box-shadow:0 12px 26px -22px rgba(15,23,42,.42)}
    .lim-rep-room-card::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:var(--lr-clean)}
    .lim-rep-room-main{display:grid;gap:9px;min-width:0;padding:12px 12px 4px 15px}
    .lim-rep-room-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;min-width:0}
    .lim-rep-room-title{display:block;color:#102a43;font-size:1rem;font-weight:950;line-height:1.1;text-decoration:none;overflow-wrap:anywhere}
    .lim-rep-room-meta{display:block;margin-top:3px;color:#516070;font-size:.72rem;font-weight:800;overflow-wrap:anywhere}
    .lim-rep-room-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
    .lim-rep-room-cell{min-width:0;padding:8px 9px;border:1px solid #e1ece8;border-radius:12px;background:#fff}
    .lim-rep-room-cell span{display:block;color:#64748b;font-size:.58rem;font-weight:950;letter-spacing:.06em;line-height:1.1;text-transform:uppercase}
    .lim-rep-room-cell strong{display:block;margin-top:4px;color:#172033;font-size:.78rem;font-weight:950;line-height:1.2;overflow-wrap:anywhere}
    .lim-rep-room-review{display:grid;gap:8px;min-width:0;padding:10px 12px 12px 15px;border-top:1px solid #e9f0ed;background:#F5F5F7}
    .lim-rep-room-review-top{display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0}
    .lim-rep-room-review-top .lim-rep-pill{min-width:0;max-width:68%;white-space:normal;line-height:1.2;overflow-wrap:anywhere}
    .lim-rep-room-review-label{display:block;color:#64748b;font-size:.58rem;font-weight:950;letter-spacing:.06em;line-height:1.1;text-transform:uppercase}
    .lim-rep-room-actions{display:grid;grid-template-columns:1fr;gap:7px;min-width:0}
    .lim-rep-room-actions:empty{display:none}
    .lim-rep-room-actions .lim-rep-inline-form{display:block;margin:0}
    .lim-rep-room-actions .lim-rep-task-create{width:100%;min-height:40px;border-radius:11px;font-size:.68rem}
    .lim-rep-side{gap:10px}
    .lim-rep-list-item{padding:10px 12px}
    .lim-rep-list-item .lim-rep-link{font-size:.82rem}
    .lim-rep-muted{font-size:.7rem}
    .lim-rep-pill{min-height:24px;padding:4px 7px;font-size:.62rem}
    .lim-rep-note{padding:9px;font-size:.66rem}
}
@media (max-width:380px){
    .lim-rep-actions{grid-template-columns:1fr}
    .lim-rep-room-grid{grid-template-columns:1fr}
}
@media (prefers-reduced-motion:reduce){
    .lim-rep *{transition:none!important}
}
</style>

<div class="lim-rep">
    <div class="lim-rep-hero">
        <div>
            <div class="lim-rep-kicker">Reportes / Limpieza</div>
            <h1 class="lim-rep-title">Limpieza operativa</h1>
            <p class="lim-rep-subtitle">Vista operativa de habitaciones en limpieza y tareas activas asociadas al hotel actual. No libera habitaciones ni ejecuta automatizaciones; la tarea manual requiere permiso.</p>
        </div>
        <div class="lim-rep-actions">
            <?php $back_arrow_href = back_url('reportes'); $back_arrow_class = 'ms-back--inline'; include APP_PATH . '/views/partials/back_arrow.php'; ?>
            <a class="lim-rep-btn ms-back-legacy" href="<?= back_url('reportes') ?>"><i class="fas fa-arrow-left"></i> Reportes</a>
            <a class="lim-rep-btn" href="<?= url('habitaciones?estado=limpieza') ?>"><i class="fas fa-broom"></i> Habitaciones</a>
        </div>
    </div>

    <div class="lim-rep-grid" aria-label="Resumen de limpieza">
        <div class="lim-rep-metric"><span>Habitaciones activas</span><strong><?= lim_rep_num($resumen['habitaciones_activas'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>En limpieza</span><strong><?= lim_rep_num($resumen['en_limpieza'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Con tarea activa</span><strong><?= lim_rep_num($resumen['con_tarea_activa'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Sin tarea activa</span><strong><?= lim_rep_num($resumen['sin_tarea_activa'] ?? 0) ?></strong></div>
        <div class="lim-rep-metric"><span>Tareas limpieza</span><strong><?= lim_rep_num($resumen['tareas_limpieza_activas'] ?? 0) ?></strong></div>
    </div>

    <div class="lim-rep-layout">
        <section class="lim-rep-panel">
            <div class="lim-rep-panel-head">
                <h2 class="lim-rep-panel-title">Habitaciones en limpieza</h2>
                <p class="lim-rep-panel-subtitle">Ordenadas por ultima actualizacion conocida. La fecha no reemplaza bitacora formal de limpieza.</p>
            </div>

            <?php if (empty($habitaciones)): ?>
                <div class="lim-rep-empty">
                    <strong>Sin habitaciones en limpieza</strong>
                    <p>No hay pendientes de limpieza para el hotel actual.</p>
                </div>
            <?php else: ?>
                <div class="lim-rep-table-wrap">
                    <table class="lim-rep-table">
                        <thead>
                            <tr>
                                <th>Habitacion</th>
                                <th>Tipo / piso</th>
                                <th>Ultima salida</th>
                                <th>Ultima actualizacion</th>
                                <th>Tarea activa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($habitaciones as $habitacion): ?>
                                <?php
                                $tareasCount = (int)($habitacion['tareas_activas_limpieza'] ?? 0);
                                $tareaId = (int)($habitacion['tarea_activa_id'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <a class="lim-rep-link" href="<?= url('habitaciones/' . (int)($habitacion['id'] ?? 0)) ?>">
                                            Habitacion <?= lim_rep_safe($habitacion['numero'] ?? null) ?>
                                        </a>
                                        <div class="lim-rep-muted"><?= lim_rep_safe(ucfirst((string)($habitacion['estado'] ?? 'limpieza'))) ?></div>
                                    </td>
                                    <td>
                                        <?= lim_rep_safe($habitacion['tipo'] ?? null) ?>
                                        <div class="lim-rep-muted">Piso <?= lim_rep_safe($habitacion['piso'] ?? null) ?></div>
                                    </td>
                                    <td><?= lim_rep_safe(lim_rep_date($habitacion['ultima_salida'] ?? null)) ?></td>
                                    <td><?= lim_rep_safe(lim_rep_date($habitacion['updated_at'] ?? null)) ?></td>
                                    <td>
                                        <?php if ($tareasCount > 0 && $tareaId > 0): ?>
                                            <a class="lim-rep-pill is-task" href="<?= url('tareas/' . $tareaId) ?>">
                                                <?= lim_rep_safe($habitacion['tarea_activa_titulo'] ?? ('Tarea #' . $tareaId)) ?>
                                            </a>
                                            <div class="lim-rep-muted"><?= lim_rep_num($tareasCount) ?> tarea(s) activa(s)</div>
                                        <?php else: ?>
                                            <span class="lim-rep-pill is-empty">Sin tarea activa</span>
                                            <?php if ($puedeCrearTareaLimpieza): ?>
                                                <form class="lim-rep-inline-form"
                                                      method="POST"
                                                      action="<?= url('tareas/desde-limpieza/' . (int)($habitacion['id'] ?? 0)) ?>"
                                                      data-ms-confirm
                                                      data-ms-type="info"
                                                      data-ms-icon="check"
                                                      data-ms-title="¿Crear tarea de limpieza?"
                                                      data-ms-msg="Se creará una tarea manual de limpieza para esta habitación."
                                                      data-ms-ok="Crear tarea">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="lim-rep-task-create">
                                                        <i class="fas fa-tasks"></i>
                                                        Crear tarea
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="lim-rep-mobile-list" aria-label="Habitaciones en limpieza en movil">
                        <?php foreach ($habitaciones as $habitacion): ?>
                            <?php
                            $tareasCount = (int)($habitacion['tareas_activas_limpieza'] ?? 0);
                            $tareaId = (int)($habitacion['tarea_activa_id'] ?? 0);
                            ?>
                            <article class="lim-rep-room-card">
                                <div class="lim-rep-room-main">
                                    <div class="lim-rep-room-head">
                                        <div>
                                            <a class="lim-rep-room-title" href="<?= url('habitaciones/' . (int)($habitacion['id'] ?? 0)) ?>">
                                                Habitacion <?= lim_rep_safe($habitacion['numero'] ?? null) ?>
                                            </a>
                                            <span class="lim-rep-room-meta">
                                                <?= lim_rep_safe($habitacion['tipo'] ?? null) ?> - Piso <?= lim_rep_safe($habitacion['piso'] ?? null) ?>
                                            </span>
                                        </div>
                                        <span class="lim-rep-pill"><?= lim_rep_safe(ucfirst((string)($habitacion['estado'] ?? 'limpieza'))) ?></span>
                                    </div>

                                    <div class="lim-rep-room-grid">
                                        <div class="lim-rep-room-cell">
                                            <span>Ultima salida</span>
                                            <strong><?= lim_rep_safe(lim_rep_date($habitacion['ultima_salida'] ?? null)) ?></strong>
                                        </div>
                                        <div class="lim-rep-room-cell">
                                            <span>Actualizacion</span>
                                            <strong><?= lim_rep_safe(lim_rep_date($habitacion['updated_at'] ?? null)) ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="lim-rep-room-review">
                                    <div class="lim-rep-room-review-top">
                                        <span class="lim-rep-room-review-label">Seguimiento</span>
                                        <?php if ($tareasCount > 0 && $tareaId > 0): ?>
                                            <a class="lim-rep-pill is-task" href="<?= url('tareas/' . $tareaId) ?>">
                                                <?= lim_rep_safe($habitacion['tarea_activa_titulo'] ?? ('Tarea #' . $tareaId)) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="lim-rep-pill is-empty">Sin tarea activa</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($tareasCount > 0 && $tareaId > 0): ?>
                                        <div class="lim-rep-muted"><?= lim_rep_num($tareasCount) ?> tarea(s) activa(s)</div>
                                    <?php elseif ($puedeCrearTareaLimpieza): ?>
                                        <div class="lim-rep-room-actions">
                                            <form class="lim-rep-inline-form"
                                                  method="POST"
                                                  action="<?= url('tareas/desde-limpieza/' . (int)($habitacion['id'] ?? 0)) ?>"
                                                  data-ms-confirm
                                                  data-ms-type="info"
                                                  data-ms-icon="check"
                                                  data-ms-title="¿Crear tarea de limpieza?"
                                                  data-ms-msg="Se creará una tarea manual de limpieza para esta habitación."
                                                  data-ms-ok="Crear tarea">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="lim-rep-task-create">
                                                    <i class="fas fa-tasks"></i>
                                                    Crear tarea
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="lim-rep-note">
                LIM-B-A agrega solo creacion manual de tarea de limpieza con permiso y CSRF. No libera habitaciones, no cambia estados, no descuenta inventario y no toca Caja, pagos, abonos, nomina, offline ni /api/sync.
            </div>
        </section>

        <aside class="lim-rep-side">
            <section class="lim-rep-panel">
                <div class="lim-rep-panel-head">
                    <h2 class="lim-rep-panel-title">Por piso</h2>
                    <p class="lim-rep-panel-subtitle">Distribucion actual de habitaciones en limpieza.</p>
                </div>
                <div class="lim-rep-list">
                    <?php if (empty($porPiso)): ?>
                        <div class="lim-rep-list-item lim-rep-muted">Sin habitaciones en limpieza.</div>
                    <?php else: ?>
                        <?php foreach ($porPiso as $piso): ?>
                            <div class="lim-rep-list-item">
                                <strong>Piso <?= lim_rep_safe($piso['piso'] ?? null) ?></strong>
                                <div class="lim-rep-muted"><?= lim_rep_num($piso['total'] ?? 0) ?> habitacion(es)</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lim-rep-panel">
                <div class="lim-rep-panel-head">
                    <h2 class="lim-rep-panel-title">Tareas activas</h2>
                    <p class="lim-rep-panel-subtitle">Categoria limpieza, solo seguimiento operativo.</p>
                </div>
                <div class="lim-rep-list">
                    <?php if (empty($tareasActivas)): ?>
                        <div class="lim-rep-list-item lim-rep-muted">Sin tareas activas de limpieza.</div>
                    <?php else: ?>
                        <?php foreach ($tareasActivas as $tarea): ?>
                            <div class="lim-rep-list-item">
                                <a class="lim-rep-link" href="<?= url('tareas/' . (int)($tarea['id'] ?? 0)) ?>">
                                    <?= lim_rep_safe($tarea['titulo'] ?? ('Tarea #' . (int)($tarea['id'] ?? 0))) ?>
                                </a>
                                <div class="lim-rep-muted">
                                    <?= lim_rep_safe(ucfirst((string)($tarea['estado'] ?? ''))) ?> -
                                    <?= lim_rep_safe(ucfirst((string)($tarea['prioridad'] ?? ''))) ?>
                                    <?php if (!empty($tarea['habitacion_numero'])): ?>
                                        - Hab. <?= lim_rep_safe($tarea['habitacion_numero']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </aside>
    </div>
</div>

<script>
/* Las confirmaciones usan el modal global msConfirm (data-ms-confirm en los forms). */
</script>
